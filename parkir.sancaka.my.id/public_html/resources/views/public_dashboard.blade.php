<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Info Parkir</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; background-color: #f3f4f6; }
    </style>
</head>
<body class="text-gray-800">

    <nav class="bg-blue-600 text-white shadow-md">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center gap-3">
                    <img src="https://tokosancaka.com/storage/uploads/logo.jpeg" alt="Logo" class="h-8 w-8 bg-white rounded-full p-1">
                    <span class="font-bold text-xl tracking-wide">Portal Info Parkir</span>
                </div>
                <div>
                    <a href="{{ route('login') }}" class="text-sm font-semibold bg-white text-blue-600 hover:bg-gray-100 px-4 py-2 rounded-full transition-colors shadow-sm">
                        Login Petugas &rarr;
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <div class="mb-6 text-center md:text-left">
            <h1 class="text-2xl font-bold text-gray-800">Ringkasan Operasional</h1>
            <p class="text-gray-500 text-sm mt-1">Live Update: {{ \Carbon\Carbon::now()->translatedFormat('l, d F Y - H:i') }} WIB</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex items-center justify-between transform transition duration-300 hover:scale-105">
                <div>
                    <h5 class="text-gray-400 text-sm font-bold uppercase tracking-wider">Motor (Hari Ini)</h5>
                    <p class="text-3xl font-black text-gray-800 mt-2">{{ $data['motor_masuk'] ?? 0 }} <span class="text-sm font-medium text-gray-500">Unit</span></p>
                </div>
                <div class="text-4xl opacity-80">🏍️</div>
            </div>

            <div class="bg-gradient-to-br from-amber-400 to-orange-500 rounded-xl shadow-md p-6 flex items-center justify-between text-white transform transition duration-300 hover:scale-105" style="border-radius: 8px;">
                <div>
                    <h5 class="text-orange-100 text-xs md:text-sm font-bold uppercase tracking-wider mb-1">Pendapatan Kemarin</h5>
                    <p class="text-2xl md:text-3xl font-black">Rp {{ number_format($data['pendapatan_kemarin'] ?? 0, 0, ',', '.') }}</p>
                </div>
                <div class="text-4xl opacity-90">⏳</div>
            </div>

            <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl shadow-md p-6 flex items-center justify-between text-white transform transition duration-300 hover:scale-105" style="border-radius: 8px;">
                <div>
                    <h5 class="text-green-100 text-xs md:text-sm font-bold uppercase tracking-wider mb-1">Pendapatan Hari Ini</h5>
                    <p class="text-2xl md:text-3xl font-black">Rp {{ number_format($data['total_pendapatan'] ?? 0, 0, ',', '.') }}</p>
                </div>
                <div class="text-4xl opacity-90">💵</div>
            </div>

            <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-md p-6 flex items-center justify-between text-white transform transition duration-300 hover:scale-105" style="border-radius: 8px;">
                <div>
                    <h5 class="text-blue-100 text-xs md:text-sm font-bold uppercase tracking-wider mb-1">Pendapatan Bulan Ini</h5>
                    <p class="text-2xl md:text-3xl font-black">Rp {{ number_format($data['pendapatan_bulan_ini'] ?? 0, 0, ',', '.') }}</p>
                </div>
                <div class="text-4xl opacity-90">📈</div>
            </div>

        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="bg-gray-50 border-b border-gray-100 px-6 py-4">
                    <h3 class="font-bold text-gray-700 text-sm">Grafik Pendapatan Bersih (7 Hari Terakhir)</h3>
                </div>
                <div class="p-4">
                    <canvas id="chartHarianPublic" height="250"></canvas>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="bg-gray-50 border-b border-gray-100 px-6 py-4">
                    <h3 class="font-bold text-gray-700 text-sm">Grafik Pendapatan Bersih (6 Bulan Terakhir)</h3>
                </div>
                <div class="p-4">
                    <canvas id="chartBulananPublic" height="250"></canvas>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-8">
            <div class="bg-gray-50 border-b border-gray-100 px-6 py-4 flex justify-between items-center">
                <h3 class="font-bold text-gray-700 text-sm">Aktivitas Kendaraan Terbaru</h3>
                <span class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded-full font-semibold">Data disamarkan</span>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr class="bg-white">
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Plat Nomor</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Jenis</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Waktu Masuk</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        @forelse($recent_transactions ?? [] as $trx)
                            <tr class="hover:bg-gray-50 transition duration-150">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="font-bold text-gray-800 tracking-wider bg-gray-100 px-3 py-1 rounded border border-gray-200">
                                        {{ Str::mask($trx->plate_number, '*', 4, 3) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap capitalize text-gray-600 font-medium">
                                    {{ $trx->vehicle_type }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-gray-500">
                                    {{ $trx->entry_time ? \Carbon\Carbon::parse($trx->entry_time)->translatedFormat('H:i') . ' WIB' : '-' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if(strtolower($trx->status) == 'masuk')
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-yellow-100 text-yellow-800">
                                            <span class="w-2 h-2 mr-1.5 bg-yellow-500 rounded-full animate-pulse"></span>
                                            Sedang Parkir
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-green-100 text-green-800">
                                            Selesai / Keluar
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-8 text-center text-gray-400 italic">Belum ada aktivitas terekam.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mb-6 mt-12 text-center md:text-left">
            <h2 class="text-2xl font-bold text-gray-800">Transparansi Kas Manual</h2>
            <p class="text-gray-500 text-sm mt-1">Laporan pemasukan dan pengeluaran operasional.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex items-center justify-between">
                <div>
                    <h5 class="text-gray-400 text-sm font-bold uppercase tracking-wider">Total Pemasukan Kas</h5>
                    <p class="text-2xl font-black text-green-600 mt-2">Rp {{ number_format($totalPemasukanKas ?? 0, 0, ',', '.') }}</p>
                </div>
                <div class="text-4xl opacity-50">📥</div>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex items-center justify-between">
                <div>
                    <h5 class="text-gray-400 text-sm font-bold uppercase tracking-wider">Total Pengeluaran Kas</h5>
                    <p class="text-2xl font-black text-red-600 mt-2">Rp {{ number_format($totalPengeluaranKas ?? 0, 0, ',', '.') }}</p>
                </div>
                <div class="text-4xl opacity-50">📤</div>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex items-center justify-between">
                <div>
                    <h5 class="text-gray-400 text-sm font-bold uppercase tracking-wider">Saldo Kas Akhir</h5>
                    <p class="text-2xl font-black text-blue-600 mt-2">Rp {{ number_format($saldoKas ?? 0, 0, ',', '.') }}</p>
                </div>
                <div class="text-4xl opacity-50">💰</div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-8">
            <div class="bg-gray-50 border-b border-gray-100 px-6 py-4 flex justify-between items-center">
                <h3 class="font-bold text-gray-700 text-sm">Aktivitas Kas Terbaru</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr class="bg-white">
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Tanggal</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Kategori / Keterangan</th>
                            <th class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Nominal</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        @forelse($recent_financials ?? [] as $kas)
                            <tr class="hover:bg-gray-50 transition duration-150">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800 font-medium">
                                    {{ \Carbon\Carbon::parse($kas->tanggal)->format('d M Y') }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    <span class="font-semibold">{{ $kas->kategori }}</span>
                                    @if($kas->keterangan)
                                        <span class="text-gray-400 ml-1">- {{ $kas->keterangan }}</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right font-bold text-sm">
                                    @if($kas->jenis == 'pemasukan')
                                        <span class="text-green-600">+ Rp {{ number_format($kas->nominal, 0, ',', '.') }}</span>
                                    @else
                                        <span class="text-red-600">- Rp {{ number_format($kas->nominal, 0, ',', '.') }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-6 py-8 text-center text-gray-400 italic">Belum ada catatan kas masuk atau keluar.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </main>

    <footer class="mt-4 py-6 text-center text-gray-400 text-sm">
        &copy; {{ date('Y') }} Sistem Informasi Parkir. Hak Cipta Dilindungi.
    </footer>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            Chart.defaults.font.family = "'Inter', sans-serif";

            const rawChartData = @json($chartData);

            // Chart Harian (Line)
            if(rawChartData.harian && document.getElementById('chartHarianPublic')) {
                const ctxH = document.getElementById('chartHarianPublic').getContext('2d');
                new Chart(ctxH, {
                    type: 'line',
                    data: {
                        labels: rawChartData.harian.labels,
                        datasets: [{
                            label: 'Pendapatan Bersih (Rp)',
                            data: rawChartData.harian.data,
                            borderColor: '#10b981', // Emerald green
                            backgroundColor: 'rgba(16, 185, 129, 0.1)',
                            borderWidth: 3,
                            pointBackgroundColor: '#10b981',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                            pointRadius: 4,
                            fill: true,
                            tension: 0.4
                        }]
                    },
                    options: {
                        responsive: true, maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: { y: { beginAtZero: true, ticks: { callback: (value) => 'Rp ' + value.toLocaleString('id-ID') } } }
                    }
                });
            }

            // Chart Bulanan (Bar)
            if(rawChartData.bulanan && document.getElementById('chartBulananPublic')) {
                const ctxB = document.getElementById('chartBulananPublic').getContext('2d');
                new Chart(ctxB, {
                    type: 'bar',
                    data: {
                        labels: rawChartData.bulanan.labels,
                        datasets: [{
                            label: 'Pendapatan Bersih (Rp)',
                            data: rawChartData.bulanan.data,
                            backgroundColor: '#3b82f6', // Blue
                            borderRadius: 6
                        }]
                    },
                    options: {
                        responsive: true, maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: { y: { beginAtZero: true, ticks: { callback: (value) => 'Rp ' + value.toLocaleString('id-ID') } } }
                    }
                });
            }
        });
    </script>
</body>
</html>
