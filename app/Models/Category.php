<?php



namespace App\Models;



use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;



class Category extends Model

{

    use HasFactory;



    protected $fillable = ['name', 'slug', 'user_id'];



    /**

     * Mendefinisikan relasi one-to-many ke model Post.

     */

    public function posts()

    {

        return $this->hasMany(Post::class);

    }

}

public function products()
{
    return $this->hasMany(Product::class);
}