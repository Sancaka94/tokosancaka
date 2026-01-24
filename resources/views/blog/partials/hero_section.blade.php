<style>
    /* HERO SECTION STYLES */
    .hero-wrap { margin-bottom: 50px; }

    /* Left Main Post */
    .hero-main { position: relative; height: 450px; overflow: hidden; border-radius: 3px; }
    .hero-main img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s; }
    .hero-main:hover img { transform: scale(1.05); }
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

    /* Right Side List */
    .hero-list-item { display: flex; gap: 15px; margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid #eee; }
    .hero-list-item:last-child { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }
    .hero-list-img { width: 100px; height: 75px; flex-shrink: 0; border-radius: 3px; overflow: hidden; }
    .hero-list-img img { width: 100%; height: 100%; object-fit: cover; }
    .hero-list-title { font-family: 'IBM Plex Serif', serif; font-size: 16px; font-weight: 700; line-height: 1.3; margin-bottom: 5px; }
    .hero-list-title a { color: #111; text-decoration: none; }
    .hero-list-title a:hover { color: #dd0017; }

    /* UPDATE BAGIAN INI */
    .hero-main img,
    .hero-list-img img {
        width: 100%;
        height: 100%;
        aspect-ratio: 1 / 1;  /* Paksa 1:1 */
        object-fit: contain;  /* Gambar utuh */
        background: #000;     /* Background hitam biar rapi */
    }

    /* Revisi tinggi container agar mengikuti rasio */
    .hero-main { height: auto; aspect-ratio: 1/1; overflow: hidden; border-radius: 3px; }
    .hero-list-img { width: 100px; height: 100px; /* Samakan lebar tinggi */ flex-shrink: 0; }

    @media(max-width: 768px) { .hero-main { height: 300px; margin-bottom: 30px; } }
</style>

<div class="hero-wrap">
    <div class="row">
        {{-- KIRI: 1 BERITA UTAMA --}}
        <div class="col-lg-7">
            @if($headline = $latestPosts->first())
            <div class="hero-main">
                <a href="{{ route('blog.posts.show', $headline->slug) }}">
                    <img src="{{ asset('/storage/' . $headline->featured_image) }}" alt="{{ $headline->title }}" onerror="this.onerror=null;this.src='https://placehold.co/800x600/333/fff?text=Headline';">
                    <div class="hero-overlay">
                        <span class="hero-cat">{{ $headline->category->name ?? 'News' }}</span>
                        <h2 class="hero-title">{{ Str::limit($headline->title, 60) }}</h2>
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

        {{-- KANAN: 4 BERITA TERBARU LAINNYA --}}
        <div class="col-lg-5">
            <div class="d-flex flex-column h-100 justify-content-between">
                @foreach($latestPosts->skip(1)->take(4) as $post)
                <div class="hero-list-item">
                    <div class="hero-list-img">
                        <a href="{{ route('blog.posts.show', $post->slug) }}">
                            <img src="{{ asset('/storage/' . $post->featured_image) }}" alt="{{ $post->title }}" onerror="this.onerror=null;this.src='https://placehold.co/200x150/eee/999?text=Img';">
                        </a>
                    </div>
                    <div>
                        <h4 class="hero-list-title">
                            <a href="{{ route('blog.posts.show', $post->slug) }}">{{ Str::limit($post->title, 55) }}</a>
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
