@extends('layouts.admin')

@section('content')
<div class="max-w-5xl mx-auto p-6">

    @if(session('success'))
        <div class="mb-4 p-4 bg-green-500 text-white rounded-lg shadow-md">
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white rounded-xl shadow-md border border-gray-100 p-8 mb-8">
        <h2 class="text-2xl font-bold text-gray-800 mb-6">Generate 2D Barcode</h2>

        <form action="{{ route('barcode.generate') }}" method="POST">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Masukkan Tautan (URL)</label>
                <input type="url" name="url" required value="{{ $url ?? '' }}"
                       class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-blue-500 focus:border-blue-500">
            </div>
            <button type="submit" class="w-full bg-blue-600 text-white font-bold py-3 rounded-lg hover:bg-blue-700 transition">
                Generate Barcode
            </button>
        </form>

        @if(isset($barcode))
            <div class="mt-8 flex flex-col items-center p-6 bg-gray-50 rounded-xl border border-dashed border-gray-300">
                <p class="text-sm font-bold text-gray-500 mb-4 uppercase tracking-widest">Preview Barcode:</p>
                <img src="data:image/png;base64, {!! $barcode !!}" class="w-48 h-48 shadow-lg bg-white p-2">
            </div>
        @endif
    </div>

    <div class="bg-white rounded-xl shadow-md border border-gray-100 overflow-hidden">
        <div class="p-6 border-b border-gray-100">
            <h3 class="text-lg font-bold text-gray-800">Riwayat Generate</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-gray-50 text-gray-600 text-xs uppercase font-semibold">
                    <tr>
                        <th class="px-6 py-4">No</th>
                        <th class="px-6 py-4">URL</th>
                        <th class="px-6 py-4">Waktu</th>
                        <th class="px-6 py-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm">
                    @forelse($riwayat as $key => $item)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-6 py-4">{{ $riwayat->firstItem() + $key }}</td>
                            <td class="px-6 py-4 font-medium text-blue-600 break-all">{{ $item->url }}</td>
                            <td class="px-6 py-4 text-gray-500 text-xs">
                                {{ \Carbon\Carbon::parse($item->created_at)->diffForHumans() }}
                            </td>
                            <td class="px-6 py-4 flex justify-center space-x-2">
                                <a href="{{ route('barcode.download', $item->id) }}" class="bg-emerald-500 text-white px-3 py-1.5 rounded hover:bg-emerald-600 text-xs">
                                    <i class="fa-solid fa-download"></i>
                                </a>
                                <a href="{{ route('barcode.edit', $item->id) }}" class="bg-amber-500 text-white px-3 py-1.5 rounded hover:bg-amber-600 text-xs">
                                    <i class="fa-solid fa-pen"></i>
                                </a>
                                <form action="{{ route('barcode.destroy', $item->id) }}" method="POST" onsubmit="return confirm('Hapus riwayat ini?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="bg-red-500 text-white px-3 py-1.5 rounded hover:bg-red-600 text-xs">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-10 text-center text-gray-400 italic">Belum ada riwayat generate.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-6 bg-gray-50">
            {{ $riwayat->links() }}
        </div>
    </div>
</div>
@endsection
