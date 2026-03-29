{{--
File: resources/views/customer/pesanan/riwayat_belanja.blade.php
Updated: Penambahan Modal Retur Ekspedisi + Logic Chat Resolusi
--}}

@extends('layouts.customer')

@section('title', 'Riwayat Belanja')

@section('content')
<div class="bg-gray-50 min-h-screen py-8">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">

        {{-- Header --}}
        <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Riwayat Belanja</h1>
                <p class="mt-2 text-sm text-gray-600">Daftar transaksi marketplace Anda.</p>
            </div>
            <a href="https://tokosancaka.com/etalase" class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 transition shadow-sm">
                <i class="fas fa-plus mr-2"></i> Belanja Lagi
            </a>
        </div>

        {{-- Alert Success/Error untuk Terima Paket --}}
        @if(session('success'))
            <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative shadow-sm">
                <span class="block sm:inline"><i class="fas fa-check-circle mr-1"></i> {{ session('success') }}</span>
            </div>
        @endif
        @if(session('error'))
            <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative shadow-sm">
                <span class="block sm:inline"><i class="fas fa-exclamation-circle mr-1"></i> {{ session('error') }}</span>
            </div>
        @endif

        {{-- Jika Tidak Ada Pesanan --}}
        @if($pesanans->isEmpty())
            <div class="bg-white rounded-xl shadow-sm p-12 text-center border border-dashed border-gray-300">
                <div class="mx-auto w-20 h-20 bg-red-50 rounded-full flex items-center justify-center mb-6">
                    <i class="fas fa-shopping-basket text-red-400 text-3xl"></i>
                </div>
                <h3 class="text-xl font-medium text-gray-900">Belum ada riwayat belanja</h3>
                <p class="text-gray-500 mt-2 mb-6">Sepertinya Anda belum pernah checkout barang apapun.</p>
                <a href="https://tokosancaka.com/etalase" class="text-red-600 hover:text-red-800 font-semibold hover:underline">
                    Cari Produk Sekarang &rarr;
                </a>
            </div>
        @else

            {{-- List Pesanan --}}
            <div class="space-y-6">
                @foreach($pesanans as $order)
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition-shadow duration-300">

                        {{-- 1. HEADER KARTU (LOGO TOKO & STATUS) --}}
                        <div class="px-6 py-4 bg-gray-50 border-b border-gray-100 flex flex-wrap justify-between items-center gap-4">
                            <div class="flex items-center gap-3">
                                @php
                                    $seller = $order->store->user ?? null;
                                    $storeName = $seller->store_name ?? ($order->store->name ?? 'Toko Tidak Dikenal');
                                    $storeLogoRaw = $seller->store_logo_path ?? null;
                                @endphp

                                <div class="w-10 h-10 rounded-full bg-white border border-gray-200 flex-shrink-0 overflow-hidden flex items-center justify-center">
                                    @if($storeLogoRaw)
                                        <img src="{{ asset('public/storage/' . $storeLogoRaw) }}"
                                             alt="{{ $storeName }}"
                                             class="w-full h-full object-cover"
                                             onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                                        <i class="fas fa-store text-red-400 hidden"></i>
                                    @else
                                        <i class="fas fa-store text-red-400"></i>
                                    @endif
                                </div>

                                <div>
                                    <h4 class="font-bold text-gray-800 text-sm flex items-center gap-2">
                                        {{ $storeName }}
                                    </h4>
                                    <p class="text-xs text-gray-500 font-mono mt-0.5">
                                        {{ $order->invoice_number }} • {{ $order->created_at->format('d M Y') }}
                                    </p>
                                </div>
                            </div>

                            @php
                                $status = strtolower($order->status);
                                $badgeClass = match($status) {
                                    'paid', 'completed', 'success', 'lunas' => 'bg-green-100 text-green-800 border-green-200',
                                    'pending', 'unpaid', 'menunggu_pembayaran' => 'bg-yellow-100 text-yellow-800 border-yellow-200',
                                    'processing', 'diproses' => 'bg-blue-100 text-blue-800 border-blue-200',
                                    'shipped', 'dikirim' => 'bg-purple-100 text-purple-800 border-purple-200',
                                    'failed', 'expired', 'batal', 'canceled', 'rejected' => 'bg-red-100 text-red-800 border-red-200',
                                    'returning', 'return_approved', 'returned' => 'bg-teal-100 text-teal-800 border-teal-200',
                                    default => 'bg-gray-100 text-gray-800 border-gray-200'
                                };
                            @endphp
                            <span class="px-3 py-1 rounded-full text-xs font-bold border {{ $badgeClass }}">
                                {{ strtoupper($status) }}
                            </span>
                        </div>

                        {{-- 2. BODY KARTU --}}
                        <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-8">

                            {{-- KOLOM KIRI: PRODUK --}}
                            <div class="md:col-span-2 space-y-4">
                                @php $items = $order->items ?? collect([]); @endphp

                                @foreach($items->take(2) as $item)
                                    <div class="flex items-start gap-4 p-2 hover:bg-gray-50 rounded-lg transition">
                                        {{-- GAMBAR PRODUK --}}
                                        <div class="w-20 h-20 flex-shrink-0 bg-gray-200 rounded-lg overflow-hidden border border-gray-200 relative group">
                                            @if($item->product && !empty($item->product->image_url))
                                                @php
                                                    $rawPath = $item->product->image_url;
                                                    $cleanPath = str_replace('public/', '', $rawPath);
                                                    $imageUrl = asset('public/storage/' . $cleanPath);
                                                @endphp
                                                <img src="{{ $imageUrl }}"
                                                     alt="{{ $item->product->name }}"
                                                     class="w-full h-full object-cover group-hover:scale-105 transition duration-300"
                                                     onerror="this.onerror=null; this.src='https://placehold.co/150?text=No+Pic';">
                                            @else
                                                <div class="w-full h-full flex flex-col items-center justify-center text-gray-400">
                                                    <i class="fas fa-image text-xl mb-1"></i>
                                                    <span class="text-[9px]">No Pic</span>
                                                </div>
                                            @endif
                                        </div>

                                        <div>
                                            <h5 class="text-sm font-bold text-gray-900 line-clamp-2">
                                                {{ $item->product?->name ?? 'Produk Dihapus' }}
                                            </h5>
                                            @if($item->variant)
                                                <p class="text-xs text-gray-500 mt-1">
                                                    Varian: {{ $item->variant->sku_code ?? 'Default' }}
                                                </p>
                                            @endif
                                            <p class="text-xs text-gray-700 font-medium mt-2">
                                                {{ $item->quantity }} x Rp {{ number_format($item->price, 0, ',', '.') }}
                                            </p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            {{-- KOLOM KANAN: PENGIRIMAN & TOTAL --}}
                            <div class="flex flex-col justify-between border-t md:border-t-0 md:border-l border-gray-100 md:pl-8 pt-4 md:pt-0">

                                <div class="mb-4">
                                    <p class="text-xs text-gray-500 uppercase font-bold mb-2 tracking-wider">KURIR PENGIRIMAN</p>

                                    <div class="flex items-center gap-3">
                                        @php
                                            $parts = explode('-', $order->shipping_method);
                                            $courierName = $parts[1] ?? 'Kurir';
                                            $logoExpedition = asset('public/storage/logo-ekspedisi/' . strtolower($courierName) . '.png');
                                        @endphp

                                        <div class="w-12 h-auto bg-white rounded border border-gray-200 p-1">
                                            <img src="{{ $logoExpedition }}"
                                                 alt="{{ $courierName }}"
                                                 class="w-full h-full object-contain"
                                                 onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                                            <i class="fas fa-truck text-gray-400 text-lg hidden text-center w-full mt-1"></i>
                                        </div>

                                        <div>
                                            <p class="text-sm font-bold text-gray-800 uppercase">{{ $courierName }}</p>
                                            <p class="text-[10px] text-gray-500">{{ strtoupper($parts[2] ?? '') }}</p>
                                        </div>
                                    </div>

                                    @php
                                        $resi = $order->shipping_resi ?? ($order->shipping_reference ?? null);
                                    @endphp

                                    @if(!empty($resi) && $resi !== 'NULL')
                                        <div class="mt-3 bg-green-50 border border-green-200 rounded p-2 flex justify-between items-center group cursor-pointer" onclick="navigator.clipboard.writeText('{{ $resi }}'); alert('Resi disalin!')">
                                            <div>
                                                <p class="text-[10px] text-green-700 font-bold uppercase">Nomor Resi / Ref</p>
                                                <p class="text-xs font-mono text-gray-900 font-bold">{{ $resi }}</p>
                                            </div>
                                            <i class="fas fa-copy text-green-400 group-hover:text-green-600"></i>
                                        </div>
                                    @elseif(in_array($status, ['paid', 'processing']))
                                        <div class="mt-3 text-xs text-blue-600 bg-blue-50 p-2 rounded border border-blue-100 flex items-start gap-2">
                                            <i class="fas fa-clock mt-0.5"></i>
                                            <span>Menunggu Resi dari Penjual</span>
                                        </div>
                                    @endif
                                </div>

                                <div>
                                    <div class="flex justify-between items-end mb-4 border-t border-dashed border-gray-200 pt-3">
                                        <span class="text-xs text-gray-500">Total Belanja</span>
                                        <span class="text-lg font-bold text-red-600">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</span>
                                    </div>

                                    <div class="grid gap-2">
                                        @if(in_array($status, ['pending', 'unpaid', 'menunggu_pembayaran']))
                                            @if(!empty($order->invoice_number))
                                                <a href="{{ route('checkout.invoice', ['invoice' => $order->invoice_number]) }}" class="block w-full text-center bg-red-600 text-white text-sm font-bold py-2.5 rounded-lg hover:bg-red-700 transition shadow-lg shadow-red-200">
                                                    Bayar Sekarang
                                                </a>
                                            @endif
                                        @elseif(!empty($resi))
                                            {{-- Lacak Paket --}}
                                            <a href="{{ route('tracking.index', ['resi' => $resi]) }}" class="block w-full text-center border border-red-600 text-red-600 text-sm font-bold py-2.5 rounded-lg hover:bg-red-50 transition">
                                                Lacak Paket Awal
                                            </a>

                                            {{-- === CEK STATUS DANA ESCROW === --}}
                                            @php
                                                $escrow = \App\Models\Escrow::where('order_id', $order->id)->first();
                                                $isMediasi = $escrow && $escrow->status_dana === 'mediasi';
                                                $isCair = $escrow && $escrow->status_dana === 'dicairkan';
                                            @endphp

                                            {{-- === TOMBOL TERIMA PAKET & KOMPLAIN === --}}
                                            @if(in_array($status, ['shipped', 'dikirim', 'completed', 'selesai']))
                                                <div class="grid grid-cols-2 gap-2 mt-1">
                                                    <form action="{{ route('customer.pesanan.terima', $order->id ?? 0) }}" method="POST">
                                                        @csrf
                                                        <button type="submit"
                                                                {{ $isCair ? 'disabled' : '' }}
                                                                onclick="return confirm('Apakah Anda yakin paket sudah diterima dengan baik? \n\nDana akan langsung diteruskan ke saldo penjual dan tidak dapat dikembalikan.');"
                                                                class="w-full {{ $isCair ? 'bg-gray-400 cursor-not-allowed' : 'bg-green-500 hover:bg-green-600' }} text-white text-[11px] font-bold py-2.5 rounded-lg transition flex items-center justify-center shadow-sm">
                                                            <i class="fas fa-check-circle mr-1"></i> {{ $isCair ? 'Selesai' : 'Terima' }}
                                                        </button>
                                                    </form>

                                                    <button type="button"
                                                            {{ $isCair ? 'disabled' : '' }}
                                                            @if(isset($returKirimData)) data-retur="{{ json_encode($returKirimData) }}" @endif
                                                            onclick="openKomplainModal('{{ $order->invoice_number }}', '{{ addslashes($storeName) }}', this)"
                                                            class="w-full border {{ $isCair ? 'border-gray-300 text-gray-400 bg-gray-50 cursor-not-allowed' : 'border-orange-500 text-orange-500 hover:bg-orange-50' }} text-[11px] font-bold py-2.5 rounded-lg transition flex items-center justify-center shadow-sm">
                                                        <i class="fas fa-headset mr-1"></i> Komplain
                                                    </button>
                                                </div>
                                            @endif

                                            {{-- === CEK DATA RETUR === --}}
                                            @php
                                                $returnOrder = \App\Models\ReturnOrder::where('order_id', $order->id)->first();
                                            @endphp

                                            {{-- Jika Penjual Setuju Retur tapi Pembeli belum input resi --}}
                                            @if(in_array($status, ['returning', 'return_approved']) && !$returnOrder)
                                                @php
                                                    $weight = $order->items->sum(function($item) { return ($item->product->weight ?? 1000) * $item->quantity; });
                                                    $itemPrice = $order->items->sum(function($item) { return $item->price * $item->quantity; });

                                                    $returKirimData = [
                                                        'invoice' => $order->invoice_number,
                                                        'old_resi' => $order->shipping_reference ?? '-',
                                                        'buyer_name' => Auth::user()->nama_lengkap ?? '',
                                                        'buyer_phone' => Auth::user()->no_wa ?? '08000000000',
                                                        'buyer_address' => $order->shipping_address ?? '',
                                                        'store_name' => $storeName,
                                                        'store_phone' => $seller->no_wa ?? '08000000000',
                                                        'store_address' => $seller->address_detail ?? 'Alamat Toko',
                                                        'weight' => $weight > 0 ? $weight : 1000,
                                                        'item_price' => $itemPrice > 0 ? $itemPrice : 10000,
                                                    ];
                                                @endphp
                                                <button type="button"
                                                        data-retur="{{ json_encode($returKirimData) }}"
                                                        onclick="openKirimReturModal(this)"
                                                        class="w-full mt-2 bg-teal-600 hover:bg-teal-700 text-white text-[11px] font-bold py-2.5 rounded-lg transition shadow-sm flex items-center justify-center animate-pulse">
                                                    <i class="fas fa-truck-loading mr-1.5"></i> Input Resi Retur
                                                </button>
                                            @endif

                                            {{-- Jika Pembeli sudah input resi retur --}}
                                            @if($returnOrder)
                                                @php
                                                    $shipInfo = \App\Helpers\ShippingHelper::parseShippingMethod($returnOrder->courier);
                                                    $infoReturData = [
                                                        'store_name' => $storeName,
                                                        'store_address' => $seller->address_detail ?? '-',
                                                        'buyer_name' => Auth::user()->nama_lengkap ?? 'Pembeli',
                                                        'buyer_address' => $order->shipping_address ?? '-',
                                                        'courier' => strtoupper($returnOrder->courier),
                                                        'service' => 'REGULER',
                                                        'logo' => $shipInfo['logo_url'] ?? '',
                                                        'resi' => $returnOrder->new_resi,
                                                        'cost' => number_format($returnOrder->shipping_cost, 0, ',', '.'),
                                                        'date' => $returnOrder->created_at->format('d M Y, H:i'),
                                                        'track_url' => route('tracking.index', ['resi' => $returnOrder->new_resi])
                                                    ];
                                                @endphp
                                                <button type="button"
                                                        data-info="{{ json_encode($infoReturData) }}"
                                                        onclick="openReturModal(this)"
                                                        class="w-full mt-2 bg-teal-50 border border-teal-200 hover:bg-teal-100 text-teal-700 text-[11px] font-bold py-2 rounded-lg transition shadow-sm flex items-center justify-center">
                                                    <i class="fas fa-exchange-alt mr-1.5"></i> Lacak Pengembalian
                                                </button>
                                            @endif

                                            <a href="{{ route('checkout.invoice', ['invoice' => $order->invoice_number]) }}" class="block w-full text-center text-gray-500 text-xs hover:text-gray-700 mt-1">
                                                Lihat Invoice
                                            </a>
                                        @else
                                            <a href="{{ route('checkout.invoice', ['invoice' => $order->invoice_number]) }}" class="block w-full text-center bg-gray-100 text-gray-700 text-sm font-bold py-2.5 rounded-lg hover:bg-gray-200 transition">
                                                Detail Pesanan
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-8">
                {{ $pesanans->links() }}
            </div>
        @endif
    </div>
</div>

<div id="komplainModal" class="fixed inset-0 z-[99] hidden bg-gray-900 bg-opacity-50 flex items-center justify-center p-4 transition-opacity duration-300">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg overflow-hidden flex flex-col h-[600px] transform transition-all scale-95 opacity-0" id="komplainModalContent">

        <div class="bg-red-600 px-4 py-3 flex justify-between items-center text-white shadow-md z-20">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 bg-white rounded-full flex items-center justify-center text-red-600">
                    <i class="fas fa-store text-sm"></i>
                </div>
                <div>
                    <h3 class="font-bold text-sm leading-tight">Pusat Resolusi</h3>
                    <p class="text-[10px] opacity-90" id="komplainStoreName">Nama Toko</p>
                </div>
            </div>
            <button onclick="closeKomplainModal()" class="text-white hover:text-red-200 bg-red-700 hover:bg-red-800 rounded-full w-8 h-8 flex items-center justify-center transition">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="bg-orange-50 px-4 py-2.5 border-b border-orange-200 flex justify-between items-center z-10 shadow-sm">
            <span class="text-[10px] text-orange-800 font-bold"><i class="fas fa-lightbulb text-orange-500 mr-1"></i> Solusi Masalah:</span>
            <div class="flex gap-2">
                <button type="button" id="btnAksiReturChat" class="bg-white border border-red-500 text-red-600 text-[10px] px-2.5 py-1.5 rounded font-bold hover:bg-red-50 transition shadow-sm">
                    Ajukan Retur Paket
                </button>

                <form id="formRetur" method="POST" class="hidden">
                    @csrf
                </form>

                <form id="formSelesai" method="POST" onsubmit="return confirm('Yakin masalah sudah selesai? Dana akan langsung diteruskan ke penjual.');">
                    @csrf
                    <button type="submit" class="bg-green-500 text-white text-[10px] px-2.5 py-1.5 rounded font-bold hover:bg-green-600 transition shadow-sm">Masalah Selesai</button>
                </form>
            </div>
        </div>

        <div id="chatScrollArea" class="flex-1 bg-[url('https://www.transparenttextures.com/patterns/cubes.png')] bg-gray-100 p-4 overflow-y-auto flex flex-col gap-4">
            <div class="text-center mb-2">
                <span class="bg-white border border-gray-200 text-gray-600 text-[10px] px-3 py-1 rounded-full font-bold shadow-sm">
                    Invoice: <span id="komplainInvoice" class="text-blue-600"></span>
                </span>
                <p class="text-[10px] text-red-500 mt-3 font-medium bg-red-50 p-2 rounded-lg border border-red-100 inline-block">
                    <i class="fas fa-shield-alt mr-1"></i> Admin Sancaka memantau obrolan ini.
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

            <button type="button" onclick="document.getElementById('chatAttachmentInput').click()" class="text-gray-400 hover:text-blue-600 transition p-2 bg-gray-50 rounded-full border border-gray-200" title="Lampirkan Foto/Video">
                <i class="fas fa-camera"></i>
            </button>

            <input type="text" id="chatMessageInput" placeholder="Ketik keluhan..." class="flex-1 border-gray-300 rounded-full text-sm focus:ring-red-500 focus:border-red-500 px-4 py-2 bg-gray-50 shadow-inner" autocomplete="off">
            <button type="submit" id="btnSendChat" class="bg-red-600 text-white w-10 h-10 rounded-full hover:bg-red-700 transition flex items-center justify-center shadow-md transform hover:scale-105 flex-shrink-0">
                <i class="fas fa-paper-plane"></i>
            </button>
        </form>
    </div>
</div>

<div id="kirimReturModal" class="fixed inset-0 z-[100] hidden bg-gray-900 bg-opacity-60 backdrop-blur-sm flex items-center justify-center p-4 transition-opacity duration-300">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden flex flex-col transform transition-all scale-95 opacity-0" id="kirimReturModalContent">
        <div class="bg-teal-600 px-5 py-4 flex justify-between items-center text-white shadow-md z-10">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 bg-white/20 rounded-full flex items-center justify-center text-white backdrop-blur-sm"><i class="fas fa-box-open text-sm"></i></div>
                <div><h3 class="font-bold text-sm leading-tight">Pengembalian Barang</h3><p class="text-[10px] text-teal-100">Buat resi otomatis via KiriminAja</p></div>
            </div>
            <button onclick="closeKirimReturModal()" class="text-white hover:text-teal-200 bg-teal-700 hover:bg-teal-800 rounded-full w-8 h-8 flex items-center justify-center transition"><i class="fas fa-times"></i></button>
        </div>

        <form action="{{ route('customer.pesanan.kirim_retur') }}" method="POST" id="formKirimRetur" class="flex-1 overflow-y-auto max-h-[75vh]">
            @csrf

            <input type="hidden" name="invoice_number" id="kr-invoice">
            <input type="hidden" name="sender_name" id="kr-sender-name-input"><input type="hidden" name="sender_phone" id="kr-sender-phone">
            <input type="hidden" name="sender_address" id="kr-sender-address-input"><input type="hidden" name="sender_district_id" id="kr-sender_district_id">
            <input type="hidden" name="sender_subdistrict_id" id="kr-sender_subdistrict_id"><input type="hidden" name="sender_postal_code" id="kr-sender_postal_code">

            <input type="hidden" name="receiver_name" id="kr-receiver-name-input"><input type="hidden" name="receiver_phone" id="kr-receiver-phone">
            <input type="hidden" name="receiver_address" id="kr-receiver-address-input"><input type="hidden" name="receiver_district_id" id="kr-receiver_district_id">
            <input type="hidden" name="receiver_subdistrict_id" id="kr-receiver_subdistrict_id"><input type="hidden" name="receiver_postal_code" id="kr-receiver_postal_code">

            <input type="hidden" name="expedition" id="kr-expedition" required>

            <div class="p-5 space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="bg-gray-50 p-3 rounded-lg border border-gray-200">
                        <p class="text-[10px] font-bold text-teal-600 uppercase tracking-wider mb-1 border-b border-gray-200 pb-1"><i class="fas fa-user mr-1"></i> Pengirim (Anda)</p>
                        <p class="text-xs font-bold text-gray-800 mt-1.5" id="kr-sender-name-display"></p>
                        <p class="text-[10px] text-gray-600 mt-1 leading-relaxed" id="kr-sender-address-display"></p>
                    </div>
                    <div class="bg-gray-50 p-3 rounded-lg border border-gray-200">
                        <p class="text-[10px] font-bold text-orange-600 uppercase tracking-wider mb-1 border-b border-gray-200 pb-1"><i class="fas fa-store mr-1"></i> Penerima (Toko)</p>
                        <p class="text-xs font-bold text-gray-800 mt-1.5" id="kr-receiver-name-display"></p>
                        <p class="text-[10px] text-gray-600 mt-1 leading-relaxed" id="kr-receiver-address-display"></p>
                    </div>
                </div>

                <div class="bg-blue-50 border border-blue-100 p-4 rounded-xl space-y-3 shadow-inner">
                    <label class="block text-[10px] font-bold text-blue-800 uppercase tracking-wider mb-1">Pilih Ekspedisi Retur</label>
                    <div id="retur_ekspedisi_list" class="space-y-2 max-h-48 overflow-y-auto custom-scrollbar pr-1">
                        <div class="text-center text-xs text-blue-500 py-4"><i class="fas fa-spinner fa-spin text-xl mb-2"></i><br>Mencari rute kurir terbaik...</div>
                    </div>

                    <div class="pt-2 border-t border-blue-200">
                        <label class="block text-[10px] font-bold text-blue-800 uppercase tracking-wider mb-1">Metode Pembayaran Ongkir</label>
                        <select name="payment_method" required class="w-full border-blue-200 text-gray-700 text-sm rounded-lg focus:ring-blue-500 bg-white">
                            <option value="saldo">Potong Saldo Sancaka (Tersedia: Rp {{ number_format(Auth::user()->saldo ?? 0, 0, ',', '.') }})</option>
                            <option value="doku">DOKU Payment Gateway</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="bg-white px-5 py-4 border-t border-gray-200 flex justify-end gap-2">
                <button type="button" onclick="closeKirimReturModal()" class="px-4 py-2 text-xs font-bold text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200 transition">Batal</button>
                <button type="submit" onclick="return confirm('Sistem akan membuat Resi Pickup dan memotong biaya ongkir retur. Lanjutkan?')" class="px-5 py-2 text-xs font-bold text-white bg-teal-600 rounded-lg hover:bg-teal-700 shadow-md transition flex items-center">
                    <i class="fas fa-check-circle mr-2"></i> Bayar & Buat Resi
                </button>
            </div>
        </form>
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
                    <h3 class="font-bold text-sm leading-tight">Informasi Pengembalian Barang</h3>
                    <p class="text-[10px] text-teal-100">Cek alamat dan lacak paket retur</p>
                </div>
            </div>
            <button onclick="closeReturModal()" class="text-white hover:text-teal-200 bg-teal-700 hover:bg-teal-800 rounded-full w-8 h-8 flex items-center justify-center transition">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="p-5 bg-gray-50 flex-1 overflow-y-auto max-h-[70vh]">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-5">
                <div class="bg-white p-4 rounded-lg border border-gray-200 shadow-sm">
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-2 border-b border-gray-100 pb-1.5"><i class="fas fa-user mr-1 text-teal-500"></i> Pengirim Retur (Anda)</p>
                    <p class="text-sm font-bold text-gray-800" id="rm-buyer-name"></p>
                    <p class="text-[11px] text-gray-600 mt-1.5 leading-relaxed" id="rm-buyer-address"></p>
                </div>
                <div class="bg-white p-4 rounded-lg border border-gray-200 shadow-sm">
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-2 border-b border-gray-100 pb-1.5"><i class="fas fa-store mr-1 text-teal-500"></i> Tujuan Retur (Toko)</p>
                    <p class="text-sm font-bold text-gray-800" id="rm-store-name"></p>
                    <p class="text-[11px] text-gray-600 mt-1.5 leading-relaxed" id="rm-store-address"></p>
                </div>
            </div>

            <div class="bg-white p-4 rounded-lg border border-gray-200 shadow-sm">
                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-3 border-b border-gray-100 pb-1.5"><i class="fas fa-truck-fast mr-1 text-teal-500"></i> Ekspedisi Pengembalian</p>
                <div class="flex items-center justify-between flex-wrap gap-4">
                    <div class="flex items-center gap-3">
                        <img id="rm-logo" src="" class="h-10 w-auto object-contain border border-gray-100 rounded p-1 bg-white hidden">
                        <div id="rm-no-logo" class="h-10 w-10 flex items-center justify-center bg-gray-100 text-gray-400 rounded border border-gray-200 text-xs font-bold hidden">IMG</div>
                        <div>
                            <p class="text-sm font-bold text-gray-800 uppercase" id="rm-courier"></p>
                            <p class="text-[10px] text-gray-500 uppercase" id="rm-service"></p>
                        </div>
                    </div>
                    <div class="text-right bg-teal-50 px-3 py-1.5 rounded border border-teal-100">
                        <p class="text-[9px] text-teal-600 uppercase font-bold">Resi Baru</p>
                        <p class="text-sm font-mono font-bold text-teal-700" id="rm-resi"></p>
                    </div>
                </div>
            </div>

            <div class="mt-5">
                <a href="#" id="rm-track-btn" target="_blank" class="w-full bg-teal-600 hover:bg-teal-700 text-white font-bold py-3 rounded-lg shadow-sm flex items-center justify-center transition-colors">
                    <i class="fas fa-search-location mr-2"></i> Lacak Paket Sekarang
                </a>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    let currentInvoice = '';

    // ==========================================
    // 1. LOGIKA CHAT KOMPLAIN & RESOLUSI
    // ==========================================
    function openKomplainModal(invoice, storeName, btnElement = null) {
        currentInvoice = invoice;
        document.getElementById('komplainInvoice').innerText = invoice;
        document.getElementById('komplainStoreName').innerText = storeName;

        document.getElementById('formSelesai').action = `/customer/komplain/selesai/${invoice}`;
        document.getElementById('formRetur').action = `/customer/komplain/retur/${invoice}`;

        const btnReturChat = document.getElementById('btnAksiReturChat');
        const newBtnReturChat = btnReturChat.cloneNode(true);
        btnReturChat.parentNode.replaceChild(newBtnReturChat, btnReturChat);

        if (btnElement && btnElement.hasAttribute('data-retur')) {
            newBtnReturChat.innerText = "Input Resi Retur";
            newBtnReturChat.classList.replace('text-red-600', 'text-teal-600');
            newBtnReturChat.classList.replace('border-red-500', 'border-teal-500');

            newBtnReturChat.onclick = function() {
                closeKomplainModal();
                openKirimReturModal(btnElement);
            };
        } else {
            newBtnReturChat.innerText = "Ajukan Retur Paket";
            newBtnReturChat.classList.replace('text-teal-600', 'text-red-600');
            newBtnReturChat.classList.replace('border-teal-500', 'border-red-500');

            newBtnReturChat.onclick = function() {
                if(confirm('Yakin ingin mengajukan Retur / Kembalikan Barang?')) {
                    document.getElementById('formRetur').submit();
                }
            };
        }

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
        cancelUpload();
        setTimeout(() => {
            modal.classList.add('hidden');
            document.getElementById('chatBoxContent').innerHTML = '';
        }, 300);
    }

    // ==========================================
    // 2. LOGIKA INPUT RESI RETUR (KIRIMINAJA)
    // ==========================================

    // Fungsi untuk melacak ID Wilayah di background (Geocoding KiriminAja)
    async function fetchKiriminAjaAreaId(addressString, prefix) {
        try {
            const response = await fetch(`{{ route('api.address.search') }}?search=${encodeURIComponent(addressString)}`);
            const data = await response.json();

            if (data && data.length > 0) {
                const item = data[0]; // Ambil hasil pertama yang paling relevan

                // Set Hidden Input sesuai format create.blade.php
                document.getElementById(`kr-${prefix}_district_id`).value = item.district_id || (item.data_lengkap ? item.data_lengkap.district_id : '');
                document.getElementById(`kr-${prefix}_subdistrict_id`).value = item.subdistrict_id || (item.data_lengkap ? item.data_lengkap.subdistrict_id : '');

                // Set Detail Wilayah
                const d = item.data_lengkap || {};
                document.getElementById(`kr-${prefix}_village`).value = d.village || '';
                document.getElementById(`kr-${prefix}_district`).value = d.district || '';
                document.getElementById(`kr-${prefix}_regency`).value = d.regency || '';
                document.getElementById(`kr-${prefix}_province`).value = d.province || '';
                document.getElementById(`kr-${prefix}_postal_code`).value = d.postal_code || '';
            } else {
                console.warn(`Data wilayah ${prefix} tidak ditemukan dari API.`);
            }
        } catch (error) {
            console.error(`Gagal Fetch Wilayah ${prefix}:`, error);
        }
    }

    // FUNGSI PENCARIAN ID WILAYAH OTOMATIS
    async function fetchKiriminAjaAreaId(addressString, prefix) {
        try {
            const response = await fetch(`{{ route('api.address.search') }}?search=${encodeURIComponent(addressString)}`);
            const data = await response.json();

            if (data && data.length > 0) {
                const item = data[0];
                const d = item.data_lengkap || {};
                document.getElementById(`kr-${prefix}_district_id`).value = item.district_id || d.district_id || '';
                document.getElementById(`kr-${prefix}_subdistrict_id`).value = item.subdistrict_id || d.subdistrict_id || '';
                document.getElementById(`kr-${prefix}_postal_code`).value = d.postal_code || '';
            }
        } catch (error) { console.error(`Geocode Error:`, error); }
    }

    // FUNGSI CEK ONGKIR OTOMATIS SAAT BUKA MODAL
    async function fetchOngkirRetur(weight, itemPrice) {
        const senderSub = document.getElementById('kr-sender_subdistrict_id').value;
        const senderDist = document.getElementById('kr-sender_district_id').value;
        const receiverSub = document.getElementById('kr-receiver_subdistrict_id').value;
        const receiverDist = document.getElementById('kr-receiver_district_id').value;
        const listContainer = document.getElementById('retur_ekspedisi_list');

        if(!senderSub || !receiverSub) {
            listContainer.innerHTML = '<div class="text-xs text-red-500 font-bold p-3 bg-red-50 rounded">⚠️ Gagal melacak Kode Pos alamat Anda / Toko. Harap ubah alamat terlebih dahulu.</div>';
            return;
        }

        const params = new URLSearchParams({
            sender_district_id: senderDist, sender_subdistrict_id: senderSub,
            receiver_district_id: receiverDist, receiver_subdistrict_id: receiverSub,
            weight: weight, item_price: itemPrice, service_type: 'regular',
            item_type: 1, ansuransi: 'tidak', _token: '{{ csrf_token() }}'
        });

        try {
            const response = await fetch(`{{ route('kirimaja.cekongkir') }}?${params.toString()}`);
            const res = await response.json();

            let results = [];
            if (res.body && res.body.results) { results = res.body.results; }
            else if (res.results) { results = res.results; }
            else if (Array.isArray(res)) { results = res; }

            listContainer.innerHTML = '';
            if(results.length === 0) {
                listContainer.innerHTML = '<div class="text-xs text-red-500 font-bold p-3">Tidak ada kurir tersedia untuk rute ini.</div>';
                return;
            }

            results.sort((a, b) => parseFloat(a.cost) - parseFloat(b.cost)).forEach(item => {
                const cost = parseFloat(item.cost) || 0;
                const serviceCode = item.service.toLowerCase();
                const logoUrl = `{{ asset('public/storage/logo-ekspedisi/') }}/${serviceCode.replace(/\s+/g, '')}.png`;
                const valueStr = `regular-${serviceCode}-${item.service_type}-${cost}-0-0`;

                listContainer.insertAdjacentHTML('beforeend', `
                    <label class="flex items-center justify-between p-3 border border-gray-200 rounded-lg cursor-pointer hover:bg-blue-50 hover:border-blue-400 transition bg-white shadow-sm">
                        <div class="flex items-center gap-3">
                            <input type="radio" name="pilih_kurir_retur" value="${valueStr}" required class="w-4 h-4 text-blue-600 focus:ring-blue-500" onchange="document.getElementById('kr-expedition').value = this.value;">
                            <div class="w-12 h-8 flex items-center justify-center border border-gray-100 rounded bg-white p-1">
                                <img src="${logoUrl}" class="max-h-full object-contain" onerror="this.src='https://placehold.co/50x20?text=${serviceCode}'">
                            </div>
                            <div>
                                <div class="text-[11px] font-bold text-gray-800 uppercase">${item.service_name}</div>
                                <div class="text-[9px] text-gray-500">Estimasi: ${item.etd ? item.etd.replace('Hari','') + ' Hari' : '-'}</div>
                            </div>
                        </div>
                        <div class="text-xs font-bold text-red-600">Rp ${cost.toLocaleString('id-ID')}</div>
                    </label>
                `);
            });
        } catch(e) {
            listContainer.innerHTML = '<div class="text-xs text-red-500">Gagal memuat ongkir dari KiriminAja.</div>';
        }
    }

    // TRIGGER BUKA MODAL DARI TOMBOL CHAT
    function openKirimReturModal(btn) {
        const data = JSON.parse(btn.getAttribute('data-retur'));

        // Isi Visual Form
        document.getElementById('kr-invoice').value = data.invoice;
        document.getElementById('kr-sender-name-display').innerText = data.buyer_name;
        document.getElementById('kr-sender-address-display').innerText = data.buyer_address;
        document.getElementById('kr-receiver-name-display').innerText = data.store_name;
        document.getElementById('kr-receiver-address-display').innerText = data.store_address;

        // Isi Hidden Inputs utk Database
        document.getElementById('kr-sender-name-input').value = data.buyer_name;
        document.getElementById('kr-sender-address-input').value = data.buyer_address;
        document.getElementById('kr-sender-phone').value = data.buyer_phone;
        document.getElementById('kr-receiver-name-input').value = data.store_name;
        document.getElementById('kr-receiver-address-input').value = data.store_address;
        document.getElementById('kr-receiver-phone').value = data.store_phone;

        Swal.fire({ title: 'Mencari Kurir Terbaik...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });

        // Tarik ID Wilayah -> Lalu Cek Ongkir -> Baru Buka Modal
        Promise.all([
            fetchKiriminAjaAreaId(data.buyer_address, 'sender'),
            fetchKiriminAjaAreaId(data.store_address, 'receiver')
        ]).then(() => {
            fetchOngkirRetur(data.weight, data.item_price).then(() => {
                Swal.close();
                const modal = document.getElementById('kirimReturModal');
                const content = document.getElementById('kirimReturModalContent');
                modal.classList.remove('hidden');
                setTimeout(() => {
                    content.classList.remove('scale-95', 'opacity-0');
                    content.classList.add('scale-100', 'opacity-100');
                }, 50);
            });
        });
    }

    function closeKirimReturModal() {
        const modal = document.getElementById('kirimReturModal');
        const content = document.getElementById('kirimReturModalContent');
        content.classList.remove('scale-100', 'opacity-100');
        content.classList.add('scale-95', 'opacity-0');
        setTimeout(() => { modal.classList.add('hidden'); }, 300);
    }

    // Logika Konfirmasi Bayar & Buat Resi Retur
    document.getElementById('formKirimRetur').addEventListener('submit', function(e) {
        e.preventDefault();

        const districtSender = document.getElementById('kr-sender_district_id').value;
        const districtReceiver = document.getElementById('kr-receiver_district_id').value;

        if(!districtSender || !districtReceiver) {
            Swal.fire('Error Wilayah', 'Sistem gagal mendeteksi kode pos / kecamatan alamat Anda atau Toko. Silakan hubungi Admin.', 'error');
            return false;
        }

        Swal.fire({
            title: 'Buat Resi Retur?',
            text: "Sistem akan memotong saldo Anda atau mengarahkan ke DOKU untuk biaya ongkir retur otomatis dari KiriminAja.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#0D9488',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Ya, Bayar & Buat Resi'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({ title: 'Memproses...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); }});
                this.submit();
            }
        });
    });

    // ==========================================
    // 3. LOGIKA LACAK RETUR (INFO MODAL)
    // ==========================================
    function openReturModal(btn) {
        const data = JSON.parse(btn.getAttribute('data-info'));

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

    // ==========================================
    // 4. LOGIKA UPLOAD & AJAX CHAT
    // ==========================================
    function handleFileSelect(input) {
        const preview = document.getElementById('filePreviewContainer');
        const nameDisplay = document.getElementById('fileNameDisplay');
        if(input.files && input.files[0]) {
            nameDisplay.innerText = input.files[0].name;
            preview.classList.remove('hidden');
            preview.classList.add('flex');
        }
    }

    function cancelUpload() {
        document.getElementById('chatAttachmentInput').value = '';
        const preview = document.getElementById('filePreviewContainer');
        preview.classList.add('hidden');
        preview.classList.remove('flex');
    }

    function loadChats() {
        const chatBox = document.getElementById('chatBoxContent');
        chatBox.innerHTML = '<div class="text-center text-xs text-gray-400 my-4"><i class="fas fa-spinner fa-spin"></i> Memuat pesan...</div>';

        fetch(`/customer/komplain/chat/${currentInvoice}`)
            .then(res => res.json())
            .then(data => {
                chatBox.innerHTML = '';
                if(data.chats && data.chats.length === 0) {
                    chatBox.innerHTML = '<div class="text-center text-xs text-gray-400 my-4">Belum ada obrolan. Silakan mulai percakapan.</div>';
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
        const inputMsg = document.getElementById('chatMessageInput');
        const inputFile = document.getElementById('chatAttachmentInput');
        const btnSend = document.getElementById('btnSendChat');

        if(inputMsg.value.trim() === '' && inputFile.files.length === 0) return;

        let formData = new FormData();
        formData.append('invoice_number', currentInvoice);
        formData.append('message', inputMsg.value);
        formData.append('_token', '{{ csrf_token() }}');
        if(inputFile.files.length > 0) {
            formData.append('attachment', inputFile.files[0]);
        }

        inputMsg.disabled = true;
        btnSend.disabled = true;
        btnSend.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

        fetch(`{{ route('customer.komplain.send_chat') }}`, {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            inputMsg.disabled = false;
            btnSend.disabled = false;
            btnSend.innerHTML = '<i class="fas fa-paper-plane"></i>';
            inputMsg.focus();

            if(data.success) {
                inputMsg.value = '';
                cancelUpload();
                appendChatHTML(data.chat);
                scrollToBottom();
            } else {
                alert('Gagal mengirim pesan: ' + (data.error || 'Kesalahan server'));
            }
        })
        .catch(err => {
            inputMsg.disabled = false;
            btnSend.disabled = false;
            btnSend.innerHTML = '<i class="fas fa-paper-plane"></i>';
            alert('Terjadi kesalahan jaringan/server.');
        });
    }

    function appendChatHTML(chat) {
        const chatBox = document.getElementById('chatBoxContent');
        const isCustomer = chat.sender_type === 'customer';
        const isAdmin = chat.sender_type === 'admin';

        const dateObj = new Date(chat.created_at);
        const time = dateObj.toLocaleTimeString('id-ID', {hour: '2-digit', minute:'2-digit'});

        let mediaHtml = '';
        if(chat.attachment) {
            const fileUrl = `{{ asset('storage') }}/${chat.attachment}`;
            const ext = chat.attachment.split('.').pop().toLowerCase();
            if(ext === 'mp4' || ext === 'mov') {
                mediaHtml = `<video controls class="max-w-full h-auto rounded-lg mt-2 mb-1 border border-gray-200 shadow-sm" style="max-height: 200px;"><source src="${fileUrl}" type="video/${ext}"></video>`;
            } else {
                mediaHtml = `<img src="${fileUrl}" class="max-w-full h-auto rounded-lg mt-2 mb-1 border border-gray-200 shadow-sm cursor-pointer" style="max-height: 200px;" onclick="window.open(this.src, '_blank')">`;
            }
        }

        let html = '';
        if(isCustomer) {
            html = `
            <div class="flex items-start justify-end gap-2 mt-2">
                <div class="bg-red-50 border border-red-100 rounded-2xl rounded-tr-none p-3 max-w-[85%] shadow-sm">
                    ${mediaHtml}
                    ${chat.message ? `<p class="text-xs text-gray-800 leading-relaxed break-words">${chat.message}</p>` : ''}
                    <p class="text-[9px] text-gray-400 mt-1 text-right">${time}</p>
                </div>
            </div>`;
        } else {
            const icon = isAdmin ? '<i class="fas fa-shield-alt text-blue-500"></i>' : '<i class="fas fa-store text-orange-500"></i>';
            const senderName = isAdmin ? 'Admin Sancaka' : (chat.sender ? chat.sender.nama_lengkap : 'Penjual');
            const nameColor = isAdmin ? 'text-blue-600' : 'text-orange-600';

            html = `
            <div class="flex items-start gap-2 mt-2">
                <div class="w-8 h-8 rounded-full bg-white flex items-center justify-center flex-shrink-0 shadow-sm border border-gray-200 text-xs">
                    ${icon}
                </div>
                <div class="bg-white border border-gray-200 rounded-2xl rounded-tl-none p-3 max-w-[85%] shadow-sm">
                    <p class="text-[10px] font-bold mb-1 ${nameColor}">${senderName}</p>
                    ${mediaHtml}
                    ${chat.message ? `<p class="text-xs text-gray-800 leading-relaxed break-words">${chat.message}</p>` : ''}
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
