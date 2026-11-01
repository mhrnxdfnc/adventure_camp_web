<?php

header('Content-Type: application/json; charset=UTF-8');

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function responseJson($status, $message, $extra = [])
{
    echo json_encode(
        array_merge([
            'status' => $status,
            'message' => $message,
        ], $extra),
        JSON_UNESCAPED_UNICODE
    );
    exit;
}

try {
    require_once __DIR__.'/koneksi.php';

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        responseJson('error', 'Gunakan metode POST.');
    }

    $data = json_decode(file_get_contents('php://input'), true);

    if (! is_array($data)) {
        $data = $_POST;
    }

    $nama = trim((string) ($data['nama_pelanggan'] ?? ''));
    $alamat = trim((string) ($data['alamat'] ?? ''));
    $telepon = trim((string) ($data['nomor_telepon'] ?? ''));
    $username = trim((string) ($data['username'] ?? ''));
    $password = (string) ($data['password'] ?? '');

    if (
        $nama === '' ||
        $alamat === '' ||
        $telepon === '' ||
        $username === '' ||
        $password === ''
    ) {
        http_response_code(400);
        responseJson(
            'error',
            'Nama, alamat, nomor telepon, username, dan password wajib diisi.'
        );
    }

    if (strlen($password) < 8) {
        http_response_code(400);
        responseJson(
            'error',
            'Password harus terdiri dari minimal 8 karakter.'
        );
    }

    if (strlen($username) < 4) {
        http_response_code(400);
        responseJson(
            'error',
            'Username harus terdiri dari minimal 4 karakter.'
        );
    }

    $conn->begin_transaction();

    // Periksa username agar tidak digunakan dua akun.
    $stmt = $conn->prepare(
        'SELECT id_user FROM users WHERE username = ? LIMIT 1'
    );
    $stmt->bind_param('s', $username);
    $stmt->execute();

    if ($stmt->get_result()->num_rows > 0) {
        $stmt->close();
        $conn->rollback();

        http_response_code(409);
        responseJson('error', 'Username sudah digunakan.');
    }
    $stmt->close();

    // Periksa nomor telepon pelanggan.
    $stmt = $conn->prepare(
        'SELECT id_pelanggan
         FROM pelanggan
         WHERE nomor_telepon = ?
         LIMIT 1'
    );
    $stmt->bind_param('s', $telepon);
    $stmt->execute();

    if ($stmt->get_result()->num_rows > 0) {
        $stmt->close();
        $conn->rollback();

        http_response_code(409);
        responseJson(
            'error',
            'Nomor telepon sudah terdaftar. Silakan gunakan nomor lain.'
        );
    }
    $stmt->close();

    // Simpan data profil pelanggan.
    $stmt = $conn->prepare(
        'INSERT INTO pelanggan
            (nama_pelanggan, alamat, nomor_telepon)
         VALUES (?, ?, ?)'
    );
    $stmt->bind_param('sss', $nama, $alamat, $telepon);
    $stmt->execute();

    $idPelanggan = (int) $conn->insert_id;
    $stmt->close();

    // Hash password sebelum disimpan ke database.
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    // Akun pendaftaran selalu ber-role Pelanggan.
    // Role Admin/Pemilik tidak dapat dipilih melalui endpoint ini.
    $role = 'Pelanggan';
    $statusAktif = 1;

    $stmt = $conn->prepare(
        'INSERT INTO users
            (id_pelanggan, nama_user, username, password, role, status_aktif)
         VALUES (?, ?, ?, ?, ?, ?)'
    );

    $stmt->bind_param(
        'issssi',
        $idPelanggan,
        $nama,
        $username,
        $passwordHash,
        $role,
        $statusAktif
    );

    $stmt->execute();
    $idUser = (int) $conn->insert_id;
    $stmt->close();

    $conn->commit();

    http_response_code(201);
    responseJson('success', 'Pendaftaran berhasil. Silakan login.', [
        'data' => [
            'id_user' => $idUser,
            'id_pelanggan' => $idPelanggan,
            'nama_pelanggan' => $nama,
            'username' => $username,
            'role' => $role,
        ],
    ]);

} catch (Throwable $e) {
    if (isset($conn) && $conn instanceof mysqli) {
        try {
            $conn->rollback();
        } catch (Throwable $ignored) {
        }
    }

    error_log('api_register_pelanggan.php: '.$e->getMessage());

    http_response_code(500);

    responseJson('error', 'Penyebab: '.$e->getMessage());
}
