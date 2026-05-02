<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sancaka Express</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-white text-black font-sans min-h-screen flex items-center justify-center">
    <div class="text-center">
        <h1 class="text-4xl font-bold tracking-tighter mb-4">SANCAKA <span class="text-gray-400">EXPRESS</span></h1>
        <p class="text-gray-500 mb-8">Sistem Manajemen Logistik & Data Kota Matraman.</p>
        
        <div class="flex gap-4 justify-center">
            @auth
                <a href="{{ url('/dashboard') }}" class="px-6 py-2 bg-black text-white rounded-md hover:bg-gray-800 transition-all">Masuk Dashboard</a>
            @else
                <a href="{{ route('login') }}" class="px-6 py-2 bg-black text-white rounded-md hover:bg-gray-800 transition-all">Log in</a>
                @if (Route::has('register'))
                    <a href="{{ route('register') }}" class="px-6 py-2 bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition-all">Daftar</a>
                @endif
            @endauth
        </div>
    </div>
</body>
</html>