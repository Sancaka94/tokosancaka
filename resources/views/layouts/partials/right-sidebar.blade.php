            {{-- ========================================================= --}}
            {{-- MULAI: SIDEBAR KANAN (MONITOR 8 CARD) --}}
            {{-- ========================================================= --}}
            <div x-data="{ monitorOpen: false }" 
                 class="fixed inset-y-0 right-0 z-[70] flex items-center justify-end h-screen pointer-events-none">
                 
                {{-- A. Tombol Trigger (Muncul di kanan) --}}
                <div @mouseenter="monitorOpen = true"
                     class="absolute right-0 top-1/2 transform -translate-y-1/2 pointer-events-auto bg-red-600 text-white py-4 px-1 rounded-l-xl shadow-lg cursor-pointer transition-all duration-300 hover:bg-red-700 hover:pr-3 z-[71]"
                     :class="monitorOpen ? 'translate-x-full opacity-0' : 'translate-x-0 opacity-100'">
                    <div class="flex flex-col items-center gap-2">
                        <i class="fas fa-chevron-left animate-pulse text-[10px]"></i>
                        <span class="text-[10px] font-bold writing-vertical tracking-widest" style="writing-mode: vertical-rl; text-orientation: mixed;">MONITOR</span>
                    </div>
                </div>

                {{-- B. Panel Sidebar Kanan --}}
                <div class="h-full bg-white/95 backdrop-blur-md shadow-2xl border-l border-gray-200 w-80 transform transition-transform duration-300 ease-in-out overflow-y-auto custom-scrollbar relative pointer-events-auto"
                     :class="monitorOpen ? 'translate-x-0' : 'translate-x-full'"
                     @mouseleave="monitorOpen = false">
                    
                    {{-- Header Panel --}}
                    <div class="h-15 px-6 bg-red-700 text-white flex justify-between items-center sticky top-0 z-10 shadow-md border-b border-gray-700">
                        <h3 class="font-bold text-sm tracking-wider uppercase"><i class="fas fa-desktop mr-2"></i>Live Monitor</h3>
                        <button @click="monitorOpen = false" class="text-gray-400 hover:text-white transition">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    {{-- KONTEN KARTU --}}
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
                                {{-- Background Icon --}}
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
            {{-- SELESAI: SIDEBAR KANAN --}}
            {{-- ========================================================= --}}