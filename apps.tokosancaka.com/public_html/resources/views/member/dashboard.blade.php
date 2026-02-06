@extends('layouts.member')

@section('title', 'Dashboard')

@section('content')

{{-- 1. NOTIFIKASI SYSTEM --}}
    <div class="mb-6">
        @if(session('success'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-2xl flex items-center gap-3 shadow-sm animate-fade-in mb-3">
            <i class="fas fa-check-circle text-lg"></i>
            <div>
                {{-- Tambahkan class 'whitespace-pre-line' agar \n terbaca sebagai Enter --}}
                <p class="text-xs font-bold whitespace-pre-line">{{ session('success') }}</p>
                @if(session('dana_report') && session('dana_report')->is_success)
                    <p class="text-[10px] opacity-80 mt-1">{{ session('dana_report')->description }}</p>
                @endif
            </div>
        </div>
        @endif

        @if(session('error'))
        <div class="bg-rose-50 border border-rose-200 text-rose-700 px-4 py-3 rounded-2xl flex items-center gap-3 shadow-sm animate-fade-in mb-3">
            <i class="fas fa-exclamation-triangle text-lg"></i>
            <p class="text-xs font-bold">{{ session('error') }}</p>
        </div>
        @endif
    </div>

    {{-- 2. CARD SALDO UTAMA --}}
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

            {{-- ACTION BUTTONS --}}
            <div class="flex gap-2">
                {{-- Tombol DANA Binding --}}
                <form action="{{ route('dana.startBinding') }}" method="POST" class="flex-1">
                    @csrf
                    <input type="hidden" name="affiliate_id" value="{{ $member->id }}">
                    <button type="submit" class="w-full text-[10px] bg-blue-500 text-white py-2 rounded-lg font-bold hover:bg-blue-600 transition flex items-center justify-center gap-1">
                        <i class="fas fa-link"></i> {{ $member->dana_access_token ? 'Update DANA' : 'Hubungkan DANA' }}
                    </button>
                </form>

                {{-- Tombol Cairkan ke DANA --}}
                @if($member->balance > 0)
                <button onclick="openTopupModal('{{ $member->id }}', '{{ $member->balance }}', '{{ $member->whatsapp }}')"
                        class="flex-1 text-[10px] bg-emerald-500 text-white py-2 rounded-lg font-bold hover:bg-emerald-600 transition flex items-center justify-center gap-1">
                    <i class="fas fa-hand-holding-usd"></i> Cairkan Saldo
                </button>
                @endif

                {{-- Tombol Cairkan ke Bank --}}
                <button onclick="openBankModal('{{ $member->id }}', '{{ $member->balance }}')"
                        class="flex-1 text-[10px] bg-slate-600 text-white py-2 rounded-lg font-bold hover:bg-slate-700 transition flex items-center justify-center gap-1">
                    <i class="fas fa-university"></i> Ke Rekening Bank
                </button>
            </div>
        </div>
    </div>

    {{-- 3. INFO DANA TERHUBUNG --}}
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

    {{-- 4. MENU GRID --}}
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

    {{-- 5. RIWAYAT TRANSAKSI DANA (LOG DATABASE) --}}
    <div class="mt-8 mb-10">
        <div class="flex justify-between items-center mb-4 px-1">
            <h3 class="font-bold text-slate-700 text-sm italic uppercase tracking-tighter">Riwayat Transaksi</h3>
            <div class="flex gap-2">
                <a href="#" class="p-2 bg-emerald-100 text-emerald-600 rounded-lg hover:bg-emerald-200 transition"><i class="fas fa-file-excel"></i></a>
            </div>
        </div>

        <div class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse min-w-[800px]">
                    <thead>
                        <tr class="bg-slate-50/50 border-b border-slate-100">
                            <th class="px-4 py-4 text-[9px] font-black text-slate-400 uppercase tracking-widest">Waktu</th>
                            <th class="px-4 py-4 text-[9px] font-black text-slate-400 uppercase tracking-widest">Reff ID</th>
                            <th class="px-4 py-4 text-[9px] font-black text-slate-400 uppercase tracking-widest">Tipe</th>
                            <th class="px-4 py-4 text-[9px] font-black text-slate-400 uppercase tracking-widest text-right">Nominal</th>
                            <th class="px-4 py-4 text-[9px] font-black text-slate-400 uppercase tracking-widest text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @forelse($transactions as $trx)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-4 py-4">
                                <p class="text-[10px] font-bold text-slate-700">{{ \Carbon\Carbon::parse($trx->created_at)->translatedFormat('d/m/Y') }}</p>
                                <p class="text-[9px] text-slate-400 italic">{{ \Carbon\Carbon::parse($trx->created_at)->format('H:i:s') }} WIB</p>
                            </td>
                            <td class="px-4 py-4">
                                <code class="text-[10px] bg-slate-100 px-2 py-1 rounded text-slate-600 font-mono">{{ $trx->reference_no }}</code>
                            </td>
                            <td class="px-4 py-4">
                                @php $isDebit = in_array($trx->type, ['TOPUP', 'DISBURSEMENT', 'TRANSFER_BANK']); @endphp
                                <span class="text-[9px] font-black px-2 py-1 rounded-full border {{ $isDebit ? 'border-rose-200 text-rose-600 bg-rose-50' : 'border-emerald-200 text-emerald-600 bg-emerald-50' }}">
                                    {{ $trx->type }}
                                </span>
                            </td>
                            <td class="px-4 py-4 text-right">
                                <p class="text-xs font-black {{ $isDebit ? 'text-rose-600' : 'text-emerald-600' }}">
                                    {{ $isDebit ? '-' : '+' }} Rp {{ number_format($trx->amount, 0, ',', '.') }}
                                </p>
                            </td>
                            <td class="px-4 py-4 text-center">
                                <span class="inline-flex items-center gap-1 px-2 py-1 rounded-md text-[9px] font-black uppercase {{ $trx->status == 'SUCCESS' ? 'bg-emerald-500 text-white' : ($trx->status == 'PENDING' ? 'bg-amber-500 text-white' : 'bg-rose-500 text-white') }}">
                                    {{ $trx->status }}
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-4 py-10 text-center text-slate-400 uppercase text-[10px] font-black">
                                <i class="fas fa-folder-open mb-2 text-2xl block opacity-20"></i> Tidak ada data
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-4 py-4 bg-slate-50/50 border-t border-slate-100">
                {{ $transactions->links() }}
            </div>
        </div>
    </div>

    {{-- ======================================================================= --}}
    {{-- MODALS --}}
    {{-- ======================================================================= --}}

    {{-- MODAL PENCAIRAN SALDO KE DANA (TOPUP) --}}
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
                </div>
                <div class="flex gap-3">
                    <button type="button" onclick="closeTopupModal()" class="flex-1 py-3 text-slate-400 font-bold text-xs uppercase transition">Batal</button>
                    <button type="submit" class="flex-[2] py-3 bg-emerald-600 text-white rounded-xl font-black text-xs uppercase shadow-lg hover:bg-emerald-700 transition">Konfirmasi</button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL KE BANK (INQUIRY + TRANSFER) --}}
    <div id="bankModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl w-full max-w-md shadow-2xl overflow-hidden relative">

            {{-- LOGIKA TAMPILAN MODAL: JIKA SUKSES CEK REKENING, TAMPILKAN FORM TRANSFER --}}
            @if(session('dana_report') && session('dana_report')->is_success)

                {{-- TAMPILAN 2: KONFIRMASI TRANSFER --}}
                <div class="bg-emerald-600 p-6 text-white text-center">
                    <div class="w-12 h-12 bg-white/20 rounded-full flex items-center justify-center mx-auto mb-3 backdrop-blur-md">
                        <i class="fas fa-check text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-black uppercase italic tracking-tighter">Rekening Valid!</h3>
                    <p class="text-emerald-100 text-[10px] uppercase font-bold tracking-widest mb-4">Siap Ditransfer</p>
                </div>

                {{-- FORM FINAL TRANSFER --}}
                <form action="{{ route('member.dana.transferBank') }}" method="POST" class="p-6">
                    @csrf
                    {{-- Hidden inputs mengambil nilai agar data tidak hilang --}}
                    <input type="hidden" name="affiliate_id" value="{{ old('affiliate_id', $member->id) }}">
                    <input type="hidden" name="bank_code" value="{{ old('bank_code') }}">
                    <input type="hidden" name="account_no" value="{{ old('account_no') }}">
                    <input type="hidden" name="amount" value="{{ old('amount') }}">

                    {{-- TAMBAHKAN INI: Mengambil nama asli dari Controller --}}
                    <input type="hidden" name="account_name" value="{{ session('valid_account_name') }}">

                    <div class="bg-slate-50 rounded-xl p-4 border border-slate-200 mb-6 space-y-3">
                        <div class="flex justify-between border-b border-slate-200 pb-2">
                            <span class="text-xs text-slate-500">Bank Tujuan</span>
                            <span class="text-xs font-bold text-slate-800">
                                {{ old('bank_code') == '014' ? 'BCA' : (old('bank_code') == '114' ? 'BANK JATIM' : 'KODE: '.old('bank_code')) }}
                            </span>
                        </div>
                        <div class="flex justify-between border-b border-slate-200 pb-2">
                            <span class="text-xs text-slate-500">No. Rekening</span>
                            <span class="text-xs font-bold text-slate-800">{{ old('account_no') }}</span>
                        </div>
                        {{-- Tampilkan Nama Pemilik di UI Konfirmasi --}}
                        <div class="flex justify-between border-b border-slate-200 pb-2">
                            <span class="text-xs text-slate-500">Atas Nama</span>
                            <span class="text-xs font-bold text-slate-800 uppercase">{{ session('valid_account_name') }}</span>
                        </div>
                        <div class="flex justify-between border-b border-slate-200 pb-2">
                            <span class="text-xs text-slate-500">Nominal Transfer</span>
                            <span class="text-lg font-black text-emerald-600">Rp {{ number_format((float)old('amount'), 0, ',', '.') }}</span>
                        </div>

                        {{-- Info Hasil Cek --}}
                        <div class="bg-emerald-100 text-emerald-800 p-3 rounded-lg text-[10px] font-bold mt-2">
                            <i class="fas fa-info-circle mr-1"></i> {{ session('dana_report')->description }}
                        </div>
                    </div>

                    <div class="flex gap-3">
                        <a href="{{ route('member.dashboard') }}" class="flex-1 py-3 text-center bg-slate-100 text-slate-500 rounded-xl font-bold text-xs uppercase hover:bg-slate-200 transition">Batal</a>
                        <button type="submit" class="flex-[2] py-3 bg-emerald-600 text-white rounded-xl font-black text-xs uppercase shadow-lg hover:bg-emerald-700 transition">
                            <i class="fas fa-paper-plane mr-1"></i> Transfer Sekarang
                        </button>
                    </div>
                    <p class="text-[9px] text-center text-slate-400 mt-4">*Saldo akan otomatis terpotong.</p>
                </form>

            @else

                {{-- TAMPILAN 1: FORM CEK REKENING (INQUIRY) --}}
                <div class="bg-slate-800 p-6 text-white">
                    <h3 class="text-lg font-black uppercase italic tracking-tighter">Cairkan Ke Bank</h3>
                    <p class="text-slate-400 text-[10px] uppercase font-bold tracking-widest">Bank Account Inquiry</p>
                </div>

                <form action="{{ route('member.dana.bankInquiry') }}" method="POST" class="p-6">
                    @csrf
                    <input type="hidden" name="affiliate_id" id="bank_aff_id" value="{{ $member->id }}">

                    {{-- Pilih Bank --}}
                    <div class="mb-4">
                        <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">Pilih Bank Tujuan</label>
                        <select name="bank_code" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl font-bold text-slate-700 focus:ring-2 focus:ring-blue-500 transition">
                            <option value="" disabled selected>-- Pilih Bank Tujuan --</option>
                            <optgroup label="Bank Terpopuler">
                                <option value="014">Bank BCA (Kode: 014)</option>
                                <option value="008">Bank Mandiri</option>
                                <option value="002">Bank BRI</option>
                                <option value="009">Bank BNI</option>
                                <option value="427">Bank Syariah Indonesia (BSI)</option>
                            </optgroup>
                            <optgroup label="Bank Daerah">
                                <option value="114">Bank Jatim (Kode: 114)</option>
                                <option value="110">Bank BJB</option>
                                <option value="111">Bank DKI</option>
                            </optgroup>
                            {{-- Tambahkan bank lain sesuai kebutuhan --}}
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
                            <span class="text-[10px] font-black text-blue-600 uppercase" id="bank_max_balance">Limit: Rp {{ number_format($member->balance,0,',','.') }}</span>
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

            @endif
        </div>
    </div>

    {{-- ======================================================================= --}}
    {{-- JAVASCRIPT --}}
    {{-- ======================================================================= --}}

    <script>
        // Modal Topup DANA
        function openTopupModal(id, balance, phone) {
            document.getElementById('modal_aff_id').value = id;
            let cleanPhone = phone.startsWith('62') ? phone.substring(2) : (phone.startsWith('0') ? phone.substring(1) : phone);
            document.getElementById('modal_aff_phone').value = cleanPhone;
            document.getElementById('modal_max_balance').innerText = 'Limit: Rp ' + new Intl.NumberFormat('id-ID').format(balance);
            document.getElementById('topupModal').classList.remove('hidden');
        }
        function closeTopupModal() {
            document.getElementById('topupModal').classList.add('hidden');
        }

        // Modal Bank
        function openBankModal(id, balance) {
            document.getElementById('bank_aff_id').value = id;
            document.getElementById('bank_max_balance').innerText = 'Limit: Rp ' + new Intl.NumberFormat('id-ID').format(balance);
            document.getElementById('bankModal').classList.remove('hidden');
        }
        function closeBankModal() {
            document.getElementById('bankModal').classList.add('hidden');
        }
    </script>

    {{-- AUTO OPEN MODAL JIKA SUKSES CEK REKENING --}}
    @if(session('dana_report') && session('dana_report')->is_success)
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Otomatis buka modal bank jika ada session sukses (hasil inquiry)
            document.getElementById('bankModal').classList.remove('hidden');
        });
    </script>
    @endif

    <style>
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-in { animation: fadeIn 0.4s ease-out forwards; }
    </style>

@endsection
