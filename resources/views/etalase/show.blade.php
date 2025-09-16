{{-- resources/views/etalase/show.blade.php --}}
@extends('layouts.marketplace')

@section('title', $product->name . ' - Sancaka Marketplace')

@push('styles')
    {{-- Tailwind CSS --}}
    <script src="https://cdn.tailwindcss.com"></script>
    {{-- Google Fonts: Inter --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* Menggunakan font Inter sebagai default */
        body {
            font-family: 'Inter', sans-serif;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        /* Style untuk thumbnail aktif */
        .thumbnail-active {
            outline: 2px solid #2563eb; /* blue-600 */
            outline-offset: 2px;
        }
        /* Menyembunyikan panah di input number */
        input[type=number]::-webkit-inner-spin-button,
        input[type=number]::-webkit-outer-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
        input[type=number] {
            -moz-appearance: textfield;
        }
    </style>
@endpush

@section('content')
<div class="bg-gray-50 dark:bg-gray-900 text-gray-800 dark:text-gray-200">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Breadcrumb -->
        <nav aria-label="Breadcrumb" class="mb-6 text-sm text-gray-500 dark:text-gray-400">
            <ol class="flex items-center space-x-2">
                <li><a href="#" class="hover:text-blue-600">Sancaka</a></li>
                <li><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-chevron-right"><path d="m9 18 6-6-6-6"/></svg></li>
                <li><a href="#" class="hover:text-blue-600">Sepatu Pria</a></li>
                <li><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-chevron-right"><path d="m9 18 6-6-6-6"/></svg></li>
                <li class="font-medium text-gray-700 dark:text-gray-300 truncate">{{ $product->name }}</li>
            </ol>
        </nav>

        <main class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-4 sm:p-6 lg:p-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-12">
                <!-- Product Images -->
                <div class="image-gallery">
                    <div class="aspect-w-1 aspect-h-1 w-full overflow-hidden rounded-xl shadow-md mb-4">
                      @php

    $imageUrl = $product->image_url
        ? (Illuminate\Support\Str::startsWith($product->image_url, '/storage/')
            ? asset($product->image_url)
            : url('storage/' . $product->image_url))
        : 'https://placehold.co/600x600/E2E8F0/4A5568?text=Image+Not+Found';
@endphp

<img id="main-product-image"
     src="{{ $imageUrl }}"
     alt="{{ $product->name }}"
     class="w-full h-full object-cover object-center transition-transform duration-300 ease-in-out hover:scale-105"
     onerror="this.onerror=null;this.src='https://placehold.co/600x600/E2E8F0/4A5568?text=Image+Not+Found';">
                    </div>
                    <div class="grid grid-cols-5 gap-2 sm:gap-3">
                        {{-- Gambar utama sebagai thumbnail pertama dan aktif --}}
                        <div>
                            <img src="{{ url('storage/' . $product->image_url) }}"
                                 alt="Thumbnail 1" 
                                 class="thumbnail-img w-full h-auto object-cover rounded-lg cursor-pointer transition-all duration-200 hover:opacity-80 thumbnail-active"
                                 onclick="changeImage(this)"
                                 onerror="this.onerror=null;this.src='https://placehold.co/100x100/E2E8F0/4A5568?text=N/A';">
                        </div>

                        {{-- Loop untuk gambar tambahan --}}
                        @if(isset($product->images) && count($product->images) > 0)


@foreach($product->images as $index => $image)
    @php
        $path = is_object($image) ? $image->path : $image;

        $thumbUrl = $path
            ? (Illuminate\Support\Str::startsWith($path, '/storage/')
                ? asset($path)
                : url('storage/' . $path))
            : 'https://placehold.co/100x100/E2E8F0/4A5568?text=N/A';
    @endphp

    <div>
        <img src="{{ $thumbUrl }}"
             alt="Thumbnail {{ $index + 2 }}"
             class="thumbnail-img w-full h-auto object-cover rounded-lg cursor-pointer transition-all duration-200 hover:opacity-80"
             onclick="changeImage(this)"
             onerror="this.onerror=null;this.src='https://placehold.co/100x100/E2E8F0/4A5568?text=N/A';">
    </div>
@endforeach
                        @else
                            {{-- Fallback jika tidak ada gambar tambahan --}}
                            @foreach(range(1, 4) as $i)
                            <div>
                                <img src="{{asset('public/assets/logo.jpg')}}" 
                                     alt="Placeholder Thumbnail {{ $i + 1 }}" 
                                     class="thumbnail-img w-full h-auto object-cover rounded-lg cursor-pointer transition-all duration-200 hover:opacity-80">
                            </div>
                            @endforeach
                        @endif
                    </div>
                </div>


                <!-- Product Info -->
                <div class="product-info">
                    <h1 class="text-2xl sm:text-3xl lg:text-4xl font-extrabold text-gray-900 dark:text-white mb-2">{{ $product->name }}</h1>
                    
                    <div class="flex items-center space-x-4 mb-4 text-sm text-gray-500 dark:text-gray-400">
                        <div class="flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor" class="text-yellow-400 mr-1"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>
                            <span class="font-semibold text-gray-700 dark:text-gray-300">5.0</span>
                            <span class="mx-2">|</span>
                            <span>150 Ulasan</span>
                        </div>
                        <span class="text-gray-300 dark:text-gray-600">|</span>
                        <div class="font-semibold text-blue-600 dark:text-blue-400">{{ $product->sold_count ?? '100+' }} Terjual</div>
                    </div>

                    <div class="bg-gray-100 dark:bg-gray-700 rounded-lg p-4 my-4">
                        <div class="flex items-baseline gap-2">
                           <span class="text-3xl lg:text-4xl font-bold text-red-600 dark:text-red-500">Rp{{ number_format($product->price, 0, ',', '.') }}</span>
                           @if($product->original_price)
                               <span class="text-lg text-gray-400 dark:text-gray-500 line-through">Rp{{ number_format($product->original_price, 0, ',', '.') }}</span>
                           @endif
                        </div>
                         @if($product->original_price && $product->original_price > $product->price)
                            <span class="text-sm font-semibold text-green-600 dark:text-green-400 bg-green-100 dark:bg-green-900/50 px-2 py-1 rounded-full mt-2 inline-block">
                                Diskon {{ round((($product->original_price - $product->price) / $product->original_price) * 100) }}%
                            </span>
                        @endif
                    </div>
                    
                    {{-- PERBAIKAN: Form untuk menambahkan ke keranjang --}}
                    {{-- Berikan ID produk sebagai parameter kedua --}}
                    <form action="{{ route('cart.add', ['product' => $product->id]) }}" method="POST">
                        @csrf
                        <input type="hidden" name="product_id" value="{{ $product->id }}">

                        <div class="mt-6">
                            <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-3">Pilih Ukuran</h3>
                            <div class="flex flex-wrap gap-3">
                                @foreach([39,40,41,42,43,44] as $size)
                                    <button type="button" class="size-option-btn px-4 py-2 border rounded-lg transition duration-200 ease-in-out hover:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 dark:border-gray-600 dark:hover:border-blue-400">{{ $size }}</button>
                                @endforeach
                            </div>
                        </div>

                        <div class="mt-6">
                            <label for="quantity" class="text-base font-semibold text-gray-900 dark:text-white">Kuantitas</label>
                            <div class="flex items-center mt-3">
                                <div class="flex items-center border border-gray-300 dark:border-gray-600 rounded-lg">
                                    <button id="button-minus" type="button" class="px-3 py-2 text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-l-lg transition">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                                    </button>
                                    <input type="number" id="quantity" name="quantity" class="w-16 text-center bg-transparent border-x border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-0" value="1" min="1" max="{{ $product->stock ?? 99 }}">
                                    <button id="button-plus" type="button" class="px-3 py-2 text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-r-lg transition">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                                    </button>
                                </div>
                                <span class="ml-4 text-sm text-gray-500 dark:text-gray-400">Tersisa {{ $product->stock ?? '100+' }} buah</span>
                            </div>
                        </div>
                        
                        <div class="mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <button type="submit" name="action" class="w-full flex items-center justify-center gap-2 px-6 py-3.5 border border-blue-600 text-blue-600 dark:border-blue-500 dark:text-blue-500 font-semibold rounded-lg shadow-sm hover:bg-blue-50 dark:hover:bg-blue-500/10 transition-colors duration-200">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                                    Masukkan Keranjang
                                </button>
                                 <button type="submit" name="action" value="buy"
                                    class="w-full flex items-center justify-center gap-2 px-6 py-3.5 bg-blue-600 text-white font-semibold rounded-lg shadow-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition-colors duration-200">
                                    Beli Sekarang
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </main>
                       @if ($product->store) 
         <!-- Store Info -->
        <div class="mt-8 lg:mt-12 bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
    {{-- Pastikan variabel $product memiliki relasi ke store --}}

    <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4">
        @php
            // Logika untuk menentukan URL logo yang benar
            $logoUrl = 'https://placehold.co/80x80/E2E8F0/4A5568?text=Toko'; // Default
            $userLogoPath = optional($product->store->user)->store_logo_path;

            if (!empty($userLogoPath)) {
                // Prioritas 1: Logo dari tabel Pengguna
                $logoUrl = asset('storage/' . $userLogoPath);
            } elseif (!empty($product->store->seller_logo)) {
                // Prioritas 2: Logo dari tabel stores (jika ada)
                $logoUrl = $product->store->seller_logo;
            }
        @endphp
        <img src="{{ $logoUrl }}" 
             alt="Logo {{ $product->store->name }}" 
             class="w-16 h-16 sm:w-20 sm:h-20 rounded-full border-2 border-gray-200 dark:border-gray-700 object-cover">
        
        
        
                <div class="flex-grow">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ $product->store->name }}</h3>
            <div class="flex items-center text-sm text-gray-500 dark:text-gray-400 mt-1">
                
                {{-- ✅ DIPERBAIKI: Logika status online/offline --}}
                @if ($product->store->user && $product->store->user->last_seen_at)
                    @php
                        // Cek apakah user aktif dalam 5 menit terakhir
                        $isOnline = \Carbon\Carbon::parse($product->store->user->last_seen_at)->gt(\Carbon\Carbon::now()->subMinutes(5));
                    @endphp
                    <span class="w-2.5 h-2.5 {{ $isOnline ? 'bg-green-500' : 'bg-gray-400' }} rounded-full mr-2"></span>
                    <span>Aktif {{ \Carbon\Carbon::parse($product->store->user->last_seen_at)->locale('id')->diffForHumans() }}</span>
                @else
                    <span class="w-2.5 h-2.5 bg-gray-400 rounded-full mr-2"></span>
                    <span>Offline</span>
                @endif
            </div>
            </div>
        
                <div class="flex w-full sm:w-auto flex-col sm:flex-row gap-3 mt-4 sm:mt-0">
                    <!-- Chat Penjual (WhatsApp) -->
                    <div class="flex w-full sm:w-auto flex-col sm:flex-row gap-3 mt-4 sm:mt-0">
                        @if(Auth::check())
                            <a href="https://wa.me/{{ preg_replace('/^0/', '62', $product->seller_wa ?? '') }}" 
                               target="_blank"
                               class="w-full sm:w-auto flex items-center justify-center gap-2 px-4 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg transition-colors hover:bg-gray-100 dark:hover:bg-gray-700">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" 
                                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="m3 21 1.65-3.8a9 9 0 1 1 3.4 2.9l-5.05.9z"/>
                                </svg>
                                Chat Penjual
                            </a>
                        @else
                            <button type="button"
                                    data-modal-target="waModal"
                                    data-modal-toggle="waModal"
                                    class="w-full sm:w-auto flex items-center justify-center gap-2 px-4 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg opacity-80 cursor-pointer">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" 
                                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="m3 21 1.65-3.8a9 9 0 1 1 3.4 2.9l-5.05.9z"/>
                                </svg>
                                Chat Penjual
                            </button>
                        @endif
                    </div>
                    
                    <div id="waModal" tabindex="-1" aria-hidden="true" 
                         class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
                        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl max-w-md w-full p-6 relative">
                            
                            <!-- Logo -->
                            <div class="flex justify-center mb-4">
                                <img src="{{ asset('public/assets/logo.jpg') }}" alt="Logo Toko" 
                                     class="w-16 h-16 shadow-md">
                            </div>
                            
                            <!-- Judul -->
                            <h2 class="text-xl font-bold text-center text-gray-800 dark:text-white mb-3">
                                Mohon Maaf
                            </h2>
                            
                            <!-- Pesan -->
                            <p class="text-center text-gray-600 dark:text-gray-300 mb-6 leading-relaxed">
                                <strong>Apakah kakak ingin menghubungi Penjual Via Whatsapp???</strong><br>
                                Jika iya, silahkan klik tombol di bawah ini untuk mendaftar sebagai <span class="font-semibold">customer terdaftar</span>.
                            </p>
                            
                            <!-- Tombol -->
                            <div class="flex justify-center gap-3">
                                <a href="/customer/register" 
                                   class="px-5 py-2 bg-green-500 text-white font-medium rounded-lg hover:bg-green-600 transition">
                                    Lanjut WA
                                </a>
                                <button type="button" 
                                        data-modal-hide="waModal"
                                        class="px-5 py-2 bg-gray-300 dark:bg-gray-700 text-gray-800 dark:text-white font-medium rounded-lg hover:bg-gray-400 dark:hover:bg-gray-600 transition">
                                    Close
                                </button>
                            </div>
                        </div>
                      {{-- ✅ PERBAIKAN: Menambahkan @endif yang hilang --}}

                    </div>
        
                     <!-- Kunjungi Toko -->
<a href="{{url('/toko/profile/'.($product->store->slug ?? $product->store->name)) }}" class="w-full sm:w-auto flex items-center justify-center gap-2 px-4 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21 7-4-4-4 4M17 3v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V3"/><path d="M7 21h10"/><path d="M12 16v5"/></svg>
Kunjungi Toko
</a>
                     
                </div>
            </div>
           
        </div>
          @endif 
        
       <div class="mt-8 lg:mt-12 bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
            <div id="short-description" class="">
                {!! nl2br(e(Str::limit($product->description, 200))) !!}
            </div>
        
            <div id="full-description" class="hidden">
                {!! nl2br(e($product->description)) !!}
            </div>
        
            <div class="flex justify-center">
                <button id="toggle-description" 
                    class="mt-4 px-6 py-2 bg-blue-600 text-white rounded-lg shadow hover:bg-blue-700 transition">
                    Baca Selengkapnya
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.querySelectorAll('[data-modal-toggle]').forEach(btn => {
    btn.addEventListener('click', () => {
        const target = document.getElementById(btn.getAttribute('data-modal-target'));
        target.classList.remove('hidden');
    });
});

document.querySelectorAll('[data-modal-hide]').forEach(btn => {
    btn.addEventListener('click', () => {
        const target = document.getElementById(btn.getAttribute('data-modal-hide'));
        target.classList.add('hidden');
    });
});
</script>

<script>
    document.getElementById("toggle-description").addEventListener("click", function () {
        const shortDesc = document.getElementById("short-description");
        const fullDesc = document.getElementById("full-description");
        const btn = document.getElementById("toggle-description");

        if (fullDesc.classList.contains("hidden")) {
            shortDesc.classList.add("hidden");
            fullDesc.classList.remove("hidden");
            btn.textContent = "Read Less";
        } else {
            shortDesc.classList.remove("hidden");
            fullDesc.classList.add("hidden");
            btn.textContent = "Read More";
        }
    });
</script>

<script>
    // Menunggu DOM siap sebelum menjalankan skrip
    document.addEventListener('DOMContentLoaded', () => {

        const mainImage = document.getElementById('main-product-image');
        const thumbnails = document.querySelectorAll('.thumbnail-img');
        
        // Fungsi untuk mengganti gambar utama
        window.changeImage = function(element) {
            // Jangan lakukan apa-apa jika sumber gambar adalah placeholder
            if (element.src.includes('placehold.co')) {
                return;
            }
            
            // Mengganti src gambar utama dengan src thumbnail yang diklik
            mainImage.src = element.src;

            // Menghapus kelas 'thumbnail-active' dari semua thumbnail
            thumbnails.forEach(thumb => thumb.classList.remove('thumbnail-active'));
            
            // Menambahkan kelas 'thumbnail-active' ke thumbnail yang diklik
            element.classList.add('thumbnail-active');
        }

        // Logika untuk tombol kuantitas
        const quantityInput = document.getElementById('quantity');
        const minusButton = document.getElementById('button-minus');
        const plusButton = document.getElementById('button-plus');
        const maxStock = parseInt(quantityInput.max) || 999;

        minusButton.addEventListener('click', () => {
            let currentValue = parseInt(quantityInput.value);
            if (currentValue > 1) {
                quantityInput.value = currentValue - 1;
            }
        });

        plusButton.addEventListener('click', () => {
            let currentValue = parseInt(quantityInput.value);
            if(currentValue < maxStock) {
               quantityInput.value = currentValue + 1;
            }
        });

        // Logika untuk pilihan ukuran
        const sizeButtons = document.querySelectorAll('.size-option-btn');
        let selectedSize = null;

        sizeButtons.forEach(button => {
            button.addEventListener('click', () => {
                // Hapus style aktif dari semua tombol
                sizeButtons.forEach(btn => {
                    btn.classList.remove('bg-blue-600', 'text-white', 'border-blue-600');
                    btn.classList.add('dark:border-gray-600');
                });
                
                // Tambahkan style aktif ke tombol yang diklik
                button.classList.add('bg-blue-600', 'text-white', 'border-blue-600');
                button.classList.remove('dark:border-gray-600');
                
                selectedSize = button.textContent;
                console.log('Ukuran dipilih:', selectedSize);
            });
        });
    });
</script>
@endpush
