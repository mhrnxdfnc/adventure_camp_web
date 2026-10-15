<?php
header('Content-Type: application/json; charset=UTF-8');
session_start();

ini_set('display_errors', '0');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    require_once __DIR__ . '/koneksi.php';

    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    if (!is_array($data)) $data = [];

    $username = trim((string)($data['username'] ?? ''));
    $password = (string)($data['password'] ?? '');
    $requestedRole = trim((string)($data['role'] ?? ''));

    if ($username === '' || $password === '' || $requestedRole === '') {
        echo json_encode([
            'status' => 'error',
            'message' => 'Username, password, dan role wajib diisi.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $allowedRoles = ['Admin', 'Pemilik', 'Pelanggan'];
    if (!in_array($requestedRole, $allowedRoles, true)) {
        echo json_encode(['status'=>'error','message'=>'Role tidak valid.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Kompatibel dengan database lama yang belum memiliki users.id_pelanggan.
    $hasCustomerColumn = false;
    $colResult = $conn->query("SHOW COLUMNS FROM `users` LIKE 'id_pelanggan'");
    if ($colResult && $colResult->num_rows > 0) {
        $hasCustomerColumn = true;
    }

    $customerSelect = $hasCustomerColumn ? '`id_pelanggan`' : 'NULL AS `id_pelanggan`';
    $sql = "SELECT `id_user`, `nama_user`, `username`, `password`, `role`, `status_aktif`, $customerSelect
            FROM `users` WHERE `username` = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if (!$user) {
        echo json_encode(['status'=>'error','message'=>'Username atau password salah.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Mendukung password lama plaintext serta password yang sudah di-hash.
    $storedPassword = (string)$user['password'];
    $passwordOk = password_get_info($storedPassword)['algo'] !== null
        ? password_verify($password, $storedPassword)
        : hash_equals($storedPassword, $password);

    if (!$passwordOk) {
        echo json_encode(['status'=>'error','message'=>'Username atau password salah.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ((int)$user['status_aktif'] !== 1) {
        echo json_encode(['status'=>'error','message'=>'Akun tidak aktif.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($user['role'] !== $requestedRole) {
        echo json_encode(['status'=>'error','message'=>'Role akun tidak sesuai pilihan login.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($requestedRole === 'Pelanggan' && !$hasCustomerColumn) {
        echo json_encode([
            'status'=>'error',
            'message'=>'Login Admin/Pemilik bisa menggunakan database lama. Untuk akun Pelanggan, jalankan query_role_pelanggan.sql terlebih dahulu.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    unset($user['password']);
    session_regenerate_id(true);
    $_SESSION['id_user'] = (int)$user['id_user'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['id_pelanggan'] = isset($user['id_pelanggan']) ? (int)$user['id_pelanggan'] : 0;

    echo json_encode([
        'status'=>'success',
        'role'=>$user['role'],
        'nama'=>$user['nama_user'],
        'id_user'=>(int)$user['id_user'],
        'id_pelanggan'=>isset($user['id_pelanggan']) ? (int)$user['id_pelanggan'] : null,
        'data'=>$user
    ], JSON_UNESCAPED_UNICODE);

    $stmt->close();
    $conn->close();
} catch (Throwable $e) {
    http_response_code(500);
    error_log('api_login.php: ' . $e->getMessage());
    echo json_encode([
        'status'=>'error',
        'message'=>'Terjadi kesalahan pada server saat login. Periksa konfigurasi koneksi.php dan log error PHP.'
    ], JSON_UNESCAPED_UNICODE);
}
