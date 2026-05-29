<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ProductController;

/*
|--------------------------------------------------------------------------
| Product API Routes
|--------------------------------------------------------------------------
*/

Route::controller(ProductController::class)->group(function () {
   
    //Category
    Route::get('/categories', [ProductController::class, 'categories']);

    //Silder
    Route::get('/sliders', [ProductController::class, 'sliders']);

    // Product List
    Route::get('/products', [ProductController::class, 'index']);

    // Single Product
    // WITHOUT subcategory
    Route::get('/{category_slug}/{slug}', [ProductController::class, 'showWithoutSubcategory']);
   
    // WITH subcategory
    Route::get('/{category_slug}/{subcategory_slug}/{slug}', [ProductController::class, 'showWithSubcategory']);
});