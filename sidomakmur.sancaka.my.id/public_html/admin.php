<?php
session_start();

// Cek apakah admin sudah login
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit;
}

require 'koneksi.php';

// 1. Proses Input Transaksi Baru
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['transaksi_id'])) {
    try {
        $trx_id = $conn->real_escape_string($_POST['transaksi_id']);
        $tanggal = $conn->real_escape_string($_POST['tanggal']);
        
        // Warga dibuat tidak wajib (null) jika berupa pengeluaran
        $warga_id_sql = !empty($_POST['warga_id']) ? (int)$_POST['warga_id'] : 'NULL'; 
        $nominal = (float)$_POST['nominal'];
        
        // Tangkap data jenis dan keterangan baru
        $jenis = $conn->real_escape_string($_POST['jenis']);
        $keterangan = isset($_POST['keterangan']) ? $conn->real_escape_string($_POST['keterangan']) : '';
        
        $sql = "INSERT INTO transaksi (transaksi_id, tanggal_setor, warga_id, jenis, nominal, keterangan) VALUES ('$trx_id', '$tanggal', $warga_id_sql, '$jenis', $nominal, '$keterangan')";
        
        $insert = $conn->query($sql);
        if ($insert) {
            header("Location: admin.php?success=1");
            exit;
        } else {
            // Tampilkan error query MySQL biasa
            die("<div style='padding:20px; font-family:sans-serif; color:red;'><b>Query Gagal:</b> " . $conn->error . "</div>");
        }
    } catch (Exception $e) {
        // Tangkap Fatal Error (Exception) dari MySQLi Strict Mode
        die("<div style='padding:20px; font-family:sans-serif; color:red;'>
                <b>Error Database:</b> " . $e->getMessage() . "<br><br>
                <b>Penyebab Umum:</b> Pastikan Anda sudah menjalankan ALTER TABLE di phpMyAdmin untuk kolom 'keterangan' dan 'warga_id'.
            </div>");
    }
}

// 2. Proses Hapus Data Transaksi
if (isset($_GET['hapus'])) {
    $id_hapus = (int)$_GET['hapus'];
    $conn->query("DELETE FROM transaksi WHERE id = $id_hapus");
    header("Location: admin.php?hapus_success=1");
    exit;
}
// ==========================================

$TARGET_DANA = 5000000;

$query_warga = $conn->query("SELECT COUNT(id) as total FROM warga");
$total_warga = $query_warga->fetch_assoc()['total'];

$query_masuk = $conn->query("SELECT SUM(nominal) as total FROM transaksi WHERE jenis='masuk'");
$total_masuk = $query_masuk->fetch_assoc()['total'] ?? 0;

$query_keluar = $conn->query("SELECT SUM(nominal) as total FROM transaksi WHERE jenis='keluar'");
$total_keluar = $query_keluar->fetch_assoc()['total'] ?? 0;

$sisa_kekurangan = $TARGET_DANA - $total_masuk;
if ($sisa_kekurangan < 0) $sisa_kekurangan = 0;

$list_warga = $conn->query("SELECT * FROM warga ORDER BY nama ASC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Keuangan RT</title>

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
                    <h1 class="text-2xl font-bold tracking-tight">Dashboard Admin</h1>
                    <p class="text-sm text-gray-500">Manajemen Iuran Gang Garuda RT.22 RW.05 Kel.Ketanggi</p>
                </div>
            </div>
            <div class="mt-4 md:mt-0 flex flex-wrap gap-3">
                <a href="index.php" class="bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium transition-colors flex items-center gap-2">
                    <i class="ph ph-globe text-lg"></i> Lihat Web Publik
                </a>
                <a href="logout.php" onclick="return confirm('Yakin ingin keluar dari halaman admin?');" class="bg-red-50 hover:bg-red-100 text-red-600 border border-red-200 px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2 transition-colors">
                    <i class="ph ph-sign-out text-lg"></i> Logout
                </a>
            </div>
        </header>

        <?php if(isset($_GET['success'])): ?>
            <div class="bg-green-50 text-green-700 p-4 rounded-xl border border-green-200 mb-6 flex items-center gap-2 shadow-sm">
                <i class="ph ph-check-circle text-xl"></i> Transaksi berhasil disimpan!
            </div>
        <?php endif; ?>
        <?php if(isset($_GET['hapus_success'])): ?>
            <div class="bg-red-50 text-red-700 p-4 rounded-xl border border-red-200 mb-6 flex items-center gap-2 shadow-sm">
                <i class="ph ph-trash text-xl"></i> Data transaksi berhasil dihapus!
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm">
                <div class="text-gray-500 text-sm font-medium mb-1">Total Warga</div>
                <div class="text-2xl font-bold"><?= number_format($total_warga, 0, ',', '.') ?> <span class="text-sm font-normal text-gray-400">KK</span></div>
            </div>
            <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm">
                <div class="text-gray-500 text-sm font-medium mb-1">Uang Masuk</div>
                <div class="text-2xl font-bold">Rp <?= number_format($total_masuk, 0, ',', '.') ?></div>
            </div>
            <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm">
                <div class="text-gray-500 text-sm font-medium mb-1">Pengeluaran</div>
                <div class="text-2xl font-bold">Rp <?= number_format($total_keluar, 0, ',', '.') ?></div>
            </div>
            <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm">
                <div class="text-gray-500 text-sm font-medium mb-1">Kekurangan Target</div>
                <div class="text-2xl font-bold text-gray-900">Rp <?= number_format($sisa_kekurangan, 0, ',', '.') ?></div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm mb-8">
            <h2 class="text-lg font-semibold mb-4 border-b border-gray-100 pb-2 flex items-center gap-2">
                <i class="ph ph-plus-circle"></i> Input Transaksi Baru
            </h2>
            <form action="" method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Transaksi ID</label>
                        <input type="text" name="transaksi_id" value="TRX-<?= time() ?>" readonly class="w-full bg-gray-50 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none cursor-not-allowed">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal</label>
                        <input type="date" name="tanggal" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-1 focus:ring-gray-900 outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Jenis Transaksi</label>
                        <select name="jenis" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-1 focus:ring-gray-900 outline-none bg-white">
                            <option value="masuk">Pemasukan (Uang Masuk)</option>
                            <option value="keluar">Pengeluaran (Uang Keluar)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Pilih Warga <span class="text-gray-400 font-normal">(Kosongkan jika pengeluaran umum)</span></label>
                        <select name="warga_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-1 focus:ring-gray-900 outline-none bg-white">
                            <option value="">-- Pilih Warga --</option>
                            <?php while($row = $list_warga->fetch_assoc()): ?>
                                <option value="<?= $row['id'] ?>"><?= $row['nama'] ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nominal (Rp)</label>
                        <input type="number" name="nominal" required placeholder="Contoh: 50000" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-1 focus:ring-gray-900 outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Keterangan</label>
                        <input type="text" name="keterangan" placeholder="Contoh: Iuran Bulanan / Beli sapu lidi" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-1 focus:ring-gray-900 outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500 mb-1">Total Keseluruhan Warga (Otomatis)</label>
                        <input type="text" value="Rp <?= number_format($total_masuk, 0, ',', '.') ?>" readonly class="w-full bg-gray-50 text-gray-500 border border-gray-300 rounded-lg px-3 py-2 text-sm cursor-not-allowed">
                    </div>
                    <div class="pt-2">
                        <button type="submit" class="w-full bg-gray-900 hover:bg-black text-white rounded-lg px-4 py-2 text-sm font-medium transition-colors flex justify-center items-center gap-2">
                            <i class="ph ph-floppy-disk text-lg"></i> Simpan Data
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="p-5 border-b border-gray-200 flex justify-between items-center bg-gray-50">
                <h2 class="text-lg font-semibold flex items-center gap-2">
                    <i class="ph ph-database"></i> Manajemen Data Transaksi
                </h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-white text-gray-500 text-xs uppercase tracking-wider border-b border-gray-200">
                            <th class="px-6 py-3 font-medium">ID Transaksi</th>
                            <th class="px-6 py-3 font-medium">Tanggal</th>
                            <th class="px-6 py-3 font-medium">Nama Warga / Keterangan</th>
                            <th class="px-6 py-3 font-medium">Jenis</th>
                            <th class="px-6 py-3 font-medium text-right">Nominal</th>
                            <th class="px-6 py-3 font-medium text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm divide-y divide-gray-100">
                        <?php
                        // Menggunakan LEFT JOIN agar data tanpa warga_id (pengeluaran) tetap tampil
                        $query_history = $conn->query("
                            SELECT t.*, w.nama 
                            FROM transaksi t LEFT JOIN warga w ON t.warga_id = w.id 
                            ORDER BY t.id DESC LIMIT 20
                        ");
                        if($query_history->num_rows > 0):
                            while($history = $query_history->fetch_assoc()):
                        ?>
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 font-medium text-gray-900"><?= $history['transaksi_id'] ?></td>
                            <td class="px-6 py-4 text-gray-600"><?= date('d/m/Y', strtotime($history['tanggal_setor'])) ?></td>
                            
                            <td class="px-6 py-4">
                                <span class="font-medium"><?= !empty($history['nama']) ? $history['nama'] : '<em>Umum/Tanpa Nama</em>' ?></span><br>
                                <span class="text-xs text-gray-500"><?= !empty($history['keterangan']) ? $history['keterangan'] : '-' ?></span>
                            </td>

                            <td class="px-6 py-4">
                                <?php if($history['jenis'] == 'masuk'): ?>
                                    <span class="inline-block bg-green-100 text-green-700 px-2 py-0.5 rounded text-xs font-medium">Pemasukan</span>
                                <?php else: ?>
                                    <span class="inline-block bg-red-100 text-red-700 px-2 py-0.5 rounded text-xs font-medium">Pengeluaran</span>
                                <?php endif; ?>
                            </td>

                            <td class="px-6 py-4 font-bold text-right">Rp <?= number_format($history['nominal'], 0, ',', '.') ?></td>
                            <td class="px-6 py-4 flex justify-center gap-2">
                                <button title="Detail" onclick="alert('Ini adalah tombol Detail untuk ID: <?= $history['transaksi_id'] ?>')" class="bg-blue-500 hover:bg-blue-600 text-white w-8 h-8 flex items-center justify-center rounded-md transition-colors shadow-sm">
                                    <i class="ph ph-eye text-base"></i>
                                </button>
                                <button title="Edit" onclick="alert('Fitur Edit (Update) belum dikonfigurasi. \nID: <?= $history['transaksi_id'] ?>')" class="bg-amber-500 hover:bg-amber-600 text-white w-8 h-8 flex items-center justify-center rounded-md transition-colors shadow-sm">
                                    <i class="ph ph-pencil-simple text-base"></i>
                                </button>
                                <a href="?hapus=<?= $history['id'] ?>" onclick="return confirm('Apakah Anda yakin ingin MENGHAPUS riwayat transaksi Rp <?= number_format($history['nominal'], 0, ',', '.') ?>?');" title="Hapus" class="bg-red-500 hover:bg-red-600 text-white w-8 h-8 flex items-center justify-center rounded-md transition-colors shadow-sm">
                                    <i class="ph ph-trash text-base"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; else: ?>
                        <tr><td colspan="6" class="px-6 py-8 text-center text-gray-500">Belum ada data transaksi tersimpan.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>