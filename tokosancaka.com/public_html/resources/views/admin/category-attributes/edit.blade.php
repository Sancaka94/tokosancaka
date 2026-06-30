@extends('layouts.admin')

@section('title', 'Edit Atribut')
@section('page-title', 'Edit Atribut')

@section('content')

@include('layouts.partials.notifications')

<div class="bg-white p-8 rounded-lg shadow-md max-w-2xl mx-auto">
    <h2 class="text-xl font-semibold text-gray-800 mb-6">Edit Atribut: <span class="text-indigo-600">{{ $attribute->name }}</span></h2>

    <form action="{{ route('admin.category-attributes.update', $attribute->id) }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        <div>
            <label for="name" class="block text-sm font-medium text-gray-700">Nama Atribut</label>
            <input type="text" name="name" id="name" value="{{ old('name', $attribute->name) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 @error('name') border-red-500 @enderror" required>
            @error('name') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="type" class="block text-sm font-medium text-gray-700">Tipe Input</label>
            <select name="type" id="type" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 @error('type') border-red-500 @enderror">
                <option value="text" {{ old('type', $attribute->type) == 'text' ? 'selected' : '' }}>Teks Singkat (Text)</option>
                <option value="number" {{ old('type', $attribute->type) == 'number' ? 'selected' : '' }}>Angka (Number)</option>
                <option value="textarea" {{ old('type', $attribute->type) == 'textarea' ? 'selected' : '' }}>Teks Panjang (Textarea)</option>
                <option value="checkbox" {{ old('type', $attribute->type) == 'checkbox' ? 'selected' : '' }}>Pilihan Ganda (Checkbox)</option>
                <option value="select" {{ old('type', $attribute->type) == 'select' ? 'selected' : '' }}>Pilihan Tunggal (Select)</option>
            </select>
            @error('type') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Bungkus input options dengan div ber-ID untuk Javascript --}}
        <div id="options-container" class="hidden">
            <label for="options" class="block text-sm font-medium text-gray-700">Pilihan (pisahkan dengan koma)</label>
            <input type="text" name="options" id="options" value="{{ old('options', $attribute->options) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 @error('options') border-red-500 @enderror" placeholder="Contoh: Merah, Kuning, Hijau">
            <p class="mt-1 text-xs text-gray-500">Hanya diisi untuk tipe Checkbox atau Select.</p>
            @error('options') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="flex items-center">
            <input type="checkbox" name="is_required" id="is_required" value="1" {{ old('is_required', $attribute->is_required) ? 'checked' : '' }} class="h-4 w-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
            <label for="is_required" class="ml-2 block text-sm text-gray-900">Wajib Diisi (Required)</label>
        </div>

        <div class="flex justify-end space-x-4 pt-4 border-t border-gray-100">
            <a href="{{ route('admin.category-attributes.index', ['category_id' => $attribute->category_id]) }}" class="bg-gray-200 text-gray-700 py-2 px-4 rounded-md hover:bg-gray-300 transition">
                Batal
            </a>
            <button type="submit" class="bg-indigo-600 text-white py-2 px-4 rounded-md hover:bg-indigo-700 transition">
                Simpan Perubahan
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const typeSelect = document.getElementById('type');
        const optionsContainer = document.getElementById('options-container');

        function toggleOptionsField() {
            if (!typeSelect || !optionsContainer) return;

            const selectedType = typeSelect.value;
            // Tampilkan field opsi jika tipe adalah checkbox atau select
            if (selectedType === 'checkbox' || selectedType === 'select') {
                optionsContainer.classList.remove('hidden');
                document.getElementById('options').setAttribute('required', 'required');
            } else {
                optionsContainer.classList.add('hidden');
                document.getElementById('options').removeAttribute('required');
            }
        }

        if (typeSelect) {
            // Cek kondisi saat halaman pertama kali dimuat (berguna saat form edit dibuka)
            toggleOptionsField();
            // Dengarkan perubahan pada select dropdown
            typeSelect.addEventListener('change', toggleOptionsField);
        }
    });
</script>
@endpush
