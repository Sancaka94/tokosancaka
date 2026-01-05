<aside class="fixed inset-y-0 left-0 z-30 w-64 bg-slate-800 text-white transition-transform duration-300 transform lg:translate-x-0 lg:static lg:inset-0 shadow-2xl flex flex-col h-full" 
       :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'">
    
    <div class="flex items-center justify-center h-16 bg-slate-900 border-b border-slate-700/50 shadow-sm flex-shrink-0">
    <a href="{{ route('dashboard') }}" class="flex items-center gap-3">
        <img src="https://tokosancaka.com/storage/uploads/sancaka.png" class="h-8 w-8 object-contain" alt="Logo">
        
        <span class="text-xl font-bold tracking-wide text-gray-100">
    SANCAKA
    <span class="text-blue-500 italic">POS</span>
        </span>

    </a>
    </div>

    <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto custom-scrollbar">
        
        <a href="{{ route('dashboard') }}" 
           class="flex items-center gap-3 px-3 py-2.5 rounded-md text-sm font-medium transition-colors duration-200
           {{ request()->routeIs('dashboard') 
             ? 'bg-blue-600 text-white shadow-md' 
             : 'text-slate-300 hover:bg-slate-700 hover:text-white' }}">
            <i class="fas fa-tachometer-alt w-5 text-center {{ request()->routeIs('dashboard') ? 'text-white' : 'text-slate-400 group-hover:text-white' }}"></i>
            <span>Dashboard</span>
        </a>

        <div class="mt-6 mb-2 px-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">
            Transaksi
        </div>
        
        <a href="{{ route('orders.create') }}" 
           class="flex items-center gap-3 px-3 py-2.5 rounded-md text-sm font-medium transition-colors duration-200
           {{ request()->routeIs('orders.create') 
             ? 'bg-emerald-600 text-white shadow-md' 
             : 'text-slate-300 hover:bg-slate-700 hover:text-white' }}">
            <i class="fas fa-cash-register w-5 text-center {{ request()->routeIs('orders.create') ? 'text-white' : 'text-emerald-500' }}"></i>
            <span>Buat Pesanan Baru</span>
        </a>

        <a href="{{ route('orders.index') }}" 
           class="flex items-center gap-3 px-3 py-2.5 rounded-md text-sm font-medium transition-colors duration-200
           {{ request()->routeIs('orders.index') || request()->routeIs('orders.show') 
             ? 'bg-blue-600 text-white shadow-md' 
             : 'text-slate-300 hover:bg-slate-700 hover:text-white' }}">
            <i class="fas fa-shopping-cart w-5 text-center {{ (request()->routeIs('orders.index') || request()->routeIs('orders.show')) ? 'text-white' : 'text-blue-400' }}"></i>
            <span>Riwayat Pesanan</span>
        </a>

        <a href="{{ route('reports.index') }}" 
           class="flex items-center gap-3 px-3 py-2.5 rounded-md text-sm font-medium transition-colors duration-200
           {{ request()->routeIs('reports.index') 
             ? 'bg-blue-600 text-white shadow-md' 
             : 'text-slate-300 hover:bg-slate-700 hover:text-white' }}">
            <i class="fas fa-file-invoice w-5 text-center {{ request()->routeIs('reports.index') ? 'text-white' : 'text-slate-400' }}"></i>
            <span>Laporan Penjualan</span>
        </a>

        <div class="mt-6 mb-2 px-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">
            Marketing & Relasi
        </div>

        <a href="{{ route('affiliate.create') }}" 
           class="flex items-center gap-3 px-3 py-2.5 rounded-md text-sm font-medium transition-colors duration-200
           {{ request()->routeIs('affiliate.create') 
             ? 'bg-blue-600 text-white shadow-md' 
             : 'text-slate-300 hover:bg-slate-700 hover:text-white' }}">
            <i class="fas fa-user-plus w-5 text-center {{ request()->routeIs('affiliate.create') ? 'text-white' : 'text-teal-400' }}"></i>
            <span>Registrasi Partner</span>
        </a>

        <a href="{{ route('affiliate.index') }}" 
           class="flex items-center gap-3 px-3 py-2.5 rounded-md text-sm font-medium transition-colors duration-200
           {{ request()->routeIs('affiliate.index') 
             ? 'bg-blue-600 text-white shadow-md' 
             : 'text-slate-300 hover:bg-slate-700 hover:text-white' }}">
            <i class="fas fa-users w-5 text-center {{ request()->routeIs('affiliate.index') ? 'text-white' : 'text-indigo-400' }}"></i>
            <span>Partner Afiliasi</span>
        </a>

        <a href="{{ route('coupons.index') }}" 
           class="flex items-center gap-3 px-3 py-2.5 rounded-md text-sm font-medium transition-colors duration-200
           {{ request()->routeIs('coupons.index') 
             ? 'bg-blue-600 text-white shadow-md' 
             : 'text-slate-300 hover:bg-slate-700 hover:text-white' }}">
            <i class="fas fa-ticket-alt w-5 text-center {{ request()->routeIs('coupons.index') ? 'text-white' : 'text-amber-400' }}"></i>
            <span>Manajemen Kupon</span>
        </a>

        <div class="mt-6 mb-2 px-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">
            Data Master
        </div>

        <a href="{{ route('products.index') }}" 
           class="flex items-center gap-3 px-3 py-2.5 rounded-md text-sm font-medium transition-colors duration-200
           {{ request()->routeIs('products.index') 
             ? 'bg-blue-600 text-white shadow-md' 
             : 'text-slate-300 hover:bg-slate-700 hover:text-white' }}">
            <i class="fas fa-box-open w-5 text-center {{ request()->routeIs('products.index') ? 'text-white' : 'text-slate-400' }}"></i>
            <span>Data Produk</span>
        </a>

        <div class="mt-6 mb-2 px-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">
            Pengaturan
        </div>

        <div x-data="{ open: {{ request()->routeIs('profile.*') ? 'true' : 'false' }} }">
            
            <button @click="open = !open" type="button" 
                class="w-full flex items-center justify-between gap-3 px-3 py-2.5 rounded-md text-sm font-medium transition-colors duration-200
                {{ request()->routeIs('profile.*') 
                    ? 'text-white bg-slate-700' 
                    : 'text-slate-300 hover:bg-slate-700 hover:text-white' }}">
                
                <div class="flex items-center gap-3">
                    <i class="fas fa-cog w-5 text-center {{ request()->routeIs('profile.*') ? 'text-white' : 'text-slate-400' }}"></i>
                    <span>Akun & Profile</span>
                </div>
                
                <i class="fas fa-chevron-down text-xs transition-transform duration-200" 
                   :class="open ? 'rotate-180' : ''"></i>
            </button>

            <div x-show="open" 
                 x-collapse
                 x-cloak
                 class="mt-1 space-y-1 bg-slate-900/50 rounded-md overflow-hidden">

                <a href="{{ route('profile.index') }}" 
                   class="flex items-center gap-3 pl-11 pr-3 py-2 text-sm transition-colors duration-200
                   {{ request()->routeIs('profile.index') 
                        ? 'text-blue-400 font-medium' 
                        : 'text-slate-400 hover:text-white hover:bg-slate-700/50' }}">
                    <span>Data Profile</span>
                </a>

                <a href="{{ route('profile.edit') }}" 
                   class="flex items-center gap-3 pl-11 pr-3 py-2 text-sm transition-colors duration-200
                   {{ request()->routeIs('profile.edit') 
                        ? 'text-blue-400 font-medium' 
                        : 'text-slate-400 hover:text-white hover:bg-slate-700/50' }}">
                    <span>Edit Profile</span>
                </a>
            </div>
        </div>

    </nav>

    <div class="p-4 border-t border-slate-700 bg-slate-900/50 flex-shrink-0">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="w-full flex items-center justify-center gap-2 px-4 py-2 text-sm font-medium text-red-400 bg-red-400/10 rounded-md hover:bg-red-500 hover:text-white transition-all duration-200 group">
                <i class="fas fa-sign-out-alt group-hover:animate-pulse"></i>
                <span>Logout</span>
            </button>
        </form>
    </div>

</aside>