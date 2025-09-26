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
        /* Style untuk tombol non-aktif */
        button:disabled {
            cursor: not-allowed;
            background-color: #9ca3af; /* Tailwind gray-400 */
            opacity: 0.7;
        }
    </style>
@endpush

@section('content')

@include('layouts.partials.notifications')

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
        
        <form id="checkout-form" action="{{ route('checkout.store') }}" method="POST">
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
                         
                   @php
$expressResults = collect($expressOptions['results'] ?? []);

$expressResults = $expressResults->map(function($option) {
    $serviceNameLower = strtolower($option['service_name'] ?? '');

    if (str_contains($serviceNameLower, 'trucking')) {
        $option['group'] = 'Trucking';
    } elseif (str_contains($serviceNameLower, 'cargo')) {
        $option['group'] = 'Cargo';
    } elseif (str_contains($serviceNameLower, 'instan') || str_contains($serviceNameLower, 'instant')) {
        $option['group'] = 'Instant';
    } elseif (str_contains($serviceNameLower, 'same day')) {
        $option['group'] = 'Same Day';
    } elseif (str_contains($serviceNameLower, 'one day') || str_contains($serviceNameLower, 'next day')) {
        $option['group'] = 'One Day';
    } else {
        $option['group'] = 'Regular';
    }

    return $option;
});

// Kelompokkan berdasarkan grup
$expressGrouped = $expressResults->groupBy('group');

// Pastikan urutannya sesuai keinginan
$groupOrder = ['Regular', 'One Day', 'Instant', 'Cargo', 'Trucking'];
$expressGrouped = collect($groupOrder)
    ->mapWithKeys(function($key) use ($expressGrouped) {
        return [$key => $expressGrouped->get($key, collect())];
    });

$firstGroup = $expressGrouped->keys()->first();
@endphp



<div class="flex gap-2 mb-4">
    {{-- Button dari express (Regular & Cargo) --}}
    @foreach($expressGrouped as $group => $options)
        <button type="button" 
            class="px-4 py-2 rounded-lg border hover:bg-gray-100 group-button" 
            data-group="{{ $group }}">
            {{ \Illuminate\Support\Str::of($group)->replace('_', ' ')->title() }}
        </button>
    @endforeach

</div>

{{-- Group Regular & Cargo --}}
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
                    {{ $loop->parent->first && $loop->first ? 'checked' : '' }}
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

{{-- Group Instant --}}
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

    <div class="express-group-options hidden" data-group="Instant">
        @foreach($instantSorted as $option)
            @php
                $logoName = strtolower(str_replace(' ', '', $option['courier_name'])) . '.png';
            @endphp
            <label class="flex items-center border border-gray-200 p-4 rounded-lg cursor-pointer hover:bg-gray-50 has-[:checked]:bg-blue-50 has-[:checked]:border-blue-500 mb-2">
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
    </div>
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

                        <!-- Syarat & Ketentuan -->
                        <div class="mt-6 border-t pt-6">
                            <div class="relative flex items-start">
                                <div class="flex h-6 items-center">
                                    <input id="terms-and-conditions" name="terms-and-conditions" type="checkbox" class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-600">
                                </div>
                                <div class="ml-3 text-sm leading-6">
                                    <label for="terms-and-conditions" class="font-medium text-gray-900">
                                        Saya telah membaca dan menyetujui
                                        <span id="terms-link" class="underline text-blue-600 cursor-pointer">Syarat & Ketentuan</span>
                                        yang berlaku.
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="mt-6">
                            <button type="submit" id="submit-button" class="flex w-full items-center justify-center rounded-md border border-transparent bg-blue-600 px-6 py-3 text-base font-medium text-white shadow-sm hover:bg-blue-700" disabled>
                                Buat Pesanan & Bayar
                            </button>
                        </div>
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
        <div class="flex justify-between items-center p-4 border-b">
            <h3 class="text-lg font-semibold text-gray-800">Pilih Metode Pembayaran</h3>
            <button type="button" id="closeModalButton" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        <div class="p-2">
            <ul id="paymentOptionsList" class="max-h-[60vh] overflow-y-auto space-y-2 p-4">
                 <li class="payment-option cursor-pointer flex items-center p-4 border rounded-lg hover:bg-blue-50" 
                     data-value="cash" 
                     data-label="CASH" 
                     data-img="https://uxwing.com/wp-content/themes/uxwing/download/banking-finance/cash-icon.png">
                     <img src="https://uxwing.com/wp-content/themes/uxwing/download/banking-finance/cash-icon.png" class="h-8 w-8 object-contain mr-4">
                     <span class="text-sm font-medium text-gray-900">CASH</span>
                 </li>
                 <li id="codPaymentOption" class="payment-option cursor-pointer flex items-center p-4 border rounded-lg hover:bg-blue-50" 
                     data-value="cod" 
                     data-label="COD" 
                     data-img="{{ asset('public/assets/cod.png') }}">
                     <img src="{{ asset('public/assets/cod.png') }}" class="h-8 w-8 object-contain mr-4">
                     <span class="text-sm font-medium text-gray-900">COD (Cash on Delivery)</span>
                 </li>
                <li class="payment-option cursor-pointer flex items-center p-4 border rounded-lg hover:bg-blue-50" data-value="PERMATAVA" data-label="Permata Virtual Account" data-img="{{ asset('storage/payments/permata.webp') }}">
                    <img src="{{ asset('public/assets/permata.webp') }}" class="h-8 w-8 object-contain mr-4">
                    <span class="text-sm font-medium text-gray-900">Permata Virtual Account</span>
                </li>
                 <li class="payment-option cursor-pointer flex items-center p-4 border rounded-lg hover:bg-blue-50" data-value="BNIVA" data-label="BNI Virtual Account" data-img="{{ asset('storage/payments/bni.webp') }}">
                    <img src="{{ asset('public/assets/bni.webp') }}" class="h-8 w-8 object-contain mr-4">
                    <span class="text-sm font-medium text-gray-900">BNI Virtual Account</span>
                </li>
                <li class="payment-option cursor-pointer flex items-center p-4 border rounded-lg hover:bg-blue-50" data-value="BRIVA" data-label="BRI Virtual Account" data-img="{{ asset('storage/payments/bri.webp') }}">
                    <img src="{{ asset('public/assets/bri.webp') }}" class="h-8 w-8 object-contain mr-4">
                    <span class="text-sm font-medium text-gray-900">BRI Virtual Account</span>
                </li>
                <li class="payment-option cursor-pointer flex items-center p-4 border rounded-lg hover:bg-blue-50" data-value="MANDIRIVA" data-label="Mandiri Virtual Account" data-img="{{ asset('storage/payments/mandiri.webp') }}">
                    <img src="{{ asset('public/assets/mandiri.webp') }}" class="h-8 w-8 object-contain mr-4">
                    <span class="text-sm font-medium text-gray-900">Mandiri Virtual Account</span>
                </li>
                <li class="payment-option cursor-pointer flex items-center p-4 border rounded-lg hover:bg-blue-50" data-value="BCAVA" data-label="BCA Virtual Account" data-img="{{ asset('storage/payments/bca.webp') }}">
                    <img src="{{ asset('public/assets/bca.webp') }}" class="h-8 w-8 object-contain mr-4">
                    <span class="text-sm font-medium text-gray-900">BCA Virtual Account</span>
                </li>
                 <li class="payment-option cursor-pointer flex items-center p-4 border rounded-lg hover:bg-blue-50" data-value="MUAMALATVA" data-label="Muamalat Virtual Account" data-img="{{ asset('storage/payments/muamalat.png') }}">
                    <img src="{{ asset('public/assets/muamalat.png') }}" class="h-8 w-8 object-contain mr-4">
                    <span class="text-sm font-medium text-gray-900">Muamalat Virtual Account</span>
                </li>
                 <li class="payment-option cursor-pointer flex items-center p-4 border rounded-lg hover:bg-blue-50" data-value="CIMBVA" data-label="CIMB Niaga Virtual Account" data-img="{{ asset('storage/payments/cimb.svg') }}">
                    <img src="{{ asset('public/assets/cimb.svg') }}" class="h-8 w-8 object-contain mr-4">
                    <span class="text-sm font-medium text-gray-900">CIMB Niaga Virtual Account</span>
                </li>
                 <li class="payment-option cursor-pointer flex items-center p-4 border rounded-lg hover:bg-blue-50" data-value="BSIVA" data-label="BSI Virtual Account" data-img="{{ asset('storage/payments/bsi.png') }}">
                    <img src="{{ asset('public/assets/bsi.png') }}" class="h-8 w-8 object-contain mr-4">
                    <span class="text-sm font-medium text-gray-900">BSI Virtual Account</span>
                </li>
                 <li class="payment-option cursor-pointer flex items-center p-4 border rounded-lg hover:bg-blue-50" data-value="OCBCVA" data-label="OCBC NISP Virtual Account" data-img="{{ asset('storage/payments/ocbc.png') }}">
                    <img src="{{ asset('public/assets/ocbc.png') }}" class="h-8 w-8 object-contain mr-4">
                    <span class="text-sm font-medium text-gray-900">OCBC NISP Virtual Account</span>
                </li>
                 <li class="payment-option cursor-pointer flex items-center p-4 border rounded-lg hover:bg-blue-50" data-value="DANAMONVA" data-label="Danamon Virtual Account" data-img="{{ asset('storage/payments/danamon.png') }}">
                    <img src="{{ asset('public/assets/danamon.png') }}" class="h-8 w-8 object-contain mr-4">
                    <span class="text-sm font-medium text-gray-900">Danamon Virtual Account</span>
                </li>
                 <li class="payment-option cursor-pointer flex items-center p-4 border rounded-lg hover:bg-blue-50" data-value="OTHERBANKVA" data-label="Other Bank Virtual Account" data-img="{{ asset('storage/payments/other.png') }}">
                    <img src="{{ asset('public/assets/other.png') }}" class="h-8 w-8 object-contain mr-4">
                    <span class="text-sm font-medium text-gray-900">Other Bank Virtual Account</span>
                </li>
                <li class="payment-option cursor-pointer flex items-center p-4 border rounded-lg hover:bg-blue-50" data-value="ALFAMART" data-label="Alfamart" data-img="{{ asset('storage/payments/alfamart.webp') }}">
                    <img src="{{ asset('public/assets/alfamart.webp') }}" class="h-8 w-8 object-contain mr-4">
                    <span class="text-sm font-medium text-gray-900">Alfamart</span>
                </li>
                <li class="payment-option cursor-pointer flex items-center p-4 border rounded-lg hover:bg-blue-50" data-value="INDOMARET" data-label="Indomaret" data-img="{{ asset('storage/payments/indomaret.webp') }}">
                    <img src="{{ asset('public/assets/indomaret.webp') }}" class="h-8 w-8 object-contain mr-4">
                    <span class="text-sm font-medium text-gray-900">Indomaret</span>
                </li>
                 <li class="payment-option cursor-pointer flex items-center p-4 border rounded-lg hover:bg-blue-50" data-value="ALFAMIDI" data-label="Alfamidi" data-img="{{ asset('storage/payments/Alfamidi.png') }}">
                    <img src="{{ asset('public/assets/Alfamidi.png') }}" class="h-8 w-8 object-contain mr-4">
                    <span class="text-sm font-medium text-gray-900">Alfamidi</span>
                </li>
                <li class="payment-option cursor-pointer flex items-center p-4 border rounded-lg hover:bg-blue-50" data-value="OVO" data-label="OVO" data-img="{{ asset('storage/payments/ovo.webp') }}">
                    <img src="{{ asset('public/assets/ovo.webp') }}" class="h-8 w-8 object-contain mr-4">
                    <span class="text-sm font-medium text-gray-900">OVO</span>
                </li>
                <li class="payment-option cursor-pointer flex items-center p-4 border rounded-lg hover:bg-blue-50" data-value="QRIS" data-label="QRIS" data-img="{{ asset('storage/payments/qris2.png') }}">
                    <img src="{{ asset('storage/payments/qris2.png') }}" class="h-8 w-8 object-contain mr-4">
                    <span class="text-sm font-medium text-gray-900">QRIS</span>
                </li>
                <li class="payment-option cursor-pointer flex items-center p-4 border rounded-lg hover:bg-blue-50" data-value="DANA" data-label="DANA" data-img="{{ asset('storage/payments/dana.webp') }}">
                    <img src="{{ asset('public/assets/dana.webp') }}" class="h-8 w-8 object-contain mr-4">
                    <span class="text-sm font-medium text-gray-900">DANA</span>
                </li>
                <li class="payment-option cursor-pointer flex items-center p-4 border rounded-lg hover:bg-blue-50" data-value="SHOPEEPAY" data-label="ShopeePay" data-img="{{ asset('storage/payments/shopeepay.webp') }}">
                    <img src="{{ asset('public/assets/shopeepay.webp') }}" class="h-8 w-8 object-contain mr-4">
                    <span class="text-sm font-medium text-gray-900">ShopeePay</span>
                </li>
            </ul>
        </div>
        <div class="flex justify-end p-4 border-t bg-gray-50 rounded-b-lg space-x-4">
            <button type="button" id="backButton" class="px-4 py-2 bg-white border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">Kembali</button>
            <button type="button" id="continueButton" class="px-4 py-2 bg-blue-600 border border-transparent rounded-md text-sm font-medium text-white hover:bg-blue-700">Lanjutkan</button>
        </div>
    </div>
</div>

<!-- Modal Syarat & Ketentuan -->
<div id="terms-modal" class="fixed inset-0 bg-gray-800 bg-opacity-75 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl mx-4 transform transition-all">
        <div class="flex justify-between items-center p-4 border-b">
            <h3 class="text-lg font-semibold text-gray-800">Syarat & Ketentuan Pembelian</h3>
            <button type="button" id="terms-modal-close" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        <div id="terms-content" class="p-6 text-sm text-gray-600 max-h-[60vh] overflow-y-auto space-y-4">
            <p class="font-bold">Selamat datang di Sancaka Marketplace!</p>
            <p>Dengan melakukan transaksi di platform kami, Anda dianggap telah membaca, memahami, dan menyetujui seluruh isi dalam Syarat & Ketentuan ini. Mohon dibaca dengan saksama.</p>
            
            <div>
                <h4 class="font-semibold text-gray-800">1. Definisi</h4>
                <p>Sancaka Marketplace adalah platform jual beli online yang mempertemukan Penjual dan Pembeli. Setiap produk yang terdaftar merupakan tanggung jawab Penjual sepenuhnya.</p>
            </div>
            <div>
                <h4 class="font-semibold text-gray-800">2. Proses Pemesanan</h4>
                <p>Pastikan alamat pengiriman dan nomor telepon yang Anda cantumkan sudah benar dan aktif. Kesalahan penulisan alamat yang mengakibatkan kegagalan pengiriman berada di luar tanggung jawab kami.</p>
            </div>
            <div>
                <h4 class="font-semibold text-gray-800">3. Pembayaran</h4>
                <p>Semua transaksi wajib dilakukan melalui metode pembayaran resmi. Biaya tambahan seperti COD atau biaya administrasi bank mungkin berlaku tergantung metode yang dipilih.</p>
            </div>
            <div>
                <h4 class="font-semibold text-gray-800">4. Pengiriman</h4>
                <p>Estimasi waktu pengiriman adalah perkiraan ekspedisi. Keterlambatan akibat kurir, cuaca, atau force majeure berada di luar kendali kami. Periksa kondisi produk saat diterima.</p>
            </div>
            <div>
                <h4 class="font-semibold text-gray-800">5. Kebijakan Pengembalian (Return & Refund)</h4>
                <p>Pengembalian hanya berlaku jika produk rusak, cacat, atau tidak sesuai deskripsi. Klaim wajib menyertakan video unboxing tanpa jeda sebagai bukti.</p>
            </div>
            <div>
                <h4 class="font-semibold text-gray-800">6. Asuransi & Kerusakan Barang</h4>
                <p>Pembeli dapat memilih layanan asuransi pengiriman. Klaim hanya dapat diproses apabila pembeli menggunakan layanan dengan asuransi.</p>
            </div>
            <div>
                <h4 class="font-semibold text-gray-800">7. Kesalahan Pemesanan</h4>
                <p>Pembeli wajib memastikan detail produk sudah sesuai sebelum checkout. Kesalahan pemesanan bukan tanggung jawab penjual maupun marketplace.</p>
            </div>
            <div>
                <h4 class="font-semibold text-gray-800">8. Tanggung Jawab Penjual</h4>
                <p>Penjual wajib memastikan produk asli, layak, dan sesuai deskripsi. Penjual dilarang menjual produk ilegal atau palsu.</p>
            </div>
            <div>
                <h4 class="font-semibold text-gray-800">9. Privasi dan Keamanan</h4>
                <p>Data pribadi pembeli dijaga sesuai kebijakan privasi. Data tidak akan dibagikan kepada pihak ketiga tanpa persetujuan.</p>
            </div>
            <div>
                <h4 class="font-semibold text-gray-800">10. Produk Digital</h4>
                <p>Untuk produk digital (e-book, software, lisensi), tidak ada pengembalian atau refund setelah produk diterima kecuali terdapat kerusakan file.</p>
            </div>
            <div>
                <h4 class="font-semibold text-gray-800">11. Garansi Produk</h4>
                <p>Beberapa produk mungkin memiliki garansi resmi dari penjual atau distributor. Pembeli wajib menyimpan bukti transaksi untuk klaim garansi.</p>
            </div>
            <div>
                <h4 class="font-semibold text-gray-800">12. Kewajiban Pembeli</h4>
                <p>Pembeli wajib memberikan informasi yang benar saat bertransaksi, menjaga etika dalam komunikasi, dan tidak melakukan penipuan.</p>
            </div>
            <div>
                <h4 class="font-semibold text-gray-800">13. Kewajiban Penjual</h4>
                <p>Penjual wajib mengirimkan produk sesuai pesanan, memberikan nomor resi valid, serta menjaga kualitas layanan kepada pembeli.</p>
            </div>
            <div>
                <h4 class="font-semibold text-gray-800">14. Larangan</h4>
                <p>Dilarang memperjualbelikan barang terlarang, berbahaya, atau yang melanggar hukum. Pelanggaran dapat mengakibatkan pemblokiran akun.</p>
            </div>
            <div>
                <h4 class="font-semibold text-gray-800">15. Penyelesaian Sengketa</h4>
                <p>Jika terjadi perselisihan, pembeli dan penjual wajib berusaha menyelesaikan secara musyawarah. Marketplace dapat menjadi mediator bila diperlukan.</p>
            </div>
            <div>
                <h4 class="font-semibold text-gray-800">16. Force Majeure</h4>
                <p>Kami tidak bertanggung jawab atas keterlambatan atau kegagalan layanan yang diakibatkan oleh kejadian di luar kendali seperti bencana alam, perang, atau kebijakan pemerintah.</p>
            </div>
            <div>
                <h4 class="font-semibold text-gray-800">17. Perubahan Harga</h4>
                <p>Harga produk dapat berubah sewaktu-waktu sesuai kebijakan penjual. Harga yang berlaku adalah harga saat checkout dilakukan.</p>
            </div>
            <div>
                <h4 class="font-semibold text-gray-800">18. Perubahan Syarat & Ketentuan</h4>
                <p>Sancaka Marketplace berhak memperbarui Syarat & Ketentuan kapan saja. Perubahan akan diumumkan melalui platform.</p>
            </div>
            <div>
                <h4 class="font-semibold text-gray-800">19. Hukum yang Berlaku</h4>
                <p>Syarat & Ketentuan ini tunduk pada hukum yang berlaku di Indonesia. Setiap sengketa akan diselesaikan di wilayah hukum Republik Indonesia.</p>
            </div>
            <div>
                <h4 class="font-semibold text-gray-800">20. Kontak & Layanan Pelanggan</h4>
                <p>Untuk pertanyaan, klaim, atau bantuan, pembeli dapat menghubungi layanan pelanggan melalui kontak resmi yang tersedia di platform.</p>
            </div>
            
            <p class="font-bold pt-4 border-t">Dengan menekan tombol "Setuju", Anda mengonfirmasi bahwa Anda telah membaca dan menerima semua poin di atas tanpa paksaan dari pihak manapun.</p>
        </div>
        <div class="flex justify-end p-4 border-t bg-gray-50 rounded-b-lg">
            <button type="button" id="terms-agree-button" class="px-6 py-2 bg-blue-600 border border-transparent rounded-md text-sm font-medium text-white hover:bg-blue-700" disabled>Setuju</button>
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

    const paymentModal = document.getElementById('paymentModal');
    const paymentMethodButton = document.getElementById('paymentMethodButton');
    const closeModalButton = document.getElementById('closeModalButton');
    const backButton = document.getElementById('backButton');
    const continueButton = document.getElementById('continueButton');
    const paymentOptionsList = document.getElementById('paymentOptionsList');

    const termsCheckbox = document.getElementById('terms-and-conditions');
    const submitButton = document.getElementById('submit-button');
    const termsModal = document.getElementById('terms-modal');
    const termsLink = document.getElementById('terms-link');
    const termsContent = document.getElementById('terms-content');
    const termsAgreeButton = document.getElementById('terms-agree-button');
    const termsModalClose = document.getElementById('terms-modal-close');
    const checkoutForm = document.getElementById('checkout-form');

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
                const defaultPayment = document.querySelector('.payment-option[data-value="PERMATAVA"]');
                if (defaultPayment) {
                    defaultPayment.click();
                }
            }
        }
        updateTotal();
    }

    function openPaymentModal() { paymentModal.classList.remove('hidden'); }
    function closePaymentModal() { paymentModal.classList.add('hidden'); }

    paymentMethodButton.addEventListener('click', openPaymentModal);
    closeModalButton.addEventListener('click', closePaymentModal);
    backButton.addEventListener('click', closePaymentModal);
    continueButton.addEventListener('click', closePaymentModal);
    
    function openTermsModal() { termsModal.classList.remove('hidden'); }
    function closeTermsModal() { termsModal.classList.add('hidden'); }
    
    // REVISI LOGIKA CHECKBOX
    termsCheckbox.addEventListener('change', function() {
        if (this.checked) {
            openTermsModal();
        } else {
            submitButton.disabled = true;
        }
    });

    termsLink.addEventListener('click', openTermsModal);

    termsContent.addEventListener('scroll', function() {
        const isScrolledToBottom = this.scrollHeight - this.scrollTop <= this.clientHeight + 1;
        termsAgreeButton.disabled = !isScrolledToBottom;
    });

    termsAgreeButton.addEventListener('click', function() {
        termsCheckbox.checked = true;
        submitButton.disabled = false;
        closeTermsModal();
    });

    termsModalClose.addEventListener('click', function() {
        termsCheckbox.checked = false;
        submitButton.disabled = true;
        closeTermsModal();
    });

    document.querySelectorAll('input[name="shipping_method"]').forEach(radio => {
        radio.addEventListener('change', handleShippingChange);
    });

    paymentOptionsList.querySelectorAll('.payment-option').forEach(item => {
        item.addEventListener('click', function () {
            const paymentValue = this.dataset.value;
            paymentMethodInput.value = paymentValue;
            isCodPayment = paymentValue === 'cod';
            
            paymentOptionsList.querySelectorAll('li').forEach(li => li.classList.remove('bg-blue-100', 'border-blue-500'));
            this.classList.add('bg-blue-100', 'border-blue-500');

            const label = this.dataset.label;
            const img = this.dataset.img;
            document.getElementById('paymentMethodLabel').textContent = label;
            document.getElementById('paymentMethodImg').src = img;
            
            updateTotal();
        });
    });

    checkoutForm.addEventListener('submit', function(e) {
        if (!termsCheckbox.checked) {
            e.preventDefault();
            alert('Anda harus menyetujui Syarat & Ketentuan untuk melanjutkan.');
        }
    });
    
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
            groupButtons.forEach(b => b.classList.remove('bg-blue-500', 'text-white'));
            button.classList.add('bg-blue-500', 'text-black');
            groupOptions.forEach(div => {
                div.classList.toggle('hidden', div.dataset.group !== group);
            });
        });
    });
</script>
@endsection

