@extends('layouts.admin')

@section('title', 'Laporan Laba Rugi')

@section('content')
<div class="container mx-auto px-4 py-6">

    {{-- HEADER & FILTER --}}
    <div class="flex flex-col md:flex-row justify-between items-center mb-6 bg-white p-4 rounded-xl shadow-sm border border-gray-100">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Laporan Laba Rugi</h1>
            <p class="text-sm text-gray-500">Periode Tahun: <span class="font-bold text-blue-600">{{ $tahun }}</span></p>
        </div>
        <form action="{{ route('admin.keuangan.laba_rugi') }}" method="GET" class="flex items-center gap-3 mt-4 md:mt-0">
            <label class="text-sm font-semibold text-gray-600">Pilih Tahun:</label>
            <select name="tahun" class="border-gray-300 rounded-lg text-sm shadow-sm focus:ring-blue-500 focus:border-blue-500">
                @for($y = date('Y'); $y >= 2023; $y--)
                    <option value="{{ $y }}" {{ $tahun == $y ? 'selected' : '' }}>{{ $y }}</option>
                @endfor
            </select>
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-700 transition">
                Filter
            </button>
            <button type="button" onclick="window.print()" class="bg-gray-700 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-800 transition">
                <i class="fas fa-print me-1"></i> Cetak
            </button>
        </form>
    </div>

    {{-- TABEL LAPORAN --}}
    <div class="bg-white rounded-xl shadow-lg overflow-x-auto border border-gray-200">
        <table class="w-full text-sm text-right whitespace-nowrap">
            
            {{-- HEADER BULAN --}}
            <thead>
                <tr class="bg-gray-50 border-b border-gray-200 text-gray-600 uppercase text-xs">
                    <th class="px-6 py-4 text-left min-w-[250px] font-extrabold sticky left-0 bg-gray-50 z-10 border-r">Akun / Kategori</th>
                    @foreach($months as $m => $name)
                        <th class="px-4 py-4 min-w-[120px]">{{ substr($name, 0, 3) }}</th>
                    @endforeach
                    <th class="px-4 py-4 bg-gray-100 font-bold border-l">TOTAL THN</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-gray-100 text-gray-700">

                {{-- A. PENDAPATAN --}}
                <tr class="bg-blue-50/50">
                    <td class="px-6 py-3 text-left font-bold text-blue-800 sticky left-0 bg-blue-50/50 border-r">PENDAPATAN USAHA</td>
                    <td colspan="13"></td>
                </tr>
                {{-- Rincian Pendapatan --}}
                @php $grandTotalPendapatan = 0; @endphp
                @foreach(['Ekspedisi', 'PPOB', 'Marketplace', 'Lain-lain'] as $sumber)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-6 py-2 text-left pl-10 sticky left-0 bg-white border-r border-gray-100">Pendapatan {{ $sumber }}</td>
                        @php $sumRow = 0; @endphp
                        @foreach($months as $m => $name)
                            <td class="px-4 py-2">{{ number_format($report[$m]['pendapatan'][$sumber], 0, ',', '.') }}</td>
                            @php $sumRow += $report[$m]['pendapatan'][$sumber]; @endphp
                        @endforeach
                        <td class="px-4 py-2 font-bold bg-gray-50 border-l">{{ number_format($sumRow, 0, ',', '.') }}</td>
                        @php $grandTotalPendapatan += $sumRow; @endphp
                    </tr>
                @endforeach
                {{-- Total Pendapatan --}}
                <tr class="bg-gray-100 font-bold border-t border-gray-300">
                    <td class="px-6 py-3 text-left sticky left-0 bg-gray-100 border-r">Total Pendapatan</td>
                    @foreach($months as $m => $name)
                        <td class="px-4 py-3 text-blue-700">{{ number_format($report[$m]['total_pendapatan'], 0, ',', '.') }}</td>
                    @endforeach
                    <td class="px-4 py-3 text-blue-800 border-l">{{ number_format($grandTotalPendapatan, 0, ',', '.') }}</td>
                </tr>

                {{-- B. BEBAN POKOK (HPP) --}}
                <tr><td colspan="14" class="h-4"></td></tr> {{-- Spacer --}}
                <tr class="bg-red-50/50">
                    <td class="px-6 py-3 text-left font-bold text-red-800 sticky left-0 bg-red-50/50 border-r">BEBAN POKOK PENDAPATAN</td>
                    <td colspan="13"></td>
                </tr>
                @php $grandTotalHPP = 0; @endphp
                @foreach(['Beban Pokok Ekspedisi', 'Beban Pokok PPOB', 'Beban Pokok Marketplace'] as $sumber)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-6 py-2 text-left pl-10 sticky left-0 bg-white border-r border-gray-100">{{ $sumber }}</td>
                        @php $sumRow = 0; @endphp
                        @foreach($months as $m => $name)
                            <td class="px-4 py-2">({{ number_format($report[$m]['hpp'][$sumber], 0, ',', '.') }})</td>
                            @php $sumRow += $report[$m]['hpp'][$sumber]; @endphp
                        @endforeach
                        <td class="px-4 py-2 font-bold bg-gray-50 border-l">({{ number_format($sumRow, 0, ',', '.') }})</td>
                        @php $grandTotalHPP += $sumRow; @endphp
                    </tr>
                @endforeach
                {{-- Total HPP --}}
                <tr class="bg-gray-100 font-bold border-t border-gray-300">
                    <td class="px-6 py-3 text-left sticky left-0 bg-gray-100 border-r">Total Beban Pokok</td>
                    @foreach($months as $m => $name)
                        <td class="px-4 py-3 text-red-700">({{ number_format($report[$m]['total_hpp'], 0, ',', '.') }})</td>
                    @endforeach
                    <td class="px-4 py-3 text-red-800 border-l">({{ number_format($grandTotalHPP, 0, ',', '.') }})</td>
                </tr>

                {{-- C. LABA KOTOR --}}
                <tr class="bg-green-100 font-extrabold border-y-2 border-green-200">
                    <td class="px-6 py-4 text-left sticky left-0 bg-green-100 border-r text-green-900">LABA KOTOR</td>
                    @php $grandTotalLabaKotor = 0; @endphp
                    @foreach($months as $m => $name)
                        <td class="px-4 py-4 text-green-800">{{ number_format($report[$m]['laba_kotor'], 0, ',', '.') }}</td>
                        @php $grandTotalLabaKotor += $report[$m]['laba_kotor']; @endphp
                    @endforeach
                    <td class="px-4 py-4 text-green-900 border-l">{{ number_format($grandTotalLabaKotor, 0, ',', '.') }}</td>
                </tr>

                {{-- D. BEBAN OPERASIONAL --}}
                <tr><td colspan="14" class="h-4"></td></tr>
                <tr class="bg-gray-50">
                    <td class="px-6 py-3 text-left font-bold text-gray-800 sticky left-0 bg-gray-50 border-r">BEBAN OPERASIONAL</td>
                    <td colspan="13"></td>
                </tr>
                @php $grandTotalBebanOps = 0; @endphp
                @if(count($kategoriBeban) > 0)
                    @foreach($kategoriBeban as $kat)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-6 py-2 text-left pl-10 sticky left-0 bg-white border-r border-gray-100">{{ $kat }}</td>
                            @php $sumRow = 0; @endphp
                            @foreach($months as $m => $name)
                                @php 
                                    $val = $report[$m]['beban'][$kat][$m] ?? 0; 
                                    $sumRow += $val;
                                @endphp
                                <td class="px-4 py-2">({{ number_format($val, 0, ',', '.') }})</td>
                            @endforeach
                            <td class="px-4 py-2 font-bold bg-gray-50 border-l">({{ number_format($sumRow, 0, ',', '.') }})</td>
                            @php $grandTotalBebanOps += $sumRow; @endphp
                        </tr>
                    @endforeach
                @else
                    <tr>
                        <td class="px-6 py-2 text-left pl-10 italic text-gray-400 sticky left-0 bg-white border-r">Belum ada data beban manual</td>
                        <td colspan="13"></td>
                    </tr>
                @endif
                {{-- Total Beban Ops --}}
                <tr class="bg-gray-100 font-bold border-t border-gray-300">
                    <td class="px-6 py-3 text-left sticky left-0 bg-gray-100 border-r">Total Beban Operasional</td>
                    @foreach($months as $m => $name)
                        <td class="px-4 py-3 text-gray-700">({{ number_format($report[$m]['total_beban'], 0, ',', '.') }})</td>
                    @endforeach
                    <td class="px-4 py-3 text-gray-800 border-l">({{ number_format($grandTotalBebanOps, 0, ',', '.') }})</td>
                </tr>

                {{-- E. LABA BERSIH --}}
                <tr class="bg-indigo-600 text-white font-extrabold text-base border-t-4 border-indigo-700 shadow-inner">
                    <td class="px-6 py-5 text-left sticky left-0 bg-indigo-600 border-r border-indigo-500">LABA BERSIH (NET INCOME)</td>
                    @php $grandTotalNetIncome = 0; @endphp
                    @foreach($months as $m => $name)
                        <td class="px-4 py-5">{{ number_format($report[$m]['laba_bersih'], 0, ',', '.') }}</td>
                        @php $grandTotalNetIncome += $report[$m]['laba_bersih']; @endphp
                    @endforeach
                    <td class="px-4 py-5 bg-indigo-700 border-l border-indigo-500">{{ number_format($grandTotalNetIncome, 0, ',', '.') }}</td>
                </tr>

            </tbody>
        </table>
    </div>

    {{-- KETERANGAN / NOTES --}}
    <div class="mt-6 bg-yellow-50 p-4 rounded-lg border border-yellow-200 text-yellow-800 text-xs">
        <strong>Catatan:</strong>
        <ul class="list-disc list-inside mt-1 space-y-1">
            <li><strong>Laba Kotor</strong> dihitung dari Total Pendapatan dikurangi Biaya Langsung (HPP/Ongkir Vendor/Harga Dasar PPOB).</li>
            <li><strong>Beban Operasional</strong> diambil dari menu "Input Manual" dengan kategori Pengeluaran (Gaji, Listrik, Sewa, dll). </li>
            <li>Jika ingin menambahkan penyusutan/amortisasi, silakan input manual pengeluaran dengan kategori "Penyusutan".</li>
        </ul>
    </div>

</div>
@endsection