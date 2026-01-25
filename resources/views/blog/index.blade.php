@extends('layouts.blog')

@section('title', 'Berita & Informasi Terbaru')

@section('content')

{{-- LOAD GOOGLE FONTS (Wajib agar style sesuai) --}}
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Serif:wght@400;600;700&family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">


{{-- 1. TICKER (RUNNING TEXT) --}}
@include('blog.partials.ticker')

<div class="container py-4">

    {{-- 2. HERO SECTION (BAGIAN ATAS / HEADLINE CAMPURAN) --}}
    {{-- Ini akan menampilkan 1 Berita Besar + 4 Berita List di sampingnya --}}
    @if(isset($latestPosts) && $latestPosts->count() > 0)
        @include('blog.partials.hero_section')
    @endif

    {{-- SPACER PEMBATAS --}}
    <div style="margin-bottom: 60px;"></div>

    {{-- 3. LOOP SEMUA CATEGORY (1 s/d SELESAI) --}}
    {{-- Ini akan meloop semua kategori yang ada di database dan menampilkan postingannya --}}

    @foreach($categories as $category)

        {{-- Hanya tampilkan kategori jika memiliki minimal 1 postingan --}}
        @if($category->posts()->count() > 0)

            {{-- Panggil Desain Partial SmartMag yang sudah kita buat sebelumnya --}}
            @include('blog.partials.categories_smartmag', ['category' => $category])

            {{-- Garis Pembatas Antar Kategori --}}
            @if(!$loop->last) {{-- Jangan tampilkan garis di kategori paling bawah --}}
                <div style="border-top: 1px dashed #ccc; margin: 50px 0;"></div>
            @endif

        @endif

    @endforeach

    {{-- PAGINATION (Jika Kategori dipaginate di Controller) --}}
    @if(method_exists($categories, 'links'))
        <div class="d-flex justify-content-center mt-5">
            {{ $categories->links() }}
        </div>
    @endif

    @include('blog.partials.bottom_grid')

</div>

@endsection
