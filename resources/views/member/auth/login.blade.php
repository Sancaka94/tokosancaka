<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Login Member - Sancaka</title>
    
    <link rel="icon" href="https://tokosancaka.com/storage/uploads/sancaka.png" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        /* Mencegah zoom pada input di iOS */
        input, select, textarea { font-size: 16px; }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 min-h-screen flex items-center justify-center p-4 font-sans">

    <div class="w-full max-w-[400px] bg-white rounded-3xl shadow-2xl overflow-hidden border border-slate-100 relative">
        
        <div class="absolute top-0 left-0 w-full h-32 bg-gradient-to-r from-blue-600 to-indigo-700 rounded-b-[50%] scale-x-150 -translate-y-16 z-0"></div>

        <div class="relative z-10 px-8 pt-12 pb-8">
            
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center p-2 bg-white rounded-2xl shadow-lg mb-4">
                    <img src="https://tokosancaka.com/storage/uploads/sancaka.png" class="h-12 w-12 object-contain" alt="Logo Sancaka">
                </div>
                <h1 class="text-2xl font-black text-slate-800 tracking-tight">Member Area</h1>
                <p class="text-sm text-slate-500 font-medium">Masuk untuk kelola pesanan & komisi</p>
            </div>

            @if ($errors->any())
                <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-3 rounded-r-lg shadow-sm animate-pulse">
                    <div class="flex items-start gap-3">
                        <i class="fas fa-exclamation-circle text-red-500 mt-0.5"></i>
                        <div>
                            <p class="text-xs font-bold text-red-700">Gagal Masuk</p>
                            <p class="text-[11px] text-red-600">{{ $errors->first() }}</p>
                        </div>
                    </div>
                </div>
            @endif

            <form action="{{ route('member.login.post') }}" method="POST" class="space-y-5">
                @csrf
                
                <div class="group">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5 ml-1">Nomor WhatsApp</label>
                    <div class="relative transition-all duration-300 group-focus-within:scale-[1.02]">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <i class="fab fa-whatsapp text-lg text-slate-400 group-focus-within:text-green-500 transition-colors"></i>
                        </div>
                        <input type="number" name="whatsapp" value="{{ old('whatsapp') }}" placeholder="Contoh: 08123456789" required autofocus
                               class="w-full pl-12 pr-4 py-3.5 bg-slate-50 border border-slate-200 text-slate-800 text-sm font-bold rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 focus:bg-white transition-all placeholder-slate-300 shadow-inner">
                    </div>
                </div>

                <div class="group" x-data="{ show: false }">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5 ml-1">PIN Keamanan</label>
                    <div class="relative transition-all duration-300 group-focus-within:scale-[1.02]">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <i class="fas fa-lock text-lg text-slate-400 group-focus-within:text-blue-500 transition-colors"></i>
                        </div>
                        <input :type="show ? 'text' : 'password'" name="pin" placeholder="Masukkan 6 digit PIN" maxlength="6" required
                               class="w-full pl-12 pr-12 py-3.5 bg-slate-50 border border-slate-200 text-slate-800 text-sm font-black tracking-widest rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 focus:bg-white transition-all placeholder-slate-300 shadow-inner">
                        
                        <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 pr-4 flex items-center text-slate-400 hover:text-slate-600 cursor-pointer">
                            <i class="fas" :class="show ? 'fa-eye-slash' : 'fa-eye'"></i>
                        </button>
                    </div>
                </div>

                <div class="flex items-center justify-between mt-2">
                    <label class="flex items-center gap-2 cursor-pointer group">
                        <input type="checkbox" name="remember" class="w-4 h-4 text-blue-600 rounded border-slate-300 focus:ring-blue-500 cursor-pointer">
                        <span class="text-xs text-slate-500 group-hover:text-slate-700 transition">Ingat Saya</span>
                    </label>
                    <a href="#" class="text-xs font-bold text-blue-600 hover:text-blue-800 hover:underline transition">Lupa PIN?</a>
                </div>

                <button type="submit" class="w-full py-4 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-bold rounded-xl shadow-lg shadow-blue-200 transform transition active:scale-[0.98] flex items-center justify-center gap-2">
                    <span>MASUK SEKARANG</span>
                    <i class="fas fa-arrow-right"></i>
                </button>

            </form>
        </div>

        <div class="bg-slate-50 px-8 py-4 border-t border-slate-100 text-center">
            <p class="text-xs text-slate-400">
                Belum terdaftar? 
                <a href="https://wa.me/6285745808809" class="font-bold text-slate-600 hover:text-blue-600 transition">Hubungi Admin</a>
            </p>
        </div>
    </div>

    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

</body>
</html>