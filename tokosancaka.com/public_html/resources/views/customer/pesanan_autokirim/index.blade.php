@extends('layouts.customer')

@section('content')
<div class="max-w-6xl mx-auto px-4 py-8 font-sans">

    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">Riwayat Pengiriman</h1>
            <p class="text-gray-500 mt-1 text-sm">Pantau status, cetak resi, dan kelola paket Anda di sini.</p>
        </div>
        <a href="{{ route('customer.pesanan-autokirim.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 px-5 rounded-xl shadow-md transition flex items-center gap-2 text-sm">
            <i class="fa-solid fa-plus"></i> Kirim Paket Baru
        </a>
    </div>

    <!-- ========================================== -->
    <!-- CARD STATISTIK -->
    <!-- ========================================== -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100">
            <p class="text-xs text-gray-500 font-bold uppercase tracking-wider mb-1">Total Resi</p>
            <h3 class="text-2xl font-black text-gray-800">{{ number_format($totalTransaksi) }}</h3>
        </div>
        <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100">
            <p class="text-xs text-gray-500 font-bold uppercase tracking-wider mb-1">Total Biaya Ongkir</p>
            <h3 class="text-2xl font-black text-blue-600">Rp {{ number_format($totalOngkir, 0, ',', '.') }}</h3>
        </div>
        <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100">
            <p class="text-xs text-gray-500 font-bold uppercase tracking-wider mb-1">Berhasil</p>
            <h3 class="text-2xl font-black text-green-600">{{ number_format($totalBerhasil) }}</h3>
        </div>
        <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100">
            <p class="text-xs text-gray-500 font-bold uppercase tracking-wider mb-1">Pending / Batal</p>
            <h3 class="text-2xl font-black text-orange-500">{{ number_format($totalPending) }}</h3>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- FILTER PENCARIAN -->
    <!-- ========================================== -->
    <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 mb-6">
        <form action="{{ route('customer.pesanan-autokirim.index') }}" method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Cari (Resi / Penerima)</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Ketik kata kunci..." class="w-full bg-gray-50 border border-gray-200 rounded-xl text-sm px-4 py-2.5 focus:ring-1 focus:ring-blue-500 outline-none transition">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Status</label>
                <select name="status" class="w-full bg-gray-50 border border-gray-200 rounded-xl text-sm px-4 py-2.5 focus:ring-1 focus:ring-blue-500 outline-none transition">
                    <option value="">Semua Status</option>
                    <option value="booking_created" {{ request('status') == 'booking_created' ? 'selected' : '' }}>Sukses (Booking Created)</option>
                    <option value="menunggu_pembayaran" {{ request('status') == 'menunggu_pembayaran' ? 'selected' : '' }}>Pending / Menunggu Bayar</option>
                    <option value="batal" {{ request('status') == 'batal' ? 'selected' : '' }}>Dibatalkan</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Tanggal (YYYY-MM-DD)</label>
                <input type="text" name="date_range" value="{{ request('date_range') }}" placeholder="Contoh: 2026-07-21" class="w-full bg-gray-50 border border-gray-200 rounded-xl text-sm px-4 py-2.5 focus:ring-1 focus:ring-blue-500 outline-none transition">
            </div>
            <div>
                <button type="submit" class="w-full bg-gray-900 hover:bg-black text-white font-bold rounded-xl px-4 py-2.5 text-sm transition shadow-md">
                    <i class="fa-solid fa-search mr-1"></i> Cari Data
                </button>
            </div>
        </form>
    </div>

    <!-- ========================================== -->
    <!-- TABEL RIWAYAT TRANSAKSI -->
    <!-- ========================================== -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50/80 border-b border-gray-100 text-xs text-gray-500 uppercase tracking-wider">
                        <th class="p-4 font-bold">Waktu & Order ID</th>
                        <th class="p-4 font-bold">Rute & Resi</th>
                        <th class="p-4 font-bold">Detail Paket</th>
                        <th class="p-4 font-bold text-right">Harga & Status</th>
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

                        <!-- KOLOM 2: RUTE & RESI -->
                        <td class="p-4 align-top">
                            <div class="mb-2 flex items-center">
                                @php $parsedKurir = \App\Helpers\ShippingHelper::parseShippingMethod($item->kurir); @endphp
                                @if($parsedKurir['logo_url'])
                                    <img src="{{ $parsedKurir['logo_url'] }}" alt="{{ $parsedKurir['courier_name'] }}" class="h-5 w-auto object-contain inline-block mr-2" onerror="this.style.display='none';">
                                @endif
                                <span class="font-extrabold text-gray-800 uppercase text-[11px]">{{ $item->kurir }}</span>
                            </div>

                            @if($item->awb_number)
                                <div class="inline-block bg-blue-50 text-blue-700 font-bold font-mono px-2 py-1 rounded text-xs border border-blue-200 mb-2 shadow-sm">
                                    {{ $item->awb_number }}
                                </div>
                            @else
                                <div class="inline-block bg-gray-100 text-gray-500 px-2 py-1 rounded italic text-xs mb-2">Menunggu Resi</div>
                            @endif

                            <p class="text-xs font-semibold text-gray-600 mt-1"><i class="fa-solid fa-location-dot text-red-500 mr-1"></i> Ke: {{ $item->penerima_nama }}</p>
                        </td>

                        <!-- KOLOM 3: DETAIL PAKET -->
                        <td class="p-4 align-top min-w-[180px]">
                            <p class="font-bold text-gray-800 text-xs">{{ $item->deskripsi_barang }}</p>
                            <div class="mt-1.5 grid grid-cols-1 gap-1 text-[11px]">
                                <div><span class="text-gray-400">Berat:</span> {{ number_format($item->berat_gram) }} gr</div>
                                <div><span class="text-gray-400">Dimensi:</span> {{ $item->panjang_cm }}x{{ $item->lebar_cm }}x{{ $item->tinggi_cm }} cm</div>
                                <div><span class="text-gray-400">Asuransi:</span> {!! $item->asuransi ? '<span class="text-green-500 font-bold">Ya</span>' : '<span class="text-gray-400">Tidak</span>' !!}</div>
                            </div>
                        </td>

                        <!-- KOLOM 4: HARGA & STATUS -->
                        <td class="p-4 align-top text-right">
                            <p class="font-black text-base text-blue-700">Rp {{ number_format($item->ongkir, 0, ',', '.') }}</p>
                            <p class="text-[9px] text-gray-400 uppercase tracking-wider mb-2">Via: <strong class="text-gray-600">{{ str_replace('_', ' ', $item->metode_pembayaran) }}</strong></p>

                            @if(in_array($item->status, ['booking_created', 'paid']))
                                <span class="bg-green-100 text-green-700 font-bold px-2.5 py-1 rounded-full text-[10px] uppercase border border-green-200 shadow-sm">Lunas & Diproses</span>
                            @elseif($item->status == 'menunggu_pembayaran')
                                <span class="bg-orange-100 text-orange-700 font-bold px-2.5 py-1 rounded-full text-[10px] uppercase border border-orange-200 shadow-sm">Belum Bayar</span>
                            @else
                                <span class="bg-red-100 text-red-700 font-bold px-2.5 py-1 rounded-full text-[10px] uppercase border border-red-200 shadow-sm">{{ $item->status }}</span>
                            @endif
                        </td>

                        <!-- KOLOM 5: AKSI -->
                        <td class="p-4 align-top min-w-[130px]">
                            <div class="flex flex-wrap gap-2 justify-end">
                                <!-- 1. Tracking -->
                                @php $resiTrack = $item->awb_number ?? $item->order_id; @endphp
                                <a href="https://tokosancaka.com/tracking/search?resi={{ $resiTrack }}" target="_blank" class="bg-white border border-gray-200 hover:bg-blue-50 hover:border-blue-200 hover:text-blue-600 text-gray-500 w-8 h-8 rounded-lg flex items-center justify-center shadow-sm transition" title="Lacak Paket">
                                    <i class="fa-solid fa-location-crosshairs"></i>
                                </a>

                                <!-- 2. Download / Cetak Resi -->
                                <a href="{{ route('customer.pesanan-autokirim.cetak', $item->id) }}" target="_blank" class="bg-white border border-gray-200 hover:bg-green-50 hover:border-green-200 hover:text-green-600 text-gray-500 w-8 h-8 rounded-lg flex items-center justify-center shadow-sm transition" title="Cetak / Download Resi">
                                    <i class="fa-solid fa-print"></i>
                                </a>

                                <!-- 3. Batal (Khusus Customer) -->
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
