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

    <div class="flex h-full w-full flex-col lg:flex-row overflow-hidden">
        
        <div class="flex-1 flex flex-col h-full relative border-r border-slate-200">
            
            <div class="h-16 px-4 bg-white shadow-sm z-20 flex items-center justify-between shrink-0 border-b border-slate-100">
                <h1 class="text-xl font-bold text-slate-800 flex items-center gap-2">
                    <i class="fas fa-print text-indigo-600"></i>
                    <span class="hidden sm:inline">Sancaka POS</span>
                </h1>
                
                <div class="relative w-full max-w-md mx-4">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400"><i class="fas fa-search"></i></span>
                    <input type="text" x-model="search" placeholder="Cari layanan..." 
                           class="w-full pl-10 pr-4 py-2 rounded-lg bg-slate-100 border-none focus:ring-2 focus:ring-indigo-500 text-sm">
                    <button x-show="search.length > 0" @click="search = ''" class="absolute inset-y-0 right-0 pr-3 text-slate-400 hover:text-slate-600">
                        <i class="fas fa-times-circle"></i>
                    </button>
                </div>

                <button @click="mobileCartOpen = !mobileCartOpen" class="lg:hidden relative p-2 bg-indigo-50 rounded-lg text-indigo-700">
                    <i class="fas fa-shopping-cart"></i>
                    <span x-show="cartTotalQty > 0" class="absolute -top-1 -right-1 bg-red-500 text-white text-[10px] font-bold h-4 w-4 flex items-center justify-center rounded-full" x-text="cartTotalQty"></span>
                </button>
            </div>

            <div class="flex-1 overflow-y-auto p-4 touch-scroll bg-slate-50">
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-5 gap-3">
                    @foreach($products as $product)
                    <template x-if="itemMatchesSearch('{{ $product->name }}')">
                        <div @click="addToCart({{ $product->id }}, '{{ $product->name }}', {{ $product->sell_price }}, {{ $product->stock }})"
                             class="relative bg-white rounded-xl p-3 shadow-sm border border-slate-100 flex flex-col h-full group
                             {{ $product->stock <= 0 ? 'opacity-60 grayscale cursor-not-allowed' : 'cursor-pointer active:scale-95 hover:border-indigo-400 hover:shadow-md' }} transition-all">
                            
                            <div class="absolute top-2 left-2 z-10">
                                @if($product->stock <= 0) <span class="bg-red-500 text-white text-[10px] font-bold px-1.5 py-0.5 rounded">Habis</span>
                                @elseif($product->stock <= 5) <span class="bg-amber-500 text-white text-[10px] font-bold px-1.5 py-0.5 rounded">Sisa {{ $product->stock }}</span>
                                @endif
                            </div>

                            <div x-show="getItemQty({{ $product->id }}) > 0" 
                                 class="absolute top-2 right-2 bg-indigo-600 text-white text-xs font-bold h-6 w-6 rounded-full flex items-center justify-center shadow-md z-10"
                                 x-text="getItemQty({{ $product->id }})">
                            </div>

                            <div class="aspect-square bg-indigo-50 rounded-lg flex items-center justify-center mb-2 text-3xl mt-4">📦</div>

                            <div class="flex-1 flex flex-col">
                                <h3 class="font-bold text-slate-700 text-sm leading-tight mb-1 line-clamp-2">{{ $product->name }}</h3>
                                <p class="text-[10px] font-bold text-slate-400 uppercase mb-auto">{{ $product->unit }}</p>
                                <p class="text-indigo-600 font-bold text-sm mt-2">Rp {{ number_format($product->sell_price, 0, ',', '.') }}</p>
                            </div>
                        </div>
                    </template>
                    @endforeach
                </div>
            </div>
        </div>

        <div x-show="mobileCartOpen" class="fixed inset-0 bg-black/50 z-30 lg:hidden backdrop-blur-sm" @click="mobileCartOpen = false" x-transition.opacity></div>

        <div class="fixed inset-y-0 right-0 w-[85%] sm:w-[400px] lg:static lg:w-[380px] bg-white shadow-xl lg:shadow-none z-40 transform transition-transform duration-300 ease-in-out flex flex-col h-full"
             :class="mobileCartOpen ? 'translate-x-0' : 'translate-x-full lg:translate-x-0'">
            
            <div class="h-16 px-4 border-b border-slate-100 flex justify-between items-center bg-white shrink-0">
                <div class="flex items-center gap-2">
                    <h2 class="font-bold text-lg text-slate-800">Pesanan</h2>
                    <span class="bg-indigo-50 text-indigo-600 text-[10px] font-bold px-2 py-0.5 rounded border border-indigo-100">#{{ date('Hi') }}</span>
                </div>
                <div class="flex items-center gap-2">
                    <button x-show="cart.length > 0" @click="confirmClearCart()" class="hidden lg:flex items-center gap-1 text-xs font-bold text-red-500 hover:text-red-700 bg-red-50 px-2 py-1 rounded">
                        <i class="fas fa-trash-alt"></i> Reset
                    </button>
                    <button @click="mobileCartOpen = false" class="lg:hidden p-2 text-slate-400"><i class="fas fa-times text-xl"></i></button>
                </div>
            </div>

            <div class="flex-1 overflow-y-auto p-3 space-y-2 bg-white touch-scroll">
                <template x-if="cart.length === 0">
                    <div class="h-full flex flex-col items-center justify-center text-slate-300 space-y-3 opacity-60">
                        <i class="fas fa-cash-register text-5xl"></i>
                        <p class="font-medium text-sm">Siap melayani pesanan</p>
                    </div>
                </template>

                <template x-for="item in cart" :key="item.id">
                    <div class="flex items-center gap-3 p-2.5 rounded-xl border border-slate-100 shadow-sm bg-white hover:border-indigo-200 transition-colors">
                        <div class="flex flex-col items-center gap-0.5 bg-slate-50 rounded-lg p-0.5 border border-slate-100">
                            <button @click="updateQty(item.id, 1, item.maxStock)" class="w-6 h-6 flex items-center justify-center bg-white rounded shadow-sm text-indigo-600 hover:bg-indigo-50">
                                <i class="fas fa-plus text-[10px]"></i>
                            </button>
                            <span class="font-bold text-xs py-0.5 w-6 text-center select-none" x-text="item.qty"></span>
                            <button @click="updateQty(item.id, -1)" class="w-6 h-6 flex items-center justify-center bg-white rounded shadow-sm text-slate-500 hover:text-red-500">
                                <i class="fas fa-minus text-[10px]"></i>
                            </button>
                        </div>

                        <div class="flex-1 min-w-0">
                            <div class="font-bold text-slate-800 text-sm truncate leading-tight" x-text="item.name"></div>
                            <div class="text-[10px] text-slate-400 mt-0.5 flex justify-between">
                                <span>@ <span x-text="formatCurrency(item.price)"></span></span>
                                <span class="text-indigo-600 font-bold" x-text="formatCurrency(item.price * item.qty)"></span>
                            </div>
                        </div>

                        <button @click="removeFromCart(item.id)" class="p-2 text-slate-300 hover:text-red-500 transition-colors">
                            <i class="fas fa-times"></i>
                        </button>
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
                        <input type="text" x-model="couponCode" placeholder="Kode..." class="flex-1 px-3 py-1.5 bg-white border border-slate-300 rounded text-sm focus:border-indigo-500">
                        <button class="bg-slate-800 text-white px-3 py-1.5 rounded text-xs font-bold">Cek</button>
                    </div>
                </div>

                <div class="flex justify-between items-end mb-3 pb-3 border-b border-slate-200 border-dashed">
                    <span class="text-sm font-bold text-slate-500">Total Tagihan</span>
                    <span class="text-2xl font-black text-slate-800 tracking-tight" x-text="formatCurrency(subtotal)"></span>
                </div>

                <button @click="checkout()" :disabled="cart.length === 0" 
                        class="w-full bg-indigo-600 text-white py-3.5 rounded-xl font-bold text-base shadow-lg shadow-indigo-200 active:scale-[0.98] transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2 group">
                    <span>Bayar</span>
                    <i class="fas fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
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
                getItemQty(id) { let item = this.cart.find(i => i.id === id); return item ? item.qty : 0; },
                get cartTotalQty() { return this.cart.reduce((sum, item) => sum + item.qty, 0); },
                itemMatchesSearch(name) { return name.toLowerCase().includes(this.search.toLowerCase()); },
                addToCart(id, name, price, maxStock) {
                    if (maxStock <= 0) { alert('Stok habis!'); return; }
                    if (navigator.vibrate) navigator.vibrate(50);
                    let found = this.cart.find(i => i.id === id);
                    if (found) { if (found.qty < maxStock) found.qty++; else alert('Stok tidak mencukupi!'); }
                    else { this.cart.push({ id, name, price, qty: 1, maxStock: maxStock }); }
                },
                updateQty(id, amount, maxStock = 9999) {
                    if (navigator.vibrate) navigator.vibrate(30);
                    let item = this.cart.find(i => i.id === id);
                    if (item) {
                        if (amount > 0 && item.qty >= item.maxStock) { alert('Maksimal stok tersedia hanya ' + item.maxStock); return; }
                        item.qty += amount;
                        if (item.qty <= 0) this.removeFromCart(id);
                    }
                },
                removeFromCart(id) { this.cart = this.cart.filter(i => i.id !== id); },
                confirmClearCart() { if(confirm('Kosongkan keranjang?')) this.cart = []; },
                get subtotal() { return this.cart.reduce((sum, item) => sum + (item.price * item.qty), 0); },
                formatCurrency(val) { return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(val); },
                checkout() {
                    if (this.cart.length === 0) return;
                    const orderPayload = { items: this.cart, total: this.subtotal, coupon: this.couponCode };
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