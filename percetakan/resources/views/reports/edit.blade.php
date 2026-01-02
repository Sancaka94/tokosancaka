@extends('layouts.app')

@section('title', 'Edit Pesanan')

@section('content')
    <div class="max-w-xl mx-auto">
        <div class="mb-6">
            <a href="{{ route('reports.index') }}" class="text-slate-500 hover:text-slate-800 text-sm font-bold flex items-center gap-2 transition">
                <i class="fas fa-arrow-left"></i> Batal & Kembali
            </a>
        </div>

        <div class="bg-white rounded-3xl shadow-sm border border-slate-100 p-8">
            <h1 class="text-xl font-black text-slate-800 mb-1">Update Status</h1>
            <p class="text-sm text-slate-400 mb-6">Invoice #{{ $order->order_number }}</p>

            <form action="{{ route('reports.update', $order->id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="mb-4">
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Pembayaran</label>
                    <select name="payment_status" class="w-full rounded-xl border-slate-200 bg-slate-50 p-3 text-sm font-bold focus:ring-red-500">
                        <option value="unpaid" {{ $order->payment_status == 'unpaid' ? 'selected' : '' }}>Belum Lunas (Unpaid)</option>
                        <option value="paid" {{ $order->payment_status == 'paid' ? 'selected' : '' }}>Lunas (Paid)</option>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Status Pesanan</label>
                    <select name="status" class="w-full rounded-xl border-slate-200 bg-slate-50 p-3 text-sm font-bold focus:ring-red-500">
                        <option value="pending" {{ $order->status == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="processing" {{ $order->status == 'processing' ? 'selected' : '' }}>Diproses</option>
                        <option value="completed" {{ $order->status == 'completed' ? 'selected' : '' }}>Selesai</option>
                        <option value="cancelled" {{ $order->status == 'cancelled' ? 'selected' : '' }}>Dibatalkan</option>
                    </select>
                </div>

                <div class="mb-6">
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Catatan Admin</label>
                    <textarea name="note" rows="3" class="w-full rounded-xl border-slate-200 bg-slate-50 p-3 text-sm focus:ring-red-500">{{ $order->note }}</textarea>
                </div>

                <button type="submit" class="w-full bg-red-600 text-white py-3 rounded-xl font-bold shadow-lg shadow-red-200 hover:bg-red-700 transition">
                    Simpan Perubahan
                </button>
            </form>
        </div>
    </div>
@endsection