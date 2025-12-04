@extends('layouts.marketplace')

@section('title', 'Checkout PPOB')

@section('content')
<div class="bg-gray-50 min-h-screen py-10">
    <div class="container mx-auto px-4 max-w-5xl">
        
        {{-- Header Tombol Kembali --}}
        <div class="flex items-center gap-3 mb-8">
            <a href="{{ route('customer.dashboard') }}" class="text-gray-500 hover:text-blue-600 transition flex items-center gap-2">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
            <h1 class="text-2xl font-bold text-gray-800">Checkout Transaksi Digital</h1>
        </div>

        <form action="{{ route('ppob.checkout.store') }}" method="POST" id="ppob-form">
            @csrf
            
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                
                {{-- KOLOM KIRI (2/3): RINCIAN & PEMBAYARAN --}}
                <div class="lg:col-span-2 space-y-6">
                    
                    {{-- 1. KARTU RINCIAN PRODUK --}}
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                        <div class="bg-blue-50 px-6 py-4 border-b border-blue-100 flex justify-between items-center">
                            <h3 class="font-bold text-blue-800 flex items-center gap-2">
                                <i class="fas fa-receipt"></i> Rincian Tagihan
                            </h3>
                        </div>
                        <div class="p-6">
                            <div class="flex justify-between items-start mb-4">
                                <div>
                                    <p class="text-xs text-gray-500 uppercase font-bold tracking-wider mb-1">Produk</p>
                                    <h3 class="text-lg font-bold text-gray-800">{{ $item['name'] }}</h3>
                                </div>
                                <div class="text-right">
                                    <p class="text-xs text-gray-500 uppercase font-bold tracking-wider mb-1">No. Pelanggan</p>
                                    <span class="bg-gray-100 text-gray-800 font-mono font-bold px-3 py-1 rounded text-base">
                                        {{ $item['customer_no'] }}
                                    </span>
                                </div>
                            </div>

                            <div class="border-t border-dashed border-gray-200 pt-4 mt-4">
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600 font-medium">Total Pembayaran</span>
                                    <span class="text-2xl font-extrabold text-blue-600">Rp {{ number_format($item['price'], 0, ',', '.') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- 2. KARTU PILIH PEMBAYARAN --}}
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-200">
                        <div class="p-6 border-b border-gray-100">
                            <h3 class="font-bold text-gray-800 flex items-center gap-2">
                                <i class="fas fa-wallet text-gray-400"></i> Pilih Metode Pembayaran
                            </h3>
                        </div>
                        
                        <div class="p-6 space-y-6">

                            {{-- A. SALDO AKUN --}}
                            @if(isset($paymentChannels['saldo']))
                                <div>
                                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Dompet Digital</p>
                                    @php 
                                        $saldo = $paymentChannels['saldo']; 
                                        $isCukup = $saldo['balance'] >= $item['price'];
                                    @endphp
                                    <label class="relative block cursor-pointer group">
                                        <input type="radio" name="payment_method" value="saldo" class="peer sr-only" {{ !$isCukup ? 'disabled' : '' }}>
                                        <div class="p-4 rounded-xl border-2 border-gray-200 peer-checked:border-blue-500 peer-checked:bg-blue-50 transition-all hover:border-blue-300 flex items-center justify-between {{ !$isCukup ? 'opacity-60 bg-gray-50 cursor-not-allowed' : '' }}">
                                            <div class="flex items-center gap-4">
                                                <img src="{{ $saldo['icon_url'] }}" class="w-10 h-10 object-contain" alt="Saldo">
                                                <div>
                                                    <span class="font-bold text-gray-800 block">Saldo Akun</span>
                                                    <span class="text-sm text-gray-500 font-medium">Sisa: Rp {{ number_format($saldo['balance']) }}</span>
                                                </div>
                                            </div>
                                            @if(!$isCukup)
                                                <span class="text-[10px] font-bold text-red-600 bg-red-100 px-2 py-1 rounded">Saldo Kurang</span>
                                            @else
                                                <div class="w-5 h-5 rounded-full border-2 border-gray-300 peer-checked:border-blue-600 peer-checked:bg-blue-600"></div>
                                            @endif
                                        </div>
                                    </label>
                                </div>
                            @endif

                            {{-- B. TRIPAY CHANNELS --}}
                            @if(isset($paymentChannels['tripay']) && count($paymentChannels['tripay']) > 0)
                                <div>
                                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Virtual Account & E-Wallet (Otomatis)</p>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        @foreach($paymentChannels['tripay'] as $channel)
                                            <label class="relative block cursor-pointer group">
                                                <input type="radio" name="payment_method" value="{{ $channel['code'] }}" class="peer sr-only">
                                                <div class="p-3 rounded-xl border border-gray-200 peer-checked:border-blue-500 peer-checked:bg-blue-50 transition-all hover:border-blue-300 flex items-center gap-3 h-full">
                                                    <img src="{{ $channel['icon_url'] }}" class="h-8 w-auto object-contain" alt="{{ $channel['name'] }}">
                                                    <span class="text-sm font-medium text-gray-700 leading-tight">{{ $channel['name'] }}</span>
                                                </div>
                                                <div class="absolute top-2 right-2 w-3 h-3 rounded-full border border-gray-300 peer-checked:bg-blue-600 peer-checked:border-blue-600 hidden peer-checked:block"></div>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            {{-- C. DOKU CHANNELS --}}
                            @if(isset($paymentChannels['doku']) && count($paymentChannels['doku']) > 0)
                                <div>
                                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3 mt-4">Kartu Kredit & Lainnya</p>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        @foreach($paymentChannels['doku'] as $channel)
                                            <label class="relative block cursor-pointer group">
                                                <input type="radio" name="payment_method" value="{{ $channel['code'] }}" class="peer sr-only">
                                                <div class="p-3 rounded-xl border border-gray-200 peer-checked:border-blue-500 peer-checked:bg-blue-50 transition-all hover:border-blue-300 flex items-center gap-3 h-full">
                                                    <img src="{{ $channel['icon_url'] }}" class="h-8 w-auto object-contain" alt="{{ $channel['name'] }}">
                                                    <span class="text-sm font-medium text-gray-700 leading-tight">{{ $channel['name'] }}</span>
                                                </div>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                        </div>
                        
                        {{-- TOMBOL BAYAR --}}
                        <div class="p-6 bg-gray-50 border-t border-gray-200 rounded-b-2xl">
                            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-4 rounded-xl shadow-lg transition transform hover:-translate-y-1 flex justify-center items-center gap-2 text-lg">
                                <span>Bayar Sekarang</span>
                                <i class="fas fa-arrow-right"></i>
                            </button>
                            <p class="text-center text-xs text-gray-400 mt-3">
                                <i class="fas fa-lock"></i> Pembayaran Anda aman dan terenkripsi.
                            </p>
                        </div>

                    </div>
                </div>

                {{-- KOLOM KANAN (1/3): INFO USER --}}
                <div class="lg:col-span-1">
                    <div class="sticky top-24 space-y-6">
                        
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                            <h3 class="font-bold text-gray-700 mb-4 flex items-center gap-2">
                                <i class="fas fa-user-circle text-gray-400"></i> Informasi Akun
                            </h3>
                            <div class="space-y-4">
                                <div>
                                    <p class="text-xs text-gray-500 uppercase">Nama Lengkap</p>
                                    <p class="font-semibold text-gray-800">{{ $user->nama_lengkap }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500 uppercase">Email</p>
                                    <p class="font-semibold text-gray-800">{{ $user->email }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500 uppercase">No. WhatsApp</p>
                                    <p class="font-semibold text-gray-800">{{ $user->no_wa }}</p>
                                </div>
                            </div>
                        </div>

                        {{-- Bantuan Singkat --}}
                        <div class="bg-blue-50 rounded-xl p-4 border border-blue-100 flex gap-3 items-start">
                            <i class="fas fa-info-circle text-blue-600 mt-1"></i>
                            <p class="text-sm text-blue-800 leading-relaxed">
                                Pastikan saldo mencukupi atau pilih metode pembayaran instan agar transaksi diproses otomatis.
                            </p>
                        </div>

                    </div>
                </div>

            </div>
        </form>
    </div>
</div>
@endsection