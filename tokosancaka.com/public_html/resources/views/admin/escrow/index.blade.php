@extends('layouts.admin')

@section('title', 'Data Escrow / Penahanan Dana')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-semibold text-gray-800">Data Escrow (Penahanan Dana)</h2>
            <p class="text-sm text-gray-500 mt-1">Kelola pencairan dana ke penjual atau mediasi komplain pembeli.</p>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
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
                            Detail Produk & Ongkir
                        </th>
                        <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Aksi
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">

                    @forelse($orders as $order)
                    <tr class="hover:bg-gray-50">

                        <td class="px-6 py-4 whitespace-nowrap align-top">
                            <div class="text-sm font-bold text-blue-600">{{ $order->invoice_number }}</div>
                            <div class="text-xs text-gray-500 mt-1">Total: <span class="font-semibold text-gray-800">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</span></div>
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800 mt-2">
                                {{ strtoupper($order->status) }}
                            </span>
                        </td>

                        <td class="px-6 py-4 align-top max-w-xs">
                            <div class="text-sm font-semibold text-gray-800">{{ $order->store->name ?? 'Toko Tidak Diketahui' }}</div>
                            <div class="text-xs text-gray-500 flex items-center mt-1">
                                <i class="fab fa-whatsapp text-green-500 mr-1"></i> {{ $order->store->user->no_wa ?? '-' }}
                            </div>
                            <div class="text-xs text-gray-500 mt-2 whitespace-normal">
                                <span class="font-semibold">Alamat:</span><br>
                                {{ $order->store->address_detail ?? 'Alamat toko belum lengkap' }}
                            </div>
                        </td>

                        <td class="px-6 py-4 align-top max-w-xs">
                            <div class="text-sm font-semibold text-gray-800">{{ $order->user->nama_lengkap ?? 'Guest / Tidak Diketahui' }}</div>
                            <div class="text-xs text-gray-500 flex items-center mt-1">
                                <i class="fab fa-whatsapp text-green-500 mr-1"></i> {{ $order->user->no_wa ?? '-' }}
                            </div>
                            <div class="text-xs text-gray-500 mt-2 whitespace-normal">
                                <span class="font-semibold">Dikirim ke:</span><br>
                                {{ $order->shipping_address ?? 'Alamat pengiriman kosong' }}
                            </div>
                        </td>

                        <td class="px-6 py-4 align-top max-w-sm whitespace-normal">
                            <ul class="text-xs text-gray-600 space-y-2 mb-2">
                                @php $totalWeight = 0; @endphp
                                @foreach($order->items as $item)
                                    @php
                                        $beratProduk = $item->product->weight ?? 1000;
                                        $totalWeight += ($beratProduk * $item->quantity);
                                    @endphp
                                    <li class="border-b border-gray-100 pb-1">
                                        <span class="font-semibold text-gray-800">
                                            {{ $item->product->name ?? 'Produk Terhapus' }}
                                            @if($item->variant)
                                                ({{ str_replace(';', ', ', $item->variant->combination_string) }})
                                            @endif
                                        </span>
                                        <br> {{ $item->quantity }} pcs x Rp {{ number_format($item->price, 0, ',', '.') }}
                                        <div class="text-[10px] text-gray-400">
                                            Vol: {{ $item->product->length ?? 5 }}x{{ $item->product->width ?? 5 }}x{{ $item->product->height ?? 5 }} cm
                                        </div>
                                    </li>
                                @endforeach
                            </ul>

                            <div class="bg-gray-50 p-2 rounded border border-gray-100 mt-2">
                                <div class="text-xs text-gray-500">
                                    <span class="font-semibold">Total Berat:</span> {{ number_format($totalWeight, 0, ',', '.') }} gram
                                </div>
                                <div class="text-xs text-gray-500">
                                    <span class="font-semibold">Metode:</span> {{ strtoupper($order->shipping_method ?? '-') }}
                                </div>
                                <div class="text-xs text-gray-800 mt-1 border-t border-gray-200 pt-1">
                                    <span class="font-semibold">Ongkos Kirim:</span> Rp {{ number_format($order->shipping_cost, 0, ',', '.') }}
                                </div>
                            </div>
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap text-center align-top">
                            <div class="flex flex-col space-y-2">
                                <form action="{{ route('admin.escrow.cairkan', $order->id) }}" method="POST" onsubmit="return confirm('Yakin ingin mencairkan dana ini ke penjual? Pastikan barang sudah diterima pembeli.');">
                                    @csrf
                                    @method('POST')
                                    <button type="submit" class="w-full inline-flex justify-center items-center px-3 py-2 border border-transparent text-xs leading-4 font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 shadow-sm transition-colors">
                                        <i class="fas fa-money-bill-wave mr-1.5"></i> Cairkan Dana
                                    </button>
                                </form>

                                <a href="{{ route('admin.escrow.mediasi', $order->id) }}" class="w-full inline-flex justify-center items-center px-3 py-2 border border-transparent text-xs leading-4 font-medium rounded-md text-white bg-orange-500 hover:bg-orange-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500 shadow-sm transition-colors">
                                    <i class="fas fa-balance-scale mr-1.5"></i> Mediasi
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                            <i class="fas fa-box-open text-4xl mb-3 text-gray-300"></i>
                            <p class="text-lg font-medium">Belum ada dana yang tertahan</p>
                            <p class="text-sm">Semua transaksi Escrow akan muncul di sini.</p>
                        </td>
                    </tr>
                    @endforelse

                </tbody>
            </table>
        </div>

        @if($orders->hasPages())
        <div class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
            {{ $orders->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
