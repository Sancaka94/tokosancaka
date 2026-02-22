<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aktivasi Layanan - Sancaka POS</title>

    <link rel="icon" href="https://tokosancaka.com/storage/uploads/logo.jpeg" type="image/jpeg">
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;900&display=swap" rel="stylesheet">

    <style>
        body { font-family: 'Inter', sans-serif; }
        [x-cloak] { display: none !important; }
        .bg-sancaka-blue { background-color: #1e3a8a; }
        .text-sancaka-blue { color: #1e3a8a; }
    </style>
</head>

@php
    // LOGIKA PENGECEKAN STATUS DATABASE
    $subdomain = request()->query('subdomain');
    $isActive = false;
    $expiredDate = null;

    if ($subdomain) {
        try {
            // Cek status tenant langsung di database mysql_second
            $tenant = \Illuminate\Support\Facades\DB::table('tenants')
                        ->table('tenants')
                        ->where('subdomain', $subdomain)
                        ->first();

            // Pastikan status aktif dan expired_at masih di masa depan
            if ($tenant && $tenant->status === 'active' && $tenant->expired_at) {
                $expired = \Carbon\Carbon::parse($tenant->expired_at);
                if ($expired->isFuture()) {
                    $isActive = true;
                    $expiredDate = $expired->locale('id')->translatedFormat('d F Y, H:i');
                }
            }
        } catch (\Exception $e) {
            // Abaikan error koneksi agar halaman tetap bisa dimuat
        }
    }
@endphp

<body class="bg-gray-50 text-gray-800"
      x-data="{
          tab: 'license',
          subdomain: '{{ $subdomain }}'
      }">

    <nav class="fixed top-0 w-full bg-white border-b border-gray-200 z-50 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-20 items-center">
                <div class="flex items-center gap-3">
                    <img src="https://tokosancaka.com/storage/uploads/logo.jpeg" alt="Logo Sancaka" class="h-10 w-10 rounded-lg object-cover border border-gray-200">
                    <div>
                        <h1 class="text-xl font-bold text-sancaka-blue tracking-tight">SANCAKA POS</h1>
                        <p class="text-[10px] text-gray-500 font-semibold tracking-widest uppercase">Smart Business Solution</p>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <div class="min-h-screen pt-32 pb-20 flex items-center justify-center px-4">
        <div class="max-w-4xl w-full bg-white rounded-3xl shadow-2xl overflow-hidden flex flex-col md:flex-row border border-gray-100">

            <div class="md:w-5/12 bg-sancaka-blue p-10 text-white flex flex-col justify-between relative overflow-hidden">
                <div class="relative z-10">
                    <h3 class="text-3xl font-black leading-tight mb-6 text-white">Satu langkah lagi untuk aktivasi bisnis Anda.</h3>
                    <p class="text-blue-200 text-sm leading-relaxed mb-8">Kelola bisnis jadi lebih cepat dan efisien dengan SancakaPOS.</p>

                    <div class="space-y-4">
                        <div class="flex items-center gap-3 text-sm text-blue-100 font-medium">
                            <i data-lucide="check-circle" class="w-5 h-5 text-blue-400"></i> Aktivasi Real-time
                        </div>
                        <div class="flex items-center gap-3 text-sm text-blue-100 font-medium">
                            <i data-lucide="check-circle" class="w-5 h-5 text-blue-400"></i> Dukungan Teknis 24/7
                        </div>
                    </div>
                </div>
                <p class="relative z-10 text-[10px] text-blue-400 font-bold tracking-widest uppercase mt-10">© 2026 CV. Sancaka Karya Hutama</p>
            </div>

            <div class="md:w-7/12 p-8 md:p-12">

                @if(session('success'))
                    <div class="mb-6 p-4 bg-green-50 border-l-4 border-green-500 text-green-700 rounded-r-xl text-sm font-bold flex items-center gap-2 animate-pulse">
                        <i data-lucide="info" class="w-5 h-5"></i> {{ session('success') }}
                    </div>
                @endif

                @if($isActive)
                    <div class="text-center py-6">
                        <div class="w-24 h-24 bg-green-50 text-green-500 rounded-full flex items-center justify-center mx-auto mb-6 border-4 border-green-100">
                            <i data-lucide="check-circle-2" class="w-12 h-12"></i>
                        </div>
                        <h2 class="text-2xl font-black text-gray-900 mb-2">Lisensi Telah Aktif!</h2>
                        <p class="text-sm text-gray-500 mb-8 leading-relaxed">
                            Toko <span class="text-sancaka-blue font-bold">{{ $subdomain }}.tokosancaka.com</span> sudah berhasil diperpanjang dan siap digunakan hingga <br>
                            <strong class="text-gray-800 text-base">{{ $expiredDate }}</strong>.
                        </p>

                        <a href="https://{{ $subdomain }}.tokosancaka.com/login" class="flex justify-center items-center gap-2 w-full bg-sancaka-blue text-white py-4 rounded-2xl font-bold shadow-lg hover:bg-blue-800 transition transform active:scale-[0.98]">
                            Buka Aplikasi Toko <i data-lucide="external-link" class="w-5 h-5"></i>
                        </a>
                    </div>
                @else
                    <div class="mb-8">
                        <h2 class="text-2xl font-black text-gray-900 mb-2">Aktivasi Layanan</h2>
                        <p class="text-sm text-gray-500 font-medium">Subdomain: <span class="text-sancaka-blue font-bold" x-text="subdomain + '.tokosancaka.com'"></span></p>
                    </div>

                    <div class="flex bg-gray-100 p-1.5 rounded-2xl mb-8">
                        <button @click="tab = 'license'"
                                :class="tab === 'license' ? 'bg-white text-sancaka-blue shadow-sm' : 'text-gray-500'"
                                class="flex-1 py-3 rounded-xl text-xs font-black uppercase tracking-wider transition-all duration-300">
                            Punya Kode
                        </button>
                        <button @click="tab = 'payment'"
                                :class="tab === 'payment' ? 'bg-white text-sancaka-blue shadow-sm' : 'text-gray-500'"
                                class="flex-1 py-3 rounded-xl text-xs font-black uppercase tracking-wider transition-all duration-300">
                            Beli Lisensi
                        </button>
                    </div>

                    <div x-show="tab === 'license'" x-transition x-cloak>
                        <form action="{{ route('public.license.process') }}" method="POST">
                            @csrf
                            <input type="hidden" name="target_subdomain" :value="subdomain">
                            <div class="mb-6">
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Kode Lisensi Anda</label>
                                <input type="text" name="license_code" required
                                       class="w-full px-5 py-4 bg-gray-50 border border-gray-200 rounded-2xl focus:ring-2 focus:ring-blue-500 outline-none font-mono text-center text-xl tracking-[0.2em] uppercase shadow-inner"
                                       placeholder="XXXX-XXXX-XXXX">
                            </div>
                            <button type="submit" class="w-full bg-sancaka-blue text-white py-4 rounded-2xl font-bold shadow-lg hover:opacity-90 transition transform active:scale-[0.98]">
                                AKTIFKAN SEKARANG
                            </button>
                        </form>
                    </div>

                    <div x-show="tab === 'payment'" x-transition x-cloak class="space-y-4">
                        @php
                            $packages = [
                                ['name' => 'Monthly Plan', 'price' => 50000, 'val' => 'monthly', 'icon' => 'zap'],
                                ['name' => 'Half Year Plan', 'price' => 600000, 'val' => 'half_year', 'icon' => 'award'],
                                ['name' => 'Yearly Plan', 'price' => 1000000, 'val' => 'yearly', 'icon' => 'crown']
                            ];
                        @endphp

                        @foreach($packages as $pkg)
                        <form action="{{ route('tenant.payment.generate') }}" method="POST">
                            @csrf
                            <input type="hidden" name="amount" value="{{ $pkg['price'] }}">
                            <input type="hidden" name="target_subdomain" :value="subdomain">
                            <input type="hidden" name="package_type" value="{{ $pkg['val'] }}">
                            <input type="hidden" name="payment_method" value="DOKU">

                            <button type="submit" class="w-full p-4 border border-gray-200 bg-gray-50 rounded-2xl flex items-center justify-between hover:border-blue-300 hover:bg-blue-50 transition group">
                                <div class="flex items-center gap-4">
                                    <div class="bg-white p-3 rounded-xl shadow-sm text-sancaka-blue">
                                        <i data-lucide="{{ $pkg['icon'] }}" class="w-5 h-5"></i>
                                    </div>
                                    <div class="text-left text-sm">
                                        <h4 class="font-bold text-gray-800">{{ $pkg['name'] }}</h4>
                                        <p class="text-gray-500 font-semibold">Rp {{ number_format($pkg['price'], 0, ',', '.') }}</p>
                                    </div>
                                </div>
                                <i data-lucide="chevron-right" class="w-4 h-4 text-gray-300 group-hover:text-sancaka-blue transition"></i>
                            </button>
                        </form>
                        @endforeach
                    </div>

                    @if(session('error'))
                        <div class="mt-6 p-4 bg-red-50 text-red-700 rounded-xl text-xs font-bold border-l-4 border-red-600">
                            {{ session('error') }}
                        </div>
                    @endif
                @endif

            </div>
        </div>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
