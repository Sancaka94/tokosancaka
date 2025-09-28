@extends('layouts.admin')

@section('title', 'Detail Postingan: ' . $post->title)

@section('content')

<main class="p-6 sm:p-10 space-y-6">
    <!-- Header Halaman -->
    <div class="flex flex-col space-y-6 md:space-y-0 md:flex-row justify-between">
        <div class="mr-6">
            <h1 class="text-4xl font-semibold mb-2 text-gray-800 dark:text-gray-200">{{ $post->title }}</h1>
            <h2 class="text-gray-600 dark:text-gray-400 ml-0.5">Detail lengkap untuk postingan ini.</h2>
        </div>
        <div class="flex flex-wrap items-start justify-end -mb-3">
            <a href="{{ route('admin.posts.index') }}" class="inline-flex px-5 py-3 text-white bg-gray-500 hover:bg-gray-600 rounded-md ml-6 mb-3">
                <i class="fas fa-arrow-left mr-2"></i>
                Kembali ke Semua Postingan
            </a>
        </div>
    </div>

    <!-- Konten Utama: Detail Postingan -->
    <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg p-8">
        <article class="prose dark:prose-invert max-w-none">
            <!-- Meta Postingan -->
            <div class="mb-6 text-sm text-gray-500 dark:text-gray-400">
                <span>Ditulis oleh: <strong>{{ $post->author->nama_lengkap ?? 'N/A' }}</strong></span>
                <span class="mx-2">|</span>
                <span>Kategori: <strong>{{ $post->category->name ?? 'Tanpa Kategori' }}</strong></span>
                <span class="mx-2">|</span>
                <span>Dipublikasikan pada: <strong>{{ $post->created_at->format('d F Y') }}</strong></span>
            </div>

            <!-- Gambar Unggulan -->
            @if($post->featured_image)
                <div class="mb-8">
                    <img src="{{ asset('storage/' . $post->featured_image) }}" alt="{{ $post->title }}" class="w-full h-auto rounded-lg shadow-md">
                </div>
            @endif

            <!-- Isi Konten Postingan -->
            <div>
                {!! $post->content !!}
            </div>

            <!-- Tampilkan Tags -->
            @if($post->tags->isNotEmpty())
                <div class="mt-8 border-t pt-4 dark:border-gray-600">
                    <h3 class="text-lg font-semibold mb-2 text-gray-700 dark:text-gray-300">Tags:</h3>
                    <div class="flex flex-wrap gap-2">
                        @foreach($post->tags as $tag)
                            <span class="bg-blue-100 text-blue-800 text-xs font-semibold mr-2 px-2.5 py-0.5 rounded dark:bg-blue-200 dark:text-blue-800">{{ $tag->name }}</span>
                        @endforeach
                    </div>
                </div>
            @endif
        </article>
    </div>
</main>

@endsection
