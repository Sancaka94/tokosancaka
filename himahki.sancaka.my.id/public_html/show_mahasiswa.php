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

    // 1. READ Data
    if ($action == 'read') {
        $result =$conn->query("SELECT * FROM mahasiswa ORDER BY nama_mahasiswa ASC");
        $data = [];
        if ($result) {
            while ($row =$result->fetch_assoc()) {
                $data[] =$row;
            }
        }
        echo json_encode($data);
        exit;
    }

    // 2. CREATE & UPDATE Data
    if ($action == 'save') {$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $nama = $conn->real_escape_string(trim($_POST['nama_mahasiswa']));
        $semester = (int)$_POST['semester'];

        if (empty($nama) \vert{}\vert{}$semester < 1) {
            echo json_encode(['status' => 'error', 'message' => 'Data tidak valid.']);
            exit;
        }

        if ($id > 0) {$sql = "UPDATE mahasiswa SET nama_mahasiswa = '$nama', semester = $semester WHERE id =$id";
        } else {
            $sql = "INSERT INTO mahasiswa (nama_mahasiswa, semester) VALUES ('$nama',$semester)";
        }

        if ($conn->query($sql)) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan: ' . $conn->error]);
        }
        exit;
    }

    // 3. DELETE Data
    if ($action == 'delete') {$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        
        // Coba hapus data (Bisa gagal jika mahasiswa sudah memiliki transaksi karena relasi database)
        if ($conn->query("DELETE FROM mahasiswa WHERE id = $id")) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal menghapus. Mahasiswa ini mungkin sudah tercatat pada tabel transaksi.']);
        }
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Mahasiswa - Admin Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap');
        body { font-family: 'Inter', sans-serif; background-color: #fafafa; }
    </style>
</head>
<body class="text-gray-800 antialiased p-4 md:p-8">

    <div class="max-w-5xl mx-auto">
        <header class="mb-6 flex justify-between items-center pb-4 border-b border-gray-200">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight text-gray-900">Manajemen Mahasiswa</h1>
                <p class="text-sm text-gray-500">Kelola master data nama dan semester</p>
            </div>
            <a href="admin.php" class="border border-gray-300 bg-white hover:bg-gray-50 text-gray-700 px-4 py-2 rounded-md text-sm font-medium transition-colors flex items-center gap-2 shadow-sm">
                <i class="ph ph-arrow-left"></i> Kembali ke Dashboard
            </a>
        </header>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            
            <!-- Form Input -->
            <div class="md:col-span-1">
                <div class="bg-white p-5 rounded-lg border border-gray-200 shadow-sm">
                    <h2 class="text-base font-semibold mb-4 flex items-center gap-2" id="formTitle">
                        <i class="ph ph-user-plus"></i> Tambah Mahasiswa
                    </h2>
                    
                    <form id="formMahasiswa" class="space-y-4">
                        <input type="hidden" name="id" id="mhs_id" value="0">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nama Mahasiswa</label>
                            <input type="text" name="nama_mahasiswa" id="mhs_nama" required class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-1 focus:ring-gray-800 outline-none">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Semester</label>
                            <input type="number" name="semester" id="mhs_sem" min="1" max="14" required class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-1 focus:ring-gray-800 outline-none">
                        </div>
                        
                        <div class="pt-2 flex gap-2">
                            <button type="submit" id="btnSimpan" class="w-full bg-gray-900 hover:bg-black text-white rounded-md px-4 py-2 text-sm font-medium transition-colors flex justify-center items-center gap-2">
                                Simpan
                            </button>
                            <button type="button" id="btnBatal" onclick="resetForm()" class="hidden w-full bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 rounded-md px-4 py-2 text-sm font-medium transition-colors justify-center items-center gap-2">
                                Batal
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Tabel Data -->
            <div class="md:col-span-2">
                <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider border-b border-gray-200">
                                    <th class="px-5 py-3 font-medium w-12 text-center">No</th>
                                    <th class="px-5 py-3 font-medium">Nama Mahasiswa</th>
                                    <th class="px-5 py-3 font-medium text-center">Semester</th>
                                    <th class="px-5 py-3 font-medium text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="tableBody" class="text-sm divide-y divide-gray-100">
                                <!-- Data di-load via JavaScript -->
                                <tr>
                                    <td colspan="4" class="text-center py-6 text-gray-400">Memuat data...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
        </div>
    </div>

    <!-- JavaScript CRUD via AJAX -->
    <script>
        const tableBody = document.getElementById('tableBody');
        const formMahasiswa = document.getElementById('formMahasiswa');
        const formTitle = document.getElementById('formTitle');
        const btnBatal = document.getElementById('btnBatal');

        // Saat halaman pertama dimuat
        document.addEventListener('DOMContentLoaded', loadData);

        // 1. Fungsi READ Data (Load Tabel)
        function loadData() {
            fetch('show_mahasiswa.php?action=read')
                .then(response => response.json())
                .then(data => {
                    tableBody.innerHTML = '';
                    if (data.length === 0) {
                        tableBody.innerHTML = `<tr><td colspan="4" class="text-center py-6 text-gray-500">Belum ada data mahasiswa.</td></tr>`;
                        return;
                    }

                    data.forEach((row, index) => {
                        let tr = document.createElement('tr');
                        tr.className = "hover:bg-gray-50 transition-colors";
                        tr.innerHTML = `
                            <td class="px-5 py-3 text-center text-gray-500">${index + 1}</td>
                            <td class="px-5 py-3 font-medium text-gray-900">${row.nama_mahasiswa}</td>
                            <td class="px-5 py-3 text-center text-gray-600">${row.semester}</td>
                            <td class="px-5 py-3 flex justify-center gap-2">
                                <button onclick="editData(${row.id}, '${row.nama_mahasiswa.replace(/'/g, "\\'")}', ${row.semester})" class="p-1.5 border border-gray-300 rounded text-gray-600 hover:bg-gray-100 transition-colors" title="Edit">
                                    <i class="ph ph-pencil-simple text-lg"></i>
                                </button>
                                <button onclick="deleteData(${row.id})" class="p-1.5 border border-gray-300 rounded text-gray-600 hover:bg-gray-100 transition-colors" title="Hapus">
                                    <i class="ph ph-trash text-lg"></i>
                                </button>
                            </td>
                        `;
                        tableBody.appendChild(tr);
                    });
                })
                .catch(error => {
                    tableBody.innerHTML = `<tr><td colspan="4" class="text-center py-6 text-red-500">Gagal memuat data.</td></tr>`;
                });
        }

        // 2. Fungsi CREATE & UPDATE (Submit Form)
        formMahasiswa.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const btnSimpan = document.getElementById('btnSimpan');
            
            btnSimpan.innerHTML = '<i class="ph ph-spinner animate-spin"></i> Loading...';
            btnSimpan.disabled = true;

            fetch('show_mahasiswa.php?action=save', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(res => {
                if (res.status === 'success') {
                    resetForm();
                    loadData();
                } else {
                    alert(res.message);
                }
            })
            .finally(() => {
                btnSimpan.innerHTML = 'Simpan';
                btnSimpan.disabled = false;
            });
        });

        // 3. Fungsi Pindah ke Mode Edit
        function editData(id, nama, semester) {
            document.getElementById('mhs_id').value = id;
            document.getElementById('mhs_nama').value = nama;
            document.getElementById('mhs_sem').value = semester;
            
            formTitle.innerHTML = '<i class="ph ph-pencil-simple"></i> Edit Mahasiswa';
            btnBatal.classList.remove('hidden');
            btnBatal.classList.add('flex');
            
            // Scroll ke form jika di layar kecil
            document.getElementById('mhs_nama').focus();
        }

        // 4. Fungsi DELETE Data
        function deleteData(id) {
            if (confirm('Yakin ingin menghapus data ini?')) {
                const formData = new FormData();
                formData.append('id', id);

                fetch('show_mahasiswa.php?action=delete', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(res => {
                    if (res.status === 'success') {
                        loadData();
                    } else {
                        alert(res.message);
                    }
                });
            }
        }

        // 5. Fungsi Reset Form ke Kondisi Tambah Baru
        function resetForm() {
            formMahasiswa.reset();
            document.getElementById('mhs_id').value = '0';
            formTitle.innerHTML = '<i class="ph ph-user-plus"></i> Tambah Mahasiswa';
            btnBatal.classList.add('hidden');
            btnBatal.classList.remove('flex');
        }
    </script>
</body>
</html>