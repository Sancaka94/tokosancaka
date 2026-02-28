<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('checkoutData', () => ({
            // ==========================================
            // 1. STATE & VARIABEL
            // ==========================================

            // Data Pesanan
            cartItems: [],
            customerName: '',
            customerPhone: '',

            // Data Pengiriman
            deliveryType: 'shipping',
            locationSearch: '',
            locationResults: [],
            destinationText: '',
            districtId: '',
            subdistrictId: '',

            // Data Kurir
            shippingRates: [],
            selectedRate: null,
            courierName: '',
            courierCode: '',
            serviceType: '',
            shippingCost: 0,

            // Data Kupon
            couponInput: '',
            appliedCoupon: '',
            discountAmount: 0,
            couponMessage: '',
            couponStatus: '',

            // Data Pembayaran & Tripay
            paymentMethod: '',
            selectedTripayChannel: '',
            tripayChannels: [], // Dikosongkan agar murni diisi oleh API Backend
            isLoadingChannels: false, // Indikator saat API sedang dipanggil

            // Status Loading Sistem
            isSearchingLoc: false,
            isLoadingRates: false,
            isCheckingCoupon: false,

            // ==========================================
            // 2. LOGIKA PROMO & KALKULASI
            // ==========================================

            get hasFreeOngkirProduct() {
                return this.cartItems.some(item => item.is_free_ongkir == 1 || item.is_free_ongkir === true);
            },

            get shippingDiscount() {
                if (this.deliveryType === 'shipping' && this.hasFreeOngkirProduct) {
                    return parseInt(this.shippingCost || 0);
                }
                return 0;
            },

            calculateTripayFee(channel) {
                let baseTotal = this.subtotal - this.discountAmount;
                if(baseTotal < 0) baseTotal = 0;

                if(this.deliveryType === 'shipping') {
                    baseTotal += Math.max(0, parseInt(this.shippingCost || 0) - this.shippingDiscount);
                }

                // Mengakomodasi berbagai kemungkinan key JSON dari API backend Anda
                let feeFlat = parseFloat(channel.fee_flat || channel.flat_fee || channel.fee_customer?.flat || 0);
                let feePercent = parseFloat(channel.fee_percent || channel.percent_fee || channel.fee_customer?.percent || 0);

                let percentageFee = baseTotal * (feePercent / 100);
                return feeFlat + percentageFee;
            },

            get paymentAdminFee() {
                if (this.paymentMethod === 'tripay' && this.selectedTripayChannel) {
                    let channel = this.tripayChannels.find(c => c.code === this.selectedTripayChannel);
                    if (channel) return this.calculateTripayFee(channel);
                }
                return 0;
            },

            // ==========================================
            // 3. KALKULASI TOTAL AKHIR
            // ==========================================

            get subtotal() {
                return this.cartItems.reduce((sum, item) => sum + (item.qty * item.price), 0);
            },

            get totalWeight() {
                return this.cartItems.reduce((sum, item) => sum + (item.qty * (item.weight || 1000)), 0);
            },

            get finalTotal() {
                let total = this.subtotal - this.discountAmount;
                if(total < 0) total = 0;

                if(this.deliveryType === 'shipping') {
                    let finalShippingCost = parseInt(this.shippingCost || 0) - this.shippingDiscount;
                    total += Math.max(0, finalShippingCost);
                }

                total += this.paymentAdminFee;

                return Math.round(total);
            },

            get isReadyToPay() {
                if(this.cartItems.length === 0) return false;
                if(!this.customerName || !this.customerPhone) return false;
                if(this.deliveryType === 'shipping' && (!this.districtId || !this.courierName)) return false;
                if(this.paymentMethod === 'tripay' && !this.selectedTripayChannel) return false;
                return true;
            },

            // ==========================================
            // 4. INISIALISASI & FUNGSI UTILITAS
            // ==========================================

            initCheckout() {
                const saved = localStorage.getItem('sancaka_cart_{{ $tenant->id ?? 1 }}');
                if (saved) Object.assign(this, { cartItems: JSON.parse(saved) });

                if(this.cartItems.length === 0) {
                    window.location.href = "{{ route('storefront.index', $subdomain) }}";
                }

                // Watcher untuk mereset pilihan bank dan trigger API Tripay
                this.$watch('paymentMethod', value => {
                    if (value === 'tripay') {
                        this.fetchPaymentChannels();
                    } else {
                        this.selectedTripayChannel = '';
                    }
                });
            },

            formatRupiah(angka) {
                return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(angka);
            },

            // ==========================================
            // 5. FUNGSI GET API CHANNEL PEMBAYARAN (TRIPAY)
            // ==========================================

            async fetchPaymentChannels() {
                // Jangan panggil API lagi jika data channel sudah terisi
                if (this.tripayChannels.length > 0) return;

                this.isLoadingChannels = true;
                try {
                    const res = await fetch(`{{ route('storefront.api.payment-channels', $subdomain ?? '') }}`);
                    const json = await res.json();

                    if(json.status === 'success' || json.success) {
                        this.tripayChannels = json.data;
                    } else {
                        console.error("Gagal memuat channel:", json.message);
                    }
                } catch (e) {
                    console.error("Fetch Payment Channels Error:", e);
                } finally {
                    this.isLoadingChannels = false;
                }
            },

            // ==========================================
            // 6. FUNGSI LOKASI & API ONGKIR
            // ==========================================

            async searchLocation() {
                if(this.locationSearch.length < 3) return;
                this.isSearchingLoc = true;
                try {
                    const res = await fetch(`{{ route('storefront.api.location') }}?query=${this.locationSearch}`);
                    const json = await res.json();
                    if(json.status === 'success') this.locationResults = json.data;
                } catch (e) { console.error("Loc Search Error", e); }
                this.isSearchingLoc = false;
            },

            formatLocationName(loc) {
                if (!loc) return '';
                if (typeof loc === 'string') return loc;
                if (loc.text) return loc.text;
                if (loc.label) return loc.label;

                let nameParts = [];
                if (loc.subdistrict_name) nameParts.push(loc.subdistrict_name);
                if (loc.district_name) nameParts.push(loc.district_name);
                if (loc.city_name) nameParts.push(loc.city_name);
                if (loc.province_name) nameParts.push(loc.province_name);

                if (nameParts.length > 0) {
                    let zip = loc.zipcode || loc.kodepos || '';
                    return nameParts.join(', ') + (zip ? ` - ${zip}` : '');
                }

                let stringValues = Object.values(loc).filter(val => typeof val === 'string' && isNaN(val));
                if (stringValues.length > 0) return stringValues.join(', ');
                return 'Alamat Ditemukan';
            },

            selectLocation(loc) {
                this.destinationText = this.formatLocationName(loc);
                this.districtId = loc.district_id || loc.kecamatan_id || loc.id || '';
                this.subdistrictId = loc.subdistrict_id || loc.kelurahan_id || '';
                this.locationSearch = '';
                this.locationResults = [];

                if(this.districtId) this.fetchOngkir();
            },

            resetLocation() {
                this.destinationText = '';
                this.districtId = '';
                this.subdistrictId = '';
                this.shippingRates = [];
                this.selectedRate = null;
                this.shippingCost = 0;
                this.courierName = '';
            },

            async fetchOngkir() {
                if (!this.districtId) return;
                this.isLoadingRates = true;
                this.shippingRates = [];

                const payload = {
                    origin_district_id: '{{ $tenant->district_id ?? "" }}',
                    destination_district_id: this.districtId,
                    destination_subdistrict_id: this.subdistrictId,
                    weight: this.totalWeight,
                    _token: '{{ csrf_token() }}'
                };

                try {
                    const res = await fetch(`{{ route('storefront.api.ongkir') }}`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                        body: JSON.stringify(payload)
                    });
                    const json = await res.json();
                    if (json.status === 'success') {
                        this.shippingRates = json.data;
                    } else {
                        alert(json.message || "Gagal memuat tarif pengiriman.");
                    }
                } catch (e) {
                    alert("Terjadi kesalahan saat menghubungi server pengiriman.");
                } finally {
                    this.isLoadingRates = false;
                }
            },

            applyShippingRate(rate) {
                this.selectedRate = rate;
                this.shippingCost = parseInt(rate.cost || 0);
                this.courierName = rate.name;
                this.courierCode = rate.courier_code;
                this.serviceType = rate.service_type;
            },

            // ==========================================
            // 7. FUNGSI API KUPON
            // ==========================================

            async checkCoupon() {
                this.isCheckingCoupon = true;
                this.couponMessage = '';
                try {
                    const res = await fetch(`{{ route('storefront.api.coupon') }}`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                        body: JSON.stringify({
                            coupon_code: this.couponInput,
                            total_belanja: this.subtotal,
                            _token: '{{ csrf_token() }}'
                        })
                    });
                    const json = await res.json();
                    if(json.status === 'success') {
                        this.discountAmount = json.data.discount_amount;
                        this.appliedCoupon = json.data.code;
                        this.couponStatus = 'success';
                        this.couponMessage = `Kupon Berhasil! Diskon Rp ${new Intl.NumberFormat('id-ID').format(this.discountAmount)}`;
                    } else {
                        this.discountAmount = 0;
                        this.appliedCoupon = '';
                        this.couponStatus = 'error';
                        this.couponMessage = json.message;
                    }
                } catch (e) {
                    this.couponMessage = "Gagal memvalidasi kupon.";
                    this.couponStatus = 'error';
                }
                this.isCheckingCoupon = false;
            },

            // ==========================================
            // 8. PROSES SUBMIT PEMESANAN
            // ==========================================

            async submitOrder() {
                if(!document.querySelector('input[name="payment_method"]:checked')) {
                    alert("Silakan pilih Metode Pembayaran terlebih dahulu!");
                    return;
                }

                this.isReadyToPay = false;
                const formData = new FormData(document.getElementById('checkoutForm'));

                // Inject data hasil kalkulasi ke Backend
                formData.append('final_total', this.finalTotal);
                formData.append('shipping_discount', this.shippingDiscount);
                formData.append('admin_fee', this.paymentAdminFee);

                if(this.paymentMethod === 'tripay') {
                    formData.append('payment_channel', this.selectedTripayChannel);
                }

                try {
                    const response = await fetch("{{ route('storefront.process', $subdomain) }}", {
                        method: 'POST',
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        body: formData
                    });

                    const result = await response.json();

                    if (response.ok && result.status === 'success') {
                        localStorage.removeItem('sancaka_cart_{{ $tenant->id ?? 1 }}');

                        if (result.payment_url && result.payment_url !== null) {
                            window.location.href = result.payment_url;
                        } else {
                            window.location.href = "/invoice/" + result.invoice;
                        }
                    } else {
                        alert("⚠️ GAGAL PROSES PESANAN:\n" + (result.message || "Terjadi kesalahan sistem."));
                        this.isReadyToPay = true;
                    }
                } catch (error) {
                    console.error("Submit Error:", error);
                    alert("Terjadi kesalahan jaringan atau server.");
                    this.isReadyToPay = true;
                }
            }
        }));
    });
</script>
