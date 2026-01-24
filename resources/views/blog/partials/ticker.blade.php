{{--
    FILE: resources/views/blog/partials/ticker.blade.php
--}}

<style>
    .trending-ticker-wrap {
        background-color: #161616; /* Warna Hitam Pekat */
        color: #fff;
        font-family: 'Inter', sans-serif;
        font-size: 13px;
        border-bottom: 1px solid #333;
        overflow: hidden;
    }

    .ticker-container {
        display: flex;
        align-items: center;
        height: 40px; /* Tinggi bar */
    }

    .ticker-label {
        background-color: #dd0017; /* Merah SmartMag */
        color: #fff;
        text-transform: uppercase;
        font-weight: 800;
        font-size: 11px;
        letter-spacing: 0.5px;
        padding: 0 15px;
        height: 100%;
        display: flex;
        align-items: center;
        margin-right: 15px;
        white-space: nowrap;
        position: relative;
        z-index: 2;
    }

    /* Panah kecil di sebelah label merah (opsional, pemanis) */
    .ticker-label::after {
        content: '';
        position: absolute;
        right: -10px;
        top: 0;
        border-top: 40px solid #dd0017; /* Sesuai tinggi bar */
        border-right: 10px solid transparent;
    }

    .ticker-content {
        flex-grow: 1;
        overflow: hidden;
        white-space: nowrap;
        position: relative;
    }

    .ticker-item {
        color: #e0e0e0;
        text-decoration: none;
        margin-right: 30px;
        transition: color 0.2s;
        font-weight: 500;
    }
    .ticker-item:hover {
        color: #fff;
        text-decoration: underline;
    }

    .ticker-item i {
        font-size: 10px;
        margin-right: 5px;
        color: #dd0017;
    }
</style>

@if(isset($latestPosts) && $latestPosts->count() > 0)
<div class="trending-ticker-wrap">
    <div class="container">
        <div class="ticker-container">
            <div class="ticker-label">
                <i class="fas fa-bolt me-2"></i> Trending
            </div>

            {{-- Menggunakan Marquee HTML sederhana agar ringan --}}
            <div class="ticker-content">
                <marquee behavior="scroll" direction="left" scrollamount="6" onmouseover="this.stop();" onmouseout="this.start();">
                    @foreach($latestPosts->take(5) as $post)
                        <a href="{{ route('blog.posts.show', $post->slug) }}" class="ticker-item">
                            <i class="fas fa-chevron-right"></i> {{ $post->title }}
                        </a>
                    @endforeach
                </marquee>
            </div>
        </div>
    </div>
</div>
@endif
