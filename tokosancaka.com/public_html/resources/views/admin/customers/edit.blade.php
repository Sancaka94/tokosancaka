@extends('layouts.admin')

@section('title', 'Edit Data Pelanggan')

@section('content')
<div class="px-4 py-6 md:px-8 max-w-[1400px] mx-auto bg-[#f8f9fa] min-h-screen">

    {{-- ================= FLASH MESSAGES (Gaya Bootstrap 5 Alert) ================= --}}
    @if(session('success'))
        <div x-data="{ show: true }" x-show="show" class="flex items-center justify-between p-4 mb-6 bg-[#d1e7dd] text-[#0f5132] border border-[#badbcc] rounded transition-opacity" role="alert">
            <div class="flex items-center gap-2">
                <i class="fa fa-check-circle text-lg"></i>
                <span class="font-semibold">{{ session('success') }}</span>
            </div>
            <button type="button" @click="show = false" class="text-[#0f5132] hover:opacity-75">
                <i class="fa fa-times text-lg"></i>
            </button>
        </div>
    @endif

    @if($errors->any())
        <div x-data="{ show: true }" x-show="show" class="flex flex-col p-4 mb-6 bg-[#f8d7da] text-[#842029] border border-[#f5c2c7] rounded transition-opacity" role="alert">
            <div class="flex items-center justify-between mb-2">
                <div class="flex items-center font-bold">
                    <i class="fa fa-exclamation-triangle mr-2"></i> Peringatan!
                </div>
                <button type="button" @click="show = false" class="text-[#842029] hover:opacity-75">
                    <i class="fa fa-times text-lg"></i>
                </button>
            </div>
            <ul class="list-disc list-inside space-y-1 ml-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- ================= HEADER PAGE ================= --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between mb-6 pb-3 border-b border-gray-300 gap-4">
        <div>
            <h1 class="text-3xl font-normal text-[#212529] mb-1">Edit Pengguna (Super Admin Mode)</h1>
            <p class="text-base text-[#6c757d]">
                Semua form terbuka. Edit data untuk pengguna: <span class="font-bold">{{ $user->nama_lengkap }}</span>
            </p>
        </div>
        <a href="{{ route('admin.customers.index') }}" class="inline-flex items-center justify-center px-4 py-2 bg-[#6c757d] text-white rounded hover:bg-[#5c636a] focus:ring-4 focus:ring-[#6c757d]/50 transition duration-150 ease-in-out text-base">
            <i class="fa fa-arrow-left mr-2"></i> Kembali
        </a>
    </div>

    {{-- ================= FORM UTAMA ================= --}}
    <form action="{{ route('admin.customers.update', ['customer' => $user]) }}" method="POST">
        @csrf
        @method('PUT')

        {{-- BARIS 1: INFORMASI DASAR & KEAMANAN --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            
            {{-- CARD: INFORMASI DASAR --}}
            <div class="bg-white border border-gray-200 rounded shadow-sm">
                <div class="bg-[#f8f9fa] border-b border-gray-200 px-5 py-3">
                    <h5 class="text-lg font-medium text-[#212529] m-0"><i class="fa fa-user text-[#0d6efd] mr-2"></i> Informasi Dasar</h5>
                </div>
                <div class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="sm:col-span-2">
                        <label class="block text-base font-normal text-[#212529] mb-2">ID Pengguna <span class="text-red-500">*</span></label>
                        <input type="text" name="id_pengguna" value="{{ old('id_pengguna', $user->id_pengguna) }}" required 
                               class="block w-full px-4 py-2.5 text-base font-normal text-[#212529] bg-white border border-[#ced4da] rounded focus:border-[#86b7fe] focus:outline-none focus:ring-[4px] focus:ring-[#0d6efd]/25 transition">
                    </div>
                    <div>
                        <label class="block text-base font-normal text-[#212529] mb-2">Nama Lengkap <span class="text-red-500">*</span></label>
                        <input type="text" name="nama_lengkap" value="{{ old('nama_lengkap', $user->nama_lengkap) }}" required 
                               class="block w-full px-4 py-2.5 text-base font-normal text-[#212529] bg-white border border-[#ced4da] rounded focus:border-[#86b7fe] focus:outline-none focus:ring-[4px] focus:ring-[#0d6efd]/25 transition">
                    </div>
                    <div>
                        <label class="block text-base font-normal text-[#212529] mb-2">Email <span class="text-red-500">*</span></label>
                        <input type="email" name="email" value="{{ old('email', $user->email) }}" required 
                               class="block w-full px-4 py-2.5 text-base font-normal text-[#212529] bg-white border border-[#ced4da] rounded focus:border-[#86b7fe] focus:outline-none focus:ring-[4px] focus:ring-[#0d6efd]/25 transition">
                    </div>
                    <div>
                        <label class="block text-base font-normal text-[#212529] mb-2">Nomor WA <span class="text-red-500">*</span></label>
                        <input type="text" name="no_wa" value="{{ old('no_wa', $user->no_wa) }}" required 
                               class="block w-full px-4 py-2.5 text-base font-normal text-[#212529] bg-white border border-[#ced4da] rounded focus:border-[#86b7fe] focus:outline-none focus:ring-[4px] focus:ring-[#0d6efd]/25 transition">
                    </div>
                    <div>
                        <label class="block text-base font-normal text-[#212529] mb-2">Nama Toko</label>
                        <input type="text" name="store_name" value="{{ old('store_name', $user->store_name) }}" 
                               class="block w-full px-4 py-2.5 text-base font-normal text-[#212529] bg-white border border-[#ced4da] rounded focus:border-[#86b7fe] focus:outline-none focus:ring-[4px] focus:ring-[#0d6efd]/25 transition">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-base font-normal text-[#212529] mb-2">Path Logo Toko</label>
                        <input type="text" name="store_logo_path" value="{{ old('store_logo_path', $user->store_logo_path) }}" 
                               class="block w-full px-4 py-2.5 text-base font-normal text-[#212529] bg-white border border-[#ced4da] rounded focus:border-[#86b7fe] focus:outline-none focus:ring-[4px] focus:ring-[#0d6efd]/25 transition">
                    </div>
                </div>
            </div>

            {{-- CARD: STATUS & KEAMANAN --}}
            <div class="bg-white border border-gray-200 rounded shadow-sm">
                <div class="bg-[#f8f9fa] border-b border-gray-200 px-5 py-3">
                    <h5 class="text-lg font-medium text-[#212529] m-0"><i class="fa fa-shield-alt text-[#198754] mr-2"></i> Status & Keamanan</h5>
                </div>
                <div class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-base font-normal text-[#212529] mb-2">Hak Akses (Role)</label>
                        <select name="role" class="block w-full px-4 py-2.5 text-base font-normal text-[#212529] bg-white border border-[#ced4da] rounded focus:border-[#86b7fe] focus:outline-none focus:ring-[4px] focus:ring-[#0d6efd]/25 transition">
                            <option value="Pelanggan" {{ old('role', $user->role) == 'Pelanggan' ? 'selected' : '' }}>Pelanggan</option>
                            <option value="Seller" {{ old('role', $user->role) == 'Seller' ? 'selected' : '' }}>Seller</option>
                            <option value="Admin" {{ old('role', $user->role) == 'Admin' ? 'selected' : '' }}>Admin</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-base font-normal text-[#212529] mb-2">Status Akun</label>
                        <select name="status" class="block w-full px-4 py-2.5 text-base font-normal text-[#212529] bg-white border border-[#ced4da] rounded focus:border-[#86b7fe] focus:outline-none focus:ring-[4px] focus:ring-[#0d6efd]/25 transition">
                            <option value="Aktif" {{ old('status', $user->status) == 'Aktif' ? 'selected' : '' }}>Aktif</option>
                            <option value="Nonaktif" {{ old('status', $user->status) == 'Nonaktif' ? 'selected' : '' }}>Nonaktif</option>
                            <option value="Banned" {{ old('status', $user->status) == 'Banned' ? 'selected' : '' }}>Banned</option>
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-base font-normal text-[#212529] mb-2">Status Verifikasi</label>
                        <select name="is_verified" class="block w-full px-4 py-2.5 text-base font-normal text-[#212529] bg-white border border-[#ced4da] rounded focus:border-[#86b7fe] focus:outline-none focus:ring-[4px] focus:ring-[#0d6efd]/25 transition">
                            <option value="1" {{ old('is_verified', $user->is_verified) == '1' ? 'selected' : '' }}>1 - Terverifikasi</option>
                            <option value="0" {{ old('is_verified', $user->is_verified) == '0' ? 'selected' : '' }}>0 - Belum Verifikasi</option>
                        </select>
                    </div>
                    
                    <div class="sm:col-span-2 border-t border-gray-200 mt-2 pt-4">
                        <p class="text-sm text-[#6c757d] mb-3">Kosongkan kolom sandi jika tidak ingin mengubah.</p>
                    </div>

                    <div x-data="{ show: false }">
                        <label class="block text-base font-normal text-[#212529] mb-2">Ganti Password</label>
                        <div class="relative">
                            <input :type="show ? 'text' : 'password'" name="password" autocomplete="new-password" 
                                   class="block w-full px-4 py-2.5 pr-10 text-base font-normal text-[#212529] bg-white border border-[#ced4da] rounded focus:border-[#86b7fe] focus:outline-none focus:ring-[4px] focus:ring-[#0d6efd]/25 transition">
                            <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 px-3 flex items-center text-gray-600">
                                <i class="fa" :class="show ? 'fa-eye' : 'fa-eye-slash'"></i>
                            </button>
                        </div>
                    </div>
                    <div x-data="{ showPin: false }">
                        <label class="block text-base font-normal text-[#212529] mb-2">Ganti PIN (6 Digit)</label>
                        <div class="relative">
                            <input :type="showPin ? 'text' : 'password'" name="pin" maxlength="6" 
                                   class="block w-full px-4 py-2.5 pr-10 text-base font-normal text-[#212529] bg-white border border-[#ced4da] rounded focus:border-[#86b7fe] focus:outline-none focus:ring-[4px] focus:ring-[#0d6efd]/25 transition font-mono tracking-widest">
                            <button type="button" @click="showPin = !showPin" class="absolute inset-y-0 right-0 px-3 flex items-center text-gray-600">
                                <i class="fa" :class="showPin ? 'fa-eye' : 'fa-eye-slash'"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- BARIS 2: KEUANGAN & BANK --}}
        <div class="bg-white border border-gray-200 rounded shadow-sm mb-6">
            <div class="bg-[#f8f9fa] border-b border-gray-200 px-5 py-3">
                <h5 class="text-lg font-medium text-[#212529] m-0"><i class="fa fa-wallet text-[#ffc107] mr-2"></i> Keuangan & Data Bank</h5>
            </div>
            <div class="p-5 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
                {{-- Saldo --}}
                <div>
                    <label class="block text-base font-normal text-[#212529] mb-2">Saldo Utama</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">Rp</span>
                        <input type="text" name="saldo" value="{{ old('saldo', $user->saldo) }}" 
                               class="block w-full pl-10 pr-4 py-2.5 text-base font-normal text-[#212529] bg-white border border-[#ced4da] rounded focus:border-[#86b7fe] focus:outline-none focus:ring-[4px] focus:ring-[#0d6efd]/25 transition">
                    </div>
                </div>
                <div>
                    <label class="block text-base font-normal text-[#212529] mb-2">Saldo DANA</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">Rp</span>
                        <input type="text" name="dana_user_balance" value="{{ old('dana_user_balance', $user->dana_user_balance) }}" 
                               class="block w-full pl-10 pr-4 py-2.5 text-base font-normal text-[#212529] bg-white border border-[#ced4da] rounded focus:border-[#86b7fe] focus:outline-none focus:ring-[4px] focus:ring-[#0d6efd]/25 transition">
                    </div>
                </div>
                <div>
                    <label class="block text-base font-normal text-[#212529] mb-2">Saldo IAK</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">Rp</span>
                        <input type="text" name="balance_iak" value="{{ old('balance_iak', $user->balance_iak) }}" 
                               class="block w-full pl-10 pr-4 py-2.5 text-base font-normal text-[#212529] bg-white border border-[#ced4da] rounded focus:border-[#86b7fe] focus:outline-none focus:ring-[4px] focus:ring-[#0d6efd]/25 transition">
                    </div>
                </div>

                {{-- Bank --}}
                <div class="sm:col-span-1 md:col-span-1 border-t border-gray-200 pt-4 md:border-none md:pt-0 mt-4 md:mt-0">
                    <label class="block text-base font-normal text-[#212529] mb-2">Nama Bank</label>
                    <input type="text" name="bank_name" value="{{ old('bank_name', $user->bank_name) }}" 
                           class="block w-full px-4 py-2.5 text-base font-normal text-[#212529] bg-white border border-[#ced4da] rounded focus:border-[#86b7fe] focus:outline-none focus:ring-[4px] focus:ring-[#0d6efd]/25 transition">
                </div>
                <div class="sm:col-span-1 md:col-span-1 border-t border-gray-200 pt-4 md:border-none md:pt-0 mt-4 md:mt-0">
                    <label class="block text-base font-normal text-[#212529] mb-2">Atas Nama Rekening</label>
                    <input type="text" name="bank_account_name" value="{{ old('bank_account_name', $user->bank_account_name) }}" 
                           class="block w-full px-4 py-2.5 text-base font-normal text-[#212529] bg-white border border-[#ced4da] rounded focus:border-[#86b7fe] focus:outline-none focus:ring-[4px] focus:ring-[#0d6efd]/25 transition">
                </div>
                <div class="sm:col-span-2 md:col-span-1 border-t border-gray-200 pt-4 md:border-none md:pt-0 mt-4 md:mt-0">
                    <label class="block text-base font-normal text-[#212529] mb-2">No. Rekening</label>
                    <input type="text" name="bank_account_number" value="{{ old('bank_account_number', $user->bank_account_number) }}" 
                           class="block w-full px-4 py-2.5 text-base font-normal text-[#212529] bg-white border border-[#ced4da] rounded focus:border-[#86b7fe] focus:outline-none focus:ring-[4px] focus:ring-[#0d6efd]/25 transition font-mono">
                </div>
            </div>
        </div>

        {{-- BARIS 3: ALAMAT & LOKASI (Bebas Edit + KiriminAja) --}}
        <div class="bg-white border border-gray-200 rounded shadow-sm mb-6" 
             x-data="addressFinder('{{ route('admin.api.search.address') }}', '{{ route('admin.api.geocode.address') }}')">
            <div class="bg-[#f8f9fa] border-b border-gray-200 px-5 py-3 flex justify-between items-center">
                <h5 class="text-lg font-medium text-[#212529] m-0"><i class="fa fa-map-marker-alt text-[#dc3545] mr-2"></i> Alamat & Lokasi Geografis</h5>
                <span class="bg-[#0dcaf0] text-black text-xs px-2 py-1 rounded font-bold">ALL EDITABLE</span>
            </div>
            
            <div class="p-5">
                {{-- Alat Bantu KiriminAja --}}
                <div class="mb-6 p-4 bg-[#e2e3e5] border border-[#d3d6d8] rounded">
                    <label class="block text-base font-bold text-[#212529] mb-2">Bantu Isi Otomatis dengan KiriminAja (Opsional)</label>
                    <div class="relative">
                        <input type="text" id="address_search" x-model.debounce.500ms="searchQuery" @input.debounce.500ms="search" 
                               placeholder="Ketik kecamatan atau nama desa di sini..." autocomplete="off"
                               class="block w-full px-4 py-2.5 text-base font-normal text-[#212529] bg-white border border-[#ced4da] rounded focus:border-[#86b7fe] focus:outline-none focus:ring-[4px] focus:ring-[#0d6efd]/25 transition">
                        
                        <div x-show="loading" x-cloak class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                            <i class="fa fa-spinner fa-spin text-[#6c757d]"></i>
                        </div>
                    </div>
                    
                    <div x-show="results.length > 0" x-cloak @click.away="results = []" class="absolute z-10 w-full mt-1 bg-white border border-[#ced4da] rounded shadow-lg max-h-60 overflow-y-auto max-w-2xl">
                        <ul class="m-0 p-0 list-none">
                            <template x-for="result in results" :key="result.district_id + result.subdistrict_id">
                                <li @click="selectAddress(result)" class="px-4 py-2 hover:bg-[#e9ecef] cursor-pointer text-base text-[#212529] border-b border-gray-100 last:border-0" x-text="result.text"></li>
                            </template>
                        </ul>
                    </div>
                    <p x-show="message" x-cloak x-text="message" class="text-sm text-[#dc3545] mt-2 mb-0 font-medium"></p>
                </div>

                {{-- Form Alamat (Full Input) --}}
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
                    <div>
                        <label class="block text-base font-normal text-[#212529] mb-2">Provinsi</label>
                        <input type="text" name="province" x-model="fields.province" 
                               class="block w-full px-4 py-2.5 text-base font-normal text-[#212529] bg-white border border-[#ced4da] rounded focus:border-[#86b7fe] focus:outline-none focus:ring-[4px] focus:ring-[#0d6efd]/25 transition">
                    </div>
                    <div>
                        <label class="block text-base font-normal text-[#212529] mb-2">Kabupaten/Kota</label>
                        <input type="text" name="regency" x-model="fields.city" 
                               class="block w-full px-4 py-2.5 text-base font-normal text-[#212529] bg-white border border-[#ced4da] rounded focus:border-[#86b7fe] focus:outline-none focus:ring-[4px] focus:ring-[#0d6efd]/25 transition">
                    </div>
                    <div>
                        <label class="block text-base font-normal text-[#212529] mb-2">Kecamatan</label>
                        <input type="text" name="district" x-model="fields.subdistrict" 
                               class="block w-full px-4 py-2.5 text-base font-normal text-[#212529] bg-white border border-[#ced4da] rounded focus:border-[#86b7fe] focus:outline-none focus:ring-[4px] focus:ring-[#0d6efd]/25 transition">
                    </div>
                    <div>
                        <label class="block text-base font-normal text-[#212529] mb-2">Desa/Kelurahan</label>
                        <input type="text" name="village" x-model="fields.village" 
                               class="block w-full px-4 py-2.5 text-base font-normal text-[#212529] bg-white border border-[#ced4da] rounded focus:border-[#86b7fe] focus:outline-none focus:ring-[4px] focus:ring-[#0d6efd]/25 transition">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-12 gap-4 mb-4">
                    <div class="md:col-span-3">
                        <label class="block text-base font-normal text-[#212529] mb-2">Kode Pos</label>
                        <input type="text" name="postal_code" x-model="fields.zip_code" 
                               class="block w-full px-4 py-2.5 text-base font-normal text-[#212529] bg-white border border-[#ced4da] rounded focus:border-[#86b7fe] focus:outline-none focus:ring-[4px] focus:ring-[#0d6efd]/25 transition">
                    </div>
                    <div class="md:col-span-9">
                        <label class="block text-base font-normal text-[#212529] mb-2">Detail Jalan / RT RW</label>
                        <textarea name="address_detail" id="address_detail" rows="2" x-model="fields.address_detail" 
                                  class="block w-full px-4 py-2.5 text-base font-normal text-[#212529] bg-white border border-[#ced4da] rounded focus:border-[#86b7fe] focus:outline-none focus:ring-[4px] focus:ring-[#0d6efd]/25 transition"></textarea>
                    </div>
                </div>

                {{-- Koordinat Maps --}}
                <div class="bg-white border border-[#ced4da] rounded p-4">
                    <div class="flex flex-col md:flex-row md:items-end gap-4">
                        <div class="flex-1">
                            <label class="block text-base font-normal text-[#212529] mb-2">Latitude</label>
                            <input type="text" name="latitude" x-model="fields.latitude" 
                                   class="block w-full px-4 py-2.5 text-base font-normal text-[#212529] bg-white border border-[#ced4da] rounded focus:border-[#86b7fe] focus:outline-none focus:ring-[4px] focus:ring-[#0d6efd]/25 transition">
                        </div>
                        <div class="flex-1">
                            <label class="block text-base font-normal text-[#212529] mb-2">Longitude</label>
                            <input type="text" name="longitude" x-model="fields.longitude" 
                                   class="block w-full px-4 py-2.5 text-base font-normal text-[#212529] bg-white border border-[#ced4da] rounded focus:border-[#86b7fe] focus:outline-none focus:ring-[4px] focus:ring-[#0d6efd]/25 transition">
                        </div>
                        <div class="pt-2 md:pt-0">
                            <button type="button" @click="getCoords" :disabled="geocoding" class="w-full md:w-auto inline-block px-4 py-2.5 bg-[#0dcaf0] text-black font-medium text-base leading-tight rounded shadow-sm hover:bg-[#31d2f2] focus:bg-[#31d2f2] focus:outline-none focus:ring-4 focus:ring-[#0dcaf0]/50 transition duration-150 ease-in-out h-[46px]">
                                <span x-show="!geocoding"><i class="fa fa-map-marked-alt mr-1"></i> Tarik Koordinat API</span>
                                <span x-show="geocoding" x-cloak><i class="fa fa-spinner fa-spin mr-1"></i> Loading...</span>
                            </button>
                        </div>
                    </div>
                    <p x-show="geocodeMessage" x-cloak x-text="geocodeMessage" class="text-sm text-[#198754] mt-3 mb-0 font-medium"></p>
                </div>
            </div>
        </div>

        {{-- BARIS 4: TOKENS & METADATA WAKTU --}}
        <div class="bg-white border border-gray-200 rounded shadow-sm mb-8">
            <div class="bg-[#f8f9fa] border-b border-gray-200 px-5 py-3">
                <h5 class="text-lg font-medium text-[#212529] m-0"><i class="fa fa-key text-[#6f42c1] mr-2"></i> API Tokens, Integration & Metadata</h5>
            </div>
            <div class="p-5 grid grid-cols-1 lg:grid-cols-2 gap-6">
                
                {{-- Token Section --}}
                <div class="space-y-4">
                    <div>
                        <label class="block text-base font-normal text-[#212529] mb-2">Expo Push Token</label>
                        <input type="text" name="expo_token" value="{{ old('expo_token', $user->expo_token) }}" 
                               class="block w-full px-4 py-2.5 text-base font-normal text-[#212529] bg-white border border-[#ced4da] rounded focus:border-[#86b7fe] focus:outline-none focus:ring-[4px] focus:ring-[#0d6efd]/25 transition font-mono text-sm">
                    </div>
                    <div>
                        <label class="block text-base font-normal text-[#212529] mb-2">DANA Access Token</label>
                        <input type="text" name="dana_access_token" value="{{ old('dana_access_token', $user->dana_access_token) }}" 
                               class="block w-full px-4 py-2.5 text-base font-normal text-[#212529] bg-white border border-[#ced4da] rounded focus:border-[#86b7fe] focus:outline-none focus:ring-[4px] focus:ring-[#0d6efd]/25 transition font-mono text-sm">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-base font-normal text-[#212529] mb-2">DANA Auth Code</label>
                            <input type="text" name="dana_auth_code" value="{{ old('dana_auth_code', $user->dana_auth_code) }}" 
                                   class="block w-full px-4 py-2.5 text-base font-normal text-[#212529] bg-white border border-[#ced4da] rounded focus:border-[#86b7fe] focus:outline-none focus:ring-[4px] focus:ring-[#0d6efd]/25 transition font-mono text-sm">
                        </div>
                        <div>
                            <label class="block text-base font-normal text-[#212529] mb-2">DANA Username</label>
                            <input type="text" name="dana_user_name" value="{{ old('dana_user_name', $user->dana_user_name) }}" 
                                   class="block w-full px-4 py-2.5 text-base font-normal text-[#212529] bg-white border border-[#ced4da] rounded focus:border-[#86b7fe] focus:outline-none focus:ring-[4px] focus:ring-[#0d6efd]/25 transition">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-base font-normal text-[#212529] mb-2">Setup Token</label>
                            <input type="text" name="setup_token" value="{{ old('setup_token', $user->setup_token) }}" 
                                   class="block w-full px-4 py-2.5 text-base font-normal text-[#212529] bg-white border border-[#ced4da] rounded focus:border-[#86b7fe] focus:outline-none focus:ring-[4px] focus:ring-[#0d6efd]/25 transition font-mono text-sm">
                        </div>
                        <div>
                            <label class="block text-base font-normal text-[#212529] mb-2">Reset Token</label>
                            <input type="text" name="reset_token" value="{{ old('reset_token', $user->reset_token) }}" 
                                   class="block w-full px-4 py-2.5 text-base font-normal text-[#212529] bg-white border border-[#ced4da] rounded focus:border-[#86b7fe] focus:outline-none focus:ring-[4px] focus:ring-[#0d6efd]/25 transition font-mono text-sm">
                        </div>
                    </div>
                </div>

                {{-- Waktu & Log Section --}}
                <div class="space-y-4">
                    <div>
                        <label class="block text-base font-normal text-[#212529] mb-2">Token Expiry</label>
                        <input type="text" name="token_expiry" value="{{ old('token_expiry', $user->token_expiry) }}" 
                               class="block w-full px-4 py-2.5 text-base font-normal text-[#212529] bg-white border border-[#ced4da] rounded focus:border-[#86b7fe] focus:outline-none focus:ring-[4px] focus:ring-[#0d6efd]/25 transition" placeholder="YYYY-MM-DD HH:MM:SS">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-base font-normal text-[#212529] mb-2">Created At</label>
                            <input type="text" name="created_at" value="{{ old('created_at', $user->created_at) }}" 
                                   class="block w-full px-4 py-2.5 text-base font-normal text-[#212529] bg-white border border-[#ced4da] rounded focus:border-[#86b7fe] focus:outline-none focus:ring-[4px] focus:ring-[#0d6efd]/25 transition" placeholder="YYYY-MM-DD HH:MM:SS">
                        </div>
                        <div>
                            <label class="block text-base font-normal text-[#212529] mb-2">Deleted At</label>
                            <input type="text" name="deleted_at" value="{{ old('deleted_at', $user->deleted_at) }}" 
                                   class="block w-full px-4 py-2.5 text-base font-normal text-[#212529] bg-white border border-[#ced4da] rounded focus:border-[#86b7fe] focus:outline-none focus:ring-[4px] focus:ring-[#0d6efd]/25 transition" placeholder="NULL">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-base font-normal text-[#212529] mb-2">Last Seen At (Timestamp)</label>
                            <input type="text" name="last_seen_at" value="{{ old('last_seen_at', $user->last_seen_at) }}" 
                                   class="block w-full px-4 py-2.5 text-base font-normal text-[#212529] bg-white border border-[#ced4da] rounded focus:border-[#86b7fe] focus:outline-none focus:ring-[4px] focus:ring-[#0d6efd]/25 transition" placeholder="YYYY-MM-DD HH:MM:SS">
                        </div>
                        <div>
                            <label class="block text-base font-normal text-[#212529] mb-2">Last Seen (DateTime)</label>
                            <input type="text" name="last_seen" value="{{ old('last_seen', $user->last_seen) }}" 
                                   class="block w-full px-4 py-2.5 text-base font-normal text-[#212529] bg-white border border-[#ced4da] rounded focus:border-[#86b7fe] focus:outline-none focus:ring-[4px] focus:ring-[#0d6efd]/25 transition" placeholder="YYYY-MM-DD HH:MM:SS">
                        </div>
                    </div>
                    <div>
                        <label class="block text-base font-normal text-[#212529] mb-2">IP Address Login Terakhir</label>
                        <input type="text" name="ip_address" value="{{ old('ip_address', $user->ip_address) }}" 
                               class="block w-full px-4 py-2.5 text-base font-normal text-[#212529] bg-white border border-[#ced4da] rounded focus:border-[#86b7fe] focus:outline-none focus:ring-[4px] focus:ring-[#0d6efd]/25 transition font-mono">
                    </div>
                    <div>
                        <label class="block text-base font-normal text-[#212529] mb-2">User Agent Login Terakhir</label>
                        <textarea name="user_agent" rows="2" class="block w-full px-4 py-2.5 text-base font-normal text-[#212529] bg-white border border-[#ced4da] rounded focus:border-[#86b7fe] focus:outline-none focus:ring-[4px] focus:ring-[#0d6efd]/25 transition">{{ old('user_agent', $user->user_agent) }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        {{-- ================= TOMBOL ACTION UTAMA ================= --}}
        <div class="flex flex-col-reverse md:flex-row justify-end gap-3 pb-10">
            <a href="{{ route('admin.customers.index') }}" class="inline-block px-6 py-3 bg-[#f8f9fa] border border-[#ced4da] text-[#212529] font-medium text-base leading-tight rounded hover:bg-[#e2e6ea] focus:bg-[#e2e6ea] focus:outline-none focus:ring-4 focus:ring-[#ced4da]/50 transition text-center">
                Batal
            </a>
            <button type="submit" class="inline-block px-8 py-3 bg-[#0d6efd] text-white font-medium text-base leading-tight rounded shadow-md hover:bg-[#0b5ed7] hover:shadow-lg focus:bg-[#0b5ed7] focus:shadow-lg focus:outline-none focus:ring-4 focus:ring-[#0d6efd]/50 active:bg-[#0a58ca] active:shadow-lg transition text-center">
                <i class="fa fa-save mr-2"></i> Simpan Semua Perubahan
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
                    this.geocodeMessage = 'Harap isi alamat lengkap (provinsi hingga jalan) terlebih dahulu.';
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
    [x-cloak] { display: none !important; }
</style>
@endpush