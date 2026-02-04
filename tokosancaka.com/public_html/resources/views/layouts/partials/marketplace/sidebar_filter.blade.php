<form id="filter-form" action="{{ route('products.index') }}" method="GET">
    <aside class="filter-sidebar" style="position: sticky; top: 100px;">
        <h5 class="filter-title">Filter Produk</h5>
        <div class="filter-group"><label class="form-label fw-bold">Kategori</label>
            @foreach ($categories as $category)
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="categories[]" value="{{ $category }}" id="cat-{{ Str::slug($category) }}" {{ in_array($category, request('categories', [])) ? 'checked' : '' }}>
                <label class="form-check-label" for="cat-{{ Str::slug($category) }}">{{ $category }}</label>
            </div>
            @endforeach
        </div>
        <div class="filter-group"><label class="form-label fw-bold">Rentang Harga</label><div class="d-flex gap-2"><input type="number" name="price_min" class="form-control" placeholder="Rp Min" value="{{ request('price_min') }}"><input type="number" name="price_max" class="form-control" placeholder="Rp Max" value="{{ request('price_max') }}"></div></div>
        <div class="filter-group"><label class="form-label fw-bold">Rating</label><div class="form-check"><input class="form-check-input" type="radio" name="rating" id="rating4" value="4" {{ request('rating') == '4' ? 'checked' : '' }}><label class="form-check-label text-warning" for="rating4"><i class="fas fa-star"></i> 4 Keatas</label></div></div>
        <input type="hidden" name="sort" value="{{ request('sort', 'latest') }}">
        <div class="d-grid"><button type="submit" class="btn btn-danger btn-apply-filter">Terapkan Filter</button></div>
    </aside>
</form>
