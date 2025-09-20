@extends('layouts.customer')

@section('title', 'Dashboard')

@section('content')
<div class="bg-gray-50 dark:bg-gray-900 min-h-screen p-4 sm:p-6 lg:p-8">
<div class="space-y-8 max-w-7xl mx-auto">

    <!-- Header Sambutan -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-800 dark:text-white">Selamat Datang, {{ Auth::user()->nama_lengkap }}!</h1>
            <p class="mt-1 text-md text-gray-500 dark:text-gray-400">Berikut adalah ringkasan aktivitas Anda hari ini.</p>
        </div>
        <div class="mt-4 sm:mt-0 text-sm text-gray-500 dark:text-gray-400">
             {{ \Carbon\Carbon::now()->isoFormat('dddd, D MMMM YYYY') }}
        </div>
    </div>

    <!-- Slider (jika ada) -->
    @if(!empty($slides))
    <div x-data="{ activeSlide: 0, slides: {{ json_encode($slides) }} }"
         x-init="if (slides.length > 1) { setInterval(() => { activeSlide = (activeSlide + 1) % slides.length }, 5000) }"
         class="relative w-full rounded-xl shadow-lg overflow-hidden">
        
        <!-- Wrapper slider -->
        <div class="relative w-full overflow-hidden">
            <!-- Track -->
            <div class="flex transition-transform duration-700 ease-in-out"
                 :style="`transform: translateX(-${activeSlide * 100}%);`">
                <!-- Slide -->
                <template x-for="(slide, index) in slides" :key="index">
                    <div class="w-full flex-shrink-0 flex justify-center relative">
                        <!-- Latar belakang blur -->
                        <div class="absolute inset-0 blur-lg scale-110 opacity-30"
                             :style="`background-image:url('${slide.img}'); background-size:cover; background-position:center;`"
                             aria-hidden="true">
                        </div>
                        <!-- Gambar utama -->
                        <img :src="slide.img" :alt="slide.alt ?? 'Informasi'" class="relative max-w-full h-auto z-10">
                    </div>
                </template>
            </div>
        </div>

        <!-- Tombol Navigasi -->
        <template x-if="slides.length > 1">
            <div class="absolute inset-0 flex justify-between items-center px-4 z-20">
                <button @click="activeSlide = (activeSlide - 1 + slides.length) % slides.length"
                        class="bg-white/60 hover:bg-white text-gray-800 rounded-full w-10 h-10 flex items-center justify-center shadow-md transition">
                    &#10094;
                </button>
                <button @click="activeSlide = (activeSlide + 1) % slides.length"
                        class="bg-white/60 hover:bg-white text-gray-800 rounded-full w-10 h-10 flex items-center justify-center shadow-md transition">
                    &#10095;
                </button>
            </div>
        </template>

        <!-- Indikator -->
        <template x-if="slides.length > 1">
            <div class="absolute bottom-4 left-0 w-full flex justify-center gap-2 z-20">
                <template x-for="(slide, index) in slides" :key="index">
                    <button @click="activeSlide = index"
                            :class="{'bg-white scale-125': activeSlide === index, 'bg-white/50': activeSlide !== index}"
                            class="w-2.5 h-2.5 rounded-full transition-all duration-300"></button>
                </template>
            </div>
        </template>
    </div>
    @endif


    <!-- Kartu Statistik -->
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
        <!-- Saldo -->
        <div class="bg-gradient-to-br from-indigo-500 to-blue-500 text-white p-6 rounded-xl shadow-lg transition hover:scale-105">
            <div class="flex justify-between">
                <div>
                    <p class="text-sm font-medium uppercase opacity-80">Saldo Anda</p>
                    <p id="dashboard-saldo" class="text-3xl font-bold">Rp {{ number_format($saldo, 0, ',', '.') }}</p>
                </div>
                <div class="text-5xl opacity-30">
                    <i class="fas fa-wallet"></i>
                </div>
            </div>
        </div>
        <!-- Total Pesanan -->
        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-lg transition hover:scale-105">
            <div class="flex justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Pesanan</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $totalPesanan }}</p>
                </div>
                <div class="text-5xl text-green-400">
                    <i class="fas fa-box-open"></i>
                </div>
            </div>
        </div>
        <!-- Pesanan Selesai -->
        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-lg transition hover:scale-105">
            <div class="flex justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Pesanan Selesai</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $pesananSelesai }}</p>
                </div>
                <div class="text-5xl text-blue-400">
                    <i class="fas fa-check-circle"></i>
                </div>
            </div>
        </div>
        <!-- Menunggu Pembayaran -->
        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-lg transition hover:scale-105">
            <div class="flex justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Menunggu Pembayaran</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $pesananPending }}</p>
                </div>
                <div class="text-5xl text-yellow-400">
                    <i class="fas fa-hourglass-half"></i>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Ajakan Buka Toko -->
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

    <!-- Area Grafik -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-lg">
            <h2 class="text-lg font-bold text-gray-800 dark:text-white mb-4">Grafik Pesanan 7 Hari Terakhir</h2>
            <canvas id="orderChart"></canvas>
        </div>
        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-lg">
            <h2 class="text-lg font-bold text-gray-800 dark:text-white mb-4">Grafik Scan SPX 7 Hari Terakhir</h2>
            <canvas id="spxScanChart"></canvas>
        </div>
    </div>

    <!-- Tabel Riwayat -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Pesanan Terbaru -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
            <h2 class="text-lg font-bold text-gray-800 dark:text-white p-6">5 Pesanan Terbaru</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">ID Pesanan</th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Status</th>
                            <th class="px-6 py-3 text-right text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse ($recentOrders as $order)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900 dark:text-white">#{{ $order->id_pesanan }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-3 py-1 inline-flex text-xs font-semibold rounded-full {{ $order->status_pesanan == 'Terkirim' ? 'bg-blue-100 text-blue-800' : ($order->status_pesanan == 'Tiba di Tujuan' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800') }}">
                                    {{ $order->status_pesanan }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right font-semibold text-gray-800 dark:text-gray-300">Rp {{ number_format($order->total_harga_barang, 0, ',', '.') }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">Belum ada pesanan terbaru.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <!-- Scan SPX Terbaru -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
            <h2 class="text-lg font-bold text-gray-800 dark:text-white p-6">5 Riwayat Scan SPX Terbaru</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">No. Resi</th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Waktu Scan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse ($recentSpxScans as $scan)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900 dark:text-white">{{ $scan->resi_number }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-3 py-1 inline-flex text-xs font-semibold rounded-full bg-cyan-100 text-cyan-800">
                                    {{ $scan->status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-gray-500 dark:text-gray-400">{{ $scan->created_at->isoFormat('D MMM YYYY, HH:mm') }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">Belum ada riwayat scan SPX.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
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
    const defaultChartOptions = {
        responsive: true,
        scales: { y: { beginAtZero: true, ticks: { stepSize: 1, precision: 0 } } },
        plugins: { legend: { display: false } }
    };

    new Chart(document.getElementById('orderChart').getContext('2d'), {
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

    new Chart(document.getElementById('spxScanChart').getContext('2d'), {
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

    const saldoElement = document.getElementById('dashboard-saldo');
    const userId = {{ Auth::id() }};
    if (typeof window.Echo !== 'undefined' && userId) {
        try {
            // Pastikan Anda sudah mengkonfigurasi Echo di bootstrap.js
            window.Echo.private(`customer-saldo.${userId}`)
                .listen('SaldoUpdated', (e) => {
                    if (saldoElement && e.formattedSaldo) {
                        saldoElement.textContent = e.formattedSaldo;
                    }
                });
        } catch (error) {
            console.error('Gagal terhubung ke channel saldo:', error);
        }
    }

    const sliderComponent = document.querySelector('[x-data*="activeSlide"]');
    if (sliderComponent && sliderComponent.__x) {
        if (typeof window.Echo !== 'undefined') {
            window.Echo.channel('site-updates')
                .listen('SliderUpdated', (e) => {
                    sliderComponent.__x.getUnobservedData().slides = e.slides;
                    sliderComponent.__x.getUnobservedData().activeSlide = 0;
                });
        }
    }
});
</script>
@endpush

