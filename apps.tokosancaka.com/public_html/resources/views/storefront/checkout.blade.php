@extends('layouts.marketplace')

@section('title', 'Checkout Pesanan')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10" x-data="checkoutData()" x-init="initCheckout()">
    <h2 class="text-3xl font-black text-gray-900 mb-8">Checkout Pesanan</h2>

    <form action="{{ route('storefront.process', $subdomain) }}" method="POST" id="checkoutForm" @submit.prevent="submitOrder">
        @csrf
        <input type="hidden" name="items" :value="JSON.stringify(cartItems)">
        <input type="hidden" name="total" :value="subtotal">
        <input type="hidden" name="shipping_cost" :value="shippingCost">
        <input type="hidden" name="courier_name" :value="courierName">
        <input type="hidden" name="courier_code" :value="courierCode">
        <input type="hidden" name="service_type" :value="serviceType">
        <input type="hidden" name="destination_district_id" :value="districtId">
        <input type="hidden" name="destination_subdistrict_id" :value="subdistrictId">
        <input type="hidden" name="coupon" :value="appliedCoupon">

        <div class="flex flex-col lg:flex-row gap-8">

            <div class="lg:w-2/3 space-y-6">

                <div class="bg-white p-6 md:p-8 rounded-3xl shadow-sm border border-gray-100">
                    <h3 class="font-bold text-gray-900 mb-4 flex items-center gap-2 text-lg">
                        <i data-lucide="user" class="text-blue-600"></i> Informasi Kontak
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-2">Nama Lengkap</label>
                            <input type="text" name="customer_name" required x-model="customerName" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white transition">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-2">No. WhatsApp</label>
                            <input type="text" name="customer_phone" required x-model="customerPhone" placeholder="08xxx..." class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white transition">
                        </div>
                    </div>
                </div>

                <div class="bg-white p-6 md:p-8 rounded-3xl shadow-sm border border-gray-100">
                    <h3 class="font-bold text-gray-900 mb-4 flex items-center gap-2 text-lg">
                        <i data-lucide="truck" class="text-blue-600"></i> Pengiriman
                    </h3>

                    <div class="flex gap-4 mb-6">
                        <label class="flex-1 cursor-pointer">
                            <input type="radio" name="delivery_type" value="shipping" x-model="deliveryType" @change="shippingCost = 0; courierName = '';" class="peer sr-only">
                            <div class="p-4 border-2 border-gray-200 rounded-xl peer-checked:border-blue-600 peer-checked:bg-blue-50 text-center transition">
                                <i data-lucide="map-pin" class="w-5 h-5 mx-auto mb-1 peer-checked:text-blue-600 text-gray-400"></i>
                                <span class="font-bold block text-sm text-gray-700 peer-checked:text-blue-700">Kirim ke Alamat</span>
                            </div>
                        </label>
                        <label class="flex-1 cursor-pointer">
                            <input type="radio" name="delivery_type" value="pickup" x-model="deliveryType" @change="shippingCost = 0; courierName = 'Ambil Sendiri';" class="peer sr-only">
                            <div class="p-4 border-2 border-gray-200 rounded-xl peer-checked:border-blue-600 peer-checked:bg-blue-50 text-center transition">
                                <i data-lucide="store" class="w-5 h-5 mx-auto mb-1 peer-checked:text-blue-600 text-gray-400"></i>
                                <span class="font-bold block text-sm text-gray-700 peer-checked:text-blue-700">Ambil di Toko</span>
                            </div>
                        </label>
                    </div>

                    <div x-show="deliveryType === 'shipping'" x-transition x-cloak class="space-y-4 border-t border-dashed border-gray-200 pt-6">

                        <div class="relative">
                            <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-2">Kecamatan / Kelurahan Tujuan <span class="text-red-500">*</span></label>
                            <input type="text" x-model="locationSearch" @input.debounce.500ms="searchLocation" placeholder="Ketik nama kecamatan..." class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white transition">
                            <i x-show="isSearchingLoc" class="fas fa-spinner fa-spin absolute right-4 top-10 text-blue-500"></i>

                            <ul x-show="locationResults.length > 0" class="absolute z-50 w-full bg-white border border-gray-200 rounded-xl shadow-xl mt-1 max-h-48 overflow-y-auto">
                                <template x-for="loc in locationResults" :key="loc.id">
                                    <li @click="selectLocation(loc)" class="px-4 py-3 hover:bg-blue-50 cursor-pointer border-b border-gray-50 last:border-0 text-sm">
                                        <div class="font-bold text-gray-800" x-text="loc.text || loc.name || (loc.kecamatan + ', ' + loc.kabupaten)"></div>
                                    </li>
                                </template>
                            </ul>
                        </div>

                        <div x-show="destinationText" class="p-3 bg-green-50 border border-green-200 rounded-xl text-xs text-green-800 font-medium flex items-center justify-between">
                            <span x-text="destinationText"></span>
                            <button type="button" @click="resetLocation()" class="text-green-600 hover:text-green-900"><i class="fas fa-times"></i></button>
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-2">Alamat Detail (Jalan, RT/RW, No. Rumah) <span class="text-red-500">*</span></label>
                            <textarea name="destination_text" rows="2" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white transition"></textarea>
                        </div>

                        <div x-show="shippingRates.length > 0" class="pt-4 border-t border-gray-100">
                            <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-3">Pilih Layanan Kurir</label>
                            <div class="space-y-3 max-h-60 overflow-y-auto pr-2">
                                <template x-for="(rate, index) in shippingRates" :key="index">
                                    <label class="flex items-center justify-between p-4 border-2 rounded-xl cursor-pointer transition hover:border-blue-300"
                                           :class="selectedRate === index ? 'border-blue-600 bg-blue-50' : 'border-gray-200 bg-white'">
                                        <input type="radio" name="courier_selection" :value="index" x-model="selectedRate" @change="applyShippingRate(rate)" class="sr-only">
                                        <div class="flex items-center gap-4">
                                            <div class="w-12 h-8 bg-white border border-gray-200 rounded flex items-center justify-center overflow-hidden">
                                                <img :src="rate.logo" :alt="rate.name" class="w-full h-full object-contain p-1" x-show="rate.logo">
                                                <i class="fas fa-truck text-gray-300" x-show="!rate.logo"></i>
                                            </div>
                                            <div>
                                                <h4 class="font-bold text-gray-900 text-sm" x-text="rate.name"></h4>
                                                <p class="text-[11px] text-gray-500 uppercase" x-text="rate.service + ' • Est: ' + rate.etd + ' hari'"></p>
                                            </div>
                                        </div>
                                        <span class="font-black text-blue-600" x-text="formatRupiah(rate.cost)"></span>
                                    </label>
                                </template>
                            </div>
                        </div>

                        <div x-show="isLoadingRates" class="p-6 text-center text-blue-600 font-bold bg-blue-50 rounded-xl animate-pulse">
                            <i class="fas fa-spinner fa-spin mr-2"></i> Mencari kurir terbaik...
                        </div>
                    </div>
                </div>

                <div class="bg-white p-6 md:p-8 rounded-3xl shadow-sm border border-gray-100">
                    <h3 class="font-bold text-gray-900 mb-4 flex items-center gap-2 text-lg">
                        <i data-lucide="wallet" class="text-blue-600"></i> Metode Pembayaran
                    </h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <label class="cursor-pointer">
                            <input type="radio" name="payment_method" value="dana_sdk" required class="peer sr-only">
                            <div class="p-4 border-2 border-gray-200 rounded-xl peer-checked:border-blue-600 peer-checked:bg-blue-50 flex items-center gap-3 transition">
                                <img src="https://upload.wikimedia.org/wikipedia/commons/7/72/Logo_dana_blue.svg" class="h-6">
                                <div>
                                    <span class="font-bold text-sm block">DANA Otomatis</span>
                                    <span class="text-[10px] text-gray-500">Saldo / Kartu Bank</span>
                                </div>
                            </div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="payment_method" value="tripay" class="peer sr-only">
                            <div class="p-4 border-2 border-gray-200 rounded-xl peer-checked:border-blue-600 peer-checked:bg-blue-50 flex items-center gap-3 transition">
                                <i class="fas fa-qrcode text-xl text-gray-700"></i>
                                <div>
                                    <span class="font-bold text-sm block">QRIS / Virtual Account</span>
                                    <span class="text-[10px] text-gray-500">BCA, BNI, Mandiri, dll</span>
                                </div>
                            </div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="payment_method" value="doku" class="peer sr-only">
                            <div class="p-4 border-2 border-gray-200 rounded-xl peer-checked:border-blue-600 peer-checked:bg-blue-50 flex items-center gap-3 transition">
                                <i class="fas fa-credit-card text-xl text-gray-700"></i>
                                <div>
                                    <span class="font-bold text-sm block">DOKU Payment</span>
                                    <span class="text-[10px] text-gray-500">Credit Card & e-Wallet</span>
                                </div>
                            </div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="payment_method" value="pay_later" class="peer sr-only">
                            <div class="p-4 border-2 border-gray-200 rounded-xl peer-checked:border-blue-600 peer-checked:bg-blue-50 flex items-center gap-3 transition">
                                <i class="fas fa-hand-holding-usd text-xl text-gray-700"></i>
                                <div>
                                    <span class="font-bold text-sm block">Bayar Nanti / COD</span>
                                    <span class="text-[10px] text-gray-500">Bayar saat terima barang</span>
                                </div>
                            </div>
                        </label>
                    </div>
                </div>

            </div>

            <div class="lg:w-1/3">
                <div class="sticky top-24 space-y-6">

                    <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100">
                        <h3 class="font-bold text-gray-900 mb-3 text-sm">Punya Kupon / Kode Affiliate?</h3>
                        <div class="flex gap-2">
                            <input type="text" x-model="couponInput" placeholder="Masukkan kode..." class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-2 outline-none focus:ring-2 focus:ring-blue-500 text-sm uppercase">
                            <button type="button" @click="checkCoupon" :disabled="isCheckingCoupon || !couponInput" class="bg-gray-900 text-white px-4 py-2 rounded-xl font-bold text-sm hover:bg-blue-600 disabled:opacity-50 transition">
                                <span x-show="!isCheckingCoupon">Pakai</span>
                                <i x-show="isCheckingCoupon" class="fas fa-spinner fa-spin"></i>
                            </button>
                        </div>
                        <p x-show="couponMessage" :class="couponStatus === 'success' ? 'text-green-600' : 'text-red-500'" class="text-xs font-bold mt-2" x-text="couponMessage"></p>
                    </div>

                    <div class="bg-white p-6 md:p-8 rounded-3xl shadow-xl shadow-blue-900/5 border border-gray-100">
                        <h3 class="font-black text-gray-900 mb-4 border-b border-gray-100 pb-4">Ringkasan Pesanan</h3>

                        <div class="space-y-3 mb-6 max-h-40 overflow-y-auto pr-2">
                            <template x-for="item in cartItems" :key="item.id">
                                <div class="flex justify-between text-xs items-center">
                                    <span class="text-gray-600 truncate pr-2"><span class="font-bold text-gray-800" x-text="item.qty+'x'"></span> <span x-text="item.name"></span></span>
                                    <span class="font-semibold text-gray-900" x-text="formatRupiah(item.price * item.qty)"></span>
                                </div>
                            </template>
                        </div>

                        <div class="space-y-3 border-t border-gray-100 pt-4 text-sm text-gray-600">
                            <div class="flex justify-between">
                                <span>Total Harga Barang</span>
                                <span class="font-semibold text-gray-900" x-text="formatRupiah(subtotal)"></span>
                            </div>

                            <div class="flex justify-between text-green-600" x-show="discountAmount > 0">
                                <span>Diskon Kupon <span class="uppercase text-[10px] bg-green-100 px-1 rounded ml-1" x-text="appliedCoupon"></span></span>
                                <span class="font-bold" x-text="'- ' + formatRupiah(discountAmount)"></span>
                            </div>

                            <div class="flex justify-between text-blue-600" x-show="deliveryType === 'shipping'">
                                <span>Ongkos Kirim <span x-show="courierName" class="text-[10px] text-gray-400" x-text="'('+courierName+')'"></span></span>
                                <span class="font-bold" x-text="shippingCost > 0 ? '+ ' + formatRupiah(shippingCost) : 'Rp 0'"></span>
                            </div>
                        </div>

                        <div class="flex justify-between items-center font-black text-xl text-gray-900 border-t border-dashed border-gray-300 pt-4 mt-4">
                            <span>Total Tagihan</span>
                            <span x-text="formatRupiah(finalTotal)"></span>
                        </div>

                        <button type="submit" :disabled="!isReadyToPay" class="w-full bg-blue-600 text-white text-center py-4 rounded-xl font-bold mt-8 shadow-lg shadow-blue-600/30 hover:bg-blue-700 hover:-translate-y-1 transition transform disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none flex justify-center items-center gap-2 text-lg">
                            <i class="fas fa-lock text-sm"></i> Bayar Sekarang
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </form>
</div>

@push('scripts')

    @include('storefront.scripts.checkout')

@endpush
@endsection
