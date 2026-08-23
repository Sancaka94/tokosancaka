@extends('layouts.marketplace')

@section('title', 'Riwayat Transaksi PPOB')

@section('content')
<div class="min-h-screen bg-gray-50 py-8 font-sans">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        {{-- Alert Messages --}}
        @if(session('success'))
            <div class="mb-5 rounded-xl bg-green-50 p-4 border-l-4 border-green-500 flex items-start shadow-sm">
                <i class="fas fa-check-circle text-green-500 mt-0.5 mr-3 text-lg"></i>
                <p class="text-sm text-green-800 font-bold">{{ session('success') }}</p>
            </div>
        @endif

        @if(session('error'))
            <div class="mb-5 rounded-xl bg-red-50 p-4 border-l-4 border-red-500 flex items-start shadow-sm">
                <i class="fas fa-exclamation-circle text-red-500 mt-0.5 mr-3 text-lg"></i>
                <p class="text-sm text-red-800 font-bold">{{ session('error') }}</p>
            </div>
        @endif

        {{-- Header Section --}}
        <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8 bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
            <div>
                <h2 class="text-2xl font-extrabold text-gray-900 flex items-center">
                    <i class="fas fa-history text-blue-600 mr-3"></i> Riwayat PPOB & Tagihan
                </h2>
                <p class="mt-2 text-sm text-gray-500 font-medium">Daftar riwayat pembelian pulsa dan pembayaran tagihan Anda.</p>
            </div>
            <div class="mt-4 md:mt-0">
                <a href="{{ route('ppob.index') }}" class="inline-flex items-center px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-bold text-sm shadow-md transition-all">
                    <i class="fas fa-plus-circle mr-2"></i> Transaksi Baru
                </a>
            </div>
        </div>

        {{-- Table Section --}}
        <div class="bg-white rounded-2xl shadow-sm overflow-hidden border border-gray-100">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-extrabold text-gray-500 uppercase tracking-wider">Tanggal</th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-extrabold text-gray-500 uppercase tracking-wider">Ref ID / Tujuan</th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-extrabold text-gray-500 uppercase tracking-wider">Produk</th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-extrabold text-gray-500 uppercase tracking-wider">Total</th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-extrabold text-gray-500 uppercase tracking-wider">Status</th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-extrabold text-gray-500 uppercase tracking-wider">SN / Token</th>
                            <th scope="col" class="px-6 py-4 text-right text-xs font-extrabold text-gray-500 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        @forelse($transactions as $trx)
                            <tr class="hover:bg-blue-50 transition-colors">

                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-bold text-gray-900">{{ $trx->created_at->format('d M Y') }}</div>
                                    <div class="text-xs font-medium text-gray-500">{{ $trx->created_at->format('H:i') }} WIB</div>
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-xs text-gray-500 mb-1">{{ $trx->ref_id }}</div>
                                    <div class="text-sm font-bold text-gray-900">{{ $trx->customer_id }}</div>
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2.5 py-0.5 inline-flex text-[10px] leading-4 font-bold rounded bg-gray-100 text-gray-700 border border-gray-300 uppercase tracking-wider mb-1">
                                        {{ $trx->type }}
                                    </span>
                                    <div class="text-sm font-bold text-blue-600 truncate max-w-[150px]" title="{{ $trx->product_code }}">{{ $trx->product_code }}</div>
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-black text-gray-900">Rp {{ number_format($trx->price, 0, ',', '.') }}</div>
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($trx->status == 'SUCCESS')
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-bold bg-green-50 text-green-700 border border-green-200">
                                            <i class="fas fa-check-circle mr-1.5"></i> Sukses
                                        </span>
                                    @elseif(in_array($trx->status, ['PROCESS', 'PENDING']))
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-bold bg-yellow-50 text-yellow-700 border border-yellow-200">
                                            <i class="fas fa-sync-alt fa-spin mr-1.5"></i> Proses
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-bold bg-red-50 text-red-700 border border-red-200">
                                            <i class="fas fa-times-circle mr-1.5"></i> Gagal
                                        </span>
                                    @endif
                                </td>

                                <td class="px-6 py-4">
                                    @if(!empty($trx->sn))
                                        <div class="text-xs font-mono bg-gray-50 text-gray-700 p-2 rounded border border-gray-200 break-all max-w-[180px] font-bold">
                                            {{ $trx->sn }}
                                        </div>
                                    @else
                                        <span class="text-gray-400 text-sm font-medium">-</span>
                                    @endif
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex items-center justify-end space-x-2">

                                        {{-- TOMBOL BAYAR (Muncul Jika Tagihan Belum Dibayar) --}}
                                        @if($trx->status === 'PENDING' && $trx->type === 'pascabayar')
                                            <button type="button" onclick="openHistoryModal('{{ $trx->tr_id }}', '{{ number_format($trx->price, 0, ',', '.') }}')" class="inline-flex items-center px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded shadow-sm transition-colors" title="Bayar Tagihan">
                                                <i class="fas fa-wallet mr-1.5"></i> Bayar
                                            </button>
                                        @endif

                                        {{-- Tombol Struk --}}
                                        <a href="{{ route('ppob.iak.invoice', $trx->ref_id) }}" class="inline-flex items-center px-3 py-1.5 bg-gray-800 hover:bg-gray-900 text-white text-xs font-semibold rounded shadow-sm transition-colors" title="Cetak Struk">
                                            <i class="fas fa-receipt mr-1.5"></i> Struk
                                        </a>

                                        {{-- Tombol Kirim WA --}}
                                        <button type="button" onclick="kirimWa('{{ $trx->ref_id }}', '{{ $trx->whatsapp_number ?? '' }}')" class="inline-flex items-center px-3 py-1.5 bg-green-500 hover:bg-green-600 text-white text-xs font-bold rounded shadow-sm transition-colors" title="Kirim Detail ke WA">
                                            <i class="fab fa-whatsapp mr-1.5"></i> WA
                                        </button>

                                        {{-- Tombol Cek Status --}}
                                        @if(in_array($trx->status, ['PROCESS', 'PENDING']))
                                            @if($trx->type === 'prabayar')
                                                <a href="{{ route('ppob.iak.check_prepaid', $trx->ref_id) }}" class="inline-flex items-center px-3 py-1.5 bg-yellow-400 hover:bg-yellow-500 text-yellow-900 text-xs font-bold rounded shadow-sm transition-colors" title="Cek Status Prabayar">
                                                    <i class="fas fa-sync-alt"></i>
                                                </a>
                                            @else
                                                @if($trx->tr_id)
                                                    <a href="{{ route('ppob.iak.check_postpaid', $trx->tr_id) }}" class="inline-flex items-center px-3 py-1.5 bg-yellow-400 hover:bg-yellow-500 text-yellow-900 text-xs font-bold rounded shadow-sm transition-colors" title="Cek Tagihan Pascabayar">
                                                        <i class="fas fa-sync-alt"></i>
                                                    </a>
                                                @endif
                                            @endif
                                        @endif

                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center">
                                    <i class="fas fa-inbox text-gray-300 text-5xl mb-4"></i>
                                    <p class="text-gray-500 text-sm font-medium">Belum ada riwayat transaksi PPOB.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($transactions->hasPages())
                <div class="bg-gray-50 px-6 py-4 border-t border-gray-200">
                    {{ $transactions->links() }}
                </div>
            @endif
        </div>

        {{-- Hidden Form untuk Request POST Kirim WA --}}
        <form id="waForm" method="POST" action="" class="hidden">
            @csrf
            <input type="hidden" name="target_wa" id="waTargetInput">
        </form>
    </div>
</div>

<!-- ========================================================== -->
<!-- MODAL: METODE PEMBAYARAN KHUSUS HISTORY -->
<!-- ========================================================== -->
<div id="historyPaymentModal" class="fixed inset-0 bg-gray-900 bg-opacity-75 flex items-center justify-center z-[200] hidden transition-opacity">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-5xl mx-4 transform transition-all flex flex-col max-h-[90vh]">

        <div class="flex justify-between items-center p-5 border-b border-gray-200">
            <h3 class="text-lg font-bold text-gray-900">Pilih Metode Pembayaran</h3>
            <button type="button" onclick="closeHistoryModal()" class="text-gray-400 hover:text-red-600 bg-gray-100 hover:bg-red-50 p-2 rounded-full transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>

        <div class="p-2 overflow-y-auto custom-scrollbar flex-1">
            <ul class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 p-4">

                <!-- 1. OPSI INTERNAL (SALDO) -->
                @auth
                <li class="payment-option col-span-full cursor-pointer flex items-center p-3 border rounded-lg hover:bg-red-50 transition-colors" data-value="SALDO">
                    <img src="{{ asset('public/assets/saldo.png') }}" class="h-8 w-8 object-contain mr-4" onerror="this.src='https://placehold.co/32x32/EFEFEF/AAAAAA?text=Rp'">
                    <span class="text-sm font-medium text-gray-900">Saldo {{ optional(Auth::user())->nama_lengkap }}: (Rp{{ number_format(optional(Auth::user())->saldo ?? 0, 0, ',', '.') }})</span>
                </li>
                @endauth

                <!-- 2. OPSI KHUSUS (DOKU) -->
                <li class="payment-option col-span-full cursor-pointer flex items-center p-3 border rounded-lg hover:bg-red-50 transition-colors" data-value="DOKU_JOKUL">
                    <img src="{{ asset('public/assets/doku.png') }}" class="h-8 w-8 object-contain mr-4" onerror="this.src='https://placehold.co/32x32/EFEFEF/AAAAAA?text=DK'">
                    <div class="flex flex-col">
                        <span class="text-sm font-bold text-gray-900">Rekomendasi Sancaka (DOKU)</span>
                        <span class="text-[11px] text-gray-500 mt-0.5">Semua Pembayaran Tersedia</span>
                    </div>
                </li>

                <!-- 3. DANA ENTERPRISE -->
                <li class="col-span-full px-1 pt-4 pb-1 text-xs font-bold text-gray-500 uppercase tracking-wider border-b border-gray-100">
                    DANA Enterprise
                </li>

                @php
                    $userDanaToken = Auth::user() ? Auth::user()->dana_access_token : null;
                    $userDanaBalance = Auth::user() ? (Auth::user()->dana_user_balance ?? 0) : 0;
                    $hasDanaBinding = !empty($userDanaToken);
                @endphp

                <li class="payment-option col-span-1 cursor-pointer flex items-center p-3 border rounded-lg hover:bg-red-50 transition-colors" data-value="DANA">
                    <img src="{{ asset('public/assets/dana.webp') }}" class="h-8 w-8 object-contain mr-4" onerror="this.src='https://upload.wikimedia.org/wikipedia/commons/7/72/Logo_dana_blue.svg'">
                    <div class="flex flex-col">
                        <span class="text-sm font-bold text-gray-900">DANA Checkout</span>
                        <span class="text-[11px] text-gray-500 mt-0.5">Diarahkan ke aplikasi DANA</span>
                    </div>
                </li>

                @if($hasDanaBinding)
                    <li class="payment-option col-span-1 cursor-pointer flex items-center p-3 border rounded-lg border-blue-200 bg-blue-50 hover:bg-blue-100 transition-colors" data-value="DANA_BINDING">
                        <img src="{{ asset('public/assets/dana.webp') }}" class="h-8 w-8 object-contain mr-4">
                        <div class="flex flex-col flex-1">
                            <span class="text-sm font-bold text-gray-900">DANA Auto-Debit</span>
                            <span class="text-[11px] text-gray-600 font-medium mt-0.5">Saldo: <span class="text-blue-700">Rp{{ number_format($userDanaBalance, 0, ',', '.') }}</span></span>
                        </div>
                    </li>
                @endif

                <!-- 4. LAINNYA (PAYPAL DLL) -->
                <li class="col-span-full px-1 pt-4 pb-1 text-xs font-bold text-gray-500 uppercase tracking-wider border-b border-gray-100">
                    Lainnya
                </li>

                <li class="payment-option col-span-1 cursor-pointer flex items-center p-3 border rounded-lg hover:bg-red-50 transition-colors" data-value="PAYPAL">
                    <img src="https://tokosancaka.com/public/assets/paypal.png" class="h-8 object-contain mr-4" onerror="this.src='https://placehold.co/32x32/EFEFEF/AAAAAA?text=PP'">
                    <div class="flex flex-col">
                        <span class="text-sm font-bold text-gray-900">PayPal / Kartu Kredit</span>
                        <span class="text-[11px] text-gray-500 mt-0.5">Pembayaran Global (Otomatis USD)</span>
                    </div>
                </li>

                <!-- 5. VIRTUAL ACCOUNT -->
                <li class="col-span-full px-1 pt-4 pb-1 text-xs font-bold text-gray-500 uppercase tracking-wider border-b border-gray-100">
                    Virtual Account (Transfer Bank)
                </li>

                <li class="payment-option col-span-1 cursor-pointer flex items-center p-3 border rounded-lg hover:bg-red-50 transition-colors" data-value="DOKU_BCA_VA">
                    <img src="{{ asset('public/assets/bca.webp') }}" class="h-6 w-12 object-contain mr-4">
                    <div class="flex flex-col">
                        <span class="text-sm font-bold text-gray-900">BCA Virtual Account</span>
                        <span class="text-[11px] text-gray-500 mt-0.5">Diverifikasi Otomatis</span>
                    </div>
                </li>

                <li class="payment-option col-span-1 cursor-pointer flex items-center p-3 border rounded-lg hover:bg-red-50 transition-colors" data-value="DOKU_MANDIRI_VA">
                    <img src="{{ asset('public/assets/mandiri.webp') }}" class="h-6 w-12 object-contain mr-4">
                    <div class="flex flex-col">
                        <span class="text-sm font-bold text-gray-900">Mandiri Virtual Account</span>
                        <span class="text-[11px] text-gray-500 mt-0.5">Diverifikasi Otomatis</span>
                    </div>
                </li>

                <li class="payment-option col-span-1 cursor-pointer flex items-center p-3 border rounded-lg hover:bg-red-50 transition-colors" data-value="DOKU_BRI_VA">
                    <img src="{{ asset('public/assets/bri.webp') }}" class="h-6 w-12 object-contain mr-4">
                    <div class="flex flex-col">
                        <span class="text-sm font-bold text-gray-900">BRIVA</span>
                        <span class="text-[11px] text-gray-500 mt-0.5">Diverifikasi Otomatis</span>
                    </div>
                </li>

                <li class="payment-option col-span-1 cursor-pointer flex items-center p-3 border rounded-lg hover:bg-red-50 transition-colors" data-value="DOKU_BNI_VA">
                    <img src="{{ asset('public/assets/bni.webp') }}" class="h-6 w-12 object-contain mr-4">
                    <div class="flex flex-col">
                        <span class="text-sm font-bold text-gray-900">BNI Virtual Account</span>
                        <span class="text-[11px] text-gray-500 mt-0.5">Diverifikasi Otomatis</span>
                    </div>
                </li>

                <!-- 6. QRIS & MINIMARKET -->
                <li class="col-span-full px-1 pt-4 pb-1 text-xs font-bold text-gray-500 uppercase tracking-wider border-b border-gray-100">
                    Scan QRIS & Minimarket
                </li>

                <li class="payment-option col-span-1 cursor-pointer flex items-center p-3 border rounded-lg hover:bg-red-50 transition-colors" data-value="DOKU_QRIS">
                    <img src="{{ asset('public/assets/qris.png') }}" class="h-8 w-14 object-contain mr-3">
                    <div class="flex flex-col">
                        <span class="text-sm font-bold text-gray-900">QRIS (E-Wallet & Bank)</span>
                        <span class="text-[11px] text-gray-500 mt-0.5">Scan kode barcode di Invoice</span>
                    </div>
                </li>

                <li class="payment-option col-span-1 cursor-pointer flex items-center p-3 border rounded-lg hover:bg-red-50 transition-colors" data-value="DOKU_ALFAMART">
                    <img src="{{ asset('public/assets/alfamart.webp') }}" class="h-6 w-12 object-contain mr-4">
                    <div class="flex flex-col">
                        <span class="text-sm font-bold text-gray-900">Alfamart / Alfamidi</span>
                        <span class="text-[11px] text-gray-500 mt-0.5">Tunjukkan kode bayar ke kasir</span>
                    </div>
                </li>
            </ul>
        </div>

        <!-- FOOTER MODAL & FORM SUBMIT -->
        <div class="p-5 border-t border-gray-200 bg-gray-50 rounded-b-xl">
            <form action="{{ route('ppob.pay_postpaid') }}" method="POST" id="formPayHistory">
                @csrf
                <input type="hidden" name="tr_id" id="modal_tr_id" value="">
                <input type="hidden" name="payment_method" id="modal_payment_method" value="">

                <!-- Input WA & PIN Khusus Saldo -->
                <div id="modal_saldo_fields" class="hidden bg-red-50 p-4 rounded-xl border border-red-200 mb-4 space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-red-800 mb-1">No. WhatsApp Akun (Pembayar)</label>
                        <input type="number" name="wa_pembayaran" id="modal_wa_pembayaran" class="w-full px-3 py-2 border border-red-300 rounded-lg focus:ring-red-500 focus:border-red-500 bg-white" placeholder="Contoh: 0812...">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-red-800 mb-1">PIN Keamanan Sancaka</label>
                        <input type="password" name="pin_pembayaran" id="modal_pin_pembayaran" class="w-full px-3 py-2 border border-red-300 rounded-lg focus:ring-red-500 focus:border-red-500 bg-white" placeholder="******">
                    </div>
                </div>

                <button type="button" id="btnSubmitHistory" onclick="submitHistoryPayment()" class="w-full py-4 rounded-xl shadow-lg text-base font-bold text-white bg-blue-600 hover:bg-blue-700 transition-all opacity-50 cursor-not-allowed" disabled>
                    Pilih Metode Pembayaran Terlebih Dahulu
                </button>
            </form>
        </div>

    </div>
</div>

<style>
    .custom-scrollbar::-webkit-scrollbar { width: 6px; height: 6px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 10px; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
</style>

<script>
    function kirimWa(refId, defaultNumber) {
        let targetNumber = prompt("Masukkan nomor WhatsApp tujuan (contoh: 0812...):", defaultNumber);
        if (targetNumber !== null && targetNumber.trim() !== "") {
            let form = document.getElementById('waForm');
            form.action = "{{ url('ppob/iak/send-wa') }}/" + refId;
            document.getElementById('waTargetInput').value = targetNumber.trim();
            form.submit();
        }
    }

    // ==========================================
    // SCRIPT MODAL PEMBAYARAN HISTORY
    // ==========================================
    function openHistoryModal(tr_id, priceFormatted) {
        document.getElementById('modal_tr_id').value = tr_id;
        document.getElementById('btnSubmitHistory').innerText = `Bayar Sekarang (Rp ${priceFormatted})`;
        document.getElementById('historyPaymentModal').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function closeHistoryModal() {
        document.getElementById('historyPaymentModal').classList.add('hidden');
        document.body.style.overflow = 'auto';

        // Reset State
        document.getElementById('modal_payment_method').value = '';
        document.getElementById('modal_saldo_fields').classList.add('hidden');
        document.getElementById('modal_wa_pembayaran').required = false;
        document.getElementById('modal_pin_pembayaran').required = false;

        const btn = document.getElementById('btnSubmitHistory');
        btn.disabled = true;
        btn.classList.add('opacity-50', 'cursor-not-allowed');

        document.querySelectorAll('.payment-option').forEach(li => li.classList.remove('bg-red-50', 'border-red-500'));
    }

    document.querySelectorAll('.payment-option').forEach(item => {
        item.addEventListener('click', function () {
            const paymentValue = this.dataset.value;
            document.getElementById('modal_payment_method').value = paymentValue;

            // Handle styling
            document.querySelectorAll('.payment-option').forEach(li => li.classList.remove('bg-red-50', 'border-red-500'));
            this.classList.add('bg-red-50', 'border-red-500');

            // Handle WA & PIN fields
            const sFields = document.getElementById('modal_saldo_fields');
            const wa = document.getElementById('modal_wa_pembayaran');
            const pin = document.getElementById('modal_pin_pembayaran');

            if(paymentValue === 'SALDO') {
                sFields.classList.remove('hidden');
                wa.required = true;
                pin.required = true;
            } else {
                sFields.classList.add('hidden');
                wa.required = false;
                pin.required = false;
            }

            // Enable submit button
            const btn = document.getElementById('btnSubmitHistory');
            btn.disabled = false;
            btn.classList.remove('opacity-50', 'cursor-not-allowed');
        });
    });

    function submitHistoryPayment() {
        const method = document.getElementById('modal_payment_method').value;
        if(!method) return alert("Pilih metode pembayaran dulu!");

        if(method === 'SALDO') {
            const wa = document.getElementById('modal_wa_pembayaran').value;
            const pin = document.getElementById('modal_pin_pembayaran').value;
            if(!wa || !pin) return alert("Nomor WA dan PIN wajib diisi untuk potong saldo!");
        }

        const btn = document.getElementById('btnSubmitHistory');
        btn.innerText = "Memproses Pembayaran...";
        btn.disabled = true;
        btn.classList.add('opacity-50', 'cursor-not-allowed');

        document.getElementById('formPayHistory').submit();
    }
</script>
@endsection
