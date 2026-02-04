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
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        body { font-family: 'Poppins', sans-serif; background-color: #f8fafc; }
        .btn-orange { background-color: #f57224; border-color: #f57224; color: #fff; }
        .btn-orange:hover { background-color: #e05b10; border-color: #e05b10; color: #fff; }
        #reader { border: 3px dashed #fd7e14; border-radius: 0.75rem; }
        .scan-history-item, .flash-message { animation: fadeIn 0.5s ease-in-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; translateY(0); } }

        /* === UPDATE BAGIAN INI === */
    .spx-alert-box {
        position: sticky; /* Agar tetap nempel saat scroll */
        top: 20px; 
        z-index: 99;
        
        display: flex;
        align-items: flex-start;
        gap: 15px;
        
        background-color: #FEF2F2;
        border-left: 5px solid #DC2626;
        color: #991B1B;
        
        /* UPDATE: Tambah padding kanan biar teks tidak nabrak tombol X */
        padding: 16px 40px 16px 20px; 
        
        margin-bottom: 20px;
        border-radius: 4px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
        line-height: 1.5;
        
        /* Penting untuk posisi tombol X */
        position: relative; 
    }

    .spx-alert-icon {
        color: #DC2626;
        flex-shrink: 0;
        margin-top: 2px;
    }

    .spx-alert-content strong {
        display: block;
        margin-bottom: 4px;
        font-size: 15px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .spx-alert-content p {
        margin: 0;
        font-size: 14px;
    }

    .spx-alert-content .highlight {
        font-weight: bold;
        text-decoration: underline;
    }

    /* === TAMBAHAN BARU: TOMBOL CLOSE (X) === */
    .spx-close-btn {
        position: absolute;
        top: 10px;
        right: 10px;
        width: 24px;
        height: 24px;
        cursor: pointer;
        color: #991B1B;
        opacity: 0.6;
        transition: opacity 0.2s;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
    }

    .spx-close-btn:hover {
        opacity: 1;
        background-color: rgba(0,0,0,0.05); /* Efek hover halus */
    }

    /* Style untuk Box Promo Member (Warna Biru Langit) */
.promo-box-blue {
    position: sticky;
    top: 20px;
    z-index: 99;
    
    display: flex;
    flex-direction: column; /* Agar isi vertikal */
    gap: 10px;
    
    background-color: #eff6ff; /* Biru sangat muda */
    border-left: 5px solid #2563eb; /* Garis Biru Utama */
    color: #1e40af; /* Teks Biru Gelap */
    
    padding: 16px 40px 16px 20px;
    margin-bottom: 20px;
    border-radius: 4px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
    line-height: 1.5;
    position: relative;
    
    /* Animasi muncul */
    animation: slideIn 0.4s ease-out;
}

@keyframes slideIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

.promo-list {
    list-style: none;
    padding: 0;
    margin: 5px 0;
}

.promo-list li {
    margin-bottom: 6px;
    font-size: 14px;
    display: flex;
    align-items: flex-start;
    gap: 8px;
}

.promo-list li i {
    color: #2563eb; /* Ikon biru */
    margin-top: 3px;
}

.btn-join {
    background-color: #2563eb;
    color: white;
    border: none;
    padding: 8px 20px;
    border-radius: 6px;
    font-weight: 600;
    font-size: 14px;
    text-decoration: none;
    display: inline-block;
    margin-top: 10px;
    width: fit-content;
}

.btn-join:hover {
    background-color: #1d4ed8;
    color: white;
}
    
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

    <div id="promo-offer" class="promo-box-blue d-none">
        <div style="display: flex; align-items: flex-start; gap: 15px;">
            <div style="font-size: 24px;">🚀</div>
            <div>
                <strong style="font-size: 16px;">MAU FITUR LEBIH LENGKAP? GABUNG MITRA SANCAKA! GRATISSSS...</strong>
                <p style="font-size: 13px; margin-bottom: 8px; opacity: 0.8;">Dapatkan akses eksklusif dengan menjadi member/agen kami:</p>
                
                <ul class="promo-list">
                    <li><i class="fas fa-check-circle"></i> Akses Full Fitur Aplikasi Sancaka Express.</li>
                    <li><i class="fas fa-store"></i> <a href="https://tokosancaka.com/etalase" target="_blank" style="text-decoration: underline; color: inherit;"><strong>Berjualan di Marketplace Sancaka</strong></a> (Jangkauan Luas).</li>
                    <li><i class="fas fa-wallet"></i> Jadi AGEN Loket PPOB Dengan Harga Kulak Kompetitif dan Realtime (Jual Pulsa, Token Listrik, Bayar Air PDAM, dll).</li>
                    <li><i class="fas fa-chart-line"></i> Monitor Jumlah Kiriman Paket ALL Ekpedisi & SPX Realtime.</li>
                    <li><i class="fas fa-search-location"></i> Lacak Status & Surat Jalan SPX dengan Detail.</li>
                    
                    <li>
                        <i class="fas fa-truck-fast"></i> 
                    <span>
                        <strong>Kirim Paket Multi Ekspedisi:</strong> Support POS, JNE, J&T (Express/Cargo), ID Express, SiCepat, Ninja Xpress, dll.
                    </span>
                    </li>

                    <li>
                    <i class="fas fa-wallet"></i> 
                    <span>
                        <strong>Pembayaran Otomatis:</strong> Topup Saldo, QRIS, Virtual Account (Semua Bank), Indomaret & Alfamart.
                    </span>
                    </li>

                    <li>
                    <i class="fas fa-map-location-dot"></i> 
                    <span>
                        <strong>Live Tracking</strong> Realtime via Link: <a href="https://tokosancaka.com/tracking" target="_blank" style="color: #2563eb; font-weight: bold;">tokosancaka.com/tracking</a>
                    </span>
                    </li>

                    <li>
                    <i class="fas fa-chart-pie"></i> 
                    <span>
                        <strong>Laporan Keuangan & Grafik Analisa:</strong> Monitor omzet penjualan dan performa pengiriman ekspedisi Anda.
                    </span>
                    </li>

                </ul>

                <a href="{{ route('register') }}" class="btn-join">DAFTAR AGEN SEKARANG <i class="fas fa-arrow-right ms-1"></i></a>
            </div>
        </div>

        <div class="spx-close-btn" onclick="document.getElementById('promo-offer').classList.add('d-none')" style="color: #1e40af;" title="Tutup Promo">
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
                <div id="search-results-container" class="position-absolute z-3 w-100 mt-1 bg-white border rounded-3 shadow-lg d-none">
                    <ul id="search-results" class="list-group list-group-flush"></ul>
                </div>
            </div>
        </div>
    </div>

    <div id="registration-form-container" class="card border-0 shadow-sm mb-4 d-none">
        <div class="card-body p-4">
            <h3 class="h5 fw-semibold text-dark mb-3">Nama tidak ditemukan, silakan daftar.</h3>
            <form id="registration-form">
                <div class="mb-3"><label for="reg-nama" class="form-label">Nama Lengkap</label><input type="text" id="reg-nama" class="form-control" required></div>
                <div class="mb-3"><label for="reg-no_hp" class="form-label">No. HP (WhatsApp)</label><input type="tel" id="reg-no_hp" class="form-control" required></div>
                <div class="mb-3"><label for="reg-alamat" class="form-label">Alamat Lengkap</label><textarea id="reg-alamat" rows="3" class="form-control" required></textarea></div>
                <button type="submit" class="btn btn-orange w-100 fw-bold">Daftar & Lanjutkan</button>
            </form>
        </div>
    </div>

    <div id="scan-resi-section" class="card border-0 shadow-sm opacity-50" style="pointer-events: none; transition: opacity 0.3s;">
        <div class="card-body p-4">
            <label class="form-label fw-semibold text-dark">Langkah 2: Scan atau Ketik Resi SPX</label>
            <div class="input-group mb-3">
                <span class="input-group-text"><i class="fas fa-barcode"></i></span>
                <input type="text" id="resi-input" placeholder="Nomor resi SPX..." class="form-control">
            </div>
            <div class="d-grid gap-2">
                <button type="button" id="start-camera-btn" class="btn btn-orange w-100 fw-bold"><i class="fas fa-camera me-2"></i>Gunakan Kamera</button>
                <button type="button" id="gallery-scan-btn" class="btn btn-outline-secondary w-100 fw-bold"><i class="fas fa-images me-2"></i>Dari Galeri</button>
            </div>
            <input type="file" id="gallery-file-input" class="d-none" accept="image/*">
        </div>
    </div>

</div>

                    <div class="col-lg-5">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body p-4">
                                <h2 class="h5 fw-bold text-dark mb-3">Hasil Scan Paket Anda</h2>
                                <div class="p-4 rounded-4 mb-3 text-center"
     style="background: rgba(25, 135, 84, 0.12); border: 1px solid rgba(25, 135, 84, 0.35);">
    
    <p class="mb-1 fw-semibold" style="color:#198754;">
        Jumlah Paket
    </p>

    <p id="pickup-count" class="display-6 fw-bolder mb-0" style="color:#198754;">
        0
    </p>
</div>

                                <hr>
                                <h3 class="h6 fw-semibold text-dark mb-3">Riwayat Scan:</h3>
                                <div id="scan-history" class="vstack gap-2" style="max-height: 260px; overflow-y: auto;">
                                    <p class="text-muted text-center">Belum ada paket yang di-scan.</p>
                                </div>
                                <hr>
                                <div id="action-buttons" class="d-grid gap-2 mt-auto">
                                    <button type="button" id="surat-jalan-btn" class="btn btn-primary w-100 fw-bold" disabled>Langkah 3: Buat Surat Jalan</button>
                                    <button type="button" id="whatsapp-btn" class="btn btn-success w-100 fw-bold d-none">Langkah 4: Konfirmasi ke Admin</button>
                                </div>
                            </div>
                        </div>
                    </div>

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
        // Elemen
        const searchInput = document.getElementById('search-input');
        const searchResultsContainer = document.getElementById('search-results-container');
        const searchResultsUl = document.getElementById('search-results');
        const registrationFormContainer = document.getElementById('registration-form-container');
        const registrationForm = document.getElementById('registration-form');
        const scanResiSection = document.getElementById('scan-resi-section');
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
        // BARU: Elemen status GPS
        const gpsStatusContainer = document.getElementById('gps-status-container');

        // Audio beep
        const beepSuccess = document.getElementById('beep-success');
        const beepFail = document.getElementById('beep-fail');

        // State
        let selectedKontak = null;
        let scannedResiArray = [];
        let searchTimeout;
        let html5QrCode = null;
        let cameraModal = null;
        let generatedSuratJalan = null;

        // BARU: State untuk Geolocation
        let latitude = null;
        let longitude = null;
        let isLoadingLocation = true;
        let locationError = '';

        if(cameraModalEl) cameraModal = new bootstrap.Modal(cameraModalEl);

        // ===================== FUNGSI =====================

        // BARU: Fungsi untuk update status GPS di UI
        const updateGpsStatusUI = (message, type = 'info') => {
            let icon = 'fa-spinner fa-spin';
            if (type === 'success') icon = 'fa-map-marker-alt';
            if (type === 'danger') icon = 'fa-exclamation-triangle';
            
            gpsStatusContainer.innerHTML = `
                <div class="alert alert-${type} d-flex align-items-center" role="alert">
                    <i class="fas ${icon} me-2"></i>
                    <div>${message}</div>
                </div>
            `;
        };

        // BARU: Fungsi untuk inisialisasi Geolocation
        const initGeolocation = () => {
            updateGpsStatusUI('Sedang mengambil lokasi GPS Anda. Mohon tunggu...', 'info');
            searchInput.placeholder = 'Mengambil GPS...';
            
            if (!'geolocation' in navigator) {
                locationError = 'Browser Anda tidak mendukung Geolocation.';
                updateGpsStatusUI(locationError, 'danger');
                isLoadingLocation = false;
                return;
            }

            navigator.geolocation.getCurrentPosition(
                // 1. Sukses
                (position) => {
                    latitude = position.coords.latitude;
                    longitude = position.coords.longitude;
                    isLoadingLocation = false;
                    locationError = '';
                    
                    updateGpsStatusUI('Lokasi GPS didapat. Silakan cari nama Anda.', 'success');
                    searchInput.disabled = false; // Aktifkan input nama
                    searchInput.placeholder = 'Ketik nama atau nomor HP Anda...';
                    
                    // Sembunyikan pesan sukses setelah 5 detik
                    setTimeout(() => {
                        if (!locationError) gpsStatusContainer.innerHTML = '';
                    }, 2000);
                },
                // 2. Error
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
                    updateGpsStatusUI(locationError, 'danger'); // Tampilkan error permanen
                    searchInput.placeholder = 'GPS Gagal. Harap izinkan lokasi.';
                    isLoadingLocation = false;
                }
            );
        };

        const showAlert = (message, type='danger') => {
            alertContainer.innerHTML = `<div class="alert alert-${type}">${message}</div>`;
            setTimeout(() => { alertContainer.innerHTML = ''; }, 4000);
        };

        const playBeep = (success=true) => {
            if(success) beepSuccess.play();
            else beepFail.play();
        };

        const selectKontak = (kontak) => {
            // MODIFIKASI: Cek GPS sebelum melanjutkan
            if (isLoadingLocation || locationError) {
                showAlert('Lokasi GPS wajib diaktifkan. ' + (locationError || 'Tunggu GPS selesai loading.'), 'warning');
                return;
            }

            selectedKontak = kontak;
    
    // LOGIC SENSOR (Copy paste yang tadi)
    let hpSensor = kontak.no_hp;
    if(hpSensor.length > 6) {
        let depan = hpSensor.substring(0, 3);
        let belakang = hpSensor.substring(hpSensor.length - 3);
        hpSensor = `${depan} *** *** ${belakang}`;
    }
    // Tampilkan versi sensor di Input Box
            searchInput.value = `${kontak.nama} (${hpSensor})`;
            searchInput.disabled = true;
            searchResultsContainer.classList.add('d-none');
            registrationFormContainer.classList.add('d-none');
            scanResiSection.classList.remove('opacity-50');
            scanResiSection.style.pointerEvents = 'auto';
            resiInput.focus();
            showAlert(`Halo <strong>${kontak.nama}</strong>, SILAHKAN SCAN RESI SPX KAKAK DIBAWAH INI.`, 'success');
        };

        const handleScan = async (resiValue) => {
            if(!selectedKontak) return showAlert('Pilih atau daftar kontak dulu.', 'warning');
            if(!resiValue) return;

            // MODIFIKASI: Cek GPS sebelum mengirim
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
                    // MODIFIKASI: Tambahkan latitude dan longitude
                    body: JSON.stringify({ 
                        kontak_id: selectedKontak.id, 
                        resi: resiValue,
                        latitude: latitude,
                        longitude: longitude
                    })
                });

                // Cek jika response adalah error validasi (422) dari Laravel
                if (response.status === 422) {
                    const result = await response.json();
                    let errorMsg = result.message || 'Data tidak valid.';
                    // Cek error spesifik dari validasi lat/long
                    if (result.errors) {
                        if (result.errors.latitude) errorMsg = 'Data latitude wajib diisi.';
                        if (result.errors.longitude) errorMsg = 'Data longitude wajib diisi.';
                    }
                    playBeep(false);
                    showAlert(errorMsg);
                    return; // Hentikan fungsi di sini
                }
                
                const result = await response.json();
                if(response.ok && result.success){
                    playBeep(true);
                    // ... (sisa kode history) ...
                    if(scanHistoryContainer.querySelector('p')) scanHistoryContainer.innerHTML = '';
                    const html = `<div class="scan-history-item d-flex justify-content-between">
                                    <span>${result.data.nomor_resi}</span>
                                    <small>${result.data.waktu_scan}</small>
                                </div>`;
                    scanHistoryContainer.insertAdjacentHTML('afterbegin', html);
                    scannedResiArray.push(result.data.nomor_resi);
                    pickupCountEl.textContent = scannedResiArray.length;
                    suratJalanBtn.disabled = false;
                } else {
                    playBeep(false);
                    showAlert(result.message || 'Resi gagal atau sudah di-scan.');
                }
            } catch (err) {
                playBeep(false);
                showAlert('Koneksi error.');
            }
            resiInput.value = '';
            resiInput.focus();
        };

        // ===================== EVENT =====================

        // Pencarian kontak
        searchInput.addEventListener('keyup', () => {
            // ... (Tidak ada perubahan di sini) ...
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
        li.className = 'list-group-item list-group-item-action';
        
        // === LOGIC SENSOR NO HP ===
        // Ambil 3 digit awal dan 3 digit akhir
        let hpSensor = kontak.no_hp;
        if(hpSensor.length > 6) {
            let depan = hpSensor.substring(0, 3);
            let belakang = hpSensor.substring(hpSensor.length - 3);
            hpSensor = `${depan} *** *** ${belakang}`;
        }
        // ==========================

        // Tampilkan hpSensor di layar (bukan nomor asli)
        li.innerHTML = `<div class="fw-semibold">${kontak.nama}</div><small class="text-muted">${hpSensor}</small>`;
        
        // PENTING: Saat diklik, kita tetap kirim data 'kontak' yang ASLI (lengkap) ke sistem
        // agar proses selanjutnya tidak error.
        li.onclick = () => selectKontak(kontak);
        
        searchResultsUl.appendChild(li);
    });

                    } else {
                        searchResultsUl.innerHTML = '<li class="list-group-item">Nama tidak ditemukan. Silakan daftar di bawah.</li>';
                        registrationFormContainer.classList.remove('d-none');
                        document.getElementById('reg-nama').value = searchInput.value;
                    }
                    searchResultsContainer.classList.remove('d-none');
                } catch(err){
                    console.error(err);
                }
            }, 500);
        });

        // Form registrasi baru
        registrationForm.addEventListener('submit', async (e) => {
            // ... (Tidak ada perubahan di sini) ...
            e.preventDefault();
            const formData = {
                nama: document.getElementById('reg-nama').value,
                no_hp: document.getElementById('reg-no_hp').value,
                alamat: document.getElementById('reg-alamat').value
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
                } else showAlert(result.message || 'Gagal mendaftar.');
            } catch(err){
                showAlert('Koneksi error.');
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
                return showAlert('Belum ada paket di-scan!', 'warning');
            }

            // MODIFIKASI: Cek GPS sebelum mengirim
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
                    // MODIFIKASI: Tambahkan latitude dan longitude
                    body: JSON.stringify({
                        kontak_id: selectedKontak.id,
                        resi_list: scannedResiArray,
                        latitude: latitude,
                        longitude: longitude
                    })
                });
        
                const result = await response.json();
                if (response.ok && result.success) {
                    // ... (sisa kode tidak berubah) ...
                    generatedSuratJalan = result;
                    window.open(result.pdf_url, '_blank');
                    whatsappBtn.classList.remove('d-none');
                } else {
                    showAlert(result.message || 'Gagal membuat Surat Jalan.');
                }
            } catch (err) {
                showAlert('Koneksi error.');
            }
        });

        // Tombol WhatsApp
        whatsappBtn.addEventListener('click', () => {
            if (!generatedSuratJalan) {
                return showAlert('Data Surat Jalan tidak tersedia. Silakan ulangi.', 'warning');
            }

            const kodeSuratJalan = generatedSuratJalan.kode_surat_jalan;
            const namaPengirim = generatedSuratJalan.customer_name;
            const jumlahPaket = generatedSuratJalan.package_count;
            const createdAt = generatedSuratJalan.created_at;

            // Buat link Google Maps
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
            // ... (Tidak ada perubahan di sini) ...
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
            // ... (Tidak ada perubahan di sini) ...
            cameraModalEl.addEventListener('hidden.bs.modal', ()=>{
                if(html5QrCode && html5QrCode.isScanning) html5QrCode.stop().catch(()=>{});
            });
        }

        // Tombol galeri
        galleryScanBtn.addEventListener('click', ()=>galleryFileInput.click());
        galleryFileInput.addEventListener('change', async (e)=>{
            // ... (Tidak ada perubahan di sini) ...
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

        // BARU: Panggil fungsi Geolocation saat halaman dimuat
        initGeolocation();

    });

    // Fungsi untuk Menutup Alert Merah & Memunculkan Promo
function showPromo() {
    // 1. Sembunyikan Alert Merah
    const alertBox = document.getElementById('scan-warning');
    if(alertBox) {
        alertBox.style.display = 'none';
    }

    // 2. Munculkan Box Promo
    const promoBox = document.getElementById('promo-offer');
    if(promoBox) {
        promoBox.classList.remove('d-none');
    }
}

    </script>

</body>
</html>