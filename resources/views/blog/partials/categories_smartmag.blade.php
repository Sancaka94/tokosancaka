<style>
    /* --- Category Header --- */
    .smart-head-cat {
        border-top: 2px solid #000;
        margin-bottom: 25px;
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

    /* --- Sidebar Scroll (Kanan - Max 10) --- */
    .side-list-container {
        max-height: 800px;
        overflow-y: auto;
        padding-right: 12px;
    }
    .side-list-container::-webkit-scrollbar { width: 4px; }
    .side-list-container::-webkit-scrollbar-track { background: #f9f9f9; }
    .side-list-container::-webkit-scrollbar-thumb { background: #ccc; border-radius: 10px; }

    .side-list-item {
        display: flex; gap: 12px;
        padding-bottom: 15px; margin-bottom: 15px;
        border-bottom: 1px solid #f0f0f0;
    }
    .side-list-title a {
        text-decoration: none !important;
        color: #000; font-size: 14px; font-weight: 600;
        transition: 0.2s;
    }
    .side-list-title a:hover { color: #dd0017; }

    /* --- Loading State --- */
    #left-content-wrapper { position: relative; min-height: 400px; }
    .ajax-loader {
        display: none; position: absolute; top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(255,255,255,0.8); z-index: 5; justify-content: center; align-items: flex-start; padding-top: 50px;
    }
</style>

@php
    // LOG LOG: Ambil 10 post terbaru untuk sidebar kanan (statis)
    $sidePosts = $category->posts()->latest()->take(10)->get();
    // Data kiri menggunakan pagination (diterima dari Controller sebagai $latestPosts)
@endphp

<div class="row mb-5">
    <div class="col-12">
        <div class="smart-head-cat"><h4>{{ $category->name }}</h4></div>
    </div>

    {{-- KIRI: Grid Content (AJAX) --}}
    <div class="col-lg-8" id="left-content-wrapper">
        <div class="ajax-loader"><strong>Memuat...</strong></div>
        <div id="ajax-content-area">
            @include('blog.partials.post_grid', ['latestPosts' => $latestPosts])
        </div>
    </div>

    {{-- KANAN: Scroll Sidebar --}}
    <div class="col-lg-4 border-start ps-lg-4">
        <div class="side-list-container">
            @foreach($sidePosts as $sidePost)
            <article class="side-list-item">
                <div class="side-list-content flex-grow-1">
                    <h5 class="side-list-title">
                        <a href="{{ route('blog.posts.show', $sidePost->slug) }}">{{ Str::limit($sidePost->title, 100) }}</a>
                    </h5>
                    <small class="text-muted" style="font-size: 10px;">{{ $sidePost->created_at->format('M d, Y') }}</small>
                </div>
                <img src="{{ asset('/storage/' . $sidePost->featured_image) }}"
                     style="width: 70px; height: 70px; object-fit: cover; border-radius: 2px;"
                     onerror="this.src='https://placehold.co/70x70?text=Img'">
            </article>
            @endforeach
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).on('click', '.pagination a', function(e) {
    e.preventDefault();
    let url = $(this).attr('href');

    $('.ajax-loader').css('display', 'flex');

    $.ajax({
        url: url,
        success: function(data) {
            $('#ajax-content-area').html(data);
            $('.ajax-loader').hide();
            $('html, body').animate({ scrollTop: $(".smart-head-cat").offset().top - 50 }, 300);
        }
    });
});
</script>
