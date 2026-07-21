@extends('layouts.admin')

@section('content')
<div class="container-fluid py-4">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h2 class="font-bold text-2xl text-gray-800">Riwayat Transaksi & Profit Autokirim</h2>
            <p class="text-gray-500 text-sm mt-1">Sistem Bagi Hasil Otomatis (60% Laba Sancaka : 40% Komisi Agen)</p>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- CARD STATISTIK PROFIT SHARING -->
    <!-- ========================================== -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 border-l-4 border-l-gray-500 relative overflow-hidden">
            <p class="text-[10px] text-gray-500 font-bold uppercase tracking-wide">Total Transaksi</p>
            <h3 class="text-xl font-black text-gray-800">{{ number_format($totalTransaksi) }} <span class="text-sm font-normal">Resi</span></h3>
            <p class="text-[10px] font-semibold text-gray-500 mt-1">Rp {{ number_format($totalOngkir, 0, ',', '.') }} (Omzet Ongkir)</p>
        </div>

        <div class="bg-blue-50 p-4 rounded-xl shadow-sm border border-blue-200 border-l-4 border-l-blue-500 relative overflow-hidden">
            <p class="text-[10px] text-blue-600 font-bold uppercase tracking-wide">Cashback Logistik (Kotor)</p>
            <h3 class="text-xl font-black text-blue-700">Rp {{ number_format($stats['cashback_pusat'], 0, ',', '.') }}</h3>
            <p class="text-[10px] font-semibold text-blue-500 mt-1">Keuntungan kotor dari Ekspedisi</p>
            <i class="fa-solid fa-hand-holding-dollar absolute -right-3 -bottom-3 text-5xl text-blue-500 opacity-10"></i>
        </div>

        <div class="bg-orange-50 p-4 rounded-xl shadow-sm border border-orange-200 border-l-4 border-l-orange-500 relative overflow-hidden">
            <p class="text-[10px] text-orange-600 font-bold uppercase tracking-wide">Jatah Agen (40%)</p>
            <h3 class="text-xl font-black text-orange-700">Rp {{ number_format($stats['komisi_agen'], 0, ',', '.') }}</h3>
            <p class="text-[10px] font-semibold text-orange-500 mt-1">Total Fee dibagikan ke Agen</p>
            <i class="fa-solid fa-users absolute -right-3 -bottom-3 text-5xl text-orange-500 opacity-10"></i>
        </div>

        <div class="bg-green-50 p-4 rounded-xl shadow-sm border border-green-200 border-l-4 border-l-green-600 relative overflow-hidden">
            <p class="text-[10px] text-green-700 font-bold uppercase tracking-wide">Laba Bersih Sancaka (60%)</p>
            <h3 class="text-xl font-black text-green-800">Rp {{ number_format($stats['laba_sancaka'], 0, ',', '.') }}</h3>
            <p class="text-[10px] font-semibold text-green-600 mt-1">Net profit masuk kas pusat</p>
            <i class="fa-solid fa-vault absolute -right-3 -bottom-3 text-5xl text-green-600 opacity-10"></i>
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
                            <th class="p-4 font-bold min-w-[280px]">Rute & Resi</th>
                            <th class="p-4 font-bold min-w-[180px]">Detail Paket</th>
                            <th class="p-4 font-bold min-w-[200px]">Status & Rincian Profit</th>
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
                                <div class="mt-2 text-[10px] bg-gray-100 px-2 py-1 rounded text-gray-600 font-mono inline-block border border-gray-200">
                                    {{ $item->order_id }}
                                </div>
                            </td>

                            <!-- KOLOM 2: RUTE & RESI LENGKAP -->
                            <td class="p-4 align-top min-w-[280px]">
                                <div class="mb-3 flex items-center">
                                    @php $parsedKurir = \App\Helpers\ShippingHelper::parseShippingMethod($item->kurir); @endphp
                                    @if($parsedKurir['logo_url'])
                                        <img src="{{ $parsedKurir['logo_url'] }}" alt="{{ $parsedKurir['courier_name'] }}" class="h-6 w-auto object-contain inline-block mr-2" onerror="this.style.display='none';">
                                    @endif
                                    <span class="font-bold text-gray-800 uppercase text-xs">{{ $item->kurir }}</span>
                                </div>

                                @if($item->awb_number)
                                    <div class="inline-block bg-blue-50 text-blue-700 font-bold font-mono px-2 py-1 rounded text-xs border border-blue-200 mb-4 shadow-sm">
                                        {{ $item->awb_number }}
                                    </div>
                                @else
                                    <div class="inline-block bg-gray-100 text-gray-500 px-2 py-1 rounded italic text-xs mb-4">Menunggu Resi</div>
                                @endif

                                <!-- DATA PENGIRIM LENGKAP -->
                                <div class="mb-3 border-l-2 border-blue-400 pl-2">
                                    <span class="text-[9px] font-black text-blue-500 uppercase tracking-widest"><i class="fa-solid fa-box-open mr-1"></i> Pengirim</span>
                                    <p class="font-bold text-gray-800 text-xs mt-0.5">{{ $item->pengirim_nama }} <span class="font-normal text-gray-500">({{ $item->pengirim_hp }})</span></p>
                                    <p class="text-[11px] text-gray-600 mt-1 leading-tight line-clamp-2" title="{{ $item->pengirim_alamat }}">{{ $item->pengirim_alamat }}</p>
                                    <p class="text-[10px] text-gray-400 mt-0.5">Kodepos: {{ $item->pengirim_kodepos }}</p>
                                </div>

                                <!-- DATA PENERIMA LENGKAP -->
                                <div class="border-l-2 border-red-400 pl-2">
                                    <span class="text-[9px] font-black text-red-500 uppercase tracking-widest"><i class="fa-solid fa-location-dot mr-1"></i> Penerima</span>
                                    <p class="font-bold text-gray-800 text-xs mt-0.5">{{ $item->penerima_nama }} <span class="font-normal text-gray-500">({{ $item->penerima_hp }})</span></p>
                                    <p class="text-[11px] text-gray-600 mt-1 leading-tight line-clamp-2" title="{{ $item->penerima_alamat }}">{{ $item->penerima_alamat }}</p>
                                    <p class="text-[10px] text-gray-400 mt-0.5">Kodepos: {{ $item->penerima_kodepos }}</p>
                                </div>
                            </td>

                            <!-- KOLOM 3: DETAIL PAKET -->
                            <td class="p-4 align-top">
                                <p class="font-bold text-gray-800 text-xs">{{ $item->deskripsi_barang }}</p>
                                <div class="mt-1 grid grid-cols-1 gap-1 text-[11px]">
                                    <div><span class="text-gray-400">Layanan:</span> {{ $item->layanan }}</div>
                                    <div><span class="text-gray-400">Berat:</span> {{ number_format($item->berat_gram) }} gr</div>
                                    <div><span class="text-gray-400">Dimensi:</span> {{ $item->panjang_cm }}x{{ $item->lebar_cm }}x{{ $item->tinggi_cm }} cm</div>
                                </div>
                            </td>

                            <!-- KOLOM 4: RINCIAN PROFIT -->
                            <td class="p-4 align-top">
                                <!-- Status Transaksi -->
                                <div class="mb-3">
                                    @if(in_array($item->status, ['booking_created', 'paid']))
                                        <span class="bg-green-100 text-green-700 font-bold px-2 py-1 rounded text-[10px] uppercase">Lunas & Diproses</span>
                                    @elseif($item->status == 'menunggu_pembayaran')
                                        <span class="bg-orange-100 text-orange-700 font-bold px-2 py-1 rounded text-[10px] uppercase">Pending</span>
                                    @else
                                        <span class="bg-red-100 text-red-700 font-bold px-2 py-1 rounded text-[10px] uppercase">{{ $item->status }}</span>
                                    @endif
                                    <p class="text-[9px] text-gray-400 uppercase tracking-wider mt-1.5 border-b border-gray-100 pb-1">Via: <strong class="text-gray-600">{{ str_replace('_', ' ', $item->metode_pembayaran) }}</strong></p>
                                </div>

                                <!-- Kalkulasi Bagi Hasil (Sancaka & Agen) -->
                                <div class="text-[10px] space-y-1">
                                    <div class="flex justify-between text-gray-800">
                                        <span>Ongkir Customer:</span>
                                        <span class="font-bold">Rp {{ number_format($item->ongkir, 0, ',', '.') }}</span>
                                    </div>
                                    @if($item->profit->persen_cashback > 0)
                                        <div class="flex justify-between text-blue-600">
                                            <span>Cashback ({{ $item->profit->persen_cashback }}%):</span>
                                            <span class="font-bold">Rp {{ number_format($item->profit->total_cashback, 0, ',', '.') }}</span>
                                        </div>
                                        <div class="flex justify-between text-orange-600">
                                            <span>- Komisi Agen (40%):</span>
                                            <span class="font-bold border-b border-dashed border-orange-300 pb-0.5">- Rp {{ number_format($item->profit->komisi_agen, 0, ',', '.') }}</span>
                                        </div>
                                        <div class="flex justify-between text-green-700 font-black mt-1">
                                            <span>= Laba Sancaka (60%):</span>
                                            <span>+ Rp {{ number_format($item->profit->laba_sancaka, 0, ',', '.') }}</span>
                                        </div>
                                    @else
                                        <div class="flex justify-between text-gray-400 italic">
                                            <span>Belum diset di master data.</span>
                                        </div>
                                    @endif
                                </div>
                            </td>

                            <!-- KOLOM 5: AKSI (Paling Kanan) -->
                            <td class="p-4 align-top">
                                <div class="flex flex-wrap gap-2 justify-end">
                                    @php $resiTrack = $item->awb_number ?? $item->order_id; @endphp
                                    <a href="https://tokosancaka.com/tracking/search?resi={{ $resiTrack }}" target="_blank" class="bg-blue-100 hover:bg-blue-200 text-blue-700 w-8 h-8 rounded flex items-center justify-center shadow-sm" title="Lacak Paket">
                                        <i class="fa-solid fa-location-crosshairs"></i>
                                    </a>

                                    <a href="{{ route('admin.pesanan-autokirim.cetak', $item->id) }}" target="_blank" class="bg-green-100 hover:bg-green-200 text-green-700 w-8 h-8 rounded flex items-center justify-center shadow-sm" title="Cetak / Download Resi">
                                        <i class="fa-solid fa-print"></i>
                                    </a>

                                    @if(in_array($item->status, ['booking_created', 'menunggu_pembayaran']))
                                        <button type="button" onclick="confirmCancel('{{ route('admin.pesanan-autokirim.cancel', $item->id) }}')" class="bg-orange-100 hover:bg-orange-200 text-orange-700 w-8 h-8 rounded flex items-center justify-center shadow-sm" title="Batalkan Pesanan">
                                            <i class="fa-solid fa-ban"></i>
                                        </button>
                                    @else
                                        <button type="button" disabled class="bg-gray-100 text-gray-400 w-8 h-8 rounded flex items-center justify-center cursor-not-allowed" title="Sudah diproses, tidak bisa dibatalkan">
                                            <i class="fa-solid fa-lock"></i>
                                        </button>
                                    @endif

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

    <script>
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
