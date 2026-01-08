<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DANA Integration Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .card { border: none; border-radius: 15px; margin-bottom: 20px; box-shadow: 0 8px 20px rgba(0,0,0,0.05); transition: transform 0.3s; overflow: hidden; }
        .card:hover { transform: translateY(-5px); }
        .header { background: linear-gradient(135deg, #108ee9 0%, #0676c4 100%); color: white; padding: 40px 20px; margin-bottom: 30px; border-radius: 0 0 50px 50px; text-align: center; }
        .monitor-display { background: #f8f9fa; border-bottom: 2px dashed #dee2e6; padding: 20px; text-align: center; }
        .saldo-value { font-size: 2rem; font-weight: 800; color: #108ee9; }
        .status-dot { height: 8px; width: 8px; border-radius: 50%; display: inline-block; margin-right: 5px; }
        .bg-gradient-blue { background: linear-gradient(45deg, #108ee9, #0072ff); color: white; }
        .bg-gradient-green { background: linear-gradient(45deg, #28a745, #20c997); color: white; }
        .bg-gradient-red { background: linear-gradient(45deg, #dc3545, #f86d70); color: white; }
    </style>
</head>
<body class="bg-light">

<div class="header shadow">
    <h1><i class="bi bi-wallet2"></i> DANA API Dashboard</h1>
    <p class="opacity-75">Control Center: Disbursement & Binding Monitoring</p>
</div>

<div class="container">

    {{-- Notifikasi Global --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row">
        {{-- KOLOM 1: ACCOUNT BINDING --}}
        <div class="col-md-4">
            <div class="card h-100">
                <div class="bg-gradient-blue p-3 text-center">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-link-45deg"></i> 1. Binding Akun</h5>
                </div>
                <div class="monitor-display">
                    <span class="text-muted small d-block">Status Koneksi</span>
                    @if(session('dana_access_token'))
                        <div class="text-success fw-bold"><span class="status-dot bg-success"></span> Terhubung</div>
                        <code class="small text-truncate d-block mt-2">{{ Str::limit(session('dana_access_token'), 20) }}</code>
                    @else
                        <div class="text-danger fw-bold"><span class="status-dot bg-danger"></span> Putus</div>
                        <span class="small text-muted">Silakan lakukan binding</span>
                    @endif
                </div>
                <div class="card-body">
                    @if(session('dana_auth_code'))
                        <div class="alert alert-info py-2 small border-0 bg-light mb-3">
                            <i class="bi bi-key"></i> <strong>Auth Code:</strong>
                            <div class="text-truncate">{{ session('dana_auth_code') }}</div>
                        </div>
                    @endif

                    <form action="{{ route('dana.do_bind') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-primary w-100 py-2 shadow-sm rounded-pill">
                             Jalankan Binding
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- KOLOM 2: CEK SALDO USER --}}
        <div class="col-md-4">
            <div class="card h-100">
                <div class="bg-gradient-green p-3 text-center">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-person-circle"></i> 2. Saldo User</h5>
                </div>
                <div class="monitor-display">
                    <span class="text-muted small d-block">Available Balance (User)</span>
                    <div class="saldo-value">
                        <small class="fs-6">Rp</small> {{ number_format((float) (session('saldo_terbaru') ?? 0), 0, ',', '.') }}
                    </div>
                    <span class="text-muted xx-small" style="font-size: 0.7rem;">
                        <i class="bi bi-clock"></i> {{ session('saldo_terbaru') !== null ? now()->format('H:i:s') : 'Belum dicek' }}
                    </span>
                </div>
                <div class="card-body">
                    <form action="{{ route('dana.check_balance') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-success">Access Token User</label>
                            <input type="text" name="access_token" class="form-control form-control-sm border-success border-opacity-25" 
                                   value="{{ session('dana_access_token') }}" placeholder="Token required...">
                        </div>
                        <button type="submit" class="btn btn-success w-100 py-2 shadow-sm rounded-pill" {{ !session('dana_access_token') ? 'disabled' : '' }}>
                            <i class="bi bi-arrow-repeat"></i> Cek Saldo User
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- KOLOM 3: CEK SALDO MERCHANT & TOPUP --}}
        <div class="col-md-4">
            <div class="card h-100">
                <div class="bg-gradient-red p-3 text-center">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-bank"></i> 3. Saldo Merchant</h5>
                </div>
                <div class="monitor-display">
                    <span class="text-muted small d-block">Deposit Balance (Merchant)</span>
                    <div class="saldo-value text-danger">
                        <small class="fs-6">Rp</small> {{ number_format((float) (session('saldo_merchant') ?? 0), 0, ',', '.') }}
                    </div>
                    <form action="{{ route('dana.check_merchant_balance') }}" method="POST" class="mt-2">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-danger border-0 py-0">
                            <i class="bi bi-arrow-clockwise"></i> Refresh Saldo Merchant
                        </button>
                    </form>
                </div>
                <div class="card-body">
                    <form action="{{ route('dana.topup') }}" method="POST">
                        @csrf
                        <div class="row g-2 mb-3">
                            <div class="col-7">
                                <label class="form-label small fw-bold text-danger">Target Phone</label>
                                <input type="text" name="phone" class="form-control form-control-sm" value="085745808809" required>
                            </div>
                            <div class="col-5">
                                <label class="form-label small fw-bold text-danger">Amount</label>
                                <input type="number" name="amount" class="form-control form-control-sm" value="1000" required>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-danger w-100 py-2 shadow-sm rounded-pill">
                            <i class="bi bi-cash-stack"></i> Eksekusi Topup
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <div class="text-center mt-5 mb-5">
        <div class="badge bg-white text-muted shadow-sm p-2 px-3 rounded-pill">
            <span class="status-dot bg-primary"></span> DANA Sandbox Version 2.0 (Open API + SNAP)
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>