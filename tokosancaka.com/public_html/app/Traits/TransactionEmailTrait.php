<?php

namespace App\Traits;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

trait TransactionEmailTrait
{
    /**
     * Helper universal untuk mengirim email sukses di seluruh controller
     */
    protected function sendTransactionSuccessEmail($email, $name, $invoice, $type, $amount)
    {
        $email = trim($email);

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Log::warning("⚠️ [EMAIL SKIP] Email tidak valid/kosong: '{$email}' untuk invoice $invoice");
            return;
        }

        try {
            $subject = "✅ Pembayaran Berhasil - Invoice $invoice";
            
            $data = [
                'name' => $name,
                'invoice' => $invoice,
                'type' => $type,
                'amount' => $amount,
                'date' => now()->timezone('Asia/Jakarta')->format('d M Y, H:i:s')
            ];

            // Render view blade menjadi format HTML string
            $htmlBody = view('emails.transaction_success', $data)->render();

            Mail::html($htmlBody, function ($message) use ($email, $subject) {
                $message->to($email)
                        ->subject($subject)
                        ->from(config('mail.from.address', 'admin@tokosancaka.com'), config('mail.from.name', 'Sancaka Server'));
            });

            Log::info("📧 [EMAIL SENT] Notifikasi $type sukses dikirim ke: $email untuk invoice $invoice");
            
        } catch (\Throwable $e) {
            Log::error("❌ [EMAIL FAILED] Gagal kirim email $type ke $email: " . $e->getMessage());
        }
    }
}