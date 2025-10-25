{{--

    File: resources/views/layouts/partials/sidebar.blade.php

    Deskripsi: Sidebar navigasi yang LENGKAP dengan semua menu, termasuk menu Blog yang sudah diperbaiki dan notifikasi real-time.

--}}

<aside id="sidebar-wrapper" class="bg-gray-800 text-gray-300 flex-shrink-0 flex flex-col w-[280px] min-h-screen fixed inset-y-0 left-0 z-50 transform -translate-x-full lg:relative lg:translate-x-0 transition-transform duration-300 ease-in-out">

    <div class="flex justify-end p-4 lg:hidden">

        <button id="sidebarClose" class="text-gray-400 hover:text-white">

            <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none"

                viewBox="0 0 24 24" stroke="currentColor">

                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"

                    d="M6 18L18 6M6 6l12 12" />

            </svg>

        </button>

    </div>

    <!-- User Panel -->

    <div class="flex flex-col items-center p-6 border-b border-gray-700">

        <img src="https://tokosancaka.biz.id/storage/uploads/sancaka.png" class="rounded-full mb-3" alt="User Image" width="80" height="80" onerror="this.onerror=null;this.src='https://placehold.co/80x80/1f2937/ffffff?text=Logo';">

        <div class="font-bold text-lg text-white">{{ Auth::user()->nama_lengkap ?? 'Sancaka Express' }}</div>

        <div class="flex items-center text-sm mt-1">

            <span class="w-2 h-2 bg-green-500 rounded-full mr-2"></span>

            <span class="text-green-400">Online</span>

        </div>

    </div>



    <!-- Search Form -->

    <div class="p-4">

        <form action="#" method="get">

            <div class="relative">

                <input type="text" name="q" class="w-full bg-gray-700 border border-gray-600 text-white text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block pl-10 p-2.5" placeholder="Cari...">

                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">

                    <i class="fa-solid fa-search text-gray-400"></i>

                </div>

            </div>

        </form>

    </div>



    <!-- Sidebar Menu -->

    <nav id="sidebar-nav" class="flex-1 px-4 pb-4 space-y-1 overflow-y-auto">

        {{-- Dashboard --}}

        <a href="{{ route('admin.dashboard') }}" class="sidebar-link flex items-center px-4 py-2.5 text-sm font-medium rounded-lg hover:bg-gray-700 hover:text-white {{ request()->routeIs('admin.dashboard') ? 'bg-gray-700 text-white' : '' }}">

            <i class="fa-solid fa-house-chimney fa-fw w-5 h-5 mr-3"></i>

            <span>Dashboard</span>

        </a>

        

        <a href="{{ url('admin/email') }}" 

            class="sidebar-link flex items-center px-4 py-2.5 text-sm font-medium rounded-lg hover:bg-gray-700 hover:text-white {{ request()->routeIs('admin.index*') ? 'bg-gray-700 text-white' : '' }}">

            <i class="fa-solid fa-inbox fa-fw w-5 h-5 mr-3"></i>

            <span>Email Sancaka</span>

        </a>

        {{-- Menu Pelanggan --}}
                <a href="{{ route('admin.pelanggan.index') }}" class="sidebar-link flex items-center px-4 py-2.5 text-sm font-medium rounded-lg hover:bg-gray-700 hover:text-white {{ request()->routeIs('admin.pelanggan.*') ? 'bg-gray-700 text-white' : '' }}">
                    <i class="fa-solid fa-users fa-fw w-5 h-5 mr-3"></i>
                    <span>Data Pelanggan</span>
                </a>
        

        <a href="{{ route('admin.spx_scans.monitor.index') }}"

            class="sidebar-link flex items-center px-4 py-2.5 text-sm font-medium rounded-lg hover:bg-gray-700 hover:text-white {{ request()->routeIs('admin.spx_scans.monitor.index') ? 'bg-gray-700 text-white' : '' }}">

            <i class="fa-solid fa-truck fa-fw w-5 h-5 mr-3"></i>

            <span>Monitor Surat Jalan</span>

        </a>



        

        {{-- Pengguna & Role --}}

        <div>

            <button onclick="toggleMenu('menuPengguna')" class="w-full flex items-center justify-between px-4 py-2.5 text-sm font-medium text-left rounded-lg hover:bg-gray-700 hover:text-white focus:outline-none {{ request()->routeIs(['admin.registrations.*', 'admin.customers.*', 'admin.roles.*']) ? 'bg-gray-700 text-white' : '' }}">

                <span class="flex items-center">

                    <i class="fa-solid fa-users-gear fa-fw w-5 h-5 mr-3"></i>

                    <span>Pengguna & Role</span>

                </span>

                <div class="flex items-center ml-auto">

                    <span id="menu-pengguna-badge" class="inline-flex items-center justify-center px-2 py-0.5 text-xs font-bold text-white bg-green-500 rounded-md mr-2">0</span>

                    <i id="arrow-menuPengguna" class="fa-solid fa-chevron-down w-4 h-4 transform transition-transform duration-200"></i>

                </div>

            </button>

            <div id="menuPengguna" class="submenu mt-1">

                <ul class="pl-8 pr-2 py-1 space-y-1">

                    <li>

                        <a href="{{ route('admin.registrations.index') }}" class="sidebar-link flex justify-between items-center px-4 py-2 text-sm rounded-md hover:text-white hover:bg-gray-700 {{ request()->routeIs('admin.registrations.*') ? 'text-white' : 'text-gray-400' }}">

                            <span>Persetujuan</span>

                            <span id="persetujuan-badge" class="inline-flex items-center justify-center px-2 text-xs font-bold text-white bg-green-500 rounded-md">0</span>

                        </a>

                    </li>

                    <li>

                        <a href="{{ route('admin.customers.index') }}" class="sidebar-link block px-4 py-2 text-sm rounded-md hover:text-white hover:bg-gray-700 {{ request()->routeIs('admin.customers.*') ? 'text-white' : 'text-gray-400' }}">

                            Manajemen Pelanggan

                        </a>

                    </li>

                    <li>

                        <a href="{{ route('admin.roles.index') }}" class="sidebar-link block px-4 py-2 text-sm rounded-md hover:text-white hover:bg-gray-700 {{ request()->routeIs('admin.roles.*') ? 'text-white' : 'text-gray-400' }}">

                            Hak Akses Role

                        </a>

                    </li>

                </ul>

            </div>

        </div>

        

{{-- Wilayah & Kode Pos --}}



        <a href="{{ route('admin.wilayah.index') }}" 

            class="sidebar-link flex items-center px-4 py-2.5 text-sm font-medium rounded-lg hover:bg-gray-700 hover:text-white 

            {{ request()->routeIs('admin.wilayah.*') ? 'bg-gray-700 text-white' : '' }}">

            <i class="fa-solid fa-map-marked-alt fa-fw w-5 h-5 mr-3"></i>

            <span>Wilayah</span>

        </a>



        {{-- ✅ LINK KODE POS DIPERBAIKI DAN DIPINDAHKAN KE SINI --}}

        <a href="{{ route('admin.kodepos.index') }}"

            class="sidebar-link flex items-center px-4 py-2.5 text-sm font-medium rounded-lg hover:bg-gray-700 hover:text-white {{ request()->routeIs('admin.kodepos.*') ? 'bg-gray-700 text-white' : '' }}">

            <i class="fa-solid fa-magnifying-glass-location fa-fw w-5 h-5 mr-3"></i>

            <span>Pencarian Kode Pos</span>

        </a>


        {{-- Marketplace --}}

        <div>

            <button onclick="toggleMenu('marketplaceMenu')" class="w-full flex items-center justify-between px-4 py-2.5 text-sm font-medium text-left rounded-lg hover:bg-gray-700 hover:text-white focus:outline-none {{ request()->is('admin/products*') || request()->is('admin/spx-scans*') ? 'bg-gray-700 text-white' : '' }}">

                <span class="flex items-center">

                    <i class="fa-solid fa-store fa-fw w-5 h-5 mr-3"></i>

                    <span>Marketplace</span>

                </span>

                <div class="flex items-center ml-auto">

                    <span id="menu-marketplace-badge" class="inline-flex items-center justify-center px-2 py-0.5 text-xs font-bold text-white bg-green-500 rounded-md mr-2">0</span>

                    <i id="arrow-marketplaceMenu" class="fa-solid fa-chevron-down w-4 h-4 transform transition-transform duration-200"></i>

                </div>

            </button>

            <div id="marketplaceMenu" class="submenu mt-1">

                <ul class="pl-8 pr-2 py-1 space-y-1">

                       {{-- ✅ LINK BARU DITAMBAHKAN DI SINI --}}

                    {{-- PERBAIKAN: Menambahkan link Kategori Produk di dalam menu Marketplace --}}
                    <li>
                        <a href="{{ route('admin.categories.index', ['type' => 'marketplace']) }}" class="sidebar-link block px-4 py-2 text-sm rounded-md hover:text-white hover:bg-gray-700 {{ request()->routeIs('admin.categories.*') && request('type') == 'marketplace' ? 'text-white' : 'text-gray-400' }}">
                            Kategori Produk
                        </a>
                    </li>

                    <li><a href="{{ route('admin.stores.index') }}" class="sidebar-link block px-4 py-2 text-sm rounded-md hover:text-white hover:bg-gray-700 {{ request()->routeIs('admin.stores.index') || request()->routeIs('admin.stores.edit') ? 'text-white' : 'text-gray-400' }}">Kelola Toko</a></li>

                    <li><a href="{{ route('admin.stores.create') }}" class="sidebar-link block px-4 py-2 text-sm rounded-md hover:text-white hover:bg-gray-700 {{ request()->routeIs('admin.stores.create') ? 'text-white' : 'text-gray-400' }}">Daftar Toko (Admin)</a></li>

                    {{-- ✅ DIPERBAIKI: Menggunakan nama route yang baru --}}

                    <li><a href="{{ route('admin.customer-to-seller.index') }}" class="sidebar-link block px-4 py-2 text-sm rounded-md hover:text-white hover:bg-gray-700 {{ request()->routeIs('admin.customer-to-seller.*') ? 'text-white' : 'text-gray-400' }}">Create Penjual</a></li>

                    {{-- ====================================================== --}}
                      {{-- == ✅ LINK DATA PESANAN MASUK DITAMBAHKAN DI SINI == --}}
                      {{-- ====================================================== --}}
                      <li>
                           <a href="{{ route('admin.orders.index') }}" class="sidebar-link block px-4 py-2 text-sm rounded-md hover:text-white hover:bg-gray-700 {{ request()->routeIs('admin.orders.*') ? 'text-white' : 'text-gray-400' }}">
                               Data Pesanan Masuk
                           </a>
                      </li>

                    <li><a href="{{ route('admin.products.index') }}" class="sidebar-link block px-4 py-2 text-sm rounded-md hover:text-white hover:bg-gray-700 {{ request()->routeIs('admin.products.index') ? 'text-white' : 'text-gray-400' }}">Daftar Produk</a></li>

                    <li><a href="{{ route('admin.products.create') }}" class="sidebar-link block px-4 py-2 text-sm rounded-md hover:text-white hover:bg-gray-700 {{ request()->routeIs('admin.products.create') ? 'text-white' : 'text-gray-400' }}">Tambah Produk</a></li>

                    <li><a href="{{ route('admin.spx_scans.create') }}" class="sidebar-link block px-4 py-2 text-sm rounded-md hover:text-white hover:bg-gray-700 {{ request()->routeIs('scan.spx.show') ? 'text-white' : 'text-gray-400' }}">Scan SPX</a></li>

                    <li>

                        <a href="{{ route('admin.spx_scans.index') }}" class="sidebar-link flex justify-between items-center px-4 py-2 text-sm rounded-md hover:text-white hover:bg-gray-700 {{ request()->routeIs('admin.spx-scans.index') ? 'text-white' : 'text-gray-400' }}">

                            <span>Data Scan SPX</span>

                            <span id="spx-badge" class="inline-flex items-center justify-center px-2 text-xs font-bold text-white bg-green-500 rounded-md">0</span>

                        </a>

                    </li>

                    

                </ul>

            </div>

        </div>


       


        {{-- Pesanan --}}

        <div>

            <button onclick="toggleMenu('menuPesanan')" class="w-full flex items-center justify-between px-4 py-2.5 text-sm font-medium text-left rounded-lg hover:bg-gray-700 hover:text-white focus:outline-none {{ request()->is('admin/pesanan*') ? 'bg-gray-700 text-white' : '' }}">

                <span class="flex items-center">

                    <i class="fa-solid fa-cart-shopping fa-fw w-5 h-5 mr-3"></i>

                    <span>Pesanan</span>

                </span>

                <div class="flex items-center ml-auto">

                    <span id="menu-pesanan-badge" class="inline-flex items-center justify-center px-2 py-0.5 text-xs font-bold text-white bg-green-500 rounded-md mr-2">0</span>

                    <i id="arrow-menuPesanan" class="fa-solid fa-chevron-down w-4 h-4 transform transition-transform duration-200"></i>

                </div>

            </button>

            <div id="menuPesanan" class="submenu mt-1">

                <ul class="pl-8 pr-2 py-1 space-y-1">

                    <li><a href="{{ route('admin.pesanan.create') }}" class="sidebar-link block px-4 py-2 text-sm rounded-md hover:text-white hover:bg-gray-700 {{ request()->routeIs('admin.pesanan.create') ? 'text-white' : 'text-gray-400' }}">Tambah Pesanan</a></li>

                    <li>

                        <a href="{{ route('admin.pesanan.index') }}" class="sidebar-link flex justify-between items-center px-4 py-2 text-sm rounded-md hover:text-white hover:bg-gray-700 {{ request()->routeIs('admin.pesanan.index') ? 'text-white' : 'text-gray-400' }}">

                            <span>Data Pesanan</span>

                            <span id="pesanan-badge" class="inline-flex items-center justify-center px-2 text-xs font-bold text-white bg-green-500 rounded-md">0</span>

                        </a>

                    </li>

                    <li>

                        <a href="{{ route('admin.pesanan.riwayat.scan') }}" class="sidebar-link flex justify-between items-center px-4 py-2 text-sm rounded-md hover:text-white hover:bg-gray-700 {{ request()->routeIs('admin.pesanan.riwayat.scan') ? 'text-white' : 'text-gray-400' }}">

                            <span>Riwayat Scan</span>

                             <span id="riwayat-scan-badge" class="inline-flex items-center justify-center px-2 text-xs font-bold text-white bg-green-500 rounded-md">0</span>

                        </a>

                    </li>

                </ul>

            </div>

        </div>



        {{-- Buku Alamat (Kontak) --}}

        <a href="{{ route('admin.kontak.index') }}" class="sidebar-link flex items-center px-4 py-2.5 text-sm font-medium rounded-lg hover:bg-gray-700 hover:text-white {{ request()->routeIs('admin.kontak.*') ? 'bg-gray-700 text-white' : '' }}">

            <i class="fa-solid fa-address-book fa-fw w-5 h-5 mr-3"></i>

            <span>Buku Alamat</span>

        </a>


        {{-- ====================================================== --}}
        {{-- == ✅ LINK BARU DITAMBAHKAN DI SINI == --}}
        {{-- ====================================================== --}}
        <a href="{{ route('admin.marketplace.index') }}" class="sidebar-link flex items-center px-4 py-2.5 text-sm font-medium rounded-lg hover:bg-gray-700 hover:text-white {{ request()->routeIs('admin.marketplace.*') ? 'bg-gray-700 text-white' : '' }}">
            <i class="fa-solid fa-store fa-fw w-5 h-5 mr-3"></i>
            <span>Produk Marketplace</span>
        </a>
        {{-- ====================================================== --}}
        {{-- == AKHIR DARI PENAMBAHAN LINK == --}}
        {{-- ====================================================== --}}



        {{-- ====================================================================== --}}

        {{-- == MENU BLOG YANG BARU DITAMBAHKAN DAN DIPERBAIKI == --}}

        {{-- ====================================================================== --}}

        <div>

            <button onclick="toggleMenu('menuBlog')" class="w-full flex items-center justify-between px-4 py-2.5 text-sm font-medium text-left rounded-lg hover:bg-gray-700 hover:text-white focus:outline-none {{ request()->is('admin/posts*') || request()->is('admin/categories*') || request()->is('admin/tags*') ? 'bg-gray-700 text-white' : '' }}">

                <span class="flex items-center">

                    <i class="fa-solid fa-newspaper fa-fw w-5 h-5 mr-3"></i>

                    <span>Blog</span>

                </span>

                <i id="arrow-menuBlog" class="fa-solid fa-chevron-down w-4 h-4 transform transition-transform duration-200"></i>

            </button>

            <div id="menuBlog" class="submenu mt-1">

                <ul class="pl-8 pr-2 py-1 space-y-1">

                    <li>

                        <a href="{{ route('admin.posts.index') }}" class="sidebar-link block px-4 py-2 text-sm rounded-md hover:text-white hover:bg-gray-700 {{ request()->routeIs(['admin.posts.index', 'admin.posts.edit']) ? 'text-white' : 'text-gray-400' }}">

                            Semua Postingan

                        </a>

                    </li>

                    <li>

                        <a href="{{ route('admin.posts.create') }}" class="sidebar-link block px-4 py-2 text-sm rounded-md hover:text-white hover:bg-gray-700 {{ request()->routeIs('admin.posts.create') ? 'text-white' : 'text-gray-400' }}">

                            Tambah Baru

                        </a>

                    </li>

                    <li>

                        <a href="{{ route('admin.categories.index', ['type' => 'blog']) }}" class="sidebar-link block px-4 py-2 text-sm rounded-md hover:text-white hover:bg-gray-700 {{ request()->routeIs('admin.categories.*') ? 'text-white' : 'text-gray-400' }}">

                            Kategori

                        </a>

                    </li>

                    <li>

                        <a href="{{ route('admin.tags.index') }}" class="sidebar-link block px-4 py-2 text-sm rounded-md hover:text-white hover:bg-gray-700 {{ request()->routeIs('admin.tags.*') ? 'text-white' : 'text-gray-400' }}">

                            Tag

                        </a>

                    </li>

                </ul>

            </div>

        </div>



        {{-- Manajemen Kurir --}}

<div>

    <button onclick="toggleMenu('menuKurir')" class="w-full flex items-center justify-between px-4 py-2.5 text-sm font-medium text-left rounded-lg hover:bg-gray-700 hover:text-white focus:outline-none">

        <span class="flex items-center">

            <i class="fa-solid fa-truck-fast fa-fw w-5 h-5 mr-3"></i>

            <span>Manajemen Kurir</span>

        </span>

        <i id="arrow-menuKurir" class="fa-solid fa-chevron-down w-4 h-4 transform transition-transform duration-200"></i>

    </button>

    <div id="menuKurir" class="submenu mt-1">

        <ul class="pl-8 pr-2 py-1 space-y-1">

            <li><a href="{{ route('admin.couriers.create') }}" class="sidebar-link block px-4 py-2 text-sm rounded-md">Tambah Kurir</a></li>

            <li><a href="{{ route('admin.couriers.index') }}" class="sidebar-link block px-4 py-2 text-sm rounded-md">Daftar Kurir</a></li>

        </ul>

    </div>

</div>



        {{-- Laporan Keuangan --}}

        <div>

            <button onclick="toggleMenu('menuKeuangan')" class="w-full flex items-center justify-between px-4 py-2.5 text-sm font-medium text-left rounded-lg hover:bg-gray-700 hover:text-white focus:outline-none {{ request()->routeIs('admin.saldo.requests.*') ? 'bg-gray-700 text-white' : '' }}">

                <span class="flex items-center">

                    <i class="fa-solid fa-chart-pie fa-fw w-5 h-5 mr-3"></i>

                    <span>Laporan Keuangan</span>

                </span>

                <div class="flex items-center ml-auto">

                    <span id="menu-keuangan-badge" class="inline-flex items-center justify-center px-2 py-0.5 text-xs font-bold text-white bg-orange-500 rounded-md mr-2">0</span>

                    <i id="arrow-menuKeuangan" class="fa-solid fa-chevron-down w-4 h-4 transform transition-transform duration-200"></i>

                </div>

            </button>

            <div id="menuKeuangan" class="submenu mt-1">

                <ul class="pl-8 pr-2 py-1 space-y-1">

                    <li>

                        <a href="{{ route('admin.saldo.requests.index') }}" class="sidebar-link flex justify-between items-center px-4 py-2 text-sm rounded-md hover:text-white hover:bg-gray-700 {{ request()->routeIs('admin.saldo.requests.*') ? 'text-white' : 'text-gray-400' }}">

                            <span>Permintaan Saldo</span>

                            <span id="saldo-requests-badge" class="inline-flex items-center justify-center px-2 text-xs font-bold text-white bg-orange-500 rounded-md">0</span>

                        </a>

                    </li>

                  

                    <li><a href="{{ route('admin.laporan.pemasukan') }}" class="sidebar-link block px-4 py-2 text-sm rounded-md hover:text-white hover:bg-gray-700 {{ request()->routeIs('admin.laporan.pemasukan*') ? 'text-white' : 'text-gray-400' }}">Pemasukan</a></li>

                    <li><a href="{{ route('admin.laporan.pengeluaran') }}" class="sidebar-link block px-4 py-2 text-sm rounded-md hover:text-white hover:bg-gray-700 {{ request()->routeIs('admin.laporan.pengeluaran*') ? 'text-white' : 'text-gray-400' }}">Pengeluaran</a></li>

                    <li><a href="{{ route('admin.laporan.labaRugi') }}" class="sidebar-link block px-4 py-2 text-sm rounded-md hover:text-white hover:bg-gray-700 {{ request()->routeIs('admin.laporan.labaRugi*') ? 'text-white' : 'text-gray-400' }}">Laba Rugi</a></li>

                    <li><a href="{{ route('admin.coa.index') }}" class="sidebar-link block px-4 py-2 text-sm rounded-md hover:text-white hover:bg-gray-700 {{ request()->routeIs('admin.coa.*') ? 'text-white' : 'text-gray-400' }}">Manajemen Akun (COA)</a></li>

                    <li><a href="{{ route('admin.laporan.neracaSaldo') }}" class="sidebar-link block px-4 py-2 text-sm rounded-md hover:text-white hover:bg-gray-700 {{ request()->routeIs('admin.laporan.neracaSaldo') ? 'text-white' : 'text-gray-400' }}">Neraca Saldo</a></li>

                    <li><a href="{{ route('admin.laporan.neraca') }}" class="sidebar-link block px-4 py-2 text-sm rounded-md hover:text-white hover:bg-gray-700 {{ request()->routeIs('admin.laporan.neraca') ? 'text-white' : 'text-gray-400' }}">Neraca</a></li>



                  </ul>

            </div>

        </div>



        {{-- Pengaturan (Utilitas) --}}

        <div>

            <button onclick="toggleMenu('menuUtilitas')" class="w-full flex items-center justify-between px-4 py-2.5 text-sm font-medium text-left rounded-lg hover:bg-gray-700 hover:text-white focus:outline-none">

                <span class="flex items-center">

                    <i class="fa-solid fa-gears fa-fw w-5 h-5 mr-3"></i>

                    <span>Pengaturan</span>

                </span>

                <i id="arrow-menuUtilitas" class="fa-solid fa-chevron-down w-4 h-4 transform transition-transform duration-200"></i>

            </button>

            <div id="menuUtilitas" class="submenu mt-1">

                <ul class="pl-8 pr-2 py-1 space-y-1">

                    <li><a href="{{ route('admin.activity-log.index') }}" class="sidebar-link block px-4 py-2 text-sm rounded-md hover:text-white hover:bg-gray-700 {{ request()->routeIs('admin.activity-log.index') ? 'text-white' : 'text-gray-400' }}">Log Aktivitas</a></li>

                    <li><a href="{{ route('admin.settings.index') }}" class="sidebar-link block px-4 py-2 text-sm rounded-md hover:text-white hover:bg-gray-700 {{ request()->routeIs('admin.settings.index') ? 'text-white' : 'text-gray-400' }}">Pengaturan Aplikasi</a></li>

                    <li><a href="{{ route('admin.settings.banners.index') }}" class="sidebar-link block px-4 py-2 text-sm rounded-md hover:text-white hover:bg-gray-700 {{ request()->routeIs('admin.settings.banners.index') ? 'text-white' : 'text-gray-400' }}">Pengaturan Marketplace</a></li>

                    {{-- PERBAIKAN: Menambahkan link baru untuk atribut kategori --}}
                    <li>
                        <a href="{{ route('admin.category-attributes.index') }}" class="sidebar-link block px-4 py-2 text-sm rounded-md hover:text-white hover:bg-gray-700 {{ request()->routeIs('admin.category-attributes.*') ? 'text-white' : 'text-gray-400' }}">
                            Atribut Kategori
                        </a>
                    </li>

                </ul>

            </div>

        </div>

    </nav>



    <div class="mt-auto p-4 border-t border-gray-700">

        <a href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();" class="flex items-center w-full px-4 py-2.5 text-sm font-medium text-red-400 rounded-lg hover:bg-gray-700 hover:text-white">

            <i class="fa-solid fa-arrow-right-from-bracket fa-fw w-5 h-5 mr-3"></i>

            <span>Keluar</span>

        </a>

        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">

            @csrf

        </form>

    </div>

</aside>



@push('scripts')

{{-- ====================================================================== --}}

{{-- == SEMUA SCRIPT UNTUK SIDEBAR DITEMPATKAN DI SINI == --}}

{{-- ====================================================================== --}}



{{-- 1. Script untuk menu dropdown --}}

<script>

    document.addEventListener("DOMContentLoaded", function () {

        const sidebar = document.getElementById("sidebar-wrapper");

        const toggleMobile = document.getElementById("sidebarToggle");

        const closeBtn = document.getElementById("sidebarClose");



        // buka sidebar

        if (toggleMobile) {

            toggleMobile.addEventListener("click", function () {

                sidebar.classList.toggle("-translate-x-full");

            });

        }



        // tutup sidebar

        if (closeBtn) {

            closeBtn.addEventListener("click", function () {

                sidebar.classList.add("-translate-x-full");

            });

        }

    });

</script>

<script>

    // Menambahkan style untuk transisi submenu

    const style = document.createElement('style');

    style.textContent = `.submenu { max-height: 0; overflow: hidden; transition: max-height 0.3s ease-out; } .rotate-180 { transform: rotate(180deg); }`;

    document.head.appendChild(style);



    // Memastikan fungsi toggleMenu hanya didefinisikan sekali

    if (typeof window.toggleMenu !== 'function') {

        window.toggleMenu = function(menuId) {

            const menu = document.getElementById(menuId);

            const arrow = document.getElementById('arrow-' + menuId);

            

            if (menu) {

                // Buka atau tutup menu dengan mengubah max-height

                if (menu.style.maxHeight) {

                    menu.style.maxHeight = null;

                } else {

                    menu.style.maxHeight = menu.scrollHeight + "px";

                }

            }

            

            // Putar ikon panah

            if (arrow) {

                arrow.classList.toggle('rotate-180');

            }

        }

    }

</script>



{{-- 2. Script untuk mengambil jumlah notifikasi (badge) --}}

<script>

    document.addEventListener('DOMContentLoaded', function() {

        const badges = {

            persetujuan: document.getElementById('persetujuan-badge'),

            pesanan: document.getElementById('pesanan-badge'),

            spx: document.getElementById('spx-badge'),

            riwayatScan: document.getElementById('riwayat-scan-badge'),

            saldoRequests: document.getElementById('saldo-requests-badge'), 

            parentPengguna: document.getElementById('menu-pengguna-badge'),

            parentMarketplace: document.getElementById('menu-marketplace-badge'),

            parentPesanan: document.getElementById('menu-pesanan-badge'),

            parentKeuangan: document.getElementById('menu-keuangan-badge'), 

        };



        async function fetchCount(url, badgeElement) {

            // Jika elemen badge tidak ada, hentikan fungsi

            if (!badgeElement) return 0;

            try {

                const response = await fetch(url);

                if (!response.ok) { throw new Error(`Network response was not ok for ${url}`); }

                const data = await response.json();

                updateBadge(badgeElement, data.count);

                return data.count;

            } catch (error) {

                console.error(`Gagal mengambil notifikasi dari ${url}:`, error);

                updateBadge(badgeElement, 0);

                return 0;

            }

        }



        function updateBadge(badgeElement, count) {

            if (badgeElement) {

                badgeElement.textContent = count;

                // Sembunyikan badge jika jumlahnya 0

                if (count > 0) {

                    badgeElement.classList.remove('hidden');

                } else {

                    badgeElement.classList.add('hidden');

                }

            }

        }



        async function fetchAllCounts() {

            const [persetujuanCount, pesananCount, spxCount, riwayatScanCount, saldoRequestsCount] = await Promise.all([

                fetchCount("{{ route('admin.notifications.registrations.count') }}", badges.persetujuan),

                fetchCount("{{ route('admin.notifications.pesanan.count') }}", badges.pesanan),

                fetchCount("{{ route('admin.notifications.spx-scans.count') }}", badges.spx),

                fetchCount("{{ route('admin.notifications.riwayat-scan.count') }}", badges.riwayatScan),

                fetchCount("{{ route('admin.notifications.saldo-requests.count') }}", badges.saldoRequests) 

            ]);



            // Update badge pada menu induk

            updateBadge(badges.parentPengguna, persetujuanCount);

            updateBadge(badges.parentMarketplace, spxCount);

            updateBadge(badges.parentPesanan, pesananCount + riwayatScanCount);

            updateBadge(badges.parentKeuangan, saldoRequestsCount);

        }



        // Jalankan saat halaman dimuat dan setiap 15 detik

        fetchAllCounts();

        setInterval(fetchAllCounts, 15000);

    });

</script>

@endpush

