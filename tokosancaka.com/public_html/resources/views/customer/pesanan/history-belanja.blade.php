@extends('layouts.marketplace')

@section('title', 'Detail Pesanan & E-Ticket - ' . $order->invoice_number)

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8 col-md-10">
            
            {{-- Header Status --}}
            <div class="text-center mb-4">
                <h2 class="fw-bold text-dark mb-1">Rincian Pesanan Anda</h2>
                <p class="text-muted">Simpan tautan halaman ini untuk mengakses produk Anda sewaktu-waktu.</p>
            </div>

            <div class="card shadow-sm border-0 rounded-4 overflow-hidden mb-4">
                {{-- Bagian Atas: QR Code & Status --}}
                <div class="card-body p-4 bg-light border-bottom d-flex flex-column flex-md-row align-items-center justify-content-between gap-4">
                    <div class="text-center text-md-start">
                        <span class="badge {{ strtolower($order->status) === 'paid' ? 'bg-success' : 'bg-warning text-dark' }} px-3 py-2 rounded-pill mb-2 fs-6">
                            {{ strtoupper($order->status) }}
                        </span>
                        <h4 class="fw-bold text-primary mb-1">{{ $order->invoice_number }}</h4>
                        <small class="text-muted"><i class="fas fa-calendar-alt me-1"></i> {{ $order->created_at->format('d F Y, H:i') }} WIB</small>
                    </div>
                    
                    {{-- Barcode 2D (QR Code) dari API Publik gratis --}}
                    <div class="text-center bg-white p-2 border rounded-3 shadow-sm">
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data={{ urlencode($order->invoice_number) }}" alt="QR Code Transaksi" class="img-fluid" style="width: 100px; height: 100px;">
                        <div class="mt-1" style="font-size: 10px; color: #6c757d;">ID Transaksi</div>
                    </div>
                </div>

                {{-- Daftar Produk & Info Penjual --}}
                <div class="card-body p-4">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px;">
                            <i class="fas fa-store"></i>
                        </div>
                        <h6 class="fw-bold mb-0">{{ $order->store->name ?? 'Toko Penjual' }}</h6>
                    </div>

                    @foreach($order->items as $item)
                    <div class="d-flex align-items-center p-3 border rounded-3 mb-3 bg-white">
                        {{-- Gambar Produk --}}
                        <div class="flex-shrink-0">
                            @php
                                $imgUrl = $item->product && $item->product->image_url 
                                    ? asset('public/storage/' . str_replace('public/', '', $item->product->image_url)) 
                                    : 'https://placehold.co/80x80?text=No+Pic';
                            @endphp
                            <img src="{{ $imgUrl }}" alt="{{ $item->product->name ?? 'Produk' }}" class="img-fluid rounded border" style="width: 80px; height: 80px; object-fit: cover;">
                        </div>
                        
                        {{-- Detail Produk --}}
                        <div class="flex-grow-1 ms-3">
                            <h6 class="fw-bold text-dark mb-1">{{ $item->product->name ?? 'Produk Digital' }}</h6>
                            <small class="text-muted d-block mb-2">Qty: {{ $item->quantity }} x Rp {{ number_format($item->price, 0, ',', '.') }}</small>
                            <span class="badge bg-info text-dark border"><i class="fas fa-bolt me-1"></i> Pengiriman Instan</span>
                        </div>
                    </div>
                    @endforeach

                    {{-- Total Harga --}}
                    <div class="d-flex justify-content-between align-items-center pt-3 border-top mt-2">
                        <span class="text-muted fw-bold">Total Pembayaran</span>
                        <h4 class="fw-bold text-danger mb-0">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</h4>
                    </div>
                </div>

                {{-- Bagian Eksekusi (E-Ticket / Download Link / SN) --}}
                @php 
                    $resiOrToken = $order->shipping_resi ?? ($order->shipping_reference ?? null);
                    $isUrl = filter_var($resiOrToken, FILTER_VALIDATE_URL);
                    $isPaid = strtolower($order->status) === 'paid' || strtolower($order->status) === 'processing';
                @endphp

                <div class="card-footer bg-white border-top-0 p-4 pt-0">
                    <div class="bg-primary bg-opacity-10 border border-primary border-opacity-25 rounded-3 p-4 text-center">
                        
                        @if($isPaid)
                            <h5 class="fw-bold text-primary mb-2"><i class="fas fa-ticket-alt me-2"></i>Akses Produk Anda</h5>
                            
                            @if(!empty($resiOrToken) && $resiOrToken !== 'NULL' && !str_starts_with($resiOrToken, 'DIGITAL-'))
                                
                                @if($isUrl)
                                    <p class="text-muted small mb-3">Pesanan Anda berupa file atau tautan eksternal. Silakan klik tombol di bawah untuk mengunduh/mengaksesnya.</p>
                                    <a href="{{ $resiOrToken }}" target="_blank" class="btn btn-primary btn-lg w-100 fw-bold shadow-sm">
                                        <i class="fas fa-cloud-download-alt me-2"></i> Akses / Download Sekarang
                                    </a>
                                @else
                                    <p class="text-muted small mb-2">Gunakan Serial Number / Kode Voucher di bawah ini:</p>
                                    <div class="input-group input-group-lg w-100 mx-auto">
                                        <input type="text" class="form-control text-center fw-bold text-dark font-monospace bg-white" value="{{ $resiOrToken }}" id="snToken" readonly>
                                        <button class="btn btn-outline-primary fw-bold" type="button" onclick="copyToken()">
                                            <i class="fas fa-copy"></i> Salin
                                        </button>
                                    </div>
                                @endif

                            @else
                                <div class="spinner-border text-primary spinner-border-sm mb-2" role="status"></div>
                                <p class="text-muted small mb-0">Menunggu penjual memproses dan mengirimkan E-Ticket/File pesanan Anda. Silakan *refresh* halaman ini secara berkala.</p>
                            @endif

                        @else
                            {{-- Jika Belum Dibayar --}}
                            <h5 class="fw-bold text-danger mb-2"><i class="fas fa-exclamation-triangle me-2"></i>Menunggu Pembayaran</h5>
                            <p class="text-muted small mb-3">Silakan selesaikan pembayaran Anda agar e-ticket/produk dapat segera diakses.</p>
                            <a href="{{ route('checkout.invoice', ['invoice' => $order->invoice_number]) }}" class="btn btn-danger btn-lg w-100 fw-bold shadow-sm">
                                <i class="fas fa-wallet me-2"></i> Lanjutkan Pembayaran
                            </a>
                        @endif
                    </div>
                </div>

            </div>
            
            <div class="text-center">
                <a href="{{ url('/') }}" class="btn btn-light border shadow-sm px-4">
                    <i class="fas fa-arrow-left me-2"></i> Kembali ke Beranda
                </a>
            </div>

        </div>
    </div>
</div>

<script>
    function copyToken() {
        var copyText = document.getElementById("snToken");
        copyText.select();
        copyText.setSelectionRange(0, 99999); /* Untuk mobile */
        navigator.clipboard.writeText(copyText.value);
        
        alert("Kode / SN berhasil disalin!");
    }
</script>
@endsection