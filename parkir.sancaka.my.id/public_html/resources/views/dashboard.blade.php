@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">
        @if(auth()->user()->isSuperadmin())
            Overview Panel Super Admin
        @elseif(auth()->user()->isAdmin())
            Overview Panel Admin Tenant
        @else
            Overview Panel Operator
        @endif
    </h1>
    <div class="text-sm text-gray-500 font-medium hidden md:block">
        {{ \Carbon\Carbon::now()->translatedFormat('l, d F Y') }}
    </div>
</div>

@if(auth()->user()->isSuperadmin() || auth()->user()->isAdmin())
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
    <div class="card bg-blue-600 text-white shadow-md border-0">
        <div class="card-body flex items-center justify-between">
            <div>
                <h5 class="text-blue-100 text-sm font-semibold uppercase tracking-wider">Motor Masuk (Hari Ini)</h5>
                <p class="text-3xl font-bold mt-2">{{ $data['motor_masuk'] ?? 0 }} <span class="text-sm font-normal">Unit</span></p>
            </div>
            <div class="text-4xl opacity-50">🏍️</div>
        </div>
    </div>

    <div class="card bg-blue-600 text-white shadow-md border-0">
        <div class="card-body flex items-center justify-between">
            <div>
                <h5 class="text-blue-100 text-sm font-semibold uppercase tracking-wider">Mobil Masuk (Hari Ini)</h5>
                <p class="text-3xl font-bold mt-2">{{ $data['mobil_masuk'] ?? 0 }} <span class="text-sm font-normal">Unit</span></p>
            </div>
            <div class="text-4xl opacity-50">🚗</div>
        </div>
    </div>

    <div class="card bg-white shadow-md border border-green-200">
        <div class="card-body flex items-center justify-between">
            <div>
                <h5 class="text-gray-500 text-sm font-semibold uppercase tracking-wider">Pendapatan (Hari Ini)</h5>
                <p class="text-3xl font-bold mt-2 text-green-600">Rp {{ isset($data['total_pendapatan']) ? number_format($data['total_pendapatan'], 0, ',', '.') : 0 }}</p>
            </div>
            <div class="text-4xl">💰</div>
        </div>
    </div>
</div>
@endif

@if(auth()->user()->isOperator())
<div class="card border-l-4 border-blue-600 mb-6 shadow-sm">
    <div class="card-body">
        <h3 class="text-lg font-bold text-gray-800 mb-2">Selamat Bertugas, {{ auth()->user()->name }}!</h3>
        <p class="text-gray-600 mb-4">Silakan menuju halaman operasional untuk mencatat plat nomor kendaraan masuk dan keluar area parkir.</p>
        <a href="{{ route('transactions.index') }}" class="btn-primary inline-block shadow-md">Buka Panel Transaksi Sekarang</a>
    </div>
</div>
@endif

<div class="card shadow-sm border-0">
    <div class="card-header flex justify-between items-center bg-white border-b-2 border-blue-600">
        <span class="font-bold text-gray-800 uppercase tracking-wide text-sm">Aktivitas Parkir Terbaru</span>
        <a href="{{ route('transactions.index') }}" class="text-sm text-blue-600 hover:text-blue-800 hover:underline font-semibold">Lihat Semua Data &rarr;</a>
    </div>
    <div class="card-body p-0 overflow-x-auto">
        <table class="table-custom min-w-full">
            <thead>
                <tr class="bg-gray-50">
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Plat Nomor</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jenis</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Waktu Masuk</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Operator</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($recent_transactions ?? [] as $trx)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap font-black text-gray-800 text-lg tracking-wider">{{ $trx->plate_number }}</td>
                        <td class="px-6 py-4 whitespace-nowrap capitalize text-gray-600">{{ $trx->vehicle_type }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-gray-600">{{ $trx->entry_time->translatedFormat('H:i WIB') }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-gray-600">{{ $trx->operator->name ?? 'Sistem' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($trx->status == 'masuk')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-yellow-100 text-yellow-800 border border-yellow-200">
                                    <span class="w-2 h-2 mr-1.5 bg-yellow-500 rounded-full animate-pulse"></span>
                                    Parkir
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-green-100 text-green-800 border border-green-200">
                                    Keluar (Rp {{ number_format($trx->fee, 0, ',', '.') }})
                                </span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-gray-500 italic">
                            Belum ada kendaraan yang masuk atau keluar hari ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
