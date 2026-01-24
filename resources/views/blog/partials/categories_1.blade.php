<div class="row">
    {{-- KIRI: KONTEN UTAMA (LIST VIEW) --}}
    <div class="col-lg-8">
        <div class="block-head">
            <h4>{{ $category->name }}</h4>
            <a href="{{ route('blog.posts.index', ['category' => $category->slug]) }}" class="block-head-link">More <i class="fas fa-arrow-right"></i></a>
        </div>

        <div class="cat-1-wrapper">
            @foreach($category->posts()->latest()->take(4)->get() as $post)
            <div class="d-flex gap-3 mb-4 pb-4 border-bottom">
                {{-- Gambar Kotak --}}
                <div style="width: 200px; height: 150px; flex-shrink: 0;">
                    <img src="{{ asset('/storage/' . $post->featured_image) }}" class="w-100 h-100 rounded" style="object-fit: cover;" alt="{{ $post->title }}">
                </div>
                {{-- Teks --}}
                <div>
                    <h3 class="h5 fw-bold mb-2 font-serif">
                        <a href="{{ route('blog.posts.show', $post->slug) }}">{{ $post->title }}</a>
                    </h3>
                    <div class="small text-muted mb-2 text-uppercase" style="font-size: 11px;">
                        <span class="text-danger fw-bold me-2">Update</span> {{ $post->created_at->format('d M Y') }}
                    </div>
                    <p class="text-secondary small mb-0 d-none d-md-block">{{ Str::limit(strip_tags($post->content), 120) }}</p>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- KANAN: SIDEBAR KHUSUS KATEGORI 1 --}}
    <div class="col-lg-4 ps-lg-4">
        <div class="widget">
            <h5 class="widget-title">Ikuti Kami</h5>
            <div class="d-grid gap-2">
                <button class="btn btn-primary btn-sm"><i class="fab fa-facebook-f me-2"></i> Facebook</button>
                <button class="btn btn-info text-white btn-sm"><i class="fab fa-twitter me-2"></i> Twitter</button>
            </div>
        </div>

        <div class="widget p-3 bg-light border rounded">
            <h6 class="fw-bold mb-2">Info {{ $category->name }}</h6>
            <p class="small text-muted mb-0">Dapatkan update terbaru seputar {{ $category->name }} langsung dari redaksi Sancaka.</p>
        </div>
    </div>
</div>
