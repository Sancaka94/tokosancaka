<style>
    /* Gambar Grid 1:1 */
    .thumb-1-1 {
        width: 100%; aspect-ratio: 1 / 1;
        object-fit: cover; border-radius: 2px;
        background: #f4f4f4; margin-bottom: 10px;
    }
    .grid-main-title a {
        text-decoration: none; color: #000; font-weight: 700; font-size: 17px; line-height: 1.3;
    }
    .grid-main-title a:hover { color: #dd0017; }

    /* List Judul Kecil (Tanpa Gambar) */
    .small-list-item {
        padding: 10px 0; border-bottom: 1px solid #f0f0f0;
    }
    .small-list-title { font-size: 14px; font-weight: 600; margin-bottom: 3px; }
    .small-list-title a { text-decoration: none; color: #333; transition: 0.2s; }
    .small-list-title a:hover { color: #dd0017; }
    .small-meta { font-size: 10px; color: #999; text-transform: uppercase; letter-spacing: 0.5px; }
</style>

<div class="row">
    {{-- BAGIAN 1: 4 KONTEN GAMBAR GRID 1:1 --}}
    @foreach($latestPosts->take(4) as $post)
    <div class="col-md-6 mb-4">
        <a href="{{ route('blog.posts.show', $post->slug) }}">
            <img src="{{ asset('/storage/' . $post->featured_image) }}" class="thumb-1-1" onerror="this.src='https://placehold.co/400x400?text=1:1'">
        </a>
        <h4 class="grid-main-title">
            <a href="{{ route('blog.posts.show', $post->slug) }}">{{ Str::limit($post->title, 55) }}</a>
        </h4>
        <p class="text-muted small mt-2">{{ Str::limit(strip_tags($post->content), 80) }}</p>
    </div>
    @endforeach

    <div class="col-12 mt-2 mb-4"><hr></div>

    {{-- BAGIAN 2: 6 KONTEN JUDUL SAJA (KECIL) --}}
    <div class="row">
        @foreach($latestPosts->skip(4)->take(6) as $post)
        <div class="col-md-6 small-list-item">
            <h5 class="small-list-title">
                <a href="{{ route('blog.posts.show', $post->slug) }}">{{ Str::limit($post->title, 65) }}</a>
            </h5>
            <div class="small-meta">
                <span>{{ $post->author->name ?? 'Admin' }}</span> •
                <span>{{ $post->created_at->format('d M Y') }}</span>
            </div>
        </div>
        @endforeach
    </div>
</div>

{{-- PAGINATION BOOTSTRAP 5 --}}
@if(method_exists($latestPosts, 'links'))
<div class="d-flex justify-content-center mt-5">
    {{ $latestPosts->withQueryString()->links('pagination::bootstrap-5') }}
</div>
@endif
