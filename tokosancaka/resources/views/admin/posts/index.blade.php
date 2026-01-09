@extends('layouts.admin')

@section('title', 'Semua Postingan')

@section('content')

<main class="p-6 sm:p-10 space-y-6">
    <!-- Header Halaman -->
    <div class="flex flex-col space-y-6 md:space-y-0 md:flex-row justify-between">
        <div class="mr-6">
            <h1 class="text-4xl font-semibold mb-2 text-gray-800">Semua Postingan</h1>
            <h2 class="text-gray-600 ml-0.5">Kelola, cari, dan lihat semua artikel Anda.</h2>
        </div>
        <div class="flex flex-wrap items-start justify-end -mb-3">
            <a href="{{ route('admin.import.wordpress.form') }}" class="inline-flex px-5 py-3 text-white bg-green-500 hover:bg-green-600 rounded-md ml-6 mb-3">
                <i class="fas fa-file-import mr-2"></i>
                Import dari WordPress
            </a>
            <a href="{{ route('admin.posts.create') }}" class="inline-flex px-5 py-3 text-white bg-red-500 hover:bg-red-700 rounded-md ml-6 mb-3">
                <i class="fas fa-plus mr-2"></i>
                Tambah Postingan Baru
            </a>
        </div>
    </div>

    <!-- Bar Pencarian -->
    <div class="bg-white shadow-md rounded-lg p-4">
        <form action="{{ route('admin.posts.index') }}" method="GET">
            <div class="flex items-center">
                <input type="text" name="search" placeholder="Cari berdasarkan judul atau isi konten..." class="w-full px-4 py-2 border rounded-l-md" value="{{ request('search') }}">
                <button type="submit" class="px-4 py-2 text-white bg-blue-500 hover:bg-blue-600 rounded-r-md">
                    <i class="fas fa-search"></i>
                </button>
            </div>
        </form>
    </div>

    <!-- Konten Utama: Tabel Postingan -->
    <div class="bg-white shadow-md rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Judul</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Penulis</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kategori</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse ($posts as $post)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-semibold text-gray-900">{{ Str::limit($post->title, 50) }}</div>
                            <div class="text-xs text-gray-500">Dipublikasikan pada {{ $post->created_at->format('d M Y') }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{{ $post->author->nama_lengkap ?? 'N/A' }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                {{ $post->category->name ?? 'Tanpa Kategori' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($post->status == 'published')
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Published</span>
                            @else
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">Draft</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex items-center justify-center space-x-4">
                                <!-- Tombol Lihat -->
                                <a href="{{ route('admin.posts.post-detail', $post->slug) }}" target="_blank" class="text-green-600 hover:text-green-900" title="Lihat Postingan">
                                    <i class="fas fa-eye text-lg"></i>
                                </a>
                                <!-- Tombol Edit -->
                                <a href="{{ route('admin.posts.edit', $post->slug) }}" class="text-indigo-600 hover:text-indigo-900" title="Edit Postingan">
                                    <i class="fas fa-pencil-alt text-lg"></i>
                                </a>
                                <!-- Tombol Hapus -->
                                <form action="{{ route('admin.posts.destroy', $post->slug) }}" method="POST" class="inline-block" onsubmit="return confirm('Apakah Anda yakin ingin menghapus postingan ini?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-900" title="Hapus Postingan">
                                        <i class="fas fa-trash text-lg"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">
                            Tidak ada postingan yang cocok dengan pencarian Anda.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 bg-white border-t border-gray-200">
            {{ $posts->appends(request()->query())->links() }}
        </div>
    </div>
</main>

@endsection

