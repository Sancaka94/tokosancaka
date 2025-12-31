@extends('pondok.admin.layouts.app')

@section('title', 'Pengaturan Pondok')
@section('page_title', 'Pengaturan Umum')

@section('content')
<div class="max-w-4xl mx-auto">
    @if(session('success'))
        <div class="mb-5 bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded shadow-sm" role="alert">
            <p class="font-bold">Berhasil!</p>
            <p>{{ session('success') }}</p>
        </div>
    @endif

    <div class="bg-white p-8 shadow-sm rounded-xl border border-gray-200">
        <form action="{{ route('admin.settings.update') }}" method="POST" enctype="multipart/form-data">
            @csrf
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                
                <div class="space-y-5">
                    <h4 class="text-sm font-semibold text-indigo-600 uppercase tracking-wider">Identitas Pondok</h4>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nama Pondok</label>
                        <input type="text" name="nama_sekolah" value="{{ $settings['nama_sekolah'] ?? '' }}" 
                            class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email Resmi</label>
                        <input type="email" name="email" value="{{ $settings['email'] ?? '' }}" 
                            class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Telepon / WhatsApp</label>
                        <input type="text" name="telepon" value="{{ $settings['telepon'] ?? '' }}" 
                            class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Alamat Lengkap</label>
                        <textarea name="alamat" rows="3" 
                            class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ $settings['alamat'] ?? '' }}</textarea>
                    </div>
                </div>

                <div class="space-y-5">
                    <h4 class="text-sm font-semibold text-indigo-600 uppercase tracking-wider">Branding</h4>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-3">Logo Pesantren</label>
                        <div class="flex items-center space-x-5 mb-4">
                            <div class="shrink-0">
                                @if(!empty($settings['logo']))
                                    <img src="{{ asset($settings['logo']) }}" alt="Logo saat ini" class="h-20 w-20 object-contain border rounded-lg bg-gray-50 p-2">
                                @else
                                    <div class="h-20 w-20 bg-gray-100 rounded-lg flex items-center justify-center text-gray-400 text-xs text-center p-2">
                                        Belum ada logo
                                    </div>
                                @endif
                            </div>
                            <label class="block">
                                <span class="sr-only">Pilih logo baru</span>
                                <input type="file" name="logo" class="block w-full text-sm text-slate-500
                                    file:mr-4 file:py-2 file:px-4
                                    file:rounded-full file:border-0
                                    file:text-sm file:font-semibold
                                    file:bg-indigo-50 file:text-indigo-700
                                    hover:file:bg-indigo-100">
                            </label>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Deskripsi / Slogan</label>
                        <textarea name="deskripsi" rows="4" 
                            class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="Contoh: Meniti Jejak Para Salafush Sholih">{{ $settings['deskripsi'] ?? '' }}</textarea>
                    </div>
                </div>
            </div>

            <div class="mt-10 pt-6 border-t flex justify-end">
                <button type="submit" class="bg-indigo-600 text-white px-8 py-3 rounded-lg font-bold hover:bg-indigo-700 shadow-lg shadow-indigo-200 transition-all transform hover:-translate-y-0.5 active:scale-95">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>
@endsection