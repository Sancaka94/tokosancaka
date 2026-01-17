
    function posSystem() {
        return {
            activeCategory: 'all', // Pindah ke sini agar reaktif

            init() {
                 if(this.couponCode) {
                      this.couponMessage = 'Kupon terdeteksi! Masukkan barang untuk cek diskon.';
                 }
            },

            // --- 1. STATE UI & UMUM ---
            mobileCartOpen: false,
            showPaymentModal: false,
            search: '',
            cart: [],
            uploadedFiles: [],
            filesToDelete: [],
            isProcessing: false,
            isValidatingCoupon: false,

            // --- [BARU] STATE VARIAN ---
            variantSelectorOpen: false,      // Kontrol Modal Varian
            selectedProductForVariant: null, // Data produk induk sementara
            productVariants: [],

            // --- 2. KUPON ---
            couponCode: '{{ $autoCoupon ?? "" }}',
            couponMessage: '',
            discountAmount: 0,

            // --- 3. PELANGGAN (MEMBER/GUEST) ---
            customerType: 'guest',
            customerName: '',
            customerPhone: '',
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

            // Variabel Pencarian Lokasi
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

            // ============================================================
            // COMPUTED PROPERTIES
            // ============================================================

            get subtotal() {
                return this.cart.reduce((sum, item) => sum + (parseInt(item.price) * parseInt(item.qty)), 0);
            },

            get cartTotalQty() {
                return this.cart.reduce((sum, item) => sum + item.qty, 0);
            },

            // --- FUNGSI BARU UNTUK CEK GPS ---
            getGeoLocation() {
                if (navigator.geolocation) {
                    this.isGettingLocation = true;
                    // Reset dulu biar kelihatan prosesnya
                    this.latitude = '';
                    this.longitude = '';

                    navigator.geolocation.getCurrentPosition(
                        (position) => {
                            this.latitude = position.coords.latitude;
                            this.longitude = position.coords.longitude;
                            this.isGettingLocation = false;

                            // Optional: Notifikasi kecil jika berhasil
                            // alert('Lokasi berhasil dikunci!');
                        },
                        (error) => {
                            this.isGettingLocation = false;
                            let msg = "Gagal mengambil lokasi.";
                            if(error.code == 1) msg = "Mohon IZINKAN akses Lokasi/GPS di browser Anda.";
                            else if(error.code == 2) msg = "Pastikan GPS HP menyala.";
                            else if(error.code == 3) msg = "Waktu permintaan habis.";

                            alert("⚠️ " + msg + "\nFitur Antar Jemput wajib menggunakan lokasi.");

                            // Kembalikan ke pickup jika gagal
                            this.deliveryType = 'pickup';
                        },
                        { enableHighAccuracy: true, timeout: 20000, maximumAge: 0 } // Perpanjang timeout jadi 20 detik
                    );
                } else {
                    alert("Browser Anda tidak mendukung Geolocation.");
                    this.deliveryType = 'pickup';
                }
            },

            get grandTotal() {
                // Pastikan diskon dan ongkir dianggap angka 0 jika kosong
                let disc = parseInt(this.discountAmount) || 0;
                let ship = parseInt(this.shippingCost) || 0;

                let total = this.subtotal - disc + ship;
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
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({
                            destination_district_id: this.destinationDistrictId,
                            destination_subdistrict_id: this.destinationSubdistrictId,
                            postal_code: this.destinationZipCode,
                            destination_text: this.searchQuery,
                            weight: finalWeight
                        })
                    });

                    const result = await response.json();

                    if (result.status === 'success') {
                        this.courierList = result.data;
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
                    if (data.status === 'success') { this.discountAmount = data.data.discount_amount; this.couponMessage = `✅ Kakak Hemat Rp ${this.rupiah(data.data.discount_amount)}`; }
                    else { this.discountAmount = 0; this.couponMessage = data.message; }
                } catch (error) { this.couponMessage = 'Gagal cek server.'; this.discountAmount = 0; } finally { this.isValidatingCoupon = false; }
            },

            // ---------------------------------------------------------
            // HAPUS function addToCart LAMA, GANTI DENGAN 3 FUNGSI INI:
            // ---------------------------------------------------------

            // 1. Method Add To Cart UTAMA
            async addToCart(id, name, price, maxStock, weight = 0, image = null, hasVariant = false) {
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
                let parsed = parseInt(item.qty);
                if (isNaN(parsed) || parsed < 1) { item.qty = 1; }
                else if (parsed > item.maxStock) { alert('Stok tidak mencukupi!'); item.qty = item.maxStock; }
                else { item.qty = parsed; }
                if(this.couponCode) this.checkCoupon();
            },

            removeFromCart(id) {
                this.cart = this.cart.filter(i => i.id !== id);
                if(this.cart.length === 0) { this.discountAmount = 0; this.couponMessage = ''; }
                else if(this.couponCode) { this.checkCoupon(); }
            },

            confirmClearCart() {
                if(confirm('Kosongkan keranjang?')) {
                    this.cart = []; this.uploadedFiles = []; this.discountAmount = 0; this.couponMessage = '';
                    this.shippingCost = 0; this.deliveryType = 'pickup'; this.searchQuery = '';
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
                // DANA tidak butuh validasi saldo di sisi client karena diproses di gateway
                }


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

                // Logic Customer ID lebih aman
            if(this.selectedCustomerId) {
                formData.append('customer_id', this.selectedCustomerId);
            }
            // Tetap kirim nama/phone manual sebagai fallback/guest
            formData.append('customer_name', this.customerName || 'Guest');
            formData.append('customer_phone', this.customerPhone || '');

                if(this.paymentMethod === 'cash') formData.append('cash_amount', this.cashAmount);
                if(this.paymentMethod === 'affiliate_balance') formData.append('affiliate_pin', this.affiliatePin);

                this.uploadedFiles.forEach((item, index) => {
                    formData.append(`attachments[${index}]`, item.file);

                    formData.append(`attachment_details[${index}][color]`, item.isColor ? 'Color' : 'BW');
                    formData.append(`attachment_details[${index}][size]`, item.paperSize);
                    formData.append(`attachment_details[${index}][qty]`, item.qty);
                });

                try {
                    const response = await fetch("{{ route('orders.store') }}", {
                        method: "POST",
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Accept': 'application/json'
                        },
                        body: formData
                    });

                    const contentType = response.headers.get("content-type");
                    if (!contentType || !contentType.includes("application/json")) {
                        throw new Error("Terjadi kesalahan Server (Error 500).");
                    }

                    const result = await response.json();
                    console.log("LOG: Response Server Diterima:", result);

                    // --- KODE BARU (UNTUK INVOICE) ---
                    if (result.status === 'success') {

                        // 1. Cek apakah harus bayar online (DANA/Tripay)
                        if (result.payment_url) {
                            console.log("LOG: Redirecting ke Payment Gateway...");
                            window.location.href = result.payment_url;
                            return;
                        }

                        // Menggunakan Blade URL Helper agar path "/percetakan/public" otomatis masuk
                        window.location.href = "{{ url('/invoice') }}/" + result.invoice;

                    } else {
                        throw new Error(result.message);
                    }
                } catch (error) {
                    console.error("LOG ERROR:", error);
                    alert('❌ Gagal: ' + error.message);
                } finally {
                    this.isProcessing = false;
                }
            }
        }
    }
