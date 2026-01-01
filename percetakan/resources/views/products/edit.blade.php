<form action="{{ route('products.update', $product->id) }}" method="POST">
    @csrf
    @method('PUT')
    
    <input type="text" name="name" value="{{ $product->name }}" class="w-full rounded-xl border-slate-200 mb-4">
    <input type="number" name="base_price" value="{{ $product->base_price }}" class="w-full rounded-xl border-slate-200 mb-4">
    
    <select name="unit" class="w-full rounded-xl border-slate-200 mb-4">
        <option value="meter" {{ $product->unit == 'meter' ? 'selected' : '' }}>Meter</option>
        <option value="lembar" {{ $product->unit == 'lembar' ? 'selected' : '' }}>Lembar</option>
        <option value="pcs" {{ $product->unit == 'pcs' ? 'selected' : '' }}>Pcs</option>
    </select>

    <button type="submit" class="w-full py-4 bg-indigo-600 text-white rounded-2xl font-bold">Update Produk</button>
</form>