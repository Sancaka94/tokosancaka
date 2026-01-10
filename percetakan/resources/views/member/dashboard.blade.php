@extends('layouts.member')

@section('title', 'Dashboard')

@section('content')

    {{-- CARD SALDO & KODE REFERAL --}}
    <div class="relative bg-slate-800 rounded-3xl p-6 text-white shadow-xl overflow-hidden mb-6 group transition-all hover:shadow-2xl hover:scale-[1.01]">
        <div class="absolute top-0 right-0 -mr-8 -mt-8 w-32 h-32 rounded-full bg-white/10 blur-2xl group-hover:bg-white/15 transition"></div>
        <div class="absolute bottom-0 left-0 -ml-8 -mb-8 w-24 h-24 rounded-full bg-blue-500/20 blur-xl group-hover:bg-blue-500/30 transition"></div>

        <div class="relative z-10">
            <div class="flex justify-between items-start mb-4">
                <div>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Saldo Komisi (Siap Cair)</p>
                    <h2 class="text-3xl font-black mt-1">Rp {{ number_format($member->balance, 0, ',', '.') }}</h2>
                </div>
                <div class="flex flex-col items-end gap-2">
                    <div class="bg-white/20 p-2.5 rounded-xl backdrop-blur-sm border border-white/10">
                        <i class="fas fa-wallet text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white/10 rounded-xl p-3 flex items-center justify-between border border-white/5 backdrop-blur-md mb-3">
                <div>
                    <p class="text-[9px] text-slate-300 uppercase mb-0.5">Kode Referal Anda</p>
                    <p class="font-mono font-bold text-lg text-yellow-400 tracking-wide leading-none">{{ $member->coupon_code }}</p>
                </div>
                <button onclick="navigator.clipboard.writeText('{{ $member->coupon_code }}'); alert('Kode Kupon berhasil disalin!')" 
                        class="text-[10px] bg-white text-slate-900 px-3 py-2 rounded-lg font-bold hover:bg-yellow-400 transition flex items-center gap-1 active:scale-95">
                    <i class="far fa-copy"></i> Salin
                </button>
            </div>

            {{-- DANA QUICK ACTIONS --}}
            <div class="flex gap-2">
                <form action="{{ route('dana.startBinding') }}" method="POST" class="flex-1">
                    @csrf
                    <input type="hidden" name="affiliate_id" value="{{ $member->id }}">
                    <button type="submit" class="w-full text-[10px] bg-blue-500 text-white py-2 rounded-lg font-bold hover:bg-blue-600 transition flex items-center justify-center gap-1">
                        <i class="fas fa-link"></i> {{ $member->dana_access_token ? 'Update DANA' : 'Hubungkan DANA' }}
                    </button>
                </form>
                @if($member->balance > 0)
                <button onclick="openTopupModal('{{ $member->id }}', '{{ $member->balance }}', '{{ $member->whatsapp }}')" 
                        class="flex-1 text-[10px] bg-emerald-500 text-white py-2 rounded-lg font-bold hover:bg-emerald-600 transition flex items-center justify-center gap-1">
                    <i class="fas fa-hand-holding-usd"></i> Cairkan Saldo
                </button>
                @endif
            </div>
        </div>
    </div>

    {{-- ALERT REPORT DANA --}}
    @if(session('dana_report'))
    <div class="mb-6 p-4 rounded-2xl border {{ session('dana_report')->is_success ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : 'bg-rose-50 border-rose-200 text-rose-800' }}">
        <div class="flex items-center gap-3">
            <i class="fas {{ session('dana_report')->is_success ? 'fa-check-circle' : 'fa-exclamation-triangle' }}"></i>
            <div>
                <p class="font-bold text-xs">{{ session('dana_report')->message_title }}</p>
                <p class="text-[10px] opacity-80">{{ session('dana_report')->description }}</p>
            </div>
        </div>
    </div>
    @endif

    {{-- INFO DANA TERKONEKSI --}}
    @if($member->dana_access_token)
    <div class="bg-blue-50 border border-blue-100 rounded-2xl p-4 mb-6 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <div class="bg-blue-600 p-2 rounded-lg">
                <img src="https://upload.wikimedia.org/wikipedia/commons/7/72/Logo_danain.png" class="h-3 brightness-0 invert" alt="DANA">
            </div>
            <div>
                <p class="text-[10px] font-bold text-blue-800 uppercase leading-none">{{ $member->dana_user_name ?? 'Akun Terhubung' }}</p>
                <p class="text-[9px] text-blue-600 mt-1">Saldo DANA: Rp {{ number_format($member->dana_user_balance ?? 0, 0, ',', '.') }}</p>
            </div>
        </div>
        <form action="{{ route('dana.checkBalance') }}" method="POST">
            @csrf
            <input type="hidden" name="affiliate_id" value="{{ $member->id }}">
            <button type="submit" class="p-2 text-blue-600 hover:bg-blue-100 rounded-lg transition" title="Refresh Saldo">
                <i class="fas fa-sync-alt text-xs"></i>
            </button>
        </form>
    </div>
    @endif

    {{-- MENU UTAMA --}}
    <div class="mb-6">
        <h3 class="font-bold text-slate-700 text-sm mb-3 ml-1">Menu Utama</h3>
        <div class="grid grid-cols-4 gap-3">
            <a href="{{ route('orders.create', ['coupon' => $member->coupon_code]) }}" class="bg-white p-3 rounded-2xl shadow-sm border border-slate-100 flex flex-col items-center gap-2 hover:border-blue-300 hover:shadow-md transition group">
                <div class="w-10 h-10 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center text-lg group-hover:bg-blue-600 group-hover:text-white transition">
                    <i class="fas fa-shopping-cart text-base"></i>
                </div>
                <span class="text-[10px] font-bold text-slate-600 group-hover:text-blue-600">Order</span>
            </a>
            
            <a href="https://wa.me/6285745808809" target="_blank" class="bg-white p-3 rounded-2xl shadow-sm border border-slate-100 flex flex-col items-center gap-2 hover:border-green-300 hover:shadow-md transition group">
                <div class="w-10 h-10 rounded-full bg-green-50 text-green-600 flex items-center justify-center text-lg group-hover:bg-green-600 group-hover:text-white transition">
                    <i class="fab fa-whatsapp"></i>
                </div>
                <span class="text-[10px] font-bold text-slate-600 group-hover:text-green-600">Admin</span>
            </a>
            
            <form action="{{ route('dana.accountInquiry') }}" method="POST" class="w-full">
                @csrf
                <input type="hidden" name="affiliate_id" value="{{ $member->id }}">
                <button type="submit" class="w-full bg-white p-3 rounded-2xl shadow-sm border border-slate-100 flex flex-col items-center gap-2 hover:border-purple-300 hover:shadow-md transition group">
                    <div class="w-10 h-10 rounded-full bg-purple-50 text-purple-600 flex items-center justify-center text-lg group-hover:bg-purple-600 group-hover:text-white transition">
                        <i class="fas fa-user-check text-base"></i>
                    </div>
                    <span class="text-[10px] font-bold text-slate-600 group-hover:text-purple-600">Verify</span>
                </button>
            </form>
            
            <a href="#" class="bg-white p-3 rounded-2xl shadow-sm border border-slate-100 flex flex-col items-center gap-2 hover:border-orange-300 hover:shadow-md transition group">
                <div class="w-10 h-10 rounded-full bg-orange-50 text-orange-600 flex items-center justify-center text-lg group-hover:bg-orange-600 group-hover:text-white transition">
                    <i class="fas fa-cog"></i>
                </div>
                <span class="text-[10px] font-bold text-slate-600 group-hover:text-orange-600">Akun</span>
            </a>
        </div>
    </div>

    {{-- PESANAN TERAKHIR --}}
    <div>
        <div class="flex justify-between items-center mb-3 px-1">
            <h3 class="font-bold text-slate-700 text-sm">Pesanan Terakhir (WhatsApp)</h3>
            <a href="#" class="text-[10px] font-bold text-blue-600 hover:underline">Lihat Semua</a>
        </div>

        <div class="space-y-3">
            @forelse($orders as $order)
            <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-sm flex justify-between items-center hover:shadow-md transition cursor-pointer">
                <div class="flex items-center gap-3">
                    <div class="h-10 w-10 shrink-0 rounded-xl bg-slate-50 flex items-center justify-center text-slate-400 border border-slate-100">
                        <i class="fas fa-receipt"></i>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-slate-800">{{ $order->order_number }}</p>
                        <p class="text-[10px] text-slate-400 flex items-center gap-1">
                            <i class="far fa-clock text-[9px]"></i>
                            {{ $order->created_at->translatedFormat('d M, H:i') }}
                        </p>
                    </div>
                </div>
                <div class="text-right">
                    <p class="text-xs font-black text-slate-800">Rp {{ number_format($order->final_price, 0, ',', '.') }}</p>
                    @php
                        $statusClass = match($order->status) {
                            'completed' => 'text-emerald-600 bg-emerald-50 border-emerald-100',
                            'processing' => 'text-blue-600 bg-blue-50 border-blue-100',
                            'pending' => 'text-amber-600 bg-amber-50 border-amber-100',
                            'cancelled' => 'text-red-600 bg-red-50 border-red-100',
                            default => 'text-slate-500 bg-slate-50'
                        };
                    @endphp
                    <span class="inline-block mt-1 px-2 py-0.5 rounded text-[9px] font-bold uppercase border {{ $statusClass }}">
                        {{ $order->status }}
                    </span>
                </div>
            </div>
            @empty
            <div class="text-center py-10 px-4 text-slate-400 bg-white rounded-2xl border border-dashed border-slate-200">
                <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-3">
                    <i class="fas fa-history text-2xl text-slate-300"></i>
                </div>
                <p class="text-xs font-bold text-slate-500">Belum ada pesanan</p>
            </div>
            @endforelse
        </div>
    </div>

    {{-- MODAL PENCAIRAN SALDO --}}
    <div id="topupModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl w-full max-w-md shadow-2xl overflow-hidden">
            <div class="bg-emerald-600 p-6 text-white">
                <h3 class="text-lg font-black uppercase italic tracking-tighter">Cairkan Profit</h3>
                <p class="text-emerald-100 text-[10px] uppercase font-bold tracking-widest">Sancaka Disbursement</p>
            </div>
            <form action="{{ route('dana.customerTopup') }}" method="POST" class="p-6">
                @csrf
                <input type="hidden" name="affiliate_id" id="modal_aff_id">
                <div class="mb-4">
                    <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">No. DANA Tujuan</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400 font-bold text-xs">+62</span>
                        <input type="text" name="phone" id="modal_aff_phone" required class="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl font-bold text-slate-700 focus:ring-0 focus:border-emerald-500 transition">
                    </div>
                </div>
                <div class="mb-6">
                    <div class="flex justify-between items-center mb-1">
                        <label class="text-[10px] font-black text-slate-400 uppercase">Nominal</label>
                        <span class="text-[10px] font-black text-emerald-600 uppercase" id="modal_max_balance"></span>
                    </div>
                    <input type="number" name="amount" required min="1000" placeholder="Min. 1000" class="w-full px-4 py-4 bg-slate-50 border border-slate-200 rounded-xl font-black text-2xl text-slate-800 focus:ring-0 focus:border-emerald-500 transition">
                    <p class="text-[9px] text-slate-400 mt-2 italic">*Saldo profit terpotong otomatis saat sukses.</p>
                </div>
                <div class="flex gap-3">
                    <button type="button" onclick="closeTopupModal()" class="flex-1 py-3 text-slate-400 font-bold text-xs uppercase transition">Batal</button>
                    <button type="submit" class="flex-[2] py-3 bg-emerald-600 text-white rounded-xl font-black text-xs uppercase shadow-lg shadow-emerald-200 hover:bg-emerald-700 transition">Konfirmasi</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openTopupModal(id, balance, phone) {
            document.getElementById('modal_aff_id').value = id;
            // Format phone 62...
            let cleanPhone = phone.startsWith('62') ? phone.substring(2) : (phone.startsWith('0') ? phone.substring(1) : phone);
            document.getElementById('modal_aff_phone').value = cleanPhone;
            document.getElementById('modal_max_balance').innerText = 'Limit: Rp ' + new Intl.NumberFormat('id-ID').format(balance);
            document.getElementById('topupModal').classList.remove('hidden');
        }
        function closeTopupModal() {
            document.getElementById('topupModal').classList.add('hidden');
        }
    </script>

@endsection