<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Slider extends Model
{
    protected $fillable = [
        'title',
        'description',
        'link',
        'image',
        'mobile_image',
        'sort_order',
        'status',
    ];
}