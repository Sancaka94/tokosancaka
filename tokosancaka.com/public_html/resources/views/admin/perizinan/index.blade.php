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

        @f(session('success'))
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
                            {{-- KOLOM BARU: KELENGKAPAN PERIZINAN --}}
                            <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                Kelengkapan Perizinan
                            </th>
                            <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-center text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                Aksi
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($data as $item)
                        <tr>
                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm align-top">
                                <p class="text-gray-900 whitespace-no-wrap">{{ $item->created_at->format('d/m/Y H:i') }}</p>
                            </td>
                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm align-top">
                                <div class="flex flex-col">
                                    <span class="font-bold text-gray-900">{{ $item->nama_pelanggan }}</span>
                                    <a href="https://wa.me/{{ $item->no_wa }}" target="_blank" class="text-green-600 hover:text-green-800 text-xs mt-1">
                                        <i class="fab fa-whatsapp"></i> {{ $item->no_wa }}
                                    </a>
                                    <span class="text-gray-500 text-xs mt-1">{{ $item->lokasi }}</span>
                                </div>
                            </td>
                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm align-top">
                                <div class="text-gray-900 whitespace-no-wrap text-xs">
                                    <div class="mb-1">📐 {{ $item->lebar }}m x {{ $item->panjang }}m ({{ $item->jumlah_lantai }} Lt)</div>
                                    <div class="mb-1">🏗 {{ $item->status_bangunan }}</div>
                                    <div class="mb-1">🏠 {{ $item->jenis_bangunan }}</div>
                                    <div class="mb-1">👥 Penghuni/Karyawan: <span class="font-semibold">{{ $item->jumlah_penghuni ?? '-' }}</span></div>
                                    <div class="mb-1">🏢 Basement: <span class="font-semibold">{{ $item->memiliki_basement ? 'Ada' : 'Tidak Ada' }}</span></div>
                                </div>
                            </td>
                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm align-top">
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
                            
                            {{-- ISI KOLOM BARU: KELENGKAPAN PERIZINAN --}}
                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm align-top">
                                <div class="text-gray-900 whitespace-no-wrap text-xs grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-1">
                                    
                                    {{-- Asumsi properti boolean pada model $item (true/false atau 1/0) --}}
                                    
                                    <div class="flex items-center">
                                        <i class="fas {{ $item->rekom_dishub ? 'fa-check text-green-500' : 'fa-times text-red-500' }} w-4 mr-1"></i> Rekom Dishub
                                    </div>
                                    
                                    <div class="flex items-center">
                                        <i class="fas {{ $item->rekom_damkar ? 'fa-check text-green-500' : 'fa-times text-red-500' }} w-4 mr-1"></i> Rekom Damkar
                                    </div>
                                    
                                    <div class="flex items-center">
                                        <i class="fas {{ $item->andalalin ? 'fa-check text-green-500' : 'fa-times text-red-500' }} w-4 mr-1"></i> Andalalin
                                    </div>
                                    
                                    <div class="flex items-center">
                                        <i class="fas {{ $item->lingkungan ? 'fa-check text-green-500' : 'fa-times text-red-500' }} w-4 mr-1"></i> SPPL/UKL-UPL/AMDAL
                                    </div>
                                    
                                    <div class="flex items-center">
                                        <i class="fas {{ $item->nib ? 'fa-check text-green-500' : 'fa-times text-red-500' }} w-4 mr-1"></i> NIB
                                    </div>
                                    
                                    <div class="flex items-center">
                                        <i class="fas {{ $item->siup ? 'fa-check text-green-500' : 'fa-times text-red-500' }} w-4 mr-1"></i> SIUP
                                    </div>
                                    
                                    {{-- Status Tanah (Biasanya string) --}}
                                    <div class="col-span-1 md:col-span-2 mt-2">
                                        <span class="font-semibold block border-t pt-1">Status Tanah:</span>
                                        <span class="inline-block bg-blue-100 text-blue-800 px-2 py-0.5 rounded text-xs mt-1">
                                            {{ $item->status_tanah ?? 'Belum Diisi' }}
                                        </span>
                                    </div>

                                    {{-- Perizinan Lain-Lain (Text Area) --}}
                                    @if($item->perizinan_lain)
                                    <div class="col-span-1 md:col-span-2 mt-2">
                                        <span class="font-semibold block">Lain-lain:</span>
                                        <p class="text-gray-600 italic mt-1 bg-gray-50 p-2 rounded border border-gray-200">
                                            {{ $item->perizinan_lain }}
                                        </p>
                                    </div>
                                    @endif

                                </div>
                            </td>

                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm text-center align-top">
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
                            <td colspan="6" class="px-5 py-5 bg-white text-center text-gray-500">
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