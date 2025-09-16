@extends('pondok.admin.layouts.app')

@section('title', 'Manajemen Santri')
@section('page_title', 'Manajemen Santri')

@section('content')
<div class="container mx-auto">

    {{-- Pesan Sukses atau Error --}}
    @if (session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-6" role="alert">
            <span class="block sm:inline">{{ session('success') }}</span>
        </div>
    @endif
    @if (session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6" role="alert">
            <span class="block sm:inline">{{ session('error') }}</span>
        </div>
    @endif

    <div class="bg-white p-6 rounded-lg shadow-md">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-xl font-semibold text-gray-700">Daftar Santri</h2>
            <a href="{{ route('admin.santri.create') }}" class="bg-indigo-600 text-white font-semibold px-4 py-2 rounded-lg hover:bg-indigo-700 transition duration-300">
                + Tambah Santri Baru
            </a>
        </div>

        {{-- Tabel Data Santri --}}
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="text-gray-600 text-sm font-medium border-b">
                        <th class="py-3 px-4">No</th>
                        <th class="py-3 px-4">NIS</th>
                        <th class="py-3 px-4">Nama Lengkap</th>
                        <th class="py-3 px-4 hidden lg:table-cell">Unit</th>
                        <th class="py-3 px-4 hidden sm:table-cell">Kelas</th>
                        <th class="py-3 px-4 hidden md:table-cell">Kamar</th>
                        <th class="py-3 px-4 hidden lg:table-cell">Status</th>
                        <th class="py-3 px-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($santri as $item)
                    <tr class="border-b hover:bg-gray-50">
                        <td class="py-3 px-4">{{ $loop->iteration + $santri->firstItem() - 1 }}</td>
                        <td class="py-3 px-4">{{ $item->nis }}</td>
                        <td class="py-3 px-4 font-semibold">
                            <a href="{{ route('admin.santri.show', $item->id) }}" class="text-indigo-600 hover:underline">
                                {{ $item->nama_lengkap }}
                            </a>
                        </td>
                        <td class="py-3 px-4 hidden lg:table-cell">{{ $item->nama_unit ?? 'N/A' }}</td>
                        <td class="py-3 px-4 hidden sm:table-cell">{{ $item->nama_kelas ?? 'N/A' }}</td>
                        <td class="py-3 px-4 hidden md:table-cell">{{ $item->nama_kamar ?? 'N/A' }}</td>
                        <td class="py-3 px-4 hidden lg:table-cell">
                             <form action="{{ route('admin.santri.updateStatus', $item->id) }}" method="POST" class="inline-block">
                                @csrf
                                @method('PATCH')
                                <select name="status" onchange="this.form.submit()" class="text-xs rounded border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 {{ 
                                    $item->status == 'Aktif' ? 'bg-green-100 text-green-800' : 
                                    ($item->status == 'Lulus' ? 'bg-blue-100 text-blue-800' : 
                                    ($item->status == 'Skors' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800')) 
                                }}">
                                    <option value="Aktif" {{ $item->status == 'Aktif' ? 'selected' : '' }}>Aktif</option>
                                    <option value="Lulus" {{ $item->status == 'Lulus' ? 'selected' : '' }}>Lulus</option>
                                    <option value="Dikeluarkan" {{ $item->status == 'Dikeluarkan' ? 'selected' : '' }}>Dikeluarkan</option>
                                    <option value="Skors" {{ $item->status == 'Skors' ? 'selected' : '' }}>Skors</option>
                                    <option value="Tidak Aktif" {{ $item->status == 'Tidak Aktif' ? 'selected' : '' }}>Tidak Aktif</option>
                                </select>
                            </form>
                        </td>
                        <td class="py-3 px-4">
                            <div class="flex justify-center items-center space-x-3">
                                <a href="{{ route('admin.santri.show', $item->id) }}" class="text-gray-500 hover:text-blue-700" title="Lihat Detail">
                                     <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                </a>
                                <a href="{{ route('admin.santri.edit', $item->id) }}" class="text-gray-500 hover:text-indigo-700" title="Edit">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.5L15.232 5.232z"></path></svg>
                                </a>
                                <form action="{{ route('admin.santri.destroy', $item->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus data ini?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-gray-500 hover:text-red-700" title="Hapus">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="py-4 px-4 text-center text-gray-500">
                            Data santri tidak ditemukan.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Paginasi --}}
        <div class="mt-6">
            {{ $santri->links() }}
        </div>
    </div>
</div>
@endsection

