@forelse ($products as $product)
<tr id="product-{{ $product->id }}">
    <td class="px-6 py-4 whitespace-nowrap">
        <img src="{{ $product->image_url ?? 'https://placehold.co/64' }}" alt="{{ $product->name }}" class="w-16 h-16 object-cover rounded">
    </td>
    <td class="px-6 py-4 font-medium text-gray-900">{{ $product->name }}</td>
    <td class="px-6 py-4 text-gray-500">Rp{{ number_format($product->price) }}</td>
    <td class="px-6 py-4 text-gray-500">{{ $product->stock }}</td>
    <td class="px-6 py-4">
        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $product->is_flash_sale ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800' }}">
            {{ $product->is_flash_sale ? 'Ya' : 'Tidak' }}
        </span>
    </td>
    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
        <button onclick="editProduct({{ $product->id }})" class="text-indigo-600 hover:text-indigo-900">Edit</button>
        <button onclick="deleteProduct({{ $product->id }})" class="text-red-600 hover:text-red-900 ml-4">Hapus</button>
    </td>
</tr>
@empty
<tr>
    <td colspan="6" class="text-center py-10 text-gray-500">
        <p>Data produk tidak ditemukan.</p>
        <p class="text-sm mt-1">Coba kata kunci lain atau <a href="{{ route('admin.marketplace.index') }}" class="text-indigo-600 hover:underline">reset pencarian</a>.</p>
    </td>
</tr>
@endforelse

