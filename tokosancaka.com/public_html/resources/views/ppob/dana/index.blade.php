@extends('layouts.marketplace')

@section('content')
<div class="min-h-screen bg-gray-50 py-10 font-sans">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="mb-8 text-center">
            <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">Top Up & Tagihan</h1>
            <p class="mt-2 text-sm text-gray-500">Beli Pulsa, Paket Data, dan Bayar Tagihan otomatis langsung masuk.</p>
        </div>

        @if(session('success'))
        <div class="mb-6 p-4 bg-green-100 border-l-4 border-green-500 text-green-700 rounded shadow-sm flex items-center">
            <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <span class="font-medium">{{ session('success') }}</span>
        </div>
        @endif

        @if(session('error'))
        <div class="mb-6 p-4 bg-red-100 border-l-4 border-red-500 text-red-700 rounded shadow-sm flex items-center">
            <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <span class="font-medium">{{ session('error') }}</span>
        </div>
        @endif

        <div class="bg-white shadow-xl rounded-2xl overflow-hidden border border-gray-100">
            <!-- TABS PRABAYAR / PASCABAYAR -->
            <div class="flex border-b border-gray-200">
                <button type="button" id="tabPraBtn" class="flex-1 py-4 text-center font-bold text-blue-600 border-b-2 border-blue-600 bg-blue-50 focus:outline-none transition-colors" onclick="switchTab('prabayar')">
                    Prabayar (Pulsa/Data)
                </button>
                <button type="button" id="tabPascaBtn" class="flex-1 py-4 text-center font-medium text-gray-500 border-b-2 border-transparent hover:text-gray-700 hover:bg-gray-50 focus:outline-none transition-colors" onclick="switchTab('pascabayar')">
                    Pascabayar (Tagihan)
                </button>
            </div>

            <!-- PERHATIKAN: Route action sesuaikan dengan route Anda -->
            <form action="{{ route('ppob.pay') }}" method="POST" class="p-6 sm:p-8" id="formPpob">
                @csrf
                <!-- Input tersembunyi yang ditangkap oleh Controller -->
                <input type="hidden" name="type" id="trx_type" value="prabayar">
                <input type="hidden" name="product_code" id="final_product_code" value="">

                <div class="mb-8">
                    <label for="customer_id" class="block text-sm font-bold text-gray-700 mb-2">Nomor HP / Tujuan / ID Pelanggan</label>
                    <div class="relative rounded-xl shadow-sm">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <!-- Name diubah jadi customer_id sesuai request controller -->
                        <input type="number" name="customer_id" id="customer_id" value="{{ old('customer_id') }}"
                            class="focus:ring-blue-500 focus:border-blue-500 block w-full pl-12 pr-4 py-3.5 sm:text-base border-gray-300 rounded-xl bg-gray-50 transition-colors duration-200"
                            placeholder="Contoh: 081234567890 / 5300xxxx" required>
                    </div>
                </div>

                <hr class="border-gray-200 mb-8">

                <!-- KONTEN PRABAYAR -->
                <div id="contentPrabayar">
                    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <h3 class="text-lg font-bold text-gray-900">Pilih Nominal</h3>
                        <div class="relative w-full sm:w-64">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-4 w-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                            <input type="text" id="searchProduct" class="focus:ring-blue-500 focus:border-blue-500 block w-full pl-9 pr-3 py-2 text-sm border-gray-300 rounded-lg bg-gray-50" placeholder="Cari pulsa, data, provider...">
                        </div>
                    </div>

                    <div class="mb-8 max-h-[400px] overflow-y-auto pr-2 pb-2 custom-scrollbar">
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4" id="productGrid">
                            @if(isset($pricelistPrepaid))
                                @forelse($pricelistPrepaid as $product)
                                <label class="product-card-item cursor-pointer h-full" data-search="{{ strtolower($product->operator . ' ' . $product->description . ' ' . $product->price) }}">
                                    <!-- Radio button lokal prabayar -->
                                    <input type="radio" name="temp_code_pra" value="{{ $product->code }}" class="peer sr-only">

                                    <div class="h-full rounded-xl border-2 border-gray-100 bg-white p-4 hover:bg-gray-50 hover:border-gray-200 peer-checked:border-blue-500 peer-checked:bg-blue-50 peer-checked:shadow-md transition-all duration-200 flex flex-col justify-between">
                                        <div class="flex items-start space-x-3 mb-3">
                                            <div class="flex-shrink-0">
                                                @php
                                                    $operatorName = $product->operator ?? 'PPOB';
                                                    $color = match(strtolower($operatorName)) {
                                                        'telkomsel' => 'e11d48',
                                                        'indosat', 'isat' => 'f59e0b',
                                                        'xl', 'axis' => '0284c7',
                                                        'tri', 'three' => '000000',
                                                        'smartfren' => 'be185d',
                                                        'pln' => '0ea5e9',
                                                        default => '4f46e5'
                                                    };
                                                @endphp
                                                <img src="https://ui-avatars.com/api/?name={{ urlencode($operatorName) }}&background={{ $color }}&color=fff&rounded=true&bold=true&size=128"
                                                     alt="{{ $operatorName }}" class="w-10 h-10 rounded-full shadow-sm">
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm font-bold text-gray-900 leading-tight">
                                                    {{ $product->description }}
                                                </p>
                                                <p class="text-xs text-gray-500 truncate capitalize mt-1">
                                                    {{ $operatorName }} - {{ $product->type }}
                                                </p>
                                            </div>
                                        </div>
                                        <div class="mt-2 flex items-center justify-between border-t border-gray-100 pt-2">
                                            <p class="text-lg font-black text-blue-600">
                                                Rp {{ number_format((float)$product->price, 0, ',', '.') }}
                                            </p>
                                            <div class="hidden peer-checked:block text-blue-600">
                                                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                                            </div>
                                        </div>
                                    </div>
                                </label>
                                @empty
                                <div class="col-span-full py-10 flex flex-col items-center justify-center border-2 border-dashed border-gray-300 rounded-xl bg-gray-50">
                                    <span class="text-gray-500 font-medium">Produk Prabayar belum tersedia.</span>
                                </div>
                                @endforelse
                            @endif
                        </div>
                        <div id="noResultMsg" class="hidden py-8 text-center text-gray-500">
                            Pencarian tidak menemukan hasil. Coba kata kunci lain.
                        </div>
                    </div>
                </div>

                <!-- KONTEN PASCABAYAR -->
                <div id="contentPascabayar" class="hidden mb-8">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">Pilih Layanan Pascabayar</h3>
                    <select name="temp_code_pasca" id="selectPasca" class="focus:ring-blue-500 focus:border-blue-500 block w-full py-3.5 px-4 sm:text-base border-gray-300 rounded-xl bg-gray-50">
                        <option value="" disabled selected>-- Pilih Layanan (PLN, PDAM, BPJS, dll) --</option>
                        @if(isset($pricelist))
                            @foreach($pricelist as $pasca)
                                <option value="{{ $pasca->code }}">{{ $pasca->name }} ({{ strtoupper($pasca->type) }})</option>
                            @endforeach
                        @endif
                    </select>
                    <div class="mt-4 p-4 bg-yellow-50 rounded-xl border border-yellow-200">
                        <p class="text-sm text-yellow-800 flex items-start">
                            <svg class="w-5 h-5 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path></svg>
                            <span>Setelah menekan tombol di bawah, sistem akan mengecek tagihan Anda secara langsung. Harga tagihan belum dipotong/dijumlahkan pada tahap ini.</span>
                        </p>
                    </div>
                </div>

                <hr class="border-gray-200 mb-8">

                <div class="mb-8">
                    <label class="block text-sm font-bold text-gray-700 mb-3">Metode Pembayaran</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                            </svg>
                        </div>
                        <select name="payment_method" id="payment_method" class="focus:ring-blue-500 focus:border-blue-500 block w-full pl-12 pr-10 py-3.5 sm:text-base border-gray-300 rounded-xl bg-gray-50 font-medium text-gray-700 transition-colors duration-200" required>
                            <option value="" disabled selected>-- Pilih Metode Pembayaran --</option>
                            <optgroup label="Sistem Internal">
                                <option value="SALDO">💰 Saldo Akun</option>
                            </optgroup>
                            <optgroup label="E-Wallet & QRIS">
                                <option value="DANA">DANA Otomatis</option>
                            </optgroup>
                        </select>
                    </div>
                </div>

                <!-- WA Pembayaran & PIN (Khusus Metode Saldo) -->
                <div id="saldoFields" class="hidden bg-gray-100 p-5 rounded-xl border border-gray-200 mb-8 space-y-4">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">No. WhatsApp Akun</label>
                        <input type="number" name="wa_pembayaran" class="block w-full py-2 px-3 border-gray-300 rounded-lg" placeholder="Cth: 0812...">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">PIN Keamanan</label>
                        <input type="password" name="pin_pembayaran" class="block w-full py-2 px-3 border-gray-300 rounded-lg" placeholder="Masukkan 6 Digit PIN">
                    </div>
                </div>

                <div class="pt-2">
                    <button type="submit" id="btnSubmit" class="w-full flex justify-center items-center py-4 px-4 border border-transparent rounded-xl shadow-md text-base font-bold text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                        <span id="txtBtn">Bayar Sekarang</span>
                    </button>
                </div>
            </form>
        </div>

    </div>
</div>

<style>
    .custom-scrollbar::-webkit-scrollbar { width: 6px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 10px; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
</style>

<script>
    // --- 1. LOGIKA TAB PRABAYAR / PASCABAYAR ---
    function switchTab(type) {
        const btnPra = document.getElementById('tabPraBtn');
        const btnPasca = document.getElementById('tabPascaBtn');
        const contentPra = document.getElementById('contentPrabayar');
        const contentPasca = document.getElementById('contentPascabayar');
        const inputType = document.getElementById('trx_type');
        const txtBtn = document.getElementById('txtBtn');

        inputType.value = type;

        if(type === 'prabayar') {
            btnPra.className = "flex-1 py-4 text-center font-bold text-blue-600 border-b-2 border-blue-600 bg-blue-50 focus:outline-none transition-colors";
            btnPasca.className = "flex-1 py-4 text-center font-medium text-gray-500 border-b-2 border-transparent hover:text-gray-700 hover:bg-gray-50 focus:outline-none transition-colors";
            contentPra.classList.remove('hidden');
            contentPasca.classList.add('hidden');
            txtBtn.innerText = "Bayar Sekarang";

            // Hapus Required di dropdown pascabayar
            document.getElementById('selectPasca').removeAttribute('required');
        } else {
            btnPasca.className = "flex-1 py-4 text-center font-bold text-blue-600 border-b-2 border-blue-600 bg-blue-50 focus:outline-none transition-colors";
            btnPra.className = "flex-1 py-4 text-center font-medium text-gray-500 border-b-2 border-transparent hover:text-gray-700 hover:bg-gray-50 focus:outline-none transition-colors";
            contentPasca.classList.remove('hidden');
            contentPra.classList.add('hidden');
            txtBtn.innerText = "Cek Tagihan";

            // Tambah Required di dropdown pascabayar
            document.getElementById('selectPasca').setAttribute('required', 'true');
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        // --- 2. LOGIKA PENCARIAN PRODUK ---
        const searchInput = document.getElementById('searchProduct');
        const productCards = document.querySelectorAll('.product-card-item');
        const noResultMsg = document.getElementById('noResultMsg');

        searchInput.addEventListener('input', function (e) {
            const searchTerm = e.target.value.toLowerCase().trim();
            let visibleCount = 0;

            productCards.forEach(card => {
                const searchString = card.getAttribute('data-search');
                if (searchString.includes(searchTerm)) {
                    card.classList.remove('hidden');
                    visibleCount++;
                } else {
                    card.classList.add('hidden');
                }
            });

            if (visibleCount === 0 && productCards.length > 0) {
                noResultMsg.classList.remove('hidden');
            } else {
                noResultMsg.classList.add('hidden');
            }
        });

        // --- 3. LOGIKA MEMUNCULKAN FORM SALDO ---
        const paymentSelect = document.getElementById('payment_method');
        const saldoFields = document.getElementById('saldoFields');

        paymentSelect.addEventListener('change', function() {
            if(this.value === 'SALDO') {
                saldoFields.classList.remove('hidden');
            } else {
                saldoFields.classList.add('hidden');
            }
        });

        // --- 4. LOGIKA SEBELUM SUBMIT (MENGISI FINAL PRODUCT CODE) ---
        const form = document.getElementById('formPpob');
        form.addEventListener('submit', function(e) {
            const type = document.getElementById('trx_type').value;
            const finalCodeInput = document.getElementById('final_product_code');

            if(type === 'prabayar') {
                const selectedRadio = document.querySelector('input[name="temp_code_pra"]:checked');
                if(!selectedRadio) {
                    e.preventDefault();
                    alert("Silakan pilih nominal produk prabayar terlebih dahulu!");
                    return;
                }
                finalCodeInput.value = selectedRadio.value;
            } else {
                const selectedSelect = document.getElementById('selectPasca').value;
                if(!selectedSelect) {
                    e.preventDefault();
                    alert("Silakan pilih layanan pascabayar terlebih dahulu!");
                    return;
                }
                finalCodeInput.value = selectedSelect;
            }
        });
    });
</script>
@endsection
