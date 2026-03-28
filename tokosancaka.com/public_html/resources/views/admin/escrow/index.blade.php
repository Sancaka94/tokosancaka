@extends('layouts.admin')

@section('title', 'Data Escrow / Penahanan Dana')

@section('content')
<div class="container mx-auto px-4 py-6">

    <div class="mb-6 flex justify-between items-end">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Data Penahanan Dana (Marketplace)</h2>
            <p class="text-sm text-gray-500 mt-1">Pantau pergerakan dana, mediasi komplain, dan cairkan saldo ke penjual.</p>
        </div>
        <a href="{{ route('admin.escrow.history') }}" class="bg-blue-50 text-blue-600 border border-blue-200 hover:bg-blue-600 hover:text-white transition-colors px-4 py-2 rounded-lg text-sm font-semibold shadow-sm flex items-center">
            <i class="fas fa-history mr-2"></i> Riwayat Pencairan Dana Marketplace
        </a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 flex items-center hover:shadow-md transition-shadow">
            <div class="p-3 rounded-full bg-blue-50 text-blue-500 mr-4">
                <i class="fas fa-truck-fast text-xl"></i>
            </div>
            <div>
                <p class="text-xs text-gray-500 font-semibold uppercase tracking-wider mb-0.5">Sedang Dikirim</p>
                <h3 class="text-2xl font-bold text-gray-800">{{ $countDikirim }}</h3>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 flex items-center hover:shadow-md transition-shadow">
            <div class="p-3 rounded-full bg-green-50 text-green-500 mr-4">
                <i class="fas fa-box-open text-xl"></i>
            </div>
            <div>
                <p class="text-xs text-gray-500 font-semibold uppercase tracking-wider mb-0.5">Pesanan Selesai</p>
                <h3 class="text-2xl font-bold text-gray-800">{{ $countSelesai }}</h3>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 flex items-center hover:shadow-md transition-shadow">
            <div class="p-3 rounded-full bg-orange-50 text-orange-500 mr-4">
                <i class="fas fa-gavel text-xl"></i>
            </div>
            <div>
                <p class="text-xs text-gray-500 font-semibold uppercase tracking-wider mb-0.5">Bermasalah (Mediasi)</p>
                <h3 class="text-2xl font-bold text-gray-800">{{ $countBermasalah }}</h3>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 flex items-center hover:shadow-md transition-shadow">
            <div class="p-3 rounded-full bg-red-50 text-red-500 mr-4">
                <i class="fas fa-times-circle text-xl"></i>
            </div>
            <div>
                <p class="text-xs text-gray-500 font-semibold uppercase tracking-wider mb-0.5">Gagal / Dibatalkan</p>
                <h3 class="text-2xl font-bold text-gray-800">{{ $countBatal }}</h3>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 mb-6">
        <form action="{{ route('admin.escrow.index') }}" method="GET" class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">

            <div class="flex flex-wrap items-center gap-2">
                @php $currentStatus = request('order_status', 'all'); @endphp
                <button type="submit" name="order_status" value="all" class="px-4 py-2 rounded-full text-xs font-semibold transition-colors {{ $currentStatus == 'all' ? 'bg-blue-600 text-white shadow-md shadow-blue-200' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">Semua</button>
                <button type="submit" name="order_status" value="dikirim" class="px-4 py-2 rounded-full text-xs font-semibold transition-colors {{ $currentStatus == 'dikirim' ? 'bg-blue-600 text-white shadow-md shadow-blue-200' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">Dikirim</button>
                <button type="submit" name="order_status" value="selesai" class="px-4 py-2 rounded-full text-xs font-semibold transition-colors {{ $currentStatus == 'selesai' ? 'bg-blue-600 text-white shadow-md shadow-blue-200' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">Selesai</button>
                <button type="submit" name="order_status" value="bermasalah" class="px-4 py-2 rounded-full text-xs font-semibold transition-colors {{ $currentStatus == 'bermasalah' ? 'bg-blue-600 text-white shadow-md shadow-blue-200' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">Mediasi</button>
                <button type="submit" name="order_status" value="batal" class="px-4 py-2 rounded-full text-xs font-semibold transition-colors {{ $currentStatus == 'batal' ? 'bg-blue-600 text-white shadow-md shadow-blue-200' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">Batal</button>
            </div>

            <div class="flex flex-col sm:flex-row items-center gap-2">
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                        <i class="far fa-calendar-alt text-gray-400 text-sm"></i>
                    </div>
                    <input type="date" name="start_date" value="{{ request('start_date') }}" class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full pl-9 p-2.5" title="Dari Tanggal">
                </div>
                <span class="text-gray-400 text-xs font-semibold">s/d</span>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                        <i class="far fa-calendar-check text-gray-400 text-sm"></i>
                    </div>
                    <input type="date" name="end_date" value="{{ request('end_date') }}" class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full pl-9 p-2.5" title="Sampai Tanggal">
                </div>
                <button type="submit" class="bg-gray-800 text-white px-4 py-2.5 rounded-lg text-xs font-semibold hover:bg-gray-900 transition-colors shadow-sm flex items-center">
                    <i class="fas fa-search mr-2"></i> Filter
                </button>
                @if(request()->has('start_date') || request()->has('order_status'))
                    <a href="{{ route('admin.escrow.index') }}" class="bg-red-50 text-red-600 border border-red-200 px-3 py-2.5 rounded-lg text-xs font-semibold hover:bg-red-100 transition-colors shadow-sm" title="Reset Filter">
                        <i class="fas fa-undo"></i>
                    </a>
                @endif
            </div>
        </form>
    </div>

    @if(session('success'))
        <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg relative shadow-sm text-sm">
            <span class="block sm:inline"><i class="fas fa-check-circle mr-1"></i> {{ session('success') }}</span>
        </div>
    @endif
    @if(session('error'))
        <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative shadow-sm text-sm">
            <span class="block sm:inline"><i class="fas fa-exclamation-circle mr-1"></i> {{ session('error') }}</span>
        </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-12">#</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Invoice & Dana</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Info Penjual</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Info Pembeli</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-72">Detail Pesanan & Kirim</th>
                        <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-40">Aksi & Status</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">

                    @forelse($escrows as $escrow)
                    @php
                        $danaPenjual = $escrow->nominal_ditahan - $escrow->nominal_ongkir;
                    @endphp
                    <tr class="hover:bg-blue-50/30 transition-colors">

                        <td class="px-4 py-4 whitespace-nowrap align-top text-center text-sm font-medium text-gray-400">
                            {{ $loop->iteration + $escrows->firstItem() - 1 }}
                        </td>

                        <td class="px-4 py-4 whitespace-nowrap align-top">
                            <div class="text-sm font-bold text-blue-600">{{ $escrow->invoice_number }}</div>
                            <div class="bg-gray-50 rounded p-2 mt-2 border border-gray-100 text-[11px] space-y-1 min-w-[140px]">
                                <div class="flex justify-between text-gray-500">
                                    <span>Total:</span>
                                    <span>Rp {{ number_format($escrow->nominal_ditahan, 0, ',', '.') }}</span>
                                </div>
                                <div class="flex justify-between text-blue-600">
                                    <span>Ongkir:</span>
                                    <span>- Rp {{ number_format($escrow->nominal_ongkir, 0, ',', '.') }}</span>
                                </div>
                                <div class="flex justify-between border-t border-gray-200 pt-1 mt-1">
                                    <span class="font-bold text-gray-700">Penjual:</span>
                                    <span class="font-bold text-green-600">Rp {{ number_format($danaPenjual, 0, ',', '.') }}</span>
                                </div>
                            </div>
                        </td>

                        <td class="px-4 py-4 align-top max-w-[180px]">
                            <div class="text-sm font-semibold text-gray-800"><i class="fas fa-store text-gray-400 mr-1"></i> {{ $escrow->store->name ?? 'Toko Terhapus' }}</div>
                            <div class="text-xs text-gray-500 flex items-center mt-1">
                                <i class="fab fa-whatsapp text-green-500 mr-1.5"></i> {{ $escrow->store->user->no_wa ?? '-' }}
                            </div>
                            <div class="text-[10px] text-gray-500 mt-2 leading-relaxed bg-gray-50 p-1.5 rounded border border-gray-100 truncate hover:whitespace-normal" title="{{ $escrow->store->address_detail ?? 'Alamat toko tidak tersedia.' }}">
                                <span class="font-semibold text-gray-700">Alamat:</span> {{ $escrow->store->address_detail ?? '-' }}
                            </div>
                        </td>

                        <td class="px-4 py-4 align-top max-w-[180px]">
                            <div class="text-sm font-semibold text-gray-800"><i class="fas fa-user text-gray-400 mr-1"></i> {{ $escrow->buyer->nama_lengkap ?? 'Akun Terhapus' }}</div>
                            <div class="text-xs text-gray-500 flex items-center mt-1">
                                <i class="fab fa-whatsapp text-green-500 mr-1.5"></i> {{ $escrow->buyer->no_wa ?? '-' }}
                            </div>
                            <div class="text-[10px] text-gray-500 mt-2 leading-relaxed bg-gray-50 p-1.5 rounded border border-gray-100 truncate hover:whitespace-normal" title="{{ $escrow->order->shipping_address ?? 'Alamat pengiriman tidak tersedia.' }}">
                                <span class="font-semibold text-gray-700">Dikirim:</span> {{ $escrow->order->shipping_address ?? '-' }}
                            </div>
                        </td>

                        <td class="px-4 py-4 align-top whitespace-normal">
                            @if($escrow->order)
                                <div class="bg-blue-50/50 p-2 rounded border border-blue-100 mb-2">
                                    <div class="flex items-center gap-2 mb-1.5">
                                        @php
                                            $ship = \App\Helpers\ShippingHelper::parseShippingMethod($escrow->order->shipping_courier ?? $escrow->order->expedition ?? '');
                                            $courierName = $ship['courier_name'] ?? $escrow->order->shipping_courier ?? 'N/A';
                                            $serviceName = $ship['service_name'] ?? $escrow->order->shipping_service ?? 'N/A';
                                            $logoUrl     = $ship['logo_url'] ?? null;
                                        @endphp

                                        @if($logoUrl)
                                            <img src="{{ $logoUrl }}" class="h-6 w-auto object-contain rounded bg-white px-1 border border-gray-100 shadow-sm" alt="{{ $courierName }}">
                                        @else
                                            <div class="h-6 px-2 flex items-center justify-center bg-gray-200 text-gray-500 font-bold text-[10px] rounded border border-gray-300">
                                                {{ substr($courierName, 0, 4) }}
                                            </div>
                                        @endif

                                        <div class="leading-tight">
                                            <div class="text-[11px] font-bold text-gray-800 uppercase">{{ $courierName }}</div>
                                            <div class="text-[9px] text-gray-500 uppercase">{{ $serviceName }}</div>
                                        </div>
                                    </div>

                                    <div class="text-xs text-gray-800 font-medium flex items-center mt-1 pt-1 border-t border-blue-100/60 w-max">
                                        <span class="text-gray-500 mr-1 text-[10px]">Resi:</span>
                                        <span class="text-blue-700 font-bold tracking-wider" id="resi-{{$escrow->id}}">{{ $escrow->order->shipping_reference ?? 'Belum ada resi' }}</span>
                                        @if($escrow->order->shipping_reference)
                                            <button onclick="copyResi('{{ $escrow->order->shipping_reference }}')" class="ml-2 text-blue-400 hover:text-blue-800 transition-colors" title="Salin Resi">
                                                <i class="far fa-copy"></i>
                                            </button>
                                        @endif
                                    </div>
                                </div>


                                <div class="bg-gray-50 p-2 rounded border border-gray-200">
                                    <ul class="space-y-2 max-h-32 overflow-y-auto pr-1 custom-scrollbar">
                                    @foreach($escrow->order->items as $item)
                                            <li class="border-b border-gray-100 pb-2 last:border-0 last:pb-0 flex items-start gap-2">

                                                {{-- LOGIKA GAMBAR BARU --}}
                                                <div class="w-10 h-10 flex-shrink-0 bg-gray-200 rounded overflow-hidden border border-gray-200 relative shadow-sm">
                                                    @if($item->product && !empty($item->product->image_url))
                                                        @php
                                                            $rawPath = $item->product->image_url;
                                                            $cleanPath = str_replace('public/', '', $rawPath);
                                                            $imageUrl = asset('public/storage/' . $cleanPath);
                                                        @endphp
                                                        <img src="{{ $imageUrl }}"
                                                             alt="{{ $item->product->name }}"
                                                             class="w-full h-full object-cover"
                                                             onerror="this.onerror=null; this.src='https://placehold.co/40x40/f3f4f6/a1a1aa?text=Img';">
                                                    @else
                                                        <div class="w-full h-full flex flex-col items-center justify-center text-gray-400 bg-gray-100">
                                                            <i class="fas fa-image text-xs"></i>
                                                        </div>
                                                    @endif
                                                </div>

                                                <div class="flex-1 min-w-0 leading-tight">
                                                    <div class="truncate text-gray-800 font-semibold text-[11px] mb-0.5" title="{{ $item->product->name ?? 'Produk' }}">{{ $item->product->name ?? 'Produk' }}</div>
                                                    @if($item->variant)
                                                        <div class="text-[9px] text-gray-500 truncate">{{ str_replace(';', ', ', $item->variant->combination_string) }}</div>
                                                    @endif
                                                    <div class="flex justify-between items-center mt-1">
                                                        <span class="text-[10px] text-gray-500 font-medium">{{ $item->quantity }}x</span>
                                                        <span class="text-[10px] font-bold text-gray-700">Rp {{ number_format($item->price, 0, ',', '.') }}</span>
                                                    </div>
                                                </div>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @else
                                <span class="text-xs text-red-500 italic">Pesanan tidak ditemukan.</span>
                            @endif
                        </td>

                        <td class="px-4 py-4 whitespace-nowrap text-center align-top bg-gray-50/50 border-l border-gray-100">
                            @if($escrow->status_dana === 'ditahan')

                                @php
                                    $statusOrder = strtolower($escrow->order->status ?? '');
                                    $isDelivered = in_array($statusOrder, ['completed', 'selesai', 'sampai', 'delivered']);
                                @endphp

                                <div class="flex flex-col space-y-2">
                                    @if($isDelivered)
                                        <div class="text-[10px] font-bold text-green-600 mb-1 border-b border-green-200 pb-1"><i class="fas fa-box-open"></i> DITERIMA</div>
                                        <form action="{{ route('admin.escrow.cairkan', $escrow->id) }}" method="POST" onsubmit="return confirm('PENTING!\nYakin cairkan DANA BERSIH Rp {{ number_format($danaPenjual, 0, ',', '.') }} ke Penjual?');">
                                            @csrf
                                            <button type="submit" class="w-full inline-flex justify-center items-center px-2 py-2 border border-transparent text-[11px] font-medium rounded shadow-sm text-white bg-green-600 hover:bg-green-700 transition-colors">
                                                <i class="fas fa-money-bill-wave mr-1"></i> Cairkan ({{ number_format($danaPenjual/1000, 0) }}k)
                                            </button>
                                        </form>
                                        <form action="{{ route('admin.escrow.mediasi', $escrow->id) }}" method="GET" onsubmit="return confirm('Ubah status jadi MEDIASI?');">
                                            <button type="submit" class="w-full inline-flex justify-center items-center px-2 py-2 border border-orange-200 text-[11px] font-medium rounded shadow-sm text-orange-700 bg-orange-50 hover:bg-orange-100 transition-colors">
                                                <i class="fas fa-balance-scale mr-1"></i> Mediasi
                                            </button>
                                        </form>
                                    @else
                                        <div class="text-[10px] font-bold text-gray-500 mb-1 border-b border-gray-200 pb-1"><i class="fas fa-truck text-blue-400"></i> {{ strtoupper($statusOrder ?: 'PROSES') }}</div>
                                        <button disabled type="button" class="w-full inline-flex justify-center items-center px-2 py-2 border border-gray-200 text-[11px] font-medium rounded shadow-inner text-gray-400 bg-gray-100 cursor-not-allowed">
                                            <i class="fas fa-lock mr-1"></i> Cairkan
                                        </button>
                                        <button disabled type="button" class="w-full inline-flex justify-center items-center px-2 py-2 border border-gray-200 text-[11px] font-medium rounded shadow-inner text-gray-400 bg-gray-50 cursor-not-allowed">
                                            <i class="fas fa-lock mr-1"></i> Mediasi
                                        </button>
                                    @endif
                                </div>
                            @elseif($escrow->status_dana === 'dicairkan')
                                <div class="text-center p-2">
                                    <i class="fas fa-check-circle text-green-500 text-3xl mb-2 drop-shadow-sm"></i>
                                    <p class="text-[10px] text-gray-500 font-medium uppercase tracking-wider">Cair</p>
                                    <p class="text-[10px] text-gray-400 mt-1">{{ $escrow->dicairkan_pada ? $escrow->dicairkan_pada->format('d M Y') : '-' }}</p>
                                </div>
                            @elseif($escrow->status_dana === 'mediasi')
                                <div class="text-center p-2 bg-red-50 rounded border border-red-200 shadow-inner">
                                    <i class="fas fa-exclamation-triangle text-red-500 text-xl mb-1"></i>
                                    <p class="text-[10px] text-red-700 font-bold uppercase tracking-wider mb-2">Mediasi</p>
                                    <button type="button" onclick="openKomplainModal('{{ $escrow->invoice_number }}', '{{ addslashes($escrow->store->name ?? 'Toko') }}')" class="w-full bg-red-600 hover:bg-red-700 text-white text-[10px] font-bold py-1.5 rounded transition shadow-sm flex items-center justify-center">
                                        <i class="fas fa-comments mr-1"></i> Buka Chat
                                    </button>
                                </div>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-16 text-center text-gray-500 bg-gray-50">
                            <i class="fas fa-file-invoice-dollar text-5xl mb-4 text-gray-300"></i>
                            <p class="text-lg font-bold text-gray-600">Tidak ada data ditemukan</p>
                            <p class="text-sm mt-1 text-gray-400">Silakan ubah filter tanggal atau status pencarian.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($escrows->hasPages())
        <div class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
            {{ $escrows->links('pagination::tailwind') }}
        </div>
        @endif
    </div>
</div>

<div id="komplainModal" class="fixed inset-0 z-[99] hidden bg-gray-900 bg-opacity-50 flex items-center justify-center p-4 transition-opacity duration-300">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg overflow-hidden flex flex-col h-[550px] transform transition-all scale-95 opacity-0" id="komplainModalContent">

        <div class="bg-gray-800 px-4 py-3 flex justify-between items-center text-white shadow-md z-10">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center text-white border-2 border-white">
                    <i class="fas fa-user-shield text-sm"></i>
                </div>
                <div>
                    <h3 class="font-bold text-sm leading-tight">Pantau Pusat Resolusi</h3>
                    <p class="text-[10px] text-gray-300" id="komplainStoreName">Nama Toko</p>
                </div>
            </div>
            <button onclick="closeKomplainModal()" class="text-white hover:text-gray-300 bg-gray-700 hover:bg-gray-600 rounded-full w-8 h-8 flex items-center justify-center transition">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div id="chatScrollArea" class="flex-1 bg-[url('https://www.transparenttextures.com/patterns/cubes.png')] bg-gray-100 p-4 overflow-y-auto flex flex-col gap-4">
            <div class="text-center mb-2">
                <span class="bg-white border border-gray-200 text-gray-600 text-[10px] px-3 py-1 rounded-full font-bold shadow-sm">
                    Invoice: <span id="komplainInvoice" class="text-blue-600"></span>
                </span>
                <p class="text-[10px] text-blue-500 mt-3 font-medium bg-blue-50 p-2 rounded-lg border border-blue-100 inline-block">
                    <i class="fas fa-info-circle mr-1"></i> Anda memantau chat ini sebagai Admin (Wasit).
                </p>
            </div>
            <div id="chatBoxContent" class="flex flex-col gap-1 mt-2"></div>
        </div>

        <form onsubmit="sendChatMsg(event)" class="p-3 border-t border-gray-200 bg-white flex items-center gap-2">
            @csrf
            <input type="text" id="chatMessageInput" name="message" placeholder="Balas sebagai Admin (Wasit)..." class="flex-1 border-gray-300 rounded-full text-sm focus:ring-blue-500 focus:border-blue-500 px-4 py-2 bg-gray-50 shadow-inner" required autocomplete="off">
            <button type="submit" class="bg-blue-600 text-white w-10 h-10 rounded-full hover:bg-blue-700 transition flex items-center justify-center shadow-md">
                <i class="fas fa-paper-plane"></i>
            </button>
        </form>
    </div>
</div>

<style>
    /* Custom Scrollbar kecil untuk list produk */
    .custom-scrollbar::-webkit-scrollbar { width: 4px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 4px; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
</style>

<script>
    let currentInvoice = '';

    function openKomplainModal(invoice, storeName) {
        currentInvoice = invoice;
        document.getElementById('komplainInvoice').innerText = invoice;
        document.getElementById('komplainStoreName').innerText = storeName;
        const modal = document.getElementById('komplainModal');
        const content = document.getElementById('komplainModalContent');
        modal.classList.remove('hidden');
        setTimeout(() => {
            content.classList.remove('scale-95', 'opacity-0');
            content.classList.add('scale-100', 'opacity-100');
            loadChats();
        }, 50);
    }

    function closeKomplainModal() {
        const modal = document.getElementById('komplainModal');
        const content = document.getElementById('komplainModalContent');
        content.classList.remove('scale-100', 'opacity-100');
        content.classList.add('scale-95', 'opacity-0');
        setTimeout(() => {
            modal.classList.add('hidden');
            document.getElementById('chatBoxContent').innerHTML = '';
        }, 300);
    }

    function loadChats() {
        const chatBox = document.getElementById('chatBoxContent');
        chatBox.innerHTML = '<div class="text-center text-xs text-gray-400 my-4"><i class="fas fa-spinner fa-spin"></i> Memuat pesan...</div>';

        // MENGGUNAKAN {{ url() }} AGAR PATH AJAX TIDAK ERROR (NOT FOUND)
        fetch(`{{ url('admin/escrow/chat') }}/${currentInvoice}`)
            .then(res => res.json())
            .then(data => {
                chatBox.innerHTML = '';
                if(data.chats && data.chats.length > 0) {
                    data.chats.forEach(chat => appendChatHTML(chat));
                } else {
                    chatBox.innerHTML = '<div class="text-center text-xs text-gray-400 my-4">Belum ada obrolan.</div>';
                }
                scrollToBottom();
            })
            .catch(err => {
                chatBox.innerHTML = '<div class="text-center text-xs text-red-500 my-4">Gagal memuat pesan.</div>';
            });
    }

    function sendChatMsg(e) {
        e.preventDefault();
        const input = document.getElementById('chatMessageInput');
        const msg = input.value;
        if(msg.trim() === '') return;

        input.value = '';
        input.disabled = true;

        fetch(`{{ route('admin.escrow.send_chat') }}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ invoice_number: currentInvoice, message: msg })
        })
        .then(res => res.json())
        .then(data => {
            input.disabled = false;
            input.focus();
            if(data.success) {
                appendChatHTML(data.chat);
                scrollToBottom();
            }
        });
    }

    function appendChatHTML(chat) {
        const chatBox = document.getElementById('chatBoxContent');
        const isAdmin = chat.sender_type === 'admin';
        const time = new Date(chat.created_at).toLocaleTimeString('id-ID', {hour: '2-digit', minute:'2-digit'});

        let html = '';
        if(isAdmin) {
            // Bubble Admin (Kanan - Karena admin yang sedang login)
            html = `
            <div class="flex items-start justify-end gap-2 mt-2">
                <div class="bg-blue-50 border border-blue-100 rounded-2xl rounded-tr-none p-3 max-w-[80%] shadow-sm">
                    <p class="text-xs text-gray-800 leading-relaxed">${chat.message}</p>
                    <p class="text-[9px] text-gray-400 mt-1 text-right">${time}</p>
                </div>
            </div>`;
        } else {
            // Bubble Pembeli / Penjual (Kiri)
            const isCustomer = chat.sender_type === 'customer';
            const icon = isCustomer ? '<i class="fas fa-user text-gray-500"></i>' : '<i class="fas fa-store text-orange-500"></i>';
            const senderName = isCustomer ? 'Pembeli' : 'Penjual';
            const nameColor = isCustomer ? 'text-gray-600' : 'text-orange-600';

            html = `
            <div class="flex items-start gap-2 mt-2">
                <div class="w-8 h-8 rounded-full bg-white flex items-center justify-center flex-shrink-0 shadow-sm border border-gray-200 text-xs">
                    ${icon}
                </div>
                <div class="bg-white border border-gray-200 rounded-2xl rounded-tl-none p-3 max-w-[80%] shadow-sm">
                    <p class="text-[10px] font-bold mb-1 ${nameColor}">${senderName}</p>
                    <p class="text-xs text-gray-800 leading-relaxed">${chat.message}</p>
                    <p class="text-[9px] text-gray-400 mt-1 text-left">${time}</p>
                </div>
            </div>`;
        }

        if(chatBox.innerHTML.includes('Belum ada obrolan')) { chatBox.innerHTML = ''; }
        chatBox.insertAdjacentHTML('beforeend', html);
    }

    function scrollToBottom() {
        const area = document.getElementById('chatScrollArea');
        if(area) area.scrollTop = area.scrollHeight;
    }
</script>

<script>
    function copyResi(text) {
        navigator.clipboard.writeText(text).then(function() {
            alert("Resi disalin: " + text);
        }).catch(function(err) {
            alert("Gagal menyalin resi.");
        });
    }
</script>

@endsection
