<?php
// PASTIKAN TIDAK ADA TAG HTML SAMA SEKALI DI FILE INI
// HANYA KODE PHP INI SAJA DARI BARIS 1 SAMPAI BAWAH

$text = "AZKEN PARKIR\n";
$text .= "Jl. Dr. Wahidin No. 18A, Ngawi\n";
$text .= "Nomor WA 085 745 808 809\n";
$text .= "--------------------------------\n";
$text .= "No. Plat : " . $transaction->plate_number . "\n";
$text .= "Jenis    : " . ucfirst($transaction->vehicle_type) . "\n";
$text .= "Masuk    : " . \Carbon\Carbon::parse($transaction->entry_time)->format('d/m/Y H:i') . "\n";
$text .= "--------------------------------\n";

// Tag QR dipisah dan ditaruh sendirian agar terbaca sistem RawBT
$text .= "[QR]" . $transaction->id . "\n";

$text .= "TRX-" . str_pad($transaction->id, 5, '0', STR_PAD_LEFT) . "\n";
$text .= "--------------------------------\n";
$text .= "Simpan karcis ini sebagai\n";
$text .= "bukti parkir yang sah.\n";
$text .= "Terima Kasih.\n";

// Spasi kosong untuk jarak potong kertas
$text .= "\n\n\n";

echo $text;
?>
