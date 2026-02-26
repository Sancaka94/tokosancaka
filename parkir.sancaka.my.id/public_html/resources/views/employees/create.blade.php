@extends('layouts.app')

@section('title', 'Tambah Pegawai')

@section('content')
<div class="mb-6">
    <a href="{{ route('employees.index') }}" class="text-blue-600 hover:text-blue-800 font-semibold flex items-center gap-1 w-fit transition-colors">
        <span>&larr;</span> Kembali ke Daftar Pegawai
    </a>
</div>

<div class="bg-white shadow-sm border border-gray-100 rounded-xl max-w-3xl mx-auto overflow-hidden">
    <div class="bg-white border-b border-gray-100 px-6 py-5 border-t-4 border-t-blue-600">
        <h2 class="text-xl font-bold text-gray-800">Buat Akun Pegawai Baru</h2>
        <p class="text-sm text-gray-500 mt-1">Lengkapi form di bawah ini untuk menambahkan akses dan target gaji pegawai.</p>
    </div>

    <div class="p-6 md:p-8 bg-gray-50/50">
        <form action="{{ route('employees.store') }}" method="POST">
            @csrf

            <div class="mb-5">
                <label class="block text-sm font-bold text-gray-700 mb-1">Nama Lengkap <span class="text-red-500">*</span></label>
                <input type="text" name="name" class="w-full border border-gray-300 rounded-md py-2.5 px-3 focus:ring-blue-500 focus:border-blue-500 transition-colors" value="{{ old('name') }}" required placeholder="Contoh: Budi Santoso">
                @error('name') <p class="text-red-500 text-xs font-semibold mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="mb-5">
                <label class="block text-sm font-bold text-gray-700 mb-1">Email (Untuk Login) <span class="text-red-500">*</span></label>
                <input type="email" name="email" class="w-full border border-gray-300 rounded-md py-2.5 px-3 focus:ring-blue-500 focus:border-blue-500 transition-colors" value="{{ old('email') }}" required placeholder="Contoh: budi@parkir.com" autocomplete="off">
                @error('email') <p class="text-red-500 text-xs font-semibold mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="mb-5">
                <label class="block text-sm font-bold text-gray-700 mb-1">Kata Sandi (Minimal 8 Karakter) <span class="text-red-500">*</span></label>
                <input type="password" name="password" class="w-full border border-gray-300 rounded-md py-2.5 px-3 focus:ring-blue-500 focus:border-blue-500 transition-colors" required minlength="8" placeholder="Masukkan kata sandi sementara" autocomplete="new-password">
                @error('password') <p class="text-red-500 text-xs font-semibold mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="mb-8">
                <label class="block text-sm font-bold text-gray-700 mb-1">Peran Akses (Role) <span class="text-red-500">*</span></label>
                <select name="role" class="w-full border border-gray-300 rounded-md py-2.5 px-3 focus:ring-blue-500 focus:border-blue-500 transition-colors bg-white" required>
                    <option value="" disabled {{ old('role') ? '' : 'selected' }}>-- Pilih Peran Pegawai --</option>
                    <option value="operator" {{ old('role') == 'operator' ? 'selected' : '' }}>Operator (Hanya bisa input parkir)</option>
                    <option value="admin" {{ old('role') == 'admin' ? 'selected' : '' }}>Admin (Bisa lihat laporan dan kelola pegawai)</option>
                </select>
                @error('role') <p class="text-red-500 text-xs font-semibold mt-1">{{ $message }}</p> @enderror
            </div>

            <div x-data="{ type: '{{ old('salary_type', 'nominal') }}' }" class="mb-8 p-5 bg-blue-50/50 border border-blue-100 rounded-xl">
                <h3 class="font-bold text-blue-800 mb-4 flex items-center gap-2">
                    <span>💰</span> Pengaturan Gaji / Bagi Hasil
                </h3>

                <div class="flex flex-col md:flex-row gap-5">
                    <div class="w-full md:w-1/2">
                        <label class="block text-sm font-bold text-gray-700 mb-1">Sistem Gaji <span class="text-red-500">*</span></label>
                        <select name="salary_type" x-model="type" class="w-full border border-gray-300 rounded-md py-2.5 px-3 focus:ring-blue-500 focus:border-blue-500 transition-colors bg-white shadow-sm" required>
                            <option value="nominal">Nominal Harian (Rp)</option>
                            <option value="percentage">Persentase Pendapatan (%)</option>
                        </select>
                    </div>

                    <div class="w-full md:w-1/2">
                        <label class="block text-sm font-bold text-gray-700 mb-1" x-text="type === 'percentage' ? 'Besaran Persentase (%)' : 'Besaran Nominal (Rp)'"></label>
                        <div class="relative shadow-sm">
                            <span x-show="type === 'nominal'" class="absolute inset-y-0 left-0 flex items-center pl-3 font-bold text-gray-500">Rp</span>

                            <input type="number"
                                   name="salary_amount"
                                   class="w-full border border-gray-300 rounded-md py-2.5 focus:ring-blue-500 focus:border-blue-500 transition-colors bg-white"
                                   :class="type === 'nominal' ? 'pl-10 pr-3' : 'pl-3 pr-10'"
                                   :placeholder="type === 'percentage' ? 'Contoh: 17' : 'Contoh: 50000'"
                                   value="{{ old('salary_amount', 0) }}"
                                   required min="0" step="any">

                            <span x-show="type === 'percentage'" class="absolute inset-y-0 right-0 flex items-center pr-3 font-bold text-gray-500" style="display: none;">%</span>
                        </div>
                        @error('salary_amount') <p class="text-red-500 text-xs font-semibold mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="mt-3 p-3 bg-white rounded-lg border border-blue-100 text-sm">
                    <p class="text-blue-700 font-medium flex items-center gap-2" x-show="type === 'percentage'" style="display: none;">
                        <span>💡</span> Gaji akan otomatis dihitung sekian persen (%) dari total pendapatan kotor harian.
                    </p>
                    <p class="text-blue-700 font-medium flex items-center gap-2" x-show="type === 'nominal'">
                        <span>💡</span> Pegawai akan mendapatkan upah/gaji tetap sesuai nominal di atas setiap harinya.
                    </p>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row justify-end gap-3 pt-5 border-t border-gray-200">
                <a href="{{ route('employees.index') }}" class="w-full sm:w-auto px-6 py-2.5 text-center text-gray-700 font-bold bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                    Batal
                </a>
                <button type="submit" class="w-full sm:w-auto px-8 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg shadow-md font-bold transition-colors">
                    Simpan & Buat Akun
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
@endpush
@endsection
