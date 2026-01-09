@extends('layouts.app')

@section('title', 'System Logs')

@section('content')
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<style>
    [x-cloak] { display: none !important; }
    .log-font { font-family: 'Fira Code', 'Consolas', monospace; }
</style>

<div class="container mx-auto px-4 py-6 max-w-7xl">

    {{-- HEADER --}}
    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-black text-slate-800 tracking-tight uppercase">System Logs</h1>
            <p class="text-slate-500 text-sm font-medium">Monitoring error dan aktivitas sistem (laravel.log).</p>
        </div>
        
        <form action="{{ route('logs.clear') }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus semua history log?');">
            @csrf
            @method('DELETE')
            <button type="submit" class="bg-rose-600 text-white px-4 py-2 rounded-xl font-bold text-sm shadow-lg shadow-rose-200 hover:bg-rose-700 transition flex items-center gap-2">
                <i class="fas fa-trash-alt"></i> Bersihkan Log
            </button>
        </form>
    </div>

    {{-- FILTER SECTION --}}
    <div class="bg-white p-4 rounded-2xl shadow-sm border border-slate-100 mb-6">
        <form action="{{ route('logs.index') }}" method="GET" class="flex flex-col md:flex-row gap-4">
            
            <div class="flex-1 relative">
                <i class="fas fa-search absolute left-3 top-3 text-slate-400"></i>
                <input type="text" name="search" value="{{ request('search') }}" 
                       class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-blue-500 focus:border-blue-500" 
                       placeholder="Cari pesan error...">
            </div>

            <div class="w-full md:w-48">
                <select name="level" onchange="this.form.submit()" class="w-full py-2.5 px-3 bg-white border border-slate-200 rounded-xl text-sm font-bold text-slate-600 focus:ring-blue-500">
                    <option value="ALL">Semua Level</option>
                    @foreach($levels as $lvl)
                        <option value="{{ $lvl }}" {{ request('level') == $lvl ? 'selected' : '' }}>{{ $lvl }}</option>
                    @endforeach
                </select>
            </div>

            <button type="submit" class="bg-slate-800 text-white px-5 py-2.5 rounded-xl font-bold text-sm hover:bg-black transition">
                Filter
            </button>
        </form>
    </div>

    {{-- TABLE LOGS --}}
    <div class="bg-white rounded-[2rem] shadow-xl shadow-slate-200/50 border border-slate-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr class="text-[10px] font-black text-slate-500 uppercase tracking-widest">
                        <th class="p-5 w-24 text-center">Level</th>
                        <th class="p-5 w-48">Waktu</th>
                        <th class="p-5">Pesan / Error</th>
                        <th class="p-5 w-20 text-center">Env</th>
                        <th class="p-5 w-20 text-center">Aksi</th>
                    </tr>
                </thead>
                {{-- Pastikan Alpine.js sudah dimuat di layout induk Anda --}}
{{-- <script src="//unpkg.com/alpinejs" defer></script> --}}

<tbody class="divide-y divide-slate-50" x-data="{ openModal: null }">
    @forelse($logs as $index => $log) {{-- Asumsi variabel $logs dari controller --}}
    <tr class="hover:bg-slate-50 transition-colors">
        {{-- Kolom LEVEL --}}
        <td class="p-5 align-top">
            {{-- Contoh logika warna badge --}}
            @php
                $levelColor = match(strtolower($log['level'])) {
                    'error' => 'bg-rose-100 text-rose-700 border-rose-200',
                    'warning' => 'bg-amber-100 text-amber-700 border-amber-200',
                    default => 'bg-blue-100 text-blue-700 border-blue-200'
                };
            @endphp
            <span class="px-3 py-1 rounded-full text-[10px] font-bold tracking-wide uppercase border {{ $levelColor }}">
                {{ $log['level'] }}
            </span>
        </td>

        {{-- Kolom WAKTU --}}
        <td class="p-5 align-top">
            <div class="font-bold text-slate-700 text-sm">{{ \Carbon\Carbon::parse($log['date'])->format('Y-m-d') }}</div>
            <div class="text-xs font-mono text-slate-500">{{ \Carbon\Carbon::parse($log['date'])->format('H:i:s') }}</div>
        </td>

        {{-- Kolom PESAN / ERROR (DENGAN TOMBOL LIHAT DETAIL) --}}
        <td class="p-5 align-top">
            <div class="flex items-start justify-between gap-3">
                {{-- Tampilkan 2 baris pertama saja agar rapi --}}
                <div class="text-sm text-slate-600 font-mono line-clamp-2" title="{{ $log['message'] }}">
                    {{ Str::limit($log['message'], 150) }}
                </div>
                
                {{-- Tombol untuk membuka Modal --}}
                <button @click="openModal = {{ $index }}" 
                        class="shrink-0 p-2 bg-white border border-slate-200 rounded-lg text-slate-500 hover:bg-blue-50 hover:text-blue-600 hover:border-blue-200 transition-all"
                        title="Lihat Pesan Lengkap">
                    <i class="fas fa-eye"></i>
                </button>
            </div>

            {{-- =================MODAL DETAIL PESAN================= --}}
            {{-- Modal ini tersembunyi dan hanya muncul saat tombol ditekan --}}
            <template x-if="openModal === {{ $index }}">
                <div class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm"
                     @click.self="openModal = null">
                    
                    <div class="bg-white w-full max-w-4xl rounded-2xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">
                        {{-- Header Modal --}}
                        <div class="flex justify-between items-center p-5 border-b border-slate-100 bg-slate-50">
                            <h3 class="text-lg font-bold text-slate-800">Detail Pesan Log</h3>
                            <button @click="openModal = null" class="text-slate-400 hover:text-slate-600 transition">
                                <i class="fas fa-times text-xl"></i>
                            </button>
                        </div>
                        
                        {{-- Body Modal (Tempat Pesan Lengkap) --}}
                        <div class="p-6 overflow-y-auto custom-scrollbar bg-slate-800">
                            {{-- Gunakan tag <pre> atau class 'whitespace-pre-wrap' agar format text terjaga --}}
                            <pre class="text-xs font-mono text-green-400 whitespace-pre-wrap break-all">{{ $log['message'] }}</pre>
                        </div>
                        
                        {{-- Footer Modal --}}
                        <div class="p-4 border-t border-slate-100 bg-slate-50 text-right">
                            <button @click="openModal = null" class="px-5 py-2.5 bg-white border border-slate-200 text-slate-600 font-bold rounded-xl text-sm hover:bg-slate-100 transition">
                                Tutup
                            </button>
                        </div>
                    </div>
                </div>
            </template>
            {{-- ===================================================== --}}
        </td>
    </tr>
    @empty
    <tr>
        <td colspan="3" class="p-8 text-center text-slate-400 italic">
            Tidak ada data log.
        </td>
    </tr>
    @endforelse
</tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="px-6 py-4 border-t border-slate-100 bg-white">
            {{ $logs->links() }}
        </div>
    </div>

</div>
@endsection