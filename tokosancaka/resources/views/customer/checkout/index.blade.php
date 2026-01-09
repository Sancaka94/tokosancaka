@extends('layouts.customer')

@section('title', 'Checkout Pesanan')

@push('styles')
{{-- ========================================================== --}}
{{-- PERBAIKAN CSS: Selector + label sekarang sudah benar --}}
{{-- ========================================================== --}}
<style>
    input[type="radio"]:checked + label {
        border-color: #ef4444; /* red-500 */
        box-shadow: 0 0 0 2px #fecaca; /* red-200 */
    }
</style>
@endpush

@section('content')
<div class="container mx-auto py-10 px-4">
    <h1 class="text-3xl font-bold text-gray-800 mb-6">Checkout</h1>

    {{-- BLOK NOTIFIKASI ERROR (Lengkap) --}}
    <div id="notification-container" class="mb-4">
        @if(session('success'))
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-md" role="alert">
                <p><strong>Sukses!</strong> {{ session('success') }}</p>
            </div>
        @endif
        @if(session('error'))
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-md" role="alert">
                <p><strong>Gagal!</strong> {{ session('error') }}</p>
            </div>
        @endif
        @if(session('warning'))
            <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 rounded-md" role="alert">
                <p><strong>Peringatan:</strong> {{ session('warning') }}</p>
            </div>
        @endif
        @if ($errors->any())
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-md" role="alert">
                <strong class="font-bold">Terjadi Kesalahan:</strong>
                <ul class="list-disc list-inside mt-2">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>

    {{-- FORM UTAMA CHECKOUT --}}
    <form id="checkout-form" action="{{ route('customer.checkout.store') }}" method="POST">
        @csrf
        <div class="flex flex-col lg:flex-row gap-8">

            {{-- KOLOM KIRI: Alamat, Pengiriman, Pembayaran --}}
            <div class="w-full lg:w-2/3 space-y-6">

                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex justify-between items-center border-b pb-4 mb-4">
                        <h2 class="text-xl font-bold text-gray-800">Alamat Pengiriman</h2>
                        <a href="{{ route('customer.profile.edit') }}" class="text-sm text-blue-600 hover:underline">Ubah Alamat</a>
                    </div>
                    <div class="space-y-1">
                        <p class="font-bold text-gray-900">{{ $user->nama_lengkap }}</p>
                        <p class="text-gray-600">{{ $user->no_wa }}</p>
                        <p class="text-gray-600 mt-2">{{ $user->address_detail }}</p>
                        <p class="text-gray-600">{{ $user->village }}, {{ $user->district }}</p>
                        <p class="text-gray-600">{{ $user->regency }}, {{ $user->province }} {{ $user->postal_code }}</p>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-bold text-gray-800 border-b pb-4 mb-4">Pilih Metode Pengiriman</h2>
                    <div class="mt-4 space-y-4">
                        
                        @if(empty($expressOptions['results']) && empty($instantOptions['results']))
                            <div class="text-red-600 font-semibold">
                                Gagal memuat opsi pengiriman. Pastikan alamat Anda dan alamat toko sudah benar dan lengkap.
                            </div>
                        @endif

                        {{-- Opsi Instant --}}
                        @if(isset($instantOptions['results']) && !empty($instantOptions['results']))
                            <h3 class="font-semibold text-gray-700">Instant (1-3 Jam)</h3>
                            @foreach($instantOptions['results'] as $key => $opt)
                                {{-- ========================================================== --}}
                                {{-- PERBAIKAN STRUKTUR HTML: input di luar label --}}
                                {{-- ========================================================== --}}
                                <input type="radio" name="shipping_method" 
                                       id="ship-instant-{{ $key }}"
                                       value="instant-{{ $opt['service'] }}-{{ $opt['service_type'] }}-{{ $opt['final_price'] }}-0-0" 
                                       class="shipping-option hidden" data-cost="{{ $opt['final_price'] }}" required>
                                <label for="ship-instant-{{ $key }}" class="flex items-center justify-between p-4 border rounded-lg cursor-pointer transition-all">
                                    <div class="flex-1">
                                        <span class="font-bold text-gray-800">{{ $opt['service_name'] ?? 'Layanan Instant' }}</span>
                                        <span class="block text-sm text-gray-500">Estimasi {{ $opt['etd'] ?? '1-3 Jam' }}</span>
                                    </div>
                                    <div class="text-right ml-4">
                                        <span class="font-bold text-gray-900">Rp{{ number_format($opt['final_price']) }}</span>
                                    </div>
                                </label>
                            @endforeach
                        @endif
                        
                        {{-- Opsi Express/Cargo --}}
                        @if(isset($expressOptions['results']) && !empty($expressOptions['results']))
                            <h3 class="font-semibold text-gray-700 mt-4">Regular / Cargo</h3>
                            @foreach($expressOptions['results'] as $key => $opt)
                                {{-- ========================================================== --}}
                                {{-- PERBAIKAN STRUKTUR HTML: input di luar label --}}
                                {{-- ========================================================== --}}
                                <input type="radio" name="shipping_method" 
                                       id="ship-express-{{ $key }}"
                                       value="{{ $opt['group'] }}-{{ $opt['service'] }}-{{ $opt['service_type'] }}-{{ $opt['final_price'] }}-{{ $opt['insurance_cost'] ?? 0 }}-{{ $opt['cod_fee'] ?? 0 }}" 
                                       class="shipping-option hidden" data-cost="{{ $opt['final_price'] }}" required>
                                <label for="ship-express-{{ $key }}" class="flex items-center justify-between p-4 border rounded-lg cursor-pointer transition-all">
                                    <div class="flex-1">
                                        <span class="font-bold text-gray-800">
                                            {{ $opt['service_name'] }}
                                            @if(isset($opt['courier_name']))
                                                ({{ $opt['courier_name'] }})
                                            @endif
                                        </span>
                                        <span class="block text-sm text-gray-500">Estimasi {{ $opt['etd'] }}</span>
                                    </div>
                                    <div class="text-right ml-4">
                                        <span class="font-bold text-gray-900">Rp{{ number_format($opt['final_price']) }}</span>
                                    </div>
                                </label>
                            @endforeach
                        @endif
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-bold text-gray-800 border-b pb-4 mb-4">Pilih Metode Pembayaran</h2>
                    <div class="mt-4 space-y-4">
                        
                        {{-- ========================================================== --}}
                        {{-- PERBAIKAN STRUKTUR HTML: input di luar label --}}
                        {{-- ========================================================== --}}

                        {{-- OPSI POTONG SALDO (Baru) --}}
                        <input type="radio" name="payment_method" value="saldo" id="pay-saldo" class="hidden payment-option" data-group="local" checked>
                        <label for="pay-saldo" class="flex items-center p-4 border rounded-lg cursor-pointer transition-all">
                            <span class="font-semibold text-gray-800">
                                Potong Saldo (Saldo Anda: <span class="text-green-600 font-bold">Rp{{ number_format(Auth::user()->saldo) }}</span>)
                            </span>
                        </label>
                        
                        {{-- OPSI DOKU --}}
                        <input type="radio" name="payment_method" value="DOKU_JOKUL" id="pay-doku" class="hidden payment-option" data-group="gateway">
                        <label for="pay-doku" class="flex items-center p-4 border rounded-lg cursor-pointer transition-all">
                            <span class="font-semibold text-gray-800">DOKU (VA, QRIS, E-Wallet, dll)</span>
                        </label>

                        {{-- OPSI COD --}}
                        <input type="radio" name="payment_method" value="cod" id="pay-cod" class="hidden payment-option" data-group="local">
                        <label for="pay-cod" class="flex items-center p-4 border rounded-lg cursor-pointer transition-all">
                            <span class="font-semibold text-gray-800">Bayar di Tempat (COD)</span>
                        </label>

                        {{-- OPSI TRIPAY (Dinamis) --}}
                        @if(!empty($tripayChannels))
                            <h3 class="font-semibold text-gray-700 pt-4 border-t">Atau Pilih Langsung (Tripay)</h3>
                            @foreach($tripayChannels as $channel)
                                <input type="radio" name="payment_method" value="{{ $channel['code'] }}" id="pay-{{ $channel['code'] }}" class="hidden payment-option" data-group="gateway">
                                <label for="pay-{{ $channel['code'] }}" class="flex items-center p-4 border rounded-lg cursor-pointer transition-all">
                                    <img src="{{ $channel['icon_url'] }}" alt="{{ $channel['name'] }}" class="w-10 h-10 object-contain mr-4">
                                    <span class="font-semibold text-gray-800">{{ $channel['name'] }}</span>
                                </label>
                            @endforeach
                        @else
                            <p class="text-sm text-gray-500">Gagal memuat channel pembayaran online.</p>
                        @endif
                    </div>
                </div>

            </div>

            {{-- KOLOM KANAN: Ringkasan Pesanan (Sudah Benar) --}}
            <div class="w-full lg:w-1/3">
                <div class="bg-white rounded-lg shadow-md p-6 sticky top-24"> 
                    <h2 class="text-xl font-bold text-gray-800 border-b pb-4 mb-4">Ringkasan Pesanan</h2>
                    
                    <div class="divide-y divide-gray-200">
                        @php $subtotal = 0 @endphp
                        @foreach($cart as $id => $details)
                            @php $subtotal += $details['price'] * $details['quantity'] @endphp
                            <div class="py-3 flex justify-between items-center text-sm">
                                <div>
                                    <span class="text-gray-800 font-semibold">{{ $details['name'] }}</span>
                                    <span class="text-gray-500 block">x{{ $details['quantity'] }}</span>
                                </div>
                                <span class="font-medium text-gray-800">Rp{{ number_format($details['price'] * $details['quantity']) }}</span>
                            </div>
                        @endforeach
                    </div>
                    
                    <div class="border-t mt-4 pt-4 space-y-2">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Subtotal</span>
                            <span class="font-semibold text-gray-800" id="subtotal-text">Rp{{ number_format($subtotal) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Ongkos Kirim</span>
                            <span class="font-semibold text-gray-800" id="shipping-cost-text">Pilih Dulu</span>
                        </div>
                        <div class="flex justify-between" id="cod-fee-row" style="display: none;">
                            <span class="text-gray-600">Biaya COD</span>
                            <span class="font-semibold text-gray-800" id="cod-fee-text">Rp0</span>
                        </div>
                        <div class="border-t pt-4 flex justify-between items-center text-lg font-bold">
                            <span>Total Pembayaran</span>
                            <span class="text-red-600" id="grand-total-text">Rp{{ number_format($subtotal) }}</span>
                        </div>
                    </div>

                    <button type="submit" id="place-order-button" 
                            class="w-full mt-6 bg-red-600 text-white font-bold py-3 rounded-lg hover:bg-red-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed" 
                            disabled>
                        Buat Pesanan
                    </button>
                    
                </div>
            </div>

        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Ambil semua elemen (Sudah Benar)
    const subtotal = {{ $subtotal }};
    const userSaldo = {{ Auth::user()->saldo }};
    const shippingOptions = document.querySelectorAll('.shipping-option');
    const paymentOptions = document.querySelectorAll('.payment-option');
    
    const shippingCostText = document.getElementById('shipping-cost-text');
    const grandTotalText = document.getElementById('grand-total-text');
    const placeOrderButton = document.getElementById('place-order-button');
    const checkoutForm = document.getElementById('checkout-form');
    
    const codFeeRow = document.getElementById('cod-fee-row');
    const codFeeText = document.getElementById('cod-fee-text');
    
    let currentShippingCost = null;
    let currentShippingData = null;
    let currentPaymentMethod = document.querySelector('input[name="payment_method"]:checked').value;

    function formatRupiah(number) {
        return 'Rp' + new Intl.NumberFormat('id-ID').format(number);
    }

    // Fungsi Hitung Total (Sudah Benar)
    function calculateTotal() {
        if (currentShippingCost === null) {
            shippingCostText.innerText = 'Pilih Opsi';
            grandTotalText.innerText = formatRupiah(subtotal);
            placeOrderButton.disabled = true;
            return;
        }

        let shippingCost = currentShippingCost;
        let codFee = 0;
        let grandTotal = subtotal + shippingCost;
        
        if (currentPaymentMethod === 'cod') {
            if (currentShippingData) {
                const parts = currentShippingData.split('-');
                codFee = parseInt(parts[5] || 0); 
                if (codFee === 0) {
                    codFee = Math.ceil((subtotal + shippingCost) * 0.03); 
                }
            }
            
            codFeeRow.style.display = 'flex';
            codFeeText.innerText = formatRupiah(codFee);
            grandTotal += codFee;
            
        } else {
            codFeeRow.style.display = 'none';
        }
        
        shippingCostText.innerText = formatRupiah(shippingCost);
        grandTotalText.innerText = formatRupiah(grandTotal);
        validateForm(grandTotal);
    }

    // Fungsi Validasi Form (Sudah Benar)
    function validateForm(grandTotal) {
        let paymentValid = true;
        
        if (currentPaymentMethod === 'saldo') {
            if (userSaldo < grandTotal) {
                placeOrderButton.innerText = 'Saldo Tidak Cukup';
                paymentValid = false;
            } else {
                placeOrderButton.innerText = 'Bayar dengan Saldo';
            }
        } else {
            placeOrderButton.innerText = 'Buat Pesanan';
        }
        
        if (currentShippingCost !== null && paymentValid) {
            placeOrderButton.disabled = false;
        } else {
            placeOrderButton.disabled = true;
        }
    }

    // Listener (Sudah Benar)
    shippingOptions.forEach(function(radio) {
        radio.addEventListener('change', function() {
            if (this.checked) {
                currentShippingCost = parseInt(this.dataset.cost);
                currentShippingData = this.value;
                calculateTotal();
            }
        });
    });

    paymentOptions.forEach(function(radio) {
        radio.addEventListener('change', function() {
            if (this.checked) {
                currentPaymentMethod = this.value;
                calculateTotal();
            }
        });
    });

    // Inisialisasi (Sudah Benar)
    calculateTotal(); 

    if (shippingOptions.length === 0) {
         placeOrderButton.disabled = true;
         placeOrderButton.innerText = 'Pengiriman Tidak Tersedia';
         shippingCostText.innerText = 'Error';
         grandTotalText.innerText = 'Error';
    }

    // ==========================================================
    // PERBAIKAN: Kode SVG yang rusak di 'innerHTML'
    // ==========================================================
    checkoutForm.addEventListener('submit', function() {
        if (placeOrderButton.disabled) {
            return; 
        }
        
        placeOrderButton.disabled = true;
        // Kode SVG lengkap dimasukkan di sini
        placeOrderButton.innerHTML = `
            <svg class="animate-spin h-5 w-5 mr-3 inline-block" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Memproses...
        `;
    });
});
</script>
@endpush