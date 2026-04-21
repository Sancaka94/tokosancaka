@extends('layouts.admin')

@section('content')
<div class="max-w-3xl mx-auto mt-10 bg-white p-8 rounded-xl shadow-lg border border-gray-100">
    <h2 class="text-2xl font-bold mb-6 text-gray-800 border-b pb-4">Generate 2D Barcode</h2>

    <form action="{{ route('barcode.generate') }}" method="POST">
        @csrf
        <div class="mb-6">
            <label for="url" class="block text-sm font-semibold text-gray-700 mb-2">
                Masukkan Tautan (URL)
            </label>
            <input type="url" name="url" id="url" required
                   value="{{ old('url', $url ?? '') }}"
                   placeholder="https://sancakamall.com/..."
                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors text-gray-800 shadow-sm">
            @error('url')
                <p class="text-red-500 text-sm mt-2 font-medium">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-lg transition duration-300 shadow-md">
            Generate Barcode
        </button>
    </form>

    @if(isset($barcode))
        <div class="mt-10 flex flex-col items-center bg-gray-50 p-8 rounded-xl border border-gray-200">
            <h3 class="text-lg font-semibold text-gray-700 mb-4">Preview Barcode:</h3>

            <div class="p-4 bg-white border border-gray-200 rounded-xl shadow-sm mb-6">
                <img src="data:image/png;base64, {!! $barcode !!}" alt="Generated Barcode" class="w-64 h-64 object-contain">
            </div>

            <a href="data:image/png;base64, {!! $barcode !!}" download="barcode-{{ Str::slug($url) }}-{{ time() }}.png"
               class="inline-flex items-center justify-center px-6 py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-lg transition duration-300 shadow-md w-full sm:w-auto">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                </svg>
                Download PNG
            </a>
        </div>
    @endif
</div>
@endsection
