@extends('layouts.app')

@section('title', 'Lacak Kiriman - Sancaka Express')

@push('styles')
<style>
    :root {
        --sancaka-blue: #1a73e8;
        --sancaka-blue-dark: #1669c1;
        --sancaka-gray: #f8f9fa;
        --timeline-color: #e9ecef;
    }

    body {
        background-color: var(--sancaka-gray);
    }

    /* Perbaikan untuk header overlap, tambahkan padding atas ke container utama */
    .main-content-padding {
        padding-top: 90px; /* Sesuaikan nilai ini dengan tinggi header Anda */
        padding-bottom: 60px;
    }

    .tracking-card {
        border: none;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        transition: all 0.3s ease;
    }

    .tracking-card-header {
        background-color: var(--sancaka-gray);
        border-bottom: 1px solid #dee2e6;
        border-top-left-radius: 12px;
        border-top-right-radius: 12px;
    }

    #tracking-button {
        background-color: var(--sancaka-blue);
        border-color: var(--sancaka-blue);
        transition: all 0.25s ease-in-out;
        box-shadow: 0 4px 12px rgba(26, 115, 232, 0.25);
        font-weight: 600;
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
        top: 5px;
        bottom: 5px;
        left: 20px;
        width: 2px;
        background-color: var(--timeline-color);
    }
    .timeline-item {
        position: relative;
        padding-left: 50px;
        margin-bottom: 2rem;
    }
    .timeline-icon {
        position: absolute;
        left: 12px;
        top: 5px;
        width: 18px;
        height: 18px;
        border-radius: 50%;
        background-color: var(--sancaka-blue);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 10px;
        border: 3px solid var(--sancaka-gray);
    }
    /* Mengubah selector untuk menyorot item terakhir (status terbaru) */
    .timeline-item:last-child .timeline-icon {
        background-color: #198754; /* Green for the latest status */
    }

    /* Copy Button Enhancement */
    .copy-btn .copy-icon { display: inline-block; }
    .copy-btn .check-icon { display: none; }
    .copy-btn.copied { background-color: #198754; border-color: #198754; }
    .copy-btn.copied .copy-icon { display: none; }
    .copy-btn.copied .check-icon { display: inline-block; }

</style>
@endpush

@section('content')
{{-- Gunakan class 'main-content-padding' untuk memberi ruang dari header --}}
@include('layouts.partials.notifications')

<div class="container main-content-padding">
    <div class="row justify-content-center">
        <div class="col-lg-8 col-md-10">
            {{-- KOTAK PENCARIAN UTAMA --}}
            <div class="card tracking-card">
                <div class="card-body p-4 p-md-5">
                    <h2 class="card-title text-center fw-bold mb-3" style="color: var(--sancaka-blue);">Lacak Kiriman Anda</h2>
                    <p class="text-center text-muted mb-4">Masukkan nomor resi Sancaka Express atau ekspedisi lain yang didukung untuk melihat status pengiriman paket Anda.</p>

                    <form action="{{ route('tracking.search') }}" method="GET" id="tracking-form">
                        <div class="input-group">
                            <input type="text" name="resi" class="form-control form-control-lg" placeholder="Contoh: SCK... atau SPX..." value="{{ request('resi') }}" required aria-label="Nomor Resi">
                            <button class="btn btn-primary px-3 px-md-4" type="submit" id="tracking-button">
                                <i class="fas fa-search me-2"></i>
                                <span class="d-none d-sm-inline">Lacak Sekarang</span>
                            </button>
                        </div>
                    </form>

                    @if (session('error'))
                        <div class="alert alert-danger mt-4">{{ session('error') }}</div>
                    @endif
                </div>
            </div>

            {{-- HASIL PELACAKAN --}}
            @if (isset($result))

                {{-- ====================================================== --}}
                {{--          LOGIKA BARU UNTUK API KIRIMIN AJA             --}}
                {{-- ====================================================== --}}
                @if (isset($result['summary'], $result['detail'], $result['history']))
                <div class="card tracking-card mt-5">
                    <div class="card-header tracking-card-header p-3 d-flex justify-content-between align-items-center">
                        <div>
                            <span class="fw-bold">Hasil untuk Resi:</span>
                            <span class="badge bg-primary fs-6 ms-2">{{ $result['summary']['awb'] }}</span>
                        </div>
                        <span class="badge bg-info text-dark">Layanan: {{ $result['summary']['service'] }} ({{ $result['summary']['courier'] }})</span>
                    </div>
                    <div class="card-body p-4 p-md-5">
                        {{-- INFO PENGIRIM & PENERIMA --}}
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h6 class="fw-bold text-muted">PENGIRIM</h6>
                                <p class="mb-0 fs-5">{{ $result['detail']['shipper'] }}</p>
                                <p class="text-muted small mt-1">{{ $result['detail']['origin'] }}</p>
                            </div>
                            <div class="col-md-6 mt-3 mt-md-0">
                                <h6 class="fw-bold text-muted">PENERIMA</h6>
                                <p class="mb-0 fs-5">{{ $result['detail']['receiver'] }}</p>
                                <p class="text-muted small mt-1">{{ $result['detail']['destination'] }}</p>
                            </div>
                        </div>

                        <hr>

                        {{-- STATUS TERAKHIR --}}
                        <div class="alert alert-info text-center my-4" role="alert">
                            <h5 class="mb-1 fw-bold">✅ Status Terakhir:</h5>
                            <p class="mb-0 fs-5"><strong>{{ $result['summary']['status'] }}</strong></p>
                             <small class="text-muted">{{ \Carbon\Carbon::parse($result['summary']['date'])->translatedFormat('d M Y, H:i') }} WIB</small>
                        </div>

                        <hr class="my-4">

                        {{-- TIMELINE STATUS PENGIRIMAN --}}
                        <h5 class="fw-bold mb-4">Riwayat Perjalanan Paket</h5>
                        <ul class="timeline">
                            @if (!empty($result['history']))
                                {{-- Menghapus array_reverse agar urutan kronologis (terlama di atas) --}}
                                @foreach ($result['history'] as $history)
                                <li class="timeline-item">
                                    <div class="timeline-icon"><i class="fas fa-check"></i></div>
                                    <p class="fw-bold mb-0">{{ $history['desc'] }}</p>
                                    @if(!empty($history['location']))
                                    <p class="mb-1 small text-muted">{{ $history['location'] }}</p>
                                    @endif
                                    <small class="text-muted">{{ \Carbon\Carbon::parse($history['date'])->translatedFormat('d M Y, H:i') }} WIB</small>
                                </li>
                                @endforeach
                            @else
                                <li class="timeline-item">
                                    <div class="timeline-icon"><i class="fas fa-times"></i></div>
                                    <p class="fw-bold mb-0">Tidak ada riwayat</p>
                                    <p class="mb-1 small text-muted">Tidak ditemukan riwayat perjalanan untuk paket ini.</p>
                                </li>
                            @endif
                        </ul>
                    </div>
                </div>

                {{-- ====================================================== --}}
                {{--          LOGIKA LAMA UNTUK SANCAKA & LAINNYA           --}}
                {{-- ====================================================== --}}
                {{-- Mengubah @else menjadi @elseif untuk pengecekan yang lebih spesifik --}}
                @elseif (isset($result['resi']))
                <div class="card tracking-card mt-5">
                    <div class="card-header tracking-card-header p-3 d-flex justify-content-between align-items-center">
                        <div>
                            <span class="fw-bold">Hasil untuk Resi:</span>
                            <span class="badge bg-primary fs-6 ms-2">{{ $result['resi'] }}</span>
                        </div>
                        @if ($result['is_pesanan'] && $result['resi'])
                            <a href="{{ route('cetak_thermal', $result['resi']) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-print me-1"></i> Cetak Resi
                            </a>
                        @endif
                    </div>
                    <div class="card-body p-4 p-md-5">
                        {{-- INFO PENGIRIM & PENERIMA --}}
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h6 class="fw-bold text-muted">PENGIRIM</h6>
                                <p class="mb-0 fs-5">{{ $result['pengirim'] }}</p>
                                 @if(!empty($result['no_pengirim']) && $result['no_pengirim'] !== 'N/A')
                                     <p class="text-muted small mt-1">{{ $result['no_pengirim'] }}</p>
                                 @endif
                                 @if(!empty($result['alamat_pengirim']) && $result['alamat_pengirim'] !== 'N/A')
                                     <p class="text-muted small mt-1">{{ $result['alamat_pengirim'] }}</p>
                                 @endif
                            </div>
                            <div class="col-md-6 mt-3 mt-md-0">
                                <h6 class="fw-bold text-muted">PENERIMA</h6>
                                <p class="mb-0 fs-5">{{ $result['penerima'] ?? 'N/A' }}</p>
                                @if(!empty($result['no_penerima']) && $result['no_penerima'] !== 'N/A')
                                    <p class="text-muted small mt-1">{{ $result['no_penerima'] }}</p>
                                @endif
                                 @if(!empty($result['alamat_penerima']) && $result['alamat_penerima'] !== 'N/A')
                                     <p class="text-muted small mt-1">{{ $result['alamat_penerima'] }}</p>
                                 @endif
                            </div>
                        </div>

                        <hr>

                        {{-- LOGIKA ALERT YANG DETAIL --}}
                        @if ($result['is_pesanan'])
                            @php
                                // Normalisasi status ke huruf kecil untuk perbandingan yang lebih andal
                                $status_lower = strtolower($result['status']);
                            @endphp

                            {{-- Kondisi untuk paket yang sudah selesai/terkirim --}}
                            @if(in_array($status_lower, ['selesai', 'delivered', 'completed', 'terkirim']))
                                <div class="alert alert-success shadow-sm border-0 fw-semibold my-4" role="alert">
                                    <h5 class="alert-heading fw-bold">✅ Paket Telah Diterima</h5>
                                    <p>Paket dengan resi <strong>{{$result['resi_aktual'] ?? $result['resi']}}</strong> telah berhasil diantar ke tujuan. Terima kasih telah menggunakan layanan kami.</p>
                                    <hr>
                                    <p class="mb-0 fst-italic small">Manajemen Sancaka Express</p>
                                </div>
                            {{-- Kondisi untuk paket yang sedang dalam perjalanan --}}
                            @elseif(in_array($status_lower, ['sedang dikirim', 'shipment', 'delivering', 'on delivery', 'transit']))
                                <div class="alert alert-info shadow-sm border-0 fw-semibold my-4" role="alert">
                                    <h5 class="alert-heading fw-bold">📦 Paket Dalam Perjalanan</h5>
                                    <p>Status terakhir paket Anda dengan resi <strong>{{$result['resi_aktual'] ?? $result['resi']}}</strong> adalah <strong>{{$result['status']}}</strong>. Semoga paket Anda aman dan selamat sampai tujuan.</p>
                                    <hr>
                                    <p class="mb-0 fst-italic small">Manajemen Sancaka Express</p>
                                </div>
                            {{-- Kondisi fallback untuk status lainnya (menunggu scan, dll) --}}
                            @else
                                <div class="alert alert-warning text-center my-4" role="alert">
                                    <h5 class="mb-2 fw-bold">❗ Menunggu Resi Aktual</h5>
                                    <p class="mb-3">Paket Anda sedang menunggu proses scan untuk mendapatkan resi dari ekspedisi.</p>
                                    @php
                                        $waMessage = rawurlencode("Hallo Admin Sancaka,\n\nResi: *{$result['resi']}* \nMohon segera dilakukan input resi aktual, karena customer saya sangat membutuhkan. 🙏\n\nTerima kasih.");
                                        $waLink = "https://wa.me/628819435180?text={$waMessage}";
                                    @endphp
                                    <a href="{{ $waLink }}" class="btn btn-success" target="_blank">
                                        <i class="fab fa-whatsapp me-2"></i> Hubungi Admin
                                    </a>
                                </div>
                            @endif
                        @else
                            <div class="alert alert-info text-center my-4" role="alert">
                                <h5 class="mb-1 fw-bold">✅ Status Terakhir:</h5>
                                <p class="mb-0 fs-5"><strong>{{ $result['status'] }}</strong></p>
                            </div>
                        @endif

                        <hr class="my-4">

                        {{-- TIMELINE STATUS PENGIRIMAN --}}
                        <h5 class="fw-bold mb-4">Riwayat Perjalanan Paket</h5>
                        <ul class="timeline">
                            <li class="timeline-item">
                                 <div class="timeline-icon"><i class="fas fa-box"></i></div>
                                <p class="fw-bold mb-0">Pesanan Dibuat</p>
                                <p class="mb-1 small text-muted">Data pesanan telah diterima oleh sistem Sancaka Express.</p>
                                <small class="text-muted">{{ \Carbon\Carbon::parse($result['tanggal_dibuat'])->translatedFormat('d M Y, H:i') }} WIB</small>
                            </li>
                            @foreach ($result['histories'] as $history)
                            <li class="timeline-item">
                                <div class="timeline-icon"><i class="fas fa-check"></i></div>
                                <p class="fw-bold mb-0">{{ $history->status }}</p>
                                <p class="mb-1 small text-muted">{{ $history->lokasi }} - {{ $history->keterangan }}</p>
                                <small class="text-muted">{{ \Carbon\Carbon::parse($history->created_at)->translatedFormat('d M Y, H:i') }} WIB</small>
                            </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
                @endif
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Fungsi untuk menyalin resi dengan feedback visual tanpa alert
    function copyResiAktual(buttonElement) {
        const text = document.getElementById("resiAktual").innerText;
        navigator.clipboard.writeText(text).then(() => {
            const originalText = buttonElement.querySelector('.copy-text').innerText;
            
            buttonElement.classList.add('copied');
            buttonElement.querySelector('.copy-text').innerText = 'Tersalin!';

            setTimeout(() => {
                buttonElement.classList.remove('copied');
                buttonElement.querySelector('.copy-text').innerText = originalText;
            }, 2000); // Kembali ke state semula setelah 2 detik
        }).catch(err => {
            console.error('Gagal menyalin resi: ', err);
            // Fallback untuk browser lama jika diperlukan
            try {
                const textArea = document.createElement("textarea");
                textArea.value = text;
                document.body.appendChild(textArea);
                textArea.focus();
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                // Trigger feedback manual jika fallback berhasil
                const originalText = buttonElement.querySelector('.copy-text').innerText;
                buttonElement.classList.add('copied');
                buttonElement.querySelector('.copy-text').innerText = 'Tersalin!';
                setTimeout(() => {
                    buttonElement.classList.remove('copied');
                    buttonElement.querySelector('.copy-text').innerText = originalText;
                }, 2000);
            } catch (fallbackErr) {
                alert('Gagal menyalin resi secara otomatis.');
            }
        });
    }

    // Script untuk animasi loading pada tombol cari
    document.addEventListener('DOMContentLoaded', function() {
        const trackingForm = document.getElementById('tracking-form');
        const trackingButton = document.getElementById('tracking-button');
        const buttonIcon = trackingButton.querySelector('i');
        const buttonText = trackingButton.querySelector('span');

        if (trackingForm) {
            trackingForm.addEventListener('submit', function() {
                trackingButton.disabled = true;
                buttonIcon.className = 'spinner-border spinner-border-sm';
                if(buttonText) buttonText.innerText = ' Mencari...';
            });
        }
    });
</script>
@endpush

