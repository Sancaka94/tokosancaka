@extends('layouts.admin')

@section('title', 'Edit Pengguna: ' . $data->nama_lengkap)

@section('content')

<div class="py-6 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-4">
        <h1 class="text-2xl font-semibold text-gray-800">Edit Data Akun Pengguna</h1>
        <a href="{{ route('admin.customers.data.pengguna.index') }}" class="mt-3 sm:mt-0 inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
            <svg class="-ml-1 mr-2 h-5 w-5 text-gray-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
            </svg>
            Kembali
        </a>
    </div>

    {{-- Error Banner --}}
    @if ($errors->any())
    <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-md">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-red-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-red-800">Terdapat {{ $errors->count() }} kesalahan pada form:</h3>
                <div class="mt-2 text-sm text-red-700">
                    <ul class="list-disc pl-5 space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>
    @endif

    <form action="{{ route('admin.customers.data.pengguna.update', $data->id_pengguna) }}" method="POST" enctype="multipart/form-data" class="bg-white border border-gray-200 shadow-sm rounded-lg">
        @csrf
        @method('PUT')

        <div class="p-5 sm:p-8 space-y-10">

            {{-- BLOK 1: INFORMASI PERSONAL & AKUN --}}
            <div>
                <div class="border-b border-gray-200 pb-3 mb-5">
                    <h3 class="text-lg font-semibold text-gray-800 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                        Informasi Personal
                    </h3>
                    <p class="mt-1 text-sm text-gray-500">Ubah data dasar dan identitas pengguna.</p>
                </div>

                <div class="grid grid-cols-1 gap-y-5 gap-x-6 sm:grid-cols-2">

                    {{-- Nama Lengkap --}}
                    <div class="col-span-1">
                        <label for="nama_lengkap" class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap <span class="text-red-500">*</span></label>
                        <input type="text" name="nama_lengkap" id="nama_lengkap" value="{{ old('nama_lengkap', $data->nama_lengkap) }}" required
                               class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                        @error('nama_lengkap')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>

                    {{-- Email --}}
                    <div class="col-span-1">
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email <span class="text-red-500">*</span></label>
                        <div class="relative rounded-md shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-4 w-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z" /><path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" /></svg>
                            </div>
                            <input type="email" name="email" id="email" value="{{ old('email', $data->email) }}" required
                                   class="block w-full pl-10 sm:text-sm border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        @error('email')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>

                    {{-- No. WhatsApp --}}
                    <div class="col-span-1">
                        <label for="no_wa" class="block text-sm font-medium text-gray-700 mb-1">No. WhatsApp</label>
                        <div class="relative rounded-md shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-4 w-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M12 2C6.48 2 2 6.48 2 12c0 2.17.69 4.18 1.83 5.84L2 22l4.27-1.78A9.973 9.973 0 0012 22c5.52 0 10-4.48 10-10S17.52 2 12 2zm5.45 13.91c-.26.74-1.52 1.4-2.12 1.48-.56.07-1.28.18-3.55-1.12-2.73-1.55-4.51-4.32-4.65-4.5-.14-.18-1.11-1.48-1.11-2.82s.69-1.99.93-2.28c.24-.29.53-.36.71-.36.18 0 .36.02.51.02.16.02.39-.06.6.45.22.52.71 1.74.77 1.88.06.14.1.31.01.5-.09.18-.14.29-.28.45-.14.16-.29.34-.41.48-.14.16-.16.32.01.62.17.31.76 1.28 1.88 2.14 1.45 1.11 2.37 1.44 2.68 1.59.31.14.49.12.67-.06.18-.18.78-.9.99-1.21.21-.31.42-.26.71-.16.29.11 1.82.86 2.14 1.01.31.16.53.24.6.38.08.14.08.82-.18 1.56z" clip-rule="evenodd" /></svg>
                            </div>
                            <input type="text" name="no_wa" id="no_wa" value="{{ old('no_wa', $data->no_wa) }}"
                                   class="block w-full pl-10 sm:text-sm border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        @error('no_wa')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>

                    {{-- Role --}}
                    <div class="col-span-1">
                        <label for="role" class="block text-sm font-medium text-gray-700 mb-1">Peran (Role) <span class="text-red-500">*</span></label>
                        <select id="role" name="role" required class="block w-full bg-white rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            <option value="Pelanggan" @selected(old('role', $data->role) == 'Pelanggan')>Pelanggan</option>
                            <option value="Seller" @selected(old('role', $data->role) == 'Seller')>Seller (Mitra)</option>
                            <option value="Admin" @selected(old('role', $data->role) == 'Admin')>Admin</option>
                        </select>
                        @error('role')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>

                    {{-- Status Akun --}}
                    <div class="col-span-1">
                        <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status Akun <span class="text-red-500">*</span></label>
                        <select id="status" name="status" required class="block w-full bg-white rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            <option value="Aktif" @selected(old('status', $data->status) == 'Aktif')>🟢 Aktif</option>
                            <option value="Beku" @selected(old('status', $data->status) == 'Beku')>🟡 Dibekukan (Suspended)</option>
                            <option value="Nonaktif" @selected(old('status', $data->status) == 'Nonaktif')>🔴 Nonaktif (Belum Verifikasi)</option>
                        </select>
                        @error('status')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>

            {{-- BLOK 2: INFORMASI TOKO & ALAMAT --}}
            <div>
                <div class="border-b border-gray-200 pb-3 mb-5">
                    <h3 class="text-lg font-semibold text-gray-800 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                        Informasi Toko & Lokasi
                    </h3>
                    <p class="mt-1 text-sm text-gray-500">Hanya relevan jika pengguna adalah Seller atau memiliki alamat spesifik.</p>
                </div>

                <div class="grid grid-cols-1 gap-y-5 gap-x-6 sm:grid-cols-2">

                    {{-- Nama Toko --}}
                    <div class="col-span-1">
                        <label for="store_name" class="block text-sm font-medium text-gray-700 mb-1">Nama Toko</label>
                        <input type="text" name="store_name" id="store_name" value="{{ old('store_name', $data->store_name) }}" placeholder="Contoh: Toko Sancaka"
                               class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                        @error('store_name')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>

                    {{-- Logo Toko (Display) --}}
                    <div class="col-span-1">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Logo Toko Saat Ini</label>
                        <div class="flex items-center space-x-4 p-2 bg-gray-50 rounded-md border border-gray-200">
                            @if ($data->store_logo_path)
                                <img src="{{ asset('public/storage/' . $data->store_logo_path) }}" alt="Logo Toko" class="h-10 w-10 object-cover rounded shadow-sm border border-gray-300">
                            @else
                                <div class="h-10 w-10 rounded bg-gray-200 flex items-center justify-center border border-gray-300">
                                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                </div>
                            @endif
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-medium text-gray-900 truncate">File Path:</p>
                                <p class="text-xs text-gray-500 truncate" title="{{ $data->store_logo_path }}">{{ $data->store_logo_path ?? 'Belum ada logo diunggah' }}</p>
                            </div>
                        </div>
                    </div>

                    {{-- Address Detail --}}
                    <div class="sm:col-span-2">
                        <label for="address_detail" class="block text-sm font-medium text-gray-700 mb-1">Detail Alamat (Jalan, No. Rumah, RT/RW)</label>
                        <textarea id="address_detail" name="address_detail" rows="3" placeholder="Masukkan alamat lengkap pengiriman/toko..."
                                  class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">{{ old('address_detail', $data->address_detail) }}</textarea>
                        @error('address_detail')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>

                    {{-- Info Geografis --}}
                    <div class="sm:col-span-2">
                        <div class="bg-gray-50 p-3 rounded-md border border-gray-200 flex flex-col sm:flex-row sm:items-center sm:space-x-8">
                            <div class="mb-2 sm:mb-0">
                                <span class="block text-xs font-medium text-gray-500 uppercase tracking-wider">Provinsi Terdaftar</span>
                                <span class="block text-sm text-gray-900">{{ $data->province ?? 'Belum Diatur' }}</span>
                            </div>
                            <div>
                                <span class="block text-xs font-medium text-gray-500 uppercase tracking-wider">Kota/Kabupaten</span>
                                <span class="block text-sm text-gray-900">{{ $data->regency ?? 'Belum Diatur' }}</span>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            {{-- BLOK 3: INFORMASI KEUNGAN & BANK --}}
            <div>
                <div class="border-b border-gray-200 pb-3 mb-5">
                    <h3 class="text-lg font-semibold text-gray-800 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                        Informasi Rekening Bank
                    </h3>
                    <p class="mt-1 text-sm text-gray-500">Data rekening yang digunakan untuk pencairan dana (Withdrawal).</p>
                </div>

                <div class="grid grid-cols-1 gap-y-5 gap-x-6 sm:grid-cols-2">

                    {{-- Nama Bank --}}
                    <div class="col-span-1">
                        <label for="bank_name" class="block text-sm font-medium text-gray-700 mb-1">Nama Bank</label>
                        <input type="text" name="bank_name" id="bank_name" value="{{ old('bank_name', $data->bank_name) }}" placeholder="Contoh: BCA, Mandiri, BRI"
                               class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                    </div>

                    {{-- Nomor Rekening --}}
                    <div class="col-span-1">
                        <label for="bank_account_number" class="block text-sm font-medium text-gray-700 mb-1">Nomor Rekening</label>
                        <input type="text" name="bank_account_number" id="bank_account_number" value="{{ old('bank_account_number', $data->bank_account_number) }}" placeholder="Contoh: 1234567890"
                               class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm font-mono">
                    </div>

                    {{-- Nama Pemilik Rekening --}}
                    <div class="sm:col-span-2">
                        <label for="bank_account_name" class="block text-sm font-medium text-gray-700 mb-1">Nama Pemilik Rekening (Sesuai Buku Tabungan)</label>
                        <input type="text" name="bank_account_name" id="bank_account_name" value="{{ old('bank_account_name', $data->bank_account_name) }}" placeholder="Contoh: JOHN DOE"
                               class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm uppercase">
                    </div>
                </div>
            </div>

            {{-- BLOK 4: KEAMANAN (PASSWORD & PIN) --}}
            <div class="border-t border-gray-200 pt-6">
                <div class="mb-5">
                    <h3 class="text-lg font-semibold text-gray-800 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                        Keamanan Akun (Password & PIN)
                    </h3>
                    <p class="mt-1 text-sm text-gray-500">Kosongkan kolom di bawah ini jika Anda <span class="font-medium text-gray-800">TIDAK INGIN</span> mengubah kredensial pengguna.</p>
                </div>

                <div class="grid grid-cols-1 gap-y-6 sm:grid-cols-2 sm:gap-x-6">

                    {{-- 4A: BAGIAN PASSWORD LOGIN --}}
                    <div class="sm:col-span-2 bg-gray-50 p-4 rounded-md border border-gray-200">
                        <h4 class="text-sm font-semibold text-gray-700 mb-3 border-b border-gray-200 pb-2">Password Login Aplikasi</h4>

                        <div class="grid grid-cols-1 gap-y-4 sm:grid-cols-2 sm:gap-x-4">
                            <div class="sm:col-span-2">
                                <label for="current_hash" class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Hash Password Saat Ini</label>
                                <div class="relative">
                                    <input type="password" id="current_hash" value="{{ $data->password_hash ?? 'Tidak Ada Hash' }}" readonly
                                           class="block w-full rounded-md border-gray-300 bg-gray-100 text-gray-500 sm:text-sm pr-10 font-mono text-xs cursor-not-allowed focus:ring-0 focus:border-gray-300">
                                    <button type="button" onclick="toggleVisibility('current_hash')" class="absolute inset-y-0 right-0 px-3 flex items-center text-gray-400 hover:text-gray-600">
                                        <svg id="current_hash_icon" class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/><path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/></svg>
                                    </button>
                                </div>
                            </div>

                            <div class="col-span-1">
                                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password Baru</label>
                                <input type="password" name="password" id="password" autocomplete="new-password" placeholder="Minimal 8 Karakter"
                                       class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                @error('password')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>

                            <div class="col-span-1">
                                <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">Ketik Ulang Password Baru</label>
                                <input type="password" name="password_confirmation" id="password_confirmation" autocomplete="new-password" placeholder="Ulangi Password"
                                       class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            </div>
                        </div>
                    </div>

                    {{-- 4B: BAGIAN PIN TRANSAKSI --}}
                    <div class="sm:col-span-2 bg-gray-50 p-4 rounded-md border border-gray-200">
                        <div class="flex justify-between items-center border-b border-gray-200 pb-2 mb-3">
                            <h4 class="text-sm font-semibold text-gray-700">PIN Transaksi (Keuangan)</h4>
                            @if(empty($data->pin))
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-200 text-gray-700">
                                  Belum Diatur
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                  Sudah Diatur
                                </span>
                            @endif
                        </div>

                        <div class="grid grid-cols-1 gap-y-4 sm:grid-cols-2 sm:gap-x-4">
                            <div class="sm:col-span-2">
                                <label for="current_pin_hash" class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Hash PIN Saat Ini</label>
                                <div class="relative">
                                    <input type="password" id="current_pin_hash" value="{{ $data->pin ?? 'Tidak Ada Hash' }}" readonly
                                           class="block w-full rounded-md border-gray-300 bg-gray-100 text-gray-500 sm:text-sm pr-10 font-mono text-xs cursor-not-allowed focus:ring-0 focus:border-gray-300">
                                    <button type="button" onclick="toggleVisibility('current_pin_hash')" class="absolute inset-y-0 right-0 px-3 flex items-center text-gray-400 hover:text-gray-600">
                                        <svg id="current_pin_hash_icon" class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/><path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/></svg>
                                    </button>
                                </div>
                            </div>

                            <div class="col-span-1 sm:col-span-2 lg:col-span-1">
                                <label for="pin" class="block text-sm font-medium text-gray-700 mb-1">Set PIN Baru / Paksa Reset PIN</label>
                                <div class="relative rounded-md shadow-sm">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <span class="text-gray-500 sm:text-sm">#</span>
                                    </div>
                                    <input type="password" name="pin" id="pin" autocomplete="off" maxlength="6" pattern="\d{6}" placeholder="Harus 6 Digit Angka"
                                           class="block w-full pl-8 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm font-mono tracking-widest text-lg">
                                </div>
                                <p class="mt-1 text-xs text-gray-500">Masukkan 6 digit angka untuk mereset paksa PIN user ini.</p>
                                @error('pin')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>
                        </div>
                    </div>

                </div>
            </div>

        </div>

        {{-- FOOTER / ACTION BUTTONS --}}
        <div class="px-5 py-4 bg-gray-50 border-t border-gray-200 rounded-b-lg flex flex-col-reverse sm:flex-row sm:justify-end sm:space-x-3">
            <a href="{{ route('admin.customers.data.pengguna.index') }}" class="mt-3 sm:mt-0 w-full sm:w-auto inline-flex justify-center items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                Batal
            </a>
            <button type="submit" class="w-full sm:w-auto inline-flex justify-center items-center px-5 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <svg class="w-4 h-4 mr-2 -ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path></svg>
                Simpan Perubahan
            </button>
        </div>
    </form>
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
        // Ikon Mata Terbuka
        icon.innerHTML = `<path fill-rule="evenodd" d="M3.28 2.22a.75.75 0 00-1.06 1.06l16.44 16.44a.75.75 0 101.06-1.06L3.28 2.22z" clip-rule="evenodd" /><path d="M12.983 2.146A.75.75 0 0012 3v1.5a.75.75 0 001.5 0V3.75c0-.414-.336-.75-.75-.75h-1.077l3.633 3.633a.75.75 0 00.902-1.206L14.077 3h1.173a2.25 2.25 0 012.25 2.25v2.73l2.844 2.844a.75.75 0 00-.53 1.282l-2.844-2.844v3.184a2.25 2.25 0 01-2.25 2.25h-4.688l-3.21 3.21a.75.75 0 001.06 1.06l3.21-3.21h4.687a3.75 3.75 0 003.75-3.75V10c0-.853.185-1.674.52-2.428L18.78 6.72a.75.75 0 10-1.06-1.06l-2.074 2.073A5.228 5.228 0 0012 6.75V5.25a.75.75 0 00-1.5 0V6.75a3.75 3.75 0 00-3.633 3.003L4.854 8.78a.75.75 0 10-1.06 1.06l3.21 3.21A5.25 5.25 0 0010 15.25h.61l-3.006-3.006a.75.75 0 10-1.06 1.06l3.006 3.006H10a6.75 6.75 0 01-5.184-2.428L2.22 13.28a.75.75 0 101.06-1.06l2.073-2.074A5.228 5.228 0 007.5 7.027V5.25a.75.75 0 00-1.5 0V7.5a.75.75 0 001.5 0V6.75a.75.75 0 00-.75-.75h-.354l-1.01-1.01a.75.75 0 00-1.06 1.06l1.01 1.01A5.25 5.25 0 005.25 9.75a.75.75 0 001.06.84L7.5 10.34l.28-.28A.75.75 0 007.22 9.22z"/>`;
    } else {
        // Ikon Mata Tertutup
        icon.innerHTML = `<path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/><path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/>`;
    }
}
</script>
@endverbatim
@endsection
