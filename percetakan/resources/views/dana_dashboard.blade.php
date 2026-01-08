@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-slate-50 font-sans" x-data="{ openModal: null }">
    
    <div class="bg-gradient-to-r from-blue-700 to-indigo-800 pb-32 pt-10 shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex justify-between items-center text-white">
            <div class="flex items-center space-x-4">
                <div class="bg-white/20 p-3 rounded-2xl backdrop-blur-md border border-white/30">
                    <i class="fas fa-shield-halved text-3xl"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-black tracking-tight uppercase">DANA COMMAND CENTER</h1>
                    <p class="text-blue-100 text-sm font-medium">Monitoring Profit & Instant Disbursement v2.0</p>
                </div>
            </div>
            <div class="hidden md:block bg-black/20 px-4 py-2 rounded-xl border border-white/10 text-xs font-bold">
                <i class="far fa-calendar-alt mr-2"></i> {{ now()->format('d M Y') }}
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 -mt-20">
        
        {{-- Flash Messages --}}
        @if(session('success'))
            <div class="mb-6 p-4 bg-emerald-500 text-white rounded-2xl shadow-lg flex items-center shadow-emerald-200">
                <i class="fas fa-check-circle mr-3 text-xl"></i> <b>{{ session('success') }}</b>
            </div>
        @endif
        @if(session('error'))
            <div class="mb-6 p-4 bg-rose-500 text-white rounded-2xl shadow-lg flex items-center">
                <i class="fas fa-exclamation-triangle mr-3 text-xl"></i> <b>{{ session('error') }}</b>
            </div>
        @endif

        <div class="bg-white rounded-[2.5rem] shadow-2xl overflow-hidden border border-gray-100">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50/50 border-b border-gray-100">
                        <th class="p-6 text-[10px] font-black text-gray-400 uppercase tracking-widest">Affiliate</th>
                        <th class="p-6 text-[10px] font-black text-gray-400 uppercase tracking-widest text-center">Connection</th>
                        <th class="p-6 text-[10px] font-black text-gray-400 uppercase tracking-widest">Balance Metrics</th>
                        <th class="p-6 text-[10px] font-black text-gray-400 uppercase tracking-widest">Merchant Deposit</th>
                        <th class="p-6 text-[10px] font-black text-gray-400 uppercase tracking-widest text-center">Panel Kontrol</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($affiliates as $aff)
                    <tr class="hover:bg-blue-50/30 transition-all">
                        <td class="p-6">
                            <div class="flex items-center space-x-3">
                                <div class="w-12 h-12 bg-indigo-100 rounded-2xl flex items-center justify-center text-indigo-600 font-bold text-xl shadow-inner uppercase">
                                    {{ substr($aff->name, 0, 1) }}
                                </div>
                                <div>
                                    <div class="font-bold text-gray-800 text-base leading-none mb-1">{{ $aff->name }}</div>
                                    <div class="text-xs text-gray-400"><i class="fab fa-whatsapp text-emerald-500 mr-1"></i> {{ $aff->whatsapp }}</div>
                                    <span class="text-[9px] font-bold text-gray-300 uppercase">ID: {{ $aff->id }}</span>
                                </div>
                            </div>
                        </td>
                        <td class="p-6 text-center">
                            @if($aff->dana_access_token)
                                <span class="bg-emerald-100 text-emerald-700 px-4 py-1.5 rounded-full text-[10px] font-black uppercase border border-emerald-200 inline-flex items-center">
                                    <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full mr-2 animate-pulse"></span> Linked
                                </span>
                            @else
                                <span class="bg-slate-100 text-slate-400 px-4 py-1.5 rounded-full text-[10px] font-black uppercase border border-slate-200 italic">Offline</span>
                            @endif
                        </td>
                        <td class="p-6">
                            <div class="space-y-3">
                                <div>
                                    <div class="text-[9px] font-bold text-gray-400 uppercase mb-1">Profit Sancaka (Internal)</div>
                                    <div class="text-sm font-black text-slate-700 tracking-tight">Rp {{ number_format($aff->balance, 0, ',', '.') }}</div>
                                </div>
                                <div class="p-2 bg-blue-50 rounded-xl border border-blue-100">
                                    <div class="text-[9px] font-bold text-blue-400 uppercase mb-1">Saldo Akun DANA (Real)</div>
                                    <div class="text-sm font-black text-blue-600">Rp {{ number_format($aff->dana_user_balance ?? 0, 0, ',', '.') }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="p-6">
                            <div class="text-[9px] font-bold text-rose-400 uppercase mb-1">Merchant Deposit</div>
                            <div class="text-lg font-black text-rose-600 tracking-tighter">Rp {{ number_format($aff->dana_merchant_balance ?? 0, 0, ',', '.') }}</div>
                            <form action="{{ route('dana.check_merchant_balance') }}" method="POST" class="mt-2">
                                @csrf
                                <input type="hidden" name="affiliate_id" value="{{ $aff->id }}">
                                <button type="submit" class="text-[10px] font-bold text-rose-400 hover:text-rose-700 flex items-center gap-1 uppercase transition-colors">
                                    <i class="fas fa-sync-alt"></i> Refresh Merchant
                                </button>
                            </form>
                        </td>
                        <td class="p-6">
                            <div class="flex justify-center items-center gap-2">
                                {{-- BINDING --}}
                                <form action="{{ route('dana.do_bind') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="affiliate_id" value="{{ $aff->id }}">
                                    <button class="w-10 h-10 bg-blue-50 text-blue-600 rounded-xl hover:bg-blue-600 hover:text-white shadow-sm transition-all flex items-center justify-center" title="Binding Akun">
                                        <i class="fas fa-link"></i>
                                    </button>
                                </form>

                                {{-- SYNC SALDO --}}
                                <form action="{{ route('dana.check_balance') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="affiliate_id" value="{{ $aff->id }}">
                                    <button class="w-10 h-10 bg-emerald-50 text-emerald-600 rounded-xl hover:bg-emerald-600 hover:text-white shadow-sm transition-all flex items-center justify-center {{ !$aff->dana_access_token ? 'opacity-30' : '' }}" {{ !$aff->dana_access_token ? 'disabled' : '' }}>
                                        <i class="fas fa-rotate"></i>
                                    </button>
                                </form>

                                {{-- MODAL CAIRKAN --}}
                                <button @click="openModal = {{ $aff->id }}" class="w-10 h-10 bg-rose-50 text-rose-600 rounded-xl hover:bg-rose-600 hover:text-white shadow-sm transition-all flex items-center justify-center">
                                    <i class="fas fa-paper-plane"></i>
                                </button>
                            </div>
                        </td>
                    </tr>

                    <div 
                        x-show="openModal === {{ $aff->id }}" 
                        x-transition.opacity 
                        class="fixed inset-0 z-[999] flex items-center justify-center bg-slate-900/80 backdrop-blur-sm p-4"
                        style="display: none;"
                    >
                        <div class="bg-white w-full max-w-md rounded-[3rem] shadow-2xl p-8 relative" @click.away="openModal = null">
                            <button @click="openModal = null" class="absolute top-8 right-8 text-slate-300 hover:text-slate-600">
                                <i class="fas fa-times-circle text-2xl"></i>
                            </button>
                            
                            <div class="text-center mb-8">
                                <div class="w-16 h-16 bg-rose-50 text-rose-500 rounded-2xl flex items-center justify-center mx-auto mb-4">
                                    <i class="fas fa-hand-holding-dollar text-3xl"></i>
                                </div>
                                <h2 class="text-2xl font-black text-slate-800 tracking-tight">Cairkan Profit</h2>
                                <p class="text-slate-400 text-sm font-medium">Kirim ke: <b>{{ $aff->name }}</b></p>
                            </div>

                            <div class="space-y-6">
                                {{-- INQUIRY --}}
                                <form action="{{ route('dana.account_inquiry') }}" method="POST" class="bg-slate-50 p-5 rounded-[2rem] border border-slate-100">
                                    @csrf
                                    <input type="hidden" name="affiliate_id" value="{{ $aff->id }}">
                                    <label class="text-[10px] font-black text-slate-400 uppercase block mb-2 tracking-widest">DANA Receiver Number</label>
                                    <input type="text" name="phone" value="{{ $aff->whatsapp }}" class="w-full bg-white border-0 rounded-2xl font-bold py-3 px-4 focus:ring-2 focus:ring-blue-500 shadow-sm mb-4">
                                    
                                    @if($aff->dana_user_name)
                                        <div class="mb-4 p-3 bg-emerald-50 text-emerald-600 rounded-2xl text-xs font-bold flex items-center gap-2 border border-emerald-100">
                                            <i class="fas fa-user-check"></i> {{ $aff->dana_user_name }}
                                        </div>
                                    @endif

                                    <button class="w-full py-3.5 bg-slate-800 text-white rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-slate-900 shadow-lg transition-all">
                                        <i class="fas fa-magnifying-glass mr-2"></i> Verifikasi Akun
                                    </button>
                                </form>

                                {{-- TOPUP --}}
                                <form action="{{ route('dana.topup') }}" method="POST" class="bg-rose-50 p-6 rounded-[2.5rem] border border-rose-100 shadow-inner">
                                    @csrf
                                    <input type="hidden" name="affiliate_id" value="{{ $aff->id }}">
                                    <input type="hidden" name="phone" value="{{ $aff->whatsapp }}">
                                    
                                    <div class="text-center mb-4">
                                        <p class="text-[10px] font-bold text-rose-300 uppercase tracking-widest">Profit Tersedia</p>
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
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-16 pb-20">
        <h3 class="text-xl font-black text-slate-800 tracking-tight uppercase mb-8 flex items-center">
            <i class="fas fa-history mr-3 text-blue-500"></i> Audit Log Transaksi
        </h3>
        <div class="bg-white rounded-[2rem] shadow-xl overflow-hidden border border-slate-100">
            <table class="w-full text-left">
                <thead class="bg-slate-50 border-b border-slate-100">
                    <tr class="text-[9px] font-black text-slate-400 uppercase tracking-widest">
                        <th class="p-6">Timestamp</th>
                        <th class="p-6">Activity Type</th>
                        <th class="p-6 text-center">Amount</th>
                        <th class="p-6">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @php $logs = DB::table('dana_transactions')->orderBy('id', 'desc')->limit(10)->get(); @endphp
                    @foreach($logs as $log)
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="p-6 text-xs font-semibold text-slate-500">{{ $log->created_at }}</td>
                        <td class="p-6">
                            <span class="px-3 py-1 rounded-lg text-[9px] font-black tracking-widest uppercase {{ $log->type == 'TOPUP' ? 'bg-rose-100 text-rose-600' : 'bg-sky-100 text-sky-600' }}">
                                {{ $log->type }}
                            </span>
                        </td>
                        <td class="p-6 text-center font-black text-slate-700 text-sm">
                            Rp {{ number_format($log->amount, 0, ',', '.') }}
                        </td>
                        <td class="p-6 font-bold text-emerald-500 text-xs flex items-center gap-2 uppercase tracking-tighter">
                            <i class="fas fa-circle-check"></i> {{ $log->status }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection