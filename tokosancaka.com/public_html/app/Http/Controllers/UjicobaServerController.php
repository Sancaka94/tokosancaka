<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UjicobaServerController extends Controller
{
    public function index()
    {
        $html = <<<HTML
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Uji Coba Server TokoSancaka</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background: #f4f7f6; color: #333; display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 20px; }
        .card { background: #ffffff; max-width: 600px; width: 100%; padding: 40px; border-radius: 12px; box-shadow: 0 8px 24px rgba(0,0,0,0.08); text-align: center; }
        .badge { display: inline-block; background: #e8f5e9; color: #2e7d32; font-size: 14px; font-weight: 600; padding: 6px 16px; border-radius: 20px; margin-bottom: 20px; }
        h1 { font-size: 28px; margin-bottom: 12px; color: #1a1a1a; }
        p { font-size: 16px; color: #666; line-height: 1.6; margin-bottom: 24px; }
        .btn { display: inline-block; background: #0066ff; color: #fff; text-decoration: none; padding: 12px 28px; border-radius: 8px; font-weight: 600; transition: background 0.2s ease; }
        .btn:hover { background: #0052cc; }
        .footer { margin-top: 30px; font-size: 13px; color: #999; }
    </style>
</head>
<body>
    <div class="card">
        <span class="badge">Server Online & Active</span>
        <h1>Uji Coba Server TokoSancaka</h1>
        <p>Halaman ini dibuat dan dideploy secara otomatis oleh Ai Agent Sancaka untuk menguji alur deployment otomatis (Git Auto Commit & Push).</p>
        <a href="/" class="btn">Kembali ke Beranda</a>
        <div class="footer">TokoSancaka &copy; 2026 - Ai Agent Sancaka</div>
    </div>
</body>
</html>
HTML;
        return response($html)->header('Content-Type', 'text/html');
    }
}
