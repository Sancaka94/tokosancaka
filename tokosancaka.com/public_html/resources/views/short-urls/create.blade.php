@extends('layouts.admin')

@section('title', 'Tambah Link Baru')

@section('content')
<div class="max-w-3xl mx-auto p-6 space-y-6">

    <!-- Header Section -->
    <header class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Tambah Link Baru</h1>
            <p class="text-gray-500">Buat URL pendek baru untuk link panjang Anda.</p>
        </div>
        <a href="/admin/short-urls" class="inline-flex items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-lg transition-colors shadow-sm no-underline">
            &larr; Kembali
        </a>
    </header>

    <!-- Form Section -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 sm:p-8">
        <!-- Perhatikan action mengarah ke '/shorten' sesuai dengan route POST Anda -->
        <form action="/shorten" method="POST" class="space-y-6">
            @csrf

            <!-- Input Original URL -->
            <div>
                <label for="original_url" class="block text-sm font-medium text-gray-700 mb-2">
                    URL Asli (Destination URL) <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <input
                        type="url"
                        name="original_url"
                        id="original_url"
                        class="w-full px-4 py-2 rounded-lg border focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none transition-colors @error('original_url') border-red-500 @else border-gray-300 @enderror"
                        placeholder="https://contoh-website.com/halaman-yang-sangat-panjang"
                        value="{{ old('original_url') }}"
                        required
                    >
                </div>
                <!-- Menampilkan pesan error jika validasi (required|url) gagal -->
                @error('original_url')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-2 text-xs text-gray-400">Pastikan URL diawali dengan http:// atau https://</p>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center justify-end pt-6 border-t border-gray-100 space-x-3">
                <a href="/admin/short-urls" class="px-6 py-2 text-gray-600 hover:text-gray-800 font-medium transition-colors">
                    Batal
                </a>
                <button type="submit" class="inline-flex items-center px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors shadow-sm">
                    Simpan & Generate Link
                </button>
            </div>
        </form>
    </div>

</div>
@endsection
