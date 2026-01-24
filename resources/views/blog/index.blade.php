@extends('layouts.blog')

@section('title', 'Berita & Informasi Terbaru')

@section('content')

{{-- CSS GLOBAL (Font & Reset) --}}
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Serif:wght@400;600;700&family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
<style>
    :root { --c-main: #dd0017; --c-dark: #161616; --c-gray: #777; --c-border: #e8e8e8; --font-heading: 'IBM Plex Serif', serif; --font-body: 'Inter', sans-serif; }
    body { font-family: var(--font-body); color: #333; background: #fff; }
    a { text-decoration: none; color: inherit; transition: 0.2s; } a:hover { color: var(--c-main); }

    /* Global Helpers */
    .block-head { border-bottom: 2px solid var(--c-dark); margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center; }
    .block-head h4 { background: var(--c-dark); color: #fff; padding: 6px 12px; font-size: 14px; text-transform: uppercase; margin: 0; font-family: var(--font-heading); letter-spacing: 0.05em; }
    .block-head-link { font-size: 12px; font-weight: 600; text-transform: uppercase; color: var(--c-gray); }
    .section-separator { margin-top: 60px; margin-bottom: 60px; border-top: 1px dashed #ddd; }
    .widget-title { font-size: 16px; font-family: var(--font-heading); font-weight: 700; border-bottom: 1px solid var(--c-border); padding-bottom: 10px; margin-bottom: 20px; position: relative; }
    .widget-title::after { content: ''; position: absolute; bottom: -1px; left: 0; width: 50px; height: 2px; background: var(--c-main); }
</style>

{{-- Ticker & Hero (Opsional, bisa di-include juga) --}}
@include('blog.partials.ticker')

<div class="container py-4">

    {{-- KATEGORI 1: Desain List Klasik (Misal: Politik/Berita Utama) --}}
    {{-- Kita ambil kategori pertama dari koleksi atau berdasarkan slug --}}
    @if($cat1 = $categories->first())
        @include('blog.partials.categories_1', ['category' => $cat1])
    @endif

    <div class="section-separator"></div>

    {{-- KATEGORI 2: Desain Grid Card (Misal: Teknologi/Lifestyle) --}}
    @if($cat2 = $categories->skip(1)->first())
        @include('blog.partials.categories_2', ['category' => $cat2])
    @endif

    <div class="section-separator"></div>

    {{-- KATEGORI 3: Desain Modern/Overlay (Misal: Olahraga/Hiburan) --}}
    @if($cat3 = $categories->skip(2)->first())
        @include('blog.partials.categories_3', ['category' => $cat3])
    @endif

</div>
@endsection
