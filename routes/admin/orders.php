<?php

// Pastikan namespace controller sudah benar
use App\Http\Controllers\Admin\AdminOrderController;
use App\Http\Controllers\Admin\ChatController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request; // Diperlukan untuk route closure chat

/*
|--------------------------------------------------------------------------
| Admin Order Routes (Marketplace Orders)
|--------------------------------------------------------------------------
|
| Rute-rute ini khusus menangani pesanan yang masuk melalui etalase/marketplace.
| File ini di-include dari routes/web.php di dalam grup middleware admin.
| Semua nama route di sini akan otomatis diawali dengan 'admin.orders.' 
| karena ada name('admin.orders.') pada grup di routes/web.php.
|
*/

// Grup route untuk pesanan marketplace
// URL: /admin/orders/*
// Nama: admin.orders.*
Route::prefix('orders')->name('orders.')->group(function () { // Name prefix disesuaikan agar jadi admin.orders.
    
    // Menampilkan halaman daftar pesanan (menggunakan DataTables)
    // URL: /admin/orders
    // Nama: admin.orders.index
    Route::get('/', [AdminOrderController::class, 'index'])->name('index');
    
    // Endpoint AJAX untuk DataTables mengambil data pesanan
    // URL: /admin/orders/data
    // Nama: admin.orders.data
    Route::get('/data', [AdminOrderController::class, 'getData'])->name('data');
    
    // Menampilkan detail satu pesanan berdasarkan nomor invoice
    // URL: /admin/orders/{invoice_number}
    // Nama: admin.orders.show
    Route::get('/{invoice_number}', [AdminOrderController::class, 'show'])->name('show'); 
    
    // Membatalkan pesanan (menggunakan PATCH karena ini update status)
    // URL: /admin/orders/{invoice_number}/cancel 
    // Nama: admin.orders.cancel
    Route::patch('/{invoice_number}/cancel', [AdminOrderController::class, 'cancel'])->name('cancel');
    
    // Mengunduh faktur PDF untuk satu pesanan
    // URL: /admin/orders/{invoice_number}/invoice-pdf
    // Nama: admin.orders.invoice.pdf
    Route::get('/{invoice_number}/invoice-pdf', [AdminOrderController::class, 'exportInvoice'])->name('invoice.pdf');

    // Menampilkan/mengunduh struk thermal untuk satu pesanan
    // URL: /admin/orders/{invoice_number}/print-thermal
    // Nama: admin.orders.print.thermal
    Route::get('/{invoice_number}/print-thermal', [AdminOrderController::class, 'printThermal'])->name('print.thermal');

}); // Akhir grup /orders

// Route untuk ekspor laporan (di luar grup prefix 'orders')
// URL: /admin/orders-report/pdf?start_date=...&end_date=...
// Nama: admin.orders.report.pdf
Route::get('/orders-report/pdf', [AdminOrderController::class, 'exportReport'])->name('orders.report.pdf'); // Nama disesuaikan

// Route Placeholder untuk memulai chat (di luar grup prefix 'orders')
// URL: /admin/chat/start?recipient={user_id}&sender={store_user_id}
// Nama: admin.chat.start
// Anda perlu membuat ChatController dan method startChat ini
Route::get('/chat/start', [ChatController::class, 'start'])->name('chat.start');


?>

