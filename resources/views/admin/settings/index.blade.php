@extends('layouts.admin')

@section('title', 'Pengaturan Marketplace')
@section('page-title', 'Pengaturan Marketplace')

@push('styles')
{{-- Tambahkan CSS untuk Select2 jika Anda menggunakannya --}}
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    .dropzone-custom { transition: all 0.2s ease-in-out; }
    .dropzone-custom:hover { background-color: #f9fafb; /* gray-50 */ border-color: #9ca3af; /* gray-400 */ }
    .dropzone-dragging { background-color: #eff6ff; /* blue-50 */ border-color: #3b82f6; /* blue-500 */ }
    .select2-container--default .select2-selection--single { height: calc(1.5em + .75rem + 2px); padding: .375rem .75rem; border: 1px solid #d1d5db; border-radius: 0.375rem; }
    .select2-container--default .select2-selection--single .select2-selection__rendered { line-height: 1.5rem; }
    .select2-container--default .select2-selection--single .select2-selection__arrow { height: calc(1.5em + .75rem); }
    .select2-dropdown { border: 1px solid #d1d5db; border-radius: 0.375rem; }
    .select2-results__option--highlighted[aria-selected] { background-color: #3b82f6; }

     /* Loader simple */
     .loader {
        border: 4px solid #f3f3f3; /* Light grey */
        border-top: 4px solid #3498db; /* Blue */
        border-radius: 50%;
        width: 1rem; height: 1rem;
        animation: spin 1s linear infinite; display: inline-block; vertical-align: middle;
     }
      @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
      .is-loading { opacity: 0.7; pointer-events: none; }
      .is-loading::after { content: ''; display: inline-block; width: 1rem; height: 1rem; border: 2px solid currentColor; border-top-color: transparent; border-radius: 50%; animation: spin 0.6s linear infinite; margin-left: 0.5rem; vertical-align: middle;}
</style>
@endpush

@section('content')
<div class="container mx-auto px-4 sm:px-8 py-8 space-y-8">

    {{-- Notifikasi --}}
    @if (session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4 alert-dismissible" role="alert">
            <strong class="font-bold">Berhasil!</strong>
            <span class="block sm:inline">{{ session('success') }}</span>
            <button type="button" class="absolute top-0 bottom-0 right-0 px-4 py-3" data-dismiss="alert" aria-label="Close">
                 <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif
    @if (session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4 alert-dismissible" role="alert">
            <strong class="font-bold">Gagal!</strong>
            <span class="block sm:inline">{!! session('error') !!}</span>
             <button type="button" class="absolute top-0 bottom-0 right-0 px-4 py-3" data-dismiss="alert" aria-label="Close">
                 <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif
     @if ($errors->any())
         <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4 alert-dismissible" role="alert">
             <strong class="font-bold">Error Validasi!</strong>
             <ul>
                 @foreach ($errors->all() as $error)
                     <li>{{ $error }}</li>
                 @endforeach
             </ul>
              <button type="button" class="absolute top-0 bottom-0 right-0 px-4 py-3" data-dismiss="alert" aria-label="Close">
                 <span aria-hidden="true">&times;</span>
            </button>
         </div>
     @endif


    {{-- Pengaturan Gambar --}}
    <div class="bg-white rounded-2xl shadow-lg p-6">
        <h1 class="text-xl font-bold mb-6">Pengaturan Logo & Banner Statis</h1>
        <form action="{{ route('admin.settings.update') }}" method="POST" enctype="multipart/form-data" class="space-y-6" id="settings-form">
            @csrf
            {{-- Method Spoofing jika route Anda PUT/PATCH --}}
            {{-- @method('PUT') --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                @foreach(['logo','banner_2','banner_3'] as $key)
                <div class="space-y-2">
                    <label class="block font-medium mb-1">{{ str_replace('_', ' ', Str::title($key)) }}</label>
                    <div class="dropzone-custom border-2 border-dashed border-gray-300 rounded-xl p-6 flex flex-col items-center justify-center cursor-pointer text-center"
                         id="{{ $key }}-dropzone">
                         {{-- Tampilkan gambar yang sudah ada sebagai background/placeholder --}}
                        @if(!empty($settings[$key]) && Storage::disk('public')->exists($settings[$key]))
                            <img src="{{ asset('storage/'.$settings[$key]) }}" class="w-24 h-24 object-contain mb-2 rounded" alt="Current {{ $key }}">
                            <span id="{{ $key }}-message" class="text-sm text-gray-500">Klik atau seret file baru ke sini untuk mengganti</span>
                        @else
                            <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 mb-2"></i>
                            <span id="{{ $key }}-message" class="text-sm font-semibold text-blue-600">Klik atau seret file ke sini</span>
                        @endif
                        <input type="file" name="{{ $key }}" id="{{ $key }}-input" class="hidden" accept="image/jpeg,image/png,image/webp">
                        <img id="{{ $key }}-preview" class="mt-2 w-24 h-24 object-contain rounded hidden"/>
                    </div>
                    <small class="text-gray-500 block mt-1 text-center">
                        @if($key == 'logo') Maks 2MB. Rekomendasi: Rasio 4:1 (misal 1800x400)
                        @elseif($key == 'banner_2') Maks 2MB. Rekomendasi: Rasio 16:9 (misal 400x210)
                        @elseif($key == 'banner_3') Maks 2MB. Rekomendasi: Rasio 16:9 (misal 400x210)
                        @endif
                    </small>
                </div>
                @endforeach
            </div>
            <button type="submit" class="mt-4 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition">Simpan Pengaturan Gambar</button>
        </form>
    </div>

    {{-- [BARU] Pengaturan Alamat Pengguna/Toko --}}
    <div class="bg-white rounded-2xl shadow-lg p-6">
        <h1 class="text-xl font-bold mb-6">Pengaturan Alamat Pengguna ({{ $user->name }})</h1>
        <p class="text-sm text-gray-600 mb-4">Alamat ini akan digunakan sebagai alamat asal pengiriman default jika Anda adalah penjual.</p>
        <form action="{{ route('admin.settings.address.update') }}" method="POST" class="space-y-4" id="address-form">
            @csrf
            {{-- Method Spoofing jika route Anda PUT/PATCH --}}
            {{-- @method('PUT') --}}

            {{-- Input Detail Alamat --}}
            <div>
                <label for="address_detail" class="block text-sm font-medium text-gray-700">Detail Alamat Lengkap <span class="text-red-500">*</span></label>
                <textarea name="address_detail" id="address_detail" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500" required placeholder="Nama jalan, nomor rumah, RT/RW, kelurahan/desa...">{{ old('address_detail', $user->address_detail) }}</textarea>
                @error('address_detail') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Kolom Provinsi, Kabupaten, Kecamatan, Desa --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                 <div>
                    <label for="province" class="block text-sm font-medium text-gray-700">Provinsi <span class="text-red-500">*</span></label>
                    <input type="text" name="province" id="province" value="{{ old('province', $user->province) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm bg-gray-100" readonly required placeholder="Pilih dari pencarian">
                     @error('province') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                 <div>
                    <label for="regency" class="block text-sm font-medium text-gray-700">Kabupaten/Kota <span class="text-red-500">*</span></label>
                    <input type="text" name="regency" id="regency" value="{{ old('regency', $user->regency) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm bg-gray-100" readonly required placeholder="Pilih dari pencarian">
                     @error('regency') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="district" class="block text-sm font-medium text-gray-700">Kecamatan <span class="text-red-500">*</span></label>
                    <input type="text" name="district" id="district" value="{{ old('district', $user->district) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm bg-gray-100" readonly required placeholder="Pilih dari pencarian">
                     @error('district') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                 <div>
                    <label for="village" class="block text-sm font-medium text-gray-700">Desa/Kelurahan <span class="text-red-500">*</span></label>
                    <input type="text" name="village" id="village" value="{{ old('village', $user->village) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm bg-gray-100" readonly required placeholder="Pilih dari pencarian">
                     @error('village') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- Kode Pos --}}
            <div>
                <label for="postal_code" class="block text-sm font-medium text-gray-700">Kode Pos <span class="text-red-500">*</span></label>
                <input type="text" name="postal_code" id="postal_code" value="{{ old('postal_code', $user->postal_code) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm bg-gray-100" readonly required placeholder="Pilih dari pencarian">
                 @error('postal_code') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

             {{-- Pencarian Alamat KiriminAja --}}
             <div>
                 <label for="address_search" class="block text-sm font-medium text-gray-700">Cari Alamat (Kecamatan/Kelurahan)</label>
                 <div class="relative mt-1">
                     <select id="address_search" class="block w-full border-gray-300 rounded-md shadow-sm" style="width: 100%;">
                         {{-- Select2 akan mengisi ini --}}
                     </select>
                     <span id="search-loading" class="absolute right-8 top-1/2 -translate-y-1/2 hidden">
                         <div class="loader !w-4 !h-4 !border-2"></div>
                     </span>
                 </div>
                 <p class="mt-1 text-xs text-gray-500">Ketik min. 3 huruf nama kecamatan atau kelurahan untuk mencari dan mengisi otomatis.</p>
             </div>


             {{-- Latitude & Longitude --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                 <div class="md:col-span-1">
                    <label for="latitude" class="block text-sm font-medium text-gray-700">Latitude</label>
                    <input type="text" name="latitude" id="latitude" value="{{ old('latitude', $user->latitude) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" placeholder="-7.xxxxxx">
                    @error('latitude') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                 <div class="md:col-span-1">
                    <label for="longitude" class="block text-sm font-medium text-gray-700">Longitude</label>
                    <input type="text" name="longitude" id="longitude" value="{{ old('longitude', $user->longitude) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" placeholder="111.xxxxxx">
                    @error('longitude') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                 <div class="md:col-span-1">
                      <button type="button" id="geocode-button" class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                          <i class="fas fa-map-marker-alt mr-2"></i> Dapatkan Koordinat
                      </button>
                      <span id="geocode-status" class="text-xs text-gray-500 mt-1 block"></span>
                 </div>
            </div>
             <p class="text-xs text-gray-500">Koordinat (Latitude & Longitude) diperlukan untuk pengiriman Instan. Jika kosong, sistem akan mencoba mencari otomatis saat menyimpan.</p>


            {{-- Hidden fields for KiriminAja IDs --}}
            <input type="hidden" name="kiriminaja_district_id" id="kiriminaja_district_id" value="{{ old('kiriminaja_district_id', $user->kiriminaja_district_id) }}">
            <input type="hidden" name="kiriminaja_subdistrict_id" id="kiriminaja_subdistrict_id" value="{{ old('kiriminaja_subdistrict_id', $user->kiriminaja_subdistrict_id) }}">

            <button type="submit" id="save-address-button" class="mt-4 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition">Simpan Alamat Pengguna</button>
        </form>
    </div>
    {{-- [AKHIR BARU] --}}


    {{-- Pengaturan Banner Etalase --}}
    <div class="bg-white rounded-2xl shadow-lg p-6 space-y-4">
        <h2 class="text-xl font-semibold mb-4">Banner Slider Etalase</h2>

        {{-- Form Add New Banner --}}
        <form action="{{ route('admin.settings.banners.store') }}" method="POST" enctype="multipart/form-data" class="space-y-2" id="add-banner-form">
            @csrf
            <label class="block font-medium mb-1">Tambah Banner Baru</label>
            <div class="dropzone-custom border-2 border-dashed border-gray-300 rounded-xl p-6 flex flex-col items-center justify-center cursor-pointer text-center"
                 id="banner-dropzone">
                 <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 mb-2"></i>
                 <span id="banner-message" class="text-sm font-semibold text-blue-600">Klik atau seret file ke sini</span>
                 <input type="file" name="image" id="banner-input" class="hidden" accept="image/jpeg,image/png,image/webp" required>
                 <img id="banner-preview" class="mt-2 w-40 h-20 object-contain rounded hidden"/>
            </div>
            <small class="text-gray-500 block mt-1 text-center">Maks 2MB. Rekomendasi: Rasio 2:1 (misal 900x450)</small>
            @error('image', 'storeBanner') <p class="mt-1 text-sm text-red-600 text-center">{{ $message }}</p> @enderror

            <button type="submit" class="mt-4 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition">Tambah Banner</button>
        </form>

         {{-- Table Banners --}}
         {{-- Menggunakan AlpineJS untuk modal edit --}}
         <div x-data="bannerEditModal()" class="mt-8">
             <h3 class="text-lg font-semibold mb-4">Daftar Banner Etalase</h3>
             <div class="overflow-x-auto border rounded-lg">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">#</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Gambar</th>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($banners as $index => $banner)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $loop->iteration }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <img src="{{ asset('storage/'.$banner->image) }}" class="h-16 w-32 object-contain rounded" alt="Banner {{ $banner->id }}" onerror="this.style.display='none'">
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium space-x-2">
                                <button @click="openModal({{ $banner->id }}, '{{ asset('storage/'.$banner->image) }}')"
                                        class="text-indigo-600 hover:text-indigo-900" title="Edit">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <form action="{{ route('admin.settings.banners.destroy', $banner->id) }}" method="POST" class="inline-block m-0" onsubmit="return confirm('Yakin ingin menghapus banner ini?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-900" title="Hapus">
                                         <i class="fas fa-trash-alt"></i> Hapus
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">Belum ada banner etalase.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
             </div>

             {{-- Modal Edit Banner --}}
             <div x-show="isOpen" @keydown.escape.window="closeModal()" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true" x-cloak>
                <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                    {{-- Background overlay --}}
                    <div x-show="isOpen" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="closeModal()" aria-hidden="true"></div>
                    {{-- Modal panel --}}
                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                    <div x-show="isOpen" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                         class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                        <form :action="updateUrl" method="POST" enctype="multipart/form-data">
                            @csrf
                            @method('PUT') {{-- Gunakan PUT untuk update --}}
                            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                                <div class="sm:flex sm:items-start">
                                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                            Edit Banner
                                        </h3>
                                        <div class="mt-4 space-y-4">
                                            <div class="dropzone-custom border-2 border-dashed border-gray-300 rounded-xl p-6 flex flex-col items-center justify-center cursor-pointer text-center"
                                                 @click="$refs.modalInput.click()" @dragover.prevent="dragOver=true" @dragleave.prevent="dragOver=false"
                                                 @drop.prevent="handleDrop($event)" :class="{'border-blue-500 bg-blue-50': dragOver}">
                                                <img x-show="preview" :src="preview" class="mb-2 w-40 h-20 object-contain rounded" alt="Preview"/>
                                                <span x-text="message" class="text-sm font-semibold text-blue-600"></span>
                                                 <input type="file" x-ref="modalInput" name="image" class="hidden" @change="previewFile" accept="image/jpeg,image/png,image/webp" required>
                                            </div>
                                             <small class="text-gray-500 block text-center">Maks 2MB. Rekomendasi: Rasio 2:1 (misal 900x450)</small>
                                             {{-- Tampilkan error validasi khusus modal --}}
                                             @if($errors->has('image') && session('edit_banner_id'))
                                                @if(session('edit_banner_id') == $banner->id) {{-- Cek apakah error milik modal ini --}}
                                                    <p class="mt-1 text-sm text-red-600 text-center">{{ $errors->first('image') }}</p>
                                                @endif
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
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

@endsection


@push('scripts')
{{-- jQuery & Select2 --}}
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script> {{-- AlpineJS for Modal --}}

<script>
// --- Script untuk Preview Gambar (Logo & Banner Statis) ---
document.addEventListener("DOMContentLoaded", function() {
    ['logo','banner_2','banner_3'].forEach(key => {
        setupImagePreview(key); // Panggil fungsi setup
    });
     // Setup juga untuk form tambah banner
     setupImagePreview('banner');

     // Fungsi setup preview reusable
     function setupImagePreview(key) {
         const dropzone = document.getElementById(`${key}-dropzone`);
         const input = document.getElementById(`${key}-input`);
         const preview = document.getElementById(`${key}-preview`);
         const message = document.getElementById(`${key}-message`);
         const existingImage = dropzone.querySelector('img[src^="http"]'); // Cari gambar yang sudah ada

         if (!dropzone || !input || !preview || !message) return; // Exit jika elemen tidak ditemukan

         dropzone.addEventListener('click', (e) => {
              // Hindari trigger klik input jika targetnya adalah gambar yang sudah ada
             if (e.target !== existingImage) {
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
                 showPreview(input.files[0], preview, message, existingImage);
             }
         });
         input.addEventListener('change', () => {
             if (input.files.length) showPreview(input.files[0], preview, message, existingImage);
         });
     }

    function showPreview(file, previewEl, messageEl, existingImageEl) {
        if (!file.type.startsWith('image/')) {
             alert('Hanya file gambar yang diperbolehkan!');
             return;
         }
        const reader = new FileReader();
        reader.onload = e => {
            previewEl.src = e.target.result;
            previewEl.classList.remove('hidden');
            messageEl.classList.add('hidden');
            if(existingImageEl) existingImageEl.classList.add('hidden'); // Sembunyikan gambar lama jika ada
        }
        reader.readAsDataURL(file);
    }

    // --- Script untuk Alert Dismiss ---
     $('.alert-dismissible').each(function() {
        var alert = $(this);
        alert.find('button[data-dismiss="alert"]').on('click', function() {
            alert.slideUp();
        });
     });
});

// --- Script untuk AlpineJS Modal Edit Banner ---
function bannerEditModal() {
    return {
        isOpen: false,
        preview: null,
        message: 'Klik atau seret file baru ke sini',
        dragOver: false,
        updateUrl: '',
        openModal(id, currentImage) {
            this.isOpen = true;
            this.preview = currentImage; // Tampilkan gambar saat ini
            // Pastikan URL update benar sesuai route Anda
            this.updateUrl = `/admin/settings/banners/${id}`; // URL harus cocok dengan route PUT/PATCH Anda
             this.message = 'Klik atau seret file baru untuk mengganti'; // Update pesan
              // Reset input file jika ada file sebelumnya terpilih
             if (this.$refs.modalInput) this.$refs.modalInput.value = null;
        },
        closeModal() {
            this.isOpen = false;
            this.preview = null; // Reset preview saat ditutup
            this.dragOver = false;
        },
        previewFile(event) {
            const file = event.target.files[0];
            if (!file) {
                 // Jika batal pilih file, kembalikan ke gambar asli (jika ada)
                 // this.preview = this.currentImage || null; // Perlu simpan currentImage saat openModal
                 this.message = 'Klik atau seret file baru ke sini';
                 return;
             }
             if (!file.type.startsWith('image/')) {
                 alert('Hanya file gambar yang diperbolehkan!');
                 return;
             }
            const reader = new FileReader();
            reader.onload = e => {
                this.preview = e.target.result;
                this.message = file.name; // Tampilkan nama file baru
            };
            reader.readAsDataURL(file);
        },
        handleDrop(event) {
             this.dragOver = false; // Reset drag over state
            const file = event.dataTransfer.files[0];
            if (!file) return;
             if (!file.type.startsWith('image/')) {
                 alert('Hanya file gambar yang diperbolehkan!');
                 return;
             }
            this.$refs.modalInput.files = event.dataTransfer.files; // Set file ke input
            this.previewFile({ target: { files: [file] } }); // Trigger preview
        }
    }
}


// --- [BARU] Script untuk Alamat & KiriminAja ---
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

    addressSearchSelect.select2({
        placeholder: "Ketik min. 3 huruf Kecamatan/Kelurahan...",
        minimumInputLength: 3,
        ajax: {
            url: "{{ route('admin.settings.address.search') }}", // Route untuk search KiriminAja
            type: "POST", // Gunakan POST
            dataType: 'json',
            delay: 250, // Delay sebelum request
            data: function (params) {
                return {
                    _token: "{{ csrf_token() }}", // Kirim CSRF token
                    query: params.term // query pencarian dari Select2
                };
            },
            processResults: function (data) {
                 searchLoading.addClass('hidden'); // Sembunyikan loading
                if (data.success && data.data) {
                    return {
                        // Map data KiriminAja ke format Select2
                        results: data.data.map(item => ({
                             id: JSON.stringify(item), // Simpan seluruh objek item sebagai ID (string JSON)
                            text: item.text // Teks yang ditampilkan di dropdown
                        }))
                    };
                } else {
                     return { results: [{ id: '', text: data.message || 'Tidak ditemukan', disabled: true }] };
                }
            },
            beforeSend: function() {
                 searchLoading.removeClass('hidden'); // Tampilkan loading
            },
            cache: true
        }
    });

    // Event listener saat memilih alamat dari Select2
    addressSearchSelect.on('select2:select', function (e) {
        const dataString = e.params.data.id;
        try {
            const selectedAddress = JSON.parse(dataString); // Parse string JSON kembali ke object

            provinceInput.val(selectedAddress.province || '');
            regencyInput.val(selectedAddress.city || ''); // 'city' dari KiriminAja adalah Kabupaten/Kota
            districtInput.val(selectedAddress.subdistrict || ''); // 'subdistrict' dari KiriminAja adalah Kecamatan
            villageInput.val(selectedAddress.village || ''); // 'village' dari KiriminAja adalah Desa/Kelurahan
            postalCodeInput.val(selectedAddress.zip_code || '');
            districtIdInput.val(selectedAddress.district_id || ''); // ID Kecamatan KiriminAja
            subdistrictIdInput.val(selectedAddress.subdistrict_id || ''); // ID Kelurahan KiriminAja

             // Reset Select2 setelah memilih
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
     const addressDetailInput = $('#address_detail'); // Tambahkan input detail alamat

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
             url: "{{ route('admin.settings.address.geocode') }}", // Route untuk geocoding
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
                      // Kosongkan field jika gagal?
                      // latitudeInput.val('');
                      // longitudeInput.val('');
                 }
             },
             error: function(xhr) {
                 const errorMsg = xhr.responseJSON?.message || 'Gagal menghubungi server geocoding.';
                 geocodeStatus.text(errorMsg).addClass('text-red-500').removeClass('text-green-500');
                  // Kosongkan field jika gagal?
                  // latitudeInput.val('');
                  // longitudeInput.val('');
             },
             complete: function() {
                 btn.removeClass('is-loading').prop('disabled', false); // Hapus loading dari tombol
             }
         });
     });

});
</script>
@endpush
