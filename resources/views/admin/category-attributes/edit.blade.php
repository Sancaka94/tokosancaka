@extends('layouts.admin')

@section('title', 'Edit Atribut')
@section('page-title', 'Edit Atribut')

@section('content')
<div class="bg-white p-8 rounded-lg shadow-md max-w-2xl mx-auto">
    <h2 class="text-xl font-semibold text-gray-800 mb-6">Edit Atribut: <span class="text-indigo-600">{{ $attribute->name }}</span></h2>
    
    <form action="{{ route('admin.category-attributes.update', $attribute->id) }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')
        
        <div>
            <label for="name" class="block text-sm font-medium text-gray-700">Nama Atribut</label>
            <input type="text" name="name" id="name" value="{{ old('name', $attribute->name) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
        </div>
        
        <div>
            <label for="type" class="block text-sm font-medium text-gray-700">Tipe Input</label>
            <select name="type" id="type" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                <option value="text" {{ $attribute->type == 'text' ? 'selected' : '' }}>Teks Singkat (Text)</option>
                <option value="number" {{ $attribute->type == 'number' ? 'selected' : '' }}>Angka (Number)</option>
                <option value="textarea" {{ $attribute->type == 'textarea' ? 'selected' : '' }}>Teks Panjang (Textarea)</option>
                <option value="checkbox" {{ $attribute->type == 'checkbox' ? 'selected' : '' }}>Pilihan Ganda (Checkbox)</option>
                <option value="select" {{ $attribute->type == 'select' ? 'selected' : '' }}>Pilihan Tunggal (Select)</option>
            </select>
        </div>
        
        <div>
            <label for="options" class="block text-sm font-medium text-gray-700">Pilihan (pisahkan dengan koma)</label>
            <input type="text" name="options" id="options" value="{{ old('options', $attribute->options) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" placeholder="Contoh: SHM, HGB, Girik">
            <p class="mt-1 text-xs text-gray-500">Hanya diisi untuk tipe Checkbox atau Select.</p>
        </div>
        
        <div class="flex items-center">
            <input type="checkbox" name="is_required" id="is_required" value="1" {{ old('is_required', $attribute->is_required) ? 'checked' : '' }} class="h-4 w-4 text-indigo-600 border-gray-300 rounded">
            <label for="is_required" class="ml-2 block text-sm text-gray-900">Wajib Diisi (Required)</label>
        </div>
        
        <div class="flex justify-end space-x-4 pt-4">
            <a href="{{ route('admin.category-attributes.index', ['category_id' => $attribute->category_id]) }}" class="bg-gray-200 text-gray-700 py-2 px-4 rounded-md hover:bg-gray-300">
                Batal
            </a>
            <button type="submit" class="bg-indigo-600 text-white py-2 px-4 rounded-md hover:bg-indigo-700">
                Simpan Perubahan
            </button>
        </div>
    </form>
</div>
@endsection

