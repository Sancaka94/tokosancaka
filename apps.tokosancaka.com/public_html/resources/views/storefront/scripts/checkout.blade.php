<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('checkoutData', () => ({
            // State Keranjang & Customer
            cartItems: [],
            customerName: '',
            customerPhone: '',

            // State Pengiriman
            deliveryType: 'shipping',
            locationSearch: '',
            locationResults: [],
            destinationText: '',
            districtId: '',
            subdistrictId: '',

            // State Kurir & Biaya
            shippingRates: [],
            selectedRate: null,
            courierName: '',
            courierCode: '',
            serviceType: '',
            shippingCost: 0,

            // State Kupon
            couponInput: '',
            appliedCoupon: '',
            discountAmount: 0,
            couponMessage: '',
            couponStatus: '',

            // State Loading
            isSearchingLoc: false,
            isLoadingRates: false,
            isCheckingCoupon: false,

            // ==========================================
            // LOGIKA PROMO (GRATIS ONGKIR & CASHBACK)
            // ==========================================

            // Cek apakah ada produk berlabel Free Ongkir
            get hasFreeOngkirProduct() {
                return this.cartItems.some(item => item.is_free_ongkir == 1 || item.is_free_ongkir === true);
            },

            // Cek apakah ada produk berlabel Cashback Xtra
            get hasCashbackProduct() {
                return this.cartItems.some(item => item.is_cashback_extra == 1 || item.is_cashback_extra === true);
            },

            // Hitung nilai subsidi ongkir
            get shippingDiscount() {
                if (this.deliveryType === 'shipping' && this.hasFreeOngkirProduct) {
                    // Memotong 100% biaya ongkir.
                    // Jika ingin dibatasi maksimal Rp20.000, ganti dengan: return Math.min(parseInt(this.shippingCost || 0), 20000);
                    return parseInt(this.shippingCost || 0);
                }
                return 0;
            },

            // ==========================================
            // KALKULASI UTAMA
            // ==========================================

            get subtotal() {
                return this.cartItems.reduce((sum, item) => sum + (item.qty * item.price), 0);
            },

            get totalWeight() {
                return this.cartItems.reduce((sum, item) => sum + (item.qty * (item.weight || 1000)), 0);
            },

            get finalTotal() {
                // 1. Total Harga Barang dikurangi Diskon Kupon
                let total = this.subtotal - this.discountAmount;
                if(total < 0) total = 0; // Cegah minus

                // 2. Tambahkan sisa ongkir (jika dikirim via kurir)
                if(this.deliveryType === 'shipping') {
                    let finalShippingCost = parseInt(this.shippingCost || 0) - this.shippingDiscount;
                    total += Math.max(0, finalShippingCost); // Cegah ongkir minus
                }

                return total;
            },

            get isReadyToPay() {
                if(this.cartItems.length === 0) return false;
                if(!this.customerName || !this.customerPhone) return false;
                if(this.deliveryType === 'shipping' && (!this.districtId || !this.courierName)) return false;
                return true;
            },

            // ==========================================
            // INISIALISASI
            // ==========================================

            initCheckout() {
                const saved = localStorage.getItem('sancaka_cart_{{ $tenant->id ?? 1 }}');
                if (saved) {
                    this.cartItems = JSON.parse(saved);
                }
                // Jika keranjang kosong, tendang balik ke beranda
                if(this.cartItems.length === 0) {
                    window.location.href = "{{ route('storefront.index', $subdomain) }}";
                }
            },

            // ==========================================
            // FUNGSI LOKASI & ONGKIR
            // ==========================================

            async searchLocation() {
                if(this.locationSearch.length < 3) return;
                this.isSearchingLoc = true;
                try {
                    const res = await fetch(`{{ route('storefront.api.location') }}?query=${this.locationSearch}`);
                    const json = await res.json();
                    if(json.status === 'success') {
                        this.locationResults = json.data;
                    }
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

                return 'Alamat Ditemukan (Pilih ini)';
            },

            selectLocation(loc) {
                this.destinationText = this.formatLocationName(loc);
                this.districtId = loc.district_id || loc.kecamatan_id || loc.id || '';
                this.subdistrictId = loc.subdistrict_id || loc.kelurahan_id || '';

                this.locationSearch = '';
                this.locationResults = [];

                if(this.districtId) {
                    this.fetchOngkir();
                } else {
                    alert('Data dari API tidak memiliki ID Kecamatan (district_id).');
                }
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
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify(payload)
                    });

                    if (!res.ok) throw new Error('Network response was not ok');
                    const json = await res.json();

                    if (json.status === 'success') {
                        this.shippingRates = json.data;
                    } else {
                        console.error("API Error:", json.message);
                        alert(json.message || "Gagal memuat tarif pengiriman.");
                    }
                } catch (e) {
                    console.error("Fetch Error:", e);
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
            // FUNGSI KUPON & FORMATTER
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

            formatRupiah(angka) {
                return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(angka);
            },

            // ==========================================
            // PROSES SUBMIT PEMESANAN
            // ==========================================

            async submitOrder() {
                const paymentMethod = document.querySelector('input[name="payment_method"]:checked');
                if(!paymentMethod) {
                    alert("Silakan pilih Metode Pembayaran terlebih dahulu!");
                    return;
                }

                this.isReadyToPay = false;

                const formData = new FormData(document.getElementById('checkoutForm'));

                // Inject data penting hasil kalkulasi ke dalam FormData agar terbaca oleh Backend Controller
                formData.append('final_total', this.finalTotal);
                formData.append('shipping_discount', this.shippingDiscount);

                try {
                    const response = await fetch("{{ route('storefront.process', $subdomain) }}", {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest' // Penting untuk deteksi AJAX di Laravel
                        },
                        body: formData
                    });

                    const result = await response.json();

                    if (response.ok && result.status === 'success') {
                        // Bersihkan keranjang
                        localStorage.removeItem('sancaka_cart_{{ $tenant->id ?? 1 }}');

                        // Redirect ke Payment Gateway (jika ada) atau langsung ke Invoice
                        if (result.payment_url && result.payment_url !== null) {
                            window.location.href = result.payment_url;
                        } else {
                            window.location.href = "/invoice/" + result.invoice;
                        }

                    } else {
                        alert("⚠️ GAGAL PROSES PESANAN:\n" + (result.message || "Terjadi kesalahan sistem."));
                        this.isReadyToPay = true; // Nyalakan tombol lagi
                    }
                } catch (error) {
                    console.error("Submit Error:", error);
                    alert("Terjadi kesalahan jaringan atau server.");
                    this.isReadyToPay = true; // Nyalakan tombol lagi
                }
            }
        }));
    });
</script>
