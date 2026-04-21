@extends('layouts.admin')

@section('content')
<div class="max-w-4xl mx-auto mt-10">

    @if(session('success'))
        <div class="mb-4 p-4 bg-green-100 text-green-700 border border-green-400 rounded-lg shadow-sm font-medium">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="mb-4 p-4 bg-red-100 text-red-700 border border-red-400 rounded-lg shadow-sm font-medium">
            {{ session('error') }}
        </div>
    @endif

    <div class="bg-white p-8 rounded-xl shadow-lg border border-gray-100 mb-8">
        <h2 class="text-2xl font-bold mb-6 text-gray-800 border-b pb-4">Generate 2D Barcode</h2>

        <form action="{{ route('barcode.generate') }}" method="POST">
            @csrf
            <div class="mb-6">
                <label for="url" class="block text-sm font-semibold text-gray-700 mb-2">
                    Masukkan Tautan (URL)
                </label>
                <input type="url" name="url" id="url" required
                       value="{{ old('url', $url ?? '') }}"
                       placeholder="https://tokosancaka.com/..."
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors text-gray-800 shadow-sm">
                @error('url')
                    <p class="text-red-500 text-sm mt-2 font-medium">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-lg transition duration-300 shadow-md">
                Generate Barcode
            </button>
        </form>

        @if(isset($barcode))
            <div class="mt-8 flex flex-col items-center bg-gray-50 p-6 rounded-xl border border-gray-200">
                <h3 class="text-lg font-semibold text-gray-700 mb-4">Preview Barcode:</h3>
                <div class="p-4 bg-white border border-gray-200 rounded-xl shadow-sm mb-4">
                    <img src="data:image/png;base64, {!! $barcode !!}" alt="Generated Barcode" class="w-48 h-48 object-contain">
                </div>
            </div>
        @endif
    </div>

    <div class="bg-white p-8 rounded-xl shadow-lg border border-gray-100 mb-10">
        <h2 class="text-xl font-bold mb-6 text-gray-800 border-b pb-4">Riwayat Generate</h2>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-100 text-gray-700 border-b border-gray-200">
                        <th class="p-3 font-semibold text-sm w-12">No</th>
                        <th class="p-3 font-semibold text-sm">URL Tautan</th>
                        <th class="p-3 font-semibold text-sm w-40">Tanggal</th>
                        <th class="p-3 font-semibold text-sm w-64 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="text-gray-600 text-sm divide-y divide-gray-200">
                    @forelse($riwayat as $key => $item)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="p-3">{{ $riwayat->firstItem() + $key }}</td>
                        <td class="p-3 break-all">{{ $item->url }}</td>
                        <td class="p-3">{{ \Carbon\Carbon::parse($item->created_at)->format('d M Y, H:i') }}</td>
                        <td class="p-3 flex justify-center space-x-2">
                            <a href="{{ route('barcode.download', $item->id) }}" class="px-3 py-1.5 bg-emerald-500 hover:bg-emerald-600 text-white rounded text-xs font-medium shadow-sm transition">
                                <i class="fa-solid fa-download mr-1"></i> Download
                            </a>
                            <a href="{{ route('barcode.edit', $item->id) }}" class="px-3 py-1.5 bg-amber-500 hover:bg-amber-600 text-white rounded text-xs font-medium shadow-sm transition">
                                <i class="fa-solid fa-pen-to-square mr-1"></i> Edit
                            </a>
                            <form action="{{ route('barcode.destroy', $item->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus riwayat ini?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="px-3 py-1.5 bg-red-500 hover:bg-red-600 text-white rounded text-xs font-medium shadow-sm transition">
                                    <i class="fa-solid fa-trash mr-1"></i> Hapus
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="p-6 text-center text-gray-500 italic">Belum ada riwayat generate barcode.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $riwayat->links() }}
        </div>
    </div>
</div>
@endsection
