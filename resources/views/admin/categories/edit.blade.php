{{-- We'll assume you have a master layout file, e.g., at layouts.admin --}}
@extends('layouts.admin')

@section('title', 'Edit Kategori')

@section('content')
<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold mb-6">Edit Kategori</h1>

    {{-- Display validation errors if any --}}
    @if ($errors->any())
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="bg-white shadow-md rounded-lg p-6">
        <form action="{{ route('admin.categories.update', $category->id) }}" method="POST">
            @csrf
            @method('PUT') {{-- Use PUT method for updating --}}

            <div class="mb-4">
                <label for="name" class="block text-gray-700 text-sm font-bold mb-2">Nama Kategori:</label>
                <input type="text" name="name" id="name" value="{{ old('name', $category->name) }}" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
            </div>

            <div class="mb-6">
                <label for="slug" class="block text-gray-700 text-sm font-bold mb-2">Slug (Opsional):</label>
                <input type="text" name="slug" id="slug" value="{{ old('slug', $category->slug) }}" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                <p class="text-gray-600 text-xs italic mt-2">Jika dibiarkan kosong, slug akan dibuat secara otomatis dari nama.</p>
            </div>

            <div class="flex items-center justify-between">
                <button type="submit" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                    Perbarui Kategori
                </button>
                <a href="{{ route('admin.categories.index') }}" class="inline-block align-baseline font-bold text-sm text-red-600 hover:text-red-800">
                    Batal
                </a>
            </div>
        </form>
    </div>
</div>
@endsection

