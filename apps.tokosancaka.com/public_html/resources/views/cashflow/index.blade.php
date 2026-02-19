@extends('layouts.app')

@section('content')
<div class="p-6 bg-gray-50 min-h-screen">

    <div class="mb-8">
        <h2 class="text-3xl font-bold text-gray-800 mb-6">Dashboard Keuangan</h2>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow-md p-6 border-l-8 border-blue-600">
                <p class="text-gray-500 font-medium">Sisa Saldo Saat Ini</p>
                <h3 class="text-3xl font-bold text-blue-800 mt-2">Rp {{ number_format($saldo, 0, ',', '.') }}</h3>
            </div>
            <div class="bg-white rounded-xl shadow-md p-6 border-l-8 border-green-500">
                <p class="text-gray-500 font-medium">Total Pemasukan</p>
                <h3 class="text-3xl font-bold text-green-600 mt-2">Rp {{ number_format($totalIncome, 0, ',', '.') }}</h3>
            </div>
            <div class="bg-white rounded-xl shadow-md p-6 border-l-8 border-red-500">
                <p class="text-gray-500 font-medium">Total Pengeluaran</p>
                <h3 class="text-3xl font-bold text-red-600 mt-2">Rp {{ number_format($totalExpense, 0, ',', '.') }}</h3>
            </div>
        </div>
    </div>

    <div class="bg-white p-6 rounded-xl shadow-sm mb-6">
        <form action="{{ route('cashflow.index') }}" method="GET" class="flex flex-col md:flex-row gap-4 items-end justify-between">

            <div class="flex flex-col md:flex-row gap-4 w-full md:w-auto">
                <div class="flex flex-col">
                    <label class="text-xs font-bold text-gray-500 uppercase mb-1">Dari Tanggal</label>
                    <input type="date" name="start_date" value="{{ request('start_date') }}" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                </div>
                <div class="flex flex-col">
                    <label class="text-xs font-bold text-gray-500 uppercase mb-1">Sampai Tanggal</label>
                    <input type="date" name="end_date" value="{{ request('end_date') }}" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                </div>

                <div class="flex flex-col w-40">
                    <label class="text-xs font-bold text-gray-500 uppercase mb-1">Tipe</label>
                    <select name="type" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                        <option value="">Semua</option>
                        <option value="income" {{ request('type') == 'income' ? 'selected' : '' }}>Pemasukan</option>
                        <option value="expense" {{ request('type') == 'expense' ? 'selected' : '' }}>Pengeluaran</option>
                    </select>
                </div>

                <div class="pb-0.5">
                    <button type="submit" class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path></svg>
                        Filter
                    </button>
                </div>
            </div>

            <div class="flex gap-2">
                <a href="{{ route('cashflow.export.pdf') }}" target="_blank" class="text-white bg-red-600 hover:bg-red-700 font-medium rounded-lg text-sm px-4 py-2.5 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                    PDF
                </a>
                <a href="{{ route('cashflow.export.excel') }}" target="_blank" class="text-white bg-green-600 hover:bg-green-700 font-medium rounded-lg text-sm px-4 py-2.5 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    Excel
                </a>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
        <table class="w-full text-sm text-left text-gray-500">
            <thead class="text-xs text-gray-700 uppercase bg-gray-100">
                <tr>
                    <th scope="col" class="px-6 py-4">No</th>
                    <th scope="col" class="px-6 py-4">Tanggal</th>
                    <th scope="col" class="px-6 py-4">Nama</th>
                    <th scope="col" class="px-6 py-4">Keterangan</th>
                    <th scope="col" class="px-6 py-4 text-right">Pemasukan</th>
                    <th scope="col" class="px-6 py-4 text-right">Pengeluaran</th>
                    <th scope="col" class="px-6 py-4 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($data as $key => $item)
                <tr class="bg-white border-b hover:bg-gray-50">
                    <td class="px-6 py-4 font-medium text-gray-900">{{ $data->firstItem() + $key }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">{{ \Carbon\Carbon::parse($item->date)->format('d M Y') }}</td>
                    <td class="px-6 py-4 font-bold text-gray-800">{{ $item->name }}</td>
                    <td class="px-6 py-4 text-gray-600">{{ $item->description }}</td>

                    <td class="px-6 py-4 text-right">
                        @if($item->type == 'income')
                            <span class="text-green-600 font-bold bg-green-100 px-2 py-1 rounded">+ {{ number_format($item->amount, 0, ',', '.') }}</span>
                        @else
                            -
                        @endif
                    </td>

                    <td class="px-6 py-4 text-right">
                        @if($item->type == 'expense')
                            <span class="text-red-600 font-bold bg-red-100 px-2 py-1 rounded">- {{ number_format($item->amount, 0, ',', '.') }}</span>
                        @else
                            -
                        @endif
                    </td>

                    <td class="px-6 py-4 text-center">
                        <div class="flex item-center justify-center space-x-2">
                            <form action="{{ route('cashflow.destroy', $item->id) }}" method="POST" onsubmit="return confirm('Yakin hapus data ini?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="p-2 text-white bg-red-500 hover:bg-red-600 rounded-lg shadow-sm" title="Hapus">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-8 text-center text-gray-400 text-lg">
                        Belum ada data transaksi.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>

        <div class="p-4">
            {{ $data->links() }}
        </div>
    </div>
</div>
@endsection
