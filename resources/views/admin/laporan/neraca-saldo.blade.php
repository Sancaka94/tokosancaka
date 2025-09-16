@extends('layouts.admin')

@section('content')
<div class="bg-gray-800 p-6 rounded-lg shadow-lg">
    <div class="flex justify-between items-center mb-6 border-b border-gray-700 pb-4">
        <div>
            <h1 class="text-3xl font-bold text-white">Laporan Neraca Saldo</h1>
            <p class="text-gray-400">Posisi per tanggal: {{ \Carbon\Carbon::parse($endDate)->format('d F Y') }}</p>
        </div>
    </div>

    {{-- Filter Tanggal --}}
    <form method="GET" action="{{ route('admin.laporan.neracaSaldo') }}" class="bg-gray-900 p-4 rounded-lg mb-6">
        <div class="flex items-center space-x-4">
            <div>
                <label for="end_date" class="text-sm font-medium text-gray-300">Pilih Tanggal Akhir:</label>
                <input type="date" name="end_date" id="end_date" value="{{ $endDate }}" class="bg-gray-700 border border-gray-600 text-white text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5">
            </div>
            <button type="submit" class="self-end bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2.5 px-5 rounded-lg">
                <i class="fa-solid fa-filter mr-2"></i> Terapkan
            </button>
        </div>
    </form>

    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left text-gray-300">
            <thead class="text-xs text-white uppercase bg-gray-700">
                <tr>
                    <th scope="col" class="px-6 py-3">Kode Akun</th>
                    <th scope="col" class="px-6 py-3">Nama Akun</th>
                    <th scope="col" class="px-6 py-3 text-right">Debit (Rp)</th>
                    <th scope="col" class="px-6 py-3 text-right">Kredit (Rp)</th>
                </tr>
            </thead>
            <tbody>
                @forelse($trialBalance as $item)
                    <tr class="border-b border-gray-700 hover:bg-gray-600">
                        <td class="px-6 py-4">{{ $item['account']->kode }}</td>
                        <td class="px-6 py-4 font-medium text-white">{{ $item['account']->nama }}</td>
                        <td class="px-6 py-4 text-right">{{ number_format($item['debit'], 2, ',', '.') }}</td>
                        <td class="px-6 py-4 text-right">{{ number_format($item['credit'], 2, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center py-4">Tidak ada data transaksi pada periode ini.</td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot class="font-bold text-white bg-gray-700">
                <tr>
                    <td colspan="2" class="px-6 py-3 text-right">Total</td>
                    <td class="px-6 py-3 text-right">{{ number_format($totalDebit, 2, ',', '.') }}</td>
                    <td class="px-6 py-3 text-right">{{ number_format($totalCredit, 2, ',', '.') }}</td>
                </tr>
                 @if(round($totalDebit) != round($totalCredit))
                <tr>
                    <td colspan="4" class="px-6 py-2 text-center bg-red-800 text-white">
                        Peringatan: Total Debit dan Kredit tidak seimbang!
                    </td>
                </tr>
                @endif
            </tfoot>
        </table>
    </div>
</div>
@endsection
