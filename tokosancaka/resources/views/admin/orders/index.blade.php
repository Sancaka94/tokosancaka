{{-- Halaman ini menampilkan data gabungan dari tabel 'orders' dan 'Pesanan' --}}
@extends('layouts.admin')

@push('styles')
<style>
    /* ✅ PERBAIKAN: Hapus width paksa, ganti dengan ini */
    
    /* CSS untuk efek Zoom Barcode */
    .barcode-zoomed {
        position: fixed !important; 
        top: 50% !important;
        left: 50% !important;
        transform: translate(-50%, -50%) scale(2) !important; 
        z-index: 1000 !important;
        background-color: white; 
        padding: 10px;
        border: 2px solid #3b82f6; 
        box-shadow: 0 10px 15px rgba(0, 0, 0, 0.5);
        border-radius: 8px;
    }

    /* Pastikan tabel responsif */
    #tableWrapper {
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        padding-bottom: 10px; /* Sedikit ruang untuk scrollbar bawah */
    }
</style>
@endpush

@section('title', 'Data Pesanan Masuk')
@section('page-title', 'Data Pesanan Masuk')

@section('content')

{{-- === 1. CARD MONITOR PENDAPATAN (GAYA WARNA-WARNI) === --}}
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    
    {{-- CARD 1: SELESAI (HIJAU) --}}
    <div class="relative overflow-hidden rounded-lg bg-green-500 p-5 shadow-lg">
        <div class="relative z-10 text-white">
            <p class="text-3xl font-bold">
                Rp{{ number_format($incomeSelesai ?? 0, 0, ',', '.') }}
            </p>
            <p class="text-sm font-bold uppercase opacity-90 mt-1">Pendapatan Selesai</p>
            <p class="text-xs opacity-75 mt-0.5">Total pesanan sukses</p>
        </div>
        <div class="absolute right-0 top-0 -mt-2 -mr-4 h-24 w-24 opacity-20 transform rotate-12">
            <i class="fas fa-store fa-5x text-white"></i>
        </div>
    </div>

    {{-- CARD 2: MENUNGGU PICKUP (CYAN/BIRU MUDA) --}}
    <div class="relative overflow-hidden rounded-lg bg-cyan-600 p-5 shadow-lg">
        <div class="relative z-10 text-white">
            <p class="text-3xl font-bold">
                Rp{{ number_format($incomePickup ?? 0, 0, ',', '.') }}
            </p>
            <p class="text-sm font-bold uppercase opacity-90 mt-1">Menunggu Pickup</p>
            <p class="text-xs opacity-75 mt-0.5">Sudah lunas, belum kirim</p>
        </div>
        <div class="absolute right-0 top-0 -mt-2 -mr-4 h-24 w-24 opacity-20 transform rotate-12">
            <i class="fas fa-box-open fa-5x text-white"></i>
        </div>
    </div>

    {{-- CARD 3: SEDANG DIKIRIM (BIRU) --}}
    <div class="relative overflow-hidden rounded-lg bg-blue-600 p-5 shadow-lg">
        <div class="relative z-10 text-white">
            <p class="text-3xl font-bold">
                Rp{{ number_format($incomeDikirim ?? 0, 0, ',', '.') }}
            </p>
            <p class="text-sm font-bold uppercase opacity-90 mt-1">Sedang Dikirim</p>
            <p class="text-xs opacity-75 mt-0.5">Sedang dalam perjalanan</p>
        </div>
        <div class="absolute right-0 top-0 -mt-2 -mr-4 h-24 w-24 opacity-20 transform rotate-12">
            <i class="fas fa-shipping-fast fa-5x text-white"></i>
        </div>
    </div>

    {{-- CARD 4: GAGAL / BATAL (MERAH) --}}
    <div class="relative overflow-hidden rounded-lg bg-red-500 p-5 shadow-lg">
        <div class="relative z-10 text-white">
            <p class="text-3xl font-bold">
                Rp{{ number_format($incomeGagal ?? 0, 0, ',', '.') }}
            </p>
            <p class="text-sm font-bold uppercase opacity-90 mt-1">Gagal / Batal</p>
            <p class="text-xs opacity-75 mt-0.5">Potensi pendapatan hilang</p>
        </div>
        <div class="absolute right-0 top-0 -mt-2 -mr-4 h-24 w-24 opacity-20 transform rotate-12">
            <i class="fas fa-arrow-up fa-5x text-white"></i>
        </div>
    </div>
</div>
{{-- === AKHIR CARD MONITOR === --}}


{{-- === 2. TABEL DATA === --}}
<div class="bg-white shadow border border-gray-200 rounded-lg overflow-hidden">
    
    {{-- HEADER: Pencarian & Tombol Export --}}
    <div class="flex flex-col md:flex-row items-center justify-between border-b border-gray-200 p-4 gap-3">
        <form action="{{ route('admin.orders.index') }}" method="GET" class="w-full md:max-w-md">
            <div class="relative">
                <input type="text" name="search" placeholder="Cari Resi, Invoice, Nama..."
                    value="{{ request('search') }}"
                    class="w-full pl-10 pr-4 py-2 text-sm border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 shadow-sm">
                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
            </div>
            @if (request('status'))
                <input type="hidden" name="status" value="{{ request('status') }}">
            @endif
        </form>

        <button type="button" onclick="openModal('exportModal')" 
    class="inline-flex items-center justify-center w-full md:w-auto px-4 py-2 bg-gray-200 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-300 transition duration-150 ease-in-out whitespace-nowrap">
    <i class="fas fa-file-export mr-2"></i> Export
        </button>
    </div>

    {{-- FILTER STATUS --}}
    <div class="p-4 border-b border-gray-200 flex flex-wrap gap-2">
        @php
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

        <a href="{{ route('admin.orders.index', request()->except('status', 'page')) }}"
            class="px-3 py-1 text-sm font-medium rounded-full transition duration-150 ease-in-out focus:outline-none whitespace-nowrap
            {{ !$currentStatus ? 'bg-green-600 text-white shadow' : 'bg-gray-200 text-gray-600 hover:bg-gray-300' }}">
            Semua
        </a>

        @foreach ($statusFilters as $key => $label)
            <a href="{{ route('admin.orders.index', array_merge(request()->query(), ['status' => $key, 'page' => 1])) }}"
                class="px-3 py-1 text-sm font-medium rounded-full transition duration-150 ease-in-out focus:outline-none whitespace-nowrap
                {{ $currentStatus == $key ? 'bg-blue-600 text-white shadow' : 'bg-gray-200 text-gray-600 hover:bg-gray-300' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>
    
   {{-- 1. Scrollbar Dummy di Atas (Biarkan seperti ini) --}}
    <div id="topScrollWrapper" class="w-full overflow-x-auto mb-1 border-b border-gray-200 hidden md:block">
        <div id="topScrollContent" class="h-1 pt-1"></div>
    </div>

    {{-- TABEL --}}
    {{-- 2. Wrapper Tabel Asli: TAMBAHKAN id="tableWrapper" DI SINI --}}
    <div id="tableWrapper" class="w-full overflow-x-auto">
        <table class="w-full text-sm text-gray-700 divide-y divide-gray-200">
            <thead class="bg-red-100">
                <tr>
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
                    <th scope="col" class="sticky right-0 z-10 bg-red-100 px-4 py-3 text-center text-xs font-medium text-gray-600 uppercase tracking-wider min-w-[170px] whitespace-nowrap border-l border-gray-300"><strong>Aksi</strong></th>
                </tr>
            </thead>
            
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($orders as $index => $order)
                    @php
                        // --- Inisialisasi Variabel ---
                        $isPesanan = isset($order->status_pesanan);
                        
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

                            // Keuangan DB
                            $totalAmountDB = $order->price ?? 0;
                            $subtotal = $order->item_price ?? 0;
                            $shippingCost = $order->shipping_cost ?? 0;
                            $codFeeDB = $order->cod_fee ?? 0;
                            $insuranceCost = $order->insurance_cost ?? 0;

                            $statusMapPesanan = ['Menunggu Pickup' => 'menunggu-pickup', 'Sedang Dikirim' => 'terkirim', 'Selesai' => 'selesai', 'Batal' => 'batal'];
                            $status = $statusMapPesanan[$statusRaw] ?? strtolower($statusRaw);
                            $canCancel = in_array($statusRaw, ['Menunggu Pickup']);
                        } else { 
                            // Tabel Orders
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
                            
                            // Keuangan DB
                            $totalAmountDB = $order->total_amount;
                            $shippingCost = $order->shipping_cost;
                            $subtotal = $order->subtotal;
                            $codFeeDB = $order->cod_fee;
                            $insuranceCost = $order->insurance_cost ?? 0;

                            $statusMapOrder = ['shipment' => 'terkirim', 'processing' => 'diproses', 'paid' => 'menunggu-pickup', 'completed' => 'selesai'];
                            $status = $statusMapOrder[$statusRaw] ?? $statusRaw;
                            $canCancel = in_array($statusRaw, ['pending', 'paid', 'processing']);
                        }
                        
                        $ship = \App\Helpers\ShippingHelper::parseShippingMethod($shippingMethodString);
                        
                        // Hitung Fee & Total
                        $metodeBayar = strtoupper(trim($paymentMethod ?? ''));
                        $isCodOngkir = ($metodeBayar === 'COD');

                        if ($isCodOngkir) {
                            $basisCod = ($shippingCost ?? 0) + 10000; 
                            $codFeeHitung = max(2500, $basisCod * 0.03);
                            $codFeeDisplay = $codFeeHitung;
                            $finalTagihan = $shippingCost + $codFeeDisplay + $insuranceCost;
                        } else {
                            $codFeeDisplay = $codFeeDB;
                            $finalTagihan = $totalAmountDB;
                        }

                        // Badge & Text Status
                        $badgeMap = ['pending' => 'bg-yellow-100 text-yellow-800', 'menunggu-pickup' => 'bg-yellow-100 text-yellow-800', 'diproses' => 'bg-blue-100 text-blue-800', 'terkirim' => 'bg-green-100 text-green-800', 'selesai' => 'bg-green-100 text-green-800', 'completed' => 'bg-green-100 text-green-800', 'batal' => 'bg-red-100 text-red-800', 'cancelled' => 'bg-red-100 text-red-800', 'failed' => 'bg-red-100 text-red-800', 'rejected' => 'bg-red-100 text-red-800'];
                        $textMap = ['pending' => 'Menunggu Bayar', 'menunggu-pickup' => 'Menunggu Pickup', 'diproses' => 'Diproses', 'terkirim' => 'Terkirim', 'selesai' => 'Selesai', 'completed' => 'Selesai', 'batal' => 'Batal', 'cancelled' => 'Dibatalkan', 'failed' => 'Gagal', 'rejected' => 'Ditolak'];
                        $statusBadge = $badgeMap[$status] ?? $badgeMap[$statusRaw] ?? 'bg-gray-100 text-gray-800';
                        $statusText = $textMap[$status] ?? $textMap[$statusRaw] ?? ucfirst($statusRaw);
                    @endphp

                    <tr class="group hover:bg-gray-50">
                        {{-- 1. Kolom No --}}
                        <td class="px-4 py-4 whitespace-nowrap text-gray-500 align-top">{{ $orders->firstItem() + $index }}</td>
                        
                        {{-- 2. Kolom ID --}}
                        <td class="px-4 py-4 whitespace-nowrap text-gray-500 align-top">{{ $id }}</td>
                        
                        {{-- 3. Kolom Tipe --}}
                        <td class="px-4 py-4 whitespace-nowrap align-top">
                            @if ($isPesanan)
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">Pesanan</span>
                            @else
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Order</span>
                            @endif
                        </td>
                        
                        {{-- 4. Kolom Transaksi --}}
                        <td class="px-4 py-4 align-top">
                            @if(Str::contains($metodeBayar, 'COD'))
                                <span class="font-bold text-green-600">{{ $paymentMethod }}</span>
                            @else
                                <span class="font-bold text-blue-600">Non COD</span>
                            @endif
                            <div class="font-bold text-gray-800">{{ $invoice }}</div>

                            <div class="text-xs mt-2 space-y-0.5 text-gray-600 w-52">
                                <div class="flex justify-between">
                                    <span>Nilai Barang:</span>
                                    <span class="font-medium {{ $isCodOngkir ? 'line-through text-gray-400' : '' }}">
                                        Rp{{ number_format($subtotal, 0, ',', '.') }}
                                    </span>
                                </div>
                                <div class="flex justify-between">
                                    <span>Ongkir:</span>
                                    <span class="font-medium">Rp{{ number_format($shippingCost, 0, ',', '.') }}</span>
                                </div>
                                @if ($insuranceCost > 0)
                                    <div class="flex justify-between">
                                    <span>Asuransi:</span>
                                    <span class="font-medium">Rp{{ number_format($insuranceCost, 0, ',', '.') }}</span>
                                    </div>
                                @endif
                                <div class="flex justify-between">
                                    <span>Biaya COD:</span>
                                    <span class="font-medium">Rp{{ number_format($codFeeDisplay, 0, ',', '.') }}</span>
                                </div>
                                <div class="flex justify-between font-bold pt-0.5 border-t border-gray-200 mt-1">
                                    <span>Total Tagihan:</span>
                                    <span class="text-blue-700"><strong>Rp{{ number_format($finalTagihan, 0, ',', '.') }}</strong></span>
                                </div>
                            </div>
                        </td>
                        
                        {{-- 5. Kolom Alamat --}}
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
                        
                        {{-- 6. Kolom Ekspedisi --}}
                        <td class="px-4 py-4 align-top whitespace-nowrap">
                            @if ($ship['logo_url'])
                                <img src="{{ $ship['logo_url'] }}" alt="{{ $ship['courier_name'] }}" class="h-5 mb-1 max-w-[90px] object-contain">
                            @else
                                <div class="font-bold text-gray-800">{{ $ship['courier_name'] }}</div>
                            @endif
                            <div class="text-xs text-gray-500">Layanan: {{ $ship['service_name'] }}</div>
                            <div class="font-semibold text-green-700 mt-1">Rp{{ number_format($shippingCost ?? 0, 0, ',', '.') }}</div>
                        </td>
                        
                        {{-- 7. Kolom Resi --}}
                        <td class="px-4 py-4 align-top">
                            @if ($resi)
                                <div id="barcode-{{ $id }}"
                                class="clickable-zoom-barcode cursor-pointer hover:opacity-75 transition-opacity"
                                data-resi="{{ $resi }}"
                                data-target="barcode-{{ $id }}">
                                    <div class="font-medium text-gray-800 break-all max-w-[180px]">{{ $resi }}</div>
                                    <div class="mt-2 barcode-svg-container inline-block">
                                        {!! DNS2D::getBarcodeSVG($resi, 'DATAMATRIX', 5, 5) !!}
                                    </div>
                                </div>
                            @else
                                <span class="text-gray-400 italic">Belum ada resi</span>
                            @endif
                        </td>
                        
                        {{-- 8. Kolom Paket --}}
                        <td class="px-4 py-4 align-top">
                            <div class="font-semibold text-gray-800 break-words max-w-xs">{{ $paket }}</div>
                            @if($isPesanan)
                            <div class="text-xs text-gray-500 mt-1">
                                Berat: {{ $order->weight ?? 0 }} gr <br> 
                                Dimensi: {{ $order->length ?? 0 }}x{{ $order->width ?? 0 }}x{{ $order->height ?? 0 }} cm
                            </div>
                            @elseif(isset($order->items) && $item = $order->items->first())
                            <div class="text-xs text-gray-500 mt-1">
                                @if($item->product)
                                Berat: {{ ($item->product->weight ?? 0) * ($item->quantity ?? 1) }} gr
                                @endif
                            </div>
                            @endif
                        </td>
                        
                        {{-- 9. Kolom Tanggal --}}
                        <td class="px-4 py-4 align-top whitespace-nowrap text-gray-500">
                            <div><span class="text-gray-400">Dibuat:</span> {{ $createdAt ? \Carbon\Carbon::parse($createdAt)->translatedFormat('d M Y, H:i') : '-' }}</div>
                            <div><span class="text-gray-400">Dikirim:</span> {{ $shippedAt ? \Carbon\Carbon::parse($shippedAt)->translatedFormat('d M Y, H:i') : '-' }}</div>
                            <div><span class="text-gray-400">Selesai:</span> {{ $finishedAt ? \Carbon\Carbon::parse($finishedAt)->translatedFormat('d M Y, H:i') : '-' }}</div>
                        </td>
                        
                        {{-- 10. Kolom Status --}}
                        <td class="px-4 py-4 align-top whitespace-nowrap">
                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full {{ $statusBadge }}">
                                {{ $statusText }}
                            </span>
                        </td>
                        
                        {{-- 11. Kolom Aksi --}}
                        <td class="sticky right-0 z-10 bg-white group-hover:bg-gray-50 px-6 py-4 align-top whitespace-nowrap border-l border-gray-200">
                            <div class="flex items-center space-x-3">
                                @if($resi)
                                    <a href="{{ 'https://tokosancaka.com/tracking/search?resi=' . e($resi) }}" target="_blank" class="text-gray-500 hover:text-green-600" title="Lacak Resi"><i class="fas fa-truck fa-fw"></i></a>
                                @endif
                                
                                @if ($isPesanan)
                                    <a href="{{ route('admin.pesanan.show', $invoice) }}" class="text-gray-500 hover:text-indigo-600" title="Detail"><i class="fas fa-eye fa-fw"></i></a>
                                @else
                                    <a href="{{ route('admin.orders.show', $invoice) }}" class="text-gray-500 hover:text-indigo-600" title="Detail"><i class="fas fa-eye fa-fw"></i></a>
                                @endif
                                
                                <a href="{{ route('admin.orders.print.thermal', $invoice) }}" target="_blank" class="text-gray-500 hover:text-gray-800" title="Cetak Label"><i class="fas fa-print fa-fw"></i></a>
                                <a href="{{ route('admin.orders.invoice.pdf', $invoice) }}" target="_blank" class="text-gray-500 hover:text-red-600" title="PDF Faktur"><i class="fas fa-file-pdf fa-fw"></i></a>
                                
                                @if ($userId)
                                <a href="{{ route('admin.chat.start', ['id_pengguna' => $userId]) }}" target="_blank" class="text-gray-500 hover:text-blue-600" title="Chat"><i class="fas fa-comment fa-fw"></i></a>
                                @endif

                                <form action="{{ route('admin.orders.cancel', $invoice) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan pesanan ini?');" class="inline-block">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="text-gray-500 hover:text-red-600 {{ !$canCancel ? 'opacity-40 cursor-not-allowed' : '' }}" title="Batalkan" @disabled(!$canCancel)>
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
    </div>
</div>


    {{-- PAGINATION --}}
    @if ($orders->hasPages())
        <div class="mt-4 p-4 border-t border-gray-200 flex flex-col items-center justify-between sm:flex-row gap-4">
            <div class="text-sm text-gray-700">
                Menampilkan 
                <span class="font-medium">{{ $orders->firstItem() }}</span> 
                sampai 
                <span class="font-medium">{{ $orders->lastItem() }}</span> 
                dari 
                <span class="font-medium">{{ $orders->total() }}</span> 
                hasil
            </div>
            <div class="inline-flex rounded-md shadow-sm">
                {{ $orders->appends(request()->query())->links() }} 
            </div>
        </div>
    @endif
{{-- === AKHIR TABEL DATA === --}}

@include('layouts.partials.modals.export', [
'excel_route' => route('admin.pesanan.export.excel'),
'pdf_route' => route('admin.orders.report.pdf')
])

{{-- Modal Barcode Zoom --}}
<div id="barcodeModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="barcodeModalLabel" aria-modal="true" role="dialog">
    <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="relative bg-white rounded-lg shadow-xl w-full max-w-lg mx-auto transform transition-all">
            <div class="flex justify-between items-start p-5 border-b rounded-t">
                <h3 class="text-xl font-semibold text-gray-900" id="barcodeModalLabel">
                    Barcode Resi: <span id="modalResiNumber"></span>
                </h3>
                <button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm p-1.5 ml-auto inline-flex items-center modal-close-btn" data-modal-hide="barcodeModal">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
                </button>
            </div>
            <div class="p-6 space-y-6 text-center">
                <div id="modalBarcodeContainer" class="flex justify-center items-center h-80"> 
                </div>
                <div id="resiTextZoom" class="font-bold text-lg text-gray-700"></div>
            </div>
            <div class="flex items-center p-6 space-x-2 border-t border-gray-200 rounded-b justify-end">
                <button data-modal-hide="barcodeModal" type="button" class="modal-close-btn text-gray-500 bg-white hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-blue-300 rounded-lg border border-gray-200 text-sm font-medium px-5 py-2.5 hover:text-gray-900 focus:z-10">Tutup</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">

<script>
    $(document).ready(function() {
        toastr.options = { "closeButton": true, "progressBar": true, "positionClass": "toast-top-right" };
        @if (session('success')) toastr.success("{{ session('success') }}", "Berhasil!"); @endif
        
        $(document).on('click', '.clickable-zoom-barcode', function(e) {
            e.stopPropagation(); 
            const targetDiv = $(this).find('.barcode-svg-container');
            if (targetDiv.hasClass('barcode-zoomed')) {
                targetDiv.removeClass('barcode-zoomed');
            } else {
                $('.barcode-zoomed').removeClass('barcode-zoomed');
                targetDiv.addClass('barcode-zoomed');
                targetDiv.find('svg').attr('preserveAspectRatio', 'xMidYMid meet');
            }
        });

        $(document).on('click', 'body', function(e) {
            if (!$(e.target).closest('.clickable-zoom-barcode').length) {
                $('.barcode-zoomed').removeClass('barcode-zoomed');
            }
        });
        
        // --- FUNGSI SCROLLBAR ATAS TABEL ---
        const topScrollWrapper = document.getElementById('topScrollWrapper');
        const topScrollContent = document.getElementById('topScrollContent');
        const tableWrapper = document.getElementById('tableWrapper');

        if (topScrollWrapper && tableWrapper) {
            // 1. Samakan lebar konten dummy dengan lebar asli tabel
            // Ini agar panjang scrollbar atas sama persis dengan bawah
            function syncScrollWidth() {
                topScrollContent.style.width = tableWrapper.scrollWidth + 'px';
                
                // Sembunyikan scrollbar atas jika tabel tidak meluber (tidak perlu scroll)
                if (tableWrapper.scrollWidth <= tableWrapper.clientWidth) {
                    topScrollWrapper.style.display = 'none';
                } else {
                    topScrollWrapper.style.display = 'block';
                }
            }

            // Panggil saat halaman dimuat
            syncScrollWidth();

            // Panggil saat window di-resize (agar responsif)
            window.addEventListener('resize', syncScrollWidth);

            // 2. Sinkronisasi Scroll (Atas menggerakkan Bawah)
            topScrollWrapper.addEventListener('scroll', function() {
                tableWrapper.scrollLeft = topScrollWrapper.scrollLeft;
            });

            // 3. Sinkronisasi Scroll (Bawah menggerakkan Atas)
            tableWrapper.addEventListener('scroll', function() {
                topScrollWrapper.scrollLeft = tableWrapper.scrollLeft;
            });
        }
        
        // --- FUNGSI MODAL EXPORT (Script Tambahan) ---
    function openModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('hidden');
            modal.setAttribute('aria-hidden', 'false');
            modal.setAttribute('aria-modal', 'true');
        } else {
            console.error('Modal dengan ID ' + modalId + ' tidak ditemukan.');
        }
    }

    function closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('hidden');
            modal.setAttribute('aria-hidden', 'true');
            modal.removeAttribute('aria-modal');
        }
    }
    });
</script>
@endpush