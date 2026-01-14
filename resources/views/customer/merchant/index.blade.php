@extends('layouts.customer')

@section('title', 'Kelola Toko DANA')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    {{-- HEADER PAGE --}}
    <div class="md:flex md:items-center md:justify-between mb-8">
        <div class="flex-1 min-w-0">
            <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
                Kelola Toko DANA
            </h2>
            <p class="mt-1 text-sm text-gray-500">
                Daftar toko yang terhubung dengan Akun DANA Bisnis Anda.
            </p>
        </div>
        <div class="mt-4 flex md:mt-0 md:ml-4">
            <a href="{{ route('customer.merchant.create') }}" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <i class="fas fa-plus mr-2"></i> Tambah Toko Baru
            </a>
        </div>
    </div>

    {{-- ALERT MESSAGES --}}
    @if(session('success'))
        <div class="rounded-md bg-green-50 p-4 mb-6 border border-green-200 shadow-sm">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-check-circle text-green-400"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
                </div>
            </div>
        </div>
    @endif

    {{-- LIST TOKO (GRID) --}}
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">

        @forelse($shops as $shop)
            <div class="bg-white overflow-hidden shadow rounded-lg border border-gray-200 hover:shadow-md transition-shadow duration-300">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            @php
                                // LOGIKA LOGO: Ambil dari Auth (Store Utama) -> Fallback ke Shop DANA -> Null
                                $logoUrl = null;
                                if (auth()->user()->store_logo_path) {
                                    $logoUrl = Storage::url(auth()->user()->store_logo_path);
                                } elseif ($shop->logo_path) {
                                    $logoUrl = Storage::url($shop->logo_path);
                                }
                            @endphp

                            @if($logoUrl)
                                <img class="h-12 w-12 rounded-full object-cover border border-gray-300"
                                     src="{{ $logoUrl }}"
                                     alt="{{ $shop->main_name }}">
                            @else
                                <span class="h-12 w-12 rounded-full bg-gray-100 flex items-center justify-center border border-gray-300">
                                    <i class="fas fa-store text-gray-400"></i>
                                </span>
                            @endif
                        </div>
                        <div class="ml-4 w-0 flex-1">
                            <h3 class="text-lg font-medium text-gray-900 truncate">
                                {{ $shop->main_name }}
                            </h3>
                            <p class="text-sm text-gray-500 truncate">
                                ID: {{ $shop->external_shop_id }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-50 px-5 py-4 border-t border-gray-100">
                    <dl class="grid grid-cols-1 gap-x-4 gap-y-4 sm:grid-cols-2">
                        <div class="sm:col-span-1">
                            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">Status DANA</dt>
                            <dd class="mt-1">
                                @if($shop->dana_status == 'SUCCESS')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        <i class="fas fa-check-circle mr-1"></i> Aktif
                                    </span>
                                @elseif($shop->dana_status == 'FAILED')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        <i class="fas fa-times-circle mr-1"></i> Gagal
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                        <i class="fas fa-clock mr-1"></i> Pending
                                    </span>
                                @endif
                            </dd>
                        </div>
                        <div class="sm:col-span-1">
                            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">Shop ID</dt>
                            <dd class="mt-1 text-sm text-gray-900 font-mono">
                                {{ $shop->dana_shop_id ?? '-' }}
                            </dd>
                        </div>

                        @if($shop->dana_status == 'FAILED')
                            <div class="sm:col-span-2">
                                <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">Error Message</dt>
                                <dd class="mt-1 text-xs text-red-600 bg-red-50 p-2 rounded border border-red-100 break-words">
                                    {{ $shop->dana_response_msg ?? 'Unknown Error' }}
                                </dd>
                            </div>
                        @endif
                    </dl>
                </div>

                {{-- ACTION BUTTONS --}}
                <div class="bg-gray-50 px-5 py-3 border-t border-gray-200 flex justify-end space-x-3">
                    {{-- FIX: Menggunakan Route Edit yang benar --}}
                    <a href="{{ route('customer.merchant.edit', $shop->id) }}" class="text-sm font-medium text-blue-600 hover:text-blue-500">
                        Update Data
                    </a>
                </div>
            </div>
        @empty
            {{-- EMPTY STATE (Jika belum ada toko) --}}
            <div class="sm:col-span-3 text-center py-12 bg-white rounded-lg border border-dashed border-gray-300">
                <i class="fas fa-store-slash text-4xl text-gray-300 mb-4"></i>
                <h3 class="text-lg font-medium text-gray-900">Belum ada Toko</h3>
                <p class="mt-1 text-sm text-gray-500">Mulai jualan dengan mendaftarkan toko Anda ke DANA.</p>
                <div class="mt-6">
                    <a href="{{ route('customer.merchant.create') }}" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                        <i class="fas fa-plus mr-2"></i> Buat Toko Sekarang
                    </a>
                </div>
            </div>
        @endforelse

    </div>
</div>
@endsection
