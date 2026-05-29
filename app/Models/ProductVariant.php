<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    protected $fillable = [
        'product_id',
        'sku',
        'price',
        'selling_price',
        'quantity',
        'image',
        'status',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function attributes()
    {
        return $this->hasMany(ProductVariantAttribute::class, 'variant_id');
    }
    public function variantAttributes() { return $this->hasMany( ProductVariantAttribute::class, 'variant_id' ); }
}