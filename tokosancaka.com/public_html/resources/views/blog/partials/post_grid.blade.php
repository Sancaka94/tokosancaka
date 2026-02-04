<style>
    /* Import Font Serif seperti di gambar referensi */
    @import url('https://fonts.googleapis.com/css2?family=IBM+Plex+Serif:wght@500;700&family=Inter:wght@400;700&display=swap');

    /* --- STYLE GRID ATAS (4 ITEM) --- */
    .grid-img-wrap {
        position: relative;
        width: 100%;
        padding-top: 100%; /* Membuat rasio 1:1 Aspect Ratio CSS murni */
        overflow: hidden;
        background: #f0f0f0;
        margin-bottom: 12px;
    }
    .grid-img-1-1 {
        position: absolute;
        top: 0; left: 0;
        width: 100%; height: 100%;
        object-fit: cover; /* Gambar full tanpa gepeng */
        transition: transform 0.3s;
    }
    .grid-item:hover .grid-img-1-1 { transform: scale(1.05); }

    .grid-title a {
        font-family: 'IBM Plex Serif', serif;
        font-size: 18px;
        font-weight: 700;
        line-height: 1.3;
        color: #000;
        text-decoration: none;
    }
    .grid-title a:hover { color: #dd0017; }

    .btn-read {
        font-size: 10px; font-weight: 800; text-transform: uppercase;
        letter-spacing: 1px; padding: 6px 12px;
        border: 1px solid #ddd; background: #fff; color: #333;
        text-decoration: none; display: inline-block; margin-top: 10px;
    }
    .btn-read:hover { background: #000; color: #fff; border-color: #000; }

    /* --- STYLE LIST BAWAH (6 ITEM) --- */
    .list-small-item {
        display: flex; gap: 15px;
        padding-bottom: 15px; margin-bottom: 15px;
        border-bottom: 1px solid #eee;
    }
    .list-small-img {
        width: 85px; height: 85px; /* Kotak Kecil */
        object-fit: cover; flex-shrink: 0;
        background: #f0f0f0;
    }
    .list-small-title a {
        font-family: 'IBM Plex Serif', serif;
        font-size: 15px; font-weight: 600; line-height: 1.4;
        color: #000; text-decoration: none;
    }
    .list-small-title a:hover { color: #dd0017; }
</style>

<div class="row">
    {{-- BAGIAN 1: 4 GRID GAMBAR BESAR (1:1) --}}
    @foreach($latestPosts->take(4) as $post)
    <div class="col-md-6 mb-4 grid-item">
        <a href="{{ route('blog.posts.show', $post->slug) }}" class="grid-img-wrap d-block">
            <img src="{{ asset('/storage/' . $post->featured_image) }}" class="grid-img-1-1" onerror="this.src='https://placehold.co/400x400?text=1:1'">
        </a>

        <div class="text-danger small fw-bold text-uppercase mb-1">
            {{ $post->created_at->format('M d, Y') }}
        </div>

        <h3 class="grid-title">
            <a href="{{ route('blog.posts.show', $post->slug) }}">{{ Str::limit($post->title, 55) }}</a>
        </h3>

        <p class="text-muted small mb-0 mt-2 d-none d-md-block">
            {{ Str::limit(strip_tags($post->content), 90) }}
        </p>

        <a href="{{ route('blog.posts.show', $post->slug) }}" class="btn-read">READ MORE</a>
    </div>
    @endforeach
</div>

{{-- GARIS PEMISAH --}}
@if($latestPosts->count() > 4)
<div class="col-12 border-top border-2 border-dark my-4"></div>

{{-- BAGIAN 2: 6 LIST KECIL (THUMBNAIL + JUDUL) --}}
<div class="row">
    @foreach($latestPosts->skip(4)->take(6) as $post)
    <div class="col-md-6">
        <div class="list-small-item">
            <a href="{{ route('blog.posts.show', $post->slug) }}">
                <img src="{{ asset('/storage/' . $post->featured_image) }}" class="list-small-img" onerror="this.src='https://placehold.co/85x85?text=Img'">
            </a>
            <div>
                <h4 class="list-small-title">
                    <a href="{{ route('blog.posts.show', $post->slug) }}">{{ Str::limit($post->title, 50) }}</a>
                </h4>
                <small class="text-muted text-uppercase" style="font-size: 10px;">
                    {{ $post->author->name ?? 'Admin' }} &bull; {{ $post->created_at->format('M d') }}
                </small>
            </div>
        </div>
    </div>
    @endforeach
</div>
@endif

{{-- PAGINATION BOOTSTRAP 5 --}}
@if(method_exists($latestPosts, 'links'))
<div class="d-flex justify-content-center mt-5 pagination-wrapper">
    {{ $latestPosts->withQueryString()->links('pagination::bootstrap-5') }}
</div>
@endif
