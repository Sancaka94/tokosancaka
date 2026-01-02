@extends('layouts.app') @section('title', 'Ringkasan Operasional')

@section('content')
    
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
        <div>
            <h1 class="text-2xl font-black text-slate-800 tracking-tight">Ringkasan Operasional</h1>
            <p class="text-slate-500 font-medium text-sm">Data transaksi per hari ini, {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}</p>
        </div>
        <div>
            <a href="{{ route('orders.create') }}" class="flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-bold shadow-lg shadow-indigo-200 transition-all transform hover:-translate-y-1">
                <i class="fas fa-plus"></i>
                <span>Transaksi Baru</span>
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 relative overflow-hidden group hover:border-indigo-100 transition-colors">
            <div class="absolute right-0 top-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                <i class="fas fa-wallet text-6xl text-indigo-600"></i>
            </div>
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Total Omzet</p>
            <h3 class="text-2xl font-black text-slate-800 mt-1">Rp {{ number_format($totalOmzet ?? 0, 0, ',', '.') }}</h3>
            <p class="text-xs text-emerald-500 font-bold mt-2 flex items-center gap-1">
                <i class="fas fa-arrow-up"></i> Realtime
            </p>
        </div>

        <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 relative overflow-hidden group hover:border-emerald-100 transition-colors">
            <div class="absolute right-0 top-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                <i class="fas fa-box text-6xl text-emerald-600"></i>
            </div>
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Item Terjual</p>
            <h3 class="text-2xl font-black text-slate-800 mt-1">{{ number_format($totalTerjual ?? 0) }} <span class="text-sm text-slate-400 font-normal">Unit</span></h3>
        </div>

        </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
            <div class="p-6 border-b border-slate-50 flex justify-between items-center">
                <h3 class="font-bold text-slate-800">Transaksi Terbaru</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-slate-50 text-slate-500 uppercase text-[10px] font-bold tracking-wider">
                        <tr>
                            <th class="px-6 py-3">Order ID</th>
                            <th class="px-6 py-3">Pelanggan</th>
                            <th class="px-6 py-3 text-right">Total</th>
                            <th class="px-6 py-3 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @foreach($recentOrders ?? [] as $order)
                        <tr class="hover:bg-slate-50/50 transition">
                            <td class="px-6 py-4 font-bold text-indigo-600">#{{ $order->order_number }}</td>
                            <td class="px-6 py-4 font-medium text-slate-700">{{ $order->customer_name }}</td>
                            <td class="px-6 py-4 text-right font-bold">Rp {{ number_format($order->final_price, 0, ',', '.') }}</td>
                            <td class="px-6 py-4 text-center">
                                <span class="px-2 py-1 rounded text-[10px] font-bold uppercase {{ $order->payment_status == 'paid' ? 'bg-emerald-100 text-emerald-600' : 'bg-amber-100 text-amber-600' }}">
                                    {{ $order->payment_status }}
                                </span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
            <h3 class="font-bold text-slate-800 mb-4">Layanan Populer</h3>
            <div class="space-y-4">
                @foreach($newProducts ?? [] as $product)
                <div class="flex items-center gap-3 p-3 rounded-xl hover:bg-slate-50 transition border border-transparent hover:border-slate-100">
                    <div class="w-10 h-10 rounded-lg bg-indigo-50 flex items-center justify-center text-indigo-600">
                        <i class="fas fa-print"></i>
                    </div>
                    <div class="flex-1">
                        <h4 class="text-sm font-bold text-slate-700">{{ $product->name }}</h4>
                        <p class="text-[10px] text-slate-400 font-bold uppercase">{{ $product->unit }}</p>
                    </div>
                    <div class="text-sm font-black text-slate-800">
                        {{ number_format($product->base_price, 0, ',', '.') }}
                    </div>
                </div>
                @endforeach
            </div>
        </div>

    </div>

@endsection