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
                <tbody class="divide-y divide-slate-50 text-sm" x-data="{ activeLog: null }">
                    @forelse($logs as $index => $log)
                    <tr class="hover:bg-slate-50 transition-colors group">
                        
                        {{-- LEVEL BADGE --}}
                        <td class="p-5 text-center align-top">
                            @php
                                $color = match($log['level']) {
                                    'ERROR', 'CRITICAL', 'EMERGENCY' => 'bg-rose-100 text-rose-700 border-rose-200',
                                    'WARNING', 'ALERT' => 'bg-amber-100 text-amber-700 border-amber-200',
                                    'INFO', 'NOTICE' => 'bg-blue-100 text-blue-700 border-blue-200',
                                    'DEBUG' => 'bg-slate-100 text-slate-600 border-slate-200',
                                    default => 'bg-slate-100 text-slate-600 border-slate-200'
                                };
                                $icon = match($log['level']) {
                                    'ERROR', 'CRITICAL' => 'fa-times-circle',
                                    'WARNING' => 'fa-exclamation-triangle',
                                    'INFO' => 'fa-info-circle',
                                    default => 'fa-bug'
                                };
                            @endphp
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[9px] font-black border {{ $color }}">
                                <i class="fas {{ $icon }}"></i> {{ $log['level'] }}
                            </span>
                        </td>

                        {{-- WAKTU --}}
                        <td class="p-5 align-top">
                            <span class="text-xs font-mono font-bold text-slate-600">
                                {{ $log['date'] }}
                            </span>
                        </td>

                        {{-- PESAN (TRUNCATED) --}}
                        <td class="p-5 align-top">
                            <div class="font-mono text-xs text-slate-700 line-clamp-2 log-font">
                                {{ Str::limit($log['message'], 150) }}
                            </div>
                        </td>

                        {{-- ENVIRONMENT --}}
                        <td class="p-5 align-top text-center">
                            <span class="text-[10px] font-bold text-slate-400 uppercase">{{ $log['env'] }}</span>
                        </td>

                        {{-- AKSI (MODAL TRIGGER) --}}
                        <td class="p-5 align-top text-center">
                            <button @click="activeLog = {{ json_encode($log) }}" 
                                    class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 hover:bg-indigo-600 hover:text-white transition flex items-center justify-center border border-indigo-100 shadow-sm">
                                <i class="fas fa-eye text-xs"></i>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="p-10 text-center text-slate-400 italic bg-slate-50">
                            <i class="fas fa-check-circle text-4xl mb-3 text-slate-300"></i>
                            <p>Tidak ada log ditemukan atau file log bersih.</p>
                        </td>
                    </tr>
                    @endforelse

                    {{-- MODAL DETAIL --}}
                    <div x-show="activeLog" 
                         style="display: none"
                         class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-slate-900/70 backdrop-blur-sm"
                         x-transition.opacity>
                        
                        <div class="bg-white w-full max-w-4xl rounded-2xl shadow-2xl overflow-hidden flex flex-col max-h-[85vh]" @click.away="activeLog = null">
                            
                            {{-- Modal Header --}}
                            <div class="bg-slate-100 px-6 py-4 border-b border-slate-200 flex justify-between items-center">
                                <div class="flex items-center gap-3">
                                    <span class="font-black text-slate-700 text-lg">Log Detail</span>
                                    <span class="text-xs font-mono bg-slate-200 px-2 py-1 rounded text-slate-600" x-text="activeLog?.date"></span>
                                    <span class="text-xs font-bold px-2 py-1 rounded uppercase" 
                                          :class="{
                                              'bg-rose-100 text-rose-700': ['ERROR','CRITICAL'].includes(activeLog?.level),
                                              'bg-amber-100 text-amber-700': activeLog?.level === 'WARNING',
                                              'bg-blue-100 text-blue-700': activeLog?.level === 'INFO',
                                              'bg-slate-200 text-slate-700': !['ERROR','CRITICAL','WARNING','INFO'].includes(activeLog?.level)
                                          }"
                                          x-text="activeLog?.level">
                                    </span>
                                </div>
                                <button @click="activeLog = null" class="text-slate-400 hover:text-slate-700 transition"><i class="fas fa-times text-xl"></i></button>
                            </div>

                            {{-- Modal Body (Scrollable) --}}
                            <div class="p-6 overflow-y-auto bg-slate-50 custom-scrollbar flex-1">
                                <div class="bg-slate-900 text-green-400 p-4 rounded-xl font-mono text-xs leading-relaxed whitespace-pre-wrap break-all shadow-inner border border-slate-700 h-full overflow-auto">
                                    <span x-text="activeLog?.message"></span>
                                </div>
                            </div>

                            {{-- Modal Footer --}}
                            <div class="bg-white px-6 py-4 border-t border-slate-200 text-right">
                                <button @click="activeLog = null" class="bg-slate-800 text-white px-6 py-2 rounded-lg font-bold text-sm hover:bg-black transition">Tutup</button>
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