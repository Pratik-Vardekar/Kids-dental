<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Controllers
|--------------------------------------------------------------------------
*/

// Frontend
use App\Http\Controllers\Frontend\FrontController;

// Authentication
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\NewPasswordController;

// Admin
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\SubCategoryController;
use App\Http\Controllers\Admin\ChildCategoryController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ReviewController;
use App\Http\Controllers\Admin\SliderController;
use App\Http\Controllers\Admin\BlogController;
use App\Http\Controllers\Admin\HomeBannerController;
use App\Http\Controllers\Admin\ContactSettingController;
use App\Http\Controllers\Admin\FooterContactSettingController;
use App\Http\Controllers\Admin\HeaderTopBarSettingController;
use App\Http\Controllers\Admin\AddressController;
use App\Http\Controllers\Admin\CouponController;
use App\Http\Controllers\Admin\GstSettingController;
use App\Http\Controllers\Admin\ShippingSettingController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\CustomerActivityController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\OrderPaymentController;
use App\Http\Controllers\Admin\RefundController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\GalleryController;
use App\Http\Controllers\Admin\AboutUsController;
use App\Http\Controllers\Admin\PrivacyPolicyController;
use App\Http\Controllers\Admin\TermsAndConditionController;
use App\Http\Controllers\Admin\FeatureController;
use App\Http\Controllers\Admin\BenefitController;
use App\Http\Controllers\Admin\PlayFunSectionController;
use App\Http\Controllers\Admin\LearnFunSectionController;
use App\Http\Controllers\Admin\HowToPlaySectionController;
use App\Http\Controllers\Admin\HomeFaqController;
use App\Http\Controllers\Admin\HelpCategoryController;
use App\Http\Controllers\Admin\HelpFaqController;
use App\Http\Controllers\Admin\HomeTestimonialController;
use App\Http\Controllers\Admin\HomeHighlightController;
use App\Http\Controllers\Admin\ContactEnquiryController;
use App\Http\Controllers\Admin\DistributorEnquiryController;
use App\Http\Controllers\Admin\WhyChooseUsController;
use App\Http\Controllers\Admin\SalesPolicyController;
use App\Http\Controllers\Admin\TutorialController;

// API
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\FaqController as PublicFaqController;
use App\Http\Controllers\Api\TutorialController as PublicTutorialController;

/*
|--------------------------------------------------------------------------
| PUBLIC FAQ & TUTORIAL API ROUTES (Supports requests with or without /api/ prefix)
|--------------------------------------------------------------------------
*/
Route::get('/faq-categories', [PublicFaqController::class, 'getFaqCategories']);
Route::get('/faq-categories/{category_slug}', [PublicFaqController::class, 'getCategoryFaqs']);
Route::get('/category-faqs/{category_slug?}', [PublicFaqController::class, 'getCategoryFaqs']);
Route::get('/help-faqs/{category_slug?}', [PublicFaqController::class, 'getCategoryFaqs']);
Route::get('/home-faqs', [PublicFaqController::class, 'getHomeFaqs']);

// Subcategory-wise Tutorials
Route::get('/tutorial-categories', [PublicTutorialController::class, 'getTutorialSubcategories']);
Route::get('/tutorial-categories/{category_slug}', [PublicTutorialController::class, 'getCategoryTutorials']);
Route::get('/category-tutorials/{category_slug?}', [PublicTutorialController::class, 'getCategoryTutorials']);
Route::get('/tutorials/{category_slug?}', [PublicTutorialController::class, 'getCategoryTutorials']);


/*
|--------------------------------------------------------------------------
| AUTH & ENTRY ROUTES (ADMIN PORTAL ONLY)
|--------------------------------------------------------------------------
*/

Route::get('/favicon.ico', function () {
    $path = base_path('backend/assets/images/kids-dental-favicon.png');
    if (file_exists($path)) {
        return response()->file($path, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
    abort(404);
});

// Root Entry: Redirect to Dashboard if logged in with staff/admin access, else show Login
Route::get('/', function () {
    if (auth()->check()) {
        $user = auth()->user();
        if (method_exists($user, 'canAccessAdmin') && $user->canAccessAdmin()) {
            return redirect()->route('admin.dashboard');
        } elseif ($user->role_id == 1 || $user->role === 'admin') {
            return redirect()->route('admin.dashboard');
        }
    }
    return redirect()->route('login');
})->name('home');

// LOGIN & LOGOUT (Admin Panel)
Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
Route::post('/login', [AuthenticatedSessionController::class, 'store']);
Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

// REDIRECT PUBLIC REGISTRATION TO LOGIN (Admin-only portal)
Route::get('/signup', function () {
    return redirect()->route('login');
})->name('register');
Route::post('/register', function () {
    return redirect()->route('login');
});

// PASSWORD RESET
Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');
Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
Route::post('/reset-password', [NewPasswordController::class, 'store'])->name('password.store');

// FRONTEND PUBLIC CONTACT
Route::get('/contact-us', [FrontController::class, 'contactUs'])->name('contact.us');
Route::post('/contact-enquiry', [FrontController::class, 'storeContactEnquiry'])->name('contact.enquiry.store');

/*
|--------------------------------------------------------------------------
| USER ACCOUNT & ORDERS
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::get('/profile/address', [FrontController::class, 'myAccountAddresses'])->name('profile.address');
    Route::post('/profile/address', [FrontController::class, 'storeAddress'])->name('profile.address.store');
    Route::post('/profile/address/{address}/default', [FrontController::class, 'setDefaultAddress'])->name('profile.address.default');
    Route::delete('/profile/address/{address}', [FrontController::class, 'destroyAddress'])->name('profile.address.destroy');
    Route::get('/profile/profile', [FrontController::class, 'myAccountProfile'])->name('profile.profile');
    Route::post('/profile/profile', [FrontController::class, 'updateProfile'])->name('profile.profile.update');
    Route::get('/profile/orders', [FrontController::class, 'myAccountOrders'])->name('profile.orders');
    Route::get('/profile/orders/{order}', [FrontController::class, 'myAccountOrderDetails'])->name('frontend.order.details');
    Route::post('/profile/orders/{order}/cancel', [FrontController::class, 'cancelOrder'])->name('frontend.orders.cancel');
    Route::post('/profile/orders/{order}/return', [FrontController::class, 'returnOrder'])->name('frontend.orders.return');
    Route::post('/profile/orders/{order}/cancel-return', [FrontController::class, 'cancelReturn'])->name('frontend.orders.cancel_return');
    Route::get('/profile/changepassword', [FrontController::class, 'myAccountChangePassword'])->name('profile.changepassword');
    Route::post('/profile/changepassword', [FrontController::class, 'updatePassword'])->name('profile.changepassword.update');
    Route::get('/my-order/invoice/{order}', [ProfileController::class, 'userInvoice'])->name('user.invoice');
});

/*
|--------------------------------------------------------------------------
| STAFF DASHBOARD
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:staff'])->group(function () {
    Route::get('/admin/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');
    Route::get('/admin/reviews', [ReviewController::class, 'index'])->name('admin.review.index');
});

/*
|--------------------------------------------------------------------------
| PRODUCTS & CATEGORIES - Admin + Web Designer (With Permission Checks)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:staff'])->prefix('admin')->group(function () {

    // Category Management
    Route::middleware(['role:permission:categories'])->group(function () {
        Route::get('category', [CategoryController::class, 'index'])->name('category.index');
        Route::get('/category-export', [CategoryController::class, 'export'])->name('category.export');
        Route::get('/category-import-sample', [CategoryController::class, 'importSample'])->name('category.import.sample');
        Route::post('/category-import', [CategoryController::class, 'import'])->name('category.import');
        Route::get('category/create', [CategoryController::class, 'create'])->name('category.create');
        Route::post('category/store', [CategoryController::class, 'store'])->name('category.store');
        Route::get('category/edit/{category}', [CategoryController::class, 'edit'])->name('category.edit');
        Route::put('category/update/{category}', [CategoryController::class, 'update'])->name('category.update');
        Route::put('/category/status/{id}', [CategoryController::class, 'status'])->name('category.status');
        Route::post('/category/reorder', [CategoryController::class, 'reorder'])->name('category.reorder');
        Route::delete('category/delete/{category}', [CategoryController::class, 'destroy'])->name('category.destroy');
    });

    // Subcategory Management
    Route::middleware(['role:permission:subcategories'])->group(function () {
        Route::resource('subcategory', SubCategoryController::class);
        Route::put('subcategory/status/{id}', [SubCategoryController::class, 'status'])->name('subcategory.status');
        Route::get('subcategory-export', [SubCategoryController::class, 'export'])->name('subcategory.export');
        Route::get('subcategory-import-sample', [SubCategoryController::class, 'importSample'])->name('subcategory.import.sample');
        Route::post('subcategory-import', [SubCategoryController::class, 'import'])->name('subcategory.import');
    });

    // Child Category Management
    Route::middleware(['role:permission:childcategories,permission:subcategories'])->group(function () {

        Route::get('childcategory', [ChildCategoryController::class, 'index'])->name('childcategory.index');
        Route::get('childcategory/create', [ChildCategoryController::class, 'create'])->name('childcategory.create');
        Route::post('childcategory/store', [ChildCategoryController::class, 'store'])->name('childcategory.store');
        Route::get('childcategory/{childcategory}/edit', [ChildCategoryController::class, 'edit'])->name('childcategory.edit');
        Route::put('childcategory/{childcategory}', [ChildCategoryController::class, 'update'])->name('childcategory.update');
        Route::delete('childcategory/{childcategory}', [ChildCategoryController::class, 'destroy'])->name('childcategory.destroy');
        Route::put('childcategory/status/{id}', [ChildCategoryController::class, 'status'])->name('childcategory.status');
        Route::get('childcategory-export', [ChildCategoryController::class, 'export'])->name('childcategory.export');
        Route::get('childcategory-import-sample', [ChildCategoryController::class, 'importSample'])->name('childcategory.importSample');
        Route::post('childcategory-import', [ChildCategoryController::class, 'import'])->name('childcategory.import');
        Route::get('get-subcategories/{categoryId}', [ChildCategoryController::class, 'getSubcategories'])->name('childcategory.getSubcategories');
    });

    // Product Management
    Route::middleware(['role:permission:products'])->group(function () {
        Route::resource('product', ProductController::class);
        Route::patch('/product/status/{product}', [ProductController::class, 'toggleStatus'])->name('product.status');
        Route::post('product/{product}/duplicate', [ProductController::class, 'duplicate'])->name('product.duplicate');
        Route::get('product-export', [ProductController::class, 'export'])->name('product.export');
        Route::get('product-import-sample', [ProductController::class, 'importSample'])->name('product.import.sample');
        Route::post('product-import', [ProductController::class, 'import'])->name('product.import');
        Route::get('get-subcategories/{category_id}', [ProductController::class, 'getSubcategories'])->name('get.subcategories');
        Route::get('get-childcategories/{subcategory_id}', [ProductController::class, 'getChildCategories'])->name('get.childcategories');

        // Product Variants AJAX Routes
        Route::post('product/{product}/ajax-add-variants', [ProductController::class, 'ajaxAddVariants'])->name('product.variant.ajax-add');
        Route::match(['post', 'delete'], 'product/variant/{id}/delete', [ProductController::class, 'deleteVariant'])->name('product.variant.delete');
        Route::post('product/variant/{id}/update-ajax', [ProductController::class, 'ajaxUpdateVariant'])->name('product.variant.update-ajax');
    });

    // Reviews Management
    Route::middleware(['role:permission:reviews'])->group(function () {
        Route::resource('review', ReviewController::class);
        Route::post('review/status/{review}', [ReviewController::class, 'status'])->name('review.status');
        Route::get('/reviews/export', [ReviewController::class, 'export'])->name('review.export');
    });
});

/*
|--------------------------------------------------------------------------
| CMS - Admin + Web Designer (With Permission Checks)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:staff'])->prefix('admin')->group(function () {

    // Slider Management
    Route::middleware(['role:permission:sliders'])->group(function () {
        Route::resource('slider', SliderController::class);
        Route::put('/slider/status/{slider}', [SliderController::class, 'toggleStatus'])->name('slider.status');
        Route::post('slider/{slider}/duplicate', [SliderController::class, 'duplicate'])->name('slider.duplicate');
        Route::get('slider-export', [SliderController::class, 'export'])->name('slider.export');
    });

    // Case Study Management
    Route::middleware(['role:permission:blogs'])->group(function () {
        Route::resource('case-study', BlogController::class)->parameters(['case-study' => 'blog']);
        Route::resource('blog', BlogController::class);
        Route::patch('/case-study/status/{blog}', [BlogController::class, 'status'])->name('case-study.status');
        Route::patch('/blog/status/{blog}', [BlogController::class, 'status'])->name('blog.status');
        Route::get('case-study-export', [BlogController::class, 'export'])->name('case-study.export');
        Route::get('blog-export', [BlogController::class, 'export'])->name('blog.export');
    });

    // Feature Management
    Route::middleware(['role:permission:features'])->group(function () {
        Route::get('/features', [FeatureController::class, 'index'])->name('features.index');
        Route::get('/features/edit/{id}', [FeatureController::class, 'edit'])->name('features.edit');
        Route::put('/features/update/{id}', [FeatureController::class, 'update'])->name('features.update');
    });

    // Benefit Management
    Route::middleware(['role:permission:benefits'])->group(function () {
        Route::get('/benefits', [BenefitController::class, 'index'])->name('benefits.index');
        Route::get('/benefits/edit/{id}', [BenefitController::class, 'edit'])->name('benefits.edit');
        Route::put('/benefits/update/{id}', [BenefitController::class, 'update'])->name('benefits.update');
    });

    // Home Banners
    Route::middleware(['role:permission:banners'])->group(function () {
        Route::get('/home-banners', [HomeBannerController::class, 'index'])->name('home-banner.index');
        Route::post('/home-banners/update', [HomeBannerController::class, 'update'])->name('home-banner.update');
    });

    // About Page
    Route::middleware(['role:permission:about-us'])->group(function () {
        Route::get('/about-us', [AboutUsController::class, 'index'])->name('about-us.index');
        Route::post('/about-us', [AboutUsController::class, 'update'])->name('about-us.update');
    });

    // Sales Policy
    Route::middleware(['role:permission:sales-policy'])->group(function () {
        Route::get('/sales-policy', [SalesPolicyController::class, 'index'])->name('sales-policy.index');
        Route::post('/sales-policy', [SalesPolicyController::class, 'update'])->name('sales-policy.update');
    });


    // Privacy Policy & Terms and Conditions
    Route::middleware(['role:permission:cms'])->group(function () {
        Route::get('/privacy-policy', [PrivacyPolicyController::class, 'index'])->name('privacy-policy.index');
        Route::post('/privacy-policy', [PrivacyPolicyController::class, 'update'])->name('privacy-policy.update');
        Route::get('/terms-and-conditions', [TermsAndConditionController::class, 'index'])->name('terms-and-conditions.index');
        Route::post('/terms-and-conditions', [TermsAndConditionController::class, 'update'])->name('terms-and-conditions.update');
        Route::get('/play-fun-section', [PlayFunSectionController::class, 'edit'])->name('admin.play-fun-section.edit');
        Route::post('/play-fun-section', [PlayFunSectionController::class, 'update'])->name('admin.play-fun-section.update');
        Route::get('/learn-fun-section', [LearnFunSectionController::class, 'edit'])->name('admin.learn-fun-section.edit');
        Route::post('/learn-fun-section', [LearnFunSectionController::class, 'update'])->name('admin.learn-fun-section.update');
        Route::get('/how-to-play-section', [HowToPlaySectionController::class, 'edit'])->name('admin.how-to-play-section.edit');
        Route::post('/how-to-play-section', [HowToPlaySectionController::class, 'update'])->name('admin.how-to-play-section.update');
        Route::get('/home-faqs', [HomeFaqController::class, 'index'])->name('admin.home-faqs.index');
        Route::post('/home-faqs/image', [HomeFaqController::class, 'updateImage'])->name('admin.home-faqs.image.update');
        Route::get('/home-faqs/{homeFaq}/edit', [HomeFaqController::class, 'edit'])->name('admin.home-faqs.edit');
        Route::put('/home-faqs/{homeFaq}', [HomeFaqController::class, 'update'])->name('admin.home-faqs.update');

        // Why Choose Us / Why Partner With Kids-e-Dental
        Route::resource('why-choose-us', WhyChooseUsController::class, ['as' => 'admin']);
        Route::patch('/why-choose-us/status/{id}', [WhyChooseUsController::class, 'status'])->name('admin.why-choose-us.status');

        Route::get('/help-categories', [HelpCategoryController::class, 'index'])->name('admin.help-categories.index');
        Route::get('/help-categories/create', [HelpCategoryController::class, 'create'])->name('admin.help-categories.create');
        Route::post('/help-categories', [HelpCategoryController::class, 'store'])->name('admin.help-categories.store');
        Route::get('/help-categories/{helpCategory}/edit', [HelpCategoryController::class, 'edit'])->name('admin.help-categories.edit');
        Route::put('/help-categories/{helpCategory}', [HelpCategoryController::class, 'update'])->name('admin.help-categories.update');
        Route::delete('/help-categories/{helpCategory}', [HelpCategoryController::class, 'destroy'])->name('admin.help-categories.destroy');
        Route::get('/faq-categories', [HelpCategoryController::class, 'index'])->name('admin.faq-categories.index');
        Route::get('/faq-categories/create', [HelpCategoryController::class, 'create'])->name('admin.faq-categories.create');
        Route::post('/faq-categories', [HelpCategoryController::class, 'store'])->name('admin.faq-categories.store');
        Route::get('/faq-categories/{helpCategory}/edit', [HelpCategoryController::class, 'edit'])->name('admin.faq-categories.edit');
        Route::put('/faq-categories/{helpCategory}', [HelpCategoryController::class, 'update'])->name('admin.faq-categories.update');
        Route::delete('/faq-categories/{helpCategory}', [HelpCategoryController::class, 'destroy'])->name('admin.faq-categories.destroy');

        Route::get('/help-faqs', [HelpFaqController::class, 'index'])->name('admin.help-faqs.index');
        Route::get('/help-faqs/create', [HelpFaqController::class, 'create'])->name('admin.help-faqs.create');
        Route::post('/help-faqs', [HelpFaqController::class, 'store'])->name('admin.help-faqs.store');
        Route::get('/help-faqs/{helpFaq}/edit', [HelpFaqController::class, 'edit'])->name('admin.help-faqs.edit');
        Route::put('/help-faqs/{helpFaq}', [HelpFaqController::class, 'update'])->name('admin.help-faqs.update');
        Route::delete('/help-faqs/{helpFaq}', [HelpFaqController::class, 'destroy'])->name('admin.help-faqs.destroy');
        Route::get('/category-faqs', [HelpFaqController::class, 'index'])->name('admin.category-faqs.index');
        Route::get('/category-faqs/create', [HelpFaqController::class, 'create'])->name('admin.category-faqs.create');
        Route::post('/category-faqs', [HelpFaqController::class, 'store'])->name('admin.category-faqs.store');
        Route::get('/category-faqs/{helpFaq}/edit', [HelpFaqController::class, 'edit'])->name('admin.category-faqs.edit');
        Route::put('/category-faqs/{helpFaq}', [HelpFaqController::class, 'update'])->name('admin.category-faqs.update');
        Route::delete('/category-faqs/{helpFaq}', [HelpFaqController::class, 'destroy'])->name('admin.category-faqs.destroy');
        Route::get('/home-testimonials', [HomeTestimonialController::class, 'index'])->name('admin.home-testimonials.index');
        Route::get('/home-testimonials/create', [HomeTestimonialController::class, 'create'])->name('admin.home-testimonials.create');
        Route::post('/home-testimonials', [HomeTestimonialController::class, 'store'])->name('admin.home-testimonials.store');
        Route::get('/home-testimonials/{homeTestimonial}/edit', [HomeTestimonialController::class, 'edit'])->name('admin.home-testimonials.edit');
        Route::put('/home-testimonials/{homeTestimonial}', [HomeTestimonialController::class, 'update'])->name('admin.home-testimonials.update');
        Route::delete('/home-testimonials/{homeTestimonial}', [HomeTestimonialController::class, 'destroy'])->name('admin.home-testimonials.destroy');
        Route::get('/home-highlights', [HomeHighlightController::class, 'index'])->name('admin.home-highlights.index');
        Route::get('/home-highlights/create', [HomeHighlightController::class, 'create'])->name('admin.home-highlights.create');
        Route::post('/home-highlights', [HomeHighlightController::class, 'store'])->name('admin.home-highlights.store');
        Route::get('/home-highlights/{homeHighlight}/edit', [HomeHighlightController::class, 'edit'])->name('admin.home-highlights.edit');
        Route::put('/home-highlights/{homeHighlight}', [HomeHighlightController::class, 'update'])->name('admin.home-highlights.update');
        Route::delete('/home-highlights/{homeHighlight}', [HomeHighlightController::class, 'destroy'])->name('admin.home-highlights.destroy');

        // Subcategory-wise Tutorials
        Route::resource('tutorials', TutorialController::class, ['as' => 'admin']);
    });

    // Contact Settings
    Route::middleware(['role:permission:settings'])->group(function () {
        Route::get('/contact-settings', [ContactSettingController::class, 'edit'])->name('admin.contact-settings.edit');
        Route::post('/contact-settings', [ContactSettingController::class, 'update'])->name('admin.contact-settings.update');
        Route::get('/footer-contact-settings', [FooterContactSettingController::class, 'edit'])->name('admin.footer-contact-settings.edit');
        Route::post('/footer-contact-settings', [FooterContactSettingController::class, 'update'])->name('admin.footer-contact-settings.update');
        Route::get('/header-top-bar-settings', [HeaderTopBarSettingController::class, 'edit'])->name('admin.header-top-bar-settings.edit');
        Route::post('/header-top-bar-settings', [HeaderTopBarSettingController::class, 'update'])->name('admin.header-top-bar-settings.update');

        // Contact Enquiry Management
        Route::get('/contact-enquiries/export', [ContactEnquiryController::class, 'export'])->name('admin.contact-enquiries.export');
        Route::get('/contact-enquiries', [ContactEnquiryController::class, 'index'])->name('admin.contact-enquiries.index');
        Route::get('/contact-enquiries/edit/{contactEnquiry}', [ContactEnquiryController::class, 'edit'])->name('admin.contact-enquiries.edit');
        Route::put('/contact-enquiries/update/{contactEnquiry}', [ContactEnquiryController::class, 'update'])->name('admin.contact-enquiries.update');

        // Distributor Enquiry Management
        Route::get('/distributor-enquiries/export', [DistributorEnquiryController::class, 'export'])->name('admin.distributor-enquiries.export');
        Route::get('/distributor-enquiries', [DistributorEnquiryController::class, 'index'])->name('admin.distributor-enquiries.index');
        Route::get('/distributor-enquiries/show/{distributorEnquiry}', [DistributorEnquiryController::class, 'show'])->name('admin.distributor-enquiries.show');
        Route::get('/distributor-enquiries/edit/{distributorEnquiry}', [DistributorEnquiryController::class, 'edit'])->name('admin.distributor-enquiries.edit');
        Route::put('/distributor-enquiries/update/{distributorEnquiry}', [DistributorEnquiryController::class, 'update'])->name('admin.distributor-enquiries.update');
        Route::post('/distributor-enquiries/toggle-status/{distributorEnquiry}', [DistributorEnquiryController::class, 'toggleStatus'])->name('admin.distributor-enquiries.toggle-status');
        Route::delete('/distributor-enquiries/{distributorEnquiry}', [DistributorEnquiryController::class, 'destroy'])->name('admin.distributor-enquiries.destroy');
    });
});

/*
|--------------------------------------------------------------------------
| ADMIN ONLY ROUTES
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'admin'])->prefix('admin')->group(function () {

    // Address Management
    Route::resource('address', AddressController::class);

    // Coupon Management
    Route::resource('coupon', CouponController::class);
    Route::put('/coupon/status/{coupon}', [CouponController::class, 'toggleStatus'])->name('coupon.status');
    Route::get('coupon-export', [CouponController::class, 'export'])->name('coupon.export');

    // GST Settings
    Route::get('/gst-settings', [GstSettingController::class, 'index'])->name('gst.settings.index');
    Route::get('/gst-settings/create', [GstSettingController::class, 'create'])->name('gst.settings.create');
    Route::post('/gst-settings/store', [GstSettingController::class, 'store'])->name('gst.settings.store');
    Route::get('/gst-settings/{gstSetting}/edit', [GstSettingController::class, 'edit'])->name('gst.settings.edit');
    Route::put('/gst-settings/{gstSetting}', [GstSettingController::class, 'update'])->name('gst.settings.update');
    Route::delete('/gst-settings/{gstSetting}', [GstSettingController::class, 'destroy'])->name('gst.settings.destroy');
    Route::patch('/gst-settings/{id}/status', [GstSettingController::class, 'status'])->name('gst.settings.status');

    // Shipping Settings
    Route::get('/shipping-settings', [ShippingSettingController::class, 'index'])->name('shipping.settings');
    Route::post('/shipping-settings', [ShippingSettingController::class, 'update'])->name('shipping.settings.update');

    // Profile Management
    Route::get('/profile', [ProfileController::class, 'index'])->name('admin.profile');
    Route::post('/profile/update', [ProfileController::class, 'update'])->name('admin.profile.update');
    Route::post('/profile/password', [ProfileController::class, 'changePassword'])->name('admin.profile.password');

    // Contact Details API (for frontend)
    Route::get('/contact-details', [ContactController::class, 'contactDetails']);

    // Role Management Routes
    Route::resource('roles', RoleController::class);
    Route::get('roles/{role}/assign-users', [RoleController::class, 'assignUsers'])->name('roles.assign-users');
    Route::post('roles/{role}/update-users', [RoleController::class, 'updateUsers'])->name('roles.update-users');
    Route::put('roles/{role}/toggle-status', [RoleController::class, 'toggleStatus'])->name('roles.toggle-status');
    Route::post('roles/bulk-assign', [RoleController::class, 'bulkAssign'])->name('roles.bulk-assign');
    Route::get('roles/{role}/permissions', [RoleController::class, 'getPermissions'])->name('roles.permissions');

    // User Management
    Route::resource('user', UserController::class);
    Route::get('customers', [UserController::class, 'customers'])->name('customer.index');

    // User Cart Details (Customer Activities: Cart, Wishlist, Buy Now)
    Route::get('user-cart-details', [CustomerActivityController::class, 'index'])->name('customer.activities.index');
    Route::get('user-cart-details/export', [CustomerActivityController::class, 'export'])->name('customer.activities.export');
    Route::get('user-cart-details/{id}', [CustomerActivityController::class, 'show'])->name('customer.activities.show');
    Route::get('customer-activities', [CustomerActivityController::class, 'index']);
    Route::get('customer-activities/export', [CustomerActivityController::class, 'export']);
    Route::get('customer-activities/{id}', [CustomerActivityController::class, 'show']);
    Route::put('user/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('user.toggle-status');
    Route::post('users/bulk-assign-role', [UserController::class, 'bulkAssignRole'])->name('users.bulk-assign-role');
    Route::post('users/bulk-delete', [UserController::class, 'bulkDelete'])->name('users.bulk-delete');
    Route::get('users/export', [UserController::class, 'export'])->name('users.export');
    Route::get('users/get-by-role', [UserController::class, 'getUsersByRole'])->name('users.get-by-role');
    Route::get('user/{user}/details', [UserController::class, 'getUserDetails'])->name('user.details');
});

Route::prefix('admin')->middleware(['auth'])->group(function () {

    // Gallery CRUD
    Route::get('/galleries', [GalleryController::class, 'index'])->name('gallery.index');
    Route::get('/galleries/create', [GalleryController::class, 'create'])->name('gallery.create');
    Route::post('/galleries/store', [GalleryController::class, 'store'])->name('gallery.store');

    Route::get('/galleries/edit/{gallery}', [GalleryController::class, 'edit'])->name('gallery.edit');
    Route::put('/galleries/update/{gallery}', [GalleryController::class, 'update'])->name('gallery.update');

    Route::delete('/galleries/delete/{gallery}', [GalleryController::class, 'destroy'])->name('gallery.destroy');

    // ✅ Gallery Images (FIXED)
    Route::get('/galleries/{gallery}/images', [GalleryController::class, 'images'])->name('gallery.images.index');
    Route::get('/galleries/{gallery}/images/create', [GalleryController::class, 'createImage'])->name('gallery.images.create');
    Route::post('/galleries/{gallery}/images/store', [GalleryController::class, 'storeImage'])->name('gallery.images.store');
    Route::delete('/galleries/{gallery}/images/{image}', [GalleryController::class, 'destroyImage'])->name('gallery.images.destroy');
});

/*
|--------------------------------------------------------------------------
| ORDERS - Admin + Order Manager + Manager (With Permission Checks)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:staff'])->prefix('admin')->group(function () {

    // Order Management
    Route::middleware(['role:permission:orders'])->group(function () {
        Route::resource('order', OrderController::class);
        Route::get('/transactions', [OrderController::class, 'transactions'])->name('transactions.index');
        Route::put('order/status/{order}', [OrderController::class, 'statusUpdate'])->name('order.status');
        Route::post('/admin/orders/{order}/remaining-payment', [OrderController::class, 'receiveRemainingPayment'])->name('order.remaining.payment');
        Route::put('order/payment-status/{order}', [OrderController::class, 'paymentStatusUpdate'])->name('order.payment.status');
        Route::get('order/invoice/{order}', [OrderController::class, 'invoice'])->name('order.invoice');
        Route::get('order/print/{order}', [OrderController::class, 'printInvoice'])->name('order.print');
        Route::get('order/pdf/{order}', [OrderController::class, 'downloadPdf'])->name('order.pdf');
        Route::post('order/{order}/payment', [OrderPaymentController::class, 'store'])->name('order.payment.store');
        Route::delete('order/payment/{payment}', [OrderController::class, 'destroy'])->name('order.payment.delete');
        Route::post('order/{order}/approve-cancel-request', [OrderController::class, 'approveCancelRequest'])->name('order.cancel_request.approve');
        Route::post('order/{order}/reject-cancel-request', [OrderController::class, 'rejectCancelRequest'])->name('order.cancel_request.reject');
        Route::post('order/{order}/approve-return-request', [OrderController::class, 'approveReturnRequest'])->name('order.return_request.approve');
        Route::post('order/{order}/reject-return-request', [OrderController::class, 'rejectReturnRequest'])->name('order.return_request.reject');
        Route::get('order-export', [OrderController::class, 'export'])->name('order.export');
        Route::get('order-import-sample', [OrderController::class, 'importSample'])->name('order.import.sample');
        Route::post('order-import', [OrderController::class, 'import'])->name('order.import');

        // Refund Management
        Route::post('/orders/{order}/refund', [RefundController::class, 'store'])->name('refund.store');
        Route::get('/refund/{refund}/approve', [RefundController::class, 'approve'])->name('refund.approve');
        Route::get('/refund/{refund}/reject', [RefundController::class, 'reject'])->name('refund.reject');
        Route::get('/refund/{refund}/complete', [RefundController::class, 'complete'])->name('refund.complete');
    });

    // Reports
    Route::middleware(['role:permission:reports'])->group(function () {
        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/sales', [ReportController::class, 'sales'])->name('reports.sales');
        Route::get('/reports/orders', [ReportController::class, 'orders'])->name('reports.orders');
        Route::get('/reports/customers', [ReportController::class, 'customers'])->name('reports.customers');
        Route::get('/reports/export/sales', [ReportController::class, 'exportSales'])->name('reports.export.sales');
        Route::get('/reports/export/orders', [ReportController::class, 'exportOrders'])->name('reports.export.orders');
        Route::get('/reports/export/customers', [ReportController::class, 'exportCustomers'])->name('reports.export.customers');
    });
});

/*
|--------------------------------------------------------------------------
| AUTHENTICATION ROUTES
|--------------------------------------------------------------------------
*/

require __DIR__ . '/auth.php';
