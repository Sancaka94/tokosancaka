<?php



namespace App\Models;



use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;

use App\Models\User; // <-- PERBAIKAN: Mengarah ke lokasi User model yang benar



class Post extends Model

{

    use HasFactory;



    protected $fillable = [

        'user_id',

        'category_id',

        'title',

        'slug',

        'content',

        'featured_image',

        'status',
        
        'original_url', // <--- TAMBAHKAN INI

    ];



    /**

     * Mendefinisikan relasi many-to-one ke model Category.

     */

    public function category()

    {

        return $this->belongsTo(Category::class);

    }



   /**

    * Mendapatkan user (author) yang memiliki post ini.

    */

    public function author()

    {

        // Post ini 'milik' satu User.

        // Relasi ini bekerja dengan mencocokkan kolom 'user_id' di tabel posts

        // dengan kolom 'id' di tabel users.

        return $this->belongsTo(User::class, 'user_id');

    }



    /**

     * Mendefinisikan relasi many-to-many ke model Tag.

     */

    public function tags()

    {

        return $this->belongsToMany(Tag::class);

    }

    /**
     * Scope Lokal: Hanya post yang sudah dipublikasikan.
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    /**
     * Scope Lokal: Muat relasi dasar (Category dan Author).
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithRelationships($query)
    {
        // Pastikan relasi 'category' dan 'author' sudah didefinisikan di Post model
        return $query->with('category', 'author');
    }

    /**
     * Mendefinisikan kolom untuk Route Model Binding (untuk URL slug).
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

}

