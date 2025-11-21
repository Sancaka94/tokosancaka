{{--
    File: resources/views/admin/pesanan/index.blade.php
    Deskripsi: Halaman untuk menampilkan daftar pesanan dengan tombol aksi yang lengkap.
--}}

@extends('layouts.admin')

@section('title', 'Data Pesanan')
@section('page-title', 'Data Pesanan')

@push('styles')
<style>
    .table-container {
        overflow-x: auto;
    }
    th.sticky-col, td.sticky-col {
        position: -webkit-sticky;
        position: sticky;
        right: 0;
        background-color: white;
        z-index: 10;
    }
    thead th.sticky-col {
        background-color: #f9fafb; /* Latar belakang header kolom sticky */
    }
    td.sticky-col {
        border-left: 1px solid #e5e7eb; /* Garis pemisah kolom sticky */
    }
</style>
@endpush

@section('content')
<div class="bg-white p-4 sm:p-6 rounded-lg shadow-md">

    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
        <div class="w-full md:w-1/3">
            <form action="{{ route('admin.pesanan.index') }}" method="GET" class="relative">
                <input type="text" name="search" class="w-full pl-10 pr-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="Cari Resi, Nama, atau No. HP..." value="{{ request('search') }}">
                <div class="absolute top-0 left-0 inline-flex items-center p-2 h-full text-gray-400">
                    <i class="fas fa-search"></i>
                </div>
            </form>
        </div>
        <div class="flex items-center gap-2 w-full md:w-auto justify-end">
            <button type="button" onclick="openModal('exportModal')" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-300">
                <i class="fas fa-file-export me-2"></i>Export
            </button>
            <a href="{{ route('admin.pesanan.create') }}" class="bg-red-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-red-700">
                <i class="fas fa-plus me-2"></i>Tambah Pesanan
            </a>
        </div>
    </div>

    <div class="flex flex-wrap gap-2 mb-4 border-b pb-4">
        <a href="{{ route('admin.pesanan.index', request()->except('status', 'page')) }}" class="px-3 py-1 text-sm font-medium rounded-full {{ !request('status') ? 'bg-green-600 text-white' : 'bg-gray-200 text-gray-600 hover:bg-gray-300' }}">Semua</a>
        <a href="{{ route('admin.pesanan.index', array_merge(request()->query(), ['status' => 'Menunggu Pickup', 'page' => 1])) }}" class="px-3 py-1 text-sm font-medium rounded-full {{ request('status') == 'Menunggu Pickup' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-600 hover:bg-gray-300' }}">Menunggu Pickup</a>
        <a href="{{ route('admin.pesanan.index', array_merge(request()->query(), ['status' => 'Diproses', 'page' => 1])) }}" class="px-3 py-1 text-sm font-medium rounded-full {{ request('status') == 'Diproses' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-600 hover:bg-gray-300' }}">Diproses</a>
        <a href="{{ route('admin.pesanan.index', array_merge(request()->query(), ['status' => 'Terkirim', 'page' => 1])) }}" class="px-3 py-1 text-sm font-medium rounded-full {{ request('status') == 'Terkirim' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-600 hover:bg-gray-300' }}">Terkirim</a>
        <a href="{{ route('admin.pesanan.index', array_merge(request()->query(), ['status' => 'Batal', 'page' => 1])) }}" class="px-3 py-1 text-sm font-medium rounded-full {{ request('status') == 'Batal' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-600 hover:bg-gray-300' }}">Batal</a>
        <a href="{{ route('admin.pesanan.index', array_merge(request()->query(), ['status' => 'Pembayaran Lunas (Gagal Auto-Resi)', 'page' => 1])) }}" class="px-3 py-1 text-sm font-medium rounded-full {{ request('status') == 'Pembayaran Lunas (Gagal Auto-Resi)' ? 'bg-red-600 text-white' : 'bg-gray-200 text-gray-600 hover:bg-gray-300' }}">Gagal Resi</a>
    </div>

    @include('layouts.partials.notifications')

    <div class="table-container">
        <table class="table min-w-full divide-y divide-gray-200">
          <thead class="bg-red-100">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">No</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider"><strong>Transaksi</strong></th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider"><strong>Alamat</strong></th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider"><strong>Ekspedisi & Ongkir</strong></th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider"><strong>Isi Paket</strong></th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider"><strong>Status</strong></th>
                <th class="px-4 py-3 text-xs font-medium text-gray-600 uppercase tracking-wider sticky-col"><strong>Aksi</strong></th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            @forelse ($orders as $index => $order)
            <tr class="group hover:bg-gray-50"> {{-- Tambahkan group hover --}}
                {{-- No --}}
                <td class="px-4 py-4 align-top text-sm text-gray-500">{{ $orders->firstItem() + $index }}</td>

                {{-- Transaksi --}}
                <td class="px-4 py-4 align-top text-sm">
                    @if(Str::contains($order->payment_method, 'COD'))
                        <span class="font-bold text-green-600">COD</span>
                    @else
                        <span class="font-bold text-blue-600">{{$order->payment_method}} (<span class="text-red-600">Non COD</span>)</span>

                    @endif

                    <div class="font-bold text-gray-800">{{ $order->resi ?? 'Menunggu Resi' }}</div>

                    <div class="text-xs text-gray-500">{{ \Carbon\Carbon::parse($order->tanggal_pesanan)->format('d M Y, H:i') }}</div>
                </td>

                {{-- Alamat --}}
                <td class="px-4 py-4 align-top text-sm">
                    <div class="mb-2">
                        <div class="text-xs text-gray-500">Dari:</div>
                        <div class="font-semibold text-blue-700"><strong>{{ $order->sender_name }}</strong></div>
                        <div class="text-xs text-gray-600">{{ $order->sender_address }}, {{ $order->sender_district }}, {{ $order->sender_regency }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-gray-500">Kepada:</div>
                        <div class="font-semibold text-red-700"><strong>{{ $order->receiver_name ?? $order->nama_pembeli }}</strong></div>
                        <div class="text-xs text-gray-600">{{ $order->receiver_address ?? $order->alamat_pengiriman }}, {{ $order->receiver_district }}, {{ $order->receiver_regency }}</div>
                    </div>
                </td>

                {{-- ====================================================== --}}
                {{-- PERBAIKAN: Kolom Ekspedisi & Ongkir dengan Helper Logo --}}
                {{-- ====================================================== --}}
                <td class="px-4 py-4 align-top text-sm">
                    @php
                        // Panggil helper shipping
                        $ship = \App\Helpers\ShippingHelper::parseShippingMethod($order->expedition);

                        $courierName = $ship['courier_name'] ?? 'N/A';
                        $serviceName = $ship['service_name'] ?? 'N/A';
                        $logoUrlFromHelper = $ship['logo_url'] ?? null;

                        // Tentukan path logo lokal sebagai fallback
                        $localLogoPath = strtolower(str_replace(' ', '', $courierName));
                        $localLogoAssetUrl = asset('storage/logo-ekspedisi/' . $localLogoPath . '.png');

                        // Prioritaskan URL dari helper, jika tidak ada, gunakan URL asset lokal
                        $finalLogoUrl = $logoUrlFromHelper ?: $localLogoAssetUrl;
                    @endphp

                    @if($finalLogoUrl && $courierName != 'N/A')
                        {{-- Tampilkan logo jika URL tersedia dan nama kurir valid --}}
                        <img src="{{ $finalLogoUrl }}" alt="{{ $courierName }} Logo" class="w-20 h-auto mb-1 max-w-[80px] object-contain"
                             onerror="this.style.display='none'; this.nextElementSibling.style.display='block';"> {{-- Sembunyikan jika error & tampilkan teks --}}
                        {{-- Fallback teks jika logo gagal dimuat (awalnya disembunyikan) --}}
                        <div style="display:none;" class="font-bold text-gray-800">{{ $courierName }}</div>
                    @else
                        {{-- Tampilkan teks jika tidak ada data ekspedisi atau logo --}}
                        <div class="font-bold text-gray-800">{{ $courierName }}</div>
                    @endif

                    <div class="text-xs text-gray-500">Layanan: {{ $serviceName }}</div>

                    {{-- Menampilkan ongkir dari 'shipping_cost' --}}
                    <div class="font-semibold text-green-700 mt-1">
                        Rp{{ number_format($order->shipping_cost, 0, ',', '.') }}
                    </div>
                </td>
                {{-- ====================================================== --}}
                {{-- AKHIR PERBAIKAN --}}
                {{-- ====================================================== --}}


                {{-- Isi Paket --}}
                <td class="px-4 py-4 align-top text-sm">
                    <div class="font-semibold text-gray-800">{{ $order->item_description }}</div>
                    <div class="text-xs text-gray-500">Order ID: # <strong>{{ $order->nomor_invoice }}</strong> </div>
                    <div class="text-xs text-gray-500 mt-1">
                        Berat: {{ $order->weight }} gr | Dimensi: {{ $order->length ?? '0' }} x {{ $order->width ?? '0' }} x {{ $order->height ?? '0' }} cm
                    </div>
                </td>

                {{-- Kolom Status --}}
                <td class="px-4 py-4 align-top text-sm">
                    @php
                        $statusClass = '';
                        $statusText = $order->status_pesanan; // Default text

                        switch ($statusText) {
                            case 'Terkirim':
                            case 'Sedang Dikirim': // Handle alias
                                $statusClass = 'bg-green-100 text-green-800';
                                $statusText = 'Terkirim'; // Konsistenkan teks
                                break;
                            case 'Diproses':
                                $statusClass = 'bg-blue-100 text-blue-800'; break;
                            case 'Menunggu Pickup':
                                $statusClass = 'bg-yellow-100 text-yellow-800'; break;
                            case 'Menunggu Pembayaran':
                                $statusClass = 'bg-gray-100 text-gray-800'; break;
                            case 'Batal':
                            case 'Kadaluarsa':
                            case 'Gagal Bayar':
                            case 'Pembayaran Lunas (Gagal Auto-Resi)':
                            case 'Pembayaran Lunas (Error Kirim API)':
                                $statusClass = 'bg-red-100 text-red-800';
                                // Optional: Persingkat teks status gagal jika terlalu panjang
                                if (strlen($statusText) > 20) {
                                    $statusText = 'Gagal';
                                }
                                break;
                            default:
                                $statusClass = 'bg-gray-100 text-gray-800';
                        }
                    @endphp
                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full {{ $statusClass }}">
                        {{ $statusText }}
                    </span>
                </td>

                {{-- Kolom Aksi (Sticky) --}}
                {{-- Background berubah saat baris dihover --}}
                <td class="px-6 py-4 align-top whitespace-nowrap text-sm font-medium sticky-col bg-white group-hover:bg-gray-50">
                    <div class="flex items-center space-x-3 justify-end"> {{-- Justify end agar ikon rata kanan --}}

                        {{-- Aksi "Detail" selalu tersedia --}}
                        <a href="{{ route('admin.pesanan.show', ['resi' => $order->resi ?? $order->nomor_invoice]) }}" class="text-gray-500 hover:text-indigo-600" title="Detail">
                            <i class="fas fa-eye fa-fw"></i> {{-- Tambah fa-fw --}}
                        </a>

                        {{-- Aksi lain HANYA JIKA RESI ADA --}}
                        @if($order->resi)
                            <a href="https://tokosancaka.com/tracking/search?resi={{ $order->resi }}" target="_blank" class="text-gray-500 hover:text-green-600" title="Lacak Resi">
                                <i class="fas fa-truck fa-fw"></i> {{-- Tambah fa-fw --}}
                            </a>

                            <a href="{{ route('admin.pesanan.cetak_thermal', ['resi' => $order->resi]) }}" target="_blank" class="text-gray-500 hover:text-gray-800" title="Cetak Label">
                                <i class="fas fa-print fa-fw"></i> {{-- Tambah fa-fw --}}
                            </a>

                            <a href="{{ route('admin.pesanan.edit', ['resi' => $order->resi]) }}" class="text-gray-500 hover:text-blue-600" title="Edit">
                                <i class="fas fa-pencil-alt fa-fw"></i> {{-- Tambah fa-fw --}}
                            </a>

                            <form action="{{ route('admin.pesanan.destroy', ['resi' => $order->resi]) }}" method="POST" onsubmit="return confirm('Yakin hapus pesanan ini?');" class="inline-block">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-gray-500 hover:text-red-600" title="Hapus">
                                    <i class="fas fa-trash-alt fa-fw"></i> {{-- Tambah fa-fw --}}
                                </button>
                            </form>
                        @endif
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                {{-- Perbaiki colspan agar sesuai jumlah kolom --}}
                <td colspan="7" class="text-center py-4 text-gray-500">Data pesanan tidak ditemukan.</td>
            </tr>
            @endforelse
        </tbody>

        </table>
    </div>

    @if ($orders->hasPages()) {{-- Cek jika ada pagination --}}
    <div class="mt-4 p-4 border-t border-gray-200">
        {{ $orders->appends(request()->query())->links() }}
    </div>
    @endif
</div>

@include('layouts.partials.modals.export', ['excel_route' => route('admin.pesanan.export.excel'), 'pdf_route' => route('admin.pesanan.export.pdf')])

@endsection

@push('scripts')
<script>
    function openModal(id) { document.getElementById(id).classList.remove('hidden'); }
    function closeModal(id) { document.getElementById(id).classList.add('hidden'); }
</script>
@endpush