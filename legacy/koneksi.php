<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$host = getenv('DB_HOST') ?: '127.0.0.1';
$user = getenv('DB_USERNAME') ?: 'root';
$password = getenv('DB_PASSWORD') !== false ? getenv('DB_PASSWORD') : 'root';
$database = getenv('DB_DATABASE') ?: 'adventure_camp_rental';
$port = (int) (getenv('DB_PORT') ?: 3306);

// Coba koneksi dengan password yang ditentukan, fallback ke string kosong jika gagal
$conn = @new mysqli($host, $user, $password, $database, $port);
if ($conn->connect_error && $password !== '') {
    $conn = @new mysqli($host, $user, '', $database, $port);
}

if ($conn->connect_error) {
    http_response_code(500);

    echo json_encode([
        'status' => 'error',
        'message' => 'Gagal terhubung ke database. Pastikan service MySQL Laragon aktif pada port 3306.',
        'code' => 'ERR_DB_CONN',
    ]);

    exit;
}

$conn->set_charset('utf8mb4');
