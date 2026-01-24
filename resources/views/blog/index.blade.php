@extends('layouts.blog')

@section('title', 'Berita & Informasi Terbaru')

@section('content')

{{-- ASSETS FONT --}}
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Serif:wght@400;600;700&family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">

<div class="container py-5">

    {{-- ============================================= --}}
    {{-- LOOPING SEMUA KATEGORI DENGAN DESAIN SAMA --}}
    {{-- ============================================= --}}

    @foreach($categories as $category)
        {{-- Pastikan kategori punya postingan agar tidak kosong --}}
        @if($category->posts()->count() > 0)

            {{-- Panggil Desain SmartMag untuk setiap kategori --}}
            @include('blog.partials.categories_smartmag', ['category' => $category])

            {{-- Pemisah antar kategori --}}
            <div style="border-top: 1px dashed #ddd; margin: 40px 0;"></div>

        @endif
    @endforeach

</div>

@endsection
