@extends('layouts.admin')

@section('title', 'Scan WhatsApp')

@section('content')
<div class="container mx-auto px-4 py-8">

    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Koneksi WhatsApp Gateway</h1>
        <p class="text-gray-600 text-sm">Kelola koneksi server WhatsApp Anda di sini.</p>
    </div>

    <div class="max-w-lg mx-auto bg-white rounded-xl shadow-lg overflow-hidden border border-gray-100">

        <div class="bg-gray-50 px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <h2 class="font-semibold text-gray-700">Status Perangkat</h2>
            @if($isConnected)
                <span class="px-3 py-1 text-xs font-semibold text-green-700 bg-green-100 rounded-full">Online</span>
            @else
                <span class="px-3 py-1 text-xs font-semibold text-yellow-700 bg-yellow-100 rounded-full">Offline</span>
            @endif
        </div>

        <div class="p-8 text-center">

            @if($isConnected)
                <div class="mb-6">
                    <div class="inline-flex items-center justify-center w-24 h-24 bg-green-100 rounded-full mb-4">
                        <svg class="w-12 h-12 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-2">WhatsApp Terhubung!</h3>
                    <p class="text-gray-600">Server Anda siap mengirim pesan notifikasi otomatis.</p>
                </div>

                <a href="{{ route('admin.pesanan.index') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 active:bg-blue-900 focus:outline-none focus:border-blue-900 focus:ring ring-blue-300 disabled:opacity-25 transition ease-in-out duration-150">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    Kembali ke Pesanan
                </a>

            @else
                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6 text-left rounded">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-yellow-700">
                                Buka WhatsApp di HP > Menu Titik Tiga > Perangkat Tertaut > <strong>Tautkan Perangkat</strong>.
                            </p>
                        </div>
                    </div>
                </div>

                <p class="text-gray-700 font-medium mb-4">{{ $message }}</p>

                @if($qrImage)
                    <div class="inline-block p-4 border-2 border-dashed border-gray-300 rounded-lg bg-gray-50 mb-4">
                        <img src="{{ $qrImage }}" alt="QR Code WhatsApp" class="w-64 h-64 object-contain">
                    </div>
                    <p class="text-xs text-gray-500 mb-6">Scan sebelum QR Code kadaluarsa.</p>
                @endif

                <a href="{{ url()->current() }}" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:outline-none focus:border-green-700 focus:ring ring-green-300 disabled:opacity-25 transition ease-in-out duration-150">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                    Refresh QR Code
                </a>
            @endif

        </div>
    </div>
</div>
@endsection
