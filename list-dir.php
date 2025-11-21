<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Laravel Folder Explorer</title>
    <style>
        body {
            font-family: Consolas, monospace;
            background-color: #f4f4f4;
            padding: 30px;
            color: #2c3e50;
        }
        ul {
            list-style-type: none;
            margin: 0;
            padding-left: 20px;
        }
        li {
            margin: 2px 0;
        }
        .folder {
            font-weight: bold;
            color: #1a5276;
        }
        .file {
            color: #34495e;
        }
    </style>
</head>
<body>
    <h2>📁 Laravel Project Directory</h2>
    <p><i>Menampilkan struktur folder dari direktori: <code><?= getcwd(); ?></code></i></p>

<?php
function listDirRecursive($dir) {
    if (!is_dir($dir)) return;

    echo "<ul>";
    $items = scandir($dir);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        $path = $dir . DIRECTORY_SEPARATOR . $item;

        if (is_dir($path)) {
            echo "<li class='folder'>📂 " . htmlspecialchars($item) . "</li>";
            listDirRecursive($path);
        } else {
            echo "<li class='file'>📄 " . htmlspecialchars($item) . "</li>";
        }
    }
    echo "</ul>";
}

listDirRecursive('.');
?>
</body>
</html>
