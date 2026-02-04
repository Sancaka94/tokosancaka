@extends('layouts.admin')

@section('title', 'Produk Digital & PPOB')

@push('styles')
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .ppob-menu-item { transition: all 0.3s ease; }
        .ppob-menu-item:hover .icon-box { transform: translateY(-5px); box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); }
        .bg-pattern { background-image: radial-gradient(#ffffff 1px, transparent 1px); background-size: 20px 20px; opacity: 0.1; }
    </style>
@endpush

@section('content')
<div class="bg-gray-50 min-h-screen pb-12">

    {{-- ================================================================= --}}
    {{-- 1. WIDGET SALDO DIGIFLAZZ (HANYA UNTUK ADMIN) --}}
    {{-- ================================================================= --}}
    @if(auth()->check() && auth()->user()->role === 'Admin')
    <div class="bg-blue-900 pt-6 pb-12">
        <div class="container mx-auto px-4">
            <div class="bg-gradient-to-r from-blue-600 to-indigo-700 rounded-xl shadow-lg p-6 text-white relative overflow-hidden border border-white/10">
                
                {{-- Dekorasi Background --}}
                <div class="absolute right-0 top-0 opacity-10 transform translate-x-4 -translate-y-4">
                    <i class="fas fa-wallet text-9xl"></i>
                </div>

                <div class="relative z-10 flex flex-col md:flex-row justify-between items-center gap-4">
                    {{-- Info Saldo --}}
                    <div>
                        <p class="text-blue-100 text-sm font-medium mb-1 flex items-center gap-2">
                            <i class="fas fa-coins"></i> Saldo Modal (Digiflazz)
                        </p>
                        <h2 class="text-3xl font-bold tracking-tight" id="digi-saldo">
                            Rp ...
                        </h2>
                        <p class="text-[10px] text-blue-200 mt-1 opacity-80">Digunakan untuk memproses transaksi user.</p>
                    </div>

                    {{-- Tombol Refresh --}}
                    <button onclick="refreshSaldo()" id="btn-refresh-saldo" 
                            class="group bg-white/20 hover:bg-white/30 text-white px-5 py-2.5 rounded-lg backdrop-blur-sm transition-all flex items-center gap-2 text-sm font-semibold border border-white/10 shadow-sm">
                        <i class="fas fa-sync-alt transition-transform group-hover:rotate-180" id="icon-refresh"></i> 
                        Cek Saldo
                    </button>
                </div>
            </div>
        </div>
    </div>
    @else
    {{-- Banner untuk User Biasa --}}
    <div class="bg-blue-600 text-white pt-8 pb-16 relative overflow-hidden">
        <div class="absolute inset-0 bg-pattern"></div>
        <div class="container mx-auto px-4 relative z-10 text-center">
            <h1 class="text-2xl md:text-3xl font-bold mb-2">Produk Digital Terlengkap</h1>
            <p class="text-blue-100 text-sm">Isi pulsa, bayar listrik, dan top up game dalam satu genggaman.</p>
        </div>
    </div>
    @endif

    {{-- ================================================================= --}}
    {{-- 2. MENU UTAMA (GRID KATEGORI) --}}
    {{-- ================================================================= --}}
    <div class="container mx-auto px-4 -mt-10 relative z-20">
        <div class="bg-white rounded-xl shadow-lg p-6 md:p-8 border border-gray-100">
            <div class="flex items-center justify-between mb-6 border-b border-gray-100 pb-4">
                <h2 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                    <span class="bg-blue-100 text-blue-600 p-1.5 rounded-lg">
                        <i class="fas fa-th-large"></i>
                    </span>
                    Pilih Layanan
                </h2>
                <span class="text-xs text-gray-400">Layanan Aktif 24 Jam</span>
            </div>
            
            <div class="grid grid-cols-4 md:grid-cols-6 lg:grid-cols-8 gap-y-8 gap-x-4">
                
                {{-- Pulsa --}}
                <a href="{{ route('admin.ppob.category', 'pulsa') }}" class="ppob-menu-item flex flex-col items-center group">
                    <div class="icon-box w-12 h-12 md:w-14 md:h-14 rounded-2xl bg-red-50 text-red-500 flex items-center justify-center mb-2 group-hover:bg-red-500 group-hover:text-white transition-colors border border-red-100">
                        <i class="fas fa-mobile-alt text-xl md:text-2xl"></i>
                    </div>
                    <span class="text-xs md:text-sm text-gray-600 text-center font-medium group-hover:text-red-600">Pulsa</span>
                </a>

                {{-- Paket Data --}}
                <a href="{{ route('admin.ppob.category', 'data') }}" class="ppob-menu-item flex flex-col items-center group">
                    <div class="icon-box w-12 h-12 md:w-14 md:h-14 rounded-2xl bg-blue-50 text-blue-500 flex items-center justify-center mb-2 group-hover:bg-blue-500 group-hover:text-white transition-colors border border-blue-100">
                        <i class="fas fa-wifi text-xl md:text-2xl"></i>
                    </div>
                    <span class="text-xs md:text-sm text-gray-600 text-center font-medium group-hover:text-blue-600">Paket Data</span>
                </a>

                {{-- Token PLN --}}
                <a href="{{ route('admin.ppob.category', 'pln-token') }}" class="ppob-menu-item flex flex-col items-center group">
                    <div class="icon-box w-12 h-12 md:w-14 md:h-14 rounded-2xl bg-yellow-50 text-yellow-500 flex items-center justify-center mb-2 group-hover:bg-yellow-500 group-hover:text-white transition-colors border border-yellow-100">
                        <i class="fas fa-bolt text-xl md:text-2xl"></i>
                    </div>
                    <span class="text-xs md:text-sm text-gray-600 text-center font-medium group-hover:text-yellow-600">Token PLN</span>
                </a>

                {{-- Tagihan Listrik (Pascabayar) --}}
                <a href="{{ route('admin.ppob.category', 'pln-bill') }}" class="ppob-menu-item flex flex-col items-center group">
                    <div class="icon-box w-12 h-12 md:w-14 md:h-14 rounded-2xl bg-orange-50 text-orange-500 flex items-center justify-center mb-2 group-hover:bg-orange-500 group-hover:text-white transition-colors border border-orange-100">
                        <i class="fas fa-file-invoice-dollar text-xl md:text-2xl"></i>
                    </div>
                    <span class="text-xs md:text-sm text-gray-600 text-center font-medium group-hover:text-orange-600">Tagihan PLN</span>
                </a>

                {{-- E-Wallet --}}
                <a href="{{ route('admin.ppob.category', 'e-money') }}" class="ppob-menu-item flex flex-col items-center group">
                    <div class="icon-box w-12 h-12 md:w-14 md:h-14 rounded-2xl bg-purple-50 text-purple-500 flex items-center justify-center mb-2 group-hover:bg-purple-500 group-hover:text-white transition-colors border border-purple-100">
                        <i class="fas fa-wallet text-xl md:text-2xl"></i>
                    </div>
                    <span class="text-xs md:text-sm text-gray-600 text-center font-medium group-hover:text-purple-600">E-Wallet</span>
                </a>

                {{-- Voucher Game --}}
                <a href="{{ route('admin.ppob.category', 'voucher-game') }}" class="ppob-menu-item flex flex-col items-center group">
                    <div class="icon-box w-12 h-12 md:w-14 md:h-14 rounded-2xl bg-indigo-50 text-indigo-500 flex items-center justify-center mb-2 group-hover:bg-indigo-500 group-hover:text-white transition-colors border border-indigo-100">
                        <i class="fas fa-gamepad text-xl md:text-2xl"></i>
                    </div>
                    <span class="text-xs md:text-sm text-gray-600 text-center font-medium group-hover:text-indigo-600">Voucher Game</span>
                </a>

                {{-- Streaming (Placeholder) --}}
                <a href="#" class="ppob-menu-item flex flex-col items-center group opacity-50 cursor-not-allowed" title="Segera Hadir">
                    <div class="icon-box w-12 h-12 md:w-14 md:h-14 rounded-2xl bg-pink-50 text-pink-500 flex items-center justify-center mb-2 border border-pink-100">
                        <i class="fas fa-tv text-xl md:text-2xl"></i>
                    </div>
                    <span class="text-xs md:text-sm text-gray-400 text-center font-medium">TV Kabel</span>
                </a>

                {{-- PDAM (Placeholder) --}}
                <a href="#" class="ppob-menu-item flex flex-col items-center group opacity-50 cursor-not-allowed">
                    <div class="icon-box w-12 h-12 md:w-14 md:h-14 rounded-2xl bg-cyan-50 text-cyan-500 flex items-center justify-center mb-2 border border-cyan-100">
                        <i class="fas fa-water text-xl md:text-2xl"></i>
                    </div>
                    <span class="text-xs md:text-sm text-gray-400 text-center font-medium">PDAM</span>
                </a>

                {{-- Lainnya (Placeholder) --}}
                <a href="#" class="ppob-menu-item flex flex-col items-center group">
                    <div class="icon-box w-12 h-12 md:w-14 md:h-14 rounded-2xl bg-gray-100 text-gray-500 flex items-center justify-center mb-2 group-hover:bg-gray-600 group-hover:text-white transition-colors border border-gray-200">
                        <i class="fas fa-ellipsis-h text-xl md:text-2xl"></i>
                    </div>
                    <span class="text-xs md:text-sm text-gray-600 text-center font-medium group-hover:text-gray-800">Lainnya</span>
                </a>

            </div>
        </div>

        {{-- ================================================================= --}}
        {{-- 3. SECTION RIWAYAT TRANSAKSI (STATIC UI) --}}
        {{-- ================================================================= --}}
        <div class="mt-8 mb-8">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-gray-800">Riwayat Transaksi Terakhir</h3>
                <a href="#" class="text-sm text-blue-600 font-medium hover:underline">Lihat Semua</a>
            </div>
            
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 divide-y divide-gray-100 overflow-hidden">
                <div class="p-8 text-center text-gray-400">
                    <div class="bg-gray-50 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-3">
                        <i class="fas fa-receipt text-2xl text-gray-300"></i>
                    </div>
                    <p class="text-sm">Belum ada riwayat transaksi produk digital.</p>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script>
    // --- SKRIP CEK SALDO DIGIFLAZZ (KHUSUS ADMIN) ---
    function refreshSaldo() {
        const saldoEl = document.getElementById('digi-saldo');
        const icon = document.getElementById('icon-refresh');
        const btn = document.getElementById('btn-refresh-saldo');
        
        if (!saldoEl) return;

        saldoEl.innerText = 'Memuat...';
        icon.classList.add('fa-spin');
        btn.disabled = true;
        btn.classList.add('opacity-75', 'cursor-not-allowed');

        // Panggil Route Controller (PASTIKAN ROUTE INI ADA DI web.php PREFIX ADMIN)
        fetch("{{ route('ppob.cek-saldo') }}", {
            headers: {
                "X-Requested-With": "XMLHttpRequest",
                "Accept": "application/json"
            }
        })
        .then(response => response.json())
        .then(data => {
            icon.classList.remove('fa-spin');
            btn.disabled = false;
            btn.classList.remove('opacity-75', 'cursor-not-allowed');

            if(data.status) {
                saldoEl.innerText = data.formatted;
            } else {
                saldoEl.innerText = 'Error';
                console.error(data.message);
            }
        })
        .catch(error => {
            icon.classList.remove('fa-spin');
            btn.disabled = false;
            saldoEl.innerText = 'Gagal';
            console.error('Error:', error);
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        if(document.getElementById('digi-saldo')) {
            refreshSaldo();
        }
    });
</script>
@endpush