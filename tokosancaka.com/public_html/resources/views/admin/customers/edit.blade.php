@extends('layouts.admin')

@section('title', 'Edit Data Pelanggan')

@section('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" />
<style>
    /* =========================
       NEXT.JS / VERCEL AESTHETIC
    ========================= */
    body {
        background-color: #fafafa;
    }

    .vercel-card {
        background: #ffffff;
        border: 1px solid #eaeaea;
        border-radius: 8px;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.02);
    }

    .vercel-input {
        width: 100%;
        min-height: 40px;
        border-radius: 6px;
        border: 1px solid #eaeaea;
        background: #ffffff;
        font-size: 0.875rem;
        color: #111111;
        padding: 0.5rem 0.75rem;
        transition: border-color 0.15s ease, box-shadow 0.15s ease;
    }

    .vercel-input:focus {
        outline: none;
        border-color: #111;
        box-shadow: 0 0 0 1px #111;
    }

    .vercel-input[readonly] {
        background: #fafafa;
        color: #666;
        cursor: not-allowed;
    }

    .vercel-label {
        display: block;
        font-size: 0.875rem;
        font-weight: 500;
        color: #444;
        margin-bottom: 0.5rem;
    }

    /* Primary Button (Black) */
    .btn-black {
        background-color: #111;
        color: #fff;
        border: 1px solid #111;
        border-radius: 6px;
        font-size: 0.875rem;
        font-weight: 500;
        padding: 0.5rem 1rem;
        transition: background-color 0.2s ease, color 0.2s ease;
    }
    
    .btn-black:hover {
        background-color: #fff;
        color: #111;
    }

    /* Secondary Button (White/Outline) */
    .btn-outline {
        background-color: #fff;
        color: #111;
        border: 1px solid #eaeaea;
        border-radius: 6px;
        font-size: 0.875rem;
        font-weight: 500;
        padding: 0.5rem 1rem;
        transition: border-color 0.2s ease;
    }

    .btn-outline:hover {
        border-color: #111;
    }

    /* Badge Kuning (Satu-satunya warna) */
    .badge-empty {
        display: inline-flex;
        align-items: center;
        padding: 0.125rem 0.5rem;
        font-size: 0.75rem;
        font-weight: 500;
        border-radius: 9999px;
        background-color: #fef3c7; /* Tailwind yellow-100 */
        color: #92400e; /* Tailwind yellow-800 */
        border: 1px solid #fde68a; /* Tailwind yellow-200 */
    }

    /* Badge Netral (Hitam/Putih) */
    .badge-neutral {
        display: inline-flex;
        align-items: center;
        padding: 0.125rem 0.5rem;
        font-size: 0.75rem;
        font-weight: 500;
        border-radius: 9999px;
        background-color: #f4f4f5; 
        color: #18181b; 
        border: 1px solid #e4e4e7;
    }
</style>
@endsection

@section('content')
<div class="px-4 py-8 md:px-8 max-w-6xl mx-auto">

    {{-- ================= FLASH MESSAGES ================= --}}
    @if(session('success'))
        <div class="flex items-center justify-between p-4 mb-6 text-sm bg-black text-white rounded-md" role="alert" id="alert-success">
            <div class="flex items-center gap-3">
                <i class="fa fa-check"></i>
                <span class="font-medium">{{ session('success') }}</span>
            </div>
            <button type="button" onclick="document.getElementById('alert-success').style.display='none'" class="text-gray-400 hover:text-white">
                <i class="fa fa-times"></i>
            </button>
        </div>
    @endif

    @if($errors->any())
        <div class="flex flex-col p-4 mb-6 text-sm border border-black bg-white rounded-md shadow-sm" role="alert">
            <div class="flex items-center font-bold text-black mb-2">
                <i class="fa fa-exclamation-triangle mr-2"></i> Terdapat kesalahan:
            </div>
            <ul class="list-disc list-inside text-gray-700 space-y-1 ml-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- ================= HEADER PAGE ================= --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-8 pb-4 border-b border-gray-200 gap-4">
        <div>
            <h1 class="text-3xl font-bold text-black tracking-tight">Pengaturan Pengguna</h1>
            <p class="mt-1 text-sm text-gray-500">
                Kelola profil, konfigurasi alamat, dan keamanan untuk <span class="font-semibold text-black">{{ $user->nama_lengkap }}</span>.
            </p>
        </div>
        <a href="{{ route('admin.customers.index') }}" class="btn-outline inline-flex items-center">
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
                <div class="vercel-card">
                    <div class="px-6 py-4 border-b border-gray-100 flex items-center">
                        <i class="fa fa-user mr-3 text-gray-400"></i>
                        <h3 class="text-base font-semibold text-black">Informasi Dasar</h3>
                    </div>
                    <div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div class="sm:col-span-2">
                            <label class="vercel-label" for="nama_lengkap">Nama Lengkap *</label>
                            <input type="text" id="nama_lengkap" name="nama_lengkap" value="{{ old('nama_lengkap', $user->nama_lengkap) }}" required class="vercel-input">
                        </div>

                        <div>
                            <label class="vercel-label" for="email">Alamat Email *</label>
                            <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required class="vercel-input">
                        </div>

                        <div>
                            <label class="vercel-label" for="no_wa">Nomor WhatsApp *</label>
                            <input type="text" id="no_wa" name="no_wa" value="{{ old('no_wa', $user->no_wa) }}" required class="vercel-input">
                        </div>

                        <div>
                            <label class="vercel-label" for="store_name">Nama Toko <span class="text-gray-400 font-normal">(Opsional)</span></label>
                            <input type="text" id="store_name" name="store_name" value="{{ old('store_name', $user->store_name) }}" class="vercel-input">
                        </div>

                        <div>
                            <label class="vercel-label" for="role">Hak Akses (Role) *</label>
                            <select id="role" name="role" required class="vercel-input">
                                <option value="Pelanggan" {{ old('role', $user->role) == 'Pelanggan' ? 'selected' : '' }}>Pelanggan</option>
                                <option value="Seller" {{ old('role', $user->role) == 'Seller' ? 'selected' : '' }}>Seller</option>
                                <option value="Admin" {{ old('role', $user->role) == 'Admin' ? 'selected' : '' }}>Admin</option>
                            </select>
                        </div>
                    </div>
                </div>

                {{-- SECTION: ALAMAT PENGGUNA (ALPINE.JS KIRIMINAJA) --}}
                <div class="vercel-card" x-data="addressFinder('{{ route('admin.api.search.address') }}', '{{ route('admin.api.geocode.address') }}')">
                    <div class="px-6 py-4 border-b border-gray-100 flex items-center">
                        <i class="fa fa-map-pin mr-3 text-gray-400"></i>
                        <h3 class="text-base font-semibold text-black">Alamat & Koordinat</h3>
                    </div>
                    
                    <div class="p-6 space-y-6">
                        {{-- KiriminAja Search --}}
                        <div class="relative">
                            <label class="vercel-label" for="address_search">Pencarian Alamat Otomatis</label>
                            <div class="relative">
                                <i class="fa fa-search absolute left-3 top-3 text-gray-400 text-sm"></i>
                                <input type="text" id="address_search"
                                       x-model.debounce.500ms="searchQuery"
                                       @input.debounce.500ms="search"
                                       placeholder="Ketik area, nama jalan, atau kelurahan..."
                                       class="vercel-input pl-9" autocomplete="off">
                                
                                <div x-show="loading" class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                    <i class="fa fa-spinner fa-spin text-gray-400"></i>
                                </div>
                            </div>
                            
                            {{-- Dropdown Results --}}
                            <div x-show="results.length > 0" @click.away="results = []" class="absolute z-10 w-full mt-1 bg-white border border-gray-200 rounded-md shadow-lg max-h-60 overflow-y-auto">
                                <ul class="divide-y divide-gray-100">
                                    <template x-for="result in results" :key="result.district_id + result.subdistrict_id">
                                        <li @click="selectAddress(result)" class="p-3 hover:bg-gray-50 cursor-pointer text-sm text-gray-800" x-text="result.text"></li>
                                    </template>
                                </ul>
                            </div>
                            <p x-show="message" x-text="message" class="text-sm text-black mt-2"></p>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <div>
                                <label class="vercel-label">Provinsi</label>
                                <input type="text" name="province" x-model="fields.province" readonly class="vercel-input">
                            </div>
                            <div>
                                <label class="vercel-label">Kabupaten / Kota</label>
                                <input type="text" name="regency" x-model="fields.city" readonly class="vercel-input">
                            </div>
                            <div>
                                <label class="vercel-label">Kecamatan</label>
                                <input type="text" name="district" x-model="fields.subdistrict" readonly class="vercel-input">
                            </div>
                            <div>
                                <label class="vercel-label">Desa / Kelurahan</label>
                                <input type="text" name="village" x-model="fields.village" readonly class="vercel-input">
                            </div>
                            <div class="sm:col-span-2">
                                <label class="vercel-label">Kode Pos</label>
                                <input type="text" name="postal_code" x-model="fields.zip_code" readonly class="vercel-input">
                            </div>
                            <div class="sm:col-span-2">
                                <label class="vercel-label">Detail Alamat Lengkap</label>
                                <textarea name="address_detail" id="address_detail" rows="3" x-model="fields.address_detail" class="vercel-input py-2 resize-y"></textarea>
                            </div>
                        </div>

                        {{-- Geocoding Section --}}
                        <div class="p-4 border border-gray-200 rounded-md bg-gray-50">
                            <div class="flex flex-col sm:flex-row sm:items-end gap-4">
                                <div class="flex-1">
                                    <label class="vercel-label text-xs uppercase text-gray-500 tracking-wider">Latitude</label>
                                    <input type="text" name="latitude" x-model="fields.latitude" class="vercel-input bg-white">
                                </div>
                                <div class="flex-1">
                                    <label class="vercel-label text-xs uppercase text-gray-500 tracking-wider">Longitude</label>
                                    <input type="text" name="longitude" x-model="fields.longitude" class="vercel-input bg-white">
                                </div>
                                <div>
                                    <button type="button" @click="getCoords" :disabled="geocoding" class="btn-outline h-[40px] w-full sm:w-auto">
                                        <span x-show="!geocoding">Cari Otomatis</span>
                                        <span x-show="geocoding"><i class="fa fa-spinner fa-spin mr-1"></i> Mencari...</span>
                                    </button>
                                </div>
                            </div>
                            <p x-show="geocodeMessage" x-text="geocodeMessage" class="text-xs text-black mt-2 font-medium"></p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ================= KOLOM KANAN (Keamanan) ================= --}}
            <div class="space-y-8">
                <div class="vercel-card">
                    <div class="px-6 py-4 border-b border-gray-100 flex items-center">
                        <i class="fa fa-lock mr-3 text-gray-400"></i>
                        <h3 class="text-base font-semibold text-black">Keamanan Akun</h3>
                    </div>

                    <div class="p-6 space-y-6">
                        <p class="text-xs text-gray-500 leading-relaxed">
                            Biarkan kolom di bawah ini kosong jika Anda tidak berencana untuk mengubah kata sandi atau PIN pengguna.
                        </p>

                        <div class="space-y-4">
                            <label class="vercel-label">Password Baru</label>
                            <div x-data="{ show: false }" class="relative">
                                <input :type="show ? 'text' : 'password'" name="password" autocomplete="new-password" placeholder="Minimal 8 karakter" class="vercel-input pr-10">
                                <button type="button" @click="show = !show" class="absolute right-3 top-[10px] text-gray-400 hover:text-black">
                                    <i class="fa" :class="show ? 'fa-eye' : 'fa-eye-slash'"></i>
                                </button>
                            </div>
                        </div>

                        <div class="pt-4 border-t border-gray-100 space-y-4">
                            <div class="flex items-center justify-between">
                                <label class="vercel-label mb-0">PIN Transaksi Baru</label>
                                @if(empty($user->pin))
                                    <span class="badge-empty">Belum Diatur</span>
                                @else
                                    <span class="badge-neutral">Terkonfigurasi</span>
                                @endif
                            </div>
                            <div x-data="{ showPin: false }" class="relative">
                                <input :type="showPin ? 'text' : 'password'" name="pin" maxlength="6" pattern="\d{6}" placeholder="6 Digit Angka" class="vercel-input font-mono tracking-widest pr-10">
                                <button type="button" @click="showPin = !showPin" class="absolute right-3 top-[10px] text-gray-400 hover:text-black">
                                    <i class="fa" :class="showPin ? 'fa-eye' : 'fa-eye-slash'"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                {{-- TOMBOL SUBMIT (Dipindahkan ke kanan bawah form keamanan untuk akses cepat) --}}
                <div class="flex flex-col gap-3">
                    <button type="submit" class="btn-black w-full text-center">
                        Simpan Perubahan
                    </button>
                    <a href="{{ route('admin.customers.index') }}" class="btn-outline w-full text-center">
                        Batal
                    </a>
                </div>
            </div>

            {{-- ================= SECTION DATABASE (READ-ONLY) ================= --}}
            <div class="lg:col-span-3">
                <div class="vercel-card mt-4">
                    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                        <div class="flex items-center">
                            <i class="fa fa-database mr-3 text-gray-400"></i>
                            <h3 class="text-base font-semibold text-black">Informasi Lengkap Database (Read-Only)</h3>
                        </div>
                        <span class="text-xs text-gray-400 font-mono">ID: #{{ $user->id_pengguna }}</span>
                    </div>
                    
                    {{-- UI Description List Modern --}}
                    <div class="px-6 py-2">
                        <dl class="divide-y divide-gray-100">
                            
                            <div class="py-4 grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <dt class="text-sm font-medium text-gray-500">Status Akun & Verifikasi</dt>
                                <dd class="text-sm text-black sm:col-span-2 flex flex-wrap gap-2">
                                    @if($user->status == 'Aktif')
                                        <span class="badge-neutral border-black text-black">Aktif</span>
                                    @else
                                        <span class="badge-neutral">{{ $user->status ?? 'Non-Aktif' }}</span>
                                    @endif

                                    @if($user->is_verified == 1)
                                        <span class="badge-neutral">Terverifikasi</span>
                                    @else
                                        <span class="badge-empty">Belum Verifikasi</span>
                                    @endif
                                </dd>
                            </div>

                            <div class="py-4 grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <dt class="text-sm font-medium text-gray-500">Saldo Pengguna</dt>
                                <dd class="text-sm text-black sm:col-span-2 grid grid-cols-1 sm:grid-cols-3 gap-4">
                                    <div>
                                        <span class="block text-xs text-gray-400 uppercase tracking-wider mb-1">Utama</span>
                                        <span class="font-mono">Rp {{ number_format($user->saldo ?? 0, 0, ',', '.') }}</span>
                                    </div>
                                    <div>
                                        <span class="block text-xs text-gray-400 uppercase tracking-wider mb-1">DANA</span>
                                        <span class="font-mono">Rp {{ number_format($user->dana_user_balance ?? 0, 0, ',', '.') }}</span>
                                    </div>
                                    <div>
                                        <span class="block text-xs text-gray-400 uppercase tracking-wider mb-1">IAK</span>
                                        <span class="font-mono">Rp {{ number_format($user->balance_iak ?? 0, 0, ',', '.') }}</span>
                                    </div>
                                </dd>
                            </div>

                            <div class="py-4 grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <dt class="text-sm font-medium text-gray-500">Informasi Bank</dt>
                                <dd class="text-sm text-black sm:col-span-2 grid grid-cols-1 sm:grid-cols-3 gap-4">
                                    <div>
                                        <span class="block text-xs text-gray-400 uppercase tracking-wider mb-1">Nama Bank</span>
                                        @if($user->bank_name) {{ $user->bank_name }} @else <span class="badge-empty">Data Kosong</span> @endif
                                    </div>
                                    <div>
                                        <span class="block text-xs text-gray-400 uppercase tracking-wider mb-1">Atas Nama</span>
                                        @if($user->bank_account_name) {{ $user->bank_account_name }} @else <span class="badge-empty">Data Kosong</span> @endif
                                    </div>
                                    <div>
                                        <span class="block text-xs text-gray-400 uppercase tracking-wider mb-1">No. Rekening</span>
                                        @if($user->bank_account_number) <span class="font-mono">{{ $user->bank_account_number }}</span> @else <span class="badge-empty">Data Kosong</span> @endif
                                    </div>
                                </dd>
                            </div>

                            <div class="py-4 grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <dt class="text-sm font-medium text-gray-500">API Tokens</dt>
                                <dd class="text-sm text-black sm:col-span-2 space-y-3">
                                    <div>
                                        <span class="block text-xs text-gray-400 uppercase tracking-wider mb-1">Expo Push Token</span>
                                        @if($user->expo_token) <code class="text-xs bg-gray-50 px-2 py-1 border border-gray-200 rounded break-all">{{ $user->expo_token }}</code> @else <span class="badge-empty">Data Kosong</span> @endif
                                    </div>
                                    <div>
                                        <span class="block text-xs text-gray-400 uppercase tracking-wider mb-1">DANA Access Token</span>
                                        @if($user->dana_access_token) <code class="text-xs bg-gray-50 px-2 py-1 border border-gray-200 rounded break-all">{{ $user->dana_access_token }}</code> @else <span class="badge-empty">Data Kosong</span> @endif
                                    </div>
                                    <div>
                                        <span class="block text-xs text-gray-400 uppercase tracking-wider mb-1">Setup Token</span>
                                        @if($user->setup_token) <code class="text-xs bg-gray-50 px-2 py-1 border border-gray-200 rounded break-all">{{ $user->setup_token }}</code> @else <span class="badge-empty">Data Kosong</span> @endif
                                    </div>
                                </dd>
                            </div>

                            <div class="py-4 grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <dt class="text-sm font-medium text-gray-500">Metadata</dt>
                                <dd class="text-sm text-black sm:col-span-2 grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <span class="block text-xs text-gray-400 uppercase tracking-wider mb-1">Dibuat Pada</span>
                                        @if($user->created_at) {{ \Carbon\Carbon::parse($user->created_at)->translatedFormat('d M Y, H:i') }} @else <span class="badge-empty">Data Kosong</span> @endif
                                    </div>
                                    <div>
                                        <span class="block text-xs text-gray-400 uppercase tracking-wider mb-1">Terakhir Aktif</span>
                                        @if($user->last_seen_at) {{ \Carbon\Carbon::parse($user->last_seen_at)->translatedFormat('d M Y, H:i') }} @else <span class="badge-empty">Data Kosong</span> @endif
                                    </div>
                                    <div class="sm:col-span-2">
                                        <span class="block text-xs text-gray-400 uppercase tracking-wider mb-1">Logo Toko (Path)</span>
                                        @if($user->store_logo_path) <code class="text-xs text-gray-600">{{ $user->store_logo_path }}</code> @else <span class="badge-empty">Data Kosong</span> @endif
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
@endpush