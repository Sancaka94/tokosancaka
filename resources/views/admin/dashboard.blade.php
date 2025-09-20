@extends('layouts.admin')

@section('title', 'Dashboard Admin')
@section('page-title', 'Dashboard')

@section('content')
{{-- 
    ============================================================================
    PERBAIKAN KUNCI: 
    Wrapper x-data sekarang membungkus SEMUA elemen yang membutuhkannya, 
    termasuk Modal Notifikasi yang berada di bagian bawah file ini. 
    Ini menyelesaikan error "notificationModal is not defined".
    ============================================================================
--}}
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
    <div x-data="{ activeSlide: 0, slides: {{ json_encode($slides ?? []) }} }" x-init="if (slides.length > 1) { setInterval(() => { activeSlide = (activeSlide + 1) % slides.length }, 5000) }" id="customer-slider" class="relative w-full max-w-7xl mx-auto rounded-lg shadow-lg overflow-hidden dark:bg-gray-800">
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
                <button @click="activeSlide = (activeSlide - 1 + slides.length) % slides.length" class="bg-white/50 hover:bg-white text-gray-800 rounded-full w-10 h-10 flex items-center justify-center shadow-md">
                    &#10094;
                </button>
                <button @click="activeSlide = (activeSlide + 1) % slides.length" class="bg-white/50 hover:bg-white text-gray-800 rounded-full w-10 h-10 flex items-center justify-center shadow-md">
                    &#10095;
                </button>
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

    {{-- Tabel Notifikasi Terbaru --}}
    <div class="mt-8">
        <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-200 mb-4">Aktivitas Terbaru</h3>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-300 uppercase tracking-wider">Notifikasi</th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-300 uppercase tracking-wider">Waktu</th>
                        </tr>
                    </thead>
                    <tbody id="notification-table-body" class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse ($recentNotifications ?? [] as $notification)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 @if(is_null($notification->read_at)) bg-blue-50 dark:bg-blue-900/50 font-semibold @endif">
                            <td class="px-6 py-4">
                                <a href="{{ $notification->data['url'] ?? '#' }}" class="block text-gray-800 dark:text-gray-200 hover:text-indigo-600 dark:hover:text-indigo-400">
                                    <p class="font-medium">{{ $notification->data['title'] ?? 'Notifikasi' }}</p>
                                    <p class="text-xs text-gray-600 dark:text-gray-400">{{ $notification->data['message'] ?? 'Tidak ada detail.' }}</p>
                                </a>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-gray-500 dark:text-gray-400">
                                {{ $notification->created_at->diffForHumans() }}
                            </td>
                        </tr>
                        @empty
                        <tr id="no-notification-row">
                            <td colspan="2" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
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
        @include('layouts.partials.stat-card', ['id' => 'total-pendapatan', 'title' => 'Total Pendapatan', 'value' => 'Rp ' . number_format($totalPendapatan ?? 0, 0, ',', '.'), 'icon' => 'fa-dollar-sign', 'color' => 'green'])
        @include('layouts.partials.stat-card', ['id' => 'total-pesanan', 'title' => 'Total Pesanan', 'value' => number_format($totalPesanan ?? 0, 0, ',', '.'), 'icon' => 'fa-box', 'color' => 'blue'])
        @include('layouts.partials.stat-card', ['id' => 'jumlah-toko', 'title' => 'Jumlah Toko', 'value' => number_format($jumlahToko ?? 0, 0, ',', '.'), 'icon' => 'fa-store', 'color' => 'indigo'])
        @include('layouts.partials.stat-card', ['id' => 'pengguna-baru', 'title' => 'Pengguna Baru (30 Hari)', 'value' => number_format($penggunaBaru ?? 0, 0, ',', '.'), 'icon' => 'fa-user-plus', 'color' => 'yellow'])
    </div>

    {{-- Grafik dan Aktivitas Terbaru --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-8">
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-lg">
                <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-200 mb-4">Grafik Pendapatan (30 Hari)</h3>
                <div class="relative h-80"><canvas id="adminTransactionChart"></canvas></div>
            </div>
            <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-lg">
                <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-200 mb-4">Grafik Scan SPX (30 Hari)</h3>
                <div class="relative h-80"><canvas id="spxScanChart"></canvas></div>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-lg">
            <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-200 mb-4">Aktivitas Pesanan Terbaru</h3>
            <div id="recent-activity-container" class="space-y-4">
                @forelse ($pesananTerbaru as $pesanan)
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-gray-100 dark:bg-gray-700"><i class="fas fa-shopping-bag text-gray-500 dark:text-gray-400"></i></div>
                    <div class="ml-4 flex-1">
                        <p class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{ $pesanan->resi ?? $pesanan->nomor_invoice }}</p>
                        <p class="text-xs text-gray-600 dark:text-gray-400">dari <span class="font-medium">{{ $pesanan->toko->nama_toko ?? 'Toko Dihapus' }}</span></p>
                    </div>
                    <span class="text-sm font-bold text-green-600 dark:text-green-400">Rp {{ number_format($pesanan->total_harga_barang, 0, ',', '.') }}</span>
                </div>
                @empty
                <p id="no-recent-activity" class="text-sm text-gray-500 dark:text-gray-400 text-center py-4">Belum ada aktivitas pesanan.</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Rekapitulasi Ekspedisi --}}
    <div class="mt-8">
        <h3 class="text-2xl font-semibold leading-tight text-gray-800 dark:text-gray-100 mb-4">Rekap Transaksi Ekspedisi</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
            @forelse ($rekapEkspedisi as $item)
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-5">
                <div class="flex items-center justify-between mb-4 h-12">
                    <img src="{{ $item->logo }}" alt="Logo {{ $item->nama }}" class="h-full w-auto max-w-[150px] object-contain" onerror="this.onerror=null;this.src='https://placehold.co/120x40/f9fafb/374151?text={{ urlencode($item->nama) }}';">
                    <a href="#" class="text-sm font-medium text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300">Detail</a>
                </div>
                <div class="grid grid-cols-3 gap-4 text-center border-t border-gray-200 dark:border-gray-700 pt-4">
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Order</p>
                        <p class="text-lg font-bold text-gray-800 dark:text-gray-200">{{ number_format($item->total_order) }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Pelanggan</p>
                        <p class="text-lg font-bold text-gray-800 dark:text-gray-200">{{ number_format($item->total_pelanggan) }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Profit</p>
                        <p class="text-lg font-bold text-green-600 dark:text-green-400">@if($item->total_profit > 0) Rp {{ number_format($item->total_profit) }} @else Rp 0 @endif</p>
                    </div>
                </div>
            </div>
            @empty
            <p class="col-span-full text-center text-gray-500 dark:text-gray-400 py-10">Belum ada data ekspedisi yang dikonfigurasi.</p>
            @endforelse
        </div>
    </div>

    {{-- Modal Notifikasi (SEKARANG DI DALAM WRAPPER x-data) --}}
    <div x-show="notificationModal.open" x-cloak x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 z-[100] flex items-center justify-center bg-black bg-opacity-50">
        <div @click.away="notificationModal.open = false" x-show="notificationModal.open" x-cloak class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-md mx-4 transform transition-all" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95">
            <div class="p-6 text-center">
                <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100 dark:bg-green-900/50">
                    <i class="fas fa-bell fa-2x text-green-600 dark:text-green-400 animate-bounce"></i>
                </div>
                <h3 class="mt-5 text-xl font-semibold text-gray-800 dark:text-gray-100" x-text="notificationModal.title"></h3>
                <div class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                    <p x-text="notificationModal.message"></p>
                </div>
            </div>
            <div class="bg-gray-50 dark:bg-gray-900 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse rounded-b-lg">
                <a :href="notificationModal.url" @click="notificationModal.open = false" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm">
                    Lihat Detail
                </a>
                <button @click="notificationModal.open = false" type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-700 text-base font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none sm:mt-0 sm:w-auto sm:text-sm">
                    Tutup
                </button>
            </div>
        </div>
    </div>

</div> {{-- Penutup untuk Wrapper x-data utama --}}
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const notificationTableBody = document.getElementById('notification-table-body');
        let adminTransactionChart, spxScanChart;

        function showNotificationModal(title, message, url) {
            window.dispatchEvent(new CustomEvent('new-notification', { detail: { title, message, url } }));
        }

        function addNotificationToTable(notification) {
            const noNotificationRow = document.getElementById('no-notification-row');
            if (noNotificationRow) noNotificationRow.remove();
            const newRow = document.createElement('tr');
            newRow.className = 'hover:bg-gray-50 dark:hover:bg-gray-700 bg-blue-50 dark:bg-blue-900/50 font-semibold';
            newRow.innerHTML = `
                <td class="px-6 py-4">
                    <a href="${notification.url || '#'}" class="block text-gray-800 dark:text-gray-200 hover:text-indigo-600 dark:hover:text-indigo-400">
                        <p class="font-medium">${notification.title || 'Notifikasi'}</p>
                        <p class="text-xs text-gray-600 dark:text-gray-400">${notification.message || 'Tidak ada detail.'}</p>
                    </a>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-gray-500 dark:text-gray-400">Baru saja</td>`;
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
            const chartData = @json($chartData ?? ['labels' => [], 'data' => []]);
            const ctx = document.getElementById('adminTransactionChart');
            if (ctx) {
                adminTransactionChart = new Chart(ctx, { type: 'line', data: { labels: chartData.labels, datasets: [{ label: 'Total Pendapatan', data: chartData.data, borderColor: 'rgb(79, 70, 229)', backgroundColor: 'rgba(79, 70, 229, 0.1)', fill: true, tension: 0.4 }] }, options: chartOptions(localStorage.getItem('darkMode') === 'true') });
            }
            const spxChartData = @json($spxChartData ?? ['labels' => [], 'data' => []]);
            const spxCtx = document.getElementById('spxScanChart');
            if (spxCtx) {
                spxScanChart = new Chart(spxCtx, { type: 'bar', data: { labels: spxChartData.labels, datasets: [{ label: 'Total Scan SPX', data: spxChartData.data, borderColor: 'rgb(239, 68, 68)', backgroundColor: 'rgba(239, 68, 68, 0.5)', borderWidth: 1 }] }, options: chartOptions(localStorage.getItem('darkMode') === 'true') });
            }
            // Listener untuk update chart saat dark mode berubah
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
            document.getElementById('total-pendapatan').textContent = `Rp ${new Intl.NumberFormat('id-ID').format(stats.totalPendapatan)}`;
            document.getElementById('total-pesanan').textContent = new Intl.NumberFormat('id-ID').format(stats.totalPesanan);
            document.getElementById('jumlah-toko').textContent = new Intl.NumberFormat('id-ID').format(stats.jumlahToko);
            document.getElementById('pengguna-baru').textContent = new Intl.NumberFormat('id-ID').format(stats.penggunaBaru);
        }

        function updateRecentActivity(activities) { /* ... implementasi ... */ }
        function updateChart(chart, newData) { /* ... implementasi ... */ }

        initCharts();

        if (typeof window.Echo !== 'undefined' && {{ Auth::check() && strtolower(Auth::user()->role) === 'admin' ? 'true' : 'false' }}) {
            window.Echo.private('admin-notifications')
                .listen('AdminNotificationEvent', (e) => {
                    console.log('Event AdminNotificationEvent diterima:', e);
                    showNotificationModal(e.title, e.message, e.url);
                    addNotificationToTable(e);
                    if (Notification.permission === "granted") {
                        new Notification(e.title, { body: e.message, icon: '{{ asset("storage/uploads/sancaka.png") }}' });
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

