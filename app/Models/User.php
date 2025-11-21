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
use Illuminate\Database\Eloquent\Relations\HasMany; // <-- DITAMBAHKAN
use Laravel\Sanctum\HasApiTokens;

// Import model lain yang direlasikan (pastikan namespace sudah benar)
use App\Models\Store;
use App\Models\Post;
use App\Models\Toko;
use App\Models\Pesanan;
use App\Models\TopUp;
use App\Models\Order; // <-- DITAMBAHKAN (Untuk relasi orders)


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
     * Nonaktifkan timestamps 'created_at' dan 'updated_at' bawaan Laravel.
     * Pastikan tabel 'Pengguna' Anda memang tidak punya kolom 'updated_at'.
     * Jika ada 'created_at', Anda mungkin perlu:
     * const CREATED_AT = 'created_at'; // Nama kolom created_at Anda
     * const UPDATED_AT = null;
     */
    public $timestamps = false;

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
        'role', // Jika tidak pakai Spatie Roles
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
        'created_at' // Biasanya tidak perlu di fillable jika dihandle otomatis
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
            // 'created_at' => 'datetime', // Aktifkan jika tabel punya kolom ini & $timestamps=true
            'profile_setup_at' => 'datetime',
            'token_expiry' => 'datetime',
            'last_seen_at' => 'datetime', // <-- DITAMBAHKAN cast untuk last_seen_at
            'is_verified' => 'boolean',
            'saldo' => 'decimal:2', // Pastikan tipe kolom di DB mendukung desimal
            'password' => 'hashed', // <-- DITAMBAHKAN: Bekerja jika $timestamps=true & $fillable['password']
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
     * Berguna untuk Eloquent saat menebak nama kunci relasi.
     */
     public function getKeyName()
    {
        return 'id_pengguna';
    }


    // --- Relasi Database ---

    /**
     * Relasi HasOne: User ini memiliki satu Store.
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function store(): HasOne
    {
        // Parameter kedua: foreign key di tabel 'stores'
        // Parameter ketiga: local key (primary key) di tabel 'Pengguna' (model ini)
        return $this->hasOne(Store::class, 'user_id', $this->getKeyName());
    }

     /**
     * Relasi HasMany: User ini memiliki banyak Order (dari model Order).
     * PENTING untuk AdminOrderController.
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function orders(): HasMany // <-- DITAMBAHKAN / DIPASTIKAN ADA
    {
        // Parameter kedua: foreign key di tabel 'orders'
        // Parameter ketiga: local key di tabel 'Pengguna'
        return $this->hasMany(Order::class, 'user_id', $this->getKeyName());
    }

    /**
     * Relasi HasMany: User ini memiliki banyak Post.
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function posts(): HasMany // <-- DITAMBAHKAN type hinting HasMany
    {
        // Parameter foreign key dan local key ditambahkan eksplisit untuk kejelasan
        return $this->hasMany(Post::class, 'user_id', $this->getKeyName());
    }

    /**
     * Relasi HasOne: User ini memiliki satu Toko (model Toko).
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function toko(): HasOne // <-- DITAMBAHKAN type hinting HasOne
    {
        // Parameter foreign key dan local key ditambahkan eksplisit
        return $this->hasOne(Toko::class, 'id_pengguna_pemilik', $this->getKeyName());
    }

    /**
     * Relasi HasMany: User ini memiliki banyak Pesanan (model Pesanan).
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function pesanans(): HasMany // <-- DITAMBAHKAN type hinting HasMany
    {
        // Parameter foreign key dan local key ditambahkan eksplisit
        return $this->hasMany(Pesanan::class, 'id_pengguna_pembeli', $this->getKeyName());
    }

    /**
     * Relasi HasMany: User ini memiliki banyak TopUp.
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function topUps(): HasMany // <-- DITAMBAHKAN type hinting HasMany
    {
        // Parameter foreign key dan local key ditambahkan eksplisit
        return $this->hasMany(TopUp::class, 'customer_id', $this->getKeyName());
    }

    // --- Method Bantuan ---

    /**
     * Menghitung saldo saat ini berdasarkan data top up dan pesanan (model Pesanan).
     * PERHATIAN: Ini mungkin tidak akurat jika ada transaksi lain atau jika performa jadi masalah.
     * @return float Saldo terhitung.
     */
    public function getCurrentBalance(): float // <-- DITAMBAHKAN type hinting float
    {
        // Gunakan $this->getKey() untuk mendapatkan nilai primary key saat ini
        $totalPemasukan = DB::table('top_ups')
                            ->where('customer_id', $this->getKey())
                            ->where('status', 'success')
                            ->sum('amount');

        // Sesuaikan status pesanan yang dianggap mengurangi saldo
        $statusesPengeluaran = ['Selesai', 'Terkirim', 'Diproses']; // Atau hanya 'Selesai'?
        $totalPengeluaran = DB::table('pesanan') // Gunakan nama tabel 'pesanan'
                                ->where('id_pengguna_pembeli', $this->getKey())
                                ->whereIn('status_pesanan', $statusesPengeluaran)
                                ->sum('total_harga_barang'); // Pastikan ini kolom yang benar

        // Cast ke float untuk memastikan tipe data kembalian
        return (float)($totalPemasukan - $totalPengeluaran);
    }

    // Tambahkan method lain jika diperlukan...
}

