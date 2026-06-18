@extends('layouts.marketplace')

@section('title', 'Detail Pesanan & E-Ticket - ' . $order->invoice_number)

@section('content')
<div class="bg-gray-50 min-h-screen py-10">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        
        {{-- Header Status --}}
        <div class="text-center mb-8">
            <h1 class="text-2xl md:text-3xl font-extrabold text-gray-900 mb-2">Rincian Pesanan Anda</h1>
            <p class="text-sm text-gray-500">Simpan tautan halaman ini untuk mengakses produk Anda sewaktu-waktu.</p>
        </div>

        <div class="bg-white shadow-sm border border-gray-200 rounded-2xl overflow-hidden mb-6">
            
            {{-- Bagian Atas: QR Code & Status --}}
            <div class="p-6 md:p-8 bg-gray-50 border-b border-gray-100 flex flex-col-reverse md:flex-row items-center justify-between gap-6">
                <div class="text-center md:text-left w-full md:w-auto">
                    @php 
                        $status = strtolower($order->status);
                        $badgeClass = match($status) {
                            'paid', 'completed', 'success', 'lunas', 'selesai' => 'bg-green-100 text-green-800 border-green-200',
                            'pending', 'unpaid', 'menunggu_pembayaran' => 'bg-yellow-100 text-yellow-800 border-yellow-200',
                            'processing', 'diproses' => 'bg-blue-100 text-blue-800 border-blue-200',
                            default => 'bg-gray-100 text-gray-800 border-gray-200'
                        };
                    @endphp
                    <span class="inline-block px-4 py-1.5 rounded-full text-xs font-bold tracking-wider border {{ $badgeClass }} mb-3">
                        {{ strtoupper($status) }}
                    </span>
                    <h2 class="text-2xl font-black text-gray-900 mb-1 tracking-tight">{{ $order->invoice_number }}</h2>
                    <p class="text-sm text-gray-500 font-medium"><i class="fas fa-calendar-alt mr-1.5"></i> {{ $order->created_at->format('d F Y, H:i') }} WIB</p>
                </div>
                
                {{-- Barcode 2D (QR Code) --}}
                <div class="bg-white p-3 border border-gray-200 rounded-xl shadow-sm flex flex-col items-center justify-center flex-shrink-0">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data={{ urlencode($order->invoice_number) }}" alt="QR Code" class="w-24 h-24 object-contain">
                    <span class="text-[9px] font-bold text-gray-400 mt-2 uppercase tracking-widest">ID Transaksi</span>
                </div>
            </div>

            {{-- Daftar Produk & Info Penjual --}}
            <div class="p-6 md:p-8">
                <div class="flex items-center gap-3 mb-6 pb-4 border-b border-gray-100">
                    <div class="w-10 h-10 bg-red-50 text-red-600 rounded-full flex items-center justify-center border border-red-100 flex-shrink-0">
                        <i class="fas fa-store"></i>
                    </div>
                    <h3 class="font-bold text-gray-800 text-base">{{ $order->store->name ?? 'Toko Penjual' }}</h3>
                </div>

                <div class="space-y-4">
                    @foreach($order->items as $item)
                    <div class="flex items-start gap-4 p-4 border border-gray-100 rounded-xl bg-white hover:bg-gray-50 hover:border-gray-200 transition duration-200 group">
                        {{-- Gambar Produk --}}
                        <div class="w-20 h-20 flex-shrink-0 bg-gray-100 rounded-lg overflow-hidden border border-gray-200">
                            @php
                                $imgUrl = $item->product && $item->product->image_url 
                                    ? asset('public/storage/' . str_replace('public/', '', $item->product->image_url)) 
                                    : 'https://placehold.co/100x100?text=No+Pic';
                            @endphp
                            <img src="{{ $imgUrl }}" alt="{{ $item->product->name ?? 'Produk' }}" class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                        </div>
                        
                        {{-- Detail Produk --}}
                        <div class="flex-grow pt-1">
                            <h4 class="font-bold text-gray-900 text-sm md:text-base leading-tight mb-1">{{ $item->product->name ?? 'Produk Digital' }}</h4>
                            <p class="text-sm text-gray-600 font-medium mb-2">{{ $item->quantity }} x Rp {{ number_format($item->price, 0, ',', '.') }}</p>
                            <span class="inline-flex items-center px-2 py-1 rounded bg-blue-50 border border-blue-100 text-blue-600 text-[10px] font-bold">
                                <i class="fas fa-bolt mr-1"></i> Pengiriman Instan
                            </span>
                        </div>
                    </div>
                    @endforeach
                </div>

                {{-- Total Harga --}}
                <div class="flex justify-between items-center pt-6 mt-6 border-t border-dashed border-gray-200">
                    <span class="text-gray-500 font-bold text-sm uppercase tracking-wider">Total Pembayaran</span>
                    <span class="text-xl md:text-2xl font-black text-red-600">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</span>
                </div>
            </div>

            {{-- Bagian Eksekusi (E-Ticket / Download Link / SN) --}}
            @php 
                $resiOrToken = $order->shipping_resi ?? ($order->shipping_reference ?? null);
                $isUrl = filter_var($resiOrToken, FILTER_VALIDATE_URL);
                $isPaid = in_array(strtolower($order->status), ['paid', 'processing', 'completed', 'selesai', 'lunas']);
            @endphp

            <div class="p-6 md:p-8 pt-0 bg-white">
                <div class="bg-blue-50 border border-blue-200 rounded-2xl p-6 md:p-8 text-center shadow-inner">
                    
                    @if($isPaid)
                        <h4 class="font-extrabold text-blue-800 mb-2 text-lg flex items-center justify-center gap-2">
                            <i class="fas fa-ticket-alt"></i> Akses Produk Anda
                        </h4>
                        
                        @if(!empty($resiOrToken) && $resiOrToken !== 'NULL' && !str_starts_with($resiOrToken, 'DIGITAL-'))
                            
                            @if($isUrl)
                                <p class="text-sm text-blue-600 mb-5">Pesanan Anda berupa file atau tautan eksternal. Silakan klik tombol di bawah untuk mengaksesnya.</p>
                                <a href="{{ $resiOrToken }}" target="_blank" class="inline-flex items-center justify-center w-full md:w-auto px-8 py-3.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl shadow-md shadow-blue-200 transition transform hover:-translate-y-0.5">
                                    <i class="fas fa-cloud-download-alt mr-2 text-lg"></i> Akses / Download Sekarang
                                </a>
                            @else
                                <p class="text-sm text-blue-600 mb-4">Gunakan Kode Voucher / Serial Number di bawah ini:</p>
                                <div class="flex items-center w-full max-w-md mx-auto shadow-sm rounded-xl overflow-hidden border-2 border-blue-300 bg-white">
                                    <input type="text" class="flex-1 text-center font-mono font-bold text-gray-800 bg-transparent py-3 px-4 focus:outline-none text-sm md:text-base" value="{{ $resiOrToken }}" id="snToken" readonly>
                                    <button class="bg-blue-100 hover:bg-blue-200 text-blue-700 font-bold py-3 px-6 transition duration-200 flex items-center gap-2 border-l border-blue-200" type="button" onclick="copyToken()">
                                        <i class="fas fa-copy"></i> <span class="hidden md:inline">Salin</span>
                                    </button>
                                </div>
                                <p class="text-[10px] text-blue-400 mt-3">* Jangan berikan kode ini kepada siapapun.</p>
                            @endif

                        @else
                            <div class="flex justify-center mb-3">
                                <i class="fas fa-circle-notch fa-spin text-blue-500 text-3xl"></i>
                            </div>
                            <p class="text-sm text-blue-600 mb-0 font-medium">Menunggu penjual memproses dan mengirimkan produk Anda.</p>
                            <p class="text-xs text-blue-400 mt-1">Silakan <a href="javascript:window.location.reload(true)" class="underline font-bold hover:text-blue-700">refresh</a> halaman ini secara berkala.</p>
                        @endif

                    @else
                        {{-- Jika Belum Dibayar --}}
                        <h4 class="font-extrabold text-red-600 mb-2 text-lg flex items-center justify-center gap-2">
                            <i class="fas fa-exclamation-triangle"></i> Menunggu Pembayaran
                        </h4>
                        <p class="text-sm text-red-500 mb-5">Silakan selesaikan pembayaran Anda agar pesanan dapat segera diakses.</p>
                        <a href="{{ route('checkout.invoice', ['invoice' => $order->invoice_number]) }}" class="inline-flex items-center justify-center w-full md:w-auto px-8 py-3.5 bg-red-600 hover:bg-red-700 text-white font-bold rounded-xl shadow-md shadow-red-200 transition transform hover:-translate-y-0.5">
                            <i class="fas fa-wallet mr-2"></i> Lanjutkan Pembayaran
                        </a>
                    @endif
                </div>
            </div>

        </div>
        
        <div class="text-center mt-8">
            <a href="{{ url('/') }}" class="inline-flex items-center px-6 py-3 bg-white text-gray-600 font-bold rounded-xl shadow-sm border border-gray-200 hover:bg-gray-50 transition">
                <i class="fas fa-arrow-left mr-2"></i> Kembali ke Beranda
            </a>
        </div>

    </div>
</div>

<script>
    function copyToken() {
        var copyText = document.getElementById("snToken");
        copyText.select();
        copyText.setSelectionRange(0, 99999); /* Untuk mobile */
        navigator.clipboard.writeText(copyText.value);
        
        // SweetAlert jika ada, kalau tidak fallback ke JS alert biasa
        if(typeof Swal !== 'undefined') {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Kode / SN berhasil disalin!',
                showConfirmButton: false,
                timer: 2000
            });
        } else {
            alert("Kode / SN berhasil disalin!");
        }
    }
</script>
@endsection