{{-- Asumsikan Anda memiliki layout utama yang sudah terhubung dengan Tailwind CSS --}}
@extends('layouts.admin') 

@section('title', 'Edit Postingan')

@section('content')
<div class="bg-gray-100 p-4 sm:p-6 lg:p-8 min-h-screen">
    <div class="max-w-4xl mx-auto">
        
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-800">Edit Postingan</h1>
            <a href="{{ route('admin.posts.index') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">
                &larr; Kembali ke Daftar Postingan
            </a>
        </div>

        {{-- Menampilkan pesan error validasi --}}
        @if ($errors->any())
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6" role="alert">
                <strong class="font-bold">Oops!</strong>
                <span class="block sm:inline">Ada beberapa masalah dengan input Anda.</span>
                <ul class="mt-2 list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <form action="{{ route('admin.posts.update', $post->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="p-6">
                    {{-- Judul Postingan --}}
                    <div class="mb-6">
                        <label for="title" class="block text-sm font-medium text-gray-700 mb-1">Judul</label>
                        <input type="text" name="title" id="title" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" value="{{ old('title', $post->title) }}" required>
                    </div>

                    {{-- Konten Postingan dengan TinyMCE --}}
                    <div class="mb-6">
                        <label for="content" class="block text-sm font-medium text-gray-700 mb-1">Konten</label>
                        {{-- Textarea ini akan diubah menjadi editor TinyMCE --}}
                        <textarea name="content" id="content" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" rows="12">{{ old('content', $post->content) }}</textarea>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        {{-- Kategori --}}
                        <div class="mb-6">
                            <label for="category_id" class="block text-sm font-medium text-gray-700 mb-1">Kategori</label>
                            <select id="category_id" name="category_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" required>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" {{ old('category_id', $post->category_id) == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Tags --}}
                        <div class="mb-6">
                            <label for="tags" class="block text-sm font-medium text-gray-700 mb-1">Tag (Tahan Ctrl/Cmd untuk memilih lebih dari satu)</label>
                            <select multiple id="tags" name="tags[]" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm h-32">
                                @foreach($tags as $tag)
                                    <option value="{{ $tag->id }}" {{ in_array($tag->id, old('tags', $post->tags->pluck('id')->toArray())) ? 'selected' : '' }}>
                                        {{ $tag->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    {{-- Gambar Unggulan --}}
                    <div class="mb-6">
                        <label for="featured_image" class="block text-sm font-medium text-gray-700 mb-1">Gambar Unggulan</label>
                        <input type="file" name="featured_image" id="featured_image" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                        <p class="text-xs text-gray-500 mt-1">Kosongkan jika tidak ingin mengubah gambar.</p>
                        
                        @if($post->featured_image)
                            <div class="mt-4">
                                <p class="text-sm font-medium text-gray-700">Gambar saat ini:</p>
                                <img src="{{ asset('storage/' . $post->featured_image) }}" alt="Gambar Unggulan" class="mt-2 rounded-md object-cover" style="max-height: 150px;">
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Tombol Aksi --}}
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm">
                        Update Postingan
                    </button>
                    <a href="{{ route('admin.posts.index') }}" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:w-auto sm:text-sm">
                        Batal
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
{{-- ✅ PERBAIKAN: Menggunakan API Key dari file .env --}}
<script src="https://cdn.tiny.cloud/1/{{ env('TINYMCE_API_KEY') }}/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>

<script>
  tinymce.init({
    selector: 'textarea#content',
    plugins: 'code table lists link image autolink fullscreen',
    toolbar: 'undo redo | blocks | bold italic | alignleft aligncenter alignright | indent outdent | bullist numlist | code | table | link image | fullscreen',
    height: 500
  });
</script>
@endpush
