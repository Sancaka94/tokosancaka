<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Models\PpobTransaction;
use App\Models\User;

class TelegramPpobController extends Controller
{
    protected $telegramToken;
    protected $defaultUserId = 8; // ID Default (Sesuai data histori Anda)
    
    // Konfigurasi Provider (Contoh: Digiflazz)
    protected $providerUrl = 'https://api.digiflazz.com/v1/transaction';
    protected $providerUser = 'username_anda';
    protected $providerKey = 'key_anda';

    public function __construct()
    {
        $this->telegramToken = env('TELEGRAM_BOT_TOKEN');
    }

    /**
     * MAIN HANDLER: Menerima Webhook
     */
    public function handle(Request $request)
    {
        $update = $request->all();

        // 1. Validasi Payload
        if (!isset($update['message']) || !isset($update['message']['text'])) {
            return response()->json(['status' => 'ignored']);
        }

        $chatId   = $update['message']['chat']['id'];
        $text     = trim($update['message']['text']);
        $username = $update['message']['chat']['username'] ?? 'Gan';

        try {
            // 2. Routing Perintah
            if ($text == '/start' || $text == '/menu') {
                $this->sendMenu($chatId, $username);
            } 
            elseif (Str::startsWith($text, '/beli')) {
                $this->processTransaction($chatId, $text);
            }
            elseif ($text == '/cek' || $text == '/riwayat') {
                $this->checkHistory($chatId);
            }
            elseif (Str::startsWith($text, '/harga')) {
                $this->checkPrice($chatId, $text);
            }
            elseif ($text == '/bantuan') {
                $this->sendMessage($chatId, "📞 Hubungi CS: @AdminSancaka");
            }
            else {
                $this->sendMessage($chatId, "⚠️ Perintah tidak dikenal. Ketik /menu");
            }
        } catch (\Exception $e) {
            Log::error("Telegram Bot Error: " . $e->getMessage());
            $this->sendMessage($chatId, "❌ Terjadi kesalahan sistem internal.");
        }

        return response()->json(['status' => 'ok']);
    }

    /**
     * FITUR 1: Proses Transaksi (/beli KODE NOMOR)
     */
    private function processTransaction($chatId, $text)
    {
        // Parsing: /beli pln20 0812345
        $parts = explode(' ', $text);
        if (count($parts) != 3) {
            $this->sendMessage($chatId, "❌ <b>Format Salah!</b>\n\nKetik: <code>/beli [KODE] [NOMOR]</code>\nContoh: <code>/beli pln20 08123456789</code>");
            return;
        }

        $skuCode    = strtoupper($parts[1]);
        $customerNo = $parts[2];
        $orderId    = 'TRX-' . Carbon::now()->format('ymdHis') . rand(100, 999);

        // A. Cek Produk di Database Master
        // Pastikan Anda punya tabel 'products' atau sesuaikan dengan nama tabel Anda
        $product = DB::table('products')->where('code', $skuCode)->first(); // Sesuaikan nama kolom

        if (!$product) {
            $this->sendMessage($chatId, "❌ Produk <b>$skuCode</b> tidak ditemukan.");
            return;
        }

        if (!$product->active) { // Asumsi ada kolom 'active'
            $this->sendMessage($chatId, "⚠️ Produk <b>$skuCode</b> sedang gangguan.");
            return;
        }

        // B. Cek Saldo User (Agen)
        $user = User::find($this->defaultUserId);
        $hargaJual = $product->selling_price; // Sesuaikan nama kolom
        $hargaBeli = $product->price;

        if ($user->saldo < $hargaJual) {
            $this->sendMessage($chatId, "❌ <b>Saldo Tidak Cukup!</b>\nSaldo Anda: Rp " . number_format($user->saldo));
            return;
        }

        // C. Simpan Transaksi (Status: Processing)
        $trx = PpobTransaction::create([
            'order_id'         => $orderId,
            'user_id'          => $this->defaultUserId,
            'telegram_chat_id' => $chatId,
            'buyer_sku_code'   => $skuCode,
            'customer_no'      => $customerNo,
            'customer_wa'      => $customerNo, // Asumsi no WA sama
            'price'            => $hargaBeli,
            'selling_price'    => $hargaJual,
            'profit'           => $hargaJual - $hargaBeli,
            'status'           => 'Processing',
            'payment_method'   => 'SALDO_AGEN',
            'message'          => 'Transaksi via Bot Telegram',
            'desc'             => ['via' => 'telegram', 'type' => 'bot_order'],
        ]);

        // D. Kurangi Saldo User (Sementara)
        $user->decrement('saldo', $hargaJual);

        $this->sendMessage($chatId, "🔄 <b>Transaksi Diproses!</b>\n\n🆔 Ref: $orderId\n📦 Produk: $skuCode\n📱 Tujuan: $customerNo\n💰 Harga: Rp " . number_format($hargaJual));

        // E. Kirim ke Provider PPOB (Digiflazz/Atlantic/Dll)
        $this->sendToProvider($trx);
    }

    /**
     * FITUR 2: Cek Riwayat (/cek)
     */
    private function checkHistory($chatId)
    {
        $txs = PpobTransaction::telegram($chatId) // Menggunakan Scope yang ada di Model
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();

        if ($txs->isEmpty()) {
            $this->sendMessage($chatId, "📭 Belum ada riwayat transaksi.");
            return;
        }

        $msg = "📜 <b>5 Transaksi Terakhir:</b>\n\n";
        foreach ($txs as $tx) {
            $icon = match($tx->status) {
                'Success' => '✅', 'Pending' => '⏳', 'Processing' => '🔄', 'Failed' => '❌', default => '❓'
            };
            $tgl = $tx->created_at->format('d/m H:i');
            $sn = $tx->sn ? "SN: <code>{$tx->sn}</code>" : "";
            
            $msg .= "$icon <b>{$tx->buyer_sku_code}</b> ($tgl)\n";
            $msg .= "   No: {$tx->customer_no}\n";
            $msg .= "   $sn\n\n";
        }
        $this->sendMessage($chatId, $msg);
    }

    /**
     * FITUR 3: Cek Harga (/harga KODE)
     */
    private function checkPrice($chatId, $text)
    {
        $parts = explode(' ', $text);
        if (!isset($parts[1])) {
            $this->sendMessage($chatId, "Format: /harga [KODE]");
            return;
        }

        $code = strtoupper($parts[1]);
        $product = DB::table('products')->where('code', $code)->first(); // Sesuaikan tabel

        if ($product) {
            $price = number_format($product->selling_price, 0, ',', '.');
            $status = $product->active ? '✅ Tersedia' : '❌ Gangguan';
            $this->sendMessage($chatId, "🏷 <b>Info Harga</b>\n\nProduk: $code\nHarga: <b>Rp $price</b>\nStatus: $status");
        } else {
            $this->sendMessage($chatId, "❌ Produk tidak ditemukan.");
        }
    }

    /**
     * LOGIC: Kirim Request ke Provider
     */
    private function sendToProvider($trx)
    {
        // Contoh Logika Digiflazz (Silakan sesuaikan dengan Dokumentasi Provider Anda)
        try {
            // $sign = md5($this->providerUser . $this->providerKey . $trx->order_id);
            
            // $response = Http::post($this->providerUrl, [
            //     'username' => $this->providerUser,
            //     'buyer_sku_code' => $trx->buyer_sku_code,
            //     'customer_no' => $trx->customer_no,
            //     'ref_id' => $trx->order_id,
            //     'sign' => $sign,
            // ]);
            
            // $resData = $response->json();

            // Jika Provider merespon langsung (Synchronous)
            /*
            if (isset($resData['data']['status'])) {
                if ($resData['data']['status'] == 'Gagal') {
                     // Refund Saldo & Update Status Failed
                     $trx->update(['status' => 'Failed', 'message' => $resData['data']['message']]);
                     User::find($trx->user_id)->increment('saldo', $trx->selling_price);
                     $this->sendMessage($trx->telegram_chat_id, "❌ Transaksi Gagal: " . $resData['data']['message']);
                }
            }
            */

        } catch (\Exception $e) {
            Log::error("Provider Error: " . $e->getMessage());
        }
    }

    /**
     * Helper: Kirim Pesan
     */
    private function sendMessage($chatId, $text)
    {
        Http::post("https://api.telegram.org/bot{$this->telegramToken}/sendMessage", [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => true
        ]);
    }

    /**
     * Helper: Menu Utama
     */
    private function sendMenu($chatId, $name)
    {
        $msg = "👋 Halo <b>$name</b>!\n\n";
        $msg .= "🤖 <b>Sancaka Bot Ready!</b>\n";
        $msg .= "-------------------------\n";
        $msg .= "🛒 <b>Transaksi:</b>\n<code>/beli [KODE] [NOMOR]</code>\n";
        $msg .= "🏷 <b>Cek Harga:</b>\n<code>/harga [KODE]</code>\n";
        $msg .= "📜 <b>Riwayat:</b>\n<code>/cek</code>\n";
        $msg .= "-------------------------\n";
        $msg .= "Butuh bantuan? /bantuan";

        $this->sendMessage($chatId, $msg);
    }
}