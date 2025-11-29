@extends('layouts.admin')

@section('title', 'Manajemen Slider')
@section('page-title', 'Manajemen Slider')

@section('content')

{{-- Notifikasi --}}
@if(session('success'))
<div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
     x-transition:leave="transition ease-in duration-300"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="flex items-center p-4 mb-4 text-sm rounded-lg border bg-green-50 text-green-800 border-green-200" role="alert">
    <i class="fa-solid fa-check-circle w-5 h-5"></i>
    <div class="ml-3 font-medium">
        {{ session('success') }}
    </div>
    <button @click="show = false" type="button" class="ml-auto -mx-1.5 -my-1.5 rounded-lg focus:ring-2 p-1.5 inline-flex items-center justify-center h-8 w-8 bg-green-50 text-green-500 hover:bg-green-200 focus:ring-green-400" aria-label="Close">
        <i class="fa-solid fa-times"></i>
    </button>
</div>
@endif
@if(session('error'))
<div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
     x-transition:leave="transition ease-in duration-300"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="flex items-center p-4 mb-4 text-sm rounded-lg border bg-red-50 text-red-800 border-red-200" role="alert">
    <i class="fa-solid fa-exclamation-triangle w-5 h-5"></i>
    <div class="ml-3 font-medium">
        {{ session('error') }}
    </div>
    <button @click="show = false" type="button" class="ml-auto -mx-1.5 -my-1.5 rounded-lg focus:ring-2 p-1.5 inline-flex items-center justify-center h-8 w-8 bg-red-50 text-red-500 hover:bg-red-200 focus:ring-red-400" aria-label="Close">
        <i class="fa-solid fa-times"></i>
    </button>
</div>
@endif
@if ($errors->any())
    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-md shadow-md" role="alert">
        <p class="font-bold">Terjadi Kesalahan:</p>
        <ul>
            @foreach ($errors->all() as $error)
                <li>- {{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="container mx-auto px-4 sm:px-8 py-8">

    {{-- 
      Blok Pengaturan Slider Informasi 
      [PERBAIKAN] 'x-show' dihapus karena ini adalah halaman khusus, bukan tab.
    --}}
    <div x-cloak x-data='{ slides: @json($slides ?? []), activeSlide: 0 }'>
    
        <div class="bg-white p-6 rounded-lg shadow-lg">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Pengaturan Slider Informasi</h3>
            <p class="text-sm text-gray-600 mb-6">
                Atur gambar dan teks yang akan ditampilkan di slider dashboard admin.
            </p>

            <form action="{{ route('admin.settings.slider.update') }}" method="POST">
                @csrf
                @method('PUT')

                <div class="space-y-6">
                    <template x-for="(slide, index) in slides" :key="index">
                        <div class="p-4 border rounded-md">
                            <h4 class="font-medium mb-2" x-text="'Slide ' + (index + 1)"></h4>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">URL Gambar</label>
                                    <input type="url"
                                       :name="'slides[' + index + '][img]'"
                                       x-model="slide.img"
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                                       placeholder="https://... atau /storage/path/gambar.jpg">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Judul (Alt Text)</label>
                                    <input type="text"
                                       :name="'slides[' + index + '][title]'"
                                       x-model="slide.title"
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                </div>

                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700">Deskripsi</label>
                                    <textarea
                                       :name="'slides[' + index + '][desc]'"
                                       x-model="slide.desc"
                                       rows="2"
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"></textarea>
                                </div>
                            </div>

                            <div class="text-right mt-2">
                                <button type="button"
                                    @click="slides.splice(index, 1)"
                                    class="text-sm text-red-600 hover:text-red-800">
                                    Hapus Slide
                                </button>
                            </div>
                        </div>
                    </template>
                </div>

                <div class="mt-6 flex justify-between items-center">
                    <button type="button"
                        @click="slides.push({ img: '', title: '', desc: '' })"
                        class="py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                        Tambah Slide
                    </button>

                    <button type="submit"
                        class="py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700">
                        Simpan Pengaturan Slider
                    </button>
                </div>
            </form>

            {{-- Live Preview Slider (menggunakan kode dari partial) --}}
            <div class="mt-8 pt-6 border-t border-gray-200">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Live Preview Slider</h3>
                <p class="text-sm text-gray-600 mb-6">
                    Slider ini akan otomatis diperbarui saat Anda mengubah URL gambar atau teks di formulir atas.
                </p>
                    
                <div id="customer-slider-preview" 
                     class="relative w-full max-w-7xl mx-auto rounded-lg shadow-lg overflow-hidden">
                    
                    {{-- Pesan jika kosong --}}
                    <template x-if="slides.length === 0">
                        <div class="w-full flex justify-center items-center py-20 bg-gray-100" style="aspect-ratio: 16/9;">
                            <p class="text-gray-500">Tambahkan slide di atas untuk melihat preview.</p>
                        </div>
                    </template>
            
                    {{-- Slider jika ada isi --}}
                    <template x-if="slides.length > 0">
                        {{-- [PERBAIKAN] Menggunakan aspect-ratio untuk layout yang konsisten --}}
                        <div class="relative w-full overflow-hidden" style="aspect-ratio: 16/9;">
                            <div class="flex transition-transform duration-700 ease-in-out h-full"
                                 :style="`transform: translateX(-${activeSlide * 100}%);`">
            
                                <template x-for="(slide, index) in slides" :key="index">
                                    <div class="w-full flex-shrink-0 flex justify-center items-center relative bg-gray-100 h-full">
                                        
                                        {{-- Background blur --}}
                                        <div class="absolute inset-0 blur-lg scale-110 opacity-30"
                                             :style="`background-image:url('${slide.img}'); background-size:cover; background-position:center;`"
                                             aria-hidden="true">
                                        </div>
            
                                        {{-- Gambar utama --}}
                                        {{-- [PERBAIKAN] Class 'h-full' agar gambar mengisi kotak 16:9 --}}
                                        <img :src="slide.img"
                                             :alt="slide.title || 'Informasi'"
                                             class="relative h-full w-auto object-contain z-10"
                                             onerror="this.src='https://placehold.co/1280x720/ef4444/white?text=URL+Gambar+Error'; this.classList.add('bg-white');">
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
            
                    {{-- [PERBAIKAN] Tombol Navigasi Kiri/Kanan --}}
                    <template x-if="slides.length > 1">
                        <div class="absolute inset-0 flex justify-between items-center px-4 z-20">
                            <button type="button"
                                    @click="activeSlide = (activeSlide - 1 + slides.length) % slides.length"
                                    class="bg-white/50 hover:bg-white text-gray-800 rounded-full w-10 h-10 flex items-center justify-center shadow-md transition-all duration-300 ease-in-out focus:outline-none focus:ring-2 focus:ring-white">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                            </button>
                            <button type="button"
                                    @click="activeSlide = (activeSlide + 1) % slides.length"
                                    class="bg-white/50 hover:bg-white text-gray-800 rounded-full w-10 h-10 flex items-center justify-center shadow-md transition-all duration-300 ease-in-out focus:outline-none focus:ring-2 focus:ring-white">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                            </button>
                        </div>
                    </template>
            
                    {{-- [PERBAIKAN] Indikator Titik (Dots) --}}
                    <template x-if="slides.length > 1">
                        <div class="absolute bottom-4 left-0 w-full flex justify-center gap-2 z-20">
                            <template x-for="(slide, index) in slides" :key="index">
                                <button type="button"
                                        @click="activeSlide = index"
                                        :class="{'bg-white scale-110': activeSlide === index, 'bg-white/50': activeSlide !== index}"
                                        class="w-3 h-3 rounded-full transition-all duration-300 shadow"></button>
                            </template>
                        </div>
                    </template>
                    
                </div>
            </div>

        </div>

        

    </div>
</div>
@endsection