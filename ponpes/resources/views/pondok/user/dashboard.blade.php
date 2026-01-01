@extends('pondok.user.layouts.app')

@section('title', 'Dashboard User')
@section('page_title', 'Dashboard')

@section('content')
    @if (session('error'))
        {{-- Display error message if present in session --}}
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6" role="alert">
            <strong class="font-bold">Terjadi Kesalahan!</strong>
            <span class="block sm:inline">{{ session('error') }}</span>
        </div>
    @endif

    {{-- Normal dashboard content displays if no error --}}
    @if (!session('error'))
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            {{-- Card 1: Status Pendaftaran --}}
            <div class="bg-white p-6 rounded-lg shadow-md flex items-center">
                <div class="bg-indigo-100 p-3 rounded-full mr-4">
                    <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="text-gray-600 text-sm font-medium">Status Pendaftaran</h3>
                    <p class="text-lg font-bold text-gray-800">{{ $status_pendaftaran ?? 'Belum Daftar' }}</p>
                </div>
            </div>

            {{-- Card 2: Tagihan --}}
            <div class="bg-white p-6 rounded-lg shadow-md flex items-center">
                <div class="bg-green-100 p-3 rounded-full mr-4">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="text-gray-600 text-sm font-medium">Tagihan Belum Lunas</h3>
                    <p class="text-3xl font-bold text-gray-800">{{ $tagihan_belum_lunas ?? '0' }}</p>
                </div>
            </div>

            {{-- Card 3: Pengumuman --}}
            <div class="bg-white p-6 rounded-lg shadow-md flex items-center">
                <div class="bg-yellow-100 p-3 rounded-full mr-4">
                    <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="text-gray-600 text-sm font-medium">Pengumuman Baru</h3>
                    <p class="text-3xl font-bold text-gray-800">{{ $pengumuman_baru ?? '0' }}</p>
                </div>
            </div>

             {{-- Card 4: Dokumen --}}
            <div class="bg-white p-6 rounded-lg shadow-md flex items-center">
                <div class="bg-red-100 p-3 rounded-full mr-4">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
                <div>
                     <h3 class="text-gray-600 text-sm font-medium">Dokumen Pending</h3>
                    <p class="text-3xl font-bold text-gray-800">{{ $dokumen_pending ?? '0' }}</p>
                </div>
            </div>
        </div>

        <div class="mt-8 bg-white p-6 rounded-lg shadow-md">
            <h3 class="text-gray-700 text-xl font-semibold mb-4">Riwayat Pembayaran Terbaru</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="text-gray-600 text-sm font-medium border-b">
                            <th class="py-2 px-4">Deskripsi</th>
                            <th class="py-2 px-4">Jumlah</th>
                            <th class="py-2 px-4 hidden sm:table-cell">Status</th>
                            <th class="py-2 px-4">Tanggal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @isset($riwayatPembayaran)
                            @forelse($riwayatPembayaran as $pembayaran)
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="py-2 px-4">{{ $pembayaran->deskripsi ?? 'N/A' }}</td>
                                    <td class="py-2 px-4">Rp {{ number_format($pembayaran->jumlah ?? 0, 0, ',', '.') }}</td>
                                    <td class="py-2 px-4 hidden sm:table-cell">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $pembayaran->status == 'Lunas' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                            {{ $pembayaran->status ?? 'N/A' }}
                                        </span>
                                    </td>
                                    <td class="py-2 px-4">{{ isset($pembayaran->created_at) ? \Carbon\Carbon::parse($pembayaran->created_at)->format('d M Y') : 'N/A' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="py-4 px-4 text-center text-gray-500">Belum ada riwayat pembayaran.</td>
                                </tr>
                            @endforelse
                        @endisset
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@endsection