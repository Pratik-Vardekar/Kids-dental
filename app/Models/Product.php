<?php

namespace App\Models;

use App\Models\Cart;
use App\Models\ProductVariant;
use App\Models\Wishlist;
use App\Models\Category;
use App\Models\SubCategory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [

        'category_id',
        'subcategory_id',
        'child_category_id',

        'name',
        'slug',

        'thumbnail',

        'price',
        'selling_price',

        'details',
        'description',
        'specification',

        'quantity',

        'gallery',
        'gallery_order',

        'status',

        'is_trending',
        'is_featured',
        'is_best_selling',
        'is_top_rated',
        'is_new_arrival',

        'badge',
        'discount',
        'sku',
        'product_type',
        'gst_setting_id',


    ];

    protected $casts = [
        'price' => 'float',
        'selling_price' => 'float',
        'gallery' => 'array',
        'gallery_order' => 'array',

        'is_trending' => 'boolean',
        'is_featured' => 'boolean',
        'is_best_selling' => 'boolean',
        'is_top_rated' => 'boolean',
        'is_new_arrival' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function subcategory()
    {
        return $this->belongsTo(SubCategory::class, 'subcategory_id');
    }

    public function childCategory()
    {
        return $this->belongsTo(ChildCategory::class, 'child_category_id');
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Average Rating
    |--------------------------------------------------------------------------
    */

    public function averageRating()
    {
        return round(
            $this->reviews()
                ->where('status', 1)
                ->avg('rating'),
            1
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Total Reviews
    |--------------------------------------------------------------------------
    */

    public function totalReviews()
    {
        return $this->reviews()
            ->where('status', 1)
            ->count();
    }
    public function carts()
    {
        return $this->hasMany(Cart::class);
    }

    public function wishlists()
    {
        return $this->hasMany(Wishlist::class);
    }
    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function activeVariants()
    {
        return $this->hasMany(ProductVariant::class)->where('status', 1);
    }

    public function isVariable(): bool
    {
        return $this->product_type === 'variable';
    }
    public function faqs()
    {
        return $this->hasMany(ProductFaq::class)->orderBy('order');
    }

    public function gstSetting()
    {
        return $this->belongsTo(GSTSetting::class, 'gst_setting_id');
    }

    /**
     * Derive discount % from MRP vs selling price, or use submitted value as fallback.
     */
    public static function calculateDiscountPercent(float $price, float $sellingPrice, $fallbackDiscount = null): int
    {
        if ($price > 0 && $sellingPrice > 0 && $sellingPrice < $price) {
            return (int) round((($price - $sellingPrice) / $price) * 100);
        }

        if ($fallbackDiscount !== null && $fallbackDiscount !== '') {
            return max(0, (int) round((float) $fallbackDiscount));
        }

        return 0;
    }

    public function getInclusiveTaxInfo(float $inclusivePrice): array
    {
        $gstPercentage = (float) ($this->gstSetting->gst_percentage ?? 0);

        if ($gstPercentage <= 0 || $inclusivePrice <= 0) {
            return [
                'gst_percentage' => 0,
                'tax_amount' => 0,
                'taxable_amount' => $inclusivePrice,
                'display_text' => 'MRP inclusive of all taxes',
            ];
        }

        $taxableAmount = round($inclusivePrice / (1 + ($gstPercentage / 100)), 2);
        $taxAmount = round($inclusivePrice - $taxableAmount, 2);

        return [
            'gst_percentage' => $gstPercentage,
            'tax_amount' => $taxAmount,
            'taxable_amount' => $taxableAmount,
            'display_text' => $gstPercentage . '% GST (inclusive)',
        ];
    }

    public function getFrontendUrl(): string
    {
        $baseUrl = rtrim(config('app.frontend_url', env('FRONTEND_URL', 'https://kids-e-dental.vercel.app')), '/');

        return "{$baseUrl}/product-details/{$this->slug}";
    }

    public function getProductImageUrl(?string $filename = null): string
    {
        $filename = $filename ?: $this->thumbnail;

        if (!empty($filename)) {
            return asset('backend/assets/images/product/' . $filename);
        }

        return asset('frontend/assets/images/no-image.png');
    }

    public function getSliderImages(): array
    {
        $ordered = $this->gallery_order ?: $this->gallery;
        $images = is_array($ordered) ? array_values(array_filter($ordered)) : [];

        if (!empty($images)) {
            return $images;
        }

        return !empty($this->thumbnail) ? [$this->thumbnail] : [];
    }

    public function specificationRows(): array
    {
        $specification = $this->specification;

        if (empty($specification)) {
            return [];
        }

        if (is_array($specification)) {
            return collect($specification)
                ->map(function ($row) {
                    if (!is_array($row)) {
                        return null;
                    }

                    $label = trim((string) ($row['label'] ?? $row['key'] ?? ''));
                    $value = trim((string) ($row['value'] ?? ''));

                    return ($label !== '' && $value !== '')
                        ? ['label' => $label, 'value' => $value]
                        : null;
                })
                ->filter()
                ->values()
                ->all();
        }

        if (!is_string($specification)) {
            return [];
        }

        $rows = [];
        preg_match_all('/<tr[^>]*>(.*?)<\/tr>/is', $specification, $matches);

        foreach ($matches[1] as $rowHtml) {
            preg_match_all('/<t[dh][^>]*>(.*?)<\/t[dh]>/is', $rowHtml, $cols);
            $cells = array_map(
                fn($cell) => trim(html_entity_decode(strip_tags($cell))),
                $cols[1] ?? []
            );

            if (count($cells) >= 3) {
                $first = $cells[0];
                if (
                    preg_match('/^\d{1,3}$/', $first)
                    || in_array(mb_strtolower(rtrim($first, '.')), ['row no', 'row number', 's.no', 'sr no', 'no', '#'], true)
                ) {
                    $label = $cells[1];
                    $value = $cells[2];
                } else {
                    $label = $cells[0];
                    $value = implode(' ', array_slice($cells, 1));
                }
            } elseif (isset($cells[0], $cells[1])) {
                $label = $cells[0];
                $value = $cells[1];
            } else {
                continue;
            }

            $label = trim($label);
            $value = trim($value);
            $normalizedLabel = mb_strtolower(rtrim($label, '.'));

            if (
                $label === ''
                || $value === ''
                || in_array($normalizedLabel, ['specification', 'specifications', 'details', 'detail', 'row no', 'row number'], true)
            ) {
                continue;
            }

            $rows[] = [
                'label' => $label,
                'value' => $value,
            ];
        }

        return $rows;
    }
}
