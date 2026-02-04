@extends('layouts.customer')

@section('title', 'Dashboard')

@section('content')
<div class="bg-gray-50 min-h-screen p-4 sm:p-6 lg:p-8">
    <div class="space-y-8 max-w-7xl mx-auto">

        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center">
            <div>
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-800">Selamat Datang, {{ Auth::user()->nama_lengkap }}!</h1>
                <p class="mt-1 text-md text-gray-500">Berikut adalah ringkasan aktivitas Anda hari ini.</p>
            </div>
            <div class="mt-4 sm:mt-0 text-sm text-gray-500">
                 {{ \Carbon\Carbon::now()->isoFormat('dddd, D MMMM YYYY') }}
            </div>
        </div>

        @if(!empty($slides))
        <div x-data="{ activeSlide: 0, slides: {{ json_encode($slides) }} }"
             x-init="if (slides.length > 1) { setInterval(() => { activeSlide = (activeSlide + 1) % slides.length }, 5000) }"
             class="relative w-full rounded-xl shadow-lg overflow-hidden">

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
                    <button @click="activeSlide = (activeSlide - 1 + slides.length) % slides.length" class="bg-white/60 hover:bg-white text-gray-800 rounded-full w-10 h-10 flex items-center justify-center shadow-md transition">&#10094;</button>
                    <button @click="activeSlide = (activeSlide + 1) % slides.length" class="bg-white/60 hover:bg-white text-gray-800 rounded-full w-10 h-10 flex items-center justify-center shadow-md transition">&#10095;</button>
                </div>
            </template>

            <template x-if="slides.length > 1">
                <div class="absolute bottom-4 left-0 w-full flex justify-center gap-2 z-20">
                    <template x-for="(slide, index) in slides" :key="index">
                        <button @click="activeSlide = index" :class="{'bg-white scale-125': activeSlide === index, 'bg-white/50': activeSlide !== index}" class="w-2.5 h-2.5 rounded-full transition-all duration-300"></button>
                    </template>
                </div>
            </template>
        </div>
        @endif

        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
            <div class="bg-gradient-to-br from-indigo-500 to-blue-500 text-white p-6 rounded-xl shadow-lg transition hover:scale-105">
                <div class="flex justify-between">
                    <div>
                        <p class="text-sm font-medium uppercase opacity-80">Saldo Anda</p>
                        <p id="dashboard-saldo" class="text-3xl font-bold">Rp {{ number_format($saldo, 0, ',', '.') }}</p>
                    </div>
                    <div class="text-5xl opacity-30"><i class="fas fa-wallet"></i></div>
                </div>
            </div>
            <div class="bg-white p-6 rounded-xl shadow-lg transition hover:scale-105">
                <div class="flex justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Total Pesanan</p>
                        <p class="text-3xl font-bold text-gray-900">{{ $totalPesananUser }}</p>
                    </div>
                    <div class="text-5xl text-green-400"><i class="fas fa-box-open"></i></div>
                </div>
            </div>
            <div class="bg-white p-6 rounded-xl shadow-lg transition hover:scale-105">
                <div class="flex justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Pesanan Selesai</p>
                        <p class="text-3xl font-bold text-gray-900">{{ $pesananSelesai }}</p>
                    </div>
                    <div class="text-5xl text-blue-400"><i class="fas fa-check-circle"></i></div>
                </div>
            </div>
            <div class="bg-white p-6 rounded-xl shadow-lg transition hover:scale-105">
                <div class="flex justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Menunggu Pembayaran</p>
                        <p class="text-3xl font-bold text-gray-900">{{ $pesananPending }}</p>
                    </div>
                    <div class="text-5xl text-yellow-400"><i class="fas fa-hourglass-half"></i></div>
                </div>
            </div>
        </div>

        @if (auth()->user()->role === 'Pelanggan' && !auth()->user()->store)
        <div class="p-6 bg-gradient-to-r from-blue-500 to-cyan-400 text-white rounded-xl shadow-lg flex flex-col sm:flex-row items-center justify-between">
            <div>
                <h3 class="text-xl font-bold">Jadilah Bagian dari Penjual Kami!</h3>
                <p class="mt-1 opacity-90">Ingin menjual produk Anda? Buka toko sekarang, gratis! Dapatkan Cuan dari Penjualan.</p>
            </div>
            <a href="{{ route('customer.seller.register.form') }}" class="mt-4 sm:mt-0 sm:ml-6 shrink-0 inline-flex items-center px-6 py-3 bg-white border border-transparent rounded-lg font-semibold text-sm text-blue-600 uppercase hover:bg-blue-50 transition ease-in-out duration-150 shadow">
                Buka Toko Gratis
            </a>
        </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <div class="bg-white p-6 rounded-xl shadow-lg">
                <h2 class="text-lg font-bold text-gray-800 mb-4">Grafik Pesanan 7 Hari Terakhir</h2>
                <canvas id="orderChart"></canvas>
            </div>
            <div class="bg-white p-6 rounded-xl shadow-lg">
                <h2 class="text-lg font-bold text-gray-800 mb-4">Grafik Scan SPX 7 Hari Terakhir</h2>
                <canvas id="spxScanChart"></canvas>
            </div>
        </div>

        {{-- ======================================================= --}}
        {{-- 6. TABEL RIWAYAT PESANAN TERBARU                        --}}
        {{-- ======================================================= --}}

        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
    <div class="flex justify-between items-center p-6 border-b border-gray-100 bg-red-600">
        <h2 class="text-lg font-bold text-white">Riwayat Pesanan Terbaru</h2>
    </div>

    <div class="overflow-x-auto overflow-y-auto max-h-96">
        <table class="min-w-full text-sm relative">
            <thead class="bg-red-100 sticky top-0 z-10 shadow-sm">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase bg-red-100">Tanggal</th>
                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase bg-red-100">Order Id / Invoice</th>
                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase bg-red-100">Ekspedisi</th>
                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase bg-red-100">Status</th>
                    <th class="px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase bg-red-100">Biaya Ongkir</th>
                    <th class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase bg-red-100">Lacak</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse ($recentOrders as $order)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap text-gray-500">
                        <div class="flex flex-col">
                            <span class="font-medium text-gray-900">
                                {{ \Carbon\Carbon::parse($order->created_at)->translatedFormat('d M Y') }}
                            </span>
                            <span class="text-xs text-gray-400">
                                {{ \Carbon\Carbon::parse($order->created_at)->format('H:i') }} WIB
                            </span>
                        </div>
                    </td>

                    <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900">
                        {{ $order->nomor_invoice }}
                    </td>

                    {{-- LOGIC EKSPEDISI (LENGKAP: IDX, J&T CARGO, DLL) --}}
                    <td class="px-6 py-4 whitespace-nowrap">
                        @php
                            $rawExpedition = strtolower($order->expedition ?? '');
                            $courierName = $order->expedition;
                            $logoFile = 'default.png';

                            // --- LOGIKA PENCOCOKAN ---
                            if (str_contains($rawExpedition, 'jtcargo')) {
                                $courierName = 'J&T Cargo'; $logoFile = 'jtcargo.png';
                            } elseif (str_contains($rawExpedition, 'idx')) {
                                $courierName = 'ID Express'; $logoFile = 'idx.png';
                            } elseif (str_contains($rawExpedition, 'jnt') || str_contains($rawExpedition, 'j&t')) {
                                $courierName = 'J&T Express'; $logoFile = 'jnt.png';
                            } elseif (str_contains($rawExpedition, 'jne')) {
                                $courierName = 'JNE'; $logoFile = 'jne.png';
                            } elseif (str_contains($rawExpedition, 'sicepat')) {
                                $courierName = 'SiCepat'; $logoFile = 'sicepat.png';
                            } elseif (str_contains($rawExpedition, 'posindonesia') || str_contains($rawExpedition, 'pos')) {
                                $courierName = 'POS Indonesia'; $logoFile = 'posindonesia.png';
                            } elseif (str_contains($rawExpedition, 'anteraja')) {
                                $courierName = 'AnterAja'; $logoFile = 'anteraja.png';
                            } elseif (str_contains($rawExpedition, 'spx') || str_contains($rawExpedition, 'shopee')) {
                                $courierName = 'SPX Express'; $logoFile = 'spx.png';
                            } elseif (str_contains($rawExpedition, 'ninja')) {
                                $courierName = 'Ninja Xpress'; $logoFile = 'ninja.png';
                            } elseif (str_contains($rawExpedition, 'lion')) {
                                $courierName = 'Lion Parcel'; $logoFile = 'lion.png';
                            } elseif (str_contains($rawExpedition, 'tiki')) {
                                $courierName = 'TIKI'; $logoFile = 'tiki.png';
                            } elseif (str_contains($rawExpedition, 'rpx')) {
                                $courierName = 'RPX'; $logoFile = 'rpx.png';
                            } elseif (str_contains($rawExpedition, 'gosend')) {
                                $courierName = 'GoSend'; $logoFile = 'gosend.png';
                            } elseif (str_contains($rawExpedition, 'grab')) {
                                $courierName = 'GrabExpress'; $logoFile = 'grab.png';
                            } elseif (str_contains($rawExpedition, 'borzo')) {
                                $courierName = 'Borzo'; $logoFile = 'borzo.png';
                            }
                        @endphp

                        <div class="flex items-center space-x-3">
                            <div class="flex-shrink-0 h-8 w-8">
                                <img class="h-8 w-8 rounded-full object-contain bg-gray-50 p-1 border border-gray-200"
                                     src="{{ asset('storage/logo-ekspedisi/' . $logoFile) }}"
                                     alt="{{ $courierName }}"
                                     onerror="this.onerror=null; this.src='https://ui-avatars.com/api/?name={{ urlencode($courierName) }}&background=random&color=fff&size=32&font-size=0.4';">
                            </div>
                            <div class="flex flex-col">
                                <span class="text-sm font-medium text-gray-900">{{ $courierName }}</span>
                            </div>
                        </div>
                    </td>

                    {{-- 3. KOLOM STATUS (LOGIC WARNA DISINI) --}}
        <td class="px-6 py-4 whitespace-nowrap">
            @php
                $status = $order->status_pesanan;

                // Default warna (Abu-abu)
                $badgeClass = 'bg-gray-100 text-gray-800';

                // Logika Warna
                if ($status == 'Selesai' || $status == 'Tiba di Tujuan') {
                    $badgeClass = 'bg-green-100 text-green-800'; // HIJAU
                }
                elseif ($status == 'Menunggu Pickup' || $status == 'Diproses') {
                    $badgeClass = 'bg-yellow-100 text-yellow-800'; // KUNING
                }
                elseif ($status == 'Dibatalkan' || $status == 'Batal' || $status == 'Retur') {
                    $badgeClass = 'bg-red-100 text-red-800'; // MERAH
                }
                elseif ($status == 'Dikirim' || $status == 'Sedang Dikirim') {
                    $badgeClass = 'bg-blue-100 text-blue-800'; // BIRU (Optional)
                }
            @endphp

            <span class="px-3 py-1 inline-flex text-xs font-semibold rounded-full {{ $badgeClass }}">
                {{ $status }}
            </span>
        </td>
                    <td class="px-6 py-4 whitespace-nowrap text-left font-semibold text-gray-800">
                        Rp {{ number_format($order->shipping_cost, 0, ',', '.') }}
                    </td>

                    <td class="px-6 py-4 whitespace-nowrap text-center">
                        <a href="https://tokosancaka.com/tracking/search?resi={{ $order->nomor_invoice }}"
                           target="_blank"
                           class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-50 text-red-600 hover:bg-indigo-100 hover:text-red-700 border border-indigo-200 rounded-lg text-xs font-bold transition-colors duration-200"
                           title="Lacak Invoice: {{ $order->nomor_invoice }}">
                            <i class="fas fa-search-location"></i> Lacak
                        </a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="px-6 py-4 text-center text-gray-500">Belum ada pesanan terbaru.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

      {{-- ======================================================= --}}
        {{-- 7. GRAFIK DISTRIBUSI PENGIRIMAN (BARU)                  --}}
        {{-- ======================================================= --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mt-8">
            {{-- Grafik Kota Tujuan --}}
            <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-100">
                <h2 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                    <i class="fas fa-city text-blue-500"></i> Top 5 Kota Tujuan
                </h2>
                <div class="relative h-64">
                    <canvas id="globalCityChart"></canvas>
                </div>
            </div>

            {{-- Grafik Provinsi Tujuan --}}
            <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-100">
                <h2 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                    <i class="fas fa-map text-green-500"></i> Top 5 Provinsi Tujuan
                </h2>
                <div class="relative h-64">
                    <canvas id="globalProvChart"></canvas>
                </div>
            </div>
        </div>

        {{-- ======================================================= --}}
        {{-- 8. REKAPITULASI BIAYA EKSPEDISI                         --}}
        {{-- ======================================================= --}}
        <div class="mt-8">
            <h3 class="text-xl font-bold text-gray-800 mb-6 flex items-center gap-2">
                <span class="bg-blue-100 p-2 rounded-lg text-blue-600"><i class="fas fa-wallet text-lg"></i></span>
                Rekap Biaya Ekspedisi
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                @forelse ($rekapEkspedisi as $item)
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-lg transition-all duration-300 group">
                    {{-- ... (Isi Kartu Ekspedisi Tetap Sama) ... --}}
                    {{-- HEADER: Logo & Detail --}}
                    <div class="p-5 pb-2">
                        <div class="flex items-center justify-between mb-4">
                            <img src="{{ $item->logo }}" alt="{{ $item->nama }}" class="h-8 w-auto max-w-[120px] object-contain grayscale group-hover:grayscale-0 transition-all duration-300">
                            <a href="{{ $item->url_detail }}" class="text-[10px] font-bold uppercase tracking-wider text-gray-600 bg-green-200 hover:bg-green-400 px-3 py-1 rounded-full transition flex items-center gap-1">
                                Lihat Detail<i class="fas fa-arrow-right text-[8px]"></i>
                            </a>
                        </div>
                    </div>

                    {{-- BIG STATS: Order & Total Tagihan --}}
                    <div class="px-5 pb-4 grid grid-cols-5 gap-3">
                        <div class="col-span-2 bg-gray-50 rounded-lg p-2 text-center border border-gray-100">
                            <p class="text-[10px] text-gray-500 font-bold uppercase tracking-wide">Order</p>
                            <p class="text-lg font-bold text-gray-800">{{ number_format($item->total_order) }}</p>
                        </div>
                        <div class="col-span-3 bg-red-500 rounded-lg p-2 flex flex-col justify-center items-center shadow-sm text-white">
                            <p class="text-[10px] text-white/90 font-bold uppercase flex items-center gap-1 tracking-wide">
                                <i class="fas fa-receipt text-[9px]"></i> Biaya Pengiriman
                            </p>
                            <p class="text-sm font-bold mt-0.5">
                                @if($item->total_pengeluaran > 0) Rp {{ number_format($item->total_pengeluaran, 0, ',', '.') }} @else - @endif
                            </p>
                        </div>
                    </div>

                    {{-- STATISTIK PENGIRIM, PENERIMA, KOTA --}}
                    <div class="px-5 py-4 mt-2">
                        <div class="grid grid-cols-2 gap-x-6 gap-y-3">
                            <div class="flex justify-between items-center text-xs">
                                <span class="text-gray-500 font-medium flex items-center gap-2">Pengirim</span>
                                <span class="font-bold text-gray-800">{{ number_format($item->total_pengirim) }}</span>
                            </div>
                            <div class="flex justify-between items-center text-xs pl-2 border-l border-gray-100">
                                <span class="text-gray-500 font-medium">Penerima</span>
                                <span class="font-bold text-gray-800">{{ number_format($item->total_penerima) }}</span>
                            </div>
                            <div class="flex justify-between items-center text-xs">
                                <div class="flex items-center gap-2">
                                    <i class="fas fa-map-marker-alt text-blue-600 text-[10px]"></i>
                                    <span class="text-gray-500 font-medium">Kota Asal</span>
                                </div>
                                <span class="font-bold text-gray-800">{{ number_format($item->total_kota_asal) }}</span>
                            </div>
                            <div class="flex justify-between items-center text-xs pl-2 border-l border-gray-100">
                                <div class="flex items-center gap-2">
                                    <i class="fas fa-map-signs text-orange-500 text-[10px]"></i>
                                    <span class="text-gray-500 font-medium">Kota Tujuan</span>
                                </div>
                                <span class="font-bold text-gray-800">{{ number_format($item->total_kota_tujuan) }}</span>
                            </div>
                        </div>
                    </div>

                    {{-- RINCIAN BIAYA --}}
                    <div class="px-5 py-4 border-t border-gray-100">
                        <div class="space-y-2">
                            <div class="flex justify-between items-center text-xs">
                                <span class="text-gray-600 flex items-center gap-2"><i class="fas fa-truck text-blue-400 w-3.5"></i> Ongkir</span>
                                <span class="font-bold text-gray-800">{{ $item->biaya_ongkir > 0 ? 'Rp '.number_format($item->biaya_ongkir,0,',','.') : '-' }}</span>
                            </div>
                            <div class="flex justify-between items-center text-xs">
                                <span class="text-gray-600 flex items-center gap-2"><i class="fas fa-shield-alt text-orange-400 w-3.5"></i> Asuransi</span>
                                <span class="font-bold text-gray-800">{{ $item->biaya_asuransi > 0 ? 'Rp '.number_format($item->biaya_asuransi,0,',','.') : '-' }}</span>
                            </div>
                            <div class="flex justify-between items-center text-xs">
                                <span class="text-gray-600 flex items-center gap-2"><i class="fas fa-hand-holding-usd text-purple-400 w-3.5"></i> Biaya COD</span>
                                <span class="font-bold text-gray-800">{{ $item->biaya_cod > 0 ? 'Rp '.number_format($item->biaya_cod,0,',','.') : '-' }}</span>
                            </div>
                        </div>
                    </div>

                </div>
                @empty
                <div class="col-span-full text-center py-10 bg-white rounded-lg border border-dashed border-gray-300">
                    <p class="text-gray-500">Belum ada riwayat transaksi.</p>
                </div>
                @endforelse
            </div>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.15.3/dist/echo.iife.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {

    // --- 1. CHART: PESANAN 7 HARI TERAKHIR ---
    const defaultChartOptions = {
        responsive: true,
        scales: { y: { beginAtZero: true, ticks: { stepSize: 1, precision: 0 } } },
        plugins: { legend: { display: false } }
    };

    const orderCtx = document.getElementById('orderChart');
    if (orderCtx) {
        new Chart(orderCtx.getContext('2d'), {
            type: 'bar',
            data: {
                labels: {!! $orderChartLabels !!},
                datasets: [{
                    label: 'Jumlah Pesanan',
                    data: {!! $orderChartValues !!},
                    backgroundColor: 'rgba(59, 130, 246, 0.5)',
                    borderColor: 'rgba(59, 130, 246, 1)',
                    borderWidth: 1,
                    borderRadius: 5,
                }]
            },
            options: defaultChartOptions
        });
    }

    // --- 2. CHART: SCAN SPX 7 HARI TERAKHIR ---
    const spxCtx = document.getElementById('spxScanChart');
    if (spxCtx) {
        new Chart(spxCtx.getContext('2d'), {
            type: 'line',
            data: {
                labels: {!! $spxChartLabels !!},
                datasets: [{
                    label: 'Jumlah Scan SPX',
                    data: {!! $spxChartValues !!},
                    backgroundColor: 'rgba(255, 159, 64, 0.2)',
                    borderColor: 'rgba(255, 159, 64, 1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.3
                }]
            },
            options: defaultChartOptions
        });
    }

    // --- 3. CHART: GLOBAL TOP 5 KOTA TUJUAN ---
    const globalCityCanvas = document.getElementById('globalCityChart');
    if (globalCityCanvas) {
        new Chart(globalCityCanvas.getContext('2d'), {
            type: 'bar',
            indexAxis: 'y', // Horizontal Bar
            data: {
                labels: {!! $cityChartLabels ?? '[]' !!},
                datasets: [{
                    label: 'Jumlah Paket',
                    data: {!! $cityChartValues ?? '[]' !!},
                    backgroundColor: 'rgba(59, 130, 246, 0.7)',
                    borderRadius: 4,
                    barThickness: 20
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { x: { display: false }, y: { grid: { display: false } } }
            }
        });
    }

    // --- 4. CHART: GLOBAL TOP 5 PROVINSI TUJUAN ---
    const globalProvCanvas = document.getElementById('globalProvChart');
    if (globalProvCanvas) {
        new Chart(globalProvCanvas.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: {!! $provChartLabels ?? '[]' !!},
                datasets: [{
                    data: {!! $provChartValues ?? '[]' !!},
                    backgroundColor: [
                        'rgba(16, 185, 129, 0.7)',
                        'rgba(245, 158, 11, 0.7)',
                        'rgba(239, 68, 68, 0.7)',
                        'rgba(59, 130, 246, 0.7)',
                        'rgba(139, 92, 246, 0.7)'
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'right' } }
            }
        });
    }

    // --- 5. CHART KECIL PER KARTU EKSPEDISI ---
    const cityCharts = document.querySelectorAll('.city-chart-canvas');
    cityCharts.forEach(canvas => {
        const labels = JSON.parse(canvas.getAttribute('data-labels'));
        const data = JSON.parse(canvas.getAttribute('data-values'));

        new Chart(canvas.getContext('2d'), {
            type: 'bar',
            indexAxis: 'y',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Paket',
                    data: data,
                    backgroundColor: [
                        'rgba(59, 130, 246, 0.7)',
                        'rgba(16, 185, 129, 0.7)',
                        'rgba(245, 158, 11, 0.7)',
                        'rgba(239, 68, 68, 0.7)',
                        'rgba(139, 92, 246, 0.7)'
                    ],
                    borderWidth: 0,
                    borderRadius: 3,
                    barThickness: 10
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false }, tooltip: { callbacks: { label: (c) => c.raw + ' Paket' } } },
                scales: {
                    x: { display: false },
                    y: { grid: { display: false }, ticks: { font: { size: 9 }, autoSkip: false } }
                }
            }
        });
    });

    // --- 6. REALTIME UPDATES (PUSHER / ECHO) ---
    const saldoElement = document.getElementById('dashboard-saldo');
    const userId = {{ Auth::id() }};

    if (typeof window.Echo !== 'undefined' && userId) {
        try {
            // Update Saldo Realtime
            window.Echo.private(`customer-saldo.${userId}`)
                .listen('SaldoUpdated', (e) => {
                    if (saldoElement && e.formattedSaldo) {
                        saldoElement.textContent = e.formattedSaldo;
                    }
                });

            // Update Slider Realtime
            const sliderComponent = document.querySelector('[x-data*="activeSlide"]');
            if (sliderComponent && sliderComponent.__x) {
                window.Echo.channel('site-updates')
                    .listen('SliderUpdated', (e) => {
                        sliderComponent.__x.getUnobservedData().slides = e.slides;
                        sliderComponent.__x.getUnobservedData().activeSlide = 0;
                    });
            }
        } catch (error) {
            console.error('Gagal terhubung ke channel websocket:', error);
        }
    }
});
</script>
@endpush
