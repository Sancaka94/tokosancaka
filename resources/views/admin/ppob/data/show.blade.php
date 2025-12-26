@extends('layouts.admin')

@section('title', 'Detail Transaksi #' . $transaction->order_id)

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    {{-- HEADER & TOMBOL KEMBALI --}}
    <div class="flex items-center justify-between">
        <div>
            <a href="https://tokosancaka.com/admin/ppob/data" class="text-sm text-gray-500 hover:text-blue-600 transition mb-1 inline-flex items-center gap-1">
                <i class="fas fa-arrow-left"></i> Kembali ke Riwayat
            </a>
            <h2 class="text-2xl font-bold text-gray-800">Detail Transaksi</h2>
        </div>
        
        {{-- Status Badge Besar --}}
        @php
            $statusColor = match($transaction->status) {
                'Success' => 'bg-green-100 text-green-700 border-green-200',
                'Pending' => 'bg-yellow-100 text-yellow-700 border-yellow-200',
                'Processing' => 'bg-blue-100 text-blue-700 border-blue-200',
                'Failed' => 'bg-red-100 text-red-700 border-red-200',
                default => 'bg-gray-100 text-gray-700 border-gray-200'
            };
            $statusIcon = match($transaction->status) {
                'Success' => 'fa-check-circle',
                'Failed' => 'fa-times-circle',
                'Processing' => 'fa-spinner fa-spin',
                default => 'fa-clock'
            };
        @endphp
        <span class="px-4 py-2 rounded-lg text-sm font-bold border {{ $statusColor }} flex items-center gap-2">
            <i class="fas {{ $statusIcon }}"></i> {{ $transaction->status }}
        </span>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        
        {{-- KOLOM KIRI: INFO UTAMA --}}
        <div class="md:col-span-2 space-y-6">
            
            {{-- KARTU DETAIL PRODUK --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="bg-gray-50 px-6 py-4 border-b border-gray-100 flex justify-between items-center">
                    <h3 class="font-bold text-gray-800">Informasi Produk</h3>
                    <span class="text-xs font-mono text-gray-400">#{{ $transaction->order_id }}</span>
                </div>
                <div class="p-6">
                    <div class="flex items-start gap-4">
                        {{-- Logo Brand --}}
                        @php
                            $brandName = strtolower($transaction->brand ?? 'other');
                            // Menggunakan asset() standar Laravel. Pastikan file ada di public/storage/logo-ppob/
                            $logoUrl = asset('storage/logo-ppob/' . $brandName . '.png');
                        @endphp
                        <div class="h-16 w-16 bg-gray-50 rounded-lg border border-gray-200 flex items-center justify-center p-2">
                            <img src="{{ $logoUrl }}" onerror="this.onerror=null; this.src='https://via.placeholder.com/64?text={{ substr($transaction->brand ?? 'NA', 0, 3) }}'" alt="{{ $brandName }}" class="max-h-full max-w-full object-contain">
                        </div>
                        
                        <div class="flex-1">
                            <h4 class="text-lg font-bold text-gray-900">{{ $transaction->product_name }}</h4>
                            <p class="text-sm text-gray-500 mb-2">{{ $transaction->brand }} - {{ $transaction->category }}</p>
                            
                            <div class="grid grid-cols-2 gap-4 mt-4">
                                <div>
                                    <p class="text-xs text-gray-500 uppercase font-semibold">Kode SKU</p>
                                    <p class="font-mono text-sm font-medium">{{ $transaction->buyer_sku_code }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500 uppercase font-semibold">Tujuan / No. Pelanggan</p>
                                    <div class="flex items-center gap-2">
                                        <p class="font-mono text-lg font-bold text-blue-600">{{ $transaction->customer_no }}</p>
                                        <button onclick="navigator.clipboard.writeText('{{ $transaction->customer_no }}')" class="text-gray-400 hover:text-blue-500" title="Salin">
                                            <i class="far fa-copy"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- SN AREA (Jika Sukses) --}}
                    @if($transaction->status == 'Success')
                    <div class="mt-6 bg-green-50 border border-green-200 rounded-lg p-4">
                        <p class="text-xs text-green-700 uppercase font-bold mb-1">Serial Number (SN) / Token</p>
                        <p class="font-mono text-sm text-gray-800 break-all select-all">{{ $transaction->sn }}</p>
                    </div>
                    @elseif($transaction->status == 'Failed')
                    <div class="mt-6 bg-red-50 border border-red-200 rounded-lg p-4">
                        <p class="text-xs text-red-700 uppercase font-bold mb-1">Penyebab Gagal</p>
                        <p class="text-sm text-red-600">{{ $transaction->note ?? $transaction->message }}</p>
                    </div>
                    @endif
                </div>
            </div>

            {{-- KARTU INFO USER --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="font-bold text-gray-800">Data Pelanggan (Agen)</h3>
                </div>
                <div class="p-6 flex items-center gap-4">
                    @php
                        // Perbaikan path gambar user. Hapus 'public/' di dalam asset() jika storage link sudah benar.
                        $userImage = !empty($transaction->user->store_logo_path) 
                            ? asset('storage/' . $transaction->user->store_logo_path) 
                            : 'https://ui-avatars.com/api/?name='.urlencode($transaction->user->name ?? 'User').'&background=random&color=fff';
                    @endphp
                    <img src="{{ $userImage }}" onerror="this.onerror=null; this.src='https://ui-avatars.com/api/?name=User&background=random&color=fff'" class="h-12 w-12 rounded-full object-cover border border-gray-200">
                    <div>
                        <p class="font-bold text-gray-900">{{ $transaction->user->nama_lengkap ?? ($transaction->user->name ?? 'User Terhapus') }}</p>
                        <p class="text-sm text-gray-500">{{ $transaction->user->email ?? '-' }}</p>
                        <p class="text-xs text-gray-400 mt-1"><i class="fas fa-phone-alt mr-1"></i> {{ $transaction->user->no_wa ?? '-' }}</p>
                    </div>
                </div>
            </div>

        </div>

        {{-- KOLOM KANAN: FINANCIAL --}}
        <div class="md:col-span-1 space-y-6">
            
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                    <h3 class="font-bold text-gray-800">Rincian Keuangan</h3>
                </div>
                <div class="p-6 space-y-4">
                    
                    <div class="flex justify-between items-center pb-2 border-b border-gray-50">
                        <span class="text-sm text-gray-500">Harga Beli (Pusat)</span>
                        <span class="font-mono text-sm">Rp {{ number_format($transaction->price, 0, ',', '.') }}</span>
                    </div>

                    <div class="flex justify-between items-center pb-2 border-b border-gray-50">
                        <span class="text-sm text-gray-500">Harga Jual (Agen)</span>
                        <span class="font-mono text-sm font-bold text-gray-800">Rp {{ number_format($transaction->selling_price, 0, ',', '.') }}</span>
                    </div>

                    <div class="flex justify-between items-center pt-2">
                        <span class="text-sm font-bold text-green-600">Keuntungan</span>
                        <span class="font-mono text-lg font-bold text-green-600">+Rp {{ number_format($transaction->profit, 0, ',', '.') }}</span>
                    </div>

                </div>
                <div class="bg-gray-50 px-6 py-3 text-xs text-gray-500 text-center">
                    Waktu Transaksi: <br>
                    {{ $transaction->created_at->format('d M Y, H:i:s') }}
                </div>
            </div>

            {{-- Tombol Aksi Tambahan --}}
            <div class="space-y-3">
                @if($transaction->status == 'Pending' || $transaction->status == 'Processing')
                <button onclick="window.location.reload()" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-xl shadow transition">
                    <i class="fas fa-sync-alt mr-2"></i> Cek Status Terbaru
                </button>
                @endif

                @if(isset($transaction->user->no_wa))
                <a href="https://wa.me/{{ preg_replace('/^0/', '62', $transaction->user->no_wa) }}?text=Halo%20kak,%20terkait%20transaksi%20{{ $transaction->product_name }}%20ke%20{{ $transaction->customer_no }}..." target="_blank" class="w-full flex items-center justify-center bg-green-500 hover:bg-green-600 text-white font-bold py-3 rounded-xl shadow transition">
                    <i class="fab fa-whatsapp mr-2"></i> Hubungi Agen
                </a>
                @else
                <button disabled class="w-full bg-gray-300 text-gray-500 font-bold py-3 rounded-xl cursor-not-allowed">
                    <i class="fab fa-whatsapp mr-2"></i> No HP Tidak Tersedia
                </button>
                @endif
            </div>

        </div>
    </div>

</div>
@endsection