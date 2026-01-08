@extends('layouts.app')

@section('content')
<script src="https://cdn.tailwindcss.com"></script>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<div class="min-h-screen bg-slate-50 font-sans text-slate-900" x-data="{ openModal: null }">
    
    <div class="bg-gradient-to-r from-blue-700 to-indigo-800 pb-32 pt-10 shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex justify-between items-center">
            <div class="flex items-center space-x-4">
                <div class="bg-white/20 p-3 rounded-2xl backdrop-blur-md border border-white/30">
                    <i class="fas fa-shield-halved text-3xl text-white"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-black text-white tracking-tight uppercase">DANA Command Center</h1>
                    <p class="text-blue-100 text-sm font-medium">Monitoring & Instant Disbursement System v2.0</p>
                </div>
            </div>
            <div class="hidden md:flex space-x-3 text-white">
                <div class="bg-black/20 px-4 py-2 rounded-xl border border-white/10 text-xs font-bold">
                    <i class="far fa-calendar-alt mr-2"></i> {{ now()->format('d M Y') }}
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 -mt-20">
        
        {{-- Flash Messages --}}
        @if(session('success'))
        <div class="mb-6 p-4 bg-emerald-500 text-white rounded-2xl shadow-lg flex items-center animate-bounce">
            <i class="fas fa-check-circle mr-3"></i> <b>{{ session('success') }}</b>
        </div>
        @endif
        @if(session('error'))
        <div class="mb-6 p-4 bg-rose-500 text-white rounded-2xl shadow-lg flex items-center">
            <i class="fas fa-exclamation-triangle mr-3"></i> <b>{{ session('error') }}</b>
        </div>
        @endif

        <div class="bg-white rounded-[2.5rem] shadow-2xl overflow-hidden border border-gray-100">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50/50 border-b border-gray-100">
                        <th class="p-6 text-[10px] font-black text-gray-400 uppercase tracking-widest">Affiliate</th>
                        <th class="p-6 text-[10px] font-black text-gray-400 uppercase tracking-widest text-center">Status</th>
                        <th class="p-6 text-[10px] font-black text-gray-400 uppercase tracking-widest">Monitoring Saldo</th>
                        <th class="p-6 text-[10px] font-black text-gray-400 uppercase tracking-widest">Merchant Balance</th>
                        <th class="p-6 text-[10px] font-black text-gray-400 uppercase tracking-widest text-center">Panel Kontrol</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($affiliates as $aff)
                    <tr class="hover:bg-blue-50/30 transition-all">
                        <td class="p-6">
                            <div class="flex items-center space-x-3">
                                <div class="w-12 h-12 bg-indigo-100 rounded-2xl flex items-center justify-center text-indigo-600 font-bold text-xl shadow-inner">
                                    {{ substr($aff->name, 0, 1) }}
                                </div>
                                <div>
                                    <div class="font-bold text-gray-800 text-base">{{ $aff->name }}</div>
                                    <div class="text-xs text-gray-400 font-medium"><i class="fab fa-whatsapp text-emerald-500 mr-1"></i> {{ $aff->whatsapp }}</div>
                                    <span class="text-[9px] font-bold text-gray-300 uppercase tracking-tighter">ID: {{ $aff->id }}</span>
                                </div>
                            </div>
                        </td>
                        <td class="p-6 text-center">
                            @if($aff->dana_access_token)
                                <span class="bg-emerald-100 text-emerald-700 px-4 py-1.5 rounded-full text-[10px] font-black uppercase border border-emerald-200 inline-flex items-center">
                                    <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full mr-2 animate-pulse"></span> Terhubung
                                </span>
                            @else
                                <span class="bg-slate-100 text-slate-400 px-4 py-1.5 rounded-full text-[10px] font-black uppercase border border-slate-200">Terputus</span>
                            @endif
                        </td>
                        <td class="p-6">
                            <div class="space-y-3">
                                <div>
                                    <div class="text-[9px] font-bold text-gray-400 uppercase tracking-widest mb-1">Profit Affiliate</div>
                                    <div class="text-sm font-black text-slate-700">Rp {{ number_format($aff->balance, 0, ',', '.') }}</div>
                                </div>
                                <div class="p-2 bg-blue-50 rounded-xl border border-blue-100">
                                    <div class="text-[9px] font-bold text-blue-400 uppercase tracking-widest mb-1">Saldo Akun DANA (Real)</div>
                                    <div class="text-sm font-black text-blue-600">Rp {{ number_format($aff->dana_user_balance ?? 0, 0, ',', '.') }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="p-6">
                            <div class="text-[9px] font-bold text-rose-400 uppercase tracking-widest mb-1">Deposit Merchant</div>
                            <div class="text-lg font-black text-rose-600 tracking-tighter">Rp {{ number_format($aff->dana_merchant_balance ?? 0, 0, ',', '.') }}</div>
                            <form action="{{ route('dana.check_merchant_balance') }}" method="POST" class="mt-2">
                                @csrf
                                <input type="hidden" name="affiliate_id" value="{{ $aff->id }}">
                                <button type="submit" class="text-[10px] font-bold text-rose-400 hover:text-rose-700 flex items-center gap-1 uppercase transition-all">
                                    <i class="fas fa-sync-alt animate-spin-slow"></i> Refresh Merchant
                                </button>
                            </form>
                        </td>
                        <td class="p-6">
                            <div class="flex justify-center items-center gap-2">
                                {{-- TOMBOL BINDING --}}
                                <form action="{{ route('dana.do_bind') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="affiliate_id" value="{{ $aff->id }}">
                                    <button type="submit" class="w-10 h-10 bg-blue-50 text-blue-600 rounded-xl hover:bg-blue-600 hover:text-white shadow-sm transition-all flex items-center justify-center" title="Binding Akun">
                                        <i class="fas fa-link"></i>
                                    </button>
                                </form>

                                {{-- TOMBOL SYNC SALDO REAL --}}
                                <form action="{{ route('dana.check_balance') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="affiliate_id" value="{{ $aff->id }}">
                                    <button type="submit" class="w-10 h-10 bg-emerald-50 text-emerald-600 rounded-xl hover:bg-emerald-600 hover:text-white shadow-sm transition-all flex items-center justify-center {{ !$aff->dana_access_token ? 'opacity-30 cursor-not-allowed' : '' }}" {{ !$aff->dana_access_token ? 'disabled' : '' }} title="Sinkron Saldo Akun DANA">
                                        <i class="fas fa-rotate"></i>
                                    </button>
                                </form>

                                {{-- TOMBOL BUKA MODAL TOPUP --}}
                                <button @click="openModal = {{ $aff->id }}" class="w-10 h-10 bg-rose-50 text-rose-600 rounded-xl hover:bg-rose-600 hover:text-white shadow-sm transition-all flex items-center justify-center" title="Cairkan Profit">
                                    <i class="fas fa-hand-holding-dollar"></i>
                                </button>
                            </div>
                        </td>
                    </tr>

                    <template x-if="openModal === {{ $aff->id }}">
                        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/80 backdrop-blur-sm">
                            <div class="bg-white w-full max-w-md rounded-[3rem] shadow-2xl p-8 relative" @click.away="openModal = null">
                                <button @click="openModal = null" class="absolute top-8 right-8 text-slate-300 hover:text-slate-600 transition-colors">
                                    <i class="fas fa-times-circle text-2xl"></i>
                                </button>
                                
                                <div class="text-center mb-8">
                                    <div class="w-20 h-20 bg-rose-50 text-rose-500 rounded-[2rem] flex items-center justify-center mx-auto mb-4 shadow-inner">
                                        <i class="fas fa-bolt-lightning text-4xl"></i>
                                    </div>
                                    <h2 class="text-2xl font-black text-slate-800 tracking-tight">Cairkan Profit</h2>
                                    <p class="text-slate-400 text-sm font-medium">Pencairan Dana: <b>{{ $aff->name }}</b></p>
                                </div>

                                <div class="space-y-6">
                                    {{-- STEP 1: VERIFIKASI NAMA --}}
                                    <form action="{{ route('dana.account_inquiry') }}" method="POST" class="bg-slate-50 p-5 rounded-[2rem] border border-slate-100">
                                        @csrf
                                        <input type="hidden" name="affiliate_id" value="{{ $aff->id }}">
                                        <label class="text-[10px] font-black text-slate-400 uppercase block mb-3 tracking-[0.1em]">Verification Number</label>
                                        <div class="relative mb-4">
                                            <i class="fas fa-mobile-screen absolute left-4 top-1/2 -translate-y-1/2 text-slate-300"></i>
                                            <input type="text" name="phone" value="{{ $aff->whatsapp }}" class="w-full bg-white border-0 rounded-2xl font-bold py-3 pl-11 pr-4 focus:ring-2 focus:ring-blue-500 shadow-sm">
                                        </div>
                                        @if($aff->dana_user_name)
                                        <div class="mb-4 p-3 bg-emerald-50 border border-emerald-100 rounded-2xl text-xs font-bold text-emerald-600 flex items-center gap-2">
                                            <i class="fas fa-user-check"></i> {{ $aff->dana_user_name }}
                                        </div>
                                        @endif
                                        <button class="w-full py-3.5 bg-slate-800 text-white rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-slate-900 shadow-lg transition-all">
                                            <i class="fas fa-magnifying-glass mr-2"></i> Verifikasi Akun
                                        </button>
                                    </form>

                                    {{-- STEP 2: TRANSFER --}}
                                    <form action="{{ route('dana.topup') }}" method="POST" class="bg-rose-50 p-6 rounded-[2.5rem] border border-rose-100 shadow-inner">
                                        @csrf
                                        <input type="hidden" name="affiliate_id" value="{{ $aff->id }}">
                                        <input type="hidden" name="phone" value="{{ $aff->whatsapp }}">
                                        <div class="text-center mb-6">
                                            <p class="text-[10px] font-bold text-rose-300 uppercase tracking-widest mb-1">Available Profit</p>
                                            <p class="text-3xl font-black text-rose-600 tracking-tighter">Rp {{ number_format($aff->balance, 0, ',', '.') }}</p>
                                        </div>
                                        <div class="relative mb-6">
                                            <span class="absolute left-5 top-1/2 -translate-y-1/2 font-black text-slate-300">Rp</span>
                                            <input type="number" name="amount" value="1000" min="1000" max="{{ $aff->balance }}" class="w-full bg-white border-0 rounded-2xl py-4 pl-12 pr-6 text-right text-2xl font-black text-slate-800 focus:ring-2 focus:ring-rose-500 shadow-sm">
                                        </div>
                                        <button class="w-full py-4 bg-gradient-to-r from-rose-500 to-rose-600 text-white rounded-2xl font-black shadow-xl shadow-rose-200 hover:scale-[1.02] active:scale-[0.98] transition-all uppercase tracking-widest text-sm" onclick="return confirm('Kirim dana sekarang?')">
                                            <i class="fas fa-paper-plane mr-2"></i> Kirim Sekarang
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </template>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-16 pb-20">
        <div class="flex items-center gap-3 mb-8">
            <div class="w-10 h-10 bg-white rounded-xl shadow-sm flex items-center justify-center text-slate-400 border border-slate-100">
                <i class="fas fa-clock-rotate-left"></i>
            </div>
            <h3 class="text-xl font-black text-slate-800 tracking-tight uppercase">Audit Log Transaksi</h3>
        </div>
        <div class="bg-white rounded-[2rem] shadow-xl overflow-hidden border border-slate-100">
            <table class="w-full text-left">
                <thead class="bg-slate-50 border-b border-slate-100">
                    <tr>
                        <th class="p-6 text-[9px] font-black text-slate-400 uppercase tracking-[0.2em]">Timestamp</th>
                        <th class="p-6 text-[9px] font-black text-slate-400 uppercase tracking-[0.2em]">Activity Type</th>
                        <th class="p-6 text-[9px] font-black text-slate-400 uppercase tracking-[0.2em] text-center">Amount</th>
                        <th class="p-6 text-[9px] font-black text-slate-400 uppercase tracking-[0.2em]">Verification</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @php $logs = DB::table('dana_transactions')->orderBy('id', 'desc')->limit(10)->get(); @endphp
                    @foreach($logs as $log)
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="p-6 text-xs font-semibold text-slate-400">{{ $log->created_at }}</td>
                        <td class="p-6">
                            <span class="px-3 py-1 rounded-lg text-[9px] font-black tracking-widest uppercase {{ $log->type == 'TOPUP' ? 'bg-rose-100 text-rose-600' : 'bg-blue-100 text-blue-600' }}">
                                {{ $log->type }}
                            </span>
                        </td>
                        <td class="p-6 text-center font-black text-slate-700 text-sm italic">
                            Rp {{ number_format($log->amount, 0, ',', '.') }}
                        </td>
                        <td class="p-6 font-bold text-emerald-500 text-xs flex items-center gap-2">
                            <i class="fas fa-circle-check"></i> {{ $log->status }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
    .animate-spin-slow { animation: spin 3s linear infinite; }
    @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
</style>
@endsection