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

    @include('orders.partials.payment-modal')

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