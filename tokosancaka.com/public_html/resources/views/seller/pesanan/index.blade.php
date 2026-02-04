@extends('layouts.customer')
@section('title', 'Laporan Pesanan Marketplace')

@push('styles')
<style>
    /* Style untuk ikon alamat (sama seperti dashboard) */
    .address-icon {
        width: 1.25rem; height: 1.25rem; flex-shrink: 0;
        display: flex; align-items: center; justify-content: center;
        border-radius: 50%; color: white;
    }
    .icon-send { background-color: #3B82F6; } /* blue-500 */
    .icon-receive { background-color: #8B5CF6; } /* violet-500 */
</style>
@endpush

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

        {{-- Header & Tombol Kembali --}}
        <div class="flex flex-col md:flex-row justify-between md:items-center gap-4 mb-6">
            <div>
                <h2 class="text-2xl font-semibold text-gray-800">
                    Laporan Pesanan Marketplace
                </h2>
                <p class="mt-1 text-gray-600">
                    Semua pesanan produk yang masuk melalui checkout.
                </p>
            </div>
            <div class="flex-shrink-0">
                 <a href="{{ route('seller.dashboard') }}" class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-500 active:bg-gray-700">
                    &larr; Kembali ke Dashboard
                </a>
            </div>
        </div>

        {{-- Card Utama --}}
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">

            {{-- Form Pencarian --}}
            <div class="p-6 border-b border-gray-200">
                <form action="{{ route('seller.pesanan.marketplace.index') }}" method="GET">
                    <div class="flex flex-col md:flex-row gap-2">
                        <input type="text" name="search"
                               class="block w-full md:w-1/3 rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                               placeholder="Cari No. Invoice atau Nama Pelanggan..."
                               value="{{ request('search') }}">
                        <button type="submit"
                                class="inline-flex justify-center items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 active:bg-gray-900">
                            Cari
                        </button>
                    </div>
                </form>
            </div>

            {{-- Header Tabel --}}
            <div class="hidden lg:grid grid-cols-12 gap-4 px-6 py-3 bg-purple-50 border-b border-purple-200 text-left text-xs font-medium text-purple-800 uppercase tracking-wider">
                <div class="col-span-1">No</div>
                <div class="col-span-2">Transaksi</div>
                <div class="col-span-3">Alamat</div>
                <div class="col-span-2">Ekspedisi & Ongkir</div>
                <div class="col-span-2">Isi Paket</div>
                <div class="col-span-2">Status</div>
            </div>

            {{-- Body Tabel (Loop) --}}
            <div class="bg-white divide-y divide-gray-200">
                @forelse ($orders as $order)
                    <div class="p-6">
                        <div class="grid grid-cols-1 lg:grid-cols-12 gap-x-4 gap-y-6 text-sm">

                            {{-- NO --}}
                            <div class="lg:col-span-1">
                                <span class="lg:hidden font-bold text-gray-500">NO: </span>
                                <span class="text-gray-900 font-medium">{{ $loop->iteration + $orders->firstItem() - 1 }}</span>
                            </div>

                            {{-- TRANSAKSI --}}
                            <div class="lg:col-span-2 space-y-1">
                                <div class="font-bold text-blue-600 uppercase">{{ $order->payment_method }}</div>
                                <div class="text-gray-900 font-medium">{{ $order->invoice_number }}</div>
                                <div class="text-xs text-gray-500">{{ $order->created_at->format('d M Y H:i') }}</div>
                                {{-- ====================================================== --}}
                                {{-- PERBAIKAN 1: Menggunakan nama toko dari order --}}
                                {{-- ====================================================== --}}
                                <div class="text-xs text-gray-500">Dibuat oleh: {{ $order->store->name ?? 'Toko' }}</div>
                            </div>

                            {{-- ALAMAT --}}
                            <div class="lg:col-span-3 space-y-3">
                                {{-- ====================================================== --}}
                                {{-- PERBAIKAN 2: Menggunakan alamat toko dari order --}}
                                {{-- ====================================================== --}}
                                {{-- Pengirim (Toko Anda) --}}
                                <div class="flex gap-3">
                                    <div class="address-icon icon-send">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path></svg>
                                    </div>
                                    <div>
                                        {{-- Cek jika relasi store dan store.user ada --}}
                                        @if($order->store)
                                            <div class="font-medium text-gray-900">{{ $order->store->name ?? 'Toko Pengirim' }} / {{ $order->store->user->no_wa ?? '' }}</div>
                                            <div class="text-xs text-gray-600">{{ $order->store->address_detail ?? 'Alamat Toko' }}</div>
                                            <div class="text-xs text-gray-500">{{ $order->store->village }}, {{ $order->store->district }}, {{ $order->store->regency }}</div>
                                        @else
                                            <div class="font-medium text-gray-900">Toko Tidak Ditemukan</div>
                                        @endif
                                    </div>
                                </div>
                                {{-- Penerima (Customer) --}}
                                <div class="flex gap-3">
                                    <div class="address-icon icon-receive">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path></svg>
                                    </div>
                                    <div>
                                        <div class="font-medium text-gray-900">{{ $order->user->nama_lengkap ?? 'Customer' }} / {{ $order->user->no_wa ?? '' }}</div>
                                        <div class="text-xs text-gray-600">{{ $order->shipping_address }}</div>
                                        <div class="text-xs text-gray-500">{{ $order->user->village ?? '' }}, {{ $order->user->district ?? '' }}, {{ $order->user->regency ?? '' }}</div>
                                    </div>
                                </div>
                            </div>

                            {{-- EKSPEDISI & ONGKIR --}}
                            <div class="lg:col-span-2 space-y-1">
                                {{-- ====================================================== --}}
                                {{-- PERBAIKAN 3: Parsing string shipping_method --}}
                                {{-- ====================================================== --}}
                                @php
                                    // Input: "regular-tiki-REG-7000-100"
                                    $shippingParts = explode('-', $order->shipping_method);
                                    $courier = $shippingParts[1] ?? 'N/A';
                                    $service = $shippingParts[2] ?? 'N/A';
                                @endphp
                                <div class="font-medium text-gray-800 uppercase">{{ $courier }} - {{ $service }}</div>
                                <div class="font-semibold text-gray-900">Rp{{ number_format($order->shipping_cost) }}</div>
                                @if($order->cod_fee > 0)
                                    @if(strtolower($order->payment_method) == 'cod')
                                    <div class="text-xs text-gray-600">Tagihan COD: Rp{{ number_format($order->total_amount) }}</div>
                                @elseif($order->cod_fee > 0)
                                    <div class="text-xs text-gray-600">Biaya COD: Rp{{ number_format($order->cod_fee) }}</div>
                                @endif
                                @endif
                                {{-- Kode Resi sudah benar, masalah ada di Controller Mbah --}}
                                <div class="text-xs text-gray-500 break-all">Resi: {{ $order->shipping_reference ?? '-' }}</div>
                                <div class="text-xs text-blue-600 font-medium">Pickup</div>
                            </div>

                            {{-- ISI PAKET --}}
                            <div class="lg:col-span-2 space-y-2">
                                @php
                                    $totalWeight = 0;
                                    $firstItem = $order->items->first();
                                    $dimension = 'Dimensi: -';
                                @endphp
                                @foreach($order->items as $item)
                                    @php
                                        $productWeight = $item->product->weight ?? 0;
                                        $totalWeight += $productWeight * $item->quantity;

                                        // ======================================================
                                        // PERBAIKAN 4: Ganti default dimensi ke 5
                                        // ======================================================
                                        if($loop->first && $item->product) {
                                            $dimension = "Dimensi: " . ($item->product->length ?? '5') . "x" . ($item->product->width ?? '5') . "x" . ($item->product->height ?? '5') . " cm";
                                        }
                                    @endphp
                                    <div class="text-xs">
                                        <div class="font-medium text-gray-800 uppercase">{{ $item->product->name ?? 'Produk Dihapus' }} ({{$item->quantity}}x)</div>
                                        <div class="text-gray-600">Rp{{ number_format($item->price) }}</div>
                                    </div>
                                @endforeach
                                <div class="text-xs text-gray-500 border-t pt-2 mt-2">
                                    <div>Berat: {{ number_format($totalWeight) }} gram</div>
                                    <div>{{ $dimension }}</div>
                                </div>
                            </div>

                            {{-- STATUS --}}
                            <div class="lg:col-span-2">
                                <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full {{ $order->status_badge_class }}">
                                    {{ Str::title($order->status) }}
                                </span>
                            </div>

                        </div>
                    </div>
                @empty
                    <div class="p-6 text-center text-gray-500">
                        @if(request('search'))
                            Pesanan dengan No. Invoice atau Pelanggan "{{ request('search') }}" tidak ditemukan.
                        @else
                            Belum ada pesanan produk yang masuk.
                        @endif
                    </div>
                @endforelse
            </div>

            {{-- Pagination --}}
            <div class="p-6 bg-gray-50 border-t">
                {{ $orders->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
