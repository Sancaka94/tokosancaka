<style>
    /* --- Category Header --- */
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
        color: #ff0000 !important;
        margin: 0;
    }

    /* --- Universal Title & Links --- */
    .main-feat-title a, .sub-grid-title a, .side-list-title a {
        text-decoration: none !important;
        color: #000 !important;
        transition: color 0.2s;
    }
    .main-feat-title a:hover, .sub-grid-title a:hover, .side-list-title a:hover {
        color: #dd0017 !important;
    }

    /* --- Sidebar Scroll Container --- */
    #sidebar-scroll-wrapper {
        max-height: 700px;
        overflow-y: auto;
        padding-right: 10px;
        position: relative;
    }

    /* Scrollbar Styling */
    #sidebar-scroll-wrapper::-webkit-scrollbar { width: 4px; }
    #sidebar-scroll-wrapper::-webkit-scrollbar-track { background: #f9f9f9; }
    #sidebar-scroll-wrapper::-webkit-scrollbar-thumb { background: #ccc; border-radius: 10px; }

    .side-list-item {
        display: flex;
        justify-content: space-between;
        padding-bottom: 15px;
        margin-bottom: 15px;
        border-bottom: 1px solid #f0f0f0;
    }

    .side-list-img {
        width: 80px; height: 80px;
        object-fit: cover; background: #f4f4f4;
        border-radius: 2px; flex-shrink: 0;
    }

    /* --- AJAX Pagination Styling --- */
    .load-more-container {
        padding: 10px 0;
        text-align: center;
    }
    #btn-load-more {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        color: #888;
        background: none;
        border: 1px solid #eee;
        padding: 5px 15px;
        cursor: pointer;
        transition: 0.3s;
        width: 100%;
    }
    #btn-load-more:hover {
        color: #000;
        border-color: #000;
    }
    .loading-spinner { display: none; font-size: 12px; color: #999; }

    /* --- Layout Rest (Existing) --- */
    .main-feat-box { display: flex; gap: 20px; margin-bottom: 30px; }
    .main-feat-img-wrap { flex: 1.3; aspect-ratio: 1/1; background: #f4f4f4; overflow: hidden; }
    .main-feat-img { width: 100%; height: 100%; object-fit: contain; }
    .sub-grid-item { border-right: 1px solid #eee; padding-right: 15px; }
</style>

@php
    // LOG LOG: Pengambilan data awal
    $postsQuery = $category->posts()->latest();
    $allPosts = $postsQuery->limit(14)->get(); // 1 (Main) + 3 (Sub) + 10 (Side)

    $mainPost = $allPosts->first();
    $subPosts = $allPosts->slice(1, 3);
    $sidePosts = $allPosts->slice(4); // Ambil 10 data awal untuk sidebar
@endphp

<div class="row mb-5">
    <div class="col-12">
        <div class="smart-head-cat"><h4>{{ $category->name }}</h4></div>
    </div>

    {{-- KIRI --}}
    <div class="col-lg-8">
        @if($mainPost)
        <div class="main-feat-box">
            <div class="main-feat-content">
                <h3 class="main-feat-title">
                    <a href="{{ route('blog.posts.show', $mainPost->slug) }}">{{ $mainPost->title }}</a>
                </h3>
                <p class="text-muted small">{{ $mainPost->created_at->format('M d, Y') }}</p>
                <p class="feat-excerpt">{{ Str::limit(strip_tags($mainPost->content), 150) }}</p>
                <a href="{{ route('blog.posts.show', $mainPost->slug) }}" class="btn-read-more">Read More</a>
            </div>
            <div class="main-feat-img-wrap">
                <img src="{{ asset('/storage/' . $mainPost->featured_image) }}" class="main-feat-img" onerror="this.src='https://placehold.co/400x400?text=No+Image'">
            </div>
        </div>
        @endif

        <div class="row">
            @foreach($subPosts as $subPost)
            <div class="col-md-4 sub-grid-item">
                <h4 class="sub-grid-title">
                    <a href="{{ route('blog.posts.show', $subPost->slug) }}">{{ Str::limit($subPost->title, 40) }}</a>
                </h4>
                <p class="sub-grid-excerpt small">{{ Str::limit(strip_tags($subPost->content), 60) }}</p>
            </div>
            @endforeach
        </div>
    </div>

    {{-- KANAN (Sidebar dengan AJAX Load More) --}}
    <div class="col-lg-4 border-start ps-lg-4">
        <div id="sidebar-scroll-wrapper">
            <div id="side-post-list">
                @foreach($sidePosts as $sidePost)
                <article class="side-list-item">
                    <div class="side-list-content">
                        <h5 class="side-list-title">
                            <a href="{{ route('blog.posts.show', $sidePost->slug) }}">{{ Str::limit($sidePost->title, 45) }}</a>
                        </h5>
                        <small class="text-muted">{{ $sidePost->created_at->format('M d, Y') }}</small>
                    </div>
                    <img src="{{ asset('/storage/' . $sidePost->featured_image) }}" class="side-list-img" onerror="this.src='https://placehold.co/80?text=Img'">
                </article>
                @endforeach
            </div>

            {{-- Pagination Controller --}}
            <div class="load-more-container">
                <div id="loading-spinner" class="loading-spinner">Memuat...</div>
                <button id="btn-load-more" data-page="1" data-category="{{ $category->id }}">Load More</button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    $('#btn-load-more').on('click', function() {
        let btn = $(this);
        let page = btn.data('page');
        let categoryId = btn.data('category');
        let listContainer = $('#side-post-list');
        let spinner = $('#loading-spinner');

        btn.hide();
        spinner.show();

        $.ajax({
            url: "{{ route('blog.posts.loadMore') }}", // Pastikan route ini ada di web.php
            method: "GET",
            data: {
                page: page + 1,
                category_id: categoryId,
                offset: 14 // Melewati data yang sudah tampil (1 main + 3 sub + 10 side)
            },
            success: function(response) {
                if(response.html.trim() == "") {
                    btn.parent().html('<p class="small text-muted text-center">Semua data telah dimuat</p>');
                } else {
                    listContainer.append(response.html);
                    btn.data('page', page + 1);
                    btn.show();
                    spinner.hide();
                }
            },
            error: function() {
                alert("Gagal memuat data.");
                btn.show();
                spinner.hide();
            }
        });
    });
});
</script>
