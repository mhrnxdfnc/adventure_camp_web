<?php

session_start();
require_once "koneksi.php";

header("Content-Type: application/json; charset=UTF-8");

function kirimRespons($status, $message, $data = [], $httpCode = 200)
{
    http_response_code($httpCode);

    echo json_encode(
        array_merge([
            "status" => $status,
            "message" => $message
        ], $data),
        JSON_UNESCAPED_UNICODE
    );

    exit;
}

// ========================================
// 1. VALIDASI LOGIN PELANGGAN
// ========================================

if (
    !isset($_SESSION["id_user"]) ||
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "Pelanggan"
) {
    kirimRespons(
        "error",
        "Silakan login sebagai pelanggan terlebih dahulu.",
        [],
        401
    );
}

$id_user = (int) $_SESSION["id_user"];
$id_pelanggan = (int) ($_SESSION["id_pelanggan"] ?? 0);

if ($id_user <= 0 || $id_pelanggan <= 0) {
    kirimRespons(
        "error",
        "Akun pengguna tidak terhubung dengan data pelanggan.",
        [],
        403
    );
}

// ========================================
// 2. BACA DATA PEMBAYARAN
// ========================================

$data = json_decode(file_get_contents("php://input"), true);

if (!is_array($data)) {
    kirimRespons("error", "Data pembayaran tidak valid.", [], 400);
}

$no_transaksi = trim((string) ($data["no_transaksi"] ?? ""));
$jumlahInput = $data["jumlah_pembayaran"] ?? null;
$metode = trim((string) ($data["metode_pembayaran"] ?? ""));

if (
    $no_transaksi === "" ||
    !is_numeric($jumlahInput) ||
    (float) $jumlahInput <= 0 ||
    $metode === ""
) {
    kirimRespons(
        "error",
        "Nomor transaksi, jumlah pembayaran, dan metode pembayaran wajib diisi.",
        [],
        400
    );
}

$jumlah_pembayaran = (float) $jumlahInput;

$metodeDiizinkan = ["Transfer Bank", "QRIS"];

if (!in_array($metode, $metodeDiizinkan, true)) {
    kirimRespons(
        "error",
        "Metode pembayaran harus Transfer Bank atau QRIS.",
        [],
        400
    );
}

// ========================================
// 3. MULAI TRANSAKSI DATABASE
// ========================================

try {
    $conn->begin_transaction();

    // Kunci data penyewaan agar dua permintaan pembayaran
    // tidak menghitung sisa tagihan secara bersamaan.
    $stmt = $conn->prepare("
        SELECT
            p.no_transaksi,
            COALESCE(
                SUM(
                    d.jumlah *
                    d.tarif_sewa *
                    GREATEST(
                        DATEDIFF(
                            p.tanggal_kembali,
                            p.tanggal_sewa
                        ),
                        1
                    )
                ),
                0
            ) AS total
        FROM penyewaan p
        JOIN detail_penyewaan d
            ON p.no_transaksi = d.no_transaksi
        WHERE p.no_transaksi = ?
          AND p.id_pelanggan = ?
          AND p.status_penyewaan = 'Menunggu Pembayaran'
        GROUP BY p.no_transaksi
        FOR UPDATE
    ");

    $stmt->bind_param("si", $no_transaksi, $id_pelanggan);
    $stmt->execute();

    $transaksi = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$transaksi) {
        throw new Exception(
            "Transaksi tidak ditemukan, bukan milik Anda, atau belum disetujui untuk pembayaran."
        );
    }

    $total = (float) $transaksi["total"];

    if ($total <= 0) {
        throw new Exception(
            "Total tagihan tidak valid. Silakan hubungi Admin."
        );
    }

    // ========================================
    // 4. HITUNG TOTAL PEMBAYARAN SEBELUMNYA
    // ========================================

    // Semua pembayaran yang sudah tercatat ikut dihitung,
    // termasuk yang masih menunggu verifikasi.
    $stmtCek = $conn->prepare("
        SELECT COALESCE(SUM(jumlah_pembayaran), 0) AS total_dibayar
        FROM pembayaran
        WHERE no_transaksi = ?
    ");

    $stmtCek->bind_param("s", $no_transaksi);
    $stmtCek->execute();

    $dataPembayaran = $stmtCek->get_result()->fetch_assoc();
    $stmtCek->close();

    $total_dibayar = (float) ($dataPembayaran["total_dibayar"] ?? 0);
    $sisa_tagihan = max(0, $total - $total_dibayar);

    if ($sisa_tagihan <= 0) {
        throw new Exception(
            "Tagihan sudah terbayar seluruhnya. Tidak ada sisa tagihan."
        );
    }

    // ========================================
    // 5. VALIDASI JUMLAH PEMBAYARAN
    // ========================================

    if ($jumlah_pembayaran > $sisa_tagihan) {
        throw new Exception(
            "Nominal pembayaran melebihi sisa tagihan Rp" .
            number_format($sisa_tagihan, 0, ",", ".") .
            ". Silakan masukkan nominal yang benar."
        );
    }

    // ========================================
    // 6. BUAT NOMOR PEMBAYARAN
    // ========================================

    $prefix = "PAY-" . date("Ymd") . "-";

    $stmtLast = $conn->prepare("
        SELECT no_pembayaran
        FROM pembayaran
        WHERE no_pembayaran LIKE ?
        ORDER BY no_pembayaran DESC
        LIMIT 1
    ");

    $like = $prefix . "%";
    $stmtLast->bind_param("s", $like);
    $stmtLast->execute();

    $resultLast = $stmtLast->get_result();
    $urutan = 1;

    if ($resultLast->num_rows > 0) {
        $last = $resultLast->fetch_assoc()["no_pembayaran"];

        $bagianAkhir = substr(
            $last,
            strrpos($last, "-") + 1
        );

        $urutan = ((int) $bagianAkhir) + 1;
    }

    $stmtLast->close();

    $no_pembayaran = $prefix . str_pad(
        (string) $urutan,
        3,
        "0",
        STR_PAD_LEFT
    );

    // ========================================
    // 7. SIMPAN PEMBAYARAN BARU
    // ========================================

    // Pembayaran tetap perlu diverifikasi oleh Admin.
    $status = "Menunggu Verifikasi";

    $stmtInsert = $conn->prepare("
        INSERT INTO pembayaran (
            no_pembayaran,
            no_transaksi,
            total_biaya,
            jumlah_pembayaran,
            metode_pembayaran,
            status_pembayaran
        )
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    $stmtInsert->bind_param(
        "ssddss",
        $no_pembayaran,
        $no_transaksi,
        $total,
        $jumlah_pembayaran,
        $metode,
        $status
    );

    $stmtInsert->execute();
    $stmtInsert->close();

    // ========================================
    // 8. CATAT AKTIVITAS
    // ========================================

    $aktivitas = "Mencatat pembayaran";
    $modul = "Pembayaran";

    $stmtLog = $conn->prepare("
        INSERT INTO activity_logs (
            id_user,
            aktivitas,
            modul,
            data_diproses
        )
        VALUES (?, ?, ?, ?)
    ");

    $stmtLog->bind_param(
        "isss",
        $id_user,
        $aktivitas,
        $modul,
        $no_transaksi
    );

    $stmtLog->execute();
    $stmtLog->close();

    // ========================================
    // 9. HITUNG SISA SETELAH PEMBAYARAN BARU
    // ========================================

    $total_dibayar_baru = $total_dibayar + $jumlah_pembayaran;
    $sisa_setelah_bayar = max(0, $total - $total_dibayar_baru);

    $conn->commit();

    kirimRespons(
        "success",
        "Pembayaran berhasil dicatat dan menunggu verifikasi Admin.",
        [
            "no_pembayaran" => $no_pembayaran,
            "no_transaksi" => $no_transaksi,
            "total_biaya" => $total,
            "total_dibayar_sebelumnya" => $total_dibayar,
            "jumlah_pembayaran" => $jumlah_pembayaran,
            "total_dibayar" => $total_dibayar_baru,
            "sisa_tagihan" => $sisa_setelah_bayar,
            "status_pembayaran" => $status
        ]
    );

} catch (Throwable $e) {
    try {
        $conn->rollback();
    } catch (Throwable $rollbackError) {
        // Abaikan jika transaksi sudah tidak aktif.
    }

    error_log("api_post_pembayaran.php: " . $e->getMessage());

    kirimRespons(
        "error",
        $e->getMessage(),
        [],
        400
    );
}

$conn->close();