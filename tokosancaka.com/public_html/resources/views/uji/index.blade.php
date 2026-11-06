<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Halaman Uji — {{ $data['app_name'] }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
            background: #0f172a;
            color: #e2e8f0;
            min-height: 100vh;
            padding: 40px 20px;
        }
        .container { max-width: 900px; margin: 0 auto; }
        .header { text-align: center; margin-bottom: 30px; }
        .header h1 { font-size: 28px; margin-bottom: 8px; color: #38bdf8; }
        .header p { color: #94a3b8; font-size: 14px; }
        .badge-ok { background: #16a34a; color: #fff; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; display: inline-block; margin-top: 10px; }
        .card {
            background: #1e293b;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 16px;
            border: 1px solid #334155;
        }
        .card h2 {
            font-size: 15px;
            font-weight: 600;
            color: #38bdf8;
            margin-bottom: 14px;
            padding-bottom: 10px;
            border-bottom: 1px solid #334155;
        }
        .row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #2d3a4f;
            font-size: 13px;
            gap: 20px;
        }
        .row:last-child { border-bottom: none; }
        .row .label { color: #94a3b8; }
        .row .value { color: #f1f5f9; font-weight: 500; text-align: right; word-break: break-word; }
        .ok { color: #4ade80 !important; }
        .err { color: #f87171 !important; }
        .footer { text-align: center; margin-top: 24px; color: #64748b; font-size: 12px; }
        a.btn-link { color: #38bdf8; text-decoration: none; }
        a.btn-link:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚀 Halaman Uji Coba</h1>
            <p>Aplikasi berjalan normal. Berikut informasi sistem Anda.</p>
            <span class="badge-ok">✓ ONLINE</span>
        </div>

        <div class="card">
            <h2>📱 Aplikasi</h2>
            <div class="row"><span class="label">Nama Aplikasi</span><span class="value">{{ $data['app_name'] }}</span></div>
            <div class="row"><span class="label">Environment</span><span class="value">{{ $data['app_env'] }}</span></div>
            <div class="row"><span class="label">Debug Mode</span><span class="value {{ $data['app_debug'] === 'ON' ? 'err' : 'ok' }}">{{ $data['app_debug'] }}</span></div>
            <div class="row"><span class="label">App URL</span><span class="value">{{ $data['app_url'] }}</span></div>
            <div class="row"><span class="label">Timezone</span><span class="value">{{ $data['timezone'] }}</span></div>
        </div>

        <div class="card">
            <h2>⚙️ Sistem</h2>
            <div class="row"><span class="label">Laravel Version</span><span class="value">{{ $data['laravel'] }}</span></div>
            <div class="row"><span class="label">PHP Version</span><span class="value">{{ $data['php_version'] }}</span></div>
            <div class="row"><span class="label">Server Time</span><span class="value">{{ $data['server_time'] }}</span></div>
            <div class="row"><span class="label">Koneksi Database</span><span class="value {{ $data['db_connect'] === 'OK' ? 'ok' : 'err' }}">{{ $data['db_connect'] }}</span></div>
            <div class="row"><span class="label">Nama Database</span><span class="value">{{ $data['db_name'] }}</span></div>
            <div class="row"><span class="label">Cache Driver</span><span class="value">{{ $data['cache_driver'] }}</span></div>
            <div class="row"><span class="label">Session Driver</span><span class="value">{{ $data['session_driver'] }}</span></div>
            <div class="row"><span class="label">Queue Driver</span><span class="value">{{ $data['queue_driver'] }}</span></div>
        </div>

        <div class="card">
            <h2>🌐 Request Info</h2>
            <div class="row"><span class="label">IP Address</span><span class="value">{{ $data['ip'] }}</span></div>
            <div class="row"><span class="label">User Agent</span><span class="value">{{ Str::limit($data['user_agent'], 80) }}</span></div>
            <div class="row"><span class="label">Health Check JSON</span><span class="value"><a href="{{ url('/uji/health') }}" class="btn-link" target="_blank">/uji/health</a></span></div>
        </div>

        <div class="footer">
            Halaman uji dibuat untuk verifikasi aplikasi · {{ now()->year }}
        </div>
    </div>
</body>
</html>