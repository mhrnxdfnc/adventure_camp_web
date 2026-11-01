<?php

session_start();
require_once 'koneksi.php';

header('Content-Type: application/json; charset=UTF-8');

function response($status, $message, $data = null)
{
    echo json_encode([
        'status' => $status,
        'message' => $message,
        'data' => $data,
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    response('error', 'Metode request tidak diizinkan.');
}

if (
    ! isset($_SESSION['id_user']) ||
    ($_SESSION['role'] ?? '') !== 'Pelanggan' ||
    empty($_SESSION['id_pelanggan'])
) {
    http_response_code(401);
    response('error', 'Silakan login sebagai pelanggan terlebih dahulu.');
}

$input = json_decode(file_get_contents('php://input'), true);

if (! is_array($input)) {
    http_response_code(400);
    response('error', 'Data pengajuan tidak valid.');
}

$tanggalSewa = $input['tanggal_sewa'] ?? '';
$tanggalKembali = $input['tanggal_kembali'] ?? '';
$items = $input['items'] ?? [];

$start = DateTime::createFromFormat('!Y-m-d', $tanggalSewa);
$end = DateTime::createFromFormat('!Y-m-d', $tanggalKembali);
$today = new DateTime('today');

if (
    ! $start || $start->format('Y-m-d') !== $tanggalSewa ||
    ! $end || $end->format('Y-m-d') !== $tanggalKembali
) {
    http_response_code(400);
    response('error', 'Format tanggal tidak valid.');
}

if ($start < $today || $end <= $start) {
    http_response_code(400);
    response('error', 'Tanggal sewa harus hari ini atau setelahnya, dan tanggal kembali harus sesudah tanggal sewa.');
}

if (! is_array($items) || count($items) === 0) {
    http_response_code(400);
    response('error', 'Pilih minimal satu alat.');
}

$pilihan = [];

foreach ($items as $item) {
    $kode = trim($item['kode_alat'] ?? '');
    $jumlah = filter_var(
        $item['jumlah'] ?? null,
        FILTER_VALIDATE_INT
    );

    if ($kode === '' || $jumlah === false || $jumlah < 1) {
        http_response_code(400);
        response('error', 'Kode alat atau jumlah tidak valid.');
    }

    if (isset($pilihan[$kode])) {
        $pilihan[$kode] += $jumlah;
    } else {
        $pilihan[$kode] = $jumlah;
    }
}

$conn->begin_transaction();

try {
    $idUser = (int) $_SESSION['id_user'];
    $idPelanggan = (int) $_SESSION['id_pelanggan'];

    // Pastikan akun pelanggan masih terhubung dengan benar.
    $stmt = $conn->prepare(
        "SELECT id_user
         FROM users
         WHERE id_user = ?
           AND id_pelanggan = ?
           AND role = 'Pelanggan'
           AND status_aktif = 1"
    );
    $stmt->bind_param('ii', $idUser, $idPelanggan);
    $stmt->execute();

    if (! $stmt->get_result()->fetch_assoc()) {
        throw new Exception('Akun pelanggan tidak valid atau tidak aktif.');
    }
    $stmt->close();

    // Ambil tarif dan periksa stok tanpa mengurangi stok.
    $detail = [];
    $durasi = (int) $start->diff($end)->days;

    foreach ($pilihan as $kode => $jumlah) {
        $stmt = $conn->prepare(
            'SELECT kode_alat, nama_alat, stok, kondisi, tarif_sewa
             FROM alat
             WHERE kode_alat = ?
             FOR UPDATE'
        );
        $stmt->bind_param('s', $kode);
        $stmt->execute();
        $alat = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (! $alat) {
            throw new Exception("Alat dengan kode $kode tidak ditemukan.");
        }

        if ($alat['kondisi'] !== 'Baik') {
            throw new Exception($alat['nama_alat'].' sedang tidak tersedia.');
        }

        if ($jumlah > (int) $alat['stok']) {
            throw new Exception(
                'Stok '.$alat['nama_alat'].' tidak mencukupi.'
            );
        }

        $detail[] = [
            'kode_alat' => $alat['kode_alat'],
            'jumlah' => $jumlah,
            'tarif_sewa' => (float) $alat['tarif_sewa'],
        ];
    }

    // Buat nomor transaksi unik.
    $noTransaksi = 'TRX-'.date('Ymd').'-'.strtoupper(bin2hex(random_bytes(4)));

    // Simpan pengajuan untuk diproses admin.
    $stmt = $conn->prepare(
        "INSERT INTO penyewaan
         (no_transaksi, id_pelanggan, id_user,
          tanggal_sewa, tanggal_kembali, status_penyewaan)
         VALUES (?, ?, ?, ?, ?, 'Menunggu Konfirmasi')"
    );

    $stmt->bind_param(
        'siiss',
        $noTransaksi,
        $idPelanggan,
        $idUser,
        $tanggalSewa,
        $tanggalKembali
    );

    $stmt->execute();
    $idPenyewaan = $conn->insert_id;
    $stmt->close();

    $stmt = $conn->prepare(
        'INSERT INTO detail_penyewaan
         (id_penyewaan, no_transaksi, kode_alat, jumlah, tarif_sewa)
         VALUES (?, ?, ?, ?, ?)'
    );

    foreach ($detail as $item) {
        $kode = $item['kode_alat'];
        $jumlah = $item['jumlah'];
        $tarif = $item['tarif_sewa'];

        $stmt->bind_param(
            'issid',
            $idPenyewaan,
            $noTransaksi,
            $kode,
            $jumlah,
            $tarif
        );
        $stmt->execute();
    }

    $stmt->close();

    // Catat aktivitas jika tabel activity_logs tersedia.
    try {
        $aktivitas = 'Pelanggan mengajukan penyewaan '.$noTransaksi;
        $stmt = $conn->prepare(
            'INSERT INTO activity_logs (id_user, aktivitas)
             VALUES (?, ?)'
        );
        $stmt->bind_param('is', $idUser, $aktivitas);
        $stmt->execute();
        $stmt->close();
    } catch (Throwable $logError) {
        // Kegagalan pencatatan log tidak membatalkan transaksi.
    }

    $conn->commit();

    response('success', 'Pengajuan penyewaan berhasil dikirim.', [
        'no_transaksi' => $noTransaksi,
        'status_penyewaan' => 'Menunggu Konfirmasi',
        'durasi_hari' => $durasi,
    ]);

} catch (Throwable $e) {
    $conn->rollback();
    http_response_code(400);
    response('error', $e->getMessage());
}
