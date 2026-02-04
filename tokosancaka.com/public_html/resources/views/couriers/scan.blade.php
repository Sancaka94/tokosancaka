{{-- Halaman ini tidak menggunakan layout admin karena akan diakses oleh kurir --}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aplikasi Scan Kurir</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
    <style>
        #reader { width: 100%; max-width: 500px; border-radius: 0.5rem; overflow: hidden; margin: 0 auto; }
        #reader__scan_region { border-color: #3b82f6 !important; background: rgba(59, 130, 246, 0.1) !important; }
    </style>
</head>
<body class="bg-gray-100 font-sans">
    <div class="container mx-auto max-w-2xl p-4">
        <header class="bg-white rounded-lg shadow p-6 mb-6">
            <h1 class="text-2xl font-bold text-gray-800 text-center">Aplikasi Scan Kurir</h1>
            <div id="courier-info" class="hidden mt-4 p-3 bg-blue-100 text-blue-800 rounded-lg text-center"></div>
        </header>

        <main class="bg-white rounded-lg shadow p-6">
            {{-- Bagian Pencarian Kurir (Muncul pertama kali) --}}
            <section id="search-section">
                <h2 class="text-xl font-semibold text-gray-700 mb-4 text-center">Cari & Pilih Nama Anda</h2>
                <div class="flex gap-2">
                    <input type="text" id="search-input" placeholder="Ketik nama kurir..." class="flex-grow w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <button id="search-btn" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded-lg">Cari</button>
                </div>
                <div id="search-results" class="mt-4 space-y-2"></div>
            </section>

            {{-- Bagian Scanner (Awalnya tersembunyi) --}}
            <div id="scanner-container" class="hidden">
                <!-- Monitor Scan -->
                <section class="mb-6 p-4 bg-gray-50 rounded-lg">
                    <h3 class="text-lg font-semibold text-gray-700 text-center">Monitor Scan Hari Ini</h3>
                    <div class="text-center text-4xl font-bold text-blue-600 mt-2" id="scan-count-today">0</div>
                    <p class="text-center text-sm text-gray-500">Jumlah surat jalan berhasil di-scan</p>
                </section>

                <section id="scanner-section">
                    <h2 class="text-xl font-semibold text-gray-700 mb-4 text-center">Arahkan Kamera ke Barcode</h2>
                    <div id="reader"></div>
                    <div class="text-center mt-4">
                        <button id="start-scan-btn" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded-lg transition duration-300">Mulai Scan</button>
                        <button id="stop-scan-btn" class="bg-red-500 hover:bg-red-600 text-white font-bold py-2 px-4 rounded-lg transition duration-300 hidden">Hentikan Scan</button>
                    </div>
                </section>

                <div id="status-message" class="mt-6 p-4 rounded-lg text-center font-semibold hidden"></div>

                <section class="mt-8">
                    <h3 class="text-lg font-semibold text-gray-700 mb-3">Riwayat Scan Hari Ini</h3>
                    <div id="scan-history" class="space-y-2"><p class="text-gray-500 text-center">Belum ada paket yang di-scan.</p></div>
                </section>
            </div>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const SEARCH_API_ENDPOINT = "{{ route('api.couriers.search') }}";
            const UPDATE_STATUS_API_ENDPOINT = "{{ route('api.packages.update_status') }}";
            const UPDATE_LOCATION_API_ENDPOINT = "{{ url('api/couriers') }}";

            const searchInput = document.getElementById('search-input');
            const searchBtn = document.getElementById('search-btn');
            const searchResultsEl = document.getElementById('search-results');
            const courierInfoEl = document.getElementById('courier-info');
            const searchSection = document.getElementById('search-section');
            const scannerContainer = document.getElementById('scanner-container');
            const statusMessageEl = document.getElementById('status-message');
            const scanCountEl = document.getElementById('scan-count-today');
            
            let selectedCourier = null;
            let successfulScansToday = 0;

            async function searchCourier() {
                const query = searchInput.value;
                if (query.length < 3) {
                    searchResultsEl.innerHTML = '<p class="text-center text-gray-500">Ketik minimal 3 huruf.</p>';
                    return;
                }
                try {
                    const response = await fetch(`${SEARCH_API_ENDPOINT}?name=${query}`);
                    const couriers = await response.json();
                    displaySearchResults(couriers);
                } catch (error) {
                    console.error('Search Error:', error);
                    searchResultsEl.innerHTML = '<p class="text-center text-red-500">Gagal mencari kurir.</p>';
                }
            }
            
            function displaySearchResults(couriers) {
                if (couriers.length === 0) {
                    searchResultsEl.innerHTML = '<p class="text-center text-gray-500">Kurir tidak ditemukan.</p>';
                } else {
                    searchResultsEl.innerHTML = couriers.map(courier => `
                        <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                            <span>${courier.full_name} (${courier.courier_id})</span>
                            <button data-id="${courier.id}" data-name="${courier.full_name}" data-courier-id="${courier.courier_id}" class="select-courier-btn bg-green-500 hover:bg-green-600 text-white text-sm font-bold py-1 px-3 rounded-lg">Pilih</button>
                        </div>
                    `).join('');
                }
            }

            function activateGPS(courierId) {
                if (!navigator.geolocation) {
                    alert('Geolocation tidak didukung oleh browser Anda.');
                    return;
                }
                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        const { latitude, longitude } = position.coords;
                        fetch(`${UPDATE_LOCATION_API_ENDPOINT}/${courierId}/location`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': "{{ csrf_token() }}",
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({ latitude, longitude })
                        });
                        alert('GPS Aktif! Lokasi Anda telah dicatat.');
                    },
                    () => {
                        alert('Gagal mendapatkan lokasi. Pastikan GPS Anda aktif dan berikan izin akses lokasi.');
                    }
                );
            }

            searchBtn.addEventListener('click', searchCourier);
            searchInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') searchCourier();
            });

            searchResultsEl.addEventListener('click', (e) => {
                if (e.target.classList.contains('select-courier-btn')) {
                    selectedCourier = {
                        id: e.target.dataset.id,
                        name: e.target.dataset.name,
                        courierId: e.target.dataset.courierId
                    };
                    courierInfoEl.innerHTML = `<p>Kurir: <strong>${selectedCourier.name}</strong> (${selectedCourier.courierId})</p>`;
                    courierInfoEl.classList.remove('hidden');
                    searchSection.classList.add('hidden');
                    scannerContainer.classList.remove('hidden');
                    activateGPS(selectedCourier.id);
                    initializeScanner(selectedCourier.id);
                }
            });

            function initializeScanner(courierId) {
                const startScanBtn = document.getElementById('start-scan-btn');
                const stopScanBtn = document.getElementById('stop-scan-btn');
                const scanHistoryEl = document.getElementById('scan-history');

                let scanHistory = [];
                let html5QrCode = new Html5Qrcode("reader");
                let isScanning = false;

                function playBeep(isSuccess) {
                    const audioContext = new (window.AudioContext || window.webkitAudioContext)();
                    if (!audioContext) return;
                    const oscillator = audioContext.createOscillator();
                    const gainNode = audioContext.createGain();
                    oscillator.connect(gainNode);
                    gainNode.connect(audioContext.destination);
                    oscillator.type = 'sine';
                    oscillator.frequency.value = isSuccess ? 800 : 400;
                    gainNode.gain.setValueAtTime(0.1, audioContext.currentTime);
                    oscillator.start();
                    oscillator.stop(audioContext.currentTime + 0.2);
                }

                function showStatusMessage(message, isSuccess) {
                    statusMessageEl.textContent = message;
                    statusMessageEl.className = `mt-6 p-4 rounded-lg text-center font-semibold ${isSuccess ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}`;
                    statusMessageEl.classList.remove('hidden');
                    setTimeout(() => statusMessageEl.classList.add('hidden'), 5000);
                }

                function updateScanHistory() {
                    if (scanHistory.length === 0) {
                        scanHistoryEl.innerHTML = '<p class="text-gray-500 text-center">Belum ada paket yang di-scan.</p>';
                    } else {
                        scanHistoryEl.innerHTML = scanHistory.map(item => `
                            <div class="flex justify-between items-center p-3 rounded-lg ${item.success ? 'bg-gray-50' : 'bg-red-50'}">
                                <p class="font-mono font-semibold">${item.resi}</p>
                                <span class="px-3 py-1 text-sm font-bold rounded-full ${item.success ? 'bg-green-200 text-green-800' : 'bg-red-200 text-red-800'}">${item.status}</span>
                            </div>`).join('');
                    }
                }

                async function handleResi(shippingCode) {
                    if (!shippingCode || scanHistory.some(item => item.resi === shippingCode)) {
                        playBeep(false);
                        showStatusMessage(`Resi ${shippingCode} sudah pernah di-scan.`, false);
                        return;
                    }

                    try {
                        const response = await fetch(UPDATE_STATUS_API_ENDPOINT, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': "{{ csrf_token() }}",
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                shipping_code: shippingCode,
                                courier_id: courierId
                            })
                        });

                        const result = await response.json();
                        const isSuccess = response.ok;
                        
                        playBeep(isSuccess);

                        if(isSuccess) {
                            successfulScansToday++;
                            scanCountEl.textContent = successfulScansToday;
                        }

                        showStatusMessage(result.message, isSuccess);
                        scanHistory.unshift({ resi: shippingCode, status: isSuccess ? 'Sukses' : 'Gagal', success: isSuccess });
                        updateScanHistory();

                    } catch (error) {
                        playBeep(false);
                        console.error("API Error:", error);
                        showStatusMessage("Terjadi kesalahan jaringan.", false);
                    }
                }

                startScanBtn.addEventListener('click', () => {
                    html5QrCode.start({ facingMode: "environment" }, { fps: 10, qrbox: { width: 250, height: 250 } }, (decodedText) => {
                        if (isScanning) handleResi(decodedText);
                    }, () => {})
                    .then(() => {
                        isScanning = true;
                        startScanBtn.classList.add('hidden');
                        stopScanBtn.classList.remove('hidden');
                    }).catch(err => {
                        showStatusMessage("Gagal memulai kamera. Pastikan izin sudah diberikan.", false);
                    });
                });

                stopScanBtn.addEventListener('click', () => {
                    html5QrCode.stop().then(() => {
                        isScanning = false;
                        stopScanBtn.classList.add('hidden');
                        startScanBtn.classList.remove('hidden');
                    }).catch(err => console.error("Gagal menghentikan scanner.", err));
                });
            }
        });
    </script>
</body>
</html>
