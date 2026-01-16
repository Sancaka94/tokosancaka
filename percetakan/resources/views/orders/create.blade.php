<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Kasir POS - Sancaka</title>

    <link rel="icon" href="https://tokosancaka.com/storage/uploads/sancaka.png" type="image/png">
    <link rel="shortcut icon" href="https://tokosancaka.com/storage/uploads/sancaka.png" type="image/png">

    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        [x-cloak] { display: none !important; }
        /* Scrollbar Style */
        .custom-scrollbar::-webkit-scrollbar { width: 6px; height: 6px; } /* Height added for horizontal scroll */
        .custom-scrollbar::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 4px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

        input[type=number]::-webkit-inner-spin-button,
        input[type=number]::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
        input[type=number] { -moz-appearance: textfield; }
    </style>
</head>
<body class="bg-slate-100 font-sans text-slate-800 h-screen overflow-hidden select-none"
      x-data="posSystem">

    <div class="flex h-full w-full flex-col lg:flex-row overflow-hidden">

        <div class="flex-1 flex flex-col h-full relative border-r border-slate-200">
            <div class="h-16 px-4 bg-slate-300 shadow-sm z-20 flex items-center justify-between shrink-0 border-b border-slate-100">
                <div class="flex items-center gap-2">
                    <div class="h-8 w-8 bg-red-600 rounded-lg flex items-center justify-center text-white font-bold text-lg">
                        <i class="fas fa-print"></i>
                    </div>
                    <h1 class="text-lg font-bold text-slate-800 hidden sm:block">Sancaka POS</h1>
                </div>

                <div class="relative w-full max-w-md mx-4">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-500"><i class="fas fa-search"></i></span>
                    <input type="text" x-model="search" placeholder="Cari layanan / produk..."
                           class="w-full pl-10 pr-10 py-2 rounded-xl bg-slate-100 border-none focus:ring-2 focus:ring-red-500 text-sm font-medium transition-all">
                    <button x-show="search.length > 0" @click="search = ''" class="absolute inset-y-0 right-0 pr-3 text-slate-400 hover:text-red-500">
                        <i class="fas fa-times-circle"></i>
                    </button>
                </div>

                <div class="flex items-center gap-2 shrink-0">
                    <a href="https://tokosancaka.com/percetakan/public/member/login"
                       class="relative p-2.5 bg-white rounded-xl text-slate-600 border border-slate-200 hover:bg-red-50 hover:text-red-600 hover:border-red-200 transition-all shadow-sm flex items-center justify-center w-10 h-10"
                       title="Login Member">
                        <i class="fas fa-user"></i>
                    </a>

                    <button @click="mobileCartOpen = !mobileCartOpen" class="lg:hidden relative p-2.5 bg-red-50 rounded-xl text-red-600 hover:bg-red-100 transition w-10 h-10 flex items-center justify-center border border-transparent hover:border-red-200">
                        <i class="fas fa-shopping-bag"></i>
                        <span x-show="cartTotalQty > 0" class="absolute -top-1 -right-1 bg-red-600 text-white text-[10px] font-bold h-5 w-5 flex items-center justify-center rounded-full border-2 border-white shadow-sm" x-text="cartTotalQty"></span>
                    </button>
                </div>
            </div>

            <div class="flex-1 overflow-y-auto p-4 custom-scrollbar bg-slate-50 relative">

                <div x-data="{ showInfo: true }" x-show="showInfo" x-transition.opacity.duration.300ms
                     class="mb-4 bg-red-50 border border-red-200 rounded-xl p-3 flex items-start gap-3 shadow-sm relative group">
                    <div class="bg-red-100 text-red-600 rounded-lg h-8 w-8 flex items-center justify-center shrink-0">
                        <i class="fas fa-bullhorn text-sm"></i>
                    </div>
                    <div class="flex-1 pr-6">
                        <h4 class="text-xs font-bold text-red-800 uppercase tracking-wide mb-0.5">Info Promo & Affiliasi</h4>
                        <p class="text-[11px] text-red-700 leading-relaxed">
                            Ingin diskon <span class="font-bold">30%</span>? Masukan kode <span class="font-bold bg-white px-1 rounded border border-red-200">KUPON</span> Pada kolom KODE PROMO.
                            Anda juga dapat menjadi <a href="https://tokosancaka.com/percetakan/public/join-partner" target="_blank" class="underline font-bold hover:text-red-900"><strong>Affiliator (Klik Disini)</strong></a> untuk komisi besar.
                        </p>
                    </div>
                    <button @click="showInfo = false" class="absolute top-2 right-2 text-red-400 hover:text-red-700 hover:bg-red-100 rounded-full h-6 w-6 flex items-center justify-center transition-all">
                        <i class="fas fa-times text-xs"></i>
                    </button>
                </div>

                {{--
                    PERBAIKAN:
                    1. -mx-4 : Melebarkan div ke samping (melawan padding parent).
                    2. px-4  : Mengembalikan padding dalam agar teks tidak mepet layar.
                    3. top-0 : Menempel di paling atas saat scroll.
                    4. bg-slate-50 : Warna background agar konten di bawahnya tidak tembus pandang.
                --}}
                <div class="sticky top-0 z-30 bg-slate-50 -mx-4 px-4 pt-2 pb-3 border-b border-slate-200 shadow-sm mb-3">
                    <div class="flex overflow-x-auto gap-2 custom-scrollbar pb-1">
                        <button @click="activeCategory = 'all'"
                            class="flex-shrink-0 px-4 py-2 rounded-xl text-xs font-bold transition-all border shadow-sm flex items-center gap-2"
                            :class="activeCategory === 'all'
                                ? 'bg-red-600 text-white border-red-600 ring-2 ring-red-100'
                                : 'bg-white text-slate-600 border-slate-200 hover:border-red-300 hover:text-red-600'">
                            <i class="fas fa-th-large"></i> Semua
                        </button>

                        @if(isset($categories))
                            @foreach($categories as $cat)
                            <button @click="activeCategory = '{{ $cat->slug }}'"
                                class="flex-shrink-0 px-4 py-2 rounded-xl text-xs font-bold transition-all border shadow-sm whitespace-nowrap"
                                :class="activeCategory === '{{ $cat->slug }}'
                                    ? 'bg-red-600 text-white border-red-600 ring-2 ring-red-100'
                                    : 'bg-white text-slate-600 border-slate-200 hover:border-red-300 hover:text-red-600'">
                                {{ $cat->name }}
                            </button>
                            @endforeach
                        @endif
                    </div>
                </div>

                {{-- KODE BARU: Tambahkan tombol "Semua" di bawah kategori --}}

                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-3 xl:grid-cols-4 gap-3">
                    @forelse($products as $product)

                    {{-- UPDATE: LOGIKA FILTER (Cek Search & Cek Kategori) --}}
                    {{-- Kita ambil slug kategori produk, jika null kita anggap 'retail' atau 'others' --}}
                    @php
                        $prodCatSlug = $product->category->slug ?? 'retail';
                    @endphp

                    <template x-if="itemMatchesSearch('{{ addslashes($product->name) }}') && (activeCategory === 'all' || activeCategory === '{{ $prodCatSlug }}')">

                        <div @click="addToCart({{ $product->id }}, '{{ addslashes($product->name) }}', {{ $product->sell_price }}, {{ $product->stock }}, {{ $product->weight ?? 0 }}, '{{ $product->image ? asset('storage/'.$product->image) : '' }}')"
                             class="relative bg-white rounded-2xl p-3 shadow-sm border border-slate-100 flex flex-col h-full group
                             {{ $product->stock <= 0 ? 'opacity-60 grayscale cursor-not-allowed' : 'cursor-pointer active:scale-95 hover:border-red-300 hover:shadow-md' }} transition-all duration-200">

                            <div class="absolute top-2 left-2 z-10">
                                @if($product->stock <= 0)
                                    <span class="bg-slate-700 text-white text-[9px] font-black uppercase px-2 py-0.5 rounded-md shadow-sm">Habis</span>
                                @elseif($product->stock <= 5)
                                    <span class="bg-amber-500 text-white text-[9px] font-black uppercase px-2 py-0.5 rounded-md animate-pulse shadow-sm">Sisa {{ $product->stock }}</span>
                                @endif
                            </div>

                            <div x-show="getItemQty({{ $product->id }}) > 0"
                                 class="absolute top-2 right-2 bg-green-600 text-white text-[10px] font-bold h-6 w-6 rounded-full flex items-center justify-center shadow-md z-10 ring-2 ring-green-50"
                                 x-text="getItemQty({{ $product->id }})" x-transition.scale>
                            </div>

                            <div class="h-40 bg-slate-50 rounded-xl flex items-center justify-center mb-3 overflow-hidden relative group-hover:bg-red-50 transition-colors p-2">
                                @if(!empty($product->image) && Storage::disk('public')->exists($product->image))
                                    <img src="{{ asset('storage/' . $product->image) }}"
                                         alt="{{ $product->name }}"
                                         class="w-full h-full object-contain transition-transform duration-500 group-hover:scale-105">
                                @else
                                    <div class="text-3xl text-slate-300 group-hover:text-red-400 transition-colors">
                                        <i class="fas fa-box-open"></i>
                                    </div>
                                @endif
                            </div>

                            <div class="flex-1 flex flex-col">
                                {{-- Menampilkan Nama Kategori kecil diatas Nama Produk (Opsional) --}}
                                <span class="text-[9px] text-slate-400 font-bold uppercase mb-0.5">{{ $product->category->name ?? 'Umum' }}</span>

                                <h3 class="font-bold text-slate-700 text-xs leading-tight mb-1 line-clamp-2 group-hover:text-red-600 transition-colors">{{ $product->name }}</h3>
                                <div class="mt-auto flex justify-between items-end">
                                    <p class="text-xs font-black text-slate-800">Rp {{ number_format($product->sell_price, 0, ',', '.') }}</p>
                                    <p class="text-[9px] font-bold text-slate-400 uppercase tracking-wider">{{ $product->unit }}</p>
                                </div>
                            </div>
                        </div>
                    </template>
                    @empty
                    <div class="col-span-full flex flex-col items-center justify-center text-slate-400 mt-20">
                        <i class="fas fa-box-open text-5xl mb-3 opacity-20"></i>
                        <p class="text-sm font-medium">Belum ada produk tersedia.</p>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>

        <div x-show="mobileCartOpen" class="fixed inset-0 bg-black/50 z-30 lg:hidden backdrop-blur-sm" @click="mobileCartOpen = false" x-transition.opacity></div>

        <div class="fixed inset-y-0 right-0 w-[90%] sm:w-[420px] lg:static lg:w-[400px] bg-white shadow-2xl lg:shadow-none z-40 transform transition-transform duration-300 ease-out flex flex-col h-full border-l border-slate-200"
             :class="mobileCartOpen ? 'translate-x-0' : 'translate-x-full lg:translate-x-0'">

            <div class="h-16 px-5 border-b border-slate-100 flex justify-between items-center bg-green-50 shrink-0">
                <div class="flex flex-col">
                    <span class="text-[10px] font-bold text-green-600 uppercase tracking-widest">Pesanan Baru</span>
                    <span class="font-black text-green-700 text-lg">#{{ date('ymd') }}-{{ rand(100,999) }}</span>
                </div>
                <div class="flex items-center gap-2">
                    <button x-show="cart.length > 0" @click="confirmClearCart()" class="hidden lg:flex items-center gap-1 text-[10px] font-bold text-red-500 hover:bg-red-50 px-3 py-1.5 rounded-lg transition">
                        <i class="fas fa-trash-alt"></i> Reset
                    </button>
                    <button @click="mobileCartOpen = false" class="lg:hidden p-2 text-slate-400 hover:text-slate-600"><i class="fas fa-times text-xl"></i></button>
                </div>
            </div>

            <div class="flex-1 overflow-y-auto custom-scrollbar bg-white">

                <div class="p-4 border-b border-slate-100 bg-slate-50/50"
                x-show="activeCategory === 'all' || (!activeCategory.includes('laundry') && !activeCategory.includes('fnb') && !activeCategory.includes('ppob'))"
                x-transition.opacity>
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">
                            Berkas Cetak (<span x-text="uploadedFiles.length"></span>/10)
                        </span>
                        <button x-show="uploadedFiles.length > 0" @click="uploadedFiles = []" class="text-[10px] text-red-500 hover:underline">
                            Reset Semua
                        </button>
                    </div>

                    <div x-show="uploadedFiles.length > 0" class="space-y-3 mb-3" x-transition>
                        <template x-for="(item, index) in uploadedFiles" :key="index">
                            <div class="bg-white border border-slate-200 rounded-xl shadow-sm p-3 hover:border-blue-300 transition-all">
                                <div class="flex items-center gap-2 mb-3 pb-2 border-b border-dashed border-slate-100">
                                    <div class="h-8 w-8 rounded bg-red-50 flex items-center justify-center text-red-500 text-xs shrink-0">
                                        <i class="fas fa-file-pdf" x-show="item.file.type.includes('pdf')"></i>
                                        <i class="fas fa-image" x-show="item.file.type.includes('image')"></i>
                                        <i class="fas fa-file" x-show="!item.file.type.includes('pdf') && !item.file.type.includes('image')"></i>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-[11px] font-bold text-slate-700 truncate" x-text="item.file.name"></p>
                                        <p class="text-[9px] text-slate-400" x-text="formatFileSize(item.file.size)"></p>
                                    </div>
                                    <button @click="removeFile(index)" class="text-slate-300 hover:text-red-500 transition px-2">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </div>
                                <div class="grid grid-cols-3 gap-2">
                                    <div class="col-span-1">
                                        <label class="flex items-center gap-2 cursor-pointer bg-slate-50 p-1.5 rounded-lg border border-slate-100 h-full">
                                            <input type="checkbox" x-model="item.isColor" class="rounded text-red-600 focus:ring-red-500 w-4 h-4 border-slate-300">
                                            <span class="text-[10px] font-bold leading-tight" :class="item.isColor ? 'text-slate-800' : 'text-slate-400'">
                                                <span x-text="item.isColor ? 'Berwarna' : 'Hitam Putih'"></span>
                                            </span>
                                        </label>
                                    </div>
                                    <div class="col-span-1">
                                        <select x-model="item.paperSize" class="w-full text-[10px] font-bold py-1.5 px-1 rounded-lg border-slate-200 bg-slate-50 focus:ring-red-500 focus:border-red-500">
                                            <option value="A4">Kertas A4</option>
                                            <option value="F4">Kertas F4</option>
                                            <option value="A3">Kertas A3</option>
                                        </select>
                                    </div>
                                    <div class="col-span-1 relative">
                                        <div class="flex items-center border border-slate-200 rounded-lg bg-slate-50 overflow-hidden h-full">
                                            <input type="number" x-model="item.qty" min="1" class="w-full text-center text-[10px] font-bold bg-transparent border-none p-0 focus:ring-0" placeholder="1">
                                            <span class="text-[9px] text-slate-400 pr-1.5">lbr/set</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    <div x-show="uploadedFiles.length < 10" x-transition>
                        <div class="relative border-2 border-dashed border-red-300 rounded-xl bg-white hover:border-green-400 hover:bg-green-50 transition-all cursor-pointer group h-12 flex items-center justify-center">
                            <input type="file" multiple @change="handleFileUpload" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10"
                                   accept=".doc,.docx,.pdf,.xls,.xlsx,.jpg,.jpeg,.png">
                            <div class="flex items-center gap-2 pointer-events-none">
                                <i class="fas" :class="uploadedFiles.length > 0 ? 'fa-plus text-green-500' : 'fa-cloud-upload-alt text-red-400'"></i>
                                <p class="text-[10px] font-bold" :class="uploadedFiles.length > 0 ? 'text-green-600' : 'text-slate-500'">
                                    <span x-text="uploadedFiles.length === 0 ? 'Upload Berkas Pertama' : 'Tambah File Lain'"></span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="p-4 space-y-3 min-h-[200px]">
                    <template x-if="cart.length === 0">
                        <div class="flex flex-col items-center justify-center h-40 text-slate-300">
                            <i class="fas fa-shopping-basket text-4xl mb-2 opacity-50"></i>
                            <p class="text-xs font-bold">Keranjang Kosong</p>
                        </div>
                    </template>

                    <template x-for="item in cart" :key="item.id">
                        <div class="flex items-start gap-3 p-3 bg-white border border-slate-100 rounded-xl shadow-sm hover:border-red-200 transition-colors group">

                            <div class="flex flex-col items-center bg-slate-50 rounded-lg border border-slate-200 shrink-0 w-10">
                                <button @click="updateQty(item.id, 1)" class="w-full h-6 flex items-center justify-center text-slate-500 hover:text-white hover:bg-green-500 rounded-t-lg transition border-b border-slate-200">
                                    <i class="fas fa-plus text-[8px]"></i>
                                </button>
                                <input type="number" x-model="item.qty" @change="validateManualQty(item.id)"
                                       class="w-full text-center text-xs font-bold bg-transparent border-none p-0 focus:ring-0 text-slate-800 h-8">
                                <button @click="updateQty(item.id, -1)" class="w-full h-6 flex items-center justify-center text-slate-500 hover:text-white hover:bg-red-500 rounded-b-lg transition border-t border-slate-200">
                                    <i class="fas fa-minus text-[8px]"></i>
                                </button>
                            </div>

                            <div class="h-10 w-10 rounded-lg bg-slate-100 border border-slate-200 overflow-hidden shrink-0 flex items-center justify-center p-0.5">
                                <template x-if="item.image">
                                    <img :src="item.image" class="h-full w-full object-contain">
                                </template>
                                <template x-if="!item.image">
                                    <i class="fas fa-box text-slate-300 text-xs"></i>
                                </template>
                            </div>

                            <div class="flex-1 min-w-0 py-0.5">
                                <div class="font-bold text-slate-700 text-xs leading-tight mb-1" x-text="item.name"></div>
                                <div class="flex justify-between items-center text-[10px] text-slate-400">
                                    <span>@ <span x-text="rupiah(item.price)"></span></span>
                                    <span class="text-slate-800 font-black text-xs" x-text="rupiah(item.price * item.qty)"></span>
                                </div>
                            </div>

                            <button @click="removeFromCart(item.id)" class="text-slate-300 hover:text-red-500 p-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                <i class="fas fa-trash-alt text-xs"></i>
                            </button>
                        </div>
                    </template>
                </div>
            </div>

            <div class="p-4 bg-slate-50 border-t border-slate-200 z-20 shrink-0 shadow-[0_-5px_15px_rgba(0,0,0,0.02)]">
                <div class="mb-3">
                    <div class="relative">
                        <input type="text" x-model="couponCode" @input.debounce.500ms="checkCoupon()" placeholder="KODE PROMO..."
                               class="w-full pl-3 pr-10 py-2 text-sm rounded-lg border border-slate-200 focus:ring-red-500 uppercase font-bold text-slate-700"
                               :class="{'border-emerald-500 bg-emerald-50': discountAmount > 0, 'border-red-300 bg-red-50': couponMessage && discountAmount === 0}">

                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                            <i x-show="isValidatingCoupon" class="fas fa-circle-notch fa-spin text-slate-400"></i>
                            <i x-show="!isValidatingCoupon && discountAmount > 0" class="fas fa-check-circle text-emerald-500"></i>
                            <i x-show="!isValidatingCoupon && couponMessage && discountAmount === 0" class="fas fa-times-circle text-red-500"></i>
                        </div>
                    </div>
                    <p x-show="couponMessage" x-text="couponMessage" class="text-[10px] font-bold mt-1"
                       :class="discountAmount > 0 ? 'text-emerald-600' : 'text-red-500'"></p>
                </div>

                <div class="space-y-1 mb-4">
                    <div class="flex justify-between items-end text-xs text-slate-500">
                        <span>Subtotal</span>
                        <span x-text="'Rp ' + rupiah(subtotal)"></span>
                    </div>

                    {{-- TOMBOL TAMBAH CATATAN --}}
                    <div class="flex justify-between items-center py-2 border-b border-dashed border-slate-200">
                        <button @click="noteModalOpen = true" class="text-[11px] font-bold flex items-center gap-1 transition-colors focus:outline-none"
                                :class="customerNote ? 'text-blue-600' : 'text-slate-400 hover:text-blue-500'">
                            <i class="fas" :class="customerNote ? 'fa-edit' : 'fa-plus-circle'"></i>
                            <span x-text="customerNote ? 'Edit Catatan Pesanan' : 'Tambah Catatan Pesanan'"></span>
                        </button>
                        <span x-show="customerNote" class="text-[10px] text-blue-600 bg-blue-50 px-2 py-0.5 rounded-full font-bold">Ada Catatan</span>
                    </div>

                    <div x-show="discountAmount > 0" class="flex justify-between items-end text-xs text-emerald-600 font-bold" x-transition>
                        <span>Diskon</span>
                        <span x-text="'- Rp ' + rupiah(discountAmount)"></span>
                    </div>
                    <div class="flex justify-between items-end pt-2 border-t border-dashed border-slate-300">
                        <span class="text-sm font-bold text-slate-800">Total Tagihan</span>
                        <span class="text-2xl font-black text-slate-800 tracking-tight" x-text="'Rp ' + rupiah(grandTotal)"></span>
                    </div>
                </div>

                <button @click="openPaymentModal()"
                        :disabled="cart.length === 0"
                        class="w-full bg-red-600 text-white py-4 rounded-xl font-bold text-base shadow-lg hover:bg-green-800 active:scale-[0.98] transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2 group">
                    <span class="flex items-center gap-2">
                        <span>Bayar Sekarang</span> <i class="fas fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                    </span>
                </button>
            </div>
        </div>
    </div>

    <div x-data="{
            showPromo: false,
            init() {
                if (!localStorage.getItem('seenPromoSancaka_v1')) {
                    setTimeout(() => { this.showPromo = true }, 1500);
                }
            },
            closePromo() {
                this.showPromo = false;
                localStorage.setItem('seenPromoSancaka_v1', 'true');
            }
        }"
        x-show="showPromo" style="display: none;"
        class="fixed inset-0 z-[100] flex items-center justify-center px-4 sm:px-0 font-sans">

        <div x-show="showPromo" x-transition.opacity @click="closePromo()" class="fixed inset-0 bg-slate-900/70 backdrop-blur-[2px]"></div>

        <div x-show="showPromo" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 scale-90 translate-y-4" x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             class="relative bg-white rounded-2xl shadow-2xl w-full max-w-[450px] overflow-hidden flex flex-col z-10 border border-slate-100">

            <button @click="closePromo()" class="absolute top-3 right-3 z-20 text-slate-400 hover:text-red-500 hover:bg-red-50 rounded-full h-8 w-8 flex items-center justify-center transition-all">
                <i class="fas fa-times text-lg"></i>
            </button>

            <div class="relative bg-slate-50 w-full h-40 flex items-center justify-center p-6 border-b border-slate-100">
                <div class="absolute inset-0 opacity-10" style="background-image: radial-gradient(#cbd5e1 1px, transparent 1px); background-size: 10px 10px;"></div>
                <img src="https://tokosancaka.com/storage/uploads/sancaka.png" alt="Sancaka Promo" class="relative w-full h-full object-contain drop-shadow-md transform hover:scale-105 transition-transform duration-500">
            </div>

            <div class="p-6 text-center">
                <h2 class="text-xl font-black text-slate-800 mb-3 leading-tight">Ingin mendapatkan <span class="text-red-600">Diskon 30%?</span></h2>
                <p class="text-slate-600 text-sm leading-relaxed mb-5">
                    Masukan kode <span class="font-bold bg-amber-100 text-amber-700 px-1.5 py-0.5 rounded border border-amber-200 text-xs">KUPON</span> dari teman atau saudara Anda.
                </p>
                <div class="bg-blue-50 border border-blue-100 rounded-xl p-3 mb-6">
                    <p class="text-xs text-blue-800 leading-relaxed">
                        <i class="fas fa-info-circle mr-1"></i>
                        Anda juga dapat menjadi <b>Affiliator</b> dan dapatkan <b>komisi besar</b> ketika menjadi member.
                    </p>
                </div>
                <div class="space-y-3">
                    <a href="https://tokosancaka.com/percetakan/public/join-partner" target="_blank" class="flex items-center justify-center w-full bg-red-600 hover:bg-red-700 text-white font-bold py-3 px-4 rounded-xl shadow-lg shadow-red-200 hover:shadow-red-300 transform active:scale-95 transition-all group">
                        <span>Gabung Sekarang</span>
                        <i class="fas fa-arrow-right ml-2 group-hover:translate-x-1 transition-transform"></i>
                    </a>
                    <button @click="closePromo()" class="text-slate-400 font-bold text-xs hover:text-slate-600 py-1">Tutup Informasi</button>
                </div>
            </div>
        </div>
    </div>

    @include('orders.partials.noteModal')

    @include('orders.partials.payment-modal')

    <script>
        @include('orders.partials.pos-script')
    </script>
</body>
</html>
