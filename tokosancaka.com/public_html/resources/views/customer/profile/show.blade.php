{{--
    File: resources/views/customer/profile/show.blade.php
    Deskripsi: Halaman detail profil pengguna dengan desain yang disempurnakan.
--}}
@extends('layouts.customer')

@section('content')
<div class="p-6 lg:p-8 bg-slate-50 min-h-screen">
    <div class="max-w-7xl mx-auto">

        <div class="mb-8">
            <h1 class="text-3xl font-bold text-slate-800 tracking-tight">Profil Saya</h1>
            <p class="mt-2 text-sm text-slate-500">Berikut adalah detail informasi akun dan alamat Anda yang terdaftar di sistem.</p>
        </div>

        @if(session('success'))
            <div class="mb-6 bg-green-50 border-l-4 border-green-400 text-green-800 p-4 rounded-r-lg" role="alert">
                <div class="flex items-center">
                    <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <div>
                        <p class="font-bold">Sukses!</p>
                        <p class="text-sm">{{ session('success') }}</p>
                    </div>
                </div>
            </div>
        @endif

        <div class="bg-white shadow-lg rounded-xl overflow-hidden border border-slate-200">
            <div class="p-6 md:p-8">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    
                    {{-- KOLOM KIRI: INFORMASI UTAMA PENGGUNA --}}
                    <div class="lg:col-span-1">
                        <div class="flex flex-col items-center text-center">
                            <img src="{{ $user->store_logo_path ? asset('public/storage/' . $user->store_logo_path) : 'https://placehold.co/128x128/e2e8f0/64748b?text=Logo' }}" 
                                 alt="Logo Toko" 
                                 class="h-28 w-28 rounded-full object-cover bg-slate-200 border-4 border-white shadow-md">
                            <h2 class="mt-4 text-2xl font-bold text-slate-800">{{ $user->nama_lengkap ?? '-' }}</h2>
                            <p class="text-sm text-slate-500">{{ $user->store_name ?? 'Toko Belum Diatur' }}</p>
                            <div class="mt-4 flex items-center gap-4">
                                <span class="inline-flex items-center rounded-full bg-blue-100 px-3 py-1 text-xs font-medium text-blue-700">
                                    {{ $user->role ?? 'Pelanggan' }}
                                </span>
                                @if($user->is_verified)
                                    <span class="inline-flex items-center rounded-full bg-green-100 px-3 py-1 text-xs font-medium text-green-700">
                                        Terverifikasi
                                    </span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-yellow-100 px-3 py-1 text-xs font-medium text-yellow-800">
                                        Belum Verifikasi
                                    </span>
                                @endif
                            </div>
                        </div>
                        <div class="mt-8 border-t border-slate-200 pt-6 space-y-4 text-sm">
                            <div>
                                <label class="block font-medium text-slate-500">Email</label>
                                <p class="mt-1 text-slate-900 font-semibold">{{ $user->email ?? '-' }}</p>
                            </div>
                            <div>
                                <label class="block font-medium text-slate-500">No. WhatsApp</label>
                                <p class="mt-1 text-slate-900 font-semibold">{{ $user->no_wa ?? '-' }}</p>
                            </div>
                             <div>
                                <label class="block font-medium text-slate-500">Status Akun</label>
                                <p class="mt-1 text-slate-900 font-semibold">{{ $user->status ?? 'Aktif' }}</p>
                            </div>
                            <div>
                                <label class="block font-medium text-slate-500">Bergabung Sejak</label>
                                <p class="mt-1 text-slate-900 font-semibold">{{ $user->created_at ? $user->created_at->format('d F Y') : '-' }}</p>
                            </div>
                        </div>
                    </div>

                    {{-- KOLOM KANAN: DETAIL ALAMAT & BANK --}}
                    <div class="lg:col-span-2 space-y-8">
                        {{-- Kartu Alamat --}}
                        <div class="border border-slate-200 rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-slate-800 pb-3 mb-4">Alamat Utama</h3>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 text-sm">
                                <div>
                                    <label class="block font-medium text-slate-500">Provinsi</label>
                                    <p class="mt-1 text-slate-900">{{ $user->province ?? '-' }}</p>
                                </div>
                                <div>
                                    <label class="block font-medium text-slate-500">Kabupaten/Kota</label>
                                    <p class="mt-1 text-slate-900">{{ $user->regency ?? '-' }}</p>
                                </div>
                                <div>
                                    <label class="block font-medium text-slate-500">Kecamatan</label>
                                    <p class="mt-1 text-slate-900">{{ $user->district ?? '-' }}</p>
                                </div>
                                <div>
                                    <label class="block font-medium text-slate-500">Desa/Kelurahan</label>
                                    <p class="mt-1 text-slate-900">{{ $user->village ?? '-' }}</p>
                                </div>
                                <div>
                                    <label class="block font-medium text-slate-500">Kode Pos</label>
                                    <p class="mt-1 text-slate-900">{{ $user->postal_code ?? '-' }}</p>
                                </div>
                                
                                {{-- START: Penambahan Lat/Long --}}
                                <div>
                                    <label class="block font-medium text-slate-500">Latitude</label>
                                    <p class="mt-1 text-slate-900">{{ $user->latitude ?? '-' }}</p>
                                </div>
                                <div>
                                    <label class="block font-medium text-slate-500">Longitude</label>
                                    <p class="mt-1 text-slate-900">{{ $user->longitude ?? '-' }}</p>
                                </div>
                                {{-- END: Penambahan Lat/Long --}}
                                
                                <div class="sm:col-span-2">
                                    <label class="block font-medium text-slate-500">Alamat Detail</label>
                                    <p class="mt-1 text-slate-900 whitespace-pre-wrap">{{ $user->address_detail ?? '-' }}</p>
                                </div>
                            </div>
                        </div>

                        {{-- Kartu Bank --}}
                        <div class="border border-slate-200 rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-slate-800 pb-3 mb-4">Informasi Bank</h3>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 text-sm">
                                <div>
                                    <label class="block font-medium text-slate-500">Nama Bank</label>
                                    <p class="mt-1 text-slate-900">{{ $user->bank_name ?? '-' }}</p>
                                </div>
                                <div>
                                    <label class="block font-medium text-slate-500">Nomor Rekening</label>
                                    <p class="mt-1 text-slate-900">{{ $user->bank_account_number ?? '-' }}</p>
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="block font-medium text-slate-500">Nama Pemilik Rekening</label>
                                    <p class="mt-1 text-slate-900">{{ $user->bank_account_name ?? '-' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Tombol Aksi --}}
                <div class="mt-8 pt-6 border-t border-slate-200 text-right">
                    <a href="{{ route('customer.profile.edit') }}" 
                       class="inline-flex items-center px-5 py-2.5 bg-red-600 hover:bg-red-700 text-white font-semibold text-sm rounded-lg shadow-md transition duration-150 ease-in-out">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                        Edit Profil
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection