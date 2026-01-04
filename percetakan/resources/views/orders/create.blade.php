<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Kasir POS - Sancaka</title>

    <link rel="icon" href="https://tokosancaka.com/storage/uploads/sancaka.png" type="image/png">
    <link rel="shortcut icon" href="https://tokosancaka.com/storage/uploads/sancaka.png" type="image/png">
    <link rel="apple-touch-icon" href="https://tokosancaka.com/storage/uploads/sancaka.png">
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        [x-cloak] { display: none !important; }
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: #f1f1f1; }
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
                    <div class="relative border-2 border-dashed border-red-300 rounded-xl bg-red-50 hover:border-green-400 hover:bg-green-50 transition-all cursor-pointer group h-20 flex items-center justify-center">
                        <input type="file" multiple @change="handleFileUpload" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" 
                               accept=".doc,.docx,.pdf,.xls,.xlsx,.jpg,.jpeg,.png">
                        <div class="text-center pointer-events-none flex flex-col items-center">
                            <i class="fas fa-cloud-upload-alt text-slate-400 group-hover:text-red-500 text-xl mb-1 transition-colors"></i>
                            <p class="text-[10px] font-bold text-slate-500 group-hover:text-red-600">Klik / Drag File, Dokumen atau Berkas</p>
                        </div>
                    </div>

                    <div x-show="uploadedFiles.length > 0" class="mt-3 space-y-2" x-transition>
                        <div class="flex justify-between items-center">
                            <span class="text-[10px] font-bold text-slate-400 uppercase">Lampiran (<span x-text="uploadedFiles.length"></span>)</span>
                            <button @click="uploadedFiles = []" class="text-[10px] text-red-500 hover:underline">Hapus Semua</button>
                        </div>
                        <template x-for="(file, index) in uploadedFiles" :key="index">
                            <div class="flex items-center gap-2 p-2 bg-white border border-slate-200 rounded-lg shadow-sm">
                                <div class="h-8 w-8 rounded bg-slate-100 flex items-center justify-center text-slate-500 text-xs shrink-0">
                                    <i class="fas fa-file"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-[11px] font-bold text-slate-700 truncate" x-text="file.name"></p>
                                    <p class="text-[9px] text-slate-400" x-text="formatFileSize(file.size)"></p>
                                </div>
                                <button @click="removeFile(index)" class="h-6 w-6 flex items-center justify-center text-slate-300 hover:text-red-500 hover:bg-red-50 rounded transition">
                                    <i class="fas fa-times text-xs"></i>
                                </button>
                            </div>
                        </template>
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

    <div x-show="showPaymentModal" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm transition-opacity" 
             x-transition.opacity 
             @click="showPaymentModal = false"></div>

        <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-lg overflow-hidden flex flex-col max-h-[90vh]" 
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-90 translate-y-4"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0">
            
            <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                <div>
                    <h3 class="font-black text-lg text-slate-800">Pembayaran</h3>
                    <p class="text-xs text-slate-500">Selesaikan transaksi</p>
                </div>
                <button @click="showPaymentModal = false" class="h-8 w-8 rounded-full bg-white text-slate-400 hover:text-red-500 hover:bg-red-50 flex items-center justify-center transition shadow-sm">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="p-6 overflow-y-auto custom-scrollbar space-y-6">
                
                <div class="text-center">
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Total Yang Harus Dibayar</p>
                    <h2 class="text-4xl font-black text-slate-800 tracking-tight" x-text="'Rp ' + rupiah(grandTotal)"></h2>
                    <p x-show="discountAmount > 0" class="text-xs text-emerald-600 font-bold mt-1">Anda Hemat Rp <span x-text="rupiah(discountAmount)"></span></p>
                </div>

                <div class="bg-slate-50 p-4 rounded-2xl border border-slate-100">
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-2">Pelanggan</label>
                    
                    <div class="flex p-1 bg-white border border-slate-200 rounded-xl mb-3 shadow-sm">
                        <button @click="customerType = 'guest'; selectedCustomerId = '';" 
                                class="flex-1 py-2 text-xs font-bold rounded-lg transition-all"
                                :class="customerType === 'guest' ? 'bg-red-600 text-white shadow' : 'text-black hover:bg-red-300'">
                            Tamu (Guest)
                        </button>
                        <button @click="customerType = 'member'" 
                                class="flex-1 py-2 text-xs font-bold rounded-lg transition-all"
                                :class="customerType === 'member' ? 'bg-green-600 text-white shadow' : 'text-black hover:bg-green-300'">
                            Member
                        </button>
                    </div>

                    <div x-show="customerType === 'guest'" x-transition>
                        <div class="grid grid-cols-2 gap-3">
                            <input type="text" x-model="customerName" placeholder="Nama..." class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-red-500 transition">
                            <input type="number" x-model="customerPhone" placeholder="No. WA..." class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-red-500 transition">
                        </div>
                    </div>

                    <div x-show="customerType === 'member'" style="display: none;" x-transition>
                        <select x-model="selectedCustomerId" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm bg-white font-bold text-slate-700 focus:ring-2 focus:ring-red-500">
                            <option value="">-- Cari Member --</option>
                            @foreach($customers as $c)
                                <option value="{{ $c->id }}" 
                                        data-saldo="{{ $c->saldo }}" 
                                        data-affiliate-balance="{{ $c->affiliate_balance ?? 0 }}"
                                        data-has-pin="{{ $c->has_pin ? 'yes' : 'no' }}">
                                    {{ $c->name }} (Topup: Rp {{ number_format($c->saldo,0,',','.') }} | Profit: Rp {{ number_format($c->affiliate_balance ?? 0,0,',','.') }})
                                </option>
                            @endforeach
                        </select>
                        
                        <div x-show="selectedCustomerId" class="mt-2 grid grid-cols-2 gap-2">
                            <div class="p-2 bg-blue-50 rounded-lg border border-blue-100 text-center">
                                <span class="text-[9px] font-bold text-blue-400 block uppercase">Saldo Topup</span>
                                <span class="text-xs font-black text-blue-700" x-text="'Rp ' + rupiah(getSelectedMemberSaldo())"></span>
                            </div>
                            <div class="p-2 bg-purple-50 rounded-lg border border-purple-100 text-center">
                                <span class="text-[9px] font-bold text-purple-400 block uppercase">Profit Afiliasi</span>
                                <span class="text-xs font-black text-purple-700" x-text="'Rp ' + rupiah(getSelectedAffiliateBalance())"></span>
                            </div>
                        </div>
                    </div>
                </div>


                <div>

                    <div class="bg-blue-50 border-2 border-blue-100 rounded-2xl p-4 shadow-sm mb-6">
                    <label class="block text-[10px] font-bold text-blue-600 uppercase tracking-widest mb-2">Metode Penyerahan</label>
                    
                    <div class="flex p-1 bg-white border border-blue-100 rounded-xl mb-3 shadow-sm">
                        <button @click="deliveryType = 'pickup'; shippingCost = 0; selectedCourier = null" 
                                class="flex-1 py-2 text-xs font-bold rounded-lg transition-all"
                                :class="deliveryType === 'pickup' ? 'bg-blue-600 text-white shadow' : 'text-slate-500 hover:bg-blue-50'">
                            <i class="fas fa-store mr-1"></i> Ambil di Toko
                        </button>
                        <button @click="deliveryType = 'shipping'" 
                                class="flex-1 py-2 text-xs font-bold rounded-lg transition-all"
                                :class="deliveryType === 'shipping' ? 'bg-blue-600 text-white shadow' : 'text-slate-500 hover:bg-blue-50'">
                            <i class="fas fa-truck mr-1"></i> Kirim (KiriminAja)
                        </button>
                    </div>

                    <div x-show="deliveryType === 'shipping'" x-transition class="space-y-4">
    
    <div class="relative z-50"> 
        <label class="text-[10px] font-bold text-slate-500 uppercase">Cari Tujuan (Kelurahan / Kecamatan / Kode Pos)</label>
        
        <div class="relative mt-1">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <i class="fas fa-search text-slate-400"></i>
            </div>

            <input type="text" 
                   x-model="searchQuery" 
                   @input.debounce.500ms="searchLocation()" 
                   placeholder="Ketik Kelurahan, Kecamatan, atau Kode Pos (cth: 63122)..." 
                   class="w-full pl-9 pr-10 py-3 text-xs rounded-xl border border-blue-200 focus:ring-2 focus:ring-blue-500 font-bold text-slate-700 shadow-sm transition-all"
                   autocomplete="off">
            
            <div x-show="isSearchingLocation" class="absolute inset-y-0 right-0 pr-3 flex items-center">
                <i class="fas fa-circle-notch fa-spin text-blue-500"></i>
            </div>
            
            <button x-show="!isSearchingLocation && searchQuery.length > 0" 
                    @click="searchQuery = ''; searchResults = []; destinationDistrictId = '';"
                    class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-red-500">
                <i class="fas fa-times-circle"></i>
            </button>
        </div>

        <div x-show="searchResults.length > 0" 
             x-transition.opacity.duration.200ms
             @click.outside="searchResults = []"
             class="absolute left-0 right-0 mt-1 bg-white border border-slate-100 rounded-xl shadow-2xl max-h-60 overflow-y-auto custom-scrollbar z-50 ring-1 ring-black/5">
            
            <template x-for="(loc, index) in searchResults" :key="index">
                <button @click="selectLocation(loc)" 
                        class="w-full text-left px-4 py-3 hover:bg-blue-50 border-b border-slate-50 last:border-0 transition-colors group flex items-start gap-3">
                    
                    <div class="h-8 w-8 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center group-hover:bg-blue-200 group-hover:text-blue-600 transition shrink-0 mt-1">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    
                    <div class="flex-1 leading-tight">
                        <p class="text-[11px] font-bold text-slate-700 group-hover:text-blue-700" x-text="loc.full_address"></p>
                        <div class="flex flex-wrap gap-1 mt-1">
                            <span class="text-[9px] bg-slate-100 px-1.5 py-0.5 rounded text-slate-500 border border-slate-200" 
                                  x-show="loc.type" x-text="loc.type"></span>
                            <span class="text-[9px] bg-yellow-50 px-1.5 py-0.5 rounded text-yellow-700 border border-yellow-200 font-bold" 
                                  x-show="loc.zip_code" x-text="'Kode Pos: ' + loc.zip_code"></span>
                        </div>
                    </div>
                </button>
            </template>
        </div>

        <div x-show="searchQuery.length > 3 && !isSearchingLocation && searchResults.length === 0 && !destinationDistrictId" 
             class="absolute mt-1 w-full bg-white p-3 rounded-xl shadow-lg border border-red-100 text-center z-50">
            <span class="text-xs text-red-500 font-medium">Lokasi tidak ditemukan. Coba Kode Pos atau nama Kecamatan.</span>
        </div>
    </div>

    <div x-show="courierList.length > 0" x-transition class="space-y-2 max-h-56 overflow-y-auto custom-scrollbar pr-1 bg-white p-2 rounded-xl border border-blue-100">
        <p class="text-[10px] font-bold text-slate-400 px-1 mb-1">PILIH KURIR (TERMURAH):</p>
        
        <template x-for="courier in courierList" :key="courier.service + courier.cost">
            <div @click="selectCourier(courier)" 
                 class="flex justify-between items-center p-3 rounded-lg border cursor-pointer transition hover:bg-blue-50 group relative overflow-hidden"
                 :class="selectedCourier && selectedCourier.service === courier.service && selectedCourier.cost === courier.cost ? 'bg-blue-50 border-blue-500 ring-1 ring-blue-500' : 'bg-white border-slate-100 hover:border-blue-300'">
                
                <div class="flex items-center gap-3 relative z-10">
                    <div class="h-10 w-10 flex items-center justify-center bg-white rounded-lg p-1 border border-slate-100 shadow-sm">
                        <template x-if="courier.logo">
                            <img :src="courier.logo" class="w-full h-full object-contain" alt="Logo">
                        </template>
                        <template x-if="!courier.logo">
                            <i class="fas fa-shipping-fast text-slate-400 text-lg"></i>
                        </template>
                    </div>

                    <div>
                        <p class="text-[11px] font-bold text-slate-700 uppercase" x-text="courier.name"></p>
                        <p class="text-[10px] text-slate-500" x-text="courier.service"></p>
                        <p class="text-[9px] text-slate-400" x-text="'Est: ' + courier.etd + ' Hari'"></p>
                    </div>
                </div>
                
                <div class="text-right relative z-10">
                    <p class="text-sm font-black text-blue-600" x-text="rupiah(courier.cost)"></p>
                    <i x-show="selectedCourier && selectedCourier.service === courier.service && selectedCourier.cost === courier.cost" class="fas fa-check-circle text-blue-600 text-xs mt-1"></i>
                </div>
            </div>
        </template>
    </div>
    
    <div x-show="isLoadingShipping" class="flex flex-col items-center justify-center py-4 bg-slate-50 rounded-xl border border-dashed border-slate-200">
        <i class="fas fa-circle-notch fa-spin text-blue-500 text-xl mb-1"></i>
        <span class="text-[10px] text-slate-400 font-medium">Menghitung Ongkir...</span>
    </div>

</div>
                        
                        <div x-show="isLoadingShipping" class="flex flex-col items-center justify-center py-4 bg-slate-50 rounded-xl border border-dashed border-slate-200">
                            <i class="fas fa-circle-notch fa-spin text-blue-500 text-xl mb-1"></i>
                            <span class="text-[10px] text-slate-400 font-medium">Menghitung Ongkir...</span>
                        </div>

                    </div>
                </div>

                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-3">Pilih Metode Bayar</label>
                    <div class="grid grid-cols-2 gap-3">
                        <div @click="paymentMethod = 'cash'" class="cursor-pointer border-2 rounded-2xl p-4 flex flex-col items-center gap-2 transition relative overflow-hidden group"
                             :class="paymentMethod === 'cash' ? 'border-red-500 bg-red-50 text-red-700' : 'border-slate-100 bg-white hover:border-red-200'">
                            <i class="fas fa-money-bill-wave text-2xl mb-1"></i> <span class="text-xs font-bold">Tunai</span>
                            <div x-show="paymentMethod === 'cash'" class="absolute top-2 right-2 text-red-500"><i class="fas fa-check-circle"></i></div>
                        </div>
                        
                        <div @click="selectAffiliatePayment()" 
                             class="cursor-pointer border-2 rounded-2xl p-4 flex flex-col items-center gap-2 transition relative overflow-hidden group"
                             :class="paymentMethod === 'affiliate_balance' ? 'border-purple-500 bg-purple-50 text-purple-700' : 'border-slate-100 bg-white hover:border-purple-200'">
                            <i class="fas fa-coins text-2xl mb-1"></i> 
                            <div class="text-center leading-tight">
                                <span class="text-xs font-bold block">Saldo Profit</span>
                            </div>
                            <div x-show="paymentMethod === 'affiliate_balance'" class="absolute top-2 right-2 text-purple-500"><i class="fas fa-check-circle"></i></div>
                        </div>

                        <div @click="paymentMethod = 'saldo'" class="cursor-pointer border-2 rounded-2xl p-4 flex flex-col items-center gap-2 transition relative overflow-hidden group"
                             :class="paymentMethod === 'saldo' ? 'border-blue-500 bg-blue-50 text-blue-700' : 'border-slate-100 bg-white hover:border-blue-200'">
                            <i class="fas fa-wallet text-2xl mb-1"></i> <span class="text-xs font-bold">Saldo Topup</span>
                            <div x-show="paymentMethod === 'saldo'" class="absolute top-2 right-2 text-blue-500"><i class="fas fa-check-circle"></i></div>
                        </div>

                        <div @click="paymentMethod = 'tripay'; fetchTripayChannels()" 
                             class="cursor-pointer border-2 rounded-2xl p-4 flex flex-col items-center gap-2 transition relative overflow-hidden group"
                             :class="paymentMethod === 'tripay' ? 'border-red-500 bg-red-50 text-red-700' : 'border-slate-100 bg-white hover:border-red-200'">
                            <i class="fas fa-qrcode text-2xl mb-1"></i> <span class="text-xs font-bold">QRIS / VA</span>
                            <div x-show="paymentMethod === 'tripay'" class="absolute top-2 right-2 text-red-500"><i class="fas fa-check-circle"></i></div>
                        </div>

                        <div @click="paymentMethod = 'doku'" class="cursor-pointer border-2 rounded-2xl p-4 flex flex-col items-center gap-2 transition relative overflow-hidden group"
                             :class="paymentMethod === 'doku' ? 'border-red-500 bg-red-50 text-red-700' : 'border-slate-100 bg-white hover:border-red-200'">
                            <i class="fas fa-credit-card text-2xl mb-1"></i> <span class="text-xs font-bold">QRIS/VA SANCAKA</span>
                            <div x-show="paymentMethod === 'doku'" class="absolute top-2 right-2 text-red-500"><i class="fas fa-check-circle"></i></div>
                        </div>
                    </div>
                </div>

                <div x-show="paymentMethod === 'tripay'" x-transition 
                     class="bg-red-50 border-2 border-red-100 rounded-2xl p-4 shadow-sm mt-3 min-h-[100px]">
                    
                    <label class="block text-[10px] font-bold text-red-600 uppercase tracking-widest mb-3">Pilih Bank / Channel</label>
                    
                    <div x-show="isLoadingChannels" class="flex flex-col items-center justify-center py-6 text-red-300">
                        <i class="fas fa-circle-notch fa-spin text-2xl mb-2"></i>
                        <span class="text-xs">Memuat Channel Pembayaran...</span>
                    </div>

                    <div x-show="!isLoadingChannels && tripayChannels.length === 0" style="display: none;" class="p-4 text-center text-red-500 bg-red-50 rounded-xl border border-red-100">
                        <i class="fas fa-exclamation-triangle text-2xl mb-2"></i>
                        <p class="text-xs font-bold">Gagal memuat channel.</p>
                        <button @click="fetchTripayChannels()" class="mt-2 px-3 py-1 bg-red-100 text-red-600 rounded text-[10px] font-bold">Coba Lagi</button>
                    </div>

                    <div x-show="!isLoadingChannels && tripayChannels.length > 0" class="space-y-4">
                        
                        <div x-show="getChannelsByGroup('E-Wallet').length > 0">
                            <p class="text-[9px] font-bold text-slate-400 mb-2 uppercase">E-Wallet & QRIS</p>
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                                <template x-for="channel in getChannelsByGroup('E-Wallet')" :key="channel.code">
                                    <button @click="paymentChannel = channel.code" 
                                            class="p-2 rounded-xl border transition flex flex-col items-center justify-center gap-2 h-20 bg-white hover:border-red-300 relative overflow-hidden"
                                            :class="paymentChannel === channel.code ? 'border-red-600 ring-1 ring-red-600 bg-red-50' : 'border-red-100'">
                                        <img :src="channel.icon_url" :alt="channel.name" class="h-6 w-auto object-contain">
                                        <span class="text-[10px] font-bold text-slate-600 leading-none text-center" x-text="channel.name"></span>
                                        <div x-show="paymentChannel === channel.code" class="absolute top-1 right-1 text-red-600"><i class="fas fa-check-circle text-xs"></i></div>
                                    </button>
                                </template>
                            </div>
                        </div>

                        <div x-show="getChannelsByGroup('Virtual Account').length > 0">
                            <p class="text-[9px] font-bold text-slate-400 mb-2 uppercase">Virtual Account</p>
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                                <template x-for="channel in getChannelsByGroup('Virtual Account')" :key="channel.code">
                                    <button @click="paymentChannel = channel.code" 
                                            class="p-2 rounded-xl border transition flex flex-col items-center justify-center gap-2 h-20 bg-white hover:border-red-300 relative"
                                            :class="paymentChannel === channel.code ? 'border-red-600 ring-1 ring-red-600 bg-red-50' : 'border-red-100'">
                                        
                                        <img :src="channel.icon_url" :alt="channel.name" class="h-8 w-auto object-contain">
                                        <div x-show="paymentChannel === channel.code" class="absolute top-1 right-1 text-red-600"><i class="fas fa-check-circle text-xs"></i></div>
                                    </button>
                                </template>
                            </div>
                        </div>

                        <div x-show="getChannelsByGroup('Convenience Store').length > 0">
                            <p class="text-[9px] font-bold text-slate-400 mb-2 uppercase">Minimarket</p>
                            <div class="grid grid-cols-2 gap-2">
                                <template x-for="channel in getChannelsByGroup('Convenience Store')" :key="channel.code">
                                    <button @click="paymentChannel = channel.code" 
                                            class="p-2 rounded-xl border transition flex flex-col items-center justify-center gap-2 h-16 bg-white hover:border-red-300 relative"
                                            :class="paymentChannel === channel.code ? 'border-red-600 ring-1 ring-red-600 bg-red-50' : 'border-red-100'">
                                        
                                        <img :src="channel.icon_url" :alt="channel.name" class="h-6 w-auto object-contain">
                                        <div x-show="paymentChannel === channel.code" class="absolute top-1 right-1 text-red-600"><i class="fas fa-check-circle text-xs"></i></div>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </div>
                    <p class="text-[9px] text-red-400 mt-3 text-center" x-show="!isLoadingChannels">*Pilih metode pembayaran.</p>
                </div>

                <div x-show="paymentMethod === 'affiliate_balance'" x-transition class="bg-purple-50 border-2 border-purple-200 rounded-2xl p-4 shadow-sm text-center">
                    <label class="block text-[10px] font-bold text-purple-600 uppercase mb-2">Masukkan PIN Keamanan</label>
                    <div class="relative max-w-[200px] mx-auto">
                        <input type="password" x-model="affiliatePin" placeholder="******" maxlength="6"
                               class="w-full px-4 py-3 text-center text-xl font-black text-purple-800 bg-white rounded-xl border-purple-200 focus:ring-2 focus:ring-purple-500 tracking-widest transition">
                    </div>
                    <p class="text-[9px] text-purple-400 mt-2">*PIN diperlukan untuk menggunakan saldo profit.</p>
                </div>

                <div x-show="paymentMethod === 'cash'" x-transition class="bg-white border-2 border-slate-100 rounded-2xl p-4 shadow-sm">
                    <label class="block text-[10px] font-bold text-slate-500 uppercase mb-2">Uang Diterima</label>
                    <div class="relative">
                        <span class="absolute left-4 top-3 text-slate-400 font-bold">Rp</span>
                        <input type="number" x-model="cashAmount" placeholder="0" 
                               class="w-full pl-12 pr-4 py-3 text-xl font-black text-slate-800 bg-slate-50 rounded-xl border-none focus:ring-2 focus:ring-red-500 transition">
                    </div>
                    
                    <div class="flex justify-between items-center mt-4 pt-4 border-t border-dashed border-slate-200">
                        <span class="text-xs font-bold text-slate-400">Kembalian</span>
                        <span class="text-xl font-black" :class="change < 0 ? 'text-red-500' : 'text-emerald-500'" x-text="'Rp ' + rupiah(change)"></span>
                    </div>
                    
                    <div class="flex gap-2 mt-3 justify-end">
                         <button @click="cashAmount = 50000" class="text-[10px] px-3 py-1.5 bg-slate-100 rounded-lg font-bold hover:bg-slate-200">50k</button>
                         <button @click="cashAmount = 100000" class="text-[10px] px-3 py-1.5 bg-slate-100 rounded-lg font-bold hover:bg-slate-200">100k</button>
                         <button @click="cashAmount = grandTotal" class="text-[10px] px-3 py-1.5 bg-slate-800 text-white rounded-lg font-bold hover:bg-black">Pas</button>
                    </div>
                </div>

            </div>

            <div class="p-6 border-t border-slate-100 bg-white">
                <button @click="checkout()" :disabled="isProcessing" 
                        class="w-full bg-red-600 text-white py-4 rounded-2xl font-bold text-lg shadow-xl shadow-red-200 hover:bg-red-700 active:scale-[0.98] transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-3">
                    <span x-show="!isProcessing">Proses Pembayaran</span>
                    <span x-show="isProcessing"><i class="fas fa-spinner fa-spin"></i> Memproses...</span>
                </button>
            </div>
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
            selectedCustomerId: '',
            
            // --- 4. PEMBAYARAN ---
            paymentMethod: 'cash',
            paymentChannel: '',     // Untuk Tripay (BRIVA, QRIS, dll)
            tripayChannels: [],     // List channel dari API
            isLoadingChannels: false,
            cashAmount: '',         // Uang tunai
            affiliatePin: '',       // PIN Member

            // --- 5. PENGIRIMAN (KIRIMINAJA) ---
            deliveryType: 'pickup', // Default: Ambil di Toko
            
            // Variabel Pencarian Lokasi
            searchQuery: '',        // Input teks user
            searchResults: [],      // Hasil dropdown API
            isSearchingLocation: false,
            
            // ID Lokasi (Penting untuk API Ongkir)
            destinationDistrictId: '', 
            destinationSubdistrictId: '', 
            
            // Hasil Ongkir
            courierList: [],
            selectedCourier: null,
            shippingCost: 0,
            isLoadingShipping: false,

            // ============================================================
            // COMPUTED PROPERTIES (PERHITUNGAN OTOMATIS)
            // ============================================================
            
            get subtotal() { 
                return this.cart.reduce((sum, item) => sum + (item.price * item.qty), 0); 
            },
            
            get cartTotalQty() { 
                return this.cart.reduce((sum, item) => sum + item.qty, 0); 
            },
            
            // GRAND TOTAL: Subtotal - Diskon + Ongkir
            get grandTotal() { 
                let total = this.subtotal - this.discountAmount + this.shippingCost;
                return total < 0 ? 0 : total;
            },
            
            // KEMBALIAN: Uang Masuk - Grand Total
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
            // LOGIKA PENGIRIMAN (KIRIMINAJA - AUTOCOMPLETE)
            // ============================================================

            // --- A. PENCARIAN LOKASI (Desa/Kecamatan/Kode Pos) ---
            async searchLocation() {
                // Hapus jika input kurang dari 3 karakter
                if (this.searchQuery.length < 3) {
                    this.searchResults = [];
                    return;
                }
                
                this.isSearchingLocation = true;
                
                try {
                    // Pastikan route backend menangani parameter 'query' yang berisi kode pos atau nama daerah
                    const response = await fetch(`{{ route('orders.search-location') }}?query=${this.searchQuery}`);
                    const result = await response.json();

                    if (result.status === 'success') {
                        // Backend diharapkan mengembalikan: { id, text, type (Kecamatan/Kelurahan), zip_code, district_id }
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

            // --- B. MEMILIH LOKASI ---
            selectLocation(location) {
    // 1. Ganti 'text' jadi 'full_address'
    this.searchQuery = location.full_address; 
    
    // 2. Simpan ID Hirarki Wilayah (Sesuaikan dengan JSON di gambar)
    this.destinationDistrictId = location.district_id; 
    
    // PERHATIKAN INI: JSON Anda pakai 'subdistrict_id', bukan 'id'
    this.destinationSubdistrictId = location.subdistrict_id; 

    // Reset dropdown & hitung ongkir
    this.searchResults = [];
    this.checkOngkir();
            },

            // --- C. CEK ONGKIR (Update Payload) ---
            async checkOngkir() {
                if (!this.destinationDistrictId) return;
                
                // Hitung Berat
                let realTotalWeight = this.cart.reduce((w, item) => w + (item.qty * (item.weight > 0 ? item.weight : 100)), 0);
                // Default minimal 1kg (1000g) jika total berat sangat kecil
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
    destination_text: this.searchQuery, // <-- WAJIB DITAMBAHKAN!
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

            // D. Saat Kurir Dipilih
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

            // TRIPAY: Ambil Channel Pembayaran
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

            // KUPON: Validasi Kode
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

            // ============================================================
            // LOGIKA KERANJANG (CART)
            // ============================================================
            
            // Tambahkan parameter 'weight' di sini
            addToCart(id, name, price, maxStock, weight = 0) {
                if (maxStock <= 0) { alert('Stok Habis!'); return; }
                
                // --- LOGIKA CERDAS: AUTO 5 GRAM ---
                // Jika input weight 0 (dari Blade), ubah jadi 5 gram.
                // Jika ada isinya (misal 200), pakai 200.
                let realWeight = (parseInt(weight) > 0) ? parseInt(weight) : 5;
                // ----------------------------------

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
                        weight: realWeight // Simpan sebagai 5 gram
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
                // Reset input jika metode bukan cash
                if(this.paymentMethod !== 'cash') this.cashAmount = '';
                // Auto fetch channel jika tripay
                if(this.paymentMethod === 'tripay') this.fetchTripayChannels();
            },
            
            handleFileUpload(event) {
                const files = event.target.files;
                for (let i = 0; i < files.length; i++) {
                    if(files[i].size > 10 * 1024 * 1024) { alert('File terlalu besar'); continue; }
                    this.uploadedFiles.push(files[i]);
                }
                event.target.value = '';
            },
            
            removeFile(index) { this.uploadedFiles.splice(index, 1); },

            // ============================================================
            // FUNGSI CHECKOUT (KIRIM DATA KE LARAVEL)
            // ============================================================
            
            async checkout() {
                // 1. VALIDASI TUNAI
                if (this.paymentMethod === 'cash') {
                    if (!this.cashAmount || this.change < 0) { alert('❌ Uang tunai kurang!'); return; }
                } 
                // 2. VALIDASI TRIPAY
                else if (this.paymentMethod === 'tripay') {
                    if (!this.paymentChannel) { alert('❌ Silakan pilih Bank / Channel Pembayaran dulu!'); return; }
                } 
                // 3. VALIDASI SALDO MEMBER
                else if (this.paymentMethod === 'saldo') {
                    if (!this.selectedCustomerId) { alert('❌ Pilih Member!'); return; }
                    if (this.getSelectedMemberSaldo() < this.grandTotal) { alert('❌ Saldo Topup kurang!'); return; }
                } 
                // 4. VALIDASI AFFILIATE
                else if (this.paymentMethod === 'affiliate_balance') {
                    if (!this.selectedCustomerId) { alert('❌ Pilih Member!'); return; }
                    if (this.getSelectedAffiliateBalance() < this.grandTotal) { alert('❌ Saldo Profit kurang!'); return; }
                    if (!this.affiliatePin || this.affiliatePin.length < 4) { alert('❌ Masukkan PIN Keamanan!'); return; }
                }

                // 5. VALIDASI PENGIRIMAN (KIRIMINAJA)
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
                
                // Susun Data Form
                let formData = new FormData();
                formData.append('items', JSON.stringify(this.cart));
                formData.append('total', this.subtotal);
                formData.append('coupon', this.couponCode);
                formData.append('payment_method', this.paymentMethod);

                // Data Pengiriman
                formData.append('delivery_type', this.deliveryType);
                if (this.deliveryType === 'shipping') {
                    formData.append('shipping_cost', this.shippingCost);
                    formData.append('courier_name', this.selectedCourier.name + ' - ' + this.selectedCourier.service);
                    // Opsi: Kirim data detail lokasi jika perlu disimpan di DB
                    formData.append('destination_district_id', this.destinationDistrictId);
                    formData.append('destination_subdistrict_id', this.destinationSubdistrictId);
                    formData.append('destination_text', this.searchQuery);
                }

                // Data Pembayaran Tripay
                if (this.paymentMethod === 'tripay') {
                    formData.append('payment_channel', this.paymentChannel);
                }
                
                // Data Member
                if(this.customerType === 'member' && this.selectedCustomerId) {
                    formData.append('customer_id', this.selectedCustomerId);
                } else {
                    formData.append('customer_name', this.customerName || 'Guest');
                    formData.append('customer_phone', this.customerPhone || '');
                }

                if(this.paymentMethod === 'cash') formData.append('cash_amount', this.cashAmount);
                if(this.paymentMethod === 'affiliate_balance') formData.append('affiliate_pin', this.affiliatePin); 

                this.uploadedFiles.forEach(file => formData.append('attachments[]', file));

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