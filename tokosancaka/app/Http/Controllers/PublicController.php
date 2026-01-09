<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PpobProduct;
use App\Models\BannerEtalase; 
use App\Models\Setting;
use App\Models\PpobTransaction; 
use App\Services\DigiflazzService; 
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PublicController extends Controller
{
    protected $digiflazz;

    public function __construct(DigiflazzService $digiflazz)
    {
        $this->digiflazz = $digiflazz;
    }

    private function getWebLogo()
    {
        try {
            $setting = Setting::where('key', 'logo')->first();
            return $setting ? $setting->value : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Halaman Utama Pricelist (Handle Semua Kondisi)
     * Bisa dipanggil dengan parameter slug atau tanpa parameter
     */
    public function pricelist($slug = 'all')
    {
        // Jika route mengirim slug ke sini, kita oper ke showCategory biar logicnya nyambung
        if ($slug !== 'all' && $slug !== null) {
            return $this->showCategory($slug);
        }

        // Logic Default Halaman Utama (Tanpa Slug Spesifik)
        $weblogo = $this->getWebLogo();

        try {
            $banners = BannerEtalase::latest()->get();
        } catch (\Exception $e) {
            $banners = collect([]); 
        }

        try {
            $settings = Setting::whereIn('key', ['banner_2', 'banner_3'])->pluck('value', 'key')->toArray();
        } catch (\Exception $e) {
            $settings = [];
        }

        $products = PpobProduct::where('seller_product_status', 1)
            ->orderBy('category', 'asc')
            ->orderBy('brand', 'asc')
            ->orderBy('sell_price', 'asc')
            ->get();

        $categories = $products->pluck('category')->unique()->values();

        $pageInfo = [
            'title'       => 'Daftar Harga & Layanan',
            'slug'        => 'all',
            'input_label' => 'Nomor Handphone',
            'input_place' => 'Cari produk...',
            'icon'        => 'fa-tags',
            'is_postpaid' => false,
        ];

        $isPostpaid = false;

        return view('public.pricelist', compact('products', 'categories', 'banners', 'settings', 'pageInfo', 'weblogo', 'isPostpaid'));
    }

    /**
     * Menampilkan Halaman Kategori Spesifik
     * Logic inti penentuan Tombol Cek Tagihan ada di sini
     */
    public function showCategory($slug)
    {
        $weblogo = $this->getWebLogo();
        
        try {
            $banners = BannerEtalase::latest()->get();
            $settings = Setting::whereIn('key', ['banner_2', 'banner_3'])->pluck('value', 'key')->toArray();
        } catch (\Exception $e) {
            $banners = collect([]);
            $settings = [];
        }

        $slug = strtolower(trim($slug));
        
        // --- LOGIC DETEKSI PASCABAYAR (PERBAIKAN UTAMA) ---
        $postpaidSlugs = ['pln-pascabayar', 'pdam', 'bpjs', 'gas', 'pbb', 'internet-pasca', 'tv-kabel-pasca'];
        
        // Deteksi "Cerdas": Cek array slug, atau cari kata kunci 'pasca' / 'bill' di URL
        $isPostpaid = in_array($slug, $postpaidSlugs) || str_contains($slug, 'pasca') || str_contains($slug, 'bill');

        // Setup Page Info
        $pageInfo = [
            'title'       => ucfirst(str_replace('-', ' ', $slug)),
            'slug'        => $slug,
            'input_label' => 'Nomor Handphone',
            'input_place' => 'Contoh: 0812xxxx',
            'icon'        => 'fa-mobile-alt',
            'is_postpaid' => $isPostpaid, // Pass variable hasil deteksi
        ];

        // Override UI untuk Kategori Tertentu
        if ($slug == 'pln-pascabayar') {
            $pageInfo['title']       = 'Cek Tagihan PLN';
            $pageInfo['input_label'] = 'ID Pelanggan PLN';
            $pageInfo['input_place'] = 'Contoh: 53xxxx';
            $pageInfo['icon']        = 'fa-file-invoice-dollar';
            $pageInfo['is_postpaid'] = true; // Paksa True
            $isPostpaid = true;

        } elseif ($slug == 'pln-token') {
            $pageInfo['title']       = 'Token Listrik PLN';
            $pageInfo['input_label'] = 'Nomor Meter / ID Pelanggan';
            $pageInfo['input_place'] = 'Contoh: 14xxxx';
            $pageInfo['icon']        = 'fa-bolt';
            // isPostpaid tetap false (karena token itu prabayar)

        } elseif ($slug == 'pdam') {
            $pageInfo['title']       = 'Cek Tagihan PDAM';
            $pageInfo['input_label'] = 'ID Pelanggan PDAM';
            $pageInfo['input_place'] = 'Nomor Pelanggan';
            $pageInfo['icon']        = 'fa-faucet';
            $pageInfo['is_postpaid'] = true;
            $isPostpaid = true;
        
        } elseif ($slug == 'bpjs') {
            $pageInfo['title']       = 'Cek Tagihan BPJS';
            $pageInfo['input_label'] = 'Nomor VA Keluarga';
            $pageInfo['input_place'] = '88888xxxx';
            $pageInfo['icon']        = 'fa-heartbeat';
            $pageInfo['is_postpaid'] = true;
            $isPostpaid = true;
        
        } elseif ($slug == 'voucher-game') {
            $pageInfo['title']       = 'Voucher Game';
            $pageInfo['input_label'] = 'ID Player / User ID';
            $pageInfo['input_place'] = 'Masukkan ID Game';
            $pageInfo['icon']        = 'fa-gamepad';
        }

        // Ambil Produk (Hanya jika Mode Prabayar, biar ringan)
        $products = collect([]);
        $categories = collect([]); 
        
        if (!$isPostpaid) {
            $dbCategory = match($slug) {
                'pulsa' => 'Pulsa',
                'data' => 'Data',
                'pln-token' => 'PLN',
                'voucher-game' => 'Games',
                'e-money' => 'E-Money',
                default => null
            };

            $query = PpobProduct::where('seller_product_status', 1);
            
            if ($dbCategory) {
                $query->where('category', $dbCategory);
            }

            $products = $query->orderBy('brand', 'asc')
                ->orderBy('sell_price', 'asc')
                ->get();
                
            $categories = $products->pluck('category')->unique()->values();
        }

        // Return View dengan variable yang lengkap
        return view('public.pricelist', compact('products', 'categories', 'banners', 'settings', 'pageInfo', 'weblogo', 'isPostpaid'));
    }

    /**
     * AJAX: Cek Tagihan Pascabayar
     */
    public function checkBill(Request $request)
    {
        try {
            $request->validate([
                'customer_no' => 'required',
                'sku' => 'required'
            ]);

            $refId = 'INQ-' . time() . rand(100,999);
            
            $response = $this->digiflazz->inquiryPasca($request->sku, $request->customer_no, $refId);

            if (isset($response['data'])) {
                $data = $response['data'];
                if ($data['rc'] === '00' || $data['status'] === 'Sukses' || $data['status'] === 'Pending') {
                    return response()->json([
                        'status'        => 'success',
                        'customer_name' => $data['customer_name'],
                        'customer_no'   => $data['customer_no'],
                        'amount'        => $data['selling_price'], 
                        'desc'          => $data['desc'] ?? [], 
                        'ref_id'        => $refId 
                    ]);
                } else {
                    return response()->json([
                        'status'  => 'error', 
                        'message' => $data['message'] ?? 'Tagihan tidak ditemukan / sudah lunas.'
                    ]);
                }
            }
            return response()->json(['status' => 'error', 'message' => 'Gagal terhubung ke server provider.']);

        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Error Sistem: ' . $e->getMessage()]);
        }
    }

    /**
     * AJAX: Cek Nama PLN Prabayar
     */
    public function checkPlnPrabayar(Request $request)
    {
        try {
            $request->validate(['customer_no' => 'required']);
            $response = $this->digiflazz->inquiryPln($request->customer_no);

            if (isset($response['data']) && ($response['data']['rc'] === '00' || $response['data']['status'] === 'Sukses')) {
                return response()->json([
                    'status'        => 'success',
                    'name'          => $response['data']['name'],
                    'segment_power' => $response['data']['segment_power'] ?? '-'
                ]);
            }
            return response()->json(['status' => 'error', 'message' => 'Nomor meter tidak ditemukan.']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Error: ' . $e->getMessage()]);
        }
    }

    /**
     * Proses Transaksi
     */
    public function store(Request $request)
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Silakan login terlebih dahulu.');
        }

        $request->validate([
            'buyer_sku_code' => 'required|exists:ppob_products,buyer_sku_code',
            'customer_no'    => 'required|numeric',
        ]);

        $user = Auth::user();
        $sku  = $request->buyer_sku_code;
        $noHp = $request->customer_no;

        $product = PpobProduct::where('buyer_sku_code', $sku)->first();

        if ($user->saldo < $product->sell_price) {
            return redirect()->back()->with('error', 'Saldo tidak mencukupi.');
        }

        $refId = 'TRX-' . time() . rand(100, 999);

        DB::beginTransaction();
        try {
            $user->decrement('saldo', $product->sell_price);

            $trx = PpobTransaction::create([
                'user_id'        => $user->id, 
                'order_id'       => $refId,
                'buyer_sku_code' => $sku,
                'customer_no'    => $noHp,
                'price'          => $product->price,
                'selling_price'  => $product->sell_price,
                'profit'         => $product->sell_price - $product->price,
                'status'         => 'Pending',
                'message'        => 'Sedang diproses...',
            ]);

            $maxPrice = (int) $product->sell_price; 
            $response = $this->digiflazz->transaction($sku, $noHp, $refId, $maxPrice);

            if (isset($response['data'])) {
                $data = $response['data'];
                $status = $data['status'] == 'Gagal' ? 'Gagal' : $data['status'];
                
                if ($status == 'Gagal') {
                    $user->increment('saldo', $product->sell_price); 
                }

                $trx->update([
                    'status'  => $status, 
                    'message' => $data['message'], 
                    'sn'      => $data['sn'] ?? null
                ]);
                
                DB::commit();
                
                if($status == 'Gagal') return redirect()->back()->with('error', 'Transaksi Gagal: ' . $data['message']);
                return redirect()->back()->with('success', 'Transaksi Berhasil! Status: ' . $status);
            } else {
                $user->increment('saldo', $product->sell_price);
                $trx->update(['status' => 'Gagal', 'message' => 'Koneksi Provider Gagal']);
                DB::commit();
                return redirect()->back()->with('error', 'Gagal terhubung ke provider.');
            }

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Transaksi Error: " . $e->getMessage());
            return redirect()->back()->with('error', 'Kesalahan sistem.');
        }
    }
}