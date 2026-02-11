function posSystem() {
    return {
        activeCategory: 'all', // Pindah ke sini agar reaktif

        init() {
            // 1. Logic Kupon (Tetap)
            if(this.couponCode) {
                this.couponMessage = 'Kupon terdeteksi! Masukkan barang untuk cek diskon.';
            }

            // 2. Logic Watcher (Tetap)
            this.$watch('cart', () => { this.updateCartTotals(); });

            // 3. [PERBAIKAN] TUNGGU ECHO DENGAN INTERVAL
            // Kita cek setiap 500ms (setengah detik) apakah Echo sudah siap
            let checkCount = 0;
            const checkEcho = setInterval(() => {
                if (typeof window.Echo !== 'undefined') {
                    // BERHASIL: Echo sudah ada, matikan pengecekan & jalankan listener
                    clearInterval(checkEcho);
                    console.log("✅ Echo ditemukan. Mengaktifkan Listener...");
                    this.listenForRemoteScan();
                } else {
                    // BELUM ADA: Tunggu lagi...
                    checkCount++;
                    console.log(`⏳ Menunggu Library Echo... (${checkCount})`);

                    // Jika sudah 20x (10 detik) masih gagal, baru menyerah
                    if(checkCount > 20) {
                        clearInterval(checkEcho);
                        console.error("❌ Gagal memuat Echo. Cek koneksi internet.");
                        alert("Gagal koneksi ke server realtime. Cek internet Anda.");
                    }
                }
            }, 500);
        },

        listenForRemoteScan() {
            if (this.isListening) return;
            if (typeof window.Echo === 'undefined') return;

            this.isListening = true;
            window.Echo.leave('pos-channel');

            window.Echo.channel('pos-channel')
                .listen('.scanned', (e) => {
                    console.log("🔔 Sinyal Masuk:", e);

                    // Anti Spam Check
                    const now = Date.now();
                    if (this.lastScannedCode === e.barcode && (now - this.lastScannedTime < 2000)) return;

                    this.lastScannedCode = e.barcode;
                    this.lastScannedTime = now;

                    if (!e.barcode) return;

                    this.playBeep('success');
                    this.search = e.barcode;

                    // ========================================================
                    // [PERBAIKAN DISINI] JANGAN LUPA OPER e.qty !!!
                    // ========================================================
                    // Sebelumnya cuma: this.scanProduct(e.barcode);
                    // Sekarang harus:
                    this.scanProduct(e.barcode, e.qty, e.image);
                });
        },

        // --- 1. STATE UI & UMUM ---
        mobileCartOpen: false,
        showPaymentModal: false,
        search: '',
        cart: [],
        uploadedFiles: [],
        filesToDelete: [],
        isProcessing: false,
        scannerOpen: false,
        scannerObj: null,
        isValidatingCoupon: false,

        // --- [BARU] STATE VARIAN ---
        variantSelectorOpen: false,      // Kontrol Modal Varian
        selectedProductForVariant: null, // Data produk induk sementara
        productVariants: [],

        // --- 2. KUPON ---

        couponCode: @json($autoCoupon ?? ""),
        couponMessage: '',
        discountAmount: 0,

        // --- 3. PELANGGAN (MEMBER/GUEST) ---
        customerType: 'guest',
        customerName: '',
        customerPhone: '',
        isSavingCustomer: false, // <--- INI WAJIB ADA & FALSE

        // 1. TAMBAHKAN VARIABLE STATE BARU (PENCARIAN PELANGGAN)
        isSearchingCustomer: false,
        isCustomerFound: false,
        customerSearchResults: [],     // Untuk hasil pencarian via WA
        customerNameSearchResults: [], // BARU: Untuk hasil pencarian via Nama


        // --- TAMBAHKAN FUNGSI INI (AUTO KOREKSI NOMOR WA) ---
        sanitizePhone() {
            // 1. Ambil nilai saat ini
            let hp = this.customerPhone;

            // 2. Hapus semua karakter SELAIN ANGKA (hapus spasi, strip, titik, huruf)
            // Contoh: "+62 857-4580" menjadi "628574580"
            hp = hp.replace(/[^0-9]/g, '');

            // 3. Logika Koreksi Awalan (Prefix)
            if (hp.startsWith('62')) {
                // Jika diawali 62, ganti jadi 0
                hp = '0' + hp.slice(2);
            }
            else if (hp.startsWith('8')) {
                // Jika diawali 8 (lupa angka 0), tambahkan 0 di depan
                hp = '0' + hp;
            }

            // 4. Kembalikan ke Input
            this.customerPhone = hp;
        },

        customerAddressDetail: '',
        selectedCustomerId: '',

        // --- 4. PEMBAYARAN ---
        paymentMethod: 'cash',
        paymentChannel: '',
        tripayChannels: [],
        isLoadingChannels: false,
        cashAmount: '',
        affiliatePin: '',
        danaRedirectUrl: '', // LOG: Simpan URL redirect DANA di sini

        // --- 5. PENGIRIMAN (KIRIMINAJA) ---
        deliveryType: 'pickup',

        // Variabel Pencarian Lokasi (Kecamatan/Kelurahan)
        searchQuery: '',
        searchResults: [],
        isSearchingLocation: false,

        // ID Lokasi
        destinationDistrictId: '',
        destinationSubdistrictId: '',

        // Hasil Ongkir
        courierList: [],
        selectedCourier: null,
        shippingCost: 0,
        isLoadingShipping: false,

        // TAMBAHKAN INI DI BAWAH VARIABEL DELIVERY TYPE
        latitude: '',
        longitude: '',
        isGettingLocation: false,

        noteModalOpen: false,
        customerNote: '', // <--- NAMA VARIABLE BARU (Default kosong)

        isListening: false,
        lastScannedCode: null,
        lastScannedTime: 0,

        scannerModalOpen: false, // Status Modal
        scannerObj: null,        // Object Html5Qrcode
        tempManualCode: '',      // Input manual di modal

        // ============================================================
        // COMPUTED PROPERTIES
        // ============================================================

        // --- FUNGSI HITUNG TOTAL QTY & HARGA ---
        updateCartTotals() {
            // 1. Hitung Total QTY (Jumlah Barang)
            // Kita loop semua item di cart, lalu jumlahkan qty-nya
            this.cartTotalQty = this.cart.reduce((total, item) => total + parseInt(item.qty), 0);

            // 2. Hitung Subtotal Harga (Opsional, biar sekalian update harga)
            this.subtotal = this.cart.reduce((total, item) => total + (parseInt(item.price) * parseInt(item.qty)), 0);

            // 3. Hitung Grand Total (Subtotal - Diskon + Pajak + Ongkir dll)
            // Sesuaikan logika ini dengan sistem Anda yang sudah ada
            this.grandTotal = this.subtotal;
        },

        // FUNGSI BARU: Update Qty Mengikuti Total Rupiah
        updateByTotal(id, totalRupiah) {
            let item = this.cart.find(i => i.id === id);
            if (item) {
                // Jika input dikosongkan/dihapus, set Qty jadi 0
                if (totalRupiah === '' || totalRupiah === null) {
                    item.qty = 0;
                    return;
                }

                let price = parseFloat(item.price);
                let total = parseFloat(totalRupiah);

                if (price > 0 && total >= 0) {
                    // Hitung Qty = Total / Harga
                    let newQty = total / price;

                    // GUNAKAN PRESISI TINGGI (4 desimal) agar Rupiah tidak meleset
                    // Membulatkan ke BAWAH di 4 desimal.
                    // 2.142857... menjadi 2.1428.
                    // 2.1428 x 7000 = 14.999,6 -> Backend akan membulatkan (ceil) ini kembali ke 15.000 PAS.
                    item.qty = Math.floor(newQty * 10000) / 10000;
                }
            }

            // Cek ulang kupon setelah total berubah
            if(this.couponCode) this.checkCoupon();
        },

        // PERBAIKAN: Ubah getter subtotal agar support desimal (Float)
        get subtotal() {
            // Gunakan parseFloat untuk qty agar desimal terbaca (misal 2.8 Kg)
            // Math.round untuk membulatkan total per item agar tidak ada koma aneh (19999,7 jadi 20000)
            return this.cart.reduce((sum, item) => {
                let itemTotal = parseInt(item.price) * parseFloat(item.qty);
                return sum + Math.round(itemTotal);
            }, 0);
        },

        // PERBAIKAN: Ubah getter total qty agar support desimal
        get cartTotalQty() {
            return this.cart.reduce((sum, item) => sum + parseFloat(item.qty), 0);
        },

        // ------------------------------------------------------------------
        // LOGIKA PENCARIAN & AUTOFILL PELANGGAN (FITUR BARU)
        // ------------------------------------------------------------------

        playBeep(type) {
            const audioId = type === 'success' ? 'beep-success' : 'beep-fail';
            const audio = document.getElementById(audioId);

            if (audio) {
                audio.currentTime = 0;
                // Tambahkan catch error agar console tidak merah penuh error
                audio.play().catch(e => {
                    console.warn("🔊 Audio di-blokir browser (Belum ada interaksi user).");
                });
            }
        },

        // FUNGSI 1: MULAI SCANNER (BUKA MODAL)
        startScanner() {
            this.scannerModalOpen = true;
            this.tempManualCode = ''; // Reset input manual

            // Tunggu modal muncul (nextTick), baru nyalakan kamera
            this.$nextTick(() => {
                // Cek apakah scanner sudah jalan sebelumnya, kalau iya stop dulu
                if (this.scannerObj) {
                    this.scannerObj.clear();
                }

                const onScanSuccess = (decodedText, decodedResult) => {
                    console.log(`Scan Sukses: ${decodedText}`);

                    // 1. Matikan kamera & Tutup Modal
                    this.stopScanner();

                    // 2. Panggil Logic Scan Utama (Yang sudah kita perbaiki tadi)
                    // Kirim decodedText sebagai barcode, null sebagai qty (default 1), null image
                    this.scanProduct(decodedText);
                };

                const onScanFailure = (error) => {
                    // Biarkan kosong agar console tidak penuh spam error saat mencari
                };

                // Inisialisasi Library
                this.scannerObj = new Html5Qrcode("reader-modal");

                const config = { fps: 10, qrbox: { width: 250, height: 250 } };

                // Mulai Kamera Belakang (Environment)
                this.scannerObj.start({ facingMode: "environment" }, config, onScanSuccess, onScanFailure)
                    .catch(err => {
                        console.error("Gagal start kamera:", err);
                        alert("Gagal akses kamera. Pastikan izin diberikan.");
                        this.scannerModalOpen = false;
                    });
            });
        },

        // FUNGSI 2: STOP SCANNER (TUTUP MODAL)
        stopScanner() {
            if (this.scannerObj) {
                this.scannerObj.stop().then(() => {
                    this.scannerObj.clear();
                    this.scannerModalOpen = false;
                }).catch(err => {
                    console.log("Stop error:", err);
                    this.scannerModalOpen = false; // Paksa tutup meski error stop
                });
            } else {
                this.scannerModalOpen = false;
            }
        },

        // FUNGSI 3: INPUT MANUAL DARI MODAL
        handleManualModalInput() {
            if(this.tempManualCode.trim().length > 2) {
                this.stopScanner(); // Tutup modal dulu
                this.scanProduct(this.tempManualCode.trim()); // Proses kode
            }
        },


        // ============================================================
        // FUNGSI SCAN PRODUCT (VERSI FINAL + GAMBAR)
        // ============================================================
        async scanProduct(manualCode = null, qtyOverride = null, imageOverride = null) {

            // 1. Ambil Barcode
            let code = manualCode || this.search.trim();
            if (!code || code.length < 3) return;

            this.isProcessing = true;

            try {
                // 2. Request ke API
                const url = "{{ route('orders.scan-product') }}?code=" + encodeURIComponent(code);
                const response = await fetch(url, { headers: { 'Accept': 'application/json' } });
                const result = await response.json();

                if (result.status === 'success') {
                    let p = result.data;

                    // 3. LOGIKA QTY (Pastikan Angka)
                    let incomingQty = qtyOverride ? parseFloat(qtyOverride) : 1;

                    console.log(`LOG POS: Barang ${p.name} | Qty: ${incomingQty}`);

                    // 4. LOGIKA GAMBAR (PENTING!)
                    // Prioritas 1: Gambar dari Pusher (imageOverride) karena URL-nya sudah lengkap dari server
                    // Prioritas 2: Gambar dari API result.data (perlu dirakit path-nya)
                    let finalImage = null;

                    if (imageOverride) {
                        finalImage = imageOverride;
                    } else if (p.image) {
                        // Cek apakah API mengirim full URL atau path relative
                        if(p.image.startsWith('http')) {
                            finalImage = p.image;
                        } else {
                            finalImage = `{{ asset('storage') }}/${p.image}`;
                        }
                    }

                    // 5. Tentukan ID Unik Cart
                    let targetCartId = (result.type === 'variant') ? `${p.id}-VAR-${p.variant_id}` : p.id;

                    // 6. Cek Keranjang
                    let existingItem = this.cart.find(item => item.id == targetCartId);

                    if (existingItem) {
                        // --- KASUS A: UPDATE QTY ---
                        existingItem.qty = parseFloat(existingItem.qty) + incomingQty;

                        // Update gambar di keranjang jika sebelumnya kosong
                        if(!existingItem.image && finalImage) existingItem.image = finalImage;

                    } else {
                        // --- KASUS B: INSERT BARU ---

                        // Masukkan ke array (Gunakan finalImage yang sudah kita proses di atas)
                        if (result.type === 'single') {
                            this.addToCart(p.id, p.name, p.sell_price, p.stock, p.weight ?? 0, finalImage, false, 'all');
                        } else {
                            this.processAddItem(targetCartId, p.name, p.sell_price, p.stock, p.weight ?? 0, finalImage, p.variant_id);
                        }

                        // Update Qty (Delay 100ms agar masuk memori)
                        setTimeout(() => {
                             let newItem = this.cart.find(item => item.id == targetCartId);
                             if(newItem) newItem.qty = incomingQty;
                             this.updateCartTotals();
                        }, 100);
                    }

                    // 7. Notifikasi Cantik (Ada Gambarnya)
                    if (typeof Swal !== 'undefined') {
                        const Toast = Swal.mixin({
                            toast: true, position: 'top-end', showConfirmButton: false, timer: 3000,
                            didOpen: (toast) => {
                                toast.addEventListener('mouseenter', Swal.stopTimer)
                                toast.addEventListener('mouseleave', Swal.resumeTimer)
                            }
                        });

                        Toast.fire({
                            icon: 'success',
                            title: p.name,
                            text: `Berhasil masuk: ${incomingQty} pcs`,
                            imageUrl: finalImage, // <--- TAMPILKAN GAMBAR DISINI
                            imageWidth: 50,
                            imageHeight: 50,
                            imageAlt: 'Produk',
                        });
                    }

                    // Reset UI
                    if(!manualCode) this.playBeep('success');
                    this.search = '';
                    this.updateCartTotals();

                } else {
                    this.playBeep('error');
                    console.warn("Produk tidak ditemukan");
                }

            } catch (error) {
                console.error("Scan Error:", error);
                this.playBeep('error');
            } finally {
                this.isProcessing = false;
            }
        },

        // 1. FUNGSI HELPER: MEMUTAR SUARA
        playBeep(type) {
            // Tentukan ID audio berdasarkan tipe
            const audioId = type === 'success' ? 'audio-success' : 'audio-error';
            const audio = document.getElementById(audioId);

            if (audio) {
                audio.currentTime = 0; // Reset durasi agar bisa diputar berulang cepat
                audio.play().catch(e => console.log('Browser memblokir autoplay audio', e));
            }
        },



        async searchCustomerByName() {
            // [LOGIC BARU] Reset Status
            this.isCustomerFound = false; // Matikan centang
            this.selectedCustomerId = '';

            // Reset Kupon
            if (this.couponCode) {
                this.couponCode = '';
                this.discountAmount = 0;
                this.couponMessage = '';
            }

            if (this.customerName.length < 3) {
                this.customerNameSearchResults = [];
                return;
            }

            this.isSearchingCustomer = true;

            try {
                const response = await fetch(`{{ route('customers.searchApi') }}?q=${this.customerName}`);
                const data = await response.json();
                this.customerNameSearchResults = data.length > 0 ? data : [];
            } catch (error) {
                console.error("Gagal mencari pelanggan:", error);
            } finally {
                this.isSearchingCustomer = false;
            }
        },

        async searchCustomerByPhone() {
            // [LOGIC BARU] Reset Status Dulu
            this.isCustomerFound = false; // Matikan centang hijau
            this.selectedCustomerId = ''; // Hapus ID customer lama

            // Reset Kupon juga biar tidak bocor ke user baru
            if (this.couponCode) {
                this.couponCode = '';
                this.discountAmount = 0;
                this.couponMessage = '';
            }

            // Bersihkan input hp
            let query = this.customerPhone.replace(/[^0-9]/g, '');

            // Minimal 4 digit baru cari
            if (query.length < 4) {
                this.customerSearchResults = [];
                return;
            }

            this.isSearchingCustomer = true;

            try {
                const response = await fetch(`{{ route('customers.searchApi') }}?q=${query}`);
                const data = await response.json();

                if (data.length > 0) {
                    // Cek Autofill: Jika hasil cuma 1 DAN nomor persis sama
                    if (data.length === 1 && data[0].whatsapp === query) {
                        // OPSI: Mau autofill langsung atau tunggu diklik?
                        // Saran: Tunggu diklik biar user sadar, kecuali Anda mau UX cepat
                        this.fillCustomerData(data[0]);
                    }
                    this.customerSearchResults = data;
                } else {
                    this.customerSearchResults = [];
                }
            } catch (error) {
                console.error("Gagal mencari pelanggan:", error);
            } finally {
                this.isSearchingCustomer = false;
            }
        },

        // 2. ISI FORM OTOMATIS (AUTOFILL)
        fillCustomerData(data) {
            console.log("LOG: Mengisi data pelanggan...", data);

            this.selectedCustomerId = data.id; // Penting untuk relasi DB
            this.customerName = data.name;
            this.customerPhone = data.whatsapp;
            this.customerAddressDetail = data.address || '';

              // [PENTING] Nyalakan Centang Hijau DI SINI
            this.isCustomerFound = true;

            // ====================================================
            // LOGIKA PINTAR: CEK KUPON DATABASE
            // ====================================================

            // KONDISI 1: Customer Punya Kupon di Database
            if (data.assigned_coupon && data.assigned_coupon !== null && data.assigned_coupon !== "") {
                // Isi form dengan kupon dia
                this.couponCode = data.assigned_coupon;
                console.log("LOG: Auto-apply kupon member: " + data.assigned_coupon);

                // Validasi ke server otomatis
                setTimeout(() => { this.checkCoupon(); }, 300);
            }
            // KONDISI 2: Customer TIDAK Punya Kupon -> BERSIHKAN!
            else {
                this.couponCode = '';      // Kosongkan form agar bisa isi manual
                this.discountAmount = 0;   // Reset diskon
                this.couponMessage = '';   // Hapus pesan "Berhasil/Gagal"

            }
            // ====================================================

            // Isi Data Wilayah & Ongkir (Jika ada di DB)
            if (data.district_id) {
                this.destinationDistrictId = data.district_id;
                this.destinationSubdistrictId = data.subdistrict_id;
                this.searchQuery = data.address; // Isi field pencarian lokasi dengan alamat teks (fallback)

                // Trigger cek ongkir otomatis jika data lengkap
                if (this.deliveryType === 'shipping') {
                    this.checkOngkir();
                }
            }

            // Isi Data GPS (Jika ada)
            if (data.latitude && data.longitude) {
                this.latitude = data.latitude;
                this.longitude = data.longitude;
            }

            this.isCustomerFound = true; // Tandai bahwa data ini valid dari DB
            this.customerSearchResults = []; // Tutup dropdown
        },

        // 3. RESET FORM
        resetCustomerData() {
            this.selectedCustomerId = '';
            this.customerName = '';
            this.customerPhone = '';
            this.customerAddressDetail = '';
            this.latitude = '';
            this.longitude = '';
            this.isCustomerFound = false;
            this.customerSearchResults = [];
        },

        // 4. SIMPAN DATA VIA AJAX (DIPERBAIKI UNTUK LAT/LONG & AUTOFILL)
        async saveCustomerToDB() {
            // 1. Validasi Sederhana
            if (!this.customerName || !this.customerPhone) {
                alert('Harap isi Nama dan Nomor WhatsApp pelanggan.');
                return;
            }

            this.isSavingCustomer = true;

            try {
                // Ambil CSRF Token
                const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

                const response = await fetch("{{ route('customers.storeAjax') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        name: this.customerName,
                        whatsapp: this.customerPhone,
                        address: this.customerAddressDetail,
                        // --- TAMBAHAN BARU: GPS ---
                        latitude: this.latitude,
                        longitude: this.longitude
                        // --------------------------
                    })
                });

                const result = await response.json();

                if (response.ok) {
                    // Sukses
                    alert('Pelanggan berhasil disimpan ke database!');

                    // --- AUTOFILL SETELAH SIMPAN ---
                    // Panggil fungsi fillCustomerData dengan data balikan dari server
                    // Ini akan mengunci form dan mengisi ID customer agar siap checkout
                    this.fillCustomerData(result.data);

                    this.isOpen = false; // Tutup accordion form kecil
                } else {
                    // Error Validasi Server
                    alert('Gagal: ' + (result.message || 'Terjadi kesalahan validasi.'));
                }

            } catch (error) {
                console.error('Error:', error);
                alert('Terjadi kesalahan sistem saat menyimpan data.');
            } finally {
                this.isSavingCustomer = false;
            }
        },

        // --- FUNGSI BARU UNTUK KIRIM LOG KE SERVER ---
        async logToServer(message, detail = {}) {
            try {
                await fetch("{{ route('log.client.error') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        message: message,
                        context: detail
                    })
                });
            } catch (e) {
                console.error("Gagal mengirim log ke server", e);
            }
        },

        // --- FUNGSI GPS V2 (ANTI ERROR WINDOWS) ---
        getGeoLocation() {
            if (!navigator.geolocation) {
                alert("Browser Anda tidak mendukung Geolocation.");
                this.deliveryType = 'pickup';
                return;
            }

            this.isGettingLocation = true;
            // Reset koordinat agar terlihat prosesnya
            this.latitude = '';
            this.longitude = '';

            // 1. Definisi Callback Sukses
            const onGeoSuccess = (position) => {
                this.latitude = position.coords.latitude;
                this.longitude = position.coords.longitude;
                this.isGettingLocation = false;
                console.log(`LOG: Lokasi dapat! (Akurasi: ${position.coords.accuracy} meter)`);
            };

            // 2. Definisi Callback Error (Hanya dipanggil jika semua cara gagal)
            const onGeoError = (error) => {
                this.isGettingLocation = false;
                let msg = "Gagal mengambil lokasi.";

                if(error.code == 1) {
                    msg = "❌ Akses Lokasi DITOLAK.\n\nCARA MEMPERBAIKI:\n1. Klik ikon Gembok 🔒 di samping URL website.\n2. Klik 'Permissions' atau 'Site Settings'.\n3. Ubah Location menjadi 'Allow' / 'Izinkan'.\n4. Coba klik tombol ini lagi.";
                }
                else if(error.code == 2) msg = "⚠️ Sinyal GPS tidak ditemukan. Pastikan GPS HP menyala.";
                else if(error.code == 3) msg = "⚠️ Waktu habis. Sinyal GPS lemah.";

                alert(msg);

                // Kembalikan ke pickup jika gagal total
                this.deliveryType = 'pickup';
            };

            // 3. EKSEKUSI: Coba High Accuracy Dulu (Default HP)
            console.log("LOG: Mencoba GPS Akurasi Tinggi...");

            navigator.geolocation.getCurrentPosition(
                onGeoSuccess,
                (error) => {
                    // JIKA GAGAL (Biasanya di Laptop/Windows), COBA LOW ACCURACY
                    console.warn("LOG: High Accuracy gagal, mencoba Low Accuracy (Mode Laptop)...");

                    navigator.geolocation.getCurrentPosition(
                        onGeoSuccess,
                        onGeoError, // Jika Low Accuracy pun gagal, baru error beneran
                        {
                            enableHighAccuracy: false, // Penting untuk Laptop
                            timeout: 15000,            // Waktu tunggu 15 detik
                            maximumAge: 0
                        }
                    );
                },
                {
                    enableHighAccuracy: true, // Coba paksa akurat dulu
                    timeout: 3000,            // Cuma tunggu 3 detik, kalau lama langsung switch ke Low
                    maximumAge: 0
                }
            );
        },

        get grandTotal() {
            let disc = parseInt(this.discountAmount) || 0;
            let ship = parseInt(this.shippingCost) || 0;

            // Math.ceil memastikan pembulatan ke atas (aman untuk toko)
            // Math.round untuk pembulatan terdekat
            let total = Math.round(this.subtotal - disc + ship);

            return total < 0 ? 0 : total;
        },

        get change() {
            let received = parseInt(this.cashAmount) || 0;
            return received - this.grandTotal;
        },

        // ============================================================
        // HELPERS
        // ============================================================

        rupiah(val) {
            return new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(val);
        },

        formatFileSize(bytes) {
            if(bytes === 0) return '0 B';
            const k = 1024; const sizes = ['B', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
        },

        itemMatchesSearch(name) {
            return name.toLowerCase().includes(this.search.toLowerCase());
        },

        getItemQty(id) {
            let item = this.cart.find(i => i.id === id);
            return item ? item.qty : 0;
        },

        // ============================================================
        // LOGIKA PENGIRIMAN
        // ============================================================

        async searchLocation() {
            if (this.searchQuery.length < 3) {
                this.searchResults = [];
                return;
            }
            this.isSearchingLocation = true;
            try {
                const response = await fetch(`{{ route('orders.search-location') }}?query=${this.searchQuery}`);
                const result = await response.json();
                if (result.status === 'success') {
                    this.searchResults = result.data;
                } else {
                    this.searchResults = [];
                }
            } catch (error) {
                console.error('Gagal cari lokasi:', error);
                this.searchResults = [];
            } finally {
                this.isSearchingLocation = false;
            }
        },

        selectLocation(location) {
            this.searchQuery = location.full_address;
            this.destinationDistrictId = location.district_id;
            this.destinationSubdistrictId = location.subdistrict_id;

            // --- TAMBAHKAN INI AGAR DATA ALAMAT LENGKAP SAAT DISIMPAN ---
            const parts = location.full_address.split(',').map(s => s.trim());
            if (parts.length >= 4) {
                this.selectedVillage = parts[0];
                this.selectedDistrict = parts[1];
                this.selectedRegency = parts[2];
                this.selectedProvince = parts[3];
                this.destinationZipCode = parts[4] || location.zip_code || '';
            }
            // -----------------------------------------------------------

            this.searchResults = [];
            this.checkOngkir();
        },

        async checkOngkir() {
            if (!this.destinationDistrictId) return;

            let realTotalWeight = this.cart.reduce((w, item) => w + (item.qty * (item.weight > 0 ? item.weight : 100)), 0);
            let finalWeight = realTotalWeight < 1000 ? 1000 : realTotalWeight;

            this.isLoadingShipping = true;
            this.courierList = [];
            this.selectedCourier = null;
            this.shippingCost = 0;

            try {
                const response = await fetch("{{ route('orders.check-ongkir') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        // --- DATA ONGKIR ---
                        destination_district_id: this.destinationDistrictId,
                        destination_subdistrict_id: this.destinationSubdistrictId,
                        destination_text: this.searchQuery,
                        weight: finalWeight,

                        // --- [FIXED] DATA PELANGGAN UNTUK AUTO-SAVE ---
                        save_customer: this.saveCustomer, // Mengambil status checkbox
                        customer_name: this.customerName,
                        customer_phone: this.customerPhone,
                        customer_address_detail: this.customerAddressDetail,

                        // Detail Wilayah (Pastikan variabel ini terisi saat selectLocation)
                        province_name: this.selectedProvince || '',
                        regency_name: this.selectedRegency || '',
                        district_name: this.selectedDistrict || '',
                        village_name: this.selectedVillage || '',
                        postal_code: this.destinationZipCode || '',

                        // Koordinat (Jika ada)
                        receiver_lat: this.latitude,
                        receiver_lng: this.longitude
                    })
                });

                const result = await response.json();

                if (result.status === 'success') {
                    this.courierList = result.data;
                    // Log kecil untuk memastikan instruksi simpan terkirim
                    if(this.saveCustomer) console.log("✅ Perintah simpan data pelanggan dikirim ke server.");
                } else {
                    alert('Gagal cek ongkir: ' + result.message);
                }
            } catch (e) {
                console.error(e);
                alert('Error koneksi server saat cek ongkir');
            } finally {
                this.isLoadingShipping = false;
            }
        },

        selectCourier(courier) {
            this.selectedCourier = courier;
            this.shippingCost = parseInt(courier.cost);
        },

        // ============================================================
        // LOGIKA PEMBAYARAN & MEMBER
        // ============================================================

        getSelectedMemberSaldo() {
            if(!this.selectedCustomerId) return 0;
            const select = document.querySelector(`select[x-model="selectedCustomerId"]`);
            if (!select) return 0;
            const option = select.querySelector(`option[value="${this.selectedCustomerId}"]`);
            return option ? parseFloat(option.dataset.saldo) : 0;
        },

        getSelectedAffiliateBalance() {
            if(!this.selectedCustomerId) return 0;
            const select = document.querySelector(`select[x-model="selectedCustomerId"]`);
            if (!select) return 0;
            const option = select.querySelector(`option[value="${this.selectedCustomerId}"]`);
            return option ? parseFloat(option.dataset.affiliateBalance) : 0;
        },

        selectAffiliatePayment() {
            if(!this.selectedCustomerId) { alert('❌ Pilih Member terlebih dahulu!'); return; }
            if(this.getSelectedAffiliateBalance() < this.grandTotal) { alert('❌ Saldo Profit tidak cukup!'); return; }
            this.paymentMethod = 'affiliate_balance';
            this.affiliatePin = '';
        },

        async fetchTripayChannels() {
            if (this.tripayChannels.length > 0) return;
            this.isLoadingChannels = true;
            try {
                const response = await fetch("{{ route('orders.tripay-channels') }}");
                const result = await response.json();
                if(result.status === 'success') { this.tripayChannels = result.data; }
            } catch (error) { console.error('Fetch error:', error); } finally { this.isLoadingChannels = false; }
        },

        getChannelsByGroup(groupName) {
            if (!this.tripayChannels || this.tripayChannels.length === 0) return [];
            return this.tripayChannels.filter(c => c.active === true && c.group.toLowerCase() === groupName.toLowerCase());
        },

        async checkCoupon() {
            if (!this.couponCode.trim()) { this.discountAmount = 0; this.couponMessage = ''; return; }
            if (this.cart.length === 0) { this.couponMessage = 'Isi keranjang dulu.'; return; }
            this.isValidatingCoupon = true; this.couponMessage = '';
            try {
                const response = await fetch("{{ route('orders.check-coupon') }}", {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') },
                    body: JSON.stringify({ coupon_code: this.couponCode, total_belanja: this.subtotal })
                });
                const data = await response.json();
                if (data.status === 'success') { this.discountAmount = data.data.discount_amount; this.couponMessage = `✅ Berhasil, Kakak Hemat Rp ${this.rupiah(data.data.discount_amount)}`; }
                else {
                    // GAGAL / TIDAK DITEMUKAN
                    this.discountAmount = 0; // Pastikan diskon jadi 0
                    this.couponMessage = data.message; // Tampilkan pesan error

                    // OPSI: Jika ingin teks input ikut dihapus saat salah (Hati-hati, ini bisa mengganggu saat mengetik)
                    // this.couponCode = '';
                }
            } catch (error) {
                this.couponMessage = 'Gagal cek server.';
                this.discountAmount = 0; // Reset diskon jika error sistem
            } finally {
                this.isValidatingCoupon = false;
            }
        },

        // ---------------------------------------------------------
        // HAPUS function addToCart LAMA, GANTI DENGAN 3 FUNGSI INI:
        // ---------------------------------------------------------

        // 1. Method Add To Cart UTAMA
        async addToCart(id, name, price, maxStock, weight = 0, image = null, hasVariant = false, categorySlug = 'all') {
            // --- [FITUR BARU: AUTO FOCUS KATEGORI] ---
            // Jika slug valid dan bukan 'all', pindahkan tab aktif
            if (categorySlug && categorySlug !== '' && categorySlug !== 'all') {
                this.activeCategory = categorySlug;
            }
            // -----------------------------------------

            if (hasVariant) {
                this.selectedProductForVariant = { id, name, image, weight };
                this.variantSelectorOpen = true;
                this.productVariants = [];

                try {
                    let response = await fetch(`{{ url('/products') }}/${id}/variants`);
                    let data = await response.json();
                    this.productVariants = data.variants;
                } catch (e) {
                    alert('Gagal memuat varian produk.');
                    this.variantSelectorOpen = false;
                }
                return;
            }
            this.processAddItem(id, name, price, maxStock, weight, image);
        },

        // 2. Method Pilih Varian dari Modal
        addVariantToCart(variant) {
            let parent = this.selectedProductForVariant;
            let fullName = `${parent.name} (${variant.name})`;
            let cartId = `${parent.id}-VAR-${variant.id}`;

            this.processAddItem(
                cartId,
                fullName,
                variant.price,
                variant.stock,
                parent.weight,
                parent.image,
                variant.id
            );

            this.variantSelectorOpen = false;
        },

        // 3. Method Helper Masuk Keranjang
        processAddItem(id, name, price, maxStock, weight, image, variantId = null) {
            if (maxStock <= 0) { alert('Stok Habis!'); return; }

            let realWeight = (parseInt(weight) > 0) ? parseInt(weight) : 5;
            let item = this.cart.find(i => i.id === id);

            if (item) {
                if (item.qty < maxStock) {
                    item.qty++;
                } else {
                    alert('Stok maksimal tercapai!');
                }
            } else {
                this.cart.push({
                    id,
                    name,
                    price,
                    qty: 1,
                    maxStock,
                    image,
                    weight: realWeight,
                    variant_id: variantId
                });
            }
            this.updateCartTotals(); // <--- Agar angka merah di keranjang berubah!
            if(navigator.vibrate) navigator.vibrate(30);
            if(this.couponCode) this.checkCoupon();
        },

        // ---------------------------------------------------------
        // AKHIR KODE BARU
        // ---------------------------------------------------------

        updateQty(id, amount) {
            let item = this.cart.find(i => i.id === id);
            if (item) {
                if (amount > 0 && item.qty >= item.maxStock) { alert('Stok maksimal tercapai'); return; }
                item.qty += amount;
                if (item.qty <= 0) this.removeFromCart(id);
            }
            if(this.couponCode) setTimeout(() => this.checkCoupon(), 500);
        },

        validateManualQty(id) {
            let item = this.cart.find(i => i.id === id);
            if (!item) return;

            // 1. Ganti parseInt jadi parseFloat agar menerima desimal (koma)
            // Tambahkan .replace(',', '.') untuk antisipasi user ngetik pakai koma Indonesia
            let rawInput = String(item.qty).replace(',', '.');
            let parsed = parseFloat(rawInput);

            // 2. Ubah validasi: Jangan < 1, tapi <= 0.
            // Karena laundry bisa saja cuma 0.5 Kg (kurang dari 1)
            if (isNaN(parsed) || parsed <= 0) {
                item.qty = 1; // Default balik ke 1 jika input ngawur
            }
            else if (parsed > item.maxStock) {
                alert('Stok tidak mencukupi!');
                item.qty = item.maxStock;
            }
            else {
                // 3. Simpan angka desimalnya.
                // Opsional: .toFixed(4) untuk membatasi max 4 angka belakang koma biar rapi
                // parseFloat lagi di depannya agar nol di belakang koma hilang (cth: 2.5000 jadi 2.5)
                item.qty = parseFloat(parsed.toFixed(4));
            }

            if(this.couponCode) this.checkCoupon();
        },

        removeFromCart(id) {
            this.cart = this.cart.filter(i => i.id !== id);
            // Jika keranjang jadi kosong
            if(this.cart.length === 0) {
                this.discountAmount = 0;
                this.couponMessage = '';
                this.couponCode = '';

                // --- [FITUR BARU: RESET KATEGORI] ---
                this.activeCategory = 'all';
                // ------------------------------------
            }
            else if(this.couponCode) {
                this.checkCoupon();
            }

        },

        confirmClearCart() {
            if(confirm('Kosongkan keranjang?')) {
                this.cart = []; this.uploadedFiles = []; this.discountAmount = 0; this.couponMessage = '';
                this.shippingCost = 0; this.deliveryType = 'pickup'; this.searchQuery = '';
                // --- [FITUR BARU: RESET KATEGORI] ---
                this.activeCategory = 'all';
                this.couponCode = '';
                // ------------------------------------
            }
        },

        openPaymentModal() {
            if(this.cart.length === 0) { alert('Keranjang masih kosong!'); return; }
            this.showPaymentModal = true;
            if(this.paymentMethod !== 'cash') this.cashAmount = '';
            if(this.paymentMethod === 'tripay') this.fetchTripayChannels();
        },

        handleFileUpload(event) {
            const files = event.target.files;
            const remainingSlots = 10 - this.uploadedFiles.length;

            if (files.length > remainingSlots) {
                alert('Maksimal 10 file total! Slot tersisa: ' + remainingSlots);
                event.target.value = '';
                return;
            }

            for (let i = 0; i < files.length; i++) {
                if(files[i].size > 10 * 1024 * 1024) {
                    alert('File terlalu besar (Max 10MB): ' + files[i].name);
                    continue;
                }

                this.uploadedFiles.push({
                    file: files[i],
                    isColor: false,
                    paperSize: 'A4',
                    qty: 1
                });
            }

            event.target.value = '';
        },

        removeFile(index) {
            this.uploadedFiles.splice(index, 1);
        },

        async checkout() {
            console.log("LOG: Memulai proses Checkout...");
            console.log("LOG: Metode Pembayaran: " + this.paymentMethod);

            // ============================================================
            // 1. VALIDASI INPUT (SEMUA LOGIC LAMA TETAP ADA)
            // ============================================================

            // Validasi Khusus Antar Jemput
            if (this.deliveryType === 'delivery') {
                if (!this.customerAddressDetail || this.customerAddressDetail.length < 5) {
                    alert('❌ Alamat Lengkap Wajib diisi untuk Antar Jemput!');
                    return;
                }
                if (!this.latitude || !this.longitude) {
                    alert('❌ Lokasi GPS belum ditemukan. Pastikan GPS nyala & Izinkan Browser.');
                    this.getGeoLocation(); // Coba ambil lagi
                    return;
                }
            }

            // Validasi Shipping Guest
            if (this.customerType === 'guest' && this.deliveryType === 'shipping') {
                if (!this.customerName || this.customerName.trim().length < 3) {
                    alert('❌ Mohon isi NAMA PENERIMA untuk keperluan pengiriman ekspedisi!');
                    return;
                }
                if (!this.customerPhone || this.customerPhone.trim().length < 9) {
                    alert('❌ Mohon isi NOMOR WA untuk keperluan pengiriman ekspedisi!');
                    return;
                }
                if (!this.customerAddressDetail || this.customerAddressDetail.trim().length < 10) {
                    alert('❌ Mohon isi Detail Alamat (Jalan/No Rumah) agar kurir tidak bingung!');
                    return;
                }
            }

            // Validasi Metode Pembayaran
            if (this.paymentMethod === 'cash') {
                if (!this.cashAmount || this.change < 0) { alert('❌ Uang tunai kurang!'); return; }
            }
            else if (this.paymentMethod === 'tripay') {
                if (!this.paymentChannel) { alert('❌ Silakan pilih Bank / Channel Pembayaran dulu!'); return; }
            }
            else if (this.paymentMethod === 'saldo') {
                if (!this.selectedCustomerId) { alert('❌ Pilih Member!'); return; }
                if (this.getSelectedMemberSaldo() < this.grandTotal) { alert('❌ Saldo Topup kurang!'); return; }
            }
            else if (this.paymentMethod === 'affiliate_balance') {
                if (!this.selectedCustomerId) { alert('❌ Pilih Member!'); return; }
                if (this.getSelectedAffiliateBalance() < this.grandTotal) { alert('❌ Saldo Profit kurang!'); return; }
                if (!this.affiliatePin || this.affiliatePin.length < 4) { alert('❌ Masukkan PIN Keamanan!'); return; }
            }
            else if (this.paymentMethod === 'dana') {
                console.log("LOG: Persiapan pengalihan ke DANA Gateway...");
            }

            // [TAMBAHKAN INI DI BAWAHNYA]
            else if (this.paymentMethod === 'dana_sdk') {
                console.log("LOG: Menggunakan DANA SDK (Widget).");
                // Tidak butuh validasi khusus, lanjut ke submit
            }

            // Validasi Shipping (Kurir)
            if (this.deliveryType === 'shipping') {
                if (!this.destinationDistrictId) {
                    alert('❌ Harap pilih lokasi tujuan pengiriman!');
                    return;
                }
                if (this.shippingCost === 0 || !this.selectedCourier) {
                    alert('❌ Harap pilih kurir pengiriman (KiriminAja)!');
                    return;
                }
            }

            // ============================================================
            // 2. PERSIAPAN DATA (FORM DATA) - TETAP LENGKAP
            // ============================================================
            this.isProcessing = true;
            console.log("LOG: Mengirim data ke Server Sancaka...");

            let formData = new FormData();
            formData.append('items', JSON.stringify(this.cart));
            formData.append('total', this.subtotal);
            formData.append('coupon', this.couponCode);
            formData.append('payment_method', this.paymentMethod);
            formData.append('customer_note', this.customerNote);

            // Tambahkan data GPS ke FormData
            formData.append('latitude', this.latitude);
            formData.append('longitude', this.longitude);

            formData.append('delivery_type', this.deliveryType);
            if (this.deliveryType === 'shipping') {
                formData.append('shipping_cost', this.shippingCost);
                formData.append('courier_name', this.selectedCourier.name + ' - ' + this.selectedCourier.service);
                formData.append('destination_district_id', this.destinationDistrictId);
                formData.append('destination_subdistrict_id', this.destinationSubdistrictId);
                formData.append('destination_text', this.searchQuery);
                formData.append('courier_code', this.selectedCourier.courier_code);
                formData.append('service_type', this.selectedCourier.service_type);
                formData.append('customer_address_detail', this.customerAddressDetail);
            }

            if (this.paymentMethod === 'tripay') {
                formData.append('payment_channel', this.paymentChannel);
            }

            // Logic Customer ID
            if(this.selectedCustomerId) {
                formData.append('customer_id', this.selectedCustomerId);
            }
            // Tetap kirim nama/phone manual sebagai fallback/guest
            formData.append('customer_name', this.customerName || 'Guest');
            formData.append('customer_phone', this.customerPhone || '');

            if(this.paymentMethod === 'cash') formData.append('cash_amount', this.cashAmount);
            if(this.paymentMethod === 'affiliate_balance') formData.append('affiliate_pin', this.affiliatePin);

            // Upload Files Loop
            this.uploadedFiles.forEach((item, index) => {
                formData.append(`attachments[${index}]`, item.file);
                formData.append(`attachment_details[${index}][color]`, item.isColor ? 'Color' : 'BW');
                formData.append(`attachment_details[${index}][size]`, item.paperSize);
                formData.append(`attachment_details[${index}][qty]`, item.qty);
            });

            // ============================================================
            // 3. KIRIM KE SERVER (BAGIAN INI YANG DIPERBAIKI ERROR HANDLINGNYA)
            // ============================================================
            try {
                const response = await fetch("{{ route('orders.store') }}", {
                    method: "POST",
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json' // WAJIB: Agar server return JSON walau error 500
                    },
                    body: formData
                });

                // --- [PERBAIKAN UTAMA MULAI DISINI] ---
                // Kita coba parse JSON dulu apapun status codenya (200, 400, 500)
                let result;
                try {
                    result = await response.json();
                } catch (err) {
                    // Jika gagal parse JSON (misal server down parah / return HTML)
                    throw new Error("Terjadi kesalahan Server (Gagal parsing JSON).");
                }

                // A. JIKA SUKSES (Status 200/201 dan logic 'success')
                if (response.ok && result.status === 'success') {
                    console.log("LOG: Response Sukses:", result);

                    // 1. Cek jika harus bayar online (DANA/Tripay/Doku via Redirect)
                    if (result.payment_url) {
                        window.location.href = result.payment_url;
                        return;
                    }

                    // 2. LOGIKA PRINT STRUK & SWEETALERT (TETAP SAMA)
                    const invoiceUrl = "{{ url('/invoice') }}/" + result.invoice;
                    const printUrl = "{{ url('/orders') }}/" + result.order_id + "/print-struk";

                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            title: 'TRANSAKSI BERHASIL!',
                            text: 'Pilih metode cetak atau lihat invoice',
                            icon: 'success',
                            showCancelButton: true,
                            showDenyButton: true,
                            confirmButtonColor: '#3085d6',
                            denyButtonColor: '#10b981',
                            cancelButtonColor: '#64748b',
                            confirmButtonText: '<i class="fas fa-print"></i> Cetak Struk',
                            denyButtonText: '<i class="fas fa-file-invoice"></i> Lihat Invoice',
                            cancelButtonText: 'Tutup',
                            allowOutsideClick: false
                        }).then((resSwal) => {
                            if (resSwal.isConfirmed) {
                                // Logic Print iframe tersembunyi
                                let printFrame = document.getElementById('printFrame');
                                if (!printFrame) {
                                    printFrame = document.createElement('iframe');
                                    printFrame.id = 'printFrame';
                                    printFrame.style.display = 'none';
                                    document.body.appendChild(printFrame);
                                }
                                printFrame.src = printUrl;
                                printFrame.onload = function() {
                                    printFrame.contentWindow.focus();
                                    printFrame.contentWindow.print();
                                    setTimeout(() => { window.location.href = invoiceUrl; }, 500);
                                };
                            } else if (resSwal.isDenied) {
                                window.location.href = invoiceUrl;
                            } else {
                                window.location.reload();
                            }
                        });
                    } else {
                        // Fallback jika Swal tidak ada
                        window.location.href = invoiceUrl;
                    }
                }
                // B. JIKA ERROR DARI SERVER (Status 400/500 tapi ada JSON message)
                else {
                    let msg = result.message || "Gagal memproses pesanan.";

                    // --- [DETEKSI ERROR SALDO KURANG] ---
                    // Cek apakah pesan error mengandung kata 'saldo' dan 'topup'
                    if (msg.toLowerCase().includes('saldo') && msg.toLowerCase().includes('topup')) {

                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                title: '⚠️ SALDO DEPOSIT KURANG',
                                html: `
                                    <div class="text-left text-sm text-gray-600">
                                        <p class="mb-3">${msg}</p>
                                        <div class="bg-red-50 border border-red-200 rounded p-3 text-red-700">
                                            <p class="font-bold text-xs uppercase mb-1">Solusi:</p>
                                            Silakan Top Up saldo admin terlebih dahulu.
                                        </div>
                                    </div>
                                `,
                                icon: 'error',
                                showCloseButton: true,
                                showCancelButton: true,
                                focusConfirm: false,
                                confirmButtonText: '➕ Top Up Sekarang',
                                confirmButtonColor: '#10b981', // Hijau
                                cancelButtonText: 'Kembali',
                                cancelButtonColor: '#64748b',
                            }).then((act) => {
                                if (act.isConfirmed) {
                                    // Trigger event untuk membuka modal Topup (jika pakai Alpine event)
                                    window.dispatchEvent(new CustomEvent('open-topup-modal'));

                                    // Scroll ke atas sebagai fallback
                                    window.scrollTo({ top: 0, behavior: 'smooth' });
                                }
                            });
                            return; // Stop disini, jangan alert error lagi
                        }
                    }

                    // Jika bukan masalah saldo, lempar error biasa
                    throw new Error(msg);
                }

            } catch (error) {
                console.error("LOG ERROR:", error);
                // Jangan tampilkan alert double jika sudah ditangani Swal Saldo
                if (typeof Swal !== 'undefined' && Swal.isVisible()) {
                    return;
                }
                alert('❌ Gagal: ' + error.message);
            } finally {
                this.isProcessing = false;
            }
        }
    }
}

{{-- AKHIR SCRIPT POS --}}
