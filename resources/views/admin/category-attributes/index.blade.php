@extends('layouts.admin')

@section('title', 'Pengaturan Atribut Kategori')
@section('page-title', 'Pengaturan Atribut Kategori')

@push('styles')
<style>
    /* Styling tambahan jika diperlukan */
</style>
@endpush

@section('content')
<div class="space-y-6">
    {{-- Filter Berdasarkan Kategori --}}
    <div class="bg-white p-6 rounded-lg shadow-md">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">Pilih Kategori</h2>
        <form action="{{ route('admin.category-attributes.index') }}" method="GET">
            <div class="flex items-center space-x-4">
                <select name="category_id" id="category_id" class="mt-1 block w-full md:w-1/2 border-gray-300 rounded-md shadow-sm" onchange="this.form.submit()">
                    <option value="">-- Tampilkan Semua --</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" {{ optional($selectedCategory)->id == $category->id ? 'selected' : '' }}>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
                <noscript>
                    <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm font-medium">Tampilkan</button>
                </noscript>
            </div>
        </form>
    </div>

    {{-- Hanya tampilkan jika ada kategori yang dipilih --}}
    @if($selectedCategory)
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        {{-- Form Tambah Atribut Baru --}}
        <div class="md:col-span-1">
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Tambah Atribut untuk: <span class="text-indigo-600">{{ $selectedCategory->name }}</span></h2>
                <form action="{{ route('admin.category-attributes.store') }}" method="POST" class="space-y-4">
                    @csrf
                    <input type="hidden" name="category_id" value="{{ $selectedCategory->id }}">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700">Nama Atribut</label>
                        <input type="text" name="name" id="name" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required placeholder="Contoh: Luas Tanah">
                    </div>
                    <div>
                        <label for="type" class="block text-sm font-medium text-gray-700">Tipe Input</label>
                        <select name="type" id="type" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                            <option value="text">Teks Singkat (Text)</option>
                            <option value="number">Angka (Number)</option>
                            <option value="checkbox">Pilihan Ganda (Checkbox)</option>
                            <option value="select">Pilihan Tunggal (Select)</option>
                        </select>
                    </div>
                    <div id="options-container" class="hidden">
                        <label for="options" class="block text-sm font-medium text-gray-700">Pilihan (pisahkan dengan koma)</label>
                        <input type="text" name="options" id="options" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" placeholder="SHM, HGB, Girik">
                        <p class="text-xs text-gray-500 mt-1">Hanya diisi untuk tipe Checkbox atau Select.</p>
                    </div>
                    <div class="flex items-center">
                        <input type="checkbox" name="is_required" id="is_required" value="1" class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        <label for="is_required" class="ml-2 block text-sm text-gray-900">Wajib Diisi (Required)</label>
                    </div>
                    <div>
                        <button type="submit" class="w-full bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-indigo-700">
                            Simpan Atribut
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Daftar Atribut yang Sudah Ada --}}
        <div class="md:col-span-2">
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Daftar Atribut</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipe</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Wajib</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse ($attributes as $attribute)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $attribute->name }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $attribute->type }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $attribute->is_required ? 'Ya' : 'Tidak' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <form action="{{ route('admin.category-attributes.destroy', $attribute) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus atribut ini?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-900">Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">Belum ada atribut untuk kategori ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const typeSelect = document.getElementById('type');
        const optionsContainer = document.getElementById('options-container');

        function toggleOptions() {
            if (typeSelect.value === 'checkbox' || typeSelect.value === 'select') {
                optionsContainer.classList.remove('hidden');
            } else {
                optionsContainer.classList.add('hidden');
            }
        }

        typeSelect.addEventListener('change', toggleOptions);
        toggleOptions(); // Jalankan saat halaman dimuat
    });
</script>
@endpush
