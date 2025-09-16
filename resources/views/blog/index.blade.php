@extends('layouts.blog')

@section('title', 'Berita & Informasi Terbaru')

@section('content')

{{-- ====================================================================== --}}
{{-- == BAGIAN BLOG DENGAN DESAIN BARU == --}}
{{-- ====================================================================== --}}
<section id="blog" class="section">
    <div class="container">
        <h2 class="section-title">Berita & Informasi Terbaru</h2>
        
        <!-- ========== FORM PENCARIAN ========== -->
        <div class="mb-5">
            <form action="{{ url()->current() }}" method="GET" class="max-w-xl mx-auto">
                <div class="input-group input-group-lg">
                    <input type="search" name="search" class="form-control" placeholder="Cari artikel berdasarkan judul atau konten..." value="{{ request('search') }}" aria-label="Cari Artikel">
                    <button class="btn btn-primary px-4" type="submit" id="button-addon2"><i class="fas fa-search"></i></button>
                </div>
            </form>
        </div>
        <!-- ========== AKHIR FORM PENCARIAN ========== -->
        
        {{-- Jika sedang dalam mode pencarian, tampilkan judul hasil pencarian --}}
        @if(request()->has('search') && request()->input('search') != '')
            <h4 class="mb-4 fw-bold">Hasil pencarian untuk: "{{ request('search') }}"</h4>
        @endif

        @if($headline && !request()->filled('search'))
        <!-- Bagian Headline Utama (Hanya tampil jika tidak sedang mencari) -->
        <div class="row g-4 mb-5">
            <!-- Artikel Utama (Kiri) -->
            <div class="col-lg-8">
                <div class="card post-card shadow-sm h-100">
                    <a href="{{ route('blog.posts.show', $headline->slug) }}" class="text-decoration-none text-dark">
                        <img src="{{ Storage::url($headline->featured_image) }}"
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

            <!-- 4 Artikel Kecil (Kanan) -->
            <div class="col-lg-4">
                <div class="d-flex flex-column gap-3 h-100">
                    @foreach($topArticles as $article)
                    <a href="{{ route('blog.posts.show', $article->slug) }}" class="text-decoration-none text-dark">
                        <div class="card post-card shadow-sm flex-row h-100">
                           <img src="{{ Storage::url($article->featured_image) }}" class="w-25" style="aspect-ratio: 1/1; object-fit: cover;" onerror="this.onerror=null;this.src='https://placehold.co/100x100/CCCCCC/FFFFFF?text=Image';" alt="{{ $article->title }}">
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

        <!-- Berita Terbaru & Sidebar Populer -->
        <div class="row g-5">
            <!-- Kolom Berita Terbaru (Kiri) -->
            <div class="col-lg-8">
                <h4 class="fw-bold mb-4">{{ request()->filled('search') ? 'Hasil Ditemukan' : 'Lainnya dari Blog Kami' }}</h4>
                @forelse($latestPosts as $post)
                    <div class="card post-card mb-4 shadow-sm">
                        <div class="row g-0">
                            <div class="col-md-4">
                                <a href="{{ route('blog.posts.show', $post->slug) }}">
                                    <img src="{{ Storage::url($post->featured_image) }}" class="img-fluid rounded-start h-100 post-card-img" onerror="this.onerror=null;this.src='https://placehold.co/400x250/CCCCCC/FFFFFF?text=Image';" alt="{{ $post->title }}">
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

                <div class="d-flex flex-column align-items-center mt-4">
                    {{-- Info jumlah data --}}
                    <div class="mb-2 text-muted">
                        Menampilkan {{ $latestPosts->firstItem() }} - {{ $latestPosts->lastItem() }}
                        dari total {{ $latestPosts->total() }} post
                    </div>

                    {{-- Tombol pagination --}}
                    <div>
                        {{ $latestPosts->appends(request()->query())->links('pagination::bootstrap-5') }}
                    </div>
                </div>
            </div>

            <!-- Sidebar (Kanan) -->
            <div class="col-lg-4">
                <div class="card shadow-sm sticky-top" style="top: 2rem;">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0 fw-bold">Terpopuler</h5>
                    </div>
                    <ul class="list-group list-group-flush">
                        @forelse($popularPosts as $key => $post)
                        <a href="{{ route('blog.posts.show', $post->slug) }}" class="list-group-item list-group-item-action d-flex align-items-center">
                            <span class="fw-bold me-3 fs-4 text-muted">{{ $key + 1 }}</span>
                            <span>{{ $post->title }}</span>
                        </a>
                        @empty
                        <li class="list-group-item">Belum ada artikel populer.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>

@endsection

