@extends('layouts.customer')
@section('title', 'Laporan Pesanan Marketplace')

@push('styles')
<style>
    .address-icon {
        width: 1.25rem; height: 1.25rem; flex-shrink: 0;
        display: flex; align-items: center; justify-content: center;
        border-radius: 50%; color: white;
    }
    .icon-send { background-color: #3B82F6; }
    .icon-receive { background-color: #8B5CF6; }
</style>
@endpush

@section('content')
<div class="py-12 bg-gray-50 min-h-screen">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

        {{-- Header & Tombol Kembali --}}
        <div class="flex flex-col md:flex-row justify-between md:items-center gap-4 mb-6">
            <div>
                <h2 class="text-2xl font-bold text-gray-800">
                    Laporan Pesanan Marketplace
                </h2>
                <p class="mt-1 text-sm text-gray-600">
                    Semua pesanan produk yang masuk melalui checkout.
                </p>
            </div>
            <div class="flex-shrink-0">
                 <a href="{{ route('seller.dashboard') }}" class="inline-flex items-center px-4 py-2.5 bg-gray-800 rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 transition shadow-sm">
                    &larr; Kembali ke Dashboard
                </a>
            </div>
        </div>

        {{-- Card Utama --}}
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-gray-200">

            {{-- Form Pencarian --}}
            <div class="p-6 border-b border-gray-100 bg-gray-50/50">
                <form action="{{ route('seller.pesanan.marketplace.index') }}" method="GET">
                    <div class="flex flex-col md:flex-row gap-2">
                        <input type="text" name="search"
                               class="block w-full md:w-1/3 rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 text-sm"
                               placeholder="Cari No. Invoice atau Nama Pelanggan..."
                               value="{{ request('search') }}">
                        <button type="submit"
                                class="inline-flex justify-center items-center px-5 py-2.5 bg-indigo-600 rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 transition shadow-sm">
                            <i class="fas fa-search mr-2"></i> Cari
                        </button>
                    </div>
                </form>
            </div>

            {{-- Header Tabel --}}
            <div class="hidden lg:grid grid-cols-12 gap-4 px-6 py-3 bg-indigo-50 border-b border-indigo-100 text-left text-xs font-bold text-indigo-800 uppercase tracking-wider">
                <div class="col-span-1">No</div>
                <div class="col-span-2">Transaksi</div>
                <div class="col-span-3">Alamat</div>
                <div class="col-span-2">Ekspedisi & Ongkir</div>
                <div class="col-span-2">Isi Paket</div>
                <div class="col-span-2">Status</div>
            </div>

            {{-- Body Tabel (Loop) --}}
            <div class="bg-white divide-y divide-gray-200">
                @forelse ($orders as $order)
                    <div class="p-6 hover:bg-indigo-50/30 transition-colors duration-200">
                        <div class="grid grid-cols-1 lg:grid-cols-12 gap-x-4 gap-y-6 text-sm">

                            {{-- NO --}}
                            <div class="lg:col-span-1">
                                <span class="lg:hidden font-bold text-gray-500 text-xs">NO: </span>
                                <span class="text-gray-900 font-bold">{{ $loop->iteration + $orders->firstItem() - 1 }}</span>
                            </div>

                            {{-- TRANSAKSI --}}
                            <div class="lg:col-span-2 space-y-1">
                                <div class="font-bold text-blue-600 uppercase text-xs">{{ $order->payment_method }}</div>
                                <div class="text-gray-900 font-bold">{{ $order->invoice_number }}</div>
                                <div class="text-xs text-gray-500">{{ $order->created_at->format('d M Y H:i') }}</div>
                                <div class="text-[10px] text-gray-400 mt-1 uppercase tracking-wider">Dibuat oleh: <br><span class="text-gray-600 font-semibold">{{ $order->store->name ?? 'Toko' }}</span></div>
                            </div>

                            {{-- ALAMAT --}}
                            <div class="lg:col-span-3 space-y-3">
                                {{-- Pengirim (Toko Anda) --}}
                                <div class="flex gap-3">
                                    <div class="address-icon icon-send shadow-sm"><i class="fas fa-store text-[10px]"></i></div>
                                    <div>
                                        @if($order->store)
                                            <div class="font-bold text-gray-900 text-xs">{{ $order->store->name ?? 'Toko Pengirim' }} <span class="text-gray-400 font-normal">/ {{ $order->store->user->no_wa ?? '' }}</span></div>
                                            <div class="text-[11px] text-gray-600 leading-tight mt-0.5">{{ $order->store->address_detail ?? 'Alamat Toko' }}</div>
                                        @else
                                            <div class="font-bold text-red-500 text-xs">Toko Tidak Ditemukan</div>
                                        @endif
                                    </div>
                                </div>
                                {{-- Penerima (Customer) --}}
                                <div class="flex gap-3">
                                    <div class="address-icon icon-receive shadow-sm"><i class="fas fa-user text-[10px]"></i></div>
                                    <div>
                                        <div class="font-bold text-gray-900 text-xs">{{ $order->user->nama_lengkap ?? 'Customer' }} <span class="text-gray-400 font-normal">/ {{ $order->user->no_wa ?? '' }}</span></div>
                                        <div class="text-[11px] text-gray-600 leading-tight mt-0.5">{{ $order->shipping_address }}</div>
                                    </div>
                                </div>
                            </div>

                            {{-- EKSPEDISI & ONGKIR --}}
                            <div class="lg:col-span-2 space-y-1">
                                @php
                                    $shippingParts = explode('-', $order->shipping_method);
                                    $courier = $shippingParts[1] ?? 'N/A';
                                    $service = $shippingParts[2] ?? 'N/A';
                                @endphp
                                <div class="font-bold text-gray-800 uppercase text-xs border-b border-gray-100 pb-1 w-max">{{ $courier }} - {{ $service }}</div>
                                <div class="font-bold text-green-600 text-xs pt-1">Rp {{ number_format($order->shipping_cost) }}</div>

                                @if($order->cod_fee > 0)
                                    @if(strtolower($order->payment_method) == 'cod')
                                    <div class="text-[10px] text-orange-600 font-semibold">Tagihan COD: Rp {{ number_format($order->total_amount) }}</div>
                                    @elseif($order->cod_fee > 0)
                                    <div class="text-[10px] text-orange-600 font-semibold">Biaya COD: Rp {{ number_format($order->cod_fee) }}</div>
                                    @endif
                                @endif

                                <div class="text-[10px] text-gray-500 mt-2 bg-gray-50 p-1 rounded border border-gray-200 w-max break-all">
                                    <span class="font-bold">Resi:</span> {{ $order->shipping_reference ?? '-' }}
                                </div>
                            </div>

                            {{-- ISI PAKET --}}
                            <div class="lg:col-span-2 space-y-2">
                                @php
                                    $totalWeight = 0;
                                    $firstItem = $order->items->first();
                                    $dimension = 'Dimensi: -';
                                @endphp
                                <div class="bg-gray-50 rounded p-2 border border-gray-100 max-h-24 overflow-y-auto custom-scrollbar">
                                    @foreach($order->items as $item)
                                        @php
                                            $totalWeight += ($item->product->weight ?? 0) * $item->quantity;
                                            if($loop->first && $item->product) {
                                                $dimension = "Dim. " . ($item->product->length ?? '5') . "x" . ($item->product->width ?? '5') . "x" . ($item->product->height ?? '5') . " cm";
                                            }
                                        @endphp
                                        <div class="text-[10px] mb-1.5 last:mb-0 border-b border-gray-200 last:border-0 pb-1.5 last:pb-0">
                                            <div class="font-bold text-gray-800 truncate" title="{{ $item->product->name ?? 'Produk' }}">{{ $item->product->name ?? 'Produk Dihapus' }} ({{$item->quantity}}x)</div>
                                            <div class="text-blue-600 font-semibold">Rp {{ number_format($item->price) }}</div>
                                        </div>
                                    @endforeach
                                </div>
                                <div class="flex flex-wrap gap-1 text-[9px] font-bold text-gray-500">
                                    <span class="bg-gray-100 px-1.5 py-0.5 rounded">{{ number_format($totalWeight) }} gr</span>
                                    <span class="bg-gray-100 px-1.5 py-0.5 rounded">{{ $dimension }}</span>
                                </div>
                            </div>

                            {{-- STATUS & TOMBOL KOMPLAIN --}}
                            {{-- STATUS & TOMBOL AKSI --}}
                            <div class="lg:col-span-2 flex flex-col items-start lg:items-end">
                                <span class="px-3 py-1 inline-flex text-[11px] leading-5 font-bold rounded-full border shadow-sm {{ $order->status_badge_class }}">
                                    {{ Str::title($order->status) }}
                                </span>

                                @php
                                    $escrow = \App\Models\Escrow::where('order_id', $order->id)->first();
                                    $isMediasi = $escrow && $escrow->status_dana === 'mediasi';
                                    $statusOrder = strtolower($order->status);
                                    $isRetur = in_array($statusOrder, ['returning', 'return_approved', 'returned']);
                                @endphp

                                {{-- JIKA STATUS RETUR --}}
                                @if($isRetur)
                                    @php
                                        $ship = \App\Helpers\ShippingHelper::parseShippingMethod($order->shipping_courier ?? $order->expedition ?? '');
                                        $returData = [
                                            'store_name' => $order->store->name ?? 'Toko Tidak Diketahui',
                                            'store_address' => $order->store->address_detail ?? '-',
                                            'buyer_name' => $order->user->nama_lengkap ?? 'Pembeli Tidak Diketahui',
                                            'buyer_address' => $order->shipping_address ?? '-',
                                            'courier' => $ship['courier_name'] ?? $order->shipping_courier ?? 'Kurir',
                                            'service' => $ship['service_name'] ?? '-',
                                            'logo' => $ship['logo_url'] ?? '',
                                            'resi' => $order->shipping_reference ?? 'Belum ada resi',
                                            'cost' => number_format($order->shipping_cost ?? 0, 0, ',', '.'),
                                            'date' => $order->created_at ? $order->created_at->format('d M Y, H:i') : '-',
                                            'track_url' => $order->shipping_reference ? route('tracking.index', ['resi' => $order->shipping_reference]) : '#'
                                        ];
                                    @endphp
                                    <div class="mt-3 w-full">
                                        <button onclick="openReturModal({{ htmlspecialchars(json_encode($returData)) }})" class="w-full bg-teal-50 hover:bg-teal-100 border border-teal-200 text-teal-700 text-[10px] font-bold py-2 rounded-lg transition-colors flex items-center justify-center shadow-sm">
                                            <i class="fas fa-box-open mr-1.5 text-teal-500"></i> Info Retur
                                        </button>
                                    </div>
                                @endif

                                {{-- JIKA ADA MASALAH (MEDIASI / RETUR / REFUND PENDING) --}}
                                @if($isMediasi || $isRetur || ($escrow && $escrow->status_dana === 'refund_pending'))
                                    <div class="mt-2 w-full">
                                        <button onclick="openKomplainModal('{{ $order->invoice_number }}', '{{ addslashes($order->user->nama_lengkap ?? 'Pembeli') }}')" class="w-full bg-red-50 hover:bg-red-100 border border-red-200 text-red-600 text-[10px] font-bold py-2 rounded-lg transition-colors flex items-center justify-center shadow-sm">
                                            <i class="fas fa-comments mr-1.5 text-red-500 animate-pulse"></i> Pusat Resolusi
                                        </button>
                                    </div>
                                @endif
                            </div>

                        </div>
                    </div>
                @empty
                    <div class="p-12 text-center">
                        <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-box-open text-gray-400 text-3xl"></i>
                        </div>
                        <h3 class="text-lg font-bold text-gray-700">Tidak ada pesanan</h3>
                        <p class="text-sm text-gray-500 mt-1">
                            @if(request('search'))
                                Pesanan dengan keyword "{{ request('search') }}" tidak ditemukan.
                            @else
                                Belum ada pesanan produk yang masuk ke toko Anda.
                            @endif
                        </p>
                    </div>
                @endempty
            </div>

            {{-- Pagination --}}
            @if($orders->hasPages())
                <div class="p-4 bg-gray-50 border-t border-gray-200">
                    {{ $orders->links('pagination::tailwind') }}
                </div>
            @endif
        </div>
    </div>
</div>

<div id="returModal" class="fixed inset-0 z-[100] hidden bg-gray-900 bg-opacity-60 backdrop-blur-sm flex items-center justify-center p-4 transition-opacity duration-300">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl overflow-hidden flex flex-col transform transition-all scale-95 opacity-0" id="returModalContent">
        <div class="bg-teal-600 px-5 py-4 flex justify-between items-center text-white shadow-md z-10">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 bg-white/20 rounded-full flex items-center justify-center text-white backdrop-blur-sm">
                    <i class="fas fa-exchange-alt text-sm"></i>
                </div>
                <div>
                    <h3 class="font-bold text-sm leading-tight">Informasi Retur & Pengiriman</h3>
                    <p class="text-[10px] text-teal-100">Cek alamat pengembalian dan lacak paket</p>
                </div>
            </div>
            <button onclick="closeReturModal()" class="text-white hover:text-teal-200 bg-teal-700 hover:bg-teal-800 rounded-full w-8 h-8 flex items-center justify-center transition">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="p-5 bg-gray-50 flex-1 overflow-y-auto max-h-[70vh]">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-5">
                <div class="bg-white p-4 rounded-lg border border-gray-200 shadow-sm">
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-2 border-b border-gray-100 pb-1.5"><i class="fas fa-store mr-1 text-teal-500"></i> Alamat Pengembalian (Toko)</p>
                    <p class="text-sm font-bold text-gray-800" id="rm-store-name"></p>
                    <p class="text-[11px] text-gray-600 mt-1.5 leading-relaxed" id="rm-store-address"></p>
                </div>
                <div class="bg-white p-4 rounded-lg border border-gray-200 shadow-sm">
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-2 border-b border-gray-100 pb-1.5"><i class="fas fa-user mr-1 text-teal-500"></i> Pembeli (Customer)</p>
                    <p class="text-sm font-bold text-gray-800" id="rm-buyer-name"></p>
                    <p class="text-[11px] text-gray-600 mt-1.5 leading-relaxed" id="rm-buyer-address"></p>
                </div>
            </div>

            <div class="bg-white p-4 rounded-lg border border-gray-200 shadow-sm">
                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-3 border-b border-gray-100 pb-1.5"><i class="fas fa-truck-fast mr-1 text-teal-500"></i> Data Ekspedisi Resi Awal</p>
                <div class="flex items-center justify-between flex-wrap gap-4">
                    <div class="flex items-center gap-3">
                        <img id="rm-logo" src="" class="h-10 w-auto object-contain border border-gray-100 rounded p-1 bg-white hidden">
                        <div id="rm-no-logo" class="h-10 w-10 flex items-center justify-center bg-gray-100 text-gray-400 rounded border border-gray-200 text-xs font-bold hidden">IMG</div>
                        <div>
                            <p class="text-sm font-bold text-gray-800 uppercase" id="rm-courier"></p>
                            <p class="text-[10px] text-gray-500 uppercase" id="rm-service"></p>
                        </div>
                    </div>
                    <div class="text-right bg-gray-50 px-3 py-1.5 rounded border border-gray-100">
                        <p class="text-[9px] text-gray-500 uppercase font-bold">Nomor Resi Awal</p>
                        <p class="text-sm font-mono font-bold text-blue-600" id="rm-resi"></p>
                    </div>
                </div>
            </div>

            <div class="mt-5">
                <a href="#" id="rm-track-btn" target="_blank" class="w-full bg-teal-600 hover:bg-teal-700 text-white font-bold py-3 rounded-lg shadow-sm flex items-center justify-center transition-colors">
                    <i class="fas fa-map-marker-alt mr-2"></i> Lacak Resi Pengembalian
                </a>
            </div>
        </div>
    </div>
</div>

<script>
    function openReturModal(data) {
        document.getElementById('rm-store-name').innerText = data.store_name;
        document.getElementById('rm-store-address').innerText = data.store_address;
        document.getElementById('rm-buyer-name').innerText = data.buyer_name;
        document.getElementById('rm-buyer-address').innerText = data.buyer_address;
        document.getElementById('rm-courier').innerText = data.courier;
        document.getElementById('rm-service').innerText = data.service;
        document.getElementById('rm-resi').innerText = data.resi;

        const imgEl = document.getElementById('rm-logo');
        const noImgEl = document.getElementById('rm-no-logo');
        if(data.logo && data.logo !== '') {
            imgEl.src = data.logo;
            imgEl.classList.remove('hidden');
            noImgEl.classList.add('hidden');
        } else {
            imgEl.classList.add('hidden');
            noImgEl.classList.remove('hidden');
        }
        document.getElementById('rm-track-btn').href = data.track_url;

        const modal = document.getElementById('returModal');
        const content = document.getElementById('returModalContent');
        modal.classList.remove('hidden');
        setTimeout(() => {
            content.classList.remove('scale-95', 'opacity-0');
            content.classList.add('scale-100', 'opacity-100');
        }, 50);
    }

    function closeReturModal() {
        const modal = document.getElementById('returModal');
        const content = document.getElementById('returModalContent');
        content.classList.remove('scale-100', 'opacity-100');
        content.classList.add('scale-95', 'opacity-0');
        setTimeout(() => { modal.classList.add('hidden'); }, 300);
    }
</script>

<div id="komplainModal" class="fixed inset-0 z-[99] hidden bg-gray-900 bg-opacity-60 backdrop-blur-sm flex items-center justify-center p-4 transition-opacity duration-300">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden flex flex-col h-[600px] transform transition-all scale-95 opacity-0" id="komplainModalContent">

        <div class="bg-gradient-to-r from-orange-500 to-red-500 px-5 py-3 flex justify-between items-center text-white shadow-md z-20">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 bg-white/20 rounded-full flex items-center justify-center backdrop-blur-sm">
                    <i class="fas fa-headset text-sm"></i>
                </div>
                <div>
                    <h3 class="font-bold text-sm tracking-wide">Pusat Resolusi Penjual</h3>
                    <p class="text-[10px] text-orange-100 uppercase" id="komplainCustomerName">Nama Pembeli</p>
                </div>
            </div>
            <button onclick="closeKomplainModal()" class="text-white hover:text-red-200 hover:bg-white/20 rounded-full w-8 h-8 flex items-center justify-center transition-colors">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="bg-orange-50 px-4 py-2.5 border-b border-orange-200 flex justify-between items-center z-10 shadow-sm">
            <span class="text-[10px] text-orange-800 font-bold"><i class="fas fa-gavel text-orange-500 mr-1"></i> Aksi Penjual:</span>
            <div class="flex gap-2">
                <form id="formApproveRetur" method="POST" onsubmit="return confirm('Anda yakin menyetujui Retur Barang? \n\nSistem akan meminta pembeli memaketkan kembali barangnya.');">
                    @csrf
                    <button type="submit" class="bg-white border border-orange-500 text-orange-600 text-[10px] px-2.5 py-1.5 rounded font-bold hover:bg-orange-100 transition shadow-sm">
                        Setujui Retur Barang
                    </button>
                </form>
                <form id="formApproveRefund" method="POST" onsubmit="return confirm('Anda yakin menyetujui Pengembalian Dana?\n\nAdmin Sancaka akan segera mengembalikan uang ke Saldo Pembeli (Hanya harga barang, ongkir hangus).');">
                    @csrf
                    <button type="submit" class="bg-red-500 text-white text-[10px] px-2.5 py-1.5 rounded font-bold hover:bg-red-600 transition shadow-sm">
                        Kembalikan Dana
                    </button>
                </form>
            </div>
        </div>

        <div id="chatScrollArea" class="flex-1 bg-[#f4f5f7] bg-[url('https://www.transparenttextures.com/patterns/cubes.png')] p-4 overflow-y-auto flex flex-col gap-4">

            <div class="text-center mb-2">
                <span class="bg-white border border-gray-200 text-gray-600 text-[10px] px-3 py-1 rounded-full font-bold shadow-sm inline-flex items-center gap-1">
                    <i class="fas fa-receipt text-gray-400"></i> <span id="komplainInvoice" class="text-orange-600 ml-1"></span>
                </span>
                <p class="text-[10px] text-orange-600 mt-3 font-medium bg-orange-50 p-2.5 rounded-lg border border-orange-200 inline-block shadow-sm">
                    <i class="fas fa-exclamation-circle mr-1"></i> Berikan solusi terbaik agar Admin dapat segera menyelesaikan masalah ini.
                </p>
            </div>

            <div id="chatBoxContent" class="flex flex-col gap-1 mt-2"></div>
        </div>

        <div id="filePreviewContainer" class="hidden bg-gray-50 p-2 border-t border-gray-200 flex items-center justify-between">
            <span class="text-xs text-blue-600 font-medium flex items-center"><i class="fas fa-paperclip mr-2"></i> <span id="fileNameDisplay">File.jpg</span></span>
            <button type="button" onclick="cancelUpload()" class="text-red-500 hover:text-red-700 text-xs"><i class="fas fa-times"></i> Batal</button>
        </div>

        <form onsubmit="sendChatMsg(event)" id="chatForm" class="p-3 border-t border-gray-200 bg-white flex items-center gap-2">
            @csrf

            <input type="file" id="chatAttachmentInput" accept="image/*,video/mp4" class="hidden" onchange="handleFileSelect(this)">

            <button type="button" onclick="document.getElementById('chatAttachmentInput').click()" class="text-gray-400 hover:text-orange-500 transition p-2 bg-gray-50 rounded-full border border-gray-200" title="Lampirkan Foto/Video">
                <i class="fas fa-camera"></i>
            </button>

            <input type="text" id="chatMessageInput" name="message" placeholder="Balas keluhan pembeli..." class="flex-1 border-gray-300 rounded-full text-sm focus:ring-orange-500 focus:border-orange-500 px-4 py-2 bg-gray-50 shadow-inner" autocomplete="off">
            <button type="submit" id="btnSendChat" class="bg-orange-500 text-white w-10 h-10 rounded-full hover:bg-orange-600 transition-colors flex items-center justify-center shadow-md">
                <i class="fas fa-paper-plane"></i>
            </button>
        </form>
    </div>
</div>

<script>
    let currentInvoice = '';

    function openKomplainModal(invoice, customerName) {
        currentInvoice = invoice;
        document.getElementById('komplainInvoice').innerText = invoice;
        document.getElementById('komplainCustomerName').innerText = 'Pembeli: ' + customerName;

        document.getElementById('formApproveRetur').action = `/seller/komplain/retur/${invoice}`;
        document.getElementById('formApproveRefund').action = `/seller/komplain/refund/${invoice}`;

        const modal = document.getElementById('komplainModal');
        const content = document.getElementById('komplainModalContent');

        modal.classList.remove('hidden');
        setTimeout(() => {
            content.classList.remove('scale-95', 'opacity-0');
            content.classList.add('scale-100', 'opacity-100');
            loadChats();
        }, 50);
    }

    function closeKomplainModal() {
        const modal = document.getElementById('komplainModal');
        const content = document.getElementById('komplainModalContent');

        content.classList.remove('scale-100', 'opacity-100');
        content.classList.add('scale-95', 'opacity-0');

        setTimeout(() => {
            modal.classList.add('hidden');
            document.getElementById('chatBoxContent').innerHTML = '';
        }, 300);
    }

    function loadChats() {
        const chatBox = document.getElementById('chatBoxContent');
        chatBox.innerHTML = '<div class="text-center text-xs text-gray-400 my-4"><i class="fas fa-spinner fa-spin"></i> Memuat pesan...</div>';

        // URL GANTI SESUAI ROUTE SELLER MAS
        fetch(`/seller/komplain/chat/${currentInvoice}`)
            .then(res => res.json())
            .then(data => {
                chatBox.innerHTML = '';
                if(data.chats && data.chats.length === 0) {
                    chatBox.innerHTML = '<div class="text-center text-xs text-gray-400 my-4">Belum ada obrolan.</div>';
                } else if(data.chats) {
                    data.chats.forEach(chat => appendChatHTML(chat));
                }
                scrollToBottom();
            })
            .catch(err => {
                chatBox.innerHTML = '<div class="text-center text-xs text-red-500 my-4">Gagal memuat pesan.</div>';
            });
    }

    function sendChatMsg(e) {
        e.preventDefault();
        const input = document.getElementById('chatMessageInput');
        const msg = input.value;
        if(msg.trim() === '') return;

        input.value = '';
        input.disabled = true;

        // URL GANTI SESUAI ROUTE SELLER MAS
        fetch(`{{ route('seller.komplain.send_chat') }}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                invoice_number: currentInvoice,
                message: msg
            })
        })
        .then(res => res.json())
        .then(data => {
            input.disabled = false;
            input.focus();
            if(data.success) {
                appendChatHTML(data.chat);
                scrollToBottom();
            } else {
                alert('Gagal mengirim pesan!');
            }
        });
    }

    function appendChatHTML(chat) {
        const chatBox = document.getElementById('chatBoxContent');
        const isSeller = chat.sender_type === 'seller';

        const dateObj = new Date(chat.created_at);
        const time = dateObj.toLocaleTimeString('id-ID', {hour: '2-digit', minute:'2-digit'});

        let html = '';
        if(isSeller) {
            // Bubble Penjual (Kanan)
            html = `
            <div class="flex items-start justify-end gap-2 mt-2">
                <div class="bg-orange-50 border border-orange-100 rounded-2xl rounded-tr-none p-3 max-w-[80%] shadow-sm">
                    <p class="text-xs text-gray-800 leading-relaxed">${chat.message}</p>
                    <p class="text-[9px] text-gray-400 mt-1 text-right">${time}</p>
                </div>
            </div>`;
        } else {
            // Bubble Pembeli / Admin (Kiri)
            const isAdmin = chat.sender_type === 'admin';
            const icon = isAdmin ? '<i class="fas fa-shield-alt text-blue-500"></i>' : '<i class="fas fa-user text-gray-500"></i>';
            const senderName = isAdmin ? 'Admin Sancaka' : (chat.sender ? chat.sender.nama_lengkap : 'Pembeli');
            const nameColor = isAdmin ? 'text-blue-600' : 'text-gray-600';

            html = `
            <div class="flex items-start gap-2 mt-2">
                <div class="w-8 h-8 rounded-full bg-white flex items-center justify-center flex-shrink-0 shadow-sm border border-gray-200 text-xs">
                    ${icon}
                </div>
                <div class="bg-white border border-gray-200 rounded-2xl rounded-tl-none p-3 max-w-[80%] shadow-sm">
                    <p class="text-[10px] font-bold mb-1 ${nameColor}">${senderName}</p>
                    <p class="text-xs text-gray-800 leading-relaxed">${chat.message}</p>
                    <p class="text-[9px] text-gray-400 mt-1 text-left">${time}</p>
                </div>
            </div>`;
        }

        if(chatBox.innerHTML.includes('Belum ada obrolan')) { chatBox.innerHTML = ''; }
        chatBox.insertAdjacentHTML('beforeend', html);
    }

    function scrollToBottom() {
        const area = document.getElementById('chatScrollArea');
        if(area) area.scrollTop = area.scrollHeight;
    }
</script>
@endsection
