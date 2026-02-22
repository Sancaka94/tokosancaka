@extends('layouts.marketplace')

@section('title', 'Keranjang Belanja')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <h2 class="text-2xl font-black text-gray-900 mb-8">Keranjang Belanja</h2>

    <template x-if="cart.length === 0">
        <div class="text-center py-20 bg-white rounded-3xl shadow-sm border border-gray-100">
            <div class="bg-gray-100 w-24 h-24 rounded-full flex items-center justify-center mx-auto mb-6">
                <i data-lucide="shopping-cart" class="w-10 h-10 text-gray-400"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-800 mb-2">Keranjang masih kosong</h3>
            <p class="text-gray-500 mb-6">Yuk, cari barang menarik di toko kami!</p>
            <a href="{{ route('storefront.index', $subdomain) }}" class="bg-blue-600 text-white px-6 py-3 rounded-full font-bold shadow-lg hover:bg-blue-700 transition">Mulai Belanja</a>
        </div>
    </template>

    <template x-if="cart.length > 0">
        <div class="flex flex-col md:flex-row gap-8">
            <div class="md:w-2/3 space-y-4">
                <template x-for="item in cart" :key="item.id">
                    <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 flex gap-4 items-center">
                        <div class="w-20 h-20 bg-gray-100 rounded-xl overflow-hidden flex-shrink-0">
                            <template x-if="item.image">
                                <img :src="item.image" class="w-full h-full object-cover">
                            </template>
                            <template x-if="!item.image">
                                <div class="w-full h-full flex items-center justify-center"><i data-lucide="image" class="text-gray-300"></i></div>
                            </template>
                        </div>

                        <div class="flex-grow">
                            <h4 class="font-bold text-gray-800 line-clamp-1" x-text="item.name"></h4>
                            <p class="text-blue-600 font-bold mt-1" x-text="formatRupiah(item.price)"></p>
                        </div>

                        <div class="flex items-center gap-3">
                            <button @click="updateQty(item.id, -1)" class="w-8 h-8 rounded-full bg-gray-100 hover:bg-gray-200 flex items-center justify-center text-gray-600 transition"><i data-lucide="minus" class="w-4 h-4"></i></button>
                            <span class="font-bold w-6 text-center" x-text="item.qty"></span>
                            <button @click="updateQty(item.id, 1)" class="w-8 h-8 rounded-full bg-blue-50 text-blue-600 hover:bg-blue-100 flex items-center justify-center transition"><i data-lucide="plus" class="w-4 h-4"></i></button>
                        </div>
                    </div>
                </template>
            </div>

            <div class="md:w-1/3">
                <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100 sticky top-24">
                    <h3 class="font-bold text-gray-900 mb-4 border-b pb-4">Ringkasan Belanja</h3>
                    <div class="flex justify-between mb-2 text-sm text-gray-600">
                        <span>Total Barang</span>
                        <span x-text="cartCount + ' Barang'"></span>
                    </div>
                    <div class="flex justify-between font-black text-lg text-gray-900 border-t pt-4 mt-4">
                        <span>Total Harga</span>
                        <span x-text="formatRupiah(cartTotal)"></span>
                    </div>
                    <a href="{{ route('storefront.checkout', $subdomain) }}" class="block w-full bg-blue-600 text-white text-center py-3 rounded-xl font-bold mt-6 hover:bg-blue-700 shadow-lg transition">Lanjut Checkout</a>
                </div>
            </div>
        </div>
    </template>
</div>
@endsection
