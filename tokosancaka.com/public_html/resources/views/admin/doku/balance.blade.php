@extends('layouts.admin')
@section('title', 'Dompet Utama Admin (Doku)')

@section('content')
<div class="container mx-auto px-4 py-8 space-y-8">

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Dompet Utama Sancaka</h1>
            <p class="text-gray-600 mt-1">Kelola saldo akun penampung utama DOKU dan distribusi dana escrow ke seller/agent.</p>
        </div>
        
        {{-- Indikator Mode --}}
        @if(isset($mode))
            <span class="self-start sm:self-center px-4 py-1 text-sm font-bold uppercase rounded-full shadow-sm 
                {{ $mode === 'production' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                Mode: {{ $mode }}
            </span>
        @endif
    </div>

    {{-- ALERT PESAN SUKSES & ERROR DARI CONTROLLER (SESSION) --}}
    @if (session('success'))
        <div class="bg-green-50 border-l-4 border-green-500 text-green-800 p-4 rounded-r-lg shadow-sm relative flex items-start" role="alert">
            <i class="fas fa-check-circle mt-1 mr-3 text-green-600"></i>
            <div>
                <strong class="font-bold">Berhasil!</strong>
                <p class="block sm:inline mt-1">{{ session('success') }}</p>
            </div>
        </div>
    @endif

    @if (session('error'))
        <div class="bg-red-50 border-l-4 border-red-500 text-red-800 p-4 rounded-r-lg shadow-sm relative flex items-start" role="alert">
            <i class="fas fa-times-circle mt-1 mr-3 text-red-600"></i>
            <div>
                <strong class="font-bold">Gagal Diproses!</strong>
                <p class="block sm:inline mt-1">{{ session('error') }}</p>
            </div>
        </div>
    @endif

    {{-- ALERT ERROR API DOKU (DARI VARIABEL VIEW) --}}
    @if ($error)
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-4 rounded-lg shadow-sm relative" role="alert">
            <strong class="font-bold">Error Sinkronisasi API!</strong>
            <p class="block sm:inline mt-1">{{ $error }}</p>
            @if(empty($mainSacId))
                <p class="mt-3 text-sm bg-red-50 p-2 rounded">
                    <i class="fas fa-info-circle mr-1"></i> Harap pastikan variabel <strong>DOKU_MAIN_SAC_ID</strong> sudah diatur di database konfigurasi (Tabel `apis`).
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
                    <p class="text-sm mt-1">Database Anda diset ke <b>Production</b>, tetapi ID Akun tidak ditemukan di server Production. Sistem telah secara otomatis beralih sementara ke <b>Sandbox</b> untuk menampilkan data ini.</p>
                </div>
            </div>
        </div>
    @endif

    {{-- MENU AKSI CEPAT --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <a href="{{ route('admin.doku.transfer') }}" class="flex items-center p-4 bg-blue-600 hover:bg-blue-700 text-white rounded-xl shadow-sm transition-all group">
            <div class="p-3 bg-blue-500 rounded-lg mr-4">
                <i class="fas fa-exchange-alt text-xl"></i>
            </div>
            <div>
                <span class="block font-bold">Riwayat Transfer</span>
                <span class="text-xs text-blue-200">Lihat log pencairan ke Sub Account</span>
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
                <span class="block text-xs text-gray-500">ID Akun Aktif (Penampung)</span>
                <span class="font-mono font-bold text-gray-900 break-all">{{ $mainSacId ?? 'Belum Diatur' }}</span>
            </div>
        </div>
    </div>

    @if ($balanceData)
        <div class="p-6 bg-white shadow-md rounded-xl border border-gray-100 relative">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-lg font-semibold text-gray-800">Informasi Saldo Terkini</h2>
                
                {{-- TOMBOL REFRESH --}}
                <a href="{{ route('admin.doku.balance') }}" 
                   onclick="this.innerHTML='<i class=\'fas fa-sync-alt fa-spin mr-1\'></i> Menyinkronkan...'; this.classList.add('opacity-50', 'cursor-not-allowed')"
                   class="inline-flex items-center px-4 py-2 text-sm font-medium text-blue-600 bg-blue-50 hover:bg-blue-100 rounded-lg transition-colors border border-blue-200">
                    <i class="fas fa-sync-alt mr-2"></i> Refresh Saldo
                </a>
            </div>

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
            
            <p class="mt-4 text-xs text-right text-gray-400">
                Terakhir disinkronkan: {{ now('Asia/Jakarta')->format('d M Y, H:i:s') }} WIB
            </p>
        </div>

        {{-- FORM PENCAIRAN ESCROW KE SELLER / AGENT --}}
        <div class="p-6 bg-white shadow-md rounded-xl border border-gray-100 mt-6">
            <h2 class="text-lg font-bold text-gray-800 mb-1">Transfer Dana Escrow (Pencairan)</h2>
            <p class="text-sm text-gray-500 mb-6">Pindahkan dana dari Dompet Utama Admin ke Sub Account milik Seller atau Agent.</p>
            
            {{-- Pastikan action diarahkan ke controller processTransfer --}}
            <form action="{{ route('admin.doku.transfer.process') }}" method="POST" class="space-y-4">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    
                    {{-- Pilihan Penerima --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Pilih Penerima (Seller / Agent) <span class="text-red-500">*</span></label>
                        <select name="store_id" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" required>
                            <option value="">-- Cari & Pilih Penerima --</option>
                            {{-- Optimalnya passing variabel $stores dari Controller. Jika terpaksa query di view, urutkan agar mudah dicari --}}
                            @foreach(\App\Models\Store::whereNotNull('doku_sac_id')->orderBy('name')->get() as $s)
                                <option value="{{ $s->id }}">{{ $s->name }} (SAC: {{ $s->doku_sac_id }})</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Nominal --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nominal Pencairan (Rp) <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <span class="text-gray-500 sm:text-sm">Rp</span>
                            </div>
                            <input type="number" name="amount" min="1000" placeholder="Contoh: 150000" class="pl-10 w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" required>
                        </div>
                    </div>

                    {{-- Deskripsi/Catatan (Sesuai validasi di Controller Anda) --}}
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Catatan Pencairan Escrow (Opsional)</label>
                        <input type="text" name="description" placeholder="Contoh: Pencairan dana escrow untuk Order INV-20231015-001" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" maxlength="255">
                        <p class="text-xs text-gray-400 mt-1">Catatan ini akan muncul di laporan mutasi DOKU dan riwayat transaksi Seller.</p>
                    </div>
                </div>

                <div class="flex justify-end mt-4 pt-4 border-t border-gray-100">
                    <button type="submit" onclick="return confirm('Apakah Anda yakin ingin mencairkan dana ini? Pastikan nominal dan penerima sudah benar.')" class="bg-blue-600 text-white font-bold px-6 py-2 rounded-md hover:bg-blue-700 transition-colors flex items-center">
                        <i class="fas fa-paper-plane mr-2"></i> Eksekusi Transfer
                    </button>
                </div>
            </form>
        </div>
    @elseif (!$error)
        <div class="p-8 bg-white shadow-md rounded-xl text-center border border-gray-100">
            <i class="fas fa-spinner fa-spin text-3xl text-blue-500 mb-3"></i>
            <p class="text-gray-500 font-medium">Menyinkronkan data saldo dengan server DOKU...</p>
        </div>
    @endif

</div>
@endsection