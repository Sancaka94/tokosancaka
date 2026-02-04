@extends('layouts.app')

@section('content')
<div class="py-12 bg-slate-50">
    <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">

        <div class="bg-white overflow-hidden shadow-xl sm:rounded-2xl border border-slate-100">

            <div class="px-8 py-6 border-b border-slate-100 flex justify-between items-center bg-white">
                <div>
                    <h2 class="text-2xl font-extrabold text-slate-800 tracking-tight">Tambah Pegawai Baru</h2>
                    <p class="text-slate-500 text-sm mt-1">
                        Akan ditambahkan ke toko:
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-md font-bold bg-blue-50 text-blue-700 border border-blue-100 text-xs">
                            ID: {{ Auth::user()->tenant_id }}
                        </span>
                    </p>
                </div>
            </div>

            <div class="p-8">
                <form method="POST" action="{{ route('employees.store') }}" class="space-y-8"
                      x-data="{
                          role: 'staff',
                          permissions: [],
                          rolesConfig: {
                              'admin': ['dashboard', 'pos', 'products', 'reports', 'settings', 'finance'],
                              'staff': ['dashboard', 'pos', 'products'],
                              'finance': ['dashboard', 'reports', 'finance'],
                              'operator': ['pos']
                          },
                          updatePermissions() {
                              this.permissions = this.rolesConfig[this.role] || [];
                          }
                      }"
                      x-init="updatePermissions()"
                >
                    @csrf

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div>
                            <label class="block font-bold text-slate-700 mb-2" for="name">Nama Lengkap</label>
                            <input class="w-full px-4 py-3 rounded-xl border border-slate-300 focus:border-blue-500 focus:ring-4 focus:ring-blue-100 transition duration-200 placeholder-slate-400 font-medium text-slate-700 shadow-sm"
                                   id="name" type="text" name="name" value="{{ old('name') }}" placeholder="Contoh: Budi Santoso" required autofocus />
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-2" for="email">Email Login</label>
                            <input class="w-full px-4 py-3 rounded-xl border border-slate-300 focus:border-blue-500 focus:ring-4 focus:ring-blue-100 transition duration-200 placeholder-slate-400 font-medium text-slate-700 shadow-sm"
                                   id="email" type="email" name="email" value="{{ old('email') }}" placeholder="nama@email.com" required />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div>
                            <label class="block font-bold text-slate-700 mb-2" for="password">Password</label>
                            <input class="w-full px-4 py-3 rounded-xl border border-slate-300 focus:border-blue-500 focus:ring-4 focus:ring-blue-100 transition duration-200 placeholder-slate-400 font-medium text-slate-700 shadow-sm"
                                   id="password" type="password" name="password" required />
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-2" for="password_confirmation">Konfirmasi Password</label>
                            <input class="w-full px-4 py-3 rounded-xl border border-slate-300 focus:border-blue-500 focus:ring-4 focus:ring-blue-100 transition duration-200 placeholder-slate-400 font-medium text-slate-700 shadow-sm"
                                   id="password_confirmation" type="password" name="password_confirmation" required />
                        </div>
                    </div>

                    <hr class="border-slate-100">

                    <div>
                        <label class="block font-bold text-slate-700 mb-4">Pilih Jabatan (Role)</label>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            @foreach(['admin' => 'Admin Toko', 'staff' => 'Staff Gudang', 'finance' => 'Keuangan', 'operator' => 'Kasir'] as $key => $label)
                            <label class="cursor-pointer group relative">
                                <input type="radio" name="role" value="{{ $key }}" x-model="role" @change="updatePermissions()" class="peer sr-only">

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
                            <i class="fas fa-shield-alt text-slate-400"></i> Izin Akses Fitur (Otomatis)
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
                            Simpan Pegawai
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>
@endsection
