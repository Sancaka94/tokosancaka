<?php



use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Pondok\Admin\DashboardController;

use App\Http\Controllers\Pondok\Admin\SantriController;

use App\Http\Controllers\Pondok\Admin\PegawaiController;

use App\Http\Controllers\Pondok\Admin\PenggunaController;

use App\Http\Controllers\Pondok\Admin\CalonSantriController;

use App\Http\Controllers\Pondok\Admin\TahunAjaranController;

use App\Http\Controllers\Pondok\Admin\UnitPendidikanController;

use App\Http\Controllers\Pondok\Admin\KelasController;

use App\Http\Controllers\Pondok\Admin\KamarController;

use App\Http\Controllers\Pondok\Admin\MataPelajaranController;

use App\Http\Controllers\Pondok\Admin\JadwalPelajaranController;

use App\Http\Controllers\Pondok\Admin\AbsensiSantriController;

use App\Http\Controllers\Pondok\Admin\PenilaianAkademikController;

use App\Http\Controllers\Pondok\Admin\TahfidzProgressController;

use App\Http\Controllers\Pondok\Admin\PosPembayaranController;

use App\Http\Controllers\Pondok\Admin\TagihanSantriController;

use App\Http\Controllers\Pondok\Admin\PembayaranSantriController;

use App\Http\Controllers\Pondok\Admin\TabunganSantriController;

use App\Http\Controllers\Pondok\Admin\AkunAkuntansiController;

use App\Http\Controllers\Pondok\Admin\TransaksiKasBankController;

use App\Http\Controllers\Pondok\Admin\JurnalUmumController;

use App\Http\Controllers\Pondok\Admin\PenggajianController;

use App\Http\Controllers\Pondok\Admin\JabatanController;

use App\Http\Controllers\Pondok\Admin\AbsensiPegawaiController;

use App\Http\Controllers\Pondok\Admin\IzinSantriController;

use App\Http\Controllers\Pondok\Admin\PelanggaranSantriController;

use App\Http\Controllers\Pondok\Admin\RekamMedisController;

use App\Http\Controllers\Pondok\Admin\PengumumanController;

use App\Http\Controllers\Pondok\Admin\PostController;

use App\Http\Controllers\Pondok\Admin\PaketController;

use App\Http\Controllers\Pondok\Admin\SettingController;



/*

|--------------------------------------------------------------------------

| Rute Admin Pondok Pesantren

|--------------------------------------------------------------------------

|

| Semua rute untuk panel admin pondok.

| Dikelompokkan dengan prefix 'admin' dan middleware 'auth'.

|

*/



// Anda bisa mengaktifkan middleware 'auth' setelah sistem login Anda siap

Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {



//Route::prefix('admin')->name('admin.')->group(function () {



    // --- MANAJEMEN DASAR ---

    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('santri', SantriController::class);
    
    // Route Menejemen Santri------------------------------------------------------------------------------------------------------------------------
    
        // TAMBAHKAN BARIS INI (Rute Khusus Update Status)
    Route::put('/santri/{id}/update-status', [SantriController::class, 'updateStatus'])->name('santri.updateStatus');
    
    

    Route::resource('pegawai', PegawaiController::class);

    Route::resource('pengguna', PenggunaController::class);

    Route::resource('calon-santri', CalonSantriController::class);



    // --- MANAJEMEN AKADEMIK ---

    Route::resource('tahun-ajaran', TahunAjaranController::class);

    Route::resource('unit-pendidikan', UnitPendidikanController::class);

    Route::resource('kelas', KelasController::class);

    Route::resource('kamar', KamarController::class);

    Route::resource('mata-pelajaran', MataPelajaranController::class);

    Route::resource('jadwal-pelajaran', JadwalPelajaranController::class);

    Route::resource('absensi-santri', AbsensiSantriController::class);

    Route::resource('penilaian-akademik', PenilaianAkademikController::class);

    Route::resource('tahfidz-progress', TahfidzProgressController::class);



    // --- MANAJEMEN KEUANGAN ---

    Route::resource('pos-pembayaran', PosPembayaranController::class);

    Route::resource('tagihan-santri', TagihanSantriController::class);

    Route::resource('pembayaran-santri', PembayaranSantriController::class);

    Route::resource('tabungan-santri', TabunganSantriController::class);

    Route::resource('akun-akuntansi', AkunAkuntansiController::class);

    Route::resource('transaksi-kas-bank', TransaksiKasBankController::class);

    Route::resource('jurnal-umum', JurnalUmumController::class);

    Route::resource('penggajian', PenggajianController::class);



    // --- MANAJEMEN KEPEGAWAIAN & KESANTRIAN ---

    Route::resource('jabatan', JabatanController::class);

    Route::resource('absensi-pegawai', AbsensiPegawaiController::class);

    Route::resource('izin-santri', IzinSantriController::class);

    Route::resource('pelanggaran-santri', PelanggaranSantriController::class);

    Route::resource('rekam-medis', RekamMedisController::class);



    // --- KONTEN & PENGATURAN ---

    Route::resource('pengumuman', PengumumanController::class);

    Route::resource('post', PostController::class);

    Route::resource('paket', PaketController::class);

    Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');

    Route::post('/settings', [SettingController::class, 'store'])->name('settings.store');



});

