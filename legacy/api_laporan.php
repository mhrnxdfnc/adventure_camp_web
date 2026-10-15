<?php

include "koneksi.php";

$jenis = $_GET["jenis"] ?? "";

$dari = $_GET["dari"] ?? "";
$sampai = $_GET["sampai"] ?? "";
$cari = $_GET["cari"] ?? "";

if ($jenis === "penyewaan") {

    $sql = "
        SELECT
            p.no_transaksi,
            p.tanggal_sewa,
            p.tanggal_kembali,
            pl.nama_pelanggan,
            u.nama_user,
            p.status_penyewaan,

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
            ) AS total_biaya

        FROM penyewaan p

        JOIN pelanggan pl
            ON p.id_pelanggan = pl.id_pelanggan

        JOIN users u
            ON p.id_user = u.id_user

        LEFT JOIN detail_penyewaan d
            ON p.no_transaksi = d.no_transaksi

        WHERE 1=1
    ";

    $params = [];
    $types = "";

    if ($dari !== "") {
        $sql .= " AND p.tanggal_sewa >= ?";
        $params[] = $dari;
        $types .= "s";
    }

    if ($sampai !== "") {
        $sql .= " AND p.tanggal_sewa <= ?";
        $params[] = $sampai;
        $types .= "s";
    }

    if ($cari !== "") {
        $sql .= "
            AND (
                p.no_transaksi LIKE ?
                OR pl.nama_pelanggan LIKE ?
            )
        ";

        $keyword = "%$cari%";

        $params[] = $keyword;
        $params[] = $keyword;

        $types .= "ss";
    }

    $sql .= "
        GROUP BY
            p.no_transaksi,
            p.tanggal_sewa,
            p.tanggal_kembali,
            pl.nama_pelanggan,
            u.nama_user,
            p.status_penyewaan

        ORDER BY p.tanggal_sewa DESC
    ";

    $stmt = $conn->prepare($sql);

    if (!empty($params)) {
        $stmt->bind_param(
            $types,
            ...$params
        );
    }

    $stmt->execute();

    $result = $stmt->get_result();

    $data = [];

    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    echo json_encode([
        "status" => "success",
        "data" => $data
    ]);

    exit;
}


if ($jenis === "pendapatan") {

    $sql = "
        SELECT
            b.no_pembayaran,
            b.no_transaksi,
            b.tanggal_pembayaran,
            pl.nama_pelanggan,
            b.total_biaya,
            b.jumlah_pembayaran,
            b.metode_pembayaran,
            b.status_pembayaran

        FROM pembayaran b

        JOIN penyewaan p
            ON b.no_transaksi = p.no_transaksi

        JOIN pelanggan pl
            ON p.id_pelanggan = pl.id_pelanggan

        WHERE 1=1
    ";

    $params = [];
    $types = "";

    if ($dari !== "") {
        $sql .= " AND DATE(b.tanggal_pembayaran) >= ?";
        $params[] = $dari;
        $types .= "s";
    }

    if ($sampai !== "") {
        $sql .= " AND DATE(b.tanggal_pembayaran) <= ?";
        $params[] = $sampai;
        $types .= "s";
    }

    if ($cari !== "") {

        $sql .= "
            AND (
                b.no_transaksi LIKE ?
                OR pl.nama_pelanggan LIKE ?
            )
        ";

        $keyword = "%$cari%";

        $params[] = $keyword;
        $params[] = $keyword;

        $types .= "ss";
    }

    $sql .= "
        ORDER BY b.tanggal_pembayaran DESC
    ";

    $stmt = $conn->prepare($sql);

    if (!empty($params)) {
        $stmt->bind_param(
            $types,
            ...$params
        );
    }

    $stmt->execute();

    $result = $stmt->get_result();

    $data = [];

    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    echo json_encode([
        "status" => "success",
        "data" => $data
    ]);

    exit;
}


// Jika jenis kosong atau role dikirim (seperti dari Flutter LaporanScreen)
$resPenyewaan = $conn->query("
    SELECT
        p.no_transaksi,
        p.tanggal_sewa,
        p.tanggal_kembali,
        pl.nama_pelanggan,
        u.nama_user,
        p.status_penyewaan,
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
        ) AS total_biaya
    FROM penyewaan p
    JOIN pelanggan pl ON p.id_pelanggan = pl.id_pelanggan
    JOIN users u ON p.id_user = u.id_user
    LEFT JOIN detail_penyewaan d ON p.no_transaksi = d.no_transaksi
    GROUP BY p.no_transaksi, p.tanggal_sewa, p.tanggal_kembali, pl.nama_pelanggan, u.nama_user, p.status_penyewaan
    ORDER BY p.tanggal_sewa DESC
");
$penyewaan = [];
if ($resPenyewaan) {
    while ($r = $resPenyewaan->fetch_assoc()) { $penyewaan[] = $r; }
}

$resPembayaran = $conn->query("
    SELECT
        b.no_pembayaran,
        b.no_transaksi,
        b.tanggal_pembayaran,
        pl.nama_pelanggan,
        b.total_biaya,
        b.jumlah_pembayaran,
        b.metode_pembayaran,
        b.status_pembayaran
    FROM pembayaran b
    JOIN penyewaan p ON b.no_transaksi = p.no_transaksi
    JOIN pelanggan pl ON p.id_pelanggan = pl.id_pelanggan
    ORDER BY b.tanggal_pembayaran DESC
");
$pembayaran = [];
if ($resPembayaran) {
    while ($r = $resPembayaran->fetch_assoc()) { $pembayaran[] = $r; }
}

$resTotal = $conn->query("
    SELECT COALESCE(SUM(jumlah_pembayaran), 0) AS total
    FROM pembayaran
    WHERE status_pembayaran = 'Lunas'
");
$totalPendapatan = 0;
if ($resTotal) {
    $totalPendapatan = (float)$resTotal->fetch_assoc()["total"];
}

echo json_encode([
    "status" => "success",
    "penyewaan" => $penyewaan,
    "pembayaran" => $pembayaran,
    "total_pendapatan" => $totalPendapatan,
    "data" => [
        "penyewaan" => $penyewaan,
        "pembayaran" => $pembayaran,
        "total_pendapatan" => $totalPendapatan
    ]
]);
$conn->close();
exit;
