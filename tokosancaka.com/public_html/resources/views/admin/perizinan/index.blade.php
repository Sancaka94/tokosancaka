@extends('layouts.app')

@section('title', 'Data Perizinan Masuk')

@section('content')
<div class="container mx-auto px-4 sm:px-8 py-8">
    <div class="py-8">

        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-semibold leading-tight text-gray-800">Data Masuk Formulir Perizinan</h2>
            <a href="{{ route('perizinan.form') }}" target="_blank" class="bg-blue-500 hover:bg-blue-700 text-white text-sm font-bold py-2 px-4 rounded shadow">
                <i class="fas fa-plus mr-1"></i> Input Manual
            </a>
        </div>

        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline">{{ session('success') }}</span>
            </div>
        @endif

        <div class="-mx-4 sm:-mx-8 px-4 sm:px-8 py-4 overflow-x-auto">
            <div class="inline-block min-w-full shadow-md rounded-lg overflow-hidden">
                <table class="min-w-full leading-normal">
                    <thead>
                        <tr>
                            <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                Tanggal
                            </th>
                            <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                Pelanggan
                            </th>
                            <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                Dimensi & Bangunan
                            </th>
                            <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                Detail Legalitas
                            </th>
                            <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-center text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                Aksi
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($data as $item)
                        <tr>
                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                <p class="text-gray-900 whitespace-no-wrap">{{ $item->created_at->format('d/m/Y H:i') }}</p>
                            </td>
                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                <div class="flex flex-col">
                                    <span class="font-bold text-gray-900">{{ $item->nama_pelanggan }}</span>
                                    <a href="https://wa.me/{{ $item->no_wa }}" target="_blank" class="text-green-600 hover:text-green-800 text-xs mt-1">
                                        <i class="fab fa-whatsapp"></i> {{ $item->no_wa }}
                                    </a>
                                    <span class="text-gray-500 text-xs mt-1">{{ $item->lokasi }}</span>
                                </div>
                            </td>
                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                <div class="text-gray-900 whitespace-no-wrap text-xs">
                                    <div class="mb-1">📐 {{ $item->lebar }}m x {{ $item->panjang }}m ({{ $item->jumlah_lantai }} Lt)</div>
                                    <div class="mb-1">🏗 {{ $item->status_bangunan }}</div>
                                    <div class="mb-1">🏠 {{ $item->jenis_bangunan }}</div>
                                </div>
                            </td>
                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                <div class="text-gray-900 whitespace-no-wrap text-xs">
                                    <div class="mb-1">🛠 {{ $item->fungsi_bangunan }}</div>
                                    <div class="mb-1">📜 {{ $item->legalitas_saat_ini }}</div>
                                    <div class="mb-1">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $item->status_krk == 'Sudah Punya' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                            KRK: {{ $item->status_krk }}
                                        </span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm text-center">
                                <form action="{{ route('admin.perizinan.destroy', $item->id) }}" method="POST" onsubmit="return confirm('Yakin hapus data ini?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-500 hover:text-red-700">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-5 py-5 bg-white text-center text-gray-500">
                                Belum ada data formulir masuk.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="px-5 py-5 bg-white border-t flex flex-col xs:flex-row items-center xs:justify-between">
                    {{ $data->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
