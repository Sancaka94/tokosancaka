@extends('layouts.admin')

@section('title', 'Riwayat Transaksi PPOB')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    {{-- Alert Messages --}}
    @if(session('success'))
        <div class="mb-5 rounded-md bg-green-50 p-4 border-l-4 border-green-400 flex items-start shadow-sm">
            <i class="fas fa-check-circle text-green-500 mt-0.5 mr-3"></i>
            <p class="text-sm text-green-800 font-medium">{{ session('success') }}</p>
        </div>
    @endif

    @if(session('error'))
        <div class="mb-5 rounded-md bg-red-50 p-4 border-l-4 border-red-400 flex items-start shadow-sm">
            <i class="fas fa-exclamation-circle text-red-500 mt-0.5 mr-3"></i>
            <p class="text-sm text-red-800 font-medium">{{ session('error') }}</p>
        </div>
    @endif

    {{-- Header Section --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 flex items-center">
                <i class="fas fa-history text-indigo-600 mr-3"></i> Riwayat Transaksi PPOB
            </h2>
            <p class="mt-1 text-sm text-gray-500">Daftar riwayat pembelian prabayar dan pembayaran pascabayar Anda.</p>
        </div>
        <div class="mt-4 md:mt-0">
            <a href="{{ route('ppob.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-indigo-600 rounded-md font-semibold text-xs text-indigo-600 uppercase tracking-widest hover:bg-indigo-50 active:bg-indigo-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150 shadow-sm">
                <i class="fas fa-plus-circle mr-2"></i> Transaksi Baru
            </a>
        </div>
    </div>

    {{-- Table Section --}}
    <div class="bg-white rounded-lg shadow-md overflow-hidden border border-gray-200">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Tanggal</th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Ref ID / Tujuan</th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Produk</th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Harga</th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Status</th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">SN / Token</th>
                        <th scope="col" class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($transactions as $trx)
                        <tr class="hover:bg-gray-50 transition-colors">

                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-bold text-gray-900">{{ $trx->created_at->format('d M Y') }}</div>
                                <div class="text-xs text-gray-500">{{ $trx->created_at->format('H:i') }} WIB</div>
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-xs text-gray-500 mb-1">{{ $trx->ref_id }}</div>
                                <div class="text-sm font-bold text-gray-900">{{ $trx->customer_id }}</div>
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2.5 py-0.5 inline-flex text-[10px] leading-4 font-bold rounded bg-gray-100 text-gray-700 border border-gray-300 uppercase tracking-wider mb-1">
                                    {{ $trx->type }}
                                </span>
                                <div class="text-sm font-medium text-gray-900">{{ $trx->product_code }}</div>
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-bold text-gray-900">Rp {{ number_format($trx->price, 0, ',', '.') }}</div>
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
                                    <div class="text-xs font-mono bg-gray-50 text-gray-700 p-2 rounded border border-gray-200 break-all max-w-[180px]">
                                        {{ $trx->sn }}
                                    </div>
                                @else
                                    <span class="text-gray-400 text-sm font-medium">-</span>
                                @endif
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex items-center justify-end space-x-2">

                                    {{-- Tombol Struk --}}
                                    <a href="{{ route('ppob.iak.invoice', $trx->ref_id) }}" class="inline-flex items-center px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded shadow-sm transition-colors" title="Cetak Struk">
                                        <i class="fas fa-receipt mr-1.5"></i> Struk
                                    </a>

                                    {{-- Tombol Kirim WA (Memanggil fungsi Javascript kirimWa) --}}
                                    <button type="button" onclick="kirimWa('{{ $trx->ref_id }}', '{{ $trx->whatsapp_number ?? '' }}')" class="inline-flex items-center px-3 py-1.5 bg-green-500 hover:bg-green-600 text-white text-xs font-bold rounded shadow-sm transition-colors" title="Kirim Detail ke WA">
                                        <i class="fab fa-whatsapp mr-1.5"></i> WA
                                    </button>

                                    {{-- Tombol Cek Status --}}
                                    @if(in_array($trx->status, ['PROCESS', 'PENDING']))
                                        @if($trx->type === 'prabayar')
                                            <a href="{{ route('ppob.iak.check_prepaid', $trx->ref_id) }}" class="inline-flex items-center px-3 py-1.5 bg-yellow-400 hover:bg-yellow-500 text-yellow-900 text-xs font-bold rounded shadow-sm transition-colors" title="Cek Status Prabayar">
                                                <i class="fas fa-sync-alt mr-1.5"></i> Cek
                                            </a>
                                        @else
                                            @if($trx->tr_id)
                                                <a href="{{ route('ppob.iak.check_postpaid', $trx->tr_id) }}" class="inline-flex items-center px-3 py-1.5 bg-yellow-400 hover:bg-yellow-500 text-yellow-900 text-xs font-bold rounded shadow-sm transition-colors" title="Cek Tagihan Pascabayar">
                                                    <i class="fas fa-sync-alt mr-1.5"></i> Cek
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
            <div class="bg-white px-6 py-4 border-t border-gray-200">
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

{{-- Skrip Khusus WA --}}
<script>
    function kirimWa(refId, defaultNumber) {
        // Tampilkan prompt input nomor WA
        let targetNumber = prompt("Masukkan nomor WhatsApp tujuan (contoh: 0812...):", defaultNumber);

        // Cek jika user mengisi dan menekan OK
        if (targetNumber !== null && targetNumber.trim() !== "") {
            let form = document.getElementById('waForm');
            // Arahkan action ke route yang telah dibuat
            form.action = "{{ url('ppob/iak/send-wa') }}/" + refId;
            document.getElementById('waTargetInput').value = targetNumber.trim();
            form.submit();
        }
    }
</script>

@endsection
