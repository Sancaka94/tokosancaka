@extends('layouts.marketplace')

@php
    // --- 1. SETUP DATA KATEGORI (Sesuai List Kamu) ---
    $urlSlug = request()->segment(4); 
    $pageInfo = $pageInfo ?? [];
    $currentSlug = $pageInfo['slug'] ?? $urlSlug ?? 'pulsa'; 

    // Daftar Slug Pascabayar untuk deteksi otomatis halaman
    $postpaidSlugs = [
        'pln-pascabayar', 'pdam', 'bpjs-kesehatan', 'bpjs-ketenagakerjaan', 
        'hp-pascabayar', 'internet-pascabayar', 'tv-pascabayar', 
        'multifinance', 'pbb', 'samsat', 'gas-negara', 'pln-nontaglis'
    ];
    
    $isPostpaid = ($pageInfo['is_postpaid'] ?? false) || in_array($currentSlug, $postpaidSlugs);
    $pageTitle = $pageInfo['title'] ?? ucwords(str_replace('-', ' ', $currentSlug));

    // --- ARRAY MENU PRABAYAR (LENGKAP) ---
    $prepaidMenus = [
        ['slug' => 'pulsa', 'name' => 'Pulsa', 'icon' => 'fa-mobile-alt', 'style' => 'text-red-500 bg-red-50 border-red-100'],
        ['slug' => 'data', 'name' => 'Paket Data', 'icon' => 'fa-wifi', 'style' => 'text-blue-500 bg-blue-50 border-blue-100'],
        ['slug' => 'pln-token', 'name' => 'Token PLN', 'icon' => 'fa-bolt', 'style' => 'text-yellow-500 bg-yellow-50 border-yellow-100'],
        ['slug' => 'e-money', 'name' => 'E-Money', 'icon' => 'fa-wallet', 'style' => 'text-purple-500 bg-purple-50 border-purple-100'],
        ['slug' => 'voucher-game', 'name' => 'Games', 'icon' => 'fa-gamepad', 'style' => 'text-indigo-500 bg-indigo-50 border-indigo-100'],
        ['slug' => 'voucher', 'name' => 'Voucher', 'icon' => 'fa-ticket-alt', 'style' => 'text-pink-500 bg-pink-50 border-pink-100'],
        ['slug' => 'paket-sms-telpon', 'name' => 'SMS & Telpon', 'icon' => 'fa-phone-volume', 'style' => 'text-green-500 bg-green-50 border-green-100'],
        ['slug' => 'masa-aktif', 'name' => 'Masa Aktif', 'icon' => 'fa-hourglass-half', 'style' => 'text-orange-500 bg-orange-50 border-orange-100'],
        ['slug' => 'aktivasi-voucher', 'name' => 'Akt. Voucher', 'icon' => 'fa-barcode', 'style' => 'text-gray-600 bg-gray-50 border-gray-200'],
        ['slug' => 'aktivasi-perdana', 'name' => 'Akt. Perdana', 'icon' => 'fa-sim-card', 'style' => 'text-slate-600 bg-slate-50 border-slate-200'],
        ['slug' => 'streaming', 'name' => 'Streaming', 'icon' => 'fa-play-circle', 'style' => 'text-red-600 bg-red-50 border-red-100'],
        ['slug' => 'tv', 'name' => 'TV Prabayar', 'icon' => 'fa-tv', 'style' => 'text-teal-500 bg-teal-50 border-teal-100'],
        ['slug' => 'china-topup', 'name' => 'China Topup', 'icon' => 'fa-globe-asia', 'style' => 'text-red-500 bg-red-50 border-red-100'],
        ['slug' => 'malaysia-topup', 'name' => 'Mly. Topup', 'icon' => 'fa-globe-asia', 'style' => 'text-blue-600 bg-blue-50 border-blue-100'],
        ['slug' => 'philippines-topup', 'name' => 'Phil. Topup', 'icon' => 'fa-globe-asia', 'style' => 'text-yellow-500 bg-yellow-50 border-yellow-100'],
        ['slug' => 'singapore-topup', 'name' => 'S\'pore Topup', 'icon' => 'fa-globe-asia', 'style' => 'text-red-600 bg-red-50 border-red-100'],
        ['slug' => 'thailand-topup', 'name' => 'Thai Topup', 'icon' => 'fa-globe-asia', 'style' => 'text-blue-500 bg-blue-50 border-blue-100'],
        ['slug' => 'vietnam-topup', 'name' => 'Viet. Topup', 'icon' => 'fa-globe-asia', 'style' => 'text-red-500 bg-red-50 border-red-100'],
        ['slug' => 'bundling', 'name' => 'Bundling', 'icon' => 'fa-box-open', 'style' => 'text-cyan-500 bg-cyan-50 border-cyan-100'],
        ['slug' => 'gas', 'name' => 'Gas Token', 'icon' => 'fa-burn', 'style' => 'text-orange-600 bg-orange-50 border-orange-100'],
        ['slug' => 'esim', 'name' => 'eSIM', 'icon' => 'fa-qrcode', 'style' => 'text-indigo-600 bg-indigo-50 border-indigo-100'],
        ['slug' => 'media-sosial', 'name' => 'Media Sosial', 'icon' => 'fa-thumbs-up', 'style' => 'text-blue-500 bg-blue-50 border-blue-100'],
        ['slug' => 'telkomsel-omni', 'name' => 'Tsel Omni', 'icon' => 'fa-tower-cell', 'style' => 'text-red-600 bg-red-50 border-red-100'],
        ['slug' => 'indosat-only4u', 'name' => 'Isat Only4u', 'icon' => 'fa-star', 'style' => 'text-yellow-500 bg-yellow-50 border-yellow-100'],
        ['slug' => 'tri-cuanmax', 'name' => 'Tri CuanMax', 'icon' => 'fa-percent', 'style' => 'text-purple-600 bg-purple-50 border-purple-100'],
        ['slug' => 'xl-axis-cuanku', 'name' => 'XL Cuanku', 'icon' => 'fa-gift', 'style' => 'text-blue-600 bg-blue-50 border-blue-100'],
        ['slug' => 'by-u', 'name' => 'by.U', 'icon' => 'fa-ghost', 'style' => 'text-orange-500 bg-orange-50 border-orange-100'],
    ];

    // --- ARRAY MENU PASCABAYAR ---
    $postpaidMenus = [
        ['slug' => 'pln-pascabayar', 'name' => 'PLN Pasca', 'icon' => 'fa-file-invoice-dollar', 'style' => 'text-yellow-600 bg-yellow-50 border-yellow-100'],
        ['slug' => 'pdam', 'name' => 'PDAM', 'icon' => 'fa-faucet', 'style' => 'text-cyan-600 bg-cyan-50 border-cyan-100'],
        ['slug' => 'bpjs-kesehatan', 'name' => 'BPJS Kes.', 'icon' => 'fa-heartbeat', 'style' => 'text-green-600 bg-green-50 border-green-100'],
        ['slug' => 'bpjs-ketenagakerjaan', 'name' => 'BPJS TK', 'icon' => 'fa-hard-hat', 'style' => 'text-green-700 bg-green-50 border-green-100'],
        ['slug' => 'hp-pascabayar', 'name' => 'HP Pasca', 'icon' => 'fa-mobile-screen', 'style' => 'text-blue-600 bg-blue-50 border-blue-100'],
        ['slug' => 'internet-pascabayar', 'name' => 'Internet', 'icon' => 'fa-network-wired', 'style' => 'text-indigo-600 bg-indigo-50 border-indigo-100'],
        ['slug' => 'tv-pascabayar', 'name' => 'TV Kabel', 'icon' => 'fa-satellite-dish', 'style' => 'text-pink-600 bg-pink-50 border-pink-100'],
        ['slug' => 'multifinance', 'name' => 'Cicilan', 'icon' => 'fa-money-bill-wave', 'style' => 'text-emerald-600 bg-emerald-50 border-emerald-100'],
        ['slug' => 'pbb', 'name' => 'Pajak PBB', 'icon' => 'fa-building', 'style' => 'text-gray-600 bg-gray-50 border-gray-200'],
        ['slug' => 'samsat', 'name' => 'SAMSAT', 'icon' => 'fa-car', 'style' => 'text-blue-700 bg-blue-50 border-blue-100'],
        ['slug' => 'gas-negara', 'name' => 'Gas Negara', 'icon' => 'fa-fire', 'style' => 'text-orange-600 bg-orange-50 border-orange-100'],
        ['slug' => 'pln-nontaglis', 'name' => 'PLN NonTag', 'icon' => 'fa-plug', 'style' => 'text-yellow-600 bg-yellow-50 border-yellow-100'],
    ];

    // --- LOGIKA LABEL INPUT DINAMIS ---
    $dynamicLabel = 'Nomor Pelanggan';
    $dynamicPlaceholder = 'Masukkan Nomor...';
    
    if (str_contains($currentSlug, 'game')) {
        $dynamicLabel = 'User ID / Zone ID';
        $dynamicPlaceholder = '12345678 (1234)';
    } elseif (str_contains($currentSlug, 'pulsa') || str_contains($currentSlug, 'data') || str_contains($currentSlug, 'paket') || str_contains($currentSlug, 'hp')) {
        $dynamicLabel = 'Nomor HP';
        $dynamicPlaceholder = '08xxxxxxxxxx';
    } elseif (str_contains($currentSlug, 'pln')) {
        $dynamicLabel = 'No. Meter / ID Pelanggan';
        $dynamicPlaceholder = '5123xxxxxxxx';
    }
@endphp

@section('title', $pageTitle)

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
    <style>
        .scrollbar-hide::-webkit-scrollbar { display: none; }
        .scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
        
        .custom-scrollbar::-webkit-scrollbar { width: 8px; height: 8px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 10px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

        .swiper-pagination-bullet-active { background-color: #ef4444 !important; width: 20px; border-radius: 5px; }
        
        .swiper-button-next, .swiper-button-prev { color: white; background-color: rgba(0,0,0,0.3); width: 40px; height: 40px; border-radius: 50%; transition: all 0.3s; }
        .swiper-button-next:hover, .swiper-button-prev:hover { background-color: rgba(0,0,0,0.6); }
        .swiper-button-next:after, .swiper-button-prev:after { font-size: 16px; font-weight: bold; }

        .loader { border: 3px solid #f3f3f3; border-radius: 50%; border-top: 3px solid #ef4444; width: 20px; height: 20px; animation: spin 1s linear infinite; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }

        /* WARNA BG & TEXT SESUAI GAMBAR */
        .bg-blue-50 { background-color: #eff6ff; } .text-blue-600 { color: #2563eb; } .border-blue-400 { border-color: #60a5fa; }
        .bg-green-50 { background-color: #f0fdf4; } .text-green-600 { color: #16a34a; } .border-green-400 { border-color: #4ade80; }
        .bg-yellow-50 { background-color: #fefce8; } .text-yellow-500 { color: #eab308; } .border-yellow-400 { border-color: #facc15; }
        .bg-orange-50 { background-color: #fff7ed; } .text-orange-500 { color: #f97316; } .border-orange-400 { border-color: #fb923c; }
        .bg-cyan-50 { background-color: #ecfeff; } .text-cyan-500 { color: #06b6d4; } .border-cyan-400 { border-color: #22d3ee; }
        .bg-purple-50 { background-color: #faf5ff; } .text-purple-600 { color: #9333ea; } .border-purple-400 { border-color: #c084fc; }
        .bg-red-50 { background-color: #fef2f2; } .text-red-500 { color: #ef4444; } .border-red-400 { border-color: #f87171; }
        .bg-pink-50 { background-color: #fdf2f8; } .text-pink-500 { color: #ec4899; } .border-pink-400 { border-color: #f472b6; }
        .bg-indigo-50 { background-color: #eef2ff; } .text-indigo-500 { color: #6366f1; }
        .bg-teal-50 { background-color: #f0fdfa; } .text-teal-500 { color: #14b8a6; }
        .bg-slate-50 { background-color: #f8fafc; } .text-slate-600 { color: #475569; }
        .bg-gray-50 { background-color: #f9fafb; } .text-gray-600 { color: #4b5563; }
        .bg-emerald-50 { background-color: #ecfdf5; } .text-emerald-600 { color: #059669; }
        
    </style>
@endpush

@section('content')
<div class="bg-gray-50 min-h-screen pb-20">
    <div class="container mx-auto px-4 pt-6 relative z-10 max-w-7xl">
        
        {{-- HERO SECTION --}}
        <section class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-8">
            <div class="lg:col-span-2 rounded-2xl shadow-lg overflow-hidden relative group h-[200px] md:h-[350px] lg:h-[420px]">
                <div class="swiper heroSwiper w-full h-full">
                    <div class="swiper-wrapper">
                        @forelse($banners ?? [] as $banner)
                            <div class="swiper-slide"><img src="{{ asset('public/storage/' . $banner->image) }}" class="w-full h-full object-fill" alt="Promo"></div>
                        @empty
                            <div class="swiper-slide"><img src="https://placehold.co/800x420/ee4d2d/ffffff?text=Promo+Sancaka+1" class="w-full h-full object-fill"></div>
                        @endforelse
                    </div>
                    <div class="swiper-pagination"></div>
                </div>
            </div>
            <div class="grid grid-cols-2 lg:grid-cols-1 lg:grid-rows-2 gap-4 h-auto lg:h-[420px]">
                <div class="rounded-2xl shadow-lg overflow-hidden h-[100px] md:h-[170px] lg:h-full w-full">
                    <img src="{{ isset($settings['banner_2']) ? asset('public/storage/' . $settings['banner_2']) : 'https://placehold.co/400x200/fbbf24/ffffff?text=Promo+1' }}" class="w-full h-full object-fill hover:scale-105 transition duration-500">
                </div>
                <div class="rounded-2xl shadow-lg overflow-hidden h-[100px] md:h-[170px] lg:h-full w-full">
                    <img src="{{ isset($settings['banner_3']) ? asset('public/storage/' . $settings['banner_3']) : 'https://placehold.co/400x200/10b981/ffffff?text=Promo+2' }}" class="w-full h-full object-fill hover:scale-105 transition duration-500">
                </div>
            </div>
        </section>

        {{-- === KATEGORI MENU SECTION (REVISI SESUAI GAMBAR) === --}}
        <section class="mb-8" data-aos="fade-up">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
                
                {{-- 1. TAB HEADER (Prabayar vs Pascabayar) --}}
                <div class="flex border-b border-gray-200">
                    <button onclick="switchTab('prepaid')" id="tab-prepaid" class="flex-1 py-4 text-center font-bold text-sm md:text-base border-b-2 border-blue-600 text-blue-600 transition hover:bg-gray-50 flex items-center justify-center gap-2">
                        <i class="fas fa-mobile-alt"></i> Prabayar / Topup
                    </button>
                    <button onclick="switchTab('postpaid')" id="tab-postpaid" class="flex-1 py-4 text-center font-bold text-sm md:text-base border-b-2 border-transparent text-gray-500 hover:text-gray-700 transition hover:bg-gray-50 flex items-center justify-center gap-2">
                        <i class="fas fa-file-invoice"></i> Pascabayar / Tagihan
                    </button>
                </div>

                <div class="p-6 md:p-8">
                    {{-- 2. KONTEN PRABAYAR (GRID 8 KOLOM) --}}
                    <div id="content-prepaid" class="animate-fade">
                        <div class="grid grid-cols-4 sm:grid-cols-6 md:grid-cols-8 gap-y-8 gap-x-4" id="grid-prepaid-wrapper">
                            @foreach($prepaidMenus as $index => $menu)
                                {{-- Item Menu --}}
                                <a href="{{ url('/etalase/ppob/digital/' . $menu['slug']) }}" 
                                   class="menu-item-prepaid group flex flex-col items-center gap-3 {{ $index >= 16 ? 'hidden' : '' }}">
                                    
                                    {{-- Icon Container (Rounded) --}}
                                    <div class="w-12 h-12 md:w-14 md:h-14 rounded-2xl flex items-center justify-center transition border {{ $menu['style'] }} {{ $currentSlug == $menu['slug'] ? 'ring-2 ring-offset-2 ring-blue-400' : 'group-hover:scale-105' }}">
                                        <i class="fas {{ $menu['icon'] }} text-xl md:text-2xl"></i>
                                    </div>
                                    
                                    {{-- Text Label --}}
                                    <span class="text-[11px] md:text-xs font-medium text-gray-600 text-center leading-tight px-1">{{ $menu['name'] }}</span>
                                </a>
                            @endforeach
                        </div>
                        
                        {{-- Tombol Lihat Semua (Accordion) --}}
                        @if(count($prepaidMenus) > 16)
                            <div class="mt-8 text-center">
                                <button onclick="toggleExpand('prepaid')" id="btn-expand-prepaid" class="inline-flex items-center gap-2 px-6 py-2 bg-blue-50 text-blue-600 text-xs font-bold rounded-full hover:bg-blue-100 transition">
                                    <span>Lihat Semua Layanan</span> 
                                    <i class="fas fa-chevron-down text-[10px]"></i>
                                </button>
                            </div>
                        @endif
                    </div>

                    {{-- 3. KONTEN PASCABAYAR (GRID 8 KOLOM) --}}
                    <div id="content-postpaid" class="hidden animate-fade">
                        <div class="grid grid-cols-4 sm:grid-cols-6 md:grid-cols-8 gap-y-8 gap-x-4">
                            @foreach($postpaidMenus as $menu)
                                <a href="{{ url('/etalase/ppob/digital/' . $menu['slug']) }}" class="group flex flex-col items-center gap-3">
                                    <div class="w-12 h-12 md:w-14 md:h-14 rounded-2xl flex items-center justify-center transition border {{ $menu['style'] }} {{ $currentSlug == $menu['slug'] ? 'ring-2 ring-offset-2 ring-blue-400' : 'group-hover:scale-105' }}">
                                        <i class="fas {{ $menu['icon'] }} text-xl md:text-2xl"></i>
                                    </div>
                                    <span class="text-[11px] md:text-xs font-medium text-gray-600 text-center leading-tight px-1">{{ $menu['name'] }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>

                </div>
            </div>
        </section>

        {{-- INPUT SECTION --}}
        <section class="mb-8" id="transaction-section">
            <div class="bg-white rounded-2xl shadow-md p-6 border-l-4 border-blue-600 flex flex-col md:flex-row gap-6">
                {{-- Input Area --}}
                <div class="w-full md:w-1/3">
                    <h3 class="font-bold text-gray-800 text-lg mb-4 flex items-center">
                        <i class="fas {{ $pageInfo['icon'] ?? 'fa-edit' }} text-blue-600 mr-2"></i> 
                        {{ $pageTitle }}
                    </h3>

                    {{-- [NEW] TAB PILIHAN KHUSUS PLN (TOKEN / PASCA) --}}
                    {{-- Menggunakan str_contains dengan pengaman null safe --}}
                    @if(str_contains($currentSlug ?? '', 'pln'))
                    <div class="flex p-1 bg-gray-100 rounded-xl mb-4">
                        <a href="{{ url('/etalase/ppob/digital/pln-token') }}" 
                           class="flex-1 text-center py-2 text-sm font-bold rounded-lg transition {{ str_contains($currentSlug ?? '', 'token') ? 'bg-white text-blue-600 shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">
                           Token Listrik
                        </a>
                        <a href="{{ url('/etalase/ppob/digital/pln-pascabayar') }}" 
                           class="flex-1 text-center py-2 text-sm font-bold rounded-lg transition {{ str_contains($currentSlug ?? '', 'pasca') ? 'bg-white text-blue-600 shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">
                           Tagihan PLN
                        </a>
                    </div>
                    @endif
                    
                    <div class="mb-4">
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-2">{{ $pageInfo['input_label'] ?? 'Nomor Tujuan / ID Pelanggan' }}</label>
                        <div class="relative group">
                            <input type="text" id="customer_no" 
                                class="w-full border border-gray-300 rounded-xl px-4 py-3 pl-12 font-bold text-lg text-gray-700 focus:ring-2 focus:ring-blue-500 outline-none transition" 
                                placeholder="{{ $pageInfo['input_place'] ?? 'Masukkan Nomor...' }}">
                            <div id="prefix-icon-container" class="absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400 group-focus-within:text-blue-500 flex items-center justify-center w-6 h-6">
                                <i class="fas fa-keyboard text-lg"></i>
                            </div>
                            <div id="operator-badge" class="absolute right-4 top-1/2 transform -translate-y-1/2 hidden bg-blue-100 text-blue-700 text-xs font-bold px-2 py-1 rounded">
                                -
                            </div>
                        </div>
                    </div>

                    {{-- 1. Tombol Cek PLN Token (Prabayar) --}}
                    @if(request()->is('*pln-token*') || ($currentSlug ?? '') == 'pln-token')
                        <button onclick="cekPlnPrabayar()" id="btn-cek-pln" class="w-full bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-3 px-4 rounded-xl transition shadow flex items-center justify-center gap-2 mb-3">
                            <i class="fas fa-search"></i> Cek Nama Pelanggan
                        </button>
                        <div id="pln_info" class="hidden bg-yellow-50 p-3 rounded-lg border border-yellow-200 text-sm">
                            <div class="flex justify-between"><span class="text-gray-500">Nama:</span><span class="font-bold" id="pln_name">-</span></div>
                            <div class="flex justify-between"><span class="text-gray-500">Daya:</span><span class="font-bold" id="pln_power">-</span></div>
                        </div>
                    @endif

                    {{-- 2. Tombol Cek Tagihan (Pascabayar) - DIPAKSA MUNCUL JIKA $isPostpaid TRUE --}}
                    @if($isPostpaid)
                        <button onclick="cekTagihan()" id="btn-cek-tagihan" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-xl transition flex justify-center items-center gap-2 shadow-lg">
                            <span id="btn-text">Cek Tagihan</span>
                            <div id="loading-spinner" class="loader hidden !border-t-white"></div>
                        </button>
                        <div id="bill_error_msg" class="hidden mt-3 text-red-600 text-sm text-center bg-red-50 p-2 rounded border border-red-200"></div>
                    @endif
                </div>

                {{-- Result Area (Pascabayar) --}}
                @if($isPostpaid)
                    <div class="w-full md:w-2/3 border-t md:border-t-0 md:border-l border-gray-100 md:pl-6 pt-6 md:pt-0">
                        <h4 class="font-bold text-gray-700 mb-4">Rincian Tagihan</h4>
                        <div id="bill_empty" class="flex flex-col items-center justify-center h-40 text-gray-400 bg-gray-50 rounded-xl border border-dashed border-gray-200">
                            <i class="fas fa-receipt text-3xl mb-2"></i>
                            <p class="text-sm">Silakan masukkan ID Pelanggan dan klik Cek Tagihan</p>
                        </div>
                        <div id="bill_result" class="hidden animate-fade-in">
                            <div class="bg-blue-50 p-5 rounded-xl border border-blue-100 mb-4">
                                <div class="grid grid-cols-2 gap-4 text-sm">
                                    <div><p class="text-xs text-gray-500 uppercase">Nama</p><p class="font-bold text-gray-800 text-lg" id="bill_name">-</p></div>
                                    <div><p class="text-xs text-gray-500 uppercase">ID Pelanggan</p><p class="font-bold text-gray-800 text-lg" id="bill_id">-</p></div>
                                </div>
                                <div class="mt-4 pt-4 border-t border-blue-200 flex justify-between items-end">
                                    <span class="text-sm text-gray-600">Total Tagihan</span>
                                    <span class="text-2xl font-extrabold text-blue-600" id="bill_amount">-</span>
                                </div>
                            </div>
                            <button onclick="bayarTagihan()" class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-3 rounded-xl shadow-lg transition">
                                <i class="fas fa-check-circle mr-2"></i> Bayar Sekarang
                            </button>
                        </div>
                    </div>
                @else
                    {{-- Banner Info (Prabayar) --}}
                    <div class="hidden md:flex w-2/3 items-center justify-center bg-blue-50 rounded-xl p-6 text-center text-blue-800">
                        <div>
                            <i class="fas fa-info-circle text-2xl mb-2"></i>
                            <p class="font-medium">Pilih produk di bawah ini untuk melakukan pembelian.</p>
                        </div>
                    </div>
                @endif
            </div>
        </section>

        {{-- FILTER & TABLE (Hanya muncul jika BUKAN Pascabayar) --}}
        @if(!$isPostpaid)
            <div class="lg:hidden mb-6 sticky top-4 z-30" id="mobileDropdownContainer">
                <div class="relative">
                    <button onclick="toggleMobileDropdown()" class="w-full bg-white rounded-xl shadow-md border border-gray-100 px-4 py-3.5 flex items-center justify-between text-left">
                        <span id="mobileCategoryLabel" class="text-sm font-bold text-gray-700 uppercase">SEMUA KATEGORI</span>
                        <i class="fas fa-chevron-down text-gray-500 text-xs transition-transform duration-200" id="dropdownArrow"></i>
                    </button>
                    <div id="mobileCategoryList" class="hidden absolute w-full z-50 bg-white shadow-xl rounded-xl mt-2 border border-gray-100 overflow-hidden">
                        <div class="max-h-60 overflow-y-auto custom-scrollbar">
                            <div onclick="filterCategory('all')" class="px-4 py-3 border-b border-gray-50 hover:bg-blue-50 cursor-pointer text-sm font-bold text-gray-600">SEMUA KATEGORI</div>
                            @foreach($categories ?? [] as $cat)
                                @php $catName = is_object($cat) ? ($cat->slug ?? $cat->name ?? '') : $cat; @endphp
                                <div onclick="filterCategory('{{ $catName }}')" class="px-4 py-3 border-b border-gray-50 hover:bg-blue-50 cursor-pointer text-sm font-bold text-gray-600 uppercase">{{ $catName }}</div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <div class="hidden lg:flex bg-white p-3 rounded-2xl shadow-sm mb-6 overflow-x-auto whitespace-nowrap custom-scrollbar gap-3 border border-gray-100 sticky top-4 z-30 pb-4">
                <button onclick="filterCategory('all')" data-cat="all" class="cat-btn active px-6 py-2.5 rounded-xl font-bold text-sm transition bg-blue-600 text-white shadow-md transform hover:scale-105">SEMUA</button>
                @foreach($categories ?? [] as $cat)
                    @php $catName = is_object($cat) ? ($cat->slug ?? $cat->name ?? '') : $cat; @endphp
                    <button onclick="filterCategory('{{ $catName }}')" data-cat="{{ $catName }}" class="cat-btn px-6 py-2.5 rounded-xl font-bold text-sm transition bg-gray-50 text-gray-600 hover:bg-blue-50 hover:text-blue-600 border border-gray-200">{{ strtoupper($catName) }}</button>
                @endforeach
            </div>

            <div class="mb-4 relative">
                <input type="text" id="searchInput" placeholder="Cari nama produk..." class="w-full py-3 pl-10 pr-4 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-blue-500">
                <i class="fas fa-search absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
            </div>

            <div class="bg-white rounded-2xl shadow-lg overflow-hidden border border-gray-100 min-h-[400px]">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-gray-50 text-gray-500 uppercase text-xs font-bold tracking-wider">
                            <tr><th class="p-5 border-b">Produk</th><th class="p-5 border-b">Keterangan</th><th class="p-5 border-b text-center hidden sm:table-cell">Status</th><th class="p-5 border-b text-right">Harga</th></tr>
                        </thead>
                        <tbody id="productTableBody" class="text-sm divide-y divide-gray-100">
                            @forelse($products ?? [] as $product)
                            <tr class="product-row hover:bg-blue-50/50 transition duration-150 cursor-pointer" data-category="{{ $product->category }}" data-name="{{ strtolower($product->product_name . ' ' . $product->brand . ' ' . $product->buyer_sku_code) }}" onclick="selectProduct('{{ $product->product_name }}', '{{ $product->sell_price }}', '{{ $product->buyer_sku_code }}')">
                                <td class="p-5">
                                    <div class="flex items-center gap-4">
                                        <div class="h-12 w-12 flex-shrink-0 relative bg-white rounded-xl p-1 shadow-sm border border-gray-100">
                                            <img src="{{ get_operator_logo($product->brand) }}" alt="{{ $product->brand }}" class="h-full w-full object-contain" loading="lazy" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                            <span class="hidden h-full w-full items-center justify-center bg-gray-100 text-gray-500 text-[10px] font-bold rounded-lg uppercase">{{ substr($product->brand, 0, 3) }}</span>
                                        </div>
                                        <div><p class="font-bold text-gray-800 text-sm md:text-base">{{ $product->brand }}</p><p class="text-xs text-gray-400 uppercase tracking-wide">{{ $product->category }}</p></div>
                                    </div>
                                </td>
                                <td class="p-5"><div class="font-medium text-gray-700 line-clamp-2">{{ $product->product_name }}</div><div class="text-xs text-gray-400 mt-1 font-mono bg-gray-100 inline-block px-2 py-0.5 rounded">SKU: {{ $product->buyer_sku_code }}</div></td>
                                <td class="p-5 text-center hidden sm:table-cell"><span class="bg-green-100 text-green-700 px-3 py-1 rounded-full text-xs font-bold border border-green-200">Tersedia</span></td>
                                <td class="p-5 text-right"><div class="font-extrabold text-blue-600 text-lg">Rp{{ number_format($product->sell_price, 0, ',', '.') }}</div></td>
                            </tr>
                            @empty
                            <tr><td colspan="4" class="p-10 text-center text-gray-400">Belum ada produk.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        @if(!Auth::check())
        <div class="mt-10 text-center pb-8">
            <a href="{{ route('login') }}" class="inline-flex items-center justify-center px-8 py-3 border border-transparent text-base font-bold rounded-full text-white bg-gradient-to-r from-blue-600 to-blue-500 hover:from-blue-700 hover:to-blue-600 shadow-lg hover:shadow-xl transition transform hover:-translate-y-1"><i class="fas fa-lock mr-2"></i> Login untuk Transaksi</a>
        </div>
        @endif
    </div>
</div>

{{-- MODAL PRABAYAR --}}
<div id="confirmModal" class="fixed inset-0 z-[999] hidden">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity backdrop-blur-sm" onclick="closeModal()"></div>
        <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md w-full">
            <div class="bg-white p-6">
                <h3 class="text-lg leading-6 font-bold text-gray-900 mb-4">Konfirmasi Pembayaran</h3>
                <div class="bg-blue-50 p-4 rounded-xl border border-blue-100 mb-4">
                    <p class="text-xs text-blue-500 uppercase font-bold mb-1">Produk</p>
                    <p class="text-gray-800 font-bold text-lg leading-tight" id="modal_product">-</p>
                </div>
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between border-b border-gray-100 pb-2"><span class="text-gray-500">Nomor Tujuan</span><span class="font-bold text-gray-800 font-mono text-base" id="modal_no">-</span></div>
                    <div class="flex justify-between items-center pt-2"><span class="text-gray-500">Total Harga</span><span class="font-extrabold text-blue-600 text-2xl" id="modal_price">-</span></div>
                </div>
            </div>
            <div class="bg-gray-50 px-6 py-4 flex flex-col-reverse sm:flex-row sm:justify-end gap-3">
                <button type="button" onclick="closeModal()" class="w-full inline-flex justify-center rounded-xl border border-gray-300 shadow-sm px-4 py-3 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:w-auto sm:text-sm">Batal</button>
                    {{-- Ganti jadi BUTTON dan panggil fungsi JS --}}
{{-- 🔥 UPDATE: Tambahkan 'this' di dalam kurung --}}
<button type="button" onclick="processPrepaidCheckout(this)" class="w-full sm:w-auto inline-flex justify-center rounded-xl border border-transparent shadow-lg shadow-blue-200 px-6 py-3 bg-blue-600 text-base font-bold text-white hover:bg-blue-700 sm:text-sm items-center gap-2 transition-all disabled:opacity-50 disabled:cursor-not-allowed">
    Bayar Sekarang <i class="fas fa-arrow-right"></i>
</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
<script>
    var swiper = new Swiper(".heroSwiper", { loop: true, effect: "fade", autoplay: { delay: 4000 }, pagination: { el: ".swiper-pagination", clickable: true }, navigation: { nextEl: ".swiper-button-next", prevEl: ".swiper-button-prev" } });
    const inputNo = document.getElementById('customer_no');

    // --- DETEKSI OPERATOR (JS) ---
    if(inputNo) {
        inputNo.addEventListener('input', function() {
            const val = this.value;
            const operator = detectOperator(val);
            const searchInput = document.getElementById('searchInput');
            const iconContainer = document.getElementById('prefix-icon-container');
            const operatorBadge = document.getElementById('operator-badge');

            if(operator) {
                // Auto filter hanya jika BUKAN pascabayar (karena pasca tidak butuh filter produk)
                @if(!$isPostpaid)
                    if(searchInput) { searchInput.value = operator; filterTable(); }
                @endif

                if(operatorBadge) { operatorBadge.innerText = operator.toUpperCase(); operatorBadge.classList.remove('hidden'); }
                if(iconContainer) { iconContainer.innerHTML = '<i class="fas fa-check-circle text-green-500 text-lg"></i>'; }
            } else {
                @if(!$isPostpaid)
                    if(val.length < 4 && searchInput) { searchInput.value = ''; filterTable(); }
                @endif
                if(operatorBadge) operatorBadge.classList.add('hidden');
                if(iconContainer) iconContainer.innerHTML = '<i class="fas fa-keyboard text-lg"></i>';
            }
        });
    }

    function detectOperator(number) {
        if (!number || number.length < 4) return null;
        const prefix = number.substring(0, 4);
        if (/^08(11|12|13|21|22|23|51|52|53)/.test(prefix)) return 'telkomsel';
        if (/^08(14|15|16|55|56|57|58)/.test(prefix)) return 'indosat';
        if (/^08(17|18|19|59|77|78)/.test(prefix)) return 'xl';
        if (/^08(31|32|33|38)/.test(prefix)) return 'axis';
        if (/^08(95|96|97|98|99)/.test(prefix)) return 'tri';
        if (/^08(81|82|83|84|85|86|87|88|89)/.test(prefix)) return 'smartfren';
        if (!number.startsWith('08') && number.length >= 11) return 'PLN';
        return null;
    }

    // --- PASCABAYAR LOGIC ---
    let inquiryRefId = null;
    function cekTagihan() {
        const no = inputNo.value;
        if(no.length < 5) { alert("Masukkan ID Pelanggan!"); return; }

        const btn = document.getElementById('btn-cek-tagihan');
        const spinner = document.getElementById('loading-spinner');
        const text = document.getElementById('btn-text');
        const resultDiv = document.getElementById('bill_result');
        const emptyDiv = document.getElementById('bill_empty');
        const errorMsg = document.getElementById('bill_error_msg');

        btn.disabled = true; spinner.classList.remove('hidden'); text.innerText = "Mengecek...";
        resultDiv.classList.add('hidden'); emptyDiv.classList.add('hidden'); errorMsg.classList.add('hidden');

        // Deteksi SKU Pasca via URL
        let skuPasca = 'pln'; 
        // Gunakan variable PHP yang sudah di-safe untuk JS
        const currentSlug = '{{ $currentSlug ?? "pulsa" }}';
        
        if(currentSlug.includes('pdam')) skuPasca = 'pdam';
        else if(currentSlug.includes('bpjs')) skuPasca = 'bpjs';
        else if(currentSlug.includes('gas')) skuPasca = 'gas';

        fetch('{{ route("ppob.check.bill") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify({ customer_no: no, sku: skuPasca })
        })
        .then(res => res.json())
        .then(data => {
            btn.disabled = false; spinner.classList.add('hidden'); text.innerText = "Cek Tagihan";
            if(data.status === 'success') {
                document.getElementById('bill_name').innerText = data.customer_name;
                document.getElementById('bill_id').innerText = data.customer_no;
                document.getElementById('bill_amount').innerText = 'Rp ' + parseInt(data.amount).toLocaleString('id-ID');
                inquiryRefId = data.ref_id;
                resultDiv.classList.remove('hidden');
            } else {
                errorMsg.innerText = data.message;
                errorMsg.classList.remove('hidden');
                emptyDiv.classList.remove('hidden');
            }
        })
        .catch(err => {
            btn.disabled = false; spinner.classList.add('hidden'); text.innerText = "Cek Tagihan";
            errorMsg.innerText = "Gagal terhubung ke server."; errorMsg.classList.remove('hidden');
        });
    }

    function bayarTagihan() {
        if(!inquiryRefId) return;
        alert("Silakan login untuk melanjutkan.");
        window.location.href = "{{ route('login') }}";
    }

    function cekPlnPrabayar() {
        const no = inputNo.value;
        if(no.length < 10) { alert("Masukkan Nomor Meter!"); return; }
        const btn = document.getElementById('btn-cek-pln');
        const infoBox = document.getElementById('pln_info');
        const oriText = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Mengecek...'; btn.disabled = true; infoBox.classList.add('hidden');
        fetch('{{ route("ppob.check.pln.prabayar") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify({ customer_no: no })
        }).then(res => res.json()).then(data => {
            btn.innerHTML = oriText; btn.disabled = false;
            if(data.status === 'success') {
                document.getElementById('pln_name').innerText = data.name;
                document.getElementById('pln_power').innerText = data.segment_power;
                infoBox.classList.remove('hidden');
            } else { alert(data.message); }
        }).catch(err => {
            btn.innerHTML = oriText; btn.disabled = false;
            alert("Gagal koneksi server.");
        });
    }

    // --- FILTER & MODAL (PRABAYAR) ---
    const searchInput = document.getElementById('searchInput');
    const rows = document.getElementById('productTableBody') ? document.getElementById('productTableBody').getElementsByTagName('tr') : [];
    const emptyState = document.getElementById('emptyState');
    let currentCategory = 'all';

    if(searchInput) searchInput.addEventListener('keyup', filterTable);

    function toggleMobileDropdown() {
        const list = document.getElementById('mobileCategoryList');
        const arrow = document.getElementById('dropdownArrow');
        if (list.classList.contains('hidden')) { list.classList.remove('hidden'); arrow.classList.add('rotate-180'); }
        else { list.classList.add('hidden'); arrow.classList.remove('rotate-180'); }
    }

    function filterCategory(category) {
        currentCategory = category;
        document.querySelectorAll('.cat-btn').forEach(btn => {
            btn.classList.remove('bg-blue-600', 'text-white', 'shadow-md', 'transform', 'scale-105');
            btn.classList.add('bg-gray-50', 'text-gray-600');
            if (btn.getAttribute('data-cat') === category) {
                btn.classList.remove('bg-gray-50', 'text-gray-600');
                btn.classList.add('bg-blue-600', 'text-white', 'shadow-md', 'transform', 'scale-105');
            }
        });
        const label = document.getElementById('mobileCategoryLabel');
        if(label) label.innerText = (category === 'all') ? 'SEMUA KATEGORI' : category.toUpperCase();
        document.getElementById('mobileCategoryList').classList.add('hidden');
        filterTable();
    }

    function filterTable() {
        const filter = searchInput ? searchInput.value.toLowerCase() : '';
        let visibleCount = 0;
        for (let row of rows) {
            const name = row.getAttribute('data-name');
            const category = row.getAttribute('data-category');
            const matchesSearch = name.includes(filter);
            const matchesCategory = currentCategory === 'all' || category === currentCategory;
            if (matchesSearch && matchesCategory) { row.style.display = ""; visibleCount++; } else { row.style.display = "none"; }
        }
        if(emptyState) emptyState.classList.toggle('hidden', visibleCount > 0);
    }

    // --- VARIABEL GLOBAL UNTUK NAMPUNG DATA SEMENTARA ---
    let selectedSku = null;
    let selectedName = null;
    let selectedPrice = 0;

    function selectProduct(name, price, sku) {
        const no = inputNo.value;
        
        // Validasi Nomor
        if(no.length < 5) { 
            alert("Mohon masukkan nomor tujuan terlebih dahulu."); 
            inputNo.focus(); 
            return; 
        }

        // 1. Simpan ke Global Variable (Biar bisa diakses tombol Bayar)
        selectedSku = sku;
        selectedName = name;
        selectedPrice = price;

        // 2. Update Tampilan Teks di Modal
        document.getElementById('modal_no').innerText = no;
        document.getElementById('modal_product').innerText = name;
        document.getElementById('modal_price').innerText = 'Rp ' + parseInt(price).toLocaleString('id-ID');

        // 3. Tampilkan Modal (Hapus logika href link yang lama)
        document.getElementById('confirmModal').classList.remove('hidden');
    }

   // --- FUNGSI EKSEKUSI SAAT TOMBOL MODAL DIKLIK (DENGAN PENGAMAN) ---
    function processPrepaidCheckout(btnElement) {
        const no = inputNo.value;
        
        // 1. PENGAMAN: Jika tombol sedang disable, hentikan proses (Mencegah Double Click)
        if (btnElement && btnElement.disabled) return;

        // 2. KUNCI TOMBOL & UBAH TAMPILAN
        if (btnElement) {
            const originalText = btnElement.innerHTML;
            btnElement.dataset.originalText = originalText; // Simpan teks asli
            btnElement.disabled = true; // Matikan tombol
            btnElement.innerHTML = '<i class="fas fa-spinner fa-spin animate-spin"></i> Memproses...';
            btnElement.classList.remove('hover:bg-blue-700'); // Hapus efek hover
        }

        // Panggil fungsi request ke server
        proceedToCheckout({
            sku: selectedSku,
            name: selectedName,
            price: selectedPrice,
            customer_no: no,
            ref_id: null, 
            desc: []      
        }, btnElement); // Kirim elemen tombol untuk di-reset jika gagal
    }

    function closeModal() { document.getElementById('confirmModal').classList.add('hidden'); }
 

    // 2. Logic Tabs (Prabayar vs Pascabayar) - SESUAI DESAIN BARU
    function switchTab(type) {
        const btnPre = document.getElementById('tab-prepaid');
        const btnPost = document.getElementById('tab-postpaid');
        const conPre = document.getElementById('content-prepaid');
        const conPost = document.getElementById('content-postpaid');

        if(type === 'prepaid') {
            btnPre.className = "flex-1 py-4 text-center font-bold text-sm md:text-base border-b-2 border-blue-600 text-blue-600 transition hover:bg-gray-50 flex items-center justify-center gap-2";
            btnPost.className = "flex-1 py-4 text-center font-bold text-sm md:text-base border-b-2 border-transparent text-gray-500 hover:text-gray-700 transition hover:bg-gray-50 flex items-center justify-center gap-2";
            
            conPre.classList.remove('hidden');
            conPost.classList.add('hidden');
        } else {
            btnPost.className = "flex-1 py-4 text-center font-bold text-sm md:text-base border-b-2 border-blue-600 text-blue-600 transition hover:bg-gray-50 flex items-center justify-center gap-2";
            btnPre.className = "flex-1 py-4 text-center font-bold text-sm md:text-base border-b-2 border-transparent text-gray-500 hover:text-gray-700 transition hover:bg-gray-50 flex items-center justify-center gap-2";

            conPost.classList.remove('hidden');
            conPre.classList.add('hidden');
        }
    }

    // 3. Logic "Lihat Semua" (Expand Grid)
    function toggleExpand(type) {
        const wrapper = document.getElementById('grid-' + type + '-wrapper');
        const btn = document.getElementById('btn-expand-' + type);
        const items = wrapper.querySelectorAll('.menu-item-' + type);
        const isExpanded = btn.getAttribute('data-expanded') === 'true';

        items.forEach((item, index) => {
            if (index >= 16) {
                if (isExpanded) item.classList.add('hidden');
                else item.classList.remove('hidden');
            }
        });

        if (isExpanded) {
            btn.innerHTML = '<span>Lihat Semua Layanan</span> <i class="fas fa-chevron-down text-[10px]"></i>';
            btn.setAttribute('data-expanded', 'false');
        } else {
            btn.innerHTML = '<span>Sembunyikan</span> <i class="fas fa-chevron-up text-[10px]"></i>';
            btn.setAttribute('data-expanded', 'true');
        }
    }

    // Default: Set active tab berdasarkan halaman (Jika halaman pascabayar, buka tab pascabayar)
    @if($isPostpaid) switchTab('postpaid'); @endif

   // Function Sakti Penghubung (Updated)
    function proceedToCheckout(data, btnElement) {
        fetch("{{ route('ppob.prepare') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify(data)
        })
        .then(res => res.json())
        .then(res => {
            if(res.success) {
                // SUKSES: Redirect ke halaman pembayaran
                window.location.href = "{{ route('ppob.checkout.index') }}";
            } else {
                // GAGAL: Munculkan pesan & Reset tombol
                alert('Gagal: ' + res.message);
                resetButton(btnElement);
            }
        })
        .catch(err => {
            console.error(err);
            alert('Terjadi kesalahan koneksi. Silakan coba lagi.');
            resetButton(btnElement);
        });
    }

    // Helper untuk mengaktifkan tombol kembali jika error
    function resetButton(btnElement) {
        if (btnElement) {
            btnElement.disabled = false;
            btnElement.innerHTML = btnElement.dataset.originalText || 'Bayar Sekarang <i class="fas fa-arrow-right"></i>';
            btnElement.classList.add('hover:bg-blue-700');
        }
    }
    
</script>
@endpush