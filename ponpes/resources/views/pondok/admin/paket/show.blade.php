@extends('pondok.admin.layouts.app')

@section('title', 'Detail Paket')
@section('page_title', 'Detail Paket Berlangganan')

@section('content')
<div class="max-w-3xl mx-auto bg-white overflow-hidden shadow-sm sm:rounded-lg">
    <div class="p-6 text-gray-900">
        
        <div class="flex justify-between items-start border-b pb-4 mb-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">{{ $paket->nama_paket }}</h1>
                <span class="inline-block bg-blue-100 text-blue-800 text-sm font-semibold px-3 py-1 rounded mt-2">
                    Aktif selama {{ $paket->periode_hari }} Hari
                </span>
            </div>
            <div class="text-right">
                <p class="text-sm text-gray-500">Harga Paket</p>
                <p class="text-3xl font-bold text-green-600">Rp {{ number_format($paket->harga, 0, ',', '.') }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div>
                <h4 class="text-sm font-bold text-gray-500 uppercase tracking-wider mb-2">Deskripsi</h4>
                <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                    <p class="text-gray-700 whitespace-pre-line">{{ $paket->deskripsi ?? 'Tidak ada deskripsi.' }}</p>
                </div>
            </div>

            <div>
                <h4 class="text-sm font-bold text-gray-500 uppercase tracking-wider mb-2">Fitur Paket</h4>
                <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                    <p class="text-gray-700 font-mono text-sm break-all">
                        {{ $paket->fitur ?? '-' }}
                    </p>
                </div>
            </div>
        </div>

        <div class="flex justify-between items-center pt-4 border-t">
            <a href="{{ route('admin.paket.index') }}" class="text-gray-600 hover:text-gray-900 font-medium flex items-center">
                &larr; Kembali ke Daftar
            </a>
            
            <div class="flex space-x-3">
                <a href="{{ route('admin.paket.edit', $paket->id) }}" class="bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-2 px-4 rounded">
                    Edit Paket
                </a>
            </div>
        </div>

    </div>
</div>
@endsection