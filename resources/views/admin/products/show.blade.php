@extends('layouts.marketplace')

@section('title', $product->name)

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<style>
    body {
        background-color: #f8f9fa;
    }
    .product-detail-card {
        background-color: #fff;
        border-radius: 0.75rem;
        box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
        overflow: hidden;
    }
    .product-gallery .main-image img {
        width: 100%;
        aspect-ratio: 1/1;
        object-fit: cover;
        border-radius: 0.5rem;
        border: 1px solid #dee2e6;
    }
    .product-info h1 {
        font-weight: 700;
        color: #212121;
        font-size: 1.75rem;
    }
    .info-meta {
        display: flex;
        align-items: center;
        gap: 1rem;
        margin: 0.5rem 0 1.5rem 0;
        font-size: 0.9rem;
        color: #6c757d;
    }
    .info-meta .rating {
        color: #FFC107;
        font-weight: 600;
    }
    .price-section .current-price {
        font-size: 2.25rem;
        font-weight: 700;
        color: #dc3545;
    }
    .price-section .original-price {
        font-size: 1.1rem;
        text-decoration: line-through;
        color: #6c757d;
        margin-left: 0.75rem;
    }
     .action-buttons .btn {
        padding: 0.8rem 1.5rem;
        font-weight: 600;
        border-radius: 0.5rem;
    }
    .seller-info-card {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 1rem;
        background-color: #fff;
        border-radius: 0.5rem;
        border: 1px solid #e9ecef;
    }
    .seller-info-card img {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        object-fit: cover;
    }
    .nav-tabs .nav-link {
        color: #6c757d;
        font-weight: 600;
    }
    .nav-tabs .nav-link.active {
        color: #dc3545;
        border-color: #dc3545 #dc3545 #fff;
    }
    .description-content, .specification-content {
        padding: 1.5rem;
        background-color: #fff;
        border: 1px solid #dee2e6;
        border-top: none;
        border-radius: 0 0 0.5rem 0.5rem;
    }
</style>
@endpush

@section('content')
<div class="container py-5">
    <div class="product-detail-card">
        <div class="card-body p-4">
            <div class="row">
                <!-- Kolom Kiri: Galeri Gambar -->
                <div class="col-lg-5">
                    <div class="product-gallery">
                        <div class="main-image mb-3">
                             <img src="{{ $product->image_url ? url('storage/' . $product->image_url) : 'https://placehold.co/600x600/e2e8f0/e2e8f0?text=Produk' }}" alt="{{ $product->name }}">
                        </div>
                        <!-- Tambahkan thumbnail di sini jika ada -->
                    </div>
                </div>

                <!-- Kolom Kanan: Info Produk & Aksi -->
                <div class="col-lg-7">
                    <div class="product-info">
                        <div>
                            @if ($product->is_new) <span class="badge bg-primary">BARU</span> @endif
                            @if ($product->is_bestseller) <span class="badge bg-danger">BESTSELLER</span> @endif
                        </div>
                        <h1 class="mt-2 mb-1">{{ $product->name }}</h1>
                        <div class="info-meta">
                            <span>Terjual <span class="fw-bold text-dark">{{ $product->sold_count ?? 0 }}+</span></span>
                            <span class="vr"></span>
                            <span class="rating">
                                <i class="bi bi-star-fill"></i> {{ number_format($product->rating, 1) }}
                            </span>
                        </div>
                        
                        <div class="price-section my-4">
                            <span class="current-price">Rp{{ number_format($product->price, 0, ',', '.') }}</span>
                            @if($product->original_price && $product->original_price > $product->price)
                                <span class="original-price">Rp{{ number_format($product->original_price, 0, ',', '.') }}</span>
                                <span class="badge bg-danger ms-2">{{ round((($product->original_price - $product->price) / $product->original_price) * 100) }}% OFF</span>
                            @endif
                        </div>
                        
                        <div class="seller-info-card my-4">
                            <img src="{{ $product->seller_logo ? url('storage/' . $product->seller_logo) : 'https://placehold.co/100x100/e2e8f0/e2e8f0?text=Toko' }}" alt="Logo Toko">
                            <div>
                                <div class="fw-bold">{{ $product->store_name ?? 'Nama Toko' }}</div>
                                <div class="text-muted small">{{ $product->seller_city ?? 'Lokasi Toko' }}</div>
                            </div>
                            <div class="ms-auto">
                                <a href="https://wa.me/{{ $product->seller_wa }}" target="_blank" class="btn btn-sm btn-outline-success"><i class="bi bi-whatsapp"></i> Chat Penjual</a>
                            </div>
                        </div>

                        <form action="{{ route('cart.add', $product->id) }}" method="POST">
                            @csrf
                            <div class="d-flex align-items-center gap-3 mb-4">
                                <label for="quantity" class="form-label mb-0">Jumlah:</label>
                                <input type="number" name="quantity" id="quantity" class="form-control" value="1" min="1" max="{{ $product->stock }}" style="width: 80px;">
                                <span class="text-muted small">Stok: {{ $product->stock }}</span>
                            </div>
                            <div class="action-buttons d-flex gap-2">
                                <button type="submit" class="btn btn-outline-danger flex-fill"><i class="bi bi-cart-plus-fill me-2"></i>Tambah ke Keranjang</button>
                                <button type="submit" formaction="{{ route('cart.buy_now', $product->id) }}" class="btn btn-danger flex-fill">Beli Sekarang</button>
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
                <button class="nav-link active" id="description-tab" data-bs-toggle="tab" data-bs-target="#description" type="button" role="tab">Deskripsi</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="specification-tab" data-bs-toggle="tab" data-bs-target="#specification" type="button" role="tab">Spesifikasi</button>
            </li>
        </ul>
        <div class="tab-content" id="productTabContent">
            <div class="tab-pane fade show active description-content" id="description" role="tabpanel">
                {!! nl2br(e($product->description)) !!}
            </div>
            <div class="tab-pane fade specification-content" id="specification" role="tabpanel">
                <table class="table table-bordered">
                    <tbody>
                        <tr>
                            <th scope="row" style="width: 30%;">Kategori</th>
                            <td>{{ $product->category }}</td>
                        </tr>
                        <tr>
                            <th scope="row">SKU</th>
                            <td>{{ $product->sku ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th scope="row">Berat</th>
                            <td>{{ $product->weight }} gram</td>
                        </tr>
                        <tr>
                            <th scope="row">Status</th>
                            <td><span class="badge bg-{{ $product->status == 'active' ? 'success' : 'secondary' }}">{{ ucfirst($product->status) }}</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
