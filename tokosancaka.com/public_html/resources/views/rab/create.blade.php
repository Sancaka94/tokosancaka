@extends('layouts.admin')

@section('content')
<!-- LOG LOG -->
<div class="max-w-3xl mx-auto py-8">
    <div class="mb-6">
        <a href="{{ route('rab.index') }}" class="text-sm text-gray-500 hover:text-gray-900 mb-2 inline-block">&larr; Kembali</a>
        <h1 class="text-2xl font-semibold text-gray-900">Tambah Item RAB</h1>
    </div>

    <div class="bg-white border border-gray-200 rounded-lg p-6 shadow-sm">
        <form action="{{ route('rab.store') }}" method="POST" class="space-y-6">
            @csrf
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Kategori Pekerjaan (Opsional)</label>
                <input type="text" name="kategori" placeholder="Cth: PEKERJAAN KERAMIK" value="{{ old('kategori') }}" 
                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-black focus:border-black">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Uraian Pekerjaan</label>
                <input type="text" name="uraian_pekerjaan" required placeholder="Cth: keramik lantai 80x80 lantai 1" value="{{ old('uraian_pekerjaan') }}" 
                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-black focus:border-black">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Volume</label>
                    <input type="number" step="any" name="volume" required placeholder="1450" value="{{ old('volume') }}" 
                        class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-black focus:border-black">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Satuan</label>
                    <input type="text" name="satuan" required placeholder="m2" value="{{ old('satuan') }}" 
                        class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-black focus:border-black">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Harga Satuan (Rp)</label>
                <input type="number" name="harga_satuan" required placeholder="55000" value="{{ old('harga_satuan') }}" 
                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-black focus:border-black">
            </div>

            <div class="flex justify-end pt-4 border-t border-gray-100">
                <button type="submit" class="bg-black hover:bg-gray-800 text-white text-sm font-medium py-2 px-6 rounded-md transition-colors">
                    Simpan Pekerjaan
                </button>
            </div>
        </form>
    </div>
</div>
@endsection