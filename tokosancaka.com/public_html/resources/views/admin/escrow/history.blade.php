@extends('layouts.admin')

@section('title', 'Riwayat Pencairan Dana Escrow')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="mb-6 flex flex-col md:flex-row md:justify-between md:items-center gap-4">
        <div>
            <div class="flex items-center gap-2 text-sm text-blue-600 font-medium mb-1">
                <a href="{{ route('admin.escrow.index') }}" class="hover:underline"><i class="fas fa-arrow-left mr-1"></i> Dashboard Pencairan Dana Marketplace</a>
            </div>
            <h2 class="text-2xl font-bold text-gray-800">Riwayat Pencairan Dana</h2>
            <p class="text-sm text-gray-500 mt-1">Laporan rekam jejak dana yang sudah berhasil ditransfer ke saldo penjual.</p>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
        <div class="bg-gradient-to-r from-green-500 to-emerald-600 rounded-xl shadow-md p-6 flex items-center justify-between text-white relative overflow-hidden">
            <div class="relative z-10">
                <p class="text-sm font-semibold uppercase tracking-wider mb-1 opacity-90">Total Dana Dicairkan</p>
                <h3 class="text-3xl font-bold">Rp {{ number_format($totalDanaBersih, 0, ',', '.') }}</h3>
                <p class="text-xs mt-1 opacity-80">*Sesuai filter tanggal yang dipilih</p>
            </div>
            <i class="fas fa-money-check-alt text-6xl opacity-20 absolute right-4 bottom-2 transform -rotate-12"></i>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500 font-semibold uppercase tracking-wider mb-1">Jumlah Transaksi Cair</p>
                <h3 class="text-3xl font-bold text-gray-800">{{ number_format($totalTransaksi, 0, ',', '.') }} <span class="text-lg font-normal text-gray-500">Pesanan</span></h3>
            </div>
            <div class="p-4 rounded-full bg-blue-50 text-blue-500">
                <i class="fas fa-list-check text-2xl"></i>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 mb-6 flex justify-between items-center flex-wrap gap-4">
        <h3 class="font-bold text-gray-700"><i class="fas fa-filter text-gray-400 mr-2"></i> Filter Data</h3>
        <form action="{{ route('admin.escrow.history') }}" method="GET" class="flex flex-col sm:flex-row items-center gap-2">
            <div class="relative">
                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                    <i class="far fa-calendar-alt text-gray-400 text-sm"></i>
                </div>
                <input type="date" name="start_date" value="{{ request('start_date') }}" class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-blue-500 focus:border-blue-500 block pl-9 p-2.5" title="Dari Tanggal Cair">
            </div>
            <span class="text-gray-400 text-xs font-semibold">s/d</span>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                    <i class="far fa-calendar-check text-gray-400 text-sm"></i>
                </div>
                <input type="date" name="end_date" value="{{ request('end_date') }}" class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-blue-500 focus:border-blue-500 block pl-9 p-2.5" title="Sampai Tanggal Cair">
            </div>
            <button type="submit" class="bg-gray-800 text-white px-4 py-2.5 rounded-lg text-xs font-semibold hover:bg-gray-900 transition-colors shadow-sm">
                Cari Riwayat
            </button>
            @if(request()->has('start_date'))
                <a href="{{ route('admin.escrow.history') }}" class="bg-red-50 text-red-600 border border-red-200 px-3 py-2.5 rounded-lg text-xs font-semibold hover:bg-red-100 transition-colors" title="Reset">
                    <i class="fas fa-undo"></i>
                </a>
            @endif
        </form>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase w-12">#</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Waktu Cair</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Invoice & Resi</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Penerima Dana (Toko)</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Rincian Nominal</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($escrows as $escrow)
                    @php
                        $danaPenjual = $escrow->nominal_ditahan - $escrow->nominal_ongkir;
                    @endphp
                    <tr class="hover:bg-blue-50/30 transition-colors">
                        <td class="px-4 py-4 align-middle text-center text-sm font-medium text-gray-400">
                            {{ $loop->iteration + $escrows->firstItem() - 1 }}
                        </td>

                        <td class="px-4 py-4 align-middle">
                            <div class="text-sm font-bold text-gray-800">{{ $escrow->dicairkan_pada ? $escrow->dicairkan_pada->format('d M Y') : '-' }}</div>
                            <div class="text-xs text-gray-500 mt-0.5"><i class="far fa-clock mr-1"></i> {{ $escrow->dicairkan_pada ? $escrow->dicairkan_pada->format('H:i') : '-' }} WIB</div>
                        </td>

                        <td class="px-4 py-4 align-middle">
                            <div class="text-sm font-bold text-blue-600 mb-1">{{ $escrow->invoice_number }}</div>
                            <div class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium bg-gray-100 text-gray-600 border border-gray-200">
                                <i class="fas fa-barcode mr-1 text-gray-400"></i> Resi: {{ $escrow->order->shipping_reference ?? '-' }}
                            </div>
                        </td>

                        <td class="px-4 py-4 align-middle">
                            <div class="text-sm font-bold text-gray-800"><i class="fas fa-store text-gray-400 mr-1.5"></i>{{ $escrow->store->name ?? 'Toko Terhapus' }}</div>
                            <div class="text-xs text-gray-500 mt-1"><i class="fas fa-user text-gray-400 mr-1.5"></i>{{ $escrow->store->user->nama_lengkap ?? '-' }}</div>
                        </td>

                        <td class="px-4 py-4 align-middle">
                            <div class="bg-green-50 border border-green-200 rounded p-2 text-xs w-56">
                                <div class="flex justify-between text-gray-500 mb-1">
                                    <span>Total Trx:</span>
                                    <span>Rp {{ number_format($escrow->nominal_ditahan, 0, ',', '.') }}</span>
                                </div>
                                <div class="flex justify-between text-red-500 mb-1">
                                    <span>Ongkir (SCK):</span>
                                    <span>- Rp {{ number_format($escrow->nominal_ongkir, 0, ',', '.') }}</span>
                                </div>
                                <div class="flex justify-between border-t border-green-200 pt-1 mt-1 font-bold text-green-700">
                                    <span>Cair ke Saldo:</span>
                                    <span>Rp {{ number_format($danaPenjual, 0, ',', '.') }}</span>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                            <i class="fas fa-money-bill-wave text-4xl mb-3 text-gray-300"></i>
                            <p class="text-lg font-bold text-gray-600">Belum ada riwayat pencairan.</p>
                            <p class="text-sm mt-1 text-gray-400">Data akan muncul setelah Anda mencairkan dana di menu Escrow Utama.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($escrows->hasPages())
        <div class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
            {{ $escrows->links('pagination::tailwind') }}
        </div>
        @endif
    </div>
</div>
@endsection
