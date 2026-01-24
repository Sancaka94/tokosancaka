{{--
    FILE: resources/views/blog/partials/ticker.blade.php
    FITUR: FULL WIDTH, STICKY, & NEMPEL HEADER
--}}

<style>
    .trending-ticker-wrapper-full {
        /* 1. Agar Sticky / Menempel saat scroll */
        position: sticky;
        top: 0; /* Sesuaikan jika Header Anda juga sticky (misal: top: 70px;) */
        z-index: 999;

        /* 2. Warna & Border */
        background-color: #161616;
        border-bottom: 1px solid #333;
        color: #fff;
        font-family: 'Inter', sans-serif;
        font-size: 13px;
        height: 44px; /* Tinggi Bar Tetap */
        overflow: hidden;

        /* 3. TEKNIK NEMPEL & FULL WIDTH (Breakout Container) */
        /* Ini memaksa ticker melebar 100% layar meski di dalam container */
        width: 100vw;
        margin-left: calc(50% - 50vw);
        margin-right: calc(50% - 50vw);

        /* 4. HILANGKAN JARAK (Negative Margin) */
        /* Tarik ke atas untuk menutup celah putih */
        margin-top: -24px; /* Sesuaikan nilai ini jika masih ada celah! */
        margin-bottom: 30px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    }

    .ticker-inner {
        /* Mengembalikan konten ke tengah (Container) */
        max-width: 1320px; /* Sesuai container-xxl Bootstrap */
        margin: 0 auto;
        padding: 0 12px;
        display: flex;
        align-items: center;
        height: 100%;
    }

    /* LABEL MERAH (Sancaka Style) */
    .ticker-label {
        background-color: #dd0017;
        color: #fff;
        text-transform: uppercase;
        font-weight: 800;
        font-size: 11px;
        letter-spacing: 1px;
        padding: 0 20px;
        height: 100%;
        display: flex;
        align-items: center;
        position: relative;
        margin-right: 20px;
        clip-path: polygon(0 0, 100% 0, 90% 100%, 0% 100%); /* Efek miring kanan */
    }

    .ticker-label i { margin-right: 8px; font-size: 12px; }

    /* RUNNING TEXT AREA */
    .ticker-content {
        flex-grow: 1;
        overflow: hidden;
        white-space: nowrap;
        display: flex;
        align-items: center;
    }

    .ticker-item {
        color: #ddd;
        text-decoration: none;
        margin-right: 40px;
        font-weight: 500;
        font-size: 13px;
        display: inline-flex;
        align-items: center;
        transition: 0.2s;
    }
    .ticker-item:hover { color: #fff; text-decoration: none; }
    .ticker-item::before {
        content: '\2022'; /* Bullet point */
        color: #dd0017;
        font-size: 18px;
        margin-right: 10px;
        line-height: 0;
    }
</style>

@if(isset($latestPosts) && $latestPosts->count() > 0)
<div class="trending-ticker-wrapper-full">
    <div class="ticker-inner">

        {{-- LABEL --}}
        <div class="ticker-label">
            <i class="fas fa-bolt"></i> TRENDING
        </div>

        {{-- MARQUEE --}}
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
