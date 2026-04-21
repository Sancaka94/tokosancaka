@extends('layouts.admin')

@section('content')
<div class="max-w-2xl mx-auto p-6">
    <div class="bg-white rounded-xl shadow-md border border-gray-100 p-8">
        <h2 class="text-2xl font-bold text-gray-800 mb-6">Edit Riwayat Barcode</h2>

        <form action="{{ route('barcode.update', $data->id) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">URL Tautan</label>
                <input type="url" name="url" required value="{{ $data->url }}"
                       class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div class="flex space-x-2">
                <button type="submit" class="flex-1 bg-blue-600 text-white font-bold py-3 rounded-lg hover:bg-blue-700 transition">
                    Simpan Perubahan
                </button>
                <a href="{{ route('barcode.create') }}" class="flex-1 bg-gray-100 text-gray-600 text-center font-bold py-3 rounded-lg hover:bg-gray-200 transition">
                    Batal
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
