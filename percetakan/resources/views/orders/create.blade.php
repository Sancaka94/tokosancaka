<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>POS Percetakan - Toko Sancaka</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        [x-cloak] { display: none !important; }
        
        /* Hide scrollbar for Chrome, Safari and Opera */
        .no-scrollbar::-webkit-scrollbar { display: none; }
        /* Hide scrollbar for IE, Edge and Firefox */
        .no-scrollbar { -ms-overflow-style: none;  scrollbar-width: none; }
        
        /* Smooth scrolling for touch devices */
        .touch-scroll { -webkit-overflow-scrolling: touch; }
    </style>
</head>
<body class="bg-slate-100 font-sans text-slate-800 h-screen overflow-hidden select-none" x-data="posSystem()">

    <div class="flex h-full flex-col lg:flex-row">
        
        <div class="flex-1 flex flex-col h-full relative overflow-hidden">
            
            <div class="p-4 bg-white shadow-sm z-20 flex flex-col sm:flex-row gap-3 items-center justify-between shrink-0">
                <div class="w-full sm:w-auto">
                    <h1 class="text-xl font-bold text-slate-800 flex items-center gap-2">
                        <i class="fas fa-print text-indigo-600"></i>
                        <span>Sancaka POS</span>
                    </h1>
                </div>
                
                <div class="relative w-full sm:max-w-md">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400"><i class="fas fa-search"></i></span>
                    <input type="text" x-model="search" placeholder="Cari layanan atau produk..." 
                           class="w-full pl-10 pr-4 py-3 rounded-xl bg-slate-100 border-none focus:ring-2 focus:ring-indigo-500 transition-all text-sm font-medium">
                    <button x-show="search.length > 0" @click="search = ''" class="absolute inset-y-0 right-0 pr-3 text-slate-400 hover:text-slate-600">
                        <i class="fas fa-times-circle"></i>
                    </button>
                </div>

                <button @click="mobileCartOpen = !mobileCartOpen" class="lg:hidden relative p-3 bg-indigo-50 rounded-xl text-indigo-700">
                    <i class="fas fa-shopping-cart text-lg"></i>
                    <span x-show="cartTotalQty > 0" class="absolute -top-1 -right-1 bg-red-500 text-white text-[10px] font-bold h-5 w-5 flex items-center justify-center rounded-full border-2 border-white" x-text="cartTotalQty"></span>
                </button>
            </div>

            <div class="flex-1 overflow-y-auto p-4 touch-scroll pb-24 lg:pb-4">
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-5 gap-3 sm:gap-4">
                    @foreach($products as $product)
                    <template x-if="itemMatchesSearch('{{ $product->name }}')">
                        <div @click="addToCart({{ $product->id }}, '{{ $product->name }}', {{ $product->sell_price }}, {{ $product->stock }})"
                             class="relative bg-white rounded-2xl p-3 sm:p-4 shadow-sm border border-slate-100 transition-all flex flex-col h-full group
                             {{ $product->stock <= 0 ? 'opacity-60 cursor-not-allowed grayscale' : 'cursor-pointer active:scale-95 active:border-indigo-500 hover:shadow-md' }}">
                            
                            <div class="absolute top-2 left-2 z-10">
                                @if($product->stock <= 0)
                                    <span class="bg-red-500 text-white text-[10px] font-bold px-2 py-0.5 rounded-full shadow-sm">Habis</span>
                                @elseif($product->stock <= 5)
                                    <span class="bg-amber-500 text-white text-[10px] font-bold px-2 py-0.5 rounded-full shadow-sm">Sisa {{ $product->stock }}</span>
                                @else
                                    <span class="bg-emerald-100 text-emerald-700 text-[10px] font-bold px-2 py-0.5 rounded-full border border-emerald-200 shadow-sm">Stok: {{ $product->stock }}</span>
                                @endif
                            </div>

                            <div x-show="getItemQty({{ $product->id }}) > 0" 
                                 class="absolute top-2 right-2 bg-indigo-600 text-white text-xs font-bold h-6 w-6 rounded-full flex items-center justify-center shadow-md z-10"
                                 x-text="getItemQty({{ $product->id }})">
                            </div>

                            <div class="aspect-square bg-indigo-50 rounded-xl flex items-center justify-center mb-3 text-3xl sm:text-4xl mt-4">
                                📦
                            </div>

                            <div class="flex-1 flex flex-col">
                                <h3 class="font-bold text-slate-800 text-sm leading-tight mb-1 line-clamp-2">{{ $product->name }}</h3>
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wide mb-auto">{{ $product->unit }}</p>
                                
                                <p class="text-indigo-600 font-black text-sm sm:text-base mt-2">
                                    Rp {{ number_format($product->sell_price, 0, ',', '.') }}
                                </p>
                            </div>
                        </div>
                    </template>
                    @endforeach
                </div>
            </div>
        </div>

        <div x-show="mobileCartOpen" class="fixed inset-0 bg-black/50 z-30 lg:hidden backdrop-blur-sm" @click="mobileCartOpen = false" x-transition.opacity></div>

        <div class="fixed inset-y-0 right-0 w-[85%] sm:w-[400px] lg:static lg:w-[380px] bg-white shadow-2xl lg:shadow-none border-l border-slate-200 z-40 transform transition-transform duration-300 ease-in-out flex flex-col h-full"
             :class="mobileCartOpen ? 'translate-x-0' : 'translate-x-full lg:translate-x-0'">
            
            <div class="p-4 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                <div class="flex items-center gap-2">
                    <h2 class="font-bold text-lg text-slate-800">Detail Pesanan</h2>
                    <span class="bg-indigo-100 text-indigo-700 text-[10px] font-bold px-2 py-0.5 rounded">#{{ date('Hi') }}</span>
                </div>
                <button @click="mobileCartOpen = false" class="lg:hidden p-2 text-slate-400 hover:text-slate-600"><i class="fas fa-times text-xl"></i></button>
                <button x-show="cart.length > 0" @click="confirmClearCart()" class="hidden lg:block text-xs font-bold text-red-500 hover:text-red-700"><i class="fas fa-trash-alt mr-1"></i> Reset</button>
            </div>

            <div class="flex-1 overflow-y-auto p-4 space-y-3 bg-white touch-scroll">
                <template x-if="cart.length === 0">
                    <div class="h-full flex flex-col items-center justify-center text-slate-300 space-y-4">
                        <i class="fas fa-shopping-basket text-6xl"></i>
                        <p class="font-medium text-sm">Keranjang masih kosong</p>
                    </div>
                </template>

                <template x-for="item in cart" :key="item.id">
                    <div class="flex items-center gap-3 p-3 rounded-xl bg-white border border-slate-100 shadow-sm">
                        <div class="flex flex-col items-center gap-1 bg-slate-50 rounded-lg p-1">
                            <button @click="updateQty(item.id, 1, item.maxStock)" class="w-8 h-8 flex items-center justify-center bg-white border border-slate-200 rounded-md text-indigo-600 shadow-sm active:scale-90 transition-transform">
                                <i class="fas fa-plus text-xs"></i>
                            </button>
                            <span class="font-bold text-sm py-1 w-8 text-center" x-text="item.qty"></span>
                            <button @click="updateQty(item.id, -1)" class="w-8 h-8 flex items-center justify-center bg-white border border-slate-200 rounded-md text-slate-500 shadow-sm active:scale-90 transition-transform">
                                <i class="fas fa-minus text-xs"></i>
                            </button>
                        </div>

                        <div class="flex-1 min-w-0">
                            <div class="font-bold text-slate-800 text-sm truncate" x-text="item.name"></div>
                            <div class="text-[10px] text-slate-400 mt-0.5">Sisa Stok: <span x-text="item.maxStock - item.qty"></span></div>
                            <div class="font-bold text-indigo-600 text-sm mt-1" x-text="formatCurrency(item.price * item.qty)"></div>
                        </div>

                        <button @click="removeFromCart(item.id)" class="p-2 text-slate-300 hover:text-red-500 transition-colors"><i class="fas fa-trash-alt"></i></button>
                    </div>
                </template>
            </div>

            <div class="p-4 bg-white border-t border-slate-100 shadow-[0_-5px_15px_rgba(0,0,0,0.05)] z-20">
                <div x-data="{ showPromo: false }" class="mb-4">
                    <button @click="showPromo = !showPromo" class="text-xs font-bold text-indigo-600 flex items-center gap-1 mb-2"><i class="fas fa-tag"></i> <span>Punya kode promo?</span></button>
                    <div x-show="showPromo" class="flex gap-2" x-transition><input type="text" x-model="couponCode" placeholder="Masukkan kode..." class="flex-1 px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm"><button class="bg-slate-800 text-white px-3 py-2 rounded-lg text-xs font-bold">Gunakan</button></div>
                </div>

                <div class="flex justify-between items-end mb-4">
                    <div>
                        <p class="text-xs text-slate-500 font-bold uppercase tracking-wider">Total Pembayaran</p>
                        <p class="text-xs text-slate-400 mt-0.5" x-text="cartTotalQty + ' Item'"></p>
                    </div>
                    <div class="text-2xl font-black text-slate-800" x-text="formatCurrency(subtotal)"></div>
                </div>

                <button @click="checkout()" :disabled="cart.length === 0" class="w-full bg-indigo-600 text-white py-4 rounded-xl font-bold text-lg shadow-lg shadow-indigo-200 active:scale-[0.98] transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-3">
                    <i class="fas fa-cash-register"></i><span>Bayar Sekarang</span>
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
                
                getItemQty(id) {
                    let item = this.cart.find(i => i.id === id);
                    return item ? item.qty : 0;
                },

                get cartTotalQty() {
                    return this.cart.reduce((sum, item) => sum + item.qty, 0);
                },

                itemMatchesSearch(name) {
                    return name.toLowerCase().includes(this.search.toLowerCase());
                },

                // UPDATE: Menambahkan parameter maxStock
                addToCart(id, name, price, maxStock) {
                    if (maxStock <= 0) {
                        alert('Stok habis!');
                        return;
                    }

                    if (navigator.vibrate) navigator.vibrate(50);

                    let found = this.cart.find(i => i.id === id);
                    if (found) {
                        if (found.qty < maxStock) {
                            found.qty++;
                        } else {
                            alert('Stok tidak mencukupi!');
                        }
                    } else {
                        // Simpan maxStock ke dalam item cart agar bisa dicek nanti
                        this.cart.push({ id, name, price, qty: 1, maxStock: maxStock });
                    }
                },

                // UPDATE: Cek stok saat tombol plus ditekan di keranjang
                updateQty(id, amount, maxStock = 9999) {
                    if (navigator.vibrate) navigator.vibrate(30);
                    
                    let item = this.cart.find(i => i.id === id);
                    if (item) {
                        // Cek jika user ingin menambah tapi stok sudah mentok
                        if (amount > 0 && item.qty >= item.maxStock) {
                            alert('Maksimal stok tersedia hanya ' + item.maxStock);
                            return;
                        }

                        item.qty += amount;
                        if (item.qty <= 0) this.removeFromCart(id);
                    }
                },

                removeFromCart(id) {
                    this.cart = this.cart.filter(i => i.id !== id);
                },

                confirmClearCart() {
                    if(confirm('Kosongkan keranjang belanja?')) {
                        this.cart = [];
                    }
                },

                get subtotal() {
                    return this.cart.reduce((sum, item) => sum + (item.price * item.qty), 0);
                },

                formatCurrency(val) {
                    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(val);
                },

                checkout() {
                    if (this.cart.length === 0) return;
                    
                    const orderPayload = {
                        items: this.cart,
                        total: this.subtotal,
                        coupon: this.couponCode
                    };

                    console.log('Checkout Payload:', orderPayload);
                    alert('Pesanan berhasil dibuat! Total: ' + this.formatCurrency(this.subtotal));
                    this.cart = [];
                    this.mobileCartOpen = false;
                }
            }
        }
    </script>
</body>
</html>