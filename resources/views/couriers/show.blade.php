@extends('layouts.admin')

@section('content')
<div class="container mx-auto p-4 sm:p-6 lg:p-8">
    <!-- Header -->
    <header class="mb-8">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-800">Detail Kurir</h1>
                <p class="text-gray-600 mt-1">Informasi lengkap untuk {{ $courier->full_name }}.</p>
            </div>
            <a href="{{ route('admin.couriers.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded-lg transition duration-300">
                <i class="fa fa-arrow-left mr-2"></i>Kembali
            </a>
        </div>
    </header>

    <!-- Main Content -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <!-- Kolom Kiri: Info Utama -->
        <div class="md:col-span-2 bg-white rounded-lg shadow-md p-6">
            <h3 class="text-xl font-semibold border-b pb-3 mb-4">Informasi Pribadi</h3>
            <div class="space-y-4">
                <div class="grid grid-cols-3">
                    <span class="font-semibold text-gray-600">ID Kurir</span>
                    <span class="col-span-2 font-mono">: {{ $courier->courier_id }}</span>
                </div>
                <div class="grid grid-cols-3">
                    <span class="font-semibold text-gray-600">Nama Lengkap</span>
                    <span class="col-span-2">: {{ $courier->full_name }}</span>
                </div>
                <div class="grid grid-cols-3">
                    <span class="font-semibold text-gray-600">Nomor HP</span>
                    <span class="col-span-2">: {{ $courier->phone_number }}</span>
                </div>
                <div class="grid grid-cols-3">
                    <span class="font-semibold text-gray-600">Alamat</span>
                    <span class="col-span-2">: {{ $courier->address }}</span>
                </div>
                <div class="grid grid-cols-3">
                    <span class="font-semibold text-gray-600">Tanggal Bergabung</span>
                    <span class="col-span-2">: {{ $courier->created_at->format('d F Y') }}</span>
                </div>
            </div>
        </div>

        <!-- Kolom Kanan: Status & Riwayat -->
        <div class="space-y-8">
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-xl font-semibold border-b pb-3 mb-4">Status & Aktivitas</h3>
                <div class="space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="font-semibold text-gray-600">Status Saat Ini</span>
                        @if($courier->status == 'Aktif')
                            <span class="px-3 py-1 text-sm font-semibold rounded-full bg-green-100 text-green-800">Aktif</span>
                        @elseif($courier->status == 'Dalam Perjalanan')
                            <span class="px-3 py-1 text-sm font-semibold rounded-full bg-yellow-100 text-yellow-800">Dalam Perjalanan</span>
                        @else
                            <span class="px-3 py-1 text-sm font-semibold rounded-full bg-red-100 text-red-800">Tidak Aktif</span>
                        @endif
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="font-semibold text-gray-600">Surat Jalan Aktif</span>
                        <span class="font-mono">{{ $courier->shipping_code ?? 'Tidak ada' }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="font-semibold text-gray-600">Scan Terakhir</span>
                        <span>{{ $courier->last_scan_time ? \Carbon\Carbon::parse($courier->last_scan_time)->diffForHumans() : '-' }}</span>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-xl font-semibold border-b pb-3 mb-4">Riwayat Scan</h3>
                <div class="space-y-3">
                    <div class="flex justify-between items-center text-lg">
                        <span class="font-semibold text-gray-600">Hari Ini</span>
                        <span class="font-bold">{{ $scanHistory['today'] ?? 0 }}</span>
                    </div>
                    <div class="flex justify-between items-center text-lg">
                        <span class="font-semibold text-gray-600">7 Hari Terakhir</span>
                        <span class="font-bold">{{ $scanHistory['last_7_days'] ?? 0 }}</span>
                    </div>
                    <div class="flex justify-between items-center text-lg">
                        <span class="font-semibold text-gray-600">Bulan Ini</span>
                        <span class="font-bold">{{ $scanHistory['this_month'] ?? 0 }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabel Riwayat Scan Lengkap -->
    <div class="mt-8 bg-white rounded-lg shadow-md overflow-hidden">
        <div class="p-4 border-b">
            <h3 class="text-xl font-semibold">Riwayat Scan Lengkap</h3>
        </div>
        <!-- Filter dan Pencarian -->
        <div class="p-4 flex flex-col sm:flex-row justify-between items-center gap-4">
            <div class="relative w-full sm:w-1/2">
                <i class="fa fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                <input type="text" placeholder="Cari kode surat jalan atau nama pelanggan..." class="pl-10 pr-4 py-2 w-full border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="flex items-center gap-4">
                <input type="date" class="border border-gray-300 rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
        </div>
        <!-- Tabel Data -->
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-700">
                <thead class="text-xs text-gray-800 uppercase bg-gray-50">
                    <tr>
                        <th class="px-6 py-3">No</th>
                        <th class="px-6 py-3">Waktu Scan</th>
                        <th class="px-6 py-3">Kode Surat Jalan</th>
                        <th class="px-6 py-3">Nama Pelanggan</th>
                        <th class="px-6 py-3 text-center">Status</th>
                        <th class="px-6 py-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    {{-- Controller perlu mengirimkan variabel $scans yang berisi data dari tabel spx_scans --}}
                    @forelse ($scans ?? [] as $scan)
                    <tr class="bg-white border-b hover:bg-gray-50">
                        <td class="px-6 py-4">{{ $loop->iteration }}</td>
                        <td class="px-6 py-4">
                            {{ \Carbon\Carbon::parse($scan->created_at)->translatedFormat('l, d F Y H:i') }}
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-2">
                                <span class="font-mono">{{ $scan->surat_jalan_id ?? 'N/A' }}</span>
                                <button title="Cek Rincian Resi" class="text-blue-500 hover:text-blue-700"><i class="fa fa-list-alt"></i></button>
                            </div>
                        </td>
                        <td class="px-6 py-4">{{ $scan->customer->name ?? 'Nama Pelanggan' }}</td>
                        <td class="px-6 py-4 text-center">
                            <span class="px-3 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">{{ $scan->status }}</span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <form action="#" method="POST">
                                @csrf
                                <button type="submit" class="bg-green-500 hover:bg-green-600 text-white font-bold py-1 px-3 rounded-lg text-xs transition duration-300">
                                    Konfirmasi Gudang
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center px-6 py-4 text-gray-500">
                            Tidak ada riwayat scan yang ditemukan.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4">
            {{-- {{ $scans->links() }} --}}
        </div>
    </div>
</div>
@endsection
