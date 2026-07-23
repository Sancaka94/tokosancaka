@extends('layouts.customer')

@section('content')
<div class="container mx-auto px-4 py-6 max-w-5xl">

    <!-- NOTIFIKASI -->
    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-xl mb-4 text-sm font-medium">
            <i class="fa-solid fa-circle-check mr-1"></i> {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-xl mb-4 text-sm font-medium">
            <i class="fa-solid fa-circle-exclamation mr-1"></i> {{ session('error') }}
        </div>
    @endif

    <!-- Header & Cards Statistik -->
    <div class="flex flex-col md:flex-row md:justify-between md:items-center mb-6 gap-4">
        <div>
            <h2 class="font-bold text-2xl text-gray-800">Riwayat Pencairan Komisi</h2>
            <p class="text-gray-500 text-sm mt-1">Daftar histori saldo komisi yang berhasil dicairkan ke akun Anda.</p>
        </div>

        <!-- Cards Grid (Sisa Komisi & Total Dicairkan) -->
        <div class="flex flex-col sm:flex-row gap-3">
            <!-- Card Sisa Komisi -->
            <div class="bg-gradient-to-r from-blue-600 to-indigo-700 rounded-xl p-4 shadow-lg text-white min-w-[220px] flex flex-col justify-between">
                <div>
                    <p class="text-[11px] font-semibold text-blue-100 uppercase tracking-wide mb-1">Sisa Komisi Bisa Ditarik</p>
                    <h3 class="text-xl font-black">Rp {{ number_format($sisaKomisi, 0, ',', '.') }}</h3>
                </div>
                @if($sisaKomisi > 0)
                <button type="button" onclick="openWithdrawModal({{ $sisaKomisi }})" class="mt-3 bg-white hover:bg-blue-50 text-blue-700 font-bold py-1.5 px-3 rounded-lg text-xs transition shadow-sm flex items-center justify-center">
                    <i class="fa-solid fa-money-bill-transfer mr-1"></i> Tarik Komisi (Withdraw)
                </button>
                @else
                <div class="mt-3 text-[11px] text-blue-200 italic">Komisi sudah habis ditarik</div>
                @endif
            </div>

            <!-- Card Total Telah Dicairkan -->
            <div class="bg-gradient-to-r from-emerald-500 to-green-600 rounded-xl p-4 shadow-lg text-white min-w-[200px]">
                <p class="text-[11px] font-semibold text-emerald-100 uppercase tracking-wide mb-1">Total Telah Dicairkan</p>
                <h3 class="text-xl font-black mt-2">Rp {{ number_format($totalDicairkan, 0, ',', '.') }}</h3>
                <p class="text-[10px] text-emerald-100 mt-2"><i class="fa-solid fa-wallet"></i> Masuk ke saldo dompet</p>
            </div>
        </div>
    </div>

    <!-- Tabel Riwayat -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-4 border-b border-gray-100 bg-gray-50/50">
            <h3 class="font-bold text-gray-700 text-sm"><i class="fa-solid fa-clock-rotate-left mr-2 text-emerald-500"></i> Mutasi Pencairan Terakhir</h3>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200 text-xs text-gray-500 uppercase tracking-wider">
                        <th class="p-4 font-bold w-16 text-center">No</th>
                        <th class="p-4 font-bold">Tanggal & Waktu</th>
                        <th class="p-4 font-bold">Keterangan</th>
                        <th class="p-4 font-bold text-right">Nominal Masuk</th>
                    </tr>
                </thead>
                <tbody class="text-sm divide-y divide-gray-100">
                    @forelse($riwayat as $index => $item)
                    <tr class="hover:bg-gray-50/50 transition">
                        <td class="p-4 align-middle text-center text-gray-500">
                            {{ $riwayat->firstItem() + $index }}
                        </td>
                        <td class="p-4 align-middle text-gray-700 whitespace-nowrap">
                            <div class="font-semibold text-gray-800">
                                {{ \Carbon\Carbon::parse($item->created_at)->translatedFormat('d F Y') }}
                            </div>
                            <div class="text-xs text-gray-500 mt-0.5">
                                <i class="fa-regular fa-clock mr-1"></i> {{ \Carbon\Carbon::parse($item->created_at)->format('H:i') }} WIB
                            </div>
                        </td>
                        <td class="p-4 align-middle">
                            <span class="inline-flex items-center gap-1.5 bg-emerald-50 text-emerald-700 px-3 py-1.5 rounded-lg text-xs font-semibold border border-emerald-100">
                                <i class="fa-solid fa-check-circle"></i>
                                {{ $item->keterangan ?? 'Pencairan komisi ke saldo agen' }}
                            </span>
                        </td>
                        <td class="p-4 align-middle text-right">
                            <span class="font-black text-emerald-600 text-base">
                                + Rp {{ number_format($item->nominal, 0, ',', '.') }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="p-10 text-center">
                            <div class="flex flex-col items-center justify-center text-gray-400">
                                <i class="fa-solid fa-money-bill-transfer text-4xl mb-3 opacity-50"></i>
                                <p class="text-gray-500 font-medium">Belum ada riwayat pencairan.</p>
                                <p class="text-xs mt-1">Komisi Anda yang cair akan muncul di sini.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($riwayat->hasPages())
        <div class="p-4 border-t border-gray-100 bg-gray-50">
            {{ $riwayat->links() }}
        </div>
        @endif
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL PENCAIRAN MANDIRI (WITHDRAW) -->
<!-- ========================================== -->
<div id="withdrawModal" class="fixed inset-0 z-[100] hidden bg-gray-900/60 backdrop-blur-sm flex justify-center items-center px-4">
    <div class="bg-white w-full max-w-md rounded-2xl shadow-2xl overflow-hidden transform scale-95 transition-all duration-200" id="withdrawModalContent">
        <form method="POST" action="{{ route('customer.riwayat-pencairan.tarik') }}">
            @csrf
            <input type="hidden" name="idempotency_key" id="modal_withdraw_idempotency">

            <div class="bg-gradient-to-r from-blue-600 to-indigo-700 p-5 text-white relative">
                <h3 class="font-bold text-lg"><i class="fa-solid fa-money-bill-transfer mr-2"></i> Tarik Komisi (Withdraw)</h3>
                <p class="text-xs text-blue-100 mt-0.5">Pindahkan sisa komisi ke saldo utama akun Anda.</p>
            </div>

            <div class="p-5 space-y-4">
                <div class="bg-blue-50 border border-blue-100 rounded-xl p-3.5 text-sm">
                    <span class="text-blue-700 font-semibold block mb-1">Maksimal Sisa Komisi:</span>
                    <strong class="text-blue-900 text-lg font-black">Rp <span id="max_sisa_komisi_text">0</span></strong>
                </div>

                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Nominal Penarikan (Rp)</label>
                    <input type="number" name="nominal_cair" id="modal_withdraw_input" min="1" required
                        class="w-full border-2 border-blue-200 rounded-xl px-4 py-3 text-xl font-black text-gray-800 focus:ring-blue-500 focus:border-blue-500" placeholder="Contoh: 50000">
                    <p class="text-xs text-gray-500 mt-2">Dana akan langsung bertambah ke Saldo Dompet Anda setelah diproses.</p>
                </div>
            </div>

            <div class="bg-gray-50 p-4 flex justify-end gap-2 border-t border-gray-100">
                <button type="button" onclick="closeWithdrawModal()" class="bg-white border border-gray-300 hover:bg-gray-100 text-gray-700 font-bold py-2 px-4 rounded-xl transition shadow-sm text-sm">Batal</button>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded-xl transition shadow-md text-sm">Konfirmasi Tarik</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openWithdrawModal(maxSisa) {
        document.getElementById('max_sisa_komisi_text').innerText = new Intl.NumberFormat('id-ID').format(maxSisa);

        // Default isi input dengan total sisa komisi (bisa diubah jika ingin parsial)
        let inputEl = document.getElementById('modal_withdraw_input');
        inputEl.value = maxSisa;
        inputEl.max = maxSisa;

        // Generate Idempotency Key unik agar aman dari double submit
        document.getElementById('modal_withdraw_idempotency').value = Date.now().toString(36) + '-' + Math.random().toString(36).substring(2, 10);

        const modal = document.getElementById('withdrawModal');
        modal.classList.remove('hidden');
        setTimeout(() => {
            document.getElementById('withdrawModalContent').classList.remove('scale-95', 'opacity-0');
            document.getElementById('withdrawModalContent').classList.add('scale-100', 'opacity-100');
        }, 10);
    }

    function closeWithdrawModal() {
        document.getElementById('withdrawModalContent').classList.remove('scale-100', 'opacity-100');
        document.getElementById('withdrawModalContent').classList.add('scale-95', 'opacity-0');
        setTimeout(() => { document.getElementById('withdrawModal').classList.add('hidden'); }, 200);
    }
</script>
@endsection
