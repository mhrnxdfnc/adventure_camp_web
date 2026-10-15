<?php
session_start();
require_once __DIR__ . '/koneksi.php';

header('Content-Type: application/json; charset=UTF-8');

if (
    empty($_SESSION['id_user']) ||
    ($_SESSION['role'] ?? '') !== 'Pelanggan'
) {
    http_response_code(401);
    echo json_encode([
        'status' => 'error',
        'message' => 'Silakan login sebagai pelanggan terlebih dahulu.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $sql = "
        SELECT
            kode_alat,
            nama_alat,
            kategori,
            stok,
            kondisi,
            tarif_sewa
        FROM alat
        WHERE stok > 0
          AND kondisi = 'Baik'
        ORDER BY kategori ASC, nama_alat ASC
    ";

    $result = $conn->query($sql);
    $alat = [];

    while ($row = $result->fetch_assoc()) {
        $alat[] = [
            'kode_alat' => $row['kode_alat'],
            'nama_alat' => $row['nama_alat'],
            'kategori' => $row['kategori'],
            'stok' => (int) $row['stok'],
            'kondisi' => $row['kondisi'],
            'tarif_sewa' => (float) $row['tarif_sewa']
        ];
    }

    echo json_encode([
        'status' => 'success',
        'data' => $alat
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(500);
    error_log('api_katalog_pelanggan.php: ' . $e->getMessage());

    echo json_encode([
        'status' => 'error',
        'message' => 'Gagal mengambil katalog alat.'
    ], JSON_UNESCAPED_UNICODE);
}

$conn->close();
