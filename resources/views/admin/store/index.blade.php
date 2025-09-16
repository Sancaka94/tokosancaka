@extends('layouts.admin')
@section('content')
<div class="container mx-auto px-4 py-8 space-y-10">

    <!-- Tabel Toko Milik Admin -->
    <div>
        <h1 class="text-2xl font-bold mb-6">Kelola Toko Admin</h1>
        <div class="bg-white shadow-md rounded-lg overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama Toko</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pemilik</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @if ($adminStore)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap font-medium">{{ $adminStore->name }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">{{ $adminStore->user->nama_lengkap ?? 'Admin' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <a href="{{ route('admin.stores.edit', $adminStore->id) }}" class="text-indigo-600 hover:text-indigo-900">Edit</a>
                            </td>
                        </tr>
                    @else
                        <tr>
                            <td colspan="3" class="px-6 py-4 text-center text-gray-500">
                                <span>Akun admin belum memiliki toko.</span>
                                <a href="{{ route('admin.stores.create') }}" class="ml-2 text-blue-600 font-semibold">Buat Sekarang</a>
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>

    <!-- Tabel Semua Toko Customer -->
    <div>
        <h1 class="text-2xl font-bold mb-6">Kelola Semua Toko Customer</h1>
        <div class="bg-white shadow-md rounded-lg overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama Toko</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pemilik</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email Pemilik</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse ($customerStores as $store)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap font-medium">{{ $store->name }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">{{ optional($store->user)->nama_lengkap ?? 'N/A' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">{{ optional($store->user)->email ?? 'N/A' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <a href="{{ route('admin.stores.edit', $store->id) }}" class="text-indigo-600 hover:text-indigo-900">Edit</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-4 text-center text-gray-500">Belum ada toko customer yang terdaftar.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            <div class="p-4">
                {{ $customerStores->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
