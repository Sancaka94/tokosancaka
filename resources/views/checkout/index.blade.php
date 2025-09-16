@extends('layouts.marketplace')

@section('title', 'Checkout - Sancaka Marketplace')

@push('styles')
    {{-- Tailwind CSS dan Font sudah ada di sini --}}
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .form-radio:checked {
            background-image: url("data:image/svg+xml,%3csvg viewBox='0 0 16 16' fill='white' xmlns='http://www.w3.org/2000/svg'%3e%3ccircle cx='8' cy='8' r='3'/%3e%3c/svg%3e");
            border-color: transparent;
            background-color: currentColor;
            background-size: 100% 100%;
            background-position: center;
            background-repeat: no-repeat;
        }
    </style>
@endpush

@section('content')
<div class="bg-gray-100">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-12">
        
        <div class="mb-8 text-center">
            <h1 class="text-3xl font-extrabold text-gray-900">Checkout</h1>
            <p class="mt-2 text-sm text-gray-500">Selesaikan pesanan Anda dalam beberapa langkah mudah.</p>
        </div>

        @if (session('error'))
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-md" role="alert">
                <p class="font-bold">Terjadi Kesalahan</p>
                <p>{{ session('error') }}</p>
            </div>
        @endif

        @if ($errors->any())
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-md" role="alert">
                <p class="font-bold">Oops! Ada beberapa kesalahan:</p>
                <ul class="mt-2 list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        
        <form action="{{ route('checkout.store') }}" method="POST">
            @csrf
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                
                <!-- Kolom Kiri: Alamat, Pengiriman, Pembayaran -->
                <div class="lg:col-span-2 space-y-8">
                    
                    <!-- Alamat Pengiriman -->
                    <div class="bg-white rounded-xl shadow-md p-6">
                        <h2 class="text-lg font-bold text-gray-900 mb-4">Alamat Pengiriman</h2>
                        <div class="border border-gray-200 rounded-lg p-4">
                            @php
                                $user = Auth::user();
                                $alamat = $user->address_detail ?? 'Mohon lengkapi alamat Anda.';
                            @endphp
                            <p class="font-semibold">{{ $user->nama_lengkap }}</p>
                            <p class="text-sm text-gray-600">{{ $user->no_wa }}</p>
                            <p class="text-sm text-gray-600 mt-2">{{ $alamat }}</p>
                            <a href="{{ route('customer.profile.edit') }}" class="text-sm text-blue-600 hover:underline mt-2 inline-block">Ubah Alamat</a>
                        </div>
                    </div>

                    <!-- Opsi Pengiriman -->
                   <div class="bg-white rounded-xl shadow-md p-6">
                        <h2 class="text-lg font-bold text-gray-900 mb-4">Pilih Metode Pengiriman</h2>
                        <div class="space-y-4">
                            {{-- EXPRESS --}}
                           @php
                            $expressResults = collect($expressOptions['results'] ?? []);
                        
                            $expressResults = $expressResults->map(function($option) {
                                $serviceNameLower = strtolower($option['service_name'] ?? '');
                                if (str_contains($serviceNameLower, 'instant')) {
                                    $option['group'] = 'Instant';
                                } elseif (str_contains($serviceNameLower, 'cargo')) {
                                    $option['group'] = 'Cargo';
                                } else {
                                    $option['group'] = 'Regular';
                                }
                                return $option;
                            });
                        
                            $expressGrouped = $expressResults->groupBy('group');
                            $firstGroup = $expressGrouped->keys()->first();
                        @endphp
                        
                        <div class="flex gap-2 mb-4">
                            @foreach($expressGrouped as $group => $options)
                                <button type="button" 
                                    class="px-4 py-2 rounded-lg border hover:bg-gray-100 group-button" 
                                    data-group="{{ $group }}">
                                    {{ \Illuminate\Support\Str::of($group)->replace('_', ' ')->title() }}
                                </button>
                            @endforeach
                        </div>
                        
                        @foreach($expressGrouped as $group => $options)
                            <div class="express-group-options {{ $loop->first ? '' : 'hidden' }}" data-group="{{ $group }}">
                                @php
                                    $sortedOptions = collect($options)->sortBy('cost')->values();
                                @endphp
                        
                               @foreach($sortedOptions as $i => $option)
                                @php
                                    $logoName = strtolower(str_replace(' ', '', $option['service'])) . '.png';
                                @endphp
                                <label class="flex items-center border border-gray-200 p-4 rounded-lg cursor-pointer hover:bg-gray-50 has-[:checked]:bg-blue-50 has-[:checked]:border-blue-500 mb-2">
                                    <input 
                                        type="radio" 
                                        name="shipping_method" 
                                        value="express-{{ $option['service'] }}-{{ $option['service_type'] }}-{{ $option['cost'] }}-{{ $option['insurance'] }}" 
                                        class="form-radio h-5 w-5 text-blue-600"
                                        data-cost="{{ $option['cost'] }}"
                                        data-insurance="{{ $option['insurance'] }}"
                                        data-cod="{{ $option['cod'] ? 'true' : 'false' }}"
                                        {{ $i === 0 ? 'checked' : '' }}
                                    >
                                    <div class="ml-4 flex justify-between w-full items-center">
                                        <div class="flex items-center gap-3">
                                            <img src="{{ asset('storage/logo-ekspedisi/'.$logoName) }}" 
                                                 alt="{{ $option['service_name'] }}" 
                                                 class="w-8 h-8 object-contain">
                                            <span class="text-sm font-medium text-gray-900">{{ $option['service_name'] }}</span>
                                        </div>
                                        <span class="text-sm font-medium text-gray-900">
                                            Rp{{ number_format($option['cost'], 0, ',', '.') }}
                                        </span>
                                    </div>
                                </label>
                            @endforeach

                            </div>
                        @endforeach





                       
                           @if($instantOptions && isset($instantOptions['result']))
                                @php
                                    $instantSorted = collect($instantOptions['result'])
                                        ->flatMap(function($courier) {
                                            return collect($courier['costs'])->map(function($cost) use ($courier) {
                                                return [
                                                    'courier_name' => $courier['name'],
                                                    'service_type' => $cost['service_type'],
                                                    'estimation' => $cost['estimation'],
                                                    'price' => $cost['price']['total_price'],
                                                ];
                                            });
                                        })
                                        ->sortBy('price')
                                        ->values();
                                @endphp
                            
                               @foreach($instantSorted as $option)
    @php
        $logoName = strtolower(str_replace(' ', '', $option['courier_name'])) . '.png';
    @endphp
    <label class="flex items-center border border-gray-200 p-4 rounded-lg cursor-pointer hover:bg-gray-50 has-[:checked]:bg-blue-50 has-[:checked]:border-blue-500">
        <input type="radio" 
               name="shipping_method" 
               value="instant-{{ $option['courier_name'] }}-{{ $option['service_type'] }}-{{$option['price']}}-0" 
               class="form-radio h-5 w-5 text-blue-600"
               data-cost="{{ $option['price'] }}"
               data-insurance="0"
               data-cod="false">
        <div class="ml-4 flex justify-between w-full items-center">
            <div class="flex items-center gap-3">
                <img src="{{ asset('storage/logo-ekspedisi/'.$logoName) }}" 
                     alt="{{ strtoupper($option['courier_name']) }}" 
                     class="w-8 h-8 object-contain">
                <div>
                    <span class="text-sm font-medium text-gray-900">
                        {{ strtoupper($option['courier_name']) }} - {{ strtoupper($option['service_type']) }}
                    </span>
                    @if($option['estimation'])
                        <span class="block text-xs text-gray-500">Est: {{ $option['estimation'] }}</span>
                    @endif
                </div>
            </div>
            <span class="text-sm font-medium text-gray-900">
                Rp{{ number_format($option['price'], 0, ',', '.') }}
            </span>
        </div>
    </label>
@endforeach

                            @endif
                        </div>
                    </div>

                    <!-- Opsi Pembayaran -->
                    <div class="bg-white rounded-xl shadow-md p-6">
                        <h2 class="text-lg font-bold text-gray-900 mb-4">Pilih Metode Pembayaran</h2>
                        <div class="w-full">
                            <button type="button" id="paymentMethodButton" class="flex items-center justify-between w-full border border-gray-200 p-4 rounded-lg cursor-pointer hover:bg-gray-50 focus:outline-none">
                                <div class="flex items-center">
                                    <img id="paymentMethodImg" src="{{ asset('public/assets/permata.webp') }}" alt="Permata Logo" class="h-8 w-8 object-contain mr-4">
                                    <span id="paymentMethodLabel" class="text-sm font-medium text-gray-900">Permata Virtual Account</span>
                                </div>
                                <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>
                            <input type="hidden" name="payment_method" id="payment_method" value="PERMATAVA">
                        </div>
                    </div>

                </div>

                <!-- Kolom Kanan: Ringkasan Pesanan -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-xl shadow-md p-6 sticky top-24">
                        <h2 class="text-lg font-medium text-gray-900">Ringkasan Pesanan</h2>
                        
                        <ul role="list" class="divide-y divide-gray-200 my-6">
                            @php $subtotal = 0; @endphp
                            @foreach(session('cart', []) as $id => $details)
                                @php $subtotal += $details['price'] * $details['quantity']; @endphp
                                <li class="flex py-4">
                                    <div class="h-16 w-16 flex-shrink-0 overflow-hidden rounded-md">
                                        <img src="{{ url('storage/' . $details['image_url']) }}" alt="{{ $details['name'] }}" class="h-full w-full object-cover object-center">
                                    </div>
                                    <div class="ml-4 flex flex-1 flex-col text-sm">
                                        <h3 class="font-medium text-gray-800">{{ $details['name'] }}</h3>
                                        <p class="text-gray-500">Qty: {{ $details['quantity'] }}</p>
                                        <p class="text-gray-500 mt-auto">Rp{{ number_format($details['price'], 0, ',', '.') }}</p>
                                    </div>
                                </li>
                            @endforeach
                        </ul>

                        <div class="border-t border-gray-200 pt-6 space-y-4">
                            <div class="flex items-center justify-between">
                                <dt class="text-sm text-gray-600">Subtotal</dt>
                                <dd class="text-sm font-medium text-gray-900">Rp{{ number_format($subtotal, 0, ',', '.') }}</dd>
                            </div>
                             <div class="flex items-center justify-between">
                                <dt class="text-sm text-gray-600">Ongkos Kirim</dt>
                                <dd class="text-sm font-medium text-gray-900" id="ongkos_kirim">Rp{{ number_format(0, 0, ',', '.') }}</dd>
                            </div>
                            <div class="flex items-center justify-between" id="cod_fee_row" style="display:none;">
                                <dt class="text-sm text-gray-600">Biaya Penanganan COD</dt>
                                <dd class="text-sm font-medium text-gray-900" id="cod_fee_text">Rp0</dd>
                            </div>
                          <div class="flex items-center justify-between border-t border-gray-200 pt-4">
                            <dt class="text-base font-bold text-gray-900">Total Pesanan</dt>
                            <dd class="text-base font-bold text-gray-900" id="total_pesanan">
                                Rp{{ number_format($subtotal, 0, ',', '.') }}
                            </dd>
                          </div>
                        </div>

                        <div class="mt-6">
                            <button type="submit" class="flex w-full items-center justify-center rounded-md border border-transparent bg-blue-600 px-6 py-3 text-base font-medium text-white shadow-sm hover:bg-blue-700">
                                Buat Pesanan & Bayar
                            </button>
                        </div>
                    </div>
                </div>

            </div>
        </form>
    </div>
</div>

<!-- Payment Method Modal -->
<div id="paymentModal" class="fixed inset-0 bg-gray-800 bg-opacity-75 flex items-center justify-center z-50 hidden transition-opacity">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-lg mx-4 transform transition-all">
        <!-- Modal Header -->
        <div class="flex justify-between items-center p-4 border-b">
            <h3 class="text-lg font-semibold text-gray-800">Pilih Metode Pembayaran</h3>
            <button type="button" id="closeModalButton" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        <!-- Modal Body -->
        <div class="p-2">
            <ul id="paymentOptionsList" class="max-h-[60vh] overflow-y-auto space-y-2 p-4">
                <!-- Opsi Pembayaran akan dimasukkan oleh JS di sini -->
                 <li id="codPaymentOption" class="cursor-pointer flex items-center p-4 border rounded-lg hover:bg-blue-50" 
                    data-value="cash" 
                    data-label="CASH" 
                    data-img="https://uxwing.com/wp-content/themes/uxwing/download/banking-finance/cash-icon.png">
                    <img src="https://uxwing.com/wp-content/themes/uxwing/download/banking-finance/cash-icon.png" class="h-8 w-8 object-contain mr-4">
                    <span class="text-sm font-medium text-gray-900">CASH</span>
                </li>
                 <li id="codPaymentOption" class="cursor-pointer flex items-center p-4 border rounded-lg hover:bg-blue-50" 
                    data-value="cod" 
                    data-label="COD" 
                    data-img="{{ asset('public/assets/cod.png') }}">
                    <img src="{{ asset('public/assets/cod.png') }}" class="h-8 w-8 object-contain mr-4">
                    <span class="text-sm font-medium text-gray-900">COD (Cash on Delivery)</span>
                </li>
                <li class="cursor-pointer flex items-center p-4 border rounded-lg hover:bg-blue-50" data-value="PERMATAVA" data-label="Permata Virtual Account" data-img="{{ asset('public/assets/permata.webp') }}">
                    <img src="{{ asset('public/assets/permata.webp') }}" class="h-8 w-8 object-contain mr-4">
                    <span class="text-sm font-medium text-gray-900">Permata Virtual Account</span>
                </li>
                <li class="cursor-pointer flex items-center p-4 border rounded-lg hover:bg-blue-50" data-value="BNIVA" data-label="BNI Virtual Account" data-img="{{ asset('public/assets/bni.webp') }}">
                    <img src="{{ asset('public/assets/bni.webp') }}" class="h-8 w-8 object-contain mr-4">
                    <span class="text-sm font-medium text-gray-900">BNI Virtual Account</span>
                </li>
                <li class="cursor-pointer flex items-center p-4 border rounded-lg hover:bg-blue-50" data-value="BRIVA" data-label="BRI Virtual Account" data-img="{{ asset('public/assets/bri.webp') }}">
                    <img src="{{ asset('public/assets/bri.webp') }}" class="h-8 w-8 object-contain mr-4">
                    <span class="text-sm font-medium text-gray-900">BRI Virtual Account</span>
                </li>
                <li class="cursor-pointer flex items-center p-4 border rounded-lg hover:bg-blue-50" data-value="MANDIRIVA" data-label="Mandiri Virtual Account" data-img="{{ asset('public/assets/mandiri.webp') }}">
                    <img src="{{ asset('public/assets/mandiri.webp') }}" class="h-8 w-8 object-contain mr-4">
                    <span class="text-sm font-medium text-gray-900">Mandiri Virtual Account</span>
                </li>
                <li class="cursor-pointer flex items-center p-4 border rounded-lg hover:bg-blue-50" data-value="BCAVA" data-label="BCA Virtual Account" data-img="{{ asset('public/assets/bca.webp') }}">
                    <img src="{{ asset('public/assets/bca.webp') }}" class="h-8 w-8 object-contain mr-4">
                    <span class="text-sm font-medium text-gray-900">BCA Virtual Account</span>
                </li>
                <li class="cursor-pointer flex items-center p-4 border rounded-lg hover:bg-blue-50" data-value="MUAMALATVA" data-label="Muamalat Virtual Account" data-img="{{ asset('public/assets/muamalat.png') }}">
                    <img src="{{ asset('public/assets/muamalat.png') }}" class="h-8 w-8 object-contain mr-4">
                    <span class="text-sm font-medium text-gray-900">Muamalat Virtual Account</span>
                </li>
                <li class="cursor-pointer flex items-center p-4 border rounded-lg hover:bg-blue-50" data-value="CIMBVA" data-label="CIMB Niaga Virtual Account" data-img="{{ asset('public/assets/cimb.svg') }}">
                    <img src="{{ asset('public/assets/cimb.svg') }}" class="h-8 w-8 object-contain mr-4">
                    <span class="text-sm font-medium text-gray-900">CIMB Niaga Virtual Account</span>
                </li>
                <li class="cursor-pointer flex items-center p-4 border rounded-lg hover:bg-blue-50" data-value="BSIVA" data-label="BSI Virtual Account" data-img="{{ asset('public/assets/bsi.png') }}">
                    <img src="{{ asset('public/assets/bsi.png') }}" class="h-8 w-8 object-contain mr-4">
                    <span class="text-sm font-medium text-gray-900">BSI Virtual Account</span>
                </li>
                <li class="cursor-pointer flex items-center p-4 border rounded-lg hover:bg-blue-50" data-value="OCBCVA" data-label="OCBC NISP Virtual Account" data-img="{{ asset('public/assets/ocbc.png') }}">
                    <img src="{{ asset('public/assets/ocbc.png') }}" class="h-8 w-8 object-contain mr-4">
                    <span class="text-sm font-medium text-gray-900">OCBC NISP Virtual Account</span>
                </li>
                <li class="cursor-pointer flex items-center p-4 border rounded-lg hover:bg-blue-50" data-value="DANAMONVA" data-label="Danamon Virtual Account" data-img="{{ asset('public/assets/danamon.png') }}">
                    <img src="{{ asset('public/assets/danamon.png') }}" class="h-8 w-8 object-contain mr-4">
                    <span class="text-sm font-medium text-gray-900">Danamon Virtual Account</span>
                </li>
                <li class="cursor-pointer flex items-center p-4 border rounded-lg hover:bg-blue-50" data-value="OTHERBANKVA" data-label="Other Bank Virtual Account" data-img="{{ asset('public/assets/other.png') }}">
                    <img src="{{ asset('public/assets/other.png') }}" class="h-8 w-8 object-contain mr-4">
                    <span class="text-sm font-medium text-gray-900">Other Bank Virtual Account</span>
                </li>
                <li class="cursor-pointer flex items-center p-4 border rounded-lg hover:bg-blue-50" data-value="ALFAMART" data-label="Alfamart" data-img="{{ asset('public/assets/alfamart.webp') }}">
                    <img src="{{ asset('public/assets/alfamart.webp') }}" class="h-8 w-8 object-contain mr-4">
                    <span class="text-sm font-medium text-gray-900">Alfamart</span>
                </li>
                <li class="cursor-pointer flex items-center p-4 border rounded-lg hover:bg-blue-50" data-value="INDOMARET" data-label="Indomaret" data-img="{{ asset('public/assets/indomaret.webp') }}">
                    <img src="{{ asset('public/assets/indomaret.webp') }}" class="h-8 w-8 object-contain mr-4">
                    <span class="text-sm font-medium text-gray-900">Indomaret</span>
                </li>
                <li class="cursor-pointer flex items-center p-4 border rounded-lg hover:bg-blue-50" data-value="ALFAMIDI" data-label="Alfamidi" data-img="{{ asset('public/assets/Alfamidi.png') }}">
                    <img src="{{ asset('public/assets/Alfamidi.png') }}" class="h-8 w-8 object-contain mr-4">
                    <span class="text-sm font-medium text-gray-900">Alfamidi</span>
                </li>
                <li class="cursor-pointer flex items-center p-4 border rounded-lg hover:bg-blue-50" data-value="OVO" data-label="OVO" data-img="{{ asset('public/assets/ovo.webp') }}">
                    <img src="{{ asset('public/assets/ovo.webp') }}" class="h-8 w-8 object-contain mr-4">
                    <span class="text-sm font-medium text-gray-900">OVO</span>
                </li>
                <li class="cursor-pointer flex items-center p-4 border rounded-lg hover:bg-blue-50" data-value="QRIS" data-label="QRIS" data-img="{{ asset('public/assets/qris2.png') }}">
                    <img src="{{ asset('public/assets/qris2.png') }}" class="h-8 w-8 object-contain mr-4">
                    <span class="text-sm font-medium text-gray-900">QRIS</span>
                </li>
                <li class="cursor-pointer flex items-center p-4 border rounded-lg hover:bg-blue-50" data-value="DANA" data-label="DANA" data-img="{{ asset('public/assets/dana.webp') }}">
                    <img src="{{ asset('public/assets/dana.webp') }}" class="h-8 w-8 object-contain mr-4">
                    <span class="text-sm font-medium text-gray-900">DANA</span>
                </li>
                <li class="cursor-pointer flex items-center p-4 border rounded-lg hover:bg-blue-50" data-value="SHOPEEPAY" data-label="ShopeePay" data-img="{{ asset('public/assets/shopeepay.webp') }}">
                    <img src="{{ asset('public/assets/shopeepay.webp') }}" class="h-8 w-8 object-contain mr-4">
                    <span class="text-sm font-medium text-gray-900">ShopeePay</span>
                </li>
            </ul>
        </div>
        <!-- Modal Footer -->
        <div class="flex justify-end p-4 border-t bg-gray-50 rounded-b-lg space-x-4">
            <button type="button" id="backButton" class="px-4 py-2 bg-white border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">Kembali</button>
            <button type="button" id="continueButton" class="px-4 py-2 bg-blue-600 border border-transparent rounded-md text-sm font-medium text-white hover:bg-blue-700">Lanjutkan Pembayaran</button>
        </div>
    </div>
</div>


<script>
document.addEventListener('DOMContentLoaded', function () {
    const subtotal = {{ $subtotal }};
    const codFeePercentage = 0.03;

    let shippingCost = 0;
    let insuranceCost = 0;
    let isCodPayment = false;
    let isCodSupportedByCourier = false;
    
    const ongkirEl = document.getElementById('ongkos_kirim');
    const codFeeRow = document.getElementById('cod_fee_row');
    const codFeeTextEl = document.getElementById('cod_fee_text');
    const totalEl = document.getElementById('total_pesanan');
    const paymentMethodInput = document.getElementById('payment_method');
    const codPaymentOption = document.getElementById("codPaymentOption");

    // Modal elements
    const paymentModal = document.getElementById('paymentModal');
    const paymentMethodButton = document.getElementById('paymentMethodButton');
    const closeModalButton = document.getElementById('closeModalButton');
    const backButton = document.getElementById('backButton');
    const continueButton = document.getElementById('continueButton');
    const paymentOptionsList = document.getElementById('paymentOptionsList');

    function formatRupiah(num) {
        return 'Rp' + new Intl.NumberFormat('id-ID').format(num || 0);
    }

    function updateTotal() {
        let codAddCost = 0;

        if (isCodPayment && isCodSupportedByCourier) {
            const baseTotal = subtotal + shippingCost + insuranceCost;
            codAddCost = Math.ceil(baseTotal * codFeePercentage);
            codFeeRow.style.display = 'flex';
            codFeeTextEl.innerText = formatRupiah(codAddCost);
        } else {
            codFeeRow.style.display = 'none';
        }

        const grandTotal = subtotal + shippingCost + insuranceCost + codAddCost;
        
        ongkirEl.innerText = formatRupiah(shippingCost);
        totalEl.innerText = formatRupiah(grandTotal);
    }

    function handleShippingChange() {
        const selectedShipping = document.querySelector('input[name="shipping_method"]:checked');
        if (!selectedShipping) return;

        shippingCost = parseInt(selectedShipping.dataset.cost || 0, 10);
        insuranceCost = parseInt(selectedShipping.dataset.insurance || 0, 10);
        isCodSupportedByCourier = selectedShipping.dataset.cod === 'true';

        if (isCodSupportedByCourier) {
            codPaymentOption.style.display = 'flex';
        } else {
            codPaymentOption.style.display = 'none';
            if(isCodPayment) {
                const defaultPayment = document.querySelector('#paymentOptionsList li[data-value="PERMATAVA"]');
                if (defaultPayment) {
                    defaultPayment.click();
                }
            }
        }
        updateTotal();
    }

    // --- Modal Logic ---
    function openModal() { paymentModal.classList.remove('hidden'); }
    function closeModal() { paymentModal.classList.add('hidden'); }

    paymentMethodButton.addEventListener('click', openModal);
    closeModalButton.addEventListener('click', closeModal);
    backButton.addEventListener('click', closeModal);
    continueButton.addEventListener('click', closeModal);
    paymentModal.addEventListener('click', (event) => {
        if (event.target === paymentModal) {
            closeModal();
        }
    });

    // --- Event Listeners ---
    document.querySelectorAll('input[name="shipping_method"]').forEach(radio => {
        radio.addEventListener('change', handleShippingChange);
    });

    paymentOptionsList.querySelectorAll('li').forEach(item => {
        item.addEventListener('click', function () {
            const paymentValue = this.dataset.value;
            paymentMethodInput.value = paymentValue;
            isCodPayment = paymentValue === 'cod';
            
            // Update selected item style in modal
            paymentOptionsList.querySelectorAll('li').forEach(li => li.classList.remove('bg-blue-100', 'border-blue-500'));
            this.classList.add('bg-blue-100', 'border-blue-500');

            // Update main button UI
            const label = this.dataset.label;
            const img = this.dataset.img;
            document.getElementById('paymentMethodLabel').textContent = label;
            document.getElementById('paymentMethodImg').src = img;
            
            updateTotal();
        });
    });

    // --- Initialization ---
    // Set initial active state for payment in modal
    const initialPayment = document.querySelector(`#paymentOptionsList li[data-value="${paymentMethodInput.value}"]`);
    if(initialPayment) {
        initialPayment.classList.add('bg-blue-100', 'border-blue-500');
    }
    handleShippingChange();
});
</script>
<script>
    const groupButtons = document.querySelectorAll('.group-button');
    const groupOptions = document.querySelectorAll('.express-group-options');

    groupButtons.forEach(button => {
        button.addEventListener('click', () => {
            const group = button.dataset.group;

            // Toggle class active pada tombol
            groupButtons.forEach(b => b.classList.remove('bg-blue-500', 'text-white'));
            button.classList.add('bg-blue-500', 'text-black');

            // Tampilkan opsi group terkait
            groupOptions.forEach(div => {
                div.classList.toggle('hidden', div.dataset.group !== group);
            });
        });
    });
</script>
@endsection

