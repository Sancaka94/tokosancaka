@extends('layouts.app')

@section('title', 'DANA Command Center')

@section('content')
<div x-data="{ openModal: null, refreshing: false }" x-cloak>
    
    <div class="bg-gradient-to-r from-blue-600 to-indigo-700 rounded-3xl p-6 lg:p-8 mb-8 shadow-lg border border-white/20">
        <div class="flex flex-col md:flex-row justify-between items-center gap-6">
            <div class="flex items-center space-x-5">
                <div class="bg-white/20 p-4 rounded-2xl backdrop-blur-md border border-white/30 shadow-inner">
                    <i class="fas fa-shield-halved text-3xl text-white"></i>
                </div>
                <div>
                    <h1 class="text-2xl lg:text-3xl font-black text-white tracking-tight uppercase">DANA COMMAND CENTER</h1>
                    <p class="text-blue-100 text-sm font-medium">Monitoring Profit & Instant Disbursement System v2.0</p>
                </div>
            </div>
            <div class="flex items-center space-x-3">
                <div class="bg-black/20 px-4 py-2 rounded-xl border border-white/10 text-white text-xs font-bold shadow-sm">
                    <i class="far fa-clock mr-2"></i> {{ now()->format('H:i') }} WIB
                </div>
            </div>
        </div>
    </div>

    @if(session('error'))
        <div class="mb-6 p-4 bg-rose-50 border-l-4 border-rose-500 rounded-xl flex items-center shadow-md animate-pulse">
            <i class="fas fa-exclamation-triangle text-rose-500 mr-3 text-xl"></i>
            <span class="text-rose-800 font-bold">{{ session('error') }}</span>
        </div>
    @endif

    <div class="bg-white rounded-[2rem] shadow-xl shadow-slate-200/50 border border-slate-100 overflow-hidden mb-10">
        <div class="overflow-x-auto custom-scrollbar">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-100">
                        <th class="p-6 text-[10px] font-black text-slate-400 uppercase tracking-widest">Affiliate Profile</th>
                        <th class="p-6 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Connection</th>
                        <th class="p-6 text-[10px] font-black text-slate-400 uppercase tracking-widest">Balance Metrics</th>
                        <th class="p-6 text-[10px] font-black text-slate-400 uppercase tracking-widest">Merchant Safe</th>
                        <th class="p-6 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Panel Kontrol</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @foreach($affiliates as $aff)
                    <tr class="hover:bg-blue-50/40 transition-all duration-200">
                        <td class="p-6">
                            <div class="flex items-center space-x-4">
                                <div class="w-12 h-12 bg-indigo-100 rounded-2xl flex items-center justify-center text-indigo-600 font-bold text-xl shadow-inner uppercase">
                                    {{ substr($aff->name, 0, 1) }}
                                </div>
                                <div>
                                    <div class="font-bold text-slate-800 text-base leading-tight">{{ $aff->name }}</div>
                                    <div class="text-xs text-slate-400 font-medium mt-1">
                                        <i class="fab fa-whatsapp text-emerald-500 mr-1"></i> {{ $aff->whatsapp }}
                                    </div>
                                    <span class="inline-block mt-1 px-2 py-0.5 bg-slate-100 text-slate-400 rounded text-[9px] font-bold uppercase">ID: {{ $aff->id }}</span>
                                </div>
                            </div>
                        </td>

                        <td class="p-6 text-center">
                            @if($aff->dana_access_token)
                                <span class="bg-emerald-100 text-emerald-700 px-4 py-1.5 rounded-full text-[10px] font-black uppercase border border-emerald-200 inline-flex items-center shadow-sm">
                                    <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full mr-2 animate-pulse"></span> Linked
                                </span>
                            @else
                                <span class="bg-slate-100 text-slate-400 px-4 py-1.5 rounded-full text-[10px] font-black uppercase border border-slate-200 italic">Offline</span>
                            @endif
                        </td>

                        <td class="p-6">
                            <div class="space-y-3">
                                <div>
                                    <div class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Profit Sancaka (Internal)</div>
                                    <div class="text-sm font-black text-slate-700 tracking-tight">Rp {{ number_format($aff->balance, 0, ',', '.') }}</div>
                                </div>
                                <div class="p-2.5 bg-blue-50 rounded-xl border border-blue-100 shadow-sm">
                                    <div class="text-[9px] font-black text-blue-400 uppercase tracking-widest mb-1 italic">Real DANA Account</div>
                                    <div class="text-sm font-black text-blue-600">Rp {{ number_format($aff->dana_user_balance ?? 0, 0, ',', '.') }}</div>
                                </div>
                            </div>
                        </td>

                        <td class="p-6">
                            <div class="text-[9px] font-black text-rose-400 uppercase tracking-widest mb-1">Main Merchant Balance</div>
                            <div class="text-lg font-black text-rose-600 tracking-tighter">Rp {{ number_format($aff->dana_merchant_balance ?? 0, 0, ',', '.') }}</div>
                            <form action="{{ route('dana.check_merchant_balance') }}" method="POST" class="mt-2">
                                @csrf
                                <input type="hidden" name="affiliate_id" value="{{ $aff->id }}">
                                <button type="submit" class="text-[10px] font-bold text-rose-400 hover:text-rose-700 flex items-center gap-1.5 uppercase transition-colors group">
                                    <i class="fas fa-sync-alt group-hover:rotate-180 transition-transform duration-500"></i> Refresh Merchant
                                </button>
                            </form>
                        </td>

                        <td class="p-6">
                            <div class="flex justify-center items-center gap-3">
                                {{-- BINDING REDIRECT --}}
                                <form action="{{ route('dana.do_bind') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="affiliate_id" value="{{ $aff->id }}">
                                    <button type="submit" class="w-10 h-10 bg-blue-50 text-blue-600 rounded-xl hover:bg-blue-600 hover:text-white shadow-sm transition-all flex items-center justify-center group" title="OAuth Binding">
                                        <i class="fas fa-link group-hover:scale-110 transition-transform"></i>
                                    </button>
                                </form>

                                {{-- SYNC SALDO REAL --}}
                                <form action="{{ route('dana.check_balance') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="affiliate_id" value="{{ $aff->id }}">
                                    <button type="submit" class="w-10 h-10 bg-emerald-50 text-emerald-600 rounded-xl hover:bg-emerald-600 hover:text-white shadow-sm transition-all flex items-center justify-center group {{ !$aff->dana_access_token ? 'opacity-30 cursor-not-allowed' : '' }}" {{ !$aff->dana_access_token ? 'disabled' : '' }} title="Balance Inquiry">
                                        <i class="fas fa-radar group-hover:animate-pulse transition-transform"></i>
                                    </button>
                                </form>

                                {{-- OPEN MODAL DISBURSEMENT (Pencairan) --}}
                                <button @click="openModal = {{ $aff->id }}" 
                                        class="w-10 h-10 bg-rose-50 text-rose-600 rounded-xl hover:bg-rose-600 hover:text-white shadow-sm transition-all flex items-center justify-center group" 
                                        title="Cairkan Profit">
                                    <i class="fas fa-hand-holding-dollar group-hover:-translate-y-1 transition-transform"></i>
                                </button>
                            </div>
                        </td>
                    </tr>

                    <template x-if="openModal === {{ $aff->id }}">
    <div class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-900/80 backdrop-blur-sm shadow-2xl">
        <div class="bg-white w-full max-w-4xl rounded-[3rem] shadow-2xl overflow-hidden relative border border-slate-100" @click.away="openModal = null">
            
            <button @click="openModal = null" class="absolute top-6 right-8 text-slate-300 hover:text-slate-600 transition-colors z-10">
                <i class="fas fa-times-circle text-2xl"></i>
            </button>

            <div class="flex flex-col md:flex-row">
                <div class="w-full md:w-5/12 bg-slate-50 p-8 border-r border-slate-100">
                    <div class="flex items-center gap-4 mb-6">
                        <div class="h-12 w-12 bg-blue-600 rounded-2xl flex items-center justify-center text-white shadow-lg shadow-blue-500/30">
                            <i class="fas fa-user-check text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-xl font-black text-slate-800 tracking-tight leading-none">Verifikasi</h3>
                            <p class="text-[10px] text-slate-400 font-bold uppercase mt-1 tracking-widest">Tahap 1: Akun Penerima</p>
                        </div>
                    </div>

                    <form action="{{ route('dana.account_inquiry') }}" method="POST" class="space-y-4">
                        @csrf
                        <input type="hidden" name="affiliate_id" value="{{ $aff->id }}">
                        
                        <div class="space-y-2">
                            <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-1">Nomor DANA</label>
                            <div class="relative">
                                <i class="fas fa-phone-alt absolute left-4 top-1/2 -translate-y-1/2 text-slate-300 text-sm"></i>
                                <input type="text" name="phone" value="{{ $aff->whatsapp }}" class="w-full bg-white border-0 rounded-2xl font-bold py-3 pl-11 pr-4 focus:ring-2 focus:ring-blue-500 shadow-sm text-slate-700">
                            </div>
                        </div>

                        @if($aff->dana_user_name)
                        <div class="p-4 bg-emerald-50 border border-emerald-100 rounded-2xl">
                            <p class="text-[9px] font-black text-emerald-400 uppercase tracking-widest mb-1">Nama Terdaftar:</p>
                            <p class="text-sm font-black text-emerald-700 italic flex items-center gap-2">
                                <i class="fas fa-check-circle"></i> {{ $aff->dana_user_name }}
                            </p>
                        </div>
                        @endif

                        <button class="w-full py-3.5 bg-slate-800 text-white rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-slate-900 shadow-lg transition-all group">
                            <i class="fas fa-magnifying-glass mr-2 group-hover:scale-110 transition-transform"></i> Cek Nama Akun
                        </button>
                    </form>
                </div>

                <div class="w-full md:w-7/12 p-8 bg-white">
                    <div class="flex items-center justify-between mb-8">
                        <div>
                            <h3 class="text-xl font-black text-slate-800 tracking-tight leading-none">Pencairan</h3>
                            <p class="text-[10px] text-slate-400 font-bold uppercase mt-1 tracking-widest">Tahap 2: Eksekusi Dana</p>
                        </div>
                        <div class="text-right">
                            <p class="text-[9px] font-black text-slate-300 uppercase leading-none mb-1">Affiliate Name</p>
                            <p class="font-bold text-slate-700 leading-none">{{ $aff->name }}</p>
                        </div>
                    </div>

                    <form action="{{ route('dana.topup') }}" method="POST" class="space-y-6">
                        @csrf
                        <input type="hidden" name="affiliate_id" value="{{ $aff->id }}">
                        <input type="hidden" name="phone" value="{{ $aff->whatsapp }}">

                        <div class="grid grid-cols-2 gap-4">
                            <div class="p-4 bg-blue-50 rounded-2xl border border-blue-100">
                                <p class="text-[9px] font-black text-blue-400 uppercase tracking-widest mb-1">Profit Tersedia</p>
                                <p class="text-lg font-black text-blue-600 tracking-tighter italic">Rp {{ number_format($aff->balance, 0, ',', '.') }}</p>
                            </div>
                            <div class="p-4 bg-slate-50 rounded-2xl border border-slate-100">
                                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Status Saldo</p>
                                <p class="text-xs font-bold text-slate-600 italic">Siap Dicairkan</p>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-1 text-center block">Input Nominal Pencairan</label>
                            <div class="relative">
                                <span class="absolute left-6 top-1/2 -translate-y-1/2 font-black text-slate-300 text-xl">Rp</span>
                                <input type="number" name="amount" value="1000" min="1000" max="{{ $aff->balance }}" class="w-full bg-white border border-slate-100 rounded-[2rem] py-5 pl-16 pr-8 text-right text-3xl font-black text-slate-800 focus:ring-4 focus:ring-rose-500/10 focus:border-rose-500 shadow-inner transition-all" required>
                            </div>
                        </div>

                        <button type="submit" class="w-full py-5 bg-gradient-to-r from-rose-500 to-rose-600 text-white rounded-[2rem] font-black shadow-xl shadow-rose-200 hover:scale-[1.02] active:scale-[0.98] transition-all uppercase tracking-widest text-sm" 
                                onclick="return confirm('Eksekusi transfer profit sekarang?')">
                            <i class="fas fa-paper-plane mr-2"></i> Cairdkan Saldo
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-16 pb-12">
        <div class="flex items-center gap-3 mb-8">
            <div class="w-11 h-11 bg-white rounded-2xl shadow-md flex items-center justify-center text-slate-400 border border-slate-100">
                <i class="fas fa-history text-xl"></i>
            </div>
            <h3 class="text-xl font-black text-slate-800 tracking-tight uppercase">Audit Log Transaksi</h3>
        </div>

        <div class="bg-white rounded-[2rem] shadow-xl overflow-hidden border border-slate-100">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-slate-50/80 border-b border-slate-100">
                        <tr>
                            <th class="px-8 py-5 text-[9px] font-black text-slate-400 uppercase tracking-[0.2em]">Timestamp</th>
                            <th class="px-8 py-5 text-[9px] font-black text-slate-400 uppercase tracking-[0.2em]">Type</th>
                            <th class="px-8 py-5 text-[9px] font-black text-slate-400 uppercase tracking-[0.2em] text-center">Amount</th>
                            <th class="px-8 py-5 text-[9px] font-black text-slate-400 uppercase tracking-[0.2em]">Verification Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @php $logs = DB::table('dana_transactions')->orderBy('id', 'desc')->limit(12)->get(); @endphp
                        @foreach($logs as $log)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-8 py-4 text-xs font-semibold text-slate-400 italic">{{ $log->created_at }}</td>
                            <td class="px-8 py-4">
                                <span class="px-3 py-1 rounded-xl text-[9px] font-black tracking-widest uppercase {{ $log->type == 'TOPUP' ? 'bg-rose-100 text-rose-600' : ($log->type == 'BINDING' ? 'bg-blue-100 text-blue-600' : 'bg-slate-100 text-slate-500') }}">
                                    <i class="fas {{ $log->type == 'TOPUP' ? 'fa-wallet' : 'fa-link' }} mr-1.5 text-[8px]"></i>
                                    {{ $log->type }}
                                </span>
                            </td>
                            <td class="px-8 py-4 text-center font-extrabold text-slate-800 text-sm">
                                Rp {{ number_format($log->amount, 0, ',', '.') }}
                            </td>
                            <td class="px-8 py-4">
                                <div class="flex items-center gap-2 text-emerald-500 font-bold text-xs uppercase tracking-tighter">
                                    <i class="fas fa-circle-check"></i>
                                    {{ $log->status }}
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection