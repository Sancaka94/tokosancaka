@extends('layouts.admin')

@section('title', 'Riwayat Pencairan, Refund & Retur Escrow')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="mb-6 flex flex-col md:flex-row md:justify-between md:items-center gap-4">
        <div>
            <div class="flex items-center gap-2 text-sm text-blue-600 font-medium mb-1">
                <a href="{{ route('admin.escrow.index') }}" class="hover:underline"><i class="fas fa-arrow-left mr-1"></i> Dashboard Penahanan Dana</a>
            </div>
            <h2 class="text-2xl font-bold text-gray-800">Riwayat Pencairan, Refund & Retur</h2>
            <p class="text-sm text-gray-500 mt-1">Laporan rekam jejak dana yang berhasil ditransfer, direfund, atau diretur.</p>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-4 mb-6">
        <div class="bg-gradient-to-r from-green-500 to-emerald-600 rounded-xl shadow-md p-4 text-white relative overflow-hidden lg:col-span-2">
            <div class="relative z-10">
                <p class="text-[10px] font-bold uppercase tracking-wider mb-1 opacity-90">Total Cair ke Penjual (Normal)</p>
                <h3 class="text-2xl font-bold">Rp {{ number_format($totalDanaBersih, 0, ',', '.') }}</h3>
            </div>
            <i class="fas fa-hand-holding-usd text-5xl opacity-20 absolute -right-2 -bottom-2 transform -rotate-12"></i>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-red-100 p-4 flex items-center justify-between">
            <div>
                <p class="text-[10px] text-gray-500 font-bold uppercase tracking-wider mb-1">Total Refund (Pembeli)</p>
                <h3 class="text-lg font-bold text-red-600">Rp {{ number_format($totalDanaRefund ?? 0, 0, ',', '.') }}</h3>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-teal-100 p-4 flex items-center justify-between">
            <div>
                <p class="text-[10px] text-gray-500 font-bold uppercase tracking-wider mb-1">Total Nilai Retur Barang</p>
                <h3 class="text-lg font-bold text-teal-600">Rp {{ number_format($totalNilaiRetur ?? 0, 0, ',', '.') }}</h3>
            </div>
        </div>

        <div class="bg-orange-50 rounded-xl shadow-sm border border-orange-100 p-4 flex items-center justify-between">
            <div>
                <p class="text-[10px] text-orange-800 font-bold uppercase tracking-wider mb-1">Trx Refund/Retur</p>
                <h3 class="text-2xl font-bold text-orange-600">{{ number_format($totalTransaksiBermasalah ?? 0, 0, ',', '.') }}</h3>
            </div>
            <div class="p-2.5 rounded-full bg-orange-100 text-orange-500">
                <i class="fas fa-exclamation-triangle text-lg"></i>
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
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase w-40">Tipe & Waktu</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Invoice & Resi (Awal)</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Penerima Hak</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase w-56">Rincian Nominal</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($escrows as $escrow)
                    @php
                        $isRefund = str_contains(strtoupper($escrow->catatan), 'REFUND');
                        $isRetur = in_array(strtolower($escrow->order->status ?? ''), ['returning', 'return_approved', 'returned']);
                        $danaBersih = $escrow->nominal_ditahan - $escrow->nominal_ongkir;
                        $time = $escrow->dicairkan_pada ? $escrow->dicairkan_pada : $escrow->updated_at;
                    @endphp
                    <tr class="hover:bg-gray-50 transition-colors">

                        <td class="px-4 py-4 align-top text-center text-sm font-medium text-gray-400">
                            {{ $loop->iteration + $escrows->firstItem() - 1 }}
                        </td>

                        <td class="px-4 py-4 align-top">
                            @if($isRetur)
                                <div class="inline-block bg-teal-100 text-teal-700 border border-teal-200 text-[10px] font-bold px-2 py-1 rounded uppercase mb-2"><i class="fas fa-exchange-alt mr-1"></i> Retur Barang</div>
                            @elseif($isRefund)
                                <div class="inline-block bg-red-100 text-red-700 border border-red-200 text-[10px] font-bold px-2 py-1 rounded uppercase mb-2"><i class="fas fa-undo-alt mr-1"></i> Refund Dana</div>
                            @else
                                <div class="inline-block bg-green-100 text-green-700 border border-green-200 text-[10px] font-bold px-2 py-1 rounded uppercase mb-2"><i class="fas fa-check-circle mr-1"></i> Cair Normal</div>
                            @endif

                            <div class="text-sm font-bold text-gray-800 mt-1">{{ $time->format('d M Y') }}</div>
                            <div class="text-xs text-gray-500"><i class="far fa-clock mr-1"></i> {{ $time->format('H:i') }} WIB</div>
                        </td>

                        <td class="px-4 py-4 align-top">
                            <div class="text-sm font-bold text-blue-600 mb-1">{{ $escrow->invoice_number }}</div>
                            <div class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium bg-gray-100 text-gray-600 border border-gray-200 mb-1">
                                <i class="fas fa-barcode mr-1 text-gray-400"></i> Resi: {{ $escrow->order->shipping_reference ?? '-' }}
                            </div>
                            @if($isRetur)
                                <div class="text-[9px] text-teal-600 font-bold bg-teal-50 px-2 py-1 rounded border border-teal-100 mt-1">Cek resi retur via Pusat Resolusi Penjual</div>
                            @endif
                        </td>

                        <td class="px-4 py-4 align-top">
                            @if($isRefund || $isRetur)
                                <div class="text-sm font-bold text-gray-800"><i class="fas fa-user text-red-400 mr-1.5"></i>{{ $escrow->buyer->nama_lengkap ?? 'Pembeli' }}</div>
                                <div class="text-xs {{ $isRetur ? 'text-teal-600' : 'text-red-500' }} mt-1 font-semibold">{{ $isRetur ? 'Mengembalikan barang ke penjual' : 'Dikembalikan ke Saldo Pembeli' }}</div>
                            @else
                                <div class="text-sm font-bold text-gray-800"><i class="fas fa-store text-green-400 mr-1.5"></i>{{ $escrow->store->name ?? 'Toko Terhapus' }}</div>
                                <div class="text-xs text-gray-500 mt-1"><i class="fas fa-user text-gray-400 mr-1.5"></i>{{ $escrow->store->user->nama_lengkap ?? '-' }}</div>
                            @endif
                        </td>

                        <td class="px-4 py-4 align-top">
                            @php
                                $boxClass = 'bg-green-50 border-green-200';
                                $borderClass = 'border-green-200 text-green-700';
                                $label = 'Cair ke Penjual:';

                                if($isRefund) {
                                    $boxClass = 'bg-red-50 border-red-200';
                                    $borderClass = 'border-red-200 text-red-700';
                                    $label = 'Refund Bersih:';
                                } elseif($isRetur) {
                                    $boxClass = 'bg-teal-50 border-teal-200 opacity-80'; // Agak redup karena uangnya tertahan / dibatalkan
                                    $borderClass = 'border-teal-200 text-teal-700';
                                    $label = 'Nilai Barang:';
                                }
                            @endphp

                            <div class="border rounded p-2 text-xs w-full {{ $boxClass }}">
                                <div class="flex justify-between text-gray-600 mb-1">
                                    <span>Total Trx:</span>
                                    <span>Rp {{ number_format($escrow->nominal_ditahan, 0, ',', '.') }}</span>
                                </div>
                                @if(!$isRetur)
                                    <div class="flex justify-between text-gray-500 mb-1">
                                        <span>Ongkir (Sancaka):</span>
                                        <span>- Rp {{ number_format($escrow->nominal_ongkir, 0, ',', '.') }}</span>
                                    </div>
                                @endif
                                <div class="flex justify-between border-t {{ $borderClass }} pt-1 mt-1 font-bold">
                                    <span>{{ $label }}</span>
                                    <span>Rp {{ number_format($isRetur ? $escrow->nominal_ditahan : $danaBersih, 0, ',', '.') }}</span>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                            <i class="fas fa-money-bill-wave text-4xl mb-3 text-gray-300"></i>
                            <p class="text-lg font-bold text-gray-600">Belum ada riwayat pencairan.</p>
                            <p class="text-sm mt-1 text-gray-400">Data akan muncul setelah ada proses penyelesaian Escrow.</p>
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
