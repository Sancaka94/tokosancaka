{{-- FILE: resources/views/blog/category.blade.php --}}

@extends('layouts.app') {{-- Sesuaikan dengan layout utama Anda --}}

@section('content')

<style>
    /* CSS Header Kategori */
    .head-cat { border-top: 3px solid #000; padding-top: 10px; margin-bottom: 25px; }
    .head-cat h4 { color: #ff0000; font-weight: 800; font-family: 'Inter', sans-serif; margin: 0; text-transform: uppercase; }

    /* CSS Sidebar Scroll */
    .sidebar-scroll { max-height: 800px; overflow-y: auto; padding-right: 10px; }
    .sidebar-scroll::-webkit-scrollbar { width: 4px; }
    .sidebar-scroll::-webkit-scrollbar-thumb { background: #ccc; border-radius: 4px; }

    /* Item Sidebar */
    .side-item { display: flex; justify-content: space-between; margin-bottom: 15px; border-bottom: 1px solid #eee; padding-bottom: 15px; }
    .side-title a { text-decoration: none; color: #000; font-weight: 600; font-size: 14px; font-family: 'IBM Plex Serif', serif; }
    .side-img { width: 70px; height: 70px; object-fit: cover; background: #f4f4f4; }
</style>

<div class="container my-5">
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

{{-- SCRIPT AJAX (Supaya tidak refresh halaman & tidak error) --}}
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    // Kode ini mendeteksi klik pada tombol pagination
    $(document).on('click', '.pagination-wrapper a', function(e) {
        e.preventDefault(); // Mencegah refresh halaman

        let url = $(this).attr('href'); // Ambil URL halaman berikutnya

        if(!url || url === '#') return;

        // Tampilkan Loading
        $('#loading').show();
        $('#ajax-container').css('opacity', '0.5');

        // Request Data Baru
        $.ajax({
            url: url,
            type: "GET",
            success: function(response) {
                // Ganti isi konten lama dengan yang baru
                $('#ajax-container').html(response);

                // Sembunyikan Loading
                $('#loading').hide();
                $('#ajax-container').css('opacity', '1');

                // Scroll sedikit ke atas
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

@endsection
