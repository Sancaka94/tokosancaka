@extends('layouts.admin')

@section('title', 'Laporan Laba Rugi')

@section('content')
<div class="container mx-auto px-4 py-6">

    {{-- HEADER & FILTER --}}
    <div class="bg-white rounded-xl shadow-sm p-6 mb-6 border border-gray-100 flex flex-col md:flex-row justify-between items-center gap-4">
        <div class="text-center md:text-left">
            <h1 class="text-xl font-extrabold text-gray-800 uppercase tracking-widest">PT. SANCAKA (Nama Bisnis Anda)</h1>
            <h2 class="text-lg font-bold text-gray-600">LAPORAN LABA RUGI</h2>
            <p class="text-sm text-gray-500">Periode: {{ \Carbon\Carbon::parse($startDate)->format('d F Y') }} s.d. {{ \Carbon\Carbon::parse($endDate)->format('d F Y') }}</p>
        </div>

        <div class="flex items-center gap-2 print:hidden">
            <form action="{{ route('admin.laporan.labaRugi') }}" method="GET" class="flex items-center gap-2">
                <input type="date" name="date_start" value="{{ $startDate }}" class="border-gray-300 rounded-lg text-sm focus:ring-emerald-500">
                <span class="text-gray-400">-</span>
                <input type="date" name="date_end" value="{{ $endDate }}" class="border-gray-300 rounded-lg text-sm focus:ring-emerald-500">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition shadow-sm">
                    <i class="fas fa-filter me-1"></i> Filter
                </button>
            </form>
            <button onclick="window.print()" class="bg-gray-700 hover:bg-gray-800 text-white px-4 py-2 rounded-lg text-sm font-medium transition shadow-sm">
                <i class="fas fa-print me-1"></i> Cetak
            </button>
        </div>
    </div>

    {{-- GRID LABA RUGI --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-0 border border-gray-300 bg-white shadow-lg rounded-t-xl overflow-hidden">

        {{-- ====================== KOLOM KIRI (PENDAPATAN) ====================== --}}
        <div class="border-r border-gray-300 flex flex-col">
            <div class="bg-emerald-50 p-3 border-b border-emerald-100 text-center font-bold text-lg text-emerald-800 border-t-4 border-emerald-500">
                PENDAPATAN (INCOME)
            </div>

            <div class="p-6 flex-1">
                <table class="w-full text-sm">
                    <tbody class="divide-y divide-gray-100">
                        {{-- LOOPING PENDAPATAN --}}
                        {{-- Sekarang pakai $pendapatanFinal --}}
                        @foreach($pendapatanFinal as $nama => $nilai)
                        <tr class="hover:bg-gray-50 transition group">
                            <td class="py-3 pl-2 text-gray-700 font-medium group-hover:text-emerald-600">
                                <i class="fas fa-arrow-circle-up text-emerald-400 me-2"></i> {{ $nama }}
                            </td>
                            <td class="py-3 pr-2 text-right font-bold text-gray-800">
                                {{ $nilai == 0 ? '-' : 'Rp'.number_format($nilai, 0, ',', '.') }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="bg-emerald-100 p-4 border-t border-emerald-200 flex justify-between items-center mt-auto">
                <span class="font-bold text-lg text-emerald-900">TOTAL PENDAPATAN</span>
                <span class="font-bold text-xl text-emerald-700">Rp{{ number_format($totalPendapatan, 0, ',', '.') }}</span>
            </div>
        </div>

        {{-- ====================== KOLOM KANAN (BEBAN) ====================== --}}
        <div class="flex flex-col">
            <div class="bg-red-50 p-3 border-b border-red-100 text-center font-bold text-lg text-red-800 border-t-4 border-red-500">
                BEBAN & BIAYA (EXPENSES)
            </div>

            <div class="p-6 flex-1">
                <table class="w-full text-sm">
                    <tbody class="divide-y divide-gray-100">
                        {{-- 1. HPP --}}
                         <tr class="bg-red-50/50">
                            <td class="py-3 pl-2 text-red-800 font-bold">
                                <i class="fas fa-box-open text-red-400 me-2"></i> Harga Pokok Penjualan (HPP)
                            </td>
                            <td class="py-3 pr-2 text-right font-bold text-red-700">
                                {{ $hpp == 0 ? '-' : '(Rp'.number_format($hpp, 0, ',', '.').')' }}
                            </td>
                        </tr>

                        {{-- 2. LOOPING BEBAN --}}
                        {{-- Sekarang pakai $bebanFinal --}}
                        @foreach($bebanFinal as $nama => $nilai)
                        <tr class="hover:bg-gray-50 transition group">
                            <td class="py-3 pl-2 text-gray-700 font-medium group-hover:text-red-600">
                                <i class="fas fa-arrow-circle-down text-red-300 me-2"></i> {{ $nama }}
                            </td>
                            <td class="py-3 pr-2 text-right font-bold text-gray-800">
                                {{ $nilai == 0 ? '-' : 'Rp'.number_format($nilai, 0, ',', '.') }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="bg-red-100 p-4 border-t border-red-200 flex justify-between items-center mt-auto">
                <span class="font-bold text-lg text-red-900">TOTAL BEBAN</span>
                <span class="font-bold text-xl text-red-700">Rp{{ number_format($totalBeban + $hpp, 0, ',', '.') }}</span>
            </div>
        </div>
    </div>

    {{-- HASIL AKHIR --}}
    <div class="mt-0 border-x border-b border-gray-300 shadow-lg rounded-b-xl overflow-hidden">
        <div class="p-6 flex flex-col md:flex-row justify-between items-center gap-4 {{ $labaBersih >= 0 ? 'bg-gradient-to-r from-emerald-600 to-green-500' : 'bg-gradient-to-r from-red-600 to-pink-600' }}">
            <div class="text-white">
                <h3 class="text-2xl font-extrabold uppercase tracking-widest">
                    {{ $labaBersih >= 0 ? 'LABA BERSIH (NET PROFIT)' : 'RUGI BERSIH (NET LOSS)' }}
                </h3>
                <p class="text-emerald-100 text-sm opacity-90">Rumus: Total Pendapatan - (HPP + Total Beban)</p>
            </div>
            <div class="bg-white/20 rounded-lg px-6 py-3 backdrop-blur-sm border border-white/30">
                <span class="text-3xl font-black text-white drop-shadow-md">
                    Rp{{ number_format($labaBersih, 0, ',', '.') }}
                </span>
            </div>
        </div>
    </div>

</div>
@endsection
