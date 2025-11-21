/**
 * tambah-pesanan.js
 * This file contains the specific JavaScript logic for the "Tambah Pesanan" page.
 */
$(document).ready(function () {
    // Check if the select element for this page exists
    if ($('.select2-class').length) {
        
        // Initialize Select2
        $('.select2-class').select2({
            theme: "bootstrap-5",
            width: '100%',
        });
    }
});
