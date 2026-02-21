<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aktivasi Lisensi - SancakaPOS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-slate-50 min-h-screen flex items-center justify-center p-4">

    <div class="max-w-md w-full">
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-blue-600 rounded-2xl shadow-lg shadow-blue-200 mb-4">
                <img src="https://tokosancaka.com/storage/uploads/sancaka.png" class="h-10 w-10 object-contain invert" alt="Logo">
            </div>
            <h1 class="text-2xl font-bold text-slate-800">Sancaka<span class="text-blue-600">POS</span></h1>
            <p class="text-slate-500 text-sm mt-1">Sistem Aktivasi Layanan Mandiri</p>
        </div>

        <div class="bg-white rounded-3xl shadow-xl shadow-slate-200/60 border border-slate-100 overflow-hidden">
            <div class="px-8 py-6 bg-slate-50/50 border-b border-slate-100">
                <h2 class="text-xl font-bold text-slate-800">Aktivasi Lisensi</h2>
                <p class="text-xs text-slate-500 mt-1 uppercase tracking-wider font-semibold">
                    Subdomain: <span class="text-blue-600">{{ request('subdomain') ?? 'None' }}</span>
                </p>
            </div>

            <div class="p-8">
                @if (session('success'))
                    <div class="mb-6 p-4 bg-emerald-50 border-l-4 border-emerald-500 text-emerald-700 rounded-r-xl flex gap-3 items-center">
                        <i class="fas fa-check-circle text-emerald-500"></i>
                        <p class="text-sm font-medium">{{ session('success') }}</p>
                    </div>
                @endif

                @if (session('error'))
                    <div class="mb-6 p-4 bg-rose-50 border-l-4 border-rose-500 text-rose-700 rounded-r-xl flex gap-3 items-center">
                        <i class="fas fa-exclamation-triangle text-rose-500"></i>
                        <p class="text-sm font-medium">{{ session('error') }}</p>
                    </div>
                @endif

                <form action="{{ route('public.license.process') }}" method="POST">
                    @csrf
                    <input type="hidden" name="target_subdomain" value="{{ request('subdomain') }}">

                    <div class="mb-6">
                        <label class="block text-sm font-bold text-slate-700 mb-2 ml-1 text-left">Punya Kode Lisensi?</label>
                        <input type="text" name="license_code"
                               class="w-full px-4 py-4 border-2 border-slate-100 bg-slate-50 rounded-2xl focus:ring-4 focus:ring-blue-100 focus:border-blue-500 focus:bg-white uppercase font-mono text-center tracking-[0.2em] text-xl shadow-inner transition-all outline-none"
                               placeholder="XXXX-XXXX-XXXX-XXXX" required>
                    </div>

                    <button type="submit" class="w-full py-4 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-2xl shadow-lg shadow-blue-200 transition-all transform active:scale-[0.98] flex items-center justify-center gap-2">
                        <i class="fas fa-key"></i> Aktifkan Sekarang
                    </button>
                </form>

                <div class="relative my-10 text-center">
                    <hr class="border-slate-100">
                    <span class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 bg-white px-4 text-xs font-bold text-slate-400 uppercase tracking-widest">Atau</span>
                </div>

                <div class="space-y-4">
                    <p class="text-center text-sm text-slate-600 mb-4 font-medium text-left ml-1">Belum punya kode? Beli Lisensi via DOKU</p>

                    <form action="{{ route('tenant.payment.generate') }}" method="POST">
                        @csrf
                        <input type="hidden" name="amount" value="150000">
                        <input type="hidden" name="payment_method" value="DOKU">

                        <button type="submit" class="w-full flex items-center justify-between p-4 border-2 border-slate-100 rounded-2xl hover:border-red-500 hover:bg-red-50/30 transition-all group">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 bg-white border border-slate-100 rounded-xl flex items-center justify-center shadow-sm group-hover:border-red-200">
                                    <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/1/1b/DOKU_Logo.png/800px-DOKU_Logo.png" class="h-4 object-contain" alt="DOKU">
                                </div>
                                <div class="text-left">
                                    <p class="text-sm font-bold text-slate-800 group-hover:text-red-600">Bayar via DOKU</p>
                                    <p class="text-[10px] text-slate-400 tracking-tight">Virtual Account, QRIS, & Retail Store</p>
                                </div>
                            </div>
                            <i class="fas fa-chevron-right text-slate-300 group-hover:text-red-500 transition-colors"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <p class="text-center text-slate-400 text-[10px] mt-8 font-medium italic">
            &copy; 2026 <strong>CV. Sancaka Karya Hutama</strong>. Digitalizing Your Business.
        </p>
    </div>

</body>
</html>
