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
                <input type="email" name="email" class="form-control" value="{{ old('email') }}" required placeholder="Contoh: budi@parkir.com">
                @error('email') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="mb-4">
                <label class="block text-sm font-bold text-gray-700 mb-1">Kata Sandi (Minimal 8 Karakter)</label>
                <input type="password" name="password" class="form-control" required placeholder="Masukkan kata sandi sementara">
                @error('password') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="mb-6">
                <label class="block text-sm font-bold text-gray-700 mb-1">Peran Akses (Role)</label>
                <select name="role" class="form-control" required>
                    <option value="operator" selected>Operator (Hanya bisa input parkir)</option>
                    <option value="admin">Admin (Bisa lihat laporan dan kelola pegawai)</option>
                </select>
                @error('role') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
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
