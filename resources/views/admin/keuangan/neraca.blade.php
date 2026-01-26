@extends('layouts.admin')

@section('title', 'Neraca Keuangan')

@section('content')
<div class="container mx-auto px-4 py-6">

    {{-- HEADER & FILTER --}}
    <div class="bg-white rounded-xl shadow-sm p-6 mb-6 border border-gray-100 flex flex-col md:flex-row justify-between items-center gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-gray-800">Neraca Keuangan</h1>
            <p class="text-sm text-gray-500">Periode: {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }} s.d. {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}</p>
        </div>

        {{-- Filter Tanggal Sederhana --}}
        <form action="{{ route('admin.keuangan.neraca') }}" method="GET" class="flex items-center gap-2">
            <input type="date" name="date_start" value="{{ $startDate }}" class="border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500">
            <span class="text-gray-400">-</span>
            <input type="date" name="date_end" value="{{ $endDate }}" class="border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition">
                <i class="fas fa-filter me-1"></i> Filter
            </button>
        </form>
    </div>

    {{-- KONTEN NERACA (GRID 2 KOLOM) --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

        {{-- KOLOM KIRI: ASET (PEMASUKAN / KAS MASUK) --}}
        <div class="bg-white rounded-xl shadow-lg border-t-4 border-green-500 overflow-hidden">
            <div class="bg-green-50 px-6 py-4 border-b border-green-100 flex justify-between items-center">
                <h3 class="font-bold text-green-800 uppercase tracking-wide">
                    <i class="fas fa-wallet me-2"></i> Aset Lancar (Kas & Bank)
                </h3>
            </div>
            
            <div class="p-6">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-xs text-gray-400 uppercase border-b border-gray-100">
                            <th class="text-left py-2">Sumber Dana (Akun)</th>
                            <th class="text-right py-2">Nilai (IDR)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 text-gray-700">
                        {{-- Loop Data Dinamis --}}
                        @forelse($neraca['aset'] as $kategori => $nilai)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="py-3 font-medium">
                                {{-- Icon Dinamis --}}
                                @if($kategori == 'Ekspedisi') <i class="fas fa-truck text-yellow-500 w-5"></i>
                                @elseif($kategori == 'PPOB') <i class="fas fa-mobile-alt text-purple-500 w-5"></i>
                                @elseif($kategori == 'Marketplace') <i class="fas fa-store text-orange-500 w-5"></i>
                                @else <i class="fas fa-coins text-gray-400 w-5"></i>
                                @endif
                                Kas dari {{ $kategori }}
                            </td>
                            <td class="py-3 text-right font-bold text-gray-800">
                                Rp{{ number_format($nilai, 0, ',', '.') }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="2" class="py-4 text-center text-gray-400 italic">Belum ada data pemasukan.</td>
                        </tr>
                        @endforelse
                    </tbody>
                    
                    {{-- Subtotal Aset --}}
                    <tfoot class="border-t-2 border-gray-200">
                        <tr>
                            <td class="py-4 font-extrabold text-gray-800 text-base">TOTAL ASET</td>
                            <td class="py-4 text-right font-extrabold text-green-600 text-lg">
                                Rp{{ number_format($neraca['total_aset'], 0, ',', '.') }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        {{-- KOLOM KANAN: KEWAJIBAN & EKUITAS --}}
        <div class="space-y-6">

            {{-- 1. KEWAJIBAN (PENGELUARAN / MODAL) --}}
            <div class="bg-white rounded-xl shadow-lg border-t-4 border-red-500 overflow-hidden">
                <div class="bg-red-50 px-6 py-4 border-b border-red-100">
                    <h3 class="font-bold text-red-800 uppercase tracking-wide">
                        <i class="fas fa-file-invoice-dollar me-2"></i> Kewajiban & Beban
                    </h3>
                </div>
                <div class="p-6">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-xs text-gray-400 uppercase border-b border-gray-100">
                                <th class="text-left py-2">Jenis Beban</th>
                                <th class="text-right py-2">Nilai (IDR)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 text-gray-700">
                            @forelse($neraca['kewajiban'] as $kategori => $nilai)
                            <tr class="hover:bg-gray-50 transition">
                                <td class="py-3 font-medium">
                                    @if(in_array($kategori, ['Gaji', 'Operasional', 'Listrik']))
                                        <i class="fas fa-receipt text-red-400 w-5"></i> Beban {{ $kategori }}
                                    @else
                                        <i class="fas fa-box text-red-300 w-5"></i> Modal {{ $kategori }}
                                    @endif
                                </td>
                                <td class="py-3 text-right font-bold text-gray-800">
                                    Rp{{ number_format($nilai, 0, ',', '.') }}
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="2" class="py-4 text-center text-gray-400 italic">Tidak ada pengeluaran tercatat.</td>
                            </tr>
                            @endforelse
                        </tbody>
                        <tfoot class="border-t border-gray-200 bg-gray-50">
                            <tr>
                                <td class="py-3 pl-3 font-bold text-gray-600">Total Kewajiban</td>
                                <td class="py-3 pr-3 text-right font-bold text-red-600">
                                    Rp{{ number_format($neraca['total_kewajiban'], 0, ',', '.') }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            {{-- 2. EKUITAS (LABA DITAHAN) --}}
            <div class="bg-white rounded-xl shadow-lg border-t-4 border-blue-500 overflow-hidden">
                <div class="bg-blue-50 px-6 py-4 border-b border-blue-100">
                    <h3 class="font-bold text-blue-800 uppercase tracking-wide">
                        <i class="fas fa-chart-pie me-2"></i> Ekuitas Pemilik
                    </h3>
                </div>
                <div class="p-6">
                    <table class="w-full text-sm">
                        <tbody class="divide-y divide-gray-50">
                            <tr>
                                <td class="py-3 font-medium text-gray-600">Modal Disetor</td>
                                <td class="py-3 text-right font-bold text-gray-800">Rp0</td> {{-- Bisa diganti dinamis jika ada fitur input modal awal --}}
                            </tr>
                            <tr>
                                <td class="py-3 font-medium text-gray-600">
                                    Laba Bersih (Tahun Berjalan)
                                    <div class="text-[10px] text-gray-400">(Aset - Kewajiban)</div>
                                </td>
                                <td class="py-3 text-right font-bold {{ $neraca['ekuitas'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                    Rp{{ number_format($neraca['ekuitas'], 0, ',', '.') }}
                                </td>
                            </tr>
                        </tbody>
                        <tfoot class="border-t-2 border-gray-200">
                            <tr>
                                <td class="py-4 font-extrabold text-gray-800 text-base">TOTAL EKUITAS + KEWAJIBAN</td>
                                <td class="py-4 text-right font-extrabold text-blue-600 text-lg">
                                    {{-- Rumus Neraca: Aset = Kewajiban + Ekuitas --}}
                                    Rp{{ number_format($neraca['total_kewajiban'] + $neraca['ekuitas'], 0, ',', '.') }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection