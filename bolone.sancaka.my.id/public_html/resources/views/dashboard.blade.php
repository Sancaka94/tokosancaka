<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Analitik</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-50 text-gray-900 font-sans antialiased">

    <div class="max-w-7xl mx-auto p-8">
        
        <!-- Header -->
        <div class="flex justify-between items-center mb-8 border-b border-gray-200 pb-4">
            <h1 class="text-2xl font-semibold tracking-tight text-black">Dashboard Analitik</h1>
            
            <div class="flex items-center space-x-3">
                <a href="{{ route('dashboard.export-pdf') }}" target="_blank" 
                   class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-md hover:bg-red-700 transition-colors shadow-sm">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Export PDF
                </a>

                <a href="{{ route('cities.index') }}" 
                   class="px-4 py-2 bg-black text-white text-sm font-medium rounded-md hover:bg-gray-800 border border-black transition-colors shadow-sm">
                    Kelola Data Kota
                </a>
            </div>
        </div>

        <!-- ================= SECTION TRANSAKSI ================= -->
        <div class="mb-10">
            <h2 class="text-lg font-bold text-gray-800 mb-4 border-l-4 border-blue-600 pl-3">Statistik Transaksi (Berdasarkan Jumlah Input)</h2>
            
            <!-- Stat Cards Transaksi -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <!-- Total Transaksi -->
                <div class="bg-white border border-gray-200 p-6 rounded-lg shadow-sm">
                    <p class="text-xs text-gray-500 font-bold uppercase tracking-wider">Total Jumlah Transaksi</p>
                    <p class="text-3xl font-extrabold text-blue-600 mt-2">{{ $totalTransaksi ?? 0 }}</p>
                    <p class="text-xs text-gray-400 mt-1 italic">Akumulasi Semua Wilayah</p>
                </div>

                <!-- Transaksi Tertinggi -->
                <div class="bg-white border border-gray-200 p-6 rounded-lg shadow-sm border-l-4 border-l-blue-600">
                    <p class="text-xs text-gray-500 font-bold uppercase tracking-wider">Wilayah Transaksi Tertinggi</p>
                    <p class="text-3xl font-extrabold text-black mt-2">{{ $chartDataTransaksi->max('total_jumlah') ?? 0 }}</p>
                    <p class="text-sm font-medium text-gray-600 mt-1">{{ $chartDataTransaksi->sortByDesc('total_jumlah')->first()->nama_kota ?? '-' }}</p>
                </div>

                <!-- Transaksi Terendah -->
                <div class="bg-white border border-gray-200 p-6 rounded-lg shadow-sm border-l-4 border-l-orange-500">
                    <p class="text-xs text-gray-500 font-bold uppercase tracking-wider">Wilayah Transaksi Terendah</p>
                    <p class="text-3xl font-extrabold text-black mt-2">{{ $chartDataTransaksi->min('total_jumlah') ?? 0 }}</p>
                    <p class="text-sm font-medium text-gray-600 mt-1">{{ $chartDataTransaksi->sortBy('total_jumlah')->first()->nama_kota ?? '-' }}</p>
                </div>
            </div>

            <!-- Area Grafik Transaksi -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 bg-white border border-gray-200 p-6 rounded-lg shadow-sm">
                    <h3 class="text-sm font-medium text-gray-500 mb-4 uppercase tracking-tighter">Grafik Bar Transaksi Wilayah</h3>
                    <div class="relative h-[400px] w-full">
                        <canvas id="barChartTransaksi"></canvas>
                    </div>
                </div>
                <div class="bg-white border border-gray-200 p-6 rounded-lg shadow-sm">
                    <h3 class="text-sm font-medium text-gray-500 mb-4 uppercase tracking-tighter">Distribusi Transaksi</h3>
                    <div class="relative h-[350px] w-full flex items-center justify-center">
                        <canvas id="pieChartTransaksi"></canvas>
                    </div>
                </div>
            </div>
        </div>


        <!-- ================= SECTION MASTER KOTA ================= -->
        <div>
            <h2 class="text-lg font-bold text-gray-800 mb-4 border-l-4 border-green-600 pl-3">Statistik Master Data Kota (Frekuensi Input)</h2>
            
            <!-- Stat Cards Master Kota -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div class="bg-white border border-gray-200 p-6 rounded-lg shadow-sm">
                    <p class="text-xs text-gray-500 font-bold uppercase tracking-wider">Total Data Master</p>
                    <p class="text-3xl font-extrabold text-black mt-2">{{ $totalData }}</p>
                    <p class="text-xs text-gray-400 mt-1 italic">Frekuensi Terdaftar</p>
                </div>

                <div class="bg-white border border-gray-200 p-6 rounded-lg shadow-sm border-l-4 border-l-green-700">
                    <p class="text-xs text-gray-500 font-bold uppercase tracking-wider">Frekuensi Tertinggi</p>
                    <p class="text-3xl font-extrabold text-black mt-2">{{ $chartData->max('total') }}</p>
                    <p class="text-sm font-medium text-gray-600 mt-1">{{ $chartData->sortByDesc('total')->first()->nama_kota ?? '-' }}</p>
                </div>

                <div class="bg-white border border-gray-200 p-6 rounded-lg shadow-sm border-l-4 border-l-red-700">
                    <p class="text-xs text-gray-500 font-bold uppercase tracking-wider">Frekuensi Terendah</p>
                    <p class="text-3xl font-extrabold text-black mt-2">{{ $chartData->min('total') }}</p>
                    <p class="text-sm font-medium text-gray-600 mt-1">{{ $chartData->sortBy('total')->first()->nama_kota ?? '-' }}</p>
                </div>
            </div>

            <!-- Area Grafik Master Kota -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 bg-white border border-gray-200 p-6 rounded-lg shadow-sm">
                    <h3 class="text-sm font-medium text-gray-500 mb-4 uppercase tracking-tighter">Grafik Bar Master Wilayah</h3>
                    <div class="relative h-[400px] w-full">
                        <canvas id="barChartMaster"></canvas>
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

    <!-- Script Chart.js -->
    <script>
    // Palet Warna Universal
    const colors = [
        '#2563eb', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', 
        '#000000', '#ec4899', '#06b6d4', '#71717a', '#a855f7',
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
            datasets: [{ label: 'Total Jumlah', data: dataCountsTransaksi, backgroundColor: '#2563eb', borderRadius: 4 }]
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
       2. DATA & GRAFIK MASTER KOTA
    ========================================= */
    const rawDataMaster = @json($chartData);
    const labelsMaster = rawDataMaster.map(item => item.nama_kota || 'Tanpa Nama');
    const dataCountsMaster = rawDataMaster.map(item => item.total);

    // Bar Chart Master
    new Chart(document.getElementById('barChartMaster'), {
        type: 'bar',
        data: {
            labels: labelsMaster,
            datasets: [{ label: 'Frekuensi', data: dataCountsMaster, backgroundColor: '#000000', borderRadius: 4 }]
        },
        options: barOptions
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
    </script>
</body>
</html>