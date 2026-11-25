<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'image',
        'description',
        'status',
        'sort_order',
    ];

    protected $casts = [
        'status' => 'integer',
        'sort_order' => 'integer',
    ];

    // Category has many subcategories
    public function subcategories()
    {
        return $this->hasMany(SubCategory::class);
    }

    // Category has many child categories through subcategories
    public function childCategories()
    {
        return $this->hasManyThrough(ChildCategory::class, SubCategory::class, 'category_id', 'sub_category_id');
    }

    // Category has many coupons
    public function coupons()
    {
        return $this->belongsToMany(
            Coupon::class,
            'coupon_category',
            'category_id',
            'coupon_id'
        );
    }

    // Category has many products
    public function products()
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Effective product price SQL.
     */
    public static function productPriceSql(string $table = 'products'): string
    {
        return "COALESCE(NULLIF({$table}.selling_price, 0), NULLIF({$table}.price, 0))";
    }

    /**
     * Eager-load the lowest active product price.
     */
    public function scopeWithStartingFromPrice(Builder $query): Builder
    {
        $priceSql = static::productPriceSql('products');

        return $query
            ->select($query->getModel()->getTable() . '.*')
            ->selectSub(
                Product::query()
                    ->selectRaw("MIN({$priceSql})")
                    ->whereColumn(
                        'products.category_id',
                        $query->getModel()->getTable() . '.id'
                    )
                    ->where('products.status', 1)
                    ->whereRaw("{$priceSql} > 0"),
                'starting_from_price'
            );
    }

    /**
     * Minimum active product price.
     */
    public function getStartingFromPrice(): ?float
    {
        if (array_key_exists('starting_from_price', $this->attributes)) {
            $value = $this->attributes['starting_from_price'];

            return $value !== null ? (float) $value : null;
        }

        $priceSql = static::productPriceSql('products');

        $min = $this->products()
            ->where('status', 1)
            ->whereRaw("{$priceSql} > 0")
            ->selectRaw("MIN({$priceSql}) as min_price")
            ->value('min_price');

        return $min !== null ? (float) $min : null;
    }

    /**
     * Formatted starting price.
     */
    public function formattedStartingFromPrice(string $fallback = '—'): string
    {
        $price = $this->getStartingFromPrice();

        return $price !== null
            ? '₹' . number_format($price, 2)
            : $fallback;
    }
}
