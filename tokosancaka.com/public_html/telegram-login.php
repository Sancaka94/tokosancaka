<?php
require __DIR__ . '/vendor/autoload.php';

$settings = new \danog\MadelineProto\Settings\AppInfo();
$settings->setApiId(34302401);
$settings->setApiHash('c7eec7fb276ef7a4d1da69a8dab2a50d');

// Memastikan path session sama persis dengan yang ada di Controller Laravel Anda
$sessionPath = __DIR__ . '/storage/app/sancaka_telegram.session';

$client = new \danog\MadelineProto\API($sessionPath, $settings);

echo "Menghubungkan ke Telegram...\n";
$client->start();
echo "Login Berhasil Tersimpan!\n";
