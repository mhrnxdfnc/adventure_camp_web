<?php

include 'koneksi.php';

$data = json_decode(file_get_contents('php://input'), true);

$id_pelanggan = (int) ($data['id_pelanggan'] ?? 0);
$id_user = (int) ($data['id_user'] ?? 0);
$tanggal_sewa = $data['tanggal_sewa'] ?? '';
$tanggal_kembali = $data['tanggal_kembali'] ?? '';
$items = $data['items'] ?? [];

if (
    $id_pelanggan <= 0 ||
    $id_user <= 0 ||
    $tanggal_sewa === '' ||
    $tanggal_kembali === '' ||
    empty($items)
) {

    echo json_encode([
        'status' => 'error',
        'message' => 'Data transaksi belum lengkap',
    ]);

    exit;
}

if ($tanggal_kembali < $tanggal_sewa) {

    echo json_encode([
        'status' => 'error',
        'message' => 'Tanggal kembali tidak boleh sebelum tanggal sewa',
    ]);

    exit;
}

$conn->begin_transaction();

try {

    /*
      Generate nomor transaksi
      Contoh:
      TRX-20261004-001
    */

    $prefix = 'TRX-'.date('Ymd').'-';

    $stmtLast = $conn->prepare('
        SELECT no_transaksi
        FROM penyewaan
        WHERE no_transaksi LIKE ?
        ORDER BY no_transaksi DESC
        LIMIT 1
    ');

    $like = $prefix.'%';

    $stmtLast->bind_param('s', $like);
    $stmtLast->execute();

    $resultLast = $stmtLast->get_result();

    $urutan = 1;

    if ($resultLast->num_rows > 0) {

        $last = $resultLast->fetch_assoc()['no_transaksi'];

        $lastNumber = (int) substr(
            $last,
            strrpos($last, '-') + 1
        );

        $urutan = $lastNumber + 1;
    }

    $no_transaksi =
        $prefix.
        str_pad($urutan, 3, '0', STR_PAD_LEFT);

    /*
      Insert penyewaan
    */

    $stmtSewa = $conn->prepare("
        INSERT INTO penyewaan
        (
            no_transaksi,
            id_pelanggan,
            id_user,
            tanggal_sewa,
            tanggal_kembali,
            status_penyewaan
        )
        VALUES (?, ?, ?, ?, ?, 'Berjalan')
    ");

    $stmtSewa->bind_param(
        'siiss',
        $no_transaksi,
        $id_pelanggan,
        $id_user,
        $tanggal_sewa,
        $tanggal_kembali
    );

    $stmtSewa->execute();

    /*
      Proses setiap alat
    */

    foreach ($items as $item) {

        $kode_alat = trim($item['kode_alat'] ?? '');
        $jumlah = (int) ($item['jumlah'] ?? 0);
        $tarif = (float) ($item['tarif_sewa'] ?? 0);

        if ($kode_alat === '' || $jumlah <= 0) {
            throw new Exception(
                'Detail alat tidak valid'
            );
        }

        /*
          Kunci stok untuk mencegah stok minus
        */

        $stmtAlat = $conn->prepare('
            SELECT
                nama_alat,
                stok,
                kondisi,
                tarif_sewa
            FROM alat
            WHERE kode_alat = ?
            FOR UPDATE
        ');

        $stmtAlat->bind_param(
            's',
            $kode_alat
        );

        $stmtAlat->execute();

        $resultAlat =
            $stmtAlat->get_result();

        if ($resultAlat->num_rows === 0) {

            throw new Exception(
                "Alat $kode_alat tidak ditemukan"
            );
        }

        $alat = $resultAlat->fetch_assoc();

        if ($alat['kondisi'] === 'Rusak') {

            throw new Exception(
                "Alat {$alat['nama_alat']} sedang rusak"
            );
        }

        if ((int) $alat['stok'] < $jumlah) {

            throw new Exception(
                "Stok {$alat['nama_alat']} tidak mencukupi"
            );
        }

        /*
          Simpan detail
        */

        $tarifDatabase =
            (float) $alat['tarif_sewa'];

        $stmtDetail = $conn->prepare('
            INSERT INTO detail_penyewaan
            (
                no_transaksi,
                kode_alat,
                jumlah,
                tarif_sewa
            )
            VALUES (?, ?, ?, ?)
        ');

        $stmtDetail->bind_param(
            'ssid',
            $no_transaksi,
            $kode_alat,
            $jumlah,
            $tarifDatabase
        );

        $stmtDetail->execute();

        /*
          Kurangi stok
        */

        $stmtStok = $conn->prepare('
            UPDATE alat
            SET stok = stok - ?
            WHERE kode_alat = ?
        ');

        $stmtStok->bind_param(
            'is',
            $jumlah,
            $kode_alat
        );

        $stmtStok->execute();
    }

    /*
      Audit trail
    */

    $aktivitas = 'Membuat transaksi penyewaan';
    $modul = 'Penyewaan';
    $dataDiproses = $no_transaksi;

    $stmtLog = $conn->prepare('
        INSERT INTO activity_logs
        (
            id_user,
            aktivitas,
            modul,
            data_diproses
        )
        VALUES (?, ?, ?, ?)
    ');

    $stmtLog->bind_param(
        'isss',
        $id_user,
        $aktivitas,
        $modul,
        $dataDiproses
    );

    $stmtLog->execute();

    $conn->commit();

    echo json_encode([
        'status' => 'success',
        'message' => 'Transaksi berhasil dibuat',
        'no_transaksi' => $no_transaksi,
    ]);

} catch (Exception $e) {

    $conn->rollback();

    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
    ]);
}

$conn->close();
