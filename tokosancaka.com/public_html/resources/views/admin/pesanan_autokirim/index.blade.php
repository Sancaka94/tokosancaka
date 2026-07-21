@extends('layouts.admin')

@section('content')
<div class="container-fluid py-4">
    <h2 class="font-bold text-2xl mb-4 text-gray-800">Riwayat Transaksi Autokirim</h2>

    <!-- ========================================== -->
    <!-- CARD STATISTIK -->
    <!-- ========================================== -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 border-l-4 border-l-blue-500">
            <p class="text-xs text-gray-500 font-bold uppercase tracking-wide">Total Transaksi</p>
            <h3 class="text-2xl font-black text-gray-800">{{ number_format($totalTransaksi) }} <span class="text-sm font-normal">Resi</span></h3>
        </div>
        <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 border-l-4 border-l-green-500">
            <p class="text-xs text-gray-500 font-bold uppercase tracking-wide">Total Omzet Ongkir</p>
            <h3 class="text-2xl font-black text-green-600">Rp {{ number_format($totalOngkir, 0, ',', '.') }}</h3>
        </div>
        <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 border-l-4 border-l-indigo-500">
            <p class="text-xs text-gray-500 font-bold uppercase tracking-wide">Transaksi Berhasil</p>
            <h3 class="text-2xl font-black text-gray-800">{{ number_format($totalBerhasil) }}</h3>
        </div>
        <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 border-l-4 border-l-orange-500">
            <p class="text-xs text-gray-500 font-bold uppercase tracking-wide">Menunggu Pembayaran</p>
            <h3 class="text-2xl font-black text-gray-800">{{ number_format($totalPending) }}</h3>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- FILTER PENCARIAN -->
    <!-- ========================================== -->
    <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 mb-6">
        <form action="{{ route('admin.pesanan-autokirim.index') }}" method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Cari (Resi / Nama / HP)</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Ketik kata kunci..." class="w-full border-gray-200 rounded-lg text-sm px-3 py-2">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Status Pembayaran</label>
                <select name="status" class="w-full border-gray-200 rounded-lg text-sm px-3 py-2">
                    <option value="">Semua Status</option>
                    <option value="booking_created" {{ request('status') == 'booking_created' ? 'selected' : '' }}>Sukses (Booking Created)</option>
                    <option value="menunggu_pembayaran" {{ request('status') == 'menunggu_pembayaran' ? 'selected' : '' }}>Pending / Menunggu Bayar</option>
                    <option value="batal" {{ request('status') == 'batal' ? 'selected' : '' }}>Dibatalkan</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Tanggal (YYYY-MM-DD)</label>
                <input type="text" name="date_range" value="{{ request('date_range') }}" placeholder="Contoh: 2026-07-21" class="w-full border-gray-200 rounded-lg text-sm px-3 py-2">
            </div>
            <div>
                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg px-4 py-2 text-sm transition">
                    <i class="fa-solid fa-filter mr-1"></i> Terapkan Filter
                </button>
            </div>
        </form>
    </div>

    <!-- ========================================== -->
    <!-- TABEL DATA LENGKAP -->
    <!-- ========================================== -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200 text-xs text-gray-500 uppercase tracking-wider">
                        <th class="p-4 font-bold">Waktu & Order ID</th>
                        <th class="p-4 font-bold">Rute Pengiriman (Asal -> Tujuan)</th>
                        <th class="p-4 font-bold">Detail Paket</th>
                        <th class="p-4 font-bold">Ekspedisi & Resi</th>
                        <th class="p-4 font-bold text-right">Pembayaran & Status</th>
                    </tr>
                </thead>
                <tbody class="text-sm divide-y divide-gray-100">
                    @forelse($pesanan as $item)
                    <tr class="hover:bg-gray-50/50 transition">
                        <!-- KOLOM 1: WAKTU & ID -->
                        <td class="p-4 align-top">
                            <p class="font-bold text-gray-800">{{ $item->created_at->format('d M Y') }}</p>
                            <p class="text-xs text-gray-500">{{ $item->created_at->format('H:i:s') }} WIB</p>
                            <div class="mt-2 text-[11px] bg-gray-100 px-2 py-1 rounded text-gray-600 font-mono inline-block">
                                {{ $item->order_id }}
                            </div>
                        </td>

                        <!-- KOLOM 2: PENGIRIM & PENERIMA LENGKAP -->
                        <td class="p-4 align-top min-w-[280px]">
                            <div class="mb-3 border-l-2 border-blue-400 pl-2">
                                <span class="text-[10px] font-bold text-blue-500 uppercase">PENGIRIM:</span>
                                <p class="font-bold text-gray-800">{{ $item->pengirim_nama }} <span class="text-xs font-normal text-gray-500">({{ $item->pengirim_hp }})</span></p>
                                <p class="text-xs text-gray-600 line-clamp-2" title="{{ $item->pengirim_alamat }}">{{ $item->pengirim_alamat }}</p>
                                <p class="text-[10px] text-gray-400">Kodepos: {{ $item->pengirim_kodepos }}</p>
                            </div>
                            <div class="border-l-2 border-red-400 pl-2">
                                <span class="text-[10px] font-bold text-red-500 uppercase">PENERIMA:</span>
                                <p class="font-bold text-gray-800">{{ $item->penerima_nama }} <span class="text-xs font-normal text-gray-500">({{ $item->penerima_hp }})</span></p>
                                <p class="text-xs text-gray-600 line-clamp-2" title="{{ $item->penerima_alamat }}">{{ $item->penerima_alamat }}</p>
                                <p class="text-[10px] text-gray-400">Kodepos: {{ $item->penerima_kodepos }}</p>
                            </div>
                        </td>

                        <!-- KOLOM 3: DETAIL PAKET (Dimensi, Berat, Asuransi) -->
                        <td class="p-4 align-top min-w-[200px]">
                            <p class="font-bold text-gray-800">{{ $item->deskripsi_barang }}</p>
                            <p class="text-[11px] text-gray-500">{{ $item->kategori_barang }}</p>
                            <div class="mt-2 grid grid-cols-2 gap-x-2 text-xs">
                                <div><span class="text-gray-400">Berat:</span> {{ number_format($item->berat_gram) }} gr</div>
                                <div><span class="text-gray-400">Dimensi:</span> {{ $item->panjang_cm }}x{{ $item->lebar_cm }}x{{ $item->tinggi_cm }} cm</div>
                                <div><span class="text-gray-400">Nilai:</span> Rp {{ number_format($item->nilai_barang, 0, ',', '.') }}</div>
                                <div><span class="text-gray-400">Asuransi:</span> {!! $item->asuransi ? '<span class="text-green-500 font-bold">Ya</span>' : '<span class="text-red-400">Tidak</span>' !!}</div>
                            </div>
                        </td>

                        <!-- KOLOM 4: KURIR & RESI -->
                        <td class="p-4 align-top">
                            <p class="font-bold text-gray-800 uppercase">{{ $item->kurir }}</p>
                            <p class="text-[11px] text-gray-500 mb-2">{{ $item->layanan }}</p>
                            @if($item->awb_number)
                                <span class="bg-blue-50 text-blue-700 font-bold font-mono px-2 py-1 rounded text-sm border border-blue-200">
                                    {{ $item->awb_number }}
                                </span>
                            @else
                                <span class="bg-gray-100 text-gray-500 text-xs px-2 py-1 rounded italic">Menunggu Resi</span>
                            @endif
                        </td>

                        <!-- KOLOM 5: HARGA & STATUS -->
                        <td class="p-4 align-top text-right">
                            <p class="font-black text-lg text-blue-700">Rp {{ number_format($item->ongkir, 0, ',', '.') }}</p>
                            <p class="text-[10px] text-gray-400 uppercase tracking-wider mb-2">Via: <strong class="text-gray-600">{{ str_replace('_', ' ', $item->metode_pembayaran) }}</strong></p>

                            @if($item->status == 'booking_created' || $item->status == 'paid')
                                <span class="bg-green-100 text-green-700 font-bold px-2.5 py-1 rounded-full text-[10px] uppercase">Lunas & Diproses</span>
                            @elseif($item->status == 'menunggu_pembayaran')
                                <span class="bg-orange-100 text-orange-700 font-bold px-2.5 py-1 rounded-full text-[10px] uppercase">Belum Bayar</span>
                            @else
                                <span class="bg-red-100 text-red-700 font-bold px-2.5 py-1 rounded-full text-[10px] uppercase">{{ $item->status }}</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="p-8 text-center text-gray-500">
                            <i class="fa-solid fa-box-open text-4xl mb-3 text-gray-300"></i>
                            <p>Belum ada riwayat transaksi Autokirim.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Paginasi -->
        <div class="p-4 border-t border-gray-100 bg-gray-50">
            {{ $pesanan->links() }}
        </div>
    </div>
</div>
@endsection
