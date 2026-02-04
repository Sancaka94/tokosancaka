@extends('pondok.admin.layouts.app')

@section('title', 'Dashboard Admin')
@section('page_title', 'Dashboard')

@section('content')
    @if (session('error'))
        {{-- Blok ini akan tampil jika ada pesan error dari controller --}}
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6" role="alert">
            <strong class="font-bold">Terjadi Kesalahan!</strong>
            <span class="block sm:inline">{{ session('error') }}</span>
        </div>
    @endif

    {{-- Konten dasbor normal akan tampil jika tidak ada error --}}
    @if (!session('error'))
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <div class="bg-white p-6 rounded-lg shadow-md flex items-center">
                <div class="bg-indigo-100 p-3 rounded-full mr-4">
                   <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                </div>
                <div>
                    <h3 class="text-gray-600 text-sm font-medium">Total Santri</h3>
                    <p class="text-3xl font-bold text-gray-800">{{ $stats['jumlah_santri'] ?? '0' }}</p>
                </div>
            </div>
            <div class="bg-white p-6 rounded-lg shadow-md flex items-center">
                <div class="bg-green-100 p-3 rounded-full mr-4">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                </div>
                <div>
                    <h3 class="text-gray-600 text-sm font-medium">Total Pegawai</h3>
                    <p class="text-3xl font-bold text-gray-800">{{ $stats['jumlah_pegawai'] ?? '0' }}</p>
                </div>
            </div>
            <div class="bg-white p-6 rounded-lg shadow-md flex items-center">
                 <div class="bg-yellow-100 p-3 rounded-full mr-4">
                    <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"></path></svg>
                </div>
                <div>
                    <h3 class="text-gray-600 text-sm font-medium">Jumlah Kelas</h3>
                    <p class="text-3xl font-bold text-gray-800">{{ $stats['jumlah_kelas'] ?? '0' }}</p>
                </div>
            </div>
            <div class="bg-white p-6 rounded-lg shadow-md flex items-center">
                <div class="bg-red-100 p-3 rounded-full mr-4">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                </div>
                <div>
                    <h3 class="text-gray-600 text-sm font-medium">Jumlah Kamar</h3>
                    <p class="text-3xl font-bold text-gray-800">{{ $stats['jumlah_kamar'] ?? '0' }}</p>
                </div>
            </div>
        </div>

        <div class="mt-8 bg-white p-6 rounded-lg shadow-md">
            <h3 class="text-gray-700 text-xl font-semibold mb-4">Pendaftar Terbaru</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="text-gray-600 text-sm font-medium border-b">
                            <th class="py-2 px-4">Nama Lengkap</th>
                            <th class="py-2 px-4 hidden sm:table-cell">Asal Sekolah</th>
                            <th class="py-2 px-4">Tanggal Daftar</th>
                        </tr>
                    </thead>
                    <tbody>
                        @isset($calonSantriTerbaru)
                            @forelse($calonSantriTerbaru as $calon)
                            <tr class="border-b hover:bg-gray-50">
                                <td class="py-2 px-4">{{ $calon->nama_lengkap ?? 'N/A' }}</td>
                                <td class="py-2 px-4 hidden sm:table-cell">{{ $calon->asal_sekolah ?? 'N/A' }}</td>
                                <td class="py-2 px-4">{{ isset($calon->created_at) ? \Carbon\Carbon::parse($calon->created_at)->format('d M Y') : 'N/A' }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="py-4 px-4 text-center text-gray-500">Belum ada pendaftar baru.</td>
                            </tr>
                            @endforelse
                        @endisset
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@endsection

