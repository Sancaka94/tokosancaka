@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6 max-w-7xl">
    
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-black text-slate-800">Riwayat Pesanan</h1>
            <p class="text-sm text-slate-500">Daftar semua transaksi yang masuk.</p>
        </div>
        <div class="flex gap-2">
            {{-- Tombol Hapus Masal (Muncul otomatis via JS) --}}
            <button id="btn-delete-selected" class="hidden bg-rose-600 text-white px-4 py-2 rounded-lg font-bold text-sm hover:bg-rose-700 transition flex items-center gap-2 shadow-lg shadow-rose-200">
                <i class="fas fa-trash-alt"></i> Hapus Terpilih (<span id="count-selected">0</span>)
            </button>

            <a href="{{ route('orders.create') }}" class="bg-emerald-600 text-white px-4 py-2 rounded-lg font-bold text-sm hover:bg-emerald-700 transition flex items-center gap-2 shadow-lg shadow-emerald-200">
                <i class="fas fa-plus"></i> Pesanan Baru
            </a>
        </div>
    </div>

    {{-- Form Pembungkus Bulk Action --}}
    <form id="form-bulk-delete" action="{{ route('orders.bulkDestroy') }}" method="POST">
        @csrf
        @method('DELETE')
        
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-slate-50 text-slate-500 font-bold uppercase text-[10px]">
                        <tr>
                            <th class="px-6 py-4 w-[5%]">
                                <input type="checkbox" id="select-all" class="w-4 h-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 cursor-pointer">
                            </th>
                            <th class="px-6 py-4 w-[15%]">Transaksi</th>
                            <th class="px-6 py-4 w-[25%]">Pelanggan & Alamat</th>
                            <th class="px-6 py-4 w-[20%]">Ekspedisi & Ongkir</th>
                            <th class="px-6 py-4 w-[15%] text-right">Total & Bayar</th>
                            <th class="px-6 py-4 w-[10%] text-center">Status</th>
                            <th class="px-6 py-4 w-[10%] text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($orders as $order)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-6 py-4">
                                <input type="checkbox" name="ids[]" value="{{ $order->id }}" class="order-checkbox w-4 h-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 cursor-pointer">
                            </td>
                            
                            {{-- KOLOM 1: TRANSAKSI --}}
                            <td class="px-6 py-4 align-top">
                                <div class="flex flex-col gap-1">
                                    <span class="font-bold text-slate-800 text-xs">{{ $order->order_number }}</span>
                                    <span class="text-[10px] text-slate-400">{{ $order->created_at->translatedFormat('d M Y, H:i') }}</span>
                                    @if($order->shipping_ref)
                                        <span class="text-[9px] font-mono text-blue-600 bg-blue-50 px-1 rounded border border-blue-100 w-fit">Resi: {{ $order->shipping_ref }}</span>
                                    @endif
                                </div>
                            </td>

                            {{-- KOLOM 2: PELANGGAN --}}
                            <td class="px-6 py-4 align-top">
                                <div class="flex flex-col gap-1">
                                    <div class="font-bold text-slate-700 text-sm">{{ $order->customer_name }}</div>
                                    <div class="text-xs text-slate-500"><i class="fab fa-whatsapp text-green-500 mr-1"></i>{{ $order->customer_phone }}</div>
                                    @if($order->destination_address)
                                        <div class="mt-1 text-[10px] text-slate-500 leading-tight bg-slate-50 p-1 rounded border border-slate-100 line-clamp-2">
                                            {{ Str::limit($order->destination_address, 70) }}
                                        </div>
                                    @endif
                                </div>
                            </td>

                            {{-- KOLOM 3: EKSPEDISI --}}
                            <td class="px-6 py-4 align-top">
                                <div class="text-[10px] font-bold text-slate-700 uppercase">{{ $order->courier_service ?? 'Pickup' }}</div>
                                <div class="text-[10px] font-bold text-emerald-600">+ Rp {{ number_format($order->shipping_cost, 0, ',', '.') }}</div>
                            </td>

                            {{-- KOLOM 4: TOTAL --}}
                            <td class="px-6 py-4 align-top text-right">
                                <div class="font-black text-slate-800 text-sm">Rp {{ number_format($order->final_price, 0, ',', '.') }}</div>
                                <span class="px-1.5 py-0.5 rounded text-[9px] font-bold border {{ $order->payment_status == 'paid' ? 'bg-green-50 text-green-600 border-green-200' : 'bg-red-50 text-red-600 border-red-200' }}">
                                    {{ $order->payment_status == 'paid' ? 'LUNAS' : 'BELUM' }}
                                </span>
                            </td>

                            {{-- KOLOM 5: STATUS --}}
                            <td class="px-6 py-4 align-top text-center">
                                @php
                                    $styles = [
                                        'completed' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
                                        'pending' => 'bg-amber-100 text-amber-700 border-amber-200',
                                        'cancelled' => 'bg-red-100 text-red-700 border-red-200',
                                    ];
                                    $style = $styles[$order->status] ?? 'bg-slate-100 text-slate-600 border-slate-200';
                                @endphp
                                <span class="px-2 py-1 rounded-full text-[9px] font-bold uppercase border {{ $style }}">
                                    {{ $order->status }}
                                </span>
                            </td>

                            {{-- KOLOM 6: AKSI --}}
                            <td class="px-6 py-4 align-top text-center">
                                <a href="{{ route('orders.show', $order->id) }}" class="inline-flex items-center justify-center w-8 h-8 bg-white border border-slate-200 rounded-lg text-slate-500 hover:text-blue-600 hover:border-blue-300 transition">
                                    <i class="fas fa-eye text-xs"></i>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-slate-400 italic">Belum ada data pesanan.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($orders->hasPages())
                <div class="px-6 py-4 border-t border-slate-100 bg-slate-50">
                    {{ $orders->links() }}
                </div>
            @endif
        </div>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const selectAll = document.getElementById('select-all');
        const checkboxes = document.querySelectorAll('.order-checkbox');
        const btnDelete = document.getElementById('btn-delete-selected');
        const countText = document.getElementById('count-selected');

        function toggleDeleteButton() {
            const checkedCount = document.querySelectorAll('.order-checkbox:checked').length;
            countText.innerText = checkedCount;
            if (checkedCount > 0) {
                btnDelete.classList.remove('hidden');
            } else {
                btnDelete.classList.add('hidden');
            }
        }

        selectAll.addEventListener('change', function() {
            checkboxes.forEach(cb => cb.checked = this.checked);
            toggleDeleteButton();
        });

        checkboxes.forEach(cb => {
            cb.addEventListener('change', toggleDeleteButton);
        });

        btnDelete.addEventListener('click', function() {
            if (confirm('Yakin ingin menghapus secara PERMANEN data yang dipilih?')) {
                document.getElementById('form-bulk-delete').submit();
            }
        });
    });
</script>
@endsection