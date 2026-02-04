<?php

namespace App\Models;

// Import trait dan class yang dibutuhkan
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Sanctum\HasApiTokens;

// Import Notifikasi (PENTING UNTUK NOTIFIKASI REAL-TIME)
use Illuminate\Notifications\DatabaseNotification; 

// Import model lain yang direlasikan
use App\Models\Store;
use App\Models\Post;
use App\Models\Toko;
use App\Models\Pesanan;
use App\Models\TopUp;
use App\Models\Order;
use App\Models\Transaction;
use App\Models\Marketplace;
use App\Models\OrderMarketplace;
use App\Models\OrderItemMarketplace; 

class User extends Authenticatable
{
    // Gunakan trait yang relevan
    use HasFactory, Notifiable, HasRoles, SoftDeletes, HasApiTokens;

    /**
     * Nama tabel kustom Anda.
     */
    protected $table = 'Pengguna';

    /**
     * Primary key kustom Anda.
     */
    protected $primaryKey = 'id_pengguna';

    /**
     * Memberi tahu Laravel bahwa Anda HANYA menggunakan 'created_at'.
     */
    const CREATED_AT = 'created_at';
    const UPDATED_AT = null; // Ini akan mencegah Laravel mencari 'updated_at'

    /**
     * Atribut yang dapat diisi secara massal (mass assignable).
     */
    protected $fillable = [
        'nama_lengkap',
        'email',
        'password', // Untuk mutator setPasswordAttribute
        'no_wa',
        'store_name',
        'province',
        'regency',
        'district',
        'village',
        'postal_code',
        'address_detail',
        'bank_name',
        'bank_account_name',
        'bank_account_number',
        'store_logo_path',
        'setup_token',
        'profile_setup_at',
        'role',
        'saldo',
        'status',
        'is_verified',
        'reset_token',
        'token_expiry',
        'ip_address',
        'user_agent',
        'latitude',
        'longitude',
        'last_seen_at',
        // 'created_at' DIHAPUS DARI SINI. Seharusnya tidak di-fillable, 
        // karena dihandle otomatis oleh Eloquent/DB.
    ];

    /**
     * Atribut yang harus disembunyikan saat serialisasi.
     */
    protected $hidden = [
        'password_hash', // Kolom hash password asli di DB
        'remember_token',
        'setup_token',
        'reset_token',
        'password', // Sembunyikan juga atribut virtual 'password'
    ];

    /**
     * Tipe data kustom untuk atribut (casting).
     */
    protected function casts(): array
    {
        return [
            'profile_setup_at' => 'datetime',
            'token_expiry' => 'datetime',
            'last_seen_at' => 'datetime',
            'is_verified' => 'boolean',
            'saldo' => 'decimal:2',
            'created_at' => 'datetime',
            
            // DITAMBAHKAN: Cast untuk SoftDeletes
            'deleted_at' => 'datetime', 
        ];
    }

    // --- Fungsi Otentikasi Kustom ---

    /**
     * Mutator: Otomatis hash password saat 'password' di-set.
     */
    public function setPasswordAttribute($value)
    {
        if (!empty($value)) {
            // Simpan hash ke kolom 'password_hash'
            $this->attributes['password_hash'] = Hash::make($value);
        }
    }

    /**
     * Memberi tahu Laravel cara mengambil password untuk otentikasi.
     */
    public function getAuthPassword()
    {
        return $this->password_hash; // Ambil dari kolom password_hash
    }

    /**
     * Memberi tahu Laravel nama kolom identifier (primary key) untuk otentikasi.
     */
    public function getAuthIdentifierName()
    {
        return 'id_pengguna'; // Gunakan primary key kustom
    }

     /**
      * Override method getKeyName() agar konsisten dengan $primaryKey.
      */
     public function getKeyName()
     {
        return 'id_pengguna';
     }
    
     /**
      * Override method getRouteKeyName() untuk Route Model Binding.
      */
    public function getRouteKeyName()
    {
        return 'id_pengguna'; // Gunakan nama kolom Primary Key Anda
    }


    // =========================================================================
    // DILENGKAPI: Method untuk Notifikasi Real-time
    // =========================================================================

    /**
     * Mendapatkan channel privat untuk broadcast notifikasi pengguna.
     * Ini PENTING untuk Laravel Echo agar tahu harus "mendengarkan" di mana.
     *
     * @return string
     */
    public function receivesBroadcastNotificationsOn()
    {
        // Channel akan menjadi: App.Models.User.123 (jika id_pengguna = 123)
        // Pastikan nama class 'User' di sini sesuai dengan nama file (User.php)
        //
        // JIKA Anda ingin nama channel-nya 'App.Models.Pengguna.123', 
        // Anda harus mengganti nama file/class ini dari User menjadi Pengguna.
        // Untuk saat ini, kita ikuti nama class 'User'.
        return 'App.Models.User.' . $this->getKey();
    }

    /**
     * Relasi HasMany ke notifikasi database.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function databaseNotifications(): HasMany
    {
        return $this->hasMany(DatabaseNotification::class, 'notifiable_id')
                    ->where('notifiable_type', $this->getMorphClass()) // Menggunakan morph class dari model ini
                    ->orderBy('created_at', 'desc');
    }

    // --- Relasi Database ---

    /**
     * Relasi HasOne: User ini memiliki satu Store.
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function store(): HasOne
    {
        return $this->hasOne(Store::class, 'user_id', $this->getKeyName());
    }

    /**
     * Relasi HasMany: User ini memiliki banyak Order (dari model Order).
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'user_id', $this->getKeyName());
    }

    /**
     * Relasi HasMany: User ini memiliki banyak Post.
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class, 'user_id', $this->getKeyName());
    }

    /**
     * Relasi HasOne: User ini memiliki satu Toko (model Toko).
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function toko(): HasOne
    {
        return $this->hasOne(Toko::class, 'id_pengguna_pemilik', $this->getKeyName());
    }

    /**
     * Relasi HasMany: User ini memiliki banyak Pesanan (model Pesanan).
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function pesanans(): HasMany
    {
        return $this->hasMany(Pesanan::class, 'id_pengguna_pembeli', $this->getKeyName());
    }

    /**
     * Relasi HasMany: User ini memiliki banyak TopUp.
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function topUps(): HasMany
    {
        return $this->hasMany(TopUp::class, 'customer_id', $this->getKeyName());
    }
    
    /**
     * Relasi HasMany: User ini memiliki banyak Transaction.
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function transactions(): HasMany
    {
        // Foreign key 'user_id' di tabel transactions
        // Local key 'id_pengguna' di tabel Pengguna
        return $this->hasMany(Transaction::class, 'user_id', 'id_pengguna');
    }

    // =========================================================================
    // DILENGKAPI: Relasi Marketplace (Asumsi)
    // =========================================================================

    /**
     * Relasi HasOne: User (sebagai Seller) memiliki satu toko Marketplace.
     * Asumsi: Foreign key di tabel 'marketplaces' adalah 'seller_id'.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function marketplace(): HasOne
    {
        return $this->hasOne(Marketplace::class, 'seller_id', $this->getKeyName());
    }

    /**
     * Relasi HasMany: User (sebagai Seller) memiliki banyak Order Marketplace.
     * Asumsi: Foreign key di tabel 'order_marketplaces' adalah 'seller_id'.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function marketplaceOrdersAsSeller(): HasMany
    {
        return $this->hasMany(OrderMarketplace::class, 'seller_id', $this->getKeyName());
    }

    /**
     * Relasi HasMany: User (sebagai Customer) memiliki banyak Order Marketplace.
     * Asumsi: Foreign key di tabel 'order_marketplaces' adalah 'customer_id'.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function marketplaceOrdersAsCustomer(): HasMany
    {
        return $this->hasMany(OrderMarketplace::class, 'customer_id', $this->getKeyName());
    }

    // NOTE: Relasi ke OrderItemMerketplace (produk) kemungkinan besar 
    // ada di model Marketplace (toko-nya) atau di OrderMarketplace (pesanan-nya),
    // bukan langsung di User.


    // --- Method Bantuan ---

    /**
     * =========================================================================
     * PERHATIAN: Method ini bisa sangat lambat (masalah performa)!
     * =========================================================================
     *
     * Menghitung saldo saat ini secara "real-time" dengan query database.
     *
     * INI TIDAK DISARANKAN untuk dipanggil di setiap halaman (misal: di header).
     * Jauh lebih baik menggunakan kolom 'saldo' yang ada di tabel 'Pengguna'
     * dan meng-update kolom 'saldo' tsb setiap kali ada 'TopUp' atau 'Pesanan'
     * yang sukses (menggunakan DB Transaction).
     *
     * Gunakan method ini HANYA untuk sinkronisasi/pengecekan berkala jika perlu.
     *
     * @return float Saldo terhitung.
     */
    public function getCurrentBalance(): float
    {
        // OPTIMALISASI: Menggunakan relasi Eloquent, lebih bersih.
        $totalPemasukan = $this->topUps()
                             ->where('status', 'success')
                             ->sum('amount');

        // Sesuaikan status pesanan yang dianggap mengurangi saldo
        $statusesPengeluaran = ['Selesai', 'Terkirim', 'Diproses']; 
        
        $totalPengeluaran = $this->pesanans()
                               ->whereIn('status_pesanan', $statusesPengeluaran)
                               ->sum('total_harga_barang'); // Pastikan ini kolom yang benar

        // Cast ke float untuk memastikan tipe data kembalian
        return (float)($totalPemasukan - $totalPengeluaran);
    }
}