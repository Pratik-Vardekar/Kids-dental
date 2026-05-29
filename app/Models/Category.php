<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'image',
        'status'
    ];
    public function subcategories()
{
    return $this->hasMany(SubCategory::class);
}
public function coupons()
{
    return $this->belongsToMany(
        Coupon::class,
        'coupon_category',
        'category_id',
        'coupon_id'
    );
}
}