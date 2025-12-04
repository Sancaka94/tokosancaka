@extends('layouts.marketplace')

@section('content')
<div class="bg-gray-50 min-h-screen py-10">
    <div class="container mx-auto px-4 max-w-5xl">
        
        {{-- Header --}}
        <div class="flex items-center gap-3 mb-8">
            <a href="{{ url()->previous() }}" class="text-gray-500 hover:text-blue-600 transition">
                <i class="fas fa-arrow-left text-xl"></i>
            </a>
            <h1 class="text-2xl font-bold text-gray-800">Checkout Transaksi Digital</h1>
        </div>

        <form action="{{ route('ppob.checkout.store') }}" method="POST" id="ppob-form">
            @csrf
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                
                {{-- KOLOM KIRI (2/3): Rincian & Info --}}
                <div class="lg:col-span-2 space-y-6">
                    
                    {{-- Card Rincian Produk --}}
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

                    {{-- Card Informasi Akun --}}
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                        <h3 class="font-bold text-gray-700 mb-4 flex items-center gap-2">
                            <i class="fas fa-user-circle text-gray-400"></i> Informasi Akun
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                            <div>
                                <p class="text-xs text-gray-500">Nama Lengkap</p>
                                <p class="font-semibold text-gray-800">{{ $user->nama_lengkap }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">Email</p>
                                <p class="font-semibold text-gray-800">{{ $user->email }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">No. WhatsApp</p>
                                <p class="font-semibold text-gray-800">{{ $user->no_wa }}</p>
                            </div>
                        </div>
                    </div>

                </div>

                {{-- KOLOM KANAN (1/3): Metode Pembayaran --}}
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-2xl shadow-lg border border-gray-200 sticky top-24">
                        <div class="p-6">
                            <h3 class="font-bold text-gray-800 mb-4">Pilih Metode Pembayaran</h3>
                            
                            <div class="space-y-3 max-h-[500px] overflow-y-auto custom-scrollbar pr-1">
                                
                                {{-- 1. SALDO --}}
                                <label class="relative block cursor-pointer group">
                                    <input type="radio" name="payment_method" value="saldo" class="peer sr-only" {{ $user->saldo < $item['price'] ? 'disabled' : '' }}>
                                    <div class="p-4 rounded-xl border-2 border-gray-200 peer-checked:border-blue-500 peer-checked:bg-blue-50 transition-all hover:border-blue-300 {{ $user->saldo < $item['price'] ? 'opacity-50 bg-gray-50' : '' }}">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center gap-3">
                                                <div class="bg-blue-100 p-2 rounded-lg text-blue-600">
                                                    <i class="fas fa-wallet text-lg"></i>
                                                </div>
                                                <div>
                                                    <p class="font-bold text-gray-800 text-sm">Saldo Akun</p>
                                                    <p class="text-xs text-gray-500">Sisa: Rp {{ number_format($user->saldo) }}</p>
                                                </div>
                                            </div>
                                            @if($user->saldo < $item['price'])
                                                <span class="text-[10px] font-bold text-red-500 bg-red-50 px-2 py-1 rounded">Kurang</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="absolute top-4 right-4 hidden peer-checked:block text-blue-600">
                                        <i class="fas fa-check-circle text-xl"></i>
                                    </div>
                                </label>

                                {{-- 2. TRIPAY --}}
                                @if(isset($paymentChannels['tripay']))
                                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mt-4 mb-2">Virtual Account & E-Wallet</p>
                                    @foreach($paymentChannels['tripay'] as $channel)
                                        <label class="relative block cursor-pointer group">
                                            <input type="radio" name="payment_method" value="{{ $channel['code'] }}" class="peer sr-only">
                                            <div class="p-3 rounded-xl border border-gray-200 peer-checked:border-blue-500 peer-checked:bg-blue-50 transition-all hover:border-blue-300 flex items-center gap-3">
                                                <img src="{{ $channel['icon_url'] }}" class="h-6 w-auto object-contain" alt="{{ $channel['name'] }}">
                                                <span class="text-sm font-medium text-gray-700">{{ $channel['name'] }}</span>
                                            </div>
                                        </label>
                                    @endforeach
                                @endif
                                
                                {{-- 3. DOKU --}}
                                @if(isset($paymentChannels['doku']))
                                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mt-4 mb-2">Kartu Kredit & Lainnya</p>
                                    @foreach($paymentChannels['doku'] as $channel)
                                        <label class="relative block cursor-pointer group">
                                            <input type="radio" name="payment_method" value="{{ $channel['code'] }}" class="peer sr-only">
                                            <div class="p-3 rounded-xl border border-gray-200 peer-checked:border-blue-500 peer-checked:bg-blue-50 transition-all hover:border-blue-300 flex items-center gap-3">
                                                <img src="{{ $channel['icon_url'] }}" class="h-6 w-auto object-contain" alt="{{ $channel['name'] }}">
                                                <span class="text-sm font-medium text-gray-700">{{ $channel['name'] }}</span>
                                            </div>
                                        </label>
                                    @endforeach
                                @endif

                            </div>
                        </div>

                        <div class="p-4 bg-gray-50 border-t border-gray-200 rounded-b-2xl">
                            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-4 rounded-xl shadow-lg transition transform hover:-translate-y-1 flex justify-center items-center gap-2">
                                <span>Bayar Sekarang</span>
                                <i class="fas fa-arrow-right"></i>
                            </button>
                        </div>
                    </div>
                </div>

            </div>
        </form>
    </div>
</div>
@endsection