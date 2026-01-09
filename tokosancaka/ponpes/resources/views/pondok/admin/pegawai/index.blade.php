@extends('pondok.admin.layouts.app')

@section('title', 'Manajemen Pegawai')
@section('page_title', 'Data Pegawai')

@section('content')
<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
    <div class="p-6 text-gray-900">
        
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-lg font-bold text-gray-700">Daftar Semua Pegawai</h3>
            <a href="{{ route('admin.pegawai.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded shadow">
                + Tambah Pegawai
            </a>
        </div>

        @if(session('success'))
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4" role="alert">
                <p>{{ session('success') }}</p>
            </div>
        @endif

        <div class="overflow-x-auto">
            <table class="min-w-full bg-white border border-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="py-3 px-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No</th>
                        <th class="py-3 px-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama & Gender</th>
                        <th class="py-3 px-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">NIP / NIK</th>
                        <th class="py-3 px-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jabatan</th>
                        <th class="py-3 px-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="py-3 px-6 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($pegawai as $index => $p)
                    <tr class="hover:bg-gray-50">
                        <td class="py-4 px-6 text-sm text-gray-500">
                            {{ $index + $pegawai->firstItem() }}
                        </td>
                        <td class="py-4 px-6">
                            <div class="text-sm font-medium text-gray-900">{{ $p->nama_lengkap }}</div>
                            <div class="text-xs text-gray-500">{{ $p->gender == 'L' ? 'Laki-laki' : 'Perempuan' }}</div>
                        </td>
                        <td class="py-4 px-6">
                            <div class="text-sm text-gray-900">{{ $p->nip ?? '-' }}</div>
                            <div class="text-xs text-gray-500">NIK: {{ $p->nik ?? '-' }}</div>
                        </td>
                        <td class="py-4 px-6 text-sm text-gray-700">
                            {{ $p->nama_jabatan ?? 'Tidak Ada' }}
                        </td>
                        <td class="py-4 px-6">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $p->status == 'Aktif' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $p->status }}
                            </span>
                        </td>
                        <td class="py-4 px-6 text-center text-sm font-medium">
                            <a href="{{ route('admin.pegawai.edit', $p->id) }}" class="text-indigo-600 hover:text-indigo-900 mr-3">Edit</a>
                            
                            <form action="{{ route('admin.pegawai.destroy', $p->id) }}" method="POST" class="inline-block" onsubmit="return confirm('Hapus data ini?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-900">Hapus</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="py-8 text-center text-gray-400">Belum ada data pegawai.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $pegawai->links() }}
        </div>
    </div>
</div>
@endsection