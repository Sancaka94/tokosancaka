@extends('layouts.admin')

@section('title', 'Data Escrow / Penahanan Dana')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="mb-6 flex flex-col md:flex-row md:justify-between md:items-center gap-4">
        <div>
            <h2 class="text-2xl font-semibold text-gray-800">Data Escrow (Penahanan Dana)</h2>
            <p class="text-sm text-gray-500 mt-1">Kelola pencairan dana ke penjual atau mediasi komplain pembeli.</p>
        </div>

        <div>
            <form action="{{ route('admin.escrow.index') }}" method="GET" class="flex items-center gap-2">
                <select name="status" class="form-select text-sm border-gray-300 rounded-md shadow-sm focus:ring-red-500 focus:border-red-500" onchange="this.form.submit()">
                    <option value="">Semua Status Berjalan</option>
                    <option value="ditahan" {{ request('status') == 'ditahan' ? 'selected' : '' }}>Dana Ditahan</option>
                    <option value="dicairkan" {{ request('status') == 'dicairkan' ? 'selected' : '' }}>Sudah Cair</option>
                    <option value="mediasi" {{ request('status') == 'mediasi' ? 'selected' : '' }}>Dalam Mediasi</option>
                </select>
            </form>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative shadow-sm" role="alert">
            <span class="block sm:inline"><i class="fas fa-check-circle mr-1"></i> {{ session('success') }}</span>
        </div>
    @endif
    @if(session('error'))
        <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative shadow-sm" role="alert">
            <span class="block sm:inline"><i class="fas fa-exclamation-circle mr-1"></i> {{ session('error') }}</span>
        </div>
    @endif
    @if(session('warning'))
        <div class="mb-4 bg-orange-100 border border-orange-400 text-orange-700 px-4 py-3 rounded relative shadow-sm" role="alert">
            <span class="block sm:inline"><i class="fas fa-exclamation-triangle mr-1"></i> {{ session('warning') }}</span>
        </div>
    @endif

    <div class="bg-white rounded-lg shadow overflow-hidden border border-gray-200">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Invoice & Status
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Info Penjual
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Info Pembeli
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Detail Pesanan
                        </th>
                        <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-32">
                            Aksi
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">

                    @forelse($escrows as $escrow)
                    <tr class="hover:bg-gray-50 transition-colors">

                        <td class="px-6 py-4 whitespace-nowrap align-top">
                            <div class="text-sm font-bold text-blue-600">{{ $escrow->invoice_number }}</div>
                            <div class="text-xs text-gray-500 mt-1">Ditahan: <span class="font-semibold text-gray-800 text-sm">Rp {{ number_format($escrow->nominal_ditahan, 0, ',', '.') }}</span></div>

                            @if($escrow->status_dana == 'ditahan')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 mt-2 border border-yellow-200">
                                    <i class="fas fa-lock text-[10px] mr-1"></i> Ditahan
                                </span>
                            @elseif($escrow->status_dana == 'dicairkan')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 mt-2 border border-green-200">
                                    <i class="fas fa-check text-[10px] mr-1"></i> Telah Cair
                                </span>
                            @elseif($escrow->status_dana == 'mediasi')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 mt-2 border border-red-200">
                                    <i class="fas fa-gavel text-[10px] mr-1"></i> Mediasi
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 mt-2 border border-gray-200">
                                    {{ ucfirst($escrow->status_dana) }}
                                </span>
                            @endif

                            <div class="text-[10px] text-gray-400 mt-2">
                                Dibuat: {{ $escrow->created_at->format('d M Y, H:i') }}
                            </div>
                        </td>

                        <td class="px-6 py-4 align-top max-w-xs">
                            <div class="text-sm font-semibold text-gray-800">{{ $escrow->store->name ?? 'Toko Terhapus' }}</div>
                            <div class="text-xs text-gray-500 flex items-center mt-1">
                                <i class="fab fa-whatsapp text-green-500 mr-1.5"></i> {{ $escrow->store->user->no_wa ?? '-' }}
                            </div>
                            <div class="text-xs text-gray-500 mt-2 whitespace-normal line-clamp-3" title="{{ $escrow->store->address_detail ?? '' }}">
                                <span class="font-semibold">Alamat:</span><br>
                                {{ $escrow->store->address_detail ?? 'Alamat tidak ditemukan' }}
                            </div>
                        </td>

                        <td class="px-6 py-4 align-top max-w-xs">
                            <div class="text-sm font-semibold text-gray-800">{{ $escrow->buyer->nama_lengkap ?? 'Akun Terhapus' }}</div>
                            <div class="text-xs text-gray-500 flex items-center mt-1">
                                <i class="fab fa-whatsapp text-green-500 mr-1.5"></i> {{ $escrow->buyer->no_wa ?? '-' }}
                            </div>
                            <div class="text-xs text-gray-500 mt-2 whitespace-normal line-clamp-3" title="{{ $escrow->order->shipping_address ?? '' }}">
                                <span class="font-semibold">Dikirim ke:</span><br>
                                {{ $escrow->order->shipping_address ?? 'Alamat pengiriman kosong' }}
                            </div>
                        </td>

                        <td class="px-6 py-4 align-top max-w-sm whitespace-normal">
                            @if($escrow->order && $escrow->order->items)
                                <ul class="text-xs text-gray-600 space-y-2 mb-2 max-h-24 overflow-y-auto pr-1">
                                    @php $totalWeight = 0; @endphp
                                    @foreach($escrow->order->items as $item)
                                        @php
                                            $beratProduk = $item->product->weight ?? 1000;
                                            $totalWeight += ($beratProduk * $item->quantity);
                                        @endphp
                                        <li class="border-b border-gray-100 pb-1 last:border-0 last:pb-0">
                                            <span class="font-semibold text-gray-800">
                                                {{ $item->product->name ?? 'Produk Terhapus' }}
                                                @if($item->variant)
                                                    <span class="text-gray-500">({{ str_replace(';', ', ', $item->variant->combination_string) }})</span>
                                                @endif
                                            </span>
                                            <div class="mt-0.5 flex justify-between">
                                                <span>{{ $item->quantity }}x Rp {{ number_format($item->price, 0, ',', '.') }}</span>
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>

                                <div class="bg-gray-50 p-2.5 rounded border border-gray-200 mt-2">
                                    <div class="flex justify-between text-xs text-gray-500 mb-1">
                                        <span class="font-semibold">Total Berat:</span>
                                        <span>{{ number_format($totalWeight, 0, ',', '.') }} gram</span>
                                    </div>
                                    <div class="flex justify-between text-xs text-gray-500">
                                        <span class="font-semibold">Ongkos Kirim:</span>
                                        <span class="text-gray-800 font-medium">Rp {{ number_format($escrow->nominal_ongkir, 0, ',', '.') }}</span>
                                    </div>
                                </div>
                            @else
                                <span class="text-xs text-red-500 italic">Data pesanan tidak ditemukan.</span>
                            @endif
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap text-center align-top">
                            @if($escrow->status_dana === 'ditahan')
                                <div class="flex flex-col space-y-2">
                                    <form action="{{ route('admin.escrow.cairkan', $escrow->id) }}" method="POST" onsubmit="return confirm('PENTING!\nYakin ingin MENGALIRKAN DANA Rp {{ number_format($escrow->nominal_ditahan, 0, ',', '.') }} ke Toko {{ $escrow->store->name ?? '' }}?\n\nPastikan barang sudah diterima pembeli tanpa komplain.');">
                                        @csrf
                                        <button type="submit" class="w-full inline-flex justify-center items-center px-3 py-2 border border-transparent text-xs leading-4 font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 shadow-sm transition-colors">
                                            <i class="fas fa-hand-holding-usd mr-1.5"></i> Cairkan
                                        </button>
                                    </form>

                                    <form action="{{ route('admin.escrow.mediasi', $escrow->id) }}" method="GET" onsubmit="return confirm('Ubah status menjadi MEDIASI? Dana akan dibekukan sementara hingga masalah dengan pembeli selesai.');">
                                        <button type="submit" class="w-full inline-flex justify-center items-center px-3 py-2 border border-gray-300 text-xs leading-4 font-medium rounded-md text-orange-700 bg-orange-50 hover:bg-orange-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500 shadow-sm transition-colors">
                                            <i class="fas fa-balance-scale mr-1.5"></i> Mediasi
                                        </button>
                                    </form>
                                </div>
                            @elseif($escrow->status_dana === 'dicairkan')
                                <div class="text-center">
                                    <i class="fas fa-check-circle text-green-500 text-2xl mb-1"></i>
                                    <p class="text-[10px] text-gray-500 font-medium">Cair Pada:</p>
                                    <p class="text-[10px] text-gray-800">{{ $escrow->dicairkan_pada ? $escrow->dicairkan_pada->format('d/m/Y H:i') : '-' }}</p>
                                </div>
                            @elseif($escrow->status_dana === 'mediasi')
                                <div class="text-center p-2 bg-red-50 rounded border border-red-100">
                                    <i class="fas fa-exclamation-triangle text-red-500 text-xl mb-1"></i>
                                    <p class="text-[10px] text-red-700 font-medium leading-tight">Proses<br>Mediasi</p>
                                </div>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-gray-500 bg-gray-50">
                            <i class="fas fa-shield-alt text-4xl mb-3 text-gray-300"></i>
                            <p class="text-lg font-medium text-gray-600">Belum ada data Escrow</p>
                            <p class="text-sm mt-1">Data penahanan dana akan muncul setelah ada pesanan yang Lunas.</p>
                        </td>
                    </tr>
                    @endforelse

                </tbody>
            </table>
        </div>

        @if($escrows->hasPages())
        <div class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
            {{ $escrows->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
