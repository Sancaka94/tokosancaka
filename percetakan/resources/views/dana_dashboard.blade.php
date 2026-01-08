@extends('layouts.app')

@section('content')
<script src="https://cdn.tailwindcss.com"></script>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<div class="min-h-screen bg-gray-50 font-sans text-slate-900" x-data="{ openModal: null }">
    
    <div class="bg-gradient-to-r from-blue-700 to-indigo-800 pb-32 pt-10 shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex justify-between items-center">
            <div class="flex items-center space-x-4">
                <div class="bg-white/20 p-3 rounded-2xl backdrop-blur-md border border-white/30">
                    <i class="fas fa-vault text-3xl text-white"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-black text-white tracking-tight">DANA COMMAND CENTER</h1>
                    <p class="text-blue-100 text-sm font-medium">Monitoring & Instant Disbursement System</p>
                </div>
            </div>
            <div class="hidden md:flex space-x-3">
                <div class="bg-black/20 px-4 py-2 rounded-xl border border-white/10 text-white text-xs font-bold">
                    <i class="far fa-calendar-alt mr-2"></i> {{ now()->format('d M Y') }}
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 -mt-20">
        
        {{-- Flash Messages --}}
        @if(session('success'))
        <div class="mb-6 p-4 bg-green-500 text-white rounded-2xl shadow-lg flex items-center shadow-green-200">
            <i class="fas fa-check-circle mr-3"></i> <b>{{ session('success') }}</b>
        </div>
        @endif

        <div class="bg-white rounded-[2rem] shadow-2xl overflow-hidden border border-gray-100">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50/50 border-b border-gray-100">
                        <th class="p-6 text-[10px] font-black text-gray-400 uppercase tracking-widest">Affiliate Profile</th>
                        <th class="p-6 text-[10px] font-black text-gray-400 uppercase tracking-widest text-center">Connection</th>
                        <th class="p-6 text-[10px] font-black text-gray-400 uppercase tracking-widest">Balance Info</th>
                        <th class="p-6 text-[10px] font-black text-gray-400 uppercase tracking-widest">Merchant Safe</th>
                        <th class="p-6 text-[10px] font-black text-gray-400 uppercase tracking-widest text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($affiliates as $aff)
                    <tr class="hover:bg-blue-50/30 transition-all">
                        <td class="p-6">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 bg-indigo-100 rounded-xl flex items-center justify-center text-indigo-600 font-bold">
                                    {{ substr($aff->name, 0, 1) }}
                                </div>
                                <div>
                                    <div class="font-bold text-gray-800">{{ $aff->name }}</div>
                                    <div class="text-xs text-gray-400"><i class="fab fa-whatsapp text-green-500"></i> {{ $aff->whatsapp }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="p-6 text-center">
                            @if($aff->dana_access_token)
                                <span class="bg-green-100 text-green-700 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-tighter border border-green-200">Linked</span>
                            @else
                                <span class="bg-gray-100 text-gray-400 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-tighter">Offline</span>
                            @endif
                        </td>
                        <td class="p-6">
                            <div class="space-y-1">
                                <div class="text-[9px] font-bold text-gray-400 uppercase">Profit Affiliate</div>
                                <div class="text-sm font-black text-slate-700">Rp {{ number_format($aff->balance, 0, ',', '.') }}</div>
                            </div>
                        </td>
                        <td class="p-6 text-rose-600">
                            <div class="text-[9px] font-bold text-rose-300 uppercase">Deposit Merchant</div>
                            <div class="text-sm font-black">Rp {{ number_format($aff->dana_merchant_balance, 0, ',', '.') }}</div>
                        </td>
                        <td class="p-6">
                            <div class="flex justify-center space-x-2">
                                <form action="{{ route('dana.do_bind') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="affiliate_id" value="{{ $aff->id }}">
                                    <button class="w-9 h-9 bg-blue-50 text-blue-600 rounded-lg hover:bg-blue-600 hover:text-white transition-all">
                                        <i class="fas fa-link"></i>
                                    </button>
                                </form>
                                <button @click="openModal = {{ $aff->id }}" class="w-9 h-9 bg-rose-50 text-rose-600 rounded-lg hover:bg-rose-600 hover:text-white transition-all">
                                    <i class="fas fa-paper-plane"></i>
                                </button>
                            </div>
                        </td>
                    </tr>

                    <template x-if="openModal === {{ $aff->id }}">
                        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
                            <div class="bg-white w-full max-w-md rounded-[2.5rem] shadow-2xl p-8 relative animate-scale-up" @click.away="openModal = null">
                                <button @click="openModal = null" class="absolute top-6 right-6 text-gray-300 hover:text-gray-600">
                                    <i class="fas fa-times text-xl"></i>
                                </button>
                                
                                <div class="text-center mb-6">
                                    <div class="w-16 h-16 bg-rose-50 text-rose-500 rounded-2xl flex items-center justify-center mx-auto mb-4">
                                        <i class="fas fa-coins text-3xl"></i>
                                    </div>
                                    <h2 class="text-xl font-black text-gray-800">Cairkan Profit</h2>
                                    <p class="text-gray-400 text-sm">Transfer ke <b>{{ $aff->name }}</b></p>
                                </div>

                                <div class="space-y-4">
                                    <form action="{{ route('dana.account_inquiry') }}" method="POST" class="bg-gray-50 p-4 rounded-2xl border border-gray-100">
                                        @csrf
                                        <input type="hidden" name="affiliate_id" value="{{ $aff->id }}">
                                        <label class="text-[10px] font-black text-gray-400 uppercase block mb-2 tracking-widest">Nomor DANA Tujuan</label>
                                        <input type="text" name="phone" value="{{ $aff->whatsapp }}" class="w-full bg-white border-0 rounded-xl font-bold p-3 focus:ring-2 focus:ring-blue-500 mb-3 shadow-inner">
                                        <button class="w-full py-3 bg-gray-800 text-white rounded-xl font-bold text-xs uppercase tracking-widest hover:bg-black transition-all">
                                            Verifikasi Akun
                                        </button>
                                    </form>

                                    <form action="{{ route('dana.topup') }}" method="POST" class="bg-rose-50 p-6 rounded-3xl border border-rose-100">
                                        @csrf
                                        <input type="hidden" name="affiliate_id" value="{{ $aff->id }}">
                                        <div class="text-center mb-4">
                                            <p class="text-[10px] font-bold text-rose-300 uppercase">Profit Tersedia</p>
                                            <p class="text-2xl font-black text-rose-600 tracking-tight">Rp {{ number_format($aff->balance, 0, ',', '.') }}</p>
                                        </div>
                                        <input type="number" name="amount" value="1000" min="1000" class="w-full bg-white border-0 rounded-2xl p-4 text-center text-xl font-black text-gray-800 shadow-inner mb-4 focus:ring-2 focus:ring-rose-500">
                                        <button class="w-full py-4 bg-rose-500 text-white rounded-2xl font-black shadow-lg shadow-rose-200 hover:bg-rose-600 transition-all uppercase tracking-widest text-sm">
                                            Kirim Dana Sekarang
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

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-12 pb-20">
        <h3 class="text-lg font-black text-gray-800 mb-6 flex items-center">
            <i class="fas fa-list-ul mr-3 text-blue-500"></i> AUDIT LOG TRANSAKSI
        </h3>
        <div class="bg-white rounded-3xl shadow-xl overflow-hidden border border-gray-100">
            <table class="w-full text-left">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="p-4 text-[9px] font-black text-gray-400 uppercase">Waktu</th>
                        <th class="p-4 text-[9px] font-black text-gray-400 uppercase">Tipe</th>
                        <th class="p-4 text-[9px] font-black text-gray-400 uppercase">Nominal</th>
                        <th class="p-4 text-[9px] font-black text-gray-400 uppercase">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @php $logs = DB::table('dana_transactions')->orderBy('id', 'desc')->limit(5)->get(); @endphp
                    @foreach($logs as $log)
                    <tr class="text-xs">
                        <td class="p-4 text-gray-400 font-medium">{{ $log->created_at }}</td>
                        <td class="p-4 text-blue-600 font-bold tracking-widest">{{ $log->type }}</td>
                        <td class="p-4 font-black">Rp {{ number_format($log->amount, 0, ',', '.') }}</td>
                        <td class="p-4 font-bold text-green-500"><i class="fas fa-check-circle mr-1"></i> {{ $log->status }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
    @keyframes scale-up {
        from { opacity: 0; transform: scale(0.95); }
        to { opacity: 1; transform: scale(1); }
    }
    .animate-scale-up { animation: scale-up 0.2s ease-out forwards; }
</style>
@endsection