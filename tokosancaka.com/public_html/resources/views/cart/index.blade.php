@extends('layouts.marketplace')

@section('title', 'Keranjang Belanja - Sancaka Marketplace')

@push('styles')
    {{-- Memuat pustaka yang dibutuhkan --}}
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    {{-- Font Awesome --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; }
        input[type=number]::-webkit-inner-spin-button,
        input[type=number]::-webkit-outer-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
        input[type=number] { -moz-appearance: textfield; }

        .loader {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #3498db;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            animation: spin 1s linear infinite;
            display: inline-block;
            margin-left: 5px;
            vertical-align: middle;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .loading-overlay {
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background-color: rgba(255, 255, 255, 0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10;
            border-radius: 0.375rem;
        }
        .dark .loading-overlay {
            background-color: rgba(31, 41, 55, 0.7);
        }

        .btn-shopee-outline {
            background-color: rgba(238, 77, 45, 0.1);
            border: 1px solid #d0011b;
            color: #d0011b;
            transition: background-color 0.2s ease;
        }
        .btn-shopee-outline:hover:not(:disabled) {
            background-color: rgba(238, 77, 45, 0.15);
        }
        .btn-shopee-solid {
            background-color: #d0011b;
            border: 1px solid #d0011b;
            color: white;
            transition: background-color 0.2s ease;
        }

        .btn-shopee-solid:hover:not(:disabled) {
            background-color: #d73210;
        }
        button:disabled, a.disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        input:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            background-color: #f3f4f6;
        }
        .dark input:disabled {
            background-color: #4b5563;
        }
        #alert-error a {
            color: #721c24;
            font-weight: bold;
            text-decoration: underline;
        }
        #alert-error a:hover {
            color: #491217;
        }
        .dark #alert-error a {
            color: #f8d7da;
        }
        .dark #alert-error a:hover {
            color: #f1b0b7;
        }
    </style>
@endpush

@section('content')

<div class="bg-gray-100 min-h-screen">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <h1 class="text-2xl sm:text-3xl font-extrabold text-gray-900 mb-8">Keranjang Belanja Anda</h1>

        @if (session('success'))
            <div id="alert-success" class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                <strong class="font-bold">Berhasil!</strong>
                <span class="block sm:inline">{{ session('success') }}</span>
                <span class="absolute top-0 bottom-0 right-0 px-4 py-3 close-alert cursor-pointer">
                    <svg class="fill-current h-6 w-6 text-green-500" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><title>Close</title><path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z"/></svg>
                </span>
            </div>
        @endif
        @if (session('error'))
            <div id="alert-error" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <strong class="font-bold">Gagal!</strong>
                <span class="block sm:inline">{!! session('error') !!}</span>
                <span class="absolute top-0 bottom-0 right-0 px-4 py-3 close-alert cursor-pointer">
                    <svg class="fill-current h-6 w-6 text-red-500" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><title>Close</title><path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z"/></svg>
                </span>
            </div>
        @endif
        @if (session('info'))
            <div id="alert-info" class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded relative mb-4" role="alert">
                <strong class="font-bold">Info:</strong>
                <span class="block sm:inline">{!! session('info') !!}</span>
                <span class="absolute top-0 bottom-0 right-0 px-4 py-3 close-alert cursor-pointer">
                    <svg class="fill-current h-6 w-6 text-blue-500" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><title>Close</title><path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z"/></svg>
                </span>
            </div>
        @endif
        @if (session('warning'))
            <div id="alert-warning" class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded relative mb-4" role="alert">
                <strong class="font-bold">Perhatian:</strong>
                <span class="block sm:inline">{!! session('warning') !!}</span>
                <span class="absolute top-0 bottom-0 right-0 px-4 py-3 close-alert cursor-pointer">
                    <svg class="fill-current h-6 w-6 text-yellow-500" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><title>Close</title><path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z"/></svg>
                </span>
            </div>
        @endif

        <div id="ajax-error-message" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4 hidden" role="alert"></div>

        <div id="cart-content-area">
            @if(!empty($cart))
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

                    <div class="lg:col-span-2 bg-white rounded-xl shadow-sm">
                        <div class="p-4 sm:p-6 border-b border-gray-200 flex justify-between items-center">
                            <h2 class="text-lg font-semibold text-gray-900">Item di Keranjang</h2>
                           @if(Route::has('cart.clear') || Route::has('customer.cart.clear'))
                            <form action="{{ Route::has('customer.cart.clear') ? route('customer.cart.clear') : route('cart.clear') }}" method="POST" onsubmit="return confirm('Anda yakin ingin mengosongkan keranjang?');">
                                @csrf
                                @method('DELETE') {{-- Method Spoofing untuk Route::delete --}}
                                <button type="submit" class="text-sm font-medium text-red-600 hover:text-red-800">
                                    <i class="fas fa-trash-alt mr-1"></i> Kosongkan Keranjang
                                </button>
                            </form>
                            @endif
                        </div>
                        <ul role="list" class="divide-y divide-gray-200">
                            @php $totalWeight = 0; $grandTotal = 0; @endphp
                            @foreach($cart as $cartKey => $details)
                                @php
                                    $price = $details['price'] ?? 0;
                                    $quantity = $details['quantity'] ?? 0;
                                    $subtotal = $price * $quantity;
                                    $grandTotal += $subtotal;
                                    $currentStock = $details['current_stock'] ?? 999;
                                @endphp

                                <li class="flex flex-col sm:flex-row py-4 sm:py-6 px-4 sm:px-6 cart-item relative" data-id="{{ $cartKey }}">
                                    <div class="h-24 w-24 sm:h-28 sm:w-28 flex-shrink-0 overflow-hidden rounded-md border border-gray-200 bg-gray-50">
                                        @php
                                            $imageSrc = 'https://placehold.co/112x112/EFEFEF/AAAAAA?text=N/A';
                                            if (isset($details['is_ppob']) && $details['is_ppob'] == true) {
                                                $imageSrc = get_operator_logo($details['slug'] ?? $details['name']);
                                            } elseif (!empty($details['image_url'])) {
                                                if (filter_var($details['image_url'], FILTER_VALIDATE_URL)) {
                                                    $imageSrc = $details['image_url'];
                                                } else {
                                                    $imageSrc = asset('public/storage/' . $details['image_url']);
                                                }
                                            }
                                        @endphp

                                        <img src="{{ $imageSrc }}"
                                             alt="{{ $details['name'] ?? 'Produk' }}"
                                             class="h-full w-full object-cover object-center"
                                             onerror="this.onerror=null;this.src='https://placehold.co/112x112/EFEFEF/AAAAAA?text=Error';">
                                    </div>

                                    <div class="ml-0 sm:ml-4 mt-4 sm:mt-0 flex flex-1 flex-col">
                                        <div>
                                            <div class="flex justify-between text-base font-medium text-gray-900">
                                                <h3>
                                                    @if(isset($details['slug']) && Route::has('products.show') && empty($details['is_ppob']))
                                                        <a href="{{ route('products.show', $details['slug']) }}" class="hover:text-orange-600 line-clamp-2">{{ $details['name'] ?? 'Nama Produk Tidak Tersedia' }}</a>
                                                    @else
                                                        <span class="line-clamp-2">{{ $details['name'] ?? 'Nama Produk Tidak Tersedia' }}</span>
                                                    @endif
                                                </h3>
                                                <p class="ml-4 item-subtotal flex-shrink-0">Rp{{ number_format($subtotal, 0, ',', '.') }}</p>
                                            </div>
                                            <p class="mt-1 text-sm text-gray-500">Rp{{ number_format($price, 0, ',', '.') }} / item</p>
                                        </div>
                                        <div class="flex flex-1 items-end justify-between text-sm mt-4">
                                            <div class="flex items-center">
                                                <button type="button" class="quantity-change px-2 py-1 border border-gray-300 rounded-l text-gray-600 hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed" data-action="decrease" data-id="{{ $cartKey }}" {{ $quantity <= 1 ? 'disabled' : '' }}>
                                                    <i class="fas fa-minus text-xs"></i>
                                                </button>
                                                <input type="number"
                                                       class="quantity-input w-12 h-8 text-center text-sm border-t border-b border-gray-300 focus:outline-none focus:ring-1 focus:ring-orange-500"
                                                       value="{{ $quantity }}"
                                                       min="1"
                                                       max="{{ $currentStock }}"
                                                       data-id="{{ $cartKey }}">
                                                <button type="button" class="quantity-change px-2 py-1 border border-gray-300 rounded-r text-gray-600 hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed" data-action="increase" data-id="{{ $cartKey }}" {{ $quantity >= $currentStock ? 'disabled' : '' }}>
                                                    <i class="fas fa-plus text-xs"></i>
                                                </button>
                                                <span class="update-status ml-2 text-xs text-gray-500" data-id="{{ $cartKey }}"></span>
                                            </div>

                                            <div class="flex">
                                                <button type="button" class="remove-item font-medium text-red-600 hover:text-red-800" data-id="{{ $cartKey }}">
                                                    <i class="fas fa-trash-alt mr-1"></i> Hapus
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="loading-overlay hidden">
                                        <div class="loader"></div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    <div class="lg:col-span-1">
                        <div class="bg-white rounded-xl shadow-sm p-6 sticky top-24">
                            <h2 class="text-lg font-semibold text-gray-900">Ringkasan Pesanan</h2>
                            <div class="mt-6 space-y-4 border-b border-gray-200 pb-4">
                                <div class="flex items-center justify-between">
                                    <dt class="text-sm text-gray-600">Subtotal (<span id="total-items">{{ count($cart) }}</span> item)</dt>
                                    <dd class="text-sm font-medium text-gray-900" id="subtotal-amount">Rp{{ number_format($grandTotal, 0, ',', '.') }}</dd>
                                </div>
                            </div>
                            <div class="flex items-center justify-between border-t border-gray-200 pt-4 mt-4">
                                <dt class="text-base font-semibold text-gray-900">Total Pesanan</dt>
                                <dd class="text-base font-semibold text-gray-900" id="total-amount">Rp{{ number_format($grandTotal, 0, ',', '.') }}</dd>
                            </div>
                            <div class="mt-6">
                                {{-- Menggunakan route customer.checkout.index --}}
                                <a href="{{ route('customer.checkout.index') }}" class="flex w-full items-center justify-center rounded-md border border-transparent btn-shopee-solid px-6 py-3 text-base font-medium text-white shadow-sm hover:opacity-90 {{ empty($cart) ? 'opacity-50 pointer-events-none disabled' : '' }}">
                                    Lanjut ke Checkout
                                </a>
                            </div>
                            <div class="mt-6 flex justify-center text-center text-sm text-gray-500">
                                <p>
                                    atau
                                    @if(Route::has('etalase.index'))
                                        <a href="{{ route('etalase.index') }}" class="font-medium text-orange-600 hover:text-orange-500">
                                            Lanjut Belanja
                                            <span aria-hidden="true"> &rarr;</span>
                                        </a>
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>

                </div>
            @else
                @include('partials.empty-cart')
            @endif
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
$(document).ready(function () {
    function formatCurrency(number) {
        number = Number(number);
        if (isNaN(number)) {
            return 'Rp0';
        }
        return 'Rp' + Math.round(number).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    }

    function updateSummary(total, itemCount) {
        $('#subtotal-amount').text(formatCurrency(total));
        $('#total-amount').text(formatCurrency(total));
        $('#total-items').text(itemCount);
        $('a[href="{{ route('customer.checkout.index') }}"]').toggleClass('opacity-50 pointer-events-none disabled', itemCount <= 0);
    }

    function updateQuantityButtonsState(cartKey) {
        const input = $(`.quantity-input[data-id="${cartKey}"]`);
        const decreaseBtn = $(`.quantity-change[data-action="decrease"][data-id="${cartKey}"]`);
        const increaseBtn = $(`.quantity-change[data-action="increase"][data-id="${cartKey}"]`);

        if (!input.length || !decreaseBtn.length || !increaseBtn.length) return;

        try {
            const currentVal = parseInt(input.val());
            const minVal = parseInt(input.attr('min'));
            const maxVal = parseInt(input.attr('max'));

            decreaseBtn.prop('disabled', currentVal <= minVal);
            increaseBtn.prop('disabled', !isNaN(maxVal) && currentVal >= maxVal);
        } catch (e) {
            console.error("Error updating quantity buttons state:", e);
            decreaseBtn.prop('disabled', true);
            increaseBtn.prop('disabled', true);
        }
    }

    function showItemLoader(cartKey) {
        $(`.cart-item[data-id="${cartKey}"] .loading-overlay`).removeClass('hidden');
    }
    function hideItemLoader(cartKey) {
        $(`.cart-item[data-id="${cartKey}"] .loading-overlay`).addClass('hidden');
    }
    function showGlobalError(message) {
        $('#ajax-error-message').html('<strong>Error:</strong> ' + message).removeClass('hidden').slideDown();
        setTimeout(() => {
            $('#ajax-error-message').slideUp();
        }, 5000);
    }

    let updateTimeout;

    // --- UPDATE KUANTITAS (+/-) ---
    $('#cart-content-area').on('click', '.quantity-change', function () {
        const cartKey = $(this).data('id');
        const action = $(this).data('action');
        const input = $(`.quantity-input[data-id="${cartKey}"]`);
        let currentVal = parseInt(input.val());
        const minVal = parseInt(input.attr('min'));
        const maxVal = parseInt(input.attr('max'));

        if (isNaN(currentVal)) currentVal = minVal;

        if (action === 'increase') {
            if (!isNaN(maxVal) && currentVal >= maxVal) return;
            currentVal++;
        } else if (action === 'decrease') {
            currentVal--;
        }

        if (currentVal >= minVal) {
            input.val(currentVal);
            updateQuantityButtonsState(cartKey);
            triggerUpdate(cartKey, currentVal);
        } else {
            input.val(minVal);
            updateQuantityButtonsState(cartKey);
        }
    });

    // --- UPDATE KUANTITAS (INPUT TYPING) ---
    $('#cart-content-area').on('input', '.quantity-input', function () {
        clearTimeout(updateTimeout);
        const cartKey = $(this).data('id');
        const input = $(this);
        let quantity = parseInt(input.val());
        const minVal = parseInt(input.attr('min'));
        const maxVal = parseInt(input.attr('max'));

        if (!isNaN(maxVal) && quantity > maxVal) {
            quantity = maxVal;
            input.val(quantity);
        }

        updateQuantityButtonsState(cartKey);

        updateTimeout = setTimeout(() => {
            quantity = parseInt(input.val());
            if (isNaN(quantity) || quantity < minVal) {
                quantity = minVal;
                input.val(quantity);
            }
            if (!isNaN(maxVal) && quantity > maxVal) {
                quantity = maxVal;
                input.val(quantity);
            }

            triggerUpdate(cartKey, quantity);
        }, 750);
    });

    function triggerUpdate(cartKey, quantity) {
        const statusEl = $(`.update-status[data-id="${cartKey}"]`);
        const inputEl = $(`.quantity-input[data-id="${cartKey}"]`);
        statusEl.html('<div class="loader !w-3 !h-3 !border-2"></div> Updating...').removeClass('text-red-500');
        showItemLoader(cartKey);

        $.ajax({
            url: '{{ Route::has('customer.cart.update') ? route('customer.cart.update') : route('cart.update') }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                _method: 'PUT', {{-- Menjaga kompatibilitas jika rute update menggunakan PUT/PATCH --}}
                id: cartKey,
                quantity: quantity
            },
            success: function (response) {
                if (response.success) {
                    $(`.cart-item[data-id="${cartKey}"] .item-subtotal`).text(formatCurrency(response.subtotal));
                    updateSummary(response.total, $('.cart-item').length);
                    statusEl.text('Updated!').fadeIn().delay(1500).fadeOut();
                    inputEl.val(response.quantity);
                    if (response.current_stock !== undefined) {
                        inputEl.attr('max', response.current_stock);
                    }
                    updateQuantityButtonsState(cartKey);
                } else {
                    statusEl.text('Error').addClass('text-red-500').fadeIn().delay(2000).fadeOut(function() { $(this).removeClass('text-red-500'); });
                    showGlobalError(response.message || 'Gagal memperbarui kuantitas.');
                    updateQuantityButtonsState(cartKey);
                }
            },
            error: function (xhr) {
                let errorMsg = 'Terjadi kesalahan.';
                let removed = false;
                let newTotal = 0;
                let itemCount = $('.cart-item').length;
                let currentStock = null;

                if (xhr.responseJSON) {
                    errorMsg = xhr.responseJSON.message || errorMsg;
                    removed = xhr.responseJSON.removed || false;
                    newTotal = xhr.responseJSON.total ?? 0;
                    if (xhr.status === 422 && errorMsg.includes('Stok tidak mencukupi')) {
                        const match = errorMsg.match(/tersisa: (\d+)/);
                        if (match && match[1]) {
                            currentStock = parseInt(match[1]);
                            inputEl.attr('max', currentStock);
                            if (parseInt(inputEl.val()) > currentStock || currentStock <= 0) {
                                inputEl.val(currentStock > 0 ? currentStock : 1);
                            }
                            quantity = parseInt(inputEl.val());
                        } else {
                            inputEl.attr('max', quantity);
                        }
                    }
                }

                statusEl.text('Error!').addClass('text-red-500').fadeIn().delay(3000).fadeOut(function() { $(this).removeClass('text-red-500'); });
                showGlobalError(errorMsg);

                if (removed) {
                    $(`.cart-item[data-id="${cartKey}"]`).remove();
                    itemCount = $('.cart-item').length;
                    updateSummary(newTotal, itemCount);
                    checkEmptyCart();
                } else {
                    updateQuantityButtonsState(cartKey);
                }
            },
            complete: function() {
                hideItemLoader(cartKey);
                updateQuantityButtonsState(cartKey);
            }
        });
    }

    // --- HAPUS ITEM (AJAX DELETE) ---
    $('#cart-content-area').on('click', '.remove-item', function () {
        if (!confirm('Anda yakin ingin menghapus item ini dari keranjang?')) {
            return;
        }

        const cartKey = $(this).data('id');
        const itemElement = $(`.cart-item[data-id="${cartKey}"]`);
        showItemLoader(cartKey);

        $.ajax({
            url: '{{ Route::has('customer.cart.remove') ? route('customer.cart.remove') : route('cart.remove') }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                _method: 'DELETE', {{-- FIX: Method spoofing agar cocok dengan Route::delete --}}
                id: cartKey
            },
            success: function (response) {
                if (response.success) {
                    itemElement.fadeOut(300, function() {
                        $(this).remove();
                        updateSummary(response.total, $('.cart-item').length);
                        checkEmptyCart();
                    });
                } else {
                    hideItemLoader(cartKey);
                    showGlobalError(response.message || 'Gagal menghapus item.');
                }
            },
            error: function (xhr) {
                hideItemLoader(cartKey);
                let errorMsg = 'Terjadi kesalahan saat menghapus item.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                }
                showGlobalError(errorMsg);
            }
        });
    });

    function checkEmptyCart() {
        const cartContent = $('#cart-content-area');
        if (cartContent.find('ul[role="list"]').length > 0 && cartContent.find('.cart-item').length === 0) {
            cartContent.html(`
                <div class="text-center bg-white p-12 rounded-xl shadow-sm col-span-1 lg:col-span-3">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path vector-effect="non-scaling-stroke" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    <h3 class="mt-2 text-lg font-medium text-gray-900">Keranjang Anda Kosong</h3>
                    <p class="mt-1 text-sm text-gray-500">Ayo cari produk menarik!</p>
                    <div class="mt-6">
                        <a href="{{ Route::has('etalase.index') ? route('etalase.index') : '#' }}" class="inline-flex items-center rounded-md border border-transparent btn-shopee-solid px-4 py-2 text-sm font-medium text-white shadow-sm hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2">
                           <i class="fas fa-shopping-bag mr-2"></i> Mulai Belanja
                        </a>
                    </div>
                </div>
            `);
        }
    }

    $('body').on('click', '.close-alert', function() {
        $(this).closest('[role="alert"]').slideUp();
    });

    checkEmptyCart();

    $('.cart-item').each(function() {
        const cartKey = $(this).data('id');
        updateQuantityButtonsState(cartKey);
    });
});
</script>
@endpush
