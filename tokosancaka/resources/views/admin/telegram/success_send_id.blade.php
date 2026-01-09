<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sukses Kirim Data - Sancaka Admin</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">

    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">

    <div class="w-full max-w-md bg-white rounded-3xl shadow-2xl overflow-hidden transform transition-all duration-300 hover:scale-[1.01]">
        
        <div class="bg-gradient-to-br from-green-500 to-emerald-600 p-8 text-center relative overflow-hidden">
            <div class="absolute top-0 left-0 w-full h-full bg-white opacity-5 pointer-events-none" 
                 style="background-image: radial-gradient(#ffffff 1px, transparent 1px); background-size: 20px 20px;">
            </div>

            <div class="w-20 h-20 bg-white/20 backdrop-blur-sm rounded-full flex items-center justify-center mx-auto mb-4 shadow-inner border border-white/30">
                <i class="fas fa-check text-3xl text-white"></i>
            </div>
            
            <h1 class="text-2xl font-bold text-white mb-1">Berhasil Dikirim!</h1>
            <p class="text-green-100 text-sm">Data akun telah terkirim ke WhatsApp Customer.</p>
        </div>

        <div class="p-6 space-y-6">
            
            <div class="text-center pb-4 border-b border-gray-100">
                <p class="text-xs font-bold text-gray-400 tracking-widest uppercase mb-2">DETAIL CUSTOMER</p>
                <div class="inline-block bg-green-50 text-green-700 px-6 py-2 rounded-xl border border-green-100">
                    <span class="text-sm text-green-600 block">ID Pengguna</span>
                    <span class="text-3xl font-mono font-bold tracking-tighter">{{ $user->id_pengguna }}</span>
                </div>
            </div>

            <div class="space-y-4">
                <div class="flex items-center space-x-4">
                    <div class="w-10 h-10 rounded-full bg-gray-50 flex items-center justify-center text-gray-400">
                        <i class="fas fa-user"></i>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 uppercase font-semibold">Nama Lengkap</p>
                        <p class="text-gray-800 font-bold">{{ strtoupper($user->nama_lengkap) }}</p>
                    </div>
                </div>

                <div class="flex items-center space-x-4">
                    <div class="w-10 h-10 rounded-full bg-gray-50 flex items-center justify-center text-gray-400">
                        <i class="fas fa-store"></i>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 uppercase font-semibold">Nama Toko</p>
                        <p class="text-gray-800 font-semibold">{{ $user->store_name }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-gray-50 p-3 rounded-xl border border-gray-100">
                        <p class="text-xs text-gray-400 uppercase font-semibold mb-1">WhatsApp</p>
                        <div class="flex items-center text-green-600 font-bold">
                            <i class="fab fa-whatsapp mr-1.5"></i> {{ $user->no_wa }}
                        </div>
                    </div>
                    <div class="bg-gray-50 p-3 rounded-xl border border-gray-100">
                        <p class="text-xs text-gray-400 uppercase font-semibold mb-1">Sisa Saldo</p>
                        <div class="text-blue-600 font-bold">
                            Rp {{ number_format($user->saldo, 0, ',', '.') }}
                        </div>
                    </div>
                </div>

                <div class="pt-2">
                    <p class="text-xs text-gray-400 uppercase font-semibold mb-1 pl-1">Alamat Terdaftar</p>
                    <div class="bg-gray-50 p-4 rounded-xl text-sm text-gray-600 leading-relaxed border border-gray-100">
                        {{ $user->address_detail }}<br>
                        Desa {{ $user->village }}, Kec. {{ $user->district }}<br>
                        Kab. {{ $user->regency }}, {{ $user->province }} ({{ $user->postal_code }})
                    </div>
                </div>
            </div>

            <div class="pt-4">
                <button onclick="window.close()" 
                        class="w-full bg-gray-800 hover:bg-gray-900 text-white font-medium py-3 px-4 rounded-xl transition duration-200 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 flex items-center justify-center">
                    <i class="fas fa-times mr-2"></i> Tutup Halaman
                </button>
                <p class="text-center text-gray-400 text-xs mt-4">
                    &copy; {{ date('Y') }} Sancaka Express System
                </p>
            </div>

        </div>
    </div>

</body>
</html>