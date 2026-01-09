<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Coa extends Model
{
    use HasFactory;

    /**
     * Atribut yang boleh diisi secara massal.
     *
     * @var array
     */
    protected $fillable = [
        'tenant_id',
        'kode',
        'nama',
        'tipe',
    ];

    /**
     * PERBAIKAN: Menambahkan relasi ke JournalTransaction.
     * Mendefinisikan bahwa satu Akun (COA) bisa memiliki banyak transaksi jurnal.
     */
    public function journalTransactions()
    {
        // Menghubungkan model Coa ke model JournalTransaction
        // Foreign key di tabel 'accounting_journal_transactions' adalah 'coa_id'
        // Local key di tabel 'coas' adalah 'id'
        return $this->hasMany(\App\Models\JournalTransaction::class, 'coa_id', 'id');
    }
}

