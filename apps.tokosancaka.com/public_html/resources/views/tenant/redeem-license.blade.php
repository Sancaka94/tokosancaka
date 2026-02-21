<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aktivasi Lisensi - Sancaka POS</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 min-h-screen flex items-center justify-center p-4 font-sans">

    <div class="max-w-md w-full bg-white rounded-2xl shadow-xl overflow-hidden">

        <div class="bg-blue-600 p-6 text-center">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-white/20 rounded-full mb-4">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                </svg>
            </div>
            <h2 class="text-2xl font-bold text-white">Aktivasi Lisensi</h2>
            <p class="text-blue-100 mt-1 text-sm">Masukkan kode lisensi untuk melanjutkan akses ke aplikasi POS Anda.</p>
        </div>

        <div class="p-6 md:p-8">

            @if (session('success'))
                <div class="mb-4 bg-green-50 border-l-4 border-green-500 p-4 rounded-md">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                        <p class="text-sm text-green-700 font-medium">{{ session('success') }}</p>
                    </div>
                </div>
            @endif

            @if (session('error') || $errors->any())
                <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-md">
                    <div class="flex items-start">
                        <svg class="w-5 h-5 text-red-500 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path></svg>
                        <div>
                            <p class="text-sm text-red-700 font-medium">{{ session('error') ?? 'Terdapat kesalahan pada input Anda.' }}</p>
                            @if($errors->any())
                                <ul class="mt-1 text-xs text-red-600 list-disc list-inside">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            <form action="{{ route('license.redeem') }}" method="POST">
                @csrf
                <div class="mb-5">
                    <label for="license_code" class="block text-sm font-semibold text-gray-700 mb-2">Kode Lisensi</label>
                    <div class="relative">
                        <input type="text"
                               name="license_code"
                               id="license_code"
                               class="w-full px-4 py-3 bg-gray-50 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors uppercase tracking-widest text-center font-mono text-lg"
                               placeholder="PRO-XXXX-XXXX"
                               required
                               autocomplete="off">
                    </div>
                    <p class="mt-2 text-xs text-gray-500 text-center">Contoh: PRO-A1B2C3D4</p>
                </div>

                <button type="submit" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-bold text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                    Aktifkan Sekarang
                </button>
            </form>

            <div class="mt-8 pt-6 border-t border-gray-100 flex flex-col space-y-3">
                <p class="text-center text-sm text-gray-600">
                    Belum punya lisensi?
                    <a href="https://wa.me/6285745808809?text=Halo%20Admin,%20saya%20ingin%20membeli%20lisensi%20Sancaka%20POS" target="_blank" class="font-semibold text-blue-600 hover:text-blue-500">
                        Beli di sini
                    </a>
                </p>

                <form method="POST" action="{{ route('logout') }}" class="text-center">
                    @csrf
                    <button type="submit" class="text-sm font-medium text-gray-500 hover:text-red-600 transition-colors underline">
                        Keluar dari akun (Logout)
                    </button>
                </form>
            </div>

        </div>
    </div>

</body>
</html>
