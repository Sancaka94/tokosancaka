{{-- Menggunakan layout customer Anda --}}
@extends('layouts.customer')

@section('title', 'Dompet Sancaka Express')

@section('content')
<div class="container mx-auto p-4 sm:p-8">

    {{-- ========================================================== --}}
    {{-- == 1. JUDUL HALAMAN == --}}
    {{-- ========================================================== --}}
    <h1 class="text-3xl font-bold text-gray-800 mb-6">Integrasi Pembayaran Dompet Sancaka Express</h1> {{-- Sesuai branding Anda --}}

    {{-- ========================================================== --}}
    {{-- == 2. NOTIFIKASI GLOBAL (UNTUK REFRESH, PAYOUT, DLL) == --}}
    {{-- ========================================================== --}}
    @if(session('success'))
        <div x-data="{ show: true }" x-show="show" x-transition
             x-init="setTimeout(() => show = false, 5000)"
             class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-md relative" role="alert">
            <p>{{ session('success') }}</p>
            <button @click="show = false" class="absolute top-0 bottom-0 right-0 px-4 py-3 text-green-700">&times;</button>
        </div>
    @endif
    @if(session('error'))
        <div x-data="{ show: true }" x-show="show" x-transition
             x-init="setTimeout(() => show = false, 5000)"
             class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-md relative" role="alert">
            <p>{{ session('error') }}</p>
            <button @click="show = false" class="absolute top-0 bottom-0 right-0 px-4 py-3 text-red-700">&times;</button>
        </div>
    @endif
    @if($errors->any())
        <div x-data="{ show: true }" x-show="show" x-transition
             x-init="setTimeout(() => show = false, 8000)" {{-- Beri waktu lebih untuk membaca error validasi --}}
             class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-md relative" role="alert">
            <p class="font-bold">Terjadi kesalahan validasi:</p>
            <ul class="list-disc list-inside mt-2">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button @click="show = false" class="absolute top-0 bottom-0 right-0 px-4 py-3 text-red-700">&times;</button>
        </div>
    @endif


    {{-- Cek apakah sudah terdaftar atau belum --}}
    @if($store->doku_sac_id)

        {{-- ========================================================== --}}
        {{-- == 3. TAMPILAN JIKA SUDAH TERDAFTAR (KONTROL PANEL) == --}}
        {{-- ========================================================== --}}

        <div x-data="{ tab: '{{ session('tab', 'ringkasan') }}' }" class="bg-white rounded-lg shadow-md">

            <!-- Navigasi Tab -->
            <div class="border-b border-gray-200">
                <nav class="flex flex-wrap -mb-px px-6" aria-label="Tabs">
                    <button @click="tab = 'ringkasan'"
                            :class="{ 'border-blue-500 text-blue-600': tab === 'ringkasan', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': tab !== 'ringkasan' }"
                            class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm mr-8 focus:outline-none">
                        Ringkasan
                    </button>
                    <button @click="tab = 'payout'"
                            :class="{ 'border-blue-500 text-blue-600': tab === 'payout', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': tab !== 'payout' }"
                            class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm mr-8 focus:outline-none">
                        Kirim Payout (Withdrawal)
                    </button>
                    <button @click="tab = 'transfer'"
                            :class="{ 'border-blue-500 text-blue-600': tab === 'transfer', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': tab !== 'transfer' }"
                            class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm mr-8 focus:outline-none">
                        Transfer Antar Akun
                    </button>
                    <button @click="tab = 'info'"
                            :class="{ 'border-blue-500 text-blue-600': tab === 'info', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': tab !== 'info' }"
                            class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm mr-8 focus:outline-none">
                        Info Terima Pembayaran
                    </button>
                </nav>
            </div>

            <!-- Konten Tab -->
            <div class="p-6 md:p-8">

                {{-- ====================================== --}}
                {{-- == KONTEN TAB 1: RINGKASAN == --}}
                {{-- ====================================== --}}
                <div x-show="tab === 'ringkasan'" x-transition:enter="transition-opacity duration-300" x-transition:enter-start="opacity-0">
                    <h2 class="text-xl font-bold text-gray-800 mb-4">Status Akun Dompet Anda</h2>

                    <div class="space-y-4">

                        <div class="flex flex-col sm:flex-row sm:space-x-8">
                            <div>
                                <span class="block text-sm font-medium text-gray-500">Status Registrasi</span>
                                <span class="inline-flex items-center px-3 py-0.5 rounded-full text-sm font-medium bg-green-100 text-green-800">
                                    Terdaftar
                                </span>
                            </div>

                            <div>
                                <span class="block text-sm font-medium text-gray-500">Status Akun</span>
                                @if($store->doku_status == 'ACTIVE')
                                    <span class="inline-flex items-center px-3 py-0.5 rounded-full text-sm font-medium bg-green-100 text-green-800">
                                        Aktif
                                    </span>
                                @elseif($store->doku_status == 'PENDING')
                                    <span class="inline-flex items-center px-3 py-0.5 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800">
                                        Pending
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-3 py-0.5 rounded-full text-sm font-medium bg-red-100 text-red-800">
                                        {{ $store->doku_status ?? 'Tidak Diketahui' }}
                                    </span>
                                @endif

                                <form action="{{ route('seller.doku.refresh_status') }}" method="POST" class="inline-block ml-2">
                                    @csrf
                                    {{-- Tambahkan ID="btn-auto-status" disini --}}
                                    <button type="submit" id="btn-auto-status" class="...">
                                        <i class="fas fa-sync-alt"></i> Cek Status
                                    </button>
                                </form>
                            </div>
                        </div>

                        <div>
                            <span class="block text-sm font-medium text-gray-500">Sub Account ID</span>
                            <span class="text-lg font-semibold text-gray-900">{{ $store->doku_sac_id }}</span>
                        </div>

                        {{-- Saldo Dompet Sancaka (DOKU) --}}
                        <div class="pt-4 border-t">
                            <div class="flex justify-between items-center mb-2">
                                <h3 class="text-lg font-medium text-gray-900">Saldo Dompet Anda (SANCAKA EXPRESS)</h3>

                                <form action="{{ route('seller.doku.refreshBalance') }}" method="POST"
                                    x-data="{ isRefreshing: false }"
                                    @submit="isRefreshing = true">
                                    @csrf
                                    {{-- TAMBAHAN: id="btn-auto-saldo" agar bisa diklik otomatis --}}
                                    <button type="submit"
                                            id="btn-auto-saldo"
                                            :disabled="isRefreshing"
                                            class="text-sm text-blue-600 hover:text-blue-800 transition-colors"
                                            :class="{ 'opacity-50 cursor-not-allowed': isRefreshing }">
                                        <i class="fas fa-sync-alt fa-fw" :class="{ 'fa-spin': isRefreshing }"></i>
                                        <span x-text="isRefreshing ? 'Menyinkronkan...' : 'Refresh Saldo'"></span>
                                    </button>
                                </form>
                            </div>

                            <div class="flex flex-col sm:flex-row space-y-4 sm:space-y-0 sm:space-x-4 mt-2">
                                <div class="flex-1 bg-green-50 p-4 rounded-lg border border-green-200">
                                    <span class="block text-sm text-green-700">Saldo Tersedia</span>
                                    <span class="text-2xl font-bold text-green-900">Rp {{ number_format($store->doku_balance_available, 0, ',', '.') }}</span>
                                </div>
                                <div class="flex-1 bg-yellow-50 p-4 rounded-lg border border-yellow-200">
                                    <span class="block text-sm text-yellow-700">Saldo Tertunda</span>
                                    <span class="text-2xl font-bold text-yellow-900">Rp {{ number_format($store->doku_balance_pending, 0, ',', '.') }}</span>
                                </div>
                            </div>
                            <p class="text-xs text-gray-400 mt-2">
                                @if($store->doku_balance_last_updated)
                                    Saldo diperbarui pada: {{ $store->doku_balance_last_updated->format('d M Y, H:i') }}
                                @else
                                    Saldo belum pernah disinkronkan.
                                @endif
                            </p>
                        </div>

                        {{-- ========================================================== --}}
                        {{-- === PERBAIKAN: Form Pencairan Saldo Utama ke Dompet === --}}
                        {{-- ========================================================== --}}
                        <div class="pt-4 border-t">
                            <h3 class="text-lg font-medium text-gray-900">Pencairan Saldo Utama ke Dompet</h3>
                            <p class="text-sm text-gray-600 mt-1">Pindahkan dana dari Saldo Utama Sancaka Express Anda (Saldo Anda) ke Dompet Sancaka Express.</p>

                            {{-- Tampilkan Saldo Utama Saat Ini --}}
                            <div class="mt-4 bg-gray-50 p-4 rounded-lg border">
                                <span class="block text-sm text-gray-700">Saldo Utama Sancaka Anda Saat Ini</span>
                                <span class="text-2xl font-bold text-gray-900">Rp {{ number_format(Auth::user()->saldo, 0, ',', '.') }}</span>
                            </div>

                            @if($store->doku_status !== 'ACTIVE')
                                <div class="mt-4 bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 rounded-md" role="alert">
                                    Fitur Pencairan Saldo Utama hanya dapat digunakan setelah akun Dompet Sancaka Express Anda berstatus
                                    <span class="inline-block px-3 py-1 text-sm font-semibold text-white bg-green-600 rounded-md">Aktif</span>

                                </div>
                            @else
                                <form action="{{ route('seller.doku.cairkanSaldoUtama') }}" method="POST" class="mt-4">
                                    @csrf
                                    <fieldset>
                                        <div class="space-y-4">
                                            <div>
                                                <label for="amount_cairkan" class="block text-sm font-medium text-gray-700">Jumlah Pencairan</label>
                                                <div class="mt-1 relative rounded-md shadow-sm">
                                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                        <span class="text-gray-500 sm:text-sm">Rp</span>
                                                    </div>
                                                    <input type="number" name="amount_cairkan" id="amount_cairkan" class="focus:ring-blue-500 focus:border-blue-500 block w-full pl-10 pr-12 sm:text-sm border-gray-300 rounded-md" placeholder="10000" min="1000" required value="{{ old('amount_cairkan') }}">
                                                </div>
                                                <p class="mt-2 text-xs text-gray-500">Minimal pencairan adalah Rp 1.000.</p>
                                            </div>
                                            <div class="pt-2">
                                                <button type="submit"
                                                        class="w-full bg-blue-600 text-white font-bold py-3 px-6 rounded-lg hover:bg-blue-700 transition-colors
                                                               disabled:bg-gray-400 disabled:cursor-not-allowed">
                                                    Cairkan Saldo Utama ke Dompet
                                                </button>
                                            </div>
                                        </div>
                                    </fieldset>
                                </form>
                            @endif
                        </div>
                        {{-- ========================================================== --}}

                    </div>
                </div>

                {{-- ====================================== --}}
                {{-- == KONTEN TAB 2: KIRIM PAYOUT == --}}
                {{-- ====================================== --}}
                <div x-show="tab === 'payout'" x-transition:enter="transition-opacity duration-300" x-transition:enter-start="opacity-0" style="display: none;">
                    <h2 class="text-xl font-bold text-gray-800 mb-4">Kirim Payout (Withdrawal)</h2>

                    @if($store->doku_status !== 'ACTIVE')
                        <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-6 rounded-md" role="alert">
                            Fitur Payout hanya dapat digunakan setelah akun Dompet Sancaka Express Anda berstatus
                            <span class="inline-block px-3 py-1 text-sm font-semibold text-white bg-green-600 rounded-md">Aktif</span>

                        </div>
                    @endif

                    <form action="{{ route('seller.doku.payout') }}" method="POST">
                        @csrf
                        <fieldset {{ $store->doku_status !== 'ACTIVE' ? 'disabled' : '' }}>
                            <div class="space-y-4">
                                <div>
                                    <label for="amount_payout" class="block text-sm font-medium text-gray-700">Jumlah Payout</label>
                                    <div class="mt-1 relative rounded-md shadow-sm">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <span class="text-gray-500 sm:text-sm">Rp</span>
                                        </div>
                                        <input type="number" name="amount" id="amount_payout" class="focus:ring-blue-500 focus:border-blue-500 block w-full pl-10 pr-12 sm:text-sm border-gray-300 rounded-md" placeholder="50000" min="10000" required value="{{ old('amount') }}">
                                    </div>
                                    <p class="mt-2 text-xs text-gray-500">Minimal payout adalah Rp 10.000. Saldo Tersedia Anda: Rp {{ number_format($store->doku_balance_available, 0, ',', '.') }}</p>
                                </div>

                                <div>
                                    <label for="bank_code" class="block text-sm font-medium text-gray-700">Kode Bank (Contoh: BRINIDJA)</label>
                                    <input type="text" name="bank_code" id="bank_code" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required value="{{ old('bank_code', $user->bank_name) }}">
                                </div>
                                <div>
                                    <label for="bank_account_number" class="block text-sm font-medium text-gray-700">Nomor Rekening</label>
                                    <input type="text" name="bank_account_number" id="bank_account_number" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required value="{{ old('bank_account_number', $user->bank_account_number) }}">
                                </div>
                                <div>
                                    <label for="bank_account_name" class="block text-sm font-medium text-gray-700">Nama Pemilik Rekening</label>
                                    <input type="text" name="bank_account_name" id="bank_account_name" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required value="{{ old('bank_account_name', $user->bank_account_name) }}">
                                </div>

                                <div class="pt-4">
                                    <button type="submit"
                                            class="w-full bg-blue-600 text-white font-bold py-3 px-6 rounded-lg hover:bg-blue-700 transition-colors
                                                   disabled:bg-gray-400 disabled:cursor-not-allowed">
                                        Kirim Payout Sekarang
                                    </button>
                                </div>
                            </div>
                        </fieldset>
                    </form>
                </div>

                {{-- ====================================== --}}
                {{-- == KONTEN TAB 3: TRANSFER ANTAR AKUN == --}}
                {{-- ====================================== --}}
                <div x-show="tab === 'transfer'" x-transition:enter="transition-opacity duration-300" x-transition:enter-start="opacity-0" style="display: none;">
                    <h2 class="text-xl font-bold text-gray-800 mb-4">Transfer Antar Akun Dompet Sancaka Express</h2>

                    @if($store->doku_status !== 'ACTIVE')
                        <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-6 rounded-md" role="alert">
                            Fitur Transfer hanya dapat digunakan setelah akun Dompet Sancaka Express Anda berstatus
                            <span class="inline-block px-3 py-1 text-sm font-semibold text-white bg-green-600 rounded-md">Aktif</span>
                        </div>
                    @endif

                    <form action="{{ route('seller.doku.transfer') }}" method="POST">
                        @csrf
                        <fieldset {{ $store->doku_status !== 'ACTIVE' ? 'disabled' : '' }}>
                            <div class="space-y-4">
                                <div>
                                    <label for="destination_sac_id" class="block text-sm font-medium text-gray-700">Sub Account ID Tujuan</label>
                                    <input type="text" name="destination_sac_id" id="destination_sac_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" placeholder="SAC-xxxx-xxxxxxxxxxxxx" required value="{{ old('destination_sac_id') }}">
                                </div>
                                <div>
                                    <label for="amount_transfer" class="block text-sm font-medium text-gray-700">Jumlah Transfer</label>
                                    <div class="mt-1 relative rounded-md shadow-sm">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <span class="text-gray-500 sm:text-sm">Rp</span>
                                        </div>
                                        <input type="number" name="amount_transfer" id="amount_transfer" class="focus:ring-blue-500 focus:border-blue-500 block w-full pl-10 pr-12 sm:text-sm border-gray-300 rounded-md" placeholder="10000" min="1000" required value="{{ old('amount_transfer') }}">
                                    </div>
                                    <p class="mt-2 text-xs text-gray-500">Minimal transfer adalah Rp 1.000. Saldo Tersedia Anda: Rp {{ number_format($store->doku_balance_available, 0, ',', '.') }}</p>
                                </div>
                                <div class="pt-4">
                                    <button type="submit"
                                            class="w-full bg-blue-600 text-white font-bold py-3 px-6 rounded-lg hover:bg-blue-700 transition-colors
                                                   disabled:bg-gray-400 disabled:cursor-not-allowed">
                                        Transfer Sekarang
                                    </button>
                                </div>
                            </div>
                        </fieldset>
                    </form>
                </div>

                {{-- ====================================== --}}
                {{-- == KONTEN TAB 4: INFO TERIMA BAYARAN == --}}
                {{-- ====================================== --}}
                <div x-show="tab === 'info'" x-transition:enter="transition-opacity duration-300" x-transition:enter-start="opacity-0" style="display: none;">
                    <h2 class="text-xl font-bold text-gray-800 mb-4">Cara Menerima Pembayaran</h2>
                    <div class="prose max-w-none text-gray-700">
                        {{-- PERBAIKAN: Penjelasan yang lebih akurat --}}
                        <p>
                            Saat seorang pembeli membayar pesanan dari toko Anda (melalui <strong>CheckoutController</strong> atau controller pesanan Anda),
                            sistem akan secara otomatis menambahkan ID Sub Account Anda (Dompet Sancaka Express) ke transaksi tersebut.
                        </p>
                        <p>
                            Ini <strong>BERBEDA</strong> dengan "Top Up Saldo". Top Up Saldo (dari <strong>TopUpController</strong>) akan masuk ke Saldo Utama Sancaka Express (Akun Pusat),
                            bukan ke Dompet Sancaka Express Anda.
                        </p>
                        <p>Secara teknis, saat pembayaran pesanan toko Anda, kami menambahkan JSON berikut ke API DOKU:</p>
                        <pre class="bg-gray-800 text-white rounded-md p-4 overflow-x-auto"><code>
                            {
                            "additional_info": {
                                "account": {
                                "id": "{{ $store->doku_sac_id }}"
                                }
                            }
                            }
                        </code></pre>
                        <p>Anda tidak perlu melakukan apa-apa, semua pembayaran yang berhasil akan otomatis masuk ke <strong>Saldo Tertunda</strong> Anda. Saldo akan pindah ke <strong>Saldo Tersedia</strong> setelah proses rekonsiliasi DOKU (biasanya H+1).</p>
                    </div>
                </div>

            </div>
        </div>

    @else

        {{-- ========================================================== --}}
        {{-- == 4. TAMPILAN JIKA BELUM TERDAFTAR == --}}
        {{-- ========================================================== --}}
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-bold text-gray-800 border-b pb-4 mb-4">Daftarkan Toko ke Dompet Sancaka Express</h2> {{-- Sesuai branding Anda --}}
            <p class="text-gray-600 mb-6">
                Daftarkan toko Anda ke sistem pembayaran Sancaka Express untuk mulai menerima pembayaran dari pelanggan
                dan mencairkan dana (payout). Kami akan mendaftarkan data toko Anda secara otomatis.
            </p>

            <div class="space-y-4 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Nama Toko</label>
                    <input type="text" value="{{ $store->name }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm bg-gray-100" disabled>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Email Toko</label>
                    <input type="email" value="{{ $user->email }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm bg-gray-100" disabled>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">No. HP (WhatsApp)</label>
                    <input type="text" value="{{ $user->no_wa ?? 'BELUM DIISI' }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm {{ empty($user->no_wa) ? 'bg-red-100 border-red-400' : 'bg-gray-100' }}" disabled>
                    @if(empty($user->no_wa))
                        <p class="mt-2 text-sm text-red-600">Anda harus mengisi No. HP di profil Anda sebelum bisa mendaftar Dompet Sancaka Express.</p>
                    @endif
                </div>
            </div>

            @if(!empty($user->no_wa))
                <form action="{{ route('seller.doku.register') }}" method="POST">
                    @csrf
                    <div class="mt-6">
                        <button type="submit"
                                class="w-full bg-red-600 text-white font-bold py-3 px-6 rounded-lg hover:bg-red-700 transition-colors">
                            Daftarkan Toko Saya ke Dompet Sancaka Express
                        </button>
                    </div>
                </form>
            @else
                <div class="mt-6">
                    <button type="button"
                            class="w-full bg-gray-400 text-white font-bold py-3 px-6 rounded-lg cursor-not-allowed">
                        Daftarkan Toko Saya ke Dompet Sancaka Express
                    </button>
                    <p class="mt-2 text-center text-sm text-gray-500">Harap lengkapi profil Anda (No. HP) terlebih dahulu.</p>
                </div>
            @endif
        </div>

    @endif
</div>

@push('scripts')
<script>
    document.addEventListener("DOMContentLoaded", function() {

        // 1. CEK DATA SAC ID (PHP ke JS)
        // Jika tidak ada SAC ID, hentikan semua proses otomatis.
        var hasSacId = "{{ $store->doku_sac_id ?? '' }}";
        if (!hasSacId) {
            console.log('Auto-Refresh: SAC ID tidak ditemukan. Membatalkan proses.');
            return;
        }

        // --- LOGIKA 1: AUTO REFRESH STATUS (ANTI LOOP) ---
        @if($store->doku_status !== 'ACTIVE')

            // Cek apakah kita sudah pernah mencoba refresh status di sesi browser ini?
            // Jika SUDAH pernah (ada di storage), jangan klik lagi (Stop Loop).
            if (!sessionStorage.getItem('doku_status_auto_checked')) {

                var btnStatus = document.getElementById('btn-auto-status');
                if (btnStatus) {
                    console.log('Auto-Refresh: Status belum Active, mencoba cek ke API (Percobaan 1)...');

                    // PENTING: Tandai bahwa kita sudah mencoba cek status
                    sessionStorage.setItem('doku_status_auto_checked', 'true');

                    // Klik tombol
                    setTimeout(function() {
                        btnStatus.click();
                        // Ubah tampilan tombol biar user tau lagi loading
                        btnStatus.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
                        btnStatus.disabled = true;
                    }, 1000);
                }
            } else {
                console.log('Auto-Refresh: Status masih Pending, tapi sistem sudah mencoba cek otomatis sebelumnya. Stop Loop untuk keamanan.');
            }

        @else

        // --- LOGIKA 2: AUTO REFRESH SALDO (ANTI LOOP) ---
        // Hanya jalan jika Status SUDAH ACTIVE.

            var sessionKey = 'doku_saldo_auto_refreshed';

            // Cek apakah sudah pernah auto-refresh saldo?
            if (!sessionStorage.getItem(sessionKey)) {

                var btnSaldo = document.getElementById('btn-auto-saldo');
                // Cek apakah tombol ada (berarti user punya akses saldo)
                if (btnSaldo) {
                    console.log('Auto-Refresh: Status Active, menyinkronkan saldo satu kali...');

                    // Tandai sudah refresh agar tidak reload terus menerus
                    sessionStorage.setItem(sessionKey, 'true');

                    setTimeout(function(){
                        btnSaldo.click();
                    }, 500);
                }
            } else {
                console.log('Auto-Refresh: Saldo sudah disinkronkan di sesi ini. Tidak perlu refresh lagi.');
            }
        @endif
    });
</script>
@endpush

@endsection
