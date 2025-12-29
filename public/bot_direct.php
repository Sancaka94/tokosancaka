<?php
// bot_direct.php - Bypass Laravel Routing & CSRF
// Taruh file ini di folder: public/bot_direct.php

$token = "8584565797:AAH3AI6Wtif5__H1NQzJ_3jp6Re7F-rqCYA"; // Token Anda
$apiUrl = "https://api.telegram.org/bot{$token}/";

// 1. Ambil data dari Telegram
$content = file_get_contents("php://input");
$update = json_decode($content, true);

if(!$update) {
    echo "Hanya untuk Telegram Webhook.";
    exit;
}

// 2. Baca Pesan
$chatId = $update["message"]["chat"]["id"];
$text = $update["message"]["text"];

// 3. Kirim Balasan (Tanpa Guzzle/Laravel, pakai PHP Native)
$reply = "Halo! Bot berhasil merespons via Direct File.\nPesan Anda: " . $text;

$data = [
    'chat_id' => $chatId,
    'text' => $reply
];

// Kirim request ke Telegram
$ch = curl_init($apiUrl . "sendMessage");
curl_setopt($ch, CURLOPT_HEADER, false);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, ($data));
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Matikan cek SSL sementara biar tidak ribet
$result = curl_exec($ch);
curl_close($ch);

// Catat hasil ke file log sederhana (biar tahu sukses/gagal)
file_put_contents("log_direct.txt", "Pesan: $text | Hasil Kirim: $result\n", FILE_APPEND);
?>