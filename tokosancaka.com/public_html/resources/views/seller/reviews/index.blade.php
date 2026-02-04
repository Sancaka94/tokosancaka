@extends('layouts.customer') {{-- Sesuaikan dengan layout dashboard seller/admin Anda --}}

@section('title', 'Ulasan Pembeli')

@section('content')
<div class="container mx-auto px-4 py-6">

    {{-- Header --}}
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Ulasan Toko</h1>
        <p class="text-gray-500 text-sm">Lihat apa kata pembeli tentang produk Anda.</p>
    </div>

    {{-- Statistik Ringkas --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center">
            <div class="p-3 bg-blue-50 rounded-full text-blue-600 mr-4">
                <i class="fas fa-comments text-2xl"></i>
            </div>
            <div>
                <p class="text-gray-500 text-sm">Total Ulasan Masuk</p>
                <h3 class="text-2xl font-bold text-gray-800">{{ number_format($totalReviews) }}</h3>
            </div>
        </div>

        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center">
            <div class="p-3 bg-yellow-50 rounded-full text-yellow-500 mr-4">
                <i class="fas fa-star text-2xl"></i>
            </div>
            <div>
                <p class="text-gray-500 text-sm">Rata-rata Rating Toko</p>
                <h3 class="text-2xl font-bold text-gray-800">
                    {{ number_format($avgRating, 1) }} <span class="text-sm font-normal text-gray-400">/ 5.0</span>
                </h3>
            </div>
        </div>
    </div>

    {{-- Daftar Ulasan --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
            <h2 class="font-semibold text-gray-700">Daftar Ulasan Terbaru</h2>
        </div>

        <div class="divide-y divide-gray-100">
            @forelse($reviews as $review)
            <div class="p-6 hover:bg-gray-50 transition duration-150">
                <div class="flex flex-col md:flex-row gap-6">
                    
                    {{-- Kolom 1: Info Produk (Kiri) --}}
                    <div class="md:w-1/4 flex-shrink-0">
                        <div class="flex items-start gap-3">
                            {{-- Gambar Produk Kecil --}}
                            @php
                                $prodImg = $review->product->image_url 
                                    ? asset('public/storage/' . $review->product->image_url) 
                                    : 'https://placehold.co/100x100/EFEFEF/AAAAAA?text=Produk';
                            @endphp
                            <img src="{{ $prodImg }}" alt="Produk" class="w-16 h-16 object-cover rounded-md border border-gray-200">
                            
                            <div>
                                <p class="text-xs text-gray-500 mb-1">Produk:</p>
                                <a href="{{ route('etalase.show', $review->product->slug) }}" target="_blank" class="text-sm font-medium text-blue-600 hover:underline line-clamp-2 leading-snug">
                                    {{ $review->product->name }}
                                </a>
                            </div>
                        </div>
                    </div>

                    {{-- Kolom 2: Isi Review & Info Pembeli (Kanan) --}}
                    <div class="md:w-3/4">
                        <div class="flex justify-between items-start mb-2">
                            {{-- Profil Pembeli --}}
                            <div class="flex items-start gap-3">
                                @php
                                    $avatarPath = $review->user->store_logo_path ?? null; 
                                    $avatarUrl = $avatarPath 
                                        ? asset('public/storage/'.$avatarPath) 
                                        : 'https://ui-avatars.com/api/?name='.urlencode($review->user->nama_lengkap).'&background=random&color=fff&size=64';
                                @endphp
                                <img src="{{ $avatarUrl }}" class="w-9 h-9 rounded-full border border-gray-200 mt-1">
                                
                                <div>
                                    <div class="flex items-center gap-2 mb-0.5">
                                        <p class="text-sm font-bold text-gray-800">{{ $review->user->nama_lengkap }}</p>
                                        {{-- BADGE PEMBELI --}}
                                        <span class="bg-green-100 text-green-700 text-[10px] px-2 py-0.5 rounded-full font-bold border border-green-200">
                                            Customer
                                        </span>
                                    </div>
                                    
                                    {{-- LOKASI LENGKAP (KOTA & PROVINSI) --}}
                                    <p class="text-[11px] text-gray-500 leading-tight">
                                        @if($review->user->regency)
                                            {{ $review->user->regency }}
                                        @endif
                                        
                                        @if($review->user->regency && $review->user->province)
                                            , 
                                        @endif
                                        
                                        @if($review->user->province)
                                            {{ $review->user->province }}
                                        @endif
                                        
                                        @if(!$review->user->regency && !$review->user->province)
                                            Lokasi tidak diketahui
                                        @endif
                                        
                                        <span class="mx-1 text-gray-300">•</span> 
                                        {{ $review->created_at->diffForHumans() }}
                                    </p>
                                </div>
                            </div>

                            {{-- Bintang --}}
                            <div class="flex items-center bg-yellow-50 px-2 py-1 rounded-lg border border-yellow-100">
                                <span class="text-yellow-500 text-sm mr-1">{{ $review->rating }}.0</span>
                                <div class="flex text-yellow-400 text-xs">
                                    @for($i = 1; $i <= 5; $i++)
                                        <i class="fas fa-star {{ $i <= $review->rating ? '' : 'text-gray-200' }}"></i>
                                    @endfor
                                </div>
                            </div>
                        </div>

                        {{-- Komentar Text --}}
                        <div class="mt-2 bg-gray-50 p-3 rounded-lg border border-dashed border-gray-200">
                            <p class="text-sm text-gray-600 italic">"{{ $review->comment }}"</p>
                        </div>

                        {{-- === FITUR BALASAN === --}}
<div class="mt-4" x-data="{ openReply: false, isEditing: false }">
    
    @if($review->reply)
        {{-- TAMPILAN BALASAN (Mode Lihat) --}}
        <div x-show="!isEditing">
            <div class="ml-4 pl-4 border-l-2 border-indigo-200">
                <div class="bg-indigo-50 p-3 rounded-r-lg rounded-bl-lg relative group">
                    
                    {{-- Header Balasan --}}
                    <div class="flex justify-between items-start mb-1">
                        <p class="text-xs font-bold text-indigo-700">
                            <i class="fas fa-store mr-1"></i> Respon Penjual
                            <span class="text-gray-400 font-normal ml-1">• {{ \Carbon\Carbon::parse($review->reply_at)->diffForHumans() }}</span>
                        </p>
                        
                        {{-- Tombol Aksi (Edit & Hapus) - Muncul saat hover --}}
                        <div class="flex gap-2 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                            {{-- Tombol Edit --}}
                            <button @click="isEditing = true" class="text-gray-400 hover:text-blue-600" title="Edit Balasan">
                                <i class="fas fa-pencil-alt text-xs"></i>
                            </button>
                            
                            {{-- Tombol Hapus --}}
                            <form action="{{ route('seller.reviews.reply.delete', $review->id) }}" method="POST" onsubmit="return confirm('Hapus balasan ini?');" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-gray-400 hover:text-red-600" title="Hapus Balasan">
                                    <i class="fas fa-trash text-xs"></i>
                                </button>
                            </form>
                        </div>
                    </div>

                    {{-- Isi Balasan --}}
                    <p class="text-sm text-gray-700 whitespace-pre-line">{{ $review->reply }}</p>
                </div>
            </div>
        </div>

        {{-- FORM EDIT BALASAN (Hidden by default) --}}
        <div x-show="isEditing" class="ml-4 mt-2" x-cloak>
            <form action="{{ route('seller.reviews.reply.update', $review->id) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="relative">
                    <textarea name="reply" rows="3" class="w-full text-sm border-indigo-300 rounded focus:ring-indigo-500 focus:border-indigo-500 bg-indigo-50" required>{{ $review->reply }}</textarea>
                    <div class="flex justify-end gap-2 mt-2">
                        <button type="button" @click="isEditing = false" class="px-3 py-1 text-xs font-medium text-gray-600 bg-white border border-gray-300 rounded hover:bg-gray-50">Batal</button>
                        <button type="submit" class="px-3 py-1 text-xs font-bold text-white bg-indigo-600 rounded hover:bg-indigo-700">Simpan Perubahan</button>
                    </div>
                </div>
            </form>
        </div>

    @else
        {{-- JIKA BELUM DIBALAS (Tombol & Form Reply Baru) --}}
        <button @click="openReply = !openReply" class="text-sm text-blue-600 hover:text-blue-800 font-medium flex items-center">
            <i class="fas fa-reply mr-1"></i> Balas Ulasan
        </button>

        {{-- Form Balasan Baru --}}
        <div x-show="openReply" class="mt-3 ml-4" x-transition x-cloak>
            <form action="{{ route('seller.reviews.reply', $review->id) }}" method="POST">
                @csrf
                <textarea name="reply" rows="2" class="w-full text-sm border-gray-300 rounded focus:ring-blue-500 focus:border-blue-500" placeholder="Tulis balasan Anda..." required></textarea>
                <div class="mt-2 text-right">
                    <button type="button" @click="openReply = false" class="mr-2 px-3 py-1.5 text-xs text-gray-500 hover:text-gray-700">Batal</button>
                    <button type="submit" class="px-3 py-1.5 bg-blue-600 text-white text-xs font-bold rounded hover:bg-blue-700">Kirim Balasan</button>
                </div>
            </form>
        </div>
    @endif
</div>
{{-- === AKHIR FITUR BALASAN === --}}
                    </div>

                </div>
            </div>
            @empty
            <div class="p-12 text-center">
                <div class="bg-gray-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4 text-gray-400">
                    <i class="far fa-star text-3xl"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-900">Belum ada ulasan</h3>
                <p class="text-gray-500 mt-1">Produk Anda belum mendapatkan ulasan dari pembeli.</p>
            </div>
            @endforelse
        </div>

        {{-- Pagination --}}
        <div class="px-6 py-4 border-t border-gray-100 bg-gray-50">
            {{ $reviews->links() }}
        </div>
    </div>
</div>
@endsection