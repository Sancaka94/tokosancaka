{{-- Halaman ini menampilkan data gabungan dari tabel 'orders' dan 'Pesanan' --}}
@extends('layouts.admin')

@section('title', 'Data Pesanan Masuk')
@section('page-title', 'Data Pesanan Masuk')

@push('scripts')
{{-- Toastr (notifikasi) --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script> {{-- Pastikan jQuery dimuat --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
<script>
    // Pastikan jQuery siap sebelum menjalankan kode Toastr
    $(document).ready(function() {
        // Konfigurasi default Toastr
        toastr.options = {
            "closeButton": true,
            "debug": false,
            "newestOnTop": true,
            "progressBar": true,
            "positionClass": "toastr-top-right",
            "preventDuplicates": false,
            "onclick": null,
            "showDuration": "300",
            "hideDuration": "1000",
            "timeOut": "5000",
            "extendedTimeOut": "1000",
            "showEasing": "swing",
            "hideEasing": "linear",
            "showMethod": "fadeIn",
            "hideMethod": "fadeOut"
        };

        // Tampilkan notifikasi 'success' dari session
        @if (session('success'))
            toastr.success("{{ session('success') }}", "Berhasil!");
        @endif

        // Tampilkan notifikasi 'error' dari session
        @if (session('error'))
            // Perbaiki pesan error untuk kolom tidak ditemukan
            @if (Str::contains(session('error'), 'Unknown column'))
                toastr.error("Kolom pencarian tidak ditemukan di database. Harap periksa logika pencarian di Controller.", "Gagal!");
            @else
                toastr.error("{{ session('error') }}", "Gagal!");
            @endif
        @endif
        
        // Tampilkan notifikasi error validasi
        @if ($errors->any())
            @foreach ($errors->all() as $error)
                toastr.error("{{ $error }}", "Kesalahan Validasi");
            @endforeach
        @endif
    });
</script>
@endpush

@section('content')
{{-- 
  Wrapper container (container, max-w-7xl, px-4, py-6, dll.) 
  SEKARANG DISEDIAKAN OLEH layout/admin.blade.php.
  File ini sekarang hanya berisi card-nya saja.
--}}
<div class="bg-white shadow border border-gray-200 rounded-lg overflow-hidden">
    
    {{-- HEADER: Pencarian & Tombol Export --}}
    <div class="flex flex-col md:flex-row items-center justify-between border-b border-gray-200 p-4 gap-3">
        {{-- Form Pencarian --}}
        <form action="{{ route('admin.orders.index') }}" method="GET" class="w-full md:max-w-md">
            <div class="relative">
                <input type="text" name="search" placeholder="Cari Resi, Invoice, Nama..."
                    value="{{ request('search') }}"
                    class="w-full pl-10 pr-4 py-2 text-sm border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 shadow-sm"> {{-- Ubah rounded-md ke rounded-lg --}}
                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
            </div>
            {{-- Menyimpan filter status saat melakukan pencarian --}}
            @if (request('status'))
                <input type="hidden" name="status" value="{{ request('status') }}">
            @endif
        </form>

        {{-- Tombol Export --}}
        <button type="button" data-bs-toggle="modal" data-bs-target="#exportModal"
            class="inline-flex items-center justify-center w-full md:w-auto px-4 py-2 bg-gray-200 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-300 transition duration-150 ease-in-out whitespace-nowrap"> {{-- Ubah gaya tombol export --}}
            <i class="fas fa-file-export mr-2"></i> Export
        </button>
    </div>

    {{-- FILTER STATUS --}}
    <div class="p-4 border-b border-gray-200 flex flex-wrap gap-2 pb-4 mb-4"> {{-- Tambah pb-4 mb-4 --}}
        @php
            // Definisikan filter tab
            $statusFilters = [
                'pending' => 'Menunggu Bayar',
                'menunggu-pickup' => 'Menunggu Pickup',
                'diproses' => 'Diproses', 
                'terkirim' => 'Terkirim', 
                'selesai' => 'Selesai', // Filter ini akan mencari status 'completed' di DB 'orders'
                'batal' => 'Batal', 
            ];
            $currentStatus = request('status');
        @endphp

        {{-- Tombol "Semua" --}}
        <a href="{{ route('admin.orders.index', request()->except('status', 'page')) }}"
            class="px-3 py-1 text-sm font-medium rounded-full transition duration-150 ease-in-out focus:outline-none whitespace-nowrap
            {{ !$currentStatus ? 'bg-green-600 text-white shadow' : 'bg-gray-200 text-gray-600 hover:bg-gray-300' }}"> {{-- Ubah gaya filter --}}
            Semua
        </a>

        {{-- Loop Tombol Filter Dinamis --}}
        @foreach ($statusFilters as $key => $label)
            <a href="{{ route('admin.orders.index', array_merge(request()->query(), ['status' => $key, 'page' => 1])) }}"
                class="px-3 py-1 text-sm font-medium rounded-full transition duration-150 ease-in-out focus:outline-none whitespace-nowrap
                {{ $currentStatus == $key ? 'bg-blue-600 text-white shadow' : 'bg-gray-200 text-gray-600 hover:bg-gray-300' }}"> {{-- Ubah gaya filter --}}
                {{ $label }}
            </a>
        @endforeach
    </div>

    {{-- TABEL --}}
    {{-- Tambahkan kembali wrapper overflow-x-auto di sini --}}
    <div class="overflow-x-auto">
        {{-- Hapus min-w-full dari tabel --}}
        <table class="text-sm text-gray-700 divide-y divide-gray-200"> 
            <thead class="bg-red-100"> {{-- Ubah background header --}}
                <tr>
                    {{-- Header Tabel --}}
                    {{-- 'sticky top-0' membuat header 'menempel' di atas saat scroll vertikal --}}
                    <th scope="col" class="sticky top-0 z-10 bg-red-100 px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider whitespace-nowrap">No</th> {{-- Ubah bg & text color --}}
                    <th scope="col" class="sticky top-0 z-10 bg-red-100 px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider min-w-[100px] whitespace-nowrap">ID</th> {{-- Ubah bg & text color --}}
                    <th scope="col" class="sticky top-0 z-10 bg-red-100 px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider min-w-[100px] whitespace-nowrap">Tipe</th> {{-- Ubah bg & text color --}}
                    <th scope="col" class="sticky top-0 z-10 bg-red-100 px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider min-w-[250px] whitespace-nowrap"><strong>Transaksi</strong></th> {{-- Ubah bg & text color, tambah strong --}}
                    <th scope="col" class="sticky top-0 z-10 bg-red-100 px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider min-w-[300px] whitespace-nowrap"><strong>Alamat</strong></th> {{-- Ubah bg & text color, tambah strong --}}
                    <th scope="col" class="sticky top-0 z-10 bg-red-100 px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider min-w-[180px] whitespace-nowrap"><strong>Ekspedisi & Ongkir</strong></th> {{-- Ubah bg & text color, tambah strong --}}
                    <th scope="col" class="sticky top-0 z-10 bg-red-100 px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider min-w-[200px] whitespace-nowrap">Resi</th> {{-- Ubah bg & text color --}}
                    <th scope="col" class="sticky top-0 z-10 bg-red-100 px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider min-w-[200px] whitespace-nowrap"><strong>Isi Paket</strong></th> {{-- Ubah bg & text color, tambah strong --}}
                    <th scope="col" class="sticky top-0 z-10 bg-red-100 px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider min-w-[200px] whitespace-nowrap">Tanggal</th> {{-- Ubah bg & text color --}}
                    <th scope="col" class="sticky top-0 z-10 bg-red-100 px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider min-w-[150px] whitespace-nowrap"><strong>Status</strong></th> {{-- Ubah bg & text color, tambah strong --}}
                    {{-- Kolom Aksi: 'sticky right-0' membuatnya 'menempel' di kanan saat scroll horizontal --}}
                    <th scope="col" class="sticky top-0 right-0 z-20 bg-red-100 px-4 py-3 text-right text-xs font-medium text-gray-600 uppercase tracking-wider min-w-[170px] whitespace-nowrap border-l border-gray-300"><strong>Aksi</strong></th> {{-- Ubah bg & text color, tambah strong --}}
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($orders as $index => $order)
                    {{-- 
                      Inisialisasi Variabel.
                      PENTING: Error 'Unknown column tracking_number' harus diperbaiki di AdminOrderController.php
                      Ganti ->orWhere('tracking_number', ...) dengan ->orWhere('shipping_reference', ...) untuk query $ordersQuery
                      dan ganti ->orWhere('resi', ...) / ->orWhere('resi_aktual', ...) untuk query $pesananQuery
                    --}}
                    @php
                        $isPesanan = isset($order->status_pesanan);
                        
                        // --- Blok Inisialisasi Variabel ---
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
                            $shippingInfo = \App\Helpers\ShippingHelper::parseShippingMethod($shippingMethodString);
                            $shippingCost = $shippingInfo['cost'];
                            $subtotal = $order->total_harga_barang;
                            $codFee = 0;
                            if (strtoupper($paymentMethod) == 'CODBARANG' || strtoupper($paymentMethod) == 'COD') {
                                $subtotalForCalc = $subtotal ?? ($totalAmount - $shippingCost);
                                $codFee = max(0, $totalAmount - $subtotalForCalc - $shippingCost);
                            }
                            if ($subtotal === null) {
                                $subtotal = max(0, $totalAmount - $shippingCost - $codFee);
                            }
                        } else { // Jika data berasal dari tabel 'orders'
                            $id = $order->id;
                            $invoice = $order->invoice_number;
                            $resi = $order->shipping_reference; // Gunakan kolom resi yang benar untuk 'orders'
                            $paymentMethod = $order->payment_method;
                            $shippingMethodString = $order->shipping_method;
                            // Cek relasi items, product, dan variant sebelum mengakses property
                            $item = $order->items->first();
                            $paket = $item && $item->product ? $item->product->name : ($item && $item->variant ? $item->variant->combination_string : 'N/A');
                            $userId = $order->user_id;
                            // Cek relasi store sebelum mengakses property
                            $senderName = $order->store ? $order->store->name : 'N/A';
                            $senderAddress = $order->store ? $order->store->address_detail : 'N/A';
                             // Cek relasi user sebelum mengakses property
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
                                'completed' => 'selesai', // Tambahkan mapping untuk 'completed'
                            ];
                            $status = $statusMapOrder[$statusRaw] ?? $statusRaw;
                            $canCancel = in_array($statusRaw, ['pending', 'paid', 'processing']);
                            $totalAmount = $order->total_amount;
                            $shippingCost = $order->shipping_cost;
                            $subtotal = $order->subtotal;
                            $codFee = $order->cod_fee;
                        }
                        
                        $ship = \App\Helpers\ShippingHelper::parseShippingMethod($shippingMethodString);

                        // Mapping Badge Status
                        $badgeMap = [
                            'pending' => 'bg-yellow-100 text-yellow-800',        
                            'menunggu-pickup' => 'bg-yellow-100 text-yellow-800',   
                            'diproses' => 'bg-blue-100 text-blue-800',        
                            'terkirim' => 'bg-green-100 text-green-800',      
                            'selesai' => 'bg-green-100 text-green-800', // Status 'selesai' dari Pesanan atau 'completed' dari Order
                            'completed' => 'bg-green-100 text-green-800', // Handle 'completed' dari Order secara eksplisit     
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
                            'selesai' => 'Selesai', // Status 'selesai' dari Pesanan atau 'completed' dari Order
                            'completed' => 'Selesai', // Handle 'completed' dari Order secara eksplisit
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
                                    <span>Subtotal:</span>
                                    <span class="font-medium">Rp{{ number_format($subtotal ?? 0, 0, ',', '.') }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>Ongkir:</span>
                                    <span class="font-medium">Rp{{ number_format($shippingCost ?? 0, 0, ',', '.') }}</span>
                                </div>
                                @if ($codFee > 0)
                                <div class="flex justify-between">
                                    <span>Biaya COD:</span>
                                    <span class="font-medium">Rp{{ number_format($codFee, 0, ',', '.') }}</span>
                                </div>
                                @endif
                                <div class="flex justify-between font-bold pt-0.5 border-t border-gray-200 mt-1">
                                    <span>Total:</span>
                                    <span class="text-indigo-600">Rp{{ number_format($totalAmount ?? 0, 0, ',', '.') }}</span>
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
                        <td class="px-4 py-4 align-top whitespace-nowrap">
                            @if ($resi)
                                <div class="font-medium text-gray-800 break-all max-w-[180px]">{{ $resi }}</div>
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
                        
                        {{-- Kolom Aksi (Sticky) --}}
                        <td class="sticky right-0 z-10 bg-white group-hover:bg-gray-50 px-6 py-4 align-top whitespace-nowrap border-l border-gray-200">
                            <div class="flex items-center space-x-3">
                                @if($resi)
                                <a href="{{ 'https://tokosancaka.com/tracking/search?resi=' . e($resi) }}" target="_blank" 
                                   class="text-gray-500 hover:text-green-600" title="Lacak Resi">
                                    <i class="fas fa-truck fa-fw"></i>
                                </a>
                                @endif
                                <a href="{{ route('admin.orders.show', $invoice) }}" 
                                   class="text-gray-500 hover:text-indigo-600" title="Detail">
                                    <i class="fas fa-eye fa-fw"></i>
                                </a>
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
    'excel_route' => route('admin.pesanan.export.excel'), // Sesuaikan route ini jika perlu
    'pdf_route' => route('admin.orders.report.pdf')
])
@endsection

