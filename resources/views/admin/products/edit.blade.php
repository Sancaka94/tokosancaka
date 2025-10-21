@extends('layouts.admin')

@section('title', 'Edit Produk')
@section('page-title', 'Edit Produk: ' . $product->name)

@push('styles')
<style>
    /* Style untuk form, tidak diubah */
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
    @method('PUT') {{-- Method untuk update --}}
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
                        @error('price') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
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
                        @error('sku') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
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
            
            {{-- PERBAIKAN: Melengkapi form Informasi Penjual --}}
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Informasi Penjual</h2>
                <div class="space-y-4">
                    <div>
                        <label for="store_name" class="block text-sm font-medium text-gray-700">Nama Toko</label>
                        <input type="text" name="store_name" id="store_name" value="{{ old('store_name', $product->store_name) }}" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label for="seller_city" class="block text-sm font-medium text-gray-700">Kota Penjual</label>
                        <input type="text" name="seller_city" id="seller_city" value="{{ old('seller_city', $product->seller_city) }}" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label for="seller_wa" class="block text-sm font-medium text-gray-700">WhatsApp Toko</label>
                        <div class="relative mt-1">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-500 select-none">+62</span>
                            <input type="tel" name="seller_wa" id="seller_wa" inputmode="numeric" placeholder="81234567890" value="{{ old('seller_wa', $product->seller_wa) }}" class="block w-full rounded-md border border-gray-300 pl-12 pr-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500" aria-describedby="seller_wa_help">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Ganti Logo Toko</label>
                        <input id="seller_logo" name="seller_logo" type="file" accept="image/*" class="sr-only">
                        <label for="seller_logo" id="seller_logo_dropzone" class="mt-1 flex flex-col items-center justify-center gap-2 w-full rounded-lg border-2 border-dashed border-gray-300 bg-gray-50 p-6 text-center cursor-pointer transition hover:border-indigo-400 hover:bg-indigo-50">
                            <div id="seller_logo_preview_container" class="{{ $product->seller_logo ? '' : 'hidden' }}">
                                <img src="{{ $product->seller_logo ? asset('storage/' . $product->seller_logo) : '' }}" alt="Logo Toko" class="mx-auto h-20 w-20 rounded-full object-cover ring-1 ring-gray-200">
                                <p id="seller_logo_filename" class="mt-2 text-xs text-gray-500">Klik untuk ganti logo</p>
                            </div>
                            <div id="seller_logo_placeholder" class="{{ $product->seller_logo ? 'hidden' : '' }}">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 15a4 4 0 004 4h10a4 4 0 004-4m-4-6l-4-4m0 0L9 9m4-4v12"/></svg>
                                <div class="text-sm text-gray-700"><span class="font-medium">Tarik & lepas</span> atau <span class="font-medium text-indigo-600 underline">klik</span></div>
                                <p class="text-xs text-gray-500">PNG, JPG (maks. 2MB)</p>
                            </div>
                        </label>
                    </div>
                </div>
            </div>

            {{-- PERBAIKAN: Melengkapi form Status & Label --}}
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Status & Label</h2>
                <div class="space-y-4">
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700">Status Produk</label>
                        <select name="status" id="status" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                            <option value="active" {{ old('status', $product->status) == 'active' ? 'selected' : '' }}>Aktif (Dijual)</option>
                            <option value="inactive" {{ old('status', $product->status) == 'inactive' ? 'selected' : '' }}>Tidak Aktif (Disimpan)</option>
                        </select>
                    </div>
                    <div class="flex items-center">
                        <input type="checkbox" name="is_new" id="is_new" value="1" class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" {{ old('is_new', $product->is_new) ? 'checked' : '' }}>
                        <label for="is_new" class="ml-2 block text-sm text-gray-900">Tandai sebagai Produk Baru</label>
                    </div>
                    <div class="flex items-center">
                        <input type="checkbox" name="is_bestseller" id="is_bestseller" value="1" class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" {{ old('is_bestseller', $product->is_bestseller) ? 'checked' : '' }}>
                        <label for="is_bestseller" class="ml-2 block text-sm text-gray-900">Tandai sebagai Bestseller</label>
                    </div>
                </div>
            </div>

        </div>
    </div>
</form>
@endsection

@section('footer')
<div class="flex justify-end">
    <a href="{{ route('admin.products.index') }}" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium mr-2">Batal</a>
    <button id="submit-button" type="submit" form="product-form" class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-indigo-700 flex items-center">
        <span id="button-text">Perbarui Produk</span>
        <span id="button-spinner" class="spinner ml-2 hidden" role="status" aria-hidden="true"></span>
    </button>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // PERBAIKAN: Melengkapi semua script
    
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

