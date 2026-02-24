<aside class="w-64 bg-blue-600 text-white flex flex-col shadow-lg flex-shrink-0 transition-all duration-300" id="sidebar">
    <div class="h-16 flex items-center justify-center border-b border-blue-500 px-4 gap-2">
        <img src="https://tokosancaka.com/storage/uploads/logo.jpeg" alt="Logo" class="h-8 w-8 bg-white rounded-full p-1">
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

    <nav class="flex-1 overflow-y-auto py-4">
        <ul class="space-y-1">
            <li>
                <a href="{{ route('dashboard') }}" class="flex items-center gap-2 px-4 py-2 hover:bg-blue-700 transition-colors {{ request()->routeIs('dashboard') ? 'bg-blue-800 border-l-4 border-white' : '' }}">
                    <span>📊</span> Dashboard
                </a>
            </li>

            <li class="px-4 py-2 mt-4 text-xs font-semibold text-blue-200 uppercase tracking-wider">Operasional</li>
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

            @if(in_array(auth()->user()->role, ['superadmin', 'admin']))
                <li class="px-4 py-2 mt-4 text-xs font-semibold text-blue-200 uppercase tracking-wider">Master Data</li>
                <li>
                    <a href="{{ route('employees.index') }}" class="flex items-center gap-2 px-4 py-2 hover:bg-blue-700 transition-colors {{ request()->routeIs('employees.*') ? 'bg-blue-800 border-l-4 border-white' : '' }}">
                        <span>👥</span> Data Pegawai
                    </a>
                </li>

                <li class="px-4 py-2 mt-4 text-xs font-semibold text-blue-200 uppercase tracking-wider">Laporan Keuangan</li>
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

                <li class="px-4 py-2 mt-4 text-xs font-semibold text-blue-200 uppercase tracking-wider">Pengaturan</li>
                <li>
                    <a href="#" class="flex items-center gap-2 px-4 py-2 hover:bg-blue-700 transition-colors">
                        <span>🏢</span> Profil Perusahaan
                    </a>
                </li>
            @endif
        </ul>
    </nav>
</aside>
