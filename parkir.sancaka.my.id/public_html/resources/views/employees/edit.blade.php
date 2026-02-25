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
    </div>

    <div class="card-body bg-gray-50 px-4 md:px-6 py-4 md:py-6">
        <form action="{{ route('employees.update', $employee->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="mb-4">
                <label class="block text-sm font-bold text-gray-700 mb-1">Nama Lengkap</label>
                <input type="text" name="name" class="form-control" value="{{ old('name', $employee->name) }}" required>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-bold text-gray-700 mb-1">Email (Username Login)</label>
                <input type="email" name="email" class="form-control" value="{{ old('email', $employee->email) }}" required>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-bold text-gray-700 mb-1">Peran Akses (Role)</label>
                <select name="role" class="form-control" required>
                    <option value="operator" {{ old('role', $employee->role) == 'operator' ? 'selected' : '' }}>Operator (Hanya bisa input parkir)</option>
                    <option value="admin" {{ old('role', $employee->role) == 'admin' ? 'selected' : '' }}>Admin (Bisa lihat laporan dan kelola pegawai)</option>
                </select>
            </div>

            <div x-data="{ type: '{{ old('salary_type', $employee->salary_type ?? 'nominal') }}' }" class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
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
                            <input type="number" name="salary_amount" class="form-control bg-white" :class="type === 'percentage' ? '' : 'pl-10'" value="{{ (float) old('salary_amount', $employee->salary_amount) }}" required min="0" step="any">
                            <span class="absolute inset-y-0 right-0 flex items-center pr-3 font-bold text-gray-500" x-text="type === 'percentage' ? '%' : ''"></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mb-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                <label class="block text-sm font-bold text-gray-800 mb-1">Reset Kata Sandi <span class="text-xs font-normal text-gray-500">(Opsional)</span></label>
                <input type="password" name="password" class="form-control bg-white" placeholder="Biarkan kosong jika tidak ingin merubah sandi" autocomplete="new-password">
            </div>

            <div class="flex flex-col sm:flex-row justify-end gap-3 pt-4 border-t border-gray-200">
                <a href="{{ route('employees.index') }}" class="w-full sm:w-auto inline-flex justify-center items-center px-6 py-2.5 border border-gray-300 shadow-sm text-sm md:text-base font-bold rounded-md text-gray-700 bg-white hover:bg-gray-50 transition-colors">Batal</a>
                <button type="submit" class="btn-primary w-full sm:w-auto text-sm md:text-base px-8 py-2.5 shadow-md font-bold transition-colors">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>
@endsection
