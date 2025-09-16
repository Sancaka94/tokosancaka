@extends('pondok.admin.layouts.app')

@section('title', 'Detail Santri')
@section('page_title', 'Detail Santri')

@section('content')
<div class="container mx-auto">
    <div class="bg-white p-8 rounded-lg shadow-md">
        <div class="flex justify-between items-center mb-6 border-b pb-4">
            <div>
                <h2 class="text-2xl font-bold text-gray-800">{{ $santri->nama_lengkap }}</h2>
                <p class="text-sm text-gray-500">NIS: {{ $santri->nis }}</p>
            </div>
            <span class="px-3 py-1 text-sm font-semibold rounded-full {{ 
                $santri->status == 'Aktif' ? 'bg-green-200 text-green-800' : 
                ($santri->status == 'Lulus' ? 'bg-blue-200 text-blue-800' : 
                ($santri->status == 'Skors' ? 'bg-yellow-200 text-yellow-800' : 'bg-red-200 text-red-800')) 
            }}">
                {{ $santri->status }}
            </span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6">
            {{-- Info Pribadi --}}
            <div class="p-4 border rounded-lg">
                <h3 class="font-semibold text-lg mb-2 text-indigo-700">Data Pribadi</h3>
                <dl class="space-y-2">
                    <div class="grid grid-cols-3 gap-2">
                        <dt class="font-medium text-gray-500">Tempat, Tgl Lahir</dt>
                        <dd class="col-span-2 text-gray-800">{{ $santri->tempat_lahir ?? '-' }}, {{ \Carbon\Carbon::parse($santri->tanggal_lahir)->isoFormat('D MMMM Y') }}</dd>
                    </div>
                    <div class="grid grid-cols-3 gap-2">
                        <dt class="font-medium text-gray-500">Jenis Kelamin</dt>
                        <dd class="col-span-2 text-gray-800">{{ $santri->jenis_kelamin }}</dd>
                    </div>
                    <div class="grid grid-cols-3 gap-2">
                        <dt class="font-medium text-gray-500">Alamat</dt>
                        <dd class="col-span-2 text-gray-800">{{ $santri->alamat ?? '-' }}</dd>
                    </div>
                </dl>
            </div>

            {{-- Info Akademik --}}
            <div class="p-4 border rounded-lg">
                <h3 class="font-semibold text-lg mb-2 text-indigo-700">Info Akademik</h3>
                <dl class="space-y-2">
                    <div class="grid grid-cols-3 gap-2">
                        <dt class="font-medium text-gray-500">Unit Pendidikan</dt>
                        <dd class="col-span-2 text-gray-800">{{ $santri->nama_unit ?? 'N/A' }}</dd>
                    </div>
                    <div class="grid grid-cols-3 gap-2">
                        <dt class="font-medium text-gray-500">Kelas</dt>
                        <dd class="col-span-2 text-gray-800">{{ $santri->nama_kelas ?? 'N/A' }}</dd>
                    </div>
                    <div class="grid grid-cols-3 gap-2">
                        <dt class="font-medium text-gray-500">Kamar</dt>
                        <dd class="col-span-2 text-gray-800">{{ $santri->nama_kamar ?? 'N/A' }}</dd>
                    </div>
                </dl>
            </div>

            {{-- Info Wali --}}
            <div class="md:col-span-2 p-4 border rounded-lg">
                <h3 class="font-semibold text-lg mb-2 text-indigo-700">Info Wali Santri</h3>
                <dl class="space-y-2">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                        <dt class="font-medium text-gray-500">Nama Ayah</dt>
                        <dd class="text-gray-800">{{ $santri->nama_ayah ?? '-' }}</dd>
                        <dt class="font-medium text-gray-500">Nama Ibu</dt>
                        <dd class="text-gray-800">{{ $santri->nama_ibu ?? '-' }}</dd>
                    </div>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                        <dt class="font-medium text-gray-500">Telepon Wali</dt>
                        <dd class="text-gray-800">{{ $santri->telepon_wali ?? '-' }}</dd>
                    </div>
                </dl>
            </div>
        </div>

        <div class="mt-8 flex justify-between items-center">
            <a href="{{ route('admin.santri.index') }}" class="bg-gray-200 text-gray-800 font-semibold px-4 py-2 rounded-lg hover:bg-gray-300 transition duration-300">
                &larr; Kembali ke Daftar
            </a>

            <div class="flex space-x-3">
                <a href="{{ route('admin.santri.edit', $santri->id) }}" class="bg-indigo-600 text-white font-semibold px-4 py-2 rounded-lg hover:bg-indigo-700 transition duration-300">
                    Edit Santri
                </a>
                <form action="{{ route('admin.santri.destroy', $santri->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus data santri ini secara permanen?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="bg-red-600 text-white font-semibold px-4 py-2 rounded-lg hover:bg-red-700 transition duration-300">
                        Hapus
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

