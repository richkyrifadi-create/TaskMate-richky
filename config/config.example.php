<?php
// Contoh pengaturan database untuk hosting.
// Salin file ini jadi config.local.php (di folder yang sama), lalu isi
// sesuai data "MySQL Details" di panel hosting kamu.
return [
    'host' => 'sqlXXX.infinityfree.com', // MySQL Hostname
    'port' => 3306,                      // MySQL Port
    'name' => 'if0_XXXXXXXX_taskmate',   // MySQL Database Name
    'user' => 'if0_XXXXXXXX',            // MySQL Username
    'pass' => 'password-akun-hosting',   // MySQL Password

    // Opsional: kirim email reset password lewat Gmail.
    // smtp_pass BUKAN password Gmail biasa, tapi "App Password" 16 huruf
    // (myaccount.google.com/apppasswords, butuh verifikasi 2 langkah aktif).
    // Kosongkan atau hapus baris-baris ini kalau tidak dipakai.
    'smtp_host' => 'smtp.gmail.com',
    'smtp_port' => 465,
    'smtp_user' => 'emailkamu@gmail.com',
    'smtp_pass' => 'xxxx xxxx xxxx xxxx',
];
