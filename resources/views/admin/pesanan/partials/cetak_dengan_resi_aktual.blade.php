{{-- resources/views/admin/pesanan/partials/cetak_dengan_resi_aktual.blade.php --}}

{{-- BAGIAN ATAS (Untuk ditempel di paket) --}}
<div class="flex-grow flex flex-col border-b-2 border-dashed border-gray-400 pb-2">
    <div class="flex justify-between items-start">
        <img src="https://sancaka.bisnis.pro/wp-content/uploads/sites/5/2024/10/WhatsApp_Image_2024-10-08_at_10.14.16-removebg-preview.png" alt="Logo Sancaka" class="h-10">
        <img src="{{ $partnerLogoUrl }}" alt="Logo Rekanan" class="h-10 object-contain">
    </div>

    <div class="my-1 text-center">
        <p class="text-xs font-semibold">RESI AKTUAL</p>
        <svg class="barcode" jsbarcode-value="{{ $order->resi_aktual }}"></svg>
    </div>
    <div class="flex text-xs border-t border-b border-gray-300 py-1">
        <div class="w-1/2 border-r border-gray-300 pr-2">
            <p class="font-bold">PENGIRIM: {{ $order->sender_name }}</p>
            <p>{{ $order->sender_phone }}</p>
        </div>
        <div class="w-1/2 pl-2">
            <p class="font-bold">PENERIMA: {{ $order->receiver_name }}</p>
            <p>{{ $order->receiver_phone }}</p>
        </div>
    </div>
    <p class="text-xs mt-1 font-semibold leading-tight">ALAMAT PENERIMA: {{ $order->receiver_address }}</p>
    <div class="flex-grow"></div>
    <div class="flex justify-between items-end text-xs border-t border-gray-300 pt-1 mt-1">
        <div>
            <p><strong>Isi:</strong> {{ $order->item_description }}</p>
            <p><strong>Ref:</strong> CV. SANCAKA KARYA HUTAMA</p>
        </div>
        <div class="text-center">
            <p class="font-bold text-lg">{{ $order->weight / 1000 }} KG</p>
            <p class="text-gray-600">{{ $order->length ?? 10 }}x{{ $order->width ?? 10 }}x{{ $order->height ?? 10 }} cm</p>
        </div>
        <div class="text-center font-black text-2xl border-2 border-black p-1">1/1</div>
    </div>
</div>

{{-- BAGIAN BAWAH (Untuk arsip/pengirim) --}}
<div class="pt-2 flex flex-col">
    <div class="flex items-center gap-4">
        <img src="https://sancaka.bisnis.pro/wp-content/uploads/sites/5/2024/10/WhatsApp_Image_2024-10-08_at_10.14.16-removebg-preview.png" alt="Logo Sancaka" class="h-8">
        <p class="font-bold text-lg">{{ $order->service_type ?? 'REG' }}</p>
    </div>
    <div class="my-1">
        <p class="text-xs font-semibold center">RESI SANCAKA</p>
        <svg class="barcode" jsbarcode-value="{{ $order->resi }}"></svg>
    </div>
    <div class="text-xs grid grid-cols-2 gap-x-4">
        <div>
            <p><strong>Pengirim:</strong> {{ $order->sender_name }}</p>
            <p><strong>Penerima:</strong> {{ $order->receiver_name }}</p>
            <p><strong>Tujuan:</strong> {{ $order->tujuan }}</p>
        </div>
        <div class="text-right">
            {{-- ====================================================== --}}
            {{-- == PERBAIKAN LOGIKA UNTUK MENAMPILKAN ONGKIR == --}}
            {{-- ====================================================== --}}
            <p>
                <strong>Total Biaya:</strong> 
                @if(isset($order->total_ongkir) && $order->total_ongkir > 0)
                    <strong>Rp {{ number_format($order->total_ongkir, 0, ',', '.') }}</strong>
                @else
                    <strong>WA ADMIN</strong>
                @endif
            </p>
            <p><strong>Berat:</strong> {{ $order->weight / 1000 }} KG</p>
            <p><strong>Dibuat:</strong> {{ \Carbon\Carbon::parse($order->created_at)->format('d/m/Y') }}</p>
        </div>
    </div>
</div>
