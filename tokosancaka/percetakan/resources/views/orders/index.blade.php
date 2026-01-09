@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6 max-w-7xl">
    
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-black text-slate-800">Riwayat Pesanan</h1>
            <p class="text-sm text-slate-500">Daftar semua transaksi yang masuk.</p>
        </div>
        <a href="{{ route('orders.create') }}" class="bg-emerald-600 text-white px-4 py-2 rounded-lg font-bold text-sm hover:bg-emerald-700 transition flex items-center gap-2 shadow-lg shadow-emerald-200">
            <i class="fas fa-plus"></i> Pesanan Baru
        </a>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-slate-50 text-slate-500 font-bold uppercase text-[10px]">
                    <tr>
                        <th class="px-6 py-4 w-[20%]">Transaksi</th>
                        <th class="px-6 py-4 w-[25%]">Pelanggan & Alamat</th>
                        <th class="px-6 py-4 w-[20%]">Ekspedisi & Ongkir</th>
                        <th class="px-6 py-4 w-[15%] text-right">Total & Pembayaran</th>
                        <th class="px-6 py-4 w-[10%] text-center">Status</th>
                        <th class="px-6 py-4 w-[10%] text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($orders as $order)
                    <tr class="hover:bg-slate-50 transition">
                        
                        {{-- KOLOM 1: TRANSAKSI --}}
                        <td class="px-6 py-4 align-top">
                            <div class="flex flex-col gap-1">
                                <span class="font-bold text-slate-800 text-xs">{{ $order->order_number }}</span>
                                <span class="text-[10px] text-slate-400">
                                    {{ $order->created_at->translatedFormat('d M Y, H:i') }}
                                </span>
                                
                                @if($order->shipping_ref)
                                <div class="mt-1 flex items-center gap-1">
                                    <span class="text-[9px] font-bold text-slate-400 uppercase">Resi:</span>
                                    <span class="text-[10px] font-mono text-blue-600 bg-blue-50 px-1.5 py-0.5 rounded border border-blue-100 select-all">
                                        {{ $order->shipping_ref }}
                                    </span>
                                </div>
                                @endif
                            </div>
                        </td>

                        {{-- KOLOM 2: PELANGGAN --}}
                        <td class="px-6 py-4 align-top">
                            <div class="flex flex-col gap-1">
                                <div class="font-bold text-slate-700 text-sm">{{ $order->customer_name }}</div>
                                <div class="flex items-center gap-1 text-xs text-slate-500">
                                    <i class="fab fa-whatsapp text-green-500"></i> {{ $order->customer_phone }}
                                </div>
                                
                                @if($order->destination_address)
                                <div class="mt-1.5 text-[10px] text-slate-500 leading-snug break-words bg-slate-50 p-1.5 rounded border border-slate-100 max-w-[250px]" title="{{ $order->destination_address }}">
                                    <i class="fas fa-map-marker-alt text-red-400 mr-1"></i>
                                    {{ Str::limit($order->destination_address, 80) }}
                                </div>
                                @else
                                <div class="mt-1">
                                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded bg-blue-50 text-blue-600 text-[9px] font-bold border border-blue-100">
                                        <i class="fas fa-store"></i> Ambil di Toko
                                    </span>
                                </div>
                                @endif
                            </div>
                        </td>

                        {{-- KOLOM 3: EKSPEDISI --}}
                        <td class="px-6 py-4 align-top">
                            @if($order->shipping_cost > 0 || $order->courier_service)
                                {{-- LOGIKA LOGO EKSPEDISI --}}
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
                                    <div class="bg-white border border-slate-200 p-1 rounded h-8 w-10 flex items-center justify-center overflow-hidden shrink-0">
                                        @if($logo)
                                            <img src="{{ asset('storage/logo-ekspedisi/' . $logo) }}" alt="{{ $order->courier_service }}" class="w-full h-full object-contain">
                                        @else
                                            <i class="fas fa-truck text-slate-400 text-xs"></i>
                                        @endif
                                    </div>
                                    <div>
                                        <div class="text-[10px] font-bold text-slate-700 uppercase leading-tight max-w-[120px]">
                                            {{ $order->courier_service }}
                                        </div>
                                        <div class="text-[10px] font-bold text-emerald-600 mt-0.5">
                                            + Rp {{ number_format($order->shipping_cost, 0, ',', '.') }}
                                        </div>
                                    </div>
                                </div>
                            @else
                                <span class="text-[10px] text-slate-400 italic">Pickup / Tanpa Kurir</span>
                            @endif
                        </td>

                        {{-- KOLOM 4: TOTAL & PEMBAYARAN --}}
                        <td class="px-6 py-4 align-top text-right">
                            <div class="flex flex-col gap-1 items-end">
                                <div class="font-black text-slate-800 text-sm">
                                    {{-- Hitung Ulang agar Akurat --}}
                                    @php $totalReal = $order->total_price - $order->discount_amount + $order->shipping_cost; @endphp
                                    Rp {{ number_format($totalReal, 0, ',', '.') }}
                                </div>
                                
                                <div class="flex items-center gap-1 mt-1">
                                    <span class="text-[9px] font-bold uppercase text-slate-400 mr-1">{{ $order->payment_method }}</span>
                                    <span class="px-1.5 py-0.5 rounded text-[9px] font-bold border uppercase
                                        {{ $order->payment_status == 'paid' ? 'bg-green-50 text-green-600 border-green-200' : 'bg-red-50 text-red-600 border-red-200' }}">
                                        {{ $order->payment_status == 'paid' ? 'LUNAS' : 'BELUM' }}
                                    </span>
                                </div>
                            </div>
                        </td>

                        {{-- KOLOM 5: STATUS --}}
                        <td class="px-6 py-4 align-top text-center">
                            @php
                                $statusStyles = [
                                    'completed' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
                                    'processing' => 'bg-blue-100 text-blue-700 border-blue-200',
                                    'shipped' => 'bg-indigo-100 text-indigo-700 border-indigo-200',
                                    'pending' => 'bg-amber-100 text-amber-700 border-amber-200',
                                    'cancelled' => 'bg-red-100 text-red-700 border-red-200',
                                ];
                                $style = $statusStyles[$order->status] ?? 'bg-slate-100 text-slate-600 border-slate-200';
                                
                                $labels = [
                                    'completed' => 'Selesai',
                                    'processing' => 'Diproses',
                                    'shipped' => 'Dikirim',
                                    'pending' => 'Menunggu',
                                    'cancelled' => 'Batal'
                                ];
                                $label = $labels[$order->status] ?? ucfirst($order->status);
                            @endphp
                            <span class="inline-block px-2.5 py-1 rounded-full text-[10px] font-bold uppercase border {{ $style }}">
                                {{ $label }}
                            </span>
                        </td>

                        {{-- KOLOM 6: AKSI --}}
                        <td class="px-6 py-4 align-top text-center">
                            <a href="{{ route('orders.show', $order->id) }}" class="inline-flex items-center justify-center w-8 h-8 bg-white border border-slate-200 rounded-lg text-slate-500 hover:text-blue-600 hover:border-blue-300 hover:bg-blue-50 transition shadow-sm" title="Lihat Detail">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-slate-400">
                            <div class="flex flex-col items-center">
                                <i class="fas fa-box-open text-4xl mb-3 opacity-30"></i>
                                <p>Belum ada data pesanan.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="px-6 py-4 border-t border-slate-100 bg-slate-50">
            {{ $orders->links() }}
        </div>
    </div>
</div>
@endsection