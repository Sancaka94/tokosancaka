{{-- Halaman ini menampilkan data gabungan dari tabel 'orders' dan 'Pesanan' --}}
@extends('layouts.admin')

@section('title', 'Data Pesanan Masuk')
@section('page-title', 'Data Pesanan Masuk')

@push('scripts')
{{-- Toastr (notifikasi) --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
<script>
    $(function() {
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
            toastr.error("{{ session('error') }}", "Gagal!");
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
                    class="w-full pl-10 pr-4 py-2 text-sm border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500 shadow-sm">
                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
            </div>
            {{-- Menyimpan filter status saat melakukan pencarian --}}
            @if (request('status'))
                <input type="hidden" name="status" value="{{ request('status') }}">
            @endif
        </form>

        {{-- Tombol Export --}}
        <button type="button" data-bs-toggle="modal" data-bs-target="#exportModal"
            class="inline-flex items-center justify-center w-full md:w-auto px-4 py-2 bg-gray-700 hover:bg-gray-800 text-white text-sm font-medium rounded-md shadow-sm transition duration-150 ease-in-out whitespace-nowrap">
            <i class="fas fa-download mr-2"></i> Export Laporan
        </button>
    </div>

    {{-- FILTER STATUS --}}
    <div class="p-4 border-b border-gray-200 flex flex-wrap gap-2">
        @php
            // Definisikan filter tab
            // Key (cth: 'menunggu-pickup') adalah apa yang dikirim di URL
            // Value (cth: 'Menunggu Pickup') adalah teks yang tampil di tombol
            $statusFilters = [
                'pending' => 'Menunggu Bayar',
                'menunggu-pickup' => 'Menunggu Pickup',
                'diproses' => 'Diproses', // 'shipping' di DB 'orders'
                'terkirim' => 'Terkirim', // 'delivered' di DB 'orders', 'Sedang Dikirim' di 'Pesanan'
                'selesai' => 'Selesai', // 'completed' di DB 'orders'
                'batal' => 'Batal', // 'cancelled' dll. di 'orders'
            ];
            $currentStatus = request('status');
        @endphp

        {{-- Tombol "Semua" --}}
        <a href="{{ route('admin.orders.index', request()->except('status', 'page')) }}"
            class="px-3 py-1 text-sm font-medium rounded-full transition duration-150 ease-in-out focus:outline-none whitespace-nowrap
            {{ !$currentStatus ? 'bg-indigo-600 text-white shadow' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
            Semua
        </a>

        {{-- Loop Tombol Filter Dinamis --}}
        @foreach ($statusFilters as $key => $label)
            <a href="{{ route('admin.orders.index', array_merge(request()->query(), ['status' => $key, 'page' => 1])) }}"
                class="px-3 py-1 text-sm font-medium rounded-full transition duration-150 ease-in-out focus:outline-none whitespace-nowrap
                {{ $currentStatus == $key ? 'bg-indigo-600 text-white shadow' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>

    {{-- TABEL --}}
    {{-- Wrapper ini adalah yang akan scroll horizontal --}}
    <div class="max-w-full overflow-x-auto">
        {{-- Hapus 'w-full' dari tabel --}}
        <table class="text-sm text-gray-700 divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    {{-- Header Tabel --}}
                    {{-- 'sticky top-0' membuat header 'menempel' di atas saat scroll vertikal --}}
                    <th scope="col" class="sticky top-0 z-10 bg-gray-50 px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider whitespace-nowrap">No</th>
                    <th scope="col" class="sticky top-0 z-10 bg-gray-50 px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider min-w-[100px] whitespace-nowrap">ID</th>
                    <th scope="col" class="sticky top-0 z-10 bg-gray-50 px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider min-w-[100px] whitespace-nowrap">Tipe</th>
                    <th scope="col" class="sticky top-0 z-10 bg-gray-50 px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider min-w-[250px] whitespace-nowrap">Transaksi</th>
                    <th scope="col" class="sticky top-0 z-10 bg-gray-50 px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider min-w-[300px] whitespace-nowrap">Alamat</th>
                    <th scope="col" class="sticky top-0 z-10 bg-gray-50 px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider min-w-[180px] whitespace-nowrap">Ekspedisi</th>
                    <th scope="col" class="sticky top-0 z-10 bg-gray-50 px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider min-w-[200px] whitespace-nowrap">Resi</th>
                    <th scope="col" class="sticky top-0 z-10 bg-gray-50 px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider min-w-[200px] whitespace-nowrap">Paket</th>
                    <th scope="col" class="sticky top-0 z-10 bg-gray-50 px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider min-w-[200px] whitespace-nowrap">Tanggal</th>
                    <th scope="col" class="sticky top-0 z-10 bg-gray-50 px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider min-w-[150px] whitespace-nowrap">Status</th>
                    {{-- Kolom Aksi: 'sticky right-0' membuatnya 'menempel' di kanan saat scroll horizontal --}}
                    <th scope="col" class="sticky top-0 right-0 z-20 bg-gray-100 px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider min-w-[170px] whitespace-nowrap border-l border-gray-300">Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($orders as $index => $order)
                    {{-- 
                      Inisialisasi Variabel.
                      Kita cek apakah $order berasal dari tabel 'Pesanan' 
                      dengan melihat keberadaan kolom 'status_pesanan'.
                    --}}
                    @php
                        $isPesanan = isset($order->status_pesanan);
                        
                        // Ambil data yang berbeda
                        if ($isPesanan) {
                            $id = $order->id_pesanan;
                            $invoice = $order->nomor_invoice;
                            $resi = $order->resi_aktual ?? $order->resi;
                            $paymentMethod = $order->payment_method;
                            $shippingMethodString = $order->jasa_ekspedisi_aktual ?? $order->expedition;
                            $paket = $order->item_description ?? 'N/A';
                            $userId = $order->customer_id ?? $order->id_pengguna_pembeli;
                            
                            // Alamat
                            $senderName = $order->sender_name ?? 'N/A';
                            $senderAddress = $order->sender_address ?? 'N/A';
                            $receiverName = $order->receiver_name ?? $order->nama_pembeli ?? 'N/A';
                            $receiverAddress = $order->receiver_address ?? $order->alamat_pengiriman ?? 'N/A';

                            // Tanggal
                            $createdAt = $order->created_at ?? $order->tanggal_pesanan;
                            $shippedAt = $order->shipped_at;
                            $finishedAt = $order->finished_at;
                            
                            // Status (mapping untuk helper)
                            $statusRaw = $order->status_pesanan;
                            $statusMap = [
                                'Menunggu Pickup' => 'menunggu-pickup',
                                'Sedang Dikirim' => 'terkirim',
                                'Selesai' => 'selesai',
                                'Batal' => 'batal',
                            ];
                            $status = $statusMap[$statusRaw] ?? strtolower($statusRaw);
                            
                            // Logika 'Bisa Batal'
                            $canCancel = in_array($statusRaw, ['Menunggu Pickup']);

                            // Hitung Biaya (dari controller 'standardizePesanan')
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

                        } else {
                            // Data dari tabel 'orders'
                            $id = $order->id;
                            $invoice = $order->invoice_number;
                            $resi = $order->tracking_number;
                            $paymentMethod = $order->payment_method;
                            $shippingMethodString = $order->shipping_method;
                            $paket = $order->items->first()->product->name ?? 'N/A';
                            $userId = $order->user_id;

                            // Alamat
                            $senderName = $order->store->name ?? 'N/A';
                            $senderAddress = $order->store->address_detail ?? 'N/A';
                            $receiverName = $order->user->nama_lengkap ?? 'N/A';
                            $receiverAddress = $order->shipping_address ?? 'N/A';
                            
                            // Tanggal
                            $createdAt = $order->created_at;
                            $shippedAt = $order->shipped_at;
                            $finishedAt = $order->finished_at;
                            
                            // Status (mapping untuk helper)
                            $statusRaw = $order->status;
                            $statusMap = [
                                'shipment' => 'terkirim', // Mapping lama
                                'processing' => 'diproses', // Mapping lama
                                'paid' => 'menunggu-pickup' // Mapping baru
                            ];
                            $status = $statusMap[$statusRaw] ?? $statusRaw;
                            
                            // Logika 'Bisa Batal' (sesuai controller baru)
                            $canCancel = in_array($statusRaw, ['pending', 'paid', 'processing']);

                            // Biaya
                            $totalAmount = $order->total_amount;
                            $shippingCost = $order->shipping_cost;
                            $subtotal = $order->subtotal;
                            $codFee = $order->cod_fee;
                        }
                        
                        // Ambil info ekspedisi
                        $ship = \App\Helpers\ShippingHelper::parseShippingMethod($shippingMethodString);

                        // --- PERBAIKAN: Ganti panggilan helper dengan mapping lokal ---
                        $badgeMap = [
                            'pending' => 'bg-yellow-100 text-yellow-800',        // Kuning
                            'menunggu-pickup' => 'bg-blue-100 text-blue-800',   // Biru
                            'diproses' => 'bg-cyan-100 text-cyan-800',        // Cyan
                            'terkirim' => 'bg-indigo-100 text-indigo-800',      // Indigo
                            'selesai' => 'bg-green-100 text-green-800',        // Hijau
                            'completed' => 'bg-green-100 text-green-800',      // Hijau (alias)
                            'batal' => 'bg-red-100 text-red-800',              // Merah
                            'cancelled' => 'bg-red-100 text-red-800',          // Merah (alias)
                            'failed' => 'bg-red-100 text-red-800',             // Merah (alias)
                            'rejected' => 'bg-red-100 text-red-800',           // Merah (alias)
                        ];
                        
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

                        // Terapkan mapping, dengan fallback. Cek status yang sudah di-map, lalu status raw
                        $statusBadge = $badgeMap[$status] ?? $badgeMap[$statusRaw] ?? 'bg-gray-100 text-gray-800';
                        $statusText = $textMap[$status] ?? $textMap[$statusRaw] ?? ucfirst($statusRaw);
                        // --- Akhir Perbaikan ---
                    @endphp
                    
                    {{-- Baris 'group' memungkinkan 'group-hover' pada kolom sticky --}}
                    <tr class="group hover:bg-gray-50">
                        {{-- Kolom No --}}
                        <td class="px-4 py-3 whitespace-nowrap text-gray-500">{{ $orders->firstItem() + $index }}</td>
                        
                        {{-- Kolom ID --}}
                        <td class="px-4 py-3 whitespace-nowrap text-gray-500">{{ $id }}</td>
                        
                        {{-- Kolom Tipe --}}
                        <td class="px-4 py-3 whitespace-nowrap">
                            @if ($isPesanan)
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">Pesanan</span>
                            @else
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Order</span>
                            @endif
                        </td>
                        
                        {{-- Kolom Transaksi --}}
                        <td class="px-4 py-3 align-top">
                            <div class="font-semibold text-gray-900">{{ strtoupper($paymentMethod ?? 'N/A') }}</div>
                            <div class="text-gray-500">{{ $invoice }}</div>
                            {{-- Rincian Biaya --}}
                            <div class="text-xs mt-2 space-y-0.5 text-gray-600 w-52">
                                <div class="flex justify-between">
                                    <span>Subtotal:</span>
                                    <span class="font-medium">Rp{{ number_format($subtotal, 0, ',', '.') }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>Ongkir:</span>
                                    <span class="font-medium">Rp{{ number_format($shippingCost, 0, ',', '.') }}</span>
                                </div>
                                @if ($codFee > 0)
                                <div class="flex justify-between">
                                    <span>Biaya COD:</span>
                                    <span class="font-medium">Rp{{ number_format($codFee, 0, ',', '.') }}</span>
                                </div>
                                @endif
                                <div class="flex justify-between font-bold pt-0.5 border-t border-gray-200">
                                    <span>Total:</span>
                                    <span class="text-indigo-600">Rp{{ number_format($totalAmount, 0, ',', '.') }}</span>
                                </div>
                            </div>
                        </td>
                        
                        {{-- Kolom Alamat --}}
                        <td class="px-4 py-3 align-top">
                            <div class="mb-2">
                                <span class="text-xs text-gray-500">Dari:</span>
                                <div class="font-semibold text-blue-700">{{ $senderName }}</div>
                                <div class="text-gray-600 text-xs">{{ $senderAddress }}</div>
                            </div>
                            <div>
                                <span class="text-xs text-gray-500">Kepada:</span>
                                <div class="font-semibold text-red-700">{{ $receiverName }}</div>
                                <div class="text-gray-600 text-xs">{{ $receiverAddress }}</div>
                            </div>
                        </td>
                        
                        {{-- Kolom Ekspedisi --}}
                        <td class="px-4 py-3 align-top whitespace-nowrap">
                            @if ($ship['logo_url'])
                                <img src="{{ $ship['logo_url'] }}" alt="{{ $ship['courier_name'] }}" class="h-5 mb-1 max-w-[90px] object-contain">
                            @else
                                <div class="font-semibold">{{ $ship['courier_name'] }}</div>
                            @endif
                            <div class="text-gray-600">{{ $ship['service_name'] }}</div>
                            <div class="text-xs text-gray-500">Ongkir: Rp{{ number_format($ship['cost'], 0, ',', '.') }}</div>
                        </td>
                        
                        {{-- Kolom Resi --}}
                        <td class="px-4 py-3 align-top whitespace-nowrap">
                            @if ($resi)
                                <div class="font-medium text-gray-800">{{ $resi }}</div>
                            @else
                                <span class="text-gray-400 italic">Belum ada resi</span>
                            @endif
                        </td>
                        
                        {{-- Kolom Paket --}}
                        <td class="px-4 py-3 align-top font-medium">{{ $paket }}</td>
                        
                        {{-- Kolom Tanggal --}}
                        <td class="px-4 py-3 align-top whitespace-nowrap text-gray-500">
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
                        <td class="px-4 py-3 align-top whitespace-nowrap">
                            <span class="px-2 py-1 rounded-full text-xs font-semibold {{ $statusBadge }}">
                                {{ $statusText }}
                            </span>
                        </td>
                        
                        {{-- Kolom Aksi (Sticky) --}}
                        {{-- 'group-hover:bg-gray-100' membuatnya ganti warna saat <tr> di-hover --}}
                        <td class="sticky right-0 z-10 bg-white group-hover:bg-gray-50 px-4 py-3 align-top text-right whitespace-nowrap border-l border-gray-200">
                            <div class="inline-flex items-center justify-end gap-1">
                                {{-- Tombol Lacak --}}
                                <a href="{{ $resi ? 'https://tokosancaka.com/tracking/search?resi=' . e($resi) : '#' }}" target="_blank" 
                                   class="inline-flex items-center justify-center w-8 h-8 rounded-md text-gray-500 hover:text-green-600 hover:bg-gray-100 transition
                                          {{ !$resi ? 'opacity-40 cursor-not-allowed' : '' }}" 
                                   title="Lacak" @if(!$resi) onclick="return false;" @endif>
                                    <i class="fas fa-truck fa-fw"></i>
                                </a>
                                {{-- Tombol Detail --}}
                                <a href="{{ route('admin.orders.show', $invoice) }}" 
                                   class="inline-flex items-center justify-center w-8 h-8 rounded-md text-gray-500 hover:text-indigo-600 hover:bg-gray-100 transition" 
                                   title="Detail">
                                    <i class="fas fa-eye fa-fw"></i>
                                </a>
                                {{-- Tombol Print Thermal --}}
                                <a href="{{ route('admin.orders.print.thermal', $invoice) }}" target="_blank" 
                                   class="inline-flex items-center justify-center w-8 h-8 rounded-md text-gray-500 hover:text-gray-800 hover:bg-gray-100 transition" 
                                   title="Print Label">
                                    <i class="fas fa-print fa-fw"></i>
                                </a>
                                {{-- Tombol PDF Faktur --}}
                                <a href="{{ route('admin.orders.invoice.pdf', $invoice) }}" target="_blank" 
                                   class="inline-flex items-center justify-center w-8 h-8 rounded-md text-gray-500 hover:text-red-600 hover:bg-gray-100 transition" 
                                   title="PDF Faktur">
                                    <i class="fas fa-file-pdf fa-fw"></i>
                                </a>
                                {{-- Tombol Chat --}}
                                @if ($userId)
                                <a href="{{ route('admin.chat.start', ['id_pengguna' => $userId]) }}" target="_blank" 
                                   class="inline-flex items-center justify-center w-8 h-8 rounded-md text-gray-500 hover:text-blue-600 hover:bg-gray-100 transition" 
                                   title="Chat">
                                    <i class="fas fa-comment fa-fw"></i>
                                </a>
                                @endif
                                {{-- Tombol Batal --}}
                                <form action="{{ route('admin.orders.cancel', $invoice) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan pesanan ini?')">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" 
                                            class="inline-flex items-center justify-center w-8 h-8 rounded-md text-gray-500 hover:text-red-600 hover:bg-gray-100 transition
                                                   {{ !$canCancel ? 'opacity-40 cursor-not-allowed' : '' }}" 
                                            title="Batalkan" @disabled(!$canCancel)>
                                        <i class="fas fa-trash fa-fw"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    {{-- Tampilan jika tidak ada data --}}
                    <tr>
                        <td colspan="11" class="text-center py-10 text-gray-500">
                            <div class="flex flex-col items-center">
                                <i class="fas fa-box-open fa-3x text-gray-400 mb-3"></i>
                                <h3 class="text-lg font-medium text-gray-700">Tidak ada pesanan ditemukan</h3>
                                <p class="text-sm text-gray-500">Coba ubah filter pencarian Anda.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- PAGINATION --}}
    {{-- Tampilkan link pagination jika ada data --}}
    @if ($orders->hasPages())
        <div class="p-4 border-t border-gray-200">
            {{-- 'links()' akan merender link pagination Tailwind --}}
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

