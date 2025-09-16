<?php
// Lokasi project di server (public_html untuk cPanel)
$path = "/home/tokq3391/public_html";
$log_file = "/home/tokq3391/deploy.log";

// Jalankan git pull
exec("cd {$path} && git pull origin master 2>&1", $output);

// Jalankan artisan clear cache
exec("cd {$path} && php artisan config:clear 2>&1", $output2);
exec("cd {$path} && php artisan cache:clear 2>&1", $output3);
exec("cd {$path} && php artisan view:clear 2>&1", $output4);
exec("cd {$path} && php artisan route:clear 2>&1", $output5);

// Gabungkan semua log
$all_output = array_merge($output, $output2, $output3, $output4, $output5);

// Simpan ke file log
file_put_contents($log_file, date('Y-m-d H:i:s') . "\n" . implode("\n", $all_output) . "\n\n", FILE_APPEND);

echo "✅ Deployment selesai. Silahkan Cek log di {$log_file}";
