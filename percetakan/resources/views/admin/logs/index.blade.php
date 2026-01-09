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
                {{-- Pastikan AlpineJS sudah di-load di layout utama --}}
{{-- <script src="//unpkg.com/alpinejs" defer></script> --}}

<tbody class="divide-y divide-slate-100" x-data="{ activeLog: null }">
    @forelse($logs as $index => $log)
    <tr class="hover:bg-slate-50 transition-colors group">
        
        {{-- 1. LEVEL BADGE --}}
        <td class="p-5 align-top w-32">
            @php
                $level = strtoupper($log['level']);
                $badgeClass = match($level) {
                    'ERROR', 'CRITICAL', 'EMERGENCY' => 'bg-rose-100 text-rose-700 border-rose-200',
                    'WARNING', 'ALERT' => 'bg-amber-100 text-amber-700 border-amber-200',
                    'INFO', 'NOTICE' => 'bg-blue-100 text-blue-700 border-blue-200',
                    'DEBUG' => 'bg-slate-100 text-slate-600 border-slate-200',
                    default => 'bg-gray-100 text-gray-600 border-gray-200'
                };
            @endphp
            <span class="inline-flex items-center justify-center w-full py-1.5 rounded-lg text-[10px] font-black tracking-wider uppercase border {{ $badgeClass }}">
                {{ $level }}
            </span>
        </td>

        {{-- 2. WAKTU --}}
        <td class="p-5 align-top w-48">
            <div class="font-mono text-xs font-bold text-slate-700">
                {{ \Carbon\Carbon::parse($log['date'])->format('Y-m-d') }}
            </div>
            <div class="font-mono text-[10px] text-slate-400 mt-1">
                {{ \Carbon\Carbon::parse($log['date'])->format('H:i:s') }}
            </div>
        </td>

        {{-- 3. PESAN LOG (PREVIEW) --}}
        <td class="p-5 align-top">
            <div class="relative group/msg">
                {{-- Tampilkan 2 baris awal saja di tabel --}}
                <div class="font-mono text-xs text-slate-600 leading-relaxed line-clamp-2 break-all cursor-pointer hover:text-blue-600 transition-colors"
                     @click="activeLog = {{ json_encode($log) }}">
                    {{ Str::limit($log['message'], 250) }}
                </div>
                
                {{-- Tooltip "Klik untuk detail" --}}
                <div class="absolute -top-8 left-0 hidden group-hover/msg:block bg-slate-800 text-white text-[10px] px-2 py-1 rounded shadow-lg">
                    Klik untuk lihat detail
                </div>
            </div>
        </td>

        {{-- 4. AKSI --}}
        <td class="p-5 align-top text-right">
            <button @click="activeLog = {{ json_encode($log) }}" 
                    class="p-2 rounded-lg border border-slate-200 text-slate-400 hover:text-blue-600 hover:border-blue-200 hover:bg-blue-50 transition-all shadow-sm"
                    title="Lihat Detail Log">
                <i class="fas fa-terminal text-sm"></i>
            </button>
        </td>
    </tr>
    @empty
    <tr>
        <td colspan="4" class="p-12 text-center">
            <div class="flex flex-col items-center justify-center text-slate-300">
                <i class="fas fa-check-circle text-5xl mb-4"></i>
                <p class="text-sm font-bold text-slate-400">Tidak ada log ditemukan.</p>
                <p class="text-xs">Sistem berjalan normal.</p>
            </div>
        </td>
    </tr>
    @endforelse

    {{-- ============================================================== --}}
    {{-- MODAL DETAIL (TERMINAL STYLE) --}}
    {{-- ============================================================== --}}
    <div x-show="activeLog" 
         style="display: none" 
         class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-slate-900/80 backdrop-blur-sm"
         x-transition.opacity>
        
        <div class="bg-slate-900 w-full max-w-5xl rounded-2xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh] border border-slate-700" 
             @click.away="activeLog = null">
            
            {{-- Header Modal --}}
            <div class="flex justify-between items-center px-6 py-4 bg-slate-800 border-b border-slate-700">
                <div class="flex items-center gap-4">
                    {{-- Tombol ala Mac --}}
                    <div class="flex gap-2">
                        <div class="w-3 h-3 rounded-full bg-red-500"></div>
                        <div class="w-3 h-3 rounded-full bg-yellow-500"></div>
                        <div class="w-3 h-3 rounded-full bg-green-500"></div>
                    </div>
                    <div class="h-6 w-[1px] bg-slate-600 mx-2"></div>
                    
                    {{-- Info Level & Waktu --}}
                    <div>
                        <span class="text-xs font-mono text-slate-400 mr-2" x-text="activeLog?.date"></span>
                        <span class="text-xs font-bold px-2 py-0.5 rounded uppercase tracking-wider"
                              :class="{
                                  'bg-rose-500/20 text-rose-400': ['ERROR','CRITICAL'].includes(activeLog?.level),
                                  'bg-amber-500/20 text-amber-400': activeLog?.level === 'WARNING',
                                  'bg-blue-500/20 text-blue-400': activeLog?.level === 'INFO',
                                  'bg-slate-700 text-slate-300': !['ERROR','CRITICAL','WARNING','INFO'].includes(activeLog?.level)
                              }"
                              x-text="activeLog?.level">
                        </span>
                    </div>
                </div>
                
                {{-- Tombol Close & Copy --}}
                <div class="flex items-center gap-3">
                    <button @click="navigator.clipboard.writeText(activeLog.message); alert('Log disalin!')" class="text-slate-400 hover:text-white transition text-xs flex items-center gap-1">
                        <i class="far fa-copy"></i> Copy
                    </button>
                    <button @click="activeLog = null" class="text-slate-400 hover:text-white transition">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
            </div>

            {{-- Body Modal (Terminal Output) --}}
            <div class="flex-1 p-6 overflow-y-auto custom-scrollbar bg-[#0f172a]">
                <pre class="font-mono text-xs leading-relaxed whitespace-pre-wrap break-all" 
                     :class="{
                        'text-rose-400': ['ERROR','CRITICAL'].includes(activeLog?.level),
                        'text-amber-400': activeLog?.level === 'WARNING',
                        'text-emerald-400': activeLog?.level === 'INFO', 
                        'text-slate-300': !['ERROR','CRITICAL','WARNING','INFO'].includes(activeLog?.level)
                     }" 
                     x-text="activeLog?.message"></pre>
            </div>

            {{-- Footer Info --}}
            <div class="px-6 py-2 bg-slate-800 border-t border-slate-700 text-[10px] text-slate-500 font-mono flex justify-between">
                <span>Environment: <span class="text-slate-300 uppercase" x-text="activeLog?.env"></span></span>
                <span>Laravel Log Viewer</span>
            </div>
        </div>
    </div>

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