// Menggunakan library versi "compat" khusus untuk Service Worker
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

// Menangkap pesan di Background (Saat tab admin ditutup / di-minimize)
messaging.onBackgroundMessage(function(payload) {
    console.log('[firebase-messaging-sw.js] Menerima Notifikasi Background ', payload);

    const notificationTitle = payload.notification ? payload.notification.title : 'Pesanan Baru!';
    const notificationOptions = {
        body: payload.notification ? payload.notification.body : 'Ada pesanan masuk ke sistem Sancaka',
        icon: '/storage/uploads/sancaka.png', // Ganti sesuai path logo Anda jika berbeda
        data: payload.data
    };

    self.registration.showNotification(notificationTitle, notificationOptions);
});

// Aksi ketika Pop-up Windows/Mac diklik
self.addEventListener('notificationclick', function(event) {
    event.notification.close();
    event.waitUntil(
        clients.openWindow('/admin/pesanan-autokirim')
    );
});
