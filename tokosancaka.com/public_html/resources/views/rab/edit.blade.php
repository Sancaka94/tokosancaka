@extends('layouts.admin')

@section('content')
<!-- LOG LOG -->
<div class="max-w-3xl mx-auto py-8">
    <div class="mb-6">
        <a href="{{ route('rab.index') }}" class="text-sm text-gray-500 hover:text-gray-900 mb-2 inline-block">&larr; Kembali</a>
        <h1 class="text-2xl font-semibold text-gray-900">Edit Item RAB</h1>
    </div>

    <div class="bg-white border border-gray-200 rounded-lg p-6 shadow-sm">
        <form action="{{ route('rab.update', $rab->id) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Kategori Pekerjaan (Opsional)</label>
                <input type="text" name="kategori" value="{{ old('kategori', $rab->kategori) }}" 
                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-black focus:border-black">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Uraian Pekerjaan</label>
                <input type="text" name="uraian_pekerjaan" required value="{{ old('uraian_pekerjaan', $rab->uraian_pekerjaan) }}" 
                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-black focus:border-black">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Volume</label>
                    <input type="number" step="any" name="volume" required value="{{ old('volume', $rab->volume) }}" 
                        class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-black focus:border-black">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Satuan</label>
                    <input type="text" name="satuan" required value="{{ old('satuan', $rab->satuan) }}" 
                        class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-black focus:border-black">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Harga Satuan (Rp)</label>
                <input type="number" name="harga_satuan" required value="{{ old('harga_satuan', $rab->harga_satuan) }}" 
                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-black focus:border-black">
            </div>

            <div class="flex justify-end pt-4 border-t border-gray-100">
                <button type="submit" class="bg-black hover:bg-gray-800 text-white text-sm font-medium py-2 px-6 rounded-md transition-colors">
                    Perbarui Pekerjaan
                </button>
            </div>
        </form>
    </div>
</div>
@endsection