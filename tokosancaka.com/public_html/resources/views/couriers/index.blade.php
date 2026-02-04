@extends('layouts.admin')

@section('content')
<div class="container mx-auto p-4 sm:p-6 lg:p-8">

    <!-- Header -->
    <header class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800">Database Kurir</h1>
        <p class="text-gray-600 mt-1">Data real-time status dan informasi kurir Sancaka Express.</p>
    </header>

    <!-- Main Content -->
    <main class="bg-white rounded-lg shadow-md overflow-hidden">
        <!-- Filter and Search Section -->
        <div class="p-4 border-b border-gray-200 flex flex-col sm:flex-row justify-between items-center gap-4">
            <form action="{{ route('admin.couriers.index') }}" method="GET" class="relative w-full sm:w-auto">
                <i class="fa fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                <input type="text" name="search" placeholder="Cari nama atau ID kurir..." value="{{ request('search') }}" class="pl-10 pr-4 py-2 w-full border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </form>
            <div class="flex items-center gap-4">
                <a href="{{ route('admin.couriers.create') }}" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded-lg transition duration-300">
                    <i class="fa fa-plus mr-2"></i>Tambah Kurir
                </a>
            </div>
        </div>

        <!-- Courier Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-700">
                <thead class="text-xs text-gray-800 uppercase bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3">No</th>
                        <th scope="col" class="px-6 py-3">ID Kurir</th>
                        <th scope="col" class="px-6 py-3">Nama Lengkap</th>
                        <th scope="col" class="px-6 py-3">Nomor HP</th>
                        <th scope="col" class="px-6 py-3">Waktu Scan Terakhir</th>
                        <th scope="col" class="px-6 py-3">Surat Jalan Aktif</th>
                        <th scope="col" class="px-6 py-3 text-center">Status</th>
                        <th scope="col" class="px-6 py-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($couriers as $courier)
                    <tr class="bg-white border-b hover:bg-gray-50">
                        <td class="px-6 py-4">{{ $loop->iteration }}</td>
                        <td class="px-6 py-4 font-mono font-medium text-gray-900">{{ $courier->courier_id }}</td>
                        <td class="px-6 py-4">{{ $courier->full_name }}</td>
                        <td class="px-6 py-4">{{ $courier->phone_number }}</td>
                        <td class="px-6 py-4">{{ $courier->last_scan_time ? \Carbon\Carbon::parse($courier->last_scan_time)->format('d M Y, H:i') : '-' }}</td>
                        <td class="px-6 py-4 font-mono">{{ $courier->shipping_code ?? '-' }}</td>
                        <td class="px-6 py-4 text-center">
                            @if($courier->status == 'Aktif')
                                <span class="px-3 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Aktif</span>
                            @elseif($courier->status == 'Dalam Perjalanan')
                                <span class="px-3 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Dalam Perjalanan</span>
                            @else
                                <span class="px-3 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Tidak Aktif</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-center">
                            <div class="flex justify-center items-center gap-3">
                                <a href="{{ route('admin.couriers.show', $courier->id) }}" title="Lihat Detail" class="text-gray-500 hover:text-gray-700"><i class="fa fa-eye"></i></a>
                                <a href="{{ route('admin.couriers.edit', $courier->id) }}" title="Edit" class="text-blue-500 hover:text-blue-700"><i class="fa fa-pencil"></i></a>
                                <a href="{{ route('admin.couriers.track', $courier->id) }}" title="Cek Posisi" class="text-green-500 hover:text-green-700"><i class="fa fa-map-marker-alt"></i></a>
                                <form action="{{ route('admin.couriers.destroy', $courier->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus kurir ini?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" title="Hapus" class="text-red-500 hover:text-red-700"><i class="fa fa-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center px-6 py-4 text-gray-500">
                            Tidak ada data kurir yang ditemukan.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="p-4">
            {{ $couriers->links() }}
        </div>
    </main>
</div>
@endsection
