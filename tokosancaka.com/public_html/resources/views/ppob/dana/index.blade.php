@extends('layouts.marketplace')

@section('content')
<div class="min-h-screen bg-gray-50 py-10 font-sans">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">Pulsa & PPOB</h1>
                <p class="mt-2 text-sm text-gray-500">Beli Pulsa, Data, E-Wallet, Game, dan Bayar Tagihan.</p>
            </div>
            <a href="{{ route('ppob.iak.history') }}" class="p-3 bg-white border border-gray-200 rounded-full shadow-sm hover:bg-gray-50">
                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </a>
        </div>

        @if(session('success'))
        <div class="mb-6 p-4 bg-green-100 border-l-4 border-green-500 text-green-700 rounded-xl shadow-sm flex items-center">
            <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <span class="font-medium">{{ session('success') }}</span>
        </div>
        @endif

        @if(session('error'))
        <div class="mb-6 p-4 bg-red-100 border-l-4 border-red-500 text-red-700 rounded-xl shadow-sm flex items-center">
            <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <span class="font-medium">{{ session('error') }}</span>
        </div>
        @endif

        <form action="{{ route('ppob.iak.store') }}" method="POST" id="formPpob">
            @csrf
            <input type="hidden" name="type" id="trx_type" value="prabayar">
            <input type="hidden" name="product_code" id="final_product_code" value="">

            <!-- TABS MENU -->
            <div class="bg-white p-2 rounded-2xl shadow-sm border border-gray-100 mb-6 flex relative z-0">
                <button type="button" id="tabPraBtn" class="flex-1 py-3 text-center font-bold text-white bg-red-600 rounded-xl transition-colors shadow-md" onclick="switchMainTab('prabayar')">
                    📱 Prabayar
                </button>
                <button type="button" id="tabPascaBtn" class="flex-1 py-3 text-center font-bold text-gray-500 bg-transparent rounded-xl hover:bg-gray-50 transition-colors" onclick="switchMainTab('pascabayar')">
                    🧾 Pascabayar
                </button>
            </div>

            <!-- ==============================================
                 KONTEN PRABAYAR
            =============================================== -->
            <div id="contentPrabayar" class="space-y-6 relative z-10">

                <div class="flex space-x-3 overflow-x-auto pb-2 custom-scrollbar">
                    <button type="button" onclick="switchPraCategory('pulsa')" id="cat_pulsa" class="pra-cat-btn flex-shrink-0 px-5 py-2.5 rounded-full border-2 border-blue-500 bg-blue-500 text-white font-bold transition-all">
                        📱 Pulsa & Data
                    </button>
                    <button type="button" onclick="switchPraCategory('ewallet')" id="cat_ewallet" class="pra-cat-btn flex-shrink-0 px-5 py-2.5 rounded-full border-2 border-gray-200 bg-white text-gray-600 font-bold hover:bg-gray-50 transition-all">
                        💳 E-Wallet
                    </button>
                    <button type="button" onclick="switchPraCategory('pln')" id="cat_pln" class="pra-cat-btn flex-shrink-0 px-5 py-2.5 rounded-full border-2 border-gray-200 bg-white text-gray-600 font-bold hover:bg-gray-50 transition-all">
                        ⚡ Token PLN
                    </button>
                    <button type="button" onclick="switchPraCategory('game')" id="cat_game" class="pra-cat-btn flex-shrink-0 px-5 py-2.5 rounded-full border-2 border-gray-200 bg-white text-gray-600 font-bold hover:bg-gray-50 transition-all">
                        🎮 Topup Game
                    </button>
                </div>

                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                    <div class="mb-6">
                        <label id="label_target" class="block text-sm font-bold text-gray-700 mb-2">Nomor HP</label>
                        <input type="text" name="customer_id_pra" id="customer_id_pra" class="w-full px-4 py-3.5 border border-gray-300 rounded-xl bg-gray-50 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 font-medium text-gray-900" placeholder="Contoh: 081234567890" onkeyup="handleTargetInput()">

                        <div id="operator_badge" class="hidden mt-3 flex items-center p-3 bg-blue-50 rounded-lg border border-blue-100">
                            <span id="op_name" class="font-bold text-blue-700 uppercase tracking-wide">TELKOMSEL</span>
                            <span class="ml-2 text-xs text-blue-500">✔ Nomor Valid</span>
                        </div>

                        <div id="game_selector_wrapper" class="hidden mb-4 mt-4">
                            <select id="game_selector" class="w-full px-4 py-3.5 border border-gray-300 rounded-xl bg-gray-50 focus:ring-2 focus:ring-blue-500 font-medium" onchange="renderProducts()">
                                <option value="">-- Semua Game --</option>
                            </select>
                        </div>
                    </div>

                    <hr class="border-gray-100 mb-6">

                    <!-- DAFTAR PRODUK GRID -->
                    <div>
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-bold text-gray-900">Pilih Produk</h3>
                            <div class="relative w-1/2">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                                </div>
                                <input type="text" id="searchNominal" class="w-full pl-9 pr-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 bg-gray-50" placeholder="Cari nama/nominal..." onkeyup="renderProducts()">
                            </div>
                        </div>

                        <div id="product_grid" class="grid grid-cols-2 md:grid-cols-3 gap-4 min-h-[250px]">
                            <!-- Dihasilkan JS -->
                        </div>

                        <div id="pagination_container" class="flex justify-center items-center space-x-2 mt-8">
                            <!-- Dihasilkan JS -->
                        </div>
                    </div>
                </div>
            </div>

            <!-- ==============================================
                 KONTEN PASCABAYAR
            =============================================== -->
            <div id="contentPascabayar" class="hidden space-y-6 relative z-50">
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">

                    <div class="mb-6 relative">
                        <label class="block text-sm font-bold text-gray-700 mb-2">Pilih Layanan Tagihan</label>

                        <input type="hidden" id="pasca_biller" value="" onchange="handlePascaBiller()">

                        <button type="button" id="customSelectTrigger" class="flex items-center justify-between w-full px-4 py-3.5 border border-gray-300 rounded-xl bg-gray-50 hover:bg-gray-100 focus:ring-2 focus:ring-blue-500 font-medium text-left transition-colors" onclick="toggleCustomSelect()">
                            <span id="customSelectText" class="text-gray-500 truncate pr-4">-- Pilih Layanan (PLN, PDAM, BPJS) --</span>
                            <svg class="w-5 h-5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </button>

                        <div id="customSelectMenu" class="hidden absolute top-full left-0 w-full mt-2 bg-white border border-gray-200 rounded-xl shadow-2xl overflow-hidden z-[100]">
                            <div class="p-3 border-b border-gray-100 bg-gray-50">
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                                    </div>
                                    <input type="text" id="customSelectSearch" class="w-full pl-9 pr-3 py-2.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Cari layanan..." onkeyup="filterCustomSelect()">
                                </div>
                            </div>

                            <ul id="customSelectList" class="max-h-64 overflow-y-auto custom-scrollbar bg-white">
                                <!-- Opsi diisi oleh Javascript -->
                            </ul>
                        </div>
                    </div>

                    <div class="mb-6 relative z-10">
                        <label class="block text-sm font-bold text-gray-700 mb-2">Nomor Pelanggan / ID</label>
                        <input type="text" name="customer_id_pasca" id="customer_id_pasca" class="w-full px-4 py-3.5 border border-gray-300 rounded-xl bg-gray-50 focus:ring-2 focus:ring-blue-500 font-medium" placeholder="Masukkan ID Pelanggan">
                    </div>

                    <div class="p-4 bg-yellow-50 rounded-xl border border-yellow-200 relative z-10">
                        <p class="text-sm text-yellow-800 font-medium">Sistem akan mengecek rincian tagihan Anda ke pusat sebelum Anda melakukan pembayaran.</p>
                    </div>

                </div>
            </div>

            <!-- ==============================================
                 BAGIAN BAWAH (METODE PEMBAYARAN CUSTOM MODAL)
            =============================================== -->
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 mt-6 relative z-0">

                <div class="mb-6">
                    <label class="block text-sm font-bold text-gray-700 mb-3">Pilih Metode Pembayaran</label>
                    <div class="w-full">
                        <button type="button" id="paymentMethodButton" class="flex items-center justify-between w-full border border-gray-300 p-4 rounded-xl cursor-pointer hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors bg-gray-50">
                            <div class="flex items-center">
                                <img id="paymentMethodImg" src="https://placehold.co/32x32/EFEFEF/AAAAAA?text=?" alt="Logo" class="h-6 w-8 object-contain mr-3">
                                <span id="paymentMethodLabel" class="text-sm font-medium text-gray-900">-- Pilih Metode Pembayaran --</span>
                            </div>
                            <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <input type="hidden" name="payment_method" id="payment_method" value="">
                    </div>
                </div>

                <!-- Input Saldo Tersembunyi -->
                <div id="saldoFields" class="hidden bg-red-50 p-4 rounded-xl border border-red-200 mb-6 space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-red-800 mb-1">No. WhatsApp Akun (Pembayar)</label>
                        <input type="number" name="wa_pembayaran" id="wa_pembayaran" class="w-full px-3 py-2 border border-red-300 rounded-lg focus:ring-red-500 focus:border-red-500 bg-white" placeholder="Contoh: 0812...">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-red-800 mb-1">PIN Keamanan Sancaka</label>
                        <input type="password" name="pin_pembayaran" id="pin_pembayaran" class="w-full px-3 py-2 border border-red-300 rounded-lg focus:ring-red-500 focus:border-red-500 bg-white" placeholder="******">
                    </div>
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-bold text-gray-700 mb-2">No. WhatsApp (Opsional)</label>
                    <input type="number" name="whatsapp_number" class="w-full px-4 py-3 border border-gray-300 rounded-xl bg-gray-50" placeholder="Untuk menerima struk transaksi via WA">
                </div>

                <button type="button" id="btnSubmit" onclick="validateAndSubmit()" class="w-full py-4 rounded-xl shadow-lg text-base font-bold text-white bg-blue-600 hover:bg-blue-700 transition-all">
                    Beli Sekarang
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================================== -->
<!-- MODAL: METODE PEMBAYARAN (TAILWIND) -->
<!-- ========================================================== -->
<div id="paymentModal" class="fixed inset-0 bg-gray-900 bg-opacity-75 flex items-center justify-center z-[200] hidden transition-opacity">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-5xl mx-4 transform transition-all flex flex-col max-h-[90vh]">

        <div class="flex justify-between items-center p-5 border-b border-gray-200">
            <h3 class="text-lg font-bold text-gray-900">Pilih Metode Pembayaran</h3>
            <button type="button" id="closeModalButton" class="text-gray-400 hover:text-red-600 bg-gray-100 hover:bg-red-50 p-2 rounded-full transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>

        <div class="p-2 overflow-y-auto custom-scrollbar flex-1">
            <ul id="paymentOptionsList" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 p-4">

                <!-- 1. OPSI INTERNAL (SALDO) -->
                @auth
                <li class="payment-option col-span-full cursor-pointer flex items-center p-3 border rounded-lg hover:bg-red-50 transition-colors"
                    data-value="SALDO" data-label="Saldo Sancaka" data-img="{{ asset('public/assets/saldo.png') }}">
                    <img src="{{ asset('public/assets/saldo.png') }}" class="h-8 w-8 object-contain mr-4" onerror="this.src='https://placehold.co/32x32/EFEFEF/AAAAAA?text=Rp'">
                    <span class="text-sm font-medium text-gray-900">Saldo {{ optional(Auth::user())->nama_lengkap }}: (Rp{{ number_format(optional(Auth::user())->saldo ?? 0, 0, ',', '.') }})</span>
                </li>
                @endauth

                <!-- 2. OPSI KHUSUS (DOKU) -->
                <li class="payment-option col-span-full cursor-pointer flex items-center p-3 border rounded-lg hover:bg-red-50 transition-colors"
                    data-value="DOKU" data-label="Doku (Kartu Kredit, E-Wallet, VA)" data-img="{{ asset('public/assets/doku.png') }}">
                    <img src="{{ asset('public/assets/doku.png') }}" class="h-8 w-8 object-contain mr-4" onerror="this.src='https://placehold.co/32x32/EFEFEF/AAAAAA?text=DK'">
                    <div class="flex flex-col">
                        <span class="text-sm font-bold text-gray-900">DOKU Payment Gateway</span>
                        <span class="text-[11px] text-gray-500 mt-0.5">Semua Pembayaran Tersedia</span>
                    </div>
                </li>

                <!-- 3. DANA ENTERPRISE -->
                <li class="col-span-full px-1 pt-4 pb-1 text-xs font-bold text-gray-500 uppercase tracking-wider border-b border-gray-100">
                    DANA Enterprise
                </li>

                <li class="payment-option col-span-1 cursor-pointer flex items-center p-3 border rounded-lg hover:bg-red-50 transition-colors"
                    data-value="DANA" data-label="DANA (Web Checkout)" data-img="{{ asset('public/assets/dana.webp') }}">
                    <img src="{{ asset('public/assets/dana.webp') }}" alt="DANA" class="h-8 w-8 object-contain mr-4" onerror="this.src='https://upload.wikimedia.org/wikipedia/commons/7/72/Logo_dana_blue.svg'">
                    <div class="flex flex-col">
                        <span class="text-sm font-bold text-gray-900">DANA Checkout</span>
                        <span class="text-[11px] text-gray-500 mt-0.5">Diarahkan ke aplikasi DANA</span>
                    </div>
                </li>

                <!-- Opsi tambahan bisa ditambahkan di sini secara statis atau dinamis -->
            </ul>
        </div>
    </div>
</div>

<style>
    .custom-scrollbar::-webkit-scrollbar { width: 6px; height: 6px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 10px; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
    .custom-option { transition: background-color 0.2s ease, color 0.2s ease; }
</style>

<script>
    const dbPrepaid = {!! json_encode($pricelistPrepaid ?? []) !!};
    const dbPostpaid = {!! json_encode($pricelist ?? []) !!};

    const prefixes = {
        'INDOSAT': { codes: ['0814','0815','0816','0855','0856','0857','0858'], color: '#f59e0b' },
        'XL': { codes: ['0817','0818','0819','0859','0878','0877'], color: '#3b82f6' },
        'AXIS': { codes: ['0838','0837','0831','0832'], color: '#8b5cf6' },
        'TELKOMSEL': { codes: ['0812','0813','0852','0853','0821','0823','0822','0851'], color: '#dc2626' },
        'SMARTFREN': { codes: ['0881','0882','0883','0884','0885','0886','0887','0888'], color: '#0ea5e9' },
        'THREE': { codes: ['0896','0897','0898','0899','0895'], color: '#1f2937' },
        'by.U': { codes: ['085154','085155','085156','085157','085158'], color: '#3b82f6' }
    };

    let activeMainTab = 'prabayar';
    let activePraCat = 'pulsa';
    let detectedOp = '';

    // Variabel Pagination
    let currentPage = 1;
    const itemsPerPage = 12;
    let currentFilteredData = [];

    document.addEventListener('DOMContentLoaded', () => {
        populatePostpaidDropdown();
        populateGameDropdown();
        renderProducts();
    });

    // ==========================================
    // SCRIPT MODAL PEMBAYARAN
    // ==========================================
    const paymentModal = document.getElementById('paymentModal');
    const paymentMethodButton = document.getElementById('paymentMethodButton');
    const closeModalButton = document.getElementById('closeModalButton');
    const paymentOptionsList = document.getElementById('paymentOptionsList');
    const paymentMethodInput = document.getElementById('payment_method');

    function openPaymentModal() {
        paymentModal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function closePaymentModal() {
        paymentModal.classList.add('hidden');
        document.body.style.overflow = 'auto';
    }

    paymentMethodButton.addEventListener('click', openPaymentModal);
    closeModalButton.addEventListener('click', closePaymentModal);

    paymentModal.addEventListener('click', function(e) {
        if (e.target === paymentModal) {
            closePaymentModal();
        }
    });

    paymentOptionsList.querySelectorAll('.payment-option').forEach(item => {
        item.addEventListener('click', function () {
            const paymentValue = this.dataset.value;
            paymentMethodInput.value = paymentValue;

            paymentOptionsList.querySelectorAll('.payment-option').forEach(li => li.classList.remove('bg-red-50', 'border-red-500'));
            this.classList.add('bg-red-50', 'border-red-500');

            document.getElementById('paymentMethodLabel').textContent = this.dataset.label;
            document.getElementById('paymentMethodImg').src = this.dataset.img;

            toggleSaldoFields();
            closePaymentModal();
        });
    });

    function toggleSaldoFields() {
        const method = paymentMethodInput.value;
        const sFields = document.getElementById('saldoFields');
        const wa = document.getElementById('wa_pembayaran');
        const pin = document.getElementById('pin_pembayaran');

        if(method === 'SALDO') {
            sFields.classList.remove('hidden');
            wa.required = true;
            pin.required = true;
        } else {
            sFields.classList.add('hidden');
            wa.required = false;
            pin.required = false;
        }
    }

    // ==========================================
    // MAPPING LOGO PPOB
    // ==========================================
    function getLogoUrl(operatorName) {
        if(!operatorName) return 'https://ui-avatars.com/api/?name=PPOB&background=4f46e5&color=fff';
        const op = operatorName.toLowerCase();
        let filename = '';

        if(op.includes('telkomsel')) filename = 'telkomsel.png';
        else if(op.includes('indosat') || op.includes('isat')) filename = 'indosat.png';
        else if(op.includes('xl')) filename = 'xl.png';
        else if(op.includes('axis')) filename = 'axis.png';
        else if(op.includes('tri') || op.includes('three')) filename = 'tri.png';
        else if(op.includes('smartfren') || op.includes('smart')) filename = 'smartfren.png';
        else if(op.includes('by.u') || op.includes('byu')) filename = 'by.u.png';
        else if(op.includes('pln')) filename = 'pln.png';
        else if(op.includes('ovo')) filename = 'ovo.png';
        else if(op.includes('dana')) filename = 'dana.png';
        else if(op.includes('gopay') || op.includes('go pay')) filename = 'go pay.png';
        else if(op.includes('shopee') || op.includes('shopeepay')) filename = 'shopee pay.png';
        else if(op.includes('mobile legend') || op.includes('mlbb')) filename = 'mobile legends.png';
        else if(op.includes('free fire')) filename = 'free fire.png';
        else if(op.includes('k-vision') || op.includes('kvision')) filename = 'k-vision dan gol.png';
        else if(op.includes('halo')) filename = 'halo.png';
        else if(op.includes('baf')) filename = 'baf.png';
        else if(op.includes('kredit bni') || op.includes('bni')) filename = 'kredit_bni.png';
        else if(op.includes('pertamina') || op.includes('gas')) filename = 'pertamina gas.png';
        else if(op.includes('bpjs')) filename = 'bpjs.png';

        if(filename) {
            return `https://tokosancaka.com/storage/logo-ppob/${filename}`;
        }

        return `https://ui-avatars.com/api/?name=${encodeURIComponent(operatorName)}&background=f1f5f9&color=64748b&rounded=true&bold=true`;
    }

    // ==========================================
    // LOGIKA CUSTOM SELECT (PASCABAYAR)
    // ==========================================
    function toggleCustomSelect() {
        const menu = document.getElementById('customSelectMenu');
        menu.classList.toggle('hidden');
        if(!menu.classList.contains('hidden')) {
            document.getElementById('customSelectSearch').focus();
        }
    }

    function selectPascaOption(code, label) {
        document.getElementById('pasca_biller').value = code;
        document.getElementById('customSelectText').innerText = label;
        document.getElementById('customSelectText').classList.remove('text-gray-500');
        document.getElementById('customSelectText').classList.add('text-gray-900', 'font-bold');
        document.getElementById('customSelectMenu').classList.add('hidden');
        handlePascaBiller();
    }

    function filterCustomSelect() {
        let filter = document.getElementById('customSelectSearch').value.toLowerCase();
        let options = document.querySelectorAll('.custom-option');
        options.forEach(opt => {
            let text = opt.innerText.toLowerCase();
            if (text.includes(filter)) {
                opt.style.display = 'block';
            } else {
                opt.style.display = 'none';
            }
        });
    }

    document.addEventListener('click', function(event) {
        const menu = document.getElementById('customSelectMenu');
        const trigger = document.getElementById('customSelectTrigger');
        if (!trigger.contains(event.target) && !menu.contains(event.target)) {
            menu.classList.add('hidden');
        }
    });

    function populatePostpaidDropdown() {
        const list = document.getElementById('customSelectList');
        list.innerHTML = '';

        dbPostpaid.forEach(p => {
            let catName = p.category ? p.category.toUpperCase() : (p.type ? p.type.toUpperCase() : 'TAGIHAN');
            let label = `${p.name} - ${catName}`;

            let li = document.createElement('li');
            li.className = 'custom-option px-4 py-3 hover:bg-blue-50 cursor-pointer text-sm text-gray-700 border-b border-gray-50 transition-colors';
            li.innerHTML = label;
            li.onclick = () => selectPascaOption(p.code, label);

            list.appendChild(li);
        });
    }

    // ==========================================
    // LOGIKA TAB & PRABAYAR STANDAR
    // ==========================================
    function switchMainTab(tab) {
        activeMainTab = tab;
        document.getElementById('trx_type').value = tab;

        const btnPra = document.getElementById('tabPraBtn');
        const btnPasca = document.getElementById('tabPascaBtn');
        const contentPra = document.getElementById('contentPrabayar');
        const contentPasca = document.getElementById('contentPascabayar');
        const btnSubmit = document.getElementById('btnSubmit');

        if(tab === 'prabayar') {
            btnPra.className = "flex-1 py-3 text-center font-bold text-white bg-red-600 rounded-xl transition-colors shadow-md";
            btnPasca.className = "flex-1 py-3 text-center font-bold text-gray-500 bg-transparent rounded-xl hover:bg-gray-50 transition-colors";
            contentPra.classList.remove('hidden');
            contentPasca.classList.add('hidden');
            btnSubmit.innerText = "Beli Sekarang";
            btnSubmit.className = "w-full py-4 rounded-xl shadow-lg text-base font-bold text-white bg-blue-600 hover:bg-blue-700 transition-all";
        } else {
            btnPasca.className = "flex-1 py-3 text-center font-bold text-white bg-red-600 rounded-xl transition-colors shadow-md";
            btnPra.className = "flex-1 py-3 text-center font-bold text-gray-500 bg-transparent rounded-xl hover:bg-gray-50 transition-colors";
            contentPasca.classList.remove('hidden');
            contentPra.classList.add('hidden');
            btnSubmit.innerText = "Cek Tagihan";
            btnSubmit.className = "w-full py-4 rounded-xl shadow-lg text-base font-bold text-white bg-green-600 hover:bg-green-700 transition-all";
        }
    }

    function switchPraCategory(cat) {
        activePraCat = cat;

        document.querySelectorAll('.pra-cat-btn').forEach(el => {
            el.className = "pra-cat-btn flex-shrink-0 px-5 py-2.5 rounded-full border-2 border-gray-200 bg-white text-gray-600 font-bold hover:bg-gray-50 transition-all";
        });

        let activeColor = 'bg-blue-500 border-blue-500';
        if(cat === 'ewallet') activeColor = 'bg-purple-500 border-purple-500';
        if(cat === 'pln') activeColor = 'bg-yellow-500 border-yellow-500';
        if(cat === 'game') activeColor = 'bg-green-500 border-green-500';

        document.getElementById('cat_'+cat).className = `pra-cat-btn flex-shrink-0 px-5 py-2.5 rounded-full border-2 text-white font-bold transition-all ${activeColor}`;

        document.getElementById('customer_id_pra').value = '';
        document.getElementById('operator_badge').classList.add('hidden');
        document.getElementById('game_selector_wrapper').classList.add('hidden');
        detectedOp = '';

        const labelTarget = document.getElementById('label_target');
        if(cat === 'pulsa') labelTarget.innerText = "Nomor HP / Tujuan";
        if(cat === 'ewallet') labelTarget.innerText = "Nomor HP E-Wallet (OVO, DANA, dll)";
        if(cat === 'pln') labelTarget.innerText = "Nomor Meter / ID Pelanggan PLN";
        if(cat === 'game') {
            labelTarget.innerText = "Player ID / User ID Game";
            document.getElementById('game_selector_wrapper').classList.remove('hidden');
        }

        renderProducts();
    }

    function handleTargetInput() {
        let number = document.getElementById('customer_id_pra').value.replace(/[^0-9]/g, '');

        if (activePraCat === 'pulsa') {
            if (number.length >= 4) {
                let foundOp = '';
                let foundColor = '';

                if (number.length >= 6) {
                    let prefix6 = number.substring(0, 6);
                    if (prefixes['by.U'].codes.includes(prefix6)) { foundOp = 'by.U'; foundColor = prefixes['by.U'].color; }
                }

                if (!foundOp) {
                    let prefix4 = number.substring(0, 4);
                    for (const [op, data] of Object.entries(prefixes)) {
                        if (op !== 'by.U' && data.codes.includes(prefix4)) {
                            foundOp = op; foundColor = data.color; break;
                        }
                    }
                }

                if (foundOp) {
                    detectedOp = foundOp;
                    document.getElementById('operator_badge').classList.remove('hidden');
                    document.getElementById('op_name').innerText = foundOp;
                    document.getElementById('op_name').style.color = foundColor;
                } else {
                    detectedOp = '';
                    document.getElementById('operator_badge').classList.add('hidden');
                }
            } else {
                detectedOp = '';
                document.getElementById('operator_badge').classList.add('hidden');
            }
        }
        renderProducts();
    }

    // ==========================================
    // RENDER PRODUK DENGAN PAGINATION JS
    // ==========================================
    function renderProducts() {
        const searchKeyword = document.getElementById('searchNominal').value.toLowerCase();
        let filtered = [];

        if(activePraCat === 'pulsa') {
            let searchOp = detectedOp ? detectedOp.toLowerCase() : '';
            if (searchOp === 'smartfren') searchOp = 'smart';
            if (searchOp === 'three') searchOp = 'three';

            filtered = dbPrepaid.filter(p => {
                if (!p.type || !p.operator) return false;
                let t = p.type.toLowerCase();
                let o = p.operator.toLowerCase();

                let isPulsaData = t.includes('pulsa') || t.includes('data');
                if(!searchOp) return isPulsaData;

                let isMatchOp = o.includes(searchOp) || (searchOp === 'three' && o.includes('tri'));
                return isPulsaData && isMatchOp;
            });
        }
        else if(activePraCat === 'ewallet') {
            filtered = dbPrepaid.filter(p => {
                if (!p.type) return false;
                let t = p.type.toLowerCase();
                return t.includes('emoney') || t.includes('ewallet') || t.includes('etoll') || t.includes('saldo');
            });
        }
        else if(activePraCat === 'pln') {
            filtered = dbPrepaid.filter(p => p.operator && p.operator.toLowerCase() === 'pln');
        }
        else if(activePraCat === 'game') {
            let selectedGame = document.getElementById('game_selector').value;
            filtered = dbPrepaid.filter(p => {
                if (!p.type || !p.type.toLowerCase().includes('game')) return false;
                if(selectedGame) return p.operator === selectedGame;
                return true;
            });
        }

        if(searchKeyword) {
            filtered = filtered.filter(p =>
                (p.description && p.description.toLowerCase().includes(searchKeyword)) ||
                (p.price && p.price.toString().includes(searchKeyword)) ||
                (p.operator && p.operator.toLowerCase().includes(searchKeyword))
            );
        }

        currentFilteredData = filtered;
        displayPage(1);
    }

    function displayPage(page) {
        currentPage = page;
        const grid = document.getElementById('product_grid');
        grid.innerHTML = '';

        if(currentFilteredData.length === 0) {
            grid.innerHTML = `<div class="col-span-full py-8 text-center text-gray-400 font-medium border-2 border-dashed border-gray-200 rounded-xl">Produk tidak ditemukan.</div>`;
            renderPagination(0, 0);
            return;
        }

        const totalPages = Math.ceil(currentFilteredData.length / itemsPerPage);
        const start = (page - 1) * itemsPerPage;
        const end = start + itemsPerPage;
        const paginatedItems = currentFilteredData.slice(start, end);

        paginatedItems.forEach(p => {
            let priceFormat = new Intl.NumberFormat('id-ID').format(p.price || 0);
            let logoUrl = getLogoUrl(p.operator);

            let html = `
                <label class="cursor-pointer h-full relative group">
                    <input type="radio" name="temp_code_pra" value="${p.code}" class="peer sr-only">
                    <div class="h-full rounded-xl border-2 border-gray-100 bg-white p-4 hover:border-blue-300 peer-checked:border-blue-600 peer-checked:bg-blue-50 peer-checked:shadow flex flex-col justify-between transition-all">
                        <div class="mb-3 flex items-start space-x-3">
                            <div class="w-10 h-10 flex-shrink-0 bg-white rounded-full p-1 border border-gray-100 shadow-sm flex items-center justify-center">
                                <img src="${logoUrl}" alt="logo" class="w-full h-full object-contain rounded-full" onerror="this.src='https://ui-avatars.com/api/?name=${encodeURIComponent(p.operator)}&background=f1f5f9&color=64748b&rounded=true&bold=true'">
                            </div>
                            <div class="flex-1">
                                <p class="text-xs text-gray-500 capitalize mb-0.5">${p.operator}</p>
                                <p class="text-sm font-bold text-gray-900 leading-tight">${p.description}</p>
                            </div>
                        </div>
                        <p class="text-base font-black text-blue-600 border-t border-gray-100 pt-2">Rp ${priceFormat}</p>

                        <div class="absolute top-2 right-2 hidden peer-checked:block text-blue-600 bg-white rounded-full">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                        </div>
                    </div>
                </label>
            `;
            grid.innerHTML += html;
        });

        renderPagination(page, totalPages);
    }

    function renderPagination(page, totalPages) {
        const container = document.getElementById('pagination_container');
        container.innerHTML = '';

        if(totalPages <= 1) return;

        let html = '';

        if (page > 1) {
            html += `<button type="button" onclick="displayPage(${page - 1})" class="px-4 py-2 rounded-lg bg-white border border-gray-300 text-gray-700 font-medium hover:bg-gray-50 transition-colors shadow-sm">« Prev</button>`;
        } else {
            html += `<button type="button" disabled class="px-4 py-2 rounded-lg bg-gray-100 border border-gray-200 text-gray-400 font-medium cursor-not-allowed">« Prev</button>`;
        }

        html += `<div class="px-4 py-2 text-sm font-bold text-gray-700">Halaman ${page} dari ${totalPages}</div>`;

        if (page < totalPages) {
            html += `<button type="button" onclick="displayPage(${page + 1})" class="px-4 py-2 rounded-lg bg-white border border-gray-300 text-gray-700 font-medium hover:bg-gray-50 transition-colors shadow-sm">Next »</button>`;
        } else {
            html += `<button type="button" disabled class="px-4 py-2 rounded-lg bg-gray-100 border border-gray-200 text-gray-400 font-medium cursor-not-allowed">Next »</button>`;
        }

        container.innerHTML = html;
    }

    function populateGameDropdown() {
        const gameSelect = document.getElementById('game_selector');
        let games = [...new Set(dbPrepaid.filter(p => p.type && p.type.toLowerCase().includes('game')).map(item => item.operator))];
        games.sort().forEach(g => {
            if(!g) return;
            let opt = document.createElement('option');
            opt.value = g;
            opt.innerHTML = g;
            gameSelect.appendChild(opt);
        });
    }

    function handlePascaBiller() {
        document.getElementById('final_product_code').value = document.getElementById('pasca_biller').value;
    }

    function validateAndSubmit() {
        const form = document.getElementById('formPpob');
        const finalCodeInput = document.getElementById('final_product_code');

        if(activeMainTab === 'prabayar') {
            let custId = document.getElementById('customer_id_pra').value;
            if(!custId) return alert("Silakan masukkan Nomor Target/ID Tujuan!");

            let selectedProduct = document.querySelector('input[name="temp_code_pra"]:checked');
            if(!selectedProduct) return alert("Pilih produk prabayar terlebih dahulu!");

            finalCodeInput.value = selectedProduct.value;

            let hiddenCust = document.createElement('input');
            hiddenCust.type = 'hidden';
            hiddenCust.name = 'customer_id';
            hiddenCust.value = custId;
            form.appendChild(hiddenCust);

        } else {
            let custIdPasca = document.getElementById('customer_id_pasca').value;
            let pascaBiller = document.getElementById('pasca_biller').value;

            if(!pascaBiller) return alert("Pilih Layanan Tagihan terlebih dahulu!");
            if(!custIdPasca) return alert("Masukkan ID Pelanggan Tagihan Anda!");

            finalCodeInput.value = pascaBiller;

            let hiddenCust = document.createElement('input');
            hiddenCust.type = 'hidden';
            hiddenCust.name = 'customer_id';
            hiddenCust.value = custIdPasca;
            form.appendChild(hiddenCust);
        }

        if (!paymentMethodInput.value) {
            return alert("Pilih metode pembayaran terlebih dahulu!");
        }

        form.submit();
    }

</script>
@endsection
