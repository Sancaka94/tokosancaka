<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Scanner POS - Sancaka</title>

    <link rel="icon" type="image/png" href="https://tokosancaka.com/storage/uploads/sancaka.png">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8fafc;
        }

        /* Tombol & Aksen Orange Sancaka */
        .text-orange { color: #f57224; }
        .bg-orange { background-color: #f57224; color: white; }
        .btn-orange {
            background-color: #f57224;
            border-color: #f57224;
            color: #fff;
            font-weight: 600;
        }
        .btn-orange:hover {
            background-color: #e05b10;
            border-color: #e05b10;
            color: #fff;
        }

        /* Area Scanner */
        #reader {
            width: 100%;
            border: 3px dashed #fd7e14;
            border-radius: 0.75rem;
            background-color: #000;
            overflow: hidden;
        }

        /* Area History */
        .history-container {
            max-height: 300px;
            overflow-y: auto;
        }

        /* Animasi Item Baru Muncul */
        .history-item {
            animation: slideDown 0.3s ease-out;
        }
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>

    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">

                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header bg-white border-0 pt-4 pb-0 text-center">
                        <h5 class="fw-bold text-dark mb-0">Scanner POS Sancaka</h5>
                        <p class="text-muted small">Arahkan kamera ke Barcode Barang</p>
                    </div>

                    <div class="card-body p-4">
                        <div id="status-alert" class="alert alert-secondary d-flex align-items-center" role="alert">
                            <i class="fas fa-camera me-2"></i>
                            <div id="status-msg" class="fw-medium">Siap melakukan scan...</div>
                        </div>

                        <div class="position-relative mb-3">
                            <div id="reader"></div>
                        </div>

                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0"><i class="fas fa-barcode text-muted"></i></span>
                            <input type="text" id="manual-input" class="form-control border-start-0" placeholder="Ketik manual...">
                            <button class="btn btn-orange" type="button" id="btn-manual"><i class="fas fa-paper-plane"></i></button>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold m-0"><i class="fas fa-history text-orange me-2"></i>Riwayat Scan</h6>
                        <span class="badge bg-orange rounded-pill" id="total-count">0 Paket</span>
                    </div>

                    <div class="card-body p-0 history-container">
                        <div id="empty-state" class="text-center py-4 text-muted">
                            <i class="fas fa-box-open fa-2x mb-2 opacity-50"></i>
                            <p class="small m-0">Belum ada barang di-scan.</p>
                        </div>

                        <ul class="list-group list-group-flush" id="history-list"></ul>
                    </div>

                    <div class="card-footer bg-white p-2">
                        <button class="btn btn-outline-danger btn-sm w-100" onclick="clearHistory()">
                            <i class="fas fa-trash-alt me-1"></i> Bersihkan Riwayat
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <audio id="beep-success" src="https://tokosancaka.com/public/sound/beep.mp3" preload="auto"></audio>
    <audio id="beep-fail" src="https://tokosancaka.biz.id/public/sound/beep-gagal.mp3" preload="auto"></audio>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // === KONFIGURASI ===
            const routeProcess = "{{ route('scanner.process') }}";
            const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

            // === STATE ===
            let isProcessing = false;
            let scanHistory = []; // Menyimpan data scan sementara

            // === UI ELEMENTS ===
            const statusAlert = document.getElementById('status-alert');
            const statusMsg = document.getElementById('status-msg');
            const historyList = document.getElementById('history-list');
            const emptyState = document.getElementById('empty-state');
            const totalCountBadge = document.getElementById('total-count');
            const manualInput = document.getElementById('manual-input');
            const beepSuccess = document.getElementById('beep-success');
            const beepFail = document.getElementById('beep-fail');

            // --- FUNGSI 1: UPDATE STATUS BAR ---
            function updateStatus(message, type = 'secondary') {
                statusAlert.className = `alert alert-${type} d-flex align-items-center`;
                statusMsg.innerText = message;

                let iconClass = 'fa-camera';
                if(type === 'success') iconClass = 'fa-check-circle';
                if(type === 'danger') iconClass = 'fa-times-circle';
                if(type === 'warning') iconClass = 'fa-spinner fa-spin';

                statusAlert.querySelector('i').className = `fas ${iconClass} me-2`;
            }

            // --- FUNGSI 2: TAMBAH KE HISTORY ---
            function addToHistory(barcode, responseMessage) {
                // Sembunyikan state kosong
                if(emptyState) emptyState.style.display = 'none';

                // Ambil waktu sekarang
                const now = new Date();
                const timeString = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });

                // Buat elemen HTML list item
                const li = document.createElement('li');
                li.className = 'list-group-item history-item d-flex justify-content-between align-items-center';
                li.innerHTML = `
                    <div>
                        <div class="fw-bold text-dark">${barcode}</div>
                        <small class="text-muted" style="font-size: 11px;">
                            <i class="far fa-clock me-1"></i>${timeString} - ${responseMessage}
                        </small>
                    </div>
                    <span class="text-success"><i class="fas fa-check-circle"></i></span>
                `;

                // Masukkan ke paling atas (Prepend)
                historyList.prepend(li);

                // Update Array & Counter
                scanHistory.push(barcode);
                totalCountBadge.innerText = `${scanHistory.length} Paket`;
            }

            // --- FUNGSI 3: PROSES DATA KE SERVER ---
            async function processBarcode(barcode) {
                if (isProcessing) return;
                // Cegah scan kosong atau terlalu pendek
                if (!barcode || barcode.length < 3) return;

                isProcessing = true;
                updateStatus(`Memproses: ${barcode}...`, 'warning');

                try {
                    const response = await fetch(routeProcess, {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                            "X-CSRF-TOKEN": csrfToken,
                            "Accept": "application/json"
                        },
                        body: JSON.stringify({ barcode: barcode })
                    });

                    const result = await response.json();

                    if (response.ok) {
                        // SUKSES
                        beepSuccess.play();
                        updateStatus(`Sukses: ${barcode}`, 'success');

                        // Panggil Fungsi Tambah History
                        addToHistory(barcode, result.message || 'Berhasil');

                        setTimeout(() => {
                            updateStatus('Siap Scan Barcode...', 'secondary');
                            isProcessing = false;
                        }, 1500);

                    } else {
                        throw new Error(result.message || 'Gagal memproses data');
                    }

                } catch (error) {
                    // GAGAL
                    console.error(error);
                    beepFail.play();
                    updateStatus(`Gagal: ${error.message}`, 'danger');

                    setTimeout(() => {
                        updateStatus('Siap Scan Barcode...', 'secondary');
                        isProcessing = false;
                    }, 2000);
                }
            }

            // --- FUNGSI 4: START KAMERA ---
            function startScanner() {
                const html5QrCode = new Html5Qrcode("reader");
                const config = { fps: 20, qrbox: 250, aspectRatio: 1.0 };

                html5QrCode.start(
                    { facingMode: "environment" },
                    config,
                    (decodedText, decodedResult) => {
                        processBarcode(decodedText);
                    },
                    (errorMessage) => { /* Ignore errors */ }
                ).catch(err => {
                    updateStatus("Kamera Error / Izin Ditolak", 'danger');
                });
            }

            // --- EVENT LISTENERS ---

            // Tombol Manual Input
            document.getElementById('btn-manual').addEventListener('click', () => {
                const val = manualInput.value.trim();
                if(val) {
                    processBarcode(val);
                    manualInput.value = '';
                }
            });

            // Enter di Input Manual
            manualInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    const val = manualInput.value.trim();
                    if(val) {
                        processBarcode(val);
                        manualInput.value = '';
                    }
                }
            });

            // Fungsi Bersihkan History (Global Function agar bisa diakses onclick HTML)
            window.clearHistory = function() {
                if(confirm('Hapus semua riwayat scan di layar ini?')) {
                    historyList.innerHTML = '';
                    scanHistory = [];
                    totalCountBadge.innerText = '0 Paket';
                    emptyState.style.display = 'block';
                }
            };

            // Jalankan Scanner
            startScanner();
        });
    </script>
</body>
</html>
