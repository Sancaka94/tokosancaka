{{--
File: resources/views/customer/pesanan/riwayat_belanja.blade.php
--}}

@extends('layouts.customer')

@section('title', 'Riwayat Belanja')

@section('content')
<div class="bg-gray-50 min-h-screen py-8">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">

        {{-- Header --}}
        <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Riwayat Belanja</h1>
                <p class="mt-2 text-sm text-gray-600">Daftar transaksi marketplace Anda.</p>
            </div>
            <a href="{{ route('katalog.index') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 transition shadow-sm">
                <i class="fas fa-plus mr-2"></i> Belanja Lagi
            </a>
        </div>

        {{-- Jika Tidak Ada Pesanan --}}
        @if($pesanans->isEmpty())
            <div class="bg-white rounded-xl shadow-sm p-12 text-center border border-dashed border-gray-300">
                <div class="mx-auto w-20 h-20 bg-indigo-50 rounded-full flex items-center justify-center mb-6">
                    <i class="fas fa-shopping-basket text-indigo-400 text-3xl"></i>
                </div>
                <h3 class="text-xl font-medium text-gray-900">Belum ada riwayat belanja</h3>
                <p class="text-gray-500 mt-2 mb-6">Sepertinya Anda belum pernah checkout barang apapun.</p>
                <a href="{{ route('katalog.index') }}" class="text-indigo-600 hover:text-indigo-800 font-semibold hover:underline">
                    Cari Produk Sekarang &rarr;
                </a>
            </div>
        @else

            {{-- List Pesanan --}}
            <div class="space-y-6">
                @foreach($pesanans as $order)
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition-shadow duration-300">

                        {{-- 1. HEADER KARTU (LOGO TOKO & STATUS) --}}
                        <div class="px-6 py-4 bg-gray-50 border-b border-gray-100 flex flex-wrap justify-between items-center gap-4">
                            <div class="flex items-center gap-3">

                                {{-- LOGO TOKO --}}
                                <div class="w-10 h-10 rounded-full bg-white border border-gray-200 flex-shrink-0 overflow-hidden flex items-center justify-center">
                                    @if($order->store && $order->store->logo_path)
                                        <img src="{{ asset('storage/' . $order->store->logo_path) }}" alt="Logo Toko" class="w-full h-full object-cover">
                                    @else
                                        <i class="fas fa-store text-indigo-400"></i>
                                    @endif
                                </div>

                                <div>
                                    <h4 class="font-bold text-gray-800 text-sm flex items-center gap-2">
                                        {{ $order->store?->name ?? 'Toko Tidak Tersedia' }}
                                        <span class="text-[10px] bg-gray-200 text-gray-600 px-1.5 py-0.5 rounded">Penjual</span>
                                    </h4>
                                    <p class="text-xs text-gray-500 font-mono mt-0.5">
                                        {{ $order->invoice_number }} • {{ $order->created_at->format('d M Y') }}
                                    </p>
                                </div>
                            </div>

                            {{-- BADGE STATUS --}}
                            @php
                                $status = strtolower($order->status);
                                $badgeClass = match($status) {
                                    'paid', 'completed', 'success', 'lunas' => 'bg-green-100 text-green-800 border-green-200',
                                    'pending', 'unpaid', 'menunggu_pembayaran', 'menunggu pembayaran' => 'bg-yellow-100 text-yellow-800 border-yellow-200',
                                    'processing', 'diproses' => 'bg-blue-100 text-blue-800 border-blue-200',
                                    'shipped', 'dikirim' => 'bg-purple-100 text-purple-800 border-purple-200',
                                    'failed', 'expired', 'batal' => 'bg-red-100 text-red-800 border-red-200',
                                    default => 'bg-gray-100 text-gray-800 border-gray-200'
                                };
                                $statusLabel = match($status) {
                                    'paid' => 'LUNAS',
                                    'processing' => 'DIPROSES PENJUAL',
                                    'shipped' => 'SEDANG DIKIRIM',
                                    'pending', 'unpaid' => 'BELUM BAYAR',
                                    'completed' => 'SELESAI',
                                    default => strtoupper($status)
                                };
                            @endphp
                            <span class="px-3 py-1 rounded-full text-xs font-bold border {{ $badgeClass }}">
                                {{ $statusLabel }}
                            </span>
                        </div>

                        {{-- 2. BODY KARTU --}}
                        <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-8">

                            {{-- KOLOM KIRI: PRODUK --}}
                            <div class="md:col-span-2 space-y-4">
                                @php
                                    $items = $order->items ?? collect([]);
                                @endphp

                                @foreach($items->take(2) as $item)
                                    <div class="flex items-start gap-4 p-2 hover:bg-gray-50 rounded-lg transition">

                                        {{-- GAMBAR PRODUK --}}
                                        <div class="w-20 h-20 flex-shrink-0 bg-gray-200 rounded-lg overflow-hidden border border-gray-200 relative group">
                                            @if($item->product && $item->product->images && $item->product->images->count() > 0)
                                                <img src="{{ asset('storage/' . $item->product->images->first()->path) }}"
                                                     alt="{{ $item->product->name }}"
                                                     class="w-full h-full object-cover group-hover:scale-105 transition duration-300"
                                                     onerror="this.onerror=null; this.src='https://via.placeholder.com/150?text=No+Image';">
                                            @else
                                                <div class="w-full h-full flex flex-col items-center justify-center text-gray-400">
                                                    <i class="fas fa-image text-xl mb-1"></i>
                                                    <span class="text-[9px]">No Pic</span>
                                                </div>
                                            @endif
                                        </div>

                                        <div>
                                            <h5 class="text-sm font-bold text-gray-900 line-clamp-2">
                                                {{ $item->product?->name ?? 'Produk Dihapus' }}
                                            </h5>

                                            {{-- Info Varian (Jika Ada) --}}
                                            @if($item->variant)
                                                <p class="text-xs text-gray-500 mt-1">
                                                    Varian: {{ $item->variant->sku_code ?? 'Default' }}
                                                </p>
                                            @endif

                                            <p class="text-xs text-gray-700 font-medium mt-2">
                                                {{ $item->quantity }} x Rp {{ number_format($item->price, 0, ',', '.') }}
                                            </p>
                                        </div>
                                    </div>
                                @endforeach

                                @if($items->count() > 2)
                                    <a href="{{ route('checkout.invoice', ['invoice' => $order->invoice_number]) }}" class="block text-xs text-indigo-600 font-medium hover:underline pl-24">
                                        + {{ $items->count() - 2 }} produk lainnya...
                                    </a>
                                @endif
                            </div>

                            {{-- KOLOM KANAN: PENGIRIMAN & TOTAL --}}
                            <div class="flex flex-col justify-between border-t md:border-t-0 md:border-l border-gray-100 md:pl-8 pt-4 md:pt-0">

                                {{-- INFO KURIR & LOGO EKSPEDISI --}}
                                <div class="mb-4">
                                    <p class="text-xs text-gray-500 uppercase font-bold mb-2 tracking-wider">KURIR PENGIRIMAN</p>

                                    <div class="flex items-center gap-3">
                                        {{-- LOGIKA PARSING LOGO EKSPEDISI --}}
                                        @php
                                            // Format: regular-anteraja-REG...
                                            $parts = explode('-', $order->shipping_method);
                                            $courierName = $parts[1] ?? 'Kurir';
                                            // Mapping manual nama file logo jika perlu (contoh: jne -> jne.png)
                                            $logoPath = strtolower($courierName);
                                        @endphp

                                        <div class="w-12 h-auto bg-white rounded border border-gray-200 p-1">
                                            {{-- Coba load logo ekspedisi, jika gagal tampilkan icon truck --}}
                                            <img src="{{ asset('storage/logo-ekspedisi/' . $logoPath . '.png') }}"
                                                 alt="{{ $courierName }}"
                                                 class="w-full h-full object-contain"
                                                 onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                                            <i class="fas fa-truck text-gray-400 text-lg hidden text-center w-full"></i>
                                        </div>

                                        <div>
                                            <p class="text-sm font-bold text-gray-800 uppercase">
                                                {{ $courierName }}
                                            </p>
                                            <p class="text-[10px] text-gray-500">
                                                {{ strtoupper($parts[2] ?? 'Layanan') }}
                                            </p>
                                        </div>
                                    </div>

                                    {{-- INFO RESI --}}
                                    @if($order->shipping_resi)
                                        <div class="mt-3 bg-green-50 border border-green-200 rounded p-2 flex justify-between items-center group cursor-pointer" onclick="navigator.clipboard.writeText('{{ $order->shipping_resi }}'); alert('Resi disalin!')">
                                            <div>
                                                <p class="text-[10px] text-green-700 font-bold uppercase">Nomor Resi</p>
                                                <p class="text-xs font-mono text-gray-900 font-bold">{{ $order->shipping_resi }}</p>
                                            </div>
                                            <i class="fas fa-copy text-green-400 group-hover:text-green-600"></i>
                                        </div>
                                    @elseif(in_array($status, ['paid', 'processing']))
                                        <div class="mt-3 text-xs text-blue-600 bg-blue-50 p-2 rounded border border-blue-100 flex items-start gap-2">
                                            <i class="fas fa-clock mt-0.5"></i>
                                            <span>Menunggu Resi dari Penjual</span>
                                        </div>
                                    @endif
                                </div>

                                {{-- TOTAL & TOMBOL AKSI --}}
                                <div>
                                    <div class="flex justify-between items-end mb-4 border-t border-dashed border-gray-200 pt-3">
                                        <span class="text-xs text-gray-500">Total Belanja</span>
                                        <span class="text-lg font-bold text-indigo-600">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</span>
                                    </div>

                                    <div class="grid gap-2">
                                        @if(in_array($status, ['pending', 'unpaid', 'menunggu_pembayaran']))
                                            @if(!empty($order->invoice_number))
                                                <a href="{{ route('checkout.invoice', ['invoice' => $order->invoice_number]) }}" class="block w-full text-center bg-indigo-600 text-white text-sm font-bold py-2.5 rounded-lg hover:bg-indigo-700 transition shadow-lg shadow-indigo-200">
                                                    Bayar Sekarang
                                                </a>
                                            @endif
                                        @elseif($order->shipping_resi)
                                            <a href="{{ route('tracking.index', ['resi' => $order->shipping_resi]) }}" class="block w-full text-center border border-indigo-600 text-indigo-600 text-sm font-bold py-2.5 rounded-lg hover:bg-indigo-50 transition">
                                                Lacak Paket
                                            </a>
                                            <a href="{{ route('checkout.invoice', ['invoice' => $order->invoice_number]) }}" class="block w-full text-center text-gray-500 text-xs hover:text-gray-700 mt-1">
                                                Lihat Invoice
                                            </a>
                                        @else
                                            @if(!empty($order->invoice_number))
                                                <a href="{{ route('checkout.invoice', ['invoice' => $order->invoice_number]) }}" class="block w-full text-center bg-gray-100 text-gray-700 text-sm font-bold py-2.5 rounded-lg hover:bg-gray-200 transition">
                                                    Detail Pesanan
                                                </a>
                                            @endif
                                        @endif
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Pagination --}}
            <div class="mt-8">
                {{ $pesanans->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
