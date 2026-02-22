@extends('layouts.marketplace')

@section('title', 'Checkout')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10" x-data="checkoutProcess()" x-init="initCheckout()">
    <h2 class="text-2xl font-black text-gray-900 mb-8">Checkout Pesanan</h2>

    <form action="{{ route('storefront.process', $subdomain) }}" method="POST" id="checkoutForm" @submit.prevent="submitForm">
        @csrf
        <input type="hidden" name="items" :value="JSON.stringify(cartItems)">
        <input type="hidden" name="total" :value="subtotal">
        <input type="hidden" name="shipping_cost" :value="shippingCost">
        <input type="hidden" name="courier_name" :value="courierName">

        <div class="flex flex-col lg:flex-row gap-8">
            <div class="lg:w-2/3 space-y-6">

                <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100">
                    <h3 class="font-bold text-gray-900 mb-4 flex items-center gap-2"><i data-lucide="user" class="w-5 h-5 text-blue-600"></i> Informasi Kontak</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Nama Lengkap</label>
                            <input type="text" name="customer_name" required class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 outline-none focus:ring-2 focus:ring-blue-500 transition">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">No. WhatsApp</label>
                            <input type="text" name="customer_phone" required placeholder="08xxx..." class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 outline-none focus:ring-2 focus:ring-blue-500 transition">
                        </div>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100">
                    <h3 class="font-bold text-gray-900 mb-4 flex items-center gap-2"><i data-lucide="truck" class="w-5 h-5 text-blue-600"></i> Opsi Pengiriman</h3>

                    <div class="flex gap-4 mb-6">
                        <label class="flex-1 cursor-pointer">
                            <input type="radio" name="delivery_type" value="shipping" x-model="deliveryType" class="peer sr-only">
                            <div class="p-4 border-2 rounded-xl peer-checked:border-blue-600 peer-checked:bg-blue-50 text-center transition">
                                <span class="font-bold block text-sm">Kirim ke Alamat</span>
                            </div>
                        </label>
                        <label class="flex-1 cursor-pointer">
                            <input type="radio" name="delivery_type" value="pickup" x-model="deliveryType" class="peer sr-only">
                            <div class="p-4 border-2 rounded-xl peer-checked:border-blue-600 peer-checked:bg-blue-50 text-center transition">
                                <span class="font-bold block text-sm">Ambil di Toko</span>
                            </div>
                        </label>
                    </div>

                    <div x-show="deliveryType === 'shipping'" x-transition x-cloak class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Alamat Lengkap</label>
                            <textarea name="destination_text" rows="3" placeholder="Nama Jalan, RT/RW, Desa, Kecamatan..." class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 outline-none focus:ring-2 focus:ring-blue-500 transition"></textarea>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Pilih Ekspedisi</label>
                            <select x-model="courierName" @change="setDummyOngkir($event.target.value)" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 outline-none focus:ring-2 focus:ring-blue-500 transition cursor-pointer">
                                <option value="">-- Pilih Ekspedisi --</option>
                                <option value="JNE">JNE Reguler</option>
                                <option value="JNT">J&T Express</option>
                                <option value="GOSEND">GoSend Instant</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100">
                    <h3 class="font-bold text-gray-900 mb-4 flex items-center gap-2"><i data-lucide="wallet" class="w-5 h-5 text-blue-600"></i> Metode Pembayaran</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <label class="cursor-pointer">
                            <input type="radio" name="payment_method" value="dana_sdk" required class="peer sr-only">
                            <div class="p-4 border-2 rounded-xl peer-checked:border-blue-600 peer-checked:bg-blue-50 flex items-center gap-3 transition">
                                <img src="https://upload.wikimedia.org/wikipedia/commons/7/72/Logo_dana_blue.svg" class="h-6">
                                <span class="font-bold text-sm">DANA Otomatis</span>
                            </div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="payment_method" value="tripay" class="peer sr-only">
                            <div class="p-4 border-2 rounded-xl peer-checked:border-blue-600 peer-checked:bg-blue-50 flex items-center gap-3 transition">
                                <i data-lucide="qr-code" class="w-6 h-6 text-gray-600"></i>
                                <span class="font-bold text-sm">QRIS / Virtual Account</span>
                            </div>
                        </label>
                    </div>
                </div>
            </div>

            <div class="lg:w-1/3">
                <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100 sticky top-24">
                    <h3 class="font-bold text-gray-900 mb-4 border-b pb-4">Ringkasan Pesanan</h3>

                    <div class="space-y-3 mb-6 max-h-60 overflow-y-auto pr-2">
                        <template x-for="item in cartItems" :key="item.id">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600 truncate pr-4"><span x-text="item.qty"></span>x <span x-text="item.name"></span></span>
                                <span class="font-semibold text-gray-900" x-text="formatRupiah(item.price * item.qty)"></span>
                            </div>
                        </template>
                    </div>

                    <div class="space-y-2 border-t pt-4 text-sm text-gray-600">
                        <div class="flex justify-between">
                            <span>Subtotal</span>
                            <span class="font-semibold text-gray-900" x-text="formatRupiah(subtotal)"></span>
                        </div>
                        <div class="flex justify-between" x-show="deliveryType === 'shipping'">
                            <span>Ongkos Kirim</span>
                            <span class="font-semibold text-gray-900" x-text="formatRupiah(shippingCost)"></span>
                        </div>
                    </div>

                    <div class="flex justify-between font-black text-xl text-blue-600 border-t pt-4 mt-4">
                        <span>Total</span>
                        <span x-text="formatRupiah(finalTotal)"></span>
                    </div>

                    <button type="submit" :disabled="cartItems.length === 0" class="w-full bg-blue-600 text-white text-center py-4 rounded-xl font-bold mt-6 hover:bg-blue-700 shadow-lg transition disabled:opacity-50 disabled:cursor-not-allowed flex justify-center items-center gap-2">
                        <i data-lucide="shield-check" class="w-5 h-5"></i> Bayar Sekarang
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('checkoutProcess', () => ({
            cartItems: [],
            deliveryType: 'shipping',
            shippingCost: 0,
            courierName: '',

            initCheckout() {
                const saved = localStorage.getItem('sancaka_cart_{{ $tenant->id }}');
                if (saved) {
                    this.cartItems = JSON.parse(saved);
                }
                if(this.cartItems.length === 0) {
                    window.location.href = "{{ route('storefront.index', $subdomain) }}";
                }
            },

            get subtotal() {
                return this.cartItems.reduce((sum, item) => sum + (item.qty * item.price), 0);
            },

            get finalTotal() {
                let total = this.subtotal;
                if(this.deliveryType === 'shipping') {
                    total += parseInt(this.shippingCost);
                }
                return total;
            },

            // Dummy Ongkir untuk Frontend (Di real app gunakan AJAX ke route check-ongkir)
            setDummyOngkir(courier) {
                if(!courier) { this.shippingCost = 0; return; }
                this.shippingCost = (courier === 'GOSEND') ? 25000 : 15000;
            },

            submitForm(e) {
                if(this.deliveryType === 'shipping' && !this.courierName) {
                    alert('Pilih ekspedisi terlebih dahulu!');
                    return;
                }
                // Jika sukses, submit ke backend
                e.target.submit();
            }
        }))
    })
</script>
@endpush
@endsection
