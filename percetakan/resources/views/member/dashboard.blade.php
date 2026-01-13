@extends('layouts.member')

@section('title', 'Dashboard')

@section('content')

{{-- 1. TARUH KODE NOTIFIKASI DI SINI --}}
    <div class="mb-6">
        {{-- Notifikasi Sukses --}}
        @if(session('success'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-2xl flex items-center gap-3 shadow-sm animate-fade-in mb-3">
            <i class="fas fa-check-circle text-lg"></i>
            <p class="text-xs font-bold">{{ session('success') }}</p>
        </div>
        @endif

        {{-- Notifikasi Gagal --}}
        @if(session('error'))
        <div class="bg-rose-50 border border-rose-200 text-rose-700 px-4 py-3 rounded-2xl flex items-center gap-3 shadow-sm animate-fade-in mb-3">
            <i class="fas fa-exclamation-triangle text-lg"></i>
            <p class="text-xs font-bold">{{ session('error') }}</p>
        </div>
        @endif

        {{-- Laporan Sistem DANA Detail --}}
        @if(session('dana_report'))
        <div class="p-4 rounded-2xl border {{ session('dana_report')->is_success ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : 'bg-rose-50 border-rose-200 text-rose-800' }} shadow-sm">
            <div class="flex items-start gap-3">
                <i class="fas {{ session('dana_report')->is_success ? 'fa-check-circle' : 'fa-info-circle' }} mt-0.5"></i>
                <div>
                    <p class="font-black text-[9px] uppercase tracking-wider mb-1 opacity-70">DANA System Report</p>
                    <p class="font-bold text-sm">{{ session('dana_report')->message_title }}</p>
                    <p class="text-[10px] opacity-80 mt-1 leading-relaxed">{{ session('dana_report')->description }}</p>
                </div>
            </div>
        </div>
        @endif
    </div>

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

                {{-- Tombol Baru: Cairkan ke Bank --}}
                <button onclick="openBankModal('{{ $member->id }}', '{{ $member->balance }}')"
                        class="flex-1 text-[10px] bg-slate-600 text-white py-2 rounded-lg font-bold hover:bg-slate-700 transition flex items-center justify-center gap-1">
                    <i class="fas fa-university"></i> Ke Rekening Bank
                </button>
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
                <img src="https://tokosancaka.com/storage/logo/dana.png" class="h-3 brightness-0 invert" alt="DANA">
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

    {{-- BAGIAN FILTER & EXPORT --}}
    <div class="mt-8 mb-4">
        {{-- Form Filter --}}
        <form action="{{ route('member.dashboard') }}" method="GET" class="bg-white p-4 rounded-3xl border border-slate-100 shadow-sm mb-4">
            <div class="grid grid-cols-2 gap-3 mb-3">
                <div>
                    <label class="text-[9px] font-black text-slate-400 uppercase ml-1">Dari Tanggal</label>
                    <input type="date" name="start_date" value="{{ request('start_date') }}" class="w-full text-xs p-2.5 bg-slate-50 border-none rounded-xl focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="text-[9px] font-black text-slate-400 uppercase ml-1">Sampai Tanggal</label>
                    <input type="date" name="end_date" value="{{ request('end_date') }}" class="w-full text-xs p-2.5 bg-slate-50 border-none rounded-xl focus:ring-2 focus:ring-blue-500">
                </div>
            </div>
            <div class="flex gap-2">
                <select name="type" class="flex-1 text-xs p-2.5 bg-slate-50 border-none rounded-xl focus:ring-2 focus:ring-blue-500">
                    <option value="">Semua Tipe</option>
                    <option value="TOPUP" {{ request('type') == 'TOPUP' ? 'selected' : '' }}>Pencairan (Keluar)</option>
                    <option value="COMMISSION" {{ request('type') == 'COMMISSION' ? 'selected' : '' }}>Komisi (Masuk)</option>
                </select>
                <button type="submit" class="bg-slate-800 text-white px-4 py-2.5 rounded-xl hover:bg-slate-900 transition flex items-center gap-2 text-xs font-bold">
                    <i class="fas fa-filter"></i> Filter
                </button>
                @if(request()->has('type') || request()->has('start_date'))
                    <a href="{{ route('member.dashboard') }}" class="bg-slate-100 text-slate-500 px-4 py-2.5 rounded-xl hover:bg-slate-200 transition text-xs font-bold">Reset</a>
                @endif
            </div>
        </form>

        {{-- Tabel Riwayat --}}
        {{-- TABEL DATA RIWAYAT TRANSAKSI SESUAI DATABASE --}}
        <div class="mt-8 mb-10">
            <div class="flex justify-between items-center mb-4 px-1">
                <h3 class="font-bold text-slate-700 text-sm italic uppercase tracking-tighter">Database Transaction Log</h3>
                <div class="flex gap-2">
                    <a href="#" class="p-2 bg-rose-100 text-rose-600 rounded-lg hover:bg-rose-200 transition" title="Export PDF">
                        <i class="fas fa-file-pdf"></i>
                    </a>
                    <a href="#" class="p-2 bg-emerald-100 text-emerald-600 rounded-lg hover:bg-emerald-200 transition" title="Export Excel">
                        <i class="fas fa-file-excel"></i>
                    </a>
                </div>
            </div>

            <div class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse min-w-[800px]">
                        <thead>
                            <tr class="bg-slate-50/50 border-b border-slate-100">
                                <th class="px-4 py-4 text-[9px] font-black text-slate-400 uppercase tracking-widest">Waktu Transaksi</th>
                                <th class="px-4 py-4 text-[9px] font-black text-slate-400 uppercase tracking-widest">Reference No</th>
                                <th class="px-4 py-4 text-[9px] font-black text-slate-400 uppercase tracking-widest">Tipe</th>
                                <th class="px-4 py-4 text-[9px] font-black text-slate-400 uppercase tracking-widest text-right">Nominal</th>
                                <th class="px-4 py-4 text-[9px] font-black text-slate-400 uppercase tracking-widest text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            @forelse($transactions as $trx)
                            <tr class="hover:bg-slate-50 transition-colors">
                                {{-- Kolom created_at --}}
                                <td class="px-4 py-4">
                                    <p class="text-[10px] font-bold text-slate-700">
                                        {{ \Carbon\Carbon::parse($trx->created_at)->translatedFormat('d/m/Y') }}
                                    </p>
                                    <p class="text-[9px] text-slate-400 italic">
                                        {{ \Carbon\Carbon::parse($trx->created_at)->format('H:i:s') }} WIB
                                    </p>
                                </td>

                                {{-- Kolom reference_no --}}
                                <td class="px-4 py-4">
                                    <code class="text-[10px] bg-slate-100 px-2 py-1 rounded text-slate-600 font-mono">
                                        {{ $trx->reference_no }}
                                    </code>
                                </td>

                                {{-- Kolom type --}}
                                <td class="px-4 py-4">
                                    @php
                                        $isDebit = in_array($trx->type, ['TOPUP', 'DISBURSEMENT']);
                                    @endphp
                                    <span class="text-[9px] font-black px-2 py-1 rounded-full border {{ $isDebit ? 'border-rose-200 text-rose-600 bg-rose-50' : 'border-emerald-200 text-emerald-600 bg-emerald-50' }}">
                                        {{ $trx->type }}
                                    </span>
                                </td>

                                {{-- Kolom amount --}}
                                <td class="px-4 py-4 text-right">
                                    <p class="text-xs font-black {{ $isDebit ? 'text-rose-600' : 'text-emerald-600' }}">
                                        {{ $isDebit ? '-' : '+' }} Rp {{ number_format($trx->amount, 0, ',', '.') }}
                                    </p>
                                </td>

                                {{-- Kolom status --}}
                                <td class="px-4 py-4 text-center">
                                    <div class="flex flex-col items-center gap-1">
                                        <span class="inline-flex items-center gap-1 px-2 py-1 rounded-md text-[9px] font-black uppercase {{ $trx->status == 'SUCCESS' ? 'bg-emerald-500 text-white' : 'bg-rose-500 text-white' }}">
                                            <i class="fas {{ $trx->status == 'SUCCESS' ? 'fa-check-circle' : 'fa-times-circle' }}"></i>
                                            {{ $trx->status }}
                                        </span>

                                        {{-- Tombol Cek Status Manual sesuai dokumentasi Retry --}}
                                        @if($trx->status != 'SUCCESS')
                                        <form action="{{ route('member.dana.checkStatus') }}" method="POST">
                                            @csrf
                                            <input type="hidden" name="affiliate_id" value="{{ $member->id }}">
                                            <input type="hidden" name="reference_no" value="{{ $trx->reference_no }}">
                                            <button type="submit" class="text-[8px] font-bold text-blue-600 hover:underline mt-1 uppercase">
                                                <i class="fas fa-sync-alt animate-spin-slow"></i> Cek Status
                                            </button>
                                        </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="px-4 py-10 text-center text-slate-400 uppercase text-[10px] font-black">
                                    <i class="fas fa-folder-open mb-2 text-2xl block opacity-20"></i>
                                    Tidak ada log transaksi ditemukan
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                    {{-- LINK PAGINATION --}}
                <div class="px-4 py-4 bg-slate-50/50 border-t border-slate-100">
                    <div class="flex flex-col gap-4">
                        {{-- Info Halaman --}}
                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest text-center">
                            Menampilkan {{ $transactions->firstItem() }} - {{ $transactions->lastItem() }}
                            dari {{ $transactions->total() }} Transaksi
                        </div>

                        {{-- Tombol Navigasi Custom (Mobile Friendly) --}}
                        <div class="flex justify-center items-center gap-2">
                            @if ($transactions->onFirstPage())
                                <span class="p-2 w-10 h-10 flex items-center justify-center rounded-xl bg-slate-100 text-slate-300 cursor-not-allowed">
                                    <i class="fas fa-chevron-left text-xs"></i>
                                </span>
                            @else
                                <a href="{{ $transactions->previousPageUrl() }}" class="p-2 w-10 h-10 flex items-center justify-center rounded-xl bg-white border border-slate-200 text-slate-600 hover:bg-blue-500 hover:text-white transition shadow-sm">
                                    <i class="fas fa-chevron-left text-xs"></i>
                                </a>
                            @endif

                            <div class="px-4 py-2 bg-white border border-slate-200 rounded-xl text-xs font-black text-slate-700 shadow-sm">
                                Halaman {{ $transactions->currentPage() }}
                            </div>

                            @if ($transactions->hasMorePages())
                                <a href="{{ $transactions->nextPageUrl() }}" class="p-2 w-10 h-10 flex items-center justify-center rounded-xl bg-white border border-slate-200 text-slate-600 hover:bg-blue-500 hover:text-white transition shadow-sm">
                                    <i class="fas fa-chevron-right text-xs"></i>
                                </a>
                            @else
                                <span class="p-2 w-10 h-10 flex items-center justify-center rounded-xl bg-slate-100 text-slate-300 cursor-not-allowed">
                                    <i class="fas fa-chevron-right text-xs"></i>
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

            </div>
            <p class="text-[9px] text-slate-400 mt-4 px-1 italic text-right">
                * Data ditarik otomatis dari tabel <b>dana_transactions</b>.
            </p>
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

    {{-- MODAL PENCAIRAN KE BANK --}}
    <div id="bankModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl w-full max-w-md shadow-2xl overflow-hidden">
            <div class="bg-slate-800 p-6 text-white">
                <h3 class="text-lg font-black uppercase italic tracking-tighter">Cairkan Ke Bank</h3>
                <p class="text-slate-400 text-[10px] uppercase font-bold tracking-widest">Bank Account Inquiry</p>
            </div>

            <form action="{{ route('member.dana.bankInquiry') }}" method="POST" class="p-6">
                @csrf
                <input type="hidden" name="affiliate_id" id="bank_aff_id">

                {{-- Pilih Bank --}}
                <div class="mb-4">
                    <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">Pilih Bank Tujuan</label>
                    <select name="bank_code" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl font-bold text-slate-700 focus:ring-2 focus:ring-blue-500 transition">
                        <option value="" disabled selected>-- Pilih Bank Tujuan --</option>

                        <optgroup label="Bank Terpopuler">
                            <option value="014">Bank BCA</option>
                            <option value="008">Bank Mandiri</option>
                            <option value="002">Bank BRI</option>
                            <option value="009">Bank BNI</option>
                            <option value="427">Bank Syariah Indonesia (BSI)</option>
                        </optgroup>

                        <optgroup label="Bank Nasional & Swasta">
                            <option value="022">Bank CIMB Niaga</option>
                            <option value="147">Bank Muamalat</option>
                            <option value="213">Bank BTPN / JENIUS</option>
                            <option value="200">Bank Tabungan Negara (BTN)</option>
                            <option value="013">Permata Bank</option>
                            <option value="011">Bank Danamon</option>
                            <option value="426">Bank Mega</option>
                            <option value="153">Bank Sinarmas</option>
                            <option value="028">Bank OCBC NISP</option>
                        </optgroup>

                        <optgroup label="Bank Pembangunan Daerah (BPD)">
                            <option value="110">Bank BJB</option>
                            <option value="111">Bank DKI</option>
                            {{-- PERHATIKAN: KODE JATIM HARUS 114 --}}
                            <option value="114">Bank Jatim</option>
                            <option value="113">Bank Jateng</option>
                            <option value="112">BPD DIY</option>
                            <option value="118">Bank Nagari</option>
                            <option value="129">BPD Bali</option>
                            <option value="132">Bank Papua</option>
                        </optgroup>
                    </select>
                </div>

                {{-- Nomor Rekening --}}
                <div class="mb-4">
                    <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">Nomor Rekening</label>
                    <input type="text" name="account_no" required placeholder="Contoh: 01234567890"
                           class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl font-bold text-slate-700 focus:border-blue-500 transition">
                </div>

                {{-- Nominal --}}
                <div class="mb-6">
                    <div class="flex justify-between items-center mb-1">
                        <label class="text-[10px] font-black text-slate-400 uppercase">Nominal Transfer</label>
                        <span class="text-[10px] font-black text-blue-600 uppercase" id="bank_max_balance"></span>
                    </div>
                    <input type="number" name="amount" required min="10000" step="1" placeholder="Min. 10.000"
                           class="w-full px-4 py-4 bg-slate-50 border border-slate-200 rounded-xl font-black text-2xl text-slate-800 focus:border-blue-500 transition">
                    <p class="text-[8px] text-slate-400 mt-2 italic">*Pengecekan ini membutuhkan waktu sekitar 8 detik.</p>
                </div>

                <div class="flex gap-3">
                    <button type="button" onclick="closeBankModal()" class="flex-1 py-3 text-slate-400 font-bold text-xs uppercase transition">Batal</button>
                    <button type="submit" class="flex-[2] py-3 bg-slate-800 text-white rounded-xl font-black text-xs uppercase shadow-lg hover:bg-slate-900 transition">
                        <i class="fas fa-search"></i> Cek Rekening
                    </button>
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

    <script>
        function openBankModal(id, balance) {
            document.getElementById('bank_aff_id').value = id;
            document.getElementById('bank_max_balance').innerText = 'Limit: Rp ' + new Intl.NumberFormat('id-ID').format(balance);
            document.getElementById('bankModal').classList.remove('hidden');
        }

        function closeBankModal() {
            document.getElementById('bankModal').classList.add('hidden');
        }
    </script>

    {{-- TAMBAHKAN CSS ANIMASI DI BAGIAN PALING BAWAH --}}
    <style>
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-in { animation: fadeIn 0.4s ease-out forwards; }
    </style>

@endsection
