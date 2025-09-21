{{-- resources/views/admin/pesanan/cetak_thermal.blade.php --}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Resi - {{ $pesanan->resi }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #E5E7EB; }
        .page {
            width: 80mm; min-height: 150mm;
            padding: 3mm; margin: 5mm auto;
            background: white; box-shadow: 0 0 3px rgba(0,0,0,0.1);
            display: flex; flex-direction: column;
            font-size: 9pt;
        }
        .barcode { width: 100%; height: 45px; }
        @media print {
            body { background: none; }
            .no-print { display: none; }
            .page { margin: 0; box-shadow: none; width: 100%; height: auto; }
        }
    </style>
</head>
<body>

{{-- Tombol print & kembali --}}
<div class="no-print p-3 text-center bg-white border-b border-gray-200 shadow-sm sticky top-0 z-10">
    @php
        $backUrl = route('home');
        if (Auth::check()) {
            if (Auth::user()->hasRole('Admin')) $backUrl = route('admin.pesanan.index');
            elseif (Auth::user()->hasRole('Pelanggan')) $backUrl = route('customer.pesanan.index');
        }
    @endphp
    <button onclick="window.print()" class="bg-indigo-600 text-white px-5 py-2 rounded-md shadow hover:bg-indigo-700 transition">🖨 Cetak Resi</button>
    <a href="{{ $backUrl }}" class="ml-2 bg-gray-200 text-gray-800 px-5 py-2 rounded-md shadow hover:bg-gray-300 transition">⬅ Kembali</a>
</div>

<div class="page">
    {{-- Header logo --}}
    <div class="flex justify-between items-center border-b border-dashed border-black pb-1">
        <img src="https://tokosancaka.biz.id/storage/uploads/sancaka.png" alt="Sancaka Express" class="h-8" onerror="this.style.display='none'">
        <span class="text-xs font-semibold">{{ strtoupper(explode('-', $pesanan->expedition)[1] ?? '') }}</span>
    </div>

    {{-- Barcode utama --}}
    <div class="text-center mt-1">
        <p class="font-bold">RESI SANCAKA</p>
        <svg id="barcodeSancaka" class="barcode"></svg>
    </div>

    {{-- Pengirim & penerima --}}
    <div class="grid grid-cols-2 gap-2 mt-1 border-b border-dashed border-black pb-1">
        <div class="pr-2 border-r border-dashed border-black">
            <p class="font-bold">PENGIRIM</p>
            <p class="font-semibold">{{ $pesanan->sender_name }}</p>
            <p>{{ $pesanan->sender_phone }}</p>
            <p>{{ implode(', ', array_filter([$pesanan->sender_address,$pesanan->sender_village,$pesanan->sender_district,$pesanan->sender_regency,$pesanan->sender_province,$pesanan->sender_postal_code])) }}</p>
        </div>
        <div>
            <p class="font-bold">PENERIMA</p>
            <p class="font-semibold">{{ $pesanan->nama_pembeli }}</p>
            <p>{{ $pesanan->telepon_pembeli }}</p>
            <p>{{ implode(', ', array_filter([$pesanan->alamat_pengiriman,$pesanan->receiver_village,$pesanan->receiver_district,$pesanan->receiver_regency,$pesanan->receiver_province,$pesanan->receiver_postal_code])) }}</p>
        </div>
    </div>

    {{-- Info singkat --}}
    <div class="grid grid-cols-3 text-center mt-1 border-b border-dashed border-black pb-1">
        <div><p class="font-bold">ORDER ID</p><p>{{ $pesanan->nomor_invoice }}</p></div>
        <div><p class="font-bold">BERAT</p><p>{{ $pesanan->weight }} gr</p></div>
        <div><p class="font-bold">VOLUME</p><p>{{ $pesanan->length ?? 0 }}x{{ $pesanan->width ?? 0 }}x{{ $pesanan->height ?? 0 }} cm</p></div>
        <div><p class="font-bold">LAYANAN</p><p>{{ strtoupper($pesanan->service_type) }}</p></div>
        <div><p class="font-bold">EKSPEDISI</p><p>{{ strtoupper(explode('-', $pesanan->expedition)[1]) }}</p></div>
    </div>

    {{-- COD jika ada --}}
    @if($pesanan->payment_method == 'COD' || $pesanan->payment_method == 'CODBARANG')
    <div class="text-center mt-1 border-b border-dashed border-black pb-1">
        <p class="font-bold">HARGA COD</p>
        <p>Rp {{ number_format($pesanan->price, 0, ',', '.') }}</p>
    </div>
    @endif

    {{-- Resi aktual jika ada --}}
    @if($pesanan->resi_aktual)
    <div class="text-center mt-1 border-b border-dashed border-black pt-1">
        <p class="font-bold">RESI AKTUAL ({{ $pesanan->jasa_ekspedisi_aktual }})</p>
        <svg id="barcodeAktual" class="barcode"></svg>
    </div>
    @endif

    {{-- ✅ Tambahan detail paket --}}
    <div class="mt-2 text-xs leading-tight border-t border-dashed border-black pt-1">
        <p><strong>Ekspedisi:</strong> {{ strtoupper(explode('-', $pesanan->expedition)[1] ?? '') }}</p>
        <p><strong>Detail Paket:</strong></p>
        <ul class="list-disc ml-4">
            <li>Nama Barang: {{ $pesanan->item_name ?? '-' }}</li>
            <li>Jumlah: {{ $pesanan->item_qty ?? 1 }}</li>
            <li>Berat: {{ $pesanan->weight }} gr</li>
            <li>Volume: {{ $pesanan->length }}x{{ $pesanan->width }}x{{ $pesanan->height }} cm</li>
        </ul>

        <p class="mt-1"><strong>Pengirim:</strong> {{ $pesanan->sender_name }} ({{ $pesanan->sender_phone }})</p>
        <p><strong>Penerima:</strong> {{ $pesanan->nama_pembeli }} ({{ $pesanan->telepon_pembeli }})</p>
        <p><strong>No Resi:</strong> {{ $pesanan->resi }}</p>

        <div class="text-center mt-2">
            {{-- Barcode 2D QR Code --}}
            <img src="data:image/png;base64, {!! base64_encode(QrCode::format('png')->size(120)->generate($pesanan->resi)) !!}" alt="QR Code Resi" class="mx-auto">
        </div>
    </div>

    {{-- Footer --}}
    <div class="mt-auto pt-2 text-center text-xs border-t border-dashed border-black">
        <p>Terima kasih telah menggunakan layanan Sancaka Express.</p>
        <p class="font-bold">{{ \Carbon\Carbon::parse($pesanan->created_at)->format('d M Y H:i') }}</p>
    </div>
</div>

{{-- Script Barcode --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    try {
        const resiSancaka = {!! json_encode($pesanan->resi ?? '') !!};
        if (resiSancaka) {
            JsBarcode("#barcodeSancaka", resiSancaka, {
                format: "CODE128", textMargin: 0, fontOptions: "bold", height: 40, width: 2
            });
        }
        @if($pesanan->resi_aktual)
        const resiAktual = {!! json_encode($pesanan->resi_aktual ?? '') !!};
        if (resiAktual) {
            JsBarcode("#barcodeAktual", resiAktual, {
                format: "CODE128", textMargin: 0, fontOptions: "bold", height: 40, width: 2
            });
        }
        @endif
    } catch (e) {
        console.error("Gagal membuat barcode:", e);
    }
});
</script>
</body>
</html>
