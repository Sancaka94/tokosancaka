<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan Akun - Sancaka Affiliate</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-slate-50 font-sans text-slate-800">

    <div class="bg-slate-900 text-white py-6 px-6 shadow-md">
        <div class="max-w-4xl mx-auto flex justify-between items-center">
            <div class="flex items-center gap-3">
                <i class="fas fa-id-card-clip text-amber-400 text-2xl"></i>
                <div>
                    <h1 class="font-bold text-lg leading-tight">Member Area</h1>
                    <p class="text-xs text-slate-400">Kelola Profil & Keamanan</p>
                </div>
            </div>
            <a href="{{ route('logout') }}" class="text-xs bg-red-600 hover:bg-red-700 px-4 py-2 rounded-lg font-bold transition">
                Logout <i class="fas fa-sign-out-alt ml-1"></i>
            </a>
        </div>
    </div>

    <div class="max-w-4xl mx-auto px-6 py-8">

        @if(session('success'))
        <div class="bg-emerald-100 border-l-4 border-emerald-500 text-emerald-700 p-4 mb-6 rounded shadow-sm flex items-center justify-between">
            <div class="flex items-center gap-2">
                <i class="fas fa-check-circle"></i>
                <span>{{ session('success') }}</span>
            </div>
        </div>
        @endif

        @if($errors->any())
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded shadow-sm">
            <ul class="list-disc list-inside text-sm">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

            <div class="md:col-span-1 space-y-4">
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200 text-center">
                    <div class="w-20 h-20 bg-slate-100 rounded-full mx-auto flex items-center justify-center text-3xl text-slate-400 mb-3">
                        <i class="fas fa-user"></i>
                    </div>
                    <h2 class="font-bold text-lg">{{ $affiliate->name ?? 'Nama Member' }}</h2>
                    <p class="text-sm text-slate-500 mb-4">{{ $affiliate->whatsapp ?? '-' }}</p>
                    <div class="inline-block bg-amber-100 text-amber-700 px-3 py-1 rounded-full text-xs font-bold">
                        Saldo: Rp {{ number_format($affiliate->balance ?? 0, 0, ',', '.') }}
                    </div>
                </div>
            </div>

            <div class="md:col-span-2 space-y-6">

                <div class="bg-white rounded-2xl shadow-lg border border-slate-100 overflow-hidden" x-data="{ editing: false }">
                    <div class="bg-slate-50 px-6 py-4 border-b border-slate-100 flex justify-between items-center">
                        <h3 class="font-bold text-slate-800 flex items-center gap-2">
                            <i class="fas fa-user-edit text-blue-500"></i> Data Profil & Kontak
                        </h3>
                    </div>
                    
                    <div class="p-6">
                        <form action="{{ route('affiliate.update_profile') }}" method="POST">
                            @csrf
                            @method('PUT')

                            <div class="grid grid-cols-1 gap-5">
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Nama Lengkap</label>
                                    <input type="text" name="name" value="{{ old('name', $affiliate->name) }}" class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none transition font-medium">
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Nomor WhatsApp (Aktif)</label>
                                    <div class="relative">
                                        <span class="absolute left-3 top-2.5 text-slate-400"><i class="fab fa-whatsapp"></i></span>
                                        <input type="number" name="whatsapp" value="{{ old('whatsapp', $affiliate->whatsapp) }}" class="w-full pl-9 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-green-500 focus:outline-none transition font-medium">
                                    </div>
                                    <p class="text-[10px] text-slate-400 mt-1">Mengubah nomor ini akan mengubah tujuan pengiriman notifikasi komisi.</p>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Alamat</label>
                                    <textarea name="address" rows="2" class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none transition">{{ old('address', $affiliate->address) }}</textarea>
                                </div>

                                <div class="grid grid-cols-2 gap-4 pt-2 border-t border-slate-100">
                                    <div>
                                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Bank / E-Wallet</label>
                                        <input type="text" name="bank_name" value="{{ old('bank_name', $affiliate->bank_name) }}" placeholder="Contoh: BCA" class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 transition">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">No. Rekening</label>
                                        <input type="number" name="bank_account_number" value="{{ old('bank_account_number', $affiliate->bank_account_number) }}" class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 transition">
                                    </div>
                                </div>
                            </div>

                            <div class="mt-6 text-right">
                                <button type="submit" class="bg-slate-800 text-white px-6 py-2 rounded-lg font-bold text-sm hover:bg-black transition shadow-md">
                                    <i class="fas fa-save mr-1"></i> Simpan Perubahan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-lg border border-slate-100 overflow-hidden" x-data="{ showPin: false }">
                    <div class="bg-slate-50 px-6 py-4 border-b border-slate-100 flex justify-between items-center">
                        <h3 class="font-bold text-slate-800 flex items-center gap-2">
                            <i class="fas fa-shield-alt text-red-500"></i> Keamanan PIN
                        </h3>
                        @if(empty($affiliate->pin))
                            <span class="bg-red-100 text-red-600 text-[10px] px-2 py-0.5 rounded-full font-bold uppercase">Belum Ada PIN</span>
                        @else
                            <span class="bg-emerald-100 text-emerald-600 text-[10px] px-2 py-0.5 rounded-full font-bold uppercase">PIN Aktif</span>
                        @endif
                    </div>

                    <div class="p-6">
                        <form action="{{ route('affiliate.update_pin') }}" method="POST">
                            @csrf
                            @method('PUT')

                            @if(empty($affiliate->pin))
                                <div class="text-center mb-6">
                                    <div class="inline-block p-3 bg-red-50 rounded-full text-red-500 mb-2">
                                        <i class="fas fa-lock-open text-2xl"></i>
                                    </div>
                                    <h4 class="font-bold text-slate-800">Buat PIN Keamanan</h4>
                                    <p class="text-sm text-slate-500">Lindungi saldo komisi Anda dengan 6 digit angka.</p>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">PIN Baru (6 Angka)</label>
                                        <input type="password" name="new_pin" required maxlength="6" pattern="[0-9]*" inputmode="numeric" class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-red-500 text-center tracking-widest font-bold text-lg">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Konfirmasi PIN</label>
                                        <input type="password" name="new_pin_confirmation" required maxlength="6" pattern="[0-9]*" inputmode="numeric" class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-red-500 text-center tracking-widest font-bold text-lg">
                                    </div>
                                </div>
                                
                                <button type="submit" class="w-full mt-6 bg-red-600 text-white px-6 py-3 rounded-xl font-bold text-sm hover:bg-red-700 transition shadow-md">
                                    Buat PIN Sekarang
                                </button>

                            @else
                                <div class="bg-yellow-50 border border-yellow-100 rounded-lg p-3 mb-4 flex items-start gap-3">
                                    <i class="fas fa-info-circle text-yellow-500 mt-0.5"></i>
                                    <p class="text-xs text-yellow-700 leading-relaxed">
                                        Masukkan PIN Lama untuk verifikasi sebelum membuat PIN Baru.
                                    </p>
                                </div>

                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">PIN Saat Ini</label>
                                        <div class="relative">
                                            <input :type="showPin ? 'text' : 'password'" name="current_pin" required maxlength="6" pattern="[0-9]*" inputmode="numeric" class="w-full px-4 py-2 bg-white border border-slate-300 rounded-lg focus:ring-2 focus:ring-red-500 tracking-widest font-bold">
                                            <button type="button" @click="showPin = !showPin" class="absolute right-3 top-2.5 text-slate-400 hover:text-slate-600">
                                                <i class="fas" :class="showPin ? 'fa-eye-slash' : 'fa-eye'"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <div class="border-t border-slate-100 my-4"></div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">PIN Baru</label>
                                            <input type="password" name="new_pin" required maxlength="6" pattern="[0-9]*" inputmode="numeric" class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-red-500 text-center tracking-widest font-bold">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Ulangi PIN Baru</label>
                                            <input type="password" name="new_pin_confirmation" required maxlength="6" pattern="[0-9]*" inputmode="numeric" class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-red-500 text-center tracking-widest font-bold">
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-6 text-right">
                                    <button type="submit" class="bg-red-600 text-white px-6 py-2 rounded-lg font-bold text-sm hover:bg-red-700 transition shadow-md">
                                        <i class="fas fa-key mr-1"></i> Update PIN
                                    </button>
                                </div>
                            @endif
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script>
        // Contoh sederhana validasi input hanya angka untuk PIN
        const pinInputs = document.querySelectorAll('input[type="password"], input[name="whatsapp"], input[name="bank_account_number"]');
        pinInputs.forEach(input => {
            input.addEventListener('input', function (e) {
                // Hapus karakter non-numeric jika field khusus angka
                if (this.name.includes('pin') || this.name.includes('whatsapp') || this.name.includes('number')) {
                    this.value = this.value.replace(/[^0-9]/g, '');
                }
            });
        });
    </script>
</body>
</html>