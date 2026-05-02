<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Analitik</title>
    <!-- Tailwind CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-50 text-gray-900 font-sans antialiased">

    <div class="max-w-7xl mx-auto p-8">
        <!-- Header -->
        <div class="flex justify-between items-center mb-8 border-b border-gray-200 pb-4">
            <h1 class="text-2xl font-semibold tracking-tight text-black">Dashboard Analitik</h1>
            <a href="{{ route('cities.index') }}" class="px-4 py-2 bg-green-50 border border-gray-300 text-sm font-medium rounded-md hover:bg-gray-50 transition-colors">
                Kelola Data Kota
            </a>
        </div>

        <!-- Stat Cards Section -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <!-- Total Data -->
            <div class="bg-white border border-gray-200 p-6 rounded-lg shadow-sm">
                <p class="text-xs text-gray-500 font-bold uppercase tracking-wider">Total Data Masuk</p>
                <p class="text-3xl font-extrabold text-black mt-2">{{ $totalData }}</p>
                <p class="text-xs text-gray-400 mt-1 italic">Semua Wilayah</p>
            </div>

            <!-- Tertinggi -->
            <div class="bg-white border border-gray-200 p-6 rounded-lg shadow-sm border-l-4 border-l-green-700">
                <p class="text-xs text-gray-500 font-bold uppercase tracking-wider">Wilayah Tertinggi</p>
                <p class="text-3xl font-extrabold text-black mt-2">{{ $chartData->max('total') }}</p>
                <p class="text-sm font-medium text-gray-600 mt-1">{{ $chartData->sortByDesc('total')->first()->nama_kota ?? '-' }}</p>
            </div>

            <!-- Terendah -->
            <div class="bg-white border border-gray-200 p-6 rounded-lg shadow-sm border-l-4 border-l-red-700">
                <p class="text-xs text-gray-500 font-bold uppercase tracking-wider">Wilayah Terendah</p>
                <p class="text-3xl font-extrabold text-black mt-2">{{ $chartData->min('total') }}</p>
                <p class="text-sm font-medium text-gray-600 mt-1">{{ $chartData->sortBy('total')->first()->nama_kota ?? '-' }}</p>
            </div>
        </div>

        <!-- Area Grafik (Dua Kolom) -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <!-- Kolom Kiri: Grafik Bar (Mendatar) -->
            <div class="lg:col-span-2 bg-white border border-gray-200 p-6 rounded-lg shadow-sm">
                <h2 class="text-sm font-medium text-gray-500 mb-4 uppercase tracking-tighter">Grafik Berdasarkan Wilayah</h2>
                <div class="relative h-[500px] w-full">
                    <canvas id="barChart"></canvas>
                </div>
            </div>

            <!-- Kolom Kanan: Grafik Bulat -->
            <div class="bg-white border border-gray-200 p-6 rounded-lg shadow-sm">
                <h2 class="text-sm font-medium text-gray-500 mb-4 uppercase tracking-tighter">Distribusi Persentase</h2>
                <div class="relative h-[400px] w-full flex items-center justify-center">
                    <canvas id="pieChart"></canvas>
                </div>
            </div>

        </div>
    </div>

    <script>
    const rawData = @json($chartData);
    const labels = rawData.map(item => item.nama_kota || 'Tanpa Nama');
    const dataCounts = rawData.map(item => item.total);

    const colors = [
        '#000000', '#3b82f6', '#10b981', '#f59e0b', '#ef4444', 
        '#8b5cf6', '#ec4899', '#06b6d4', '#71717a', '#a855f7',
        '#4ade80', '#fb923c', '#2dd4bf', '#6366f1'
    ];

    // 1. BAR CHART
    const ctxBar = document.getElementById('barChart');
    new Chart(ctxBar, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Jumlah',
                data: dataCounts,
                backgroundColor: '#000000', 
                borderRadius: 4,
                barPercentage: 0.8,
                categoryPercentage: 0.8
            }]
        },
        options: {
            indexAxis: 'y',
            maintainAspectRatio: false,
            scales: {
                x: { 
                    beginAtZero: true, 
                    grid: { color: '#f3f4f6' },
                    ticks: { font: { size: 11 } }
                },
                y: { 
                    grid: { display: false },
                    ticks: { font: { size: 11, weight: 'bold' } }
                }
            },
            plugins: { legend: { display: false } }
        }
    });

    // 2. DOUGHNUT CHART
    const ctxPie = document.getElementById('pieChart');
    new Chart(ctxPie, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: dataCounts,
                backgroundColor: colors,
                hoverOffset: 15,
                borderWidth: 2,
                borderColor: '#ffffff'
            }]
        },
        options: {
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        boxWidth: 10,
                        padding: 10,
                        font: { size: 9 }
                    }
                }
            },
            cutout: '65%'
        }
    });
</script>
</body>
</html>