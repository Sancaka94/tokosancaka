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
        .bg-sancaka-red { background-color: #dc2626; }
        .text-sancaka-blue { color: #1e3a8a; }
        .border-sancaka-blue { border-color: #1e3a8a; }
    </style>
</head>
<body class="bg-gray-50 text-gray-800"
      x-data="{
          tab: 'license',
          subdomain: '{{ request('subdomain') }}'
      }">

    <div class="min-h-screen flex items-center justify-center p-4 md:p-8">
        <div class="max-w-4xl w-full bg-white rounded-3xl shadow-2xl overflow-hidden flex flex-col md:flex-row border border-gray-100">

            <div class="md:w-5/12 bg-sancaka-blue p-10 text-white flex flex-col justify-between relative overflow-hidden">
                <div class="relative z-10">
                    <div class="flex items-center gap-3 mb-10">
                        <img src="https://tokosancaka.com/storage/uploads/logo.jpeg" class="h-10 w-10 rounded-lg border border-blue-700" alt="Logo">
                        <h2 class="text-xl font-black tracking-tight">SANCAKA POS</h2>
                    </div>

                    <h3 class="text-3xl font-bold leading-tight mb-6">Satu langkah lagi untuk mengaktifkan bisnis Anda.</h3>
                    <p class="text-blue-200 text-sm leading-relaxed mb-8">Pilih metode aktivasi yang Anda inginkan. Gunakan kode lisensi atau beli paket langganan secara instan.</p>

                    <div class="space-y-4">
                        <div class="flex items-start gap-3 text-sm text-blue-100">
                            <i data-lucide="check-circle-2" class="w-5 h-5 text-blue-400 mt-0.5"></i>
                            <span>Akses fitur premium lengkap</span>
                        </div>
                        <div class="flex items-start gap-3 text-sm text-blue-100">
                            <i data-lucide="check-circle-2" class="w-5 h-5 text-blue-400 mt-0.5"></i>
                            <span>Sinkronisasi data otomatis</span>
                        </div>
                    </div>
                </div>

                <div class="absolute -bottom-10 -left-10 w-40 h-40 bg-blue-800 rounded-full blur-3xl opacity-50"></div>
                <p class="relative z-10 text-[10px] text-blue-400 font-bold tracking-widest uppercase mt-10">© 2026 CV. Sancaka Karya Hutama</p>
            </div>

            <div class="md:w-7/12 p-8 md:p-12">
                <div class="mb-8">
                    <h2 class="text-2xl font-black text-gray-900 mb-2">Aktivasi Layanan</h2>
                    <p class="text-sm text-gray-500 font-medium">Subdomain: <span class="text-sancaka-blue font-bold">@{{ subdomain }}.tokosancaka.com</span></p>
                </div>

                <div class="flex bg-gray-100 p-1.5 rounded-2xl mb-8">
                    <button @click="tab = 'license'"
                            :class="tab === 'license' ? 'bg-white text-sancaka-blue shadow-sm' : 'text-gray-500 hover:text-gray-700'"
                            class="flex-1 py-3 rounded-xl text-xs font-black uppercase tracking-wider transition-all duration-300">
                        Input Kode
                    </button>
                    <button @click="tab = 'payment'"
                            :class="tab === 'payment' ? 'bg-white text-sancaka-blue shadow-sm' : 'text-gray-500 hover:text-gray-700'"
                            class="flex-1 py-3 rounded-xl text-xs font-black uppercase tracking-wider transition-all duration-300">
                        Beli Lisensi
                    </button>
                </div>

                <div x-show="tab === 'license'" x-transition:enter="duration-300 ease-out" x-transition:enter-start="opacity-0 translate-y-2">
                    <form action="{{ route('public.license.process') }}" method="POST">
                        @csrf
                        <input type="hidden" name="target_subdomain" :value="subdomain">

                        <div class="mb-6">
                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Masukkan Kode Lisensi</label>
                            <input type="text" name="license_code" required
                                   class="w-full px-5 py-4 bg-gray-50 border border-gray-200 rounded-2xl focus:ring-2 focus:ring-blue-500 outline-none font-mono text-center text-xl tracking-[0.2em] uppercase shadow-inner"
                                   placeholder="XXXX-XXXX-XXXX">
                        </div>

                        <button type="submit" class="w-full bg-sancaka-blue text-white py-4 rounded-2xl font-bold shadow-lg hover:bg-blue-800 transition transform active:scale-[0.98] flex items-center justify-center gap-2">
                            Validasi Lisensi <i data-lucide="arrow-right" class="w-5 h-5"></i>
                        </button>
                    </form>
                </div>

                <div x-show="tab === 'payment'" x-transition:enter="duration-300 ease-out" x-transition:enter-start="opacity-0 translate-y-2">
                    <div class="space-y-4">
                        @php
                            $packages = [
                                ['name' => 'Starter (1 bln)', 'price' => 100000, 'val' => 'monthly', 'icon' => 'zap'],
                                ['name' => 'Business (6 bln)', 'price' => 500000, 'val' => 'half_year', 'icon' => 'award'],
                                ['name' => 'Premium (1 thn)', 'price' => 1000000, 'val' => 'yearly', 'icon' => 'crown']
                            ];
                        @endphp

                        @foreach($packages as $pkg)
                        <form action="{{ route('tenant.payment.generate') }}" method="POST">
                            @csrf
                            <input type="hidden" name="amount" value="{{ $pkg['price'] }}">
                            <input type="hidden" name="target_subdomain" :value="subdomain">
                            <input type="hidden" name="package_type" value="{{ $pkg['val'] }}">
                            <input type="hidden" name="payment_method" value="DOKU">

                            <button type="submit" class="w-full p-4 border border-gray-100 bg-gray-50 rounded-2xl flex items-center justify-between hover:border-blue-300 hover:bg-blue-50 transition group">
                                <div class="flex items-center gap-4">
                                    <div class="bg-white p-3 rounded-xl shadow-sm text-sancaka-blue">
                                        <i data-lucide="{{ $pkg['icon'] }}" class="w-5 h-5"></i>
                                    </div>
                                    <div class="text-left">
                                        <h4 class="font-bold text-gray-800 text-sm">{{ $pkg['name'] }}</h4>
                                        <p class="text-xs text-gray-500 font-bold">Rp {{ number_format($pkg['price'], 0, ',', '.') }}</p>
                                    </div>
                                </div>
                                <i data-lucide="chevron-right" class="w-4 h-4 text-gray-300 group-hover:text-sancaka-blue group-hover:translate-x-1 transition"></i>
                            </button>
                        </form>
                        @endforeach
                    </div>
                </div>

                @if(session('error'))
                <div class="mt-6 p-4 bg-red-50 border-l-4 border-sancaka-red text-sancaka-red rounded-r-xl text-xs font-bold flex items-center gap-2">
                    <i data-lucide="alert-circle" class="w-4 h-4"></i> {{ session('error') }}
                </div>
                @endif
            </div>
        </div>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
