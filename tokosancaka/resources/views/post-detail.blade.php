<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    {{-- Judul halaman akan dinamis sesuai judul postingan --}}
    <title>{{ $post->title }} - Sancaka Express</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Roboto', sans-serif; background-color: #f7fafc; }
        .category-title { background-color: #333; color: white; padding: 8px 15px; display: inline-block; font-size: 0.875rem; font-weight: 700; text-transform: uppercase; }
        /* Styling untuk konten dari editor WYSIWYG */
        .prose img { border-radius: 0.5rem; margin-top: 1.5em; margin-bottom: 1.5em; }
        .prose h1, .prose h2, .prose h3 { font-weight: 700; margin-top: 1.5em; margin-bottom: 0.75em; }
        .prose p { margin-bottom: 1.25em; line-height: 1.7; }
        .prose ul, .prose ol { margin-left: 1.5rem; margin-bottom: 1.25em; }
        .prose a { color: #8b5cf6; text-decoration: underline; }
    </style>
</head>
<body class="bg-gray-100 flex flex-col min-h-screen">

{{-- Asumsi header ini adalah bagian dari layout utama Anda --}}
<header class="bg-white shadow-sm">
    <div class="bg-gray-800 text-gray-400 text-xs">
        <div class="container mx-auto px-4 py-2 flex justify-between items-center">
            <span>{{ \Carbon\Carbon::now()->translatedFormat('l, d F Y') }}</span>
            <div class="flex space-x-4">
                <a href="#" class="hover:text-white">About</a>
                <a href="#" class="hover:text-white">Contact</a>
            </div>
        </div>
    </div>
    <nav class="container mx-auto px-4 py-4 flex justify-between items-center">
        <a href="{{ route('home') }}" class="text-3xl font-black text-gray-800">SANCAKA</a>
        <div class="hidden lg:flex items-center space-x-6 text-sm font-bold uppercase">
            <a href="{{ route('home') }}" class="hover:text-purple-600">Home</a>
            <a href="{{ route('blog.index') }}" class="text-purple-600">Blog</a>
            {{-- CATATAN: Anda perlu mengirimkan variabel $categories dari controller --}}
            @if(isset($categories))
                @foreach($categories->take(4) as $category)
                    <a href="#" class="hover:text-purple-600">{{ $category->name }}</a>
                @endforeach
            @endif
        </div>
        <div class="flex items-center space-x-4">
            <button class="text-gray-500 hover:text-purple-600"><i class="fas fa-search"></i></button>
            <button class="lg:hidden text-gray-500 hover:text-purple-600"><i class="fas fa-bars"></i></button>
        </div>
    </nav>
</header>

<main class="container mx-auto px-4 py-8 flex-grow">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

        {{-- Kolom Konten Utama untuk Detail Postingan --}}
        <div class="lg:col-span-2 bg-white p-6 shadow-md rounded-lg">
            <h1 class="text-3xl font-bold text-gray-800 mb-2">{{ $post->title }}</h1>
            <div class="text-sm text-gray-500 mb-4">
                <span>Diposting pada {{ $post->created_at->format('d M Y') }}</span>
                @if($post->category)
                    <span>dalam <a href="#" class="text-purple-600">{{ $post->category->name }}</a></span>
                @endif
            </div>

            @if($post->featured_image)
                <img src="{{ asset('storage/' . $post->featured_image) }}" alt="{{ $post->title }}" class="w-full h-auto object-cover mb-6 rounded">
            @endif
            
            <div class="prose max-w-none">
                {!! $post->content !!}
            </div>
        </div>

        {{-- Kolom Sidebar --}}
        <aside>
            <div class="bg-white p-4 shadow-md rounded-lg sticky top-24">
                {{-- ✅ PERBAIKAN: Menampilkan semua artikel lainnya di sidebar --}}
                {{-- CATATAN: Anda perlu mengirimkan variabel $otherPosts dari controller --}}
                @if(isset($otherPosts) && $otherPosts->isNotEmpty())
                <div>
                    <h3 class="category-title mb-4">Artikel Lainnya</h3>
                    <ul class="space-y-4">
                        @foreach($otherPosts as $otherPost)
                        <li>
                            <a href="{{ route('posts.show', $otherPost->slug) }}" class="flex items-center space-x-3 group">
                                <img src="{{ $otherPost->featured_image ? asset('storage/' . $otherPost->featured_image) : 'https://placehold.co/80x60/cccccc/ffffff?text=Img' }}" alt="{{ $otherPost->title }}" class="w-20 h-16 object-cover rounded">
                                <div>
                                    <h4 class="font-semibold text-sm text-gray-700 group-hover:text-purple-600">{{ $otherPost->title }}</h4>
                                    <small class="text-gray-500 text-xs">{{ $otherPost->created_at->format('d M Y') }}</small>
                                </div>
                            </a>
                        </li>
                        @endforeach
                    </ul>
                </div>
                @else
                <div class="text-sm text-gray-500">Tidak ada artikel lainnya.</div>
                @endif
            </div>
        </aside>
    </div>
</main>

{{-- Asumsi footer ini adalah bagian dari layout utama Anda --}}
<footer class="bg-gray-800 text-gray-400 mt-auto">
    <div class="container mx-auto px-4 py-8">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
            <div>
                <h4 class="font-bold text-white mb-3 text-lg">About Sancaka</h4>
                <p class="text-sm">Portal berita dan tutorial seputar teknologi dan bisnis, didukung oleh Sancaka Express.</p>
            </div>
            <div>
                <h4 class="font-bold text-white mb-3 text-lg">Latest News</h4>
                <ul class="space-y-2 text-sm">
                    {{-- CATATAN: Anda perlu mengirimkan variabel $recentPosts dari controller --}}
                    @if(isset($recentPosts))
                        @foreach($recentPosts->take(3) as $post)
                        <li><a href="{{ route('posts.show', $post->slug) }}" class="hover:text-white">{{ $post->title }}</a></li>
                        @endforeach
                    @endif
                </ul>
            </div>
            <div>
                <h4 class="font-bold text-white mb-3 text-lg">Tag Cloud</h4>
                <div class="flex flex-wrap gap-2">
                    {{-- CATATAN: Anda perlu mengirimkan variabel $categories dari controller --}}
                    @if(isset($categories))
                        @foreach($categories as $category)
                        <a href="#" class="bg-gray-700 text-xs px-2 py-1 rounded hover:bg-purple-600">{{ $category->name }}</a>
                        @endforeach
                    @endif
                </div>
            </div>
            <div>
                <h4 class="font-bold text-white mb-3 text-lg">Social Media</h4>
                <div class="flex space-x-4">
                    <a href="#" class="hover:text-white"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="hover:text-white"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="hover:text-white"><i class="fab fa-instagram"></i></a>
                </div>
            </div>
        </div>
        <div class="border-t border-gray-700 mt-8 pt-6 text-center text-sm">
            <p>&copy; {{ date('Y') }} Sancaka Express. All Rights Reserved.</p>
        </div>
    </div>
</footer>

</body>
</html>
