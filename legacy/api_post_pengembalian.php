<?php

include 'koneksi.php';

$data = json_decode(file_get_contents('php://input'), true);

$no_transaksi =
    trim($data['no_transaksi'] ?? '');

$kondisi =
    trim($data['kondisi_alat'] ?? '');

$statusPengembalian =
    $data['status_pengembalian'] ?? 'Selesai';

$nominalDenda =
    (float) ($data['nominal_denda'] ?? 0);

$alasanDenda =
    trim($data['alasan_denda'] ?? '');

$idUser =
    (int) ($data['id_user'] ?? 0);

if (
    $no_transaksi === '' ||
    $kondisi === '' ||
    $idUser <= 0
) {

    echo json_encode([
        'status' => 'error',
        'message' => 'Data pengembalian belum lengkap',
    ]);

    exit;
}

$conn->begin_transaction();

try {

    /*
      Pastikan transaksi ada
    */

    $stmtCek = $conn->prepare('
        SELECT status_penyewaan
        FROM penyewaan
        WHERE no_transaksi = ?
        FOR UPDATE
    ');

    $stmtCek->bind_param(
        's',
        $no_transaksi
    );

    $stmtCek->execute();

    $resultCek =
        $stmtCek->get_result();

    if ($resultCek->num_rows === 0) {
        throw new Exception(
            'Transaksi tidak ditemukan'
        );
    }

    $transaksi =
        $resultCek->fetch_assoc();

    if ($transaksi['status_penyewaan'] !== 'Berjalan') {

        throw new Exception(
            'Transaksi sudah tidak berstatus Berjalan'
        );
    }

    /*
      Ambil detail alat
    */

    $stmtDetail = $conn->prepare('
        SELECT
            kode_alat,
            jumlah
        FROM detail_penyewaan
        WHERE no_transaksi = ?
    ');

    $stmtDetail->bind_param(
        's',
        $no_transaksi
    );

    $stmtDetail->execute();

    $resultDetail =
        $stmtDetail->get_result();

    /*
      Kembalikan stok
    */

    while ($item = $resultDetail->fetch_assoc()) {

        $stmtStok = $conn->prepare('
            UPDATE alat
            SET stok = stok + ?
            WHERE kode_alat = ?
        ');

        $stmtStok->bind_param(
            'is',
            $item['jumlah'],
            $item['kode_alat']
        );

        $stmtStok->execute();
    }

    /*
      Update transaksi
    */

    $stmtUpdate = $conn->prepare("
        UPDATE penyewaan
        SET status_penyewaan = 'Selesai'
        WHERE no_transaksi = ?
    ");

    $stmtUpdate->bind_param(
        's',
        $no_transaksi
    );

    $stmtUpdate->execute();

    /*
      Insert pengembalian
    */

    $tanggal = date('Y-m-d');

    $stmtReturn = $conn->prepare('
        INSERT INTO pengembalian
        (
            no_transaksi,
            tanggal_pengembalian_aktual,
            kondisi_alat,
            status_pengembalian
        )
        VALUES (?, ?, ?, ?)
    ');

    $stmtReturn->bind_param(
        'ssss',
        $no_transaksi,
        $tanggal,
        $kondisi,
        $statusPengembalian
    );

    $stmtReturn->execute();

    $idPengembalian =
        $conn->insert_id;

    /*
      Denda
    */

    if ($nominalDenda > 0) {

        $tarifDenda =
            $nominalDenda;

        $stmtDenda = $conn->prepare('
            INSERT INTO denda
            (
                id_pengembalian,
                tarif_denda,
                nominal_denda,
                alasan_denda
            )
            VALUES (?, ?, ?, ?)
        ');

        $stmtDenda->bind_param(
            'idds',
            $idPengembalian,
            $tarifDenda,
            $nominalDenda,
            $alasanDenda
        );

        $stmtDenda->execute();
    }

    /*
      Audit
    */

    $aktivitas =
        'Memproses pengembalian';

    $modul =
        'Pengembalian';

    $stmtLog = $conn->prepare('
        INSERT INTO activity_logs
        (
            id_user,
            aktivitas,
            modul,
            data_diproses
        )
        VALUES (?, ?, ?, ?)
    ');

    $stmtLog->bind_param(
        'isss',
        $idUser,
        $aktivitas,
        $modul,
        $no_transaksi
    );

    $stmtLog->execute();

    $conn->commit();

    echo json_encode([
        'status' => 'success',
        'message' => 'Pengembalian berhasil diproses',
    ]);

} catch (Exception $e) {

    $conn->rollback();

    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
    ]);
}

$conn->close();
