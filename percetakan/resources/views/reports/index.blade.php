@extends('layouts.app')

@section('title', 'Laporan Penjualan & Pengiriman')

@section('content')
    <div class="mb-8 flex flex-col sm:flex-row sm:items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-slate-800 uppercase tracking-tight">Laporan Transaksi</h1>
            <p class="text-sm font-medium text-red-600 mt-1">Rekap penjualan, status pembayaran, dan resi pengiriman.</p>
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
            <p class="text-red-100 text-[10px] font-bold uppercase tracking-widest mb-1">Total Omzet (Bruto)</p>
            <h2 class="text-2xl font-black">Rp {{ number_format($totalOmzet, 0, ',', '.') }}</h2>
        </div>

        <div class="bg-gradient-to-br from-emerald-500 to-emerald-600 p-6 rounded-2xl text-white shadow-xl shadow-emerald-200 relative overflow-hidden group">
            <div class="absolute right-0 top-0 opacity-10 transform translate-x-4 -translate-y-4 group-hover:scale-110 transition-transform">
                <i class="fas fa-chart-line text-8xl"></i>
            </div>
            <p class="text-emerald-100 text-[10px] font-bold uppercase tracking-widest mb-1">Profit Bersih</p>
            <h2 class="text-2xl font-black">Rp {{ number_format($totalProfit, 0, ',', '.') }}</h2>
        </div>

        <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm relative overflow-hidden">
            <div class="absolute right-4 top-4 text-slate-100">
                <i class="fas fa-receipt text-6xl"></i>
            </div>
            <p class="text-slate-400 text-[10px] font-bold uppercase tracking-widest mb-1">Total Transaksi</p>
            <h2 class="text-2xl font-black text-slate-800">{{ $totalPesanan }} <span class="text-sm font-medium text-slate-400">Trx</span></h2>
        </div>

        <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm border-l-4 border-l-blue-500 relative">
            <p class="text-slate-400 text-[10px] font-bold uppercase tracking-widest mb-1">Total Ongkos Kirim</p>
            <h2 class="text-2xl font-black text-blue-500">
                @php
                    $totalOngkir = $orders->where('delivery_type', 'shipping')->sum('shipping_cost');
                @endphp
                Rp {{ number_format($totalOngkir, 0, ',', '.') }}
            </h2>
        </div>
    </div>

    <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left whitespace-nowrap">
                <thead class="bg-slate-50 border-b border-slate-100">
                    <tr>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Order Info</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Pelanggan & Tujuan</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Logistik (Resi)</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Pembayaran</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-right">Total Tagihan</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Status</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 text-sm">
                    @forelse($orders as $order)
                    <tr class="transition group {{ $order->status == 'cancelled' ? 'bg-slate-50 opacity-60 grayscale' : 'hover:bg-red-50/10' }}">
                        
                        <td class="px-6 py-4 align-top">
                            <div class="flex flex-col">
                                <span class="font-bold text-slate-700 text-xs">{{ $order->created_at->format('d M Y') }}</span>
                                <span class="text-[10px] text-slate-400 mb-1">{{ $order->created_at->format('H:i') }} WIB</span>
                                <span class="font-mono text-[10px] font-bold bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded border border-slate-200 select-all w-fit">
                                    {{ $order->order_number }}
                                </span>
                                <span class="text-[9px] text-slate-400 mt-1">ID: #{{ $order->id }}</span>
                            </div>
                        </td>

                        <td class="px-6 py-4 align-top">
                            <div class="flex flex-col">
                                <div class="font-bold text-slate-700">{{ $order->customer_name }}</div>
                                <div class="text-[11px] text-slate-500 mb-1">
                                    <i class="fas fa-phone-alt text-[9px] mr-1"></i> {{ $order->customer_phone ?? '-' }}
                                </div>
                                
                                @if(!empty($order->courier_service))
                                    <div class="text-[10px] text-slate-400 leading-tight max-w-[180px] mt-1">
                                        <i class="fas fa-map-marker-alt text-red-400 mr-1"></i>
                                        Ke: Ke: {{ Str::limit($order->destination_address ?? 'Alamat tidak tersedia', 40) }}
                                    </div>
                                    <div class="text-[9px] font-bold text-slate-500 mt-0.5">
                                        Pengirim: Toko Sancaka
                                    </div>
                                @else
                                    <span class="inline-flex items-center gap-1 text-[10px] text-blue-600 bg-blue-50 px-2 py-1 rounded w-fit mt-1">
                                        <i class="fas fa-store"></i> Ambil di Toko
                                    </span>
                                @endif
                            </div>
                        </td>

                        <td class="px-6 py-4 align-top">
                            @if(!empty($order->courier_service))
                                <div class="flex flex-col gap-1">
                                    <div class="font-bold text-slate-700 text-xs uppercase flex items-center gap-1">
                                        <i class="fas fa-truck text-slate-400"></i>
                                        {{ $order->courier_service ?? 'Kurir Manual' }}
                                    </div>
                                    
                                    @if($order->shipping_ref)
                                        <div class="flex items-center gap-1">
                                            <span class="font-mono text-[11px] text-blue-600 font-bold select-all bg-blue-50 px-1.5 py-0.5 rounded border border-blue-100">
                                                {{ $order->shipping_ref }}
                                            </span>
                                            <button onclick="navigator.clipboard.writeText('{{ $order->shipping_ref }}'); alert('Resi disalin!')" class="text-slate-300 hover:text-blue-500" title="Copy Resi">
                                                <i class="far fa-copy"></i>
                                            </button>
                                        </div>
                                    @else
                                        <span class="text-[10px] text-amber-500 italic"><i class="fas fa-clock"></i> Menunggu Resi</span>
                                    @endif

                                    <div class="text-[10px] text-slate-500 mt-1">
                                        Ongkir: <span class="font-bold text-slate-700">Rp {{ number_format($order->shipping_cost, 0, ',', '.') }}</span>
                                    </div>
                                </div>
                            @else
                                <div class="text-center text-slate-300">
                                    <i class="fas fa-minus"></i>
                                </div>
                            @endif
                        </td>

                        <td class="px-6 py-4 align-top text-center">
                            <div class="flex flex-col gap-1 items-center">
                                @php
                                    $method = strtolower($order->payment_method);
                                    $icon = 'fa-money-bill-wave';
                                    $bg = 'bg-slate-100 text-slate-600';
                                    
                                    if($method == 'cash') { $icon = 'fa-money-bill-wave'; $bg = 'bg-green-50 text-green-600 border border-green-200'; }
                                    elseif($method == 'saldo') { $icon = 'fa-wallet'; $bg = 'bg-blue-50 text-blue-600 border border-blue-200'; }
                                    elseif($method == 'tripay') { $icon = 'fa-qrcode'; $bg = 'bg-purple-50 text-purple-600 border border-purple-200'; }
                                    elseif($method == 'doku') { $icon = 'fa-credit-card'; $bg = 'bg-red-50 text-red-600 border border-red-200'; }
                                    elseif($method == 'affiliate_balance') { $icon = 'fa-coins'; $bg = 'bg-amber-50 text-amber-600 border border-amber-200'; }
                                @endphp
                                <span class="inline-flex items-center gap-1.5 px-2 py-1 text-[9px] font-bold uppercase rounded-lg {{ $bg }}">
                                    <i class="fas {{ $icon }}"></i> {{ $order->payment_method }}
                                </span>

                                @if($order->payment_status == 'paid')
                                    <span class="text-[9px] text-emerald-600 font-bold"><i class="fas fa-check-circle"></i> Lunas</span>
                                @else
                                    <span class="text-[9px] text-red-500 font-bold animate-pulse"><i class="fas fa-exclamation-circle"></i> Belum Lunas</span>
                                @endif
                            </div>
                        </td>

                        <td class="px-6 py-4 align-top text-right">
                            <div class="font-black text-slate-800 text-sm {{ $order->status == 'cancelled' ? 'line-through decoration-red-500' : '' }}">
                                Rp {{ number_format($order->final_price, 0, ',', '.') }}
                            </div>
                            <div class="text-[9px] text-slate-400 mt-1 flex flex-col items-end">
                                <span>Produk: {{ number_format($order->final_price - $order->shipping_cost, 0, ',', '.') }}</span>
                                @if($order->discount_amount > 0)
                                    <span class="text-emerald-500">Hemat: -{{ number_format($order->discount_amount, 0, ',', '.') }}</span>
                                @endif
                            </div>
                        </td>

                        <td class="px-6 py-4 align-top text-center">
                            @php
                                $statusColor = 'bg-slate-100 text-slate-500 border-slate-200';
                                $iconStatus = 'fa-circle';
                                if($order->status == 'completed') { $statusColor = 'bg-blue-50 text-blue-600 border-blue-200'; $iconStatus = 'fa-check-double'; }
                                if($order->status == 'processing') { $statusColor = 'bg-amber-50 text-amber-600 border-amber-200'; $iconStatus = 'fa-spinner fa-spin'; }
                                if($order->status == 'pending') { $statusColor = 'bg-slate-100 text-slate-500 border-slate-200'; $iconStatus = 'fa-clock'; }
                                if($order->status == 'cancelled') { $statusColor = 'bg-red-50 text-red-600 border-red-200'; $iconStatus = 'fa-times-circle'; }
                            @endphp
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-[9px] font-bold uppercase rounded-full border {{ $statusColor }}">
                                <i class="fas {{ $iconStatus }}"></i> {{ $order->status }}
                            </span>
                        </td>

                        <td class="px-6 py-4 align-top text-center">
                            <div class="flex items-center justify-center gap-2">
                                <a href="{{ route('reports.show', $order->id) }}" class="h-8 w-8 rounded-full bg-white border border-slate-200 text-slate-400 hover:text-blue-600 hover:border-blue-200 hover:bg-blue-50 transition shadow-sm flex items-center justify-center" title="Detail & Cetak Invoice">
                                    <i class="fas fa-eye text-xs"></i>
                                </a>
                                @if($order->status != 'cancelled' && $order->status != 'completed')
                                    <a href="{{ route('reports.edit', $order->id) }}" class="h-8 w-8 rounded-full bg-white border border-slate-200 text-slate-400 hover:text-amber-500 hover:border-amber-200 hover:bg-amber-50 transition shadow-sm flex items-center justify-center" title="Update Status">
                                        <i class="fas fa-pencil-alt text-xs"></i>
                                    </a>
                                @endif
                                <form action="{{ route('reports.destroy', $order->id) }}" method="POST" onsubmit="return confirm('Hapus pesanan ini?\nData tidak bisa dikembalikan.');">
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
                        <td colspan="7" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center justify-center text-slate-400">
                                <i class="fas fa-inbox text-4xl mb-3 text-slate-300"></i>
                                <p class="italic">Tidak ada data transaksi.</p>
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