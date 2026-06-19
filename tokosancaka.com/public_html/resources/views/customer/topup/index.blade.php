@extends('layouts.customer')

@section('content')
    <div class="flex justify-between items-center mb-8">
        <div>
            <h3 class="text-3xl font-semibold text-gray-700">Riwayat Top Up</h3>
            <p class="mt-1 text-gray-500">Berikut adalah riwayat semua transaksi top up saldo Anda.</p>
        </div>
        <a href="{{ route('customer.topup.create') }}" class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:bg-blue-700 shadow-lg transition">
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
                        <tr class="text-gray-700 hover:bg-gray-50 transition">

                            <td class="px-6 py-4 font-medium">{{ $transaction->reference_id }}</td>

                            <td class="px-6 py-4 font-semibold text-green-600">Rp {{ number_format($transaction->amount, 0, ',', '.') }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600 font-medium">
                                {{ $transaction->payment_method ? str_replace('_', ' ', $transaction->payment_method) : str_replace('Top up saldo via ', '', $transaction->description ?? '-') }}
                            </td>
                            <td class="px-6 py-4">
                                @php
                                    $status = strtolower($transaction->status);
                                    $statusClass = match($status) {
                                        'success', 'paid' => 'bg-green-100 text-green-700',
                                        'pending'         => 'bg-yellow-100 text-yellow-700',
                                        'failed'          => 'bg-red-100 text-red-700',
                                        'refunded'        => 'bg-purple-100 text-purple-700',
                                        default           => 'bg-gray-100 text-gray-700'
                                    };
                                @endphp
                                <span class="px-3 py-1 text-sm font-semibold leading-tight rounded-full {{ $statusClass }}">
                                    {{ ucfirst($status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4">{{ $transaction->created_at->format('d M Y, H:i') }}</td>
                            <td class="px-6 py-4 flex items-center space-x-2">

                                <a href="{{ route('customer.topup.show', ['topup' => $transaction->reference_id]) }}" class="text-blue-600 hover:text-blue-800 font-medium hover:underline transition">
                                    Detail
                                </a>

                                {{-- Deteksi Khusus Metode DANA --}}
                                @if(str_contains(strtoupper($transaction->payment_method ?? ''), 'DANA') || str_contains(strtoupper($transaction->description ?? ''), 'DANA'))
                                    
                                    {{-- KECERDASAN TOMBOL: Deteksi Widget vs Payment Gateway --}}
                                    @php
                                        $isDanaWidget = str_contains(strtoupper($transaction->payment_method ?? ''), 'BINDING');
                                        
                                        // URL Dinamis ke API JSON
                                        $cancelUrl = $isDanaWidget ? route('api.dana.widget.cancel_payment', $transaction->reference_id) : route('api.dana.cancel_payment', $transaction->reference_id);
                                        $refundUrl = $isDanaWidget ? route('api.dana.widget.refund_payment', $transaction->reference_id) : route('api.dana.refund_payment', $transaction->reference_id);
                                    @endphp

                                    {{-- Tombol Cek Status --}}
                                    @if(!in_array($status, ['failed', 'refunded']))
                                    <button type="button" onclick="cekStatusDana('{{ $transaction->reference_id }}')" 
                                    class="inline-flex items-center px-2 py-1 bg-blue-50 text-blue-600 border border-blue-200 rounded text-xs hover:bg-blue-100 transition-colors" title="Cek DANA">
                                        <i class="fas fa-sync-alt mr-1"></i> Cek
                                    </button>
                                    @endif

                                    {{-- Tombol Cancel (Pake tipe 'button' agar dieksekusi AJAX) --}}
                                    @if($status == 'pending')
                                    <button type="button" onclick="cancelDana('{{ $cancelUrl }}')" 
                                    class="inline-flex items-center px-2 py-1 bg-red-50 text-red-600 border border-red-200 rounded text-xs hover:bg-red-100 transition-colors" title="Batalkan Pesanan">
                                        <i class="fas fa-times-circle mr-1"></i> Batal
                                    </button>
                                    @endif

                                    {{-- Tombol Refund (Pake tipe 'button' agar dieksekusi AJAX) --}}
                                    @if(in_array($status, ['success', 'paid']))
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
                            <td colspan="6" class="text-center py-16">
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

            fetch(url, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
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
                        }).then(() => window.location.reload());
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

        // FUNGSI CANCEL AJAX
        function cancelDana(actionUrl) {
            Swal.fire({
                title: 'Batalkan Pesanan?',
                text: "Pesanan ini akan dibatalkan secara permanen di DANA.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Ya, Batalkan!',
                cancelButtonText: 'Tutup'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({ title: 'Memproses...', text: 'Menghubungi DANA', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); }});

                    fetch(actionUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json', // <--- WAJIB ADA AGAR LARAVEL TAHU INI AJAX
                            'X-Requested-With': 'XMLHttpRequest', // <--- WAJIB ADA AGAR LARAVEL TAHU INI AJAX
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        }
                    })
                    .then(res => res.json())
                    .then(response => {
                        // Cek status sesuai dengan helper backend kita
                        if(response.status === 'success') {
                            Swal.fire('Dibatalkan!', response.message, 'success').then(() => window.location.reload());
                        } else {
                            Swal.fire('Gagal', response.message || 'Terjadi kesalahan sistem.', 'error');
                        }
                    }).catch(error => {
                        console.error('Error:', error);
                        Swal.fire('Error', 'Gagal membatalkan pesanan. Cek console log untuk detail.', 'error');
                    });
                }
            });
        }

        // FUNGSI REFUND AJAX
        function refundDana(actionUrl) {
            Swal.fire({
                title: 'Refund Saldo?',
                text: "Saldo Sancaka Anda akan ditarik dan dikembalikan ke akun DANA Anda.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#9333ea',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Ya, Refund Sekarang',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({ title: 'Memproses Refund...', text: 'Mengembalikan dana ke DANA', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); }});

                    fetch(actionUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json', // <--- WAJIB ADA
                            'X-Requested-With': 'XMLHttpRequest', // <--- WAJIB ADA
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        }
                    })
                    .then(res => res.json())
                    .then(response => {
                        // Cek status sesuai dengan helper backend kita
                        if(response.status === 'success') {
                            Swal.fire('Berhasil!', response.message, 'success').then(() => window.location.reload());
                        } else {
                            Swal.fire('Gagal', response.message || 'Terjadi kesalahan sistem.', 'error');
                        }
                    }).catch(error => {
                        console.error('Error:', error);
                        Swal.fire('Error', 'Gagal memproses refund. Server tidak merespons JSON dengan benar.', 'error');
                    });
                }
            });
        }
    </script>
@endpush
@endsection