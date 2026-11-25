<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChildCategory extends Model
{
    protected $fillable = [
        'sub_category_id',
        'name',
        'slug',
        'image',
        'status',
    ];

    protected $casts = [
        'status' => 'integer',
    ];

    // Child Category belongs to SubCategory
    public function subcategory()
    {
        return $this->belongsTo(SubCategory::class, 'sub_category_id');
    }

    // Child Category belongs to Parent Category through SubCategory
    public function category()
    {
        return $this->hasOneThrough(
            Category::class,
            SubCategory::class,
            'id',
            'id',
            'sub_category_id',
            'category_id'
        );
    }

    // Child Category has many products
    public function products()
    {
        return $this->hasMany(Product::class, 'child_category_id');
    }
}
