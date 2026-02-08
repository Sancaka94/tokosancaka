<header class="h-20 bg-white/80 backdrop-blur-md border-b border-slate-200 flex items-center justify-between px-4 lg:px-8 sticky top-0 z-40"
        x-data="{ userOpen: false, topupOpen: false, topupAmount: '' }">

    {{-- ================================================================= --}}
    {{-- [LOGIC PENYELAMAT] AMBIL SUBDOMAIN MANUAL UNTUK HEADER            --}}
    {{-- ================================================================= --}}
    @php
        $host = request()->getHost();
        $parts = explode('.', $host);
        $currentSubdomain = $parts[0] ?? 'admin';
        $params = ['subdomain' => $currentSubdomain];
    @endphp

    <div class="flex items-center gap-4">
        {{-- Tombol Toggle Sidebar (Mobile) --}}
        <button @click="sidebarOpen = !sidebarOpen" class="text-slate-500 hover:text-blue-600 focus:outline-none lg:hidden p-2.5 rounded-xl hover:bg-slate-50 transition-all border border-transparent hover:border-slate-100">
            <i class="fas fa-bars-staggered text-xl"></i>
        </button>

        {{-- Search Bar --}}
        <div class="hidden md:flex relative group">
            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400 group-focus-within:text-blue-600 transition-colors">
                <i class="fas fa-search text-sm"></i>
            </span>
            <input type="text" placeholder="Cari data..." class="bg-slate-100/50 border-none rounded-2xl py-2.5 pl-10 pr-4 text-sm w-64 focus:ring-4 focus:ring-blue-600/5 focus:bg-white transition-all outline-none text-slate-600 font-medium">
        </div>
    </div>

    <div class="flex items-center gap-3 sm:gap-5">

        {{-- ================================================================= --}}
        {{-- [WIDGET SALDO + TOMBOL TOPUP]                                     --}}
        {{-- ================================================================= --}}
        @auth
            <div class="hidden sm:flex items-center gap-2">
                {{-- Tampilan Saldo --}}
                <div class="flex items-center gap-3 px-3 py-1.5 bg-emerald-50/50 border border-emerald-100 rounded-2xl cursor-default group">
                    <div class="h-8 w-8 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center shadow-sm">
                        <i class="fas fa-wallet text-xs"></i>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider leading-none mb-0.5">Saldo Aktif</span>
                        <span class="text-sm font-black text-slate-700 leading-none group-hover:text-emerald-700 transition-colors">
                            Rp {{ number_format(Auth::user()->saldo ?? 0, 0, ',', '.') }}
                        </span>
                    </div>
                </div>

                {{-- [BARU] Tombol Topup (+) --}}
                <button @click="topupOpen = true"
                        class="h-[46px] w-[46px] rounded-2xl bg-blue-600 text-white flex items-center justify-center hover:bg-blue-700 hover:shadow-lg hover:shadow-blue-200 transition-all border border-blue-500"
                        title="Top Up Saldo via DOKU">
                    <i class="fas fa-plus text-sm"></i>
                </button>
            </div>
        @endauth
        {{-- ================================================================= --}}

        {{-- Notifikasi --}}
        <button class="relative p-2.5 text-slate-400 hover:text-blue-600 hover:bg-blue-50 rounded-2xl transition-all group border border-transparent hover:border-blue-100">
            <i class="fas fa-bell text-lg group-hover:shake"></i>
            <span class="absolute top-2.5 right-2.5 h-2.5 w-2.5 rounded-full bg-red-600 ring-2 ring-white"></span>
        </button>

        <div class="h-8 w-px bg-slate-200 hidden sm:block"></div>

        {{-- User Dropdown --}}
        <div class="relative">
            <button @click="userOpen = !userOpen"
                    @click.outside="userOpen = false"
                    class="flex items-center gap-3 p-1 rounded-2xl hover:bg-slate-50 transition-all focus:outline-none border border-transparent hover:border-slate-100 group">

                <div class="text-right hidden md:block leading-tight px-1">
                    @auth
                        <p class="text-sm font-bold text-slate-800 group-hover:text-blue-600 transition-colors truncate max-w-[150px]">
                            {{ Auth::user()->name }}
                        </p>
                        <p class="text-[10px] font-bold text-emerald-600 uppercase tracking-widest mt-0.5">
                            {{ str_replace('_', ' ', Auth::user()->role ?? 'Administrator') }}
                        </p>
                    @else
                        <p class="text-sm font-bold text-slate-800">Tamu</p>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">GUEST</p>
                    @endauth
                </div>

                <div class="h-10 w-10 rounded-2xl bg-gradient-to-tr from-blue-600 to-blue-400 p-0.5 shadow-lg shadow-blue-100 group-hover:rotate-3 transition-transform">
                    <div class="h-full w-full rounded-[14px] bg-white overflow-hidden p-0.5">
                        @auth
                            <img class="h-full w-full object-cover rounded-[12px]"
                                 src="https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name) }}&background=f1f5f9&color=2563eb&size=128&bold=true"
                                 alt="{{ Auth::user()->name }}">
                        @else
                            <img class="h-full w-full object-cover rounded-[12px]"
                                 src="https://ui-avatars.com/api/?name=Guest&background=f1f5f9&color=64748b&size=128&bold=true"
                                 alt="Guest">
                        @endauth
                    </div>
                </div>
            </button>

            {{-- Dropdown Menu --}}
            <div x-show="userOpen"
                 class="absolute right-0 top-full mt-3 w-64 bg-white rounded-2xl shadow-2xl shadow-slate-200/60 border border-slate-100 py-2 z-50 overflow-hidden origin-top-right"
                 style="display: none;">

                 @auth
                    <div class="px-5 py-4 bg-slate-50/50 border-b border-slate-100 mb-2">
                        <p class="text-sm font-black text-slate-800 truncate">{{ Auth::user()->name }}</p>

                        {{-- Mobile Balance View --}}
                        <div class="mt-2 sm:hidden flex items-center justify-between text-emerald-600 bg-emerald-50 px-3 py-2 rounded-xl">
                            <div class="flex items-center gap-2">
                                <i class="fas fa-wallet text-xs"></i>
                                <span class="text-xs font-bold">Rp {{ number_format(Auth::user()->saldo ?? 0, 0, ',', '.') }}</span>
                            </div>
                            <button @click="topupOpen = true; userOpen = false" class="text-xs bg-emerald-600 text-white px-2 py-0.5 rounded shadow hover:bg-emerald-700">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                    </div>

                    {{-- Menu Items --}}
                    <div class="px-2 space-y-1">
                        <a href="{{ route('profile.index', $params) }}" class="flex items-center gap-3 px-3 py-2.5 text-sm font-semibold text-slate-600 rounded-xl hover:bg-blue-50 hover:text-blue-600 transition-all">
                            <i class="fas fa-user-circle w-5 text-center text-slate-400"></i> Profile Saya
                        </a>
                        <a href="{{ route('profile.edit', $params) }}" class="flex items-center gap-3 px-3 py-2.5 text-sm font-semibold text-slate-600 rounded-xl hover:bg-blue-50 hover:text-blue-600 transition-all">
                            <i class="fas fa-user-gear w-5 text-center text-slate-400"></i> Pengaturan Akun
                        </a>
                    </div>
                    <div class="h-px bg-slate-100 my-2 mx-4"></div>
                    <div class="px-2">
                        <form method="POST" action="{{ route('logout', $params) }}">
                            @csrf
                            <button type="submit" class="w-full flex items-center gap-3 px-3 py-2.5 text-sm font-bold text-red-600 rounded-xl hover:bg-red-50 transition-all">
                                <i class="fas fa-arrow-right-from-bracket w-5 text-center"></i> Logout Sesi
                            </button>
                        </form>
                    </div>
                 @else
                    <div class="p-3">
                        <a href="{{ route('login', $params) }}" class="flex items-center justify-center gap-3 px-4 py-3 text-sm font-bold text-white bg-blue-600 rounded-xl hover:bg-blue-700 transition-all shadow-lg shadow-blue-200">
                            <i class="fas fa-sign-in-alt"></i> Login Sekarang
                        </a>
                    </div>
                 @endauth
            </div>
        </div>
    </div>

   {{-- ================================================================= --}}
    {{-- [MODAL TOPUP] - GUNAKAN X-TELEPORT AGAR KELUAR DARI HEADER        --}}
    {{-- ================================================================= --}}

    {{-- Bungkus dengan template x-teleport="body" --}}
    <template x-teleport="body">

        <div x-show="topupOpen"
             class="fixed inset-0 z-[999] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm"
             style="display: none;"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0">

            {{-- Tambahkan @click.outside di sini untuk menutup modal --}}
            <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md overflow-hidden transform transition-all relative"
                 @click.outside="topupOpen = false"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 scale-95">

                {{-- Modal Header --}}
                <div class="bg-blue-600 px-6 py-4 flex justify-between items-center relative z-10">
                    <div class="text-white">
                        <h3 class="text-lg font-bold">Isi Saldo</h3>
                        <p class="text-blue-100 text-xs">Topup aman & instan via DOKU</p>
                    </div>
                    <button @click="topupOpen = false" class="text-blue-100 hover:text-white bg-blue-500/30 hover:bg-blue-500/50 rounded-xl p-2 transition-colors">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                {{-- Modal Body --}}
                {{-- Gunakan parameter $params yang sudah didefinisikan di PHP atas --}}
                <form action="{{ route('topup.process', ['subdomain' => request()->getHost() == env('APP_URL') ? 'admin' : explode('.', request()->getHost())[0]]) }}" method="POST" class="p-6">
                    @csrf
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Nominal Topup</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-slate-400 font-bold">Rp</span>
                                <input type="number" name="amount" x-model="topupAmount" required min="10000"
                                       class="w-full pl-12 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none font-bold text-slate-800 placeholder:font-normal"
                                       placeholder="Min. 10.000">
                            </div>
                        </div>

                        {{-- Quick Amount --}}
                        <div class="grid grid-cols-3 gap-2">
                            <button type="button" @click="topupAmount = 50000" class="py-2 text-xs font-bold text-slate-600 bg-slate-100 hover:bg-blue-50 hover:text-blue-600 border border-transparent hover:border-blue-200 rounded-lg transition-all">
                                50.000
                            </button>
                            <button type="button" @click="topupAmount = 100000" class="py-2 text-xs font-bold text-slate-600 bg-slate-100 hover:bg-blue-50 hover:text-blue-600 border border-transparent hover:border-blue-200 rounded-lg transition-all">
                                100.000
                            </button>
                            <button type="button" @click="topupAmount = 500000" class="py-2 text-xs font-bold text-slate-600 bg-slate-100 hover:bg-blue-50 hover:text-blue-600 border border-transparent hover:border-blue-200 rounded-lg transition-all">
                                500.000
                            </button>
                        </div>

                        <div class="p-4 bg-blue-50 rounded-xl border border-blue-100 flex gap-3">
                            <div class="shrink-0 text-blue-600 mt-0.5"><i class="fas fa-info-circle"></i></div>
                            <p class="text-xs text-blue-800 leading-relaxed">
                                Anda akan diarahkan ke halaman pembayaran DOKU (QRIS, VA, E-Wallet). Saldo otomatis masuk setelah bayar.
                            </p>
                        </div>
                    </div>

                    <div class="mt-6">
                        <button type="submit" class="w-full py-3.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl shadow-lg shadow-blue-200 transition-all flex justify-center items-center gap-2">
                            <span>Lanjut Pembayaran</span>
                            <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </template>

</header>
