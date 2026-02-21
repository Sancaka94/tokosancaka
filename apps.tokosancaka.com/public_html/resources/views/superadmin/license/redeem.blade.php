<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aktivasi SancakaPOS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-slate-50 min-h-screen flex items-center justify-center p-4 font-sans text-slate-900">

    <div class="max-w-2xl w-full">
        <div class="text-center mb-8">
            <img src="https://tokosancaka.com/storage/uploads/sancaka.png" class="h-14 mx-auto mb-4" alt="Logo">
            <h1 class="text-3xl font-extrabold tracking-tight">Sancaka<span class="text-blue-600">POS</span></h1>
            <p class="text-slate-500 mt-2 font-medium">Aktivasi layanan untuk subdomain: <span class="text-blue-600 font-bold underline">{{ request('subdomain') }}</span></p>
        </div>

        <div class="grid md:grid-cols-5 gap-6">
            <div class="md:col-span-2 space-y-4">
                <div class="bg-white p-6 rounded-[2rem] shadow-xl border border-slate-100">
                    <h3 class="text-sm font-bold uppercase tracking-widest text-slate-400 mb-4">Punya Kode?</h3>
                    <form action="{{ route('public.license.process') }}" method="POST">
                        @csrf
                        <input type="hidden" name="target_subdomain" value="{{ request('subdomain') }}">
                        <input type="text" name="license_code" class="w-full px-4 py-4 bg-slate-50 border-2 border-slate-100 rounded-2xl mb-4 focus:ring-2 focus:ring-blue-500 uppercase font-mono text-center" placeholder="KODE-LISENSI">
                        <button type="submit" class="w-full py-3 bg-slate-800 text-white rounded-xl font-bold hover:bg-black transition-all">Aktivasi Kode</button>
                    </form>
                </div>
            </div>

            <div class="md:col-span-3 space-y-4">
                <h3 class="text-sm font-bold uppercase tracking-widest text-slate-400 ml-2">Pilih Paket Langganan</h3>

                @php
                    $packages = [
                        ['name' => 'Bulanan', 'price' => 50000, 'label' => 'monthly', 'icon' => 'fa-calendar-day', 'color' => 'blue'],
                        ['name' => '6 Bulan', 'price' => 250000, 'label' => 'half_year', 'icon' => 'fa-box', 'color' => 'purple'],
                        ['name' => 'Tahunan', 'price' => 450000, 'label' => 'yearly', 'icon' => 'fa-crown', 'color' => 'amber'],
                    ];
                @endphp

                @foreach($packages as $pkg)
                <form action="{{ route('tenant.payment.generate') }}" method="POST">
                    @csrf
                    <input type="hidden" name="amount" value="{{ $pkg['price'] }}">
                    <input type="hidden" name="target_subdomain" value="{{ request('subdomain') }}">
                    <input type="hidden" name="package_type" value="{{ $pkg['label'] }}">

                    <button type="submit" class="w-full flex items-center justify-between p-5 bg-white border-2 border-slate-100 rounded-[2rem] hover:border-{{ $pkg['color'] }}-500 hover:shadow-lg transition-all group">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-{{ $pkg['color'] }}-50 rounded-2xl flex items-center justify-center text-{{ $pkg['color'] }}-600">
                                <i class="fas {{ $pkg['icon'] }} text-xl"></i>
                            </div>
                            <div class="text-left">
                                <p class="font-bold text-slate-800">{{ $pkg['name'] }}</p>
                                <p class="text-xs text-slate-400 font-bold">Rp {{ number_format($pkg['price'], 0, ',', '.') }}</p>
                            </div>
                        </div>
                        <div class="bg-slate-100 px-3 py-1 rounded-full group-hover:bg-{{ $pkg['color'] }}-600 group-hover:text-white transition-all">
                            <i class="fas fa-chevron-right text-[10px]"></i>
                        </div>
                    </button>
                </form>
                @endforeach
            </div>
        </div>

        @if(session('error'))
        <div class="mt-6 p-4 bg-red-50 text-red-700 rounded-2xl border-l-4 border-red-500 font-bold text-sm">
            <i class="fas fa-exclamation-circle mr-2"></i> {{ session('error') }}
        </div>
        @endif
    </div>
</body>
</html>
