{{-- Halaman ini menampilkan data gabungan dari tabel 'orders' dan 'Pesanan' --}}
@extends('layouts.admin')

@push('styles')
<style>
/* 1. Paksa kontainer utama (yang berisi sidebar dan konten) mengambil lebar penuh */
.main-layout-container {
    width: 133.33vw; /* Mengatasi lebar kanan kosong global (jika belum di body) */
}

/* 2. Targetkan DIV yang menampung konten utama (di sebelah kanan sidebar) */
/* Di layout Anda, ini adalah div dengan class="flex-1 flex flex-col overflow-hidden" */
/* Kita akan membuat class kustom untuk menargetkannya: */
.content-wrapper-fixed {
    /* Wajib untuk memastikan bagian konten mengambil seluruh lebar yang tersisa dari sidebar */
    width: 100%;
}


    /* CSS untuk efek Zoom Barcode */
    .barcode-zoomed {
        /* PENTING: Posisikan di atas semua elemen lain */
        position: fixed !important; 
        top: 50% !important;
        left: 50% !important;
        /* Perbesar 10x dari ukuran aslinya */
        transform: translate(-50%, -50%) scale(2) !important; 
        z-index: 1000 !important;
        background-color: white; /* Beri background putih agar jelas */
        padding: 10px;
        border: 2px solid #3b82f6; /* Border biru agar terlihat */
        box-shadow: 0 10px 15px rgba(0, 0, 0, 0.5);
        border-radius: 8px;
    }

</style>
@endpush

@section('title', 'Data Pesanan Masuk')
@section('page-title', 'Data Pesanan Masuk')


@section('content')
{{--
  CATATAN: Halaman Pesanan cenderung memiliki konten panjang (banyak baris),
  sehingga TIDAK memerlukan CSS height: 133.33vh. Skala 75% dari body sudah cukup.
--}}
<div class="bg-white shadow border border-gray-200 rounded-lg overflow-hidden">
    
    {{-- HEADER: Pencarian & Tombol Export --}}
    <div class="flex flex-col md:flex-row items-center justify-between border-b border-gray-200 p-4 gap-3">
        {{-- Form Pencarian --}}
        <form action="{{ route('admin.orders.index') }}" method="GET" class="w-full md:max-w-md">
            <div class="relative">
                <input type="text" name="search" placeholder="Cari Resi, Invoice, Nama..."
                    value="{{ request('search') }}"
                    class="w-full pl-10 pr-4 py-2 text-sm border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 shadow-sm">
                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
            </div>
            {{-- Menyimpan filter status saat melakukan pencarian --}}
            @if (request('status'))
                <input type="hidden" name="status" value="{{ request('status') }}">
            @endif
        </form>

        {{-- Tombol Export --}}
        <button type="button" data-bs-toggle="modal" data-bs-target="#exportModal"
            class="inline-flex items-center justify-center w-full md:w-auto px-4 py-2 bg-gray-200 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-300 transition duration-150 ease-in-out whitespace-nowrap">
            <i class="fas fa-file-export mr-2"></i> Export
        </button>
    </div>

    {{-- FILTER STATUS --}}
    <div class="p-4 border-b border-gray-200 flex flex-wrap gap-2">
        @php
            // Definisikan filter tab
            $statusFilters = [
                'pending' => 'Menunggu Bayar',
                'menunggu-pickup' => 'Menunggu Pickup',
                'diproses' => 'Diproses',
                'terkirim' => 'Terkirim',
                'selesai' => 'Selesai', 
                'batal' => 'Batal',
            ];
            $currentStatus = request('status');
        @endphp

        {{-- Tombol "Semua" --}}
        <a href="{{ route('admin.orders.index', request()->except('status', 'page')) }}"
            class="px-3 py-1 text-sm font-medium rounded-full transition duration-150 ease-in-out focus:outline-none whitespace-nowrap
            {{ !$currentStatus ? 'bg-green-600 text-white shadow' : 'bg-gray-200 text-gray-600 hover:bg-gray-300' }}">
            Semua
        </a>

        {{-- Loop Tombol Filter Dinamis --}}
        @foreach ($statusFilters as $key => $label)
            <a href="{{ route('admin.orders.index', array_merge(request()->query(), ['status' => $key, 'page' => 1])) }}"
                class="px-3 py-1 text-sm font-medium rounded-full transition duration-150 ease-in-out focus:outline-none whitespace-nowrap
                {{ $currentStatus == $key ? 'bg-blue-600 text-white shadow' : 'bg-gray-200 text-gray-600 hover:bg-gray-300' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>

    {{-- TABEL --}}
    {{-- Memastikan wrapper overflow-x-auto mencakup seluruh lebar --}}
    <div class="w-full overflow-x-auto">
        {{-- TAMBAH w-full agar tabel memanjang penuh secara horizontal di dalam wrapper --}}
        <table class="w-full text-sm text-gray-700 divide-y divide-gray-200">
            <thead class="bg-red-100">
                <tr>
                    {{-- Ganti sticky top-0 dengan kelas CSS standar untuk header --}}
                    {{-- Kolom Non-Sticky --}}
                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider whitespace-nowrap">No</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider min-w-[100px] whitespace-nowrap">ID</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider min-w-[100px] whitespace-nowrap">Tipe</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider min-w-[250px] whitespace-nowrap"><strong>Transaksi</strong></th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider min-w-[300px] whitespace-nowrap"><strong>Alamat</strong></th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider min-w-[180px] whitespace-nowrap"><strong>Ekspedisi & Ongkir</strong></th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider min-w-[200px] whitespace-nowrap">Resi</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider min-w-[200px] whitespace-nowrap"><strong>Isi Paket</strong></th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider min-w-[200px] whitespace-nowrap">Tanggal</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider min-w-[150px] whitespace-nowrap"><strong>Status</strong></th>
                    
                    {{-- Kolom Aksi (Sticky Kanan) --}}
                    {{-- PENTING: Gunakan z-10 agar aksi berada di atas kolom lain saat scroll --}}
                    <th scope="col" class="sticky right-0 z-10 bg-red-100 px-4 py-3 text-right text-xs font-medium text-gray-600 uppercase tracking-wider min-w-[170px] whitespace-nowrap border-l border-gray-300"><strong>Aksi</strong></th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($orders as $index => $order)
                    @php
                        $isPesanan = isset($order->status_pesanan);
                        
                        // --- Blok Inisialisasi Variabel (disimpan untuk kejelasan) ---
                        if ($isPesanan) {
                            $id = $order->id_pesanan;
                            $invoice = $order->nomor_invoice;
                            $resi = $order->resi_aktual ?? $order->resi;
                            $paymentMethod = $order->payment_method;
                            $shippingMethodString = $order->jasa_ekspedisi_aktual ?? $order->expedition;
                            $paket = $order->item_description ?? 'N/A';
                            $userId = $order->customer_id ?? $order->id_pengguna_pembeli;
                            $senderName = $order->sender_name ?? 'N/A';
                            $senderAddress = $order->sender_address ?? 'N/A';
                            $receiverName = $order->receiver_name ?? $order->nama_pembeli ?? 'N/A';
                            $receiverAddress = $order->receiver_address ?? $order->alamat_pengiriman ?? 'N/A';
                            $createdAt = $order->created_at ?? $order->tanggal_pesanan;
                            $shippedAt = $order->shipped_at;
                            $finishedAt = $order->finished_at;
                            $statusRaw = $order->status_pesanan;
                            $statusMapPesanan = [
                                'Menunggu Pickup' => 'menunggu-pickup',
                                'Sedang Dikirim' => 'terkirim',
                                'Selesai' => 'selesai',
                                'Batal' => 'batal',
                            ];
                            $status = $statusMapPesanan[$statusRaw] ?? strtolower($statusRaw);
                            $canCancel = in_array($statusRaw, ['Menunggu Pickup']);
                            
                            $totalAmount = $order->price ?? 0;
                            $subtotal = $order->item_price ?? 0;
                            $shippingCost = $order->shipping_cost ?? 0;
                            $codFee = $order->cod_fee ?? 0;
                            $insuranceCost = $order->insurance_cost ?? 0;
                        } else { // Jika data berasal dari tabel 'orders'
                            $id = $order->id;
                            $invoice = $order->invoice_number;
                            $resi = $order->shipping_reference;
                            $paymentMethod = $order->payment_method;
                            $shippingMethodString = $order->shipping_method;
                            $item = $order->items->first();
                            $paket = $item && $item->product ? $item->product->name : ($item && $item->variant ? $item->variant->combination_string : 'N/A');
                            $userId = $order->user_id;
                            $senderName = $order->store ? $order->store->name : 'N/A';
                            $senderAddress = $order->store ? $order->store->address_detail : 'N/A';
                            $receiverName = $order->user ? $order->user->nama_lengkap : 'N/A';
                            $receiverAddress = $order->shipping_address ?? 'N/A';
                            $createdAt = $order->created_at;
                            $shippedAt = $order->shipped_at;
                            $finishedAt = $order->finished_at;
                            $statusRaw = $order->status;
                            $statusMapOrder = [
                                'shipment' => 'terkirim',
                                'processing' => 'diproses',
                                'paid' => 'menunggu-pickup',
                                'completed' => 'selesai',
                            ];
                            $status = $statusMapOrder[$statusRaw] ?? $statusRaw;
                            $canCancel = in_array($statusRaw, ['pending', 'paid', 'processing']);
                            $totalAmount = $order->total_amount;
                            $shippingCost = $order->shipping_cost;
                            $subtotal = $order->subtotal;
                            $codFee = $order->cod_fee;
                            $insuranceCost = $order->insurance_cost ?? 0;
                        }
                        
                        $ship = \App\Helpers\ShippingHelper::parseShippingMethod($shippingMethodString);

                        // Mapping Badge Status
                        $badgeMap = [
                            'pending' => 'bg-yellow-100 text-yellow-800',
                            'menunggu-pickup' => 'bg-yellow-100 text-yellow-800',
                            'diproses' => 'bg-blue-100 text-blue-800',
                            'terkirim' => 'bg-green-100 text-green-800',
                            'selesai' => 'bg-green-100 text-green-800',
                            'completed' => 'bg-green-100 text-green-800',
                            'batal' => 'bg-red-100 text-red-800',
                            'cancelled' => 'bg-red-100 text-red-800',
                            'failed' => 'bg-red-100 text-red-800',
                            'rejected' => 'bg-red-100 text-red-800',
                        ];
                        
                        // Mapping Teks Status
                        $textMap = [
                            'pending' => 'Menunggu Bayar',
                            'menunggu-pickup' => 'Menunggu Pickup',
                            'diproses' => 'Diproses',
                            'terkirim' => 'Terkirim',
                            'selesai' => 'Selesai',
                            'completed' => 'Selesai',
                            'batal' => 'Batal',
                            'cancelled' => 'Dibatalkan',
                            'failed' => 'Gagal',
                            'rejected' => 'Ditolak',
                        ];

                        // Tentukan badge dan teks, utamakan mapping $status lalu $statusRaw
                        $statusBadge = $badgeMap[$status] ?? $badgeMap[$statusRaw] ?? 'bg-gray-100 text-gray-800';
                        $statusText = $textMap[$status] ?? $textMap[$statusRaw] ?? ucfirst($statusRaw);
                        // --- Akhir Blok Inisialisasi ---
                    @endphp
                    
                    <tr class="group hover:bg-gray-50">
                        {{-- Kolom No --}}
                        <td class="px-4 py-4 whitespace-nowrap text-gray-500 align-top">{{ $orders->firstItem() + $index }}</td>
                        
                        {{-- Kolom ID --}}
                        <td class="px-4 py-4 whitespace-nowrap text-gray-500 align-top">{{ $id }}</td>
                        
                        {{-- Kolom Tipe --}}
                        <td class="px-4 py-4 whitespace-nowrap align-top">
                            @if ($isPesanan)
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">Pesanan</span>
                            @else
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Order</span>
                            @endif
                        </td>
                        
                        {{-- Kolom Transaksi --}}
                        <td class="px-4 py-4 align-top">
                            @if(Str::contains(strtoupper($paymentMethod ?? ''), 'COD'))
                                <span class="font-bold text-green-600">COD</span>
                            @else
                                <span class="font-bold text-blue-600">Non COD</span>
                            @endif
                            <div class="font-bold text-gray-800">{{ $invoice }}</div>

                            {{-- Rincian Biaya --}}
                            <div class="text-xs mt-2 space-y-0.5 text-gray-600 w-52">
                                <div class="flex justify-between">
                                    <span>Nilai Barang:</span>
                                    <span class="font-medium">Rp{{ number_format($subtotal ?? 0, 0, ',', '.') }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>Ongkir:</span>
                                    <span class="font-medium">Rp{{ number_format($shippingCost ?? 0, 0, ',', '.') }}</span>
                                </div>
                                
                                @if ($insuranceCost > 0)
                                    <div class="flex justify-between">
                                    <span>Asuransi:</span>
                                    <span class="font-medium">Rp{{ number_format($insuranceCost, 0, ',', '.') }}</span>
                                    </div>
                                @endif
                                    
                                @if ($codFee > 0)
                                <div class="flex justify-between">
                                    <span>Biaya COD:</span>
                                    <span class="font-medium">Rp{{ number_format($codFee, 0, ',', '.') }}</span>
                                </div>
                                @endif
                                <div class="flex justify-between font-bold pt-0.5 border-t border-gray-200 mt-1">
                                    <span>Total Tagihan:</span>
                                    <span class="text-blue-700"><strong>Rp{{ number_format($totalAmount ?? 0, 0, ',', '.') }}</strong></span>
                                </div>
                            </div>
                        </td>
                        
                        {{-- Kolom Alamat --}}
                        <td class="px-4 py-4 align-top">
                            <div class="mb-2">
                                <div class="text-xs text-gray-500">Dari:</div>
                                <div class="font-semibold text-blue-700"><strong>{{ $senderName }}</strong></div>
                                <div class="text-xs text-gray-600 break-words max-w-xs">{{ $senderAddress }}</div>
                            </div>
                            <div>
                                <div class="text-xs text-gray-500">Kepada:</div>
                                <div class="font-semibold text-red-700"><strong>{{ $receiverName }}</strong></div>
                                <div class="text-xs text-gray-600 break-words max-w-xs">{{ $receiverAddress }}</div>
                            </div>
                        </td>
                        
                        {{-- Kolom Ekspedisi --}}
                        <td class="px-4 py-4 align-top whitespace-nowrap">
                            @if ($ship['logo_url'])
                                <img src="{{ $ship['logo_url'] }}" alt="{{ $ship['courier_name'] }}" class="h-5 mb-1 max-w-[90px] object-contain">
                            @else
                                <div class="font-bold text-gray-800">{{ $ship['courier_name'] }}</div>
                            @endif
                            <div class="text-xs text-gray-500">Layanan: {{ $ship['service_name'] }}</div>
                            <div class="font-semibold text-green-700 mt-1">Rp{{ number_format($shippingCost ?? 0, 0, ',', '.') }}</div>
                        </td>
                        
                        {{-- Kolom Resi --}}
{{-- Kolom Resi (Disesuaikan untuk Zoom Tanpa Modal) --}}
<td class="px-4 py-4 align-top">
    @if ($resi)
        {{-- Gunakan Div atau Span sebagai target klik, bukan A, agar tidak ada hash URL --}}
        <div id="barcode-{{ $id }}"
           class="clickable-zoom-barcode cursor-pointer hover:opacity-75 transition-opacity"
           data-resi="{{ $resi }}"
           data-target="barcode-{{ $id }}">
            
            {{-- 1. Tampilkan Teks Resi --}}
            <div class="font-medium text-gray-800 break-all max-w-[180px]">{{ $resi }}</div>

            {{-- 2. Tampilkan Barcode 2D (ukuran kecil) --}}
            <div class="mt-2 barcode-svg-container inline-block">
                {{-- Gunakan dimensi yang lebih kecil (misalnya 5, 5) --}}
                {!! DNS2D::getBarcodeSVG($resi, 'DATAMATRIX', 5, 5) !!}
            </div>
        </div>
    @else
        <span class="text-gray-400 italic">Belum ada resi</span>
    @endif
</td>
                        
                        {{-- Kolom Paket --}}
                        <td class="px-4 py-4 align-top">
                            <div class="font-semibold text-gray-800 break-words max-w-xs">{{ $paket }}</div>
                            @if($isPesanan)
                            <div class="text-xs text-gray-500 mt-1">
                                Berat: {{ $order->weight ?? 0 }} gr | Dimensi: {{ $order->length ?? 0 }}x{{ $order->width ?? 0 }}x{{ $order->height ?? 0 }} cm
                            </div>
                            @elseif(isset($order->items) && $item = $order->items->first()) {{-- Assign $item here --}}
                            <div class="text-xs text-gray-500 mt-1">
                                @if($item->product)
                                Berat: {{ ($item->product->weight ?? 0) * ($item->quantity ?? 1) }} gr | 
                                Dimensi: {{ $item->product->length ?? 0 }}x{{ $item->product->width ?? 0 }}x{{ $item->product->height ?? 0 }} cm
                                @else
                                Berat: ? gr | Dimensi: ?x?x? cm
                                @endif
                            </div>
                            @endif
                        </td>
                        
                        {{-- Kolom Tanggal --}}
                        <td class="px-4 py-4 align-top whitespace-nowrap text-gray-500">
                            <div>
                                <span class="text-gray-400">Dibuat:</span>
                                {{ $createdAt ? \Carbon\Carbon::parse($createdAt)->translatedFormat('d M Y, H:i') : '-' }}
                            </div>
                            <div>
                                <span class="text-gray-400">Dikirim:</span>
                                {{ $shippedAt ? \Carbon\Carbon::parse($shippedAt)->translatedFormat('d M Y, H:i') : '-' }}
                            </div>
                            <div>
                                <span class="text-gray-400">Selesai:</span>
                                {{ $finishedAt ? \Carbon\Carbon::parse($finishedAt)->translatedFormat('d M Y, H:i') : '-' }}
                            </div>
                        </td>
                        
                        {{-- Kolom Status --}}
                        <td class="px-4 py-4 align-top whitespace-nowrap">
                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full {{ $statusBadge }}">
                                {{ $statusText }}
                            </span>
                        </td>
                        
                        {{-- Kolom Aksi (Sticky Kanan) --}}
                        <td class="sticky right-0 z-10 bg-white group-hover:bg-gray-50 px-6 py-4 align-top whitespace-nowrap border-l border-gray-200">
                            <div class="flex items-center space-x-3">
                                @if($resi)
                                <a href="{{ 'https://tokosancaka.com/tracking/search?resi=' . e($resi) }}" target="_blank"
                                    class="text-gray-500 hover:text-green-600" title="Lacak Resi">
                                    <i class="fas fa-truck fa-fw"></i>
                                </a>
                                @endif
                                
                                @if ($isPesanan)
                                    <a href="{{ route('admin.pesanan.show', $invoice) }}"
                                       class="text-gray-500 hover:text-indigo-600" title="Detail">
                                        <i class="fas fa-eye fa-fw"></i>
                                    </a>
                                @else
                                    <a href="{{ route('admin.orders.show', $invoice) }}"
                                       class="text-gray-500 hover:text-indigo-600" title="Detail">
                                        <i class="fas fa-eye fa-fw"></i>
                                    </a>
                                @endif
                                
                                <a href="{{ route('admin.orders.print.thermal', $invoice) }}" target="_blank"
                                    class="text-gray-500 hover:text-gray-800" title="Cetak Label">
                                    <i class="fas fa-print fa-fw"></i>
                                </a>
                                <a href="{{ route('admin.orders.invoice.pdf', $invoice) }}" target="_blank"
                                    class="text-gray-500 hover:text-red-600" title="PDF Faktur">
                                    <i class="fas fa-file-pdf fa-fw"></i>
                                </a>
                                <a href="#" {{-- route('admin.orders.edit', $invoice) --}}
                                    class="text-gray-500 hover:text-blue-600" title="Edit">
                                    <i class="fas fa-pencil-alt fa-fw"></i>
                                </a>
                                @if ($userId)
                                <a href="{{ route('admin.chat.start', ['id_pengguna' => $userId]) }}" target="_blank"
                                    class="text-gray-500 hover:text-blue-600" title="Chat">
                                    <i class="fas fa-comment fa-fw"></i>
                                </a>
                                @endif
                                <form action="{{ route('admin.orders.cancel', $invoice) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan pesanan ini?');" class="inline-block">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit"
                                                class="text-gray-500 hover:text-red-600 {{ !$canCancel ? 'opacity-40 cursor-not-allowed' : '' }}"
                                                title="Batalkan" @disabled(!$canCancel)>
                                            <i class="fas fa-trash-alt fa-fw"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11" class="text-center py-4 text-gray-500">
                            Data pesanan tidak ditemukan.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div> {{-- Akhir dari wrapper overflow-x-auto --}}

    {{-- PAGINATION --}}
    @if ($orders->hasPages())
        <div class="mt-4 p-4 border-t border-gray-200">
            {{ $orders->links() }}
        </div>
    @endif
</div>

    {{-- Modal Export Laporan (dari file partials) --}}
    @include('layouts.partials.modals.export', [
    'excel_route' => route('admin.pesanan.export.excel'),
    'pdf_route' => route('admin.orders.report.pdf')
    ])
    
  {{-- Modal Barcode Zoom (Tailwind CSS) --}}
<div id="barcodeModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="barcodeModalLabel" aria-modal="true" role="dialog">
    {{-- Background Overlay --}}
    <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity" aria-hidden="true"></div>

    {{-- Modal Panel: Pastikan Lebar Cukup (misal max-w-lg) --}}
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="relative bg-white rounded-lg shadow-xl w-full max-w-lg mx-auto transform transition-all">

            {{-- Modal Header --}}
            <div class="flex justify-between items-start p-5 border-b rounded-t">
                <h3 class="text-xl font-semibold text-gray-900" id="barcodeModalLabel">
                    Barcode Resi: <span id="modalResiNumber"></span>
                </h3>
                <button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm p-1.5 ml-auto inline-flex items-center modal-close-btn" data-modal-hide="barcodeModal">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
                </button>
            </div>

            {{-- Modal Body: Gunakan padding vertikal besar (py-12) agar ada ruang untuk zoom --}}
            <div class="p-6 space-y-6 text-center">
                {{-- Container yang menahan SVG yang di-zoom --}}
                {{-- Tambahkan ketinggian (h-80) agar SVG tidak menabrak teks di bawah --}}
                <div id="modalBarcodeContainer" class="flex justify-center items-center h-80"> 
                    </div>
                <div id="resiTextZoom" class="font-bold text-lg text-gray-700"></div>
            </div>

            {{-- Modal Footer --}}
            <div class="flex items-center p-6 space-x-2 border-t border-gray-200 rounded-b justify-end">
                <button data-modal-hide="barcodeModal" type="button" class="modal-close-btn text-gray-500 bg-white hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-blue-300 rounded-lg border border-gray-200 text-sm font-medium px-5 py-2.5 hover:text-gray-900 focus:z-10">Tutup</button>
            </div>
        </div>
    </div>
</div>
    
@endsection

@push('scripts')
{{-- Sertakan jQuery dan Toastr --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">

<script>
    $(document).ready(function() {

        // --- KONFIGURASI TOASTR & NOTIFIKASI ---
        toastr.options = { /* ... konfigurasi ... */ };
        @if (session('success')) toastr.success("{{ session('success') }}", "Berhasil!"); @endif
        // ... (sisanya dari kode notifikasi Anda) ...

        // --- FUNGSI ZOOM BARCODE (Tanpa Modal) ---
        $(document).on('click', '.clickable-zoom-barcode', function(e) {
            e.stopPropagation(); // Mencegah event menyebar ke body
            
            const targetDiv = $(this).find('.barcode-svg-container');
            
            if (targetDiv.hasClass('barcode-zoomed')) {
                // Jika sudah di-zoom, kembalikan ke ukuran normal
                targetDiv.removeClass('barcode-zoomed');
            } else {
                // Hapus semua zoom yang mungkin aktif
                $('.barcode-zoomed').removeClass('barcode-zoomed');
                
                // Terapkan zoom pada elemen yang diklik
                targetDiv.addClass('barcode-zoomed');

                // Opsional: Atur ulang preserveAspectRatio agar rasio tetap benar saat zoom (terkadang perlu)
                targetDiv.find('svg').attr('preserveAspectRatio', 'xMidYMid meet');
            }
        });

        // Event untuk mengembalikan ukuran jika klik di luar barcode
        $(document).on('click', 'body', function(e) {
            // Pastikan klik terjadi di luar area barcode yang di-zoom
            if (!$(e.target).closest('.clickable-zoom-barcode').length) {
                $('.barcode-zoomed').removeClass('barcode-zoomed');
            }
        });
        
    });
</script>
@endpush