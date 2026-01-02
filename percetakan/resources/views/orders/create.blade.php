<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>POS Percetakan - Toko Sancaka</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        [x-cloak] { display: none !important; }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        .touch-scroll { -webkit-overflow-scrolling: touch; }
    </style>
</head>
<body class="bg-slate-100 font-sans text-slate-800 h-screen overflow-hidden select-none" x-data="posSystem()">

    <div class="flex h-full w-full flex-col lg:flex-row overflow-hidden">
        
        <div class="flex-1 flex flex-col h-full relative border-r border-slate-200">
            
            <div class="h-16 px-4 bg-white shadow-sm z-20 flex items-center justify-between shrink-0 border-b border-slate-100">
                <h1 class="text-xl font-bold text-slate-800 flex items-center gap-2">
                    <i class="fas fa-print text-red-600"></i>
                    <span class="hidden sm:inline">Sancaka POS</span>
                </h1>
                
                <div class="relative w-full max-w-md mx-4">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400"><i class="fas fa-search"></i></span>
                    <input type="text" x-model="search" placeholder="Cari layanan..." 
                           class="w-full pl-10 pr-4 py-2 rounded-lg bg-slate-100 border-none focus:ring-2 focus:ring-red-500 text-sm">
                    <button x-show="search.length > 0" @click="search = ''" class="absolute inset-y-0 right-0 pr-3 text-slate-400 hover:text-slate-600">
                        <i class="fas fa-times-circle"></i>
                    </button>
                </div>

                <button @click="mobileCartOpen = !mobileCartOpen" class="lg:hidden relative p-2 bg-red-50 rounded-lg text-red-700">
                    <i class="fas fa-shopping-cart"></i>
                    <span x-show="cartTotalQty > 0" class="absolute -top-1 -right-1 bg-red-600 text-white text-[10px] font-bold h-4 w-4 flex items-center justify-center rounded-full" x-text="cartTotalQty"></span>
                </button>
            </div>

            <div class="flex-1 overflow-y-auto p-4 touch-scroll bg-slate-50">
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-5 gap-3">
                    @forelse($products as $product)
                    <template x-if="itemMatchesSearch('{{ $product->name }}')">
                        <div @click="addToCart({{ $product->id }}, '{{ $product->name }}', {{ $product->sell_price }}, {{ $product->stock }})"
                             class="relative bg-white rounded-xl p-3 shadow-sm border border-slate-100 flex flex-col h-full group
                             {{ $product->stock <= 0 ? 'opacity-60 grayscale cursor-not-allowed' : 'cursor-pointer active:scale-95 hover:border-red-400 hover:shadow-md' }} transition-all">
                            
                            <div class="absolute top-2 left-2 z-10">
                                @if($product->stock <= 0) <span class="bg-slate-600 text-white text-[10px] font-bold px-2 py-0.5 rounded">Habis</span>
                                @elseif($product->stock <= 5) <span class="bg-amber-500 text-white text-[10px] font-bold px-2 py-0.5 rounded">Sisa {{ $product->stock }}</span>
                                @endif
                            </div>

                            <div x-show="getItemQty({{ $product->id }}) > 0" 
                                 class="absolute top-2 right-2 bg-red-600 text-white text-xs font-bold h-6 w-6 rounded-full flex items-center justify-center shadow-md z-10"
                                 x-text="getItemQty({{ $product->id }})">
                            </div>

                            <div class="aspect-square bg-red-50 rounded-lg flex items-center justify-center mb-2 text-3xl mt-4 text-red-400">
                                <i class="fas fa-box-open"></i>
                            </div>
                            
                            <div class="flex-1 flex flex-col">
                                <h3 class="font-bold text-slate-700 text-sm leading-tight mb-1 line-clamp-2 group-hover:text-red-600">{{ $product->name }}</h3>
                                <p class="text-[10px] font-bold text-slate-400 uppercase mb-auto">{{ $product->unit }}</p>
                                <p class="text-red-600 font-bold text-sm mt-2">Rp {{ number_format($product->sell_price, 0, ',', '.') }}</p>
                            </div>
                        </div>
                    </template>
                    @empty
                    <div class="col-span-full flex flex-col items-center justify-center text-slate-400 mt-10">
                        <i class="fas fa-box-open text-4xl mb-2"></i>
                        <p>Belum ada produk tersedia.</p>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>

        <div x-show="mobileCartOpen" class="fixed inset-0 bg-black/50 z-30 lg:hidden backdrop-blur-sm" @click="mobileCartOpen = false" x-transition.opacity></div>

        <div class="fixed inset-y-0 right-0 w-[90%] sm:w-[400px] lg:static lg:w-[380px] bg-white shadow-xl lg:shadow-none z-40 transform transition-transform duration-300 ease-in-out flex flex-col h-full"
             :class="mobileCartOpen ? 'translate-x-0' : 'translate-x-full lg:translate-x-0'">
            
            <div class="h-16 px-4 border-b border-slate-100 flex justify-between items-center bg-white shrink-0">
                <div class="flex items-center gap-2">
                    <h2 class="font-bold text-lg text-slate-800">Pesanan</h2>
                    <span class="bg-red-50 text-red-600 text-[10px] font-bold px-2 py-0.5 rounded border border-red-100">#{{ date('Hi') }}</span>
                </div>
                <div class="flex items-center gap-2">
                    <button x-show="cart.length > 0" @click="confirmClearCart()" class="hidden lg:flex items-center gap-1 text-xs font-bold text-red-500 hover:text-red-700 bg-red-50 px-2 py-1 rounded">
                        <i class="fas fa-trash-alt"></i> Reset
                    </button>
                    <button @click="mobileCartOpen = false" class="lg:hidden p-2 text-slate-400"><i class="fas fa-times text-xl"></i></button>
                </div>
            </div>

            <div class="p-4 bg-slate-50 border-b border-slate-200 shrink-0 space-y-3 max-h-[30vh] overflow-y-auto custom-scrollbar">
                
                <div class="relative border-2 border-dashed border-red-300 rounded-xl bg-white hover:bg-red-50 transition-colors cursor-pointer group h-20 flex items-center justify-center">
                    <input type="file" multiple @change="handleFileUpload" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" 
                           accept=".doc,.docx,.pdf,.xls,.xlsx,.jpg,.jpeg,.png">
                    <div class="text-center pointer-events-none flex flex-col items-center">
                        <div class="text-red-400 group-hover:scale-110 transition-transform"><i class="fas fa-cloud-upload-alt text-xl"></i></div>
                        <p class="text-[10px] font-bold text-slate-600 group-hover:text-red-600 mt-1">Upload File Pesanan</p>
                    </div>
                </div>

                <div x-show="uploadedFiles.length > 0" class="space-y-2" x-transition>
                    <div class="flex justify-between items-end">
                        <span class="text-[10px] font-bold text-slate-500 uppercase">File Terlampir (<span x-text="uploadedFiles.length"></span>)</span>
                        <button @click="uploadedFiles = []" class="text-[10px] text-red-500 hover:underline">Hapus Semua</button>
                    </div>

                    <template x-for="(file, index) in uploadedFiles" :key="index">
                        <div class="flex items-center gap-2 p-2 bg-white border border-slate-200 rounded-lg shadow-sm group hover:border-red-200 transition-colors">
                            
                            <div class="h-6 w-6 rounded flex items-center justify-center text-xs"
                                 :class="{
                                    'bg-red-100 text-red-600': file.type.includes('pdf'),
                                    'bg-blue-100 text-blue-600': file.type.includes('word') || file.type.includes('doc'),
                                    'bg-green-100 text-green-600': file.type.includes('sheet') || file.type.includes('excel'),
                                    'bg-purple-100 text-purple-600': file.type.includes('image'),
                                    'bg-slate-100 text-slate-500': !file.type.match(/(pdf|word|doc|sheet|excel|image)/)
                                 }">
                                <i class="fas" 
                                   :class="{
                                       'fa-file-pdf': file.type.includes('pdf'),
                                       'fa-file-word': file.type.includes('word') || file.type.includes('doc'),
                                       'fa-file-excel': file.type.includes('sheet') || file.type.includes('excel'),
                                       'fa-file-image': file.type.includes('image'),
                                       'fa-file': !file.type.match(/(pdf|word|doc|sheet|excel|image)/)
                                   }"></i>
                            </div>

                            <div class="flex-1 min-w-0">
                                <p class="text-[11px] font-bold text-slate-700 truncate" x-text="file.name"></p>
                                <p class="text-[9px] text-slate-400" x-text="formatFileSize(file.size)"></p>
                            </div>

                            <button @click="removeFile(index)" class="text-slate-300 hover:text-red-500 p-1">
                                <i class="fas fa-times text-xs"></i>
                            </button>
                        </div>
                    </template>
                </div>
            </div>

            <div class="flex-1 overflow-y-auto p-3 space-y-2 bg-white touch-scroll">
                <template x-if="cart.length === 0">
                    <div class="h-full flex flex-col items-center justify-center text-slate-300 space-y-3 opacity-60">
                        <i class="fas fa-cash-register text-5xl"></i>
                        <p class="font-medium text-sm">Keranjang kosong</p>
                    </div>
                </template>

                <template x-for="item in cart" :key="item.id">
                    <div class="flex items-center gap-3 p-2.5 rounded-xl border border-slate-100 shadow-sm bg-white hover:border-red-200 transition-colors">
                        <div class="flex flex-col items-center gap-0.5 bg-slate-50 rounded-lg p-0.5 border border-slate-100">
                            <button @click="updateQty(item.id, 1, item.maxStock)" class="w-6 h-6 flex items-center justify-center bg-white rounded shadow-sm text-red-600 hover:bg-red-50"><i class="fas fa-plus text-[10px]"></i></button>
                            <span class="font-bold text-xs py-0.5 w-6 text-center select-none" x-text="item.qty"></span>
                            <button @click="updateQty(item.id, -1)" class="w-6 h-6 flex items-center justify-center bg-white rounded shadow-sm text-slate-500 hover:text-red-500"><i class="fas fa-minus text-[10px]"></i></button>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="font-bold text-slate-800 text-sm truncate leading-tight" x-text="item.name"></div>
                            <div class="text-[10px] text-slate-400 mt-0.5 flex justify-between">
                                <span>@ <span x-text="formatCurrency(item.price)"></span></span>
                                <span class="text-red-600 font-bold" x-text="formatCurrency(item.price * item.qty)"></span>
                            </div>
                        </div>
                        <button @click="removeFromCart(item.id)" class="p-2 text-slate-300 hover:text-red-500"><i class="fas fa-times"></i></button>
                    </div>
                </template>
            </div>

            <div class="p-4 bg-slate-50 border-t border-slate-200 z-20 shrink-0">
                <div x-data="{ showPromo: false }" class="mb-3">
                    <div class="flex justify-between items-center cursor-pointer" @click="showPromo = !showPromo">
                        <span class="text-xs font-bold text-slate-500 flex items-center gap-1"><i class="fas fa-ticket-alt"></i> Kode Promo</span>
                        <i class="fas fa-chevron-up text-xs text-slate-400 transition-transform" :class="showPromo ? 'rotate-180' : ''"></i>
                    </div>
                    <div x-show="showPromo" x-transition class="mt-2 flex gap-2">
                        <input type="text" x-model="couponCode" placeholder="Kode..." class="flex-1 px-3 py-1.5 bg-white border border-slate-300 rounded text-sm focus:border-red-500">
                        <button class="bg-slate-800 text-white px-3 py-1.5 rounded text-xs font-bold">Cek</button>
                    </div>
                </div>

                <div class="flex justify-between items-end mb-3 pb-3 border-b border-slate-200 border-dashed">
                    <span class="text-sm font-bold text-slate-500">Total Tagihan</span>
                    <span class="text-2xl font-black text-slate-800 tracking-tight" x-text="formatCurrency(subtotal)"></span>
                </div>

                <button @click="checkout()" :disabled="cart.length === 0 || isProcessing" 
                        class="w-full bg-red-600 text-white py-3.5 rounded-xl font-bold text-base shadow-lg shadow-red-200 active:scale-[0.98] transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2 group">
                    <span x-show="!isProcessing" class="flex items-center gap-2">
                        <span>Bayar Sekarang</span> <i class="fas fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                    </span>
                    <span x-show="isProcessing" class="flex items-center gap-2">
                        <i class="fas fa-spinner fa-spin"></i> <span>Memproses...</span>
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
                couponCode: '',
                uploadedFiles: [],
                isProcessing: false,

                // --- LOGIKA FILE UPLOAD ---
                handleFileUpload(event) {
                    const newFiles = event.target.files;
                    if (newFiles.length === 0) return;

                    for (let i = 0; i < newFiles.length; i++) {
                        const file = newFiles[i];
                        // Cek Duplikat
                        const isDuplicate = this.uploadedFiles.some(f => f.name === file.name && f.size === file.size);
                        // Cek Ukuran (10MB)
                        if (file.size > 10 * 1024 * 1024) { alert(`File "${file.name}" terlalu besar (Max 10MB)`); continue; }
                        
                        if (!isDuplicate) this.uploadedFiles.push(file);
                    }
                    event.target.value = ''; // Reset input agar bisa pilih file yg sama
                },
                removeFile(index) { this.uploadedFiles.splice(index, 1); },
                formatFileSize(bytes) {
                    if (bytes === 0) return '0 B';
                    const k = 1024;
                    const sizes = ['B', 'KB', 'MB', 'GB'];
                    const i = Math.floor(Math.log(bytes) / Math.log(k));
                    return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
                },

                // --- LOGIKA CART ---
                getItemQty(id) { let item = this.cart.find(i => i.id === id); return item ? item.qty : 0; },
                get cartTotalQty() { return this.cart.reduce((sum, item) => sum + item.qty, 0); },
                itemMatchesSearch(name) { return name.toLowerCase().includes(this.search.toLowerCase()); },
                
                addToCart(id, name, price, maxStock) {
                    if (maxStock <= 0) { alert('Stok produk ini habis!'); return; }
                    if (navigator.vibrate) navigator.vibrate(50);
                    let found = this.cart.find(i => i.id === id);
                    if (found) { 
                        if (found.qty < maxStock) found.qty++; 
                        else alert('Stok tidak mencukupi!'); 
                    } else { 
                        this.cart.push({ id, name, price, qty: 1, maxStock: maxStock }); 
                    }
                },
                updateQty(id, amount, maxStock = 9999) {
                    let item = this.cart.find(i => i.id === id);
                    if (item) {
                        if (amount > 0 && item.qty >= item.maxStock) { alert('Stok maksimal tercapai'); return; }
                        item.qty += amount;
                        if (item.qty <= 0) this.removeFromCart(id);
                    }
                },
                removeFromCart(id) { this.cart = this.cart.filter(i => i.id !== id); },
                confirmClearCart() { if(confirm('Kosongkan keranjang & file?')) { this.cart = []; this.uploadedFiles = []; } },
                get subtotal() { return this.cart.reduce((sum, item) => sum + (item.price * item.qty), 0); },
                formatCurrency(val) { return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(val); },

                // --- CHECKOUT ---
                async checkout() {
                    if (this.cart.length === 0) return;
                    this.isProcessing = true;

                    let formData = new FormData();
                    formData.append('items', JSON.stringify(this.cart));
                    formData.append('total', this.subtotal);
                    formData.append('coupon', this.couponCode);
                    
                    this.uploadedFiles.forEach(file => formData.append('attachments[]', file));

                    try {
                        const response = await fetch("{{ route('orders.store') }}", {
                            method: "POST",
                            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') },
                            body: formData
                        });
                        
                        const result = await response.json();

                        if (result.status === 'success') {
                            alert(`✅ Pesanan Berhasil! \nNo Invoice: ${result.invoice}`);
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