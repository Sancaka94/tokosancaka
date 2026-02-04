@extends('layouts.admin')

@section('content')
<div class="bg-gray-800 p-6 rounded-lg shadow-lg">
    <div class="flex justify-between items-center mb-6 border-b border-gray-700 pb-4">
         <div>
            <h1 class="text-3xl font-bold text-white">Laporan Neraca</h1>
            <p class="text-gray-400">Posisi per tanggal: {{ \Carbon\Carbon::parse($endDate)->format('d F Y') }}</p>
        </div>
    </div>

    {{-- Filter Tanggal --}}
    <form method="GET" action="{{ route('admin.laporan.neraca') }}" class="bg-gray-900 p-4 rounded-lg mb-6">
        <div class="flex items-center space-x-4">
            <div>
                <label for="end_date" class="text-sm font-medium text-gray-300">Pilih Tanggal:</label>
                <input type="date" name="end_date" id="end_date" value="{{ $endDate }}" class="bg-gray-700 border border-gray-600 text-white text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5">
            </div>
            <button type="submit" class="self-end bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2.5 px-5 rounded-lg">
                <i class="fa-solid fa-filter mr-2"></i> Terapkan
            </button>
        </div>
    </form>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        {{-- Sisi Aktiva (Aset) --}}
        <div class="bg-gray-900 p-4 rounded-lg">
            <h2 class="text-xl font-bold text-white border-b border-gray-700 pb-2 mb-4">Aktiva</h2>
            <table class="w-full text-sm text-left text-gray-300">
                @php $totalAssets = 0; @endphp
                @foreach($assets as $asset)
                    @php $totalAssets += $asset->balance; @endphp
                    <tr>
                        <td class="py-2 pr-4">{{ $asset->nama }}</td>
                        <td class="py-2 text-right">{{ number_format($asset->balance, 2, ',', '.') }}</td>
                    </tr>
                @endforeach
            </table>
            <div class="border-t border-gray-700 mt-4 pt-2 flex justify-between font-bold text-white">
                <span>Total Aktiva</span>
                <span>{{ number_format($totalAssets, 2, ',', '.') }}</span>
            </div>
        </div>

        {{-- Sisi Pasiva (Kewajiban & Ekuitas) --}}
        <div class="bg-gray-900 p-4 rounded-lg">
            <h2 class="text-xl font-bold text-white border-b border-gray-700 pb-2 mb-4">Pasiva</h2>
            
            {{-- Kewajiban --}}
            <h3 class="font-semibold text-gray-200 mt-4 mb-2">Kewajiban</h3>
            <table class="w-full text-sm text-left text-gray-300">
                @php $totalLiabilities = 0; @endphp
                @foreach($liabilities as $liability)
                    @php $balance = abs($liability->balance); $totalLiabilities += $balance; @endphp
                    <tr>
                        <td class="py-2 pr-4">{{ $liability->nama }}</td>
                        <td class="py-2 text-right">{{ number_format($balance, 2, ',', '.') }}</td>
                    </tr>
                @endforeach
            </table>
            <div class="border-t border-gray-700 mt-2 pt-2 flex justify-between font-semibold text-gray-200">
                <span>Total Kewajiban</span>
                <span>{{ number_format($totalLiabilities, 2, ',', '.') }}</span>
            </div>

            {{-- Ekuitas --}}
            <h3 class="font-semibold text-gray-200 mt-6 mb-2">Ekuitas</h3>
            <table class="w-full text-sm text-left text-gray-300">
                 @php $totalEquities = 0; @endphp
                 @foreach($equities as $equity)
                    @php $balance = abs($equity->balance); $totalEquities += $balance; @endphp
                    <tr>
                        <td class="py-2 pr-4">{{ $equity->nama }}</td>
                        <td class="py-2 text-right">{{ number_format($balance, 2, ',', '.') }}</td>
                    </tr>
                 @endforeach
                 <tr>
                    <td class="py-2 pr-4">Laba/Rugi Periode Berjalan</td>
                    <td class="py-2 text-right">{{ number_format($labaRugiBerjalan, 2, ',', '.') }}</td>
                 </tr>
            </table>
            @php $totalEquities += $labaRugiBerjalan; @endphp
            <div class="border-t border-gray-700 mt-2 pt-2 flex justify-between font-semibold text-gray-200">
                <span>Total Ekuitas</span>
                <span>{{ number_format($totalEquities, 2, ',', '.') }}</span>
            </div>

            {{-- Total Pasiva --}}
            <div class="border-t-2 border-indigo-500 mt-6 pt-2 flex justify-between font-bold text-white">
                <span>Total Kewajiban dan Ekuitas</span>
                <span>{{ number_format($totalLiabilities + $totalEquities, 2, ',', '.') }}</span>
            </div>
        </div>
    </div>
</div>
@endsection
