@extends('layouts.customer')

@section('title', 'Riwayat Transaksi - Sancaka Marketplace')

@section('content')
    {{-- Alpine.js untuk fungsionalitas modal --}}
    <script src="//unpkg.com/alpinejs" defer></script>

    {{-- 
        Inisialisasi Alpine.js
        - isModalOpen: Mengontrol visibilitas modal detail produk.
        - currentOrderItems: Menyimpan data item dari pesanan yang diklik.
    --}}
    <div class="container mx-auto p-4 sm:p-8 max-w-5xl" x-data="{ isModalOpen: false, currentOrderItems: [] }">
        
        <!-- Header Halaman -->
        <header class="mb-8">
            <h1 class="text-3xl font-bold text-gray-800">Riwayat Transaksi Saya</h1>
            <p class="text-gray-500 mt-2">Lihat semua riwayat pembelian dan pembayaran Anda di sini.</p>
        </header>

        <!-- Card Riwayat Transaksi -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <div class="p-6 border-b">
                <h2 class="text-xl font-semibold text-gray-700">Semua Pesanan</h2>
            </div>

            <!-- Kontainer Tabel agar Responsif -->
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm text-left text-gray-500">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3">Detail Pesanan</th>
                            {{-- ✅ DITAMBAHKAN: Kolom baru untuk metode pengiriman --}}
                            <th scope="col" class="px-6 py-3">Metode Pengiriman</th>
                            {{-- ✅ DITAMBAHKAN: Kolom baru untuk metode pembayaran --}}
                            <th scope="col" class="px-6 py-3">Metode Pembayaran</th>
                            <th scope="col" class="px-6 py-3">Total</th>
                            <th scope="col" class="px-6 py-3 text-center">Status</th>
                            <th scope="col" class="px-6 py-3 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($orders as $order)
                            <tr class="bg-white border-b hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <div class="font-medium text-gray-900">#{{ $order->invoice_number }}</div>
                                    <div class="text-xs text-gray-500">{{ \Carbon\Carbon::parse($order->created_at)->format('d F Y') }}</div>
                                </td>
                                {{-- ✅ DITAMBAHKAN: Data untuk kolom pengiriman --}}
                                <td class="px-6 py-4">
                                    {{ $order->shipping_method }}
                                </td>
                                {{-- ✅ DITAMBAHKAN: Data untuk kolom pembayaran --}}
                                <td class="px-6 py-4">
                                    {{ $order->payment_method }}
                                </td>
                                <td class="px-6 py-4 font-medium text-gray-800">
                                    Rp {{ number_format($order->total_amount, 0, ',', '.') }}
                                </td>
                                <td class="px-6 py-4 text-center">
                                    @php
                                        $status = strtolower($order->status);
                                        $badgeClass = match($status) {
                                            'paid'    => 'bg-green-100 text-green-800',
                                            'pending' => 'bg-yellow-100 text-yellow-800',
                                            'failed'  => 'bg-red-100 text-red-800',
                                            'expired' => 'bg-gray-100 text-gray-800',
                                            default   => 'bg-blue-100 text-blue-800'
                                        };
                                    @endphp
                                    <span class="px-3 py-1 text-xs font-medium rounded-full {{ $badgeClass }}">{{ ucfirst($status) }}</span>
                                </td>
                                <td class="px-6 py-4 text-center">
    <div x-data="{ isModalOpen: false }" class="flex items-center justify-center space-x-2">
        {{-- Bayar / Invoice --}}
        @if($status === 'pending')
            <a href="{{ route('checkout.invoice', $order->invoice_number) }}" class="px-3 py-1 text-xs font-medium text-white bg-orange-500 rounded-md hover:bg-orange-600 transition-colors">Bayar</a>
        @else
            <a href="{{ route('checkout.invoice', $order->invoice_number) }}" class="px-3 py-1 text-xs font-medium text-white bg-blue-500 rounded-md hover:bg-blue-600 transition-colors">Invoice</a>
        @endif

        {{-- Tombol Detail --}}
        <button @click="isModalOpen = true" class="px-3 py-1 text-xs font-medium text-white bg-gray-700 rounded-md hover:bg-gray-800 transition-colors">Detail</button>

        {{-- Modal Detail --}}
        <div 
            x-show="isModalOpen" 
            x-transition
            class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50"
        >
            <div class="bg-white w-11/12 max-w-2xl p-6 rounded-lg overflow-y-auto max-h-[80vh]">
                <h3 class="text-lg font-medium mb-4">Detail Order #{{ $order->invoice_number }}</h3>
                <table class="w-full text-left border">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="px-4 py-2">Produk</th>
                            <th class="px-4 py-2">Nama</th>
                             <th class="px-4 py-2">Kategori</th>
                              <th class="px-4 py-2">SKU</th>
                            <th class="px-4 py-2">Qty</th>
                            <th class="px-4 py-2">Harga</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($order->items as $item)
                            <tr class="border-b">
                                <td class="px-4 py-2">
                                    <img src="{{ asset('storage/'.$item->product->image_url) }}" alt="{{ $item->product->name }}" class="w-16 h-16 object-cover rounded">
                                </td>
                                <td class="px-4 py-2">{{ $item->product->name }}</td>
                                 <td class="px-4 py-2">{{ $item->product->category }}</td>
                                  <td class="px-4 py-2">{{ $item->product->sku }}</td>
                                <td class="px-4 py-2">{{ $item->quantity }}</td>
                                <td class="px-4 py-2">Rp {{ number_format($item->price, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="mt-4 text-right">
                    <button @click="isModalOpen = false" class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600 transition-colors">Tutup</button>
                </div>
            </div>
        </div>
    </div>
</td>


                                
                            </tr>
                        @empty
                            <tr>
                                {{-- ✅ DIPERBAIKI: Colspan disesuaikan menjadi 6 --}}
                                <td colspan="6" class="text-center py-10 text-gray-500">
                                    Anda belum memiliki riwayat transaksi.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination Links -->
            <div class="p-4 border-t">
                {{ $orders->links() }}
            </div>
        </div>





@endsection
