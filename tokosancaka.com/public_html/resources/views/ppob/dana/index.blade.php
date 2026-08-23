@extends('layouts.marketplace')

@section('content')
<div class="min-h-screen bg-gray-50 py-10 font-sans">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">Pulsa & PPOB</h1>
                <p class="mt-2 text-sm text-gray-500">Beli Pulsa, Data, E-Wallet, Game, dan Bayar Tagihan.</p>
            </div>
            <a href="{{ route('ppob.history') ?? '#' }}" class="p-3 bg-white border border-gray-200 rounded-full shadow-sm hover:bg-gray-50">
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

        <!-- MAIN FORM -->
        <form action="{{ route('ppob.store') ?? '#' }}" method="POST" id="formPpob">
            @csrf
            <input type="hidden" name="type" id="trx_type" value="prabayar">
            <input type="hidden" name="product_code" id="final_product_code" value="">

            <!-- TABS MENU -->
            <div class="bg-white p-2 rounded-2xl shadow-sm border border-gray-100 mb-6 flex">
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
            <div id="contentPrabayar" class="space-y-6">

                <!-- KATEGORI SCROLL (PULSA, PLN, OVO, GAME) -->
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

                    <!-- INPUT TARGET (Dinamis berubah sesuai kategori) -->
                    <div class="mb-6">
                        <label id="label_target" class="block text-sm font-bold text-gray-700 mb-2">Nomor HP</label>
                        <input type="text" name="customer_id" id="customer_id_pra" class="w-full px-4 py-3.5 border border-gray-300 rounded-xl bg-gray-50 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 font-medium text-gray-900" placeholder="Contoh: 081234567890" onkeyup="handleTargetInput()">

                        <!-- Operator Badge -->
                        <div id="operator_badge" class="hidden mt-3 flex items-center p-3 bg-blue-50 rounded-lg border border-blue-100">
                            <span id="op_name" class="font-bold text-blue-700 uppercase tracking-wide">TELKOMSEL</span>
                            <span class="ml-2 text-xs text-blue-500">✔ Nomor Valid</span>
                        </div>

                        <!-- Dropdown Game (Muncul kalau tab game dipilih) -->
                        <div id="game_selector_wrapper" class="hidden mb-4">
                            <select id="game_selector" class="w-full px-4 py-3.5 border border-gray-300 rounded-xl bg-gray-50 focus:ring-2 focus:ring-blue-500 font-medium" onchange="renderProducts()">
                                <option value="">-- Pilih Game --</option>
                            </select>
                        </div>
                    </div>

                    <hr class="border-gray-100 mb-6">

                    <!-- DAFTAR PRODUK (Dihasilkan oleh Javascript) -->
                    <div>
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-bold text-gray-900">Pilih Nominal</h3>
                            <input type="text" id="searchNominal" class="w-1/2 px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-blue-500" placeholder="Cari nominal..." onkeyup="renderProducts()">
                        </div>

                        <div id="product_grid" class="grid grid-cols-2 md:grid-cols-3 gap-4 max-h-[400px] overflow-y-auto pr-2 custom-scrollbar">
                            <!-- Produk akan dirender di sini via JS -->
                            <div class="col-span-full py-8 text-center text-gray-400 font-medium border-2 border-dashed border-gray-200 rounded-xl">
                                Masukkan nomor tujuan untuk memunculkan produk.
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- ==============================================
                 KONTEN PASCABAYAR
            =============================================== -->
            <div id="contentPascabayar" class="hidden space-y-6">
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">

                    <div class="mb-6">
                        <label class="block text-sm font-bold text-gray-700 mb-2">Pilih Layanan Tagihan</label>
                        <select id="pasca_biller" class="w-full px-4 py-3.5 border border-gray-300 rounded-xl bg-gray-50 focus:ring-2 focus:ring-blue-500 font-medium" onchange="handlePascaBiller()">
                            <option value="" disabled selected>-- Pilih Layanan (PLN, PDAM, BPJS) --</option>
                            <!-- Diisi oleh JS -->
                        </select>
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm font-bold text-gray-700 mb-2">Nomor Pelanggan / ID</label>
                        <input type="text" name="customer_id_pasca" id="customer_id_pasca" class="w-full px-4 py-3.5 border border-gray-300 rounded-xl bg-gray-50 focus:ring-2 focus:ring-blue-500 font-medium" placeholder="Masukkan ID Pelanggan">
                    </div>

                    <!-- Input Tambahan (Dinamis jika butuh bulan/tahun dsb, disiapkan hidden) -->
                    <div class="p-4 bg-yellow-50 rounded-xl border border-yellow-200">
                        <p class="text-sm text-yellow-800 font-medium">Sistem akan mengecek rincian tagihan Anda ke pusat sebelum Anda melakukan pembayaran.</p>
                    </div>

                </div>
            </div>

            <!-- ==============================================
                 BAGIAN BAWAH (METODE PEMBAYARAN & SUBMIT)
            =============================================== -->
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 mt-6">

                <div class="mb-6">
                    <label class="block text-sm font-bold text-gray-700 mb-3">Metode Pembayaran</label>
                    <select name="payment_method" id="payment_method" class="w-full px-4 py-3.5 border border-gray-300 rounded-xl bg-gray-50 focus:ring-blue-500 font-bold text-gray-800" required onchange="toggleSaldoFields()">
                        <option value="" disabled selected>-- Pilih Metode --</option>
                        <option value="SALDO">💰 Potong Saldo Sancaka</option>
                        <option value="DANA">🔵 DANA (E-Wallet)</option>
                        <option value="DOKU">🛡️ DOKU Payment Gateway</option>
                    </select>
                </div>

                <!-- Input Saldo Tersembunyi -->
                <div id="saldoFields" class="hidden bg-gray-50 p-4 rounded-xl border border-gray-200 mb-6 space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">No. WhatsApp Akun (Pembayar)</label>
                        <input type="number" name="wa_pembayaran" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">PIN Keamanan Sancaka</label>
                        <input type="password" name="pin_pembayaran" class="w-full px-3 py-2 border border-gray-300 rounded-lg" placeholder="******">
                    </div>
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-bold text-gray-700 mb-2">No. WhatsApp (Opsional)</label>
                    <input type="number" name="whatsapp_number" class="w-full px-4 py-3 border border-gray-300 rounded-xl bg-gray-50" placeholder="Untuk menerima struk transaksi">
                </div>

                <button type="button" id="btnSubmit" onclick="validateAndSubmit()" class="w-full py-4 rounded-xl shadow-lg text-base font-bold text-white bg-blue-600 hover:bg-blue-700 transition-all">
                    Beli Sekarang
                </button>
            </div>
        </form>
    </div>
</div>

<style>
    .custom-scrollbar::-webkit-scrollbar { width: 6px; height: 6px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 10px; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
</style>

<!-- JSON DATA INJECTION DARI CONTROLLER -->
<script>
    // Data langsung dari Laravel Controller
    const dbPrepaid = @json($pricelistPrepaid ?? []);
    const dbPostpaid = @json($pricelist ?? []);

    // Prefix Nomor HP (Sama persis seperti React Native Anda)
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

    // ==========================================
    // INIT PADA SAAT HALAMAN DIMUAT
    // ==========================================
    document.addEventListener('DOMContentLoaded', () => {
        populatePostpaidDropdown();
        populateGameDropdown();
    });

    // ==========================================
    // LOGIKA TABS (PRABAYAR / PASCABAYAR)
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

    // ==========================================
    // LOGIKA KATEGORI PRABAYAR
    // ==========================================
    function switchPraCategory(cat) {
        activePraCat = cat;

        // Reset warna tombol
        document.querySelectorAll('.pra-cat-btn').forEach(el => {
            el.className = "pra-cat-btn flex-shrink-0 px-5 py-2.5 rounded-full border-2 border-gray-200 bg-white text-gray-600 font-bold hover:bg-gray-50 transition-all";
        });

        // Warnai tombol yang aktif
        let activeColor = 'bg-blue-500 border-blue-500';
        if(cat === 'ewallet') activeColor = 'bg-purple-500 border-purple-500';
        if(cat === 'pln') activeColor = 'bg-yellow-500 border-yellow-500';
        if(cat === 'game') activeColor = 'bg-green-500 border-green-500';

        document.getElementById('cat_'+cat).className = `pra-cat-btn flex-shrink-0 px-5 py-2.5 rounded-full border-2 text-white font-bold transition-all ${activeColor}`;

        // Reset Input Target
        document.getElementById('customer_id_pra').value = '';
        document.getElementById('operator_badge').classList.add('hidden');
        document.getElementById('game_selector_wrapper').classList.add('hidden');
        detectedOp = '';

        // Ubah Label Input
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

    // ==========================================
    // DETEKSI PREFIX OTOMATIS (MURNI JS)
    // ==========================================
    function handleTargetInput() {
        let number = document.getElementById('customer_id_pra').value.replace(/[^0-9]/g, '');

        if (activePraCat === 'pulsa') {
            if (number.length >= 4) {
                let foundOp = '';
                let foundColor = '';

                // Cek by.U khusus (6 digit)
                if (number.length >= 6) {
                    let prefix6 = number.substring(0, 6);
                    if (prefixes['by.U'].codes.includes(prefix6)) { foundOp = 'by.U'; foundColor = prefixes['by.U'].color; }
                }

                // Cek 4 Digit Umum
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
    // RENDER GRID PRODUK PRABAYAR LOKAL
    // ==========================================
    function renderProducts() {
        const grid = document.getElementById('product_grid');
        const searchKeyword = document.getElementById('searchNominal').value.toLowerCase();
        let targetNum = document.getElementById('customer_id_pra').value;

        // Kosongkan grid
        grid.innerHTML = '';

        // Tentukan aturan filter berdasar tab yang aktif
        let filtered = [];

        if(activePraCat === 'pulsa') {
            if(!detectedOp) {
                grid.innerHTML = `<div class="col-span-full py-8 text-center text-gray-400 font-medium border-2 border-dashed border-gray-200 rounded-xl">Masukkan minimal 4 digit nomor HP untuk memunculkan produk.</div>`;
                return;
            }
            // Tampilkan produk pulsa/data yang operatornya sesuai
            filtered = dbPrepaid.filter(p =>
                ['pulsa', 'data'].includes(p.type.toLowerCase()) &&
                p.operator.toLowerCase().includes(detectedOp.toLowerCase())
            );
        }
        else if(activePraCat === 'ewallet') {
            filtered = dbPrepaid.filter(p => ['ewallet', 'emoney'].includes(p.type.toLowerCase()));
            if(targetNum === '') {
                 grid.innerHTML = `<div class="col-span-full py-8 text-center text-gray-400 font-medium border-2 border-dashed border-gray-200 rounded-xl">Isi nomor HP OVO/DANA dulu.</div>`;
                 return;
            }
        }
        else if(activePraCat === 'pln') {
            filtered = dbPrepaid.filter(p => p.operator.toLowerCase() === 'pln');
            if(targetNum === '') {
                 grid.innerHTML = `<div class="col-span-full py-8 text-center text-gray-400 font-medium border-2 border-dashed border-gray-200 rounded-xl">Isi ID Pelanggan PLN dulu.</div>`;
                 return;
            }
        }
        else if(activePraCat === 'game') {
            let selectedGame = document.getElementById('game_selector').value;
            if(!selectedGame) {
                 grid.innerHTML = `<div class="col-span-full py-8 text-center text-gray-400 font-medium border-2 border-dashed border-gray-200 rounded-xl">Pilih nama game terlebih dahulu.</div>`;
                 return;
            }
            filtered = dbPrepaid.filter(p => p.operator === selectedGame);
        }

        // Terapkan Pencarian Kotak Search
        if(searchKeyword) {
            filtered = filtered.filter(p =>
                p.description.toLowerCase().includes(searchKeyword) ||
                p.price.toString().includes(searchKeyword)
            );
        }

        if(filtered.length === 0) {
            grid.innerHTML = `<div class="col-span-full py-8 text-center text-gray-400 font-medium border-2 border-dashed border-gray-200 rounded-xl">Produk sedang tidak tersedia.</div>`;
            return;
        }

        // Generate HTML Card
        filtered.forEach(p => {
            let priceFormat = new Intl.NumberFormat('id-ID').format(p.price);
            let html = `
                <label class="cursor-pointer h-full">
                    <input type="radio" name="temp_code_pra" value="${p.code}" class="peer sr-only">
                    <div class="h-full rounded-xl border-2 border-gray-100 bg-white p-4 hover:border-blue-300 peer-checked:border-blue-600 peer-checked:bg-blue-50 peer-checked:shadow flex flex-col justify-between transition-all">
                        <div class="mb-2">
                            <p class="text-sm font-bold text-gray-900 leading-tight">${p.description}</p>
                            <p class="text-xs text-gray-500 mt-1 capitalize">${p.operator}</p>
                        </div>
                        <p class="text-base font-black text-blue-600 border-t border-gray-100 pt-2">Rp ${priceFormat}</p>
                    </div>
                </label>
            `;
            grid.innerHTML += html;
        });
    }

    // ==========================================
    // DROPDOWNS SETUP
    // ==========================================
    function populateGameDropdown() {
        const gameSelect = document.getElementById('game_selector');
        // Cari unique operator yang typenya game
        let games = [...new Set(dbPrepaid.filter(p => p.type.toLowerCase() === 'game').map(item => item.operator))];
        games.sort().forEach(g => {
            let opt = document.createElement('option');
            opt.value = g;
            opt.innerHTML = g;
            gameSelect.appendChild(opt);
        });
    }

    function populatePostpaidDropdown() {
        const pascaSelect = document.getElementById('pasca_biller');
        dbPostpaid.forEach(p => {
            let opt = document.createElement('option');
            opt.value = p.code;
            opt.innerHTML = `${p.name} (${p.category.toUpperCase()})`;
            pascaSelect.appendChild(opt);
        });
    }

    function handlePascaBiller() {
        document.getElementById('final_product_code').value = document.getElementById('pasca_biller').value;
    }

    // ==========================================
    // TOGGLE SALDO (WA & PIN)
    // ==========================================
    function toggleSaldoFields() {
        const method = document.getElementById('payment_method').value;
        const sFields = document.getElementById('saldoFields');
        if(method === 'SALDO') {
            sFields.classList.remove('hidden');
        } else {
            sFields.classList.add('hidden');
        }
    }

    // ==========================================
    // VALIDASI SEBELUM SUBMIT
    // ==========================================
    function validateAndSubmit() {
        const form = document.getElementById('formPpob');
        const finalCodeInput = document.getElementById('final_product_code');

        if(activeMainTab === 'prabayar') {
            let custId = document.getElementById('customer_id_pra').value;
            if(!custId) return alert("Silakan masukkan Nomor HP/ID Tujuan!");

            let selectedProduct = document.querySelector('input[name="temp_code_pra"]:checked');
            if(!selectedProduct) return alert("Pilih nominal produk prabayar terlebih dahulu!");

            finalCodeInput.value = selectedProduct.value;

            // Pindahkan customer_id karena name di input asli kita bedakan id-nya
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

        form.submit();
    }

</script>
@endsection
