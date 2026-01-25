@extends('layouts.blog')

@section('title')
    Hasil Pencarian: {{ request('search') }}
@endsection

@section('content')
<div class="container py-5">
    {{-- HEADER PENCARIAN --}}
    <div class="row justify-content-center mb-5">
        <div class="col-lg-8 text-center">
            <h1 class="fw-bold" style="font-family: 'IBM Plex Serif', serif;">
                Hasil Pencarian
            </h1>
            <p class="text-muted">
                Menampilkan artikel untuk kata kunci: <span class="text-danger fw-bold">"{{ request('search') }}"</span>
            </p>

            {{-- FORM PENCARIAN ULANG --}}
            <div class="mt-4">
                <form action="{{ route('blog.index') }}" method="GET" class="d-flex gap-2 justify-content-center">
                    <input type="text" name="search" class="form-control w-50"
                           placeholder="Cari artikel lain..." value="{{ request('search') }}">
                    <button type="submit" class="btn btn-danger" style="background-color: #dd0017;">
                        CARI
                    </button>
                </form>
            </div>
        </div>
    </div>

    <hr class="mb-5">

    {{-- DAFTAR HASIL --}}
    <div class="row">
        @forelse($latestPosts as $post)
            <div class="col-md-4 mb-4">
                <div class="card h-100 shadow-sm border-0">
                    <a href="{{ route('blog.posts.show', $post->slug) }}">
                        <img src="{{ asset('/storage/' . $post->featured_image) }}"
                             class="card-img-top" style="height: 200px; object-fit: cover;"
                             onerror="this.src='https://placehold.co/600x400?text=No+Image'">
                    </a>
                    <div class="card-body">
                        <span class="badge bg-danger mb-2" style="font-size: 10px;">
                            {{ $post->category->name ?? 'Info' }}
                        </span>
                        <h5 class="card-title fw-bold" style="font-size: 16px;">
                            <a href="{{ route('blog.posts.show', $post->slug) }}" class="text-dark text-decoration-none">
                                {{ Str::limit($post->title, 60) }}
                            </a>
                        </h5>
                        <p class="card-text text-muted small">
                            {{ Str::limit(strip_tags($post->content), 100) }}
                        </p>
                    </div>
                    <div class="card-footer bg-white border-0 pb-3">
                        <small class="text-muted">{{ $post->created_at->format('d M Y') }}</small>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12 text-center py-5">
                <i class="fas fa-search-minus fa-4x text-muted mb-3"></i>
                <h4 class="text-muted">Tidak ada artikel yang cocok dengan "{{ request('search') }}"</h4>
                <a href="{{ route('blog.index') }}" class="btn btn-outline-secondary mt-3">Kembali ke Beranda Blog</a>
            </div>
        @endforelse
    </div>

    {{-- PAGINATION --}}
    <div class="d-flex justify-content-center mt-5">
        {{ $latestPosts->withQueryString()->links('pagination::bootstrap-5') }}
    </div>
</div>

{{-- LOG LOG --}}
@endsection
