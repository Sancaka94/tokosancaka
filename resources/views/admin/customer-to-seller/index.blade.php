@extends('layouts.admin')
@section('content')
<div class="container mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold mb-6">Jadikan Pelanggan Sebagai Penjual</h1>
    <div class="bg-white shadow-md rounded-lg overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama Pelanggan</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status Toko</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse ($customers as $customer)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap font-medium">{{ $customer->nama_lengkap }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ $customer->email }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if ($customer->store)
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                    Sudah Punya Toko
                                </span>
                            @else
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                    Belum Punya Toko
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            @if ($customer->store)
                                <a href="{{ route('admin.stores.edit', $customer->store->id) }}" class="text-indigo-600 hover:text-indigo-900">Edit Toko</a>
                            @else
                            
                             {{-- ✅ DIPERBAIKI: Menggunakan nama route yang baru --}}
                                <a href="{{ route('admin.customer-to-seller.create', $customer->id_pengguna) }}" class="text-blue-600 hover:text-blue-900 font-semibold">Daftarkan Toko</a>
                            
                            
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-6 py-4 text-center text-gray-500">Tidak ada pelanggan yang bisa didaftarkan.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-4">
            {{ $customers->links() }}
        </div>
    </div>
</div>
@endsection