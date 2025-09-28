@extends('layouts.admin')

@section('title', 'Edit Kategori')

@section('content')

<main class="p-6 sm:p-10 space-y-6">
    <!-- Header Halaman -->
    <div class="flex flex-col space-y-6 md:space-y-0 md:flex-row justify-between">
        <div class="mr-6">
            <h1 class="text-4xl font-semibold mb-2 text-gray-800 dark:text-gray-200">Edit Kategori</h1>
            <h2 class="text-gray-600 dark:text-gray-400 ml-0.5">Perbarui detail untuk kategori ini.</h2>
        </div>
    </div>

    <!-- Konten Utama: Form Edit Kategori -->
    <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg p-6">
        @if ($errors->any())
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('admin.categories.update', $category->id) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="space-y-4">
                <!-- Nama Kategori -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nama Kategori</label>
                    <input type="text" name="name" id="name" value="{{ old('name', $category->name) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm" required>
                </div>

                <!-- Slug -->
                <div>
                    <label for="slug" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Slug</label>
                    <input type="text" name="slug" id="slug" value="{{ old('slug', $category->slug) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm">
                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Slug adalah versi URL-friendly dari nama. Biasanya huruf kecil, berisi huruf, angka, dan tanda hubung (-).</p>
                </div>
            </div>

            <!-- Tombol Aksi -->
            <div class="mt-6 flex justify-end space-x-3">
                <a href="{{ route('admin.categories.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded-md inline-flex items-center">
                    Batal
                </a>
                <button type="submit" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded-md inline-flex items-center">
                    <i class="fas fa-save mr-2"></i>
                    Perbarui Kategori
                </button>
            </div>
        </form>
    </div>
</main>

@endsection

