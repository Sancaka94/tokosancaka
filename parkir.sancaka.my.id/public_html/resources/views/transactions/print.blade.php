<?php
// PASTIKAN TIDAK ADA TAG HTML SAMA SEKALI DI FILE INI
// HANYA KODE PHP INI SAJA DARI BARIS 1 SAMPAI BAWAH

$plat = strtoupper($transaction->plate_number);

// Cek awalan plat untuk menentukan kategori
$isSepedaListrik = str_starts_with($plat, 'SPL-');
$isPegawaiRSUD   = str_starts_with($plat, 'RSUD-');

// --- HEADER GLOBAL ---
$text = "AZKEN PARKIR\n";
$text .= "Jl. Dr. Wahidin No. 18A, Ngawi\n";
$text .= "Nomor WA 085 745 808 809\n";
$text .= "--------------------------------\n";

// --- ISI STRUK (BERBEDA TIAP MODEL) ---
if ($isSepedaListrik) {

    // MODEL 1: SEPEDA LISTRIK
    $text .= "     *** SEPEDA LISTRIK *** \n";
    $text .= "--------------------------------\n";
    $text .= "ID Parkir: " . $plat . "\n";
    $text .= "Masuk    : " . \Carbon\Carbon::parse($transaction->entry_time)->format('d/m/Y H:i') . "\n";

} elseif ($isPegawaiRSUD) {

    // MODEL 2: PEGAWAI RSUD
    $text .= "      *** PEGAWAI RSUD *** \n";
    $text .= "--------------------------------\n";
    $text .= "ID Parkir: " . $plat . "\n";
    $text .= "Masuk    : " . \Carbon\Carbon::parse($transaction->entry_time)->format('d/m/Y H:i') . "\n";

} else {

    // MODEL 3: CUSTOMER UMUM
    $text .= "No. Plat : " . $plat . "\n";
    $text .= "Jenis    : " . ucfirst($transaction->vehicle_type) . "\n";
    $text .= "Masuk    : " . \Carbon\Carbon::parse($transaction->entry_time)->format('d/m/Y H:i') . "\n";

}

$text .= "--------------------------------\n";

// --- QR CODE & TRX ID GLOBAL ---
// Tag QR dipisah dan ditaruh sendirian agar terbaca sistem RawBT
$text .= "Nomor-Id-" . $transaction->id . "\n";

$text .= "TRX-" . str_pad($transaction->id, 5, '0', STR_PAD_LEFT) . "\n";
$text .= "--------------------------------\n";

// --- FOOTER (BERBEDA TIAP MODEL) ---
if ($isPegawaiRSUD) {
    $text .= "Karcis Khusus Pegawai RSUD.\n";
    $text .= "Harap ditunjukkan saat keluar.\n";
} else {
    $text .= "Simpan karcis ini sebagai\n";
    $text .= "bukti parkir yang sah.\n";
}

$text .= "Terima Kasih.\n";

// Spasi kosong untuk jarak potong kertas
//$text .= "\n";

echo $text;
?>
