<style>
    /* HERO SECTION STYLES */
    .hero-wrap { margin-bottom: 50px; }

    /* Left Main Post */
    .hero-main {
        position: relative;
        height: 450px; /* Tinggi Tetap */
        overflow: hidden;
        border-radius: 3px;
    }

    .hero-main a { display: block; height: 100%; width: 100%; }

    .hero-overlay {
        position: absolute; bottom: 0; left: 0; width: 100%;
        background: linear-gradient(to top, #000 0%, transparent 100%);
        padding: 30px;
        z-index: 2;
    }
    .hero-cat {
        background: #dd0017; color: #fff; font-size: 10px; font-weight: 700;
        text-transform: uppercase; padding: 3px 8px; border-radius: 2px;
    }
    .hero-title {
        color: #fff; font-family: 'IBM Plex Serif', serif; font-size: 32px;
        font-weight: 700; margin-top: 10px; line-height: 1.2; text-shadow: 0 2px 4px rgba(0,0,0,0.5);
    }
    .hero-meta { color: rgba(255,255,255,0.8); font-size: 11px; margin-top: 10px; text-transform: uppercase; }

    /* Right Side List & Scrollbar */
    .hero-right-scroll {
        height: 450px; /* Samakan tinggi dengan gambar utama kiri */
        overflow-y: auto; /* Aktifkan scroll vertikal */
        padding-right: 10px; /* Jarak untuk scrollbar */
    }

    /* Custom Scrollbar Styling (Webkit) */
    .hero-right-scroll::-webkit-scrollbar { width: 5px; }
    .hero-right-scroll::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 3px; }
    .hero-right-scroll::-webkit-scrollbar-thumb { background: #dd0017; border-radius: 4px; } /* Warna Merah */
    .hero-right-scroll::-webkit-scrollbar-thumb:hover { background: #b00012; }

    .hero-list-item {
        display: flex;
        gap: 15px;
        margin-bottom: 20px;
        padding-bottom: 20px;
        border-bottom: 1px solid #eee;
    }
    .hero-list-item:last-child { margin-bottom: 0; }

    /* Gambar Kecil Kanan */
    .hero-list-img {
        width: 100px;
        height: 100px; /* Ukuran kotak fix */
        flex-shrink: 0;
        border-radius: 3px;
        overflow: hidden;
        background: #000;
        border: 1px solid #eee;
    }

    .hero-list-title {
        font-family: 'IBM Plex Serif', serif;
        font-size: 15px;
        font-weight: 700;
        line-height: 1.3;
        margin-bottom: 5px;
    }
    .hero-list-title a { color: #111; text-decoration: none; }
    .hero-list-title a:hover { color: #dd0017; }

    /* IMAGE FIX 1:1 (CONTAIN) */
    .hero-main img,
    .hero-list-img img {
        width: 100%;
        height: 100%;
        aspect-ratio: 1 / 1;  /* Paksa Rasio 1:1 */
        object-fit: contain;  /* Gambar utuh (ada letterbox hitam jika tidak persegi) */
        background: #000;     /* Background hitam pengisi ruang kosong */
        transition: transform 0.5s;
    }

    .hero-main:hover img { transform: scale(1.05); }

    /* Responsive */
    @media(max-width: 991px) {
        .hero-main { height: 300px; margin-bottom: 30px; }
        .hero-right-scroll { height: auto; max-height: 400px; }
    }

    /* SEARCH FORM STYLES */
    .search-wrap {
        margin-bottom: 40px;
        padding: 20px;
        background: #f8f9fa;
        border-radius: 4px;
        border-left: 4px solid #dd0017; /* Aksen merah Sancaka */
    }

    .search-form .input-group {
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }

    .search-form input {
        border: 1px solid #eee;
        padding: 12px 20px;
        font-size: 14px;
    }

    .search-form input:focus {
        border-color: #dd0017;
        box-shadow: none;
    }

    .search-form .btn-search {
        background: #dd0017;
        color: #fff;
        padding: 0 25px;
        text-transform: uppercase;
        font-weight: 700;
        font-size: 13px;
        transition: 0.3s;
    }

    .search-form .btn-search:hover {
        background: #b00012;
        color: #fff;
    }

</style>


<div class="hero-wrap">

    <div class="search-wrap">
        <div class="row align-items-center">
            <div class="col-md-4 mb-3 mb-md-0">
                <h5 class="mb-0" style="font-family: 'IBM Plex Serif', serif; font-weight: 700;">
                    Cari Artikel
                </h5>
                <p class="text-muted small mb-0">Temukan informasi terbaru dari Sancaka Express</p>
            </div>
            <div class="col-md-8">
                <form action="{{ route('blog.posts.index') }}" method="GET" class="search-form">
                    <div class="input-group">
                        <input type="text" name="search" class="form-control" placeholder="Masukkan kata kunci judul atau isi artikel..." value="{{ request('search') }}">
                        <button class="btn btn-search" type="submit">
                            <i class="fas fa-search me-2"></i> Cari
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- KIRI: 1 BERITA UTAMA (HEADLINE) --}}
        <div class="col-lg-7">
            @if($headline = $latestPosts->first())
            <div class="hero-main">
                <a href="{{ route('blog.posts.show', $headline->slug) }}">
                    <img src="{{ asset('/storage/' . $headline->featured_image) }}" alt="{{ $headline->title }}" onerror="this.onerror=null;this.src='https://placehold.co/800x800/000/fff?text=Headline';">
                    <div class="hero-overlay">
                        <span class="hero-cat">{{ $headline->category->name ?? 'News' }}</span>
                        <h2 class="hero-title">{{ Str::limit($headline->title, 70) }}</h2>
                        <div class="hero-meta">
                            <i class="far fa-clock me-1"></i> {{ $headline->created_at->format('d M Y') }}
                            <span class="mx-2">•</span>
                            By {{ $headline->user->name ?? 'Admin' }}
                        </div>
                    </div>
                </a>
            </div>
            @endif
        </div>

        {{-- KANAN: LIST SEMUA BERITA TERBARU (SCROLLABLE) --}}
        <div class="col-lg-5">
            {{-- Wrapper Scrollbar --}}
            <div class="hero-right-scroll">
                <div class="d-flex flex-column">
                    {{-- Loop semua post kecuali yang pertama (headline), tanpa limit --}}
                    @foreach($latestPosts->skip(1) as $post)
                    <div class="hero-list-item">
                        <div class="hero-list-img">
                            <a href="{{ route('blog.posts.show', $post->slug) }}">
                                <img src="{{ asset('/storage/' . $post->featured_image) }}" alt="{{ $post->title }}" onerror="this.onerror=null;this.src='https://placehold.co/200x200/000/fff?text=Img';">
                            </a>
                        </div>
                        <div>
                            <h4 class="hero-list-title">
                                <a href="{{ route('blog.posts.show', $post->slug) }}">{{ Str::limit($post->title, 60) }}</a>
                            </h4>
                            <div class="text-muted small" style="font-size: 10px; text-transform: uppercase;">
                                <span class="text-danger fw-bold">{{ $post->category->name ?? 'Info' }}</span> • {{ $post->created_at->diffForHumans() }}
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
