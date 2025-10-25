@extends('layouts.admin')



@section('title', 'Pengaturan Aplikasi')

@section('page-title', 'Pengaturan Aplikasi')



@section('content')

<div class="container mx-auto px-4 sm:px-8 py-8">

    <div x-data="{ activeTab: 'profile' }" class="w-full">

        <!-- Tab Headers -->

        <div class="mb-4 border-b border-gray-200">

            <nav class="-mb-px flex space-x-8" aria-label="Tabs">

                <button @click="activeTab = 'profile'" :class="{ 'border-indigo-500 text-indigo-600': activeTab === 'profile', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'profile' }"

                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">

                    Profil Admin

                </button>

                <button @click="activeTab = 'slider'" :class="{ 'border-indigo-500 text-indigo-600': activeTab === 'slider', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'slider' }"

                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">

                    Slider Informasi

                </button>

                <button @click="activeTab = 'customer'" :class="{ 'border-indigo-500 text-indigo-600': activeTab === 'customer', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'customer' }"

                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">

                    Pengaturan Pelanggan

                </button>

            </nav>

        </div>



        {{-- Flasher Notifikasi --}}

        @if (session('success'))

            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)" x-transition

                 class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-md shadow-md" role="alert">

                <p>{{ session('success') }}</p>

            </div>

        @endif



        @if ($errors->any())

            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-md shadow-md" role="alert">

                <ul>

                    @foreach ($errors->all() as $error)

                        <li>{{ $error }}</li>

                    @endforeach

                </ul>

            </div>

        @endif





        <!-- Tab Content -->

        <div>

            {{-- 1. Tab Profil Admin --}}

            <div x-show="activeTab === 'profile'" x-cloak>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">

                    <!-- Form Update Profil -->

                    <div class="md:col-span-2 bg-white p-6 rounded-lg shadow-lg">

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

                                                <img class="h-full w-full object-cover" src="{{ Auth::user()->photo_profile ? Storage::url(Auth::user()->photo_profile) : 'https://ui-avatars.com/api/?name=' . urlencode(Auth::user()->nama_lengkap) . '&color=7F9CF5&background=EBF4FF' }}" alt="Profil">

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

                                </div>



                                <div>

                                    <label for="nama_lengkap" class="block text-sm font-medium text-gray-700">Nama Lengkap</label>

                                    <input type="text" name="nama_lengkap" id="nama_lengkap" value="{{ old('nama_lengkap', $admin->nama_lengkap) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">

                                </div>

                                <div>

                                    <label for="email" class="block text-sm font-medium text-gray-700">Alamat Email</label>

                                    <input type="email" name="email" id="email" value="{{ old('email', $admin->email) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">

                                </div>

                                <div>

                                    <label for="no_hp" class="block text-sm font-medium text-gray-700">Nomor HP</label>

                                    <input type="text" name="no_hp" id="no_hp" value="{{ old('no_hp', $admin->no_hp) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">

                                </div>

                                <div>

                                    <label for="alamat" class="block text-sm font-medium text-gray-700">Alamat</label>

                                    <textarea name="alamat" id="alamat" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">{{ old('alamat', $admin->alamat) }}</textarea>

                                </div>

                                <div>

                                    <button type="submit" class="w-full inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">Simpan Perubahan</button>

                                </div>

                            </div>

                        </form>

                    </div>

                    <!-- Form Update Password -->

                    <div class="md:col-span-1 bg-white p-6 rounded-lg shadow-lg">

                        <h3 class="text-lg font-semibold text-gray-800 mb-4">Ubah Password</h3>

                        <form action="{{ route('admin.settings.password.update') }}" method="POST">

                            @csrf

                            @method('PUT')

                            <div class="space-y-4">

                                <div>

                                    <label for="current_password" class="block text-sm font-medium text-gray-700">Password Saat Ini</label>

                                    <input type="password" name="current_password" id="current_password" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>

                                </div>

                                <div>

                                    <label for="password" class="block text-sm font-medium text-gray-700">Password Baru</label>

                                    <input type="password" name="password" id="password" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>

                                </div>

                                <div>

                                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Konfirmasi Password Baru</label>

                                    <input type="password" name="password_confirmation" id="password_confirmation" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>

                                </div>

                                <div>

                                    <button type="submit" class="w-full inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">Ubah Password</button>

                                </div>

                            </div>

                        </form>

                    </div>

                </div>

            </div>



            {{-- 2. Tab Slider Informasi --}}

            <div x-show="activeTab === 'slider'" x-cloak>

                <div x-data='{ slides: @json($slides) }' class="bg-white p-6 rounded-lg shadow-lg">

                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Pengaturan Slider Informasi</h3>

                    <p class="text-sm text-gray-600 mb-6">Atur gambar dan teks yang akan ditampilkan di slider dashboard admin.</p>

                    <form action="{{ route('admin.settings.slider.update') }}" method="POST">

                        @csrf

                        @method('PUT')

                        <div class="space-y-6">

                            <template x-for="(slide, index) in slides" :key="index">

                                <div class="p-4 border rounded-md">

                                    <h4 class="font-medium mb-2" x-text="'Slide ' + (index + 1)"></h4>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                                        <div>

                                            <label class="block text-sm font-medium text-gray-700">URL Gambar</label>

                                            <input type="url" :name="'slides['+index+'][img]'" x-model="slide.img" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" placeholder="https://placehold.co/...">

                                        </div>

                                        <div>

                                            <label class="block text-sm font-medium text-gray-700">Judul</label>

                                            <input type="text" :name="'slides['+index+'][title]'" x-model="slide.title" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">

                                        </div>

                                        <div class="md:col-span-2">

                                            <label class="block text-sm font-medium text-gray-700">Deskripsi</label>

                                            <textarea :name="'slides['+index+'][desc]'" x-model="slide.desc" rows="2" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"></textarea>

                                        </div>

                                    </div>

                                    <div class="text-right mt-2">

                                        <button type="button" @click="slides.splice(index, 1)" class="text-sm text-red-600 hover:text-red-800">Hapus Slide</button>

                                    </div>

                                </div>

                            </template>

                        </div>

                        <div class="mt-6 flex justify-between items-center">

                            <button type="button" @click="slides.push({img: '', title: '', desc: ''})" class="py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">Tambah Slide</button>

                            <button type="submit" class="py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">Simpan Pengaturan Slider</button>

                        </div>

                    </form>

                </div>

            </div>



            {{-- 3. Tab Pengaturan Pelanggan --}}

            <div x-show="activeTab === 'customer'" x-cloak>

                <div class="bg-white p-6 rounded-lg shadow-lg">

                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Pengaturan Umum</h3>

                    <form action="{{ route('admin.settings.general.update') }}" method="POST">

                        @csrf

                        @method('PUT')

                        <div class="space-y-4">

                            <div class="relative flex items-start">

                                <div class="flex items-center h-5">

                                    <input id="auto_freeze" name="auto_freeze" type="checkbox" {{ $autoFreeze ? 'checked' : '' }} class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded">

                                </div>

                                <div class="ml-3 text-sm">

                                    <label for="auto_freeze" class="font-medium text-gray-700">Bekukan Akun Otomatis</label>

                                    <p class="text-gray-500">Jika diaktifkan, sistem akan membekukan akun pelanggan yang memiliki tunggakan pembayaran melebihi batas waktu.</p>

                                </div>

                            </div>

                        </div>

                        <div class="mt-6 text-right">

                             <button type="submit" class="py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">Simpan Pengaturan</button>

                        </div>

                    </form>

                </div>

            </div>

        </div>

    </div>

</div>

@endsection

