@extends('layouts.customer')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8 font-sans">

    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">Riwayat Pengiriman & Komisi Agen</h1>
            <p class="text-gray-500 mt-1 text-sm">Pantau status paket dan total pendapatan komisi (fee) Anda secara real-time.</p>
        </div>
        <a href="{{ route('customer.pesanan-autokirim.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 px-5 rounded-xl shadow-md transition flex items-center gap-2 text-sm">
            <i class="fa-solid fa-plus"></i> Kirim Paket Baru
        </a>
    </div>

    <!-- ========================================== -->
    <!-- CARD STATISTIK KOMISI (DINAMIS) -->
    <!-- ========================================== -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 border-l-4 border-l-blue-500">
            <p class="text-[10px] text-gray-500 font-bold uppercase tracking-wider mb-1">Total Resi & Omzet</p>
            <h3 class="text-xl font-black text-gray-800">{{ number_format($totalTransaksi) }} Resi</h3>
            <p class="text-xs font-semibold text-blue-600 mt-1">Rp {{ number_format($totalOngkir, 0, ',', '.') }} (Ongkir)</p>
        </div>

        <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 border-l-4 border-l-orange-500 relative overflow-hidden">
            <p class="text-[10px] text-gray-500 font-bold uppercase tracking-wider mb-1">Komisi Hari Ini</p>
            <h3 class="text-xl font-black text-gray-800">Rp {{ number_format($komisi['hari_ini'], 0, ',', '.') }}</h3>

            <div class="mt-2 text-[10px] font-bold flex items-center {{ $growthHarian >= 0 ? 'text-green-500' : 'text-red-500' }}">
                <i class="fa-solid {{ $growthHarian >= 0 ? 'fa-arrow-trend-up' : 'fa-arrow-trend-down' }} mr-1.5"></i>
                <span>{{ number_format(abs($growthHarian), 1) }}% vs Kemarin</span>
            </div>
            <i class="fa-solid fa-coins absolute -right-3 -bottom-3 text-5xl text-orange-50 opacity-50"></i>
        </div>

        <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 border-l-4 border-l-green-500 relative overflow-hidden">
            <p class="text-[10px] text-gray-500 font-bold uppercase tracking-wider mb-1">Komisi Bulan Ini</p>
            <h3 class="text-xl font-black text-gray-800">Rp {{ number_format($komisi['bulan_ini'], 0, ',', '.') }}</h3>

            <div class="mt-2 text-[10px] font-bold flex items-center {{ $growthBulanan >= 0 ? 'text-green-500' : 'text-red-500' }}">
                <i class="fa-solid {{ $growthBulanan >= 0 ? 'fa-arrow-trend-up' : 'fa-arrow-trend-down' }} mr-1.5"></i>
                <span>{{ number_format(abs($growthBulanan), 1) }}% vs Bln Lalu</span>
            </div>
            <i class="fa-solid fa-chart-line absolute -right-3 -bottom-3 text-5xl text-green-50 opacity-50"></i>
        </div>

        <div class="bg-gradient-to-br from-gray-900 to-gray-800 p-5 rounded-2xl shadow-md text-white relative overflow-hidden">
            <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider mb-1">Total Fee / Komisi</p>
            <h3 class="text-2xl font-black text-green-400">Rp {{ number_format($komisi['total'], 0, ',', '.') }}</h3>
            <p class="text-xs text-gray-300 mt-2"><i class="fa-solid fa-check-circle text-green-500 mr-1"></i> Total pendapatan agen</p>
            <i class="fa-solid fa-wallet absolute -right-3 -bottom-3 text-5xl text-white opacity-5"></i>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- FILTER PENCARIAN -->
    <!-- ========================================== -->
    <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 mb-6">
        <form action="{{ route('customer.pesanan-autokirim.index') }}" method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Cari Data Lengkap</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Resi, Pengirim, Penerima, HP..." class="w-full bg-gray-50 border border-gray-200 rounded-xl text-sm px-4 py-2.5 focus:ring-1 focus:ring-blue-500 outline-none transition">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Status Transaksi</label>
                <select name="status" class="w-full bg-gray-50 border border-gray-200 rounded-xl text-sm px-4 py-2.5 focus:ring-1 focus:ring-blue-500 outline-none transition">
                    <option value="">Semua Status</option>
                    <option value="booking_created" {{ request('status') == 'booking_created' ? 'selected' : '' }}>Sukses (Booking Created)</option>
                    <option value="menunggu_pembayaran" {{ request('status') == 'menunggu_pembayaran' ? 'selected' : '' }}>Pending / Menunggu Bayar</option>
                    <option value="batal" {{ request('status') == 'batal' ? 'selected' : '' }}>Dibatalkan</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Tanggal (YYYY-MM-DD)</label>
                <input type="text" name="date_range" value="{{ request('date_range') }}" placeholder="Cth: 2026-07-21" class="w-full bg-gray-50 border border-gray-200 rounded-xl text-sm px-4 py-2.5 focus:ring-1 focus:ring-blue-500 outline-none transition">
            </div>
            <div>
                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl px-4 py-2.5 text-sm transition shadow-md">
                    <i class="fa-solid fa-search mr-1"></i> Filter Data
                </button>
            </div>
        </form>
    </div>

    <!-- ========================================== -->
    <!-- TABEL RIWAYAT TRANSAKSI LENGKAP -->
    <!-- ========================================== -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50/80 border-b border-gray-100 text-xs text-gray-500 uppercase tracking-wider">
                        <th class="p-4 font-bold">Waktu & Order ID</th>
                        <th class="p-4 font-bold">Pengirim & Penerima</th>
                        <th class="p-4 font-bold">Ekspedisi & Paket</th>
                        <th class="p-4 font-bold text-right">Biaya & Komisi Agen</th>
                        <th class="p-4 font-bold text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="text-sm divide-y divide-gray-100">
                    @forelse($pesanan as $item)
                    <tr class="hover:bg-blue-50/30 transition">

                        <!-- KOLOM 1: WAKTU & ID -->
                        <td class="p-4 align-top">
                            <p class="font-bold text-gray-800">{{ $item->created_at->format('d M Y') }}</p>
                            <p class="text-xs text-gray-500">{{ $item->created_at->format('H:i:s') }} WIB</p>
                            <div class="mt-2 text-[10px] bg-gray-100 px-2 py-1 rounded text-gray-600 font-mono inline-block border border-gray-200">
                                {{ $item->order_id }}
                            </div>
                        </td>

                        <!-- KOLOM 2: DATA PENGIRIM & PENERIMA LENGKAP -->
                        <td class="p-4 align-top min-w-[280px]">
                            <div class="mb-3 border-l-2 border-blue-400 pl-2">
                                <span class="text-[9px] font-black text-blue-500 uppercase tracking-widest"><i class="fa-solid fa-box-open mr-1"></i> Pengirim</span>
                                <p class="font-bold text-gray-800 text-xs mt-0.5">{{ $item->pengirim_nama }} <span class="font-normal text-gray-500">({{ $item->pengirim_hp }})</span></p>
                                <p class="text-[11px] text-gray-600 mt-1 leading-tight line-clamp-2" title="{{ $item->pengirim_alamat }}">{{ $item->pengirim_alamat }}</p>
                                <p class="text-[10px] text-gray-400 mt-0.5">Kodepos: {{ $item->pengirim_kodepos }}</p>
                            </div>
                            <div class="border-l-2 border-red-400 pl-2">
                                <span class="text-[9px] font-black text-red-500 uppercase tracking-widest"><i class="fa-solid fa-location-dot mr-1"></i> Penerima</span>
                                <p class="font-bold text-gray-800 text-xs mt-0.5">{{ $item->penerima_nama }} <span class="font-normal text-gray-500">({{ $item->penerima_hp }})</span></p>
                                <p class="text-[11px] text-gray-600 mt-1 leading-tight line-clamp-2" title="{{ $item->penerima_alamat }}">{{ $item->penerima_alamat }}</p>
                                <p class="text-[10px] text-gray-400 mt-0.5">Kodepos: {{ $item->penerima_kodepos }}</p>
                            </div>
                        </td>

                        <!-- KOLOM 3: EKSPEDISI & DETAIL PAKET -->
                        <td class="p-4 align-top min-w-[220px]">
                            <div class="mb-2 flex items-center">
                                @php $parsedKurir = \App\Helpers\ShippingHelper::parseShippingMethod($item->kurir); @endphp
                                @if($parsedKurir['logo_url'])
                                    <img src="{{ $parsedKurir['logo_url'] }}" alt="{{ $parsedKurir['courier_name'] }}" class="h-5 w-auto object-contain inline-block mr-2" onerror="this.style.display='none';">
                                @endif
                                <span class="font-extrabold text-gray-800 uppercase text-[11px]">{{ $item->kurir }}</span>
                            </div>

                            @if($item->awb_number)
                                <div class="inline-block bg-blue-50 text-blue-700 font-bold font-mono px-2 py-1 rounded text-xs border border-blue-200 mb-3 shadow-sm">
                                    {{ $item->awb_number }}
                                </div>
                            @else
                                <div class="inline-block bg-gray-100 text-gray-500 px-2 py-1 rounded italic text-xs mb-3">Menunggu Resi</div>
                            @endif

                            <p class="font-bold text-gray-700 text-[11px] border-t border-gray-100 pt-2 uppercase">{{ $item->deskripsi_barang }}</p>
                            <div class="mt-1 grid grid-cols-1 gap-0.5 text-[10px]">
                                <div><span class="text-gray-400">Kategori:</span> {{ $item->kategori_barang }}</div>
                                <div><span class="text-gray-400">Layanan:</span> {{ $item->layanan }}</div>
                                <div><span class="text-gray-400">Berat:</span> {{ number_format($item->berat_gram, 0, ',', '.') }} gr</div>
                                <div><span class="text-gray-400">Dimensi:</span> {{ $item->panjang_cm }}x{{ $item->lebar_cm }}x{{ $item->tinggi_cm }} cm</div>

                                @php
                                    $isCodPaket = in_array(strtolower($item->metode_pembayaran), ['cod', 'codbarang', 'cod_barang', 'cod_ongkir']);
                                    $isCodOngkirSaja = strtolower($item->metode_pembayaran) === 'cod_ongkir';

                                    // Hitung mundur untuk mendapatkan Biaya Admin COD + Asuransi
                                    $biayaAdminDanAsuransi = 0;
                                    if ($isCodPaket) {
                                        if ($isCodOngkirSaja) {
                                            $biayaAdminDanAsuransi = $item->grand_total - $item->ongkir;
                                        } else {
                                            $biayaAdminDanAsuransi = $item->grand_total - $item->ongkir - $item->nilai_barang;
                                        }
                                    }
                                @endphp

                                <!-- Status Asuransi (Jika BUKAN COD) -->
                                @if($item->asuransi && !$isCodPaket)
                                    <div class="mt-1 pt-1 border-t border-gray-100">
                                        <span class="text-gray-400">Asuransi:</span>
                                        <span class="font-bold text-green-600">Ya (Brg: Rp {{ number_format($item->nilai_barang, 0, ',', '.') }})</span>
                                    </div>
                                @elseif(!$item->asuransi && !$isCodPaket && $item->nilai_barang > 0 && $item->nilai_barang != 10000)
                                    <div class="mt-1 pt-1 border-t border-gray-100">
                                        <span class="text-gray-400">Harga Barang:</span>
                                        <span class="font-medium text-gray-700">Rp {{ number_format($item->nilai_barang, 0, ',', '.') }}</span>
                                    </div>
                                @endif

                                <!-- Blok Khusus Info COD -->
                                @if($isCodPaket)
                                    <div class="mt-2 p-2 bg-red-50 border border-red-100 rounded shadow-sm text-[9px]">
                                        <div class="text-red-600 font-bold mb-1 flex items-center border-b border-red-200 pb-1">
                                            <i class="fa-solid fa-hand-holding-dollar mr-1.5"></i>
                                            {{ $isCodOngkirSaja ? 'COD ONGKIR SAJA' : 'COD BARANG & ONGKIR' }}
                                        </div>

                                        <div class="space-y-0.5 mt-1">
                                            <div class="flex justify-between" {!! $isCodOngkirSaja ? 'title="Acuan Klaim Asuransi"' : '' !!}>
                                                <span class="text-gray-500">Nilai Barang:</span>
                                                <span class="font-medium">Rp {{ number_format($item->nilai_barang, 0, ',', '.') }}</span>
                                            </div>

                                            <div class="flex justify-between">
                                                <span class="text-gray-500">Admin + Asuransi:</span>
                                                <span class="font-medium">+ Rp {{ number_format($biayaAdminDanAsuransi, 0, ',', '.') }}</span>
                                            </div>

                                            <div class="flex justify-between text-red-700 font-black mt-1 pt-1 border-t border-red-200/50">
                                                <span>TAGIHAN KURIR:</span>
                                                <span>Rp {{ number_format($item->grand_total, 0, ',', '.') }}</span>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </td>

                        <!-- KOLOM 4: HARGA & KOMISI FEE AGEN -->
                        <td class="p-4 align-top text-right min-w-[150px]">
                            <p class="font-black text-sm text-gray-800">Rp {{ number_format($item->ongkir, 0, ',', '.') }}</p>
                            <p class="text-[9px] text-gray-400 uppercase tracking-wider mb-2">Via: <strong class="text-gray-600">{{ str_replace('_', ' ', $item->metode_pembayaran) }}</strong></p>

                            <!-- Indikator Status -->
                            <div class="mb-3">
                                @if(in_array($item->status, ['booking_created', 'paid']))
                                    <span class="bg-green-100 text-green-700 font-bold px-2 py-0.5 rounded text-[9px] uppercase border border-green-200 shadow-sm">Lunas / Sukses</span>
                                @elseif($item->status == 'menunggu_pembayaran')
                                    <span class="bg-orange-100 text-orange-700 font-bold px-2 py-0.5 rounded text-[9px] uppercase border border-orange-200 shadow-sm">Pending</span>
                                @else
                                    <span class="bg-red-100 text-red-700 font-bold px-2 py-0.5 rounded text-[9px] uppercase border border-red-200 shadow-sm">{{ $item->status }}</span>
                                @endif
                            </div>

                            <!-- BOX FEE KOMISI -->
                            @if($item->komisi_agen > 0)
                            <div class="p-2 bg-green-50 border border-green-200 rounded-lg inline-block text-right shadow-sm">
                                <p class="text-[9px] text-green-600 font-bold uppercase mb-0.5"><i class="fa-solid fa-coins mr-1"></i> Fee Komisi</p>
                                <p class="text-sm font-black text-green-700">+ Rp {{ number_format($item->komisi_agen, 0, ',', '.') }}</p>
                            </div>
                            @endif
                        </td>

                        <!-- KOLOM 5: AKSI -->
                        <td class="p-4 align-top min-w-[120px]">
                            <div class="flex flex-wrap gap-2 justify-end">
                                @php $resiTrack = $item->awb_number ?? $item->order_id; @endphp
                                <a href="https://tokosancaka.com/tracking/search?resi={{ $resiTrack }}" target="_blank" class="bg-white border border-gray-200 hover:bg-blue-50 hover:border-blue-200 hover:text-blue-600 text-gray-500 w-8 h-8 rounded-lg flex items-center justify-center shadow-sm transition" title="Lacak Paket">
                                    <i class="fa-solid fa-location-crosshairs"></i>
                                </a>

                                <a href="{{ route('customer.pesanan-autokirim.cetak', $item->id) }}" target="_blank" class="bg-white border border-gray-200 hover:bg-green-50 hover:border-green-200 hover:text-green-600 text-gray-500 w-8 h-8 rounded-lg flex items-center justify-center shadow-sm transition" title="Cetak / Download Resi">
                                    <i class="fa-solid fa-print"></i>
                                </a>

                                @if(in_array($item->status, ['booking_created', 'menunggu_pembayaran']))
                                    <button type="button" onclick="confirmCancel('{{ route('customer.pesanan-autokirim.cancel', $item->id) }}')" class="bg-white border border-gray-200 hover:bg-red-50 hover:border-red-200 hover:text-red-600 text-gray-500 w-8 h-8 rounded-lg flex items-center justify-center shadow-sm transition" title="Batalkan Pesanan">
                                        <i class="fa-solid fa-ban"></i>
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="p-10 text-center text-gray-500">
                            <i class="fa-solid fa-folder-open text-5xl mb-3 text-gray-300"></i>
                            <p class="font-medium">Anda belum memiliki riwayat transaksi Autokirim.</p>
                            <a href="{{ route('customer.pesanan-autokirim.create') }}" class="inline-block mt-4 text-blue-600 font-bold hover:underline">Kirim Paket Sekarang</a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-gray-100 bg-gray-50/50">
            {{ $pesanan->links() }}
        </div>
    </div>

    <!-- Form Tersembunyi untuk Batal -->
    <form id="actionForm" method="POST" class="hidden">
        @csrf
        <input type="hidden" name="_method" id="actionMethod">
    </form>

    <script>
        function confirmCancel(url) {
            if(confirm('Apakah Anda yakin ingin membatalkan pesanan ini? Aksi ini tidak dapat dikembalikan.')) {
                let form = document.getElementById('actionForm');
                form.action = url;
                document.getElementById('actionMethod').value = 'POST';
                form.submit();
            }
        }
    </script>
</div>
@endsection
