@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6 max-w-4xl">

    {{-- HEADER --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-black text-slate-800">Edit Pesanan</h1>
            <p class="text-sm text-slate-500">
                No. Invoice: <span class="font-mono font-bold text-indigo-600">{{ $order->order_number }}</span>
            </p>
        </div>
        <a href="{{ route('orders.index') }}" class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg text-sm font-bold hover:bg-slate-50 transition">
            <i class="fas fa-arrow-left mr-2"></i> Kembali
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- KOLOM KIRI: FORM EDIT --}}
        <div class="lg:col-span-2">
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                <form action="{{ route('orders.update', $order->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    {{-- 1. Status Order --}}
                    <div class="mb-5">
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Status Pesanan</label>
                        <select name="status" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl font-bold text-slate-700 focus:ring-2 focus:ring-indigo-500 focus:bg-white transition cursor-pointer">
                            <option value="pending" {{ $order->status == 'pending' ? 'selected' : '' }}>PENDING (Menunggu)</option>
                            <option value="processing" {{ $order->status == 'processing' ? 'selected' : '' }}>PROCESSING (Diproses)</option>
                            <option value="shipped" {{ $order->status == 'shipped' ? 'selected' : '' }}>SHIPPED (Dikirim)</option>
                            <option value="completed" {{ $order->status == 'completed' ? 'selected' : '' }}>COMPLETED (Selesai)</option>
                            <option value="cancelled" {{ $order->status == 'cancelled' ? 'selected' : '' }}>CANCELLED (Batal)</option>
                        </select>
                    </div>

                    {{-- 2. Status Pembayaran --}}
                    <div class="mb-5">
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Status Pembayaran</label>
                        <select name="payment_status" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl font-bold text-slate-700 focus:ring-2 focus:ring-indigo-500 focus:bg-white transition cursor-pointer">
                            <option value="unpaid" {{ $order->payment_status == 'unpaid' ? 'selected' : '' }}>BELUM BAYAR (Unpaid)</option>
                            <option value="paid" {{ $order->payment_status == 'paid' ? 'selected' : '' }}>LUNAS (Paid)</option>
                        </select>
                    </div>

                    {{-- 3. Nomor Resi --}}
                    <div class="mb-5">
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">
                            Nomor Resi (Opsional)
                        </label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-slate-400">
                                <i class="fas fa-barcode"></i>
                            </span>
                            <input type="text" name="shipping_ref" value="{{ old('shipping_ref', $order->shipping_ref) }}"
                                class="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl font-mono text-sm text-slate-700 focus:ring-2 focus:ring-indigo-500 focus:bg-white transition"
                                placeholder="Input Nomor Resi Kurir...">
                        </div>
                        <p class="text-[10px] text-slate-400 mt-1">Isi jika pesanan dikirim menggunakan ekspedisi manual.</p>
                    </div>

                    {{-- 4. Catatan --}}
                    <div class="mb-6">
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Catatan Tambahan</label>
                        <textarea name="customer_note" rows="3"
                            class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-700 focus:ring-2 focus:ring-indigo-500 focus:bg-white transition resize-none"
                            placeholder="Catatan untuk pesanan ini...">{{ old('customer_note', $order->customer_note) }}</textarea>
                    </div>

                    <hr class="border-slate-100 my-6">

                    <div class="flex justify-end gap-3">
                        <a href="{{ route('orders.index') }}" class="px-6 py-3 rounded-xl font-bold text-slate-500 hover:bg-slate-100 transition">Batal</a>
                        <button type="submit" class="px-8 py-3 bg-indigo-600 text-white rounded-xl font-bold shadow-lg shadow-indigo-200 hover:bg-indigo-700 hover:shadow-indigo-300 transition transform active:scale-95">
                            Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- KOLOM KANAN: INFO RINGKAS --}}
        <div class="lg:col-span-1">
            <div class="bg-slate-50 rounded-2xl border border-slate-200 p-6 space-y-4">
                <h3 class="font-bold text-slate-800 border-b border-slate-200 pb-2">Info Ringkas</h3>

                <div>
                    <label class="text-[10px] uppercase font-bold text-slate-400">Pelanggan</label>
                    <p class="font-bold text-slate-700">{{ $order->customer_name }}</p>
                    <p class="text-xs text-slate-500">{{ $order->customer_phone }}</p>
                </div>

                <div>
                    <label class="text-[10px] uppercase font-bold text-slate-400">Total Harga</label>
                    <p class="font-black text-xl text-emerald-600">Rp {{ number_format($order->final_price, 0, ',', '.') }}</p>
                </div>

                <div>
                    <label class="text-[10px] uppercase font-bold text-slate-400">Kurir</label>
                    <p class="font-bold text-slate-700">{{ $order->courier_service ?? 'Pickup / Ambil Sendiri' }}</p>
                </div>

                <div>
                    <label class="text-[10px] uppercase font-bold text-slate-400">Waktu Order</label>
                    <p class="text-xs text-slate-600 font-medium">
                        {{ $order->created_at->translatedFormat('d F Y, H:i') }}
                    </p>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection
