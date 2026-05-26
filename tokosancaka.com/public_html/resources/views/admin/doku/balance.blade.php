@extends('layouts.admin')
@section('title', 'Dompet Utama Admin (Doku)')

@section('content')
<div class="container mx-auto px-4 py-8 space-y-8">

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Dompet Utama Sancaka</h1>
            <p class="text-gray-600 mt-1">Kelola saldo akun penampung utama DOKU dan distribusi dana ke toko pelanggan.</p>
        </div>
        
        {{-- Indikator Mode (Sandbox / Production) --}}
        @if(isset($mode))
            <span class="self-start sm:self-center px-4 py-1 text-sm font-bold uppercase rounded-full shadow-sm 
                {{ $mode === 'production' ? 'bg-green-100 text-green-700' : 'bg-gray-200 text-gray-700' }}">
                Mode: {{ $mode }}
            </span>
        @endif
    </div>

    @if ($error)
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-4 rounded-lg shadow-sm relative" role="alert">
            <strong class="font-bold">Error Sinkronisasi API!</strong>
            <p class="block sm:inline mt-1">{{ $error }}</p>
            @if(empty($mainSacId))
                <p class="mt-3 text-sm bg-red-50 p-2 rounded">
                    <i class="fas fa-info-circle mr-1"></i> Harap pastikan variabel <strong>DOKU_MAIN_SAC_ID</strong> sudah diatur di database konfigurasi Anda.
                </p>
            @endif
        </div>
    @endif

    @if(isset($autoSwitched) && $autoSwitched)
        <div class="bg-yellow-50 border-l-4 border-yellow-500 text-yellow-800 p-4 rounded-r-lg shadow-sm" role="alert">
            <div class="flex items-start">
                <i class="fas fa-exclamation-triangle mt-1 mr-3 text-yellow-600"></i>
                <div>
                    <strong class="font-bold">Perhatian Sistem:</strong>
                    <p class="text-sm mt-1">Database Anda diset ke <b>Production</b>, tetapi ID Akun tidak ditemukan. Sistem telah secara otomatis beralih sementara ke <b>Sandbox</b> untuk menampilkan data ini.</p>
                </div>
            </div>
        </div>
    @endif

    {{-- ========================================================== --}}
    {{-- == MENU AKSI CEPAT ADMIN (TRANSFER SALDO, DLL) == --}}
    {{-- ========================================================== --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <a href="{{ route('admin.doku.transfer') }}" class="flex items-center p-4 bg-blue-600 hover:bg-blue-700 text-white rounded-xl shadow-sm transition-all group">
            <div class="p-3 bg-blue-500 rounded-lg mr-4">
                <i class="fas fa-exchange-alt text-xl"></i>
            </div>
            <div>
                <span class="block font-bold">Transfer / Cairkan Dana</span>
                <span class="text-xs text-blue-200">Kirim saldo ke Sub Account Toko</span>
            </div>
            <i class="fas fa-chevron-right ml-auto text-blue-300 group-hover:translate-x-1 transition-transform"></i>
        </a>

        <a href="{{ route('admin.saldo.requests.index') }}" class="flex items-center p-4 bg-white hover:bg-gray-50 text-gray-800 rounded-xl shadow-sm border border-gray-200 transition-all group">
            <div class="p-3 bg-gray-100 text-gray-600 rounded-lg mr-4">
                <i class="fas fa-file-invoice-dollar text-xl"></i>
            </div>
            <div>
                <span class="block font-bold text-gray-900">Permintaan Saldo</span>
                <span class="text-xs text-gray-500">Cek antrean request top up / WD</span>
            </div>
            <i class="fas fa-chevron-right ml-auto text-gray-400 group-hover:translate-x-1 transition-transform"></i>
        </a>

        <div class="flex items-center p-4 bg-white text-gray-800 rounded-xl shadow-sm border border-gray-200">
            <div class="p-3 bg-gray-100 text-gray-600 rounded-lg mr-4">
                <i class="fas fa-wallet text-xl"></i>
            </div>
            <div>
                <span class="block text-xs text-gray-500">ID Akun Aktif</span>
                <span class="font-mono font-bold text-gray-900 break-all">{{ $mainSacId ?? 'Belum Diatur' }}</span>
            </div>
        </div>
    </div>

    @if ($balanceData)
        <div class="p-6 bg-white shadow-md rounded-xl border border-gray-100">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Informasi Saldo Terkini</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="p-6 bg-green-50 border border-green-200 rounded-xl shadow-sm">
                    <p class="text-sm font-medium text-green-700">Saldo Tersedia (Available Balance)</p>
                    <p class="mt-2 text-4xl font-bold text-green-800">
                        Rp {{ number_format($balanceData['balance']['available'] ?? 0, 0, ',', '.') }}
                    </p>
                    <p class="mt-2 text-xs text-gray-500">Saldo penampung yang dapat Anda distribusikan ke toko pembeli.</p>
                </div>

                <div class="p-6 bg-yellow-50 border border-yellow-200 rounded-xl shadow-sm">
                    <p class="text-sm font-medium text-yellow-700">Saldo Tertunda (Pending Balance)</p>
                    <p class="mt-2 text-4xl font-bold text-yellow-800">
                        Rp {{ number_format($balanceData['balance']['pending'] ?? 0, 0, ',', '.') }}
                    </p>
                    <p class="mt-2 text-xs text-gray-500">Dana pembayaran konsumen yang masih ditahan/proses kliring DOKU.</p>
                </div>
            </div>
        </div>
    @elseif (!$error)
        <div class="p-8 bg-white shadow-md rounded-xl text-center border border-gray-100">
            <i class="fas fa-spinner fa-spin text-3xl text-blue-500 mb-3"></i>
            <p class="text-gray-500 font-medium">Menyinkronkan data saldo dengan server DOKU...</p>
        </div>
    @endif

    <div class="p-6 bg-white shadow-md rounded-xl border border-gray-100">
        <h2 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-3">Panduan Pengisian & Distribusi Dana</h2>
        <ol class="list-decimal list-inside space-y-3 text-gray-700 text-sm">
            <li>Lakukan **Top Up** ke Akun Penampung Utama melalui portal merchant DOKU jika saldo *Available* menipis.</li>
            <li>Gunakan tombol <strong class="text-blue-600">"Transfer / Cairkan Dana"</strong> di atas untuk memindahkan sebagian saldo utama ini ke masing-masing dompet sub account milik seller.</li>
            <li>Setiap transaksi transfer sukses antar internal SAC DOKU akan langsung memotong saldo *Available* admin dan menambah saldo *Available* toko pembeli tanpa delay.</li>
        </ol>
    </div>

</div>
@endsection