@extends('layouts.admin')

@section('title', 'Laporan Laba Rugi Tahunan')

@section('content')
<div class="container mx-auto px-4 py-6">

    {{-- ================================================================= --}}
    {{-- CSS KHUSUS UNTUK TABEL LEBAR & PRINT --}}
    {{-- ================================================================= --}}
    @push('styles')
    <style>
        /* Agar kolom pertama (Akun) tetap diam saat scroll ke samping */
        .sticky-col {
            position: -webkit-sticky;
            position: sticky;
            left: 0;
            z-index: 10;
            /* Background color moved inline/class specific to avoid conflict */
        }

        /* Agar kolom TERAKHIR (TOTAL) tetap diam saat scroll ke samping */
        .sticky-col-right {
            position: -webkit-sticky;
            position: sticky;
            right: 0;
            z-index: 10;
            border-left: 1px solid #e5e7eb; /* Border pemisah di kiri */
            box-shadow: -2px 0 5px rgba(0,0,0,0.05); /* Bayangan halus agar terlihat terpisah */
        }

        /* Style khusus saat mode Print / PDF */
        @media print {
            .no-print { display: none !important; }
            body { background: white; }
            .shadow-lg, .shadow-sm { box-shadow: none !important; }
            .sticky-col, .sticky-col-right { position: static !important; border: none !important; box-shadow: none !important; }
            table { width: 100%; border-collapse: collapse; }
            th, td { border: 1px solid #000 !important; font-size: 10px !important; padding: 4px !important; }
        }
    </style>
    @endpush

    {{-- ================================================================= --}}
    {{-- HEADER: JUDUL, FILTER TAHUN, DAN TOMBOL EXPORT                    --}}
    {{-- ================================================================= --}}
    <div class="bg-white rounded-xl shadow-sm p-5 mb-6 border border-gray-100 no-print">
        <div class="flex flex-col md:flex-row justify-between items-center gap-4">
            
            {{-- Bagian Kiri: Judul Halaman --}}
            <div class="w-full md:w-auto text-center md:text-left">
                <h1 class="text-2xl font-bold text-gray-800">Laporan Laba Rugi</h1>
                <p class="text-sm text-gray-500">
                    Periode Laporan: <span class="font-bold text-blue-600">Januari - Desember {{ $tahun }}</span>
                </p>
            </div>

            {{-- Bagian Kanan: Form Filter & Export --}}
            <div class="flex flex-col sm:flex-row items-center gap-3 w-full md:w-auto">
                
                {{-- 1. Form Filter Tahun --}}
                <form action="{{ route('admin.keuangan.laba_rugi') }}" method="GET" class="flex items-center gap-2 w-full sm:w-auto">
                    {{-- Tambahkan h-10 agar tinggi fix --}}
                    <select name="tahun" class="h-10 border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500 px-3 w-full sm:w-auto">
                        @for($y = date('Y'); $y >= 2023; $y--)
                            <option value="{{ $y }}" {{ $tahun == $y ? 'selected' : '' }}>Tahun {{ $y }}</option>
                        @endfor
                    </select>

                    {{-- Tambahkan h-10 agar tinggi fix sama dengan select --}}
                    <button type="submit" class="h-10 bg-blue-600 hover:bg-blue-700 text-white px-4 rounded-lg text-sm font-medium transition shadow-sm whitespace-nowrap flex items-center justify-center" title="Tampilkan di Web">
                        <i class="fas fa-search mr-2"></i> Lihat
                    </button>
                </form>

                {{-- Separator --}}
                <div class="h-8 w-px bg-gray-300 hidden sm:block mx-2"></div>

                {{-- 2. Form Export PDF --}}
                <form action="{{ route('admin.keuangan.laba_rugi.export_pdf') }}" method="GET" target="_blank" class="flex items-center gap-2 w-full sm:w-auto">
                    <input type="hidden" name="tahun" value="{{ $tahun }}">

                    {{-- Tambahkan h-10 --}}
                    <select name="bulan" class="h-10 border-gray-300 rounded-lg text-sm focus:ring-red-500 focus:border-red-500 px-3 w-full sm:w-48">
                        <option value="all" selected>Semua (Detail Landscape)</option>
                        <option disabled>--- Pilih Bulan (Portrait) ---</option>
                        @foreach($months as $k => $v)
                            <option value="{{ $k }}">{{ $v }} (Ringkas)</option>
                        @endforeach
                    </select>

                    {{-- Tambahkan h-10 --}}
                    <button type="submit" class="h-10 bg-red-600 hover:bg-red-700 text-white px-4 rounded-lg text-sm font-medium transition shadow-sm flex items-center gap-2 whitespace-nowrap">
                        <i class="fas fa-file-pdf"></i> PDF
                    </button>
                </form>
                
                {{-- 3. Tombol Export Excel --}}
                {{-- Tambahkan h-10 dan flex items-center agar ikon di tengah --}}
                <a href="{{ route('admin.keuangan.laba_rugi.export_excel', ['tahun' => $tahun]) }}" target="_blank" class="h-10 bg-green-600 hover:bg-green-700 text-white px-4 rounded-lg text-sm font-medium transition shadow-sm flex items-center justify-center" title="Download Excel Full">
                    <i class="fas fa-file-excel text-lg"></i>
                </a>

            </div>
        </div>
    </div>

    {{-- ================================================================= --}}
    {{-- TABEL LAPORAN (SCROLLABLE HORIZONTAL) --}}
    {{-- ================================================================= --}}
    <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-200">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-right whitespace-nowrap border-collapse">
                
                {{-- HEADER BULAN --}}
                <thead class="bg-gray-100 text-gray-600 uppercase text-xs font-bold">
                    <tr>
                        <th class="px-6 py-4 text-left min-w-[250px] sticky-col bg-gray-100 border-r border-gray-200">AKUN / KATEGORI</th>
                        @foreach($months as $m => $name)
                            <th class="px-4 py-4 min-w-[100px]">{{ substr($name, 0, 3) }}</th>
                        @endforeach
                        {{-- Sticky Right diterapkan di sini --}}
                        <th class="px-4 py-4 min-w-[120px] sticky-col-right bg-gray-200 text-gray-800">TOTAL</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-100 text-gray-700">

                    {{-- ==================================================== --}}
                    {{-- A. PENDAPATAN USAHA --}}
                    {{-- ==================================================== --}}
                    <tr class="bg-blue-50/50">
                        <td class="px-6 py-3 text-left font-bold text-blue-800 sticky-col bg-blue-50 border-r">PENDAPATAN USAHA</td>
                        <td colspan="12"></td>
                        {{-- Kolom sticky kosong untuk baris judul --}}
                        <td class="sticky-col-right bg-blue-50/50"></td>
                    </tr>

                    @php $grandTotalPendapatan = 0; @endphp
                    @foreach(['Ekspedisi', 'PPOB', 'Marketplace', 'Lain-lain'] as $sumber)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-6 py-2 text-left pl-8 sticky-col bg-white border-r">Pendapatan {{ $sumber }}</td>
                            @php $sumRow = 0; @endphp
                            @foreach($months as $m => $name)
                                <td class="px-4 py-2 text-gray-600">
                                    {{ number_format($report[$m]['pendapatan'][$sumber], 0, ',', '.') }}
                                </td>
                                @php $sumRow += $report[$m]['pendapatan'][$sumber]; @endphp
                            @endforeach
                            {{-- Sticky Right diterapkan di sini --}}
                            <td class="px-4 py-2 font-bold sticky-col-right bg-gray-50 text-gray-800">
                                {{ number_format($sumRow, 0, ',', '.') }}
                            </td>
                            @php $grandTotalPendapatan += $sumRow; @endphp
                        </tr>
                    @endforeach

                    {{-- TOTAL PENDAPATAN --}}
                    <tr class="bg-gray-100 font-bold text-blue-900 border-t border-b border-gray-300">
                        <td class="px-6 py-3 text-left sticky-col bg-gray-100 border-r">TOTAL PENDAPATAN</td>
                        @foreach($months as $m => $name)
                            <td class="px-4 py-3">{{ number_format($report[$m]['total_pendapatan'], 0, ',', '.') }}</td>
                        @endforeach
                        {{-- Sticky Right diterapkan di sini --}}
                        <td class="px-4 py-3 sticky-col-right bg-blue-100 text-blue-900">
                            {{ number_format($grandTotalPendapatan, 0, ',', '.') }}
                        </td>
                    </tr>

                    {{-- ==================================================== --}}
                    {{-- B. BEBAN POKOK (HPP) --}}
                    {{-- ==================================================== --}}
                    <tr class="bg-red-50/50">
                        <td class="px-6 py-3 text-left font-bold text-red-800 sticky-col bg-red-50 border-r">BEBAN POKOK PENDAPATAN</td>
                        <td colspan="12"></td>
                         <td class="sticky-col-right bg-red-50/50"></td>
                    </tr>

                    @php $grandTotalHPP = 0; @endphp
                    @foreach(['Beban Pokok Ekspedisi', 'Beban Pokok PPOB', 'Beban Pokok Marketplace'] as $sumber)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-6 py-2 text-left pl-8 sticky-col bg-white border-r">{{ $sumber }}</td>
                            @php $sumRow = 0; @endphp
                            @foreach($months as $m => $name)
                                <td class="px-4 py-2 text-gray-500">
                                    ({{ number_format($report[$m]['hpp'][$sumber], 0, ',', '.') }})
                                </td>
                                @php $sumRow += $report[$m]['hpp'][$sumber]; @endphp
                            @endforeach
                            {{-- Sticky Right diterapkan di sini --}}
                            <td class="px-4 py-2 font-bold sticky-col-right bg-gray-50 text-gray-800">
                                ({{ number_format($sumRow, 0, ',', '.') }})
                            </td>
                            @php $grandTotalHPP += $sumRow; @endphp
                        </tr>
                    @endforeach

                    {{-- TOTAL HPP --}}
                    <tr class="bg-gray-100 font-bold text-red-900 border-t border-b border-gray-300">
                        <td class="px-6 py-3 text-left sticky-col bg-gray-100 border-r">TOTAL BEBAN POKOK</td>
                        @foreach($months as $m => $name)
                            <td class="px-4 py-3">({{ number_format($report[$m]['total_hpp'], 0, ',', '.') }})</td>
                        @endforeach
                        {{-- Sticky Right diterapkan di sini --}}
                        <td class="px-4 py-3 sticky-col-right bg-red-100 text-red-900">
                            ({{ number_format($grandTotalHPP, 0, ',', '.') }})
                        </td>
                    </tr>

                    {{-- ==================================================== --}}
                    {{-- C. LABA KOTOR --}}
                    {{-- ==================================================== --}}
                    <tr class="bg-green-100 font-extrabold text-green-900 border-b-2 border-green-200">
                        <td class="px-6 py-4 text-left sticky-col bg-green-100 border-r border-green-200">LABA KOTOR</td>
                        @php $grandTotalLabaKotor = 0; @endphp
                        @foreach($months as $m => $name)
                            <td class="px-4 py-4">{{ number_format($report[$m]['laba_kotor'], 0, ',', '.') }}</td>
                            @php $grandTotalLabaKotor += $report[$m]['laba_kotor']; @endphp
                        @endforeach
                        {{-- Sticky Right diterapkan di sini --}}
                        <td class="px-4 py-4 sticky-col-right bg-green-200 text-green-900">
                            {{ number_format($grandTotalLabaKotor, 0, ',', '.') }}
                        </td>
                    </tr>

                    {{-- ==================================================== --}}
                    {{-- D. BEBAN OPERASIONAL --}}
                    {{-- ==================================================== --}}
                    <tr class="bg-gray-50">
                        <td class="px-6 py-3 text-left font-bold text-gray-800 sticky-col bg-gray-50 border-r">BEBAN OPERASIONAL</td>
                        <td colspan="12"></td>
                         <td class="sticky-col-right bg-gray-50"></td>
                    </tr>

                    @php $grandTotalBebanOps = 0; @endphp
                    @if(count($listKategoriBeban) > 0)
                        @foreach($listKategoriBeban as $kat)
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-2 text-left pl-8 sticky-col bg-white border-r">{{ $kat }}</td>
                                @php $sumRow = 0; @endphp
                                @foreach($months as $m => $name)
                                    @php 
                                        $val = $report[$m]['beban'][$kat] ?? 0; 
                                        $sumRow += $val;
                                    @endphp
                                    <td class="px-4 py-2 text-gray-500">
                                        ({{ number_format($val, 0, ',', '.') }})
                                    </td>
                                @endforeach
                                {{-- Sticky Right diterapkan di sini --}}
                                <td class="px-4 py-2 font-bold sticky-col-right bg-gray-50 text-gray-800">
                                    ({{ number_format($sumRow, 0, ',', '.') }})
                                </td>
                                @php $grandTotalBebanOps += $sumRow; @endphp
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td class="px-6 py-2 text-left pl-8 italic text-gray-400 sticky-col bg-white border-r">Tidak ada data beban manual</td>
                            <td colspan="12"></td>
                            <td class="sticky-col-right bg-white"></td>
                        </tr>
                    @endif

                    {{-- TOTAL BEBAN OPERASIONAL --}}
                    <tr class="bg-gray-100 font-bold text-gray-900 border-t border-b border-gray-300">
                        <td class="px-6 py-3 text-left sticky-col bg-gray-100 border-r">TOTAL BEBAN OPERASIONAL</td>
                        @foreach($months as $m => $name)
                            <td class="px-4 py-3">({{ number_format($report[$m]['total_beban'], 0, ',', '.') }})</td>
                        @endforeach
                        {{-- Sticky Right diterapkan di sini --}}
                        <td class="px-4 py-3 sticky-col-right bg-gray-200 text-gray-900">
                            ({{ number_format($grandTotalBebanOps, 0, ',', '.') }})
                        </td>
                    </tr>

                    {{-- ==================================================== --}}
                    {{-- E. LABA BERSIH (NET INCOME) --}}
                    {{-- ==================================================== --}}
                    <tr class="bg-indigo-700 text-white font-extrabold text-base border-t-4 border-indigo-800 shadow-inner">
                        <td class="px-6 py-5 text-left sticky-col bg-indigo-700 border-r border-indigo-600">
                            LABA BERSIH (NET INCOME)
                        </td>
                        @php $grandTotalNetIncome = 0; @endphp
                        @foreach($months as $m => $name)
                            <td class="px-4 py-5">
                                {{ number_format($report[$m]['laba_bersih'], 0, ',', '.') }}
                            </td>
                            @php $grandTotalNetIncome += $report[$m]['laba_bersih']; @endphp
                        @endforeach
                        {{-- Sticky Right diterapkan di sini --}}
                        <td class="px-4 py-5 sticky-col-right bg-indigo-900 border-l border-indigo-600 text-lg">
                            {{ number_format($grandTotalNetIncome, 0, ',', '.') }}
                        </td>
                    </tr>

                </tbody>
            </table>
        </div>
    </div>

    {{-- FOOTER NOTE --}}
    <div class="mt-6 bg-yellow-50 p-4 rounded-lg border border-yellow-200 text-yellow-800 text-xs no-print">
        <h4 class="font-bold mb-1"><i class="fas fa-info-circle"></i> Catatan Laporan:</h4>
        <ul class="list-disc list-inside space-y-1">
            <li><strong>Laba Kotor</strong>: Pendapatan dikurangi HPP (Modal Dasar / Ongkir Vendor).</li>
            <li><strong>Beban Operasional</strong>: Diambil dari menu "Input Manual" kategori Pengeluaran.</li>
            <li>Angka dalam kurung `( ... )` menandakan pengurangan/minus.</li>
        </ul>
    </div>

</div>
@endsection