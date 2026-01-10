@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6 max-w-7xl">
    
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-black text-slate-800">Riwayat Pesanan</h1>
            <p class="text-sm text-slate-500">Daftar semua transaksi yang masuk.</p>
        </div>
        <div class="flex gap-2">
            <button id="btn-delete-selected" class="hidden bg-red-600 text-white px-4 py-2 rounded-lg font-bold text-sm hover:bg-red-700 transition flex items-center gap-2 shadow-lg shadow-red-200">
                <i class="fas fa-trash"></i> Hapus Terpilih (<span id="count-selected">0</span>)
            </button>
            
            <a href="{{ route('orders.create') }}" class="bg-emerald-600 text-white px-4 py-2 rounded-lg font-bold text-sm hover:bg-emerald-700 transition flex items-center gap-2 shadow-lg shadow-emerald-200">
                <i class="fas fa-plus"></i> Pesanan Baru
            </a>
        </div>
    </div>

    <form id="form-bulk-delete" action="{{ route('orders.bulkDestroy') }}" method="POST">
        @csrf
        @method('DELETE')
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-slate-50 text-slate-500 font-bold uppercase text-[10px]">
                        <tr>
                            <th class="px-6 py-4 w-[50px]">
                                <input type="checkbox" id="select-all" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                            </th>
                            <th class="px-6 py-4 w-[15%]">Transaksi</th>
                            <th class="px-6 py-4 w-[25%]">Pelanggan & Alamat</th>
                            <th class="px-6 py-4 w-[20%]">Ekspedisi & Ongkir</th>
                            <th class="px-6 py-4 w-[15%] text-right">Total</th>
                            <th class="px-6 py-4 w-[10%] text-center">Status</th>
                            <th class="px-6 py-4 w-[10%] text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($orders as $order)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-6 py-4">
                                <input type="checkbox" name="ids[]" value="{{ $order->id }}" class="order-checkbox rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                            </td>
                            <td class="px-6 py-4 align-top">
                                <div class="flex flex-col gap-1">
                                    <span class="font-bold text-slate-800 text-xs">{{ $order->order_number }}</span>
                                    <span class="text-[10px] text-slate-400">{{ $order->created_at->translatedFormat('d M Y') }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 align-top text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <a href="{{ route('orders.show', $order->id) }}" class="inline-flex items-center justify-center w-8 h-8 bg-white border border-slate-200 rounded-lg text-slate-500 hover:text-blue-600 shadow-sm transition"><i class="fas fa-eye text-xs"></i></a>
                                </div>
                            </td>
                        </tr>
                        @empty
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </form>
</div>

<script>
    // Logika Checkbox All & Tombol Hapus
    const selectAll = document.getElementById('select-all');
    const orderCheckboxes = document.querySelectorAll('.order-checkbox');
    const btnDeleteSelected = document.getElementById('btn-delete-selected');
    const countSelected = document.getElementById('count-selected');
    const formBulkDelete = document.getElementById('form-bulk-delete');

    function updateDeleteButton() {
        const checkedCount = document.querySelectorAll('.order-checkbox:checked').length;
        countSelected.innerText = checkedCount;
        if (checkedCount > 0) {
            btnDeleteSelected.classList.remove('hidden');
        } else {
            btnDeleteSelected.classList.add('hidden');
        }
    }

    selectAll.addEventListener('change', function() {
        orderCheckboxes.forEach(cb => cb.checked = this.checked);
        updateDeleteButton();
    });

    orderCheckboxes.forEach(cb => {
        cb.addEventListener('change', updateDeleteButton);
    });

    btnDeleteSelected.addEventListener('click', function() {
        if (confirm('Apakah Anda yakin ingin menghapus ' + countSelected.innerText + ' pesanan terpilih secara permanen?')) {
            formBulkDelete.submit();
        }
    });
</script>
@endsection