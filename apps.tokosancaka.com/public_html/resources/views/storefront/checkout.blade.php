@extends('layouts.marketplace')

@section('title', 'Checkout Pesanan')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10" x-data="checkoutData()" x-init="initCheckout()">
    <h2 class="text-3xl font-black text-gray-900 mb-8">Checkout Pesanan</h2>

    <div x-show="errorMessage" x-cloak class="fixed inset-0 z-[200] flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
        <div class="bg-white rounded-3xl p-8 max-w-sm w-full shadow-2xl text-center">
            <div class="w-16 h-16 bg-red-100 text-red-600 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-times-circle text-3xl"></i>
            </div>
            <h3 class="text-xl font-black text-gray-900 mb-2">Ops, Gagal!</h3>
            <p class="text-gray-500 text-sm mb-6" x-text="errorMessage"></p>
            <button @click="errorMessage = ''" class="w-full bg-gray-900 text-white py-3 rounded-xl font-bold">Tutup & Perbaiki</button>
        </div>
    </div>

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
                                <template x-for="(loc, index) in locationResults" :key="index">
                                    <li @click="selectLocation(loc)" class="px-4 py-3 hover:bg-blue-50 cursor-pointer border-b border-gray-50 last:border-0 text-sm">
                                        <div class="font-bold text-gray-800" x-text="formatLocationName(loc)"></div>
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

                    {{-- GRID METODE UTAMA --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-4">

                        <label class="cursor-pointer">
                            <input type="radio" name="payment_method" value="dana_sdk" x-model="paymentMethod" required class="peer sr-only">
                            <div class="p-4 border-2 border-gray-200 rounded-xl peer-checked:border-blue-600 peer-checked:bg-blue-50 flex items-center gap-3 transition">
                                <img src="https://upload.wikimedia.org/wikipedia/commons/7/72/Logo_dana_blue.svg" class="h-6">
                                <div>
                                    <span class="font-bold text-sm block">DANA Otomatis</span>
                                    <span class="text-[10px] text-gray-500">Potong Saldo DANA</span>
                                </div>
                            </div>
                        </label>

                        <label class="cursor-pointer">
                            <input type="radio" name="payment_method" value="tripay" x-model="paymentMethod" class="peer sr-only">
                            <div class="p-4 border-2 border-gray-200 rounded-xl peer-checked:border-blue-600 peer-checked:bg-blue-50 flex items-center gap-3 transition">
                                <i class="fas fa-qrcode text-xl text-gray-700 peer-checked:text-blue-600"></i>
                                <div>
                                    <span class="font-bold text-sm block peer-checked:text-blue-700">QRIS / Virtual Account</span>
                                    <span class="text-[10px] text-gray-500">BCA, BNI, Mandiri, BRI, dll</span>
                                </div>
                            </div>
                        </label>

                        <label class="cursor-pointer">
                            <input type="radio" name="payment_method" value="doku" x-model="paymentMethod" class="peer sr-only">
                            <div class="p-4 border-2 border-gray-200 rounded-xl peer-checked:border-blue-600 peer-checked:bg-blue-50 flex items-center gap-3 transition">
                                <i class="fas fa-credit-card text-xl text-gray-700 peer-checked:text-blue-600"></i>
                                <div>
                                    <span class="font-bold text-sm block">DOKU Payment</span>
                                    <span class="text-[10px] text-gray-500">Credit Card & e-Wallet Lainnya</span>
                                </div>
                            </div>
                        </label>

                        <label class="cursor-pointer">
                            <input type="radio" name="payment_method" value="pay_later" x-model="paymentMethod" class="peer sr-only">
                            <div class="p-4 border-2 border-gray-200 rounded-xl peer-checked:border-blue-600 peer-checked:bg-blue-50 flex items-center gap-3 transition">
                                <i class="fas fa-hand-holding-usd text-xl text-gray-700 peer-checked:text-blue-600"></i>
                                <div>
                                    <span class="font-bold text-sm block">COD (Bayar di Tempat)</span>
                                    <span class="text-[10px] text-gray-500">Bayar Barang + Ongkir saat kurir tiba</span>
                                </div>
                            </div>
                        </label>
                    </div>

                    {{-- GRID KHUSUS TRIPAY (MUNCUL SAAT TRIPAY DIKLIK) --}}
                    <div x-show="paymentMethod === 'tripay'" x-cloak x-transition.opacity.duration.300ms class="mt-6 pt-6 border-t border-dashed border-gray-200">
                        <div class="flex items-center justify-between mb-4">
                            <h4 class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">Pilih Bank / Channel Pembayaran</h4>
                            <span x-show="isLoadingChannels" class="text-xs font-bold text-blue-500 animate-pulse">
                                <i class="fas fa-spinner fa-spin mr-1"></i> Memuat...
                            </span>
                        </div>

                        <div class="grid grid-cols-2 lg:grid-cols-3 gap-3">
                            <template x-for="channel in tripayChannels" :key="channel.code">
                                <label class="cursor-pointer relative h-full">
                                    <input type="radio" name="tripay_channel" :value="channel.code" x-model="selectedTripayChannel" class="peer sr-only">
                                    <div class="p-3 border-2 border-gray-200 rounded-xl peer-checked:border-blue-600 peer-checked:bg-blue-50 text-center transition flex flex-col items-center justify-between h-full bg-white hover:border-blue-300 shadow-sm relative overflow-hidden">

                                        <div x-show="selectedTripayChannel === channel.code" class="absolute top-2 right-2 text-blue-600">
                                            <i class="fas fa-check-circle"></i>
                                        </div>

                                        <div class="h-10 flex items-center justify-center mb-3 mt-1 w-full p-1 bg-white rounded">
                                            <img :src="channel.logo || channel.icon_url" :alt="channel.name" class="max-h-full max-w-full object-contain" @@error="$el.src='https://via.placeholder.com/100x40?text=Logo+Gagal'">
                                        </div>

                                        <div class="w-full">
                                            <span class="font-bold text-xs text-gray-800 block mb-1 truncate" x-text="channel.name"></span>
                                            <div class="text-[10px] font-semibold text-gray-500 bg-gray-100 px-2 py-1 rounded w-full border border-gray-200">
                                                Admin: <span class="text-blue-600" x-text="formatRupiah(calculateTripayFee(channel))"></span>
                                            </div>
                                        </div>
                                    </div>
                                </label>
                            </template>
                        </div>
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

                        {{-- 1. LIST PRODUK BESERTA BADGE PROMO --}}
                        <div class="space-y-4 mb-6 max-h-48 overflow-y-auto pr-2">
                            <template x-for="item in cartItems" :key="item.id">
                                <div class="flex flex-col border-b border-gray-50 pb-3 last:border-0 last:pb-0">
                                    <div class="flex justify-between text-xs items-center">
                                        <span class="text-gray-600 truncate pr-2"><span class="font-bold text-gray-800" x-text="item.qty+'x'"></span> <span x-text="item.name"></span></span>
                                        <span class="font-semibold text-gray-900" x-text="formatRupiah(item.price * item.qty)"></span>
                                    </div>

                                    {{-- Badge Muncul di bawah nama produk --}}
                                    <div class="flex gap-1 mt-1.5">
                                        <template x-if="item.is_free_ongkir == 1">
                                            <span class="text-[8px] font-bold text-teal-600 border border-teal-500 px-1 py-0.5 rounded-sm bg-teal-50 uppercase">Gratis Ongkir</span>
                                        </template>
                                        <template x-if="item.is_cashback_extra == 1">
                                            <span class="text-[8px] font-bold text-red-500 border border-red-500 px-1 py-0.5 rounded-sm bg-red-50 uppercase">Cashback Xtra</span>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </div>

                        {{-- 2. RINCIAN BIAYA & NOMINAL --}}
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

                            {{-- INFO POTONGAN GRATIS ONGKIR --}}
                            <div class="flex justify-between text-teal-600 bg-teal-50 p-2.5 rounded-xl border border-teal-100" x-show="cartItems.some(i => i.is_free_ongkir == 1) && deliveryType === 'shipping' && shippingCost > 0">
                                <span>Potongan Ongkir <span class="text-[10px] bg-teal-600 text-white px-1.5 py-0.5 rounded ml-1">PROMO</span></span>
                                <span class="font-bold" x-text="'- ' + formatRupiah(shippingCost)"></span>
                            </div>

                            {{-- ESTIMASI NOMINAL CASHBACK --}}
                            <div class="flex justify-between text-orange-600 bg-orange-50 p-2.5 rounded-xl border border-orange-100" x-show="cartItems.some(i => i.is_cashback_extra == 1)">
                                <span>Potensi Cashback <span class="text-[10px] bg-orange-500 text-white px-1.5 py-0.5 rounded ml-1">XTRA</span></span>
                                <span class="font-bold" x-text="'+ ' + formatRupiah(cartItems.filter(i => i.is_cashback_extra == 1).reduce((sum, item) => sum + (item.price * item.qty * 0.05), 0))"></span>
                            </div>

                            {{-- BARIS BARU YANG TERTINGGAL: BIAYA ADMIN TRIPAY --}}
                            <div class="flex justify-between text-gray-600" x-show="paymentMethod === 'tripay' && paymentAdminFee > 0">
                                <span>Biaya Admin <span class="text-[10px] text-gray-400">(Tripay)</span></span>
                                <span class="font-bold" x-text="'+ ' + formatRupiah(paymentAdminFee)"></span>
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
