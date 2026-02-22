<footer class="bg-gray-900 text-white py-10 mt-12 border-t border-gray-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 grid grid-cols-1 md:grid-cols-3 gap-8">
        <div>
            <h3 class="text-xl font-bold mb-4">{{ $tenant->name ?? 'Toko Anda' }}</h3>
            <p class="text-gray-400 text-sm mb-4">Belanja aman, nyaman, dan terpercaya langsung dari tangan pertama.</p>
            <div class="flex items-center gap-2 text-gray-400 text-sm">
                <i data-lucide="map-pin" class="w-4 h-4"></i> {{ $tenant->address ?? 'Indonesia' }}
            </div>
        </div>
        <div>
            <h4 class="font-bold text-gray-300 mb-4 uppercase tracking-wider text-sm">Bantuan</h4>
            <ul class="space-y-2 text-sm text-gray-400">
                <li><a href="#" class="hover:text-white transition">Cara Belanja</a></li>
                <li><a href="#" class="hover:text-white transition">Konfirmasi Pembayaran</a></li>
                <li><a href="#" class="hover:text-white transition">Lacak Pesanan</a></li>
            </ul>
        </div>
        <div>
            <h4 class="font-bold text-gray-300 mb-4 uppercase tracking-wider text-sm">Pembayaran & Pengiriman</h4>
            <div class="flex gap-2 flex-wrap mb-4">
                <span class="px-2 py-1 bg-gray-800 rounded text-xs">DANA</span>
                <span class="px-2 py-1 bg-gray-800 rounded text-xs">QRIS</span>
                <span class="px-2 py-1 bg-gray-800 rounded text-xs">Transfer Bank</span>
            </div>
            <div class="flex gap-2 flex-wrap">
                <span class="px-2 py-1 bg-gray-800 rounded text-xs">JNE</span>
                <span class="px-2 py-1 bg-gray-800 rounded text-xs">J&T</span>
                <span class="px-2 py-1 bg-gray-800 rounded text-xs">GoSend</span>
            </div>
        </div>
    </div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-10 pt-6 border-t border-gray-800 text-center text-xs text-gray-500">
        &copy; {{ date('Y') }} {{ $tenant->name ?? 'Toko Anda' }}. Powered by <a href="https://tokosancaka.com" class="text-blue-400 hover:underline">SancakaPOS SaaS</a>.
    </div>
</footer>
