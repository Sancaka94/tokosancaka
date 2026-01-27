@extends('layouts.customer')

@section('title', 'Scan Paket SPX')

@push('styles')
<style>
    #reader { border: 3px dashed #F97316; } /* Tailwind orange-500 */
    .scan-history-item, .flash-message { animation: fadeIn 0.5s ease-in-out; }
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    /* Animasi Modal */
    .modal-enter { opacity: 0; transform: scale(0.9); }
    .modal-enter-active { opacity: 1; transform: scale(1); transition: opacity 0.3s, transform 0.3s; }
    .modal-exit { opacity: 1; transform: scale(1); }
    .modal-exit-active { opacity: 0; transform: scale(0.9); transition: opacity 0.3s, transform 0.3s; }
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

                    <h2 class="text-2xl font-bold text-gray-800 mb-2">Daftarkan Paket SPX Anda</h2>

                    {{-- Informasi Saldo --}}
                    <div class="mb-6 flex items-center justify-between bg-indigo-50 p-3 rounded-lg border border-indigo-100">
                        <div>
                            <span class="text-xs font-bold text-indigo-500 uppercase tracking-wide">Saldo Anda</span>
                            <div class="text-xl font-extrabold text-indigo-700">Rp {{ number_format($customer->saldo, 0, ',', '.') }}</div>
                        </div>
                        <div class="text-right">
                             <span class="text-xs text-gray-500">Biaya per scan</span>
                             <div class="font-bold text-gray-700">Rp 1.000</div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Pelanggan Terdaftar</label>
                        <input type="text" class="mt-1 block w-full bg-gray-100 border-gray-300 rounded-md shadow-sm text-gray-600 cursor-not-allowed" value="{{ $customer->nama_lengkap ?? $customer->name }}" readonly>
                    </div>

                    {{-- LAYER 1: Tombol Aktivasi Scan (Awal Muncul) --}}
                    <div id="init-scan-wrapper" class="text-center py-10">
                        <div class="bg-orange-50 rounded-full w-20 h-20 flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-qrcode text-3xl text-orange-600"></i>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">Siap untuk Memproses Paket?</h3>
                        <p class="text-sm text-gray-500 mb-6">Pastikan saldo mencukupi untuk memproses resi yang belum dicetak.</p>

                        <button type="button" id="btn-scan-sekarang" class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-orange-600 hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500 transition-all transform hover:scale-105">
                            <i class="fas fa-play mr-2"></i> Scan Sekarang
                        </button>
                    </div>

                    {{-- LAYER 2: Interface Scan (Awalnya Hidden) --}}
                    <div id="scan-interface" class="hidden transition-all duration-500 ease-in-out">
                        <form id="scan-form" class="mb-4">
                            <div>
                                <label for="resi-spx" class="block text-sm font-medium text-gray-700">Langkah 1: Input Resi</label>
                                <div class="mt-1 relative rounded-md shadow-sm">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="fas fa-barcode text-gray-400"></i>
                                    </div>
                                    <input type="text" id="resi-spx" name="resi_number" class="block w-full pl-10 pr-3 py-3 border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" placeholder="Ketik resi lalu tekan Enter..." required autocomplete="off">
                                </div>
                                <p class="mt-1 text-xs text-gray-500">*Tekan Enter untuk menyimpan</p>
                            </div>
                        </form>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Atau Gunakan Metode Lain:</label>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <button type="button" id="start-camera-btn" class="w-full flex justify-center items-center py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-orange-600 hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500">
                                    <i class="fas fa-camera mr-2"></i>Scan Kamera
                                </button>
                                <button type="button" id="gallery-scan-btn" class="w-full flex justify-center items-center py-3 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    <i class="fas fa-images mr-2"></i>Scan Galeri
                                </button>
                                <input type="file" id="gallery-file-input" class="hidden" accept="image/*">
                            </div>
                        </div>

                        <div class="mt-6 text-center">
                            <button type="button" id="btn-cancel-scan" class="text-sm text-red-500 hover:text-red-700 underline">
                                Batalkan / Selesai Sesi
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
    </div>
</div>

<div id="print-check-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-60 hidden backdrop-blur-sm">
    <div class="bg-white rounded-xl shadow-2xl overflow-hidden transform transition-all sm:max-w-md sm:w-full p-6 text-center">
        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-indigo-100 mb-4">
            <i class="fas fa-print text-indigo-600 text-lg"></i>
        </div>
        <h3 class="text-lg leading-6 font-bold text-gray-900 mb-2">Konfirmasi Cetak Resi</h3>
        <p class="text-sm text-gray-500 mb-6">
            Apakah Kakak sudah mencetak resi (Thermal) untuk paket yang akan di-scan ini?
        </p>

        <div class="grid grid-cols-2 gap-3">
            <button type="button" id="btn-belum-print" class="w-full inline-flex justify-center rounded-lg border border-transparent shadow-sm px-4 py-3 bg-red-50 text-base font-medium text-red-700 hover:bg-red-100 focus:outline-none sm:text-sm">
                <div class="flex flex-col items-center">
                    <span class="font-bold">BELUM</span>
                    <span class="text-xs font-normal">(Potong Saldo)</span>
                </div>
            </button>
            <button type="button" id="btn-sudah-print" class="w-full inline-flex justify-center rounded-lg border border-transparent shadow-sm px-4 py-3 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none sm:text-sm items-center">
                <span class="font-bold">SUDAH</span>
            </button>
        </div>

        <div class="mt-4 pt-4 border-t border-gray-100">
             <button type="button" id="close-check-modal" class="text-xs text-gray-400 hover:text-gray-600">Batal</button>
        </div>
    </div>
</div>

<div id="camera-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 hidden">
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
@endsection

@push('scripts')
<script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // --- Data PHP ke JS ---
    const userSaldo = {{ (float) $customer->saldo }};
    const scanCost = 1000;

    // --- Referensi Elemen ---
    const wrapperInit = document.getElementById('init-scan-wrapper');
    const wrapperInterface = document.getElementById('scan-interface');
    const btnScanSekarang = document.getElementById('btn-scan-sekarang');
    const btnCancelScan = document.getElementById('btn-cancel-scan');

    // Modal Confirm
    const modalCheck = document.getElementById('print-check-modal');
    const btnBelum = document.getElementById('btn-belum-print');
    const btnSudah = document.getElementById('btn-sudah-print');
    const btnCloseCheck = document.getElementById('close-check-modal');

    // Scan Logic
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
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    let html5QrCode = null;
    let scannedResiArray = Array.from(document.querySelectorAll('.scanned-resi-value')).map(el => el.textContent);
    let suratJalanData = null;
    const successSound = new Audio("{{ asset('sound/beep.mp3') }}");
    const failSound = new Audio("{{ asset('sound/beep-gagal.mp3') }}");

    // --- LOGIKA BUKA/TUTUP AKSES ---

    // 1. Klik Scan Sekarang -> Buka Modal
    btnScanSekarang.addEventListener('click', () => {
        modalCheck.classList.remove('hidden');
    });

    // 2. Tutup Modal Batal
    btnCloseCheck.addEventListener('click', () => {
        modalCheck.classList.add('hidden');
    });

    // 3. Klik SUDAH Print -> Langsung Buka Interface
    btnSudah.addEventListener('click', () => {
        modalCheck.classList.add('hidden');
        openInterface();
    });

    // 4. Klik BELUM Print -> Cek Saldo
    btnBelum.addEventListener('click', () => {
        modalCheck.classList.add('hidden');

        if (userSaldo < scanCost) {
            // Saldo Kurang
            failSound.play().catch(e => {});
            Swal.fire({
                icon: 'error',
                title: 'Saldo Tidak Cukup!',
                text: 'Saldo Anda kurang dari Rp 1.000. Mohon isi saldo untuk menggunakan fitur scan tanpa cetak.',
                confirmButtonColor: '#d33',
                confirmButtonText: 'Tutup'
            });
            // Interface TETAP TERTUTUP
        } else {
            // Saldo Cukup
            Swal.fire({
                icon: 'success',
                title: 'Saldo Mencukupi',
                text: 'Metode potong saldo diaktifkan untuk sesi ini.',
                timer: 1500,
                showConfirmButton: false
            });
            openInterface();
        }
    });

    // 5. Cancel Sesi -> Tutup Interface
    btnCancelScan.addEventListener('click', () => {
        wrapperInterface.classList.add('hidden');
        wrapperInit.classList.remove('hidden');
    });

    function openInterface() {
        wrapperInit.classList.add('hidden');
        wrapperInterface.classList.remove('hidden');
        setTimeout(() => resiInput.focus(), 300); // Focus ke input
    }


    // --- FUNGSI SCAN BAWAAN (TIDAK BERUBAH BANYAK) ---
    const showAlert = (message, type = 'danger', onCamera = false) => {
        const container = onCamera ? flashMessageContainer : alertContainer;
        const bgColor = type === 'success' ? 'bg-green-100 border-green-400 text-green-700' : 'bg-red-100 border-red-400 text-red-700';
        const iconClass = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle';
        const alertHTML = `<div class="flash-message border px-4 py-3 rounded relative ${bgColor}" role="alert">
            <i class="fas ${iconClass} mr-2"></i>
            <span class="block sm:inline">${message}</span>
        </div>`;
        container.innerHTML = alertHTML;
        setTimeout(() => { container.innerHTML = '' }, 4000);
    };

    const handleScan = async (resiValue) => {
        if (!resiValue) return;
        resiInput.disabled = true;

        // Frontend "Loading" state
        resiInput.classList.add('bg-gray-200');

        try {
            const response = await fetch("{{ route('customer.scan.spx.store') }}", {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                body: JSON.stringify({ resi_number: resiValue })
            });
            const result = await response.json();

            if (response.ok && result.success) {
                successSound.play().catch(e => {});
                showAlert(result.message, 'success', !cameraModalEl.classList.contains('hidden'));
                todaysCountEl.innerText = result.todays_count;
                recentScansContainer.innerHTML = result.recent_scans_html;
                if (!scannedResiArray.includes(result.package.resi_number)) {
                    scannedResiArray.push(result.package.resi_number);
                }
                suratJalanBtn.disabled = false;

                // Update tampilan saldo di UI jika dikembalikan oleh backend (Opsional)
                if(result.current_saldo) {
                     // Cari elemen saldo dan update text-nya
                     document.querySelector('.text-xl.font-extrabold.text-indigo-700').innerText = 'Rp ' + result.current_saldo;
                }

            } else {
                failSound.play().catch(e => {});
                showAlert(result.message || 'Terjadi kesalahan.', 'danger', !cameraModalEl.classList.contains('hidden'));
            }
        } catch (error) {
            failSound.play().catch(e => {});
            showAlert('Tidak dapat terhubung ke server.', 'danger', !cameraModalEl.classList.contains('hidden'));
        } finally {
            resiInput.value = '';
            resiInput.disabled = false;
            resiInput.classList.remove('bg-gray-200');
            resiInput.focus();
        }
    };

    const onScanSuccess = (decodedText, decodedResult) => {
        handleScan(decodedText);
    };

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

    // Listeners
    document.getElementById('scan-form').addEventListener('submit', (e) => {
        e.preventDefault();
        handleScan(resiInput.value);
    });

    startCameraBtn.addEventListener('click', openCameraModal);
    closeCameraModalBtn.addEventListener('click', closeCameraModal);
    doneScanBtn.addEventListener('click', closeCameraModal);

    galleryScanBtn.addEventListener('click', () => galleryFileInput.click());
    galleryFileInput.addEventListener('change', (e) => {
        if (e.target.files.length === 0) return;
        const imageFile = e.target.files[0];
        if (!html5QrCode) { html5QrCode = new Html5Qrcode("reader"); }
        showAlert('Memproses gambar...', 'info');
        html5QrCode.scanFile(imageFile, true)
            .then(decodedText => handleScan(decodedText))
            .catch(err => showAlert('Gagal memindai barcode dari gambar.', 'danger'))
            .finally(() => { galleryFileInput.value = ''; });
    });

    suratJalanBtn.addEventListener('click', async () => {
        if (scannedResiArray.length === 0) {
            showAlert('Belum ada resi yang di-scan.', 'warning');
            return;
        }
        suratJalanBtn.disabled = true;
        suratJalanBtn.innerHTML = '<svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Mencetak...';

        try {
            const response = await fetch("{{ route('customer.suratjalan.create') }}", {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                body: JSON.stringify({ resi_list: scannedResiArray })
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
                suratJalanBtn.innerHTML = '<i class="fas fa-print mr-2"></i>Langkah 2: Cetak Surat Jalan';
            }
        } catch (error) {
            showAlert('Tidak dapat terhubung ke server.', 'danger');
            suratJalanBtn.disabled = false;
            suratJalanBtn.innerHTML = '<i class="fas fa-print mr-2"></i>Langkah 2: Cetak Surat Jalan';
        }
    });

    whatsappBtn.addEventListener('click', () => {
        if (!suratJalanData) {
            showAlert('Data surat jalan tidak ditemukan. Silakan cetak ulang.', 'danger');
            return;
        }

        let resiListText = '*Daftar Resi:*\n';
        scannedResiArray.forEach((resi, index) => {
            resiListText += `${index + 1}. ${resi}\n`;
        });

        const adminNumber = '628819435180';
        const message = `*Konfirmasi Pickup Sancaka Express*\n\n` +
                        `Halo Admin, mohon segera proses pickup untuk:\n\n` +
                        `*Nama:* ${suratJalanData.customer_name}\n` +
                        `*Total Paket:* ${suratJalanData.package_count} Koli\n` +
                        `*Kode Surat Jalan:* ${suratJalanData.surat_jalan_code}\n\n` +
                        `${resiListText}\n` +
                        `Terima kasih.`;
        const whatsappUrl = `https://api.whatsapp.com/send?phone=${adminNumber}&text=${encodeURIComponent(message)}`;
        window.open(whatsappUrl, '_blank');

        showAlert('Konfirmasi sedang dibuka. Halaman akan direset.', 'info');
        setTimeout(() => {
            window.location.reload();
        }, 3000);
    });
});
</script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@endpush
