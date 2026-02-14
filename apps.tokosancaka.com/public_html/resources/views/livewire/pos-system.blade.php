<div class="flex h-screen w-full flex-col lg:flex-row overflow-hidden bg-slate-100 font-sans text-slate-800">

    {{-- BAGIAN KIRI (PRODUK & SEARCH) --}}
    <div class="flex-1 flex flex-col h-full relative border-r border-slate-200">

        {{-- TOP BAR --}}
        <div class="h-16 px-3 bg-white shadow-sm z-30 flex items-center justify-between shrink-0 border-b border-slate-200 gap-3">
            <div class="flex items-center gap-2 shrink-0">
                <div class="h-9 w-9 bg-red-600 rounded-lg flex items-center justify-center text-white text-lg shadow-lg">
                    <i class="fas fa-cash-register"></i>
                </div>
                <div class="hidden md:block">
                    <h1 class="text-lg font-black text-slate-800 tracking-tight">Sancaka<span class="text-red-600">POS</span></h1>
                    <div class="text-[10px] text-slate-400 font-mono -mt-1">{{ $currentTenant->subdomain }}.tokosancaka.com</div>
                </div>
            </div>

            {{-- SEARCH BAR (Realtime Livewire) --}}
            <div class="flex-1 max-w-xl">
                <div class="relative group">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-search text-slate-400 group-focus-within:text-red-500 transition-colors"></i>
                    </span>
                    {{-- Debounce 300ms agar server tidak berat --}}
                    <input type="text" wire:model.live.debounce.300ms="search"
                           class="block w-full pl-10 pr-3 py-2.5 bg-slate-100 border-2 border-transparent focus:bg-white focus:border-red-500 focus:ring-0 rounded-xl text-sm font-medium transition-all"
                           placeholder="Cari item (Scan / Ketik)..." autofocus>

                    {{-- Loading Icon saat searching --}}
                    <div wire:loading wire:target="search" class="absolute inset-y-0 right-0 pr-3 flex items-center">
                        <i class="fas fa-circle-notch fa-spin text-red-500"></i>
                    </div>
                </div>
            </div>

            {{-- TOMBOL SCANNER (Trigger Modal JS) --}}
            <button onclick="startScannerJS()" class="h-10 w-10 bg-white border-2 border-slate-100 text-slate-600 hover:text-red-600 hover:bg-red-50 rounded-xl shadow-sm">
                <i class="fas fa-qrcode text-lg"></i>
            </button>

            {{-- SUPER ADMIN SWITCHER --}}
            @if($isSuperAdmin)
            <div x-data="{ open: false }" class="relative">
                <button @click="open = !open" class="h-10 w-10 bg-purple-50 text-purple-600 rounded-xl border border-purple-200 hover:bg-purple-600 hover:text-white transition">
                    <i class="fas fa-store"></i>
                </button>
                <div x-show="open" @click.outside="open = false" class="absolute right-0 mt-2 w-64 bg-white border rounded-xl shadow-xl z-50 p-2 max-h-80 overflow-y-auto" style="display: none;">
                    @foreach($allTenants as $t)
                        <button wire:click="switchTenant({{ $t->id }})" @click="open = false"
                                class="w-full text-left px-3 py-2 rounded-lg text-xs font-bold hover:bg-purple-50 {{ $targetTenantId == $t->id ? 'bg-purple-600 text-white' : 'text-slate-700' }}">
                            {{ $t->name }}
                        </button>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

        {{-- FILTER KATEGORI --}}
        <div class="bg-slate-50 px-4 py-2 border-b border-slate-200 shadow-sm sticky top-0 z-20">
            <div class="flex overflow-x-auto gap-2 pb-1 custom-scrollbar">
                <button wire:click="$set('activeCategory', 'all')"
                        class="px-4 py-2 rounded-xl text-xs font-bold border transition-all whitespace-nowrap {{ $activeCategory == 'all' ? 'bg-red-600 text-white border-red-600' : 'bg-white text-slate-600 border-slate-200 hover:text-red-600' }}">
                    Semua
                </button>
                @foreach($categories as $cat)
                <button wire:click="$set('activeCategory', '{{ $cat->slug }}')"
                        class="px-4 py-2 rounded-xl text-xs font-bold border transition-all whitespace-nowrap {{ $activeCategory == $cat->slug ? 'bg-red-600 text-white border-red-600' : 'bg-white text-slate-600 border-slate-200 hover:text-red-600' }}">
                    {{ $cat->name }}
                </button>
                @endforeach
            </div>
        </div>

        {{-- GRID PRODUK --}}
        <div class="flex-1 overflow-y-auto p-4 bg-slate-50 relative custom-scrollbar">

            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-3 xl:grid-cols-4 gap-3">
                @forelse($products as $product)
                <div wire:click="addToCart({{ $product->id }})"
                     class="relative bg-white rounded-2xl p-3 shadow-sm border border-slate-100 flex flex-col h-full cursor-pointer hover:border-red-300 hover:shadow-md transition-all group active:scale-95">

                    {{-- Label Stok --}}
                    <div class="absolute top-2 left-2 z-10">
                        @if($product->stock <= 0)
                            <span class="bg-slate-700 text-white text-[9px] px-2 py-0.5 rounded-md font-bold">HABIS</span>
                        @elseif($product->stock <= 5)
                            <span class="bg-amber-500 text-white text-[9px] px-2 py-0.5 rounded-md animate-pulse font-bold">Sisa {{ $product->stock }}</span>
                        @endif
                    </div>

                    {{-- Badge Qty di Cart --}}
                    @if(isset($cart[$product->id]))
                    <div class="absolute top-2 right-2 bg-green-600 text-white text-[10px] font-bold h-6 w-6 rounded-full flex items-center justify-center shadow-md z-10 ring-2 ring-white">
                        {{ $cart[$product->id]['qty'] }}
                    </div>
                    @endif

                    <div class="h-32 bg-slate-50 rounded-xl flex items-center justify-center mb-3 overflow-hidden p-2 group-hover:bg-red-50 transition">
                        @if($product->image)
                            <img src="{{ asset('storage/'.$product->image) }}" class="h-full object-contain">
                        @else
                            <i class="fas fa-box text-3xl text-slate-300"></i>
                        @endif
                    </div>

                    <div class="flex-1 flex flex-col">
                        <h3 class="font-bold text-slate-700 text-xs leading-tight mb-1 line-clamp-2 group-hover:text-red-600">{{ $product->name }}</h3>
                        <div class="mt-auto flex justify-between items-end">
                            <p class="text-xs font-black text-slate-800">Rp {{ number_format($product->sell_price, 0, ',', '.') }}</p>
                            <p class="text-[9px] text-slate-400 uppercase font-bold">{{ $product->unit }}</p>
                        </div>
                    </div>
                </div>
                @empty
                <div class="col-span-full flex flex-col items-center justify-center text-slate-400 mt-20">
                    <i class="fas fa-box-open text-5xl mb-3 opacity-20"></i>
                    <p class="text-sm font-medium">Produk tidak ditemukan.</p>
                </div>
                @endforelse
            </div>

            <div class="mt-4">
                {{ $products->links() }}
            </div>
        </div>
    </div>

    {{-- BAGIAN KANAN (KERANJANG) --}}
    <div class="w-full lg:w-[400px] bg-white border-l border-slate-200 flex flex-col h-full shadow-2xl lg:shadow-none z-40">
        <div class="h-16 px-5 border-b border-slate-100 flex justify-between items-center bg-green-50 shrink-0">
            <div>
                <span class="text-[10px] font-bold text-green-600 uppercase tracking-widest">Order Baru</span>
                <div class="font-black text-green-700 text-lg">#{{ date('ymd') }}-{{ rand(100,999) }}</div>
            </div>
            @if(count($cart) > 0)
                <button wire:click="$set('cart', [])" class="text-xs text-red-500 font-bold hover:underline"><i class="fas fa-trash-alt"></i> Reset</button>
            @endif
        </div>

        <div class="flex-1 overflow-y-auto p-4 space-y-3 custom-scrollbar">
            @forelse($cart as $productId => $item)
            <div class="flex items-start gap-3 p-3 bg-white border border-slate-100 rounded-xl shadow-sm group hover:border-red-100 transition-colors">

                {{-- Qty Controls --}}
                <div class="flex flex-col items-center bg-slate-50 rounded-lg border border-slate-200 w-8 shrink-0">
                    <button wire:click="updateQty({{ $productId }}, 1)" class="w-full h-7 flex items-center justify-center text-slate-500 hover:bg-green-500 hover:text-white rounded-t-lg transition border-b border-slate-200">
                        <i class="fas fa-plus text-[9px]"></i>
                    </button>
                    <span class="text-xs font-bold py-1 text-slate-700">{{ $item['qty'] }}</span>
                    <button wire:click="updateQty({{ $productId }}, -1)" class="w-full h-7 flex items-center justify-center text-slate-500 hover:bg-red-500 hover:text-white rounded-b-lg transition border-t border-slate-200">
                        <i class="fas fa-minus text-[9px]"></i>
                    </button>
                </div>

                {{-- Gambar Kecil --}}
                <div class="h-12 w-12 rounded-lg bg-slate-100 border border-slate-200 overflow-hidden shrink-0 flex items-center justify-center p-0.5">
                    @if($item['image'])
                        <img src="{{ asset('storage/'.$item['image']) }}" class="h-full w-full object-contain">
                    @else
                        <i class="fas fa-box text-slate-300"></i>
                    @endif
                </div>

                <div class="flex-1 min-w-0">
                    <div class="font-bold text-slate-700 text-xs truncate mb-1">{{ $item['name'] }}</div>
                    <div class="flex justify-between items-center">
                        <span class="text-[10px] text-slate-400 bg-slate-50 px-1.5 rounded border border-slate-100">@ {{ number_format($item['price'], 0, ',', '.') }}</span>
                        <span class="text-xs font-bold text-slate-800">Rp {{ number_format($item['price'] * $item['qty'], 0, ',', '.') }}</span>
                    </div>
                </div>

                <button wire:click="removeFromCart({{ $productId }})" class="text-slate-300 hover:text-red-500 p-1 opacity-0 group-hover:opacity-100 transition">
                    <i class="fas fa-trash-alt text-sm"></i>
                </button>
            </div>
            @empty
            <div class="flex flex-col items-center justify-center h-40 text-slate-300">
                <i class="fas fa-shopping-basket text-4xl mb-2 opacity-50"></i>
                <p class="text-xs font-bold">Keranjang Kosong</p>
            </div>
            @endforelse
        </div>

        {{-- Footer Keranjang --}}
        <div class="p-4 bg-slate-50 border-t border-slate-200 shrink-0">
            <div class="space-y-1 mb-4">
                <div class="flex justify-between text-xs text-slate-500">
                    <span>Subtotal</span>
                    <span>Rp {{ number_format($subtotal, 0, ',', '.') }}</span>
                </div>
                <div class="flex justify-between items-end pt-2 border-t border-slate-300">
                    <span class="text-sm font-bold text-slate-800">Total Tagihan</span>
                    <span class="text-2xl font-black text-slate-800 tracking-tight">Rp {{ number_format($grandTotal, 0, ',', '.') }}</span>
                </div>
            </div>

            <button class="w-full bg-red-600 text-white py-3 rounded-xl font-bold shadow-lg hover:bg-green-700 transition disabled:opacity-50 disabled:cursor-not-allowed group flex items-center justify-center gap-2"
                    {{ count($cart) == 0 ? 'disabled' : '' }}>
                <span>Bayar Sekarang</span>
                <i class="fas fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
            </button>
        </div>
    </div>

    {{-- MODAL SCANNER JS (Wajib pakai JS karena akses hardware/kamera) --}}
    <div id="scanner-modal" class="fixed inset-0 z-[100] bg-slate-900/90 backdrop-blur-sm hidden flex-col items-center justify-center p-4">
        <div class="w-full max-w-md bg-white rounded-3xl overflow-hidden border-4 border-slate-800 relative">
            <button onclick="stopScannerJS()" class="absolute top-4 right-4 z-10 bg-white/20 text-white rounded-full p-2 hover:bg-red-600 transition"><i class="fas fa-times"></i></button>
            <div id="reader" class="w-full bg-black min-h-[300px]"></div>
            <div class="bg-slate-900 p-4 text-center text-white text-xs">Arahkan kamera ke barcode</div>
        </div>
    </div>

    {{-- Script Penghubung JS -> Livewire --}}
    <script>
        document.addEventListener('livewire:initialized', () => {
            // Audio Effect
            Livewire.on('play-audio', (data) => {
                let audio = new Audio(data.type === 'success' ? 'https://tokosancaka.com/public/sound/beep.mp3' : 'https://tokosancaka.com/public/sound/beep-gagal.mp3');
                audio.play();
            });

            // SweetAlert Error
            Livewire.on('swal:error', (data) => {
                Swal.fire({ icon: 'error', title: 'Oops...', text: data.message, timer: 1500, showConfirmButton: false });
            });
        });

        // --- LOGIKA SCANNER JS ---
        let html5QrcodeScanner;

        function startScannerJS() {
            document.getElementById('scanner-modal').classList.remove('hidden');
            document.getElementById('scanner-modal').classList.add('flex');

            html5QrcodeScanner = new Html5Qrcode("reader");
            html5QrcodeScanner.start(
                { facingMode: "environment" },
                { fps: 10, qrbox: { width: 250, height: 250 } },
                (decodedText) => {
                    // SUKSES SCAN -> KIRIM KE LIVEWIRE
                    @this.call('handleScan', decodedText);
                    stopScannerJS();
                },
                (errorMessage) => { /* ignore errors */ }
            );
        }

        function stopScannerJS() {
            if(html5QrcodeScanner) {
                html5QrcodeScanner.stop().then(() => {
                    document.getElementById('scanner-modal').classList.add('hidden');
                    document.getElementById('scanner-modal').classList.remove('flex');
                });
            }
        }
    </script>
</div>
