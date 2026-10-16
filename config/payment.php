<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Konfigurasi Rekening Pembayaran Bank Resmi
    |--------------------------------------------------------------------------
    | Data rekening resmi Adventure Camp Rental.
    | Jika salah satu nilai null/kosong, antarmuka akan menampilkan petunjuk
    | agar pelanggan meminta nomor rekening resmi kepada kasir atau Admin.
    */
    'bank' => [
        'name' => env('PAYMENT_BANK_NAME', 'Bank BCA'),
        'account_number' => env('PAYMENT_BANK_ACCOUNT', '8830592811'),
        'account_holder' => env('PAYMENT_BANK_HOLDER', 'Adventure Camp Rental'),
    ],
];
