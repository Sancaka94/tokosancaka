@extends('layouts.app')

{{-- Judul Halaman --}}
@section('title', 'Riwayat Pembaruan Sistem')

{{-- Header (Jika layout Anda support @section header) --}}
@section('header')
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        {{ __('Changelog & Version History') }}
    </h2>
@endsection

{{-- Konten Utama --}}
@section('content')
<div class="py-12 bg-gray-50 min-h-screen">
    <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">

        {{-- Card Header Versi --}}
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl mb-6 border-b-4 border-blue-600">
            <div class="p-6 md:p-8 flex flex-col md:flex-row justify-between items-center gap-4">
                <div>
                    <h3 class="text-2xl font-black text-slate-800 tracking-tight">System Changelog</h3>
                    <p class="text-slate-500 mt-1">
                        Daftar perbaikan bug, penambahan fitur, dan pembaruan keamanan terbaru.
                    </p>
                </div>
                <div class="text-center md:text-right bg-blue-50 px-6 py-3 rounded-2xl border border-blue-100">
                    <span class="block text-[10px] font-bold text-blue-400 uppercase tracking-widest">Current Build</span>
                    <span class="text-3xl font-black text-blue-700">{{ $version ?? 'DEV' }}</span>
                </div>
            </div>
        </div>

        {{-- Timeline Container --}}
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl p-6 md:p-8 relative">

            @if(empty($commits))
                <div class="text-center py-12">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-slate-100 mb-4">
                        <i class="fas fa-code-branch text-2xl text-slate-400"></i>
                    </div>
                    <h3 class="text-lg font-bold text-slate-700">Tidak ada data Git</h3>
                    <p class="text-slate-500 max-w-sm mx-auto mt-2">Pastikan folder <code class="bg-slate-100 px-1 py-0.5 rounded text-sm text-red-500">.git</code> tersedia di server dan fungsi <code>exec()</code> PHP diaktifkan.</p>
                </div>
            @else

                <div class="absolute left-8 md:left-[50%] top-8 bottom-8 w-px bg-slate-200 hidden md:block"></div>

                <div class="space-y-8 relative">
                    @foreach($commits as $key => $log)
                        <div class="relative flex flex-col md:flex-row gap-4 md:gap-0 group">

                            {{-- Sisi Kiri (Tanggal & Meta) --}}
                            <div class="md:w-1/2 md:pr-12 md:text-right flex flex-col justify-center order-2 md:order-1 pl-12 md:pl-0">
                                <span class="text-[10px] font-bold uppercase tracking-wider text-blue-600 mb-1">
                                    {{ $log['date'] }}
                                </span>
                                <div class="flex items-center md:justify-end gap-2">
                                    <span class="text-xs text-slate-400 bg-slate-100 px-2 py-0.5 rounded-full border border-slate-200">
                                        {{ $log['ago'] }}
                                    </span>
                                    <span class="text-xs font-mono text-slate-500 bg-slate-100 px-2 py-0.5 rounded border border-slate-200" title="Commit Hash">
                                        #{{ $log['hash'] }}
                                    </span>
                                </div>
                            </div>

                            {{-- Titik Tengah Timeline --}}
                            <div class="absolute left-0 md:left-[50%] md:-ml-[9px] top-0 md:top-1/2 md:-mt-[9px] w-[18px] h-[18px] rounded-full border-4 border-white shadow-sm z-10
                                {{ $key === 0 ? 'bg-blue-600 ring-4 ring-blue-100' : 'bg-slate-300 group-hover:bg-blue-400 transition-colors' }}">
                            </div>

                            {{-- Sisi Kanan (Pesan Commit) --}}
                            <div class="md:w-1/2 md:pl-12 order-1 md:order-2 pl-12">
                                <div class="bg-slate-50 p-4 rounded-xl border border-slate-100 group-hover:bg-white group-hover:shadow-md group-hover:border-blue-200 transition-all duration-300">
                                    <h4 class="font-bold text-slate-800 text-sm leading-relaxed">
                                        {{ $log['message'] }}
                                    </h4>
                                    <div class="mt-2 flex items-center gap-2">
                                        <div class="w-5 h-5 rounded-full bg-indigo-100 flex items-center justify-center text-[10px] font-bold text-indigo-600">
                                            {{ substr($log['author'], 0, 1) }}
                                        </div>
                                        <span class="text-xs text-slate-500">{{ $log['author'] }}</span>
                                    </div>
                                </div>
                            </div>

                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="mt-6 text-center text-slate-400 text-xs">
            &copy; {{ date('Y') }} SancakaPOS System Log
        </div>
    </div>
</div>
@endsection
