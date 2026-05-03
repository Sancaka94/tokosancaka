<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Dashboard Analitik</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/proj4js/2.9.0/proj4.js"></script>
    <script src="https://code.highcharts.com/maps/highmaps.js"></script>
    <script src="https://code.highcharts.com/mapdata/countries/id/id-all.js"></script>
</head>
<body class="bg-gray-50 text-gray-900 font-sans antialiased">

    <!-- Diubah ke p-4 md:p-8 agar tidak terlalu ke tengah saat di HP -->
    <div class="max-w-7xl mx-auto p-4 md:p-8">
        
        <!-- Header -->
        <div class="flex justify-between items-center mb-8 border-b border-gray-200 pb-4 relative">
            <h1 class="text-xl md:text-2xl font-semibold tracking-tight text-black">Dashboard Analitik</h1>
            
            <!-- Tombol Titik Tiga (Hanya Tampil di Mobile) -->
            <button id="mobileMenuBtn" class="md:hidden p-2 text-gray-600 hover:text-black focus:outline-none">
                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 8c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm0 2c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm0 6c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"></path>
                </svg>
            </button>

            <!-- Container Tombol (Dropdown di Mobile, Flex Normal di PC) -->
            <div id="actionMenu" class="hidden absolute top-12 right-0 z-50 w-56 p-4 flex-col gap-3 bg-white border border-gray-200 rounded-lg shadow-xl md:flex md:static md:w-auto md:p-0 md:flex-row md:items-center md:gap-3 md:bg-transparent md:border-none md:shadow-none">
                
                <a href="{{ route('dashboard.export-pdf') }}" target="_blank" 
                   class="inline-flex justify-center items-center gap-2 px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-md hover:bg-red-700 transition-colors shadow-sm w-full md:w-auto">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Export PDF
                </a>

                <a href="{{ route('cities.index') }}" 
                   class="flex justify-center px-4 py-2 bg-black text-white text-sm font-medium rounded-md hover:bg-gray-800 border border-black transition-colors shadow-sm w-full md:w-auto">
                    Kelola Data Kota
                </a>

                <!-- Tombol Logout -->
                <form method="POST" action="{{ route('logout') }}" class="inline-block m-0 w-full md:w-auto">
                    @csrf
                    <button type="submit" onclick="return confirm('Apakah Anda yakin ingin keluar?');" 
                            class="w-full justify-center px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-md hover:bg-red-50 hover:text-red-700 hover:border-red-300 transition-all shadow-sm inline-flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                        </svg>
                        Logout
                    </button>
                </form>
            </div>
        </div>

        <!-- ================= SECTION TRANSAKSI ================= -->
        <div class="mb-12">
            <h2 class="text-lg font-bold text-black mb-4 border-l-4 border-black pl-3">Statistik Data (Berdasarkan Jumlah Input)</h2>
            
            <!-- Stat Cards Transaksi -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <!-- Total Transaksi -->
                <div class="bg-white border border-gray-200 p-6 rounded-lg shadow-sm">
                    <p class="text-xs text-gray-500 font-bold uppercase tracking-wider">Total Jumlah Input</p>
                    <p class="text-3xl font-extrabold text-black mt-2">{{ $totalTransaksi ?? 0 }}</p>
                    <p class="text-xs text-gray-400 mt-1 italic">Akumulasi Semua Wilayah</p>
                </div>

                <!-- Transaksi Tertinggi -->
                <div class="bg-white border border-gray-200 p-6 rounded-lg shadow-sm border-l-4 border-black">
                    <p class="text-xs text-gray-500 font-bold uppercase tracking-wider">Wilayah Input Tertinggi</p>
                    <p class="text-3xl font-extrabold text-black mt-2">{{ $chartDataTransaksi->max('total_jumlah') ?? 0 }}</p>
                    <p class="text-sm font-medium text-gray-600 mt-1">{{ $chartDataTransaksi->sortByDesc('total_jumlah')->first()->nama_kota ?? '-' }}</p>
                </div>

                <!-- Transaksi Terendah -->
                <div class="bg-white border border-gray-200 p-6 rounded-lg shadow-sm border-l-4 border-black">
                    <p class="text-xs text-gray-500 font-bold uppercase tracking-wider">Wilayah Input Terendah</p>
                    <p class="text-3xl font-extrabold text-black mt-2">{{ $chartDataTransaksi->min('total_jumlah') ?? 0 }}</p>
                    <p class="text-sm font-medium text-gray-600 mt-1">{{ $chartDataTransaksi->sortBy('total_jumlah')->first()->nama_kota ?? '-' }}</p>
                </div>
            </div>

            <!-- Area Grafik Transaksi -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 bg-white border border-gray-200 p-6 rounded-lg shadow-sm">
                    <h3 class="text-sm font-medium text-gray-500 mb-4 uppercase tracking-tighter">Grafik Bar Input Wilayah</h3>
                    <div class="relative h-[400px] w-full">
                        <canvas id="barChartTransaksi"></canvas>
                    </div>
                </div>
                <div class="bg-white border border-gray-200 p-6 rounded-lg shadow-sm">
                    <h3 class="text-sm font-medium text-gray-500 mb-4 uppercase tracking-tighter">Distribusi Data</h3>
                    <div class="relative h-[350px] w-full flex items-center justify-center">
                        <canvas id="pieChartTransaksi"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- ================= SECTION MASTER KOTA ================= -->
        <div>
            <h2 class="text-lg font-bold text-black mb-4 border-l-4 border-black pl-3">Statistik Master Data Kota (Frekuensi Input)</h2>
            
            <!-- Stat Cards Master Kota -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div class="bg-white border border-gray-200 p-6 rounded-lg shadow-sm">
                    <p class="text-xs text-gray-500 font-bold uppercase tracking-wider">Total Data Master</p>
                    <p class="text-3xl font-extrabold text-black mt-2">{{ $totalData }}</p>
                    <p class="text-xs text-gray-400 mt-1 italic">Frekuensi Terdaftar</p>
                </div>

                <div class="bg-white border border-gray-200 p-6 rounded-lg shadow-sm border-l-4 border-black">
                    <p class="text-xs text-gray-500 font-bold uppercase tracking-wider">Frekuensi Tertinggi</p>
                    <p class="text-3xl font-extrabold text-black mt-2">{{ $chartData->max('total') }}</p>
                    <p class="text-sm font-medium text-gray-600 mt-1">{{ $chartData->sortByDesc('total')->first()->nama_kota ?? '-' }}</p>
                </div>

                <div class="bg-white border border-gray-200 p-6 rounded-lg shadow-sm border-l-4 border-black">
                    <p class="text-xs text-gray-500 font-bold uppercase tracking-wider">Frekuensi Terendah</p>
                    <p class="text-3xl font-extrabold text-black mt-2">{{ $chartData->min('total') }}</p>
                    <p class="text-sm font-medium text-gray-600 mt-1">{{ $chartData->sortBy('total')->first()->nama_kota ?? '-' }}</p>
                </div>
            </div>

            <!-- Area Grafik Master Kota -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 bg-white border border-gray-200 p-6 rounded-lg shadow-sm">
                    <h3 class="text-sm font-medium text-gray-500 mb-4 uppercase tracking-tighter">Peta Master Wilayah</h3>
                    <div class="relative h-[400px] w-full">
                        <div id="mapChartMaster" class="h-full w-full rounded-md" style="min-height: 400px;"></div>
                    </div>
                </div>
                <div class="bg-white border border-gray-200 p-6 rounded-lg shadow-sm">
                    <h3 class="text-sm font-medium text-gray-500 mb-4 uppercase tracking-tighter">Distribusi Master Data</h3>
                    <div class="relative h-[350px] w-full flex items-center justify-center">
                        <canvas id="pieChartMaster"></canvas>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Script Chart.js & Highmaps -->
    <script>
    // Palet Warna Universal
    const colors = [
        '#000000', '#3b82f6', '#10b981', '#f59e0b', '#ef4444', 
        '#8b5cf6', '#ec4899', '#06b6d4', '#71717a', '#a855f7',
        '#4ade80', '#fb923c', '#2dd4bf', '#6366f1'
    ];

    // Opsi Default Chart untuk Bar
    const barOptions = {
        indexAxis: 'y',
        maintainAspectRatio: false,
        scales: {
            x: { beginAtZero: true, grid: { color: '#f3f4f6' }, ticks: { font: { size: 11 } } },
            y: { grid: { display: false }, ticks: { font: { size: 11, weight: 'bold' } } }
        },
        plugins: { legend: { display: false } }
    };

    // Opsi Default Chart untuk Pie/Doughnut
    const pieOptions = {
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'bottom', labels: { boxWidth: 10, padding: 10, font: { size: 9 } } }
        },
        cutout: '65%'
    };

    /* =========================================
       1. DATA & GRAFIK TRANSAKSI
    ========================================= */
    const rawDataTransaksi = @json($chartDataTransaksi ?? []);
    const labelsTransaksi = rawDataTransaksi.map(item => item.nama_kota || 'Tanpa Nama');
    const dataCountsTransaksi = rawDataTransaksi.map(item => item.total_jumlah);

    // Bar Chart Transaksi
    new Chart(document.getElementById('barChartTransaksi'), {
        type: 'bar',
        data: {
            labels: labelsTransaksi,
            datasets: [{ 
                label: 'Total Jumlah', 
                data: dataCountsTransaksi, 
                backgroundColor: '#000000',
                borderRadius: 4,
                maxBarThickness: 40 
            }]
        },
        options: barOptions
    });

    // Doughnut Chart Transaksi
    new Chart(document.getElementById('pieChartTransaksi'), {
        type: 'doughnut',
        data: {
            labels: labelsTransaksi,
            datasets: [{ data: dataCountsTransaksi, backgroundColor: colors, hoverOffset: 15, borderWidth: 2, borderColor: '#fff' }]
        },
        options: pieOptions
    });

    /* =========================================
       2. DATA & GRAFIK MASTER KOTA (PETA & PIE)
    ========================================= */
    const rawDataMaster = @json($chartData);
    const labelsMaster = rawDataMaster.map(item => item.nama_kota || 'Tanpa Nama');
    const dataCountsMaster = rawDataMaster.map(item => item.total);

    // PENTING: Untuk menampilkan peta Choropleth ini berfungsi dengan baik, 
    // pastikan Anda mengirim variabel $chartDataMap dari Controller Laravel Anda.
    // Format yang dibutuhkan: 
    // [ { "hc-key": "id-ji", "value": 150, "name": "Jawa Timur", "cities": [ { "name": "Surabaya", "count": 100 } ] } ]
    
    // Fallback sementara jika data peta dari backend belum siap, gunakan array kosong
    const mapDataMaster = @json($chartDataMap ?? []);

    // Inisialisasi Peta (Choropleth Map)
    const mapChart = Highcharts.mapChart('mapChartMaster', {
        chart: { 
            map: 'countries/id/id-all', 
            backgroundColor: 'transparent' 
        },
        title: { text: null },
        mapNavigation: { 
            enabled: true, 
            buttonOptions: { verticalAlign: 'bottom' } 
        },
        colorAxis: {
            min: 0,
            minColor: '#e0f2fe', // Warna biru muda
            maxColor: '#0369a1'  // Warna biru tua
        },
        tooltip: {
            useHTML: true,
            formatter: function () {
                let html = `<div style="min-width: 150px; padding: 5px;">
                                <strong style="font-size: 14px; border-bottom: 1px solid #ccc; display: block; padding-bottom: 5px; margin-bottom: 5px;">
                                    ${this.point.name}
                                </strong>`;
                
                if (this.point.cities && this.point.cities.length > 0) {
                    html += `<ul style="margin: 0; padding-left: 15px; font-size: 12px; color: #333;">`;
                    this.point.cities.forEach(city => {
                        html += `<li>${city.name}: <b>${city.count}</b></li>`;
                    });
                    html += `</ul>`;
                } else {
                    html += `<span style="font-size: 12px; color: #666;">Tidak ada data terinci</span>`;
                }
                
                html += `</div>`;
                return html;
            }
        },
        series: [
            {
                type: 'map',
                name: 'Provinsi',
                joinBy: 'hc-key',
                data: mapDataMaster,
                states: {
                    hover: { color: '#fca5a5' } // Warna saat di-hover
                },
                dataLabels: { 
                    enabled: true, 
                    format: '{point.name}',
                    style: { fontSize: '10px', fontWeight: 'normal', textOutline: 'none' }
                }
            }
        ]
    });

    // Doughnut Chart Master
    new Chart(document.getElementById('pieChartMaster'), {
        type: 'doughnut',
        data: {
            labels: labelsMaster,
            datasets: [{ data: dataCountsMaster, backgroundColor: colors, hoverOffset: 15, borderWidth: 2, borderColor: '#fff' }]
        },
        options: pieOptions
    });

    /* =========================================
       3. SCRIPT TOGGLE MENU MOBILE
    ========================================= */
    const mobileBtn = document.getElementById('mobileMenuBtn');
    const actionMenu = document.getElementById('actionMenu');

    // Toggle menu saat tombol titik tiga di klik
    mobileBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        actionMenu.classList.toggle('hidden');
        actionMenu.classList.toggle('flex');
    });

    // Otomatis menutup dropdown menu jika klik di luar area menu
    document.addEventListener('click', (e) => {
        if (!mobileBtn.contains(e.target) && !actionMenu.contains(e.target)) {
            actionMenu.classList.add('hidden');
            actionMenu.classList.remove('flex');
        }
    });
    </script>
</body>
</html>