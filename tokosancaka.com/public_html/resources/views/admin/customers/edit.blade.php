@extends('layouts.admin')

@section('title', 'Edit Data Pelanggan')

@section('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" />
<style>
    /* =========================
       GLOBAL & CARD
    ========================= */
    .form-card {
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 1rem;
        overflow: hidden;
        box-shadow: 0 1px 2px rgba(0,0,0,.03), 0 8px 24px rgba(15,23,42,.04);
        transition: all .25s ease;
    }
    .form-card:hover {
        box-shadow: 0 4px 8px rgba(0,0,0,.04), 0 16px 32px rgba(15,23,42,.08);
    }
    
    /* =========================
       INPUT MODERN
    ========================= */
    input[type="text"], input[type="email"], input[type="password"], textarea, select {
        width: 100%;
        min-height: 46px;
        border-radius: 0.85rem !important;
        border: 1px solid #dbe1ea !important;
        background: #f8fafc !important;
        font-size: .92rem !important;
        color: #0f172a !important;
        transition: all .2s ease;
        box-shadow: none !important;
    }
    input:focus, textarea:focus, select:focus {
        background: #ffffff !important;
        border-color: #2563eb !important;
        box-shadow: 0 0 0 4px rgba(37,99,235,.10) !important;
        outline: none !important;
    }
    input[readonly], textarea[readonly] {
        background: #e2e8f0 !important;
        cursor: not-allowed;
    }
    label {
        font-size: .875rem;
        font-weight: 600;
        color: #1e293b;
        margin-bottom: .55rem !important;
    }

    /* =========================
       BADGE & LIST
    ========================= */
    .badge-empty {
        background-color: #fef3c7; /* yellow-100 */
        color: #92400e; /* yellow-800 */
        font-size: 0.75rem;
        font-weight: 600;
        padding: 0.25rem 0.6rem;
        border-radius: 9999px;
        display: inline-block;
    }
    .data-list dt {
        font-size: 0.8rem;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 0.25rem;
    }
    .data-list dd {
        font-size: 0.95rem;
        color: #0f172a;
        font-weight: 500;
        word-break: break-all;
    }
</style>
@endsection

@section('content')
<div class="px-4 py-6 md:px-6 lg:px-8 max-w-7xl mx-auto">

    {{-- ================= FLASH MESSAGES ================= --}}
    @if(session('success'))
        <div id="alert-success" class="flex items-center justify-between p-4 mb-6 text-sm text-emerald-800 border border-emerald-200 rounded-lg bg-emerald-50 shadow-sm" role="alert">
            <div class="flex items-center gap-3">
                <i class="fa fa-check-circle text-xl text-emerald-500"></i>
                <span class="font-medium">{{ session('success') }}</span>
            </div>
            <button type="button" onclick="document.getElementById('alert-success').style.display='none'" class="text-emerald-600 hover:text-emerald-900 focus:outline-none p-1">
                <i class="fa fa-times text-lg"></i>
            </button>
        </div>
    @endif

    @if($errors->any())
        <div id="alert-validation" class="flex p-4 mb-6 text-sm text-red-800 border border-red-200 rounded-lg bg-red-50 shadow-sm" role="alert">
            <i class="fa fa-exclamation-circle text-xl text-red-500 mr-3 mt-0.5"></i>
            <div>
                <span class="font-semibold text-base">Oops! Terdapat kesalahan pada form:</span>
                <ul class="mt-2 ml-4 list-disc list-outside text-red-700 space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            <button type="button" onclick="document.getElementById('alert-validation').style.display='none'" class="ml-auto text-red-500 hover:text-red-700 p-1 focus:outline-none h-fit">
                <i class="fa fa-times text-lg"></i>
            </button>
        </div>
    @endif

    {{-- ================= HEADER PAGE ================= --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-8 gap-4">
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-gray-900 tracking-tight">Edit Data Pengguna</h1>
            <p class="mt-1.5 text-sm text-gray-500">
                Perbarui informasi profil, alamat (via KiriminAja), dan keamanan dari <span class="font-semibold text-gray-800">{{ $user->nama_lengkap }}</span>.
            </p>
        </div>
        <a href="{{ route('admin.customers.index') }}" class="inline-flex items-center justify-center px-4 py-2.5 bg-white border border-gray-300 rounded-lg shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 hover:text-blue-600 transition-all">
            <i class="fa fa-arrow-left mr-2"></i> Kembali
        </a>
    </div>

    {{-- ================= FORM UTAMA ================= --}}
    <form action="{{ route('admin.customers.update', ['customer' => $user]) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 lg:gap-8">

            {{-- ================= KOLOM KIRI ================= --}}
            <div class="lg:col-span-8 space-y-6 lg:space-y-8">

                {{-- CARD: INFORMASI DASAR --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50/50">
                        <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                            <div class="bg-blue-100 text-blue-600 p-2 rounded-lg mr-3">
                                <i class="fa fa-user-circle w-5 h-5 flex items-center justify-center"></i>
                            </div>
                            Informasi Dasar
                        </h3>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <div class="sm:col-span-2">
                                <label for="nama_lengkap">Nama Lengkap <span class="text-red-500">*</span></label>
                                <input type="text" id="nama_lengkap" name="nama_lengkap" value="{{ old('nama_lengkap', $user->nama_lengkap) }}" required class="@error('nama_lengkap') border-red-500 bg-red-50 @enderror px-4">
                            </div>

                            <div>
                                <label for="email">Alamat Email <span class="text-red-500">*</span></label>
                                <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required class="@error('email') border-red-500 bg-red-50 @enderror px-4">
                            </div>

                            <div>
                                <label for="no_wa">Nomor WhatsApp <span class="text-red-500">*</span></label>
                                <input type="text" id="no_wa" name="no_wa" value="{{ old('no_wa', $user->no_wa) }}" required class="@error('no_wa') border-red-500 bg-red-50 @enderror px-4">
                            </div>

                            <div>
                                <label for="store_name">Nama Toko <span class="text-gray-400 font-normal">(Opsional)</span></label>
                                <input type="text" id="store_name" name="store_name" value="{{ old('store_name', $user->store_name) }}" class="px-4">
                            </div>

                            <div>
                                <label for="role">Hak Akses (Role) <span class="text-red-500">*</span></label>
                                <select id="role" name="role" required class="px-4">
                                    <option value="Pelanggan" {{ old('role', $user->role) == 'Pelanggan' ? 'selected' : '' }}>Pelanggan</option>
                                    <option value="Seller" {{ old('role', $user->role) == 'Seller' ? 'selected' : '' }}>Seller</option>
                                    <option value="Admin" {{ old('role', $user->role) == 'Admin' ? 'selected' : '' }}>Admin</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- CARD: ALAMAT PENGGUNA (ALPINE.JS KIRIMINAJA) --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden" 
                     x-data="addressFinder('{{ route('admin.api.search.address') }}', '{{ route('admin.api.geocode.address') }}')">
                    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50/50">
                        <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                            <div class="bg-teal-100 text-teal-600 p-2 rounded-lg mr-3">
                                <i class="fa fa-map-marker-alt w-5 h-5 flex items-center justify-center"></i>
                            </div>
                            Alamat & Koordinat
                        </h3>
                    </div>
                    <div class="p-6">
                        <div class="space-y-5">
                            
                            {{-- Field Pencarian --}}
                            <div class="relative">
                                <label for="address_search">Cari Alamat (KiriminAja)</label>
                                <input type="text" id="address_search"
                                       x-model.debounce.500ms="searchQuery"
                                       @input.debounce.500ms="search"
                                       placeholder="Ketik nama jalan, kelurahan, atau kecamatan..."
                                       class="px-4" autocomplete="off">
                                
                                {{-- Loading & Results --}}
                                <div x-show="loading" class="absolute inset-y-0 right-0 top-8 pr-4 flex items-center pointer-events-none">
                                    <i class="fa fa-spinner fa-spin text-gray-400"></i>
                                </div>
                                <div x-show="results.length > 0" @click.away="results = []" class="absolute z-10 w-full bg-white border border-gray-300 rounded-md shadow-lg mt-1 max-h-60 overflow-y-auto">
                                    <ul>
                                        <template x-for="result in results" :key="result.district_id + result.subdistrict_id">
                                            <li @click="selectAddress(result)" class="p-3 hover:bg-gray-100 cursor-pointer text-sm" x-text="result.text"></li>
                                        </template>
                                    </ul>
                                </div>
                                <p x-show="message" x-text="message" class="text-sm text-red-600 mt-1"></p>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                <div>
                                    <label>Provinsi</label>
                                    <input type="text" name="province" x-model="fields.province" readonly class="px-4">
                                </div>
                                <div>
                                    <label>Kabupaten / Kota</label>
                                    <input type="text" name="regency" x-model="fields.city" readonly class="px-4">
                                </div>
                                <div>
                                    <label>Kecamatan</label>
                                    <input type="text" name="district" x-model="fields.subdistrict" readonly class="px-4">
                                </div>
                                <div>
                                    <label>Desa / Kelurahan</label>
                                    <input type="text" name="village" x-model="fields.village" readonly class="px-4">
                                </div>
                                <div class="sm:col-span-2">
                                    <label>Kode Pos</label>
                                    <input type="text" name="postal_code" x-model="fields.zip_code" readonly class="px-4">
                                </div>
                                <div class="sm:col-span-2">
                                    <label>Detail Alamat (Jalan, RT/RW, Patokan)</label>
                                    <textarea name="address_detail" id="address_detail" rows="3" x-model="fields.address_detail" class="px-4 py-2 resize-y"></textarea>
                                </div>
                            </div>

                            {{-- Koordinat Lat/Long --}}
                            <div class="p-4 bg-gray-50 rounded-lg border border-gray-200 mt-4">
                                <h4 class="text-sm font-semibold mb-3 text-gray-700">Koordinat Peta</h4>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label>Latitude</label>
                                        <input type="text" name="latitude" x-model="fields.latitude" class="px-4 bg-white">
                                    </div>
                                    <div>
                                        <label>Longitude</label>
                                        <input type="text" name="longitude" x-model="fields.longitude" class="px-4 bg-white">
                                    </div>
                                </div>
                                <div class="mt-4">
                                    <button type="button" @click="getCoords" :disabled="geocoding" class="w-full sm:w-auto inline-flex justify-center py-2.5 px-4 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-100 transition-colors">
                                        <i class="fa fa-map-marker-alt mr-2 mt-0.5"></i>
                                        <span x-show="!geocoding">Auto Dapatkan Koordinat</span>
                                        <span x-show="geocoding">Mencari...</span>
                                    </button>
                                    <p x-show="geocodeMessage" x-text="geocodeMessage" class="text-sm text-green-600 mt-2 font-medium"></p>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>

            {{-- ================= KOLOM KANAN ================= --}}
            <div class="lg:col-span-4 space-y-6 lg:space-y-8">
                {{-- CARD: KEAMANAN --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50/50">
                        <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                            <div class="bg-slate-100 text-slate-700 p-2 rounded-lg mr-3">
                                <i class="fa fa-shield-alt w-5 h-5 flex items-center justify-center"></i>
                            </div>
                            Keamanan Akun
                        </h3>
                    </div>

                    <div class="p-6 space-y-6">
                        <div class="bg-blue-50 text-blue-800 text-xs font-medium px-3 py-2 rounded-md border border-blue-100">
                            <i class="fa fa-info-circle mr-1"></i> Kosongkan jika tidak ingin mengubah password/PIN.
                        </div>

                        {{-- UBAH PASSWORD --}}
                        <div class="space-y-4">
                            <h4 class="text-sm font-bold text-gray-900 uppercase tracking-wider border-b border-gray-100 pb-2">Password Login</h4>
                            <div x-data="{ show: false }" class="relative">
                                <label>Password Baru</label>
                                <input :type="show ? 'text' : 'password'" name="password" autocomplete="new-password" placeholder="Minimal 8 Karakter" class="px-4 pr-10">
                                <button type="button" @click="show = !show" class="absolute right-3 top-10 text-gray-400 hover:text-gray-600">
                                    <i class="fa" :class="show ? 'fa-eye' : 'fa-eye-slash'"></i>
                                </button>
                            </div>
                        </div>

                        {{-- UBAH PIN --}}
                        <div class="space-y-4 pt-2">
                            <div class="flex justify-between items-center border-b border-gray-100 pb-2">
                                <h4 class="text-sm font-bold text-gray-900 uppercase tracking-wider">PIN Transaksi</h4>
                                @if(empty($user->pin))
                                    <span class="badge-empty">Belum Diatur</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-100 text-emerald-800 border border-emerald-200">
                                      <i class="fa fa-check mr-1"></i> Terlindungi
                                    </span>
                                @endif
                            </div>
                            <div x-data="{ showPin: false }" class="relative">
                                <label>Set PIN Baru / Reset</label>
                                <input :type="showPin ? 'text' : 'password'" name="pin" maxlength="6" pattern="\d{6}" placeholder="6 Digit Angka" class="px-4 pr-10 font-mono tracking-widest text-center">
                                <button type="button" @click="showPin = !showPin" class="absolute right-3 top-10 text-gray-400 hover:text-gray-600">
                                    <i class="fa" :class="showPin ? 'fa-eye' : 'fa-eye-slash'"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ================= FULL WIDTH: SEMUA DATA DATABASE ================= --}}
            <div class="lg:col-span-12">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50/50 flex justify-between items-center">
                        <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                            <div class="bg-indigo-100 text-indigo-600 p-2 rounded-lg mr-3">
                                <i class="fa fa-database w-5 h-5 flex items-center justify-center"></i>
                            </div>
                            Informasi Lengkap Database (Read-Only)
                        </h3>
                    </div>
                    <div class="p-6">
                        <dl class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-x-6 gap-y-8 data-list">
                            {{-- Baris 1: Core Identifiers --}}
                            <div>
                                <dt>ID Pengguna</dt>
                                <dd>#{{ $user->id_pengguna }}</dd>
                            </div>
                            <div>
                                <dt>Status Verifikasi</dt>
                                <dd>
                                    @if($user->is_verified == 1)
                                        <span class="text-green-600 font-bold"><i class="fa fa-check-circle"></i> Terverifikasi</span>
                                    @else
                                        <span class="badge-empty">Belum Verifikasi</span>
                                    @endif
                                </dd>
                            </div>
                            <div>
                                <dt>Status Akun</dt>
                                <dd>
                                    @if($user->status == 'Aktif')
                                        <span class="text-green-600 font-bold">{{ $user->status }}</span>
                                    @else
                                        <span class="text-red-600 font-bold">{{ $user->status ?? 'Non-Aktif' }}</span>
                                    @endif
                                </dd>
                            </div>

                            {{-- Baris 2: Keuangan --}}
                            <div>
                                <dt>Saldo Utama</dt>
                                <dd class="text-blue-600 font-bold">Rp {{ number_format($user->saldo ?? 0, 0, ',', '.') }}</dd>
                            </div>
                            <div>
                                <dt>Saldo IAK</dt>
                                <dd>Rp {{ number_format($user->balance_iak ?? 0, 0, ',', '.') }}</dd>
                            </div>
                            <div>
                                <dt>Saldo DANA</dt>
                                <dd>Rp {{ number_format($user->dana_user_balance ?? 0, 0, ',', '.') }}</dd>
                            </div>

                            {{-- Baris 3: Rekening Bank --}}
                            <div>
                                <dt>Nama Bank</dt>
                                <dd>@if($user->bank_name) {{ $user->bank_name }} @else <span class="badge-empty">Kosong</span> @endif</dd>
                            </div>
                            <div>
                                <dt>Atas Nama Rekening</dt>
                                <dd>@if($user->bank_account_name) {{ $user->bank_account_name }} @else <span class="badge-empty">Kosong</span> @endif</dd>
                            </div>
                            <div>
                                <dt>Nomor Rekening</dt>
                                <dd>@if($user->bank_account_number) <span class="font-mono">{{ $user->bank_account_number }}</span> @else <span class="badge-empty">Kosong</span> @endif</dd>
                            </div>

                            {{-- Baris 4: Tokens / API --}}
                            <div>
                                <dt>Expo Push Token</dt>
                                <dd>@if($user->expo_token) <span class="text-xs break-all bg-gray-100 p-1 rounded">{{ $user->expo_token }}</span> @else <span class="badge-empty">Kosong</span> @endif</dd>
                            </div>
                            <div>
                                <dt>DANA Access Token</dt>
                                <dd>@if($user->dana_access_token) <span class="text-xs break-all bg-gray-100 p-1 rounded">{{ $user->dana_access_token }}</span> @else <span class="badge-empty">Kosong</span> @endif</dd>
                            </div>
                            <div>
                                <dt>Setup Token</dt>
                                <dd>@if($user->setup_token) <span class="text-xs break-all bg-gray-100 p-1 rounded">{{ $user->setup_token }}</span> @else <span class="badge-empty">Kosong</span> @endif</dd>
                            </div>

                            {{-- Baris 5: Timestamps & Meta --}}
                            <div>
                                <dt>Tanggal Bergabung</dt>
                                <dd>@if($user->created_at) {{ \Carbon\Carbon::parse($user->created_at)->translatedFormat('d F Y, H:i') }} @else <span class="badge-empty">Kosong</span> @endif</dd>
                            </div>
                            <div>
                                <dt>Terakhir Dilihat (Last Seen)</dt>
                                <dd>@if($user->last_seen_at) {{ \Carbon\Carbon::parse($user->last_seen_at)->translatedFormat('d F Y, H:i') }} @else <span class="badge-empty">Kosong</span> @endif</dd>
                            </div>
                            <div>
                                <dt>Path Logo Toko</dt>
                                <dd>@if($user->store_logo_path) <span class="text-xs text-blue-500 underline break-all">{{ $user->store_logo_path }}</span> @else <span class="badge-empty">Kosong</span> @endif</dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>

        </div>

        {{-- AREA TOMBOL SUBMIT --}}
        <div class="mt-8 pt-6 border-t border-gray-200 flex flex-col-reverse sm:flex-row sm:justify-end gap-3 pb-10">
            <a href="{{ route('admin.customers.index') }}" class="w-full sm:w-auto inline-flex justify-center items-center px-5 py-2.5 bg-white border border-gray-300 rounded-lg shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 hover:text-blue-600 transition-all">
                Batal
            </a>
            <button type="submit" class="w-full sm:w-auto inline-flex justify-center items-center px-6 py-2.5 bg-blue-600 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white hover:bg-blue-700 transition-all">
                <i class="fa fa-save mr-2"></i> Simpan Perubahan
            </button>
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
                    // Gunakan fetch dengan menyertakan CSRF token
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
                    this.message = 'Gagal terhubung ke server pencari alamat.';
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
                
                // Pindahkan fokus ke textarea
                this.$nextTick(() => {
                    document.getElementById('address_detail').focus();
                });
            },

            async getCoords() {
                this.geocoding = true;
                this.geocodeMessage = 'Mencari koordinat...';
                
                const fullAddress = [
                    this.fields.address_detail,
                    this.fields.village,
                    this.fields.subdistrict,
                    this.fields.city,
                    this.fields.province,
                    this.fields.zip_code
                ].filter(Boolean).join(', ');

                if (fullAddress.length < 10) {
                    this.geocodeMessage = 'Harap isi detail alamat lebih lengkap terlebih dahulu.';
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
                        this.geocodeMessage = `Berhasil! Lat: ${data.data.lat}, Lng: ${data.data.lng}`;
                    } else {
                        this.geocodeMessage = 'Koordinat tidak ditemukan untuk alamat ini. Silakan input manual.';
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