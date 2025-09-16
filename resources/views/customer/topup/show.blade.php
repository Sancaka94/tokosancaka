@extends('layouts.customer')

@section('content')
<div class="max-w-2xl mx-auto">
    {{-- Tombol Kembali --}}
    <div class="mb-6">
        <a href="{{ route('customer.topup.index') }}" class="text-blue-600 hover:text-blue-800 hover:underline flex items-center">
            <i class="fas fa-arrow-left mr-2"></i>
            Kembali ke Riwayat Top Up
        </a>
    </div>

    {{-- Kotak Detail Transaksi --}}
    <div class="bg-white rounded-lg shadow-lg p-6 md:p-8">
        {{-- Header --}}
        <div class="border-b pb-4 mb-4 text-center">
            <h3 class="text-2xl font-semibold text-gray-800">Detail Transaksi</h3>
            <p class="text-gray-500">ID Transaksi: {{ $topUp->transaction_id ?? 'N/A' }}</p>
        </div>

        {{-- Detail --}}
        <div class="space-y-4">
            <div class="flex justify-between items-center">
                <span class="text-gray-600">Tanggal Permintaan:</span>
                <span class="font-medium text-gray-800">
                    @if(isset($topUp->created_at))
                        {{ $topUp->created_at->format('d F Y, H:i') }}
                    @else
                        Tidak tersedia
                    @endif
                </span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-gray-600">Metode Pembayaran:</span>
                <span class="font-medium text-gray-800">{{ ucwords(str_replace('_', ' ', $topUp->payment_method ?? 'N/A')) }}</span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-gray-600">Status:</span>
                @php $status = $topUp->status ?? 'failed'; @endphp
                <span class="px-3 py-1 text-sm font-semibold leading-tight rounded-full
                    @if($status == 'success') bg-green-100 text-green-700 @endif
                    @if($status == 'pending') bg-yellow-100 text-yellow-700 @endif
                    @if($status == 'failed') bg-red-100 text-red-700 @endif
                ">
                    {{ ucfirst($status) }}
                </span>
            </div>
        </div>

        {{-- Separator --}}
        <div class="border-t my-6"></div>

        {{-- Total --}}
        <div class="flex justify-between items-center">
            <span class="text-lg font-semibold text-gray-700">Total Top Up:</span>
            <span class="text-2xl font-bold text-green-600">Rp {{ number_format($topUp->amount ?? 0, 0, ',', '.') }}</span>
        </div>

        <div class="border-t my-6"></div>

        @if($topUp->payment_url)
            @php
                $method = strtoupper($topUp->payment_method);
                $url    = $topUp->payment_url;
                $virtualAccounts = [
                    'PERMATAVA','BNIVA','BRIVA','MANDIRIVA','BCAVA','MUAMALATVA',
                    'CIMBVA','BSIVA','OCBCVA','DANAMONVA','OTHERBANKVA'
                ];
            @endphp

            <div class="space-y-4 text-center">
                <h4 class="text-xl font-semibold text-gray-800">Instruksi Pembayaran:</h4>

                @if(str_contains($method, 'QRIS'))
                    <p>Scan QR berikut menggunakan aplikasi pembayaran Anda:</p>
                    <img src="{{ $url }}" alt="QRIS Payment" class="mx-auto" style="max-width: 250px;">
                @elseif(in_array($method, $virtualAccounts))
                    <p>Gunakan Virtual Account berikut untuk pembayaran:</p>
                    <strong class="text-lg">{{ $url }}</strong>
                @else
                    <p>Silakan klik tombol di bawah untuk melanjutkan pembayaran:</p>
                    <a href="{{ $url }}" target="_blank">
                        <button class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                            Bayar Sekarang
                        </button>
                    </a>
                @endif
            </div>
        @endif

    </div>
</div>


@endsection
