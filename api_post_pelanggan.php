<?php

include "koneksi.php";

$data = json_decode(file_get_contents("php://input"), true);

$nama = trim($data["nama_pelanggan"] ?? "");
$alamat = trim($data["alamat"] ?? "");
$telepon = trim($data["nomor_telepon"] ?? "");

if ($nama === "" || $alamat === "" || $telepon === "") {

    echo json_encode([
        "status" => "error",
        "message" => "Semua data pelanggan wajib diisi"
    ]);

    exit;
}

$stmtCek = $conn->prepare("
    SELECT id_pelanggan
    FROM pelanggan
    WHERE nomor_telepon = ?
    LIMIT 1
");

$stmtCek->bind_param("s", $telepon);
$stmtCek->execute();

$result = $stmtCek->get_result();

if ($result->num_rows > 0) {

    echo json_encode([
        "status" => "error",
        "message" => "Nomor telepon sudah terdaftar"
    ]);

    exit;
}

$stmt = $conn->prepare("
    INSERT INTO pelanggan
    (
        nama_pelanggan,
        alamat,
        nomor_telepon
    )
    VALUES (?, ?, ?)
");

$stmt->bind_param(
    "sss",
    $nama,
    $alamat,
    $telepon
);

if ($stmt->execute()) {

    echo json_encode([
        "status" => "success",
        "message" => "Pelanggan berhasil ditambahkan",
        "id_pelanggan" => $conn->insert_id
    ]);

} else {

    echo json_encode([
        "status" => "error",
        "message" => "Gagal menambahkan pelanggan"
    ]);
}

$stmt->close();
$conn->close();