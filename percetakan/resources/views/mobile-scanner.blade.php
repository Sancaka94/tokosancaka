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

        /* Tombol Orange Khas Sancaka */
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

        /* Area Scanner dengan Border Putus-putus Orange */
        #reader {
            width: 100%;
            border: 3px dashed #fd7e14;
            border-radius: 0.75rem;
            background-color: #000; /* Background hitam agar video kontras */
            overflow: hidden;
        }

        /* Indikator Status */
        .status-card {
            transition: all 0.3s;
        }
    </style>
</head>
<body>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">

                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0 pt-4 pb-0 text-center">
                        <h4 class="fw-bold text-dark mb-0">Scanner POS Sancaka</h4>
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
                            <input type="text" id="manual-input" class="form-control border-start-0" placeholder="Atau ketik barcode...">
                            <button class="btn btn-orange" type="button" id="btn-manual">Kirim</button>
                        </div>

                    </div>
                    <div class="card-footer bg-light text-center py-3">
                        <small class="text-muted">Pastikan cahaya ruangan cukup terang.</small>
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
            // Konfigurasi
            const routeProcess = "{{ route('scanner.process') }}";
            const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

            // Elemen UI
            const statusAlert = document.getElementById('status-alert');
            const statusMsg = document.getElementById('status-msg');
            const beepSuccess = document.getElementById('beep-success');
            const beepFail = document.getElementById('beep-fail');
            const manualInput = document.getElementById('manual-input');

            let html5QrCode = null;
            let isProcessing = false;

            // --- FUNGSI UPDATE UI ---
            function updateStatus(message, type = 'secondary') {
                statusAlert.className = `alert alert-${type} d-flex align-items-center`;
                statusMsg.innerText = message;

                // Icon handling sederhana
                let iconClass = 'fa-camera';
                if(type === 'success') iconClass = 'fa-check-circle';
                if(type === 'danger') iconClass = 'fa-times-circle';
                if(type === 'warning') iconClass = 'fa-spinner fa-spin';

                statusAlert.querySelector('i').className = `fas ${iconClass} me-2`;
            }

            // --- CORE PROCESS (Kirim Data ke Server) ---
            async function processBarcode(barcode) {
                if (isProcessing) return;
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

                    if (response.ok) { // Sesuaikan dengan response server Anda
                        beepSuccess.play();
                        updateStatus(`Sukses: ${result.message || barcode}`, 'success');
                        // Reset status setelah 2 detik
                        setTimeout(() => {
                            updateStatus('Siap Scan Barcode...', 'secondary');
                            isProcessing = false;
                        }, 2000);
                    } else {
                        throw new Error(result.message || 'Gagal memproses');
                    }

                } catch (error) {
                    console.error(error);
                    beepFail.play();
                    updateStatus(`Gagal: ${error.message}`, 'danger');

                    setTimeout(() => {
                        updateStatus('Siap Scan Barcode...', 'secondary');
                        isProcessing = false;
                    }, 2000);
                }
            }

            // --- ENGINE SCANNER (Setelan disamakan dengan Referensi 1) ---
            function startScanner() {
                html5QrCode = new Html5Qrcode("reader");

                // Config disamakan dengan referensi awal (FPS 10, QRBox 250)
                // Ini lebih stabil daripada FPS 20
                const config = {
                    fps: 10,
                    qrbox: 250,
                    aspectRatio: 1.0
                };

                html5QrCode.start(
                    { facingMode: "environment" },
                    config,
                    (decodedText, decodedResult) => {
                        // Callback sukses scan
                        processBarcode(decodedText);
                    },
                    (errorMessage) => {
                        // Error scanning frame, biarkan kosong agar log tidak penuh
                    }
                ).catch(err => {
                    updateStatus("Kamera tidak dapat diakses", 'danger');
                });
            }

            // --- INPUT MANUAL ---
            document.getElementById('btn-manual').addEventListener('click', () => {
                processBarcode(manualInput.value);
                manualInput.value = '';
            });

            manualInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    processBarcode(manualInput.value);
                    manualInput.value = '';
                }
            });

            // Jalankan Scanner
            startScanner();
        });
    </script>
</body>
</html>
