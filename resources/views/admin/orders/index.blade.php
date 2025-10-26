@extends('layouts.admin')

@section('title', 'Data Pesanan Masuk')
@section('page-title', 'Data Pesanan Masuk')

{{-- Menghapus @push('styles') karena semua styling dipindahkan ke utility classes Tailwind --}}

@section('content')
{{-- PERBAIKAN: Tambahkan wrapper 'w-full min-w-0' untuk mengatasi overflow flexbox --}}
<div class="w-full min-w-0">
    <div class="px-4 sm:px-6 lg:px-8 py-6">
        {{-- 'overflow-hidden' di sini penting untuk "memotong" div tabel di bawah --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            
            {{-- HEADER: Pencarian dan Tombol Ekspor --}}
            <div class="flex flex-col md:flex-row items-center justify-between border-b border-gray-200 p-4 sm:p-6 gap-3">
                <form action="{{ route('admin.orders.index') }}" method="GET" class="w-full md:w-1/3">
                    <div class="relative">
                        <input type="text" name="search" placeholder="Cari Resi, Invoice, Nama..."
                               value="{{ request('search') }}"
                               class="w-full pl-10 pr-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition duration-150">
                        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    </div>
                    @if(request('status'))
                        <input type="hidden" name="status" value="{{ request('status') }}">
                    @endif
                </form>
    
                <button type="button" data-bs-toggle="modal" data-bs-target="#exportModal"
                        class="inline-flex items-center px-4 py-2 bg-gray-700 hover:bg-gray-800 text-white text-sm font-medium rounded-md shadow-sm transition duration-150 ease-in-out whitespace-nowrap">
                    <i class="fas fa-download -ml-1 mr-2 h-4 w-4"></i>
                    Export Laporan
                </button>
            </div>
    
            {{-- FILTER STATUS: Menggunakan utility classes secara langsung --}}
            <div class="p-4 border-b border-gray-200 flex flex-wrap gap-2">
                @php
                    $statusFilters = [
                        'pending' => 'Menunggu Bayar',
                        'menunggu-pickup' => 'Menunggu Pickup',
                        'processing' => 'Diproses', // PERBAIKAN: Mengganti key 'diproses' menjadi 'processing' agar sesuai DB
                        'shipment' => 'Dikirim', 
                        'selesai' => 'Selesai',
                        'batal' => 'Batal',
                    ];
                @endphp
                
                {{-- Tombol "Semua" --}}
                <a href="{{ route('admin.orders.index', request()->except('status','page')) }}"
                   class="px-3 py-1 text-sm font-medium rounded-full transition duration-150 ease-in-out focus:outline-none whitespace-nowrap
                          {{ !request('status') ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">
                    Semua
                </a>
                
                {{-- Tombol filter lainnya --}}
                @foreach($statusFilters as $key => $label)
                    <a href="{{ route('admin.orders.index', array_merge(request()->query(), ['status' => $key, 'page' => 1])) }}"
                       class="px-3 py-1 text-sm font-medium rounded-full transition duration-150 ease-in-out focus:outline-none whitespace-nowrap
                              {{ request('status') == $key ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>
    
            {{-- TABEL: Menggunakan styling tabel Tailwind --}}
            {{-- Wrapper untuk horizontal scroll pada tabel --}}
            <div class="overflow-x-auto">
                <table class="w-full min-w-max text-sm text-left text-gray-700">
                    <thead class="text-xs text-gray-600 uppercase bg-gray-50 border-b border-gray-200">
                        <tr>
                            {{-- Styling TH dengan sticky top --}}
                            <th scope="col" class="sticky top-0 z-10 bg-gray-50 px-4 py-3 font-medium tracking-wider">No</th>
                            <th scope="col" class="sticky top-0 z-10 bg-gray-50 px-4 py-3 font-medium tracking-wider">ID</th>
                            <th scope="col" class="sticky top-0 z-10 bg-gray-50 px-4 py-3 font-medium tracking-wider">Tipe</th>
                            <th scope="col" class="sticky top-0 z-10 bg-gray-50 px-4 py-3 font-medium tracking-wider">Transaksi</th>
                            <th scope="col" class="sticky top-0 z-10 bg-gray-50 px-4 py-3 font-medium tracking-wider">Alamat</th>
                            <th scope="col" class="sticky top-0 z-10 bg-gray-50 px-4 py-3 font-medium tracking-wider">Ekspedisi</th>
                            <th scope="col" class="sticky top-0 z-10 bg-gray-50 px-4 py-3 font-medium tracking-wider">Resi</th>
                            <th scope="col" class="sticky top-0 z-10 bg-gray-50 px-4 py-3 font-medium tracking-wider">Paket</th>
                            <th scope="col" class="sticky top-0 z-10 bg-gray-50 px-4 py-3 font-medium tracking-wider">Tanggal</th>
                            <th scope="col" class="sticky top-0 z-10 bg-gray-50 px-4 py-3 font-medium tracking-wider">Status</th>
                            {{-- Kolom Aksi Sticky Kanan --}}
                            <th scope="col" class="sticky top-0 right-0 z-20 bg-gray-100 px-4 py-3 font-medium tracking-wider text-right shadow-sm min-w-[160px]">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($orders as $index => $order)
                            @php
                                // STANDARISASI: Tentukan sumber data
                                $isPesanan = isset($order->status_pesanan);
                                
                                if ($isPesanan) {
                                    // == DATA DARI 'Pesanan' ==
                                    $invoice = $order->nomor_invoice;
                                    $resi = $order->resi;
                                    $status = $order->status_pesanan;
                                    
                                    // Mapping status 'Pesanan' ke status helper
                                    if ($status == 'Menunggu Pickup') $status = 'menunggu-pickup';
                                    if ($status == 'Sedang Dikirim') $status = 'Dikirim';
                                    if ($status == 'Selesai') $status = 'selesai';
                                    if ($status == 'Batal') $status = 'batal';

                                    $canCancel = in_array($status, ['menunggu-pickup']); // Sesuaikan logika 'Pesanan'
                                } else {
                                    // == DATA DARI 'Order' ==
                                    $invoice = $order->invoice_number;
                                    $resi = $order->shipping_reference ?? $order->tracking_number ?? $order->resi ?? null;
                                    $status = $order->status;
                                    
                                    // Mapping status 'Order' ke status helper
                                    if ($status == 'shipment') $status = 'Dikirim';
                                    if ($status == 'processing') $status = 'diproses';

                                    $canCancel = in_array($order->status, ['pending','paid','processing', 'menunggu-pickup']);
                                }
                            @endphp
                            {{-- Tambahkan 'group' untuk hover pada sticky column --}}
                            <tr class="group hover:bg-gray-50 transition duration-150">
                                <td class="px-4 py-3 align-top whitespace-nowrap">{{ $orders->firstItem() + $index }}</td>
                                <td class="px-4 py-3 align-top whitespace-nowrap font-mono text-xs">{{ $isPesanan ? $order->id_pesanan : $order->id }}</td>
                                
                                {{-- KOLOM TIPE BARU --}}
                                <td class="px-4 py-3 align-top whitespace-nowrap">
                                    @if($isPesanan)
                                        <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-700">Pesanan</span>
                                    @else
                                        <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-700">Order</span>
                                    @endif
                                </td>

                                {{-- Kolom Transaksi --}}
                                <td class="px-4 py-3 align-top whitespace-nowrap min-w-[220px]">
                                    @if($isPesanan)
                                        {{-- Tampilan 'Pesanan' --}}
                                        <div><b class="text-gray-900">{{ strtoupper($order->payment_method ?? '-') }}</b></div>
                                        <div class="font-mono">{{ $invoice }}</div>
                                        <div class="mt-1 text-xs space-y-0.5">
                                            <div class="flex justify-between gap-4 font-bold text-indigo-600 pt-0.5 border-t border-gray-200">
                                                <span class="text-indigo-600">Total:</span>
                                                <span>Rp{{ number_format($order->price, 0, ',', '.') }}</span>
                                            </div>
                                        </div>
                                    @else
                                        {{-- Tampilan 'Order' (Existing) --}}
                                        <div><b class="text-gray-900">{{ strtoupper($order->payment_method ?? '-') }}</b></div>
                                        <div class="font-mono">{{ $invoice }}</div>
                                        <div class="mt-1 text-xs space-y-0.5">
                                            <div class="flex justify-between gap-4">
                                                <span class="text-gray-500">Subtotal:</span>
                                                <span class="font-medium text-gray-700">Rp{{ number_format($order->subtotal, 0, ',', '.') }}</span>
                                            </div>
                                            <div class="flex justify-between gap-4">
                                                <span class="text-gray-500">Ongkir:</span>
                                                <span class="font-medium text-gray-700">Rp{{ number_format($order->shipping_cost, 0, ',', '.') }}</span>
                                            </div>
                                            @if($order->cod_fee > 0)
                                            <div class="flex justify-between gap-4">
                                                <span class="text-gray-500">Biaya COD:</span>
                                                <span class="font-medium text-gray-700">Rp{{ number_format($order->cod_fee, 0, ',', '.') }}</span>
                                            </div>
                                            @endif
                                            <div class="flex justify-between gap-4 font-bold text-indigo-600 pt-0.5 border-t border-gray-200">
                                                <span class="text-indigo-600">Total:</span>
                                                <span>Rp{{ number_format($order->total_amount, 0, ',', '.') }}</span>
                                            </div>
                                        </div>
                                    @endif
                                </td>
                                
                                {{-- Kolom Alamat --}}
                                <td class="px-4 py-3 align-top min-w-[250px]">
                                    @if($isPesanan)
                                        {{-- Tampilan 'Pesanan' --}}
                                        <div class="mb-1">
                                            <span class="text-xs text-gray-500">Dari:</span>
                                            <strong class="text-blue-700 block">{{ $order->sender_name ?? '-' }}</strong>
                                            <small class="text-gray-600">{{ $order->sender_address ?? '' }}</small>
                                        </div>
                                        <div>
                                            <span class="text-xs text-gray-500">Kepada:</span>
                                            <strong class="text-red-700 block">{{ $order->receiver_name ?? '-' }}</strong>
                                            <small class="text-gray-600">{{ $order->receiver_address ?? '-' }}</small>
                                        </div>
                                    @else
                                        {{-- Tampilan 'Order' (Existing) --}}
                                        <div class="mb-1">
                                            <span class="text-xs text-gray-500">Dari:</span>
                                            <strong class="text-blue-700 block">{{ $order->store->name ?? '-' }}</strong>
                                            <small class="text-gray-600">{{ $order->store->address_detail ?? '' }}</small>
                                        </div>
                                        <div>
                                            <span class="text-xs text-gray-500">Kepada:</span>
                                            <strong class="text-red-700 block">{{ $order->user->nama_lengkap ?? '-' }}</strong>
                                            <small class="text-gray-600">{{ $order->shipping_address ?? '-' }}</small>
                                        </div>
                                    @endif
                                </td>

                                {{-- Kolom Ekspedisi --}}
                                <td class="px-4 py-3 align-top whitespace-nowrap">
                                    @php 
                                        $shipping_method = $isPesanan ? $order->jasa_ekspedisi_aktual : $order->shipping_method;
                                        $ship = \App\Helpers\ShippingHelper::parseShippingMethod($shipping_method); 
                                    @endphp
                                    @if($ship['logo_url'])
                                        <img src="{{ $ship['logo_url'] }}" alt="{{ $ship['courier_name'] }}" class="h-5 mb-1 max-w-[90px]">
                                    @endif
                                    <div class="text-sm font-medium text-gray-900">{{ $ship['courier_name'] ?? '-' }}</div>
                                    <small class="text-gray-600">{{ $ship['service_name'] ?? '' }}</small>
                                </td>
                                
                                {{-- Kolom Resi (Sudah distandarisasi) --}}
                                <td class="px-4 py-3 align-top whitespace-nowrap font-mono text-gray-700">
                                    {{ $resi ?? '-' }}
                                </td>
                                
                                {{-- Kolom Paket --}}
                                <td class="px-4 py-3 align-top min-w-[200px]">
                                    @if($isPesanan)
                                        {{ $order->item_description ?? '-' }}
                                    @else
                                        @php $first = $order->items->first(); @endphp
                                        {{ $first->product->name ?? '-' }}
                                    @endif
                                </td>
                                
                                {{-- Kolom Tanggal --}}
                                <td class="px-4 py-3 align-top whitespace-nowrap text-xs">
                                    @if($isPesanan)
                                        {{-- Tampilan 'Pesanan' --}}
                                        <div>
                                            <span class="text-gray-500">Dibuat:</span>
                                            <span class="text-gray-800 block">{{ $order->tanggal_pesanan ? \Carbon\Carbon::parse($order->tanggal_pesanan)->format('d M Y, H:i') : '-' }}</span>
                                        </div>
                                        <div class="mt-1">
                                            <span class="text-gray-500">Dikirim:</span>
                                            <span class="text-gray-800 block">
                                                {{ $order->shipped_at ? \Carbon\Carbon::parse($order->shipped_at)->format('d M Y, H:i') : '-' }}
                                            </span>
                                        </div>
                                        <div class="mt-1">
                                            <span class="text-gray-500">Selesai:</span>
                                            <span class="text-gray-800 block">
                                                {{ $order->finished_at ? \Carbon\Carbon::parse($order->finished_at)->format('d M Y, H:i') : '-' }}
                                            </span>
                                        </div>
                                    @else
                                        {{-- Tampilan 'Order' (Existing) --}}
                                        <div>
                                            <span class="text-gray-500">Dibuat:</span>
                                            <span class="text-gray-800 block">{{ $order->created_at->format('d M Y, H:i') }}</span>
                                        </div>
                                        <div class="mt-1">
                                            <span class="text-gray-500">Dikirim:</span>
                                            <span class="text-gray-800 block">
                                                {{ $order->shipped_at ? \Carbon\Carbon::parse($order->shipped_at)->format('d M Y, H:i') : '-' }}
                                            </span>
                                        </div>
                                        <div class="mt-1">
                                            <span class="text-gray-500">Selesai:</span>
                                            <span class="text-gray-800 block">
                                                {{ $order->finished_at ? \Carbon\Carbon::parse($order->finished_at)->format('d M Y, H:i') : '-' }}
                                            </span>
                                        </div>
                                    @endif
                                </td>

                                {{-- Kolom Status (Sudah distandarisasi) --}}
                                <td class="px-4 py-3 align-top whitespace-nowrap">
                                    @php
                                        $text = \App\Helpers\OrderStatusHelper::getStatusText($status);
                                        $badge = \App\Helpers\OrderStatusHelper::getStatusBadgeClass($status);
                                    @endphp
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $badge }}">
                                        {{ $text }}
                                    </span>
                                </td>
                                
                                {{-- Kolom Aksi Sticky Kanan (Invoice & Resi sudah distandarisasi) --}}
                                <td class="sticky right-0 z-10 bg-white px-4 py-3 text-right align-top transition duration-150 group-hover:bg-gray-50 shadow-sm">
                                    <div class="flex items-center justify-end gap-1">
                                        @php
                                            $actionBtnClass = "inline-flex items-center justify-center w-8 h-8 rounded-md text-gray-500 transition duration-150";
                                            $actionBtnHover = "hover:bg-gray-100";
                                            $actionBtnDisabled = "disabled:opacity-40 disabled:cursor-not-allowed";
                                        @endphp
                                        
                                        <a href="{{ $resi ? 'https://tokosancaka.com/tracking/search?resi=' . e($resi) : '#' }}" target="_blank" 
                                           class="{{ $actionBtnClass }} {{ $resi ? $actionBtnHover.' hover:text-green-600' : $actionBtnDisabled }}" 
                                           title="Lacak" @disabled(!$resi)>
                                            <i class="fas fa-truck fa-fw"></i>
                                        </a>

                                        {{-- Tombol aksi mungkin perlu logika @if juga, tapi kita standarisasi yg utama --}}
                                        @if(!$isPesanan)
                                            <a href="{{ route('admin.orders.show', $invoice) }}" title="Detail" 
                                            class="{{ $actionBtnClass }} {{ $actionBtnHover }} hover:text-indigo-600">
                                                <i class="fas fa-eye fa-fw"></i>
                                            </a>
                                            <a href="{{ route('admin.orders.print.thermal', $invoice) }}" target="_blank" 
                                            class="{{ $actionBtnClass }} {{ $actionBtnHover }} hover:text-gray-700" title="Print">
                                                <i class="fas fa-print fa-fw"></i>
                                            </a>
                                            <a href="{{ route('admin.orders.invoice.pdf', $invoice) }}" target="_blank" 
                                            class="{{ $actionBtnClass }} {{ $actionBtnHover }} hover:text-red-600" title="PDF">
                                                <i class="fas fa-file-pdf fa-fw"></i>
                                            </a>
                                            <a href="{{ route('admin.chat.start', ['id_pengguna' => $order->user_id]) }}" target="_blank" 
                                            class="{{ $actionBtnClass }} {{ $actionBtnHover }} hover:text-blue-600" title="Chat">
                                                <i class="fas fa-comment fa-fw"></i>
                                            </a>
                                            <form action="{{ route('admin.orders.cancel', $invoice) }}" method="POST" onsubmit="return confirm('Batalkan pesanan ini?')">
                                                @csrf @method('PATCH')
                                                <button type="submit" 
                                                        class="{{ $actionBtnClass }} {{ $canCancel ? $actionBtnHover.' hover:text-red-700' : $actionBtnDisabled }}" 
                                                        title="Batalkan" @disabled(!$canCancel)>
                                                    <i class="fas fa-trash fa-fw"></i>
                                                </button>
                                            </form>
                                        @else
                                            {{-- Tombol Aksi untuk 'Pesanan' (Sesuaikan rutenya jika perlu) --}}
                                            <a href="#" title="Detail Pesanan" 
                                            class="{{ $actionBtnClass }} {{ $actionBtnHover }} hover:text-indigo-600">
                                                <i class="fas fa-eye fa-fw"></i>
                                            </a>
                                            <a href="#" target="_blank" 
                                            class="{{ $actionBtnClass }} {{ $actionBtnHover }} hover:text-gray-700" title="Print">
                                                <i class="fas fa-print fa-fw"></i>
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                {{-- Memperbarui colspan menjadi 11 --}}
                                <td colspan="11" class="text-center py-10 text-gray-500">
                                    <i class="fas fa-box-open text-4xl text-gray-400 mb-2"></i><br>
                                    Tidak ada pesanan ditemukan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
    
            {{-- PAGINATION --}}
            <div class="p-4 border-t border-gray-200">
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
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<script>
$(function(){
    // Inisialisasi Toastr
    toastr.options = {
        closeButton: true,
        progressBar: true,
        timeOut: 3000,
        positionClass: "toast-top-right"
    };

    // Tambahkan notifikasi jika ada
    @if(session('success'))
        toastr.success("{{ session('success') }}");
    @endif
    @if(session('error'))
        toastr.error("{{ session('error') }}");
    @endif
});
</script>
@endpush

