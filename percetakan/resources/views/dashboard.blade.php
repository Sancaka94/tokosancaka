<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Monitoring - Sancaka POS</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <style>
        [x-cloak] { display: none !important; }
        .glass-card {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
    </style>
</head>
<body class="bg-slate-50 font-sans" x-data="{ sidebarOpen: false }">

    <div class="flex h-screen overflow-hidden">
        @include('layouts.partials.sidebar')

        <div class="flex-1 flex flex-col overflow-y-auto">
            @include('layouts.partials.header')

            <main class="p-6 space-y-8">
                
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                    <div>
                        <h1 class="text-3xl font-black text-slate-800 tracking-tighter italic uppercase">Ringkasan Operasional</h1>
                        <p class="text-slate-500 font-medium">Data transaksi per hari ini, {{ $hariIni }}</p>
                    </div>
                    <div class="flex gap-2">
                        <a href="{{ route('orders.create') }}" class="px-6 py-3 bg-indigo-600 text-white rounded-2xl font-bold shadow-lg shadow-indigo-100 hover:scale-105 transition-all">
                            ➕ Transaksi Baru
                        </a>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    
                    <div class="bg-indigo-600 rounded-[35px] p-6 text-white shadow-2xl shadow-indigo-200 relative overflow-hidden group">
                        <div class="absolute -right-4 -top-4 text-white/10 text-8xl transition-transform group-hover:scale-110">💰</div>
                        <p class="text-indigo-100 text-[10px] font-black uppercase tracking-[0.2em] mb-2">Total Omzet (Paid)</p>
                        <h2 class="text-2xl font-black italic tracking-tight">Rp {{ number_format($totalOmzet, 0, ',', '.') }}</h2>
                        <div class="mt-4 flex items-center gap-2">
                            <span class="px-2 py-1 bg-white/20 rounded-lg text-[10px] font-bold tracking-wider uppercase italic">Real-time Data</span>
                        </div>
                    </div>

                    <div class="bg-white rounded-[35px] p-6 border border-slate-100 shadow-sm relative overflow-hidden group">
                        <div class="absolute -right-4 -top-4 text-slate-50 text-8xl group-hover:text-indigo-50 transition-colors">📦</div>
                        <p class="text-slate-400 text-[10px] font-black uppercase tracking-[0.2em] mb-2">Item Terjual</p>
                        <h2 class="text-2xl font-black italic text-slate-800">{{ number_format($totalTerjual) }} <span class="text-xs text-slate-400 uppercase tracking-widest ml-1 italic">Unit</span></h2>
                        <div class="mt-4 flex items-center gap-2">
                            <span class="px-2 py-1 bg-slate-100 rounded-lg text-[10px] font-bold text-slate-500 uppercase italic">Produk POS</span>
                        </div>
                    </div>

                    <div class="bg-white rounded-[35px] p-6 border border-slate-100 shadow-sm relative overflow-hidden group">
                        <div class="absolute -right-4 -top-4 text-slate-50 text-8xl">👥</div>
                        <p class="text-slate-400 text-[10px] font-black uppercase tracking-[0.2em] mb-2">Pelanggan Unik</p>
                        <h2 class="text-2xl font-black italic text-slate-800">{{ number_format($totalPelanggan) }} <span class="text-xs text-slate-400 uppercase tracking-widest ml-1 italic">Orang</span></h2>
                        <div class="mt-4 flex items-center gap-2">
                            <span class="px-2 py-1 bg-emerald-50 text-emerald-600 rounded-lg text-[10px] font-bold uppercase italic">Loyalitas Tinggi</span>
                        </div>
                    </div>

                    <div class="bg-slate-900 rounded-[35px] p-6 text-white shadow-xl relative overflow-hidden group">
                        <div class="absolute -right-4 -top-4 text-white/5 text-8xl">🛡️</div>
                        <p class="text-slate-500 text-[10px] font-black uppercase tracking-[0.2em] mb-2">User / Staff</p>
                        <h2 class="text-2xl font-black italic">{{ number_format($totalUser) }} <span class="text-xs text-slate-500 uppercase tracking-widest ml-1 italic">Aktif</span></h2>
                        <div class="mt-4 flex items-center gap-2">
                            <span class="px-2 py-1 bg-white/10 text-white rounded-lg text-[10px] font-bold uppercase italic">Otoritas Admin</span>
                        </div>
                    </div>

                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    
                    <div class="lg:col-span-2 bg-white rounded-[40px] shadow-sm border border-slate-100 overflow-hidden">
                        <div class="p-8 border-b border-slate-50 flex justify-between items-center">
                            <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest italic">Aktivitas Transaksi Terbaru</h3>
                            <span class="text-[10px] font-bold text-indigo-600 uppercase italic">5 Data Terakhir</span>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="bg-slate-50/50">
                                        <th class="px-8 py-4 text-[10px] font-black text-slate-400 uppercase italic tracking-widest">Order ID</th>
                                        <th class="px-8 py-4 text-[10px] font-black text-slate-400 uppercase italic tracking-widest">Pelanggan</th>
                                        <th class="px-8 py-4 text-[10px] font-black text-slate-400 uppercase italic tracking-widest">Total</th>
                                        <th class="px-8 py-4 text-center text-[10px] font-black text-slate-400 uppercase italic tracking-widest">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-50 text-sm">
                                    @foreach($recentOrders as $order)
                                    <tr class="hover:bg-indigo-50/30 transition-colors">
                                        <td class="px-8 py-5 font-bold text-slate-800">#{{ $order->order_number }}</td>
                                        <td class="px-8 py-5">
                                            <div class="font-bold text-slate-700">{{ $order->customer_name }}</div>
                                            <div class="text-[10px] text-slate-400 font-medium">{{ $order->created_at->diffForHumans() }}</div>
                                        </td>
                                        <td class="px-8 py-5 font-black text-indigo-600 italic">Rp {{ number_format($order->final_price, 0, ',', '.') }}</td>
                                        <td class="px-8 py-5 text-center">
                                            <span class="px-3 py-1 text-[10px] font-black uppercase rounded-full {{ $order->payment_status == 'paid' ? 'bg-emerald-100 text-emerald-600' : 'bg-amber-100 text-amber-600' }}">
                                                {{ $order->payment_status }}
                                            </span>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="bg-white rounded-[40px] shadow-sm border border-slate-100 p-8">
                        <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest italic mb-6">Layanan Baru</h3>
                        <div class="space-y-6">
                            @foreach($newProducts as $product)
                            <div class="flex items-center justify-between group">
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 bg-indigo-50 rounded-xl flex items-center justify-center text-indigo-600 group-hover:bg-indigo-600 group-hover:text-white transition-all">
                                        🖨️
                                    </div>
                                    <div>
                                        <p class="text-sm font-bold text-slate-700">{{ $product->name }}</p>
                                        <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">{{ $product->unit }}</p>
                                    </div>
                                </div>
                                <div class="text-right font-black text-slate-800 text-sm italic group-hover:text-indigo-600">
                                    Rp {{ number_format($product->base_price, 0, ',', '.') }}
                                </div>
                            </div>
                            @endforeach
                        </div>
                        <a href="{{ route('products.index') }}" class="block mt-10 text-center py-4 border-2 border-dashed border-slate-100 rounded-2xl text-xs font-bold text-slate-400 hover:border-indigo-300 hover:text-indigo-500 transition-all">
                            Lihat Semua Layanan
                        </a>
                    </div>

                </div>
            </main>

            @include('layouts.partials.footer')
        </div>
    </div>

</body>
</html>