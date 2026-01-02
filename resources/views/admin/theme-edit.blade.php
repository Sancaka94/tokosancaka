@extends('layouts.admin')

@section('title', 'Edit Tampilan')

@section('content')
<div class="max-w-4xl mx-auto">
    
    <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50">
            <div>
                <h2 class="text-xl font-bold text-gray-800">Pengaturan Tampilan (Theme)</h2>
                <p class="text-sm text-gray-500">Sesuaikan warna dan identitas website Anda.</p>
            </div>
            <div class="flex gap-2">
                <div class="w-6 h-6 rounded-full border border-gray-300" style="background-color: {{ $theme['primary_color'] ?? '#3b82f6' }}" title="Primary"></div>
                <div class="w-6 h-6 rounded-full border border-gray-300" style="background-color: {{ $theme['bg_color'] ?? '#f3f4f6' }}" title="Background"></div>
            </div>
        </div>

        <form action="{{ route('theme.update') }}" method="POST" enctype="multipart/form-data" class="p-6 space-y-6">
            @csrf

            <div>
                <h3 class="text-lg font-semibold text-gray-700 mb-4 flex items-center gap-2">
                    <i class="fas fa-id-card text-blue-500"></i> Identitas Website
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Judul Tab Browser</label>
                        <input type="text" name="site_title" value="{{ $theme['site_title'] ?? '' }}" 
                               class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 transition p-2 border">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Upload Logo Baru</label>
                        <input type="file" name="site_logo" accept="image/*"
                               class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                        @if(isset($theme['site_logo']))
                            <div class="mt-2 text-xs text-gray-500">Logo saat ini: <a href="{{ $theme['site_logo'] }}" target="_blank" class="text-blue-600 underline">Lihat</a></div>
                        @endif
                    </div>
                </div>
            </div>

            <hr class="border-gray-100">

            <div>
                <h3 class="text-lg font-semibold text-gray-700 mb-4 flex items-center gap-2">
                    <i class="fas fa-palette text-purple-500"></i> Skema Warna
                </h3>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    
                    <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                        <label class="block text-sm font-bold text-gray-700 mb-2">Warna Utama (Primary)</label>
                        <div class="flex items-center gap-3">
                            <input type="color" name="primary_color" value="{{ $theme['primary_color'] ?? '#3b82f6' }}" 
                                   class="h-10 w-14 cursor-pointer rounded border border-gray-300 p-1 bg-white">
                            <span class="text-xs text-gray-500">Untuk Navbar, Tombol, & Link aktif.</span>
                        </div>
                    </div>

                    <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                        <label class="block text-sm font-bold text-gray-700 mb-2">Warna Latar (Body)</label>
                        <div class="flex items-center gap-3">
                            <input type="color" name="bg_color" value="{{ $theme['bg_color'] ?? '#f3f4f6' }}" 
                                   class="h-10 w-14 cursor-pointer rounded border border-gray-300 p-1 bg-white">
                            <span class="text-xs text-gray-500">Warna dasar seluruh halaman.</span>
                        </div>
                    </div>

                    <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                        <label class="block text-sm font-bold text-gray-700 mb-2">Warna Teks</label>
                        <div class="flex items-center gap-3">
                            <input type="color" name="text_color" value="{{ $theme['text_color'] ?? '#1f2937' }}" 
                                   class="h-10 w-14 cursor-pointer rounded border border-gray-300 p-1 bg-white">
                            <span class="text-xs text-gray-500">Warna tulisan paragraf standar.</span>
                        </div>
                    </div>

                </div>
            </div>

            <hr class="border-gray-100">

            <div class="flex justify-end pt-4">
                <button type="submit" class="bg-blue-600 text-white px-8 py-3 rounded-lg font-semibold hover:bg-blue-700 focus:ring-4 focus:ring-blue-300 transition shadow-lg transform active:scale-95 flex items-center gap-2">
                    <i class="fas fa-save"></i> Simpan Perubahan
                </button>
            </div>

        </form>
    </div>
</div>
@endsection