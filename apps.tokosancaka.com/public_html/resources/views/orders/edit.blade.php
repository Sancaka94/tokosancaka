@extends('layouts.app')

@section('content')
{{-- 1. LOAD CSS FLATPICKR LANGSUNG DISINI --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<link rel="stylesheet" type="text/css" href="https://npmcdn.com/flatpickr/dist/themes/airbnb.css">
<style>
    /* Paksa kalender muncul di paling depan */
    .flatpickr-calendar {
        z-index: 9999 !important;
    }
</style>

@section('content')
<div class="container mx-auto px-4 py-6 max-w-5xl">

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-black text-slate-800">Edit Data Pesanan</h1>
            <p class="text-sm text-slate-500">
                Invoice: <span class="font-mono font-bold text-indigo-600">{{ $order->order_number }}</span>
            </p>
        </div>
        <a href="{{ route('orders.index') }}" class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg text-sm font-bold hover:bg-slate-50 transition">
            <i class="fas fa-arrow-left mr-2"></i> Kembali
        </a>
    </div>

    <form action="{{ route('orders.update', $order->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- KOLOM KIRI (UTAMA) --}}
            <div class="lg:col-span-2 space-y-6">

                {{-- 1. INFORMASI PELANGGAN & TANGGAL --}}
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                    <h3 class="font-bold text-slate-800 border-b border-slate-200 pb-3 mb-4 uppercase text-xs tracking-wider">Data Transaksi</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 mb-1">Nama Pelanggan</label>
                            <input type="text" name="customer_name" value="{{ old('customer_name', $order->customer_name) }}" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg font-bold text-slate-700 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 mb-1">WhatsApp</label>
                            <input type="text" name="customer_phone" value="{{ old('customer_phone', $order->customer_phone) }}" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg font-bold text-slate-700 focus:ring-indigo-500">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-xs font-bold text-slate-500 mb-1">Alamat / Tujuan</label>
                            <textarea name="destination_address" rows="2" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm text-slate-700 resize-none">{{ old('destination_address', $order->destination_address) }}</textarea>
                        </div>

                        {{-- EDIT TANGGAL --}}
                        <div class="md:col-span-2">
                            <label class="block text-xs font-bold text-slate-500 mb-1">Waktu Transaksi (Tanggal Order)</label>
                            <input type="text" id="created_at" name="created_at" value="{{ old('created_at', $order->created_at) }}" class="w-full px-3 py-2 bg-indigo-50 border border-indigo-200 rounded-lg font-bold text-indigo-700 cursor-pointer focus:ring-indigo-500">
                            <p class="text-[10px] text-slate-400 mt-1">*Ubah tanggal jika ingin memundurkan/memajukan waktu pencatatan.</p>
                        </div>
                    </div>
                </div>

                {{-- 2. EDIT ITEM (HARGA & QTY) --}}
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                    <h3 class="font-bold text-slate-800 border-b border-slate-200 pb-3 mb-4 uppercase text-xs tracking-wider">Rincian Produk (Item)</h3>

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left">
                            <thead class="bg-slate-50 text-slate-500 font-bold text-[10px] uppercase">
                                <tr>
                                    <th class="px-3 py-2">Produk</th>
                                    <th class="px-3 py-2 w-24">Qty</th>
                                    <th class="px-3 py-2 w-32">Harga Satuan</th>
                                    <th class="px-3 py-2 w-32 text-right">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach($order->items as $item)
                                <tr>
                                    <td class="px-3 py-3">
                                        <div class="font-bold text-slate-700">{{ $item->product_name }}</div>
                                        <div class="text-xs text-slate-400">{{ $item->product->unit ?? 'pcs' }}</div>
                                        <input type="hidden" name="items[{{ $item->id }}][id]" value="{{ $item->id }}">
                                    </td>
                                    <td class="px-3 py-3">
                                        {{-- Input QTY (Bisa Koma/Decimal) --}}
                                        <input type="number" step="any" name="items[{{ $item->id }}][qty]" value="{{ $item->quantity + 0 }}"
                                            class="w-full px-2 py-1 border border-slate-300 rounded text-center font-bold text-slate-700 focus:ring-indigo-500">
                                    </td>
                                    <td class="px-3 py-3">
                                        {{-- Input Harga Satuan --}}
                                        <input type="number" name="items[{{ $item->id }}][price]" value="{{ $item->price_at_order }}"
                                            class="w-full px-2 py-1 border border-slate-300 rounded text-right font-bold text-slate-700 focus:ring-indigo-500">
                                    </td>
                                    <td class="px-3 py-3 text-right font-bold text-slate-600">
                                        {{ number_format($item->subtotal, 0, ',', '.') }}
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <p class="text-[10px] text-orange-500 mt-2 italic">*Mengubah Qty akan otomatis menyesuaikan stok produk.</p>
                </div>

                {{-- 3. EDIT KEUANGAN --}}
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                    <h3 class="font-bold text-slate-800 border-b border-slate-200 pb-3 mb-4 uppercase text-xs tracking-wider">Rincian Biaya</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 mb-1">Ongkos Kirim</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400">Rp</span>
                                <input type="number" name="shipping_cost" value="{{ old('shipping_cost', $order->shipping_cost) }}" class="w-full pl-8 pr-3 py-2 bg-slate-50 border border-slate-200 rounded-lg font-bold text-slate-700">
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 mb-1">Potongan Diskon</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400">Rp</span>
                                <input type="number" name="discount_amount" value="{{ old('discount_amount', $order->discount_amount) }}" class="w-full pl-8 pr-3 py-2 bg-slate-50 border border-slate-200 rounded-lg font-bold text-slate-700">
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            {{-- KOLOM KANAN (STATUS & SAVE) --}}
            <div class="lg:col-span-1 space-y-6">

                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 sticky top-6">
                    <h3 class="font-bold text-slate-800 border-b border-slate-200 pb-3 mb-4 uppercase text-xs tracking-wider">Status & Aksi</h3>

                    {{-- Status Order --}}
                    <div class="mb-4">
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Status Pesanan</label>
                        <select name="status" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl font-bold text-slate-700 focus:ring-2 focus:ring-indigo-500 transition cursor-pointer">
                            <option value="pending" {{ $order->status == 'pending' ? 'selected' : '' }}>PENDING</option>
                            <option value="processing" {{ $order->status == 'processing' ? 'selected' : '' }}>PROCESSING</option>
                            <option value="shipped" {{ $order->status == 'shipped' ? 'selected' : '' }}>SHIPPED (Dikirim)</option>
                            <option value="completed" {{ $order->status == 'completed' ? 'selected' : '' }}>COMPLETED (Selesai)</option>
                            <option value="cancelled" {{ $order->status == 'cancelled' ? 'selected' : '' }}>CANCELLED (Batal)</option>
                        </select>
                    </div>

                    {{-- Status Pembayaran --}}
                    <div class="mb-4">
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Status Pembayaran</label>
                        <select name="payment_status" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl font-bold text-slate-700 focus:ring-2 focus:ring-indigo-500 transition cursor-pointer">
                            <option value="unpaid" {{ $order->payment_status == 'unpaid' ? 'selected' : '' }}>BELUM BAYAR (Unpaid)</option>
                            <option value="paid" {{ $order->payment_status == 'paid' ? 'selected' : '' }}>LUNAS (Paid)</option>
                        </select>
                    </div>

                    {{-- Catatan --}}
                    <div class="mb-4">
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Catatan</label>
                        <textarea name="customer_note" rows="4" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-700 resize-none">{{ old('customer_note', $order->customer_note) }}</textarea>
                    </div>

                    {{-- Info Total --}}
                    <div class="bg-indigo-50 rounded-xl p-4 mb-4 border border-indigo-100 text-center">
                        <span class="text-xs text-indigo-500 font-bold uppercase">Total Saat Ini</span>
                        <div class="text-2xl font-black text-indigo-700">Rp {{ number_format($order->final_price, 0, ',', '.') }}</div>
                        <span class="text-[10px] text-slate-400">(Akan berubah setelah disimpan)</span>
                    </div>

                    <button type="submit" class="w-full px-4 py-3 bg-indigo-600 text-white rounded-xl font-bold shadow-lg shadow-indigo-200 hover:bg-indigo-700 transition transform active:scale-95">
                        <i class="fas fa-save mr-2"></i> Simpan Perubahan
                    </button>
                </div>

            </div>
        </div>
    </form>
</div>

{{-- Script Flatpickr --}}
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://npmcdn.com/flatpickr/dist/l10n/id.js"></script>
<script>
    flatpickr("#created_at", {
        enableTime: true,
        dateFormat: "Y-m-d H:i:s",
        altInput: true,
        altFormat: "d F Y, H:i",
        locale: "id",
        time_24hr: true
    });
</script>
@endsection
