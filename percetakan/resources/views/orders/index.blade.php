@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6 max-w-7xl">
    
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-black text-slate-800">Riwayat Pesanan</h1>
            <p class="text-sm text-slate-500">Daftar semua transaksi yang masuk.</p>
        </div>
        <div class="flex gap-2">
            <button id="btn-delete-selected" class="hidden bg-rose-600 text-white px-4 py-2 rounded-lg font-bold text-sm hover:bg-rose-700 transition flex items-center gap-2 shadow-lg shadow-rose-200">
                <i class="fas fa-trash-alt"></i> Hapus Terpilih (<span id="count-selected">0</span>)
            </button>

            <a href="{{ route('orders.create') }}" class="bg-emerald-600 text-white px-4 py-2 rounded-lg font-bold text-sm hover:bg-emerald-700 transition flex items-center gap-2 shadow-lg shadow-emerald-200">
                <i class="fas fa-plus"></i> Pesanan Baru
            </a>
        </div>
    </div>

    <form id="form-bulk-delete" action="{{ route('orders.bulkDestroy') }}" method="POST">
        @csrf
        @method('DELETE')
        
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-slate-50 text-slate-500 font-bold uppercase text-[10px]">
                        <tr>
                            <th class="px-6 py-4 w-[5%]">
                                <input type="checkbox" id="select-all" class="w-4 h-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 cursor-pointer">
                            </th>
                            <th class="px-6 py-4 w-[15%]">Transaksi</th>
                            <th class="px-6 py-4 w-[25%]">Pelanggan & Alamat</th>
                            <th class="px-6 py-4 w-[20%]">Ekspedisi & Ongkir</th>
                            <th class="px-6 py-4 w-[15%] text-right">Total & Bayar</th>
                            <th class="px-6 py-4 w-[10%] text-center">Status</th>
                            <th class="px-6 py-4 w-[10%] text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @php
                            // Mapping Logo Ekspedisi
                            $courierMap = [
                                'jne'          => ['name' => 'JNE', 'url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/jne.png'],
                                'tiki'         => ['name' => 'TIKI', 'url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/tiki.png'],
                                'pos'          => ['name' => 'POS Indonesia', 'url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/posindonesia.png'],
                                'posindonesia' => ['name' => 'POS Indonesia', 'url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/posindonesia.png'],
                                'sicepat'      => ['name' => 'SiCepat', 'url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/sicepat.png'],
                                'sap'          => ['name' => 'SAP Express', 'url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/sap.png'],
                                'ncs'          => ['name' => 'NCS Kurir', 'url' => 'https://blogger.googleusercontent.com/img/b/R29vZ2xl/AVvXsEjxj3iyyZEjK2L4A4yCIr_E-4W3hF2lk_yb-t0Oj2oFPErCPCMHie5LHqps02xMb6sNa-Gqz5NSX_P_hzWlYpUpJUlCD4iN6_QxiSG9fzY4bsZ9XvLFDn7HCiORtNvIlPfuQbSSdW96p7x7uN8ek3FWyHW9c2bznrFBQkoLd5A9sVAFVKWLfUhT3Dxh/s320/GKL41_NCS%20Kurir%20-%20Koleksilogo.com.jpg'],
                                'idx'          => ['name' => 'ID Express', 'url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/idx.png'],
                                'idexpress'    => ['name' => 'ID Express', 'url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/idx.png'],
                                'gojek'        => ['name' => 'GoSend', 'url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/gosend.png'],
                                'gosend'       => ['name' => 'GoSend', 'url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/gosend.png'],
                                'grab'         => ['name' => 'GrabExpress', 'url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/grab.png'],
                                'jnt'          => ['name' => 'J&T Express', 'url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/jnt.png'],
                                'j&t'          => ['name' => 'J&T Express', 'url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/jnt.png'],
                                'indah'        => ['name' => 'Indah Cargo', 'url' => 'https://blogger.googleusercontent.com/img/b/R29vZ2xl/AVvXsEicOAaLoH2eElQ93_gbkzhvk4dRhWVlk5wQsGgilihIB58321aHchlJLdjyz1ToS25P_nWrHJ_E4QBiW_OVlI7tQt7cZ5I0HZqk6StS7jZltLVvDXp2d5ZDLB9yklhV4x6z2iXyURURDv_unhf-U6vyiD_8to9OC4PBwMwyU_5wAqOiCl6tKiaTA-ri1Q/s851/Logo%20Indah%20Logistik%20Cargo@0.5x.png'],
                                'jtcargo'      => ['name' => 'J&T Cargo', 'url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/jtcargo.png'],
                                'lion'         => ['name' => 'Lion Parcel', 'url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/lion.png'],
                                'spx'          => ['name' => 'SPX Express', 'url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/spx.png'],
                                'shopee'       => ['name' => 'SPX Express', 'url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/spx.png'],
                                'ninja'        => ['name' => 'Ninja Express', 'url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/ninja.png'],
                                'anteraja'     => ['name' => 'Anteraja', 'url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/anteraja.png'],
                                'sentral'      => ['name' => 'Sentral Cargo', 'url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/centralcargo.png'],
                                'borzo'        => ['name' => 'Borzo', 'url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/borzo.png'],
                            ];
                        @endphp

                        @forelse($orders as $order)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-6 py-4">
                                <input type="checkbox" name="ids[]" value="{{ $order->id }}" class="order-checkbox w-4 h-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 cursor-pointer">
                            </td>
                            
                            {{-- KOLOM 1: TRANSAKSI --}}
                            <td class="px-6 py-4 align-top">
                                <div class="flex flex-col gap-1">
                                    <span class="font-bold text-slate-800 text-xs uppercase">{{ $order->order_number }}</span>
                                    <span class="text-[10px] text-slate-400">{{ $order->created_at->translatedFormat('d M Y, H:i') }}</span>
                                    @if($order->shipping_ref)
                                        <div class="mt-1 flex items-center gap-1">
                                            <span class="text-[9px] font-bold text-slate-400 uppercase">Resi:</span>
                                            <span class="text-[10px] font-mono text-blue-600 bg-blue-50 px-1.5 py-0.5 rounded border border-blue-100 select-all">
                                                {{ $order->shipping_ref }}
                                            </span>
                                        </div>
                                    @endif
                                </div>
                            </td>

                            {{-- KOLOM 2: PELANGGAN & ALAMAT (LENGKAP) --}}
                            <td class="px-6 py-4 align-top">
                                <div class="flex flex-col gap-1">
                                    <div class="font-bold text-slate-700 text-sm uppercase">{{ $order->customer_name }}</div>
                                    <div class="flex items-center gap-1 text-xs text-slate-500">
                                        <i class="fab fa-whatsapp text-green-500"></i> {{ $order->customer_phone }}
                                    </div>
                                    @if($order->destination_address)
                                        <div class="mt-1.5 text-[10px] text-slate-500 leading-snug break-words bg-slate-50 p-1.5 rounded border border-slate-100 max-w-[250px]">
                                            {{ $order->destination_address }}
                                        </div>
                                    @else
                                        <div class="mt-1">
                                            <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded bg-blue-50 text-blue-600 text-[9px] font-bold border border-blue-100">
                                                <i class="fas fa-store"></i> Ambil di Toko
                                            </span>
                                        </div>
                                    @endif
                                </div>
                            </td>

                            {{-- KOLOM 3: EKSPEDISI & LOGO --}}
                            <td class="px-6 py-4 align-top">
                                @php
                                    $kurirKey = strtolower($order->courier_service ?? '');
                                    // Mencari key yang cocok dari courier_service yang ada
                                    $matchedKey = collect(array_keys($courierMap))->first(function($key) use ($kurirKey) {
                                        return str_contains($kurirKey, $key);
                                    });
                                    $logoData = $matchedKey ? $courierMap[$matchedKey] : null;
                                @endphp

                                <div class="flex items-start gap-2">
                                    <div class="bg-white border border-slate-200 p-1 rounded h-8 w-10 flex items-center justify-center overflow-hidden shrink-0 shadow-sm">
                                        @if($logoData)
                                            <img src="{{ $logoData['url'] }}" alt="{{ $logoData['name'] }}" class="w-full h-full object-contain">
                                        @else
                                            <i class="fas fa-truck text-slate-400 text-xs"></i>
                                        @endif
                                    </div>
                                    <div>
                                        <div class="text-[10px] font-bold text-slate-700 uppercase leading-tight">
                                            {{ $order->courier_service ?? 'Pickup' }}
                                        </div>
                                        <div class="text-[10px] font-bold text-emerald-600 mt-0.5">
                                            + Rp {{ number_format($order->shipping_cost, 0, ',', '.') }}
                                        </div>
                                    </div>
                                </div>
                            </td>

                            {{-- KOLOM 4: TOTAL & BAYAR --}}
                            <td class="px-6 py-4 align-top text-right">
                                <div class="font-black text-slate-800 text-sm">Rp {{ number_format($order->final_price, 0, ',', '.') }}</div>
                                <div class="mt-1">
                                    <span class="px-1.5 py-0.5 rounded text-[9px] font-bold border uppercase {{ $order->payment_status == 'paid' ? 'bg-green-50 text-green-600 border-green-200' : 'bg-red-50 text-red-600 border-red-200' }}">
                                        {{ $order->payment_status == 'paid' ? 'LUNAS' : 'BELUM' }}
                                    </span>
                                </div>
                            </td>

                            {{-- KOLOM 5: STATUS --}}
                            <td class="px-6 py-4 align-top text-center">
                                @php
                                    $styles = [
                                        'completed' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
                                        'processing' => 'bg-blue-100 text-blue-700 border-blue-200',
                                        'pending' => 'bg-amber-100 text-amber-700 border-amber-200',
                                        'cancelled' => 'bg-red-100 text-red-700 border-red-200',
                                    ];
                                    $style = $styles[$order->status] ?? 'bg-slate-100 text-slate-600 border-slate-200';
                                @endphp
                                <span class="inline-block px-2.5 py-1 rounded-full text-[9px] font-bold uppercase border {{ $style }}">
                                    {{ $order->status }}
                                </span>
                            </td>

                            {{-- KOLOM 6: AKSI --}}
                            <td class="px-6 py-4 align-top text-center">
                                <a href="{{ route('orders.show', $order->id) }}" class="inline-flex items-center justify-center w-8 h-8 bg-white border border-slate-200 rounded-lg text-slate-500 hover:text-blue-600 hover:border-blue-300 transition shadow-sm">
                                    <i class="fas fa-eye text-xs"></i>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-slate-400 italic font-medium">
                                <div class="flex flex-col items-center gap-2">
                                    <i class="fas fa-box-open text-3xl opacity-20"></i>
                                    <span>Belum ada data pesanan masuk.</span>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($orders->hasPages())
                <div class="px-6 py-4 border-t border-slate-100 bg-slate-50">
                    {{ $orders->links() }}
                </div>
            @endif
        </div>
    </form>
</div>

{{-- Script JS tetap sama --}}
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const selectAll = document.getElementById('select-all');
        const checkboxes = document.querySelectorAll('.order-checkbox');
        const btnDelete = document.getElementById('btn-delete-selected');
        const countText = document.getElementById('count-selected');

        function toggleDeleteButton() {
            const checkedCount = document.querySelectorAll('.order-checkbox:checked').length;
            countText.innerText = checkedCount;
            if (checkedCount > 0) {
                btnDelete.classList.remove('hidden');
            } else {
                btnDelete.classList.add('hidden');
            }
        }

        selectAll.addEventListener('change', function() {
            checkboxes.forEach(cb => cb.checked = this.checked);
            toggleDeleteButton();
        });

        checkboxes.forEach(cb => {
            cb.addEventListener('change', toggleDeleteButton);
        });

        // Pastikan tombol memicu submit form
        document.getElementById('btn-delete-selected').addEventListener('click', function() {
            if (confirm('Hapus permanen data terpilih?')) {
                document.getElementById('form-bulk-delete').submit();
            }
        });
    });
</script>
@endsection