@extends('layouts.admin')

@section('title', 'Edit Short URL')

@section('content')
<div class="max-w-3xl mx-auto p-6 space-y-6">

    <header class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Edit Link</h1>
            <p class="text-gray-500">Ubah URL asli atau kode pendek Anda.</p>
        </div>
        <a href="/admin/short-urls" class="inline-flex items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-lg transition-colors shadow-sm no-underline">
            &larr; Kembali
        </a>
    </header>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 sm:p-8">
        <!-- Menggunakan method PUT untuk Update -->
        <form action="/admin/short-urls/{{ $shortUrl->id }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            <!-- Input Original URL -->
            <div>
                <label for="original_url" class="block text-sm font-medium text-gray-700 mb-2">
                    URL Asli (Destination URL) <span class="text-red-500">*</span>
                </label>
                <input type="url" name="original_url" id="original_url" class="w-full px-4 py-2 rounded-lg border focus:border-blue-500 outline-none transition-colors @error('original_url') border-red-500 @else border-gray-300 @enderror" value="{{ old('original_url', $shortUrl->original_url) }}" required>
                @error('original_url')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Input Custom URL -->
            <div>
                <label for="custom_code" class="block text-sm font-medium text-gray-700 mb-2">
                    Short URL Code <span class="text-red-500">*</span>
                </label>
                <div class="flex items-center">
                    <span class="px-4 py-2 bg-gray-100 border border-r-0 border-gray-300 rounded-l-lg text-gray-500">
                        {{ url('/') }}/
                    </span>
                    <input type="text" name="custom_code" id="custom_code" class="w-full px-4 py-2 rounded-r-lg border focus:border-blue-500 outline-none transition-colors @error('custom_code') border-red-500 @else border-gray-300 @enderror" value="{{ old('custom_code', $shortUrl->short_code) }}" required>
                </div>
                @error('custom_code')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center justify-end pt-6 border-t border-gray-100 space-x-3">
                <a href="/admin/short-urls" class="px-6 py-2 text-gray-600 hover:text-gray-800 font-medium transition-colors">Batal</a>
                <button type="submit" class="inline-flex items-center px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors shadow-sm">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
