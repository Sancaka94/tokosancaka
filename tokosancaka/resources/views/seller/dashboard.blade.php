@extends('layouts.customer')
@section('title', 'Dashboard Toko')

@push('styles')
<style>
    /* Style untuk ikon alamat */
    .address-icon {
        width: 1.25rem; /* 20px */
        height: 1.25rem; /* 20px */
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        color: white;
    }
    .icon-send { background-color: #3B82F6; } /* blue-500 */
    .icon-receive { background-color: #8B5CF6; } /* violet-500 */
</style>
@endpush

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

        {{-- Notifikasi --}}
        @if (session('success'))
            <div class="mb-4 p-4 bg-green-100 text-green-700 rounded-lg">
                {{ session('success') }}
            </div>
        @endif
        @if (session('info'))
            <div class="mb-4 p-4 bg-blue-100 text-blue-700 rounded-lg">
                {{ session('info') }}
            </div>
        @endif

        {{-- Header Sambutan --}}
        <div class="flex flex-col md:flex-row justify-between md:items-center gap-4 mb-6">
            <div>
                <h2 class="text-2xl font-semibold text-gray-800">
                    Selamat Datang, {{ Auth::user()->nama_lengkap ?? 'Penjual' }}!
                </h2>
                <p class="mt-1 text-gray-600">
                    Anda mengelola toko: <strong>{{ $store->store_name ?? $store->name ?? 'Toko Anda' }}</strong>
                </p>
            </div>
            <div class="flex-shrink-0 flex gap-2">
                 <a href="{{ route('seller.produk.create') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-500 active:bg-blue-700">
                    + Tambah Produk
                </a>
                 <a href="#" class="inline-flex items-center px-4 py-2 bg-gray-700 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-600 active:bg-gray-800">
                    Buat Pesanan Manual
                </a>
            </div>
        </div>

        {{-- Card Statistik --}}
        {{-- ====================================================== --}}
        {{-- PERBAIKAN: Grid diubah jadi 4 kolom dan kartu ditambah --}}
        {{-- ====================================================== --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            
            {{-- 1. Perlu Diproses (Pesanan Baru) --}}
            <div class="relative bg-yellow-500 text-white p-5 rounded-lg shadow-md overflow-hidden transform transition duration-300 hover:-translate-y-1 hover:shadow-xl">
                <div class="relative z-10">
                    <h3 class="text-3xl font-bold">{{ $stats['ordersProcessing'] ?? 0 }}</h3>
                    <p class="text-sm text-yellow-100">Pesanan Perlu Diproses</p>
                </div>
                <div class="absolute top-0 right-0 p-4 -mt-2 -mr-2 z-0 opacity-30">
                    <svg class="w-20 h-20 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                    </svg>
                </div>
                <a href="{{ route('seller.pesanan.marketplace.index', ['status' => 'processing']) }}" class="relative z-10 block mt-4 text-sm text-yellow-100 hover:text-white font-medium">
                    Lihat Pesanan &rarr;
                </a>
            </div>
            
            {{-- 2. Dalam Pengiriman --}}
            <div class="relative bg-blue-500 text-white p-5 rounded-lg shadow-md overflow-hidden transform transition duration-300 hover:-translate-y-1 hover:shadow-xl">
                <div class="relative z-10">
                    <h3 class="text-3xl font-bold">{{ $stats['ordersShipment'] ?? 0 }}</h3>
                    <p class="text-sm text-blue-100">Dalam Pengiriman</p>
                </div>
                <div class="absolute top-0 right-0 p-4 -mt-2 -mr-2 z-0 opacity-30">
                    <svg class="w-20 h-20 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z" />
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10l2 2h8a1 1 0 001-1z" />
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h2a1 1 0 001-1V6a1 1 0 00-1-1h-2v11z" />
                    </svg>
                </div>
                <a href="{{ route('seller.pesanan.marketplace.index', ['status' => 'shipment']) }}" class="relative z-10 block mt-4 text-sm text-blue-100 hover:text-white font-medium">
                    Lacak Pengiriman &rarr;
                </a>
            </div>

            {{-- 3. Pesanan Selesai (30 Hari) --}}
            <div class="relative bg-green-500 text-white p-5 rounded-lg shadow-md overflow-hidden transform transition duration-300 hover:-translate-y-1 hover:shadow-xl">
                <div class="relative z-10">
                    <h3 class="text-3xl font-bold">{{ $stats['ordersCompletedMonth'] ?? 0 }}</h3>
                    <p class="text-sm text-green-100">Pesanan Selesai (30 Hari)</p>
                </div>
                <div class="absolute top-0 right-0 p-4 -mt-2 -mr-2 z-0 opacity-30">
                     <svg class="w-20 h-20 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <a href="{{ route('seller.pesanan.marketplace.index', ['status' => 'completed']) }}" class="relative z-10 block mt-4 text-sm text-green-100 hover:text-white font-medium">
                    Lihat Riwayat &rarr;
                </a>
            </div>

            {{-- 4. Menunggu Pembayaran --}}
            <div class="relative bg-gray-400 text-white p-5 rounded-lg shadow-md overflow-hidden transform transition duration-300 hover:-translate-y-1 hover:shadow-xl">
                <div class="relative z-10">
                    <h3 class="text-3xl font-bold">{{ $stats['ordersPending'] ?? 0 }}</h3>
                    <p class="text-sm text-gray-100">Menunggu Pembayaran</p>
                </div>
                <div class="absolute top-0 right-0 p-4 -mt-2 -mr-2 z-0 opacity-30">
                     <svg class="w-20 h-20 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <a href="{{ route('seller.pesanan.marketplace.index', ['status' => 'pending']) }}" class="relative z-10 block mt-4 text-sm text-gray-100 hover:text-white font-medium">
                    Lihat Detail &rarr;
                </a>
            </div>
            
            {{-- 5. Pendapatan Hari Ini --}}
            <div class="relative bg-green-600 text-white p-5 rounded-lg shadow-md overflow-hidden transform transition duration-300 hover:-translate-y-1 hover:shadow-xl">
                <div class="relative z-10">
                    <h3 class="text-3xl font-bold">Rp {{ number_format($stats['revenueToday'] ?? 0) }}</h3>
                    <p class="text-sm text-green-100">Pendapatan Selesai (Hari Ini)</p>
                </div>
                <div class="absolute top-0 right-0 p-4 -mt-2 -mr-2 z-0 opacity-30">
                    <svg class="w-20 h-20 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8C9.79 8 8 9.79 8 12s1.79 4 4 4 4-1.79 4-4-1.79-4-4-4zm0 6c-1.11 0-2.08-.402-2.599-1M12 14c-2.21 0-4-1.79-4-4s1.79-4 4-4 4 1.79 4 4-1.79 4-4 4z"></path></svg>
                </div>
                <span class="relative z-10 block mt-4 text-sm text-green-200">
                    Dari pesanan selesai hari ini
                </span>
            </div>

            {{-- 6. Pesanan Masuk Hari Ini --}}
            <div class="relative bg-yellow-600 text-white p-5 rounded-lg shadow-md overflow-hidden transform transition duration-300 hover:-translate-y-1 hover:shadow-xl">
                <div class="relative z-10">
                    <h3 class="text-3xl font-bold">{{ $stats['ordersToday'] ?? 0 }}</h3>
                    <p class="text-sm text-yellow-100">Pesanan Masuk (Hari Ini)</p>
                </div>
                <div class="absolute top-0 right-0 p-4 -mt-2 -mr-2 z-0 opacity-30">
                    <svg class="w-20 h-20 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                </div>
                <a href="{{ route('seller.pesanan.marketplace.index') }}" class="relative z-10 block mt-4 text-sm text-yellow-100 hover:text-white font-medium">
                    Lihat Semua Pesanan &rarr;
                </a>
            </div>
            
            {{-- 7. Pesanan Manual (Baru) --}}
            <div class="relative bg-red-500 text-white p-5 rounded-lg shadow-md overflow-hidden transform transition duration-300 hover:-translate-y-1 hover:shadow-xl">
                <div class="relative z-10">
                    <h3 class="text-3xl font-bold">{{ $stats['newManualOrders'] ?? 0 }}</h3>
                    <p class="text-sm text-red-100">Pesanan Manual (Baru)</p>
                </div>
                <div class="absolute top-0 right-0 p-4 -mt-2 -mr-2 z-0 opacity-30">
                    <svg class="w-20 h-20 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                    </svg>
                </div>
                <a href="#" class="relative z-10 block mt-4 text-sm text-red-100 hover:text-white font-medium">
                    Lihat Pesanan Manual &rarr;
                </a>
            </div>

            {{-- 8. Total Produk Aktif --}}
            <div class="relative bg-teal-500 text-white p-5 rounded-lg shadow-md overflow-hidden transform transition duration-300 hover:-translate-y-1 hover:shadow-xl">
                <div class="relative z-10">
                    <h3 class="text-3xl font-bold">{{ $stats['totalActiveProducts'] ?? 0 }}</h3>
                    <p class="text-sm text-teal-100">Total Produk Aktif</p>
                </div>
                <div class="absolute top-0 right-0 p-4 -mt-2 -mr-2 z-0 opacity-30">
                    <svg class="w-20 h-20 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                    </svg>
                </div>
                <a href="{{ route('seller.produk.index') }}" class="relative z-10 block mt-4 text-sm text-teal-100 hover:text-white font-medium">
                    Lihat Produk &rarr;
                </a>
            </div>

        </div>
        {{-- ============================================= --}}
        {{-- AKHIR PERBAIKAN KARTU STATISTIK --}}
        {{-- ============================================= --}}


        {{-- ============================================= --}}
        {{-- BAGIAN GRAFIK STATISTIK (Perlu Penyesuaian) --}}
        {{-- ============================================= --}}
        <div class="mt-8 grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            {{-- Grafik Gelombang (Pendapatan 30 Hari) --}}
            <div class="lg:col-span-3 bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">
                    Grafik Pendapatan Selesai (30 Hari Terakhir)
                </h3>
                <div>
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>

            {{-- Grafik Persen (Status Pesanan) --}}
            <div class="lg:col-span-1 bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">
                    Ringkasan Status (30 Hari)
                </h3>
                <div class="h-64 flex items-center justify-center">
                    <canvas id="statusChart"></canvas>
                </div>
            </div>

            {{-- Grafik Cart (Tipe Pesanan) --}}
            <div class="lg:col-span-2 bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">
                    Pesanan Masuk (30 Hari)
                </h3>
                <div class="h-64 flex items-center justify-center">
                    <canvas id="orderTypeChart"></canvas>
                </div>
            </div>

        </div>
        {{-- =TAKE(5) --}}
        {{-- ============================================= --}}
        <div class="mt-8 bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 border-b border-gray-200 flex justify-between items-center">
                <h3 class="text-lg font-semibold text-gray-800">
                    5 Pesanan Marketplace Terbaru
                </h3>
                <a href="{{ route('seller.pesanan.marketplace.index') }}"
   class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-1 transition">
    Lihat Semua Pesanan &rarr;
</a>

            </div>
            
            {{-- Header Tabel (versi Tailwind) --}}
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
                @forelse ($recentMarketplaceOrders as $order)
                    <div class="p-6">
                        <div class="grid grid-cols-1 lg:grid-cols-12 gap-x-4 gap-y-6 text-sm">

                            {{-- NO --}}
                            <div class="lg:col-span-1">
                                <span class="lg:hidden font-bold text-gray-500">NO: </span>
                                <span class="text-gray-900 font-medium">{{ $loop->iteration }}</span>
                            </div>

                            {{-- TRANSAKSI --}}
                            <div class="lg:col-span-2 space-y-1">
                                <div class="font-bold text-blue-600 uppercase">{{ $order->payment_method }}</div>
                                <div class="text-gray-900 font-medium">{{ $order->invoice_number }}</div>
                                <div class="text-xs text-gray-500">{{ $order->created_at->format('d M Y H:i') }}</div>
                                <div class="text-xs text-gray-500">Dibuat oleh: {{ $order->store->name ?? 'Toko' }}</div>
                            </div>

                            {{-- ALAMAT --}}
                            <div class="lg:col-span-3 space-y-3">
                                {{-- Pengirim (Toko Anda) --}}
                                <div class="flex gap-3">
                                    <div class="address-icon icon-send">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path></svg>
                                    </div>
                                    <div>
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
                                @php
                                    $shippingParts = explode('-', $order->shipping_method);
                                    $courier = $shippingParts[1] ?? 'N/A';
                                    $service = $shippingParts[2] ?? 'N/A';
                                @endphp
                                <div class="font-medium text-gray-800 uppercase">{{ $courier }} - {{ $service }}</div>
                                <div class="font-semibold text-gray-900">Rp{{ number_format($order->shipping_cost) }}</div>
                                
                                @if(strtolower($order->payment_method) == 'cod')
                                    <div class="text-xs text-gray-600">Tagihan COD: Rp{{ number_format($order->total_amount) }}</div>
                                @elseif($order->cod_fee > 0)
                                    <div class="text-xs text-gray-600">Biaya COD: Rp{{ number_format($order->cod_fee) }}</div>
                                @endif
                                
                                <div class="text-xs text-gray-500 break-all">Resi: {{ $order->shipping_reference ?? '-' }}</div>
                                <div class="text-xs text-blue-600 font-medium">Pickup</div>
                            </div>

                            {{-- ISI PAKET --}}
                            <div class="lg:col-span-2 space-y-2">
                                @php
                                    $totalWeight = 0;
                                @endphp
                                @foreach($order->items as $item)
                                    @php
                                        $productWeight = $item->product->weight ?? 0;
                                        $totalWeight += $productWeight * $item->quantity;
                                    @endphp
                                    <div class="text-xs">
                                        <div class="font-medium text-gray-800 uppercase">{{ $item->product->name ?? 'Produk Dihapus' }} ({{$item->quantity}}x)</div>
                                        <div class="text-gray-600">Rp{{ number_format($item->price) }}</div>
                                    </div>
                                @endforeach
                                 <div class="text-xs text-gray-500 border-t pt-2 mt-2">
                                    <div>Berat: {{ number_format($totalWeight) }} gram</div>
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
                        Belum ada pesanan produk yang masuk.
                    </div>
                @endforelse
            </div>
            
        </div>
        {{-- ============================================= --}}
        {{-- AKHIR BAGIAN PESANAN TERBARU --}}
        {{-- ============================================= --}}

    </div>
</div>
@endsection

@push('scripts')
{{-- ============================================= --}}
{{-- SKRIP BARU: Import Chart.js dan menggambar grafik --}}
{{-- ============================================= --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Data dari Controller (sudah di-passing sebagai $stats)
        const dailyData = @json($stats['dailyRevenue'] ?? []);
        const statusData = @json($stats['orderStatusSummary'] ?? []);
        
        // ===================================
        // PERBAIKAN: Ambil data dari statistik baru
        // ===================================
        // Ambil data untuk grafik bar dari total 30 hari
        const statusSummary = @json($stats['orderStatusSummary'] ?? []);
        const processingCount = statusSummary.find(s => s.status === 'processing')?.count || 0;
        const shipmentCount = statusSummary.find(s => s.status === 'shipment')?.count || 0;
        const completedCount = statusSummary.find(s => s.status === 'completed')?.count || 0;
        const marketplaceOrders = processingCount + shipmentCount + completedCount;
        
        const manualOrders = {{ $stats['newManualOrders'] ?? 0 }};

        // ===================================
        // 1. Grafik Gelombang (Pendapatan)
        // ===================================
        if (document.getElementById('revenueChart')) {
            const ctxLine = document.getElementById('revenueChart').getContext('2d');
            
            // Buat gradient
            const gradient = ctxLine.createLinearGradient(0, 0, 0, 300);
            gradient.addColorStop(0, 'rgba(16, 185, 129, 0.7)'); // green-500
            gradient.addColorStop(1, 'rgba(255, 255, 255, 0)');

            new Chart(ctxLine, {
                type: 'line',
                data: {
                    labels: dailyData.map(item => item.date),
                    datasets: [{
                        label: 'Pendapatan',
                        data: dailyData.map(item => item.total),
                        borderColor: '#059669', // green-600
                        backgroundColor: gradient,
                        tension: 0.3,
                        fill: true,
                        pointBackgroundColor: '#059669'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return 'Rp ' + new Intl.NumberFormat('id-ID').format(value);
                                }
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        }

        // ===================================
        // 2. Grafik Persen (Status Pesanan)
        // ===================================
        if (document.getElementById('statusChart') && statusData.length > 0) {
            const ctxPie = document.getElementById('statusChart').getContext('2d');
            
            // Fungsi untuk mengubah 'processing' -> 'Processing'
            const capitalize = (s) => s.charAt(0).toUpperCase() + s.slice(1);

            new Chart(ctxPie, {
                type: 'doughnut',
                data: {
                    labels: statusData.map(item => capitalize(item.status)),
                    datasets: [{
                        label: 'Jumlah Pesanan',
                        data: statusData.map(item => item.count),
                        backgroundColor: [
                            'rgba(245, 158, 11, 0.8)', // yellow-500 (processing)
                            'rgba(59, 130, 246, 0.8)', // blue-500 (shipment)
                            'rgba(34, 197, 94, 0.8)',  // green-500 (completed)
                            'rgba(156, 163, 175, 0.8)',// gray-400 (pending)
                            'rgba(239, 68, 68, 0.8)',  // red-500 (canceled)
                            'rgba(168, 85, 247, 0.8)', // purple-500 (lainnya)
                        ],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                boxWidth: 12,
                                padding: 15
                            }
                        }
                    }
                }
            });
        }

        // ===================================
        // 3. Grafik Cart (Tipe Pesanan)
        // ===================================
        if (document.getElementById('orderTypeChart')) {
            const ctxBar = document.getElementById('orderTypeChart').getContext('2d');
            new Chart(ctxBar, {
                type: 'bar',
                data: {
                    labels: ['Pesanan Marketplace', 'Pesanan Manual'],
                    datasets: [{
                        label: 'Jumlah Pesanan (30 Hari)',
                        data: [marketplaceOrders, manualOrders],
                        backgroundColor: [
                            'rgba(96, 165, 250, 0.8)', // blue-400
                            'rgba(239, 68, 68, 0.8)'  // red-500
                        ],
                        borderRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1 // Pastikan angkanya bulat (1, 2, 3)
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        }
    });
</script>
@endpush