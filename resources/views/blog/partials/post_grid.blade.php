{{-- resources/views/blog/partials/post_grid.blade.php --}}
@php $mainPost = $posts->first(); @endphp

@if($mainPost)
<div class="main-feat-box">
    <div class="main-feat-content">
        <h3 class="main-feat-title">
            <a href="{{ route('blog.posts.show', $mainPost->slug) }}">{{ $mainPost->title }}</a>
        </h3>
        <p class="text-muted small">— {{ $mainPost->created_at->format('M d, Y') }}</p>
        <p class="feat-excerpt text-muted small">{{ Str::limit(strip_tags($mainPost->content), 150) }}</p>
        <a href="{{ route('blog.posts.show', $mainPost->slug) }}" class="btn-read-more">Read More</a>
    </div>
    <div class="main-feat-img-wrap">
        <img src="{{ asset('/storage/' . $mainPost->featured_image) }}" class="main-feat-img" onerror="this.src='https://placehold.co/400x400?text=No+Image'">
    </div>
</div>
@endif

<div class="row">
    @foreach($posts->skip(1) as $post)
    <div class="col-md-4 sub-grid-item">
        <h4 class="sub-grid-title">
            <a href="{{ route('blog.posts.show', $post->slug) }}">{{ Str::limit($post->title, 45) }}</a>
        </h4>
        <p class="small text-muted">{{ Str::limit(strip_tags($post->content), 60) }}</p>
    </div>
    @endforeach
</div>

<div class="custom-pagination mt-4">
    {{ $posts->links() }}
</div>
