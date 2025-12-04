@extends('layouts.marketplace')

@section('content')
<div class="container mx-auto py-10 px-4">
    <div class="max-w-3xl mx-auto">
        <h1 class="text-2xl font-bold mb-6">Checkout Transaksi Digital</h1>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            {{-- KOLOM KIRI: RINCIAN --}}
            <div class="md:col-span-2 space-y-4">
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                    <h3 class="font-bold text-gray-700 border-b pb-2 mb-4">Rincian Pesanan</h3>
                    
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-gray-500">Produk</span>
                        <span class="font-bold text-gray-900">{{ $item['name'] }}</span>
                    </div>
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-gray-500">No. Pelanggan</span>
                        <span class="font-mono font-bold text-gray-900 bg-gray-100 px-2 py-1 rounded">{{ $item['customer_no'] }}</span>
                    </div>
                    <div class="flex justify-between items-center mt-4 pt-4 border-t">
                        <span class="text-gray-800 font-bold">Total Tagihan</span>
                        <span class="text-2xl font-extrabold text-blue-600">Rp {{ number_format($item['price'], 0, ',', '.') }}</span>
                    </div>
                </div>

                {{-- METODE PEMBAYARAN --}}
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                    <h3 class="font-bold text-gray-700 mb-4">Pilih Pembayaran</h3>
                    
                    <form action="{{ route('ppob.checkout.store') }}" method="POST" id="ppob-form">
                        @csrf
                        
                        {{-- 1. SALDO --}}
                        <div class="mb-4">
                            <label class="flex items-center p-4 border rounded-lg cursor-pointer hover:bg-gray-50 {{ $user->saldo < $item['price'] ? 'opacity-50' : '' }}">
                                <input type="radio" name="payment_method" value="saldo" class="mr-3" {{ $user->saldo < $item['price'] ? 'disabled' : '' }}>
                                <div class="flex-1">
                                    <span class="font-bold block">Saldo Akun</span>
                                    <span class="text-sm text-gray-500">Sisa: Rp {{ number_format($user->saldo) }}</span>
                                </div>
                                @if($user->saldo < $item['price'])
                                    <span class="text-xs text-red-500 font-bold">Saldo Kurang</span>
                                @endif
                            </label>
                        </div>

                        {{-- 2. TRIPAY --}}
                        @if(isset($paymentChannels['tripay']))
                            <p class="text-sm font-bold text-gray-500 mb-2">Virtual Account & E-Wallet</p>
                            @foreach($paymentChannels['tripay'] as $channel)
                                <label class="flex items-center p-3 border rounded-lg cursor-pointer mb-2 hover:bg-gray-50">
                                    <input type="radio" name="payment_method" value="{{ $channel['code'] }}" class="mr-3">
                                    <img src="{{ $channel['icon_url'] }}" class="h-6 w-auto mr-2">
                                    <span class="font-medium">{{ $channel['name'] }}</span>
                                </label>
                            @endforeach
                        @endif

                        {{-- 3. DOKU --}}
                        {{-- Sama seperti tripay logicnya --}}

                        <button type="submit" class="w-full bg-blue-600 text-white font-bold py-4 rounded-xl mt-6 hover:bg-blue-700 transition shadow-lg">
                            Bayar Sekarang
                        </button>
                    </form>
                </div>
            </div>

            {{-- KOLOM KANAN: INFO USER --}}
            <div>
                <div class="bg-blue-50 p-4 rounded-xl border border-blue-100">
                    <h4 class="font-bold text-blue-800 mb-2">Informasi Akun</h4>
                    <p class="text-sm text-gray-600">{{ $user->nama_lengkap }}</p>
                    <p class="text-sm text-gray-600">{{ $user->email }}</p>
                    <p class="text-sm text-gray-600">{{ $user->no_wa }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection