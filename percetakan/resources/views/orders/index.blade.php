@extends('layouts.app')

{{-- Tambahkan Style Flatpickr untuk kalender keren --}}
@section('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<link rel="stylesheet" type="text/css" href="https://npmcdn.com/flatpickr/dist/themes/airbnb.css">
@endsection

@section('content')
<div class="container mx-auto px-4 py-6 max-w-7xl">

    {{-- BAGIAN 1: HEADER & ACTIONS --}}
    <div class="flex flex-col xl:flex-row justify-between items-start xl:items-center mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-black text-slate-800">Riwayat Pesanan</h1>
            <p class="text-sm text-slate-500">Daftar semua transaksi yang masuk.</p>
        </div>

        <div class="flex flex-wrap gap-2">
            {{-- Tombol Bulk Delete --}}
            <button id="btn-delete-selected" class="hidden bg-rose-600 text-white px-4 py-2 rounded-lg font-bold text-sm hover:bg-rose-700 transition flex items-center gap-2 shadow-lg shadow-rose-200">
                <i class="fas fa-trash-alt"></i> Hapus Terpilih (<span id="count-selected">0</span>)
            </button>

            {{-- Tombol Export (BARU) --}}
            <div class="flex gap-2 mr-2">
                {{-- Pastikan route export sudah dibuat di web.php --}}
                <a href="{{ route('orders.export.pdf', request()->query()) }}" target="_blank" class="bg-white text-red-600 border border-red-200 px-4 py-2 rounded-lg font-bold text-sm hover:bg-red-50 transition flex items-center gap-2">
                    <i class="fas fa-file-pdf"></i> PDF
                </a>
                <a href="{{ route('orders.export.excel', request()->query()) }}" target="_blank" class="bg-white text-green-600 border border-green-200 px-4 py-2 rounded-lg font-bold text-sm hover:bg-green-50 transition flex items-center gap-2">
                    <i class="fas fa-file-excel"></i> Excel
                </a>
            </div>

            <a href="{{ route('orders.create') }}" class="bg-emerald-600 text-white px-4 py-2 rounded-lg font-bold text-sm hover:bg-emerald-700 transition flex items-center gap-2 shadow-lg shadow-emerald-200">
                <i class="fas fa-plus"></i> Pesanan Baru
            </a>
        </div>
    </div>

    {{-- BAGIAN 2: FILTER & PENCARIAN (BARU & KEREN) --}}
    <div class="bg-white rounded-2xl p-5 mb-6 shadow-sm border border-slate-200">
        <form action="{{ route('orders.index') }}" method="GET" class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">

            {{-- Search Input --}}
            <div class="md:col-span-4">
                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1 block">Pencarian</label>
                <div class="relative group">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400 group-focus-within:text-emerald-500 transition">
                        <i class="fas fa-search"></i>
                    </span>
                    <input type="text" name="q" value="{{ request('q') }}" placeholder="No. Order / Nama Pelanggan..."
                        class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold text-slate-700 focus:ring-2 focus:ring-emerald-100 focus:border-emerald-500 transition placeholder-slate-400">
                </div>
            </div>

            {{-- Filter Tanggal (Flatpickr) --}}
            <div class="md:col-span-4">
                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1 block">Periode Tanggal</label>
                <div class="relative group">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400 group-focus-within:text-emerald-500 transition">
                        <i class="far fa-calendar-alt"></i>
                    </span>
                    <input type="text" id="date_range" name="date_range" value="{{ request('date_range') }}" placeholder="Pilih Rentang Waktu"
                        class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold text-slate-700 focus:ring-2 focus:ring-emerald-100 focus:border-emerald-500 transition cursor-pointer placeholder-slate-400">
                </div>
            </div>

            {{-- Filter Status --}}
            <div class="md:col-span-2">
                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1 block">Status</label>
                <div class="relative">
                    <select name="status" class="w-full pl-3 pr-8 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold text-slate-700 focus:ring-2 focus:ring-emerald-100 focus:border-emerald-500 transition appearance-none cursor-pointer">
                        <option value="">Semua</option>
                        <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>Lunas</option>
                        <option value="unpaid" {{ request('status') == 'unpaid' ? 'selected' : '' }}>Belum Bayar</option>
                    </select>
                    <i class="fas fa-chevron-down absolute right-3 top-3.5 text-xs text-slate-400 pointer-events-none"></i>
                </div>
            </div>

            {{-- Tombol Submit Filter --}}
            <div class="md:col-span-2 flex gap-2">
                <button type="submit" class="w-full px-4 py-2.5 bg-slate-800 text-white rounded-xl font-bold text-sm hover:bg-slate-900 transition shadow-lg shadow-slate-200">
                    Filter
                </button>
                @if(request()->anyFilled(['q', 'date_range', 'status']))
                    <a href="{{ route('orders.index') }}" class="px-3 py-2.5 bg-white text-rose-500 border border-rose-200 rounded-xl font-bold text-sm hover:bg-rose-50 transition" title="Reset">
                        <i class="fas fa-times"></i>
                    </a>
                @endif
            </div>
        </form>
    </div>

    {{-- BAGIAN 3: TABEL DATA --}}
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
                            <th class="px-6 py-4 w-[20%]">Pelanggan & Alamat</th>
                            <th class="px-6 py-4 w-[20%]">Ekspedisi & Ongkir</th>
                            <th class="px-6 py-4 w-[15%] text-right">Total & Bayar</th>
                            <th class="px-6 py-4 w-[10%] text-center">Status</th>
                            <th class="px-6 py-4 w-[15%] text-center">Aksi</th> {{-- Lebar ditambah --}}
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @php
                            // Mapping Logo Ekspedisi (Kode Asli Anda)
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
                        <tr class="hover:bg-slate-50 transition group">
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

                            {{-- KOLOM 2: PELANGGAN & ALAMAT --}}
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

                            {{-- KOLOM 6: AKSI LENGKAP --}}
                            <td class="px-6 py-4 align-top text-center">
                                <div class="flex items-center justify-center gap-2">
                                    {{-- 1. SHOW (DETAIL) --}}
                                    <a href="{{ route('orders.show', $order->id) }}" class="w-8 h-8 flex items-center justify-center bg-blue-50 text-blue-600 rounded-lg hover:bg-blue-600 hover:text-white transition shadow-sm border border-blue-100" title="Detail">
                                        <i class="fas fa-eye text-xs"></i>
                                    </a>

                                    {{-- 2. EDIT --}}
                                    <a href="{{ route('orders.edit', $order->id) }}" class="w-8 h-8 flex items-center justify-center bg-amber-50 text-amber-600 rounded-lg hover:bg-amber-500 hover:text-white transition shadow-sm border border-amber-100" title="Edit">
                                        <i class="fas fa-edit text-xs"></i>
                                    </a>

                                    {{-- 3. HAPUS (SINGLE) --}}
                                    {{-- PENTING: Gunakan type="button" dan panggil JS confirmDelete --}}
                                    <button type="button" onclick="confirmDelete('{{ route('orders.destroy', $order->id) }}')" class="w-8 h-8 flex items-center justify-center bg-rose-50 text-rose-600 rounded-lg hover:bg-rose-600 hover:text-white transition shadow-sm border border-rose-100" title="Hapus">
                                        <i class="fas fa-trash-alt text-xs"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-slate-400 italic font-medium">
                                <div class="flex flex-col items-center gap-2">
                                    <i class="fas fa-box-open text-3xl opacity-20"></i>
                                    <span>Tidak ada pesanan yang ditemukan.</span>
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
                    {{ $orders->appends(request()->query())->links() }} {{-- Gunakan appends agar filter tidak hilang saat ganti halaman --}}
                </div>
            @endif
        </div>
    </form>
</div>

{{-- Hidden Form untuk Single Delete (Solusi Nested Form) --}}
<form id="single-delete-form" action="" method="POST" class="hidden">
    @csrf
    @method('DELETE')
</form>

{{-- SCRIPTS --}}
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://npmcdn.com/flatpickr/dist/l10n/id.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {

        // 1. Inisialisasi Flatpickr (Kalender Keren)
        flatpickr("#date_range", {
            mode: "range",             // Mode Rentang Tanggal
            dateFormat: "Y-m-d",       // Format kirim ke server
            altInput: true,            // Tampilkan input alternatif yang rapi
            altFormat: "j F Y",        // Format User (Contoh: 17 Januari 2026)
            locale: "id",              // Bahasa Indonesia
            theme: "airbnb",           // Tema Minimalis
            showMonths: 2,             // Tampilkan 2 bulan sekaligus
            allowInput: true
        });

        // 2. Logic Bulk Delete
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
                e.preventDefault(); // Prevent default button behavior
                if (confirm('Apakah Anda yakin ingin menghapus ' + countText.innerText + ' data terpilih secara permanen?')) {
                    document.getElementById('form-bulk-delete').submit();
                }
            });
        }
    });

    // 3. Logic Single Delete
    function confirmDelete(url) {
        if (confirm('Apakah Anda yakin ingin menghapus pesanan ini secara permanen?')) {
            const form = document.getElementById('single-delete-form');
            form.action = url;
            form.submit();
        }
    }
</script>
@endsection
