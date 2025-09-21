{{--
File: resources/views/customer/pesanan/index.blade.php
Deskripsi: Halaman daftar pesanan untuk pelanggan dengan tampilan rinci.
--}}

@extends('layouts.customer')

@section('title', 'Riwayat Pesanan')

@section('content')

<div class="bg-slate-50 min-h-screen">
<div class="container mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">

<!-- Header Halaman -->

<div class="mb-8">
<h1 class="text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">Riwayat Pesanan</h1>
<p class="mt-2 text-lg text-slate-600">Berikut adalah riwayat semua pesanan yang telah Anda buat, lengkap dengan detailnya.</p>
</div>

<!-- Konten Utama: Daftar Pesanan -->

<div class="space-y-4">

<!-- Header untuk Tampilan Desktop -->
<div class="hidden md:grid grid-cols-12 gap-4 px-4 py-2 bg-purple-100 text-purple-800 rounded-lg text-xs font-bold uppercase tracking-wider">
    <div class="col-span-1 text-center">No</div>
    <div class="col-span-2">Transaksi</div>
    <div class="col-span-3">Alamat</div>
    <div class="col-span-2">Ekspedisi & Ongkir</div>
    <div class="col-span-2">Isi Paket</div>
    <div class="col-span-2 text-center">Status</div>
</div>

@forelse ($pesanans as $order)
    <div class="bg-white rounded-xl shadow-md overflow-hidden border border-slate-200">
        <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-start">

            <!-- Kolom 1: No -->
            <div class="hidden md:flex col-span-1 h-full items-center justify-center font-bold text-slate-700 bg-slate-50 border-r border-slate-200">
               {{ $loop->iteration + ($pesanans->currentPage() - 1) * $pesanans->perPage() }}
            </div>

            <!-- Kolom 2: Transaksi -->
            <div class="md:col-span-2 p-4">
                <div class="md:hidden text-xs font-semibold uppercase tracking-wider text-slate-500 mb-2">Transaksi</div>
                <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium bg-orange-100 text-orange-800">{{ $order->payment_method }}</span>
                <div class="mt-2 font-semibold text-sm text-indigo-600">{{ $order->nomor_invoice }}</div>
                <div class="text-xs text-slate-500">{{ \Carbon\Carbon::parse($order->tanggal_pesanan)->format('d M Y H:i') }}</div>
                <div class="text-xs text-slate-500">Dibuat oleh: {{ Auth::user()->nama_lengkap }}</div>
            </div>

            <!-- Kolom 3: Alamat -->
            <div class="md:col-span-3 p-4 border-t md:border-t-0 md:border-l border-slate-200">
                <div class="md:hidden text-xs font-semibold uppercase tracking-wider text-slate-500 mb-2">Alamat</div>
                <div class="flex items-start mb-3">
                    <i class="fas fa-arrow-up-from-bracket text-red-500 mr-3 mt-1 flex-shrink-0"></i>
                    <div>
                        <div class="font-semibold text-slate-800 text-sm">{{ $order->sender_name }} ({{ $order->sender_phone }})</div>
                        <div class="text-xs text-slate-600">{{ $order->sender_address }}</div>
                    </div>
                </div>
                <div class="flex items-start">
                    <i class="fas fa-arrow-down-to-bracket text-green-500 mr-3 mt-1 flex-shrink-0"></i>
                    <div>
                        <div class="font-semibold text-slate-800 text-sm">{{ $order->nama_pembeli }} ({{ $order->telepon_pembeli }})</div>
                        <div class="text-xs text-slate-600">{{ $order->alamat_pengiriman }}</div>
                    </div>
                </div>
            </div>

            <!-- Kolom 4: Ekspedisi & Ongkir -->
            <div class="md:col-span-2 p-4 border-t md:border-t-0 md:border-l border-slate-200">
                 <div class="md:hidden text-xs font-semibold uppercase tracking-wider text-slate-500 mb-2">Ekspedisi & Ongkir</div>
                @php
                    $expeditionParts = explode('-', $order->expedition);
                    $expeditionName = $expeditionParts[1] ?? 'POSINDONESIA';
                    $expeditionService = $expeditionParts[2] ?? 'Regular';
                    $logoPath = strtolower(str_replace(' ', '', $expeditionName));
                @endphp
                <div class="flex items-center mb-1">
                    <img src="{{ asset('storage/logo-ekspedisi/' . $logoPath . '.png') }}" alt="{{ $expeditionName }} Logo" class="w-10 h-auto mr-2">
                    <span class="font-bold text-sm">{{ $expeditionName }}</span>
                </div>
                <div class="text-xs text-slate-600">{{ $expeditionService }}</div>
                <div class="text-sm text-slate-600 mt-2">Ongkir: <span class="font-bold text-red-600">Rp {{ number_format($order->price ?? 0, 0, ',', '.') }}</span></div>
                <div class="text-xs text-slate-500 break-all">Resi: {{ $order->resi ?? 'Menunggu' }}</div>
            </div>

            <!-- Kolom 5: Isi Paket -->
            <div class="md:col-span-2 p-4 border-t md:border-t-0 md:border-l border-slate-200">
                <div class="md:hidden text-xs font-semibold uppercase tracking-wider text-slate-500 mb-2">Isi Paket</div>
                <div class="text-sm text-slate-800 font-semibold">{{ $order->item_description }}</div>
                <div class="text-xs text-slate-600">Nilai: Rp {{ number_format($order->total_harga_barang ?? 0, 0, ',', '.') }}</div>
                <div class="text-xs text-slate-600">Berat: {{ $order->weight }} gram</div>
                <div class="text-xs text-slate-600">Dimensi: {{ $order->length ?? 0 }}x{{ $order->width ?? 0 }}x{{ $order->height ?? 0 }} cm</div>
            </div>

            <!-- Kolom 6: Status -->
            <div class="md:col-span-2 p-4 flex flex-col items-center justify-center border-t md:border-t-0 md:border-l border-slate-200">
                <div class="md:hidden text-xs font-semibold uppercase tracking-wider text-slate-500 mb-2">Status</div>
                @php
                    $status = $order->status_pesanan ?? 'Belum ada status';
                    $statusClass = 'bg-slate-100 text-slate-800';
                    switch ($status) {
                        case 'Menunggu Pembayaran': $statusClass = 'bg-yellow-100 text-yellow-800'; break;
                        case 'Menunggu Pickup': case 'Sedang Dikirim': $statusClass = 'bg-blue-100 text-blue-800'; break;
                        case 'Dalam Proses Retur': $statusClass = 'bg-orange-100 text-orange-800'; break;
                        case 'Selesai': case 'Retur Selesai': $statusClass = 'bg-green-100 text-green-800'; break;
                        case 'Dibatalkan': $statusClass = 'bg-red-100 text-red-800'; break;
                    }
                @endphp
                    <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold leading-5 {{ $statusClass }}">
                        {{ $status }}
                    </span>
                    @if ($order->status_pesanan === 'Menunggu Pembayaran' && $order->payment_url)
                        <a href="{{ $order->payment_url }}" target="_blank" class="mt-2 text-indigo-600 hover:text-indigo-900 font-semibold text-sm">Bayar Sekarang</a>
                    @elseif ($order->resi)
                        <a href="{{ route('customer.lacak.index', ['resi' => $order->resi]) }}"
                        class="mt-2 text-indigo-600 hover:text-indigo-900 font-semibold text-sm">
                        Lacak Paket
                        </a>
                    @endif
                </div>
            </div>
        </div>
    @empty
        {{-- Tampilan jika tidak ada pesanan sama sekali --}}
        <div class="text-center py-16 bg-white rounded-xl shadow-md border border-slate-200">
            <div class="mx-auto max-w-md">
                <i class="fas fa-box-open fa-4x text-slate-300"></i>
                <h3 class="mt-4 text-lg font-medium text-slate-900">Tidak ada data pesanan</h3>
                <p class="mt-1 text-sm text-slate-500">Anda belum pernah membuat pesanan. Mulai kirim paket sekarang!</p>
                <div class="mt-6">
                    <a href="{{ route('customer.pesanan.create') }}" class="inline-flex items-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700">
                        <i class="fas fa-plus -ml-1 mr-2"></i>
                        Buat Pesanan Baru
                    </a>
                </div>
            </div>
        </div>
    @endforelse
</div>

{{-- Navigasi Paginasi --}}
@if ($pesanans->hasPages())
    <div class="mt-6">
        {{ $pesanans->links() }}
    </div>
@endif

</div>

</div>

@endsection