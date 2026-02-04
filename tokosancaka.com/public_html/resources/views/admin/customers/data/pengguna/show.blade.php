@extends('layouts.admin')

@section('title', 'Detail Pengguna: ' . $data->nama_lengkap)

@section('content')

<div class="py-6 px-4 sm:px-6 lg:px-8">
<h1 class="text-2xl font-bold text-gray-900 mb-6">Detail Akun Pengguna</h1>

{{-- ================================================= --}}
{{-- BLOK 1: INFORMASI PERSONAL & AKUN --}}
{{-- ================================================= --}}
<div class="bg-white shadow overflow-hidden sm:rounded-lg mb-6">
    <div class="px-4 py-5 sm:px-6 bg-indigo-50 border-b border-indigo-200">
        <h3 class="text-lg leading-6 font-medium text-indigo-900">
            Informasi Personal & Akun
        </h3>
        <p class="mt-1 max-w-2xl text-sm text-indigo-600">
            Data dasar pengguna dan status keanggotaan.
        </p>
    </div>
    <div class="border-t border-gray-200">
        <dl>
            <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt class="text-sm font-medium text-gray-500">Nama Lengkap</dt>
                <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ $data->nama_lengkap }}</dd>
            </div>
            <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt class="text-sm font-medium text-gray-500">Email</dt>
                <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ $data->email }}</dd>
            </div>
            <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt class="text-sm font-medium text-gray-500">No. WhatsApp</dt>
                <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ $data->no_wa }}</dd>
            </div>
            <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt class="text-sm font-medium text-gray-500">Role</dt>
                <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ $data->role }}</dd>
            </div>
            <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt class="text-sm font-medium text-gray-500">Status Akun</dt>
                <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ $data->status }}</dd>
            </div>
            <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt class="text-sm font-medium text-gray-500">Terdaftar Sejak</dt>
                <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ \Carbon\Carbon::parse($data->created_at)->translatedFormat('d F Y (H:i)') }}</dd>
            </div>
            <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt class="text-sm font-medium text-gray-500">Terakhir Dilihat (Last Seen)</dt>
                <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ $data->last_seen_at ? \Carbon\Carbon::parse($data->last_seen_at)->diffForHumans() : 'Belum pernah login' }}</dd>
            </div>
        </dl>
    </div>
</div>

{{-- ================================================= --}}
{{-- BLOK 2: INFORMASI TOKO & ALAMAT --}}
{{-- ================================================= --}}
<div class="bg-white shadow overflow-hidden sm:rounded-lg mb-6">
    <div class="px-4 py-5 sm:px-6 bg-teal-50 border-b border-teal-200">
        <h3 class="text-lg leading-6 font-medium text-teal-900">
            Informasi Toko & Lokasi
        </h3>
    </div>
    <div class="border-t border-gray-200">
        <dl>
            <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt class="text-sm font-medium text-gray-500">Nama Toko</dt>
                <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ $data->store_name ?? '—' }}</dd>
            </div>
            <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt class="text-sm font-medium text-gray-500">Logo Toko</dt>
                <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">
                    @if ($data->store_logo_path)
                    <img src="{{ $data->store_logo_path ? asset('public/storage/' . $data->store_logo_path) : 'https://ui-avatars.com/api/?name=' . urlencode($data->nama_lengkap ?? 'User') }}" 
                        alt="Logo Toko" 
                        class="h-10 w-10 object-cover rounded-full"
                        onerror="this.src='https://ui-avatars.com/api/?name={{ urlencode($data->nama_lengkap ?? 'User') }}'">
                    @else
                        —
                    @endif
                </dd>
            </div>
            <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt class="text-sm font-medium text-gray-500">Provinsi</dt>
                <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ $data->province ?? '—' }}</dd>
            </div>
            <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt class="text-sm font-medium text-gray-500">Kota/Kabupaten</dt>
                <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ $data->regency ?? '—' }}</dd>
            </div>
            <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt class="text-sm font-medium text-gray-500">Alamat Lengkap (Detail)</dt>
                <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ $data->address_detail ?? '—' }}</dd>
            </div>
            <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt class="text-sm font-medium text-gray-500">Kode Pos</dt>
                <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ $data->postal_code ?? '—' }}</dd>
            </div>
            <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt class="text-sm font-medium text-gray-500">Koordinat (Lat/Long)</dt>
                <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ $data->latitude ?? '—' }} / {{ $data->longitude ?? '—' }}</dd>
            </div>
        </dl>
    </div>
</div>

{{-- ================================================= --}}
{{-- BLOK 3: INFORMASI KEUNGAN & BANK --}}
{{-- ================================================= --}}
<div class="bg-white shadow overflow-hidden sm:rounded-lg mb-6">
    <div class="px-4 py-5 sm:px-6 bg-blue-50 border-b border-blue-200">
        <h3 class="text-lg leading-6 font-medium text-blue-900">
            Informasi Keuangan
        </h3>
    </div>
    <div class="border-t border-gray-200">
        <dl>
            <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt class="text-sm font-medium text-gray-500">Saldo Saat Ini</dt>
                <dd class="mt-1 text-sm text-gray-900 font-semibold sm:col-span-2 sm:mt-0">Rp{{ number_format($data->saldo, 0, ',', '.') }}</dd>
            </div>
            <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt class="text-sm font-medium text-gray-500">Nama Bank</dt>
                <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ $data->bank_name ?? '—' }}</dd>
            </div>
            <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt class="text-sm font-medium text-gray-500">Nomor Rekening</dt>
                <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ $data->bank_account_number ?? '—' }}</dd>
            </div>
            <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt class="text-sm font-medium text-gray-500">Nama Pemilik Rekening</dt>
                <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ $data->bank_account_name ?? '—' }}</dd>
            </div>
        </dl>
    </div>
</div>

{{-- Tombol Aksi --}}
<div class="mt-6">
    <a href="{{ route('admin.customers.data.pengguna.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
        Kembali ke Daftar
    </a>
    <a href="{{ route('admin.customers.data.pengguna.edit', $data->id_pengguna) }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 ml-3">
        Edit Data
    </a>
</div>


</div>
@endsection