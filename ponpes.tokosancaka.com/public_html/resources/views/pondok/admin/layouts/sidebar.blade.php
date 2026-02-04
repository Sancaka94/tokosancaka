<!-- Sidebar -->
<aside class="w-64 bg-gray-800 text-white flex flex-col hidden md:flex">
    <div class="px-6 py-4 text-2xl font-bold border-b border-gray-700">Admin ePesantren</div>
    
    <!-- Alpine.js for dropdowns -->
    <script src="https://cdn.jsdelivr.net/gh/alpinejs/alpine@v2.x.x/dist/alpine.min.js" defer></script>

    <nav class="flex-1 px-4 py-4 space-y-1 overflow-y-auto">
        <a href="{{ route('admin.dashboard') }}" class="flex items-center px-4 py-2 text-gray-300 {{ request()->routeIs('admin.dashboard') ? 'bg-gray-700 text-white' : 'hover:bg-gray-700' }} rounded-md">
            <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
            Dashboard
        </a>

        <!-- Menu Manajemen Utama -->
        <div x-data="{ open: false }" class="space-y-1">
            <button @click="open = !open" class="w-full flex items-center justify-between px-4 py-2 text-gray-300 hover:bg-gray-700 rounded-md">
                <span class="flex items-center">
                    <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                    Manajemen Utama
                </span>
                <svg :class="{'rotate-180': open}" class="w-5 h-5 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
            </button>
            <div x-show="open" class="pl-8 space-y-1">
                <a href="{{ route('admin.santri.index') }}" class="block px-4 py-2 text-sm text-gray-300 hover:bg-gray-700 rounded-md">Santri</a>
                <a href="{{ route('admin.pegawai.index') }}" class="block px-4 py-2 text-sm text-gray-300 hover:bg-gray-700 rounded-md">Pegawai</a>
                <a href="{{ route('admin.pengguna.index') }}" class="block px-4 py-2 text-sm text-gray-300 hover:bg-gray-700 rounded-md">Pengguna</a>
                <a href="{{ route('admin.calon-santri.index') }}" class="block px-4 py-2 text-sm text-gray-300 hover:bg-gray-700 rounded-md">Pendaftar</a>
            </div>
        </div>

        <!-- Menu Akademik -->
        <div x-data="{ open: false }" class="space-y-1">
            <button @click="open = !open" class="w-full flex items-center justify-between px-4 py-2 text-gray-300 hover:bg-gray-700 rounded-md">
                <span class="flex items-center">
                    <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v11.494m-9-5.747h18"></path></svg>
                    Akademik
                </span>
                <svg :class="{'rotate-180': open}" class="w-5 h-5 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
            </button>
            <div x-show="open" class="pl-8 space-y-1">
                <a href="{{ route('admin.tahun-ajaran.index') }}" class="block px-4 py-2 text-sm text-gray-300 hover:bg-gray-700 rounded-md">Tahun Ajaran</a>
                <a href="{{ route('admin.unit-pendidikan.index') }}" class="block px-4 py-2 text-sm text-gray-300 hover:bg-gray-700 rounded-md">Unit Pendidikan</a>
                <a href="{{ route('admin.kelas.index') }}" class="block px-4 py-2 text-sm text-gray-300 hover:bg-gray-700 rounded-md">Kelas</a>
                <a href="{{ route('admin.kamar.index') }}" class="block px-4 py-2 text-sm text-gray-300 hover:bg-gray-700 rounded-md">Kamar</a>
                <a href="{{ route('admin.mata-pelajaran.index') }}" class="block px-4 py-2 text-sm text-gray-300 hover:bg-gray-700 rounded-md">Mata Pelajaran</a>
                <a href="{{ route('admin.jadwal-pelajaran.index') }}" class="block px-4 py-2 text-sm text-gray-300 hover:bg-gray-700 rounded-md">Jadwal Pelajaran</a>
                <a href="{{ route('admin.absensi-santri.index') }}" class="block px-4 py-2 text-sm text-gray-300 hover:bg-gray-700 rounded-md">Absensi Santri</a>
                <a href="{{ route('admin.penilaian-akademik.index') }}" class="block px-4 py-2 text-sm text-gray-300 hover:bg-gray-700 rounded-md">Penilaian</a>
                <a href="{{ route('admin.tahfidz-progress.index') }}" class="block px-4 py-2 text-sm text-gray-300 hover:bg-gray-700 rounded-md">Tahfidz</a>
            </div>
        </div>

        <!-- Menu Keuangan -->
        <div x-data="{ open: false }" class="space-y-1">
            <button @click="open = !open" class="w-full flex items-center justify-between px-4 py-2 text-gray-300 hover:bg-gray-700 rounded-md">
                <span class="flex items-center">
                    <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    Keuangan
                </span>
                <svg :class="{'rotate-180': open}" class="w-5 h-5 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
            </button>
            <div x-show="open" class="pl-8 space-y-1">
                <a href="{{ route('admin.pos-pembayaran.index') }}" class="block px-4 py-2 text-sm text-gray-300 hover:bg-gray-700 rounded-md">POS Pembayaran</a>
                <a href="{{ route('admin.tagihan-santri.index') }}" class="block px-4 py-2 text-sm text-gray-300 hover:bg-gray-700 rounded-md">Tagihan Santri</a>
                <a href="{{ route('admin.pembayaran-santri.index') }}" class="block px-4 py-2 text-sm text-gray-300 hover:bg-gray-700 rounded-md">Pembayaran Santri</a>
                <a href="{{ route('admin.tabungan-santri.index') }}" class="block px-4 py-2 text-sm text-gray-300 hover:bg-gray-700 rounded-md">Tabungan Santri</a>
                <a href="{{ route('admin.akun-akuntansi.index') }}" class="block px-4 py-2 text-sm text-gray-300 hover:bg-gray-700 rounded-md">Akun Akuntansi</a>
                <a href="{{ route('admin.transaksi-kas-bank.index') }}" class="block px-4 py-2 text-sm text-gray-300 hover:bg-gray-700 rounded-md">Kas & Bank</a>
                <a href="{{ route('admin.jurnal-umum.index') }}" class="block px-4 py-2 text-sm text-gray-300 hover:bg-gray-700 rounded-md">Jurnal Umum</a>
                <a href="{{ route('admin.penggajian.index') }}" class="block px-4 py-2 text-sm text-gray-300 hover:bg-gray-700 rounded-md">Penggajian</a>
            </div>
        </div>

        <!-- Menu Kesantrian & Kepegawaian -->
         <div x-data="{ open: false }" class="space-y-1">
            <button @click="open = !open" class="w-full flex items-center justify-between px-4 py-2 text-gray-300 hover:bg-gray-700 rounded-md">
                <span class="flex items-center">
                    <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    Kesantrian & Staf
                </span>
                <svg :class="{'rotate-180': open}" class="w-5 h-5 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
            </button>
            <div x-show="open" class="pl-8 space-y-1">
                <a href="{{ route('admin.jabatan.index') }}" class="block px-4 py-2 text-sm text-gray-300 hover:bg-gray-700 rounded-md">Jabatan</a>
                <a href="{{ route('admin.absensi-pegawai.index') }}" class="block px-4 py-2 text-sm text-gray-300 hover:bg-gray-700 rounded-md">Absensi Pegawai</a>
                <a href="{{ route('admin.izin-santri.index') }}" class="block px-4 py-2 text-sm text-gray-300 hover:bg-gray-700 rounded-md">Perizinan Santri</a>
                <a href="{{ route('admin.pelanggaran-santri.index') }}" class="block px-4 py-2 text-sm text-gray-300 hover:bg-gray-700 rounded-md">Pelanggaran Santri</a>
                <a href="{{ route('admin.rekam-medis.index') }}" class="block px-4 py-2 text-sm text-gray-300 hover:bg-gray-700 rounded-md">Rekam Medis</a>
            </div>
        </div>

        <!-- Menu Konten -->
         <div x-data="{ open: false }" class="space-y-1">
            <button @click="open = !open" class="w-full flex items-center justify-between px-4 py-2 text-gray-300 hover:bg-gray-700 rounded-md">
                <span class="flex items-center">
                    <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"></path></svg>
                    Konten
                </span>
                <svg :class="{'rotate-180': open}" class="w-5 h-5 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
            </button>
            <div x-show="open" class="pl-8 space-y-1">
                <a href="{{ route('admin.pengumuman.index') }}" class="block px-4 py-2 text-sm text-gray-300 hover:bg-gray-700 rounded-md">Pengumuman</a>
                <a href="{{ route('admin.post.index') }}" class="block px-4 py-2 text-sm text-gray-300 hover:bg-gray-700 rounded-md">Artikel</a>
            </div>
        </div>

        <!-- Menu Pengaturan -->
        <div x-data="{ open: false }" class="space-y-1">
            <button @click="open = !open" class="w-full flex items-center justify-between px-4 py-2 text-gray-300 hover:bg-gray-700 rounded-md">
                <span class="flex items-center">
                    <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                    Pengaturan
                </span>
                <svg :class="{'rotate-180': open}" class="w-5 h-5 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
            </button>
            <div x-show="open" class="pl-8 space-y-1">
                 <a href="{{ route('admin.paket.index') }}" class="block px-4 py-2 text-sm text-gray-300 hover:bg-gray-700 rounded-md">Paket Harga</a>
                 <a href="{{ route('admin.settings.index') }}" class="block px-4 py-2 text-sm text-gray-300 hover:bg-gray-700 rounded-md">Pengaturan Umum</a>
            </div>
        </div>
    </nav>
</aside>

