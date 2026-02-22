<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('checkoutData', () => ({
            cartItems: [],
            customerName: '',
            customerPhone: '',

            // Pengiriman
            deliveryType: 'shipping',
            locationSearch: '',
            locationResults: [],
            destinationText: '',
            districtId: '',
            subdistrictId: '',

            // Kurir
            shippingRates: [],
            selectedRate: null,
            courierName: '',
            courierCode: '',
            serviceType: '',
            shippingCost: 0,

            // Kupon
            couponInput: '',
            appliedCoupon: '',
            discountAmount: 0,
            couponMessage: '',
            couponStatus: '',

            // Status Loading
            isSearchingLoc: false,
            isLoadingRates: false,
            isCheckingCoupon: false,

            initCheckout() {
                const saved = localStorage.getItem('sancaka_cart_{{ $tenant->id }}');
                if (saved) {
                    this.cartItems = JSON.parse(saved);
                }
                if(this.cartItems.length === 0) {
                    window.location.href = "{{ route('storefront.index', $subdomain) }}";
                }
            },

            get subtotal() {
                return this.cartItems.reduce((sum, item) => sum + (item.qty * item.price), 0);
            },
            get totalWeight() {
                return this.cartItems.reduce((sum, item) => sum + (item.qty * (item.weight || 1000)), 0);
            },
            get finalTotal() {
                let total = this.subtotal - this.discountAmount;
                if(total < 0) total = 0;
                if(this.deliveryType === 'shipping') total += parseInt(this.shippingCost || 0);
                return total;
            },
            get isReadyToPay() {
                if(this.cartItems.length === 0) return false;
                if(!this.customerName || !this.customerPhone) return false;
                if(this.deliveryType === 'shipping' && (!this.districtId || !this.courierName)) return false;
                return true;
            },

            // CARI LOKASI
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

            // FUNGSI BARU: Pembaca Data Kebal Error
            formatLocationName(loc) {
                if (!loc) return '';
                if (typeof loc === 'string') return loc; // Jika API membalas array of string

                // Coba berbagai kemungkinan nama kolom dari KiriminAja/RajaOngkir
                if (loc.text) return loc.text;
                if (loc.label) return loc.label;
                if (loc.name && typeof loc.name === 'string') return loc.name;

                // Jika formatnya dipecah per wilayah
                let parts = [];
                if (loc.kelurahan || loc.village_name) parts.push(loc.kelurahan || loc.village_name);
                if (loc.kecamatan || loc.district_name || loc.district) parts.push(loc.kecamatan || loc.district_name || loc.district);
                if (loc.kabupaten || loc.kab_kota || loc.city_name || loc.city) parts.push(loc.kabupaten || loc.kab_kota || loc.city_name || loc.city);
                if (loc.provinsi || loc.province_name || loc.province) parts.push(loc.provinsi || loc.province_name || loc.province);

                if (parts.length > 0) return parts.join(', ');

                // Fallback Terakhir: Tampilkan data mentah agar kita tau apa isi kolom aslinya
                return JSON.stringify(loc).substring(0, 60) + '...';
            },

            selectLocation(loc) {
                this.destinationText = this.formatLocationName(loc);

                // Trik menangkap berbagai variasi ID Kecamatan dari API
                this.districtId = loc.kecamatan_id || loc.district_id || loc.id || '';
                this.subdistrictId = loc.kelurahan_id || loc.subdistrict_id || '';

                this.locationSearch = '';
                this.locationResults = [];

                // Lanjut cek ongkir jika ID ketemu
                if(this.districtId) {
                    this.fetchOngkir();
                } else {
                    alert('Data lokasi dari API tidak memiliki ID Kecamatan. Hubungi Admin.');
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

            // CEK ONGKIR
            async fetchOngkir() {
                if(!this.districtId) return;
                this.isLoadingRates = true;
                this.shippingRates = [];
                this.shippingCost = 0;

                const payload = {
                    weight: this.totalWeight,
                    destination_district_id: this.districtId,
                    destination_subdistrict_id: this.subdistrictId,
                    _token: '{{ csrf_token() }}'
                };

                try {
                    const res = await fetch(`{{ route('storefront.api.ongkir') }}`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                        body: JSON.stringify(payload)
                    });
                    const json = await res.json();
                    if(json.status === 'success') {
                        this.shippingRates = json.data;
                    } else {
                        alert(json.message || "Gagal mengambil tarif pengiriman.");
                    }
                } catch (e) { alert("Terjadi kesalahan jaringan."); }

                this.isLoadingRates = false;
            },

            applyShippingRate(rate) {
                this.shippingCost = rate.cost;
                this.courierName = rate.name;
                this.courierCode = rate.courier_code;
                this.serviceType = rate.service_type;
            },

            // CEK KUPON
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

            submitOrder(e) {
                const paymentMethod = document.querySelector('input[name="payment_method"]:checked');
                if(!paymentMethod) {
                    alert("Silakan pilih Metode Pembayaran terlebih dahulu!");
                    return;
                }
                e.target.submit();
            }
        }))
    })
</script>
