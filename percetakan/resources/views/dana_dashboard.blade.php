@extends('layouts.app')

@section('title', 'DANA Command Center')

@section('content')

{{-- Alpine JS & Styles --}}
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<style>
    [x-cloak] { display: none !important; }
    .custom-scrollbar::-webkit-scrollbar { height: 6px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: #f1f5f9; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
    .glass-effect { background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px); }
</style>

<div x-data="{ openModal: null }" x-cloak class="relative font-sans">

    {{-- HEADER --}}
    <div class="bg-gradient-to-r from-slate-800 to-slate-900 rounded-3xl p-6 lg:p-8 mb-8 shadow-xl border border-white/10 relative overflow-hidden">
        {{-- Decorative Background --}}
        <div class="absolute top-0 right-0 w-64 h-64 bg-blue-500/10 rounded-full blur-3xl -mr-16 -mt-16 pointer-events-none"></div>
        
        <div class="flex flex-col md:flex-row justify-between items-center gap-6 text-white relative z-10">
            <div class="flex items-center space-x-5">
                <div class="bg-white/10 p-4 rounded-2xl border border-white/20 shadow-inner">
                    <i class="fas fa-shield-alt text-3xl text-blue-400"></i>
                </div>
                <div>
                    <h1 class="text-2xl lg:text-3xl font-black tracking-tight uppercase text-white">Sancaka DANA Center</h1>
                    <p class="text-slate-400 text-sm font-medium">Monitoring & Disbursement System</p>
                </div>
            </div>
            
            <div class="bg-white/5 px-5 py-2.5 rounded-xl border border-white/10 text-xs font-bold text-slate-300 shadow-sm flex items-center">
                <i class="far fa-clock mr-2 text-blue-400"></i> {{ now()->format('d M Y') }}
            </div>
        </div>
    </div>

    {{-- MAIN TABLE --}}
    <div class="bg-white rounded-[2rem] shadow-xl shadow-slate-200/50 border border-slate-100 overflow-hidden mb-10">
        <div class="overflow-x-auto custom-scrollbar">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/80 border-b border-slate-100">
                        <th class="p-6 text-[10px] font-bold text-slate-500 uppercase tracking-widest text-center">Profile</th>
                        <th class="p-6 text-[10px] font-bold text-slate-500 uppercase tracking-widest text-center">Status Link</th>
                        <th class="p-6 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Monitoring Saldo</th>
                        <th class="p-6 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Deposit Merchant</th>
                        <th class="p-6 text-[10px] font-bold text-slate-500 uppercase tracking-widest text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @foreach($affiliates as $aff)
                    <tr class="hover:bg-slate-50/80 transition-all duration-200 group">
                        
                        {{-- 1. Profile --}}
                        <td class="p-6">
                            <div class="flex items-center space-x-4">
                                <div class="w-12 h-12 bg-indigo-50 rounded-2xl flex items-center justify-center text-indigo-600 font-black text-lg shadow-sm border border-indigo-100 uppercase">
                                    {{ substr($aff->name, 0, 1) }}
                                </div>
                                <div>
                                    <div class="font-bold text-slate-800 text-sm leading-tight">{{ $aff->name }}</div>
                                    <div class="text-xs text-slate-400 font-medium mt-1 flex items-center">
                                        <i class="fab fa-whatsapp text-green-500 mr-1"></i> {{ $aff->whatsapp }}
                                    </div>
                                </div>
                            </div>
                        </td>

                        {{-- 2. Status --}}
                        <td class="p-6 text-center">
                            @if($aff->dana_access_token)
                                <div class="inline-flex flex-col items-center">
                                    <span class="bg-emerald-100 text-emerald-700 px-3 py-1 rounded-full text-[10px] font-bold uppercase border border-emerald-200 inline-flex items-center shadow-sm">
                                        <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full mr-1.5 animate-pulse"></span> Linked
                                    </span>
                                </div>
                            @else
                                <span class="bg-slate-100 text-slate-400 px-3 py-1 rounded-full text-[10px] font-bold uppercase border border-slate-200">Offline</span>
                            @endif
                        </td>

                        {{-- 3. Monitoring Saldo (TOMBOL CEK SALDO ADA DISINI) --}}
                        <td class="p-6">
                            <div class="space-y-3">
                                <div>
                                    <div class="text-[9px] font-bold text-slate-400 uppercase tracking-wide mb-1">Profit Internal</div>
                                    <div class="text-sm font-black text-slate-700">Rp {{ number_format($aff->balance, 0, ',', '.') }}</div>
                                </div>
                                
                                {{-- Kotak Real Account + Tombol Refresh --}}
                                <div class="p-3 bg-blue-50/50 rounded-xl border border-blue-100 flex justify-between items-center group-hover:border-blue-200 transition-colors">
                                    <div>
                                        <div class="text-[9px] font-bold text-blue-400 uppercase tracking-wide">Real DANA</div>
                                        <div class="text-sm font-black text-blue-600">Rp {{ number_format($aff->dana_user_balance ?? 0, 0, ',', '.') }}</div>
                                    </div>
                                    
                                    {{-- TOMBOL CEK SALDO DIKEMBALIKAN --}}
                                    <form action="{{ route('dana.check_balance') }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="affiliate_id" value="{{ $aff->id }}">
                                        <button type="submit" class="w-8 h-8 bg-white text-blue-500 rounded-lg shadow-sm border border-blue-100 hover:bg-blue-600 hover:text-white hover:border-blue-600 transition-all flex items-center justify-center {{ !$aff->dana_access_token ? 'opacity-50 cursor-not-allowed' : '' }}" title="Sinkronisasi Saldo Real">
                                            <i class="fas fa-sync-alt text-xs {{ !$aff->dana_access_token ? '' : 'group-hover:animate-spin-slow' }}"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </td>

                        {{-- 4. Deposit Merchant --}}
                        <td class="p-6">
                            <div class="text-[9px] font-bold text-rose-400 uppercase tracking-wide mb-1">Deposit Merchant</div>
                            <div class="text-sm font-black text-rose-600">Rp {{ number_format($aff->dana_merchant_balance ?? 0, 0, ',', '.') }}</div>
                            <form action="{{ route('dana.check_merchant_balance') }}" method="POST" class="mt-2">
                                @csrf
                                <input type="hidden" name="affiliate_id" value="{{ $aff->id }}">
                                <button type="submit" class="text-[10px] font-bold text-rose-400 hover:text-rose-600 flex items-center gap-1 uppercase transition-colors">
                                    <i class="fas fa-redo-alt text-[9px]"></i> Refresh
                                </button>
                            </form>
                        </td>

                        {{-- 5. Aksi --}}
                        <td class="p-6">
                            <div class="flex justify-center items-center gap-2">
                                <form action="{{ route('dana.do_bind') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="affiliate_id" value="{{ $aff->id }}">
                                    <button class="w-10 h-10 bg-white text-slate-400 border border-slate-200 rounded-xl hover:bg-blue-50 hover:text-blue-600 hover:border-blue-200 transition-all flex items-center justify-center" title="Binding Akun">
                                        <i class="fas fa-link"></i>
                                    </button>
                                </form>

                                {{-- Trigger Modal --}}
                                <button type="button" 
                                        @click.prevent.stop="openModal = {{ $aff->id }}" 
                                        class="w-10 h-10 bg-rose-50 text-rose-600 border border-rose-100 rounded-xl hover:bg-rose-600 hover:text-white hover:border-rose-600 shadow-sm transition-all flex items-center justify-center cursor-pointer" 
                                        title="Cairkan Saldo">
                                    <i class="fas fa-paper-plane"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- LOG AREA --}}
    <div class="mt-12 pb-12">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-black text-slate-800 tracking-tight uppercase flex items-center">
                <span class="w-1 h-6 bg-blue-500 rounded-full mr-3"></span>
                Riwayat Transaksi Database
            </h3>
            <div class="bg-blue-50 text-blue-600 px-3 py-1 rounded-lg text-xs font-bold border border-blue-100">
                Total: {{ DB::table('dana_transactions')->count() }} Data
            </div>
        </div>

        <div class="bg-white rounded-[2rem] shadow-sm border border-slate-100 overflow-hidden">
            <div class="overflow-x-auto custom-scrollbar">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-slate-50 border-b border-slate-200">
                        <tr class="text-[10px] font-bold text-slate-500 uppercase tracking-widest whitespace-nowrap">
                            <th class="p-4 text-center">ID</th>
                            <th class="p-4">Waktu</th>
                            <th class="p-4">Ref No</th>
                            <th class="p-4">Type</th>
                            <th class="p-4">Tujuan (Phone)</th>
                            <th class="p-4 text-right">Nominal</th>
                            <th class="p-4 text-center">Status</th>
                            <th class="p-4">Response (Payload)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        {{-- Mengambil 50 data terakhir agar halaman tidak berat, ganti get() jika ingin SEMUA --}}
                        @php 
                            $logs = DB::table('dana_transactions')->orderBy('id', 'desc')->limit(50)->get(); 
                        @endphp

                        @foreach($logs as $log)
                        <tr class="hover:bg-blue-50/30 transition-colors group text-xs">
                            
                            {{-- ID & AFFILIATE --}}
                            <td class="p-4 text-center">
                                <span class="font-bold text-slate-700">#{{ $log->id }}</span>
                                <div class="text-[9px] text-slate-400 mt-0.5">Aff: {{ $log->affiliate_id }}</div>
                            </td>

                            {{-- WAKTU --}}
                            <td class="p-4 whitespace-nowrap">
                                <div class="font-bold text-slate-600">{{ \Carbon\Carbon::parse($log->created_at)->format('d M Y') }}</div>
                                <div class="text-[10px] text-slate-400 font-mono">{{ \Carbon\Carbon::parse($log->created_at)->format('H:i:s') }}</div>
                            </td>

                            {{-- REFERENCE NO --}}
                            <td class="p-4">
                                <span class="font-mono text-slate-600 bg-slate-100 px-2 py-1 rounded border border-slate-200 select-all">
                                    {{ $log->reference_no }}
                                </span>
                            </td>

                            {{-- TYPE --}}
                            <td class="p-4">
                                @php
                                    $typeColor = match($log->type) {
                                        'TOPUP' => 'bg-rose-50 text-rose-600 border-rose-100',
                                        'BINDING' => 'bg-indigo-50 text-indigo-600 border-indigo-100',
                                        'INQUIRY' => 'bg-amber-50 text-amber-600 border-amber-100',
                                        default => 'bg-slate-50 text-slate-600 border-slate-100'
                                    };
                                @endphp
                                <span class="px-2.5 py-1 rounded-lg text-[10px] font-bold tracking-wide uppercase border {{ $typeColor }}">
                                    {{ $log->type }}
                                </span>
                            </td>

                            {{-- PHONE --}}
                            <td class="p-4">
                                @if($log->phone && $log->phone != '-')
                                    <span class="font-mono text-slate-600">{{ $log->phone }}</span>
                                @else
                                    <span class="text-slate-300 italic">-</span>
                                @endif
                            </td>

                            {{-- AMOUNT --}}
                            <td class="p-4 text-right">
                                @if($log->amount > 0)
                                    <span class="font-black text-slate-700">Rp {{ number_format($log->amount, 0, ',', '.') }}</span>
                                @else
                                    <span class="text-slate-300 font-bold">0</span>
                                @endif
                            </td>

                            {{-- STATUS --}}
                            <td class="p-4 text-center">
                                @if($log->status == 'SUCCESS')
                                    <span class="inline-flex items-center gap-1 text-[10px] font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-100">
                                        <i class="fas fa-check-circle"></i> SUKSES
                                    </span>
                                @elseif($log->status == 'PENDING')
                                    <span class="inline-flex items-center gap-1 text-[10px] font-bold text-amber-600 bg-amber-50 px-2 py-0.5 rounded-full border border-amber-100">
                                        <i class="fas fa-clock"></i> PENDING
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 text-[10px] font-bold text-rose-600 bg-rose-50 px-2 py-0.5 rounded-full border border-rose-100">
                                        <i class="fas fa-times-circle"></i> {{ $log->status }}
                                    </span>
                                @endif
                            </td>

                            {{-- PAYLOAD (JSON) --}}
                            <td class="p-4">
                                @if($log->response_payload)
                                    <div class="group/payload relative">
                                        <div class="font-mono text-[9px] text-slate-400 bg-slate-50 p-1.5 rounded border border-slate-100 max-w-[150px] truncate cursor-help">
                                            {{ $log->response_payload }}
                                        </div>
                                        {{-- Tooltip on Hover --}}
                                        <div class="absolute right-0 bottom-full mb-2 w-64 bg-slate-800 text-white text-[10px] p-2 rounded shadow-lg opacity-0 group-hover/payload:opacity-100 pointer-events-none transition-opacity z-10 font-mono break-all whitespace-normal">
                                            {{ Str::limit($log->response_payload, 200) }}
                                        </div>
                                    </div>
                                @else
                                    <span class="text-slate-200">-</span>
                                @endif
                            </td>

                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            {{-- Footer info --}}
            <div class="bg-slate-50 px-6 py-3 border-t border-slate-200 text-[10px] text-slate-400 font-bold uppercase tracking-wider text-center">
                Menampilkan 50 Transaksi Terakhir
            </div>
        </div>
    </div>

    {{-- 
        ====================================================
        MODAL AREA (DESAIN BARU & LEBIH RAPI)
        ====================================================
    --}}
    @foreach($affiliates as $aff)
        <div x-show="openModal == {{ $aff->id }}" 
             style="display: none" 
             class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm"
             x-transition.opacity.duration.200ms>
            
            <div class="bg-white w-full max-w-3xl rounded-[2rem] shadow-2xl overflow-hidden relative" 
                 @click.away="openModal = null"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                 x-transition:enter-end="opacity-100 scale-100 translate-y-0">
                
                {{-- Close Button --}}
                <button @click="openModal = null" class="absolute top-4 right-4 z-20 w-8 h-8 flex items-center justify-center rounded-full bg-slate-100 text-slate-400 hover:bg-slate-200 hover:text-slate-600 transition-colors cursor-pointer">
                    <i class="fas fa-times"></i>
                </button>

                <div class="flex flex-col md:flex-row">
                    
                    {{-- KIRI: VERIFIKASI (Biru Muda/Abu) --}}
                    <div class="w-full md:w-5/12 bg-slate-50 p-8 border-r border-slate-100 flex flex-col justify-center">
                        <div class="text-center mb-6">
                            <div class="w-16 h-16 bg-blue-100 text-blue-600 rounded-2xl flex items-center justify-center text-2xl mx-auto mb-4 shadow-sm">
                                <i class="fas fa-shield-alt"></i>
                            </div>
                            <h3 class="text-lg font-black text-slate-800 uppercase tracking-tight">Verifikasi</h3>
                            <p class="text-xs text-slate-400 font-bold uppercase tracking-widest">Cek Validitas Akun</p>
                        </div>

                        <form action="{{ route('dana.account_inquiry') }}" method="POST" class="space-y-4">
                            @csrf
                            <input type="hidden" name="affiliate_id" value="{{ $aff->id }}">
                            
                            <div class="space-y-1">
                                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider pl-1">Nomor DANA</label>
                                <input type="text" name="phone" value="{{ $aff->whatsapp }}" class="w-full bg-white border border-slate-200 rounded-xl font-bold py-3 px-4 text-center text-slate-700 shadow-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition-all">
                            </div>

                            @if($aff->dana_user_name)
                            <div class="p-3 bg-emerald-50 border border-emerald-100 rounded-xl text-center">
                                <div class="text-[10px] font-bold text-emerald-400 uppercase tracking-wider mb-0.5">Terverifikasi</div>
                                <div class="text-sm font-black text-emerald-700 truncate px-2">{{ $aff->dana_user_name }}</div>
                            </div>
                            @endif

                            <button class="w-full py-3 bg-slate-800 text-white rounded-xl font-bold text-xs uppercase tracking-widest hover:bg-black transition-all shadow-lg shadow-slate-200 mt-2">
                                <i class="fas fa-search mr-2"></i> Cek Akun
                            </button>
                        </form>
                    </div>

                    {{-- KANAN: PENCAIRAN (Putih Bersih) --}}
                    <div class="w-full md:w-7/12 p-8 relative">
                        <div class="mb-8 flex justify-between items-start">
                            <div>
                                <h3 class="text-xl font-black text-slate-800 uppercase tracking-tight">Pencairan</h3>
                                <p class="text-xs text-slate-400 font-bold uppercase tracking-widest mt-1">Transfer Profit</p>
                            </div>
                            <div class="text-right">
                                <div class="text-[10px] font-bold text-slate-300 uppercase">Penerima</div>
                                <div class="font-bold text-slate-700 text-sm">{{ Str::limit($aff->name, 15) }}</div>
                            </div>
                        </div>

                        <form action="{{ route('dana.topup') }}" method="POST" class="space-y-6">
                            @csrf
                            <input type="hidden" name="affiliate_id" value="{{ $aff->id }}">
                            <input type="hidden" name="phone" value="{{ $aff->whatsapp }}">

                            {{-- Info Cards --}}
                            <div class="grid grid-cols-2 gap-3">
                                <div class="p-3 bg-blue-50 rounded-xl border border-blue-100 text-center">
                                    <div class="text-[10px] font-bold text-blue-400 uppercase tracking-wide">Saldo Profit</div>
                                    <div class="text-base font-black text-blue-600">Rp {{ number_format($aff->balance, 0, ',', '.') }}</div>
                                </div>
                                <div class="p-3 bg-slate-50 rounded-xl border border-slate-100 text-center">
                                    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wide">Status</div>
                                    <div class="text-xs font-bold text-slate-600 mt-0.5">Siap Transfer</div>
                                </div>
                            </div>

                            {{-- Input Nominal --}}
                            <div class="space-y-2">
                                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider pl-1">Nominal Transfer</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                        <span class="text-slate-400 font-bold text-lg">Rp</span>
                                    </div>
                                    <input type="number" 
                                           name="amount" 
                                           value="1000" 
                                           min="1000" 
                                           max="{{ $aff->balance }}" 
                                           class="w-full bg-slate-50 border border-slate-200 rounded-2xl py-4 pl-12 pr-4 text-3xl font-black text-slate-800 focus:ring-4 focus:ring-rose-500/10 focus:border-rose-500 outline-none transition-all placeholder-slate-300" 
                                           required>
                                </div>
                            </div>

                            {{-- Button Eksekusi --}}
                            <button type="submit" 
                                    class="w-full py-4 bg-gradient-to-r from-rose-500 to-rose-600 text-white rounded-2xl font-black text-sm shadow-xl shadow-rose-200 hover:scale-[1.01] hover:shadow-rose-300 transition-all uppercase tracking-widest flex items-center justify-center gap-2" 
                                    onclick="return confirm('Yakin ingin mencairkan saldo ini?')">
                                <i class="fas fa-paper-plane"></i> Eksekusi Transfer
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endforeach

</div>
@endsection