@extends('layouts.admin')

@section('title', 'Pengaturan Aplikasi')
@section('page-title', 'Pengaturan Aplikasi')

@push('styles')
{{-- Tambahkan CSS untuk Select2 --}}
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
{{-- Font Awesome --}}
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
    [x-cloak] { display: none !important; }
     /* Loader simple */
     .loader {
        border: 4px solid #f3f3f3; /* Light grey */
        border-top: 4px solid #3498db; /* Blue */
        border-radius: 50%;
        width: 1rem; height: 1rem;
        animation: spin 1s linear infinite; display: inline-block; vertical-align: middle;
     }
      @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
      /* Select2 adjustments */
      .select2-container--default .select2-selection--single { height: calc(1.5em + .75rem + 2px); padding: .375rem .75rem; border: 1px solid #d1d5db; border-radius: 0.375rem; }
      .select2-container--default .select2-selection--single .select2-selection__rendered { line-height: 1.5rem; padding-left: 0; }
      .select2-container--default .select2-selection--single .select2-selection__arrow { height: calc(1.5em + .75rem); }
      .select2-dropdown { border: 1px solid #d1d5db; border-radius: 0.375rem; }
      .select2-results__option--highlighted[aria-selected] { background-color: #60a5fa; } /* blue-400 */
       .select2-search__field { border: 1px solid #d1d5db !important; }

       /* Loading state for button */
       .is-loading { opacity: 0.7; pointer-events: none; }
       .is-loading::after { content: ''; display: inline-block; width: 1rem; height: 1rem; border: 2px solid currentColor; border-top-color: transparent; border-radius: 50%; animation: spin 0.6s linear infinite; margin-left: 0.5rem; vertical-align: middle;}

</style>
{{-- AlpineJS --}}
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
@endpush

@section('content')
<div class="container mx-auto px-4 sm:px-8 py-8">

    {{-- Gunakan AlpineJS untuk Tabbing --}}
    <div x-data="{ activeTab: window.location.hash ? window.location.hash.substring(1) : 'profile' }" class="w-full">
        <!-- Tab Headers -->
        <div class="mb-4 border-b border-gray-200 dark:border-gray-700">
            <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                <a href="#profile" @click.prevent="activeTab = 'profile'"
                        :class="{ 'border-indigo-500 text-indigo-600': activeTab === 'profile', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'profile' }"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm focus:outline-none">
                    Profil & Alamat Pengguna
                </a>
                {{-- Contoh Tab Lain (jika diperlukan) --}}
                {{--
                 <a href="#banners" @click.prevent="activeTab = 'banners'"
                         :class="{ 'border-indigo-500 text-indigo-600': activeTab === 'banners', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'banners' }"
                         class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm focus:outline-none">
                     Pengaturan Banner
                 </a>
                --}}
            </nav>
        </div>

        {{-- Flasher Notifikasi --}}
        @if (session('success'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)" x-transition
                 class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-md shadow-md relative" role="alert">
                <p>{{ session('success') }}</p>
                 <button type="button" @click="show = false" class="absolute top-0 bottom-0 right-0 px-4 py-3 focus:outline-none" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                 </button>
            </div>
        @endif
        @if (session('error'))
             <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)" x-transition
                  class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-md shadow-md relative" role="alert">
                 <p>{!! session('error') !!}</p>
                  <button type="button" @click="show = false" class="absolute top-0 bottom-0 right-0 px-4 py-3 focus:outline-none" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                 </button>
             </div>
        @endif

        @if ($errors->any())
            <div x-data="{ show: true }" x-show="show"
                 class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-md shadow-md relative" role="alert">
                <p class="font-bold mb-2">Terjadi kesalahan validasi:</p>
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                 <button type="button" @click="show = false" class="absolute top-0 bottom-0 right-0 px-4 py-3 focus:outline-none" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                 </button>
            </div>
        @endif


        <!-- Tab Content -->
        <div>
            {{-- 1. Tab Profil Pengguna --}}
            <div x-show="activeTab === 'profile'" x-cloak>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">

                    <!-- Kolom Kiri: Form Update Profil & Password -->
                    <div class="md:col-span-2 space-y-8">
                        {{-- Form Update Profil --}}
                        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-lg">
                            <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Informasi Profil</h3>
                            {{-- Action ke route admin.settings.profile.update --}}
                            <form action="{{ route('admin.settings.profile.update') }}" method="POST" enctype="multipart/form-data" x-data="{ photoName: null, photoPreview: null }">
                                @csrf
                                @method('PUT')
                                <div class="space-y-4">

                                    {{-- Input Foto Profil --}}
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Foto Profil / Logo Toko</label>
                                        <div class="mt-1 flex items-center space-x-4">
                                            <span class="inline-block h-16 w-16 rounded-full overflow-hidden bg-gray-100 dark:bg-gray-700">
                                                {{-- [PERBAIKAN] Pindahkan logika PHP ke sini --}}
                                                @php
                                                    // Default URL jika $user tidak ada
                                                    $profilePhotoUrl = 'https://ui-avatars.com/api/?name=Error&color=7F9CF5&background=EBF4FF';
                                                    if (isset($user)) {
                                                        $profilePhotoPath = $user->photo_profile ?? $user->store_logo_path;
                                                        // Gunakan Storage facade dengan benar
                                                        $profilePhotoExists = $profilePhotoPath && Illuminate\Support\Facades\Storage::disk('public')->exists($profilePhotoPath);
                                                        $profilePhotoUrl = $profilePhotoExists
                                                                            ? Illuminate\Support\Facades\Storage::url($profilePhotoPath)
                                                                            : 'https://ui-avatars.com/api/?name=' . urlencode($user->nama_lengkap ?? 'User') . '&color=7F9CF5&background=EBF4FF';
                                                    }
                                                @endphp
                                                <template x-if="!photoPreview">
                                                    {{-- Tampilkan gambar yang sudah ada atau default avatar --}}
                                                    <img class="h-full w-full object-cover text-gray-300" src="{{ $profilePhotoUrl }}" alt="Profil">
                                                </template>
                                                <template x-if="photoPreview">
                                                    {{-- Tampilkan preview gambar baru --}}
                                                    <span class="block w-full h-full bg-cover bg-no-repeat bg-center rounded-full" :style="'background-image: url(\'' + photoPreview + '\');'"></span>
                                                </template>
                                            </span>
                                            {{-- Sesuaikan 'name' input jika perlu --}}
                                            <input type="file" name="photo_profile" id="photo_profile" class="hidden" accept="image/jpeg,image/png,image/webp,image/jpg"
                                                   @change="
                                                       if ($event.target.files.length > 0) { // Pastikan file dipilih
                                                           photoName = $event.target.files[0].name;
                                                           const reader = new FileReader();
                                                           reader.onload = (e) => { photoPreview = e.target.result; };
                                                           reader.readAsDataURL($event.target.files[0]);
                                                       } else { // Jika batal pilih file
                                                           photoPreview = null;
                                                           photoName = null;
                                                       }
                                                   ">
                                            <label for="photo_profile" class="cursor-pointer py-2 px-3 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                                                Ganti Foto
                                            </label>
                                        </div>
                                         @error('photo_profile') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                    </div>

                                    {{-- Gunakan $user dan nama kolom dari tabel Pengguna --}}
                                    <div>
                                        <label for="nama_lengkap" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nama Lengkap</label>
                                        <input type="text" name="nama_lengkap" id="nama_lengkap" value="{{ old('nama_lengkap', $user->nama_lengkap ?? '') }}" required
                                               class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                         @error('nama_lengkap') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Alamat Email</label>
                                        <input type="email" name="email" id="email" value="{{ old('email', $user->email ?? '') }}" required
                                               class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                         @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="no_wa" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nomor WA</label>
                                        <input type="text" name="no_wa" id="no_wa" value="{{ old('no_wa', $user->no_wa ?? '') }}"
                                               class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Contoh: 08123456789">
                                        @error('no_wa') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                    </div>

                                    <div>
                                        <button type="submit" class="w-full inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                            Simpan Perubahan Profil
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>

                         {{-- Form Update Password --}}
                        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-lg">
                            <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Ubah Password</h3>
                             {{-- Action ke route admin.settings.password.update --}}
                            <form action="{{ route('admin.settings.password.update') }}" method="POST">
                                @csrf
                                @method('PUT')
                                <div class="space-y-4">
                                    <div>
                                        <label for="current_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Password Saat Ini</label>
                                        <input type="password" name="current_password" id="current_password" required
                                               class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        @error('current_password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Password Baru</label>
                                        <input type="password" name="password" id="password" required
                                               class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                         @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="password_confirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Konfirmasi Password Baru</label>
                                        <input type="password" name="password_confirmation" id="password_confirmation" required
                                               class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        {{-- Error konfirmasi biasanya terkait dengan field 'password' --}}
                                        @error('password')
                                            @if (str_contains($message, 'confirmation')) {{-- Cek jika errornya tentang konfirmasi --}}
                                                <p class="mt-1 text-sm text-red-600">Konfirmasi password baru tidak cocok.</p>
                                            @endif
                                        @enderror
                                    </div>
                                    <div>
                                        <button type="submit" class="w-full inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                            Ubah Password
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Kolom Kanan: Form Alamat -->
                    <div class="md:col-span-1 bg-white dark:bg-gray-800 p-6 rounded-lg shadow-lg">
                         <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Alamat Pengguna</h3>
                         <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Alamat ini akan digunakan sebagai alamat asal pengiriman default jika Anda bertindak sebagai penjual.</p>
                          {{-- Action ke route admin.settings.address.update --}}
                         <form action="{{ route('admin.settings.address.update') }}" method="POST" class="space-y-4" id="address-form">
                             @csrf
                             @method('PUT')

                             {{-- Input Detail Alamat --}}
                             <div>
                                 <label for="address_detail" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Detail Alamat Lengkap <span class="text-red-500">*</span></label>
                                 <textarea name="address_detail" id="address_detail" rows="3" required
                                           class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                           placeholder="Nama jalan, nomor rumah, RT/RW, kelurahan/desa...">{{ old('address_detail', $user->address_detail ?? '') }}</textarea>
                                 @error('address_detail') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                             </div>

                             {{-- Kolom Provinsi, Kabupaten, Kecamatan, Desa --}}
                             <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                  <div>
                                     <label for="province" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Provinsi <span class="text-red-500">*</span></label>
                                     <input type="text" name="province" id="province" value="{{ old('province', $user->province ?? '') }}" required readonly
                                            class="mt-1 block w-full border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-700 dark:text-white rounded-md shadow-sm cursor-not-allowed" placeholder="Pilih dari pencarian">
                                      @error('province') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                 </div>
                                  <div>
                                     <label for="regency" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Kabupaten/Kota <span class="text-red-500">*</span></label>
                                     <input type="text" name="regency" id="regency" value="{{ old('regency', $user->regency ?? '') }}" required readonly
                                            class="mt-1 block w-full border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-700 dark:text-white rounded-md shadow-sm cursor-not-allowed" placeholder="Pilih dari pencarian">
                                      @error('regency') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                 </div>
                                 <div>
                                     <label for="district" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Kecamatan <span class="text-red-500">*</span></label>
                                     <input type="text" name="district" id="district" value="{{ old('district', $user->district ?? '') }}" required readonly
                                            class="mt-1 block w-full border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-700 dark:text-white rounded-md shadow-sm cursor-not-allowed" placeholder="Pilih dari pencarian">
                                      @error('district') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                 </div>
                                  <div>
                                     <label for="village" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Desa/Kelurahan <span class="text-red-500">*</span></label>
                                     <input type="text" name="village" id="village" value="{{ old('village', $user->village ?? '') }}" required readonly
                                            class="mt-1 block w-full border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-700 dark:text-white rounded-md shadow-sm cursor-not-allowed" placeholder="Pilih dari pencarian">
                                      @error('village') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                 </div>
                             </div>

                             {{-- Kode Pos --}}
                             <div>
                                 <label for="postal_code" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Kode Pos <span class="text-red-500">*</span></label>
                                 <input type="text" name="postal_code" id="postal_code" value="{{ old('postal_code', $user->postal_code ?? '') }}" required readonly
                                        class="mt-1 block w-full border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-700 dark:text-white rounded-md shadow-sm cursor-not-allowed" placeholder="Pilih dari pencarian">
                                  @error('postal_code') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                             </div>

                              {{-- Pencarian Alamat KiriminAja --}}
                              <div>
                                  <label for="address_search" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Cari & Isi Otomatis Alamat</label>
                                  <div class="relative mt-1">
                                      <select id="address_search" class="block w-full border-gray-300 rounded-md shadow-sm" style="width: 100%;">
                                          {{-- Select2 akan mengisi ini --}}
                                      </select>
                                      <span id="search-loading" class="absolute right-8 top-1/2 -translate-y-1/2 hidden">
                                          <div class="loader !w-4 !h-4 !border-2"></div>
                                      </span>
                                  </div>
                                  <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Ketik min. 3 huruf nama kecamatan atau kelurahan.</p>
                              </div>


                              {{-- Latitude & Longitude --}}
                             <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 items-end">
                                  <div class="sm:col-span-1">
                                     <label for="latitude" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Latitude</label>
                                     <input type="text" name="latitude" id="latitude" value="{{ old('latitude', $user->latitude ?? '') }}"
                                            class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="-7.xxxxxx">
                                     @error('latitude') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                 </div>
                                  <div class="sm:col-span-1">
                                     <label for="longitude" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Longitude</label>
                                     <input type="text" name="longitude" id="longitude" value="{{ old('longitude', $user->longitude ?? '') }}"
                                            class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="111.xxxxxx">
                                     @error('longitude') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                 </div>
                                  <div class="sm:col-span-1">
                                       <button type="button" id="geocode-button" class="w-full inline-flex justify-center py-2 px-4 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                           <i class="fas fa-map-marker-alt mr-2"></i> Dapatkan Koordinat
                                       </button>
                                  </div>
                             </div>
                             <div class="text-center">
                                 <span id="geocode-status" class="text-xs text-gray-500 dark:text-gray-400 mt-1 block"></span>
                            </div>
                              <p class="text-xs text-gray-500 dark:text-gray-400">Koordinat (Latitude & Longitude) diperlukan untuk pengiriman Instan. Jika kosong, sistem akan mencoba mencari otomatis saat menyimpan berdasarkan alamat lengkap.</p>


                             {{-- Hidden fields for KiriminAja IDs --}}
                             <input type="hidden" name="kiriminaja_district_id" id="kiriminaja_district_id" value="{{ old('kiriminaja_district_id', $user->kiriminaja_district_id ?? '') }}">
                             <input type="hidden" name="kiriminaja_subdistrict_id" id="kiriminaja_subdistrict_id" value="{{ old('kiriminaja_subdistrict_id', $user->kiriminaja_subdistrict_id ?? '') }}">

                             <div>
                                 <button type="submit" id="save-address-button" class="w-full inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                     Simpan Alamat Pengguna
                                 </button>
                             </div>
                         </form>
                    </div>

                </div>
            </div>

             {{-- 2. Tab Pengaturan Banner --}}
             <div x-show="activeTab === 'banners'" x-cloak>
                 {{-- Pindahkan konten pengaturan banner dari file sebelumnya ke sini --}}
                 <div class="space-y-8">
                     {{-- Pengaturan Gambar Banner Statis --}}
                     <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
                         <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-6">Banner Statis (Banner 2 & 3)</h3>
                         {{-- Action ke route admin.settings.update (untuk banner statis) --}}
                         <form action="{{ route('admin.settings.update') }}" method="POST" enctype="multipart/form-data" class="space-y-6" id="banner-settings-form">
                             @csrf
                             @method('PUT') {{-- Sesuaikan dengan method route Anda --}}
                             <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                 @foreach(['banner_2','banner_3'] as $key)
                                 <div class="space-y-2">
                                     <label class="block font-medium mb-1">{{ str_replace('_', ' ', Str::title($key)) }}</label>
                                     {{-- Dropzone untuk Banner Statis --}}
                                     <div class="dropzone-custom border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-xl p-6 flex flex-col items-center justify-center cursor-pointer text-center"
                                          id="{{ $key }}-dropzone">
                                         @if(!empty($settings[$key]) && Storage::disk('public')->exists($settings[$key]))
                                             <img src="{{ asset('storage/'.$settings[$key]) }}" class="w-24 h-auto object-contain mb-2 rounded" alt="Current {{ $key }}">
                                             <span id="{{ $key }}-message" class="text-sm text-gray-500 dark:text-gray-400">Klik atau seret file baru ke sini untuk mengganti</span>
                                         @else
                                             <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 mb-2"></i>
                                             <span id="{{ $key }}-message" class="text-sm font-semibold text-blue-600">Klik atau seret file ke sini</span>
                                         @endif
                                         <input type="file" name="{{ $key }}" id="{{ $key }}-input" class="hidden" accept="image/jpeg,image/png,image/webp">
                                         <img id="{{ $key }}-preview" class="mt-2 w-24 h-auto object-contain rounded hidden"/>
                                     </div>
                                     <small class="text-gray-500 dark:text-gray-400 block mt-1 text-center">
                                         Maks 2MB. Rekomendasi: Rasio 16:9 (misal 400x210)
                                     </small>
                                     @error($key) <p class="mt-1 text-sm text-red-600 text-center">{{ $message }}</p> @enderror
                                 </div>
                                 @endforeach
                             </div>
                             <button type="submit" class="mt-4 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition">Simpan Banner Statis</button>
                         </form>
                     </div>

                     {{-- Pengaturan Banner Slider (Etalase) --}}
                     <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 space-y-4">
                         <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Banner Slider Etalase</h3>

                         {{-- Form Add New Banner --}}
                         <form action="{{ route('admin.settings.banners.store') }}" method="POST" enctype="multipart/form-data" class="space-y-2 pb-6 border-b dark:border-gray-700" id="add-banner-form">
                             @csrf
                             <label class="block font-medium mb-1">Tambah Banner Baru</label>
                             {{-- Dropzone untuk Banner Slider --}}
                              <div class="dropzone-custom border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-xl p-6 flex flex-col items-center justify-center cursor-pointer text-center"
                                   id="banner-dropzone"> {{-- ID 'banner' akan bentrok, ganti ke 'add_banner' --}}
                                   <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 mb-2"></i>
                                   <span id="add_banner-message" class="text-sm font-semibold text-blue-600">Klik atau seret file ke sini</span>
                                   <input type="file" name="image" id="add_banner-input" class="hidden" accept="image/jpeg,image/png,image/webp" required>
                                   <img id="add_banner-preview" class="mt-2 w-40 h-auto object-contain rounded hidden"/>
                              </div>
                             <small class="text-gray-500 dark:text-gray-400 block mt-1 text-center">Maks 2MB. Rekomendasi: Rasio 2:1 (misal 900x450)</small>
                             @error('image', 'storeBanner') <p class="mt-1 text-sm text-red-600 text-center">{{ $message }}</p> @enderror

                             <button type="submit" class="mt-4 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition">Tambah Banner</button>
                         </form>

                          {{-- Table Banners --}}
                          <div x-data="bannerEditModal()" class="mt-8">
                              <h4 class="text-md font-semibold mb-4">Daftar Banner Etalase</h4>
                              <div class="overflow-x-auto border rounded-lg dark:border-gray-700">
                                 <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                     <thead class="bg-gray-50 dark:bg-gray-700">
                                         <tr>
                                             <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">#</th>
                                             <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Gambar</th>
                                             <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Aksi</th>
                                         </tr>
                                     </thead>
                                     <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                         @forelse($banners as $index => $banner)
                                         <tr>
                                             <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $loop->iteration }}</td>
                                             <td class="px-6 py-4 whitespace-nowrap">
                                                 {{-- Gunakan pengecekan Storage::url --}}
                                                 <img src="{{ $banner->image && Storage::disk('public')->exists($banner->image) ? Storage::url($banner->image) : 'https://placehold.co/128x64?text=No+Image' }}"
                                                      class="h-16 w-32 object-contain rounded bg-gray-100 dark:bg-gray-700" alt="Banner {{ $banner->id }}">
                                             </td>
                                             <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium space-x-2">
                                                 <button @click="openModal({{ $banner->id }}, '{{ $banner->image && Storage::disk('public')->exists($banner->image) ? Storage::url($banner->image) : 'https://placehold.co/320x160?text=No+Image' }}')"
                                                         class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300" title="Edit">
                                                     <i class="fas fa-edit"></i> Edit
                                                 </button>
                                                 {{-- Action ke route admin.settings.banners.destroy --}}
                                                 <form action="{{ route('admin.settings.banners.destroy', $banner->id) }}" method="POST" class="inline-block m-0" onsubmit="return confirm('Yakin ingin menghapus banner ini?');">
                                                     @csrf
                                                     @method('DELETE')
                                                     <button type="submit" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300" title="Hapus">
                                                          <i class="fas fa-trash-alt"></i> Hapus
                                                     </button>
                                                 </form>
                                             </td>
                                         </tr>
                                         @empty
                                         <tr>
                                             <td colspan="3" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 text-center">Belum ada banner etalase.</td>
                                         </tr>
                                         @endforelse
                                     </tbody>
                                 </table>
                              </div>

                              {{-- Modal Edit Banner --}}
                              <div x-show="isOpen" @keydown.escape.window="closeModal()" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true" x-cloak>
                                 <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                                     <div x-show="isOpen" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="closeModal()" aria-hidden="true"></div>
                                     <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                                     <div x-show="isOpen" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                                          class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                                         {{-- Action ke route admin.settings.banners.update --}}
                                         <form :action="updateUrl" method="POST" enctype="multipart/form-data">
                                             @csrf
                                             @method('PUT')
                                             <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                                                 <div class="sm:flex sm:items-start">
                                                     <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                                         <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white" id="modal-title">
                                                             Edit Banner
                                                         </h3>
                                                         <div class="mt-4 space-y-4">
                                                             <div class="dropzone-custom border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-xl p-6 flex flex-col items-center justify-center cursor-pointer text-center"
                                                                  @click="$refs.modalInput.click()" @dragover.prevent="dragOver=true" @dragleave.prevent="dragOver=false"
                                                                  @drop.prevent="handleDrop($event)" :class="{'border-blue-500 bg-blue-50 dark:bg-gray-700': dragOver}">
                                                                 <img x-show="preview" :src="preview" class="mb-2 w-40 h-auto object-contain rounded" alt="Preview"/>
                                                                 <span x-text="message" class="text-sm font-semibold text-blue-600"></span>
                                                                  <input type="file" x-ref="modalInput" name="image" class="hidden" @change="previewFile" accept="image/jpeg,image/png,image/webp" required>
                                                             </div>
                                                              <small class="text-gray-500 dark:text-gray-400 block text-center">Maks 2MB. Rekomendasi: Rasio 2:1 (misal 900x450)</small>
                                                               {{-- Error handling untuk modal update banner --}}
                                                              @if($errors->hasBag('updateBanner') && session('edit_banner_id'))
                                                                 <div x-show="currentBannerId == {{ session('edit_banner_id') }}">
                                                                      @foreach($errors->updateBanner->get('image') as $error)
                                                                         <p class="mt-1 text-sm text-red-600 text-center">{{ $error }}</p>
                                                                      @endforeach
                                                                 </div>
                                                              @endif
                                                         </div>
                                                     </div>
                                                 </div>
                                             </div>
                                             <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                                                 <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                                                     Update Banner
                                                 </button>
                                                 <button @click="closeModal()" type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                                     Batal
                                                 </button>
                                             </div>
                                         </form>
                                     </div>
                                 </div>
                              </div>
                              {{-- Akhir Modal Edit Banner --}}
                          </div>


                     </div>
                 </div>
             </div>


            {{-- Tab Slider & Customer (Placeholder) --}}
            {{-- Tambahkan konten untuk tab lain di sini jika diperlukan --}}

        </div>
    </div>
</div>
@endsection


@push('scripts')
{{-- jQuery & Select2 --}}
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
{{-- AlpineJS sudah di-push di styles --}}

<script>
    // --- Script untuk Alert Dismiss (jQuery version) ---
     $(document).ready(function() {
        // Dismiss alert standar Bootstrap style
         $('[data-dismiss="alert"]').on('click', function() {
             $(this).closest('.alert-dismissible').fadeOut();
         });
         // Alpine alert dismiss sudah dihandle inline
     });


    // --- Script untuk Alamat & KiriminAja ---
    $(document).ready(function() {
        const addressSearchSelect = $('#address_search');
        const provinceInput = $('#province');
        const regencyInput = $('#regency');
        const districtInput = $('#district');
        const villageInput = $('#village');
        const postalCodeInput = $('#postal_code');
        const districtIdInput = $('#kiriminaja_district_id');
        const subdistrictIdInput = $('#kiriminaja_subdistrict_id');
        const searchLoading = $('#search-loading');

         // Fungsi untuk menampilkan error global (jika belum ada)
         function showGlobalError(message) {
             let errorDiv = $('#ajax-error-message');
             if (!errorDiv.length) {
                  // Prepend error message div if it doesn't exist
                   $('<div id="ajax-error-message" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4 hidden" role="alert"></div>')
                     .prependTo('.container'); // Prepend to main container
                 errorDiv = $('#ajax-error-message'); // Re-select
             }
             // Use html() to allow potential HTML in the message
             errorDiv.html('<strong>Gagal!</strong> ' + message).removeClass('hidden').slideDown();
            setTimeout(() => { errorDiv.slideUp(() => errorDiv.addClass('hidden')); }, 7000); // Longer timeout
         }


        addressSearchSelect.select2({
            placeholder: "Ketik min. 3 huruf Kecamatan/Kelurahan...",
            minimumInputLength: 3,
            ajax: {
                url: "{{ route('admin.settings.address.search') }}", // Pastikan route ini ada
                type: "POST",
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return {
                        _token: "{{ csrf_token() }}",
                        query: params.term
                    };
                },
                processResults: function (data) {
                     searchLoading.addClass('hidden');
                    if (data.success && data.data && data.data.length > 0) { // Check if data array is not empty
                        return {
                            results: data.data.map(item => ({
                                 id: JSON.stringify(item),
                                text: item.text // Teks dari backend (label)
                            }))
                        };
                    } else {
                         // Tampilkan pesan "Tidak ditemukan" jika array data kosong atau success false
                         return { results: [{ id: '', text: data.message || 'Tidak ditemukan', disabled: true }] };
                    }
                },

                beforeSend: function() {
                     searchLoading.removeClass('hidden');
                },
                 error: function(jqXHR, status, error) { // Tambahkan error handling AJAX Select2
                     searchLoading.addClass('hidden');
                     console.error("Select2 AJAX error:", status, error, jqXHR.responseText);
                     // Menampilkan pesan error yang lebih spesifik jika memungkinkan
                     let errMsg = "Gagal memuat data alamat.";
                     if (jqXHR.responseJSON && jqXHR.responseJSON.message) {
                         errMsg = jqXHR.responseJSON.message;
                     } else if (jqXHR.statusText) {
                         errMsg += ` (${jqXHR.statusText})`;
                     }
                     showGlobalError(errMsg);
                 },
                cache: true
            }
        });

        addressSearchSelect.on('select2:select', function (e) {
            const dataString = e.params.data.id;
            if (!dataString) return;

            try {
                const selectedAddress = JSON.parse(dataString);

                provinceInput.val(selectedAddress.province || '');
                regencyInput.val(selectedAddress.city || ''); // city = kab/kota
                districtInput.val(selectedAddress.subdistrict || ''); // subdistrict = kecamatan
                villageInput.val(selectedAddress.village || ''); // village = desa/kel
                postalCodeInput.val(selectedAddress.zip_code || '');
                districtIdInput.val(selectedAddress.district_id || ''); // ID Kecamatan
                subdistrictIdInput.val(selectedAddress.subdistrict_id || ''); // ID Kelurahan

                 addressSearchSelect.val(null).trigger('change'); // Reset Select2

            } catch (error) {
                console.error("Error parsing selected address data:", error);
                showGlobalError("Gagal memproses data alamat terpilih.");
            }
        });

        // --- Geocoding ---
         const geocodeButton = $('#geocode-button');
         const latitudeInput = $('#latitude');
         const longitudeInput = $('#longitude');
         const geocodeStatus = $('#geocode-status');
         const addressDetailInput = $('#address_detail'); // Pastikan ID ini benar

         geocodeButton.on('click', function() {
             const btn = $(this);
             // Buat string alamat lengkap dari form
             const addressParts = [
                 addressDetailInput.val(), // Gunakan detail alamat sebagai bagian utama
                 villageInput.val(),
                 districtInput.val(),
                 regencyInput.val(),
                 provinceInput.val(),
                 postalCodeInput.val()
             ];
             // Filter out empty parts before joining
             const fullAddress = addressParts.filter(part => part && part.trim() !== '').join(', ');

             if (fullAddress.length < 10) {
                  geocodeStatus.text('Masukkan alamat lebih lengkap (termasuk detail).').addClass('text-red-500').removeClass('text-green-500');
                  return;
             }

             geocodeStatus.text('Mencari koordinat...').removeClass('text-red-500 text-green-500');
             btn.addClass('is-loading').prop('disabled', true); // Tampilkan loading di tombol

             $.ajax({
                 url: "{{ route('admin.settings.address.geocode') }}", // Pastikan route ini ada
                 method: 'POST',
                 data: {
                     _token: "{{ csrf_token() }}",
                     address: fullAddress
                 },
                 success: function(response) {
                     if (response.success && response.data) {
                         latitudeInput.val(response.data.lat.toFixed(6)); // Format 6 angka desimal
                         longitudeInput.val(response.data.lng.toFixed(6));
                         geocodeStatus.text('Koordinat ditemukan!').addClass('text-green-500').removeClass('text-red-500');
                     } else {
                         geocodeStatus.text(response.message || 'Koordinat tidak ditemukan. Coba perbaiki alamat.').addClass('text-red-500').removeClass('text-green-500');
                     }
                 },
                 error: function(xhr) {
                     const errorMsg = xhr.responseJSON?.message || 'Gagal menghubungi server geocoding.';
                     geocodeStatus.text(errorMsg).addClass('text-red-500').removeClass('text-green-500');
                 },
                 complete: function() {
                     btn.removeClass('is-loading').prop('disabled', false); // Hapus loading dari tombol
                 }
             });
         });

         // --- Image Preview Setup ---
         // (Fungsi setupImagePreview dan showPreview dipindahkan ke sini agar rapi)
         function setupImagePreview(key) {
             const dropzone = document.getElementById(`${key}-dropzone`);
             const input = document.getElementById(`${key}-input`);
             const preview = document.getElementById(`${key}-preview`);
             const message = document.getElementById(`${key}-message`);
             const existingImage = dropzone ? dropzone.querySelector('img[src^="{{ asset('storage/') }}"]') : null;
             const iconEl = dropzone ? dropzone.querySelector('i') : null;


             if (!dropzone || !input || !preview || !message) {
                 // console.warn(`Element missing for image preview setup: key=${key}`);
                 return;
             }

             dropzone.addEventListener('click', (e) => {
                  // Only trigger if clicking on the placeholder text/icon, not the existing image
                 if (e.target === message || e.target === iconEl) {
                    input.click();
                 }
             });
             dropzone.addEventListener('dragover', e => { e.preventDefault(); dropzone.classList.add('dropzone-dragging'); });
             dropzone.addEventListener('dragleave', e => { e.preventDefault(); dropzone.classList.remove('dropzone-dragging'); });
             dropzone.addEventListener('drop', e => {
                 e.preventDefault();
                 dropzone.classList.remove('dropzone-dragging');
                 if (e.dataTransfer.files.length) {
                     input.files = e.dataTransfer.files;
                     showPreview(input.files[0], preview, message, existingImage, iconEl);
                 }
             });
             input.addEventListener('change', () => {
                 if (input.files.length) showPreview(input.files[0], preview, message, existingImage, iconEl);
                 else { // Handle case where user cancels file selection
                    preview.classList.add('hidden');
                    preview.src = '#'; // Clear src
                    message.classList.remove('hidden');
                    if(existingImage) existingImage.classList.remove('hidden');
                    if(iconEl) iconEl.classList.remove('hidden');
                 }
             });
         }

         function showPreview(file, previewEl, messageEl, existingImageEl, iconEl) {
            if (!file) return; // Exit if no file
            if (!file.type.startsWith('image/')) {
                 alert('Hanya file gambar yang diperbolehkan!');
                  // Reset input value if invalid file type selected
                 const inputElement = previewEl.previousElementSibling; // Find the input element
                 if (inputElement && inputElement.type === 'file') {
                     inputElement.value = null;
                 }
                 return;
             }
            const reader = new FileReader();
            reader.onload = e => {
                previewEl.src = e.target.result;
                previewEl.classList.remove('hidden');
                messageEl.classList.add('hidden');
                if(existingImageEl) existingImageEl.classList.add('hidden');
                if(iconEl) iconEl.classList.add('hidden');
            }
            reader.readAsDataURL(file);
        }

        // Initialize previews for all zones on page load
         ['logo','banner_2','banner_3', 'add_banner'].forEach(key => { // Ganti 'banner' ke 'add_banner'
             setupImagePreview(key);
         });


    }); // End $(document).ready

    // --- Script untuk AlpineJS Modal Edit Banner ---
    // Pastikan ini dieksekusi setelah AlpineJS dimuat
    document.addEventListener('alpine:init', () => {
        Alpine.data('bannerEditModal', () => ({
            isOpen: false,
            preview: null,
            message: 'Klik atau seret file baru ke sini',
            dragOver: false,
            updateUrl: '',
            currentBannerId: null,
            openModal(id, currentImage) {
                this.currentBannerId = id;
                this.isOpen = true;
                this.preview = currentImage;
                this.updateUrl = `/admin/settings/banners/${id}`; // Sesuaikan URL
                 this.message = 'Klik atau seret file baru untuk mengganti';
                 if (this.$refs.modalInput) this.$refs.modalInput.value = null;

                  // Cek error dari session flash
                  const errorBag = @json($errors->updateBanner ?? null); // Error bag spesifik
                  const errorBannerId = {{ session('edit_banner_id', 'null') }};
                  if (errorBag && errorBannerId == id) {
                      // Tampilkan pesan error pertama jika ada
                      const firstError = Object.values(errorBag)[0] ? Object.values(errorBag)[0][0] : 'Terjadi kesalahan.';
                      // Tampilkan error (bisa menggunakan alert atau elemen di modal)
                      // alert(firstError);
                      // Atau tambahkan elemen error di modal dan tampilkan:
                      // this.$nextTick(() => {
                      //    const errorEl = this.$refs.modalError; // Tambahkan ref="modalError" ke elemen P
                      //    if(errorEl) {
                      //        errorEl.textContent = firstError;
                      //        errorEl.classList.remove('hidden');
                      //    }
                      // });
                  }
            },
            closeModal() {
                this.isOpen = false;
                this.preview = null;
                this.dragOver = false;
                this.currentBannerId = null;
                 // Sembunyikan pesan error jika ada
                 // const errorEl = this.$refs.modalError;
                 // if(errorEl) errorEl.classList.add('hidden');
            },
            previewFile(event) {
                const file = event.target.files[0];
                if (!file) {
                     // Jika batal, kembalikan ke state awal (mungkin perlu gambar asli)
                     this.message = 'Klik atau seret file baru ke sini';
                     // this.preview = originalImage; // Perlu simpan original image
                     return;
                 }
                 if (!file.type.startsWith('image/')) {
                     alert('Hanya file gambar yang diperbolehkan!');
                      event.target.value = null;
                     return;
                 }
                const reader = new FileReader();
                reader.onload = e => {
                    this.preview = e.target.result;
                    this.message = file.name;
                };
                reader.readAsDataURL(file);
            },
            handleDrop(event) {
                 this.dragOver = false;
                const file = event.dataTransfer.files[0];
                if (!file) return;
                 if (!file.type.startsWith('image/')) {
                     alert('Hanya file gambar yang diperbolehkan!');
                     return;
                 }
                // Pastikan $refs.modalInput ada sebelum akses files
                if (this.$refs.modalInput) {
                    this.$refs.modalInput.files = event.dataTransfer.files;
                    this.previewFile({ target: this.$refs.modalInput }); // Trigger preview
                } else {
                     console.error("modalInput ref not found!");
                 }
            }
        }));
    });


</script>
@endpush

