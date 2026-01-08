<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DANA Integration Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .card { border: none; border-radius: 15px; margin-bottom: 20px; box-shadow: 0 8px 20px rgba(0,0,0,0.05); transition: transform 0.3s; }
        .card:hover { transform: translateY(-5px); }
        .header { background: linear-gradient(135deg, #108ee9 0%, #0676c4 100%); color: white; padding: 40px 20px; margin-bottom: 30px; border-radius: 0 0 50px 50px; text-align: center; }
        .saldo-value { font-size: 3.5rem; letter-spacing: -2px; }
        .status-dot { height: 10px; width: 10px; border-radius: 50%; display: inline-block; margin-right: 5px; }
    </style>
</head>
<body class="bg-light">

<div class="header shadow">
    <h1><i class="bi bi-wallet2"></i> DANA API Dashboard</h1>
    <p class="opacity-75">Monitoring Integrasi Real-time: Binding, Saldo, & Topup</p>
</div>

<div class="container">

    {{-- Notifikasi Global --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- MONITOR SALDO UTAMA --}}
    <div class="row justify-content-center mb-4">
        <div class="col-md-10">
            <div class="card bg-white border-start border-success border-5 shadow-lg">
                <div class="card-body py-4 px-5">
                    <div class="row align-items-center">
                        <div class="col-md-7 text-center text-md-start">
                            <h5 class="text-muted fw-light mb-1">Saldo DANA User Saat Ini</h5>
                            <div class="saldo-value fw-bold text-primary">
                                <span class="fs-2">Rp</span> {{ number_format((float) (session('saldo_terbaru') ?? 0), 0, ',', '.') }}
                            </div>
                            <p class="text-muted small">
                                <i class="bi bi-clock-history"></i> Update Terakhir: {{ session('saldo_terbaru') !== null ? now()->format('H:i:s') : '-' }}
                            </p>
                        </div>
                        <div class="col-md-5 text-center">
                            @if(session('dana_access_token'))
                                <span class="badge bg-light text-success p-2 px-3 rounded-pill border border-success">
                                    <span class="status-dot bg-success"></span> Akun Terhubung
                                </span>
                            @else
                                <span class="badge bg-light text-danger p-2 px-3 rounded-pill border border-danger">
                                    <span class="status-dot bg-danger"></span> Belum Terhubung
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- KOLOM 1: ACCOUNT BINDING --}}
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header bg-white fw-bold py-3"><i class="bi bi-link-45deg text-primary"></i> 1. Binding Akun</div>
                <div class="card-body">
                    <p class="small text-muted text-center mb-4">Wajib dilakukan untuk mendapatkan izin akses ke saldo user.</p>
                    
                    @if(session('dana_auth_code'))
                        <div class="alert alert-info py-2 small border-0 bg-light text-dark">
                            <strong><i class="bi bi-key"></i> Auth Code:</strong><br>
                            <code class="text-primary">{{ Str::limit(session('dana_auth_code'), 25) }}...</code>
                        </div>
                    @endif

                    <form action="{{ route('dana.do_bind') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-primary w-100 py-2 shadow-sm rounded-pill">
                             Sambungkan Akun DANA
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- KOLOM 2: CEK SALDO --}}
        <div class="col-md-4">
            <div class="card h-100 border-success border-opacity-25">
                <div class="card-header bg-white fw-bold py-3"><i class="bi bi-currency-dollar text-success"></i> 2. Cek Saldo</div>
                <div class="card-body">
                    <p class="small text-muted text-center mb-4">Gunakan Access Token untuk menarik data saldo terbaru.</p>
                    <form action="{{ route('dana.check_balance') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Access Token</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-white"><i class="bi bi-shield-lock"></i></span>
                                <input type="text" name="access_token" class="form-control" placeholder="Token Otomatis Terisi..." required 
                                       value="{{ session('dana_access_token') }}">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-success w-100 py-2 shadow-sm rounded-pill" {{ !session('dana_access_token') ? 'disabled' : '' }}>
                            💰 Perbarui Saldo
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- KOLOM 3: TRANSFER / TOPUP --}}
        <div class="col-md-4">
            <div class="card h-100 border-danger border-opacity-25">
                <div class="card-header bg-white fw-bold py-3"><i class="bi bi-send text-danger"></i> 3. Pencairan (Topup)</div>
                <div class="card-body">
                    <p class="small text-muted text-center mb-4">Kirim uang dari saldo Merchant ke dompet DANA User.</p>
                    <form action="{{ route('dana.topup') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Nomor HP</label>
                            <input type="text" name="phone" class="form-control form-control-sm" value="085745808809" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Nominal (Rp)</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text">Rp</span>
                                <input type="number" name="amount" class="form-control" value="1000" required>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-danger w-100 py-2 shadow-sm rounded-pill">
                            💸 Kirim Sekarang
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <div class="text-center mt-5 mb-5">
        <p class="text-muted small">DANA Sandbox Environment &bull; Version 1.2</p>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>