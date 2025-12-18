@extends('layouts.admin')

@push('styles')
<style>
.main-layout-container {
    height: 133.33vh; 
}
.sidebar-container {
    height: 100%; 
}
</style>
@endpush

@section('title', 'Dashboard Admin')
@section('page-title', 'Dashboard')

@section('content')

{{-- Notifikasi Sandbox (Komponen) --}}
@include('components.sandbox_alert')

<div x-data="{
    notificationModal: {
        open: false,
        title: '',
        message: '',
        url: '#'
    }
}" @new-notification.window="
    notificationModal.title = $event.detail.title;
    notificationModal.message = $event.detail.message;
    notificationModal.url = $event.detail.url;
    notificationModal.open = true;
">

    {{-- Slider --}}
    <div x-data="{ activeSlide: 0, slides: {{ json_encode($slides ?? []) }} }" x-init="if (slides.length > 1) { setInterval(() => { activeSlide = (activeSlide + 1) % slides.length }, 5000) }" id="customer-slider" class="relative w-full max-w-7xl mx-auto rounded-lg shadow-lg overflow-hidden">
        <div class="relative w-full overflow-hidden">
            <div class="flex transition-transform duration-700 ease-in-out" :style="`transform: translateX(-${activeSlide * 100}%);`">
                <template x-for="(slide, index) in slides" :key="index">
                    <div class="w-full flex-shrink-0 flex justify-center relative">
                        <div class="absolute inset-0 blur-lg scale-110 opacity-30" :style="`background-image:url('${slide.img}'); background-size:cover; background-position:center;`" aria-hidden="true"></div>
                        <img :src="slide.img" :alt="slide.alt ?? 'Informasi'" class="relative max-w-full h-auto z-10">
                    </div>
                </template>
            </div>
        </div>
        <template x-if="slides.length > 1">
            <div class="absolute inset-0 flex justify-between items-center px-4 z-20">
                <button @click="activeSlide = (activeSlide - 1 + slides.length) % slides.length" class="bg-white/50 hover:bg-white text-gray-800 rounded-full w-10 h-10 flex items-center justify-center shadow-md">&#10094;</button>
                <button @click="activeSlide = (activeSlide + 1) % slides.length" class="bg-white/50 hover:bg-white text-gray-800 rounded-full w-10 h-10 flex items-center justify-center shadow-md">&#10095;</button>
            </div>
        </template>
        <template x-if="slides.length > 1">
            <div class="absolute bottom-4 left-0 w-full flex justify-center gap-2 z-20">
                <template x-for="(slide, index) in slides" :key="index">
                    <button @click="activeSlide = index" :class="{'bg-white': activeSlide === index, 'bg-white/50': activeSlide !== index}" class="w-3 h-3 rounded-full transition shadow"></button>
                </template>
            </div>
        </template>
    </div>

    {{-- Kartu Statistik --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mt-8">
    
    <div class="relative group">
        <div class="absolute -inset-0.5 bg-gradient-to-r from-emerald-500 to-teal-600 rounded-2xl blur opacity-20 group-hover:opacity-40 transition duration-1000 group-hover:duration-200"></div>
        <div class="relative bg-white border border-gray-100 p-6 rounded-2xl shadow-sm transition-all duration-300 transform group-hover:-translate-y-2 group-hover:shadow-xl group-hover:shadow-emerald-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-gray-500 mb-1">Total Pendapatan</p>
                    <h3 id="total-pendapatan" class="text-2xl font-extrabold text-gray-800 leading-none">
                        Rp {{ number_format($totalPendapatan ?? 0, 0, ',', '.') }}
                    </h3>
                    <div class="flex items-center mt-2 text-[10px] font-bold text-emerald-500">
                        <i class="fas fa-chart-line mr-1"></i><span>Akumulasi Saldo</span>
                    </div>
                </div>
                <div class="flex-shrink-0 w-14 h-14 bg-gradient-to-br from-emerald-500 to-teal-600 rounded-xl flex items-center justify-center shadow-lg transform transition-transform group-hover:rotate-12 group-hover:scale-110">
                    <i class="fas fa-coins text-white text-xl"></i>
                </div>
            </div>
            <div class="mt-4 w-full bg-gray-100 rounded-full h-1">
                <div class="bg-emerald-500 h-1 rounded-full opacity-60" style="width: 100%"></div>
            </div>
        </div>
    </div>

    <div class="relative group">
        <div class="absolute -inset-0.5 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-2xl blur opacity-20 group-hover:opacity-40 transition duration-1000 group-hover:duration-200"></div>
        <div class="relative bg-white border border-gray-100 p-6 rounded-2xl shadow-sm transition-all duration-300 transform group-hover:-translate-y-2 group-hover:shadow-xl group-hover:shadow-blue-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-gray-500 mb-1">Total Pesanan</p>
                    <h3 id="total-pesanan" class="text-2xl font-extrabold text-gray-800 leading-none">
                        {{ number_format($totalPesanan ?? 0, 0, ',', '.') }}
                    </h3>
                    <div class="flex items-center mt-2 text-[10px] font-bold text-blue-500">
                        <i class="fas fa-truck-ramp-box mr-1"></i><span>Total Transaksi</span>
                    </div>
                </div>
                <div class="flex-shrink-0 w-14 h-14 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl flex items-center justify-center shadow-lg transform transition-transform group-hover:rotate-12 group-hover:scale-110">
                    <i class="fas fa-box-open text-white text-xl"></i>
                </div>
            </div>
            <div class="mt-4 w-full bg-gray-100 rounded-full h-1">
                <div class="bg-blue-500 h-1 rounded-full opacity-60" style="width: 100%"></div>
            </div>
        </div>
    </div>

    <div class="relative group">
        <div class="absolute -inset-0.5 bg-gradient-to-r from-violet-500 to-purple-600 rounded-2xl blur opacity-20 group-hover:opacity-40 transition duration-1000 group-hover:duration-200"></div>
        <div class="relative bg-white border border-gray-100 p-6 rounded-2xl shadow-sm transition-all duration-300 transform group-hover:-translate-y-2 group-hover:shadow-xl group-hover:shadow-purple-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-gray-500 mb-1">Jumlah Toko</p>
                    <h3 id="jumlah-toko" class="text-2xl font-extrabold text-gray-800 leading-none">
                        {{ number_format($jumlahToko ?? 0, 0, ',', '.') }}
                    </h3>
                    <div class="flex items-center mt-2 text-[10px] font-bold text-purple-500">
                        <i class="fas fa-shop mr-1"></i><span>Mitra Terdaftar</span>
                    </div>
                </div>
                <div class="flex-shrink-0 w-14 h-14 bg-gradient-to-br from-violet-500 to-purple-600 rounded-xl flex items-center justify-center shadow-lg transform transition-transform group-hover:rotate-12 group-hover:scale-110">
                    <i class="fas fa-store-alt text-white text-xl"></i>
                </div>
            </div>
            <div class="mt-4 w-full bg-gray-100 rounded-full h-1">
                <div class="bg-purple-500 h-1 rounded-full opacity-60" style="width: 100%"></div>
            </div>
        </div>
    </div>

    <div class="relative group">
        <div class="absolute -inset-0.5 bg-gradient-to-r from-amber-400 to-orange-500 rounded-2xl blur opacity-20 group-hover:opacity-40 transition duration-1000 group-hover:duration-200"></div>
        <div class="relative bg-white border border-gray-100 p-6 rounded-2xl shadow-sm transition-all duration-300 transform group-hover:-translate-y-2 group-hover:shadow-xl group-hover:shadow-orange-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-gray-500 mb-1">Pengguna Baru</p>
                    <h3 id="pengguna-baru" class="text-2xl font-extrabold text-gray-800 leading-none">
                        {{ number_format($penggunaBaru ?? 0, 0, ',', '.') }}
                    </h3>
                    <div class="flex items-center mt-2 text-[10px] font-bold text-orange-500">
                        <i class="fas fa-user-clock mr-1"></i><span>30 Hari Terakhir</span>
                    </div>
                </div>
                <div class="flex-shrink-0 w-14 h-14 bg-gradient-to-br from-amber-400 to-orange-500 rounded-xl flex items-center justify-center shadow-lg transform transition-transform group-hover:rotate-12 group-hover:scale-110">
                    <i class="fas fa-user-check text-white text-xl"></i>
                </div>
            </div>
            <div class="mt-4 w-full bg-gray-100 rounded-full h-1">
                <div class="bg-orange-500 h-1 rounded-full opacity-60" style="width: 100%"></div>
            </div>
        </div>
    </div>

</div>

    {{-- Grafik --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-8">
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white p-6 rounded-lg shadow-lg">
                <h3 class="text-lg font-semibold text-gray-700 mb-4">Grafik Pendapatan (30 Hari)</h3>
                <div class="relative h-80"><canvas id="adminTransactionChart"></canvas></div>
            </div>
            <div class="bg-white p-6 rounded-lg shadow-lg">
                <h3 class="text-lg font-semibold text-gray-700 mb-4">Grafik Scan SPX (30 Hari)</h3>
                <div class="relative h-80"><canvas id="spxScanChart"></canvas></div>
            </div>
        </div>
        <div class="bg-white p-6 rounded-lg shadow-lg">
            <h3 class="text-lg font-semibold text-gray-700 mb-4">Aktivitas Pesanan Terbaru</h3>
            <div id="recent-activity-container" class="space-y-4">
                {{-- GANTI BAGIAN @forelse ($pesananTerbaru ... SAMPAI @empty DENGAN INI --}}
@forelse ($pesananTerbaru as $pesanan)
<div class="flex items-start py-2 border-b border-gray-100 last:border-0">
    {{-- LOGO EKSPEDISI (Pengganti Icon Cart) --}}
    <div class="mt-1">
        @php
            // Ambil kode ekspedisi dari string (contoh: regular-jne-REG -> jne)
            $parts = explode('-', $pesanan->expedition);
            $kodeEks = isset($parts[1]) ? strtolower($parts[1]) : 'default';
            
            // Path folder logo ekspedisi Anda
            $logoPath = "public/storage/logo-ekspedisi/{$kodeEks}.png";
            $fullPath = asset($logoPath);
        @endphp

        <div class="w-12 h-12 rounded-full bg-gray-50 border border-gray-100 flex items-center justify-center overflow-hidden shadow-sm">
            @if(file_exists(base_path($logoPath)) || $kodeEks != 'default')
                <img src="{{ $fullPath }}" 
                     alt="{{ $kodeEks }}" 
                     class="w-10 h-10 object-contain"
                     onerror="this.onerror=null; this.src='{{ asset('public/storage/uploads/sancaka.png') }}';">
            @else
                <i class="fas fa-shipping-fast text-indigo-500"></i>
            @endif
        </div>
    </div>

    <div class="ml-3 flex-1">
        {{-- Baris 1: RESI & HARGA --}}
        <div class="flex justify-between items-start">
    <div class="flex flex-col">
        <p class="text-sm font-bold text-gray-800">
            {{ $pesanan->resi ?? $pesanan->nomor_invoice }}
        </p>
        {{-- TOMBOL TRACKING BARU --}}
        @if($pesanan->resi)
        <a href="https://tokosancaka.com/tracking?resi={{ $pesanan->resi }}" 
           target="_blank" 
           class="mt-1 inline-flex items-center text-[10px] font-bold text-red-600 hover:text-red-800 uppercase tracking-tighter">
            <i class="fas fa-search-location mr-1"></i> Lacak Pesanan
        </a>
        @endif
    </div>
    
    <span class="text-xs font-bold text-green-600 bg-green-50 px-2 py-0.5 rounded-full">
        Rp {{ number_format($pesanan->shipping_cost, 0, ',', '.') }}
    </span>
</div>

        {{-- Info Toko & User (Pakai ALIAS dari Controller sebelumnya) --}}
        <div class="mt-1 flex flex-col gap-0.5">
            <div class="flex items-center text-xs text-indigo-600 font-semibold">
                <i class="fas fa-store w-4 text-center mr-1 opacity-70"></i>
                <span>{{ $pesanan->nama_toko_anda ?? 'Tanpa Nama Toko' }}</span>
            </div>

            <div class="flex items-center text-xs text-gray-700 font-medium">
                <i class="fas fa-user w-4 text-center mr-1 opacity-60"></i>
                <span>{{ $pesanan->nama_user_anda ?? 'User Tidak Dikenal' }}</span>
            </div>

            <div class="flex items-center text-[10px] text-gray-500">
                <i class="fas fa-paper-plane w-4 text-center mr-1 opacity-50"></i>
                <span>Pengirim: {{ $pesanan->sender_name ?? '-' }}</span>
            </div>
        </div>
    </div>
</div>
@empty
    <p class="text-sm text-gray-500 text-center py-4">Belum ada aktivitas pesanan.</p>
@endforelse
                {{-- SAMPAI DISINI --}}
            </div>
        </div>
    </div>

    {{-- GRAFIK EKSPEDISI TERBANYAK (Diletakkan diatas Rekap) --}}
    <div class="mt-8">
        <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-100">
            <div class="flex items-center gap-3 mb-6">
                <div class="p-2 bg-indigo-50 rounded-lg text-indigo-600">
                    <i class="fas fa-chart-bar fa-lg"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-800">Peringkat Ekspedisi Terbanyak</h3>
            </div>
            <div class="relative h-96">
                <canvas id="expeditionRankChart"></canvas>
            </div>
        </div>
    </div>

    {{-- Rekapitulasi Ekspedisi --}}
    <div class="mt-8">
        <h3 class="text-2xl font-bold leading-tight text-gray-800 mb-6">Rekap Transaksi Ekspedisi</h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
           @forelse ($rekapEkspedisi as $item)
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-lg transition-all duration-300 group">
                
                {{-- BAGIAN ATAS: Header & Stats Utama --}}
                <div class="p-5 pb-3">
                    {{-- Logo & Tombol Detail --}}
                    <div class="flex items-center justify-between mb-5">
                        <img src="{{ $item->logo }}" alt="{{ $item->nama }}" class="h-8 w-auto max-w-[120px] object-contain grayscale group-hover:grayscale-0 transition-all duration-300">
                        <a href="{{ $item->url_detail }}" class="text-[10px] font-bold uppercase tracking-wider text-red-500 bg-red-50 hover:bg-red-100 px-3 py-1 rounded-full transition flex items-center gap-1">
                            Detail <i class="fas fa-arrow-right text-[8px]"></i>
                        </a>
                    </div>

                    {{-- Highlight Stats (Order, Pelanggan, Profit) --}}
                    <div class="grid grid-cols-3 gap-3 text-center">
                        
                        {{-- ORDER --}}
                        <div class="py-2 px-1 bg-gray-50 rounded-lg border border-gray-100">
                            <div class="flex items-center justify-center gap-1 mb-1">
                                {{-- Icon Box --}}
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-3 h-3 text-gray-400">
                                    <path d="M12.378 1.602a.75.75 0 00-.756 0L3 6.632l9 5.25 9-5.25-8.622-5.03zM21.75 7.93l-9 5.25v9l8.628-5.032a.75.75 0 00.372-.648V7.93zM11.25 22.18v-9l-9-5.25v8.57a.75.75 0 00.372.648l8.628 5.033z" />
                                </svg>
                                <p class="text-[10px] text-gray-500 font-bold uppercase tracking-wide">Order</p>
                            </div>
                            <p class="text-sm font-bold text-gray-800">{{ number_format($item->total_order) }}</p>
                        </div>

                        {{-- PELANGGAN --}}
                        <div class="py-2 px-1 bg-gray-50 rounded-lg border border-gray-100">
                            <div class="flex items-center justify-center gap-1 mb-1">
                                {{-- Icon Users --}}
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-3 h-3 text-gray-400">
                                    <path d="M4.5 6.375a4.125 4.125 0 118.25 0 4.125 4.125 0 01-8.25 0zM14.25 8.625a3.375 3.375 0 116.75 0 3.375 3.375 0 01-6.75 0zM1.5 19.125a7.125 7.125 0 0114.25 0v.003l-.001.119a.75.75 0 01-.363.63 13.067 13.067 0 01-6.761 1.873c-2.472 0-4.786-.684-6.76-1.873a.75.75 0 01-.364-.63l-.001-.122zM17.25 19.128l-.001.144a2.25 2.25 0 01-.233.96 10.088 10.088 0 005.06-1.01.75.75 0 00.42-.643 4.875 4.875 0 00-6.957-4.611 8.586 8.586 0 011.71 5.157v.003z" />
                                </svg>
                                <p class="text-[10px] text-gray-500 font-bold uppercase tracking-wide">Pelanggan</p>
                            </div>
                            <p class="text-sm font-bold text-gray-800">{{ number_format($item->total_pelanggan) }}</p>
                        </div>

                        {{-- PROFIT --}}
                        <div class="py-2 px-1 bg-green-500 rounded-lg shadow-sm flex flex-col justify-center">
                            <div class="flex items-center justify-center gap-1 mb-1">
                                {{-- Icon Money --}}
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-3 h-3 text-white/90">
                                    <path d="M10.464 8.746c.227-.18.497-.311.786-.394v2.795a2.252 2.252 0 01-.786-.393c-.394-.313-.546-.681-.546-1.004 0-.324.152-.691.546-1.004zM12.75 15.662v-2.824c.347.085.664.228.921.421.427.32.579.686.579.991 0 .305-.152.671-.579.991a2.534 2.534 0 01-.921.42z" />
                                    <path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25zM12.75 6a.75.75 0 00-1.5 0v.816a3.836 3.836 0 00-1.72.756c-.712.566-1.112 1.35-1.112 2.178 0 .829.4 1.612 1.113 2.178.502.4 1.102.647 1.719.756v2.978a2.536 2.536 0 01-.921-.421l-.879-.66a.75.75 0 00-.9 1.2l.879.66c.533.4 1.169.645 1.821.75V18a.75.75 0 001.5 0v-.81a4.124 4.124 0 001.821-.749c.745-.559 1.179-1.344 1.179-2.191 0-.847-.434-1.632-1.179-2.191a4.122 4.122 0 00-1.821-.75V8.354c.29.082.559.213.786.393l.415.33a.75.75 0 00.933-1.175l-.415-.33a3.836 3.836 0 00-1.719-.755V6z" clip-rule="evenodd" />
                                </svg>
                                <p class="text-[10px] text-white/90 font-bold uppercase tracking-wide">Profit</p>
                            </div>
                            <p class="text-xs font-bold text-white">
                                @if($item->total_profit > 0)
                                    Rp {{ number_format($item->total_profit, 0, ',', '.') }}
                                @else
                                    -
                                @endif
                            </p>
                        </div>
                    </div>
                </div>

                {{-- BAGIAN TENGAH: Statistik Pengiriman --}}
                <div class="px-5 py-4 border-t border-gray-100">
                    <div class="grid grid-cols-2 gap-6">
                        
                        {{-- KIRI: Pengirim & Asal --}}
                        <div class="space-y-3">
                            {{-- Pengirim --}}
                            <div class="flex justify-between items-center">
                                <div class="flex items-center gap-2">
                                    {{-- Icon User --}}
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-3.5 h-3.5 text-blue-400">
                                        <path fill-rule="evenodd" d="M7.5 6a4.5 4.5 0 119 0 4.5 4.5 0 01-9 0zM3.751 20.105a8.25 8.25 0 0116.498 0 .75.75 0 01-.437.695A18.683 18.683 0 0112 22.5c-2.786 0-5.433-.608-7.812-1.7a.75.75 0 01-.437-.695z" clip-rule="evenodd" />
                                    </svg>
                                    <span class="text-xs text-gray-500 font-medium">Pengirim</span>
                                </div>
                                <span class="text-sm font-bold text-gray-800">{{ number_format($item->total_pengirim) }}</span>
                            </div>
                            
                            {{-- Kota Asal --}}
                            <div class="flex justify-between items-center">
                                <div class="flex items-center gap-2">
                                    {{-- Icon Pin --}}
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-3.5 h-3.5 text-blue-600">
                                        <path fill-rule="evenodd" d="M11.54 22.351l.07.04.028.016a.76.76 0 00.723 0l.028-.015.071-.041a16.975 16.975 0 001.144-.742 19.58 19.58 0 002.683-2.282c1.944-1.99 3.963-4.98 3.963-8.827a8.25 8.25 0 00-16.5 0c0 3.846 2.02 6.837 3.963 8.827a19.58 19.58 0 002.682 2.282 16.975 16.975 0 001.145.742zM12 13.5a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd" />
                                    </svg>
                                    <span class="text-xs text-gray-500 font-medium">Kota Asal</span>
                                </div>
                                <span class="text-sm font-bold text-gray-800">{{ number_format($item->total_kota_asal) }}</span>
                            </div>
                        </div>

                        {{-- KANAN: Penerima & Tujuan --}}
                        <div class="space-y-3 border-l border-gray-100 pl-6">
                            {{-- Penerima --}}
                            <div class="flex justify-between items-center">
                                <div class="flex items-center gap-2">
                                    {{-- Icon User Outline --}}
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-3.5 h-3.5 text-orange-400">
                                        <path fill-rule="evenodd" d="M7.5 6a4.5 4.5 0 119 0 4.5 4.5 0 01-9 0zM3.751 20.105a8.25 8.25 0 0116.498 0 .75.75 0 01-.437.695A18.683 18.683 0 0112 22.5c-2.786 0-5.433-.608-7.812-1.7a.75.75 0 01-.437-.695z" clip-rule="evenodd" />
                                    </svg>
                                    <span class="text-xs text-gray-500 font-medium">Penerima</span>
                                </div>
                                <span class="text-sm font-bold text-gray-800">{{ number_format($item->total_penerima) }}</span>
                            </div>

                            {{-- Kota Tujuan --}}
                            <div class="flex justify-between items-center">
                                <div class="flex items-center gap-2">
                                    {{-- Icon Rambu --}}
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-3.5 h-3.5 text-orange-500">
                                        <path d="M12.75 2a.75.75 0 00-1.5 0v2.25H4.5a.75.75 0 00-.53 1.28l3.75 3.75-3.75 3.75A.75.75 0 004.5 14.25h6.75v6.25a.75.75 0 001.5 0V14.25h6.75a.75.75 0 00.53-1.28l-3.75-3.75 3.75-3.75a.75.75 0 00-.53-1.28H12.75V2z" />
                                    </svg>
                                    <span class="text-xs text-gray-500 font-medium">Kota Tujuan</span>
                                </div>
                                <span class="text-sm font-bold text-gray-800">{{ number_format($item->total_kota_tujuan) }}</span>
                            </div>
                        </div>

                    </div>
                </div>

                {{-- BAGIAN BAWAH: Status Breakdown --}}
                <div class="bg-gray-50 px-5 py-3 border-t border-gray-200/60">
                    <p class="text-[9px] text-gray-400 font-bold uppercase mb-2 tracking-wider">Status Pesanan</p>
                    <div class="grid grid-cols-4 gap-1 text-center">
                        {{-- Pickup --}}
                        <div class="flex flex-col items-center">
                            {{-- Icon Paket --}}
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-3 h-3 text-gray-400 mb-0.5">
                                <path d="M3.375 3C2.339 3 1.5 3.84 1.5 4.875v.75c0 1.036.84 1.875 1.875 1.875h17.25c1.035 0 1.875-.84 1.875-1.875v-.75C22.5 3.839 21.66 3 20.625 3H3.375z" />
                                <path fill-rule="evenodd" d="M3.087 9l.54 9.176A3 3 0 006.62 21h10.757a3 3 0 002.995-2.824L20.913 9H3.087zm6.163 3.75A.75.75 0 0110 12h4a.75.75 0 010 1.5h-4a.75.75 0 01-.75-.75z" clip-rule="evenodd" />
                            </svg>
                            <span class="text-xs font-bold text-gray-700">{{ $item->status_pickup }}</span>
                            <span class="text-[8px] text-gray-500 uppercase">Pickup</span>
                        </div>
                        {{-- Jalan --}}
                        <div class="flex flex-col items-center border-l border-gray-200">
                            {{-- Icon Truk --}}
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-3 h-3 text-blue-400 mb-0.5">
                                <path d="M3.375 4.5C2.339 4.5 1.5 5.34 1.5 6.375V13.5h12V6.375c0-1.035-.84-1.875-1.875-1.875H3.375zM13.5 15h-12v2.625c0 1.035.84 1.875 1.875 1.875h.375a3 3 0 116 0h3a.75.75 0 00.75-.75V15z" />
                                <path d="M8.25 19.5a1.5 1.5 0 10-3 0 1.5 1.5 0 003 0zM15.75 6.75a.75.75 0 00-.75.75v11.25c0 .087.015.17.042.248a3 3 0 015.958.464c.853-.175 1.522-.935 1.464-1.883a18.659 18.659 0 00-3.732-10.104 1.837 1.837 0 00-1.47-.725H15.75z" />
                                <path d="M19.5 19.5a1.5 1.5 0 10-3 0 1.5 1.5 0 003 0z" />
                            </svg>
                            <span class="text-xs font-bold text-blue-600">{{ $item->status_dikirim }}</span>
                            <span class="text-[8px] text-gray-500 uppercase">Jalan</span>
                        </div>
                        {{-- Selesai --}}
                        <div class="flex flex-col items-center border-l border-gray-200">
                            {{-- Icon Check --}}
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-3 h-3 text-green-500 mb-0.5">
                                <path fill-rule="evenodd" d="M8.603 3.799A4.49 4.49 0 0112 2.25c1.357 0 2.573.6 3.397 1.549a4.49 4.49 0 013.498 1.307 4.491 4.491 0 011.307 3.497A4.49 4.49 0 0121.75 12a4.49 4.49 0 01-1.549 3.397 4.491 4.491 0 01-1.307 3.497 4.491 4.491 0 01-3.497 1.307A4.49 4.49 0 0112 21.75a4.49 4.49 0 01-3.397-1.549 4.49 4.49 0 01-3.498-1.306 4.491 4.491 0 01-1.307-3.498A4.49 4.49 0 012.25 12c0-1.357.6-2.573 1.549-3.397a4.49 4.49 0 011.307-3.497 4.491 4.491 0 013.497-1.307zm4.458 2.75a.75.75 0 00-1.12-1.098l-4.435 4.536-1.928-1.826a.75.75 0 10-1.036 1.093l2.5 2.368c.3.284.773.284 1.074 0l4.945-5.073z" clip-rule="evenodd" />
                            </svg>
                            <span class="text-xs font-bold text-green-600">{{ $item->status_selesai }}</span>
                            <span class="text-[8px] text-gray-500 uppercase">Selesai</span>
                        </div>
                        {{-- Gagal --}}
                        <div class="flex flex-col items-center border-l border-gray-200">
                            {{-- Icon X --}}
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-3 h-3 text-red-400 mb-0.5">
                                <path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25zm-1.72 6.97a.75.75 0 10-1.06 1.06L10.94 12l-1.72 1.72a.75.75 0 101.06 1.06L12 13.06l1.72 1.72a.75.75 0 101.06-1.06L13.06 12l1.72-1.72a.75.75 0 10-1.06-1.06L12 10.94l-1.72-1.72z" clip-rule="evenodd" />
                            </svg>
                            <span class="text-xs font-bold text-red-600">{{ $item->status_gagal }}</span>
                            <span class="text-[8px] text-gray-500 uppercase">Gagal</span>
                        </div>
                    </div>
                </div>

            </div>
            @empty
            <p class="col-span-full text-center text-gray-500 py-10">Belum ada data transaksi.</p>
            @endforelse
        </div>
    </div>

    {{-- Modal Notifikasi Internal --}}
    <div x-show="notificationModal.open" x-cloak x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 z-[100] flex items-center justify-center bg-black bg-opacity-50">
        <div @click.away="notificationModal.open = false" x-show="notificationModal.open" x-cloak class="bg-white rounded-lg shadow-xl w-full max-w-md mx-4 transform transition-all" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95">
            <div class="p-6 text-center">
                <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100">
                    <i class="fas fa-bell fa-2x text-green-600 animate-bounce"></i>
                </div>
                <h3 class="mt-5 text-xl font-semibold text-gray-800" x-text="notificationModal.title"></h3>
                <div class="mt-2 text-sm text-gray-600">
                    <p x-text="notificationModal.message"></p>
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse rounded-b-lg">
                <a :href="notificationModal.url" @click="notificationModal.open = false" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm">Lihat Detail</a>
                <button @click="notificationModal.open = false" type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:w-auto sm:text-sm">Tutup</button>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // --- Inisialisasi & Setup Chart ---
        const notificationTableBody = document.getElementById('notification-table-body');
        let adminTransactionChart, spxScanChart, expeditionRankChart;

        function showNotificationModal(title, message, url) {
            window.dispatchEvent(new CustomEvent('new-notification', { detail: { title, message, url } }));
        }

        function addNotificationToTable(notification) {
            if (!notificationTableBody) return;
            const noNotificationRow = document.getElementById('no-notification-row');
            if (noNotificationRow) noNotificationRow.remove();
            const newRow = document.createElement('tr');
            newRow.className = 'hover:bg-gray-50 bg-red-50 font-semibold';
            newRow.innerHTML = `
                <td class="px-6 py-4">
                    <a href="${notification.url || '#'}" class="block text-gray-800 hover:text-indigo-600">
                        <p class="font-medium">${notification.title || 'Notifikasi'}</p>
                        <p class="text-xs text-gray-600">${notification.message || 'Tidak ada detail.'}</p>
                    </a>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-gray-500">Baru saja</td>`;
            notificationTableBody.prepend(newRow);
        }

        function initCharts() {
            const chartOptions = (isDarkMode) => ({
                responsive: true, maintainAspectRatio: false,
                scales: { 
                    y: { 
                        beginAtZero: true, 
                        ticks: { color: isDarkMode ? '#9ca3af' : '#4b5563' },
                        grid: { color: isDarkMode ? '#374151' : '#e5e7eb' }
                    },
                    x: {
                        ticks: { color: isDarkMode ? '#9ca3af' : '#4b5563' },
                        grid: { color: isDarkMode ? '#374151' : '#e5e7eb' }
                    }
                },
                plugins: { legend: { labels: { color: isDarkMode ? '#9ca3af' : '#4b5563' } } }
            });

            // Data Awal dari Controller
            const chartData = @json($chartData ?? ['labels' => [], 'data' => []]);
            const ctx = document.getElementById('adminTransactionChart');
            if (ctx) {
                adminTransactionChart = new Chart(ctx, { 
                    type: 'line', 
                    data: { 
                        labels: chartData.labels, 
                        datasets: [{ 
                            label: 'Total Pendapatan', 
                            data: chartData.data, 
                            borderColor: 'rgb(79, 70, 229)', 
                            backgroundColor: 'rgba(79, 70, 229, 0.1)', 
                            fill: true, 
                            tension: 0.4 
                        }] 
                    }, 
                    options: chartOptions(localStorage.getItem('darkMode') === 'true') 
                });
            }

            const spxChartData = @json($spxChartData ?? ['labels' => [], 'data' => []]);
            const spxCtx = document.getElementById('spxScanChart');
            if (spxCtx) {
                spxScanChart = new Chart(spxCtx, { 
                    type: 'bar', 
                    data: { 
                        labels: spxChartData.labels, 
                        datasets: [{ 
                            label: 'Total Scan SPX', 
                            data: spxChartData.data, 
                            borderColor: 'rgb(239, 68, 68)', 
                            backgroundColor: 'rgba(239, 68, 68, 0.5)', 
                            borderWidth: 1 
                        }] 
                    }, 
                    options: chartOptions(localStorage.getItem('darkMode') === 'true') 
                });
            }

            // --- TAMBAHKAN INI SAJA DI DALAM initCharts() ---
const expCtx = document.getElementById('expeditionRankChart');
if (expCtx) {
    const expData = @json($expeditionData ?? ['labels' => [], 'data' => []]);

    // 1. Mapping Warna Brand Ekspedisi
    const brandColors = {
        'JTCARGO': '#008d36', // HIJAU KHUSUS CARGO
        'JNT': '#ff0000',       // MERAH EXPRESS
        'JNE': '#0054a6',
        'POS': '#ff6600',
        'SICEPAT': '#d31027',
        'SPX': '#ee4d2d',
        'ANTERAJA': '#e0004d',
        'NINJA': '#c00d0d',
        'ID': '#f37021',
        'SENTRAL': '#004b93'
    };

    // 2. Mapping Logo Ekspedisi
    const brandLogos = expData.labels.map(label => {
        const key = label.toUpperCase();
        return `{{ asset('public/storage/logo-ekspedisi') }}/${key.toLowerCase()}.png`;
    });

    const bgColors = expData.labels.map(label => brandColors[label.toUpperCase()] || '#4f46e5');

    expeditionRankChart = new Chart(expCtx, {
        type: 'bar',
        data: {
            labels: expData.labels,
            datasets: [{
                label: 'Total Kiriman',
                data: expData.data,
                backgroundColor: bgColors, // Warna sesuai logo
                borderRadius: 5,
                barThickness: 25
            }]
        },
        options: {
    indexAxis: 'y',
    responsive: true,
    maintainAspectRatio: false,
    layout: {
        // Tambahkan padding kiri yang lebih besar (min 70-80) agar logo tidak mentok pinggir
        padding: { left: 80, right: 20, top: 10, bottom: 10 } 
    },
    plugins: {
        legend: { display: false }
    },
    scales: {
        x: { beginAtZero: true, grid: { display: false } },
        y: { 
            grid: { display: false },
            ticks: {
                // Beri jarak antara teks label dengan garis bar
                padding: 10, 
                font: { weight: 'bold', size: 12 }
            }
        }
    }
},
        // 3. PLUGIN KHUSUS UNTUK GAMBAR LOGO
        plugins: [{
    id: 'yAxisLogos',
    afterDraw: (chart) => {
        const { ctx, scales: { y } } = chart;
        
        y.ticks.forEach((tick, index) => {
            const img = new Image();
            img.src = brandLogos[index]; // brandLogos didefinisikan dari data labels
            
            if (img.complete || img.height > 0) {
                const yPos = y.getPixelForTick(index);
                const targetW = 35; // Lebar maksimal logo
                const targetH = 25; // Tinggi maksimal logo
                
                // Kalkulasi rasio asli agar TIDAK GEPENG
                const ratio = img.width / img.height;
                let drawW = targetW;
                let drawH = targetW / ratio;

                // Jika tinggi melebihi batas, sesuaikan lebarnya
                if (drawH > targetH) {
                    drawH = targetH;
                    drawW = targetH * ratio;
                }

                // Posisi X: ditaruh di sebelah kiri label (y.left)
                // y.left - 75 adalah posisi di dalam area padding yang kita buat tadi
                const xOff = y.left - 75 + (targetW - drawW) / 2;
                const yOff = yPos - (drawH / 2);

                ctx.drawImage(img, xOff, yOff, drawW, drawH);
            } else {
                // Redraw jika gambar baru saja dimuat
                img.onload = () => chart.draw();
            }
        });
    }
}]
    });
}

            // Update chart saat Dark Mode berubah
            window.addEventListener('dark-mode-toggled', (e) => {
                if(adminTransactionChart) {
                    adminTransactionChart.options = chartOptions(e.detail.darkMode);
                    adminTransactionChart.update();
                }
                if(spxScanChart) {
                    spxScanChart.options = chartOptions(e.detail.darkMode);
                    spxScanChart.update();
                }
            });
        }
        
        function updateStats(stats) {
            if (!stats) return;
            const fmt = (val) => new Intl.NumberFormat('id-ID').format(val);
            if(document.getElementById('total-pendapatan')) document.getElementById('total-pendapatan').textContent = `Rp ${fmt(stats.totalPendapatan)}`;
            if(document.getElementById('total-pesanan')) document.getElementById('total-pesanan').textContent = fmt(stats.totalPesanan);
            if(document.getElementById('jumlah-toko')) document.getElementById('jumlah-toko').textContent = fmt(stats.jumlahToko);
            if(document.getElementById('pengguna-baru')) document.getElementById('pengguna-baru').textContent = fmt(stats.penggunaBaru);
        }

        // GANTI FUNCTION updateRecentActivity YANG LAMA DENGAN INI

        function updateRecentActivity(activities) {
    const container = document.getElementById('recent-activity-container');
    if (!container) return;

    container.innerHTML = ''; 

    if (activities && activities.length > 0) {
        activities.forEach(pesanan => {
            // 1. Logika Penentuan Logo Ekspedisi
            const parts = (pesanan.expedition || '').split('-');
            const kodeEks = parts[1] ? parts[1].toLowerCase() : 'default';
            const logoUrl = `{{ asset('public/storage/logo-ekspedisi') }}/${kodeEks}.png`;
            const defaultLogo = `{{ asset('public/storage/uploads/sancaka.png') }}`;

            // 2. Persiapan Data Teks
            const resi = pesanan.resi || pesanan.nomor_invoice;
            const harga = new Intl.NumberFormat('id-ID').format(pesanan.shipping_cost);
            const storeName = pesanan.nama_toko_anda || 'Tanpa Nama Toko';
            const userName = pesanan.nama_user_anda || 'User Tidak Dikenal';
            const senderName = pesanan.sender_name || '-';

            // 3. Logika Tombol Tracking
            const trackingButton = pesanan.resi 
                ? `<a href="https://tokosancaka.com/tracking?resi=${pesanan.resi}" target="_blank" class="mt-1 inline-flex items-center text-[10px] font-bold text-blue-600 hover:text-blue-800 uppercase tracking-tighter">
                     <i class="fas fa-search-location mr-1"></i> Lacak Pesanan
                   </a>` 
                : '';

            // 4. Template HTML
            const html = `
                <div class="flex items-start py-2 border-b border-gray-100 last:border-0">
                    <div class="mt-1">
                        <div class="w-12 h-12 rounded-full bg-gray-50 border border-gray-100 flex items-center justify-center overflow-hidden shadow-sm">
                            <img src="${logoUrl}" class="w-10 h-10 object-contain" onerror="this.src='${defaultLogo}'">
                        </div>
                    </div>

                    <div class="ml-3 flex-1">
                        <div class="flex justify-between items-start">
                            <div class="flex flex-col">
                                <p class="text-sm font-bold text-gray-800">${resi}</p>
                                ${trackingButton}
                            </div>
                            <span class="text-xs font-bold text-green-600 bg-green-50 px-2 py-0.5 rounded-full">
                                Rp ${harga}
                            </span>
                        </div>

                        <div class="mt-1 flex flex-col gap-0.5">
                            <div class="flex items-center text-xs text-indigo-600 font-semibold">
                                <i class="fas fa-store w-4 text-center mr-1 opacity-70"></i>
                                <span>${storeName}</span>
                            </div>

                            <div class="flex items-center text-xs text-gray-700 font-medium">
                                <i class="fas fa-user w-4 text-center mr-1 opacity-60"></i>
                                <span>${userName}</span>
                            </div>

                            <div class="flex items-center text-[10px] text-gray-500">
                                <i class="fas fa-paper-plane w-4 text-center mr-1 opacity-50"></i>
                                <span>Pengirim: ${senderName}</span>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', html);
        });
    } else {
        container.innerHTML = '<p id="no-recent-activity" class="text-sm text-gray-500 text-center py-4">Belum ada aktivitas pesanan.</p>';
    }
}

        function updateChart(chart, newData) {
            if (!chart || !newData) return;
            if (newData.labels) chart.data.labels = newData.labels;
            if (newData.data && chart.data.datasets.length > 0) {
                chart.data.datasets[0].data = newData.data;
            }
            chart.update();
        }

        

        initCharts();

        if (typeof window.Echo !== 'undefined' && {{ Auth::check() && strtolower(Auth::user()->role) === 'admin' ? 'true' : 'false' }}) {
            window.Echo.private('admin-notifications')
                .listen('AdminNotificationEvent', (e) => {
                    console.log('Event AdminNotificationEvent diterima:', e);
                    showNotificationModal(e.title, e.message, e.url);
                    addNotificationToTable(e);
                    if (Notification.permission === "granted") {
                        new Notification(e.title, { body: e.message, icon: '{{ asset("public/storage/uploads/sancaka.png") }}' });
                    }
                });
            window.Echo.private('admin-dashboard')
                .listen('DashboardUpdated', (e) => {
                    console.log('Event DashboardUpdated diterima:', e);
                    updateStats(e.stats);
                    updateChart(adminTransactionChart, e.chartData);
                    updateChart(spxScanChart, e.spxChartData);
                    updateRecentActivity(e.pesananTerbaru);
                });
        }
    });
</script>
@endpush