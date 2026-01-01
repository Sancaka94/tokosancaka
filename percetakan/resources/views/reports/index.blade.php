<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Penjualan - Sancaka POS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-slate-50 font-sans" x-data="{ sidebarOpen: false }">

    <div class="flex h-screen overflow-hidden">
        @include('layouts.partials.sidebar')

        <div class="flex-1 flex flex-col overflow-y-auto">
            @include('layouts.partials.header')

            <main class="p-6">
                <div class="mb-8">
                    <h1 class="text-2xl font-black text-slate-800 italic uppercase italic">LAPORAN PENJUALAN</h1>
                    <p class="text-slate-500 text-sm font-medium text-indigo-600">Pantau performa keuangan Sancaka Group.</p>
                </div>

                <div class="bg-white p-6 rounded-[30px] shadow-sm border border-slate-100 mb-8">
                    <form action="{{ route('reports.index') }}" method="GET" class="flex flex-col md:flex-row items-end gap-4">
                        <div class="flex-1">
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Dari Tanggal</label>
                            <input type="date" name="from_date" value="{{ $fromDate }}" class="w-full rounded-xl border-slate-200 p-3 text-sm focus:ring-indigo-500">
                        </div>
                        <div class="flex-1">
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Sampai Tanggal</label>
                            <input type="date" name="to_date" value="{{ $toDate }}" class="w-full rounded-xl border-slate-200 p-3 text-sm focus:ring-indigo-500">
                        </div>
                        <button type="submit" class="bg-slate-900 text-white px-8 py-3.5 rounded-xl font-bold hover:bg-black transition text-sm">Filter Data</button>
                    </form>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <div class="bg-indigo-600 p-6 rounded-[30px] text-white">
                        <p class="text-indigo-100 text-[10px] font-bold uppercase tracking-widest mb-1">Total Omzet (Lunas)</p>
                        <h2 class="text-2xl font-black italic">Rp {{ number_format($totalOmzet, 0, ',', '.') }}</h2>
                    </div>
                    <div class="bg-white p-6 rounded-[30px] border border-slate-100 shadow-sm">
                        <p class="text-slate-400 text-[10px] font-bold uppercase tracking-widest mb-1">Total Pesanan</p>
                        <h2 class="text-2xl font-black italic text-slate-800">{{ $totalPesanan }} Trx</h2>
                    </div>
                    <div class="bg-white p-6 rounded-[30px] border border-slate-100 shadow-sm border-l-4 border-l-amber-500">
                        <p class="text-slate-400 text-[10px] font-bold uppercase tracking-widest mb-1">Piutang (Belum Lunas)</p>
                        <h2 class="text-2xl font-black italic text-amber-600">Rp {{ number_format($piutang, 0, ',', '.') }}</h2>
                    </div>
                </div>

                <div class="bg-white rounded-[35px] shadow-sm border border-slate-100 overflow-hidden">
                    <table class="w-full text-left">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-8 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest italic">Tanggal</th>
                                <th class="px-8 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest italic">No. Nota</th>
                                <th class="px-8 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest italic">Pelanggan</th>
                                <th class="px-8 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest italic text-right">Total Tagihan</th>
                                <th class="px-8 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest italic text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50 text-sm">
                            @forelse($orders as $order)
                            <tr class="hover:bg-slate-50 transition">
                                <td class="px-8 py-5 text-slate-500">{{ $order->created_at->format('d/m/Y H:i') }}</td>
                                <td class="px-8 py-5 font-bold text-slate-800">#{{ $order->order_number }}</td>
                                <td class="px-8 py-5 font-bold text-slate-600">{{ $order->customer_name }}</td>
                                <td class="px-8 py-5 text-right font-black text-slate-900 italic">Rp {{ number_format($order->final_price, 0, ',', '.') }}</td>
                                <td class="px-8 py-5 text-center">
                                    <span class="px-3 py-1 text-[10px] font-black uppercase rounded-full {{ $order->payment_status == 'paid' ? 'bg-green-100 text-green-600' : 'bg-red-100 text-red-600' }}">
                                        {{ $order->payment_status }}
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="px-8 py-10 text-center text-slate-400 italic">Tidak ada transaksi pada periode ini.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                    <div class="p-6 bg-slate-50 border-t border-slate-100">
                        {{ $orders->links() }}
                    </div>
                </div>
            </main>

            @include('layouts.partials.footer')
        </div>
    </div>
</body>
</html>