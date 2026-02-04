@extends('layouts.app')

@section('title', 'Lacak Kiriman - Sancaka Express')

@push('styles')
<style>
    :root {
        --sancaka-blue: #1a73e8;
        --sancaka-blue-dark: #1669c1;
        --sancaka-gray: #f8f9fa;
        --timeline-color: #e9ecef;
        --success-color: #28a745;
        --info-color: #17a2b8;
        --warning-color: #ffc107;
        --secondary-color: #6c757d;
    }

    body {
        background-color: var(--sancaka-gray);
        font-family: 'Poppins', sans-serif;
    }

    .main-content-padding {
        padding-top: 90px;
        padding-bottom: 60px;
    }

    .tracking-card {
        border: none;
        border-radius: 1rem;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
    }

    .tracking-card-header {
        background-color: #ffffff;
        border-bottom: 1px solid #e0e0e0;
        border-top-left-radius: 1rem;
        border-top-right-radius: 1rem;
    }

    #tracking-button {
        background-color: var(--sancaka-blue);
        border-color: var(--sancaka-blue);
        transition: all 0.25s ease-in-out;
        box-shadow: 0 4px 12px rgba(26, 115, 232, 0.25);
        font-weight: 600;
        border-radius: 0.5rem;
    }

    #tracking-button:hover {
        background-color: var(--sancaka-blue-dark);
        border-color: var(--sancaka-blue-dark);
        box-shadow: 0 6px 14px rgba(26, 115, 232, 0.35);
        transform: translateY(-2px);
    }

    .timeline {
        list-style: none;
        padding: 0;
        position: relative;
    }
    .timeline:before {
        content: '';
        position: absolute;
        top: 0; bottom: 0; left: 20px;
        width: 2px;
        background-color: var(--timeline-color);
    }
    .timeline-item {
        position: relative;
        padding-left: 50px;
        margin-bottom: 2.25rem;
        opacity: 0;
        transform: translateY(20px);
        animation: fadeInSlideUp 0.5s ease-out forwards;
    }

    @keyframes fadeInSlideUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .timeline-icon {
        position: absolute;
        left: 10px; top: 0;
        width: 24px; height: 24px;
        border-radius: 50%;
        background-color: var(--sancaka-blue);
        display: flex; align-items: center; justify-content: center;
        color: white; font-size: 12px;
        border: 3px solid #ffffff;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        z-index: 1;
    }

    .timeline-item:first-child .timeline-icon {
        background-color: var(--success-color);
        border-color: var(--success-color);
        box-shadow: 0 4px 15px rgba(40, 167, 69, 0.4);
        transform: scale(1.1);
    }
    .timeline-item:first-child .fw-bold { color: var(--success-color); }

    .timeline-item.order-created .timeline-icon {
        background-color: var(--secondary-color);
        border-color: var(--secondary-color);
    }

    .alert.shadow-sm {
        box-shadow: 0 4px 15px rgba(0,0,0,0.1) !important;
        border-radius: 0.75rem;
    }
</style>
@endpush

@php
if (!function_exists('getTrackingStatusIcon')) {
    function getTrackingStatusIcon($status) {
        $status = strtolower($status ?? '');
        if (str_contains($status, 'dibuat')) return 'fas fa-box';
        if (str_contains($status, 'pickup') || str_contains($status, 'jemput')) return 'fas fa-truck-pickup';
        if (str_contains($status, 'tiba') || str_contains($status, 'hub') || str_contains($status, 'origin') || str_contains($status, 'manifest')) return 'fas fa-warehouse';
        if (str_contains($status, 'perjalanan') || str_contains($status, 'kirim') || str_contains($status, 'transit') || str_contains($status, 'shipment')) return 'fas fa-truck-moving';
        if (str_contains($status, 'kurir') || str_contains($status, 'antar')) return 'fas fa-person-carrying-box';
        if (str_contains($status, 'selesai') || str_contains($status, 'terkirim') || str_contains($status, 'delivered')) return 'fas fa-handshake';
        if (str_contains($status, 'gagal') || str_contains($status, 'retur')) return 'fas fa-exclamation-triangle';
        return 'fas fa-circle-info';
    }
}
@endphp

@section('content')


<div class="container main-content-padding">
    <div class="row justify-content-center">
        <div class="col-lg-8 col-md-10">

            {{-- FORM PENCARIAN --}}
            <div class="card tracking-card mb-5">
                <div class="card-body p-4 p-md-5">
                    <h2 class="card-title text-center fw-bold mb-3" style="color: var(--sancaka-blue);">Lacak Kiriman Anda</h2>
                    <p class="text-center text-muted mb-4">Masukkan nomor resi Sancaka Express atau ekspedisi lain.</p>

                    <form action="{{ route('tracking.search') }}" method="GET" id="tracking-form">
                        <div class="input-group">
                            <input type="text" name="resi" class="form-control form-control-lg" placeholder="Contoh: SCK..." value="{{ request('resi') }}" required>
                            <button class="btn btn-primary px-3 px-md-4" type="submit" id="tracking-button">
                                <i class="fas fa-search me-1"></i> <span class="d-none d-sm-inline">Lacak</span>
                            </button>
                        </div>
                    </form>


                </div>
            </div>

            {{-- HASIL PELACAKAN --}}
            @if (isset($result))

                {{-- ========================================== --}}
                {{-- BLOK 1: TAMPILAN DATA DARI API (KIRIMINAJA) --}}
                {{-- ========================================== --}}
                @if (isset($result['summary'], $result['detail']))
                <div class="card tracking-card">
                    {{-- HEADER: HANYA RESI & TOMBOL CETAK --}}
                    <div class="card-header tracking-card-header p-3">
                        <div class="row align-items-center gy-2">
                            {{-- Bagian Kiri: Label Resi --}}
                            <div class="col-12 col-md-auto me-auto">
                                <div class="d-flex align-items-center flex-wrap gap-2">
                                    <span class="fw-bold text-nowrap">Hasil untuk Resi:</span>
                                    <span class="badge bg-primary fs-6">{{ $result['summary']['awb'] ?? request('resi') }}</span>
                                </div>
                            </div>

                            {{-- Bagian Kanan: Tombol Cetak SAJA (Layanan dipindah) --}}
                            <div class="col-12 col-md-auto">
                                @if ($result['is_pesanan'] ?? false)
                                    <a href="{{ route('cetak_thermal', $result['summary']['awb'] ?? $result['resi']) }}"
                                       target="_blank"
                                       class="btn btn-sm btn-outline-secondary text-nowrap bg-green-50">
                                        <i class="fas fa-print me-1"></i> Cetak Resi
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- BODY CARD --}}
                    <div class="card-body p-4 p-md-5">
                        {{-- Info Pengirim/Penerima --}}
                        <div class="row mb-4">
                            <div class="col-md-6 mb-3">
                                <h6 class="fw-bold text-muted mb-2">PENGIRIM</h6>
                                <p class="mb-1 fs-5 fw-medium">{{ $result['detail']['shipper'] ?? '-' }}</p>
                                <p class="text-muted small mt-1">{{ $result['detail']['origin'] ?? '-' }}</p>
                            </div>
                            <div class="col-md-6">
                                <h6 class="fw-bold text-muted mb-2">PENERIMA</h6>
                                <p class="mb-1 fs-5 fw-medium">{{ $result['detail']['receiver'] ?? '-' }}</p>
                                <p class="text-muted small mt-1">{{ $result['detail']['destination'] ?? '-' }}</p>
                            </div>
                        </div>
                        <hr>

                        {{-- Status Terakhir Alert --}}
                        <div class="alert alert-info text-center my-4 shadow-sm border-0">
                            <h5 class="mb-1 fw-bold">Status Terakhir:</h5>
                            <p class="mb-0 fs-5"><strong>{{ $result['status'] ?? 'Dalam Proses' }}</strong></p>
                            @if(isset($result['summary']['date']))
                                <small class="text-muted">{{ \Carbon\Carbon::parse($result['summary']['date'])->setTimezone('Asia/Jakarta')->translatedFormat('d M Y, H:i') }} WIB</small>
                            @endif
                        </div>
                        <hr class="my-4">

                        {{-- 🔥 POSISI BARU: LAYANAN (DIPINDAH KE SINI) 🔥 --}}
                        <div class="mb-4">
                            <span class="badge bg-info text-dark p-2 fs-6 text-wrap text-start" style="line-height: 1.5;">
                                <i class="fas fa-truck me-2"></i>Layanan: {{ $result['summary']['service'] ?? '-' }} ({{ $result['summary']['courier'] ?? 'Sancaka' }})
                            </span>
                        </div>

                        {{-- Timeline --}}
                        <h5 class="fw-bold mb-4">Riwayat Perjalanan Paket</h5>
                        <ul class="timeline" id="tracking-timeline-kiriminaja">
                            @if (!empty($result['histories']))
                                @foreach ($result['histories'] as $history)
                                <li class="timeline-item">
                                    <div class="timeline-icon">
                                        <i class="{{ getTrackingStatusIcon($history->status ?? '') }}"></i>
                                    </div>
                                    <p class="fw-bold mb-0">{{ $history->status ?? '-' }}</p>

                                    @if(!empty($history->lokasi))
                                        <p class="mb-1 small text-muted">{{ $history->lokasi }}</p>
                                    @endif

                                    <small class="text-muted">
                                        {{ is_a($history->created_at, 'Carbon\Carbon') ? $history->created_at->format('d M Y, H:i') . ' WIB' : $history->created_at }}
                                    </small>
                                </li>
                                @endforeach
                            @else
                                <li class="timeline-item">
                                    <div class="timeline-icon"><i class="fas fa-times"></i></div>
                                    <p class="fw-bold mb-0">Belum ada riwayat</p>
                                </li>
                            @endif
                        </ul>
                    </div>
                </div>

                {{-- ========================================== --}}
                {{-- BLOK 2: TAMPILAN DATA INTERNAL (MANUAL)    --}}
                {{-- ========================================== --}}
                @elseif (isset($result['resi']))
                <div class="card tracking-card">
                    <div class="card-header tracking-card-header p-3">
                        <div class="row align-items-center gy-2">
                            <div class="col-12 col-md-auto me-auto">
                                <div class="d-flex align-items-center flex-wrap gap-2">
                                    <span class="fw-bold text-nowrap">Hasil untuk Resi:</span>
                                    <span class="badge bg-primary fs-6">{{ $result['resi'] }}</span>
                                </div>
                            </div>
                            <div class="col-12 col-md-auto">
                                @if ($result['is_pesanan'] ?? false)
                                    <a href="{{ route('cetak_thermal', $result['resi']) }}"
                                       target="_blank"
                                       class="btn btn-sm btn-outline-secondary text-nowrap">
                                        <i class="fas fa-print me-1"></i> Cetak Resi
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="card-body p-4 p-md-5">
                        <div class="row mb-4">
                            <div class="col-md-6 mb-3">
                                <h6 class="fw-bold text-muted mb-2">PENGIRIM</h6>
                                <p class="mb-1 fs-5 fw-medium">{{ $result['pengirim'] ?? '-' }}</p>
                                @if(!empty($result['no_pengirim'])) <p class="text-muted small mb-1">{{ $result['no_pengirim'] }}</p> @endif
                                @if(!empty($result['alamat_pengirim'])) <p class="text-muted small mb-0">{{ $result['alamat_pengirim'] }}</p> @endif
                            </div>
                            <div class="col-md-6">
                                <h6 class="fw-bold text-muted mb-2">PENERIMA</h6>
                                <p class="mb-1 fs-5 fw-medium">{{ $result['penerima'] ?? '-' }}</p>
                                @if(!empty($result['no_penerima'])) <p class="text-muted small mb-1">{{ $result['no_penerima'] }}</p> @endif
                                @if(!empty($result['alamat_penerima'])) <p class="text-muted small mb-0">{{ $result['alamat_penerima'] }}</p> @endif
                            </div>
                        </div>
                        <hr>

                        <div class="alert alert-info text-center my-4 shadow-sm border-0">
                            <h5 class="mb-1 fw-bold">Status Terakhir:</h5>
                            <p class="mb-0 fs-5"><strong>{{ $result['status'] ?? 'Data Diterima' }}</strong></p>
                        </div>
                        <hr class="my-4">

                        {{-- 🔥 POSISI BARU: LAYANAN INTERNAL (DIPINDAH KE SINI JUGA) 🔥 --}}
                        @if(!empty($result['jasa_ekspedisi_aktual']))
                        <div class="mb-4">
                            <span class="badge bg-info text-dark p-2 fs-6 text-wrap text-start" style="line-height: 1.5;">
                                <i class="fas fa-truck me-2"></i>Layanan: {{ $result['jasa_ekspedisi_aktual'] }}
                            </span>
                        </div>
                        @endif

                        <h5 class="fw-bold mb-4">Riwayat Perjalanan Paket</h5>
                        <ul class="timeline" id="tracking-timeline-sancaka">
                            @if (!empty($result['histories']))
                                @foreach ($result['histories'] as $history)
                                <li class="timeline-item">
                                    <div class="timeline-icon">
                                        <i class="{{ getTrackingStatusIcon($history->status ?? '') }}"></i>
                                    </div>
                                    <p class="fw-bold mb-0">{{ $history->status ?? '-' }}</p>
                                    {{-- KODE BARU (Benar: HTML dirender) --}}
                                    <p class="mb-1 small text-muted">
                                        {{ $history->lokasi ?? '' }}
                                        {!! isset($history->keterangan) ? '- ' . $history->keterangan : '' !!}
                                    </p>
                                    <small class="text-muted">
                                        {{ is_a($history->created_at, 'Carbon\Carbon') ? $history->created_at->format('d M Y, H:i') . ' WIB' : $history->created_at }}
                                    </small>
                                </li>
                                @endforeach
                            @else
                                <li class="timeline-item">
                                    <div class="timeline-icon"><i class="fas fa-hourglass-start"></i></div>
                                    <p class="fw-bold mb-0">Belum Ada Pemindaian</p>
                                </li>
                            @endif
                        </ul>
                    </div>
                </div>
                @endif

            @endif
        </div>
    </div>
</div>

<div class="modal fade" id="errorModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-body text-center p-5">
                <div class="mb-4">
                    {{-- Ikon Animasi --}}
                    <div class="mx-auto d-flex align-items-center justify-content-center bg-danger bg-opacity-10 rounded-circle" style="width: 80px; height: 80px;">
                        <i class="fas fa-search-minus text-danger fa-3x"></i>
                    </div>
                </div>
                <h4 class="fw-bold mb-3 text-dark">Data Tidak Ditemukan</h4>
                <p class="text-muted mb-4">
                    {{ session('error') }}
                </p>
                <button type="button" class="btn btn-danger px-4 py-2 rounded-pill" data-bs-dismiss="modal">
                    Coba Lagi
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Animasi Timeline
        const items = document.querySelectorAll('.timeline-item');
        items.forEach((item, index) => { item.style.animationDelay = `${index * 0.1}s`; });

        // Logic Trigger Modal Error
        @if (session('error'))
            var errorModalEl = document.getElementById('errorModal');
            if (errorModalEl) {
                var modal = new bootstrap.Modal(errorModalEl);
                modal.show();
            }
        @endif
    });
</script>
@endpush
