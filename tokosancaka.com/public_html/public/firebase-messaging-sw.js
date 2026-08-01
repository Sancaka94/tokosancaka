importScripts('https://www.gstatic.com/firebasejs/12.17.0/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/12.17.0/firebase-messaging-compat.js');

firebase.initializeApp({
    apiKey: "AIzaSyBd4Rl2pnQlr-mYQSVZamWnCkvpi5anU8w",
    authDomain: "sancaka-express.firebaseapp.com",
    databaseURL: "https://sancaka-express-default-rtdb.asia-southeast1.firebasedatabase.app",
    projectId: "sancaka-express",
    storageBucket: "sancaka-express.firebasestorage.app",
    messagingSenderId: "960582735209",
    appId: "1:960582735209:web:710a898b750150824ad9f8",
    measurementId: "G-Z1V0BHLZ6P"
});

const messaging = firebase.messaging();

messaging.onBackgroundMessage(function(payload) {
    console.log('[firebase-messaging-sw.js] Background message: ', payload);

    const notificationTitle = payload.notification ? payload.notification.title : 'Pesanan Baru!';
    const notificationOptions = {
        body: payload.notification ? payload.notification.body : 'Ada pesanan masuk ke sistem Sancaka',
        icon: 'https://tokosancaka.com/storage/uploads/sancaka.png',
        data: payload.data
    };

    // 1. Munculkan notifikasi bawaan OS (Windows/Mac)
    self.registration.showNotification(notificationTitle, notificationOptions);

    // 2. 🔥 GUNAKAN BROADCAST CHANNEL (LEBIH KUAT DARI POSTMESSAGE) 🔥
    const broadcast = new BroadcastChannel('sancaka_notif_channel');
    broadcast.postMessage(payload);
});

self.addEventListener('notificationclick', function(event) {
    event.notification.close();

    // Ambil link dinamis dari payload data FCM, jika tidak ada fallback ke ojek online
    const targetUrl = (event.notification.data && event.notification.data.link)
        ? event.notification.data.link
        : '/admin/pesanan-ojek/riwayat';

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then(windowClients => {
            // Cek apakah tab admin sudah terbuka, jika ya fokuskan dan arahkan ke link tujuan
            for (let i = 0; i < windowClients.length; i++) {
                const client = windowClients[i];
                if (client.url.includes('/admin') && 'focus' in client) {
                    client.navigate(targetUrl);
                    return client.focus();
                }
            }
            // Jika belum ada tab yang terbuka, buka jendela/tab baru
            if (clients.openWindow) {
                return clients.openWindow(targetUrl);
            }
        })
    );
});
