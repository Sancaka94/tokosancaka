@extends('layouts.app')
@section('title', 'Data Pegawai & Rekap Pendapatan')

@section('content')
<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Rekap Pendapatan & Data Pegawai</h1>
        <p class="text-sm text-gray-500 mt-1">Laporan historis gaji yang telah dibayarkan melalui Kas.</p>
    </div>

    @if(in_array(auth()->user()->role, ['superadmin', 'admin']))
        <a href="{{ route('employees.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-lg font-bold shadow-md transition-colors flex items-center gap-2">
            <span>+</span> Tambah Pegawai
        </a>
    @endif
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-10">
    <div class="overflow-x-auto p-0">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Nama / Role</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Sistem Gaji (Target)</th>
                    <th class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase tracking-wider bg-blue-50/50">Pendapatan Bulan Ini</th>
                    <th class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase tracking-wider bg-green-50/50">Total Keseluruhan</th>

                    @if(in_array(auth()->user()->role, ['superadmin', 'admin']))
                        <th class="px-6 py-4 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Aksi</th>
                    @endif
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-100">
                @forelse($employees as $emp)
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="font-bold text-gray-800 text-base">{{ $emp->name }}</div>
                        <div class="text-xs text-gray-500 uppercase tracking-widest mt-1">{{ $emp->role }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @if($emp->salary_type == 'percentage')
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-bold bg-blue-100 text-blue-800">
                                Bagi Hasil ({{ (float)$emp->salary_amount }}%)
                            </span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-bold bg-gray-100 text-gray-800">
                                Flat (Rp {{ number_format($emp->salary_amount, 0, ',', '.') }})
                            </span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right font-black text-blue-600 text-lg bg-blue-50/20">
                        Rp {{ number_format($emp->pendapatan_bulan_ini, 0, ',', '.') }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right font-black text-green-600 text-lg bg-green-50/20">
                        Rp {{ number_format($emp->total_pendapatan, 0, ',', '.') }}
                    </td>

                    @if(in_array(auth()->user()->role, ['superadmin', 'admin']))
                    <td class="px-6 py-4 whitespace-nowrap text-center">
                        <div class="flex items-center justify-center gap-2">
                            <a href="{{ route('employees.edit', $emp->id) }}" class="text-blue-500 hover:text-blue-700 bg-blue-50 hover:bg-blue-100 px-3 py-1.5 rounded text-sm font-semibold transition-colors">Edit</a>

                            <form action="{{ route('employees.destroy', $emp->id) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus pegawai ini?');" class="inline-block">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-500 hover:text-red-700 bg-red-50 hover:bg-red-100 px-3 py-1.5 rounded text-sm font-semibold transition-colors">Hapus</button>
                            </form>
                        </div>
                    </td>
                    @endif
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-6 py-8 text-center text-gray-500 italic">Belum ada data pegawai.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if(method_exists($employees, 'links') && $employees->hasPages())
        <div class="px-6 py-4 bg-gray-50 border-t border-gray-100">
            {{ $employees->appends(request()->except('emp_page'))->links() }}
        </div>
    @endif
</div>


<div class="mb-4">
    <h2 class="text-xl font-bold text-gray-800">Riwayat Pembayaran Gaji Harian</h2>
    <p class="text-sm text-gray-500 mt-1">Detail pendapatan yang dicatat pada buku kas manual.</p>
</div>

<div class="bg-white rounded-xl mb-6 border-t-4 border-purple-500 shadow-sm p-4 md:p-5">
    <form action="{{ route('employees.index') }}" method="GET" class="flex flex-col sm:flex-row gap-4 items-end">
        <div class="w-full sm:w-auto flex-1 md:flex-none">
            <label class="block text-sm font-bold text-gray-700 mb-1">Pilih Tanggal</label>
            <input type="date" name="tanggal" value="{{ request('tanggal') }}" class="w-full border border-gray-300 rounded-md py-2 px-3 focus:ring-purple-500 focus:border-purple-500">
        </div>

        @if(in_array(auth()->user()->role, ['superadmin', 'admin']))
        <div class="w-full sm:w-auto flex-1 md:flex-none">
            <label class="block text-sm font-bold text-gray-700 mb-1">Cari Nama Pegawai</label>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Contoh: Dodik" class="w-full border border-gray-300 rounded-md py-2 px-3 focus:ring-purple-500 focus:border-purple-500">
        </div>
        @endif

        <div class="flex gap-2 w-full sm:w-auto">
            <button type="submit" class="w-full sm:w-auto bg-gray-800 hover:bg-gray-900 text-white px-6 py-2 rounded-md font-bold transition-colors shadow-sm">
                Cari Data
            </button>
            @if(request()->has('tanggal') || request()->has('search'))
                <a href="{{ route('employees.index') }}" class="w-full sm:w-auto bg-red-50 hover:bg-red-100 text-red-600 px-4 py-2 rounded-md font-bold transition-colors border border-red-200 text-center text-sm flex items-center justify-center">
                    Reset
                </a>
            @endif
        </div>
    </form>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-8">
    <div class="overflow-x-auto p-0">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-4 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">No</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Hari & Tanggal</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Keterangan (Nama)</th>
                    <th class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Nominal Pendapatan</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-100">
                @forelse($salaryHistory as $index => $history)
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-6 py-4 text-center text-sm text-gray-700">
                        {{ $salaryHistory->firstItem() + $index }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-800">
                        {{ \Carbon\Carbon::parse($history->tanggal)->translatedFormat('l, d F Y') }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                        {{ $history->keterangan }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right font-black text-purple-600 text-lg">
                        Rp {{ number_format($history->nominal, 0, ',', '.') }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="px-6 py-8 text-center text-gray-500 italic">Data gaji tidak ditemukan untuk filter ini.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if(method_exists($salaryHistory, 'links') && $salaryHistory->hasPages())
        <div class="px-6 py-4 bg-gray-50 border-t border-gray-100">
            {{ $salaryHistory->appends(request()->except('hist_page'))->links() }}
        </div>
    @endif
</div>

@endsection
