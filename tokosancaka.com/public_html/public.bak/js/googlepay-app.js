// public/js/googlepay-app.js

console.log("LOG LOG: File googlepay-app.js berhasil dieksekusi!");

const csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');
const csrfToken = csrfTokenMeta ? csrfTokenMeta.getAttribute('content') : '';

const baseRequest = {
    apiVersion: 2,
    apiVersionMinor: 0,
};

let paymentsClient = null,
    allowedPaymentMethods = null,
    merchantInfo = null;

// =========================================================================
// INISIALISASI TOMBOL STANDAR PAYPAL (Sebagai Cadangan/Pelengkap)
// =========================================================================
document.addEventListener("DOMContentLoaded", () => {
    if (window.paypal && window.paypal.Buttons) {
        console.log("LOG LOG: Merender PayPal Standard Buttons");
        window.paypal.Buttons({
            createOrder: async function() {
                console.log("LOG LOG: Membuat Order via Tombol PayPal Standar");
                const res = await fetch('/paypal/orders/create', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                    body: JSON.stringify({})
                });
                const data = await res.json();
                return data.id; // Return langsung ID untuk tombol standar
            },
            onApprove: async function(data) {
                console.log("LOG LOG: Order PayPal Standar disetujui, menangkap pembayaran...", data.orderID);
                const res = await fetch(`/paypal/orders/${data.orderID}/capture`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken }
                });
                const captureData = await res.json();
                if (captureData && captureData.status === "COMPLETED") {
                    alert("Pembayaran PayPal Berhasil!");
                    window.location.href = "/pembayaran/sukses-tripay?reference=" + data.orderID + "&jenis=paypal";
                }
            }
        }).render('#paypal-button-container');
    }
});


// =========================================================================
// LOGIKA GOOGLE PAY (Sesuai Dokumentasi Resmi)
// =========================================================================

function getGoogleIsReadyToPayRequest(allowedPaymentMethods) {
    return Object.assign({}, baseRequest, {
        allowedPaymentMethods: allowedPaymentMethods,
    });
}

async function getGooglePayConfig() {
    if (allowedPaymentMethods == null || merchantInfo == null) {
        console.log("LOG LOG: Mengambil Config dari paypal.Googlepay().config()");
        const googlePayConfig = await paypal.Googlepay().config();
        allowedPaymentMethods = googlePayConfig.allowedPaymentMethods;
        merchantInfo = googlePayConfig.merchantInfo;
    }
    return {
        allowedPaymentMethods,
        merchantInfo,
    };
}

async function getGooglePaymentDataRequest() {
    const paymentDataRequest = Object.assign({}, baseRequest);
    const { allowedPaymentMethods, merchantInfo } = await getGooglePayConfig();
    
    paymentDataRequest.allowedPaymentMethods = allowedPaymentMethods;
    paymentDataRequest.merchantInfo = merchantInfo;
    paymentDataRequest.callbackIntents = ["PAYMENT_AUTHORIZATION"];
    
    paymentDataRequest.transactionInfo = {
        countryCode: window.AppConfig.countryCode,
        currencyCode: window.AppConfig.currency,
        totalPriceStatus: "FINAL",
        totalPrice: window.AppConfig.amount,
    };
    
    return paymentDataRequest;
}

function onPaymentAuthorized(paymentData) {
    console.log("LOG LOG: Otorisasi Google Pay disetujui di sisi klien.");
    return new Promise(function (resolve, reject) {
        processPayment(paymentData)
            .then(function (data) {
                if (data.transactionState === "SUCCESS") {
                    resolve({ transactionState: "SUCCESS" });
                } else {
                    resolve({ transactionState: "ERROR", error: { intent: "PAYMENT_AUTHORIZATION", message: "Transaksi Gagal" }});
                }
            })
            .catch(function (errDetails) {
                resolve({ transactionState: "ERROR" });
            });
    });
}

function getGooglePaymentsClient() {
    if (paymentsClient === null) {
        paymentsClient = new google.payments.api.PaymentsClient({
            environment: window.AppConfig.googlePayEnv, // Mengikuti setting database
            paymentDataCallbacks: {
                onPaymentAuthorized: onPaymentAuthorized,
            },
        });
    }
    return paymentsClient;
}

// Fungsi ini dipanggil otomatis oleh <script onload="onGooglePayLoaded()"> di HTML
async function onGooglePayLoaded() {
    console.log("LOG LOG: Memulai pengecekan kelayakan Google Pay");
    try {
        if (!window.paypal || !window.paypal.Googlepay) {
            console.error("LOG LOG: Komponen paypal.Googlepay tidak terdeteksi!");
            return;
        }

        const paymentsClient = getGooglePaymentsClient();
        const { allowedPaymentMethods } = await getGooglePayConfig();
        
        const response = await paymentsClient.isReadyToPay(getGoogleIsReadyToPayRequest(allowedPaymentMethods));
        
        if (response.result) {
            console.log("LOG LOG: Perangkat mendukung Google Pay. Merender tombol...");
            addGooglePayButton();
        } else {
            console.warn("LOG LOG: isReadyToPay = false. Google Pay tidak didukung oleh browser/perangkat saat ini, ATAU tidak ada kartu tersimpan di Google Wallet.");
        }
    } catch (err) {
        console.error("LOG LOG: Error di onGooglePayLoaded:", err);
    }
}

function addGooglePayButton() {
    const paymentsClient = getGooglePaymentsClient();
    const button = paymentsClient.createButton({
        onClick: onGooglePaymentButtonClicked,
        allowedPaymentMethods: allowedPaymentMethods // Konfigurasi visual UI
    });
    const container = document.getElementById("google-pay-button-container");
    if(container) {
        container.innerHTML = "";
        container.appendChild(button);
        console.log("LOG LOG: Tombol Google Pay berhasil ditambahkan ke HTML");
    }
}

async function onGooglePaymentButtonClicked() {
    console.log("LOG LOG: Tombol Google Pay diklik");
    const paymentDataRequest = await getGooglePaymentDataRequest();
    const paymentsClient = getGooglePaymentsClient();
    paymentsClient.loadPaymentData(paymentDataRequest);
}

// Proses Pembayaran Utama (Create Order -> Confirm -> SCA -> Capture)
async function processPayment(paymentData) {
    try {
        console.log("LOG LOG: 1. Membuat Order di Backend Laravel");
        
        const orderResponse = await fetch(`/paypal/orders/create`, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": csrfToken
            },
            body: JSON.stringify({}),
        });
        const { id } = await orderResponse.json();
        console.log("LOG LOG: Order ID didapatkan:", id);

        console.log("LOG LOG: 2. Konfirmasi Order menggunakan paypal.Googlepay().confirmOrder()");
        const confirmOrderResponse = await paypal.Googlepay().confirmOrder({
            orderId: id,
            paymentMethodData: paymentData.paymentMethodData
        });

        console.log("LOG LOG: Status Konfirmasi:", confirmOrderResponse.status);

        // Penanganan 3D Secure (SCA)
        if (confirmOrderResponse.status === "PAYER_ACTION_REQUIRED") {
            console.log("LOG LOG: 3DS Required. Meluncurkan initiatePayerAction...");
            await paypal.Googlepay().initiatePayerAction({ orderId: id });
            console.log("LOG LOG: Aksi Payer 3DS selesai.");
        } else if (confirmOrderResponse.status !== "APPROVED") {
            throw new Error("Order tidak disetujui oleh PayPal.");
        }

        console.log("LOG LOG: 3. Mengeksekusi Capture di Backend Laravel");
        const captureResponse = await fetch(`/paypal/orders/${id}/capture`, {
            method: 'POST',
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": csrfToken
            }
        });
        const captureData = await captureResponse.json();

        if (captureData && captureData.status === "COMPLETED") {
            console.log("LOG LOG: Order Capture Completed!");
            alert("Pembayaran Google Pay Berhasil!");
            window.location.href = "/pembayaran/sukses-tripay?reference=" + id + "&jenis=googlepay";
            return { transactionState: 'SUCCESS' };
        } else {
            console.error("LOG LOG: Capture Gagal", captureData);
            return { transactionState: 'ERROR', error: { intent: 'PAYMENT_AUTHORIZATION', message: 'TRANSACTION FAILED' }};
        }

    } catch (err) {
        console.error("LOG LOG: Terjadi kesalahan di processPayment:", err);
        return { transactionState: 'ERROR', error: { intent: 'PAYMENT_AUTHORIZATION', message: err.message }};
    }
}