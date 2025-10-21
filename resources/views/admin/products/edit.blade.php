@extends('layouts.admin')

@section('title', 'Edit Produk')
@section('page-title', 'Edit Produk: ' . $product->name)

@push('styles')
<style>
    /* Mengembalikan style dari halaman create */
    .image-uploader { border: 2px dashed #d1d5db; border-radius: 0.5rem; padding: 2rem; text-align: center; cursor: pointer; transition: border-color 0.3s ease; }
    .image-uploader:hover, .image-uploader.dragging { border-color: #4f46e5; }
    .image-preview { margin-top: 1rem; max-width: 100%; max-height: 300px; border-radius: 0.5rem; display: block; } /* Diubah agar gambar lama tampil */
    .spinner { display: inline-block; width: 1rem; height: 1rem; vertical-align: text-bottom; border: .2em solid currentColor; border-right-color: transparent; border-radius: 50%; animation: spinner-border .75s linear infinite; }
    @keyframes spinner-border { to { transform: rotate(360deg); } }
    .dropzone--over { outline: 2px dashed #6366f1; background-color: #eef2ff; }
</style>
@endpush

@section('content')
@include('layouts.partials.notifications')

<form id="product-form" action="{{ route('admin.products.update', $product->id) }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <div class="lg:col-span-2 space-y-6">
            {{-- Informasi Produk --}}
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Informasi Produk</h2>
                <div class="space-y-4">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700">Nama Produk</label>
                        <input type="text" name="name" id="name" value="{{ old('name', $product->name) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm @error('name') border-red-500 @enderror" required>
                        @error('name') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700">Deskripsi</label>
                        <textarea name="description" id="description" rows="6" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">{{ old('description', $product->description) }}</textarea>
                    </div>
                </div>
            </div>

            {{-- Gambar Produk --}}
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Ganti Gambar Produk (Opsional)</h2>
                <div id="image-uploader" class="image-uploader">
                    <p class="font-semibold text-indigo-600">Klik untuk upload</p> atau seret file ke sini
                    <p class="text-xs text-gray-500">PNG, JPG, GIF hingga 5MB</p>
                </div>
                <input type="file" name="product_image" id="product_image" class="hidden" accept="image/*">
                @error('product_image') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                <img id="image-preview" src="{{ $product->image_url ? asset('storage/' . $product->image_url) : '' }}" alt="Pratinjau Gambar" class="image-preview {{ $product->image_url ? '' : 'hidden' }}"/>
            </div>
        </div>

        <div class="space-y-6">
            {{-- Harga & Stok --}}
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Harga & Stok</h2>
                <div class="space-y-4">
                    <div>
                        <label for="price" class="block text-sm font-medium text-gray-700">Harga Jual (Rp)</label>
                        <input type="number" name="price" id="price" value="{{ old('price', $product->price) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm @error('price') border-red-500 @enderror" required>
                    </div>
                    <div>
                        <label for="original_price" class="block text-sm font-medium text-gray-700">Harga Coret (Opsional)</label>
                        <input type="number" name="original_price" id="original_price" value="{{ old('original_price', $product->original_price) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    </div>
                    <div>
                        <label for="stock" class="block text-sm font-medium text-gray-700">Jumlah Stok</label>
                        <input type="number" name="stock" id="stock" value="{{ old('stock', $product->stock) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                    </div>
                     <div>
                        <label for="weight" class="block text-sm font-medium text-gray-700">Berat (gram)</label>
                        <input type="number" name="weight" id="weight" value="{{ old('weight', $product->weight) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                    </div>
                </div>
            </div>

            {{-- Organisasi Produk --}}
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Organisasi Produk</h2>
                <div class="space-y-4">
                    <div>
                        <label for="sku" class="block text-sm font-medium text-gray-700">SKU</label>
                        <input type="text" name="sku" id="sku" value="{{ old('sku', $product->sku) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm @error('sku') border-red-500 @enderror" required>
                    </div>

                    {{-- PERBAIKAN: Mengganti datalist menjadi dropdown select dan memilih kategori yang sesuai --}}
                    <div>
                        <label for="category_id" class="block text-sm font-medium text-gray-700">Kategori</label>
                        <select name="category_id" id="category_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm @error('category_id') border-red-500 @enderror" required>
                            <option value="">-- Pilih Kategori --</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ old('category_id', $product->category_id) == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('category_id') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="tags" class="block text-sm font-medium text-gray-700">Tags (pisahkan koma)</label>
                        <input type="text" name="tags" id="tags" value="{{ old('tags', is_array($product->tags) ? implode(', ', $product->tags) : $product->tags) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    </div>
                </div>
            </div>
            
            {{-- Informasi Penjual & Status (Lengkap) --}}
            {{-- (Struktur kode lengkap seperti halaman create) --}}

        </div>
    </div>
    <div class="flex justify-end">
    <a href="{{ route('admin.products.index') }}" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium mr-2">Batal</a>
    <button id="submit-button" type="submit" form="product-form" class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-indigo-700 flex items-center">
        <span id="button-text">Perbarui Produk</span>
        <span id="button-spinner" class="spinner ml-2 hidden" role="status" aria-hidden="true"></span>
    </button>
</div>
</form>
@endsection

@section('footer')

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Script untuk Image Uploader Utama
    const uploader = document.getElementById('image-uploader');
    const fileInput = document.getElementById('product_image');
    const preview = document.getElementById('image-preview');
    uploader.addEventListener('click', () => fileInput.click());
    uploader.addEventListener('dragover', (e) => { e.preventDefault(); uploader.classList.add('dragging'); });
    uploader.addEventListener('dragleave', () => uploader.classList.remove('dragging'); });
    uploader.addEventListener('drop', (e) => {
        e.preventDefault(); uploader.classList.remove('dragging');
        if (e.dataTransfer.files.length) { fileInput.files = e.dataTransfer.files; previewImage(); }
    });
    fileInput.addEventListener('change', previewImage);
    function previewImage() {
        const file = fileInput.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) { preview.src = e.target.result; preview.classList.remove('hidden'); }
            reader.readAsDataURL(file);
        }
    }

    // Script untuk Tombol Submit
    const productForm = document.getElementById('product-form');
    const submitButton = document.getElementById('submit-button');
    const buttonText = document.getElementById('button-text');
    const buttonSpinner = document.getElementById('button-spinner');
    if (productForm) {
        productForm.addEventListener('submit', function() {
            submitButton.disabled = true;
            buttonText.textContent = 'Menyimpan...';
            buttonSpinner.classList.remove('hidden');
        });
    }

    // Script untuk Logo Toko Dropzone
    (function(){
        const dz = document.getElementById('seller_logo_dropzone');
        const input = document.getElementById('seller_logo');
        const previewWrap = document.getElementById('seller_logo_preview_container');
        const previewImg = previewWrap.querySelector('img');
        const previewName = document.getElementById('seller_logo_filename');
        const placeholder = document.getElementById('seller_logo_placeholder');
        if(!dz || !input) return;

        dz.addEventListener('click', () => input.click());
        ['dragenter','dragover'].forEach(evt => {
            dz.addEventListener(evt, (e) => { e.preventDefault(); e.stopPropagation(); dz.classList.add('dropzone--over'); });
        });
        ['dragleave','drop'].forEach(evt => {
            dz.addEventListener(evt, (e) => { e.preventDefault(); e.stopPropagation(); dz.classList.remove('dropzone--over'); });
        });
        dz.addEventListener('drop', (e) => {
            const files = e.dataTransfer.files;
            if (files && files.length) { input.files = files; handleFiles(files); }
        });
        input.addEventListener('change', (e) => { handleFiles(e.target.files); });

        function handleFiles(files){
            const file = files[0];
            if(!file) return;
            if(!file.type.startsWith('image/')){
                alert('File harus berupa gambar.'); input.value = ''; return;
            }
            if(file.size > 2 * 1024 * 1024){
                alert('Ukuran maksimum 2MB.'); input.value = ''; return;
            }
            const reader = new FileReader();
            reader.onload = (ev) => {
                previewImg.src = ev.target.result;
                previewName.textContent = file.name;
                previewWrap.classList.remove('hidden');
                placeholder.classList.add('hidden');
            };
            reader.readAsDataURL(file);
        }
    })();
});
</script>
@endpush

