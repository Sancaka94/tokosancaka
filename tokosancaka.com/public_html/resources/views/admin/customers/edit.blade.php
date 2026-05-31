@extends('layouts.admin')

@section('title', 'Edit Data Pelanggan')

@section('content')
<div class="px-4 py-8 md:px-8 max-w-7xl mx-auto bg-gray-50/30 min-h-screen">

    {{-- ================= FLASH MESSAGES ================= --}}
    @if(session('success'))
        <div x-data="{ show: true }" x-show="show" class="flex items-center justify-between p-4 mb-6 text-sm bg-black text-white rounded-lg shadow-md" role="alert">
            <div class="flex items-center gap-3">
                <i class="fa fa-check-circle"></i>
                <span class="font-medium">{{ session('success') }}</span>
            </div>
            <button type="button" @click="show = false" class="text-gray-400 hover:text-white transition-colors">
                <i class="fa fa-times"></i>
            </button>
        </div>
    @endif

    @if($errors->any())
        <div x-data="{ show: true }" x-show="show" class="flex flex-col p-4 mb-6 text-sm border border-red-200 bg-red-50 rounded-lg shadow-sm" role="alert">
            <div class="flex items-center justify-between">
                <div class="flex items-center font-bold text-red-800">
                    <i class="fa fa-exclamation-triangle mr-2"></i> Terdapat kesalahan:
                </div>
                <button type="button" @click="show = false" class="text-red-500 hover:text-red-700">
                    <i class="fa fa-times"></i>
                </button>
            </div>
            <ul class="list-disc list-inside text-red-700 space-y-1 mt-2 ml-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- ================= HEADER PAGE ================= --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-8 pb-5 border-b border-gray-200 gap-4">
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-gray-900 tracking-tight">Pengaturan Pengguna</h1>
            <p class="mt-1.5 text-sm text-gray-500">
                Kelola profil, konfigurasi alamat, dan keamanan untuk <span class="font-semibold text-gray-900">{{ $user->nama_lengkap }}</span>.
            </p>
        </div>
        <a href="{{ route('admin.customers.index') }}" class="inline-flex items-center justify-center px-4 py-2 bg-white border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-black transition-colors">
            <i class="fa fa-arrow-left mr-2 text-xs"></i> Kembali
        </a>
    </div>

    {{-- ================= FORM UTAMA ================= --}}
    <form action="{{ route('admin.customers.update', ['customer' => $user]) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

            {{-- ================= KOLOM KIRI (Profil & Alamat) ================= --}}
            <div class="lg:col-span-2 space-y-8">

                {{-- SECTION: INFORMASI DASAR --}}
                <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 flex items-center bg-gray-50/50">
                        <i class="fa fa-user mr-3 text-gray-400"></i>
                        <h3 class="text-base font-semibold text-gray-900">Informasi Dasar</h3>
                    </div>
                    
                    <div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1.5" for="nama_lengkap">Nama Lengkap *</label>
                            <input type="text" id="nama_lengkap" name="nama_lengkap" value="{{ old('nama_lengkap', $user->nama_lengkap) }}" required 
                                   class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm text-sm focus:outline-none focus:ring-1 focus:ring-black focus:border-black bg-white transition-colors">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5" for="email">Alamat Email *</label>
                            <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required 
                                   class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm text-sm focus:outline-none focus:ring-1 focus:ring-black focus:border-black bg-white transition-colors">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5" for="no_wa">Nomor WhatsApp *</label>
                            <input type="text" id="no_wa" name="no_wa" value="{{ old('no_wa', $user->no_wa) }}" required 
                                   class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm text-sm focus:outline-none focus:ring-1 focus:ring-black focus:border-black bg-white transition-colors">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5" for="store_name">Nama Toko <span class="text-gray-400 font-normal">(Opsional)</span></label>
                            <input type="text" id="store_name" name="store_name" value="{{ old('store_name', $user->store_name) }}" 
                                   class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm text-sm focus:outline-none focus:ring-1 focus:ring-black focus:border-black bg-white transition-colors">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5" for="role">Hak Akses (Role) *</label>
                            <select id="role" name="role" required class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm text-sm focus:outline-none focus:ring-1 focus:ring-black focus:border-black bg-white transition-colors">
                                <option value="Pelanggan" {{ old('role', $user->role) == 'Pelanggan' ? 'selected' : '' }}>Pelanggan</option>
                                <option value="Seller" {{ old('role', $user->role) == 'Seller' ? 'selected' : '' }}>Seller</option>
                                <option value="Admin" {{ old('role', $user->role) == 'Admin' ? 'selected' : '' }}>Admin</option>
                            </select>
                        </div>
                    </div>
                </div>

                {{-- SECTION: ALAMAT PENGGUNA (ALPINE.JS KIRIMINAJA) --}}
                <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden" 
                     x-data="addressFinder('{{ route('admin.api.search.address') }}', '{{ route('admin.api.geocode.address') }}')">
                    
                    <div class="px-6 py-4 border-b border-gray-100 flex items-center bg-gray-50/50">
                        <i class="fa fa-map-pin mr-3 text-gray-400"></i>
                        <h3 class="text-base font-semibold text-gray-900">Alamat & Koordinat</h3>
                    </div>
                    
                    <div class="p-6 space-y-6">
                        {{-- KiriminAja Search --}}
                        <div class="relative">
                            <label class="block text-sm font-medium text-gray-700 mb-1.5" for="address_search">Pencarian Alamat Otomatis</label>
                            <div class="relative rounded-md shadow-sm">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fa fa-search text-gray-400 sm:text-sm"></i>
                                </div>
                                <input type="text" id="address_search"
                                       x-model.debounce.500ms="searchQuery"
                                       @input.debounce.500ms="search"
                                       placeholder="Ketik area, nama jalan, atau kelurahan..."
                                       class="block w-full pl-9 pr-10 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-1 focus:ring-black focus:border-black transition-colors" autocomplete="off">
                                
                                <div x-show="loading" x-cloak class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                    <i class="fa fa-spinner fa-spin text-gray-400"></i>
                                </div>
                            </div>
                            
                            {{-- Dropdown Results --}}
                            <div x-show="results.length > 0" x-cloak @click.away="results = []" class="absolute z-10 w-full mt-1 bg-white border border-gray-200 rounded-md shadow-lg max-h-60 overflow-y-auto">
                                <ul class="divide-y divide-gray-100">
                                    <template x-for="result in results" :key="result.district_id + result.subdistrict_id">
                                        <li @click="selectAddress(result)" class="p-3 hover:bg-gray-50 cursor-pointer text-sm text-gray-800 transition-colors" x-text="result.text"></li>
                                    </template>
                                </ul>
                            </div>
                            <p x-show="message" x-cloak x-text="message" class="text-sm text-gray-600 mt-2"></p>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Provinsi</label>
                                <input type="text" name="province" x-model="fields.province" readonly class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm text-sm bg-gray-50 text-gray-500 cursor-not-allowed">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Kabupaten / Kota</label>
                                <input type="text" name="regency" x-model="fields.city" readonly class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm text-sm bg-gray-50 text-gray-500 cursor-not-allowed">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Kecamatan</label>
                                <input type="text" name="district" x-model="fields.subdistrict" readonly class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm text-sm bg-gray-50 text-gray-500 cursor-not-allowed">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Desa / Kelurahan</label>
                                <input type="text" name="village" x-model="fields.village" readonly class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm text-sm bg-gray-50 text-gray-500 cursor-not-allowed">
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Kode Pos</label>
                                <input type="text" name="postal_code" x-model="fields.zip_code" readonly class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm text-sm bg-gray-50 text-gray-500 cursor-not-allowed">
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Detail Alamat Lengkap</label>
                                <textarea name="address_detail" id="address_detail" rows="3" x-model="fields.address_detail" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm text-sm focus:outline-none focus:ring-1 focus:ring-black focus:border-black transition-colors resize-y"></textarea>
                            </div>
                        </div>

                        {{-- Geocoding Section --}}
                        <div class="p-5 border border-gray-200 rounded-lg bg-gray-50">
                            <div class="flex flex-col sm:flex-row sm:items-end gap-4">
                                <div class="flex-1">
                                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Latitude</label>
                                    <input type="text" name="latitude" x-model="fields.latitude" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm text-sm bg-white focus:outline-none focus:ring-1 focus:ring-black focus:border-black">
                                </div>
                                <div class="flex-1">
                                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Longitude</label>
                                    <input type="text" name="longitude" x-model="fields.longitude" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm text-sm bg-white focus:outline-none focus:ring-1 focus:ring-black focus:border-black">
                                </div>
                                <div>
                                    <button type="button" @click="getCoords" :disabled="geocoding" class="w-full sm:w-auto inline-flex justify-center items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-black transition-colors disabled:opacity-50 h-[38px]">
                                        <span x-show="!geocoding">Tarik Koordinat</span>
                                        <span x-show="geocoding" x-cloak><i class="fa fa-spinner fa-spin mr-2"></i>Mencari...</span>
                                    </button>
                                </div>
                            </div>
                            <p x-show="geocodeMessage" x-cloak x-text="geocodeMessage" class="text-xs text-gray-700 mt-3 font-medium"></p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ================= KOLOM KANAN (Keamanan) ================= --}}
            <div class="space-y-8">
                
                <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 flex items-center bg-gray-50/50">
                        <i class="fa fa-lock mr-3 text-gray-400"></i>
                        <h3 class="text-base font-semibold text-gray-900">Keamanan Akun</h3>
                    </div>

                    <div class="p-6 space-y-6">
                        <p class="text-xs text-gray-500 leading-relaxed">
                            Biarkan kolom di bawah ini kosong jika Anda tidak berencana untuk mengubah kata sandi atau PIN pengguna.
                        </p>

                        <div class="space-y-4">
                            <label class="block text-sm font-medium text-gray-700">Password Baru</label>
                            <div x-data="{ show: false }" class="relative rounded-md shadow-sm">
                                <input :type="show ? 'text' : 'password'" name="password" autocomplete="new-password" placeholder="Minimal 8 karakter" 
                                       class="block w-full px-3 py-2 pr-10 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-1 focus:ring-black focus:border-black transition-colors">
                                <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 px-3 flex items-center text-gray-400 hover:text-gray-600">
                                    <i class="fa" :class="show ? 'fa-eye' : 'fa-eye-slash'"></i>
                                </button>
                            </div>
                        </div>

                        <div class="pt-5 border-t border-gray-100 space-y-4">
                            <div class="flex items-center justify-between">
                                <label class="block text-sm font-medium text-gray-700 mb-0">PIN Transaksi Baru</label>
                                @if(empty($user->pin))
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 border border-yellow-200">
                                        Belum Diatur
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 border border-gray-200">
                                        Terkonfigurasi
                                    </span>
                                @endif
                            </div>
                            <div x-data="{ showPin: false }" class="relative rounded-md shadow-sm">
                                <input :type="showPin ? 'text' : 'password'" name="pin" maxlength="6" pattern="\d{6}" placeholder="6 Digit Angka" 
                                       class="block w-full px-3 py-2 pr-10 border border-gray-300 rounded-md text-sm font-mono tracking-widest focus:outline-none focus:ring-1 focus:ring-black focus:border-black transition-colors">
                                <button type="button" @click="showPin = !showPin" class="absolute inset-y-0 right-0 px-3 flex items-center text-gray-400 hover:text-gray-600">
                                    <i class="fa" :class="showPin ? 'fa-eye' : 'fa-eye-slash'"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                {{-- TOMBOL SUBMIT --}}
                <div class="flex flex-col gap-3">
                    <button type="submit" class="w-full flex justify-center py-2.5 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-black hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-black transition-colors">
                        Simpan Perubahan
                    </button>
                    <a href="{{ route('admin.customers.index') }}" class="w-full flex justify-center py-2.5 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-black transition-colors">
                        Batal
                    </a>
                </div>
            </div>

            {{-- ================= SECTION DATABASE (READ-ONLY) ================= --}}
            <div class="lg:col-span-3">
                <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden mt-4">
                    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
                        <div class="flex items-center">
                            <i class="fa fa-database mr-3 text-gray-400"></i>
                            <h3 class="text-base font-semibold text-gray-900">Informasi Lengkap Database (Read-Only)</h3>
                        </div>
                        <span class="text-xs text-gray-500 font-mono font-medium">ID: #{{ $user->id_pengguna }}</span>
                    </div>
                    
                    <div class="px-6 py-4">
                        <dl class="divide-y divide-gray-100">
                            
                            {{-- Status & Verifikasi --}}
                            <div class="py-4 grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <dt class="text-sm font-medium text-gray-500">Status Akun & Verifikasi</dt>
                                <dd class="text-sm text-gray-900 sm:col-span-2 flex flex-wrap gap-2">
                                    @if($user->status == 'Aktif')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-900 text-white">Aktif</span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 border border-gray-200">{{ $user->status ?? 'Non-Aktif' }}</span>
                                    @endif

                                    @if($user->is_verified == 1)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 border border-gray-200">Terverifikasi</span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 border border-yellow-200">Belum Verifikasi</span>
                                    @endif
                                </dd>
                            </div>

                            {{-- Saldo --}}
                            <div class="py-4 grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <dt class="text-sm font-medium text-gray-500">Saldo Pengguna</dt>
                                <dd class="text-sm text-gray-900 sm:col-span-2 grid grid-cols-1 sm:grid-cols-3 gap-4">
                                    <div>
                                        <span class="block text-xs text-gray-400 uppercase tracking-wider mb-1">Utama</span>
                                        <span class="font-mono font-medium">Rp {{ number_format($user->saldo ?? 0, 0, ',', '.') }}</span>
                                    </div>
                                    <div>
                                        <span class="block text-xs text-gray-400 uppercase tracking-wider mb-1">DANA</span>
                                        <span class="font-mono font-medium">Rp {{ number_format($user->dana_user_balance ?? 0, 0, ',', '.') }}</span>
                                    </div>
                                    <div>
                                        <span class="block text-xs text-gray-400 uppercase tracking-wider mb-1">IAK</span>
                                        <span class="font-mono font-medium">Rp {{ number_format($user->balance_iak ?? 0, 0, ',', '.') }}</span>
                                    </div>
                                </dd>
                            </div>

                            {{-- Bank --}}
                            <div class="py-4 grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <dt class="text-sm font-medium text-gray-500">Informasi Bank</dt>
                                <dd class="text-sm text-gray-900 sm:col-span-2 grid grid-cols-1 sm:grid-cols-3 gap-4">
                                    <div>
                                        <span class="block text-xs text-gray-400 uppercase tracking-wider mb-1">Nama Bank</span>
                                        @if($user->bank_name) <span class="font-medium">{{ $user->bank_name }}</span> @else <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 border border-yellow-200">Data Kosong</span> @endif
                                    </div>
                                    <div>
                                        <span class="block text-xs text-gray-400 uppercase tracking-wider mb-1">Atas Nama</span>
                                        @if($user->bank_account_name) <span class="font-medium">{{ $user->bank_account_name }}</span> @else <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 border border-yellow-200">Data Kosong</span> @endif
                                    </div>
                                    <div>
                                        <span class="block text-xs text-gray-400 uppercase tracking-wider mb-1">No. Rekening</span>
                                        @if($user->bank_account_number) <span class="font-mono font-medium">{{ $user->bank_account_number }}</span> @else <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 border border-yellow-200">Data Kosong</span> @endif
                                    </div>
                                </dd>
                            </div>

                            {{-- Token --}}
                            <div class="py-4 grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <dt class="text-sm font-medium text-gray-500">API Tokens</dt>
                                <dd class="text-sm text-gray-900 sm:col-span-2 space-y-4">
                                    <div>
                                        <span class="block text-xs text-gray-400 uppercase tracking-wider mb-1">Expo Push Token</span>
                                        @if($user->expo_token) <code class="text-xs bg-gray-50 px-2 py-1.5 border border-gray-200 rounded break-all block w-fit">{{ $user->expo_token }}</code> @else <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 border border-yellow-200">Data Kosong</span> @endif
                                    </div>
                                    <div>
                                        <span class="block text-xs text-gray-400 uppercase tracking-wider mb-1">DANA Access Token</span>
                                        @if($user->dana_access_token) <code class="text-xs bg-gray-50 px-2 py-1.5 border border-gray-200 rounded break-all block w-fit">{{ $user->dana_access_token }}</code> @else <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 border border-yellow-200">Data Kosong</span> @endif
                                    </div>
                                    <div>
                                        <span class="block text-xs text-gray-400 uppercase tracking-wider mb-1">Setup Token</span>
                                        @if($user->setup_token) <code class="text-xs bg-gray-50 px-2 py-1.5 border border-gray-200 rounded break-all block w-fit">{{ $user->setup_token }}</code> @else <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 border border-yellow-200">Data Kosong</span> @endif
                                    </div>
                                </dd>
                            </div>

                            {{-- Metadata --}}
                            <div class="py-4 grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <dt class="text-sm font-medium text-gray-500">Metadata</dt>
                                <dd class="text-sm text-gray-900 sm:col-span-2 grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <span class="block text-xs text-gray-400 uppercase tracking-wider mb-1">Dibuat Pada</span>
                                        @if($user->created_at) <span class="font-medium">{{ \Carbon\Carbon::parse($user->created_at)->translatedFormat('d M Y, H:i') }}</span> @else <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 border border-yellow-200">Data Kosong</span> @endif
                                    </div>
                                    <div>
                                        <span class="block text-xs text-gray-400 uppercase tracking-wider mb-1">Terakhir Aktif</span>
                                        @if($user->last_seen_at) <span class="font-medium">{{ \Carbon\Carbon::parse($user->last_seen_at)->translatedFormat('d M Y, H:i') }}</span> @else <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 border border-yellow-200">Data Kosong</span> @endif
                                    </div>
                                    <div class="sm:col-span-2">
                                        <span class="block text-xs text-gray-400 uppercase tracking-wider mb-1">Logo Toko (Path)</span>
                                        @if($user->store_logo_path) <code class="text-xs text-gray-500">{{ $user->store_logo_path }}</code> @else <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 border border-yellow-200">Data Kosong</span> @endif
                                    </div>
                                </dd>
                            </div>

                        </dl>
                    </div>
                </div>
            </div>

        </div>
    </form>
</div>
@endsection

@push('scripts')
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('addressFinder', (searchUrl, geocodeUrl) => ({
            fields: {
                province: @json(old('province', $user->province)),
                city: @json(old('regency', $user->regency)), 
                subdistrict: @json(old('district', $user->district)), 
                village: @json(old('village', $user->village)),
                zip_code: @json(old('postal_code', $user->postal_code)),
                address_detail: @json(old('address_detail', $user->address_detail)),
                latitude: @json(old('latitude', $user->latitude)),
                longitude: @json(old('longitude', $user->longitude)),
            },
            searchQuery: '',
            results: [],
            loading: false,
            message: '',
            geocoding: false,
            geocodeMessage: '',
            
            async search() {
                if (this.searchQuery.length < 3) {
                    this.results = [];
                    this.message = '';
                    return;
                }
                
                this.loading = true;
                this.message = '';
                
                try {
                    const csrfToken = document.querySelector('meta[name="csrf-token"]');
                    const headers = { 'X-Requested-With': 'XMLHttpRequest' };
                    if (csrfToken) headers['X-CSRF-TOKEN'] = csrfToken.content;

                    const response = await fetch(`${searchUrl}?query=${encodeURIComponent(this.searchQuery)}`, { headers });
                    const data = await response.json();
                    
                    if (data.success && data.data.length > 0) {
                        this.results = data.data;
                    } else {
                        this.results = [];
                        this.message = data.message || 'Alamat tidak ditemukan.';
                    }
                } catch (error) {
                    console.error('Error searching address:', error);
                    this.message = 'Sistem sedang sibuk. Coba lagi.';
                } finally {
                    this.loading = false;
                }
            },
            
            selectAddress(result) {
                this.fields.province = result.province;
                this.fields.city = result.city;
                this.fields.subdistrict = result.subdistrict;
                this.fields.village = result.village;
                this.fields.zip_code = result.zip_code;
                
                this.searchQuery = '';
                this.results = [];
                
                this.$nextTick(() => {
                    document.getElementById('address_detail').focus();
                });
            },

            async getCoords() {
                this.geocoding = true;
                this.geocodeMessage = '';
                
                const fullAddress = [
                    this.fields.address_detail,
                    this.fields.village,
                    this.fields.subdistrict,
                    this.fields.city,
                    this.fields.province,
                    this.fields.zip_code
                ].filter(Boolean).join(', ');

                if (fullAddress.length < 10) {
                    this.geocodeMessage = 'Harap isi alamat lengkap terlebih dahulu.';
                    this.geocoding = false;
                    return;
                }

                try {
                    const csrfToken = document.querySelector('meta[name="csrf-token"]');
                    const headers = { 'X-Requested-With': 'XMLHttpRequest' };
                    if (csrfToken) headers['X-CSRF-TOKEN'] = csrfToken.content;

                    const response = await fetch(`${geocodeUrl}?address=${encodeURIComponent(fullAddress)}`, { headers });
                    const data = await response.json();
                    
                    if (data.success && data.data.lat) {
                        this.fields.latitude = data.data.lat;
                        this.fields.longitude = data.data.lng;
                        this.geocodeMessage = `Berhasil ditarik.`;
                    } else {
                        this.geocodeMessage = 'Koordinat tidak valid untuk alamat ini.';
                    }
                } catch (error) {
                    console.error('Error geocoding:', error);
                    this.geocodeMessage = 'Gagal memuat API koordinat.';
                } finally {
                    this.geocoding = false;
                }
            }
        }));
    });
</script>
<style>
    /* Menghindari flicker AlpineJS saat load */
    [x-cloak] { display: none !important; }
</style>
@endpush