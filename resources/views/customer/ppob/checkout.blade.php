@extends('layouts.marketplace')

@section('title', 'Keranjang & Checkout')

@push('styles')
<style>
    /* Custom Scrollbar untuk list payment */
    .payment-scroll::-webkit-scrollbar { width: 6px; }
    .payment-scroll::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 10px; }
    .payment-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
    .payment-scroll::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
</style>
@endpush

@section('content')
<div class="bg-gray-50 min-h-screen py-8 md:py-12">
    <div class="container mx-auto px-4 max-w-6xl">

        {{-- 1. ALERT NOTIFIKASI --}}
        @if(session('success'))
            <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl flex items-center gap-2" role="alert">
                <i class="fas fa-check-circle"></i> <span>{{ session('success') }}</span>
            </div>
        @endif
        @if(session('error'))
            <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl flex items-center gap-2" role="alert">
                <i class="fas fa-exclamation-circle"></i> <span>{{ session('error') }}</span>
            </div>
        @endif

        {{-- 2. HEADER HALAMAN --}}
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
            <div>
                <h1 class="text-2xl font-extrabold text-gray-800 flex items-center gap-3">
                    <i class="fas fa-shopping-cart text-blue-600"></i> Checkout Transaksi
                </h1>
                <p class="text-gray-500 text-sm mt-1">Periksa kembali pesanan digital Anda sebelum membayar.</p>
            </div>

            {{-- TOMBOL BATALKAN SEMUA TRANSAKSI --}}
            @if(!empty($cart))
            <form action="{{ route('ppob.cart.clear') }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin mengosongkan keranjang?');">
                @csrf
                <button type="submit" class="group flex items-center gap-2 bg-white border border-red-200 text-red-500 hover:bg-red-50 hover:border-red-300 hover:text-red-700 px-5 py-2.5 rounded-xl transition font-semibold shadow-sm text-sm">
                    <i class="fas fa-trash-alt group-hover:scale-110 transition-transform"></i> 
                    Batalkan Semua
                </button>
            </form>
            @endif
        </div>

        {{-- FORM UTAMA CHECKOUT --}}
        <form action="{{ route('ppob.checkout.store') }}" method="POST" id="checkout-form">
            @csrf

            {{-- 🔥 TAMBAHAN KODE PENGAMAN (IDEMPOTENCY) 🔥 --}}
            {{-- Jika variabel $idempotencyKey belum ada (fallback), buat baru --}}
            @php
                if (!isset($idempotencyKey)) {
                    $idempotencyKey = (string) \Illuminate\Support\Str::uuid();
                }
            @endphp
            <input type="hidden" name="idempotency_key" value="{{ $idempotencyKey }}">
            
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                
                {{-- === KOLOM KIRI (LIST ITEM) === --}}
                <div class="lg:col-span-2 space-y-6">
                    
                    {{-- LOOPING ITEM KERANJANG --}}
                    @forelse($cart as $item)
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden relative transition hover:shadow-md hover:border-blue-200 group">
                        
                        {{-- DEKORASI SIDEBAR WARNA --}}
                        <div class="absolute left-0 top-0 bottom-0 w-1.5 bg-blue-500"></div>

                        <div class="p-5 pl-7 flex flex-col sm:flex-row gap-5 items-start sm:items-center justify-between">
                            
                            {{-- Info Produk --}}
                            <div class="flex items-start gap-4 flex-1">
                                {{-- Icon Produk --}}
                                <div class="w-12 h-12 rounded-xl bg-blue-50 flex items-center justify-center text-blue-600 shadow-sm border border-blue-100 flex-shrink-0">
                                    <i class="fas fa-receipt text-xl"></i>
                                </div>
                                
                                <div>
                                    <h4 class="font-bold text-gray-800 text-lg leading-tight mb-1">{{ $item['name'] }}</h4>
                                    <div class="flex flex-wrap items-center gap-2 text-sm text-gray-500">
                                        <div class="flex items-center gap-1 bg-gray-100 px-2 py-0.5 rounded text-xs font-mono">
                                            <i class="fas fa-user text-gray-400"></i> {{ $item['customer_no'] }}
                                        </div>
                                        <span class="text-gray-300">|</span>
                                        <span class="text-xs">SKU: {{ $item['sku'] }}</span>
                                    </div>
                                    
                                    {{-- Tampilkan Rincian Tagihan (Jika Ada) --}}
                                    @if(!empty($item['desc']))
                                        <div class="mt-2 text-xs text-gray-500 bg-yellow-50 p-2 rounded border border-yellow-100 inline-block">
                                            @if(isset($item['desc']['customer_name']))
                                                <strong>An. {{ $item['desc']['customer_name'] }}</strong>
                                            @endif
                                            @if(isset($item['desc']['power']))
                                                / {{ $item['desc']['power'] }}
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </div>

                            {{-- Harga & Hapus --}}
                            <div class="flex items-center gap-4 sm:gap-6 w-full sm:w-auto justify-between sm:justify-end">
                                <div class="text-right">
                                    <p class="text-xs text-gray-400 uppercase font-bold tracking-wider">Harga</p>
                                    <p class="text-xl font-extrabold text-blue-600">Rp {{ number_format($item['price'], 0, ',', '.') }}</p>
                                </div>

                                {{-- TOMBOL HAPUS PER ITEM (SAMPAH MERAH) --}}
                                <a href="{{ route('ppob.cart.remove', $item['id']) }}" 
                                   class="w-10 h-10 rounded-full bg-red-50 text-red-500 flex items-center justify-center hover:bg-red-500 hover:text-white transition shadow-sm border border-red-100 tooltip-trigger"
                                   onclick="return confirm('Hapus produk ini dari keranjang?')"
                                   title="Hapus Item">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                    @empty
                        {{-- Tampilan Jika Keranjang Kosong (Safety Fallback) --}}
                        <div class="bg-white rounded-2xl p-10 text-center border border-dashed border-gray-300">
                            <i class="fas fa-shopping-basket text-4xl text-gray-300 mb-4"></i>
                            <p class="text-gray-500">Keranjang transaksi kosong.</p>
                            <a href="{{ route('ppob.pricelist') }}" class="text-blue-600 font-bold hover:underline mt-2 inline-block">Kembali Belanja</a>
                        </div>
                    @endforelse

                    {{-- TOTAL TAGIHAN --}}
                    @if(!empty($cart))
                    <div class="bg-gradient-to-r from-blue-600 to-blue-700 rounded-2xl shadow-lg p-6 text-white flex flex-col sm:flex-row justify-between items-center gap-4">
                        <div class="flex items-center gap-4">
                            <div class="p-3 bg-white/20 rounded-full">
                                <i class="fas fa-wallet text-2xl"></i>
                            </div>
                            <div>
                                <p class="text-blue-100 text-sm font-medium">Total Pembayaran ({{ count($cart) }} Item)</p>
                                <h2 class="text-3xl font-bold">Rp {{ number_format($totalPrice, 0, ',', '.') }}</h2>
                            </div>
                        </div>
                        <div class="text-blue-200 text-xs text-right hidden sm:block">
                            <p>Tidak ada biaya tersembunyi.</p>
                            <p>Transaksi diproses otomatis.</p>
                        </div>
                    </div>
                    @endif

                </div>

                {{-- === KOLOM KANAN (PEMBAYARAN) === --}}
                <div class="lg:col-span-1 space-y-6">
                    
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 sticky top-24">
                        <div class="p-5 border-b border-gray-100 bg-gray-50/50 rounded-t-2xl">
                            <h3 class="font-bold text-gray-800 flex items-center gap-2">
                                <i class="fas fa-credit-card text-blue-500"></i> Metode Pembayaran
                            </h3>
                        </div>
                        
                        <div class="p-5 space-y-4 max-h-[500px] overflow-y-auto payment-scroll">
                            
                            {{-- A. SALDO AKUN --}}
                            @if(isset($paymentChannels['saldo']))
                                @php 
                                    $saldo = $paymentChannels['saldo']; 
                                    $isCukup = $saldo['balance'] >= $totalPrice;
                                @endphp
                                <label class="relative block cursor-pointer group">
                                    <input type="radio" name="payment_method" value="saldo" class="peer sr-only" {{ !$isCukup ? 'disabled' : '' }}>
                                    <div class="p-4 rounded-xl border-2 border-gray-200 peer-checked:border-blue-500 peer-checked:bg-blue-50/50 transition-all hover:border-blue-300 {{ !$isCukup ? 'opacity-60 bg-gray-50 cursor-not-allowed' : 'bg-white' }}">
                                        <div class="flex justify-between items-center mb-2">
                                            <div class="bg-red-600 rounded-xl shadow-lg relative overflow-hidden p-6 text-white group transition-all hover:-translate-y-1">
    
    {{-- Konten Teks --}}
    <div class="relative z-10">
        {{-- Menampilkan Nominal Saldo (Saya tambahkan variabel ini agar informatif) --}}
        <h3 class="text-3xl font-extrabold tracking-tight">
            Rp {{ number_format($saldo['balance'] ?? 0, 0, ',', '.') }}
        </h3>
        
        {{-- Label --}}
        <span class="text-sm font-medium uppercase tracking-wider opacity-90 block mt-1">
            Saldo Akun Anda
        </span>
    </div>

    {{-- Dekorasi Ikon Besar di Pojok --}}
    <div class="absolute -right-6 -bottom-6 opacity-20 pointer-events-none">
        {{-- 
            brightness-0 invert: Membuat ikon jadi putih polos (siluet). 
            Hapus class ini jika ingin ikon tetap berwarna asli (kuning/emas). 
        --}}
        <img src="{{ $saldo['icon_url'] }}" 
             class="w-32 h-32 object-contain brightness-0 invert transform rotate-12 group-hover:scale-110 transition-transform duration-500" 
             alt="Icon">
    </div>

</div>
                                            <div class="w-5 h-5 rounded-full border-2 border-gray-300 peer-checked:border-blue-600 peer-checked:bg-blue-600 flex items-center justify-center">
                                                <div class="w-2 h-2 bg-white rounded-full hidden peer-checked:block"></div>
                                            </div>
                                        </div>
                                        
                                            <span class="text-xs text-gray-500">Sisa Saldo Anda</span>
                                            <span class="text-sm font-bold {{ $isCukup ? 'text-green-600' : 'text-red-500' }}">
                                                Rp {{ number_format($saldo['balance']) }}
                                            </span>
                                      
                                        @if(!$isCukup)
                                            <div class="mt-2 text-[10px] text-red-500 font-bold bg-red-50 px-2 py-1 rounded text-center">
                                                Saldo Tidak Mencukupi
                                            </div>
                                        @endif
                                    </div>
                                </label>
                            @endif

                            {{-- C. DOKU CHANNELS --}}
                            @if(isset($paymentChannels['doku']) && count($paymentChannels['doku']) > 0)
                                <div class="mt-4">
                                    <p class="text-xs font-bold text-black uppercase tracking-wider mb-2 pl-1">Rekomendasi Sancaka</p>
                                    <div class="space-y-3">
                                        @foreach($paymentChannels['doku'] as $channel)
                                            <label class="relative block cursor-pointer group">
                                                <input type="radio" name="payment_method" value="{{ $channel['code'] }}" class="peer sr-only">
                                                <div class="p-3 rounded-xl border border-gray-200 peer-checked:border-blue-500 peer-checked:bg-blue-50 transition-all hover:border-blue-300 flex items-center gap-3 bg-white">
                                                    <img src="{{ $channel['icon_url'] }}" class="h-6 w-auto object-contain">
                                                    <span class="text-sm font-medium text-gray-700 flex-1">{{ $channel['name'] }}</span>
                                                    <div class="w-4 h-4 rounded-full border border-gray-300 peer-checked:bg-blue-600 peer-checked:border-blue-600"></div>
                                                </div>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            {{-- B. TRIPAY CHANNELS --}}
                            @if(isset($paymentChannels['tripay']) && count($paymentChannels['tripay']) > 0)
                                <div class="mt-4">
                                    <p class="text-xs font-bold text-black uppercase tracking-wider mb-2 pl-1">Virtual Account & E-Wallet</p>
                                    <div class="space-y-3">
                                        @foreach($paymentChannels['tripay'] as $channel)
                                            <label class="relative block cursor-pointer group">
                                                <input type="radio" name="payment_method" value="{{ $channel['code'] }}" class="peer sr-only">
                                                <div class="p-3 rounded-xl border border-gray-200 peer-checked:border-blue-500 peer-checked:bg-blue-50 transition-all hover:border-blue-300 flex items-center gap-3 bg-white">
                                                    <img src="{{ $channel['icon_url'] }}" class="h-6 w-auto object-contain">
                                                    <span class="text-sm font-medium text-gray-700 flex-1">{{ $channel['name'] }}</span>
                                                    <div class="w-4 h-4 rounded-full border border-gray-300 peer-checked:bg-blue-600 peer-checked:border-blue-600"></div>
                                                </div>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            

                        </div>

                        <div class="p-5 bg-gray-50 border-t border-gray-200 rounded-b-2xl">
                            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3.5 rounded-xl shadow-lg transition transform hover:-translate-y-1 flex justify-center items-center gap-2 group">
                                <span>Bayar Sekarang</span> 
                                <i class="fas fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                            </button>
                            <p class="text-center text-xs text-gray-400 mt-3 flex justify-center items-center gap-1">
                                <i class="fas fa-shield-alt"></i> Pembayaran aman & terenkripsi SSL.
                            </p>
                        </div>
                    </div>

                    {{-- INFORMASI USER --}}
                    <div class="bg-white rounded-2xl p-5 border border-gray-200 shadow-sm">
                        <div class="flex items-center gap-3 border-b border-gray-100 pb-3 mb-3">
                            <div class="w-10 h-10 rounded-full bg-blue-50 flex items-center justify-center text-blue-600 font-bold">
                                {{ substr($user->nama_lengkap, 0, 1) }}
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 uppercase font-bold">Pembeli</p>
                                <p class="text-sm font-bold text-gray-800">{{ $user->nama_lengkap }}</p>
                            </div>
                        </div>
                        <p class="text-xs text-gray-500">
                            Invoice akan dikirim ke email <strong>{{ $user->email }}</strong>
                        </p>
                    </div>

                </div>

            </div>
        </form>
    </div>
</div>
@endsection