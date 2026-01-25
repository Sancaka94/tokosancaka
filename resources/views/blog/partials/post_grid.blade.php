<style>
    .grid-thumb {
        width: 100%; aspect-ratio: 16/9; object-fit: cover;
        border-radius: 4px; margin-bottom: 12px;
        background: #f4f4f4; transition: 0.3s;
    }
    .sub-grid-item:hover .grid-thumb { opacity: 0.8; }
    .sub-grid-title a { text-decoration: none; color: #000; font-weight: 700; font-size: 16px; line-height: 1.4; }
    .sub-grid-title a:hover { color: #dd0017; }
</style>

<div class="row">
    @foreach($latestPosts as $post)
    <div class="col-md-6 mb-4 sub-grid-item">
        <a href="{{ route('blog.posts.show', $post->slug) }}">
            <img src="{{ asset('/storage/' . $post->featured_image) }}"
                 class="grid-thumb"
                 onerror="this.src='https://placehold.co/400x225?text=No+Image'">
        </a>
        <h4 class="sub-grid-title mt-2">
            <a href="{{ route('blog.posts.show', $post->slug) }}">{{ Str::limit($post->title, 60) }}</a>
        </h4>
        <div class="meta-line mb-2" style="font-size: 11px; color: #999;">
            {{ $post->created_at->format('M d, Y') }}
        </div>
        <p class="text-muted small">
            {{ Str::limit(strip_tags($post->content), 90) }}
        </p>
    </div>
    @endforeach
</div>

{{-- PAGINATION SESUAI REQUEST --}}
@if(method_exists($latestPosts, 'links'))
<div class="d-flex justify-content-center mt-5">
    {{ $latestPosts->withQueryString()->links('pagination::bootstrap-5') }}
</div>
@endif
