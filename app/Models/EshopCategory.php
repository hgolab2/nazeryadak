<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EshopCategory extends Model
{
    protected $table = 'eshop_categories';

    protected $fillable = ['name', 'slug', 'image', 'is_featured'];

    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_in_category', 'category_id', 'product_id');
    }
}
