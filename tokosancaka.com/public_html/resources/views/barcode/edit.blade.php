@extends('layouts.admin')

@section('content')
<div class="max-w-3xl mx-auto mt-10 bg-white p-8 rounded-xl shadow-lg border border-gray-100">
    <div class="flex items-center justify-between border-b pb-4 mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Edit URL Barcode</h2>
        <a href="{{ route('barcode.create') }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium transition">
            &larr; Kembali
        </a>
    </div>

    <form action="{{ route('barcode.update', $data->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="mb-6">
            <label for="url" class="block text-sm font-semibold text-gray-700 mb-2">
                URL Saat Ini
            </label>
            <input type="url" name="url" id="url" required
                   value="{{ old('url', $data->url) }}"
                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors text-gray-800 shadow-sm">
            @error('url')
                <p class="text-red-500 text-sm mt-2 font-medium">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex space-x-3">
            <button type="submit" class="w-full bg-amber-500 hover:bg-amber-600 text-white font-bold py-3 px-4 rounded-lg transition duration-300 shadow-md">
                Update Data
            </button>
        </div>
    </form>
</div>
@endsection
