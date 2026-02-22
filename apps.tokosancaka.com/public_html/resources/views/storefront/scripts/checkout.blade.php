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
           // FUNGSI BARU: Pembaca Data "Sapu Jagat" Kebal Error
            formatLocationName(loc) {
                if (!loc) return '';
                if (typeof loc === 'string') return loc;

                // 1. Cek format standar
                if (loc.text) return loc.text;
                if (loc.label) return loc.label;

                // 2. Ekstrak format KiriminAja V3 (Bahasa Inggris)
                let nameParts = [];
                if (loc.subdistrict_name) nameParts.push(loc.subdistrict_name);
                if (loc.district_name) nameParts.push(loc.district_name);
                if (loc.city_name) nameParts.push(loc.city_name);
                if (loc.province_name) nameParts.push(loc.province_name);

                if (nameParts.length > 0) {
                    let zip = loc.zipcode || loc.kodepos || '';
                    return nameParts.join(', ') + (zip ? ` - ${zip}` : '');
                }

                // 3. JURUS SAPU JAGAT: Jika format di atas masih gagal,
                // Ambil SEMUA data bertipe TEKS/HURUF dari dalam JSON dan gabungkan!
                let stringValues = Object.values(loc).filter(val => typeof val === 'string' && isNaN(val));
                if (stringValues.length > 0) {
                    return stringValues.join(', ');
                }

                return 'Alamat Ditemukan (Pilih ini)';
            },

            selectLocation(loc) {
                // Tampilkan nama yang sudah dirapikan ke form input
                this.destinationText = this.formatLocationName(loc);

                // Ambil ID Kecamatan (Bisa district_id atau kecamatan_id)
                this.districtId = loc.district_id || loc.kecamatan_id || loc.id || '';
                this.subdistrictId = loc.subdistrict_id || loc.kelurahan_id || '';

                this.locationSearch = '';
                this.locationResults = [];

                // Langsung tembak API Ongkir!
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

            async submitOrder() {
                // Tampilkan Loading
                Swal.fire({
                    title: 'Memproses Pesanan...',
                    text: 'Mohon tunggu sebentar',
                    allowOutsideClick: false,
                    didOpen: () => { Swal.showLoading() }
                });

                const formData = new FormData(document.getElementById('checkoutForm'));

                try {
                    const response = await fetch("{{ route('storefront.process', $subdomain) }}", {
                        method: 'POST',
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        body: formData
                    });

                    const result = await response.json();

                    if (response.ok && result.status === 'success') {
                        localStorage.removeItem('sancaka_cart_' + this.tenantId);

                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: 'Pesanan Anda telah diterima. Silakan selesaikan pembayaran.',
                            confirmButtonText: 'Bayar Sekarang'
                        }).then(() => {
                            // Jika ada payment_url (DANA/Tripay), arahkan ke sana
                            if(result.payment_url) {
                                window.location.href = result.payment_url;
                            } else {
                                window.location.href = "{{ url('/checkout/success') }}/" + result.invoice;
                            }
                        });
                    } else {
                        // Munculkan Modal Error jika saldo tidak cukup atau data tidak lengkap
                        Swal.fire({
                            icon: 'error',
                            title: 'Pemesanan Gagal',
                            text: result.message || 'Terjadi kesalahan sistem.'
                        });
                    }
                } catch (error) {
                    Swal.fire({ icon: 'error', title: 'Error', text: 'Koneksi ke server terputus.' });
                }
            }
        }))
    })
</script>
