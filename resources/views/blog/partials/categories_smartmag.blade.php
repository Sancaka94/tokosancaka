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

    /* --- Universal Links --- */
    .main-feat-title a, .sub-grid-title a, .side-list-title a {
        text-decoration: none !important;
        color: #000 !important;
        transition: color 0.2s;
    }
    .main-feat-title a:hover, .sub-grid-title a:hover, .side-list-title a:hover {
        color: #dd0017 !important;
    }

    /* --- Sidebar Scroll (Max 10) --- */
    .side-list-container {
        max-height: 750px;
        overflow-y: auto;
        padding-right: 12px;
    }
    .side-list-container::-webkit-scrollbar { width: 4px; }
    .side-list-container::-webkit-scrollbar-track { background: #f9f9f9; }
    .side-list-container::-webkit-scrollbar-thumb { background: #ccc; border-radius: 10px; }

    .side-list-item {
        display: flex; justify-content: space-between;
        padding-bottom: 15px; margin-bottom: 15px;
        border-bottom: 1px solid #f0f0f0;
    }
    .side-list-img {
        width: 85px; height: 85px; object-fit: contain;
        background: #f4f4f4; border-radius: 2px; flex-shrink: 0;
    }

    /* --- Left Content Layout --- */
    .main-feat-box { display: flex; gap: 20px; margin-bottom: 30px; }
    .main-feat-img-wrap { flex: 1.3; aspect-ratio: 1/1; background: #f4f4f4; overflow: hidden; }
    .main-feat-img { width: 100%; height: 100%; object-fit: contain; }
    .sub-grid-item { border-right: 1px solid #eee; padding-right: 15px; margin-bottom: 20px; }

    /* --- AJAX Overlay Loading --- */
    #left-content-wrapper { position: relative; min-height: 400px; }
    .loading-overlay {
        display: none; position: absolute; top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(255,255,255,0.7); z-index: 10; justify-content: center; align-items: flex-start; padding-top: 100px;
    }
</style>

@php
    // LOG LOG: Pengambilan data awal
    $paginatedPosts = $category->posts()->latest()->paginate(7); // 1 main + 6 sub
    $sidePosts = $category->posts()->latest()->take(10)->get();
@endphp

<div class="row mb-5">
    <div class="col-12">
        <div class="smart-head-cat"><h4>{{ $category->name }}</h4></div>
    </div>

    {{-- KIRI: Konten dengan AJAX Wrapper --}}
    <div class="col-lg-8" id="left-content-wrapper">
        <div class="loading-overlay"><span>Memuat...</span></div>

        <div id="ajax-content-area">
            @include('blog.partials.post_grid', ['posts' => $paginatedPosts])
        </div>
    </div>

    {{-- KANAN: Scroll Sidebar (Tetap Statis) --}}
    <div class="col-lg-4 border-start ps-lg-4">
        <div class="side-list-container">
            @foreach($sidePosts as $sidePost)
            <article class="side-list-item">
                <div class="side-list-content">
                    <h5 class="side-list-title" style="font-size: 14px; font-weight: 600;">
                        <a href="{{ route('blog.posts.show', $sidePost->slug) }}">{{ Str::limit($sidePost->title, 50) }}</a>
                    </h5>
                    <small class="text-muted" style="font-size: 10px;">{{ $sidePost->created_at->format('M d, Y') }}</small>
                </div>
                <img src="{{ asset('/storage/' . $sidePost->featured_image) }}" class="side-list-img" onerror="this.src='https://placehold.co/85x85?text=Img'">
            </article>
            @endforeach
        </div>
    </div>
</div>

{{-- SCRIPT AJAX PAGINATION --}}
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    $(document).on('click', '.custom-pagination a', function(e) {
        e.preventDefault();
        let url = $(this).attr('href');

        $('.loading-overlay').css('display', 'flex');

        $.ajax({
            url: url,
            success: function(data) {
                // Pastikan controller mengembalikan view partial
                $('#ajax-content-area').html(data);
                $('.loading-overlay').hide();
                // Scroll ke atas section kategori
                $('html, body').animate({ scrollTop: $(".smart-head-cat").offset().top - 100 }, 500);
            }
        });
    });
});
</script>
