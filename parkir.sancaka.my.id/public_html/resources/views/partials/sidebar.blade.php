<button id="toggleSidebar" class="p-2 m-4 text-white bg-blue-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-400 z-50 relative">
    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path>
    </svg>
</button>

<div id="sidebarOverlay" class="fixed inset-0 z-40 hidden bg-black bg-opacity-50 transition-opacity"></div>

<aside class="fixed inset-y-0 left-0 z-50 flex flex-col flex-shrink-0 w-64 text-white transition-transform duration-300 ease-in-out transform -translate-x-full bg-blue-600 shadow-lg" id="sidebar">
    <div class="flex items-center justify-center h-16 gap-2 px-4 border-b border-blue-500">
        <img src="https://tokosancaka.com/storage/uploads/logo.jpeg" alt="Logo" class="w-8 h-8 p-1 bg-white rounded-full">
        <div class="flex flex-col">
            <span class="text-lg font-bold leading-tight tracking-wide">
                @if(auth()->user()->role == 'superadmin')
                    SUPER ADMIN
                @elseif(auth()->user()->role == 'admin')
                    ADMIN TENANT
                @else
                    OPERATOR
                @endif
            </span>
        </div>
    </div>

    <nav class="flex-1 py-4 overflow-y-auto">
        <ul class="space-y-1">
            <li>
                <a href="{{ route('dashboard') }}" class="flex items-center gap-2 px-4 py-2 hover:bg-blue-700 transition-colors {{ request()->routeIs('dashboard') ? 'bg-blue-800 border-l-4 border-white' : '' }}">
                    <span>📊</span> Dashboard
                </a>
            </li>

            <li class="px-4 py-2 mt-4 text-xs font-semibold tracking-wider text-blue-200 uppercase">Operasional</li>
            <li>
                <a href="{{ route('transactions.index') }}" class="flex items-center gap-2 px-4 py-2 hover:bg-blue-700 transition-colors {{ request()->routeIs('transactions.*') ? 'bg-blue-800 border-l-4 border-white' : '' }}">
                    <span>🚗</span> Transaksi Kendaraan
                </a>
            </li>

            <li>
                <a href="{{ route('transactions.createManual') }}" class="flex items-center gap-2 px-4 py-2 hover:bg-blue-700 transition-colors {{ request()->routeIs('transactions.createManual') ? 'bg-blue-800 border-l-4 border-white' : '' }}">
                    <span>📝</span> Catat Manual
                </a>
            </li>

            <li>
                <a href="{{ route('financial.index') }}" class="flex items-center gap-2 px-4 py-2 hover:bg-blue-700 transition-colors {{ request()->routeIs('financial.*') ? 'bg-blue-800 border-l-4 border-white' : '' }}">
                    <span>📒</span> Buku Kas Manual
                </a>
            </li>

            @if(in_array(auth()->user()->role, ['superadmin', 'admin']))
                <li class="px-4 py-2 mt-4 text-xs font-semibold tracking-wider text-blue-200 uppercase">Master Data</li>
                <li>
                    <a href="{{ route('employees.index') }}" class="flex items-center gap-2 px-4 py-2 hover:bg-blue-700 transition-colors {{ request()->routeIs('employees.*') ? 'bg-blue-800 border-l-4 border-white' : '' }}">
                        <span>👥</span> Data Pegawai
                    </a>
                </li>

                <li class="px-4 py-2 mt-4 text-xs font-semibold tracking-wider text-blue-200 uppercase">Laporan Keuangan</li>
                <li>
                    <a href="{{ route('laporan.harian') }}" class="flex items-center gap-2 px-4 py-2 hover:bg-blue-700 transition-colors">
                        <span>📄</span> Laporan Harian
                    </a>
                </li>
                <li>
                    <a href="{{ route('laporan.bulanan') }}" class="flex items-center gap-2 px-4 py-2 hover:bg-blue-700 transition-colors">
                        <span>📅</span> Laporan Bulanan
                    </a>
                </li>
                <li>
                    <a href="{{ route('laporan.triwulan') }}" class="flex items-center gap-2 px-4 py-2 hover:bg-blue-700 transition-colors">
                        <span>📈</span> Laporan 3 Bulan
                    </a>
                </li>

                <li class="px-4 py-2 mt-4 text-xs font-semibold tracking-wider text-blue-200 uppercase">Pengaturan</li>
                <li>
                    <a href="#" class="flex items-center gap-2 px-4 py-2 hover:bg-blue-700 transition-colors">
                        <span>🏢</span> Profil Perusahaan
                    </a>
                </li>
            @endif
        </ul>
    </nav>
</aside>
