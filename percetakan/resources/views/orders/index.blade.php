@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6 max-w-7xl">
    
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-black text-slate-800">Riwayat Pesanan</h1>
            <p class="text-sm text-slate-500">Daftar semua transaksi yang masuk.</p>
        </div>
        <a href="{{ route('orders.create') }}" class="bg-emerald-600 text-white px-4 py-2 rounded-lg font-bold text-sm hover:bg-emerald-700 transition flex items-center gap-2 shadow-lg shadow-emerald-200">
            <i class="fas fa-plus"></i> Pesanan Baru
        </a>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-slate-50 text-slate-500 font-bold uppercase text-[10px]">
                    <tr>
                        <th class="px-6 py-4">No. Order</th>
                        <th class="px-6 py-4">Pelanggan</th>
                        <th class="px-6 py-4">Total</th>
                        <th class="px-6 py-4 text-center">Status</th>
                        <th class="px-6 py-4 text-center">Pembayaran</th>
                        <th class="px-6 py-4">Tanggal</th>
                        <th class="px-6 py-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($orders as $order)
                    <tr class="hover:bg-slate-50 transition">
                        <td class="px-6 py-4">
                            <span class="font-bold text-slate-700 block">{{ $order->order_number }}</span>
                            @if($order->shipping_ref)
                                <span class="text-[10px] text-blue-500 bg-blue-50 px-1.5 py-0.5 rounded border border-blue-100 mt-1 inline-block">
                                    {{ $order->shipping_ref }}
                                </span>
                            @endif
                        </td>

                        <td class="px-6 py-4">
                            <div class="font-bold text-slate-700">{{ $order->customer_name }}</div>
                            <div class="text-xs text-slate-400">{{ $order->customer_phone }}</div>
                        </td>

                        <td class="px-6 py-4 font-bold text-slate-800">
                            Rp {{ number_format($order->final_price, 0, ',', '.') }}
                        </td>

                        <td class="px-6 py-4 text-center">
                            @php
                                $statusStyles = [
                                    'completed' => 'bg-emerald-100 text-emerald-700',
                                    'processing' => 'bg-blue-100 text-blue-700',
                                    'pending' => 'bg-amber-100 text-amber-700',
                                    'cancelled' => 'bg-red-100 text-red-700',
                                ];
                                $style = $statusStyles[$order->status] ?? 'bg-slate-100 text-slate-600';
                                
                                $labels = [
                                    'completed' => 'Selesai',
                                    'processing' => 'Diproses',
                                    'pending' => 'Menunggu',
                                    'cancelled' => 'Batal'
                                ];
                                $label = $labels[$order->status] ?? ucfirst($order->status);
                            @endphp
                            <span class="px-3 py-1 rounded-full text-[10px] font-bold uppercase {{ $style }}">
                                {{ $label }}
                            </span>
                        </td>

                        <td class="px-6 py-4 text-center">
                            <span class="px-2 py-1 rounded text-[10px] font-bold border uppercase
                                {{ $order->payment_status == 'paid' ? 'bg-green-50 text-green-600 border-green-200' : 'bg-red-50 text-red-600 border-red-200' }}">
                                {{ $order->payment_status == 'paid' ? 'LUNAS' : 'BELUM' }}
                            </span>
                            <div class="text-[9px] text-slate-400 mt-1 uppercase">{{ $order->payment_method }}</div>
                        </td>

                        <td class="px-6 py-4 text-slate-500">
                            {{ $order->created_at->format('d M Y') }}
                            <span class="text-xs text-slate-400 block">{{ $order->created_at->format('H:i') }}</span>
                        </td>

                        <td class="px-6 py-4 text-center">
                            <a href="{{ route('orders.show', $order->id) }}" class="inline-flex items-center justify-center w-8 h-8 bg-white border border-slate-200 rounded-lg text-slate-500 hover:text-blue-600 hover:border-blue-300 hover:bg-blue-50 transition shadow-sm" title="Lihat Detail">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-slate-400">
                            <div class="flex flex-col items-center">
                                <i class="fas fa-box-open text-4xl mb-3 opacity-30"></i>
                                <p>Belum ada data pesanan.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="px-6 py-4 border-t border-slate-100">
            {{ $orders->links() }}
        </div>
    </div>
</div>
@endsection