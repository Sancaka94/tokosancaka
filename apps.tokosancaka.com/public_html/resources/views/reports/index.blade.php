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

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-indigo-50/50 border-b border-indigo-100">
                    <tr>
                        <th class="px-4 py-4 text-[11px] font-bold text-slate-500 uppercase tracking-wider w-[20%]">Transaksi</th>
                        <th class="px-4 py-4 text-[11px] font-bold text-slate-500 uppercase tracking-wider w-[25%]">Alamat Pengiriman</th>
                        <th class="px-4 py-4 text-[11px] font-bold text-slate-500 uppercase tracking-wider w-[20%]">Ekspedisi & Ongkir</th>
                        <th class="px-4 py-4 text-[11px] font-bold text-slate-500 uppercase tracking-wider w-[15%] text-right">Total & Status</th>
                        <th class="px-4 py-4 text-[11px] font-bold text-slate-500 uppercase tracking-wider w-[10%] text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm">
                    @forelse($orders as $order)
                    <tr class="hover:bg-slate-50 transition duration-150 ease-in-out">
                        
                        <td class="px-4 py-4 align-top">
                            <div class="flex flex-col gap-1.5">
                                <div>
                                    @php
                                        $payBg = 'bg-slate-100 text-slate-600';
                                        if($order->payment_method == 'cod') $payBg = 'bg-orange-100 text-orange-700 border border-orange-200';
                                        elseif($order->payment_method == 'cash') $payBg = 'bg-emerald-100 text-emerald-700 border border-emerald-200';
                                        elseif($order->payment_method == 'tripay') $payBg = 'bg-indigo-100 text-indigo-700 border border-indigo-200';
                                    @endphp
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide {{ $payBg }}">
                                        {{ $order->payment_method }}
                                    </span>
                                </div>

                                <div>
                                    <div class="font-bold text-slate-800 text-xs">{{ $order->order_number }}</div>
                                    <div class="text-[10px] text-slate-400 mt-0.5">
                                        {{ $order->created_at->format('d M Y, H:i') }}
                                    </div>
                                </div>

                                <div>
                                    @if($order->payment_status == 'paid')
                                        <span class="text-[10px] font-bold text-emerald-600 bg-emerald-50 px-1.5 py-0.5 rounded border border-emerald-100">
                                            <i class="fas fa-check-circle mr-1"></i>Lunas
                                        </span>
                                    @else
                                        <span class="text-[10px] font-bold text-rose-500 bg-rose-50 px-1.5 py-0.5 rounded border border-rose-100">
                                            <i class="fas fa-clock mr-1"></i>Belum Bayar
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </td>

                        <td class="px-4 py-4 align-top">
                            <div class="relative pl-4 border-l-2 border-slate-200 ml-1 flex flex-col gap-4">
                                <div class="relative">
                                    <span class="absolute -left-[21px] top-0.5 bg-white">
                                        <i class="fas fa-arrow-circle-up text-blue-500 text-sm"></i>
                                    </span>
                                    <div class="leading-tight">
                                        <div class="text-[10px] text-slate-400">Pengirim</div>
                                        <div class="font-bold text-slate-700 text-xs">Toko Sancaka</div>
                                        <div class="text-[10px] text-slate-500 truncate max-w-[200px]">Ngawi, Jawa Timur</div>
                                    </div>
                                </div>

                                <div class="relative">
                                    <span class="absolute -left-[21px] top-0.5 bg-white">
                                        <i class="fas fa-arrow-circle-down text-orange-500 text-sm"></i>
                                    </span>
                                    <div class="leading-tight">
                                        <div class="text-[10px] text-slate-400">Penerima</div>
                                        <div class="font-bold text-slate-700 text-xs">{{ $order->customer_name }}</div>
                                        <div class="text-[10px] text-slate-500 font-mono">{{ $order->customer_phone }}</div>
                                        
                                        @if(!empty($order->courier_service))
                                            <div class="text-[10px] text-slate-600 mt-1 leading-snug break-words">
                                                {{ $order->destination_address ?? '-' }}
                                            </div>
                                        @else
                                            <div class="mt-1">
                                                <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded bg-blue-50 text-blue-600 text-[10px] font-bold border border-blue-100">
                                                    <i class="fas fa-store"></i> Ambil di Toko
                                                </span>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </td>

                        <td class="px-4 py-4 align-top">
                            @if(!empty($order->courier_service))
                                <div class="flex flex-col gap-2">
                                    {{-- LOGIKA DETEKSI LOGO --}}
@php
    $kurir = strtolower($order->courier_service);
    $logo = null;

    if (str_contains($kurir, 'jne')) $logo = 'jne.png';
    elseif (str_contains($kurir, 'sicepat')) $logo = 'sicepat.png';
    elseif (str_contains($kurir, 'j&t') || str_contains($kurir, 'jnt')) $logo = 'jnt.png';
    elseif (str_contains($kurir, 'pos')) $logo = 'posindonesia.png';
    elseif (str_contains($kurir, 'anteraja')) $logo = 'anteraja.png';
    elseif (str_contains($kurir, 'ninja')) $logo = 'ninja.png';
    elseif (str_contains($kurir, 'id express') || str_contains($kurir, 'idx')) $logo = 'idx.png';
    elseif (str_contains($kurir, 'tiki')) $logo = 'tiki.png';
    elseif (str_contains($kurir, 'spx') || str_contains($kurir, 'shopee')) $logo = 'spx.png';
    elseif (str_contains($kurir, 'lion')) $logo = 'lion.png';
    elseif (str_contains($kurir, 'gojek') || str_contains($kurir, 'gosend')) $logo = 'gosend.png';
    elseif (str_contains($kurir, 'grab')) $logo = 'grab.png';
@endphp

<div class="flex items-start gap-2">
    {{-- TAMPILKAN LOGO JIKA ADA, JIKA TIDAK PAKAI IKON TRUK --}}
    <div class="bg-white border border-slate-200 p-1 rounded h-10 w-12 flex items-center justify-center overflow-hidden">
        @if($logo)
            <img src="{{ asset('storage/logo-ekspedisi/' . $logo) }}" alt="{{ $order->courier_service }}" class="w-full h-full object-contain">
        @else
            <i class="fas fa-truck text-slate-400"></i>
        @endif
    </div>
    
    <div>
        <div class="font-bold text-slate-700 text-xs uppercase max-w-[120px] leading-tight">
            {{ $order->courier_service }}
        </div>
        <div class="text-[11px] font-bold text-emerald-600 mt-0.5">
            Rp {{ number_format($order->shipping_cost, 0, ',', '.') }}
        </div>
    </div>
</div>

                                    <div class="bg-slate-50 border border-slate-200 rounded p-1.5">
                                        <div class="text-[9px] text-slate-400 uppercase tracking-wider mb-0.5">Nomor Resi</div>
                                        @if($order->shipping_ref)
                                            <div class="flex items-center justify-between gap-2">
                                                <span class="font-mono text-[11px] font-bold text-slate-700 select-all">{{ $order->shipping_ref }}</span>
                                                <button onclick="navigator.clipboard.writeText('{{ $order->shipping_ref }}'); alert('Resi disalin!')" class="text-slate-400 hover:text-blue-500 transition">
                                                    <i class="far fa-copy"></i>
                                                </button>
                                            </div>
                                        @else
                                            <span class="text-[10px] text-amber-500 italic">Menunggu Resi...</span>
                                        @endif
                                    </div>

                                    <div class="text-[10px] text-slate-500">
                                        Berat: <span class="font-bold text-slate-700">1.000 gram</span> 
                                        </div>
                                </div>
                            @else
                                <div class="text-slate-400 text-xs italic py-2">
                                    Tidak ada pengiriman (Pickup)
                                </div>
                            @endif
                        </td>

                        <td class="px-4 py-4 align-top text-right">
    <div class="flex flex-col items-end gap-1">
        <div class="font-black text-slate-800 text-sm">
            {{-- Hitung Ulang di Table juga --}}
            @php $totalReal = $order->total_price - $order->discount_amount + $order->shipping_cost; @endphp
            Rp {{ number_format($totalReal, 0, ',', '.') }}
        </div>
        
        {{-- Tampilkan rincian kecil jika ada ongkir/diskon --}}
        @if($order->shipping_cost > 0)
            <div class="text-[9px] text-slate-400">+Ongkir {{ number_format($order->shipping_cost/1000, 0) }}k</div>
        @endif
        @if($order->discount_amount > 0)
            <div class="text-[9px] text-emerald-500">-Disc {{ number_format($order->discount_amount/1000, 0) }}k</div>
        @endif

        <div class="mt-2">
            {{-- Status Badge --}}
            @php
                $statusClass = 'bg-slate-100 text-slate-500 border-slate-200';
                $icon = 'fa-clock';
                
                if($order->status == 'processing') { $statusClass = 'bg-amber-50 text-amber-600 border-amber-200'; $icon = 'fa-spinner fa-spin'; }
                elseif($order->status == 'shipped') { $statusClass = 'bg-indigo-50 text-indigo-600 border-indigo-200'; $icon = 'fa-shipping-fast'; }
                elseif($order->status == 'completed') { $statusClass = 'bg-emerald-50 text-emerald-600 border-emerald-200'; $icon = 'fa-check-double'; }
                elseif($order->status == 'cancelled') { $statusClass = 'bg-rose-50 text-rose-600 border-rose-200'; $icon = 'fa-times-circle'; }
            @endphp
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-bold uppercase border {{ $statusClass }}">
                <i class="fas {{ $icon }}"></i> {{ $order->status }}
            </span>
        </div>
    </div>
</td>

                        <td class="px-4 py-4 align-top text-center">
                            <div class="flex justify-center gap-1">
                                <a href="{{ route('reports.show', $order->id) }}" class="p-2 rounded hover:bg-indigo-50 text-slate-400 hover:text-indigo-600 transition" title="Lihat Detail">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <form action="{{ route('reports.destroy', $order->id) }}" method="POST" onsubmit="return confirm('Hapus pesanan ini?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="p-2 rounded hover:bg-rose-50 text-slate-400 hover:text-rose-600 transition" title="Hapus">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-slate-400">
                            <div class="flex flex-col items-center">
                                <i class="fas fa-box-open text-4xl mb-3 text-slate-200"></i>
                                <span>Belum ada data pesanan.</span>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="px-4 py-3 border-t border-slate-200 bg-slate-50">
            {{ $orders->links() }}
        </div>
    </div>
        
       
@endsection