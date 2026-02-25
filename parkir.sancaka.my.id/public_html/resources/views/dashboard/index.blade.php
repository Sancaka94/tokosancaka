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

    <div class="card bg-white shadow-md border border-gray-200">
        <div class="card-body flex items-center justify-between">
            <div>
                <h5 class="text-gray-500 text-xs md:text-sm font-semibold uppercase tracking-wider">Motor Masuk (Hari Ini)</h5>
                <p class="text-2xl md:text-3xl font-bold mt-2 text-gray-800">{{ $data['motor_masuk'] ?? 0 }} <span class="text-sm font-normal text-gray-500">Unit</span></p>
            </div>
            <div class="text-3xl md:text-4xl">🏍️</div>
        </div>
    </div>

    <div class="card bg-white shadow-md border border-gray-200">
        <div class="card-body flex items-center justify-between">
            <div>
                <h5 class="text-gray-500 text-xs md:text-sm font-semibold uppercase tracking-wider">Mobil Masuk (Hari Ini)</h5>
                <p class="text-2xl md:text-3xl font-bold mt-2 text-gray-800">{{ $data['mobil_masuk'] ?? 0 }} <span class="text-sm font-normal text-gray-500">Unit</span></p>
            </div>
            <div class="text-3xl md:text-4xl">🚗</div>
        </div>
    </div>

    <div class="card bg-white shadow-md border border-green-200 relative overflow-hidden">
        <div class="card-body flex items-center justify-between h-full">
            <div>
                <h5 class="text-gray-500 text-xs md:text-sm font-semibold uppercase tracking-wider">Pendapatan (Hari Ini)</h5>
                <p class="text-2xl md:text-3xl font-bold mt-2 text-green-600">Rp {{ number_format($data['total_pendapatan'] ?? 0, 0, ',', '.') }}</p>
            </div>
            <div class="text-3xl md:text-4xl">💵</div>
        </div>
    </div>

    <div class="card bg-white shadow-md border border-blue-200 relative overflow-hidden">
        <div class="card-body">
            <h5 class="text-gray-500 text-xs md:text-sm font-semibold uppercase tracking-wider mb-1">Pendapatan Bulan Ini</h5>
            <p class="text-xl md:text-2xl font-bold text-gray-800 mb-2">Rp {{ number_format($data['perbandingan']['bulan_ini'] ?? 0, 0, ',', '.') }}</p>

            <div class="text-[10px] md:text-xs font-bold flex items-center gap-1 flex-wrap">
                @if(isset($data['perbandingan']['trend']))
                    @if($data['perbandingan']['trend'] == 'naik')
                        <span class="text-green-600 bg-green-100 px-2 py-0.5 rounded-full">&uarr; Naik {{ $data['perbandingan']['persentase'] }}%</span>
                    @elseif($data['perbandingan']['trend'] == 'turun')
                        <span class="text-red-600 bg-red-100 px-2 py-0.5 rounded-full">&darr; Turun {{ $data['perbandingan']['persentase'] }}%</span>
                    @else
                        <span class="text-gray-600 bg-gray-100 px-2 py-0.5 rounded-full">Stabil</span>
                    @endif
                    <span class="text-gray-400 font-medium">dari bulan lalu</span>
                @endif
            </div>
            <div class="absolute right-4 top-1/2 -translate-y-1/2 text-5xl opacity-10 hidden sm:block">📈</div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-4 md:gap-6 mb-6">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white border-b-2 border-gray-100">
            <span class="font-bold text-gray-800 text-sm">Grafik Pendapatan (7 Hari Terakhir)</span>
        </div>
        <div class="card-body">
            <div class="relative w-full h-[250px]">
                <canvas id="chartHarian" height="250"></canvas>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white border-b-2 border-gray-100">
            <span class="font-bold text-gray-800 text-sm">Grafik Pendapatan (6 Bulan Terakhir)</span>
        </div>
        <div class="card-body">
            <div class="relative w-full h-[250px]">
                <canvas id="chartBulanan" height="250"></canvas>
            </div>
        </div>
    </div>
</div>
@endif

@if(auth()->user()->role == 'operator')
<div class="card border-l-4 border-blue-600 mb-6 shadow-sm">
    <div class="card-body">
        <h3 class="text-lg font-bold text-gray-800 mb-2">Selamat Bertugas, {{ auth()->user()->name }}!</h3>
        <p class="text-gray-600 mb-4 text-sm md:text-base">Silakan menuju halaman operasional untuk mencatat plat nomor kendaraan masuk dan keluar area parkir.</p>
        <a href="{{ route('transactions.index') }}" class="btn-primary inline-block shadow-md text-sm md:text-base">Buka Panel Transaksi Sekarang</a>
    </div>
</div>
@endif

<div class="card shadow-sm border-0 mb-8">
    <div class="card-header flex flex-wrap justify-between items-center bg-white border-b-2 border-blue-600 gap-2">
        <span class="font-bold text-gray-800 uppercase tracking-wide text-xs md:text-sm">Aktivitas Parkir Terbaru</span>
        <a href="{{ route('transactions.index') }}" class="text-xs md:text-sm text-blue-600 hover:text-blue-800 hover:underline font-semibold">Lihat Semua Data &rarr;</a>
    </div>
    <div class="card-body p-0 w-full block overflow-x-auto">
        <table class="table-custom min-w-full">
            <thead>
                <tr class="bg-gray-50">
                    <th class="px-4 md:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Plat Nomor</th>
                    <th class="px-4 md:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jenis</th>
                    <th class="px-4 md:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Waktu Masuk</th>
                    <th class="px-4 md:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Operator</th>
                    <th class="px-4 md:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($recent_transactions ?? [] as $trx)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-4 md:px-6 py-3 md:py-4 whitespace-nowrap font-black text-gray-800 text-base md:text-lg tracking-wider">{{ $trx->plate_number }}</td>
                        <td class="px-4 md:px-6 py-3 md:py-4 whitespace-nowrap capitalize text-sm md:text-base text-gray-600">{{ $trx->vehicle_type }}</td>
                        <td class="px-4 md:px-6 py-3 md:py-4 whitespace-nowrap text-sm md:text-base text-gray-600">
                            {{ $trx->entry_time ? \Carbon\Carbon::parse($trx->entry_time)->translatedFormat('H:i') . ' WIB' : '-' }}
                        </td>
                        <td class="px-4 md:px-6 py-3 md:py-4 whitespace-nowrap text-sm md:text-base text-gray-600">{{ $trx->operator->name ?? 'Sistem' }}</td>
                        <td class="px-4 md:px-6 py-3 md:py-4 whitespace-nowrap">
                            @if(strtolower($trx->status) == 'masuk')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-yellow-100 text-yellow-800 border border-yellow-200">
                                    <span class="w-2 h-2 mr-1.5 bg-yellow-500 rounded-full animate-pulse"></span>
                                    Parkir
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-green-100 text-green-800 border border-green-200">
                                    Keluar (Rp {{ number_format(($trx->fee ?? 0) + ($trx->toilet_fee ?? 0), 0, ',', '.') }})
                                </span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-sm text-gray-500 italic">
                            Belum ada kendaraan yang masuk atau keluar.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if(isset($recent_transactions) && method_exists($recent_transactions, 'links'))
    <div class="px-4 py-3 border-t border-gray-200 bg-gray-50 rounded-b-lg">
        {{ $recent_transactions->links() }}
    </div>
    @endif
</div>

@if(in_array(auth()->user()->role, ['superadmin', 'admin']))
<div class="mt-8 mb-4 flex justify-between items-center">
    <h2 class="text-lg md:text-xl font-bold text-gray-800">Ringkasan Buku Kas (Manual)</h2>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 md:gap-6 mb-6">
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 md:p-5 flex items-center justify-between">
        <div>
            <h5 class="text-gray-500 text-xs md:text-sm font-semibold uppercase tracking-wider">Total Pemasukan Kas</h5>
            <p class="text-xl md:text-2xl font-bold text-green-600 mt-2">Rp {{ number_format($totalPemasukanKas ?? 0, 0, ',', '.') }}</p>
        </div>
        <div class="text-3xl opacity-50 hidden sm:block">📥</div>
    </div>
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 md:p-5 flex items-center justify-between">
        <div>
            <h5 class="text-gray-500 text-xs md:text-sm font-semibold uppercase tracking-wider">Total Pengeluaran Kas</h5>
            <p class="text-xl md:text-2xl font-bold text-red-600 mt-2">Rp {{ number_format($totalPengeluaranKas ?? 0, 0, ',', '.') }}</p>
        </div>
        <div class="text-3xl opacity-50 hidden sm:block">📤</div>
    </div>
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 md:p-5 flex items-center justify-between sm:col-span-2 md:col-span-1">
        <div>
            <h5 class="text-gray-500 text-xs md:text-sm font-semibold uppercase tracking-wider">Saldo Akhir</h5>
            <p class="text-xl md:text-2xl font-bold text-blue-600 mt-2">Rp {{ number_format($saldoKas ?? 0, 0, ',', '.') }}</p>
        </div>
        <div class="text-3xl opacity-50 hidden sm:block">💰</div>
    </div>
</div>

<div class="card shadow-sm border-0 mb-8">
    <div class="card-header flex flex-wrap justify-between items-center bg-white border-b-2 border-green-500 gap-2">
        <span class="font-bold text-gray-800 uppercase tracking-wide text-xs md:text-sm">Catatan Kas Terbaru</span>
        <a href="{{ route('financial.index') }}" class="text-xs md:text-sm text-green-600 hover:text-green-800 hover:underline font-semibold">Kelola Buku Kas &rarr;</a>
    </div>
    <div class="card-body p-0 w-full block overflow-x-auto">
        <table class="table-custom min-w-full">
            <thead>
                <tr class="bg-gray-50">
                    <th class="px-4 md:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                    <th class="px-4 md:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kategori</th>
                    <th class="px-4 md:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Keterangan</th>
                    <th class="px-4 md:px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Nominal</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($recent_financials ?? [] as $kas)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-4 md:px-6 py-3 md:py-4 whitespace-nowrap text-xs md:text-sm text-gray-800 font-medium">
                            {{ \Carbon\Carbon::parse($kas->tanggal)->format('d/m/Y') }}
                        </td>
                        <td class="px-4 md:px-6 py-3 md:py-4 whitespace-nowrap text-xs md:text-sm text-gray-600">{{ $kas->kategori }}</td>
                        <td class="px-4 md:px-6 py-3 md:py-4 text-xs md:text-sm text-gray-500 truncate max-w-[150px] md:max-w-xs">{{ $kas->keterangan ?? '-' }}</td>
                        <td class="px-4 md:px-6 py-3 md:py-4 whitespace-nowrap text-right font-bold text-xs md:text-sm">
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
                            Belum ada catatan kas masuk atau keluar.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if(isset($recent_financials) && method_exists($recent_financials, 'links'))
    <div class="px-4 py-3 border-t border-gray-200 bg-gray-50 rounded-b-lg">
        {{ $recent_financials->links() }}
    </div>
    @endif
</div>
@endif

@if(in_array(auth()->user()->role, ['superadmin', 'admin']))
<div class="card shadow-sm border-0 mb-8">
    <div class="card-header flex flex-wrap justify-between items-center bg-white border-b-2 border-purple-500 gap-2">
        <span class="font-bold text-gray-800 uppercase tracking-wide text-xs md:text-sm">Estimasi Gaji Pegawai (Hari Ini)</span>
        <span class="text-xs font-semibold text-purple-600 bg-purple-50 px-2 py-1 rounded">Dihitung Otomatis</span>
    </div>

    <div class="card-body p-0 w-full block overflow-x-auto">
        <table class="table-custom min-w-full">
            <thead>
                <tr class="bg-gray-50">
                    <th class="px-4 md:px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Nama Pegawai</th>
                    <th class="px-4 md:px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Sistem Gaji</th>
                    <th class="px-4 md:px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Estimasi Didapat (Rp)</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($employeeSalaries ?? [] as $salary)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-4 md:px-6 py-3 md:py-4 whitespace-nowrap font-bold text-gray-800 text-sm md:text-base">
                            {{ $salary->name }}
                        </td>
                        <td class="px-4 md:px-6 py-3 md:py-4 whitespace-nowrap text-sm text-gray-600 font-medium">
                            @if($salary->type == 'percentage')
                                <span class="bg-blue-100 text-blue-800 px-2 py-0.5 rounded text-xs">Persentase ({{ (float)$salary->amount }}%)</span>
                            @else
                                <span class="bg-green-100 text-green-800 px-2 py-0.5 rounded text-xs">Nominal Harian (Rp {{ number_format($salary->amount, 0, ',', '.') }})</span>
                            @endif
                        </td>
                        <td class="px-4 md:px-6 py-3 md:py-4 whitespace-nowrap text-right font-black text-purple-600 text-base md:text-lg">
                            Rp {{ number_format($salary->earned, 0, ',', '.') }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="px-4 md:px-6 py-8 text-center text-sm text-gray-500 italic">
                            Belum ada data pegawai atau data gaji belum diatur.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endif

@if(in_array(auth()->user()->role, ['superadmin', 'admin']) && isset($chartData))
<script>
    document.addEventListener("DOMContentLoaded", function() {
        Chart.defaults.font.family = "'Inter', 'Helvetica Neue', 'Helvetica', 'Arial', sans-serif";
        Chart.defaults.color = '#6b7280';

        const rawChartData = @json($chartData);

        // Grafik Harian
        if(rawChartData.harian && document.getElementById('chartHarian')) {
            const ctxHarian = document.getElementById('chartHarian').getContext('2d');
            new Chart(ctxHarian, {
                type: 'line',
                data: {
                    labels: rawChartData.harian.labels,
                    datasets: [{
                        label: 'Pendapatan (Rp)',
                        data: rawChartData.harian.data,
                        borderColor: '#2563eb',
                        backgroundColor: 'rgba(37, 99, 235, 0.1)',
                        borderWidth: 2,
                        pointBackgroundColor: '#2563eb',
                        fill: true,
                        tension: 0.3
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, ticks: { callback: function(value) { return 'Rp ' + value.toLocaleString('id-ID'); } } }
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
                        label: 'Pendapatan (Rp)',
                        data: rawChartData.bulanan.data,
                        backgroundColor: '#2563eb',
                        borderRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, ticks: { callback: function(value) { return 'Rp ' + value.toLocaleString('id-ID'); } } }
                    }
                }
            });
        }
    });
</script>
@endif

@endsection
