@extends('layouts.admin')

@section('content')
<div class="container mx-auto px-4 py-6">

    {{-- TITLE & ACTION BUTTONS --}}
    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Dashboard Peserta Seminar</h2>
            <p class="text-sm text-gray-500">Pantau pendaftaran dan absensi secara real-time.</p>
        </div>

        <div class="flex gap-2">
            <a href="{{ route('admin.seminar.export.pdf') }}" class="bg-red-600 hover:bg-red-700 text-white text-sm font-bold py-2 px-4 rounded shadow transition flex items-center">
                <i class="fas fa-file-pdf mr-2"></i> PDF
            </a>
            <a href="{{ route('admin.seminar.export.excel') }}" class="bg-green-600 hover:bg-green-700 text-white text-sm font-bold py-2 px-4 rounded shadow transition flex items-center">
                <i class="fas fa-file-excel mr-2"></i> Excel
            </a>
            <a href="{{ route('admin.seminar.scan') }}" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold py-2 px-4 rounded shadow transition flex items-center">
                <i class="fas fa-qrcode mr-2"></i> Buka Scanner
            </a>
        </div>
    </div>

    {{-- STATISTIK CARDS (GRID 5 KOLOM) --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">

        {{-- Card 1: Total Peserta --}}
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-blue-500">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-xs font-bold text-gray-500 uppercase tracking-wider">Total Peserta</p>
                    <p class="text-2xl font-bold text-gray-800 mt-1">{{ $stats['total'] }}</p>
                </div>
                <div class="text-blue-500 bg-blue-50 p-3 rounded-full">
                    <i class="fas fa-users text-xl"></i>
                </div>
            </div>
        </div>

        {{-- Card 2: Jumlah Instansi --}}
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-purple-500">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-xs font-bold text-gray-500 uppercase tracking-wider">Instansi</p>
                    <p class="text-2xl font-bold text-gray-800 mt-1">{{ $stats['instansi'] }}</p>
                </div>
                <div class="text-purple-500 bg-purple-50 p-3 rounded-full">
                    <i class="fas fa-building text-xl"></i>
                </div>
            </div>
        </div>

        {{-- Card 3: Sudah Punya NIB (BARU) --}}
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-yellow-500">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-xs font-bold text-gray-500 uppercase tracking-wider">Memiliki NIB</p>
                    <p class="text-2xl font-bold text-gray-800 mt-1">{{ $stats['nib'] }}</p>
                </div>
                <div class="text-yellow-600 bg-yellow-50 p-3 rounded-full">
                    <i class="fas fa-id-card text-xl"></i>
                </div>
            </div>
        </div>

        {{-- Card 4: Sudah Hadir --}}
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-green-500">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-xs font-bold text-gray-500 uppercase tracking-wider">Sudah Hadir</p>
                    <p class="text-2xl font-bold text-gray-800 mt-1">{{ $stats['hadir'] }}</p>
                </div>
                <div class="text-green-500 bg-green-50 p-3 rounded-full">
                    <i class="fas fa-check-circle text-xl"></i>
                </div>
            </div>
        </div>

        {{-- Card 5: Belum Hadir --}}
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-red-500">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-xs font-bold text-gray-500 uppercase tracking-wider">Belum Hadir</p>
                    <p class="text-2xl font-bold text-gray-800 mt-1">{{ $stats['belum_hadir'] }}</p>
                </div>
                <div class="text-red-500 bg-red-50 p-3 rounded-full">
                    <i class="fas fa-times-circle text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- TABEL DATA PESERTA --}}
    <div class="bg-white shadow-lg rounded-lg overflow-hidden border border-gray-100">
        <div class="overflow-x-auto">
            <table class="min-w-full leading-normal">
                <thead>
                    <tr>
                        <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-50 text-left text-xs font-bold text-gray-600 uppercase tracking-wider w-12">No</th>
                        <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-50 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Tiket & Nama</th>
                        <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-50 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Instansi</th>
                        <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-50 text-center text-xs font-bold text-gray-600 uppercase tracking-wider">Status NIB</th>
                        <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-50 text-center text-xs font-bold text-gray-600 uppercase tracking-wider">Kehadiran</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($participants as $index => $p)
                    <tr class="hover:bg-gray-50 transition duration-150">
                        {{-- 1. NOMOR URUT --}}
                        <td class="px-5 py-4 whitespace-nowrap text-sm text-center text-gray-500">
                            {{ $participants->firstItem() + $index }}
                        </td>

                        {{-- 2. TIKET & NAMA --}}
                        <td class="px-5 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="ml-0">
                                    <div class="text-xs font-mono font-bold text-blue-600 bg-blue-50 px-2 py-0.5 rounded inline-block mb-1">
                                        {{ $p->ticket_number }}
                                    </div>
                                    <div class="text-sm font-bold text-gray-900">{{ $p->nama }}</div>
                                    <div class="text-xs text-gray-500">{{ $p->email }}</div>
                                    <div class="text-[10px] text-gray-400 mt-0.5">
                                        <i class="fab fa-whatsapp text-green-500"></i> {{ $p->no_wa }}
                                    </div>
                                </div>
                            </div>
                        </td>

                        {{-- 3. INSTANSI --}}
                        <td class="px-5 py-4 whitespace-nowrap text-sm text-gray-700">
                            {{ $p->instansi ?? '-' }}
                        </td>

                        {{-- 4. STATUS NIB (KOLOM BARU) --}}
                        <td class="px-5 py-4 whitespace-nowrap text-center">
                            @if($p->nib_status == 'Sudah')
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                    <i class="fas fa-check mr-1 mt-0.5"></i> Sudah
                                </span>
                            @else
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-500">
                                    Belum
                                </span>
                            @endif
                        </td>

                        {{-- 5. STATUS KEHADIRAN --}}
                        <td class="px-5 py-4 whitespace-nowrap text-center">
                            @if($p->is_checked_in)
                                <div class="flex flex-col items-center">
                                    <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                        Hadir
                                    </span>
                                    <span class="text-[10px] text-gray-500 mt-1">
                                        {{ $p->check_in_at->format('H:i') }} WIB
                                    </span>
                                </div>
                            @else
                                <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                    Belum Hadir
                                </span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-5 py-10 bg-white text-center text-gray-500">
                            <div class="flex flex-col items-center justify-center">
                                <i class="fas fa-users-slash text-4xl mb-3 text-gray-300"></i>
                                <p>Belum ada peserta yang mendaftar.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- PAGINATION --}}
        <div class="px-5 py-4 border-t border-gray-200 bg-gray-50">
            {{ $participants->links() }}
        </div>
    </div>
</div>
@endsection
