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

    /* --- Sidebar Kanan --- */
    .side-list-container {
        max-height: 850px;
        overflow-y: auto;
        padding-right: 12px;
    }
    /* Scrollbar Styling */
    .side-list-container::-webkit-scrollbar { width: 4px; }
    .side-list-container::-webkit-scrollbar-track { background: #f9f9f9; }
    .side-list-container::-webkit-scrollbar-thumb { background: #ccc; border-radius: 10px; }

    .side-list-item {
        display: flex;
        gap: 12px;
        padding-bottom: 15px;
        margin-bottom: 15px;
        border-bottom: 1px solid #f0f0f0;
        align-items: flex-start;
    }

    .side-list-content {
        flex-grow: 1;
    }

    .side-list-title a {
        text-decoration: none !important;
        color: #000;
        font-size: 13px;
        font-weight: 600;
        transition: color 0.2s;
    }
    .side-list-title a:hover {
        color: #ff0000; /* Efek hover merah */
    }

    .side-list-img {
        width: 60px;
        height: 60px;
        object-fit: cover;
        background: #eee;
        border-radius: 4px;
        flex-shrink: 0; /* Mencegah gambar gepeng */
    }

    /* --- AJAX Loading Effect --- */
    #left-content-wrapper {
        position: relative;
        min-height: 500px;
    }

    .ajax-loader {
        display: none;
        position: absolute;
        top: 0; left: 0;
        width: 100%; height: 100%;
        background: rgba(255,255,255,0.85);
        z-index: 10;
        justify-content: center;
        align-items: flex-start;
        padding-top: 100px;
    }

    /* Simple CSS Spinner */
    .spinner {
        border: 4px solid #f3f3f3;
        border-top: 4px solid #ff0000;
        border-radius: 50%;
        width: 40px;
        height: 40px;
        animation: spin 1s linear infinite;
    }
    @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
</style>

@php
    // Query Sidebar: Mengambil 10 post terbaru
    // Pastikan variabel $category tersedia dari controller
    $sidePosts = $category->posts()->latest()->take(20)->get();
@endphp

<div class="row mb-5">
    {{-- HEADER KATEGORI --}}
    <div class="col-12">
        <div class="smart-head-cat"><h4>{{ $category->name }}</h4></div>
    </div>

    {{-- KIRI: Konten Berita (AJAX Area) --}}
    <div class="col-lg-8" id="left-content-wrapper">
        {{-- Loader --}}
        <div class="ajax-loader">
            <div class="spinner"></div>
        </div>

        {{-- Area Konten Dinamis --}}
        <div id="ajax-content-area">
            {{-- Pastikan file partials/post_grid.blade.php ada --}}
            @include('blog.partials.post_grid', ['latestPosts' => $latestPosts])
        </div>
    </div>

    {{-- KANAN: Sidebar Scroll --}}
    <div class="col-lg-4 border-start ps-lg-4">
        <div class="side-list-container">
            @foreach($sidePosts as $sidePost)
            <article class="side-list-item">
                <div class="side-list-content">
                    <h5 class="side-list-title">
                        <a href="{{ route('blog.posts.show', $sidePost->slug) }}">
                            {{ Str::limit($sidePost->title, 50) }}
                        </a>
                    </h5>
                    <small class="text-muted" style="font-size: 10px;">
                        {{ $sidePost->created_at->format('M d, Y') }}
                    </small>
                </div>
                <img src="{{ asset('/storage/' . $sidePost->featured_image) }}"
                     class="side-list-img"
                     alt="{{ $sidePost->title }}"
                     onerror="this.src='https://placehold.co/60x60?text=No+Img'">
            </article>
            @endforeach
        </div>
    </div>
</div>

{{-- SCRIPT --}}
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    // Event Delegation untuk Pagination
    $(document).on('click', '.pagination a', function(e) {
        e.preventDefault();

        let url = $(this).attr('href');

        // Cek jika URL valid
        if(!url || url === '#') return;

        // Tampilkan Loader
        $('.ajax-loader').css('display', 'flex');

        $.ajax({
            url: url,
            type: 'GET',
            dataType: 'html',
            success: function(data) {
                // Update konten
                $('#ajax-content-area').html(data);

                // Scroll halus ke atas kategori
                $('html, body').animate({
                    scrollTop: $(".smart-head-cat").offset().top - 20
                }, 300);
            },
            error: function(xhr, status, error) {
                console.error("AJAX Error:", error);
                alert("Gagal memuat halaman. Silakan coba lagi.");
            },
            complete: function() {
                // Sembunyikan loader baik sukses maupun gagal
                $('.ajax-loader').hide();
            }
        });
    });
});
</script>
