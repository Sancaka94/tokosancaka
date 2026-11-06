<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SyncTenantStatus extends Command
{
    protected $signature = 'tenant:sync-status {--dry : Tampilkan saja tanpa mengubah}';

    protected $description = 'Sinkronkan status tenant dengan tanggal expired_at';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry');
        $now = Carbon::now('Asia/Jakarta');

        $expired = Tenant::whereNotNull('expired_at')
            ->whereNotIn('status', ['inactive', 'suspended'])
            ->where('expired_at', '<', $now)
            ->get();

        if ($expired->isEmpty()) {
            $this->info('Semua status tenant sudah sesuai.');

            return self::SUCCESS;
        }

        foreach ($expired as $t) {
            $this->line(sprintf(
                '%s %-20s paket=%-8s expired=%s',
                $dry ? '[dry]' : '[fix]',
                $t->subdomain,
                (string) $t->package,
                (string) $t->expired_at
            ));

            if (!$dry) {
                $t->status = 'inactive';
                $t->save();
            }
        }

        $this->info(sprintf(
            '%s %d tenant diperbarui menjadi inactive.',
            $dry ? 'Akan' : 'Selesai:',
            $expired->count()
        ));

        return self::SUCCESS;
    }
}
