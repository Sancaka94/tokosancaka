@extends('layouts.app')

@section('content')
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style>
    body { font-family: 'Plus Jakarta Sans', sans-serif; }
    .glass-effect { background: rgba(255, 255, 255, 0.1); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.2); }
    .modal-backdrop.show { opacity: 0.7; background-color: #0f172a; }
</style>

<div class="min-h-screen bg-slate-50">
    <div class="bg-gradient-to-br from-blue-700 via-blue-600 to-sky-500 pb-40 pt-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div class="flex items-center gap-5">
                    <div class="h-16 w-16 bg-white/20 rounded-2xl flex items-center justify-center shadow-inner border border-white/30">
                        <i class="bi bi-wallet2 text-white text-3xl"></i>
                    </div>
                    <div>
                        <h1 class="text-3xl font-extrabold text-white tracking-tight">DANA Command Center</h1>
                        <p class="text-blue-100 flex items-center gap-2 mt-1">
                            <span class="flex h-2 w-2 rounded-full bg-emerald-400"></span>
                            Live Monitoring System v2.0
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <div class="glass-effect px-5 py-2.5 rounded-2xl text-white">
                        <p class="text-[10px] uppercase font-bold opacity-70 leading-none mb-1">Server Time</p>
                        <div class="flex items-center gap-2">
                            <i class="bi bi-clock-history"></i>
                            <span class="font-bold tracking-wider">{{ now()->format('H:i') }} WIB</span>
                        </div>
                    </div>
                    <div class="glass-effect px-5 py-2.5 rounded-2xl text-white">
                        <p class="text-[10px] uppercase font-bold opacity-70 leading-none mb-1">Current Date</p>
                        <div class="flex items-center gap-2">
                            <i class="bi bi-calendar3"></i>
                            <span class="font-bold tracking-wider">{{ now()->format('d M Y') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 -mt-28">
        {{-- ALERTS --}}
        @if(session('success'))
        <div class="mb-6 p-4 bg-emerald-50 border border-emerald-100 rounded-3xl flex items-center gap-4 shadow-xl shadow-emerald-500/10 animate-fade-in">
            <div class="h-10 w-10 bg-emerald-500 rounded-2xl flex items-center justify-center text-white shadow-lg shadow-emerald-500/30">
                <i class="bi bi-check2-all text-xl"></i>
            </div>
            <div class="text-emerald-900 font-bold">{{ session('success') }}</div>
        </div>
        @endif

        @if(session('error'))
        <div class="mb-6 p-4 bg-rose-50 border border-rose-100 rounded-3xl flex items-center gap-4 shadow-xl shadow-rose-500/10">
            <div class="h-10 w-10 bg-rose-500 rounded-2xl flex items-center justify-center text-white shadow-lg shadow-rose-500/30">
                <i class="bi bi-exclamation-octagon text-xl"></i>
            </div>
            <div class="text-rose-900 font-bold">{{ session('error') }}</div>
        </div>
        @endif

        <div class="bg-white rounded-[2.5rem] shadow-2xl shadow-slate-200/50 border border-slate-100 overflow-hidden mb-10">
            <div class="p-8 border-b border-slate-50 flex items-center justify-between bg-white">
                <h2 class="text-xl font-extrabold text-slate-800 flex items-center gap-3">
                    <i class="bi bi-people-fill text-blue-600"></i>
                    Affiliate Network
                </h2>
                <span class="px-4 py-1.5 bg-slate-100 text-slate-500 rounded-full text-xs font-bold uppercase tracking-widest">
                    {{ count($affiliates) }} total users
                </span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="bg-slate-50/50">
                            <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Profile</th>
                            <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] text-center">Connection</th>
                            <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Balance Metrics</th>
                            <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Merchant Deposit</th>
                            <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] text-center">Operations</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @foreach($affiliates as $aff)
                        <tr class="group hover:bg-blue-50/30 transition-all duration-300">
                            <td class="px-8 py-6">
                                <div class="flex items-center gap-4">
                                    <div class="h-12 w-12 rounded-2xl bg-gradient-to-br from-slate-100 to-slate-200 flex items-center justify-center text-slate-600 font-extrabold text-lg shadow-inner group-hover:scale-110 transition-transform">
                                        {{ substr($aff->name, 0, 1) }}
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-slate-800 tracking-tight">{{ $aff->name }}</h4>
                                        <p class="text-xs text-slate-500 flex items-center gap-1.5 mt-0.5">
                                            <i class="bi bi-whatsapp text-emerald-500"></i>
                                            {{ $aff->whatsapp }}
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-8 py-6 text-center text-xs font-bold">
                                @if($aff->dana_access_token)
                                <div class="inline-flex items-center gap-2 px-4 py-1.5 bg-emerald-50 text-emerald-600 rounded-2xl border border-emerald-100">
                                    <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                    Linked
                                </div>
                                @else
                                <div class="inline-flex items-center gap-2 px-4 py-1.5 bg-slate-100 text-slate-400 rounded-2xl border border-slate-200">
                                    <i class="bi bi-slash-circle"></i>
                                    Disconnected
                                </div>
                                @endif
                            </td>
                            <td class="px-8 py-6">
                                <div class="flex flex-col gap-3">
                                    <div class="flex items-center justify-between">
                                        <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Internal Profit</span>
                                        <span class="text-sm font-extrabold text-slate-700">Rp {{ number_format($aff->balance, 0, ',', '.') }}</span>
                                    </div>
                                    <div class="px-3 py-2 bg-blue-50/80 rounded-xl border border-blue-100">
                                        <div class="flex items-center justify-between mb-0.5">
                                            <span class="text-[9px] font-black text-blue-400 uppercase tracking-widest text-center">Real DANA Saldo</span>
                                            <i class="bi bi-info-circle text-[10px] text-blue-300"></i>
                                        </div>
                                        <p class="text-sm font-black text-blue-600">Rp {{ number_format($aff->dana_user_balance ?? 0, 0, ',', '.') }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-8 py-6">
                                <p class="text-[9px] font-black text-rose-400 uppercase tracking-widest mb-1">Main Merchant Account</p>
                                <p class="text-lg font-black text-rose-500 tracking-tight">Rp {{ number_format($aff->dana_merchant_balance ?? 0, 0, ',', '.') }}</p>
                                <form action="{{ route('dana.check_merchant_balance') }}" method="POST" class="mt-2">
                                    @csrf
                                    <input type="hidden" name="affiliate_id" value="{{ $aff->id }}">
                                    <button class="text-[10px] font-black text-rose-400 hover:text-rose-600 uppercase flex items-center gap-1.5 transition-colors">
                                        <i class="bi bi-arrow-repeat text-xs"></i> Force Sync
                                    </button>
                                </form>
                            </td>
                            <td class="px-8 py-6">
                                <div class="flex items-center justify-center gap-3">
                                    <form action="{{ route('dana.do_bind') }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="affiliate_id" value="{{ $aff->id }}">
                                        <button class="h-10 w-10 flex items-center justify-center bg-blue-50 text-blue-600 rounded-2xl hover:bg-blue-600 hover:text-white hover:shadow-lg hover:shadow-blue-500/30 transition-all duration-300" title="OAuth Binding">
                                            <i class="bi bi-link-45deg text-xl"></i>
                                        </button>
                                    </form>

                                    <form action="{{ route('dana.check_balance') }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="affiliate_id" value="{{ $aff->id }}">
                                        <button class="h-10 w-10 flex items-center justify-center bg-emerald-50 text-emerald-600 rounded-2xl hover:bg-emerald-600 hover:text-white hover:shadow-lg hover:shadow-emerald-500/30 transition-all duration-300 disabled:opacity-30" {{ !$aff->dana_access_token ? 'disabled' : '' }} title="Balance Inquiry">
                                            <i class="bi bi-radar text-xl"></i>
                                        </button>
                                    </form>

                                    <button class="h-10 w-10 flex items-center justify-center bg-rose-50 text-rose-600 rounded-2xl hover:bg-rose-600 hover:text-white hover:shadow-lg hover:shadow-rose-500/30 transition-all duration-300" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#modalTopup{{ $aff->id }}" title="Disburse Profit">
                                        <i class="bi bi-box-arrow-up-right text-lg"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>

                        <div class="modal fade" id="modalTopup{{ $aff->id }}" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content !rounded-[3rem] border-0 shadow-2xl p-6">
                                    <div class="flex justify-center -mt-16 mb-6">
                                        <div class="h-20 w-20 bg-white rounded-[2rem] shadow-xl flex items-center justify-center text-rose-500 border border-slate-50">
                                            <i class="bi bi-lightning-charge-fill text-4xl"></i>
                                        </div>
                                    </div>
                                    
                                    <div class="text-center mb-8">
                                        <h3 class="text-2xl font-black text-slate-800 tracking-tight">Instant Disburse</h3>
                                        <p class="text-sm text-slate-500 font-medium mt-1 text-center">Transfer profit to <b>{{ $aff->name }}</b></p>
                                    </div>

                                    <div class="space-y-5">
                                        <form action="{{ route('dana.account_inquiry') }}" method="POST">
                                            @csrf
                                            <input type="hidden" name="affiliate_id" value="{{ $aff->id }}">
                                            <div class="p-5 bg-slate-50 rounded-3xl border border-slate-100 group focus-within:ring-2 focus-within:ring-blue-500 transition-all">
                                                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3">DANA Receiver Number</label>
                                                <div class="flex items-center gap-3">
                                                    <i class="bi bi-phone-vibrate text-blue-500"></i>
                                                    <input type="text" name="phone" value="{{ $aff->whatsapp }}" class="w-full bg-transparent border-0 focus:ring-0 font-bold text-slate-700 p-0">
                                                </div>
                                                @if($aff->dana_user_name)
                                                <div class="mt-4 pt-3 border-t border-slate-200 flex items-center gap-2 text-xs font-bold text-emerald-600">
                                                    <i class="bi bi-person-check-fill"></i>
                                                    {{ $aff->dana_user_name }}
                                                </div>
                                                @endif
                                            </div>
                                            <button type="submit" class="w-full mt-3 py-3.5 bg-slate-800 text-white rounded-2xl font-bold shadow-lg shadow-slate-200 hover:bg-slate-700 transition-all text-xs uppercase tracking-widest">
                                                <i class="bi bi-search mr-2"></i> Verify Account
                                            </button>
                                        </form>

                                        <div class="flex items-center gap-4 py-2">
                                            <div class="flex-1 h-px bg-slate-100"></div>
                                            <span class="text-[10px] font-black text-slate-300 uppercase">Transfer Details</span>
                                            <div class="flex-1 h-px bg-slate-100"></div>
                                        </div>

                                        <form action="{{ route('dana.topup') }}" method="POST">
                                            @csrf
                                            <input type="hidden" name="affiliate_id" value="{{ $aff->id }}">
                                            <div class="p-6 bg-rose-50 rounded-[2.5rem] border border-rose-100 text-center mb-6">
                                                <p class="text-[10px] font-bold text-rose-400 uppercase tracking-widest mb-2 text-center">Current Profit Balance</p>
                                                <p class="text-3xl font-black text-rose-600 tracking-tighter">Rp {{ number_format($aff->balance, 0, ',', '.') }}</p>
                                                
                                                <div class="mt-6">
                                                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-3 text-center">Disbursement Amount</label>
                                                    <div class="relative">
                                                        <span class="absolute left-4 top-1/2 -translate-y-1/2 font-black text-slate-400">Rp</span>
                                                        <input type="number" name="amount" min="1000" max="{{ $aff->balance }}" value="1000" 
                                                               class="w-full bg-white border-0 text-right pr-6 pl-12 text-2xl font-black text-slate-800 focus:ring-2 focus:ring-rose-500 rounded-2xl py-4 shadow-inner">
                                                    </div>
                                                </div>
                                            </div>
                                            <button type="submit" class="w-full py-4 bg-gradient-to-r from-rose-500 to-rose-600 text-white rounded-3xl font-black shadow-xl shadow-rose-200 hover:scale-[1.02] active:scale-[0.98] transition-all tracking-wide uppercase text-sm" 
                                                    onclick="return confirm('Execute instant disbursement to DANA?')">
                                                <i class="bi bi-send-check-fill mr-2"></i> Send Funds Now
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-20 pb-20">
            <div class="flex items-center justify-between mb-8">
                <div class="flex items-center gap-4">
                    <div class="h-10 w-10 bg-white rounded-xl shadow-md flex items-center justify-center text-slate-400 border border-slate-100">
                        <i class="bi bi-activity text-xl"></i>
                    </div>
                    <h2 class="text-2xl font-black text-slate-800 tracking-tight">Transaction Logs</h2>
                </div>
            </div>
            
            <div class="bg-white rounded-[2rem] shadow-xl shadow-slate-200/40 border border-slate-100 overflow-hidden">
                <table class="w-full text-left">
                    <thead class="bg-slate-50/80 border-b border-slate-100">
                        <tr>
                            <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase">Timestamp</th>
                            <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase">Action Type</th>
                            <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase text-center">Amount</th>
                            <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase">Verification Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @php $logs = DB::table('dana_transactions')->orderBy('id', 'desc')->limit(12)->get(); @endphp
                        @foreach($logs as $log)
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-8 py-4 text-xs font-semibold text-slate-500">{{ $log->created_at }}</td>
                            <td class="px-8 py-4">
                                <span class="px-3 py-1 rounded-xl text-[9px] font-black tracking-widest uppercase {{ $log->type == 'TOPUP' ? 'bg-rose-100 text-rose-600' : 'bg-sky-100 text-sky-600' }}">
                                    <i class="bi {{ $log->type == 'TOPUP' ? 'bi-cash-stack' : 'bi-search' }} mr-1.5"></i>
                                    {{ $log->type }}
                                </span>
                            </td>
                            <td class="px-8 py-4 text-center font-extrabold text-slate-800 text-sm">
                                Rp {{ number_format($log->amount, 0, ',', '.') }}
                            </td>
                            <td class="px-8 py-4">
                                <div class="flex items-center gap-2 text-emerald-500 font-bold text-xs uppercase tracking-tighter">
                                    <i class="bi bi-check-circle-fill"></i>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
@endsection