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
        /* Style tambahan untuk tampilan yang lebih baik */
        body { font-family: 'Inter', sans-serif; }
        input[type=number]::-webkit-inner-spin-button,
        input[type=number]::-webkit-outer-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
        input[type=number] { -moz-appearance: textfield; }

        /* Loader simple */
        .loader {
            border: 4px solid #f3f3f3; /* Light grey */
            border-top: 4px solid #3498db; /* Blue */
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
        }
         /* Shopee Button Styles */
         .btn-shopee-outline {
             background-color: rgba(255, 87, 34, 0.1);
             border: 1px solid #EE4D2D;
             color: #EE4D2D;
             transition: background-color 0.2s ease;
         }
         .btn-shopee-outline:hover {
             background-color: rgba(255, 87, 34, 0.15);
         }
         .btn-shopee-solid {
             background-color: #EE4D2D;
             border: 1px solid #EE4D2D;
             color: white;
              transition: background-color 0.2s ease;
         }
         .btn-shopee-solid:hover {
             background-color: #d73210; /* Slightly darker orange */
         }
    </style>
@endpush

@section('content')

<div class="bg-gray-100 dark:bg-gray-900 min-h-screen">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <h1 class="text-2xl sm:text-3xl font-extrabold text-gray-900 dark:text-white mb-8">Keranjang Belanja Anda</h1>

        {{-- Notifikasi --}}
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
                <span class="block sm:inline">{{ session('error') }}</span>
                <span class="absolute top-0 bottom-0 right-0 px-4 py-3 close-alert cursor-pointer">
                    <svg class="fill-current h-6 w-6 text-red-500" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><title>Close</title><path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z"/></svg>
                </span>
            </div>
        @endif

        {{-- Global error message area for AJAX --}}
        <div id="ajax-error-message" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4 hidden" role="alert"></div>


        {{-- Cek apakah keranjang ada isinya --}}
        @php $cart = session('cart', []); @endphp
        @if(!empty($cart))
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

                <!-- Daftar Item Keranjang (Kolom Kiri) -->
                <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-xl shadow-sm">
                     <div class="p-4 sm:p-6 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                         <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Item di Keranjang</h2>
                         {{-- Pastikan route 'cart.clear' ada dan metodenya POST --}}
                         @if(Route::has('cart.clear'))
                         <form action="{{ route('cart.clear') }}" method="POST" onsubmit="return confirm('Anda yakin ingin mengosongkan keranjang?');">
                             @csrf
                             <button type="submit" class="text-sm font-medium text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300">
                                 <i class="fas fa-trash-alt mr-1"></i> Kosongkan Keranjang
                            </button>
                        </form>
                        @endif
                    </div>
                    <ul role="list" class="divide-y divide-gray-200 dark:divide-gray-700">
                        @php $totalWeight = 0; @endphp
                        @foreach($cart as $cartKey => $details)
                            {{-- Calculate total weight if available --}}
                            {{-- @php $totalWeight += ($details['weight'] ?? 0) * $details['quantity']; @endphp --}}
                            <li class="flex flex-col sm:flex-row py-4 sm:py-6 px-4 sm:px-6 cart-item relative" data-id="{{ $cartKey }}">
                                <div class="h-24 w-24 sm:h-28 sm:w-28 flex-shrink-0 overflow-hidden rounded-md border border-gray-200 dark:border-gray-700">
                                    <img src="{{ $details['image_url'] ? asset('storage/' . $details['image_url']) : 'https://placehold.co/112x112/EFEFEF/AAAAAA?text=N/A' }}"
                                         alt="{{ $details['name'] ?? 'Produk' }}"
                                         class="h-full w-full object-cover object-center"
                                         onerror="this.onerror=null;this.src='https://placehold.co/112x112/EFEFEF/AAAAAA?text=Error';">
                                </div>

                                <div class="ml-0 sm:ml-4 mt-4 sm:mt-0 flex flex-1 flex-col">
                                    <div>
                                        <div class="flex justify-between text-base font-medium text-gray-900 dark:text-white">
                                            <h3>
                                                {{-- Link kembali ke halaman produk (gunakan slug jika ada) --}}
                                                {{-- Pastikan route 'products.show' ada dan menerima slug --}}
                                                @if(isset($details['slug']) && Route::has('products.show'))
                                                    <a href="{{ route('products.show', $details['slug']) }}" class="hover:text-orange-600 dark:hover:text-orange-400">{{ $details['name'] ?? 'Nama Produk Tidak Tersedia' }}</a>
                                                @else
                                                     <span>{{ $details['name'] ?? 'Nama Produk Tidak Tersedia' }}</span>
                                                 @endif
                                            </h3>
                                            <p class="ml-4 item-subtotal">Rp{{ number_format(($details['price'] ?? 0) * ($details['quantity'] ?? 0), 0, ',', '.') }}</p>
                                        </div>
                                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Rp{{ number_format($details['price'] ?? 0, 0, ',', '.') }} / item</p>
                                    </div>
                                    <div class="flex flex-1 items-end justify-between text-sm mt-4">
                                        {{-- Update Kuantitas (AJAX) --}}
                                        <div class="flex items-center">
                                            <button type="button" class="quantity-change px-2 py-1 border border-gray-300 rounded-l dark:border-gray-600 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700" data-action="decrease" data-id="{{ $cartKey }}">
                                                <i class="fas fa-minus text-xs"></i>
                                            </button>
                                            <input type="number"
                                                   class="quantity-input w-12 h-8 text-center text-sm border-t border-b border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-1 focus:ring-orange-500 dark:bg-gray-700 dark:text-white"
                                                   value="{{ $details['quantity'] ?? 1 }}"
                                                   min="1"
                                                   {{-- Tambahkan max stock jika tersedia --}}
                                                   {{-- max="{{ $details['current_stock'] ?? 99 }}" --}}
                                                   data-id="{{ $cartKey }}">
                                            <button type="button" class="quantity-change px-2 py-1 border border-gray-300 rounded-r dark:border-gray-600 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700" data-action="increase" data-id="{{ $cartKey }}">
                                                <i class="fas fa-plus text-xs"></i>
                                            </button>
                                            <span class="update-status ml-2 text-xs text-gray-500" data-id="{{ $cartKey }}"></span> {{-- Status update --}}
                                        </div>

                                        {{-- Tombol Hapus (AJAX) --}}
                                        <div class="flex">
                                            <button type="button" class="remove-item font-medium text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300" data-id="{{ $cartKey }}">
                                                <i class="fas fa-trash-alt mr-1"></i> Hapus
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                {{-- Loading overlay for item --}}
                                <div class="loading-overlay hidden absolute inset-0 bg-white dark:bg-gray-800 bg-opacity-75 dark:bg-opacity-75 flex items-center justify-center rounded-md">
                                    <div class="loader"></div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <!-- Ringkasan Pesanan (Kolom Kanan) -->
                <div class="lg:col-span-1">
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 sticky top-24"> {{-- Make summary sticky --}}
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Ringkasan Pesanan</h2>
                        <div class="mt-6 space-y-4 border-b border-gray-200 dark:border-gray-700 pb-4">
                            <div class="flex items-center justify-between">
                                <dt class="text-sm text-gray-600 dark:text-gray-400">Subtotal (<span id="total-items">{{ count($cart) }}</span> item)</dt>
                                <dd class="text-sm font-medium text-gray-900 dark:text-white" id="subtotal-amount">Rp{{ number_format(array_sum(array_map(fn($item) => ($item['price'] ?? 0) * ($item['quantity'] ?? 0), $cart)), 0, ',', '.') }}</dd>
                            </div>
                           {{-- Add Shipping Estimation if needed --}}
                           {{-- <div class="flex items-center justify-between">
                                <dt class="text-sm text-gray-600 dark:text-gray-400">Estimasi Ongkir</dt>
                                <dd class="text-sm font-medium text-gray-900 dark:text-white" id="shipping-amount">Rp?</dd>
                           </div> --}}
                        </div>
                        <div class="flex items-center justify-between border-t border-gray-200 dark:border-gray-700 pt-4 mt-4">
                            <dt class="text-base font-semibold text-gray-900 dark:text-white">Total Pesanan</dt>
                            <dd class="text-base font-semibold text-gray-900 dark:text-white" id="total-amount">Rp{{ number_format(array_sum(array_map(fn($item) => ($item['price'] ?? 0) * ($item['quantity'] ?? 0), $cart)), 0, ',', '.') }}</dd>
                        </div>
                        <div class="mt-6">
                            {{-- Gunakan route dari group 'customer' --}}
                            <a href="{{ route('customer.checkout.index') }}" class="flex w-full items-center justify-center rounded-md border border-transparent btn-shopee-solid px-6 py-3 text-base font-medium text-white shadow-sm hover:opacity-90 {{ empty($cart) ? 'opacity-50 pointer-events-none' : '' }}">
                                Lanjut ke Checkout
                            </a>
                        </div>
                        <div class="mt-6 flex justify-center text-center text-sm text-gray-500 dark:text-gray-400">
                            <p>
                                atau
                                {{-- Pastikan route 'etalase.index' ada --}}
                                <a href="{{ route('etalase.index') }}" class="font-medium text-orange-600 hover:text-orange-500 dark:text-orange-500 dark:hover:text-orange-400">
                                    Lanjut Belanja
                                    <span aria-hidden="true"> &rarr;</span>
                                </a>
                            </p>
                        </div>
                    </div>
                </div>

            </div>
        @else
            {{-- Tampilan jika keranjang kosong --}}
            <div class="text-center bg-white dark:bg-gray-800 p-12 rounded-xl shadow-sm">
                 <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                     <path vector-effect="non-scaling-stroke" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                 </svg>
                <h3 class="mt-2 text-lg font-medium text-gray-900 dark:text-white">Keranjang Anda Kosong</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Ayo cari produk menarik dan tambahkan ke keranjang!</p>
                <div class="mt-6">
                    {{-- Pastikan route 'etalase.index' ada --}}
                    <a href="{{ route('etalase.index') }}" class="inline-flex items-center rounded-md border border-transparent btn-shopee-solid px-4 py-2 text-sm font-medium text-white shadow-sm hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900">
                       <i class="fas fa-shopping-bag mr-2"></i> Mulai Belanja
                    </a>
                </div>
            </div>
        @endif
    </div>
</div>

@endsection

@push('scripts')
{{-- jQuery for simpler AJAX --}}
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
$(document).ready(function () {
    // Function to format currency
    function formatCurrency(number) {
        // Handle potential non-numeric input gracefully
        number = Number(number);
        if (isNaN(number)) {
            return 'Rp0'; // Or some default value
        }
        return 'Rp' + Math.round(number).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    }


    // Function to update summary totals
    function updateSummary(total, itemCount) {
        $('#subtotal-amount').text(formatCurrency(total));
        $('#total-amount').text(formatCurrency(total)); // Assuming no shipping/taxes for now
        $('#total-items').text(itemCount);
         // Disable checkout if cart is empty
         // Gunakan route dari group 'customer'
         $('a[href="{{ route('customer.checkout.index') }}"]').toggleClass('opacity-50 pointer-events-none', itemCount <= 0);
    }

    // Function to show item loader
    function showItemLoader(cartKey) {
        $(`.cart-item[data-id="${cartKey}"] .loading-overlay`).removeClass('hidden');
    }
    // Function to hide item loader
    function hideItemLoader(cartKey) {
         $(`.cart-item[data-id="${cartKey}"] .loading-overlay`).addClass('hidden');
    }
    // Function to display global AJAX error
    function showGlobalError(message) {
        $('#ajax-error-message').html('<strong>Error:</strong> ' + message).removeClass('hidden'); // Use html() for potential strong tag
         // Automatically hide after 5 seconds
         setTimeout(() => {
             $('#ajax-error-message').addClass('hidden');
         }, 5000);
    }

    // --- AJAX Cart Update ---
    let updateTimeout; // To debounce input changes

    // Update via buttons +/-
    $('.quantity-change').on('click', function () {
        const cartKey = $(this).data('id');
        const action = $(this).data('action');
        const input = $(`.quantity-input[data-id="${cartKey}"]`);
        let currentVal = parseInt(input.val());
        const minVal = parseInt(input.attr('min'));

        if (action === 'increase') {
            // Check max stock if available before increasing
            const maxVal = parseInt(input.attr('max'));
            if (!isNaN(maxVal) && currentVal >= maxVal) return; // Don't increase if at max
            currentVal++;
        } else if (action === 'decrease') {
            currentVal--;
        }

        if (!isNaN(currentVal) && currentVal >= minVal) {
             input.val(currentVal);
             triggerUpdate(cartKey, currentVal); // Trigger AJAX update
        } else if (!isNaN(currentVal) && currentVal < minVal) {
             input.val(minVal); // Reset to min if attempt to go below
             // Optionally trigger update if value changed to minVal
             // triggerUpdate(cartKey, minVal);
        }
    });

    // Update via typing in input (debounced)
    $('.quantity-input').on('input', function () {
        clearTimeout(updateTimeout);
        const cartKey = $(this).data('id');
        const input = $(this);
        let quantity = parseInt(input.val());
        const minVal = parseInt(input.attr('min'));
        const maxVal = parseInt(input.attr('max')); // Get max value if exists

        // Basic validation on input
        if (isNaN(quantity) || quantity < minVal) {
            // Don't immediately reset if user might be deleting to type new number
            // quantity = minVal;
        } else if (!isNaN(maxVal) && quantity > maxVal) {
            quantity = maxVal; // Cap at max value
            input.val(quantity); // Update input immediately if exceeding max
        }


        updateTimeout = setTimeout(() => {
             // Final check before sending AJAX - ensure it's at least minVal
             quantity = parseInt(input.val()); // Re-read the value
             if (isNaN(quantity) || quantity < minVal) {
                quantity = minVal;
                input.val(quantity); // Correct the input value visually
             }
             // Ensure it doesn't exceed maxVal again
             if (!isNaN(maxVal) && quantity > maxVal) {
                 quantity = maxVal;
                 input.val(quantity);
             }

             triggerUpdate(cartKey, quantity);
        }, 750); // Delay before sending AJAX request (750ms)
    });

    function triggerUpdate(cartKey, quantity) {
        const statusEl = $(`.update-status[data-id="${cartKey}"]`);
        const inputEl = $(`.quantity-input[data-id="${cartKey}"]`);
        statusEl.html('<div class="loader !w-3 !h-3 !border-2"></div> Updating...').removeClass('text-red-500'); // Show loader
        showItemLoader(cartKey);

        $.ajax({
            // Gunakan route dari group 'customer' dan method PATCH
            url: '{{ route('customer.cart.update') }}',
            method: 'PATCH', // <--- UBAH METHOD KE PATCH
            data: {
                _token: '{{ csrf_token() }}',
                id: cartKey, // Send cartKey as id
                quantity: quantity
            },
            success: function (response) {
                if (response.success) {
                    $(`.cart-item[data-id="${cartKey}"] .item-subtotal`).text(formatCurrency(response.subtotal));
                    updateSummary(response.total, $('.cart-item').length);
                    statusEl.text('Updated!').fadeIn().delay(1500).fadeOut();
                    inputEl.val(response.quantity); // Sync input value
                    // Update max attribute if available from response (optional)
                    // if (response.current_stock !== undefined) {
                    //     inputEl.attr('max', response.current_stock);
                    // }
                } else {
                    statusEl.text('Error').addClass('text-red-500').fadeIn().delay(2000).fadeOut(function() { $(this).removeClass('text-red-500'); });
                    showGlobalError(response.message || 'Gagal memperbarui kuantitas.');
                     // Optionally revert input value on failure
                     // inputEl.val(response.previous_quantity ?? 1); // Need backend to send this
                }
            },
            error: function (xhr) {
                let errorMsg = 'Terjadi kesalahan.';
                 let removed = false;
                 let newTotal = 0;
                 let itemCount = $('.cart-item').length; // Current count

                if (xhr.responseJSON) {
                    errorMsg = xhr.responseJSON.message || errorMsg;
                    removed = xhr.responseJSON.removed || false;
                    newTotal = xhr.responseJSON.total ?? 0; // Get total if item was removed
                }

                 statusEl.text('Error').addClass('text-red-500').fadeIn().delay(2000).fadeOut(function() { $(this).removeClass('text-red-500'); });
                 showGlobalError(errorMsg);

                 if (removed) {
                    $(`.cart-item[data-id="${cartKey}"]`).remove();
                    itemCount = $('.cart-item').length; // Update count after removal
                    updateSummary(newTotal, itemCount);
                    checkEmptyCart();
                 } else {
                     // Try to revert quantity - find previous quantity from DOM maybe? Less reliable.
                     // Or just leave it and let user correct.
                 }
            },
            complete: function() {
                 hideItemLoader(cartKey);
                 // Re-evaluate button states after update attempt
                 // (Need max stock info for accuracy)
                 // updateQuantityButtonsState(cartKey); // Need a way to pass max stock
            }
        });
    }


    // --- AJAX Cart Remove ---
    $('.remove-item').on('click', function () {
        if (!confirm('Anda yakin ingin menghapus item ini dari keranjang?')) {
            return;
        }

        const cartKey = $(this).data('id');
        const itemElement = $(`.cart-item[data-id="${cartKey}"]`);
        showItemLoader(cartKey);

        $.ajax({
            // Gunakan route dari group 'customer' dan method DELETE
            url: '{{ route('customer.cart.remove') }}',
            method: 'DELETE', // <--- UBAH METHOD KE DELETE
            data: {
                _token: '{{ csrf_token() }}',
                id: cartKey // Send cartKey as id
            },
            success: function (response) {
                if (response.success) {
                    itemElement.fadeOut(300, function() {
                        $(this).remove();
                        updateSummary(response.total, $('.cart-item').length);
                        checkEmptyCart();
                    });
                     // Show success notification if needed
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

    // Function to check if cart is empty and show message
    function checkEmptyCart() {
         // Check if the cart list itself exists before checking children count
         if ($('.lg\\:col-span-2 ul[role="list"]').length > 0 && $('.cart-item').length === 0) {
             // Replace the entire grid container content with the empty cart message
             $('.grid.grid-cols-1.lg\\:grid-cols-3').html(`
                 <div class="text-center bg-white dark:bg-gray-800 p-12 rounded-xl shadow-sm col-span-1 lg:col-span-3">
                     <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                         <path vector-effect="non-scaling-stroke" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                     </svg>
                     <h3 class="mt-2 text-lg font-medium text-gray-900 dark:text-white">Keranjang Anda Kosong</h3>
                     <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Ayo cari produk menarik!</p>
                     <div class="mt-6">
                         <a href="{{ route('etalase.index') }}" class="inline-flex items-center rounded-md border border-transparent btn-shopee-solid px-4 py-2 text-sm font-medium text-white shadow-sm hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900">
                            <i class="fas fa-shopping-bag mr-2"></i> Mulai Belanja
                         </a>
                     </div>
                 </div>
             `);
         }
    }


    // Close alert messages
    $('body').on('click', '.close-alert', function() {
        // Use slideUp for smoother effect
        $(this).closest('[role="alert"]').slideUp();
    });

    // Initial check in case cart becomes empty due to background changes
    checkEmptyCart();

});
</script>
@endpush

