@extends('layouts.app')

@section('content')
<script src="https://cdn.tailwindcss.com"></script>

<div class="min-h-screen bg-slate-50 pb-12">
    <div class="bg-gradient-to-r from-blue-600 to-sky-500 pb-32 pt-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-white/20 rounded-2xl backdrop-blur-md">
                        <i class="bi bi-shield-lock text-white text-3xl"></i>
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold text-white tracking-tight">DANA Admin Center</h1>
                        <p class="text-blue-100 font-medium">Monitoring Profit Affiliasi & Saldo Akun DANA secara Real-time</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 bg-white/10 backdrop-blur-md px-4 py-2 rounded-full border border-white/20">
                    <i class="bi bi-calendar-event text-white"></i>
                    <span class="text-white font-semibold">{{ now()->format('d M Y') }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 -mt-24">
        
        {{-- Flash Messages --}}
        @if(session('success'))
        <div class="mb-6 flex items-center p-4 text-emerald-800 bg-emerald-50 border-l-4 border-emerald-500 rounded-r-xl shadow-sm animate-bounce">
            <i class="bi bi-check-circle-fill mr-3 text-xl"></i>
            <span class="font-bold">{{ session('success') }}</span>
        </div>
        @endif

        @if(session('error'))
        <div class="mb-6 flex items-center p-4 text-rose-800 bg-rose-50 border-l-4 border-rose-500 rounded-r-xl shadow-sm">
            <i class="bi bi-exclamation-triangle-fill mr-3 text-xl"></i>
            <span class="font-bold">{{ session('error') }}</span>
        </div>
        @endif

        <div class="bg-white rounded-3xl shadow-xl shadow-slate-200/60 overflow-hidden border border-slate-100">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-slate-50 border-b border-slate-100">
                        <tr>
                            <th class="px-6 py-5 text-xs font-bold text-slate-500 uppercase tracking-wider">Informasi Affiliate</th>
                            <th class="px-6 py-5 text-xs font-bold text-slate-500 uppercase tracking-wider">Koneksi DANA</th>
                            <th class="px-6 py-5 text-xs font-bold text-slate-500 uppercase tracking-wider">Monitoring Saldo</th>
                            <th class="px-6 py-5 text-xs font-bold text-slate-500 uppercase tracking-wider">Deposit Merchant</th>
                            <th class="px-6 py-5 text-xs font-bold text-slate-500 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($affiliates as $aff)
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="px-6 py-6">
                                <div class="flex items-center gap-3">
                                    <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold">
                                        {{ substr($aff->name, 0, 1) }}
                                    </div>
                                    <div>
                                        <div class="text-sm font-bold text-slate-900">{{ $aff->name }}</div>
                                        <div class="text-xs text-slate-500 flex items-center gap-1 mt-1">
                                            <i class="bi bi-whatsapp text-emerald-500"></i> {{ $aff->whatsapp }}
                                        </div>
                                        <span class="inline-block mt-2 px-2 py-0.5 bg-slate-100 text-slate-500 rounded text-[10px] font-bold uppercase tracking-wider">ID: {{ $aff->id }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-6 text-center">
                                @if($aff->dana_access_token)
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-700 border border-emerald-200">
                                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 mr-2 animate-pulse"></span> Terhubung
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-rose-100 text-rose-700 border border-rose-200">
                                        <span class="h-1.5 w-1.5 rounded-full bg-rose-500 mr-2"></span> Terputus
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-6">
                                <div class="space-y-3">
                                    <div>
                                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Profit Sancaka (Internal)</p>
                                        <p class="text-sm font-black text-slate-800">Rp {{ number_format($aff->balance, 0, ',', '.') }}</p>
                                    </div>
                                    <div class="p-2 bg-blue-50 rounded-lg">
                                        <p class="text-[10px] font-bold text-blue-400 uppercase tracking-widest">Saldo Akun DANA (Riil)</p>
                                        <p class="text-sm font-black text-blue-700">Rp {{ number_format($aff->dana_user_balance, 0, ',', '.') }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-6">
                                <p class="text-[10px] font-bold text-rose-400 uppercase tracking-widest">Deposit Merchant</p>
                                <p class="text-base font-black text-rose-600">Rp {{ number_format($aff->dana_merchant_balance, 0, ',', '.') }}</p>
                                <form action="{{ route('dana.check_merchant_balance') }}" method="POST" class="mt-2">
                                    @csrf
                                    <input type="hidden" name="affiliate_id" value="{{ $aff->id }}">
                                    <button type="submit" class="text-[11px] font-bold text-rose-500 hover:text-rose-700 flex items-center gap-1 transition-colors">
                                        <i class="bi bi-arrow-clockwise"></i> Refresh Merchant
                                    </button>
                                </form>
                            </td>
                            <td class="px-6 py-6">
                                <div class="flex items-center justify-center gap-2">
                                    <form action="{{ route('dana.do_bind') }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="affiliate_id" value="{{ $aff->id }}">
                                        <button class="p-2.5 bg-blue-50 text-blue-600 rounded-xl hover:bg-blue-600 hover:text-white transition-all duration-300" title="Binding Akun">
                                            <i class="bi bi-link-45deg text-lg"></i>
                                        </button>
                                    </form>

                                    <form action="{{ route('dana.check_balance') }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="affiliate_id" value="{{ $aff->id }}">
                                        <button class="p-2.5 bg-emerald-50 text-emerald-600 rounded-xl hover:bg-emerald-600 hover:text-white transition-all duration-300 disabled:opacity-30" {{ !$aff->dana_access_token ? 'disabled' : '' }}>
                                            <i class="bi bi-arrow-repeat text-lg"></i>
                                        </button>
                                    </form>

                                    <button class="p-2.5 bg-rose-50 text-rose-600 rounded-xl hover:bg-rose-600 hover:text-white transition-all duration-300" data-bs-toggle="modal" data-bs-target="#modalTopup{{ $aff->id }}">
                                        <i class="bi bi-cash-stack text-lg"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-12">
            <div class="flex items-center gap-2 mb-6">
                <i class="bi bi-journal-text text-slate-400 text-xl"></i>
                <h2 class="text-xl font-bold text-slate-800">Audit Log Transaksi</h2>
            </div>
            <div class="bg-white rounded-3xl shadow-lg shadow-slate-200/40 border border-slate-100 overflow-hidden">
                <table class="w-full text-left">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Waktu</th>
                            <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Tipe</th>
                            <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase text-center">Nominal</th>
                            <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @php $logs = DB::table('dana_transactions')->orderBy('id', 'desc')->limit(10)->get(); @endphp
                        @foreach($logs as $log)
                        <tr>
                            <td class="px-6 py-4 text-xs text-slate-500">{{ $log->created_at }}</td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 rounded text-[10px] font-black {{ $log->type == 'TOPUP' ? 'bg-rose-100 text-rose-600' : 'bg-sky-100 text-sky-600' }}">
                                    {{ $log->type }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center font-black text-slate-700 text-sm">
                                Rp {{ number_format($log->amount, 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-4 font-bold text-emerald-500 text-xs">
                                <i class="bi bi-check-circle-fill"></i> {{ $log->status }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Modal Topup tetap pakai Bootstrap (karena JS dependency) tapi desain Tailwind --}}
@foreach($affiliates as $aff)
<div class="modal fade" id="modalTopup{{ $aff->id }}" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content !rounded-[2.5rem] border-0 shadow-2xl p-4">
            <div class="text-center mb-6">
                <div class="h-16 w-16 bg-rose-50 text-rose-500 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="bi bi-cash-coin text-3xl"></i>
                </div>
                <h3 class="text-xl font-black text-slate-800">Cairkan Profit</h3>
                <p class="text-sm text-slate-500">Pencairan untuk <b>{{ $aff->name }}</b></p>
            </div>

            <form action="{{ route('dana.account_inquiry') }}" method="POST" class="mb-4">
                @csrf
                <input type="hidden" name="affiliate_id" value="{{ $aff->id }}">
                <div class="bg-slate-50 p-4 rounded-3xl border border-slate-100 mb-2">
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Nomor DANA Tujuan</label>
                    <input type="text" name="phone" value="{{ $aff->whatsapp }}" class="w-full bg-white border-0 focus:ring-2 focus:ring-blue-500 rounded-xl font-bold text-slate-700">
                    @if($aff->dana_user_name)
                    <div class="mt-2 text-xs font-bold text-emerald-600 flex items-center gap-1">
                        <i class="bi bi-person-check-fill"></i> {{ $aff->dana_user_name }}
                    </div>
                    @endif
                </div>
                <button type="submit" class="w-full py-3 bg-blue-600 text-white rounded-2xl font-bold shadow-lg shadow-blue-200 hover:bg-blue-700 transition-all text-sm">
                    Verifikasi Akun
                </button>
            </form>

            <hr class="border-slate-100 my-4">

            <form action="{{ route('dana.topup') }}" method="POST">
                @csrf
                <input type="hidden" name="affiliate_id" value="{{ $aff->id }}">
                <div class="bg-slate-50 p-4 rounded-3xl border border-slate-100 mb-6 text-center">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Profit Tersedia</p>
                    <p class="text-2xl font-black text-slate-800">Rp {{ number_format($aff->balance, 0, ',', '.') }}</p>
                    
                    <div class="mt-4">
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Nominal Pencairan</label>
                        <input type="number" name="amount" min="1000" max="{{ $aff->balance }}" value="1000" class="w-full bg-white border-0 text-center text-xl font-black text-rose-600 focus:ring-2 focus:ring-rose-500 rounded-xl py-3">
                    </div>
                </div>
                <button type="submit" class="w-full py-4 bg-rose-500 text-white rounded-3xl font-bold shadow-xl shadow-rose-200 hover:bg-rose-600 transition-all">
                    Konfirmasi & Transfer
                </button>
            </form>
        </div>
    </div>
</div>
@endforeach

@endsection