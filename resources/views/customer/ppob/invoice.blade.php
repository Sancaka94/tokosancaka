@extends('layouts.marketplace')

@section('content')
<div class="bg-gray-50 min-h-screen py-10">
    <div class="container mx-auto px-4 max-w-2xl">
        
        {{-- Tombol Kembali --}}
        <a href="{{ route('customer.dashboard') }}" class="inline-flex items-center text-gray-500 hover:text-blue-600 mb-6 transition">
            <i class="fas fa-arrow-left mr-2"></i> Kembali ke Dashboard
        </a>

        <div class="bg-white rounded-2xl shadow-lg overflow-hidden border border-gray-200 relative">
            
            {{-- Hiasan Atas --}}
            <div class="h-2 bg-blue-600 w-full"></div>

            <div class="p-8">
                {{-- Header Invoice --}}
                <div class="text-center mb-8">
                    @if(in_array($transaction->status, ['Success', 'Processing']))
                        <div class="w-16 h-16 bg-green-100 text-green-600 rounded-full flex items-center justify-center mx-auto mb-4 text-2xl">
                            <i class="fas fa-check"></i>
                        </div>
                        <h2 class="text-2xl font-bold text-gray-800">Pembayaran Berhasil</h2>
                        <p class="text-gray-500">Transaksi Anda sedang diproses</p>
                    @elseif($transaction->status == 'Pending')
                        <div class="w-16 h-16 bg-yellow-100 text-yellow-600 rounded-full flex items-center justify-center mx-auto mb-4 text-2xl">
                            <i class="fas fa-clock"></i>
                        </div>
                        <h2 class="text-2xl font-bold text-gray-800">Menunggu Pembayaran</h2>
                        <p class="text-gray-500">Silakan selesaikan pembayaran Anda</p>
                    @else
                        <div class="w-16 h-16 bg-red-100 text-red-600 rounded-full flex items-center justify-center mx-auto mb-4 text-2xl">
                            <i class="fas fa-times"></i>
                        </div>
                        <h2 class="text-2xl font-bold text-gray-800">Transaksi Gagal</h2>
                        <p class="text-gray-500">Mohon hubungi admin jika ada kendala</p>
                    @endif
                </div>

                {{-- Detail Utama --}}
                <div class="border-t border-b border-dashed border-gray-200 py-6 mb-6">
                    <div class="flex justify-between mb-3">
                        <span class="text-gray-500">No. Invoice</span>
                        <span class="font-mono font-bold text-gray-800">{{ $transaction->order_id }}</span>
                    </div>
                    <div class="flex justify-between mb-3">
                        <span class="text-gray-500">Tanggal</span>
                        <span class="font-medium text-gray-800">{{ $transaction->created_at->format('d M Y H:i') }}</span>
                    </div>
                    <div class="flex justify-between mb-3">
                        <span class="text-gray-500">Metode Bayar</span>
                        <span class="font-medium text-gray-800 uppercase">{{ $transaction->payment_method }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Status</span>
                        @php
                            $badgeColor = match($transaction->status) {
                                'Success' => 'bg-green-100 text-green-700',
                                'Processing' => 'bg-blue-100 text-blue-700',
                                'Pending' => 'bg-yellow-100 text-yellow-700',
                                default => 'bg-red-100 text-red-700'
                            };
                        @endphp
                        <span class="{{ $badgeColor }} px-3 py-1 rounded-full text-xs font-bold uppercase">
                            {{ $transaction->status }}
                        </span>
                    </div>
                </div>

                {{-- Detail Produk --}}
                <div class="bg-gray-50 rounded-xl p-5 mb-6">
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Rincian Produk</p>
                    <div class="flex justify-between items-start mb-2">
                        <div>
                            <h3 class="font-bold text-gray-800 text-lg">{{ $transaction->buyer_sku_code }}</h3> {{-- Bisa diganti nama produk jika ada relasi --}}
                            <p class="text-sm text-gray-500">ID Pel: <span class="font-mono text-gray-700">{{ $transaction->customer_no }}</span></p>
                        </div>
                        <p class="font-bold text-lg text-gray-800">Rp {{ number_format($transaction->selling_price, 0, ',', '.') }}</p>
                    </div>

                    {{-- Tampilkan SN / Token Listrik Jika Sukses --}}
                    @if($transaction->sn)
                        <div class="mt-4 pt-4 border-t border-gray-200">
                            <p class="text-xs text-gray-500 mb-1">Serial Number / Token:</p>
                            <div class="bg-white border border-gray-300 p-3 rounded font-mono text-center font-bold text-lg tracking-widest text-blue-600 select-all">
                                {{ $transaction->sn }}
                            </div>
                            <p class="text-[10px] text-center text-gray-400 mt-1">Salin kode di atas untuk digunakan</p>
                        </div>
                    @endif
                </div>

                {{-- Tombol Aksi --}}
                <div class="flex gap-3">
                    @if($transaction->payment_method !== 'saldo' && $transaction->status == 'Pending' && $transaction->payment_url)
                        <a href="{{ $transaction->payment_url }}" target="_blank" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-xl text-center transition shadow-lg">
                            Bayar Sekarang
                        </a>
                    @endif
                    
                    <button onclick="window.print()" class="flex-1 border border-gray-300 hover:bg-gray-50 text-gray-700 font-bold py-3 rounded-xl text-center transition">
                        <i class="fas fa-print mr-2"></i> Cetak Struk
                    </button>
                </div>

            </div>
        </div>

        {{-- Bantuan --}}
        <div class="text-center mt-8">
            <p class="text-sm text-gray-500">Butuh bantuan? Hubungi <a href="#" class="text-blue-600 font-bold">Customer Service</a></p>
        </div>

    </div>
</div>
@endsection