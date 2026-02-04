@extends('layouts.admin')

{{-- Perbaikan: Judul halaman menggunakan resi atau nomor invoice --}}
@section('title', 'Detail Pesanan: ' . ($order->resi ?? $order->nomor_invoice))

@section('page-title', 'Detail Pesanan')

@section('content')
<div class="bg-white p-8 rounded-lg shadow-md max-w-4xl mx-auto">
    <div class="flex flex-col sm:flex-row justify-between items-start mb-6 border-b pb-6">
        <div>
            {{-- Perbaikan: Header menampilkan resi atau 'Menunggu Resi' --}}
            <h2 class="text-2xl font-bold text-gray-800">Resi: {{ $order->resi ?? 'Menunggu Resi' }}</h2>

            {{-- Label COD / NON-COD --}}
            @if(Str::contains($order->payment_method, 'COD'))
                <span class="mt-1 inline-block bg-green-100 text-green-800 text-xs font-semibold px-2.5 py-0.5 rounded-full">COD</span>
            @else
                <span class="mt-1 inline-block bg-blue-100 text-blue-800 text-xs font-semibold px-2.5 py-0.5 rounded-full">NON-COD</span>
            @endif

            <p class="text-sm text-gray-500 mt-2">Tanggal Pesanan: {{ \Carbon\Carbon::parse($order->tanggal_pesanan)->format('d M Y, H:i') }}</p>
            <p class="text-sm text-gray-500">No. Invoice: {{ $order->nomor_invoice }}</p>
        </div>
        <div class="text-right mt-4 sm:mt-0">
            @php
                $status_colors = [
                    'Terkirim' => 'bg-green-100 text-green-800',
                    'Diproses' => 'bg-blue-100 text-blue-800',
                    'Menunggu Pickup' => 'bg-yellow-100 text-yellow-800',
                    'Batal' => 'bg-red-100 text-red-800',
                    'Menunggu Pembayaran' => 'bg-gray-100 text-gray-800',
                    'Pembayaran Lunas (Gagal Auto-Resi)' => 'bg-red-100 text-red-800',
                    'Pembayaran Lunas (Error Kirim API)' => 'bg-red-100 text-red-800',
                    'Kadaluarsa' => 'bg-gray-100 text-gray-800',
                    'Gagal Bayar' => 'bg-red-100 text-red-800',
                ];
            @endphp
            <span class="px-3 py-1 text-sm font-semibold rounded-full {{ $status_colors[$order->status_pesanan] ?? 'bg-gray-100 text-gray-800' }}">
                Status: {{ $order->status_pesanan }}
            </span>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        <div>
            <h3 class="font-semibold text-lg text-gray-700 mb-3">Pengirim</h3>
            <p class="text-gray-800 font-medium">{{ $order->sender_name }}</p>
            <p class="text-gray-600">{{ $order->sender_phone }}</p>
            <p class="text-gray-600 mt-2">{{ $order->sender_address }}</p>
            @if($order->sender_note)
            <p class="text-sm text-gray-500 mt-1">Catatan: {{ $order->sender_note }}</p>
            @endif
        </div>
        
        <div>
            <h3 class="font-semibold text-lg text-gray-700 mb-3">Penerima</h3>
            {{-- Menggunakan kolom baru dan fallback ke kolom lama jika ada --}}
            <p class="text-gray-800 font-medium">{{ $order->receiver_name ?? $order->nama_pembeli }}</p>
            <p class="text-gray-600">{{ $order->receiver_phone ?? $order->telepon_pembeli }}</p>
            <p class="text-gray-600 mt-2">{{ $order->receiver_address ?? $order->alamat_pengiriman }}</p>
            @if($order->receiver_note)
            <p class="text-sm text-gray-500 mt-1">Catatan: {{ $order->receiver_note }}</p>
            @endif
        </div>
    </div>

    {{-- Perbaikan: Parsing ekspedisi HANYA untuk nama, BUKAN BIAYA --}}
    @php
        $expedition_parts = explode('-', $order->expedition ?? '---');
        $courier = !empty($expedition_parts[1]) ? strtoupper($expedition_parts[1]) : 'N/A';
        $service = !empty($expedition_parts[2]) ? $expedition_parts[2] : 'N/A';
    @endphp

    <div class="mt-8 border-t pt-6">
        <h3 class="font-semibold text-lg text-gray-700 mb-3">Detail Pengiriman & Paket</h3>
        <dl class="grid grid-cols-2 md:grid-cols-3 gap-x-6 gap-y-4 text-sm">
            <div><dt class="text-gray-500">Ekspedisi</dt><dd class="text-gray-800 font-medium">{{ $courier }}</dd></div>
            <div><dt class="text-gray-500">Layanan</dt><dd class="text-gray-800 font-medium">{{ $service }} ({{ $order->service_type }})</dd></div>
            <div><dt class="text-gray-500">Pembayaran</dt><dd class="text-gray-800 font-medium">{{ $order->payment_method }}</dd></div>
            <div><dt class="text-gray-500">Isi Paket</dt><dd class="text-gray-800 font-medium">{{ $order->item_description }}</dd></div>
            <div><dt class="text-gray-500">Berat</dt><dd class="text-gray-800 font-medium">{{ number_format($order->weight) }} gram</dd></div>
            <div><dt class="text-gray-500">Dimensi</dt><dd class="text-gray-800 font-medium">{{ $order->length ?? '0' }} x {{ $order->width ?? '0' }} x {{ $order->height ?? '0' }} cm</dd></div>
        </dl>
    </div>

    {{-- Perbaikan: Rincian Biaya membaca dari kolom baru di DB --}}
    <div class="mt-8 border-t pt-6">
        <h3 class="font-semibold text-lg text-gray-700 mb-3">Rincian Biaya</h3>
        <dl class="grid grid-cols-2 gap-x-6 gap-y-2 text-sm">
            
            {{-- Menggunakan 'item_price' dan fallback ke 'total_harga_barang' --}}
            <div><dt class="text-gray-500">Harga Barang</dt><dd class="text-gray-800 font-medium">Rp {{ number_format($order->item_price ?? $order->total_harga_barang ?? 0) }}</dd></div>
            
            {{-- Menggunakan 'shipping_cost' --}}
            <div><dt class="text-gray-500">Ongkos Kirim</dt><dd class="text-gray-800 font-medium">Rp {{ number_format($order->shipping_cost ?? 0) }}</dd></div>
            
            {{-- Menggunakan 'insurance_cost' --}}
            @if($order->insurance_cost > 0)
                <div><dt class="text-gray-500">Biaya Asuransi</dt><dd class="text-gray-800 font-medium">Rp {{ number_format($order->insurance_cost) }}</dd></div>
            @endif

            {{-- Menggunakan 'cod_fee' --}}
            @if($order->cod_fee > 0)
                    <div><dt class="text-gray-500">Biaya COD</dt><dd class="text-gray-800 font-medium">Rp {{ number_format($order->cod_fee) }}</dd></div>
            @endif
            
            <div class="col-span-2 border-t mt-2 pt-2"></div>
            <div><dt class="text-gray-500 font-bold">Total</dt><dd class="text-gray-800 font-bold text-base">Rp {{ number_format($order->price) }}</dd></div>
        </dl>
    </div>

    @if($order->resi_aktual)
    <div class="mt-8 border-t pt-6">
        <h3 class="font-semibold text-lg text-gray-700 mb-3">Informasi Resi Aktual</h3>
        <dl class="grid grid-cols-2 md:grid-cols-3 gap-x-6 gap-y-4 text-sm">
            <div><dt class="text-gray-500">Ekspedisi Aktual</dt><dd class="text-gray-800 font-medium">{{ $order->jasa_ekspedisi_aktual }}</dd></div>
            <div><dt class="text-gray-500">Nomor Resi Aktual</dt><dd class="text-gray-800 font-medium">{{ $order->resi_aktual }}</dd></div>
        </dl>
    </div>
    @endif

    <div class="mt-8 text-center border-t pt-6">
        <a href="{{ route('admin.pesanan.index') }}" class="bg-gray-200 text-gray-700 px-6 py-2 rounded-lg hover:bg-gray-300">
            Kembali ke Data Pesanan
        </a>
        
        {{-- Perbaikan: Tombol Cetak Resi --}}
        @if(!empty($order->resi))
            <a href="{{ route('admin.pesanan.cetak_thermal', $order->resi) }}" target="_blank" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">
                Cetak Resi Thermal
            </a>
        @else
            <a href="#" class="bg-gray-400 text-white px-6 py-2 rounded-lg cursor-not-allowed" aria-disabled="true" 
               onclick="alert('Resi belum tersedia. Silakan refresh halaman ini dalam beberapa detik.'); return false;">
                Cetak Resi (Menunggu Resi)
            </a>
        @endif
    </div>

</div>
@endsection