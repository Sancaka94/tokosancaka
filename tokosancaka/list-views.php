<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Laravel Views Structure</title>
    <style>
        body {
            font-family: Consolas, monospace;
            background: #f9f9f9;
            padding: 30px;
            color: #333;
        }
        ul {
            list-style: none;
            padding-left: 20px;
        }
        li {
            margin: 2px 0;
        }
        .folder {
            font-weight: bold;
            color: #2c3e50;
        }
        .file {
            color: #34495e;
        }
    </style>
</head>
<body>
    <h2>📁 Laravel Blade Views Structure</h2>
    <p><i>Berikut struktur semua file di <code>resources/views</code></i></p>
    <?php
    function renderStructure($dir) {
        echo "<ul>";
        foreach (scandir($dir) as $item) {
            if ($item === '.' || $item === '..') continue;
            $path = $dir . '/' . $item;
            if (is_dir($path)) {
                echo "<li class='folder'>📂 {$item}</li>";
                renderStructure($path);
            } else {
                echo "<li class='file'>📄 {$item}</li>";
            }
        }
        echo "</ul>";
    }

    renderStructure('resources/views');
    ?>
</body>
</html>
