<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    protected $fillable = [

        'code',
        'type',
        'value',
        'minimum_amount',
        'maximum_discount',
        'start_date',
        'end_date',
        'usage_limit',
        'used_count',
        'status',

        // NEW
        'product_ids',
        'exclude_product_ids',
        'exclude_category_ids',

    ];

    protected $casts = [

        'product_ids'           => 'array',
        'exclude_product_ids'   => 'array',
        'exclude_category_ids'  => 'array',

        'start_date'            => 'date',
        'end_date'              => 'date',

    ];

    /*
    |--------------------------------------------------------------------------
    | Included Categories
    |--------------------------------------------------------------------------
    */

    public function categories()
    {
        return $this->belongsToMany(
            Category::class,
            'coupon_category',
            'coupon_id',
            'category_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Included Products
    |--------------------------------------------------------------------------
    */

    public function products()
    {
        return Product::whereIn(
            'id',
            $this->product_ids ?? []
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Excluded Products
    |--------------------------------------------------------------------------
    */

    public function excludedProducts()
    {
        return Product::whereIn(
            'id',
            $this->exclude_product_ids ?? []
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Excluded Categories
    |--------------------------------------------------------------------------
    */

    public function excludedCategories()
    {
        return Category::whereIn(
            'id',
            $this->exclude_category_ids ?? []
        );
    }
}