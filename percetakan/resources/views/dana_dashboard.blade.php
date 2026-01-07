<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DANA Integration Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .card { margin-bottom: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .header { background: #108ee9; color: white; padding: 20px; margin-bottom: 30px; text-align: center; }
    </style>
</head>
<body class="bg-light">

<div class="header">
    <h1>DANA API Dashboard</h1>
    <p>Pusat Kontrol Integrasi: Binding, Saldo, & Topup</p>
</div>

<div class="container">

    {{-- Notifikasi --}}
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif



    <div class="row">
        {{-- KOLOM 1: ACCOUNT BINDING --}}
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-primary text-white">1. Sambungkan Akun (Binding)</div>
                <div class="card-body">
                    <p>Langkah pertama: Sambungkan akun DANA User untuk mendapatkan Token.</p>
                    
                    @if(session('dana_auth_code'))
                        <div class="alert alert-info">
                            <strong>Auth Code Didapat:</strong><br>
                            <small>{{ Str::limit(session('dana_auth_code'), 20) }}...</small>
                        </div>
                    @else
                        <div class="alert alert-warning">Belum terhubung.</div>
                    @endif

                    <form action="{{ route('dana.do_bind') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-primary w-100">🔗 Sambungkan Akun DANA</button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Notifikasi Error/Sukses Biasa --}}
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    {{-- [BARU] LAYAR MONITOR SALDO --}}
    @if(session('saldo_terbaru') !== null)
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card bg-success text-white text-center">
                <div class="card-body">
                    <h3 class="card-title">Saldo DANA User Saat Ini</h3>
                    <h1 class="display-4 fw-bold">
                        Rp {{ number_format((float) session('saldo_terbaru'), 0, ',', '.') }}
                    </h1>
                    <p>Update Terakhir: {{ now()->format('H:i:s') }}</p>
                </div>
            </div>
        </div>
    </div>
    @endif

        {{-- KOLOM 2: CEK SALDO --}}
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-success text-white">2. Cek Saldo User</div>
                <div class="card-body">
                    <p>Cek saldo user yang sudah terhubung.</p>
                    <form action="{{ route('dana.check_balance') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label>Access Token</label>
                            <input type="text" name="access_token" class="form-control" placeholder="Masukkan Token..." required 
                                   value="{{ session('dana_access_token') }}">
                            <small class="text-muted">*Butuh Access Token (Tukar Auth Code)</small>
                        </div>
                        <button type="submit" class="btn btn-success w-100">💰 Cek Saldo</button>
                    </form>
                </div>
            </div>
        </div>

        {{-- KOLOM 3: TRANSFER / TOPUP --}}
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-danger text-white">3. Kirim Uang (Topup)</div>
                <div class="card-body">
                    <p>Kirim saldo dari Merchant ke User.</p>
                    <form action="{{ route('dana.topup') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label>Nomor HP Tujuan</label>
                            <input type="text" name="phone" class="form-control" value="085745808809" placeholder="08..." required>
                        </div>
                        <div class="mb-3">
                            <label>Nominal (Rp)</label>
                            <input type="number" name="amount" class="form-control" value="1000" required>
                        </div>
                        <button type="submit" class="btn btn-danger w-100">💸 Kirim Uang</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>