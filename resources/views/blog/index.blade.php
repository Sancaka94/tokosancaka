@extends('layouts.blog')

@section('title', 'Berita & Informasi Terbaru')

@section('content')

{{-- FONT ASSETS --}}
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Serif:wght@400;600;700&family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">

{{-- 1. TICKER --}}
@include('blog.partials.ticker')

<div class="container py-5">

    {{-- 2. KATEGORI UTAMA (Desain Grid Kiri + Sidebar) --}}
    @foreach($categories->take(1) as $category)
        @include('blog.partials.categories_smartmag', ['category' => $category])
    @endforeach

    {{-- SPACER --}}
    <div style="border-top: 1px dashed #ddd; margin: 50px 0;"></div>

    {{-- 3. BOTTOM GRID 4 KOLOM (Desain Travel, UK, Science, Economy) --}}
    {{-- Ini akan menampilkan 4 kategori berikutnya secara berjajar --}}
    @include('blog.partials.bottom_grid')

</div>

@endsection

{{-- 4. FOOTER (Sebaiknya letakkan kode ini di file layouts/blog.blade.php menggantikan footer lama) --}}
{{-- Tapi jika ingin dipanggil di sini juga bisa, pastikan diluar container content utama --}}
@section('footer')
    @include('blog.partials.footer_smartmag')
@endsection
