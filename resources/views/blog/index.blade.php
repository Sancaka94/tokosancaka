@extends('layouts.blog')

@section('title', 'Berita & Informasi Terbaru')

@section('content')

{{-- ====================================================================== --}}
{{-- == BAGIAN BLOG DENGAN DESAIN BARU == --}}
{{-- ====================================================================== --}}
<section id="blog" class="section">
    <div class="container">
        <h2 class="section-title">Berita & Informasi Terbaru</h2>
        
        @if($headline && !request()->filled('search'))
        <div class="row g-4 mb-5">
            <div class="col-lg-8">
                <div class="card post-card shadow-sm h-100">
                    <a href="{{ route('blog.posts.show', $headline->slug) }}" class="text-decoration-none text-dark">
                        {{-- PERBAIKAN 1: Tambahkan prefix 'storage/' --}}
                        <img src="{{ asset('/storage/' . $headline->featured_image) }}"
                             class="card-img-top post-card-img"
                             onerror="this.onerror=null;this.src='https://placehold.co/800x450/1a73e8/ffffff?text=Headline';"
                             alt="{{ $headline->title }}">
                        <div class="card-body p-4">
                            <small class="text-primary fw-bold">{{ $headline->category->name ?? 'UMUM' }}</small>
                            <h3 class="card-title fw-bold mt-2">{{ $headline->title }}</h3>
                            <p class="card-text text-muted">{{ Str::limit(strip_tags($headline->content), 1000) }}</p>
                            <small class="text-muted">{{ $headline->created_at->diffForHumans() }}</small>
                        </div>
                    </a>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="d-flex flex-column gap-3 h-100">
                    @foreach($topArticles as $article)
                    <a href="{{ route('blog.posts.show', $article->slug) }}" class="text-decoration-none text-dark">
                        <div class="card post-card shadow-sm flex-row h-100">
                           {{-- PERBAIKAN 2: Tambahkan prefix 'storage/' --}}
                           <img src="{{ asset('/storage/' . $article->featured_image) }}" class="w-25" style="aspect-ratio: 1/1; object-fit: cover;" onerror="this.onerror=null;this.src='https://placehold.co/100x100/CCCCCC/FFFFFF?text=Image';" alt="{{ $article->title }}">
                            <div class="card-body d-flex align-items-center p-3">
                                <h6 class="fw-bold small mb-0">{{ $article->title }}</h6>
                            </div>
                        </div>
                    </a>
                    @endforeach
                </div>
            </div>
        </div>
        @endif

        <div class="row g-5">
            <div class="col-lg-8">
                <h4 class="fw-bold mb-4">{{ request()->filled('search') ? 'Hasil Ditemukan' : 'Lainnya dari Blog Kami' }}</h4>
                @forelse($latestPosts as $post)
                    <div class="card post-card mb-4 shadow-sm">
                        <div class="row g-0">
                            <div class="col-md-4">
                                <a href="{{ route('blog.posts.show', $post->slug) }}">
                                    {{-- PERBAIKAN 3: Tambahkan prefix 'storage/' --}}
                                    <img src="{{ asset('/storage/' . $post->featured_image) }}" class="img-fluid rounded-start h-100 post-card-img" onerror="this.onerror=null;this.src='https://placehold.co/400x250/CCCCCC/FFFFFF?text=Image';" alt="{{ $post->title }}">
                                </a>
                            </div>
                            <div class="col-md-8">
                                <div class="card-body">
                                    <h5 class="card-title fw-bold">
                                        <a href="{{ route('blog.posts.show', $post->slug) }}" class="text-decoration-none text-dark">{{ $post->title }}</a>
                                    </h5>
                                    <p class="card-text text-muted small">{{ Str::limit(strip_tags($post->content), 500) }}</p>
                                    <div class="d-flex justify-content-between align-items-center mt-3">
                                        <small class="text-muted">{{ $post->created_at->diffForHumans() }} by {{ $post->author->nama_lengkap ?? 'Admin' }}</small>
                                        <a href="{{ route('blog.posts.show', $post->slug) }}" class="btn btn-sm btn-outline-primary">Baca Selengkapnya</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="alert alert-warning">
                        Tidak ada artikel yang cocok dengan pencarian Anda. Silakan coba kata kunci lain.
                    </div>
                @endforelse

                </div>

            </div>
    </div>
</section>

@endsection