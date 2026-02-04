<?php

namespace App\Exports;

use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class ProductsExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    protected $search;
    protected $storeId;

    /**
     * Menerima query pencarian dari controller
     */
    public function __construct($search)
    {
        $this->search = $search;

        // Dapatkan ID store (yang merupakan ID pengguna) dari user yang sedang login
        $user = Auth::user();
        $this->storeId = $user->id_pengguna ?? $user->id; 
    }

    /**
    * Query ini akan mengambil data dari database.
    * Ini adalah query yang sama dengan di halaman index Anda.
    *
    * @return \Illuminate\Database\Query\Builder
    */
    public function query()
    {
        // Buat query dasar, sama seperti di controller
        $query = Product::query()
                        ->where('store_id', $this->storeId) // Filter HANYA untuk toko user ini
                        ->with('category'); // Load relasi kategori (penting untuk map)

        // Terapkan filter pencarian jika ada
        if ($this->search) {
            $query->where(function($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('sku', 'like', '%' . $this->search . '%');
            });
        }

        return $query->latest(); // Urutkan berdasarkan yang terbaru
    }

    /**
    * Ini adalah baris judul (header) di file Excel Anda.
    *
    * @return array
    */
    public function headings(): array
    {
        return [
            'ID',
            'Nama Produk',
            'SKU',
            'Kategori',
            'Harga',
            'Stok',
            'Berat (gram)',
            'Status',
            'Dibuat Pada',
        ];
    }

    /**
    * Ini memetakan data dari $product ke setiap kolom.
    *
    * @var Product $product
    */
    public function map($product): array
    {
        return [
            $product->id,
            $product->name,
            $product->sku ?? '-',
            $product->category->name ?? '-', // Mengambil nama dari relasi
            $product->price,
            $product->stock,
            $product->weight,
            $product->status == 'active' ? 'Aktif' : 'Tidak Aktif',
            $product->created_at->format('Y-m-d H:i:s'),
        ];
    }
}