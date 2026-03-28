{{--
File: resources/views/customer/pesanan/riwayat_belanja.blade.php
Updated: Penambahan Tombol Terima Paket & Chat Komplain
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

        {{-- Jika Tidak Ada Pesanan --}}
        @if($pesanans->isEmpty())
            <div class="bg-white rounded-xl shadow-sm p-12 text-center border border-dashed border-gray-300">
                <div class="mx-auto w-20 h-20 bg-red-50 rounded-full flex items-center justify-center mb-6">
                    <i class="fas fa-shopping-basket text-red-400 text-3xl"></i>
                </div>
                <h3 class="text-xl font-medium text-gray-900">Belum ada riwayat belanja</h3>
                <p class="text-gray-500 mt-2 mb-6">Sepertinya Anda belum pernah checkout barang apapun.</p>
                <a href="{{ route('katalog.index') }}" class="text-red-600 hover:text-red-800 font-semibold hover:underline">
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
                                    'failed', 'expired', 'batal' => 'bg-red-100 text-red-800 border-red-200',
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
                                                Lacak Paket
                                            </a>

                                            {{-- === TOMBOL TERIMA PAKET & KOMPLAIN === --}}
                                            @if(in_array($status, ['shipped', 'dikirim', 'completed', 'selesai']))
                                                <div class="grid grid-cols-2 gap-2 mt-1">
                                                    <form action="{{ route('customer.pesanan.terima', $order->id ?? 0) }}" method="POST">
                                                        @csrf
                                                        <button type="submit" onclick="return confirm('Apakah Anda yakin paket sudah diterima dengan baik? \n\nDana akan langsung diteruskan ke saldo penjual dan tidak dapat dikembalikan.');" class="w-full bg-green-500 text-white text-[11px] font-bold py-2.5 rounded-lg hover:bg-green-600 transition flex items-center justify-center shadow-sm">
                                                            <i class="fas fa-check-circle mr-1"></i> Terima
                                                        </button>
                                                    </form>

                                                    <button type="button" onclick="openKomplainModal('{{ $order->invoice_number }}', '{{ $storeName }}')" class="w-full border border-orange-500 text-orange-500 text-[11px] font-bold py-2.5 rounded-lg hover:bg-orange-50 transition flex items-center justify-center shadow-sm">
                                                        <i class="fas fa-headset mr-1"></i> Komplain
                                                    </button>
                                                </div>
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
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg overflow-hidden flex flex-col h-[550px] transform transition-all scale-95 opacity-0" id="komplainModalContent">

        <div class="bg-red-600 px-4 py-3 flex justify-between items-center text-white shadow-md z-10">
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

        <div class="flex-1 bg-[url('https://www.transparenttextures.com/patterns/cubes.png')] bg-gray-100 p-4 overflow-y-auto flex flex-col gap-4">

            <div class="text-center mb-2">
                <span class="bg-white border border-gray-200 text-gray-600 text-[10px] px-3 py-1 rounded-full font-bold shadow-sm">
                    Invoice: <span id="komplainInvoice" class="text-blue-600"></span>
                </span>
                <p class="text-[10px] text-red-500 mt-3 font-medium bg-red-50 p-2 rounded-lg border border-red-100 inline-block">
                    <i class="fas fa-shield-alt mr-1"></i> Admin Sancaka memantau obrolan ini sebagai penengah.
                </p>
            </div>

            <div class="flex items-start gap-2">
                <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center flex-shrink-0 text-blue-500 shadow-sm border border-blue-200">
                    <i class="fas fa-robot text-xs"></i>
                </div>
                <div class="bg-white border border-gray-200 rounded-2xl rounded-tl-none p-3 max-w-[80%] shadow-sm">
                    <p class="text-xs text-gray-800 leading-relaxed">Halo kak! Silakan jelaskan kendala yang dialami. Sertakan foto/video *unboxing* paket sebagai bukti agar penjual dan Admin dapat membantu dengan cepat.</p>
                    <p class="text-[9px] text-gray-400 mt-2 text-right">Sistem Otomatis</p>
                </div>
            </div>

            </div>

        <form action="#" method="POST" class="p-3 border-t border-gray-200 bg-white flex items-center gap-2">
            @csrf
            <button type="button" class="text-gray-400 hover:text-red-600 transition p-2 bg-gray-50 rounded-full border border-gray-200" title="Lampirkan Foto/Video">
                <i class="fas fa-paperclip"></i>
            </button>
            <input type="text" name="message" placeholder="Ketik keluhan Anda di sini..." class="flex-1 border-gray-300 rounded-full text-sm focus:ring-red-500 focus:border-red-500 px-4 py-2 bg-gray-50 shadow-inner" required>
            <button type="submit" class="bg-red-600 text-white w-10 h-10 rounded-full hover:bg-red-700 transition flex items-center justify-center shadow-md transform hover:scale-105">
                <i class="fas fa-paper-plane"></i>
            </button>
        </form>
    </div>
</div>

<script>
    function openKomplainModal(invoice, storeName) {
        document.getElementById('komplainInvoice').innerText = invoice;
        document.getElementById('komplainStoreName').innerText = storeName;

        const modal = document.getElementById('komplainModal');
        const content = document.getElementById('komplainModalContent');

        modal.classList.remove('hidden');
        // Sedikit delay untuk efek animasi masuk
        setTimeout(() => {
            content.classList.remove('scale-95', 'opacity-0');
            content.classList.add('scale-100', 'opacity-100');
        }, 50);
    }

    function closeKomplainModal() {
        const modal = document.getElementById('komplainModal');
        const content = document.getElementById('komplainModalContent');

        content.classList.remove('scale-100', 'opacity-100');
        content.classList.add('scale-95', 'opacity-0');

        // Tunggu animasi selesai baru sembunyikan modal
        setTimeout(() => {
            modal.classList.add('hidden');
        }, 300);
    }
</script>
@endsection
