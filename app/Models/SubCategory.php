<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubCategory extends Model
{
    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'image',
        'status'
    ];

    protected $casts = [
        'status' => 'integer',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function childCategories()
    {
        return $this->hasMany(ChildCategory::class, 'sub_category_id');
    }

    public function products()
    {
        return $this->hasMany(Product::class, 'sub_category_id');
    }

    public function faqs()
    {
        return $this->hasMany(HelpFaq::class, 'sub_category_id')->orderBy('sort_order')->orderBy('id');
    }

    public function activeFaqs()
    {
        return $this->faqs()->where('status', 1);
    }

    public function tutorials()
    {
        return $this->hasMany(Tutorial::class, 'sub_category_id')->orderBy('sort_order')->orderBy('id');
    }

    public function activeTutorials()
    {
        return $this->tutorials()->where('status', 1);
    }
}
