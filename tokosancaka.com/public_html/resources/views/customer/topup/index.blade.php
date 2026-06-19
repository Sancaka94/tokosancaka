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

    {{-- ALERT INFORMASI REFUND & CANCEL DANA --}}
    <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 rounded-r-lg shadow-sm">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="fas fa-exclamation-circle text-red-500 mt-1"></i>
            </div>
            <div class="ml-3">
                <p class="text-sm text-red-700 leading-relaxed font-medium">
                    Mohon maaf, refund dana dan cancel transaksi otomatis hanya bisa dilakukan ketika kakak memilih metode pembayaran dengan Saldo akun DANA. Jangan lupa pilih <strong>DANA BALANCE</strong> dan tautkan akun DANA Anda ke website Sancaka Express.
                    <br><br>
                    Terima kasih,<br>
                    <strong>Manajemen Sancaka Express</strong>
                </p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-lg">
        <div class="overflow-x-auto">
            <table class="w-full whitespace-no-wrap">
                <thead>
                    <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b bg-gray-50">
                        <th class="px-6 py-3">ID Transaksi</th>
                        <th class="px-6 py-3">Jumlah</th>
                        <th class="px-6 py-3">Metode</th>
                        <th class="px-6 py-3">Status</th>
                        <th class="px-6 py-3">Tanggal</th>
                        <th class="px-6 py-3">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y">
                    @forelse ($transactions ?? [] as $transaction)
                        <tr class="text-gray-700 hover:bg-gray-50">

                            <td class="px-6 py-4 font-medium">{{ $transaction->reference_id }}</td>

                            <td class="px-6 py-4 font-semibold text-green-600">Rp {{ number_format($transaction->amount, 0, ',', '.') }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600 font-medium">
                                {{ $transaction->payment_method ? str_replace('_', ' ', $transaction->payment_method) : str_replace('Top up saldo via ', '', $transaction->description ?? '-') }}
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-3 py-1 text-sm font-semibold leading-tight rounded-full
                                    @if($transaction->status == 'success') bg-green-100 text-green-700 @endif
                                    @if($transaction->status == 'pending') bg-yellow-100 text-yellow-700 @endif
                                    @if($transaction->status == 'failed') bg-red-100 text-red-700 @endif
                                    @if($transaction->status == 'refunded') bg-purple-100 text-purple-700 @endif
                                ">
                                    {{ ucfirst($transaction->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4">{{ $transaction->created_at->format('d M Y, H:i') }}</td>
                            <td class="px-6 py-4 flex items-center space-x-2">

                                <a href="{{ route('customer.topup.show', ['topup' => $transaction->reference_id]) }}" class="text-blue-600 hover:text-blue-800 font-medium hover:underline">
                                    Detail
                                </a>

                                {{-- Deteksi Khusus Metode DANA --}}
                                @if(str_contains(strtoupper($transaction->payment_method ?? ''), 'DANA') || str_contains(strtoupper($transaction->description ?? ''), 'DANA'))
                                    
                                    {{-- KECERDASAN TOMBOL: Deteksi Widget vs Payment Gateway --}}
                                    @php
                                        $isDanaWidget = str_contains(strtoupper($transaction->payment_method ?? ''), 'BINDING');
                                        
                                        // Generate URL Dinamis berdasarkan jenis DANA
                                        $cancelUrl = $isDanaWidget ? route('dana.widget.cancel_payment', $transaction->reference_id) : route('dana.cancel_payment', $transaction->reference_id);
                                        $refundUrl = $isDanaWidget ? route('dana.widget.refund_payment', $transaction->reference_id) : route('dana.refund_payment', $transaction->reference_id);
                                    @endphp

                                    {{-- Tombol Cek Status --}}
                                    @if(!in_array($transaction->status, ['failed', 'refunded']))
                                    <button type="button" onclick="cekStatusDana('{{ $transaction->reference_id }}')" 
                                    class="inline-flex items-center px-2 py-1 bg-blue-50 text-blue-600 border border-blue-200 rounded text-xs hover:bg-blue-100 transition-colors" title="Cek DANA">
                                        <i class="fas fa-sync-alt mr-1"></i> Cek
                                    </button>
                                    @endif

                                    {{-- Tombol Cancel (Mengirim URL Dinamis) --}}
                                    @if($transaction->status == 'pending')
                                    <button type="button" onclick="cancelDana('{{ $cancelUrl }}')" 
                                    class="inline-flex items-center px-2 py-1 bg-red-50 text-red-600 border border-red-200 rounded text-xs hover:bg-red-100 transition-colors" title="Batalkan Pesanan">
                                        <i class="fas fa-times-circle mr-1"></i> Batal
                                    </button>
                                    @endif

                                    {{-- Tombol Refund (Mengirim URL Dinamis) --}}
                                    @if($transaction->status == 'success')
                                    <button type="button" onclick="refundDana('{{ $refundUrl }}')" 
                                    class="inline-flex items-center px-2 py-1 bg-purple-50 text-purple-600 border border-purple-200 rounded text-xs hover:bg-purple-100 transition-colors" title="Kembalikan Dana">
                                        <i class="fas fa-undo mr-1"></i> Refund
                                    </button>
                                    @endif

                                @endif
                            </td>
                        </tr>
                    @empty
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

        @if(isset($transactions) && $transactions->hasPages())
            <div class="p-4 bg-white border-t">
                {{ $transactions->links() }}
            </div>
        @endif
    </div>

    {{-- MODAL SUKSES (POPUP) --}}
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

   @push('scripts')
    {{-- Memanggil Library SweetAlert2 --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        function cekStatusDana(orderId) {
            Swal.fire({
                title: 'Mengecek Status...',
                text: 'Menghubungi server DANA',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            const url = "{{ url('/uat-dana-status') }}/" + orderId;

            fetch(url)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(response => {
                    if (response.success && response.status === 'PAID') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Pembayaran Lunas!',
                            text: 'Status di DANA sudah PAID. Saldo berhasil masuk ke akun Anda.',
                            footer: '<span style="color:#6b7280; font-size:12px;">Ref: ' + orderId + '</span>'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                window.location.reload(); 
                            }
                        });
                        
                    } else if (response.success && response.status === 'PENDING') {
                        Swal.fire({
                            icon: 'info',
                            title: 'Masih Pending',
                            text: 'Transaksi ini belum dibayar oleh pelanggan di aplikasi DANA.',
                        });
                        
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal / Tidak Ditemukan',
                            text: response.message || 'Transaksi sudah kadaluarsa atau tidak ditemukan di DANA.'
                        });
                    }
                })
                .catch(error => {
                    console.error("Fetch Error: ", error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Koneksi Terputus',
                        text: 'Terjadi kesalahan sistem di server atau koneksi internet terputus.'
                    });
                });
        }
    </script>

    <script>
    // FUNGSI CANCEL CERDAS (Menerima URL Dinamis)
    function cancelDana(actionUrl) {
        Swal.fire({
            title: 'Batalkan Pesanan?',
            text: "Pesanan ini akan dibatalkan secara permanen di DANA.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Ya, Batalkan!',
            cancelButtonText: 'Tutup'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({ title: 'Memproses...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); }});

                fetch(actionUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                })
                .then(res => res.json())
                .then(response => {
                    if(response.success) {
                        Swal.fire('Dibatalkan!', response.message, 'success').then(() => window.location.reload());
                    } else {
                        Swal.fire('Gagal', response.message, 'error');
                    }
                }).catch(error => {
                    Swal.fire('Error', 'Terjadi kesalahan sistem.', 'error');
                });
            }
        });
    }

    // FUNGSI REFUND CERDAS (Menerima URL Dinamis)
    function refundDana(actionUrl) {
        Swal.fire({
            title: 'Refund Saldo?',
            text: "Saldo pelanggan ini akan ditarik dan dikembalikan ke akun DANA mereka.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#9333ea',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Ya, Refund Sekarang',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({ title: 'Memproses Refund...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); }});

                fetch(actionUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                })
                .then(res => res.json())
                .then(response => {
                    if(response.success) {
                        Swal.fire('Berhasil!', response.message, 'success').then(() => window.location.reload());
                    } else {
                        Swal.fire('Gagal', response.message, 'error');
                    }
                }).catch(error => {
                    Swal.fire('Error', 'Terjadi kesalahan sistem.', 'error');
                });
            }
        });
    }
</script>

@endpush

@endsection