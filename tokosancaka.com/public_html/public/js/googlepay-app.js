// public/js/googlepay-app.js

const csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');
const csrfToken = csrfTokenMeta ? csrfTokenMeta.getAttribute('content') : '';

const baseRequest = {
    apiVersion: 2,
    apiVersionMinor: 0,
};

let paymentsClient = null;
let allowedPaymentMethods = null;
let merchantInfo = null;

async function getGooglePayConfig() {
    if (allowedPaymentMethods == null || merchantInfo == null) {
        console.log("LOG LOG: Mengambil konfigurasi Google Pay dari PayPal SDK");
        try {
            const googlePayConfig = await paypal.Googlepay().config();
            allowedPaymentMethods = googlePayConfig.allowedPaymentMethods;
            merchantInfo = googlePayConfig.merchantInfo;
        } catch (error) {
            console.error("LOG LOG: Gagal mengambil konfigurasi Google Pay dari PayPal:", error);
            throw error;
        }
    }
    return { allowedPaymentMethods, merchantInfo };
}

function getGooglePaymentsClient() {
    if (paymentsClient === null) {
        console.log("LOG LOG: Inisialisasi Google PaymentsClient");
        paymentsClient = new google.payments.api.PaymentsClient({
            environment: "TEST", // Ubah ke "PRODUCTION" jika sudah live di dashboard / database
            paymentDataCallbacks: {
                onPaymentAuthorized: onPaymentAuthorized,
            },
        });
    }
    return paymentsClient;
}

async function onGooglePayLoaded() {
    console.log("LOG LOG: SDK Google Pay berhasil dimuat ke dalam window");
    try {
        if (!window.google || !window.paypal || !window.paypal.Googlepay) {
            console.error("LOG LOG: SDK PayPal atau Google Pay belum siap atau tidak terdeteksi di global window");
            return;
        }

        const client = getGooglePaymentsClient();
        const { allowedPaymentMethods } = await getGooglePayConfig();

        const isReadyToPayRequest = Object.assign({}, baseRequest, {
            allowedPaymentMethods: allowedPaymentMethods,
        });

        const response = await client.isReadyToPay(isReadyToPayRequest);
        if (response.result) {
            console.log("LOG LOG: Browser/Device mendukung Google Pay. Merender tombol ke DOM...");
            addGooglePayButton();
        } else {
            console.warn("LOG LOG: Google Pay tidak didukung pada browser/device saat ini.");
        }
    } catch (err) {
        console.error("LOG LOG: Terjadi error pada fungsi onGooglePayLoaded:", err);
    }
}

function addGooglePayButton() {
    const client = getGooglePaymentsClient();
    const button = client.createButton({
        onClick: onGooglePaymentButtonClicked,
        allowedPaymentMethods: allowedPaymentMethods
    });
    const container = document.getElementById("google-pay-button-container");
    if (container) {
        container.innerHTML = ""; 
        container.appendChild(button);
        console.log("LOG LOG: Tombol Google Pay berhasil ditempelkan ke kontainer");
    } else {
        console.error("LOG LOG: Elemen kontainer '#google-pay-button-container' tidak ditemukan di DOM");
    }
}

function getGoogleTransactionInfo() {
    return {
        countryCode: "US", 
        currencyCode: "USD",
        totalPriceStatus: "FINAL",
        totalPrice: "100.00", 
    };
}

async function onGooglePaymentButtonClicked() {
    console.log("LOG LOG: Event click tombol Google Pay terdeteksi");
    try {
        const paymentDataRequest = Object.assign({}, baseRequest);
        const { allowedPaymentMethods, merchantInfo } = await getGooglePayConfig();
        
        paymentDataRequest.allowedPaymentMethods = allowedPaymentMethods;
        paymentDataRequest.transactionInfo = getGoogleTransactionInfo();
        paymentDataRequest.merchantInfo = merchantInfo;
        paymentDataRequest.callbackIntents = ["PAYMENT_AUTHORIZATION"];

        const client = getGooglePaymentsClient();
        console.log("LOG LOG: Membuka Google Pay payment sheet/popup sheet");
        client.loadPaymentData(paymentDataRequest);
    } catch (error) {
        console.error("LOG LOG: Error saat memuat data sheet Google Pay:", error);
    }
}

function onPaymentAuthorized(paymentData) {
    console.log("LOG LOG: Otorisasi Google Pay berhasil di sisi client. Memulai pemrosesan transaksi...");
    return new Promise(function (resolve, reject) {
        processPayment(paymentData)
            .then(function (data) {
                if (data && data.transactionState === "SUCCESS") {
                    console.log("LOG LOG: Alur pembayaran Google Pay selesai dengan status BERHASIL");
                    resolve({ transactionState: "SUCCESS" });
                } else {
                    console.error("LOG LOG: Alur pembayaran Google Pay selesai dengan status GAGAL");
                    resolve({ 
                        transactionState: "ERROR",
                        error: {
                            intent: "PAYMENT_AUTHORIZATION",
                            message: data.message || "Transaksi gagal diproses"
                        }
                    });
                }
            })
            .catch(function (err) {
                console.error("LOG LOG: Terjadi pengecualian fatal di dalam Promise onPaymentAuthorized:", err);
                resolve({ 
                    transactionState: "ERROR",
                    error: {
                        intent: "PAYMENT_AUTHORIZATION",
                        message: err.message || "Sistem error"
                    }
                });
            });
    });
}

function createOrder() {
    console.log("LOG LOG: Memanggil route Laravel untuk create order");
    return fetch("/paypal/orders/create", {
        method: "POST",
        headers: { 
            "Content-Type": "application/json",
            "Accept": "application/json",
            "X-CSRF-TOKEN": csrfToken 
        },
        body: JSON.stringify({}),
    })
    .then(response => {
        if (!response.ok) throw new Error("Gagal menghubungi server pembuatan order");
        return response.json();
    })
    .then(data => {
        return { orderId: data.id }; 
    });
}

async function processPayment(paymentData) {
    try {
        if (!csrfToken) {
            throw new Error("CSRF Token Laravel tidak ditemukan. Pastikan meta tag meta[name='csrf-token'] tersedia.");
        }

        console.log("LOG LOG: Mengirim request pembuatan Order ke Backend Laravel");
        const orderData = await createOrder();
        const orderId = orderData.orderId;

        console.log("LOG LOG: Order ID berhasil dibuat di server Laravel:", orderId);

        console.log("LOG LOG: Mengirim token enkripsi Google Pay ke PayPal SDK via confirmOrder");
        const confirmResponse = await paypal.Googlepay().confirmOrder({
            orderId: orderId,
            paymentMethodData: paymentData.paymentMethodData,
        });

        console.log("LOG LOG: Respon confirmOrder diterima dengan status:", confirmResponse.status);

        if (confirmResponse.status === "PAYER_ACTION_REQUIRED") {
            console.log("LOG LOG: Status PAYER_ACTION_REQUIRED terdeteksi. Meluncurkan modul initiatePayerAction (3DS)...");
            await paypal.Googlepay().initiatePayerAction({ orderId: orderId });
            console.log("LOG LOG: Payer action/3DS kontingensi selesai divalidasi.");
        } else if (confirmResponse.status !== "APPROVED") {
            throw new Error(`Status otorisasi PayPal tidak disetujui. Status saat ini: ${confirmResponse.status}`);
        }

        console.log("LOG LOG: Order di-approve, memanggil API Laravel untuk Capture");
        const captureResponse = await fetch(`/paypal/orders/${orderId}/capture`, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "Accept": "application/json",
                "X-CSRF-TOKEN": csrfToken
            }
        });

        if (!captureResponse.ok) {
            throw new Error(`HTTP Error saat capture order: ${captureResponse.status}`);
        }

        const captureData = await captureResponse.json();
        console.log("LOG LOG: Respon capture data dari Laravel:", captureData);

        if (captureData && captureData.status === "COMPLETED") {
            console.log("LOG LOG: Capture tuntas, dana sukses ditarik.");
            return { transactionState: "SUCCESS" };
        } else {
            return { 
                transactionState: "ERROR", 
                message: `Capture gagal dengan status: ${captureData.status || 'UNKNOWN'}` 
            };
        }

    } catch (error) {
        console.error("LOG LOG: Kegagalan dalam fungsi internal processPayment:", error);
        return { 
            transactionState: "ERROR", 
            message: error.message 
        };
    }
}