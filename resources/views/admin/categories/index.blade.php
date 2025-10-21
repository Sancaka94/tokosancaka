@extends('layouts.admin')

@section('content')

<main class="p-6 sm:p-10 space-y-6">

    <div class="flex flex-col space-y-6 md:space-y-0 md:flex-row justify-between">

        <div class="mr-6">

            <h1 class="text-4xl font-semibold mb-2 text-gray-800 dark:text-gray-200">Manajemen Kategori</h1>

            <h2 class="text-gray-600 dark:text-gray-400 ml-0.5">Daftar semua kategori untuk postingan blog.</h2>

        </div>

        <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
        {{-- Mengubah judul agar lebih spesifik --}}
        <h2 class="text-xl font-semibold text-gray-800">Daftar Kategori Produk Marketplace</h2>
        <div class="flex items-center gap-4">
            {{-- PERBAIKAN: Menambahkan link baru ke halaman kategori etalase --}}
            <a href="{{ route('admin.categories.etalase.index') }}" class="bg-green-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-green-700 whitespace-nowrap">
                Kategori Etalase
            </a>
            <a href="{{ route('admin.categories.create') }}" class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-indigo-700 whitespace-nowrap">
                Tambah Kategori Baru
            </a>
        </div>
    </div>

    </div>



    <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">

        <div class="overflow-x-auto">

            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">

                <thead class="bg-gray-50 dark:bg-gray-700">

                    <tr>

                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Nama Kategori</th>

                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Slug</th>

                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Jumlah Postingan</th>

                        <th scope="col" class="relative px-6 py-3"><span class="sr-only">Aksi</span></th>

                    </tr>

                </thead>

                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">

                    @forelse ($categories as $category)

                    <tr>

                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">{{ $category->name }}</td>

                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $category->slug }}</td>

                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $category->posts_count }}</td>

                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">

                            <a href="{{ route('admin.categories.edit', $category->id) }}" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400">Edit</a>

                            <form action="{{ route('admin.categories.destroy', $category->id) }}" method="POST" class="inline-block ml-4" onsubmit="return confirm('Apakah Anda yakin?');">

                                @csrf

                                @method('DELETE')

                                <button type="submit" class="text-red-600 hover:text-red-900 dark:text-red-400">Hapus</button>

                            </form>

                        </td>

                    </tr>

                    @empty

                    <tr>

                        <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">Belum ada kategori.</td>

                    </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </div>

</main>

@endsection

