<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\ContactSetting;
use App\Services\CartService;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();
        try {
            if (Schema::hasTable('users')) {
                DB::table('users')
                    ->where('name', 'like', '%Cheriks%')
                    ->update(['name' => DB::raw("REPLACE(name, 'Cheriks', 'Kids-e-Dental')")]);
            }
        } catch (\Throwable $e) {
            // Silently ignore if DB is offline
        }

        if ($appUrl = config('app.url')) {
            URL::forceRootUrl(rtrim($appUrl, '/'));
        }

        if (str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        View::composer('*', function ($view) {
            try {
                $this->shareGlobalViewData($view);
            } catch (\Throwable $e) {
                report($e);

                $view->with([
                    'navCategories' => collect(),
                    'categories' => collect(),
                    'contactSetting' => null,
                    'footerContactSetting' => null,
                    'headerTopBarSetting' => null,
                    'cartCount' => 0,
                    'cartProductIds' => [],
                    'wishlistProductIds' => [],
                    'wishlistCount' => 0,
                ]);
            }
        });
    }

    private function shareGlobalViewData($view): void
    {
        $data = $view->getData();

        if (!array_key_exists('navCategories', $data)) {
            $navCategories = Category::where('status', 1)
                ->with(['products' => function ($q) {
                    $q->where('status', 1)->orderBy('name');
                }])
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get();

            $view->with('navCategories', $navCategories);

            if (!array_key_exists('categories', $data)) {
                $view->with('categories', $navCategories);
            }
        } elseif (!array_key_exists('categories', $data)) {
            $view->with('categories', $data['navCategories']);
        }

        if (!array_key_exists('contactSetting', $data)) {
            $contactSetting = null;

            if (class_exists(ContactSetting::class)) {
                try {
                    $contactSetting = ContactSetting::first();
                } catch (\Throwable $e) {
                    $contactSetting = null;
                }
            }

            $view->with('contactSetting', $contactSetting);
        }

        if (!array_key_exists('footerContactSetting', $data)) {
            $footerContactSetting = null;

            if (class_exists(\App\Models\FooterContactSetting::class)) {
                try {
                    $footerContactSetting = \App\Models\FooterContactSetting::first();
                } catch (\Throwable $e) {
                    $footerContactSetting = null;
                }
            }

            $view->with('footerContactSetting', $footerContactSetting);
        }

        if (!array_key_exists('headerTopBarSetting', $data)) {
            $headerTopBarSetting = null;

            if (class_exists(\App\Models\HeaderTopBarSetting::class)) {
                try {
                    $headerTopBarSetting = \App\Models\HeaderTopBarSetting::first();
                } catch (\Throwable $e) {
                    $headerTopBarSetting = null;
                }
            }

            $view->with('headerTopBarSetting', $headerTopBarSetting);
        }

        $cartService = app(CartService::class);

        if (!array_key_exists('cartCount', $data)) {
            $view->with('cartCount', $cartService->getCartCount());
        }

        if (!array_key_exists('cartProductIds', $data)) {
            $view->with('cartProductIds', $cartService->getCartProductIds());
        }

        if (!array_key_exists('wishlistProductIds', $data)) {
            $wishlistProductIds = [];
            if (\Illuminate\Support\Facades\Auth::check()) {
                $wishlistProductIds = \App\Models\Wishlist::where('user_id', \Illuminate\Support\Facades\Auth::id())
                    ->pluck('product_id')->toArray();
            } else {
                $wishlistProductIds = session()->get('guest_wishlist', []);
            }
            $view->with('wishlistProductIds', $wishlistProductIds);
        } else {
            $wishlistProductIds = $data['wishlistProductIds'];
        }

        if (!array_key_exists('wishlistCount', $data)) {
            $view->with('wishlistCount', count($wishlistProductIds ?? []));
        }
    }
}
