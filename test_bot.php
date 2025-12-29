<?php
// test_bot.php
// File ini untuk mengecek apakah request Telegram sampai ke server

$data = file_get_contents("php://input");
$update = json_decode($data, true);

// Simpan data mentah ke file teks di folder yang sama
file_put_contents("debug_telegram.txt", print_r($update, true));

echo "OK";
?>