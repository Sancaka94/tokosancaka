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
        height: 350px;
        overflow-y: scroll;
        background-color: #f8fafc;
        border: 2px solid #e2e8f0;
        border-radius: 0.5rem;
        padding: 1.5rem;
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
                            <i class="fa-solid fa-shield-virus me-2 fs-5"></i> {{ session('error') }}
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

                    <form action="{{ route('driver.register.store') }}" method="POST" enctype="multipart/form-data" id="formPendaftaran">
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
                                    {{-- DOKUMEN PRIBADI --}}
                                    <h6 class="fw-bold mt-2 mb-0 text-secondary" style="font-size: 0.85rem;">A. Berkas Administrasi Diri</h6>
                                    
                                    <div class="col-md-6">
                                        <label class="form-label">Foto 4x6 Warna Biru <span class="text-danger">*</span></label>
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

                                    {{-- DOKUMEN KENDARAAN --}}
                                    <h6 class="fw-bold mt-4 mb-0 text-secondary" style="font-size: 0.85rem;">B. Berkas Kendaraan Operasional</h6>
                                    
                                    <div class="col-md-6">
                                        <label class="form-label">STNK Asli (Pajak Hidup) <span class="text-danger">*</span></label>
                                        <input type="file" name="file_stnk" class="form-control custom-file-input w-100" required accept=".jpg,.jpeg,.png,.pdf">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Foto Kendaraan (Tampak Samping) <span class="text-danger">*</span></label>
                                        <input type="file" name="foto_motor" class="form-control custom-file-input w-100" required accept=".jpg,.jpeg,.png">
                                    </div>

                                    {{-- DOKUMEN PENDUKUNG --}}
                                    <h6 class="fw-bold mt-4 mb-0 text-secondary" style="font-size: 0.85rem;">C. Berkas Pendukung (Opsional)</h6>
                                    
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
                                    <i class="fa-solid fa-scale-balanced"></i> Peraturan, Syarat Ketentuan & Kebijakan Privasi Sancaka
                                </div>
                                <p class="text-muted small mb-3"><i class="fa-solid fa-circle-exclamation text-danger"></i> Harap baca lembar dokumen ini dengan cara <strong>melakukan scroll box ke bawah sampai selesai</strong> untuk mengaktifkan tombol pendaftaran.</p>
                                
                                <div id="tosScrollBox" class="tos-scroll-box mb-3 shadow-inner">
                                    
                                  {{-- 1. PERATURAN MITRA DRIVER --}}
                                    <h5 class="fw-bold text-danger border-bottom pb-1 mb-3">PERSYARATAN & PERATURAN MITRA DRIVER SANCAKA</h5>
                                    
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
                                    <ul class="ps-3 mb-5 text-dark">
                                        <li>Mitra dilarang keras menggunakan aplikasi pihak ketiga untuk memanipulasi koordinat lokasi (Fake GPS / Tuyul).</li>
                                        <li>Dilarang melakukan orderan Fiktif (transaksi palsu) bersama rekan atau pelanggan komplotan untuk mengejar bonus / insentif harian.</li>
                                        <li>Akun driver dilarang dipindahtangankan, dijualbelikan, atau digantikan oleh joki. 1 Akun mutlak untuk 1 Identitas KTP & Wajah terdaftar.</li>
                                        <li>Segala bentuk pelanggaran Anti Fraud (Kecurangan) akan berakibat pada <strong>Pemblokiran Akun Permanen (Putus Mitra)</strong> dan pembekuan saldo di dalam dompet aplikasi.</li>
                                    </ul>

                                    {{-- 2. KEBIJAKAN PRIVASI --}}
                                    <h5 class="fw-bold text-primary border-bottom pb-1 mb-3">KEBIJAKAN PRIVASI</h5>
                                    <p class="text-dark">Privasi Anda adalah prioritas utama kami di Sancaka Express, Sancaka Store, Toko Sancaka, dan Sancaka Marketplace. Dokumen ini menjelaskan bagaimana kami mengumpulkan, menggunakan, menyimpan, dan melindungi informasi pribadi Anda saat menggunakan layanan kami. Dengan menggunakan situs atau aplikasi kami, Anda dianggap telah menyetujui seluruh isi Kebijakan Privasi ini.</p>
                                    
                                    <h6 class="fw-bold mt-3 text-dark">1. Informasi yang Kami Kumpulkan</h6>
                                    <p class="mb-2 text-dark">Kami dapat mengumpulkan data berikut:</p>
                                    <ul class="ps-3 mb-3 text-dark">
                                        <li>Nama lengkap, alamat email, dan nomor telepon</li>
                                        <li>Alamat pengiriman dan penagihan</li>
                                        <li>Data transaksi & riwayat belanja</li>
                                        <li>Informasi pembayaran (hanya melalui saluran resmi, tidak kami simpan detail kartu)</li>
                                        <li>Data lokasi (jika Anda mengaktifkan layanan berbasis lokasi)</li>
                                    </ul>

                                    <h6 class="fw-bold mt-3 text-dark">2. Cara Pengumpulan Data</h6>
                                    <p class="mb-2 text-dark">Data diperoleh melalui:</p>
                                    <ul class="ps-3 mb-3 text-dark">
                                        <li>Formulir pendaftaran akun</li>
                                        <li>Transaksi pembelian & pemesanan layanan</li>
                                        <li>Penggunaan cookie di situs web</li>
                                        <li>Komunikasi melalui email, WhatsApp, atau live chat</li>
                                    </ul>

                                    <h6 class="fw-bold mt-3 text-dark">3. Tujuan Penggunaan Informasi</h6>
                                    <p class="mb-2 text-dark">Data pribadi digunakan untuk:</p>
                                    <ul class="ps-3 mb-3 text-dark">
                                        <li>Memproses pesanan dan mengirimkan produk/jasa</li>
                                        <li>Menyediakan dukungan pelanggan</li>
                                        <li>Meningkatkan kualitas layanan dan pengalaman pengguna</li>
                                        <li>Mengirimkan notifikasi, promo, dan informasi penting</li>
                                        <li>Mencegah penipuan dan menjaga keamanan transaksi</li>
                                    </ul>

                                    <h6 class="fw-bold mt-3 text-dark">4. Penggunaan Cookie</h6>
                                    <p class="text-dark mb-3">Kami menggunakan cookie untuk menyimpan preferensi pengguna, melacak aktivitas, dan meningkatkan pengalaman saat menggunakan layanan. Anda dapat menonaktifkan cookie melalui pengaturan browser, namun beberapa fitur mungkin tidak berfungsi optimal.</p>

                                    <h6 class="fw-bold mt-3 text-dark">5. Perlindungan Data</h6>
                                    <p class="text-dark mb-3">Kami menerapkan teknologi enkripsi dan prosedur keamanan standar industri untuk melindungi data pribadi Anda. Meskipun demikian, transmisi data melalui internet tidak sepenuhnya aman, dan kami tidak dapat menjamin 100% keamanan informasi.</p>

                                    <h6 class="fw-bold mt-3 text-dark">6. Pembagian Informasi</h6>
                                    <p class="mb-2 text-dark">Kami tidak menjual atau menyewakan data pribadi Anda. Informasi dapat dibagikan hanya kepada:</p>
                                    <ul class="ps-3 mb-3 text-dark">
                                        <li>Partner logistik & ekspedisi untuk pengiriman pesanan</li>
                                        <li>Penyedia pembayaran untuk memproses transaksi</li>
                                        <li>Pihak berwenang jika diwajibkan oleh hukum</li>
                                    </ul>

                                    <h6 class="fw-bold mt-3 text-dark">7. Hak Pengguna</h6>
                                    <p class="mb-2 text-dark">Anda memiliki hak untuk:</p>
                                    <ul class="ps-3 mb-3 text-dark">
                                        <li>Meminta salinan data pribadi Anda</li>
                                        <li>Memperbaiki data yang salah atau tidak akurat</li>
                                        <li>Meminta penghapusan data sesuai ketentuan hukum</li>
                                        <li>Menolak penggunaan data untuk tujuan pemasaran</li>
                                    </ul>

                                    <h6 class="fw-bold mt-3 text-dark">8. Penyimpanan Data</h6>
                                    <p class="text-dark mb-3">Data pribadi Anda akan disimpan selama akun aktif atau selama diperlukan untuk memenuhi tujuan yang disebutkan dalam kebijakan ini. Setelah itu, data akan dihapus atau dianonimkan.</p>

                                    <h6 class="fw-bold mt-3 text-dark">9. Keamanan Transaksi</h6>
                                    <p class="text-dark mb-3">Semua transaksi hanya dapat dilakukan melalui metode pembayaran resmi yang tersedia di Sancaka Express, Sancaka Store, Toko Sancaka, dan Sancaka Marketplace. Kami tidak bertanggung jawab atas transaksi di luar platform resmi kami.</p>

                                    <h6 class="fw-bold mt-3 text-dark">10. Layanan Pihak Ketiga</h6>
                                    <p class="text-dark mb-3">Situs atau aplikasi kami dapat memuat tautan ke layanan pihak ketiga (misalnya ekspedisi, metode pembayaran). Kami tidak bertanggung jawab atas kebijakan privasi pihak ketiga tersebut. Disarankan untuk membaca syarat dan ketentuan mereka secara terpisah.</p>

                                    <h6 class="fw-bold mt-3 text-dark">11. Kebijakan Anak-anak</h6>
                                    <p class="text-dark mb-3">Layanan kami tidak ditujukan untuk anak-anak di bawah usia 13 tahun. Kami tidak sengaja mengumpulkan informasi pribadi dari anak-anak. Jika Anda percaya bahwa anak Anda telah memberikan data kepada kami, segera hubungi layanan pelanggan.</p>

                                    <h6 class="fw-bold mt-3 text-dark">12. Proses Delivery</h6>
                                    <p class="text-dark mb-3">Estimasi pengiriman ditentukan oleh pihak ekspedisi. Data alamat yang Anda berikan akan digunakan untuk memastikan pesanan sampai dengan benar. Kami tidak bertanggung jawab atas kesalahan pengiriman akibat informasi alamat yang tidak lengkap atau salah.</p>

                                    <h6 class="fw-bold mt-3 text-dark">13. Pembatalan Pesanan (Cancel)</h6>
                                    <p class="text-dark mb-3">Pesanan dapat dibatalkan sebelum status berubah menjadi "Diproses". Setelah pesanan diproses atau dikirim, pembatalan tidak dapat dilakukan. Untuk bantuan pembatalan, hubungi layanan pelanggan segera setelah pemesanan.</p>

                                    <h6 class="fw-bold mt-3 text-dark">14. Kebijakan Pengembalian & Refund</h6>
                                    <p class="mb-2 text-dark">Anda berhak mengajukan refund atau pengembalian barang apabila:</p>
                                    <ul class="ps-3 mb-2 text-dark">
                                        <li>Produk yang diterima rusak atau cacat produksi</li>
                                        <li>Produk tidak sesuai dengan deskripsi atau pesanan</li>
                                        <li>Produk hilang dalam proses pengiriman (setelah konfirmasi resmi dari ekspedisi)</li>
                                    </ul>
                                    <p class="text-dark mb-3">Proses refund akan dilakukan ke metode pembayaran yang sama dengan yang digunakan saat transaksi. Waktu pencairan refund mengikuti kebijakan bank atau penyedia pembayaran masing-masing.</p>

                                    <h6 class="fw-bold mt-3 text-dark">15. Perubahan Kebijakan</h6>
                                    <p class="text-dark mb-3">Kami dapat memperbarui Kebijakan Privasi ini dari waktu ke waktu. Versi terbaru akan ditampilkan di situs web, dan perubahan berlaku sejak tanggal dipublikasikan.</p>

                                    <h6 class="fw-bold mt-3 text-dark">16. Persetujuan</h6>
                                    <p class="text-dark mb-3">Dengan menggunakan layanan kami, Anda menyatakan telah membaca, memahami, dan menyetujui seluruh isi Kebijakan Privasi ini.</p>

                                    <h6 class="fw-bold mt-3 text-dark">17. Izin Akses Perangkat (Permissions)</h6>
                                    <p class="mb-2 text-dark">Untuk memastikan aplikasi dan layanan kami berfungsi dengan optimal, kami mungkin meminta izin akses ke beberapa fitur pada perangkat Anda, yaitu:</p>
                                    <ul class="ps-3 mb-2 text-dark">
                                        <li><strong>Kamera:</strong> Untuk mengambil foto profil, memindai QR code/barcode, atau melampirkan foto bukti transaksi dan pengembalian barang.</li>
                                        <li><strong>GPS (Lokasi):</strong> Untuk mendeteksi lokasi Anda demi akurasi alamat pengiriman, pencarian toko terdekat, dan pelacakan kurir.</li>
                                        <li><strong>Browser & Akses Internet:</strong> Untuk menghubungkan aplikasi dengan server kami, memproses transaksi secara real-time, dan membuka tautan layanan.</li>
                                        <li><strong>Pengiriman Data Pribadi:</strong> Untuk mengirimkan dan menyinkronkan data pribadi Anda secara aman ke server kami guna keperluan verifikasi akun dan kelancaran layanan.</li>
                                    </ul>
                                    <p class="text-dark mb-3">Anda dapat mengaktifkan atau menonaktifkan izin ini kapan saja melalui pengaturan (settings) perangkat Anda. Namun, perlu diketahui bahwa menonaktifkan izin tertentu dapat membuat beberapa fitur aplikasi tidak berfungsi dengan baik.</p>

                                    <h6 class="fw-bold mt-3 text-dark">18. Kontak Kami</h6>
                                    <p class="text-dark mb-5">Jika Anda memiliki pertanyaan, keluhan, atau permintaan terkait data pribadi, pengiriman, pembatalan, atau refund, silakan hubungi kami melalui halaman kontak resmi di Sancaka Express, Sancaka Store, Toko Sancaka, dan Sancaka Marketplace. Terima kasih telah mempercayakan belanja dan layanan Anda kepada kami.</p>

                                    {{-- 3. SYARAT & KETENTUAN PLATFORM --}}
                                    <h5 class="fw-bold text-success border-bottom pb-1 mb-3">SYARAT & KETENTUAN PLATFORM (T.O.S)</h5>
                                    <p class="text-dark mb-3">Halaman ini berisi syarat & ketentuan resmi yang berlaku di Sancaka Express, Sancaka Store, Toko Sancaka, dan Sancaka Marketplace. Mohon dibaca dengan seksama karena semua poin ini mengikat setiap pengguna layanan kami.</p>
                                    
                                    <ul class="ps-3 mb-4 text-dark">
                                        <li><strong>Penerimaan Ketentuan:</strong> Dengan menggunakan layanan kami, Anda setuju terikat oleh ketentuan ini.</li>
                                        <li><strong>Pendaftaran Akun:</strong> Semua informasi akun harus akurat, lengkap, dan terkini.</li>
                                        <li><strong>Kerahasiaan Akun:</strong> Anda bertanggung jawab menjaga keamanan username & password Anda.</li>
                                        <li><strong>Penggunaan yang Sah:</strong> Layanan hanya boleh digunakan untuk aktivitas legal.</li>
                                        <li><strong>Produk & Deskripsi:</strong> Kami berusaha akurat, tapi kesalahan deskripsi bisa terjadi.</li>
                                        <li><strong>Harga:</strong> Harga dapat berubah sewaktu-waktu tanpa pemberitahuan.</li>
                                        <li><strong>Pembayaran:</strong> Hanya melalui metode resmi yang kami sediakan.</li>
                                        <li><strong>Transaksi di Luar Platform:</strong> Tidak diakui & bukan tanggung jawab kami.</li>
                                        <li><strong>Konfirmasi Pesanan:</strong> Pesanan diproses setelah pembayaran terverifikasi.</li>
                                        <li><strong>Estimasi Pengiriman:</strong> Berdasarkan ekspedisi, bisa berubah karena faktor eksternal.</li>
                                        <li><strong>Pemeriksaan Barang:</strong> Pembeli wajib memeriksa kondisi produk saat diterima.</li>
                                        <li><strong>Kerusakan Pengiriman:</strong> Harus diklaim dalam 1x24 jam dengan bukti video/foto.</li>
                                        <li><strong>Asuransi:</strong> Tersedia sesuai pilihan ekspedisi & tanggungan pembeli.</li>
                                        <li><strong>Pembatalan Pesanan:</strong> Hanya berlaku sebelum pesanan diproses/ dikirim.</li>
                                        <li><strong>Kebijakan Refund:</strong> Berlaku untuk barang rusak, tidak sesuai, atau hilang.</li>
                                        <li><strong>Waktu Refund:</strong> Mengikuti kebijakan penyedia pembayaran (estimasi 3–14 hari kerja).</li>
                                        <li><strong>Produk Non-Refund:</strong> Produk digital, makanan segar, atau custom tidak bisa dikembalikan.</li>
                                        <li><strong>Tanggung Jawab Penjual:</strong> Kualitas & keaslian produk adalah tanggung jawab penjual.</li>
                                        <li><strong>Tanggung Jawab Pembeli:</strong> Memberikan alamat & data penerima yang benar.</li>
                                        <li><strong>Klaim Salah Kirim:</strong> Wajib diajukan maksimal 2x24 jam setelah barang diterima.</li>
                                        <li><strong>Perubahan Pesanan:</strong> Tidak dapat dilakukan setelah pesanan masuk tahap pengiriman.</li>
                                        <li><strong>Privasi:</strong> Data pribadi dilindungi sesuai kebijakan privasi kami.</li>
                                        <li><strong>Keamanan Data:</strong> Kami gunakan teknologi terbaik untuk melindungi data Anda.</li>
                                        <li><strong>Penyalahgunaan Akun:</strong> Kami berhak menonaktifkan akun yang melanggar aturan.</li>
                                        <li><strong>Keterlambatan:</strong> Tidak ada kompensasi jika karena force majeure (cuaca, bencana, dsb).</li>
                                        <li><strong>Pajak & Biaya:</strong> Pembeli bertanggung jawab atas pajak atau biaya tambahan yang berlaku.</li>
                                        <li><strong>Promosi & Diskon:</strong> Berlaku sesuai syarat yang ditentukan, tidak dapat digabungkan.</li>
                                        <li><strong>Layanan Pelanggan:</strong> Tersedia untuk membantu melalui kontak resmi kami.</li>
                                        <li><strong>Hak Kekayaan Intelektual:</strong> Semua konten platform dilindungi hak cipta & merek dagang.</li>
                                        <li><strong>Penyalahgunaan Platform:</strong> Tindakan ilegal/penipuan akan diproses hukum.</li>
                                        <li><strong>Batas Tanggung Jawab:</strong> Kami tidak bertanggung jawab atas kerugian tidak langsung.</li>
                                        <li><strong>Ketersediaan Layanan:</strong> Bisa dihentikan sementara untuk pemeliharaan sistem.</li>
                                        <li><strong>Perubahan Layanan:</strong> Kami berhak mengubah fitur kapan saja.</li>
                                        <li><strong>Force Majeure:</strong> Kami dibebaskan dari tanggung jawab bila ada keadaan di luar kendali.</li>
                                        <li><strong>Pihak Ketiga:</strong> Kami tidak menjamin layanan pihak ketiga yang terintegrasi.</li>
                                        <li><strong>Konten Pengguna:</strong> Semua konten yang diunggah pengguna menjadi tanggung jawab pribadi.</li>
                                        <li><strong>Penghapusan Konten:</strong> Kami berhak menghapus konten yang melanggar aturan.</li>
                                        <li><strong>Notifikasi:</strong> Semua pemberitahuan resmi dilakukan melalui email/akun resmi.</li>
                                        <li><strong>Korespondensi:</strong> Percakapan dengan CS bisa disimpan untuk bukti layanan.</li>
                                        <li><strong>Penyelesaian Sengketa:</strong> Diselesaikan secara musyawarah, bila gagal melalui jalur hukum.</li>
                                        <li><strong>Wilayah Hukum:</strong> Berlaku hukum Republik Indonesia.</li>
                                        <li><strong>Bahasa:</strong> Dokumen ini dibuat dalam bahasa Indonesia.</li>
                                        <li><strong>Kebijakan Anak:</strong> Layanan tidak ditujukan untuk anak di bawah 13 tahun tanpa izin orang tua.</li>
                                        <li><strong>Update Ketentuan:</strong> Kami bisa memperbarui syarat & ketentuan kapan saja.</li>
                                        <li><strong>Tanggal Berlaku:</strong> Ketentuan ini berlaku sejak tanggal dipublikasikan.</li>
                                        <li><strong>Keterikatan Hukum:</strong> Semua pengguna terikat otomatis oleh syarat & ketentuan ini.</li>
                                        <li><strong>Kompensasi:</strong> Tidak ada kompensasi selain refund sesuai aturan.</li>
                                        <li><strong>Pelanggaran:</strong> Pelanggaran bisa mengakibatkan penutupan akun permanen.</li>
                                        <li><strong>Kontak Resmi:</strong> Pertanyaan & keluhan hanya diterima lewat kanal resmi kami.</li>
                                        <li><strong>Penutup:</strong> Dengan menggunakan layanan kami, Anda menyatakan setuju tanpa paksaan.</li>
                                    </ul>

                                    <p class="text-dark mb-5">Terima kasih telah menggunakan Sancaka Express, Sancaka Store, Toko Sancaka, dan Sancaka Marketplace.</p>
                                    
                                    <p class="fw-bold text-success text-center mt-5 bg-light p-3 border rounded shadow-sm">--- AKHIR DOKUMEN PERATURAN & PERSETUJUAN ---</p>
                                </div>

                                {{-- Centang Konfirmasi --}}
                                <div class="form-check mb-4 bg-light p-3 rounded border d-flex align-items-center gap-2" id="checkboxWrapper" style="opacity: 0.5; pointer-events: none;">
                                    <input class="form-check-input ms-0" type="checkbox" value="" id="agreeCheckbox" disabled>
                                    <label class="form-check-input-label fw-bold text-dark small" for="agreeCheckbox" style="cursor: pointer;">
                                        Saya menjamin seluruh berkas adalah asli. Saya telah membaca lengkap aturan Kualifikasi Mitra, Anti-Fraud, Kebijakan Privasi, dan T.O.S Sancaka Express.
                                    </label>
                                </div>

                                {{-- INFO SISTEM ANTI-VIRUS --}}
                                <div class="text-center mb-3">
                                    <p class="text-sm text-gray-500 mb-0" style="font-size: 0.85rem; color: #64748b;">
                                        <i class="fa-solid fa-shield-halved text-success me-1"></i> Sistem Keamanan Aktif: 
                                        Semua dokumen yang Anda unggah akan dipindai oleh sistem Anti-Virus. 
                                        Proses pengiriman mungkin memakan waktu hingga 30 detik.
                                    </p>
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
        // --- LOGIC FORM SUBMIT (LOADING STATE) ---
        const form = document.getElementById('formPendaftaran');
        const submitBtn = document.getElementById('submitBtn');

        form.addEventListener('submit', function() {
            // Ubah tampilan tombol saat form di-submit
            submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i> Mengunggah & Memindai Berkas... Mohon tunggu!';
            submitBtn.classList.replace('btn-danger', 'btn-secondary');
            submitBtn.style.opacity = '0.8';
            submitBtn.style.cursor = 'not-allowed';
            submitBtn.disabled = true; // Mencegah multiple submit
        });

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
                        statusText.innerHTML = '<span class="text-success fw-bold"><i class="fa-solid fa-check-circle"></i> Titik koordinat berhasil dimasukkan otomatis! Lebih akurat, Gunakan GPS HP Anda</span>';
                    },
                    function(error) {
                        btnGetLocation.disabled = false;
                        btnGetLocation.innerHTML = '<i class="fa-solid fa-location-crosshairs me-2 text-danger"></i> Dapatkan Lokasi GPS Otomatis';
                        statusText.innerHTML = '<span class="text-danger fw-bold"><i class="fa-solid fa-triangle-exclamation"></i> GPS Gagal dideteksi. Silakan input manual. Cek Koordinat Anda Di Google Maps</span>';
                    },
                    { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
                );
            }
        });

        // --- LOGIC SCROLL VALIDATION ---
        const tosBox = document.getElementById('tosScrollBox');
        const agreeCheckbox = document.getElementById('agreeCheckbox');
        const checkboxWrapper = document.getElementById('checkboxWrapper');

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