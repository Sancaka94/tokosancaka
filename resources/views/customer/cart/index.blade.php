@extends('layouts.customer')

@section('title', 'Keranjang Belanja Anda')

@section('content')
<div class="container mx-auto py-10 px-4">
    <h1 class="text-3xl font-bold text-gray-800 mb-6">Keranjang Belanja Anda</h1>

    @if(!empty($cart))
        <div class="flex flex-col lg:flex-row gap-8">
            
            <!-- Daftar Item Keranjang -->
            <div class="w-full lg:w-2/3">
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <table class="w-full text-left">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="p-4 font-semibold text-sm text-gray-600 uppercase">Produk</th>
                                <th class="p-4 font-semibold text-sm text-gray-600 uppercase">Harga</th>
                                <th class="p-4 font-semibold text-sm text-gray-600 uppercase">Kuantitas</th>
                                <th class="p-4 font-semibold text-sm text-gray-600 uppercase">Subtotal</th>
                                <th class="p-4 font-semibold text-sm text-gray-600 uppercase"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $total = 0 @endphp
                            @foreach($cart as $id => $details)
                                @php $total += $details['price'] * $details['quantity'] @endphp
                                <tr class="border-b">
                                    <td class="p-4 flex items-center gap-4">
                                        <img src="{{ $details['image_url'] ? asset($details['image_url']) : 'https://placehold.co/80' }}" alt="{{ $details['name'] }}" class="w-16 h-16 object-cover rounded-md">
                                        <div>
                                            <p class="font-semibold text-gray-800">{{ $details['name'] }}</p>
                                        </div>
                                    </td>
                                    <td class="p-4 text-gray-700">Rp{{ number_format($details['price']) }}</td>
                                    <td class="p-4">
                                        <input type="number" value="{{ $details['quantity'] }}" class="w-20 text-center border rounded-md p-1">
                                    </td>
                                    <td class="p-4 text-gray-800 font-semibold">Rp{{ number_format($details['price'] * $details['quantity']) }}</td>
                                    <td class="p-4">
                                        <button class="text-red-500 hover:text-red-700">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Ringkasan Pesanan -->
            <div class="w-full lg:w-1/3">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-bold text-gray-800 border-b pb-4 mb-4">Ringkasan Pesanan</h2>
                    <div class="flex justify-between mb-2">
                        <span class="text-gray-600">Subtotal</span>
                        <span class="font-semibold text-gray-800">Rp{{ number_format($total) }}</span>
                    </div>
                    <div class="flex justify-between mb-4">
                        <span class="text-gray-600">Ongkos Kirim</span>
                        <span class="font-semibold text-gray-800">Rp0</span>
                    </div>
                    <div class="border-t pt-4 flex justify-between items-center">
                        <span class="text-lg font-bold text-gray-800">Total</span>
                        <span class="text-xl font-bold text-red-600">Rp{{ number_format($total) }}</span>
                    </div>
                    <a href="{{ route('customer.checkout.index') }}"
                        class="block w-full mt-6 bg-red-600 text-white font-bold py-3 rounded-lg text-center hover:bg-red-700 transition-colors">
                            Lanjutkan ke Checkout
                    </a>

                    <a href="{{ route('customer.marketplace.index') }}"
                        class="block w-full mt-6 bg-green-600 text-white font-bold py-3 rounded-lg text-center hover:bg-green-700 transition-colors">
                            Lanjutkan Belanja
                    </a>

                </div>
            </div>

        </div>
    @else
        <div class="text-center py-20 bg-white rounded-lg shadow-md">
            <i class="fas fa-shopping-cart text-6xl text-gray-300 mb-4"></i>
            <h2 class="text-2xl font-bold text-gray-700 mb-2">Keranjang Anda Kosong</h2>
            <p class="text-gray-500 mb-6">Sepertinya Anda belum menambahkan produk apapun ke keranjang.</p>
            <a href="{{ route('katalog.index') }}" class="bg-indigo-600 text-white font-bold py-3 px-6 rounded-lg hover:bg-indigo-700 transition-colors">
                Mulai Belanja
            </a>
        </div>
    @endif
</div>
@endsection
