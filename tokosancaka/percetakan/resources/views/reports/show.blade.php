@extends('layouts.app')

@section('title', 'Detail Pesanan')

@section('content')
    <div class="mb-6">
        <a href="{{ route('reports.index') }}" class="text-slate-500 hover:text-slate-800 text-sm font-bold flex items-center gap-2 transition">
            <i class="fas fa-arrow-left"></i> Kembali ke Riwayat
        </a>
    </div>

    <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
        
        <div class="bg-slate-800 text-white p-6 flex justify-between items-center">
            <div>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Detail Pesanan</p>
                <h1 class="text-2xl font-black">#{{ $order->order_number }}</h1>
            </div>
            <div class="text-right">
                <p class="text-sm font-medium opacity-80">{{ $order->created_at->translatedFormat('d F Y, H:i') }}</p>
                <div class="mt-1">
                    <span class="px-2 py-1 text-[10px] font-bold uppercase rounded bg-white/20 text-white border border-white/20">
                        {{ $order->status }}
                    </span>
                </div>
            </div>
        </div>

        <div class="p-8 grid grid-cols-1 md:grid-cols-2 gap-8">
            <div>
                <h3 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-3 border-b pb-2">Informasi Pelanggan</h3>
                <p class="font-bold text-lg text-slate-800">{{ $order->customer_name }}</p>
                <p class="text-slate-500 text-sm mt-1">
                    <i class="fas fa-phone mr-1"></i> {{ $order->customer_phone ?? '-' }}
                </p>
                <p class="text-slate-500 text-sm mt-1">
                    <i class="fas fa-map-marker-alt mr-1"></i> {{ Str::limit($order->destination_address, 100) }}
                </p>

                {{-- TAMPILAN UNTUK INPUT MANUAL (CATATAN PELANGGAN) --}}
@if(!empty($order->customer_note))
<div class="mt-6" x-data="{ openNoteModal: false }">
    <h3 class="text-xs font-black text-amber-500 uppercase tracking-widest mb-2 flex items-center gap-2">
        <i class="fas fa-comment-dots"></i> Catatan Pelanggan
    </h3>
    
    <div class="bg-amber-50 border border-amber-100 p-4 rounded-xl relative">
        <i class="fas fa-quote-left text-amber-200 absolute top-2 left-2 text-2xl"></i>
        
        <p class="text-sm text-slate-700 italic relative z-10 pl-6 line-clamp-3 break-all">
            "{{ $order->customer_note }}"
        </p>

        <div class="mt-2 pl-6">
            <button @click="openNoteModal = true" 
                    class="text-xs font-bold text-amber-600 hover:text-amber-800 hover:underline flex items-center gap-1 transition">
                <span>Baca Selengkapnya</span>
                <i class="fas fa-external-link-alt"></i>
            </button>
        </div>
    </div>

    <div x-show="openNoteModal" 
         style="display: none;"
         class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-60 backdrop-blur-sm p-4"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
         
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden transform transition-all border border-slate-200"
             @click.away="openNoteModal = false">
            
            <div class="bg-amber-100 px-6 py-4 flex justify-between items-center border-b border-amber-200">
                <h3 class="text-lg font-bold text-amber-900 flex items-center gap-2">
                    <i class="fas fa-comment-alt"></i> Isi Catatan Lengkap
                </h3>
                <button @click="openNoteModal = false" class="text-amber-800 hover:text-red-600 transition p-1">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <div class="p-6 max-h-[70vh] overflow-y-auto bg-white">
                <div class="bg-slate-50 p-5 rounded-xl border border-slate-200 shadow-inner">
                    <p class="text-slate-700 text-sm leading-relaxed whitespace-pre-line break-words text-justify">
                        {{ $order->customer_note }}
                    </p>
                </div>
            </div>

            <div class="bg-slate-50 px-6 py-3 flex justify-end border-t border-slate-100">
                <button @click="openNoteModal = false" 
                        class="px-5 py-2 bg-slate-800 text-white text-sm font-bold rounded-lg hover:bg-black transition shadow-lg">
                    Tutup
                </button>
            </div>
        </div>
    </div>
</div>
@endif
                
                <h3 class="text-xs font-black text-slate-400 uppercase tracking-widest mt-6 mb-3 border-b pb-2">Catatan</h3>
                <p class="text-sm text-slate-600 italic bg-slate-50 p-3 rounded-xl border border-slate-100">
                    "{{ $order->note ?? 'Tidak ada catatan' }}"
                </p>
            </div>

            <div>
                <h3 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-3 border-b pb-2">Berkas Lampiran</h3>
                @if($order->attachments->count() > 0)
                    <div class="space-y-2">
                        @foreach($order->attachments as $file)
                        <a href="{{ asset('storage/' . $file->file_path) }}" target="_blank" 
                           class="flex items-center gap-3 p-3 bg-slate-50 border border-slate-200 rounded-xl hover:border-red-300 hover:bg-red-50 transition group">
                            <div class="h-10 w-10 bg-white rounded-lg flex items-center justify-center text-red-500 shadow-sm group-hover:scale-110 transition-transform">
                                <i class="fas fa-file-download"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-bold text-slate-700 truncate group-hover:text-red-600">{{ $file->file_name }}</p>
                                <p class="text-[10px] text-slate-400 uppercase">{{ $file->file_type }}</p>
                            </div>
                            <i class="fas fa-external-link-alt text-slate-300 group-hover:text-red-500"></i>
                        </a>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-6 bg-slate-50 rounded-xl border border-dashed border-slate-200">
                        <p class="text-sm text-slate-400">Tidak ada file dilampirkan</p>
                    </div>
                @endif
            </div>
        </div>

        <div class="p-8 border-t border-slate-100">
            <h3 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-4">Rincian Item</h3>
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-slate-500 font-bold">
                    <tr>
                        <th class="px-4 py-3 rounded-l-lg">Produk</th>
                        <th class="px-4 py-3 text-center">Qty</th>
                        <th class="px-4 py-3 text-right">Harga</th>
                        <th class="px-4 py-3 text-right rounded-r-lg">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                 
    @foreach($order->details as $item)
    <tr>
        <td class="px-4 py-3 font-bold text-slate-700">{{ $item->product_name }}</td>
        
        {{-- PERBAIKAN: Gunakan 'quantity' bukan 'qty' --}}
        <td class="px-4 py-3 text-center">{{ $item->quantity }}</td>
        
        {{-- PERBAIKAN: Gunakan 'price_at_order' bukan 'price' --}}
        <td class="px-4 py-3 text-right text-slate-500">
            Rp {{ number_format($item->price_at_order, 0, ',', '.') }}
        </td>
        
        <td class="px-4 py-3 text-right font-bold text-slate-800">
            Rp {{ number_format($item->subtotal, 0, ',', '.') }}
        </td>
    </tr>
    @endforeach
</tbody>
                
                <tfoot class="border-t border-slate-200 bg-slate-50/50">
    {{-- 1. SUBTOTAL (Total Harga Barang) --}}
    <tr>
        <td colspan="3" class="px-4 pt-4 pb-1 text-right text-xs font-bold text-slate-500 uppercase tracking-wide">
            Subtotal
        </td>
        <td class="px-4 pt-4 pb-1 text-right text-sm font-bold text-slate-700">
            Rp {{ number_format($order->total_price, 0, ',', '.') }}
        </td>
    </tr>

    {{-- 2. DISKON (Hanya muncul jika ada diskon) --}}
    @if($order->discount_amount > 0)
    <tr>
        <td colspan="3" class="px-4 py-1 text-right text-xs font-bold text-green-600 uppercase tracking-wide">
            Diskon Kupon
        </td>
        <td class="px-4 py-1 text-right text-sm font-bold text-green-600">
            - Rp {{ number_format($order->discount_amount, 0, ',', '.') }}
        </td>
    </tr>
    @endif

    {{-- 3. ONGKIR (Hanya muncul jika ada ongkir) --}}
    @if($order->shipping_cost > 0)
    <tr>
        <td colspan="3" class="px-4 py-1 text-right text-xs font-bold text-slate-500 uppercase tracking-wide">
            Ongkos Kirim ({{ strtoupper($order->courier_service ?? 'Ekspedisi') }})
        </td>
        <td class="px-4 py-1 text-right text-sm font-bold text-slate-700">
            + Rp {{ number_format($order->shipping_cost, 0, ',', '.') }}
        </td>
    </tr>
    @endif

    {{-- 4. GARIS PEMISAH TIPIS --}}
    <tr>
        <td colspan="4" class="px-4 py-2">
            <div class="border-b border-slate-200 border-dashed"></div>
        </td>
    </tr>

    {{-- 5. TOTAL AKHIR (Dihitung Ulang agar Sinkron) --}}
    <tr>
        <td colspan="3" class="px-4 pb-4 pt-2 text-right font-black text-slate-800 uppercase tracking-widest text-sm">
            Total Bayar
        </td>
        <td class="px-4 pb-4 pt-2 text-right font-black text-xl text-red-600">
            {{-- KITA HITUNG MANUAL DISINI AGAR TAMPILANNYA KONSISTEN --}}
            @php
                $hitungUlang = $order->total_price - $order->discount_amount + $order->shipping_cost;
            @endphp
            Rp {{ number_format($hitungUlang, 0, ',', '.') }}
        </td>
    </tr>
</tfoot>
            </table>
        </div>

        <div class="bg-slate-50 p-6 flex justify-end gap-3 border-t border-slate-100">
            <a href="{{ route('reports.edit', $order->id) }}" class="px-6 py-2.5 rounded-xl text-sm font-bold bg-slate-800 text-white hover:bg-black transition shadow-lg">Edit Status</a>
        </div>
    </div>
@endsection