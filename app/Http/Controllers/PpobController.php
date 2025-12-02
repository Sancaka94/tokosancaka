<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\DigiflazzService;
use App\Models\Setting;
use App\Models\PpobProduct; // Pastikan Model ini di-import
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PpobController extends Controller
{
    protected $digiflazz;

    public function __construct(DigiflazzService $digiflazz)
    {
        $this->digiflazz = $digiflazz;
    }

    /**
     * Helper: Ambil Logo Website dari Database Settings (Key-Value)
     */
    private function getWebLogo()
    {
        // Mencari setting dengan key 'logo'
        $setting = Setting::where('key', 'logo')->first();
        // Mengembalikan value (path gambar) atau null
        return $setting ? $setting->value : null;
    }

    /**
     * Fitur Utama: Sinkronisasi Data dari API ke Database
     * Diakses via URL: /digital/sync-produk
     */
    public function sync()
    {
        // 1. Ambil data terbaru dari API Digiflazz (Prepaid)
        // Pastikan DigiflazzService Anda sudah menggunakan signature 'pricelist'
        $products = $this->digiflazz->getPriceList('prepaid');

        // ======================================================
        // [DEBUG] TAMPILKAN JSON KE LOG LARAVEL
        // ======================================================
        // Cek file di folder: storage/logs/laravel.log
        \Illuminate\Support\Facades\Log::info('DATA JSON DARI DIGIFLAZZ:', [
            'jumlah_data' => count($products),
            'sample_data' => $products // Ini akan mencetak semua array ke log
        ]);
        // ======================================================

        if (empty($products)) {
            return redirect()->back()->with('error', 'Gagal mengambil data dari Digiflazz. Cek koneksi atau IP Whitelist.');
        }

        DB::beginTransaction();
        try {
            $count = 0;
            foreach ($products as $item) {
                // Filter sederhana: Lewati jika kategori pascabayar nyasar (opsional)
                // if ($item['category'] == 'Pascabayar') continue;

                // --- LOGIKA HARGA JUAL ---
                // Modal dari API
                $modal = (float) $item['price'];
                
                // Margin keuntungan (Misal: Rp 2.000)
                $margin = 2000; 
                
                // Harga Jual Default
                $hargaJualBaru = $modal + $margin;

                // Kita gunakan updateOrCreate agar data yang sudah ada diupdate, yang belum ada dibuat baru
                // Kuncinya adalah 'buyer_sku_code' (SKU Unik)
                $product = PpobProduct::updateOrCreate(
                    ['buyer_sku_code' => $item['buyer_sku_code']], 
                    [
                        'product_name'          => $item['product_name'],
                        'category'              => $item['category'],
                        'brand'                 => $item['brand'],
                        'type'                  => $item['type'],
                        'seller_name'           => $item['seller_name'],
                        'price'                 => $modal, // Update harga modal terbaru
                        
                        // Status Produk
                        'buyer_product_status'  => $item['buyer_product_status'],
                        'seller_product_status' => $item['seller_product_status'],
                        'unlimited_stock'       => $item['unlimited_stock'],
                        'stock'                 => $item['stock'],
                        'multi'                 => $item['multi'],
                        
                        // Cut Off Time
                        'start_cut_off'         => $item['start_cut_off'],
                        'end_cut_off'           => $item['end_cut_off'],
                        'desc'                  => $item['desc'],
                    ]
                );

                // --- LOGIKA PINTAR HARGA JUAL ---
                // Jika produk ini BARU dibuat (sell_price masih 0 atau default), set harga jual + margin.
                // Jika produk LAMA, biarkan harga jual yang sudah disetting admin (jangan ditimpa), 
                // KECUALI jika Anda ingin selalu mereset harga jual, hapus kondisi if ini.
                if ($product->wasRecentlyCreated || $product->sell_price <= 0) {
                    $product->sell_price = $hargaJualBaru;
                    $product->save();
                } else {
                    // Opsi Tambahan: Jika harga modal NAIK drastis melebihi harga jual saat ini,
                    // maka update harga jual otomatis agar tidak rugi.
                    if ($product->sell_price < $modal) {
                        $product->sell_price = $hargaJualBaru;
                        $product->save();
                    }
                }

                $count++;
            }
            
            DB::commit();
            return redirect()->back()->with('success', "Sukses! $count produk berhasil diperbarui dari Digiflazz.");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Sync PPOB Error: " . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan saat menyimpan ke database: ' . $e->getMessage());
        }
    }

    /**
     * Halaman Utama PPOB
     */
    public function index()
    {
        $weblogo = $this->getWebLogo();
        return view('ppob.index', compact('weblogo'));
    }

    /**
     * Halaman Pulsa & Data
     */
    public function pulsa()
    {
        $weblogo = $this->getWebLogo();

        // --- AMBIL DARI DATABASE LOKAL ---
        // 1. Ambil data dari tabel ppob_products
        // 2. Filter Kategori 'Pulsa' atau 'Data' (sesuaikan dengan nama kategori di Digiflazz)
        // 3. Hanya tampilkan yang statusnya AKTIF (buyer & seller true)
        // 4. Urutkan berdasarkan harga jual termurah
        
        $products = PpobProduct::whereIn('category', ['Pulsa', 'Data']) // Bisa sesuaikan kategorinya
            ->where('buyer_product_status', true)
            ->where('seller_product_status', true)
            ->orderBy('sell_price', 'asc')
            ->get();

        // Ambil daftar Brand unik untuk filter tombol operator (Telkomsel, XL, dll)
        // pluck('brand') mengambil kolom brand, unique() menghapus duplikat, values() reset index array
        $operators = $products->pluck('brand')->unique()->values();

        return view('ppob.pulsa', compact('products', 'operators', 'weblogo'));
    }

    /**
     * Halaman Token PLN
     */
    public function pln()
    {
        $weblogo = $this->getWebLogo();
        
        // Contoh untuk PLN
        $products = PpobProduct::where('category', 'PLN')
            ->where('buyer_product_status', true)
            ->where('seller_product_status', true)
            ->orderBy('sell_price', 'asc')
            ->get();

        return view('ppob.pln', compact('weblogo', 'products'));
    }

    /**
     * Halaman Cek Saldo (Bisa diakses via AJAX atau Halaman Khusus)
     */
    public function cekSaldo()
    {
        $result = $this->digiflazz->checkDeposit();

        if (request()->ajax()) {
            // Format Rupiah untuk AJAX response
            $result['formatted'] = 'Rp ' . number_format($result['deposit'], 0, ',', '.');
            return response()->json($result);
        }

        // Jika diakses langsung via browser, kembalikan ke halaman sebelumnya dengan pesan
        if ($result['status']) {
            $saldoFormatted = number_format($result['deposit'], 0, ',', '.');
            return redirect()->back()->with('success', "Sisa Saldo Digiflazz: Rp $saldoFormatted");
        } else {
            return redirect()->back()->with('error', "Gagal Cek Saldo: " . $result['message']);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'buyer_sku_code' => 'required|exists:ppob_products,buyer_sku_code',
            'customer_no'    => 'required|numeric|digits_between:9,15',
        ]);

        $user = Auth::user();
        $sku = $request->buyer_sku_code;
        $noHp = $request->customer_no;

        // 1. Ambil Data Produk dari Database Lokal
        $product = PpobProduct::where('buyer_sku_code', $sku)->first();

        // 2. Cek Saldo User (Pastikan kolom 'saldo' ada di tabel users)
        if ($user->saldo < $product->sell_price) {
            return redirect()->back()->with('error', 'Saldo Anda tidak cukup. Silakan Top Up terlebih dahulu.');
        }

        // 3. Buat Ref ID Unik (Format: TRX-USERID-TIMESTAMP)
        $refId = 'TRX-' . $user->id . '-' . time();

        // 4. TEMBAK API DIGIFLAZZ dengan MAX PRICE
        // Kita set max_price = harga jual kita.
        // Jika harga modal Digiflazz tiba-tiba naik melebihi harga jual kita, transaksi DITOLAK otomatis.
        // Ini mencegah Anda rugi (jual rugi).
        $maxPrice = (int) $product->sell_price; 

        $response = $this->digiflazz->transaction($sku, $noHp, $refId, $maxPrice);

        // 5. Cek Response
        if (isset($response['data'])) {
            $data = $response['data'];
            
            // Status: Sukses / Pending -> Potong Saldo
            if (in_array($data['status'], ['Sukses', 'Pending'])) {
                
                // POTONG SALDO USER
                $user->decrement('saldo', $product->sell_price);

                // TODO: Simpan ke tabel 'transactions' atau 'orders' Anda di sini
                // Order::create([...]);

                $pesan = $data['status'] == 'Sukses' ? 'Transaksi Berhasil!' : 'Transaksi sedang diproses.';
                return redirect()->back()->with('success', $pesan . ' SN: ' . ($data['sn'] ?? '-'));
            } 
            // Status Gagal
            else {
                return redirect()->back()->with('error', 'Transaksi Gagal: ' . ($data['message'] ?? 'Unknown Error'));
            }
        }

        return redirect()->back()->with('error', 'Gagal terhubung ke server PPOB.');
    }

}