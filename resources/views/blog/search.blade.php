@extends('layouts.blog')

@section('title')
    Hasil Pencarian: {{ request('search') }}
@endsection

@section('content')
<style>
    /* Menjamin bingkai gambar tetap kotak 1:1 */
    .img-container-1to1 {
        position: relative;
        width: 100%;
        padding-top: 100%; /* Rasio 1:1 */
        overflow: hidden;
        background-color: #f8f9fa;
    }

    .img-container-1to1 img {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        object-fit: contain; /* Gambar utuh, tidak terpotong */
        padding: 5px;
    }
</style>

@include('blog.partials.ticker')

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

            <div class="mt-4">
                <form action="{{ url('/blog') }}" method="GET" class="d-flex gap-2 justify-content-center">
                    <input type="text" name="search" class="form-control w-50"
                        placeholder="Cari artikel lain..." value="{{ request('search') }}">

                    {{-- Tombol Biru Baru --}}
                    <button type="submit" class="btn btn-primary" style="background-color: #0051ff; border-color: #0051ff; font-weight: bold;">
                        CARI
                    </button>

                    {{-- Tombol Beranda Biru --}}
                    <a href="{{ url('/blog') }}" class="btn btn-primary" style="background-color: #0051ff; border-color: #0051ff; font-weight: bold;">
                        BERANDA
                    </a>
                </form>

            </div>
        </div>
    </div>

    <hr class="mb-5">

<div id="search-results-wrapper">
        {{-- DAFTAR HASIL --}}
        <div class="row">
            @forelse($latestPosts as $post)
                <div class="col-md-4 mb-4">
                    <div class="card h-100 shadow-sm border-0">
                        <a href="{{ route('blog.posts.show', $post->slug) }}">
                            <div class="img-container-1to1">
                                <img src="{{ asset('/storage/' . $post->featured_image) }}"
                                    alt="{{ $post->title }}"
                                    onerror="this.src='https://placehold.co/800x800?text=No+Image'">
                            </div>
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
                    <a href="{{ url('/blog') }}" class="btn btn-outline-secondary mt-3">Kembali ke Beranda Blog</a>
                </div>
            @endforelse
        </div>

        {{-- PAGINATION --}}
        @if(method_exists($latestPosts, 'links'))
        <div class="d-flex justify-content-center mt-5">
            {{ $latestPosts->withQueryString()->links('pagination::bootstrap-5') }}
        </div>
        @endif


        @include('blog.partials.hero_section')

        {{-- BAGIAN BAWAH (Hanya ticker dan grid jika diperlukan) --}}

        @include('blog.partials.bottom_grid')

    </div>

</div>

{{-- JAVASCRIPT UNTUK AJAX PAGINATION --}}

<script>
document.addEventListener('DOMContentLoaded', function() {
    const wrapper = document.getElementById('search-results-wrapper');

    // Gunakan event delegation agar klik pada elemen yang baru di-load tetap berfungsi
    wrapper.addEventListener('click', function(e) {
        const link = e.target.closest('.pagination a');

        if (link) {
            e.preventDefault();
            const url = link.getAttribute('href');

            // Efek transisi halus (opsional)
            wrapper.style.opacity = '0.5';

            fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.text())
            .then(html => {
                // Parsing HTML yang diterima
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const newContent = doc.getElementById('search-results-wrapper').innerHTML;

                // Ganti konten lama dengan konten baru
                wrapper.innerHTML = newContent;
                wrapper.style.opacity = '1';

                // Geser tampilan kembali ke atas daftar artikel (Smooth Scroll)
                wrapper.scrollIntoView({ behavior: 'smooth', block: 'start' });

                // Update URL di browser tanpa refresh (opsional)
                window.history.pushState({}, '', url);
            })
            .catch(error => {
                console.error('Error fetching pagination:', error);
                wrapper.style.opacity = '1';
            });
        }
    });
});
</script>

{{-- LOG LOG --}}
@endsection
