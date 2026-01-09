@extends('layouts.admin')

@section('title', 'Edit Pengguna: ' . $data->nama_lengkap)

@section('content')

<div class="py-6 px-4 sm:px-6 lg:px-8">
<h1 class="text-2xl font-bold text-gray-900 mb-6">Edit Data Akun Pengguna</h1>

{{-- Fix #1: Display errors above the form --}}
@if ($errors->any())
<div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg">
    <p class="font-semibold mb-1">Terdapat kesalahan:</p>
    <ul class="list-disc list-inside">
    @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
    @endforeach
    </ul>
</div>
@endif

<div class="bg-white shadow overflow-hidden sm:rounded-lg">
<form action="{{ route('admin.customers.data.pengguna.update', $data->id_pengguna) }}" method="POST" enctype="multipart/form-data">
@csrf
@method('PUT')
    <div class="p-6 space-y-6">
        
        {{-- BLOK 1: INFORMASI PERSONAL & AKUN --}}
        <div class="border-b border-gray-200 pb-4">
            <h3 class="text-lg font-medium leading-6 text-indigo-900">Informasi Personal</h3>
            <p class="mt-1 text-sm text-gray-500">Ubah data dasar pengguna.</p>
        </div>
        
        <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-2">
            
            {{-- Nama Lengkap --}}
            <div class="sm:col-span-1">
                <label for="nama_lengkap" class="block text-sm font-medium text-gray-700">Nama Lengkap</label>
                <input type="text" name="nama_lengkap" id="nama_lengkap" value="{{ old('nama_lengkap', $data->nama_lengkap) }}" 
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                @error('nama_lengkap')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
            </div>
            
            {{-- Email --}}
            <div class="sm:col-span-1">
                <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                <input type="email" name="email" id="email" value="{{ old('email', $data->email) }}" 
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                @error('email')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
            </div>

            {{-- No. WhatsApp --}}
            <div class="sm:col-span-1">
                <label for="no_wa" class="block text-sm font-medium text-gray-700">No. WhatsApp</label>
                <input type="text" name="no_wa" id="no_wa" value="{{ old('no_wa', $data->no_wa) }}" 
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                @error('no_wa')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
            </div>

            {{-- Role --}}
            <div class="sm:col-span-1">
                <label for="role" class="block text-sm font-medium text-gray-700">Role</label>
                <select id="role" name="role" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    <option value="Admin" @selected(old('role', $data->role) == 'Admin')>Admin</option>
                    <option value="Seller" @selected(old('role', $data->role) == 'Seller')>Seller</option>
                    <option value="Pelanggan" @selected(old('role', $data->role) == 'Pelanggan')>Pelanggan</option>
                </select>
                @error('role')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
            </div>

            {{-- Status Akun --}}
            <div class="sm:col-span-1">
                <label for="status" class="block text-sm font-medium text-gray-700">Status Akun</label>
                <select id="status" name="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    <option value="Aktif" @selected(old('status', $data->status) == 'Aktif')>Aktif</option>
                    <option value="Beku" @selected(old('status', $data->status) == 'Beku')>Beku</option>
                    <option value="Nonaktif" @selected(old('status', $data->status) == 'Nonaktif')>Nonaktif</option>
                </select>
                @error('status')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
            </div>
        </div>

        {{-- BLOK 2: INFORMASI TOKO & ALAMAT --}}
        <div class="border-t border-gray-200 pt-6">
            <h3 class="text-lg font-medium leading-6 text-teal-900">Informasi Toko & Lokasi</h3>
            <p class="mt-1 text-sm text-gray-500">Ubah data toko dan alamat pengguna.</p>
        </div>
        
        <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-2">
            
            {{-- Nama Toko --}}
            <div class="sm:col-span-1">
                <label for="store_name" class="block text-sm font-medium text-gray-700">Nama Toko</label>
                <input type="text" name="store_name" id="store_name" value="{{ old('store_name', $data->store_name) }}" 
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                @error('store_name')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
            </div>
            
            {{-- Logo Toko (Display) --}}
            <div class="sm:col-span-1">
                <label for="store_logo" class="block text-sm font-medium text-gray-700">Logo Toko (Hanya Display)</label>
                <div class="mt-1 flex items-center space-x-3">
                    @if ($data->store_logo_path)
                        <img src="{{ asset('public/storage/' . $data->store_logo_path) }}" alt="Logo Toko" class="h-10 w-10 object-cover rounded-full">
                    @else
                        <span class="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center text-xs text-gray-500">No Logo</span>
                    @endif
                    <span class="text-sm text-gray-500 truncate max-w-xs">File path: {{ $data->store_logo_path ?? '—' }}</span>
                </div>
            </div>

            {{-- Address Detail --}}
            <div class="sm:col-span-2">
                <label for="address_detail" class="block text-sm font-medium text-gray-700">Detail Alamat (Jalan, No. Rumah, RT/RW)</label>
                <textarea id="address_detail" name="address_detail" rows="3" 
                          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">{{ old('address_detail', $data->address_detail) }}</textarea>
                @error('address_detail')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
            </div>
            
            {{-- Provinsi Saat Ini --}}
            <div class="sm:col-span-1">
                <label class="block text-sm font-medium text-gray-700">Provinsi Saat Ini</label>
                <p class="mt-1 text-sm text-gray-900">{{ $data->province ?? '—' }}</p>
            </div>
            {{-- Kota/Kabupaten Saat Ini --}}
            <div class="sm:col-span-1">
                <label class="block text-sm font-medium text-gray-700">Kota/Kabupaten Saat Ini</label>
                <p class="mt-1 text-sm text-gray-900">{{ $data->regency ?? '—' }}</p>
            </div>

        </div>

        {{-- BLOK 3: INFORMASI KEUNGAN & BANK --}}
        <div class="border-t border-gray-200 pt-6">
            <h3 class="text-lg font-medium leading-6 text-blue-900">Informasi Keuangan & Bank</h3>
            <p class="mt-1 text-sm text-gray-500">Ubah data rekening bank pengguna.</p>
        </div>

        <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-2">
            
            {{-- Nama Bank --}}
            <div class="sm:col-span-1">
                <label for="bank_name" class="block text-sm font-medium text-gray-700">Nama Bank</label>
                <input type="text" name="bank_name" id="bank_name" value="{{ old('bank_name', $data->bank_name) }}" 
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
            </div>

            {{-- Nomor Rekening --}}
            <div class="sm:col-span-1">
                <label for="bank_account_number" class="block text-sm font-medium text-gray-700">Nomor Rekening</label>
                <input type="text" name="bank_account_number" id="bank_account_number" value="{{ old('bank_account_number', $data->bank_account_number) }}" 
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
            </div>

            {{-- Nama Pemilik Rekening --}}
            <div class="sm:col-span-2">
                <label for="bank_account_name" class="block text-sm font-medium text-gray-700">Nama Pemilik Rekening</label>
                <input type="text" name="bank_account_name" id="bank_account_name" value="{{ old('bank_account_name', $data->bank_account_name) }}" 
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
            </div>
        </div>


        {{-- BLOK 4: UBAH PASSWORD --}}
        <div class="border-t border-gray-200 pt-6">
            <h3 class="text-lg font-medium leading-6 text-red-900">Ubah Password & Hash Saat Ini</h3>
            <p class="mt-1 text-sm text-gray-500">Kosongkan field di bawah jika Anda tidak ingin mengubah password.</p>
        </div>
        
        <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-2">
            
            {{-- Password Hash Saat Ini (Display only) --}}
            <div class="sm:col-span-2">
                <label for="current_hash" class="block text-sm font-medium text-gray-700">Password Hash Saat Ini</label>
                <div class="relative mt-1">
                     {{-- Menggunakan input type=password agar bisa di-toggle --}}
                     <input type="password" id="current_hash" value="{{ $data->password_hash ?? 'Tidak Ada Hash' }}" readonly
                           class="block w-full rounded-md border-gray-300 bg-gray-50 shadow-sm sm:text-sm pr-10 truncate">
                    
                    {{-- Tombol Show/Hide Hash --}}
                    <button type="button" onclick="toggleVisibility('current_hash')" class="absolute inset-y-0 right-0 px-3 flex items-center text-gray-400 hover:text-gray-600">
                        <svg id="current_hash_icon" class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/><path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/></svg>
                    </button>
                </div>
            </div>
            
            {{-- Password Baru --}}
            <div class="sm:col-span-1">
                <label for="password" class="block text-sm font-medium text-gray-700">Password Baru</label>
                <input type="password" name="password" id="password" autocomplete="new-password"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                @error('password')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
            </div>

            {{-- Konfirmasi Password Baru --}}
            <div class="sm:col-span-1">
                <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Konfirmasi Password Baru</label>
                <input type="password" name="password_confirmation" id="password_confirmation" autocomplete="new-password"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                @error('password_confirmation')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
            </div>
        </div>

    </div>

    <div class="px-4 py-3 bg-gray-50 text-right sm:px-6 flex justify-between">
        <a href="{{ route('admin.customers.data.pengguna.index') }}" class="inline-flex justify-center rounded-md border border-gray-300 bg-white py-2 px-4 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-100">
            Batal
        </a>
        <button type="submit" class="inline-flex justify-center rounded-md border border-transparent bg-indigo-600 py-2 px-4 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
            Simpan Perubahan
        </button>
    </div>
</form>
</div>
</div>

@verbatim
<script>
function toggleVisibility(fieldId) {
    const field = document.getElementById(fieldId);
    const icon = document.getElementById(fieldId + '_icon');

    // Toggle type antara 'password' dan 'text'
    field.type = field.type === 'password' ? 'text' : 'password';

    // Ganti ikon mata
    if (field.type === 'text') {
        // Ikon Mata Terbuka - Use backticks (`) for the complex SVG string
        icon.innerHTML = `<path fill-rule="evenodd" d="M3.28 2.22a.75.75 0 00-1.06 1.06l16.44 16.44a.75.75 0 101.06-1.06L3.28 2.22z" clip-rule="evenodd" /><path d="M12.983 2.146A.75.75 0 0012 3v1.5a.75.75 0 001.5 0V3.75c0-.414-.336-.75-.75-.75h-1.077l3.633 3.633a.75.75 0 00.902-1.206L14.077 3h1.173a2.25 2.25 0 012.25 2.25v2.73l2.844 2.844a.75.75 0 00-.53 1.282l-2.844-2.844v3.184a2.25 2.25 0 01-2.25 2.25h-4.688l-3.21 3.21a.75.75 0 001.06 1.06l3.21-3.21h4.687a3.75 3.75 0 003.75-3.75V10c0-.853.185-1.674.52-2.428L18.78 6.72a.75.75 0 10-1.06-1.06l-2.074 2.073A5.228 5.228 0 0012 6.75V5.25a.75.75 0 00-1.5 0V6.75a3.75 3.75 0 00-3.633 3.003L4.854 8.78a.75.75 0 10-1.06 1.06l3.21 3.21A5.25 5.25 0 0010 15.25h.61l-3.006-3.006a.75.75 0 10-1.06 1.06l3.006 3.006H10a6.75 6.75 0 01-5.184-2.428L2.22 13.28a.75.75 0 101.06-1.06l2.073-2.074A5.228 5.228 0 007.5 7.027V5.25a.75.75 0 00-1.5 0V7.5a.75.75 0 001.5 0V6.75a.75.75 0 00-.75-.75h-.354l-1.01-1.01a.75.75 0 00-1.06 1.06l1.01 1.01A5.25 5.25 0 005.25 9.75a.75.75 0 001.06.84L7.5 10.34l.28-.28A.75.75 0 007.22 9.22z"/>`;
    } else {
        // Ikon Mata Tertutup - Use backticks (`) for the complex SVG string
        icon.innerHTML = `<path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/><path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/>`;
    }
}
</script>
@endverbatim
@endsection