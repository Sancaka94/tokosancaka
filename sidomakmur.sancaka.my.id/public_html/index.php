<?php
require 'koneksi.php';

$TARGET_DANA = 5000000;

$query_warga = $conn->query("SELECT COUNT(id) as total FROM warga");
$total_warga = $query_warga->fetch_assoc()['total'];

$query_masuk = $conn->query("SELECT SUM(nominal) as total FROM transaksi WHERE jenis='masuk'");
$total_masuk = $query_masuk->fetch_assoc()['total'] ?? 0;

$query_keluar = $conn->query("SELECT SUM(nominal) as total FROM transaksi WHERE jenis='keluar'");
$total_keluar = $query_keluar->fetch_assoc()['total'] ?? 0;

$sisa_kekurangan = $TARGET_DANA - $total_masuk;
if ($sisa_kekurangan < 0) $sisa_kekurangan = 0;

// LOGIK TAMBAHAN: Menghitung Sisa Saldo (Pemasukan - Pengeluaran)
$sisa_saldo = $total_masuk - $total_keluar;

// MENARIK DATA SELURUH TRANSAKSI UNTUK DITAMPILKAN DI MODAL (DIKELOMPOKKAN PER WARGA)
$all_trx = $conn->query("SELECT warga_id, tanggal_setor, nominal FROM transaksi WHERE jenis='masuk' ORDER BY tanggal_setor DESC");
$trx_data = [];
while($row_trx = $all_trx->fetch_assoc()) {
    $trx_data[$row_trx['warga_id']][] = $row_trx;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Keuangan KAS RT22 - sidomakmur.sancaka.my.id</title>
    
    <link rel="icon" type="image/png" href="https://tokosancaka.com/public/assets/ngawi.png" />
    <link rel="shortcut icon" type="image/png" href="https://tokosancaka.com/public/assets/ngawi.png" />
    <link rel="apple-touch-icon" href="https://tokosancaka.com/public/assets/ngawi.png" />

    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-50 text-gray-900 antialiased p-4 md:p-8">

    <div class="max-w-7xl mx-auto">
        <header class="mb-8 flex flex-col md:flex-row md:items-center justify-between border-b border-gray-200 pb-4">
            <div class="flex items-center gap-4">
                <img src="https://tokosancaka.com/public/assets/ngawi.png" alt="Logo Ngawi" class="w-14 h-14 object-contain drop-shadow-sm">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight">Laporan Keuangan KAS Warga RT.022 RW.005</h1>
                    <p class="text-sm text-gray-500">sidomakmur.sancaka.my.id | Proyek Gapura Gang Garuda RT.22 RW.05 Kel.Ketanggi</p>
                </div>
            </div>
            <div class="mt-4 md:mt-0">
                <a href="admin.php" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                    Login Admin
                </a>
            </div>
        </header>

        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-4 mb-8">
            <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm">
                <div class="text-gray-500 text-sm font-medium mb-1">Total Warga</div>
                <div class="text-2xl font-bold"><?= number_format($total_warga, 0, ',', '.') ?> <span class="text-sm font-normal text-gray-400">KK</span></div>
            </div>
            <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm">
                <div class="text-gray-500 text-sm font-medium mb-1">Total Uang Masuk</div>
                <div class="text-2xl font-bold">Rp <?= number_format($total_masuk, 0, ',', '.') ?></div>
            </div>
            <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm">
                <div class="text-gray-500 text-sm font-medium mb-1">Total Pengeluaran</div>
                <div class="text-2xl font-bold">Rp <?= number_format($total_keluar, 0, ',', '.') ?></div>
            </div>
            <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm">
                <div class="text-gray-500 text-sm font-medium mb-1">Kekurangan (Target 5 Jt)</div>
                <div class="text-2xl font-bold text-gray-900">Rp <?= number_format($sisa_kekurangan, 0, ',', '.') ?></div>
            </div>
            <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm">
                <div class="text-gray-500 text-sm font-medium mb-1">Sisa Saldo Kas</div>
                <div class="text-2xl font-bold text-red-700">Rp <?= number_format($sisa_saldo, 0, ',', '.') ?></div>
            </div>
        </div>

        <div class="bg-red-50 border border-red-100 p-5 rounded-xl mb-8 flex flex-col lg:flex-row gap-6 items-start lg:items-center justify-between shadow-sm">
            <div>
                <h3 class="text-red-800 font-semibold text-lg flex items-center gap-2">
                    <i class="ph ph-wallet text-xl"></i> Informasi Pembayaran Iuran
                </h3>
                <p class="text-sm text-red-700 mt-2 leading-relaxed">
                    Silakan transfer iuran Anda melalui salah satu rekening di bawah ini.<br>
                    Harap Konfirmasi Ke Nomor Ini: 
                    <a href="https://wa.me/6285745808809" target="_blank" class="inline-flex items-center gap-1 font-semibold text-green-700 hover:text-green-800 bg-green-100 hover:bg-green-200 px-2 py-1 rounded transition-colors mt-1 lg:mt-0">
                        <i class="ph ph-whatsapp-logo text-base"></i> WA 085745808809
                    </a>
                </p>
            </div>
            
            <div class="flex flex-col sm:flex-row gap-4 w-full lg:w-auto">
                <div class="bg-white p-4 rounded-lg border border-red-100 flex items-center justify-between gap-6 shadow-sm flex-1">
                    <div>
                        <div class="text-xs text-gray-500 font-semibold uppercase tracking-wider mb-1">DANA</div>
                        <div class="font-bold text-gray-900 text-lg tracking-wide">085745808809</div>
                        <div class="text-xs text-gray-600 mt-0.5">a.n AMAL IBNU MUHARRAM</div>
                    </div>
                    <button onclick="copyText('085745808809')" class="p-2.5 bg-red-50 hover:bg-red-100 text-red-600 rounded-md transition-colors" title="Salin Nomor DANA">
                        <i class="ph ph-copy text-xl"></i>
                    </button>
                </div>

                <div class="bg-white p-4 rounded-lg border border-red-100 flex items-center justify-between gap-6 shadow-sm flex-1">
                    <div>
                        <div class="text-xs text-gray-500 font-semibold uppercase tracking-wider mb-1">BCA</div>
                        <div class="font-bold text-gray-900 text-lg tracking-wide">7790319499</div>
                        <div class="text-xs text-gray-600 mt-0.5">a.n AMAL IBNU MUHARRAM</div>
                    </div>
                    <button onclick="copyText('7790319499')" class="p-2.5 bg-red-50 hover:bg-red-100 text-red-600 rounded-md transition-colors" title="Salin Nomor BCA">
                        <i class="ph ph-copy text-xl"></i>
                    </button>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden mb-8">
            <div class="p-5 border-b border-gray-200 bg-gray-50 flex justify-between items-center">
                <h2 class="text-lg font-semibold flex items-center gap-2">
                    <i class="ph ph-users"></i> Laporan Uang Terkumpul Per Warga
                </h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-white text-gray-500 text-xs uppercase tracking-wider border-b border-gray-200">
                            <th class="px-6 py-3 font-medium">No</th>
                            <th class="px-6 py-3 font-medium">Nama Warga</th>
                            <th class="px-6 py-3 font-medium">Alamat</th>
                            <th class="px-6 py-3 font-medium text-right">Total Terkumpul</th>
                            <th class="px-6 py-3 font-medium text-center">Rincian</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm divide-y divide-gray-100">
                        <?php
                        // Menambahkan w.id pada query untuk mapping rincian
                        $query_rekap = $conn->query("
                            SELECT w.id, w.nama, w.alamat, 
                            COALESCE((SELECT SUM(nominal) FROM transaksi WHERE warga_id = w.id AND jenis='masuk'), 0) as total_setor
                            FROM warga w ORDER BY total_setor DESC
                        ");
                        $no = 1;
                        while($row = $query_rekap->fetch_assoc()):
                            $warga_id = $row['id'];
                            // Mengambil data history spesifik warga ini & mengubahnya menjadi JSON agar bisa dibaca Javascript
                            $history_json = isset($trx_data[$warga_id]) ? json_encode($trx_data[$warga_id]) : '[]';
                        ?>
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 text-gray-500"><?= $no++ ?></td>
                            <td class="px-6 py-4 font-medium text-gray-900"><?= $row['nama'] ?></td>
                            <td class="px-6 py-4 text-gray-500"><?= $row['alamat'] ?></td>
                            <td class="px-6 py-4 font-bold text-right">Rp <?= number_format($row['total_setor'], 0, ',', '.') ?></td>
                            <td class="px-6 py-4 text-center">
                                <button onclick="openDetailModal('<?= htmlspecialchars($row['nama'], ENT_QUOTES) ?>', <?= $row['total_setor'] ?>, '<?= htmlspecialchars($history_json, ENT_QUOTES) ?>')" class="bg-teal-50 hover:bg-teal-100 text-teal-600 p-2 rounded-md transition-colors inline-flex items-center justify-center" title="Lihat Rincian Setoran">
                                    <i class="ph ph-list-magnifying-glass text-lg"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden mb-8">
            <div class="p-5 border-b border-gray-200 bg-gray-50 flex justify-between items-center">
                <h2 class="text-lg font-semibold flex items-center gap-2">
                    <i class="ph ph-bank"></i> Mutasi Kas (Laporan Debit & Kredit)
                </h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-white text-gray-500 text-xs uppercase tracking-wider border-b border-gray-200">
                            <th class="px-6 py-3 font-medium">Tanggal</th>
                            <th class="px-6 py-3 font-medium">Keterangan Transaksi</th>
                            <th class="px-6 py-3 font-medium text-right">Debit (Masuk)</th>
                            <th class="px-6 py-3 font-medium text-right">Kredit (Keluar)</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm divide-y divide-gray-100">
                        <?php
                        $query_mutasi = $conn->query("
                            SELECT t.*, w.nama 
                            FROM transaksi t 
                            LEFT JOIN warga w ON t.warga_id = w.id 
                            ORDER BY t.tanggal_setor DESC, t.id DESC
                        ");
                        if($query_mutasi->num_rows > 0):
                            while($mutasi = $query_mutasi->fetch_assoc()):
                                $is_masuk = $mutasi['jenis'] == 'masuk';
                                
                                // Format text Keterangan
                                $teks_keterangan = "";
                                if($is_masuk) {
                                    $teks_keterangan = "Setoran: " . (!empty($mutasi['nama']) ? $mutasi['nama'] : "Umum");
                                    if(!empty($mutasi['keterangan'])) {
                                        $teks_keterangan .= " - " . $mutasi['keterangan'];
                                    }
                                } else {
                                    $teks_keterangan = "Pengeluaran: " . (!empty($mutasi['keterangan']) ? $mutasi['keterangan'] : "Tanpa Keterangan");
                                }
                        ?>
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 text-gray-500"><?= date('d/m/Y', strtotime($mutasi['tanggal_setor'])) ?></td>
                            <td class="px-6 py-4 font-medium text-gray-900"><?= $teks_keterangan ?></td>
                            
                            <td class="px-6 py-4 font-bold text-right text-green-600 bg-green-50/30">
                                <?= $is_masuk ? '+ Rp ' . number_format($mutasi['nominal'], 0, ',', '.') : '-' ?>
                            </td>
                            
                            <td class="px-6 py-4 font-bold text-right text-red-600 bg-red-50/30">
                                <?= !$is_masuk ? '- Rp ' . number_format($mutasi['nominal'], 0, ',', '.') : '-' ?>
                            </td>
                        </tr>
                        <?php endwhile; else: ?>
                        <tr><td colspan="4" class="px-6 py-8 text-center text-gray-500">Belum ada riwayat transaksi mutasi kas.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <div id="detailModal" class="fixed inset-0 z-50 hidden bg-gray-900 bg-opacity-50 flex items-center justify-center p-4 backdrop-blur-sm transition-opacity">
        <div class="bg-white rounded-xl shadow-lg w-full max-w-lg overflow-hidden flex flex-col max-h-full">
            <div class="p-5 border-b border-gray-200 flex justify-between items-center bg-gray-50">
                <h3 class="text-lg font-semibold text-gray-900">
                    Rincian Setoran: <span id="modalNamaWarga" class="text-teal-600 ml-1"></span>
                </h3>
                <button onclick="closeDetailModal()" class="text-gray-400 hover:text-red-500 transition-colors">
                    <i class="ph ph-x text-xl font-bold"></i>
                </button>
            </div>
            
            <div class="p-0 overflow-y-auto max-h-80">
                <table class="w-full text-left border-collapse">
                    <thead class="sticky top-0 bg-white shadow-sm">
                        <tr class="text-gray-500 text-xs uppercase tracking-wider border-b border-gray-200">
                            <th class="px-5 py-3 font-medium">Tanggal Transaksi</th>
                            <th class="px-5 py-3 font-medium text-right">Nominal (Rp)</th>
                        </tr>
                    </thead>
                    <tbody id="modalTableBody" class="text-sm divide-y divide-gray-100">
                        </tbody>
                </table>
            </div>
            
            <div class="p-5 border-t border-gray-200 bg-gray-50 flex justify-between items-center">
                <span class="font-medium text-gray-500">Total Keseluruhan</span>
                <span id="modalTotal" class="font-bold text-xl text-gray-900"></span>
            </div>
        </div>
    </div>

    <script>
        // Fungsi Salin Teks
        function copyText(text) {
            navigator.clipboard.writeText(text).then(function() {
                alert('Nomor rekening/DANA ' + text + ' berhasil disalin!');
            }).catch(function(err) {
                console.error('Gagal menyalin teks: ', err);
                alert('Gagal menyalin teks. Silakan blok dan salin manual.');
            });
        }

        // Fungsi Format Angka ke Rupiah
        function formatRupiah(angka) {
            return new Intl.NumberFormat('id-ID').format(angka);
        }

        // Fungsi Membuka Modal dan Mengisi Data
        function openDetailModal(nama, total, historyStr) {
            // Set judul nama dan total di dalam modal
            document.getElementById('modalNamaWarga').innerText = nama;
            document.getElementById('modalTotal').innerText = 'Rp ' + formatRupiah(total);
            
            const tbody = document.getElementById('modalTableBody');
            tbody.innerHTML = ''; // Kosongkan isi tabel sebelumnya
            
            const history = JSON.parse(historyStr);
            
            if (history.length === 0) {
                tbody.innerHTML = '<tr><td colspan="2" class="px-5 py-8 text-center text-gray-500">Belum ada riwayat setoran tercatat.</td></tr>';
            } else {
                history.forEach(trx => {
                    const tr = document.createElement('tr');
                    tr.className = 'hover:bg-gray-50 transition-colors';
                    
                    // Mengubah format YYYY-MM-DD menjadi DD/MM/YYYY
                    const dateParts = trx.tanggal_setor.split('-');
                    const formattedDate = `${dateParts[2]}/${dateParts[1]}/${dateParts[0]}`;
                    
                    tr.innerHTML = `
                        <td class="px-5 py-3">${formattedDate}</td>
                        <td class="px-5 py-3 text-right font-medium text-green-600">+ Rp ${formatRupiah(trx.nominal)}</td>
                    `;
                    tbody.appendChild(tr);
                });
            }
            
            // Tampilkan Modal
            document.getElementById('detailModal').classList.remove('hidden');
        }

        // Fungsi Menutup Modal
        function closeDetailModal() {
            document.getElementById('detailModal').classList.add('hidden');
        }

        // Fitur tambahan: Menutup modal ketika area luar pop-up diklik
        document.getElementById('detailModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeDetailModal();
            }
        });
    </script>
</body>
</html>