<?php

header("Content-Type: application/json; charset=UTF-8");

session_start();
include "koneksi.php";

if (!isset($_SESSION["id_user"]) || !isset($_SESSION["role"])) {
    http_response_code(401);

    echo json_encode([
        "status" => "error",
        "message" => "Silakan login terlebih dahulu"
    ]);

    exit;
}

if ($_SESSION["role"] !== "Admin") {
    http_response_code(403);

    echo json_encode([
        "status" => "error",
        "message" => "Hanya Admin yang dapat menyetujui penyewaan"
    ]);

    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

$no_transaksi = trim($data["no_transaksi"] ?? "");

if ($no_transaksi === "") {
    echo json_encode([
        "status" => "error",
        "message" => "Nomor transaksi wajib diisi"
    ]);

    exit;
}

$conn->begin_transaction();

try {
    $stmt = $conn->prepare("
        SELECT status_penyewaan
        FROM penyewaan
        WHERE no_transaksi = ?
        FOR UPDATE
    ");

    $stmt->bind_param("s", $no_transaksi);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception("Transaksi tidak ditemukan");
    }

    $transaksi = $result->fetch_assoc();

    if ($transaksi["status_penyewaan"] !== "Menunggu Konfirmasi") {
        throw new Exception(
            "Transaksi tidak berstatus Menunggu Konfirmasi"
        );
    }

    $stmtUpdate = $conn->prepare("
        UPDATE penyewaan
        SET status_penyewaan = 'Menunggu Pembayaran'
        WHERE no_transaksi = ?
    ");

    $stmtUpdate->bind_param("s", $no_transaksi);
    $stmtUpdate->execute();

    $id_user = (int) $_SESSION["id_user"];
    $aktivitas = "Menyetujui penyewaan";
    $modul = "Penyewaan";

    $stmtLog = $conn->prepare("
        INSERT INTO activity_logs
        (
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

    $conn->commit();

    echo json_encode([
        "status" => "success",
        "message" => "Penyewaan berhasil disetujui",
        "no_transaksi" => $no_transaksi
    ]);

} catch (Throwable $e) {
    $conn->rollback();

    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}

$conn->close();