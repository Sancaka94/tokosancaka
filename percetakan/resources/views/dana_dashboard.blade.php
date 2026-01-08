@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-slate-50 py-10" x-data="{ openModal: null }">
    <div class="max-w-7xl mx-auto px-4">
        
        <div class="bg-gradient-to-r from-blue-600 to-indigo-700 rounded-[2rem] p-8 mb-10 shadow-xl flex justify-between items-center text-white">
            <div>
                <h1 class="text-2xl font-black tracking-tight"><i class="fas fa-shield-check mr-2"></i> DANA ADMIN CENTER</h1>
                <p class="opacity-80 text-sm">Monitoring Profit & Instant Disbursement</p>
            </div>
            <div class="bg-white/20 px-4 py-2 rounded-xl backdrop-blur-md border border-white/20 text-xs font-bold">
                <i class="fas fa-calendar-day mr-2"></i> {{ now()->format('d M Y') }}
            </div>
        </div>

        {{-- Alerts --}}
        @if(session('success'))
            <div class="mb-6 p-4 bg-emerald-500 text-white rounded-2xl shadow-lg flex items-center">
                <i class="fas fa-check-circle mr-3"></i> <b>{{ session('success') }}</b>
            </div>
        @endif

        <div class="bg-white rounded-[2.5rem] shadow-xl border border-slate-100 overflow-hidden">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-100">
                        <th class="p-6 text-[10px] font-black text-slate-400 uppercase tracking-widest">Affiliate</th>
                        <th class="p-6 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Status</th>
                        <th class="p-6 text-[10px] font-black text-slate-400 uppercase tracking-widest">Saldo</th>
                        <th class="p-6 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @foreach($affiliates as $aff)
                    <tr class="hover:bg-blue-50/50 transition-colors">
                        <td class="p-6">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center text-blue-600 font-bold uppercase">
                                    {{ substr($aff->name, 0, 1) }}
                                </div>
                                <div>
                                    <div class="font-bold text-slate-800">{{ $aff->name }}</div>
                                    <div class="text-xs text-slate-400"><i class="fab fa-whatsapp text-emerald-500"></i> {{ $aff->whatsapp }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="p-6 text-center text-[10px] font-black">
                            @if($aff->dana_access_token)
                                <span class="text-emerald-500 bg-emerald-50 px-3 py-1 rounded-full border border-emerald-100">LINKED</span>
                            @else
                                <span class="text-slate-300 bg-slate-50 px-3 py-1 rounded-full border border-slate-100">OFFLINE</span>
                            @endif
                        </td>
                        <td class="p-6">
                            <div class="text-[9px] font-bold text-slate-400 uppercase mb-1 tracking-tighter">Profit Sancaka</div>
                            <div class="text-sm font-black text-slate-700 italic">Rp {{ number_format($aff->balance, 0, ',', '.') }}</div>
                        </td>
                        <td class="p-6 text-center">
                            <div class="flex justify-center space-x-2">
                                {{-- Tombol Binding --}}
                                <form action="{{ route('dana.do_bind') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="affiliate_id" value="{{ $aff->id }}">
                                    <button class="w-9 h-9 bg-blue-50 text-blue-600 rounded-xl hover:bg-blue-600 hover:text-white transition-all">
                                        <i class="fas fa-link"></i>
                                    </button>
                                </form>
                                {{-- Tombol Modal Cairkan --}}
                                <button @click="openModal = {{ $aff->id }}" class="w-9 h-9 bg-rose-50 text-rose-600 rounded-xl hover:bg-rose-600 hover:text-white transition-all">
                                    <i class="fas fa-paper-plane"></i>
                                </button>
                            </div>
                        </td>
                    </tr>

                    <div 
                        x-show="openModal === {{ $aff->id }}" 
                        x-transition.opacity 
                        class="fixed inset-0 z-[999] flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4"
                        style="display: none;"
                    >
                        <div class="bg-white w-full max-w-md rounded-[2.5rem] shadow-2xl p-8 relative" @click.away="openModal = null">
                            <button @click="openModal = null" class="absolute top-6 right-6 text-slate-300 hover:text-slate-600">
                                <i class="fas fa-times-circle text-2xl"></i>
                            </button>
                            
                            <div class="text-center mb-6">
                                <div class="w-16 h-16 bg-rose-50 text-rose-500 rounded-2xl flex items-center justify-center mx-auto mb-4">
                                    <i class="fas fa-money-bill-transfer text-3xl"></i>
                                </div>
                                <h2 class="text-xl font-black text-slate-800 italic">CAIRKAN PROFIT</h2>
                                <p class="text-slate-400 text-xs">Penerima: <b>{{ $aff->name }}</b></p>
                            </div>

                            <div class="space-y-4">
                                {{-- Inquiry --}}
                                <form action="{{ route('dana.account_inquiry') }}" method="POST" class="bg-slate-50 p-4 rounded-2xl border border-slate-100 text-left">
                                    @csrf
                                    <input type="hidden" name="affiliate_id" value="{{ $aff->id }}">
                                    <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest block mb-1">DANA Number</label>
                                    <input type="text" name="phone" value="{{ $aff->whatsapp }}" class="w-full bg-white border-0 rounded-xl font-bold py-2 px-3 shadow-inner focus:ring-2 focus:ring-blue-500 mb-3">
                                    @if($aff->dana_user_name)
                                        <div class="mb-3 p-2 bg-emerald-50 text-emerald-600 rounded-xl text-[10px] font-bold border border-emerald-100 italic">
                                            <i class="fas fa-user-check"></i> {{ $aff->dana_user_name }}
                                        </div>
                                    @endif
                                    <button class="w-full py-2.5 bg-slate-800 text-white rounded-xl font-bold text-[10px] uppercase tracking-widest hover:bg-black transition-all">
                                        Cek Pemilik Akun
                                    </button>
                                </form>

                                {{-- Topup --}}
                                <form action="{{ route('dana.topup') }}" method="POST" class="bg-rose-50 p-6 rounded-[2rem] border border-rose-100 text-center">
                                    @csrf
                                    <input type="hidden" name="affiliate_id" value="{{ $aff->id }}">
                                    <input type="hidden" name="phone" value="{{ $aff->whatsapp }}">
                                    <div class="mb-4">
                                        <p class="text-[9px] font-bold text-rose-300 uppercase tracking-widest">Profit Tersedia</p>
                                        <p class="text-2xl font-black text-rose-600 italic tracking-tighter">Rp {{ number_format($aff->balance, 0, ',', '.') }}</p>
                                    </div>
                                    <div class="relative mb-4">
                                        <span class="absolute left-4 top-1/2 -translate-y-1/2 font-black text-slate-300">Rp</span>
                                        <input type="number" name="amount" value="1000" min="1000" max="{{ $aff->balance }}" class="w-full bg-white border-0 rounded-xl py-3 pl-10 pr-4 text-right text-lg font-black text-slate-800 shadow-inner focus:ring-2 focus:ring-rose-500">
                                    </div>
                                    <button class="w-full py-4 bg-rose-500 text-white rounded-2xl font-black shadow-lg shadow-rose-200 hover:bg-rose-600 transition-all uppercase text-xs" onclick="return confirm('Kirim Dana Sekarang?')">
                                        Kirim Sekarang
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </tbody>
            </table>
        </div>
        
        <div class="mt-12 bg-white rounded-[2rem] shadow-xl border border-slate-100 overflow-hidden pb-4">
             <div class="p-6 border-b border-slate-50 font-black text-slate-800 text-sm uppercase tracking-widest"><i class="fas fa-history mr-2 text-blue-500"></i> Audit Log Transaksi</div>
             <div class="overflow-x-auto">
                 <table class="w-full text-left text-xs">
                     <thead class="bg-slate-50">
                         <tr class="text-[9px] font-black text-slate-400 uppercase tracking-widest">
                             <th class="p-4">Waktu</th>
                             <th class="p-4">Tipe</th>
                             <th class="p-4">Nominal</th>
                             <th class="p-4">Status</th>
                         </tr>
                     </thead>
                     <tbody class="divide-y divide-slate-50">
                         @php $logs = DB::table('dana_transactions')->orderBy('id', 'desc')->limit(5)->get(); @endphp
                         @foreach($logs as $log)
                         <tr>
                             <td class="p-4 text-slate-400">{{ $log->created_at }}</td>
                             <td class="p-4 font-bold text-blue-600">{{ $log->type }}</td>
                             <td class="p-4 font-black">Rp {{ number_format($log->amount, 0, ',', '.') }}</td>
                             <td class="p-4 text-emerald-500 font-bold"><i class="fas fa-circle-check"></i> {{ $log->status }}</td>
                         </tr>
                         @endforeach
                     </tbody>
                 </table>
             </div>
        </div>
    </div>
</div>
@endsection