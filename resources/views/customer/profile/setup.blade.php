{{--
    File: resources/views/customer/profile/edit.blade.php
    Deskripsi: Halaman untuk mengedit detail profil pelanggan.
--}}
@extends('layouts.customer')

@section('styles')
{{-- Tidak ada style khusus yang diperlukan untuk input manual --}}
@endsection

@section('content')
<div class="p-6 lg:p-8 bg-slate-50 min-h-screen">
    <div class="max-w-7xl mx-auto">

        <div class="mb-8">
            <h1 class="text-3xl font-bold text-slate-800 tracking-tight">Setup Profil</h1>
            <p class="mt-2 text-sm text-slate-500">Lengkapi informasi akun, alamat, dan toko Anda di sini.</p>
        </div>

        {{-- Menampilkan pesan error validasi --}}
        @if ($errors->any())
            <div class="mb-6 bg-red-50 border-l-4 border-red-400 text-red-800 p-4 rounded-r-lg" role="alert">
                <p class="font-bold">Oops! Terjadi kesalahan.</p>
                <ul class="mt-2 list-disc list-inside text-sm">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('customer.profile.update.setup', $user->setup_token) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="bg-white shadow-lg rounded-xl overflow-hidden border border-slate-200">
                <div class="p-6 md:p-8">
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                        
                        {{-- Kolom Kiri (Informasi Akun & Toko) --}}
                        <div class="lg:col-span-1 space-y-6">
                            <div class="flex flex-col items-center text-center">
                                <img id="logo-preview" src="{{ $user->store_logo_path ? asset('storage/' . $user->store_logo_path) : 'https://placehold.co/128x128/e2e8f0/64748b?text=Logo' }}" 
                                     alt="Logo Toko" 
                                     class="h-24 w-24 rounded-full object-cover bg-slate-200 border-4 border-white shadow-md">
                                <label for="store_logo" class="mt-4 cursor-pointer text-sm font-semibold text-indigo-600 hover:text-indigo-800 transition">
                                    Ubah Logo
                                </label>
                                <input type="file" name="store_logo" id="store_logo" class="sr-only" accept="image/*">
                                @error('store_logo') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="nama_lengkap" class="block text-sm font-medium text-slate-700">Nama Lengkap</label>
                                <input type="text" name="nama_lengkap" id="nama_lengkap" value="{{ old('nama_lengkap', $user->nama_lengkap) }}" class="mt-1 block w-full border-slate-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                @error('nama_lengkap') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="email_display" class="block text-sm font-medium text-slate-700">Email</label>
                                <input type="email" id="email_display" value="{{ $user->email }}" class="mt-1 block w-full border-slate-300 rounded-lg shadow-sm bg-slate-100 cursor-not-allowed" readonly>
                                {{-- ✅ DIPERBAIKI: Menambahkan input tersembunyi untuk email agar lolos validasi --}}
                                <input type="hidden" name="email" value="{{ $user->email }}">
                            </div>
                            <div>
                                <label for="no_wa" class="block text-sm font-medium text-slate-700">No. WhatsApp</label>
                                <input type="text" name="no_wa" id="no_wa" value="{{ old('no_wa', $user->no_wa) }}" class="mt-1 block w-full border-slate-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                @error('no_wa') <p class="text-xs text-red-600 mt-1">{{ $message }}</p @enderror
                            </div>
                            <div>
                                <label for="store_name" class="block text-sm font-medium text-slate-700">Nama Toko</label>
                                <input type="text" name="store_name" id="store_name" value="{{ old('store_name', $user->store_name) }}" class="mt-1 block w-full border-slate-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                @error('store_name') <p class="text-xs text-red-600 mt-1">{{ $message }}</p @enderror
                            </div>
                        </div>

                        {{-- Kolom Kanan (Informasi Alamat & Bank) --}}
                        <div class="lg:col-span-2 grid grid-cols-1 sm:grid-cols-2 gap-8">
                            <div>
                                <h3 class="text-lg font-semibold text-slate-800 border-b border-slate-200 pb-3 mb-4">Alamat Utama</h3>
                                <div class="space-y-4">
                                    {{-- Input teks manual untuk alamat --}}
                                    <div>
                                        <label for="province" class="block text-sm font-medium text-slate-700">Provinsi</label>
                                        <input type="text" name="province" id="province" value="{{ old('province', $user->province) }}" class="mt-1 block w-full border-slate-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                    </div>
                                    <div>
                                        <label for="regency" class="block text-sm font-medium text-slate-700">Kabupaten/Kota</label>
                                        <input type="text" name="regency" id="regency" value="{{ old('regency', $user->regency) }}" class="mt-1 block w-full border-slate-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                    </div>
                                    <div>
                                        <label for="district" class="block text-sm font-medium text-slate-700">Kecamatan</label>
                                        <input type="text" name="district" id="district" value="{{ old('district', $user->district) }}" class="mt-1 block w-full border-slate-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                    </div>
                                    <div>
                                        <label for="village" class="block text-sm font-medium text-slate-700">Desa/Kelurahan</label>
                                        <input type="text" name="village" id="village" value="{{ old('village', $user->village) }}" class="mt-1 block w-full border-slate-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                    </div>
                                    <div>
                                        <label for="postal_code" class="block text-sm font-medium text-slate-700">Kode Pos</label>
                                        <input type="text" name="postal_code" id="postal_code" value="{{ old('postal_code', $user->postal_code) }}" class="mt-1 block w-full border-slate-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label for="address_detail" class="block text-sm font-medium text-slate-700">Alamat Detail</label>
                                        <textarea name="address_detail" id="address_detail" rows="3" class="mt-1 block w-full border-slate-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500">{{ old('address_detail', $user->address_detail) }}</textarea>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-slate-800 border-b border-slate-200 pb-3 mb-4">Informasi Bank</h3>
                                <div class="space-y-4">
                                    <div>
                                        <label for="bank_name" class="block text-sm font-medium text-slate-700">Nama Bank</label>
                                        <input type="text" name="bank_name" id="bank_name" value="{{ old('bank_name', $user->bank_name) }}" class="mt-1 block w-full border-slate-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                    </div>
                                    <div>
                                        <label for="bank_account_name" class="block text-sm font-medium text-slate-700">Nama Pemilik Rekening</label>
                                        <input type="text" name="bank_account_name" id="bank_account_name" value="{{ old('bank_account_name', $user->bank_account_name) }}" class="mt-1 block w-full border-slate-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                    </div>
                                    <div>
                                        <label for="bank_account_number" class="block text-sm font-medium text-slate-700">Nomor Rekening</label>
                                        <input type="text" name="bank_account_number" id="bank_account_number" value="{{ old('bank_account_number', $user->bank_account_number) }}" class="mt-1 block w-full border-slate-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-8 pt-6 border-t border-slate-200 flex justify-end items-center gap-4 p-6 md:p-8">
                    <a href="{{ url('/customer/profile') }}" class="text-sm font-semibold text-slate-600 hover:text-slate-800">Batal</a>
                    <button type="submit" class="inline-flex items-center px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-sm rounded-lg shadow-md transition duration-150 ease-in-out">
                        Simpan Perubahan
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Logo preview logic
    const logoInput = document.getElementById('store_logo');
    const logoPreview = document.getElementById('logo-preview');
    
    if(logoInput && logoPreview) {
        logoInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    logoPreview.src = event.target.result;
                }
                reader.readAsDataURL(file);
            }
        });
    }
});
</script>
@endpush
