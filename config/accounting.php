<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Models
    |--------------------------------------------------------------------------
    |
    | If you want to use your own models you can specify them here.
    |
    */
    'ledger_model'              => \Scottlaurent\Accounting\Models\Ledger::class,
    'journal_model'             => \Scottlaurent\Accounting\Models\Journal::class,
    
    // ✅ UBAH BARIS INI
    // Arahkan ke model baru yang sudah kita buat.
    'journal_transaction_model' => \App\Models\JournalTransaction::class,
    
    /*
    |--------------------------------------------------------------------------
    | Double Entry Locking
    |--------------------------------------------------------------------------
    |
    | If you want to enable pessimisitc locking on the double entry transactions
    | to prevent race conditions, you can enable it here.
    |
    */
    'double_entry_lock' => env('ACCOUNTING_DOUBLE_ENTRY_LOCK', false),

    /*
     |--------------------------------------------------------------------------
     | Maximum Journal Transaction Amount
     |--------------------------------------------------------------------------
     |
     | If you want to limit the maximum amount of a journal transaction
     |
     */
    'max_journal_transaction_amount' => null,
];
