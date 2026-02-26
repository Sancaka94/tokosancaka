@extends('layouts.app')

@section('content')

{{-- ========================================== --}}
{{-- 1. CSS CUSTOM UNTUK TIMELINE & INVOICE --}}
{{-- ========================================== --}}
<style>
    /* Wrapper & Header Invoice */
    .invoice-wrapper {
        background: #fff;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.05);
    }
    .invoice-header {
        background: #003399;
        color: white;
        padding: 2.5rem 2rem;
        border-bottom-left-radius: 30px;
        border-bottom-right-radius: 30px;
    }

    /* Tombol DOKU Custom */
    .btn-doku {
        background-color: #e52c2a;
        color: white;
        border: none;
        transition: all 0.3s ease;
    }
    .btn-doku:hover {
        background-color: #bd211e;
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(229, 44, 42, 0.3);
    }

    /* Timeline Styles - Ala Resi Ekspedisi */
    .tracking-timeline {
        position: relative;
        padding-left: 2rem;
        list-style: none;
        margin-bottom: 0;
    }
    .tracking-timeline::before {
        content: '';
        position: absolute;
        left: 11px;
        top: 0;
        bottom: 0;
        width: 2px;
        background: #e9ecef;
    }
    .track-item {
        position: relative;
        margin-bottom: 2rem;
    }
    .track-item:last-child {
        margin-bottom: 0;
    }
    .track-icon {
        position: absolute;
        left: -2rem;
        top: 0;
        width: 24px;
        height: 24px;
        border-radius: 50%;
        background: #e9ecef;
        border: 3px solid #fff;
        box-shadow: 0 0 0 2px #dee2e6;
        z-index: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        color: transparent;
        font-size: 10px;
        transition: all 0.3s ease;
    }

    /* Timeline States */
    /* Active State (Tahap yang sudah terlewati) */
    .track-item.active .track-icon {
        background: #0d6efd;
        box-shadow: 0 0 0 2px #0d6efd;
        color: white;
    }
    /* Current State (Tahap yang sedang dikerjakan sekarang) */
    .track-item.current .track-icon {
        background: #ffc107;
        box-shadow: 0 0 0 3px #ffc107;
        color: white;
        animation: pulse 2s infinite;
    }
    /* Success State (Selesai 100%) */
    .track-item.success .track-icon {
        background: #198754;
        box-shadow: 0 0 0 2px #198754;
        color: white;
    }

    /* Teks Timeline */
    .track-content { padding-left: 1rem; }
    .track-title { font-weight: 700; margin-bottom: 0.2rem; color: #343a40; }
    .track-item:not(.active):not(.current):not(.success) .track-title { color: #adb5bd; }
    .track-desc { font-size: 0.85rem; color: #6c757d; margin-bottom: 0; line-height: 1.5; }

    /* Animasi Berkedip untuk Status Saat Ini */
    @keyframes pulse {
        0% { box-shadow: 0 0 0 0 rgba(255, 193, 7, 0.7); }
        70% { box-shadow: 0 0 0 6px rgba(255, 193, 7, 0); }
        100% { box-shadow: 0 0 0 0 rgba(255, 193, 7, 0); }
    }
</style>

<div class="container py-5">

    {{-- ========================================== --}}
    {{-- 2. ALERT MESSAGES (SUCCESS / ERROR)        --}}
    {{-- ========================================== --}}
    @if(session('error'))
    <div class="row justify-content-center mb-4">
        <div class="col-lg-10">
            <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0 d-flex align-items-center p-4" role="alert">
                <i class="fa-solid fa-triangle-exclamation fs-3 me-3 text-danger"></i>
                <div>
                    <h5 class="fw-bold mb-1">Gagal Memproses Transaksi</h5>
                    <span class="mb-0">{{ session('error') }}</span>
                </div>
                <button type="button" class="btn-close m-2" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    </div>
    @endif

    {{-- ========================================== --}}
    {{-- 3. FORM PENCARIAN INVOICE                  --}}
    {{-- ========================================== --}}
    <div class="row justify-content-center mb-5">
        <div class="col-md-8 text-center">
            <img src="https://tokosancaka.com/storage/uploads/logo.jpeg" alt="Sancaka Logo" class="mb-3 rounded shadow-sm" style="width: 80px;">
            <h2 class="fw-bold" style="color: #003399;">Lacak Status Pesanan & Invoice</h2>
            <p class="text-muted mb-4 px-3">Masukkan nomor invoice Anda untuk melihat rincian tagihan, melacak progress pengerjaan, dan melakukan pembayaran online via DOKU.</p>

            <form action="{{ route('public.invoice.track') }}" method="GET" class="d-flex mx-auto shadow-sm" style="max-width: 600px;">
                <input type="text" name="invoice_no" class="form-control form-control-lg border-primary border-2 border-end-0 rounded-start" placeholder="Cth: INV-20260226-XXXX" value="{{ request('invoice_no') }}" required style="box-shadow: none;">
                <button type="submit" class="btn btn-primary btn-lg px-4 fw-bold rounded-end">
                    <i class="fa-solid fa-magnifying-glass me-2"></i> Lacak
                </button>
            </form>
        </div>
    </div>

    {{-- ========================================== --}}
    {{-- 4. AREA HASIL PENCARIAN                    --}}
    {{-- ========================================== --}}
    @if($searched)
        @if($invoice)

            {{-- Konfigurasi Progress Bar & Tahapan Ekspedisi --}}
            @php
                $pct = $invoice->progress_percent ?? 0;
                $steps = [
                    ['title' => 'Invoice Diterbitkan', 'desc' => 'Sistem telah menerbitkan tagihan. Menunggu pembayaran dari pelanggan.', 'threshold' => 0, 'icon' => 'fa-file-invoice'],
                    ['title' => 'Pembayaran Terverifikasi', 'desc' => 'Pembayaran Anda telah diterima dan diverifikasi oleh sistem kami.', 'threshold' => 25, 'icon' => 'fa-check-double'],
                    ['title' => 'Proses Pengerjaan', 'desc' => 'Pesanan Anda sedang dalam proses pengerjaan oleh tim Sancaka Express.', 'threshold' => 50, 'icon' => 'fa-gears'],
                    ['title' => 'Finishing & Siap Kirim', 'desc' => 'Pesanan memasuki tahap akhir, pengecekan kualitas, dan siap untuk dikirim.', 'threshold' => 75, 'icon' => 'fa-box-open'],
                    ['title' => 'Selesai & Lunas', 'desc' => 'Pesanan telah selesai 100% dan seluruh tagihan telah dilunasi.', 'threshold' => 100, 'icon' => 'fa-flag-checkered'],
                ];
            @endphp

            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="invoice-wrapper border">

                        {{-- 4.1. HEADER INVOICE --}}
                        <div class="invoice-header d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
                            <div>
                                <h1 class="mb-0 fw-bold" style="letter-spacing: 2px;">INVOICE</h1>
                                <p class="mb-0 fs-5 opacity-75">NO: {{ $invoice->invoice_no }}</p>
                            </div>
                            <div class="text-md-end mt-3 mt-md-0">

                                {{-- Lunas / Belum Lunas Badge --}}
                                @if($invoice->sisa_tagihan > 0)
                                    <span class="badge bg-danger fs-5 px-4 py-2 rounded-1 shadow-sm d-block mb-2 text-uppercase tracking-wider">UNPAID</span>
                                @else
                                    <span class="badge bg-success fs-5 px-4 py-2 rounded-1 shadow-sm d-block mb-2 text-uppercase tracking-wider">PAID</span>
                                @endif

                                {{-- Status Tracking Badge --}}
                                <span class="badge bg-light text-dark fs-6 px-3 py-2 rounded-pill shadow-sm">
                                    <i class="fa-solid fa-circle-dot {{ $pct >= 100 ? 'text-success' : 'text-warning' }} me-2"></i>
                                    {{ $invoice->status ?? 'Menunggu Pembayaran' }}
                                </span>
                            </div>
                        </div>

                        <div class="p-4 p-md-5 pt-0">
                            <div class="row">

                                {{-- ======================================= --}}
                                {{-- KOLOM KIRI: DATA INVOICE & PEMBAYARAN --}}
                                {{-- ======================================= --}}
                                <div class="col-md-7 mb-4 mb-md-0 pe-md-4">
                                    <h5 class="fw-bold text-primary mb-3 border-bottom pb-2">Informasi Tagihan</h5>

                                    {{-- Data Pelanggan & Tanggal --}}
                                    <div class="row mb-4">
                                        <div class="col-sm-6 mb-3 mb-sm-0">
                                            <div class="text-muted small text-uppercase fw-bold mb-1">Ditagihkan Kepada (Bill To):</div>
                                            <div class="fw-bold fs-5 text-dark">{{ $invoice->customer_name }}</div>
                                            @if($invoice->company_name) <div class="text-secondary">{{ $invoice->company_name }}</div> @endif
                                            @if($invoice->alamat) <div class="text-secondary mt-1" style="font-size: 0.9rem;">{{ $invoice->alamat }}</div> @endif
                                        </div>
                                        <div class="col-sm-6 text-sm-end">
                                            <div class="text-muted small text-uppercase fw-bold mb-1">Tanggal Invoice:</div>
                                            <div class="fw-bold text-dark fs-5">{{ date('d F Y', strtotime($invoice->date)) }}</div>
                                        </div>
                                    </div>

                                    {{-- Tabel Rincian Item --}}
                                    <div class="table-responsive mb-4 border rounded">
                                        <table class="table table-sm table-borderless align-middle mb-0">
                                            <thead style="background-color: #f8f9fa; border-bottom: 2px solid #dee2e6;">
                                                <tr>
                                                    <th class="p-3 text-secondary">Deskripsi Item</th>
                                                    <th class="text-center p-3 text-secondary">Qty</th>
                                                    <th class="text-end p-3 text-secondary">Total</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($invoice->items as $item)
                                                <tr class="border-bottom">
                                                    <td class="p-3 fw-medium">{{ $item->description }}</td>
                                                    <td class="text-center p-3 text-muted">{{ $item->qty }}</td>
                                                    <td class="text-end p-3 fw-bold">Rp {{ number_format($item->total, 0, ',', '.') }}</td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>

                                    {{-- Kalkulasi Total Harga --}}
                                    <div class="d-flex justify-content-end mb-5">
                                        <table class="table table-sm border-0 w-auto">
                                            <tr>
                                                <td class="text-muted border-0 pe-4 py-2">Subtotal</td>
                                                <td class="text-end border-0 fw-bold py-2">Rp {{ number_format($invoice->subtotal, 0, ',', '.') }}</td>
                                            </tr>
                                            @if($invoice->discount_amount > 0)
                                            <tr>
                                                <td class="text-danger border-0 pe-4 py-2">Diskon</td>
                                                <td class="text-end text-danger border-0 fw-bold py-2">- Rp {{ number_format($invoice->discount_amount, 0, ',', '.') }}</td>
                                            </tr>
                                            @endif
                                            <tr style="background: #f8f9fa;">
                                                <td class="text-dark border-0 fw-bold fs-6 py-3 pe-4 ps-3 rounded-start">Grand Total</td>
                                                <td class="text-end border-0 text-primary fw-bold fs-5 py-3 pe-3 rounded-end">Rp {{ number_format($invoice->grand_total, 0, ',', '.') }}</td>
                                            </tr>
                                            @if($invoice->dp > 0)
                                            <tr>
                                                <td class="text-muted border-0 pe-4 pt-3">Telah Dibayar (DP)</td>
                                                <td class="text-end border-0 fw-bold pt-3 text-success">- Rp {{ number_format($invoice->dp, 0, ',', '.') }}</td>
                                            </tr>
                                            <tr>
                                                <td class="text-danger border-top fw-bold pe-4 pt-2 mt-2">Sisa Kekurangan</td>
                                                <td class="text-end border-top text-danger fw-bold fs-5 pt-2 mt-2">Rp {{ number_format($invoice->sisa_tagihan, 0, ',', '.') }}</td>
                                            </tr>
                                            @endif
                                        </table>
                                    </div>

                                    {{-- ======================================= --}}
                                    {{-- BLOK AKSI LOGIKA PEMBAYARAN (AUTO)    --}}
                                    {{-- ======================================= --}}
                                    <div class="mt-4 border-top pt-4">
                                        @if($invoice->sisa_tagihan > 0)

                                            @php $statusTagihan = $invoice->status ?? 'Invoice Diterbitkan'; @endphp

                                            {{-- Skenario 1: Baru Terbit (Bayar DP atau Lunas) --}}
                                            @if($statusTagihan == 'Invoice Diterbitkan')
                                                <div class="alert alert-warning border border-warning shadow-sm text-center mb-4 p-4 rounded-3">
                                                    @if($invoice->dp > 0)
                                                        <span class="d-block mb-1 text-dark fw-medium">Menunggu Pembayaran Uang Muka (DP):</span>
                                                        <strong class="fs-2 text-dark d-block mb-2">Rp {{ number_format($invoice->dp, 0, ',', '.') }}</strong>
                                                        <small class="text-muted">Pembayaran DP diperlukan untuk mulai memproses pesanan.</small>
                                                    @else
                                                        <span class="d-block mb-1 text-dark fw-medium">Menunggu Pembayaran Penuh:</span>
                                                        <strong class="fs-2 text-dark d-block mb-2">Rp {{ number_format($invoice->grand_total, 0, ',', '.') }}</strong>
                                                        <small class="text-muted">Silakan selesaikan pembayaran agar pesanan dapat diproses.</small>
                                                    @endif
                                                </div>

                                                <form action="{{ route('public.invoice.pay', $invoice->id) }}" method="POST" class="d-grid mb-3">
                                                    @csrf
                                                    <button type="submit" class="btn btn-doku btn-lg shadow-sm fw-bold py-3 fs-5 rounded-3">
                                                        <i class="fa-solid fa-credit-card me-2"></i>
                                                        Bayar {{ $invoice->dp > 0 ? 'DP' : 'Lunas' }} via DOKU
                                                    </button>
                                                </form>

                                            {{-- Skenario 2: Siap Kirim (Bayar Pelunasan Sisa) --}}
                                            @elseif($statusTagihan == 'Finishing & Siap Kirim')
                                                <div class="alert alert-info border border-info shadow-sm text-center mb-4 p-4 rounded-3">
                                                    <span class="d-block mb-1 text-dark fw-medium">Pesanan Siap! Menunggu Pelunasan:</span>
                                                    <strong class="fs-2 text-primary d-block mb-2">Rp {{ number_format($invoice->sisa_tagihan, 0, ',', '.') }}</strong>
                                                    <small class="text-muted">Pesanan akan diserahkan/dikirim setelah pelunasan dikonfirmasi.</small>
                                                </div>

                                                <form action="{{ route('public.invoice.pay', $invoice->id) }}" method="POST" class="d-grid mb-3">
                                                    @csrf
                                                    <button type="submit" class="btn btn-doku btn-lg shadow-sm fw-bold py-3 fs-5 rounded-3">
                                                        <i class="fa-solid fa-credit-card me-2"></i>
                                                        Bayar Pelunasan via DOKU
                                                    </button>
                                                </form>

                                            {{-- Skenario 3: Sedang Diproses (Tombol Bayar Disembunyikan) --}}
                                            @else
                                                <div class="alert alert-primary text-center fw-bold mb-4 p-4 border border-primary shadow-sm rounded-3">
                                                    <i class="fa-solid fa-gears fs-1 d-block mb-3 text-primary"></i>
                                                    Pembayaran Diterima.<br>Pesanan Anda sedang dalam proses pengerjaan.
                                                </div>
                                            @endif

                                        {{-- Skenario 4: Sisa Tagihan 0 (LUNAS) --}}
                                        @else
                                            <div class="alert alert-success text-center fw-bold mb-4 p-4 border border-success shadow-sm rounded-3">
                                                <i class="fa-solid fa-circle-check fs-1 d-block mb-3 text-success"></i>
                                                TERIMA KASIH<br>Seluruh Tagihan Invoice Telah Lunas
                                            </div>
                                        @endif

                                        {{-- Tombol Download PDF --}}
                                        <a href="{{ route('public.invoice.download', $invoice->invoice_no) }}" class="btn btn-outline-danger btn-lg shadow-sm w-100 py-3 fw-bold rounded-3">
                                            <i class="fa-solid fa-file-pdf me-2"></i> Download / Print Invoice PDF
                                        </a>
                                    </div>
                                </div>


                                {{-- ======================================= --}}
                                {{-- KOLOM KANAN: TRACKING EKSPEDISI       --}}
                                {{-- ======================================= --}}
                                <div class="col-md-5 border-start-md ps-md-4 mt-5 mt-md-0">
                                    <h5 class="fw-bold text-primary mb-4 border-bottom pb-2">Tracking Progress</h5>

                                    {{-- Progress Bar Persentase --}}
                                    <div class="mb-4">
                                        <div class="d-flex justify-content-between mb-2">
                                            <span class="text-muted fw-bold text-uppercase tracking-wider" style="font-size: 0.8rem;">Tingkat Penyelesaian</span>
                                            <span class="fw-bold {{ $pct >= 100 ? 'text-success' : 'text-primary' }} fs-5">{{ $pct }}%</span>
                                        </div>
                                        <div class="progress shadow-sm" style="height: 14px; border-radius: 10px; background-color: #e9ecef;">
                                            <div class="progress-bar progress-bar-striped progress-bar-animated {{ $pct >= 100 ? 'bg-success' : 'bg-primary' }}"
                                                 role="progressbar" style="width: {{ $pct }}%"></div>
                                        </div>
                                    </div>

                                    {{-- Catatan dari Admin (Jika ada) --}}
                                    @if($invoice->tracking_note)
                                        <div class="alert alert-info border-0 shadow-sm mt-4 mb-4 rounded-3 p-4 bg-light text-dark">
                                            <strong class="d-flex align-items-center mb-2 text-primary">
                                                <i class="fa-solid fa-bullhorn fs-5 me-2"></i>
                                                Update / Catatan Terkini:
                                            </strong>
                                            <p class="mb-0 ms-4" style="line-height: 1.6;">{{ $invoice->tracking_note }}</p>
                                        </div>
                                    @endif

                                    {{-- Timeline Vertical --}}
                                    <div class="mt-5">
                                        <ul class="tracking-timeline">
                                            @foreach($steps as $index => $step)
                                                @php
                                                    $isPassed = $pct > $step['threshold'];
                                                    $isCurrent = $pct == $step['threshold'] || ($pct > $step['threshold'] && (!isset($steps[$index+1]) || $pct < $steps[$index+1]['threshold']));
                                                    $isSuccess = $pct >= 100 && $step['threshold'] == 100;

                                                    $stateClass = '';
                                                    if($isSuccess) $stateClass = 'success';
                                                    elseif($isCurrent) $stateClass = 'current';
                                                    elseif($isPassed) $stateClass = 'active';
                                                @endphp
                                                <li class="track-item {{ $stateClass }}">
                                                    <div class="track-icon">
                                                        <i class="fa-solid {{ $step['icon'] }}"></i>
                                                    </div>
                                                    <div class="track-content">
                                                        <h6 class="track-title fs-6">{{ $step['title'] }}</h6>
                                                        <p class="track-desc mt-1">{{ $step['desc'] }}</p>
                                                    </div>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>

                                    {{-- Kontak Bantuan --}}
                                    <div class="mt-5 p-4 bg-light border rounded-3 text-center shadow-sm">
                                        <div class="mb-3">
                                            <i class="fa-solid fa-headset fs-2 text-secondary"></i>
                                        </div>
                                        <h6 class="fw-bold text-dark">Butuh Bantuan?</h6>
                                        <p class="text-muted small mb-3">Jika Anda memiliki pertanyaan terkait pesanan atau tagihan, silakan hubungi tim kami.</p>
                                        <a href="https://wa.me/6285745808809" target="_blank" class="btn btn-outline-success fw-bold rounded-pill px-4 py-2 w-100">
                                            <i class="fa-brands fa-whatsapp fs-5 me-2 align-middle"></i> Chat Admin via WA
                                        </a>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        @else
            {{-- ========================================== --}}
            {{-- 5. AREA JIKA INVOICE TIDAK DITEMUKAN       --}}
            {{-- ========================================== --}}
            <div class="row justify-content-center">
                <div class="col-md-6 text-center">
                    <div class="card border-0 shadow-lg p-5 rounded-4 bg-white mt-4">
                        <div class="mb-4">
                            <i class="fa-solid fa-file-circle-xmark text-danger" style="font-size: 5rem; opacity: 0.8;"></i>
                        </div>
                        <h3 class="fw-bold text-dark mb-3">Invoice Tidak Ditemukan</h3>
                        <p class="text-muted mb-4 fs-6 px-3" style="line-height: 1.6;">
                            Maaf, kami tidak dapat menemukan invoice dengan nomor <strong>"{{ request('invoice_no') }}"</strong> di dalam sistem kami. Mohon periksa kembali penulisan nomor invoice Anda.
                        </p>
                        <a href="{{ route('public.invoice.track') }}" class="btn btn-primary btn-lg px-5 rounded-pill shadow-sm fw-bold">
                            <i class="fa-solid fa-rotate-left me-2"></i> Coba Lagi
                        </a>
                    </div>
                </div>
            </div>
        @endif
    @endif
</div>

@endsection
