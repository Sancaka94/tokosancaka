@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6 max-w-7xl">
    
    {{-- HEADER: Tombol Kembali & Judul --}}
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
        <div>
            <div class="flex items-center gap-3">
                <a href="{{ route('orders.index') }}" class="p-2 rounded-full bg-white text-slate-500 hover:text-slate-800 shadow-sm transition">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <h1 class="text-2xl font-black text-slate-800">Order #{{ $order->order_number }}</h1>
            </div>
            <p class="text-sm text-slate-500 mt-1 ml-11">
                Dibuat pada: {{ \Carbon\Carbon::parse($order->created_at)->translatedFormat('d F Y, H:i') }} WIB
            </p>
        </div>

        <div class="flex gap-3">
            {{-- BADGE STATUS ORDER --}}
            @php
                $statusColor = match($order->status) {
                    'completed' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
                    'processing' => 'bg-blue-100 text-blue-700 border-blue-200',
                    'shipped'    => 'bg-indigo-100 text-indigo-700 border-indigo-200',
                    'pending'    => 'bg-amber-100 text-amber-700 border-amber-200',
                    'cancelled'  => 'bg-red-100 text-red-700 border-red-200',
                    default      => 'bg-slate-100 text-slate-700 border-slate-200'
                };
                $statusLabel = match($order->status) {
                    'completed' => 'Selesai',
                    'processing'=> 'Diproses',
                    'shipped'   => 'Dikirim',
                    'pending'   => 'Menunggu',
                    'cancelled' => 'Dibatalkan',
                    default     => ucfirst($order->status)
                };
            @endphp
            <span class="px-4 py-2 rounded-lg font-bold text-xs uppercase tracking-wider border {{ $statusColor }}">
                {{ $statusLabel }}
            </span>

            {{-- BADGE STATUS PEMBAYARAN --}}
            @php
                $payColor = $order->payment_status == 'paid' ? 'bg-emerald-500 text-white shadow-lg shadow-emerald-200' : 'bg-rose-500 text-white shadow-lg shadow-rose-200';
            @endphp
            <span class="px-4 py-2 rounded-lg font-bold text-xs uppercase tracking-wider {{ $payColor }}">
                {{ $order->payment_status == 'paid' ? 'LUNAS' : 'BELUM BAYAR' }}
            </span>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        {{-- KOLOM KIRI (UTAMA) --}}
        <div class="lg:col-span-2 space-y-6">

            {{-- 1. RINCIAN PRODUK --}}
            @if($order->items->count() > 0)
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50">
                    <h3 class="font-bold text-slate-700 flex items-center gap-2">
                        <i class="fas fa-box text-slate-400"></i> Rincian Produk
                    </h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-slate-50 text-slate-500 font-bold uppercase text-[10px]">
                            <tr>
                                <th class="px-6 py-3">Produk</th>
                                <th class="px-6 py-3 text-center">Qty</th>
                                <th class="px-6 py-3 text-right">Harga Satuan</th>
                                <th class="px-6 py-3 text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($order->items as $item)
                            <tr class="hover:bg-slate-50 transition">
                                <td class="px-6 py-4 font-bold text-slate-700">{{ $item->product_name }}</td>
                                <td class="px-6 py-4 text-center">{{ $item->quantity }}</td>
                                <td class="px-6 py-4 text-right text-slate-500">Rp {{ number_format($item->price_at_order, 0, ',', '.') }}</td>
                                <td class="px-6 py-4 text-right font-bold text-slate-800">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            {{-- 2. FILE ATTACHMENT --}}
            @if($order->attachments->count() > 0)
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 bg-indigo-50 flex justify-between items-center">
                    <h3 class="font-bold text-indigo-800 flex items-center gap-2">
                        <i class="fas fa-print"></i> Berkas Cetak (Upload)
                    </h3>
                    <span class="bg-white text-indigo-600 px-2 py-1 rounded text-xs font-bold border border-indigo-100">
                        {{ $order->attachments->count() }} File
                    </span>
                </div>
                
                <div class="p-4 grid grid-cols-1 gap-4">
                    @foreach($order->attachments as $file)
                    <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4 p-4 rounded-xl border border-slate-200 hover:border-indigo-300 hover:shadow-md transition-all bg-white group">
                        
                        {{-- Icon / Preview --}}
                        <div class="h-16 w-16 shrink-0 rounded-lg bg-slate-100 flex items-center justify-center text-3xl text-slate-400 overflow-hidden border border-slate-200">
                            @if(Str::contains($file->file_type, 'image'))
                                <img src="{{ asset('storage/'.$file->file_path) }}" class="w-full h-full object-cover cursor-pointer hover:scale-110 transition" onclick="window.open(this.src)">
                            @elseif(Str::contains($file->file_type, 'pdf'))
                                <i class="fas fa-file-pdf text-red-500"></i>
                            @else
                                <i class="fas fa-file text-blue-500"></i>
                            @endif
                        </div>

                        <div class="flex-1 min-w-0 w-full">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h4 class="font-bold text-slate-800 text-sm truncate pr-4" title="{{ $file->file_name }}">
                                        {{ $file->file_name }}
                                    </h4>
                                    <a href="{{ asset('storage/'.$file->file_path) }}" target="_blank" class="text-[10px] text-blue-500 hover:underline flex items-center gap-1 mt-1 font-bold">
                                        <i class="fas fa-external-link-alt"></i> Buka File Asli
                                    </a>
                                </div>
                            </div>

                            <div class="flex flex-wrap gap-2 mt-3">
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md bg-slate-100 text-slate-600 text-xs font-bold border border-slate-200">
                                    <i class="fas fa-expand text-slate-400"></i> {{ $file->paper_size ?? 'A4' }}
                                </span>

                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-bold border 
                                    {{ ($file->color_mode == 'Color' || $file->color_mode == '1') ? 'bg-pink-50 text-pink-700 border-pink-200' : 'bg-gray-50 text-gray-700 border-gray-200' }}">
                                    <i class="fas fa-palette"></i> 
                                    {{ ($file->color_mode == 'Color' || $file->color_mode == '1') ? 'Berwarna' : 'Hitam Putih' }}
                                </span>

                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md bg-blue-50 text-blue-700 text-xs font-bold border border-blue-200">
                                    <i class="fas fa-copy"></i> {{ $file->quantity ?? 1 }} Copy
                                </span>
                            </div>
                        </div>

                        <a href="{{ asset('storage/'.$file->file_path) }}" download 
                           class="w-full sm:w-auto px-4 py-2 bg-slate-800 text-white text-xs font-bold rounded-lg hover:bg-slate-900 transition flex items-center justify-center gap-2 shadow-lg shadow-slate-200">
                            <i class="fas fa-download"></i> <span class="sm:hidden">Download</span>
                        </a>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- 3. CATATAN SISTEM (JIKA ADA) --}}
            @if($order->note)
            <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 flex gap-3 items-start">
                <i class="fas fa-info-circle text-amber-500 mt-0.5"></i>
                <div>
                    <h4 class="font-bold text-amber-800 text-sm mb-1">Catatan Pesanan:</h4>
                    <p class="text-sm text-amber-700 whitespace-pre-line leading-relaxed">{{ $order->note }}</p>
                </div>
            </div>
            @endif

        </div>

        {{-- KOLOM KANAN (SIDEBAR) --}}
        <div class="lg:col-span-1 space-y-6">
            
            {{-- A. INFORMASI PENGIRIMAN & PELANGGAN --}}
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
                <h3 class="font-bold text-slate-700 mb-4 border-b border-slate-100 pb-2">Informasi Pengiriman</h3>
                
                <div class="space-y-4">
                    {{-- 1. Pengirim --}}
                    <div class="relative pl-4 border-l-2 border-slate-200 ml-1">
                        <span class="absolute -left-[5px] top-0 h-2.5 w-2.5 rounded-full bg-slate-300 ring-4 ring-white"></span>
                        <div class="leading-tight">
                            <div class="text-[10px] text-slate-400 font-bold uppercase tracking-wider mb-1">Pengirim</div>
                            <div class="font-bold text-slate-700 text-sm">Toko Sancaka</div>
                            <div class="text-xs text-slate-500">Ngawi, Jawa Timur</div>
                        </div>
                    </div>

                    {{-- 2. Penerima --}}
                    <div class="relative pl-4 border-l-2 border-slate-200 ml-1 pb-2">
                        <span class="absolute -left-[5px] top-0 h-2.5 w-2.5 rounded-full bg-blue-500 ring-4 ring-white"></span>
                        <div class="leading-tight">
                            <div class="text-[10px] text-slate-400 font-bold uppercase tracking-wider mb-1">Penerima</div>
                            <div class="font-bold text-slate-700 text-sm">{{ $order->customer_name }}</div>
                            <div class="text-xs text-slate-500 font-mono mt-0.5">{{ $order->customer_phone }}</div>
                            
                            @if($order->destination_address)
                                <div class="mt-2 text-xs text-slate-600 bg-slate-50 p-2 rounded border border-slate-100 leading-relaxed">
                                    {{ $order->destination_address }}
                                </div>
                            @else
                                <div class="mt-2">
                                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded bg-blue-50 text-blue-600 text-[10px] font-bold border border-blue-100">
                                        <i class="fas fa-store"></i> Ambil di Toko
                                    </span>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- TAMPILAN UNTUK INPUT MANUAL (CUSTOMER NOTE) --}}
{{-- TAMPILAN UNTUK INPUT MANUAL (CUSTOMER NOTE) --}}
@if($order->customer_note)
<div class="mt-6" x-data="{ openNoteModal: false }">
    <h3 class="text-xs font-black text-amber-500 uppercase tracking-widest mb-2 flex items-center gap-2">
        <i class="fas fa-comment-dots"></i> Catatan Pelanggan
    </h3>
    
    <div class="bg-amber-50 border border-amber-100 p-4 rounded-xl relative">
        <p class="text-sm text-slate-700 italic line-clamp-3 break-all">
            "{{ $order->customer_note }}"
        </p>
        
        <button @click="openNoteModal = true" 
                class="mt-2 text-xs font-bold text-amber-600 hover:text-amber-800 hover:underline flex items-center gap-1">
            <span>Baca Selengkapnya</span>
            <i class="fas fa-external-link-alt"></i>
        </button>
    </div>

    <div x-show="openNoteModal" 
         style="display: none;"
         class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 backdrop-blur-sm p-4"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
         
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden transform transition-all"
             @click.away="openNoteModal = false">
            
            <div class="bg-amber-100 px-6 py-4 flex justify-between items-center border-b border-amber-200">
                <h3 class="text-lg font-bold text-amber-800 flex items-center gap-2">
                    <i class="fas fa-comment-alt"></i> Catatan Lengkap
                </h3>
                <button @click="openNoteModal = false" class="text-amber-800 hover:text-red-600 transition">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <div class="p-6 max-h-[70vh] overflow-y-auto">
                <div class="bg-slate-50 p-4 rounded-lg border border-slate-200">
                    <p class="text-slate-700 text-sm leading-relaxed whitespace-pre-line break-words">
                        {{ $order->customer_note }}
                    </p>
                </div>
            </div>

            <div class="bg-gray-50 px-6 py-3 flex justify-end border-t">
                <button @click="openNoteModal = false" 
                        class="px-4 py-2 bg-slate-200 text-slate-700 text-sm font-bold rounded-lg hover:bg-slate-300 transition">
                    Tutup
                </button>
            </div>
        </div>
    </div>
</div>
@endif
                </div>
            </div>

            {{-- B. LOGO EKSPEDISI & ONGKIR --}}
            @if($order->shipping_cost > 0 || $order->courier_service)
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
                <h3 class="font-bold text-slate-700 mb-4 border-b border-slate-100 pb-2">Ekspedisi</h3>
                
                {{-- LOGIKA LOGO --}}
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

                <div class="flex items-center gap-3 bg-slate-50 p-3 rounded-xl border border-slate-100 mb-3">
                    <div class="w-12 h-10 flex items-center justify-center bg-white rounded border border-slate-200 p-1">
                        @if($logo)
                            <img src="{{ asset('storage/logo-ekspedisi/' . $logo) }}" class="w-full h-full object-contain">
                        @else
                            <i class="fas fa-shipping-fast text-slate-400 text-xl"></i>
                        @endif
                    </div>
                    <div>
                        <div class="text-[10px] font-bold text-slate-500 uppercase">Layanan</div>
                        <div class="text-xs font-bold text-slate-800 uppercase leading-tight">{{ $order->courier_service }}</div>
                    </div>
                </div>

                @if($order->shipping_ref)
                <div class="flex justify-between items-center bg-indigo-50 p-2.5 rounded-lg border border-indigo-100">
                    <span class="text-xs text-indigo-600 font-bold">Resi:</span>
                    <div class="flex items-center gap-2">
                        <span class="font-mono font-bold text-slate-800 text-sm select-all">{{ $order->shipping_ref }}</span>
                        <button onclick="navigator.clipboard.writeText('{{ $order->shipping_ref }}'); alert('Resi disalin!')" class="text-indigo-400 hover:text-indigo-600">
                            <i class="far fa-copy"></i>
                        </button>
                    </div>
                </div>
                @else
                <div class="text-xs text-amber-600 bg-amber-50 p-2 rounded border border-amber-100 text-center italic">
                    Menunggu Resi...
                </div>
                @endif
            </div>
            @endif

            {{-- C. RINCIAN PEMBAYARAN (FIX LOGIKA) --}}
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
                <h3 class="font-bold text-slate-700 mb-4 border-b border-slate-100 pb-2">Pembayaran</h3>
                
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between text-slate-600">
                        <span>Subtotal Produk</span>
                        <span>Rp {{ number_format($order->total_price, 0, ',', '.') }}</span>
                    </div>
                    
                    @if($order->shipping_cost > 0)
                    <div class="flex justify-between text-blue-600">
                        <span>Ongkos Kirim (+)</span>
                        <span>Rp {{ number_format($order->shipping_cost, 0, ',', '.') }}</span>
                    </div>
                    @endif

                    @if($order->discount_amount > 0)
                    <div class="flex justify-between text-emerald-600 font-bold">
                        <span>Diskon Kupon (-)</span>
                        <span>Rp {{ number_format($order->discount_amount, 0, ',', '.') }}</span>
                    </div>
                    @endif

                    <div class="border-t border-dashed border-slate-300 my-2 pt-2">
                        <div class="flex justify-between items-end">
                            <span class="font-bold text-slate-800">Total Bayar</span>
                            <span class="text-2xl font-black text-red-600">
                                {{-- PERHITUNGAN ULANG MANUAL AGAR TAMPILAN BENAR --}}
                                @php 
                                    $totalFix = $order->total_price + $order->shipping_cost - $order->discount_amount; 
                                @endphp
                                Rp {{ number_format($totalFix, 0, ',', '.') }}
                            </span>
                        </div>
                        <div class="text-right mt-1">
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest bg-slate-100 px-2 py-0.5 rounded">
                                {{ strtoupper($order->payment_method) }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="mt-6 space-y-2">
                    <a href="#" onclick="window.print()" class="block w-full py-3 bg-slate-800 text-white font-bold text-center rounded-xl hover:bg-slate-900 transition shadow-lg shadow-slate-200">
                        <i class="fas fa-print mr-2"></i> Cetak Invoice
                    </a>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection