@extends('layouts.app') 

@section('title', 'Arsip Blog - Sancaka Express')

@section('content')
{{-- PERBAIKAN: Menambahkan padding atas agar tidak tertutup header --}}
<div class="container py-5" style="padding-top: 6rem !important;">
    <div class="row justify-content-center">
        <div class="col-lg-10">

            <h1 class="text-center mb-4">Arsip Blog</h1>
            <p class="text-center text-muted mb-5">Berikut adalah daftar semua postingan terbaru dari blog Sancaka Express.</p>

            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col">Judul Artikel</th>
                                    <th scope="col">Kategori</th>
                                    <th scope="col">Penulis</th>
                                    <th scope="col" class="text-center">Tanggal</th>
                                    <th scope="col">Link</th>
                                    <th scope="col" class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($posts as $post)
                                    <tr>
                                        <td>
                                            <a href="{{ route('blog.posts.show', $post->slug) }}" class="fw-bold text-decoration-none text-dark">
                                                {{ $post->title }}
                                            </a>
                                        </td>
                                        <td>
                                            <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill">{{ $post->category->name ?? 'Umum' }}</span>
                                        </td>
                                        <td>{{ $post->author->nama_lengkap ?? 'Admin' }}</td>
                                        <td class="text-center text-muted small">{{ $post->created_at->format('d M Y') }}</td>
                                        {{-- PERBAIKAN: Menambahkan kolom untuk link --}}
                                        <td>
                                            <a href="{{ route('blog.posts.show', $post->slug) }}" target="_blank" class="small text-muted text-decoration-none">
                                                {{ Str::limit($post->slug, 20) }}
                                            </a>
                                        </td>
                                        <td class="text-end">
                                            <a href="{{ route('blog.posts.show', $post->slug) }}" class="btn btn-sm btn-outline-primary">
                                                Baca
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        {{-- PERBAIKAN: Menyesuaikan colspan --}}
                                        <td colspan="6" class="text-center text-muted py-4">Belum ada postingan untuk ditampilkan.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection

