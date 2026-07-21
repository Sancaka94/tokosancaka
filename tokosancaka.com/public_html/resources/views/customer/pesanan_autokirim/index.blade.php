@extends('layouts.admin')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-8 mb-12">

    <div class="flex justify-between items-center mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Manajemen Pesanan Autokirim</h2>
            <p class="text-sm text-gray-500 mt-1">Daftar seluruh transaksi resi otomatis.</p>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Order ID & Waktu</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Rute Pengiriman</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Kurir & Barang</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Total Biaya</th>
                        <th class="px-6 py-4 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Status / AWB</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">
                    @forelse($pesanan as $p)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="font-bold text-sm text-gray-900">{{ $p->order_id }}</div>
                            <div class="text-xs text-gray-500">{{ $p->created_at->format('d M Y, H:i') }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm">
                                <p><span class="font-semibold text-gray-700">Dari:</span> {{ $p->pengirim_nama }}</p>
                                <p><span class="font-semibold text-gray-700">Ke:</span> {{ $p->penerima_nama }}</p>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex px-2 py-1 text-xs font-bold bg-blue-100 text-blue-800 rounded-md">
                                {{ $p->kurir }} - {{ $p->layanan }}
                            </span>
                            <div class="text-xs text-gray-500 mt-1">{{ $p->deskripsi_barang }} ({{ $p->berat_gram }}g)</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap font-bold text-gray-800 text-sm">
                            Rp {{ number_format($p->ongkir, 0, ',', '.') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            @if($p->status == 'pending')
                                <span class="px-3 py-1 text-xs font-semibold bg-yellow-100 text-yellow-800 rounded-full">Pending</span>
                            @else
                                <span class="px-3 py-1 text-xs font-semibold bg-green-100 text-green-800 rounded-full">{{ $p->status }}</span>
                            @endif
                            <div class="text-xs font-mono text-gray-600 mt-2">{{ $p->awb_number ?? 'Belum ada Resi' }}</div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-10 text-center text-gray-500 text-sm">Belum ada data pesanan Autokirim.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-6 py-4 border-t border-gray-200">
            {{ $pesanan->links() }}
        </div>
    </div>
</div>
@endsection
