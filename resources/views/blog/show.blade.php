@extends('layouts.blog')

{{-- Mengatur judul halaman secara dinamis berdasarkan judul postingan --}}
@section('title', $post->title ?? 'Detail Berita')

@section('content')
<div class="container py-5">
    <div class="row g-5">
        {{-- Kolom Konten Utama (Kiri) --}}
        <div class="col-lg-8">
            <article>
                <!-- Header Artikel -->
                <header class="mb-4">
                    <!-- Judul Postingan -->
                    <h1 class="fw-bolder mb-1">{{ $post->title ?? 'Judul Tidak Ditemukan' }}</h1>
                    <!-- Meta Info: Tanggal, Kategori, Penulis -->
                    <div class="text-muted fst-italic mb-2">
                        Diposting pada {{ $post->created_at?->format('d F Y') ?? 'Tanggal tidak diketahui' }} oleh {{ $post->author?->name ?? 'Penulis' }}
                    </div>
                    <!-- Kategori -->
                    <a class="badge bg-primary text-decoration-none link-light" href="#!">{{ $post->category?->name ?? 'Umum' }}</a>
                </header>
                
                <!-- Gambar Utama (Featured Image) -->
                <figure class="mb-4">
                    <img class="img-fluid rounded" src="{{ $post->featured_image ? Storage::url($post->featured_image) : 'https://placehold.co/900x400/6c757d/ffffff?text=Gambar+Postingan' }}" alt="{{ $post->title ?? 'Gambar' }}">
                </figure>
                
                <!-- Konten Artikel -->
                <section class="mb-5 fs-5">
                    {{-- Menggunakan {!! !!} untuk merender HTML dari editor teks --}}
                    {!! $post->content ?? '<p>Konten tidak tersedia.</p>' !!}
                </section>
            </article>
        </div>

        {{-- Kolom Sidebar (Kanan) --}}
        <aside class="col-lg-4">
            {{-- Memanggil sidebar yang sudah ada --}}
            @include('layouts.partials.blog.sidebar')
        </aside>
    </div>
</div>
@endsection
