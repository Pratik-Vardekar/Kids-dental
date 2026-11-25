<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesPolicy extends Model
{
    protected $table = 'sales_policy';
    protected $fillable = [
        'content'
    ];
}
