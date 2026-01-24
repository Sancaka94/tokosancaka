@extends('layouts.admin')

@section('title', 'Pengaturan Aplikasi')
@section('page-title', 'Pengaturan Aplikasi')

@section('content')

{{-- Notifikasi --}}
@if(session('success') || session('error'))
<div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
     x-transition:leave="transition ease-in duration-300"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="flex items-center p-4 mb-4 text-sm rounded-lg border {{ session('success') ? 'bg-green-50 text-green-800 border-green-200' : 'bg-red-50 text-red-800 border-red-200' }}" role="alert">
    
    @if(session('success'))
        <i class="fa-solid fa-check-circle w-5 h-5"></i>
    @else
        <i class="fa-solid fa-exclamation-triangle w-5 h-5"></i>
    @endif

    <div class="ml-3 font-medium">
        {{ session('success') ?? session('error') }}
    </div>
    <button @click="show = false" type="button" class="ml-auto -mx-1.5 -my-1.5 rounded-lg focus:ring-2 p-1.5 inline-flex items-center justify-center h-8 w-8 {{ session('success') ? 'bg-green-50 text-green-500 hover:bg-green-200 focus:ring-green-400' : 'bg-red-50 text-red-500 hover:bg-red-200 focus:ring-red-400' }}" aria-label="Close">
        <i class="fa-solid fa-times"></i>
    </button>
</div>
@endif
@if ($errors->any())
    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-md shadow-md" role="alert">
        <p class="font-bold">Terjadi Kesalahan:</p>
        <ul>
            @foreach ($errors->all() as $error)
                <li>- {{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif


<div class="container mx-auto px-4 sm:px-8 py-8">
    {{-- [FIX] Variabel admin didefinisikan sekali saja --}}
    @php $admin = $user; @endphp

    <div x-data="{ activeTab: 'profile' }" class="w-full">
        <div class="mb-4 border-b border-gray-200">
            <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                <button @click="activeTab = 'profile'" :class="{ 'border-red-500 text-red-600': activeTab === 'profile', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'profile' }"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    Profil Admin & Alamat
                </button>
                <button @click="activeTab = 'customer'" :class="{ 'border-red-500 text-red-600': activeTab === 'customer', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'customer' }"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    Manajemen Pengguna
                </button>
                <button @click="activeTab = 'banners'" :class="{ 'border-red-500 text-red-600': activeTab === 'banners', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'banners' }"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    Pengaturan Gambar Slide Marketplace
                </button>
            </nav>
        </div>

        <div>
            {{-- 1. Tab Profil Admin & Alamat --}}
            <div x-show="activeTab === 'profile'" x-cloak>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    
                    {{-- Form Profil dan Alamat digabung --}}
                    <div class="md:col-span-2 space-y-8">
                        
                        <div class="bg-white p-6 rounded-lg shadow-lg">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4">Informasi Profil</h3>
                            <form action="{{ route('admin.settings.profile.update') }}" method="POST" enctype="multipart/form-data" x-data="{ photoName: null, photoPreview: null }">
                                @csrf
                                @method('PUT')
                                <div class="space-y-4">
                                    {{-- Input Foto Profil --}}
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Foto Profil</label>
                                        <div class="mt-1 flex items-center space-x-4">
                                            <span class="inline-block h-16 w-16 rounded-full overflow-hidden bg-gray-100">
                                                <template x-if="!photoPreview">
                                                    {{-- [FIX] Path asset() yang benar adalah 'storage/', BUKAN 'public/storage/' --}}
                                                    <img class="h-full w-full object-cover" src="{{ $admin->store_logo_path ? asset('public/storage/' . $admin->store_logo_path) : 'https://ui-avatars.com/api/?name=' . urlencode($admin->nama_lengkap) . '&color=7F9CF5&background=EBF4FF' }}" alt="Profil">
                                                </template>
                                                <template x-if="photoPreview">
                                                    <span class="block w-full h-full bg-cover bg-no-repeat bg-center" :style="'background-image: url(\'' + photoPreview + '\');'"></span>
                                                </template>
                                            </span>
                                            <input type="file" name="photo_profile" id="photo_profile" class="hidden"
                                                   @change="photoName = $event.target.files[0].name;
                                                           const reader = new FileReader();
                                                           reader.onload = (e) => { photoPreview = e.target.result; };
                                                           reader.readAsDataURL($event.target.files[0]);">
                                            <label for="photo_profile" class="cursor-pointer py-2 px-3 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                                Ganti Foto
                                            </label>
                                        </div>
                                        @error('photo_profile') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                    </div>

                                    <div>
                                        <label for="nama_lengkap" class="block text-sm font-medium text-gray-700">Nama Lengkap</label>
                                        <input type="text" name="nama_lengkap" id="nama_lengkap" value="{{ old('nama_lengkap', $admin->nama_lengkap) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm @error('nama_lengkap') border-red-500 @enderror">
                                        @error('nama_lengkap') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                    </div>
                                    
                                    {{-- [TAMBAHAN] Input Nama Toko --}}
<div>
    <label for="store_name" class="block text-sm font-medium text-gray-700">Nama Toko</label>
    <input 
        type="text" 
        name="store_name" 
        id="store_name" 
        value="{{ old('store_name', $admin->store_name) }}" 
        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm @error('store_name') border-red-500 @enderror"
    >
    @error('store_name')
        <span class="text-red-500 text-sm">{{ $message }}</span>
    @enderror
</div>

                                    <div>
                                        <label for="email" class="block text-sm font-medium text-gray-700">Alamat Email</label>
                                        <input type="email" name="email" id="email" value="{{ old('email', $admin->email) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm @error('email') border-red-500 @enderror">
                                        @error('email') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label for="no_hp" class="block text-sm font-medium text-gray-700">Nomor HP (WA)</label>
                                        <input type="text" name="no_hp" id="no_hp" value="{{ old('no_hp', $admin->no_wa) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm @error('no_hp') border-red-500 @enderror" placeholder="Contoh: 08123456789">
                                        @error('no_hp') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                    </div>
                                    
                                    {{-- [TAMBAHAN] Input Bank --}}
<div>
    <label for="bank_name" class="block text-sm font-medium text-gray-700">Nama Bank</label>
    <input 
        type="text" 
        name="bank_name" 
        id="bank_name" 
        value="{{ old('bank_name', $admin->bank_name) }}" 
        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm @error('bank_name') border-red-500 @enderror" 
        placeholder="Contoh: BCA / BRI / Mandiri"
    >
    @error('bank_name')
        <span class="text-red-500 text-sm">{{ $message }}</span>
    @enderror
</div>

<div>
    <label for="bank_account_name" class="block text-sm font-medium text-gray-700">Nama Pemilik Rekening</label>
    <input 
        type="text" 
        name="bank_account_name" 
        id="bank_account_name" 
        value="{{ old('bank_account_name', $admin->bank_account_name) }}" 
        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm @error('bank_account_name') border-red-500 @enderror" 
        placeholder="Sesuai nama di buku tabungan"
    >
    @error('bank_account_name')
        <span class="text-red-500 text-sm">{{ $message }}</span>
    @enderror
</div>

<div>
    <label for="bank_account_number" class="block text-sm font-medium text-gray-700">Nomor Rekening</label>
    <input 
        type="text" 
        name="bank_account_number" 
        id="bank_account_number" 
        value="{{ old('bank_account_number', $admin->bank_account_number) }}" 
        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm @error('bank_account_number') border-red-500 @enderror" 
        placeholder="Hanya angka"
    >
    @error('bank_account_number')
        <span class="text-red-500 text-sm">{{ $message }}</span>
    @enderror
</div>

                                    
                                    {{-- [PERBAIKAN] TAMBAHKAN TOMBOL SIMPAN DI SINI --}}
<div class="text-right mt-6">
    <button type="submit"
        class="w-full inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
        Simpan Perubahan Profil
    </button>
</div>

                                </div>
                            </form>
                        </div>
                        
                        <div class="bg-white p-6 rounded-lg shadow-lg" x-data="addressFinder('{{ route('admin.api.search.address') }}', '{{ route('admin.api.geocode.address') }}')">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4">Pengaturan Alamat Toko</h3>
                            <p class="text-sm text-gray-600 mb-4">Cari alamat menggunakan KiriminAja untuk mengisi otomatis form di bawah ini.</p>
                            
                            <form action="{{ route('admin.settings.address.update') }}" method="POST">
                                @csrf
                                @method('PUT')

                                {{-- ID KiriminAja tetap tersembunyi --}}
<input type="hidden" name="kiriminaja_district_id" id="kiriminaja_district_id" x-model="fields.kiriminaja_district_id">
<input type="hidden" name="kiriminaja_subdistrict_id" id="kiriminaja_subdistrict_id" x-model="fields.kiriminaja_subdistrict_id">

<div class="space-y-4">

    {{-- [TAMBAHAN] Field Lat/Long yang terlihat --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label for="latitude_visible" class="block text-sm font-medium text-gray-700">Latitude</label>
            {{-- Input ini hanya untuk TAMPILAN, nilainya diambil dari x-model --}}
            <input type="text" id="latitude_visible" x-model="fields.latitude" 
                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm bg-gray-100" 
                   readonly>
            {{-- Input 'hidden' yang asli tetap ada untuk dikirim ke form --}}
            <input type="hidden" name="latitude" id="latitude" x-model="fields.latitude">
        </div>
        <div>
            <label for="longitude_visible" class="block text-sm font-medium text-gray-700">Longitude</label>
            {{-- Input ini hanya untuk TAMPILAN, nilainya diambil dari x-model --}}
            <input type="text" id="longitude_visible" x-model="fields.longitude" 
                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm bg-gray-100" 
                   readonly>
            {{-- Input 'hidden' yang asli tetap ada untuk dikirim ke form --}}
            <input type="hidden" name="longitude" id="longitude" x-model="fields.longitude">
        </div>
    </div>

    <div class="relative">
                                        <label for="address_search" class="block text-sm font-medium text-gray-700">Cari Alamat (KiriminAja)</label>
                                        <input type="text" id="address_search"
                                               x-model.debounce.500ms="searchQuery"
                                               @input.debounce.500ms="search"
                                               placeholder="Ketik nama jalan, kelurahan, atau kecamatan..."
                                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                        <div x-show="loading" class="absolute inset-y-0 right-0 top-6 pr-3 flex items-center pointer-events-none">
                                            <svg class="animate-spin h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                        </div>
                                        <div x-show="results.length > 0" @click.away="results = []" class="absolute z-10 w-full bg-white border border-gray-300 rounded-md shadow-lg mt-1 max-h-60 overflow-y-auto">
                                            <ul>
                                                <template x-for="result in results" :key="result.district_id + result.subdistrict_id">
                                                    <li @click="selectAddress(result)"
                                                        class="p-3 hover:bg-gray-100 cursor-pointer text-sm"
                                                        x-text="result.text">
                                                    </li>
                                                </template>
                                            </ul>
                                        </div>
                                        <p x-show="message" x-text="message" class="text-sm text-red-600 mt-1"></p>
                                    </div>
                                    
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label for="province" class="block text-sm font-medium text-gray-700">Provinsi</label>
                                            <input type="text" name="province" id="province" x-model="fields.province" readonly class="mt-1 block w-full border-gray-300 rounded-md shadow-sm bg-gray-100">
                                        </div>
                                        <div>
                                            <label for="regency" class="block text-sm font-medium text-gray-700">Kabupaten / Kota</label>
                                            <input type="text" name="regency" id="regency" x-model="fields.city" readonly class="mt-1 block w-full border-gray-300 rounded-md shadow-sm bg-gray-100">
                                        </div>
                                        <div>
                                            <label for="district" class="block text-sm font-medium text-gray-700">Kecamatan</label>
                                            <input type="text" name="district" id="district" x-model="fields.subdistrict" readonly class="mt-1 block w-full border-gray-300 rounded-md shadow-sm bg-gray-100">
                                        </div>
                                        <div>
                                            <label for="village" class="block text-sm font-medium text-gray-700">Desa / Kelurahan</label>
                                            <input type="text" name="village" id="village" x-model="fields.village" readonly class="mt-1 block w-full border-gray-300 rounded-md shadow-sm bg-gray-100">
                                        </div>
                                    </div>
                                    <div>
                                        <label for="postal_code" class="block text-sm font-medium text-gray-700">Kode Pos</label>
                                        <input type="text" name="postal_code" id="postal_code" x-model="fields.zip_code" readonly class="mt-1 block w-full border-gray-300 rounded-md shadow-sm bg-gray-100">
                                    </div>
                                    <div>
                                        <label for="address_detail" class="block text-sm font-medium text-gray-700">Detail Alamat (Nama Jalan, No. Rumah, RT/RW)</label>
                                        <textarea name="address_detail" id="address_detail" rows="3" x-model="fields.address_detail" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" placeholder="Contoh: Jl. Merdeka No. 10, RT 01 / RW 02, ..."></textarea>
                                    </div>

                                    <div>
                                        <button type="button" @click="getCoords" :disabled="geocoding" class="w-full inline-flex justify-center py-2 px-4 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                            <span x-show="!geocoding">Dapatkan Koordinat (Lat/Long)</span>
                                            <span x-show="geocoding">Mencari Koordinat...</span>
                                        </button>
                                        <p x-show="geocodeMessage" x-text="geocodeMessage" class="text-sm text-green-600 mt-1"></p>
                                    </div>

                                    <div class="text-right">
                                        <button type="submit" class="w-full inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                            Simpan Perubahan Alamat
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <div class="md:col-span-1 bg-white p-6 rounded-lg shadow-lg h-fit">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">Ubah Password</h3>
                        <form action="{{ route('admin.settings.password.update') }}" method="POST">
                            @csrf
                            @method('PUT')
                            <div class="space-y-4">
                                <div>
                                    <label for="current_password" class="block text-sm font-medium text-gray-700">Password Saat Ini</label>
                                    <input type="password" name="current_password" id="current_password" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm @error('current_password') border-red-500 @enderror" required>
                                    @error('current_password') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label for="password" class="block text-sm font-medium text-gray-700">Password Baru</label>
                                    <input type="password" name="password" id="password" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm @error('password') border-red-500 @enderror" required>
                                    @error('password') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Konfirmasi Password Baru</label>
                                    <input type="password" name="password_confirmation" id="password_confirmation" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                                </div>
                                <div>
                                    <button type="submit" class="w-full inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                        Ubah Password
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>

                </div>
            </div>


            {{-- 3. Tab Manajemen Pengguna --}}
            <div x-show="activeTab === 'customer'" x-cloak id="customer-table">
                <div class="bg-white p-6 rounded-lg shadow-lg">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Pengaturan Umum</h3>
                    <form action="{{ route('admin.settings.general.update') }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="space-y-4">
                             <div class="relative flex items-start">
                                <div class="flex items-center h-5">
                                    <input id="auto_freeze" name="auto_freeze" type="checkbox" {{ $autoFreeze ? 'checked' : '' }} class="focus:ring-red-500 h-4 w-4 text-red-600 border-gray-300 rounded">
                                </div>
                                <div class="ml-3 text-sm">
                                    <label for="auto_freeze" class="font-medium text-gray-700">Bekukan Akun Otomatis</label>
                                    <p class="text-gray-500">Jika diaktifkan, sistem akan membekukan akun pelanggan yang memiliki tunggakan pembayaran melebihi batas waktu.</p>
                                </div>
                            </div>
                        </div>
                        <div class="mt-6 text-right">
                           <button type="submit" class="py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                Simpan Pengaturan Umum
                           </button>
                        </div>
                    </form>
                </div>
                
                {{-- Tabel Manajemen Pengguna --}}
                {{-- [FIX] Menggunakan {!! ... !!} untuk JSON string dan satu baris --}}
                <div class="bg-white p-6 rounded-lg shadow-lg mt-8" x-data='dataTable(@json($penggunaList ?? []), @json($roles ?? []), @json($statuses ?? []))'>                    
                    
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Manajemen Pengguna</h3>
                    
                    {{-- [FIX] Anda harus membuat file partials ini --}}
                    {{-- @include('admin.settings.partials.user-table') --}}
                    
                    {{-- [ALTERNATIF] Jika Anda belum membuat partial, salin kode tabel Anda ke sini --}}
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                        <div>
                            <label for="searchInput" class="block text-sm font-medium text-gray-700">Cari Pengguna</label>
                            <input type="text" id="searchInput" x-model.debounce.300ms="searchTerm" placeholder="Cari nama, email, toko..."
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-red-500 focus:border-red-500 sm:text-sm">
                        </div>
                        <div>
                            <label for="filterRole" class="block text-sm font-medium text-gray-700">Filter Role</label>
                            <select id="filterRole" x-model="filterRole" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-red-500 focus:border-red-500 sm:text-sm">
                                <option value="all">Semua Role</option>
                                <template x-for="role in availableRoles" :key="role">
                                    <option :value="role" x-text="role"></option>
                                </template>
                            </select>
                        </div>
                        <div>
                            <label for="filterStatus" class="block text-sm font-medium text-gray-700">Filter Status</label>
                            <select id="filterStatus" x-model="filterStatus" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-red-500 focus:border-red-500 sm:text-sm">
                                <option value="all">Semua Status</option>
                                <template x-for="status in availableStatuses" :key="status">
                                    <option :value="status" x-text="status"></option>
                                </template>
                            </select>
                        </div>
                    </div>

                    <div class="overflow-x-auto relative shadow-md sm:rounded-lg">
                        <table class="w-full text-sm text-left text-gray-500">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50 sticky top-0 z-10">
                                <tr>
                                    <th scope="col" class="px-6 py-3 cursor-pointer" @click="sortBy('id_pengguna')">
                                        ID <span x-show="sortColumn === 'id_pengguna'" x-text="sortDirection === 'asc' ? '▲' : '▼'"></span>
                                    </th>
                                    <th scope="col" class="px-6 py-3 cursor-pointer" @click="sortBy('nama_lengkap')">
                                        Nama Lengkap <span x-show="sortColumn === 'nama_lengkap'" x-text="sortDirection === 'asc' ? '▲' : '▼'"></span>
                                    </th>
                                    <th scope="col" class="px-6 py-3">Email</th>
                                    <th scope="col" class="px-6 py-3">No. WA</th>
                                    <th scope="col" class="px-6 py-3 cursor-pointer" @click="sortBy('store_name')">
                                        Toko <span x-show="sortColumn === 'store_name'" x-text="sortDirection === 'asc' ? '▲' : '▼'"></span>
                                    </th>
                                    <th scope="col" class="px-6 py-3">Role</th>
                                    <th scope="col" class="px-6 py-3 cursor-pointer" @click="sortBy('saldo')">
                                        Saldo <span x-show="sortColumn === 'saldo'" x-text="sortDirection === 'asc' ? '▲' : '▼'"></span>
                                    </th>
                                    
                                    {{-- [TAMBAHAN] Kolom Header Bank --}}
                                    <th scope="col" class="px-6 py-3">Nama Bank</th>
                                    <th scope="col" class="px-6 py-3">Atas Nama</th>
                                    <th scope="col" class="px-6 py-3">No. Rekening</th>

                                    <th scope="col" class="px-6 py-3">Status</th>
                                    <th scope="col" class="px-6 py-3">Terverifikasi</th>
                                    <th scope="col" class="px-6 py-3 cursor-pointer" @click="sortBy('last_seen_at')">
                                        Terakhir Dilihat <span x-show="sortColumn === 'last_seen_at'" x-text="sortDirection === 'asc' ? '▲' : '▼'"></span>
                                    </th>
                                    <th scope="col" class="px-6 py-3 cursor-pointer" @click="sortBy('created_at')">
                                        Bergabung <span x-show="sortColumn === 'created_at'" x-text="sortDirection === 'asc' ? '▲' : '▼'"></span>
                                    </th>
                                    <th scope="col" class="px-6 py-3">Logo</th>
                                    <th scope="col" class="px-6 py-3 sticky right-0 bg-gray-50 z-20 shadow-[-2px_0_3px_rgba(0,0,0,0.1)]">
                                        Aksi
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-if="filteredUsers.length === 0">
                                    <tr>
                                        <td colspan="16" class="px-6 py-4 text-center">
                                            Tidak ada data yang cocok dengan pencarian atau filter.
                                        </td>
                                    </tr>
                                </template>
                                <template x-for="user in filteredUsers" :key="user.id_pengguna">
                                    <tr class="bg-white border-b hover:bg-gray-50">
                                        <td class="px-6 py-4" x-text="user.id_pengguna"></td>
                                        <td class="px-6 py-4 font-medium text-gray-900" x-text="user.nama_lengkap"></td>
                                        <td class="px-6 py-4" x-text="user.email || '-'"></td>
                                        <td class="px-6 py-4" x-text="user.no_wa || '-'"></td>
                                        <td class="px-6 py-4" x-text="user.store_name || '-'"></td>
                                        <td class="px-6 py-4">
                                            <span x-text="user.role"
                                                  :class="{
                                                      'bg-blue-100 text-blue-800': user.role === 'Admin',
                                                      'bg-green-100 text-green-800': user.role === 'Seller',
                                                      'bg-gray-100 text-gray-800': user.role === 'Pelanggan',
                                                      'bg-yellow-100 text-yellow-800': user.role !== 'Admin' && user.role !== 'Seller' && user.role !== 'Pelanggan'
                                                  }"
                                                  class="text-xs font-medium mr-2 px-2.5 py-0.5 rounded-full">
                                            </span>
                                        </td>
                                        {{-- [TAMBAHAN] Kolom Data Bank --}}
                                        <td class="px-6 py-4" x-text="formatCurrency(user.saldo)"></td>
                                        <td class="px-6 py-4" x-text="user.bank_name || '-'"></td>
                                        <td class="px-6 py-4" x-text="user.bank_account_name || '-'"></td>
                                        <td class="px-6 py-4" x-text="user.bank_account_number || '-'"></td>

                                       
                                        <td class="px-6 py-4">
                                            <span x-text="user.status"
                                                  :class="{
                                                      'bg-green-100 text-green-800': user.status === 'Aktif',
                                                      'bg-red-100 text-red-800': user.status !== 'Aktif',
                                                  }"
                                                  class="text-xs font-medium mr-2 px-2.5 py-0.5 rounded-full">
                                            </span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span x-text="user.is_verified ? 'Ya' : 'Tidak'"
                                                  :class="user.is_verified ? 'text-green-600 font-medium' : 'text-red-600'">
                                            </span>
                                        </td>
                                        <td class="px-6 py-4" x-text="user.last_seen_at ? new Date(user.last_seen_at).toLocaleString('id-ID', { dateStyle: 'short', timeStyle: 'short' }) : '-'"></td>
                                        <td class="px-6 py-4" x-text="user.created_at ? new Date(user.created_at).toLocaleDateString('id-ID', { dateStyle: 'short' }) : '-'"></td>
                                        <td class="px-6 py-4">
                                            {{-- [FIX] Path asset() yang benar --}}
                                            {{-- [PERBAIKAN GAMBAR TABEL PENGGUNA] --}}
                                            <img :src="user.store_logo_path 
                                                ? '{{ asset('public/storage') }}/' + user.store_logo_path 
                                                : 'https://ui-avatars.com/api/?name=' + encodeURIComponent(user.nama_lengkap || 'User') + '&color=7F9CF5&background=EBF4FF'"
                                                 alt="Logo" class="w-10 h-10 rounded-full object-cover">
                                        </td>
                                        <td class="px-6 py-4 sticky right-0 bg-white z-10 shadow-[-2px_0_3px_rgba(0,0,0,0.05)]">
                                            <div class="flex space-x-2">
                                                {{-- Ganti button dengan <a> atau tambahkan @click --}}
                                                {{-- Tombol Lihat (Sudah Benar) --}}
    <button @click.prevent="viewUser(user)" title="Lihat" class="text-blue-600 hover:text-blue-900">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
            <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
            <path fill-rule="evenodd" d="M.458 10C3.18 4.292 8.98 1 10 1c1.02 0 6.82 3.292 9.542 9-.06.12-.119.238-.178.356a.97.97 0 01-1.638-.707C16.208 6.017 12.071 4 10 4 7.929 4 3.792 6.017 2.274 8.649a.97.97 0 01-1.638.707.03.03 0 01-.178-.356z" clip-rule="evenodd" />
        </svg>
    </button>
    
    {{-- Tombol Edit (Desain dan Klik sudah benar) --}}
    <button @click.prevent="showEditUserModal(user)" title="Edit" class="text-yellow-600 hover:text-yellow-900">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
        </svg>
    </button>
    
    {{-- ======== [BARU] Tombol Freeze/Unfreeze ======== --}}
    <button @click.prevent="toggleFreeze(user)" 
            :title="user.status === 'Aktif' ? 'Bekukan Akun' : 'Aktifkan Akun'" 
            :class="user.status === 'Aktif' ? 'text-gray-400 hover:text-red-600' : 'text-green-500 hover:text-green-700'">
        
        {{-- Tampilkan ikon "Bekukan" ⛔ jika status Aktif --}}
        <template x-if="user.status === 'Aktif'">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM5.5 8a.5.5 0 01.5-.5h8a.5.5 0 010 1H6a.5.5 0 01-.5-.5z" clip-rule="evenodd" />
            </svg>
        </template>
        
        {{-- Tampilkan ikon "Aktifkan" ✅ jika status BUKAN Aktif --}}
        <template x-if="user.status !== 'Aktif'">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
            </svg>
        </template>
    </button>
    {{-- ======== AKHIR [BARU] Tombol Freeze/Unfreeze ======== --}}
    
    {{-- Tombol Hapus (Sudah Benar) --}}
    <button @click.prevent="confirmDelete(user.id_pengguna, user.nama_lengkap)" title="Hapus" class="text-red-600 hover:text-red-900">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
        </svg>
    </button>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                        
                         {{-- ======== [BARU] MODAL DETAIL PENGGUNA ======== --}}
<div x-show="showDetailModal" x-cloak
    x-transition:enter="ease-out duration-300"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="ease-in duration-200"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900 bg-opacity-75">

    {{-- Latar belakang modal, klik untuk menutup --}}
    <div @click="closeModal()" class="absolute inset-0"></div>

    {{-- Konten Modal --}}
    <div x-show="showDetailModal"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="relative bg-white rounded-lg shadow-xl w-full max-w-2xl mx-4 p-6 overflow-y-auto"
        style="max-height: 90vh;">
        
        <template x-if="selectedUser">
            <div>
                {{-- Header Modal --}}
                <div class="flex justify-between items-start pb-4 border-b">
                    <div>
                        <h3 class="text-xl font-semibold text-gray-900" x-text="selectedUser.nama_lengkap"></h3>
                        <p class="text-sm text-gray-500" x-text="selectedUser.store_name || 'Toko tidak ada nama'"></p>
                    </div>
                    <button @click="closeModal()" class="text-gray-400 hover:text-gray-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Body Modal --}}
                <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4 text-sm">
                    {{-- Info Kontak --}}
                    <div>
                        <dt class="font-medium text-gray-500">Email</dt>
                        <dd class="text-gray-900 mt-1" x-text="selectedUser.email || '-'"></dd>
                    </div>
                    <div>
                        <dt class="font-medium text-gray-500">No. WA</dt>
                        <dd class="text-gray-900 mt-1" x-text="selectedUser.no_wa || '-'"></dd>
                    </div>

                    {{-- Info Akun --}}
                    <div>
                        <dt class="font-medium text-gray-500">Role</dt>
                        <dd class="mt-1">
                            <span x-text="selectedUser.role"
                                :class="{
                                    'bg-blue-100 text-blue-800': selectedUser.role === 'Admin',
                                    'bg-green-100 text-green-800': selectedUser.role === 'Seller',
                                    'bg-gray-100 text-gray-800': selectedUser.role === 'Pelanggan',
                                    'bg-yellow-100 text-yellow-800': selectedUser.role !== 'Admin' && selectedUser.role !== 'Seller' && selectedUser.role !== 'Pelanggan'
                                }"
                                class="text-xs font-medium mr-2 px-2.5 py-0.5 rounded-full">
                            </span>
                        </dd>
                    </div>
                    <div>
                        <dt class="font-medium text-gray-500">Status</dt>
                        <dd class="mt-1">
                            <span x-text="selectedUser.status"
                                :class="{
                                    'bg-green-100 text-green-800': selectedUser.status === 'Aktif',
                                    'bg-red-100 text-red-800': selectedUser.status !== 'Aktif',
                                }"
                                class="text-xs font-medium mr-2 px-2.5 py-0.5 rounded-full">
                            </span>
                        </dd>
                    </div>

                    {{-- Info Bank --}}
                    <div class="md:col-span-2 pt-4 border-t mt-2">
                        <h4 class="font-semibold text-gray-800">Informasi Bank</h4>
                    </div>
                    <div>
                        <dt class="font-medium text-gray-500">Nama Bank</dt>
                        <dd class="text-gray-900 mt-1" x-text="selectedUser.bank_name || '-'"></dd>
                    </div>
                    <div>
                        <dt class="font-medium text-gray-500">Atas Nama Rekening</dt>
                        <dd class="text-gray-900 mt-1" x-text="selectedUser.bank_account_name || '-'"></dd>
                    </div>
                    <div>
                        <dt class="font-medium text-gray-500">Nomor Rekening</dt>
                        <dd class="text-gray-900 mt-1" x-text="selectedUser.bank_account_number || '-'"></dd>
                    </div>

                    {{-- Info Saldo & Verifikasi --}}
                    <div>
                        <dt class="font-medium text-gray-500">Saldo</dt>
                        <dd class="text-gray-900 font-semibold mt-1" x-text="formatCurrency(selectedUser.saldo)"></dd>
                    </div>
                    <div>
                        <dt class="font-medium text-gray-500">Terverifikasi</dt>
                        <dd class="mt-1">
                            <span x-text="selectedUser.is_verified ? 'Ya' : 'Tidak'"
                                :class="selectedUser.is_verified ? 'text-green-600 font-medium' : 'text-red-600'">
                            </span>
                        </dd>
                    </div>
                </div>

                {{-- Footer Modal --}}
                <div class="mt-8 pt-4 border-t text-right">
                    <button @click="closeModal()" type="button" class="py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                        Tutup
                    </button>
                </div>
            </div>
        </template>
    </div>
</div>
{{-- ======== AKHIR [BARU] MODAL DETAIL PENGGUNA ======== --}}

{{-- ======== MODAL EDIT PENGGUNA ======== --}}
<div x-show="showEditModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-gray-600 bg-opacity-50 flex items-center justify-center">
    <div @click.away="closeModal()" class="relative bg-white w-full max-w-2xl p-6 rounded-lg shadow-xl m-4">
        <div class="flex justify-between items-center pb-3 border-b border-gray-200">
            <h3 class="text-2xl font-semibold text-gray-800">Edit Pengguna: <span x-text="selectedUser ? selectedUser.nama : ''"></span></h3>
            <button @click="closeModal()" class="text-gray-500 hover:text-gray-700">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>

        <div x-if="selectedUser" class="mt-4">
            <form @submit.prevent="saveUserChanges()">
                <div class="mb-4">
                    <label for="edit_nama" class="block text-sm font-medium text-gray-700">Nama</label>
                    <input type="text" id="edit_nama" x-model="selectedUser.nama" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-red-500 focus:border-red-500">
                </div>
                <div class="mb-4">
                    <label for="edit_email" class="block text-sm font-medium text-gray-700">Email</label>
                    <input type="email" id="edit_email" x-model="selectedUser.email" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-red-500 focus:border-red-500">
                </div>
                <div class="mb-4">
                    <label for="edit_role" class="block text-sm font-medium text-gray-700">Role</label>
                    <input type="text" id="edit_role" x-model="selectedUser.role" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-red-500 focus:border-red-500">
                </div>
                {{-- Tambahkan field lain yang ingin Anda edit --}}

                <div class="mt-6 flex justify-end space-x-3 border-t border-gray-200 pt-4">
                    <button type="button" @click="closeModal()" class="px-4 py-2 bg-gray-300 text-gray-800 rounded-md hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-opacity-50">Batal</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>
{{-- ======== AKHIR MODAL EDIT PENGGUNA ======== --}}


                    </div>
                </div>
                
               
            

            </div>

            {{-- 4. [BARU] Tab Pengaturan Gambar (dari SettingController) --}}
            <div x-show="activeTab === 'banners'" x-cloak>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    
                    <div class="bg-white p-6 rounded-lg shadow-lg">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">Logo & Banner Utama</h3>
                        <form action="{{ route('admin.settings.main.update') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                            @csrf
                            @method('PUT')

                            @php
                                $logo_url = $settings['logo'] ?? null;
                                $banner_2_url = $settings['banner_2'] ?? null;
                                $banner_3_url = $settings['banner_3'] ?? null;
                            @endphp

                            {{-- Logo --}}
                            <div>
                                <label for="logo" class="block text-sm font-medium text-gray-700">Logo Utama</label>
                                @if($logo_url)
                                    {{-- [FIX] Path asset() yang benar --}}
                                    <img src="{{ asset('public/storage/' . $logo_url) }}" alt="Logo" class="h-16 w-auto my-2">
                                @endif
                                <input type="file" name="logo" id="logo" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-red-50 file:text-red-700 hover:file:bg-red-100">
                                @error('logo') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                            
                            {{-- Banner 2 --}}
                            <div>
                                <label for="banner_2" class="block text-sm font-medium text-gray-700">Banner Halaman 2</label>
                                @if($banner_2_url)
                                    {{-- [FIX] Path asset() yang benar --}}
                                    <img src="{{ asset('public/storage/' . $banner_2_url) }}" alt="Banner 2" class="h-24 w-auto my-2">
                                @endif
                                <input type="file" name="banner_2" id="banner_2" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-red-50 file:text-red-700 hover:file:bg-red-100">
                                @error('banner_2') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>

                            {{-- Banner 3 --}}
                            <div>
                                <label for="banner_3" class="block text-sm font-medium text-gray-700">Banner Halaman 3</label>
                                @if($banner_3_url)
                                    {{-- [FIX] Path asset() yang benar --}}
                                    <img src="{{ asset('public/storage/' . $banner_3_url) }}" alt="Banner 3" class="h-24 w-auto my-2">
                                @endif
                                <input type="file" name="banner_3" id="banner_3" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-red-50 file:text-red-700 hover:file:bg-red-100">
                                @error('banner_3') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>

                            <div class="text-right">
                                <button type="submit" class="py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700">
                                    Simpan Pengaturan Gambar
                                </button>
                            </div>
                        </form>
                    </div>

                    <div class="bg-white p-6 rounded-lg shadow-lg">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">Banner Etalase (Slider Baru)</h3>
                        
                        {{-- Form Tambah Banner Baru --}}
                        <form action="{{ route('admin.settings.banners.store') }}" method="POST" enctype="multipart/form-data" class="mb-6 pb-6 border-b">
                            @csrf
                            <label for="image" class="block text-sm font-medium text-gray-700">Tambah Banner Baru</label>
                            <input type="file" name="image" id="image" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-red-50 file:text-red-700 hover:file:bg-red-100" required>
                            @error('image') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            <button type="submit" class="mt-3 w-full py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700">
                                Upload Banner
                            </button>
                        </form>
                        
                        {{-- Daftar Banner yang Ada --}}
                        <h4 class="text-md font-semibold text-gray-700 mb-2">Daftar Banner</h4>
                        <div class="space-y-4 max-h-96 overflow-y-auto">
                            @forelse($banners as $banner)
                            <div class="flex items-center justify-between space-x-4">
                                {{-- [FIX] Path asset() yang benar --}}
                                <img src="{{ asset('public/storage/' . $banner->image) }}" alt="Banner {{ $banner->id }}" class="h-16 w-32 object-cover rounded-md">
                                <form action="{{ route('admin.settings.banners.destroy', $banner) }}" method="POST" onsubmit="return confirm('Anda yakin ingin menghapus banner ini?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-900">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </form>
                            </div>
                            @empty
                            <p class="text-sm text-gray-500">Belum ada banner etalase.</p>
                            @endforelse
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- 
========================================================================
[PERBAIKAN UTAMA]
Semua skrip @push dipindahkan ke sini, di dalam @section('content')
agar memiliki akses ke variabel Blade seperti $admin dan $penggunaListJson
========================================================================
--}}
@push('scripts')
{{-- Skrip untuk tabel pengguna (dataTable) --}}
<script>
    // [FIX] Definisikan fungsi pada window agar bisa diakses oleh x-data
    window.dataTable = function(users, roles, statuses) {
        
        let parsedUsers = Array.isArray(users) ? users : [];
        
   
        if (!Array.isArray(users) && typeof users === 'string') {
            try {
                parsedUsers = JSON.parse(users || '[]');
            } catch (e) {
                console.error("Data 'users' (penggunaListArray) tidak valid:", e, users);
                parsedUsers = [];
            }
        }
        
        return {
            users: parsedUsers,
            availableRoles: Array.isArray(roles) ? roles : [],
            availableStatuses: Array.isArray(statuses) ? statuses : [],
            searchTerm: '',
            filterRole: 'all',
            filterStatus: 'all',
            sortColumn: 'id_pengguna',
            sortDirection: 'asc',
            
            showDetailModal: false,   // <-- [BARU]
            showEditModal: false, // <--- TAMBAHKAN INI
            selectedUser: null,     // <-- [BARU]


            formatCurrency(value) {
                if (value === null || value === undefined) return 'Rp 0';
                return new Intl.NumberFormat('id-ID', {
                    style: 'currency',
                    currency: 'IDR',
                    minimumFractionDigits: 0,
                    maximumFractionDigits: 0
                }).format(value);
            },
            
            // ======== [BARU] FUNGSI MODAL ========
viewUser(user) {
    this.selectedUser = user;
    this.showEditModal = false; // <--- PASTIKAN MENUTUP KEDUA MODAL
    this.showDetailModal = true;
},
closeModal() {
    this.showDetailModal = false;
    this.showEditModal = false; // <--- PASTIKAN MENUTUP KEDUA MODAL
    // Kita beri sedikit jeda agar transisi selesai sebelum data hilang
    setTimeout(() => {
        this.selectedUser = null;
    }, 300);
},
// =====================================

// Tambahkan fungsi baru ini untuk modal edit
showEditUserModal(user) {
    this.selectedUser = { ...user }; // Copy data user agar tidak langsung mengubah data asli tabel
    this.showEditModal = true;
},
// Tambahkan fungsi untuk menyimpan perubahan (ini hanya contoh, Anda perlu menghubungkannya ke backend Laravel)
saveUserChanges() {
    // Di sini Anda akan mengirim data this.selectedUser ke backend menggunakan AJAX (misalnya Axios)
    // Contoh sederhana:
    console.log('Menyimpan perubahan untuk pengguna:', this.selectedUser);
    alert('Perubahan disimpan! (Simulasi)');
    this.closeModal(); // Tutup modal setelah menyimpan
},

// ======== [BARU] FUNGSI TOGGLE FREEZE ========
async toggleFreeze(user) {
    // Tampilkan konfirmasi
    const confirmationText = user.status === 'Aktif' ? 'MEMBEKUKAN' : 'MENGAKTIFKAN';
    if (!confirm(`Anda yakin ingin ${confirmationText} akun "${user.nama_lengkap}"?`)) {
        return; // Batal jika user menekan 'Cancel'
    }

    // Tentukan URL API (sesuai dengan pola `confirmDelete`)
    let url = `{{ url('admin/users') }}/${user.id_pengguna}/toggle-freeze`;
    
    // Ambil token CSRF dari tag meta
    let csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    try {
        // Kirim permintaan 'POST' ke server
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: JSON.stringify({}) // Kirim body kosong, karena ID sudah di URL
        });

        const data = await response.json();

        if (response.ok && data.success) {
            // Jika SUKSES:
            // 1. Update status di tabel secara langsung (UI Optimistic)
            user.status = data.new_status;
            
            // 2. Beri notifikasi (opsional, bisa diganti notifikasi yang lebih cantik)
            alert(data.message || 'Status berhasil diperbarui!');
            
        } else {
            // Jika GAGAL:
            alert('Gagal memperbarui status: ' + (data.message || 'Error tidak diketahui'));
        }
    } catch (error) {
        console.error('Error saat toggle freeze:', error);
        alert('Terjadi kesalahan koneksi. Lihat console (F12) untuk detail.');
    }
},
// ===========================================

            
            sortBy(column) {
                if (this.sortColumn === column) {
                    this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
                } else {
                    this.sortColumn = column;
                    this.sortDirection = 'asc';
                }
            },
            
            confirmDelete(userId, userName) {
                if (!confirm(`Anda yakin ingin menghapus pengguna "${userName}" (ID: ${userId})?`)) {
                    return;
                }
                let form = document.createElement('form');
                // [FIX] Pastikan route ini ada di web.php Anda, atau sesuaikan
                form.action = `{{ url('admin/users') }}/${userId}`; 
                form.method = 'POST';
                form.style.display = 'none';
                let csrfToken = document.querySelector('meta[name="csrf-token"]').content;
                form.innerHTML = `
                    <input type="hidden" name="_method" value="DELETE">
                    <input type="hidden" name="_token" value="${csrfToken}">
                `;
                document.body.appendChild(form);
                form.submit();
            },
            
            get filteredUsers() {
                try {
                    let filtered = this.users;
                    // 1. Filter
                    if (this.filterRole !== 'all') {
                        filtered = filtered.filter(user => user.role === this.filterRole);
                    }
                    if (this.filterStatus !== 'all') {
                        filtered = filtered.filter(user => user.status === this.filterStatus);
                    }
                    // 2. Search
                    if (this.searchTerm.trim() !== '') {
                        const searchLower = this.searchTerm.toLowerCase();
                        filtered = filtered.filter(user => {
                            return (user.nama_lengkap && user.nama_lengkap.toLowerCase().includes(searchLower)) ||
                                   (user.email && user.email.toLowerCase().includes(searchLower)) ||
                                   (user.store_name && user.store_name.toLowerCase().includes(searchLower)) ||
                                   (user.no_wa && user.no_wa.toLowerCase().includes(searchLower));
                        });
                    }
                    // 3. Sorting
                    filtered.sort((a, b) => {
                        let aVal = a[this.sortColumn];
                        let bVal = b[this.sortColumn];
                        if (this.sortColumn === 'id_pengguna' || this.sortColumn === 'saldo') {
                            aVal = parseFloat(aVal) || 0;
                            bVal = parseFloat(bVal) || 0;
                        } 
                        else if (this.sortColumn === 'last_seen_at' || this.sortColumn === 'created_at') {
                            aVal = aVal ? new Date(aVal).getTime() : 0;
                            bVal = bVal ? new Date(bVal).getTime() : 0;
                        } 
                        else {
                            aVal = (String(aVal || '')).toLowerCase();
                            bVal = (String(bVal || '')).toLowerCase();
                        }
                        if (aVal < bVal) return this.sortDirection === 'asc' ? -1 : 1;
                        if (aVal > bVal) return this.sortDirection === 'asc' ? 1 : -1;
                        return 0;
                    });
                    return filtered;
                } catch (e) {
                    console.error('Error saat filter/sort:', e);
                    return this.users;
                }
            }
        };
    }

    // [FIX] Definisikan fungsi addressFinder pada window
    window.addressFinder = function(searchUrl, geocodeUrl) {
        return {
            // [FIX] Data sekarang diambil dari $admin, yang ada di dalam scope
            fields: {
                province: @json(old('province', $admin->province)),
                city: @json(old('regency', $admin->regency)), 
                subdistrict: @json(old('district', $admin->district)), 
                village: @json(old('village', $admin->village)),
                zip_code: @json(old('postal_code', $admin->postal_code)),
                address_detail: @json(old('address_detail', $admin->address_detail)),
                latitude: @json(old('latitude', $admin->latitude)),
                longitude: @json(old('longitude', $admin->longitude)),
                //kiriminaja_district_id: @json(old('kiriminaja_district_id', $admin->kiriminaja_district_id)),
                //kiriminaja_subdistrict_id: @json(old('kiriminaja_subdistrict_id', $admin->kiriminaja_subdistrict_id)),
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
                    // [FIX] Tambahkan header CSRF untuk keamanan
                    const response = await fetch(`${searchUrl}?query=${encodeURIComponent(this.searchQuery)}`, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        }
                    });
                    
                  
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
                //this.fields.kiriminaja_district_id = result.district_id;
                //this.fields.kiriminaja_subdistrict_id = result.subdistrict_id;
                
                this.searchQuery = '';
                this.results = [];
                
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
                    this.geocodeMessage = 'Harap isi detail alamat lebih lengkap.';
                    this.geocoding = false;
                    return;
                }

                try {
                    // [FIX] Tambahkan header CSRF untuk keamanan
                    const response = await fetch(`${geocodeUrl}?address=${encodeURIComponent(fullAddress)}`, {
                         headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        }
                    });
                    const data = await response.json();
                    
                    if (data.success && data.data.lat) {
                        this.fields.latitude = data.data.lat;
                        this.fields.longitude = data.data.lng;
                        this.geocodeMessage = `Koordinat ditemukan: ${data.data.lat}, ${data.data.lng}`;
                    } else {
                        this.geocodeMessage = 'Koordinat tidak ditemukan untuk alamat ini.';
                    }
                } catch (error) {
                    console.error('Error geocoding:', error);
                    this.geocodeMessage = 'Gagal terhubung ke server geocoding.';
                } finally {
                    this.geocoding = false;
                }
            }
        };
    }
    
    document.addEventListener('alpine:init', () => {
    console.info('%c[INFO] Data dari Laravel dimuat:', 'color: green; font-weight: bold;');
    console.log('Daftar Pengguna:', @json($penggunaList ?? []));
    console.log('Roles:', @json($roles ?? []));
    console.log('Statuses:', @json($statuses ?? []));
});
</script>
@endpush

@endsection