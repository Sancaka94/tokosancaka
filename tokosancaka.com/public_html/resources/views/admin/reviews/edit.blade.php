@extends('layouts.admin')

@section('title', 'Edit Ulasan')

@section('content')
<div class="container mx-auto px-4 py-6 max-w-lg">
    <div class="bg-white rounded-xl shadow p-6">
        <h2 class="text-lg font-bold mb-4">Edit Konten Ulasan</h2>
        
        <form action="{{ route('admin.reviews.update', $review->id) }}" method="POST">
            @csrf @method('PUT')
            
            <div class="mb-4">
                <label class="block text-sm font-medium mb-1">Rating</label>
                <select name="rating" class="w-full border-gray-300 rounded">
                    @for($i=1; $i<=5; $i++)
                        <option value="{{ $i }}" {{ $review->rating == $i ? 'selected' : '' }}>{{ $i }} Bintang</option>
                    @endfor
                </select>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium mb-1">Komentar</label>
                <textarea name="comment" rows="5" class="w-full border-gray-300 rounded">{{ $review->comment }}</textarea>
            </div>

            <div class="flex justify-end gap-2">
                <a href="{{ route('admin.reviews.index') }}" class="px-4 py-2 bg-gray-200 rounded text-gray-700">Batal</a>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>
@endsection