<?php

include "koneksi.php";

$data = [];



/* TOTAL PELANGGAN */

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM pelanggan
");

$data["total_pelanggan"] =
    (int)$result->fetch_assoc()["total"];

/* TOTAL STOK */

$result = $conn->query("
    SELECT COALESCE(SUM(stok), 0) AS total
    FROM alat
    WHERE kondisi != 'Rusak'
");

$data["total_alat"] =
    (int)$result->fetch_assoc()["total"];


/* TRANSAKSI AKTIF */

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM penyewaan
    WHERE status_penyewaan = 'Berjalan'
");

$data["transaksi_aktif"] =
    (int)$result->fetch_assoc()["total"];


/* TERLAMBAT */

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM penyewaan
    WHERE status_penyewaan = 'Berjalan'
    AND tanggal_kembali < CURDATE()
");

$data["terlambat"] =
    (int)$result->fetch_assoc()["total"];


/* PENDAPATAN */

$result = $conn->query("
    SELECT COALESCE(
        SUM(jumlah_pembayaran),
        0
    ) AS total
    FROM pembayaran
    WHERE status_pembayaran = 'Lunas'
    AND MONTH(tanggal_pembayaran) = MONTH(CURDATE())
    AND YEAR(tanggal_pembayaran) = YEAR(CURDATE())
");

$data["pendapatan_bulan"] =
    (float)$result->fetch_assoc()["total"];


/* TRANSAKSI TERBARU */

$result = $conn->query("
    SELECT
        p.no_transaksi,
        pl.nama_pelanggan,
        p.tanggal_sewa,
        p.tanggal_kembali,
        p.status_penyewaan
    FROM penyewaan p
    JOIN pelanggan pl
        ON p.id_pelanggan = pl.id_pelanggan
    ORDER BY p.tanggal_sewa DESC
    LIMIT 7
");

$data["terbaru"] = [];

while ($row = $result->fetch_assoc()) {
    $data["terbaru"][] = $row;
}


/* GRAFIK TRANSAKSI BULANAN */

$result = $conn->query("
    SELECT
        DATE_FORMAT(tanggal_sewa, '%Y-%m') AS bulan,
        COUNT(*) AS total
    FROM penyewaan
    GROUP BY DATE_FORMAT(tanggal_sewa, '%Y-%m')
    ORDER BY bulan ASC
    LIMIT 6
");

$data["grafik_transaksi"] = [];

while ($row = $result->fetch_assoc()) {
    $data["grafik_transaksi"][] = $row;
}


/* GRAFIK ALAT TERLARIS */

$result = $conn->query("
    SELECT
        a.nama_alat,
        SUM(d.jumlah) AS total
    FROM detail_penyewaan d
    JOIN alat a
        ON d.kode_alat = a.kode_alat
    JOIN penyewaan p
        ON d.no_transaksi = p.no_transaksi
    GROUP BY a.kode_alat, a.nama_alat
    ORDER BY total DESC
    LIMIT 5
");

$data["alat_terlaris"] = [];

while ($row = $result->fetch_assoc()) {
    $data["alat_terlaris"][] = $row;
}


echo json_encode([
    "status" => "success",
    "data" => $data
]);

$conn->close();