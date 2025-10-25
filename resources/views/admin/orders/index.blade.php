{{-- Halaman ini adalah view untuk 'AdminOrderController@index' --}}
{{-- Menggunakan layout admin Tailwind CSS dan Pagination Server-Side --}}
@extends('layouts.admin') {{-- Pastikan nama layout ini benar --}}

@section('title', 'Data Pesanan Masuk')
@section('page-title', 'Data Pesanan Masuk')

@push('styles')

<!-- CSS Toastr -->

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">

{{-- CSS Khusus untuk Sticky Column dan Styling Tabel --}}

<style>
/* Container tabel agar bisa scroll horizontal /
.table-container {
overflow-x: auto;
-webkit-overflow-scrolling: touch; / Scrolling halus di iOS */
}

/* Styling untuk kolom sticky /
th.sticky-col, td.sticky-col {
position: -webkit-sticky; / Safari /
position: sticky;
right: 0;               / Menempel di kanan /
background-color: white; / Background solid putih untuk sel data /
z-index: 10;             / Di atas kolom lain /
border-left: 1px solid #e5e7eb; / Garis pemisah kiri (gray-200) */
}

/* Background solid untuk header sticky /
thead th.sticky-col {
background-color: #f9fafb; / gray-50 (sesuaikan dengan thead Anda) /
z-index: 11; / Di atas sel data sticky */
}

/* Styling tambahan agar tabel responsif /
.table {
border-collapse: separate;
border-spacing: 0;
width: 100%; / Pastikan tabel mengisi container /
}
.table th, .table td {
border-bottom-width: 1px;
border-color: #e5e7eb; / gray-200 /
white-space: nowrap; / Default: jangan wrap text di sel /
padding: 0.75rem 1.5rem; / Sesuaikan padding sel /
vertical-align: top; / Ratakan konten ke atas /
}
/ Kecuali kolom alamat bisa wrap /
.table td.address-col {
white-space: normal;
min-width: 250px; / Beri lebar minimum untuk kolom alamat /
}
.table td.package-col {
white-space: normal; / Kolom isi paket juga bisa wrap /
min-width: 250px;
}
.table thead th {
border-top-width: 1px;
white-space: nowrap; / Header jangan wrap */
}

/* Style untuk tombol filter status */
.filter-button {
@apply px-3 py-1 text-sm font-medium rounded-full transition duration-150 ease-in-out focus:outline-none focus:ring-2 focus:ring-offset-2 whitespace-nowrap;
}
.filter-button-active {
@apply bg-indigo-600 text-white focus:ring-indigo-500;
}
.filter-button-inactive {
@apply bg-gray-200 text-gray-700 hover:bg-gray-300 focus:ring-gray-400;
}

/* Styling tombol aksi /
.action-buttons div {
display: flex;
align-items: center;
gap: 0.5rem; / Jarak antar ikon/tombol /
justify-content: flex-end; / Ratakan ke kanan /
}
.action-buttons a, .action-buttons button {
@apply p-1 text-gray-500 hover:text-indigo-600 focus:outline-none focus:ring-2 focus:ring-offset-1 focus:ring-indigo-500 rounded; / Styling dasar ikon aksi /
}
.action-buttons a[disabled], .action-buttons button[disabled] {
@apply opacity-50 cursor-not-allowed hover:text-gray-500; / Styling disabled /
}
.action-buttons form {
display: inline-flex;
vertical-align: middle;
}
.action-buttons .btn-delete:hover {
@apply text-red-600; / Warna hover khusus tombol delete /
}
.action-buttons .btn-track:hover {
@apply text-green-600; / Warna hover tombol track /
}
.action-buttons .btn-print:hover {
@apply text-gray-800; / Warna hover tombol print /
}
.action-buttons .btn-pdf:hover {
@apply text-red-600; / Warna hover tombol pdf /
}
.action-buttons .btn-chat:hover {
@apply text-blue-600; / Warna hover tombol chat */
}
</style>

@endpush

@section('content')

<div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">

<!-- Judul Halaman -->

<h1 class="text-2xl font-semibold text-gray-800 mb-6">Data Pesanan Masuk</h1>

<!-- Card Utama -->

<div class="bg-white shadow rounded-lg mb-6 overflow-hidden">
{{-- Header Card: Pencarian dan Tombol Aksi --}}
<div class="px-6 py-4 border-b border-gray-200 flex flex-col md:flex-row items-center justify-between gap-4">

    {{-- Form Pencarian (menggunakan GET) --}}
    <form action="{{ route('admin.orders.index') }}" method="GET" class="w-full md:w-1/3">
        <div class="relative flex items-stretch w-full">
            <input type="text" name="search" class="block w-full px-4 py-2 pl-10 text-sm text-gray-700 bg-gray-100 border border-gray-300 rounded-l-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" placeholder="Cari Resi, Invoice, Nama..." value="{{ request('search') }}">
             <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-gray-400">
                 <i class="fas fa-search"></i>
            </div>
        </div>
         {{-- Hidden input untuk menjaga filter status saat search --}}
        @if(request('status'))
            <input type="hidden" name="status" value="{{ request('status') }}">
        @endif
    </form>

    {{-- Tombol Aksi Kanan Atas --}}
    <div class="flex items-center gap-2 flex-shrink-0">
        <button type="button" class="inline-flex items-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white text-sm font-medium rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500" data-bs-toggle="modal" data-bs-target="#exportModal">
            <i class="fas fa-download fa-sm mr-2 opacity-75"></i> Export Laporan
        </button>
         {{-- Tombol Tambah Pesanan (jika diperlukan) --}}
         {{-- <a href="{{ route('admin.orders.create') }}" class="...">...</a> --}}
    </div>
</div>

{{-- Body Card: Filter Tab dan Tabel --}}
<div class="p-6">
    {{-- Filter Status (link GET) --}}
     <div class="flex flex-wrap gap-2 mb-4 border-b pb-4">
         {{-- Tombol 'Semua' --}}
         <a href="{{ route('admin.orders.index', request()->except('status', 'page')) }}" class="filter-button {{ !request('status') ? 'filter-button-active' : 'filter-button-inactive' }}">Semua</a>
         
         {{-- Tombol Status Lain --}}
         @php
             // Definisikan filter status Anda
             $statusFilters = [
                 'pending' => 'Menunggu Bayar',
                 'menunggu-pickup' => 'Menunggu Pickup',
                 'diproses' => 'Diproses',
                 'terkirim' => 'Terkirim',
                 'selesai' => 'Selesai',
                 'batal' => 'Batal',
             ];
         @endphp

         @foreach($statusFilters as $statusKey => $statusLabel)
             <a href="{{ route('admin.orders.index', array_merge(request()->query(), ['status' => $statusKey, 'page' => 1])) }}"
                class="filter-button {{ request('status') == $statusKey ? 'filter-button-active' : 'filter-button-inactive' }}">
                 {{ $statusLabel }}
             </a>
         @endforeach
     </div>

      {{-- Notifikasi Sukses/Error (jika ada) --}}
      @include('layouts.partials.notifications') {{-- Sesuaikan path jika perlu --}}

    {{-- Container Tabel untuk Scroll Horizontal --}}
    <div class="table-container">
        <table class="table w-full text-sm text-left text-gray-500">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3">NO</th>
                    <th scope="col" class="px-6 py-3">TRANSAKSI</th>
                    <th scope="col" class="px-6 py-3">ALAMAT</th>
                    <th scope="col" class="px-6 py-3">EKSPEDISI & ONGKIR</th>
                    <th scope="col" class="px-6 py-3">ISI PAKET</th>
                    <th scope="col" class="px-6 py-3">STATUS</th>
                    {{-- Kolom Aksi (sticky) --}}
                    <th scope="col" class="px-6 py-3 sticky-col">AKSI</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                {{-- Loop data $orders dari controller --}}
                @forelse ($orders as $index => $order)
                    <tr>
                        {{-- No Urut (dari pagination) --}}
                        <td class="px-6 py-4 align-top whitespace-nowrap">{{ $orders->firstItem() + $index }}</td>

                        {{-- Transaksi --}}
                        <td class="px-6 py-4 align-top whitespace-nowrap">
                            <div><strong>{{ strtoupper($order->payment_method ?? 'N/A') }}</strong></div>
                            <div class="font-medium text-gray-800">{{ $order->invoice_number }}</div>
                            <div class="text-xs text-gray-500">{{ \Carbon\Carbon::parse($order->created_at)->translatedFormat('d M Y, H:i') }}</div>
                            {{-- TAMBAHAN: Menampilkan Total Amount --}}
                            <div class="font-bold text-lg text-indigo-600 mt-1">Rp{{ number_format($order->total_amount, 0, ',', '.') }}</div>
                        </td>

                        {{-- Alamat (dibuat bisa wrap) --}}
                        <td class="px-6 py-4 align-top address-col">
                            <div class="mb-1">
                                <small class="text-gray-500">Dari:</small>
                                <strong class="text-blue-700">{{ $order->store->name ?? 'Toko N/A' }}</strong>
                                <div class="text-xs text-gray-600">
                                    {{ $order->store->address_detail ?? 'Alamat N/A' }},
                                    {{ implode(', ', array_filter([$order->store->village ?? null, $order->store->district ?? null, $order->store->regency ?? null])) ?: 'Wilayah N/A' }}
                                </div>
                            </div>
                            <div>
                                <small class="text-gray-500">Kepada:</small>
                                <strong class="text-red-700">{{ $order->user->nama_lengkap ?? 'Pembeli N/A' }}</strong> ({{ $order->user->no_wa ?? 'No WA N/A' }})
                                <div class="text-xs text-gray-600">
                                    {{ $order->shipping_address ?? 'Alamat N/A' }},
                                     {{ implode(', ', array_filter([$order->user->village ?? null, $order->user->district ?? null, $order->user->regency ?? null])) ?: 'Wilayah N/A' }}
                                </div>
                            </div>
                        </td>

                        {{-- Ekspedisi & Ongkir --}}
                        <td class="px-6 py-4 align-top whitespace-nowrap">
                            {{-- Gunakan Helper untuk parsing (dari App\Helpers\ShippingHelper.php) --}}
                            @php $shippingInfo = \App\Helpers\ShippingHelper::parseShippingMethod($order->shipping_method); @endphp
                            
                            {{-- Tampilkan logo jika ada --}}
                            @if($shippingInfo['logo_url'])
                            <img src="{{ $shippingInfo['logo_url'] }}" alt="{{ $shippingInfo['courier_name'] }}" class="h-6 mb-1 max-w-[100px] object-contain">
                            @else
                            <div class="font-bold text-gray-800">{{ $shippingInfo['courier_name'] }}</div>
                            @endif
                            
                            <div><small>{{ $shippingInfo['service_name'] }} ({{ $shippingInfo['type'] }})</small></div>
                            {{-- DIPERBAIKI: Menggunakan shipping_cost dari database --}}
                            <div class="font-semibold text-green-700">Rp{{ number_format($order->shipping_cost, 0, ',', '.') }}</div>
                        </td>

                        {{-- Isi Paket --}}
                        <td class="px-6 py-4 align-top package-col">
                            @php
                                $firstItem = $order->items->first();
                                $itemName = '<span class="text-gray-400">N/A</span>'; // Default
                                $itemDetails = '';
                                if ($firstItem) {
                                    $productName = $firstItem->product->name ?? '<span class="text-red-500">Produk Dihapus</span>';
                                    $variantName = '';
                                    if ($firstItem->variant) {
                                         $comboString = $firstItem->variant->combination_string ? str_replace(';', ', ', $firstItem->variant->combination_string) : $firstItem->variant->sku_code;
                                         $variantName = ' <span class="text-xs text-gray-500">(' . ($comboString ?: 'Varian N/A') . ')</span>';
                                    }
                                    $itemName = $productName . $variantName . ' x ' . $firstItem->quantity;
                                    $totalItems = $order->items->count();
                                    if ($totalItems > 1) { $itemName .= ' <span class="text-xs text-gray-500"> + ' . ($totalItems - 1) . ' item lain</span>'; }

                                    $totalWeight = $order->items->sum(fn($item) => ($item->product->weight ?? 0) * $item->quantity);
                                    $length = $firstItem->product->length ?? '-';
                                    $width = $firstItem->product->width ?? '-';
                                    $height = $firstItem->product->height ?? '-';
                                    $itemDetails = "<span class='text-xs text-gray-500'>Berat: {$totalWeight} gr | Dimensi: {$length}x{$width}x{$height} cm</span>";
                                }
                            @endphp
                            <div class="font-semibold text-gray-800">{!! $itemName !!}</div>
                            <div class="mt-1">{!! $itemDetails !!}</div>
                        </td>

                        {{-- Status --}}
                        <td class="px-6 py-4 align-top whitespace-nowrap">
                            {{-- Gunakan Helper untuk status (buat App\Helpers\OrderStatusHelper.php jika belum) --}}
                            @php
                                $status = $order->status;
                                // Asumsi Anda punya Helper, jika tidak ganti dengan $order->status
                                $statusText = \App\Helpers\OrderStatusHelper::getStatusText($status);
                                $badgeClass = \App\Helpers\OrderStatusHelper::getStatusBadgeClass($status);
                            @endphp
                           <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full {{ $badgeClass }}">
                                {{ $statusText }}
                           </span>
                        </td>

                        {{-- Aksi (sticky) --}}
                        <td class="px-6 py-4 align-top whitespace-nowrap text-sm font-medium sticky-col action-buttons">
                            <div> {{-- Container flex untuk tombol --}}
                                @php
                                    $invoice = $order->invoice_number;
                                    $resi = $order->tracking_number ?? $order->resi ?? null;
                                    $customerUserId = $order->user_id;
                                    $canCancel = in_array($order->status, ['pending', 'paid', 'processing']); // Sesuaikan status
                                @endphp

                                {{-- Lacak Paket --}}
<a href="{{ $resi ? 'https://tokosancaka.com/tracking/search?resi=' . e($resi) : '#' }}" 
   target="_blank" 
   class="btn-track" 
   title="Lacak Paket" 
   @disabled(!$resi)>
    <i class="fas fa-truck fa-fw"></i>
</a>


                                {{-- Detail --}}
                                <a href="{{ route('admin.orders.show', $invoice) }}" title="Detail Pesanan">
                                    <i class="fas fa-eye fa-fw"></i>
                                </a>

                                {{-- Thermal --}}
                                <a href="{{ route('admin.orders.print.thermal', $invoice) }}" target="_blank" class="btn-print" title="Cetak Label Thermal">
                                    <i class="fas fa-print fa-fw"></i>
                                </a>

                                {{-- Invoice PDF --}}
                                 <a href="{{ route('admin.orders.invoice.pdf', $invoice) }}" target="_blank" class="btn-pdf" title="Unduh Faktur PDF">
                                     <i class="fas fa-file-pdf fa-fw"></i>
                                 </a>

                                {{-- Chat Penerima --}}
                                <a href="{{ $customerUserId ? route('admin.chat.start', ['id_pengguna' => $customerUserId]) : '#' }}" target="_blank" class="btn-chat" title="Chat Penerima" @disabled(!$customerUserId)>
                                    <i class="fas fa-comment fa-fw"></i>
                                </a>

                                {{-- Cancel/Hapus --}}
                                <form action="{{ route('admin.orders.cancel', $invoice) }}" method="POST" onsubmit="return confirm('Anda yakin ingin membatalkan pesanan ini?')">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="btn-delete" title="Batalkan Pesanan" @disabled(!$canCancel)>
                                        <i class="fas fa-trash fa-fw"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    {{-- Jika $orders kosong --}}
                    <tr>
                        <td colspan="7" class="text-center py-10 text-gray-500">
                            <i class="fas fa-box-open fa-3x mb-3 text-gray-400"></i><br>
                            Data pesanan tidak ditemukan.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div> {{-- Akhir table-container --}}

    {{-- Link Pagination --}}
    <div class="mt-6 pagination-container">
         {{-- Pastikan $orders di-pass dari controller dengan ->paginate() --}}
         {{ $orders->links() }}
    </div>
</div> {{-- Akhir card body --}}


</div> {{-- Akhir card --}}
</div> {{-- Akhir container --}}

<!-- Modal Export Laporan -->

{{-- Pastikan Anda memiliki file modal ini --}}
@include('layouts.partials.modals.export', ['excel_route' => route('admin.pesanan.export.excel'), 'pdf_route' => route('admin.orders.report.pdf')])
@endsection

@push('scripts')

<!-- jQuery -->

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>

<!-- Bootstrap JS (Hanya jika modal masih pakai Bootstrap) -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Toastr JS -->

<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

{{-- Laravel Echo JS (jika Anda menggunakannya untuk notifikasi real-time) --}}
{{-- <script src="{{ mix('js/app.js') }}"></script> --}}

<script>
$(document).ready(function() {
console.log("Halaman Order Index siap.");

// Laravel Echo Listener (Contoh jika Anda pakai)
if (typeof Echo !== &#39;undefined&#39;) {
    console.log(&#39;Laravel Echo siap, mendengarkan notifikasi...&#39;);
    Echo.channel(&#39;admin-notifications&#39;) // Sesuaikan nama channel
        .listen(&#39;AdminNotificationEvent&#39;, (e) =&gt; { // Sesuaikan nama event
            console.log(&#39;Notifikasi diterima:&#39;, e);
            toastr.options = {
                &quot;closeButton&quot;: true, &quot;progressBar&quot;: true, &quot;positionClass&quot;: &quot;toast-top-right&quot;, &quot;timeOut&quot;: &quot;8000&quot;,
            };
            // Sarankan refresh manual saat ada notifikasi
            toastr.info((e.message || &#39;Ada update pesanan!&#39;) + &#39;<br>&lt;small&gt;Refresh halaman untuk melihat perubahan.&lt;/small&gt;&#39;, e.title || &#39;Notifikasi Pesanan&#39;);
        });
} else {
    console.warn(&#39;Laravel Echo tidak terdefinisi.&#39;);
}

// Konfigurasi default Toastr
toastr.options = { &quot;positionClass&quot;: &quot;toast-top-right&quot;, &quot;progressBar&quot;: true, &quot;timeOut&quot;: &quot;4000&quot; };


});
</script>

@endpush