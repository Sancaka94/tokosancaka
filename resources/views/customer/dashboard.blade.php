@extends('layouts.customer')

@section('content')
<div class="space-y-8">

    {{-- SLIDER: fokus ke atas + opsi no-crop --}}
   <div
    x-data="{ activeSlide: 0, slides: {{ json_encode($slides ?? []) }} }"
    x-init="if (slides.length > 1) { setInterval(() => { activeSlide = (activeSlide + 1) % slides.length }, 5000) }"
    id="customer-slider"
    class="relative w-full max-w-7xl mx-auto rounded-lg shadow-lg overflow-hidden"
>
 <div
    x-data="{ activeSlide: 0, slides: {{ json_encode($slides ?? []) }} }"
    x-init="if (slides.length > 1) { setInterval(() => { activeSlide = (activeSlide + 1) % slides.length }, 5000) }"
    id="customer-slider"
    class="relative w-full max-w-7xl mx-auto rounded-lg shadow-lg overflow-hidden"
>
    <!-- Wrapper slider -->
    <div class="relative w-full overflow-hidden">
        <!-- Track -->
        <div
            class="flex transition-transform duration-700 ease-in-out"
            :style="`transform: translateX(-${activeSlide * 100}%);`"
        >
            <!-- Slide -->
            <template x-for="(slide, index) in slides" :key="index">
                <div class="w-full flex-shrink-0 flex justify-center relative">
                    <!-- Blur background -->
                    <div
                        class="absolute inset-0 blur-lg scale-110 opacity-30"
                        :style="`background-image:url('${slide.img}'); background-size:cover; background-position:center;`"
                        aria-hidden="true"
                    ></div>

                    <!-- Gambar -->
                    <img
                        :src="slide.img"
                        :alt="slide.alt ?? 'Informasi'"
                        class="relative max-w-full h-auto z-10"
                    >
                </div>
            </template>
        </div>
    </div>

    <!-- Tombol navigasi -->
    <template x-if="slides.length > 1">
        <div class="absolute inset-0 flex justify-between items-center px-4 z-20">
            <button
                @click="activeSlide = (activeSlide - 1 + slides.length) % slides.length"
                class="bg-white/50 hover:bg-white text-gray-800 rounded-full w-10 h-10 flex items-center justify-center"
            >
                &#10094;
            </button>
            <button
                @click="activeSlide = (activeSlide + 1) % slides.length"
                class="bg-white/50 hover:bg-white text-gray-800 rounded-full w-10 h-10 flex items-center justify-center"
            >
                &#10095;
            </button>
        </div>
    </template>

    <!-- Indicator -->
    <template x-if="slides.length > 1">
        <div class="absolute bottom-4 left-0 w-full flex justify-center gap-2 z-20">
            <template x-for="(slide, index) in slides" :key="index">
                <button
                    @click="activeSlide = index"
                    :class="{'bg-white': activeSlide === index, 'bg-white/50': activeSlide !== index}"
                    class="w-3 h-3 rounded-full transition"
                ></button>
            </template>
        </div>
    </template>
</div>
            
 

    {{-- Judul Halaman --}}
    <h1 class="text-3xl font-bold text-gray-800">Dashboard Monitor</h1>

    {{-- Kartu Statistik --}}
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4 mb-4">
        <div class="flex items-center p-6 bg-white rounded-xl shadow-lg">
            <div class="flex items-center justify-center w-16 h-16 bg-blue-100 rounded-full">
                <i class="fas fa-wallet fa-2x text-blue-500"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-500">Saldo Anda</p>
                <p id="dashboard-saldo" class="text-2xl font-bold text-gray-900">
                    Rp {{ number_format($saldo, 0, ',', '.') }}
                </p>
            </div>
        </div>

        <div class="flex items-center p-6 bg-white rounded-xl shadow-lg">
            <div class="flex items-center justify-center w-16 h-16 bg-green-100 rounded-full">
                <i class="fas fa-box-open fa-2x text-green-500"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-500">Total Pesanan</p>
                <p class="text-2xl font-bold text-gray-900">{{ $totalPesanan }}</p>
            </div>
        </div>

        <div class="flex items-center p-6 bg-white rounded-xl shadow-lg">
            <div class="flex items-center justify-center w-16 h-16 bg-indigo-100 rounded-full">
                <i class="fas fa-check-circle fa-2x text-indigo-500"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-500">Pesanan Selesai</p>
                <p class="text-2xl font-bold text-gray-900">{{ $pesananSelesai }}</p>
            </div>
        </div>

        <div class="flex items-center p-6 bg-white rounded-xl shadow-lg">
            <div class="flex items-center justify-center w-16 h-16 bg-yellow-100 rounded-full">
                <i class="fas fa-hourglass-half fa-2x text-yellow-500"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-500">Menunggu Pembayaran</p>
                <p class="text-2xl font-bold text-gray-900">{{ $pesananPending }}</p>
            </div>
        </div>
    </div>
    
     {{-- ====================================================== --}}
    {{-- == TAMBAHKAN BLOK KODE INI DI SINI == --}}
    {{-- ====================================================== --}}
    {{-- Cek jika user adalah 'Pelanggan' dan belum punya toko --}}
    @if (auth()->user()->role === 'Pelanggan' && !auth()->user()->store)
        <div class="p-6 bg-white border border-gray-200 rounded-lg shadow">
            <h3 class="text-lg font-semibold text-gray-800">Jadilah Bagian dari Penjual Kami!</h3>
            <p class="mt-2 text-gray-600">Ingin menjual produk Anda sendiri di Toko Sancaka? Buka toko Anda sekarang juga, gratis! Dapatkan Cuan Dari Penjualan Toko Sancaka</p>
            <a href="{{ route('customer.seller.register.form') }}" class="mt-4 inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150">
                Buka Toko Gratis
            </a>
        </div>
    @endif
    {{-- ====================================================== --}}
    {{-- == AKHIR DARI BLOK KODE == --}}
    {{-- ====================================================== --}}


    {{-- Area Grafik --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-4">
        <div class="bg-white p-6 rounded-xl shadow-lg">
            <h2 class="text-xl font-bold text-gray-800 mb-4">Grafik Pesanan 7 Hari Terakhir</h2>
            <canvas id="orderChart"></canvas>
        </div>
        <div class="bg-white p-6 rounded-xl shadow-lg">
            <h2 class="text-xl font-bold text-gray-800 mb-4">Grafik Scan SPX 7 Hari Terakhir</h2>
            <canvas id="spxScanChart"></canvas>
        </div>
    </div>

    {{-- Tabel Riwayat --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <div>
            <h2 class="text-2xl font-bold text-gray-800 mb-4">5 Pesanan Terbaru</h2>
            <div class="overflow-x-auto bg-white rounded-xl shadow-lg">
                <table class="min-w-full text-sm divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">ID Pesanan</th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse ($recentOrders as $order)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900">#{{ $order->id_pesanan }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-3 py-1 inline-flex text-xs font-semibold rounded-full
                                        {{ $order->status_pesanan == 'Terkirim' ? 'bg-blue-100 text-blue-800' : ($order->status_pesanan == 'Tiba di Tujuan' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800') }}">
                                        {{ $order->status_pesanan }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap font-semibold text-gray-800">Rp {{ number_format($order->total_harga_barang, 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-6 py-4 text-center text-gray-500">Belum ada pesanan terbaru.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div>
            <h2 class="text-2xl font-bold text-gray-800 mb-4">5 Riwayat Scan SPX Terbaru</h2>
            <div class="overflow-x-auto bg-white rounded-xl shadow-lg">
                <table class="min-w-full text-sm divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">No. Resi</th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Waktu Scan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse ($recentSpxScans as $scan)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900">{{ $scan->resi_number }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-3 py-1 inline-flex text-xs font-semibold rounded-full bg-cyan-100 text-cyan-800">
                                        {{ $scan->status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-gray-500">{{ $scan->created_at->isoFormat('D MMM YYYY, HH:mm') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-6 py-4 text-center text-gray-500">Belum ada riwayat scan SPX.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
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

    const sliderComponent = document.querySelector('#customer-slider');
    if (sliderComponent && sliderComponent.__x) {
        if (typeof window.Echo !== 'undefined') {
            window.Echo.channel('site-updates')
                .listen('SliderUpdated', (e) => {
                    // dukung fit 'contain' dari broadcast juga
                    sliderComponent.__x.getUnobservedData().slides = e.slides;
                    sliderComponent.__x.getUnobservedData().activeSlide = 0;
                });
        }
    }
});
</script>
@endpush
