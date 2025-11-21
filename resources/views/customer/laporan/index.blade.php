@extends('layouts.customer')

@section('title', 'Laporan Keuangan')

@section('content')
<div class="bg-slate-50 min-h-screen">
    <div class="container mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">

        <!-- Header Halaman -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">Laporan Keuangan</h1>
            <p class="mt-2 text-lg text-slate-600">Riwayat semua transaksi dan ringkasan saldo Anda.</p>
        </div>

        <!-- Form Filter Tanggal -->
        <div class="mb-8 rounded-xl bg-white p-6 shadow-lg">
            <form method="GET" action="{{ route('customer.laporan.index') }}" class="grid grid-cols-1 gap-6 sm:grid-cols-3 lg:grid-cols-4 items-end">
                <div>
                    <label for="start_date" class="block text-sm font-medium text-slate-700">Tanggal Mulai</label>
                    <input type="date" name="start_date" id="start_date" value="{{ $startDate }}" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>
                <div>
                    <label for="end_date" class="block text-sm font-medium text-slate-700">Tanggal Selesai</label>
                    <input type="date" name="end_date" id="end_date" value="{{ $endDate }}" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>
                <div class="sm:col-span-1 lg:col-span-2 flex space-x-3">
                    <button type="submit" class="inline-flex w-full justify-center rounded-md border border-transparent bg-indigo-600 py-2 px-4 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                        <i class="fas fa-filter mr-2"></i> Terapkan Filter
                    </button>
                    <a href="{{ route('customer.laporan.index') }}" class="inline-flex w-full justify-center rounded-md border border-slate-300 bg-white py-2 px-4 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                        Reset
                    </a>
                </div>
            </form>
        </div>
        
        <!-- Ringkasan Saldo dalam Grid -->
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
            <div class="overflow-hidden rounded-xl bg-white p-6 shadow-lg">
                <dt class="truncate text-sm font-medium text-slate-500">Saldo Saat Ini (Total)</dt>
                <dd class="mt-1 text-4xl font-semibold tracking-tight text-indigo-600">Rp {{ number_format($saldo, 0, ',', '.') }}</dd>
            </div>
            <div class="overflow-hidden rounded-xl bg-white p-6 shadow-lg">
                <dt class="truncate text-sm font-medium text-slate-500">Pemasukan (Periode Ini)</dt>
                <dd class="mt-1 flex items-baseline gap-x-2">
                    <span class="text-3xl font-semibold tracking-tight text-green-600">Rp {{ number_format($totalPemasukan, 0, ',', '.') }}</span>
                </dd>
            </div>
            <div class="overflow-hidden rounded-xl bg-white p-6 shadow-lg">
                <dt class="truncate text-sm font-medium text-slate-500">Pengeluaran (Periode Ini)</dt>
                <dd class="mt-1 flex items-baseline gap-x-2">
                    <span class="text-3xl font-semibold tracking-tight text-red-600">Rp {{ number_format($totalPengeluaran, 0, ',', '.') }}</span>
                </dd>
            </div>
        </div>

        <!-- Tabel Riwayat Transaksi -->
        <div class="mt-8 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-lg">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Tanggal</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Deskripsi</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-slate-500">Jumlah</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-slate-500">Sisa Saldo</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white">
                        @forelse ($transactions as $tx)
                            <tr>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-500">
                                    {{-- Kode ini aman karena controller sudah memastikan $tx->created_at adalah objek Carbon --}}
                                    <div>{{ $tx->created_at->format('d M Y') }}</div>
                                    <div class="text-xs text-slate-400">{{ $tx->created_at->format('H:i') }} WIB</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-slate-800">{{ $tx->description }}</div>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-right text-sm">
                                    @if($tx->type == 'topup')
                                        <span class="font-semibold text-green-600">+ Rp {{ number_format($tx->amount, 0, ',', '.') }}</span>
                                    @else
                                        <span class="font-semibold text-red-600">- Rp {{ number_format($tx->amount, 0, ',', '.') }}</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium text-slate-700">
                                    Rp {{ number_format($tx->running_balance, 0, ',', '.') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-12 text-center">
                                    <div class="mx-auto max-w-md">
                                        <i class="fas fa-exchange-alt fa-3x text-slate-400"></i>
                                        <h3 class="mt-2 text-sm font-medium text-slate-900">Tidak ada riwayat transaksi</h3>
                                        <p class="mt-1 text-sm text-slate-500">Tidak ada transaksi yang cocok dengan filter yang Anda pilih.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    {{-- Menampilkan Saldo Awal hanya jika ada filter tanggal yang aktif --}}
                    @if($startDate && $endDate)
                    <tfoot class="bg-slate-50 border-t-2 border-slate-300">
                        <tr>
                            <td colspan="3" class="px-6 py-3 text-right text-sm font-semibold text-slate-600">Saldo Awal Periode (sebelum {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }})</td>
                            <td class="px-6 py-3 text-right text-sm font-bold text-slate-800">Rp {{ number_format($saldoAwal, 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
            
            @if ($transactions->hasPages())
                <div class="border-t border-slate-200 bg-white px-4 py-3 sm:px-6">
                    {{-- Menambahkan appends agar filter tetap aktif saat paginasi --}}
                    {{ $transactions->appends(request()->query())->links() }}
                </div>
            @endif
        </div>

    </div>
</div>
@endsection

