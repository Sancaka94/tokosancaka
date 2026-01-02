@extends('layouts.app')

@section('title', 'Laporan Penjualan')

@section('content')
    <div class="mb-8 flex flex-col sm:flex-row sm:items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-slate-800 uppercase tracking-tight">Laporan Penjualan</h1>
            <p class="text-sm font-medium text-red-600 mt-1">Analisa performa penjualan & keuntungan bersih.</p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('orders.create') }}" class="bg-red-600 text-white px-5 py-2.5 rounded-xl font-bold text-sm shadow-lg shadow-red-200 hover:bg-red-700 transition flex items-center gap-2">
                <i class="fas fa-plus"></i> <span class="hidden sm:inline">Transaksi Baru</span>
            </a>
        </div>
    </div>

    <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-100 mb-8">
        <form action="{{ route('reports.index') }}" method="GET" class="flex flex-col md:flex-row items-end gap-4">
            <div class="flex-1 w-full">
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2">Dari Tanggal</label>
                <input type="date" name="from_date" value="{{ $fromDate }}" class="w-full rounded-xl border-slate-200 bg-slate-50 p-2.5 text-sm focus:ring-2 focus:ring-red-500 focus:border-red-500 transition font-bold text-slate-700">
            </div>
            <div class="flex-1 w-full">
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2">Sampai Tanggal</label>
                <input type="date" name="to_date" value="{{ $toDate }}" class="w-full rounded-xl border-slate-200 bg-slate-50 p-2.5 text-sm focus:ring-2 focus:ring-red-500 focus:border-red-500 transition font-bold text-slate-700">
            </div>
            
            <div class="flex gap-2 w-full md:w-auto">
                <button type="submit" class="flex-1 md:flex-none bg-slate-800 text-white px-6 py-2.5 rounded-xl font-bold hover:bg-black transition text-sm flex items-center justify-center gap-2 shadow-lg shadow-slate-200">
                    <i class="fas fa-filter"></i> Filter
                </button>
                
                <a href="{{ route('reports.export', request()->query()) }}" class="flex-1 md:flex-none bg-emerald-600 text-white px-6 py-2.5 rounded-xl font-bold hover:bg-emerald-700 transition text-sm flex items-center justify-center gap-2 shadow-lg shadow-emerald-200" target="_blank">
                    <i class="fas fa-file-csv"></i> Export
                </a>
            </div>
        </form>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-gradient-to-br from-red-500 to-red-600 p-6 rounded-2xl text-white shadow-xl shadow-red-200 relative overflow-hidden group">
            <div class="absolute right-0 top-0 opacity-10 transform translate-x-4 -translate-y-4 group-hover:scale-110 transition-transform">
                <i class="fas fa-coins text-8xl"></i>
            </div>
            <p class="text-red-100 text-[10px] font-bold uppercase tracking-widest mb-1">Total Omzet (Paid)</p>
            <h2 class="text-2xl font-black">Rp {{ number_format($totalOmzet, 0, ',', '.') }}</h2>
        </div>

        <div class="bg-gradient-to-br from-emerald-500 to-emerald-600 p-6 rounded-2xl text-white shadow-xl shadow-emerald-200 relative overflow-hidden group">
            <div class="absolute right-0 top-0 opacity-10 transform translate-x-4 -translate-y-4 group-hover:scale-110 transition-transform">
                <i class="fas fa-chart-line text-8xl"></i>
            </div>
            <p class="text-emerald-100 text-[10px] font-bold uppercase tracking-widest mb-1">Total Profit (Bersih)</p>
            <h2 class="text-2xl font-black">Rp {{ number_format($totalProfit, 0, ',', '.') }}</h2>
        </div>

        <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm relative overflow-hidden">
            <div class="absolute right-4 top-4 text-slate-100">
                <i class="fas fa-receipt text-6xl"></i>
            </div>
            <p class="text-slate-400 text-[10px] font-bold uppercase tracking-widest mb-1">Total Transaksi</p>
            <h2 class="text-2xl font-black text-slate-800">{{ $totalPesanan }} <span class="text-sm font-medium text-slate-400">Trx</span></h2>
        </div>

        <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm border-l-4 border-l-amber-500 relative">
            <p class="text-slate-400 text-[10px] font-bold uppercase tracking-widest mb-1">Piutang (Belum Lunas)</p>
            <h2 class="text-2xl font-black text-amber-500">Rp {{ number_format($piutang, 0, ',', '.') }}</h2>
        </div>
    </div>

    <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left whitespace-nowrap">
                <thead class="bg-slate-50 border-b border-slate-100">
                    <tr>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Waktu</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Invoice</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Pelanggan</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Metode</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-right">Omzet</th>
                        <th class="px-6 py-4 text-[10px] font-black text-emerald-500 uppercase tracking-widest text-right">Profit</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Status</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 text-sm">
                    @forelse($orders as $order)
                    <tr class="transition group {{ $order->status == 'cancelled' ? 'bg-slate-50 opacity-60 grayscale' : 'hover:bg-red-50/10' }}">
                        <td class="px-6 py-4">
                            <div class="font-bold text-slate-700">{{ $order->created_at->format('d M Y') }}</div>
                            <div class="text-[10px] text-slate-400">{{ $order->created_at->format('H:i') }} WIB</div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="font-mono text-[10px] font-bold bg-slate-100 text-slate-600 px-2 py-1 rounded border border-slate-200 select-all">
                                {{ $order->order_number ?? $order->invoice_number }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="font-bold text-slate-700">{{ $order->customer_name }}</div>
                            @if($order->user_id)
                                <span class="text-[9px] bg-blue-50 text-blue-600 px-1.5 py-0.5 rounded font-bold uppercase">Member</span>
                            @else
                                <span class="text-[9px] text-slate-400 uppercase">Guest</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-center">
                            @php
                                $method = strtolower($order->payment_method);
                                $icon = 'fa-money-bill-wave';
                                $bg = 'bg-slate-100 text-slate-600';
                                
                                if($method == 'cash') { $icon = 'fa-money-bill-wave'; $bg = 'bg-green-50 text-green-600 border border-green-200'; }
                                elseif($method == 'saldo') { $icon = 'fa-wallet'; $bg = 'bg-blue-50 text-blue-600 border border-blue-200'; }
                                elseif($method == 'tripay') { $icon = 'fa-qrcode'; $bg = 'bg-purple-50 text-purple-600 border border-purple-200'; }
                                elseif($method == 'doku') { $icon = 'fa-credit-card'; $bg = 'bg-red-50 text-red-600 border border-red-200'; }
                            @endphp
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-[10px] font-bold uppercase rounded-lg {{ $bg }}">
                                <i class="fas {{ $icon }}"></i> {{ $order->payment_method }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="font-black text-slate-800 {{ $order->status == 'cancelled' ? 'line-through decoration-red-500' : '' }}">
                                Rp {{ number_format($order->final_price, 0, ',', '.') }}
                            </div>
                        </td>
                        
                        <td class="px-6 py-4 text-right">
                            @if($order->status == 'cancelled')
                                <span class="text-slate-400 font-bold">-</span>
                            @else
                                @php 
                                    $profitVal = $order->profit; 
                                    $profitColor = $profitVal >= 0 ? 'text-emerald-600' : 'text-red-600';
                                    $profitSign = $profitVal >= 0 ? '+' : '';
                                @endphp
                                <div class="font-black {{ $profitColor }}">
                                    {{ $profitSign }} {{ number_format($profitVal, 0, ',', '.') }}
                                </div>
                            @endif
                        </td>

                        <td class="px-6 py-4 text-center">
                            <div class="flex flex-col gap-1 items-center">
                                @if($order->payment_status == 'paid')
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 text-[9px] font-black uppercase rounded bg-emerald-100 text-emerald-600">
                                        Lunas
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 text-[9px] font-black uppercase rounded bg-amber-100 text-amber-600">
                                        Belum Lunas
                                    </span>
                                @endif

                                @php
                                    $statusColor = 'bg-slate-100 text-slate-500';
                                    if($order->status == 'completed') $statusColor = 'bg-blue-100 text-blue-600';
                                    if($order->status == 'processing') $statusColor = 'bg-indigo-100 text-indigo-600';
                                    if($order->status == 'cancelled') $statusColor = 'bg-red-100 text-red-600';
                                @endphp
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 text-[9px] font-bold uppercase rounded {{ $statusColor }}">
                                    {{ $order->status }}
                                </span>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <a href="{{ route('reports.show', $order->id) }}" class="h-8 w-8 rounded-full bg-white border border-slate-200 text-slate-400 hover:text-blue-600 hover:border-blue-200 hover:bg-blue-50 transition shadow-sm flex items-center justify-center" title="Detail">
                                    <i class="fas fa-eye text-xs"></i>
                                </a>
                                <a href="{{ route('reports.edit', $order->id) }}" class="h-8 w-8 rounded-full bg-white border border-slate-200 text-slate-400 hover:text-amber-500 hover:border-amber-200 hover:bg-amber-50 transition shadow-sm flex items-center justify-center" title="Edit Status">
                                    <i class="fas fa-pencil-alt text-xs"></i>
                                </a>
                                <form action="{{ route('reports.destroy', $order->id) }}" method="POST" onsubmit="return confirm('Hapus pesanan ini?\nStok barang akan dikembalikan (kecuali order sudah Cancelled sebelumnya).');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="h-8 w-8 rounded-full bg-white border border-slate-200 text-slate-400 hover:text-red-600 hover:border-red-200 hover:bg-red-50 transition shadow-sm flex items-center justify-center" title="Hapus">
                                        <i class="fas fa-trash text-xs"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center justify-center text-slate-400">
                                <i class="fas fa-inbox text-4xl mb-3 text-slate-300"></i>
                                <p class="italic">Tidak ada data pesanan pada periode ini.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="px-6 py-4 bg-slate-50 border-t border-slate-100">
            {{ $orders->links() }}
        </div>
    </div>
@endsection