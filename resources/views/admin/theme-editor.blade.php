@extends('layouts.app')

@section('content')
<div class="max-w-md mx-auto bg-white p-6 rounded shadow mt-10">
    <h2 class="text-2xl font-bold mb-4">Edit Tampilan (SettingTheme)</h2>

    @if(session('success'))
        <div class="bg-green-100 text-green-700 p-2 mb-4 rounded">{{ session('success') }}</div>
    @endif

    <form action="{{ route('theme.update') }}" method="POST">
        @csrf

        <div class="mb-4">
            <label class="block text-sm font-medium mb-1">Judul Website</label>
            <input type="text" name="site_title" 
                   value="{{ $theme['site_title'] ?? '' }}" 
                   class="w-full border rounded p-2">
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium mb-1">Warna Utama (Navbar/Button)</label>
            <div class="flex items-center gap-2">
                <input type="color" name="primary_color" 
                       value="{{ $theme['primary_color'] ?? '#3b82f6' }}" 
                       class="h-10 w-20 cursor-pointer">
            </div>
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium mb-1">Warna Background Body</label>
            <input type="color" name="bg_color" 
                   value="{{ $theme['bg_color'] ?? '#ffffff' }}" 
                   class="h-10 w-20 cursor-pointer">
        </div>

        <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700">
            Simpan Perubahan
        </button>
    </form>
</div>
@endsection