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
    <!-- TABEL DATA LENGKAP & AKSI -->
    <!-- ========================================== -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">

        <form action="{{ route('admin.pesanan-autokirim.bulk_destroy') }}" method="POST" id="bulkDeleteForm">
            @csrf

            <!-- Tombol Bulk Delete -->
            <div class="p-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
                <button type="button" onclick="confirmBulkDelete()" class="bg-red-500 hover:bg-red-600 text-white text-xs font-bold py-2 px-4 rounded shadow-sm transition">
                    <i class="fa-solid fa-trash-can mr-1"></i> Hapus yang Dipilih
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200 text-xs text-gray-500 uppercase tracking-wider">
                            <th class="p-4 w-10 text-center">
                                <input type="checkbox" id="selectAll" class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                            </th>
                            <th class="p-4 font-bold">Waktu & Order ID</th>
                            <th class="p-4 font-bold">Rute & Resi</th>
                            <th class="p-4 font-bold">Detail Paket</th>
                            <th class="p-4 font-bold text-right">Harga & Status</th>
                            <!-- Header Aksi Dipindah ke Sini -->
                            <th class="p-4 font-bold text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm divide-y divide-gray-100">
                        @forelse($pesanan as $item)
                        <tr class="hover:bg-gray-50/50 transition">
                            <!-- Checkbox Bulk -->
                            <td class="p-4 align-top text-center">
                                <input type="checkbox" name="ids[]" value="{{ $item->id }}" class="rowCheckbox w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                            </td>

                            <!-- KOLOM 1: WAKTU & ID -->
                            <td class="p-4 align-top">
                                <p class="font-bold text-gray-800">{{ $item->created_at->format('d M Y') }}</p>
                                <p class="text-xs text-gray-500">{{ $item->created_at->format('H:i:s') }} WIB</p>
                                <div class="mt-2 text-[11px] bg-gray-100 px-2 py-1 rounded text-gray-600 font-mono inline-block">
                                    {{ $item->order_id }}
                                </div>
                            </td>

                            <!-- KOLOM 2: RUTE & RESI -->
                            <td class="p-4 align-top">
                                <div class="mb-2">
                                    @php $parsedKurir = \App\Helpers\ShippingHelper::parseShippingMethod($item->kurir); @endphp
                                    @if($parsedKurir['logo_url'])
                                        <img src="{{ $parsedKurir['logo_url'] }}" alt="{{ $parsedKurir['courier_name'] }}" class="h-6 w-auto object-contain inline-block mr-2" onerror="this.style.display='none';">
                                    @endif
                                    <span class="font-bold text-gray-800 uppercase text-xs">{{ $item->kurir }}</span>
                                </div>

                                @if($item->awb_number)
                                    <div class="inline-block bg-blue-50 text-blue-700 font-bold font-mono px-2 py-1 rounded text-xs border border-blue-200 mb-2">
                                        {{ $item->awb_number }}
                                    </div>
                                @else
                                    <div class="inline-block bg-gray-100 text-gray-500 px-2 py-1 rounded italic text-xs mb-2">Menunggu Resi</div>
                                @endif

                                <p class="text-xs font-semibold text-gray-700"><i class="fa-solid fa-arrow-right-from-bracket text-blue-500 mr-1"></i> {{ $item->pengirim_nama }}</p>
                                <p class="text-xs font-semibold text-gray-700 mt-1"><i class="fa-solid fa-location-dot text-red-500 mr-1"></i> {{ $item->penerima_nama }}</p>
                            </td>

                            <!-- KOLOM 3: DETAIL PAKET -->
                            <td class="p-4 align-top min-w-[200px]">
                                <p class="font-bold text-gray-800 text-xs">{{ $item->deskripsi_barang }}</p>
                                <div class="mt-1 grid grid-cols-1 gap-1 text-[11px]">
                                    <div><span class="text-gray-400">Berat:</span> {{ number_format($item->berat_gram) }} gr</div>
                                    <div><span class="text-gray-400">Dimensi:</span> {{ $item->panjang_cm }}x{{ $item->lebar_cm }}x{{ $item->tinggi_cm }} cm</div>
                                    <div><span class="text-gray-400">Asuransi:</span> {!! $item->asuransi ? '<span class="text-green-500 font-bold">Ya</span>' : '<span class="text-red-400">Tidak</span>' !!}</div>
                                </div>
                            </td>

                            <!-- KOLOM 4: HARGA & STATUS -->
                            <td class="p-4 align-top text-right">
                                <p class="font-black text-lg text-blue-700">Rp {{ number_format($item->ongkir, 0, ',', '.') }}</p>
                                <p class="text-[9px] text-gray-400 uppercase tracking-wider mb-2">Via: <strong class="text-gray-600">{{ str_replace('_', ' ', $item->metode_pembayaran) }}</strong></p>

                                @if(in_array($item->status, ['booking_created', 'paid']))
                                    <span class="bg-green-100 text-green-700 font-bold px-2 py-1 rounded text-[10px] uppercase">Lunas & Diproses</span>
                                @elseif($item->status == 'menunggu_pembayaran')
                                    <span class="bg-orange-100 text-orange-700 font-bold px-2 py-1 rounded text-[10px] uppercase">Belum Bayar</span>
                                @else
                                    <span class="bg-red-100 text-red-700 font-bold px-2 py-1 rounded text-[10px] uppercase">{{ $item->status }}</span>
                                @endif
                            </td>

                            <!-- KOLOM AKSI (Dipindah ke Sini) -->
                            <td class="p-4 align-top min-w-[150px]">
                                <div class="flex flex-wrap gap-2 justify-end"> <!-- Tambahan justify-end agar rapi di kanan -->
                                    <!-- 1. Tracking -->
                                    @php $resiTrack = $item->awb_number ?? $item->order_id; @endphp
                                    <a href="https://tokosancaka.com/tracking/search?resi={{ $resiTrack }}" target="_blank" class="bg-blue-100 hover:bg-blue-200 text-blue-700 w-8 h-8 rounded flex items-center justify-center shadow-sm" title="Lacak Paket">
                                        <i class="fa-solid fa-location-crosshairs"></i>
                                    </a>

                                    <!-- 2. Download / Cetak Resi -->
                                    <a href="{{ route('admin.pesanan-autokirim.cetak', $item->id) }}" target="_blank" class="bg-green-100 hover:bg-green-200 text-green-700 w-8 h-8 rounded flex items-center justify-center shadow-sm" title="Cetak / Download Resi">
                                        <i class="fa-solid fa-print"></i>
                                    </a>

                                    <!-- 3. Batal (Locked logic) -->
                                    @if(in_array($item->status, ['booking_created', 'menunggu_pembayaran']))
                                        <button type="button" onclick="confirmCancel('{{ route('admin.pesanan-autokirim.cancel', $item->id) }}')" class="bg-orange-100 hover:bg-orange-200 text-orange-700 w-8 h-8 rounded flex items-center justify-center shadow-sm" title="Batalkan Pesanan">
                                            <i class="fa-solid fa-ban"></i>
                                        </button>
                                    @else
                                        <button type="button" disabled class="bg-gray-100 text-gray-400 w-8 h-8 rounded flex items-center justify-center cursor-not-allowed" title="Sudah diproses, tidak bisa dibatalkan">
                                            <i class="fa-solid fa-lock"></i>
                                        </button>
                                    @endif

                                    <!-- 4. Hapus Satuan -->
                                    <button type="button" onclick="confirmDelete('{{ route('admin.pesanan-autokirim.destroy', $item->id) }}')" class="bg-red-100 hover:bg-red-200 text-red-700 w-8 h-8 rounded flex items-center justify-center shadow-sm" title="Hapus Data">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </div>
                            </td>

                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="p-8 text-center text-gray-500">Belum ada riwayat transaksi Autokirim.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </form>

        <div class="p-4 border-t border-gray-100 bg-gray-50">
            {{ $pesanan->links() }}
        </div>
    </div>

    <!-- Form Tersembunyi untuk Hapus & Cancel Satuan -->
    <form id="actionForm" method="POST" class="hidden">
        @csrf
        <input type="hidden" name="_method" id="actionMethod">
    </form>

    <!-- Javascript untuk Fitur Checkbox & Konfirmasi -->
    <script>
        // Checkbox Semua
        document.getElementById('selectAll').addEventListener('change', function(e) {
            let checkboxes = document.querySelectorAll('.rowCheckbox');
            checkboxes.forEach(cb => cb.checked = e.target.checked);
        });

        function confirmBulkDelete() {
            let checked = document.querySelectorAll('.rowCheckbox:checked');
            if(checked.length === 0) {
                alert('Pilih minimal satu pesanan untuk dihapus!');
                return;
            }
            if(confirm('Apakah Anda yakin ingin menghapus ' + checked.length + ' data terpilih?')) {
                document.getElementById('bulkDeleteForm').submit();
            }
        }

        function confirmDelete(url) {
            if(confirm('Yakin ingin menghapus data pesanan ini secara permanen?')) {
                let form = document.getElementById('actionForm');
                form.action = url;
                document.getElementById('actionMethod').value = 'DELETE';
                form.submit();
            }
        }

        function confirmCancel(url) {
            if(confirm('Yakin ingin membatalkan pesanan ini?')) {
                let form = document.getElementById('actionForm');
                form.action = url;
                document.getElementById('actionMethod').value = 'POST';
                form.submit();
            }
        }
    </script>

</div>
@endsection
