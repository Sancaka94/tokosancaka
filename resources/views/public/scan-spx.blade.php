<!DOCTYPE html>

<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Daftarkan Paket SPX Express - Sancaka Express</title>

    <!-- Favicon PNG -->

    <link rel="icon" type="image/png" href="https://tokosancaka.biz.id/storage/uploads/sancaka.png">



    <!-- Favicon untuk Apple devices -->

    <link rel="apple-touch-icon" href="https://tokosancaka.biz.id/storage/uploads/sancaka.png">



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

                    <!-- Kolom Kiri: Input & Scanner -->

                    <div class="col-lg-7">

                        <div id="alert-container"></div>

                        <!-- Langkah 1: Pilih Pengirim -->

                        <div class="card border-0 shadow-sm mb-4">

                            <div class="card-body p-4">

                                <label for="search-input" class="form-label fw-semibold text-dark">Langkah 1: Cari Nama Anda</label>

                                <div class="position-relative">

                                    <i class="fas fa-user position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>

                                    <input type="text" id="search-input" placeholder="Ketik nama atau nomor HP Anda..." class="form-control ps-5" autocomplete="off">

                                    <div id="search-results-container" class="position-absolute z-3 w-100 mt-1 bg-white border rounded-3 shadow-lg d-none">

                                        <ul id="search-results" class="list-group list-group-flush"></ul>

                                    </div>

                                </div>

                            </div>

                        </div>



                        <!-- FORM REGISTRASI BARU (TERSEMBUNYI) -->

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



                        <!-- Langkah 2: Scan Resi (Disabled by default) -->

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



                    <!-- Kolom Kanan: Hasil & Surat Jalan -->

                    <div class="col-lg-5">

                        <div class="card border-0 shadow-sm">

                            <div class="card-body p-4">

                                <h2 class="h5 fw-bold text-dark mb-3">Hasil Scan Sesi Ini</h2>

                                <div class="bg-light p-3 rounded-3 mb-3 text-center">

                                    <p class="text-muted mb-1">Jumlah Paket:</p>

                                    <p id="pickup-count" class="h1 fw-bolder text-primary">0</p>

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

    <!-- Tambahkan di <body> sebelum </body> -->

    

    <!-- Audio beep -->

<audio id="beep-success" src="https://tokosancaka.com/public/sound/beep.mp3" preload="auto"></audio>

<audio id="beep-fail" src="https://tokosancaka.biz.id/public/sound/beep-gagal.mp3" preload="auto"></audio>



<script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>



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



    if(cameraModalEl) cameraModal = new bootstrap.Modal(cameraModalEl);



    // ===================== FUNGSI =====================

    const showAlert = (message, type='danger') => {

        alertContainer.innerHTML = `<div class="alert alert-${type}">${message}</div>`;

        setTimeout(() => { alertContainer.innerHTML = ''; }, 4000);

    };



    const playBeep = (success=true) => {

        if(success) beepSuccess.play();

        else beepFail.play();

    };



    const selectKontak = (kontak) => {

        selectedKontak = kontak;

        searchInput.value = `${kontak.nama} (${kontak.no_hp})`;

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



        try {

            const response = await fetch("{{ route('scan.spx.handle') }}", {

                method: 'POST',

                headers: {

                    'Content-Type': 'application/json',

                    'X-CSRF-TOKEN': csrfToken,

                    'Accept': 'application/json'

                },

                body: JSON.stringify({ kontak_id: selectedKontak.id, resi: resiValue })

            });

            const result = await response.json();

            if(response.ok && result.success){

                playBeep(true);

                // Update history

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

                if(data.length>0){

                    data.forEach(kontak => {

                        const li = document.createElement('li');

                        li.className = 'list-group-item list-group-item-action';

                        li.innerHTML = `<div class="fw-semibold">${kontak.nama}</div><small>${kontak.no_hp}</small>`;

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

                    resi_list: scannedResiArray

                })

            });

    

            const result = await response.json();

            if (response.ok && result.success) {

                // Simpan data surat jalan

                generatedSuratJalan = result;

    

                // Buka PDF di tab baru

                window.open(result.pdf_url, '_blank');

    

                // Tampilkan tombol WA

                whatsappBtn.classList.remove('d-none');

            } else {

                showAlert(result.message || 'Gagal membuat Surat Jalan.');

            }

        } catch (err) {

            showAlert('Koneksi error.');

        }

    });



    // =========================================================

 // Tombol WhatsApp (GANTI BLOK INI DENGAN KODE DI BAWAH)

    // =========================================================

     whatsappBtn.addEventListener('click', () => {

    if (!generatedSuratJalan) {

        return showAlert('Data Surat Jalan tidak tersedia. Silakan ulangi.', 'warning');

    }



    const kodeSuratJalan = generatedSuratJalan.kode_surat_jalan;

    const namaPengirim = generatedSuratJalan.customer_name;

    const jumlahPaket = generatedSuratJalan.package_count;

    const createdAt = generatedSuratJalan.created_at; // pastikan dikirim dari backend



    let message = `📦 *Konfirmasi Input Paket SPX Express*\n\n`;

    message += `Halo Admin Sancaka Express,\nMohon diproses untuk pickup paket dengan detail berikut:\n\n`;

    message += `*Nomor Surat Jalan:*\n${kodeSuratJalan}\n\n`;

    message += `*Tanggal Dibuat:*\n${createdAt}\n\n`;

    message += `*Pengirim:*\n${namaPengirim}\n\n`;

    message += `*Jumlah Paket:*\n${jumlahPaket} Paket\n\n`;

    message += `*Daftar Nomor Resi:*\n`;

    scannedResiArray.forEach((resi, index) => { message += `${index + 1}. ${resi}\n`; });

    message += `\nTerima kasih! 🙏`;



    const adminWhatsappNumber = '628819435180'; // nomor Admin

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



});

</script>





</body>

</html>

