{{--
|--------------------------------------------------------------------------
| Partial untuk Tampilan Keranjang Kosong
|--------------------------------------------------------------------------
|
| File ini akan ditampilkan di halaman keranjang ketika sesi 'cart'
| milik pengguna kosong.
|
--}}

<div class="flex flex-col items-center justify-center py-16 px-6 text-center">
    {{-- Ikon Keranjang Kosong (Menggunakan SVG Inisial) --}}
    <svg class="w-24 h-24 text-gray-300 mb-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" />
    </svg>

    <h2 class="text-2xl font-semibold text-gray-700 mb-3">Keranjang Belanja Anda Kosong</h2>
    
    <p class="text-gray-500 mb-8 max-w-md">
        Sepertinya Anda belum menambahkan produk apapun ke keranjang. Yuk, cari produk yang Anda butuhkan!
    </p>

    {{-- Tombol untuk kembali ke halaman etalase/produk --}}
    <a href="https://tokosancaka.com/etalase" 
       class="inline-block bg-red-600 text-white font-medium py-3 px-6 rounded-lg shadow-md hover:bg-red-700 transition-colors duration-300 ease-in-out focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
        Mulai Belanja
    </a>
</div>
