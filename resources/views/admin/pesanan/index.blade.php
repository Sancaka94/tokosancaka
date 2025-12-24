{{--
    File: resources/views/admin/pesanan/index.blade.php
    Deskripsi: Halaman Admin untuk manajemen semua pesanan.
--}}

@extends('layouts.admin')

@section('title', 'Data Pesanan')
@section('page-title', 'Data Pesanan')

@push('styles')
<style>
    .table-container {
        overflow-x: auto;
    }
    th.sticky-col, td.sticky-col {
        position: -webkit-sticky;
        position: sticky;
        right: 0;
        background-color: white;
        z-index: 10;
        border-left: 1px solid #e5e7eb; /* Garis pemisah */
    }
    thead th.sticky-col {
        background-color: #fce7f3; /* Sesuaikan dengan bg-red-100 header (kira-kira pink/red muda) */
        z-index: 20; /* Header harus lebih tinggi dari body */
    }
    /* Fix untuk background saat hover di baris tabel */
    tr:hover td.sticky-col {
        background-color: #f9fafb; /* bg-gray-50 */
    }
</style>
@endpush

@section('content')
<div class="bg-white p-4 sm:p-6 rounded-lg shadow-md">

    {{-- HEADER & SEARCH --}}
    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
        <div class="w-full md:w-1/3">
            <form action="{{ route('admin.pesanan.index') }}" method="GET" class="relative">
                {{-- Simpan filter status saat mencari --}}
                @if(request('status'))
                    <input type="hidden" name="status" value="{{ request('status') }}">
                @endif
                
                <input type="text" name="search" class="w-full pl-10 pr-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="Cari Resi, Nama, atau No. HP..." value="{{ request('search') }}">
                <div class="absolute top-0 left-0 inline-flex items-center p-2 h-full text-gray-400">
                    <i class="fas fa-search"></i>
                </div>
            </form>
        </div>
        <div class="flex items-center gap-2 w-full md:w-auto justify-end">
            <button type="button" onclick="openModal('exportModal')" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-300">
                <i class="fas fa-file-export me-2"></i>Export
            </button>
            <a href="{{ route('admin.pesanan.create') }}" class="bg-red-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-red-700">
                <i class="fas fa-plus me-2"></i>Tambah Pesanan
            </a>
        </div>
    </div>

    {{-- TAB STATUS --}}
    <div class="flex flex-wrap gap-2 mb-4 border-b pb-4">
        @php
            // 1. Route Admin
            $routeIndex = 'admin.pesanan.index';

            // 2. Definisi Status (Sesuai Database)
            $statuses = [
                'Menunggu Pickup' => 'Menunggu Pickup',
                'Diproses'        => 'Diproses',
                'Terkirim'        => 'Terkirim',
                'Selesai'         => 'Selesai',
                'Batal'           => 'Batal',
                'Gagal Resi'      => 'Pembayaran Lunas (Gagal Auto-Resi)' 
            ];

            // 3. Status Aktif
            $currentStatus = request('status');
            
            // 4. Base Query (Simpan search/page saat pindah tab)
            $baseQuery = request()->except(['status', 'page']);
        @endphp

        {{-- TOMBOL SEMUA --}}
        <a href="{{ route($routeIndex, $baseQuery) }}" 
           class="px-4 py-2 text-xs font-bold rounded-full border transition 
                  {{ !$currentStatus ? 'bg-green-600 text-white border-green-600' : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-50' }}">
            Semua
        </a>

        {{-- LOOP STATUS --}}
        @foreach($statuses as $label => $value)
            <a href="{{ route($routeIndex, array_merge($baseQuery, ['status' => $value, 'page' => 1])) }}" 
               class="px-4 py-2 text-xs font-bold rounded-full border transition 
                      {{ $currentStatus == $value ? 'bg-green-600 text-white border-green-600' : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-50' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>

    @include('layouts.partials.notifications')
    
   {{-- === MULAI CARD MONITOR PENDAPATAN (FULL RESPONSIVE) === --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 md:gap-4 mb-6">
    
    {{-- CARD 1: SELESAI --}}
    <div class="relative overflow-hidden rounded-xl bg-green-500 p-3 md:p-5 shadow-md">
        <div class="relative z-10 text-white">
            <p class="text-base md:text-2xl lg:text-3xl font-bold truncate">
                Rp{{ number_format($incomeSelesai, 0, ',', '.') }}
            </p>
            <p class="text-[10px] md:text-sm font-bold uppercase opacity-90 mt-1 leading-tight">Pendapatan Selesai</p>
            <p class="hidden md:block text-xs opacity-75 mt-0.5">Total pesanan sukses</p>
        </div>
        {{-- Ikon Background Responsif --}}
        <div class="absolute right-0 top-0 -mt-2 -mr-2 md:-mr-4 h-16 w-16 md:h-24 md:w-24 opacity-20 transform rotate-12 transition-transform">
            <i class="fas fa-store fa-3x md:fa-5x text-white"></i>
        </div>
    </div>

    {{-- CARD 2: MENUNGGU PICKUP --}}
    <div class="relative overflow-hidden rounded-xl bg-cyan-600 p-3 md:p-5 shadow-md">
        <div class="relative z-10 text-white">
            <p class="text-base md:text-2xl lg:text-3xl font-bold truncate">
                Rp{{ number_format($incomePickup, 0, ',', '.') }}
            </p>
            <p class="text-[10px] md:text-sm font-bold uppercase opacity-90 mt-1 leading-tight">Menunggu Pickup</p>
            <p class="hidden md:block text-xs opacity-75 mt-0.5">Sudah lunas, belum kirim</p>
        </div>
        <div class="absolute right-0 top-0 -mt-2 -mr-2 md:-mr-4 h-16 w-16 md:h-24 md:w-24 opacity-20 transform rotate-12">
            <i class="fas fa-box-open fa-3x md:fa-5x text-white"></i>
        </div>
    </div>

    {{-- CARD 3: SEDANG DIKIRIM --}}
    <div class="relative overflow-hidden rounded-xl bg-blue-600 p-3 md:p-5 shadow-md">
        <div class="relative z-10 text-white">
            <p class="text-base md:text-2xl lg:text-3xl font-bold truncate">
                Rp{{ number_format($incomeDikirim, 0, ',', '.') }}
            </p>
            <p class="text-[10px] md:text-sm font-bold uppercase opacity-90 mt-1 leading-tight">Sedang Dikirim</p>
            <p class="hidden md:block text-xs opacity-75 mt-0.5">Dalam perjalanan</p>
        </div>
        <div class="absolute right-0 top-0 -mt-2 -mr-2 md:-mr-4 h-16 w-16 md:h-24 md:w-24 opacity-20 transform rotate-12">
            <i class="fas fa-shipping-fast fa-3x md:fa-5x text-white"></i>
        </div>
    </div>

    {{-- CARD 4: GAGAL / BATAL --}}
    <div class="relative overflow-hidden rounded-xl bg-red-500 p-3 md:p-5 shadow-md">
        <div class="relative z-10 text-white">
            <p class="text-base md:text-2xl lg:text-3xl font-bold truncate">
                Rp{{ number_format($incomeGagal, 0, ',', '.') }}
            </p>
            <p class="text-[10px] md:text-sm font-bold uppercase opacity-90 mt-1 leading-tight">Gagal / Batal</p>
            <p class="hidden md:block text-xs opacity-75 mt-0.5">Potensi hilang</p>
        </div>
        <div class="absolute right-0 top-0 -mt-2 -mr-2 md:-mr-4 h-16 w-16 md:h-24 md:w-24 opacity-20 transform rotate-12">
            <i class="fas fa-arrow-up fa-3x md:fa-5x text-white"></i>
        </div>
    </div>

</div>
{{-- === SELESAI CARD MONITOR === --}}

    {{-- TABEL DATA --}}
    <div class="table-container">
        <table class="table min-w-full divide-y divide-gray-200">
          <thead class="bg-red-100">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">No</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider"><strong>Transaksi</strong></th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider"><strong>Alamat</strong></th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider"><strong>Ekspedisi & Ongkir</strong></th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider"><strong>Isi Paket</strong></th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider"><strong>Status</strong></th>
                <th class="px-4 py-3 text-center text-xs font-medium text-gray-600 uppercase tracking-wider sticky-col"><strong>Aksi</strong></th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            @forelse ($orders as $index => $order)
            <tr class="group hover:bg-gray-50">
                {{-- No --}}
                <td class="px-4 py-4 align-top text-sm text-gray-500">{{ $orders->firstItem() + $index }}</td>

                {{-- Transaksi --}}
                <td class="px-4 py-4 align-top text-sm">
                    @if(Str::contains($order->payment_method, 'COD'))
                        <span class="font-bold text-green-600">COD</span><br>
                    @else
                        <span class="font-bold text-blue-600">{{$order->payment_method}}</span><br>
                    @endif

@php
    $resiValue = $order->resi ?? null;
    $isResiReady = !empty($resiValue) && strtolower($resiValue) !== 'menunggu resi';
@endphp

@if($isResiReady)
    <div class="bg-red-200 border border-red-500 text-gray-800 font-bold mt-1 p-2 rounded 
                flex items-center justify-between">
        
        <span>
            RESI: <span id="resiNumber">{{ $resiValue }}</span>
        </span>

        <button 
            onclick="copyResiNumber()" 
            class="text-gray-700 hover:text-gray-900 ml-2"
            title="Copy">
            <i class="fas fa-copy"></i>
        </button>
    </div>
@else
    <div class="font-bold text-red-800 mt-1">
        RESI: Menunggu Resi
    </div>
@endif
                    <div class="text-xs text-gray-500">Invoice: <strong>{{ $order->nomor_invoice }}</strong></div>
                    <div class="text-xs text-gray-500 mt-1">{{ \Carbon\Carbon::parse($order->tanggal_pesanan)->format('d M Y, H:i') }}</div>
                </td>

                {{-- Alamat --}}
                <td class="px-4 py-4 align-top text-sm">
                    <div class="mb-2">
                        <div class="text-xs text-gray-500">Dari:</div>
                        <div class="font-semibold text-blue-700 space-y-1">
    <div class="flex items-center gap-2">
        <i class="fas fa-user"></i>
        {{ $order->sender_name }}
    </div>

    <div class="flex items-center gap-2">
        <i class="fas fa-phone-alt"></i>
        {{ $order->sender_phone }}
    </div>
                        </div>

                        <div class="text-xs text-gray-600 leading-tight flex items-start gap-2">
    
    <i class="fas fa-map-marker-alt mt-1 text-red-500"></i>

    <div>
        {{ $order->sender_address }}<br>
        {{ $order->sender_village }}, {{ $order->sender_district }}<br>
        {{ $order->sender_regency }}, {{ $order->sender_province }}<br>
        <span class="font-semibold">Kode Pos: {{ $order->sender_postal_code }}</span>
    </div>

</div>

                    </div>
                    <div>
    <div class="text-xs text-gray-500 flex items-center gap-1">
        <i class="fas fa-arrow-right text-gray-400"></i> Ke:
    </div>

    <div class="font-semibold text-red-700 space-y-1 mt-1">
        <div class="flex items-center gap-2">
            <i class="fas fa-user"></i>
            {{ $order->receiver_name ?? $order->nama_pembeli }}
        </div>
        <div class="flex items-center gap-2">
            <i class="fas fa-phone-alt"></i>
            {{ $order->receiver_phone }}
        </div>
    </div>

    <div class="text-xs text-gray-600 leading-tight flex items-start gap-2 mt-1">
        <i class="fas fa-map-marker-alt mt-1 text-red-500"></i>
        <div>
            {{ $order->receiver_address }}<br>
            {{ $order->receiver_village }}, {{ $order->receiver_district }}<br>
            {{ $order->receiver_regency }}, {{ $order->receiver_province }}<br>
            <span class="font-semibold">Kode Pos: {{ $order->receiver_postal_code }}</span>
        </div>
    </div>
</div>

                </td>

                {{-- Ekspedisi --}}
                <td class="px-4 py-4 align-top text-sm">
                    @php
                        $ship = \App\Helpers\ShippingHelper::parseShippingMethod($order->expedition);
                        $courierName = $ship['courier_name'] ?? 'N/A';
                        $serviceName = $ship['service_name'] ?? 'N/A';
                        $logoUrl = $ship['logo_url'] ?? null;
                    @endphp

                    @if($logoUrl)
                        <img src="{{ $logoUrl }}" alt="{{ $courierName }}" class="h-6 mb-1 object-contain">
                    @else
                        <div class="font-bold text-gray-800">{{ $courierName }}</div>
                    @endif

                    <div class="text-xs text-gray-500">{{ $serviceName }}</div>
                    <div class="font-semibold text-green-700 mt-1">
                        Rp{{ number_format($order->shipping_cost, 0, ',', '.') }}
                    </div>
                </td>

                {{-- Isi Paket --}}
                <td class="px-4 py-4 align-top text-sm">
                    <div class="font-semibold text-gray-800">Isi Paket: {{ $order->item_description }}</div>
                    <div class="text-xs text-gray-500 mt-1">Dimensi: {{ $order->length ?? '0' }} x {{ $order->width ?? '0' }} x {{ $order->height ?? '0' }}</div>
                    <div class="text-xs text-gray-500 mt-1">Berat: {{ $order->weight }}gr</div>
                    Nilai Barang: Rp {{ number_format($order->total_harga_barang ?? $order->item_price ?? 0, 0, ',', '.') }}
                    
                </td>

                {{-- Status --}}
                <td class="px-4 py-4 align-top text-sm">
                    @php
                        $statusText = $order->status_pesanan;
                        $bgClass = match($statusText) {
                            'Terkirim', 'Selesai', 'Sedang Dikirim' => 'bg-green-100 text-green-800',
                            'Diproses' => 'bg-blue-100 text-blue-800',
                            'Menunggu Pickup' => 'bg-yellow-100 text-yellow-800',
                            'Batal', 'Gagal Bayar', 'Kadaluarsa' => 'bg-red-100 text-red-800',
                            default => 'bg-gray-100 text-gray-800',
                        };
                        // Persingkat status panjang
                        if (Str::contains($statusText, 'Gagal Auto-Resi')) $statusText = 'Gagal Resi';
                    @endphp
                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full {{ $bgClass }}">
                        {{ $statusText }}
                    </span>
                </td>

                {{-- Aksi --}}
                <td class="px-6 py-4 align-middle whitespace-nowrap text-sm font-medium sticky-col">
                    <div class="flex items-center justify-center space-x-3">

                        {{-- 1. Detail (Selalu Ada) --}}
                        <a href="{{ route('admin.pesanan.show', ['resi' => $order->resi ?? $order->nomor_invoice]) }}" class="text-gray-500 hover:text-indigo-600" title="Detail">
                            <i class="fas fa-eye fa-lg"></i>
                        </a>

                        {{-- 2. Edit (Selalu Ada - Untuk Input Resi Manual/Edit Data) --}}
                        <a href="{{ route('admin.pesanan.edit', ['resi' => $order->resi ?? $order->nomor_invoice]) }}" class="text-gray-500 hover:text-blue-600" title="Edit">
                            <i class="fas fa-pencil-alt fa-lg"></i>
                        </a>

                        {{-- 3. Cetak & Lacak (Hanya jika ada Resi) --}}
                        @if($order->resi)
                            <a href="{{ route('admin.pesanan.cetak_thermal', ['resi' => $order->resi]) }}" target="_blank" class="text-gray-500 hover:text-gray-800" title="Cetak Label">
                                <i class="fas fa-print fa-lg"></i>
                            </a>
                            <a href="https://tokosancaka.com/tracking/search?resi={{ $order->resi }}" target="_blank" class="text-gray-500 hover:text-green-600" title="Lacak Resi">
                                <i class="fas fa-truck fa-lg"></i>
                            </a>
                        @endif

                        {{-- 4. Hapus (Selalu Ada) --}}
                        <form action="{{ route('admin.pesanan.destroy', ['resi' => $order->resi ?? $order->nomor_invoice]) }}" method="POST" onsubmit="return confirm('Yakin hapus pesanan ini?');" class="inline-block">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-gray-500 hover:text-red-600" title="Hapus">
                                <i class="fas fa-trash-alt fa-lg"></i>
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="text-center py-8 text-gray-500">
                    <i class="fas fa-box-open text-4xl mb-3 text-gray-300"></i><br>
                    Data pesanan tidak ditemukan.
                </td>
            </tr>
            @endforelse
        </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if ($orders->hasPages())
    <div class="mt-4 p-4 border-t border-gray-200">
        {{ $orders->appends(request()->query())->links() }}
    </div>
    @endif
</div>

@include('layouts.partials.modals.export', ['excel_route' => route('admin.pesanan.export.excel'), 'pdf_route' => route('admin.pesanan.export.pdf')])

@endsection

@push('scripts')
<script>
    function openModal(id) { document.getElementById(id).classList.remove('hidden'); }
    function closeModal(id) { document.getElementById(id).classList.add('hidden'); }
    
     function copyResiNumber() {
        const text = document.getElementById('resiNumber').innerText;
        navigator.clipboard.writeText(text).then(() => {
            alert('Nomor resi berhasil disalin!');
        });
    }
</script>
@endpush