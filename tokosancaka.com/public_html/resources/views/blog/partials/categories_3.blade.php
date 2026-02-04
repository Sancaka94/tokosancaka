<div class="row">
    {{-- KIRI: KONTEN UTAMA (LAYOUT CAMPURAN) --}}
    <div class="col-lg-8">
        <div class="block-head">
            <h4 style="background: #000000;">{{ $category->name }}</h4>
            <a href="{{ route('blog.posts.index', ['category' => $category->slug]) }}" class="block-head-link">Lihat</a>
        </div>

        <div class="row">
            {{-- Postingan Pertama (Besar) --}}
            @if($firstPost = $category->posts()->latest()->first())
            <div class="col-md-6 mb-4 mb-md-0">
                <div class="position-relative rounded overflow-hidden mb-2">
                    <img src="{{ asset('/storage/' . $firstPost->featured_image) }}" class="w-100" style="height: 300px; object-fit: cover;" alt="">
                    <div class="position-absolute bottom-0 start-0 w-100 p-3" style="background: linear-gradient(transparent, rgba(0,0,0,0.8));">
                        <h3 class="h5 text-white fw-bold m-0">{{ Str::limit($firstPost->title, 50) }}</h3>
                    </div>
                </div>
                <p class="small text-secondary">{{ Str::limit(strip_tags($firstPost->content), 100) }}</p>
            </div>
            @endif

            {{-- Sisa Postingan (List Kecil) --}}
            <div class="col-md-6">
                @foreach($category->posts()->latest()->skip(1)->take(3)->get() as $post)
                <div class="d-flex gap-3 mb-3">
                    <img src="{{ asset('/storage/' . $post->featured_image) }}" class="rounded" style="width: 80px; height: 60px; object-fit: cover;" alt="">
                    <div>
                        <h6 class="fw-bold mb-1" style="font-size: 14px; line-height: 1.3;">
                            <a href="{{ route('blog.posts.show', $post->slug) }}">{{ Str::limit($post->title, 40) }}</a>
                        </h6>
                        <small class="text-muted" style="font-size: 10px;">{{ $post->created_at->format('d M') }}</small>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- KANAN: SIDEBAR KHUSUS KATEGORI 3 --}}
    <div class="col-lg-4 ps-lg-4">
        <div class="widget">
            <h5 class="widget-title">Terpopuler di {{ $category->name }}</h5>
            <ul class="list-unstyled">
                @foreach($category->posts()->inRandomOrder()->take(4)->get() as $pop)
                <li class="mb-2 pb-2 border-bottom border-light">
                    <a href="{{ route('blog.posts.show', $pop->slug) }}" class="fw-semibold text-dark" style="font-size: 14px;">
                        <i class="fas fa-angle-right text-success me-2"></i> {{ Str::limit($pop->title, 45) }}
                    </a>
                </li>
                @endforeach
            </ul>
        </div>
    </div>
</div>
