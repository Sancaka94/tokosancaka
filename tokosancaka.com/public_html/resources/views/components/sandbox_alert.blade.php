@php
    // Ambil status awal dari DB saat halaman pertama kali dimuat
    $initialMode = \App\Models\Api::getValue('KIRIMINAJA_MODE', 'global', 'staging');
@endphp

{{-- 
    PERUBAHAN REALTIME:
    1. Kita hapus @if($currentMode) PHP agar komponen selalu ada di DOM.
    2. Kita gunakan x-data AlpineJS untuk inisialisasi state berdasarkan $initialMode.
    3. Kita tambahkan listener Echo untuk menangkap event SystemModeUpdated secara LIVE.
    4. [FIX] Menambahkan interval check untuk memastikan window.Echo siap sebelum digunakan.
--}}
<div x-data="{ 
        show: '{{ $initialMode }}' === 'staging',
        init() {
            // Timer buat ngecek setiap 500ms (setengah detik)
            let checkSystemEcho = setInterval(() => {
                
                // PENTING: Cek Echo ada DAN pastikan .channel itu beneran fungsi
                if (window.Echo && typeof window.Echo.channel === 'function') {
                    
                    // Kalau udah siap, matiin timer-nya
                    clearInterval(checkSystemEcho);
                    
                    // Baru jalanin perintahnya
                    console.log('Echo System Banner Connected');
                    window.Echo.channel('global-system-channel')
                        .listen('SystemModeUpdated', (e) => {
                            console.log('System Mode Updated:', e.mode);
                            if (e.mode === 'staging') {
                                this.show = true;
                            } else {
                                this.show = false;
                            }
                        });
                }
            }, 500); 
        }
    }" 
     x-show="show" 
     x-cloak
     class="relative z-[9999]" 
     aria-labelledby="modal-title" 
     role="dialog" 
     aria-modal="true">
    
    <div x-show="show"
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-gray-600 bg-opacity-75 transition-opacity"></div>

    <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
            
            <div x-show="show"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg border border-yellow-200">
                
                <div class="bg-yellow-300 px-4 py-3 sm:px-6 flex justify-between items-center border-b border-yellow-300">
                    <h3 class="text-lg font-bold leading-6 text-yellow-800 flex items-center" id="modal-title">
                        <i class="fas fa-exclamation-triangle mr-2"></i> Informasi Layanan
                    </h3>
                    <button @click="show = false" type="button" class="text-gray-400 hover:text-gray-500 focus:outline-none">
                        <span class="sr-only">Close</span>
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <div class="px-4 py-6 sm:p-6 text-center">
                    <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-yellow-300 mb-5">
                        <i class="fas fa-tools text-yellow-600 text-4xl"></i>
                    </div>
                    
                    <h3 class="text-xl font-bold text-gray-900 mb-2">Layanan Pengiriman Sedang Perbaikan</h3>
                    
                    <p class="text-sm text-gray-500 mb-6 leading-relaxed">
                        Sistem pengiriman saat ini sedang dalam mode pemeliharaan (Maintenance) untuk peningkatan kualitas layanan.
                    </p>

                    <div class="inline-flex items-center px-5 py-2.5 rounded-full bg-yellow-300 border border-yellow-300 text-yellow-700 font-bold text-sm shadow-sm">
                        <i class="fas fa-clock mr-2"></i> Estimasi: 15 - 60 Menit Kedepan, Mohon Ditunggu !
                    </div>
                </div>

                <div class="bg-gray-50 px-4 py-4 sm:flex sm:flex-row-reverse sm:px-6 justify-center">
                    <button @click="show = false" type="button" class="inline-flex w-full justify-center rounded-full bg-red-700 px-8 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-red-500 sm:w-auto transition-all transform hover:scale-105 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-900">
                        Saya Mengerti
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>