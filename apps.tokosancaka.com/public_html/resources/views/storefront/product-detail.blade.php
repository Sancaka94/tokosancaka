@extends('layouts.marketplace')

@section('title', $product->name)

@section('content')
<div class="bg-gray-50 py-8 min-h-screen"
     x-data="productPage()"
     x-init="initData()">

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        {{-- BREADCRUMB --}}
        <nav class="flex text-sm text-gray-500 mb-6 items-center">
            <a href="{{ route('storefront.index', $subdomain) }}" class="hover:text-blue-600 font-medium">Beranda</a>
            <i data-lucide="chevron-right" class="w-4 h-4 mx-2"></i>
            <span class="text-gray-800 font-bold truncate">{{ $product->name }}</span>
        </nav>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden flex flex-col md:flex-row">

            {{-- KIRI: GALERI FOTO --}}
            <div class="md:w-2/5 p-4 md:p-6 flex flex-col bg-white border-r border-gray-100">
                <div class="aspect-square w-full relative overflow-hidden rounded-xl bg-gray-50 flex justify-center items-center shadow-inner mb-4">
                    <template x-if="mainImage">
                        <img :src="mainImage" alt="{{ $product->name }}" class="w-full h-full object-contain transition-all duration-300">
                    </template>
                    <template x-if="!mainImage">
                        <div class="text-gray-300 flex flex-col items-center">
                            <i data-lucide="image" class="w-20 h-20"></i>
                            <p class="text-xs font-bold mt-2">No Image</p>
                        </div>
                    </template>
                </div>

                <div class="flex gap-3 overflow-x-auto pb-2 snap-x hide-scrollbar">
                    <template x-for="(img, index) in gallery" :key="index">
                        <div @click="mainImage = img"
                             class="w-16 h-16 md:w-20 md:h-20 flex-shrink-0 rounded-lg overflow-hidden cursor-pointer border-2 transition-all duration-200 snap-start"
                             :class="mainImage === img ? 'border-blue-600 shadow-md' : 'border-transparent hover:border-gray-300 bg-gray-50'">
                            <img :src="img" class="w-full h-full object-cover">
                        </div>
                    </template>
                </div>
            </div>

            {{-- KANAN: INFO & PILIHAN --}}
            <div class="md:w-3/5 p-6 md:p-8 flex flex-col">

                <div class="flex flex-wrap items-center gap-2 mb-3">
                    @if($product->is_flash_sale)
                        <span class="bg-orange-600 text-white text-[10px] font-bold px-2 py-0.5 rounded flex items-center gap-1">
                            <i data-lucide="zap" class="w-3 h-3 fill-current"></i> FLASH SALE
                        </span>
                    @endif
                    <div class="flex items-center text-yellow-400 text-sm">
                        <i data-lucide="star" class="w-4 h-4 fill-current"></i>
                        <span class="text-gray-700 ml-1 font-medium">{{ $product->rating ?? '5.0' }}</span>
                        <span class="text-gray-300 mx-2">|</span>
                        <span class="text-gray-600">{{ $product->sold ?? 0 }} Terjual</span>
                    </div>
                </div>

                <h1 class="text-2xl md:text-3xl font-bold text-gray-900 mb-4 leading-snug">{{ $product->name }}</h1>

                {{-- HARGA DINAMIS --}}
                <div class="bg-gray-50 p-5 rounded-xl mb-6 border border-gray-100">
                    <div class="flex items-center gap-3">
                        <span class="text-3xl md:text-4xl font-black text-blue-700">Rp <span x-text="formatPrice(currentPrice)"></span></span>

                        <template x-if="basePrice > currentPrice && !selectedSubVariant">
                            <span class="bg-red-100 text-red-600 text-xs font-black px-2 py-1 rounded">
                                -<span x-text="Math.round(((basePrice - currentPrice) / basePrice) * 100)"></span>%
                            </span>
                        </template>
                    </div>
                    <template x-if="basePrice > currentPrice && !selectedSubVariant">
                        <div class="text-gray-400 line-through text-sm mt-1">
                            Rp <span x-text="formatPrice(basePrice)"></span>
                        </div>
                    </template>
                </div>

                {{-- PILIHAN VARIAN LEVEL 1 --}}
                <template x-if="variants.length > 0">
                    <div class="mb-5">
                        <h3 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-3">Pilih Varian:</h3>
                        <div class="flex flex-wrap gap-3">
                            <template x-for="variant in variants" :key="variant.id">
                                <button @click="selectVariant(variant)"
                                        class="px-4 py-2 border rounded-lg text-sm font-semibold transition-all"
                                        :class="selectedVariant && selectedVariant.id === variant.id ? 'border-blue-600 bg-blue-50 text-blue-700 ring-1 ring-blue-600' : 'border-gray-200 text-gray-600 hover:border-blue-400'">
                                    <span x-text="variant.name"></span>
                                </button>
                            </template>
                        </div>
                    </div>
                </template>

                {{-- PILIHAN SUB VARIAN LEVEL 2 (Nongol kalau Varian 1 sudah dipilih & punya anak) --}}
                <template x-if="selectedVariant && selectedVariant.sub_variants && selectedVariant.sub_variants.length > 0">
                    <div class="mb-6 animate-fade-in-down">
                        <h3 class="text-xs font-bold text-orange-500 uppercase tracking-widest mb-3">Pilih Ukuran/Tipe:</h3>
                        <div class="flex flex-wrap gap-3">
                            <template x-for="sub in selectedVariant.sub_variants" :key="sub.id">
                                <button @click="selectSubVariant(sub)"
                                        :disabled="sub.stock <= 0"
                                        class="px-4 py-2 border rounded-lg text-sm font-semibold transition-all"
                                        :class="{
                                            'border-orange-500 bg-orange-50 text-orange-700 ring-1 ring-orange-500': selectedSubVariant && selectedSubVariant.id === sub.id,
                                            'border-gray-200 text-gray-600 hover:border-orange-400': (!selectedSubVariant || selectedSubVariant.id !== sub.id) && sub.stock > 0,
                                            'border-gray-100 bg-gray-50 text-gray-300 cursor-not-allowed': sub.stock <= 0
                                        }">
                                    <span x-text="sub.name"></span>
                                    <template x-if="sub.stock <= 0"><span class="text-[8px] block">(Habis)</span></template>
                                </button>
                            </template>
                        </div>
                    </div>
                </template>

                {{-- INFO PRODUK --}}
                <div class="grid grid-cols-2 gap-y-3 gap-x-4 mb-8 text-sm border-t border-gray-100 pt-6">
                    <div class="text-gray-500">Kondisi</div>
                    <div class="font-semibold text-gray-800 text-right">Baru</div>
                    <div class="text-gray-500">Berat</div>
                    <div class="font-semibold text-gray-800 text-right"><span x-text="currentWeight"></span> Gram</div>
                    <div class="text-gray-500">Stok Tersedia</div>
                    <div class="font-bold text-blue-600 text-right"><span x-text="currentStock"></span> {{ $product->unit ?? 'pcs' }}</div>
                </div>

                {{-- TOMBOL BELI --}}
                <div class="mt-auto">
                    <button @click="handleAddToCart()"
                            :disabled="!isReadyToBuy"
                            class="w-full px-6 py-4 rounded-xl font-bold text-lg shadow-lg transition-all duration-200 flex items-center justify-center gap-3 active:scale-[0.98]"
                            :class="isReadyToBuy ? 'bg-blue-600 text-white hover:bg-blue-700 shadow-blue-200' : 'bg-gray-200 text-gray-400 cursor-not-allowed shadow-none'">
                        <i data-lucide="shopping-cart" class="w-6 h-6"></i>
                        <span x-text="currentStock <= 0 ? 'Stok Habis' : 'Masukkan Keranjang'"></span>
                    </button>

                    <p x-show="!isReadyToBuy && currentStock > 0" class="text-center text-xs text-orange-500 mt-3 font-medium">
                        <i class="fas fa-exclamation-circle mr-1"></i> Selesaikan pilihan varian Anda
                    </p>
                </div>

            </div>
        </div>

        {{-- DESKRIPSI --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 md:p-8 mt-6">
            <h3 class="text-lg font-bold text-gray-900 border-b border-gray-200 pb-3 mb-4">Deskripsi Produk</h3>
            <div class="prose max-w-none text-gray-700 text-sm leading-relaxed whitespace-pre-line">
                {{ $product->description ?? 'Tidak ada deskripsi detail untuk produk ini.' }}
            </div>
        </div>

    </div>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('productPage', () => ({
            // Data Induk dari DB
            productName: '{{ $product->name }}',
            productId: {{ $product->id }},
            basePrice: {{ $product->base_price ?? $product->sell_price }},
            baseWeight: {{ $product->weight ?? 0 }},

            // State Realtime
            currentPrice: {{ $product->sell_price }},
            currentStock: {{ $product->stock }},
            currentWeight: {{ $product->weight ?? 0 }},

            mainImage: '{{ $product->image ? asset("storage/".$product->image) : "" }}',
            gallery: ['{{ $product->image ? asset("storage/".$product->image) : "" }}'].filter(Boolean),

            // Relasi Dinamis (Varian -> Sub Varian)
            variants: @json($product->variants->load('subVariants')),
            selectedVariant: null,
            selectedSubVariant: null,

            initData() {
                // Jika ada varian, biarkan kosong dulu agar pembeli memilih
                // Atau aktifkan varian pertama jika mau:
                if(this.variants.length > 0) this.selectVariant(this.variants[0]);
            },

            selectVariant(variant) {
                this.selectedVariant = variant;
                this.selectedSubVariant = null; // Reset anak kalau induk ganti

                // Jika varian ini TIDAK punya anak, langsung update harga/stok
                if (!variant.sub_variants || variant.sub_variants.length === 0) {
                    this.currentPrice = variant.price;
                    this.currentStock = variant.stock;
                } else {
                    // Jika PUNYA anak, reset ke harga terendah anak atau harga induk
                    this.currentPrice = variant.price;
                    this.currentStock = variant.stock;
                }
            },

            selectSubVariant(sub) {
                if(sub.stock <= 0) return;
                this.selectedSubVariant = sub;
                this.currentPrice = sub.price;
                this.currentStock = sub.stock;
                this.currentWeight = sub.weight || this.baseWeight;
            },

            formatPrice(price) {
                return new Intl.NumberFormat('id-ID').format(price);
            },

            // LOGIKA: Apakah tombol beli boleh nyala?
            get isReadyToBuy() {
                // 1. Stok harus ada
                if (this.currentStock <= 0) return false;

                // 2. Jika punya varian, harus dipilih
                if (this.variants.length > 0 && !this.selectedVariant) return false;

                // 3. Jika varian terpilih punya sub varian, sub varian harus dipilih
                if (this.selectedVariant && this.selectedVariant.sub_variants.length > 0 && !this.selectedSubVariant) return false;

                return true;
            },

            handleAddToCart() {
                if(!this.isReadyToBuy) return;

                // Gabungkan Nama (Produk + Varian + Sub)
                let fullName = this.productName;
                if(this.selectedVariant) fullName += ' - ' + this.selectedVariant.name;
                if(this.selectedSubVariant) fullName += ' (' + this.selectedSubVariant.name + ')';

                const payload = {
                    id: this.productId,
                    variant_id: this.selectedVariant ? this.selectedVariant.id : null,
                    sub_variant_id: this.selectedSubVariant ? this.selectedSubVariant.id : null,
                    name: fullName,
                    sell_price: this.currentPrice,
                    weight: this.currentWeight,
                    image: this.mainImage
                };

                // Panggil fungsi addToCart global dari layout
                if (typeof addToCart === 'function') {
                    addToCart(payload);
                } else {
                    console.error("Fungsi addToCart tidak ditemukan di layout induk.");
                }
            }
        }));
    });
</script>
@endsection
