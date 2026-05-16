<!DOCTYPE html>
<html lang="id">
<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Akun Ditangguhkan - {{ $tenant->name ?? 'Pelanggan' }}</title>

    <link rel="icon" type="image/jpeg" href="https://tokosancaka.com/public/assets/logo.jpg">
    <link rel="shortcut icon" type="image/jpeg" href="https://tokosancaka.com/public/assets/logo.jpg">

    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap');
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f4f7fa; }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen p-4">

    <div class="max-w-4xl w-full bg-white rounded-3xl shadow-2xl overflow-hidden flex flex-col md:flex-row">

        {{-- KIRI: INFO SUSPEND & TIMER --}}
        <div class="md:w-1/3 bg-red-600 p-10 text-white flex flex-col justify-center items-center text-center relative overflow-hidden">

            {{-- TIMER HITUNG MUNDUR --}}
            @php
                // Ambil tanggal expired dari database, tambah 30 hari batas toleransi
                $batasHapus = isset($tenant->expired_at) 
                    ? \Carbon\Carbon::parse($tenant->expired_at)->addDays(30) 
                    : now()->addDays(30);
            @endphp
            

            <div x-data="{
                    expiry: '{{ $batasHapus->toIso8601String() }}',
                    days: '00', hours: '00', minutes: '00', seconds: '00',
                    distance: 0,
                    init() {
                        const target = new Date(this.expiry).getTime();
                        setInterval(() => {
                            const now = new Date().getTime();
                            this.distance = target - now;
                            if (this.distance < 0) {
                                this.days = '00'; this.hours = '00'; this.minutes = '00'; this.seconds = '00';
                            } else {
                                this.days = String(Math.floor(this.distance / (1000 * 60 * 60 * 24))).padStart(2, '0');
                                this.hours = String(Math.floor((this.distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60))).padStart(2, '0');
                                this.minutes = String(Math.floor((this.distance % (1000 * 60 * 60)) / (1000 * 60))).padStart(2, '0');
                                this.seconds = String(Math.floor((this.distance % (1000 * 60)) / 1000)).padStart(2, '0');
                            }
                        }, 1000);
                    }
                }" class="mb-8 w-full z-10 relative">

                <p class="text-red-200 text-xs font-bold uppercase tracking-widest mb-2">PENGHAPUSAN DATA ANDA:</p>
                <div class="flex justify-center gap-2 text-red-600">
                    <div class="bg-white rounded-lg p-2 w-14 shadow-lg"><span x-text="days" class="block text-xl font-black">00</span><span class="text-[9px] font-bold">HARI</span></div>
                    <div class="bg-white rounded-lg p-2 w-14 shadow-lg"><span x-text="hours" class="block text-xl font-black">00</span><span class="text-[9px] font-bold">JAM</span></div>
                    <div class="bg-white rounded-lg p-2 w-14 shadow-lg"><span x-text="minutes" class="block text-xl font-black">00</span><span class="text-[9px] font-bold">MENIT</span></div>
                    <div class="bg-white rounded-lg p-2 w-14 shadow-lg animate-pulse"><span x-text="seconds" class="block text-xl font-black">00</span><span class="text-[9px] font-bold">DETIK</span></div>
                </div>
            </div>

            <img src="https://tokosancaka.com/storage/uploads/logos/jWfpluPG2sSkvcvaOnYTNRqjizdUbSbeGKyv1F3A.jpg" alt="Logo" class="w-24 h-24 rounded-2xl mb-6 shadow-lg border-4 border-red-500 z-10 relative">

            <h2 class="text-2xl font-extrabold mb-2 uppercase italic tracking-tighter z-10 relative">
                {{ $tenant->name ?? 'Sancaka Tenant' }}
            </h2>

            <div class="inline-block px-4 py-1 bg-blue-700 rounded-full text-xs font-bold mb-6 z-10 relative">STATUS: SUSPENDED</div>

            <p class="text-red-100 text-sm leading-relaxed text-justify z-10 relative">
                Layanan dihentikan sementara karena masa sewa telah berakhir. Jika Anda tidak melakukan perpanjangan,
                <b>maka</b> akun dan database Anda akan kami hapus <b>permanen</b> dalam waktu 30 Hari sejak masa aktif ini
                <b>BERAKHIR</b>. Terima kasih.
            </p>
        </div>

        {{-- KANAN: PILIH PAKET --}}
        <div class="md:w-2/3 p-8 md:p-12">
            <h1 class="text-2xl font-bold text-gray-800 mb-6">Pilih Paket Perpanjangan</h1>

            <div class="grid grid-cols-1 gap-4 mb-8">
                <label class="relative flex items-center p-4 border-2 border-gray-100 rounded-2xl cursor-pointer hover:border-red-500 transition-all">
                    <input type="radio" name="plan" value="100000" class="w-5 h-5 text-red-600" checked onchange="updatePlan(100000)">
                    <div class="ml-4">
                        <span class="block font-bold text-gray-800">1 Bulan</span>
                        <span class="text-sm text-gray-500 italic">Rp 100.000 / bln</span>
                    </div>
                </label>

                <label class="relative flex items-center p-4 border-2 border-gray-100 rounded-2xl cursor-pointer hover:border-red-500 transition-all">
                    <input type="radio" name="plan" value="300000" class="w-5 h-5 text-red-600" onchange="updatePlan(300000)">
                    <div class="ml-4">
                        <span class="block font-bold text-gray-800">3 Bulan</span>
                        <span class="text-sm text-gray-500 italic">Rp 300.000</span>
                    </div>
                </label>

                <label class="relative flex items-center p-4 border-2 border-red-200 bg-red-100 rounded-2xl cursor-pointer hover:border-red-500 transition-all">
                    <input type="radio" name="plan" value="1000000" class="w-5 h-5 text-red-600" onchange="updatePlan(1000000)">
                    <div class="ml-4">
                        <span class="block font-bold text-gray-800">1 Tahun <span class="ml-2 bg-red-500 text-white text-[10px] px-2 py-0.5 rounded-full uppercase">Hemat 20%</span></span>
                        <span class="text-sm text-gray-500 italic">Rp 1.000.000</span>
                    </div>
                </label>
            </div>

            <div class="bg-gray-50 p-6 rounded-2xl mb-6 flex justify-between items-center border border-gray-100">
                <div>
                    <span class="text-xs text-gray-400 uppercase font-bold tracking-widest">Total Pembayaran</span>
                    <h3 id="display-price" class="text-3xl font-black text-gray-900 leading-none mt-1">Rp 100.000</h3>
                </div>
                <div class="text-right">
                    <img src="https://tokosancaka.com/storage/logo/doku-ewallet.png" alt="DOKU" class="h-8">
                </div>
            </div>

            <div class="flex flex-col gap-3">
                <a id="pay-button" href="#" onclick="processPayment(event)" class="w-full bg-red-600 text-white text-center font-bold py-4 rounded-xl hover:bg-red-700 transition shadow-lg">
                    BAYAR SEKARANG (Automatis)
                </a>

                <a href="https://wa.me/6285745808809?text=Halo%20Admin,%20saya%20butuh%20bantuan%20untuk%20perpanjangan%20paket." target="_blank" class="w-full bg-green-600 hover:bg-green-500 text-white text-center font-bold py-4 rounded-xl transition shadow-lg flex items-center justify-center gap-2">
                    <i class="fab fa-whatsapp text-xl"></i>
                    <span>Hubungi Admin (Bantuan)</span>
                </a>
            </div>

            <p id="waiting-text" class="hidden text-center text-xs text-amber-600 mt-3 font-bold animate-pulse">
                Menunggu Pembayaran... Halaman akan refresh otomatis.
            </p>
        </div>
    </div>

    {{-- SCRIPT: JAVASCRIPT MURNI --}}
    <script>
    let currentAmount = 100000;

    function updatePlan(amount) {
        currentAmount = amount;
        document.getElementById('display-price').innerText = 'Rp ' + amount.toLocaleString('id-ID');
    }

    async function processPayment(e) {
        e.preventDefault();

        const btn = document.getElementById('pay-button');
        const waitingText = document.getElementById('waiting-text');
        const originalText = btn.innerHTML;

        btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Memproses...';
        btn.classList.add('opacity-50', 'pointer-events-none');

        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        let packageStr = 'monthly';
        if (currentAmount === 300000) packageStr = 'quarterly';
        if (currentAmount === 1000000) packageStr = 'yearly';

        try {
            const response = await fetch('/tenant/generate-payment', {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json', 
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken 
                },
                body: JSON.stringify({ 
                    amount: currentAmount,
                    target_subdomain: '{{ $tenant->subdomain ?? "" }}' || window.location.hostname.split('.')[0], 
                    package_type: packageStr
                })
            });

            if (!response.ok) {
                if (response.status === 422) {
                    let errData = await response.json();
                    throw new Error("Validasi Gagal: " + JSON.stringify(errData.errors || errData.message));
                }
                throw new Error(`HTTP Error: ${response.status}`);
            }

            const res = await response.json();

            if(res.success) {
                window.open(res.url, '_blank');
                btn.innerHTML = 'MENUNGGU PEMBAYARAN...';
                waitingText.classList.remove('hidden');
                startPolling();
            } else {
                throw new Error(res.message || 'Gagal generate token');
            }

        } catch (error) {
            console.error(error);
            btn.innerHTML = originalText;
            btn.classList.remove('opacity-50', 'pointer-events-none');
            alert('Gagal: ' + error.message);
        }
    }

    function startPolling() {
        console.log("Mulai cek status...");
        let checkInterval = setInterval(async () => {
            try {
                let response = await fetch('/tenant/check-status');

                if (response.ok) {
                    let data = await response.json();
                    if (data.active) {
                        clearInterval(checkInterval);

                        Swal.fire({
                            icon: 'success',
                            title: 'LUNAS!',
                            text: 'Akun Anda aktif kembali.',
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => {
                            let subdomain = window.location.hostname.split('.')[0];
                            window.location.href = "https://" + subdomain + ".tokosancaka.com/dashboard";
                        });
                    }
                }
            } catch (err) {
                console.log("Server belum merespon status aktif...");
            }
        }, 3000);
    }
    </script>
</body>
</html>