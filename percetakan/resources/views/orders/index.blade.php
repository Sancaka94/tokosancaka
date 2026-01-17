@extends('layouts.app')

@section('content')

    {{-- ASSETS: Flatpickr --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" type="text/css" href="https://npmcdn.com/flatpickr/dist/themes/airbnb.css">
    <style>
        .flatpickr-calendar { z-index: 9999 !important; }
        .table-hover tr:hover td { background-color: #f8fafc; }
    </style>

    <div class="container mx-auto px-4 py-8 max-w-7xl">

        {{-- BAGIAN 1: HEADER & ACTIONS --}}
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
            <div>
                <h1 class="text-3xl font-bold text-slate-800 tracking-tight">Riwayat Pesanan</h1>
                <p class="text-slate-500 mt-1 text-sm">Kelola dan pantau semua transaksi yang masuk.</p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                {{-- Tombol Bulk Delete --}}
                <button id="btn-delete-selected" class="hidden bg-rose-600 text-white px-4 py-2.5 rounded-lg font-semibold text-sm hover:bg-rose-700 transition shadow-sm flex items-center gap-2">
                    <i class="fas fa-trash-alt"></i>
                    <span>Hapus (<span id="count-selected">0</span>)</span>
                </button>

                {{-- Export Buttons --}}
                <div class="inline-flex rounded-lg shadow-sm" role="group">
                    <a href="{{ route('orders.export.pdf', request()->query()) }}" target="_blank" class="bg-white text-rose-600 border border-slate-200 px-4 py-2.5 text-sm font-semibold hover:bg-rose-50 transition flex items-center gap-2 rounded-l-lg first:border-r-0">
                        <i class="fas fa-file-pdf"></i> PDF
                    </a>
                    <a href="{{ route('orders.export.excel', request()->query()) }}" target="_blank" class="bg-white text-emerald-600 border border-slate-200 px-4 py-2.5 text-sm font-semibold hover:bg-emerald-50 transition flex items-center gap-2 rounded-r-lg border-l-0">
                        <i class="fas fa-file-excel"></i> Excel
                    </a>
                </div>

                <a href="{{ route('orders.create') }}" class="bg-emerald-600 text-white px-5 py-2.5 rounded-lg font-semibold text-sm hover:bg-emerald-700 transition shadow-md shadow-emerald-200 flex items-center gap-2">
                    <i class="fas fa-plus"></i> Buat Pesanan
                </a>
            </div>
        </div>

        {{-- BAGIAN 2: FILTER CARD --}}
        <div class="bg-white rounded-xl p-6 mb-8 shadow-sm border border-slate-200">
            <form action="{{ route('orders.index') }}" method="GET" class="grid grid-cols-1 md:grid-cols-12 gap-5 items-end">
                {{-- Search Input --}}
                <div class="md:col-span-4">
                    <label class="text-xs font-bold text-slate-500 uppercase tracking-wide mb-2 block">Pencarian</label>
                    <div class="relative group">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400 group-focus-within:text-emerald-500 transition">
                            <i class="fas fa-search"></i>
                        </span>
                        <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari No. Order / Pelanggan..."
                            class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm text-slate-700 focus:bg-white focus:ring-2 focus:ring-emerald-100 focus:border-emerald-500 transition placeholder-slate-400 shadow-sm">
                    </div>
                </div>

                {{-- Filter Tanggal --}}
                <div class="md:col-span-4">
                    <label class="text-xs font-bold text-slate-500 uppercase tracking-wide mb-2 block">Periode Transaksi</label>
                    <div class="relative group">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400 group-focus-within:text-emerald-500 transition">
                            <i class="far fa-calendar-alt"></i>
                        </span>
                        <input type="text" id="date_range" name="date_range" value="{{ request('date_range') }}" placeholder="Pilih Rentang Tanggal"
                            class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm text-slate-700 focus:bg-white focus:ring-2 focus:ring-emerald-100 focus:border-emerald-500 transition cursor-pointer placeholder-slate-400 shadow-sm">
                    </div>
                </div>

                {{-- Filter Status --}}
                <div class="md:col-span-2">
                    <label class="text-xs font-bold text-slate-500 uppercase tracking-wide mb-2 block">Status Pembayaran</label>
                    <div class="relative">
                        <select name="status" class="w-full pl-3 pr-8 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm text-slate-700 focus:bg-white focus:ring-2 focus:ring-emerald-100 focus:border-emerald-500 transition appearance-none cursor-pointer shadow-sm">
                            <option value="">Semua Status</option>
                            <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>Lunas</option>
                            <option value="unpaid" {{ request('status') == 'unpaid' ? 'selected' : '' }}>Belum Bayar</option>
                        </select>
                        <i class="fas fa-chevron-down absolute right-3 top-3.5 text-xs text-slate-400 pointer-events-none"></i>
                    </div>
                </div>

                {{-- Action Buttons Filter --}}
                <div class="md:col-span-2 flex gap-2">
                    <button type="submit" class="w-full px-4 py-2.5 bg-slate-800 text-white rounded-lg font-semibold text-sm hover:bg-slate-900 transition shadow-lg shadow-slate-200">
                        Terapkan
                    </button>
                    @if(request()->anyFilled(['q', 'date_range', 'status']))
                        <a href="{{ route('orders.index') }}" class="px-3 py-2.5 bg-white text-rose-500 border border-rose-200 rounded-lg hover:bg-rose-50 transition shadow-sm" title="Reset Filter">
                            <i class="fas fa-times"></i>
                        </a>
                    @endif
                </div>
            </form>
        </div>

        {{-- BAGIAN 3: DASHBOARD CARDS (STATISTIK) --}}
        {{-- CATATAN: Pastikan variabel $totalRevenue, $totalCustomer, dll dikirim dari Controller --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">

            {{-- Card 1: Total Omset (Uang) --}}
            <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex items-start gap-4 hover:shadow-md transition">
                <div class="w-12 h-12 rounded-full bg-emerald-100 flex items-center justify-center text-emerald-600 shrink-0">
                    <i class="fas fa-money-bill-wave text-lg"></i>
                </div>
                <div>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-wide">Total Pendapatan</p>
                    <h3 class="text-xl font-black text-slate-800">Rp {{ number_format($totalRevenue ?? 0, 0, ',', '.') }}</h3>
                    <p class="text-[10px] text-slate-500 mt-1">Sesuai periode terpilih</p>
                </div>
            </div>

            {{-- Card 2: Total Customer --}}
            <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex items-start gap-4 hover:shadow-md transition">
                <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 shrink-0">
                    <i class="fas fa-users text-lg"></i>
                </div>
                <div>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-wide">Total Pelanggan</p>
                    <h3 class="text-xl font-black text-slate-800">{{ number_format($totalCustomer ?? 0) }}</h3>
                    <p class="text-[10px] text-slate-500 mt-1">Orang Transaksi</p>
                </div>
            </div>

            {{-- Card 3: Lunas --}}
            <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex items-start gap-4 hover:shadow-md transition">
                <div class="w-12 h-12 rounded-full bg-green-100 flex items-center justify-center text-green-600 shrink-0">
                    <i class="fas fa-check-circle text-lg"></i>
                </div>
                <div>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-wide">Transaksi Lunas</p>
                    <h3 class="text-xl font-black text-slate-800">{{ number_format($totalLunas ?? 0) }}</h3>
                    <p class="text-[10px] text-slate-500 mt-1">Pesanan selesai bayar</p>
                </div>
            </div>

            {{-- Card 4: Belum Lunas --}}
            <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex items-start gap-4 hover:shadow-md transition">
                <div class="w-12 h-12 rounded-full bg-rose-100 flex items-center justify-center text-rose-600 shrink-0">
                    <i class="fas fa-times-circle text-lg"></i>
                </div>
                <div>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-wide">Belum Lunas</p>
                    <h3 class="text-xl font-black text-slate-800">{{ number_format($totalUnpaid ?? 0) }}</h3>
                    <p class="text-[10px] text-slate-500 mt-1">Perlu ditagih</p>
                </div>
            </div>

            {{-- Card 5: Best Seller Category --}}
            <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex items-start gap-4 hover:shadow-md transition">
                <div class="w-12 h-12 rounded-full bg-amber-100 flex items-center justify-center text-amber-600 shrink-0">
                    <i class="fas fa-crown text-lg"></i>
                </div>
                <div class="overflow-hidden">
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-wide">Kategori Terlaris</p>
                    <h3 class="text-lg font-black text-slate-800 truncate" title="{{ $bestSellerCategory->name ?? '-' }}">
                        {{ $bestSellerCategory->name ?? '-' }}
                    </h3>
                    <p class="text-[10px] text-slate-500 mt-1">{{ $bestSellerCategory->total ?? 0 }} Terjual</p>
                </div>
            </div>

            {{-- Card 6: Best Seller Varian --}}
            <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex items-start gap-4 hover:shadow-md transition">
                <div class="w-12 h-12 rounded-full bg-purple-100 flex items-center justify-center text-purple-600 shrink-0">
                    <i class="fas fa-tags text-lg"></i>
                </div>
                <div class="overflow-hidden">
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-wide">Varian Terlaris</p>
                    <h3 class="text-lg font-black text-slate-800 truncate" title="{{ $bestSellerVariant->name ?? '-' }}">
                        {{ $bestSellerVariant->name ?? '-' }}
                    </h3>
                    <p class="text-[10px] text-slate-500 mt-1">{{ $bestSellerVariant->total ?? 0 }} Pcs</p>
                </div>
            </div>

             {{-- Card 7: Laundry Tonase (Kg) --}}
             <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex items-start gap-4 hover:shadow-md transition md:col-span-2 lg:col-span-2">
                <div class="w-12 h-12 rounded-full bg-sky-100 flex items-center justify-center text-sky-600 shrink-0">
                    <i class="fas fa-weight-hanging text-lg"></i>
                </div>
                <div>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-wide">Total Berat Laundry</p>
                    <div class="flex items-end gap-2">
                        <h3 class="text-xl font-black text-slate-800">{{ number_format($totalLaundryWeight ?? 0, 1, ',', '.') }}</h3>
                        <span class="text-sm font-bold text-slate-500 mb-1">Kg</span>
                    </div>
                    <p class="text-[10px] text-slate-500 mt-1">Khusus kategori Laundry</p>
                </div>
            </div>

        </div>

        {{-- BAGIAN 4: TABEL DATA (Kode Sama Seperti Sebelumnya) --}}
        <form id="form-bulk-delete" action="{{ route('orders.bulkDestroy') }}" method="POST">
            @csrf
            @method('DELETE')

            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-200 text-xs font-bold text-slate-500 uppercase tracking-wider">
                                <th class="px-6 py-4 w-[60px] text-center">
                                    <input type="checkbox" id="select-all" class="w-4 h-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 cursor-pointer">
                                </th>
                                <th class="px-6 py-4">Detail Transaksi</th>
                                <th class="px-6 py-4">Pelanggan</th>
                                <th class="px-6 py-4">Pengiriman</th>
                                <th class="px-6 py-4 text-right">Total & Status</th>
                                <th class="px-6 py-4 text-center">Status Order</th>
                                <th class="px-6 py-4 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-sm">

                            {{-- MAPPING LOGO KURIR --}}
                            @php
                                $courierMap = [
                                    'jne'          => ['name' => 'JNE', 'url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/jne.png'],
                                    'tiki'         => ['name' => 'TIKI', 'url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/tiki.png'],
                                    'pos'          => ['name' => 'POS Indo', 'url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/posindonesia.png'],
                                    'posindonesia' => ['name' => 'POS Indo', 'url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/posindonesia.png'],
                                    'sicepat'      => ['name' => 'SiCepat', 'url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/sicepat.png'],
                                    'sap'          => ['name' => 'SAP', 'url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/sap.png'],
                                    'ncs'          => ['name' => 'NCS', 'url' => 'https://blogger.googleusercontent.com/img/b/R29vZ2xl/AVvXsEjxj3iyyZEjK2L4A4yCIr_E-4W3hF2lk_yb-t0Oj2oFPErCPCMHie5LHqps02xMb6sNa-Gqz5NSX_P_hzWlYpUpJUlCD4iN6_QxiSG9fzY4bsZ9XvLFDn7HCiORtNvIlPfuQbSSdW96p7x7uN8ek3FWyHW9c2bznrFBQkoLd5A9sVAFVKWLfUhT3Dxh/s320/GKL41_NCS%20Kurir%20-%20Koleksilogo.com.jpg'],
                                    'idx'          => ['name' => 'ID Express', 'url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/idx.png'],
                                    'idexpress'    => ['name' => 'ID Express', 'url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/idx.png'],
                                    'gojek'        => ['name' => 'GoSend', 'url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/gosend.png'],
                                    'gosend'       => ['name' => 'GoSend', 'url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/gosend.png'],
                                    'grab'         => ['name' => 'Grab', 'url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/grab.png'],
                                    'jnt'          => ['name' => 'J&T', 'url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/jnt.png'],
                                    'j&t'          => ['name' => 'J&T', 'url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/jnt.png'],
                                    'indah'        => ['name' => 'Indah', 'url' => 'https://blogger.googleusercontent.com/img/b/R29vZ2xl/AVvXsEicOAaLoH2eElQ93_gbkzhvk4dRhWVlk5wQsGgilihIB58321aHchlJLdjyz1ToS25P_nWrHJ_E4QBiW_OVlI7tQt7cZ5I0HZqk6StS7jZltLVvDXp2d5ZDLB9yklhV4x6z2iXyURURDv_unhf-U6vyiD_8to9OC4PBwMwyU_5wAqOiCl6tKiaTA-ri1Q/s851/Logo%20Indah%20Logistik%20Cargo@0.5x.png'],
                                    'jtcargo'      => ['name' => 'J&T Cargo', 'url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/jtcargo.png'],
                                    'lion'         => ['name' => 'Lion Parcel', 'url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/lion.png'],
                                    'spx'          => ['name' => 'SPX', 'url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/spx.png'],
                                    'shopee'       => ['name' => 'SPX', 'url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/spx.png'],
                                    'ninja'        => ['name' => 'Ninja', 'url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/ninja.png'],
                                    'anteraja'     => ['name' => 'Anteraja', 'url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/anteraja.png'],
                                    'sentral'      => ['name' => 'Sentral', 'url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/centralcargo.png'],
                                    'borzo'        => ['name' => 'Borzo', 'url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/borzo.png'],
                                ];
                            @endphp

                            @forelse($orders as $order)
                            <tr class="group hover:bg-slate-50 transition duration-150">
                                {{-- CHECKBOX --}}
                                <td class="px-6 py-4 text-center align-top">
                                    <input type="checkbox" name="ids[]" value="{{ $order->id }}" class="order-checkbox w-4 h-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 cursor-pointer mt-1">
                                </td>

                                {{-- INFO TRANSAKSI --}}
                                <td class="px-6 py-4 align-top">
                                    <div class="flex flex-col">
                                        <span class="font-bold text-slate-800 text-sm font-mono">{{ $order->order_number }}</span>
                                        <div class="flex items-center gap-1 mt-1 text-xs text-slate-500">
                                            <i class="far fa-clock"></i>
                                            <span>{{ $order->created_at->translatedFormat('d M Y, H:i') }}</span>
                                        </div>

                                        @if($order->shipping_ref)
                                            <div class="mt-2 flex flex-col items-start gap-1">
                                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wide">Resi Pengiriman</span>
                                                <div class="inline-flex items-center px-2 py-1 bg-blue-50 border border-blue-100 rounded text-blue-700 font-mono text-xs select-all">
                                                    {{ $order->shipping_ref }}
                                                    <i class="fas fa-copy ml-2 opacity-50 text-[10px]"></i>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </td>

                                {{-- INFO PELANGGAN --}}
                                <td class="px-6 py-4 align-top">
                                    <div class="flex flex-col">
                                        <span class="font-bold text-slate-700 text-sm">{{ $order->customer_name }}</span>
                                        <a href="https://wa.me/{{ preg_replace('/^0/', '62', preg_replace('/[^0-9]/', '', $order->customer_phone)) }}" target="_blank" class="inline-flex items-center gap-1.5 mt-1 text-xs text-green-600 hover:text-green-700 transition w-fit">
                                            <i class="fab fa-whatsapp text-sm"></i>
                                            <span class="underline decoration-green-200">{{ $order->customer_phone }}</span>
                                        </a>

                                        @if($order->destination_address)
                                            <div class="mt-2 p-2 bg-slate-50 rounded border border-slate-100 text-xs text-slate-600 leading-snug break-words max-w-[280px]">
                                                {{ $order->destination_address }}
                                            </div>
                                        @else
                                            <div class="mt-2">
                                                <span class="inline-flex items-center gap-1 px-2 py-1 rounded-md bg-indigo-50 text-indigo-600 text-xs font-semibold border border-indigo-100">
                                                    <i class="fas fa-store"></i> Ambil di Toko
                                                </span>
                                            </div>
                                        @endif
                                    </div>
                                </td>

                                {{-- INFO EKSPEDISI --}}
                                <td class="px-6 py-4 align-top">
                                    @php
                                        $kurirKey = strtolower($order->courier_service ?? '');
                                        $matchedKey = collect(array_keys($courierMap))->first(function($key) use ($kurirKey) {
                                            return str_contains($kurirKey, $key);
                                        });
                                        $logoData = $matchedKey ? $courierMap[$matchedKey] : null;
                                    @endphp

                                    <div class="flex items-start gap-3">
                                        <div class="w-10 h-10 rounded-lg border border-slate-200 bg-white p-1 flex items-center justify-center shrink-0 shadow-sm">
                                            @if($logoData)
                                                <img src="{{ $logoData['url'] }}" alt="{{ $logoData['name'] }}" class="w-full h-full object-contain">
                                            @else
                                                <i class="fas fa-truck text-slate-300"></i>
                                            @endif
                                        </div>
                                        <div class="flex flex-col">
                                            <span class="text-xs font-bold text-slate-700 uppercase">
                                                {{ $order->courier_service ?? 'Pickup' }}
                                            </span>
                                            <span class="text-xs font-semibold text-emerald-600 mt-0.5">
                                                + Rp {{ number_format($order->shipping_cost, 0, ',', '.') }}
                                            </span>
                                        </div>
                                    </div>
                                </td>

                                {{-- TOTAL & STATUS BAYAR --}}
                                <td class="px-6 py-4 align-top text-right">
                                    <div class="flex flex-col items-end gap-1">
                                        <span class="text-sm font-black text-slate-800">
                                            Rp {{ number_format($order->final_price, 0, ',', '.') }}
                                        </span>
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase border {{ $order->payment_status == 'paid' ? 'bg-green-50 text-green-600 border-green-200' : 'bg-red-50 text-red-600 border-red-200' }}">
                                            {{ $order->payment_status == 'paid' ? 'LUNAS' : 'BELUM BAYAR' }}
                                        </span>
                                    </div>
                                </td>

                                {{-- STATUS PESANAN --}}
                                <td class="px-6 py-4 align-top text-center">
                                    @php
                                        $statusStyles = [
                                            'completed'  => 'bg-emerald-100 text-emerald-700 border-emerald-200',
                                            'processing' => 'bg-sky-100 text-sky-700 border-sky-200',
                                            'pending'    => 'bg-amber-100 text-amber-700 border-amber-200',
                                            'cancelled'  => 'bg-rose-100 text-rose-700 border-rose-200',
                                        ];
                                        $style = $statusStyles[$order->status] ?? 'bg-slate-100 text-slate-600 border-slate-200';
                                    @endphp
                                    <span class="inline-block px-3 py-1 rounded-full text-[10px] font-bold uppercase border tracking-wide {{ $style }}">
                                        {{ $order->status }}
                                    </span>
                                </td>

                                {{-- AKSI --}}
                                <td class="px-6 py-4 align-top text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <a href="{{ route('orders.show', $order->id) }}" class="w-8 h-8 flex items-center justify-center rounded-lg bg-blue-50 text-blue-600 border border-blue-100 hover:bg-blue-600 hover:text-white hover:border-blue-600 transition shadow-sm" title="Lihat Detail">
                                            <i class="fas fa-eye text-xs"></i>
                                        </a>

                                        <a href="{{ route('orders.edit', $order->id) }}" class="w-8 h-8 flex items-center justify-center rounded-lg bg-amber-50 text-amber-600 border border-amber-100 hover:bg-amber-500 hover:text-white hover:border-amber-500 transition shadow-sm" title="Edit Pesanan">
                                            <i class="fas fa-pencil-alt text-xs"></i>
                                        </a>

                                        <button type="button" onclick="confirmDelete('{{ route('orders.destroy', $order->id) }}')" class="w-8 h-8 flex items-center justify-center rounded-lg bg-rose-50 text-rose-600 border border-rose-100 hover:bg-rose-600 hover:text-white hover:border-rose-600 transition shadow-sm" title="Hapus Pesanan">
                                            <i class="fas fa-trash-alt text-xs"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="px-6 py-16 text-center">
                                    <div class="flex flex-col items-center justify-center text-slate-400">
                                        <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mb-4">
                                            <i class="fas fa-box-open text-3xl opacity-30"></i>
                                        </div>
                                        <p class="font-medium text-slate-500">Data pesanan tidak ditemukan.</p>
                                        <p class="text-xs text-slate-400 mt-1">Coba ubah filter pencarian atau tanggal.</p>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- PAGINATION --}}
                @if($orders->hasPages())
                    <div class="px-6 py-4 border-t border-slate-100 bg-slate-50">
                        {{ $orders->appends(request()->query())->links() }}
                    </div>
                @endif
            </div>
        </form>
    </div>

    {{-- Hidden Form Single Delete --}}
    <form id="single-delete-form" action="" method="POST" class="hidden">
        @csrf @method('DELETE')
    </form>

    {{-- SCRIPTS --}}
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://npmcdn.com/flatpickr/dist/l10n/id.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Konfigurasi Flatpickr
            flatpickr("#date_range", {
                mode: "range",
                dateFormat: "Y-m-d",
                altInput: true,
                altFormat: "j F Y",
                locale: "id",
                theme: "airbnb",
                showMonths: 2,
                allowInput: true
            });

            // Logic Checkbox & Bulk Delete
            const selectAll = document.getElementById('select-all');
            const checkboxes = document.querySelectorAll('.order-checkbox');
            const btnDelete = document.getElementById('btn-delete-selected');
            const countText = document.getElementById('count-selected');

            function toggleDeleteButton() {
                const checkedCount = document.querySelectorAll('.order-checkbox:checked').length;
                if(countText) countText.innerText = checkedCount;

                if (btnDelete) {
                    if (checkedCount > 0) {
                        btnDelete.classList.remove('hidden');
                        btnDelete.classList.add('flex');
                    } else {
                        btnDelete.classList.add('hidden');
                        btnDelete.classList.remove('flex');
                    }
                }
            }

            if (selectAll) {
                selectAll.addEventListener('change', function() {
                    checkboxes.forEach(cb => cb.checked = this.checked);
                    toggleDeleteButton();
                });
            }

            checkboxes.forEach(cb => {
                cb.addEventListener('change', toggleDeleteButton);
            });

            if (btnDelete) {
                btnDelete.addEventListener('click', function(e) {
                    e.preventDefault();
                    if (confirm('Yakin ingin menghapus ' + countText.innerText + ' data terpilih? Tindakan ini tidak dapat dibatalkan.')) {
                        document.getElementById('form-bulk-delete').submit();
                    }
                });
            }
        });

        function confirmDelete(url) {
            if (confirm('Apakah Anda yakin ingin menghapus pesanan ini secara permanen?')) {
                const form = document.getElementById('single-delete-form');
                form.action = url;
                form.submit();
            }
        }
    </script>
@endsection
