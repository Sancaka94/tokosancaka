<style>
    /* --- CSS Header Kategori --- */
    .head-cat { border-top: 3px solid #000; padding-top: 10px; margin-bottom: 25px; }
    .head-cat h4 { color: #dd0017; font-weight: 800; font-family: 'Inter', sans-serif; margin: 0; text-transform: uppercase; }

    /* --- CSS Sidebar Scroll --- */
    .sidebar-scroll { max-height: 800px; overflow-y: auto; padding-right: 10px; }
    .sidebar-scroll::-webkit-scrollbar { width: 4px; }
    .sidebar-scroll::-webkit-scrollbar-thumb { background: #ccc; border-radius: 4px; }

    /* --- Item Sidebar --- */
    .side-item { display: flex; justify-content: space-between; margin-bottom: 15px; border-bottom: 1px solid #eee; padding-bottom: 15px; }
    .side-title a { text-decoration: none; color: #000; font-weight: 600; font-size: 14px; font-family: 'IBM Plex Serif', serif; }
    .side-img { width: 70px; height: 70px; object-fit: cover; background: #f4f4f4; }

    /* --- [BARU] CSS CATEGORY BUTTONS --- */
    .cat-nav-scroll {
        display: flex;
        gap: 10px;
        overflow-x: auto;
        white-space: nowrap;
        padding-bottom: 5px; /* Ruang untuk scrollbar */
        margin-bottom: 30px;
        -webkit-overflow-scrolling: touch; /* Smooth scroll di HP */
    }
    /* Sembunyikan scrollbar navigasi tapi tetap bisa discroll */
    .cat-nav-scroll::-webkit-scrollbar { height: 0px; background: transparent; }

    .btn-cat-nav {
        display: inline-block;
        padding: 8px 20px;
        border: 1px solid #e0e0e0;
        background: #fff;
        color: #333;
        border-radius: 50px; /* Bentuk Pill/Lonjong */
        font-family: 'Inter', sans-serif;
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        text-decoration: none;
        transition: all 0.2s ease;
    }

    .btn-cat-nav:hover {
        border-color: #000;
        background: #f9f9f9;
        color: #000;
    }

    /* Status Aktif (Tombol Kategori yang sedang dibuka) */
    .btn-cat-nav.active {
        background: #dd0017; /* Merah */
        border-color: #dd0017;
        color: #fff;
    }
</style>

@php
    // LOG LOG: Ambil semua kategori untuk menu tombol
    // (Jika Anda sudah passing variabel $all_categories dari controller, baris ini bisa dihapus)
    $allCategories = \App\Models\Category::orderBy('name', 'asc')->get();
@endphp

<div class="container my-5">

    {{-- [BARU] BAGIAN TOMBOL PILIH KATEGORI --}}
    <div class="row mb-2">
        <div class="col-12">
            <div class="cat-nav-scroll">
                {{-- Tombol "All" atau Home --}}
                <a href="{{ url('/') }}" class="btn-cat-nav">Home</a>

                {{-- Loop Kategori --}}
                @foreach($allCategories as $catItem)
                    <a href="{{ route('blog.posts.category', $catItem->slug) }}"
                       class="btn-cat-nav {{ (isset($category) && $category->id == $catItem->id) ? 'active' : '' }}">
                        {{ $catItem->name }}
                    </a>
                @endforeach
            </div>
        </div>
    </div>
    {{-- [AKHIR] BAGIAN TOMBOL --}}


    <div class="row">
        {{-- JUDUL KATEGORI --}}
        <div class="col-12">
            <div class="head-cat"><h4>{{ $category->name }}</h4></div>
        </div>

        {{-- KOLOM KIRI (AJAX CONTENT) --}}
        <div class="col-lg-8 position-relative">
            <div id="loading" style="display:none; position:absolute; inset:0; background:rgba(255,255,255,0.8); z-index:10; text-align:center; padding-top:100px;">
                <div class="spinner-border text-danger" role="status"></div>
            </div>

            <div id="ajax-container">
                @include('blog.partials.content_grid', ['posts' => $posts])
            </div>
        </div>

        {{-- KOLOM KANAN (SIDEBAR SCROLL) --}}
        <div class="col-lg-4 border-start ps-lg-4">
            <div class="sidebar-scroll">
                @foreach($category->posts()->latest()->take(10)->get() as $sidePost)
                <div class="side-item">
                    <div style="flex:1; padding-right:10px;">
                        <h5 class="side-title">
                            <a href="{{ route('blog.posts.show', $sidePost->slug) }}">{{ Str::limit($sidePost->title, 45) }}</a>
                        </h5>
                        <small class="text-muted">{{ $sidePost->created_at->format('M d, Y') }}</small>
                    </div>
                    <a href="{{ route('blog.posts.show', $sidePost->slug) }}">
                        <img src="{{ asset('storage/' . $sidePost->featured_image) }}" class="side-img">
                    </a>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

{{-- SCRIPT AJAX --}}
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    $(document).on('click', '.pagination-wrapper a', function(e) {
        e.preventDefault();
        let url = $(this).attr('href');
        if(!url || url === '#') return;

        $('#loading').show();
        $('#ajax-container').css('opacity', '0.5');

        $.ajax({
            url: url,
            type: "GET",
            success: function(response) {
                $('#ajax-container').html(response);
                $('#loading').hide();
                $('#ajax-container').css('opacity', '1');
                $('html, body').animate({ scrollTop: $(".head-cat").offset().top - 20 }, 300);
            },
            error: function() {
                alert("Gagal memuat data. Cek koneksi.");
                $('#loading').hide();
            }
        });
    });
});
</script>
