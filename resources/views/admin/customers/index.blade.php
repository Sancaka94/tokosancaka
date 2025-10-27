@extends('layouts.admin')

@section('title', 'Manajemen Pelanggan & Pendaftaran')

@section('content')
<div class="space-y-8">
    
    {{-- Header Halaman --}}
    <div>
        <h1 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white">Manajemen Pelanggan & Pendaftaran</h1>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Setujui permintaan baru dan kelola pelanggan yang sudah terdaftar di satu tempat.</p>
    </div>

    {{-- Notifikasi (Dihilangkan otomatis setelah 5 detik) --}}
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
                @if(session('success') && session('whatsapp_url'))
                    <a href="{{ session('whatsapp_url') }}" target="_blank" class="ml-3 inline-flex items-center px-3 py-1.5 text-xs font-medium text-center text-white bg-green-600 rounded-lg hover:bg-green-700 focus:ring-4 focus:outline-none focus:ring-green-300 dark:bg-green-500 dark:hover:bg-green-600 dark:focus:ring-green-800">
                        <i class="fa-brands fa-whatsapp mr-2"></i> Kirim Link Setup
                    </a>
                @endif
            </div>
            <button @click="show = false" type="button" class="ml-auto -mx-1.5 -my-1.5 rounded-lg focus:ring-2 p-1.5 inline-flex items-center justify-center h-8 w-8 {{ session('success') ? 'bg-green-50 text-green-500 hover:bg-green-200 focus:ring-green-400 dark:bg-gray-800 dark:text-green-400 dark:hover:bg-gray-700' : 'bg-red-50 text-red-500 hover:bg-red-200 focus:ring-red-400 dark:bg-gray-800 dark:text-red-400 dark:hover:bg-gray-700' }}" aria-label="Close">
                <i class="fa-solid fa-times"></i>
            </button>
        </div>
    @endif
    
    {{-- Bagian Tabel Permintaan Pending --}}
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
                        {{-- PERBAIKAN: Menambahkan class sticky untuk kolom Aksi --}}
                        <th scope="col" class="py-3 px-6 sticky right-0 bg-gray-50 dark:bg-gray-700">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($requests as $request)
                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                            {{-- PERBAIKAN: Menambahkan Carbon::parse untuk memastikan ini adalah objek Carbon --}}
                            <td class="py-4 px-6">{{ $request->created_at ? \Carbon\Carbon::parse($request->created_at)->translatedFormat('d M Y, H:i') : '-' }}</td>
                            <td class="py-4 px-6">{{ $request->nama_lengkap }}</td>
                            <td class="py-4 px-6">{{ $request->email }}</td>
                            <td class="py-4 px-6">{{ $request->no_wa }}</td>
                            <td class="py-4 px-6">{{ $request->store_name ?? '-' }}</td>
                            {{-- PERBAIKAN: Mengganti teks dengan ikon dan menambahkan class sticky --}}
                            <td class="py-4 px-6 sticky right-0 bg-white dark:bg-gray-800">
                                <form action="{{ route('admin.registrations.approve', $request->id_pengguna) }}" method="POST" onsubmit="return confirm('Anda yakin ingin menyetujui pendaftar ini?');">
                                    @csrf
                                    <button type="submit" title="Setujui" class="text-green-600 hover:text-green-900 dark:text-green-500 dark:hover:text-green-300">
                                        <i class="fa-solid fa-check fa-lg"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-4 px-6 text-center text-gray-500">
                                Tidak ada permintaan pendaftaran baru.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Bagian Tabel Pengguna Terdaftar --}}
    <div class="space-y-4">
        <h2 class="text-xl font-semibold text-gray-800 dark:text-white">Daftar Pelanggan Terdaftar</h2>
        <div class="overflow-x-auto relative shadow-md sm:rounded-lg">
            <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th scope="col" class="py-3 px-6">Nama</th>
                        <th scope="col" class="py-3 px-6">Email</th>
                        <th scope="col" class="py-3 px-6">No. WA</th>
                        <th scope="col" class="py-3 px-6">Status Profil</th>
                        <th scope="col" class="py-3 px-6">Tanggal Daftar</th>
                        {{-- PERBAIKAN: Menambahkan class sticky untuk kolom Aksi --}}
                        <th scope="col" class="py-3 px-6 sticky right-0 bg-gray-50 dark:bg-gray-700">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($customers as $user)
                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                            <td class="py-4 px-6">{{ $user->nama_lengkap }}</td>
                            <td class="py-4 px-6">{{ $user->email }}</td>
                            <td class="py-4 px-6">{{ $user->no_wa }}</td>
                            <td class="py-4 px-6">
                                @if($user->status == 'Aktif')
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300">Terverifikasi</span>
                                @else
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-300">Belum Verifikasi</span>
                                @endif
                            </td>
                            {{-- PERBAIKAN: Menambahkan Carbon::parse untuk memastikan ini adalah objek Carbon --}}
                            <td class="py-4 px-6">{{ $user->created_at ? \Carbon\Carbon::parse($user->created_at)->translatedFormat('d M Y') : '-' }}</td>
                            {{-- PERBAIKAN: Mengganti teks dengan ikon dan menambahkan class sticky --}}
                            <td class="py-4 px-6 sticky right-0 bg-white dark:bg-gray-800">
                                <div class="flex items-center space-x-4">
                                    <a href="{{ route('admin.customers.edit', $user->id_pengguna) }}" title="Edit" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-500 dark:hover:text-indigo-300">
                                        <i class="fa-solid fa-pencil fa-lg"></i>
                                    </a>
                                    <form action="{{ route('admin.customers.send-setup-link', $user->id_pengguna) }}" method="POST">
                                        @csrf
                                        <button type="submit" title="Kirim Link Setup" class="text-blue-600 hover:text-blue-900 dark:text-blue-500 dark:hover:text-blue-300">
                                            <i class="fa-solid fa-paper-plane fa-lg"></i>
                                        </button>
                                    </form>
                                    <form action="{{ route('admin.customers.destroy', $user->id_pengguna) }}" method="POST" onsubmit="return confirm('PERINGATAN: Menghapus pengguna tidak dapat diurungkan. Anda yakin?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" title="Hapus" class="text-red-600 hover:text-red-900 dark:text-red-500 dark:hover:text-red-300">
                                            <i class="fa-solid fa-trash-can fa-lg"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-4 px-6 text-center text-gray-500">
                                Belum ada pelanggan yang terdaftar.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination untuk Pengguna Terdaftar --}}
        <div class="mt-4">
            {{ $customers->links() }}
        </div>
    </div>
</div>
@endsection
