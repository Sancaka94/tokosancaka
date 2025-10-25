@extends('layouts.admin')

@section('title', 'Pengaturan Aplikasi')
@section('page-title', 'Pengaturan Aplikasi')

@push('styles')
{{-- Tambahkan CSS jika diperlukan, misal untuk AlpineJS atau styling tambahan --}}
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
                    Profil Pengguna
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
                 class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-md shadow-md" role="alert">
                <p>{{ session('success') }}</p>
            </div>
        @endif
        @if (session('error'))
             <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)" x-transition
                  class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-md shadow-md" role="alert">
                 <p>{!! session('error') !!}</p> {{-- Use {!! !!} if error might contain HTML --}}
             </div>
        @endif

        @if ($errors->any())
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-md shadow-md" role="alert">
                <p class="font-bold mb-2">Terjadi kesalahan validasi:</p>
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif


        <!-- Tab Content -->
        <div>
            {{-- 1. Tab Profil Pengguna --}}
            <div x-show="activeTab === 'profile'" x-cloak>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">

                    <!-- Form Update Profil -->
                    <div class="md:col-span-2 bg-white dark:bg-gray-800 p-6 rounded-lg shadow-lg">
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Informasi Profil</h3>
                        {{-- [PERBAIKAN] Pastikan route ini ada dan mengarah ke method update profil di controller --}}
                        <form action="{{ route('admin.settings.profile.update') }}" method="POST" enctype="multipart/form-data" x-data="{ photoName: null, photoPreview: null }">
                            @csrf
                            @method('PUT') {{-- Gunakan PUT atau PATCH sesuai definisi route Anda --}}
                            <div class="space-y-4">

                                {{-- Input Foto Profil --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Foto Profil / Logo Toko</label>
                                    <div class="mt-1 flex items-center space-x-4">
                                        <span class="inline-block h-16 w-16 rounded-full overflow-hidden bg-gray-100 dark:bg-gray-700">
                                            {{-- [PERBAIKAN] Gunakan $user dan sesuaikan nama field (misal: store_logo_path atau photo_profile) --}}
                                            @php
                                                // Prioritaskan photo_profile jika ada, fallback ke store_logo_path
                                                $profilePhotoPath = $user->photo_profile ?? $user->store_logo_path;
                                                $profilePhotoUrl = $profilePhotoPath ? Storage::url($profilePhotoPath) : 'https://ui-avatars.com/api/?name=' . urlencode($user->nama_lengkap) . '&color=7F9CF5&background=EBF4FF';
                                            @endphp
                                            <template x-if="!photoPreview">
                                                <img class="h-full w-full object-cover text-gray-300" src="{{ $profilePhotoUrl }}" alt="Profil">
                                            </template>
                                            <template x-if="photoPreview">
                                                {{-- Tampilkan preview gambar baru --}}
                                                <span class="block w-full h-full bg-cover bg-no-repeat bg-center rounded-full" :style="'background-image: url(\'' + photoPreview + '\');'"></span>
                                            </template>
                                        </span>
                                        {{-- [PERBAIKAN] Sesuaikan 'name' input jika perlu (misal 'store_logo_path') --}}
                                        <input type="file" name="photo_profile" id="photo_profile" class="hidden" accept="image/jpeg,image/png,image/webp,image/jpg"
                                               @change="
                                                   photoName = $event.target.files[0].name;
                                                   const reader = new FileReader();
                                                   reader.onload = (e) => { photoPreview = e.target.result; };
                                                   reader.readAsDataURL($event.target.files[0]);
                                               ">
                                        <label for="photo_profile" class="cursor-pointer py-2 px-3 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                                            Ganti Foto
                                        </label>
                                    </div>
                                     @error('photo_profile') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>

                                {{-- [PERBAIKAN] Gunakan $user dan nama kolom dari tabel Pengguna --}}
                                <div>
                                    <label for="nama_lengkap" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nama Lengkap</label>
                                    <input type="text" name="nama_lengkap" id="nama_lengkap" value="{{ old('nama_lengkap', $user->nama_lengkap) }}" required
                                           class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                     @error('nama_lengkap') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Alamat Email</label>
                                    <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}" required
                                           class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                     @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label for="no_wa" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nomor WA</label>
                                    <input type="text" name="no_wa" id="no_wa" value="{{ old('no_wa', $user->no_wa) }}"
                                           class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Contoh: 08123456789">
                                    @error('no_wa') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>

                                {{-- Tampilkan field Store Name & Logo hanya jika user adalah Seller --}}
                                {{-- @if($user->role === 'Seller') --}}
                                {{--
                                <div>
                                    <label for="store_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nama Toko</label>
                                    <input type="text" name="store_name" id="store_name" value="{{ old('store_name', $user->store_name) }}"
                                           class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    @error('store_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>
                                --}}
                                {{-- @endif --}}

                                {{--
                                <div>
                                    <label for="address_detail" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Alamat Detail</label>
                                    <textarea name="address_detail" id="address_detail" rows="3"
                                              class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('address_detail', $user->address_detail) }}</textarea>
                                     @error('address_detail') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>
                                --}}

                                <div>
                                    <button type="submit" class="w-full inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                        Simpan Perubahan Profil
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- Form Update Password -->
                    <div class="md:col-span-1 bg-white dark:bg-gray-800 p-6 rounded-lg shadow-lg">
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Ubah Password</h3>
                         {{-- [PERBAIKAN] Pastikan route ini ada dan mengarah ke method update password di controller --}}
                        <form action="{{ route('admin.settings.password.update') }}" method="POST">
                            @csrf
                            @method('PUT') {{-- Gunakan PUT atau PATCH --}}
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
            </div>

            {{-- 2. Tab Slider Informasi (Placeholder) --}}
            {{--
            <div x-show="activeTab === 'slider'" x-cloak>
                 @php
                     // [PERBAIKAN] Ambil data slider dari settings atau model terpisah
                     // Contoh jika disimpan di settings table dengan key 'dashboard_slider' (format JSON)
                     $sliderJson = App\Models\Setting::where('key', 'dashboard_slider')->value('value') ?? '[]';
                     try {
                         $slides = json_decode($sliderJson, true);
                         if (!is_array($slides)) $slides = []; // Fallback jika decode gagal atau bukan array
                     } catch (\Exception $e) {
                         $slides = []; // Fallback jika JSON tidak valid
                         Log::error('Invalid JSON for dashboard_slider setting: ' . $e->getMessage());
                     }
                 @endphp
                <div x-data='{ slides: @json($slides) }' class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-lg">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Pengaturan Slider Informasi</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">Atur gambar dan teks yang akan ditampilkan di slider dashboard admin.</p>
                     <!-- [PERBAIKAN] Pastikan route ini ada dan mengarah ke method update slider di controller -->
                    <form action="{{ route('admin.settings.slider.update') }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="space-y-6">
                            <template x-for="(slide, index) in slides" :key="index">
                                <div class="p-4 border dark:border-gray-700 rounded-md relative">
                                    <h4 class="font-medium mb-2 dark:text-white" x-text="'Slide ' + (index + 1)"></h4>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">URL Gambar</label>
                                            <input type="url" :name="'slides['+index+'][img]'" x-model="slide.img" required
                                                   class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="https://placehold.co/...">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Judul (Opsional)</label>
                                            <input type="text" :name="'slides['+index+'][title]'" x-model="slide.title"
                                                   class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        </div>
                                        <div class="md:col-span-2">
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Deskripsi (Opsional)</label>
                                            <textarea :name="'slides['+index+'][desc]'" x-model="slide.desc" rows="2"
                                                      class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                                        </div>
                                    </div>
                                    <div class="absolute top-2 right-2">
                                        <button type="button" @click="slides.splice(index, 1)" title="Hapus Slide" class="text-red-500 hover:text-red-700 focus:outline-none">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>
                        <div class="mt-6 flex justify-between items-center">
                            <button type="button" @click="slides.push({img: '', title: '', desc: ''})" class="py-2 px-4 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                <i class="fas fa-plus mr-2"></i>Tambah Slide
                            </button>
                            <button type="submit" class="py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                Simpan Pengaturan Slider
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            --}}

            {{-- 3. Tab Pengaturan Pelanggan (Placeholder) --}}
            {{--
             <div x-show="activeTab === 'customer'" x-cloak>
                 @php
                     // [PERBAIKAN] Ambil data auto_freeze dari settings
                     $autoFreeze = (bool) App\Models\Setting::where('key', 'auto_freeze')->value('value');
                 @endphp
                 <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-lg">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Pengaturan Umum Pelanggan</h3>
                     <!-- [PERBAIKAN] Pastikan route ini ada dan mengarah ke method update general settings di controller -->
                     <form action="{{ route('admin.settings.general.update') }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="space-y-4">
                            <div class="relative flex items-start">
                                <div class="flex items-center h-5">
                                    <input id="auto_freeze" name="auto_freeze" type="checkbox" value="1" {{ $autoFreeze ? 'checked' : '' }} class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded">
                                </div>
                                <div class="ml-3 text-sm">
                                    <label for="auto_freeze" class="font-medium text-gray-700 dark:text-gray-300">Bekukan Akun Otomatis</label>
                                    <p class="text-gray-500 dark:text-gray-400">Jika diaktifkan, sistem akan membekukan akun pelanggan yang memiliki tunggakan pembayaran melebihi batas waktu (memerlukan logika tambahan di backend).</p>
                                </div>
                            </div>
                            <!-- Tambahkan pengaturan lain jika perlu -->
                        </div>
                        <div class="mt-6 text-right">
                             <button type="submit" class="py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                 Simpan Pengaturan Umum
                             </button>
                        </div>
                    </form>
                 </div>
            </div>
             --}}

        </div>
    </div>
</div>
@endsection

@push('scripts')
{{-- Tambahkan script jika diperlukan --}}
<script>
    // Script untuk alert dismiss (jika menggunakan Bootstrap atau JS manual)
    document.querySelectorAll('.alert-dismissible [data-dismiss="alert"]').forEach(button => {
        button.addEventListener('click', function() {
            this.closest('.alert-dismissible').style.display = 'none';
        });
    });
</script>
@endpush

