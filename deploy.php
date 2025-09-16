<?php
// Lokasi folder project di server
$path = "/home/tokq3391/public_html/";
$log_file = "/home/tokq3391/deploy.log";

// Jalankan git pull
exec("cd {$path} && git pull origin master 2>&1", $output);

// Simpan log
file_put_contents($log_file, date('Y-m-d H:i:s') . "\n" . implode("\n", $output) . "\n\n", FILE_APPEND);
echo "Deployment done.";
?>
