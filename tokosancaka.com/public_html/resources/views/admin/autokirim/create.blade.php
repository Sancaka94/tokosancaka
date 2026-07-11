@extends('layouts.admin')

@section('content')
<div class="max-w-2xl mx-auto py-12 px-4 sm:px-6 lg:px-8 bg-white text-black min-h-screen font-sans">
    <div class="mb-8">
        <a href="{{ route('admin.autokirim.index') }}" class="text-gray-400 hover:text-black text-sm font-semibold transition-colors tracking-wide">&larr; BACK TO LIST</a>
        <h1 class="text-3xl font-extrabold tracking-tight mt-4">Tambah Area</h1>
    </div>

    <form action="{{ route('admin.autokirim.store') }}" method="POST" class="space-y-6">
        @csrf
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">ZIP</label>
                <input type="text" name="zip" required class="w-full border border-gray-300 px-4 py-3 text-sm focus:outline-none focus:border-black focus:ring-1 focus:ring-black transition-colors rounded-none" value="{{ old('zip') }}">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">District ID</label>
                <input type="text" name="district_id" required class="w-full border border-gray-300 px-4 py-3 text-sm focus:outline-none focus:border-black focus:ring-1 focus:ring-black transition-colors rounded-none font-mono" value="{{ old('district_id') }}">
            </div>
            <div class="sm:col-span-2">
                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">District Name</label>
                <input type="text" name="district_name" required class="w-full border border-gray-300 px-4 py-3 text-sm focus:outline-none focus:border-black focus:ring-1 focus:ring-black transition-colors rounded-none" value="{{ old('district_name') }}">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Regency Name</label>
                <input type="text" name="regency_name" required class="w-full border border-gray-300 px-4 py-3 text-sm focus:outline-none focus:border-black focus:ring-1 focus:ring-black transition-colors rounded-none" value="{{ old('regency_name') }}">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Province Name</label>
                <input type="text" name="province_name" required class="w-full border border-gray-300 px-4 py-3 text-sm focus:outline-none focus:border-black focus:ring-1 focus:ring-black transition-colors rounded-none" value="{{ old('province_name') }}">
            </div>
        </div>
        
        <div class="pt-6 mt-6 border-t border-gray-100 flex justify-end">
            <button type="submit" class="bg-black text-white px-8 py-3 text-sm font-bold hover:bg-gray-800 transition-colors border border-black">
                Simpan Data
            </button>
        </div>
    </form>
</div>
@endsection