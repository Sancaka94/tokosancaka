<style>
    /* DESAIN: SMARTMAG FOCUS LAYOUT
       Note: LOG LOG tetap dipertahankan
    */

    /* --- Base Styles & Reset --- */
    .smart-head-cat {
        border-top: 2px solid #000;
        margin-bottom: 20px;
        padding-top: 10px;
    }

    .smart-head-cat h4 {
        font-family: 'Inter', sans-serif;
        font-weight: 800;
        text-transform: uppercase;
        font-size: 14px;
        letter-spacing: 0.5px;
        margin: 0;
        color: #ff0000 !important;
    }

    /* --- Link Management --- */
    .main-feat-title a,
    .sub-grid-title a,
    .side-list-title a {
        text-decoration: none !important;
        color: #000000 !important;
        display: block;
        border: none !important;
        box-shadow: none !important;
        transition: color 0.2s ease-in-out;
    }

    .main-feat-title a:hover,
    .sub-grid-title a:hover,
    .side-list-title a:hover {
        color: #dd0017 !important;
    }

    /* --- Typography & Meta --- */
    .meta-line {
        font-size: 11px;
        color: #888;
        font-family: 'Inter', sans-serif;
        text-transform: uppercase;
        margin-bottom: 15px;
    }

    .cat-text {
        color: #dd0017;
        font-weight: 700;
        margin-right: 5px;
    }

    /* --- 1. Main Featured Post --- */
    .main-feat-box {
        display: flex;
        gap: 20px;
        margin-bottom: 30px;
        align-items: flex-start;
    }

    .main-feat-content { flex: 1; }

    .main-feat-img-wrap {
        flex: 1.3;
        aspect-ratio: 1 / 1;
        overflow: hidden;
        border-radius: 2px;
        background: #f4f4f4;
    }

    .main-feat-img {
        width: 100%;
        height: 100%;
        object-fit: contain;
        transition: transform 0.4s;
    }

    .main-feat-box:hover .main-feat-img { transform: scale(1.05); }

    .main-feat-title {
        font-family: 'IBM Plex Serif', serif;
        font-size: 28px;
        font-weight: 700;
        line-height: 1.2;
        margin-bottom: 10px;
    }

    .feat-excerpt {
        font-family: 'Inter', sans-serif;
        font-size: 14px;
        color: #555;
        line-height: 1.6;
        margin-bottom: 20px;
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .btn-read-more {
        display: inline-block;
        padding: 8px 20px;
        border: 1px solid #ddd;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        color: #333;
        background: #fff;
        text-decoration: none;
        transition: 0.2s;
    }

    .btn-read-more:hover {
        border-color: #000;
        background: #000;
        color: #fff;
    }

    /* --- 2. Sub Grid --- */
    .sub-grid-item {
        border-right: 1px solid #eee;
        padding-right: 15px;
        margin-bottom: 20px;
    }

    .sub-grid-item:last-child { border-right: none; }

    .sub-grid-title {
        font-family: 'IBM Plex Serif', serif;
        font-size: 17px;
        font-weight: 700;
        line-height: 1.3;
        margin-bottom: 8px;
    }

    .sub-grid-excerpt {
        font-size: 13px;
        color: #666;
        line-height: 1.5;
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    /* --- 3. Sidebar List --- */
    .side-list-item {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        padding-bottom: 15px;
        margin-bottom: 15px;
        border-bottom: 1px solid #f0f0f0;
    }

    .side-list-item:last-child { border-bottom: none; }

    .side-list-content { padding-right: 10px; flex: 1; }

    .side-list-title {
        font-family: 'IBM Plex Serif', serif;
        font-size: 15px;
        font-weight: 600;
        line-height: 1.4;
        margin-bottom: 5px;
    }

    .side-list-img {
        width: 90px;
        height: 90px;
        object-fit: contain;
        background: #f4f4f4;
        border-radius: 2px;
        flex-shrink: 0;
        border: 1px solid #eee;
    }

    /* --- Responsive --- */
    @media(max-width: 991px) {
        .main-feat-box { flex-direction: column-reverse; }
        .main-feat-img-wrap { width: 100%; aspect-ratio: 16/9; }
        .sub-grid-item { border-right: none; border-bottom: 1px solid #eee; padding-bottom: 15px; }
        .border-start-lg { border-left: none !important; padding-left: 0 !important; }
    }
</style>

@php
    // LOG LOG: Pengambilan data terpusat untuk efisiensi
    $allPosts = $category->posts()->latest()->take(8)->get();
    $mainPost = $allPosts->first();
    $subPosts = $allPosts->slice(1, 3);
    $sidePosts = $allPosts->slice(4, 4);
@endphp

<div class="row mb-5">
    {{-- Header Category --}}
    <div class="col-12">
        <div class="smart-head-cat">
            <h4>{{ $category->name }}</h4>
        </div>
    </div>

    {{-- KOLOM KIRI (MAIN CONTENT) --}}
    <div class="col-lg-8">
        {{-- 1. FEATURED POST --}}
        @if($mainPost)
        <div class="main-feat-box">
            <div class="main-feat-content">
                <h3 class="main-feat-title">
                    <a href="{{ route('blog.posts.show', $mainPost->slug) }}">{{ $mainPost->title }}</a>
                </h3>
                <div class="meta-line">
                    <span class="cat-text">{{ $category->name }}</span>
                    <span class="text-muted">&mdash; {{ $mainPost->created_at->format('M d, Y') }}</span>
                </div>
                <p class="feat-excerpt">
                    {{ Str::limit(strip_tags($mainPost->content), 150) }}
                </p>
                <a href="{{ route('blog.posts.show', $mainPost->slug) }}" class="btn-read-more">Read More</a>
            </div>

            <div class="main-feat-img-wrap">
                <a href="{{ route('blog.posts.show', $mainPost->slug) }}">
                    <img src="{{ asset('/storage/' . $mainPost->featured_image) }}"
                         class="main-feat-img"
                         alt="{{ $mainPost->title }}"
                         onerror="this.onerror=null;this.src='https://placehold.co/600x600/eee/999?text=Image';">
                </a>
            </div>
        </div>
        @endif

        {{-- 2. SUB GRID --}}
        <div class="row">
            @foreach($subPosts as $subPost)
            <div class="col-md-4 sub-grid-item">
                <h4 class="sub-grid-title">
                    <a href="{{ route('blog.posts.show', $subPost->slug) }}">{{ Str::limit($subPost->title, 45) }}</a>
                </h4>
                <div class="meta-line mb-2">
                    <span class="text-muted">{{ $subPost->created_at->format('M d, Y') }}</span>
                </div>
                <p class="sub-grid-excerpt">
                    {{ Str::limit(strip_tags($subPost->content), 80) }}
                </p>
            </div>
            @endforeach
        </div>
    </div>

    {{-- KOLOM KANAN (SIDEBAR LIST) --}}
    <div class="col-lg-4 border-start ps-lg-4">
        @foreach($sidePosts as $sidePost)
        <article class="side-list-item">
            <div class="side-list-content">
                <h5 class="side-list-title">
                    <a href="{{ route('blog.posts.show', $sidePost->slug) }}">{{ Str::limit($sidePost->title, 50) }}</a>
                </h5>
                <div class="meta-line mb-0" style="font-size: 10px;">
                    {{ $sidePost->created_at->format('M d, Y') }}
                </div>
            </div>
            <a href="{{ route('blog.posts.show', $sidePost->slug) }}">
                <img src="{{ asset('/storage/' . $sidePost->featured_image) }}"
                     class="side-list-img"
                     alt="thumb"
                     onerror="this.onerror=null;this.src='https://placehold.co/100x100/eee/999?text=Img';">
            </a>
        </article>
        @endforeach
    </div>
</div>
