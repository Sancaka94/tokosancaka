@extends('layouts.marketplace')

{{-- Ganti title agar lebih dinamis --}}
@section('title', $product->name . ' - ' . ($product->category->name ?? 'Produk'))

@push('styles')
{{-- Load Bootstrap Icons --}}
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
{{-- Load Bootstrap 5 (jika belum ada di layout) --}}
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
    body {
        background-color: #f8f9fa; /* Latar belakang abu-abu muda */
        font-family: 'Inter', sans-serif; /* Pastikan font Inter dimuat di layout */
    }
    .product-detail-card {
        background-color: #fff;
        border-radius: 0.75rem; /* Lebih bulat */
        box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
        overflow: hidden;
    }
    .product-gallery .main-image img {
        width: 100%;
        aspect-ratio: 1/1; /* Jaga rasio 1:1 */
        object-fit: cover; /* atau 'contain' jika ingin gambar utuh */
        border-radius: 0.5rem;
        border: 1px solid #dee2e6;
    }
     /* Style untuk thumbnail (jika ada) */
    .thumbnail-list {
        display: grid;
        grid-template-columns: repeat(5, 1fr); /* 5 thumbnail per baris */
        gap: 0.5rem;
        margin-top: 0.75rem;
    }
    .thumbnail-list img {
        width: 100%;
        aspect-ratio: 1/1;
        object-fit: cover;
        border-radius: 0.375rem;
        border: 1px solid #dee2e6;
        cursor: pointer;
        transition: border-color 0.2s ease;
    }
    .thumbnail-list img:hover,
    .thumbnail-list img.active {
        border-color: #dc3545; /* Warna merah saat hover/aktif */
        box-shadow: 0 0 0 2px rgba(220, 53, 69, 0.5);
    }
    .product-info h1 {
        font-weight: 700;
        color: #212121; /* Hitam pekat */
        font-size: 1.75rem; /* Ukuran judul */
        line-height: 1.3;
    }
    .info-meta {
        display: flex;
        align-items: center;
        flex-wrap: wrap; /* Agar wrap di mobile */
        gap: 0.5rem 1rem; /* Jarak vertikal dan horizontal */
        margin: 0.5rem 0 1.5rem 0;
        font-size: 0.9rem;
        color: #6c757d; /* Abu-abu */
    }
    .info-meta .rating {
        color: #FFC107; /* Kuning rating */
        font-weight: 600;
        display: inline-flex; /* Agar ikon dan teks sejajar */
        align-items: center;
        gap: 0.25rem;
    }
    .info-meta .vr {
        align-self: stretch; /* Garis pemisah setinggi flex item */
        border-left: 1px solid #dee2e6;
    }
    .price-section {
        background-color: #f8f9fa; /* Latar abu-abu untuk bagian harga */
        padding: 1rem;
        border-radius: 0.5rem;
    }
    .price-section .current-price {
        font-size: 2.25rem; /* Harga utama lebih besar */
        font-weight: 700;
        color: #dc3545; /* Merah */
        line-height: 1;
    }
    .price-section .original-price {
        font-size: 1.1rem;
        text-decoration: line-through;
        color: #6c757d;
        margin-left: 0.75rem;
    }
    .price-section .discount-badge {
         font-size: 0.8rem;
         font-weight: 600;
    }
    .action-buttons .btn {
        padding: 0.8rem 1.5rem;
        font-weight: 600;
        border-radius: 0.5rem;
        font-size: 0.95rem; /* Sedikit besarkan font tombol */
    }
    .quantity-section {
        display: flex;
        align-items: center;
        gap: 0.75rem; /* Jarak antar elemen quantity */
        margin-bottom: 1.5rem; /* Jarak ke tombol aksi */
    }
    .quantity-section .form-label {
        margin-bottom: 0;
        font-weight: 500;
    }
    .quantity-section .input-group {
        width: auto; /* Agar input group tidak full width */
    }
    .quantity-section .btn-qty {
         padding: 0.375rem 0.75rem; /* Ukuran tombol +/- */
         border-color: #ced4da;
    }
    .quantity-section .form-control-qty {
        width: 60px; /* Lebar input angka */
        text-align: center;
        border-left: none;
        border-right: none;
    }
    .quantity-section .stock-info {
        font-size: 0.85rem;
        color: #6c757d;
    }
    .seller-info-card {
        display: flex;
        flex-wrap: wrap; /* Wrap di mobile */
        align-items: center;
        gap: 1rem;
        padding: 1rem;
        background-color: #fff;
        border-radius: 0.5rem;
        border: 1px solid #e9ecef;
        margin-bottom: 1.5rem; /* Jarak sebelum form */
    }
    .seller-info-card img {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        object-fit: cover;
    }
    .seller-info-card .seller-details {
         flex-grow: 1; /* Ambil sisa ruang */
    }
    .seller-info-card .seller-actions {
        margin-left: auto; /* Dorong tombol ke kanan di layar besar */
        width: 100%; /* Full width di mobile */
        display: flex;
        justify-content: flex-end; /* Ratakan ke kanan */
    }
     @media (min-width: 576px) { /* sm breakpoint */
        .seller-info-card .seller-actions {
            width: auto; /* Kembali ke auto di layar lebih besar */
        }
     }

    .nav-tabs .nav-link {
        color: #6c757d;
        font-weight: 600;
        border-radius: 0.5rem 0.5rem 0 0; /* Bulatkan sudut atas */
        border-color: transparent transparent #dee2e6; /* Border bawah saja */
    }
    .nav-tabs .nav-link.active {
        color: #dc3545;
        border-color: #dee2e6 #dee2e6 #fff; /* Border atas, kiri, kanan */
        background-color: #fff;
    }
    .description-content, .specification-content {
        padding: 1.5rem;
        background-color: #fff;
        border: 1px solid #dee2e6;
        border-top: none; /* Hilangkan border atas karena sudah ada di tab */
        border-radius: 0 0 0.5rem 0.5rem; /* Bulatkan sudut bawah */
        line-height: 1.6; /* Spasi antar baris */
        color: #495057; /* Warna teks lebih gelap */
    }
    .specification-content th {
         background-color: #f8f9fa; /* Latar header tabel */
         font-weight: 600;
         color: #495057;
    }
    /* Style untuk nilai atribut checkbox */
    .attribute-list {
        list-style: none;
        padding-left: 0;
        margin-bottom: 0;
    }
    .attribute-list li::before {
        content: "✓ "; /* Ceklis */
        color: #198754; /* Warna hijau */
        margin-right: 0.5rem;
    }

</style>
@endpush

@section('content')
<div class="container py-4 py-lg-5"> {{-- Tambah padding atas bawah --}}

    {{-- Breadcrumb (jika perlu) --}}
    <nav aria-label="breadcrumb" class="mb-4">
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('etalase.index') }}">Etalase</a></li>
        @if($product->category)
        <li class="breadcrumb-item"><a href="{{ route('etalase.category.show', $product->category->slug) }}">{{ $product->category->name }}</a></li>
        @endif
        <li class="breadcrumb-item active" aria-current="page">{{ Str::limit($product->name, 50) }}</li>
      </ol>
    </nav>

    <div class="product-detail-card">
        <div class="card-body p-4">
            <div class="row g-4"> {{-- Tambah gap antar kolom --}}
                <!-- Kolom Kiri: Galeri Gambar -->
                <div class="col-lg-5">
                    <div class="product-gallery">
                        <div class="main-image mb-3">
                            {{-- Gunakan kolom 'image' jika 'image_url' tidak ada --}}
                            @php
                                $mainImageUrl = $product->image_url ?? $product->image;
                                $displayImageUrl = $mainImageUrl ? asset('storage/' . $mainImageUrl) : 'https://placehold.co/600x600/e2e8f0/e2e8f0?text=Produk';
                            @endphp
                            <img id="main-product-image" src="{{ $displayImageUrl }}" alt="{{ $product->name }}">
                        </div>
                        {{-- Logika Thumbnail (Contoh) --}}
                        {{-- @php
                            $galleryImages = is_array($product->gallery) ? $product->gallery : ($product->gallery ? json_decode($product->gallery, true) : []);
                        @endphp
                        @if($mainImageUrl || !empty($galleryImages))
                        <div class="thumbnail-list">
                            @if($mainImageUrl)
                            <img src="{{ asset('storage/' . $mainImageUrl) }}" alt="Thumbnail 1" class="active" onclick="changeMainImage(this)">
                            @endif
                            @if(is_array($galleryImages))
                                @foreach($galleryImages as $index => $galleryImage)
                                    @if($loop->index < 4) // Batasi jumlah thumbnail
                                    <img src="{{ asset('storage/' . $galleryImage) }}" alt="Thumbnail {{ $index + 2 }}" onclick="changeMainImage(this)">
                                    @endif
                                @endforeach
                            @endif
                        </div>
                        @endif --}}
                    </div>
                </div>

                <!-- Kolom Kanan: Info Produk & Aksi -->
                <div class="col-lg-7">
                    <div class="product-info">
                        {{-- Label Baru & Bestseller --}}
                        <div class="mb-2">
                            @if ($product->is_new) <span class="badge bg-primary rounded-pill me-1">BARU</span> @endif
                            @if ($product->is_bestseller) <span class="badge bg-danger rounded-pill">BESTSELLER</span> @endif
                        </div>

                        <h1>{{ $product->name }}</h1>

                        <div class="info-meta">
                            <span>Terjual <span class="fw-bold text-dark">{{ $product->sold_count ?? 0 }}+</span></span>
                            <span class="vr"></span>
                            <span class="rating">
                                <i class="bi bi-star-fill"></i> {{ number_format($product->rating ?? 0, 1) }}
                            </span>
                            {{-- Tambahkan jumlah ulasan jika ada --}}
                            {{-- <span class="vr"></span>
                            <span>{{ $product->reviews_count ?? 0 }} Ulasan</span> --}}
                        </div>

                        <div class="price-section my-4">
                             {{-- Tampilkan harga varian jika ada --}}
                             @php
                                $displayPrice = $product->price;
                                $displayOriginalPrice = $product->original_price;
                                // Anda mungkin perlu logika lebih kompleks jika ingin menampilkan rentang harga varian
                                // if($product->productVariants && $product->productVariants->isNotEmpty()){
                                //     $displayPrice = $product->productVariants->min('price');
                                // }
                             @endphp
                            <span class="current-price">Rp{{ number_format($displayPrice, 0, ',', '.') }}</span>
                            @if($displayOriginalPrice && $displayOriginalPrice > $displayPrice)
                                <span class="original-price">Rp{{ number_format($displayOriginalPrice, 0, ',', '.') }}</span>
                                <span class="badge bg-danger ms-2 discount-badge">{{ round((($displayOriginalPrice - $displayPrice) / $displayOriginalPrice) * 100) }}% OFF</span>
                            @endif
                        </div>

                        {{-- Info Penjual --}}
                        <div class="seller-info-card my-4">
                            <img src="{{ $product->seller_logo ? asset('storage/' . $product->seller_logo) : 'https://placehold.co/100x100/e2e8f0/e2e8f0?text=Toko' }}" alt="Logo {{ $product->store_name ?? 'Toko' }}">
                            <div class="seller-details">
                                <div class="fw-bold text-dark">{{ $product->store_name ?? 'Nama Toko' }}</div>
                                <div class="text-muted small"><i class="bi bi-geo-alt-fill me-1"></i>{{ $product->seller_city ?? 'Lokasi Toko' }}</div>
                            </div>
                            @if($product->seller_wa)
                            <div class="seller-actions">
                                <a href="https://wa.me/{{ $product->seller_wa }}" target="_blank" class="btn btn-sm btn-outline-success"><i class="bi bi-whatsapp me-1"></i> Chat Penjual</a>
                            </div>
                            @endif
                        </div>

                        {{-- Form Aksi (Keranjang & Beli) --}}
                        {{-- Logika untuk varian perlu ditambahkan di sini jika produk memiliki varian --}}
                        {{-- Misalnya, pilihan varian sebelum bisa menambah ke keranjang --}}
                        <form action="{{ route('cart.add', $product->slug) }}" method="POST"> {{-- Gunakan slug --}}
                            @csrf
                            <input type="hidden" name="product_slug" value="{{ $product->slug }}"> {{-- Kirim slug --}}

                            <div class="quantity-section">
                                <label for="quantity" class="form-label">Jumlah:</label>
                                <div class="input-group">
                                    <button class="btn btn-outline-secondary btn-qty" type="button" id="button-minus">-</button>
                                    <input type="number" name="quantity" id="quantity" class="form-control form-control-qty" value="1" min="1" max="{{ $product->stock ?? 1 }}">
                                    <button class="btn btn-outline-secondary btn-qty" type="button" id="button-plus">+</button>
                                </div>
                                <span class="stock-info">Stok: {{ $product->stock ?? 0 }}</span>
                            </div>

                            <div class="action-buttons d-flex gap-2">
                                <button type="submit" class="btn btn-outline-danger flex-fill"><i class="bi bi-cart-plus-fill me-2"></i>Tambah ke Keranjang</button>
                                {{-- Ganti route buy_now jika perlu --}}
                                <button type="submit" name="action" value="buy_now" class="btn btn-danger flex-fill">Beli Sekarang</button>
                            </div>
                        </form>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Detail & Spesifikasi Produk -->
    <div class="mt-4">
        <ul class="nav nav-tabs" id="productTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="description-tab" data-bs-toggle="tab" data-bs-target="#description-pane" type="button" role="tab" aria-controls="description-pane" aria-selected="true">Deskripsi</button>
            </li>
            {{-- Hanya tampilkan tab Spesifikasi jika ada atribut --}}
            @if($product->productAttributes && $product->productAttributes->isNotEmpty())
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="specification-tab" data-bs-toggle="tab" data-bs-target="#specification-pane" type="button" role="tab" aria-controls="specification-pane" aria-selected="false">Spesifikasi</button>
            </li>
            @endif
        </ul>
        <div class="tab-content" id="productTabContent">
            {{-- Pane Deskripsi --}}
            <div class="tab-pane fade show active description-content" id="description-pane" role="tabpanel" aria-labelledby="description-tab">
                @if($product->description)
                    {!! nl2br(e($product->description)) !!}
                @else
                    <p class="text-muted">Tidak ada deskripsi untuk produk ini.</p>
                @endif
            </div>

            {{-- Pane Spesifikasi (Dinamis) --}}
            @if($product->productAttributes && $product->productAttributes->isNotEmpty())
            <div class="tab-pane fade specification-content" id="specification-pane" role="tabpanel" aria-labelledby="specification-tab">
                <table class="table table-bordered table-striped">
                    <tbody>
                        {{-- Baris Kategori (Contoh statis, bisa dihapus jika ada di atribut) --}}
                        @if($product->category)
                        <tr>
                            <th scope="row" style="width: 30%;">Kategori</th>
                            <td>{{ $product->category->name }}</td>
                        </tr>
                        @endif
                         {{-- Baris SKU (Contoh statis, bisa dihapus jika ada di atribut) --}}
                        <tr>
                            <th scope="row">SKU</th>
                            <td>{{ $product->sku ?? '-' }}</td>
                        </tr>
                         {{-- Baris Berat (Contoh statis, bisa dihapus jika ada di atribut) --}}
                         <tr>
                            <th scope="row">Berat</th>
                            <td>{{ $product->weight }} gram</td>
                         </tr>

                        {{-- Loop melalui Atribut Produk --}}
                        @foreach($product->productAttributes as $productAttribute)
                            {{-- Pastikan relasi 'attribute' sudah di-load --}}
                            @if($productAttribute->attribute)
                                <tr>
                                    <th scope="row">{{ $productAttribute->attribute->name }}</th>
                                    <td>
                                        @php $value = $productAttribute->value; @endphp
                                        {{-- Cek jika tipe checkbox dan value adalah JSON --}}
                                        @if($productAttribute->attribute->type === 'checkbox' && is_string($value))
                                            @php
                                                try {
                                                    $decodedValue = json_decode($value, true);
                                                    // Pastikan hasil decode adalah array
                                                    $value = is_array($decodedValue) ? $decodedValue : [$value];
                                                } catch (\JsonException $e) {
                                                    $value = [$value]; // Jika gagal decode, anggap string tunggal
                                                }
                                            @endphp
                                            {{-- Tampilkan sebagai list jika array --}}
                                            @if(is_array($value))
                                                <ul class="attribute-list">
                                                    @foreach($value as $item)
                                                        <li>{{ e($item) }}</li>
                                                    @endforeach
                                                </ul>
                                            @else
                                                {{ e($value) }} {{-- Fallback jika bukan array --}}
                                            @endif
                                        @else
                                             {{-- Tampilkan langsung jika bukan checkbox atau bukan JSON --}}
                                            {{ e(is_array($value) ? implode(', ', $value) : $value) }}
                                        @endif
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                         {{-- Baris Status (Contoh statis) --}}
                         <tr>
                            <th scope="row">Status</th>
                            <td><span class="badge bg-{{ $product->status == 'active' ? 'success' : 'secondary' }}">{{ ucfirst($product->status) }}</span></td>
                         </tr>
                    </tbody>
                </table>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
{{-- Load Bootstrap JS (jika belum ada di layout) --}}
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        // --- Script untuk Quantity +/- ---
        const quantityInput = document.getElementById('quantity');
        const minusButton = document.getElementById('button-minus');
        const plusButton = document.getElementById('button-plus');
        const maxStock = parseInt(quantityInput.max) || 1; // Ambil max stock

        if (quantityInput && minusButton && plusButton) {
            minusButton.addEventListener('click', () => {
                let currentValue = parseInt(quantityInput.value);
                if (currentValue > 1) {
                    quantityInput.value = currentValue - 1;
                }
            });

            plusButton.addEventListener('click', () => {
                let currentValue = parseInt(quantityInput.value);
                 // Jangan biarkan melebihi maxStock
                if (currentValue < maxStock) {
                    quantityInput.value = currentValue + 1;
                }
            });

             // Pastikan nilai tidak melebihi stok saat input manual
             quantityInput.addEventListener('change', () => {
                let currentValue = parseInt(quantityInput.value);
                if (isNaN(currentValue) || currentValue < 1) {
                    quantityInput.value = 1;
                } else if (currentValue > maxStock) {
                    quantityInput.value = maxStock;
                }
            });
        }

        // --- Script untuk Galeri Thumbnail (jika ada) ---
        // window.changeMainImage = function(thumbnailElement) {
        //     const mainImage = document.getElementById('main-product-image');
        //     if (mainImage && thumbnailElement) {
        //         mainImage.src = thumbnailElement.src;
        //         // Hapus kelas active dari semua thumbnail
        //         document.querySelectorAll('.thumbnail-list img').forEach(img => img.classList.remove('active'));
        //         // Tambahkan kelas active ke thumbnail yang diklik
        //         thumbnailElement.classList.add('active');
        //     }
        // }
    });
</script>
@endpush
