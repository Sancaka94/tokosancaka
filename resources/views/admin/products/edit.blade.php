@extends('layouts.admin')

@section('title', 'Edit Produk: ' . $product->name)
@section('page-title', 'Edit Produk')

@section('content')
<form action="{{ route('admin.products.update', $product->id) }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Kolom Kiri -->
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Informasi Produk</h2>
                <div class="space-y-4">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700">Nama Produk</label>
                        <input type="text" name="name" id="name" value="{{ old('name', $product->name) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                    </div>
                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700">Deskripsi</label>
                        <textarea name="description" id="description" rows="6" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">{{ old('description', $product->description) }}</textarea>
                    </div>
                </div>
            </div>
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Ganti Gambar Produk (Opsional)</h2>
                <input type="file" name="product_image" class="mt-1 block w-full text-sm">
                <p class="mt-2 text-sm text-gray-500">Gambar saat ini:</p>
                <img src="{{ asset('storage/' . $product->image_url) }}" class="mt-2 h-32 w-32 object-cover rounded-md">
            </div>
        </div>
        
        <!-- =============================================== -->
        <!-- PERBAIKAN: KOLOM KANAN SEKARANG SUDAH LENGKAP -->
        <!-- =============================================== -->
        <div class="space-y-6">
            <!-- Harga & Stok -->
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Harga & Stok</h2>
                <div class="space-y-4">
                    <div>
                        <label for="price" class="block text-sm font-medium text-gray-700">Harga Jual (Rp)</label>
                        <input type="number" name="price" id="price" value="{{ old('price', $product->price) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
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

            <!-- Organisasi Produk -->
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Organisasi Produk</h2>
                <div class="space-y-4">
                    <div>
                        <label for="sku" class="block text-sm font-medium text-gray-700">SKU</label>
                        <input type="text" name="sku" id="sku" value="{{ old('sku', $product->sku) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                    </div>
                    <div>
                        <label for="category" class="block text-sm font-medium text-gray-700">Kategori</label>
                        <input type="text" name="category" id="category" value="{{ old('category', $product->category) }}" list="category-options" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                        <datalist id="category-options">
                            @foreach($categories as $category) <option value="{{ $category }}"> @endforeach
                        </datalist>
                    </div>
                    <div>
                        <label for="tags" class="block text-sm font-medium text-gray-700">Tags (pisahkan koma)</label>
                        <input type="text" name="tags" id="tags" value="{{ old('tags', is_array($product->tags) ? implode(', ', $product->tags) : $product->tags) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    </div>
                </div>
            </div>
            
            <!-- Informasi Penjual -->
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Informasi Penjual</h2>
            
                <div class="space-y-4">
                    <!-- Nama Toko -->
                    <div>
                        <label for="store_name" class="block text-sm font-medium text-gray-700">Nama Toko</label>
                        <input type="text" name="store_name" id="store_name"
                               value="{{ old('store_name', $product->store_name) }}"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    </div>
            
                    <!-- Kota Penjual -->
                    <div>
                        <label for="seller_city" class="block text-sm font-medium text-gray-700">Kota Penjual</label>
                        <input type="text" name="seller_city" id="seller_city"
                               value="{{ old('seller_city', $product->seller_city) }}"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    </div>
            
                    <!-- WhatsApp Toko -->
                    <div>
                        <label for="seller_wa" class="block text-sm font-medium text-gray-700">WhatsApp Toko</label>
                        <div class="relative mt-1">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-500">+62</span>
                            <input type="tel" name="seller_wa" id="seller_wa"
                                   value="{{ old('seller_wa', $product->seller_wa) }}"
                                   placeholder="81234567890"
                                   class="block w-full pl-12 border-gray-300 rounded-md shadow-sm">
                        </div>
                    </div>
            
                    <!-- Logo Toko -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Logo Toko</label>
            
                        <input id="seller_logo" name="seller_logo" type="file" accept="image/*" class="sr-only">
                        <label for="seller_logo"
                               id="seller_logo_dropzone"
                               class="mt-1 flex flex-col items-center justify-center gap-2 w-full rounded-lg border-2 border-dashed border-gray-300 bg-gray-50 p-6 text-center cursor-pointer transition hover:border-indigo-400 hover:bg-indigo-50">
                        
                            <div id="seller_logo_preview" class="{{ $product->seller_logo ? '' : 'hidden' }}">
                                <img src="{{ $product->seller_logo ? asset('storage/' . $product->seller_logo) : '' }}" 
                                     alt="Logo Toko"
                                     class="mx-auto h-20 w-20 rounded-full object-cover ring-1 ring-gray-200">
                                <p id="seller_logo_filename" class="mt-2 text-xs text-gray-500">
                                    {{ $product->seller_logo ? 'Klik untuk ganti logo' : '' }}
                                </p>
                            </div>
                        
                            @if(!$product->seller_logo)
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                          d="M3 15a4 4 0 004 4h10a4 4 0 004-4m-4-6l-4-4m0 0L9 9m4-4v12"/>
                                </svg>
                                <div class="text-sm text-gray-700">
                                    <span class="font-medium">Tarik & lepas</span> atau klik untuk pilih
                                </div>
                            @endif
                        </label>
                    </div>
                </div>
            </div>

            <!-- Status & Label -->
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
                    <div class="flex items-center"><input type="checkbox" name="is_new" id="is_new" value="1" class="h-4 w-4 rounded" {{ old('is_new', $product->is_new) ? 'checked' : '' }}><label for="is_new" class="ml-2">Tandai sebagai Produk Baru</label></div>
                    <div class="flex items-center"><input type="checkbox" name="is_bestseller" id="is_bestseller" value="1" class="h-4 w-4 rounded" {{ old('is_bestseller', $product->is_bestseller) ? 'checked' : '' }}><label for="is_bestseller" class="ml-2">Tandai sebagai Bestseller</label></div>
                </div>
            </div>
        </div>
    </div>
    <div class="mt-6 flex justify-end">
        <a href="{{ route('admin.products.index') }}" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg mr-2">Batal</a>
        <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-lg">Simpan Perubahan</button>
    </div>
</form>


<style>
  .dropzone--over { outline: 2px dashed #6366f1; background-color: #eef2ff; }
</style>

@endsection

@push('scripts')

<script>
  (function(){
    const dz = document.getElementById('seller_logo_dropzone');
    const input = document.getElementById('seller_logo');
    const previewWrap = document.getElementById('seller_logo_preview');
    const previewImg = previewWrap.querySelector('img');
    const previewName = document.getElementById('seller_logo_filename');

    dz.addEventListener('click', () => input.click());

    ;['dragenter','dragover'].forEach(evt => {
      dz.addEventListener(evt, (e) => {
        e.preventDefault(); e.stopPropagation();
        dz.classList.add('dropzone--over');
      });
    });

    ;['dragleave','drop'].forEach(evt => {
      dz.addEventListener(evt, (e) => {
        e.preventDefault(); e.stopPropagation();
        dz.classList.remove('dropzone--over');
      });
    });

    dz.addEventListener('drop', (e) => {
      const files = e.dataTransfer.files;
      if (files && files.length) {
        input.files = files; 
        handleFiles(files);
      }
    });

    input.addEventListener('change', (e) => {
      handleFiles(e.target.files);
    });

    function handleFiles(files){
      const file = files[0];
      if(!file) return;

      if(!file.type.startsWith('image/')){
        alert('File harus berupa gambar (PNG/JPG/JPEG).');
        input.value = '';
        return;
      }
      if(file.size > 2 * 1024 * 1024){
        alert('Ukuran maksimum 2MB.');
        input.value = '';
        return;
      }

      const reader = new FileReader();
      reader.onload = (ev) => {
        previewImg.src = ev.target.result;
        previewName.textContent = file.name;
        previewWrap.classList.remove('hidden');
      };
      reader.readAsDataURL(file);
    }
  })();
</script>
@endpush