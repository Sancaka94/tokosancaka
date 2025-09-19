@extends('layouts.admin')



@section('title', 'Dashboard Admin')

@section('page-title', 'Dashboard')



@section('content')

{{-- Wrapper for Alpine.js data, including the notification modal --}}

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



            {{-- Tabel Notifikasi Terbaru --}}

            <div class="mt-8">

                <h3 class="text-lg font-semibold text-gray-700 mb-4">Aktivitas Terbaru</h3>

                <div class="bg-white rounded-lg shadow-lg overflow-hidden">

                    <div class="overflow-x-auto">

                        <table class="min-w-full text-sm">

                            <thead class="bg-gray-50">

                                <tr>

                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Notifikasi</th>

                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Waktu</th>

                                </tr>

                            </thead>

                            <tbody id="notification-table-body" class="divide-y divide-gray-200">

                                @forelse ($recentNotifications ?? [] as $notification)

                                    <tr class="hover:bg-gray-50 @if(is_null($notification->read_at)) bg-blue-50 font-semibold @endif">

                                        <td class="px-6 py-4">

                                            <a href="{{ $notification->data['url'] ?? '#' }}" class="block text-gray-800 hover:text-indigo-600">

                                                <p class="font-medium">{{ $notification->data['title'] ?? 'Notifikasi' }}</p>

                                                <p class="text-xs text-gray-600">{{ $notification->data['message'] ?? 'Tidak ada detail.' }}</p>

                                            </a>

                                        </td>

                                        <td class="px-6 py-4 whitespace-nowrap text-gray-500">

                                            {{ $notification->created_at->diffForHumans() }}

                                        </td>

                                    </tr>

                                @empty

                                    <tr id="no-notification-row">

                                        <td colspan="2" class="px-6 py-4 text-center text-gray-500">

                                            Tidak ada notifikasi baru.

                                        </td>

                                    </tr>

                                @endforelse

                            </tbody>

                        </table>

                    </div>

                </div>

            </div>



            {{-- Stat Cards --}}

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mt-8">

                <!-- Card Total Pendapatan -->

                <div class="bg-white p-6 rounded-lg shadow-lg">

                    <div class="flex items-center">

                        <div class="p-3 rounded-full bg-green-100"><svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v.01"></path></svg></div>

                        <div class="ml-4">

                            <p class="text-sm font-medium text-gray-500">Total Pendapatan</p>

                            <p id="total-pendapatan" class="text-2xl font-bold text-gray-800">Rp {{ number_format($totalPendapatan ?? 0, 0, ',', '.') }}</p>

                        </div>

                    </div>

                </div>

                

                <!-- Card Total Pesanan -->

                <div class="bg-white p-6 rounded-lg shadow-lg">

                    <div class="flex items-center">

                        <div class="p-3 rounded-full bg-blue-100"><svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg></div>

                        <div class="ml-4">

                            <p class="text-sm font-medium text-gray-500">Total Pesanan</p>

                            <p id="total-pesanan" class="text-2xl font-bold text-gray-800">{{ number_format($totalPesanan ?? 0, 0, ',', '.') }}</p>

                        </div>

                    </div>

                </div>



                <!-- Card Jumlah Toko -->

                <div class="bg-white p-6 rounded-lg shadow-lg">

                    <div class="flex items-center">

                        <div class="p-3 rounded-full bg-indigo-100"><svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg></div>

                        <div class="ml-4">

                            <p class="text-sm font-medium text-gray-500">Jumlah Toko</p>

                            <p id="jumlah-toko" class="text-2xl font-bold text-gray-800">{{ number_format($jumlahToko ?? 0, 0, ',', '.') }}</p>

                        </div>

                    </div>

                </div>

                <!-- Card Pengguna Baru -->

                <div class="bg-white p-6 rounded-lg shadow-lg">

                    <div class="flex items-center">

                        <div class="p-3 rounded-full bg-yellow-100"><svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg></div>

                        <div class="ml-4">

                            <p class="text-sm font-medium text-gray-500">Pengguna Baru (30 Hari)</p>

                            <p id="pengguna-baru" class="text-2xl font-bold text-gray-800">{{ number_format($penggunaBaru ?? 0, 0, ',', '.') }}</p>

                        </div>

                    </div>

                </div>

            </div>

            

            {{-- Grafik dan Aktivitas Terbaru --}}

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

                        @forelse ($pesananTerbaru as $pesanan)

                            <div class="flex items-center">

                                <div class="p-3 rounded-full bg-gray-100"><svg class="w-6 h-6 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg></div>

                                <div class="ml-4 flex-1">

                                    <p class="text-sm font-semibold text-gray-800">{{ $pesanan->resi ?? $pesanan->nomor_invoice }}</p>

                                    <p class="text-xs text-gray-600">dari <span class="font-medium">{{ $pesanan->toko->nama_toko ?? 'Toko Dihapus' }}</span></p>

                                </div>

                                <span class="text-sm font-bold text-green-600">Rp {{ number_format($pesanan->total_harga_barang, 0, ',', '.') }}</span>

                            </div>

                        @empty

                            <p id="no-recent-activity" class="text-sm text-gray-500 text-center py-4">Belum ada aktivitas pesanan.</p>

                        @endforelse

                    </div>

                </div>

            </div>



            {{-- Rekapitulasi Ekspedisi --}}

            <div class="mt-8">

                <h3 class="text-2xl font-semibold leading-tight text-gray-800 mb-4">Rekap Transaksi Ekspedisi</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">

                    @forelse ($rekapEkspedisi as $item)

                        <div class="bg-white rounded-lg shadow-lg p-5">

                            <div class="flex items-center justify-between mb-4 h-12">

                                <img src="{{ $item->logo }}" alt="Logo {{ $item->nama }}" class="h-full w-auto max-w-[150px] object-contain" onerror="this.onerror=null;this.src='https://placehold.co/120x40/f9fafb/374151?text={{ urlencode($item->nama) }}';">

                                <a href="#" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">Detail</a>

                            </div>

                            <div class="grid grid-cols-3 gap-4 text-center border-t pt-4">

                                <div><p class="text-xs text-gray-500">Order</p><p class="text-lg font-bold text-gray-800">{{ number_format($item->total_order) }}</p></div>

                                <div><p class="text-xs text-gray-500">Pelanggan</p><p class="text-lg font-bold text-gray-800">{{ number_format($item->total_pelanggan) }}</p></div>

                                <div><p class="text-xs text-gray-500">Profit</p><p class="text-lg font-bold text-green-600">@if($item->total_profit > 0) Rp {{ number_format($item->total_profit) }} @else Rp 0 @endif</p></div>

                            </div>

                        </div>

                    @empty

                        <p class="col-span-full text-center text-gray-500 py-10">Belum ada data ekspedisi yang dikonfigurasi.</p>

                    @endforelse

                </div>

            </div>

        </div>

    </div>



    {{-- Modal Notifikasi --}}

    <div x-show="notificationModal.open" x-cloak

        x-transition:enter="ease-out duration-300"

        x-transition:enter-start="opacity-0"

        x-transition:enter-end="opacity-100"

        x-transition:leave="ease-in duration-200"

        x-transition:leave-start="opacity-100"

        x-transition:leave-end="opacity-0"

        class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">

        

        <div @click.away="notificationModal.open = false"

            x-show="notificationModal.open" x-cloak

            x-transition:enter="ease-out duration-300"

            x-transition:enter-start="opacity-0 scale-95"

            x-transition:enter-end="opacity-100 scale-100"

            x-transition:leave="ease-in duration-200"

            x-transition:leave-start="opacity-100 scale-100"

            x-transition:leave-end="opacity-0 scale-95"

            class="bg-white rounded-lg shadow-xl w-full max-w-md mx-4">

            

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

                <a :href="notificationModal.url" @click="notificationModal.open = false"

                   class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm">

                    Lihat Detail

                </a>

                <button @click="notificationModal.open = false" type="button"

                        class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:w-auto sm:text-sm">

                    Close

                </button>

            </div>

        </div>

    </div>

</div>

@endsection



@push('scripts')

{{-- Script Chart.js --}}

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>



{{-- Script Notifikasi dan Update Real-time Terpusat --}}

<script>

document.addEventListener('DOMContentLoaded', function() {

    // --- INISIALISASI ---

    const notificationTableBody = document.getElementById('notification-table-body');

    let adminTransactionChart, spxScanChart;



    // --- FUNGSI-FUNGSI HELPER ---



    function showNotificationModal(title, message, url) {

        window.dispatchEvent(new CustomEvent('new-notification', {

            detail: { title, message, url }

        }));

    }



    function addNotificationToTable(notification) {

        const noNotificationRow = document.getElementById('no-notification-row');

        if (noNotificationRow) noNotificationRow.remove();

        const newRow = document.createElement('tr');

        newRow.className = 'hover:bg-gray-50 bg-blue-50 font-semibold';

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

        const chartData = @json($chartData ?? ['labels' => [], 'data' => []]);

        const ctx = document.getElementById('adminTransactionChart');

        if (ctx && chartData.labels.length > 0) {

            adminTransactionChart = new Chart(ctx, { type: 'line', data: { labels: chartData.labels, datasets: [{ label: 'Total Pendapatan', data: chartData.data, borderColor: 'rgb(79, 70, 229)', backgroundColor: 'rgba(79, 70, 229, 0.1)', fill: true, tension: 0.4 }] }, options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true, ticks: { callback: (value) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(value) } } } } });

        }

        

        const spxChartData = @json($spxChartData ?? ['labels' => [], 'data' => []]);

        const spxCtx = document.getElementById('spxScanChart');

        if (spxCtx && spxChartData.labels.length > 0) {

            spxScanChart = new Chart(spxCtx, { type: 'bar', data: { labels: spxChartData.labels, datasets: [{ label: 'Total Scan SPX', data: spxChartData.data, borderColor: 'rgb(239, 68, 68)', backgroundColor: 'rgba(239, 68, 68, 0.5)', borderWidth: 1 }] }, options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true, ticks: { stepSize: 1, callback: (value) => (Math.floor(value) === value) ? value : null } } } } });

        }

    }



    function updateChart(chart, newData) {

        if (!chart || !newData) return;

        chart.data.labels = newData.labels;

        chart.data.datasets[0].data = newData.data;

        chart.update();

    }



    function updateRecentActivity(activities) {

        const container = document.getElementById('recent-activity-container');

        container.innerHTML = '';

        if (activities && activities.length > 0) {

            activities.forEach(activity => {

                const activityHtml = `<div class="flex items-center"><div class="p-3 rounded-full bg-gray-100"><svg class="w-6 h-6 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg></div><div class="ml-4 flex-1"><p class="text-sm font-semibold text-gray-800">${activity.resi || activity.nomor_invoice}</p><p class="text-xs text-gray-600">dari <span class="font-medium">${activity.toko_nama || 'Toko Dihapus'}</span></p></div><span class="text-sm font-bold text-green-600">Rp ${new Intl.NumberFormat('id-ID').format(activity.total_harga_barang)}</span></div>`;

                container.insertAdjacentHTML('beforeend', activityHtml);

            });

        } else {

            container.innerHTML = `<p id="no-recent-activity" class="text-sm text-gray-500 text-center py-4">Belum ada aktivitas pesanan.</p>`;

        }

    }



    function updateStats(stats) {

        if (!stats) return;

        document.getElementById('total-pendapatan').textContent = `Rp ${new Intl.NumberFormat('id-ID').format(stats.totalPendapatan)}`;

        document.getElementById('total-pesanan').textContent = new Intl.NumberFormat('id-ID').format(stats.totalPesanan);

        document.getElementById('jumlah-toko').textContent = new Intl.NumberFormat('id-ID').format(stats.jumlahToko);

        document.getElementById('pengguna-baru').textContent = new Intl.NumberFormat('id-ID').format(stats.penggunaBaru);

    }



    // --- LOGIKA UTAMA ---

    initCharts();



   if (typeof window.Echo !== 'undefined' && {{ Auth::check() && strtolower(Auth::user()->role) === 'admin' ? 'true' : 'false' }}) {

        // Listener untuk notifikasi (modal dan tabel)

        window.Echo.private('admin-notifications')

            .listen('AdminNotificationEvent', (e) => {

                console.log('Event AdminNotificationEvent diterima:', e);

                

                // Modal web

                showNotificationModal(e.title, e.message, e.url);

                addNotificationToTable(e);

    

                // Notifikasi browser

                if (Notification.permission === "granted") {

                    new Notification(e.title, {

                        body: e.message,

                        icon: '{{ asset("storage/uploads/sancaka.png") }}' // icon dari storage

                    });

                }

            });

    

        // Listener untuk update data dashboard (statistik, grafik, aktivitas)

        window.Echo.private('admin-dashboard')

            .listen('DashboardUpdated', (e) => {

                console.log('Event DashboardUpdated diterima:', e);

                updateStats(e.stats);

                updateChart(adminTransactionChart, e.chartData);

                updateChart(spxScanChart, e.spxChartData);

                updateRecentActivity(e.pesananTerbaru);

                

                if (Notification.permission === "granted") {

                    new Notification(e.title, {

                        body: e.message,

                        icon: '{{ asset("storage/uploads/sancaka.png") }}' // icon dari storage

                    });

                }

            });

        

        // ✅ TAMBAHAN: Listener untuk update slider

        const adminSliderComponent = document.querySelector('#admin-slider');

        if (adminSliderComponent && adminSliderComponent.__x) {

            window.Echo.channel('site-updates')

                .listen('SliderUpdated', (e) => {

                    console.log('Admin slider received update:', e);

                    adminSliderComponent.__x.getUnobservedData().slides = e.slides;

                    adminSliderComponent.__x.getUnobservedData().activeSlide = 0;

                });

        }

    

        // ✅ TAMBAHAN: Request izin notifikasi jika belum diberikan

        if (Notification.permission !== "granted") {

            Notification.requestPermission();

        }

    }

});

</script>

@endpush

