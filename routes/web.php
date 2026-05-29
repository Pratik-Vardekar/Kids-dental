<?php

use App\Http\Controllers\Admin\AddressController;
use App\Http\Controllers\Admin\BlogController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CouponController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\OrderPaymentController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\RefundController;
use App\Http\Controllers\Admin\ReviewController;
use App\Http\Controllers\Admin\SliderController;
use App\Http\Controllers\Admin\SubCategoryController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::resource('subcategory', SubCategoryController::class);
Route::get('test-subcategories/{category_id}', [ProductController::class, 'getSubcategories']);

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});
Route::middleware(['auth', 'admin'])->group(function () {

   Route::get('/admin/dashboard', [DashboardController::class, 'index'])
    ->name('admin.dashboard');

Route::prefix('admin')->group(function () {

    Route::get('category', [CategoryController::class, 'index'])
        ->name('category.index');
Route::get('/category-export',
    [CategoryController::class, 'export'])
    ->name('category.export');
    Route::get('category/create', [CategoryController::class, 'create'])
        ->name('category.create');

    Route::post('category/store', [CategoryController::class, 'store'])
        ->name('category.store');

    Route::get('category/edit/{category}', [CategoryController::class, 'edit'])
        ->name('category.edit');

    Route::put('category/update/{category}', [CategoryController::class, 'update'])
        ->name('category.update');
        Route::put('/category/status/{id}', [CategoryController::class, 'status'])
    ->name('category.status');

    Route::delete('category/delete/{category}', [CategoryController::class, 'destroy'])
        ->name('category.destroy');
        Route::resource('subcategory', SubCategoryController::class);
        Route::put('subcategory/status/{id}',[SubCategoryController::class, 'status'])->name('subcategory.status');


Route::get('subcategory-export',[SubCategoryController::class, 'export'])->name('subcategory.export');
        Route::resource('product', ProductController::class);
        Route::patch('/product/status/{product}', [ProductController::class, 'toggleStatus'])
    ->name('product.status');
    Route::post(
    'product/{product}/duplicate',
    [ProductController::class, 'duplicate']
)->name('product.duplicate');
Route::get('product-export', [ProductController::class, 'export'])->name('product.export');
Route::get('get-subcategories/{category_id}', [ProductController::class, 'getSubcategories'])
    ->name('get.subcategories');

        Route::resource('slider', SliderController::class);
        Route::put('/slider/status/{slider}', [SliderController::class, 'toggleStatus'])
    ->name('slider.status');
    Route::get('slider-export', [SliderController::class, 'export'])->name('slider.export');
                Route::resource('user', UserController::class);
                Route::resource('address', AddressController::class);
                Route::resource('coupon', CouponController::class);
                Route::put('/coupon/status/{coupon}', [CouponController::class, 'toggleStatus'])
    ->name('coupon.status');
    Route::get('coupon-export', [CouponController::class, 'export'])->name('coupon.export');
                Route::resource('order', OrderController::class);
                Route::put(
    'order/status/{order}',
    [OrderController::class, 'statusUpdate']
)->name('order.status');

// Update Payment Status
Route::put(
    'order/payment-status/{order}',
    [OrderController::class, 'paymentStatusUpdate']
)->name('order.payment.status');

// Invoice View
Route::get(
    'order/invoice/{order}',
    [OrderController::class, 'invoice']
)->name('order.invoice');

// Print Invoice
Route::get(
    'order/print/{order}',
    [OrderController::class, 'printInvoice']
)->name('order.print');
Route::get(
    'order/pdf/{order}',
    [OrderController::class, 'downloadPdf']
)->name('order.pdf');
Route::post(
    'order/{order}/payment',
    [OrderPaymentController::class, 'store']
)->name('order.payment.store');
Route::delete(
    'order/payment/{payment}',
    [OrderPaymentController::class, 'destroy']
)->name('order.payment.delete');
 Route::post('/orders/{order}/refund', [RefundController::class, 'store'])->name('refund.store');

    Route::get('/refund/{refund}/approve', [RefundController::class, 'approve'])->name('refund.approve');

    Route::get('/refund/{refund}/reject', [RefundController::class, 'reject'])->name('refund.reject');

    Route::get('/refund/{refund}/complete', [RefundController::class, 'complete'])->name('refund.complete');

Route::get('order-export', [OrderController::class, 'export'])->name('order.export');
Route::resource('blog', BlogController::class);
Route::patch('/blog/status/{blog}', [BlogController::class, 'status'])->name('blog.status');
Route::get('blog-export', [BlogController::class, 'export'])->name('blog.export'); // Add this line

Route::resource('review', ReviewController::class);

Route::post(
    'review/status/{review}',
    [ReviewController::class, 'status']
)->name('review.status');
Route::get('/reviews/export', [ReviewController::class, 'export'])->name('review.export');

        Route::get('/profile', [ProfileController::class, 'index'])
            ->name('admin.profile');

        Route::post('/profile/update', [ProfileController::class, 'update'])
            ->name('admin.profile.update');

        Route::post('/profile/password', [ProfileController::class, 'changePassword'])
            ->name('admin.profile.password');
});

});

require __DIR__.'/auth.php';
