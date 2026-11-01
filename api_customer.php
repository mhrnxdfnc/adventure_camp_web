<?php

session_start();
require_once __DIR__.'/koneksi.php';

header('Content-Type: application/json; charset=UTF-8');

function customerResponse($status, $message, $data = [], $httpCode = 200)
{
    http_response_code($httpCode);

    echo json_encode([
        'status' => $status,
        'message' => $message,
        'data' => $data,
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// ========================================
// 1. VALIDASI LOGIN PELANGGAN
// ========================================

if (
    empty($_SESSION['id_user']) ||
    ($_SESSION['role'] ?? '') !== 'Pelanggan'
) {
    customerResponse(
        'error',
        'Silakan login sebagai pelanggan.',
        [],
        401
    );
}

$idPelanggan = (int) ($_SESSION['id_pelanggan'] ?? 0);

if ($idPelanggan <= 0) {
    customerResponse(
        'error',
        'Akun belum terhubung dengan data pelanggan.',
        [],
        400
    );
}

try {

    // ========================================
    // 2. AMBIL DATA PELANGGAN
    // ========================================

    $st = $conn->prepare('
        SELECT id_pelanggan, nama_pelanggan
        FROM pelanggan
        WHERE id_pelanggan = ?
        LIMIT 1
    ');

    $st->bind_param('i', $idPelanggan);
    $st->execute();

    $customer = $st->get_result()->fetch_assoc();
    $st->close();

    if (! $customer) {
        throw new Exception('Data pelanggan tidak ditemukan.');
    }

    // ========================================
    // 3. AMBIL DATA TRANSAKSI DAN TAGIHAN
    // ========================================

    // Total tagihan dihitung hanya dari detail penyewaan.
    // Pembayaran tidak di-JOIN langsung ke detail agar SUM
    // tidak berlipat ketika terdapat banyak pembayaran.

    $sql = "
        SELECT
            p.no_transaksi,
            p.tanggal_sewa,
            p.tanggal_kembali,
            p.status_penyewaan,

            COALESCE(
                (
                    SELECT SUM(
                        d.jumlah *
                        d.tarif_sewa *
                        GREATEST(
                            DATEDIFF(
                                p.tanggal_kembali,
                                p.tanggal_sewa
                            ),
                            1
                        )
                    )
                    FROM detail_penyewaan d
                    WHERE d.no_transaksi = p.no_transaksi
                ),
                0
            ) AS total_biaya,

            COALESCE(
                (
                    SELECT SUM(b.jumlah_pembayaran)
                    FROM pembayaran b
                    WHERE b.no_transaksi = p.no_transaksi
                ),
                0
            ) AS total_pembayaran_tercatat,

            COALESCE(
                (
                    SELECT SUM(
                        CASE
                            WHEN LOWER(b.status_pembayaran)
                                IN (
                                    'terverifikasi',
                                    'diverifikasi',
                                    'lunas',
                                    'berhasil'
                                )
                            THEN b.jumlah_pembayaran
                            ELSE 0
                        END
                    )
                    FROM pembayaran b
                    WHERE b.no_transaksi = p.no_transaksi
                ),
                0
            ) AS total_terbayar,

            COALESCE(
                (
                    SELECT MAX(b.status_pembayaran)
                    FROM pembayaran b
                    WHERE b.no_transaksi = p.no_transaksi
                ),
                'Belum ada pembayaran'
            ) AS status_pembayaran,

            (
                SELECT b.no_pembayaran
                FROM pembayaran b
                WHERE b.no_transaksi = p.no_transaksi
                ORDER BY b.no_pembayaran DESC
                LIMIT 1
            ) AS no_pembayaran,

            COALESCE(
                (
                    SELECT COUNT(*)
                    FROM pembayaran b
                    WHERE b.no_transaksi = p.no_transaksi
                      AND LOWER(b.status_pembayaran)
                          = 'menunggu verifikasi'
                ),
                0
            ) AS pembayaran_menunggu,

            COALESCE(
                (
                    SELECT COUNT(*)
                    FROM pembayaran b
                    WHERE b.no_transaksi = p.no_transaksi
                      AND LOWER(b.status_pembayaran)
                          IN ('ditolak', 'gagal')
                ),
                0
            ) AS pembayaran_ditolak

        FROM penyewaan p
        WHERE p.id_pelanggan = ?
        ORDER BY p.tanggal_sewa DESC
    ";

    $st = $conn->prepare($sql);
    $st->bind_param('i', $idPelanggan);
    $st->execute();

    $result = $st->get_result();
    $orders = [];

    // ========================================
    // 4. OLAH DATA SETIAP TRANSAKSI
    // ========================================

    while ($o = $result->fetch_assoc()) {

        $o['total_biaya'] =
            (float) $o['total_biaya'];

        $o['total_pembayaran_tercatat'] =
            (float) $o['total_pembayaran_tercatat'];

        $o['total_terbayar'] =
            (float) $o['total_terbayar'];

        $o['pembayaran_menunggu'] =
            (int) $o['pembayaran_menunggu'];

        $o['pembayaran_ditolak'] =
            (int) $o['pembayaran_ditolak'];

        // Total pembayaran tercatat digunakan untuk
        // menghitung sisa yang belum dialokasikan.
        $o['sisa_tagihan'] = max(
            0,
            $o['total_biaya'] -
            $o['total_pembayaran_tercatat']
        );

        $o['durasi_hari'] = max(
            1,
            (int) (
                (
                    strtotime($o['tanggal_kembali']) -
                    strtotime($o['tanggal_sewa'])
                ) / 86400
            )
        );

        $o['kode_bayar'] =
            'AC-'.$o['no_transaksi'];

        // Tombol bayar tetap tersedia selama masih ada
        // sisa tagihan. Pembayaran sebelumnya yang masih
        // menunggu verifikasi sudah diperhitungkan dalam
        // total_pembayaran_tercatat.
        $o['bisa_bayar'] =
            $o['sisa_tagihan'] > 0 &&
            $o['total_biaya'] > 0 &&
            $o['status_penyewaan'] === 'Menunggu Pembayaran';

        // ========================================
        // 5. AMBIL DETAIL ALAT
        // ========================================

        $sd = $conn->prepare('
            SELECT
                d.kode_alat,
                a.nama_alat,
                d.jumlah,
                d.tarif_sewa
            FROM detail_penyewaan d
            JOIN alat a
                ON a.kode_alat = d.kode_alat
            WHERE d.no_transaksi = ?
        ');

        $sd->bind_param('s', $o['no_transaksi']);
        $sd->execute();

        $ri = $sd->get_result();
        $o['items'] = [];

        while ($i = $ri->fetch_assoc()) {
            $i['jumlah'] = (int) $i['jumlah'];
            $i['tarif_sewa'] = (float) $i['tarif_sewa'];

            $o['items'][] = $i;
        }

        $sd->close();

        $orders[] = $o;
    }

    $st->close();

    // ========================================
    // 6. KIRIM RESPONS KE BROWSER
    // ========================================

    echo json_encode([
        'status' => 'success',
        'data' => [
            'nama' => $customer['nama_pelanggan'],
            'pesanan' => $orders,
        ],
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {

    error_log('api_customer.php: '.$e->getMessage());

    http_response_code(500);

    echo json_encode([
        'status' => 'error',
        'message' => 'Gagal mengambil data pelanggan. Periksa log PHP jika masalah berlanjut.',
    ], JSON_UNESCAPED_UNICODE);
}

$conn->close();
