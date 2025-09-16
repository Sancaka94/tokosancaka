<?php
// Lokasi folder project di server
$path = "/home/username/public_html/tokosancaka";
$log_file = "/home/username/deploy.log";

// Jalankan git pull
exec("cd {$path} && git pull origin main 2>&1", $output);

// Simpan log
file_put_contents($log_file, date('Y-m-d H:i:s') . "\n" . implode("\n", $output) . "\n\n", FILE_APPEND);
echo "Deployment done.";
?>
