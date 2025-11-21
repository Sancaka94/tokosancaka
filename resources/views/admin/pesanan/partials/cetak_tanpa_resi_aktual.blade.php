{{-- TAMPILAN RESI TUNGGAL --}}
<div class="flex-grow flex flex-col">
    <div class="flex justify-between items-start border-b-2 border-black pb-2">
        <img src="https://sancaka.bisnis.pro/wp-content/uploads/sites/5/2024/10/WhatsApp_Image_2024-10-08_at_10.14.16-removebg-preview.png" alt="Logo Sancaka" class="h-12">
        <img src="{{ $partnerLogoUrl }}" alt="Logo Rekanan" class="h-10 object-contain">
    </div>

    <div class="grid grid-cols-2 gap-4 my-4">
        <div class="text-xs border-r pr-2">
            <p class="font-bold uppercase mb-1">PENGIRIM</p>
            <p class="font-semibold">{{ $order->sender_name }}</p>
            <p>{{ $order->sender_phone }}</p>
            <p class="mt-1">{{ $order->sender_address }}</p>
        </div>
        <div class="text-xs">
            <p class="font-bold uppercase mb-1">PENERIMA</p>
            <p class="font-semibold">{{ $order->receiver_name }}</p>
            <p>{{ $order->receiver_phone }}</p>
            <p class="mt-1">{{ $order->receiver_address }}</p>
        </div>
    </div>

    <div class="border-t border-b py-2 text-xs grid grid-cols-3 gap-2 text-center">
        <div>
            <p class="font-bold">BERAT</p>
            <p>{{ $order->weight }} gr</p>
        </div>
        <div>
            <p class="font-bold">VOLUME</p>
            <p>{{ $order->length ?? 0 }}x{{ $order->width ?? 0 }}x{{ $order->height ?? 0 }} cm</p>
        </div>
        <div>
            <p class="font-bold">LAYANAN</p>
            <p>{{ $order->service_type }}</p>
        </div>
    </div>
    
    <div class="flex-grow"></div> <!-- Spacer -->

    <div class="mt-4 text-center">
        <p class="text-xs font-semibold">RESI SANCAKA</p>
        <svg class="barcode" jsbarcode-value="{{ $order->resi }}"></svg>
    </div>
    <p class="text-center text-xs mt-2">Terima kasih telah menggunakan layanan jasa pengiriman Sancaka Express.</p>
</div>
