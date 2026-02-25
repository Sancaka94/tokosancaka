@extends('layouts.app')

@section('title', 'Edit Akun Pegawai')

@section('content')
<div class="mb-4 md:mb-6">
    <a href="{{ route('employees.index') }}" class="text-blue-600 hover:text-blue-800 hover:underline font-semibold text-sm md:text-base flex items-center gap-1 transition-colors">
        <span>&larr;</span> Kembali ke Daftar Pegawai
    </a>
</div>

<div class="card shadow-md border-t-4 border-blue-600 max-w-3xl mx-auto">
    <div class="card-header bg-white border-b border-gray-100 px-4 md:px-6 py-4">
        <h2 class="text-lg md:text-xl font-bold text-gray-800">Edit Akun Pegawai</h2>
        <p class="text-xs md:text-sm text-gray-500 font-normal mt-1">Perbarui data diri, akses role, atau reset kata sandi pegawai.</p>
    </div>

    <div class="card-body bg-gray-50 px-4 md:px-6 py-4 md:py-6">
        <form action="{{ route('employees.update', $employee->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="mb-4">
                <label class="block text-sm font-bold text-gray-700 mb-1">Nama Lengkap</label>
                <input type="text" name="name" class="form-control" value="{{ old('name', $employee->name) }}" required placeholder="Contoh: Budi Santoso">
                @error('name') <p class="text-red-500 text-xs mt-1 font-semibold">{{ $message }}</p> @enderror
            </div>

            <div class="mb-4">
                <label class="block text-sm font-bold text-gray-700 mb-1">Email (Username Login)</label>
                <input type="email" name="email" class="form-control" value="{{ old('email', $employee->email) }}" required placeholder="Contoh: budi@parkir.com">
                @error('email') <p class="text-red-500 text-xs mt-1 font-semibold">{{ $message }}</p> @enderror
            </div>

            <div class="mb-4">
                <label class="block text-sm font-bold text-gray-700 mb-1">Peran Akses (Role)</label>
                <select name="role" class="form-control" required>
                    <option value="operator" {{ old('role', $employee->role) == 'operator' ? 'selected' : '' }}>Operator (Hanya bisa input parkir)</option>
                    <option value="admin" {{ old('role', $employee->role) == 'admin' ? 'selected' : '' }}>Admin (Bisa lihat laporan dan kelola pegawai)</option>
                </select>
                @error('role') <p class="text-red-500 text-xs mt-1 font-semibold">{{ $message }}</p> @enderror
            </div>

            <div class="mb-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                <label class="block text-sm font-bold text-gray-800 mb-1">Reset Kata Sandi <span class="text-xs font-normal text-gray-500">(Opsional)</span></label>
                <input type="password" name="password" class="form-control bg-white" placeholder="Biarkan kosong jika tidak ingin merubah sandi">
                <p class="text-xs text-yellow-700 mt-2 font-medium">
                    * Hanya isi kolom ini jika pegawai lupa kata sandi dan ingin menggantinya dengan yang baru (Minimal 8 Karakter).
                </p>
                @error('password') <p class="text-red-500 text-xs mt-1 font-semibold">{{ $message }}</p> @enderror
            </div>

            <div class="flex flex-col sm:flex-row justify-end gap-3 pt-4 border-t border-gray-200">
                <a href="{{ route('employees.index') }}" class="w-full sm:w-auto inline-flex justify-center items-center px-6 py-2.5 border border-gray-300 shadow-sm text-sm md:text-base font-bold rounded-md text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                    Batal
                </a>
                <button type="submit" class="btn-primary w-full sm:w-auto text-sm md:text-base px-8 py-2.5 shadow-md font-bold transition-colors">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
