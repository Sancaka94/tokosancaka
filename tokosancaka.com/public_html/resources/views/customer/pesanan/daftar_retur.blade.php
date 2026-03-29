@extends('layouts.customer')

@section('title', 'Daftar Paket Retur')

@section('content')
<div class="bg-gray-50 min-h-screen py-8">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Pengembalian Barang (Retur)</h1>
            <p class="mt-2 text-sm text-gray-600">Pantau resi dan status paket yang Anda kembalikan ke penjual.</p>
        </div>

        <div class="space-y-6">
            @php
                // Ambil data retur user ini langsung di view (atau bisa diover dari controller)
                $returOrders = \App\Models\ReturnOrder::with(['store', 'order.items.product'])
                                ->where('buyer_id', Auth::user()->id_pengguna ?? Auth::id())
                                ->latest()
                                ->paginate(10);
            @endphp

            @forelse($returOrders as $retur)
                <div class="bg-white rounded-xl shadow-sm border border-teal-100 overflow-hidden">
                    <div class="px-6 py-4 bg-teal-50 border-b border-teal-100 flex justify-between items-center">
                        <div>
                            <span class="px-3 py-1 bg-teal-600 text-white text-[10px] font-bold rounded-full uppercase tracking-wider">Sedang Dikembalikan</span>
                            <span class="ml-2 text-sm font-bold text-teal-800">{{ $retur->invoice_number }}</span>
                        </div>
                        <div class="text-xs text-teal-600 font-medium">{{ $retur->created_at->format('d M Y') }}</div>
                    </div>

                    <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center text-gray-500"><i class="fas fa-store"></i></div>
                                <div>
                                    <p class="text-xs text-gray-500 uppercase tracking-wider">Dikembalikan Ke Toko</p>
                                    <p class="text-sm font-bold text-gray-800">{{ $retur->store->name ?? 'Toko' }}</p>
                                </div>
                            </div>

                            <div class="bg-gray-50 p-3 rounded-lg border border-gray-100">
                                @foreach($retur->order->items->take(2) as $item)
                                    <p class="text-xs font-semibold text-gray-700 truncate"><i class="fas fa-box text-gray-400 mr-2"></i> {{ $item->product->name ?? 'Barang' }} ({{ $item->quantity }}x)</p>
                                @endforeach
                            </div>
                        </div>

                        <div class="border-t md:border-t-0 md:border-l border-gray-100 md:pl-6 space-y-4">
                            <div class="bg-blue-50 border border-blue-100 p-3 rounded-lg flex justify-between items-center">
                                <div>
                                    <p class="text-[10px] text-blue-500 font-bold uppercase tracking-wider">Resi Retur Baru ({{ $retur->courier }})</p>
                                    <p class="text-sm font-mono font-bold text-blue-700 mt-0.5">{{ $retur->new_resi }}</p>
                                </div>
                                <a href="{{ route('tracking.index', ['resi' => $retur->new_resi]) }}" class="bg-blue-600 text-white text-[10px] px-3 py-1.5 rounded hover:bg-blue-700 transition"><i class="fas fa-search mr-1"></i> Lacak</a>
                            </div>

                            <div class="flex justify-between items-center text-xs">
                                <span class="text-gray-500">Biaya Ongkir Retur:</span>
                                <span class="font-bold text-gray-800">Rp {{ number_format($retur->shipping_cost, 0, ',', '.') }}</span>
                            </div>
                            <div class="flex justify-between items-center text-xs">
                                <span class="text-gray-500">Metode Pembayaran:</span>
                                <span class="font-bold uppercase {{ $retur->payment_method == 'saldo' ? 'text-green-600' : 'text-blue-600' }}">{{ $retur->payment_method }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center py-16 bg-white rounded-xl border border-gray-200">
                    <i class="fas fa-box-open text-4xl text-gray-300 mb-3"></i>
                    <h3 class="text-lg font-bold text-gray-600">Belum Ada Retur</h3>
                    <p class="text-sm text-gray-400">Anda belum memiliki riwayat pengembalian barang.</p>
                </div>
            @endforelse

            <div class="mt-4">{{ $returOrders->links() }}</div>
        </div>
    </div>
</div>
@endsection
