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
        font-family: 'Poppins', sans-serif; /* Contoh font lebih modern */
    }

    .main-content-padding {
        padding-top: 90px;
        padding-bottom: 60px;
    }

    .tracking-card {
        border: none;
        border-radius: 1rem; /* Lebih bulat */
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1); /* Shadow lebih dalam */
        transition: all 0.3s ease;
    }

    .tracking-card-header {
        background-color: #ffffff; /* Lebih bersih */
        border-bottom: 1px solid #e0e0e0;
        border-top-left-radius: 1rem;
        border-top-right-radius: 1rem;
        padding: 1.25rem 2rem; /* Padding lebih luas */
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

    #tracking-button:active {
        transform: scale(0.98);
        box-shadow: 0 2px 8px rgba(26, 115, 232, 0.2);
    }

    .form-control-lg {
        border-radius: 0.5rem !important;
        border: 1px solid #ced4da;
        padding: 0.75rem 1rem;
    }
    .form-control-lg:focus {
        border-color: var(--sancaka-blue);
        box-shadow: 0 0 0 0.2rem rgba(26, 115, 232, 0.25);
    }

    /* Timeline Styling */
    .timeline {
        list-style: none;
        padding: 0;
        position: relative;
    }
    .timeline:before {
        content: '';
        position: absolute;
        top: 0; /* Mulai dari atas */
        bottom: 0; /* Sampai bawah */
        left: 20px;
        width: 2px;
        background-color: var(--timeline-color);
    }
    .timeline-item {
        position: relative;
        padding-left: 50px;
        margin-bottom: 2.25rem; /* Jarak antar item */
        opacity: 0; /* Untuk animasi fade-in */
        transform: translateY(20px); /* Untuk animasi slide-up */
        animation: fadeInSlideUp 0.5s ease-out forwards;
    }

    /* Animasi untuk item timeline baru */
    @keyframes fadeInSlideUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .timeline-item:nth-child(1) { animation-delay: 0s; }
    .timeline-item:nth-child(2) { animation-delay: 0.1s; }
    .timeline-item:nth-child(3) { animation-delay: 0.2s; }
    /* ... Tambahkan lebih banyak jika perlu, atau gunakan JS untuk dinamis */


    .timeline-icon {
        position: absolute;
        left: 10px; /* Sesuaikan posisi horizontal */
        top: 0; /* Sesuaikan posisi vertikal */
        width: 24px; /* Ukuran ikon lebih besar */
        height: 24px;
        border-radius: 50%;
        background-color: var(--sancaka-blue);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 12px; /* Ukuran font ikon */
        border: 3px solid #ffffff; /* Border putih */
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15); /* Shadow pada ikon */
        z-index: 1; /* Agar di atas garis */
    }

    /* Highlight untuk item pertama (terbaru) */
    .timeline-item:first-child .timeline-icon {
        background-color: var(--success-color); /* Ikon hijau untuk terbaru */
        border-color: var(--success-color); /* Border juga hijau */
        box-shadow: 0 4px 15px rgba(40, 167, 69, 0.4);
        transform: scale(1.1); /* Sedikit membesar */
        transition: all 0.3s ease;
    }
    .timeline-item:first-child .fw-bold {
        color: var(--success-color); /* Teks status juga hijau */
    }

    /* Styling untuk item terakhir (Pesanan Dibuat) */
    .timeline-item.order-created .timeline-icon {
        background-color: var(--secondary-color); /* Abu-abu untuk awal */
        border-color: var(--secondary-color);
        box-shadow: 0 2px 8px rgba(108, 117, 125, 0.25);
    }

    .timeline-item p.fw-bold {
        margin-bottom: 0.25rem; /* Jarak lebih kecil ke detail */
        font-size: 1.1rem; /* Sedikit lebih besar */
    }
    .timeline-item p.small {
        font-size: 0.875rem; /* Detail lebih kecil */
        color: #6c757d;
    }
    .timeline-item small.text-muted {
        font-size: 0.75rem; /* Waktu lebih kecil */
        color: #999;
    }

    /* Alert untuk status */
    .alert.shadow-sm {
        box-shadow: 0 4px 15px rgba(0,0,0,0.1) !important;
        border-radius: 0.75rem;
    }
    .alert-success { background-color: #d4edda; color: #155724; border-color: #c3e6cb; }
    .alert-info { background-color: #d1ecf1; color: #0c5460; border-color: #bee5eb; }
    .alert-warning { background-color: #fff3cd; color: #856404; border-color: #ffeeba; }
    .alert-secondary { background-color: #e2e3e5; color: #383d41; border-color: #d6d8db; }

    /* Responsif */
    @media (max-width: 767.98px) {
        .tracking-card-header {
            flex-direction: column;
            align-items: flex-start !important;
            padding: 1rem 1.5rem;
        }
        .tracking-card-header .badge {
            margin-top: 0.5rem;
            margin-left: 0 !important;
        }
        .timeline-item {
            margin-bottom: 1.5rem;
        }
    }
</style>
@endpush

@php
// Pastikan semua fungsi helper didefinisikan DI DALAM blok PHP ini.

if (!function_exists('getSancakaStatusIcon')) {
    function getSancakaStatusIcon($status) {
        $status = strtolower($status);
        switch ($status) {
            case 'pesanan dibuat':
                return 'fas fa-box';
            case 'paket diambil':
            case 'paket dijemput':
            case 'pickup berhasil':
                return 'fas fa-truck-pickup';
            case 'tiba di agen':
            case 'tiba di hub':
                return 'fas fa-warehouse';
            case 'dalam perjalanan':
            case 'transit':
            case 'dikirim ke tujuan':
            case 'pengiriman':
                return 'fas fa-truck-moving';
            case 'dengan kurir':
            case 'proses pengantaran':
                return 'fas fa-person-carrying-box';
            case 'selesai kirim':
            case 'terkirim':
            case 'delivered':
                return 'fas fa-handshake';
            case 'gagal antar':
            case 'retur':
                return 'fas fa-exclamation-triangle';
            case 'menunggu pembayaran':
                return 'fas fa-money-bill-wave';
            case 'batal':
            case 'cancelled':
                return 'fas fa-ban';
            case 'proses sortir':
            case 'sortir ulang':
                return 'fas fa-dolly';
            default:
                return 'fas fa-circle-info';
        }
    }
}

if (!function_exists('getKiriminAjaStatusIcon')) {
    function getKiriminAjaStatusIcon($description) {
        $description = strtolower($description);
        if (str_contains($description, 'pickup')) {
            return 'fas fa-truck-pickup';
        } elseif (str_contains($description, 'origin')) {
            return 'fas fa-warehouse';
        } elseif (str_contains($description, 'transit')) {
            return 'fas fa-route';
        } elseif (str_contains($description, 'delivered')) {
            return 'fas fa-handshake';
        } elseif (str_contains($description, 'gagal')) {
            return 'fas fa-exclamation-triangle';
        } elseif (str_contains($description, 'tiba di')) {
            return 'fas fa-city';
        } elseif (str_contains($description, 'manifest')) {
            return 'fas fa-file-invoice';
        } elseif (str_contains($description, 'dengan kurir')) {
            return 'fas fa-person-carrying-box';
        }
        return 'fas fa-circle-info';
    }
}
@endphp

@section('content')
@include('layouts.partials.notifications')

<div class="container main-content-padding">
    <div class="row justify-content-center">
        <div class="col-lg-8 col-md-10">
            {{-- KOTAK PENCARIAN UTAMA --}}
            <div class="card tracking-card mb-5">
                <div class="card-body p-4 p-md-5">
                    <h2 class="card-title text-center fw-bold mb-3" style="color: var(--sancaka-blue);">Lacak Kiriman Anda</h2>
                    <p class="text-center text-muted mb-4">Masukkan nomor resi Sancaka Express atau ekspedisi lain yang didukung.</p>

                    <form action="{{ route('tracking.search') }}" method="GET" id="tracking-form">
                        <div class="input-group">
                            <input type="text" name="resi" class="form-control form-control-lg" placeholder="Contoh: SCK... atau MOCK..." value="{{ request('resi') }}" required aria-label="Nomor Resi">
                            <button class="btn btn-primary px-3 px-md-4" type="submit" id="tracking-button">
                                <i class="fas fa-search me-1 me-sm-2"></i>
                                <span class="d-none d-sm-inline">Lacak</span>
                                <span class="d-inline d-sm-none">Cari</span>
                            </button>
                        </div>
                    </form>

                    @if (session('error'))
                        <div class="alert alert-danger mt-4">{{ session('error') }}</div>
                    @endif
                </div>
            </div>

            {{-- ====================================================== --}}
            {{-- 					HASIL PELACAKAN 					--}}
            {{-- ====================================================== --}}
            @if (isset($result))

                {{-- ===== Blok untuk API Kirimin Aja / Pihak Ketiga (Waktu UTC) ===== --}}
                @if (isset($result['summary'], $result['detail'], $result['history']))
                <div class="card tracking-card">
                    <div class="card-header tracking-card-header p-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div>
                            <span class="fw-bold">Hasil untuk Resi:</span>
                            <span class="badge bg-primary fs-6 ms-2">{{ $result['summary']['awb'] }}</span>
                        </div>
                        <span class="badge bg-info text-dark">Layanan: {{ $result['summary']['service'] }} ({{ $result['summary']['courier'] }})</span>
                    </div>
                    <div class="card-body p-4 p-md-5">
                        {{-- INFO PENGIRIM & PENERIMA --}}
                        <div class="row mb-4">
                            <div class="col-md-6 mb-3 mb-md-0">
                                <h6 class="fw-bold text-muted mb-2">PENGIRIM</h6>
                                <p class="mb-1 fs-5 fw-medium">{{ $result['detail']['shipper'] }}</p>
                                <p class="text-muted small mt-1">{{ $result['detail']['origin'] }}</p>
                            </div>
                            <div class="col-md-6">
                                <h6 class="fw-bold text-muted mb-2">PENERIMA</h6>
                                <p class="mb-1 fs-5 fw-medium">{{ $result['detail']['receiver'] }}</p>
                                <p class="text-muted small mt-1">{{ $result['detail']['destination'] }}</p>
                            </div>
                        </div>

                        <hr>

                        {{-- STATUS TERAKHIR --}}
                        <div class="alert alert-info text-center my-4 shadow-sm border-0" role="alert">
                            <h5 class="mb-1 fw-bold">Status Terakhir:</h5>
                            <p class="mb-0 fs-5"><strong>{{ $result['summary']['status'] }}</strong></p>
                            {{-- PERBAIKAN TIMEZONE API KIRIMINAJA --}}
                            <small class="text-muted">{{ \Carbon\Carbon::parse($result['summary']['date'])->timezone('Asia/Jakarta')->translatedFormat('d M Y, H:i') }} WIB</small>
                        </div>

                        <hr class="my-4">

                        {{-- TIMELINE STATUS PENGIRIMAN --}}
                        <h5 class="fw-bold mb-4">Riwayat Perjalanan Paket</h5>
                        <ul class="timeline" id="tracking-timeline-kiriminaja">
                            @if (!empty($result['history']))
                                {{-- Mengurutkan riwayat berdasarkan tanggal DESC (Terbaru di atas) --}}
                                @php
                                    $sortedHistory = collect($result['history'])->sortByDesc(function ($item) {
                                        return \Carbon\Carbon::parse($item['date']);
                                    });
                                @endphp
                                @foreach ($sortedHistory as $history)
                                <li class="timeline-item">
                                    <div class="timeline-icon">
                                        <i class="{{ getKiriminAjaStatusIcon($history['desc']) }}"></i>
                                    </div>
                                    <p class="fw-bold mb-0">{{ $history['desc'] }}</p>
                                    @if(!empty($history['location']))
                                    <p class="mb-1 small text-muted">{{ $history['location'] }}</p>
                                    @endif
                                    {{-- PERBAIKAN TIMEZONE API KIRIMINAJA --}}
                                    <small class="text-muted">{{ \Carbon\Carbon::parse($history['date'])->timezone('Asia/Jakarta')->translatedFormat('d M Y, H:i') }} WIB</small>
                                </li>
                                @endforeach
                            @else
                                <li class="timeline-item">
                                    <div class="timeline-icon"><i class="fas fa-times"></i></div>
                                    <p class="fw-bold mb-0">Tidak ada riwayat</p>
                                    <p class="mb-1 small text-muted">Belum ada riwayat perjalanan untuk paket ini.</p>
                                </li>
                            @endif
                        </ul>
                    </div>
                </div>

                {{-- ===== Blok untuk Data Internal SANCAKA (Waktu Database) ===== --}}
                @elseif (isset($result['resi']))
                <div class="card tracking-card">
                    <div class="card-header tracking-card-header p-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div>
                            <span class="fw-bold">Hasil untuk Resi:</span>
                            <span class="badge bg-primary fs-6 ms-2">{{ $result['resi'] }}</span>
                        </div>
                        {{-- Tombol Cetak Resi --}}
                        @if ($result['is_pesanan'])
                            <a href="{{ route('cetak_thermal', $result['resi']) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-print me-1"></i> Cetak Resi
                            </a>
                        @endif
                    </div>
                    <div class="card-body p-4 p-md-5">
                        {{-- PERBAIKAN: Tampilkan Detail Pengirim/Penerima --}}
                        <div class="row mb-4">
                            <div class="col-md-6 mb-3 mb-md-0">
                                <h6 class="fw-bold text-muted mb-2">PENGIRIM</h6>
                                <p class="mb-1 fs-5 fw-medium">{{ $result['pengirim'] }}</p>
                                @if(!empty($result['no_pengirim']) && $result['no_pengirim'] !== 'N/A')
                                    <p class="text-muted small mb-1"><i class="fas fa-phone-alt fa-fw me-1"></i>{{ $result['no_pengirim'] }}</p>
                                @endif
                                @if(!empty($result['alamat_pengirim']) && $result['alamat_pengirim'] !== 'N/A')
                                    <p class="text-muted small mb-0"><i class="fas fa-map-marker-alt fa-fw me-1"></i>{{ $result['alamat_pengirim'] }}</p>
                                @endif
                            </div>
                            <div class="col-md-6">
                                <h6 class="fw-bold text-muted mb-2">PENERIMA</h6>
                                <p class="mb-1 fs-5 fw-medium">{{ $result['penerima'] ?? 'N/A' }}</p>
                                @if(!empty($result['no_penerima']) && $result['no_penerima'] !== 'N/A')
                                    <p class="text-muted small mb-1"><i class="fas fa-phone-alt fa-fw me-1"></i>{{ $result['no_penerima'] }}</p>
                                @endif
                                @if(!empty($result['alamat_penerima']) && $result['alamat_penerima'] !== 'N/A')
                                    <p class="text-muted small mb-0"><i class="fas fa-map-marker-alt fa-fw me-1"></i>{{ $result['alamat_penerima'] }}</p>
                                @endif
                            </div>
                        </div>

                        <hr>

                        {{-- LOGIKA ALERT STATUS (Diperbaiki) --}}
                        @if ($result['is_pesanan'])
                            @php
                                $status_lower = strtolower($result['status']);
                            @endphp
                            @if(in_array($status_lower, ['selesai', 'delivered', 'completed', 'terkirim']))
                                <div class="alert alert-success shadow-sm border-0 fw-semibold my-4" role="alert">
                                    <h5 class="alert-heading fw-bold">✔️ Paket Telah Diterima</h5>
                                    <p>Paket dengan resi <strong>{{$result['resi_aktual'] ?? $result['resi']}}</strong> telah berhasil diantar.</p>
                                </div>
                            @elseif(in_array($status_lower, ['sedang dikirim', 'shipment', 'delivering', 'on delivery', 'transit', 'diproses']))
                                <div class="alert alert-info shadow-sm border-0 fw-semibold my-4" role="alert">
                                    <h5 class="alert-heading fw-bold">📦 Paket Dalam Perjalanan</h5>
                                    <p>Status terakhir: <strong>{{$result['status']}}</strong>.</p>
                                </div>
                            @elseif(in_array($status_lower, ['menunggu pickup']))
                                <div class="alert alert-warning shadow-sm border-0 fw-semibold my-4" role="alert">
                                    <h5 class="alert-heading fw-bold">🚚 Menunggu Penjemputan</h5>
                                    <p>Paket Anda sedang menunggu dijemput oleh kurir.</p>
                                </div>
                            @else {{-- Menunggu Pembayaran, Gagal, Batal, dll. --}}
                                <div class="alert alert-secondary text-center my-4" role="alert">
                                    <h5 class="mb-1 fw-bold">Status Saat Ini:</h5>
                                    <p class="mb-0 fs-5"><strong>{{ $result['status'] }}</strong></p>
                                </div>
                            @endif
                        @else {{-- Fallback jika bukan dari tabel Pesanan --}}
                            <div class="alert alert-info text-center my-4" role="alert">
                                <h5 class="mb-1 fw-bold">✔️ Status Terakhir:</h5>
                                <p class="mb-0 fs-5"><strong>{{ $result['status'] }}</strong></p>
                            </div>
                        @endif

                        <hr class="my-4">

                        {{-- TIMELINE STATUS PENGIRIMAN --}}
                        <h5 class="fw-bold mb-4">Riwayat Perjalanan Paket</h5>
                        <ul class="timeline" id="tracking-timeline-sancaka">
                            
                            {{-- Loop untuk riwayat dari database (TERBARU DI ATAS) --}}
                            @if (!empty($result['histories']))
                                {{-- KUNCI PERBAIKAN: Mengurutkan riwayat berdasarkan created_at DESC (Terbaru di atas) --}}
                                @php
                                    $sortedHistories = collect($result['histories'])->sortByDesc('created_at');
                                @endphp
                                @foreach ($sortedHistories as $history)
                                <li class="timeline-item">
                                    <div class="timeline-icon">
                                        <i class="{{ getSancakaStatusIcon($history->status) }}"></i>
                                    </div>
                                    <p class="fw-bold mb-0">{{ $history->status }}</p>
                                    <p class="mb-1 small text-muted">{{ $history->lokasi }} {{ $history->keterangan ? '- '.$history->keterangan : '' }}</p>
                                    {{-- PERBAIKAN TIMEZONE DATABASE (ASUMSI UTC) --}}
                                    <small class="text-muted">{{ \Carbon\Carbon::parse($history->created_at)->timezone('Asia/Jakarta')->translatedFormat('d M Y, H:i') }} WIB</small>
                                </li>
                                @endforeach
                            @endif

                            {{-- Item paling akhir statis: Pesanan Dibuat --}}
                            @if(isset($result['tanggal_dibuat']))
                            <li class="timeline-item order-created">
                                <div class="timeline-icon"><i class="fas fa-box"></i></div>
                                <p class="fw-bold mb-0">Pesanan Dibuat</p>
                                <p class="mb-1 small text-muted">Data diterima sistem Sancaka Express.</p>
                                {{-- PERBAIKAN TIMEZONE DATABASE --}}
                                <small class="text-muted">{{ \Carbon\Carbon::parse($result['tanggal_dibuat'])->timezone('Asia/Jakarta')->translatedFormat('d M Y, H:i') }} WIB</small>
                            </li>
                            @endif
                            
                            {{-- Jika tidak ada riwayat scan sama sekali (dan juga 'Pesanan Dibuat' tidak ada) --}}
                            @if (empty($result['histories']) && !isset($result['tanggal_dibuat']))
                                <li class="timeline-item">
                                    <div class="timeline-icon"><i class="fas fa-hourglass-start"></i></div>
                                    <p class="fw-bold mb-0">Belum Ada Pemindaian</p>
                                    <p class="mb-1 small text-muted">Paket sedang menunggu proses selanjutnya.</p>
                                </li>
                            @endif
                        </ul>
                    </div>
                </div>
                @endif
            {{-- Akhir @if (isset($result)) --}}
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// ... (Semua fungsi JavaScript Anda: copyResiAktual, debounce, dll.)
    function copyResiAktual(buttonElement) {
        const resiText = buttonElement.dataset.resi;
        navigator.clipboard.writeText(resiText).then(() => {
            const originalIcon = buttonElement.innerHTML;
            buttonElement.innerHTML = '<i class="fas fa-check"></i> Disalin!';
            buttonElement.classList.add('btn-success');
            buttonElement.classList.remove('btn-outline-primary');
            setTimeout(() => {
                buttonElement.innerHTML = originalIcon;
                buttonElement.classList.remove('btn-success');
                buttonElement.classList.add('btn-outline-primary');
            }, 1500);
        }).catch(err => {
            console.error('Failed to copy text: ', err);
            alert('Gagal menyalin resi.');
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        const trackingForm = document.getElementById('tracking-form');
        const trackingButton = document.getElementById('tracking-button');

        if (trackingForm && trackingButton) {
            trackingForm.addEventListener('submit', function() {
                trackingButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Memuat...';
                trackingButton.disabled = true;
            });
        }

        // Apply animation delay dynamically for better visual flow
        const timelineItems = document.querySelectorAll('.timeline-item');
        timelineItems.forEach((item, index) => {
            item.style.animationDelay = `${index * 0.1}s`;
        });
    });

    // =============================================
    // Fungsi Refresh Timeline Otomatis
    // =============================================
    @if (isset($result['resi']) && !in_array(strtolower($result['status'] ?? ''), ['selesai', 'delivered', 'completed', 'terkirim', 'batal', 'cancelled', 'failed']))
        const resiToRefresh = "{{ $result['resi'] }}";
        const timelineElementId = "{{ isset($result['summary']) ? 'tracking-timeline-kiriminaja' : 'tracking-timeline-sancaka' }}";

        function refreshTimeline() {
            fetch("{{ route('tracking.refresh') }}?resi=" + resiToRefresh)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.html) {
                        const timelineElement = document.getElementById(timelineElementId);
                        if (timelineElement) {
                            // Cek apakah ada perubahan sebelum update total
                            if (timelineElement.innerHTML.trim() !== data.html.trim()) {
                                timelineElement.innerHTML = data.html;
                                console.log('Timeline updated at ' + new Date().toLocaleTimeString());
                                // Re-apply animation delays for new/updated items
                                const updatedTimelineItems = timelineElement.querySelectorAll('.timeline-item');
                                updatedTimelineItems.forEach((item, index) => {
                                    item.style.animationDelay = `${index * 0.1}s`;
                                    item.style.animation = 'none'; // Reset animation
                                    void item.offsetWidth; // Trigger reflow
                                    item.style.animation = null; // Re-enable animation
                                });
                            }
                        }
                    } else if (data.message) {
                        console.warn('Failed to refresh timeline:', data.message);
                    }
                })
                .catch(error => {
                    console.error('Error fetching timeline update:', error);
                });
        }

        // Refresh setiap 60 detik (60000 milidetik)
        setInterval(refreshTimeline, 60000); 

        console.log(`Auto-refresh enabled for resi ${resiToRefresh}, checking every 60 seconds.`);

    @endif
</script>
@endpush