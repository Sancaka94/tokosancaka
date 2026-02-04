@extends('layouts.app')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

        <div class="flex justify-between items-center mb-6">
            <div>
                <h2 class="text-2xl font-bold text-slate-800">Daftar Pegawai & User</h2>
                <p class="text-sm text-slate-500">
                    @if(Auth::user()->role === 'super_admin')
                        <span class="text-purple-600 font-bold bg-purple-100 px-2 py-0.5 rounded-md border border-purple-200">
                            <i class="fas fa-crown mr-1"></i> Mode Super Admin
                        </span>
                        Menampilkan seluruh user dari semua tenant/toko.
                    @else
                        Manajemen staff internal toko Anda.
                    @endif
                </p>
            </div>

            <a href="{{ route('employees.create') }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition shadow-sm font-semibold text-sm">
                <i class="fas fa-plus mr-2"></i> Tambah Pegawai
            </a>
        </div>

        @if(session('success'))
        <div class="mb-4 bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-lg relative flex items-center gap-2">
            <i class="fas fa-check-circle"></i> {{ session('success') }}
        </div>
        @endif

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-600">
                    <thead class="bg-slate-50 text-slate-800 font-bold uppercase text-xs border-b border-slate-200">
                        <tr>
                            {{-- KOLOM KHUSUS SUPER ADMIN --}}
                            @if(Auth::user()->role === 'super_admin')
                                <th class="px-6 py-4 bg-purple-50 text-purple-800 border-r border-purple-100 w-1/4">
                                    <i class="fas fa-store mr-1"></i> Asal Toko (Tenant)
                                </th>
                            @endif

                            <th class="px-6 py-4">Nama User</th>
                            <th class="px-6 py-4">Jabatan (Role)</th>
                            <th class="px-6 py-4">Akses Fitur</th>
                            <th class="px-6 py-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($employees as $emp)
                        <tr class="hover:bg-slate-50 transition group">

                            {{-- ISI KOLOM KHUSUS SUPER ADMIN --}}
                            @if(Auth::user()->role === 'super_admin')
                                <td class="px-6 py-4 border-r border-slate-100 bg-purple-50/10">
                                    <div class="flex flex-col gap-1.5">
                                        {{-- Nama Toko --}}
                                        <div class="font-bold text-slate-800 text-sm leading-tight">
                                            {{ $emp->tenant->name ?? 'Tanpa Toko' }}
                                        </div>

                                        {{-- Subdomain Badge --}}
                                        <div class="flex items-center gap-2">
                                            <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-white border border-purple-200 text-purple-700 font-mono shadow-sm">
                                                <i class="fas fa-globe text-[10px] mr-1.5 text-purple-400"></i>
                                                {{ $emp->tenant->subdomain ?? '-' }}
                                            </span>
                                        </div>

                                        {{-- ID & Link --}}
                                        <div class="flex items-center justify-between mt-1">
                                            <span class="text-[10px] text-slate-400">ID: #{{ $emp->tenant_id }}</span>

                                            @if(isset($emp->tenant->subdomain))
                                            <a href="http://{{ $emp->tenant->subdomain }}.tokosancaka.com/orders/create" target="_blank" class="text-[10px] text-blue-500 hover:text-blue-700 hover:underline flex items-center gap-1">
                                                Visit <i class="fas fa-external-link-alt text-[9px]"></i>
                                            </a>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                            @endif

                            <td class="px-6 py-4">
                                <div class="font-bold text-slate-800">{{ $emp->name }}</div>
                                <div class="text-xs text-slate-500">{{ $emp->email }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2.5 py-1 rounded-md text-[10px] font-bold uppercase tracking-wide
                                    {{ $emp->role == 'admin' ? 'bg-indigo-100 text-indigo-700 border border-indigo-200' : '' }}
                                    {{ $emp->role == 'staff' ? 'bg-blue-100 text-blue-700 border border-blue-200' : '' }}
                                    {{ $emp->role == 'finance' ? 'bg-emerald-100 text-emerald-700 border border-emerald-200' : '' }}
                                    {{ $emp->role == 'operator' ? 'bg-orange-100 text-orange-700 border border-orange-200' : '' }}">
                                    {{ str_replace('_', ' ', $emp->role) }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-wrap gap-1">
                                    @if(empty($emp->permissions))
                                        <span class="text-slate-400 italic text-[11px]">Default Access</span>
                                    @else
                                        @foreach($emp->permissions as $perm)
                                            <span class="px-1.5 py-0.5 border border-slate-200 rounded text-[10px] font-medium text-slate-600 bg-white">
                                                {{ $perm }}
                                            </span>
                                        @endforeach
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2 opacity-100 sm:opacity-0 sm:group-hover:opacity-100 transition-opacity">
                                    <a href="{{ route('employees.edit', $emp->id) }}" class="text-blue-600 hover:bg-blue-50 px-3 py-1.5 rounded-lg text-xs font-semibold transition">
                                        Edit
                                    </a>

                                    @if(Auth::user()->role === 'super_admin' || $emp->role !== 'admin')
                                        <form action="{{ route('employees.destroy', $emp->id) }}" method="POST" onsubmit="return confirm('Hapus user ini? \n\nTindakan ini tidak bisa dibatalkan.')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:bg-red-50 px-3 py-1.5 rounded-lg text-xs font-semibold transition">
                                                Hapus
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="{{ Auth::user()->role === 'super_admin' ? 5 : 4 }}" class="px-6 py-16 text-center text-slate-400">
                                <div class="flex flex-col items-center justify-center">
                                    <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mb-4">
                                        <i class="fas fa-users-slash text-2xl text-slate-300"></i>
                                    </div>
                                    <p class="font-medium">Belum ada data user/pegawai ditemukan.</p>
                                    <p class="text-xs mt-1">Silakan tambah pegawai baru.</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
