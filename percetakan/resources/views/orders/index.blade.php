@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6 max-w-7xl">

    {{-- 1. HEADER & ACTIONS --}}
    <div class="flex flex-col xl:flex-row justify-between items-start xl:items-center mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-black text-slate-800">Riwayat Pesanan</h1>
            <p class="text-sm text-slate-500">Kelola dan pantau semua transaksi.</p>
        </div>

        <div class="flex flex-wrap gap-2">
             {{-- Tombol Bulk Delete (Muncul via JS) --}}
             <button id="btn-delete-selected" class="hidden bg-rose-600 text-white px-4 py-2 rounded-lg font-bold text-sm hover:bg-rose-700 transition flex items-center gap-2 shadow-lg shadow-rose-200">
                <i class="fas fa-trash-alt"></i> Hapus (<span id="count-selected">0</span>)
            </button>

            {{-- Create Button --}}
            <a href="{{ route('orders.create') }}" class="bg-indigo-600 text-white px-4 py-2 rounded-lg font-bold text-sm hover:bg-indigo-700 transition flex items-center gap-2 shadow-lg shadow-indigo-200">
                <i class="fas fa-plus"></i> Pesanan Baru
            </a>
        </div>
    </div>

    {{-- 2. SECTION PENCARIAN & FILTER (KEREN) --}}
    <div class="bg-white rounded-2xl p-4 mb-6 shadow-sm border border-slate-200">
        <form action="{{ route('orders.index') }}" method="GET" class="flex flex-col lg:flex-row gap-4 items-end lg:items-center">

            {{-- Search Input --}}
            <div class="w-full lg:w-1/3">
                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1 block">Cari Transaksi</label>
                <div class="relative group">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400 group-focus-within:text-indigo-500 transition">
                        <i class="fas fa-search"></i>
                    </span>
                    <input type="text" name="q" value="{{ request('q') }}" placeholder="No. Order / Nama Pelanggan..."
                        class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold text-slate-700 focus:ring-2 focus:ring-indigo-100 focus:border-indigo-500 transition placeholder-slate-400">
                </div>
            </div>

            {{-- Date Range Filter (Flatpickr) --}}
            <div class="w-full lg:w-1/3">
                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1 block">Periode Tanggal</label>
                <div class="relative group">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400 group-focus-within:text-indigo-500 transition">
                        <i class="far fa-calendar-alt"></i>
                    </span>
                    {{-- Input Khusus Flatpickr --}}
                    <input type="text" id="date_range" name="date_range" value="{{ request('date_range') }}" placeholder="Pilih Rentang Tanggal"
                        class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold text-slate-700 focus:ring-2 focus:ring-indigo-100 focus:border-indigo-500 transition cursor-pointer placeholder-slate-400">
                </div>
            </div>

            {{-- Status Filter --}}
            <div class="w-full lg:w-1/6">
                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1 block">Status Pembayaran</label>
                <div class="relative">
                    <select name="status" class="w-full pl-3 pr-8 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold text-slate-700 focus:ring-2 focus:ring-indigo-100 focus:border-indigo-500 transition appearance-none cursor-pointer">
                        <option value="">Semua</option>
                        <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>Lunas</option>
                        <option value="unpaid" {{ request('status') == 'unpaid' ? 'selected' : '' }}>Belum Bayar</option>
                    </select>
                    <i class="fas fa-chevron-down absolute right-3 top-3.5 text-xs text-slate-400 pointer-events-none"></i>
                </div>
            </div>

            {{-- Action Buttons --}}
            <div class="flex gap-2 w-full lg:w-auto">
                <button type="submit" class="px-5 py-2.5 bg-slate-800 text-white rounded-xl font-bold text-sm hover:bg-slate-900 transition shadow-lg shadow-slate-200">
                    Filter
                </button>
                @if(request()->has('q') || request()->has('date_range') || request()->has('status'))
                    <a href="{{ route('orders.index') }}" class="px-4 py-2.5 bg-white text-rose-500 border border-rose-200 rounded-xl font-bold text-sm hover:bg-rose-50 transition" title="Reset Filter">
                        <i class="fas fa-times"></i>
                    </a>
                @endif
            </div>
        </form>
    </div>

    {{-- 3. TABEL DATA (Di dalam Form Bulk Delete) --}}
    <form id="form-bulk-delete" action="{{ route('orders.bulkDestroy') }}" method="POST">
        @csrf
        @method('DELETE')

        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            {{-- Table Content --}}
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-slate-50 text-slate-500 font-bold uppercase text-[10px]">
                        <tr>
                            <th class="px-6 py-4 w-[5%]">
                                <input type="checkbox" id="select-all" class="w-4 h-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer">
                            </th>
                            <th class="px-6 py-4 w-[15%]">Transaksi</th>
                            <th class="px-6 py-4 w-[20%]">Pelanggan</th>
                            <th class="px-6 py-4 w-[20%]">Ekspedisi</th>
                            <th class="px-6 py-4 w-[15%] text-right">Total</th>
                            <th class="px-6 py-4 w-[10%] text-center">Status</th>
                            <th class="px-6 py-4 w-[15%] text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        {{-- (Looping Data Tetap Sama Seperti Sebelumnya) --}}
                        @forelse($orders as $order)
                             {{-- ... (Gunakan kode tr/td dari jawaban sebelumnya) ... --}}
                             @include('orders.partials.row', ['order' => $order]) {{-- Atau paste kode TR disini --}}
                        @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-slate-400 italic">
                                <div class="flex flex-col items-center gap-2">
                                    <i class="fas fa-search text-3xl opacity-20"></i>
                                    <span>Tidak ada pesanan ditemukan.</span>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination dengan Append Filter --}}
            @if($orders->hasPages())
                <div class="px-6 py-4 border-t border-slate-100 bg-slate-50">
                    {{ $orders->appends(request()->query())->links() }}
                </div>
            @endif
        </div>
    </form>
</div>

{{-- Hidden Form Single Delete --}}
<form id="single-delete-form" action="" method="POST" class="hidden">
    @csrf @method('DELETE')
</form>

{{-- SCRIPTS --}}
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://npmcdn.com/flatpickr/dist/l10n/id.js"></script> {{-- Bahasa Indonesia --}}

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // 1. Inisialisasi Flatpickr (Kalender Keren)
        flatpickr("#date_range", {
            mode: "range",             // Mode Rentang Tanggal
            dateFormat: "Y-m-d",       // Format yang dikirim ke server
            altInput: true,            // Tampilkan format berbeda ke user
            altFormat: "d M Y",        // Format User (Contoh: 17 Jan 2026)
            locale: "id",              // Bahasa Indonesia
            allowInput: true,
            theme: "airbnb"            // Tema (sesuai css yang di load)
        });

        // 2. Logic Bulk Delete (Tetap sama)
        const selectAll = document.getElementById('select-all');
        const checkboxes = document.querySelectorAll('.order-checkbox');
        const btnDelete = document.getElementById('btn-delete-selected');
        const countText = document.getElementById('count-selected');

        function toggleDeleteButton() {
            const checkedCount = document.querySelectorAll('.order-checkbox:checked').length;
            countText.innerText = checkedCount;
            btnDelete.classList.toggle('hidden', checkedCount === 0);
        }

        if(selectAll) {
            selectAll.addEventListener('change', function() {
                checkboxes.forEach(cb => cb.checked = this.checked);
                toggleDeleteButton();
            });
        }

        checkboxes.forEach(cb => {
            cb.addEventListener('change', toggleDeleteButton);
        });

        if(btnDelete) {
            btnDelete.addEventListener('click', function(e) {
                e.preventDefault();
                if (confirm('Hapus ' + countText.innerText + ' data terpilih?')) {
                    document.getElementById('form-bulk-delete').submit();
                }
            });
        }
    });

    // Single Delete Logic
    function confirmDelete(url) {
        if (confirm('Hapus data ini permanen?')) {
            const form = document.getElementById('single-delete-form');
            form.action = url;
            form.submit();
        }
    }
</script>
@endsection
