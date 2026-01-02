<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Kasir POS - Sancaka</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        [x-cloak] { display: none !important; }
        /* Custom Scrollbar */
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: #f1f1f1; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        
        /* Hide Number Arrows */
        input[type=number]::-webkit-inner-spin-button, 
        input[type=number]::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
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
                        <div @click="addToCart({{ $product->id }}, '{{ addslashes($product->name) }}', {{ $product->sell_price }}, {{ $product->stock }})"
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
                                 x-text="getItemQty({{ $product->id }})"
                                 x-transition.scale>
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
            
            <div class="h-16 px-5 border-b border-slate-100 flex justify-between items-center bg-white shrink-0">
                <div class="flex flex-col">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Pesanan Baru</span>
                    <span class="font-black text-slate-800 text-lg">#{{ date('ymd') }}-{{ rand(100,999) }}</span>
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
                    <div class="relative border-2 border-dashed border-slate-300 rounded-xl bg-white hover:border-red-400 hover:bg-red-50 transition-all cursor-pointer group h-20 flex items-center justify-center">
                        <input type="file" multiple @change="handleFileUpload" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" 
                               accept=".doc,.docx,.pdf,.xls,.xlsx,.jpg,.jpeg,.png">
                        <div class="text-center pointer-events-none flex flex-col items-center">
                            <i class="fas fa-cloud-upload-alt text-slate-400 group-hover:text-red-500 text-xl mb-1 transition-colors"></i>
                            <p class="text-[10px] font-bold text-slate-500 group-hover:text-red-600">Klik / Drag File Pesanan</p>
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
                            <div class="flex flex-col items-center bg-slate-50 rounded-lg p-0.5 border border-slate-200 shrink-0">
                                <button @click="updateQty(item.id, 1)" class="w-6 h-6 flex items-center justify-center text-slate-500 hover:text-red-600 hover:bg-white rounded transition"><i class="fas fa-plus text-[9px]"></i></button>
                                <span class="font-bold text-xs py-0.5 w-6 text-center" x-text="item.qty"></span>
                                <button @click="updateQty(item.id, -1)" class="w-6 h-6 flex items-center justify-center text-slate-500 hover:text-red-600 hover:bg-white rounded transition"><i class="fas fa-minus text-[9px]"></i></button>
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

                <div class="p-4 border-t border-slate-100 bg-slate-50/50">
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-2">Pelanggan</label>
                    
                    <div class="flex p-1 bg-white border border-slate-200 rounded-xl mb-3">
                        <button @click="customerType = 'guest'; selectedCustomerId = ''; customerName = ''; customerPhone = '';" 
                                class="flex-1 py-1.5 text-xs font-bold rounded-lg transition-all"
                                :class="customerType === 'guest' ? 'bg-slate-800 text-white shadow-md' : 'text-slate-400 hover:bg-slate-50'">
                            Tamu
                        </button>
                        <button @click="customerType = 'member'" 
                                class="flex-1 py-1.5 text-xs font-bold rounded-lg transition-all"
                                :class="customerType === 'member' ? 'bg-slate-800 text-white shadow-md' : 'text-slate-400 hover:bg-slate-50'">
                            Member
                        </button>
                    </div>

                    <div x-show="customerType === 'guest'" x-transition>
                        <input type="text" x-model="customerName" placeholder="Nama Pelanggan" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-sm mb-2 focus:ring-2 focus:ring-red-500 focus:border-red-500 transition">
                        <input type="number" x-model="customerPhone" placeholder="No. HP / WA (628...)" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-red-500 focus:border-red-500 transition">
                    </div>

                    <div x-show="customerType === 'member'" style="display: none;" x-transition>
                        <select x-model="selectedCustomerId" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-sm bg-white font-bold text-slate-700 focus:ring-2 focus:ring-red-500">
                            <option value="">-- Pilih Member --</option>
                            @foreach($customers as $c)
                                <option value="{{ $c->id }}" data-saldo="{{ $c->saldo }}" data-name="{{ $c->name }}" data-phone="{{ $c->phone }}">
                                    {{ $c->name }} (Saldo: Rp {{ number_format($c->saldo, 0, ',', '.') }})
                                </option>
                            @endforeach
                        </select>
                        <div x-show="selectedCustomerId" class="mt-2 text-center p-2 bg-blue-50 rounded-lg border border-blue-100">
                            <p class="text-[10px] text-blue-500 font-bold uppercase">Sisa Saldo Member</p>
                            <p class="text-sm font-black text-blue-700" x-text="'Rp ' + rupiah(getSelectedMemberSaldo())"></p>
                        </div>
                    </div>
                </div>

                <div class="p-4 border-t border-slate-100">
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-2">Metode Pembayaran</label>
                    <div class="grid grid-cols-2 gap-2 mb-4">
                        <div @click="paymentMethod = 'cash'" 
                             class="cursor-pointer border rounded-xl p-3 flex flex-col items-center gap-1 transition relative overflow-hidden"
                             :class="paymentMethod === 'cash' ? 'border-red-500 bg-red-50 text-red-700 ring-1 ring-red-500' : 'border-slate-200 bg-white hover:border-red-200'">
                            <div x-show="paymentMethod === 'cash'" class="absolute top-0 right-0 bg-red-500 text-white text-[8px] px-1.5 rounded-bl-lg"><i class="fas fa-check"></i></div>
                            <i class="fas fa-money-bill-wave"></i> <span class="text-xs font-bold">Tunai</span>
                        </div>
                        <div @click="paymentMethod = 'saldo'" 
                             class="cursor-pointer border rounded-xl p-3 flex flex-col items-center gap-1 transition relative overflow-hidden"
                             :class="paymentMethod === 'saldo' ? 'border-red-500 bg-red-50 text-red-700 ring-1 ring-red-500' : 'border-slate-200 bg-white hover:border-red-200'">
                            <div x-show="paymentMethod === 'saldo'" class="absolute top-0 right-0 bg-red-500 text-white text-[8px] px-1.5 rounded-bl-lg"><i class="fas fa-check"></i></div>
                            <i class="fas fa-wallet"></i> <span class="text-xs font-bold">Saldo</span>
                        </div>
                        <div @click="paymentMethod = 'tripay'" 
                             class="cursor-pointer border rounded-xl p-3 flex flex-col items-center gap-1 transition relative overflow-hidden"
                             :class="paymentMethod === 'tripay' ? 'border-red-500 bg-red-50 text-red-700 ring-1 ring-red-500' : 'border-slate-200 bg-white hover:border-red-200'">
                             <div x-show="paymentMethod === 'tripay'" class="absolute top-0 right-0 bg-red-500 text-white text-[8px] px-1.5 rounded-bl-lg"><i class="fas fa-check"></i></div>
                             <i class="fas fa-qrcode"></i> <span class="text-xs font-bold">QRIS/VA</span>
                        </div>
                        <div @click="paymentMethod = 'doku'" 
                             class="cursor-pointer border rounded-xl p-3 flex flex-col items-center gap-1 transition relative overflow-hidden"
                             :class="paymentMethod === 'doku' ? 'border-red-500 bg-red-50 text-red-700 ring-1 ring-red-500' : 'border-slate-200 bg-white hover:border-red-200'">
                             <div x-show="paymentMethod === 'doku'" class="absolute top-0 right-0 bg-red-500 text-white text-[8px] px-1.5 rounded-bl-lg"><i class="fas fa-check"></i></div>
                             <i class="fas fa-credit-card"></i> <span class="text-xs font-bold">Doku</span>
                        </div>
                    </div>

                    <div x-show="paymentMethod === 'cash'" x-transition class="bg-white border border-slate-200 rounded-xl p-3 shadow-sm mb-4">
                        <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Uang Diterima</label>
                        <div class="relative">
                            <span class="absolute left-0 top-0 text-slate-400 font-bold p-2">Rp</span>
                            <input type="number" x-model="cashAmount" placeholder="0" 
                                   class="w-full pl-8 pr-2 py-2 text-right font-black text-lg text-slate-800 border-b-2 border-slate-200 focus:border-red-500 focus:outline-none bg-transparent">
                        </div>
                        <div class="flex justify-between items-center mt-3 pt-2 border-t border-dashed border-slate-100">
                            <span class="text-xs font-bold text-slate-400">Kembalian:</span>
                            <span class="text-lg font-black" :class="change < 0 ? 'text-red-500' : 'text-emerald-500'" x-text="'Rp ' + rupiah(change)"></span>
                        </div>
                        <div class="flex justify-end mt-2">
                             <button @click="cashAmount = subtotal" class="text-[10px] px-2 py-1 bg-slate-100 rounded text-slate-600 font-bold hover:bg-slate-200">Uang Pas</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="p-4 bg-slate-50 border-t border-slate-200 z-20 shrink-0 shadow-[0_-5px_15px_rgba(0,0,0,0.02)]">
                <div class="flex gap-2 mb-3">
                    <input type="text" x-model="couponCode" placeholder="Kode Promo" class="flex-1 px-3 py-2 text-sm rounded-lg border border-slate-200 focus:ring-red-500 uppercase">
                </div>

                <div class="flex justify-between items-end mb-4">
                    <span class="text-sm font-bold text-slate-500">Total Tagihan</span>
                    <span class="text-2xl font-black text-slate-800 tracking-tight" x-text="'Rp ' + rupiah(subtotal)"></span>
                </div>

                <button @click="checkout()" :disabled="cart.length === 0 || isProcessing" 
                        class="w-full bg-slate-900 text-white py-4 rounded-xl font-bold text-base shadow-lg hover:bg-black active:scale-[0.98] transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2 group">
                    <span x-show="!isProcessing" class="flex items-center gap-2">
                        <span>Bayar & Proses</span> <i class="fas fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                    </span>
                    <span x-show="isProcessing" class="flex items-center gap-2">
                        <i class="fas fa-spinner fa-spin"></i> <span>Memproses Transaksi...</span>
                    </span>
                </button>
            </div>
        </div>
    </div>

    <script>
        function posSystem() {
            return {
                mobileCartOpen: false,
                search: '',
                cart: [],
                uploadedFiles: [],
                isProcessing: false,
                
                // Form Data
                couponCode: '',
                customerType: 'guest',
                customerName: '',
                customerPhone: '',
                selectedCustomerId: '',
                paymentMethod: 'cash',
                cashAmount: '',

                // --- COMPUTED ---
                get subtotal() {
                    return this.cart.reduce((sum, item) => sum + (item.price * item.qty), 0);
                },
                get cartTotalQty() {
                    return this.cart.reduce((sum, item) => sum + item.qty, 0);
                },
                get change() {
                    let received = parseInt(this.cashAmount) || 0;
                    return received - this.subtotal;
                },

                // --- HELPERS ---
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
                getSelectedMemberSaldo() {
                    if(!this.selectedCustomerId) return 0;
                    const select = document.querySelector(`select[x-model="selectedCustomerId"]`);
                    const option = select.querySelector(`option[value="${this.selectedCustomerId}"]`);
                    return option ? parseFloat(option.dataset.saldo) : 0;
                },

                // --- CART ACTIONS ---
                addToCart(id, name, price, maxStock) {
                    if (maxStock <= 0) { alert('Stok Habis!'); return; }
                    let item = this.cart.find(i => i.id === id);
                    if (item) {
                        if (item.qty < maxStock) item.qty++;
                        else alert('Stok maksimal tercapai!');
                    } else {
                        this.cart.push({ id, name, price, qty: 1, maxStock });
                    }
                    if(navigator.vibrate) navigator.vibrate(30);
                },
                updateQty(id, amount) {
                    let item = this.cart.find(i => i.id === id);
                    if (item) {
                        if (amount > 0 && item.qty >= item.maxStock) { alert('Stok maksimal tercapai'); return; }
                        item.qty += amount;
                        if (item.qty <= 0) this.removeFromCart(id);
                    }
                },
                removeFromCart(id) {
                    this.cart = this.cart.filter(i => i.id !== id);
                },
                confirmClearCart() {
                    if(confirm('Kosongkan keranjang?')) { this.cart = []; this.uploadedFiles = []; }
                },

                // --- FILE UPLOAD ---
                handleFileUpload(event) {
                    const files = event.target.files;
                    for (let i = 0; i < files.length; i++) {
                        if(files[i].size > 10 * 1024 * 1024) { alert(files[i].name + ' Terlalu besar (Max 10MB)'); continue; }
                        this.uploadedFiles.push(files[i]);
                    }
                    event.target.value = ''; // Reset input
                },
                removeFile(index) {
                    this.uploadedFiles.splice(index, 1);
                },

                // --- CHECKOUT ---
                async checkout() {
                    if (this.cart.length === 0) return;

                    // 1. Validasi Metode Bayar
                    if (this.paymentMethod === 'cash') {
                        if (!this.cashAmount || this.change < 0) {
                            alert('❌ Uang tunai kurang dari total tagihan!'); return;
                        }
                    } else if (this.paymentMethod === 'saldo') {
                        if (!this.selectedCustomerId) { alert('❌ Pilih Member untuk Potong Saldo!'); return; }
                        if (this.getSelectedMemberSaldo() < this.subtotal) { alert('❌ Saldo Member tidak cukup!'); return; }
                    }

                    // 2. Siapkan Data
                    this.isProcessing = true;
                    let formData = new FormData();
                    formData.append('items', JSON.stringify(this.cart));
                    formData.append('total', this.subtotal);
                    formData.append('coupon', this.couponCode);
                    formData.append('payment_method', this.paymentMethod);
                    
                    // Data Pelanggan
                    if(this.customerType === 'member' && this.selectedCustomerId) {
                        formData.append('customer_id', this.selectedCustomerId);
                    } else {
                        formData.append('customer_name', this.customerName || 'Guest');
                        formData.append('customer_phone', this.customerPhone || '');
                    }

                    // Data Cash
                    if(this.paymentMethod === 'cash') {
                        formData.append('cash_amount', this.cashAmount);
                    }

                    // Files
                    this.uploadedFiles.forEach(file => formData.append('attachments[]', file));

                    // 3. Kirim ke Server
                    try {
                        const response = await fetch("{{ route('orders.store') }}", {
                            method: "POST",
                            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') },
                            body: formData
                        });
                        
                        const result = await response.json();

                        if (result.status === 'success') {
                            let msg = `✅ Transaksi Berhasil!\nInvoice: ${result.invoice}`;
                            if(this.paymentMethod === 'cash') msg += `\n💰 Kembalian: Rp ${this.rupiah(result.change_amount)}`;
                            
                            alert(msg);

                            // Buka Link Pembayaran (Tripay/Doku)
                            if (result.payment_url) {
                                window.open(result.payment_url, '_blank');
                            }

                            window.location.reload();
                        } else {
                            throw new Error(result.message);
                        }
                    } catch (error) {
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