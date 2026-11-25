<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class HelpCategory extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'color',
        'sort_order',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected $appends = [
        'slug',
    ];

    public const COLORS = [
        'yellow' => 'Yellow',
        'teal' => 'Teal',
        'purple' => 'Purple',
        'orange' => 'Orange',
    ];

    protected static function booted()
    {
        static::saving(function ($category) {
            if (empty($category->slug) && !empty($category->title)) {
                $category->slug = Str::slug($category->title);
            }
        });
    }

    public function getSlugAttribute(): string
    {
        $dbSlug = $this->attributes['slug'] ?? null;
        if (!empty($dbSlug)) {
            return $dbSlug;
        }

        return !empty($this->title) ? Str::slug($this->title) : '';
    }

    public function faqs(): HasMany
    {
        return $this->hasMany(HelpFaq::class)->orderBy('sort_order')->orderBy('id');
    }

    public function activeFaqs(): HasMany
    {
        return $this->faqs()->where('status', 1);
    }

    public function getBgClassAttribute(): string
    {
        return match ($this->color) {
            'teal' => 'bgTeal',
            'purple' => 'bgPurple',
            'orange' => 'bgOrange',
            default => 'bgYellow',
        };
    }

    public function getBgSubClassAttribute(): string
    {
        return match ($this->color) {
            'teal' => 'bgTealSub',
            'purple' => 'bgPurpleSub',
            'orange' => 'bgOrangeSub',
            default => 'bgYellowSub',
        };
    }
}
