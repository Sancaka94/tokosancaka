<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin DANA Central Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .table-admin { background: white; border-radius: 15px; overflow: hidden; box-shadow: 0 8px 20px rgba(0,0,0,0.05); }
        .header-admin { background: linear-gradient(135deg, #108ee9 0%, #0676c4 100%); color: white; padding: 30px; border-radius: 0 0 30px 30px; }
        .status-badge { font-size: 0.75rem; padding: 5px 12px; border-radius: 50px; }
        .btn-action { padding: 5px 12px; font-size: 0.9rem; border-radius: 8px; transition: 0.3s; }
        .btn-action:hover { opacity: 0.8; transform: translateY(-2px); }
        .balance-label { font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.5px; font-weight: bold; }
    </style>
</head>
<body class="bg-light">

<div class="header-admin shadow-sm mb-4 text-center">
    <div class="container d-flex justify-content-between align-items-center">
        <div class="text-start">
            <h2 class="mb-0 fw-bold"><i class="bi bi-shield-lock"></i> DANA Admin Center</h2>
            <p class="mb-0 opacity-75">Real-time Monitoring: Profit Affiliasi & Saldo Akun DANA</p>
        </div>
        <div class="text-end">
            <span class="badge bg-white text-primary p-2 px-3">
                <i class="bi bi-calendar-event"></i> {{ now()->format('d M Y') }}
            </span>
        </div>
    </div>
</div>

<div class="container-fluid px-4">
    {{-- Notifikasi --}}
    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm alert-dismissible fade show">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger border-0 shadow-sm alert-dismissible fade show">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="table-admin p-3 mb-5">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Informasi Affiliate</th>
                        <th>Koneksi DANA</th>
                        <th>Monitoring Saldo</th>
                        <th>Deposit Merchant</th>
                        <th>Data Kredensial</th>
                        <th class="text-center">Panel Kontrol</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($affiliates as $aff)
                    <tr>
                        {{-- Affiliate Info --}}
                        <td>
                            <div class="fw-bold text-dark">{{ $aff->name }}</div>
                            <small class="text-muted"><i class="bi bi-whatsapp"></i> {{ $aff->whatsapp }}</small><br>
                            <small class="badge bg-secondary-subtle text-secondary" style="font-size: 0.65rem;">ID: {{ $aff->id }}</small>
                        </td>

                        {{-- Binding Status --}}
                        <td>
                            @if($aff->dana_access_token)
                                <span class="status-badge bg-success-subtle text-success border border-success">
                                    <i class="bi bi-check-circle-fill"></i> Terhubung
                                </span>
                            @else
                                <span class="status-badge bg-danger-subtle text-danger border border-danger">
                                    <i class="bi bi-x-circle-fill"></i> Terputus
                                </span>
                            @endif
                        </td>

                        {{-- Balance Monitoring (Profit vs Real DANA) --}}
                        <td>
                            <div class="mb-2">
                                <div class="balance-label text-muted">Profit Sancaka (Affiliete)</div>
                                <div class="fw-bold text-dark">Rp {{ number_format($aff->balance, 0, ',', '.') }}</div>
                            </div>
                            <div>
                                <div class="balance-label text-primary">Saldo Akun DANA (Real)</div>
                                <div class="fw-bold text-primary">Rp {{ number_format($aff->dana_user_balance, 0, ',', '.') }}</div>
                            </div>
                        </td>

                        {{-- Merchant Balance --}}
                        <td>
                            <div class="balance-label text-danger">Saldo Deposit Merchant</div>
                            <div class="fw-bold text-danger">Rp {{ number_format($aff->dana_merchant_balance, 0, ',', '.') }}</div>
                            <form action="{{ route('dana.check_merchant_balance') }}" method="POST">
                                @csrf
                                <input type="hidden" name="affiliate_id" value="{{ $aff->id }}">
                                <button type="submit" class="btn btn-link btn-sm p-0 text-decoration-none text-danger" style="font-size: 0.7rem;">
                                    <i class="bi bi-arrow-clockwise"></i> Refresh Merchant
                                </button>
                            </form>
                        </td>

                        {{-- Credentials --}}
                        <td style="max-width: 180px;">
                            <div class="text-truncate small text-muted" title="{{ $aff->dana_access_token }}">
                                <strong>Token:</strong> {{ $aff->dana_access_token ?: '-' }}
                            </div>
                            <div class="text-truncate small text-muted" title="{{ $aff->dana_auth_code }}">
                                <strong>Auth:</strong> {{ $aff->dana_auth_code ?: '-' }}
                            </div>
                        </td>

                        {{-- Action Buttons --}}
                        <td class="text-center">
                            <div class="d-flex gap-2 justify-content-center">
                                {{-- Binding --}}
                                <form action="{{ route('dana.do_bind') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="affiliate_id" value="{{ $aff->id }}">
                                    <button type="submit" class="btn btn-outline-primary btn-action" title="Binding Akun">
                                        <i class="bi bi-link-45deg"></i>
                                    </button>
                                </form>

                                {{-- Sync Real DANA --}}
                                <form action="{{ route('dana.check_balance') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="affiliate_id" value="{{ $aff->id }}">
                                    <button type="submit" class="btn btn-outline-success btn-action" title="Sinkron Saldo Akun DANA" {{ !$aff->dana_access_token ? 'disabled' : '' }}>
                                        <i class="bi bi-arrow-repeat"></i>
                                    </button>
                                </form>

                                {{-- Disbursement --}}
                                <button class="btn btn-outline-danger btn-action" data-bs-toggle="modal" data-bs-target="#modalTopup{{ $aff->id }}" title="Topup / Cairkan Profit">
                                    <i class="bi bi-cash-stack"></i>
                                </button>
                            </div>

                            {{-- Modal Topup Per User --}}
<div class="modal fade" id="modalTopup{{ $aff->id }}" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content text-start border-0 shadow-lg">
            
            {{-- HEADER MODAL --}}
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title fw-bold"><i class="bi bi-send-fill me-2"></i> Pencairan Profit: {{ $aff->name }}</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body p-4">
                {{-- STEP 1: VERIFIKASI AKUN (ACCOUNT INQUIRY) --}}
                <div class="card mb-3 border-info">
                    <div class="card-header bg-info-subtle small fw-bold text-info">
                        <i class="bi bi-shield-check"></i> TAHAP 1: VERIFIKASI PENERIMA
                    </div>
                    <div class="card-body">
                        <form action="{{ route('dana.account_inquiry') }}" method="POST">
                            @csrf
                            <input type="hidden" name="affiliate_id" value="{{ $aff->id }}">
                            <div class="mb-2">
                                <label class="form-label small">Nomor HP DANA Tujuan</label>
                                <input type="text" name="phone" class="form-control form-control-sm" value="{{ $aff->whatsapp }}" required>
                            
                                {{-- TAMPILAN NAMA HASIL VERIFIKASI --}}
                                    @if($aff->dana_user_name)
                                        <div class="mt-2 p-2 bg-success-subtle border border-success rounded small text-success fw-bold">
                                            <i class="bi bi-person-check-fill"></i> Terverifikasi: {{ $aff->dana_user_name }}
                                        </div>
                                    @endif
                            </div>
                            <button type="submit" class="btn btn-info btn-sm w-100 text-white rounded-pill">
                                <i class="bi bi-search"></i> Cek Nama Pemilik Akun
                            </button>
                        </form>
                    </div>
                </div>

                <hr>

                {{-- STEP 2: EKSEKUSI TRANSFER --}}
                <form action="{{ route('dana.topup') }}" method="POST">
                    @csrf
                    <input type="hidden" name="affiliate_id" value="{{ $aff->id }}">
                    <input type="hidden" name="phone" value="{{ $aff->whatsapp }}">
                    
                    <div class="card border-danger">
                        <div class="card-header bg-danger-subtle small fw-bold text-danger">
                            <i class="bi bi-cash-coin"></i> TAHAP 2: KIRIM SALDO PROFIT
                        </div>
                        <div class="card-body">
                            <div class="alert alert-light border small py-2 mb-3">
                                <div class="text-muted">Profit Tersedia:</div>
                                <div class="fw-bold text-dark fs-5">Rp {{ number_format($aff->balance, 0, ',', '.') }}</div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold small text-danger">Nominal Cair (IDR)</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light fw-bold">Rp</span>
                                    <input type="number" name="amount" class="form-control" value="1000" min="100" max="{{ $aff->balance }}" required>
                                </div>
                                <small class="text-muted" style="font-size: 0.7rem;">*Maksimal sesuai saldo profit</small>
                            </div>

                            <button type="submit" class="btn btn-danger w-100 rounded-pill shadow-sm" onclick="return confirm('Apakah Anda yakin ingin mencairkan saldo profit ini?')">
                                <i class="bi bi-check2-all"></i> Proses Transfer Sekarang
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            
            <div class="modal-footer bg-light py-2">
                <button type="button" class="btn btn-link text-muted btn-sm text-decoration-none" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Riwayat Transaksi Terakhir (Audit Log) --}}

    <div class="table-admin p-3 mt-4">
    <h5 class="fw-bold mb-3"><i class="bi bi-journal-text"></i> Audit Log Transaksi</h5>
    <div class="table-responsive">
        <table class="table table-sm table-hover small">
            <thead class="table-light">
                <tr>
                    <th>Waktu</th>
                    <th>Tipe</th>
                    <th>Affiliate</th>
                    <th>Nomor / Ref</th>
                    <th>Nominal</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $logs = DB::table('dana_transactions')->orderBy('id', 'desc')->limit(15)->get();
                @endphp
                @foreach($logs as $log)
                <tr>
                    <td>{{ $log->created_at }}</td>
                    <td>
                        <span class="badge {{ $log->type == 'TOPUP' ? 'bg-danger' : 'bg-info' }}">{{ $log->type }}</span>
                    </td>
                    <td>ID: {{ $log->affiliate_id }}</td>
                    <td>
                        {{ $log->phone }}<br>
                        <small class="text-muted">{{ $log->reference_no }}</small>
                    </td>
                    <td class="fw-bold">Rp {{ number_format($log->amount, 0, ',', '.') }}</td>
                    <td><span class="text-success fw-bold">{{ $log->status }}</span></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
    
    <div class="text-center pb-5">
        <span class="badge bg-white text-muted shadow-sm p-2 px-3">
            <i class="bi bi-cpu"></i> DANA Integration Layer v2.0 - SNAP & Open API Standard
        </span>
    </div>
</div>



<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>