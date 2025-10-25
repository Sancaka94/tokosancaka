@extends('layouts.admin')

@section('title', 'Data Pesanan Masuk')
@section('page-title', 'Data Pesanan Masuk')

@push('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
<style>
    /* === WRAPPER TABEL === */
    .table-container {
        overflow-x: auto;
        overflow-y: auto;
        -webkit-overflow-scrolling: touch;
        background: #fff;
        border-radius: 0.5rem;
        box-shadow: 0 1px 4px rgba(0,0,0,0.08);
    }

    /* === TABEL === */
    table.table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        font-size: 14px;
    }

    thead th {
        position: sticky;
        top: 0;
        z-index: 10;
        background-color: #f9fafb;
        font-weight: 600;
        color: #374151;
        text-transform: uppercase;
        font-size: 12px;
        border-bottom: 2px solid #e5e7eb;
        padding: 0.75rem 1rem;
        white-space: nowrap;
    }

    tbody td {
        padding: 0.75rem 1rem;
        vertical-align: top;
        border-bottom: 1px solid #f3f4f6;
        background-color: #fff;
    }

    tbody tr:hover {
        background-color: #f8fafc;
    }

    /* === KOLOM STICKY KANAN === */
    th.sticky-col,
    td.sticky-col {
        position: sticky;
        right: 0;
        top: 0;
        background-color: #fff;
        z-index: 20;
        box-shadow: -3px 0 6px rgba(0,0,0,0.05);
        border-left: 1px solid #e5e7eb;
        min-width: 140px;
    }

    thead th.sticky-col {
        background-color: #f3f4f6;
        z-index: 25;
    }

    /* === AKSI === */
    .action-buttons {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 0.4rem;
    }

    .action-buttons a,
    .action-buttons button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 30px;
        height: 30px;
        border-radius: 6px;
        color: #6b7280;
        transition: all 0.2s ease;
    }

    .action-buttons a:hover,
    .action-buttons button:hover {
        background-color: #f3f4f6;
        color: #2563eb;
    }

    .btn-delete:hover { color: #dc2626; }
    .btn-pdf:hover { color: #ef4444; }
    .btn-track:hover { color: #16a34a; }
    .btn-print:hover { color: #374151; }
    .btn-chat:hover { color: #3b82f6; }

    /* === FILTER === */
    .filter-button {
        @apply px-3 py-1 text-sm font-medium rounded-full transition duration-150 ease-in-out focus:outline-none whitespace-nowrap;
    }
    .filter-button-active {
        @apply bg-indigo-600 text-white;
    }
    .filter-button-inactive {
        @apply bg-gray-200 text-gray-700 hover:bg-gray-300;
    }
</style>
@endpush

@section('content')
<div class="px-4 sm:px-6 lg:px-8 py-6">
    <div class="bg-white shadow rounded-lg overflow-hidden">
        {{-- HEADER --}}
        <div class="flex flex-col md:flex-row items-center justify-between border-b border-gray-200 p-4 gap-3">
            <form action="{{ route('admin.orders.index') }}" method="GET" class="w-full md:w-1/3">
                <div class="relative">
                    <input type="text" name="search" placeholder="Cari Resi, Invoice, Nama..."
                        value="{{ request('search') }}"
                        class="w-full pl-10 pr-3 py-2 text-sm border rounded-md focus:ring-indigo-500 focus:border-indigo-500">
                    <i class="fas fa-search absolute left-3 top-2.5 text-gray-400"></i>
                </div>
                @if(request('status'))
                    <input type="hidden" name="status" value="{{ request('status') }}">
                @endif
            </form>

            <button type="button" data-bs-toggle="modal" data-bs-target="#exportModal"
                class="inline-flex items-center px-4 py-2 bg-gray-700 hover:bg-gray-800 text-white text-sm font-medium rounded-md shadow-sm">
                <i class="fas fa-download mr-2"></i> Export Laporan
            </button>
        </div>

        {{-- FILTER STATUS --}}
        <div class="p-4 border-b border-gray-100 flex flex-wrap gap-2">
            <a href="{{ route('admin.orders.index', request()->except('status','page')) }}"
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

        {{-- TABEL --}}
        <div class="table-container">
            <table class="table text-sm text-gray-700">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Transaksi</th>
                        <th>Alamat</th>
                        <th>Ekspedisi</th>
                        <th>Paket</th>
                        <th>Status</th>
                        <th class="sticky-col text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $index => $order)
                        @php
                            $invoice = $order->invoice_number;
                            $resi = $order->tracking_number ?? $order->resi ?? null;
                            $canCancel = in_array($order->status, ['pending','paid','processing']);
                        @endphp
                        <tr>
                            <td>{{ $orders->firstItem() + $index }}</td>
                            <td>
                                <div><b>{{ strtoupper($order->payment_method ?? '-') }}</b></div>
                                <div>{{ $invoice }}</div>
                                <div class="text-xs text-gray-500">{{ $order->created_at->format('d M Y, H:i') }}</div>
                                <div class="font-bold text-indigo-600 mt-1">Rp{{ number_format($order->total_amount, 0, ',', '.') }}</div>
                            </td>
                            <td>
                                <div class="mb-1">
                                    <small class="text-gray-500">Dari:</small>
                                    <strong class="text-blue-700">{{ $order->store->name ?? '-' }}</strong><br>
                                    <small>{{ $order->store->address_detail ?? '' }}</small>
                                </div>
                                <div>
                                    <small class="text-gray-500">Kepada:</small>
                                    <strong class="text-red-700">{{ $order->user->nama_lengkap ?? '-' }}</strong><br>
                                    <small>{{ $order->shipping_address ?? '-' }}</small>
                                </div>
                            </td>
                            <td>
                                @php $ship = \App\Helpers\ShippingHelper::parseShippingMethod($order->shipping_method); @endphp
                                @if($ship['logo_url'])
                                    <img src="{{ $ship['logo_url'] }}" alt="" class="h-5 mb-1 max-w-[90px]">
                                @endif
                                <div class="text-sm">{{ $ship['courier_name'] ?? '-' }}</div>
                                <small>{{ $ship['service_name'] ?? '' }}</small>
                            </td>
                            <td>
                                @php $first = $order->items->first(); @endphp
                                {{ $first->product->name ?? '-' }}
                            </td>
                            <td>
                                @php
                                    $status = $order->status;
                                    $text = \App\Helpers\OrderStatusHelper::getStatusText($status);
                                    $badge = \App\Helpers\OrderStatusHelper::getStatusBadgeClass($status);
                                @endphp
                                <span class="px-2 py-1 rounded-full text-xs font-semibold {{ $badge }}">
                                    {{ $text }}
                                </span>
                            </td>
                            <td class="sticky-col text-right">
                                <div class="action-buttons">
                                    <a href="{{ $resi ? 'https://tokosancaka.com/tracking/search?resi=' . e($resi) : '#' }}" target="_blank" class="btn-track" title="Lacak" @disabled(!$resi)>
                                        <i class="fas fa-truck"></i>
                                    </a>
                                    <a href="{{ route('admin.orders.show', $invoice) }}" title="Detail">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('admin.orders.print.thermal', $invoice) }}" target="_blank" class="btn-print" title="Print">
                                        <i class="fas fa-print"></i>
                                    </a>
                                    <a href="{{ route('admin.orders.invoice.pdf', $invoice) }}" target="_blank" class="btn-pdf" title="PDF">
                                        <i class="fas fa-file-pdf"></i>
                                    </a>
                                    <a href="{{ route('admin.chat.start', ['id_pengguna' => $order->user_id]) }}" target="_blank" class="btn-chat" title="Chat">
                                        <i class="fas fa-comment"></i>
                                    </a>
                                    <form action="{{ route('admin.orders.cancel', $invoice) }}" method="POST" onsubmit="return confirm('Batalkan pesanan ini?')">
                                        @csrf @method('PATCH')
                                        <button type="submit" class="btn-delete" title="Batalkan" @disabled(!$canCancel)>
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-6 text-gray-500">
                                <i class="fas fa-box-open text-gray-400"></i><br>
                                Tidak ada pesanan ditemukan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4">
            {{ $orders->links() }}
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
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<script>
$(function(){
    toastr.options = { closeButton: true, progressBar: true, timeOut: 3000 };
});
</script>
@endpush
