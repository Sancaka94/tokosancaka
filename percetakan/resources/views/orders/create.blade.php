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
        /* Scrollbar custom yang lebih rapi */
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 4px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        
        input[type=number]::-webkit-inner-spin-button, 
        input[type=number]::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
        input[type=number] { -moz-appearance: textfield; }
    </style>
</head>
<body class="bg-slate-100 font-sans text-slate-800 h-screen overflow-hidden select-none" x-data="posSystem()">

    <div class="flex h-full w-full flex-col lg:flex-row overflow-hidden">
        
        <div class="flex-1 flex flex-col h-full relative border-r border-slate-200">
            <div class="h-16 px-4 bg-white shadow-sm z-20 flex items-center justify-between shrink-0 border-b border-slate-100">
                <div class="flex items-center gap-2">
                    <div class="h-8 w-8 bg-red-600 rounded-lg flex items-center justify-center text-white font-bold text-lg">
                        <i class="fas fa-print"></i>
                    </div>
                    <h1 class="text-lg font-bold text-slate-800 hidden sm:block">Sancaka POS</h1>
                </div>
                
                <div class="relative w-full max-w-md mx-4">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400"><i class="fas fa-search"></i></span>
                    <input type="text" x-model="search" placeholder="Cari layanan / produk..." 
                           class="w-full pl-10 pr-10 py-2 rounded-xl bg-slate-100 border-none focus:ring-2 focus:ring-red-500 text-sm font-medium transition-all">
                    <button x-show="search.length > 0" @click="search = ''" class="absolute inset-y-0 right-0 pr-3 text-slate-400 hover:text-red-500">
                        <i class="fas fa-times-circle"></i>
                    </button>
                </div>

                <button @click="mobileCartOpen = !mobileCartOpen" class="lg:hidden relative p-2.5 bg-red-50 rounded-xl text-red-600 hover:bg-red-100 transition">
                    <i class="fas fa-shopping-bag"></i>
                    <span x-show="cartTotalQty > 0" class="absolute -top-1 -right-1 bg-red-600 text-white text-[10px] font-bold h-5 w-5 flex items-center justify-center rounded-full border-2 border-white shadow-sm" x-text="cartTotalQty"></span>
                </button>
            </div>

            <div class="flex-1 overflow-y-auto p-4 custom-scrollbar bg-slate-50">
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-3 xl:grid-cols-4 gap-3">
                    @forelse($products as $product)
                    <template x-if="itemMatchesSearch('{{ addslashes($product->name) }}')">
                        <div @click="addToCart({{ $product->id }}, '{{ addslashes($product->name) }}', {{ $product->sell_price }}, {{ $product->stock }}, {{ $product->weight ?? 0 }})"
                             class="relative bg-white rounded-2xl p-3 shadow-sm border border-slate-100 flex flex-col h-full group
                             {{ $product->stock <= 0 ? 'opacity-60 grayscale cursor-not-allowed' : 'cursor-pointer active:scale-95 hover:border-red-300 hover:shadow-md' }} transition-all duration-200">
                            
                            <div class="absolute top-2 left-2 z-10">
                                @if($product->stock <= 0) 
                                    <span class="bg-slate-700 text-white text-[9px] font-black uppercase px-2 py-0.5 rounded-md">Habis</span>
                                @elseif($product->stock <= 5) 
                                    <span class="bg-amber-500 text-white text-[9px] font-black uppercase px-2 py-0.5 rounded-md animate-pulse">Sisa {{ $product->stock }}</span>
                                @endif
                            </div>

                            <div x-show="getItemQty({{ $product->id }}) > 0" 
                                 class="absolute top-2 right-2 bg-red-600 text-white text-[10px] font-bold h-6 w-6 rounded-full flex items-center justify-center shadow-md z-10 ring-2 ring-white"
                                 x-text="getItemQty({{ $product->id }})" x-transition.scale>
                            </div>

                            <div class="aspect-[4/3] bg-slate-50 rounded-xl flex items-center justify-center mb-3 text-3xl text-slate-300 group-hover:text-red-400 group-hover:bg-red-50 transition-colors">
                                <i class="fas fa-box-open"></i>
                            </div>
                            
                            <div class="flex-1 flex flex-col">
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
                
                <div class="p-4 border-b border-slate-100 bg-slate-50/50">
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
                        <p x-show="uploadedFiles.length === 0" class="text-[9px] text-slate-400 mt-1 text-center">Format: PDF, JPG, Docx. Max 10MB.</p>
                    </div>

                    <div x-show="uploadedFiles.length >= 10" class="p-2 bg-amber-50 border border-amber-200 rounded-lg text-center mt-2">
                        <p class="text-[10px] font-bold text-amber-600">Maksimal 10 file tercapai.</p>
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
                                
                                <input type="number" 
                                       x-model="item.qty" 
                                       @change="validateManualQty(item.id)" 
                                       class="w-full text-center text-xs font-bold bg-transparent border-none p-0 focus:ring-0 text-slate-800 h-8">
                                
                                <button @click="updateQty(item.id, -1)" class="w-full h-6 flex items-center justify-center text-slate-500 hover:text-white hover:bg-red-500 rounded-b-lg transition border-t border-slate-200">
                                    <i class="fas fa-minus text-[8px]"></i>
                                </button>
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
                        <input type="text" 
                               x-model="couponCode" 
                               @input.debounce.500ms="checkCoupon()" 
                               placeholder="KODE PROMO..." 
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

    <div x-show="showPaymentModal" style="display: none;" 
         class="fixed inset-0 z-50 bg-slate-100 flex flex-col"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-full"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 translate-y-full">
        
        <div class="h-16 px-6 bg-white border-b border-slate-200 flex justify-between items-center shadow-sm shrink-0 z-20">
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-xl bg-red-50 text-red-600 flex items-center justify-center">
                    <i class="fas fa-cash-register text-xl"></i>
                </div>
                <div>
                    <h3 class="font-black text-xl text-slate-800 leading-tight">Pembayaran</h3>
                    <p class="text-xs text-slate-500 font-medium">Selesaikan transaksi pesanan ini</p>
                </div>
            </div>
            
            <button @click="showPaymentModal = false" class="group flex items-center gap-2 px-4 py-2 rounded-full bg-slate-100 hover:bg-red-50 text-slate-500 hover:text-red-600 transition border border-transparent hover:border-red-100">
                <span class="text-xs font-bold hidden sm:block">BATAL / TUTUP</span>
                <i class="fas fa-times text-lg"></i>
            </button>
        </div>

        <div class="flex-1 flex flex-col lg:flex-row overflow-hidden relative">
            
            <div class="lg:w-[35%] bg-white border-r border-slate-200 overflow-y-auto custom-scrollbar flex flex-col order-2 lg:order-1 h-full shadow-[4px_0_24px_rgba(0,0,0,0.02)] z-10">
                
                <div class="p-6 space-y-6">
                    <div class="text-center p-6 bg-slate-50 rounded-2xl border border-slate-100">
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-2">Total Yang Harus Dibayar</p>
                        <h2 class="text-4xl sm:text-5xl font-black text-slate-800 tracking-tight break-all" x-text="'Rp ' + rupiah(grandTotal)"></h2>
                        <div x-show="discountAmount > 0" class="mt-2 inline-flex items-center gap-1 px-3 py-1 bg-emerald-100 text-emerald-700 rounded-full text-xs font-bold">
                            <i class="fas fa-check-circle"></i> Hemat Rp <span x-text="rupiah(discountAmount)"></span>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <h4 class="text-sm font-bold text-slate-700 border-b border-slate-100 pb-2">Rincian Biaya</h4>
                        
                        <div class="flex justify-between items-center text-sm text-slate-600">
                            <span>Subtotal (<span x-text="cartTotalQty"></span> Item)</span>
                            <span class="font-bold text-slate-800" x-text="'Rp ' + rupiah(subtotal)"></span>
                        </div>

                        <div x-show="discountAmount > 0" class="flex justify-between items-center text-sm text-emerald-600">
                            <span>Potongan Diskon</span>
                            <span class="font-bold" x-text="'- Rp ' + rupiah(discountAmount)"></span>
                        </div>

                        <div x-show="deliveryType === 'shipping'" class="flex justify-between items-center text-sm text-blue-600">
                            <span>Ongkos Kirim</span>
                            <span class="font-bold" x-text="shippingCost > 0 ? '+ Rp ' + rupiah(shippingCost) : 'Rp 0'"></span>
                        </div>

                        <div class="border-t border-dashed border-slate-300 my-2"></div>

                        <div class="flex justify-between items-center text-base">
                            <span class="font-bold text-slate-800">Total Akhir</span>
                            <span class="font-black text-red-600 text-lg" x-text="'Rp ' + rupiah(grandTotal)"></span>
                        </div>
                    </div>
                </div>

                <div class="mt-auto p-6 bg-slate-50 border-t border-slate-100 hidden lg:block">
                    <div class="flex items-center gap-3 text-slate-400 text-xs">
                        <i class="fas fa-shield-alt text-xl"></i>
                        <p>Transaksi aman dan terenkripsi. Pastikan nominal pembayaran sesuai dengan total tagihan.</p>
                    </div>
                </div>
            </div>

            <div class="lg:w-[65%] bg-slate-50/50 overflow-y-auto custom-scrollbar p-4 sm:p-8 order-1 lg:order-2 h-full">
                
                <div class="max-w-3xl mx-auto space-y-6">
                    
                    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
                        <label class="flex items-center gap-2 text-xs font-bold text-slate-500 uppercase tracking-widest mb-4">
                            <span class="w-6 h-6 rounded-full bg-slate-100 flex items-center justify-center text-slate-600">1</span>
                            Data Pelanggan
                        </label>
                        
                        <div class="flex p-1 bg-slate-100 border border-slate-200 rounded-xl mb-4 w-full sm:w-80">
                            <button @click="customerType = 'guest'; selectedCustomerId = '';" 
                                    class="flex-1 py-2 text-xs font-bold rounded-lg transition-all"
                                    :class="customerType === 'guest' ? 'bg-white text-red-600 shadow-sm ring-1 ring-slate-200' : 'text-slate-500 hover:text-slate-700'">
                                Tamu (Guest)
                            </button>
                            <button @click="customerType = 'member'" 
                                    class="flex-1 py-2 text-xs font-bold rounded-lg transition-all"
                                    :class="customerType === 'member' ? 'bg-white text-green-600 shadow-sm ring-1 ring-slate-200' : 'text-slate-500 hover:text-slate-700'">
                                Member
                            </button>
                        </div>

                        <div x-show="customerType === 'guest'" x-transition>
                            <div x-show="deliveryType === 'shipping'" class="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-4 space-y-3">
                                <div class="flex items-center gap-2 mb-1 border-b border-blue-200 pb-2">
                                    <i class="fas fa-truck-fast text-blue-600"></i>
                                    <span class="text-xs font-bold text-blue-700">Info Pengiriman (Wajib)</span>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <label class="text-[10px] font-bold text-slate-500">Nama Penerima*</label>
                                        <input type="text" x-model="customerName" class="w-full mt-1 px-3 py-2 rounded-lg border border-slate-300 text-sm focus:ring-2 focus:ring-blue-500">
                                    </div>
                                    <div>
                                        <label class="text-[10px] font-bold text-slate-500">WhatsApp*</label>
                                        <input type="number" x-model="customerPhone" class="w-full mt-1 px-3 py-2 rounded-lg border border-slate-300 text-sm focus:ring-2 focus:ring-blue-500">
                                    </div>
                                </div>
                                <div>
                                    <label class="text-[10px] font-bold text-slate-500">Alamat Lengkap (Jalan, RT/RW)*</label>
                                    <textarea x-model="customerAddressDetail" rows="2" class="w-full mt-1 px-3 py-2 rounded-lg border border-slate-300 text-sm focus:ring-2 focus:ring-blue-500 resize-none"></textarea>
                                </div>
                            </div>

                            <div x-show="deliveryType === 'pickup'" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="text-[10px] font-bold text-slate-500">Nama Pemesan</label>
                                    <input type="text" x-model="customerName" placeholder="Nama..." class="w-full mt-1 px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-red-500 transition">
                                </div>
                                <div>
                                    <label class="text-[10px] font-bold text-slate-500">WhatsApp (Opsional)</label>
                                    <input type="number" x-model="customerPhone" placeholder="08..." class="w-full mt-1 px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-red-500 transition">
                                </div>
                            </div>
                        </div>

                        <div x-show="customerType === 'member'" style="display: none;" x-transition>
                            <label class="text-[10px] font-bold text-slate-500">Cari Member Terdaftar</label>
                            <select x-model="selectedCustomerId" class="w-full mt-1 px-4 py-3 rounded-xl border border-slate-200 text-sm bg-slate-50 font-bold text-slate-700 focus:ring-2 focus:ring-red-500">
                                <option value="">-- Pilih Member --</option>
                                @foreach($customers as $c)
                                    <option value="{{ $c->id }}" 
                                            data-saldo="{{ $c->saldo }}" 
                                            data-affiliate-balance="{{ $c->affiliate_balance ?? 0 }}"
                                            data-has-pin="{{ $c->has_pin ? 'yes' : 'no' }}">
                                        {{ $c->name }} (Saldo: Rp {{ number_format($c->saldo,0,',','.') }})
                                    </option>
                                @endforeach
                            </select>
                            
                            <div x-show="selectedCustomerId" class="mt-3 flex gap-3">
                                <div class="flex-1 p-3 bg-blue-50 rounded-xl border border-blue-100 flex flex-col items-center">
                                    <span class="text-[10px] text-blue-400 font-bold uppercase">Saldo Topup</span>
                                    <span class="text-sm font-black text-blue-700" x-text="'Rp ' + rupiah(getSelectedMemberSaldo())"></span>
                                </div>
                                <div class="flex-1 p-3 bg-purple-50 rounded-xl border border-purple-100 flex flex-col items-center">
                                    <span class="text-[10px] text-purple-400 font-bold uppercase">Profit Afiliasi</span>
                                    <span class="text-sm font-black text-purple-700" x-text="'Rp ' + rupiah(getSelectedAffiliateBalance())"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
                        <label class="flex items-center gap-2 text-xs font-bold text-slate-500 uppercase tracking-widest mb-4">
                            <span class="w-6 h-6 rounded-full bg-slate-100 flex items-center justify-center text-slate-600">2</span>
                            Metode Pembayaran
                        </label>

                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                            <div @click="paymentMethod = 'cash'" 
                                 class="cursor-pointer border-2 rounded-xl p-3 flex flex-col items-center justify-center gap-2 transition relative overflow-hidden group min-h-[90px]"
                                 :class="paymentMethod === 'cash' ? 'border-red-500 bg-red-50 text-red-700' : 'border-slate-100 bg-white hover:border-red-200 hover:bg-slate-50'">
                                <i class="fas fa-money-bill-wave text-2xl"></i> 
                                <span class="text-xs font-bold text-center">Tunai</span>
                                <div x-show="paymentMethod === 'cash'" class="absolute top-1 right-1 text-red-500"><i class="fas fa-check-circle"></i></div>
                            </div>
                            
                            <div @click="paymentMethod = 'saldo'" 
                                 class="cursor-pointer border-2 rounded-xl p-3 flex flex-col items-center justify-center gap-2 transition relative overflow-hidden group min-h-[90px]"
                                 :class="paymentMethod === 'saldo' ? 'border-blue-500 bg-blue-50 text-blue-700' : 'border-slate-100 bg-white hover:border-blue-200 hover:bg-slate-50'">
                                <i class="fas fa-wallet text-2xl"></i> 
                                <span class="text-xs font-bold text-center">Saldo Topup</span>
                                <div x-show="paymentMethod === 'saldo'" class="absolute top-1 right-1 text-blue-500"><i class="fas fa-check-circle"></i></div>
                            </div>

                            <div @click="selectAffiliatePayment()" 
                                 class="cursor-pointer border-2 rounded-xl p-3 flex flex-col items-center justify-center gap-2 transition relative overflow-hidden group min-h-[90px]"
                                 :class="paymentMethod === 'affiliate_balance' ? 'border-purple-500 bg-purple-50 text-purple-700' : 'border-slate-100 bg-white hover:border-purple-200 hover:bg-slate-50'">
                                <i class="fas fa-coins text-2xl"></i> 
                                <span class="text-xs font-bold text-center">Saldo Profit</span>
                                <div x-show="paymentMethod === 'affiliate_balance'" class="absolute top-1 right-1 text-purple-500"><i class="fas fa-check-circle"></i></div>
                            </div>

                            <div @click="paymentMethod = 'tripay'; fetchTripayChannels()" 
                                 class="cursor-pointer border-2 rounded-xl p-3 flex flex-col items-center justify-center gap-2 transition relative overflow-hidden group min-h-[90px]"
                                 :class="paymentMethod === 'tripay' ? 'border-red-500 bg-red-50 text-red-700' : 'border-slate-100 bg-white hover:border-red-200 hover:bg-slate-50'">
                                <i class="fas fa-qrcode text-2xl"></i> 
                                <span class="text-xs font-bold text-center">QRIS / VA</span>
                                <div x-show="paymentMethod === 'tripay'" class="absolute top-1 right-1 text-red-500"><i class="fas fa-check-circle"></i></div>
                            </div>
                        </div>

                        <div class="mt-5 pt-5 border-t border-dashed border-slate-200">
                            
                            <div x-show="paymentMethod === 'cash'" x-transition>
                                <label class="block text-[10px] font-bold text-slate-500 uppercase mb-2">Nominal Diterima</label>
                                <div class="relative">
                                    <span class="absolute left-4 top-3.5 text-slate-400 font-bold text-lg">Rp</span>
                                    <input type="number" x-model="cashAmount" placeholder="0" 
                                           class="w-full pl-12 pr-4 py-3 text-2xl font-black text-slate-800 bg-slate-50 rounded-xl border border-slate-200 focus:ring-2 focus:ring-red-500 transition">
                                </div>
                                <div class="flex gap-2 mt-3 overflow-x-auto pb-1 no-scrollbar">
                                     <button @click="cashAmount = 50000" class="text-xs px-4 py-2 bg-white border border-slate-200 rounded-lg font-bold hover:border-slate-400 whitespace-nowrap">Rp 50.000</button>
                                     <button @click="cashAmount = 100000" class="text-xs px-4 py-2 bg-white border border-slate-200 rounded-lg font-bold hover:border-slate-400 whitespace-nowrap">Rp 100.000</button>
                                     <button @click="cashAmount = grandTotal" class="text-xs px-4 py-2 bg-slate-800 text-white border border-slate-800 rounded-lg font-bold hover:bg-black whitespace-nowrap">Uang Pas</button>
                                </div>
                                <div class="mt-4 p-4 rounded-xl flex justify-between items-center transition-colors"
                                     :class="change < 0 ? 'bg-red-50 text-red-700' : 'bg-emerald-50 text-emerald-700'">
                                    <span class="font-bold text-sm">Kembalian</span>
                                    <span class="font-black text-xl" x-text="change < 0 ? 'Kurang Rp ' + rupiah(Math.abs(change)) : 'Rp ' + rupiah(change)"></span>
                                </div>
                            </div>

                            <div x-show="paymentMethod === 'affiliate_balance'" x-transition class="text-center py-2">
                                <label class="block text-[10px] font-bold text-purple-600 uppercase mb-2">PIN Keamanan (6 Digit)</label>
                                <div class="relative max-w-[240px] mx-auto">
                                    <input type="password" x-model="affiliatePin" placeholder="******" maxlength="6"
                                           class="w-full px-4 py-3 text-center text-3xl font-black text-purple-800 bg-white rounded-xl border border-purple-200 focus:ring-4 focus:ring-purple-100 tracking-[0.5em] transition placeholder-purple-200">
                                </div>
                            </div>

                            <div x-show="paymentMethod === 'tripay'" x-transition>
                                <div x-show="isLoadingChannels" class="text-center py-4 text-slate-400">
                                    <i class="fas fa-circle-notch fa-spin text-2xl"></i>
                                </div>
                                <div x-show="!isLoadingChannels && tripayChannels.length > 0" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-2 max-h-60 overflow-y-auto custom-scrollbar p-1">
                                    <template x-for="channel in tripayChannels" :key="channel.code">
                                        <button @click="paymentChannel = channel.code" 
                                                x-show="channel.active"
                                                class="p-2 rounded-lg border transition flex flex-col items-center justify-center gap-1 h-20 bg-white hover:border-red-300 relative"
                                                :class="paymentChannel === channel.code ? 'border-red-600 bg-red-50 ring-1 ring-red-600' : 'border-slate-200'">
                                            <img :src="channel.icon_url" class="h-6 object-contain">
                                            <span class="text-[9px] font-bold text-slate-600 text-center leading-none" x-text="channel.name"></span>
                                        </button>
                                    </template>
                                </div>
                            </div>

                        </div>
                    </div>

                </div>
            </div>
        </div>

        <div class="p-4 sm:p-6 bg-white border-t border-slate-200 shrink-0 z-20 flex justify-end gap-3 shadow-[0_-4px_20px_rgba(0,0,0,0.05)]">
            <div class="hidden lg:block mr-auto">
                <p class="text-xs text-slate-400">Pastikan data sudah benar sebelum memproses.</p>
            </div>
            
            <button @click="showPaymentModal = false" class="px-6 py-3 rounded-xl font-bold text-slate-500 hover:bg-slate-100 transition">
                Kembali
            </button>
            
            <button @click="checkout()" :disabled="isProcessing" 
                    class="w-full sm:w-auto px-8 py-3 bg-red-600 text-white rounded-xl font-bold text-lg shadow-lg shadow-red-200 hover:bg-red-700 active:scale-[0.98] transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2">
                <span x-show="!isProcessing">Bayar & Cetak Struk</span>
                <span x-show="isProcessing"><i class="fas fa-spinner fa-spin"></i> Proses...</span>
                <i x-show="!isProcessing" class="fas fa-arrow-right"></i>
            </button>
        </div>
    </div>

    <script>
    function posSystem() {
        return {
            init() {
                 if(this.couponCode) { 
                      this.couponMessage = 'Kupon terdeteksi! Masukkan barang untuk cek diskon.';
                 }
            },
            
            // --- 1. STATE UI & UMUM ---
            mobileCartOpen: false,
            showPaymentModal: false,
            search: '',
            cart: [],
            uploadedFiles: [],
            filesToDelete: [],
            isProcessing: false,
            isValidatingCoupon: false,
            
            // --- 2. KUPON ---
            couponCode: '{{ $autoCoupon ?? "" }}',
            couponMessage: '',
            discountAmount: 0,
            
            // --- 3. PELANGGAN (MEMBER/GUEST) ---
            customerType: 'guest',
            customerName: '',
            customerPhone: '',
            customerAddressDetail: '', 
            selectedCustomerId: '',
            
            // --- 4. PEMBAYARAN ---
            paymentMethod: 'cash',
            paymentChannel: '',     
            tripayChannels: [],     
            isLoadingChannels: false,
            cashAmount: '',         
            affiliatePin: '',       

            // --- 5. PENGIRIMAN (KIRIMINAJA) ---
            deliveryType: 'pickup', 
            
            // Variabel Pencarian Lokasi
            searchQuery: '',        
            searchResults: [],      
            isSearchingLocation: false,
            
            // ID Lokasi
            destinationDistrictId: '', 
            destinationSubdistrictId: '', 
            
            // Hasil Ongkir
            courierList: [],
            selectedCourier: null,
            shippingCost: 0,
            isLoadingShipping: false,

            // ============================================================
            // COMPUTED PROPERTIES
            // ============================================================
            
            get subtotal() { 
                return this.cart.reduce((sum, item) => sum + (item.price * item.qty), 0); 
            },
            
            get cartTotalQty() { 
                return this.cart.reduce((sum, item) => sum + item.qty, 0); 
            },
            
            get grandTotal() { 
                let total = this.subtotal - this.discountAmount + this.shippingCost;
                return total < 0 ? 0 : total;
            },
            
            get change() {
                let received = parseInt(this.cashAmount) || 0;
                return received - this.grandTotal;
            },

            // ============================================================
            // HELPERS
            // ============================================================
            
            rupiah(val) { 
                return new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(val); 
            },
            
            formatFileSize(bytes) {
                if(bytes === 0) return '0 B';
                const k = 1024; const sizes = ['B', 'KB', 'MB', 'GB'];
                const i = Math.floor(Math.log(bytes) / Math.log(k));
                return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
            },
            
            itemMatchesSearch(name) { 
                return name.toLowerCase().includes(this.search.toLowerCase()); 
            },
            
            getItemQty(id) {
                let item = this.cart.find(i => i.id === id);
                return item ? item.qty : 0;
            },

            // ============================================================
            // LOGIKA PENGIRIMAN
            // ============================================================

            async searchLocation() {
                if (this.searchQuery.length < 3) {
                    this.searchResults = [];
                    return;
                }
                this.isSearchingLocation = true;
                try {
                    const response = await fetch(`{{ route('orders.search-location') }}?query=${this.searchQuery}`);
                    const result = await response.json();
                    if (result.status === 'success') {
                        this.searchResults = result.data;
                    } else {
                        this.searchResults = [];
                    }
                } catch (error) {
                    console.error('Gagal cari lokasi:', error);
                    this.searchResults = [];
                } finally {
                    this.isSearchingLocation = false;
                }
            },

            selectLocation(location) {
                this.searchQuery = location.full_address; 
                this.destinationDistrictId = location.district_id; 
                this.destinationSubdistrictId = location.subdistrict_id; 
                this.searchResults = [];
                this.checkOngkir();
            },

            async checkOngkir() {
                if (!this.destinationDistrictId) return;
                
                let realTotalWeight = this.cart.reduce((w, item) => w + (item.qty * (item.weight > 0 ? item.weight : 100)), 0);
                let finalWeight = realTotalWeight < 1000 ? 1000 : realTotalWeight;

                this.isLoadingShipping = true;
                this.courierList = []; 
                this.selectedCourier = null;
                this.shippingCost = 0;

                try {
                    const response = await fetch("{{ route('orders.check-ongkir') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({
                            destination_district_id: this.destinationDistrictId,
                            destination_subdistrict_id: this.destinationSubdistrictId,
                            postal_code: this.destinationZipCode, 
                            destination_text: this.searchQuery, 
                            weight: finalWeight 
                        })
                    });
                    
                    const result = await response.json();
                    
                    if (result.status === 'success') {
                        this.courierList = result.data; 
                    } else {
                        alert('Gagal cek ongkir: ' + result.message);
                    }
                } catch (e) {
                    console.error(e);
                    alert('Error koneksi server saat cek ongkir');
                } finally {
                    this.isLoadingShipping = false;
                }
            },

            selectCourier(courier) {
                this.selectedCourier = courier;
                this.shippingCost = parseInt(courier.cost); 
            },

            // ============================================================
            // LOGIKA PEMBAYARAN & MEMBER
            // ============================================================

            getSelectedMemberSaldo() {
                if(!this.selectedCustomerId) return 0;
                const select = document.querySelector(`select[x-model="selectedCustomerId"]`);
                if (!select) return 0;
                const option = select.querySelector(`option[value="${this.selectedCustomerId}"]`);
                return option ? parseFloat(option.dataset.saldo) : 0;
            },
            
            getSelectedAffiliateBalance() {
                if(!this.selectedCustomerId) return 0;
                const select = document.querySelector(`select[x-model="selectedCustomerId"]`);
                if (!select) return 0;
                const option = select.querySelector(`option[value="${this.selectedCustomerId}"]`);
                return option ? parseFloat(option.dataset.affiliateBalance) : 0;
            },

            selectAffiliatePayment() {
                if(!this.selectedCustomerId) { alert('❌ Pilih Member terlebih dahulu!'); return; }
                if(this.getSelectedAffiliateBalance() < this.grandTotal) { alert('❌ Saldo Profit tidak cukup!'); return; }
                this.paymentMethod = 'affiliate_balance';
                this.affiliatePin = '';
            },

            async fetchTripayChannels() {
                if (this.tripayChannels.length > 0) return;
                this.isLoadingChannels = true;
                try {
                    const response = await fetch("{{ route('orders.tripay-channels') }}");
                    const result = await response.json();
                    if(result.status === 'success') { this.tripayChannels = result.data; }
                } catch (error) { console.error('Fetch error:', error); } finally { this.isLoadingChannels = false; }
            },
            
            getChannelsByGroup(groupName) {
                if (!this.tripayChannels || this.tripayChannels.length === 0) return [];
                return this.tripayChannels.filter(c => c.active === true && c.group.toLowerCase() === groupName.toLowerCase());
            },

            async checkCoupon() {
                if (!this.couponCode.trim()) { this.discountAmount = 0; this.couponMessage = ''; return; }
                if (this.cart.length === 0) { this.couponMessage = 'Isi keranjang dulu.'; return; }
                this.isValidatingCoupon = true; this.couponMessage = '';
                try {
                    const response = await fetch("{{ route('orders.check-coupon') }}", {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') },
                        body: JSON.stringify({ coupon_code: this.couponCode, total_belanja: this.subtotal })
                    });
                    const data = await response.json();
                    if (data.status === 'success') { this.discountAmount = data.data.discount_amount; this.couponMessage = `✅ Hemat Rp ${this.rupiah(data.data.discount_amount)}`; } 
                    else { this.discountAmount = 0; this.couponMessage = data.message; }
                } catch (error) { this.couponMessage = 'Gagal cek server.'; this.discountAmount = 0; } finally { this.isValidatingCoupon = false; }
            },
            
            addToCart(id, name, price, maxStock, weight = 0) {
                if (maxStock <= 0) { alert('Stok Habis!'); return; }
                
                let realWeight = (parseInt(weight) > 0) ? parseInt(weight) : 5;

                let item = this.cart.find(i => i.id === id);
                if (item) {
                    if (item.qty < maxStock) item.qty++;
                    else alert('Stok maksimal tercapai!');
                } else {
                    this.cart.push({ 
                        id, 
                        name, 
                        price, 
                        qty: 1, 
                        maxStock, 
                        weight: realWeight 
                    });
                }
                
                if(navigator.vibrate) navigator.vibrate(30);
                if(this.couponCode) this.checkCoupon();
            },
            
            updateQty(id, amount) {
                let item = this.cart.find(i => i.id === id);
                if (item) {
                    if (amount > 0 && item.qty >= item.maxStock) { alert('Stok maksimal tercapai'); return; }
                    item.qty += amount;
                    if (item.qty <= 0) this.removeFromCart(id);
                }
                if(this.couponCode) setTimeout(() => this.checkCoupon(), 500); 
            },
            
            validateManualQty(id) {
                let item = this.cart.find(i => i.id === id);
                if (!item) return;
                let parsed = parseInt(item.qty);
                if (isNaN(parsed) || parsed < 1) { item.qty = 1; } 
                else if (parsed > item.maxStock) { alert('Stok tidak mencukupi!'); item.qty = item.maxStock; } 
                else { item.qty = parsed; }
                if(this.couponCode) this.checkCoupon();
            },
            
            removeFromCart(id) { 
                this.cart = this.cart.filter(i => i.id !== id);
                if(this.cart.length === 0) { this.discountAmount = 0; this.couponMessage = ''; } 
                else if(this.couponCode) { this.checkCoupon(); }
            },
            
            confirmClearCart() { 
                if(confirm('Kosongkan keranjang?')) { 
                    this.cart = []; this.uploadedFiles = []; this.discountAmount = 0; this.couponMessage = ''; 
                    this.shippingCost = 0; this.deliveryType = 'pickup'; this.searchQuery = '';
                } 
            },
            
            openPaymentModal() {
                if(this.cart.length === 0) { alert('Keranjang masih kosong!'); return; }
                this.showPaymentModal = true;
                if(this.paymentMethod !== 'cash') this.cashAmount = '';
                if(this.paymentMethod === 'tripay') this.fetchTripayChannels();
            },
            
            handleFileUpload(event) {
                const files = event.target.files;
                const remainingSlots = 10 - this.uploadedFiles.length;

                if (files.length > remainingSlots) {
                    alert('Maksimal 10 file total! Slot tersisa: ' + remainingSlots);
                    event.target.value = ''; 
                    return;
                }

                for (let i = 0; i < files.length; i++) {
                    if(files[i].size > 10 * 1024 * 1024) { 
                        alert('File terlalu besar (Max 10MB): ' + files[i].name); 
                        continue; 
                    }
                    
                    this.uploadedFiles.push({
                        file: files[i],      
                        isColor: false,      
                        paperSize: 'A4',     
                        qty: 1               
                    });
                }
                
                event.target.value = ''; 
            },

            removeFile(index) { 
                this.uploadedFiles.splice(index, 1); 
            },
            
            async checkout() {
                if (this.customerType === 'guest' && this.deliveryType === 'shipping') {
                    if (!this.customerName || this.customerName.trim().length < 3) {
                        alert('❌ Mohon isi NAMA PENERIMA untuk keperluan pengiriman ekspedisi!');
                        return; 
                    }
                    if (!this.customerPhone || this.customerPhone.trim().length < 9) {
                        alert('❌ Mohon isi NOMOR WA untuk keperluan pengiriman ekspedisi!');
                        return;
                    }
                    if (!this.customerAddressDetail || this.customerAddressDetail.trim().length < 10) {
                        alert('❌ Mohon isi Detail Alamat (Jalan/No Rumah) agar kurir tidak bingung!');
                        return;
                    }
                }
                if (this.paymentMethod === 'cash') {
                    if (!this.cashAmount || this.change < 0) { alert('❌ Uang tunai kurang!'); return; }
                } 
                else if (this.paymentMethod === 'tripay') {
                    if (!this.paymentChannel) { alert('❌ Silakan pilih Bank / Channel Pembayaran dulu!'); return; }
                } 
                else if (this.paymentMethod === 'saldo') {
                    if (!this.selectedCustomerId) { alert('❌ Pilih Member!'); return; }
                    if (this.getSelectedMemberSaldo() < this.grandTotal) { alert('❌ Saldo Topup kurang!'); return; }
                } 
                else if (this.paymentMethod === 'affiliate_balance') {
                    if (!this.selectedCustomerId) { alert('❌ Pilih Member!'); return; }
                    if (this.getSelectedAffiliateBalance() < this.grandTotal) { alert('❌ Saldo Profit kurang!'); return; }
                    if (!this.affiliatePin || this.affiliatePin.length < 4) { alert('❌ Masukkan PIN Keamanan!'); return; }
                }

                if (this.deliveryType === 'shipping') {
                    if (!this.destinationDistrictId) {
                        alert('❌ Harap pilih lokasi tujuan pengiriman!');
                        return;
                    }
                    if (this.shippingCost === 0 || !this.selectedCourier) {
                        alert('❌ Harap pilih kurir pengiriman (KiriminAja)!');
                        return;
                    }
                }

                this.isProcessing = true;
                
                let formData = new FormData();
                formData.append('items', JSON.stringify(this.cart));
                formData.append('total', this.subtotal);
                formData.append('coupon', this.couponCode);
                formData.append('payment_method', this.paymentMethod);

                formData.append('delivery_type', this.deliveryType);
                if (this.deliveryType === 'shipping') {
                    formData.append('shipping_cost', this.shippingCost);
                    formData.append('courier_name', this.selectedCourier.name + ' - ' + this.selectedCourier.service);
                    formData.append('destination_district_id', this.destinationDistrictId);
                    formData.append('destination_subdistrict_id', this.destinationSubdistrictId);
                    formData.append('destination_text', this.searchQuery);
                    formData.append('courier_code', this.selectedCourier.courier_code); 
                    formData.append('service_type', this.selectedCourier.service_type);
                    formData.append('customer_address_detail', this.customerAddressDetail);
                }

                if (this.paymentMethod === 'tripay') {
                    formData.append('payment_channel', this.paymentChannel);
                }
                
                if(this.customerType === 'member' && this.selectedCustomerId) {
                    formData.append('customer_id', this.selectedCustomerId);
                } else {
                    formData.append('customer_name', this.customerName || 'Guest');
                    formData.append('customer_phone', this.customerPhone || '');
                }

                if(this.paymentMethod === 'cash') formData.append('cash_amount', this.cashAmount);
                if(this.paymentMethod === 'affiliate_balance') formData.append('affiliate_pin', this.affiliatePin); 

                this.uploadedFiles.forEach((item, index) => {
                    formData.append(`attachments[${index}]`, item.file);
                    
                    formData.append(`attachment_details[${index}][color]`, item.isColor ? 'Color' : 'BW');
                    formData.append(`attachment_details[${index}][size]`, item.paperSize);
                    formData.append(`attachment_details[${index}][qty]`, item.qty);
                });

                try {
                    const response = await fetch("{{ route('orders.store') }}", {
                        method: "POST",
                        headers: { 
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Accept': 'application/json'
                        },
                        body: formData
                    });
                    
                    const contentType = response.headers.get("content-type");
                    if (!contentType || !contentType.includes("application/json")) {
                        throw new Error("Terjadi kesalahan Server (Error 500).");
                    }

                    const result = await response.json();

                    if (result.status === 'success') {
                        this.showPaymentModal = false;
                        let msg = `✅ Transaksi Berhasil!\nInvoice: ${result.invoice}`;
                        if(this.paymentMethod === 'cash') msg += `\n💰 KEMBALIAN: Rp ${this.rupiah(result.change_amount)}`;
                        alert(msg);
                        if (result.payment_url) window.open(result.payment_url, '_blank');
                        window.location.reload();
                    } else {
                        throw new Error(result.message);
                    }
                } catch (error) {
                    console.error(error);
                    alert('❌ Gagal: ' + error.message);
                } finally {
                    this.isProcessing = false;
                }
            }
        }
    }
</script>
</body>
</html>