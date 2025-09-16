@extends('layouts.marketplace')

@section('title', 'Invoice Pesanan - ' . ($order->invoice_number ?? ''))

@section('content')
    {{-- Main container with a subtle gradient background --}}
    <div class="bg-gradient-to-br from-gray-50 to-gray-200 min-h-screen flex items-center justify-center p-4 sm:p-6 font-sans">

        {{-- Invoice Card --}}
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-4xl overflow-hidden">
            
            {{-- Header Section with Branding --}}
            <div class="p-6 border-b border-gray-200">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    {{-- Company Branding --}}
                    <div class="flex items-center">
                        <img src="https://tokosancaka.biz.id/storage/uploads/logo.jpeg" alt="Logo CV. Sancaka Karya Hutama" class="h-16 w-16 mr-4 flex-shrink-0 rounded-lg object-cover" onerror="this.style.display='none';">
                        <div>
                            <h2 class="text-lg font-bold text-gray-800">CV. Sancaka Karya Hutama</h2>
                            <div class="flex items-start text-xs text-gray-500 mt-1">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2 mt-0.5 flex-shrink-0" fill="none" viewBox="0-0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <span class="max-w-xs">JL.DR.WAHIDIN NO.18A RT.22 RW.05 KEL.KETANGGI KEC.NGAWI KAB.NGAWI JAWA TIMUR 63211</span>
                            </div>
                            <div class="flex items-center text-xs text-gray-500 mt-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2 flex-shrink-0" fill="none" viewBox="0-0 24 24" stroke="currentColor">
                                   <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                </svg>
                                <span>085745808809 / 08819435180</span>
                            </div>
                        </div>
                    </div>
                    {{-- Invoice Details --}}
                    <div class="text-left sm:text-right w-full sm:w-auto flex-shrink-0">
                        <h1 class="text-2xl font-bold text-blue-600">INVOICE</h1>
                        <p class="font-semibold text-gray-700">#{{ $order->invoice_number }}</p>
                        {{-- Menggunakan kolom timestamp standar Laravel 'created_at' --}}
                        <p class="text-sm text-gray-500">Tanggal: {{ $order->created_at->format('d/m/Y') }}</p>
                    </div>
                </div>
            </div>

            {{-- Main Content Section with Horizontal Layout --}}
            <div class="flex flex-col md:flex-row">
                
                {{-- Left Column: Order Details --}}
                <div class="w-full md:w-1/2 p-8">
                    <div class="mb-6">
                        <h2 class="text-base font-semibold text-gray-700 mb-3">Detail Pengiriman</h2>
                        <div class="text-sm text-gray-600 space-y-1">
                            {{-- Mengambil data dari relasi user/pengguna --}}
                            <p class="font-medium text-gray-800">{{ $order->user->nama_lengkap ?? 'Nama Pelanggan' }}</p>
                            <p>{{ $order->user->no_wa ?? '' }}</p>
                            <p>{{ $order->user->address_detail ?? 'Alamat tidak tersedia' }}</p>
                        </div>
                    </div>

                    {{-- === BAGIAN DETAIL PRODUK (DIPERBAIKI) === --}}
                    <h3 class="text-base font-semibold text-gray-700 mb-2">Ringkasan Pesanan</h3>
                    {{-- Menggunakan relasi $order->items untuk mengambil detail produk --}}
                    <ul role="list" class="divide-y divide-gray-200 border-b border-t border-gray-200">
                        @foreach($order->items as $item)
                        <li class="flex py-4 items-center">
                            <div class="h-16 w-16 flex-shrink-0 overflow-hidden rounded-md">
                                {{-- DITAMBAHKAN: Pengecekan jika produk ada --}}
                                @if($item->product->image_url)
                                    <img src="{{ asset('storage/'.$item->product->image_url) }}" alt="{{ $item->product->name }}" class="h-full w-full object-cover object-center">
                                @else
                                    {{-- Tampilan fallback jika produk tidak ditemukan --}}
                                    <img src="https://placehold.co/64x64/EFEFEF/333333?text=?" alt="Produk tidak ditemukan" class="h-full w-full object-cover object-center">
                                @endif
                            </div>
                            <div class="ml-4 flex flex-1 flex-col text-sm">
                                {{-- DITAMBAHKAN: Pengecekan jika produk ada --}}
                                @if($item->product)
                                    <h4 class="font-medium text-gray-800">{{ $item->product->name }}</h4>
                                @else
                                    <h4 class="font-medium text-red-500">Produk tidak ditemukan</h4>
                                @endif
                                <p class="text-gray-500">Qty: {{ $item->quantity }}</p>
                                <p class="text-gray-500 mt-auto">@ Rp {{ number_format($item->price, 0, ',', '.') }}</p>
                            </div>
                            <div class="text-right font-medium text-gray-800 text-sm">
                                <p>Rp {{ number_format($item->price * $item->quantity, 0, ',', '.') }}</p>
                            </div>
                        </li>
                        @endforeach
                    </ul>
                    
                    {{-- === BAGIAN RINCIAN BIAYA (DIPERBAIKI) === --}}
                    <div class="mt-6 space-y-4">
                        @php
                            // Menghitung subtotal secara dinamis dari item
                            $subtotal = $order->items->sum(function($item) {
                                return $item->price * $item->quantity;
                            });
                        @endphp
                        <div class="flex justify-between text-sm text-gray-600">
                            <span>Subtotal</span>
                            <span>Rp {{ number_format($subtotal, 0, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between text-sm text-gray-600">
                            <span>Ongkos Kirim</span>
                            <span>Rp {{ number_format($order->shipping_cost, 0, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between text-lg font-bold text-gray-800 border-t border-gray-200 pt-4">
                            <span>Total Pembayaran:</span>
                            <span class="text-blue-600">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</span>
                        </div>
                    </div>
                    
                    <div class="mt-8 text-center">
                        @php
    $status = strtolower($order->status);
    $badgeClass = match($status) {
        'pending'    => 'bg-yellow-100 text-yellow-800 border border-yellow-300',
        'processing' => 'bg-blue-100 text-blue-800 border border-blue-300',
        'shipment'   => 'bg-purple-100 text-purple-800 border border-purple-300',
        'completed'  => 'bg-green-100 text-green-800 border border-green-300',
        'canceled'   => 'bg-red-100 text-red-800 border border-red-300',
        'returned'   => 'bg-orange-100 text-orange-800 border border-orange-300',
        default      => 'bg-gray-100 text-gray-800 border border-gray-300'
    };
@endphp

<p class="text-gray-600">Status: 
    <span class="px-4 py-1.5 rounded-full text-sm font-semibold {{ $badgeClass }}">
        {{ ucfirst($status) }}
    </span>
</p>

                    </div>
                </div>

                {{-- Right Column: Payment Instructions --}}
                <div class="w-full md:w-1/2 p-8 bg-gray-50 md:border-l border-t md:border-t-0 border-gray-200">
                     {{-- === KODE TRIPAY DITAMBAHKAN DI SINI === --}}
                     @if($status === 'pending')
                        <div class="h-full flex flex-col justify-center">
                            <h2 class="text-lg font-semibold text-gray-800 mb-4 text-center flex items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2 text-gray-400" fill="none" viewBox="0-0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                </svg>
                                Instruksi Pembayaran
                            </h2>

                            @php
                                $method = strtoupper($order->payment_method);
                                $url    = $order->payment_url;
                                $virtualAccounts = [
                                    'PERMATAVA','BNIVA','BRIVA','MANDIRIVA','BCAVA','MUAMALATVA',
                                    'CIMBVA','BSIVA','OCBCVA','DANAMONVA','OTHERBANKVA'
                                ];
                            @endphp
                            
                            <div class="text-center">
                                @if (str_contains($method, 'QRIS'))
                                    {{-- QRIS --}}
                                    <p class="text-gray-600 mb-4">Scan QR di bawah ini:</p>
                                    <div class="flex justify-center p-2 bg-white rounded-lg shadow-inner">
                                        <img src="{{ $url }}" alt="QRIS Payment" class="w-48 h-48 rounded-md">
                                    </div>
                                    <p class="mt-4 text-xs text-gray-500">Halaman ini akan diperbarui secara otomatis.</p>
                            
                                @elseif (in_array($method, ['DANA', 'OVO', 'SHOPEEPAY']))
                                    {{-- E-Wallet redirect --}}
                                    <script>
                                        window.location.href = "{{ $url }}";
                                    </script>
                                    <p class="text-gray-600 mb-4">Lanjutkan pembayaran dengan {{ ucfirst(strtolower($method)) }}:</p>
                                    <a href="{{ $url }}" target="_blank" class="inline-block">
                                        <button class="px-8 py-3 bg-purple-600 text-white font-semibold rounded-lg shadow-md hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-opacity-75 transition-transform transform hover:scale-105">
                                            Bayar dengan {{ ucfirst(strtolower($method)) }}
                                        </button>
                                    </a>
                            
                                @elseif (in_array($method, $virtualAccounts))
                                    {{-- Virtual Account --}}
                                    <p class="text-gray-600 mb-2">Gunakan Virtual Account berikut:</p>
                                    <div class="bg-white p-4 rounded-lg border-2 border-dashed">
                                        <strong class="text-2xl font-mono tracking-widest text-blue-600">
                                            {{ $url }}
                                        </strong>
                                    </div>
                                    <p class="mt-4 text-xs text-gray-500">Status akan diperbarui secara otomatis.</p>
                            
                                @else
                                    {{-- Default tombol bayar --}}
                                    <p class="text-gray-600 mb-4">Lanjutkan pembayaran dengan menekan tombol di bawah:</p>
                                    <a href="{{ $url }}" target="_blank" class="inline-block">
                                        <button class="px-8 py-3 bg-blue-600 text-white font-semibold rounded-lg shadow-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-75 transition-transform transform hover:scale-105">
                                            Bayar Sekarang
                                        </button>
                                    </a>
                                @endif
                            </div>
                        </div>
                    @else
                        <div class="h-full flex flex-col justify-center items-center text-center">
                           <h2 class="text-lg font-semibold text-gray-800">Pembayaran Selesai</h2>
                           <p class="text-gray-600 mt-2">Terima kasih telah menyelesaikan pembayaran Anda.</p>
                        </div>
                    @endif
                     {{-- === AKHIR DARI KODE TRIPAY === --}}
                </div>
            </div>
        </div>
    </div>
@endsection
