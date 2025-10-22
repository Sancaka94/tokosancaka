@extends('layouts.admin')

@section('title', 'Pengaturan Atribut Kategori')
@section('page-title', 'Pengaturan Atribut Kategori')

@push('styles')
    {{-- Tambahkan style jika perlu --}}
@endpush

@section('content')
    <div class="space-y-8">
        <!-- Pemilihan Kategori -->
        <div class="bg-white p-6 rounded-lg shadow-md">
            <label for="category_id" class="block text-sm font-medium text-gray-700 mb-2">Pilih Kategori</label>
            <select name="category_id" id="category_id" class="block w-full border-gray-300 rounded-md shadow-sm"
                    onchange="window.location.href = this.value ? '{{ route('admin.category-attributes.index') }}?category_id=' + this.value : '{{ route('admin.category-attributes.index') }}'">
                <option value="">-- Tampilkan Semua --</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" {{ $selectedCategory && $selectedCategory->id == $category->id ? 'selected' : '' }}>
                        {{ $category->name }}
                    </option>
                @endforeach
            </select>
        </div>

        @if($selectedCategory)
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <!-- Form Tambah Atribut -->
            <div class="md:col-span-1 bg-white p-6 rounded-lg shadow-md">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Tambah Atribut untuk: <span class="text-indigo-600">{{ $selectedCategory->name }}</span></h3>
                <form action="{{ route('admin.category-attributes.store', $selectedCategory->id) }}" method="POST" class="space-y-6">
                    @csrf
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700">Nama Atribut</label>
                        <input type="text" name="name" id="name" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
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
                     <div>
                        <label for="options" class="block text-sm font-medium text-gray-700">Pilihan (pisahkan dengan koma)</label>
                        <input type="text" name="options" id="options" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" placeholder="Contoh: SHM, HGB, Girik">
                        <p class="mt-1 text-xs text-gray-500">Hanya diisi untuk tipe Checkbox atau Select.</p>
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

            <!-- Daftar Atribut -->
            <div class="md:col-span-2 bg-white p-6 rounded-lg shadow-md">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Daftar Atribut</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipe</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Wajib</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
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
                                            {{-- PERBAIKAN: Mengarahkan link ke route edit yang baru --}}
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
                                    <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">
                                        Belum ada atribut untuk kategori ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif
    </div>
@endsection

