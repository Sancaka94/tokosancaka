{{-- ========================================================= --}}
{{-- GROUP SIDEBAR KANAN (AKTIVITAS & MONITOR) --}}
{{-- ========================================================= --}}

{{-- 1. SIDEBAR AKTIVITAS (Z-Index Teratur) --}}
<div x-data="{ activityOpen: false }"
    class="fixed inset-y-0 right-0 z-[100] flex items-center justify-end h-screen pointer-events-none">

    {{-- Tombol Trigger Aktivitas --}}
    <div @mouseenter="activityOpen = true; monitorOpen = false"
        class="fixed right-0 top-[33%] transform -translate-y-1/2 pointer-events-auto bg-blue-600 text-white py-4 px-1 rounded-l-xl shadow-lg cursor-pointer transition-all duration-300 hover:bg-blue-700 hover:pr-3 z-[110]">
        <div class="flex flex-col items-center gap-2">
            <i class="fas fa-history animate-pulse text-[10px]"></i>
            <span class="text-[10px] font-bold writing-vertical tracking-widest" style="writing-mode: vertical-rl; text-orientation: mixed;">AKTIVITAS</span>
        </div>
    </div>

    {{-- Panel Isi Aktivitas --}}
    <div class="h-full bg-white/95 backdrop-blur-md shadow-2xl border-l border-gray-200 w-96 transform transition-transform duration-300 ease-in-out overflow-y-auto custom-scrollbar relative pointer-events-auto z-[100]"
        :class="activityOpen ? 'translate-x-0' : 'translate-x-full'"
        @mouseleave="activityOpen = false">

        {{-- Header Aktivitas --}}
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


{{-- 2. SIDEBAR MONITOR (Z-Index Teratur) --}}
<div x-data="{ monitorOpen: false }"
    class="fixed inset-y-0 right-0 z-[100] flex items-center justify-end h-screen pointer-events-none">

    {{-- Tombol Trigger Monitor --}}
    <div @mouseenter="monitorOpen = true; activityOpen = false"
        class="fixed right-0 top-1/2 transform -translate-y-1/2 pointer-events-auto bg-red-600 text-white py-4 px-1 rounded-l-xl shadow-lg cursor-pointer transition-all duration-300 hover:bg-red-700 hover:pr-3 z-[110]">
        <div class="flex flex-col items-center gap-2">
            <i class="fas fa-desktop animate-pulse text-[10px]"></i>
            <span class="text-[10px] font-bold writing-vertical tracking-widest" style="writing-mode: vertical-rl; text-orientation: mixed;">MONITOR</span>
        </div>
    </div>

    {{-- Panel Isi Monitor --}}
    <div class="h-full bg-white/95 backdrop-blur-md shadow-2xl border-l border-gray-200 w-80 transform transition-transform duration-300 ease-in-out overflow-y-auto custom-scrollbar relative pointer-events-auto z-[100]"
        :class="monitorOpen ? 'translate-x-0' : 'translate-x-full'"
        @mouseleave="monitorOpen = false">

        {{-- Header Monitor --}}
        <div class="h-15 px-6 bg-red-900 text-white flex justify-between items-center sticky top-0 z-10 shadow-md border-b border-gray-700">
            <h3 class="font-bold text-sm tracking-wider uppercase flex items-center gap-2">
                <i class="fas fa-chart-line text-red-500 animate-pulse"></i> Live Monitor
            </h3>
            <button @click="monitorOpen = false" class="text-gray-400 hover:text-white transition focus:outline-none">
                <i class="fas fa-times text-lg"></i>
            </button>
        </div>

        {{-- Konten Kartu Monitor --}}
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

            {{-- Tombol SPX sudah dihapus dari sini supaya desainnya rapi --}}
        </div>
    </div>
</div>

{{-- ========================================================= --}}
{{-- 3. TOMBOL TRIGGER QUICK COPAS SPX (BERDIRI SENDIRI DI LUAR) --}}
{{-- ========================================================= --}}
<div onclick="openSpxGlobalModal()"
    class="fixed right-0 top-[66%] transform -translate-y-1/2 pointer-events-auto bg-orange-600 text-white py-4 px-1 rounded-l-xl shadow-lg cursor-pointer transition-all duration-300 hover:bg-orange-700 hover:pr-3 z-[110]">
    <div class="flex flex-col items-center gap-2">
        <i class="fas fa-clipboard-list animate-pulse text-[10px]"></i>
        <span class="text-[10px] font-bold writing-vertical tracking-widest" style="writing-mode: vertical-rl; text-orientation: mixed;">SPX COPAS</span>
    </div>
</div>

{{-- ========================================================= --}}
{{-- SELESAI: GROUP SIDEBAR KANAN --}}
{{-- ========================================================= --}}


{{-- ========================================================= --}}
{{-- MODAL & SCRIPT QUICK COPAS SPX (GLOBAL) --}}
{{-- ========================================================= --}}
<div id="spxGlobalModal" class="fixed inset-0 z-[9999] hidden bg-gray-900 bg-opacity-60 overflow-y-auto h-full w-full backdrop-blur-sm transition-opacity duration-300 flex items-center justify-center" style="display: none;">
    <div class="relative mx-auto p-6 border w-11/12 md:w-3/4 lg:w-2/3 shadow-2xl rounded-2xl bg-white">

       {{-- Header Modal --}}
        <div class="pb-4 border-b border-gray-200 relative">
            <div class="flex justify-between items-center mb-3">
                <h3 class="text-xl font-extrabold text-gray-800 flex items-center gap-2">
                    <i class="fas fa-shipping-fast text-blue-600"></i> Laporan Quick Scan SPX
                </h3>
                <button onclick="closeSpxGlobalModal()" class="text-gray-400 hover:text-red-500 transition focus:outline-none">
                    <i class="fas fa-times fa-lg"></i>
                </button>
            </div>
            {{-- Badge Counter SPX --}}
            <div class="flex flex-wrap items-center gap-2">
                <span class="bg-gray-100 text-gray-800 text-xs font-bold px-3 py-1.5 rounded-full border border-gray-200 shadow-sm">
                    Total: <span id="modal-summary-total">...</span>
                </span>
                <span class="bg-green-100 text-green-800 text-xs font-bold px-3 py-1.5 rounded-full border border-green-200 shadow-sm">
                    <i class="fas fa-check-double mr-1"></i> Selesai: <span id="modal-summary-copied">...</span>
                </span>
                <span class="bg-red-100 text-red-800 text-xs font-bold px-3 py-1.5 rounded-full border border-red-200 shadow-sm">
                    <i class="fas fa-minus-circle mr-1"></i> Belum: <span id="modal-summary-uncopied">...</span>
                </span>
            </div>
        </div>

        {{-- Body Modal (Tabel Resi) --}}
        <div class="mt-5 max-h-[60vh] overflow-y-auto custom-scrollbar pr-2">
            <table class="min-w-full divide-y divide-gray-200 border border-gray-100 rounded-lg overflow-hidden">
                <thead class="bg-gray-50 sticky top-0 z-10">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Pengirim</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">No Resi</th>
                        <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Status</th>
                    </tr>
                </thead>
                <tbody id="spx-global-tbody" class="bg-white divide-y divide-gray-100">
                    <tr>
                        <td colspan="3" class="px-4 py-8 text-center text-gray-500">
                            <i class="fas fa-spinner fa-spin fa-2x mb-2"></i><br>
                            Memuat data resi...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- Footer Modal --}}
        <div class="mt-6 flex justify-end gap-3 pt-4 border-t border-gray-200">
            <button onclick="closeSpxGlobalModal()" class="px-5 py-2 bg-gray-200 text-gray-800 hover:bg-gray-300 rounded-lg font-medium transition">
                Tutup
            </button>
            <a href="/admin/spx_scans" class="px-5 py-2 bg-blue-600 text-white hover:bg-blue-700 rounded-lg font-medium transition">
                Ke Halaman SPX <i class="fas fa-arrow-right ml-1"></i>
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

        tbody.innerHTML = `<tr><td colspan="3" class="px-4 py-8 text-center text-gray-500"><i class="fas fa-spinner fa-spin fa-2x mb-2"></i><br>Memuat data resi...</td></tr>`;

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
                tbody.innerHTML = `<tr><td colspan="3" class="px-4 py-8 text-center text-green-600 font-bold"><i class="fas fa-check-circle fa-2x mb-2"></i><br>Semua resi sudah selesai di-copy!</td></tr>`;
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
                tr.className = 'hover:bg-gray-50';
                tr.innerHTML = `
                    <td class="px-4 py-3 whitespace-nowrap text-sm font-bold text-gray-900">
                        <div class="flex flex-col gap-1">
                            <span>${scan.pengirim}</span>
                            <span class="bg-red-50 text-red-600 text-[10px] px-2 py-0.5 rounded-md w-fit border border-red-100 shadow-sm">
                                <i class="fas fa-box text-[9px] mr-1"></i> ${countPerPerson[scan.pengirim]} Paket (Belum di-copy)
                            </span>
                        </div>
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm font-semibold text-gray-900">
                        <div class="flex items-center gap-2">
                            <button type="button" onclick="copyResiGlobal('${scan.resi_number}', '${scan.id}')" class="text-gray-400 hover:text-blue-600 focus:outline-none transition-colors" title="Salin Nomor Resi">
                                <i id="global-icon-copy-${scan.id}" class="fas fa-copy"></i>
                            </button>
                            <span id="global-resi-${scan.id}">${scan.resi_number}</span>
                        </div>
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-center text-sm" id="global-status-copas-${scan.id}">
                        <span class="text-red-600 font-semibold"><i class="fas fa-minus"></i> Belum</span>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        })
        .catch(error => {
            console.error('LOG LOG: Error fetch data SPX', error);
            tbody.innerHTML = `<tr><td colspan="3" class="px-4 py-8 text-center text-red-500"><i class="fas fa-exclamation-triangle fa-2x mb-2"></i><br>Gagal memuat data.</td></tr>`;
        });
    }

    // Script Copy Resi dari Global Modal
    function copyResiGlobal(text, id) {
        console.log('LOG LOG: copyResiGlobal dipanggil. Text:', text, 'ID:', id);
        let iconId = 'global-icon-copy-' + id;

        navigator.clipboard.writeText(text).then(function() {
            console.log('LOG LOG: Text berhasil dicopy ke clipboard dari modal global.');
            let iconElement = document.getElementById(iconId);
            iconElement.className = 'fas fa-check text-green-500';

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
                    document.getElementById('global-status-copas-' + id).innerHTML = '<span class="text-green-600 font-semibold"><i class="fas fa-check-double"></i> DONE</span>';

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

            setTimeout(() => { iconElement.className = 'fas fa-copy'; }, 2000);
        }).catch(function(err) {
            console.error('LOG LOG: Gagal menyalin text global:', err);
            alert('Gagal menyalin nomor resi.');
        });
    }

</script>
