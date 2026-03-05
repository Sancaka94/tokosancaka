<?php
// PASTIKAN TIDAK ADA TAG HTML SAMA SEKALI DI FILE INI

$plat = strtoupper($transaction->plate_number);

$isSepeda        = str_starts_with($plat, 'SPD-');
$isSepedaListrik = str_starts_with($plat, 'SPL-');
$isPegawaiRSUD   = str_starts_with($plat, 'RSUD-');

$text = "AZKEN PARKIR\n";
$text .= "Jl. Dr. Wahidin No. 18A, Ngawi\n";
$text .= "Nomor WA 085 745 808 809\n";
$text .= "--------------------------------\n";

if ($isSepeda) {
    $text .= "      *** SEPEDA BIASA *** \n";
    $text .= "--------------------------------\n";
    $text .= "ID Parkir: " . $plat . "\n";
    $text .= "Tarif    : Rp 2.000\n";
    $text .= "Masuk    : " . \Carbon\Carbon::parse($transaction->entry_time)->format('d/m/Y H:i') . "\n";
} elseif ($isSepedaListrik) {
    $text .= "     *** SEPEDA LISTRIK *** \n";
    $text .= "--------------------------------\n";
    $text .= "ID Parkir: " . $plat . "\n";
    $text .= "Masuk    : " . \Carbon\Carbon::parse($transaction->entry_time)->format('d/m/Y H:i') . "\n";
} elseif ($isPegawaiRSUD) {
    $text .= "      *** PEGAWAI RSUD *** \n";
    $text .= "--------------------------------\n";
    $text .= "ID Parkir: " . $plat . "\n";
    $text .= "Masuk    : " . \Carbon\Carbon::parse($transaction->entry_time)->format('d/m/Y H:i') . "\n";
} else {
    $text .= "No. Plat : " . $plat . "\n";
    $text .= "Jenis    : " . ucfirst($transaction->vehicle_type) . "\n";
    $text .= "Masuk    : " . \Carbon\Carbon::parse($transaction->entry_time)->format('d/m/Y H:i') . "\n";
}

$text .= "--------------------------------\n";
$text .= "Nomor-Id-" . $transaction->id . "\n";
$text .= "TRX-" . str_pad($transaction->id, 5, '0', STR_PAD_LEFT) . "\n";
$text .= "--------------------------------\n";

if ($isPegawaiRSUD) {
    $text .= "Karcis Khusus Pegawai RSUD.\n";
    $text .= "Harap ditunjukkan saat keluar.\n";
} else {
    $text .= "Simpan karcis ini sebagai\n";
    $text .= "bukti parkir yang sah.\n";
}

$text .= "Terima Kasih.\n";

echo $text;
?>
