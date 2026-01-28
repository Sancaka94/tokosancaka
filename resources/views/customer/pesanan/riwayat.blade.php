{{--
File: resources/views/customer/pesanan/riwayat.blade.php
Deskripsi: Tampilan riwayat OrderMarketplace dengan desain rinci.
--}}

@extends('layouts.customer')

@section('title', 'Riwayat Pesanan Marketplace')

@section('content')
<div class="bg-slate-50 min-h-screen">
    <div class="container mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">

        <div class="mb-8 text-center md:text-left">
            <h1 class="text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">
                Riwayat Pesanan Marketplace
            </h1>
            <p class="mt-2 text-lg text-slate-600">
                Berikut adalah riwayat semua pesanan marketplace yang telah Anda buat.
            </p>
        </div>

        <div class="space-y-4">
            <div class="hidden md:grid grid-cols-12 gap-4 px-4 py-2 bg-red-100 text-red-800 rounded-lg text-xs font-bold uppercase tracking-wider">
                <div class="col-span-1 text-center">No</div>
                <div class="col-span-2">Transaksi</div>
                <div class="col-span-3">Alamat</div>
                <div class="col-span-2">Ekspedisi & Ongkir</div>
                <div class="col-span-2">Isi Paket</div>
                <div class="col-span-2 text-center">Status</div>
            </div>

            {{-- ========================================================== --}}
            {{-- PERBAIKAN: Loop $pesanans (sesuai kiriman controller) --}}
            {{-- ========================================================== --}}
            @forelse ($pesanans as $order)
                <div class="bg-white rounded-xl shadow-md overflow-hidden border border-slate-200 hover:shadow-lg transition duration-200">
                    <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-start">

                        <div class="hidden md:flex col-span-1 h-full items-center justify-center font-bold text-slate-700 bg-slate-50 border-r border-slate-200">
                            {{ $loop->iteration + ($pesanans->currentPage() - 1) * $pesanans->perPage() }}
                        </div>

                        <div class="md:col-span-2 p-4">
                            <div class="md:hidden text-xs font-semibold uppercase tracking-wider text-slate-500 mb-2">Transaksi</div>
                            <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium bg-orange-100 text-orange-800">
                                {{-- Variabel Marketplace --}}
                                {{ $order->payment_method }}
                            </span>
                            <div class="mt-2 font-semibold text-sm text-indigo-600">{{ $order->invoice_number }}</div>
                            <div class="text-xs text-slate-500">
                                {{-- Variabel Marketplace --}}
                                {{ \Carbon\Carbon::parse($order->created_at)->format('d M Y H:i') }}
                            </div>
                            <div class="text-xs text-slate-500">
                                Dibuat oleh: {{ Auth::user()->nama_lengkap }}
                            </div>
                        </div>

                        <div class="md:col-span-3 p-4 border-t md:border-t-0 md:border-l border-slate-200 space-y-3">
                            {{-- ALAMAT PENGIRIM (TOKO) --}}
                            <div class="flex items-start">
                                <i class="fas fa-upload text-red-500 mr-3 mt-1 flex-shrink-0"></i>
                                <div>
                                    <div class="font-semibold text-slate-800 text-sm">
                                        {{-- Variabel Marketplace --}}
                                        {{ $order->store?->name ?? 'Toko Dihapus' }} ({{ $order->store?->user?->no_wa ?? 'N/A' }})
                                    </div>
                                    <div class="text-xs text-slate-600">{{ $order->store?->address_detail ?? 'Alamat toko tidak ada' }}</div>
                                </div>
                            </div>

                            {{-- ALAMAT PENERIMA (PEMBELI) --}}
                            <div class="flex items-start">
                                <i class="fas fa-download text-green-500 mr-3 mt-1 flex-shrink-0"></i>
                                <div>
                                    <div class="font-semibold text-slate-800 text-sm">
                                        {{-- Variabel Marketplace --}}
                                        {{ $order->user?->nama_lengkap ?? 'User Tidak Dikenal' }} ({{ $order->user?->no_wa ?? '-' }})
                                    </div>
                                    <div class="text-xs text-slate-600">{{ $order->shipping_address }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="md:col-span-2 p-4 border-t md:border-t-0 md:border-l border-slate-200">
                            <div class="md:hidden text-xs font-semibold uppercase tracking-wider text-slate-500 mb-2">
                                Ekspedisi & Ongkir
                            </div>
                            @php
                                // PARSING DATA DARI 'shipping_method' (cth: regular-anteraja-REG-6500-0-0)
                                $shippingParts = explode('-', $order->shipping_method);
                                $expeditionName = ucwords($shippingParts[1] ?? 'N/A');
                                $expeditionService = $shippingParts[2] ?? 'N/A';
                                $logoPath = strtolower(str_replace(' ', '', $expeditionName));
                            @endphp
                            <div class="flex items-center mb-1">
                                {{-- PERBAIKAN PATH GAMBAR: Hapus 'public/' --}}
                                <img src="{{ asset('public/storage/logo-ekspedisi/' . $logoPath . '.png') }}"
                                     alt="{{ $expeditionName }} Logo"
                                     class="w-10 h-auto mr-2"
                                     onerror="this.style.display='none'"> {{-- Sembunyikan jika logo tdk ada --}}
                                <span class="font-bold text-sm">{{ $expeditionName }}</span>
                            </div>
                            <div class="text-xs text-slate-600">{{ $expeditionService }}</div>
                            <div class="text-sm text-slate-600 mt-2">
                                {{-- Variabel Marketplace --}}
                                Ongkir: <span class="font-bold text-red-600">Rp {{ number_format($order->shipping_cost ?? 0, 0, ',', '.') }}</span>
                            </div>
                            <div class="text-xs text-slate-500 break-all font-semibold">
                                {{-- Variabel Marketplace --}}
                                <strong>Resi: {{ $order->shipping_resi ?? 'Menunggu' }}</strong>
                            </div>
                        </div>

                        <div class="md:col-span-2 p-4 border-t md:border-t-0 md:border-l border-slate-200">
                            <div class="md:hidden text-xs font-semibold uppercase tracking-wider text-slate-500 mb-2">Isi Paket</div>

                            {{-- PERBAIKAN: Gunakan Null Coalescing (??) agar tidak error jika items null --}}
                            @php
                                // Jika $order->items null, ganti dengan collect([]) agar tetap dianggap array kosong
                                $items = $order->items ?? collect([]);
                                $firstItem = $items->first();
                            @endphp

                            <div class="text-sm text-slate-800 font-semibold">
                                {{-- Cek apakah firstItem ada produknya --}}
                                {{ $firstItem?->product?->name ?? 'Produk Tidak Ditemukan' }}

                                @if($items->count() > 1)
                                    <span class="text-xs text-slate-500">(+ {{ $items->count() - 1 }} item lain)</span>
                                @endif
                            </div>
                            <div class="text-xs text-slate-600">Total Nilai: Rp {{ number_format($order->subtotal ?? 0, 0, ',', '.') }}</div>
                            {{-- Gunakan variabel $items yang sudah aman --}}
                            <div class="text-xs text-slate-600">Total Kuantitas: {{ $items->sum('quantity') }} pcs</div>
                        </div>

                        <div class="md:col-span-2 p-4 flex flex-col items-center justify-center border-t md:border-t-0 md:border-l border-slate-200">
                            <div class="md:hidden text-xs font-semibold uppercase tracking-wider text-slate-500 mb-2">Status</div>
                            @php
                                // (Kode badge warna status biarkan saja seperti sebelumnya)
                                $status = strtolower($order->status);
                                $badgeClass = match($status) {
                                    'paid', 'completed', 'success' => 'bg-green-100 text-green-800',
                                    'pending'    => 'bg-yellow-100 text-yellow-800',
                                    'failed'     => 'bg-red-100 text-red-800',
                                    'expired'    => 'bg-gray-100 text-gray-800',
                                    'processing' => 'bg-blue-100 text-blue-800',
                                    default      => 'bg-blue-100 text-blue-800'
                                };
                            @endphp
                            <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold leading-5 {{ $badgeClass }}">
                                {{ ucfirst($status) }}
                            </span>

                            {{-- ================================================= --}}
                            {{-- PERBAIKAN: Cek Invoice Number Sebelum Render Link --}}
                            {{-- ================================================= --}}

                            @if ($status === 'pending' && $order->payment_url)
                                {{-- Cek apakah invoice ada isinya --}}
                                @if(!empty($order->invoice_number))
                                    <a href="{{ route('checkout.invoice', ['invoice' => $order->invoice_number]) }}" class="mt-2 text-indigo-600 hover:text-indigo-900 font-semibold text-sm">
                                        Bayar Sekarang
                                    </a>
                                @else
                                    <span class="mt-2 text-xs text-red-400 italic">Invoice Error</span>
                                @endif

                            @elseif ($order->shipping_resi)
                                <a href="{{ route('customer.lacak.index', ['resi' => $order->shipping_resi]) }}"
                                   class="mt-2 text-indigo-600 hover:text-indigo-900 font-semibold text-sm">
                                   Lacak Paket
                                </a>

                            @else
                                {{-- Cek apakah invoice ada isinya --}}
                                @if(!empty($order->invoice_number))
                                    <a href="{{ route('checkout.invoice', ['invoice' => $order->invoice_number]) }}" class="mt-2 text-blue-600 hover:text-blue-900 font-semibold text-sm">
                                       Lihat Invoice
                                    </a>
                                @else
                                    <span class="mt-2 text-xs text-slate-400 italic">Menunggu Invoice</span>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center py-16 bg-white rounded-xl shadow-md border border-slate-200">
                    <div class="mx-auto max-w-md">
                        <i class="fas fa-box-open fa-4x text-slate-300"></i>
                        <h3 class="mt-4 text-lg font-medium text-slate-900">Tidak ada data pesanan</h3>
                        <p class="mt-1 text-sm text-slate-500">
                            Anda belum pernah membuat pesanan marketplace.
                        </p>
                        <div class="mt-6">
                            <a href="{{ route('katalog.index') }}" {{-- Ganti ke route marketplace --}}
                               class="inline-flex items-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700">
                                <i class="fas fa-shopping-cart -ml-1 mr-2"></i>
                                Mulai Belanja
                            </a>
                        </div>
                    </div>
                </div>
            @endforelse
        </div>

        @if ($pesanans->hasPages())
            <div class="mt-6">
                {{ $pesanans->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
