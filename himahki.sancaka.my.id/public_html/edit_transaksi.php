<?php
session_start();

// Cek apakah admin sudah login
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit;
}

require 'koneksi.php';

// Ambil ID dari URL
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Proses Update Data ketika form disubmit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $tanggal = $conn->real_escape_string($_POST['tanggal']);
    $mahasiswa_id_sql = !empty($_POST['mahasiswa_id']) ? (int)$_POST['mahasiswa_id'] : 'NULL'; 
    $jenis = $conn->real_escape_string($_POST['jenis']);
    $nominal = (float)$_POST['nominal'];
    $keterangan = isset($_POST['keterangan']) ? $conn->real_escape_string($_POST['keterangan']) : '';

    // 1. UPDATE DATA MAHASISWA JIKA ADA PERUBAHAN NAMA/SEMESTER
    if ($mahasiswa_id_sql !== 'NULL' && isset($_POST['nama_mahasiswa']) && isset($_POST['semester'])) {
        $nama_mhs = $conn->real_escape_string($_POST['nama_mahasiswa']);
        $sem_mhs = (int)$_POST['semester'];
        $conn->query("UPDATE mahasiswa SET nama_mahasiswa = '$nama_mhs', semester = $sem_mhs WHERE id = $mahasiswa_id_sql");
    }

    // 2. UPDATE DATA TRANSAKSI
    $sql_update = "UPDATE transaksi SET 
                    tanggal_setor = '$tanggal', 
                    mahasiswa_id = $mahasiswa_id_sql, 
                    jenis = '$jenis', 
                    nominal = $nominal, 
                    keterangan = '$keterangan' 
                   WHERE id = $id";
                   
    if ($conn->query($sql_update)) {
        echo "<script>alert('Data transaksi & mahasiswa berhasil diupdate!'); window.location.href='admin.php';</script>";
        exit;
    } else {
        $error = "Error: " . $conn->error;
    }
}

// Ambil data transaksi saat ini berdasarkan ID
$query_trx = $conn->query("SELECT * FROM transaksi WHERE id = $id");
if ($query_trx->num_rows == 0) {
    die("Data transaksi tidak ditemukan! <a href='admin.php'>Kembali</a>");
}
$data = $query_trx->fetch_assoc();

// Ambil data mahasiswa untuk dropdown
$list_mahasiswa = $conn->query("SELECT * FROM mahasiswa ORDER BY nama_mahasiswa ASC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Transaksi - Admin Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-50 text-gray-900 antialiased p-4 md:p-8 flex items-center justify-center min-h-screen">

    <div class="max-w-2xl w-full bg-white p-6 md:p-8 rounded-xl border border-gray-200 shadow-sm">
        <div class="flex items-center justify-between mb-6 pb-4 border-b border-gray-100">
            <h2 class="text-xl font-bold flex items-center gap-2">
                <i class="ph ph-pencil-simple text-blue-600"></i> Edit Transaksi
            </h2>
            <a href="admin.php" class="text-gray-500 hover:text-gray-900 text-sm flex items-center gap-1">
                <i class="ph ph-arrow-left"></i> Kembali
            </a>
        </div>

        <?php if(isset($error)): ?>
            <div class="bg-red-50 text-red-700 p-4 rounded-lg mb-6 text-sm"><?= $error ?></div>
        <?php endif; ?>

        <form action="" method="POST" class="space-y-5">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Transaksi ID</label>
                <input type="text" value="<?= htmlspecialchars($data['transaksi_id']) ?>" readonly class="w-full bg-gray-50 border border-gray-300 rounded-lg px-3 py-2 text-sm text-gray-500 cursor-not-allowed">
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal</label>
                    <input type="date" name="tanggal" value="<?= $data['tanggal_setor'] ?>" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-1 focus:ring-blue-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Jenis Transaksi</label>
                    <select name="jenis" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-1 focus:ring-blue-500 outline-none">
                        <option value="masuk" <?= $data['jenis'] == 'masuk' ? 'selected' : '' ?>>Pemasukan (Uang Masuk / Kas)</option>
                        <option value="keluar" <?= $data['jenis'] == 'keluar' ? 'selected' : '' ?>>Pengeluaran (Uang Keluar)</option>
                    </select>
                </div>
            </div>

            <!-- Bagian Pemilihan & Edit Mahasiswa -->
            <div class="border border-gray-200 p-4 rounded-lg bg-gray-50/50">
                <label class="block text-sm font-medium text-gray-700 mb-1">Pilih Mahasiswa <span class="text-gray-400 font-normal">(Kosongkan jika umum)</span></label>
                <select name="mahasiswa_id" id="mahasiswa_select" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-1 focus:ring-blue-500 outline-none bg-white">
                    <option value="" data-nama="" data-sem="">-- Pilih Mahasiswa --</option>
                    <?php 
                    $curr_nama = '';
                    $curr_sem = '';
                    if($list_mahasiswa) {
                        while($row = $list_mahasiswa->fetch_assoc()): 
                            $selected = '';
                            if ($data['mahasiswa_id'] == $row['id']) {
                                $selected = 'selected';
                                $curr_nama = $row['nama_mahasiswa'];
                                $curr_sem = $row['semester'];
                            }
                    ?>
                        <option value="<?= $row['id'] ?>" data-nama="<?= htmlspecialchars($row['nama_mahasiswa']) ?>" data-sem="<?= htmlspecialchars($row['semester']) ?>" <?= $selected ?>>
                            <?= htmlspecialchars($row['nama_mahasiswa']) ?> (Sem <?= htmlspecialchars($row['semester']) ?>)
                        </option>
                    <?php 
                        endwhile; 
                    }
                    ?>
                </select>

                <!-- Form Edit Data Master Mahasiswa (Muncul lewat JS jika ada mahasiswa terpilih) -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4 pt-4 border-t border-gray-200" id="box_edit_mhs" style="<?= empty($data['mahasiswa_id']) ? 'display:none;' : 'display:grid;' ?>">
                    <div>
                        <label class="block text-xs font-medium text-blue-700 mb-1">Edit Nama Mahasiswa</label>
                        <input type="text" name="nama_mahasiswa" id="input_nama" value="<?= htmlspecialchars($curr_nama) ?>" class="w-full border border-blue-200 rounded-lg px-3 py-1.5 text-sm focus:ring-1 focus:ring-blue-500 outline-none bg-blue-50/30">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-blue-700 mb-1">Edit Semester</label>
                        <input type="number" name="semester" id="input_sem" value="<?= htmlspecialchars($curr_sem) ?>" class="w-full border border-blue-200 rounded-lg px-3 py-1.5 text-sm focus:ring-1 focus:ring-blue-500 outline-none bg-blue-50/30">
                    </div>
                    <div class="md:col-span-2 text-xs text-gray-500 italic">
                        *Perubahan pada nama atau semester di atas akan mengupdate data master mahasiswa di database.
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nominal (Rp)</label>
                    <input type="number" name="nominal" value="<?= $data['nominal'] ?>" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-1 focus:ring-blue-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Keterangan</label>
                    <input type="text" name="keterangan" value="<?= htmlspecialchars($data['keterangan'] ?? '') ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-1 focus:ring-blue-500 outline-none">
                </div>
            </div>

            <div class="pt-4 flex gap-3">
                <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white rounded-lg px-4 py-2.5 text-sm font-medium transition-colors flex justify-center items-center gap-2">
                    <i class="ph ph-floppy-disk text-lg"></i> Simpan Perubahan
                </button>
            </div>
        </form>
    </div>

    <!-- Script untuk merubah input nama & semester otomatis berdasarkan dropdown -->
    <script>
        document.getElementById('mahasiswa_select').addEventListener('change', function() {
            var selectedOpt = this.options[this.selectedIndex];
            var boxMhs = document.getElementById('box_edit_mhs');
            var inputNama = document.getElementById('input_nama');
            var inputSem = document.getElementById('input_sem');

            if (this.value !== "") {
                boxMhs.style.display = 'grid';
                inputNama.value = selectedOpt.getAttribute('data-nama');
                inputSem.value = selectedOpt.getAttribute('data-sem');
            } else {
                boxMhs.style.display = 'none';
                inputNama.value = '';
                inputSem.value = '';
            }
        });
    </script>
</body>
</html>