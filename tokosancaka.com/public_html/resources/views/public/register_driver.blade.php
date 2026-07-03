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
        color: #dc2626; /* Sancaka Red */
    }
    .form-label {
        font-weight: 600;
        color: #475569;
        font-size: 0.9rem;
    }
    .custom-input {
        background-color: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 0.5rem;
        padding: 0.75rem 1rem;
        transition: all 0.2s ease-in-out;
    }
    .custom-input:focus {
        background-color: #ffffff;
        border-color: #dc2626;
        box-shadow: 0 0 0 0.25rem rgba(220, 38, 38, 0.15);
    }
    .custom-file-input {
        background-color: #f8fafc;
        border: 1px dashed #cbd5e1;
        border-radius: 0.5rem;
        padding: 0.5rem;
        font-size: 0.85rem;
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
        transition: all 0.2s;
    }
    .btn-get-location:hover {
        background-color: #e2e8f0;
        color: #0f172a;
    }
    /* Style Khusus untuk Box Term & Condition Scroll */
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
    .tos-scroll-box::-webkit-scrollbar {
        width: 8px;
    }
    .tos-scroll-box::-webkit-scrollbar-track {
        background: #f1f5f9;
    }
    .tos-scroll-box::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 4px;
    }
    .tos-scroll-box::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }
    .rules-badge {
        font-size: 0.75rem;
        background-color: #fee2e2;
        color: #991b1b;
        padding: 0.25rem 0.5rem;
        border-radius: 0.25rem;
        font-weight: bold;
    }
</style>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-xl-11 col-lg-12">
            
            <div class="card register-card">
                <div class="register-header">
                    <h2 class="fw-bold mb-2">Gabung Menjadi Mitra Driver Sancaka</h2>
                    <p class="mb-0 opacity-75">Pendaftaran Ojek Online & Kurir Ekspres Resmi Mandiri</p>
                </div>

                <div class="card-body p-4 p-md-5">
                    
                    {{-- Alert Messages --}}
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
                            <div class="fw-bold mb-2"><i class="fa-solid fa-circle-exclamation me-2"></i> Terdapat kesalahan input:</div>
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('driver.register.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        
                        <div class="row g-5">
                            {{-- ================= KOLOM KIRI ================= --}}
                            <div class="col-lg-6">
                                <div class="form-section-title border-bottom pb-2">
                                    <i class="fa-solid fa-id-card"></i> Informasi Pribadi
                                </div>
                                
                                <div class="row g-3 mb-4">
                                    <div class="col-12">
                                        <label class="form-label">Nama Lengkap Sesuai KTP <span class="text-danger">*</span></label>
                                        <input type="text" name="nama_lengkap" class="form-control custom-input @error('nama_lengkap') is-invalid @enderror" value="{{ old('nama_lengkap') }}" required placeholder="Contoh: Amal Abu Kholid">
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Nomor NIK <span class="text-danger">*</span></label>
                                        <input type="number" name="nomor_nik" class="form-control custom-input @error('nomor_nik') is-invalid @enderror" value="{{ old('nomor_nik') }}" required placeholder="16 Digit NIK">
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Nomor Kartu Keluarga <span class="text-danger">*</span></label>
                                        <input type="number" name="nomor_kk" class="form-control custom-input @error('nomor_kk') is-invalid @enderror" value="{{ old('nomor_kk') }}" required placeholder="16 Digit Nomor KK">
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label">Nomor WhatsApp <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-end-0"><i class="fa-brands fa-whatsapp text-success"></i></span>
                                            <input type="text" name="nomor_wa" class="form-control custom-input border-start-0 @error('nomor_wa') is-invalid @enderror" value="{{ old('nomor_wa') }}" required placeholder="Contoh: 085745808809">
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label">Alamat Domisili <span class="text-danger">*</span></label>
                                        <textarea name="alamat_lengkap" class="form-control custom-input @error('alamat_lengkap') is-invalid @enderror" rows="3" required placeholder="Tuliskan alamat lengkap beserta RT/RW, Kelurahan, Kecamatan, dan Kabupaten">{{ old('alamat_lengkap') }}</textarea>
                                    </div>
                                </div>

                                <div class="form-section-title border-bottom pb-2 mt-2">
                                    <i class="fa-solid fa-location-dot"></i> Titik Lokasi GPS Perangkat
                                </div>
                                <div class="row g-3">
                                    <div class="col-12">
                                        <button type="button" id="btnGetLocation" class="btn btn-get-location w-100 rounded-3 py-2">
                                            <i class="fa-solid fa-location-crosshairs me-2 text-danger"></i> Dapatkan Lokasi Otomatis via Perangkat
                                        </button>
                                        <div id="gpsStatus" class="form-text mt-1 text-center"></div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Latitude</label>
                                        <input type="text" id="latitude" name="latitude" class="form-control custom-input" value="{{ old('latitude') }}" placeholder="-7.39338940">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Longitude</label>
                                        <input type="text" id="longitude" name="longitude" class="form-control custom-input" value="{{ old('longitude') }}" placeholder="111.44485420">
                                    </div>
                                </div>
                            </div>

                            {{-- ================= KOLOM KANAN ================= --}}
                            <div class="col-lg-6">
                                <div class="form-section-title border-bottom pb-2">
                                    <i class="fa-solid fa-file-arrow-up"></i> Upload Dokumen Utama
                                </div>
                                
                                <div class="alert alert-light border rounded-3 text-muted text-center py-2 mb-3" style="font-size: 0.85rem;">
                                    <i class="fa-solid fa-circle-info me-1"></i> File Ekstensi: <strong>JPG, PNG, PDF</strong> (Maks: 5MB)
                                </div>

                                <div class="row g-3 mb-4">
                                    <div class="col-md-6">
                                        <label class="form-label">KTP (Wajib)</label>
                                        <input type="file" name="file_ktp" class="form-control custom-file-input" required accept=".jpg,.jpeg,.png,.pdf">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Kartu Keluarga (Wajib)</label>
                                        <input type="file" name="file_kk" class="form-control custom-file-input" required accept=".jpg,.jpeg,.png,.pdf">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">STNK Kendaraan (Wajib)</label>
                                        <input type="file" name="file_stnk" class="form-control custom-file-input" required accept=".jpg,.jpeg,.png,.pdf">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">BPKB Kendaraan (Wajib)</label>
                                        <input type="file" name="file_bpkb" class="form-control custom-file-input" required accept=".jpg,.jpeg,.png,.pdf">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Foto Motor Tampak Samping (Wajib)</label>
                                        <input type="file" name="foto_motor" class="form-control custom-file-input" required accept=".jpg,.jpeg,.png,.pdf">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Foto 4x6 Warna Biru (Wajib)</label>
                                        <input type="file" name="foto_wajah" class="form-control custom-file-input" required accept=".jpg,.jpeg,.png,.pdf">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label text-muted">Buku Nikah (Opsional)</label>
                                        <input type="file" name="file_buku_nikah" class="form-control custom-file-input" accept=".jpg,.jpeg,.png,.pdf">
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- ================= BARIS FULL WIDTH DI BAWAH: PERATURAN & KETENTUAN ================= --}}
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="form-section-title border-bottom pb-2">
                                    <i class="fa-solid fa-scale-balanced"></i> Ketentuan Pendaftaran Mitra & Kebijakan Privasi
                                </div>
                                <p class="text-muted small mb-2"><i class="fa-solid fa-circle-exclamation text-danger"></i> Anda wajib membaca dan melakukan <strong>scroll ke bawah sampai selesai</strong> pada teks Box Peraturan berikut untuk mengaktifkan tombol daftar.</p>
                                
                                {{-- Box Peraturan Gabungan --}}
                                <div id="tosScrollBox" class="tos-scroll-box mb-3 shadow-inner">
                                    <h5 class="fw-bold text-danger border-bottom pb-1 mb-2">PERATURAN UTAMA MITRA DRIVER & KURIR SANCAKA</h5>
                                    <ol class="ps-3 mb-4 font-bold text-dark">
                                        <li><strong>Kepatuhan Operasional:</strong> Mitra bersedia mematuhi jam operasional, standar pelayanan pengantaran penumpang (Ojek), serta penanganan paket express/marketplace sesuai SOP Sancaka Express.</li>
                                        <li><strong>Kelayakan Perangkat & Kendaraan:</strong> Kendaraan yang didaftarkan wajib memiliki STNK aktif, pajak hidup, serta kondisi mekanis yang aman. Handphone wajib mendukung sinyal internet stabil untuk akurasi peta.</li>
                                        <li><strong>Kejujuran Transaksi:</strong> Dilarang keras melakukan transaksi palsu (Fiktif), kecurangan manipulasi GPS (Fake GPS), atau penyalahgunaan akun. Pelanggaran berakibat pada pemblokiran permanen (Putus Mitra) tanpa pencairan sisa saldo.</li>
                                        <li><strong>Tarif Resmi Platform:</strong> Seluruh sistem penarifan, bagi hasil, dan ongkos kirim wajib mengikuti ketentuan mutlak aplikasi Sancaka Marketplace / Toko Sancaka. Dilarang memungut biaya tambahan di luar platform kepada customer secara sepihak.</li>
                                        <li><strong>Perlindungan Barang & Penumpang:</strong> Driver bertanggung jawab penuh atas keselamatan penumpang dan keutuhan paket yang dibawa sejak serah terima hingga tujuan. Segala kehilangan akibat kelalaian driver menjadi tanggung jawab pribadi.</li>
                                    </ol>

                                    <h5 class="fw-bold border-bottom pb-1 mb-2">KEBIJAKAN PRIVASI</h5>
                                    <p>Privasi Anda adalah prioritas utama kami di Sancaka Express, Sancaka Store, Toko Sancaka, dan Sancaka Marketplace.<br>
                                    Dokumen ini menjelaskan bagaimana kami mengumpulkan, menggunakan, menyimpan, dan melindungi informasi pribadi Anda saat menggunakan layanan kami. Dengan menggunakan situs atau aplikasi kami, Anda dianggap telah menyetujui seluruh isi Kebijakan Privasi ini.</p>
                                    
                                    <h6>1. Informasi yang Kami Kumpulkan</h6>
                                    <p>Kami dapat mengumpulkan data berikut:<br>
                                    Nama lengkap, alamat email, dan nomor telepon<br>
                                    Alamat pengiriman dan penagihan<br>
                                    Data transaksi & riwayat belanja<br>
                                    Informasi pembayaran (hanya melalui saluran resmi, tidak kami simpan detail kartu)<br>
                                    Data lokasi (jika Anda mengaktifkan layanan berbasis lokasi)</p>

                                    <h6>2. Cara Pengumpulan Data</h6>
                                    <p>Data diperoleh melalui formulir pendaftaran akun, transaksi pembelian & pemesanan layanan, penggunaan cookie di situs web, serta komunikasi melalui email, WhatsApp, atau live chat.</p>

                                    <h6>3. Tujuan Penggunaan Informasi</h6>
                                    <p>Data pribadi digunakan untuk memproses pesanan dan mengirimkan produk/jasa, menyediakan dukungan pelanggan, meningkatkan kualitas layanan, mengirimkan notifikasi/promo, serta mencegah penipuan.</p>

                                    <h6>4. Penggunaan Cookie</h6>
                                    <p>Kami menggunakan cookie untuk menyimpan preferensi pengguna, melacak aktivitas, dan meningkatkan pengalaman saat menggunakan layanan.</p>

                                    <h6>5. Perlindungan Data</h6>
                                    <p>Kami menerapkan teknologi enkripsi dan prosedur keamanan standar industri untuk melindungi data pribadi Anda. Meskipun demikian, kami tidak dapat menjamin 100% keamanan informasi dari transmisi data internet.</p>

                                    <h6>6. Pembagian Informasi</h6>
                                    <p>Kami tidak menjual atau menyewakan data pribadi Anda. Informasi hanya dibagikan kepada partner logistik/ekspedisi, penyedia pembayaran resmi, atau pihak berwenang jika diwajibkan oleh hukum.</p>

                                    <h6>7. Hak Pengguna</h6>
                                    <p>Anda memiliki hak untuk meminta salinan data pribadi Anda, memperbaiki data yang salah atau tidak akurat, meminta penghapusan data, atau menolak penggunaan data pemasaran.</p>

                                    <h6>8. Penyimpanan Data</h6>
                                    <p>Data pribadi Anda akan disimpan selama akun aktif atau selama diperlukan untuk memenuhi tujuan yang disebutkan dalam kebijakan ini.</p>

                                    <h6>9. Keamanan Transaksi</h6>
                                    <p>Semua transaksi hanya dapat dilakukan melalui metode pembayaran resmi yang tersedia di Sancaka Express, Sancaka Store, Toko Sancaka, dan Sancaka Marketplace.</p>

                                    <h6>10. Layanan Pihak Ketiga</h6>
                                    <p>Situs atau aplikasi kami dapat memuat tautan ke layanan pihak ketiga. Kami tidak bertanggung jawab atas kebijakan privasi pihak ketiga tersebut.</p>

                                    <h6>11. Kebijakan Anak-anak</h6>
                                    <p>Layanan kami tidak ditujukan untuk anak-anak di bawah usia 13 tahun. Kami tidak sengaja mengumpulkan informasi pribadi dari anak-anak.</p>

                                    <h6>12. Proses Delivery</h6>
                                    <p>Estimasi pengiriman ditentukan oleh pihak ekspedisi. Kami tidak bertanggung jawab atas kesalahan pengiriman akibat informasi alamat yang tidak lengkap atau salah.</p>

                                    <h6>13. Pembatalan Pesanan (Cancel)</h6>
                                    <p>Pesanan dapat dibatalkan sebelum status berubah menjadi "Diproses". Setelah pesanan diproses atau dikirim, pembatalan tidak dapat dilakukan.</p>

                                    <h6>14. Kebijakan Pengembalian & Refund</h6>
                                    <p>Anda berhak mengajukan refund jika produk yang diterima rusak/cacat produksi, tidak sesuai deskripsi, atau hilang dalam proses pengiriman setelah konfirmasi ekspedisi.</p>

                                    <h6>15. Perubahan Kebijakan</h6>
                                    <p>Kami dapat memperbarui Kebijakan Privasi ini dari waktu ke waktu. Versi terbaru akan ditampilkan di situs web.</p>

                                    <h6>16. Persetujuan</h6>
                                    <p>Dengan menggunakan layanan kami, Anda menyatakan telah membaca, memahami, dan menyetujui seluruh isi Kebijakan Privasi ini.</p>

                                    <h6>17. Izin Akses Perangkat (Permissions)</h6>
                                    <p>Untuk memastikan aplikasi berfungsi optimal, kami meminta izin akses ke Kamera (foto profil/QR), GPS/Lokasi (akurasi pengantaran kurir), Browser/Akses Internet, dan Pengiriman sinkronisasi Data Pribadi.</p>

                                    <h6>18. Kontak Kami</h6>
                                    <p>Jika Anda memiliki pertanyaan keluhan silakan hubungi kami melalui halaman kontak resmi di Sancaka Express.</p>

                                    <h5 class="fw-bold border-bottom pb-1 mb-2 mt-4">SYARAT & KETENTUAN</h5>
                                    <p>Halaman ini berisi syarat & ketentuan resmi yang berlaku di Sancaka Express, Sancaka Store, Toko Sancaka, dan Sancaka Marketplace. Mohon dibaca dengan seksama karena semua poin ini mengikat setiap pengguna layanan kami.</p>
                                    <p>Penerimaan Ketentuan, Pendaftaran Akun valid, Kerahasiaan sandi pengguna, Penggunaan platform yang sah secara hukum Republik Indonesia, Transaksi luar platform tidak diakui, klaim paket rusak maksimal 1x24 jam wajib menyertakan bukti video unboxing, pembatalan sepihak dilarang jika pesanan kurir sudah berjalan, penyalahgunaan akun berakibat penonaktifan sepihak oleh admin, penyelesaian sengketa diselesaikan secara kekeluargaan / musyawarah mufakat.</p>
                                    <p class="fw-bold text-success text-center mt-3 bg-light p-2 border rounded">--- AKHIR DOKUMEN PERATURAN ---</p>
                                </div>

                                {{-- Pilihan Persetujuan & Tanda Centang --}}
                                <div class="form-check mb-4 bg-light p-3 rounded border d-flex align-items-center gap-2" id="checkboxWrapper" style="opacity: 0.5; pointer-events: none;">
                                    <input class="form-check-input ms-0" type="checkbox" value="" id="agreeCheckbox" disabled>
                                    <label class="form-check-input-label fw-bold text-dark small" for="agreeCheckbox" style="cursor: pointer;">
                                        Saya telah membaca lengkap Peraturan Mitra & Kebijakan Privasi, dan saya setuju terikat secara hukum dengan ketentuan Sancaka Express.
                                    </label>
                                </div>

                                {{-- Tombol Submit Form --}}
                                <button type="submit" id="submitBtn" class="btn btn-secondary btn-lg w-100 rounded-pill fw-bold shadow-sm py-3" disabled>
                                    <i class="fa-solid fa-lock me-2"></i> Baca Peraturan Terlebih Dahulu
                                </button>
                            </div>
                        </div>

                    </form>
                </div>
            </div>
            
        </div>
    </div>
</div>

{{-- =========================================================== --}}
{{-- JAVASCRIPT: LOKASI GPS & SCROLL TO ENABLE BUTTON            --}}
{{-- =========================================================== --}}
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // --- LOGIC GPS ---
        const btnGetLocation = document.getElementById('btnGetLocation');
        const latInput = document.getElementById('latitude');
        const lngInput = document.getElementById('longitude');
        const statusText = document.getElementById('gpsStatus');

        btnGetLocation.addEventListener('click', function() {
            if (navigator.geolocation) {
                btnGetLocation.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i> Mencari koordinat...';
                btnGetLocation.disabled = true;
                statusText.innerHTML = '<span class="text-muted">Sedang meminta izin akses lokasi...</span>';

                navigator.geolocation.getCurrentPosition(
                    function(position) {
                        latInput.value = position.coords.latitude;
                        lngInput.value = position.coords.longitude;
                        btnGetLocation.innerHTML = '<i class="fa-solid fa-check text-success me-2"></i> Berhasil Didapatkan';
                        btnGetLocation.classList.replace('btn-get-location', 'btn-light');
                        btnGetLocation.disabled = false;
                        statusText.innerHTML = '<span class="text-success fw-bold"><i class="fa-solid fa-check-circle"></i> Koordinat berhasil disinkronkan! Gunakan HP untuk mendapatkan titik yang akurat, Terimakasih</span>';
                        setTimeout(() => {
                            btnGetLocation.innerHTML = '<i class="fa-solid fa-location-crosshairs me-2 text-danger"></i> Perbarui Lokasi Perangkat';
                            btnGetLocation.classList.replace('btn-light', 'btn-get-location');
                        }, 3000);
                    },
                    function(error) {
                        btnGetLocation.disabled = false;
                        btnGetLocation.innerHTML = '<i class="fa-solid fa-location-crosshairs me-2 text-danger"></i> Dapatkan Lokasi Otomatis via Perangkat';
                        statusText.innerHTML = '<span class="text-danger fw-bold"><i class="fa-solid fa-triangle-exclamation"></i> Gagal mendeteksi GPS. Silakan isi manual.</span>';
                    },
                    { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
                );
            }
        });

        // --- LOGIC SCROLL TO READ (Wajib Baca Sebelum Daftar) ---
        const tosBox = document.getElementById('tosScrollBox');
        const agreeCheckbox = document.getElementById('agreeCheckbox');
        const checkboxWrapper = document.getElementById('checkboxWrapper');
        const submitBtn = document.getElementById('submitBtn');

        let hasScrolledToBottom = false;

        tosBox.addEventListener('scroll', function() {
            // Toleransi 5 piksel dari bawah box untuk mendeteksi scroll mentok dasar
            if (!hasScrolledToBottom && (tosBox.scrollHeight - tosBox.scrollTop <= tosBox.clientHeight + 5)) {
                hasScrolledToBottom = true;
                
                // Aktifkan area checkbox persetujuan
                checkboxWrapper.style.opacity = "1";
                checkboxWrapper.style.pointerEvents = "auto";
                agreeCheckbox.disabled = false;
                
                // Berikan tanda visual box berganti border hijau sebagai indikator selesai baca
                tosBox.style.borderColor = "#10b981";
            }
        });

        // Listener Checkbox untuk memicu tombol aktif jadi merah
        agreeCheckbox.addEventListener('change', function() {
            if (agreeCheckbox.checked && hasScrolledToBottom) {
                // Tombol aktif penuh (Warna Merah Sancaka)
                submitBtn.disabled = false;
                submitBtn.classList.replace('btn-secondary', 'btn-danger');
                submitBtn.innerHTML = '<i class="fa-solid fa-paper-plane me-2"></i> Kirim Pendaftaran Sekarang';
            } else {
                // Kunci kembali tombol jika centang dilepas
                submitBtn.disabled = true;
                submitBtn.classList.replace('btn-danger', 'btn-secondary');
                submitBtn.innerHTML = '<i class="fa-solid fa-lock me-2"></i> Baca Peraturan Terlebih Dahulu';
            }
        });
    });
</script>
@endsection