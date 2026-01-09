@extends('layouts.admin')

@section('title', 'Manajemen Produk Marketplace')

@section('content')
<div class="p-6">
    <div class="flex justify-between items-center mb-4">
        <h1 class="text-2xl font-semibold">Manajemen Produk Marketplace</h1>
        <button id="btnAddProduct" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
            + Tambah Produk
        </button>
    </div>

    {{-- Filter dan Pencarian --}}
    <form method="GET" action="{{ route('admin.marketplace.index') }}" class="flex flex-wrap gap-3 mb-5">
        <input type="text" name="search" value="{{ request('search') }}"
               placeholder="Cari produk..."
               class="border rounded-lg px-3 py-2 w-full sm:w-1/3">

        <select name="category_filter" class="border rounded-lg px-3 py-2 w-full sm:w-1/4">
            <option value="all">Semua Kategori</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" {{ request('category_filter') == $category->id ? 'selected' : '' }}>
                    {{ $category->name }}
                </option>
            @endforeach
        </select>

        <button type="submit" class="bg-gray-700 text-white px-4 py-2 rounded-lg">
            Filter
        </button>
    </form>

    {{-- Tabel Produk --}}
    <div class="overflow-x-auto bg-white shadow rounded-lg">
        <table class="min-w-full text-sm text-left">
            <thead class="bg-gray-100 text-gray-700 uppercase text-xs">
                <tr>
                    <th class="px-4 py-3">#</th>
                    <th class="px-4 py-3">Gambar</th>
                    <th class="px-4 py-3">Nama</th>
                    <th class="px-4 py-3">Kategori</th>
                    <th class="px-4 py-3">Harga</th>
                    <th class="px-4 py-3">Stok</th>
                    <th class="px-4 py-3">Flash Sale</th>
                    <th class="px-4 py-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($products as $index => $product)
                    <tr class="border-b hover:bg-gray-50">
                        <td class="px-4 py-3">{{ $products->firstItem() + $index }}</td>
                        <td class="px-4 py-3">
                            {{-- PERBAIKAN: Path asset() yang benar --}}
                            <img src="{{ asset('public/storage/'.$product->image_url) }}"
                                 onerror="this.src='https://placehold.co/60x60?text=No+Image';"
                                 class="w-14 h-14 rounded object-cover">
                        </td>
                        <td class="px-4 py-3 font-medium">{{ $product->name }}</td>
                        <td class="px-4 py-3">{{ $product->category }}</td>
                        <td class="px-4 py-3">Rp {{ number_format($product->price,0,',','.') }}</td>
                        <td class="px-4 py-3">{{ $product->stock }}</td>
                        <td class="px-4 py-3">
                            @if($product->is_flash_sale)
                                <span class="text-green-600 font-semibold">Ya</span>
                            @else
                                <span class="text-gray-500">Tidak</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            {{-- PERBAIKAN: Gunakan data-slug, bukan data-id --}}
                            <button data-slug="{{ $product->slug }}" class="btnEdit bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-1 rounded">Edit</button>
                            <button data-slug="{{ $product->slug }}" class="btnDelete bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded">Hapus</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-4 text-gray-500">Tidak ada produk ditemukan</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $products->links() }}
    </div>
</div>

{{-- Modal Produk --}}
<div id="productModalOverlay" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 justify-center items-center p-4">
    <div id="productModal" class="bg-white rounded-lg w-full max-w-3xl shadow-xl flex flex-col">
        <div class="flex justify-between items-center p-4 border-b">
            <h2 id="modalTitle" class="text-lg font-semibold">Tambah Produk</h2>
            <button type="button" class="btn-close-modal text-gray-600 hover:text-gray-900 text-2xl">&times;</button>
        </div>
        <div class="overflow-y-auto p-6 space-y-4" style="max-height: 70vh;">
            <form id="productForm" enctype="multipart/form-data">
                @csrf
                {{-- ID Produk tidak lagi digunakan untuk URL, tapi kita simpan untuk referensi --}}
                <input type="hidden" id="product_id" name="product_id">
                <input type="hidden" id="product_slug" name="product_slug">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- Kolom Kiri --}}
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium">Nama Produk *</label>
                            <input type="text" name="name" id="name" class="border rounded-lg w-full px-3 py-2 text-sm">
                        </div>

                        <div>
                            <label class="block text-sm font-medium">SKU</label>
                            <input type="text" name="sku" id="sku" class="border rounded-lg w-full px-3 py-2 text-sm">
                        </div>

                        <div>
                            <label class="block text-sm font-medium">Toko (Store) *</label>
                            <select name="store_id" id="store_id" class="border rounded-lg w-full px-3 py-2 text-sm">
                                <option value="">Pilih Toko</option>
                                {{-- Variabel $stores harus dikirim dari controller --}}
                                @if(isset($stores))
                                    @foreach($stores as $store)
                                        <option value="{{ $store->id }}">{{ $store->name }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium">Kategori *</label>
                            <select name="category_id" id="category_id" class="border rounded-lg w-full px-3 py-2 text-sm">
                                <option value="">Pilih Kategori</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium">Status *</label>
                            <select name="status" id="status" class="border rounded-lg w-full px-3 py-2 text-sm">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="pending">Pending</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium">Tags (pisahkan koma)</label>
                            <input type="text" name="tags" id="tags" class="border rounded-lg w-full px-3 py-2 text-sm">
                        </div>

                    </div>

                    {{-- Kolom Kanan --}}
                    <div class="space-y-4">
                        <fieldset class="border rounded-lg p-4">
                            <legend class="text-sm font-medium px-2">Harga & Stok</legend>
                            <div class="flex flex-col sm:flex-row gap-4">
                                <div class="w-full sm:w-1/2">
                                    <label class="block text-sm font-medium">Harga *</label>
                                    <input type="number" name="price" id="price" class="border rounded-lg w-full px-3 py-2 text-sm">
                                </div>
                                <div class="w-full sm:w-1/2">
                                    <label class="block text-sm font-medium">Harga Asli (Coret)</label>
                                    <input type="number" name="original_price" id="original_price" class="border rounded-lg w-full px-3 py-2 text-sm">
                                </div>
                            </div>
                            <div class="mt-4">
                                <label class="block text-sm font-medium">Stok *</label>
                                <input type="number" name="stock" id="stock" class="border rounded-lg w-full px-3 py-2 text-sm">
                            </div>
                        </fieldset>

                        <fieldset class="border rounded-lg p-4">
                            <legend class="text-sm font-medium px-2">Pengiriman (Gram & CM) *</legend>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                <div>
                                    <label class="block text-sm font-medium">Berat (gr) *</label>
                                    <input type="number" name="weight" id="weight" class="border rounded-lg w-full px-3 py-2 text-sm" value="1000">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium">Panjang</label>
                                    <input type="number" name="length" id="length" class="border rounded-lg w-full px-3 py-2 text-sm" value="5">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium">Lebar</label>
                                    <input type="number" name="width" id="width" class="border rounded-lg w-full px-3 py-2 text-sm" value="5">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium">Tinggi</label>
                                    <input type="number" name="height" id="height" class="border rounded-lg w-full px-3 py-2 text-sm" value="5">
                                </div>
                            </div>
                             <div class="mt-4">
                                <label class="block text-sm font-medium">Jenis Barang (KiriminAja) *</label>
                                <select name="jenis_barang" id="jenis_barang" class="border rounded-lg w-full px-3 py-2 text-sm">
                                    <option value="1">General (Umum)</option>
                                    <option value="3">Dangerous Goods</option>
                                    <option value="4">Food</option>
                                    <option value="8">Frozen Food</option>
                                    {{-- Tambahkan opsi lain jika perlu --}}
                                </select>
                            </div>
                        </fieldset>

                    </div>
                </div>

                {{-- Bagian Bawah (Full Width) --}}
                <div class="mt-6">
                    <label class="block text-sm font-medium">Deskripsi</label>
                    <textarea name="description" id="description" rows="3" class="border rounded-lg w-full px-3 py-2 text-sm"></textarea>
                </div>

                <div class="mt-4">
                    <label class="block text-sm font-medium">Gambar Produk</label>
                    <input type="file" name="image_url" id="image_url" accept="image/*" class="text-sm">
                    <img id="previewImage" src="" alt="Preview Gambar" class="hidden mt-2 rounded w-32 h-32 object-cover border">
                    <p class="text-xs text-gray-500 mt-1">Kosongkan jika tidak ingin mengubah gambar.</p>
                </div>

                <div class="flex items-center mt-4">
                    <input type="checkbox" name="is_flash_sale" id="is_flash_sale" value="1" class="mr-2">
                    <label for="is_flash_sale" class="text-sm">Tandai sebagai Flash Sale</label>
                </div>

                <div class="text-right pt-4 border-t mt-6">
                    <button type="button" class="btn-close-modal bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-lg mr-2">Batal</button>
                    <button type="submit" id="btnSubmitForm" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">

<script>
$(function() {
    // PERBAIKAN: Base URL sekarang tidak menyertakan 'slug' atau 'id'
    const BASE_URL = "{{ url('admin/marketplace') }}";
    const STORE_URL = "{{ route('admin.marketplace.store') }}";
    const CSRF_TOKEN = '{{ csrf_token() }}';
    // PERBAIKAN: Path asset() yang benar
    const STORAGE_URL = "{{ asset('public/storage') }}";

    toastr.options = {positionClass: "toast-top-right", progressBar: true, timeOut: 3000};

    // === Tambah Produk ===
    $('#btnAddProduct').click(function() {
        $('#productForm')[0].reset();
        $('#product_id').val('');
        $('#product_slug').val(''); // Pastikan slug juga di-reset
        $('#previewImage').addClass('hidden');
        $('#modalTitle').text('Tambah Produk Baru');
        $('#productModalOverlay').removeClass('hidden').addClass('flex');
        
        // Set default values untuk form tambah
        $('#status').val('active');
        $('#weight').val('1000');
        $('#length').val('5');
        $('#width').val('5');
        $('#height').val('5');
        $('#jenis_barang').val('1');
    });

    // === Edit Produk ===
    $(document).on('click', '.btnEdit', function() {
        // PERBAIKAN: Gunakan 'data-slug'
        const slug = $(this).data('slug');
        if (!slug) return;

        $('#modalTitle').text('Memuat...');
        $('#productModalOverlay').removeClass('hidden').addClass('flex');

        $.get(`${BASE_URL}/${slug}`, function(res) {
            const p = res.product;
            $('#modalTitle').text('Edit Produk: ' + p.name);
            
            // Isi semua field form
            $('#product_id').val(p.id); // Simpan ID untuk referensi
            $('#product_slug').val(p.slug); // Simpan Slug untuk URL submit
            $('#name').val(p.name);
            $('#sku').val(p.sku);
            $('#store_id').val(p.store_id);
            $('#category_id').val(p.category_id);
            $('#status').val(p.status);
            $('#tags').val(p.tags);
            
            $('#price').val(p.price);
            $('#original_price').val(p.original_price);
            $('#stock').val(p.stock);
            
            $('#weight').val(p.weight);
            $('#length').val(p.length);
            $('#width').val(p.width);
            $('#height').val(p.height);
            $('#jenis_barang').val(p.jenis_barang);
            
            $('#description').val(p.description);
            $('#is_flash_sale').prop('checked', p.is_flash_sale == 1);
            
            if (p.image_url) {
                // PERBAIKAN: Path asset() yang benar
                $('#previewImage').attr('src', `${STORAGE_URL}/${p.image_url}`).removeClass('hidden');
            } else {
                $('#previewImage').addClass('hidden');
            }
        }).fail(() => {
            toastr.error('Gagal mengambil data produk.');
            $('#productModalOverlay').addClass('hidden');
        });
    });

    // === Simpan Produk (Tambah / Edit) ===
    $('#productForm').submit(function(e) {
        e.preventDefault();
        
        // PERBAIKAN: Tentukan URL berdasarkan slug, bukan ID
        const slug = $('#product_slug').val();
        const url = slug ? `${BASE_URL}/${slug}` : STORE_URL;
        
        const fd = new FormData(this);
        if (slug) fd.append('_method', 'PUT'); // Method spoofing untuk update

        $('#btnSubmitForm').prop('disabled', true).text('Menyimpan...');

        $.ajax({
            url: url, 
            method: 'POST', // Selalu POST, biarkan _method yang urus Update
            data: fd, 
            processData: false, 
            contentType: false,
            headers: {'X-CSRF-TOKEN': CSRF_TOKEN},
            success: res => {
                toastr.success(res.message);
                setTimeout(() => location.reload(), 1000);
            },
            error: xhr => {
                // Menampilkan error validasi
                if (xhr.status === 422) {
                    let errors = xhr.responseJSON.errors;
                    let errorMsg = '<ul>';
                    $.each(errors, function(key, value) {
                        errorMsg += '<li>' + value[0] + '</li>';
                    });
                    errorMsg += '</ul>';
                    toastr.error(errorMsg, 'Error Validasi');
                } else {
                    toastr.error(xhr.responseJSON?.message || 'Gagal menyimpan data');
                }
                $('#btnSubmitForm').prop('disabled', false).text('Simpan');
            }
        });
    });

    // === Hapus Produk ===
    $(document).on('click', '.btnDelete', function() {
        // PERBAIKAN: Gunakan 'data-slug'
        const slug = $(this).data('slug');
        if (!slug) return;

        if (!confirm('Yakin ingin menghapus produk ini?')) return;

        $.post(`${BASE_URL}/${slug}`, {_method:'DELETE', _token:CSRF_TOKEN}, res => {
            toastr.success(res.message);
            setTimeout(()=>location.reload(), 800);
        }).fail(()=>toastr.error('Gagal menghapus produk'));
    });

    // === Preview Gambar ===
    $('#image_url').change(function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = e => $('#previewImage').attr('src', e.target.result).removeClass('hidden');
            reader.readAsDataURL(file);
        }
    });

    // === Tutup Modal ===
    $('#productModalOverlay').on('click', function(e) {
        if (e.target.id === 'productModalOverlay' || $(e.target).hasClass('btn-close-modal') || $(e.target).closest('.btn-close-modal').length)
            $('#productModalOverlay').addClass('hidden').removeClass('flex');
    });
});
</script>
@endpush