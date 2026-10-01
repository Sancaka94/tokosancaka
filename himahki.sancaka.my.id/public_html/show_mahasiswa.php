<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();

// Cek apakah admin sudah login
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit;
}

require 'koneksi.php';

// ==========================================
// BLOK API / AJAX HANDLER
// ==========================================
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    $action =$_GET['action'];

   // READ Data - Riwayat Transaksi Mahasiswa (LEFT JOIN + GROUP BY)
    if ($action == 'read_laporan') {
        // Query dimodifikasi dengan GROUP BY agar nama & semester yang sama digabung jadi 1 baris
        // Jika mahasiswa membayar lebih dari 1 kali, nominalnya akan dijumlahkan otomatis (SUM)
        $sql = "SELECT 
                    MAX(m.id) AS id_mahasiswa, 
                    m.nama_mahasiswa, 
                    m.semester, 
                    MAX(t.transaksi_id) AS transaksi_id, 
                    MAX(t.tanggal_setor) AS tanggal_setor, 
                    SUM(t.nominal) AS nominal, 
                    MAX(t.keterangan) AS keterangan 
                FROM mahasiswa m 
                LEFT JOIN transaksi t ON m.id = t.mahasiswa_id 
                GROUP BY m.nama_mahasiswa, m.semester
                ORDER BY m.nama_mahasiswa ASC";
                
        $result = $conn->query($sql);
        $data = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
        }
        echo json_encode($data);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Transaksi Mahasiswa - Admin Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; background-color: #fafafa; }
        
        /* CSS Khusus untuk Cetak PDF (Print) */
        @media print {
            @page { margin: 1cm; size: A4 portrait; }
            body { background-color: white !important; color: black !important; }
            
            /* Sembunyikan elemen web yang tidak perlu di kertas */
            .no-print, header { display: none !important; }
            
            /* Tampilkan header cetak yang berisi Logo */
            #printHeader { display: flex !important; }
            
            /* Perbaiki tampilan tabel di kertas */
            .shadow-sm { box-shadow: none !important; }
            .border { border: none !important; }
            .bg-white { background-color: transparent !important; }
            .overflow-x-auto { overflow: visible !important; }
            
            table { width: 100% !important; border-collapse: collapse !important; margin-top: 20px; }
            th, td { 
                border: 1px solid #000 !important; 
                padding: 10px !important; 
                color: black !important; 
                font-size: 12px !important; 
            }
            th { background-color: #f3f4f6 !important; font-weight: bold !important; -webkit-print-color-adjust: exact; }
            
            /* Ubah badge status menjadi teks biasa saat diprint agar lebih rapi */
            .status-badge { background: none !important; color: black !important; padding: 0 !important; font-weight: bold; border: none !important; }
        }
    </style>
</head>
<body class="text-gray-800 antialiased p-4 md:p-8">

    <!-- Header Cetak Khusus (Hidden di web, muncul saat print PDF) -->
    <div id="printHeader" class="hidden flex-col items-center justify-center border-b-4 border-black pb-4 mb-6">
        <div class="flex items-center justify-center gap-6 w-full">
            <img src="https://iaingawi.ac.id/assets/logo-iai.png" alt="Logo IAI" style="height: 90px; width: auto;">
            <div class="text-center">
                <h1 class="text-2xl font-bold uppercase tracking-wider text-black">Institut Agama Islam Ngawi</h1>
                <h2 class="text-lg font-semibold text-black">Himpunan Mahasiswa (HIMA)</h2>
                <p class="text-sm text-black">Jl. Raya Ngawi - Solo Km. 09, Ngawi, Jawa Timur</p>
            </div>
        </div>
        <h3 class="text-xl font-bold text-black mt-6 uppercase underline">Laporan Riwayat Transaksi Mahasiswa</h3>
    </div>

    <div class="max-w-6xl mx-auto">
        <header class="mb-6 flex justify-between items-center pb-4 border-b border-gray-200 no-print">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight text-gray-900">Laporan Transaksi</h1>
                <p class="text-sm text-gray-500">Cek status pembayaran dan riwayat transaksi mahasiswa</p>
            </div>
            <a href="admin.php" class="border border-gray-300 bg-white hover:bg-gray-50 text-gray-700 px-4 py-2 rounded-md text-sm font-medium transition-colors flex items-center gap-2 shadow-sm">
                <i class="ph ph-arrow-left text-lg"></i> Kembali
            </a>
        </header>

        <div>
            <!-- Toolbar Tombol Aksi -->
            <div class="flex justify-end gap-3 mb-4 no-print">
                <button onclick="window.print()" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-md text-sm font-medium transition-colors flex items-center gap-2 shadow-sm">
                    <i class="ph ph-printer text-lg"></i> Cetak PDF Laporan
                </button>
            </div>

            <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse" id="tabelTransaksi">
                        <thead>
                            <tr class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider border-b border-gray-200">
                                <th class="px-5 py-3 font-medium w-12 text-center border-r border-gray-100">No</th>
                                <th class="px-5 py-3 font-medium border-r border-gray-100">Nama Mahasiswa</th>
                                <th class="px-5 py-3 font-medium text-center w-24 border-r border-gray-100">Smt</th>
                                <th class="px-5 py-3 font-medium text-center border-r border-gray-100">Status</th>
                                <th class="px-5 py-3 font-medium border-r border-gray-100">ID Transaksi</th>
                                <th class="px-5 py-3 font-medium text-right border-r border-gray-100">Nominal (Rp)</th>
                                <th class="px-5 py-3 font-medium border-r border-gray-100">Keterangan</th>
                                <th class="px-5 py-3 font-medium text-center">Tanggal</th>
                            </tr>
                        </thead>
                        <tbody id="tableBody" class="text-sm divide-y divide-gray-100">
                            <tr>
                                <td colspan="8" class="text-center py-6 text-gray-400">Memuat data...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', loadData);

        function formatRupiah(angka) {
            return new Intl.NumberFormat('id-ID').format(angka);
        }

        function formatDate(dateString) {
            if (!dateString) return '-';
            const options = { day: 'numeric', month: 'short', year: 'numeric' };
            return new Date(dateString).toLocaleDateString('id-ID', options);
        }

        // 1. Fungsi READ Data Laporan
        function loadData() {
            fetch('?action=read_laporan') // Memanggil ke file ini sendiri
                .then(response => response.json())
                .then(data => {
                    const tableBody = document.getElementById('tableBody');
                    tableBody.innerHTML = '';
                    
                    if (data.length === 0) {
                        tableBody.innerHTML = `<tr><td colspan="8" class="text-center py-6 text-gray-500">Belum ada data mahasiswa.</td></tr>`;
                        return;
                    }

                    data.forEach((row, index) => {
                        let tr = document.createElement('tr');
                        tr.className = "hover:bg-gray-50 transition-colors";
                        
                        // Cek apakah mahasiswa sudah melakukan transaksi (nominal tidak null)
                        let isPaid = row.nominal !== null;
                        
                        // Render Badge Status
                        let statusHtml = isPaid 
                            ? `<span class="status-badge px-2 py-1 bg-green-100 text-green-700 rounded-md text-xs font-semibold border border-green-200">Sudah Transaksi</span>` 
                            : `<span class="status-badge px-2 py-1 bg-red-100 text-red-700 rounded-md text-xs font-semibold border border-red-200">Belum Transaksi</span>`;

                        let idTrx = isPaid ? row.transaksi_id : '-';
                        let nominal = isPaid ? formatRupiah(row.nominal) : '-';
                        let keterangan = isPaid ? row.keterangan : '-';
                        let tanggal = isPaid ? formatDate(row.tanggal_setor) : '-';

                        tr.innerHTML = `
                            <td class="px-5 py-3 text-center text-gray-500 border-r border-gray-100">${index + 1}</td>
                            <td class="px-5 py-3 font-medium text-gray-900 border-r border-gray-100">${row.nama_mahasiswa}</td>
                            <td class="px-5 py-3 text-center text-gray-600 border-r border-gray-100">${row.semester}</td>
                            <td class="px-5 py-3 text-center border-r border-gray-100">${statusHtml}</td>
                            <td class="px-5 py-3 text-gray-600 font-mono text-xs border-r border-gray-100">${idTrx}</td>
                            <td class="px-5 py-3 text-right text-gray-700 font-medium border-r border-gray-100">${nominal}</td>
                            <td class="px-5 py-3 text-gray-600 border-r border-gray-100">${keterangan}</td>
                            <td class="px-5 py-3 text-center text-gray-500">${tanggal}</td>
                        `;
                        tableBody.appendChild(tr);
                    });
                })
                .catch(error => {
                    document.getElementById('tableBody').innerHTML = `<tr><td colspan="8" class="text-center py-6 text-red-500">Gagal memuat data. Periksa koneksi atau query.</td></tr>`;
                });
        }
    </script>
</body>
</html>