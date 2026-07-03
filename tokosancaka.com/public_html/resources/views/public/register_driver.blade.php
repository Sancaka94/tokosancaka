@extends('layouts.app')

@section('content')
<style>
    .register-card {
        border-radius: 1rem;
        border: none;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
        overflow: hidden;
    }
    .register-header {
        background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
        color: white;
        padding: 2.5rem 1.5rem;
        text-align: center;
    }
    .form-section-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 1.25rem;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .form-section-title i {
        color: #dc2626;
    }
    .form-label {
        font-weight: 600;
        color: #475569;
        font-size: 0.85rem;
    }
    .custom-input, .custom-select {
        background-color: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 0.5rem;
        padding: 0.75rem 1rem;
        font-size: 0.9rem;
        transition: all 0.2s ease-in-out;
    }
    .custom-input:focus, .custom-select:focus {
        background-color: #ffffff;
        border-color: #dc2626;
        box-shadow: 0 0 0 0.25rem rgba(220, 38, 38, 0.15);
    }
    .custom-file-input {
        background-color: #f8fafc;
        border: 1px dashed #cbd5e1;
        border-radius: 0.5rem;
        padding: 0.45rem;
        font-size: 0.8rem;
        transition: all 0.2s;
    }
    .custom-file-input:hover {
        border-color: #94a3b8;
        background-color: #f1f5f9;
    }
    .btn-get-location {
        background-color: #f1f5f9;
        color: #334155;
        border: 1px solid #cbd5e1;
        font-weight: 600;
        font-size: 0.9rem;
        transition: all 0.2s;
    }
    .btn-get-location:hover {
        background-color: #e2e8f0;
        color: #0f172a;
    }
    .tos-scroll-box {
        height: 250px;
        overflow-y: scroll;
        background-color: #f8fafc;
        border: 2px solid #e2e8f0;
        border-radius: 0.5rem;
        padding: 1rem;
        font-size: 0.85rem;
        color: #334155;
        line-height: 1.6;
    }
    .tos-scroll-box::-webkit-scrollbar { width: 8px; }
    .tos-scroll-box::-webkit-scrollbar-track { background: #f1f5f9; }
    .tos-scroll-box::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
    .tos-scroll-box::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
</style>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-xl-11 col-lg-12">
            
            <div class="card register-card">
                <div class="register-header">
                    <h2 class="fw-bold mb-2">Gabung Menjadi Mitra Driver Sancaka</h2>
                    <p class="mb-0 opacity-75">Formulir Pendaftaran Resmi Ojek Online (Ride) & Mobil (Car)</p>
                </div>

                <div class="card-body p-4 p-md-5">
                    
                    {{-- Alert Notifikasi --}}
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm" role="alert">
                            <i class="fa-solid fa-circle-check me-2 fs-5"></i>
                            <div>{{ session('success') }}</div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm" role="alert">
                            <i class="fa-solid fa-triangle-exclamation me-2"></i> {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="alert alert-danger rounded-3 shadow-sm">
                            <div class="fw-bold mb-2"><i class="fa-solid fa-circle-exclamation me-2"></i> Pendaftaran Gagal! Periksa kembali berkas input Anda:</div>
                            <ul class="mb-0 ps-3">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('driver.register.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        
                        <div class="row g-5">
                            {{-- ================= KOLOM KIRI (Informasi Pribadi & Kendaraan) ================= --}}
                            <div class="col-lg-6">
                                
                                <div class="form-section-title border-bottom pb-2">
                                    <i class="fa-solid fa-id-card"></i> Identitas Diri Pelamar
                                </div>
                                
                                <div class="row g-3 mb-4">
                                    <div class="col-12">
                                        <label class="form-label">Nama Lengkap Sesuai KTP <span class="text-danger">*</span></label>
                                        <input type="text" name="nama_lengkap" class="form-control custom-input w-100 @error('nama_lengkap') is-invalid @enderror" value="{{ old('nama_lengkap') }}" required placeholder="Contoh: Amal Abu Kholid">
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Tempat Lahir <span class="text-danger">*</span></label>
                                        <input type="text" name="tempat_lahir" class="form-control custom-input w-100 @error('tempat_lahir') is-invalid @enderror" value="{{ old('tempat_lahir') }}" required placeholder="Contoh: Ngawi">
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Tanggal Lahir <span class="text-danger">*</span></label>
                                        <input type="date" name="tanggal_lahir" class="form-control custom-input w-100 @error('tanggal_lahir') is-invalid @enderror" value="{{ old('tanggal_lahir') }}" required>
                                        <small class="text-muted d-block" style="font-size: 0.7rem;">Batas usia standar minimal 18 tahun.</small>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Nomor NIK KTP <span class="text-danger">*</span></label>
                                        <input type="number" name="nomor_nik" class="form-control custom-input w-100 @error('nomor_nik') is-invalid @enderror" value="{{ old('nomor_nik') }}" required placeholder="16 Digit NIK">
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Nomor Kartu Keluarga (KK)</label>
                                        <input type="number" name="nomor_kk" class="form-control custom-input w-100 @error('nomor_kk') is-invalid @enderror" value="{{ old('nomor_kk') }}" placeholder="16 Digit Nomor KK">
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label">Nomor WhatsApp Aktif <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-end-0"><i class="fa-brands fa-whatsapp text-success"></i></span>
                                            <input type="text" name="nomor_wa" class="form-control custom-input border-start-0 @error('nomor_wa') is-invalid @enderror" value="{{ old('nomor_wa') }}" required placeholder="Contoh: 085745808809">
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label">Alamat Domisili Sekarang <span class="text-danger">*</span></label>
                                        <textarea name="alamat_lengkap" class="form-control custom-input w-100 @error('alamat_lengkap') is-invalid @enderror" rows="2" required placeholder="Tuliskan alamat rumah lengkap beserta RT/RW, Kecamatan, dan Kabupaten">{{ old('alamat_lengkap') }}</textarea>
                                    </div>
                                </div>

                                <div class="form-section-title border-bottom pb-2">
                                    <i class="fa-solid fa-motorcycle"></i> Detail Layanan & Spesifikasi Kendaraan
                                </div>
                                <div class="row g-3 mb-4">
                                    <div class="col-12">
                                        <label class="form-label">Jenis Layanan Standar Mitra <span class="text-danger">*</span></label>
                                        <select name="jenis_layanan" class="form-select custom-select w-100 @error('jenis_layanan') is-invalid @enderror" required>
                                            <option value="" selected disabled>-- Pilih Jenis Kendaraan Operasional --</option>
                                            <option value="motor" {{ old('jenis_layanan') == 'motor' ? 'selected' : '' }}>Sancaka RIDE (Ojek Motor - Maks. 250cc)</option>
                                            <option value="mobil" {{ old('jenis_layanan') == 'mobil' ? 'selected' : '' }}>Sancaka CAR (Mobil - Min. 1000cc)</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Merek & Tipe <span class="text-danger">*</span></label>
                                        <input type="text" name="merk_kendaraan" class="form-control custom-input w-100" value="{{ old('merk_kendaraan') }}" required placeholder="Honda Vario 150">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Tahun Pembuatan <span class="text-danger">*</span></label>
                                        <input type="number" name="tahun_kendaraan" class="form-control custom-input w-100" value="{{ old('tahun_kendaraan') }}" required placeholder="2022">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Plat Nomor <span class="text-danger">*</span></label>
                                        <input type="text" name="plat_nomor" class="form-control custom-input w-100 text-uppercase" value="{{ old('plat_nomor') }}" required placeholder="AE 1234 XX">
                                    </div>
                                </div>

                                <div class="form-section-title border-bottom pb-2">
                                    <i class="fa-solid fa-location-dot"></i> Pemetaan Titik Koordinat GPS Rumah / Pangkalan
                                </div>
                                <div class="row g-3">
                                    <div class="col-12">
                                        <button type="button" id="btnGetLocation" class="btn btn-get-location w-100 rounded-3 py-2">
                                            <i class="fa-solid fa-location-crosshairs me-2 text-danger"></i> Ambil Titik Lokasi GPS Perangkat Otomatis
                                        </button>
                                        <div id="gpsStatus" class="form-text mt-1 text-center"></div>
                                    </div>
                                    <div class="col-md-6">
                                        <input type="text" id="latitude" name="latitude" class="form-control custom-input text-center w-100" value="{{ old('latitude') }}" placeholder="Latitude">
                                    </div>
                                    <div class="col-md-6">
                                        <input type="text" id="longitude" name="longitude" class="form-control custom-input text-center w-100" value="{{ old('longitude') }}" placeholder="Longitude">
                                    </div>
                                </div>
                            </div>

                            {{-- ================= KOLOM KANAN (Upload Berkas Dokumen) ================= --}}
                            <div class="col-lg-6">
                                <div class="form-section-title border-bottom pb-2">
                                    <i class="fa-solid fa-file-arrow-up"></i> Upload Berkas Dokumen Driver (Foto Jelas)
                                </div>
                                
                                <div class="alert alert-light border rounded-3 text-muted text-center py-2 mb-3" style="font-size: 0.8rem;">
                                    <i class="fa-solid fa-circle-info me-1"></i> Format: <strong>JPG, PNG, PDF</strong> (Maksimal Ukuran: 5MB per File).
                                </div>

                                <div class="row g-3">
                                    <h6 class="fw-bold mt-2 mb-0 text-secondary" style="font-size: 0.85rem;">A. Berkas Administrasi Diri</h6>
                                    
                                    <div class="col-md-6">
                                        <label class="form-label">Foto Selfie / Wajah Pas <span class="text-danger">*</span></label>
                                        <input type="file" name="foto_wajah" class="form-control custom-file-input w-100" required accept=".jpg,.jpeg,.png">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">E-KTP Asli Terbaca <span class="text-danger">*</span></label>
                                        <input type="file" name="file_ktp" class="form-control custom-file-input w-100" required accept=".jpg,.jpeg,.png,.pdf">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">SIM (A / C) Aktif <span class="text-danger">*</span></label>
                                        <input type="file" name="file_sim" class="form-control custom-file-input w-100" required accept=".jpg,.jpeg,.png,.pdf">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">SKCK Kepolisian Aktif <span class="text-danger">*</span></label>
                                        <input type="file" name="file_skck" class="form-control custom-file-input w-100" required accept=".jpg,.jpeg,.png,.pdf">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Halaman Depan Buku Rekening Bank <span class="text-danger">*</span></label>
                                        <input type="file" name="file_buku_rekening" class="form-control custom-file-input w-100" required accept=".jpg,.jpeg,.png,.pdf">
                                        <small class="text-muted d-block mt-1" style="font-size: 0.7rem;">Nama rekening bank wajib mutlak sama dengan KTP pendaftar.</small>
                                    </div>

                                    <h6 class="fw-bold mt-4 mb-0 text-secondary" style="font-size: 0.85rem;">B. Berkas Kendaraan Operasional</h6>
                                    
                                    <div class="col-md-6">
                                        <label class="form-label">STNK Asli (Pajak Hidup) <span class="text-danger">*</span></label>
                                        <input type="file" name="file_stnk" class="form-control custom-file-input w-100" required accept=".jpg,.jpeg,.png,.pdf">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Foto Kendaraan (Tampak Samping) <span class="text-danger">*</span></label>
                                        <input type="file" name="foto_motor" class="form-control custom-file-input w-100" required accept=".jpg,.jpeg,.png">
                                    </div>

                                    <h6 class="fw-bold mt-4 mb-0 text-secondary" style="font-size: 0.85rem;">C. Berkas Pendukung (Tambahan / Opsional)</h6>
                                    
                                    <div class="col-md-4">
                                        <label class="form-label text-muted">Kartu Keluarga (KK)</label>
                                        <input type="file" name="file_kk" class="form-control custom-file-input w-100" accept=".jpg,.jpeg,.png,.pdf">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label text-muted">BPKB Lembar Utama</label>
                                        <input type="file" name="file_bpkb" class="form-control custom-file-input w-100" accept=".jpg,.jpeg,.png,.pdf">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label text-muted">Buku Nikah KUA</label>
                                        <input type="file" name="file_buku_nikah" class="form-control custom-file-input w-100" accept=".jpg,.jpeg,.png,.pdf">
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- ================= BOX FULL WIDTH DI BAWAH: PERATURAN & KETENTUAN SCROLL ================= --}}
                        <div class="row mt-5">
                            <div class="col-12">
                                <div class="form-section-title border-bottom pb-2">
                                    <i class="fa-solid fa-scale-balanced"></i> Syarat Ketentuan Kemitraan & Kebijakan Privasi
                                </div>
                                <p class="text-muted small mb-2"><i class="fa-solid fa-circle-exclamation text-danger"></i> Harap baca lembar dokumen ini dengan cara **melakukan scroll box ke bawah sampai selesai** untuk mengaktifkan persetujuan.</p>
                                
                                <div id="tosScrollBox" class="tos-scroll-box mb-3 shadow-inner">
                                    <h5 class="fw-bold text-danger border-bottom pb-1 mb-2">PERSYARATAN & PERATURAN MITRA DRIVER SANCAKA</h5>
                                    
                                    <h6 class="fw-bold mt-3 text-dark">1. Persyaratan Kualifikasi Mitra Driver (Standar Resmi)</h6>
                                    <ul class="ps-3 mb-3 text-dark">
                                        <li>Batas Usia pendaftar adalah <strong>18 tahun hingga 65 tahun</strong> pada saat pendaftaran.</li>
                                        <li>Memiliki dokumen identitas (e-KTP), SIM C (untuk Ride) atau SIM A/B (untuk Car) yang masih berlaku.</li>
                                        <li>Wajib melampirkan <strong>Surat Keterangan Catatan Kepolisian (SKCK)</strong> yang masih aktif/berlaku.</li>
                                        <li>Memiliki rekening Bank atas nama sendiri sesuai dengan KTP untuk pencairan komisi (Withdrawal).</li>
                                    </ul>

                                    <h6 class="fw-bold text-dark">2. Spesifikasi Standar Kendaraan</h6>
                                    <ul class="ps-3 mb-3 text-dark">
                                        <li>Tahun produksi/pembuatan kendaraan maksimal <strong>8 tahun terakhir</strong> (Batas minimal tahun kendaraan mengikuti aturan tahun berjalan sistem).</li>
                                        <li>Kapasitas mesin motor maksimal 250cc (mesin 4 tak). Dilarang mendaftarkan motor tipe Trail atau Sport Modifikasi ekstrim.</li>
                                        <li>Mobil (Car) diwajibkan berkapasitas mesin minimal 1.000cc.</li>
                                        <li>Kondisi fisik kendaraan harus layak jalan, bodi tidak hancur, mesin tidak brebet, ban tidak gundul, serta lampu & rem berfungsi 100%.</li>
                                    </ul>

                                    <h6 class="fw-bold text-dark">3. Kepatuhan & Kejujuran Operasional (Anti Fraud)</h6>
                                    <ul class="ps-3 mb-4 text-dark">
                                        <li>Mitra dilarang keras menggunakan aplikasi pihak ketiga untuk memanipulasi koordinat lokasi (Fake GPS / Tuyul).</li>
                                        <li>Dilarang melakukan orderan Fiktif (transaksi palsu) bersama rekan atau pelanggan komplotan untuk mengejar bonus / insentif harian.</li>
                                        <li>Akun driver dilarang dipindahtangankan, dijualbelikan, atau digantikan oleh joki. 1 Akun mutlak untuk 1 Identitas KTP & Wajah terdaftar.</li>
                                        <li>Segala bentuk pelanggaran Anti Fraud (Kecurangan) akan berakibat pada <strong>Pemblokiran Akun Permanen (Putus Mitra)</strong> dan pembekuan saldo di dalam dompet aplikasi.</li>
                                    </ul>

                                    <h5 class="fw-bold border-bottom pb-1 mb-2 mt-4">KEBIJAKAN PRIVASI</h5>
                                    <p>Privasi Anda adalah prioritas utama kami di Sancaka Express, Sancaka Store, Toko Sancaka, dan Sancaka Marketplace. Dokumen ini menjelaskan bagaimana kami mengumpulkan, menggunakan, menyimpan, dan melindungi informasi pribadi Anda. Dengan menggunakan situs atau aplikasi kami, Anda dianggap telah menyetujui seluruh isi Kebijakan Privasi ini.</p>
                                    
                                    <h6>1. Informasi yang Kami Kumpulkan</h6>
                                    <p>Kami dapat mengumpulkan data berupa: Nama lengkap, alamat email, nomor telepon, alamat pengiriman/penagihan, data lokasi live GPS, dan dokumen foto kelayakan berkas kendaraan.</p>

                                    <h6>2. Perlindungan & Pembagian Informasi</h6>
                                    <p>Kami menerapkan teknologi enkripsi dan prosedur keamanan standar industri untuk melindungi data pribadi Anda. Kami tidak menjual atau menyewakan data pribadi Anda ke pihak periklanan manapun.</p>

                                    <h6>3. Izin Akses Perangkat (Permissions)</h6>
                                    <p>Untuk memastikan aplikasi dapat berjalan optimal melacak rute orderan, kami meminta izin akses konfirmasi perangkat atas Kamera (Foto bukti drop paket), GPS Lokasi (Pelacakan maps real-time kurir), dan sinkronisasi data internet.</p>

                                    <h5 class="fw-bold border-bottom pb-1 mb-2 mt-4">SYARAT & KETENTUAN PLATFORM</h5>
                                    <p>Halaman ini berisi syarat & ketentuan resmi yang berlaku di Sancaka Express, Sancaka Store, Toko Sancaka, dan Sancaka Marketplace. Mohon dibaca dengan seksama karena semua poin ini mengikat setiap pengguna layanan kami.</p>
                                    <p>Penerimaan Ketentuan, Pendaftaran Akun valid, Kerahasiaan sandi pengguna, Penggunaan platform yang sah secara hukum Republik Indonesia, Transaksi luar platform tidak diakui, klaim paket rusak maksimal 1x24 jam wajib menyertakan bukti video unboxing, pembatalan sepihak dilarang jika pesanan kurir sudah berjalan, penyalahgunaan akun berakibat penonaktifan sepihak oleh admin, penyelesaian sengketa diselesaikan secara kekeluargaan / musyawarah mufakat.</p>
                                    
                                    <p class="fw-bold text-success text-center mt-4 bg-light p-2 border rounded">--- AKHIR DOKUMEN PERATURAN ---</p>
                                </div>

                                {{-- Centang Konfirmasi --}}
                                <div class="form-check mb-4 bg-light p-3 rounded border d-flex align-items-center gap-2" id="checkboxWrapper" style="opacity: 0.5; pointer-events: none;">
                                    <input class="form-check-input ms-0" type="checkbox" value="" id="agreeCheckbox" disabled>
                                    <label class="form-check-input-label fw-bold text-dark small" for="agreeCheckbox" style="cursor: pointer;">
                                        Saya menjamin seluruh berkas adalah asli. Saya telah membaca lengkap aturan Mitra, Anti-Fraud, & Kebijakan Privasi Sancaka Express.
                                    </label>
                                </div>

                                {{-- Tombol Kirim Form --}}
                                <button type="submit" id="submitBtn" class="btn btn-secondary btn-lg w-100 rounded-pill fw-bold shadow-sm py-3" disabled>
                                    <i class="fa-solid fa-lock me-2"></i> Silakan Scroll Peraturan Terlebih Dahulu
                                </button>
                            </div>
                        </div>

                    </form>
                </div>
            </div>
            
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // --- LOGIC DETEKSI GPS MAPS ---
        const btnGetLocation = document.getElementById('btnGetLocation');
        const latInput = document.getElementById('latitude');
        const lngInput = document.getElementById('longitude');
        const statusText = document.getElementById('gpsStatus');

        btnGetLocation.addEventListener('click', function() {
            if (navigator.geolocation) {
                btnGetLocation.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i> Sinkronisasi Koordinat...';
                btnGetLocation.disabled = true;
                statusText.innerHTML = '<span class="text-muted">Meminta izin GPS satelit...</span>';

                navigator.geolocation.getCurrentPosition(
                    function(position) {
                        latInput.value = position.coords.latitude;
                        lngInput.value = position.coords.longitude;
                        btnGetLocation.innerHTML = '<i class="fa-solid fa-check text-success me-2"></i> Lokasi Terkunci';
                        btnGetLocation.classList.replace('btn-get-location', 'btn-light');
                        btnGetLocation.disabled = false;
                        statusText.innerHTML = '<span class="text-success fw-bold"><i class="fa-solid fa-check-circle"></i> Titik koordinat berhasil dimasukkan otomatis!</span>';
                    },
                    function(error) {
                        btnGetLocation.disabled = false;
                        btnGetLocation.innerHTML = '<i class="fa-solid fa-location-crosshairs me-2 text-danger"></i> Dapatkan Lokasi GPS Otomatis';
                        statusText.innerHTML = '<span class="text-danger fw-bold"><i class="fa-solid fa-triangle-exclamation"></i> GPS Gagal dideteksi. Silakan input manual.</span>';
                    },
                    { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
                );
            }
        });

        // --- LOGIC SCROLL VALIDATION ---
        const tosBox = document.getElementById('tosScrollBox');
        const agreeCheckbox = document.getElementById('agreeCheckbox');
        const checkboxWrapper = document.getElementById('checkboxWrapper');
        const submitBtn = document.getElementById('submitBtn');

        let hasScrolledToBottom = false;

        tosBox.addEventListener('scroll', function() {
            if (!hasScrolledToBottom && (tosBox.scrollHeight - tosBox.scrollTop <= tosBox.clientHeight + 6)) {
                hasScrolledToBottom = true;
                checkboxWrapper.style.opacity = "1";
                checkboxWrapper.style.pointerEvents = "auto";
                agreeCheckbox.disabled = false;
                tosBox.style.borderColor = "#10b981"; // Ganti border box jadi hijau tanda lulus baca
            }
        });

        agreeCheckbox.addEventListener('change', function() {
            if (agreeCheckbox.checked && hasScrolledToBottom) {
                submitBtn.disabled = false;
                submitBtn.classList.replace('btn-secondary', 'btn-danger');
                submitBtn.innerHTML = '<i class="fa-solid fa-paper-plane me-2"></i> Kirim Berkas Pendaftaran Mitra';
            } else {
                submitBtn.disabled = true;
                submitBtn.classList.replace('btn-danger', 'btn-secondary');
                submitBtn.innerHTML = '<i class="fa-solid fa-lock me-2"></i> Silakan Scroll Peraturan Terlebih Dahulu';
            }
        });
    });
</script>
@endsection