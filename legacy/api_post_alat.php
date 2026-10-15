<?php

include "koneksi.php";

$data = json_decode(file_get_contents("php://input"), true);

$kode_alat = strtoupper(trim($data["kode_alat"] ?? ""));
$nama_alat = trim($data["nama_alat"] ?? "");
$kategori = trim($data["kategori"] ?? "");
$stok = (int)($data["stok"] ?? 0);
$kondisi = $data["kondisi"] ?? "Baik";
$tarif_sewa = (float)($data["tarif_sewa"] ?? 0);
$id_user = (int)($data["id_user"] ?? 1);

if ($kode_alat === "" || $nama_alat === "" || $tarif_sewa <= 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Kode alat, nama alat, dan tarif sewa wajib diisi"
    ]);
    exit;
}

if (!in_array($kondisi, ["Baik", "Perlu Perbaikan", "Rusak"])) {
    $kondisi = "Baik";
}

$conn->begin_transaction();

try {
    // Check if exists (upsert / insert)
    $stmtCek = $conn->prepare("SELECT kode_alat FROM alat WHERE kode_alat = ?");
    $stmtCek->bind_param("s", $kode_alat);
    $stmtCek->execute();
    $result = $stmtCek->get_result();

    if ($result->num_rows > 0) {
        // Update
        $stmtUpdate = $conn->prepare("
            UPDATE alat
            SET nama_alat = ?, kategori = ?, stok = ?, kondisi = ?, tarif_sewa = ?
            WHERE kode_alat = ?
        ");
        $stmtUpdate->bind_param("ssisds", $nama_alat, $kategori, $stok, $kondisi, $tarif_sewa, $kode_alat);
        $stmtUpdate->execute();
        $pesan = "Data alat berhasil diperbarui";
    } else {
        // Insert
        $stmtInsert = $conn->prepare("
            INSERT INTO alat (kode_alat, nama_alat, kategori, stok, kondisi, tarif_sewa)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmtInsert->bind_param("sssisd", $kode_alat, $nama_alat, $kategori, $stok, $kondisi, $tarif_sewa);
        $stmtInsert->execute();
        $pesan = "Alat baru berhasil ditambahkan";
    }

    // Log activity
    $aktivitas = "Kelola alat: $kode_alat";
    $modul = "Alat";
    $stmtLog = $conn->prepare("
        INSERT INTO activity_logs (id_user, aktivitas, modul, data_diproses)
        VALUES (?, ?, ?, ?)
    ");
    $stmtLog->bind_param("isss", $id_user, $aktivitas, $modul, $nama_alat);
    $stmtLog->execute();

    $conn->commit();

    echo json_encode([
        "status" => "success",
        "message" => $pesan,
        "kode_alat" => $kode_alat
    ]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}

$conn->close();