{{-- Halaman ini adalah view untuk 'AdminOrderController@index' --}}
{{-- Menggunakan layout admin Tailwind CSS dan Pagination Server-Side --}}
@extends('layouts.admin')

@section('title', 'Data Pesanan Masuk')
@section('page-title', 'Data Pesanan Masuk')

@push('styles')
<!-- CSS Toastr -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">

{{-- CSS Khusus untuk Sticky Column dan Styling Tabel --}}
<style>
.table-container {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch; /* Scrolling halus di iOS */
}

/* === KOLOM STICKY (KANAN & ATAS) === */
th.sticky-col,
td.sticky-col {
    position: -webkit-sticky; /* Safari */
    position: sticky;
    right: 0; /* Menempel di kanan */
    top: 0; /* Tetap di atas */
    background-color: #fff;
    z-index: 20;
    border-left: 1px solid #e5e7eb;
    box-shadow: -3px 0 6px rgba(0, 0, 0, 0.05);
}

thead th.sticky-col {
    background-color: #f9fafb;
    z-index: 25;
    font-weight: 600;
}

/* === TABEL DASAR === */
.table {
    border-collapse: separate;
    border-spacing: 0;
    width: 100%;
}
.table th,
.table td {
    border-bottom-width: 1px;
    border-color: #e5e7eb;
    white-space: nowrap;
    padding: 0.75rem 1.5rem;
    vertical-align: top;
}
.table td.address-col {
    white-space: normal;
    min-width: 250px;
}
.table td.package-col {
    white-space: normal;
    min-width: 250px;
}
.table thead th {
    border-top-width: 1px;
    white-space: nowrap;
}

/* === FILTER STATUS === */
.filter-button {
    @apply px-3 py-1 text-sm font-medium rounded-full transition duration-150 ease-in-out focus:outline-none focus:ring-2 focus:ring-offset-2 whitespace-nowrap;
}
.filter-button-active {
    @apply bg-indigo-600 text-white focus:ring-indigo-500;
}
.filter-button-inactive {
    @apply bg-gray-200 text-gray-700 hover:bg-gray-300 focus:ring-gray-400;
}

/* === AKSI === */
.action-buttons div {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 0.5rem;
}
.action-buttons a,
.action-buttons button {
    @apply p-1 text-gray-500 hover:text-indigo-600 focus:outline-none focus:ring-2 focus:ring-offset-1 focus:ring-indigo-500 rounded;
}
.action-buttons a[disabled],
.action-buttons button[disabled] {
    @apply opacity-50 cursor-not-allowed hover:text-gray-500;
}
.action-buttons form {
    display: inline-flex;
    vertical-align: middle;
}
.action-buttons .btn-delete:hover {
    @apply text-red-600;
}
.action-buttons .btn-track:hover {
    @apply text-green-600;
}
.action-buttons .btn-print:hover {
    @apply text-gray-800;
}
.action-buttons .btn-pdf:hover {
    @apply text-red-600;
}
.action-buttons .btn-chat:hover {
    @apply text-blue-600;
}
</style>
@endpush

@section('content')
<div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <h1 class="text-2xl font-semibold text-gray-800 mb-6">Data Pesanan Masuk</h1>

    <div class="bg-white shadow rounded-lg mb-6 overflow-hidden">
        {{-- HEADER CARD --}}
        <div class="px-6 py-4 border-b border-gray-200 flex flex-col md:flex-row items-center justify-between gap-4">
            {{-- Pencarian --}}
            <form action="{{ route('admin.orders.index') }}" method="GET" class="w-full md:w-1/3">
                <div class="relative flex items-stretch w-full">
                    <input type="text" name="search" placeholder="Cari Resi, Invoice, Nama..."
                        value="{{ request('search') }}"
                        class="block w-full px-4 py-2 pl-10 text-sm text-gray-700 bg-gray-100 border border-gray-300 rounded-l-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                        <i class="fas fa-search"></i>
                    </div>
                </div>
                @if(request('status'))
                    <input type="hidden" name="status" value="{{ request('status') }}">
                @endif
            </form>

            {{-- Tombol kanan --}}
            <div class="flex items-center gap-2">
                <button type="button" data-bs-toggle="modal" data-bs-target="#exportModal"
                    class="inline-flex items-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white text-sm font-medium rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                    <i class="fas fa-download fa-sm mr-2 opacity-75"></i> Export Laporan
                </button>
            </div>
        </div>

        {{-- FILTER STATUS --}}
        <div class="p-6">
            <div class="flex flex-wrap gap-2 mb-4 border-b pb-4">
                <a href="{{ route('admin.orders.index', request()->except('status', 'page')) }}"
                   class="filter-button {{ !request('status') ? 'filter-button-active' : 'filter-button-inactive' }}">
                    Semua
                </a>
                @php
                    $statusFilters = [
                        'pending' => 'Menunggu Bayar',
                        'menunggu-pickup' => 'Menunggu Pickup',
                        'diproses' => 'Diproses',
                        'terkirim' => 'Terkirim',
                        'selesai' => 'Selesai',
                        'batal' => 'Batal',
                    ];
                @endphp
                @foreach($statusFilters as $key => $label)
                    <a href="{{ route('admin.orders.index', array_merge(request()->query(), ['status' => $key, 'page' => 1])) }}"
                       class="filter-button {{ request('status') == $key ? 'filter-button-active' : 'filter-button-inactive' }}">
                       {{ $label }}
                    </a>
                @endforeach
            </div>

            @include('layouts.partials.notifications')

            {{-- TABEL PESANAN --}}
            <div class="table-container">
                <table class="table w-full text-sm text-left text-gray-500">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                        <tr>
                            <th class="px-6 py-3">NO</th>
                            <th class="px-6 py-3">TRANSAKSI</th>
                            <th class="px-6 py-3">ALAMAT</th>
                            <th class="px-6 py-3">EKSPEDISI & ONGKIR</th>
                            <th class="px-6 py-3">ISI PAKET</th>
                            <th class="px-6 py-3">STATUS</th>
                            <th class="px-6 py-3 sticky-col">AKSI</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse ($orders as $index => $order)
                            <tr>
                                <td class="px-6 py-4 align-top">{{ $orders->firstItem() + $index }}</td>
                                <td class="px-6 py-4 align-top">
                                    <div><strong>{{ strtoupper($order->payment_method ?? 'N/A') }}</strong></div>
                                    <div class="font-medium text-gray-800">{{ $order->invoice_number }}</div>
                                    <div class="text-xs text-gray-500">{{ \Carbon\Carbon::parse($order->created_at)->translatedFormat('d M Y, H:i') }}</div>
                                    <div class="font-bold text-lg text-indigo-600 mt-1">Rp{{ number_format($order->total_amount, 0, ',', '.') }}</div>
                                </td>
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
                                        <strong class="text-red-700">{{ $order->user->nama_lengkap ?? 'Pembeli N/A' }}</strong>
                                        ({{ $order->user->no_wa ?? 'No WA N/A' }})
                                        <div class="text-xs text-gray-600">
                                            {{ $order->shipping_address ?? 'Alamat N/A' }},
                                            {{ implode(', ', array_filter([$order->user->village ?? null, $order->user->district ?? null, $order->user->regency ?? null])) ?: 'Wilayah N/A' }}
                                        </div>
                                    </div>
                                </td>

                                {{-- Ekspedisi --}}
                                @php $shipping = \App\Helpers\ShippingHelper::parseShippingMethod($order->shipping_method); @endphp
                                <td class="px-6 py-4 align-top">
                                    @if($shipping['logo_url'])
                                        <img src="{{ $shipping['logo_url'] }}" alt="{{ $shipping['courier_name'] }}" class="h-6 mb-1 max-w-[100px] object-contain">
                                    @else
                                        <div class="font-bold text-gray-800">{{ $shipping['courier_name'] }}</div>
                                    @endif
                                    <div><small>{{ $shipping['service_name'] }} ({{ $shipping['type'] }})</small></div>
                                    <div class="font-semibold text-green-700">Rp{{ number_format($order->shipping_cost, 0, ',', '.') }}</div>
                                </td>

                                {{-- Isi Paket --}}
                                @php
                                    $firstItem = $order->items->first();
                                    $itemName = $firstItem ? ($firstItem->product->name ?? '<span class="text-red-500">Produk Dihapus</span>') : '<span class="text-gray-400">N/A</span>';
                                @endphp
                                <td class="px-6 py-4 align-top package-col">
                                    <div class="font-semibold text-gray-800">{!! $itemName !!}</div>
                                </td>

                                {{-- Status --}}
                                @php
                                    $status = $order->status;
                                    $statusText = \App\Helpers\OrderStatusHelper::getStatusText($status);
                                    $badgeClass = \App\Helpers\OrderStatusHelper::getStatusBadgeClass($status);
                                @endphp
                                <td class="px-6 py-4 align-top">
                                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full {{ $badgeClass }}">
                                        {{ $statusText }}
                                    </span>
                                </td>

                                {{-- Aksi --}}
                                <td class="px-6 py-4 align-top sticky-col action-buttons">
                                    <div>
                                        @php
                                            $invoice = $order->invoice_number;
                                            $resi = $order->tracking_number ?? $order->resi ?? null;
                                            $canCancel = in_array($order->status, ['pending', 'paid', 'processing']);
                                        @endphp

                                        <a href="{{ $resi ? 'https://tokosancaka.com/tracking/search?resi=' . e($resi) : '#' }}"
                                           target="_blank" class="btn-track" title="Lacak Paket" @disabled(!$resi)>
                                            <i class="fas fa-truck fa-fw"></i>
                                        </a>
                                        <a href="{{ route('admin.orders.show', $invoice) }}" title="Detail Pesanan">
                                            <i class="fas fa-eye fa-fw"></i>
                                        </a>
                                        <a href="{{ route('admin.orders.print.thermal', $invoice) }}" target="_blank" class="btn-print" title="Cetak Label Thermal">
                                            <i class="fas fa-print fa-fw"></i>
                                        </a>
                                        <a href="{{ route('admin.orders.invoice.pdf', $invoice) }}" target="_blank" class="btn-pdf" title="Unduh Faktur PDF">
                                            <i class="fas fa-file-pdf fa-fw"></i>
                                        </a>
                                        <a href="{{ route('admin.chat.start', ['id_pengguna' => $order->user_id]) }}" target="_blank" class="btn-chat" title="Chat Penerima" @disabled(!$order->user_id)>
                                            <i class="fas fa-comment fa-fw"></i>
                                        </a>
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
                            <tr>
                                <td colspan="7" class="text-center py-10 text-gray-500">
                                    <i class="fas fa-box-open fa-3x mb-3 text-gray-400"></i><br>
                                    Data pesanan tidak ditemukan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-6">
                {{ $orders->links() }}
            </div>
        </div>
    </div>
</div>

@include('layouts.partials.modals.export', [
    'excel_route' => route('admin.pesanan.export.excel'),
    'pdf_route' => route('admin.orders.report.pdf')
])
@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<script>
$(function() {
    toastr.options = {
        closeButton: true,
        progressBar: true,
        positionClass: "toast-top-right",
        timeOut: "4000"
    };
    console.log("Halaman Order Index siap.");
});
</script>
@endpush
