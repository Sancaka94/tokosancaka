@extends('layouts.admin')

@section('content')

<main class="p-6 sm:p-10 space-y-6">

    <div class="flex flex-col space-y-6 md:space-y-0 md:flex-row justify-between">

        <div class="mr-6">

            <h1 class="text-4xl font-semibold mb-2 text-gray-800">Manajemen Tag</h1>

            <h2 class="text-gray-600 ml-0.5">Daftar semua tag untuk postingan blog.</h2>

        </div>

        <div class="flex flex-wrap items-start justify-end -mb-3">

            <a href="{{ route('admin.tags.create') }}" class="inline-flex px-5 py-3 text-white bg-red-600 hover:bg-red-700 focus:bg-red-700 rounded-md ml-6 mb-3">

                <i class="fas fa-plus mr-2"></i>

                Tambah Tag Baru

            </a>

        </div>

    </div>



    <div class="bg-white shadow-md rounded-lg overflow-hidden">

        <div class="overflow-x-auto">

            <table class="min-w-full divide-y divide-gray-200">

                <thead class="bg-gray-50">

                    <tr>

                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Tag</th>

                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Slug</th>

                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jumlah Postingan</th>

                        <th scope="col" class="relative px-6 py-3"><span class="sr-only">Aksi</span></th>

                    </tr>

                </thead>

                <tbody class="bg-white divide-y divide-gray-200">

                    @forelse ($tags as $tag)

                    <tr>

                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $tag->name }}</td>

                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $tag->slug }}</td>

                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $tag->posts_count }}</td>

                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">

                            <a href="{{ route('admin.tags.edit', $tag->id) }}" class="text-indigo-600 hover:text-indigo-900">Edit</a>

                            <form action="{{ route('admin.tags.destroy', $tag->id) }}" method="POST" class="inline-block ml-4" onsubmit="return confirm('Apakah Anda yakin?');">

                                @csrf

                                @method('DELETE')

                                <button type="submit" class="text-red-600 hover:text-red-900">Hapus</button>

                            </form>

                        </td>

                    </tr>

                    @empty

                    <tr>

                        <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">Belum ada tag.</td>

                    </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </div>

</main>

@endsection

