@extends('layouts.admin')

@section('title', 'Scan Paket SPX Pelanggan')
@section('page-title', 'Scan Paket SPX Pelanggan')

@push('styles')
<style>
    #reader { border: 3px dashed #F97316; }
    .scan-history-item, .flash-message { animation: fadeIn 0.5s ease-in-out; }
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    #scanner-container.fullscreen {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: black;
        z-index: 9999;
    }
</style>
@endpush

@section('content')
{{-- 
    ✅ PERBAIKAN UTAMA: 
    Menyimpan semua URL rute yang dibutuhkan oleh JavaScript di dalam atribut data-*
    pada elemen container utama. Ini adalah cara yang benar untuk meneruskan data dari
    Blade (server-side) ke JavaScript (client-side).
--}}
<div 
    id="main-container"
    data-store-url="{{ route('admin.spx_scans.store') }}"
    data-customer-scans-url-template="{{ route('admin.spx_scans.getTodaysScans', ['customer' => ':customer']) }}"
    data-surat-jalan-url="{{ route('admin.spx_scans.createSuratJalan') }}"
    class="bg-gray-50 min-h-screen">
    <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        
        <div id="alert-container" class="fixed top-20 right-5 z-[10000] w-full max-w-sm"></div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            
            <div class="lg:col-span-7">
                <div class="bg-white p-6 shadow-md rounded-xl h-full">
                    <h2 class="text-2xl font-bold text-gray-800 mb-6">Scan Paket SPX Pelanggan</h2>
                    
                    <div class="mb-6">
                        <label for="customer_id" class="block text-sm font-medium text-gray-700">Langkah 1: Pilih Pelanggan</label>
                        <select id="customer_id" name="user_id" class="mt-1 block w-full bg-gray-100 border-gray-300 rounded-md shadow-sm p-3">
                            <option value="">-- Pilih Pelanggan --</option>
                            @foreach($customers as $customer)
                                {{-- ✅ PERBAIKAN: Menggunakan kolom id_pengguna yang benar untuk value --}}
                                <option value="{{ $customer->id_pengguna }}" data-phone="{{ $customer->no_wa }}">{{ $customer->nama_lengkap }}</option>
                            @endforeach
                        </select>
                    </div>

                    <form id="scan-form" class="mb-4">
                        <div>
                            <label for="resi-spx" class="block text-sm font-medium text-gray-700">Langkah 2: Input Resi</label>
                            <div class="mt-1 relative rounded-md shadow-sm">
                                <input type="text" id="resi-spx" name="resi_number" class="block w-full pl-4 pr-3 py-3 border-gray-300 rounded-md" placeholder="Pilih pelanggan untuk memulai..." required autocomplete="off" disabled>
                            </div>
                        </div>
                    </form>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Atau Gunakan Metode Lain:</label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <button type="button" id="start-camera-btn" class="w-full flex justify-center items-center py-3 px-4 border rounded-md shadow-sm text-sm font-medium text-white bg-orange-600 hover:bg-orange-700 disabled:opacity-50" disabled>
                                <i class="fas fa-camera mr-2"></i>Scan Kamera
                            </button>
                            <button type="button" id="gallery-scan-btn" class="w-full flex justify-center items-center py-3 px-4 border rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50" disabled>
                                <i class="fas fa-images mr-2"></i>Pindai Galeri
                            </button>
                            <input type="file" id="gallery-file-input" class="hidden" accept="image/*">
                        </div>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-5">
                <div class="bg-white p-6 shadow-md rounded-xl h-full flex flex-col">
                    <h3 class="text-xl font-bold text-gray-800 mb-4">Hasil Scan Hari Ini</h3>
                    <div class="bg-gray-100 p-4 rounded-lg mb-4 text-center">
                        <p id="todays-count" class="text-4xl font-extrabold text-indigo-600">0</p>
                    </div>
                    <div id="recent-scans-container" class="space-y-2 flex-grow overflow-y-auto" style="max-height: 250px;">
                       <p class="text-center text-gray-500">Pilih pelanggan untuk melihat riwayat.</p>
                    </div>
                    <div class="mt-auto pt-4 border-t space-y-3">
                        <button type="button" id="surat-jalan-btn" class="w-full flex justify-center py-3 px-4 border rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50" disabled>
                            <i class="fas fa-print mr-2"></i>Cetak Surat Jalan
                        </button>
                        <button type="button" id="whatsapp-btn" class="w-full flex justify-center py-3 px-4 border rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 hidden">
                            <i class="fab fa-whatsapp mr-2"></i>Konfirmasi ke Pelanggan
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal untuk Scanner Kamera -->
<div id="scanner-container" class="fixed inset-0 bg-black z-[9999] hidden">
    <div id="reader" class="w-full h-full"></div>
    <div id="scanner-ui" class="absolute inset-0 flex flex-col justify-between p-4">
        <div class="flex justify-between items-start">
            <button id="close-scanner-btn" class="bg-white/20 backdrop-blur-sm text-white px-4 py-2 rounded-lg hover:bg-white/30">
                <i class="fas fa-times mr-2"></i>Tutup
            </button>
            <div id="flash-message-container" class="w-1/2"></div>
            <label for="gallery-file-input-modal" class="bg-white/20 backdrop-blur-sm text-white px-4 py-2 rounded-lg cursor-pointer hover:bg-white/30">
                <i class="fas fa-images mr-2"></i>Galeri
            </label>
            <input type="file" id="gallery-file-input-modal" accept="image/*" class="hidden">
        </div>
        <div class="text-center">
            <button id="done-scan-btn" class="bg-indigo-600 text-white px-8 py-3 rounded-lg hover:bg-indigo-700 font-medium">
                Selesai Scan
            </button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
{{-- Pastikan Anda memiliki meta tag CSRF di layout utama Anda, contoh: <meta name="csrf-token" content="{{ csrf_token() }}"> --}}
<script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // --- Referensi Elemen ---
    const mainContainer = document.getElementById('main-container');
    const customerSelect = document.getElementById('customer_id');
    const resiInput = document.getElementById('resi-spx');
    const alertContainer = document.getElementById('alert-container');
    const todaysCountEl = document.getElementById('todays-count');
    const recentScansContainer = document.getElementById('recent-scans-container');
    const startCameraBtn = document.getElementById('start-camera-btn');
    const galleryScanBtn = document.getElementById('gallery-scan-btn');
    const galleryFileInput = document.getElementById('gallery-file-input');
    const scannerContainer = document.getElementById('scanner-container');
    const flashMessageContainer = document.getElementById('flash-message-container');
    const suratJalanBtn = document.getElementById('surat-jalan-btn');
    const whatsappBtn = document.getElementById('whatsapp-btn');
    const closeScannerBtn = document.getElementById('close-scanner-btn');
    const doneScanBtn = document.getElementById('done-scan-btn');
    const galleryFileInputModal = document.getElementById('gallery-file-input-modal');
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    // ✅ PERBAIKAN: Mengambil URL dari atribut data-*
    const STORE_URL = mainContainer.dataset.storeUrl;
    const CUSTOMER_SCANS_URL_TEMPLATE = mainContainer.dataset.customerScansUrlTemplate;
    const SURAT_JALAN_URL = mainContainer.dataset.suratJalanUrl;

    // --- Variabel State ---
    let html5QrCode = null;
    let scannedResiArray = [];
    let suratJalanData = null; 
    let audioUnlocked = false;

    // --- Audio Notifikasi ---
    const successSound = new Audio("{{ asset('sound/beep.mp3') }}");
    const failSound = new Audio("{{ asset('sound/beep-gagal.mp3') }}");
    successSound.load();
    failSound.load();

    const unlockAudio = () => {
        if (audioUnlocked) return;
        successSound.play().catch(()=>{}); successSound.pause(); successSound.currentTime = 0;
        failSound.play().catch(()=>{}); failSound.pause(); failSound.currentTime = 0;
        audioUnlocked = true;
        document.body.removeEventListener('click', unlockAudio);
        document.body.removeEventListener('keydown', unlockAudio);
    };
    document.body.addEventListener('click', unlockAudio);
    document.body.addEventListener('keydown', unlockAudio);

    // --- Fungsi-fungsi ---
    const showAlert = (message, type = 'danger', onCamera = false) => {
        const container = onCamera ? flashMessageContainer : alertContainer;
        const bgColor = type === 'success' ? 'bg-green-100 border-green-400 text-green-700' : 'bg-red-100 border-red-400 text-red-700';
        const iconClass = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle';
        const alertHTML = `<div class="flash-message border px-4 py-3 rounded relative ${bgColor}" role="alert"><i class="fas ${iconClass} mr-2"></i><span class="block sm:inline">${message}</span></div>`;
        container.innerHTML = alertHTML;
        setTimeout(() => { container.innerHTML = '' }, 4000);
    };

    const handleScan = async (resiValue) => {
        const customerId = customerSelect.value;
        if (!customerId) { showAlert('Pilih pelanggan terlebih dahulu!', 'danger'); return; }
        if (!resiValue) return;

        resiInput.disabled = true;
        try {
            // ✅ PERBAIKAN: Menggunakan variabel URL yang sudah didefinisikan
            const response = await fetch(STORE_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                body: JSON.stringify({ resi_number: resiValue, user_id: customerId })
            });
            const result = await response.json();
            if (response.ok && result.success) {
                successSound.play().catch(e => {});
                showAlert(result.message, 'success', scannerContainer.classList.contains('hidden') === false);
                todaysCountEl.innerText = result.todays_count;
                recentScansContainer.innerHTML = result.recent_scans_html;
                scannedResiArray = Array.from(document.querySelectorAll('.scanned-resi-value')).map(el => el.textContent);
                suratJalanBtn.disabled = false;
            } else {
                failSound.play().catch(e => {});
                showAlert(result.message || 'Terjadi kesalahan.', 'danger', scannerContainer.classList.contains('hidden') === false);
            }
        } catch (error) {
            failSound.play().catch(e => {});
            showAlert('Tidak dapat terhubung ke server.', 'danger', scannerContainer.classList.contains('hidden') === false);
        } finally {
            resiInput.value = '';
            resiInput.disabled = false;
            resiInput.focus();
        }
    };
    
    const onScanSuccess = (decodedText, decodedResult) => {
        handleScan(decodedText);
    };

    const openScanner = () => {
        scannerContainer.classList.remove('hidden');
        if (!html5QrCode) { html5QrCode = new Html5Qrcode("reader"); }
        html5QrCode.start({ facingMode: "environment" }, { fps: 10, qrbox: { width: 250, height: 150 } }, onScanSuccess, ()=>{})
        .catch(() => {
            showAlert('Gagal memulai kamera. Pastikan Anda memberikan izin akses.', 'danger');
            closeScanner();
        });
    };
    
    const closeScanner = () => {
        if (html5QrCode && html5QrCode.isScanning) {
            html5QrCode.stop().catch(err => console.error("Gagal menghentikan kamera.", err));
        }
        scannerContainer.classList.add('hidden');
    };

    const fetchCustomerScans = async () => {
        const customerId = customerSelect.value;
        if (!customerId) {
            resiInput.disabled = true;
            startCameraBtn.disabled = true;
            galleryScanBtn.disabled = true;
            suratJalanBtn.disabled = true;
            todaysCountEl.innerText = '0';
            recentScansContainer.innerHTML = '<p class="text-center text-gray-500">Pilih pelanggan untuk melihat riwayat.</p>';
            return;
        }

        resiInput.disabled = false;
        startCameraBtn.disabled = false;
        galleryScanBtn.disabled = false;
        resiInput.placeholder = "Ketik resi lalu tekan Enter...";

        try {
            // ✅ PERBAIKAN: Menggunakan placeholder :customer yang benar untuk diganti
            let url = CUSTOMER_SCANS_URL_TEMPLATE.replace(':customer', customerId);
            const response = await fetch(url);
            const result = await response.json();
            todaysCountEl.innerText = result.todays_count;
            recentScansContainer.innerHTML = result.recent_scans_html || '<p class="text-center text-gray-500">Belum ada scan hari ini.</p>';
            scannedResiArray = Array.from(document.querySelectorAll('.scanned-resi-value')).map(el => el.textContent);
            suratJalanBtn.disabled = scannedResiArray.length === 0;
        } catch (error) {
            console.error("Gagal mengambil data scan:", error);
        }
    };

    // --- Event Listener ---
    customerSelect.addEventListener('change', fetchCustomerScans);

    document.getElementById('scan-form').addEventListener('submit', (e) => {
        e.preventDefault();
        handleScan(resiInput.value);
    });
    
    startCameraBtn.addEventListener('click', openScanner);
    closeScannerBtn.addEventListener('click', closeScanner);
    doneScanBtn.addEventListener('click', closeScanner);

    const handleGalleryScan = (file) => {
        if (!file) return;
        if (!html5QrCode) { html5QrCode = new Html5Qrcode("reader"); }
        showAlert('Memproses gambar...', 'info', true);
        html5QrCode.scanFile(file, true)
            .then(decodedText => handleScan(decodedText))
            .catch(err => showAlert('Gagal memindai barcode dari gambar.', 'danger', true))
            .finally(() => {
                galleryFileInput.value = '';
                galleryFileInputModal.value = '';
            });
    };

    galleryScanBtn.addEventListener('click', () => galleryFileInput.click());
    galleryFileInput.addEventListener('change', (e) => handleGalleryScan(e.target.files[0]));
    galleryFileInputModal.addEventListener('change', (e) => handleGalleryScan(e.target.files[0]));

    suratJalanBtn.addEventListener('click', async () => {
        const customerId = customerSelect.value;
        if (scannedResiArray.length === 0 || !customerId) {
            showAlert('Pilih pelanggan dan pastikan ada resi yang di-scan.', 'warning');
            return;
        }
        suratJalanBtn.disabled = true;
        suratJalanBtn.innerHTML = 'Mencetak...';
        
        try {
            // ✅ PERBAIKAN: Menggunakan variabel URL yang sudah didefinisikan
            const response = await fetch(SURAT_JALAN_URL, { 
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                body: JSON.stringify({ resi_list: scannedResiArray, user_id: customerId })
            });
            const result = await response.json();

            if (response.ok && result.success) {
                showAlert('Surat Jalan berhasil dibuat!', 'success');
                window.open(result.pdf_url, '_blank');
                suratJalanData = result;
                suratJalanBtn.classList.add('hidden');
                whatsappBtn.classList.remove('hidden');
            } else {
                showAlert(result.message || 'Gagal membuat surat jalan.', 'danger');
                suratJalanBtn.disabled = false;
                suratJalanBtn.innerHTML = '<i class="fas fa-print mr-2"></i>Cetak Surat Jalan';
            }
        } catch (error) {
            showAlert('Tidak dapat terhubung ke server.', 'danger');
            suratJalanBtn.disabled = false;
            suratJalanBtn.innerHTML = '<i class="fas fa-print mr-2"></i>Cetak Surat Jalan';
        }
    });

    whatsappBtn.addEventListener('click', () => {
        if (!suratJalanData) {
            showAlert('Data surat jalan tidak ditemukan.', 'danger');
            return;
        }
        const message = `*Konfirmasi Pickup Sancaka Express*\n\n` +
                        `Halo ${suratJalanData.customer_name},\n` +
                        `Surat Jalan Anda dengan kode *${suratJalanData.surat_jalan_code}* telah kami terima.\n\n` +
                        `Total *${suratJalanData.package_count} Koli* akan segera kami proses untuk pickup.\n\n` +
                        `Terima kasih.`;
        const whatsappUrl = `https://wa.me/${suratJalanData.customer_phone}?text=${encodeURIComponent(message)}`;
        window.open(whatsappUrl, '_blank');
    });
});
</script>
@endpush
