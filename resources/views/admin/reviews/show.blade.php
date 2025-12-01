@extends('layouts.admin')

@section('title', 'Detail Ulasan')

@section('content')
<div class="container mx-auto px-4 py-6 max-w-4xl">
    <a href="{{ route('admin.reviews.index') }}" class="text-gray-500 hover:text-blue-600 mb-4 inline-block">
        <i class="fas fa-arrow-left"></i> Kembali
    </a>

    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
        {{-- Header Info --}}
        <div class="bg-gray-50 px-6 py-4 border-b flex justify-between items-center">
            <h2 class="font-bold text-gray-800">Detail Ulasan #{{ $review->id }}</h2>
            <span class="text-xs text-gray-500">{{ $review->created_at->format('d F Y, H:i') }}</span>
        </div>

        <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-8">
            {{-- KIRI: Info Review --}}
            <div>
                <h3 class="text-sm font-bold text-gray-400 uppercase tracking-wider mb-3">Info Pembeli & Produk</h3>
                
                <div class="flex items-center mb-4">
                    <img src="{{ $review->user->profile_photo_path ? asset('public/storage/'.$review->user->profile_photo_path) : 'https://ui-avatars.com/api/?name='.urlencode($review->user->name) }}" class="w-12 h-12 rounded-full border mr-3">
                    <div>
                        <p class="font-bold text-gray-800">{{ $review->user->name }}</p>
                        <p class="text-xs text-gray-500">{{ $review->user->email }} | {{ $review->user->no_wa ?? '-' }}</p>
                    </div>
                </div>

                <div class="bg-blue-50 p-3 rounded-lg mb-4">
                    <p class="text-xs text-blue-600 font-bold mb-1">Produk yang diulas:</p>
                    <a href="{{ route('products.show', $review->product->slug) }}" target="_blank" class="text-sm font-medium text-gray-800 hover:underline">
                        {{ $review->product->name }}
                    </a>
                    <p class="text-xs text-gray-500 mt-1">Toko: {{ $review->product->store->name }}</p>
                </div>

                <div class="mb-2">
                    <div class="flex text-yellow-400 text-sm mb-1">
                        @for($i=1; $i<=5; $i++) <i class="fas fa-star {{ $i<=$review->rating ? '' : 'text-gray-300' }}"></i> @endfor
                    </div>
                    <blockquote class="text-gray-700 italic border-l-4 border-gray-300 pl-3 py-1">
                        "{{ $review->comment }}"
                    </blockquote>
                </div>
            </div>

            {{-- KANAN: Form Balasan --}}
            <div class="bg-gray-50 p-6 rounded-xl border border-gray-200">
                <h3 class="text-sm font-bold text-gray-700 mb-3 flex items-center">
                    <i class="fas fa-reply mr-2"></i> Balasan Admin
                </h3>

                @if($review->reply)
                    <div class="bg-white p-4 rounded border border-green-200 mb-4">
                        <p class="text-xs text-gray-400 mb-1">Dibalas pada: {{ \Carbon\Carbon::parse($review->reply_at)->format('d M Y H:i') }}</p>
                        <p class="text-gray-800">{{ $review->reply }}</p>
                    </div>
                    <p class="text-xs text-center text-gray-500">Balasan sudah dikirim. Anda bisa menimpa balasan di bawah ini.</p>
                @endif

                <form action="{{ route('admin.reviews.reply', $review->id) }}" method="POST" class="mt-3">
                    @csrf
                    <div class="mb-3">
                        <label class="block text-xs font-medium text-gray-600 mb-1">Isi Balasan (Akan dikirim ke WA Pembeli & Seller)</label>
                        <textarea name="reply" rows="4" class="w-full border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 text-sm" placeholder="Tulis tanggapan admin di sini..." required>{{ $review->reply }}</textarea>
                    </div>
                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg transition flex justify-center items-center">
                        <i class="fab fa-whatsapp mr-2"></i> Kirim Balasan & Notif WA
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection