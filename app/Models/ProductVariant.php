<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    protected $fillable = [
        'product_id',
        'arch',
        'size',
        'sku',
        'price',
        'selling_price',
        'quantity',
        'image',
        'status',
    ];

    protected $casts = [
        'price' => 'float',
        'selling_price' => 'float',
        'quantity' => 'integer',
        'status' => 'boolean',
    ];

    protected $appends = [
        'variant_title',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function attributes()
    {
        return $this->hasMany(ProductVariantAttribute::class, 'variant_id');
    }

    public function variantAttributes()
    {
        return $this->hasMany(ProductVariantAttribute::class, 'variant_id');
    }

    public function getArchAttribute($value)
    {
        if (!empty($value)) {
            return $value;
        }
        if (!empty($this->sku)) {
            $skuUpper = strtoupper($this->sku);
            if (str_contains($skuUpper, '-LL-') || str_ends_with($skuUpper, '-LL')) return 'Lower Left';
            if (str_contains($skuUpper, '-LR-') || str_ends_with($skuUpper, '-LR')) return 'Lower Right';
            if (str_contains($skuUpper, '-UL-') || str_ends_with($skuUpper, '-UL')) return 'Upper Left';
            if (str_contains($skuUpper, '-UR-') || str_ends_with($skuUpper, '-UR')) return 'Upper Right';
        }
        return $value;
    }

    public function getSizeAttribute($value)
    {
        if ($value !== null && $value !== '') {
            return (string) $value;
        }
        if (!empty($this->sku)) {
            if (preg_match('/-(?:LL|LR|UL|UR)-([0-9]+)/', strtoupper($this->sku), $matches)) {
                return $matches[1];
            }
        }
        return $value !== null ? (string) $value : null;
    }

    public function getVariantTitleAttribute(): string
    {
        $parts = array_filter([$this->arch, $this->size ? 'Size ' . $this->size : null]);
        return !empty($parts) ? implode(' - ', $parts) : ($this->sku ?? ('Variant #' . $this->id));
    }
}
