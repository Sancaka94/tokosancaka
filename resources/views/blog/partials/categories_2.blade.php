<div class="row">
    {{-- KIRI: KONTEN UTAMA (GRID VIEW) --}}
    <div class="col-lg-8">
        <div class="block-head">
            <h4 style="background: #000000;">{{ $category->name }}</h4> {{-- Warna beda dikit --}}
            <a href="{{ route('blog.posts.index', ['category' => $category->slug]) }}" class="block-head-link">Semua</a>
        </div>

        <div class="row g-4">
            @foreach($category->posts()->latest()->take(4)->get() as $post)
            <div class="col-md-6">
                <div class="card h-100 border-0 shadow-sm">
                    <div style="height: 200px; overflow: hidden;">
                        <img src="{{ asset('/storage/' . $post->featured_image) }}" class="card-img-top h-100 w-100" style="object-fit: cover; transition: 0.3s;" alt="{{ $post->title }}">
                    </div>
                    <div class="card-body">
                        <h5 class="card-title fw-bold font-serif" style="font-size: 16px;">
                            <a href="{{ route('blog.posts.show', $post->slug) }}">{{ Str::limit($post->title, 50) }}</a>
                        </h5>
                        <p class="card-text small text-muted">{{ $post->created_at->diffForHumans() }}</p>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- KANAN: SIDEBAR KHUSUS KATEGORI 2 --}}
    <div class="col-lg-4 ps-lg-4">
        <div class="widget">
            <h5 class="widget-title">Sponsor</h5>
            <div class="bg-secondary bg-opacity-10 d-flex align-items-center justify-content-center text-muted border" style="height: 300px;">
                <span>Iklan 300x300</span>
            </div>
        </div>
    </div>
</div>
