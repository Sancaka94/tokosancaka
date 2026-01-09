<?php

// Laravel Audit Tool — Simpan file ini di public/laravel-audit.php



error_reporting(E_ALL);

ini_set('display_errors', 1);



$basePath = realpath(__DIR__);

while (!is_dir($basePath . '/vendor') && dirname($basePath) !== $basePath) {

    $basePath = dirname($basePath);

}



function listDirectory($dir)

{

    $items = scandir($dir);

    $html = "<ul>";

    foreach ($items as $item) {

        if ($item === '.' || $item === '..') continue;

        $path = "$dir/$item";

        if (is_dir($path)) {

            $html .= "<li><span class='folder' onclick='toggle(this)'>📂 $item</span>";

            $html .= "<div class='nested'>" . listDirectory($path) . "</div></li>";

        } else {

            $html .= "<li class='file'>📄 $item</li>";

        }

    }

    $html .= "</ul>";

    return $html;

}



function getAllRouteNames()

{

    $routes = [];

    $output = [];

    exec('php ../artisan route:list --name', $output);

    foreach ($output as $line) {

        if (preg_match('/\|\s+(.*?)\s+\|/', $line, $match)) {

            $routes[] = trim($match[1]);

        }

    }

    return array_unique($routes);

}



function scanBladeRoutes($path)

{

    $results = [];

    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));

    foreach ($files as $file) {

        if (str_ends_with($file, '.blade.php')) {

            $lines = file($file);

            foreach ($lines as $i => $line) {

                if (preg_match_all('/route\(\s*[\'"]([^\'"]+)[\'"]\s*\)/', $line, $matches)) {

                    foreach ($matches[1] as $routeName) {

                        $results[] = [

                            'file' => str_replace(realpath(__DIR__ . '/../'), '', $file),

                            'line' => $i + 1,

                            'route' => $routeName,

                        ];

                    }

                }

            }

        }

    }

    return $results;

}



function getUsedViewsFromCode($path)

{

    $used = [];

    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));

    foreach ($files as $file) {

        if (str_ends_with($file, '.php')) {

            $content = file_get_contents($file);

            if (preg_match_all('/view\s*\(\s*[\'"]([^\'"]+)[\'"]/', $content, $matches)) {

                foreach ($matches[1] as $view) {

                    $used[] = $view;

                }

            }

        }

    }

    return array_unique($used);

}



function findOrphanViews($viewsPath, $usedViews)

{

    $orphans = [];

    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($viewsPath));

    foreach ($files as $file) {

        if (str_ends_with($file, '.blade.php')) {

            $relative = str_replace([realpath($viewsPath) . '/', '.blade.php'], '', $file);

            $viewName = str_replace(DIRECTORY_SEPARATOR, '.', $relative);

            if (!in_array($viewName, $usedViews)) {

                $orphans[] = $viewName;

            }

        }

    }

    return $orphans;

}



// === EXECUTION ===

$routes = getAllRouteNames();

$bladeCalls = scanBladeRoutes($basePath . '/resources/views');

$usedViews = array_merge(

    getUsedViewsFromCode($basePath . '/routes'),

    getUsedViewsFromCode($basePath . '/app')

);

$orphans = findOrphanViews($basePath . '/resources/views', $usedViews);

?>



<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <title>Laravel Audit Tool</title>

    <style>

        body { font-family: Consolas, monospace; background: #f9f9f9; padding: 30px; }

        h1 { font-size: 24px; margin-bottom: 10px; }

        h2 { border-bottom: 1px solid #ccc; padding-bottom: 5px; margin-top: 40px; }

        ul { list-style: none; padding-left: 20px; }

        li { margin: 3px 0; }

        .folder { font-weight: bold; color: #2c3e50; cursor: pointer; }

        .nested { display: none; padding-left: 20px; }

        .file { color: #34495e; }

        table { border-collapse: collapse; width: 100%; margin-top: 15px; }

        th, td { border: 1px solid #ccc; padding: 6px 12px; text-align: left; }

        th { background: #eee; }

        .missing { color: red; font-weight: bold; }

        .valid { color: green; font-weight: bold; }

    </style>

</head>

<body>

    <h1>🛠 Laravel Audit Tool</h1>



    <h2>📁 Folder Explorer: <code>resources/views</code></h2>

    <?= listDirectory($basePath . '/resources/views'); ?>



    <h2>🔍 Blade route() Check</h2>

    <?php

    $total = count($bladeCalls);

    $missingCount = 0;

    ?>



    <?php if ($total === 0): ?>

        <p><i>Tidak ditemukan pemanggilan <code>route(...)</code> di file Blade.</i></p>

    <?php else: ?>

        <table>

            <thead>

                <tr>

                    <th>File</th>

                    <th>Line</th>

                    <th>Route Name</th>

                    <th>Status</th>

                </tr>

            </thead>

            <tbody>

                <?php foreach ($bladeCalls as $call): ?>

                    <?php

                        $isValid = in_array($call['route'], $routes);

                        if (!$isValid) $missingCount++;

                    ?>

                    <tr>

                        <td><?= htmlspecialchars($call['file']) ?></td>

                        <td><?= $call['line'] ?></td>

                        <td><?= $call['route'] ?></td>

                        <td>

                            <?php if ($isValid): ?>

                                <span class="valid">✅ Valid</span>

                            <?php else: ?>

                                <span class="missing">❌ Missing</span>

                            <?php endif; ?>

                        </td>

                    </tr>

                <?php endforeach; ?>

            </tbody>

        </table>



        <?php if ($missingCount === 0): ?>

            <p style="color:green; font-weight:bold; margin-top:10px;">

                ✅ Semua <code>route('...')</code> sudah terdaftar di Laravel.

            </p>

        <?php else: ?>

            <p style="color:red; font-weight:bold; margin-top:10px;">

                ⚠️ Ada <?= $missingCount ?> route yang belum didefinisikan!

            </p>

        <?php endif; ?>

    <?php endif; ?>



    <h2>🗃️ Orphan Views (Tidak dipanggil dari Controller / Route)</h2>

    <?php if (count($orphans) === 0): ?>

        <p><span class="valid">✅ Tidak ada view yang orphan.</span></p>

    <?php else: ?>

        <ul>

            <?php foreach ($orphans as $view): ?>

                <li class="missing">❌ <?= $view ?></li>

            <?php endforeach; ?>

        </ul>

    <?php endif; ?>



    <script>

        function toggle(el) {

            let next = el.nextElementSibling;

            if (next && next.classList.contains('nested')) {

                next.style.display = next.style.display === 'block' ? 'none' : 'block';

            }

        }

    </script>

</body>

</html>

