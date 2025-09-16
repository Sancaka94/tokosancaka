@extends('layouts.app')

@section('title', 'Buat Pesanan Baru')

@push('styles')

{{-- Font Awesome untuk ikon --}}

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">


<style>

    /* Menggunakan variabel CSS untuk konsistensi tema */

    :root {

        --bs-primary-rgb: 220, 53, 69; /* Mengambil warna merah dari .bg-danger */

        --bs-font-sans-serif: 'Inter', system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", "Liberation Sans", sans-serif;

        --bs-body-bg: #f8f9fa;

        --card-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.05);

        --input-group-icon-width: 45px;

    }



    body {

        background-color: var(--bs-body-bg);

        font-family: var(--bs-font-sans-serif);

    }



    .card {

        border: none;

        border-radius: 0.75rem;

        box-shadow: var(--card-shadow);

        transition: all 0.3s ease-in-out;

    }

    

    .card:hover {

        transform: translateY(-3px);

        box-shadow: 0 0.75rem 1.5rem rgba(0, 0, 0, 0.07);

    }



    .card-header {

        background-color: #ffffff;

        border-bottom: 1px solid #dee2e6;

        font-weight: 600;

        font-size: 1.1rem;

        padding: 1rem 1.25rem;

        border-top-left-radius: 0.75rem;

        border-top-right-radius: 0.75rem;

    }



    .card-header .fa-stack {

        font-size: 0.6em;

    }



    .form-label {

        font-weight: 500;

        margin-bottom: 0.5rem;

        color: #495057;

    }

    

    /* Style untuk input group dengan ikon */

    .input-group-text {

        background-color: #f1f3f5;

        border: 1px solid #ced4da;

        width: var(--input-group-icon-width);

        justify-content: center;

        color: #6c757d;

    }

    

    .form-control:focus + .input-group-text,

    .form-select:focus + .input-group-text {

        border-color: #86b7fe;

        color: var(--bs-primary);

    }



    /* Style untuk hasil pencarian alamat/kontak (dipertahankan dari kode asli) */

    .search-results-container { 

        position: absolute; 

        z-index: 1000; 

        width: 100%; 

        background: #fff; 

        border: 1px solid #e9ecef; 

        border-top: none; 

        border-radius: 0 0 .5rem .5rem; 

        max-height: 250px; 

        overflow-y: auto; 

        box-shadow: 0 8px 15px rgba(0,0,0,0.1);

    }

    .search-result-item { padding: 12px 18px; cursor: pointer; font-size: 0.9rem; border-bottom: 1px solid #f1f1f1; }

    .search-result-item:last-child { border-bottom: none; }

    .search-result-item:hover { background: #f8f9fa; }

    .search-result-item .font-weight-bold { font-weight: 600; color: #343a40; }

    .search-result-item small { color: #6c757d; }

    

    /* Style untuk tombol pilihan custom (Ekspedisi & Pembayaran) */

    #selected_expedition_display, #paymentMethodButton { 

        cursor: pointer; 

        background-color: #fff; 

        transition: all 0.2s ease-in-out;

    }

    #selected_expedition_display:hover, #paymentMethodButton:hover {

        background-color: #e9ecef;

        border-color: #adb5bd;

    }

    

    #paymentOptionsList .list-group-item { cursor: pointer; transition: background-color 0.2s ease-in-out; }

    #paymentOptionsList .list-group-item.active { background-color: #fdebeb; border-color: var(--bs-primary); color: #000; font-weight: 500; }

    #paymentMethodButton img, #paymentOptionsList img { width: 32px; height: 32px; object-fit: contain; }



    /* Tombol Aksi Utama */

    .btn-primary {

        --bs-btn-bg: #dc3545;

        --bs-btn-border-color: #dc3545;

        --bs-btn-hover-bg: #bb2d3b;

        --bs-btn-hover-border-color: #b02a37;

        font-weight: 600;

        padding: 0.75rem 1.25rem;

    }



    .btn-success {

        font-weight: 600;

    }

    

    .main-content-container {

        padding-top: 6rem; /* Extra padding to offset the fixed header */

    }

    

/* Fokus → border & ikon biru */

.input-group:focus-within .input-group-text {

    color: #0d6efd;       /* biru Bootstrap */

    border-color: #0d6efd;

}



/* Sudah ada isi → ikon ikut biru */

.input-group.has-value .input-group-text {

    color: #0d6efd;

    border-color: #0d6efd;

}







.filled {

    border-color: #0d6efd !important;

    box-shadow: 0 0 0 2px rgba(13,110,253,.25) !important;

}







</style>

@endpush



@section('content')

<div class="container pb-4 pb-md-5 main-content-container">

    <div class="text-center mb-5">

        <h1 class="display-6 fw-bold">Buat Pesanan Baru</h1>

        <p class="text-muted">Lengkapi semua detail di bawah ini untuk melanjutkan.</p>

    </div>

    

    <form id="orderForm" action="{{ route('pesanan.public.store') }}" method="POST">

        @csrf

        <div class="row g-4 g-lg-5">

            {{-- Kolom Kiri: Informasi Pengirim & Penerima --}}

            <div class="col-lg-7">

                {{-- Card Pengirim --}}

                <div class="card mb-4">

                    <div class="card-header text-danger d-flex align-items-center">

                        <span class="fa-stack me-2">

                          <i class="fas fa-circle fa-stack-2x"></i>

                          <i class="fas fa-arrow-up fa-stack-1x fa-inverse"></i>

                        </span>

                        Informasi Pengirim

                    </div>

                    <div class="card-body p-4">

                        <div class="row g-3">

                            <div class="col-md-6">

                                <label for="sender_name" class="form-label">Nama Pengirim</label>

                                <div class="input-group">

                                    <span class="input-group-text"><i class="fas fa-user"></i></span>

                                    <input type="text" name="sender_name" id="sender_name" class="form-control" placeholder="John Doe" required>

                                </div>

                            </div>

                            <div class="col-md-6">

                                <label for="sender_phone" class="form-label">No. HP Pengirim</label>

                                <div class="input-group">

                                    <span class="input-group-text"><i class="fas fa-phone"></i></span>

                                    <input type="text" name="sender_phone" id="sender_phone" class="form-control" placeholder="08123456789" required>

                                </div>

                            </div>

                        </div>

                        

                        <div class="position-relative mt-3">

                            <label for="sender_address_search" class="form-label">Cari Alamat (Kec/Kel/Kodepos)</label>

                            <div class="input-group">

                                <span class="input-group-text"><i class="fas fa-search-location"></i></span>

                                <input type="text" id="sender_address_search" class="form-control" placeholder="Ketik disini untuk mencari..." autocomplete="off">

                            </div>

                            <div id="sender_address_results" class="search-results-container d-none"></div>

                        </div>

                        

                        <div class="mt-3">

                            <label for="sender_address" class="form-label">Detail Alamat Lengkap</label>

                             <div class="input-group">

                                <span class="input-group-text align-items-start pt-2"><i class="fas fa-map-marked-alt"></i></span>

                                <textarea name="sender_address" id="sender_address" rows="2" class="form-control" placeholder="Contoh: Jl. Merdeka No. 10, RT 01/RW 02 Perum. Sancaka Warna Cet Biru" required></textarea>

                            </div>

                        </div>



                        {{-- Hidden inputs tidak diubah --}}

                        <input type="hidden" name="pengirim_id" id="pengirim_id">

                        <input type="hidden" name="sender_lat" id="sender_lat"><input type="hidden" name="sender_lng" id="sender_lng">

                        <input type="hidden" name="sender_province" id="sender_province" required><input type="hidden" name="sender_regency" id="sender_regency" required>

                        <input type="hidden" name="sender_district" id="sender_district" required><input type="hidden" name="sender_village" id="sender_village" required>

                        <input type="hidden" name="sender_postal_code" id="sender_postal_code" required>

                        <input type="hidden" name="sender_district_id" id="sender_district_id" required>

                        <input type="hidden" name="sender_subdistrict_id" id="sender_subdistrict_id" required>



                        <div class="form-check mt-3"><input class="form-check-input" type="checkbox" name="save_sender" id="save_sender" value="1"><label class="form-check-label" for="save_sender">Simpan data pengirim ini di buku alamat</label></div>

                    </div>

                </div>



                {{-- Card Penerima --}}

                <div class="card">

                     <div class="card-header text-success d-flex align-items-center">

                        <span class="fa-stack me-2">

                          <i class="fas fa-circle fa-stack-2x"></i>

                          <i class="fas fa-map-marker-alt fa-stack-1x fa-inverse"></i>

                        </span>

                        Informasi Penerima

                     </div>

                     <div class="card-body p-4">

                         <div class="row g-3">

                             <div class="col-md-6">

                                <label for="receiver_name" class="form-label">Nama Penerima</label>

                                <div class="input-group">

                                    <span class="input-group-text"><i class="fas fa-user-friends"></i></span>

                                    <input type="text" name="receiver_name" id="receiver_name" class="form-control" placeholder="Jane Doe" required>

                                </div>

                             </div>

                             <div class="col-md-6">

                                <label for="receiver_phone" class="form-label">No. HP Penerima</label>

                                <div class="input-group">

                                    <span class="input-group-text"><i class="fas fa-mobile-alt"></i></span>

                                    <input type="text" name="receiver_phone" id="receiver_phone" class="form-control" placeholder="08987654321" required>

                                </div>

                            </div>

                         </div>

                        <div class="position-relative mt-3">

                            <label for="receiver_address_search" class="form-label">Cari Alamat (Kec/Kel/Kodepos)</label>

                            <div class="input-group">

                                <span class="input-group-text"><i class="fas fa-search-location"></i></span>

                                <input type="text" id="receiver_address_search" class="form-control" placeholder="Ketik disini untuk mencari..." autocomplete="off">

                            </div>

                            <div id="receiver_address_results" class="search-results-container d-none"></div>

                        </div>

                        <div class="mt-3">

                            <label for="receiver_address" class="form-label">Detail Alamat Lengkap</label>

                             <div class="input-group">

                                <span class="input-group-text align-items-start pt-2"><i class="fas fa-map-marked-alt"></i></span>

                                <textarea name="receiver_address" id="receiver_address" rows="2" class="form-control" placeholder="Contoh: Jl. Pahlawan No. 21, Dusun Mawar, Sebelah Toko Roti" required></textarea>

                            </div>

                        </div>



                        {{-- Hidden inputs tidak diubah --}}

                        <input type="hidden" name="penerima_id" id="penerima_id">

                        <input type="hidden" name="receiver_lat" id="receiver_lat"><input type="hidden" name="receiver_lng" id="receiver_lng">

                        <input type="hidden" name="receiver_province" id="receiver_province" required><input type="hidden" name="receiver_regency" id="receiver_regency" required>

                        <input type="hidden" name="receiver_district" id="receiver_district" required><input type="hidden" name="receiver_village" id="receiver_village" required>

                        <input type="hidden" name="receiver_postal_code" id="receiver_postal_code" required>

                        <input type="hidden" name="receiver_district_id" id="receiver_district_id" required>

                        <input type="hidden" name="receiver_subdistrict_id" id="receiver_subdistrict_id" required>



                        <div class="form-check mt-3"><input class="form-check-input" type="checkbox" name="save_receiver" id="save_receiver" value="1"><label class="form-check-label" for="save_receiver">Simpan data penerima ini di buku alamat</label></div>

                     </div>

                </div>

            </div>



            {{-- Kolom Kanan: Detail Paket & Aksi --}}

            <div class="col-lg-5">

                <div class="card position-sticky" style="top: 20px;">

                    <div class="card-header text-dark d-flex align-items-center">

                        <span class="fa-stack me-2 text-warning">

                          <i class="fas fa-circle fa-stack-2x"></i>

                          <i class="fas fa-box-open fa-stack-1x fa-inverse"></i>

                        </span>

                        Detail Paket & Pengiriman

                    </div>

                    <div class="card-body p-4 row g-3">

                        <div class="col-12">

                            <label for="item_description" class="form-label">Deskripsi Barang</label>

                            <div class="input-group">

                                <span class="input-group-text"><i class="fas fa-tag"></i></span>

                                <input type="text" name="item_description" id="item_description" class="form-control" placeholder="Contoh: Baju, Sepatu, Dokumen Penting" required>

                            </div>

                        </div>

                        <div class="col-md-6">

                            <label for="item_price" class="form-label">Harga Barang</label>

                            <div class="input-group">

                                <span class="input-group-text">Rp</span>

                                <input type="number" name="item_price" id="item_price" class="form-control" placeholder="50000" required min="1">

                            </div>

                        </div>

                        <div class="col-md-6">

                            <label for="weight" class="form-label">Berat</label>

                            <div class="input-group">

                                <span class="input-group-text"><i class="fas fa-weight-hanging"></i></span>

                                <input type="number" name="weight" id="weight" class="form-control" placeholder="1000" required min="1">

                                <span class="input-group-text">gr</span>

                            </div>

                        </div>

                        <div class="col-12"><label class="form-label">Dimensi (Opsional)</label></div>

                        <div class="col-4">

                            <div class="input-group">

                                <span class="input-group-text">P</span>

                                <input type="number" name="length" id="length" class="form-control" placeholder="cm">

                            </div>

                        </div>

                        <div class="col-4">

                            <div class="input-group">

                                <span class="input-group-text">L</span>

                                <input type="number" name="width" id="width" class="form-control" placeholder="cm">

                            </div>

                        </div>

                        <div class="col-4">

                            <div class="input-group">

                                <span class="input-group-text">T</span>

                                <input type="number" name="height" id="height" class="form-control" placeholder="cm">

                            </div>

                        </div>

                        <div class="col-md-6">

                            <label for="item_type" class="form-label">Jenis Barang</label>

                            <select name="item_type" id="item_type" class="form-select" required>

                                <option value="" disabled selected>Pilih...</option>

                                <option value="1">Elektronik</option>

<option value="2">Pakaian</option>

<option value="3">Pecah Belah</option>

<option value="4">Dokumen</option>

<option value="5">Rumah Tangga</option>

<option value="6">Aksesoris</option>

<option value="7">Makanan & Minuman</option>

<option value="8">Kosmetik & Perawatan</option>

<option value="9">Obat & Suplemen</option>

<option value="10">Alat Tulis & Kantor</option>

<option value="11">Mainan & Hobi</option>

<option value="12">Otomotif & Sparepart</option>

<option value="13">Bahan Bangunan</option>

<option value="14">Alat Kesehatan</option>

<option value="15">Peralatan Olahraga</option>

<option value="16">Produk Digital</option>

<option value="17">Hewan & Tanaman</option>

<option value="18">Perhiasan</option>

<option value="19">Furniture</option>

<option value="20">Lainnya</option>



                                </select>

                        </div>

                        <div class="col-md-6">

                            <label for="service_type" class="form-label">Jenis Layanan</label>

                            <select name="service_type" id="service_type" class="form-select" required>

                                <option value="" disabled selected>Pilih...</option>

                                <option value="regular">Regular</option><option value="express">Express</option><option value="sameday">Sameday</option>

                                <option value="instant">Instant</option><option value="cargo">Cargo</option>

                            </select>

                        </div>

                         <div class="col-12">

                            <label for="ansuransi" class="form-label">Asuransi</label>

                             <div class="input-group">

                                <span class="input-group-text"><i class="fas fa-shield-alt"></i></span>

                                <select name="ansuransi" id="ansuransi" class="form-select" required>

                                    <option value="tidak" selected>Tidak Pakai Asuransi</option>

                                    <option value="iya">Ya, Pakai Asuransi</option>

                                </select>

                            </div>

                        </div>

                        <hr class="my-3">

                        <div class="col-12">

                            <label for="selected_expedition_display" class="form-label">Pilih Ekspedisi</label>

                            <input type="text" id="selected_expedition_display" class="form-control text-center fw-bold" placeholder="Lengkapi data & klik di sini" readonly required>

                            <input type="hidden" name="expedition" id="expedition" required>

                        </div>

                        <div class="col-12">

                            <label for="paymentMethodButton" class="form-label">Metode Pembayaran</label>

                            <div id="paymentMethodButton" class="form-control d-flex justify-content-between align-items-center">

                                <div class="d-flex align-items-center"><img id="selectedPaymentLogo" src="https://cdn-icons-png.flaticon.com/512/2331/2331941.png" alt="Logo" class="me-2"><span id="selectedPaymentName">Pilih Pembayaran...</span></div><i class="fas fa-chevron-down text-muted"></i>

                            </div>

                            <input type="hidden" name="payment_method" id="payment_method" required>

                        </div>

                        <div class="d-grid gap-2 mt-4">

                            <button type="button" id="confirmBtn" class="btn btn-primary btn-lg shadow"><i class="fas fa-paper-plane me-2"></i>Buat Pesanan Sekarang</button>

                            <button type="button" id="cekOngkirWaBtn" class="btn btn-outline-success"><i class="fab fa-whatsapp me-2"></i>Tanya Ongkir via WA</button>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </form>

</div>



{{-- Modal Pilihan Ekspedisi (Struktur tidak diubah, hanya styling minor) --}}

<div class="modal fade" id="ongkirModal" tabindex="-1"><div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable"><div class="modal-content" style="border-radius: 0.75rem;"><div class="modal-header"><h5 class="modal-title fw-bold"><i class="fas fa-shipping-fast me-2 text-danger"></i>Pilihan Ekspedisi</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body" id="ongkirModalBody"></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button></div></div></div></div>



{{-- Modal Metode Pembayaran (Struktur tidak diubah, hanya styling minor) --}}

<div class="modal fade" id="paymentMethodModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered modal-dialog-scrollable"><div class="modal-content" style="border-radius: 0.75rem;"><div class="modal-header"><h5 class="modal-title fw-bold"><i class="fas fa-credit-card me-2 text-danger"></i>Pilih Metode Pembayaran</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><ul id="paymentOptionsList" class="list-group list-group-flush">

    <li class="list-group-item list-group-item-action d-flex align-items-center cod-payment-option" data-value="COD" data-label="COD Ongkir"><img src="{{ asset('public/assets/cod.png') }}" class="me-3">COD Ongkir</li>

    <li class="list-group-item list-group-item-action d-flex align-items-center cod-payment-option" data-value="CODBARANG" data-label="COD Barang + Ongkir"><img src="{{ asset('public/assets/cod.png') }}" class="me-3">COD Barang + Ongkir</li>

    <li class="list-group-item list-group-item-action d-flex align-items-center" data-value="PERMATAVA" data-label="Permata VA"><img src="{{ asset('public/assets/permata.webp') }}" class="me-3">Permata VA</li>

    <li class="list-group-item list-group-item-action d-flex align-items-center" data-value="BNIVA" data-label="BNI VA"><img src="{{ asset('public/assets/bni.webp') }}" class="me-3">BNI VA</li>

    <li class="list-group-item list-group-item-action d-flex align-items-center" data-value="BRIVA" data-label="BRI VA"><img src="{{ asset('public/assets/bri.webp') }}" class="me-3">BRI VA</li>

    <li class="list-group-item list-group-item-action d-flex align-items-center" data-value="MANDIRIVA" data-label="Mandiri VA"><img src="{{ asset('public/assets/mandiri.webp') }}" class="me-3">Mandiri VA</li>

    <li class="list-group-item list-group-item-action d-flex align-items-center" data-value="BCAVA" data-label="BCA VA"><img src="{{ asset('public/assets/bca.webp') }}" class="me-3">BCA VA</li>

    <li class="list-group-item list-group-item-action d-flex align-items-center" data-value="ALFAMART" data-label="Alfamart"><img src="{{ asset('public/assets/alfamart.webp') }}" class="me-3">Alfamart</li>

    <li class="list-group-item list-group-item-action d-flex align-items-center" data-value="INDOMARET" data-label="Indomaret"><img src="{{ asset('public/assets/indomaret.webp') }}" class="me-3">Indomaret</li>

    <li class="list-group-item list-group-item-action d-flex align-items-center" data-value="OVO" data-label="OVO"><img src="{{ asset('public/assets/ovo.webp') }}" class="me-3">OVO</li>

    <li class="list-group-item list-group-item-action d-flex align-items-center" data-value="DANA" data-label="DANA"><img src="{{ asset('public/assets/dana.webp') }}" class="me-3">DANA</li>

    <li class="list-group-item list-group-item-action d-flex align-items-center" data-value="SHOPEEPAY" data-label="ShopeePay"><img src="{{ asset('public/assets/shopeepay.webp') }}" class="me-3">ShopeePay</li>

    <li class="list-group-item list-group-item-action d-flex align-items-center" data-value="QRIS" data-label="QRIS"><img src="{{ asset('public/assets/qris2.png') }}" class="me-3">QRIS</li>

</ul></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button></div></div></div></div>

@endsection



@push('scripts')

{{-- SCRIPT TIDAK DIUBAH SAMA SEKALI SESUAI PERMINTAAN --}}

<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>



@if(session('success'))

<script>Swal.fire({ title: 'Berhasil!', text: "{{ session('success') }}", icon: 'success', confirmButtonColor: '#16a34a' });</script>

@endif

@if(session('error'))

<script>Swal.fire({ title: 'Gagal!', text: @json(session('error')), icon: 'error', confirmButtonColor: '#dc2626' });</script>

@endif



<script>

$(document).ready(function () {

    const ongkirModal = new bootstrap.Modal(document.getElementById('ongkirModal'));

    const paymentModal = new bootstrap.Modal(document.getElementById('paymentMethodModal'));

    let searchTimeout = null;



    const debounce = (func, delay) => {

        return (...args) => { clearTimeout(searchTimeout); searchTimeout = setTimeout(() => func.apply(this, args), delay); };

    };



    function formatRupiah(angka) { return 'Rp ' + parseInt(angka, 10).toLocaleString('id-ID'); }



    // FUNGSI Autocomplete Pencarian Alamat

    function setupAddressSearch(prefix) {

        const searchInput = $(`#${prefix}_address_search`);

        const resultsContainer = $(`#${prefix}_address_results`);

        searchInput.on('input', debounce(() => {

            const query = searchInput.val();

            if (query.length < 3) { resultsContainer.addClass('d-none'); return; }

            $.get("{{ route('api.address.search') }}", { search: query })

                .done(function(data) {

                    resultsContainer.html('').removeClass('d-none');

                    if (data && data.length > 0) {

                        data.forEach(item => {

                            const resultDiv = $(`<div class="search-result-item"><div class="font-weight-bold">${item.full_address}</div></div>`)

                            .on('click', function() {

                                searchInput.val(item.full_address);

                                const parts = item.full_address.split(',').map(s => s.trim());

                                $(`#${prefix}_village`).val(parts[0] || '').trigger('change');

                                $(`#${prefix}_district`).val(parts[1] || '').trigger('change');

                                $(`#${prefix}_regency`).val(parts[2] || '').trigger('change');

                                $(`#${prefix}_province`).val(parts[3] || '').trigger('change');

                                $(`#${prefix}_postal_code`).val(parts[4] || '').trigger('change');

                                $(`#${prefix}_district_id`).val(item.district_id).trigger('change');

                                $(`#${prefix}_subdistrict_id`).val(item.subdistrict_id).trigger('change');

                                resultsContainer.addClass('d-none');

                            });

                            resultsContainer.append(resultDiv);

                        });

                    } else { resultsContainer.html('<div class="p-3 text-muted">Alamat tidak ditemukan.</div>'); }

                })

                .fail(() => resultsContainer.html('<div class="p-3 text-danger">Gagal memuat data.</div>'));

        }, 400));

    }

    setupAddressSearch('sender');

    setupAddressSearch('receiver');



    // FUNGSI Cek Ongkir

    function runCekOngkir() {

        const required = { '#sender_village': 'Alamat Pengirim', '#receiver_village': 'Alamat Penerima', '#item_price': 'Harga Barang', '#weight': 'Berat', '#service_type': 'Jenis Layanan', '#ansuransi': 'Ansuransi' };

        let missing = Object.keys(required).filter(s => !$(s).val());

        if (missing.length > 0) { Swal.fire('Data Belum Lengkap', 'Harap lengkapi: ' + missing.map(s => required[s]).join(', '), 'warning'); return; }

        $('#ongkirModalBody').html(`<div class="text-center p-5"><div class="spinner-border text-danger"></div><p class="mt-2 text-muted">Memuat tarif...</p></div>`);

        ongkirModal.show();

        $.ajax({

            url: "{{ route('kirimaja.cekongkir') }}", type: "GET", data: $('#orderForm').serialize(),

            success: function (res) {

                const body = $('#ongkirModalBody').empty();

                let results = (res.results || []).concat((res.result || []).flatMap(v => v.costs.map(c => ({...c, service: v.name, service_name: `${v.name.toUpperCase()} - ${c.service_type}`, cost: c.price.total_price, etd: c.estimation || '-', setting: c.setting || {}, insurance: c.price.insurance_fee || 0 }))));

                if (results.length === 0) { body.html('<div class="alert alert-warning text-center">Layanan pengiriman tidak ditemukan.</div>'); return; }

                results.sort((a, b) => a.cost - b.cost).forEach(item => {

                    const isCod = item.cod;

                    const insuranceFee = item.insurance || 0;

                    const codFee = item.setting?.cod_fee_amount || 0;

                    const value = `${$('#service_type').val()}-${item.service}-${item.service_type}-${item.cost}-${insuranceFee}-${codFee}`;

                    let details = `<small class="text-muted d-block">Estimasi: ${item.etd}</small>`;

                    if ($('#ansuransi').val() == 'iya' && insuranceFee > 0) details += `<small class="text-muted d-block">Asuransi: ${formatRupiah(insuranceFee)}</small>`;

                    if (isCod && codFee > 0) details += `<small class="text-muted d-block">Biaya COD: ${formatRupiah(codFee)}</small>`;

                    if (isCod) details += `<small class="text-success fw-bold d-block">COD Tersedia</small>`;

                    const logo = `{{ asset('storage/logo-ekspedisi/') }}/${item.service.toLowerCase().replace(/\s+/g, '')}.png`;

                    body.append(`<div class="card mb-2 shadow-sm"><div class="card-body d-flex justify-content-between align-items-center p-3"><div class="d-flex align-items-center"><img src="${logo}" class="me-3" style="width:60px;object-fit:contain;" onerror="this.style.display='none'"><div class="flex-grow-1"><h6 class="mb-1 fw-bold">${item.service_name}</h6>${details}</div></div><div class="text-end"><small class="text-muted">Ongkir</small><strong class="d-block fs-5 text-danger">${formatRupiah(item.cost)}</strong><button type="button" class="btn btn-sm btn-danger mt-1 select-ongkir-btn" data-value="${value}" data-display="${item.service_name}" data-cod-supported="${isCod}"><i class="fas fa-check-circle me-1"></i>Pilih</button></div></div></div>`);

                });

            },

            error: (xhr) => $('#ongkirModalBody').html(`<div class="alert alert-danger text-center">${xhr.responseJSON?.message || 'Gagal mengambil data ongkir.'}</div>`)

        });

    }

    // SEMUA EVENT LISTENER

    $('#selected_expedition_display').on('click', runCekOngkir);

    $('input, select, textarea').on('change', () => { $('#expedition').val(''); $('#selected_expedition_display').val('Klik untuk Cek Ulang Ongkir'); $('.cod-payment-option').hide(); });

    $(document).on('click', '.select-ongkir-btn', function() {

        $('#expedition').val($(this).data('value'));

        $('#selected_expedition_display').val($(this).data('display'));

        if ($(this).data('cod-supported')) $('.cod-payment-option').show();

        else {

            if (['COD', 'CODBARANG'].includes($('#payment_method').val())) { $('#payment_method').val(''); $('#selectedPaymentName').text('Pilih...'); $('#selectedPaymentLogo').attr('src', 'https://cdn-icons-png.flaticon.com/512/2331/2331941.png'); }

            $('.cod-payment-option').hide();

        }

        ongkirModal.hide();

    });

    $('#paymentMethodButton').on('click', () => paymentModal.show());

    $('#paymentOptionsList .list-group-item').on('click', function() {

        $('#payment_method').val($(this).data('value'));

        $('#selectedPaymentName').text($(this).data('label'));

        $('#selectedPaymentLogo').attr('src', $(this).find('img').attr('src'));

        $('#paymentOptionsList .list-group-item').removeClass('active');

        $(this).addClass('active');

        paymentModal.hide();

    });

    $('.cod-payment-option').hide();

    $('#confirmBtn').on('click', (e) => {

        e.preventDefault();

        if (!$('#orderForm')[0].checkValidity()) { $('#orderForm')[0].reportValidity(); Swal.fire('Peringatan', 'Harap lengkapi semua field yang wajib diisi.', 'warning'); return; }

        Swal.fire({ title: 'Konfirmasi Pesanan', text: "Apakah semua data sudah benar?", icon: 'question', showCancelButton: true, confirmButtonText: 'Ya, Buat Pesanan', cancelButtonText: 'Batal' }).then((result) => { if (result.isConfirmed) $('#orderForm').submit(); });

    });

    $('#cekOngkirWaBtn').on('click', () => window.open(`https://wa.me/6285745808809?text=${encodeURIComponent(`Halo, saya mau tanya ongkir:\n\n*Dari:* ${$('#sender_address').val()}, ${$('#sender_village').val()}\n*Ke:* ${$('#receiver_address').val()}, ${$('#receiver_village').val()}\n*Berat:* ${$('#weight').val()} gr\n\nTerima kasih.`)}`, '_blank'));

    $(document).on('click', e => {

        if (!$(e.target).closest('#sender_address_search, #sender_address_results').length) $('#sender_address_results').addClass('d-none');

        if (!$(e.target).closest('#receiver_address_search, #receiver_address_results').length) $('#receiver_address_results').addClass('d-none');

    });

});


document.querySelectorAll('.input-group .form-control, .input-group .form-select, .input-group textarea')

    .forEach(el => {

        el.addEventListener('blur', function() {

            if (this.value.trim() !== "") {

                this.closest('.input-group').classList.add('has-value');

            } else {

                this.closest('.input-group').classList.remove('has-value');

            }

        });

    });

</script>

@endpush

