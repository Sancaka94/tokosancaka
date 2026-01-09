{{-- ========================================================= --}}
            {{-- GROUP SIDEBAR KANAN (AKTIVITAS & MONITOR) --}}
            {{-- ========================================================= --}}
            
            {{-- 1. SIDEBAR AKTIVITAS (POSISI ATAS - BIRU) --}}
            {{-- 1. SIDEBAR AKTIVITAS (Z-Index Teratur) --}}
            <div x-data="{ activityOpen: false }" 
                class="fixed inset-y-0 right-0 z-[100] flex items-center justify-end h-screen pointer-events-none">
                
                {{-- Tombol Trigger --}}
                <div @mouseenter="activityOpen = true; monitorOpen = false"
                    class="fixed right-0 top-[33%] transform -translate-y-1/2 pointer-events-auto bg-blue-600 text-white py-4 px-1 rounded-l-xl shadow-lg cursor-pointer transition-all duration-300 hover:bg-blue-700 hover:pr-3 z-[110]">
                    <div class="flex flex-col items-center gap-2">
                        <i class="fas fa-history animate-pulse text-[10px]"></i>
                        <span class="text-[10px] font-bold writing-vertical tracking-widest" style="writing-mode: vertical-rl; text-orientation: mixed;">AKTIVITAS</span>
                    </div>
                </div>

                {{-- Panel Isi --}}
                <div class="h-full bg-white/95 backdrop-blur-md shadow-2xl border-l border-gray-200 w-96 transform transition-transform duration-300 ease-in-out overflow-y-auto custom-scrollbar relative pointer-events-auto z-[100]"
                    :class="activityOpen ? 'translate-x-0' : 'translate-x-full'"
                    @mouseleave="activityOpen = false">
                    
                    {{-- Header & Isi tetap sama seperti kode Anda --}}
                    <div class="h-15 px-6 bg-blue-900 text-white flex justify-between items-center sticky top-0 z-10 shadow-md border-b border-blue-800">
                        <h3 class="font-bold text-sm tracking-wider uppercase flex items-center gap-2">
                            <i class="fas fa-history text-blue-300"></i> Aktivitas Terbaru
                        </h3>
                        <button @click="activityOpen = false" class="text-blue-200 hover:text-white transition focus:outline-none">
                            <i class="fas fa-times text-lg"></i>
                        </button>
                    </div>

                    {{-- List Aktivitas --}}
                    <div class="p-4 space-y-4 pb-20">
                        @forelse ($pesananTerbaru as $pesanan)
                        <div class="flex items-start py-3 border-b border-gray-100 last:border-0 hover:bg-blue-50/50 transition-colors rounded-lg px-2 group">
                            
                            {{-- LOGO EKSPEDISI --}}
                            <div class="mt-1 flex-shrink-0">
                                @php
                                    $parts = explode('-', $pesanan->expedition);
                                    $kodeEks = isset($parts[1]) ? strtolower($parts[1]) : 'default';
                                    $logoPath = "public/storage/logo-ekspedisi/{$kodeEks}.png";
                                    $fullPath = asset($logoPath);
                                @endphp
                                <div class="w-10 h-10 rounded-full bg-white border border-gray-100 flex items-center justify-center overflow-hidden shadow-sm group-hover:shadow-md transition-shadow">
                                    <img src="{{ $fullPath }}" 
                                         alt="{{ $kodeEks }}" 
                                         class="w-8 h-8 object-contain"
                                         onerror="this.onerror=null; this.src='https://tokosancaka.com/storage/uploads/sancaka.png';">
                                </div>
                            </div>

                            <div class="ml-3 flex-1 min-w-0">
                                <div class="flex justify-between items-start">
                                    <div class="flex flex-col">
                                        <p class="text-xs font-bold text-gray-800 tracking-tight flex items-center gap-1">
                                            Resi: {{ $pesanan->resi ?? $pesanan->nomor_invoice }}
                                            <button class="text-gray-400 hover:text-blue-600 transition" title="Salin" onclick="navigator.clipboard.writeText('{{ $pesanan->resi ?? $pesanan->nomor_invoice }}')">
                                                <i class="far fa-copy text-[10px]"></i>
                                            </button>
                                        </p>
                                        @if($pesanan->resi)
                                        <a href="https://tokosancaka.com/tracking?resi={{ $pesanan->resi }}" target="_blank" class="mt-0.5 inline-flex items-center text-[9px] font-bold text-red-600 hover:text-red-800 uppercase">
                                            <i class="fas fa-search-location mr-1"></i> Lacak
                                        </a>
                                        @endif
                                    </div>
                                    <span class="text-[10px] font-bold text-emerald-600 bg-emerald-50 px-1.5 py-0.5 rounded ml-2 whitespace-nowrap">
                                        Rp {{ number_format($pesanan->shipping_cost, 0, ',', '.') }}
                                    </span>
                                </div>

                                <div class="mt-1.5 space-y-0.5">
                                    <div class="flex items-center text-[10px] text-green-700 font-bold truncate">
                                        <i class="fas fa-store w-3 mr-0.5 opacity-60"></i>
                                        <span class="truncate">{{ $pesanan->pembeli->store_name ?? 'Tanpa Nama Toko' }}</span>
                                    </div>
                                    <div class="flex items-center text-[10px] text-blue-600 truncate">
                                        <i class="fas fa-user w-3 mr-0.5 opacity-60"></i>
                                        <span>{{ $pesanan->pembeli->nama_lengkap ?? 'User Tidak Dikenal' }}</span>
                                    </div>
                                    <div class="flex items-center text-[9px] text-gray-400 mt-1">
                                        <i class="far fa-clock w-3 mr-0.5"></i>
                                        <span>{{ $pesanan->created_at->diffForHumans() }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @empty
                            <div class="flex flex-col items-center justify-center py-10 text-gray-400">
                                <i class="fas fa-box-open text-3xl mb-2 opacity-50"></i>
                                <p class="text-xs">Belum ada aktivitas.</p>
                            </div>
                        @endforelse

                        <div class="pt-4 text-center">
                            <a href="#" class="text-[10px] font-bold uppercase text-blue-500 hover:text-blue-700 hover:underline">Lihat Semua Aktivitas</a>
                        </div>
                    </div>
                </div>
            </div>


            {{-- 2. SIDEBAR MONITOR (POSISI BAWAH - MERAH) --}}
            {{-- 2. SIDEBAR MONITOR (Z-Index Teratur) --}}
            <div x-data="{ monitorOpen: false }" 
                class="fixed inset-y-0 right-0 z-[100] flex items-center justify-end h-screen pointer-events-none">
                
                {{-- Tombol Trigger --}}
                <div @mouseenter="monitorOpen = true; activityOpen = false"
                    class="fixed right-0 top-1/2 transform -translate-y-1/2 pointer-events-auto bg-red-600 text-white py-4 px-1 rounded-l-xl shadow-lg cursor-pointer transition-all duration-300 hover:bg-red-700 hover:pr-3 z-[110]">
                    <div class="flex flex-col items-center gap-2">
                        <i class="fas fa-desktop animate-pulse text-[10px]"></i>
                        <span class="text-[10px] font-bold writing-vertical tracking-widest" style="writing-mode: vertical-rl; text-orientation: mixed;">MONITOR</span>
                    </div>
                </div>

                {{-- Panel Isi --}}
                <div class="h-full bg-white/95 backdrop-blur-md shadow-2xl border-l border-gray-200 w-80 transform transition-transform duration-300 ease-in-out overflow-y-auto custom-scrollbar relative pointer-events-auto z-[100]"
                    :class="monitorOpen ? 'translate-x-0' : 'translate-x-full'"
                    @mouseleave="monitorOpen = false">
                    
                    {{-- Header & Isi tetap sama seperti kode Anda --}}
                    <div class="h-15 px-6 bg-red-900 text-white flex justify-between items-center sticky top-0 z-10 shadow-md border-b border-gray-700">
                        <h3 class="font-bold text-sm tracking-wider uppercase flex items-center gap-2">
                            <i class="fas fa-chart-line text-red-500 animate-pulse"></i> Live Monitor
                        </h3>
                        <button @click="monitorOpen = false" class="text-gray-400 hover:text-white transition focus:outline-none">
                            <i class="fas fa-times text-lg"></i>
                        </button>
                    </div>

                    {{-- Konten Kartu Monitor (KODE LAMA ANDA, TETAP SAMA) --}}
                    <div class="p-4 space-y-4 pb-20">
                        {{-- GROUP 1: STATISTIK UTAMA (PUTIH) --}}
                        <div class="space-y-3">
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest ml-1">Keuangan & User</p>
                            
                            {{-- 1. Pendapatan --}}
                            <div class="relative group bg-white border border-gray-200 p-4 rounded-xl shadow-sm hover:shadow-md transition-all">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="bg-emerald-100 p-2 rounded-lg text-emerald-600"><i class="fas fa-coins"></i></div>
                                    <span class="text-[10px] font-bold text-gray-400 uppercase">Pendapatan</span>
                                </div>
                                <h3 class="text-xl font-extrabold text-gray-800 leading-none">
                                    Rp {{ number_format($totalPendapatan ?? 0, 0, ',', '.') }}
                                </h3>
                                <div class="mt-2 h-1 w-full bg-gray-100 rounded-full overflow-hidden">
                                    <div class="h-full bg-emerald-500 w-full animate-pulse"></div>
                                </div>
                            </div>

                            {{-- 2. Total Pesanan --}}
                            <div class="relative group bg-white border border-gray-200 p-4 rounded-xl shadow-sm hover:shadow-md transition-all">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="bg-blue-100 p-2 rounded-lg text-blue-600"><i class="fas fa-box-open"></i></div>
                                    <span class="text-[10px] font-bold text-gray-400 uppercase">Total Pesanan</span>
                                </div>
                                <h3 class="text-xl font-extrabold text-gray-800 leading-none">
                                    {{ number_format($totalPesanan ?? 0, 0, ',', '.') }}
                                </h3>
                                <div class="mt-2 h-1 w-full bg-gray-100 rounded-full overflow-hidden">
                                    <div class="h-full bg-blue-500 w-[80%]"></div>
                                </div>
                            </div>

                            {{-- 3. Jumlah Toko --}}
                            <div class="relative group bg-white border border-gray-200 p-4 rounded-xl shadow-sm hover:shadow-md transition-all">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="bg-purple-100 p-2 rounded-lg text-purple-600"><i class="fas fa-store-alt"></i></div>
                                    <span class="text-[10px] font-bold text-gray-400 uppercase">Jumlah Toko</span>
                                </div>
                                <h3 class="text-xl font-extrabold text-gray-800 leading-none">
                                    {{ number_format($jumlahToko ?? 0, 0, ',', '.') }}
                                </h3>
                                <div class="mt-2 h-1 w-full bg-gray-100 rounded-full overflow-hidden">
                                    <div class="h-full bg-purple-500 w-[60%]"></div>
                                </div>
                            </div>

                            {{-- 4. Pengguna Baru --}}
                            <div class="relative group bg-white border border-gray-200 p-4 rounded-xl shadow-sm hover:shadow-md transition-all">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="bg-orange-100 p-2 rounded-lg text-orange-600"><i class="fas fa-user-check"></i></div>
                                    <span class="text-[10px] font-bold text-gray-400 uppercase">User Baru</span>
                                </div>
                                <h3 class="text-xl font-extrabold text-gray-800 leading-none">
                                    {{ number_format($penggunaBaru ?? 0, 0, ',', '.') }}
                                </h3>
                                <div class="mt-2 h-1 w-full bg-gray-100 rounded-full overflow-hidden">
                                    <div class="h-full bg-orange-500 w-[40%]"></div>
                                </div>
                            </div>
                        </div>

                        {{-- GROUP 2: STATUS PENGIRIMAN (BERWARNA) --}}
                        <div class="space-y-3 pt-2">
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest ml-1">Status Paket</p>

                            {{-- 5. Total Terkirim (Hijau) --}}
                            <div class="relative overflow-hidden bg-gradient-to-br from-emerald-500 to-teal-700 p-4 rounded-xl shadow-md text-white group hover:scale-[1.02] transition-all duration-300">
                                <div class="relative z-10 flex justify-between items-start">
                                    <div>
                                        <p class="text-[10px] font-bold uppercase opacity-80 mb-1">Total Terkirim</p>
                                        <h3 class="text-2xl font-black">{{ number_format($totalTerkirim ?? 0) }}</h3>
                                        <div class="flex items-center mt-2 text-[10px] font-bold bg-white/20 px-2 py-0.5 rounded w-fit">
                                            <i class="fas fa-check-double mr-1"></i> Selesai
                                        </div>
                                    </div>
                                    <div class="p-2 bg-white/20 rounded-lg">
                                        <i class="fas fa-shipping-fast text-xl"></i>
                                    </div>
                                </div>
                                <div class="absolute -right-2 -bottom-2 text-white/10 text-6xl rotate-12 group-hover:scale-110 transition-transform">
                                    <i class="fas fa-box-check"></i>
                                </div>
                            </div>

                            {{-- 6. Sedang Dikirim (Biru) --}}
                            <div class="relative overflow-hidden bg-gradient-to-br from-blue-500 to-blue-700 p-4 rounded-xl shadow-md text-white group hover:scale-[1.02] transition-all duration-300">
                                <div class="relative z-10 flex justify-between items-start">
                                    <div>
                                        <p class="text-[10px] font-bold uppercase opacity-80 mb-1">Sedang Dikirim</p>
                                        <h3 class="text-2xl font-black">{{ number_format($totalSedangDikirim ?? 0) }}</h3>
                                        <div class="flex items-center mt-2 text-[10px] font-bold bg-white/20 px-2 py-0.5 rounded w-fit">
                                            <i class="fas fa-road mr-1"></i> On Process
                                        </div>
                                    </div>
                                    <div class="p-2 bg-white/20 rounded-lg">
                                        <i class="fas fa-truck-moving text-xl"></i>
                                    </div>
                                </div>
                                <div class="absolute -right-2 -bottom-2 text-white/10 text-6xl rotate-12 group-hover:scale-110 transition-transform">
                                    <i class="fas fa-route"></i>
                                </div>
                            </div>

                            {{-- 7. Menunggu Pickup (Orange) --}}
                            <div class="relative overflow-hidden bg-gradient-to-br from-orange-400 to-orange-600 p-4 rounded-xl shadow-md text-white group hover:scale-[1.02] transition-all duration-300">
                                <div class="relative z-10 flex justify-between items-start">
                                    <div>
                                        <p class="text-[10px] font-bold uppercase opacity-80 mb-1">Menunggu Pickup</p>
                                        <h3 class="text-2xl font-black">{{ number_format($totalMenungguPickup ?? 0) }}</h3>
                                        <div class="flex items-center mt-2 text-[10px] font-bold bg-white/20 px-2 py-0.5 rounded w-fit">
                                            <i class="fas fa-user-clock mr-1"></i> Siap Jemput
                                        </div>
                                    </div>
                                    <div class="p-2 bg-white/20 rounded-lg">
                                        <i class="fas fa-user-ninja text-xl"></i>
                                    </div>
                                </div>
                                <div class="absolute -right-2 -bottom-2 text-white/10 text-6xl rotate-12 group-hover:scale-110 transition-transform">
                                    <i class="fas fa-hand-holding-box"></i>
                                </div>
                            </div>

                            {{-- 8. Gagal / Cancel (Merah) --}}
                            <div class="relative overflow-hidden bg-gradient-to-br from-rose-500 to-red-800 p-4 rounded-xl shadow-md text-white group hover:scale-[1.02] transition-all duration-300">
                                <div class="relative z-10 flex justify-between items-start">
                                    <div>
                                        <p class="text-[10px] font-bold uppercase opacity-80 mb-1">Gagal / Cancel</p>
                                        <h3 class="text-2xl font-black">{{ number_format($totalGagal ?? 0) }}</h3>
                                        <div class="flex items-center mt-2 text-[10px] font-bold bg-white/20 px-2 py-0.5 rounded w-fit">
                                            <i class="fas fa-exclamation-triangle mr-1"></i> Bermasalah
                                        </div>
                                    </div>
                                    <div class="p-2 bg-white/20 rounded-lg">
                                        <i class="fas fa-ban text-xl"></i>
                                    </div>
                                </div>
                                <div class="absolute -right-2 -bottom-2 text-white/10 text-6xl rotate-12 group-hover:scale-110 transition-transform">
                                    <i class="fas fa-times-circle"></i>
                                </div>
                            </div>
                        </div>

                        {{-- Status Realtime --}}
                        <div class="mt-6 pt-4 border-t border-gray-100 text-center">
                            <div class="flex justify-center gap-2 mt-2 items-center">
                                <span class="relative flex h-3 w-3">
                                  <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                                  <span class="relative inline-flex rounded-full h-3 w-3 bg-green-500"></span>
                                </span>
                                <span class="text-[10px] font-bold text-green-600">Live Connection</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            {{-- ========================================================= --}}
            {{-- SELESAI: GROUP SIDEBAR KANAN --}}
            {{-- ========================================================= --}}