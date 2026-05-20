<?php
session_start();
require 'koneksi.php';

// Jika sudah login, langsung arahkan ke admin.php
if (isset($_SESSION['admin_logged_in'])) {
    header("Location: admin.php");
    exit;
}

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $conn->real_escape_string($_POST['username']);
    $password = md5($_POST['password']); // Menggunakan MD5 sesuai query database

    $cek = $conn->query("SELECT * FROM admin WHERE username='$username' AND password='$password'");
    
    if ($cek->num_rows > 0) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['username'] = $username;
        header("Location: admin.php");
        exit;
    } else {
        $error = "Username atau password salah!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin - sidomakmur.sancaka.my.id</title>
    
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
<body class="bg-gray-50 flex items-center justify-center min-h-screen p-4 antialiased">

    <div class="w-full max-w-md bg-white p-8 rounded-2xl border border-gray-200 shadow-sm">
        
        <div class="text-center mb-8 flex flex-col items-center">
            <img src="https://tokosancaka.com/public/assets/ngawi.png" alt="Logo Ngawi" class="w-16 h-16 object-contain drop-shadow-sm mb-4">
            <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Login Admin</h1>
            <p class="text-sm text-gray-500 mt-1">Sistem Iuran Gang Garuda RT.22</p>
        </div>

        <?php if($error): ?>
        <div class="bg-red-50 text-red-600 p-3 rounded-lg text-sm mb-4 border border-red-100 flex items-center gap-2">
            <i class="ph ph-warning-circle text-lg"></i> <?= $error ?>
        </div>
        <?php endif; ?>

        <form action="" method="POST" class="space-y-5">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                <input type="text" name="username" required autofocus class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-1 focus:ring-gray-900 focus:border-gray-900 outline-none transition-all">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                <input type="password" name="password" required class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-1 focus:ring-gray-900 focus:border-gray-900 outline-none transition-all">
            </div>
            <button type="submit" class="w-full bg-gray-900 hover:bg-black text-white rounded-lg px-4 py-2.5 text-sm font-medium transition-colors mt-2 flex justify-center items-center gap-2">
                <i class="ph ph-sign-in text-lg"></i> Masuk Dashboard
            </button>
        </form>

        <div class="mt-6 text-center">
            <a href="index.php" class="text-sm text-gray-500 hover:text-gray-900 flex items-center justify-center gap-1 transition-colors">
                <i class="ph ph-arrow-left"></i> Kembali ke Web Publik
            </a>
        </div>
    </div>

</body>
</html>