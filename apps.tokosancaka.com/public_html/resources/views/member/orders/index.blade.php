@extends('layouts.member')

@section('title', 'Riwayat Pesanan')

@section('content')
<div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
    <div class="p-6 border-b border-slate-100">
        <h2 class="text-lg font-bold text-slate-800">Semua Pesanan</h2>
        <p class="text-xs text-slate-500">Daftar transaksi yang pernah Anda lakukan.</p>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm text-slate-600">
            <thead class="bg-slate-50 text-xs uppercase font-bold text-slate-500">
                <tr>
                    <th class="px-6 py-4">Order ID</th>
                    <th class="px-6 py-4">Tanggal</th>
                    <th class="px-6 py-4 text-right">Total</th>
                    <th class="px-6 py-4 text-center">Status</th>
                    <th class="px-6 py-4 text-center">Opsi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($orders as $order)
                <tr class="hover:bg-slate-50 transition">
                    <td class="px-6 py-4 font-bold text-blue-600">
                        {{ $order->order_number }}
                    </td>
                    <td class="px-6 py-4">
                        {{ $order->created_at->format('d M Y') }}
                        <div class="text-[10px] text-slate-400">{{ $order->created_at->format('H:i') }}</div>
                    </td>
                    <td class="px-6 py-4 text-right font-bold text-slate-800">
                        Rp {{ number_format($order->final_price, 0, ',', '.') }}
                    </td>
                    <td class="px-6 py-4 text-center">
                        @php
                        $statusClass = match($order->status) {
                            'completed' => 'bg-emerald-100 text-emerald-700',
                            'processing' => 'bg-blue-100 text-blue-700',
                            'pending' => 'bg-amber-100 text-amber-700',
                            'cancelled' => 'bg-red-100 text-red-700',
                            default => 'bg-slate-100 text-slate-600'
                        };
                        @endphp
                        <span class="px-2 py-1 rounded-full text-[10px] font-bold uppercase {{ $statusClass }}">
                            {{ $order->status }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-center">
                        {{-- Tombol Detail (Opsional, jika halaman detail dibuat) --}}
                         <button disabled class="text-xs bg-slate-100 text-slate-400 px-3 py-1.5 rounded cursor-not-allowed">
                            Detail
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-6 py-12 text-center text-slate-400">
                        <i class="fas fa-box-open text-4xl mb-3 opacity-30"></i>
                        <p>Belum ada riwayat pesanan.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="p-4">
        {{ $orders->links() }}
    </div>
</div>
@endsection