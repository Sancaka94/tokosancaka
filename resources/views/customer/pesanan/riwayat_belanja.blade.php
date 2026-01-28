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
            <a href="{{ route('katalog.index') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:border-indigo-900 focus:ring ring-indigo-300 transition ease-in-out duration-150">
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

                        {{-- Header Kartu --}}
                        <div class="px-6 py-4 bg-gray-50 border-b border-gray-100 flex flex-wrap justify-between items-center gap-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-white border border-gray-200 flex items-center justify-center text-indigo-600 shadow-sm">
                                    <i class="fas fa-store"></i>
                                </div>
                                <div>
                                    {{-- Menggunakan ?-> untuk mencegah error jika toko dihapus --}}
                                    <h4 class="font-bold text-gray-800 text-sm">{{ $order->store?->name ?? 'Toko Tidak Tersedia' }}</h4>
                                    <p class="text-xs text-gray-500 font-mono">{{ $order->invoice_number }} • {{ $order->created_at->format('d M Y') }}</p>
                                </div>
                            </div>

                            {{-- Badge Status --}}
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
                                    'paid' => 'Lunas',
                                    'processing' => 'Diproses Penjual',
                                    'shipped' => 'Sedang Dikirim',
                                    'pending', 'unpaid' => 'Belum Bayar',
                                    'completed' => 'Selesai',
                                    default => ucfirst($status)
                                };
                            @endphp

                            <span class="px-3 py-1 rounded-full text-xs font-bold border {{ $badgeClass }}">
                                {{ strtoupper($statusLabel) }}
                            </span>
                        </div>

                        {{-- Body Kartu --}}
                        <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-8">

                            {{-- Kolom 1: Produk --}}
                            <div class="md:col-span-2 space-y-4">
                                @php
                                    // Handle jika items null
                                    $items = $order->items ?? collect([]);
                                @endphp

                                @foreach($items->take(2) as $item)
                                    <div class="flex items-start gap-4">
                                        <div class="w-16 h-16 flex-shrink-0 bg-gray-100 rounded-lg overflow-hidden border border-gray-200">
                                            @if($item->product && $item->product->images && $item->product->images->count() > 0)
                                                <img src="{{ asset('storage/' . $item->product->images->first()->path) }}" class="w-full h-full object-cover">
                                            @else
                                                <div class="w-full h-full flex items-center justify-center text-gray-400 text-xs">No Pic</div>
                                            @endif
                                        </div>
                                        <div>
                                            <h5 class="text-sm font-semibold text-gray-900 line-clamp-2">
                                                {{ $item->product?->name ?? 'Produk Dihapus' }}
                                            </h5>
                                            <p class="text-xs text-gray-500 mt-1">
                                                {{ $item->quantity }} x Rp {{ number_format($item->price, 0, ',', '.') }}
                                                @if($item->variant)
                                                    <span class="text-gray-400">({{ $item->variant->sku_code ?? 'Varian' }})</span>
                                                @endif
                                            </p>
                                        </div>
                                    </div>
                                @endforeach

                                @if($items->count() > 2)
                                    <p class="text-xs text-gray-500 font-medium italic pl-20">+ {{ $items->count() - 2 }} produk lainnya...</p>
                                @endif
                            </div>

                            {{-- Kolom 2: Info & Aksi --}}
                            <div class="flex flex-col justify-between border-t md:border-t-0 md:border-l border-gray-100 md:pl-8 pt-4 md:pt-0">

                                {{-- Info Pengiriman --}}
                                <div class="mb-4">
                                    <p class="text-xs text-gray-500 uppercase font-bold mb-1">Kurir</p>
                                    <p class="text-sm font-medium text-gray-800">
                                        {{ strtoupper(str_replace('-', ' ', $order->shipping_method)) }}
                                    </p>

                                    {{-- TAMPILKAN RESI JIKA ADA --}}
                                    @if($order->shipping_resi)
                                        <div class="mt-3 bg-green-50 border border-green-200 rounded p-2">
                                            <p class="text-[10px] text-green-700 font-bold uppercase">Nomor Resi</p>
                                            <p class="text-sm font-mono text-gray-800 select-all">{{ $order->shipping_resi }}</p>
                                        </div>
                                    @elseif(in_array($status, ['paid', 'processing']))
                                        <div class="mt-3 text-xs text-blue-600 bg-blue-50 p-2 rounded border border-blue-100">
                                            <i class="fas fa-clock mr-1"></i> Menunggu Resi dari Penjual
                                        </div>
                                    @endif
                                </div>

                                {{-- Total & Tombol --}}
                                <div>
                                    <div class="flex justify-between items-end mb-4">
                                        <span class="text-xs text-gray-500">Total Belanja</span>
                                        <span class="text-lg font-bold text-indigo-600">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</span>
                                    </div>

                                    <div class="grid gap-2">
                                        {{-- LOGIKA TOMBOL --}}
                                        @if(in_array($status, ['pending', 'unpaid', 'menunggu_pembayaran']))
                                            @if(!empty($order->invoice_number))
                                                <a href="{{ route('checkout.invoice', ['invoice' => $order->invoice_number]) }}" class="block w-full text-center bg-indigo-600 text-white text-sm font-bold py-2 rounded hover:bg-indigo-700 transition shadow-sm">
                                                    Bayar Sekarang
                                                </a>
                                            @endif
                                        @elseif($order->shipping_resi)
                                            {{-- Pastikan route tracking Anda benar --}}
                                            <a href="{{ route('tracking.index', ['resi' => $order->shipping_resi]) }}" class="block w-full text-center border border-indigo-600 text-indigo-600 text-sm font-bold py-2 rounded hover:bg-indigo-50 transition">
                                                Lacak Paket
                                            </a>
                                        @else
                                            @if(!empty($order->invoice_number))
                                                <a href="{{ route('checkout.invoice', ['invoice' => $order->invoice_number]) }}" class="block w-full text-center bg-gray-100 text-gray-700 text-sm font-bold py-2 rounded hover:bg-gray-200 transition">
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
