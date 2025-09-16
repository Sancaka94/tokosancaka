<?php
// Path project di server
$path = "/home/tokq3391/public_html/";
$log_file = "/home/tokq3391/deploy.log";

// Perintah deployment
$commands = [
    "cd {$path}",
    // Tarik update terbaru dari GitHub
    "git pull origin master",
    // Tambahkan semua perubahan lokal
    "git add .",
    // Commit otomatis kalau ada perubahan
    "git commit -m 'Auto commit dari server' || echo 'No changes to commit'",
    // Push ke GitHub
    "git push origin master",
    // Clear cache Laravel
    "php artisan config:clear",
    "php artisan cache:clear",
    "php artisan route:clear",
    "php artisan view:clear",
    "php artisan config:cache"
];

// Eksekusi semua perintah
$output = [];
foreach ($commands as $command) {
    $result = [];
    exec($command . " 2>&1", $result);
    $output[] = "> $command\n" . implode("\n", $result);
}

// Simpan log
file_put_contents(
    $log_file,
    "=== Git Deploy at " . date('Y-m-d H:i:s') . " ===\n" .
    implode("\n\n", $output) .
    "\n\n",
    FILE_APPEND
);

// Output ke browser
echo "✅ Deployment selesai. Cek log di {$log_file}";
?>
