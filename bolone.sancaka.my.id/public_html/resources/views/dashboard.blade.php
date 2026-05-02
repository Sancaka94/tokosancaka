<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <!-- TANPA VITE: Menggunakan Tailwind CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-50 text-gray-900 font-sans antialiased">

    <div class="max-w-6xl mx-auto p-8">
        <!-- Header -->
        <div class="flex justify-between items-center mb-8 border-b border-gray-200 pb-4">
            <h1 class="text-2xl font-semibold tracking-tight text-black">Dashboard Analitik</h1>
            <a href="{{ route('cities.index') }}" class="px-4 py-2 bg-white border border-gray-300 text-sm font-medium rounded-md hover:bg-gray-50 transition-colors">
                Kelola Data Kota
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white border border-gray-200 p-6 rounded-lg shadow-sm">
                <p class="text-sm text-gray-500 font-medium">Total Data Masuk</p>
                <p class="text-3xl font-bold text-black mt-2">{{ $totalData }}</p>
            </div>
        </div>

        <!-- Area Grafik -->
        <div class="bg-white border border-gray-200 p-6 rounded-lg shadow-sm max-w-2xl">
            <h2 class="text-sm font-medium text-gray-500 mb-4">Grafik Berdasarkan Wilayah</h2>
            
            <!-- Ubah h-80 menjadi lebih tinggi sesuai jumlah data -->
            <div class="relative h-[500px] w-full">
                <canvas id="myChart"></canvas>
            </div>
        </div>
    </div>

    <script>
    // LOG LOG - Inisialisasi Chart monokrom
    const rawData = @json($chartData);
    const labels = rawData.map(item => item.nama_kota || 'Tanpa Keterangan');
    const dataCounts = rawData.map(item => item.total);

    const ctx = document.getElementById('myChart');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Jumlah Data',
                data: dataCounts,
                backgroundColor: '#000000', 
                borderRadius: 4,
                barPercentage: 0.9, // Naikkan ini dari 0.6 ke 0.9
                categoryPercentage: 0.8 // Tambahkan ini juga jika perlu
            }]
        },
        options: {
            indexAxis: 'y', // <--- KUNCI: Membuat grafik menjadi mendatar (Horizontal Bar)
            maintainAspectRatio: false, // Menjaga grafik tidak gepeng jika canvas ditinggikan
            scales: {
                x: { 
                    beginAtZero: true, 
                    grid: { color: '#f3f4f6' } // Garis vertikal
                },
                y: { 
                    grid: { display: false } // Sembunyikan garis horizontal
                }
            },
            plugins: { 
                legend: { display: false } 
            }
        }
    });
</script>
</body>
</html>