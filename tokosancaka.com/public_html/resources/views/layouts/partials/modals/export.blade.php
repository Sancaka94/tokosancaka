<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contoh Modal Lengkap</title>

    <!-- Google Fonts: Poppins (Opsional, untuk tampilan yang lebih baik) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- CSS untuk Modal dan Halaman -->
    <style>
        /* === Gaya Dasar Halaman === */
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f0f2f5;
            color: #333;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .main-content {
            text-align: center;
            padding: 2rem;
        }

        .open-modal-button {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 12px 24px;
            font-size: 1rem;
            font-weight: 600;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .open-modal-button:hover {
            background-color: #0056b3;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 123, 255, 0.3);
        }
        
        /*
         * CSS Kustom untuk Komponen Modal
         */

        /* Kontainer utama modal (overlay) */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.6); /* Latar belakang gelap transparan */
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            z-index: 50; /* Pastikan di atas konten lain */
            opacity: 0; /* Awalnya transparan */
            visibility: hidden; /* Awalnya tersembunyi */
            transition: opacity 0.3s ease, visibility 0.3s ease;
        }

        /* Status aktif untuk overlay */
        .modal-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        /* Konten modal (kotak putih di tengah) */
        .modal-content {
            background-color: #ffffff;
            border-radius: 0.75rem; /* Sudut lebih bulat */
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 500px; /* Lebar maksimum modal */
            transform: scale(0.95); /* Efek zoom saat muncul */
            transition: transform 0.3s ease;
        }

        /* Efek zoom saat modal aktif */
        .modal-overlay.active .modal-content {
            transform: scale(1);
        }

        /* Header Modal */
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #e5e7eb;
        }

        .modal-header h3 {
            font-size: 1.25rem;
            font-weight: 600;
            color: #111827;
            margin: 0;
        }

        .modal-header .close-button {
            background: none;
            border: none;
            cursor: pointer;
            color: #9ca3af;
            padding: 0.5rem;
            line-height: 1;
        }

        .modal-header .close-button:hover {
            color: #111827;
        }

        /* Body Modal */
        .modal-body {
            padding: 1.5rem;
            color: #4b5563;
        }

        /* Footer Modal */
        .modal-footer {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem 1.5rem;
            border-top: 1px solid #e5e7eb;
            background-color: #f9fafb;
            border-bottom-left-radius: 0.75rem;
            border-bottom-right-radius: 0.75rem;
        }

        .modal-footer .btn {
            border: none;
            padding: 10px 20px;
            font-size: 0.9rem;
            font-weight: 600;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .modal-footer .btn-secondary {
            background-color: #e5e7eb;
            color: #374151;
        }
        .modal-footer .btn-secondary:hover {
            background-color: #d1d5db;
        }
    </style>
</head>
<body>

    <!-- Konten Halaman Utama -->
    <div class="main-content"></div>

    <!-- Struktur HTML untuk Modal -->
    <div id="exportModal" class="modal-overlay">
        <div class="modal-content">
            <!-- Header -->
            <div class="modal-header">
                <h3>Download Rekap Data Pesanan</h3>
                <button type="button" class="close-button" data-modal-hide="exportModal" aria-label="Tutup modal">
                    <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <!-- Body -->
            <div class="modal-body">
                <p>Pastikan Download File Sesuai Kebutuhan Anda</p>
                <p>Klik Salah Satu Tombol PDF atau Excell</p>
                
            </div>
            <!-- Footer -->
            <div class="modal-footer">
                <button type="button" onclick="closeModal('exportModal')" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times fa-lg"></i>
            </button>
            
                <a href="{{ $excel_route ?? '#' }}" class="flex-1 text-center bg-green-600 text-white p-3 rounded-lg hover:bg-green-700 transition-colors">
                    <i class="fas fa-file-excel me-2"></i>Export Excel
                </a>
                <a href="{{ $pdf_route ?? '#' }}" class="flex-1 text-center bg-red-600 text-white p-3 rounded-lg hover:bg-red-700 transition-colors">
                    <i class="fas fa-file-pdf me-2"></i>Export PDF
                </a>
                <button type="button" class="flex-1 text-center bg-blue-600 text-white p-3 rounded-lg hover:bg-blue-700 transition-colors" data-modal-hide="exportModal">Kembali</button>
            </div>
        </div>
    </div>

    <!-- JavaScript untuk Fungsionalitas Modal -->
    <script>
        // Menunggu semua konten halaman dimuat sebelum menjalankan script
        document.addEventListener('DOMContentLoaded', function() {

            // Fungsi untuk membuka modal berdasarkan ID-nya
            function openModal(modalId) {
                const modal = document.getElementById(modalId);
                if (modal) {
                    modal.classList.add('active');
                }
            }

            // Fungsi untuk menutup modal berdasarkan ID-nya
            function closeModal(modalId) {
                const modal = document.getElementById(modalId);
                if (modal) {
                    modal.classList.remove('active');
                }
            }

            // Menambahkan event listener ke semua elemen yang bisa membuka modal
            // Cari semua elemen dengan atribut 'data-modal-toggle'
            document.querySelectorAll('[data-modal-toggle]').forEach(button => {
                button.addEventListener('click', function() {
                    const modalId = this.getAttribute('data-modal-toggle');
                    openModal(modalId);
                });
            });

            // Menambahkan event listener ke semua elemen yang bisa menutup modal
            // Cari semua elemen dengan atribut 'data-modal-hide'
            document.querySelectorAll('[data-modal-hide]').forEach(button => {
                button.addEventListener('click', function() {
                    const modalId = this.getAttribute('data-modal-hide');
                    closeModal(modalId);
                });
            });

            // Menambahkan event listener untuk menutup modal saat area overlay (luar) diklik
            document.querySelectorAll('.modal-overlay').forEach(overlay => {
                overlay.addEventListener('click', function(event) {
                    // Pastikan yang diklik adalah overlay, bukan konten di dalamnya
                    if (event.target === overlay) {
                        closeModal(this.id);
                    }
                });
            });

            // Membuat fungsi openModal dan closeModal bisa diakses secara global
            // agar bisa dipanggil dari atribut onclick="" di HTML jika diperlukan
            window.openModal = openModal;
            window.closeModal = closeModal;

        });
    </script>
</body>
</html>


