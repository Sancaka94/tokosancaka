{{-- FILE: resources/views/blog/partials/content_grid.blade.php --}}

<style>
    /* CSS Khusus Grid 4 & List 6 */
    .thumb-square {
        width: 100%; aspect-ratio: 1/1; object-fit: cover;
        margin-bottom: 15px; background: #eee;
    }
    .grid-title a {
        text-decoration: none; color: #000; font-weight: 700; font-size: 18px;
        font-family: 'IBM Plex Serif', serif; line-height: 1.3;
    }
    .grid-title a:hover { color: #dd0017; }

    .list-item {
        display: flex; gap: 15px; border-bottom: 1px solid #f0f0f0; padding-bottom: 15px; margin-bottom: 15px;
    }
    .thumb-small {
        width: 80px; height: 80px; object-fit: cover; flex-shrink: 0; background: #eee;
    }
    .list-title a {
        text-decoration: none; color: #333; font-weight: 600; font-size: 15px;
        font-family: 'IBM Plex Serif', serif;
    }
    .list-title a:hover { color: #dd0017; }
</style>

<div class="row">
    {{-- BAGIAN ATAS: 4 Grid (Gambar 1:1) --}}
    @foreach($posts->take(4) as $post)
    <div class="col-md-6 mb-4">
        <a href="{{ route('blog.posts.show', $post->slug) }}">
            <img src="{{ asset('storage/' . $post->featured_image) }}" class="thumb-square">
        </a>
        <div class="text-danger fw-bold small mb-2">{{ $post->created_at->format('M d, Y') }}</div>
        <h3 class="grid-title">
            <a href="{{ route('blog.posts.show', $post->slug) }}">{{ Str::limit($post->title, 55) }}</a>
        </h3>
        <p class="text-muted small mt-2">{{ Str::limit(strip_tags($post->content), 80) }}</p>
        <a href="{{ route('blog.posts.show', $post->slug) }}" class="btn btn-sm btn-outline-dark rounded-0" style="font-size: 10px; font-weight: 700;">READ MORE</a>
    </div>
    @endforeach
</div>

@if($posts->count() > 4)
<hr class="my-4">
{{-- BAGIAN BAWAH: 6 List (Gambar Kecil di Kiri) --}}
<div class="row">
    @foreach($posts->skip(4)->take(6) as $post)
    <div class="col-md-6">
        <div class="list-item">
            <a href="{{ route('blog.posts.show', $post->slug) }}">
                <img src="{{ asset('storage/' . $post->featured_image) }}" class="thumb-small">
            </a>
            <div>
                <h4 class="list-title">
                    <a href="{{ route('blog.posts.show', $post->slug) }}">{{ Str::limit($post->title, 50) }}</a>
                </h4>
                <small class="text-muted text-uppercase" style="font-size: 10px;">{{ $post->created_at->format('M d, Y') }}</small>
            </div>
        </div>
    </div>
    @endforeach
</div>
@endif

{{-- PAGINATION --}}
<div class="d-flex justify-content-center mt-5 pagination-wrapper">
    {{ $posts->links('pagination::bootstrap-5') }}
</div>
