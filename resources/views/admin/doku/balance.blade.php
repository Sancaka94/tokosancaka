@extends('layouts.admin')
@section('title', 'Dompet Utama Admin (Doku)')

@section('content')
<div class="container mx-auto px-4 py-8 space-y-8">

    <h1 class="text-2xl font-bold mb-6">Dompet Utama Admin (Akun Penampung)</h1>
    <p class="text-gray-600 -mt-6 mb-6">Halaman ini menampilkan saldo di Akun Doku Utama Anda yang digunakan sebagai modal untuk membayar (payout) ke toko/seller.</p>

    <!-- Notifikasi Error -->
    @if ($error)
        <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
            <strong class="font-bold">Error!</strong>
            <p class="block sm:inline">{{ $error }}</p>
            @if(empty($mainSacId))
                <p class="mt-2 text-sm">Harap set <strong>DOKU_MAIN_SAC_ID</strong> di file .env Anda, lalu jalankan <strong>php artisan config:clear</strong>.</p>
            @endif
        </div>
    @endif

    @if(isset($autoSwitched) && $autoSwitched)
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle"></i>
        <strong>Perhatian:</strong> Database Anda diset ke <b>Production</b>, tetapi ID Akun tidak ditemukan.
        Sistem secara cerdas beralih ke <b>Sandbox</b> untuk menampilkan data ini.
    </div>
    @endif

    <!-- Tampilkan Saldo -->
    @if ($balanceData)
        <div class="p-6 bg-white shadow-md rounded-lg">
            <h2 class="text-lg font-semibold text-gray-800">Saldo Akun: <span class="font-mono text-blue-600">{{ $mainSacId }}</span></h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                <!-- Saldo Tersedia -->
                <div class="p-6 bg-green-50 border border-green-200 rounded-xl">
                    <p class="text-sm font-medium text-green-700">Saldo Tersedia (Available)</p>
                    <p class="mt-2 text-4xl font-bold text-green-800">
                        Rp {{ number_format($balanceData['balance']['available'] ?? 0, 0, ',', '.') }}
                    </p>
                    <p class="mt-1 text-xs text-gray-500">Saldo ini yang digunakan untuk Payout ke seller.</p>
                </div>

                <!-- Saldo Tertunda -->
                <div class="p-6 bg-yellow-50 border border-yellow-200 rounded-xl">
                    <p class="text-sm font-medium text-yellow-700">Saldo Tertunda (Pending)</p>
                    <p class="mt-2 text-4xl font-bold text-yellow-800">
                        Rp {{ number_format($balanceData['balance']['pending'] ?? 0, 0, ',', '.') }}
                    </p>
                    <p class="mt-1 text-xs text-gray-500">Saldo dari pembayaran online yang belum di-settle oleh Doku.</p>
                </div>
            </div>
        </div>
    @elseif (!$error)
        <div class="p-6 bg-white shadow-md rounded-lg text-center">
            <p class="text-gray-500">Memuat data saldo...</p>
        </div>
    @endif

    <!-- Instruksi Top Up -->
    <div class="p-6 bg-white shadow-md rounded-lg">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">Cara Mengisi Saldo (Top Up) Akun Utama</h2>
        <ol class="list-decimal list-inside space-y-2 text-gray-700">
            <li>Buka dan <strong>Login</strong> ke Dashboard Doku Merchant Anda.</li>
            <li>Cari Akun Utama Anda (<strong>{{ $mainSacId ?? 'SAC-ANDA' }}</strong>).</li>
            <li>Cari menu <strong>"Top Up"</strong>, <strong>"Isi Saldo"</strong>, atau "Settlement".</li>
            <li>Ikuti instruksi untuk melakukan transfer (biasanya ke Virtual Account) dari Rekening Bank Admin Sancaka Anda.</li>
            <li>Setelah berhasil, saldo "Available" di atas akan bertambah.</li>
            <li>Setelah saldo tersedia, Anda baru bisa melakukan "Cairkan Dana" di halaman <a href="{{ route('admin.stores.index') }}" class="text-blue-600 hover:underline">Pencairan Dana Toko</a>.</li>
        </ol>
    </div>

</div>
@endsection
