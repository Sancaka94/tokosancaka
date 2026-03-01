<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Toko Online') - {{ $tenant->name ?? 'SancakaPOS' }}</title>

    {{-- KODE FAVICON DI SINI --}}
    @if($tokoAdmin && !empty($tokoAdmin->logo))
        {{-- Jika toko memiliki logo --}}
        <link rel="icon" type="image/png" href="{{ asset('storage/' . $tokoAdmin->logo) }}">
    @else
        {{-- Fallback: Gunakan favicon default jika toko tidak memiliki logo --}}
        <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    @endif

    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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

                    // KUNCI UTAMA: Ekspos fungsi addToCart ke Window Global agar bisa dipanggil dari detail produk
                    window.addToCart = (productData) => {
                        this.addToCart(productData);
                    };
                },

                get cartCount() {
                    return this.cart.reduce((sum, item) => sum + item.qty, 0);
                },

                get cartTotal() {
                    return this.cart.reduce((sum, item) => sum + (item.qty * item.price), 0);
                },

                addToCart(product) {
                    // Gunakan unique_id bawaan dari produk jika ada, jika tidak buat baru
                    let uniqueId = product.unique_id || product.id;
                    if (!product.unique_id) {
                        if (product.variant_id) uniqueId += '_' + product.variant_id;
                        if (product.sub_variant_id) uniqueId += '_' + product.sub_variant_id;
                    }

                    let existing = this.cart.find(i => i.unique_id === uniqueId);

                    if (existing) {
                        // Tambahkan qty berdasarkan qty yang dikirim (default 1)
                        existing.qty += (product.qty || 1);
                    } else {
                        this.cart.push({
                            unique_id: uniqueId,
                            id: product.id,
                            variant_id: product.variant_id || null,
                            sub_variant_id: product.sub_variant_id || null,
                            name: product.name,

                            // PERBAIKAN UTAMA: Cek 'price' dulu, baru 'sell_price' sebagai cadangan
                            price: product.price !== undefined ? product.price : (product.sell_price || 0),

                            qty: product.qty || 1,
                            image: product.image,
                            weight: product.weight || 0,

                            // PERBAIKAN: Jangan lupakan data tambahan ini agar fitur keranjang berjalan mulus!
                            is_free_ongkir: product.is_free_ongkir || 0,
                            is_cashback_extra: product.is_cashback_extra || 0
                        });
                    }

                    this.saveCart();
                    this.showToast(product.name + ' masuk keranjang!');
                },

                updateQty(uniqueId, amount) {
                    let existing = this.cart.find(i => i.unique_id === uniqueId);
                    if (existing) {
                        existing.qty += amount;
                        if (existing.qty <= 0) {
                            this.cart = this.cart.filter(i => i.unique_id !== uniqueId);
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
