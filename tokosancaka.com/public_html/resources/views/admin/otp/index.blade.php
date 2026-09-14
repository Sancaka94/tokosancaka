@extends('layouts.admin')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 font-sans">
    
    <!-- Alert Sukses -->
    @if(session('success'))
        <div class="p-4 mb-6 text-sm text-green-800 bg-green-50 rounded-md border border-green-200 flex items-center gap-3 shadow-sm">
            <i class="fa-solid fa-circle-check text-lg text-green-600"></i>
            <div><span class="font-bold">Sukses!</span> {{ session('success') }}</div>
        </div>
    @endif

    <!-- Bagian Header & Action -->
    <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div class="flex-1 min-w-0">
            <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate flex items-center gap-2">
                <i class="fa-solid fa-shield-halved text-red-600"></i>
                Log Backup OTP Pengguna
            </h2>
            <p class="mt-1 text-sm text-gray-500">
                Pusat data cadangan kode OTP (One Time Password) jika pelanggan gagal menerima pesan via Email/WhatsApp.
            </p>
        </div>
        
        <div class="flex items-center gap-2">
            <!-- Form Pencarian -->
            <form action="" method="GET" class="flex w-full md:w-auto">
                <div class="relative w-full md:w-64">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fa-solid fa-magnifying-glass text-gray-400"></i>
                    </div>
                    <input type="text" name="search" value="{{ request('search') }}" 
                           class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg leading-5 bg-white placeholder-gray-500 focus:outline-none focus:ring-1 focus:ring-red-500 focus:border-red-500 sm:text-sm transition duration-150 ease-in-out" 
                           placeholder="Cari Nama / Kontak / OTP">
                </div>
                <button type="submit" class="ml-2 inline-flex items-center px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-gray-800 hover:bg-black transition-colors">
                    Cari
                </button>
            </form>

            <!-- Tombol Bersihkan Semua Log -->
            <form action="{{ route('admin.otp.clearAll') }}" method="POST" onsubmit="return confirm('PERINGATAN: Apakah Anda yakin ingin menghapus SEMUA riwayat OTP dari database?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="inline-flex items-center px-4 py-2 border border-red-200 rounded-lg shadow-sm text-sm font-medium text-red-700 bg-red-50 hover:bg-red-100 transition-colors">
                    <i class="fa-solid fa-trash-can mr-2"></i> Bersihkan Log
                </button>
            </form>
        </div>
    </div>

    <!-- Tabel Data -->
    <div class="bg-white shadow-sm ring-1 ring-black ring-opacity-5 md:rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-300">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-xs font-semibold text-gray-900 uppercase tracking-wider w-16">No</th>
                        <th scope="col" class="px-3 py-3.5 text-left text-xs font-semibold text-gray-900 uppercase tracking-wider">Waktu Request</th>
                        <th scope="col" class="px-3 py-3.5 text-left text-xs font-semibold text-gray-900 uppercase tracking-wider">Pengguna</th>
                        <th scope="col" class="px-3 py-3.5 text-left text-xs font-semibold text-gray-900 uppercase tracking-wider">Kontak Tujuan</th>
                        <th scope="col" class="px-3 py-3.5 text-left text-xs font-semibold text-gray-900 uppercase tracking-wider">Tipe OTP</th>
                        <th scope="col" class="px-3 py-3.5 text-center text-xs font-semibold text-gray-900 uppercase tracking-wider">Kode OTP</th>
                        <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6"><span class="sr-only">Aksi</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    @forelse ($dataOtp as $index => $item)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-500">
                                {{ $dataOtp->firstItem() + $index }}
                            </td>
                            
                            <!-- Kolom Waktu -->
                            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-600">
                                <div class="font-medium text-gray-900">{{ \Carbon\Carbon::parse($item->created_at)->format('d M Y') }}</div>
                                <div class="text-xs">{{ \Carbon\Carbon::parse($item->created_at)->format('H:i:s') }} WIB</div>
                            </td>
                            
                            <!-- Kolom Pengguna -->
                            <td class="whitespace-nowrap px-3 py-4 text-sm">
                                <div class="font-bold text-gray-900">{{ $item->nama_lengkap ?? 'Tidak Diketahui' }}</div>
                                <div class="text-xs text-gray-500">ID: {{ $item->id_pengguna ?? '-' }}</div>
                            </td>
                            
                            <!-- Kolom Kontak -->
                            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-600">
                                <div class="flex items-center gap-1.5">
                                    @if(str_contains($item->kontak, '@'))
                                        <i class="fa-solid fa-envelope text-gray-400"></i>
                                    @else
                                        <i class="fa-brands fa-whatsapp text-green-500"></i>
                                    @endif
                                    <span>{{ $item->kontak }}</span>
                                </div>
                            </td>
                            
                            <!-- Kolom Tipe OTP -->
                            <td class="whitespace-nowrap px-3 py-4 text-sm">
                                @if(str_contains(strtolower($item->tipe_otp), 'reset') || str_contains(strtolower($item->tipe_otp), 'lupa'))
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-red-100 text-red-800 uppercase tracking-wider">
                                        {{ $item->tipe_otp }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-800 uppercase tracking-wider">
                                        {{ $item->tipe_otp }}
                                    </span>
                                @endif
                            </td>
                            
                            <!-- Kolom Kode OTP & Tombol Copy -->
                            <td class="whitespace-nowrap px-3 py-4 text-sm text-center">
                                <div x-data="{ copied: false }" class="inline-flex items-center justify-center gap-2 bg-gray-100 border border-gray-200 rounded-lg px-3 py-1.5">
                                    <span class="font-mono font-bold text-lg text-gray-900 tracking-widest">
                                        {{ $item->otp_code }}
                                    </span>
                                    
                                    <button @click="
                                            navigator.clipboard.writeText('{{ $item->otp_code }}'); 
                                            copied = true; 
                                            setTimeout(() => copied = false, 2000)
                                        " 
                                        type="button" 
                                        class="text-gray-400 hover:text-gray-700 transition-colors focus:outline-none"
                                        :title="copied ? 'Tersalin!' : 'Salin Kode'">
                                        <i class="fa-solid" :class="copied ? 'fa-check text-green-600' : 'fa-copy'"></i>
                                    </button>
                                </div>
                            </td>

                            <!-- Tombol Hapus Baris -->
                            <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                                <form action="{{ route('admin.otp.destroy', $item->id) }}" method="POST" onsubmit="return confirm('Hapus log OTP ini?');" class="inline-block">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-500 hover:text-red-800 bg-red-50 hover:bg-red-100 p-2 rounded transition-colors" title="Hapus Log">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="whitespace-nowrap py-12 text-center text-sm text-gray-500">
                                <div class="flex flex-col items-center justify-center">
                                    <i class="fa-solid fa-inbox text-4xl text-gray-300 mb-3"></i>
                                    <span class="font-medium text-gray-900">Belum Ada Data OTP</span>
                                    <p class="mt-1">Sistem belum mencatat pengiriman OTP apapun saat ini.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        @if($dataOtp->hasPages())
            <div class="bg-gray-50 px-4 py-3 border-t border-gray-200 sm:px-6">
                {{ $dataOtp->links() }}
            </div>
        @endif
    </div>

</div>
@endsection