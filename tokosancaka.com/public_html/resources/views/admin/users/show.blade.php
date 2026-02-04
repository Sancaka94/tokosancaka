@extends('layouts.admin')

@section('title', 'Detail Pengguna: ' . $user->nama_lengkap)
@section('page-title', 'Detail Pengguna')

@section('content')
<div class="container mx-auto px-4 sm:px-8 py-8">
    <div class="bg-white p-6 rounded-lg shadow-lg max-w-4xl mx-auto">
        
        <a href="{{ route('admin.settings.index') }}#customer-table" class="text-indigo-600 hover:text-indigo-900 mb-4 inline-block">
            &larr; Kembali ke Pengaturan
        </a>

        <div class="flex items-center space-x-4 mb-6">
            <img class="h-20 w-20 rounded-full object-cover"
                 src="{{ $user->store_logo_path
                       ? asset('public/storage/' . $user->store_logo_path)  {{-- <-- SUDAH DIPERBAIKI --}}
                       : 'https://ui-avatars.com/api/?name=' . urlencode($user->nama_lengkap ?? 'User') . '&color=7F9CF5&background=EBF4FF' }}"
                 alt="Logo/Foto">
            <div>
                <h3 class="text-2xl font-semibold text-gray-800">{{ $user->nama_lengkap }}</h3>
                <p class="text-gray-600">{{ $user->store_name ?? 'Belum ada toko' }}</p>
            </div>
        </div>
        
        <div class="border-t border-gray-200 pt-4">
            <dl class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4">
                <div class="sm:col-span-1">
                    <dt class="text-sm font-medium text-gray-500">ID Pengguna</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $user->id_pengguna }}</dd>
                </div>
                <div class="sm:col-span-1">
                    <dt class="text-sm font-medium text-gray-500">Email</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $user->email }}</dd>
                </div>
                <div class="sm:col-span-1">
                    <dt class="text-sm font-medium text-gray-500">No. WA</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $user->no_wa ?? '-' }}</dd>
                </div>
                <div class="sm:col-span-1">
                    <dt class="text-sm font-medium text-gray-500">Role</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $user->role ?? 'N/A' }}</dd>
                </div>
                <div class="sm:col-span-1">
                    <dt class="text-sm font-medium text-gray-500">Status Akun</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $user->status }}</dd>
                </div>
                <div class="sm:col-span-1">
                    <dt class="text-sm font-medium text-gray-500">Saldo</dt>
                    <dd class="mt-1 text-sm text-gray-900 font-semibold">Rp {{ number_format($user->saldo, 0, ',', '.') }}</dd>
                </div>
                <div class="sm:col-span-1">
                    <dt class="text-sm font-medium text-gray-500">Terverifikasi</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $user->is_verified ? 'Ya' : 'Tidak' }}</dd>
                </div>
                <div class="sm:col-span-1">
                    <dt class="text-sm font-medium text-gray-500">Bergabung</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $user->created_at ? $user->created_at->format('d M Y, H:i') : '-' }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-sm font-medium text-gray-500">Detail Alamat</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $user->address_detail ?? '-' }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-sm font-medium text-gray-500">Info Bank</dt>
                    <dd class="mt-1 text-sm text-gray-900">
                        {{ $user->bank_name ?? '-' }} (A/N: {{ $user->bank_account_name ?? '-' }} - No: {{ $user->bank_account_number ?? '-' }})
                    </D_DL>
                </div>
            </dl>
        </div>
        
    </div>
</div>
@endsection