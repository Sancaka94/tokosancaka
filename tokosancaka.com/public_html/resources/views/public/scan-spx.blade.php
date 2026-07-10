<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">

    <title>Daftarkan Paket SPX Express - Sancaka Express</title>

    <link rel="icon" type="image/png" href="https://tokosancaka.com/storage/uploads/sancaka.png">
    <link rel="apple-touch-icon" href="https://tokosancaka.com/storage/uploads/sancaka.png">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        /* Desain Clean Minimalist Next.js Style (Black & White) */
        body { 
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; 
            background-color: #ffffff; 
            color: #111111;
        }
        
        .form-control, .form-select {
            border-radius: 6px;
            border: 1px solid #d1d5db;
        }
        .form-control:focus, .form-select:focus {
            border-color: #000000;
            box-shadow: 0 0 0 2px rgba(0, 0, 0, 0.1);
        }

        .btn-black { 
            background-color: #000000; 
            border-color: #000000; 
            color: #ffffff; 
            border-radius: 6px;
        }
        .btn-black:hover, .btn-black:focus { 
            background-color: #333333; 
            border-color: #333333; 
            color: #ffffff; 
        }

        .btn-outline-black {
            background-color: transparent;
            border: 1px solid #d1d5db;
            color: #000000;
            border-radius: 6px;
        }
        .btn-outline-black:hover {
            background-color: #f9fafb;
            color: #000000;
            border-color: #000000;
        }

        #reader { border: 2px dashed #d1d5db; border-radius: 0.75rem; }
        
        .scan-history-item, .flash-message { animation: fadeIn 0.5s ease-in-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; translateY(0); } }

        /* Custom Alert Box - Clean */
        .spx-alert-box {
            position: sticky;
            top: 20px;
            z-index: 99;
            display: flex;
            align-items: flex-start;
            gap: 15px;
            background-color: #ffffff;
            border: 1px solid #e5e7eb;
            border-left: 4px solid #000000;
            color: #000000;
            padding: 16px 40px 16px 20px;
            margin-bottom: 20px;
            border-radius: 6px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            line-height: 1.5;
            position: relative;
        }

        .spx-alert-icon {
            color: #000000;
            flex-shrink: 0;
            margin-top: 2px;
        }

        .spx-alert-content strong {
            display: block;
            margin-bottom: 4px;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .spx-alert-content p { margin: 0; font-size: 13px; color: #4b5563; }
        .spx-alert-content .highlight { font-weight: 600; color: #000; }

        .spx-close-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            width: 24px;
            height: 24px;
            cursor: pointer;
            color: #9ca3af;
            transition: color 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .spx-close-btn:hover { color: #000000; }

        /* Promo Box - Clean */
        .promo-box-clean {
            position: sticky;
            top: 20px;
            z-index: 99;
            display: flex;
            flex-direction: column;
            gap: 10px;
            background-color: #fafafa;
            border: 1px solid #e5e7eb;
            color: #000000;
            padding: 16px 40px 16px 20px;
            margin-bottom: 20px;
            border-radius: 6px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            line-height: 1.5;
            position: relative;
            animation: slideIn 0.4s ease-out;
        }

        .promo-list { list-style: none; padding: 0; margin: 5px 0; }
        .promo-list li { margin-bottom: 6px; font-size: 13px; display: flex; align-items: flex-start; gap: 8px; color: #4b5563; }
        .promo-list li i { color: #000000; margin-top: 3px; }

        .card { border: 1px solid #e5e7eb !important; border-radius: 8px; }
        .text-muted { color: #6b7280 !important; }
    </style>
</head>
<body>

    {{-- Header bisa Anda include dari partial --}}
    @include('layouts.partials.public-header')
    @include('layouts.partials.notifications')

    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="row g-4">

                    <div class="col-lg-7">

    <div id="alert-container"></div>

    <div id="gps-status-container" class="mb-3"></div>

    <div class="spx-alert-box" id="scan-warning">
        <div class="spx-alert-icon">
            <svg viewBox="0 0 24 24" fill="currentColor" width="24" height="24">
                <path d="M12 2L1 21h22L12 2zm0 3.516L20.297 19H3.703L12 5.516zM11 10v4h2v-4h-2zm0 6v2h2v-2h-2z"/>
            </svg>
        </div>

        <div class="spx-alert-content">
            <strong>INFORMASI PENTING:</strong>
            <p style="margin-top: 5px;">
                1. FOKUSKAN KAMERA ATAU KETIK DI KOLOM RESI, LALU TEKAN (ENTER).<br>
                2. PASTIKAN HANYA SCAN <span class="highlight">BARCODE 2D (QR)</span> ATAU RESI BERAWALAN <span class="highlight">SPX / ID</span>.<br>
                3. TANDA BERHASIL ADALAH SUARA "BEEP" DAN NOTIFIKASI PADA LAYAR.<br>
                4. PASTIKAN GPS AKTIF DAN JUMLAH PAKET BERTAMBAH.<br>
                5. KLIK TOMBOL SURAT JALAN JIKA SCAN SELESAI.
            </p>
        </div>

        <div class="spx-close-btn" onclick="showPromo()" title="Tutup">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
        </div>
    </div>

    <div id="promo-offer" class="promo-box-clean d-none">
        <div style="display: flex; align-items: flex-start; gap: 15px;">
            <div style="font-size: 24px; color: #000;"><i class="fas fa-rocket"></i></div>
            <div>
                <strong style="font-size: 15px; text-transform: uppercase;">Mau Fitur Lebih Lengkap? Gabung Mitra Sancaka</strong>
                <p style="font-size: 13px; margin-bottom: 8px; color: #4b5563;">Dapatkan akses eksklusif dengan menjadi member/agen kami:</p>

                <ul class="promo-list">
                    <li><i class="fas fa-check"></i> Akses Full Fitur Aplikasi Sancaka Express.</li>
                    <li><i class="fas fa-store"></i> <a href="https://tokosancaka.com/etalase" target="_blank" style="text-decoration: underline; color: #000;">Berjualan di Marketplace Sancaka</a> (Jangkauan Luas).</li>
                    <li><i class="fas fa-wallet"></i> Jadi AGEN Loket PPOB Dengan Harga Kulak Kompetitif dan Realtime.</li>
                    <li><i class="fas fa-chart-line"></i> Monitor Jumlah Kiriman Paket ALL Ekpedisi & SPX Realtime.</li>
                    <li><i class="fas fa-search-location"></i> Lacak Status & Surat Jalan SPX dengan Detail.</li>
                    <li><i class="fas fa-box"></i> Kirim Paket Multi Ekspedisi: Support POS, JNE, J&T, ID Express, SiCepat, Ninja Xpress.</li>
                    <li><i class="fas fa-credit-card"></i> Pembayaran Otomatis: Topup Saldo, QRIS, Virtual Account, Indomaret & Alfamart.</li>
                    <li><i class="fas fa-map-pin"></i> Live Tracking Realtime via Link: <a href="https://tokosancaka.com/tracking" target="_blank" style="color: #000; font-weight: 500;">tokosancaka.com/tracking</a></li>
                    <li><i class="fas fa-chart-pie"></i> Laporan Keuangan & Grafik Analisa: Monitor omzet dan performa.</li>
                </ul>

                <a href="{{ route('register') }}" class="btn btn-black btn-sm mt-2">Daftar Agen Sekarang &rarr;</a>
            </div>
        </div>

        <div class="spx-close-btn" onclick="document.getElementById('promo-offer').classList.add('d-none')" title="Tutup Promo">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <label for="search-input" class="form-label fw-semibold text-dark">Langkah 1: Cari Nama Anda</label>
            <div class="position-relative">
                <i class="fas fa-user position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
                <input type="text" id="search-input" placeholder="Ketik nama atau nomor HP Anda..." class="form-control ps-5" autocomplete="off" disabled>
                <div id="search-results-container" class="position-absolute z-3 w-100 mt-1 bg-white border rounded-3 shadow-sm d-none">
                    <ul id="search-results" class="list-group list-group-flush"></ul>
                </div>
            </div>
        </div>
    </div>

    <!-- UPDATE FORM REGISTRASI -->
    <div id="registration-form-container" class="card border-0 shadow-sm mb-4 d-none">
        <div class="card-body p-4">
            <h3 class="h6 fw-bold text-dark mb-3 text-uppercase">Pendaftaran Kontak Baru</h3>
            <form id="registration-form">
                <div class="mb-3"><label for="reg-nama" class="form-label">Nama Lengkap</label><input type="text" id="reg-nama" class="form-control" required></div>
                <div class="mb-3"><label for="reg-no_hp" class="form-label">No. HP (WhatsApp)</label><input type="tel" id="reg-no_hp" class="form-control" required></div>
                <div class="mb-3"><label for="reg-alamat" class="form-label">Alamat Lengkap</label><textarea id="reg-alamat" rows="2" class="form-control" required></textarea></div>
                
                <div class="mb-3"><label for="reg-email" class="form-label">Email Valid</label><input type="email" id="reg-email" class="form-control" placeholder="nama@email.com" required></div>
                
                <!-- OPSI GENDER / INSTANSI BARU -->
                <div class="mb-3">
                    <label for="reg-jk" class="form-label">Tipe Pengirim</label>
                    <select id="reg-jk" class="form-select" required>
                        <option value="">Pilih...</option>
                        <option value="Laki-laki">Laki-laki</option>
                        <option value="Perempuan">Perempuan</option>
                        <option value="Pribadi">Atas Nama Pribadi</option>
                        <option value="Perusahaan">Atas Nama Perusahaan</option>
                    </select>
                </div>
                <div class="mb-3 d-none" id="reg-instansi-container">
                    <label for="reg-instansi" class="form-label">Nama Instansi / Perusahaan</label>
                    <input type="text" id="reg-instansi" class="form-control" placeholder="Contoh: CV / PT / Toko Sancaka">
                </div>

                <button type="submit" class="btn btn-black w-100 fw-semibold">Daftar & Lanjutkan</button>
            </form>
        </div>
    </div>

    <!-- UPDATE FORM LENGKAPI PROFIL -->
    <div id="completion-form-container" class="card border-0 shadow-sm mb-4 d-none">
        <div class="card-body p-4">
            <div class="alert alert-light border mb-3" style="color: #000;">
                <i class="fas fa-info-circle me-2"></i><strong>Lengkapi Data Anda</strong><br>
                <span class="text-muted" style="font-size: 13px;">Kami membutuhkan informasi tambahan untuk melampirkan resi elektronik.</span>
            </div>
            <form id="completion-form">
                <input type="hidden" id="comp-id">
                <div class="mb-3">
                    <label for="comp-email" class="form-label">Email Valid</label>
                    <input type="email" id="comp-email" class="form-control" placeholder="nama@email.com" required>
                </div>

                <!-- OPSI GENDER / INSTANSI BARU -->
                <div class="mb-3">
                    <label for="comp-jk" class="form-label">Tipe Pengirim</label>
                    <select id="comp-jk" class="form-select" required>
                        <option value="">Pilih...</option>
                        <option value="Laki-laki">Laki-laki</option>
                        <option value="Perempuan">Perempuan</option>
                        <option value="Pribadi">Atas Nama Pribadi</option>
                        <option value="Perusahaan">Atas Nama Perusahaan</option>
                    </select>
                </div>
                <div class="mb-3 d-none" id="comp-instansi-container">
                    <label for="comp-instansi" class="form-label">Nama Instansi / Perusahaan</label>
                    <input type="text" id="comp-instansi" class="form-control" placeholder="Contoh: CV / PT / Toko Sancaka">
                </div>

                <button type="submit" class="btn btn-black w-100 fw-semibold">Simpan & Lanjutkan Scan</button>
            </form>
        </div>
    </div>

    <div id="scan-resi-section" class="card border-0 shadow-sm opacity-50" style="pointer-events: none; transition: opacity 0.3s;">
        <div class="card-body p-4">
            <label class="form-label fw-semibold text-dark">Langkah 2: Scan atau Ketik Resi SPX</label>
            <div class="input-group mb-3">
                <span class="input-group-text bg-white"><i class="fas fa-barcode"></i></span>
                <input type="text" id="resi-input" placeholder="Nomor resi SPX..." class="form-control">
            </div>
            <div class="d-grid gap-2">
                <button type="button" id="start-camera-btn" class="btn btn-black w-100 fw-semibold"><i class="fas fa-camera me-2"></i>Gunakan Kamera</button>
                <button type="button" id="gallery-scan-btn" class="btn btn-outline-black w-100 fw-semibold"><i class="fas fa-images me-2"></i>Dari Galeri</button>
            </div>
            <input type="file" id="gallery-file-input" class="d-none" accept="image/*">
        </div>
    </div>

</div>

                    <div class="col-lg-5">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body p-4">
                                <h2 class="h6 fw-bold text-dark mb-3 text-uppercase">Hasil Scan Paket</h2>
                                <div class="p-4 rounded-3 mb-3 text-center" style="background: #f9fafb; border: 1px solid #e5e7eb;">
                                    <p class="mb-1 fw-semibold text-muted" style="font-size: 14px;">Jumlah Paket</p>
                                    <p id="pickup-count" class="display-6 fw-bolder mb-0 text-dark">0</p>
                                </div>

                                <hr style="border-color: #e5e7eb;">
                                <h3 class="h6 fw-semibold text-dark mb-3">Riwayat Scan:</h3>
                                <div id="scan-history" class="vstack gap-2" style="max-height: 260px; overflow-y: auto;">
                                    <p class="text-muted text-center" style="font-size: 14px;">Belum ada paket yang di-scan.</p>
                                </div>
                                <hr style="border-color: #e5e7eb;">
                                <div id="action-buttons" class="d-grid gap-2 mt-auto">
                                    <button type="button" id="surat-jalan-btn" class="btn btn-black w-100 fw-semibold" disabled>Langkah 3: Buat Surat Jalan</button>
                                    <button type="button" id="whatsapp-btn" class="btn btn-outline-black w-100 fw-semibold d-none">Langkah 4: Konfirmasi Admin</button>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="downloadApkModal" tabindex="-1" aria-labelledby="downloadApkModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-sm">
                <div class="modal-header border-0 pb-0">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center pb-4 pt-0 px-4">
                    <div class="mb-3">
                        <i class="fab fa-android text-dark" style="font-size: 4rem;"></i>
                    </div>
                    <h4 class="modal-title fw-bold mb-2 text-dark" id="downloadApkModalLabel">Aplikasi Sancaka Express</h4>
                    <p class="text-muted mb-4" style="font-size: 14px;">
                        Download aplikasi Android kami untuk pengalaman scan resi, cek status, dan kelola paket yang lebih cepat dan praktis!
                    </p>
                    <a href="https://play.google.com/store/apps/details?id=com.sancaka.express.app"
                       class="btn btn-black btn-lg w-100 fw-semibold"
                       target="_blank"
                       onclick="tutupModalApk()">
                        <i class="fas fa-download me-2"></i> Download APK Sekarang
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal dan Footer bisa Anda include --}}
    @include('layouts.partials.scan-modal')
    @include('layouts.partials.public-footer')

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>

    <audio id="beep-success" src="https://tokosancaka.com/public/sound/beep.mp3" preload="auto"></audio>
    <audio id="beep-fail" src="https://tokosancaka.biz.id/public/sound/beep-gagal.mp3" preload="auto"></audio>

    <script>
    document.addEventListener('DOMContentLoaded', function () {

        // ====== LOGIKA TAMPILKAN/SEMBUNYIKAN NAMA INSTANSI ======
        const regJk = document.getElementById('reg-jk');
        if(regJk) {
            regJk.addEventListener('change', function() {
                const container = document.getElementById('reg-instansi-container');
                if(this.value === 'Perusahaan') {
                    container.classList.remove('d-none');
                    document.getElementById('reg-instansi').setAttribute('required', 'true');
                } else {
                    container.classList.add('d-none');
                    document.getElementById('reg-instansi').removeAttribute('required');
                    document.getElementById('reg-instansi').value = '';
                }
            });
        }

        const compJk = document.getElementById('comp-jk');
        if(compJk) {
            compJk.addEventListener('change', function() {
                const container = document.getElementById('comp-instansi-container');
                if(this.value === 'Perusahaan') {
                    container.classList.remove('d-none');
                    document.getElementById('comp-instansi').setAttribute('required', 'true');
                } else {
                    container.classList.add('d-none');
                    document.getElementById('comp-instansi').removeAttribute('required');
                    document.getElementById('comp-instansi').value = '';
                }
            });
        }
        // =========================================================

        // Elemen
        const searchInput = document.getElementById('search-input');
        const searchResultsContainer = document.getElementById('search-results-container');
        const searchResultsUl = document.getElementById('search-results');
        const registrationFormContainer = document.getElementById('registration-form-container');
        const registrationForm = document.getElementById('registration-form');
        const scanResiSection = document.getElementById('scan-resi-section');
        const completionFormContainer = document.getElementById('completion-form-container');
        const completionForm = document.getElementById('completion-form');
        const resiInput = document.getElementById('resi-input');
        const alertContainer = document.getElementById('alert-container');
        const scanHistoryContainer = document.getElementById('scan-history');
        const pickupCountEl = document.getElementById('pickup-count');
        const suratJalanBtn = document.getElementById('surat-jalan-btn');
        const whatsappBtn = document.getElementById('whatsapp-btn');
        const startCameraBtn = document.getElementById('start-camera-btn');
        const galleryScanBtn = document.getElementById('gallery-scan-btn');
        const galleryFileInput = document.getElementById('gallery-file-input');
        const cameraModalEl = document.getElementById('camera-modal');
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        
        // Elemen status GPS
        const gpsStatusContainer = document.getElementById('gps-status-container');

        // Audio beep
        const beepSuccess = document.getElementById('beep-success');
        const beepFail = document.getElementById('beep-fail');

        // Modal Apk
        const isApkModalSeen = localStorage.getItem('sancaka_apk_modal_seen');
        if (!isApkModalSeen) {
            setTimeout(() => {
                const apkModalEl = document.getElementById('downloadApkModal');
                if (apkModalEl) {
                    const apkModal = new bootstrap.Modal(apkModalEl);
                    apkModal.show();
                    localStorage.setItem('sancaka_apk_modal_seen', 'true');
                }
            }, 1000);
        }

        // State
        let selectedKontak = null;
        let scannedResiArray = [];
        let searchTimeout;
        let html5QrCode = null;
        let cameraModal = null;
        let generatedSuratJalan = null;

        // State Geolocation
        let latitude = null;
        let longitude = null;
        let isLoadingLocation = true;
        let locationError = '';

        if(cameraModalEl) cameraModal = new bootstrap.Modal(cameraModalEl);


        const updateGpsStatusUI = (message, type = 'secondary') => {
            let icon = 'fa-spinner fa-spin';
            if (type === 'dark') icon = 'fa-map-marker-alt'; // success -> dark for clean look
            if (type === 'danger') icon = 'fa-exclamation-triangle'; // danger -> keep alert color or use custom border

            gpsStatusContainer.innerHTML = `
                <div class="alert alert-light border border-${type === 'danger' ? 'danger' : 'dark'} d-flex align-items-center" role="alert" style="color: #000;">
                    <i class="fas ${icon} me-2 ${type === 'danger' ? 'text-danger' : ''}"></i>
                    <div>${message}</div>
                </div>
            `;
        };

        const initGeolocation = () => {
            updateGpsStatusUI('Sedang mengambil lokasi GPS Anda. Mohon tunggu...', 'secondary');
            searchInput.placeholder = 'Mengambil GPS...';

            if (!'geolocation' in navigator) {
                locationError = 'Browser Anda tidak mendukung Geolocation.';
                updateGpsStatusUI(locationError, 'danger');
                isLoadingLocation = false;
                return;
            }

            navigator.geolocation.getCurrentPosition(
                (position) => {
                    latitude = position.coords.latitude;
                    longitude = position.coords.longitude;
                    isLoadingLocation = false;
                    locationError = '';

                    updateGpsStatusUI('Lokasi GPS didapat. Silakan cari nama Anda.', 'dark');
                    searchInput.disabled = false; 
                    searchInput.placeholder = 'Ketik nama atau nomor HP Anda...';

                    setTimeout(() => {
                        if (!locationError) gpsStatusContainer.innerHTML = '';
                    }, 2000);
                },
                (error) => {
                    switch(error.code) {
                        case error.PERMISSION_DENIED:
                            locationError = 'Anda menolak izin GPS. Harap aktifkan/izinkan untuk melanjutkan.';
                            break;
                        case error.POSITION_UNAVAILABLE:
                            locationError = 'Informasi lokasi tidak tersedia. Coba refresh halaman.';
                            break;
                        case error.TIMEOUT:
                            locationError = 'Waktu permintaan lokasi habis. Coba refresh halaman.';
                            break;
                        default:
                            locationError = 'Terjadi kesalahan saat mengambil lokasi. Coba refresh halaman.';
                    }
                    updateGpsStatusUI(locationError, 'danger'); 
                    searchInput.placeholder = 'GPS Gagal. Harap izinkan lokasi.';
                    isLoadingLocation = false;
                }
            );
        };

        const showAlert = (message, type='dark') => {
            alertContainer.innerHTML = `<div class="alert alert-light border border-${type === 'danger' ? 'danger' : 'dark'}" style="color:#000;">${message}</div>`;
            setTimeout(() => { alertContainer.innerHTML = ''; }, 4000);
        };

        const playBeep = (success=true) => {
            if(success) beepSuccess.play();
            else beepFail.play();
        };

       const selectKontak = (kontak) => {
            if (isLoadingLocation || locationError) {
                showAlert('Lokasi GPS wajib diaktifkan. ' + (locationError || 'Tunggu GPS selesai loading.'), 'dark');
                return;
            }

            // Memeriksa kelengkapan data (termasuk tipe pengirim)
            if (!kontak.email || !kontak.jenis_kelamin) {
                searchInput.value = kontak.nama;
                searchInput.disabled = true;
                searchResultsContainer.classList.add('d-none');
                completionFormContainer.classList.remove('d-none');
                
                document.getElementById('comp-id').value = kontak.id;
                return; 
            }

            selectedKontak = kontak;

            let hpSensor = kontak.no_hp;
            if(hpSensor.length > 6) {
                let depan = hpSensor.substring(0, 3);
                let belakang = hpSensor.substring(hpSensor.length - 3);
                hpSensor = `${depan} *** *** ${belakang}`;
            }
            
            searchInput.value = `${kontak.nama} (${hpSensor})`;
            searchInput.disabled = true;
            searchResultsContainer.classList.add('d-none');
            registrationFormContainer.classList.add('d-none');
            completionFormContainer.classList.add('d-none'); 
            scanResiSection.classList.remove('opacity-50');
            scanResiSection.style.pointerEvents = 'auto';
            resiInput.focus();
            showAlert(`Halo <strong>${kontak.nama}</strong>, SILAKAN SCAN RESI SPX ANDA DI BAWAH INI.`, 'dark');
        };

        const handleScan = async (resiValue) => {
            if(!selectedKontak) return showAlert('Pilih atau daftar kontak dulu.', 'dark');
            if(!resiValue) return;

            if (isLoadingLocation || locationError) {
                playBeep(false);
                showAlert('Lokasi GPS wajib diaktifkan. ' + (locationError || 'Tunggu GPS selesai loading.'), 'danger');
                return;
            }

            try {
                const response = await fetch("{{ route('scan.spx.handle') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        kontak_id: selectedKontak.id,
                        resi: resiValue,
                        latitude: latitude,
                        longitude: longitude
                    })
                });

                if (response.status === 422) {
                    const result = await response.json();
                    let errorMsg = result.message || 'Data tidak valid.';
                    if (result.errors) {
                        if (result.errors.latitude) errorMsg = 'Data latitude wajib diisi.';
                        if (result.errors.longitude) errorMsg = 'Data longitude wajib diisi.';
                    }
                    playBeep(false);
                    showAlert(errorMsg, 'danger');
                    return;
                }

                const result = await response.json();
                if(response.ok && result.success){
                    playBeep(true);
                    if(scanHistoryContainer.querySelector('p')) scanHistoryContainer.innerHTML = '';
                    const html = `<div class="scan-history-item d-flex justify-content-between p-2 border-bottom">
                                    <span class="fw-medium text-dark">${result.data.nomor_resi}</span>
                                    <small class="text-muted">${result.data.waktu_scan}</small>
                                </div>`;
                    scanHistoryContainer.insertAdjacentHTML('afterbegin', html);
                    scannedResiArray.push(result.data.nomor_resi);
                    pickupCountEl.textContent = scannedResiArray.length;
                    suratJalanBtn.disabled = false;
                } else {
                    playBeep(false);
                    showAlert(result.message || 'Resi gagal atau sudah di-scan.', 'danger');
                }
            } catch (err) {
                playBeep(false);
                showAlert('Koneksi error.', 'danger');
            }
            resiInput.value = '';
            resiInput.focus();
        };

        searchInput.addEventListener('keyup', () => {
            clearTimeout(searchTimeout);
            if(searchInput.value.length < 2){
                searchResultsContainer.classList.add('d-none');
                return;
            }
            searchTimeout = setTimeout(async () => {
                try {
                    const response = await fetch(`{{ route('api.search.kontak') }}?query=${searchInput.value}`);
                    const data = await response.json();
                    searchResultsUl.innerHTML = '';
                    if(data.length > 0){
                        data.forEach(kontak => {
                            const li = document.createElement('li');
                            li.className = 'list-group-item list-group-item-action cursor-pointer';

                            let hpSensor = kontak.no_hp;
                            if(hpSensor.length > 6) {
                                let depan = hpSensor.substring(0, 3);
                                let belakang = hpSensor.substring(hpSensor.length - 3);
                                hpSensor = `${depan} *** *** ${belakang}`;
                            }

                            li.innerHTML = `<div class="fw-semibold text-dark">${kontak.nama}</div><small class="text-muted">${hpSensor}</small>`;
                            li.onclick = () => selectKontak(kontak);
                            searchResultsUl.appendChild(li);
                        });
                    } else {
                        searchResultsUl.innerHTML = '<li class="list-group-item text-muted">Nama tidak ditemukan. Silakan daftar di bawah.</li>';
                        registrationFormContainer.classList.remove('d-none');
                        document.getElementById('reg-nama').value = searchInput.value;
                    }
                    searchResultsContainer.classList.remove('d-none');
                } catch(err){
                    console.error(err);
                }
            }, 500);
        });

        // Form registrasi baru submit
        registrationForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = {
                nama: document.getElementById('reg-nama').value,
                no_hp: document.getElementById('reg-no_hp').value,
                alamat: document.getElementById('reg-alamat').value,
                email: document.getElementById('reg-email').value, 
                jenis_kelamin: document.getElementById('reg-jk').value,
                instansi_perusahaan: document.getElementById('reg-instansi').value // Data instansi
            };
            try {
                const response = await fetch("{{ route('kontak.register') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type':'application/json',
                        'X-CSRF-TOKEN':csrfToken,
                        'Accept':'application/json'
                    },
                    body: JSON.stringify(formData)
                });
                const result = await response.json();
                if(response.ok && result.success){
                    selectKontak(result.data);
                } else {
                    showAlert(result.message || 'Gagal mendaftar. Pastikan email/nomor WA belum pernah digunakan.', 'danger');
                }
            } catch(err){
                showAlert('Koneksi error.', 'danger');
            }
        });

        // Form Lengkapi Data submit
        completionForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const idKontak = document.getElementById('comp-id').value;
            const formData = {
                email: document.getElementById('comp-email').value,
                jenis_kelamin: document.getElementById('comp-jk').value,
                instansi_perusahaan: document.getElementById('comp-instansi').value // Data instansi
            };
            
            try {
                const url = `/kontak/${idKontak}/lengkapi-profil`;
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type':'application/json',
                        'X-CSRF-TOKEN':csrfToken,
                        'Accept':'application/json'
                    },
                    body: JSON.stringify(formData)
                });
                const result = await response.json();
                
                if(response.ok && result.success){
                    completionFormContainer.classList.add('d-none');
                    selectKontak(result.data);
                } else {
                    showAlert(result.message || 'Gagal memperbarui data. Email mungkin sudah dipakai.', 'danger');
                }
            } catch(err){
                showAlert('Koneksi error.', 'danger');
            }
        });

        // Scan input resi manual
        resiInput.addEventListener('keypress', e=>{
            if(e.key==='Enter'){
                e.preventDefault();
                handleScan(resiInput.value);
            }
        });

        // Tombol Surat Jalan
        suratJalanBtn.addEventListener('click', async () => {
            if (scannedResiArray.length === 0) {
                return showAlert('Belum ada paket di-scan!', 'dark');
            }

            if (isLoadingLocation || locationError) {
                showAlert('Lokasi GPS wajib diaktifkan. ' + (locationError || 'Tunggu GPS selesai loading.'), 'danger');
                return;
            }

            try {
                const response = await fetch("{{ route('scan.spx.suratjalan.create') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        kontak_id: selectedKontak.id,
                        resi_list: scannedResiArray,
                        latitude: latitude,
                        longitude: longitude
                    })
                });

                const result = await response.json();
                if (response.ok && result.success) {
                    generatedSuratJalan = result;
                    window.open(result.pdf_url, '_blank');
                    whatsappBtn.classList.remove('d-none');
                } else {
                    showAlert(result.message || 'Gagal membuat Surat Jalan.', 'danger');
                }
            } catch (err) {
                showAlert('Koneksi error.', 'danger');
            }
        });

        // Tombol WhatsApp
        whatsappBtn.addEventListener('click', () => {
            if (!generatedSuratJalan) {
                return showAlert('Data Surat Jalan tidak tersedia. Silakan ulangi.', 'dark');
            }

            const kodeSuratJalan = generatedSuratJalan.kode_surat_jalan;
            const namaPengirim = generatedSuratJalan.customer_name;
            const jumlahPaket = generatedSuratJalan.package_count;
            const createdAt = generatedSuratJalan.created_at;

            let googleMapsUrl = 'Lokasi tidak tersedia';
            if (latitude && longitude) {
                googleMapsUrl = `https://www.google.com/maps?q=${latitude},${longitude}`;
            }

            let message = `📦 *Konfirmasi Input Paket SPX Express*\n\n`;
            message += `Halo Admin Sancaka Express,\nMohon diproses untuk pickup paket dengan detail berikut:\n\n`;
            message += `*Nomor Surat Jalan:*\n${kodeSuratJalan}\n\n`;
            message += `*Tanggal Dibuat:*\n${createdAt}\n\n`;
            message += `*Pengirim:*\n${namaPengirim}\n\n`;
            message += `*Jumlah Paket:*\n${jumlahPaket} Paket\n\n`;
            message += `*Lokasi Pickup (Maps):*\n${googleMapsUrl}\n\n`;
            message += `*Daftar Nomor Resi:*\n`;

            scannedResiArray.forEach((resi, index) => {
                message += `${index + 1}. ${resi}\n`;
            });

            message += `\nTerima kasih! 🙏`;

            const adminWhatsappNumber = '628819435180';
            const waUrl = `https://wa.me/${adminWhatsappNumber}?text=` + encodeURIComponent(message);
            window.open(waUrl, '_blank');
        });

        // Kamera QR
        startCameraBtn.addEventListener('click', ()=>{
            if(!html5QrCode) html5QrCode = new Html5Qrcode("reader");
            if(cameraModal) cameraModal.show();
            html5QrCode.start(
                { facingMode: "environment" },
                { fps: 10, qrbox: 250 },
                qrCodeMessage => handleScan(qrCodeMessage),
                errorMessage => {}
            ).catch(err=>{
                showAlert('Gagal membuka kamera.', 'danger');
                if(cameraModal) cameraModal.hide();
            });
        });

        // Stop kamera saat modal ditutup
        if(cameraModalEl){
            cameraModalEl.addEventListener('hidden.bs.modal', ()=>{
                if(html5QrCode && html5QrCode.isScanning) html5QrCode.stop().catch(()=>{});
            });
        }

        // Tombol galeri
        galleryScanBtn.addEventListener('click', ()=>galleryFileInput.click());
        galleryFileInput.addEventListener('change', async (e)=>{
            const file = e.target.files[0];
            if(!file) return;
            try {
                const result = await Html5Qrcode.scanFile(file, true);
                if(result) handleScan(result);
            } catch(err){
                showAlert('Gagal membaca file gambar.', 'danger');
            }
            galleryFileInput.value = '';
        });

        // Panggil fungsi Geolocation saat halaman dimuat
        initGeolocation();

    });

    // Fungsi untuk Menutup Alert & Memunculkan Promo
    function showPromo() {
        const alertBox = document.getElementById('scan-warning');
        if(alertBox) {
            alertBox.style.display = 'none';
        }

        const promoBox = document.getElementById('promo-offer');
        if(promoBox) {
            promoBox.classList.remove('d-none');
        }
    }

    // Fungsi untuk menutup modal secara otomatis setelah tombol download diklik
    function tutupModalApk() {
        const apkModalEl = document.getElementById('downloadApkModal');
        if (apkModalEl) {
            const apkModal = bootstrap.Modal.getInstance(apkModalEl);
            if (apkModal) {
                setTimeout(() => {
                    apkModal.hide();
                }, 500);
            }
        }
    }

    </script>

</body>
</html>