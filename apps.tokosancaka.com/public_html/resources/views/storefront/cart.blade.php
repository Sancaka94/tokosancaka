@extends('layouts.marketplace')

@section('title', 'Keranjang Belanja')

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10" x-data="cartPage()">
    <h2 class="text-3xl font-black text-gray-900 mb-8">Keranjang Belanja</h2>

    {{-- KONDISI 1: JIKA KOSONG --}}
    <div x-show="cart.length === 0" x-cloak class="text-center py-20 bg-white rounded-3xl shadow-sm border border-gray-100">
        <div class="bg-gray-50 w-32 h-32 rounded-full flex items-center justify-center mx-auto mb-6">
            <i data-lucide="shopping-cart" class="w-12 h-12 text-gray-300"></i>
        </div>
        <h3 class="text-2xl font-bold text-gray-800 mb-2">Keranjang Anda Kosong</h3>
        <p class="text-gray-50 mb-8">Yuk, temukan produk menarik di toko kami!</p>
        <a href="{{ route('storefront.index', $subdomain) }}" class="bg-blue-600 text-white px-8 py-4 rounded-xl font-bold shadow-lg hover:bg-blue-700 hover:-translate-y-1 transition transform inline-block">Mulai Belanja Sekarang</a>
    </div>

    {{-- KONDISI 2: JIKA ADA ISINYA --}}
    <div x-show="cart.length > 0" x-cloak class="flex flex-col lg:flex-row gap-8">

        {{-- LIST PRODUK --}}
        <div class="lg:w-2/3 space-y-4">
            <template x-for="item in cart" :key="item.unique_id">
                <div class="bg-white p-4 md:p-6 rounded-3xl shadow-sm border border-gray-100 flex flex-col md:flex-row gap-4 md:gap-6 items-start md:items-center hover:shadow-md transition">

                    {{-- Foto Produk --}}
                    <div class="w-24 h-24 bg-gray-100 rounded-2xl overflow-hidden flex-shrink-0 relative border border-gray-100">
                        <template x-if="item.image">
                            <img :src="item.image" class="w-full h-full object-cover">
                        </template>
                        <template x-if="!item.image">
                            <div class="w-full h-full flex items-center justify-center">
                                <i data-lucide="image" class="text-gray-300"></i>
                            </div>
                        </template>
                    </div>

                    {{-- Detail Produk --}}
                    <div class="flex-grow w-full">
                        <div class="flex flex-wrap gap-1 mb-2">
                            {{-- BADGE DINAMIS: CASHBACK EXTRA --}}
                            <template x-if="item.is_cashback_extra">
                                <span class="text-[8px] font-bold text-red-500 border border-red-500 px-1.5 py-0.5 rounded-sm bg-white uppercase">Cashback Xtra</span>
                            </template>
                            {{-- BADGE DINAMIS: GRATIS ONGKIR --}}
                            <template x-if="item.is_free_ongkir">
                                <span class="text-[8px] font-bold text-teal-600 border border-teal-500 px-1.5 py-0.5 rounded-sm bg-teal-50 uppercase flex items-center gap-0.5">
                                    <i data-lucide="truck" class="w-2 h-2"></i> Gratis Ongkir
                                </span>
                            </template>
                        </div>

                        <h4 class="font-bold text-gray-900 text-lg line-clamp-2" x-text="item.name"></h4>
                        <p class="text-blue-600 font-black mt-1" x-text="formatRupiah(item.price)"></p>
                        <p class="text-[10px] text-gray-400" x-text="(item.weight || 1000) + ' gram'"></p>
                    </div>

                    {{-- Kontrol Quantity --}}
                    <div class="flex items-center gap-2 md:gap-3 bg-gray-50 p-2 rounded-2xl w-full md:w-auto justify-between md:justify-center border border-gray-100">
                        <button @click="updateQty(item.unique_id, -1)" class="w-10 h-10 rounded-xl bg-white text-gray-600 hover:text-blue-600 hover:shadow shadow-sm flex items-center justify-center transition border border-gray-200 active:scale-95">
                            <i data-lucide="minus" class="w-4 h-4"></i>
                        </button>

                        <span class="font-black text-lg w-8 text-center text-gray-800" x-text="item.qty"></span>

                        <button @click="updateQty(item.unique_id, 1)" class="w-10 h-10 rounded-xl bg-blue-600 text-white hover:bg-blue-700 hover:shadow shadow-sm flex items-center justify-center transition border border-blue-600 active:scale-95">
                            <i data-lucide="plus" class="w-4 h-4"></i>
                        </button>

                        <button @click="removeItem(item.unique_id)" class="ml-2 w-10 h-10 rounded-xl bg-red-50 text-red-500 hover:bg-red-500 hover:text-white flex items-center justify-center transition border border-red-100 active:scale-95">
                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                        </button>
                    </div>
                </div>
            </template>
        </div>

        {{-- RINGKASAN BELANJA --}}
        <div class="lg:w-1/3">
            <div class="bg-white p-6 md:p-8 rounded-3xl shadow-xl shadow-blue-900/5 border border-gray-100 sticky top-24">
                <h3 class="font-black text-gray-900 mb-6 flex items-center gap-2">
                    <i data-lucide="file-text" class="text-blue-600 w-5 h-5"></i>
                    Ringkasan Belanja
                </h3>

                <div class="space-y-4 mb-6 text-sm text-gray-600">
                    <div class="flex justify-between border-b border-gray-100 pb-4">
                        <span>Total Barang</span>
                        <span class="font-bold text-gray-800" x-text="totalItems + ' Barang'"></span>
                    </div>
                    <div class="flex justify-between items-end">
                        <span>Total Harga</span>
                        <span class="font-black text-2xl text-gray-900" x-text="formatRupiah(cartTotal)"></span>
                    </div>
                </div>

                <a href="{{ route('storefront.checkout', $subdomain) }}" class="w-full bg-blue-600 text-white text-center py-4 rounded-xl font-bold hover:bg-blue-700 hover:shadow-lg hover:-translate-y-1 transition transform flex justify-center items-center gap-2">
                    Lanjut Checkout
                    <i data-lucide="arrow-right" class="w-5 h-5"></i>
                </a>
            </div>
        </div>

    </div>
</div>

<script>
    function cartPage() {
        return {
            cart: [],
            storageKey: 'sancaka_cart_{{ $tenant->id }}',

            init() {
                const savedCart = localStorage.getItem(this.storageKey);
                if (savedCart) {
                    this.cart = JSON.parse(savedCart).map(item => ({
                        ...item,
                        // Fix NaN: Pastikan price dan qty selalu angka
                        price: parseFloat(item.price) || 0,
                        qty: parseInt(item.qty) || 1
                    }));
                }
            },

            get totalItems() {
                return this.cart.reduce((sum, item) => sum + item.qty, 0);
            },

            get cartTotal() {
                return this.cart.reduce((sum, item) => sum + (item.price * item.qty), 0);
            },

            updateQty(uniqueId, amount) {
                const index = this.cart.findIndex(i => i.unique_id === uniqueId);
                if (index !== -1) {
                    this.cart[index].qty += amount;
                    if (this.cart[index].qty <= 0) {
                        this.removeItem(uniqueId);
                    } else {
                        this.saveCart();
                    }
                }
            },

            removeItem(uniqueId) {
                this.cart = this.cart.filter(i => i.unique_id !== uniqueId);
                this.saveCart();
                // Update counter header jika ada fungsi dispatch
                window.dispatchEvent(new CustomEvent('cart-updated'));
            },

            saveCart() {
                localStorage.setItem(this.storageKey, JSON.stringify(this.cart));
            },

            formatRupiah(number) {
                return new Intl.NumberFormat('id-ID', {
                    style: 'currency',
                    currency: 'IDR',
                    minimumFractionDigits: 0
                }).format(number);
            }
        }
    }
</script>
@endsection
