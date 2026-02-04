{{-- File: resources/views/layouts/partials/customer/footer.blade.php --}}
<div class="flex flex-col md:flex-row items-center justify-between w-full">
    
    {{-- Bagian Kiri: Logo & Copyright --}}
    <div class="flex items-center space-x-3 mb-1 md:mb-0">
        {{-- Logo: Diperkecil jadi h-5 (sekitar 20px) --}}
        <img src="https://tokosancaka.com/storage/uploads/sancaka.png" 
             alt="Logo Sancaka" 
             class="h-5 w-auto object-contain">
        
        {{-- Pembatas Vertikal --}}
        <div class="h-4 w-px bg-gray-300 mx-2"></div>
        
        {{-- Teks Copyright --}}
        <p class="text-xs text-gray-500 font-medium">
            &copy; {{ date('Y') }} <span class="text-blue-900 font-bold">Sancaka Express</span> 
            <span class="hidden sm:inline text-gray-400">— @sancakaexpress</span>
        </p>
    </div>

    {{-- Bagian Kanan: Social Media Icons --}}
    <div class="flex items-center space-x-4">
        <a href="#" class="text-gray-400 hover:text-blue-600 transition-colors duration-200">
            <i class="fab fa-facebook text-sm"></i>
        </a>
        <a href="#" class="text-gray-400 hover:text-blue-400 transition-colors duration-200">
            <i class="fab fa-twitter text-sm"></i>
        </a>
        <a href="#" class="text-gray-400 hover:text-pink-600 transition-colors duration-200">
            <i class="fab fa-instagram text-sm"></i>
        </a>
    </div>

</div>