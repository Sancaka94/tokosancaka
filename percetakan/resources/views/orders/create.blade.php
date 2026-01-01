<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POS Percetakan - Toko Sancaka</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <style>
        [x-cloak] { display: none !important; }
        ::-webkit-scrollbar { width: 5px; }
        ::-webkit-scrollbar-thumb { background: #888; border-radius: 10px; }
    </style>
</head>
<body class="bg-gray-100 font-sans">

    <div x-data="posSystem()" x-cloak class="flex flex-col lg:flex-row h-screen overflow-hidden">
        
        <div class="w-full lg:w-2/3 p-4 lg:p-6 overflow-y-auto">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
                <div>
                    <h2 class="text-2xl font-extrabold text-gray-800">Layanan Percetakan</h2>
                    <p class="text-sm text-gray-500">Pilih layanan untuk menambah ke pesanan</p>
                </div>
                <div class="relative w-full md:w-64">
                    <input type="text" x-model="search" placeholder="Cari produk..." 
                           class="w-full rounded-xl border-none shadow-sm focus:ring-2 focus:ring-indigo-500 py-3 px-4">
                </div>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-4">
                @foreach($products as $product)
                <template x-if="itemMatchesSearch('{{ $product->name }}')">
                    <div @click="addToCart({{ $product->id }}, '{{ $product->name }}', {{ $product->base_price }})"
                         class="bg-white p-4 rounded-2xl shadow-sm hover:shadow-xl transition-all cursor-pointer border-2 border-transparent hover:border-indigo-500 group relative overflow-hidden">
                        
                        <div class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity">
                            <span class="bg-indigo-600 text-white p-1 rounded-full text-xs">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4" />
                                </svg>
                            </span>
                        </div>

                        <div class="w-full h-24 bg-indigo-50 rounded-xl mb-3 flex items-center justify-center group-hover:bg-indigo-100 transition-colors">
                            <span class="text-3xl">📦</span>
                        </div>

                        <h3 class="font-bold text-gray-800 group-hover:text-indigo-600 truncate text-sm lg:text-base">{{ $product->name }}</h3>
                        <p class="text-[10px] text-gray-400 font-semibold mb-2 uppercase tracking-wider">{{ $product->unit }}</p>
                        
                        <div class="text-indigo-600 font-bold text-sm">
                            Rp {{ number_format($product->base_price, 0, ',', '.') }}
                        </div>
                    </div>
                </template>
                @endforeach
            </div>
        </div>

        <div class="w-full lg:w-1/3 bg-white border-l border-gray-200 flex flex-col h-full shadow-2xl">
            <div class="p-6 border-b flex justify-between items-center bg-white sticky top-0 z-10">
                <div>
                    <h2 class="text-xl font-bold text-gray-800">Detail Pesanan</h2>
                    <span class="text-xs bg-indigo-100 text-indigo-700 px-2 py-1 rounded-full font-bold">INV-{{ date('YmdHi') }}</span>
                </div>
                <button @click="cart = []" class="text-red-500 hover:text-red-700 text-xs font-semibold">Kosongkan</button>
            </div>

            <div class="flex-grow overflow-y-auto p-4 space-y-3 bg-gray-50/50">
                <template x-for="item in cart" :key="item.id">
                    <div class="flex flex-col bg-white p-3 rounded-xl shadow-sm border border-gray-100">
                        <div class="flex justify-between mb-2">
                            <span class="font-bold text-gray-700 text-sm" x-text="item.name"></span>
                            <button @click="removeFromCart(item.id)" class="text-gray-300 hover:text-red-500">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M6 18L18 6M6 6l12 12" stroke-width="2"/></svg>
                            </button>
                        </div>
                        <div class="flex justify-between items-center">
                            <div class="flex items-center gap-2">
                                <button @click="updateQty(item.id, -1)" class="w-7 h-7 flex items-center justify-center bg-gray-100 rounded-lg hover:bg-gray-200">-</button>
                                <span class="w-8 text-center font-bold text-sm" x-text="item.qty"></span>
                                <button @click="updateQty(item.id, 1)" class="w-7 h-7 flex items-center justify-center bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">+</button>
                            </div>
                            <span class="font-bold text-gray-800 text-sm" x-text="formatCurrency(item.price * item.qty)"></span>
                        </div>
                    </div>
                </template>

                <div x-show="cart.length === 0" class="flex flex-col items-center justify-center h-full opacity-30 py-20">
                    <span class="text-6xl mb-4">🛒</span>
                    <p class="font-bold">Belum ada item dipilih</p>
                </div>
            </div>

            <div class="p-6 bg-white border-t border-gray-200 space-y-4">
                <div class="space-y-2">
                    <div class="flex gap-2">
                        <input type="text" x-model="couponCode" placeholder="Kode Kupon" class="flex-1 rounded-xl border-gray-200 text-sm focus:ring-indigo-500">
                        <button class="bg-gray-800 text-white px-4 py-2 rounded-xl text-xs font-bold hover:bg-black transition-colors">Klaim</button>
                    </div>
                    <div class="flex gap-2">
                        <input type="text" x-model="referralCode" placeholder="Kode Referral" class="flex-1 rounded-xl border-gray-200 text-sm focus:ring-indigo-500">
                        <button class="bg-indigo-100 text-indigo-700 px-4 py-2 rounded-xl text-xs font-bold hover:bg-indigo-200 transition-colors">Cek</button>
                    </div>
                </div>

                <div class="space-y-2 pt-2">
                    <div class="flex justify-between text-gray-500 text-sm">
                        <span>Subtotal</span>
                        <span x-text="formatCurrency(subtotal)"></span>
                    </div>
                    <div class="flex justify-between text-xl font-black text-gray-900 pt-2 border-t">
                        <span>Total Bayar</span>
                        <span class="text-indigo-600" x-text="formatCurrency(subtotal)"></span>
                    </div>
                </div>

                <button @click="checkout()" 
                        :disabled="cart.length === 0"
                        class="w-full bg-indigo-600 text-white py-4 rounded-2xl font-bold text-lg shadow-lg shadow-indigo-100 hover:bg-indigo-700 transition-all active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed">
                    Buat Pesanan Sekarang
                </button>
            </div>
        </div>
    </div>

    <script>
        function posSystem() {
            return {
                search: '',
                cart: [],
                couponCode: '',
                referralCode: '',
                
                itemMatchesSearch(name) {
                    return name.toLowerCase().includes(this.search.toLowerCase());
                },

                addToCart(id, name, price) {
                    let found = this.cart.find(i => i.id === id);
                    if (found) {
                        found.qty++;
                    } else {
                        this.cart.push({ id, name, price, qty: 1 });
                    }
                },

                updateQty(id, amount) {
                    let item = this.cart.find(i => i.id === id);
                    if (item) {
                        item.qty += amount;
                        if (item.qty <= 0) this.removeFromCart(id);
                    }
                },

                removeFromCart(id) {
                    this.cart = this.cart.filter(i => i.id !== id);
                },

                get subtotal() {
                    return this.cart.reduce((sum, item) => sum + (item.price * item.qty), 0);
                },

                formatCurrency(val) {
                    return 'Rp ' + new Intl.NumberFormat('id-ID').format(val);
                },

                checkout() {
                    alert('Data pesanan siap dikirim ke database!');
                    console.log('Order Data:', {
                        items: this.cart,
                        coupon: this.couponCode,
                        referral: this.referralCode,
                        total: this.subtotal
                    });
                }
            }
        }
    </script>
</body>
</html>