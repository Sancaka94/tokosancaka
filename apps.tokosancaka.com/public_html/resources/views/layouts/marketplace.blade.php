<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Toko Online') - {{ $tenant->name ?? 'SancakaPOS' }}</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;900&display=swap" rel="stylesheet">

    <style>
        body { font-family: 'Inter', sans-serif; }
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-gray-50 text-gray-800 flex flex-col min-h-screen"
      x-data="marketplace()"
      x-init="initCart()">

    <div x-show="toast.show" x-transition x-cloak class="fixed top-24 right-5 bg-emerald-500 text-white px-6 py-3 rounded-xl shadow-2xl flex items-center gap-3 z-[100] border-2 border-emerald-400">
        <i data-lucide="check-circle" class="w-5 h-5 text-white"></i>
        <span class="font-bold tracking-wide" x-text="toast.message"></span>
    </div>

    @include('storefront.partials.header')

    <main class="flex-grow">
        @yield('content')
    </main>

    @include('storefront.partials.footer')

    <script>
        // Inisialisasi Icon
        lucide.createIcons();

        // Logic AlpineJS Global untuk Keranjang
        function marketplace() {
            return {
                tenantId: '{{ $tenant->id ?? 1 }}',
                cart: [],
                toast: { show: false, message: '' },

                initCart() {
                    let saved = localStorage.getItem('sancaka_cart_' + this.tenantId);
                    if (saved) { this.cart = JSON.parse(saved); }
                },

                get cartCount() {
                    return this.cart.reduce((sum, item) => sum + item.qty, 0);
                },

                get cartTotal() {
                    return this.cart.reduce((sum, item) => sum + (item.qty * item.price), 0);
                },

                addToCart(product) {
                    let existing = this.cart.find(i => i.id === product.id);
                    if (existing) {
                        existing.qty += 1;
                    } else {
                        // Format menyesuaikan kebutuhan OrderController Backend Anda
                        this.cart.push({
                            id: product.id,
                            name: product.name,
                            price: product.sell_price,
                            qty: 1,
                            image: product.image
                        });
                    }
                    this.saveCart();
                    this.showToast(product.name + ' masuk keranjang!');
                },

                updateQty(id, amount) {
                    let existing = this.cart.find(i => i.id === id);
                    if (existing) {
                        existing.qty += amount;
                        if (existing.qty <= 0) {
                            this.cart = this.cart.filter(i => i.id !== id);
                        }
                        this.saveCart();
                    }
                },

                emptyCart() {
                    this.cart = [];
                    this.saveCart();
                },

                saveCart() {
                    localStorage.setItem('sancaka_cart_' + this.tenantId, JSON.stringify(this.cart));
                },

                showToast(msg) {
                    this.toast.message = msg;
                    this.toast.show = true;
                    setTimeout(() => { this.toast.show = false; }, 3000);
                },

                formatRupiah(angka) {
                    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(angka);
                }
            }
        }
    </script>
    @stack('scripts')
</body>
</html>
