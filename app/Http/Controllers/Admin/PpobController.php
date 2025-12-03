<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\DigiflazzService;
use App\Models\Setting;
use App\Models\PpobProduct;
use App\Models\PpobTransaction;
use App\Models\Banner; 
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class PublicPpobController extends Controller
{
    protected $digiflazz;

    public function __construct(DigiflazzService $digiflazz)
    {
        $this->digiflazz = $digiflazz;
    }

    /**
     * Halaman Utama Daftar Harga / Kategori (Frontend)
     * URL: /etalase/ppob/digital/{slug}
     */
    public function index($slug = 'pulsa') 
    {
        // 1. Bersihkan Slug
        $slug = strtolower(trim($slug));

        // 2. Mapping Kategori Database
        $categoriesMap = [
            'pulsa'          => ['Pulsa'],
            'data'           => ['Data'],
            'pln-token'      => ['PLN'],
            'pln-pascabayar' => ['PLN Pascabayar', 'Tagihan PLN'], 
            'pdam'           => ['PDAM'],
            'e-money'        => ['E-Money'],
            'voucher-game'   => ['Games'],
            'streaming'      => ['TV', 'Streaming'],
            'bpjs'           => ['BPJS'],
        ];

        // 3. Fallback Cerdas: Jika slug tidak dikenali, cek apakah itu pascabayar
        if (!array_key_exists($slug, $categoriesMap)) {
            // Jika slug mengandung kata 'pasca', 'bill', 'pdam', 'bpjs', jangan reset ke pulsa
            if (str_contains($slug, 'pasca') || str_contains($slug, 'bill') || str_contains($slug, 'pdam') || str_contains($slug, 'bpjs')) {
                // Biarkan slug apa adanya, nanti akan dihandle logika postpaid
            } else {
                $slug = 'pulsa';
            }
        }

        $dbCategories = $categoriesMap[$slug] ?? [];

        // 4. DETEKSI OTOMATIS MODE PASCABAYAR (Agar Tombol Cek Muncul)
        // Daftar semua slug yang WAJIB menampilkan tombol "Cek Tagihan"
        $postpaidSlugs = ['pln-pascabayar', 'pdam', 'bpjs', 'gas', 'pbb', 'internet-pasca', 'tv-kabel', 'pajak'];
        
        $isPostpaid = in_array($slug, $postpaidSlugs);

        // 5. Setup Variable $pageInfo
        $pageInfo = [
            'title'       => ucfirst(str_replace('-', ' ', $slug)),
            'slug'        => $slug,
            'input_label' => 'Nomor Handphone',
            'input_place' => 'Contoh: 0812xxxx',
            'icon'        => 'fa-mobile-alt',
            'is_postpaid' => $isPostpaid // Set sesuai deteksi di atas
        ];

        // Custom UI per Kategori (Override Config)
        switch ($slug) {
            case 'pln-token':
                $pageInfo['title']       = 'Token Listrik PLN';
                $pageInfo['input_label'] = 'Nomor Meter / ID Pelanggan';
                $pageInfo['input_place'] = 'Contoh: 141234567890';
                $pageInfo['icon']        = 'fa-bolt';
                break;

            case 'pln-pascabayar': 
                $pageInfo['title']       = 'Tagihan Listrik Pasca';
                $pageInfo['input_label'] = 'ID Pelanggan';
                $pageInfo['input_place'] = 'Contoh: 53xxxxxxxxx';
                $pageInfo['icon']        = 'fa-file-invoice-dollar';
                $pageInfo['is_postpaid'] = true; // FORCE TRUE
                break;

            case 'pdam':
                $pageInfo['title']       = 'Tagihan Air PDAM';
                $pageInfo['input_label'] = 'ID Pelanggan / No Sambungan';
                $pageInfo['icon']        = 'fa-faucet';
                $pageInfo['is_postpaid'] = true; // FORCE TRUE
                break;
            
            case 'bpjs':
                $pageInfo['title']       = 'Bayar BPJS Kesehatan';
                $pageInfo['input_label'] = 'Nomor VA Keluarga';
                $pageInfo['icon']        = 'fa-heartbeat';
                $pageInfo['is_postpaid'] = true; // FORCE TRUE
                break;

            case 'e-money':
                $pageInfo['title']       = 'Top Up E-Wallet';
                $pageInfo['icon']        = 'fa-wallet';
                break;

            case 'voucher-game':
                $pageInfo['title']       = 'Voucher Game';
                $pageInfo['input_label'] = 'ID Pemain (User ID)';
                $pageInfo['input_place'] = 'Masukkan ID Game';
                $pageInfo['icon']        = 'fa-gamepad';
                break;
                
            case 'streaming':
                $pageInfo['title']       = 'Voucher TV & Streaming';
                $pageInfo['icon']        = 'fa-tv';
                break;
        }

        // 6. Ambil Produk (Hanya jika kategori ada di DB)
        $products = [];
        if (!empty($dbCategories)) {
            $products = PpobProduct::whereIn('category', $dbCategories)
                ->where('buyer_product_status', true)
                ->where('seller_product_status', true)
                ->orderBy('sell_price', 'asc')
                ->get();
        }

        // 7. Data Pendukung View
        $brands = collect($products)->pluck('brand')->unique()->values();
        
        $categories = [
            (object)['slug' => 'pulsa', 'name' => 'Pulsa'],
            (object)['slug' => 'data', 'name' => 'Data'],
            (object)['slug' => 'pln-token', 'name' => 'Token PLN'],
            (object)['slug' => 'pln-pascabayar', 'name' => 'PLN Pasca'],
            (object)['slug' => 'pdam', 'name' => 'PDAM'],
            (object)['slug' => 'e-money', 'name' => 'E-Wallet'],
            (object)['slug' => 'voucher-game', 'name' => 'Games'],
            (object)['slug' => 'streaming', 'name' => 'Streaming'],
        ];

        try {
            $banners = Banner::where('status', 'active')->get();
        } catch (\Exception $e) {
            $banners = [];
        }

        $settings = Setting::whereIn('key', ['banner_2', 'banner_3'])->pluck('value', 'key');

        // 8. Return View (Kirim isPostpaid secara eksplisit)
        return view('public.pricelist', compact(
            'products', 
            'pageInfo', 
            'isPostpaid', // Variabel ini penting agar view tidak bingung
            'categories', 
            'brands', 
            'banners', 
            'settings'
        ));
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
            return response()->json(['status' => 'error', 'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage()]);
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
            return response()->json(['status' => 'error', 'message' => 'Gagal koneksi: ' . $e->getMessage()]);
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