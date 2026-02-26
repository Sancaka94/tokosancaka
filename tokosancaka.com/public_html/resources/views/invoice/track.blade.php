@extends('layouts.app')

@section('content')
{{-- Tambahan CSS Khusus untuk Timeline Tracking Ekspedisi --}}
<style>
    .invoice-wrapper { background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.05); }
    .invoice-header { background: #003399; color: white; padding: 2rem; border-bottom-left-radius: 30px; border-bottom-right-radius: 30px; }

    /* Timeline Styles */
    .tracking-timeline { position: relative; padding-left: 2rem; list-style: none; margin-bottom: 0; }
    .tracking-timeline::before { content: ''; position: absolute; left: 11px; top: 0; bottom: 0; width: 2px; background: #e9ecef; }
    .track-item { position: relative; margin-bottom: 1.5rem; }
    .track-item:last-child { margin-bottom: 0; }
    .track-icon { position: absolute; left: -2rem; top: 0; width: 24px; height: 24px; border-radius: 50%; background: #e9ecef; border: 3px solid #fff; box-shadow: 0 0 0 2px #dee2e6; z-index: 1; display: flex; align-items: center; justify-content: center; color: transparent; font-size: 10px; transition: all 0.3s ease;}

    /* Active State (Sudah Terlewati) */
    .track-item.active .track-icon { background: #0d6efd; box-shadow: 0 0 0 2px #0d6efd; color: white; }
    /* Current State (Sedang Dikerjakan) */
    .track-item.current .track-icon { background: #ffc107; box-shadow: 0 0 0 3px #ffc107; color: white; animation: pulse 2s infinite; }
    /* Success State (Selesai 100%) */
    .track-item.success .track-icon { background: #198754; box-shadow: 0 0 0 2px #198754; color: white; }

    .track-content { padding-left: 1rem; }
    .track-title { font-weight: 700; margin-bottom: 0.2rem; color: #343a40; }
    .track-item:not(.active):not(.current):not(.success) .track-title { color: #adb5bd; }
    .track-desc { font-size: 0.85rem; color: #6c757d; margin-bottom: 0; }

    @keyframes pulse {
        0% { box-shadow: 0 0 0 0 rgba(255, 193, 7, 0.7); }
        70% { box-shadow: 0 0 0 6px rgba(255, 193, 7, 0); }
        100% { box-shadow: 0 0 0 0 rgba(255, 193, 7, 0); }
    }
</style>

<div class="container py-5">

    {{-- Form Pencarian --}}
    <div class="row justify-content-center mb-5">
        <div class="col-md-8 text-center">
            <img src="https://tokosancaka.com/storage/uploads/logo.jpeg" alt="Sancaka Logo" class="mb-3 rounded shadow-sm" style="width: 80px;">
            <h2 class="fw-bold" style="color: #003399;">Lacak Status Pesanan & Invoice</h2>
            <p class="text-muted mb-4">Masukkan nomor invoice Anda untuk melihat rincian tagihan dan melacak progress pengerjaan layaknya resi ekspedisi.</p>

            <form action="{{ route('public.invoice.track') }}" method="GET" class="d-flex mx-auto shadow-sm" style="max-width: 600px;">
                <input type="text" name="invoice_no" class="form-control form-control-lg border-primary border-2 border-end-0 rounded-start" placeholder="Cth: INV-20260226-XXXX" value="{{ request('invoice_no') }}" required style="box-shadow: none;">
                <button type="submit" class="btn btn-primary btn-lg px-4 fw-bold rounded-end">
                    <i class="fa-solid fa-magnifying-glass me-2"></i> Lacak
                </button>
            </form>
        </div>
    </div>

    {{-- Hasil Pencarian --}}
    @if($searched)
        @if($invoice)
            @php
                // Logika Simulasi Status Ekspedisi berdasarkan % Progress
                $pct = $invoice->progress_percent ?? 0;
                $steps = [
                    ['title' => 'Invoice Diterbitkan', 'desc' => 'Sistem telah menerbitkan tagihan. Menunggu konfirmasi.', 'threshold' => 0, 'icon' => 'fa-check'],
                    ['title' => 'Pembayaran Terverifikasi', 'desc' => 'Pembayaran (DP/Lunas) telah diterima oleh sistem.', 'threshold' => 25, 'icon' => 'fa-check'],
                    ['title' => 'Proses Pengerjaan', 'desc' => 'Pesanan Anda sedang dalam proses pengerjaan oleh tim Sancaka.', 'threshold' => 50, 'icon' => 'fa-gears'],
                    ['title' => 'Finishing & Siap Kirim', 'desc' => 'Pesanan dalam tahap akhir pengecekan kualitas / siap dikirim.', 'threshold' => 75, 'icon' => 'fa-truck-fast'],
                    ['title' => 'Selesai & Lunas', 'desc' => 'Pesanan telah selesai 100% dan tagihan telah lunas.', 'threshold' => 100, 'icon' => 'fa-flag-checkered'],
                ];
            @endphp

            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="invoice-wrapper border">

                        {{-- Header Invoice Style --}}
                        <div class="invoice-header d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
                            <div>
                                <h1 class="mb-0 fw-bold tracking-widest">INVOICE</h1>
                                <p class="mb-0 fs-5 opacity-75">NO: {{ $invoice->invoice_no }}</p>
                            </div>
                            <div class="text-md-end mt-3 mt-md-0">
                                <span class="badge bg-light text-dark fs-6 px-3 py-2 rounded-pill shadow-sm">
                                    <i class="fa-solid fa-circle-dot {{ $pct >= 100 ? 'text-success' : 'text-warning' }} me-2"></i>
                                    {{ $invoice->status ?? 'Menunggu Pembayaran' }}
                                </span>
                            </div>
                        </div>

                        <div class="p-4 p-md-5 pt-0">
                            <div class="row">
                                {{-- KIRI: Tampilan Invoice --}}
                                <div class="col-md-7 mb-4 mb-md-0">
                                    <h5 class="fw-bold text-primary mb-3 border-bottom pb-2">Informasi Pelanggan</h5>
                                    <div class="row mb-4">
                                        <div class="col-sm-6 mb-3 mb-sm-0">
                                            <div class="text-muted small text-uppercase fw-bold">Ditagihkan Kepada (Bill To):</div>
                                            <div class="fw-bold fs-5 text-dark">{{ $invoice->customer_name }}</div>
                                            @if($invoice->company_name) <div>{{ $invoice->company_name }}</div> @endif
                                            @if($invoice->alamat) <div>{{ $invoice->alamat }}</div> @endif
                                        </div>
                                        <div class="col-sm-6 text-sm-end">
                                            <div class="text-muted small text-uppercase fw-bold">Tanggal Invoice:</div>
                                            <div class="fw-bold text-dark">{{ date('d F Y', strtotime($invoice->date)) }}</div>
                                        </div>
                                    </div>

                                    {{-- Tabel Rincian --}}
                                    <div class="table-responsive mb-4">
                                        <table class="table table-sm border align-middle">
                                            <thead style="background-color: #f8f9fa;">
                                                <tr>
                                                    <th class="p-2">Deskripsi</th>
                                                    <th class="text-center p-2">Qty</th>
                                                    <th class="text-end p-2">Total</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($invoice->items as $item)
                                                <tr>
                                                    <td class="p-2">{{ $item->description }}</td>
                                                    <td class="text-center p-2">{{ $item->qty }}</td>
                                                    <td class="text-end p-2">Rp {{ number_format($item->total, 0, ',', '.') }}</td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>

                                    {{-- Kalkulasi Total --}}
                                    <div class="d-flex justify-content-end mb-4">
                                        <table class="table table-sm border-0 w-auto">
                                            <tr>
                                                <td class="text-muted border-0 pe-4">Subtotal</td>
                                                <td class="text-end border-0 fw-bold">Rp {{ number_format($invoice->subtotal, 0, ',', '.') }}</td>
                                            </tr>
                                            @if($invoice->discount_amount > 0)
                                            <tr>
                                                <td class="text-danger border-0 pe-4">Diskon</td>
                                                <td class="text-end text-danger border-0 fw-bold">- Rp {{ number_format($invoice->discount_amount, 0, ',', '.') }}</td>
                                            </tr>
                                            @endif
                                            <tr style="background: #f8f9fa;">
                                                <td class="text-dark border-0 fw-bold fs-6 py-2 pe-4 rounded-start">Grand Total</td>
                                                <td class="text-end border-0 text-primary fw-bold fs-6 py-2 rounded-end">Rp {{ number_format($invoice->grand_total, 0, ',', '.') }}</td>
                                            </tr>
                                            @if($invoice->dp > 0)
                                            <tr>
                                                <td class="text-muted border-0 pe-4 pt-3">Telah Dibayar (DP)</td>
                                                <td class="text-end border-0 fw-bold pt-3">Rp {{ number_format($invoice->dp, 0, ',', '.') }}</td>
                                            </tr>
                                            <tr>
                                                <td class="text-danger border-top fw-bold pe-4">Sisa Kekurangan</td>
                                                <td class="text-end border-top text-danger fw-bold">Rp {{ number_format($invoice->sisa_tagihan, 0, ',', '.') }}</td>
                                            </tr>
                                            @endif
                                        </table>
                                    </div>

                                    {{-- Tombol Download --}}
                                    <div class="d-grid mt-4">
                                        <a href="{{ route('public.invoice.download', $invoice->invoice_no) }}" class="btn btn-danger btn-lg shadow-sm">
                                            <i class="fa-solid fa-file-pdf me-2"></i> Download Invoice PDF
                                        </a>
                                    </div>
                                </div>

                                {{-- KANAN: Tracking Ekspedisi --}}
                                <div class="col-md-5 border-start-md ps-md-4 mt-5 mt-md-0">
                                    <h5 class="fw-bold text-primary mb-4 border-bottom pb-2">Tracking Progress</h5>

                                    {{-- Progress Bar Besar --}}
                                    <div class="mb-4">
                                        <div class="d-flex justify-content-between mb-1">
                                            <span class="text-muted fw-bold text-uppercase" style="font-size: 0.8rem;">Penyelesaian</span>
                                            <span class="fw-bold {{ $pct >= 100 ? 'text-success' : 'text-primary' }}">{{ $pct }}%</span>
                                        </div>
                                        <div class="progress" style="height: 12px; border-radius: 10px;">
                                            <div class="progress-bar progress-bar-striped progress-bar-animated {{ $pct >= 100 ? 'bg-success' : 'bg-primary' }}"
                                                 role="progressbar" style="width: {{ $pct }}%"></div>
                                        </div>
                                    </div>

                                    @if($invoice->tracking_note)
                                        <div class="alert alert-info border-0 shadow-sm mt-3 mb-4 rounded-3 text-sm p-3">
                                            <strong class="d-block mb-1 text-dark"><i class="fa-solid fa-bullhorn me-1"></i> Info / Catatan Terkini:</strong>
                                            <span class="text-dark">{{ $invoice->tracking_note }}</span>
                                        </div>
                                    @endif

                                    {{-- Timeline --}}
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
                                                    <h6 class="track-title">{{ $step['title'] }}</h6>
                                                    <p class="track-desc">{{ $step['desc'] }}</p>
                                                </div>
                                            </li>
                                        @endforeach
                                    </ul>

                                    <div class="mt-4 p-3 bg-light rounded text-center">
                                        <small class="text-muted d-block mb-1">Butuh bantuan terkait pesanan?</small>
                                        <a href="https://wa.me/6285745808809" target="_blank" class="btn btn-sm btn-outline-success fw-bold rounded-pill px-3">
                                            <i class="fa-brands fa-whatsapp me-1"></i> Hubungi Admin
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @else
            {{-- Invoice Tidak Ditemukan --}}
            <div class="row justify-content-center">
                <div class="col-md-6 text-center">
                    <div class="card border-0 shadow-sm p-5 rounded-4 bg-white">
                        <i class="fa-solid fa-file-circle-xmark text-danger" style="font-size: 4rem; opacity: 0.5;"></i>
                        <h4 class="fw-bold mt-4">Data Tidak Ditemukan</h4>
                        <p class="text-muted">Maaf, kami tidak dapat menemukan invoice dengan nomor <strong>{{ request('invoice_no') }}</strong>. Mohon periksa kembali nomor yang Anda masukkan.</p>
                        <a href="{{ route('public.invoice.track') }}" class="btn btn-outline-primary mt-3 px-4 rounded-pill">Kembali Mencari</a>
                    </div>
                </div>
            </div>
        @endif
    @endif
</div>
@endsection
