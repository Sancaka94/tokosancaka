@extends('layouts.admin')

@section('title', 'Manajemen Pendaftaran')

@section('content')
<div class="space-y-8">
    
    {{-- Header Halaman --}}
    <div>
        <h1 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white">Manajemen Pendaftaran</h1>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Setujui permintaan pendaftaran baru dari calon pelanggan.</p>
    </div>

    {{-- Notifikasi Sukses atau Error --}}
    @if(session('success') || session('error'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
             x-transition:leave="transition ease-in duration-300"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="flex items-center p-4 mb-4 text-sm rounded-lg border {{ session('success') ? 'bg-green-50 text-green-800 border-green-200 dark:bg-gray-800 dark:text-green-400 dark:border-green-600' : 'bg-red-50 text-red-800 border-red-200 dark:bg-gray-800 dark:text-red-400 dark:border-red-600' }}" role="alert">
            
            @if(session('success'))
                <i class="fa-solid fa-check-circle w-5 h-5"></i>
            @else
                <i class="fa-solid fa-exclamation-triangle w-5 h-5"></i>
            @endif

            <div class="ml-3 font-medium">
                {{ session('success') ?? session('error') }}
            </div>
            <button @click="show = false" type="button" class="ml-auto -mx-1.5 -my-1.5 rounded-lg focus:ring-2 p-1.5 inline-flex items-center justify-center h-8 w-8" aria-label="Close">
                <i class="fa-solid fa-times"></i>
            </button>
        </div>
    @endif
    
    {{-- Tabel Permintaan Pending --}}
    <div class="space-y-4">
        <h2 class="text-xl font-semibold text-gray-800 dark:text-white">Daftar Permintaan Pending</h2>
        <div class="overflow-x-auto relative shadow-md sm:rounded-lg">
            <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th scope="col" class="py-3 px-6">Tanggal</th>
                        <th scope="col" class="py-3 px-6">Nama</th>
                        <th scope="col" class="py-3 px-6">Email</th>
                        <th scope="col" class="py-3 px-6">No. WA</th>
                        <th scope="col" class="py-3 px-6">Nama Toko</th>
                        <th scope="col" class="py-3 px-6 sticky right-0 bg-gray-50 dark:bg-gray-700">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($requests as $request)
                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                            <td class="py-4 px-6">{{ \Carbon\Carbon::parse($request->created_at)->translatedFormat('d M Y, H:i') }}</td>
                            <td class="py-4 px-6">{{ $request->nama_lengkap }}</td>
                            <td class="py-4 px-6">{{ $request->email }}</td>
                            <td class="py-4 px-6">{{ $request->no_wa }}</td>
                            <td class="py-4 px-6">{{ $request->store_name }}</td>
                            <td class="py-4 px-6 sticky right-0 bg-white dark:bg-gray-800 flex space-x-2">
               
                                <form action="{{ route('admin.registrations.approve', $request->id_pengguna) }}" method="POST" onsubmit="return confirm('Anda yakin ingin menyetujui pendaftar ini?');">
                                    @csrf
                                    <button type="submit" title="Setujui" class="text-green-600 hover:text-green-900 dark:text-green-500 dark:hover:text-green-300">
                                        <i class="fa-solid fa-check-circle fa-lg"></i>
                                    </button>
                                </form>
                            
                                <form action="{{ route('admin.registrations.reject', $request->id_pengguna) }}" method="POST" onsubmit="return confirm('Tolak pendaftar ini dan suruh lengkapi data?');">
                                    @csrf
                                    <button type="submit" title="Tolak & Suruh Lengkapi Data" class="text-yellow-600 hover:text-yellow-900 dark:text-yellow-500 dark:hover:text-yellow-300">
                                        <i class="fa-solid fa-triangle-exclamation fa-lg"></i>
                                    </button>
                                </form>
                            
                                <form action="{{ route('admin.registrations.destroy', $request->id_pengguna) }}" method="POST" onsubmit="return confirm('Anda yakin ingin menghapus data pendaftar ini?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" title="Hapus" class="text-red-600 hover:text-red-900 dark:text-red-500 dark:hover:text-red-300">
                                        <i class="fa-solid fa-trash fa-lg"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-4 px-6 text-center text-gray-500">
                                Tidak ada permintaan pendaftaran baru saat ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
