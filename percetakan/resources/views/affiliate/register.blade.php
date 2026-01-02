<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Afiliasi - Raih Komisi Jutaan</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-slate-50 font-sans text-slate-800">

    <div class="bg-gradient-to-r from-slate-900 to-slate-800 text-white pt-16 pb-24 px-6 text-center rounded-b-[3rem] shadow-2xl relative overflow-hidden">
        <div class="absolute top-0 left-0 w-full h-full opacity-10">
            <i class="fas fa-coins text-[200px] absolute -left-10 top-10 rotate-12"></i>
            <i class="fas fa-wallet text-[150px] absolute right-10 bottom-10 -rotate-12"></i>
        </div>

        <div class="relative z-10 max-w-3xl mx-auto">
            <span class="bg-amber-400 text-slate-900 px-4 py-1 rounded-full text-xs font-black uppercase tracking-widest mb-4 inline-block">Program Partner Resmi</span>
            <h1 class="text-3xl md:text-5xl font-black mb-4 leading-tight">Ubah Jejaring Anda Menjadi <span class="text-amber-400">Mesin Uang</span></h1>
            <p class="text-slate-300 text-lg mb-8">Daftar gratis sekarang, dapatkan kode unik otomatis via WhatsApp, dan mulai hasilkan komisi dari setiap penjualan aplikasi kasir kami.</p>
        </div>
    </div>

    <div class="max-w-xl mx-auto -mt-16 px-6 relative z-20 pb-20">
        
        @if(session('success'))
        <div class="bg-emerald-500 text-white p-4 rounded-2xl mb-6 shadow-lg flex items-center gap-3">
            <i class="fas fa-check-circle text-2xl"></i>
            <div>
                <h4 class="font-bold">Berhasil!</h4>
                <p class="text-sm opacity-90">{{ session('success') }}</p>
            </div>
        </div>
        @endif

        @if(session('error'))
        <div class="bg-red-500 text-white p-4 rounded-2xl mb-6 shadow-lg">
            {{ session('error') }}
        </div>
        @endif

        <div class="bg-white rounded-3xl shadow-xl border border-slate-100 p-8">
            <h2 class="text-2xl font-bold mb-6 text-slate-800">Formulir Pendaftaran</h2>
            
            <form action="{{ route('affiliate.store') }}" method="POST">
                @csrf
                
                <div class="mb-5">
                    <label class="block text-sm font-bold text-slate-500 mb-2 uppercase tracking-wide">Nama Lengkap</label>
                    <div class="relative">
                        <i class="fas fa-user absolute left-4 top-3.5 text-slate-400"></i>
                        <input type="text" name="name" required class="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-slate-800 focus:border-slate-800 transition" placeholder="Contoh: Budi Santoso" value="{{ old('name') }}">
                    </div>
                </div>

                <div class="mb-5">
                    <label class="block text-sm font-bold text-slate-500 mb-2 uppercase tracking-wide">Nomor WhatsApp</label>
                    <div class="relative">
                        <i class="fab fa-whatsapp absolute left-4 top-3.5 text-slate-400 text-lg"></i>
                        <input type="number" name="whatsapp" required class="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-slate-800 focus:border-slate-800 transition" placeholder="Contoh: 08123456789" value="{{ old('whatsapp') }}">
                    </div>
                    <p class="text-[11px] text-slate-400 mt-1 italic">*Kode kupon akan dikirim ke nomor ini. Pastikan aktif.</p>
                </div>

                <div class="mb-5">
                    <label class="block text-sm font-bold text-slate-500 mb-2 uppercase tracking-wide">Alamat Lengkap</label>
                    <textarea name="address" required rows="3" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-slate-800 focus:border-slate-800 transition" placeholder="Jalan, Kelurahan, Kecamatan, Kota...">{{ old('address') }}</textarea>
                </div>

                <div class="border-t border-slate-100 my-6 pt-6">
                    <h3 class="font-bold text-slate-800 mb-4 flex items-center gap-2">
                        <i class="fas fa-money-check-alt text-amber-500"></i> Info Rekening (Untuk Pencairan)
                    </h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="mb-2">
                            <label class="block text-xs font-bold text-slate-500 mb-2">Nama Bank / E-Wallet</label>
                            <input type="text" name="bank_name" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm" placeholder="BCA / DANA / OVO" value="{{ old('bank_name') }}">
                        </div>

                        <div class="mb-2">
                            <label class="block text-xs font-bold text-slate-500 mb-2">Nomor Rekening</label>
                            <input type="number" name="bank_account_number" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm" placeholder="xxxx-xxxx-xxxx" value="{{ old('bank_account_number') }}">
                        </div>
                    </div>
                </div>

                <button type="submit" class="w-full bg-slate-900 text-white font-bold py-4 rounded-xl shadow-lg hover:bg-black hover:scale-[1.02] transition transform duration-200 flex items-center justify-center gap-2">
                    Daftar Sekarang <i class="fas fa-arrow-right"></i>
                </button>

                <p class="text-center text-xs text-slate-400 mt-6">
                    Dengan mendaftar, Anda menyetujui Syarat & Ketentuan program afiliasi kami.
                </p>
            </form>
        </div>

        <div class="mt-12 text-center max-w-lg mx-auto">
            <h3 class="font-bold text-slate-800 mb-4">Cara Kerja</h3>
            <div class="flex justify-center gap-8 text-sm text-slate-600">
                <div class="flex flex-col items-center gap-2">
                    <div class="w-10 h-10 rounded-full bg-amber-100 text-amber-600 flex items-center justify-center font-bold">1</div>
                    <span>Daftar</span>
                </div>
                <div class="w-8 h-0.5 bg-slate-200 mt-5"></div>
                <div class="flex flex-col items-center gap-2">
                    <div class="w-10 h-10 rounded-full bg-amber-100 text-amber-600 flex items-center justify-center font-bold">2</div>
                    <span>Dapat Kode</span>
                </div>
                <div class="w-8 h-0.5 bg-slate-200 mt-5"></div>
                <div class="flex flex-col items-center gap-2">
                    <div class="w-10 h-10 rounded-full bg-amber-100 text-amber-600 flex items-center justify-center font-bold">3</div>
                    <span>Sebar & Cuan</span>
                </div>
            </div>
        </div>
    </div>

</body>
</html>