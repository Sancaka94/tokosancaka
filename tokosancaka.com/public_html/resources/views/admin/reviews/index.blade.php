@extends('layouts.admin')

@section('title', 'Manajemen Ulasan')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Manajemen Ulasan & Testimoni</h1>
    </div>

    @if(session('success'))
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4" role="alert">
        <p>{{ session('success') }}</p>
    </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-gray-50 text-gray-600 font-medium border-b">
                    <tr>
                        <th class="px-6 py-3">Produk & Toko</th>
                        <th class="px-6 py-3">Pembeli</th>
                        <th class="px-6 py-3">Rating & Ulasan</th>
                        <th class="px-6 py-3 text-center">Status Balasan</th>
                        <th class="px-6 py-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($reviews as $review)
                    <tr class="hover:bg-gray-50 transition">
                        {{-- Produk & Toko --}}
                        <td class="px-6 py-4 max-w-xs">
                            <div class="font-bold text-gray-800 line-clamp-1" title="{{ $review->product->name }}">
                                {{ $review->product->name }}
                            </div>
                            <div class="text-xs text-gray-500 flex items-center mt-1">
                                <i class="fas fa-store mr-1"></i> {{ $review->product->store->store_name ?? 'Toko Online' }}
                            </div>
                        </td>

                        {{-- Pembeli --}}
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                @php
                                    $avatar = $review->user->store_logo_path 
                                        ? asset('public/storage/'.$review->user->store_logo_path) 
                                        : 'https://ui-avatars.com/api/?name='.urlencode($review->user->nama_lengkap);
                                @endphp
                                <img src="{{ $avatar }}" class="w-8 h-8 rounded-full mr-2 border">
                                <div>
                                    <div class="font-medium text-gray-800">{{ $review->user->nama_lengkap }}</div>
                                    <div class="text-xs text-gray-400">{{ $review->created_at->format('d M Y') }}</div>
                                </div>
                            </div>
                        </td>

                        {{-- Rating --}}
                        <td class="px-6 py-4 max-w-md">
                            <div class="flex text-yellow-400 text-xs mb-1">
                                @for($i=1; $i<=5; $i++) <i class="fas fa-star {{ $i<=$review->rating ? '' : 'text-gray-300' }}"></i> @endfor
                            </div>
                            <p class="text-gray-600 line-clamp-2 italic">"{{ $review->comment }}"</p>
                        </td>

                        {{-- Status --}}
                        <td class="px-6 py-4 text-center">
                            @if($review->reply)
                                <span class="px-2 py-1 bg-green-100 text-green-700 rounded-full text-xs font-semibold">Sudah Dibalas</span>
                            @else
                                <span class="px-2 py-1 bg-red-100 text-red-700 rounded-full text-xs font-semibold">Belum Dibalas</span>
                            @endif
                        </td>

                        {{-- Aksi --}}
                        <td class="px-6 py-4 text-center">
                            <div class="flex items-center justify-center space-x-2">
                                {{-- Tombol Lihat/Balas --}}
                                <a href="{{ route('admin.reviews.show', $review->id) }}" class="p-2 bg-blue-50 text-blue-600 rounded hover:bg-blue-100" title="Lihat & Balas">
                                    <i class="fas fa-reply"></i>
                                </a>
                                {{-- Tombol Edit --}}
                                <a href="{{ route('admin.reviews.edit', $review->id) }}" class="p-2 bg-yellow-50 text-yellow-600 rounded hover:bg-yellow-100" title="Edit Ulasan">
                                    <i class="fas fa-edit"></i>
                                </a>
                                {{-- Tombol Hapus --}}
                                <form action="{{ route('admin.reviews.destroy', $review->id) }}" method="POST" onsubmit="return confirm('Hapus ulasan ini selamanya?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="p-2 bg-red-50 text-red-600 rounded hover:bg-red-100" title="Hapus">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-gray-500">Belum ada data ulasan.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t bg-gray-50">
            {{ $reviews->links() }}
        </div>
    </div>
</div>
@endsection