@extends('layouts.admin')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 font-sans">

    <!-- Header Section -->
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Manajemen Blokir IP</h1>
        <p class="mt-1 text-sm text-gray-500">Kelola dan pantau alamat IP yang dibatasi aksesnya ke dalam sistem.</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

        <!-- Kiri: Form Tambah IP (1 Kolom) -->
        <div class="lg:col-span-1">
            <div class="bg-white border border-gray-200 rounded-xl shadow-sm">
                <div class="px-6 py-5 border-b border-gray-100">
                    <h3 class="text-base font-semibold text-gray-900">Blokir IP Baru</h3>
                </div>

                <div class="p-6">
                    <!-- Notifikasi Sukses -->
                    @if (session('success'))
                        <div class="mb-5 flex items-start p-4 text-sm text-green-800 border border-green-200 rounded-lg bg-green-50" role="alert">
                            <svg class="flex-shrink-0 inline w-5 h-5 me-3 mt-[1px]" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20"><path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5Zm3.707 8.207-4 4a1 1 0 0 1-1.414 0l-2-2a1 1 0 0 1 1.414-1.414L9 10.586l3.293-3.293a1 1 0 0 1 1.414 1.414Z"/></svg>
                            <div>{{ session('success') }}</div>
                        </div>
                    @endif

                    <!-- Notifikasi Error -->
                    @if ($errors->any())
                        <div class="mb-5 flex items-start p-4 text-sm text-red-800 border border-red-200 rounded-lg bg-red-50" role="alert">
                            <svg class="flex-shrink-0 inline w-5 h-5 me-3 mt-[1px]" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20"><path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5Zm3.707 11.793a1 1 0 1 1-1.414 1.414L10 11.414l-2.293 2.293a1 1 0 0 1-1.414-1.414L8.586 10 6.293 7.707a1 1 0 0 1 1.414-1.414L10 8.586l2.293-2.293a1 1 0 0 1 1.414 1.414L11.414 10l2.293 2.293Z"/></svg>
                            <div>
                                <ul class="list-disc list-inside space-y-1">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    @endif

                    <!-- Form -->
                    <form action="{{ route('admin.blocked-ips.store') }}" method="POST" class="space-y-5">
                        @csrf
                        <div>
                            <label for="ip_address" class="block text-sm font-medium text-gray-700 mb-1.5">Alamat IP</label>
                            <input type="text" name="ip_address" id="ip_address" value="{{ old('ip_address') }}" class="block w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-900 bg-white focus:border-gray-900 focus:ring-1 focus:ring-gray-900 focus:outline-none transition-shadow" placeholder="Contoh: 192.168.1.10" required>
                        </div>
                        <div>
                            <label for="reason" class="block text-sm font-medium text-gray-700 mb-1.5">Alasan <span class="text-gray-400 font-normal">(Opsional)</span></label>
                            <input type="text" name="reason" id="reason" value="{{ old('reason') }}" class="block w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-900 bg-white focus:border-gray-900 focus:ring-1 focus:ring-gray-900 focus:outline-none transition-shadow" placeholder="Cth: Bot Pendaftaran">
                        </div>
                        <button type="submit" class="w-full flex justify-center items-center px-4 py-2.5 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-gray-900 hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-900 transition-colors">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                            Blokir IP
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Kanan: Tabel Daftar IP (2 Kolom) -->
        <div class="lg:col-span-2">
            <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
                <div class="px-6 py-5 border-b border-gray-100 flex justify-between items-center bg-white">
                    <h3 class="text-base font-semibold text-gray-900">Daftar IP Terblokir</h3>
                    <!-- Badge Total -->
                    <span class="inline-flex items-center justify-center px-2.5 py-1 text-xs font-medium text-gray-600 bg-gray-100 border border-gray-200 rounded-md">
                        {{ $blockedIps->count() }} Total
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left whitespace-nowrap">
                        <thead class="text-xs text-gray-500 uppercase tracking-wider bg-gray-50/50 border-b border-gray-200">
                            <tr>
                                <th scope="col" class="px-6 py-4 font-medium">IP Address</th>
                                <th scope="col" class="px-6 py-4 font-medium">Alasan</th>
                                <th scope="col" class="px-6 py-4 font-medium">Waktu</th>
                                <th scope="col" class="px-6 py-4 font-medium text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse ($blockedIps as $ip)
                                <tr class="hover:bg-gray-50/80 transition-colors group">
                                    <td class="px-6 py-4 font-medium text-gray-900">
                                        {{ $ip->ip_address }}
                                    </td>
                                    <td class="px-6 py-4">
                                        @if($ip->reason)
                                            <!-- Badge Alasan -->
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-medium bg-gray-100 text-gray-600 border border-gray-200">
                                                {{ $ip->reason }}
                                            </span>
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-gray-500 text-xs">
                                        {{ $ip->created_at->format('d M Y, H:i') }}
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <form action="{{ route('admin.blocked-ips.destroy', $ip->id) }}" method="POST" class="inline-block">
                                            @csrf
                                            @method('DELETE')
                                            <!-- Icon CRUD Berwarna Merah -->
                                            <button type="submit" onclick="return confirm('Hapus IP ini dari daftar blokir?')" class="p-2 text-red-500 bg-red-50 rounded-lg border border-transparent hover:border-red-200 hover:bg-red-100 hover:text-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 transition-all opacity-80 group-hover:opacity-100" title="Cabut Blokir">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <!-- Empty State -->
                                <tr>
                                    <td colspan="4" class="px-6 py-14 text-center">
                                        <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-gray-50 border border-gray-100 mb-4">
                                            <svg class="w-6 h-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                            </svg>
                                        </div>
                                        <h3 class="text-sm font-medium text-gray-900">Belum ada IP terblokir</h3>
                                        <p class="mt-1 text-sm text-gray-500">Daftar IP yang Anda blokir akan muncul di sini.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection
