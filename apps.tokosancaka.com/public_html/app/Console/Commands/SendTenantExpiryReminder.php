<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Services\FonnteService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SendTenantExpiryReminder extends Command
{
    // Nama command yang dipanggil di Cron
    protected $signature = 'tenant:send-reminder';
    protected $description = 'Cek database tenants dan kirim WA H-7 sebelum expired';

    public function handle()
    {
        Log::info("CRON TENANT: Memulai proses harian.");
    
        // --- LOGIKA 1: PENGINGAT H-7 ---
        $targetH7 = Carbon::now()->addDays(7)->toDateString();
        $tenantsH7 = DB::table('tenants')->whereDate('expired_at', $targetH7)->where('status', 'active')->get();
    
        foreach ($tenantsH7 as $tenant) {
            $alreadySent = DB::table('tenant_notifications')
                ->where('tenant_id', $tenant->id)
                ->where('type', 'H-7')
                ->whereDate('sent_at', Carbon::today())
                ->exists();
    
            if (!$alreadySent) {
                $this->executeSendWa($tenant, "H-7");
            }
        }
    
        // --- LOGIKA 2: AUTO-SUSPEND (Tepat saat Expired) ---
        // Mencari tenant yang expired hari ini atau yang sudah lewat tapi masih 'active'
        $expiredTenants = DB::table('tenants')
            ->where('status', 'active')
            ->whereNotNull('expired_at')
            ->where('expired_at', '<', now())
            ->get();
    
        foreach ($expiredTenants as $tenant) {
            // Ubah status jadi inactive di database
            DB::table('tenants')->where('id', $tenant->id)->update(['status' => 'inactive']);
            
            // Kirim pesan WA terakhir bahwa akun telah dibekukan
            $this->executeSendWa($tenant, "SUSPEND");
            
            Log::warning("CRON TENANT: Akun {$tenant->subdomain} otomatis di-suspend karena expired.");
        }
    }

    private function executeSendWa($tenant)
    {
        // Pastikan nomor whatsapp tidak null
        if (empty($tenant->whatsapp)) {
            Log::warning("CRON REMINDER: Tenant {$tenant->name} tidak punya nomor WhatsApp.");
            return;
        }

        // Sanitasi nomor whatsapp (Ubah 08... jadi 628...)
        $nomorWA = preg_replace('/[^0-9]/', '', $tenant->whatsapp);
        if (str_starts_with($nomorWA, '0')) {
            $nomorWA = '62' . substr($nomorWA, 1);
        }

        $tglExpired = Carbon::parse($tenant->expired_at)->format('d-m-Y');

        // Pesan Peringatan
        $message = "*PERINGATAN MASA SEWA*\n\n" .
                   "Halo Kak *" . $tenant->name . "*,\n" .
                   "Masa aktif akun Anda (" . $tenant->subdomain . ") akan berakhir pada: *" . $tglExpired . "* (7 Hari lagi).\n\n" .
                   "Mohon segera lakukan pembayaran perpanjangan agar akun tidak dibekukan atau suspend oleh sistem.\n\n" .
                   "Terima Kasih,\n*Manajemen Sancaka*";

        try {
            // Panggil Service Fonnte
            FonnteService::sendMessage($nomorWA, $message);

            // 4. Catat ke tabel log tenant_notifications
            DB::table('tenant_notifications')->insert([
                'tenant_id'  => $tenant->id,
                'type'       => 'H-7',
                'sent_at'    => Carbon::now()->toDateString(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            Log::info("CRON REMINDER: Sukses kirim WA H-7 ke {$tenant->name} ({$nomorWA})");
            $this->info("Berhasil kirim ke {$tenant->name}");

        } catch (\Exception $e) {
            Log::error("CRON REMINDER ERROR: Gagal kirim ke {$tenant->name}. Pesan: " . $e->getMessage());
        }
    }
}