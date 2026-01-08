@extends('layouts.app')

@section('title', 'DANA Command Center')

@section('content')
<div x-data="{ openModal: null }" x-cloak>
    
    <div class="bg-gradient-to-r from-blue-600 to-indigo-700 rounded-3xl p-6 lg:p-8 mb-8 shadow-lg border border-white/20">
        <div class="flex flex-col md:flex-row justify-between items-center gap-6 text-white">
            <div class="flex items-center space-x-5">
                <div class="bg-white/20 p-4 rounded-2xl backdrop-blur-md border border-white/30 shadow-inner">
                    <i class="fas fa-vault text-3xl"></i>
                </div>
                <div>
                    <h1 class="text-2xl lg:text-3xl font-black tracking-tight uppercase">DANA COMMAND CENTER</h1>
                    <p class="text-blue-100 text-sm font-medium">Monitoring Profit & Instant Disbursement v2.0</p>
                </div>
            </div>
            <div class="bg-black/20 px-4 py-2 rounded-xl border border-white/10 text-xs font-bold shadow-sm">
                <i class="far fa-calendar-alt mr-2"></i> {{ now()->format('d M Y') }}
            </div>
        </div>
    </div>

    <div class="bg-white rounded-[2.5rem] shadow-xl shadow-slate-200/50 border border-slate-100 overflow-hidden mb-10">
        <div class="overflow-x-auto custom-scrollbar">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-100 italic">
                        <th class="p-6 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center italic">Profile</th>
                        <th class="p-6 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center italic">Status</th>
                        <th class="p-6 text-[10px] font-black text-slate-400 uppercase tracking-widest italic">Monitoring Saldo</th>
                        <th class="p-6 text-[10px] font-black text-slate-400 uppercase tracking-widest italic">Deposit Merchant</th>
                        <th class="p-6 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center italic">Panel Kontrol</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @foreach($affiliates as $aff)
                    <tr class="hover:bg-blue-50/40 transition-all duration-200 italic">
                        <td class="p-6">
                            <div class="flex items-center space-x-4">
                                <div class="w-12 h-12 bg-indigo-100 rounded-2xl flex items-center justify-center text-indigo-600 font-bold text-xl shadow-inner uppercase">
                                    {{ substr($aff->name, 0, 1) }}
                                </div>
                                <div>
                                    <div class="font-bold text-slate-800 text-base leading-tight">{{ $aff->name }}</div>
                                    <div class="text-xs text-slate-400 font-medium mt-1 italic"><i class="fab fa-whatsapp text-emerald-500 mr-1"></i> {{ $aff->whatsapp }}</div>
                                </div>
                            </div>
                        </td>

                        <td class="p-6 text-center italic">
                            @if($aff->dana_access_token)
                                <span class="bg-emerald-100 text-emerald-700 px-4 py-1.5 rounded-full text-[10px] font-black uppercase border border-emerald-200 inline-flex items-center shadow-sm">
                                    <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full mr-2 animate-pulse"></span> Linked
                                </span>
                            @else
                                <span class="bg-slate-100 text-slate-400 px-4 py-1.5 rounded-full text-[10px] font-black uppercase border border-slate-200">Offline</span>
                            @endif
                        </td>

                        <td class="p-6 italic">
                            <div class="space-y-3">
                                <div>
                                    <div class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1 italic text-center">Profit Sancaka (Internal)</div>
                                    <div class="text-sm font-black text-slate-700 tracking-tight italic text-center">Rp {{ number_format($aff->balance, 0, ',', '.') }}</div>
                                </div>
                                <div class="p-2.5 bg-blue-50 rounded-xl border border-blue-100 shadow-sm flex justify-between items-center italic">
                                    <div class="text-center italic">
                                        <div class="text-[9px] font-black text-blue-400 uppercase tracking-widest mb-1 italic">Real DANA Account</div>
                                        <div class="text-sm font-black text-blue-600 italic">Rp {{ number_format($aff->dana_user_balance ?? 0, 0, ',', '.') }}</div>
                                    </div>
                                    <form action="{{ route('dana.check_balance') }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="affiliate_id" value="{{ $aff->id }}">
                                        <button type="submit" class="w-8 h-8 bg-white text-blue-600 rounded-lg shadow-sm border border-blue-100 hover:bg-blue-600 hover:text-white transition-all flex items-center justify-center {{ !$aff->dana_access_token ? 'hidden' : '' }}" title="Sinkron Saldo Real">
                                            <i class="fas fa-rotate text-xs"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </td>

                        <td class="p-6 italic">
                            <div class="text-[9px] font-black text-rose-400 uppercase tracking-widest mb-1 italic text-center">Deposit Merchant</div>
                            <div class="text-lg font-black text-rose-600 tracking-tighter italic text-center">Rp {{ number_format($aff->dana_merchant_balance ?? 0, 0, ',', '.') }}</div>
                            <form action="{{ route('dana.check_merchant_balance') }}" method="POST" class="mt-2 text-center italic">
                                @csrf
                                <input type="hidden" name="affiliate_id" value="{{ $aff->id }}">
                                <button type="submit" class="text-[10px] font-bold text-rose-400 hover:text-rose-700 flex items-center justify-center gap-1.5 uppercase transition-all bg-rose-50 px-3 py-1.5 rounded-xl border border-rose-100 hover:bg-rose-100 italic">
                                    <i class="fas fa-sync-alt"></i> Refresh Merchant
                                </button>
                            </form>
                        </td>

                        <td class="p-6 italic">
                            <div class="flex justify-center items-center gap-3 italic">
                                {{-- BINDING BUTTON --}}
                                <form action="{{ route('dana.do_bind') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="affiliate_id" value="{{ $aff->id }}">
                                    <button class="w-12 h-12 bg-blue-50 text-blue-600 rounded-2xl hover:bg-blue-600 hover:text-white shadow-sm transition-all flex items-center justify-center group" title="Binding Akun">
                                        <i class="fas fa-link text-lg"></i>
                                    </button>
                                </form>

                                {{-- CAIRKAN BUTTON (TRIGGER MODAL) --}}
                                <button @click="openModal = {{ $aff->id }}" 
                                        class="w-12 h-12 bg-rose-50 text-rose-600 rounded-2xl hover:bg-rose-600 hover:text-white shadow-sm transition-all flex items-center justify-center group" 
                                        title="Cairkan Profit">
                                    <i class="fas fa-paper-plane text-lg"></i>
                                </button>
                            </div>
                        </td>
                    </tr>

                    <template x-if="openModal === {{ $aff->id }}">
                        <div class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-900/80 backdrop-blur-sm shadow-2xl">
                            <div class="bg-white w-full max-w-4xl rounded-[3rem] shadow-2xl overflow-hidden relative border border-slate-100 italic" @click.away="openModal = null">
                                
                                <button @click="openModal = null" class="absolute top-6 right-8 text-slate-300 hover:text-slate-600 transition-colors z-10">
                                    <i class="fas fa-times-circle text-2xl"></i>
                                </button>

                                <div class="flex flex-col md:flex-row italic">
                                    <div class="w-full md:w-5/12 bg-slate-50 p-8 border-r border-slate-100 italic">
                                        <div class="flex items-center gap-4 mb-8 italic">
                                            <div class="h-12 w-12 bg-blue-600 rounded-2xl flex items-center justify-center text-white shadow-lg italic">
                                                <i class="fas fa-user-check text-xl italic"></i>
                                            </div>
                                            <div class="italic">
                                                <h3 class="text-xl font-black text-slate-800 tracking-tight italic">Verifikasi</h3>
                                                <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest italic">Tahap 1: Rekening</p>
                                            </div>
                                        </div>

                                        <form action="{{ route('dana.account_inquiry') }}" method="POST" class="space-y-6 italic">
                                            @csrf
                                            <input type="hidden" name="affiliate_id" value="{{ $aff->id }}">
                                            <div class="space-y-2 italic text-center">
                                                <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest italic text-center">Nomor DANA Penerima</label>
                                                <input type="text" name="phone" value="{{ $aff->whatsapp }}" class="w-full bg-white border-0 rounded-2xl font-bold py-3 px-4 focus:ring-2 focus:ring-blue-500 shadow-sm text-slate-700 italic text-center">
                                            </div>

                                            @if($aff->dana_user_name)
                                            <div class="p-4 bg-emerald-50 border border-emerald-100 rounded-2xl italic">
                                                <p class="text-[9px] font-black text-emerald-400 uppercase tracking-widest italic mb-1">Nama Terverifikasi:</p>
                                                <p class="text-sm font-black text-emerald-700 italic flex items-center gap-2 italic">
                                                    <i class="fas fa-check-circle"></i> {{ $aff->dana_user_name }}
                                                </p>
                                            </div>
                                            @endif

                                            <button class="w-full py-4 bg-slate-800 text-white rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-slate-900 shadow-lg italic">
                                                <i class="fas fa-search mr-2 italic"></i> Verifikasi Akun DANA
                                            </button>
                                        </form>
                                    </div>

                                    <div class="w-full md:w-7/12 p-8 bg-white italic">
                                        <div class="flex items-center justify-between mb-8 italic">
                                            <div class="italic">
                                                <h3 class="text-xl font-black text-slate-800 tracking-tight italic">Pencairan</h3>
                                                <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest italic">Tahap 2: Kirim Saldo</p>
                                            </div>
                                            <div class="text-right italic">
                                                <p class="text-[9px] font-black text-slate-300 uppercase italic">User Target</p>
                                                <p class="font-bold text-slate-700 italic">{{ $aff->name }}</p>
                                            </div>
                                        </div>

                                        <form action="{{ route('dana.topup') }}" method="POST" class="space-y-6 italic">
                                            @csrf
                                            <input type="hidden" name="affiliate_id" value="{{ $aff->id }}">
                                            <input type="hidden" name="phone" value="{{ $aff->whatsapp }}">

                                            <div class="grid grid-cols-2 gap-4 italic">
                                                <div class="p-4 bg-blue-50 rounded-2xl border border-blue-100 italic text-center">
                                                    <p class="text-[9px] font-black text-blue-400 uppercase tracking-widest italic mb-1">Total Profit</p>
                                                    <p class="text-lg font-black text-blue-600 italic tracking-tighter italic">Rp {{ number_format($aff->balance, 0, ',', '.') }}</p>
                                                </div>
                                                <div class="p-4 bg-slate-50 rounded-2xl border border-slate-100 italic text-center">
                                                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest italic mb-1">Status</p>
                                                    <p class="text-xs font-bold text-slate-600 italic">Ready to Send</p>
                                                </div>
                                            </div>

                                            <div class="space-y-2 italic text-center">
                                                <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest italic">Masukkan Nominal Cair</label>
                                                <div class="relative italic">
                                                    <span class="absolute left-6 top-1/2 -translate-y-1/2 font-black text-slate-300 text-xl italic">Rp</span>
                                                    <input type="number" name="amount" value="1000" min="1000" max="{{ $aff->balance }}" class="w-full bg-white border border-slate-100 rounded-[2rem] py-5 pl-16 pr-8 text-right text-3xl font-black text-slate-800 focus:ring-4 focus:ring-rose-500/10 shadow-inner italic" required>
                                                </div>
                                            </div>

                                            <button type="submit" class="w-full py-5 bg-gradient-to-r from-rose-500 to-rose-600 text-white rounded-[2rem] font-black shadow-xl shadow-rose-200 hover:scale-[1.02] transition-all uppercase tracking-widest text-sm italic" onclick="return confirm('Kirim dana profit sekarang?')">
                                                <i class="fas fa-paper-plane mr-2 italic"></i> Eksekusi Transfer
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

    <div class="mt-16 pb-12 italic">
        <h3 class="text-xl font-black text-slate-800 tracking-tight uppercase mb-8 flex items-center italic">
            <i class="fas fa-history mr-3 text-blue-500 italic"></i> Audit Log Transaksi
        </h3>
        <div class="bg-white rounded-[2.5rem] shadow-xl overflow-hidden border border-slate-100 italic">
            <table class="w-full text-left italic">
                <thead class="bg-slate-50 border-b border-slate-100 italic">
                    <tr class="text-[9px] font-black text-slate-400 uppercase tracking-widest italic">
                        <th class="p-6 italic">Waktu</th>
                        <th class="p-6 italic">Jenis</th>
                        <th class="p-6 text-center italic">Nominal</th>
                        <th class="p-6 italic">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 italic">
                    @php $logs = DB::table('dana_transactions')->orderBy('id', 'desc')->limit(12)->get(); @endphp
                    @foreach($logs as $log)
                    <tr class="hover:bg-slate-50 transition-colors italic">
                        <td class="p-6 text-xs font-semibold text-slate-400 italic italic">{{ $log->created_at }}</td>
                        <td class="p-6 italic">
                            <span class="px-3 py-1 rounded-xl text-[9px] font-black tracking-widest uppercase italic {{ $log->type == 'TOPUP' ? 'bg-rose-100 text-rose-600' : 'bg-blue-100 text-blue-600' }}">
                                {{ $log->type }}
                            </span>
                        </td>
                        <td class="p-6 text-center font-black text-slate-700 text-sm italic italic">
                            Rp {{ number_format($log->amount, 0, ',', '.') }}
                        </td>
                        <td class="p-6 font-bold text-emerald-500 text-xs flex items-center gap-2 italic italic uppercase">
                            <i class="fas fa-circle-check italic"></i> {{ $log->status }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection