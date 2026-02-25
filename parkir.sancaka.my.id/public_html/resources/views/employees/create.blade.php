@extends('layouts.app')

@section('title', 'Tambah Pegawai')

@section('content')
<div class="mb-6">
    <a href="{{ route('employees.index') }}" class="text-blue-600 hover:underline font-medium">&larr; Kembali ke Daftar Pegawai</a>
</div>

<div class="card shadow-md border-t-4 border-blue-600 max-w-3xl mx-auto">
    <div class="card-header bg-white border-b border-gray-100">
        <h2 class="text-xl font-bold text-gray-800">Buat Akun Pegawai Baru</h2>
    </div>
    <div class="card-body bg-gray-50">
        <form action="{{ route('employees.store') }}" method="POST">
            @csrf

            <div class="mb-4">
                <label class="block text-sm font-bold text-gray-700 mb-1">Nama Lengkap</label>
                <input type="text" name="name" class="form-control" value="{{ old('name') }}" required placeholder="Contoh: Budi Santoso">
                @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="mb-4">
                <label class="block text-sm font-bold text-gray-700 mb-1">Email (Digunakan untuk Login)</label>
                <input type="email" name="email" class="form-control" value="{{ old('email') }}" required placeholder="Contoh: budi@parkir.com" autocomplete="off">
                @error('email') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="mb-4">
                <label class="block text-sm font-bold text-gray-700 mb-1">Kata Sandi (Minimal 8 Karakter)</label>
                <input type="password" name="password" class="form-control" required placeholder="Masukkan kata sandi sementara" autocomplete="new-password">
                @error('password') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="mb-6">
                <label class="block text-sm font-bold text-gray-700 mb-1">Peran Akses (Role)</label>
                <select name="role" class="form-control" required>
                    <option value="operator" {{ old('role') == 'operator' ? 'selected' : '' }}>Operator (Hanya bisa input parkir)</option>
                    <option value="admin" {{ old('role') == 'admin' ? 'selected' : '' }}>Admin (Bisa lihat laporan dan kelola pegawai)</option>
                </select>
                @error('role') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div x-data="{ type: '{{ old('salary_type', 'nominal') }}' }" class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                <h3 class="font-bold text-blue-800 mb-3 border-b border-blue-200 pb-2">Pengaturan Gaji / Bagi Hasil (Hari Ini)</h3>

                <div class="flex flex-col md:flex-row gap-4">
                    <div class="w-full md:w-1/2">
                        <label class="block text-sm font-bold text-gray-700 mb-1">Sistem Gaji</label>
                        <select name="salary_type" x-model="type" class="form-control bg-white" required>
                            <option value="nominal">Nominal Harian (Rp)</option>
                            <option value="percentage">Persentase Pendapatan (%)</option>
                        </select>
                    </div>
                    <div class="w-full md:w-1/2">
                        <label class="block text-sm font-bold text-gray-700 mb-1" x-text="type === 'percentage' ? 'Besaran Persentase (%)' : 'Besaran Nominal (Rp)'"></label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 font-bold text-gray-500" x-text="type === 'percentage' ? '' : 'Rp'"></span>
                            <input type="number" name="salary_amount" class="form-control bg-white" :class="type === 'percentage' ? '' : 'pl-10'" x-bind:placeholder="type === 'percentage' ? 'Contoh: 20' : 'Contoh: 50000'" value="{{ old('salary_amount', 0) }}" required min="0" step="any">
                            <span class="absolute inset-y-0 right-0 flex items-center pr-3 font-bold text-gray-500" x-text="type === 'percentage' ? '%' : ''"></span>
                        </div>
                        @error('salary_amount') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
                <p class="text-xs text-blue-600 mt-2 italic" x-show="type === 'percentage'">*Gaji akan otomatis dihitung X% dari total pemasukan harian yang disetor.</p>
                <p class="text-xs text-blue-600 mt-2 italic" x-show="type === 'nominal'" style="display: none;">*Pegawai akan mendapat gaji/upah tetap per hari.</p>
            </div>

            <div class="flex justify-end pt-4 border-t border-gray-200">
                <button type="submit" class="btn-primary text-lg px-8 shadow-md font-bold">
                    Simpan & Buat Akun
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
