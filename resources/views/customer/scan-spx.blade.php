@extends('layouts.customer') {{-- Pastikan layout ini sudah memuat Tailwind CSS --}}



@section('title', 'Scan Paket SPX')



@push('styles')

{{-- Custom style untuk animasi dan area scanner --}}

<style>

    #reader { border: 3px dashed #F97316; } /* Tailwind orange-500 */

    .scan-history-item, .flash-message { animation: fadeIn 0.5s ease-in-out; }

    @keyframes fadeIn {

        from { opacity: 0; transform: translateY(-10px); }

        to { opacity: 1; transform: translateY(0); }

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

                <div class="bg-white p-6 shadow-md rounded-xl h-full">

                    <h2 class="text-2xl font-bold text-gray-800 mb-6">Daftarkan Paket SPX Anda</h2>



                    <div class="mb-6">

                        <label class="block text-sm font-medium text-gray-700">Pelanggan Terdaftar</label>

                        <input type="text" class="mt-1 block w-full bg-gray-100 border-gray-300 rounded-md shadow-sm" value="{{ $customer->name }} ({{ $customer->phone_number }})" readonly>

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

    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;



    // --- Variabel State ---

    let html5QrCode = null;

    let scannedResiArray = Array.from(document.querySelectorAll('.scanned-resi-value')).map(el => el.textContent);

    let suratJalanData = null;



    // --- Audio Notifikasi ---

    const successSound = new Audio("{{ asset('sound/beep.mp3') }}");

    const failSound = new Audio("{{ asset('sound/beep-gagal.mp3') }}");



    // --- Fungsi-fungsi ---

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



    // --- Event Listener ---

    resiInput.focus();

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

@endpush

