@extends('layouts.admin')

@section('title', 'Edit Tampilan')

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-900">Pengaturan Tampilan</h1>
        <p class="mt-1 text-sm text-gray-500">Kustomisasi identitas dan skema warna aplikasi Anda secara real-time.</p>
    </div>

    <div class="bg-white rounded-2xl shadow-xl overflow-hidden border border-gray-100">
        
        <div class="px-6 py-5 border-b border-gray-100 bg-gray-50/50 flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-blue-100 text-blue-600 rounded-lg">
                    <i class="fas fa-paint-brush text-lg"></i>
                </div>
                <div>
                    <h2 class="text-lg font-semibold text-gray-800">Tema & Branding</h2>
                    <p class="text-xs text-gray-500">Perubahan akan langsung diterapkan.</p>
                </div>
            </div>
            
            <div class="flex items-center gap-3 bg-white px-3 py-2 rounded-lg border border-gray-200 shadow-sm">
                <span class="text-xs font-medium text-gray-500">Warna Saat Ini:</span>
                <div class="flex -space-x-2">
                    <div class="w-6 h-6 rounded-full border-2 border-white shadow-sm ring-1 ring-gray-200" 
                         style="background-color: {{ $theme['primary_color'] ?? '#3b82f6' }}" 
                         title="Primary Color"></div>
                    <div class="w-6 h-6 rounded-full border-2 border-white shadow-sm ring-1 ring-gray-200" 
                         style="background-color: {{ $theme['bg_color'] ?? '#f3f4f6' }}" 
                         title="Background Body"></div>
                </div>
            </div>
        </div>

        <form action="{{ route('theme.update') }}" method="POST" enctype="multipart/form-data">
            @csrf
            
            <div class="p-6 sm:p-8 space-y-10">

                <section>
                    <div class="flex items-center gap-2 mb-5">
                        <span class="w-1 h-6 bg-blue-500 rounded-full"></span>
                        <h3 class="text-lg font-medium text-gray-900">Identitas Visual</h3>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div class="group">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Judul Tab Browser</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-globe text-gray-400"></i>
                                </div>
                                <input type="text" name="site_title" value="{{ $theme['site_title'] ?? '' }}" 
                                       placeholder="Contoh: Toko Sancaka"
                                       class="pl-10 block w-full rounded-lg border-gray-300 bg-gray-50 focus:bg-white focus:border-blue-500 focus:ring-blue-500 sm:text-sm transition-all shadow-sm py-2.5">
                            </div>
                            <p class="mt-1 text-xs text-gray-400">Judul yang muncul di tab atas browser.</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Logo Website</label>
                            <div class="flex items-start gap-4">
                                <div class="flex-1">
                                    <input type="file" name="site_logo" accept="image/*"
                                           class="block w-full text-sm text-gray-500
                                                  file:mr-4 file:py-2.5 file:px-4
                                                  file:rounded-full file:border-0
                                                  file:text-sm file:font-semibold
                                                  file:bg-blue-50 file:text-blue-700
                                                  hover:file:bg-blue-100
                                                  cursor-pointer border border-gray-300 rounded-lg bg-gray-50">
                                    <p class="mt-1 text-xs text-gray-400">Format: PNG, JPG (Max. 2MB)</p>
                                </div>
                                
                                @if(isset($theme['site_logo']))
                                    <div class="flex-shrink-0 text-center">
                                        <p class="text-xs text-gray-500 mb-1">Saat ini:</p>
                                        <a href="{{ $theme['site_logo'] }}" target="_blank" class="block w-12 h-12 rounded-lg border border-gray-200 overflow-hidden shadow-sm hover:opacity-75 transition">
                                            <img src="{{ $theme['site_logo'] }}" alt="Logo" class="w-full h-full object-contain bg-gray-100">
                                        </a>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </section>

                <hr class="border-gray-100 border-dashed">

                <section>
                    <div class="flex items-center gap-2 mb-5">
                        <span class="w-1 h-6 bg-purple-500 rounded-full"></span>
                        <h3 class="text-lg font-medium text-gray-900">Palet Warna</h3>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                        
                        <div class="relative bg-white border border-gray-200 rounded-xl p-4 hover:border-blue-300 hover:shadow-md transition-all group">
                            <div class="flex justify-between items-start mb-2">
                                <label class="text-sm font-semibold text-gray-700">Warna Utama</label>
                                <i class="fas fa-check-circle text-blue-500 opacity-0 group-hover:opacity-100 transition"></i>
                            </div>
                            <p class="text-xs text-gray-500 mb-3 h-8">Warna dominan untuk Navbar, Tombol, dan Link.</p>
                            <div class="flex items-center gap-3 bg-gray-50 p-2 rounded-lg border border-gray-100">
                                <input type="color" name="primary_color" value="{{ $theme['primary_color'] ?? '#3b82f6' }}" 
                                       class="h-10 w-10 cursor-pointer rounded border-0 p-0 bg-transparent flex-shrink-0 shadow-sm">
                                <span class="text-xs font-mono text-gray-600 uppercase bg-white px-2 py-1 rounded border border-gray-200">
                                    {{ $theme['primary_color'] ?? '#3b82f6' }}
                                </span>
                            </div>
                        </div>

                        <div class="relative bg-white border border-gray-200 rounded-xl p-4 hover:border-blue-300 hover:shadow-md transition-all group">
                            <div class="flex justify-between items-start mb-2">
                                <label class="text-sm font-semibold text-gray-700">Background Body</label>
                                <i class="fas fa-check-circle text-blue-500 opacity-0 group-hover:opacity-100 transition"></i>
                            </div>
                            <p class="text-xs text-gray-500 mb-3 h-8">Warna latar belakang keseluruhan halaman.</p>
                            <div class="flex items-center gap-3 bg-gray-50 p-2 rounded-lg border border-gray-100">
                                <input type="color" name="bg_color" value="{{ $theme['bg_color'] ?? '#f3f4f6' }}" 
                                       class="h-10 w-10 cursor-pointer rounded border-0 p-0 bg-transparent flex-shrink-0 shadow-sm">
                                <span class="text-xs font-mono text-gray-600 uppercase bg-white px-2 py-1 rounded border border-gray-200">
                                    {{ $theme['bg_color'] ?? '#f3f4f6' }}
                                </span>
                            </div>
                        </div>

                        <div class="relative bg-white border border-gray-200 rounded-xl p-4 hover:border-blue-300 hover:shadow-md transition-all group">
                            <div class="flex justify-between items-start mb-2">
                                <label class="text-sm font-semibold text-gray-700">Warna Teks</label>
                                <i class="fas fa-check-circle text-blue-500 opacity-0 group-hover:opacity-100 transition"></i>
                            </div>
                            <p class="text-xs text-gray-500 mb-3 h-8">Warna tulisan paragraf standar.</p>
                            <div class="flex items-center gap-3 bg-gray-50 p-2 rounded-lg border border-gray-100">
                                <input type="color" name="text_color" value="{{ $theme['text_color'] ?? '#1f2937' }}" 
                                       class="h-10 w-10 cursor-pointer rounded border-0 p-0 bg-transparent flex-shrink-0 shadow-sm">
                                <span class="text-xs font-mono text-gray-600 uppercase bg-white px-2 py-1 rounded border border-gray-200">
                                    {{ $theme['text_color'] ?? '#1f2937' }}
                                </span>
                            </div>
                        </div>

                    </div>
                </section>
            </div>

            <div class="px-6 py-5 bg-gray-50 border-t border-gray-100 flex items-center justify-between">
                <button type="button" onclick="window.location.reload()" class="text-sm text-gray-500 hover:text-gray-700 transition">
                    Reset Form
                </button>
                <button type="submit" class="inline-flex items-center justify-center px-6 py-2.5 border border-transparent text-sm font-medium rounded-lg shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all transform active:scale-95">
                    <i class="fas fa-save mr-2"></i> Simpan Perubahan
                </button>
            </div>

        </form>
    </div>
</div>
@endsection