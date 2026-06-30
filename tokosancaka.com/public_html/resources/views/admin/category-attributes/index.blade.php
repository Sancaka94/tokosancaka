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
                <select name="category_id" onchange="this.form.submit()" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">-- Tampilkan Semua --</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" {{ ($selectedCategory && $selectedCategory->id == $category->id) ? 'selected' : '' }}>
                            {{ $category->name }}
                            {{-- Info tambahan agar admin tahu jenis kategorinya --}}
                            ({{ ucfirst($category->type) }} - {{ ucwords(str_replace('_', ' ', $category->flag)) }})
                        </option>
                    @endforeach
                </select>
            </form>
        </div>

        {{-- Form Tambah Atribut --}}
        @if ($selectedCategory)
        <div class="bg-white p-6 rounded-lg shadow-md">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">
                Tambah Atribut <br>
                <span class="text-indigo-600 text-sm">{{ $selectedCategory->name }}</span>
            </h2>

            {{-- PERBAIKAN: Route action disesuaikan, tidak menggunakan ID di URL --}}
            <form action="{{ route('admin.category-attributes.store') }}" method="POST" class="space-y-4">
                @csrf

                {{-- PERBAIKAN: Input hidden untuk category_id ditambahkan di sini --}}
                <input type="hidden" name="category_id" value="{{ $selectedCategory->id }}">

                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700">Nama Atribut</label>
                    <input type="text" name="name" id="name" value="{{ old('name') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 @error('name') border-red-500 @enderror" required placeholder="Contoh: Ukuran, Warna, Bahan">
                    @error('name') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="type" class="block text-sm font-medium text-gray-700">Tipe Input</label>
                    <select name="type" id="type" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                        {{-- PERBAIKAN: Menambahkan helper old() agar tidak reset saat error --}}
                        <option value="text" {{ old('type') == 'text' ? 'selected' : '' }}>Teks Singkat (Text)</option>
                        <option value="number" {{ old('type') == 'number' ? 'selected' : '' }}>Angka (Number)</option>
                        <option value="textarea" {{ old('type') == 'textarea' ? 'selected' : '' }}>Teks Panjang (Textarea)</option>
                        <option value="checkbox" {{ old('type') == 'checkbox' ? 'selected' : '' }}>Pilihan Ganda (Checkbox)</option>
                        <option value="select" {{ old('type') == 'select' ? 'selected' : '' }}>Pilihan Tunggal (Select)</option>
                    </select>
                </div>

                <div id="options-container" class="hidden">
                    <label for="options" class="block text-sm font-medium text-gray-700">Pilihan (pisahkan dengan koma)</label>
                    <input type="text" name="options" id="options" value="{{ old('options') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 @error('options') border-red-500 @enderror" placeholder="Contoh: Merah, Kuning, Hijau">
                    <p class="mt-1 text-xs text-gray-500">Hanya diisi untuk tipe Checkbox atau Select.</p>
                    @error('options') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="flex items-center">
                    {{-- PERBAIKAN: Menambahkan pengecekan old() pada checkbox --}}
                    <input type="checkbox" name="is_required" id="is_required" value="1" class="h-4 w-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500" {{ old('is_required') ? 'checked' : '' }}>
                    <label for="is_required" class="ml-2 block text-sm text-gray-900">Wajib Diisi (Required)</label>
                </div>

                <div class="pt-2">
                    <button type="submit" class="w-full bg-indigo-600 text-white py-2 px-4 rounded-md hover:bg-indigo-700 transition">
                        Simpan Atribut
                    </button>
                </div>
            </form>
        </div>
        @endif
    </div>

    {{-- Kolom Kanan: Daftar Atribut --}}
    <div class="lg:col-span-2 bg-white p-6 rounded-lg shadow-md">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">
            Daftar Atribut
            @if($selectedCategory)
                <span class="text-gray-500 text-sm font-normal">({{ $selectedCategory->name }})</span>
            @endif
        </h2>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Atribut</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipe</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Opsi/Pilihan</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Wajib</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse ($attributes as $attribute)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $attribute->name }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <span class="bg-gray-100 text-gray-600 px-2 py-1 rounded text-xs">{{ $attribute->type }}</span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            {{-- Menampilkan label opsi jika ada, jika tidak strip (-) --}}
                            {{ $attribute->options ?: '-' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            @if($attribute->is_required)
                                <span class="text-red-600 text-xs font-semibold"><i class="fas fa-asterisk"></i> Ya</span>
                            @else
                                <span class="text-gray-400 text-xs">Tidak</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex items-center space-x-3">
                                <a href="{{ route('admin.category-attributes.edit', $attribute->id) }}" class="text-indigo-600 hover:text-indigo-900 bg-indigo-50 p-2 rounded" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form action="{{ route('admin.category-attributes.destroy', $attribute->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus atribut ini? Semua data produk terkait atribut ini berpotensi hilang.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-900 bg-red-50 p-2 rounded" title="Hapus">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-sm text-gray-500">
                            @if(!$selectedCategory)
                                <i class="fas fa-hand-point-left text-2xl mb-3 text-gray-300 block"></i>
                                Silakan pilih kategori di kolom sebelah kiri terlebih dahulu.
                            @else
                                <i class="fas fa-clipboard-list text-2xl mb-3 text-gray-300 block"></i>
                                Belum ada atribut khusus untuk kategori ini.
                            @endif
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
                // Optional: set input to required if visible
                document.getElementById('options').setAttribute('required', 'required');
            } else {
                optionsContainer.classList.add('hidden');
                document.getElementById('options').removeAttribute('required');
            }
        }

        if (typeSelect) {
            toggleOptionsField();
            typeSelect.addEventListener('change', toggleOptionsField);
        }
    });
</script>
@endpush
