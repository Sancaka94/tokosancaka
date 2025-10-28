Tentu, saya akan perbaiki kode `index.blade.php` Anda.

Masalah "N/A" dan harga yang salah (menampilkan total harga, bukan ongkir) di screenshot Anda terjadi karena dua alasan:

1.  **Data `expedition` Kosong:** Untuk pesanan yang pembayarannya gagal atau "Gagal Auto-Resi" (seperti `...J5JFCG`), kolom `expedition` di database Anda `NULL`. Kode `explode` di *view* Anda gagal, sehingga menampilkan "N/A".
2.  **Menampilkan Harga yang Salah:** Kode Anda menampilkan `$order->price` (Total Bayar: Rp 5.500) di kolom ongkir, padahal seharusnya menampilkan `$order->shipping_cost` (yang bernilai `Rp 0` untuk pesanan lama yang gagal itu).
3.  **Aksi Tidak Tersedia:** Tombol "Aksi" (seperti "Detail") hilang untuk pesanan yang gagal, padahal Anda perlu melihat detailnya.

Berikut adalah kode yang sudah diperbaiki secara lengkap. Saya telah memberi tanda `{{-- PERBAIKAN --}}` pada baris-baris yang krusial.

-----

### `resources/views/admin/pesanan/index.blade.php` (Lengkap & Benar)

Salin dan ganti seluruh isi file Anda dengan kode di bawah ini:

```blade
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
        background-color: #f9fafb;
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
        {{-- PERBAIKAN: Tambahan filter untuk melihat pesanan gagal --}}
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
            <tr>
                {{-- No --}}
                <td class="px-4 py-4 align-top text-sm text-gray-500">{{ $orders->firstItem() + $index }}</td>

                {{-- Transaksi --}}
                <td class="px-4 py-4 align-top text-sm">
                    @if(Str::contains($order->payment_method, 'COD'))
                        <span class="font-bold text-green-600">COD</span>
                    @else
                        <span class="font-bold text-blue-600">Non COD</span>
                    @endif
                    
                    {{-- PERBAIKAN: Tampilkan resi atau "Menunggu Resi" --}}
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
                        {{-- PERBAIKAN: Gunakan receiver_name, fallback ke nama_pembeli --}}
                        <div class="font-semibold text-red-700"><strong>{{ $order->receiver_name ?? $order->nama_pembeli }}</strong></div>
                        <div class="text-xs text-gray-600">{{ $order->receiver_address ?? $order->alamat_pengiriman }}, {{ $order->receiver_district }}, {{ $order->receiver_regency }}</div>
                    </div>
                </td>

                {{-- PERBAIKAN: Kolom Ekspedisi & Ongkir --}}
                <td class="px-4 py-4 align-top text-sm">
                    @php
                        $courier = 'N/A';
                        $service = 'N/A';
                        $logoPath = null;
                        
                        // Cek jika expedition tidak kosong dan mengandung '-'
                        if (!empty($order->expedition) && strpos($order->expedition, '-') !== false) {
                            $expParts = explode('-', $order->expedition);
                            $courierName = $expParts[1] ?? 'N/A'; // Ambil nama kurir
                            $service = !empty($expParts[2]) ? $expParts[2] : 'N/A';
                            
                            $logoPath = strtolower(str_replace(' ', '', $courierName));
                            $courier = strtoupper($courierName);
                        }
                    @endphp

                    @if($logoPath && $logoPath != 'n/a')
                        {{-- Tampilkan logo jika ada path-nya --}}
                        <img src="{{ asset('storage/logo-ekspedisi/' . $logoPath . '.png') }}" alt="{{ $courier }} Logo" class="w-20 h-auto mb-1" 
                             onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                        {{-- Fallback teks jika logo gagal dimuat --}}
                        <div style="display:none;" class="font-bold text-gray-800">{{ $courier }}</div>
                    @else
                        {{-- Tampilkan teks jika tidak ada data ekspedisi --}}
                        <div class="font-bold text-gray-800">{{ $courier }}</div>
                    @endif
                    
                    <div class="text-xs text-gray-500">Layanan: {{ $service }}</div>

                    {{-- PERBAIKAN PALING PENTING: Menampilkan ongkir dari 'shipping_cost', bukan 'price' --}}
                    <div class="font-semibold text-green-700 mt-1">
                        Rp{{ number_format($order->shipping_cost, 0, ',', '.') }}
                    </div>
                </td>

                {{-- Isi Paket --}}
                <td class="px-4 py-4 align-top text-sm">
                    <div class="font-semibold text-gray-800">{{ $order->item_description }}</div>
                    <div class="text-xs text-gray-500">Order ID: # <strong>{{ $order->nomor_invoice }}</strong> </div>
                    <div class="text-xs text-gray-500 mt-1">
                        Berat: {{ $order->weight }} gr | Dimensi: {{ $order->length ?? 'N/A' }}x{{ $order->width ?? 'N/A' }}x{{ $order->height ?? 'N/A' }} cm
                    </div>
                </td>

                {{-- PERBAIKAN: Kolom Status (lebih rapi dan lengkap) --}}
                <td class="px-4 py-4 align-top text-sm">
                    @php
                        $statusClass = '';
                        switch ($order->status_pesanan) {
                            case 'Terkirim':
                                $statusClass = 'bg-green-100 text-green-800'; break;
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
                                $statusClass = 'bg-red-100 text-red-800'; break;
                            default:
                                $statusClass = 'bg-gray-100 text-gray-800';
                        }
                    @endphp
                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full {{ $statusClass }}">
                        {{ $order->status_pesanan }}
                    </span>
                </td>
                
                {{-- PERBAIKAN: Kolom Aksi --}}
                <td class="px-6 py-4 align-top whitespace-nowrap text-sm font-medium sticky-col">
                    <div class="flex items-center space-x-3">
                        
                        {{-- Aksi "Detail" selalu tersedia, menggunakan invoice sebagai fallback --}}
                        <a href="{{ route('admin.pesanan.show', ['resi' => $order->resi ?? $order->nomor_invoice]) }}" class="text-gray-500 hover:text-indigo-600" title="Detail">
                            <i class="fas fa-eye"></i>
                        </a>

                        {{-- Aksi lain HANYA JIKA RESI ADA --}}
                        @if($order->resi)
                            <a href="https://tokosancaka.com/tracking/search?resi={{ $order->resi }}" target="_blank" class="text-gray-500 hover:text-green-600" title="Lacak Resi">
                                <i class="fas fa-truck"></i>
                            </a>
                            
                            <a href="{{ route('admin.pesanan.cetak_thermal', ['resi' => $order->resi]) }}" target="_blank" class="text-gray-500 hover:text-gray-800" title="Cetak Label">
                                <i class="fas fa-print"></i>
                            </a>
                            
                            <a href="{{ route('admin.pesanan.edit', ['resi' => $order->resi]) }}" class="text-gray-500 hover:text-blue-600" title="Edit">
                                <i class="fas fa-pencil-alt"></i>
                            </a>
                            
                            <form action="{{ route('admin.pesanan.destroy', ['resi' => $order->resi]) }}" method="POST" onsubmit="return confirm('Yakin hapus pesanan ini?');" class="inline-block">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-gray-500 hover:text-red-600" title="Hapus">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </form>
                        @endif
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="text-center py-4 text-gray-500">Data pesanan tidak ditemukan.</td>
            </tr>
            @endforelse
        </tbody>

        </table>
    </div>

    <div class="mt-4">
        {{ $orders->appends(request()->query())->links() }}
    </div>
</div>

@include('layouts.partials.modals.export', ['excel_route' => route('admin.pesanan.export.excel'), 'pdf_route' => route('admin.pesanan.export.pdf')])

@endsection

@push('scripts')
<script>
    function openModal(id) { document.getElementById(id).classList.remove('hidden'); }
    function closeModal(id) { document.getElementById(id).classList.add('hidden'); }
</script>
@endpush
```