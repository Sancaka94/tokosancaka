@extends('layouts.customer')

@section('content')
    <div class="flex justify-between items-center mb-8">
        <div>
            <h3 class="text-3xl font-semibold text-gray-700">Riwayat Top Up</h3>
            <p class="mt-1 text-gray-500">Berikut adalah riwayat semua transaksi top up saldo Anda.</p>
        </div>
        <a href="{{ route('customer.topup.create') }}" class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:bg-blue-700 shadow-lg">
            <i class="fas fa-plus-circle mr-2"></i>
            Top Up Sekarang
        </a>
    </div>

    <div class="bg-white rounded-lg shadow-lg">
        <div class="overflow-x-auto">
            <table class="w-full whitespace-no-wrap">
                <thead>
                    <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b bg-gray-50">
                        <th class="px-6 py-3">ID Transaksi</th>
                        <th class="px-6 py-3">Jumlah</th>
                        <th class="px-6 py-3">Status</th>
                        <th class="px-6 py-3">Tanggal</th>
                        <th class="px-6 py-3">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y">
                    {{-- ✅ PERBAIKAN: Menggunakan $transactions (dari Controller) dan $transaction (loop) --}}
                    @forelse ($transactions ?? [] as $transaction)
                        <tr class="text-gray-700 hover:bg-gray-50">

                            {{-- ✅ PERBAIKAN: Menggunakan reference_id --}}
                            <td class="px-6 py-4 font-medium">{{ $transaction->reference_id }}</td>

                            <td class="px-6 py-4 font-semibold text-green-600">Rp {{ number_format($transaction->amount, 0, ',', '.') }}</td>
                            <td class="px-6 py-4">
                                <span class="px-3 py-1 text-sm font-semibold leading-tight rounded-full
                                    @if($transaction->status == 'success') bg-green-100 text-green-700 @endif
                                    @if($transaction->status == 'pending') bg-yellow-100 text-yellow-700 @endif
                                    @if($transaction->status == 'failed') bg-red-100 text-red-700 @endif
                                ">
                                    {{ ucfirst($transaction->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4">{{ $transaction->created_at->format('d M Y, H:i') }}</td>
                            <td class="px-6 py-4">

                                {{-- ✅ PERBAIKAN: Memperbaiki panggilan route agar sesuai dengan controller --}}
                                <a href="{{ route('customer.topup.show', ['topup' => $transaction->reference_id]) }}" class="text-blue-600 hover:underline">Detail</a>

                            </td>
                        </tr>
                    @empty
                        {{-- Tampilan ini akan muncul jika tidak ada data top up --}}
                        <tr>
                            <td colspan="5" class="text-center py-16">
                                <div class="flex flex-col items-center">
                                    <i class="fas fa-wallet fa-3x text-gray-400 mb-3"></i>
                                    <h3 class="text-lg font-semibold text-gray-700">Belum Ada Riwayat</h3>
                                    <p class="text-gray-500">Anda belum pernah melakukan top up.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- ✅ PERBAIKAN: Menggunakan $transactions (dari Controller) --}}
        @if(isset($transactions) && $transactions->hasPages())
            <div class="p-4 bg-white border-t">
                {{ $transactions->links() }}
            </div>
        @endif
    </div>

    {{-- ================================================================= --}}
    {{-- MODAL SUKSES (POPUP) --}}
    {{-- Akan muncul otomatis jika ada session 'dana_success' --}}
    {{-- ================================================================= --}}
    @if(session('dana_success'))
    <div x-data="{ show: true }" x-show="show"
         class="fixed inset-0 z-50 overflow-y-auto"
         aria-labelledby="modal-title" role="dialog" aria-modal="true" style="display: none;">

        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">

            {{-- Background Overlay --}}
            <div x-show="show"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            {{-- Modal Panel --}}
            <div x-show="show"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">

                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-green-100 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 text-green-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                Transaksi Diproses!
                            </h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500">
                                    {{ session('dana_success') }} <br><br>
                                    Sistem sedang memverifikasi pembayaran Anda dari DANA. Saldo akan bertambah otomatis dalam hitungan detik. Silakan refresh halaman ini.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="button" @click="show = false; window.location.reload();" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm">
                        Refresh Halaman
                    </button>
                    <button type="button" @click="show = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

@endsection
