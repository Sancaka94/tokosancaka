@extends('layouts.marketplace')

@section('title', 'Keranjang Belanja - Sancaka Marketplace')

@push('styles')
    {{-- Memuat pustaka yang dibutuhkan --}}
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* Style tambahan untuk tampilan yang lebih baik */
        body { font-family: 'Inter', sans-serif; }
        input[type=number]::-webkit-inner-spin-button,
        input[type=number]::-webkit-outer-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
        input[type=number] { -moz-appearance: textfield; }
    </style>
@endpush

@section('content')
<div class="bg-gray-100">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <h1 class="text-3xl font-extrabold text-gray-900 mb-8">Keranjang Belanja Anda</h1>

        {{-- Cek apakah keranjang ada isinya --}}
        @if(session('cart') && count(session('cart')) > 0)
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                
                <!-- Daftar Item Keranjang (Kolom Kiri) -->
                <div class="lg:col-span-2 bg-white rounded-xl shadow-md">
                    <ul role="list" class="divide-y divide-gray-200 p-4 sm:p-6">
                        @php $total = 0; @endphp
                        @foreach($cart as $id => $details)
                            @php $total += $details['price'] * $details['quantity']; @endphp
                            <li class="flex py-6">
                                <div class="h-24 w-24 flex-shrink-0 overflow-hidden rounded-md border border-gray-200">
                                    <img src="{{ url('storage/' . $details['image_url']) }}" alt="{{ $details['name'] }}" class="h-full w-full object-cover object-center" onerror="this.onerror=null;this.src='https://placehold.co/100x100?text=N/A';">
                                </div>

                                <div class="ml-4 flex flex-1 flex-col">
                                    <div>
                                        <div class="flex justify-between text-base font-medium text-gray-900">
                                            <h3>
                                                {{-- Link kembali ke halaman produk --}}
                                                <a href="{{ route('products.show', $details['slug']) }}">{{ $details['name'] }}</a>
                                            </h3>
                                            <p class="ml-4">Rp{{ number_format($details['price'] * $details['quantity'], 0, ',', '.') }}</p>
                                        </div>
                                        <p class="mt-1 text-sm text-gray-500">Rp{{ number_format($details['price'], 0, ',', '.') }} / item</p>
                                    </div>
                                    <div class="flex flex-1 items-end justify-between text-sm mt-4">
                                        {{-- Form untuk memperbarui kuantitas --}}
                                        <form action="{{ route('cart.update') }}" method="POST" class="flex items-center">
                                            @csrf
                                            <input type="hidden" name="product_id" value="{{ $id }}">
                                            <input type="number" name="quantity" value="{{ $details['quantity'] }}" class="w-16 text-center border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                            <button type="submit" class="ml-2 text-xs font-semibold text-blue-600 hover:text-blue-800">Update</button>
                                        </form>

                                        {{-- Tombol untuk menghapus item --}}
                                        <div class="flex">
                                            <form action="{{ route('cart.remove') }}" method="POST">
                                                @csrf
                                                <input type="hidden" name="product_id" value="{{ $id }}">
                                                <button type="submit" class="font-medium text-red-600 hover:text-red-800">Hapus</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
                
                @if (session('error'))
                <div style="padding: 15px; background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; border-radius: 8px; margin: 20px;">
                <strong>Error:</strong> {{ session('error') }}
                </div>
                @endif

                <!-- Ringkasan Pesanan (Kolom Kanan) -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-xl shadow-md p-6 sticky top-24">
                        <h2 class="text-lg font-medium text-gray-900">Ringkasan Pesanan</h2>
                        <div class="mt-6 space-y-4">
                            <div class="flex items-center justify-between">
                                <dt class="text-sm text-gray-600">Subtotal</dt>
                                <dd class="text-sm font-medium text-gray-900">Rp{{ number_format($total, 0, ',', '.') }}</dd>
                            </div>
                            <div class="flex items-center justify-between border-t border-gray-200 pt-4">
                                <dt class="text-base font-medium text-gray-900">Total Pesanan</dt>
                                <dd class="text-base font-medium text-gray-900">Rp{{ number_format($total, 0, ',', '.') }}</dd>
                            </div>
                        </div>
                        <div class="mt-6">
                            {{-- ✅ PERBAIKAN: Mengarahkan tombol ke route checkout.index --}}
                            <a href="{{ route('checkout.index') }}" class="flex w-full items-center justify-center rounded-md border border-transparent bg-blue-600 px-6 py-3 text-base font-medium text-white shadow-sm hover:bg-blue-700">
                                Lanjut ke Checkout
                            </a>
                        </div>
                        <div class="mt-6 flex justify-center text-center text-sm text-gray-500">
                            <p>
                                atau
                                <a href="{{ route('etalase.index') }}" class="font-medium text-blue-600 hover:text-blue-500">
                                    Lanjut Belanja
                                    <span aria-hidden="true"> &rarr;</span>
                                </a>
                            </p>
                        </div>
                    </div>
                </div>

            </div>
        @else
            {{-- Tampilan jika keranjang kosong --}}
            <div class="text-center bg-white p-12 rounded-xl shadow-md">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path vector-effect="non-scaling-stroke" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                <h3 class="mt-2 text-lg font-medium text-gray-900">Keranjang Anda Kosong</h3>
                <p class="mt-1 text-sm text-gray-500">Ayo cari produk menarik dan tambahkan ke keranjang!</p>
                <div class="mt-6">
                    <a href="{{ route('etalase.index') }}" class="inline-flex items-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        Mulai Belanja
                    </a>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
