// public/js/googlepay-app.js

console.log("LOG LOG: File googlepay-app.js berhasil dieksekusi browser!");

const csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');
const csrfToken = csrfTokenMeta ? csrfTokenMeta.getAttribute('content') : '';

let sdkInstance = null;
let googlePaySession = null;
let paymentsClient = null;
let rawGooglePayConfig = null;

const baseRequest = {
    apiVersion: 2,
    apiVersionMinor: 0,
};

// ==========================================
// INISIALISASI UTAMA (DIPANGGIL OTOMATIS)
// ==========================================
async function initPaymentGateway() {
    console.log("LOG LOG: Memulai inisialisasi SDK PayPal v6 dinamis...");

    try {
        if (!window.paypal || !window.paypal.createInstance) {
            console.error("LOG LOG: Objek global SDK PayPal v6 gagal dideteksi. Pastikan koneksi internet lancar.");
            return;
        }

        // Inisialisasi Instance v6 secara dinamis menggunakan database Client ID
        sdkInstance = await window.paypal.createInstance({
            clientId: window.AppConfig.paypalClientId,
            components: ["paypal-payments", "googlepay-payments"],
            pageType: "checkout",
            locale: "id-ID",
        });

        // Pengecekan ketersediaan metode pembayaran berdasarkan mata uang dinamis
        const paymentMethods = await sdkInstance.findEligibleMethods({ 
            currencyCode: window.AppConfig.currency 
        });

        // Setup: Tombol Standar PayPal
        if (paymentMethods.isEligible("paypal")) {
            console.log("LOG LOG: Metode PayPal Standar eligible.");
            setupPayPalStandardButton();
        } else {
            console.warn("LOG LOG: Tombol PayPal disembunyikan (Tidak Eligible).");
        }

        // Setup: Tombol Google Pay
        if (paymentMethods.isEligible("googlepay")) {
            console.log("LOG LOG: Metode Google Pay eligible. Merender tombol...");
            
            const googlePayDetails = paymentMethods.getDetails("googlepay");
            googlePaySession = sdkInstance.createGooglePayOneTimePaymentSession();
            rawGooglePayConfig = googlePaySession.formatConfigForPaymentRequest(googlePayDetails.config);
            
            setupGooglePayButton();
        } else {
            console.warn("LOG LOG: Google Pay tidak didukung pada browser/perangkat atau region akun PayPal saat ini.");
        }

    } catch (err) {
        console.error("LOG LOG: Kesalahan fatal pada blok inisialisasi:", err);
    }
}

// Jalankan fungsi inisialisasi secara langsung!
initPaymentGateway();

/** ==========================================
 * FUNGSI LOGIKA: PAYPAL STANDARD
 * ========================================== */
function setupPayPalStandardButton() {
    const standardOptions = {
        async onApprove(data) {
            console.log("LOG LOG: Tombol PayPal Standar disetujui user. Order ID:", data.orderId);
            try {
                const response = await fetch(`/paypal/orders/${data.orderId}/capture`, {
                    method: "POST",
                    headers: { "Content-Type": "application/json", "Accept": "application/json", "X-CSRF-TOKEN": csrfToken }
                });
                const captureData = await response.json();
                if (captureData && captureData.status === "COMPLETED") {
                    console.log("LOG LOG: Capture PayPal standar sukses.");
                    alert("Pembayaran PayPal Berhasil!");
                    window.location.href = "/pembayaran/sukses-tripay?reference=" + data.orderId + "&jenis=paypal";
                }
            } catch (error) {
                console.error("LOG LOG: Gagal capture order PayPal standar:", error);
            }
        }
    };

    const standardSession = sdkInstance.createPayPalOneTimePaymentSession(standardOptions);
    const btnElement = document.getElementById("standard-paypal-btn");
    
    if (btnElement) {
        btnElement.removeAttribute("hidden");
        btnElement.addEventListener("click", async () => {
            try {
                await standardSession.start({ presentationMode: "auto" }, createOrder());
            } catch (err) {
                console.error("LOG LOG: Gagal meluncurkan popup PayPal:", err);
            }
        });
    }
}

/** ==========================================
 * FUNGSI LOGIKA: GOOGLE PAY
 * ========================================== */
function getGooglePaymentsClient() {
    if (paymentsClient === null) {
        paymentsClient = new google.payments.api.PaymentsClient({
            environment: window.AppConfig.googlePayEnv,
            paymentDataCallbacks: {
                onPaymentAuthorized: onPaymentAuthorized,
            },
        });
        console.log("LOG LOG: Google PaymentsClient diatur ke environment:", window.AppConfig.googlePayEnv);
    }
    return paymentsClient;
}

function setupGooglePayButton() {
    const client = getGooglePaymentsClient();
    
    const isReadyToPayRequest = Object.assign({}, baseRequest, {
        allowedPaymentMethods: rawGooglePayConfig.allowedPaymentMethods,
    });

    client.isReadyToPay(isReadyToPayRequest).then(response => {
        if (response.result) {
            const button = client.createButton({
                onClick: onGooglePaymentButtonClicked,
                allowedPaymentMethods: rawGooglePayConfig.allowedPaymentMethods
            });
            document.getElementById("google-pay-button-container").appendChild(button);
            console.log("LOG LOG: Tombol Google Pay berhasil ditampilkan di HTML.");
        } else {
            console.error("LOG LOG: isReadyToPay merespon false. Google Pay diblokir oleh browser.");
        }
    }).catch(err => {
        console.error("LOG LOG: Error pada fungsi isReadyToPay Google Pay:", err);
    });
}

async function onGooglePaymentButtonClicked() {
    console.log("LOG LOG: Proses penekanan tombol Google Pay");
    const paymentDataRequest = Object.assign({}, baseRequest);
    
    paymentDataRequest.allowedPaymentMethods = rawGooglePayConfig.allowedPaymentMethods;
    paymentDataRequest.merchantInfo = rawGooglePayConfig.merchantInfo;
    paymentDataRequest.callbackIntents = ["PAYMENT_AUTHORIZATION"];
    
    paymentDataRequest.transactionInfo = {
        countryCode: window.AppConfig.countryCode, 
        currencyCode: window.AppConfig.currency,
        totalPriceStatus: "FINAL",
        totalPrice: window.AppConfig.amount, 
    };

    const client = getGooglePaymentsClient();
    client.loadPaymentData(paymentDataRequest);
}

function onPaymentAuthorized(paymentData) {
    console.log("LOG LOG: Callback otorisasi Google Pay disetujui oleh user.");
    return new Promise(function (resolve) {
        processGooglePay(paymentData)
            .then(res => resolve(res))
            .catch(err => resolve({ transactionState: "ERROR", error: { intent: "PAYMENT_AUTHORIZATION", message: err.message } }));
    });
}

/** ==========================================
 * COMMUNICATOR HANDLER: BACKEND INTERACTION
 * ========================================== */
function createOrder() {
    console.log("LOG LOG: Memanggil Laravel backend untuk membuat kode referensi order baru");
    return fetch("/paypal/orders/create", {
        method: "POST",
        headers: { "Content-Type": "application/json", "Accept": "application/json", "X-CSRF-TOKEN": csrfToken },
        body: JSON.stringify({}),
    }).then(res => res.json()).then(data => ({ orderId: data.id }));
}

async function processGooglePay(paymentData) {
    if (!csrfToken) throw new Error("CSRF Token kosong.");
    
    const orderData = await createOrder();
    const orderId = orderData.orderId;
    console.log("LOG LOG: Order ID backend berhasil didapatkan:", orderId);

    const { status } = await googlePaySession.confirmOrder({
        orderId: orderId,
        paymentMethodData: paymentData.paymentMethodData,
    });

    console.log("LOG LOG: Status konfirmasi sesi Google Pay PayPal:", status);

    if (status === "PAYER_ACTION_REQUIRED") {
        console.log("LOG LOG: Menjalankan tantangan keamanan 3DS bank penerbit...");
        await googlePaySession.initiatePayerAction({ orderId: orderId });
    } else if (status !== "APPROVED") {
        throw new Error(`Otorisasi gagal dengan status: ${status}`);
    }

    const captureResponse = await fetch(`/paypal/orders/${orderId}/capture`, {
        method: "POST",
        headers: { "Content-Type": "application/json", "Accept": "application/json", "X-CSRF-TOKEN": csrfToken }
    });
    const captureData = await captureResponse.json();

    if (captureData && captureData.status === "COMPLETED") {
        console.log("LOG LOG: Sesi transfer sukses secara keseluruhan.");
        alert("Pembayaran Google Pay Berhasil!");
        window.location.href = "/pembayaran/sukses-tripay?reference=" + orderId + "&jenis=googlepay";
        return { transactionState: "SUCCESS" };
    } else {
        throw new Error("Gagal mengeksekusi capture order di database.");
    }
}