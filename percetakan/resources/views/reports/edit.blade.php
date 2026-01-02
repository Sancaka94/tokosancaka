<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Pesanan - Sancaka POS</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 font-sans text-slate-800 flex items-center justify-center h-screen">

    <div class="w-full max-w-md bg-white rounded-3xl shadow-lg border border-slate-100 p-8">
        <h1 class="text-xl font-black text-slate-800 mb-1">Update Status</h1>
        <p class="text-sm text-slate-400 mb-6">Invoice #{{ $order->order_number }}</p>

        <form action="{{ route('reports.update', $order->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="mb-4">
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Pembayaran</label>
                <select name="payment_status" class="w-full rounded-xl border-slate-200 p-3 text-sm font-bold focus:ring-red-500">
                    <option value="unpaid" {{ $order->payment_status == 'unpaid' ? 'selected' : '' }}>Belum Lunas (Unpaid)</option>
                    <option value="paid" {{ $order->payment_status == 'paid' ? 'selected' : '' }}>Lunas (Paid)</option>
                </select>
            </div>

            <div class="mb-4">
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Status Pesanan</label>
                <select name="status" class="w-full rounded-xl border-slate-200 p-3 text-sm font-bold focus:ring-red-500">
                    <option value="pending" {{ $order->status == 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="processing" {{ $order->status == 'processing' ? 'selected' : '' }}>Diproses</option>
                    <option value="completed" {{ $order->status == 'completed' ? 'selected' : '' }}>Selesai</option>
                    <option value="cancelled" {{ $order->status == 'cancelled' ? 'selected' : '' }}>Dibatalkan</option>
                </select>
            </div>

            <div class="mb-6">
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Catatan Admin</label>
                <textarea name="note" rows="3" class="w-full rounded-xl border-slate-200 p-3 text-sm focus:ring-red-500">{{ $order->note }}</textarea>
            </div>

            <div class="flex gap-3">
                <a href="{{ route('reports.index') }}" class="flex-1 py-3 rounded-xl text-center font-bold text-slate-500 hover:bg-slate-100">Batal</a>
                <button type="submit" class="flex-1 bg-red-600 text-white py-3 rounded-xl font-bold shadow-lg shadow-red-200 hover:bg-red-700">Simpan Perubahan</button>
            </div>
        </form>
    </div>

</body>
</html>