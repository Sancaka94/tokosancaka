<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Mail\NotificationSummaryMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Notifications\DatabaseNotification;
use Carbon\Carbon;

class SendNotificationSummary extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'summary:send-notifications';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Kumpulkan notifikasi dalam 24 jam terakhir dan kirimkan rekapannya ke admin.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Mulai mengumpulkan notifikasi...');

        // 1. Ambil semua notifikasi dari 24 jam terakhir
        $notifications = DatabaseNotification::where('created_at', '>=', Carbon::now()->subDay())->get();

        if ($notifications->isEmpty()) {
            $this->info('Tidak ada notifikasi baru untuk dikirim. Selesai.');
            return 0;
        }

        // 2. Cari semua user dengan role 'Admin'
        $admins = User::where('role', 'Admin')->get();

        if ($admins->isEmpty()) {
            $this->error('Tidak ditemukan user dengan role Admin.');
            return 1;
        }

        $this->info("Menemukan {$notifications->count()} notifikasi dan {$admins->count()} admin.");

        // 3. Kirim email ke setiap admin
        foreach ($admins as $admin) {
            Mail::to($admin->email)->send(new NotificationSummaryMail($notifications));
            $this->info("Email rekapan berhasil dikirim ke: {$admin->email}");
        }

        $this->info('Semua email rekapan berhasil dikirim. Selesai.');
        return 0;
    }
}
