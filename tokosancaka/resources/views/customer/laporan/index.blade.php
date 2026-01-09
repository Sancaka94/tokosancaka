@extends('layouts.customer')

@section('title', 'Laporan Keuangan')

@section('content')
<div class="bg-gray-100 min-h-screen">
    <div class="container mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">

        <!-- Header Halaman -->
        <div class="mb-8 flex items-center">
             <i class="fas fa-file-invoice-dollar fa-2x text-gray-700 mr-4"></i>
            <div>
                <h1 class="text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">Laporan Keuangan</h1>
                <p class="mt-1 text-lg text-gray-600">Riwayat semua transaksi dan ringkasan saldo Anda.</p>
            </div>
        </div>
        
        <!-- ============================================= -->
        <!-- Ringkasan Saldo (4 Kartu) -->
        <!-- ============================================= -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            
            {{-- 1. Saldo Saat Ini --}}
            <div class="relative bg-blue-500 text-white p-6 rounded-lg shadow-md overflow-hidden transform transition duration-300 hover:-translate-y-1 hover:shadow-xl">
                <div class="relative z-10">
                    <h3 class="text-3xl font-bold">Rp {{ number_format($saldo, 0, ',', '.') }}</h3>
                    <!-- Tag <strong> dihapus, diganti class -->
                    <p class="text-sm font-medium text-blue-100">Saldo Saat Ini (Total)</p>
                </div>
                <div class="absolute top-0 right-0 p-4 -mt-2 -mr-2 z-0 opacity-30">
                    <i class="fas fa-wallet fa-5x text-white"></i>
                </div>
                <span class="relative z-10 block mt-4 text-sm text-blue-200">
                    Total saldo di akun Anda
                </span>
            </div>

            {{-- 2. Pendapatan Marketplace --}}
            <div class="relative bg-green-500 text-white p-6 rounded-lg shadow-md overflow-hidden transform transition duration-300 hover:-translate-y-1 hover:shadow-xl">
                <div class="relative z-10">
                    <h3 class="text-3xl font-bold">Rp {{ number_format($totalPemasukan, 0, ',', '.') }}</h3>
                    <p class="text-sm font-medium text-green-100">Pendapatan Marketplace</p>
                </div>
                <div class="absolute top-0 right-0 p-4 -mt-2 -mr-2 z-0 opacity-30">
                    <i class="fas fa-store fa-5x text-white"></i>
                </div>
                <span class="relative z-10 block mt-4 text-sm text-green-200">
                    Pendapatan dari penjualan
                </span>
            </div>

            {{-- 3. Top Up Saldo --}}
            <div class="relative bg-teal-500 text-white p-6 rounded-lg shadow-md overflow-hidden transform transition duration-300 hover:-translate-y-1 hover:shadow-xl">
                <div class="relative z-10">
                    <h3 class="text-3xl font-bold">Rp {{ number_format($totalTopUp, 0, ',', '.') }}</h3>
                    <p class="text-sm font-medium text-teal-100">Top Up Saldo</p>
                </div>
                <div class="absolute top-0 right-0 p-4 -mt-2 -mr-2 z-0 opacity-30">
                    <i class="fas fa-arrow-down fa-5x text-white"></i>
                </div>
                <span class="relative z-10 block mt-4 text-sm text-teal-200">
                    Total saldo masuk (non-penjualan)
                </span>
            </div>

            {{-- 4. Pengeluaran --}}
            <div class="relative bg-red-500 text-white p-6 rounded-lg shadow-md overflow-hidden transform transition duration-300 hover:-translate-y-1 hover:shadow-xl">
                <div class="relative z-10">
                    <h3 class="text-3xl font-bold">Rp {{ number_format($totalPengeluaran, 0, ',', '.') }}</h3>
                    <p class="text-sm font-medium text-red-100">Pengeluaran (Periode Ini)</p>
                </div>
                <div class="absolute top-0 right-0 p-4 -mt-2 -mr-2 z-0 opacity-30">
                    <i class="fas fa-arrow-up fa-5x text-white"></i>
                </div>
                <span class="relative z-10 block mt-4 text-sm text-red-200">
                    Total saldo keluar
                </span>
            </div>
            
        </div>
        <!-- ============================================= -->
        <!-- AKHIR: Ringkasan Saldo -->
        <!-- ============================================= -->

        <!-- Form Filter Tanggal (Dipindah ke setelah kartu) -->
        <div class="mb-8 rounded-xl bg-white p-6 shadow-lg mt-8">
            <form method="GET" action="{{ route('customer.laporan.index') }}" class="grid grid-cols-1 gap-6 sm:grid-cols-3 lg:grid-cols-4 items-end">
                <div>
                    <label for="start_date" class="block text-sm font-medium text-gray-700">Tanggal Mulai</label>
                    <input type="date" name="start_date" id="start_date" value="{{ $startDate }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>
                <div>
                    <label for="end_date" class="block text-sm font-medium text-gray-700">Tanggal Selesai</label>
                    <input type="date" name="end_date" id="end_date" value="{{ $endDate }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>
                <div class="sm:col-span-1 lg:col-span-2 flex space-x-3">
                    <button type="submit" class="inline-flex w-full justify-center rounded-md border border-transparent bg-indigo-600 py-2 px-4 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                        <i class="fas fa-filter mr-2"></i> Terapkan Filter
                    </button>
                    <!-- Desain Tombol Reset Diperbarui -->
                    <a href="{{ route('customer.laporan.index') }}" class="inline-flex w-full justify-center rounded-md border border-gray-300 bg-white py-2 px-4 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                        Reset
                    </a>
                </div>
            </form>
        </div>
        
        <!-- ============================================= -->
        <!-- AKHIR PERBAIKAN: Ringkasan Saldo -->
        <!-- ============================================= -->


        <!-- Tabel Riwayat Transaksi -->
        <div class="mt-8 overflow-hidden rounded-xl border border-blue-200 bg-white shadow-lg">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-blue-200">
                    <thead class="bg-blue-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"><strong>Tanggal</strong></th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"><strong>Deskripsi</strong></th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500"><strong>Jumlah</strong></th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500"><strong>Sisa Saldo</strong></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        @forelse ($transactions as $tx)
                            <tr>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                    <div>{{ $tx->created_at->format('d M Y') }}</div>
                                    <div class="text-xs text-gray-400">{{ $tx->created_at->format('H:i') }} WIB</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-800"><strong>{{ $tx->description }}</strong></div>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-right text-sm">
                                    {{-- PERBAIKAN: Gunakan 'type' (topup, revenue, payment) --}}
                                    @if($tx->type == 'topup' || $tx->type == 'revenue')
                                        <span class="font-semibold text-green-600">+ Rp {{ number_format($tx->amount, 0, ',', '.') }}</span>
                                    @else
                                        <span class="font-semibold text-red-600">- Rp {{ number_format($tx->amount, 0, ',', '.') }}</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium text-gray-700">
                                    <strong>Rp {{ number_format($tx->running_balance, 0, ',', '.') }}</strong>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-12 text-center">
                                    <div class="mx-auto max-w-md">
                                        <i class="fas fa-exchange-alt fa-3x text-gray-400"></i>
                                        <h3 class="mt-2 text-sm font-medium text-gray-900">Tidak ada riwayat transaksi</h3>
                                        <p class="mt-1 text-sm text-gray-500">Tidak ada transaksi yang cocok dengan filter yang Anda pilih.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    {{-- Tampilkan Saldo Awal HANYA jika ada filter tanggal --}}
                    @if($startDate && $endDate)
                    <tfoot class="bg-gray-50 border-t-2 border-gray-300">
                        <tr>
                            <td colspan="3" class="px-6 py-3 text-right text-sm font-semibold text-gray-600">Saldo Awal Periode (sebelum {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }})</td>
                            <td class="px-6 py-3 text-right text-sm font-bold text-gray-800">Rp {{ number_format($saldoAwal, 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
            
            @if ($transactions->hasPages())
                <div class="border-t border-gray-200 bg-white px-4 py-3 sm:px-6">
                    {{ $transactions->appends(request()->query())->links() }}
                </div>
            @endif
        </div>

    </div>
</div>
@endsection