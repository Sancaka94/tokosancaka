{{--
    FILE: resources/views/blog/partials/ticker.blade.php
    FITUR: FORCE STICKY & REMOVE GAP
--}}

<style>
    /* Wrapper Utama untuk memaksa Sticky dan Full Width */
    .ticker-sticky-wrapper {
    position: -webkit-sticky;
    position: sticky;
    top: 65px;
    z-index: 999;

    /* TARIK KE ATAS UNTUK MENGHILANGKAN CELAH */
    margin-top: -65px; /* Sesuaikan angka ini (-1px s/d -10px) sampai rapat */

    width: 100vw;
    margin-left: calc(50% - 50vw);
    margin-right: calc(50% - 50vw);
    background: #161616;
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);

    }

    .ticker-inner {
        max-width: 1320px; /* Sesuaikan dengan container website Anda */
        margin: 0 auto;
        padding: 0;
        display: flex;
        align-items: stretch; /* Agar tinggi sama rata */
        height: 46px; /* Tinggi Bar Ticker */
    }

    /* LABEL MERAH (Gaya Miring/Skew) */
    .ticker-label {
        background-color: #dd0017;
        color: #fff;
        text-transform: uppercase;
        font-family: 'Inter', sans-serif;
        font-weight: 800;
        font-size: 12px;
        letter-spacing: 1px;
        padding: 0 25px;
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
        z-index: 2;
        /* Membuat efek miring di sisi kanan */
        clip-path: polygon(0 0, 100% 0, 90% 100%, 0% 100%);
        min-width: 140px;
    }

    .ticker-label i { margin-right: 8px; font-size: 13px; }

    /* KONTEN TEKS BERJALAN */
    .ticker-content {
        flex-grow: 1;
        overflow: hidden;
        white-space: nowrap;
        display: flex;
        align-items: center;
        background-color: #161616;
        border-bottom: 2px solid #dd0017; /* Garis merah tipis di bawah sepanjang bar */
    }

    .ticker-item {
        color: #e0e0e0;
        text-decoration: none;
        margin-right: 40px;
        font-family: 'Inter', sans-serif;
        font-weight: 500;
        font-size: 13px;
        display: inline-flex;
        align-items: center;
        transition: 0.2s;
        line-height: 46px;
    }
    .ticker-item:hover { color: #fff; text-decoration: underline; }

    /* Bullet Point Merah antar berita */
    .ticker-item::before {
        content: '\2022'; /* Bullet Entity */
        color: #dd0017;
        font-size: 20px;
        margin-right: 10px;
        line-height: 0;
        position: relative;
        top: 1px;
    }
</style>

@if(isset($latestPosts) && $latestPosts->count() > 0)
<div class="ticker-sticky-wrapper">
    <div class="ticker-inner">

        {{-- LABEL MERAH --}}
        <div class="ticker-label">
            <i class="fas fa-bolt"></i> TRENDING
        </div>

        {{-- TEKS BERJALAN --}}
        <div class="ticker-content">
            <marquee behavior="scroll" direction="left" scrollamount="6" onmouseover="this.stop();" onmouseout="this.start();">
                @foreach($latestPosts->take(5) as $post)
                    <a href="{{ route('blog.posts.show', $post->slug) }}" class="ticker-item">
                        {{ $post->title }}
                    </a>
                @endforeach
            </marquee>
        </div>

    </div>
</div>
@endif
