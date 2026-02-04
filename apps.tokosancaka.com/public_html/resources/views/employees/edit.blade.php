@extends('layouts.app')

@section('content')
<div class="py-12 bg-slate-50">
    <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">

        <div class="bg-white overflow-hidden shadow-xl sm:rounded-2xl border border-slate-100">

            <div class="px-8 py-6 border-b border-slate-100 flex justify-between items-center bg-white">
                <div>
                    <h2 class="text-2xl font-extrabold text-slate-800 tracking-tight">Edit Pegawai</h2>
                    <p class="text-slate-500 text-sm mt-1">Perbarui data: <span class="font-bold text-blue-600">{{ $employee->name }}</span></p>
                </div>
                <a href="{{ route('employees.index') }}" class="text-sm font-semibold text-slate-500 hover:text-blue-600 transition flex items-center gap-2">
                    <i class="fas fa-arrow-left"></i> Kembali
                </a>
            </div>

            <div class="p-8">
                <form method="POST" action="{{ route('employees.update', $employee->id) }}" class="space-y-8"
                      x-data="{
                          role: '{{ $employee->role }}',
                          permissions: {{ json_encode($employee->permissions ?? []) }},
                          rolesConfig: {
                              'admin': ['dashboard', 'pos', 'products', 'reports', 'settings', 'finance'],
                              'staff': ['dashboard', 'pos', 'products'],
                              'finance': ['dashboard', 'reports', 'finance'],
                              'operator': ['pos']
                          },
                          changeRole(newRole) {
                              this.role = newRole;
                              this.permissions = this.rolesConfig[newRole] || [];
                          }
                      }"
                >
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div>
                            <label class="block font-bold text-slate-700 mb-2" for="name">
                                Nama Lengkap
                            </label>
                            <input class="w-full px-4 py-3 rounded-xl border border-slate-300 focus:border-blue-500 focus:ring-4 focus:ring-blue-100 transition duration-200 placeholder-slate-400 font-medium text-slate-700 shadow-sm"
                                   id="name" type="text" name="name"
                                   value="{{ old('name', $employee->name) }}"
                                   placeholder="Contoh: Budi Santoso" required />
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 mb-2" for="email">
                                Email Login
                            </label>
                            <input class="w-full px-4 py-3 rounded-xl border border-slate-300 focus:border-blue-500 focus:ring-4 focus:ring-blue-100 transition duration-200 placeholder-slate-400 font-medium text-slate-700 shadow-sm"
                                   id="email" type="email" name="email"
                                   value="{{ old('email', $employee->email) }}"
                                   placeholder="nama@email.com" required />
                        </div>
                    </div>

                    <div class="bg-amber-50 rounded-2xl p-6 border border-amber-100">
                        <div class="flex items-center gap-2 mb-4">
                            <i class="fas fa-lock text-amber-500 bg-amber-100 p-2 rounded-lg"></i>
                            <h3 class="font-bold text-amber-800">Ubah Password (Opsional)</h3>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block font-semibold text-amber-900/70 mb-2 text-sm" for="password">Password Baru</label>
                                <input class="w-full px-4 py-3 rounded-xl border border-amber-200 focus:border-amber-500 focus:ring-4 focus:ring-amber-100 transition duration-200 placeholder-amber-900/30 font-medium text-slate-700 bg-white"
                                       id="password" type="password" name="password"
                                       placeholder="Kosongkan jika tidak diganti" />
                            </div>
                            <div>
                                <label class="block font-semibold text-amber-900/70 mb-2 text-sm" for="password_confirmation">Ulangi Password</label>
                                <input class="w-full px-4 py-3 rounded-xl border border-amber-200 focus:border-amber-500 focus:ring-4 focus:ring-amber-100 transition duration-200 placeholder-amber-900/30 font-medium text-slate-700 bg-white"
                                       id="password_confirmation" type="password" name="password_confirmation"
                                       placeholder="Ketik ulang password baru" />
                            </div>
                        </div>
                    </div>

                    <hr class="border-slate-100">

                    <div>
                        <label class="block font-bold text-slate-700 mb-4">Pilih Jabatan (Role)</label>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            @foreach(['admin' => 'Admin Toko', 'staff' => 'Staff Gudang', 'finance' => 'Keuangan', 'operator' => 'Kasir'] as $key => $label)
                            <label class="cursor-pointer group relative">
                                <input type="radio" name="role" value="{{ $key }}"
                                       @click="changeRole('{{ $key }}')"
                                       :checked="role === '{{ $key }}'"
                                       class="peer sr-only">

                                <div class="h-full p-5 rounded-2xl border-2 border-slate-200 bg-white peer-checked:border-blue-600 peer-checked:bg-blue-50/50 hover:border-blue-300 transition-all duration-200 flex flex-col items-center justify-center text-center gap-2">
                                    <div class="font-bold text-slate-600 peer-checked:text-blue-700 group-hover:text-blue-600">{{ $label }}</div>
                                </div>

                                <div class="absolute top-3 right-3 text-blue-600 opacity-0 peer-checked:opacity-100 transition-opacity scale-0 peer-checked:scale-100 transform duration-200">
                                    <i class="fas fa-check-circle text-xl"></i>
                                </div>
                            </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="bg-slate-50 p-6 rounded-2xl border border-slate-200">
                        <h3 class="font-bold text-slate-800 mb-4 flex items-center gap-2">
                            <i class="fas fa-shield-alt text-slate-400"></i> Izin Akses Fitur
                        </h3>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            @foreach(['dashboard', 'pos', 'products', 'reports', 'finance', 'settings'] as $perm)
                            <label class="flex items-center justify-between p-4 bg-white rounded-xl border border-slate-200 shadow-sm cursor-pointer hover:border-blue-400 hover:shadow-md transition-all duration-200 group">
                                <span class="font-semibold text-slate-600 group-hover:text-blue-700 capitalize">{{ $perm }}</span>
                                <input type="checkbox" name="permissions[]" value="{{ $perm }}" x-model="permissions"
                                       class="w-5 h-5 text-blue-600 rounded focus:ring-blue-500 border-slate-300 transition cursor-pointer">
                            </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-4 pt-4">
                        <a href="{{ route('employees.index') }}" class="px-6 py-3.5 rounded-xl font-bold text-slate-500 hover:bg-slate-100 hover:text-slate-800 transition">
                            Batal
                        </a>
                        <button type="submit" class="px-8 py-3.5 rounded-xl bg-blue-600 text-white font-bold hover:bg-blue-700 active:scale-95 transition-all shadow-lg shadow-blue-200">
                            Simpan Perubahan
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>
@endsection
