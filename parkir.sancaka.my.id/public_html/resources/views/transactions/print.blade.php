<?php
// Susun teks murni dengan bahasa format RawBT
$text = "AZKEN PARKIR\n";
$text .= "Jl. Dr. Wahidin No. 18A, Ngawi\n";
$text .= "Nomor WA 085 745 808 809\n";
$text .= "--------------------------------\n";
$text .= "No. Plat : " . $transaction->plate_number . "\n";
$text .= "Jenis    : " . ucfirst($transaction->vehicle_type) . "\n";
$text .= "Masuk    : " . $transaction->entry_time->format('d/m/Y H:i') . "\n";
$text .= "--------------------------------\n";

// Ajaibnya RawBT: Kode [QR] di bawah ini otomatis diubah jadi gambar QR Code oleh printer!
$text .= "[QR]" . $transaction->id . "\n";

$text .= "TRX-" . str_pad($transaction->id, 5, '0', STR_PAD_LEFT) . "\n";
$text .= "--------------------------------\n";
$text .= "Simpan karcis ini sebagai\n";
$text .= "bukti parkir yang sah.\n";
$text .= "Terima Kasih Ya Kak.\n";

// Tambahkan spasi kosong di bawah agar kertas agak naik saat dipotong
$text .= "\n\n";

echo $text;
?>
