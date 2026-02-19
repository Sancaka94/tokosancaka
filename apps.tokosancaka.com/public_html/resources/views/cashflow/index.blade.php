@extends('layouts.app')

@section('content')
<div class="p-6 bg-gray-50 min-h-screen">

    <div class="mb-8">
        <div class="flex flex-col md:flex-row md:justify-between md:items-center gap-4 mb-6">
            <h2 class="text-3xl font-bold text-gray-800">Dashboard Keuangan</h2>

            <div class="flex flex-wrap gap-2">
                <a href="{{ route('cashflow.index') }}" class="inline-flex items-center justify-center gap-2 bg-slate-600 hover:bg-slate-700 text-white font-semibold py-2.5 px-4 rounded-lg shadow-sm transition-colors">
                    <i class="fas fa-chart-line"></i>
                    <span class="hidden sm:inline">Dashboard Harian</span>
                </a>

                <a href="{{ route('contacts.index') }}" class="inline-flex items-center justify-center gap-2 bg-amber-500 hover:bg-amber-600 text-white font-semibold py-2.5 px-4 rounded-lg shadow-sm transition-colors">
                    <i class="fas fa-address-book"></i>
                    <span class="hidden sm:inline">Data Kontak</span>
                </a>

                <a href="{{ route('cashflow.create') }}" class="inline-flex items-center justify-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold py-2.5 px-5 rounded-lg shadow-md transition-all hover:-translate-y-0.5">
                    <i class="fas fa-edit"></i>
                    <span>Catat Transaksi</span>
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded text-sm font-medium">
                {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded text-sm font-medium">
                {{ session('error') }}
            </div>
        @endif

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
                        <i class="fas fa-filter"></i>
                        Filter
                    </button>
                </div>
            </div>

            <div class="flex gap-2">
                <a href="{{ route('cashflow.export.pdf') }}" target="_blank" class="text-white bg-red-600 hover:bg-red-700 font-medium rounded-lg text-sm px-4 py-2.5 flex items-center gap-2">
                    <i class="fas fa-file-pdf"></i>
                    PDF
                </a>
                <a href="{{ route('cashflow.export.excel') }}" target="_blank" class="text-white bg-green-600 hover:bg-green-700 font-medium rounded-lg text-sm px-4 py-2.5 flex items-center gap-2">
                    <i class="fas fa-file-excel"></i>
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
                    <th scope="col" class="px-6 py-4">Kategori / Kontak</th>
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
                    <td class="px-6 py-4">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-gray-500 bg-gray-100 px-2 py-0.5 rounded block mb-1 w-max">
                            {{ str_replace('_', ' ', $item->category ?? 'UMUM') }}
                        </span>
                        <span class="font-bold text-gray-800">{{ $item->name }}</span>
                    </td>
                    <td class="px-6 py-4 text-gray-600">{{ $item->description ?? '-' }}</td>

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
                            <form action="{{ route('cashflow.destroy', $item->id) }}" method="POST" onsubmit="return confirm('Yakin hapus data ini? (Jika ini hutang/piutang, saldo akan di-rollback)')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="p-2 text-white bg-red-500 hover:bg-red-600 rounded-lg shadow-sm transition-colors" title="Hapus">
                                    <i class="fas fa-trash-alt"></i>
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

        <div class="p-4 border-t border-gray-100">
            {{ $data->links() }}
        </div>
    </div>
</div>
@endsection
