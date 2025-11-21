/**
 * Ini adalah file app.js utama Anda.
 * Baris pertama ini (import './bootstrap') SANGAT PENTING
 * untuk memuat Laravel Echo.
 */
import './bootstrap';

/**
 * =========================================================================
 * Handler Notifikasi Real-time (Untuk header.blade.php)
 * =========================================================================
 *
 * Ini adalah skrip yang menghidupkan notifikasi.
 * 1. Mengambil notifikasi awal (dari Controller) saat halaman dimuat.
 * 2. Mendengarkan notifikasi baru (dari Echo/Reverb) secara real-time.
 * 3. Membangun HTML untuk setiap notifikasi dan menampilkannya.
 */

// Pastikan skrip ini dieksekusi setelah DOM siap
document.addEventListener('DOMContentLoaded', function () {

    // Ambil ID pengguna dari meta tag (pastikan ada di <head> layout Anda)
    // <meta name="user-id" content="{{ Auth::user()->id_pengguna }}">
    
    // --- MODIFIKASI (Sintaks Lebih Aman) ---
    // Mengganti '...?' (optional chaining) agar cPanel tidak error
    const metaTag = document.querySelector("meta[name='user-id']");
    const userId = metaTag ? metaTag.content : null;
    // ----------------------------------------

    // Dapatkan elemen-elemen penting dari HTML
    const notificationBadge = document.getElementById('notification-count-badge');
    const notificationList = document.getElementById('notification-list');
    const emptyState = document.getElementById('notification-empty-state');

    // Jika elemen-elemennya tidak ada, jangan jalankan skrip
    if (!userId || !notificationBadge || !notificationList || !emptyState) {
        console.warn('Elemen notifikasi (badge/list/empty) atau User ID tidak ditemukan. Real-time non-aktif.');
        return;
    }

    /**
     * -------------------------------------------------
     * LANGKAH 1: Ambil Notifikasi Awal (saat load)
     * -------------------------------------------------
     * Memanggil NotifikasiCustomerController@getUnread
     */
    function fetchInitialNotifications() {
        fetch('/notifikasi/get-unread') // Sesuaikan rute ini jika perlu
            .then(response => response.json())
            .then(data => {
                // Update badge count
                updateNotificationBadge(data.unread_count);

                // Kosongkan list
                notificationList.innerHTML = ''; 

                if (data.notifications && data.notifications.length > 0) {
                    // Loop notifikasi (dari DB) dan tambahkan ke list
                    data.notifications.forEach(notif => {
                        // Data notifikasi dari DB ada di notif.data
                        // notif.id adalah ID unik dari tabel notifications
                        addNotificationToDropdown(notif.data, notif.id);
                    });
                } else {
                    // Tampilkan pesan "Tidak ada notifikasi"
                    emptyState.style.display = 'block';
                }
            })
            .catch(error => console.error('Error fetching unread notifications:', error));
    }

    /**
     * -------------------------------------------------
     * LANGKAH 2: Dengarkan Notifikasi Baru (Real-time)
     * -------------------------------------------------
     */
    if (window.Echo) {
        window.Echo.private(`App.Models.User.${userId}`)
            .notification((notification) => {
                
                console.log("NOTIFIKASI REAL-TIME DITERIMA:", notification);

                const data = notification.data; // Data lengkap (judul, pesan, produk, dll)
                const unreadCount = notification.unread_count; // Count terbaru

                // 1. Update angka di badge lonceng
                updateNotificationBadge(unreadCount);

                // 2. Tambahkan notifikasi baru ke dropdown
                // (Kita belum punya 'id' unik dari DB, jadi kirim null)
                addNotificationToDropdown(data, null);

                // 3. Tampilkan "Toast" pop-up
                showToastNotification(data);
            });
    } else {
        console.error('Laravel Echo tidak terdefinisi. Real-time notifikasi GAGAL.');
    }

    /**
     * -------------------------------------------------
     * FUNGSI HELPER
     * -------------------------------------------------
     */

    /**
     * Memperbarui badge angka di ikon lonceng
     */
    function updateNotificationBadge(count) {
        if (!notificationBadge) return;
        
        const countNum = parseInt(count, 10);
        if (countNum > 0) {
            notificationBadge.innerText = countNum > 9 ? '9+' : countNum;
            notificationBadge.style.display = 'flex'; // 'flex' (sesuai HTML Anda)
        } else {
            notificationBadge.style.display = 'none';
        }
    }

    /**
     * Menambahkan notifikasi baru ke HTML dropdown
     * @param {object} data - Objek data notifikasi (dari NotifikasiUmum)
     * @param {string|null} notificationId - ID unik dari tabel notifications (jika ada)
     */
    function addNotificationToDropdown(data, notificationId) {
        if (!notificationList || !emptyState) return;

        // Sembunyikan pesan "Tidak ada notifikasi"
        emptyState.style.display = 'none';

        // Tentukan URL. Jika 'lacak pembeli' ada, gunakan itu
        let url = data.url || '#';
        let target = ''; // Buka di tab yang sama

        // KHUSUS UNTUK ADMIN/SELLER: Lacak Pembeli
        if (data.lat_pembeli && data.lng_pembeli) {
            url = `https://maps.google.com/?q=${data.lat_pembeli},${data.lng_pembeli}`;
            target = '_blank'; // Buka Google Maps di tab baru
        }
        
        // Buat HTML (meniru struktur Blade @forelse Anda)
        // Kita juga tambahkan data-id jika kita memilikinya
        const notifHtml = `
            <a class="flex items-start w-full px-4 py-3 text-sm text-gray-700 rounded-md hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700" 
               href="${url}" 
               target="${target}"
               ${notificationId ? `data-notif-id="${notificationId}"` : ''}>
                
                <div class="mr-3 pt-1">
                    <div class="p-2 bg-blue-100 dark:bg-blue-900/50 rounded-full">
                        <i class="${data.icon || 'fas fa-info-circle'} text-blue-500"></i>
                    </div>
                </div>
                <div>
                    <p class="font-semibold">${data.judul || 'Notifikasi Baru'}</p>
                    <p class="text-xs text-gray-500">${data.pesan_utama || 'Anda memiliki pembaruan baru.'}</p>
                    
                    <!-- Data Tambahan yang Lengkap -->
                    ${data.produk_list ? `<p class="text-xs text-gray-600 dark:text-gray-300 mt-1">Produk: ${data.produk_list}</p>` : ''}
                    ${data.nama_pembeli ? `<p class="text-xs text-gray-500">Pembeli: ${data.nama_pembeli}</p>` : ''}
                    
                    <p class="text-xs text-gray-400 mt-1">${data.waktu_masuk_human || 'Baru saja'}</p>
                </div>
            </a>
        `;

        // Tambahkan notifikasi baru di paling atas
        notificationList.insertAdjacentHTML('afterbegin', notifHtml);
    }

    /**
     * (Opsional) Menampilkan toast/pop-up
     * (Membutuhkan library seperti Toastify, SweetAlert2, atau Bootstrap Toast)
     */
    function showToastNotification(data) {
        // Ganti ini dengan library toast favorit Anda
        console.log(`[TOAST] ${data.judul}: ${data.pesan_utama}`);
        
        // Contoh SANGAT SEDERHANA jika Anda punya SweetAlert2
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'info',
                title: data.judul,
                text: data.pesan_utama,
                showConfirmButton: false,
                timer: 5000,
                timerProgressBar: true
            });
        }
    }

    // Jalankan pengambilan data awal
    fetchInitialNotifications();
});