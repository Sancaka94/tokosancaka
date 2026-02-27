{{-- SCRIPT ALPINE JS --}}
<script>
    function productManager() {
        return {
            // --- STATE UNTUK MODAL VARIAN ---
            variantModalOpen: false,
            isLoadingVariant: false,
            isSavingVariant: false,
            activeProductId: null,
            activeProductName: '',
            variants: [],

            // 1. Buka Modal & Fetch Data
            async openVariantModal(productId) {
                this.activeProductId = productId;
                this.variantModalOpen = true;
                this.isLoadingVariant = true;
                this.variants = []; // Reset dulu

                try {
                    let url = `/products/${productId}/variants`;
                    let response = await fetch(url);
                    if (!response.ok) throw new Error('Gagal ambil data');

                    let data = await response.json();

                    this.activeProductName = data.product_name;
                    // Map data dari DB ke struktur JS (termasuk barcode & diskon)
                    this.variants = data.variants.map(v => ({
                        id: v.id,
                        name: v.name,
                        price: v.price,
                        stock: v.stock,
                        sku: v.sku,
                        barcode: v.barcode || '',
                        // 👇 TAMBAHKAN DISKON VARIAN DARI DB 👇
                        discount_type: v.discount_type || 'percent',
                        discount_value: v.discount_value || 0,

                        // Mapping Sub Varian dari Response Controller
                        sub_variants: v.sub_variants ? v.sub_variants.map(sub => ({
                            id: sub.id,
                            name: sub.name,
                            price: sub.price,
                            stock: sub.stock,
                            weight: sub.weight || 0,
                            sku: sub.sku,
                            barcode: sub.barcode || '',
                            // 👇 TAMBAHKAN DISKON SUB VARIAN DARI DB 👇
                            discount_type: sub.discount_type || 'percent',
                            discount_value: sub.discount_value || 0
                        })) : []
                    }));

                } catch (error) {
                    console.error(error);
                    alert('Terjadi kesalahan saat mengambil data varian.');
                    this.variantModalOpen = false;
                } finally {
                    this.isLoadingVariant = false;
                }
            },

            // 2. Tambah Baris Baru di Modal (VARIAN UTAMA)
            addVariantRow() {
                this.variants.push({
                    name: '',
                    price: 0,
                    stock: 0,
                    sku: '',
                    barcode: '',
                    // 👇 DEFAULT DISKON VARIAN BARU 👇
                    discount_type: 'percent',
                    discount_value: 0,
                    sub_variants: [] // Array kosong buat nampung sub varian
                });
                this.scrollToBottom();
            },

            // TAMBAHAN: Tambah Sub Varian di Bawah Varian Tertentu
            addSubVariantRow(variantIndex) {
                if(!this.variants[variantIndex].sub_variants) {
                    this.variants[variantIndex].sub_variants = [];
                }
                this.variants[variantIndex].sub_variants.push({
                    name: '',
                    price: this.variants[variantIndex].price, // Default ambil harga induknya
                    stock: 0,
                    weight: 0,
                    sku: '',
                    barcode: '',
                    // 👇 DEFAULT DISKON SUB VARIAN BARU 👇
                    discount_type: 'percent',
                    discount_value: 0
                });
            },

            // TAMBAHAN: Hapus Sub Varian
            removeSubVariantRow(variantIndex, subIndex) {
                this.variants[variantIndex].sub_variants.splice(subIndex, 1);
            },

            calculateTotalStock(vIndex) {
                let variant = this.variants[vIndex];
                if (variant.sub_variants && variant.sub_variants.length > 0) {
                    let total = 0;
                    variant.sub_variants.forEach(sub => total += parseInt(sub.stock) || 0);
                    variant.stock = total;
                }
            },

            scrollToBottom() {
                this.$nextTick(() => {
                    let container = document.querySelector('.overflow-y-auto');
                    if(container) container.scrollTop = container.scrollHeight;
                });
            },

            // 3. Hapus Baris di Modal
            removeVariantRow(index) {
                this.variants.splice(index, 1);
            },

            // 4. Simpan ke Database (VERSI FULL LOGGING)
            async saveVariants() {
                // LOG 1: Cek Data Sebelum Dikirim
                console.log("🔥 LOG 1 [START]: Memulai proses simpan...");
                console.log("🔥 LOG 2 [DATA]: Data Varian yang akan dikirim:", JSON.parse(JSON.stringify(this.variants)));

                // Validasi Sederhana
                if (this.variants.length > 0) {
                    for (let v of this.variants) {
                        if (!v.name || v.name.trim() === '') {
                            console.warn("⚠️ LOG [VALIDASI]: Nama varian kosong ditemukan!");
                            alert('Nama varian tidak boleh kosong!');
                            return;
                        }
                    }
                }

                this.isSavingVariant = true;

                try {
                    // Cek URL Target
                    let url = `/products/${this.activeProductId}/variants`;
                    console.log("🔥 LOG 3 [URL]: Menembak ke ->", url);

                    let response = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json', // PENTING: Minta balasan JSON
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            variants: this.variants
                        })
                    });

                    console.log("🔥 LOG 4 [STATUS]: HTTP Status Code ->", response.status);

                    // JIKA ERROR (Bukan 200 OK)
                    if (!response.ok) {
                        let errorText = await response.text();

                        console.error("❌ LOG 5 [SERVER ERROR MESSAGE]:");
                        console.error("---------------------------------------------------");
                        console.error(errorText);
                        console.error("---------------------------------------------------");

                        throw new Error(`Server Error: ${response.status}`);
                    }

                    // JIKA SUKSES
                    let result = await response.json();
                    console.log("✅ LOG 6 [RESPONSE]:", result);

                    if (result.success) {
                        alert('Berhasil! Varian dan Stok telah diperbarui.');
                        this.variantModalOpen = false;
                        window.location.reload();
                    } else {
                        console.warn("⚠️ LOG [GAGAL LOGIC]:", result.message);
                        alert('Gagal: ' + (result.message || 'Terjadi kesalahan.'));
                    }

                } catch (error) {
                    console.error("❌ LOG [EXCEPTION]: Javascript Error ->", error);
                    alert('Gagal menghubungi server. Cek Console (F12) untuk melihat LOG merah.');
                } finally {
                    this.isSavingVariant = false;
                }
            }
        };
    }
</script>
