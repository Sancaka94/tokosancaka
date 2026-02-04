<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// PENTING: Pastikan Anda mengimpor Model yang BENAR
use App\Models\OrderMarketplace; 
use App\Models\Marketplace;
use App\Models\ProductVariant;

class OrderItemMerketplace extends Model
{
    use HasFactory;

    /**
     * Nama tabel Anda (Sudah Benar)
     */
    protected $table = 'order_item_merketplaces'; 

    /**
     * Mass Assignment (Sudah Benar)
     */
    protected $fillable = [
        'order_id',
        'product_id',
        'product_variant_id',
        'quantity',
        'price',
    ];

    /**
     * Relasi ke Order (Induk) (Sudah Benar)
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(OrderMarketplace::class, 'order_id');
    }

    /**
     * Relasi ke Produk (Sudah Benar)
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Marketplace::class, 'product_id');
    }

    /**
     * Relasi ke Varian (Sudah Benar)
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }
    
    // FUNGSI items() YANG SALAH SUDAH DIHAPUS
}