@extends('layouts.marketplace')

@section('content')
<div class="min-h-screen bg-gray-50 py-10 font-sans">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <div class="mb-8 text-center">
            <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">Top Up & Tagihan</h1>
            <p class="mt-2 text-sm text-gray-500">Beli Pulsa, Paket Data, dan Token Listrik otomatis langsung masuk.</p>
        </div>

        @if(session('success'))
        <div class="mb-6 p-4 bg-green-100 border-l-4 border-green-500 text-green-700 rounded shadow-sm flex items-center">
            <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <span class="font-medium">{{ session('success') }}</span>
        </div>
        @endif

        @if(session('error'))
        <div class="mb-6 p-4 bg-red-100 border-l-4 border-red-500 text-red-700 rounded shadow-sm flex items-center">
            <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <span class="font-medium">{{ session('error') }}</span>
        </div>
        @endif

        @if($errors->any())
        <div class="mb-6 p-4 bg-red-100 border-l-4 border-red-500 text-red-700 rounded shadow-sm">
            <ul class="list-disc list-inside text-sm">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <div class="bg-white shadow-xl rounded-2xl overflow-hidden border border-gray-100">
            <form action="{{ route('ppob.pay') }}" method="POST" class="p-6 sm:p-8" id="formPpob">
                @csrf
                
                <div class="mb-8">
                    <label for="primary_param" class="block text-sm font-bold text-gray-700 mb-2">Nomor HP / Tujuan</label>
                    <div class="relative rounded-xl shadow-sm">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <input type="number" name="primary_param" id="primary_param" value="{{ old('primary_param') }}" 
                            class="focus:ring-blue-500 focus:border-blue-500 block w-full pl-12 pr-4 py-3.5 sm:text-base border-gray-300 rounded-xl bg-gray-50 transition-colors duration-200" 
                            placeholder="Contoh: 081234567890" required>
                    </div>
                    <p class="mt-2 text-xs text-gray-500">Pastikan nomor tujuan sudah benar dan aktif.</p>
                </div>

                <hr class="border-gray-200 mb-8">

                <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <h3 class="text-lg font-bold text-gray-900">Pilih Nominal</h3>
                    
                    <div class="relative w-full sm:w-64">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-4 w-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input type="text" id="searchProduct" class="focus:ring-blue-500 focus:border-blue-500 block w-full pl-9 pr-3 py-2 text-sm border-gray-300 rounded-lg" placeholder="Cari pulsa, data, provider...">
                    </div>
                </div>
                
                <div class="mb-8 max-h-[400px] overflow-y-auto pr-2 pb-2 custom-scrollbar">
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4" id="productGrid">
                        @forelse($products as $product)
                        <label class="product-card-item cursor-pointer h-full" data-search="{{ strtolower($product->provider . ' ' . $product->product_type . ' ' . $product->price_value) }}">
                            <input type="radio" name="product_id" value="{{ $product->product_id }}" class="peer sr-only" required>
                            
                            <div class="h-full rounded-xl border-2 border-gray-100 bg-white p-4 hover:bg-gray-50 hover:border-gray-200 peer-checked:border-blue-500 peer-checked:bg-blue-50 peer-checked:shadow-md transition-all duration-200 flex flex-col justify-between">
                                
                                <div class="flex items-start space-x-3 mb-3">
                                    <div class="flex-shrink-0">
                                        @php
                                            // Warna dinamis berdasarkan nama provider (Opsional, agar lebih cantik)
                                            $color = match(strtolower($product->provider)) {
                                                'telkomsel' => 'e11d48', // Rose 600
                                                'indosat' => 'f59e0b',   // Amber 500
                                                'xl', 'axis' => '0284c7', // Sky 600
                                                'tri', 'three' => '000000', // Black
                                                'smartfren' => 'be185d', // Pink 700
                                                'pln' => '0ea5e9',       // Sky 500
                                                default => '4f46e5'      // Indigo 600
                                            };
                                        @endphp
                                        <img src="https://ui-avatars.com/api/?name={{ urlencode($product->provider) }}&background={{ $color }}&color=fff&rounded=true&bold=true&size=128" 
                                             alt="{{ $product->provider }}" 
                                             class="w-10 h-10 rounded-full shadow-sm">
                                    </div>
                                    
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-bold text-gray-900 truncate uppercase">
                                            {{ str_replace('_', ' ', $product->product_type) }}
                                        </p>
                                        <p class="text-xs text-gray-500 truncate capitalize">
                                            {{ $product->provider }}
                                        </p>
                                    </div>
                                </div>
                                
                                <div class="mt-2 flex items-center justify-between">
                                    <p class="text-lg font-black text-gray-900">
                                        Rp {{ number_format($product->price_value, 0, ',', '.') }}
                                    </p>
                                    <div class="hidden peer-checked:block text-blue-600">
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                                    </div>
                                </div>
                            </div>
                        </label>
                        @empty
                        <div class="col-span-full py-10 flex flex-col items-center justify-center border-2 border-dashed border-gray-300 rounded-xl bg-gray-50">
                            <svg class="w-12 h-12 text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
                            <span class="text-gray-500 font-medium">Produk belum tersedia saat ini.</span>
                        </div>
                        @endforelse
                    </div>

                    <div id="noResultMsg" class="hidden py-8 text-center text-gray-500">
                        Pencarian tidak menemukan hasil. Coba kata kunci lain.
                    </div>
                </div>

                <hr class="border-gray-200 mb-8">

                <div class="mb-8">
                    <label class="block text-sm font-bold text-gray-700 mb-3">Metode Pembayaran</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                            </svg>
                        </div>
                        <select name="payment_method" id="payment_method" class="focus:ring-blue-500 focus:border-blue-500 block w-full pl-12 pr-10 py-3.5 sm:text-base border-gray-300 rounded-xl bg-gray-50 font-medium text-gray-700 transition-colors duration-200" required>
                            <option value="" disabled selected>-- Pilih Metode Pembayaran --</option>
                            <optgroup label="Sistem Internal">
                                <option value="POTONG SALDO">💰 Saldo Sancaka</option>
                            </optgroup>
                            <optgroup label="Virtual Account (Otomatis)">
                                <option value="BCAVA">BCA Virtual Account</option>
                                <option value="BRIVA">BRI Virtual Account</option>
                                <option value="MANDIRIVA">Mandiri Virtual Account</option>
                                <option value="MYBVA">Maybank Virtual Account</option>
                                <option value="PERMATAVA">Permata Virtual Account</option>
                            </optgroup>
                            <optgroup label="E-Wallet & QRIS">
                                <option value="QRISC">QRIS (ShopeePay, GoPay, DANA, LinkAja)</option>
                                <option value="OVO">OVO</option>
                            </optgroup>
                            <optgroup label="Minimarket">
                                <option value="ALFAMART">Alfamart / Alfamidi</option>
                                <option value="INDOMARET">Indomaret</option>
                            </optgroup>
                        </select>
                    </div>
                </div>

                <div class="pt-2">
                    <button type="submit" id="btnSubmit" class="w-full flex justify-center items-center py-4 px-4 border border-transparent rounded-xl shadow-md text-base font-bold text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                        Bayar Sekarang
                    </button>
                    <p class="mt-3 text-center text-xs text-gray-400">
                        Dengan menekan tombol di atas, Anda menyetujui Syarat & Ketentuan TokoSancaka.
                    </p>
                </div>
            </form>
        </div>
        
    </div>
</div>

<style>
    .custom-scrollbar::-webkit-scrollbar {
        width: 6px;
    }
    .custom-scrollbar::-webkit-scrollbar-track {
        background: #f1f1f1; 
        border-radius: 10px;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: #cbd5e1; 
        border-radius: 10px;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
        background: #94a3b8; 
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const searchInput = document.getElementById('searchProduct');
        const productCards = document.querySelectorAll('.product-card-item');
        const noResultMsg = document.getElementById('noResultMsg');

        searchInput.addEventListener('input', function (e) {
            const searchTerm = e.target.value.toLowerCase().trim();
            let visibleCount = 0;

            productCards.forEach(card => {
                // Mengambil string pencarian dari atribut data-search yang sudah kita buat di blade
                const searchString = card.getAttribute('data-search');
                
                if (searchString.includes(searchTerm)) {
                    card.classList.remove('hidden');
                    visibleCount++;
                } else {
                    card.classList.add('hidden');
                }
            });

            // Tampilkan pesan jika tidak ada produk yang cocok
            if (visibleCount === 0 && productCards.length > 0) {
                noResultMsg.classList.remove('hidden');
            } else {
                noResultMsg.classList.add('hidden');
            }
        });
    });
</script>
@endsection