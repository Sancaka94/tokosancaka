@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6 max-w-7xl">
    
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
        <div>
            <div class="flex items-center gap-3">
                <a href="{{ route('orders.index') }}" class="p-2 rounded-full bg-white text-slate-500 hover:text-slate-800 shadow-sm transition">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <h1 class="text-2xl font-black text-slate-800">Order #{{ $order->order_number }}</h1>
            </div>
            <p class="text-sm text-slate-500 mt-1 ml-11">
                Dibuat pada: {{ \Carbon\Carbon::parse($order->created_at)->format('d M Y, H:i') }}
            </p>
        </div>

        <div class="flex gap-3">
            @php
                $statusColor = match($order->status) {
                    'completed' => 'bg-emerald-100 text-emerald-700',
                    'processing' => 'bg-blue-100 text-blue-700',
                    'pending' => 'bg-amber-100 text-amber-700',
                    'cancelled' => 'bg-red-100 text-red-700',
                    default => 'bg-slate-100 text-slate-700'
                };
                $statusLabel = match($order->status) {
                    'completed' => 'Selesai',
                    'processing' => 'Diproses',
                    'pending' => 'Menunggu',
                    'cancelled' => 'Dibatalkan',
                    default => ucfirst($order->status)
                };
            @endphp
            <span class="px-4 py-2 rounded-lg font-bold text-xs uppercase tracking-wider {{ $statusColor }}">
                {{ $statusLabel }}
            </span>

            @php
                $payColor = $order->payment_status == 'paid' ? 'bg-green-100 text-green-700 border-green-200' : 'bg-red-100 text-red-700 border-red-200';
            @endphp
            <span class="px-4 py-2 rounded-lg font-bold text-xs uppercase tracking-wider border {{ $payColor }}">
                {{ $order->payment_status == 'paid' ? 'LUNAS' : 'BELUM BAYAR' }}
            </span>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <div class="lg:col-span-2 space-y-6">

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
                            <tr class="hover:bg-slate-50">
                                <td class="px-6 py-4 font-bold text-slate-700">{{ $item->product_name }}</td>
                                <td class="px-6 py-4 text-center">{{ $item->quantity }}</td>
                                <td class="px-6 py-4 text-right">Rp {{ number_format($item->price_at_order, 0, ',', '.') }}</td>
                                <td class="px-6 py-4 text-right font-bold text-slate-800">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

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
                    <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4 p-4 rounded-xl border border-slate-200 hover:border-indigo-300 hover:shadow-md transition-all bg-white">
                        
                        <div class="h-16 w-16 shrink-0 rounded-lg bg-slate-100 flex items-center justify-center text-3xl text-slate-400 overflow-hidden border border-slate-200">
                            @if(Str::contains($file->file_type, 'image'))
                                <img src="{{ asset('storage/'.$file->file_path) }}" class="w-full h-full object-cover cursor-pointer" onclick="window.open(this.src)">
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
                                    <a href="{{ asset('storage/'.$file->file_path) }}" target="_blank" class="text-[10px] text-blue-500 hover:underline flex items-center gap-1 mt-1">
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
                           class="w-full sm:w-auto px-4 py-2 bg-slate-800 text-white text-xs font-bold rounded-lg hover:bg-slate-900 transition flex items-center justify-center gap-2">
                            <i class="fas fa-download"></i> <span class="sm:hidden">Download</span>
                        </a>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            @if($order->note)
            <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-4">
                <h4 class="font-bold text-yellow-800 text-sm mb-1"><i class="fas fa-sticky-note mr-1"></i> Catatan Pesanan:</h4>
                <p class="text-sm text-yellow-700">{{ $order->note }}</p>
            </div>
            @endif

        </div>

        <div class="lg:col-span-1 space-y-6">
            
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
                <h3 class="font-bold text-slate-700 mb-4 border-b border-slate-100 pb-2">Informasi Pelanggan</h3>
                
                <div class="space-y-3">
                    <div class="flex items-start gap-3">
                        <div class="mt-1"><i class="fas fa-user text-slate-400"></i></div>
                        <div>
                            <p class="text-xs text-slate-400 uppercase font-bold">Nama</p>
                            <p class="font-bold text-slate-800">{{ $order->customer_name }}</p>
                        </div>
                    </div>

                    <div class="flex items-start gap-3">
                        <div class="mt-1"><i class="fab fa-whatsapp text-green-500"></i></div>
                        <div>
                            <p class="text-xs text-slate-400 uppercase font-bold">WhatsApp</p>
                            <a href="https://wa.me/{{ preg_replace('/^0/', '62', $order->customer_phone) }}" target="_blank" class="font-bold text-green-600 hover:underline">
                                {{ $order->customer_phone }}
                            </a>
                        </div>
                    </div>

                    @if($order->destination_address)
                    <div class="flex items-start gap-3">
                        <div class="mt-1"><i class="fas fa-map-marker-alt text-red-500"></i></div>
                        <div>
                            <p class="text-xs text-slate-400 uppercase font-bold">Alamat Pengiriman</p>
                            <p class="text-sm text-slate-600 leading-snug">{{ $order->destination_address }}</p>
                        </div>
                    </div>
                    @endif

                    {{-- [TAMBAHAN] CATATAN PELANGGAN DI SINI --}}
                    @if($order->customer_note)
                    <div class="mt-4 pt-4 border-t border-slate-100">
                        <div class="flex items-start gap-3">
                            <div class="mt-1"><i class="fas fa-sticky-note text-amber-500"></i></div>
                            <div>
                                <p class="text-xs text-slate-400 uppercase font-bold">Catatan Pelanggan</p>
                                <div class="bg-amber-50 p-2.5 rounded-lg border border-amber-100 mt-1">
                                    <p class="text-xs text-slate-700 italic">"{{ $order->customer_note }}"</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif
                    {{-- [SELESAI TAMBAHAN] --}}
                </div>
            </div>

            @if($order->shipping_cost > 0 || $order->courier_service)
            <div class="bg-blue-50 rounded-2xl shadow-sm border border-blue-100 p-5">
                <h3 class="font-bold text-blue-800 mb-3 border-b border-blue-200 pb-2">Ekspedisi</h3>
                <div class="flex justify-between items-center mb-2">
                    <span class="text-sm text-blue-600">Kurir</span>
                    <span class="font-bold text-blue-900 uppercase">{{ $order->courier_service ?? 'Ambil Sendiri' }}</span>
                </div>
                @if($order->shipping_ref)
                <div class="flex justify-between items-center">
                    <span class="text-sm text-blue-600">Resi</span>
                    <span class="font-mono font-bold text-slate-800 bg-white px-2 py-0.5 rounded border border-blue-200">{{ $order->shipping_ref }}</span>
                </div>
                @endif
            </div>
            @endif

            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
                <h3 class="font-bold text-slate-700 mb-4 border-b border-slate-100 pb-2">Rincian Pembayaran</h3>
                
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between text-slate-600">
                        <span>Subtotal</span>
                        <span>Rp {{ number_format($order->total_price, 0, ',', '.') }}</span>
                    </div>
                    
                    @if($order->shipping_cost > 0)
                    <div class="flex justify-between text-blue-600">
                        <span>Ongkos Kirim</span>
                        <span>+ Rp {{ number_format($order->shipping_cost, 0, ',', '.') }}</span>
                    </div>
                    @endif

                    @if($order->discount_amount > 0)
                    <div class="flex justify-between text-emerald-600 font-bold">
                        <span>Diskon Kupon</span>
                        <span>- Rp {{ number_format($order->discount_amount, 0, ',', '.') }}</span>
                    </div>
                    @endif

                    <div class="border-t border-dashed border-slate-300 my-2 pt-2">
                        <div class="flex justify-between items-end">
                            <span class="font-bold text-slate-800">Total Bayar</span>
                            <span class="text-2xl font-black text-red-600">Rp {{ number_format($order->final_price, 0, ',', '.') }}</span>
                        </div>
                        <div class="text-right mt-1">
                            <span class="text-xs font-bold text-slate-400 uppercase">Via {{ strtoupper($order->payment_method) }}</span>
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