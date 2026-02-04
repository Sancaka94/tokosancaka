{{--
File: resources/views/customer/pesanan/index.blade.php
Deskripsi: Halaman riwayat pesanan dengan TABEL RESPONSIF + PENCARIAN + FILTER + EXPORT PDF.
--}}

@extends('layouts.customer')

@section('title', 'Riwayat Pesanan')

@section('content')
<div class="bg-slate-50 min-h-screen">
    <div class="container mx-auto max-w-[95%] px-4 py-8">

        {{-- HEADER & TOMBOL TAMBAH --}}
        <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">
                    Riwayat Pesanan
                </h1>
                <p class="mt-1 text-sm text-slate-600">
                    Kelola dan pantau semua transaksi pengiriman Anda di sini.
                </p>
            </div>

            {{-- Group Tombol Aksi --}}
            <div class="flex flex-col sm:flex-row gap-3">
                {{-- Tombol Kirim Paket Massal (BARU) --}}
                <a href="https://tokosancaka.com/customer/pesanan/multi/create" class="inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-bold rounded-lg shadow-sm text-white bg-green-600 hover:bg-green-700 transition">
                    <i class="fas fa-boxes mr-2"></i> Kirim Paket Massal
                </a>

                {{-- Tombol Pesanan Baru --}}
                <a href="{{ route('customer.pesanan.create') }}" class="inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-bold rounded-lg shadow-sm text-white bg-red-600 hover:bg-red-700 transition">
                    <i class="fas fa-plus mr-2"></i> Pesanan Baru
                </a>
            </div>
        </div>

        {{-- FILTER & PENCARIAN SECTION --}}
        <div class="bg-white p-4 rounded-xl shadow-sm border border-slate-200 mb-6">
            <form action="{{ route('customer.pesanan.index') }}" method="GET" class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">

                {{-- Input Pencarian --}}
                <div class="md:col-span-4">
                    <label for="search" class="block text-xs font-semibold text-slate-500 uppercase mb-1">Cari Data</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-search text-slate-400"></i>
                        </div>
                        <input type="text" name="search" id="search" value="{{ request('search') }}"
                               class="pl-10 block w-full rounded-lg border-slate-300 bg-slate-50 text-sm focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                               placeholder="Resi, Nama, No HP, atau Invoice...">
                    </div>
                </div>

                {{-- Filter Status --}}
                <div class="md:col-span-2">
                    <label for="status" class="block text-xs font-semibold text-slate-500 uppercase mb-1">Status</label>
                    <select name="status" id="status" class="block w-full rounded-lg border-slate-300 bg-slate-50 text-sm focus:border-indigo-500 focus:ring-indigo-500 shadow-sm">
                        <option value="">Semua Status</option>
                        <option value="Menunggu Pembayaran" {{ request('status') == 'Menunggu Pembayaran' ? 'selected' : '' }}>Menunggu Pembayaran</option>
                        <option value="Menunggu Pickup" {{ request('status') == 'Menunggu Pickup' ? 'selected' : '' }}>Menunggu Pickup</option>
                        <option value="Sedang Dikirim" {{ request('status') == 'Sedang Dikirim' ? 'selected' : '' }}>Sedang Dikirim</option>
                        <option value="Selesai" {{ request('status') == 'Selesai' ? 'selected' : '' }}>Selesai</option>
                        <option value="Dibatalkan" {{ request('status') == 'Dibatalkan' ? 'selected' : '' }}>Dibatalkan</option>
                    </select>
                </div>

                {{-- Filter Tanggal Mulai --}}
                <div class="md:col-span-2">
                    <label for="start_date" class="block text-xs font-semibold text-slate-500 uppercase mb-1">Dari Tanggal</label>
                    <input type="date" name="start_date" id="start_date" value="{{ request('start_date') }}"
                           class="block w-full rounded-lg border-slate-300 bg-slate-50 text-sm focus:border-indigo-500 focus:ring-indigo-500 shadow-sm">
                </div>

                {{-- Filter Tanggal Akhir --}}
                <div class="md:col-span-2">
                    <label for="end_date" class="block text-xs font-semibold text-slate-500 uppercase mb-1">Sampai Tanggal</label>
                    <input type="date" name="end_date" id="end_date" value="{{ request('end_date') }}"
                           class="block w-full rounded-lg border-slate-300 bg-slate-50 text-sm focus:border-indigo-500 focus:ring-indigo-500 shadow-sm">
                </div>

                {{-- Tombol Aksi --}}
                <div class="md:col-span-2 flex gap-2">
                    <button type="submit" class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold py-2 px-4 rounded-lg shadow transition">
                        Filter
                    </button>

                    {{-- Tombol Export PDF (Mengirim parameter filter saat ini) --}}
                    {{-- Pastikan Anda sudah membuat route 'customer.pesanan.export_pdf' --}}
                    <a href="{{ route('customer.pesanan.export_pdf', request()->all()) }}" target="_blank" class="bg-red-50 hover:bg-red-100 text-red-600 border border-red-200 text-sm font-bold py-2 px-3 rounded-lg shadow-sm transition" title="Export PDF">
                        <i class="fas fa-file-pdf fa-lg"></i>
                    </a>
                </div>
            </form>
        </div>

        {{-- TABEL DATA --}}
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden relative">

            {{-- 1. SCROLLBAR ATAS (Dummy) --}}
            <div id="top-scroll-container" class="overflow-x-auto w-full border-b border-slate-100 hidden md:block">
                <div id="top-scroll-content" class="h-4"></div>
            </div>

            {{-- 2. WRAPPER TABEL --}}
            <div id="bottom-scroll-container" class="overflow-x-auto w-full">
                <table id="data-table" class="w-full text-left border-collapse whitespace-nowrap">
                    <thead>
                        <tr class="bg-blue-900 text-white text-xs uppercase tracking-wider font-bold">
                            <th class="p-4 border-b border-indigo-800 text-center w-10">No</th>
                            <th class="p-4 border-b border-indigo-800 min-w-[250px]">Transaksi / Order Id</th>
                            <th class="p-4 border-b border-indigo-800 min-w-[250px]">Data Pengirim</th>
                            <th class="p-4 border-b border-indigo-800 min-w-[250px]">Data Penerima</th>
                            <th class="p-4 border-b border-indigo-800 min-w-[200px]">Ekspedisi & Tagihan</th>
                            <th class="p-4 border-b border-indigo-800 min-w-[200px]">Detail Paket</th>
                            <th class="p-4 border-b border-indigo-800 text-center min-w-[150px]">Status</th>
                            <th class="p-4 border-b border-indigo-800 text-center min-w-[120px]">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 text-sm text-slate-700">
                        @forelse ($pesanans as $order)
                            @php
                                // --- LOGIC PHP UNTUK DATA ---
                                $pm = strtoupper(trim($order->payment_method));
                                $pm = ($pm === 'DOKU_JOKUL') ? 'DOMPET SANCAKA' : $pm;
                                // Ganti otomatis jika POTONG SALDO
                                if ($pm === 'POTONG SALDO') {$pm = 'CASH / SALDO';}

                                $isCodOngkir = ($pm === 'COD');

                                // Ganti otomatis jika COD
                                if ($pm === 'COD') {$pm = 'COD ONGKIR';}
                                $isCodBarang = ($pm === 'CODBARANG');

                                // Ganti CODBARANG → COD BARANG
                                if ($pm === 'CODBARANG') {$pm = 'COD BARANG';}


                                $badgeColor = 'bg-blue-800 text-white';

if ($isCodBarang) {
    $badgeColor = 'bg-red-500 text-white';
}
elseif ($isCodOngkir) {
    $badgeColor = 'bg-yellow-400 text-grey';
}
elseif ($pm === 'DOMPET SANCAKA') {
    // Warna hijau
    $badgeColor = 'bg-green-800 text-white';
}

                                $expParts = explode('-', $order->expedition);
                                $kurirName = $expParts[1] ?? 'Expedition';
                                $kurirService = $expParts[2] ?? 'Reg';
                                $logo = strtolower(str_replace(' ', '', $kurirName));

                                $hargaBarang = $order->total_harga_barang ?? ($order->item_price ?? 0);
                                $ongkirAsli  = $order->shipping_cost ?? 0;
                                $asuransi    = $order->insurance_cost ?? 0;
                                $feeDb       = $order->cod_fee ?? 0;

                                // Hitung Total & Label
                                if ($isCodOngkir) {
                                    $basisBarang = ($hargaBarang > 1000000) ? 10000 : $hargaBarang;
                                    $basisHitung = $ongkirAsli + $basisBarang;
                                    $feeHitung = $basisHitung * 0.03;
                                    $feeLayanan = max(2500, ceil($feeHitung));
                                    $displayTotal = $ongkirAsli + $feeLayanan + $asuransi;
                                    $labelTotal = "Total Ongkir (COD)";
                                    $noteBawah = "*Jangan bayar harga barang lagi";
                                } elseif ($isCodBarang) {
                                    $feeLayanan = $feeDb;
                                    $displayTotal = $order->price;
                                    $labelTotal = "Total Bayar ke Kurir";
                                    $noteBawah = "";
                                } else {
                                    $feeLayanan = $feeDb;
                                    $displayTotal = $order->price;
                                    $labelTotal = "Total Biaya";
                                    $noteBawah = "";
                                }
                            @endphp

                            <tr class="hover:bg-slate-50 transition duration-150">
                                {{-- 1. NO --}}
                                <td class="p-4 text-center font-bold text-slate-500 bg-slate-50/50">
                                    {{ $loop->iteration + ($pesanans->currentPage() - 1) * $pesanans->perPage() }}
                                </td>

                                {{-- 2. TRANSAKSI --}}
                                <td class="p-4 align-top">
                                    <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold uppercase {{ $badgeColor }} mb-1">
                                        {{ $pm }}
                                    </span>
                                    <div class="text-xs text-slate-500 mt-1">
                                        <i class="far fa-clock mr-1"></i>
                                        {{ \Carbon\Carbon::parse($order->tanggal_pesanan)->translatedFormat('l, d F Y : H.i') }}
                                    </div>
                                    <div class="font-mono font-bold text-indigo-600 mt-1">
                                        <a><strong>Order Id:</strong> {{ $order->nomor_invoice }}</a>
                                    </div>

                                    @if($order->resi)
                                        <div class="mt-2 p-2 bg-green-100 border border-green-200 rounded hover:bg-green-200 cursor-pointer"
                                        onclick="navigator.clipboard.writeText('{{ $order->resi }}'); alert('Resi disalin!')">
                                            <div class="flex items-start justify-between flex-wrap gap-1">
                                                <div class="flex-1 min-w-[140px]">
                                                    <div class="text-[10px] text-slate-500 uppercase leading-none">Nomor Resi Pengiriman:</div>
                                                    <div class="font-mono font-bold text-slate-800 text-xs break-all">{{ $order->resi }}</div>
                                                </div>
                                                <div class="flex items-center"><i class="fas fa-copy text-slate-600 text-sm p-1"></i></div>
                                            </div>
                                        </div>
                                    @endif

                                    {{-- ========================================================================= --}}
                                    {{-- 🔥 TAMBAHAN BARU: SHIPPING REF (KODE BOOKING) JIKA ADA 🔥 --}}
                                    {{-- ========================================================================= --}}
                                    @if(!empty($order->shipping_ref))
                                        <div class="mt-1 p-1.5 bg-blue-50 border border-blue-200 rounded flex items-center gap-2">
                                            <i class="fas fa-barcode text-blue-500"></i>
                                            <div>
                                                <div class="text-[9px] text-slate-500 uppercase leading-none">Kode Booking (Ref):</div>
                                                <div class="font-mono font-bold text-blue-800 text-xs">{{ $order->shipping_ref }}</div>
                                            </div>
                                        </div>
                                    @endif
                                    {{-- ========================================================================= --}}

                                </td>

                                {{-- 3. PENGIRIM --}}
                                <td class="p-4 align-top">
                                    <div class="font-bold text-slate-800 mb-0.5">{{ $order->sender_name }}</div>
                                    <div class="flex items-center gap-1 text-xs text-indigo-600 font-semibold mb-1">
                                        <i class="fas fa-phone-alt fa-fw"></i> {{ $order->sender_phone }}
                                    </div>
                                    <div class="text-xs text-slate-600 whitespace-normal leading-snug max-w-[250px]">
                                        {{ $order->sender_address }}<br>
                                        {{ $order->sender_village }}, {{ $order->sender_district }}<br>
                                        {{ $order->sender_regency }}, {{ $order->sender_province }}<br>
                                        <span class="font-semibold">Kode Pos: {{ $order->sender_postal_code }}</span>
                                    </div>
                                    @if($order->sender_note)
                                        <div class="mt-1 text-[10px] text-slate-500 italic border-l-2 border-yellow-400 pl-1">Note: {{ $order->sender_note }}</div>
                                    @endif
                                </td>

                                {{-- 4. PENERIMA --}}
                                <td class="p-4 align-top bg-slate-50/30">
                                    <div class="font-bold text-slate-800 mb-0.5">{{ $order->receiver_name }}</div>
                                    <div class="flex items-center gap-1 text-xs text-green-600 font-semibold mb-1">
                                        <i class="fas fa-phone-alt fa-fw"></i> {{ $order->receiver_phone }}
                                    </div>
                                    <div class="text-xs text-slate-600 whitespace-normal leading-snug max-w-[250px]">
                                        {{ $order->receiver_address }}<br>
                                        {{ $order->receiver_village }}, {{ $order->receiver_district }}<br>
                                        {{ $order->receiver_regency }}, {{ $order->receiver_province }}<br>
                                        <span class="font-semibold">Kode Pos: {{ $order->receiver_postal_code }}</span>
                                    </div>
                                    @if($order->receiver_note)
                                        <div class="mt-1 text-[10px] text-slate-500 italic border-l-2 border-yellow-400 pl-1">Note: {{ $order->receiver_note }}</div>
                                    @endif
                                </td>

                                {{-- 5. EKSPEDISI & TAGIHAN --}}
                                <td class="p-4 align-top">
                                    <div class="flex items-center gap-2 mb-2">
                                        <img src="{{ asset('public/storage/logo-ekspedisi/' . $logo . '.png') }}"
                                             class="h-5 w-auto object-contain" onerror="this.style.display='none';">
                                        <div class="text-xs font-bold uppercase">{{ $kurirName }} <span class="text-slate-400 font-normal">{{ $kurirService }}</span></div>
                                    </div>

                                    {{-- Kotak Total Bayar --}}
                                    <div class="p-2 border border-red-500 rounded bg-red-100">
                                        <div class="text-[10px] font-bold text-slate-400 uppercase">{{ $labelTotal }}</div>
                                        <div class="text-lg font-extrabold text-slate-800">Rp {{ number_format($displayTotal, 0, ',', '.') }}</div>
                                    </div>

                                    {{-- Rincian Biaya --}}
                                    <div class="mt-2 space-y-1 text-xs text-slate-600">
                                        <div class="flex justify-between gap-4">
                                            <span>Barang:</span>
                                            <span class="font-medium {{ $isCodOngkir ? 'line-through text-slate-400' : '' }}">
                                                Rp {{ number_format($hargaBarang, 0, ',', '.') }}
                                            </span>
                                        </div>
                                        <div class="flex justify-between gap-4">
                                            <span>Ongkir:</span>
                                            <span class="font-medium">Rp {{ number_format($ongkirAsli, 0, ',', '.') }}</span>
                                        </div>
                                        <div class="flex justify-between gap-4">
                                            <span>Biaya Layanan:</span>
                                            <span class="font-medium">Rp {{ number_format($feeLayanan, 0, ',', '.') }}</span>
                                        </div>
                                        @if($asuransi > 0)
                                        <div class="flex justify-between gap-4">
                                            <span>Asuransi:</span>
                                            <span class="font-medium">Rp {{ number_format($asuransi, 0, ',', '.') }}</span>
                                        </div>
                                        @endif
                                        @if($isCodOngkir)
                                            <div class="text-[10px] text-red-600 italic mt-1">{{ $noteBawah }}</div>
                                        @endif
                                    </div>
                                </td>

                                {{-- 6. DETAIL PAKET --}}
                                <td class="p-4 align-top">
                                    <div class="font-semibold text-slate-800 text-sm whitespace-normal max-w-[200px] mb-1">
                                        ISI PAKET : {{ $order->item_description }}
                                    </div>
                                    <div class="text-xs text-slate-500 space-y-0.5">
                                        <div><i class="fas fa-weight-hanging w-4"></i> {{ $order->weight }} Gram</div>
                                        <div><i class="fas fa-tag w-4"></i> Rp {{ number_format($hargaBarang, 0, ',', '.') }}</div>
                                        <div><i class="fas fa-cube w-4"></i> {{ $order->item_type ?? 'Paket' }}</div>
                                        <div><i class="fas fa-cube w-4"></i> Dimensi: {{ $order->length ?? '0' }} x {{ $order->width ?? '0' }} x {{ $order->height ?? '0' }} cm</div>
                                    </div>
                                </td>

                                {{-- 7. STATUS --}}
                                <td class="p-4 align-top text-center">
                                    @php
                                        $statusMap = [
                                            'Menunggu Pembayaran' => 'bg-yellow-100 text-yellow-800',
                                            'Menunggu Pickup' => 'bg-sky-100 text-sky-800',
                                            'Sedang Dikirim' => 'bg-blue-100 text-blue-800',
                                            'Selesai' => 'bg-green-100 text-green-800',
                                            'Dibatalkan' => 'bg-red-100 text-red-800',
                                        ];
                                        $stClass = $statusMap[$order->status_pesanan] ?? 'bg-slate-100 text-slate-800';
                                    @endphp
                                    <span class="px-2 py-1 rounded text-[11px] font-bold {{ $stClass }} inline-block w-full">
                                        {{ $order->status_pesanan }}
                                    </span>
                                </td>

                                {{-- 8. AKSI --}}
                                <td class="p-4 align-top text-center">
                                    @if ($order->status_pesanan === 'Menunggu Pembayaran' && $order->payment_url)
                                        <a href="{{ $order->payment_url }}" target="_blank" class="flex items-center justify-center gap-1 w-full bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold py-1.5 rounded transition mb-2">
                                            <i class="fas fa-wallet"></i> Bayar
                                        </a>
                                    @endif

                                    @if ($order->resi)
                                        <a href="{{ route('customer.lacak.index', ['resi' => $order->resi]) }}"
   class="flex items-center justify-center gap-1 w-full bg-white border border-slate-300
          hover:bg-slate-50 text-slate-700 text-xs font-bold py-1.5 rounded-md transition">
    <i class="fas fa-search-location"></i>
    Lacak
</a>

<br>

<a href="{{ url($order->resi . '/cetak_thermal') }}"
   target="_blank"
   class="flex items-center justify-center gap-1 w-full bg-green-600 border border-green-700
          hover:bg-green-700 text-white text-xs font-bold py-1.5 rounded-md transition">
    <i class="fas fa-print"></i>
    Cetak Resi
</a>

                                    @endif
                                </td>

                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="p-12 text-center text-slate-500">
                                    <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                        <i class="fas fa-box-open text-slate-400 text-2xl"></i>
                                    </div>
                                    <p class="font-medium">Data pesanan tidak ditemukan.</p>
                                    <p class="text-xs mt-1">Coba ubah kata kunci pencarian atau filter.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>

        {{-- PAGINATION --}}
        @if($pesanans->hasPages())
            <div class="mt-6">
                {{ $pesanans->links() }}
            </div>
        @endif

    </div>
</div>

{{-- SCRIPT SCROLLBAR GANDA --}}
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const topScrollContainer = document.getElementById('top-scroll-container');
        const topScrollContent = document.getElementById('top-scroll-content');
        const bottomScrollContainer = document.getElementById('bottom-scroll-container');
        const dataTable = document.getElementById('data-table');

        // Fungsi sinkronisasi lebar dummy scrollbar dengan lebar tabel asli
        function syncWidth() {
            if(dataTable && topScrollContent) {
                topScrollContent.style.width = dataTable.offsetWidth + 'px';
            }
        }

        // Sinkronisasi gerakan scroll
        if(topScrollContainer && bottomScrollContainer) {
            topScrollContainer.addEventListener('scroll', function(e) {
                bottomScrollContainer.scrollLeft = topScrollContainer.scrollLeft;
            });

            bottomScrollContainer.addEventListener('scroll', function(e) {
                topScrollContainer.scrollLeft = bottomScrollContainer.scrollLeft;
            });
        }

        // Jalankan saat load & resize
        syncWidth();
        window.addEventListener('resize', syncWidth);
    });
</script>

<style>
    /* Styling scrollbar agar lebih manis */
    ::-webkit-scrollbar {
        height: 8px; /* Tinggi scrollbar horizontal */
        width: 8px;
    }
    ::-webkit-scrollbar-track {
        background: #f1f5f9;
    }
    ::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 4px;
    }
    ::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }
</style>
@endsection
