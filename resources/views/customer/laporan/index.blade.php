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

        <!-- Ringkasan Saldo dalam Grid -->
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
            <!-- Saldo Saat Ini -->
            <div class="overflow-hidden rounded-xl bg-white p-6 shadow-lg">
                <dt class="truncate text-sm font-medium text-slate-500">Saldo Saat Ini</dt>
                <dd class="mt-1 text-4xl font-semibold tracking-tight text-indigo-600">Rp {{ number_format($saldo, 0, ',', '.') }}</dd>
            </div>
            <!-- Total Pemasukan -->
            <div class="overflow-hidden rounded-xl bg-white p-6 shadow-lg">
                <dt class="truncate text-sm font-medium text-slate-500">Total Pemasukan</dt>
                <dd class="mt-1 flex items-baseline gap-x-2">
                    <span class="text-3xl font-semibold tracking-tight text-green-600">Rp {{ number_format($totalPemasukan, 0, ',', '.') }}</span>
                    <span class="text-sm text-slate-500">(dari Top Up)</span>
                </dd>
            </div>
            <!-- Total Pengeluaran -->
            <div class="overflow-hidden rounded-xl bg-white p-6 shadow-lg">
                <dt class="truncate text-sm font-medium text-slate-500">Total Pengeluaran</dt>
                <dd class="mt-1 flex items-baseline gap-x-2">
                    <span class="text-3xl font-semibold tracking-tight text-red-600">Rp {{ number_format($totalPengeluaran, 0, ',', '.') }}</span>
                    <span class="text-sm text-slate-500">(dari Pesanan)</span>
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
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white">
                        @forelse ($transactions as $tx)
                            <tr>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-500">
                                    <div>{{ $tx->created_at->format('d M Y') }}</div>
                                    <div class="text-xs text-slate-400">{{ $tx->created_at->format('H:i') }} WIB</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-slate-800">{{ $tx->description }}</div>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-right text-sm">
                                    @if($tx->type == 'masuk')
                                        <span class="font-semibold text-green-600">+ Rp {{ number_format($tx->amount, 0, ',', '.') }}</span>
                                    @else
                                        <span class="font-semibold text-red-600">- Rp {{ number_format($tx->amount, 0, ',', '.') }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-6 py-12 text-center">
                                    <div class="mx-auto max-w-md">
                                        <i class="fas fa-exchange-alt fa-3x text-slate-400"></i>
                                        <h3 class="mt-2 text-sm font-medium text-slate-900">Tidak ada riwayat transaksi</h3>
                                        <p class="mt-1 text-sm text-slate-500">Semua transaksi masuk dan keluar akan muncul di sini.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <!-- Navigasi Paginasi -->
            @if ($transactions->hasPages())
                <div class="border-t border-slate-200 bg-white px-4 py-3 sm:px-6">
                    {{ $transactions->links() }}
                </div>
            @endif
        </div>

    </div>
</div>
@endsection
