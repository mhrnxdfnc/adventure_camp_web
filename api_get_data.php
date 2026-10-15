<?php

include "koneksi.php";

header("Content-Type: application/json; charset=UTF-8");

$jenis = $_GET["jenis"] ?? "";
$data = [];

switch ($jenis) {

    case "pelanggan":

        $sql = "
            SELECT
                id_pelanggan,
                nama_pelanggan,
                alamat,
                nomor_telepon,
                created_at
            FROM pelanggan
            ORDER BY id_pelanggan DESC
        ";

        $result = $conn->query($sql);
        break;


    case "alat":

        $sql = "
            SELECT
                kode_alat,
                nama_alat,
                kategori,
                stok,
                kondisi,
                tarif_sewa,
                created_at
            FROM alat
            ORDER BY kode_alat ASC
        ";

        $result = $conn->query($sql);
        break;


    case "penyewaan":

        $sql = "
            SELECT
                p.no_transaksi,
                p.tanggal_sewa,
                p.tanggal_kembali,
                p.status_penyewaan,

                pl.id_pelanggan,
                pl.nama_pelanggan,
                pl.alamat,
                pl.nomor_telepon,

                u.nama_user,

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

            GROUP BY
                p.no_transaksi,
                p.tanggal_sewa,
                p.tanggal_kembali,
                p.status_penyewaan,
                pl.id_pelanggan,
                pl.nama_pelanggan,
                pl.alamat,
                pl.nomor_telepon,
                u.nama_user

            ORDER BY p.tanggal_sewa DESC
        ";

        $result = $conn->query($sql);
        break;


    case "detail_penyewaan":

        $no = $_GET["no_transaksi"] ?? "";

        $stmt = $conn->prepare("
            SELECT
                d.id_detail,
                d.no_transaksi,
                d.kode_alat,
                a.nama_alat,
                a.kategori,
                d.jumlah,
                d.tarif_sewa
            FROM detail_penyewaan d
            JOIN alat a
                ON d.kode_alat = a.kode_alat
            WHERE d.no_transaksi = ?
        ");

        if (!$stmt) {
            echo json_encode([
                "status" => "error",
                "message" => $conn->error
            ]);
            exit;
        }

        $stmt->bind_param("s", $no);
        $stmt->execute();
        $result = $stmt->get_result();
        break;


    case "pembayaran":

        $sql = "
            SELECT
                b.no_pembayaran,
                b.no_transaksi,
                b.total_biaya,
                b.jumlah_pembayaran,
                b.metode_pembayaran,
                b.tanggal_pembayaran,
                b.status_pembayaran,
                pl.nama_pelanggan

            FROM pembayaran b

            JOIN penyewaan p
                ON b.no_transaksi = p.no_transaksi

            JOIN pelanggan pl
                ON p.id_pelanggan = pl.id_pelanggan

            ORDER BY
                b.tanggal_pembayaran DESC,
                b.no_pembayaran DESC
        ";

        $result = $conn->query($sql);
        break;


    case "pengembalian":

        $sql = "
            SELECT
                pg.id_pengembalian,
                pg.no_transaksi,
                pg.tanggal_pengembalian_aktual,
                pg.kondisi_alat,
                pg.status_pengembalian,
                pl.nama_pelanggan,

                COALESCE(
                    d.nominal_denda,
                    0
                ) AS nominal_denda,

                d.alasan_denda

            FROM pengembalian pg

            JOIN penyewaan p
                ON pg.no_transaksi = p.no_transaksi

            JOIN pelanggan pl
                ON p.id_pelanggan = pl.id_pelanggan

            LEFT JOIN denda d
                ON pg.id_pengembalian = d.id_pengembalian

            ORDER BY
                pg.tanggal_pengembalian_aktual DESC
        ";

        $result = $conn->query($sql);
        break;


    case "users":

        $sql = "
            SELECT
                id_user,
                nama_user,
                username,
                role,
                status_aktif,
                created_at
            FROM users
            ORDER BY id_user ASC
        ";

        $result = $conn->query($sql);
        break;


    case "audit":

        $sql = "
            SELECT
                l.id_log,
                u.nama_user,
                u.role,
                l.aktivitas,
                l.modul,
                l.data_diproses,
                l.waktu
            FROM activity_logs l
            JOIN users u
                ON l.id_user = u.id_user
            ORDER BY l.waktu DESC
        ";

        $result = $conn->query($sql);
        break;


    case "alat_terlaris":

        $sql = "
            SELECT
                a.kode_alat,
                a.nama_alat,
                a.kategori,
                a.tarif_sewa,
                COALESCE(
                    SUM(d.jumlah),
                    0
                ) AS total_disewa

            FROM alat a

            LEFT JOIN detail_penyewaan d
                ON a.kode_alat = d.kode_alat

            GROUP BY
                a.kode_alat,
                a.nama_alat,
                a.kategori,
                a.tarif_sewa

            ORDER BY total_disewa DESC
        ";

        $result = $conn->query($sql);
        break;


    case "penyewaan_pelanggan":

        $idPelanggan = (int)($_GET["id_pelanggan"] ?? 0);

        $stmt = $conn->prepare("
            SELECT
                p.no_transaksi,
                p.tanggal_sewa,
                p.tanggal_kembali,
                p.status_penyewaan,

                pl.id_pelanggan,
                pl.nama_pelanggan,

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

            LEFT JOIN detail_penyewaan d
                ON p.no_transaksi = d.no_transaksi

            WHERE p.id_pelanggan = ?

            GROUP BY
                p.no_transaksi,
                p.tanggal_sewa,
                p.tanggal_kembali,
                p.status_penyewaan,
                pl.id_pelanggan,
                pl.nama_pelanggan

            ORDER BY p.tanggal_sewa DESC
        ");

        if (!$stmt) {
            echo json_encode([
                "status" => "error",
                "message" => $conn->error
            ]);
            exit;
        }

        $stmt->bind_param("i", $idPelanggan);
        $stmt->execute();
        $result = $stmt->get_result();
        break;


    default:

        echo json_encode([
            "status" => "error",
            "message" => "Jenis data tidak valid"
        ]);
        $conn->close();
        exit;
}


if (!$result) {

    echo json_encode([
        "status" => "error",
        "message" => $conn->error
    ]);

    $conn->close();
    exit;
}


while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}


echo json_encode([
    "status" => "success",
    "data" => $data
], JSON_UNESCAPED_UNICODE);


if (isset($stmt)) {
    $stmt->close();
}

$conn->close();

?>
