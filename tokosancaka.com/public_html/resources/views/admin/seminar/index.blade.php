@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Data Peserta Seminar</h2>
        <a href="{{ route('admin.seminar.scan') }}" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded shadow">
            <i class="fas fa-qrcode mr-2"></i> Buka Scanner
        </a>
    </div>

    <div class="bg-white shadow-md rounded-lg overflow-hidden">
        <table class="min-w-full leading-normal">
            <thead>
                <tr>
                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Tiket</th>
                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Nama</th>
                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Instansi</th>
                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Status Kehadiran</th>
                </tr>
            </thead>
            <tbody>
                @foreach($participants as $p)
                <tr>
                    <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                        <span class="font-mono font-bold">{{ $p->ticket_number }}</span>
                    </td>
                    <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                        <p class="text-gray-900 whitespace-no-wrap">{{ $p->nama }}</p>
                        <p class="text-gray-500 text-xs">{{ $p->email }}</p>
                    </td>
                    <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                        {{ $p->instansi ?? '-' }}
                    </td>
                    <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm text-center">
                        @if($p->is_checked_in)
                            <span class="relative inline-block px-3 py-1 font-semibold text-green-900 leading-tight">
                                <span aria-hidden class="absolute inset-0 bg-green-200 opacity-50 rounded-full"></span>
                                <span class="relative text-xs">Hadir: {{ $p->check_in_at->format('H:i') }}</span>
                            </span>
                        @else
                            <span class="relative inline-block px-3 py-1 font-semibold text-red-900 leading-tight">
                                <span aria-hidden class="absolute inset-0 bg-red-200 opacity-50 rounded-full"></span>
                                <span class="relative text-xs">Belum Hadir</span>
                            </span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div class="p-4">
            {{ $participants->links() }}
        </div>
    </div>
</div>
@endsection
