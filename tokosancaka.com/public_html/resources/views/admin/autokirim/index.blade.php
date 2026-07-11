@extends('layouts.admin') {{-- Sesuaikan nama layout admin kamu --}}

@section('content')
<div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8 bg-white text-black min-h-screen font-sans selection:bg-black selection:text-white">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
        <h1 class="text-3xl font-extrabold tracking-tight">Area AutoKirim</h1>
        
        <div class="flex flex-wrap items-center gap-3 w-full sm:w-auto">
            <a href="{{ route('admin.autokirim.create') }}" class="bg-black text-white px-5 py-2.5 text-sm font-semibold hover:bg-gray-800 transition-colors border border-black">
                + Tambah Manual
            </a>
            <form action="{{ route('admin.autokirim.import') }}" method="POST" enctype="multipart/form-data" class="flex items-center">
                @csrf
                <label class="sr-only" for="file_upload">Upload File Excel</label>
                <input type="file" id="file_upload" name="file" class="text-sm border border-gray-300 px-3 py-2 focus:outline-none focus:border-black rounded-none h-[42px]" required accept=".xlsx,.xls,.csv">
                <button type="submit" class="border border-black border-l-0 bg-white text-black px-5 py-2.5 text-sm font-semibold hover:bg-gray-100 transition-colors h-[42px]">
                    Import Excel
                </button>
            </form>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-6 p-4 border border-black bg-black text-white text-sm font-semibold flex items-center justify-between">
            <span>{{ session('success') }}</span>
            <button onclick="this.parentElement.remove()" class="text-white opacity-70 hover:opacity-100">&times;</button>
        </div>
    @endif

    <div class="overflow-x-auto border border-gray-200">
        <table class="w-full text-left text-sm whitespace-nowrap">
            <thead class="border-b border-gray-200 bg-gray-50/50">
                <tr>
                    <th class="px-6 py-4 font-bold text-gray-900 tracking-wider">ZIP</th>
                    <th class="px-6 py-4 font-bold text-gray-900 tracking-wider">District ID</th>
                    <th class="px-6 py-4 font-bold text-gray-900 tracking-wider">District Name</th>
                    <th class="px-6 py-4 font-bold text-gray-900 tracking-wider">Regency</th>
                    <th class="px-6 py-4 font-bold text-gray-900 tracking-wider">Province</th>
                    <th class="px-6 py-4 font-bold text-gray-900 tracking-wider text-right">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($data as $item)
                <tr class="hover:bg-gray-50/80 transition-colors group">
                    <td class="px-6 py-4 text-gray-700">{{ $item->zip }}</td>
                    <td class="px-6 py-4 font-mono text-xs text-gray-500">{{ $item->district_id }}</td>
                    <td class="px-6 py-4 font-medium">{{ $item->district_name }}</td>
                    <td class="px-6 py-4 text-gray-700">{{ $item->regency_name }}</td>
                    <td class="px-6 py-4 text-gray-700">{{ $item->province_name }}</td>
                    <td class="px-6 py-4 text-right">
                        <a href="{{ route('admin.autokirim.edit', $item->id) }}" class="text-gray-400 hover:text-black mr-4 font-semibold transition-colors">Edit</a>
                        <form action="{{ route('admin.autokirim.destroy', $item->id) }}" method="POST" class="inline-block">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-gray-400 hover:text-black font-semibold transition-colors" onclick="return confirm('Apakah kamu yakin ingin menghapus data ini?')">Hapus</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-16 text-center text-gray-400 font-medium">Belum ada data Area AutoKirim. Silakan import atau tambah manual.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    <div class="mt-6">
        {{ $data->links() }}
    </div>
</div>
@endsection