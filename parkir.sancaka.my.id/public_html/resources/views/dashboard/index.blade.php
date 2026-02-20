@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Overview Panel</h1>
    <div class="text-sm text-gray-500">
        {{ \Carbon\Carbon::now()->translatedFormat('l, d F Y') }}
    </div>
</div>

@if(in_array(auth()->user()->role, ['superadmin', 'admin']))
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">

    <div class="card bg-blue-600 text-white shadow-md border-0">
        <div class="card-body flex items-center justify-between">
            <div>
                <h5 class="text-blue-100 text-sm font-semibold uppercase tracking-wider">Motor Masuk (Hari Ini)</h5>
                <p class="text-3xl font-bold mt-2">{{ $data['motor_masuk'] }} <span class="text-sm font-normal">Unit</span></p>
            </div>
            <div class="text-4xl opacity-50">🏍️</div>
        </div>
    </div>

    <div class="card bg-blue-600 text-white shadow-md border-0">
        <div class="card-body flex items-center justify-between">
            <div>
                <h5 class="text-blue-100 text-sm font-semibold uppercase tracking-wider">Mobil Masuk (Hari Ini)</h5>
                <p class="text-3xl font-bold mt-2">{{ $data['mobil_masuk'] }} <span class="text-sm font-normal">Unit</span></p>
            </div>
            <div class="text-4xl opacity-50">🚗</div>
        </div>
    </div>

    <div class="card bg-white shadow-md border border-green-200">
        <div class="card-body flex items-center justify-between">
            <div>
                <h5 class="text-gray-500 text-sm font-semibold uppercase tracking-wider">Pendapatan (Hari Ini)</h5>
                <p class="text-3xl font-bold mt-2 text-green-600">Rp {{ number_format($data['total_pendapatan'], 0, ',', '.') }}</p>
            </div>
            <div class="text-4xl">💰</div>
        </div>
    </div>
</div>
@endif

@if(auth()->user()->role == 'operator')
<div class="card border-l-4 border-blue-600">
    <div class="card-body">
        <h3 class="text-lg font-bold text-gray-800 mb-2">Selamat Bertugas, {{ auth()->user()->name }}!</h3>
        <p class="text-gray-600 mb-4">Silakan menuju halaman operasional untuk mencatat plat nomor kendaraan masuk dan keluar.</p>
        <a href="{{ route('transactions.index') }}" class="btn-primary inline-block">Buka Panel Transaksi Sekarang</a>
    </div>
</div>
@endif

<div class="card">
    <div class="card-header flex justify-between items-center">
        <span>Aktivitas Parkir Terbaru</span>
        <button class="text-sm text-blue-600 hover:underline">Lihat Semua</button>
    </div>
    <div class="card-body p-0 overflow-x-auto">
        <table class="table-custom">
            <thead>
                <tr>
                    <th>Plat Nomor</th>
                    <th>Jenis</th>
                    <th>Waktu Masuk</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="font-bold">AE 1234 XX</td>
                    <td>Motor</td>
                    <td>10:30 WIB</td>
                    <td><span class="bg-yellow-100 text-yellow-800 px-2 py-1 rounded text-xs font-bold">Masuk (Parkir)</span></td>
                </tr>
                <tr>
                    <td class="font-bold">B 9999 AB</td>
                    <td>Mobil</td>
                    <td>09:15 WIB</td>
                    <td><span class="bg-green-100 text-green-800 px-2 py-1 rounded text-xs font-bold">Selesai</span></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
@endsection
