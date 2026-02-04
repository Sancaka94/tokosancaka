@extends('layouts.customer') {{-- Pastikan layout ini sudah memuat Tailwind CSS dan tag meta csrf-token --}}



@section('title', 'Scan Paket SPX')



@push('styles')

{{-- Custom style untuk animasi dan area scanner --}}

<style>

    #reader { border: 3px dashed #F97316; } /* Tailwind orange-500 */

    .scan-history-item, .flash-message, .history-row { animation: fadeIn 0.5s ease-in-out; }

    @keyframes fadeIn {

        from { opacity: 0; transform: translateY(-10px); }

        to { opacity: 1; transform: translateY(0); }

    }

    .filter-btn.active {

        background-color: #4F46E5; /* indigo-600 */

        color: white;

    }

</style>

@endpush



@section('content')

<div class="bg-gray-50 min-h-screen">

    <div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">



        {{-- Container untuk notifikasi/alert --}}

        <div id="alert-container" class="fixed top-20 right-5 z-50 w-full max-w-sm"></div>



        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">



            {{-- Kolom Kiri: Input & Scanner --}}

            <div class="lg:col-span-7">
                <div class="bg-white p-6 shadow-md rounded-xl h-full relative">
                    <h2 class="text-2xl font-bold text-gray-800 mb-6">Daftarkan Paket SPX Anda</h2>

                    <div id="init-scan-wrapper" class="text-center py-10">
                        <div class="mb-6 inline-block bg-indigo-50 px-4 py-2 rounded-lg border border-indigo-100">
                            <p class="text-xs text-gray-500">Sisa Saldo: <span class="font-bold text-indigo-700">Rp {{ number_format($customer->saldo, 0, ',', '.') }}</span></p>
                        </div>

                        <div class="block">
                            <button type="button" id="btn-scan-sekarang" class="inline-flex items-center justify-center px-8 py-4 border border-transparent text-lg font-medium rounded-md text-white bg-orange-600 hover:bg-orange-700 focus:outline-none shadow-lg transition-transform transform hover:scale-105">
                                <i class="fas fa-barcode mr-3"></i> MULAI SCAN BARU
                            </button>
                            <p class="mt-3 text-sm text-gray-400">Klik tombol di atas untuk membuka formulir scan</p>
                        </div>
                    </div>

                    <div id="scan-interface" class="hidden transition-opacity duration-300">

                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700">Pelanggan Terdaftar</label>
                            <input type="text" class="mt-1 block w-full bg-gray-100 border-gray-300 rounded-md shadow-sm text-gray-600 cursor-not-allowed" value="{{ $customer->nama_lengkap }} ({{ $customer->no_wa }})" readonly>
                        </div>

                        <form id="scan-form" class="mb-4">
                            <div>
                                <label for="resi-spx" class="block text-sm font-medium text-gray-700">Langkah 1: Input Resi</label>
                                <div class="mt-1 relative rounded-md shadow-sm">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="fas fa-barcode text-gray-400"></i>
                                    </div>
                                    <input type="text" id="resi-spx" name="resi_number" class="block w-full pl-10 pr-3 py-3 border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" placeholder="Ketik resi lalu tekan Enter..." required autocomplete="off">
                                </div>
                            </div>
                        </form>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Atau Gunakan Metode Lain:</label>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <button type="button" id="start-camera-btn" class="w-full flex justify-center items-center py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-orange-600 hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500">
                                    <i class="fas fa-camera mr-2"></i>Scan dengan Kamera
                                </button>
                                <button type="button" id="gallery-scan-btn" class="w-full flex justify-center items-center py-3 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    <i class="fas fa-images mr-2"></i>Pindai dari Galeri
                                </button>
                                <input type="file" id="gallery-file-input" class="hidden" accept="image/*">
                            </div>
                        </div>

                        <div class="mt-6 text-center border-t pt-4">
                            <button type="button" id="btn-cancel-scan" class="text-sm text-red-500 hover:text-red-700 font-medium">
                                <i class="fas fa-times mr-1"></i> Selesai / Tutup Sesi
                            </button>
                        </div>
                    </div>

                </div>
            </div>



            {{-- Kolom Kanan: Hasil & Tombol Aksi --}}

            <div class="lg:col-span-5">

                <div class="bg-white p-6 shadow-md rounded-xl h-full flex flex-col">

                    <h3 class="text-xl font-bold text-gray-800 mb-4">Hasil Scan Hari Ini</h3>



                    <div class="bg-gray-100 p-4 rounded-lg mb-4 text-center">

                        <p class="text-sm text-gray-600 mb-1">Jumlah Paket Siap Pickup:</p>

                        <p id="todays-count" class="text-4xl font-extrabold text-indigo-600">{{ $todays_scans->count() }}</p>

                    </div>



                    <hr class="my-4">



                    <h4 class="text-md font-semibold text-gray-700 mb-3">Riwayat Scan Terakhir:</h4>

                    <div id="recent-scans-container" class="space-y-2 flex-grow overflow-y-auto" style="max-height: 250px;">

                       @include('customer.partials.recent-scans', ['scans' => $todays_scans])

                    </div>



                    <hr class="my-4">



                    <div id="action-buttons" class="space-y-3 mt-auto">

                        <button type="button" id="surat-jalan-btn" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed" {{ $todays_scans->isEmpty() ? 'disabled' : '' }}>

                            <i class="fas fa-print mr-2"></i>Langkah 2: Cetak Surat Jalan

                        </button>

                        <button type="button" id="whatsapp-btn" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 hidden">

                            <i class="fab fa-whatsapp mr-2"></i>Langkah 3: Konfirmasi ke Admin

                        </button>

                    </div>

                </div>

            </div>

        </div>



        <!-- NEW: Scan History Table Section -->

        <div class="mt-8 bg-white p-6 shadow-md rounded-xl">

            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center">

                <h3 class="text-2xl font-bold text-gray-800 mb-4 sm:mb-0">Riwayat Scan Lengkap</h3>

                <div id="history-filter-buttons" class="flex flex-wrap gap-2">

                    <button data-period="today" class="filter-btn bg-indigo-600 text-white px-4 py-2 rounded-md text-sm font-medium">Hari ini</button>

                    <button data-period="7days" class="filter-btn bg-gray-200 text-gray-700 px-4 py-2 rounded-md text-sm font-medium">7 hari</button>

                    <button data-period="14days" class="filter-btn bg-gray-200 text-gray-700 px-4 py-2 rounded-md text-sm font-medium">14 hari</button>

                    <button data-period="30days" class="filter-btn bg-gray-200 text-gray-700 px-4 py-2 rounded-md text-sm font-medium">30 hari</button>

                    <button data-period="lastmonth" class="filter-btn bg-gray-200 text-gray-700 px-4 py-2 rounded-md text-sm font-medium">Bulan Lalu</button>

                </div>

            </div>



            <div class="mt-4 overflow-x-auto">

                <table class="min-w-full divide-y divide-gray-200">

                    <thead class="bg-gray-50">

                        <tr>

                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No</th>

                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Waktu</th>

                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Resi</th>

                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>

                        </tr>

                    </thead>

                    <tbody id="history-table-body" class="bg-white divide-y divide-gray-200">

                        <!-- Data rows will be inserted here by JavaScript -->

                    </tbody>

                </table>

            </div>

        </div>



    </div>

</div>



<!-- Modal untuk Scanner Kamera -->

<div id="camera-modal" class="fixed inset-0 z-40 flex items-center justify-center bg-black bg-opacity-50 hidden">

    <div class="bg-white rounded-lg shadow-xl overflow-hidden transform transition-all sm:max-w-lg sm:w-full">

        <div class="px-4 pt-5 pb-4 sm:p-6 sm:pb-4 flex justify-between items-center border-b border-gray-200">

            <h5 class="text-lg leading-6 font-medium text-gray-900">Arahkan Kamera ke Barcode</h5>

            <button type="button" id="close-camera-modal-btn" class="text-gray-400 hover:text-gray-500">

                <span class="sr-only">Close</span>

                <i class="fas fa-times"></i>

            </button>

        </div>

        <div class="p-4 relative">

            <div id="flash-message-container" class="absolute top-5 left-1/2 -translate-x-1/2 w-3/4 z-10"></div>

            <div id="reader" class="w-full rounded-lg"></div>

        </div>

        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse border-t border-gray-200">

            <button type="button" id="done-scan-btn" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 sm:ml-3 sm:w-auto sm:text-sm">

                Selesai Scan

            </button>

        </div>

    </div>

</div>

<div id="print-check-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-60 hidden" style="z-index: 9999;">
    <div class="bg-white rounded-xl shadow-2xl p-6 w-full max-w-sm text-center transform transition-all">
        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-indigo-100 mb-4">
            <i class="fas fa-print text-indigo-600 text-lg"></i>
        </div>
        <h3 class="text-lg font-bold text-gray-900 mb-2">Konfirmasi Cetak Resi</h3>
        <p class="text-sm text-gray-500 mb-6">Apakah Kakak sudah mencetak resi fisik?</p>

        <div class="space-y-3">
            <button type="button" id="btn-sudah-print" class="w-full py-3 bg-indigo-600 text-white rounded-lg font-bold hover:bg-indigo-700 shadow-md">
                SUDAH <span class="font-normal text-xs ml-1 opacity-80">(Buka Scanner)</span>
            </button>
            <button type="button" id="btn-belum-print" class="w-full py-3 bg-white text-red-600 border border-red-200 rounded-lg font-bold hover:bg-red-50">
                BELUM <span class="font-normal text-xs ml-1 text-red-400">(Cek Saldo -Rp1.000)</span>
            </button>
        </div>
        <button type="button" id="close-check-modal" class="mt-4 text-xs text-gray-400 hover:text-gray-600 underline">Batal</button>
    </div>
</div>

@endsection



@push('scripts')

<script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>

<script>

document.addEventListener('DOMContentLoaded', function () {

    // --- Referensi Elemen ---

    const resiInput = document.getElementById('resi-spx');

    const alertContainer = document.getElementById('alert-container');

    const todaysCountEl = document.getElementById('todays-count');

    const recentScansContainer = document.getElementById('recent-scans-container');

    const startCameraBtn = document.getElementById('start-camera-btn');

    const galleryScanBtn = document.getElementById('gallery-scan-btn');

    const galleryFileInput = document.getElementById('gallery-file-input');

    const cameraModalEl = document.getElementById('camera-modal');

    const flashMessageContainer = document.getElementById('flash-message-container');

    const suratJalanBtn = document.getElementById('surat-jalan-btn');

    const whatsappBtn = document.getElementById('whatsapp-btn');

    const closeCameraModalBtn = document.getElementById('close-camera-modal-btn');

    const doneScanBtn = document.getElementById('done-scan-btn');

    const csrfToken = document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').content : null;

    const historyFilterButtons = document.getElementById('history-filter-buttons');

    const historyTableBody = document.getElementById('history-table-body');

    const customerInfoInput = document.getElementById('customer-info-input');



    // --- Variabel State ---

    let html5QrCode = null;

    let scannedResiArray = Array.from(document.querySelectorAll('.scanned-resi-value')).map(el => el.textContent);

    let suratJalanData = null;



    // ==============================

    //  AUDIO

    // ==============================

    // PERBAIKAN: Menggunakan asset() dengan path yang benar

    const successSound = new Audio("{{ asset('sound/beep.mp3') }}");

    const failSound    = new Audio("{{ asset('sound/beep-gagal.mp3') }}");

    let audioUnlocked = false;

    const unlockAudio = () => {

        if (audioUnlocked) return;

        try {

            successSound.play().then(() => successSound.pause()).catch(() => {});

            failSound.play().then(() => failSound.pause()).catch(() => {});

        } catch(e) {}

        audioUnlocked = true;

        document.removeEventListener('click', unlockAudio);

        document.removeEventListener('keydown', unlockAudio);

    };

    document.addEventListener('click', unlockAudio);

    document.addEventListener('keydown', unlockAudio);

    const playSound = (audio) => {

        try {

            audio.currentTime = 0;

            audio.play().catch(() => {});

        } catch(e){}

    };

    const playSuccess = () => playSound(successSound);

    const playFail = () => playSound(failSound);



    // --- UI Helper ---

    const showAlert = (message, type = 'danger', onCamera = false) => {

        const container = onCamera ? flashMessageContainer : alertContainer;

        const bgColor = type === 'success' ? 'bg-green-100 border-green-400 text-green-700'

                      : 'bg-red-100 border-red-400 text-red-700';

        const iconClass = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle';

        const alertHTML = `<div class="flash-message border px-4 py-3 rounded relative ${bgColor}" role="alert">

            <i class="fas ${iconClass} mr-2"></i>

            <span class="block sm:inline">${message}</span>

        </div>`;

        container.innerHTML = alertHTML;

        setTimeout(() => { container.innerHTML = '' }, 5000);

    };



    // --- Proses Scan ---

    const handleScan = async (resiValue) => {

        if (!resiValue || !csrfToken) return;

        resiInput.disabled = true;

        resiInput.placeholder = "Menyimpan...";

        try {

            const response = await fetch("{{ route('customer.scan.spx.store') }}", {

                method: 'POST',

                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },

                body: JSON.stringify({ resi_number: resiValue })

            });

            const result = await response.json();

            if (!response.ok) throw new Error(result.message || 'Error dari server.');



            if (result.success) {

                playSuccess();

                showAlert(result.message, 'success', !cameraModalEl.classList.contains('hidden'));

                if (todaysCountEl) todaysCountEl.innerText = result.todays_count;

                if (recentScansContainer) recentScansContainer.innerHTML = result.recent_scans_html;

                if (!scannedResiArray.includes(result.package.resi_number)) {

                    scannedResiArray.push(result.package.resi_number);

                }

                if (suratJalanBtn) suratJalanBtn.disabled = false;

                if (document.querySelector('.filter-btn[data-period="today"]').classList.contains('active')) {

                    fetchAndRenderHistory('today');

                }

            } else {

                playFail();

                showAlert(result.message || 'Terjadi kesalahan.', 'danger', !cameraModalEl.classList.contains('hidden'));

            }

        } catch (error) {

            playFail();

            showAlert(`Error: ${error.message}`, 'danger', !cameraModalEl.classList.contains('hidden'));

        } finally {

            resiInput.value = '';

            resiInput.disabled = false;

            resiInput.placeholder = "Ketik resi lalu tekan Enter...";

            resiInput.focus();

        }

    };



    const onScanSuccess = (decodedText) => handleScan(decodedText);

    const openCameraModal = () => {

        cameraModalEl.classList.remove('hidden');

        if (!html5QrCode) { html5QrCode = new Html5Qrcode("reader"); }

        html5QrCode.start({ facingMode: "environment" }, { fps: 10, qrbox: { width: 250, height: 150 } }, onScanSuccess, () => {})

        .catch(() => {

            showAlert('Gagal memulai kamera. Pastikan Anda memberikan izin akses.', 'danger');

            closeCameraModal();

        });

    };

    const closeCameraModal = () => {

        if (html5QrCode && html5QrCode.isScanning) {

            html5QrCode.stop().catch(err => console.error("Gagal menghentikan kamera.", err));

        }

        cameraModalEl.classList.add('hidden');

    };



    // --- History Table ---

    const renderHistoryTable = (scans) => {

        historyTableBody.innerHTML = '';

        if (!scans || scans.length === 0) {

            historyTableBody.innerHTML = `<tr><td colspan="4" class="text-center py-10 text-gray-500">Tidak ada data untuk periode ini.</td></tr>`;

            return;

        }

        scans.forEach((scan, index) => {

            const statusClass = 'bg-green-100 text-green-800';

            const d = new Date(scan.created_at);

            const date = `${String(d.getDate()).padStart(2, '0')}-${String(d.getMonth() + 1).padStart(2, '0')}-${d.getFullYear()}`;

            const time = `${String(d.getHours()).padStart(2, '0')}:${String(d.getMinutes()).padStart(2, '0')}`;

            historyTableBody.insertAdjacentHTML('beforeend', `

                <tr class="history-row">

                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${index + 1}</td>

                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${date} ${time}</td>

                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 font-mono">${scan.resi_number}</td>

                    <td class="px-6 py-4 whitespace-nowrap text-sm">

                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${statusClass}">${scan.status || 'Berhasil'}</span>

                    </td>

                </tr>`);

        });

    };



    const fetchAndRenderHistory = async (period) => {

        historyTableBody.innerHTML = `<tr><td colspan="4" class="text-center py-10 text-gray-500"><i class="fas fa-spinner fa-spin mr-2"></i> Memuat data...</td></tr>`;

        document.querySelectorAll('.filter-btn').forEach(btn => {

            btn.classList.remove('bg-indigo-600', 'text-white');

            btn.classList.add('bg-gray-200', 'text-gray-700');

        });

        const activeButton = document.querySelector(`.filter-btn[data-period="${period}"]`);

        if (activeButton) {

            activeButton.classList.add('bg-indigo-600', 'text-white');

            activeButton.classList.remove('bg-gray-200', 'text-gray-700');

        }

        try {

            const response = await fetch(`{{ route('customer.scan.history') }}?period=${period}`, {

                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken }

            });

            if (!response.ok) throw new Error('Gagal mengambil data riwayat.');

            const data = await response.json();

            renderHistoryTable(data.scans);

        } catch (error) {

            console.error('Error fetching history:', error);

            historyTableBody.innerHTML = `<tr><td colspan="4" class="text-center py-10 text-red-500"><i class="fas fa-exclamation-triangle mr-2"></i> Gagal memuat data.</td></tr>`;

        }

    };



    // --- Event Listeners ---

    if (resiInput) {

        resiInput.focus();

        document.getElementById('scan-form').addEventListener('submit', (e) => {

            e.preventDefault();

            handleScan(resiInput.value);

        });

    }



    if (startCameraBtn) startCameraBtn.addEventListener('click', openCameraModal);

    if (closeCameraModalBtn) closeCameraModalBtn.addEventListener('click', closeCameraModal);

    if (doneScanBtn) doneScanBtn.addEventListener('click', closeCameraModal);



    if (galleryScanBtn) galleryScanBtn.addEventListener('click', () => galleryFileInput.click());

    if (galleryFileInput) galleryFileInput.addEventListener('change', (e) => {

        if (e.target.files.length === 0) return;

        const imageFile = e.target.files[0];

        if (!html5QrCode) { html5QrCode = new Html5Qrcode("reader"); }

        showAlert('Memproses gambar...', 'info');

        html5QrCode.scanFile(imageFile, true)

            .then(decodedText => handleScan(decodedText))

            .catch(() => showAlert('Gagal memindai barcode dari gambar.', 'danger'))

            .finally(() => { galleryFileInput.value = ''; });

    });



    // --- LOGIKA TOMBOL CETAK SURAT JALAN ---
    if (suratJalanBtn) {
        suratJalanBtn.addEventListener('click', async () => {
            // Cek jika array kosong
            if (scannedResiArray.length === 0) {
                showAlert('Belum ada resi yang di-scan.', 'warning');
                return;
            }

            // Loading state
            suratJalanBtn.disabled = true;
            suratJalanBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Mencetak...';

            try {
                // Kirim Data ke Backend
                const response = await fetch("{{ route('customer.suratjalan.create') }}", {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                    body: JSON.stringify({ resi_list: scannedResiArray })
                });
                const result = await response.json();

                if (response.ok && result.success) {
                    // 1. Notifikasi Sukses
                    showAlert('Surat Jalan berhasil dibuat!', 'success');
                    playSound(successSound);

                    // 2. Buka PDF di tab baru
                    window.open(result.pdf_url, '_blank');

                    // 3. Simpan data untuk WA (sebelum di-clear)
                    suratJalanData = result;

                    // =======================================================
                    // BAGIAN INI YANG MENGHAPUS RIWAYAT DI MONITOR
                    // =======================================================

                    // A. Kosongkan Tampilan List HTML (Monitor)
                    if(recentScansContainer) {
                        recentScansContainer.innerHTML = '<div class="p-4 text-center text-gray-400 text-sm">Riwayat scan telah dicetak & dibersihkan.</div>';
                    }

                    // B. Kosongkan Array Memory (Agar scan berikutnya mulai dari 0 lagi)
                    // Kita simpan dulu backupnya untuk WA jika perlu, lalu kosongkan array utama
                    const lastBatchResis = [...scannedResiArray];
                    scannedResiArray = [];

                    // C. Reset Tampilan Tombol
                    suratJalanBtn.classList.add('hidden'); // Sembunyikan tombol cetak
                    suratJalanBtn.innerHTML = '<i class="fas fa-print mr-2"></i>Langkah 2: Cetak Surat Jalan'; // Reset teks

                    // D. Tampilkan Tombol WA
                    whatsappBtn.classList.remove('hidden');

                } else {
                    throw new Error(result.message || 'Gagal membuat surat jalan.');
                }
            } catch (error) {
                showAlert(error.message, 'danger');
                suratJalanBtn.disabled = false;
                suratJalanBtn.innerHTML = '<i class="fas fa-print mr-2"></i>Langkah 2: Cetak Surat Jalan';
                playSound(failSound);
            }
        });
    }



    if (whatsappBtn) whatsappBtn.addEventListener('click', () => {

        if (!suratJalanData || !suratJalanData.surat_jalan_code || !suratJalanData.customer_name) {

            showAlert('Data surat jalan tidak lengkap. Silakan cetak ulang.', 'danger');

            playFail();

            return;

        }



        const customerName = suratJalanData.customer_name;

        const packageCount = suratJalanData.package_count;

        const suratJalanCode = suratJalanData.surat_jalan_code;



        let resiListText = '*Daftar Resi:*\n';

        scannedResiArray.forEach((resi, index) => { resiListText += `${index + 1}. ${resi}\n`; });



        const adminNumber = '628819435180';

        const message = `*Konfirmasi Pickup Sancaka Express*\n\n` +

                        `Halo Admin, mohon segera proses pickup untuk:\n\n` +

                        `*Nama:* ${customerName}\n` +

                        `*Total Paket:* ${packageCount} Koli\n` +

                        `*Kode Surat Jalan:* ${suratJalanCode}\n\n` +

                        `${resiListText}\n` +

                        `Terima kasih.`;



        const whatsappUrl = `https://api.whatsapp.com/send?phone=${adminNumber}&text=${encodeURIComponent(message)}`;

        window.open(whatsappUrl, '_blank');



        showAlert('Konfirmasi sedang dibuka. Halaman akan direset.', 'info');

        setTimeout(() => { window.location.reload(); }, 3000);

    });



    if (historyFilterButtons) {

        historyFilterButtons.addEventListener('click', (e) => {

            if (e.target.classList.contains('filter-btn')) {

                const period = e.target.dataset.period;

                fetchAndRenderHistory(period);

            }

        });

    }



    // Initial fetch

    fetchAndRenderHistory('today');

});

// --- LOGIKA BARU: CEK SALDO ---
    const userSaldo = {{ (float) ($customer->saldo ?? 0) }};
    const scanCost = 1000;

    // Referensi Elemen Baru
    const wrapperInit = document.getElementById('init-scan-wrapper');
    const wrapperInterface = document.getElementById('scan-interface');
    const modalCheck = document.getElementById('print-check-modal');

    // 1. Klik Scan Sekarang -> Muncul Modal
    document.getElementById('btn-scan-sekarang').addEventListener('click', () => {
        modalCheck.classList.remove('hidden');
    });

    // 2. Klik Batal Modal
    document.getElementById('close-check-modal').addEventListener('click', () => {
        modalCheck.classList.add('hidden');
    });

    // 3. Klik SUDAH Print -> Langsung Buka Scanner
    document.getElementById('btn-sudah-print').addEventListener('click', () => {
        modalCheck.classList.add('hidden');
        openScanner();
    });

    // 4. Klik BELUM Print -> Cek Saldo Dulu
    document.getElementById('btn-belum-print').addEventListener('click', () => {
        modalCheck.classList.add('hidden');
        if (userSaldo < scanCost) {
            // Jika Saldo Kurang -> Alert Error & Jangan Buka Scanner
            Swal.fire({
                icon: 'error',
                title: 'Saldo Tidak Cukup!',
                text: 'Sisa saldo Anda kurang dari Rp 1.000. Harap isi saldo atau cetak resi terlebih dahulu.',
            });
        } else {
            // Jika Saldo Cukup -> Buka Scanner
            Swal.fire({
                icon: 'success',
                title: 'Saldo Aman',
                text: 'Saldo akan dipotong otomatis saat scan berhasil.',
                timer: 1500, showConfirmButton: false
            });
            openScanner();
        }
    });

    // 5. Tombol Selesai Sesi
    document.getElementById('btn-cancel-scan').addEventListener('click', () => {
        wrapperInterface.classList.add('hidden');
        wrapperInit.classList.remove('hidden');
    });

    function openScanner() {
        wrapperInit.classList.add('hidden');
        wrapperInterface.classList.remove('hidden');
        document.getElementById('resi-spx').focus();
    }

</script>

@endpush

