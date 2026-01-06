
    // Menambahkan style untuk transisi submenu
    const style = document.createElement('style');
    style.textContent = `.submenu { max-height: 0; overflow: hidden; transition: max-height 0.3s ease-out; } .rotate-180 { transform: rotate(180deg); }`;
    document.head.appendChild(style);

    // Memastikan fungsi toggleMenu hanya didefinisikan sekali
    if (typeof window.toggleMenu !== 'function') {
        window.toggleMenu = function(menuId) {
            const menu = document.getElementById(menuId);
            const arrow = document.getElementById('arrow-' + menuId);
            
            if (menu) {
                // Buka atau tutup menu dengan mengubah max-height
                if (menu.style.maxHeight) {
                    menu.style.maxHeight = null;
                } else {
                    menu.style.maxHeight = menu.scrollHeight + "px";
                }
            }
            
            // Putar ikon panah
            if (arrow) {
                arrow.classList.toggle('rotate-180');
            }
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        const badges = {
            persetujuan: document.getElementById('persetujuan-badge'),
            pesanan: document.getElementById('pesanan-badge'),
            spx: document.getElementById('spx-badge'),
            riwayatScan: document.getElementById('riwayat-scan-badge'),
            saldoRequests: document.getElementById('saldo-requests-badge'), 
            parentPengguna: document.getElementById('menu-pengguna-badge'),
            parentMarketplace: document.getElementById('menu-marketplace-badge'),
            parentPesanan: document.getElementById('menu-pesanan-badge'),
            parentKeuangan: document.getElementById('menu-keuangan-badge'), 
        };

        async function fetchCount(url, badgeElement) {
            // Jika elemen badge tidak ada, hentikan fungsi
            if (!badgeElement) return 0;
            try {
                const response = await fetch(url);
                if (!response.ok) { throw new Error(`Network response was not ok for ${url}`); }
                const data = await response.json();
                updateBadge(badgeElement, data.count);
                return data.count;
            } catch (error) {
                console.error(`Gagal mengambil notifikasi dari ${url}:`, error);
                updateBadge(badgeElement, 0);
                return 0;
            }
        }

        function updateBadge(badgeElement, count) {
            if (badgeElement) {
                badgeElement.textContent = count;
                // Sembunyikan badge jika jumlahnya 0
                if (count > 0) {
                    badgeElement.classList.remove('hidden');
                } else {
                    badgeElement.classList.add('hidden');
                }
            }
        }

        async function fetchAllCounts() {
            const [persetujuanCount, pesananCount, spxCount, riwayatScanCount, saldoRequestsCount] = await Promise.all([
                fetchCount("{{ route('admin.notifications.registrations.count') }}", badges.persetujuan),
                fetchCount("{{ route('admin.notifications.pesanan.count') }}", badges.pesanan),
                fetchCount("{{ route('admin.notifications.spx-scans.count') }}", badges.spx),
                fetchCount("{{ route('admin.notifications.riwayat-scan.count') }}", badges.riwayatScan),
                fetchCount("{{ route('admin.notifications.saldo-requests.count') }}", badges.saldoRequests) 
            ]);

            // Update badge pada menu induk
            updateBadge(badges.parentPengguna, persetujuanCount);
            updateBadge(badges.parentMarketplace, spxCount);
            updateBadge(badges.parentPesanan, pesananCount + riwayatScanCount);
            updateBadge(badges.parentKeuangan, saldoRequestsCount);
        }

        // Jalankan saat halaman dimuat dan setiap 15 detik
        fetchAllCounts();
        setInterval(fetchAllCounts, 15000);
    });
