@extends('layouts.admin') {{-- Sesuaikan dengan layout admin Anda --}}

@section('title', 'Riwayat Pesanan Ojek & Express')

@section('content')
<div class="p-6 bg-gray-50 min-h-screen">

    <!-- HEADER -->
    <div class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Riwayat Pesanan</h1>
            <p class="text-sm text-gray-500 mt-1">Kelola dan pantau transaksi Sancaka Ride & Express.</p>
        </div>
    </div>

    <!-- MONITOR CARDS (Statistik) -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex items-center space-x-4">
            <div class="p-3 bg-blue-50 text-blue-600 rounded-xl">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-500">Total Pesanan</p>
                <h3 class="text-2xl font-bold text-gray-900">{{ $totalPesanan ?? $orders->total() ?? 0 }}</h3>
            </div>
        </div>

        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex items-center space-x-4">
            <div class="p-3 bg-red-50 text-red-600 rounded-xl">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-500">Sancaka Express</p>
                <h3 class="text-2xl font-bold text-gray-900">{{ $totalExpress ?? 0 }}</h3>
            </div>
        </div>

        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex items-center space-x-4">
            <div class="p-3 bg-green-50 text-green-600 rounded-xl">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-500">Sancaka Ride</p>
                <h3 class="text-2xl font-bold text-gray-900">{{ $totalRide ?? 0 }}</h3>
            </div>
        </div>

        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex items-center space-x-4">
            <div class="p-3 bg-emerald-50 text-emerald-600 rounded-xl">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-500">Selesai</p>
                <h3 class="text-2xl font-bold text-gray-900">{{ $totalSelesai ?? 0 }}</h3>
            </div>
        </div>
    </div>

    <!-- MAIN TABLE SECTION -->
    <div class="bg-white border border-gray-100 shadow-sm rounded-2xl overflow-hidden">

        <!-- Form Bulk Destroy -->
        <form action="{{ route('admin.pesanan_ojek.bulk_destroy') }}" method="POST" id="bulkForm">
            @csrf
            @method('DELETE')

            <div class="p-5 border-b border-gray-100 flex items-center justify-between bg-white">
                <h2 class="text-base font-semibold text-gray-800">Daftar Transaksi</h2>

                <!-- Tombol Bulk Delete (Hidden by default) -->
                <button type="button" id="bulkDeleteBtn" onclick="confirmBulkDelete()" class="hidden items-center px-4 py-2 bg-red-50 text-red-600 text-sm font-medium rounded-lg hover:bg-red-100 transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                    Hapus Terpilih
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-gray-600 whitespace-nowrap">
                    <thead class="bg-gray-50/50 text-gray-500 text-xs uppercase tracking-wider font-semibold border-b border-gray-100">
                        <tr>
                            <th class="px-6 py-4 text-center w-12">
                                <input type="checkbox" id="selectAll" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 w-4 h-4 cursor-pointer">
                            </th>
                            <th class="px-6 py-4">Order ID & Layanan</th>
                            <th class="px-6 py-4">Tanggal</th>
                            <th class="px-6 py-4">Pelanggan & Driver</th>
                            <th class="px-6 py-4">Rute & Jarak</th>
                            <th class="px-6 py-4 text-right">Tarif</th>
                            <th class="px-6 py-4 text-center">Status</th>
                            <th class="px-6 py-4 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($orders as $key => $order)
                        <tr class="hover:bg-gray-50/50 transition-colors group">
                            <td class="px-6 py-4 text-center">
                                <input type="checkbox" name="ids[]" value="{{ $order->id }}" class="order-checkbox rounded border-gray-300 text-blue-600 focus:ring-blue-500 w-4 h-4 cursor-pointer">
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-semibold text-gray-900">{{ $order->order_id }}</div>
                                <div class="mt-1">
                                    @if(str_starts_with($order->order_id, 'S-EXP'))
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">Sancaka Express</span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">Sancaka Ride</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 text-gray-500">
                                {{ \Carbon\Carbon::parse($order->created_at)->format('d M Y') }}<br>
                                <span class="text-xs text-gray-400">{{ \Carbon\Carbon::parse($order->created_at)->format('H:i') }} WIB</span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-col space-y-1">
                                    <div class="flex items-center text-gray-900">
                                        <svg class="w-4 h-4 mr-1.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                        {{ $order->customer_name ?? 'Tidak Diketahui' }}
                                    </div>
                                    <div class="flex items-center text-gray-600">
                                        <svg class="w-4 h-4 mr-1.5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                                        {{ $order->driver_name }}
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-normal min-w-[250px]">
                                <div class="flex flex-col space-y-1.5 text-xs">
                                    <div class="flex items-start">
                                        <span class="font-semibold text-gray-700 w-10">Dari:</span>
                                        <span class="text-gray-500 truncate" title="{{ $order->origin_address }}">{{ \Illuminate\Support\Str::limit($order->origin_address, 40) }}</span>
                                    </div>
                                    <div class="flex items-start">
                                        <span class="font-semibold text-gray-700 w-10">Ke:</span>
                                        <span class="text-gray-500 truncate" title="{{ $order->dest_address }}">{{ \Illuminate\Support\Str::limit($order->dest_address, 40) }}</span>
                                    </div>
                                    <div class="text-blue-600 font-medium flex items-center mt-1">
                                        <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"></path></svg>
                                        {{ $order->jarak_km }} KM
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="font-semibold text-gray-900">Rp {{ number_format($order->tarif, 0, ',', '.') }}</div>
                                <div class="text-xs text-gray-500">{{ $order->metode_pembayaran }}</div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                @if($order->status == 'pending')
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-amber-100 text-amber-800">Menunggu</span>
                                @elseif(in_array($order->status, ['accepted', 'otw_jemput', 'otw_antar']))
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">{{ strtoupper(str_replace('_', ' ', $order->status)) }}</span>
                                @elseif(in_array($order->status, ['completed', 'selesai']))
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800">SELESAI</span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">{{ strtoupper($order->status) }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex items-center justify-center space-x-2 opacity-0 group-hover:opacity-100 transition-opacity">

                                    <!-- Tombol Bukti (Membuka Modal) -->
                                    <button type="button" onclick="openModal('modal-{{ $order->id }}')" class="p-1.5 text-blue-600 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500" title="Lihat Bukti">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                    </button>

                                    <!-- Tombol Hapus (Single Destroy) -->
                                    <button type="button" onclick="confirmSingleDelete('{{ route('admin.pesanan_ojek.destroy', $order->id) }}')" class="p-1.5 text-red-600 bg-red-50 rounded-lg hover:bg-red-100 transition-colors focus:outline-none focus:ring-2 focus:ring-red-500" title="Hapus">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>

                                </div>
                            </td>
                        </tr>

                        <!-- MODAL BUKTI PENGIRIMAN (TAILWIND) -->
                        <div id="modal-{{ $order->id }}" class="fixed inset-0 z-[100] hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                            <!-- Backdrop -->
                            <div class="fixed inset-0 bg-gray-900 bg-opacity-50 transition-opacity backdrop-blur-sm" onclick="closeModal('modal-{{ $order->id }}')"></div>

                            <!-- Modal Panel -->
                            <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
                                <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                                    <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-3xl">

                                        <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                                            <div class="flex justify-between items-center mb-5 border-b border-gray-100 pb-3">
                                                <h3 class="text-lg font-semibold leading-6 text-gray-900" id="modal-title">
                                                    Bukti Pesanan <span class="text-blue-600">{{ $order->order_id }}</span>
                                                </h3>
                                                <button type="button" onclick="closeModal('modal-{{ $order->id }}')" class="text-gray-400 hover:text-gray-500 focus:outline-none">
                                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                                                </button>
                                            </div>

                                            @if(str_starts_with($order->order_id, 'S-EXP'))
                                            <!-- SECTION PENGIRIM (AMBIL PAKET) -->
                                            <div class="mb-8">
                                                <h4 class="text-md font-bold text-gray-800 border-b border-gray-100 pb-2 mb-4">📍 Bukti Ambil Paket (Pengirim)</h4>
                                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                                    <!-- Foto Pengirim -->
                                                    <div class="flex flex-col items-center">
                                                        <p class="text-sm font-medium text-gray-700 mb-2">Foto Paket / Pengirim</p>
                                                        @if($order->bukti_foto_pengirim)
                                                            <div class="w-full aspect-[4/3] bg-gray-100 rounded-xl border border-gray-200 overflow-hidden flex justify-center items-center">
                                                                <img src="{{ asset('storage/' . $order->bukti_foto_pengirim) }}" alt="Foto Pengirim" class="object-contain w-full h-full">
                                                            </div>
                                                        @else
                                                            <div class="w-full aspect-[4/3] bg-gray-50 rounded-xl border border-dashed border-gray-300 flex items-center justify-center">
                                                                <span class="text-sm text-gray-400">Tidak ada foto</span>
                                                            </div>
                                                        @endif
                                                    </div>

                                                    <!-- TTD Pengirim -->
                                                    <div class="flex flex-col items-center">
                                                        <p class="text-sm font-medium text-gray-700 mb-2">Tanda Tangan Pengirim</p>
                                                        @if($order->bukti_ttd_pengirim)
                                                            <div class="w-full aspect-[4/3] bg-white rounded-xl border border-gray-200 overflow-hidden flex justify-center items-center p-2 shadow-inner">
                                                                <img src="{{ asset('storage/' . $order->bukti_ttd_pengirim) }}" alt="Tanda Tangan Pengirim" class="object-contain w-full h-full filter contrast-125">
                                                            </div>
                                                        @else
                                                            <div class="w-full aspect-[4/3] bg-gray-50 rounded-xl border border-dashed border-gray-300 flex items-center justify-center">
                                                                <span class="text-sm text-gray-400">Belum ditandatangani</span>
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>

                                                <!-- Token Pengirim -->
                                                @if($order->foto_token_id_pengirim)
                                                <div class="mt-4 bg-gray-50 rounded-lg p-3 flex items-center justify-between border border-gray-100">
                                                    <span class="text-xs text-gray-500 font-medium uppercase">Security Token (Pengirim)</span>
                                                    <span class="font-mono text-xs font-bold text-gray-900 bg-gray-200 px-2 py-1 rounded">{{ $order->foto_token_id_pengirim }}</span>
                                                </div>
                                                @endif
                                            </div>
                                            @endif

                                            <!-- SECTION PENERIMA (ANTAR PAKET) -->
                                            <div>
                                                <h4 class="text-md font-bold text-gray-800 border-b border-gray-100 pb-2 mb-4">
                                                    {{ str_starts_with($order->order_id, 'S-EXP') ? '🏁 Bukti Antar Paket (Penerima)' : '🏁 Bukti Selesai (Sancaka Ride)' }}
                                                </h4>
                                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                                    <!-- Foto Penerima -->
                                                    <div class="flex flex-col items-center">
                                                        <p class="text-sm font-medium text-gray-700 mb-2">Foto Penerima / Kondisi</p>
                                                        @if($order->bukti_foto_penerima)
                                                            <div class="w-full aspect-[4/3] bg-gray-100 rounded-xl border border-gray-200 overflow-hidden flex justify-center items-center">
                                                                <img src="{{ asset('storage/' . $order->bukti_foto_penerima) }}" alt="Foto Penerima" class="object-contain w-full h-full">
                                                            </div>
                                                        @else
                                                            <div class="w-full aspect-[4/3] bg-gray-50 rounded-xl border border-dashed border-gray-300 flex items-center justify-center">
                                                                <span class="text-sm text-gray-400">Tidak ada foto</span>
                                                            </div>
                                                        @endif
                                                    </div>

                                                    <!-- Tanda Tangan Penerima -->
                                                    <div class="flex flex-col items-center">
                                                        <p class="text-sm font-medium text-gray-700 mb-2">Tanda Tangan Penerima</p>
                                                        @if($order->bukti_ttd_penerima)
                                                            <div class="w-full aspect-[4/3] bg-white rounded-xl border border-gray-200 overflow-hidden flex justify-center items-center p-2 shadow-inner">
                                                                <img src="{{ asset('storage/' . $order->bukti_ttd_penerima) }}" alt="Tanda Tangan Penerima" class="object-contain w-full h-full filter contrast-125">
                                                            </div>
                                                        @else
                                                            <div class="w-full aspect-[4/3] bg-gray-50 rounded-xl border border-dashed border-gray-300 flex items-center justify-center">
                                                                <span class="text-sm text-gray-400">Belum ditandatangani</span>
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>

                                                <!-- Token Penerima -->
                                                @if($order->foto_token_id)
                                                <div class="mt-4 bg-gray-50 rounded-lg p-3 flex items-center justify-between border border-gray-100">
                                                    <span class="text-xs text-gray-500 font-medium uppercase">Security Token (Penerima)</span>
                                                    <span class="font-mono text-xs font-bold text-gray-900 bg-gray-200 px-2 py-1 rounded">{{ $order->foto_token_id }}</span>
                                                </div>
                                                @endif
                                            </div>

                                        </div>

                                        <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                                            <button type="button" onclick="closeModal('modal-{{ $order->id }}')" class="mt-3 inline-flex w-full justify-center rounded-lg bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto">Tutup</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center justify-center text-gray-400">
                                    <svg class="w-12 h-12 mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.894.553l-.236.473a1 1 0 01-.894.553H8.618a1 1 0 01-.894-.553l-.236-.473A1 1 0 006.586 13H4"></path></svg>
                                    <span class="text-sm font-medium">Belum ada riwayat pesanan.</span>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </form>

        <!-- Pagination -->
        @if($orders->hasPages())
        <div class="p-5 border-t border-gray-100 bg-white">
            {{ $orders->links('pagination::tailwind') }}
        </div>
        @endif
    </div>
</div>

<!-- Form tersembunyi untuk Single Delete -->
<form id="singleDeleteForm" method="POST" class="hidden">
    @csrf
    @method('DELETE')
</form>

<!-- Javascript Murni (Vanilla) untuk interaktivitas Tailwind -->
<script>
    // --- Logika Checkbox & Bulk Delete ---
    const selectAllCheckbox = document.getElementById('selectAll');
    const orderCheckboxes = document.querySelectorAll('.order-checkbox');
    const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');

    function toggleBulkBtn() {
        const checkedCount = document.querySelectorAll('.order-checkbox:checked').length;
        if (checkedCount > 0) {
            bulkDeleteBtn.classList.remove('hidden');
            bulkDeleteBtn.classList.add('inline-flex');
        } else {
            bulkDeleteBtn.classList.add('hidden');
            bulkDeleteBtn.classList.remove('inline-flex');
        }
    }

    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            orderCheckboxes.forEach(cb => cb.checked = this.checked);
            toggleBulkBtn();
        });
    }

    orderCheckboxes.forEach(cb => {
        cb.addEventListener('change', function() {
            const allChecked = document.querySelectorAll('.order-checkbox:checked').length === orderCheckboxes.length;
            selectAllCheckbox.checked = allChecked;
            toggleBulkBtn();
        });
    });

    // --- Konfirmasi Delete ---
    function confirmBulkDelete() {
        if(confirm('Apakah Anda yakin ingin menghapus semua data yang dipilih secara permanen?')) {
            document.getElementById('bulkForm').submit();
        }
    }

    function confirmSingleDelete(actionUrl) {
        if(confirm('Apakah Anda yakin ingin menghapus transaksi ini?')) {
            const form = document.getElementById('singleDeleteForm');
            form.action = actionUrl;
            form.submit();
        }
    }

    // --- Logika Modal ---
    function openModal(modalId) {
        const modal = document.getElementById(modalId);
        if(modal) {
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden'; // cegah scroll background
        }
    }

    function closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if(modal) {
            modal.classList.add('hidden');
            document.body.style.overflow = 'auto'; // kembalikan scroll
        }
    }
</script>
@endsection
