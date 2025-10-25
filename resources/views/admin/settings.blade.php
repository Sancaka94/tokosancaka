@extends('layouts.admin')

@section('title', 'Pengaturan Aplikasi')
@section('page-title', 'Pengaturan Aplikasi')

@push('styles')
{{-- Tambahkan CSS untuk Select2 --}}
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
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
    <div x-data="{ activeTab: 'profile' }" class="w-full">
        <!-- Tab Headers -->
        <div class="mb-4 border-b border-gray-200 dark:border-gray-700">
            <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                <button @click="activeTab = 'profile'"
                        :class="{ 'border-indigo-500 text-indigo-600': activeTab === 'profile', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'profile' }"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm focus:outline-none">
                    Profil & Alamat Pengguna
                </button>
                {{-- Tambahkan tab lain jika diperlukan, misal untuk pengaturan umum, banner, dll. --}}
                {{--
                <button @click="activeTab = 'slider'"
                        :class="{ 'border-indigo-500 text-indigo-600': activeTab === 'slider', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'slider' }"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm focus:outline-none">
                    Slider Informasi
                </button>
                <button @click="activeTab = 'customer'"
                        :class="{ 'border-indigo-500 text-indigo-600': activeTab === 'customer', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'customer' }"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm focus:outline-none">
                    Pengaturan Pelanggan
                </button>
                 --}}
            </nav>
        </div>

        {{-- Flasher Notifikasi --}}
        @if (session('success'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)" x-transition
                 class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-md shadow-md alert-dismissible" role="alert">
                <p>{{ session('success') }}</p>
                 <button type="button" @click="show = false" class="absolute top-0 bottom-0 right-0 px-4 py-3" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                 </button>
            </div>
        @endif
        @if (session('error'))
             <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)" x-transition
                  class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-md shadow-md alert-dismissible" role="alert">
                 <p>{!! session('error') !!}</p>
                  <button type="button" @click="show = false" class="absolute top-0 bottom-0 right-0 px-4 py-3" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                 </button>
             </div>
        @endif

        @if ($errors->any())
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-md shadow-md alert-dismissible" role="alert">
                <p class="font-bold mb-2">Terjadi kesalahan validasi:</p>
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                 <button type="button" data-dismiss="alert" class="absolute top-0 bottom-0 right-0 px-4 py-3" aria-label="Close">
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
                            {{-- Pastikan route ini ada dan mengarah ke method update profil di controller --}}
                            <form action="{{ route('admin.settings.profile.update') }}" method="POST" enctype="multipart/form-data" x-data="{ photoName: null, photoPreview: null }">
                                @csrf
                                @method('PUT') {{-- Gunakan PUT atau PATCH sesuai definisi route Anda --}}
                                <div class="space-y-4">

                                    {{-- Input Foto Profil --}}
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Foto Profil / Logo Toko</label>
                                        <div class="mt-1 flex items-center space-x-4">
                                            <span class="inline-block h-16 w-16 rounded-full overflow-hidden bg-gray-100 dark:bg-gray-700">
                                                @php
                                                    if (isset($user)) {
                                                        $profilePhotoPath = $user->photo_profile ?? $user->store_logo_path;
                                                        $profilePhotoUrl = $profilePhotoPath && Illuminate\Support\Facades\Storage::disk('public')->exists($profilePhotoPath)
                                                                            ? Illuminate\Support\Facades\Storage::url($profilePhotoPath)
                                                                            : 'https://ui-avatars.com/api/?name=' . urlencode($user->nama_lengkap ?? 'User') . '&color=7F9CF5&background=EBF4FF';
                                                    } else {
                                                        $profilePhotoUrl = 'https://ui-avatars.com/api/?name=Error&color=7F9CF5&background=EBF4FF';
                                                    }
                                                @endphp
                                                <template x-if="!photoPreview">
                                                    <img class="h-full w-full object-cover text-gray-300" src="{{ $profilePhotoUrl }}" alt="Profil">
                                                </template>
                                                <template x-if="photoPreview">
                                                    <span class="block w-full h-full bg-cover bg-no-repeat bg-center rounded-full" :style="'background-image: url(\'' + photoPreview + '\');'"></span>
                                                </template>
                                            </span>
                                            <input type="file" name="photo_profile" id="photo_profile" class="hidden" accept="image/jpeg,image/png,image/webp,image/jpg"
                                                   @change="
                                                       if ($event.target.files.length > 0) {
                                                           photoName = $event.target.files[0].name;
                                                           const reader = new FileReader();
                                                           reader.onload = (e) => { photoPreview = e.target.result; };
                                                           reader.readAsDataURL($event.target.files[0]);
                                                       } else { photoPreview = null; photoName = null; }
                                                   ">
                                            <label for="photo_profile" class="cursor-pointer py-2 px-3 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                                                Ganti Foto
                                            </label>
                                        </div>
                                         @error('photo_profile') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                    </div>

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
                         {{-- Pastikan route ini ada dan mengarah ke method update alamat di controller --}}
                         <form action="{{ route('admin.settings.address.update') }}" method="POST" class="space-y-4" id="address-form">
                             @csrf
                             @method('PUT') {{-- Gunakan PUT atau PATCH --}}

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
    // --- Script untuk Alert Dismiss ---
    document.querySelectorAll('.alert-dismissible [data-dismiss="alert"]').forEach(button => {
        button.addEventListener('click', function() {
            this.closest('.alert-dismissible').style.display = 'none';
        });
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
             const errorDiv = $('#ajax-error-message'); // Asumsi div ini ada di layout
             if (!errorDiv.length) { // Buat jika belum ada
                  $('<div id="ajax-error-message" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4 hidden" role="alert"></div>').insertBefore('#cart-content-area'); // Sesuaikan selector insertBefore
             }
             errorDiv.html('<strong>Error:</strong> ' + message).removeClass('hidden').slideDown();
            setTimeout(() => { errorDiv.slideUp(() => errorDiv.addClass('hidden')); }, 5000);
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
                    if (data.success && data.data) {
                        return {
                            results: data.data.map(item => ({
                                 id: JSON.stringify(item),
                                text: item.text
                            }))
                        };
                    } else {
                         return { results: [{ id: '', text: data.message || 'Tidak ditemukan', disabled: true }] };
                    }
                },
                beforeSend: function() {
                     searchLoading.removeClass('hidden');
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
                regencyInput.val(selectedAddress.city || '');
                districtInput.val(selectedAddress.subdistrict || '');
                villageInput.val(selectedAddress.village || '');
                postalCodeInput.val(selectedAddress.zip_code || '');
                districtIdInput.val(selectedAddress.district_id || '');
                subdistrictIdInput.val(selectedAddress.subdistrict_id || '');

                 addressSearchSelect.val(null).trigger('change');

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
             const fullAddress = addressParts.filter(Boolean).join(', '); // Gabungkan bagian yang tidak kosong

             if (fullAddress.length < 10) {
                  geocodeStatus.text('Masukkan alamat lebih lengkap.').addClass('text-red-500').removeClass('text-green-500');
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
                         geocodeStatus.text(response.message || 'Koordinat tidak ditemukan.').addClass('text-red-500').removeClass('text-green-500');
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

    });

     // Script untuk preview foto profil AlpineJS
     // (Sudah inline di dalam tag <form>)

</script>
@endpush

