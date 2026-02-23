@extends('layouts.marketplace')

@section('title', 'Keranjang Belanja')

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <h2 class="text-3xl font-black text-gray-900 mb-8">Keranjang Belanja</h2>

    {{-- KONDISI 1: JIKA KOSONG (Gunakan x-show dan x-cloak) --}}
    <div x-show="cart.length === 0" x-cloak class="text-center py-20 bg-white rounded-3xl shadow-sm border border-gray-100">
        <div class="bg-gray-50 w-32 h-32 rounded-full flex items-center justify-center mx-auto mb-6">
            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-gray-400"><circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/></svg>
        </div>
        <h3 class="text-2xl font-bold text-gray-800 mb-2">Keranjang Anda Kosong</h3>
        <p class="text-gray-500 mb-8">Yuk, temukan produk menarik di toko kami!</p>
        <a href="{{ route('storefront.index', $subdomain) }}" class="bg-blue-600 text-white px-8 py-4 rounded-xl font-bold shadow-lg hover:bg-blue-700 hover:-translate-y-1 transition transform inline-block">Mulai Belanja Sekarang</a>
    </div>

    {{-- KONDISI 2: JIKA ADA ISINYA (Gunakan x-show dan x-cloak) --}}
    <div x-show="cart.length > 0" x-cloak class="flex flex-col lg:flex-row gap-8">

        {{-- LIST PRODUK --}}
        <div class="lg:w-2/3 space-y-4">
            {{-- Template x-for dibiarkan karena ini sudah benar strukturnya --}}
            <template x-for="item in cart" :key="item.unique_id">
                <div class="bg-white p-4 md:p-6 rounded-3xl shadow-sm border border-gray-100 flex flex-col md:flex-row gap-4 md:gap-6 items-start md:items-center hover:shadow-md transition">

                    <div class="w-24 h-24 bg-gray-100 rounded-2xl overflow-hidden flex-shrink-0 relative border border-gray-100">
                        <template x-if="item.image">
                            <img :src="item.image" class="w-full h-full object-cover">
                        </template>
                        <template x-if="!item.image">
                            <div class="w-full h-full flex items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-gray-300"><rect width="18" height="18" x="3" y="3" rx="2" ry="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>
                            </div>
                        </template>
                    </div>

                    <div class="flex-grow w-full">
                        <h4 class="font-bold text-gray-900 text-lg line-clamp-2" x-text="item.name"></h4>
                        <p class="text-blue-600 font-black mt-1" x-text="formatRupiah(item.price)"></p>
                    </div>

                    <div class="flex items-center gap-2 md:gap-3 bg-gray-50 p-2 rounded-2xl w-full md:w-auto justify-between md:justify-center border border-gray-100">
                        <button @click="updateQty(item.unique_id, -1)" class="w-10 h-10 rounded-xl bg-white text-gray-600 hover:text-blue-600 hover:shadow shadow-sm flex items-center justify-center transition border border-gray-200 active:scale-95">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/></svg>
                        </button>

                        <span class="font-black text-lg w-8 text-center text-gray-800" x-text="item.qty"></span>

                        <button @click="updateQty(item.unique_id, 1)" class="w-10 h-10 rounded-xl bg-blue-600 text-white hover:bg-blue-700 hover:shadow shadow-sm flex items-center justify-center transition border border-blue-600 active:scale-95">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                        </button>

                        <button @click="updateQty(item.unique_id, -item.qty)" class="ml-2 w-10 h-10 rounded-xl bg-red-50 text-red-500 hover:bg-red-500 hover:text-white flex items-center justify-center transition border border-red-100 active:scale-95" title="Hapus Produk">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/></svg>
                        </button>
                    </div>
                </div>
            </template>
        </div>

        {{-- RINGKASAN BELANJA --}}
        <div class="lg:w-1/3">
            <div class="bg-white p-6 md:p-8 rounded-3xl shadow-xl shadow-blue-900/5 border border-gray-100 sticky top-24">
                <h3 class="font-black text-gray-900 mb-6 flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-blue-600"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" x2="8" y1="13" y2="13"/><line x1="16" x2="8" y1="17" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                    Ringkasan Belanja
                </h3>

                <div class="space-y-4 mb-6 text-sm text-gray-600">
                    <div class="flex justify-between border-b border-gray-100 pb-4">
                        <span>Total Barang</span>
                        <span class="font-bold text-gray-800" x-text="cartCount + ' Barang'"></span>
                    </div>
                    <div class="flex justify-between items-end">
                        <span>Total Harga</span>
                        <span class="font-black text-2xl text-gray-900" x-text="formatRupiah(cartTotal)"></span>
                    </div>
                </div>

                <a href="{{ route('storefront.checkout', $subdomain) }}" class="w-full bg-blue-600 text-white text-center py-4 rounded-xl font-bold hover:bg-blue-700 hover:shadow-lg hover:-translate-y-1 transition transform flex justify-center items-center gap-2">
                    Lanjut Checkout
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                </a>
            </div>
        </div>

    </div>
</div>
@endsection
