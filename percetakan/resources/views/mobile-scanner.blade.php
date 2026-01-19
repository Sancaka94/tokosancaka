<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Scanner POS Sancaka</title>

    <link rel="icon" type="image/png" href="https://tokosancaka.com/storage/uploads/sancaka.png">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">

    <style>
        body { font-family: 'Poppins', sans-serif; background-color: #f8fafc; }
        .text-orange { color: #f57224; }
        .btn-orange { background-color: #f57224; border-color: #f57224; color: #fff; font-weight: 600; }
        .btn-orange:hover { background-color: #e05b10; border-color: #e05b10; color: #fff; }

        #reader {
            width: 100%;
            border: 3px dashed #fd7e14;
            border-radius: 0.75rem;
            background-color: #000;
            overflow: hidden;
        }

        .cart-item { animation: slideIn 0.3s ease-out; }
        @keyframes slideIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body>

    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">

                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header bg-white border-0 pt-4 text-center">
                        <h5 class="fw-bold text-dark">Scanner POS Sancaka</h5>
                        <p class="text-muted small">Arahkan kamera ke Barcode</p>
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
                            <input type="text" id="manual-input" class="form-control" placeholder="Ketik barcode manual...">
                            <button class="btn btn-orange" type="button" id="btn-manual"><i class="fas fa-search"></i></button>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom py-3">
                        <h6 class="fw-bold m-0"><i class="fas fa-shopping-cart text-orange me-2"></i>Item Ter-Scan</h6>
                    </div>
                    <div class="card-body p-0" style="max-height: 300px; overflow-y: auto;">
                        <ul class="list-group list-group-flush" id="cart-list">
                            <li class="list-group-item text-center text-muted py-4 small" id="empty-cart">
                                Belum ada item masuk.
                            </li>
                        </ul>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <audio id="beep-success" src="https://tokosancaka.com/public/sound/beep.mp3"></audio>
    <audio id="beep-fail" src="https://tokosancaka.biz.id/public/sound/beep-gagal.mp3"></audio>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const beepSuccess = document.getElementById('beep-success');
            const beepFail = document.getElementById('beep-fail');
            const statusMsg = document.getElementById('status-msg');
            const statusAlert = document.getElementById('status-alert');
            const cartList = document.getElementById('cart-list');
            const emptyCart = document.getElementById('empty-cart');

            let isProcessing = false;
            let html5QrCode = null;

            function updateStatus(msg, type='secondary') {
                statusMsg.innerText = msg;
                statusAlert.className = `alert alert-${type} d-flex align-items-center`;
            }

            // Fungsi Utama Pemrosesan
            async function processBarcode(barcode) {
                if(isProcessing) return;
                if(!barcode || barcode.length < 3) return;

                isProcessing = true;
                if(html5QrCode) html5QrCode.pause();

                updateStatus("Mencari data produk...", "warning");

                try {
                    // 1. CARI BARANG (Hanya Lookup untuk info Nama & Harga)
                    const routeUrl = "{{ route('orders.scan-product') }}";

                    // Gunakan encodeURIComponent agar karakter spesial barcode aman
                    const response = await fetch(`${routeUrl}?code=${encodeURIComponent(barcode)}`, {
                        headers: { 'Accept': 'application/json' }
                    });

                    // Validasi HTML Error (404/500)
                    const contentType = response.headers.get("content-type");
                    if (!contentType || !contentType.includes("application/json")) {
                        throw new Error("Respon Server Error (Bukan JSON). Cek Log Server.");
                    }

                    const result = await response.json();

                    if(result.status === 'success') {
                        beepSuccess.play();
                        const product = result.data;
                        const unitLabel = result.unit || 'pcs';

                        // 2. MUNCULKAN POPUP KONFIRMASI JUMLAH
                        const { value: qty } = await Swal.fire({
                            title: 'Produk Ditemukan!',
                            html: `
                                <div class="text-start">
                                    <p class="mb-1"><strong>Nama:</strong> ${product.name}</p>
                                    <p class="mb-1"><strong>Harga:</strong> Rp ${new Intl.NumberFormat('id-ID').format(product.sell_price)} /${unitLabel}</p>
                                    <p class="mb-3"><strong>Sisa Stok:</strong> ${product.stock} ${unitLabel}</p>
                                    <label class="form-label">Masukkan Jumlah Order (${unitLabel}):</label>
                                </div>
                            `,
                            input: 'number',
                            inputValue: 1,
                            inputAttributes: {
                                min: 0.1,
                                step: 0.1,
                                max: product.stock
                            },
                            showCancelButton: true,
                            confirmButtonText: 'Masukan Keranjang',
                            confirmButtonColor: '#f57224',
                            cancelButtonText: 'Batal',
                            didOpen: () => {
                                const input = Swal.getInput();
                                if(input) input.select();
                            }
                        });

                        // 3. JIKA USER KLIK OK (QTY TERISI)
                        if (qty) {
                            // Tampilkan di HP (Visual List)
                            addToCartUI(product, qty, unitLabel);

                            // =======================================================
                            // [PERBAIKAN] KIRIM DATA KE LAPTOP (BROADCAST)
                            // =======================================================
                            try {
                                updateStatus("Mengirim ke kasir...", "info");

                                await fetch("{{ route('scanner.process') }}", {
                                    method: "POST",
                                    headers: {
                                        "Content-Type": "application/json",
                                        "X-CSRF-TOKEN": "{{ csrf_token() }}"
                                    },
                                    body: JSON.stringify({
                                        // [FIXED] Gunakan variabel 'barcode' mentah dari parameter fungsi
                                        // Jangan pakai product.barcode karena dari DB bisa saja namanya beda/null
                                        barcode: barcode,

                                        qty: qty
                                    })
                                });

                                // Notifikasi Sukses
                                updateStatus("Berhasil masuk ke Kasir!", "success");
                                const Toast = Swal.mixin({
                                    toast: true, position: 'top-end', showConfirmButton: false, timer: 2000
                                });
                                Toast.fire({ icon: 'success', title: 'Terkirim ke Kasir!' });

                            } catch (broadcastError) {
                                console.error(broadcastError);
                                alert("Gagal Kirim ke Laptop: " + broadcastError.message);
                                updateStatus("Gagal Broadcast", "danger");
                            }
                            // =======================================================

                        } else {
                            updateStatus("Input dibatalkan.", "secondary");
                        }

                    } else {
                        // Jika status error (Stok habis / tidak ketemu)
                        throw new Error(result.message || "Produk tidak terdaftar.");
                    }

                } catch (error) {
                    beepFail.play();
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: error.message,
                        timer: 2000,
                        showConfirmButton: false
                    });
                    updateStatus(error.message, "danger");
                } finally {
                    isProcessing = false;
                    if(html5QrCode) html5QrCode.resume();
                }
            }

            // Fungsi UI: Menambah list di bawah layar HP
            function addToCartUI(product, qty, unit) {
                if(emptyCart) emptyCart.style.display = 'none';

                const subtotal = product.sell_price * qty;

                const li = document.createElement('li');
                li.className = 'list-group-item cart-item d-flex justify-content-between align-items-center';
                li.innerHTML = `
                    <div>
                        <div class="fw-bold text-dark">${product.name}</div>
                        <small class="text-muted">
                            ${qty} ${unit} x ${new Intl.NumberFormat('id-ID').format(product.sell_price)}
                        </small>
                    </div>
                    <div class="text-end">
                        <span class="d-block fw-bold text-orange">Rp ${new Intl.NumberFormat('id-ID').format(subtotal)}</span>
                        <small class="text-secondary" style="font-size:10px;">${new Date().toLocaleTimeString()}</small>
                    </div>
                `;

                cartList.prepend(li);
            }

            // Inisialisasi Kamera
            function startScanner() {
                html5QrCode = new Html5Qrcode("reader");
                const config = { fps: 30, qrbox: 250, aspectRatio: 1.0 };

                html5QrCode.start(
                    { facingMode: "environment" },
                    config,
                    (decodedText) => processBarcode(decodedText),
                    (err) => {}
                ).catch(err => updateStatus("Kamera Gagal/Izin Ditolak", "danger"));
            }

            const manualInput = document.getElementById('manual-input');
            const btnManual = document.getElementById('btn-manual');

            // Event Listeners Manual Input
            btnManual.addEventListener('click', () => {
                processBarcode(manualInput.value.trim());
                manualInput.value = '';
            });

            manualInput.addEventListener('keypress', (e) => {
                if(e.key === 'Enter') {
                    processBarcode(manualInput.value.trim());
                    manualInput.value = '';
                }
            });

            // Mulai Scanner saat halaman dimuat
            startScanner();
        });
    </script>
</body>
</html>
