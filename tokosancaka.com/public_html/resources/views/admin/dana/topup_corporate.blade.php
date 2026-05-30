@extends('layouts.admin')

@section('title', 'Top Up Saldo DANA Corporate')

@section('content')
<div class="container mx-auto px-4 py-6">
    
    {{-- BAGIAN ATAS: FORMULIR TOP UP --}}
    <div class="bg-white shadow-md rounded-lg p-6 max-w-4xl mx-auto mb-10 border border-gray-200">
        <div class="border-b pb-4 mb-6">
            <h2 class="text-2xl font-bold text-gray-800">Top Up DANA Pelanggan (Corporate)</h2>
            <p class="text-gray-600 mt-1">Formulir untuk mencairkan saldo komisi aplikasi menjadi saldo DANA secara otomatis melalui saldo Merchant Deposit Sancaka.</p>
        </div>

        {{-- ALERT PESAN --}}
        @if (session('success'))
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-5 rounded" role="alert">
                <p class="font-bold">Berhasil</p>
                <p>{!! nl2br(e(session('success'))) !!}</p>
            </div>
        @endif
        @if (session('warning'))
            <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-5 rounded" role="alert">
                <p class="font-bold">Menunggu (Pending)</p>
                <p>{!! nl2br(e(session('warning'))) !!}</p>
            </div>
        @endif
        @if (session('error'))
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-5 rounded" role="alert">
                <p class="font-bold">Gagal</p>
                <p>{!! nl2br(e(session('error'))) !!}</p>
            </div>
        @endif
        @if ($errors->any())
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-5 rounded" role="alert">
                <p class="font-bold">Mohon periksa kembali form Anda:</p>
                <ul class="list-disc ml-5 mt-2">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- FORMULIR --}}
        <form action="{{ route('customer.dana.topup_corporate') }}" method="POST">
            @csrf
            <div class="mb-5">
                <label for="affiliate_id" class="block text-gray-700 font-semibold mb-2">ID Pengguna Pelanggan</label>
                <input type="text" name="affiliate_id" id="affiliate_id" value="{{ old('affiliate_id') }}" 
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-gray-50" 
                    placeholder="Contoh: 12" required>
                <p class="text-sm text-gray-500 mt-1">*Saldo aplikasi milik ID Pengguna ini akan dipotong secara otomatis.</p>
            </div>

            <div class="mb-5">
                <label for="phone" class="block text-gray-700 font-semibold mb-2">Nomor HP DANA Tujuan</label>
                <input type="number" name="phone" id="phone" value="{{ old('phone') }}" 
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-gray-50" 
                    placeholder="Contoh: 081234567890" required>
            </div>

            <div class="mb-6">
                <label for="amount" class="block text-gray-700 font-semibold mb-2">Nominal Top Up</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none">
                        <span class="text-gray-500 font-bold text-lg">Rp</span>
                    </div>
                    <input type="number" name="amount" id="amount" value="{{ old('amount') }}" min="1000" 
                        class="w-full pl-12 px-4 py-3 text-lg font-bold border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-gray-50" 
                        placeholder="10000" required>
                </div>
                <p class="text-sm text-gray-500 mt-1">*Minimal pengiriman DANA Rp 1.000</p>
            </div>

            <div class="flex items-center justify-end border-t border-gray-200 pt-6">
                <button type="submit" 
                    class="w-full sm:w-auto bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-8 rounded-xl shadow-lg transition duration-300 flex justify-center items-center"
                    onclick="return confirm('PENTING: Pastikan Saldo Corporate Sancaka mencukupi. Apakah Anda yakin ingin memproses top up DANA ini?')">
                    <i class="fas fa-paper-plane mr-2"></i> Proses Pencairan DANA
                </button>
            </div>
        </form>
    </div>

    {{-- BAGIAN BAWAH: TABEL RIWAYAT TRANSAKSI & CRUD --}}
    <div class="max-w-6xl mx-auto">
        <h3 class="text-2xl font-bold text-gray-800 mb-4">Riwayat Top Up Corporate</h3>
        
        <div class="bg-white rounded-lg shadow-lg overflow-hidden border border-gray-200">
            <div class="overflow-x-auto">
                <table class="w-full whitespace-nowrap">
                    <thead>
                        <tr class="bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                            <th class="px-6 py-4">Tanggal</th>
                            <th class="px-6 py-4">Ref. Transaksi</th>
                            <th class="px-6 py-4 text-center">ID User</th>
                            <th class="px-6 py-4">No. DANA</th>
                            <th class="px-6 py-4">Nominal</th>
                            <th class="px-6 py-4 text-center">Status</th>
                            <th class="px-6 py-4 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($transactions as $trx)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 text-sm text-gray-700">
                                    {{ \Carbon\Carbon::parse($trx->created_at)->format('d M Y, H:i') }}
                                </td>
                                <td class="px-6 py-4 text-sm font-medium text-gray-900">
                                    {{ $trx->reference_no }}
                                </td>
                                <td class="px-6 py-4 text-sm text-center text-gray-700 font-bold">
                                    {{ $trx->affiliate_id }}
                                </td>
                                <td class="px-6 py-4 text-sm text-blue-600 font-medium">
                                    {{ $trx->phone }}
                                </td>
                                <td class="px-6 py-4 text-sm font-bold text-green-600">
                                    Rp {{ number_format($trx->amount, 0, ',', '.') }}
                                </td>
                                <td class="px-6 py-4 text-center">
                                    @if($trx->status === 'SUCCESS')
                                        <span class="px-3 py-1 inline-flex text-xs font-semibold rounded-full bg-green-100 text-green-800">Sukses</span>
                                    @elseif($trx->status === 'PENDING')
                                        <span class="px-3 py-1 inline-flex text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Pending</span>
                                    @else
                                        <span class="px-3 py-1 inline-flex text-xs font-semibold rounded-full bg-red-100 text-red-800">Gagal</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 flex justify-center space-x-2">
                                    {{-- TOMBOL CEK STATUS --}}
                                    @if($trx->status === 'PENDING')
                                        <form action="{{ route('customer.dana.check_topup_status') }}" method="POST" onsubmit="return confirm('Cek status transaksi ini ke API DANA?');">
                                            @csrf
                                            <input type="hidden" name="reference_no" value="{{ $trx->reference_no }}">
                                            <input type="hidden" name="affiliate_id" value="{{ $trx->affiliate_id }}">
                                            <button type="submit" class="text-white bg-blue-500 hover:bg-blue-600 px-3 py-1.5 rounded-md text-xs font-bold transition-colors shadow-sm">
                                                <i class="fas fa-sync-alt mr-1"></i> Cek
                                            </button>
                                        </form>
                                    @endif
                                    
                                    {{-- TOMBOL HAPUS (CRUD) --}}
                                    <form action="{{ route('customer.dana.destroy_topup', $trx->id) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus riwayat transaksi ini?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-white bg-red-500 hover:bg-red-600 px-3 py-1.5 rounded-md text-xs font-bold transition-colors shadow-sm">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-10 text-center text-gray-500">
                                    <i class="fas fa-box-open text-4xl mb-3 text-gray-300 block"></i>
                                    Belum ada riwayat top up DANA Corporate.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            {{-- Navigasi Pagination --}}
            @if(isset($transactions) && $transactions->hasPages())
                <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
                    {{ $transactions->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection