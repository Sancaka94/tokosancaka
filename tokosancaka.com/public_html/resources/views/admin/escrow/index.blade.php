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
                <select name="status" class="form-select text-sm border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" onchange="this.form.submit()">
                    <option value="">Semua Status Berjalan</option>
                    <option value="ditahan" {{ request('status') == 'ditahan' ? 'selected' : '' }}>Dana Ditahan</option>
                    <option value="dicairkan" {{ request('status') == 'dicairkan' ? 'selected' : '' }}>Sudah Cair</option>
                    <option value="mediasi" {{ request('status') == 'mediasi' ? 'selected' : '' }}>Dalam Mediasi</option>
                </select>
            </form>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative shadow-sm">
            <span class="block sm:inline"><i class="fas fa-check-circle mr-1"></i> {{ session('success') }}</span>
        </div>
    @endif
    @if(session('error'))
        <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative shadow-sm">
            <span class="block sm:inline"><i class="fas fa-exclamation-circle mr-1"></i> {{ session('error') }}</span>
        </div>
    @endif

    <div class="bg-white rounded-lg shadow overflow-hidden border border-gray-200">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Invoice & Dana</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Info Penjual</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Info Pembeli</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Detail Pesanan</th>
                        <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-40">Aksi & Status Order</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">

                    @forelse($escrows as $escrow)
                    @php
                        // Hitung dana bersih yang masuk ke penjual
                        $danaPenjual = $escrow->nominal_ditahan - $escrow->nominal_ongkir;
                    @endphp
                    <tr class="hover:bg-gray-50 transition-colors">

                        <td class="px-6 py-4 whitespace-nowrap align-top">
                            <div class="text-sm font-bold text-blue-600">{{ $escrow->invoice_number }}</div>

                            <div class="bg-gray-50 rounded p-2 mt-2 border border-gray-100 text-[11px] space-y-1 min-w-[150px]">
                                <div class="flex justify-between text-gray-500">
                                    <span>Total Bayar:</span>
                                    <span>Rp {{ number_format($escrow->nominal_ditahan, 0, ',', '.') }}</span>
                                </div>
                                <div class="flex justify-between text-blue-600">
                                    <span>Ongkir (Sancaka):</span>
                                    <span>- Rp {{ number_format($escrow->nominal_ongkir, 0, ',', '.') }}</span>
                                </div>
                                <div class="flex justify-between border-t border-gray-200 pt-1 mt-1">
                                    <span class="font-bold text-gray-700">Hak Penjual:</span>
                                    <span class="font-bold text-green-600">Rp {{ number_format($danaPenjual, 0, ',', '.') }}</span>
                                </div>
                            </div>

                            @if($escrow->status_dana == 'ditahan')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded text-[10px] font-medium bg-yellow-100 text-yellow-800 mt-2 border border-yellow-200">
                                    <i class="fas fa-hand-paper mr-1.5"></i> Dana Ditahan
                                </span>
                            @elseif($escrow->status_dana == 'dicairkan')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded text-[10px] font-medium bg-green-100 text-green-800 mt-2 border border-green-200">
                                    <i class="fas fa-check-double mr-1.5"></i> Telah Cair
                                </span>
                            @elseif($escrow->status_dana == 'mediasi')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded text-[10px] font-medium bg-red-100 text-red-800 mt-2 border border-red-200">
                                    <i class="fas fa-gavel mr-1.5"></i> Mediasi
                                </span>
                            @endif
                        </td>

                        <td class="px-6 py-4 align-top max-w-xs">
                            <div class="text-sm font-semibold text-gray-800"><i class="fas fa-store text-gray-400 mr-1"></i> {{ $escrow->store->name ?? 'Toko Terhapus' }}</div>
                            <div class="text-xs text-gray-500 flex items-center mt-1">
                                <i class="fab fa-whatsapp text-green-500 mr-1.5"></i> {{ $escrow->store->user->no_wa ?? '-' }}
                            </div>
                            <div class="text-[10px] text-gray-500 mt-2 leading-relaxed bg-gray-50 p-1.5 rounded border border-gray-100">
                                <span class="font-semibold text-gray-700"><i class="fas fa-map-marker-alt text-red-500 mr-1"></i> Alamat Toko:</span><br>
                                {{ $escrow->store->address_detail ?? 'Alamat toko tidak tersedia.' }}
                            </div>
                        </td>

                        <td class="px-6 py-4 align-top max-w-xs">
                            <div class="text-sm font-semibold text-gray-800"><i class="fas fa-user text-gray-400 mr-1"></i> {{ $escrow->buyer->nama_lengkap ?? 'Akun Terhapus' }}</div>
                            <div class="text-xs text-gray-500 flex items-center mt-1">
                                <i class="fab fa-whatsapp text-green-500 mr-1.5"></i> {{ $escrow->buyer->no_wa ?? '-' }}
                            </div>
                            <div class="text-[10px] text-gray-500 mt-2 leading-relaxed bg-gray-50 p-1.5 rounded border border-gray-100">
                                <span class="font-semibold text-gray-700"><i class="fas fa-map-marker-alt text-red-500 mr-1"></i> Dikirim Ke:</span><br>
                                {{ $escrow->order->shipping_address ?? 'Alamat pengiriman tidak tersedia.' }}
                            </div>
                        </td>

                        <td class="px-6 py-4 align-top max-w-sm whitespace-normal">
                            @if($escrow->order)
                                <div class="text-xs text-gray-800 font-medium mb-1.5 flex items-center bg-blue-50 py-1 px-2 rounded border border-blue-100 w-max">
                                    <span>Resi:</span>
                                    <span class="text-blue-700 ml-1 font-bold" id="resi-{{$escrow->id}}">{{ $escrow->order->shipping_reference ?? 'Belum ada resi' }}</span>
                                    @if($escrow->order->shipping_reference)
                                        <button onclick="copyResi('{{ $escrow->order->shipping_reference }}')" class="ml-2 text-blue-400 hover:text-blue-800 transition-colors" title="Salin Resi">
                                            <i class="far fa-copy text-sm"></i>
                                        </button>
                                    @endif
                                </div>

                                <div class="bg-gray-50 p-2 rounded border border-gray-200 mb-2">
                                    <ul class="text-[11px] text-gray-600 space-y-1 max-h-20 overflow-y-auto pr-1">
                                        @foreach($escrow->order->items as $item)
                                            <li class="border-b border-gray-100 pb-1 last:border-0 last:pb-0 flex justify-between">
                                                <span class="truncate pr-2">{{ $item->product->name ?? 'Produk' }}</span>
                                                <span class="font-medium whitespace-nowrap">{{ $item->quantity }}x</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @else
                                <span class="text-xs text-red-500 italic">Data pesanan tidak ditemukan.</span>
                            @endif
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap text-center align-top bg-gray-50 border-l border-gray-100">
                            @if($escrow->status_dana === 'ditahan')

                                @php
                                    $statusOrder = strtolower($escrow->order->status ?? '');
                                    $isDelivered = in_array($statusOrder, ['completed', 'selesai', 'sampai', 'delivered']);
                                @endphp

                                <div class="flex flex-col space-y-2">
                                    @if($isDelivered)
                                        <div class="text-[10px] font-bold text-green-600 mb-1 border-b border-green-200 pb-1">
                                            <i class="fas fa-box-open"></i> PAKET DITERIMA
                                        </div>

                                        <form action="{{ route('admin.escrow.cairkan', $escrow->id) }}" method="POST" onsubmit="return confirm('PENTING!\nYakin ingin MENGALIRKAN DANA BERSIH Rp {{ number_format($danaPenjual, 0, ',', '.') }} ke Saldo Penjual?\n\nPastikan pembeli tidak melakukan komplain.');">
                                            @csrf
                                            <button type="submit" class="w-full inline-flex justify-center items-center px-3 py-2 border border-transparent text-xs font-medium rounded shadow-sm text-white bg-green-600 hover:bg-green-700 transition-colors">
                                                <i class="fas fa-money-bill-wave mr-1.5"></i> Cairkan (Rp {{ number_format($danaPenjual/1000, 0) }}k)
                                            </button>
                                        </form>

                                        <form action="{{ route('admin.escrow.mediasi', $escrow->id) }}" method="GET" onsubmit="return confirm('Ubah status menjadi MEDIASI? Dana akan dibekukan sementara untuk investigasi komplain.');">
                                            <button type="submit" class="w-full inline-flex justify-center items-center px-3 py-2 border border-orange-200 text-xs font-medium rounded shadow-sm text-orange-700 bg-orange-50 hover:bg-orange-100 transition-colors">
                                                <i class="fas fa-balance-scale mr-1.5"></i> Mediasi
                                            </button>
                                        </form>
                                    @else
                                        <div class="text-[10px] font-bold text-gray-500 mb-1 border-b border-gray-200 pb-1">
                                            <i class="fas fa-truck text-blue-400"></i> STATUS: {{ strtoupper($statusOrder ?: 'PROSES') }}
                                        </div>

                                        <button disabled type="button" class="w-full inline-flex justify-center items-center px-3 py-2 border border-gray-200 text-xs font-medium rounded shadow-inner text-gray-400 bg-gray-100 cursor-not-allowed">
                                            <i class="fas fa-lock mr-1.5"></i> Cairkan
                                        </button>

                                        <button disabled type="button" class="w-full inline-flex justify-center items-center px-3 py-2 border border-gray-200 text-xs font-medium rounded shadow-inner text-gray-400 bg-gray-50 cursor-not-allowed">
                                            <i class="fas fa-lock mr-1.5"></i> Mediasi
                                        </button>

                                        <p class="text-[9px] text-red-500 font-medium leading-tight mt-1 whitespace-normal text-center">
                                            *Terkunci hingga status pesanan Selesai / Delivered.
                                        </p>
                                    @endif
                                </div>

                            @elseif($escrow->status_dana === 'dicairkan')
                                <div class="text-center p-2">
                                    <i class="fas fa-check-circle text-green-500 text-3xl mb-2 drop-shadow-sm"></i>
                                    <p class="text-[10px] text-gray-500 font-medium uppercase tracking-wider">Berhasil Cair</p>
                                    <p class="text-xs font-bold text-green-600 mt-0.5">Rp {{ number_format($danaPenjual, 0, ',', '.') }}</p>
                                    <p class="text-[9px] text-gray-400 mt-1">{{ $escrow->dicairkan_pada ? $escrow->dicairkan_pada->format('d M Y, H:i') : '-' }}</p>
                                </div>
                            @elseif($escrow->status_dana === 'mediasi')
                                <div class="text-center p-3 bg-red-50 rounded border border-red-200 shadow-inner">
                                    <i class="fas fa-exclamation-triangle text-red-500 text-2xl mb-1"></i>
                                    <p class="text-xs text-red-700 font-bold uppercase tracking-wider">Tahap Mediasi</p>
                                    <p class="text-[9px] text-red-500 mt-1 leading-tight">Dana dibekukan sementara.</p>
                                </div>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-16 text-center text-gray-500 bg-gray-50">
                            <i class="fas fa-shield-alt text-5xl mb-4 text-gray-300"></i>
                            <p class="text-lg font-bold text-gray-600">Belum Ada Data Penahanan Dana</p>
                            <p class="text-sm mt-1 text-gray-400">Data escrow otomatis muncul setelah pembeli menyelesaikan pembayaran.</p>
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

<script>
    function copyResi(text) {
        navigator.clipboard.writeText(text).then(function() {
            // Animasi kecil saat berhasil copy
            alert("Resi disalin: " + text);
        }).catch(function(err) {
            console.error('Gagal menyalin resi: ', err);
            alert("Gagal menyalin resi.");
        });
    }
</script>
@endsection
