/**
 * pesanan.js
 * File ini berisi logika JavaScript khusus untuk halaman "Data Pesanan".
 * Tugas utamanya adalah untuk menginisialisasi plugin DataTables.
 */
$(document).ready(function () {
    // Cek apakah tabel dengan ID #tabelPesanan ada di halaman ini
    // untuk menghindari error di halaman lain.
    if ($('#tabelPesanan').length) {
        
        // Inisialisasi DataTables
        $('#tabelPesanan').DataTable({
            // Menggunakan bahasa Indonesia untuk semua label
            "language": { 
                "url": "https://cdn.datatables.net/plug-ins/1.13.6/i18n/id.json" 
            },
            "paging": true,       // Aktifkan penomoran halaman
            "lengthChange": true, // Aktifkan pilihan jumlah data per halaman
            "searching": true,    // Aktifkan fitur pencarian
            "ordering": true,     // Aktifkan pengurutan kolom
            "info": true,         // Tampilkan informasi (contoh: "Menampilkan 1 dari 10 data")
            "autoWidth": false,   // Nonaktifkan penyesuaian lebar otomatis
            "responsive": true,   // Aktifkan desain responsif
        });
    }
});
