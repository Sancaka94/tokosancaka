@extends('layouts.admin')

@section('title', 'Pengaturan Atribut Kategori')
@section('page-title', 'Pengaturan Atribut Kategori')

@section('content')
@include('layouts.partials.notifications')

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    
    {{-- Kolom Kiri: Filter dan Form Tambah --}}
    <div class="lg:col-span-1 space-y-8">
        {{-- Filter Kategori --}}
        <div class="bg-white p-6 rounded-lg shadow-md">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Pilih Kategori</h2>
            <form action="{{ route('admin.category-attributes.index') }}" method="GET">
                <select name="category_id" onchange="this.form.submit()" class="w-full border-gray-300 rounded-md shadow-sm">
                    <option value="">-- Tampilkan Semua --</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" {{ $selectedCategory && $selectedCategory->id == $category->id ? 'selected' : '' }}>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
            </form>
        </div>

        {{-- Form Tambah Atribut --}}
        @if ($selectedCategory)
        <div class="bg-white p-6 rounded-lg shadow-md">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Tambah Atribut untuk: <span class="text-indigo-600">{{ $selectedCategory->name }}</span></h2>
            {{-- PERBAIKAN: Mengirim parameter category_id ke dalam route() --}}
            <form action="{{ route('admin.category-attributes.store', $selectedCategory->id) }}" method="POST" class="space-y-4">
                @csrf
                {{-- Input tersembunyi untuk category_id tidak lagi diperlukan karena sudah ada di URL --}}
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700">Nama Atribut</label>
                    <input type="text" name="name" id="name" value="{{ old('name') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm @error('name') border-red-500 @enderror" required>
                    @error('name') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="type" class="block text-sm font-medium text-gray-700">Tipe Input</label>
                    <select name="type" id="type" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                        <option value="text">Teks Singkat (Text)</option>
                        <option value="number">Angka (Number)</option>
                        <option value="textarea">Teks Panjang (Textarea)</option>
                        <option value="checkbox">Pilihan Ganda (Checkbox)</option>
                        <option value="select">Pilihan Tunggal (Select)</option>
                    </select>
                </div>
                <div id="options-container" class="hidden">
                    <label for="options" class="block text-sm font-medium text-gray-700">Pilihan (pisahkan dengan koma)</label>
                    <input type="text" name="options" id="options" value="{{ old('options') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm @error('options') border-red-500 @enderror" placeholder="Contoh: SHM, HGB, Girik">
                    <p class="mt-1 text-xs text-gray-500">Hanya diisi untuk tipe Checkbox atau Select.</p>
                    @error('options') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="flex items-center">
                    <input type="checkbox" name="is_required" id="is_required" value="1" class="h-4 w-4 text-indigo-600 border-gray-300 rounded">
                    <label for="is_required" class="ml-2 block text-sm text-gray-900">Wajib Diisi (Required)</label>
                </div>
                <div>
                    <button type="submit" class="w-full bg-indigo-600 text-white py-2 px-4 rounded-md hover:bg-indigo-700">
                        Simpan Atribut
                    </button>
                </div>
            </form>
        </div>
        @endif
    </div>

    {{-- Kolom Kanan: Daftar Atribut --}}
    <div class="lg:col-span-2 bg-white p-6 rounded-lg shadow-md">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">Daftar Atribut</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipe</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Wajib</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse ($attributes as $attribute)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $attribute->name }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $attribute->type }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $attribute->is_required ? 'Ya' : 'Tidak' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex items-center space-x-4">
                                <a href="{{ route('admin.category-attributes.edit', $attribute->id) }}" class="text-indigo-600 hover:text-indigo-900" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form action="{{ route('admin.category-attributes.destroy', $attribute->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus atribut ini?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-900" title="Hapus">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-6 py-12 text-center text-sm text-gray-500">
                            Pilih kategori untuk melihat atau menambah atribut.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
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
            if (selectedType === 'checkbox' || selectedType === 'select') {
                optionsContainer.classList.remove('hidden');
            } else {
                optionsContainer.classList.add('hidden');
            }
        }

        if (typeSelect) {
            // Jalankan saat halaman dimuat
            toggleOptionsField();
            // Jalankan setiap kali pilihan berubah
            typeSelect.addEventListener('change', toggleOptionsField);
        }
    });
</script>
@endpush

