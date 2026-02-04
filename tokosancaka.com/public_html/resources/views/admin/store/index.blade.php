@extends('layouts.admin')
@section('title', 'Pencairan Dana Toko') {{-- Judul diubah --}}

@section('content')
{{-- 
CATATAN: Halaman ini menggunakan Alpine.js untuk modal.
Pastikan layout admin Anda memuat script Alpine.js.
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script> 
--}}
<div class="container mx-auto px-4 py-8 space-y-10" x-data="{ 
    showModal: false, 
    modalStoreId: null, 
    modalStoreName: '', 
    modalTotalBalance: 0, 
    modalInternalBalance: 0, 
    modalDokuBalance: 0,
    modalSacId: '' 
}">

    <!-- Judul Halaman Diubah -->
    <h1 class="text-2xl font-bold mb-6">Pencairan Dana Toko Customer</h1>
    <p class="text-gray-600 -mt-6 mb-6">Tinjau semua saldo toko dan lakukan pencairan dana (payout) ke Sub-Account Doku mereka.</p>

    <!-- Notifikasi Sukses/Error (jika ada proses pencairan nanti) -->
    @if (session('success'))
        <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
            <strong class="font-bold">Sukses!</strong>
            <span class="block sm:inline">{{ session('success') }}</span>
        </div>
    @endif
    @if (session('error'))
        <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
            <strong class="font-bold">Error!</strong>
            <span class="block sm:inline">{{ session('error') }}</span>
        </div>
    @endif

    <!-- Tabel Semua Toko Customer (Sekarang fokus ke Saldo) -->
    <div class="bg-white shadow-md rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama Toko</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pemilik</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Doku SAC ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status Doku</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Saldo Internal (COD)</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Saldo Doku (Online)</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Siap Cair</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse ($customerStores as $store)
                        @php
                            // Ambil Saldo Internal (COD) dari relasi user
                            $saldo_internal = optional($store->user)->saldo ?? 0;
                            
                            // Ambil Saldo Doku (Online) dari tabel store
                            $saldo_doku = $store->doku_balance_available ?? 0;
                            
                            // Total yang bisa dicairkan
                            $total_saldo = $saldo_internal + $saldo_doku;
                        @endphp
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap font-medium">{{ $store->name }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">{{ optional($store->user)->nama_lengkap ?? 'N/A' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap font-mono text-sm">{{ $store->doku_sac_id ?? 'N/A' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if(strtolower($store->doku_status) == 'completed' || strtolower($store->doku_status) == 'success')
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                        Aktif
                                    </span>
                                @elseif(strtolower($store->doku_status) == 'pending')
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                        Pending
                                    </span>
                                @else
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                        Gagal
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900">
                                Rp {{ number_format($saldo_internal, 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900">
                                Rp {{ number_format($saldo_doku, 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap font-bold text-lg text-blue-600">
                                Rp {{ number_format($total_saldo, 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                {{-- Tombol ini akan memicu modal --}}
                                <button type="button" 
                                    class="font-semibold text-green-600 hover:text-green-900 disabled:text-gray-400 disabled:cursor-not-allowed"
                                    @click="
                                        showModal = true;
                                        modalStoreId = {{ $store->id }};
                                        modalStoreName = '{{ $store->name }}';
                                        modalTotalBalance = {{ $total_saldo }};
                                        modalInternalBalance = {{ $saldo_internal }};
                                        modalDokuBalance = {{ $saldo_doku }};
                                        modalSacId = '{{ $store->doku_sac_id ?? '' }}';
                                    "
                                    {{-- Nonaktifkan jika tidak ada saldo atau SAC ID --}}
                                    @if($total_saldo <= 0 || empty($store->doku_sac_id)) disabled @endif
                                >
                                    Cairkan Dana
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            {{-- Sesuaikan colspan menjadi 8 --}}
                            <td colspan="8" class="px-6 py-4 text-center text-gray-500">Belum ada toko customer yang terdaftar.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4">
            {{ $customerStores->links() }}
        </div>
    </div>

    <!-- ========================================================== -->
    <!-- === MODAL UNTUK KONFIRMASI PENCAIRAN DANA (PAYOUT) === -->
    <!-- ========================================================== -->
    <div 
        x-show="showModal" 
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        style="display: none;"
    >
        <!-- Latar belakang overlay -->
        <div class="fixed inset-0 bg-black bg-opacity-75" @click="showModal = false"></div>
        
        <!-- Konten Modal -->
        {{-- ✅ PERBAIKAN: Tambahkan flex flex-col dan max-h-[90vh] --}}
        {{-- ✅ PERBAIKAN 1: Tambahkan x-ref="payoutForm" --}}
        <form x-ref="payoutForm" :action="'{{ route('admin.stores.payout', ':storeId') }}'.replace(':storeId', modalStoreId)" method="POST"
              class="relative z-10 w-full max-w-lg bg-white rounded-lg shadow-xl overflow-hidden flex flex-col max-h-[90vh]"
              @click.away="showModal = false"
        >
            @csrf
            {{-- ✅ PERBAIKAN: Tambahkan flex-shrink-0 pada Header --}}
            <div class="flex justify-between items-center p-4 border-b flex-shrink-0">
                <h3 class="text-lg font-semibold text-gray-800">Konfirmasi Pencairan Dana</h3>
                <button type="button" @click="showModal = false" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            {{-- ✅ PERBAIKAN: Tambahkan overflow-y-auto dan flex-grow pada Konten --}}
            <div class="p-6 space-y-4 overflow-y-auto flex-grow">
                <p>Anda akan mencairkan dana (payout) untuk toko:</p>
                <div class="p-4 bg-gray-50 rounded-lg border">
                    <h4 class="font-bold text-lg text-gray-900" x-text="modalStoreName"></h4>
                    <p class="text-sm text-gray-600">SAC ID: <span class="font-mono" x-text="modalSacId"></span></p>
                </div>
                
                <div class="space-y-2 border-t pt-4">
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">Saldo Internal (COD)</span>
                        <span class="text-sm font-medium text-gray-900" x-text="new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(modalInternalBalance)"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">Saldo Doku (Online)</span>
                        <span class="text-sm font-medium text-gray-900" x-text="new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(modalDokuBalance)"></span>
                    </div>
                    <div class="flex justify-between text-lg font-bold border-t pt-2 mt-2">
                        <span class="text-gray-900">Total Dicairkan</span>
                        <span class="text-blue-600" x-text="new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(modalTotalBalance)"></span>
                    </div>
                </div>

                <div class="mt-4">
                    <label for="payout_amount" class="block text-sm font-medium text-gray-700">Jumlah Pencairan (Manual)</label>
                    <p class="text-xs text-gray-500 mb-1">Jika Anda ingin mencairkan sebagian, masukkan jumlah di bawah. Jika tidak, biarkan 0 untuk mencairkan semua.</p>
                    <input type="number" name="payout_amount" id="payout_amount" value="0"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                </div>
                
                <div class="mt-4">
                    <label for="payout_description" class="block text-sm font-medium text-gray-700">Catatan (Opsional)</label>
                    <input type="text" name="payout_description" id="payout_description" placeholder="Cth: Pencairan dana mingguan"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                </div>

                <div class="mt-2 p-3 bg-yellow-50 border-l-4 border-yellow-400 text-yellow-700 text-sm">
                    <strong>Penting:</strong> Tindakan ini akan memicu API Payout Doku untuk mentransfer dana dari Akun Utama Anda ke Sub-Account toko. Pastikan Anda sudah siap.
                </div>
            </div>

            {{-- ✅ PERBAIKAN: Tambahkan flex-shrink-0 pada Footer --}}
            <div class="flex justify-end p-4 border-t bg-gray-50 rounded-b-lg space-x-3 flex-shrink-0">
                <button type="button" @click="showModal = false" class="px-4 py-2 bg-white border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                    Batal
                </button>
                {{-- ✅ PERBAIKAN 2: Ubah type="submit" menjadi type="button" dan tambahkan @click --}}
                <button type="button" 
                        @click="$refs.payoutForm.submit()"
                        class="px-4 py-2 bg-green-600 border border-transparent rounded-md text-sm font-medium text-white hover:bg-green-700">
                    Ya, Lanjutkan Pencairan
                </button>
            </div>
        </form>
    </div>

</div>
@endsection