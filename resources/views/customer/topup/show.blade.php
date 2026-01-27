@extends('layouts.customer')

@section('title', 'Detail Top Up - ' . $topUp->reference_id)

@section('content')

    {{-- Main container with a subtle gradient background --}}
    <div class="bg-gradient-to-br from-gray-50 to-gray-200 min-h-screen flex items-center justify-center p-4 sm:p-6 font-sans">

        {{-- Invoice Card --}}
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-4xl overflow-hidden">

            {{-- Header Section with Branding --}}
            <div class="p-6 border-b border-gray-200">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    {{-- Company Branding --}}
                    <div class="flex items-center">
                        <img src="https://tokosancaka.biz.id/storage/uploads/logo.jpeg" alt="Logo CV. Sancaka Karya Hutama" class="h-16 w-16 mr-4 flex-shrink-0 rounded-lg object-cover" onerror="this.style.display='none';">
                        <div>
                            <h2 class="text-lg font-bold text-gray-800">CV. Sancaka Karya Hutama</h2>
                            <div class="flex items-start text-xs text-gray-500 mt-1">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2 mt-0.5 flex-shrink-0" fill="none" viewBox="0-0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <span class="max-w-xs">JL.DR.WAHIDIN NO.18A RT.22 RW.05 KEL.KETANGGI KEC.NGAWI KAB.NGAWI JAWA TIMUR 63211</span>
                            </div>
                            <div class="flex items-center text-xs text-gray-500 mt-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2 flex-shrink-0" fill="none" viewBox="0-0 24 24" stroke="currentColor">
                                   <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                </svg>
                                <span>085745808809 / 08819435180</span>
                            </div>
                        </div>
                    </div>

                    {{-- Invoice Details --}}
                    <div class="text-left sm:text-right w-full sm:w-auto flex-shrink-0">
                        <h1 class="text-2xl font-bold text-blue-600">DETAIL TOP UP</h1>
                        <p class="font-semibold text-gray-700">#{{ $topUp->reference_id }}</p>
                        <p class="text-sm text-gray-500">Tanggal: {{ $topUp->created_at->format('d/m/Y') }}</p>
                    </div>
                </div>
            </div>

            {{-- Main Content Section with Horizontal Layout --}}
            <div class="flex flex-col md:flex-row">

                {{-- Left Column: Order Details --}}
                <div class="w-full md:w-1/2 p-8">
                    <div class="mb-6">
                        <h2 class="text-base font-semibold text-gray-700 mb-3">Detail Pelanggan</h2>
                        <div class="text-sm text-gray-600 space-y-1">
                            <p class="font-medium text-gray-800">{{ $topUp->user->nama_lengkap ?? 'Nama Pelanggan' }}</p>
                            <p>{{ $topUp->user->no_wa ?? '' }}</p>
                            <p>{{ $topUp->user->email ?? '' }}</p>
                        </div>
                    </div>

                    {{-- Ringkasan Top Up --}}
                    <h3 class="text-base font-semibold text-gray-700 mb-2">Ringkasan Top Up</h3>
                    <ul role="list" class="divide-y divide-gray-200 border-b border-t border-gray-200">
                        <li class="flex py-4 items-center">
                            <div class="h-16 w-16 flex-shrink-0 overflow-hidden rounded-md bg-blue-50 flex items-center justify-center">
                                <svg xmlns="https://tokosancaka.com/public/assets/saldo.png" class="h-8 w-8 text-blue-500" fill="none" viewBox="0-0 24 24" stroke="currentColor">
                                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                            </div>
                            <div class="ml-4 flex flex-1 flex-col text-sm">
                                <h4 class="font-medium text-gray-800">Top Up Saldo</h4>
                                <p class="text-gray-500">Top up saldo via {{ $topUp->payment_method }}</p>
                                <p class="text-gray-500 mt-auto">Metode: {{ $topUp->description }}</p>
                            </div>
                        </li>
                    </ul>

                    {{-- Rincian Biaya --}}
                    <div class="mt-6 space-y-4">
                        <div class="flex justify-between text-lg font-bold text-gray-800 border-t border-gray-200 pt-4">
                            <span>Nominal Top Up:</span>
                            <span class="text-blue-600">Rp {{ number_format($topUp->amount, 0, ',', '.') }}</span>
                        </div>
                    </div>

                    <div class="mt-8 text-center">
                        @php
                            $status = strtolower($topUp->status);
                            $badgeClass = match($status) {
                                'pending'    => 'bg-yellow-100 text-yellow-800 border border-yellow-300',
                                'success'    => 'bg-green-100 text-green-800 border border-green-300',
                                'failed'     => 'bg-red-100 text-red-800 border border-red-300',
                                'expired'    => 'bg-gray-100 text-gray-800 border border-gray-300',
                                default      => 'bg-gray-100 text-gray-800 border border-gray-300'
                            };
                        @endphp

                        <p class="text-gray-600">Status:
                            <span class="px-4 py-1.5 rounded-full text-sm font-semibold {{ $badgeClass }}">
                                {{ ucfirst($status) }}
                            </span>
                        </p>
                    </div>
                </div>

                {{-- Right Column: Payment Instructions --}}
                <div class="w-full md:w-1/2 p-8 bg-gray-50 md:border-l border-t md:border-t-0 border-gray-200">

                        {{-- Tampilkan instruksi HANYA jika status 'pending' --}}
                        @if($status === 'pending')
                            <div class="h-full flex flex-col justify-center">
                                <h2 class="text-lg font-semibold text-gray-800 mb-4 text-center flex items-center justify-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2 text-gray-400" fill="none" viewBox="0-0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                    </svg>
                                    Instruksi Pembayaran
                                </h2>

                                @php
                                    $method = trim(strtoupper($topUp->payment_method));
                                    $description = trim(strtoupper($topUp->description)); // <-- KITA TAMBAHKAN INI
                                    $url    = $topUp->payment_url;
                                    $virtualAccounts = [
                                        'PERMATAVA','BNIVA','BRIVA','MANDIRIVA','BCAVA','MUAMALATVA',
                                        'CIMBVA','BSIVA','OCBCVA','DANAMONVA','OTHERBANKVA'
                                    ];
                                @endphp

                                <div class="text-center">

                                    {{-- 1. QRIS --}}
                                    @if (str_contains($method, 'QRIS'))
                                        <p class="text-gray-600 mb-4">Scan QR di bawah ini:</p>
                                        <div class="flex justify-center p-2 bg-white rounded-lg shadow-inner">
                                            <img src="{{ $url }}" alt="QRIS Payment" class="w-48 h-48 rounded-md">
                                        </div>
                                        <p class="mt-4 text-xs text-gray-500">Halaman ini akan diperbarui secara otomatis.</p>

                                    {{-- 2. DOKU / E-Wallet (Redirect) --}}
                                    @elseif (str_contains($method, 'DOKU_JOKUL') || in_array($method, ['OVO', 'DANA', 'SHOPEEPAY', 'LINKAJA']))
                                        {{-- DOKU atau E-Wallet redirect --}}
                                        <script>
                                            window.location.href = "{{ $url }}";
                                        </script>
                                        <p class="text-gray-600 mb-4">Anda akan diarahkan ke halaman pembayaran...</p>
                                        <a href="{{ $url }}" target="_blank" class="inline-block">
                                            <button class="px-8 py-3 bg-purple-600 text-white font-semibold rounded-lg shadow-md hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-opacity-75 transition-transform transform hover:scale-105">
                                                Bayar Sekarang
                                            </button>
                                        </a>

                                    {{-- 3. Virtual Account (Tampilkan Nomor) --}}
                                    @elseif (in_array($method, $virtualAccounts))
                                        <p class="text-gray-600 mb-2">Gunakan Virtual Account berikut:</p>
                                        <div class="bg-white p-4 rounded-lg border-2 border-dashed">
                                            <strong class="text-2xl font-mono tracking-widest text-blue-600">
                                                {{ $url }}
                                            </strong>
                                        </div>
                                        <p class="mt-4 text-xs text-gray-500">Status akan diperbarui secara otomatis.</p>

                                    {{-- 4. TRANSFER MANUAL (Info Rekening & Form Upload) --}}
@elseif ($method === 'TRANSFER_MANUAL' || str_contains($description, 'TRANSFER MANUAL'))
    {{-- Transfer Manual --}}
    <h3 class="text-base font-semibold text-gray-700 mb-3">Transfer Manual</h3>
    <p class="text-sm text-gray-600 mb-4">Silakan transfer ke salah satu rekening resmi kami:</p>

    <div class="text-left space-y-3 p-4 bg-white rounded-lg border border-gray-200 shadow-inner">

        {{-- BCA --}}
        <div class="flex justify-between items-center account-row">
            <p class="font-bold text-gray-800">
                BCA: <span class="font-mono text-blue-600 account-number" data-account="7790480494">7790480494</span>
            </p>
            <button class="copy-btn px-3 py-1 bg-green-500 text-white text-xs rounded-full hover:bg-green-600 transition"
                    data-clipboard-target="7790480494">
                <i class="fas fa-copy mr-1"></i> Copy
            </button>
        </div>

        {{-- BRI --}}
        <div class="flex justify-between items-center account-row">
            <p class="font-bold text-gray-800">
                BRI: <span class="font-mono text-blue-600 account-number" data-account="005701004162308">005701004162308</span>
            </p>
            <button class="copy-btn px-3 py-1 bg-green-500 text-white text-xs rounded-full hover:bg-green-600 transition"
                    data-clipboard-target="005701004162308">
                <i class="fas fa-copy mr-1"></i> Copy
            </button>
        </div>

        {{-- MANDIRI --}}
        <div class="flex justify-between items-center account-row">
            <p class="font-bold text-gray-800">
                MANDIRI: <span class="font-mono text-blue-600 account-number" data-account="1710018351539">1710018351539</span>
            </p>
            <button class="copy-btn px-3 py-1 bg-green-500 text-white text-xs rounded-full hover:bg-green-600 transition"
                    data-clipboard-target="1710018351539">
                <i class="fas fa-copy mr-1"></i> Copy
            </button>
        </div>

        <p class="text-center font-semibold text-gray-800 pt-2 border-t border-gray-100">a/n CV. SANCAKA KARYA HUTAMA</p>
    </div>


                                        {{-- Pesan Peringatan --}}
                                        <div class="mt-4 p-3 bg-yellow-100 border-l-4 border-yellow-500 text-yellow-800 text-sm text-left">
                                            <p class="font-bold">Mohon kerjasamanya,</p>
                                            <p>Transfer Lunas Akan Di Anggap Sah Apabila Bapak/Ibu Melampirkan Bukti Transfer. Terima kasih Banyak.</p>
                                            <p class="font-medium mt-1">- Menejemen Sancaka Express</p>
                                        </div>

                                        {{-- Form Upload Bukti (Alur Baru) --}}

                                        @if($topUp->payment_proof_path)
                                            {{-- Jika SUDAH upload --}}
                                            <div class="mt-4">
                                                <p class="text-sm font-medium text-gray-700 mb-2">Bukti Transfer Anda (Sedang ditinjau):</p>
                                                <img src="{{ asset('public/storage/' . $topUp->payment_proof_path) }}" alt="Bukti Bayar" class="w-full max-w-xs mx-auto rounded-lg shadow-md border border-gray-300">
                                                <p class="mt-2 text-xs text-gray-500 text-center">Anda dapat meng-upload ulang jika ada kesalahan.</p>
                                            </div>
                                        @endif

                                        {{-- Form untuk UPLOAD BARU atau UPLOAD ULANG --}}
                                        <form action="{{ route('customer.topup.upload_proof', $topUp->reference_id) }}" method="POST" enctype="multipart/form-data" class="mt-4 space-y-3 p-4 border border-gray-200 rounded-lg bg-white shadow-inner">
                                            @csrf

                                            <div>
                                                <label for="proof_of_payment" class="block text-sm font-medium text-gray-700 text-left">
                                                    {{ $topUp->payment_proof_path ? 'Upload Ulang Bukti Transfer' : 'Upload Bukti Transfer Anda' }}
                                                </label>
                                                <input type="file" name="proof_of_payment" id="proof_of_payment"
                                                       required
                                                       accept="image/png, image/jpeg, image/jpg"
                                                       class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                                                @error('proof_of_payment')
                                                    <p class="text-red-500 text-xs mt-1 text-left">{{ $message }}</p>
                                                @enderror
                                            </div>

                                            <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                                Kirim Bukti Transfer
                                            </button>
                                        </form>

                                    {{-- 5. Fallback/Lainnya (Mis. Alfamart/Indomaret) --}}
                                    @else
                                        <p class="text-gray-600 mb-2">Gunakan Kode Pembayaran berikut:</p>
                                        <div class="bg-white p-4 rounded-lg border-2 border-dashed">
                                            <strong class="text-2xl font-mono tracking-widest text-blue-600">
                                                {{ $url }}
                                            </strong>
                                        </div>
                                        <p class="mt-4 text-xs text-gray-500">Status akan diperbarui secara otomatis.</p>
                                    @endif
                                </div>
                            </div>

                        {{-- Tampilkan ini jika pembayaran SUDAH LUNAS/GAGAL (bukan 'pending') --}}
                        @else
                            <div class="h-full flex flex-col justify-center items-center text-center">

                                @if($status === 'success')
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-green-500" fill="none" viewBox="0-0 24 24" stroke="currentColor">
                                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <h2 class="text-xl font-semibold text-gray-800 mt-3">Top Up Berhasil</h2>
                                    <p class="text-gray-600 mt-2">Saldo Anda telah berhasil ditambahkan.</p>
                                @else
                                    {{-- Gagal, Kadaluwarsa, dll. --}}
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-red-500" fill="none" viewBox="0-0 24 24" stroke="currentColor">
                                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <h2 class="text-xl font-semibold text-gray-800 mt-3">Top Up {{ ucfirst($status) }}</h2>
                                    <p class="text-gray-600 mt-2">Transaksi ini telah {{ $status }}.</p>
                                @endif

                                <a href="{{ route('customer.topup.index') }}" class="mt-6 px-6 py-2 bg-blue-600 text-white font-semibold rounded-lg shadow-md hover:bg-blue-700">
                                    Kembali ke Riwayat Top Up
                                </a>
                            </div>
                        @endif

                </div>
            </div>
        </div>
    </div>
@endsection

{{-- ========================================================== --}}
{{-- === 3. SCRIPT BARU UNTUK AUTO-REFRESH (POLLING) === --}}
{{-- ========================================================== --}}
@push('scripts')
<script>
    // Pastikan kita hanya menjalankan poller jika status HANYA 'pending'
    @if(strtolower($topUp->status) === 'pending')

        // Fungsi untuk mengecek status
        const checkStatus = () => {
            // URL API yang kita buat di Langkah 2
            const statusCheckUrl = "{{ route('customer.topup.check_status', $topUp->reference_id) }}";

            fetch(statusCheckUrl)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Current status:', data.status);

                    // Jika status dari server BUKAN 'pending' lagi
                    if (data.status !== 'pending') {
                        // Hentikan interval polling
                        clearInterval(pollingInterval);
                        // Muat ulang halaman untuk menampilkan status baru (Success/Failed)
                        window.location.reload();
                    }
                    // Jika masih 'pending', biarkan saja, interval akan berjalan lagi
                })
                .catch(error => {
                    console.error('Error polling status:', error);
                    // Hentikan polling jika ada error
                    clearInterval(pollingInterval);
                });
        };

        // Mulai polling: Panggil fungsi checkStatus() setiap 5 detik (5000 ms)
        const pollingInterval = setInterval(checkStatus, 3000);

    @endif

    // ==========================================================
    // === FUNGSI COPY NOMOR REKENING (Vanilla JS) ===
    // ==========================================================
    document.addEventListener('DOMContentLoaded', () => {
        const copyButtons = document.querySelectorAll('.copy-btn');

        copyButtons.forEach(button => {
            button.addEventListener('click', (e) => {
                const accountNumber = button.getAttribute('data-clipboard-target');

                if (accountNumber) {
                    // Gunakan Clipboard API modern untuk menyalin
                    navigator.clipboard.writeText(accountNumber).then(() => {
                        // Feedback visual setelah berhasil copy
                        const originalText = button.innerHTML;
                        button.innerHTML = '<i class="fas fa-check"></i> Disalin!';
                        button.classList.remove('bg-green-500');
                        button.classList.add('bg-blue-500');

                        // Kembalikan tombol ke kondisi semula setelah 2 detik
                        setTimeout(() => {
                            button.innerHTML = originalText;
                            button.classList.remove('bg-blue-500');
                            button.classList.add('bg-green-500');
                        }, 2000);

                    }).catch(err => {
                        // Fallback jika API gagal (jarang terjadi di browser modern)
                        alert("Gagal menyalin nomor rekening: " + accountNumber);
                        console.error('Could not copy text: ', err);
                    });
                }
            });
        });
    });
</script>
@endpush
