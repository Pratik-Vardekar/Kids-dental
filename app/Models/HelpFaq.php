<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HelpFaq extends Model
{
    protected $fillable = [
        'sub_category_id',
        'help_category_id',
        'question',
        'answer',
        'sort_order',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
        'sort_order' => 'integer',
        'sub_category_id' => 'integer',
        'help_category_id' => 'integer',
    ];

    public function subCategory(): BelongsTo
    {
        return $this->belongsTo(SubCategory::class, 'sub_category_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(HelpCategory::class, 'help_category_id');
    }

    /**
     * Get the subcategory name or category title
     */
    public function getCategoryTitleAttribute(): string
    {
        if ($this->subCategory) {
            return $this->subCategory->name;
        }

        if ($this->category) {
            return $this->category->title;
        }

        return 'General';
    }
}
