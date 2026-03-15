{{-- ========================================================= --}}
{{-- GROUP SIDEBAR KANAN (AKTIVITAS & MONITOR) --}}
{{-- ========================================================= --}}

{{-- 1. SIDEBAR AKTIVITAS (Z-Index Teratur) --}}
<div x-data="{ activityOpen: false }"
    class="fixed inset-y-0 right-0 z-[100] flex items-center justify-end h-screen pointer-events-none">

    {{-- Tombol Trigger Aktivitas --}}
    <div @mouseenter="activityOpen = true; monitorOpen = false"
        class="fixed right-0 top-[30%] transform -translate-y-1/2 pointer-events-auto bg-blue-600/95 backdrop-blur-sm border border-r-0 border-white/20 text-white py-5 px-1.5 rounded-l-2xl shadow-xl cursor-pointer transition-all duration-300 hover:bg-blue-600 hover:pr-4 z-[110]">
        <div class="flex flex-col items-center gap-2">
            <i class="fas fa-history animate-pulse text-[11px]"></i>
            <span class="text-[10px] font-bold writing-vertical tracking-widest" style="writing-mode: vertical-rl; text-orientation: mixed;">AKTIVITAS</span>
        </div>
    </div>

    {{-- Panel Isi Aktivitas --}}
    <div class="h-full bg-white/95 backdrop-blur-xl shadow-2xl border-l border-gray-100 w-96 transform transition-transform duration-300 ease-in-out overflow-y-auto custom-scrollbar relative pointer-events-auto z-[100]"
        :class="activityOpen ? 'translate-x-0' : 'translate-x-full'"
        @mouseleave="activityOpen = false">

        {{-- Header Aktivitas --}}
        <div class="h-16 px-6 bg-gradient-to-r from-blue-700 to-blue-600 text-white flex justify-between items-center sticky top-0 z-10 shadow-md">
            <h3 class="font-bold text-sm tracking-wider uppercase flex items-center gap-2">
                <div class="p-1.5 bg-white/20 rounded-lg">
                    <i class="fas fa-history text-white"></i>
                </div>
                Aktivitas Terbaru
            </h3>
            <button @click="activityOpen = false" class="text-blue-100 hover:text-white hover:bg-white/20 p-1.5 rounded-lg transition focus:outline-none">
                <i class="fas fa-times text-lg"></i>
            </button>
        </div>

        {{-- List Aktivitas --}}
        <div class="p-4 space-y-3 pb-20">
            @forelse ($pesananTerbaru as $pesanan)
            <div class="flex items-start p-3 bg-white border border-gray-100 hover:border-blue-200 hover:shadow-md transition-all duration-200 rounded-xl group cursor-default">

                {{-- LOGO EKSPEDISI --}}
                <div class="mt-1 flex-shrink-0">
                    @php
                        $parts = explode('-', $pesanan->expedition);
                        $kodeEks = isset($parts[1]) ? strtolower($parts[1]) : 'default';
                        $logoPath = "public/storage/logo-ekspedisi/{$kodeEks}.png";
                        $fullPath = asset($logoPath);
                    @endphp
                    <div class="w-10 h-10 rounded-full bg-gray-50 border border-gray-200 flex items-center justify-center overflow-hidden shadow-sm group-hover:shadow transition-all">
                        <img src="{{ $fullPath }}"
                                alt="{{ $kodeEks }}"
                                class="w-7 h-7 object-contain"
                                onerror="this.onerror=null; this.src='https://tokosancaka.com/storage/uploads/sancaka.png';">
                    </div>
                </div>

                <div class="ml-3 flex-1 min-w-0">
                    <div class="flex justify-between items-start">
                        <div class="flex flex-col">
                            <p class="text-xs font-extrabold text-gray-800 tracking-tight flex items-center gap-1.5">
                                {{ $pesanan->resi ?? $pesanan->nomor_invoice }}
                                <button class="text-gray-400 hover:text-blue-600 hover:bg-blue-50 p-1 rounded transition-colors" title="Salin" onclick="navigator.clipboard.writeText('{{ $pesanan->resi ?? $pesanan->nomor_invoice }}')">
                                    <i class="far fa-copy text-[11px]"></i>
                                </button>
                            </p>
                            @if($pesanan->resi)
                            <a href="https://tokosancaka.com/tracking?resi={{ $pesanan->resi }}" target="_blank" class="mt-0.5 inline-flex items-center text-[10px] font-bold text-red-500 hover:text-red-700 uppercase transition-colors">
                                <i class="fas fa-search-location mr-1"></i> Lacak Paket
                            </a>
                            @endif
                        </div>
                        <span class="text-[10px] font-bold text-emerald-700 bg-emerald-100 ring-1 ring-inset ring-emerald-200 px-2 py-1 rounded-md ml-2 whitespace-nowrap shadow-sm">
                            Rp {{ number_format($pesanan->shipping_cost, 0, ',', '.') }}
                        </span>
                    </div>

                    <div class="mt-2.5 space-y-1 bg-gray-50/50 p-2 rounded-lg border border-gray-50">
                        <div class="flex items-center text-[10px] text-gray-700 font-medium truncate">
                            <i class="fas fa-store w-4 text-center text-emerald-600/70"></i>
                            <span class="truncate">{{ $pesanan->pembeli->store_name ?? 'Tanpa Nama Toko' }}</span>
                        </div>
                        <div class="flex items-center text-[10px] text-gray-700 font-medium truncate">
                            <i class="fas fa-user w-4 text-center text-blue-600/70"></i>
                            <span>{{ $pesanan->pembeli->nama_lengkap ?? 'User Tidak Dikenal' }}</span>
                        </div>
                        <div class="flex items-center text-[9px] text-gray-400 font-medium pt-0.5">
                            <i class="far fa-clock w-4 text-center"></i>
                            <span>{{ $pesanan->created_at->diffForHumans() }}</span>
                        </div>
                    </div>
                </div>
            </div>
            @empty
                <div class="flex flex-col items-center justify-center py-12 text-gray-400 bg-gray-50 rounded-2xl border border-dashed border-gray-200">
                    <i class="fas fa-box-open text-4xl mb-3 text-gray-300"></i>
                    <p class="text-xs font-medium">Belum ada aktivitas terbaru.</p>
                </div>
            @endforelse

            <div class="pt-2 text-center">
                <a href="#" class="inline-flex items-center gap-1.5 text-[10px] font-bold uppercase text-blue-600 hover:text-blue-800 bg-blue-50 hover:bg-blue-100 px-4 py-2 rounded-lg transition-colors">
                    Lihat Semua Aktivitas <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>
</div>


{{-- 2. SIDEBAR MONITOR (Z-Index Teratur) --}}
<div x-data="{ monitorOpen: false }"
    class="fixed inset-y-0 right-0 z-[100] flex items-center justify-end h-screen pointer-events-none">

    {{-- Tombol Trigger Monitor --}}
    <div @mouseenter="monitorOpen = true; activityOpen = false"
        class="fixed right-0 top-[50%] transform -translate-y-1/2 pointer-events-auto bg-red-600/95 backdrop-blur-sm border border-r-0 border-white/20 text-white py-5 px-1.5 rounded-l-2xl shadow-xl cursor-pointer transition-all duration-300 hover:bg-red-600 hover:pr-4 z-[110]">
        <div class="flex flex-col items-center gap-2">
            <i class="fas fa-desktop animate-pulse text-[11px]"></i>
            <span class="text-[10px] font-bold writing-vertical tracking-widest" style="writing-mode: vertical-rl; text-orientation: mixed;">MONITOR</span>
        </div>
    </div>

    {{-- Panel Isi Monitor --}}
    <div class="h-full bg-gray-50/95 backdrop-blur-xl shadow-2xl border-l border-gray-200 w-80 transform transition-transform duration-300 ease-in-out overflow-y-auto custom-scrollbar relative pointer-events-auto z-[100]"
        :class="monitorOpen ? 'translate-x-0' : 'translate-x-full'"
        @mouseleave="monitorOpen = false">

        {{-- Header Monitor --}}
        <div class="h-16 px-6 bg-gradient-to-r from-red-700 to-red-600 text-white flex justify-between items-center sticky top-0 z-10 shadow-md">
            <h3 class="font-bold text-sm tracking-wider uppercase flex items-center gap-2">
                <div class="p-1.5 bg-white/20 rounded-lg">
                    <i class="fas fa-chart-line text-white animate-pulse"></i>
                </div>
                Live Monitor
            </h3>
            <button @click="monitorOpen = false" class="text-red-100 hover:text-white hover:bg-white/20 p-1.5 rounded-lg transition focus:outline-none">
                <i class="fas fa-times text-lg"></i>
            </button>
        </div>

        {{-- Konten Kartu Monitor --}}
        <div class="p-4 space-y-5 pb-20">
            {{-- GROUP 1: STATISTIK UTAMA (PUTIH) --}}
            <div class="space-y-3">
                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest ml-1">Keuangan & User</p>

                {{-- 1. Pendapatan --}}
                <div class="relative group bg-white border border-gray-100 p-4 rounded-2xl shadow-sm hover:shadow-md transition-all">
                    <div class="flex items-center justify-between mb-3">
                        <div class="bg-emerald-50 text-emerald-600 ring-1 ring-inset ring-emerald-100 p-2 rounded-xl"><i class="fas fa-coins text-sm"></i></div>
                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wide">Pendapatan</span>
                    </div>
                    <h3 class="text-2xl font-black text-gray-800 leading-none">
                        <span class="text-sm font-bold text-gray-400 mr-0.5">Rp</span>{{ number_format($totalPendapatan ?? 0, 0, ',', '.') }}
                    </h3>
                    <div class="mt-3 h-1.5 w-full bg-gray-100 rounded-full overflow-hidden">
                        <div class="h-full bg-emerald-500 w-full animate-pulse rounded-full"></div>
                    </div>
                </div>

                {{-- 2. Total Pesanan --}}
                <div class="relative group bg-white border border-gray-100 p-4 rounded-2xl shadow-sm hover:shadow-md transition-all">
                    <div class="flex items-center justify-between mb-3">
                        <div class="bg-blue-50 text-blue-600 ring-1 ring-inset ring-blue-100 p-2 rounded-xl"><i class="fas fa-box-open text-sm"></i></div>
                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wide">Total Pesanan</span>
                    </div>
                    <h3 class="text-2xl font-black text-gray-800 leading-none">
                        {{ number_format($totalPesanan ?? 0, 0, ',', '.') }}
                    </h3>
                    <div class="mt-3 h-1.5 w-full bg-gray-100 rounded-full overflow-hidden">
                        <div class="h-full bg-blue-500 w-[80%] rounded-full"></div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    {{-- 3. Jumlah Toko --}}
                    <div class="relative group bg-white border border-gray-100 p-3.5 rounded-2xl shadow-sm hover:shadow-md transition-all">
                        <div class="flex items-center justify-between mb-2">
                            <div class="bg-purple-50 text-purple-600 ring-1 ring-inset ring-purple-100 p-1.5 rounded-lg"><i class="fas fa-store-alt text-xs"></i></div>
                        </div>
                        <span class="text-[9px] font-bold text-gray-400 uppercase tracking-wide block mb-1">Jml Toko</span>
                        <h3 class="text-xl font-black text-gray-800 leading-none">
                            {{ number_format($jumlahToko ?? 0, 0, ',', '.') }}
                        </h3>
                    </div>

                    {{-- 4. Pengguna Baru --}}
                    <div class="relative group bg-white border border-gray-100 p-3.5 rounded-2xl shadow-sm hover:shadow-md transition-all">
                        <div class="flex items-center justify-between mb-2">
                            <div class="bg-orange-50 text-orange-600 ring-1 ring-inset ring-orange-100 p-1.5 rounded-lg"><i class="fas fa-user-check text-xs"></i></div>
                        </div>
                        <span class="text-[9px] font-bold text-gray-400 uppercase tracking-wide block mb-1">User Baru</span>
                        <h3 class="text-xl font-black text-gray-800 leading-none">
                            {{ number_format($penggunaBaru ?? 0, 0, ',', '.') }}
                        </h3>
                    </div>
                </div>
            </div>

            {{-- GROUP 2: STATUS PENGIRIMAN (BERWARNA) --}}
            <div class="space-y-3 pt-2">
                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest ml-1">Status Paket</p>

                {{-- 5. Total Terkirim (Hijau) --}}
                <div class="relative overflow-hidden bg-gradient-to-br from-emerald-500 to-teal-600 p-4 rounded-2xl shadow-md text-white group hover:shadow-lg hover:-translate-y-0.5 transition-all duration-300 border border-emerald-400/30">
                    <div class="relative z-10 flex justify-between items-start">
                        <div>
                            <p class="text-[10px] font-bold uppercase text-emerald-50 mb-1 tracking-wide">Total Terkirim</p>
                            <h3 class="text-3xl font-black drop-shadow-sm">{{ number_format($totalTerkirim ?? 0) }}</h3>
                            <div class="flex items-center mt-2 text-[10px] font-bold bg-white/20 backdrop-blur-sm px-2.5 py-1 rounded-md w-fit shadow-sm border border-white/10">
                                <i class="fas fa-check-double mr-1.5"></i> Selesai
                            </div>
                        </div>
                        <div class="p-2 bg-white/20 backdrop-blur-sm rounded-xl shadow-sm border border-white/10">
                            <i class="fas fa-shipping-fast text-xl"></i>
                        </div>
                    </div>
                    <div class="absolute -right-3 -bottom-3 text-white/10 text-7xl rotate-12 group-hover:scale-110 group-hover:rotate-6 transition-all duration-500">
                        <i class="fas fa-box-check"></i>
                    </div>
                </div>

                {{-- 6. Sedang Dikirim (Biru) --}}
                <div class="relative overflow-hidden bg-gradient-to-br from-blue-500 to-indigo-600 p-4 rounded-2xl shadow-md text-white group hover:shadow-lg hover:-translate-y-0.5 transition-all duration-300 border border-blue-400/30">
                    <div class="relative z-10 flex justify-between items-start">
                        <div>
                            <p class="text-[10px] font-bold uppercase text-blue-50 mb-1 tracking-wide">Sedang Dikirim</p>
                            <h3 class="text-3xl font-black drop-shadow-sm">{{ number_format($totalSedangDikirim ?? 0) }}</h3>
                            <div class="flex items-center mt-2 text-[10px] font-bold bg-white/20 backdrop-blur-sm px-2.5 py-1 rounded-md w-fit shadow-sm border border-white/10">
                                <i class="fas fa-road mr-1.5"></i> On Process
                            </div>
                        </div>
                        <div class="p-2 bg-white/20 backdrop-blur-sm rounded-xl shadow-sm border border-white/10">
                            <i class="fas fa-truck-moving text-xl"></i>
                        </div>
                    </div>
                    <div class="absolute -right-3 -bottom-3 text-white/10 text-7xl rotate-12 group-hover:scale-110 group-hover:rotate-6 transition-all duration-500">
                        <i class="fas fa-route"></i>
                    </div>
                </div>

                {{-- 7. Menunggu Pickup (Orange) --}}
                <div class="relative overflow-hidden bg-gradient-to-br from-orange-400 to-orange-600 p-4 rounded-2xl shadow-md text-white group hover:shadow-lg hover:-translate-y-0.5 transition-all duration-300 border border-orange-300/30">
                    <div class="relative z-10 flex justify-between items-start">
                        <div>
                            <p class="text-[10px] font-bold uppercase text-orange-50 mb-1 tracking-wide">Menunggu Pickup</p>
                            <h3 class="text-3xl font-black drop-shadow-sm">{{ number_format($totalMenungguPickup ?? 0) }}</h3>
                            <div class="flex items-center mt-2 text-[10px] font-bold bg-white/20 backdrop-blur-sm px-2.5 py-1 rounded-md w-fit shadow-sm border border-white/10">
                                <i class="fas fa-user-clock mr-1.5"></i> Siap Jemput
                            </div>
                        </div>
                        <div class="p-2 bg-white/20 backdrop-blur-sm rounded-xl shadow-sm border border-white/10">
                            <i class="fas fa-user-ninja text-xl"></i>
                        </div>
                    </div>
                    <div class="absolute -right-3 -bottom-3 text-white/10 text-7xl rotate-12 group-hover:scale-110 group-hover:rotate-6 transition-all duration-500">
                        <i class="fas fa-hand-holding-box"></i>
                    </div>
                </div>

                {{-- 8. Gagal / Cancel (Merah) --}}
                <div class="relative overflow-hidden bg-gradient-to-br from-rose-500 to-rose-700 p-4 rounded-2xl shadow-md text-white group hover:shadow-lg hover:-translate-y-0.5 transition-all duration-300 border border-rose-400/30">
                    <div class="relative z-10 flex justify-between items-start">
                        <div>
                            <p class="text-[10px] font-bold uppercase text-rose-50 mb-1 tracking-wide">Gagal / Cancel</p>
                            <h3 class="text-3xl font-black drop-shadow-sm">{{ number_format($totalGagal ?? 0) }}</h3>
                            <div class="flex items-center mt-2 text-[10px] font-bold bg-white/20 backdrop-blur-sm px-2.5 py-1 rounded-md w-fit shadow-sm border border-white/10">
                                <i class="fas fa-exclamation-triangle mr-1.5"></i> Bermasalah
                            </div>
                        </div>
                        <div class="p-2 bg-white/20 backdrop-blur-sm rounded-xl shadow-sm border border-white/10">
                            <i class="fas fa-ban text-xl"></i>
                        </div>
                    </div>
                    <div class="absolute -right-3 -bottom-3 text-white/10 text-7xl rotate-12 group-hover:scale-110 group-hover:rotate-6 transition-all duration-500">
                        <i class="fas fa-times-circle"></i>
                    </div>
                </div>
            </div>

            {{-- Status Realtime --}}
            <div class="mt-6 pt-5 border-t border-gray-200 text-center">
                <div class="inline-flex justify-center gap-2 items-center bg-green-50 px-3 py-1.5 rounded-full border border-green-100 shadow-sm">
                    <span class="relative flex h-2.5 w-2.5">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-green-500"></span>
                    </span>
                    <span class="text-[10px] font-bold text-green-700 tracking-wide uppercase">Live Connection</span>
                </div>
            </div>

        </div>
    </div>
</div>

{{-- ========================================================= --}}
{{-- 3. TOMBOL TRIGGER QUICK COPAS SPX (BERDIRI SENDIRI DI LUAR) --}}
{{-- ========================================================= --}}
<div onclick="openSpxGlobalModal()"
    class="fixed right-0 top-[calc(70%+6px)] transform -translate-y-1/2 pointer-events-auto bg-orange-500/95 backdrop-blur-sm border border-r-0 border-white/20 text-white py-5 px-1.5 rounded-l-2xl shadow-xl cursor-pointer transition-all duration-300 hover:bg-orange-600 hover:pr-4 z-[110]">
    <div class="flex flex-col items-center gap-2">
        <i class="fas fa-clipboard-list animate-pulse text-[11px]"></i>
        <span class="text-[10px] font-bold writing-vertical tracking-widest" style="writing-mode: vertical-rl; text-orientation: mixed;">SPX Tracking</span>
    </div>
</div>

{{-- ========================================================= --}}
{{-- SELESAI: GROUP SIDEBAR KANAN --}}
{{-- ========================================================= --}}


{{-- ========================================================= --}}
{{-- MODAL & SCRIPT QUICK COPAS SPX (GLOBAL) --}}
{{-- ========================================================= --}}
<div id="spxGlobalModal" class="fixed inset-0 z-[9999] hidden bg-gray-900/70 backdrop-blur-sm overflow-y-auto h-full w-full transition-opacity duration-300 flex items-center justify-center" style="display: none;">
    <div class="relative mx-auto p-0 border-0 w-11/12 md:w-3/4 lg:w-2/3 shadow-2xl rounded-2xl bg-white overflow-hidden transform transition-all">

       {{-- Header Modal --}}
        <div class="p-6 bg-gradient-to-r from-gray-50 to-white border-b border-gray-200">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-black text-gray-800 flex items-center gap-2.5">
                    <div class="bg-blue-100 text-blue-600 p-2 rounded-lg shadow-sm">
                        <i class="fas fa-shipping-fast"></i>
                    </div>
                    Laporan Quick Scan SPX
                </h3>
                <button onclick="closeSpxGlobalModal()" class="text-gray-400 bg-gray-100 hover:bg-red-50 hover:text-red-500 w-8 h-8 rounded-full transition-colors focus:outline-none flex items-center justify-center">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            {{-- Badge Counter SPX --}}
            <div class="flex flex-wrap items-center gap-2.5">
                <span class="bg-white text-gray-800 text-xs font-bold px-3 py-1.5 rounded-lg border border-gray-200 shadow-sm flex items-center gap-1.5">
                    Total Scan: <span id="modal-summary-total" class="text-blue-600 text-sm">...</span>
                </span>
                <span class="bg-emerald-50 text-emerald-700 text-xs font-bold px-3 py-1.5 rounded-lg border border-emerald-200 shadow-sm flex items-center gap-1.5">
                    <i class="fas fa-check-double"></i> Selesai: <span id="modal-summary-copied" class="text-sm">...</span>
                </span>
                <span class="bg-rose-50 text-rose-700 text-xs font-bold px-3 py-1.5 rounded-lg border border-rose-200 shadow-sm flex items-center gap-1.5">
                    <i class="fas fa-minus-circle"></i> Belum: <span id="modal-summary-uncopied" class="text-sm">...</span>
                </span>
            </div>
        </div>

        {{-- Body Modal (Tabel Resi) --}}
        <div class="p-6 bg-white">
            <div class="max-h-[55vh] overflow-y-auto custom-scrollbar rounded-xl border border-gray-200 shadow-sm">
                <table class="min-w-full divide-y divide-gray-200 text-left">
                    <thead class="bg-gray-50/90 backdrop-blur-sm sticky top-0 z-10">
                        <tr>
                            <th scope="col" class="px-5 py-3.5 text-xs font-black text-gray-500 uppercase tracking-wider border-b border-gray-200">Pengirim</th>
                            <th scope="col" class="px-5 py-3.5 text-xs font-black text-gray-500 uppercase tracking-wider border-b border-gray-200">No Resi</th>
                            <th scope="col" class="px-5 py-3.5 text-center text-xs font-black text-gray-500 uppercase tracking-wider border-b border-gray-200">Status</th>
                        </tr>
                    </thead>
                    <tbody id="spx-global-tbody" class="bg-white divide-y divide-gray-100">
                        <tr>
                            <td colspan="3" class="px-6 py-12 text-center text-gray-400">
                                <i class="fas fa-circle-notch fa-spin fa-3x mb-3 text-blue-500"></i><br>
                                <span class="font-medium">Memuat data resi...</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Footer Modal --}}
        <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end gap-3">
            <button onclick="closeSpxGlobalModal()" class="px-5 py-2.5 bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 rounded-xl font-bold shadow-sm transition-all focus:outline-none focus:ring-2 focus:ring-gray-200">
                Tutup
            </button>
            <a href="/admin/spx-scans" class="px-5 py-2.5 bg-blue-600 border border-transparent text-white hover:bg-blue-700 rounded-xl font-bold shadow-sm hover:shadow transition-all flex items-center gap-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-1">
                Ke Halaman SPX <i class="fas fa-arrow-right"></i>
            </a>
        </div>
    </div>
</div>

<script>
    function openSpxGlobalModal() {
        console.log('LOG LOG: Membuka Modal Global SPX dari Floating Button');
        const modal = document.getElementById('spxGlobalModal');
        modal.style.display = 'flex';
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';

        // Panggil fungsi untuk fetch data
        fetchSpxGlobalData();
    }

    function closeSpxGlobalModal() {
        console.log('LOG LOG: Menutup Modal Global SPX');
        const modal = document.getElementById('spxGlobalModal');
        modal.style.display = 'none';
        modal.classList.add('hidden');
        document.body.style.overflow = 'auto';
    }

    // Menutup modal jika klik area luar
    window.addEventListener('click', function(event) {
        const modal = document.getElementById('spxGlobalModal');
        if (event.target === modal) {
            closeSpxGlobalModal();
        }
    });

    // Fetch data resi yang belum dicopy dari server
    function fetchSpxGlobalData() {
        console.log('LOG LOG: Mengambil data SPX via AJAX');
        const tbody = document.getElementById('spx-global-tbody');

        tbody.innerHTML = `<tr><td colspan="3" class="px-6 py-12 text-center text-gray-400"><i class="fas fa-circle-notch fa-spin fa-3x mb-3 text-blue-500"></i><br><span class="font-medium">Memuat data resi...</span></td></tr>`;

        fetch('/admin/spx_scans/api/unprocessed', {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            console.log('LOG LOG: Data SPX berhasil diambil', data);

            // Update nilai di Header Modal
            if(data.summary) {
                document.getElementById('modal-summary-total').textContent = data.summary.total_all;
                document.getElementById('modal-summary-copied').textContent = data.summary.total_copied;
                document.getElementById('modal-summary-uncopied').textContent = data.summary.total_uncopied;
            }

            tbody.innerHTML = '';

            let itemsArray = data.items || [];

            if(itemsArray.length === 0) {
                tbody.innerHTML = `<tr><td colspan="3" class="px-6 py-12 text-center text-emerald-600"><div class="mx-auto w-16 h-16 bg-emerald-100 rounded-full flex items-center justify-center mb-3"><i class="fas fa-check-circle fa-2xl"></i></div><p class="font-bold text-lg">Hore!</p><p class="text-sm font-medium mt-1">Semua resi sudah selesai di-copy.</p></td></tr>`;
                return;
            }

            // Hitung jumlah paket per orang untuk daftar yang belum dicopy
            let countPerPerson = {};
            itemsArray.forEach(scan => {
                countPerPerson[scan.pengirim] = (countPerPerson[scan.pengirim] || 0) + 1;
            });

            // Loop dan render data
            itemsArray.forEach(scan => {
                const tr = document.createElement('tr');
                tr.className = 'hover:bg-gray-50/80 transition-colors';
                tr.innerHTML = `
                    <td class="px-5 py-4 whitespace-nowrap">
                        <div class="flex flex-col gap-1.5">
                            <span class="text-sm font-bold text-gray-900">${scan.pengirim}</span>
                            <span class="bg-rose-50 text-rose-600 text-[10px] px-2.5 py-1 rounded-md w-fit border border-rose-100 shadow-sm font-bold">
                                <i class="fas fa-box text-[9px] mr-1"></i> ${countPerPerson[scan.pengirim]} Paket (Belum di-copy)
                            </span>
                        </div>
                    </td>
                    <td class="px-5 py-4 whitespace-nowrap">
                        <div class="flex items-center gap-2.5">
                            <button type="button" onclick="copyResiGlobal('${scan.resi_number}', '${scan.id}')" class="bg-gray-100 text-gray-500 hover:bg-blue-100 hover:text-blue-600 w-8 h-8 rounded-lg focus:outline-none transition-all shadow-sm flex items-center justify-center" title="Salin Nomor Resi">
                                <i id="global-icon-copy-${scan.id}" class="fas fa-copy"></i>
                            </button>
                            <span id="global-resi-${scan.id}" class="text-sm font-bold text-gray-800 tracking-wide">${scan.resi_number}</span>
                        </div>
                    </td>
                    <td class="px-5 py-4 whitespace-nowrap text-center" id="global-status-copas-${scan.id}">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-gray-100 text-gray-600 text-xs font-bold border border-gray-200 shadow-sm">
                            <i class="fas fa-minus"></i> Belum
                        </span>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        })
        .catch(error => {
            console.error('LOG LOG: Error fetch data SPX', error);
            tbody.innerHTML = `<tr><td colspan="3" class="px-6 py-12 text-center text-rose-500"><i class="fas fa-exclamation-triangle fa-3x mb-3 opacity-80"></i><br><span class="font-bold">Gagal memuat data. Silakan coba lagi.</span></td></tr>`;
        });
    }

    // Script Copy Resi dari Global Modal
    function copyResiGlobal(text, id) {
        console.log('LOG LOG: copyResiGlobal dipanggil. Text:', text, 'ID:', id);
        let iconId = 'global-icon-copy-' + id;
        let btnElement = document.getElementById(iconId).parentElement;

        navigator.clipboard.writeText(text).then(function() {
            console.log('LOG LOG: Text berhasil dicopy ke clipboard dari modal global.');
            let iconElement = document.getElementById(iconId);

            // Animasi tombol saat berhasil di-copy
            btnElement.classList.remove('bg-gray-100', 'text-gray-500', 'hover:bg-blue-100');
            btnElement.classList.add('bg-emerald-100', 'text-emerald-600', 'ring-2', 'ring-emerald-400');
            iconElement.className = 'fas fa-check';

            fetch(`/admin/spx_scans/${id}/mark-copied`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    console.log('LOG LOG: Update berhasil di global modal!');
                    document.getElementById('global-status-copas-' + id).innerHTML = '<span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-100 text-emerald-700 text-xs font-bold border border-emerald-200 shadow-sm"><i class="fas fa-check-double"></i> DONE</span>';

                    // Update live counter di Header Modal saat berhasil dicopy
                    let copiedEl = document.getElementById('modal-summary-copied');
                    let uncopiedEl = document.getElementById('modal-summary-uncopied');

                    if (copiedEl && uncopiedEl) {
                        let currentCopied = parseInt(copiedEl.textContent) || 0;
                        let currentUncopied = parseInt(uncopiedEl.textContent) || 0;

                        copiedEl.textContent = currentCopied + 1;
                        if(currentUncopied > 0) uncopiedEl.textContent = currentUncopied - 1;
                    }
                }
            })
            .catch(error => console.error('LOG LOG: Terjadi Error pada Fetch Global:', error));

            // Kembalikan style tombol setelah 2 detik
            setTimeout(() => {
                btnElement.classList.add('bg-gray-100', 'text-gray-500', 'hover:bg-blue-100');
                btnElement.classList.remove('bg-emerald-100', 'text-emerald-600', 'ring-2', 'ring-emerald-400');
                iconElement.className = 'fas fa-copy';
            }, 2000);
        }).catch(function(err) {
            console.error('LOG LOG: Gagal menyalin text global:', err);
            alert('Gagal menyalin nomor resi.');
        });
    }

</script>
