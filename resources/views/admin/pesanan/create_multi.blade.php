{{-- 
    File: resources/views/admin/pesanan/create_multi.blade.php
    Deskripsi: Halaman Multi-Koli Admin dengan Sortir Ongkir, Pencarian Global, dan UI Feedback
--}}

@extends('layouts.admin')

@section('title', 'Buat Pesanan Kirim Paket Massal')

{{-- 🔥 TAMBAHAN KODE PENGAMAN (IDEMPOTENCY) 🔥 --}}
@php
    // Membuat kunci unik (UUID) untuk mencegah dobel input saat admin submit form
    if (!isset($idempotencyKey)) {
        $idempotencyKey = (string) \Illuminate\Support\Str::uuid();
    }
@endphp

@push('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
    /* Styles for Steps and Search */
    .step-container { display: flex; justify-content: space-between; align-items: center; position: relative; margin-bottom: 2.5rem; max-width: 600px; margin-left: auto; margin-right: auto; padding: 0 15px; }
    .step-progress-bg { position: absolute; top: 50%; left: 0; width: 100%; height: 3px; background-color: #e2e8f0; transform: translateY(-50%); z-index: 0; }
    .step-progress-active { position: absolute; top: 50%; left: 0; height: 3px; background-color: #ef4444; transform: translateY(-50%); z-index: 0; transition: width 0.4s ease; }
    .step-item { position: relative; z-index: 10; background-color: #f1f5f9; padding: 0 5px; display: flex; flex-direction: column; align-items: center; cursor: default; }
    .step-circle { width: 35px; height: 35px; border-radius: 50%; background-color: #fff; border: 3px solid #cbd5e1; color: #64748b; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 14px; transition: all 0.3s ease; }
    .step-item.active .step-circle { border-color: #ef4444; background-color: #ef4444; color: #fff; box-shadow: 0 0 0 4px #dbeafe; transform: scale(1.1); }
    .step-item.active .step-label { color: #ef4444; }
    .step-item.completed .step-circle { border-color: #10b981; background-color: #10b981; color: #fff; }
    .step-item.completed .step-label { color: #10b981; }
    
    /* Search Results */
    .search-group { position: relative; }
    .search-results { position: absolute; top: 100%; left: 0; right: 0; z-index: 9999 !important; background: white; border: 1px solid #e2e8f0; border-radius: 0.5rem; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.15); max-height: 250px; overflow-y: auto; margin-top: 4px; }
    .result-item { padding: 10px 15px; border-bottom: 1px solid #f1f5f9; cursor: pointer; font-size: 13px; color: #334155; transition: background 0.2s; }
    .result-item:hover { background-color: #eff6ff; color: #ef4444; }
    
    /* Animations & Utilities */
    .step-content { display: none; animation: slideUp 0.4s ease-out; }
    .step-content.active { display: block; }
    @keyframes slideUp { from { opacity: 0; transform: translateY(15px); } to { opacity: 1; transform: translateY(0); } }
    .required-label::after { content: " *"; color: #ef4444; }
    .custom-scrollbar::-webkit-scrollbar { width: 6px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: #f1f1f1; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
    button:disabled { cursor: not-allowed; opacity: 0.6; filter: grayscale(100%); }
</style>
@endpush

@section('content')
{{-- Gunakan partials notifikasi yang sesuai dengan admin jika ada, atau gunakan default --}}
@include('layouts.partials.notifications')

<div class="max-w-5xl mx-auto pb-24 pt-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row justify-between items-center mb-10 gap-4 px-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-gray-900">Buat Pesanan Kirim Paket Massal</h1>
            <p class="text-sm text-gray-500 mt-1">Satu pesanan dengan banyak paket (beda berat/dimensi).</p>
        </div>
        {{-- Ubah Route ke Admin --}}
        <a href="{{ route('admin.pesanan.create') }}" class="text-sm font-semibold text-blue-700 bg-blue-50 border border-blue-200 px-5 py-2.5 rounded-xl hover:bg-blue-100 transition">
            <i class="fas fa-exchange-alt mr-2"></i> Mode Satuan
        </a>
    </div>

    {{-- Stepper Indicator --}}
    <div class="step-container">
        <div class="step-progress-bg"></div>
        <div class="step-progress-active" id="progressBar" style="width: 0%;"></div>
        <div class="step-item active" id="ind-1"><div class="step-circle">1</div><div class="step-label">Pengirim</div></div>
        <div class="step-item" id="ind-2"><div class="step-circle">2</div><div class="step-label">Penerima</div></div>
        <div class="step-item" id="ind-3"><div class="step-circle">3</div><div class="step-label">Detail Paket</div></div>
    </div>

    {{-- Ubah Route Store ke Admin --}}
    <form id="multiOrderForm" action="{{ route('admin.koli.store') }}" method="POST" class="bg-white shadow-xl rounded-2xl border border-gray-100 relative z-0">
        @csrf

        {{-- STEP 1: PENGIRIM --}}
        <div id="step-1" class="step-content active p-6 sm:p-8">
            <div class="mb-6 border-b pb-3 flex items-center">
                <span class="bg-blue-100 text-blue-600 w-8 h-8 rounded-full flex items-center justify-center mr-3"><i class="fas fa-truck"></i></span>
                <h2 class="text-lg font-bold text-gray-800">Data Pengirim (Cari User)</h2>
            </div>
            <div class="grid grid-cols-1 gap-6">
                <div class="search-group">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Cari Kontak User (Global)</label>
                    <div class="relative">
                        <input type="text" id="sender_contact_search" class="w-full border-gray-300 rounded-xl pl-10 py-2.5" placeholder="Ketik nama user / pengirim..." autocomplete="off">
                        <i class="fas fa-search absolute left-3.5 top-3.5 text-gray-400"></i>
                    </div>
                    <div id="sender_contact_results" class="search-results hidden"></div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-1 required-label">Nama Pengirim</label>
                        {{-- HAPUS Auth::user() karena Admin menginput untuk orang lain --}}
                        <input type="text" name="sender_name" id="sender_name" class="w-full border-gray-300 rounded-lg text-sm" value="{{ old('sender_name') }}" required>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-1 required-label">No. HP</label>
                        {{-- HAPUS Auth::user() --}}
                        <input type="text" name="sender_phone" id="sender_phone" class="w-full border-gray-300 rounded-lg text-sm" value="{{ old('sender_phone') }}" required>
                    </div>
                </div>
                <div class="search-group">
                    <label class="block text-xs font-bold text-gray-600 mb-1 required-label">Cari Kelurahan / Kecamatan</label>
                    <div class="relative">
                        <input type="text" id="sender_address_search" class="w-full border-gray-300 rounded-lg text-sm pr-10 py-2.5" placeholder="Ketik kecamatan..." autocomplete="off" value="{{ old('sender_address_search') }}">
                        <i id="sender_check" class="fas fa-check-circle text-green-500 absolute right-3 top-3 hidden text-lg animate-bounce"></i>
                    </div>
                    <div id="sender_address_results" class="search-results hidden"></div>
                    <p class="text-xs text-red-500 mt-1 hidden" id="sender_error_msg">Wajib dipilih dari daftar!</p>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-600 mb-1 required-label">Alamat Lengkap</label>
                    {{-- HAPUS Auth::user() --}}
                    <textarea name="sender_address" id="sender_address" rows="2" class="w-full border-gray-300 rounded-lg text-sm" required>{{ old('sender_address') }}</textarea>
                </div>
                <div class="pt-2">
                    <label class="inline-flex items-center text-sm text-gray-600 cursor-pointer hover:text-blue-600">
                        <input type="checkbox" name="save_sender" value="on" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 mr-2 w-5 h-5">
                        <span class="font-medium">Simpan ke buku alamat</span>
                    </label>
                </div>
            </div>
        </div>

        {{-- STEP 2: PENERIMA --}}
        <div id="step-2" class="step-content p-6 sm:p-8">
            <div class="mb-6 border-b pb-3 flex items-center">
                <span class="bg-green-100 text-green-600 w-8 h-8 rounded-full flex items-center justify-center mr-3"><i class="fas fa-user"></i></span>
                <h2 class="text-lg font-bold text-gray-800">Data Penerima</h2>
            </div>
            <div class="grid grid-cols-1 gap-6">
                <div class="search-group">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Cari Kontak Penerima (Global)</label>
                    <div class="relative">
                        <input type="text" id="receiver_contact_search" class="w-full border-gray-300 rounded-xl pl-10 py-2.5" placeholder="Ketik nama penerima..." autocomplete="off">
                        <i class="fas fa-search absolute left-3.5 top-3.5 text-gray-400"></i>
                    </div>
                    <div id="receiver_contact_results" class="search-results hidden"></div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-1 required-label">Nama Penerima</label>
                        <input type="text" name="receiver_name" id="receiver_name" class="w-full border-gray-300 rounded-lg text-sm" required value="{{ old('receiver_name') }}">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-1 required-label">No. HP</label>
                        <input type="text" name="receiver_phone" id="receiver_phone" class="w-full border-gray-300 rounded-lg text-sm" required value="{{ old('receiver_phone') }}">
                    </div>
                </div>
                <div class="search-group">
                    <label class="block text-xs font-bold text-gray-600 mb-1 required-label">Cari Kelurahan / Kecamatan</label>
                    <div class="relative">
                        <input type="text" id="receiver_address_search" class="w-full border-gray-300 rounded-lg text-sm pr-10 py-2.5" placeholder="Ketik kecamatan..." autocomplete="off" value="{{ old('receiver_address_search') }}">
                        <i id="receiver_check" class="fas fa-check-circle text-green-500 absolute right-3 top-3 hidden text-lg animate-bounce"></i>
                    </div>
                    <div id="receiver_address_results" class="search-results hidden"></div>
                    <p class="text-xs text-red-500 mt-1 hidden" id="receiver_error_msg">Wajib dipilih dari daftar!</p>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-600 mb-1 required-label">Alamat Lengkap</label>
                    <textarea name="receiver_address" id="receiver_address" rows="2" class="w-full border-gray-300 rounded-lg text-sm" required>{{ old('receiver_address') }}</textarea>
                </div>
                <div class="pt-2">
                    <label class="inline-flex items-center text-sm text-gray-600 cursor-pointer hover:text-green-600">
                        <input type="checkbox" name="save_receiver" value="on" class="rounded border-gray-300 text-green-600 focus:ring-green-500 mr-2 w-5 h-5">
                        <span class="font-medium">Simpan ke buku alamat</span>
                    </label>
                </div>
            </div>
        </div>

        {{-- STEP 3: DETAIL PAKET BARU --}}
        <div id="step-3" class="step-content p-6 sm:p-8">
            <div class="mb-4 border-b pb-3 flex items-center">
                <span class="bg-yellow-100 text-yellow-600 w-8 h-8 rounded-full flex items-center justify-center mr-3"><i class="fas fa-box"></i></span>
                <h2 class="text-lg font-bold text-gray-800">Detail Semua Paket (Koli)</h2>
            </div>

            {{-- MONITOR TOTAL --}}
            <div class="sticky top-0 z-30 bg-white border-2 border-blue-600 rounded-xl p-4 mb-6 flex justify-between items-center shadow-lg">
                <div class="flex items-center gap-3">
                    <div class="bg-blue-50 p-2.5 rounded-full text-blue-600"><i class="fas fa-wallet text-xl"></i></div>
                    <div>
                        <p class="text-[10px] text-gray-500 font-bold uppercase tracking-wider">Total Semua Ongkir</p>
                        <div class="text-2xl font-extrabold text-blue-700 leading-none" id="grand_total_display">Rp 0</div>
                    </div>
                </div>
                <div class="text-right">
                    <span id="status_monitor" class="text-xs font-bold text-red-500 bg-red-100 px-2 py-1 rounded">Belum Ada Paket</span>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                {{-- KIRI: DETAIL UMUM (Berlaku untuk semua Koli) --}}
                
                <div class="col-span-1 lg:col-span-1 space-y-6">
                    <div class="bg-white p-6 rounded-xl shadow-md border border-gray-100">
                        {{-- Header Card --}}
                        <div class="flex items-center justify-between mb-6 border-b border-gray-100 pb-4">
                            <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                                <i class="fas fa-file-invoice-dollar text-blue-600"></i>
                                Detail & Pembayaran
                            </h3>
                        </div>
                        
                        {{-- Isi Paket --}}
                        <div class="mb-5">
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-2 required-label">
                                Isi Paket (Umum)
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-box-open text-gray-400"></i>
                                </div>
                                <input type="text" name="item_description" 
                                    class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all text-sm font-medium placeholder-gray-400" 
                                    required 
                                    placeholder="Contoh: Pakaian, Kosmetik, Makanan" 
                                    value="{{ old('item_description') }}">
                            </div>
                        </div>

                        {{-- Estimasi Harga --}}
                        <div class="mb-5">
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-2 required-label">
                                Estimasi Harga Barang
                            </label>
                            <div class="relative rounded-md shadow-sm">
                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                    <span class="text-gray-500 font-bold sm:text-sm">Rp</span>
                                </div>
                                <input type="text" name="item_price" 
                                    class="block w-full rounded-lg border-gray-300 pl-10 py-3 pr-12 focus:border-blue-500 focus:ring-2 focus:ring-blue-500 sm:text-sm font-bold text-gray-900 placeholder-gray-300" 
                                    required 
                                    placeholder="0" 
                                    value="{{ old('item_price') }}">
                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                                    <span class="text-gray-400 sm:text-xs">IDR</span>
                                </div>
                            </div>
                            <p class="mt-1 text-[10px] text-gray-400 italic">*Wajib diisi untuk klaim asuransi jika hilang.</p>
                        </div>
                        
                        {{-- Pilihan Asuransi --}}
                        <div class="mb-5">
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-2">
                                Asuransi Pengiriman
                            </label>
                            <div class="relative">
                                <select name="ansuransi" id="ansuransi" 
                                    class="appearance-none w-full bg-gray-50 border border-gray-300 text-gray-700 py-3 px-4 pr-8 rounded-lg leading-tight focus:outline-none focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-colors cursor-pointer font-medium text-sm">
                                    <option value="tidak" {{ old('ansuransi') == 'tidak' ? 'selected' : '' }}>🛡️ Tidak Perlu Asuransi</option>
                                    <option value="iya" {{ old('ansuransi') == 'iya' ? 'selected' : '' }}>✅ Gunakan Asuransi (Disarankan)</option>
                                </select>
                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
                                    <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/></svg>
                                </div>
                            </div>
                        </div>

                        {{-- Metode Pembayaran Custom Dropdown --}}
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-2 required-label">
                                Metode Pembayaran (Admin)
                            </label>
                            
                            <div class="relative" id="customPaymentDropdown">
                                {{-- Hidden Input --}}
                                <input type="hidden" name="payment_method" id="selectedPaymentMethod" value="{{ old('payment_method') }}">

                                {{-- Trigger Button --}}
                                <button type="button" onclick="togglePaymentDropdown()" 
                                    class="w-full bg-white border border-gray-300 rounded-lg py-3 px-4 flex items-center justify-between focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all text-left">
                                    <span class="flex items-center gap-3" id="paymentTriggerContent">
                                        {{-- Default Tampilan Jika Belum Ada Pilihan --}}
                                        <span class="text-gray-500 font-medium">-- Pilih Metode Pembayaran --</span>
                                    </span>
                                    <i class="fas fa-chevron-down text-gray-400 transition-transform" id="dropdownArrow"></i>
                                </button>

                                {{-- Dropdown List --}}
                                <div id="paymentOptionsListContainer" class="absolute z-50 mt-2 w-full bg-white rounded-xl shadow-xl border border-gray-100 hidden max-h-80 overflow-y-auto">
                                    <ul class="p-2 space-y-1">
                                        {{-- Saldo --}}
                                        <li class="payment-option cursor-pointer flex items-center p-3 rounded-lg hover:bg-blue-50 transition-colors border-b border-gray-50 last:border-0" 
                                            onclick="selectPayment('Potong Saldo', '{{ asset('public/assets/saldo.png') }}', 'Potong Saldo Admin')">
                                            <img src="{{ asset('public/assets/saldo.png') }}" class="h-8 w-8 object-contain mr-3 bg-gray-50 rounded p-1" onerror="this.style.display='none'">
                                            <div>
                                                <span class="block text-sm font-bold text-gray-800">Potong Saldo Admin</span>
                                                <span class="block text-xs text-gray-500">
                                                    Saldo Admin: 
                                                    <span class="font-bold text-green-600">Rp{{ number_format(Auth::user()->saldo ?? 0, 0, ',', '.') }}</span>
                                                </span>
                                            </div>
                                        </li>
                                        {{-- DOKU --}}
                                        <li class="payment-option cursor-pointer flex items-center p-3 rounded-lg hover:bg-blue-50 transition-colors border-b border-gray-50 last:border-0"
                                            onclick="selectPayment('DOKU_JOKUL', '{{ asset('public/assets/doku.png') }}', 'Doku (Kartu Kredit, E-Wallet)')">
                                            <img src="{{ asset('public/assets/doku.png') }}" class="h-8 w-8 object-contain mr-3 bg-gray-50 rounded p-1" onerror="this.style.display='none'">
                                            <div>
                                                <span class="block text-sm font-bold text-gray-800">DOMPET SANCAKA</span>
                                                <span class="block text-xs text-gray-500">Rekomendasi SANCAKA(VA, QRIS, CC, E-Wallet, DLL)</span>
                                            </div>
                                        </li>
                                        {{-- COD --}}
                                        <li class="payment-option cursor-pointer flex items-center p-3 rounded-lg hover:bg-blue-50 transition-colors border-b border-gray-50 last:border-0" 
                                            onclick="selectPayment('COD', '{{ asset('public/assets/cod.png') }}', 'COD (Ongkir Saja)')">
                                            <img src="{{ asset('public/assets/cod.png') }}" class="h-8 w-8 object-contain mr-3 bg-gray-50 rounded p-1" onerror="this.style.display='none'">
                                            <div>
                                                <span class="block text-sm font-bold text-gray-800">COD ONGKIR</span>
                                                <span class="block text-xs text-gray-500">Bayar ongkir di tempat</span>
                                            </div>
                                        </li>
                                        <li class="payment-option cursor-pointer flex items-center p-3 rounded-lg hover:bg-blue-50 transition-colors border-b border-gray-50 last:border-0" 
                                            onclick="selectPayment('CODBARANG', '{{ asset('public/assets/cod.png') }}', 'COD (Barang + Ongkir)')">
                                            <img src="{{ asset('public/assets/cod.png') }}" class="h-8 w-8 object-contain mr-3 bg-gray-50 rounded p-1" onerror="this.style.display='none'">
                                            <div>
                                                <span class="block text-sm font-bold text-gray-800">COD BARANG</span>
                                                <span class="block text-xs text-gray-500">Bayar Barang + Ongkir</span>
                                            </div>
                                        </li>
                                        {{-- VA & Retail --}}
                                        <div class="px-3 py-2 text-xs font-bold text-gray-400 uppercase tracking-wider bg-gray-50 mt-2 rounded">Virtual Account</div>
                                        @php
                                            $vaMethods = [
                                                'BCAVA' => ['label' => 'BCA Virtual Account', 'img' => 'bca.webp'],
                                                'BRIVA' => ['label' => 'BRI Virtual Account', 'img' => 'bri.webp'],
                                                'MANDIRIVA' => ['label' => 'Mandiri Virtual Account', 'img' => 'mandiri.webp'],
                                                'BNIVA' => ['label' => 'BNI Virtual Account', 'img' => 'bni.webp'],
                                                'PERMATAVA' => ['label' => 'Permata Virtual Account', 'img' => 'permata.webp'],
                                                'CIMBVA' => ['label' => 'CIMB Niaga VA', 'img' => 'cimb.svg'],
                                                'BSIVA' => ['label' => 'BSI Virtual Account', 'img' => 'bsi.png'],
                                                'OCBCVA' => ['label' => 'OCBC NISP Virtual Account', 'img' => 'ocbc.png'],
                                                'MUAMALATVA' => ['label' => 'Muamalat Virtual Account', 'img' => 'muamalat.png'],
                                                'DANAMONVA' => ['label' => 'Danamon Virtual Account', 'img' => 'danamon.png'],
                                                'OTHERBANKVA' => ['label' => 'Other Bank Virtual Account', 'img' => 'other.png'],
                                            ];
                                        @endphp
                                        @foreach($vaMethods as $val => $data)
                                        <li class="payment-option cursor-pointer flex items-center p-3 rounded-lg hover:bg-blue-50 transition-colors border-b border-gray-50 last:border-0" 
                                            onclick="selectPayment('{{ $val }}', '{{ asset('public/assets/'.$data['img']) }}', '{{ $data['label'] }}')">
                                            <img src="{{ asset('public/assets/'.$data['img']) }}" class="h-6 w-10 object-contain mr-3" onerror="this.style.display='none'">
                                            <span class="text-sm font-medium text-gray-700">{{ $data['label'] }}</span>
                                        </li>
                                        @endforeach
                                        
                                        <div class="px-3 py-2 text-xs font-bold text-gray-400 uppercase tracking-wider bg-gray-50 mt-2 rounded">Retail & E-Wallet</div>
                                        @php
                                            $retailMethods = [
                                                'ALFAMART' => ['label' => 'Alfamart', 'img' => 'alfamart.webp'],
                                                'INDOMARET' => ['label' => 'Indomaret', 'img' => 'indomaret.webp'],
                                                'ALFAMIDI' => ['label' => 'Alfamidi', 'img' => 'Alfamidi.png'],
                                                'QRIS' => ['label' => 'QRIS (All Payment)', 'img' => 'qris2.png'],
                                                'OVO' => ['label' => 'OVO', 'img' => 'ovo.webp'],
                                                'DANA' => ['label' => 'DANA', 'img' => 'dana.webp'],
                                                'SHOPEEPAY' => ['label' => 'ShopeePay', 'img' => 'shopeepay.webp'],
                                            ];
                                        @endphp
                                        @foreach($retailMethods as $val => $data)
                                        <li class="payment-option cursor-pointer flex items-center p-3 rounded-lg hover:bg-blue-50 transition-colors border-b border-gray-50 last:border-0" 
                                            onclick="selectPayment('{{ $val }}', '{{ asset('public/assets/'.$data['img']) }}', '{{ $data['label'] }}')">
                                            <img src="{{ asset('public/assets/'.$data['img']) }}" class="h-6 w-10 object-contain mr-3" onerror="this.style.display='none'">
                                            <span class="text-sm font-medium text-gray-700">{{ $data['label'] }}</span>
                                        </li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                {{-- KANAN: DAFTAR KOLI DINAMIS --}}
                <div class="col-span-1 lg:col-span-2">
                    <div id="koli_container" class="space-y-6">
                        {{-- Tempat Paket Ditambahkan --}}
                    </div>
                    <button type="button" id="btnAddKoli" class="w-full py-3 mt-6 border-2 border-dashed border-gray-300 text-gray-600 rounded-xl hover:bg-gray-50 transition font-bold text-sm">
                        <i class="fas fa-plus mr-2"></i> Tambah Detail Paket Baru
                    </button>
                </div>
            </div>
        </div>

        {{-- NAVIGATION BUTTONS --}}
        <div class="bg-gray-50 px-8 py-4 border-t flex justify-between items-center rounded-b-2xl sticky bottom-0 z-20">
            <button type="button" onclick="moveStep(-1)" id="btnPrev" class="hidden px-5 py-2 border border-gray-300 bg-white rounded-lg text-sm font-bold text-gray-600 hover:bg-gray-100">Kembali</button>
            <div class="flex-1"></div>
            <button type="button" onclick="moveStep(1)" id="btnNext" class="px-8 py-2.5 bg-blue-600 text-white rounded-lg text-sm font-bold shadow hover:bg-blue-700">Lanjut <i class="fas fa-arrow-right ml-1"></i></button>
            <button type="submit" id="btnSubmit" disabled class="hidden px-8 py-2.5 bg-green-600 text-white rounded-lg text-sm font-bold shadow hover:bg-green-700 disabled:bg-gray-300 disabled:text-gray-500 transition-all"><i class="fas fa-check-circle mr-2"></i> Buat Pesanan</button>
        </div>

        {{-- HIDDEN INPUTS --}}
        <input type="hidden" name="sender_district_id" id="sender_district_id" value="{{ old('sender_district_id') }}">
        <input type="hidden" name="sender_subdistrict_id" id="sender_subdistrict_id" value="{{ old('sender_subdistrict_id') }}">
        <input type="hidden" name="sender_postal_code" id="sender_postal_code" value="{{ old('sender_postal_code') }}">
        
        <input type="hidden" name="receiver_district_id" id="receiver_district_id" value="{{ old('receiver_district_id') }}">
        <input type="hidden" name="receiver_subdistrict_id" id="receiver_subdistrict_id" value="{{ old('receiver_subdistrict_id') }}">
        <input type="hidden" name="receiver_postal_code" id="receiver_postal_code" value="{{ old('receiver_postal_code') }}">
        
        <input type="hidden" name="sender_province" id="sender_province" value="{{ old('sender_province') }}">
        <input type="hidden" name="sender_regency" id="sender_regency" value="{{ old('sender_regency') }}">
        <input type="hidden" name="sender_district" id="sender_district" value="{{ old('sender_district') }}">
        <input type="hidden" name="sender_village" id="sender_village" value="{{ old('sender_village') }}">
        
        <input type="hidden" name="receiver_province" id="receiver_province" value="{{ old('receiver_province') }}">
        <input type="hidden" name="receiver_regency" id="receiver_regency" value="{{ old('receiver_regency') }}">
        <input type="hidden" name="receiver_district" id="receiver_district" value="{{ old('receiver_district') }}">
        <input type="hidden" name="receiver_village" id="receiver_village" value="{{ old('receiver_village') }}">
        
        <input type="hidden" name="sender_lat" id="sender_lat" value="{{ old('sender_lat') }}">
        <input type="hidden" name="sender_lng" id="sender_lng" value="{{ old('sender_lng') }}">
        <input type="hidden" name="receiver_lat" id="receiver_lat" value="{{ old('receiver_lat') }}">
        <input type="hidden" name="receiver_lng" id="receiver_lng" value="{{ old('receiver_lng') }}">
        
        <input type="hidden" name="grand_total" id="grand_total_input" value="0">
        <input type="hidden" name="item_type" value="1">
        {{-- 🔥 SISIPKAN INI DI SINI 🔥 --}}
        <input type="hidden" name="idempotency_key" value="{{ $idempotencyKey }}">
    </form>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // --- KONFIGURASI & VARIABEL GLOBAL (ADMIN MODE) ---
    let step = 1;
    let koliCount = 0;
    
    const ASSET_BASE_URL = '{{ asset('public/storage/logo-ekspedisi') }}'; 
    
    // Ubah routes ke Admin jika diperlukan, atau pastikan API support Global Search
    const ROUTES = {
        addr: "{{ route('api.address.search') }}",
        contact: "{{ route('api.contacts.search') }}",
        ongkir: "{{ route('admin.koli.cek_ongkir') }}",      // Route Admin
        store_single: "{{ route('admin.koli.store_single') }}" // Route Admin
    };

    document.addEventListener('DOMContentLoaded', function() {
        const priceInput = document.querySelector('input[name="item_price"]');
        const btnAddKoli = document.getElementById('btnAddKoli');
        
        // 1. Setup Awal
        renderKoliCard(koliCount);
        koliCount++;
        updateGrandTotal();

        // 2. Event Listener
        if (btnAddKoli) {
            btnAddKoli.addEventListener('click', () => {
                renderKoliCard(koliCount);
                koliCount++;
            });
        }

        if(priceInput) {
            priceInput.addEventListener('keyup', function() {
                let raw = this.value.replace(/\D/g,'');
                this.value = new Intl.NumberFormat('id-ID').format(raw || 0);
            });
        }

        // 3. Inisialisasi Pencarian
        setupSearch('sender');
        setupSearch('receiver');
    });

    // --- FUNGSI UTAMA: RENDER KARTU PAKET ---
    function renderKoliCard(index) {
        const container = document.getElementById('koli_container');
        const html = `
        <div class="koli-card bg-white border border-gray-200 rounded-xl shadow-sm p-4 relative transition-all duration-300" id="koli-card-${index}">
            <div class="flex justify-between items-center border-b pb-2 mb-3">
                <span class="bg-gray-800 text-white text-xs font-bold px-2 py-1 rounded" id="badge-status-${index}">PAKET #${index+1}</span>
                <div class="flex items-center gap-3">
                    <div class="text-right" id="selected-courier-display-${index}">
                        <span class="text-xs text-red-500 font-bold"><i class="fas fa-exclamation-circle"></i> Belum pilih kurir</span>
                    </div>
                    <button type="button" id="btn-delete-${index}" onclick="removeKoli(${index})" class="text-red-500 hover:text-red-700 transition text-xs ${index === 0 ? 'hidden' : ''}">
                        <i class="fas fa-times-circle"></i> Hapus
                    </button>
                </div>
            </div>
            <fieldset id="fieldset-koli-${index}" class="grid grid-cols-4 gap-3 mb-3 border-0 p-0 m-0">
                <div class="col-span-4 sm:col-span-1">
                    <label class="text-[10px] text-gray-500 block uppercase font-bold required-label">Berat (Gram)</label>
                    <input type="number" name="packages[${index}][weight]" class="w-full border-gray-300 rounded text-sm p-2 font-bold focus:ring-blue-500" placeholder="1000" value="1000" min="1" onchange="resetKoli(${index})">
                </div>
                <div class="col-span-4 sm:col-span-3 grid grid-cols-3 gap-2">
                    <div><label class="text-[10px] text-gray-500 block required-label">P (cm)</label><input type="number" name="packages[${index}][length]" class="w-full border-gray-300 rounded text-xs p-2" value="10" min="1" onchange="resetKoli(${index})"></div>
                    <div><label class="text-[10px] text-gray-500 block required-label">L (cm)</label><input type="number" name="packages[${index}][width]" class="w-full border-gray-300 rounded text-xs p-2" value="10" min="1" onchange="resetKoli(${index})"></div>
                    <div><label class="text-[10px] text-gray-500 block required-label">T (cm)</label><input type="number" name="packages[${index}][height]" class="w-full border-gray-300 rounded text-xs p-2" value="10" min="1" onchange="resetKoli(${index})"></div>
                </div>
            </fieldset>
            <button type="button" id="btn-cek-ongkir-${index}" onclick="checkOngkirSingle(${index})" class="w-full py-2 bg-blue-50 text-blue-700 border border-blue-200 rounded-lg font-bold text-sm hover:bg-blue-100 transition mb-3">
                <i class="fas fa-search mr-1"></i> Cek Ongkir Paket #${index+1}
            </button>
            <div id="result-list-${index}" class="hidden max-h-48 overflow-y-auto border border-gray-200 rounded-lg bg-gray-50 custom-scrollbar mb-3"></div>
            <div id="action-area-${index}" class="hidden mt-2 pt-2 border-t border-dashed border-gray-300 animate-fade-in">
                <button type="button" onclick="uploadSingleKoli(${index})" class="w-full py-3 bg-green-600 text-white rounded-xl shadow-lg hover:bg-green-700 transition font-bold text-sm flex justify-center items-center gap-2">
                    <i class="fas fa-paper-plane"></i> KIRIM / SIMPAN PAKET #${index+1}
                </button>
                <p class="text-[10px] text-gray-500 text-center mt-2">Data paket ini (berat & dimensi) akan langsung disimpan.</p>
            </div>
            <div id="success-msg-${index}" class="hidden bg-green-100 text-green-700 p-3 rounded-lg text-center font-bold text-sm mt-3 border border-green-200">
                <i class="fas fa-check-circle text-lg mb-1"></i><br>Paket Tersimpan
            </div>
            <input type="hidden" name="packages[${index}][courier_code]" id="input-courier-${index}">
            <input type="hidden" name="packages[${index}][service_code]" id="input-service-${index}">
            <input type="hidden" name="packages[${index}][shipping_cost]" id="input-cost-${index}" value="0">
        </div>`;
        container.insertAdjacentHTML('beforeend', html);
    }

    function removeKoli(index) {
        const card = document.getElementById(`koli-card-${index}`);
        if(card) {
            card.style.opacity = '0';
            setTimeout(() => { card.remove(); updateGrandTotal(); }, 300);
        }
    }

    function resetKoli(index) {
        document.getElementById(`input-cost-${index}`).value = 0;
        document.getElementById(`input-courier-${index}`).value = '';
        document.getElementById(`selected-courier-display-${index}`).innerHTML = '<span class="text-xs text-red-500 font-bold"><i class="fas fa-exclamation-circle"></i> Pilih Lagi</span>';
        
        const card = document.getElementById(`koli-card-${index}`);
        if(card.classList.contains('bg-green-50')) return; 

        card.classList.remove('border-blue-400', 'bg-blue-50');
        document.getElementById(`action-area-${index}`).classList.add('hidden');
        document.getElementById(`result-list-${index}`).innerHTML = '';
        updateGrandTotal();
    }

    function checkOngkirSingle(index) {
        const sDist = document.getElementById('sender_district_id').value;
        const rDist = document.getElementById('receiver_district_id').value;
        const sSub = document.getElementById('sender_subdistrict_id').value;
        const rSub = document.getElementById('receiver_subdistrict_id').value;
          
        if(!sDist || !rDist) { 
            Swal.fire({icon: 'error', title: 'Data Wilayah Kosong', text: 'Mohon lengkapi alamat pengirim & penerima di Step 1 & 2.'}); 
            return; 
        }

        const card = document.getElementById(`koli-card-${index}`);
        const weight = card.querySelector(`input[name="packages[${index}][weight]"]`).value;
        const l = card.querySelector(`input[name="packages[${index}][length]"]`).value;
        const w = card.querySelector(`input[name="packages[${index}][width]"]`).value;
        const h = card.querySelector(`input[name="packages[${index}][height]"]`).value;
        const vol = parseInt(l) * parseInt(w) * parseInt(h);
        
        let itemPriceRaw = document.querySelector('input[name="item_price"]').value.replace(/\D/g, '');
        const itemPrice = itemPriceRaw || 1000;

        const resultDiv = document.getElementById(`result-list-${index}`);
        resultDiv.classList.remove('hidden');
        resultDiv.innerHTML = '<div class="p-4 text-center text-xs text-gray-500"><i class="fas fa-spinner fa-spin"></i> Sedang memuat ongkir...</div>';

        let fd = new FormData();
        fd.append('sender_district_id', sDist);
        fd.append('sender_subdistrict_id', sSub);
        fd.append('receiver_district_id', rDist);
        fd.append('receiver_subdistrict_id', rSub);
        fd.append('weight', weight);
        fd.append('volume', vol);
        fd.append('item_price', itemPrice);
        fd.append('service_type', 'mix'); 
        fd.append('ansuransi', document.querySelector('select[name="ansuransi"]').value);
        fd.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));

        fetch(ROUTES.ongkir, { method: 'POST', body: fd })
        .then(r => r.json())
        .then(d => {
            resultDiv.innerHTML = '';
            if(!d.status || !d.results || d.results.length === 0) { 
                resultDiv.innerHTML = '<div class="p-2 text-center text-red-500 text-xs">Kurir tidak ditemukan untuk rute/berat ini.</div>'; 
                return; 
            }

            // 4. SORTING: Termurah -> Termahal
            d.results.sort((a, b) => parseInt(a.cost) - parseInt(b.cost));

            d.results.forEach(res => {
                let cost = parseInt(res.cost);
                let logo = `${ASSET_BASE_URL}/${res.service.toLowerCase()}.png`;
                
                let item = document.createElement('div');
                item.className = 'p-3 border-b bg-white hover:bg-blue-50 cursor-pointer flex justify-between items-center transition';
                
                item.innerHTML = `
                    <div class="flex items-center gap-3">
                        <img src="${logo}" class="w-8 h-8 object-contain" onerror="this.style.display='none'">
                        <div>
                            <div class="font-bold text-sm text-gray-800">${res.service_name}</div>
                            <div class="text-[10px] text-gray-500 uppercase">${res.service} - ${res.service_type} • Est: ${res.etd} hari</div>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="font-bold text-blue-700 text-sm">Rp ${new Intl.NumberFormat('id-ID').format(cost)}</div>
                        <button type="button" class="mt-1 text-[10px] bg-blue-100 text-blue-700 px-2 py-1 rounded font-bold hover:bg-blue-600 hover:text-white transition">PILIH</button>
                    </div>
                `;
                item.onclick = () => selectCourierForKoli(index, res.service, res.service_type, res.service_name, cost);
                resultDiv.appendChild(item);
            });
        })
        .catch(e => { 
            console.error(e);
            resultDiv.innerHTML = '<div class="p-2 text-center text-red-500 text-xs">Gagal memuat data (API Error).</div>'; 
        });
    }

    function selectCourierForKoli(index, courierCode, serviceCode, serviceName, cost) {
        document.getElementById(`input-courier-${index}`).value = courierCode;
        document.getElementById(`input-service-${index}`).value = serviceCode;
        document.getElementById(`input-cost-${index}`).value = cost;
        
        document.getElementById(`selected-courier-display-${index}`).innerHTML = `
            <div class="flex flex-col items-end animate-pulse">
                <span class="text-[10px] text-gray-500">${serviceName}</span>
                <span class="text-sm font-bold text-green-600">Rp ${new Intl.NumberFormat('id-ID').format(cost)}</span>
            </div>`;
        
        document.getElementById(`result-list-${index}`).classList.add('hidden');
        
        const card = document.getElementById(`koli-card-${index}`);
        card.classList.remove('border-gray-200');
        card.classList.add('border-blue-400', 'bg-blue-50');

        const actionArea = document.getElementById(`action-area-${index}`);
        actionArea.classList.remove('hidden');
        
        updateGrandTotal();
    }
    
    function uploadSingleKoli(index) {
        const sName = document.getElementById('sender_name').value;
        const rName = document.getElementById('receiver_name').value;
        const itemDesc = document.querySelector('input[name="item_description"]').value;
        const itemPrice = document.querySelector('input[name="item_price"]').value.replace(/\D/g, '');

        const sProv = document.getElementById('sender_province').value;
        const rProv = document.getElementById('receiver_province').value;

        if (document.getElementById('sender_district_id').value && !sProv) {
             Swal.fire({
                icon: 'error',
                title: 'Data Wilayah Pengirim Belum Dipilih',
                text: 'Mohon KLIK salah satu pilihan alamat Pengirim yang muncul di bawah kolom pencarian (warna biru).',
            });
            return; 
        }
        if (document.getElementById('receiver_district_id').value && !rProv) {
             Swal.fire({
                icon: 'error',
                title: 'Data Wilayah Penerima Belum Dipilih',
                text: 'Mohon KLIK salah satu pilihan alamat Penerima yang muncul di bawah kolom pencarian (warna biru).',
            });
            return; 
        }

        if(!sName || !rName) { Swal.fire('Data Tidak Lengkap', 'Pastikan nama pengirim dan penerima terisi.', 'warning'); return; }
        if(!itemDesc || !itemPrice) { Swal.fire('Detail Barang Kosong', 'Isi deskripsi dan harga barang.', 'warning'); return; }

        const card = document.getElementById(`koli-card-${index}`);
        let formData = new FormData();
        
        formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
        
        formData.append('sender_name', sName);
        formData.append('sender_phone', document.getElementById('sender_phone').value);
        formData.append('sender_address', document.getElementById('sender_address').value);
        formData.append('sender_district_id', document.getElementById('sender_district_id').value);
        formData.append('sender_subdistrict_id', document.getElementById('sender_subdistrict_id').value);
        formData.append('sender_province', document.getElementById('sender_province').value);
        formData.append('sender_regency', document.getElementById('sender_regency').value);
        formData.append('sender_district', document.getElementById('sender_district').value);
        formData.append('sender_village', document.getElementById('sender_village').value);
        formData.append('sender_postal_code', document.getElementById('sender_postal_code').value);
        formData.append('sender_lat', document.getElementById('sender_lat').value);
        formData.append('sender_lng', document.getElementById('sender_lng').value);
        
        formData.append('receiver_name', rName);
        formData.append('receiver_phone', document.getElementById('receiver_phone').value);
        formData.append('receiver_address', document.getElementById('receiver_address').value);
        formData.append('receiver_district_id', document.getElementById('receiver_district_id').value);
        formData.append('receiver_subdistrict_id', document.getElementById('receiver_subdistrict_id').value);
        formData.append('receiver_province', document.getElementById('receiver_province').value);
        formData.append('receiver_regency', document.getElementById('receiver_regency').value);
        formData.append('receiver_district', document.getElementById('receiver_district').value);
        formData.append('receiver_village', document.getElementById('receiver_village').value);
        formData.append('receiver_postal_code', document.getElementById('receiver_postal_code').value);
        formData.append('receiver_lat', document.getElementById('receiver_lat').value);
        formData.append('receiver_lng', document.getElementById('receiver_lng').value);

        formData.append('item_description', itemDesc);
        formData.append('item_price', itemPrice);
        formData.append('payment_method', document.querySelector('input[name="payment_method"]').value);
        formData.append('ansuransi', document.querySelector('select[name="ansuransi"]').value);

        formData.append('weight', card.querySelector(`input[name="packages[${index}][weight]"]`).value);
        formData.append('length', card.querySelector(`input[name="packages[${index}][length]"]`).value);
        formData.append('width', card.querySelector(`input[name="packages[${index}][width]"]`).value);
        formData.append('height', card.querySelector(`input[name="packages[${index}][height]"]`).value);
        
        formData.append('courier_code', document.getElementById(`input-courier-${index}`).value);
        formData.append('service_code', document.getElementById(`input-service-${index}`).value);
        formData.append('shipping_cost', document.getElementById(`input-cost-${index}`).value);
        
        formData.append('return_url', '{{ request()->url() }}');

        Swal.fire({
            title: 'Menyimpan Paket...',
            html: 'Sedang memproses pesanan ke server...',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading() }
        });

        fetch(ROUTES.store_single, { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            if(data.success) {
                // 1. & 3. Logic Payment URL (Open New Tab)
                if(data.payment_url) {
                    window.open(data.payment_url, '_blank');
                    
                    Swal.fire({
                        icon: 'info',
                        title: 'Pembayaran',
                        text: 'Halaman pembayaran telah dibuka di tab baru. Silakan selesaikan pembayaran.',
                        timer: 5000,
                        showConfirmButton: false
                    });
                } else {
                     Swal.fire({
                        icon: 'success', 
                        title: 'Berhasil!', 
                        text: 'Paket berhasil disimpan. Resi: ' + (data.resi || 'DIPROSES'),
                        timer: 2000,
                        showConfirmButton: false
                    });
                }
                
                // 2. Status Sukses (Hijau)
                lockKoliCard(index);
            } else {
                Swal.fire('Gagal', data.message || 'Terjadi kesalahan pada server.', 'error');
            }
        })
        .catch(err => {
            console.error(err);
            Swal.fire('Error', 'Terjadi kesalahan koneksi internet.', 'error');
        });
    }

    function lockKoliCard(index) {
        const card = document.getElementById(`koli-card-${index}`);
        const fieldset = document.getElementById(`fieldset-koli-${index}`);
        const btnCek = document.getElementById(`btn-cek-ongkir-${index}`);
        const actionArea = document.getElementById(`action-area-${index}`);
        const successMsg = document.getElementById(`success-msg-${index}`);
        const badge = document.getElementById(`badge-status-${index}`);
        const btnDel = document.getElementById(`btn-delete-${index}`);

        // UI Changes for Success State
        card.classList.remove('border-blue-400', 'bg-blue-50');
        card.classList.add('border-green-500', 'bg-green-50'); // Green border/bg
        
        fieldset.disabled = true;
        btnCek.classList.add('hidden');
        actionArea.classList.add('hidden'); 
        if(btnDel) btnDel.classList.add('hidden'); 

        successMsg.classList.remove('hidden');
        badge.classList.remove('bg-gray-800');
        badge.classList.add('bg-green-600');
        badge.innerHTML = '<i class="fas fa-check"></i> SUKSES';
        
        updateGrandTotal();
        checkAllCompleted();
    }

    function updateGrandTotal() {
        let total = 0;
        let koliElements = document.querySelectorAll('.koli-card');
        let count = koliElements.length;
        
        koliElements.forEach(card => {
            const index = parseInt(card.id.split('-')[2]);
            const costInput = document.getElementById(`input-cost-${index}`);
            const val = parseInt(costInput ? costInput.value : 0) || 0;
            total += val;
        });
        
        document.getElementById('grand_total_display').innerText = 'Rp ' + new Intl.NumberFormat('id-ID').format(total);
        document.getElementById('grand_total_input').value = total;

        const st = document.getElementById('status_monitor');
        if (count === 0) {
            st.innerHTML = 'Belum Ada Paket'; st.className = 'text-xs font-bold text-red-500 bg-red-100 px-2 py-1 rounded';
        } else {
             st.innerHTML = `${count} Paket dalam daftar`; st.className = 'text-xs font-bold text-blue-600 bg-blue-100 px-2 py-1 rounded';
        }
    }

    function checkAllCompleted() {
        const btnFinish = document.getElementById('btnSubmit');
        if(btnFinish) {
            btnFinish.innerHTML = 'Selesai & Buat Baru'; // Clearer text
            btnFinish.classList.remove('hidden');
            btnFinish.disabled = false;
            btnFinish.type = 'button'; 
            // 5. Reload current page (Step 3 fresh state/New Order)
            btnFinish.onclick = function() { window.location.reload(); };
        }
    }

    function moveStep(dir) {
        if(dir === 1 && !validate()) return;
        document.getElementById(`ind-${step}`).classList.remove('active');
        document.getElementById(`step-${step}`).classList.remove('active');
        if(dir===1) document.getElementById(`ind-${step}`).classList.add('completed'); 
        else document.getElementById(`ind-${step}`).classList.remove('completed');
        
        step += dir;
        
        document.getElementById(`ind-${step}`).classList.add('active');
        document.getElementById(`step-${step}`).classList.add('active');
        document.getElementById('progressBar').style.width = ((step - 1) * 50) + '%';
        
        document.getElementById('btnPrev').classList.toggle('hidden', step === 1);
        document.getElementById('btnNext').classList.toggle('hidden', step === 3);
        
        const btnSubmit = document.getElementById('btnSubmit');
        if(step === 3) {
            btnSubmit.classList.remove('hidden');
            btnSubmit.innerHTML = 'Selesai & Buat Baru';
            // 5. Reload current page
            btnSubmit.onclick = function(e) { e.preventDefault(); window.location.reload(); };
        } else {
            btnSubmit.classList.add('hidden');
        }
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function validate() {
        let valid = true; let msg = '';
        if(step === 1) {
            if(!document.getElementById('sender_name').value) { valid=false; msg='Isi nama pengirim'; }
            else if(!document.getElementById('sender_phone').value) { valid=false; msg='Isi nomor HP pengirim'; }
            else if(!document.getElementById('sender_district_id').value) { valid=false; document.getElementById('sender_error_msg').classList.remove('hidden'); msg='Pilih wilayah pengirim!'; }
            else if(!document.getElementById('sender_address').value) { valid=false; msg='Isi alamat lengkap pengirim!'; }
        } else if(step === 2) {
            if(!document.getElementById('receiver_name').value) { valid=false; msg='Isi nama penerima'; }
            else if(!document.getElementById('receiver_phone').value) { valid=false; msg='Isi nomor HP penerima'; }
            else if(!document.getElementById('receiver_district_id').value) { valid=false; document.getElementById('receiver_error_msg').classList.remove('hidden'); msg='Pilih wilayah penerima!'; }
            else if(!document.getElementById('receiver_address').value) { valid=false; msg='Isi alamat lengkap penerima!'; }
        }
        if(!valid) Swal.fire({icon:'warning', title:'Lengkapi Data', text: msg, timer: 3000, showConfirmButton: false});
        return valid;
    }

    // --- FUNGSI PENGISI DATA ALAMAT (DIPAKAI OLEH SETUP SEARCH & API) ---
    function pilihAlamatAPI(prefix, i, aInput, aRes, check) {
        // 1. TAMPILKAN DI INPUT
        aInput.value = i.full_address;
        
        // 2. ISI ID (Jika Ada)
        document.getElementById(prefix+'_district_id').value = i.district_id; 
        document.getElementById(prefix+'_subdistrict_id').value = i.subdistrict_id;
        
        // 3. PARSING TEKS (Pecah string koma)
        // Format: "Desa, Kecamatan, Kota, Provinsi, KodePos"
        let parts = i.full_address.split(',').map(s => s.trim());
        let vVillage="", vDistrict="", vRegency="", vProv="", vPostal="";

        if (parts.length >= 4) {
            vVillage  = parts[0]; 
            vDistrict = parts[1]; 
            vRegency  = parts[2]; 
            vProv     = parts[3]; 
            vPostal   = parts[4] || ""; 
        } else {
            // Fallback
            vDistrict = i.full_address;
        }

        // 4. ISI HIDDEN INPUT
        document.getElementById(prefix+'_province').value    = vProv;
        document.getElementById(prefix+'_regency').value     = vRegency;
        document.getElementById(prefix+'_district').value    = vDistrict;
        document.getElementById(prefix+'_village').value     = vVillage;
        document.getElementById(prefix+'_postal_code').value = vPostal;

        // 5. UI UPDATE
        check.classList.remove('hidden');
        aRes.classList.add('hidden');
        
        // Debug
        console.log(`ALAMAT DIPILIH (${prefix}):`, {vProv, vRegency, vDistrict});
    }

    // --- SETUP SEARCH (LOGIC ADMIN: GLOBAL SEARCH VIA PARAMETER) ---
    function setupSearch(prefix) {
        const cInput = document.getElementById(prefix+'_contact_search');
        const cRes = document.getElementById(prefix+'_contact_results');
        const aInput = document.getElementById(prefix+'_address_search');
        const aRes = document.getElementById(prefix+'_address_results');
        const check = document.getElementById(prefix+'_check');
        let timer;

        // 1. PENCARIAN KONTAK
        cInput.addEventListener('input', function() {
            clearTimeout(timer);
            if(this.value.length < 2) { cRes.classList.add('hidden'); return; }
            
            timer = setTimeout(() => {
                // Menambahkan &scope=global atau parameter lain agar backend tahu ini Admin
                fetch(`${ROUTES.contact}?search=${encodeURIComponent(this.value)}&tipe=${prefix==='sender'?'Pengirim':'Penerima'}&scope=global`)
                .then(r=>r.json()).then(d => {
                    cRes.innerHTML = ''; cRes.classList.remove('hidden');
                    if (d.length === 0) { cRes.innerHTML = '<div class="result-item">Tidak ada kontak ditemukan.</div>'; return; }
                    
                    d.forEach(i => {
                        let div = document.createElement('div');
                        div.className = 'result-item';
                        div.innerHTML = `<strong>${i.nama}</strong><br><span class="text-xs text-gray-500">${i.no_hp}</span>`;
                        
                        div.onclick = (e) => {
                            e.stopPropagation(); // STOP PROPAGASI agar tidak menutup dropdown lain
                            
                            // A. ISI DATA UMUM
                            document.getElementById(prefix+'_name').value = i.nama;
                            document.getElementById(prefix+'_phone').value = i.no_hp;
                            document.getElementById(prefix+'_address').value = i.alamat;

                            // B. SUSUN KATA KUNCI PENCARIAN ALAMAT
                            let keyword = '';
                            if (i.village && i.district) { keyword = `${i.village}, ${i.district}`; }
                            else if (i.district && i.regency) { keyword = `${i.district}, ${i.regency}`; }
                            else if (i.regency) { keyword = i.regency; }
                            
                            aInput.value = keyword;
                            
                            // KOSONGKAN ID AGAR USER MEMILIH ULANG
                            document.getElementById(prefix+'_district_id').value = ''; 
                            check.classList.add('hidden');

                            // C. AUTO SEARCH API
                            if (keyword.length > 2) {
                                aRes.innerHTML = '<div class="result-item text-blue-600"><i class="fas fa-spinner fa-spin"></i> Mencari data wilayah valid...</div>';
                                aRes.classList.remove('hidden'); // TAMPILKAN DROPDOWN

                                fetch(`${ROUTES.addr}?search=${encodeURIComponent(keyword)}`)
                                .then(r2 => r2.json())
                                .then(resp => {
                                    aRes.innerHTML = '';
                                    let results = Array.isArray(resp) ? resp : (resp.data || []);

                                    if (results.length === 0) {
                                        aRes.innerHTML = '<div class="result-item text-red-500">Wilayah tidak ditemukan otomatis. Silakan ketik manual.</div>';
                                    } else {
                                            results.forEach(addr => {
                                                let item = document.createElement('div');
                                                item.className = 'result-item';
                                                item.innerText = addr.full_address;
                                                item.onclick = () => pilihAlamatAPI(prefix, addr, aInput, aRes, check);
                                                aRes.appendChild(item);
                                            });
                                            
                                            const Toast = Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 4000 });
                                            Toast.fire({ icon: 'info', title: 'Silakan KLIK wilayah yang sesuai di bawah.' });
                                    }
                                    // PASTIKAN DROPDOWN TETAP MUNCUL
                                    aRes.classList.remove('hidden'); 
                                });
                            }

                            cRes.classList.add('hidden'); cInput.value = '';
                        };
                        cRes.appendChild(div);
                    });
                });
            }, 400);
        });

        // 2. PENCARIAN ALAMAT MANUAL
        aInput.addEventListener('input', function() {
            clearTimeout(timer);
            check.classList.add('hidden');
            document.getElementById(prefix+'_district_id').value = ''; 
            
            if(this.value.length < 3) { aRes.classList.add('hidden'); return; }
            
            timer = setTimeout(() => {
                fetch(`${ROUTES.addr}?search=${encodeURIComponent(this.value)}`)
                .then(r=>r.json()).then(resp => {
                    aRes.innerHTML = ''; aRes.classList.remove('hidden');
                    let results = Array.isArray(resp) ? resp : (resp.data || []);

                    if (results.length === 0) { 
                        aRes.innerHTML = '<div class="result-item">Tidak ada wilayah ditemukan.</div>'; 
                        return; 
                    }
                    
                    results.forEach(i => {
                        let div = document.createElement('div');
                        div.className = 'result-item';
                        div.innerText = i.full_address; 
                        div.onclick = (e) => pilihAlamatAPI(prefix, i, aInput, aRes, check);
                        aRes.appendChild(div);
                    });
                });
            }, 400);
        });

        // Close Dropdown
        document.addEventListener('click', e => { 
            if(!cInput.contains(e.target)) cRes.classList.add('hidden'); 
            if(!aInput.contains(e.target)) aRes.classList.add('hidden'); 
        });
    }
    
     function togglePaymentDropdown() {
        const dropdown = document.getElementById('paymentOptionsListContainer');
        const arrow = document.getElementById('dropdownArrow');
        
        if (dropdown.classList.contains('hidden')) {
            dropdown.classList.remove('hidden');
            arrow.classList.add('rotate-180');
        } else {
            dropdown.classList.add('hidden');
            arrow.classList.remove('rotate-180');
        }
    }

    function selectPayment(value, imgSrc, label) {
        // Update Hidden Input
        document.getElementById('selectedPaymentMethod').value = value;
        
        // Update Trigger Display
        const triggerContent = document.getElementById('paymentTriggerContent');
        triggerContent.innerHTML = `
            <img src="${imgSrc}" class="h-6 w-auto object-contain" onerror="this.style.display='none'">
            <span class="text-sm font-bold text-gray-800">${label}</span>
        `;
        
        // Close Dropdown
        togglePaymentDropdown();
    }

    // Close dropdown when clicking outside
    document.addEventListener('click', function(event) {
        const dropdown = document.getElementById('paymentOptionsListContainer');
        const trigger = document.getElementById('customPaymentDropdown');
        
        if (!trigger.contains(event.target) && !dropdown.classList.contains('hidden')) {
            togglePaymentDropdown();
        }
    });

    // Initialize selected value (if editing)
    document.addEventListener('DOMContentLoaded', function() {
        const initialValue = document.getElementById('selectedPaymentMethod').value;
        if(initialValue) {
            // Find corresponding option in list to simulate click or just set text
            // Simple approach: check if we can find the element with onclick containing the value
            const options = document.querySelectorAll('.payment-option');
            options.forEach(opt => {
                if(opt.getAttribute('onclick').includes(`'${initialValue}'`)) {
                    opt.click(); // Simulate click to set UI
                    // But prevent dropdown from opening/toggling immediately
                    const dropdown = document.getElementById('paymentOptionsListContainer');
                    const arrow = document.getElementById('dropdownArrow');
                    dropdown.classList.add('hidden');
                    arrow.classList.remove('rotate-180');
                }
            });
        }
    });
</script>
@endpush