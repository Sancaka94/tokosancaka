<?php
// Lokasi project
$path = "/home/tokq3391/public_html";
$log_file = "/home/tokq3391/deploy.log";

// Timestamp
$now = date('Y-m-d H:i:s');

// Masuk ke folder project
chdir($path);

// 1. Tambahkan semua perubahan lokal (kalau ada)
exec("git add . 2>&1", $add_output);

// 2. Commit perubahan lokal (jika ada yang berubah)
exec("git diff --cached --quiet || git commit -m 'Auto commit from server on {$now}' 2>&1", $commit_output);

// 3. Tarik update terbaru dari GitHub, kalau ada konflik pilih versi remote
exec("git pull origin master -X theirs 2>&1", $pull_output);

// 4. Dorong perubahan lokal ke GitHub
exec("git push origin master 2>&1", $push_output);

// 5. Clear cache Laravel
exec("php artisan config:clear 2>&1", $output2);
exec("php artisan cache:clear 2>&1", $output3);
exec("php artisan view:clear 2>&1", $output4);
exec("php artisan route:clear 2>&1", $output5);

// Gabungkan log
$all_output = array_merge(
    ["==== {$now} ===="],
    ["--- Git Add ---"], $add_output,
    ["--- Git Commit ---"], $commit_output,
    ["--- Git Pull ---"], $pull_output,
    ["--- Git Push ---"], $push_output,
    ["--- Artisan ---"], $output2, $output3, $output4, $output5,
    [""]
);

// Simpan ke file log
file_put_contents($log_file, implode("\n", $all_output) . "\n", FILE_APPEND);

echo "✅ Deploy selesai. Cek log di {$log_file}";
