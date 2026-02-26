@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-2 md:gap-0">
    <h1 class="text-xl md:text-2xl font-bold text-gray-800">
        @if(auth()->user()->role == 'superadmin')
            Overview Panel Super Admin
        @elseif(auth()->user()->role == 'admin')
            Overview Panel Admin Tenant
        @else
            Overview Panel Operator
        @endif
    </h1>
    <div class="text-sm text-gray-500 font-medium hidden md:block">
        {{ \Carbon\Carbon::now()->translatedFormat('l, d F Y') }}
    </div>
</div>

@if(in_array(auth()->user()->role, ['superadmin', 'admin']))
<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 md:gap-6 mb-6">

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 flex items-center justify-between transition-transform hover:scale-[1.02]">
        <div>
            <h5 class="text-gray-500 text-xs md:text-sm font-semibold uppercase tracking-wider">Motor Masuk (Hari Ini)</h5>
            <p class="text-2xl md:text-3xl font-bold mt-2 text-gray-800">{{ $data['motor_masuk'] ?? 0 }} <span class="text-sm font-normal text-gray-500">Unit</span></p>
        </div>
        <div class="text-4xl opacity-80">🏍️</div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 flex items-center justify-between transition-transform hover:scale-[1.02]">
        <div>
            <h5 class="text-gray-500 text-xs md:text-sm font-semibold uppercase tracking-wider">Mobil Masuk (Hari Ini)</h5>
            <p class="text-2xl md:text-3xl font-bold mt-2 text-gray-800">{{ $data['mobil_masuk'] ?? 0 }} <span class="text-sm font-normal text-gray-500">Unit</span></p>
        </div>
        <div class="text-4xl opacity-80">🚗</div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-green-200 p-5 flex items-center justify-between transition-transform hover:scale-[1.02] relative overflow-hidden">
        <div class="z-10">
            <h5 class="text-gray-500 text-xs md:text-sm font-semibold uppercase tracking-wider">Pendapatan Bersih (Hari Ini)</h5>
            <p class="text-2xl md:text-3xl font-bold mt-2 text-green-600">Rp {{ number_format($data['total_pendapatan'] ?? 0, 0, ',', '.') }}</p>
        </div>
        <div class="text-4xl opacity-80 z-10">💵</div>
        <div class="absolute -right-4 -bottom-4 w-24 h-24 bg-green-50 rounded-full blur-2xl opacity-60"></div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-blue-200 p-5 flex flex-col justify-center transition-transform hover:scale-[1.02] relative overflow-hidden">
        <div class="z-10">
            <h5 class="text-gray-500 text-xs md:text-sm font-semibold uppercase tracking-wider mb-1">Pendapatan Bulan Ini</h5>
            <p class="text-xl md:text-2xl font-bold text-blue-700 mb-2">Rp {{ number_format($data['perbandingan']['bulan_ini'] ?? 0, 0, ',', '.') }}</p>
            <div class="text-[10px] md:text-xs font-bold flex items-center gap-1 flex-wrap">
                @if(isset($data['perbandingan']['trend']))
                    @if($data['perbandingan']['trend'] == 'naik')
                        <span class="text-green-600 bg-green-100 px-2 py-0.5 rounded-full">&uarr; Naik {{ $data['perbandingan']['persentase'] }}%</span>
                    @elseif($data['perbandingan']['trend'] == 'turun')
                        <span class="text-red-600 bg-red-100 px-2 py-0.5 rounded-full">&darr; Turun {{ $data['perbandingan']['persentase'] }}%</span>
                    @else
                        <span class="text-gray-600 bg-gray-100 px-2 py-0.5 rounded-full">Sama dengan bln lalu</span>
                    @endif
                @endif
            </div>
        </div>
        <div class="absolute right-4 top-1/2 -translate-y-1/2 text-5xl opacity-10 hidden sm:block">📈</div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-4 md:gap-6 mb-8">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="bg-gray-50 border-b border-gray-100 px-6 py-4">
            <h3 class="font-bold text-gray-700 text-sm">Grafik Pendapatan Bersih (7 Hari Terakhir)</h3>
        </div>
        <div class="p-4 relative w-full h-[250px]">
            <canvas id="chartHarian" height="250"></canvas>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="bg-gray-50 border-b border-gray-100 px-6 py-4">
            <h3 class="font-bold text-gray-700 text-sm">Grafik Pendapatan Bersih (6 Bulan Terakhir)</h3>
        </div>
        <div class="p-4 relative w-full h-[250px]">
            <canvas id="chartBulanan" height="250"></canvas>
        </div>
    </div>
</div>
@endif

@if(auth()->user()->role == 'operator')
<div class="bg-white rounded-xl border-l-4 border-blue-600 mb-8 shadow-sm p-6">
    <h3 class="text-lg font-bold text-gray-800 mb-2">Selamat Bertugas, {{ auth()->user()->name }}!</h3>
    <p class="text-gray-600 mb-4 text-sm md:text-base">Silakan menuju halaman operasional untuk mencatat plat nomor kendaraan masuk dan keluar area parkir hari ini.</p>
    <a href="{{ route('transactions.index') }}" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded-lg shadow-md transition-colors inline-block text-sm md:text-base">
        Masuk ke Panel Transaksi &rarr;
    </a>
</div>
@endif

<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-8">
    <div class="bg-gray-50 border-b border-gray-100 px-6 py-4 flex flex-wrap justify-between items-center gap-2">
        <h3 class="font-bold text-gray-800 uppercase tracking-wide text-xs md:text-sm">Aktivitas Parkir Terbaru</h3>
        <a href="{{ route('transactions.index') }}" class="text-xs md:text-sm text-blue-600 hover:text-blue-800 hover:underline font-semibold">Lihat Semua Data &rarr;</a>
    </div>

    <div class="w-full overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead>
                <tr class="bg-white">
                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider whitespace-nowrap">Plat Nomor</th>
                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider whitespace-nowrap">Jenis</th>
                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider whitespace-nowrap">Waktu Masuk</th>
                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider whitespace-nowrap">Operator</th>
                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider whitespace-nowrap">Status</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-100">
                @forelse($recent_transactions ?? [] as $trx)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="font-black text-gray-800 text-base md:text-lg tracking-widest bg-gray-100 px-3 py-1 rounded border border-gray-200">
                                {{ $trx->plate_number }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap capitalize text-sm font-medium text-gray-700">
                            {{ $trx->vehicle_type }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $trx->entry_time ? \Carbon\Carbon::parse($trx->entry_time)->translatedFormat('H:i') . ' WIB' : '-' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                            {{ optional($trx->operator)->name ?? 'Sistem' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if(strtolower($trx->status) == 'masuk')
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-yellow-100 text-yellow-800">
                                    <span class="w-2 h-2 mr-1.5 bg-yellow-500 rounded-full animate-pulse"></span> Sedang Parkir
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-green-100 text-green-800">
                                    Selesai (Rp {{ number_format(($trx->fee ?? 0) + ($trx->toilet_fee ?? 0), 0, ',', '.') }})
                                </span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-sm text-gray-500 italic">
                            Belum ada aktivitas kendaraan terekam.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>


@if(in_array(auth()->user()->role, ['superadmin', 'admin']))
<div class="mt-10 mb-4">
    <h2 class="text-lg md:text-xl font-bold text-gray-800">Transparansi Kas Operasional</h2>
    <p class="text-gray-500 text-sm mt-1">Laporan pemasukan dan pengeluaran manual sistem.</p>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 md:gap-6 mb-8">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 flex items-center justify-between">
        <div>
            <h5 class="text-gray-400 text-xs md:text-sm font-bold uppercase tracking-wider">Total Pemasukan Kas</h5>
            <p class="text-xl md:text-2xl font-black text-green-600 mt-1">Rp {{ number_format($totalPemasukanKas ?? 0, 0, ',', '.') }}</p>
        </div>
        <div class="text-4xl opacity-50 hidden sm:block">📥</div>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 flex items-center justify-between">
        <div>
            <h5 class="text-gray-400 text-xs md:text-sm font-bold uppercase tracking-wider">Total Pengeluaran Kas</h5>
            <p class="text-xl md:text-2xl font-black text-red-600 mt-1">Rp {{ number_format($totalPengeluaranKas ?? 0, 0, ',', '.') }}</p>
        </div>
        <div class="text-4xl opacity-50 hidden sm:block">📤</div>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 flex items-center justify-between sm:col-span-2 md:col-span-1">
        <div>
            <h5 class="text-gray-400 text-xs md:text-sm font-bold uppercase tracking-wider">Saldo Kas Akhir</h5>
            <p class="text-xl md:text-2xl font-black text-blue-600 mt-1">Rp {{ number_format($saldoKas ?? 0, 0, ',', '.') }}</p>
        </div>
        <div class="text-4xl opacity-50 hidden sm:block">💰</div>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-8">
    <div class="bg-gray-50 border-b border-gray-100 px-6 py-4 flex flex-wrap justify-between items-center gap-2">
        <h3 class="font-bold text-gray-800 uppercase tracking-wide text-xs md:text-sm">Catatan Kas Terbaru</h3>
        <a href="{{ route('financial.index') }}" class="text-xs md:text-sm text-green-600 hover:text-green-800 hover:underline font-semibold">Kelola Buku Kas &rarr;</a>
    </div>
    <div class="w-full overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead>
                <tr class="bg-white">
                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider whitespace-nowrap">Tanggal</th>
                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider whitespace-nowrap">Kategori</th>
                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider whitespace-nowrap">Keterangan</th>
                    <th class="px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase tracking-wider whitespace-nowrap">Nominal</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-100">
                @forelse($recent_financials ?? [] as $kas)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800 font-bold">
                            {{ \Carbon\Carbon::parse($kas->tanggal)->format('d/m/Y') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-700">{{ $kas->kategori }}</td>
                        <td class="px-6 py-4 text-sm text-gray-500 min-w-[200px]">{{ $kas->keterangan ?? '-' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-right font-black text-sm">
                            @if($kas->jenis == 'pemasukan')
                                <span class="text-green-600">+ Rp {{ number_format($kas->nominal, 0, ',', '.') }}</span>
                            @else
                                <span class="text-red-600">- Rp {{ number_format($kas->nominal, 0, ',', '.') }}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-6 py-8 text-center text-sm text-gray-500 italic">
                            Belum ada catatan kas yang dimasukkan.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-8 border-t-4 border-t-purple-500">
    <div class="bg-gray-50 border-b border-gray-100 px-6 py-4 flex flex-wrap justify-between items-center gap-2">
        <h3 class="font-bold text-gray-800 text-sm">Estimasi Gaji Pegawai (Hari Ini)</h3>
        <span class="text-[10px] md:text-xs font-bold text-purple-700 bg-purple-100 px-2 py-1 rounded-full uppercase tracking-wider">Dihitung Otomatis</span>
    </div>

    <div class="w-full overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead>
                <tr class="bg-white">
                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider whitespace-nowrap">Nama Pegawai</th>
                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider whitespace-nowrap">Status Pembayaran</th>
                    <th class="px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase tracking-wider whitespace-nowrap">Estimasi / Diterima (Rp)</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-100">
                @forelse($employeeSalaries ?? [] as $salary)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap font-bold text-gray-800 text-sm md:text-base">
                            {{ $salary->name }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-xs md:text-sm font-medium">
                            @if(str_contains($salary->status, 'Sudah Dibayar'))
                                <span class="bg-green-100 text-green-800 px-2.5 py-1 rounded-md border border-green-200"><span class="mr-1">✅</span> {{ $salary->status }}</span>
                            @elseif($salary->type == 'percentage')
                                <span class="bg-blue-100 text-blue-800 px-2.5 py-1 rounded-md border border-blue-200"><span class="mr-1">🔄</span> {{ $salary->status }}</span>
                            @else
                                <span class="bg-gray-100 text-gray-700 px-2.5 py-1 rounded-md border border-gray-200"><span class="mr-1">📅</span> {{ $salary->status }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right font-black text-purple-600 text-base md:text-lg">
                            Rp {{ number_format($salary->earned, 0, ',', '.') }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="px-6 py-8 text-center text-sm text-gray-500 italic">
                            Belum ada data pegawai atau pengelolan gaji belum diatur.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        Chart.defaults.font.family = "'Inter', 'Helvetica Neue', 'Helvetica', 'Arial', sans-serif";
        Chart.defaults.color = '#6b7280';

        const rawChartData = @json($chartData ?? null);

        if(rawChartData) {
            // Grafik Harian
            if(rawChartData.harian && document.getElementById('chartHarian')) {
                const ctxHarian = document.getElementById('chartHarian').getContext('2d');
                new Chart(ctxHarian, {
                    type: 'line',
                    data: {
                        labels: rawChartData.harian.labels,
                        datasets: [{
                            label: 'Pendapatan Bersih (Rp)',
                            data: rawChartData.harian.data,
                            borderColor: '#2563eb', // Blue 600
                            backgroundColor: 'rgba(37, 99, 235, 0.1)',
                            borderWidth: 3,
                            pointBackgroundColor: '#2563eb',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                            pointRadius: 4,
                            fill: true,
                            tension: 0.4 // Membuat kurva melengkung (smooth)
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: {
                            y: { beginAtZero: true, ticks: { callback: function(value) { return 'Rp ' + value.toLocaleString('id-ID'); } } },
                            x: { grid: { display: false } }
                        }
                    }
                });
            }

            // Grafik Bulanan
            if(rawChartData.bulanan && document.getElementById('chartBulanan')) {
                const ctxBulanan = document.getElementById('chartBulanan').getContext('2d');
                new Chart(ctxBulanan, {
                    type: 'bar',
                    data: {
                        labels: rawChartData.bulanan.labels,
                        datasets: [{
                            label: 'Pendapatan Bersih (Rp)',
                            data: rawChartData.bulanan.data,
                            backgroundColor: '#10b981', // Emerald 500
                            borderRadius: 6,
                            barPercentage: 0.6 // Membuat bar tidak terlalu gemuk
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: {
                            y: { beginAtZero: true, ticks: { callback: function(value) { return 'Rp ' + value.toLocaleString('id-ID'); } } },
                            x: { grid: { display: false } }
                        }
                    }
                });
            }
        }
    });
</script>
@endif

@endsection
