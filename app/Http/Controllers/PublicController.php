<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PpobProduct;
use App\Models\BannerEtalase; // Model Banner Sesuai Kode Anda
use App\Models\Setting;
use App\Models\PpobTransaction; // Wajib untuk simpan transaksi
use App\Services\DigiflazzService; // Service API
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PublicController extends Controller
{
    protected $digiflazz;

    // Inject Service Digiflazz
    public function __construct(DigiflazzService $digiflazz)
    {
        $this->digiflazz = $digiflazz;
    }

    /**
     * Helper: Ambil Logo Web untuk Layout
     */
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
     * Halaman Utama Pricelist (Semua Kategori)
     * URL: /daftar-harga
     */
    public function pricelist()
    {
        $weblogo = $this->getWebLogo();

        // 1. Ambil Banner Utama
        try {
            $banners = BannerEtalase::latest()->get();
        } catch (\Exception $e) {
            $banners = collect([]); 
        }

        // 2. Ambil Setting Banner Samping
        try {
            $settings = Setting::whereIn('key', ['banner_2', 'banner_3'])->pluck('value', 'key')->toArray();
        } catch (\Exception $e) {
            $settings = [];
        }

        // 3. Ambil Produk Aktif (Default Prabayar)
        $products = PpobProduct::where('seller_product_status', 1)
            ->orderBy('category', 'asc')
            ->orderBy('brand', 'asc')
            ->orderBy('sell_price', 'asc')
            ->get();

        $categories = $products->pluck('category')->unique()->values();

        // Data Page Info Default
        $pageInfo = [
            'title'       => 'Daftar Harga & Layanan',
            'slug'        => 'all',
            'input_label' => 'Nomor Handphone',
            'input_place' => 'Cari produk...',
            'icon'        => 'fa-tags',
            'is_postpaid' => false,
        ];

        return view('public.pricelist', compact('products', 'categories', 'banners', 'settings', 'pageInfo', 'weblogo'));
    }

    /**
     * Menampilkan Halaman Kategori Spesifik
     * URL: /layanan/{slug} atau /etalase/ppob/digital/{slug}
     */
    public function showCategory($slug)
    {
        $weblogo = $this->getWebLogo();
        
        // 1. Ambil Data Banner & Setting
        try {
            $banners = BannerEtalase::latest()->get();
            $settings = Setting::whereIn('key', ['banner_2', 'banner_3'])->pluck('value', 'key')->toArray();
        } catch (\Exception $e) {
            $banners = collect([]);
            $settings = [];
        }

        // 2. Konfigurasi Halaman & Deteksi Otomatis
        $slug = strtolower(trim($slug));
        
        // Daftar Slug yang WAJIB tombol Cek Tagihan
        $postpaidSlugs = ['pln-pascabayar', 'pdam', 'bpjs', 'gas', 'pbb', 'internet-pasca', 'tv-kabel-pasca'];
        $isPostpaid = in_array($slug, $postpaidSlugs) || str_contains($slug, 'pasca');

        $pageInfo = [
            'title'       => ucfirst(str_replace('-', ' ', $slug)),
            'slug'        => $slug,
            'input_label' => 'Nomor Handphone',
            'input_place' => 'Contoh: 0812xxxx',
            'icon'        => 'fa-mobile-alt',
            'is_postpaid' => $isPostpaid, // KUNCI: Set true jika pascabayar
        ];

        // 3. Custom UI per Kategori (Agar Label & Icon Sesuai)
        if ($slug == 'pln-pascabayar') {
            $pageInfo['title']       = 'Cek Tagihan PLN';
            $pageInfo['input_label'] = 'ID Pelanggan PLN';
            $pageInfo['input_place'] = 'Contoh: 53xxxx';
            $pageInfo['icon']        = 'fa-file-invoice-dollar';
            
        } elseif ($slug == 'pln-token') {
            $pageInfo['title']       = 'Token Listrik PLN';
            $pageInfo['input_label'] = 'Nomor Meter / ID Pelanggan';
            $pageInfo['input_place'] = 'Contoh: 14xxxx';
            $pageInfo['icon']        = 'fa-bolt';

        } elseif ($slug == 'pdam') {
            $pageInfo['title']       = 'Cek Tagihan PDAM';
            $pageInfo['input_label'] = 'ID Pelanggan PDAM';
            $pageInfo['input_place'] = 'Nomor Pelanggan';
            $pageInfo['icon']        = 'fa-faucet';
        
        } elseif ($slug == 'bpjs') {
            $pageInfo['title']       = 'Cek Tagihan BPJS';
            $pageInfo['input_label'] = 'Nomor VA Keluarga';
            $pageInfo['input_place'] = '88888xxxx';
            $pageInfo['icon']        = 'fa-heartbeat';
        
        } elseif ($slug == 'voucher-game') {
            $pageInfo['title']       = 'Voucher Game';
            $pageInfo['input_label'] = 'ID Player / User ID';
            $pageInfo['input_place'] = 'Masukkan ID Game';
            $pageInfo['icon']        = 'fa-gamepad';
        }

        // 4. Ambil Produk (Hanya jika Mode Prabayar)
        $products = collect([]);
        $categories = collect([]); // Kategori untuk filter button
        
        if (!$pageInfo['is_postpaid']) {
            // Mapping Slug ke Kategori Database (Opsional, sesuaikan dengan nama di DB Anda)
            $dbCategory = match($slug) {
                'pulsa' => 'Pulsa',
                'data' => 'Data',
                'pln-token' => 'PLN',
                'voucher-game' => 'Games',
                'e-money' => 'E-Money',
                default => null
            };

            $query = PpobProduct::where('seller_product_status', 1);
            
            // Jika ada mapping kategori spesifik, filter. Jika tidak ('all'), ambil semua.
            if ($dbCategory) {
                $query->where('category', $dbCategory);
            }

            $products = $query->orderBy('brand', 'asc')
                ->orderBy('sell_price', 'asc')
                ->get();
                
            $categories = $products->pluck('category')->unique()->values();
        }

        // 5. Return View
        return view('public.pricelist', compact('products', 'categories', 'banners', 'settings', 'pageInfo', 'weblogo'));
    }

    /**
     * AJAX: Cek Tagihan Pascabayar (PLN/PDAM/BPJS)
     */
    public function checkBill(Request $request)
    {
        try {
            $request->validate([
                'customer_no' => 'required',
                'sku' => 'required'
            ]);

            $refId = 'INQ-' . time() . rand(100,999);
            
            // Panggil Service Digiflazz
            $response = $this->digiflazz->inquiryPasca($request->sku, $request->customer_no, $refId);

            if (isset($response['data'])) {
                $data = $response['data'];
                // Cek status sukses (Bisa 'Sukses', 'Pending', atau RC '00')
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
     * Proses Transaksi (Checkout)
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
                'user_id'        => $user->id, // Pastikan 'id' adalah primary key user Anda
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
                    $user->increment('saldo', $product->sell_price); // Refund
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