<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input Laporan Keuangan</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-6">

    <div class="bg-white w-full max-w-3xl rounded-2xl shadow-2xl overflow-hidden">
        <div class="bg-blue-900 p-8 text-white text-center">
            <h1 class="text-4xl font-bold tracking-wide">INPUT KEUANGAN</h1>
            <p class="mt-2 text-blue-200">Catat Pemasukan & Pengeluaran Anda</p>
        </div>

        <div class="p-10">
            @if(session('success'))
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded text-lg">
                    {{ session('success') }}
                </div>
            @endif

            <form action="{{ route('cashflow.store') }}" method="POST" class="space-y-8">
                @csrf

                <div class="grid grid-cols-2 gap-6">
                    <label class="cursor-pointer">
                        <input type="radio" name="type" value="income" class="peer sr-only" checked>
                        <div class="p-6 text-center border-2 border-gray-200 rounded-xl peer-checked:border-green-500 peer-checked:bg-green-50 peer-checked:text-green-700 transition-all">
                            <svg class="w-12 h-12 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                            <span class="text-2xl font-bold">PEMASUKAN</span>
                        </div>
                    </label>
                    <label class="cursor-pointer">
                        <input type="radio" name="type" value="expense" class="peer sr-only">
                        <div class="p-6 text-center border-2 border-gray-200 rounded-xl peer-checked:border-red-500 peer-checked:bg-red-50 peer-checked:text-red-700 transition-all">
                            <svg class="w-12 h-12 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"></path></svg>
                            <span class="text-2xl font-bold">PENGELUARAN</span>
                        </div>
                    </label>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-gray-600 font-semibold mb-2 text-lg">Tanggal Transaksi</label>
                        <input type="date" name="date" value="{{ date('Y-m-d') }}" class="w-full p-4 border-2 border-gray-300 rounded-lg focus:outline-none focus:border-blue-600 text-xl" required>
                    </div>
                    <div>
                        <label class="block text-gray-600 font-semibold mb-2 text-lg">Jumlah (Rp)</label>
                        <input type="number" name="amount" placeholder="0" class="w-full p-4 border-2 border-gray-300 rounded-lg focus:outline-none focus:border-blue-600 text-xl font-mono" required>
                    </div>
                </div>

                <div>
                    <label class="block text-gray-600 font-semibold mb-2 text-lg">Nama Penyetor / Penerima</label>
                    <input type="text" name="name" placeholder="Contoh: Budi / Toko Jaya" class="w-full p-4 border-2 border-gray-300 rounded-lg focus:outline-none focus:border-blue-600 text-xl" required>
                </div>

                <div>
                    <label class="block text-gray-600 font-semibold mb-2 text-lg">Keterangan (Opsional)</label>
                    <textarea name="description" rows="3" placeholder="Detail transaksi..." class="w-full p-4 border-2 border-gray-300 rounded-lg focus:outline-none focus:border-blue-600 text-lg"></textarea>
                </div>

                <button type="submit" class="w-full bg-blue-900 hover:bg-blue-800 text-white font-bold py-5 px-6 rounded-xl text-2xl shadow-lg transform transition hover:scale-[1.01]">
                    SIMPAN & KIRIM WA
                </button>
            </form>
        </div>
        <div class="bg-gray-50 p-4 text-center text-gray-400">
            &copy; 2026 Sancaka Express Financial System
        </div>
    </div>
</body>
</html>
