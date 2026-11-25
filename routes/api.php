<?php

use App\Http\Controllers\Api\AddressController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CheckoutController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\PrivacyPolicyController;
use App\Http\Controllers\Api\TermsConditionController;
use App\Http\Controllers\Api\SalesPolicyController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\DistributorEnquiryController;
use App\Http\Controllers\Api\FaqController;
use App\Http\Controllers\Api\TutorialController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Authentication API Routes (Public)
|--------------------------------------------------------------------------
*/

Route::get('/location/check', [LocationController::class, 'check']);
Route::get('/location-details', [LocationController::class, 'getLocationDetails']);
Route::controller(AuthController::class)->group(function () {
    Route::post('/register', 'register');
    Route::post('/login', 'login');
    Route::post('/forgot-password', 'forgotPassword');
    Route::post('/reset-password', 'resetPassword');

    // Email OTP Verification Flow
    Route::post('/verify-otp', 'verifyOtp');
    Route::post('/resend-otp', 'resendOtp');

    // // Google OAuth
    // Route::post('/google-login', 'googleLogin');
});

Route::post('/payment/webhook', [PaymentController::class, 'webhook']);

/*
|--------------------------------------------------------------------------
| Authenticated User Routes (Sanctum)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    Route::controller(AuthController::class)->group(function () {
        Route::get('/user-profile', 'profile');
        Route::post('/user-profile/update', 'updateProfile');
        Route::post('/change-password', 'changePassword');
        Route::post('/logout', 'logout');
    });


    Route::controller(ProductController::class)->group(function () {
        Route::post('/merge-cart', 'mergeCart');
        Route::post('/add-to-wishlist', 'addToWishlist');
        Route::get('/get-wishlist', 'getWishlist');
        Route::post('/remove-wishlist-item', 'removeWishlistItem');
        Route::post('/clear-wishlist', 'clearWishlist');
        Route::get('/wishlist-count', 'wishlistCount');
        Route::post('/add-review', 'addReview');
        Route::get('/get-review/{id}', 'getReview');
        Route::post('/edit-review', 'editReview');
        Route::post('/update-review', 'updateReview');
        Route::post('/delete-review', 'deleteReview');
        Route::get('/my-reviews', 'myReviews');
        Route::post('/review-like', 'reviewLike');
        Route::post('/review-dislike', 'reviewDislike');
    });

    Route::controller(PaymentController::class)->group(function () {
        Route::post('/payment/create-order', 'createOrder');
        Route::post('/payment/verify', 'verifyPayment');
    });

    Route::controller(OrderController::class)->group(function () {
        Route::post('/order/create', 'createOrder');
        Route::get('/order/payment-status/{id}', 'paymentStatus');
        Route::get('/my-orders', 'myOrders');
        Route::get('/order/view/{id}', 'orderView');
        Route::post('/order/cancel/{id}', 'cancelOrder');
        Route::get('/order/track/{id}', 'trackOrder');
    });
});

/*
|--------------------------------------------------------------------------
| Product API Routes (NO AUTH)
|--------------------------------------------------------------------------
*/
Route::controller(ProductController::class)->group(function () {

    // ========== BUY NOW & CHECKOUT ROUTES ==========
    Route::match(['get', 'post'], '/buy-now/get', 'getBuyNow');
    Route::match(['post'], '/buy-now/add', 'addToBuyNow');
    // Route::post('/buy-now/add', 'addToBuyNow');
    Route::post('/buy-now/update', 'updateBuyNow');
    Route::post('/buy-now/update-address', 'updateBuyNowAddress');
    Route::match(['get', 'post'], '/buy-now/apply-coupon', 'applyCouponToBuyNow');
    Route::match(['get', 'post'], '/buy-now/remove-coupon', 'removeCouponFromBuyNow');
    Route::post('/buy-now/checkout', 'buyNowCheckout');
    Route::post('/checkout/order', 'buyNowCheckout');
    Route::match(['get', 'post', 'delete'], '/buy-now/remove', 'removeBuyNow');

    // Home page
    Route::get('/home', 'home');
    Route::get('/why-choose-us', 'whyChooseUs');
    Route::get('/product-listings', 'productListings');

    // Category
    Route::get('/categories', 'categories');
    Route::get('/subcategories', 'subcategories');
    Route::get('/all-category-subcategories', 'allCategorySubcategories');

    // Slider
    Route::get('/sliders', 'sliders');

    // Blog List
    Route::get('/related-blogs/{slug}', 'relatedBlogs');
    Route::get('/blogs-categories', 'blogCategories');
    Route::post('/blogs-list', 'blogs');
    Route::get('/blogs/{slug}', 'blogDetails');

    // Search Products
    Route::get('/search', 'searchProducts');

    // Product List & Details
    Route::get('/products', 'index');
    Route::get('/products/best-selling', 'bestSelling');
    Route::get('/products/top-rated', 'topRated');
    Route::get('/products/{id}', 'show')->where('id', '[0-9]+');
    Route::post('/product/{slug?}', 'productDetails');

    // Create Product
    Route::post('/products', 'store');

    // Cart & Coupon Routes
    Route::post('/add-to-cart', 'addToCart');
    Route::get('/get-cart', 'getCart');
    Route::post('/update-cart', 'updateCart');
    Route::post('/remove-cart-item', 'removeCartItem');
    Route::post('/clear-cart', 'clearCart');
    Route::post('/cart-count', 'cartCount');
    Route::match(['get', 'post'], '/apply-coupon', 'applyCoupon');
    Route::match(['get', 'post'], '/remove-coupon', 'removeCoupon');
    Route::match(['get', 'post'], '/apply-coupon-to-cart', 'applyCouponToCart');
    Route::match(['get', 'post'], '/remove-coupon-from-cart', 'removeCouponFromCart');
    Route::get('/available-coupons', 'availableCoupons');

    // Address Routes (Accessible for Cart & Buy Now page)
    Route::controller(AddressController::class)->group(function () {
        Route::get('/all-addresses', 'index');
        Route::post('/add-address', 'store');
        Route::get('/address/{id}', 'show');
        Route::post('/update-address', 'update');
        Route::post('/delete-address', 'destroy');
    });

    // Footer
    Route::post('/subscribe-email', 'emails');
    Route::post('/product-question', 'addProductQuestion');

    // Privacy Policy & Terms
    Route::get('/privacy-policy', [PrivacyPolicyController::class, 'privacyPolicy']);
    Route::get('/terms-conditions', [TermsConditionController::class, 'termsConditions']);
    Route::get('/sales-policy', [SalesPolicyController::class, 'salesPolicy']);
    Route::get('/salesPolicy', [SalesPolicyController::class, 'salesPolicy']);

    // Contact & Enquiry API
    Route::get('/contact-details', [ContactController::class, 'contactDetails']);
    Route::post('/contact-enquiry', [ContactController::class, 'store']);
    Route::post('/contact-us', [ContactController::class, 'store']);
    Route::get('/contact-enquiries', [ContactController::class, 'enquiries']);

    // Distributor Enquiry API
    Route::post('/distributor-enquiry', [DistributorEnquiryController::class, 'store']);
    Route::post('/become-distributor', [DistributorEnquiryController::class, 'store']);
    Route::post('/distributor-enquiries', [DistributorEnquiryController::class, 'store']);
    Route::get('/distributor-enquiries', [DistributorEnquiryController::class, 'index']);

    // FAQs (Category-wise & Home FAQs)
    Route::get('/category-faqs/{category_slug?}', [FaqController::class, 'getCategoryFaqs']);

    // Subcategory-wise Tutorials
    Route::get('/tutorial-categories', [TutorialController::class, 'getTutorialSubcategories']);
    Route::get('/tutorial-categories/{category_slug}', [TutorialController::class, 'getCategoryTutorials']);
    Route::get('/category-tutorials/{category_slug?}', [TutorialController::class, 'getCategoryTutorials']);
    Route::get('/tutorials/{category_slug?}', [TutorialController::class, 'getCategoryTutorials']);

    // ========== CATEGORY ROUTES (MUST BE LAST - THEY CATCH ALL SINGLE SEGMENTS) ==========
    Route::get('/price-range/{category_slug}/{subcategory_slug?}', 'productPriceRange');
    Route::get('/{category_slug}', 'categorywiseProduct')->where('category_slug', '^(?!(checkout|buy-now|remove-coupon|remove-coupon-from-cart|apply-coupon|apply-coupon-to-cart|available-coupons|add-to-cart|get-cart|update-cart|clear-cart|cart-count|all-addresses|add-address|update-address|delete-address|location-details|location_details|locationDetails|location|distributor-enquiry|become-distributor|distributor-enquiries|faq-categories|category-faqs|help-faqs|home-faqs|tutorials|category-tutorials|tutorial-categories)).*$');
    Route::get('/{category_slug}/{subcategory_slug}', 'subcategorywiseProduct')->where('category_slug', '^(?!(checkout|buy-now)).*$');
});
