{{-- 
    File: resources/views/admin/marketplace/partials/product_rows.blade.php
    Deskripsi: Hanya berisi baris-baris <tr> untuk tabel produk. 
               Digunakan untuk refresh data via AJAX.
--}}
@forelse($products as $product)
<tr id="product-row-{{ $product->id }}">
    <td class="px-6 py-4 whitespace-nowrap">
        {{-- Menampilkan gambar dari storage link --}}
        <img src="{{ $product->image_url ? asset('storage/' . $product->image_url) : 'https://placehold.co/100' }}" alt="{{ $product->name }}" class="h-12 w-12 object-cover rounded-md">
    </td>
    <td class="px-6 py-4 whitespace-nowrap">
        <div class="text-sm font-medium text-gray-900">{{ $product->name }}</div>
    </td>
    <td class="px-6 py-4 whitespace-nowrap">
        {{-- Menampilkan nama kategori dari relasi --}}
        <div class="text-sm text-gray-500">{{ $product->category->name ?? 'N/A' }}</div>
    </td>
    <td class="px-6 py-4 whitespace-nowrap">
        <div class="text-sm text-gray-900">Rp{{ number_format($product->price) }}</div>
    </td>
    <td class="px-6 py-4 whitespace-nowrap">
        <div class="text-sm text-gray-900">{{ $product->stock }}</div>
    </td>
    <td class="px-6 py-4 whitespace-nowrap">
        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $product->is_flash_sale ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
            {{ $product->is_flash_sale ? 'Ya' : 'Tidak' }}
        </span>
    </td>
    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
        <button onclick="editProduct({{ $product->id }})" class="text-indigo-600 hover:text-indigo-900 mr-3">Edit</button>
        <button onclick="deleteProduct({{ $product->id }})" class="text-red-600 hover:text-red-900">Hapus</button>
    </td>
</tr>
@empty
<tr>
    <td colspan="7" class="text-center py-4 text-sm text-gray-500">Tidak ada produk ditemukan.</td>
</tr>
@endforelse
