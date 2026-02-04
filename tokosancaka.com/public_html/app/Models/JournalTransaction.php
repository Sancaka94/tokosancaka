<?php

namespace App\Models;

use Scottlaurent\Accounting\Models\JournalTransaction as BaseJournalTransaction;

/**
 * Class JournalTransaction
 *
 * Mewarisi model dasar dari paket akuntansi dan menambahkan
 * relasi kustom ke model Chart of Accounts (Coa) kita.
 */
class JournalTransaction extends BaseJournalTransaction
{
    /**
     * Mendefinisikan relasi "dimiliki oleh" (belongsTo) ke model Coa.
     * Satu transaksi jurnal pasti merujuk ke satu akun (COA).
     */
    public function coa()
    {
        return $this->belongsTo(Coa::class, 'coa_id');
    }
}
