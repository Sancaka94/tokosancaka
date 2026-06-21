<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>Sancaka Express - Checkout Pembayaran</title>
    
    <script src="https://www.paypal.com/sdk/js?client-id={{ $paypalClientId }}&currency=USD&components=googlepay,paypal-payments"></script>
    
    <style>
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background-color: #f4f6f9; 
            color: #333; 
            padding: 40px 20px; 
        }
        .checkout-card { 
            max-width: 480px; 
            margin: 0 auto; 
            background: #fff; 
            padding: 30px; 
            border-radius: 12px; 
            box-shadow: 0 4px 6px rgba(0,0,0,0.05), 0 1px 3px rgba(0,0,0,0.1); 
        }
        .checkout-header { 
            text-align: center; 
            margin-bottom: 25px; 
        }
        .checkout-header h2 { 
            margin: 0; 
            color: #1a202c; 
            font-size: 22px; 
        }
        .order-summary { 
            background: #f8fafc; 
            padding: 15px; 
            border-radius: 8px; 
            margin-bottom: 25px; 
            border: 1px solid #e2e8f0; 
        }
        .order-row { 
            display: flex; 
            justify-content: space-between; 
            font-weight: bold; 
            font-size: 16px; 
            color: #1e293b; 
        }
        .payment-methods { 
            display: flex; 
            flex-direction: column; 
            gap: 15px; 
        }
        #google-pay-button-container { 
            min-height: 40px; 
        }
    </style>
</head>
<body>

<div class="checkout-card">
    <div class="checkout-header">
        <h2>Metode Pembayaran</h2>
        <p style="color: #64748b; font-size: 14px; margin-top: 5px;">Selesaikan transaksi Anda dengan aman</p>
    </div>

    <div class="order-summary">
        <div class="order-row">
            <span>Total Tagihan:</span>
            <span>$100.00</span>
        </div>
    </div>

    <div class="payment-methods">
        <div id="google-pay-button-container"></div>
        
        <paypal-button id="standard-paypal-btn" hidden></paypal-button>
    </div>
</div>

<script src="{{ asset('js/googlepay-app.js') }}"></script>

<script>
    document.addEventListener("DOMContentLoaded", async () => {
        console.log("LOG LOG: Memulai inisialisasi Standard PayPal Button v6 pada komponen Blade");
        try {
            if (!window.paypal || !window.paypal.createInstance) {
                console.error("LOG LOG: Objek global SDK PayPal v6 gagal dideteksi di browser");
                return;
            }

            // Membaca CSRF Token Laravel di scope lokal script
            const bladeCsrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            // Membuat instance terpisah untuk Standard PayPal Payment Session
            const standardSdkInstance = await window.paypal.createInstance({
                clientId: "{{ $paypalClientId }}",
                components: ["paypal-payments"],
                pageType: "checkout",
                locale: "id-ID",
            });

            // Handler Terpusat untuk Siklus Hidup Tombol PayPal Standar
            const standardOptions = {
                async onApprove(data) {
                    console.log("LOG LOG: Tombol PayPal Standar disetujui user. Memulai request capture. Order ID:", data.orderId);
                    try {
                        const response = await fetch(`/paypal/orders/${data.orderId}/capture`, {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/json",
                                "Accept": "application/json",
                                "X-CSRF-TOKEN": bladeCsrfToken
                            }
                        });
                        
                        const captureData = await response.json();
                        console.log("LOG LOG: Respon capture tombol standar diterima:", captureData);
                        
                        if (captureData && captureData.status === "COMPLETED") {
                            alert("Pembayaran Berhasil Diverifikasi!");
                            // Alihkan ke halaman sukses Tripay bawaan Anda agar tercatat rapi di log sistem
                            window.location.href = "/pembayaran/sukses-tripay?reference=" + data.orderId + "&jenis=paypal";
                        } else {
                            alert("Proses penarikan dana gagal atau tertunda.");
                        }
                    } catch (error) {
                        console.error("LOG LOG: Gagal mengeksekusi capture untuk tombol standar:", error);
                    }
                },
                onCancel(data) {
                    console.log("LOG LOG: Transaksi via tombol standar dibatalkan oleh user", data);
                },
                onError(error) {
                    console.error("LOG LOG: Terjadi eror teknis pada modul PayPal Button standar:", error);
                }
            };

            // Mengecek kelayakan metode pembayaran standar
            const eligibilityCheck = await standardSdkInstance.findEligibleMethods({ currencyCode: "USD" });

            if (eligibilityCheck.isEligible("paypal")) {
                console.log("LOG LOG: Metode pembayaran standar dinyatakan Eligible. Menampilkan tombol...");
                const standardSession = standardSdkInstance.createPayPalOneTimePaymentSession(standardOptions);
                const standardBtnElement = document.getElementById("standard-paypal-btn");
                
                if (standardBtnElement) {
                    standardBtnElement.removeAttribute("hidden");
                    standardBtnElement.addEventListener("click", async () => {
                        try {
                            console.log("LOG LOG: Event klik tombol standar terdeteksi. Memicu pembukaan modal/popup.");
                            
                            // Menggunakan fungsi createOrder global yang sudah dideklarasikan di googlepay-app.js
                            if (typeof createOrder === "function") {
                                await standardSession.start({ presentationMode: "auto" }, createOrder());
                            } else {
                                // Fallback aman jika script eksternal termuat terlambat
                                const orderFallback = fetch("/paypal/orders/create", {
                                    method: "POST",
                                    headers: {
                                        "Content-Type": "application/json",
                                        "Accept": "application/json",
                                        "X-CSRF-TOKEN": bladeCsrfToken
                                    }
                                }).then(res => res.json()).then(data => ({ orderId: data.id }));
                                
                                await standardSession.start({ presentationMode: "auto" }, orderFallback);
                            }
                        } catch (err) {
                            console.error("LOG LOG: Gagal meluncurkan popup sesi pembayaran standar:", err);
                        }
                    });
                }
            }
        } catch (err) {
            console.error("LOG LOG: Kesalahan fatal pada blok inisialisasi tombol standar Blade:", err);
        }
    });
</script>

<script async src="https://pay.google.com/gp/p/js/pay.js" onload="onGooglePayLoaded()"></script>

</body>
</html>