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
        .btn-action { padding: 2px 10px; font-size: 0.8rem; }
    </style>
</head>
<body class="bg-light">

<div class="header-admin shadow-sm mb-4">
    <div class="container d-flex justify-content-between align-items-center">
        <div>
            <h2 class="mb-0 fw-bold"><i class="bi bi-shield-lock"></i> DANA Admin Center</h2>
            <p class="mb-0 opacity-75">Monitoring Saldo & Token Seluruh Affiliate</p>
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
        <div class="alert alert-success border-0 shadow-sm">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger border-0 shadow-sm">{{ session('error') }}</div>
    @endif

    <div class="table-admin p-3">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Affiliate</th>
                        <th>Status Binding</th>
                        <th>Saldo User (DANA)</th>
                        <th>Saldo Merchant (Pusat)</th>
                        <th>Token & Auth Code</th>
                        <th class="text-center">Aksi Sinkron</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($affiliates as $aff)
                    <tr>
                        <td>
                            <div class="fw-bold">{{ $aff->name }}</div>
                            <small class="text-muted"><i class="bi bi-whatsapp"></i> {{ $aff->whatsapp }}</small>
                        </td>
                        <td>
                            @if($aff->dana_access_token)
                                <span class="status-badge bg-success-subtle text-success border border-success">
                                    <i class="bi bi-check-circle"></i> Terhubung
                                </span>
                            @else
                                <span class="status-badge bg-danger-subtle text-danger border border-danger">
                                    <i class="bi bi-x-circle"></i> Terputus
                                </span>
                            @endif
                        </td>
                        <td>
                            <div class="fw-bold text-success">Rp {{ number_format($aff->balance, 0, ',', '.') }}</div>
                            <small class="text-muted" style="font-size: 0.7rem;">Update: {{ $aff->updated_at }}</small>
                        </td>
                        <td>
                            <div class="fw-bold text-danger">Rp {{ number_format($aff->dana_merchant_balance, 0, ',', '.') }}</div>
                            <form action="{{ route('dana.check_merchant_balance') }}" method="POST">
                                @csrf
                                <input type="hidden" name="affiliate_id" value="{{ $aff->id }}">
                                <button type="submit" class="btn btn-link btn-sm p-0 text-decoration-none" style="font-size: 0.7rem;">Refresh Merchant</button>
                            </form>
                        </td>
                        <td style="max-width: 200px;">
                            <div class="text-truncate small text-muted"><strong>Token:</strong> {{ $aff->dana_access_token ?: '-' }}</div>
                            <div class="text-truncate small text-muted"><strong>Auth:</strong> {{ $aff->dana_auth_code ?: '-' }}</div>
                        </td>
                        <td class="text-center">
                            <div class="d-flex gap-1 justify-content-center">
                                {{-- Tombol Binding --}}
                                <form action="{{ route('dana.do_bind') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="affiliate_id" value="{{ $aff->id }}">
                                    <button type="submit" class="btn btn-primary btn-action" title="Lakukan Binding">
                                        <i class="bi bi-link"></i>
                                    </button>
                                </form>

                                {{-- Tombol Cek Saldo User --}}
                                <form action="{{ route('dana.check_balance') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="affiliate_id" value="{{ $aff->id }}">
                                    <input type="hidden" name="access_token" value="{{ $aff->dana_access_token }}">
                                    <button type="submit" class="btn btn-success btn-action" title="Update Saldo User" {{ !$aff->dana_access_token ? 'disabled' : '' }}>
                                        <i class="bi bi-arrow-repeat"></i>
                                    </button>
                                </form>

                                {{-- Tombol Topup Modal --}}
                                <button class="btn btn-danger btn-action" data-bs-toggle="modal" data-bs-target="#modalTopup{{ $aff->id }}">
                                    <i class="bi bi-cash-stack"></i>
                                </button>
                            </div>

                            {{-- Modal Topup Per User --}}
                            <div class="modal fade" id="modalTopup{{ $aff->id }}" tabindex="-1">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content text-start">
                                        <form action="{{ route('dana.topup') }}" method="POST">
                                            @csrf
                                            <div class="modal-header">
                                                <h5 class="modal-title">Top Up: {{ $aff->name }}</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <input type="hidden" name="affiliate_id" value="{{ $aff->id }}">
                                                <div class="mb-3">
                                                    <label class="form-label">Nomor WhatsApp (Tujuan)</label>
                                                    <input type="text" name="phone" class="form-control" value="{{ $aff->whatsapp }}" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Nominal Transfer (Rp)</label>
                                                    <input type="number" name="amount" class="form-control" value="1000" required>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                <button type="submit" class="btn btn-danger">Kirim Uang Sekarang</button>
                                            </div>
                                        </form>
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
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>