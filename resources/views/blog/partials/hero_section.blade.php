<style>
    /* HERO WRAPPER */
    .hero-wrap { margin-bottom: 50px; }

    /* --- KOLOM KIRI: HEADLINE --- */
    .hero-main {
        position: relative;
        height: 450px; /* Tinggi Tetap */
        overflow: hidden;
        border-radius: 5px;
        background: #000;
    }
    .hero-main a { display: block; height: 100%; width: 100%; }
    .hero-main img {
        width: 100%; height: 100%; object-fit: cover; /* Cover agar full */
        transition: transform 0.5s ease;
        opacity: 0.8;
    }
    .hero-main:hover img { transform: scale(1.05); opacity: 0.6; }

    .hero-overlay {
        position: absolute; bottom: 0; left: 0; width: 100%;
        background: linear-gradient(to top, rgba(0,0,0,0.9) 0%, transparent 100%);
        padding: 30px; z-index: 2;
    }
    .hero-cat {
        background: #dd0017; color: #fff; font-size: 10px; font-weight: 700;
        text-transform: uppercase; padding: 4px 10px; border-radius: 3px; letter-spacing: 1px;
    }
    .hero-title {
        color: #fff; font-family: 'IBM Plex Serif', serif; font-size: 28px;
        font-weight: 700; margin-top: 15px; line-height: 1.3;
        text-shadow: 0 2px 5px rgba(0,0,0,0.5);
    }
    .hero-meta { color: rgba(255,255,255,0.7); font-size: 11px; margin-top: 10px; text-transform: uppercase; }

    /* --- KOLOM TENGAH: FAVORITE LIST --- */
    .hero-scroll-wrap {
        height: 450px;
        overflow-y: auto;
        padding-right: 5px;
    }
    /* Scrollbar Style */
    .hero-scroll-wrap::-webkit-scrollbar { width: 4px; }
    .hero-scroll-wrap::-webkit-scrollbar-track { background: #eee; }
    .hero-scroll-wrap::-webkit-scrollbar-thumb { background: #dd0017; border-radius: 10px; }

    .fav-item {
        display: flex; gap: 12px; margin-bottom: 15px;
        padding-bottom: 15px; border-bottom: 1px solid #f0f0f0;
    }
    .fav-item:last-child { border-bottom: none; }

    .fav-img {
        width: 80px; height: 80px; flex-shrink: 0;
        border-radius: 4px; overflow: hidden; background: #eee;
    }
    .fav-img img { width: 100%; height: 100%; object-fit: cover; }

    .fav-info h5 {
        font-size: 14px; font-weight: 700; line-height: 1.4; margin-bottom: 5px;
        font-family: 'IBM Plex Serif', serif;
    }
    .fav-info h5 a { color: #222; text-decoration: none; transition: 0.2s; }
    .fav-info h5 a:hover { color: #dd0017; }
    .fav-meta { font-size: 10px; color: #888; text-transform: uppercase; font-weight: 600; }

    /* --- KOLOM KANAN: CATEGORIES --- */
    .cat-list-wrap {
        height: 450px;
        overflow-y: auto;
        background: #f9f9f9; /* Sedikit abu agar beda */
        padding: 20px;
        border-radius: 5px;
        border: 1px solid #eee;
    }
    .section-head {
        font-size: 14px; font-weight: 800; text-transform: uppercase;
        letter-spacing: 1px; margin-bottom: 20px; border-bottom: 2px solid #dd0017;
        display: inline-block; padding-bottom: 5px; color: #111;
    }

    .cat-item-link {
        display: flex; justify-content: space-between; align-items: center;
        padding: 12px 0; border-bottom: 1px dashed #ddd;
        color: #444; text-decoration: none; font-weight: 600; font-size: 13px;
        transition: 0.2s;
    }
    .cat-item-link:hover { color: #dd0017; padding-left: 5px; }
    .cat-count {
        background: #ddd; color: #555; font-size: 10px;
        padding: 2px 6px; border-radius: 10px;
    }
    .cat-item-link:hover .cat-count { background: #dd0017; color: #fff; }

    /* RESPONSIVE */
    @media(max-width: 991px) {
        .hero-main { height: 300px; margin-bottom: 20px; }
        .hero-scroll-wrap, .cat-list-wrap { height: auto; max-height: 350px; margin-bottom: 20px; }
    }

    /* Desain Form Pencarian Sancaka */
    .search-section {
        background: #fff;
        padding: 25px;
        border-radius: 8px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        margin-bottom: 30px;
        border-left: 5px solid #dd0017; /* Aksen Merah Sancaka */
    }
    .search-box {
        position: relative;
    }
    .search-box input {
        height: 50px;
        border-radius: 5px;
        padding-left: 45px;
        border: 1px solid #ddd;
        font-size: 15px;
    }
    .search-box i {
        position: absolute;
        left: 15px;
        top: 17px;
        color: #888;
    }
    .btn-sancaka {
        background: #dd0017;
        color: white;
        font-weight: 700;
        padding: 0 30px;
        border-radius: 5px;
        transition: 0.3s;
    }
    .btn-sancaka:hover {
        background: #000;
        color: white;
    }

</style>

<div class="hero-wrap">
    <div class="row g-4"> {{-- g-4 memberi jarak antar kolom --}}

        {{-- =================================== --}}
        {{-- KOLOM 1: KIRI (HEADLINE) - LEBAR 5 --}}
        {{-- =================================== --}}
        <div class="col-lg-5">
            @if($headline = $latestPosts->first())
                <div class="hero-main">
                    <a href="{{ route('blog.posts.show', $headline->slug) }}">
                        <img src="{{ asset('/storage/' . $headline->featured_image) }}"
                             alt="{{ $headline->title }}"
                             onerror="this.onerror=null;this.src='https://placehold.co/800x800/000/fff?text=Headline';">

                        <div class="hero-overlay">
                            <span class="hero-cat">{{ $headline->category->name ?? 'News' }}</span>
                            <h2 class="hero-title">{{ Str::limit($headline->title, 60) }}</h2>
                            <div class="hero-meta">
                                {{ $headline->created_at->format('d M Y') }} • By {{ $headline->user->name ?? 'Admin' }}
                            </div>
                        </div>
                    </a>
                </div>
            @endif
        </div>

        {{-- ======================================== --}}
        {{-- KOLOM 2: TENGAH (FAVORITE) - LEBAR 4 --}}
        {{-- ======================================== --}}
        <div class="col-lg-4">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="section-head" style="margin-bottom: 10px; border-bottom: none;">FAVORIT & POPULER</span>
            </div>

            <div class="hero-scroll-wrap">
                {{-- Mengambil post urutan ke-2 dst, anggap sebagai 'Favorite' untuk tampilan --}}
                {{-- Jika ada variabel khusus $favoritePosts, ganti $latestPosts->skip(1) dengan $favoritePosts --}}
                @foreach($latestPosts->skip(1)->take(6) as $post)
                    <div class="fav-item">
                        <div class="fav-img">
                            <a href="{{ route('blog.posts.show', $post->slug) }}">
                                <img src="{{ asset('/storage/' . $post->featured_image) }}"
                                     alt="{{ $post->title }}"
                                     onerror="this.onerror=null;this.src='https://placehold.co/200x200/eee/999?text=Img';">
                            </a>
                        </div>
                        <div class="fav-info">
                            <div class="fav-meta text-danger mb-1">{{ $post->category->name ?? 'Umum' }}</div>
                            <h5>
                                <a href="{{ route('blog.posts.show', $post->slug) }}">
                                    {{ Str::limit($post->title, 55) }}
                                </a>
                            </h5>
                            <small class="text-muted" style="font-size:10px;">{{ $post->created_at->diffForHumans() }}</small>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- ========================================== --}}
        {{-- KOLOM 3: KANAN (KATEGORI) - LEBAR 3 --}}
        {{-- ========================================== --}}
        <div class="col-lg-3">
            <div class="cat-list-wrap">
                <span class="section-head">KATEGORI</span>

                <div class="mt-2">
                    @foreach($categories as $category)
                        {{-- Hanya tampilkan kategori yang punya post (opsional) --}}
                        <a href="{{ url('/blog?category=' . $category->slug) }}" class="cat-item-link">
                            <span>{{ $category->name }}</span>
                            {{-- Menampilkan jumlah post jika relasi count tersedia --}}
                            <span class="cat-count">{{ $category->posts_count ?? 0 }}</span>
                        </a>
                    @endforeach
                </div>

                {{-- Tombol Lihat Semua di bawah --}}
                <div class="mt-4 text-center">
                    <a href="{{ url('/blog/categories') }}" class="btn btn-sm btn-outline-dark w-100" style="font-size: 11px; font-weight: bold;">
                        LIHAT SEMUA
                    </a>
                </div>
            </div>
        </div>

    </div> {{-- End Row --}}
</div>
