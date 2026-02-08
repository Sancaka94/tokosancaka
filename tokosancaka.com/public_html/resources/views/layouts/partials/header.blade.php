{{--
    File: resources/views/layouts/partials/header.blade.php
    Deskripsi: Header admin panel dengan dropdown notifikasi dinamis dan Toggle API Mode Robust.

    MODIFIKASI:
    1. Desain Header di-set ZOOM 80%.
    2. Lebar (Width) di-set 125% agar tetap full-screen saat di-zoom out.
--}}
<style>
    [x-cloak] { display: none !important; }
    /* Toggle Switch Custom Style */
    .toggle-checkbox:checked {
        /* right: 0;  <-- HAPUS BARIS INI agar tombol tidak 'bablas' ke kanan */
        border-color: #22c55e; /* Red-500 */
    }
    .toggle-checkbox:checked + .toggle-label {
        background-color: #22c55e; /* Red-500 */
    }
    .toggle-checkbox:not(:checked) + .toggle-label {
        background-color: #ef4444; /* Indigo-500 */
    }
</style>

{{--
    PERUBAHAN DISINI:
    style="zoom: 80%; width: 125%;"
    - zoom: 80% untuk mengecilkan ukuran.
    - width: 125% untuk memastikan background tetap full layar (100/0.8 = 125).
--}}
<header class="flex justify-between items-center p-4 bg-gray-700 border-b shadow-sm sticky top-0 z-40"
        style="zoom: 80%; width: 100%;">

    <div class="flex items-center">
        {{-- Tombol toggle sidebar --}}
        <button type="button" 
        @click="sidebarOpen = !sidebarOpen" 
        class="p-2 rounded-md text-white hover:bg-gray-600 lg:hidden focus:outline-none">
    <span class="sr-only">Toggle sidebar</span>
    <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
    </svg>
        </button>

        {{-- Judul halaman --}}
        <h1 class="ml-3 text-lg font-semibold text-white">
            @yield('page-title', 'Dashboard')
        </h1>
    </div>

    <div class="ml-auto flex items-center space-x-2 sm:space-x-4 mr-6">

        {{-- =================================================================== --}}
        {{-- TOGGLE API MODE (ALPINE JS VERSION - ROBUST)                        --}}
        {{-- =================================================================== --}}
        @php
            // Cek status saat ini langsung dari DB untuk initial state alpine
            $currentMode = \App\Models\Api::getValue('KIRIMINAJA_MODE', 'global', 'staging');
            $isProd = ($currentMode === 'production');
        @endphp

        <div x-data="{
                isProd: {{ $isProd ? 'true' : 'false' }},
                isLoading: false,
                async toggleMode() {
                    this.isLoading = true;
                    // Tentukan target mode selanjutnya
                    const targetMode = this.isProd ? 'staging' : 'production';

                    try {
                        const response = await fetch('{{ route('admin.settings.api.toggle') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest', // Wajib untuk AJAX Laravel
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({ mode: targetMode })
                        });

                        // Cek Tipe Konten Response
                        const contentType = response.headers.get('content-type');

                        // SKENARIO 1: Response JSON (Ideal)
                        if (contentType && contentType.includes('application/json')) {
                            const result = await response.json();
                            if (response.ok) {
                                this.isProd = !this.isProd;
                                window.location.reload();
                            } else {
                                throw new Error(result.message || 'Terjadi kesalahan.');
                            }
                        }
                        // SKENARIO 2: Response HTML tapi Sukses 200 OK (Fallback return back())
                        else if (response.ok) {
                            console.log('Response HTML (Redirect sukses), reloading page...');
                            window.location.reload();
                        }
                        // SKENARIO 3: Error Server (500, 404, dll)
                        else {
                            const text = await response.text();
                            console.error('Server Error Details:', text);
                            throw new Error(`Server Error (${response.status}). Cek Console Browser.`);
                        }

                    } catch (e) {
                        console.error(e);
                        alert('Gagal: ' + e.message);
                        // Kembalikan posisi tombol jika gagal
                        this.$refs.apiToggle.checked = this.isProd;
                    } finally {
                        this.isLoading = false;
                    }
                }
             }"
             class="hidden md:flex items-center mr-4 bg-white/10 rounded-lg p-1 border border-white/20">

            <span class="text-[10px] font-bold text-white mr-2 ml-2 uppercase" x-text="isProd ? 'MODE AKTIF' : 'NON AKTIF'"></span>

            <div class="relative inline-block w-10 align-middle select-none transition duration-200 ease-in">
                <input type="checkbox" x-ref="apiToggle"
                       class="toggle-checkbox absolute block w-5 h-5 rounded-full bg-white border-4 border-gray-300 appearance-none cursor-pointer transition-transform duration-300"
                       :class="isProd ? 'translate-x-5 border-red-500' : 'translate-x-0 border-red-500'"
                       @click.prevent="toggleMode()"
                       :checked="isProd"/>
                <label class="toggle-label block overflow-hidden h-5 rounded-full cursor-pointer bg-gray-300 shadow-inner"></label>
            </div>

            {{-- Loading Spinner Kecil --}}
            <div x-show="isLoading" class="ml-2" x-cloak>
                <svg class="animate-spin h-3 w-3 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </div>
        </div>
        {{-- =================================================================== --}}

        {{-- Saldo + tombol top up --}}
        <div class="hidden md:flex items-center">
            <span class="text-sm font-medium text-white"><strong>Saldo:</strong></span>
            <span class="ml-2 text-sm font-semibold bg-green-500 text-white py-1 px-3 rounded-full border:white">
                <strong>Rp {{ number_format(Auth::user()->saldo ?? 0, 0, ',', '.') }}</strong>
            </span>

            <a href="{{ route('admin.saldo.requests.index') }}"
                class="ml-2 inline-flex items-center gap-x-1.5 px-3 py-1.5
                       bg-blue-600 hover:bg-blue-700
                       text-white text-sm font-medium rounded-md
                       focus:outline-none">
                <i class="fas fa-money-bill-wave text-white text-base"></i>
                <strong>Top Up</strong>
            </a>

            <a href="https://tokosancaka.com/admin/products"
                class="p-2 rounded-full text-gray-500 hover:bg-red-700 focus:outline-none relative">
                <span class="sr-only">Lihat Produk</span>
                <i class="fas fa-store text-lg text-white"></i>
            </a>
        </div>

        <div x-data="{ open: false }" class="relative">
            <button @click="open = !open; if(open) loadInitialNotifications();"
                    class="p-2 rounded-full text-gray-500 hover:bg-red-700 focus:outline-none relative">
                    <span class="sr-only">Lihat notifikasi</span>
                    <i class="fas fa-bell text-lg text-white"></i>

                {{-- Badge notifikasi dinamis --}}
                <span id="notification-count-badge"
                    class="absolute top-1 right-1 flex items-center justify-center text-[10px] text-white bg-red-600 rounded-full w-4 h-4"
                    style="display: none;">
                    0
                </span>
            </button>

            {{-- Dropdown body --}}
            <div x-show="open" @click.away="open = false" x-cloak
                x-transition:enter="transition ease-out duration-100"
                x-transition:enter-start="transform opacity-0 scale-95"
                x-transition:enter-end="transform opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-75"
                x-transition:leave-start="transform opacity-100 scale-100"
                x-transition:leave-end="transform opacity-0 scale-95"
                class="origin-top-right absolute right-0 mt-2 w-80 sm:w-96 rounded-xl shadow-lg bg-white ring-1 ring-black ring-opacity-5 focus:outline-none z-50">

                <div class="py-1">
                    <div class="px-4 py-2 text-sm font-semibold text-gray-900 border-b">
                        Notifikasi
                    </div>

                    <div id="notification-scroll-area" class="max-h-96 overflow-y-auto">
                        <table class="min-w-full divide-y divide-gray-100 table-fixed">
                            <tbody id="notification-list-body" class="divide-y divide-gray-100">
                                {{-- JavaScript akan mengisi baris (<tr>) di sini --}}
                            </tbody>
                            <tbody id="notification-empty-state" style="display: none;">
                                <tr>
                                    <td class="px-4 py-10 text-sm text-gray-500 text-center">
                                        Tidak ada notifikasi baru.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <a href="{{ route('admin.notifications.index') }}"
                        class="block text-center px-4 py-2 text-sm text-indigo-600 hover:bg-gray-50 rounded-b-xl border-t">
                        Lihat semua notifikasi
                    </a>
                </div>
            </div>
        </div>

        {{-- Dropdown Profil --}}
        <div x-data="{ open: false }" class="relative">
            <button @click="open = !open"
                class="flex items-center text-sm rounded-full focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                <span class="sr-only">Buka menu pengguna</span>
{{-- PERBAIKAN: Menambahkan operator null safe (?->) dan fallback default --}}
                <img class="h-8 w-8 rounded-full object-cover"
                    src="{{ Auth::user()?->store_logo_path
                    ? asset('public/storage/' . Auth::user()->store_logo_path)
                    : 'https://ui-avatars.com/api/?name=' . urlencode(Auth::user()?->nama_lengkap ?? 'User') . '&color=7F9CF5&background=EBF4FF' }}">            </button>

            <div x-show="open" @click.away="open = false" x-cloak
                x-transition:enter="transition ease-out duration-100"
                x-transition:enter-start="transform opacity-0 scale-95"
                x-transition:enter-end="transform opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-75"
                x-transition:leave-start="transform opacity-100 scale-100"
                x-transition:leave-end="transform opacity-0 scale-95"
                class="origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg py-1 bg-white ring-1 ring-black ring-opacity-5 focus:outline-none z-50">

                <div class="px-4 py-3 border-b">
                    <p class="text-sm text-gray-600">Masuk sebagai</p>
                    <p class="text-sm font-medium text-gray-900 truncate">
                        {{-- PERBAIKAN: Gunakan fallback 'User' jika nama kosong --}}
                        {{ Auth::user()?->nama_lengkap ?? 'User' }}
                    </p>
                </div>

                <a href="{{ route('admin.settings.index') }}"
                    class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                    Profil & Pengaturan
                </a>

                <div class="border-t border-gray-100 my-1"></div>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                        class="w-full text-left block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                        Keluar
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>
