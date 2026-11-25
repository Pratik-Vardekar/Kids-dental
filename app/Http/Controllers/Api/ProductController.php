<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Slider;
use App\Models\Product;
use App\Models\SubCategory;
use App\Models\ChildCategory;
use App\Models\Blog;
use App\Models\CaseStudy;
use App\Models\WhyChooseUs;
use App\Models\BlogCategory;
use App\Models\Coupon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Cart;
use App\Models\Wishlist;
use App\Models\Review;
use App\Models\ReviewReaction;
use App\Models\ProductFaq;
use App\Models\ProductVariant;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderPayment;
use App\Models\OrderNote;
use App\Models\OrderRefund;
use App\Models\OrderAddress;
use App\Models\OrderShippingAddress;
use App\Models\OrderBillingAddress;
use App\Models\Address;
use App\Models\BuyNow;
use App\Models\ShippingSetting;
use App\Models\Newsletter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\HomeBanner;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    /**
     * Check product stock availability (handles both simple and variant products)
     */
    private function checkProductStock($productId, $quantity = 1, $variantId = null)
    {
        $product = Product::find($productId);

        if (!$product) {
            return [
                'available' => false,
                'message' => 'Product not found',
                'available_stock' => 0,
                'product_type' => null
            ];
        }

        // If variant is specified, check variant stock
        if ($variantId) {
            $variant = ProductVariant::find($variantId);

            if (!$variant) {
                return [
                    'available' => false,
                    'message' => 'Variant not found',
                    'available_stock' => 0,
                    'product_type' => 'variable'
                ];
            }

            if ($variant->quantity <= 0) {
                return [
                    'available' => false,
                    'message' => 'This variant is currently out of stock',
                    'available_stock' => 0,
                    'product_type' => 'variable'
                ];
            }

            if ($quantity > $variant->quantity) {
                return [
                    'available' => false,
                    'message' => 'Not enough stock for this variant. Available: ' . $variant->quantity,
                    'available_stock' => $variant->quantity,
                    'product_type' => 'variable'
                ];
            }

            return [
                'available' => true,
                'message' => 'In stock',
                'available_stock' => $variant->quantity,
                'product_type' => 'variable'
            ];
        }

        // Simple product stock check
        if ($product->quantity <= 0) {
            return [
                'available' => false,
                'message' => 'This product is currently out of stock',
                'available_stock' => 0,
                'product_type' => 'simple'
            ];
        }

        if ($quantity > $product->quantity) {
            return [
                'available' => false,
                'message' => 'Not enough stock available. Available: ' . $product->quantity,
                'available_stock' => $product->quantity,
                'product_type' => 'simple'
            ];
        }

        return [
            'available' => true,
            'message' => 'In stock',
            'available_stock' => $product->quantity,
            'product_type' => 'simple'
        ];
    }

    /**
     * Get available stock for a product (handles both simple and variant)
     */
    private function getProductAvailableStock($productId, $variantId = null)
    {
        if ($variantId) {
            $variant = ProductVariant::find($variantId);
            return $variant ? (int) $variant->quantity : 0;
        }

        $product = Product::find($productId);
        return $product ? (int) $product->quantity : 0;
    }
    public function home(Request $request)
    {
        try {
            // Get authenticated user from token first
            $user = auth('sanctum')->user();

            // If no token auth, check for user_id parameter (for debugging/mobile apps)
            if (!$user && $request->has('user_id')) {
                $user = \App\Models\User::find($request->user_id);
            }

            // Fallback to default guard
            if (!$user) {
                $user = auth()->user();
            }

            $wishlistProductIds = [];
            $cartProductIds = [];

            if ($user) {
                $wishlistProductIds = Wishlist::where('user_id', $user->id)
                    ->pluck('product_id')
                    ->toArray();
                $cartProductIds = Cart::where('user_id', $user->id)
                    ->pluck('product_id')
                    ->toArray();

                // Debug logging - check your laravel.log file
                \Log::info('Home API - User ID: ' . $user->id);
                \Log::info('Home API - Wishlist IDs: ' . json_encode($wishlistProductIds));
                \Log::info('Home API - Is product 252 in wishlist? ' . (in_array(252, $wishlistProductIds) ? 'YES' : 'NO'));
            } else {
                // Guest user logic
                $sessionId = $request->header('session-id');
                if ($sessionId) {
                    $cartProductIds = Cart::where('session_id', $sessionId)
                        ->pluck('product_id')
                        ->toArray();
                }
            }

            $sliders = Slider::where('status', 1)
                ->orderBy('sort_order', 'asc')
                ->latest()
                ->get()
                ->map(function ($slider) {
                    $slider->image = $slider->image ? url('backend/assets/images/slider/' . $slider->image) : null;
                    $slider->mobile_image = $slider->mobile_image ? url('backend/assets/images/slider/' . $slider->mobile_image) : null;
                    return $slider;
                });

            $whyChooseUs = [];
            if (Schema::hasTable('why_choose_us')) {
                $whyChooseUs = WhyChooseUs::where('status', 1)
                    ->orderBy('sort_order', 'asc')
                    ->orderBy('id', 'asc')
                    ->get()

                    ->map(function ($item) {
                        $item->image = $item->image ? url('backend/assets/images/why-choose-us/' . $item->image) : null;
                        return $item;
                    });
            }

            $caseStudies = CaseStudy::where('status', 1)
                ->latest()
                ->get()
                ->map(function ($item) {
                    $item->image = url('backend/assets/images/blog/' . $item->image);
                    return $item;
                });

            $formatProduct = function ($product) use ($wishlistProductIds, $cartProductIds) {
                $approvedReviews = $product->reviews ? $product->reviews->where('status', 1) : collect();

                // Active variants from relation
                $activeVariants = $product->variants
                    ? $product->variants->where('status', 1)->values()
                    : collect();

                $isVariant = false;
                if ($activeVariants->isNotEmpty()) {
                    if (isset($product->is_variant)) {
                        $isVariant = (bool) $product->is_variant;
                    } elseif (isset($product->is_varient)) {
                        $isVariant = (bool) $product->is_varient;
                    } else {
                        $isVariant = true;
                    }
                }

                $displayPrice = (float) ($product->price ?? 0);
                $displaySellingPrice = (float) ($product->selling_price ?? 0);

                $formattedVariants = [];
                $variantTypes = [];
                $selectedVariant = null;

                if ($isVariant && $activeVariants->isNotEmpty()) {
                    $firstVariant = $activeVariants->first();

                    $formattedVariants = $activeVariants->map(function ($v) {
                        return [
                            'id' => $v->id,
                            'product_id' => $v->product_id,
                            'sku' => $v->sku,
                            'arch' => $v->arch,
                            'size' => (string) $v->size,
                            'variant_title' => $v->variant_title ?? trim(($v->arch ?? '') . ' ' . ($v->size ? 'Size ' . $v->size : '')),
                            'price' => (float) $v->price,
                            'selling_price' => (float) ($v->selling_price ?? $v->price),
                            'quantity' => (int) $v->quantity,
                            'in_stock' => (int) $v->quantity > 0,
                            'status' => (int) ($v->status ?? 1),
                        ];
                    })->values()->toArray();

                    $variantTypes = $activeVariants
                        ->filter(fn($v) => !empty($v->arch))
                        ->groupBy('arch')
                        ->map(function ($variants, $arch) {
                            return [
                                'arch' => $arch,
                                'available_sizes' => $variants
                                    ->pluck('size')
                                    ->filter(fn($size) => $size !== null && $size !== '')
                                    ->unique()
                                    ->sort(fn($a, $b) => (int) $a <=> (int) $b)
                                    ->values()
                                    ->map(fn($size) => (string) $size)
                                    ->toArray(),
                            ];
                        })
                        ->values()
                        ->toArray();

                    $vPrice = (float) ($firstVariant->price ?? 0);
                    $vSelling = (float) ($firstVariant->selling_price ?? $firstVariant->price);

                    if ($vSelling > 0) {
                        $displaySellingPrice = $vSelling;
                    }
                    if ($vPrice > 0) {
                        $displayPrice = $vPrice;
                    } elseif ($displaySellingPrice > 0 && $displayPrice <= 0) {
                        $displayPrice = $displaySellingPrice;
                    }

                    $selectedVariant = [
                        'id' => $firstVariant->id,
                        'product_id' => $firstVariant->product_id,
                        'sku' => $firstVariant->sku,
                        'arch' => $firstVariant->arch,
                        'size' => (string) $firstVariant->size,
                        'variant_title' => $firstVariant->variant_title ?? trim(($firstVariant->arch ?? '') . ' ' . ($firstVariant->size ? 'Size ' . $firstVariant->size : '')),
                        'price' => (float) $vPrice,
                        'selling_price' => (float) $vSelling,
                        'quantity' => (int) $firstVariant->quantity,
                        'in_stock' => (int) $firstVariant->quantity > 0,
                        'status' => (int) ($firstVariant->status ?? 1),
                    ];
                }

                if ($displaySellingPrice <= 0 && $displayPrice > 0) {
                    $displaySellingPrice = $displayPrice;
                }
                if ($displayPrice <= 0 && $displaySellingPrice > 0) {
                    $displayPrice = $displaySellingPrice;
                }

                $discountValue = 0;
                if (
                    $displayPrice > 0 &&
                    $displaySellingPrice > 0 &&
                    $displaySellingPrice < $displayPrice
                ) {
                    $discountAmount = $displayPrice - $displaySellingPrice;
                    $discountValue = (int) round(($discountAmount / $displayPrice) * 100);
                } elseif (!empty($product->discount)) {
                    $discountValue = (int) $product->discount;
                }

                $isWishlist = in_array($product->id, $wishlistProductIds ?? []);
                $isCart = in_array($product->id, $cartProductIds ?? []);

                return [
                    'id' => $product->id,
                    'category_id' => $product->category_id,
                    'subcategory_id' => $product->subcategory_id,
                    'name' => $product->name,
                    'slug' => $product->slug,
                    'thumbnail' => $product->thumbnail
                        ? url('backend/assets/images/product/' . $product->thumbnail)
                        : null,
                    'price' => (float) $displayPrice,
                    'selling_price' => (float) $displaySellingPrice,
                    'is_variant' => (bool) $isVariant,
                    'is_variable' => (bool) $isVariant,
                    'variant' => $selectedVariant,
                    'selected_variant' => $selectedVariant,
                    'variants' => $formattedVariants,
                    'variant_types' => $variantTypes,
                    'details' => $product->details,
                    'description' => $product->description,
                    'specification' => $product->specification,
                    'quantity' => $product->quantity,
                    'gallery' => collect($product->gallery)->map(
                        fn($img) =>
                        url('backend/assets/images/product/' . $img)
                    ),
                    'gallery_order' => collect($product->gallery_order)->map(
                        fn($img) =>
                        url('backend/assets/images/product/' . $img)
                    ),
                    'status' => $product->status,
                    'is_trending' => (bool) $product->is_trending,
                    'is_featured' => (bool) $product->is_featured,
                    'is_best_selling' => (bool) $product->is_best_selling,
                    'is_top_rated' => (bool) $product->is_top_rated,
                    'is_new_arrival' => (bool) $product->is_new_arrival,
                    'badge' => $product->badge,
                    'discount' => $discountValue,
                    'subcategory' => $product->subcategory,
                    'average_rating' => round($approvedReviews->avg('rating') ?? 0, 1),
                    'total_reviews' => $approvedReviews->count(),
                    'is_wishlist' => (bool) $isWishlist,
                    'is_cart' => (bool) $isCart,
                ];
            };

            $products = Product::where('status', 1)
                ->where('is_featured', 1)
                ->with('subcategory')
                ->with(['reviews' => function ($q) {
                    $q->where('status', 1)->with('user');
                }])
                ->with(['variants' => function ($q) {
                    $q->where('status', 1);
                }])
                ->latest()
                ->get()
                ->map($formatProduct);

            return response()->json([
                'status' => true,
                'message' => 'Home data fetched successfully',
                'data' => [
                    'sliders' => $sliders,
                    'featured_products' => $products,
                    'why_choose_us' => $whyChooseUs,
                    'case_study' => $caseStudies
                ],
            ]);
        } catch (\Throwable $e) {
            \Log::error('Home API Error: ' . $e->getMessage());

            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function productListings(Request $request)
    {
        try {
            // Validate request
            $request->validate([
                'min_price' => 'nullable|numeric|min:0',
                'max_price' => 'nullable|numeric|min:0|gte:min_price',
                'sort' => 'nullable|in:recently_added,relevance,price_low_high,price_high_low,brand_asc,brand_desc',
            ]);

            // Optional Sanctum authentication
            $user = null;

            if ($request->bearerToken()) {
                try {
                    $user = auth('sanctum')->user();
                } catch (\Throwable $authException) {
                    \Log::warning(
                        'Optional Sanctum authentication failed: ' .
                            $authException->getMessage()
                    );
                }
            }

            $wishlistProductIds = [];
            $cartProductIds = [];

            // Get wishlist and cart products
            if ($user) {
                $wishlistProductIds = Wishlist::where('user_id', $user->id)
                    ->pluck('product_id')
                    ->toArray();

                $cartProductIds = Cart::where('user_id', $user->id)
                    ->pluck('product_id')
                    ->toArray();
            } else {
                $sessionId = $request->header('session-id');

                if ($sessionId) {
                    $cartProductIds = Cart::where('session_id', $sessionId)
                        ->pluck('product_id')
                        ->toArray();
                }
            }

            // Base product query
            $query = Product::where('status', 1)
                ->with([
                    'category',
                    'subcategory',
                    'childCategory',
                    'variants' => function ($q) {
                        $q->where('status', 1);
                    }
                ]);

            // Main category filter
            if ($request->filled('category_slug')) {
                $categorySlug = trim($request->category_slug);

                $query->whereHas('category', function ($q) use ($categorySlug) {
                    $q->where('slug', $categorySlug);
                });
            }

            // Subcategory filter
            if ($request->filled('subcategory_slug')) {
                $subcategorySlug = trim($request->subcategory_slug);

                $query->whereHas('subcategory', function ($q) use ($subcategorySlug) {
                    $q->where('slug', $subcategorySlug);
                });
            }

            // Child category filter
            if ($request->filled('child_category_slug')) {
                $childCategorySlug = trim($request->child_category_slug);

                $query->whereHas('childCategory', function ($q) use ($childCategorySlug) {
                    $q->where('slug', $childCategorySlug);
                });
            }

            // Effective product price
            $effectivePrice = 'COALESCE(NULLIF(selling_price, 0), price)';

            // Calculate price range before applying price filters
            $priceRange = (clone $query)
                ->reorder()
                ->selectRaw("
                MIN($effectivePrice) AS min_price,
                MAX($effectivePrice) AS max_price
            ")
                ->first();

            // Fixed slider boundaries
            $actualMinPrice = 0;
            $actualMaxPrice = (float) ($priceRange->max_price ?? 0);

            // Selected price filters
            $minPrice = $request->input('min_price');
            $maxPrice = $request->input('max_price');

            $selectedMinPrice = (
                $minPrice !== null && $minPrice !== ''
            ) ? (float) $minPrice : $actualMinPrice;

            $selectedMaxPrice = (
                $maxPrice !== null && $maxPrice !== ''
            ) ? (float) $maxPrice : $actualMaxPrice;

            // Apply minimum price filter
            if ($minPrice !== null && $minPrice !== '') {
                $query->whereRaw(
                    "$effectivePrice >= ?",
                    [(float) $minPrice]
                );
            }

            // Apply maximum price filter
            if ($maxPrice !== null && $maxPrice !== '') {
                $query->whereRaw(
                    "$effectivePrice <= ?",
                    [(float) $maxPrice]
                );
            }

            // Sorting
            $sort = $request->input('sort', 'recently_added');

            switch ($sort) {

                // Recently Added
                case 'recently_added':
                    $query->orderBy('created_at', 'DESC');
                    break;

                // Relevance
                case 'relevance':
                    $query->orderByDesc('is_featured')
                        ->orderByDesc('is_best_selling')
                        ->orderByDesc('is_trending')
                        ->orderByDesc('created_at');
                    break;

                // Price Low to High
                case 'price_low_high':
                    $query->orderByRaw("$effectivePrice ASC");
                    break;

                // Price High to Low
                case 'price_high_low':
                    $query->orderByRaw("$effectivePrice DESC");
                    break;

                // Brands A to Z
                case 'brand_asc':
                    $query->orderByRaw("
                    (
                        SELECT name
                        FROM brands
                        WHERE brands.id = products.brand_id
                        LIMIT 1
                    ) ASC
                ");
                    break;

                // Brands Z to A
                case 'brand_desc':
                    $query->orderByRaw("
                    (
                        SELECT name
                        FROM brands
                        WHERE brands.id = products.brand_id
                        LIMIT 1
                    ) DESC
                ");
                    break;

                default:
                    $query->orderBy('created_at', 'DESC');
                    break;
            }

            // Fetch products
            $products = $query->get()->map(function ($product) use (
                $wishlistProductIds,
                $cartProductIds,
                $user
            ) {

                // Thumbnail URL
                $product->thumbnail = $product->thumbnail
                    ? url('backend/assets/images/product/' . $product->thumbnail)
                    : null;

                // Gallery images
                $gallery = is_string($product->gallery)
                    ? json_decode($product->gallery, true)
                    : $product->gallery;

                $product->gallery = collect($gallery ?? [])
                    ->map(function ($img) {
                        return url('backend/assets/images/product/' . $img);
                    })
                    ->values();

                // Gallery order
                $galleryOrder = is_string($product->gallery_order)
                    ? json_decode($product->gallery_order, true)
                    : $product->gallery_order;

                $product->gallery_order = collect($galleryOrder ?? [])
                    ->map(function ($img) {
                        return url('backend/assets/images/product/' . $img);
                    })
                    ->values();

                // Normalize prices
                $price = (float) ($product->price ?? 0);
                $sellingPrice = (float) ($product->selling_price ?? 0);

                if ($sellingPrice <= 0 && $price > 0) {
                    $sellingPrice = $price;
                }

                if ($price <= 0 && $sellingPrice > 0) {
                    $price = $sellingPrice;
                }

                $product->price = $price;
                $product->selling_price = $sellingPrice;

                // Discount calculation
                $discount = 0;

                if ($price > 0 && $sellingPrice < $price) {
                    $discount = (int) round(
                        (($price - $sellingPrice) / $price) * 100
                    );
                }

                $product->discount = $discount;

                // Wishlist status
                $product->is_wishlist = $user
                    ? in_array($product->id, $wishlistProductIds)
                    : false;

                // Cart status
                $product->is_cart = in_array(
                    $product->id,
                    $cartProductIds
                );

                return $product;
            });

            // Success response
            return response()->json([
                'status' => true,
                'message' => 'Product listings fetched successfully',

                // Fixed slider boundaries
                'price_range' => [
                    'min_price' => $actualMinPrice,
                    'max_price' => $actualMaxPrice,
                ],

                // Selected slider values
                'selected_price' => [
                    'min_price' => $selectedMinPrice,
                    'max_price' => $selectedMaxPrice,
                ],

                // Applied sorting
                'sort' => $sort,

                'data' => $products

            ], 200);
        } catch (\Throwable $e) {

            \Log::error('Product Listings API Error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch product listings',
            ], 500);
        }
    }

    public function categories()
    {
        $categories = Category::where('status', 1)
            ->with([
                'subcategories' => function ($query) {
                    $query->where('status', 1)
                        ->orderBy('id')
                        ->with([
                            'childCategories' => function ($childQuery) {
                                $childQuery->where('status', 1)
                                    ->orderBy('id');
                            }
                        ]);
                }
            ])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(function ($category) {
                $category->image = url('backend/assets/images/category/' . $category->image);
                return $category;
            });

        return response()->json([
            'status' => true,
            'message' => 'Categories fetched successfully',
            'data' => $categories
        ]);
    }

    public function subcategories()
    {
        $subcategories = SubCategory::with([
            'category',
            'childCategories' => function ($query) {
                $query->where('status', 1)
                    ->orderBy('id');
            }
        ])
            ->where('status', 1)
            ->orderBy('id')
            ->get();

        return response()->json([
            'status' => true,
            'message' => 'Subcategories with child categories fetched successfully',
            'data' => $subcategories
        ]);
    }

    public function sliders()
    {
        $sliders = Slider::where('status', 1)
            ->orderBy('sort_order', 'asc')
            ->latest()
            ->get()
            ->map(function ($slider) {
                $slider->image = $slider->image ? url('backend/assets/images/slider/' . $slider->image) : null;
                $slider->mobile_image = $slider->mobile_image ? url('backend/assets/images/slider/' . $slider->mobile_image) : null;
                return $slider;
            });

        return response()->json([
            'status' => true,
            'message' => 'Sliders fetched successfully',
            'data' => $sliders
        ]);
    }

    public function searchProducts(Request $request)
    {
        try {
            $request->validate([
                'query' => 'required|string|min:1|max:255',
            ]);

            $search = trim($request->input('query'));
            $searchTerm = '%' . $search . '%';

            $results = collect();

            /*
        |--------------------------------------------------------------------------
        | 1. Matching Categories
        |--------------------------------------------------------------------------
        */
            $categories = Category::where('status', 1)
                ->where('name', 'LIKE', $searchTerm)
                ->get();

            foreach ($categories as $category) {
                $results->push([
                    'name' => $category->name,
                    'type' => 'category',
                    'slug' => $category->slug,
                ]);
            }

            /*
        |--------------------------------------------------------------------------
        | 2. Matching Subcategories
        |--------------------------------------------------------------------------
        */
            $subcategories = Subcategory::where('status', 1)
                ->where('name', 'LIKE', $searchTerm)
                ->get();

            foreach ($subcategories as $subcategory) {
                $results->push([
                    'name' => $subcategory->name,
                    'type' => 'subcategory',
                    'slug' => $subcategory->slug,
                ]);
            }

            /*
        |--------------------------------------------------------------------------
        | 3. Matching Child Categories
        |--------------------------------------------------------------------------
        */
            $childCategories = ChildCategory::where('status', 1)
                ->where('name', 'LIKE', $searchTerm)
                ->get();

            foreach ($childCategories as $childCategory) {
                $results->push([
                    'name' => $childCategory->name,
                    'type' => 'child_category',
                    'slug' => $childCategory->slug,
                ]);
            }

            /*
        |--------------------------------------------------------------------------
        | 4. Matching Products
        | Search by product name OR matched category hierarchy
        |--------------------------------------------------------------------------
        */
            $products = Product::with([
                'category',
                'subcategory',
                'childCategory'
            ])
                ->where('status', 1)
                ->where(function ($q) use (
                    $searchTerm,
                    $categories,
                    $subcategories,
                    $childCategories
                ) {
                    // Product name matching
                    $q->where('name', 'LIKE', $searchTerm);

                    // Products belonging to matched categories
                    if ($categories->isNotEmpty()) {
                        $q->orWhereIn(
                            'category_id',
                            $categories->pluck('id')
                        );
                    }

                    // Products belonging to matched subcategories
                    if ($subcategories->isNotEmpty()) {
                        $q->orWhereIn(
                            'subcategory_id',
                            $subcategories->pluck('id')
                        );
                    }

                    // Products belonging to matched child categories
                    if ($childCategories->isNotEmpty()) {
                        $q->orWhereIn(
                            'child_category_id',
                            $childCategories->pluck('id')
                        );
                    }
                })
                ->latest('created_at')
                ->limit(5)
                ->get();

            foreach ($products as $product) {
                $results->push([
                    'name' => $product->name,
                    'type' => 'product',
                    'slug' => $product->slug,
                ]);
            }

            /*
        |--------------------------------------------------------------------------
        | 5. Response
        |--------------------------------------------------------------------------
        */
            return response()->json([
                'status' => true,
                'message' => $results->isEmpty()
                    ? 'No search results found'
                    : 'Search results fetched successfully',
                'data' => $results->values()
            ], 200);
        } catch (\Throwable $e) {

            \Log::error('Search Products API Error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch search results'
            ], 500);
        }
    }

    public function index()
    {
        $user = auth('sanctum')->user();

        $wishlistProductIds = [];

        if ($user) {
            $wishlistProductIds = Wishlist::where('user_id', $user->id)
                ->pluck('product_id')
                ->toArray();
        }

        $products = Product::with([
            'category',
            'subcategory',
            'reviews',
            'variants' => fn($q) => $q->where('status', 1)
        ])
            ->where('status', 1)
            ->get()
            ->map(function ($product) use ($wishlistProductIds, $user) {
                $product->thumbnail = $product->thumbnail
                    ? url('backend/assets/images/product/' . $product->thumbnail)
                    : null;

                $product->gallery = collect($product->gallery ?? [])->map(function ($image) {
                    return url('backend/assets/images/product/' . $image);
                });

                $product->gallery_order = collect($product->gallery_order ?? [])->map(function ($image) {
                    return url('backend/assets/images/product/' . $image);
                });

                $discountValue = null;
                if (
                    $product->price !== null &&
                    $product->selling_price !== null &&
                    $product->price > 0 &&
                    $product->selling_price < $product->price
                ) {
                    $discountValue = round((($product->price - $product->selling_price) / $product->price) * 100);
                }

                $activeVariants = $product->variants ? $product->variants->where('status', 1)->values() : collect();

                return [
                    'id' => $product->id,
                    'category_id' => $product->category_id,
                    'subcategory_id' => $product->subcategory_id,
                    'name' => $product->name,
                    'slug' => $product->slug,
                    'sku' => $product->sku,
                    'product_type' => $product->product_type ?? ($product->isVariable() ? 'variable' : 'simple'),
                    'is_variable' => $product->isVariable(),
                    'available_arches' => $activeVariants->pluck('arch')->filter()->unique()->values(),
                    'available_sizes' => $activeVariants->pluck('size')->filter()->unique()->sort(fn($a, $b) => (int)$a <=> (int)$b)->values(),
                    'variants' => $activeVariants->map(function ($variant) {
                        return [
                            'id' => $variant->id,
                            'product_id' => $variant->product_id,
                            'sku' => $variant->sku,
                            'arch' => $variant->arch,
                            'size' => $variant->size,
                            'variant_title' => $variant->variant_title,
                            'price' => (float) $variant->price,
                            'selling_price' => (float) ($variant->selling_price ?? $variant->price),
                            'quantity' => (int) $variant->quantity,
                            'in_stock' => (int) $variant->quantity > 0,
                            'status' => (int) $variant->status,
                        ];
                    })->values(),
                    'thumbnail' => $product->thumbnail,
                    'price' => $product->price,
                    'selling_price' => $product->selling_price,
                    'details' => $product->details,
                    'description' => $product->description,
                    'specification' => $product->specification,
                    'quantity' => $product->quantity,
                    'gallery' => $product->gallery,
                    'gallery_order' => $product->gallery_order,
                    'status' => $product->status,
                    'is_trending' => $product->is_trending,
                    'is_featured' => $product->is_featured,
                    'is_best_selling' => $product->is_best_selling,
                    'is_top_rated' => $product->is_top_rated,
                    'is_new_arrival' => $product->is_new_arrival,
                    'badge' => $product->badge,
                    'discount' => $discountValue,
                    'category' => $product->category,
                    'subcategory' => $product->subcategory,
                    'reviews' => $product->reviews->map(
                        fn($review) => $this->formatReviewForApi($review)
                    ),
                    'average_rating' => $product->averageRating(),
                    'total_reviews' => $product->totalReviews(),
                    'created_at' => $product->created_at,
                    'updated_at' => $product->updated_at,
                    'is_wishlist' => ($user && in_array($product->id, $wishlistProductIds)) ? true : false
                ];
            });

        return response()->json([
            'status' => true,
            'message' => 'Products with status 1 fetched successfully',
            'data' => $products
        ]);
    }

    public function show(Request $request, $id)
    {
        $user = $this->resolveOptionalApiUser($request);

        $product = Product::with([
            'category',
            'subcategory',
            'variants' => function ($q) {
                $q->where('status', 1);
            },
            'faqs' => function ($q) {
                $q->where('status', 1)
                    ->whereNotNull('answer')
                    ->orderBy('order', 'asc');
            },
            'reviews.user' => function ($q) {
                $q->orderBy('created_at', 'desc');
            }
        ])->find($id);

        if (!$product) {
            return response()->json([
                'status' => false,
                'message' => 'Product not found'
            ], 404);
        }

        // Thumbnail URL
        $product->thumbnail = $product->thumbnail
            ? url('backend/assets/images/product/' . $product->thumbnail)
            : null;

        // Gallery URLs
        $product->gallery = collect($product->gallery ?? [])
            ->map(function ($img) {
                return url('backend/assets/images/product/' . $img);
            })
            ->values()
            ->toArray();

        // Discount calculation
        $discountValue = null;
        if (
            $product->price !== null &&
            $product->selling_price !== null &&
            $product->price > 0 &&
            $product->selling_price < $product->price
        ) {
            $discountValue = round((($product->price - $product->selling_price) / $product->price) * 100);
        }
        $product->discount = $discountValue;

        // Wishlist flag
        $wishlistProductIds = [];
        if ($user) {
            $wishlistProductIds = Wishlist::where('user_id', $user->id)->pluck('product_id')->toArray();
        }
        $product->is_wishlist = ($user && in_array($product->id, $wishlistProductIds)) ? true : false;
        $product->average_rating = $product->averageRating();
        $product->total_reviews = $product->totalReviews();

        // Product Variants
        // ==========================================
        // PRODUCT VARIANTS FOR API
        // ==========================================

        $activeVariants = $product->variants
            ? $product->variants->where('status', 1)->values()
            : collect();

        $product->is_variable = $product->isVariable();

        // Group variants by arch and return full details for each size.
        $activeVariants = $product->variants
            ->where('status', 1)
            ->values();

        $requestedArch = request()->input('arch');
        $requestedSize = request()->input('size');

        // Find requested variant, otherwise use the first active variant
        $selectedVariant = $activeVariants->first(function ($variant) use (
            $requestedArch,
            $requestedSize
        ) {
            return $requestedArch !== null
                && $requestedSize !== null
                && $variant->arch == $requestedArch
                && (string) $variant->size === (string) $requestedSize;
        });

        $selectedVariant = $selectedVariant ?? $activeVariants->first();

        $product->selling_price = $selectedVariant
            ? (float) ($selectedVariant->selling_price ?? $selectedVariant->price)
            : (float) ($product->selling_price ?? $product->price);

        // ==========================================
        // API RESPONSE
        // ==========================================

        return response()->json([
            'status' => true,
            'message' => 'Product fetched successfully',
            'data' => $product,
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category_id'    => 'required|integer',
            'subcategory_id' => 'nullable|integer',
            'name'           => 'required|string|max:255',
            'slug'           => 'nullable|string|unique:products,slug',
            'sku'            => 'nullable|string|unique:products,sku',
            'price'          => 'required|numeric|min:0',
            'selling_price'  => 'required|numeric|min:0',
            'quantity'       => 'required|integer|min:0',
            'product_type'   => 'nullable|in:simple,variable',
            'details'        => 'nullable|string',
            'description'    => 'nullable|string',
            'specification'  => 'nullable|string',
            'status'         => 'nullable|in:0,1',
            'badge'          => 'nullable|string',
            'thumbnail'      => 'nullable',
            'gallery'        => 'nullable|array',
            'video'          => 'nullable',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'Validation error',
                'errors'  => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {
            // Slug generation
            $slug = $request->filled('slug')
                ? Str::slug($request->slug)
                : Str::slug($request->name);

            $originalSlug = $slug;
            $count = 1;
            while (Product::where('slug', $slug)->exists()) {
                $slug = "{$originalSlug}-{$count}";
                $count++;
            }

            // SKU generation
            $sku = $request->filled('sku')
                ? $request->sku
                : 'PRD-' . strtoupper(Str::random(6));

            // Thumbnail upload or string path
            $thumbnail = null;
            if ($request->hasFile('thumbnail')) {
                $thumb = $request->file('thumbnail');
                $thumbnail = time() . '_thumb.' . $thumb->extension();
                $thumb->move(public_path('backend/assets/images/product'), $thumbnail);
            } elseif ($request->filled('thumbnail') && is_string($request->thumbnail)) {
                $thumbnail = $request->thumbnail;
            }

            // Gallery upload or array
            $gallery = [];
            if ($request->hasFile('gallery')) {
                foreach ($request->file('gallery') as $image) {
                    $imageName = time() . rand(111, 999) . '.' . $image->extension();
                    $image->move(public_path('backend/assets/images/product'), $imageName);
                    $gallery[] = $imageName;
                }
            } elseif ($request->filled('gallery') && is_array($request->gallery)) {
                $gallery = $request->gallery;
            }

            // Video upload
            $video = null;
            if ($request->hasFile('video')) {
                $vid = $request->file('video');
                $video = time() . '_video.' . $vid->extension();
                $vid->move(public_path('backend/assets/videos/product'), $video);
            }

            // Discount
            $price = (float) $request->price;
            $sellingPrice = (float) $request->selling_price;
            $discount = 0;
            if ($price > 0 && $sellingPrice < $price) {
                $discount = (int) round((($price - $sellingPrice) / $price) * 100);
            }

            $product = Product::create([
                'category_id'     => $request->category_id,
                'subcategory_id'  => $request->subcategory_id ?: null,
                'name'            => $request->name,
                'slug'            => $slug,
                'sku'             => $sku,
                'product_type'    => $request->product_type ?? 'simple',
                'thumbnail'       => $thumbnail,
                'price'           => $price,
                'selling_price'   => $sellingPrice,
                'discount'        => $discount,
                'quantity'        => (int) $request->quantity,
                'details'         => $request->details,
                'description'     => $request->description,
                'specification'   => $request->specification,
                'gallery'         => $gallery,
                'gallery_order'   => $gallery,
                'video'           => $video,
                'status'          => $request->has('status') ? (int) $request->status : 1,
                'is_featured'     => $request->boolean('is_featured', false),
                'is_trending'     => $request->boolean('is_trending', false),
                'is_best_selling' => $request->boolean('is_best_selling', false),
                'is_top_rated'    => $request->boolean('is_top_rated', false),
                'is_new_arrival'  => $request->boolean('is_new_arrival', false),
                'badge'           => $request->badge,
            ]);

            // Save variants if variable product
            if (($request->product_type ?? 'simple') === 'variable' && $request->has('variants') && is_array($request->variants)) {
                foreach ($request->variants as $variant) {
                    if (empty($variant['arch']) && empty($variant['size']) && empty($variant['sku'])) {
                        continue;
                    }
                    ProductVariant::create([
                        'product_id'    => $product->id,
                        'arch'          => $variant['arch'] ?? null,
                        'size'          => $variant['size'] ?? null,
                        'sku'           => $variant['sku'] ?? ($sku . '-' . ($variant['arch'] ?? 'VAR') . '-' . ($variant['size'] ?? '1')),
                        'price'         => isset($variant['price']) ? (float)$variant['price'] : $price,
                        'selling_price' => isset($variant['selling_price']) ? (float)$variant['selling_price'] : $sellingPrice,
                        'quantity'      => isset($variant['quantity']) ? (int)$variant['quantity'] : (int)$request->quantity,
                        'status'        => isset($variant['status']) ? (int)$variant['status'] : 1,
                    ]);
                }
            }

            DB::commit();

            $product->load(['category', 'subcategory', 'variants']);
            $product->is_variable = $product->isVariable();
            $product->available_arches = $product->variants->where('status', 1)->pluck('arch')->filter()->unique()->values();
            $product->available_sizes = $product->variants->where('status', 1)->pluck('size')->filter()->unique()->sort(fn($a, $b) => (int)$a <=> (int)$b)->values();

            return response()->json([
                'status'  => true,
                'message' => 'Product created successfully',
                'data'    => $product
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status'  => false,
                'message' => 'Failed to create product: ' . $e->getMessage()
            ], 500);
        }
    }

    public function productDetails(Request $request, $slug = null)
    {
        $slug = $slug ?: ($request->slug ?: $request->input('slug'));

        if (!$slug) {
            return response()->json([
                'status' => false,
                'message' => 'Product slug is required'
            ], 422);
        }

        $user = $this->resolveOptionalApiUser($request);

        $wishlistProductIds = [];
        $cartProductIds = [];

        // GET PRODUCT
        $product = Product::with([
            'category',
            'subcategory',
            'variants' => function ($q) {
                $q->where('status', 1);
            },
            'faqs' => function ($q) {
                $q->where('status', 1)
                    ->whereNotNull('answer')
                    ->orderBy('order', 'asc');
            },
            'reviews.user' => function ($q) {
                $q->orderBy('created_at', 'desc');
            }
        ])
            ->where('slug', $slug)
            ->where('status', 1)
            ->first();

        if (!$product) {
            return response()->json([
                'status' => false,
                'message' => 'Product not found'
            ], 404);
        }

        // WISHLIST / CART
        if ($user) {
            $wishlistProductIds = Wishlist::where('user_id', $user->id)
                ->pluck('product_id')
                ->map(fn($id) => (int) $id)
                ->toArray();

            $cartProductIds = Cart::where('user_id', $user->id)
                ->pluck('product_id')
                ->map(fn($id) => (int) $id)
                ->toArray();
        } else {
            $sessionId = $request->header('session-id');

            if ($sessionId) {
                $cartProductIds = Cart::where('session_id', $sessionId)
                    ->pluck('product_id')
                    ->toArray();
            }
        }

        // THUMBNAIL / VIDEO
        $product->thumbnail = $product->thumbnail
            ? url('backend/assets/images/product/' . $product->thumbnail)
            : null;

        $product->video_url = $product->video
            ? asset('backend/assets/videos/product/' . $product->video)
            : null;

        // GALLERY
        $product->gallery = collect($product->gallery ?? [])
            ->map(function ($img) {
                return url('backend/assets/images/product/' . $img);
            })
            ->values()
            ->toArray();

        unset($product->gallery_order);

        // RECENTLY BOUGHT
        $boughtCount = OrderItem::where('product_id', $product->id)
            ->sum('quantity');

        $product->recently_bought = [
            'count' => $boughtCount,
            'text' => $boughtCount > 0
                ? $boughtCount . '+ people bought this recently'
                : 'Recently added product'
        ];

        // DETAILS / DESCRIPTION
        $rawDetails = html_entity_decode($product->details ?? '');

        $product->details = trim(strip_tags($rawDetails));

        $product->description = trim(
            html_entity_decode(strip_tags($product->description ?? ''))
        );

        // KEY FEATURES
        $keyFeatures = [];

        preg_match_all('/<li[^>]*>(.*?)<\/li>/s', $rawDetails, $matches);

        if (!empty($matches[1])) {
            foreach ($matches[1] as $feature) {
                $feature = trim(html_entity_decode(strip_tags($feature)));

                if (!empty($feature)) {
                    $keyFeatures[] = $feature;
                }
            }
        }

        if (empty($keyFeatures)) {
            $lines = preg_split('/\r\n|\r|\n/', strip_tags($rawDetails));

            foreach ($lines as $line) {
                $line = trim($line);

                if (!empty($line)) {
                    $keyFeatures[] = $line;
                }
            }
        }

        $product->key_features = $keyFeatures;

        // SPECIFICATIONS
        $specifications = [];

        if (!empty($product->specification)) {
            preg_match_all(
                '/<tr[^>]*>(.*?)<\/tr>/is',
                $product->specification,
                $rows
            );

            foreach ($rows[1] as $row) {
                preg_match_all(
                    '/<t[dh][^>]*>(.*?)<\/t[dh]>/is',
                    $row,
                    $cols
                );

                $cells = array_map(
                    fn($cell) => trim(strip_tags($cell)),
                    $cols[1] ?? []
                );

                if (count($cells) >= 3) {
                    $first = $cells[0];

                    if (
                        preg_match('/^\d{1,3}$/', $first) ||
                        in_array(
                            mb_strtolower(rtrim($first, '.')),
                            ['row no', 'row number', 's.no', 'sr no', 'no', '#'],
                            true
                        )
                    ) {
                        $key = $cells[1];
                        $value = $cells[2];
                    } else {
                        $key = $cells[1];
                        $value = $cells[2];
                    }
                } elseif (isset($cells[0], $cells[1])) {
                    $key = $cells[0];
                    $value = $cells[1];
                } else {
                    continue;
                }

                $normalizedKey = mb_strtolower(rtrim(trim($key), '.'));

                if (
                    $key === '' ||
                    in_array(
                        $normalizedKey,
                        [
                            'specification',
                            'specifications',
                            'details',
                            'detail',
                            'row no',
                            'row number'
                        ],
                        true
                    )
                ) {
                    continue;
                }

                $specifications[] = [
                    'key' => $key,
                    'value' => $value,
                ];
            }
        }

        $product->specification = $specifications;

        // STOCK STATUS
        $product->in_stock = $product->quantity > 0;

        $product->stock_status = $product->quantity > 0
            ? 'In Stock'
            : 'Out Of Stock';

        // REVIEWS / RATINGS
        $reviews = collect($product->reviews)->where('status', 1);

        $totalReviews = $reviews->count();

        $averageRating = round($reviews->avg('rating') ?? 0, 1);

        $fullStars = floor($averageRating);

        $emptyStars = 5 - $fullStars;

        $stars = str_repeat('⭐', $fullStars)
            . str_repeat('☆', $emptyStars);

        $ratingBreakdown = [];

        for ($i = 5; $i >= 1; $i--) {
            $count = $reviews->where('rating', $i)->count();

            $percentage = $totalReviews > 0
                ? round(($count / $totalReviews) * 100)
                : 0;

            $ratingBreakdown[] = [
                'star' => $i,
                'count' => $count,
                'percentage' => $percentage
            ];
        }

        $product->rating_summary = [
            'average_rating' => $averageRating,
            'total_reviews' => $totalReviews,
            'rating_breakdown' => $ratingBreakdown,
            'stars' => $stars,
            'display_text' => $stars . ' ' . $averageRating
                . '(' . $totalReviews . ' reviews)'
        ];

        // PURCHASE / REVIEW STATUS
        $hasPurchased = false;
        $hasReviewed = false;

        if ($user) {
            $hasPurchased = OrderItem::where('product_id', $product->id)
                ->whereHas('order', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                })
                ->exists();

            $hasReviewed = Review::where('product_id', $product->id)
                ->where('user_id', $user->id)
                ->exists();
        }

        $product->has_purchased = $hasPurchased;
        $product->has_reviewed = $hasReviewed;
        $product->can_review = $hasPurchased && !$hasReviewed;

        // PRODUCT REVIEW MEDIA
        $product->product_review_media = Review::with('user')
            ->where('product_id', $product->id)
            ->where('status', 1)
            ->get()
            ->flatMap(function ($review) {
                return collect($review->attachment_urls ?? [])
                    ->map(function ($attachment) use ($review) {
                        return [
                            'review_id' => $review->id,
                            'user_id' => $review->user_id,
                            'user_name' => $review->user->name ?? null,
                            'rating' => $review->rating,
                            'review' => $review->review,
                            'created_at' => $review->created_at?->format('d M Y'),
                            'media_type' => $attachment['type'] ?? 'image',
                            'media_url' => $attachment['url'] ?? null,
                        ];
                    });
            })
            ->filter(fn($item) => !empty($item['media_url']))
            ->values()
            ->toArray();

        // FAQ
        $product->faqs = collect($product->faqs ?? [])
            ->filter(function ($faq) {
                return !empty($faq->answer);
            })
            ->map(function ($faq) {
                return [
                    'question' => $faq->question,
                    'answer' => strip_tags($faq->answer),
                ];
            })
            ->values()
            ->toArray();

        // SIMILAR PRODUCTS
        $similarProducts = Product::where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->where('status', 1)
            ->latest()
            ->take(10)
            ->get();

        $product->similar_products = $similarProducts->map(
            function ($item) use ($wishlistProductIds, $cartProductIds, $user) {
                $avgRating = Review::where('product_id', $item->id)
                    ->where('status', 1)
                    ->avg('rating');

                $reviewCount = Review::where('product_id', $item->id)
                    ->where('status', 1)
                    ->count();

                $galleryImages = [];

                if (!empty($item->gallery)) {
                    $gallery = is_string($item->gallery)
                        ? json_decode($item->gallery, true)
                        : $item->gallery;

                    if (is_array($gallery)) {
                        $galleryImages = collect($gallery)
                            ->map(fn($image) => url('backend/assets/images/product/' . $image))
                            ->values()
                            ->toArray();
                    }
                }

                $isWishlist = $user
                    && in_array((int) $item->id, $wishlistProductIds);

                $isCart = in_array((int) $item->id, $cartProductIds);

                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'slug' => $item->slug,
                    'thumbnail' => $item->thumbnail
                        ? url('backend/assets/images/product/' . $item->thumbnail)
                        : null,
                    'gallery' => $galleryImages,
                    'price' => (float) $item->price,
                    'selling_price' => (float) $item->selling_price,
                    'rating' => round($avgRating ?? 0, 1),
                    'review_count' => $reviewCount,
                    'quantity' => $item->quantity,
                    'in_stock' => $item->quantity > 0,
                    'stock_status' => $item->quantity > 0
                        ? 'In Stock'
                        : 'Out Of Stock',
                    'is_wishlist' => $isWishlist,
                    'is_cart' => $isCart,
                ];
            }
        );

        // MAIN PRODUCT WISHLIST / CART STATUS
        $product->is_wishlist = $user
            && in_array((int) $product->id, $wishlistProductIds, true);

        $product->is_cart = in_array(
            (int) $product->id,
            $cartProductIds,
            true
        );

        // FORMAT REVIEWS
        $approvedReviews = collect($product->reviews)
            ->where('status', 1)
            ->sortByDesc('created_at')
            ->values();

        $userReactions = $this->getUserReviewReactions(
            $user,
            $approvedReviews->pluck('id')->all()
        );

        $product->reviews = $approvedReviews
            ->map(fn($review) => $this->formatReviewForApi(
                $review,
                $userReactions[$review->id] ?? null
            ))
            ->values();

        // --------------------------------------------------
        // PRODUCT VARIANTS
        // --------------------------------------------------
        $activeVariants = $product->variants
            ? $product->variants->where('status', 1)->values()
            : collect();

        $product->is_variable = $activeVariants->isNotEmpty() && $product->isVariable();
        $product->is_variant = $product->is_variable;

        // Group available sizes by arch
        $product->variant_types = $activeVariants
            ->filter(fn($variant) => !empty($variant->arch))
            ->groupBy('arch')
            ->map(function ($variants, $arch) {
                return [
                    'arch' => $arch,
                    'available_sizes' => $variants
                        ->pluck('size')
                        ->filter(fn($size) => $size !== null && $size !== '')
                        ->unique()
                        ->sort(fn($a, $b) => (int) $a <=> (int) $b)
                        ->values()
                        ->map(fn($size) => (string) $size)
                        ->toArray(),
                ];
            })
            ->values();

        // --------------------------------------------------
        // SELECT VARIANT AND UPDATE PRODUCT PRICE
        // --------------------------------------------------

        // Read arch and size from query parameters or request body
        $requestedArch = $request->input('arch');
        $requestedSize = $request->input('size');

        // Match the requested active variant
        $selectedVariant = null;

        if ($requestedArch !== null && $requestedSize !== null) {
            $selectedVariant = $activeVariants->first(function ($variant) use (
                $requestedArch,
                $requestedSize
            ) {
                return (string) $variant->arch === (string) $requestedArch
                    && (string) $variant->size === (string) $requestedSize;
            });
        }

        // If no valid selection was sent, use the first active variant
        if (!$selectedVariant) {
            $selectedVariant = $activeVariants->first();
        }

        // Update the main product price from the selected variant
        if ($selectedVariant) {
            $product->price = (float) (
                $selectedVariant->price ?? $product->price
            );

            $product->selling_price = (float) (
                $selectedVariant->selling_price ?? $selectedVariant->price
            );

            $product->selected_variant = [
                'id' => $selectedVariant->id,
                'arch' => $selectedVariant->arch,
                'size' => (string) $selectedVariant->size,
                'price' => (float) $selectedVariant->price,
                'selling_price' => (float) (
                    $selectedVariant->selling_price ?? $selectedVariant->price
                ),
                'quantity' => (int) $selectedVariant->quantity,
                'in_stock' => (int) $selectedVariant->quantity > 0,
            ];
        } else {
            // Product has no active variants
            $product->selected_variant = null;
        }

        // Return all active variants
        $product->variants = $activeVariants->map(function ($variant) {
            return [
                'id' => $variant->id,
                'product_id' => $variant->product_id,
                'sku' => $variant->sku,
                'arch' => $variant->arch,
                'size' => $variant->size,
                'variant_title' => $variant->variant_title,
                'price' => (float) $variant->price,
                'selling_price' => (float) (
                    $variant->selling_price ?? $variant->price
                ),
                'quantity' => (int) $variant->quantity,
                'in_stock' => (int) $variant->quantity > 0,
                'status' => (int) $variant->status,
            ];
        })->values();

        return response()->json([
            'status' => true,
            'message' => 'Product fetched successfully',
            'data' => $product
        ]);
    }

    public function productPriceRange($category_slug, $subcategory_slug = null)
    {
        $category = Category::where('slug', $category_slug)->where('status', 1)->first();

        if (!$category) {
            return response()->json([
                'status' => false,
                'message' => 'Category not found',
                'data' => null,
            ], 404);
        }

        $subcategory = null;

        if ($subcategory_slug) {
            $subcategory = SubCategory::where('slug', $subcategory_slug)
                ->where('category_id', $category->id)
                ->where('status', 1)
                ->first();

            if (!$subcategory) {
                return response()->json([
                    'status' => false,
                    'message' => 'Subcategory not found for this category',
                    'data' => null,
                ], 404);
            }
        }

        $productsQuery = Product::where('status', 1)
            ->where('category_id', $category->id);

        if ($subcategory) {
            $productsQuery->where('subcategory_id', $subcategory->id);
        }

        $prices = $this->collectProductPrices($productsQuery);

        return response()->json([
            'status' => true,
            'message' => 'Price range fetched successfully',
            'data' => [
                'category' => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                ],
                'subcategory' => $subcategory ? [
                    'id' => $subcategory->id,
                    'name' => $subcategory->name,
                    'slug' => $subcategory->slug,
                ] : null,
                'min_price' => !empty($prices) ? (float) min($prices) : 0,
                'max_price' => !empty($prices) ? (float) max($prices) : 0,
                'product_count' => $productsQuery->count(),
            ],
        ]);
    }

    private function collectProductPrices($query): array
    {
        $prices = [];

        $query->with(['variants' => function ($q) {
            $q->where('status', 1);
        }])->get()->each(function ($product) use (&$prices) {
            if ($product->product_type === 'variable' && $product->variants->isNotEmpty()) {
                foreach ($product->variants as $variant) {
                    $price = $this->getEffectivePrice($variant->selling_price, $variant->price);
                    if ($price !== null) {
                        $prices[] = $price;
                    }
                }
            } else {
                $price = $this->getEffectivePrice($product->selling_price, $product->price);
                if ($price !== null) {
                    $prices[] = $price;
                }
            }
        });

        return $prices;
    }

    private function getEffectivePrice($sellingPrice, $price): ?float
    {
        if ($sellingPrice !== null && $sellingPrice > 0) {
            return (float) $sellingPrice;
        }

        if ($price !== null && $price > 0) {
            return (float) $price;
        }

        return null;
    }

    private function getPriceRange(array $prices): array
    {
        return [
            'min_price' => !empty($prices) ? (float) min($prices) : 0,
            'max_price' => !empty($prices) ? (float) max($prices) : 0,
        ];
    }

    public function categorywiseProduct(Request $request, $category_slug)
    {
        // If wildcard catches get-wishlist or wishlist-count, forward to handler
        if ($category_slug === 'get-wishlist') {
            return $this->getWishlist($request);
        }

        if ($category_slug === 'wishlist-count') {
            return $this->wishlistCount($request);
        }

        if ($category_slug === 'buy-now') {
            return $this->getBuyNow($request);
        }

        $category = Category::where('slug', $category_slug)
            ->where('status', 1)
            ->first();

        if (!$category) {
            return response()->json([
                'status'  => false,
                'message' => 'Category not found',
                'data'    => []
            ], 404);
        }

        // Price Range Query
        $priceRangeQuery = Product::where('status', 1)
            ->where('category_id', $category->id);

        $allPrices = $this->collectProductPrices(clone $priceRangeQuery);

        $originalPriceRange = [
            'min_price' => !empty($allPrices) ? min($allPrices) : 0,
            'max_price' => !empty($allPrices) ? max($allPrices) : 0,
        ];

        // Product Query
        $query = Product::with([
            'category',
            'subcategory',
            'reviews',
            'variants' => fn($q) => $q->where('status', 1)
        ])
            ->where('status', 1)
            ->where('category_id', $category->id);

        if ($request->filled('min_price')) {
            $query->where('selling_price', '>=', $request->min_price);
        }

        if ($request->filled('max_price')) {
            $query->where('selling_price', '<=', $request->max_price);
        }

        $products = $query->get();

        // Image URLs
        $products = $products->map(function ($product) {

            // Thumbnail
            $product->thumbnail = $product->thumbnail
                ? url('backend/assets/images/product/' . $product->thumbnail)
                : null;

            // Gallery
            $gallery = is_array($product->gallery)
                ? $product->gallery
                : json_decode($product->gallery, true);

            $product->gallery = collect($gallery ?? [])
                ->map(function ($img) {
                    return url('backend/assets/images/product/' . $img);
                })
                ->values();

            // Gallery Order
            $galleryOrder = is_array($product->gallery_order)
                ? $product->gallery_order
                : json_decode($product->gallery_order, true);

            $product->gallery_order = collect($galleryOrder ?? [])
                ->map(function ($img) {
                    return url('backend/assets/images/product/' . $img);
                })
                ->values();

            $activeVariants = $product->variants ? $product->variants->where('status', 1)->values() : collect();
            $product->is_variable = $product->isVariable();
            $product->available_arches = $activeVariants->pluck('arch')->filter()->unique()->values();
            $product->available_sizes = $activeVariants->pluck('size')->filter()->unique()->sort(fn($a, $b) => (int)$a <=> (int)$b)->values();

            return $product;
        });

        return response()->json([
            'status' => true,
            'message' => 'Products fetched successfully',
            'filters_applied' => [
                'min_price' => $request->min_price,
                'max_price' => $request->max_price,
            ],
            'price_range' => $originalPriceRange,
            'data' => $products,
        ]);
    }

    public function subcategorywiseProduct(Request $request, $category_slug, $subcategory_slug)
    {
        // If wildcard catches buy-now routes, forward to handler
        if ($category_slug === 'buy-now') {
            if ($subcategory_slug === 'get' || $subcategory_slug === 'buy-now') {
                return $this->getBuyNow($request);
            }
        }

        $user = auth()->user();
        $wishlistProductIds = [];

        if ($user) {
            $wishlistProductIds = Wishlist::where('user_id', $user->id)
                ->pluck('product_id')
                ->toArray();
        }

        // Find category
        $category = Category::where('slug', $category_slug)->first();

        if (!$category) {
            return response()->json([
                'status' => true,
                'message' => 'Category not found',
                'min_price' => 0,
                'max_price' => 0,
                'data' => []
            ]);
        }

        // Find subcategory
        $subcategory = SubCategory::where('slug', $subcategory_slug)
            ->where('category_id', $category->id)
            ->first();

        if (!$subcategory) {
            return response()->json([
                'status' => true,
                'message' => 'Subcategory not found for this category',
                'min_price' => 0,
                'max_price' => 0,
                'data' => []
            ]);
        }

        // 🔥 Build product query with price filters
        $productQuery = Product::with([
            'category',
            'subcategory',
            'reviews',
            'variants' => fn($q) => $q->where('status', 1)
        ])
            ->where('status', 1)
            ->where('category_id', $category->id)
            ->where('subcategory_id', $subcategory->id);

        // ✅ Apply price range filters from query parameters
        if ($request->has('min_price') && is_numeric($request->min_price)) {
            $productQuery->where(function ($q) use ($request) {
                $q->where('selling_price', '>=', $request->min_price)
                    ->orWhere('price', '>=', $request->min_price);
            });
        }

        if ($request->has('max_price') && is_numeric($request->max_price)) {
            $productQuery->where(function ($q) use ($request) {
                $q->where('selling_price', '<=', $request->max_price)
                    ->orWhere('price', '<=', $request->max_price);
            });
        }

        // Get filtered products
        $products = $productQuery->latest()->get();

        // Get price range for the filtered products
        $priceRange = $this->getPriceRange(
            $this->collectProductPrices(
                clone $productQuery
            )
        );

        if ($products->isEmpty()) {
            return response()->json([
                'status' => true,
                'message' => 'No products found in this price range',
                'min_price' => $priceRange['min_price'],
                'max_price' => $priceRange['max_price'],
                'data' => []
            ]);
        }

        $products = $products->map(function ($product) use ($wishlistProductIds, $user) {
            // Thumbnail
            $product->thumbnail = $product->thumbnail
                ? url('backend/assets/images/product/' . $product->thumbnail)
                : null;

            // Gallery
            $product->gallery = collect($product->gallery ?? [])
                ->map(function ($img) {
                    return url('backend/assets/images/product/' . $img);
                })
                ->values();

            // Gallery Order
            $product->gallery_order = collect($product->gallery_order ?? [])
                ->map(function ($img) {
                    return url('backend/assets/images/product/' . $img);
                })
                ->values();

            // Discount %
            $discountValue = null;
            if (
                $product->price !== null &&
                $product->selling_price !== null &&
                $product->price > 0 &&
                $product->selling_price < $product->price
            ) {
                $discountValue = round(
                    (($product->price - $product->selling_price) / $product->price) * 100
                );
            }

            $product->discount = $discountValue;
            $product->is_wishlist = $user
                ? in_array($product->id, $wishlistProductIds)
                : false;

            $activeVariants = $product->variants ? $product->variants->where('status', 1)->values() : collect();
            $product->is_variable = $product->isVariable();
            $product->available_arches = $activeVariants->pluck('arch')->filter()->unique()->values();
            $product->available_sizes = $activeVariants->pluck('size')->filter()->unique()->sort(fn($a, $b) => (int)$a <=> (int)$b)->values();

            return $product;
        });

        return response()->json([
            'status' => true,
            'message' => 'Products fetched successfully',
            'filters_applied' => [
                'min_price' => $request->min_price ?? null,
                'max_price' => $request->max_price ?? null,
            ],
            'price_range' => $priceRange,
            'data' => $products
        ]);
    }

    // public function allCategorySubcategories()
    // {
    //     $user = auth()->user();
    //     $wishlistProductIds = [];

    //     if ($user) {
    //         $wishlistProductIds = Wishlist::where('user_id', $user->id)->pluck('product_id')->toArray();
    //     }

    //     /*
    //     |--------------------------------------------------------------------------
    //     | All Categories With Subcategories
    //     |--------------------------------------------------------------------------
    //     */
    //     $categories = Category::where('status', 1)
    //         ->with([
    //             'subcategories' => function ($query) {
    //                 $query->where('status', 1)
    //                     ->latest()
    //                     ->with([
    //                         'childCategories' => function ($childQuery) {
    //                             $childQuery->where('status', 1)
    //                                 ->orderBy('id');
    //                         }
    //                     ]);
    //             }
    //         ])
    //         ->orderBy('sort_order')
    //         ->orderBy('id')
    //         ->get();

    //     /*
    //     |--------------------------------------------------------------------------
    //     | Only Latest 2 Products With Reviews
    //     |--------------------------------------------------------------------------
    //     */
    //     $products = Product::with(['category', 'subcategory', 'reviews'])
    //         ->where('status', 1)
    //         ->latest()
    //         ->take(2)
    //         ->get();

    //     /*
    //     |--------------------------------------------------------------------------
    //     | Category Data
    //     |--------------------------------------------------------------------------
    //     */
    //     $allCategory = $categories->map(function ($category) {
    //         return [
    //             'id' => $category->id,
    //             'name' => $category->name,
    //             'slug' => $category->slug,
    //             'image' => $category->image
    //                 ? url('backend/assets/images/category/' . $category->image)
    //                 : null,
    //             'subcategories' => $category->subcategories->map(function ($subcategory) use ($category) {
    //                 return [
    //                     'id' => $subcategory->id,
    //                     'name' => $subcategory->name,
    //                     'slug' => $subcategory->slug,
    //                     'url' => url($category->slug . '/' . $subcategory->slug),
    //                 ];
    //             }),
    //         ];
    //     });

    //     /*
    //     |--------------------------------------------------------------------------
    //     | Product Data
    //     |--------------------------------------------------------------------------
    //     */
    //     $productData = $products->map(function ($product) use ($wishlistProductIds, $user) {
    //         $approvedReviews = $product->reviews->where('status', 1);
    //         $discountValue = null;

    //         if (
    //             $product->price !== null &&
    //             $product->selling_price !== null &&
    //             $product->price > 0 &&
    //             $product->selling_price < $product->price
    //         ) {
    //             $discountValue = round((($product->price - $product->selling_price) / $product->price) * 100);
    //         }

    //         return [
    //             'id' => $product->id,
    //             'category_id' => $product->category_id,
    //             'subcategory_id' => $product->subcategory_id,
    //             'category_name' => optional($product->category)->name,
    //             'subcategory_name' => optional($product->subcategory)->name,
    //             'name' => $product->name,
    //             'slug' => $product->slug,
    //             'thumbnail' => $product->thumbnail
    //                 ? url('backend/assets/images/product/' . $product->thumbnail)
    //                 : null,
    //             'price' => $product->price,
    //             'selling_price' => $product->selling_price,
    //             'discount' => $discountValue,
    //             'badge' => $product->badge,
    //             'average_rating' => round($approvedReviews->avg('rating') ?? 0, 1),
    //             'total_reviews' => $approvedReviews->count(),
    //             'product_url' => url(
    //                 optional($product->category)->slug . '/' .
    //                     optional($product->subcategory)->slug . '/' .
    //                     $product->slug
    //             ),
    //             'is_wishlist' => ($user && in_array($product->id, $wishlistProductIds)) ? true : false
    //         ];
    //     });

    //     /*
    //     |--------------------------------------------------------------------------
    //     | Final Response
    //     |--------------------------------------------------------------------------
    //     */
    //     return response()->json([
    //         'status' => true,
    //         'message' => 'All categories and latest 2 products fetched successfully',
    //         'data' => [
    //             'AllCategory' => $allCategory,
    //             'Product' => $productData,
    //         ]
    //     ]);
    // }
    public function allCategorySubcategories()
    {
        $user = auth()->user();
        $wishlistProductIds = [];

        if ($user) {
            $wishlistProductIds = Wishlist::where('user_id', $user->id)
                ->pluck('product_id')
                ->toArray();
        }

        /*
        |--------------------------------------------------------------------------
        | All Categories With Subcategories and Child Categories
        |--------------------------------------------------------------------------
        */
        $categories = Category::where('status', 1)
            ->with([
                'subcategories' => function ($query) {
                    $query->where('status', 1)
                        ->latest()
                        ->with([
                            'childCategories' => function ($childQuery) {
                                $childQuery->where('status', 1)
                                    ->orderBy('id');
                            }
                        ]);
                }
            ])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Only Latest 2 Products With Reviews
        |--------------------------------------------------------------------------
        */
        $products = Product::with([
            'category',
            'subcategory',
            'childCategory',
            'reviews'
        ])
            ->where('status', 1)
            ->latest()
            ->take(2)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Category Data
        |--------------------------------------------------------------------------
        */
        $allCategory = $categories->map(function ($category) {

            return [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'image' => $category->image
                    ? url('backend/assets/images/category/' . $category->image)
                    : null,

                'subcategories' => $category->subcategories->map(function ($subcategory) use ($category) {

                    return [
                        'id' => $subcategory->id,
                        'category_id' => $category->id,
                        'name' => $subcategory->name,
                        'slug' => $subcategory->slug,
                        'url' => url($category->slug . '/' . $subcategory->slug),

                        'child_categories' => $subcategory->childCategories->map(function ($child) use ($category, $subcategory) {

                            return [
                                'id' => $child->id,
                                'sub_category_id' => $subcategory->id,
                                'name' => $child->name,
                                'slug' => $child->slug,
                                'image' => $child->image
                                    ? url('backend/assets/images/childcategory/' . $child->image)
                                    : null,
                                'url' => url(
                                    $category->slug . '/' .
                                        $subcategory->slug . '/' .
                                        $child->slug
                                ),
                            ];
                        })->values(),
                    ];
                })->values(),
            ];
        });

        /*
        |--------------------------------------------------------------------------
        | Product Data
        |--------------------------------------------------------------------------
        */
        $productData = $products->map(function ($product) use ($wishlistProductIds, $user) {

            $approvedReviews = $product->reviews->where('status', 1);
            $discountValue = null;

            if (
                $product->price !== null &&
                $product->selling_price !== null &&
                $product->price > 0 &&
                $product->selling_price < $product->price
            ) {
                $discountValue = round(
                    (($product->price - $product->selling_price) / $product->price) * 100
                );
            }

            return [
                'id' => $product->id,

                'category_id' => $product->category_id,
                'subcategory_id' => $product->subcategory_id,
                'child_category_id' => $product->child_category_id,

                'category_name' => optional($product->category)->name,
                'subcategory_name' => optional($product->subcategory)->name,
                'child_category_name' => optional($product->childCategory)->name,

                'name' => $product->name,
                'slug' => $product->slug,

                'thumbnail' => $product->thumbnail
                    ? url('backend/assets/images/product/' . $product->thumbnail)
                    : null,

                'price' => $product->price,
                'selling_price' => $product->selling_price,
                'discount' => $discountValue,
                'badge' => $product->badge,

                'average_rating' => round($approvedReviews->avg('rating') ?? 0, 1),
                'total_reviews' => $approvedReviews->count(),

                'product_url' => url(
                    optional($product->category)->slug . '/' .
                        optional($product->subcategory)->slug . '/' .
                        optional($product->childCategory)->slug . '/' .
                        $product->slug
                ),

                'is_wishlist' => ($user && in_array($product->id, $wishlistProductIds))
                    ? true
                    : false
            ];
        });

        /*
        |--------------------------------------------------------------------------
        | Final Response
        |--------------------------------------------------------------------------
        */
        return response()->json([
            'status' => true,
            'message' => 'All categories, subcategories, child categories and latest 2 products fetched successfully',
            'data' => [
                'AllCategory' => $allCategory,
                'Product' => $productData,
            ]
        ]);
    }

    private function productResponse($product)
    {
        $user = auth()->user();
        $wishlistProductIds = [];

        if ($user) {
            $wishlistProductIds = Wishlist::where('user_id', $user->id)->pluck('product_id')->toArray();
        }

        // Calculate discount as percent
        $discountValue = null;
        if (
            $product->price !== null &&
            $product->selling_price !== null &&
            $product->price > 0 &&
            $product->selling_price < $product->price
        ) {
            $discountValue = round((($product->price - $product->selling_price) / $product->price) * 100);
        }

        return response()->json([
            'status' => true,
            'message' => 'Product fetched successfully',
            'data' => [
                'id' => $product->id,
                'category_id' => $product->category_id,
                'subcategory_id' => $product->subcategory_id,
                'name' => $product->name,
                'slug' => $product->slug,
                'thumbnail' => $product->thumbnail,
                'price' => $product->price,
                'selling_price' => $product->selling_price,
                'details' => $product->details,
                'description' => $product->description,
                'specification' => $product->specification,
                'quantity' => $product->quantity,
                'gallery' => $product->gallery,
                'gallery_order' => $product->gallery_order,
                'status' => $product->status,
                'is_trending' => $product->is_trending,
                'is_featured' => $product->is_featured,
                'is_best_selling' => $product->is_best_selling,
                'is_top_rated' => $product->is_top_rated,
                'is_new_arrival' => $product->is_new_arrival,
                'badge' => $product->badge,
                'discount' => $discountValue,
                'category' => $product->category,
                'subcategory' => $product->subcategory,
                'reviews' => $product->reviews->map(
                    fn($review) => $this->formatReviewForApi($review)
                ),
                'created_at' => $product->created_at,
                'updated_at' => $product->updated_at,
                'is_wishlist' => ($user && in_array($product->id, $wishlistProductIds)) ? true : false,
            ],
            'average_rating' => $product->averageRating(),
            'total_reviews' => $product->totalReviews()
        ]);
    }

    public function bestSelling()
    {
        $user = auth()->user();
        $wishlistProductIds = [];

        if ($user) {
            $wishlistProductIds = Wishlist::where('user_id', $user->id)->pluck('product_id')->toArray();
        }

        $products = Product::where('status', 1)
            ->where('is_best_selling', 1)
            ->latest()
            ->get()
            ->map(function ($product) use ($wishlistProductIds, $user) {
                $discountValue = null;
                if (
                    $product->price !== null &&
                    $product->selling_price !== null &&
                    $product->price > 0 &&
                    $product->selling_price < $product->price
                ) {
                    $discountValue = round((($product->price - $product->selling_price) / $product->price) * 100);
                }

                $product->discount = $discountValue;
                $product->is_wishlist = ($user && in_array($product->id, $wishlistProductIds)) ? true : false;

                return $product;
            });

        return response()->json([
            'status' => true,
            'data' => $products
        ]);
    }

    public function topRated()
    {
        $user = auth()->user();
        $wishlistProductIds = [];

        if ($user) {
            $wishlistProductIds = Wishlist::where('user_id', $user->id)->pluck('product_id')->toArray();
        }

        $products = Product::where('status', '=', 1)
            ->where('is_top_rated', '=', 1)
            ->latest()
            ->get()
            ->map(function ($product) use ($wishlistProductIds, $user) {
                $discountValue = null;
                if (
                    $product->price !== null &&
                    $product->selling_price !== null &&
                    $product->price > 0 &&
                    $product->selling_price < $product->price
                ) {
                    $discountValue = round((($product->price - $product->selling_price) / $product->price) * 100);
                }

                $product->discount = $discountValue;
                $product->is_wishlist = ($user && in_array($product->id, $wishlistProductIds)) ? true : false;

                return $product;
            });

        return response()->json([
            'status' => true,
            'data' => $products
        ]);
    }

    public function blogCategories()
    {
        $categories = BlogCategory::where('status', 1)
            ->latest()
            ->get()
            ->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                ];
            });

        return response()->json([
            'success' => true,
            'message' => 'Blog categories fetched successfully',
            'data' => $categories
        ]);
    }

    public function blogs(Request $request)
    {
        $query = CaseStudy::where('status', 1);

        if ($request->filled('slug') && $request->slug !== 'all') {
            $category = BlogCategory::where('slug', $request->slug)->first();

            if ($category) {
                $query->where('category_id', $category->id);
            } else {
                return response()->json([
                    'success' => true,
                    'message' => 'Category Not Found',
                    'data' => [
                        'blogs' => [],
                        'trending' => []
                    ]
                ]);
            }
        }

        $blogs = $query->with('category')
            ->latest()
            ->get()
            ->map(function ($blog) {
                $blog->image = url('backend/assets/images/blog/' . $blog->image);
                $blog->description = strip_tags($blog->description);
                $blog->short_description = strip_tags($blog->short_description);
                return $blog;
            });

        $trending = CaseStudy::where('status', 1)
            ->where('trending', 1)
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($blog) {
                return [
                    'title' => $blog->title,
                    'slug' => $blog->slug,
                    'image' => url('backend/assets/images/blog/' . $blog->image),
                    'short_description' => strip_tags($blog->short_description),
                    'created_at' => $blog->created_at->format('d M Y'),
                ];
            });

        return response()->json([
            'success' => true,
            'message' => ($request->filled('slug') && $request->slug !== 'all')
                ? 'Category wise blogs fetched successfully'
                : 'All blogs fetched successfully',
            'data' => [
                'blogs' => $blogs,
                'trending' => $trending
            ]
        ]);
    }

    public function blogDetails($slug)
    {
        $slug = trim($slug);

        $blog = CaseStudy::with('category')
            ->where('slug', $slug)
            ->where('status', 1)
            ->first();

        if (!$blog) {
            return response()->json([
                'status' => false,
                'message' => 'Blog not found'
            ], 404);
        }

        $blogImage = $blog->image
            ? url('backend/assets/images/blog/' . $blog->image)
            : null;

        // Trending Blogs
        $trending = CaseStudy::where('status', 1)
            ->where('trending', 1)
            ->where('id', '!=', $blog->id) // Exclude current blog
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($item) {
                return [
                    'title' => $item->title,
                    'slug' => $item->slug,
                    'image' => $item->image
                        ? url('backend/assets/images/blog/' . $item->image)
                        : null,
                    'short_description' => strip_tags($item->short_description),
                    'created_at' => $item->created_at->format('d M Y'),
                ];
            });

        return response()->json([
            'status' => true,
            'message' => 'Blog fetched successfully',
            'data' => [
                'blog' => [
                    'id' => $blog->id,
                    'title' => $blog->title,
                    'slug' => $blog->slug,
                    'category' => $blog->category,
                    'image' => $blogImage,
                    'author' => $blog->author,
                    'blog_date' => $blog->blog_date,
                    'short_description' => strip_tags($blog->short_description),
                    'description' => $blog->description,
                    'tags' => !empty($blog->tags)
                        ? array_map('trim', explode(',', $blog->tags))
                        : [],
                    'status' => $blog->status,
                    'created_at' => $blog->created_at,
                ],
                'trending' => $trending
            ]
        ]);
    }

    public function relatedBlogs($slug)
    {
        $blog = CaseStudy::where('slug', $slug)->first();

        if (!$blog) {
            return response()->json([
                'status' => false,
                'message' => 'Blog not found'
            ], 404);
        }

        $related = CaseStudy::where('category_id', $blog->category_id)
            ->where('id', '!=', $blog->id)
            ->where('status', 1)
            ->latest()
            ->limit(10)
            ->with('category') // Make sure to eager load category relation
            ->get()
            ->map(function ($relatedBlog) {
                return [
                    'id' => $relatedBlog->id,
                    'title' => $relatedBlog->title,
                    'slug' => $relatedBlog->slug,
                    'name' => $relatedBlog->title, // Added 'name' field as duplicate of title
                    'category_id' => $relatedBlog->category_id,
                    'category_name' => optional($relatedBlog->category)->name, // Show category name
                    'image' => $relatedBlog->image ? url('backend/assets/images/blog/' . $relatedBlog->image) : null,
                    'author' => $relatedBlog->author,
                    'blog_date' => $relatedBlog->blog_date,
                    'short_description' => strip_tags($relatedBlog->short_description),
                    'description' => strip_tags($relatedBlog->description),
                    'status' => $relatedBlog->status,
                    'created_at' => $relatedBlog->created_at,
                    'updated_at' => $relatedBlog->updated_at
                ];
            });

        return response()->json([
            'status' => true,
            'data' => $related
        ]);
    }

    public function mergeCart(Request $request)
    {
        $request->validate([
            'session_id' => 'required|string',
        ]);

        $user = auth()->user();
        $guestCartItems = Cart::where('session_id', $request->session_id)->get();

        foreach ($guestCartItems as $item) {
            $existingCart = Cart::where('user_id', $user->id)
                ->where('product_id', $item->product_id)
                ->where('variant_id', $item->variant_id)
                ->first();

            if ($existingCart) {
                $existingCart->quantity += $item->quantity;
                $existingCart->save();
                $item->delete();
            } else {
                $item->update([
                    'user_id' => $user->id,
                    'session_id' => null
                ]);
            }
        }

        return response()->json([
            'status' => true,
            'message' => 'Cart merged successfully'
        ]);
    }

    public function addToCart(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'variant_id' => 'nullable|integer|exists:product_variants,id',
            'quantity'   => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        // Get product
        $product = Product::find($request->product_id);
        if (!$product) {
            return response()->json([
                'status' => false,
                'message' => 'Product not found',
            ], 404);
        }

        // Resolve variant by arch & size if variant_id wasn't directly supplied
        if (empty($request->variant_id)) {
            $archInput = $request->input('arch') ?: $request->input('selected_arch');
            $sizeInput = $request->input('size') ?: $request->input('selected_size');
            if ($archInput && $sizeInput) {
                $matchedVariant = ProductVariant::where('product_id', $product->id)
                    ->where('arch', $archInput)
                    ->where('size', (string) $sizeInput)
                    ->where('status', 1)
                    ->first();
                if ($matchedVariant) {
                    $request->merge(['variant_id' => $matchedVariant->id]);
                }
            }
        }

        // Require variant selection if product is variable
        if ($product->isVariable() && empty($request->variant_id)) {
            return response()->json([
                'status' => false,
                'message' => 'Please select product Arch and Size variant before adding to cart',
            ], 422);
        }

        if (!empty($request->variant_id)) {
            $variant = ProductVariant::where('id', $request->variant_id)
                ->where('product_id', $request->product_id)
                ->where('status', 1)
                ->first();
            if (!$variant) {
                return response()->json([
                    'status' => false,
                    'message' => 'Selected variant does not belong to this product or is inactive',
                ], 422);
            }
        }

        // 🔥 Check stock for simple or variant product
        $stockCheck = $this->checkProductStock(
            $request->product_id,
            $request->quantity,
            $request->variant_id
        );

        if (!$stockCheck['available']) {
            return response()->json([
                'status' => false,
                'message' => $stockCheck['message'],
                'available_stock' => $stockCheck['available_stock'],
                'product_type' => $stockCheck['product_type']
            ], 400);
        }

        // 🔐 Check login user
        $user = $this->resolveOptionalApiUser($request);

        // 🟢 Guest session support
        $sessionId = $this->resolveOptionalSessionId($request, $user);

        if (!$user && !$sessionId) {
            $sessionId = uniqid('guest_');
        }

        if ($user && $sessionId) {
            Cart::whereNull('user_id')
                ->where('session_id', $sessionId)
                ->update(['user_id' => $user->id]);
        }

        // 🔍 Find existing cart item (user OR guest)
        $cartQuery = Cart::where('product_id', $request->product_id)
            ->where('variant_id', $request->variant_id);

        if ($user) {
            $cartQuery->where('user_id', $user->id);
        } else {
            $cartQuery->where('session_id', $sessionId);
        }

        $cart = $cartQuery->first();

        // ✅ Check if total quantity (existing + new) exceeds available stock
        $currentQuantity = $cart ? (int) $cart->quantity : 0;
        $newTotalQuantity = $currentQuantity + $request->quantity;

        $availableStock = $this->getProductAvailableStock(
            $request->product_id,
            $request->variant_id
        );

        if ($newTotalQuantity > $availableStock) {
            return response()->json([
                'status' => false,
                'message' => 'Cannot add more items. Available stock: ' . $availableStock . ', Your cart has: ' . $currentQuantity,
                'available_stock' => $availableStock,
                'current_cart_quantity' => $currentQuantity
            ], 400);
        }

        if ($cart) {
            // ➕ increase quantity
            $cart->quantity += $request->quantity;
            if ($user && empty($cart->user_id)) {
                $cart->user_id = $user->id;
            }
            if ($sessionId && empty($cart->session_id)) {
                $cart->session_id = $sessionId;
            }
            $cart->save();
        } else {
            // 🆕 create new cart item
            $cart = Cart::create([
                'user_id'    => $user->id ?? null,
                'session_id' => $sessionId,
                'product_id' => $request->product_id,
                'variant_id' => $request->variant_id,
                'quantity'   => $request->quantity,
            ]);
        }

        // Load product details for response
        $cart->load('product', 'variant');

        $vSelling = (float) ($cart->variant->selling_price ?? 0);
        $vPrice = (float) ($cart->variant->price ?? 0);
        $pSelling = (float) ($cart->product->selling_price ?? 0);
        $pPrice = (float) ($cart->product->price ?? 0);

        if ($cart->variant) {
            $unitPrice = $vSelling > 0 ? $vSelling : ($vPrice > 0 ? $vPrice : ($pSelling > 0 ? $pSelling : $pPrice));
            $originalPrice = $vPrice > 0 ? $vPrice : ($pPrice > 0 ? $pPrice : $unitPrice);
        } else {
            $unitPrice = $pSelling > 0 ? $pSelling : $pPrice;
            $originalPrice = $pPrice > 0 ? $pPrice : $unitPrice;
        }

        if ($user) {
            \Illuminate\Support\Facades\Cache::put('checkout_active_flow_u_' . $user->id, 'cart', now()->addDays(7));
            \Illuminate\Support\Facades\Cache::put('cart_viewed_at_u_' . $user->id, now()->timestamp, now()->addDays(7));
        }
        if ($sessionId) {
            \Illuminate\Support\Facades\Cache::put('checkout_active_flow_s_' . $sessionId, 'cart', now()->addDays(7));
            \Illuminate\Support\Facades\Cache::put('cart_viewed_at_s_' . $sessionId, now()->timestamp, now()->addDays(7));
        }

        $cartArch = $cart->variant?->arch;
        $cartSize = $cart->variant?->size !== null && $cart->variant?->size !== '' ? (string) $cart->variant->size : null;
        $cartVariantTitle = $cart->variant ? ($cart->variant->variant_title ?? trim(($cartArch ?? '') . ' ' . ($cartSize ? 'Size ' . $cartSize : ''))) : null;

        return response()->json([
            'status' => true,
            'message' => 'Product added to cart successfully',
            'data' => [
                'cart_id' => $cart->id,
                'product_id' => $cart->product_id,
                'variant_id' => $cart->variant_id,
                'quantity' => $cart->quantity,
                'arch' => $cartArch,
                'size' => $cartSize,
                'selected_arch' => $cartArch,
                'selected_size' => $cartSize,
                'variant_title' => $cartVariantTitle,
                'sku' => $cart->variant?->sku ?: ($cart->product->sku ?? null),
                'price' => (float) $unitPrice,
                'unit_price' => (float) $unitPrice,
                'selling_price' => (float) $unitPrice,
                'original_price' => (float) $originalPrice,
                'line_total' => (float) round($unitPrice * $cart->quantity, 2),
                'product_name' => $cart->product->name ?? null,
                'variant_name' => $cart->variant ? $cart->variant->sku : null,
                'available_stock' => $availableStock,
                'in_stock' => $availableStock > 0
            ],
            'session_id' => $sessionId
        ])->cookie('guest_session_id', $sessionId, 60 * 24 * 30, '/', null, false, false);
    }

    public function getCart(Request $request)
    {
        try {
            $user = $this->resolveOptionalApiUser($request);
            $sessionId = $this->resolveOptionalSessionId($request, $user);

            $cartQuery = Cart::with(['product', 'variant.attributes.attribute', 'variant.attributes.attributeValue']);

            if ($user && $sessionId) {
                // Claim guest cart items for logged-in user
                Cart::whereNull('user_id')
                    ->where('session_id', $sessionId)
                    ->update(['user_id' => $user->id]);

                $cartQuery->where(function ($q) use ($user, $sessionId) {
                    $q->where('user_id', $user->id)
                        ->orWhere('session_id', $sessionId);
                });
            } elseif ($user) {
                $cartQuery->where('user_id', $user->id);
            } elseif ($sessionId) {
                $cartQuery->where('session_id', $sessionId);
            } else {
                // Logged-out user with no session => empty cart
                $cartQuery->whereRaw('1 = 0');
            }

            $rawCartItems = $cartQuery->orderBy('id', 'desc')->get();

            // Filter out items whose product was removed from database
            $cartItems = $rawCartItems->filter(fn($item) => $item->product !== null)->values();

            // Touch cart items so database reflects that the user actively viewed Cart
            if ($cartItems->isNotEmpty()) {
                Cart::whereIn('id', $cartItems->pluck('id'))->update(['updated_at' => now()]);
            }

            // Record active cart flow for checkout
            if ($user) {
                \Illuminate\Support\Facades\Cache::put('checkout_active_flow_u_' . $user->id, 'cart', now()->addDays(7));
                \Illuminate\Support\Facades\Cache::put('cart_viewed_at_u_' . $user->id, now()->timestamp, now()->addDays(7));
            }
            if ($sessionId) {
                \Illuminate\Support\Facades\Cache::put('checkout_active_flow_s_' . $sessionId, 'cart', now()->addDays(7));
                \Illuminate\Support\Facades\Cache::put('cart_viewed_at_s_' . $sessionId, now()->timestamp, now()->addDays(7));
            }

            /*
            |--------------------------------------------------------------------------
            | ADDRESS
            |--------------------------------------------------------------------------
            */
            $selectedAddress = null;

            $addressId = $cartItems->first()?->address_id ?? null;

            if ($addressId) {
                $selectedAddress = Address::where('id', $addressId)->first();
            } elseif ($user) {
                $selectedAddress = Address::where('user_id', $user->id)->latest()->first();
            }

            $allSimilarProducts = collect();

            $cartItems = $cartItems->map(function ($item) use (&$allSimilarProducts) {
                $item->quantity = (int) $item->quantity;

                $unitPrice = 0;
                $originalPrice = 0;

                if ($item->variant) {
                    $vSelling = (float) ($item->variant->selling_price ?? 0);
                    $vPrice = (float) ($item->variant->price ?? 0);
                    $pSelling = (float) ($item->product->selling_price ?? 0);
                    $pPrice = (float) ($item->product->price ?? 0);

                    // Active unit price to charge
                    if ($vSelling > 0) {
                        $unitPrice = $vSelling;
                    } elseif ($vPrice > 0) {
                        $unitPrice = $vPrice;
                    } elseif ($pSelling > 0) {
                        $unitPrice = $pSelling;
                    } elseif ($pPrice > 0) {
                        $unitPrice = $pPrice;
                    }

                    // Original MRP
                    if ($vPrice > 0) {
                        $originalPrice = $vPrice;
                    } elseif ($pPrice > 0) {
                        $originalPrice = $pPrice;
                    } else {
                        $originalPrice = $unitPrice;
                    }

                    $arch = $item->variant->arch;
                    $size = $item->variant->size !== null && $item->variant->size !== '' ? (string) $item->variant->size : null;
                    $sku = $item->variant->sku;

                    if ((!$arch || !$size) && $item->variant->relationLoaded('attributes')) {
                        foreach ($item->variant->attributes as $attrRel) {
                            $attrName = strtolower($attrRel->attribute?->name ?? '');
                            $attrVal = $attrRel->attributeValue?->value ?? '';
                            if ($attrName === 'arch' && !$arch) $arch = $attrVal;
                            if ($attrName === 'size' && !$size) $size = (string) $attrVal;
                        }
                    }

                    if ((!$arch || !$size) && $sku) {
                        $skuUpper = strtoupper($sku);
                        if (!$arch) {
                            if (str_contains($skuUpper, '-LL-') || str_ends_with($skuUpper, '-LL')) $arch = 'Lower Left';
                            elseif (str_contains($skuUpper, '-LR-') || str_ends_with($skuUpper, '-LR')) $arch = 'Lower Right';
                            elseif (str_contains($skuUpper, '-UL-') || str_ends_with($skuUpper, '-UL')) $arch = 'Upper Left';
                            elseif (str_contains($skuUpper, '-UR-') || str_ends_with($skuUpper, '-UR')) $arch = 'Upper Right';
                        }
                        if (!$size && preg_match('/-(?:LL|LR|UL|UR)-([0-9]+)/', $skuUpper, $matches)) {
                            $size = $matches[1];
                        }
                    }

                    $variantTitle = null;
                    if ($arch || $size) {
                        $parts = array_filter([$arch, $size ? 'Size ' . $size : null]);
                        $variantTitle = implode(' - ', $parts);
                    } else {
                        $variantTitle = $item->variant->variant_title ?? trim(($item->variant->arch ?? '') . ' ' . ($item->variant->size ? 'Size ' . $item->variant->size : ''));
                    }

                    $item->variant_title = $variantTitle;
                    $item->arch = $arch;
                    $item->size = $size;
                    $item->selected_arch = $arch;
                    $item->selected_size = $size;
                    $item->sku = $sku ?: ($item->product->sku ?? null);

                    $item->setAttribute('arch', $arch);
                    $item->setAttribute('size', $size);
                    $item->setAttribute('selected_arch', $arch);
                    $item->setAttribute('selected_size', $size);
                    $item->setAttribute('variant_title', $variantTitle);
                    $item->setAttribute('sku', $sku ?: ($item->product->sku ?? null));

                    $item->variant->arch = $arch;
                    $item->variant->size = $size;
                    $item->variant->selected_arch = $arch;
                    $item->variant->selected_size = $size;
                    $item->variant->variant_title = $variantTitle;
                    $item->variant->setAttribute('arch', $arch);
                    $item->variant->setAttribute('size', $size);
                    $item->variant->setAttribute('selected_arch', $arch);
                    $item->variant->setAttribute('selected_size', $size);
                    $item->variant->setAttribute('variant_title', $variantTitle);

                    // Normalize variant prices so frontend doesn't see 0
                    if (($item->variant->selling_price ?? 0) <= 0 && $unitPrice > 0) {
                        $item->variant->selling_price = (float) $unitPrice;
                    }
                    if (($item->variant->price ?? 0) <= 0 && $originalPrice > 0) {
                        $item->variant->price = (float) $originalPrice;
                    }
                } elseif ($item->product) {
                    $item->arch = null;
                    $item->size = null;
                    $item->selected_arch = null;
                    $item->selected_size = null;
                    $item->variant_title = null;
                    $item->sku = $item->product->sku ?? null;
                    $item->setAttribute('arch', null);
                    $item->setAttribute('size', null);
                    $item->setAttribute('selected_arch', null);
                    $item->setAttribute('selected_size', null);
                    $item->setAttribute('variant_title', null);
                    $item->setAttribute('sku', $item->product->sku ?? null);
                    $pSelling = (float) ($item->product->selling_price ?? 0);
                    $pPrice = (float) ($item->product->price ?? 0);

                    // Active unit price to charge
                    if ($pSelling > 0) {
                        $unitPrice = $pSelling;
                    } elseif ($pPrice > 0) {
                        $unitPrice = $pPrice;
                    }

                    // Original MRP
                    if ($pPrice > 0) {
                        $originalPrice = $pPrice;
                    } else {
                        $originalPrice = $unitPrice;
                    }
                }

                if ($originalPrice < $unitPrice) {
                    $originalPrice = $unitPrice;
                }

                $discountPercent = ($originalPrice > 0 && $originalPrice > $unitPrice)
                    ? (int) round((($originalPrice - $unitPrice) / $originalPrice) * 100)
                    : 0;

                // Explicitly attach prices to the cart item
                $item->price = (float) $unitPrice;
                $item->unit_price = (float) $unitPrice;
                $item->selling_price = (float) $unitPrice;
                $item->original_price = (float) $originalPrice;
                $item->discount = $discountPercent;
                $item->line_total = (float) round($unitPrice * $item->quantity, 2);

                if ($item->product) {
                    $item->product->arch = $item->arch;
                    $item->product->size = $item->size;
                    $item->product->selected_arch = $item->selected_arch;
                    $item->product->selected_size = $item->selected_size;
                    $item->product->variant_title = $item->variant_title;
                    $item->product->setAttribute('arch', $item->arch);
                    $item->product->setAttribute('size', $item->size);
                    $item->product->setAttribute('selected_arch', $item->selected_arch);
                    $item->product->setAttribute('selected_size', $item->selected_size);
                    $item->product->setAttribute('variant_title', $item->variant_title);

                    // Override product prices with the actual cart item pricing
                    $item->product->selling_price = (float) $unitPrice;
                    $item->product->price = (float) $originalPrice;
                    $item->product->discount = $discountPercent;

                    if ($item->product->thumbnail) {
                        $item->product->thumbnail = url(
                            'backend/assets/images/product/' . $item->product->thumbnail
                        );
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Product Gallery
                    |--------------------------------------------------------------------------
                    */
                    $galleryImages = [];

                    if (!empty($item->product->gallery)) {
                        $gallery = is_string($item->product->gallery)
                            ? json_decode($item->product->gallery, true)
                            : $item->product->gallery;

                        if (is_array($gallery)) {
                            $galleryImages = collect($gallery)
                                ->map(function ($image) {
                                    return url('backend/assets/images/product/' . $image);
                                })
                                ->values()
                                ->toArray();
                        }
                    }

                    $item->product->gallery = $galleryImages;

                    $similarProducts = Product::where('category_id', $item->product->category_id)
                        ->where('id', '!=', $item->product->id)
                        ->where('status', 1)
                        ->latest()
                        ->take(5)
                        ->get()
                        ->map(function ($p) {
                            $galleryImages = [];

                            if (!empty($p->gallery)) {
                                $gallery = is_string($p->gallery)
                                    ? json_decode($p->gallery, true)
                                    : $p->gallery;

                                if (is_array($gallery)) {
                                    $galleryImages = collect($gallery)
                                        ->map(function ($image) {
                                            return url('backend/assets/images/product/' . $image);
                                        })
                                        ->values()
                                        ->toArray();
                                }
                            }

                            return [
                                'id' => $p->id,
                                'category_id' => $p->category_id,
                                'subcategory_id' => $p->subcategory_id,
                                'name' => $p->name,
                                'slug' => $p->slug,
                                'thumbnail' => $p->thumbnail
                                    ? url('backend/assets/images/product/' . $p->thumbnail)
                                    : null,
                                'gallery' => $galleryImages,
                                'price' => (float) $p->price,
                                'selling_price' => (float) $p->selling_price,
                                'is_trending' => (bool) $p->is_trending,
                                'is_featured' => (bool) $p->is_featured,
                                'is_best_selling' => (bool) $p->is_best_selling,
                                'is_top_rated' => (bool) $p->is_top_rated,
                                'is_new_arrival' => (bool) $p->is_new_arrival,
                                'quantity' => $p->quantity,
                                'in_stock' => $p->quantity > 0,
                                'stock_status' => $p->quantity > 0 ? 'In Stock' : 'Out Of Stock',
                            ];
                        });

                    $allSimilarProducts = $allSimilarProducts->merge($similarProducts);
                }

                return $item;
            });

            /*
            |--------------------------------------------------------------------------
            | SHOW TRENDING PRODUCTS IF CART IS EMPTY
            |--------------------------------------------------------------------------
            */
            if ($cartItems->isEmpty()) {
                $allSimilarProducts = Product::where('status', 1)
                    ->latest()
                    ->take(10)
                    ->get()
                    ->map(function ($p) {
                        $galleryImages = [];

                        if (!empty($p->gallery)) {
                            $gallery = is_string($p->gallery)
                                ? json_decode($p->gallery, true)
                                : $p->gallery;

                            if (is_array($gallery)) {
                                $galleryImages = collect($gallery)
                                    ->map(function ($image) {
                                        return url('backend/assets/images/product/' . $image);
                                    })
                                    ->values()
                                    ->toArray();
                            }
                        }

                        return [
                            'id' => $p->id,
                            'category_id' => $p->category_id,
                            'subcategory_id' => $p->subcategory_id,
                            'name' => $p->name,
                            'slug' => $p->slug,
                            'thumbnail' => $p->thumbnail
                                ? url('backend/assets/images/product/' . $p->thumbnail)
                                : null,
                            'gallery' => $galleryImages,
                            'price' => (float) $p->price,
                            'selling_price' => (float) $p->selling_price,
                            'is_trending' => (bool) $p->is_trending,
                            'is_featured' => (bool) $p->is_featured,
                            'is_best_selling' => (bool) $p->is_best_selling,
                            'is_top_rated' => (bool) $p->is_top_rated,
                            'is_new_arrival' => (bool) $p->is_new_arrival,
                            'quantity' => $p->quantity,
                            'in_stock' => $p->quantity > 0,
                            'stock_status' => $p->quantity > 0 ? 'In Stock' : 'Out Of Stock',
                        ];
                    });
            }

            /*
            |--------------------------------------------------------------------------
            | SUBTOTAL (SUM OF ALL PRODUCTS IN CART)
            |--------------------------------------------------------------------------
            */
            $subtotal = round($cartItems->sum('line_total'), 2);

            /*
            |--------------------------------------------------------------------------
            | SHIPPING
            |--------------------------------------------------------------------------
            */
            $shippingCharge = (float) $this->calculateShippingCharge($subtotal);

            /*
            |--------------------------------------------------------------------------
            | COUPON
            |--------------------------------------------------------------------------
            */
            $couponCode = $request->input('coupon_code')
                ?: $request->header('coupon-code')
                ?: $request->header('coupon_code')
                ?: $request->query('coupon_code')
                ?: $cartItems->first(fn($i) => !empty($i->coupon_code))?->coupon_code;

            // Check database if not found on items
            if (!$couponCode && $user) {
                $couponCode = Cart::where('user_id', $user->id)->whereNotNull('coupon_code')->where('coupon_code', '!=', '')->latest()->value('coupon_code');
            }
            if (!$couponCode && $sessionId) {
                $couponCode = Cart::where('session_id', $sessionId)->whereNotNull('coupon_code')->where('coupon_code', '!=', '')->latest()->value('coupon_code');
            }

            $discountAmount = 0;
            $couponData = null;

            if ($couponCode && $subtotal > 0) {
                $coupon = Coupon::where('code', $couponCode)->where('status', 1)->first();
                if ($coupon) {
                    $couponData = $this->validateAndCalculateCoupon($coupon, (float) $subtotal);
                    if ($couponData) {
                        $discountAmount = (float) ($couponData['discount_amount'] ?? 0);

                        // Persist to Cart so subsequent requests keep the coupon
                        if ($user) {
                            Cart::where('user_id', $user->id)->update(['coupon_code' => $coupon->code]);
                        } elseif ($sessionId) {
                            Cart::where('session_id', $sessionId)->update(['coupon_code' => $coupon->code]);
                        }

                        // Attach coupon_code to each cart item
                        foreach ($cartItems as $ci) {
                            $ci->coupon_code = $coupon->code;
                        }
                    }
                }
            }

            /*
            |--------------------------------------------------------------------------
            | GRAND TOTAL
            |--------------------------------------------------------------------------
            */
            $taxable = max(0, round($subtotal - $discountAmount, 2));
            $gstPercentage = 0;
            $gstAmount = round(($taxable * $gstPercentage) / 100, 2);
            $grandTotal = round($taxable + $gstAmount + $shippingCharge, 2);

            return response()->json([
                'status' => true,
                'message' => 'Cart fetched successfully',
                'session_id' => $sessionId,
                'selected_address' => $selectedAddress,
                'coupon_code' => $couponData['code'] ?? null,
                'coupon' => $couponData,
                'data' => $cartItems->values(),
                'trending_products' => $allSimilarProducts->unique('id')->values(),
                'cart_summary' => [
                    'subtotal' => round($subtotal, 2),
                    'discount_amount' => round($discountAmount, 2),
                    'amount_after_discount' => round($taxable, 2),
                    'gst_percentage' => $gstPercentage,
                    'gst_amount' => round($gstAmount, 2),
                    'shipping_charge' => round($shippingCharge, 2),
                    'grand_total' => round($grandTotal, 2),
                ]
            ])->cookie('guest_session_id', $sessionId, 60 * 24 * 30, '/', null, false, false);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function updateCart(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'cart_id'       => 'nullable',
            'id'            => 'nullable',
            'item_id'       => 'nullable',
            'buy_now_id'    => 'nullable',
            'product_id'    => 'nullable',
            'variant_id'    => 'nullable',
            'quantity'      => 'nullable|integer|min:0',
            'source'        => 'nullable|in:cart,buy_now',
            'is_buy_now'    => 'nullable|boolean',
            'address_id'    => 'nullable|integer|min:1',
            'coupon_code'   => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $this->resolveOptionalApiUser($request);
        $sessionId = $this->resolveOptionalSessionId($request, $user);

        $itemId = $request->input('cart_id')
            ?? $request->input('id')
            ?? $request->input('item_id')
            ?? $request->input('buy_now_id');

        $quantity = $request->has('quantity') ? (int) $request->input('quantity') : null;
        $isBuyNow = $request->input('source') === 'buy_now'
            || $request->boolean('is_buy_now')
            || $request->filled('buy_now_id');

        // Auto-claim guest cart and buy now items when user is logged in
        if ($user) {
            if ($sessionId) {
                Cart::whereNull('user_id')
                    ->where('session_id', $sessionId)
                    ->update(['user_id' => $user->id]);

                BuyNow::whereNull('user_id')
                    ->where('session_id', $sessionId)
                    ->update(['user_id' => $user->id]);
            }
            if ($itemId) {
                Cart::where('id', $itemId)->whereNull('user_id')->update(['user_id' => $user->id]);
                BuyNow::where('id', $itemId)->whereNull('user_id')->update(['user_id' => $user->id]);
            }
        }

        // 1. UPDATE COUPON
        if ($request->has('coupon_code')) {
            $couponCodeVal = !empty($request->coupon_code) ? $request->coupon_code : null;

            if ($isBuyNow) {
                if ($user) BuyNow::where('user_id', $user->id)->update(['coupon_code' => $couponCodeVal]);
                if ($sessionId) BuyNow::where('session_id', $sessionId)->update(['coupon_code' => $couponCodeVal]);
                if ($itemId) BuyNow::where('id', $itemId)->update(['coupon_code' => $couponCodeVal]);
                if (!$user && !$sessionId && !$itemId) BuyNow::query()->update(['coupon_code' => $couponCodeVal]);
            } else {
                if ($user) Cart::where('user_id', $user->id)->update(['coupon_code' => $couponCodeVal]);
                if ($sessionId) Cart::where('session_id', $sessionId)->update(['coupon_code' => $couponCodeVal]);
                if ($itemId) Cart::where('id', $itemId)->update(['coupon_code' => $couponCodeVal]);
                if (!$user && !$sessionId && !$itemId) Cart::query()->update(['coupon_code' => $couponCodeVal]);
            }
        }

        // 2. UPDATE ADDRESS
        if ($request->filled('address_id')) {
            $addressIdVal = $request->address_id;

            if ($isBuyNow) {
                if ($user) BuyNow::where('user_id', $user->id)->update(['address_id' => $addressIdVal]);
                if ($sessionId) BuyNow::where('session_id', $sessionId)->update(['address_id' => $addressIdVal]);
                if ($itemId) BuyNow::where('id', $itemId)->update(['address_id' => $addressIdVal]);
                if (!$user && !$sessionId && !$itemId) BuyNow::query()->update(['address_id' => $addressIdVal]);
            } else {
                if ($user) Cart::where('user_id', $user->id)->update(['address_id' => $addressIdVal]);
                if ($sessionId) Cart::where('session_id', $sessionId)->update(['address_id' => $addressIdVal]);
                if ($itemId) Cart::where('id', $itemId)->update(['address_id' => $addressIdVal]);
                if (!$user && !$sessionId && !$itemId) Cart::query()->update(['address_id' => $addressIdVal]);
            }
        }

        // 3. UPDATE ITEM QUANTITY (if provided)
        if ($quantity !== null) {
            $item = null;
            $isBuyNowItem = false;

            // Priority A: If explicitly BuyNow
            if ($isBuyNow) {
                if ($itemId) {
                    $item = BuyNow::find($itemId);
                }
                if (!$item && ($user || $sessionId)) {
                    $item = BuyNow::where(function ($q) use ($user, $sessionId) {
                        if ($user) $q->where('user_id', $user->id);
                        if ($sessionId) $user ? $q->orWhere('session_id', $sessionId) : $q->where('session_id', $sessionId);
                    })->latest()->first();
                }
                if ($item) {
                    $isBuyNowItem = true;
                }
            }

            // Priority B: Search in Cart by item ID
            if (!$item && $itemId) {
                $item = Cart::find($itemId);
                if (!$item) {
                    $item = BuyNow::find($itemId);
                    if ($item) {
                        $isBuyNowItem = true;
                    }
                }
            }

            // Priority C: Search by product_id and variant_id
            if (!$item && $request->filled('product_id')) {
                $item = Cart::where('product_id', $request->product_id)
                    ->when($request->filled('variant_id'), fn($q) => $q->where('variant_id', $request->variant_id))
                    ->when($user, fn($q) => $q->where('user_id', $user->id))
                    ->when(!$user && $sessionId, fn($q) => $q->where('session_id', $sessionId))
                    ->first();

                if (!$item) {
                    $item = Cart::where('product_id', $request->product_id)
                        ->when($request->filled('variant_id'), fn($q) => $q->where('variant_id', $request->variant_id))
                        ->first();
                }
            }

            // Priority D: Fallback to user/session latest cart item or overall latest item
            if (!$item) {
                if ($user) {
                    $item = Cart::where('user_id', $user->id)->latest()->first();
                } elseif ($sessionId) {
                    $item = Cart::where('session_id', $sessionId)->latest()->first();
                }
                if (!$item) {
                    $item = Cart::latest()->first();
                }
                if (!$item) {
                    $item = BuyNow::latest()->first();
                    if ($item) {
                        $isBuyNowItem = true;
                    }
                }
            }

            if (!$item) {
                return response()->json([
                    'status' => false,
                    'message' => 'Cart item not found.',
                    'cart_id' => $itemId ? (int) $itemId : null,
                ], 404);
            }

            // If quantity is 0, delete the item
            if ($quantity === 0) {
                $removedId = $item->id;
                $item->delete();

                return response()->json([
                    'status' => true,
                    'message' => $isBuyNowItem ? 'Buy now item removed successfully.' : 'Cart item removed successfully.',
                    'id' => (int) $removedId,
                    'cart_id' => (int) $removedId,
                    'buy_now_id' => $isBuyNowItem ? (int) $removedId : null,
                ]);
            }

            // Stock check
            $availableStock = $this->getProductAvailableStock($item->product_id, $item->variant_id);
            if ($quantity > $availableStock) {
                return response()->json([
                    'status' => false,
                    'message' => 'Not enough stock available. Available: ' . $availableStock,
                    'available_stock' => $availableStock,
                    'requested_quantity' => $quantity,
                ], 422);
            }

            // Update item quantity
            $item->quantity = $quantity;
            if ($request->filled('address_id')) {
                $item->address_id = $request->address_id;
            }
            $item->save();
            $item->load(['product', 'variant']);

            return response()->json([
                'status' => true,
                'message' => $isBuyNowItem ? 'Buy now item updated successfully.' : 'Cart item updated successfully.',
                'data' => [
                    'id' => $item->id,
                    'cart_id' => $item->id,
                    'buy_now_id' => $isBuyNowItem ? $item->id : null,
                    'product_id' => $item->product_id,
                    'variant_id' => $item->variant_id,
                    'quantity' => (int) $item->quantity,
                    'available_stock' => $availableStock,
                    'in_stock' => $availableStock > 0,
                    'product_name' => $item->product->name ?? null,
                    'variant_name' => $item->variant->sku ?? null,
                    'address_id' => $item->address_id ?? null,
                ],
            ]);
        }

        // General update (address or coupon only)
        return response()->json([
            'status' => true,
            'message' => 'Cart updated successfully.',
            'address_id' => $request->address_id ?? null,
            'coupon_code' => $request->coupon_code ?? null,
        ]);
    }
    public function applyCouponToCart(Request $request)
    {
        $request->merge(['source' => 'cart']);
        return $this->applyCoupon($request);
    }

    public function clearCart(Request $request)
    {
        $user = $this->resolveOptionalApiUser($request);
        $sessionId = $this->resolveOptionalSessionId($request, $user);

        if (!$user && !$sessionId) {
            return response()->json([
                'status' => true,
                'message' => 'Cart is already empty',
                'cart_count' => 0
            ]);
        }

        if ($user) {
            Cart::where('user_id', $user->id)->delete();
        }

        if ($sessionId) {
            Cart::where('session_id', $sessionId)->delete();
        }

        return response()->json([
            'status' => true,
            'message' => 'Cart cleared successfully',
            'cart_count' => 0
        ]);
    }

    public function cartCount(Request $request)
    {
        $user = $this->resolveOptionalApiUser($request);
        $sessionId = $this->resolveOptionalSessionId($request, $user);

        if (!$user && !$sessionId) {
            return response()->json([
                'status' => true,
                'message' => 'Cart count fetched successfully',
                'count' => 0
            ], 200);
        }

        $count = Cart::when($user, function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })
            ->when(!$user, function ($q) use ($sessionId) {
                $q->where('session_id', $sessionId);
            })
            ->sum('quantity');

        return response()->json([
            'status' => true,
            'message' => 'Cart count fetched successfully',
            'count' => (int) $count
        ]);
    }

    public function removeCartItem(Request $request)
    {
        $cartId = $request->input('cart_id') ?? $request->input('id');
        $productId = $request->input('product_id');
        $variantId = $request->input('variant_id');

        if (!$cartId && !$productId) {
            return response()->json([
                'status' => false,
                'message' => 'cart_id or product_id is required',
            ], 422);
        }

        $user = $this->resolveOptionalApiUser($request);
        $sessionId = $this->resolveOptionalSessionId($request, $user);

        $cart = null;

        // 1. First attempt: Find by cart row primary key
        if ($cartId) {
            // Check direct match with ownership (user or session)
            $cart = Cart::where('id', $cartId)
                ->where(function ($q) use ($user, $sessionId) {
                    if ($user && $sessionId) {
                        $q->where('user_id', $user->id)
                            ->orWhere('session_id', $sessionId)
                            ->orWhereNull('user_id');
                    } elseif ($user) {
                        $q->where('user_id', $user->id)
                            ->orWhereNull('user_id');
                    } elseif ($sessionId) {
                        $q->where('session_id', $sessionId)
                            ->orWhereNull('session_id');
                    }
                })
                ->first();

            // Direct ID fallback: if item exists and does NOT belong to a different authenticated user
            if (!$cart) {
                $candidate = Cart::find($cartId);
                if ($candidate) {
                    if ($user && $candidate->user_id && $candidate->user_id != $user->id) {
                        // Belongs to another user, forbidden
                        $candidate = null;
                    }
                    $cart = $candidate;
                }
            }
        }

        // 2. Second attempt: Maybe the passed cart_id was actually a product_id or product_id was sent
        if (!$cart) {
            $targetProductId = $productId ?: $cartId;
            $prodQuery = Cart::where('product_id', $targetProductId);

            if ($variantId) {
                $prodQuery->where('variant_id', $variantId);
            }

            if ($user && $sessionId) {
                $cart = (clone $prodQuery)->where(function ($q) use ($user, $sessionId) {
                    $q->where('user_id', $user->id)
                        ->orWhere('session_id', $sessionId);
                })->first();
            } elseif ($user) {
                $cart = (clone $prodQuery)->where('user_id', $user->id)->first()
                    ?: (clone $prodQuery)->whereNull('user_id')->first();
            } elseif ($sessionId) {
                $cart = (clone $prodQuery)->where('session_id', $sessionId)->first();
            }

            // Fallback: match any cart item with that product if still not found
            if (!$cart && ($user || $sessionId)) {
                $cart = $prodQuery->first();
            }
        }

        if (!$cart) {
            return response()->json([
                'status' => false,
                'message' => 'Cart item not found',
            ], 404);
        }

        $cart->delete();

        // Calculate updated cart count
        $remainingCount = 0;
        if ($user) {
            $remainingCount = Cart::where('user_id', $user->id)->sum('quantity');
        } elseif ($sessionId) {
            $remainingCount = Cart::where('session_id', $sessionId)->sum('quantity');
        }

        return response()->json([
            'status' => true,
            'message' => 'Cart item removed successfully',
            'cart_count' => (int) $remainingCount
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Add to Wishlist
    |--------------------------------------------------------------------------
    */
    public function addToWishlist(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id'    => 'required|exists:users,id',
            'product_id' => 'required|exists:products,id',
            'variant_id' => 'nullable|integer|exists:product_variants,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        // ✅ Sanctum user
        $authUser = auth('sanctum')->user();

        if (!$authUser) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized user',
            ], 401);
        }

        // ❗ SECURITY CHECK (VERY IMPORTANT)
        if ($authUser->id != $request->user_id) {
            return response()->json([
                'status' => false,
                'message' => 'User mismatch with token',
            ], 403);
        }

        // variant validation
        if ($request->variant_id) {
            $variantValid = ProductVariant::where('id', $request->variant_id)
                ->where('product_id', $request->product_id)
                ->exists();

            if (!$variantValid) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid variant for this product',
                ], 422);
            }
        }

        // prevent duplicate
        $exists = Wishlist::where('user_id', $request->user_id)
            ->where('product_id', $request->product_id)
            ->where(function ($q) use ($request) {
                if ($request->variant_id) {
                    $q->where('variant_id', $request->variant_id);
                } else {
                    $q->whereNull('variant_id');
                }
            })
            ->exists();

        if ($exists) {
            return response()->json([
                'status' => false,
                'message' => 'Already in wishlist',
            ]);
        }

        $wishlist = Wishlist::create([
            'user_id'    => $request->user_id,
            'product_id' => $request->product_id,
            'variant_id' => $request->variant_id ?? null,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Added to wishlist successfully',
            'data' => $wishlist
        ]);
    }

    // public function getWishlist(Request $request)
    // {
    //     $userId = $request->user_id ?? auth('sanctum')->id();
    //     if ($userId) {
    //         $request->merge(['user_id' => $userId]);
    //     }

    //     $validator = Validator::make($request->all(), [
    //         'user_id' => 'required|exists:users,id',
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => $validator->errors()->first(),
    //         ], 422);
    //     }

    //     $wishlist = Wishlist::with(['product', 'user', 'variant'])
    //         ->where('user_id', $request->user_id)
    //         ->latest()
    //         ->get();

    //     $data = [];

    //     foreach ($wishlist as $item) {
    //         if (!$item->product) {
    //             continue;
    //         }

    //         $price = $item->variant
    //             ? ($item->variant->price ?? 0)
    //             : ($item->product->selling_price ?? $item->product->price ?? 0);

    //         $product = $item->product->toArray();

    //         // Thumbnail full URL
    //         $product['thumbnail'] = $item->product->thumbnail
    //             ? url('backend/assets/images/product/' . $item->product->thumbnail)
    //             : null;

    //         // Gallery full URL
    //         $product['gallery'] = collect($item->product->gallery)->map(function ($img) {
    //             return url('backend/assets/images/product/' . $img);
    //         })->toArray();

    //         // Gallery order full URL
    //         $product['gallery_order'] = collect($item->product->gallery_order)->map(function ($img) {
    //             return url('backend/assets/images/product/' . $img);
    //         })->toArray();

    //         $data[] = [
    //             'id' => $item->id,
    //             'user_id' => $item->user_id,
    //             'product_id' => $item->product_id,
    //             'variant_id' => $item->variant_id,
    //             'price' => $price,
    //             'variant' => $item->variant ? [
    //                 'id' => $item->variant->id,
    //                 'name' => $item->variant->name ?? null,
    //                 'price' => $item->variant->price ?? 0,
    //             ] : null,
    //             'product' => $product,
    //         ];
    //     }

    //     return response()->json([
    //         'status' => true,
    //         'message' => 'Wishlist fetched successfully',
    //         'count' => count($data),
    //         'data' => $data
    //     ]);
    // }

    public function getWishlist(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $wishlist = Wishlist::with(['product', 'user', 'variant'])
            ->where('user_id', $request->user_id)
            ->latest()
            ->get();

        $data = [];

        foreach ($wishlist as $item) {
            if (!$item->product) {
                continue;
            }

            $price = $item->variant
                ? ($item->variant->price ?? 0)
                : ($item->product->selling_price ?? $item->product->price ?? 0);

            $product = $item->product->toArray();

            // Thumbnail full URL
            $product['thumbnail'] = $item->product->thumbnail
                ? url('backend/assets/images/product/' . $item->product->thumbnail)
                : null;

            // Gallery full URL
            $product['gallery'] = collect($item->product->gallery)->map(function ($img) {
                return url('backend/assets/images/product/' . $img);
            })->toArray();

            // Gallery order full URL
            $product['gallery_order'] = collect($item->product->gallery_order)->map(function ($img) {
                return url('backend/assets/images/product/' . $img);
            })->toArray();

            $data[] = [
                'id' => $item->id,
                'user_id' => $item->user_id,
                'product_id' => $item->product_id,
                'variant_id' => $item->variant_id,
                'price' => $price,
                'variant' => $item->variant ? [
                    'id' => $item->variant->id,
                    'name' => $item->variant->name ?? null,
                    'price' => $item->variant->price ?? 0,
                ] : null,
                'product' => $product,
            ];
        }

        return response()->json([
            'status' => true,
            'message' => 'Wishlist fetched successfully',
            'count' => count($data),
            'data' => $data
        ]);
    }

    public function wishlistCount(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $count = Wishlist::where('user_id', $request->user_id)
            ->count();

        return response()->json([
            'status' => true,
            'message' => 'Wishlist count fetched successfully',
            'count' => $count
        ]);
    }

    public function clearWishlist(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        Wishlist::where('user_id', $request->user_id)->delete();

        return response()->json([
            'status' => true,
            'message' => 'Wishlist cleared successfully'
        ]);
    }

    public function removeWishlistItem(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'User not authenticated',
            ], 401);
        }

        $wishlist = Wishlist::where('user_id', $user->id)
            ->where('product_id', $request->product_id)
            ->first();

        if (!$wishlist) {
            return response()->json([
                'status' => false,
                'message' => 'Product not found in wishlist',
            ], 404);
        }

        $wishlist->delete();

        return response()->json([
            'status' => true,
            'message' => 'Wishlist item removed successfully',
        ]);
    }

    public function checkout(Request $request)
    {
        DB::beginTransaction();

        try {
            $request->validate([
                'address_id' => 'required|exists:addresses,id',
                'payment_method' => 'required|string'
            ]);

            $user = auth()->user();
            $sessionId = $request->header('session-id');

            // ===========================================
            // BUY NOW CHECKOUT (if buy_now_id is present)
            // ===========================================
            if ($request->has('buy_now_id')) {

                // Get buy now item
                $query = BuyNow::with(['product', 'variant']);

                if ($user) {
                    $query->where('user_id', $user->id);
                } elseif ($sessionId) {
                    $query->where('session_id', $sessionId);
                } else {
                    return response()->json([
                        'status' => false,
                        'message' => 'No buy now item found'
                    ], 404);
                }

                $buyNow = $query->where('id', $request->buy_now_id)->first();

                if (!$buyNow) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Buy now item not found'
                    ], 404);
                }

                // Get address
                $address = Address::find($request->address_id);
                if (!$address || ($user && $address->user_id != $user->id)) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Address not found'
                    ]);
                }

                // Check stock
                $product = $buyNow->product;
                if ($product->quantity < $buyNow->quantity) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Insufficient stock for ' . $product->name
                    ]);
                }

                // Calculate amounts
                $price = $this->getBuyNowPrice($buyNow);
                $subtotal = $price * $buyNow->quantity;

                // Coupon discount calculation
                $discountAmount = 0;

                if ($buyNow->coupon_code) {
                    $coupon = Coupon::where('code', $buyNow->coupon_code)
                        ->where('status', 1)
                        ->first();

                    if ($coupon) {
                        $couponData = $this->validateAndCalculateCoupon($coupon, (float) $subtotal);
                        if ($couponData) {
                            $discountAmount = (float) ($couponData['discount_amount'] ?? 0);
                            $coupon->increment('used_count');
                        }
                    }
                }

                // GST calculation (Applied AFTER Discount)
                $gstPercentage = 0;
                $amountAfterDiscount = $subtotal - $discountAmount;
                $gstAmount = ($amountAfterDiscount * $gstPercentage) / 100;
                $totalWithGst = $amountAfterDiscount + $gstAmount;

                $shippingCharge = $this->calculateShippingCharge($subtotal);
                $grandTotal = $totalWithGst + $shippingCharge;

                // Create order with discount
                $order = Order::create([
                    'user_id' => $user->id ?? null,
                    'order_id' => Order::temporaryOrderNumber(),
                    'subtotal' => $subtotal,
                    'discount' => $discountAmount,
                    'gst_amount' => $gstAmount,
                    'shipping_charge' => $shippingCharge,
                    'grand_total' => $grandTotal,
                    'paid_amount' => 0,
                    'due_amount' => $grandTotal,
                    'payment_method' => $request->payment_method,
                    'payment_type' => 'full',
                    'payment_status' => 'pending',
                    'order_status' => 'pending',
                    'invoice_date' => now(),
                    'coupon_code' => $buyNow->coupon_code,
                ]);

                $bnVar = $buyNow->variant ?: ($buyNow->variant_id ? ProductVariant::find($buyNow->variant_id) : null);
                // Create order item
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $buyNow->product_id,
                    'variant_id' => $buyNow->variant_id,
                    'product_name' => $product->name ?? null,
                    'product_image' => $product->thumbnail ?? null,
                    'product_sku' => $bnVar?->sku ?: ($product->sku ?? null),
                    'arch' => $bnVar?->arch,
                    'size' => $bnVar?->size !== null && $bnVar?->size !== '' ? (string) $bnVar->size : null,
                    'quantity' => $buyNow->quantity,
                    'price' => $price,
                    'total' => $subtotal,
                ]);

                // Reduce product stock
                $product->decrement('quantity', $buyNow->quantity);

                // Delete buy now item (auto-clear after checkout)
                $buyNow->delete();

                $order->assignOrderNumber();

                DB::commit();

                return response()->json([
                    'status' => true,
                    'message' => 'Order placed successfully',
                    'data' => [
                        'order_id' => $order->id,
                        'order_number' => $order->display_order_id,
                        'subtotal' => round($subtotal, 2),
                        'discount_amount' => $discountAmount,
                        'gst_amount' => round($gstAmount, 2),
                        'shipping_charge' => $shippingCharge,
                        'grand_total' => round($grandTotal, 2),
                        'payment_method' => $request->payment_method,
                        'coupon_applied' => $buyNow->coupon_code
                    ]
                ]);
            }

            // ===========================================
            // REGULAR CART CHECKOUT
            // ===========================================

            // Get cart items
            $cartItems = Cart::with('product')
                ->where('user_id', $user->id)
                ->get();

            if ($cartItems->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Cart is empty'
                ]);
            }

            // Get address
            $address = Address::where('id', $request->address_id)
                ->where('user_id', $user->id)
                ->first();

            if (!$address) {
                return response()->json([
                    'status' => false,
                    'message' => 'Address not found'
                ]);
            }

            // Calculate subtotal
            $subtotal = 0;
            foreach ($cartItems as $cart) {
                $price = $cart->price ?? $cart->product->selling_price ?? $cart->product->price ?? 0;
                $subtotal += $price * $cart->quantity;
            }

            // Check for coupon on cart
            $discountAmount = 0;
            $couponCode = null;

            $firstCartItem = $cartItems->first();
            if ($firstCartItem && $firstCartItem->coupon_code) {
                $coupon = Coupon::where('code', $firstCartItem->coupon_code)
                    ->where('status', 1)
                    ->first();

                if ($coupon) {
                    $couponData = $this->validateAndCalculateCoupon($coupon, (float) $subtotal);
                    if ($couponData) {
                        $discountAmount = (float) ($couponData['discount_amount'] ?? 0);
                        $couponCode = $coupon->code;
                        $coupon->increment('used_count');
                    }
                }
            }

            // GST calculation for cart
            $gstPercentage = 0;
            $amountAfterDiscount = $subtotal - $discountAmount;
            $gstAmount = ($amountAfterDiscount * $gstPercentage) / 100;
            $totalWithGst = $amountAfterDiscount + $gstAmount;

            $shippingCharge = $this->calculateShippingCharge($subtotal);
            $grandTotal = $totalWithGst + $shippingCharge;

            // Create Order
            $order = Order::create([
                'user_id' => $user->id,
                'order_id' => Order::temporaryOrderNumber(),
                'subtotal' => $subtotal,
                'discount' => $discountAmount,
                'gst_amount' => $gstAmount,
                'shipping_charge' => $shippingCharge,
                'grand_total' => $grandTotal,
                'paid_amount' => 0,
                'due_amount' => $grandTotal,
                'payment_method' => $request->payment_method,
                'payment_type' => 'full',
                'payment_status' => 'pending',
                'order_status' => 'pending',
                'invoice_date' => now(),
                'coupon_code' => $couponCode,
            ]);

            // Save order items
            foreach ($cartItems as $cart) {
                $price = $cart->price ?? $cart->product->selling_price ?? $cart->product->price ?? 0;
                $cVar = $cart->variant ?: ($cart->variant_id ? ProductVariant::find($cart->variant_id) : null);
                $cProd = $cart->product ?: Product::find($cart->product_id);

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $cart->product_id,
                    'variant_id' => $cart->variant_id,
                    'product_name' => $cProd?->name,
                    'product_image' => $cProd?->thumbnail,
                    'product_sku' => $cVar?->sku ?: ($cProd?->sku ?? null),
                    'arch' => $cVar?->arch,
                    'size' => $cVar?->size !== null && $cVar?->size !== '' ? (string) $cVar->size : null,
                    'quantity' => $cart->quantity,
                    'price' => $price,
                    'total' => $price * $cart->quantity,
                ]);

                // Reduce product stock for cart items
                $product = Product::find($cart->product_id);
                if ($product) {
                    $product->decrement('quantity', $cart->quantity);
                }
            }

            // Clear cart
            Cart::where('user_id', $user->id)->delete();

            $order->assignOrderNumber();

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Order placed successfully',
                'data' => [
                    'order_id' => $order->id,
                    'order_number' => $order->display_order_id,
                    'subtotal' => round($subtotal, 2),
                    'discount_amount' => $discountAmount,
                    'gst_amount' => round($gstAmount, 2),
                    'shipping_charge' => $shippingCharge,
                    'grand_total' => round($grandTotal, 2),
                    'payment_method' => $request->payment_method,
                    'coupon_applied' => $couponCode
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    /**
     * Remove/clear buy now item
     */

    public function removeBuyNow(Request $request)
    {
        try {
            $buyNowId = $request->input('buy_now_id')
                ?: $request->input('id')
                ?: $request->input('buyNowId')
                ?: $request->input('cart_id')
                ?: $request->input('item_id');

            $productId = $request->input('product_id');
            $variantId = $request->input('variant_id');

            $user = $this->resolveOptionalApiUser($request);
            $sessionId = $this->resolveOptionalSessionId($request, $user);

            $userKey = $user ? 'u_' . $user->id : 's_' . $sessionId;
            $activeFlow = \Illuminate\Support\Facades\Cache::get('checkout_active_flow_' . $userKey);
            if (!$activeFlow && $user && $sessionId) {
                $activeFlow = \Illuminate\Support\Facades\Cache::get('checkout_active_flow_s_' . $sessionId);
            }

            $cartViewedAt = (int) \Illuminate\Support\Facades\Cache::get('cart_viewed_at_' . $userKey, 0);
            if ($cartViewedAt === 0 && $user && $sessionId) {
                $cartViewedAt = (int) \Illuminate\Support\Facades\Cache::get('cart_viewed_at_s_' . $sessionId, 0);
            }

            $buyNowClickedAt = (int) \Illuminate\Support\Facades\Cache::get('buy_now_clicked_at_' . $userKey, 0);
            if ($buyNowClickedAt === 0 && $user && $sessionId) {
                $buyNowClickedAt = (int) \Illuminate\Support\Facades\Cache::get('buy_now_clicked_at_s_' . $sessionId, 0);
            }

            $source = $request->input('source') ?: $request->input('type');
            $referer = (string) ($request->header('referer') ?: $request->header('Referer') ?: '');

            $isCartMode = false;
            if ($source === 'cart' || str_contains(strtolower($referer), 'my-cart') || str_contains(strtolower($referer), '/cart')) {
                $isCartMode = true;
            } elseif ($source === 'buy_now') {
                $isCartMode = false;
            } elseif ($activeFlow === 'buy_now' && ($buyNowClickedAt > 0 && $buyNowClickedAt >= $cartViewedAt)) {
                $isCartMode = false;
            } elseif ($activeFlow === 'cart' && ($cartViewedAt > 0 && $cartViewedAt >= $buyNowClickedAt)) {
                $isCartMode = true;
            } else {
                $isCartMode = false;
            }

            $deleted = false;

            if ($isCartMode) {
                // Priority to Cart table when checking out from Cart
                if ($buyNowId) {
                    $cartItem = Cart::find($buyNowId);
                    if ($cartItem) {
                        $cartItem->delete();
                        $deleted = true;
                    }
                }
                if (!$deleted && $productId) {
                    $delCart = Cart::where('product_id', $productId)
                        ->when($variantId, fn($q) => $q->where('variant_id', $variantId))
                        ->when($user, fn($q) => $q->where('user_id', $user->id))
                        ->when(!$user && $sessionId, fn($q) => $q->where('session_id', $sessionId))
                        ->delete();
                    if ($delCart > 0) {
                        $deleted = true;
                    }
                }
                if (!$deleted && $buyNowId) {
                    $delCart = Cart::where('product_id', $buyNowId)
                        ->when($user, fn($q) => $q->where('user_id', $user->id))
                        ->when(!$user && $sessionId, fn($q) => $q->where('session_id', $sessionId))
                        ->delete();
                    if ($delCart > 0) {
                        $deleted = true;
                    }
                }
                // Fallback to BuyNow
                if (!$deleted && $buyNowId) {
                    $buyNow = BuyNow::find($buyNowId);
                    if ($buyNow) {
                        $buyNow->delete();
                        $deleted = true;
                    }
                }
                if (!$deleted && $productId) {
                    $delBN = BuyNow::where('product_id', $productId)
                        ->when($variantId, fn($q) => $q->where('variant_id', $variantId))
                        ->when($user, fn($q) => $q->where('user_id', $user->id))
                        ->when(!$user && $sessionId, fn($q) => $q->where('session_id', $sessionId))
                        ->delete();
                    if ($delBN > 0) {
                        $deleted = true;
                    }
                }
            } else {
                // Priority to BuyNow table when checking out from Buy Now
                if ($buyNowId) {
                    $buyNow = BuyNow::find($buyNowId);
                    if ($buyNow) {
                        $buyNow->delete();
                        $deleted = true;
                    }
                }
                if (!$deleted && $productId) {
                    $delBN = BuyNow::where('product_id', $productId)
                        ->when($variantId, fn($q) => $q->where('variant_id', $variantId))
                        ->when($user, fn($q) => $q->where('user_id', $user->id))
                        ->when(!$user && $sessionId, fn($q) => $q->where('session_id', $sessionId))
                        ->delete();
                    if ($delBN > 0) {
                        $deleted = true;
                    }
                }
                if (!$deleted && $buyNowId) {
                    $delBN = BuyNow::where('product_id', $buyNowId)
                        ->when($user, fn($q) => $q->where('user_id', $user->id))
                        ->when(!$user && $sessionId, fn($q) => $q->where('session_id', $sessionId))
                        ->delete();
                    if ($delBN > 0) {
                        $deleted = true;
                    }
                }
                // Fallback to Cart
                if (!$deleted && $buyNowId) {
                    $cartItem = Cart::find($buyNowId);
                    if ($cartItem) {
                        $cartItem->delete();
                        $deleted = true;
                    }
                }
                if (!$deleted && $productId) {
                    $delCart = Cart::where('product_id', $productId)
                        ->when($variantId, fn($q) => $q->where('variant_id', $variantId))
                        ->when($user, fn($q) => $q->where('user_id', $user->id))
                        ->when(!$user && $sessionId, fn($q) => $q->where('session_id', $sessionId))
                        ->delete();
                    if ($delCart > 0) {
                        $deleted = true;
                    }
                }
                if (!$deleted && ($user || $sessionId)) {
                    BuyNow::where(function ($q) use ($user, $sessionId) {
                        if ($user) $q->where('user_id', $user->id);
                        if ($sessionId) $user ? $q->orWhere('session_id', $sessionId) : $q->where('session_id', $sessionId);
                    })->delete();
                    $deleted = true;
                }
            }

            return response()->json([
                'status' => true,
                'message' => 'Buy now item removed successfully',
                'buy_now_id' => $buyNowId ? (int) $buyNowId : null
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function applyCoupon(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'coupon_code' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $user = auth('sanctum')->user() ?? auth()->user();
        if (!$user && $request->has('user_id') && !empty($request->user_id)) {
            $user = \App\Models\User::find($request->user_id);
        }

        $sessionId = $request->header('session-id')
            ?: $request->header('Session-Id')
            ?: $request->header('session_id')
            ?: $request->input('session_id')
            ?: $request->input('sessionId')
            ?: $request->input('session-id');

        $couponCodeInput = trim((string) $request->coupon_code);
        $coupon = Coupon::where('code', $couponCodeInput)->first();

        if (!$coupon) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid coupon code',
            ], 404);
        }

        if ((int) $coupon->status !== 1) {
            return response()->json([
                'status' => false,
                'message' => 'This coupon is currently inactive',
            ], 422);
        }

        $subtotal = 0;
        $buyNow = null;
        $cartItems = collect();
        $source = $request->input('source'); // 'cart' or 'buy_now'

        // ==========================================
        // FETCH CART & BUY NOW ITEMS
        // ==========================================
        $cartQuery = Cart::with(['product', 'variant']);
        $buyNowQuery = BuyNow::with(['product', 'variant']);

        if ($user) {
            $cartQuery->where('user_id', $user->id);
            $buyNowQuery->where('user_id', $user->id);
        } elseif ($sessionId) {
            $cartQuery->where('session_id', $sessionId);
            $buyNowQuery->where('session_id', $sessionId);
        }

        $cartItems = ($user || $sessionId) ? $cartQuery->get() : collect();
        $buyNowItems = ($user || $sessionId) ? $buyNowQuery->get() : collect();

        // Calculate subtotals
        $cartSubtotal = 0;
        foreach ($cartItems as $c) {
            $unitPrice = (float) ($c->variant->selling_price ?? $c->variant->price ?? $c->product->selling_price ?? $c->product->price ?? 0);
            $cartSubtotal += ($unitPrice * (int) $c->quantity);
        }

        $buyNowSubtotal = 0;
        foreach ($buyNowItems as $b) {
            $unitPrice = (float) ($b->variant->selling_price ?? $b->variant->price ?? $b->product->selling_price ?? $b->product->price ?? 0);
            $buyNowSubtotal += ($unitPrice * (int) $b->quantity);
        }

        // Determine effective subtotal & primary source
        if ($source === 'buy_now') {
            $subtotal = $buyNowSubtotal > 0 ? $buyNowSubtotal : $cartSubtotal;
        } elseif ($source === 'cart') {
            $subtotal = $cartSubtotal > 0 ? $cartSubtotal : $buyNowSubtotal;
        } else {
            if ($cartSubtotal > 0) {
                $subtotal = $cartSubtotal;
                $source = 'cart';
            } elseif ($buyNowSubtotal > 0) {
                $subtotal = $buyNowSubtotal;
                $source = 'buy_now';
            } else {
                $subtotal = 0;
            }
        }

        if ($subtotal <= 0) {
            return response()->json([
                'status' => false,
                'message' => 'No active items in cart or buy now to apply coupon.',
            ], 422);
        }

        // ==========================================
        // COUPON VALIDATIONS
        // ==========================================
        $today = now()->toDateString();
        $startDate = $coupon->start_date ? \Illuminate\Support\Carbon::parse($coupon->start_date)->toDateString() : null;
        $endDate = $coupon->end_date ? \Illuminate\Support\Carbon::parse($coupon->end_date)->toDateString() : null;

        if ($startDate && $today < $startDate) {
            return response()->json([
                'status' => false,
                'message' => 'Coupon has not started yet',
            ], 422);
        }

        if ($endDate && $today > $endDate) {
            return response()->json([
                'status' => false,
                'message' => 'Coupon has expired',
            ], 422);
        }

        if (!empty($coupon->usage_limit) && $coupon->used_count >= $coupon->usage_limit) {
            return response()->json([
                'status' => false,
                'message' => 'Coupon usage limit exceeded',
            ], 422);
        }

        if (!empty($coupon->minimum_amount) && $subtotal < (float) $coupon->minimum_amount) {
            return response()->json([
                'status' => false,
                'message' => 'Minimum order amount of ₹' . $coupon->minimum_amount . ' required.',
            ], 422);
        }

        // ==========================================
        // DISCOUNT CALCULATION
        // ==========================================
        $discount = 0;
        if ($coupon->type === 'percent') {
            $discount = ($subtotal * (float) $coupon->value) / 100;
            if (!empty($coupon->maximum_discount) && $discount > (float) $coupon->maximum_discount) {
                $discount = (float) $coupon->maximum_discount;
            }
        } else {
            $discount = min((float) $coupon->value, $subtotal);
        }
        $discount = round((float) $discount, 2);

        // ==========================================
        // SAVE COUPON ACCORDING TO SOURCE
        // ==========================================
        if ($source === 'buy_now') {
            if ($user) BuyNow::where('user_id', $user->id)->update(['coupon_code' => $coupon->code]);
            if ($sessionId) BuyNow::where('session_id', $sessionId)->update(['coupon_code' => $coupon->code]);
        } else {
            if ($user) Cart::where('user_id', $user->id)->update(['coupon_code' => $coupon->code]);
            if ($sessionId) Cart::where('session_id', $sessionId)->update(['coupon_code' => $coupon->code]);
        }

        // ==========================================
        // TOTALS & RESPONSE
        // ==========================================
        $amountAfterDiscount = max(0, round($subtotal - $discount, 2));
        $gstPercentage = 0;
        $gstAmount = round(($amountAfterDiscount * $gstPercentage) / 100, 2);
        $shippingCharge = (float) $this->calculateShippingCharge($subtotal);
        $grandTotal = round($amountAfterDiscount + $gstAmount + $shippingCharge, 2);

        $couponData = $this->formatCouponForApi($coupon, [
            'discount_amount' => $discount,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Coupon applied successfully',
            'applied_on' => $source ?: 'cart',
            'coupon_code' => $coupon->code,
            'coupon' => $couponData,
            'summary' => [
                'subtotal' => round($subtotal, 2),
                'discount_amount' => $discount,
                'amount_after_discount' => round($amountAfterDiscount, 2),
                'gst_amount' => round($gstAmount, 2),
                'shipping_charge' => $shippingCharge,
                'grand_total' => round($grandTotal, 2),
            ]
        ]);
    }

    public function availableCoupons()
    {
        $today = now()->toDateString();

        $coupons = Coupon::where('status', 1)
            ->where(function ($query) use ($today) {
                $query->whereNull('start_date')
                    ->orWhereDate('start_date', '<=', $today);
            })
            ->where(function ($query) use ($today) {
                $query->whereNull('end_date')
                    ->orWhereDate('end_date', '>=', $today);
            })
            ->get()
            ->map(fn($coupon) => $this->formatCouponForApi($coupon));

        return response()->json([
            'status' => true,
            'message' => 'Available coupons fetched successfully',
            'data' => $coupons
        ]);
    }

    public function removeCoupon(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'source' => 'nullable|in:cart,buy_now',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $user = auth('sanctum')->user() ?? auth()->user();
        if (!$user && $request->has('user_id') && !empty($request->user_id)) {
            $user = \App\Models\User::find($request->user_id);
        }
        $sessionId = $request->header('session-id')
            ?: $request->header('Session-Id')
            ?: $request->header('session_id')
            ?: $request->input('session_id')
            ?: $request->input('sessionId')
            ?: $request->input('session-id');

        $source = $request->input('source');

        if ($user) {
            if ($source === 'buy_now') {
                BuyNow::where('user_id', $user->id)->update(['coupon_code' => null]);
            } elseif ($source === 'cart') {
                Cart::where('user_id', $user->id)->update(['coupon_code' => null]);
            } else {
                BuyNow::where('user_id', $user->id)->update(['coupon_code' => null]);
                Cart::where('user_id', $user->id)->update(['coupon_code' => null]);
            }
        }
        if ($sessionId) {
            if ($source === 'buy_now') {
                BuyNow::where('session_id', $sessionId)->update(['coupon_code' => null]);
            } elseif ($source === 'cart') {
                Cart::where('session_id', $sessionId)->update(['coupon_code' => null]);
            } else {
                BuyNow::where('session_id', $sessionId)->update(['coupon_code' => null]);
                Cart::where('session_id', $sessionId)->update(['coupon_code' => null]);
            }
        }

        // Also check if buy_now_id or cart_id was passed directly
        if ($request->filled('buy_now_id')) {
            BuyNow::where('id', $request->buy_now_id)->update(['coupon_code' => null]);
        }
        if ($request->filled('cart_id')) {
            Cart::where('id', $request->cart_id)->update(['coupon_code' => null]);
        }

        return response()->json([
            'status' => true,
            'message' => 'Coupon removed successfully',
            'coupon_code' => null,
            'coupon' => null,
            'data' => [
                'coupon_code' => null,
                'coupon' => null,
                'discount_amount' => 0,
            ],
        ]);
    }

    public function removeCouponFromCart(Request $request)
    {
        $request->merge(['source' => 'cart']);
        return $this->removeCoupon($request);
    }
    public function submitFaqQuestion(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'user_id'    => 'required|exists:users,id',
            'question'   => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        $faq = ProductFaq::create([
            'product_id' => $request->product_id,
            'user_id'    => $request->user_id,
            'question'   => $request->question,
            'answer'     => null,
            'order'      => 0,
            'status'     => 0
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Question submitted successfully.',
            'data' => $faq
        ]);
    }

    public function addReview(Request $request)
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        /*
        |--------------------------------------------------------------------------
        | VALIDATION
        |--------------------------------------------------------------------------
        */
        $validator = Validator::make($request->except(array_keys($request->allFiles())), [
            'product_id' => 'required|exists:products,id',
            'rating' => 'required|integer|min:1|max:5',
            'review' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | CHECK: ONE REVIEW PER USER PER PRODUCT
        |--------------------------------------------------------------------------
        */
        $alreadyReviewed = Review::where('product_id', $request->product_id)
            ->where('user_id', $user->id)
            ->exists();

        if ($alreadyReviewed) {
            return response()->json([
                'status' => false,
                'message' => 'You have already submitted a review for this product'
            ], 400);
        }

        /*
        |--------------------------------------------------------------------------
        | OPTIONAL: PURCHASE CHECK (KEEP IF REQUIRED)
        |--------------------------------------------------------------------------
        */
        $hasPurchased = OrderItem::where('product_id', $request->product_id)
            ->whereHas('order', function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->where('payment_status', 'paid');
            })
            ->exists();

        if (!$hasPurchased) {
            return response()->json([
                'status' => false,
                'message' => 'You can only review purchased products'
            ], 403);
        }

        /*
        |--------------------------------------------------------------------------
        | MEDIA FILES
        |--------------------------------------------------------------------------
        */
        $mediaFiles = $this->collectReviewMediaFiles($request);
        $imageFiles = $mediaFiles['images'];
        $videoFiles = $mediaFiles['videos'];

        $fileValidator = Validator::make(
            ['image' => $imageFiles, 'video' => $videoFiles],
            [
                'image' => 'nullable|array',
                'image.*' => 'image|mimes:jpg,jpeg,png,webp,gif|max:5120',
                'video' => 'nullable|array',
                'video.*' => 'mimes:mp4,mov,avi,mkv,3gp,webm|max:51200',
            ]
        );

        if ($fileValidator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $fileValidator->errors()->first(),
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | UPLOAD FILES
        |--------------------------------------------------------------------------
        */
        $images = $this->uploadFiles($imageFiles, 'backend/assets/images/review');
        $videos = $this->uploadFiles($videoFiles, 'backend/assets/videos/review');

        /*
        |--------------------------------------------------------------------------
        | CREATE REVIEW
        |--------------------------------------------------------------------------
        */
        $review = new Review([
            'user_id' => $user->id,
            'product_id' => $request->product_id,
            'rating' => $request->rating,
            'review' => $request->review,
            'status' => 1,
            'like_count' => 0,
            'dislike_count' => 0,
        ]);

        $review->setAttachmentsFromUploads($images, $videos);
        $review->save();

        $review->load('user');

        /*
        |--------------------------------------------------------------------------
        | RESPONSE
        |--------------------------------------------------------------------------
        */
        return response()->json([
            'status' => true,
            'message' => 'Review added successfully',
            'data' => $this->formatReviewForApi($review),
            'meta' => [
                'images_uploaded' => count($images),
                'videos_uploaded' => count($videos),
            ],
        ]);
    }

    private function collectReviewMediaFiles(Request $request): array
    {
        $imageFiles = [];
        $videoFiles = [];

        foreach ($request->allFiles() as $field => $files) {
            if (preg_match('/^(image|images|photo|photos|video|videos|attachments?|media|files)([._\[].*)?$/i', (string) $field) !== 1) {
                continue;
            }

            $isVideoField = preg_match('/^(video|videos)([._\[].*)?$/i', (string) $field) === 1;

            foreach ($this->flattenUploadedFiles($files) as $file) {
                if (!$file || !$file->isValid()) {
                    continue;
                }

                if ($isVideoField || $this->isVideoUpload($file)) {
                    $videoFiles[] = $file;
                } else {
                    $imageFiles[] = $file;
                }
            }
        }

        return [
            'images' => $this->uniqueUploadedFiles($imageFiles),
            'videos' => $this->uniqueUploadedFiles($videoFiles),
        ];
    }

    private function uniqueUploadedFiles(array $files): array
    {
        $unique = [];
        $seen = [];

        foreach ($files as $file) {
            if (!$file) {
                continue;
            }

            $key = spl_object_hash($file);

            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $unique[] = $file;
        }

        return $unique;
    }

    private function isVideoUpload($file): bool
    {
        $extension = strtolower((string) $file->getClientOriginalExtension());
        $mimeType = strtolower((string) $file->getMimeType());

        if (in_array($extension, ['mp4', 'mov', 'avi', 'mkv', '3gp', 'webm'], true)) {
            return true;
        }

        return str_starts_with($mimeType, 'video/');
    }

    private function getUploadedFilesAsArray(Request $request, string $field): array
    {
        $files = $request->file($field);

        if (!$files) {
            return [];
        }

        return $this->flattenUploadedFiles($files);
    }

    private function flattenUploadedFiles($files): array
    {
        if (!$files) {
            return [];
        }

        if (!is_array($files)) {
            return $files->isValid() ? [$files] : [];
        }

        $result = [];
        foreach ($files as $file) {
            $result = array_merge($result, $this->flattenUploadedFiles($file));
        }

        return $result;
    }

    public function uploadFiles($files, $path)
    {
        $uploadedFiles = [];

        if (!is_array($files)) {
            $files = [$files];
        }

        if (!file_exists(public_path($path))) {
            mkdir(public_path($path), 0777, true);
        }

        foreach ($files as $file) {
            if (!$file || !$file->isValid()) {
                continue;
            }

            $extension = $file->getClientOriginalExtension() ?: $file->extension() ?: 'bin';
            $fileName = uniqid() . '_' . time() . '.' . $extension;
            $destination = public_path($path . '/' . $fileName);

            if ($file->move(public_path($path), $fileName)) {
                $uploadedFiles[] = $fileName;
                continue;
            }

            if (@copy($file->getRealPath(), $destination)) {
                $uploadedFiles[] = $fileName;
            }
        }

        return $uploadedFiles;
    }

    public function getReview($id)
    {
        $review = Review::with(['product', 'user'])->find($id);

        if (!$review) {
            return response()->json([
                'status' => false,
                'message' => 'Review not found'
            ], 404);
        }

        // Optional authentication
        $user = auth('sanctum')->user();

        $userReaction = null;

        if ($user) {
            $userReactions = $this->getUserReviewReactions(
                $user,
                [$review->id]
            );

            $userReaction = $userReactions[$review->id] ?? null;
        }

        // Get all images/videos from all reviews of this product
        $productReviewMedia = Review::where('product_id', $review->product_id)
            ->get()
            ->flatMap(function ($reviewItem) {

                return collect($reviewItem->attachment_urls)
                    ->map(function ($attachment) {

                        return [
                            'type' => $attachment['type'] ?? null,
                            'url'  => $attachment['url'] ?? null,
                        ];
                    });
            })
            ->filter(function ($item) {
                return !empty($item['url']);
            })
            ->values()
            ->toArray();

        $data = $this->formatReviewForApi(
            $review,
            $userReaction
        );

        $data['product_review_media'] = $productReviewMedia;

        return response()->json([
            'status' => true,
            'message' => 'Review fetched successfully',
            'data' => $data
        ]);
    }

    public function updateReview(Request $request)
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        /*
        |--------------------------------------------------------------------------
        | VALIDATION
        |--------------------------------------------------------------------------
        */
        $request->validate([
            'id' => 'required|exists:reviews,id',
            'rating' => 'required|integer|min:1|max:5',
            'review' => 'required|string',
            'remove_attachments' => 'nullable',
            'remove_images' => 'nullable',
            'remove_videos' => 'nullable',
            'keep_attachments' => 'nullable',
            'keep_images' => 'nullable',
            'keep_videos' => 'nullable',
            'replace_attachments' => 'nullable|boolean',
        ]);

        /*
        |--------------------------------------------------------------------------
        | FIND REVIEW (SECURE)
        |--------------------------------------------------------------------------
        */
        $review = Review::where('id', $request->id)
            ->where('user_id', $user->id)
            ->first();

        if (!$review) {
            return response()->json([
                'status' => false,
                'message' => 'Review not found or unauthorized'
            ], 404);
        }

        /*
        |--------------------------------------------------------------------------
        | ATTACHMENTS HANDLING
        |--------------------------------------------------------------------------
        */
        $currentAttachments = $review->getStoredAttachments();
        $mediaFiles = $this->collectReviewMediaFiles($request);

        $hasNewUploads = !empty($mediaFiles['images']) || !empty($mediaFiles['videos']);
        $hasAttachmentFieldUploads = $this->hasReviewAttachmentFieldUploads($request);

        $shouldReplaceAttachments =
            $hasAttachmentFieldUploads ||
            ($request->boolean('replace_attachments') && $hasNewUploads) ||
            $this->shouldClearAttachmentsFromInput($request);

        $keepList = $this->resolveReviewAttachmentKeepList($request, $currentAttachments);

        /*
        |--------------------------------------------------------------------------
        | APPLY ATTACHMENT LOGIC
        |--------------------------------------------------------------------------
        */
        if ($shouldReplaceAttachments) {

            $review->clearAttachments();
        } elseif ($keepList !== null) {

            $review->keepOnlyAttachments($keepList);
        } else {

            $filesToRemove = array_values(array_unique(array_merge(
                Review::normalizeAttachmentRemovalReferences($request->input('remove_attachments', []), $currentAttachments),
                Review::normalizeAttachmentRemovalReferences($request->input('remove_images', []), $currentAttachments),
                Review::normalizeAttachmentRemovalReferences($request->input('remove_videos', []), $currentAttachments)
            )));

            if (!empty($filesToRemove)) {
                $review->removeAttachmentsByFilenames($filesToRemove);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | NEW FILE UPLOADS
        |--------------------------------------------------------------------------
        */
        if ($hasNewUploads) {

            $newImages = $this->uploadFiles($mediaFiles['images'], 'backend/assets/images/review');
            $newVideos = $this->uploadFiles($mediaFiles['videos'], 'backend/assets/videos/review');

            if ($shouldReplaceAttachments) {
                $review->setAttachmentsFromUploads($newImages, $newVideos);
            } else {
                $review->appendAttachmentsFromUploads($newImages, $newVideos);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | UPDATE CORE DATA
        |--------------------------------------------------------------------------
        */
        $review->update([
            'rating' => $request->rating,
            'review' => $request->review,
            'attachments' => Review::encodeFromAttachmentItems(
                $review->getStoredAttachments()
            ),
        ]);

        /*
        |--------------------------------------------------------------------------
        | RESPONSE
        |--------------------------------------------------------------------------
        */
        return response()->json([
            'status' => true,
            'message' => 'Review updated successfully',
            'data' => $this->formatReviewForApi(
                $review->fresh()->load('user')
            ),
        ]);
    }

    private function resolveReviewAttachmentKeepList(Request $request, array $currentAttachments): ?array
    {
        if ($request->has('keep_attachments')) {
            return Review::normalizeAttachmentReferences($request->input('keep_attachments', []));
        }

        if ($request->has('keep_images') || $request->has('keep_videos')) {
            return array_values(array_unique(array_merge(
                Review::normalizeAttachmentReferences($request->input('keep_images', [])),
                Review::normalizeAttachmentReferences($request->input('keep_videos', []))
            )));
        }

        $attachmentsInput = $request->input('attachments');

        if ($attachmentsInput !== null && !$this->hasReviewAttachmentFieldUploads($request)) {
            return Review::normalizeAttachmentReferences($attachmentsInput);
        }

        return null;
    }

    private function hasReviewAttachmentFieldUploads(Request $request): bool
    {
        foreach (array_keys($request->allFiles()) as $field) {
            if (preg_match('/^attachments?([._\[]|$)/i', (string) $field) !== 1) {
                continue;
            }

            foreach ($this->flattenUploadedFiles($request->file($field)) as $file) {
                if ($file && $file->isValid()) {
                    return true;
                }
            }
        }

        return false;
    }

    private function shouldClearAttachmentsFromInput(Request $request): bool
    {
        if ($this->hasReviewAttachmentFieldUploads($request) || !$request->has('attachments')) {
            return false;
        }

        $attachmentsInput = $request->input('attachments');

        if ($attachmentsInput === null || $attachmentsInput === '' || $attachmentsInput === '[]') {
            return true;
        }

        return is_array($attachmentsInput) && empty($attachmentsInput);
    }

    public function deleteReview(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:reviews,id'
        ]);

        $review = Review::where('id', $request->id)
            ->where('user_id', auth()->id())
            ->first();

        if (!$review) {
            return response()->json([
                'status' => false,
                'message' => 'Review not found or you are not authorized to delete it'
            ], 404);
        }

        $review->deleteMediaFiles();
        $review->delete();

        return response()->json([
            'status' => true,
            'message' => 'Review deleted successfully'
        ]);
    }

    public function myReviews()
    {
        $reviews = Review::with('product')
            ->where('user_id', auth()->id())
            ->latest()
            ->get();

        return response()->json([
            'status' => true,
            'data' => $reviews->map(fn($review) => $this->formatReviewForApi($review))->values(),
        ]);
    }

    public function reviewLike(Request $request)
    {
        return $this->handleReviewReaction($request, 'like');
    }

    public function reviewDislike(Request $request)
    {
        return $this->handleReviewReaction($request, 'dislike');
    }

    private function handleReviewReaction(Request $request, string $type)
    {
        $user = auth('sanctum')->user();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Please login to ' . $type . ' this review',
            ], 401);
        }

        $validator = Validator::make($request->all(), [
            'review_id' => 'required|exists:reviews,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $review = Review::where('id', $request->review_id)
            ->where('status', 1)
            ->first();

        if (!$review) {
            return response()->json([
                'status' => false,
                'message' => 'Review not found',
            ], 404);
        }

        $oppositeType = $type === 'like' ? 'dislike' : 'like';
        $countColumn = $type . '_count';
        $oppositeCountColumn = $oppositeType . '_count';
        $userReaction = null;

        DB::transaction(function () use ($review, $user, $type, $oppositeType, $countColumn, $oppositeCountColumn, &$userReaction) {
            $reaction = ReviewReaction::where('review_id', $review->id)
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            if ($reaction && $reaction->type === $type) {
                $reaction->delete();
                $review->decrement($countColumn);
                $userReaction = null;
                return;
            }

            if ($reaction && $reaction->type === $oppositeType) {
                $reaction->update(['type' => $type]);
                $review->decrement($oppositeCountColumn);
                $review->increment($countColumn);
                $userReaction = $type;
                return;
            }

            ReviewReaction::create([
                'review_id' => $review->id,
                'user_id' => $user->id,
                'type' => $type,
            ]);
            $review->increment($countColumn);
            $userReaction = $type;
        });

        $review->refresh()->load('user');

        return response()->json([
            'status' => true,
            'message' => ucfirst($type) . ' updated successfully',
            'data' => $this->formatReviewForApi($review, $userReaction),
        ]);
    }

    private function resolveOptionalApiUser(Request $request)
    {
        $user = auth('sanctum')->user();

        if (!$user) {
            $rawToken = $request->bearerToken();

            if (!$rawToken) {
                $authHeader = $request->header('Authorization')
                    ?: $request->header('authorization')
                    ?: ($request->server('HTTP_AUTHORIZATION')
                        ?: ($request->server('REDIRECT_HTTP_AUTHORIZATION')
                            ?: (function_exists('apache_request_headers') ? (apache_request_headers()['Authorization'] ?? apache_request_headers()['authorization'] ?? null) : null)));

                if ($authHeader) {
                    $rawToken = $authHeader;
                }
            }

            if (!$rawToken) {
                $rawToken = $request->header('token')
                    ?: $request->header('Token')
                    ?: $request->header('auth_token')
                    ?: $request->header('auth-token')
                    ?: $request->header('x-auth-token')
                    ?: $request->header('x-token')
                    ?: $request->input('token')
                    ?: $request->input('auth_token')
                    ?: $request->input('access_token')
                    ?: $request->query('token')
                    ?: $request->query('auth_token')
                    ?: $request->cookie('token')
                    ?: $request->cookie('auth_token');
            }

            if ($rawToken) {
                $rawToken = trim($rawToken);
                if (preg_match('/^Bearer\s+(.*)$/i', $rawToken, $matches)) {
                    $rawToken = trim($matches[1]);
                }
                $rawToken = trim($rawToken, "\"' ");

                $accessToken = PersonalAccessToken::findToken($rawToken);
                if ($accessToken) {
                    $user = $accessToken->tokenable;
                }
            }
        }

        if (!$user) {
            $uid = $request->input('user_id')
                ?? $request->input('userId')
                ?? $request->header('user-id')
                ?? $request->header('user_id')
                ?? $request->header('userId')
                ?? $request->header('x-user-id')
                ?? $request->cookie('user_id')
                ?? $request->cookie('userId')
                ?? $request->input('user.id')
                ?? $request->input('userData.id');

            if (!empty($uid) && is_numeric($uid)) {
                $user = \App\Models\User::find($uid);
            }
        }

        // If session_id was formatted like 'user_5', extract user ID 5
        if (!$user) {
            $sid = $request->header('session-id')
                ?: $request->header('session_id')
                ?: $request->input('session_id')
                ?: $request->query('session_id')
                ?: $request->cookie('session_id');

            if ($sid && str_starts_with($sid, 'user_')) {
                $extractedId = substr($sid, 5);
                if (is_numeric($extractedId)) {
                    $user = \App\Models\User::find($extractedId);
                }
            }
        }

        if (!$user) {
            $user = auth()->user();
        }

        return $user;
    }

    private function resolveOptionalSessionId(Request $request, $user = null): ?string
    {
        $candidates = [
            $request->header('session-id'),
            $request->header('Session-Id'),
            $request->header('session_id'),
            $request->header('Session_Id'),
            $request->header('sessionId'),
            $request->header('guest-id'),
            $request->header('guest_id'),
            $request->header('guestId'),
            $request->header('device-id'),
            $request->header('device_id'),
            $request->header('deviceId'),
            $request->input('session_id'),
            $request->input('sessionId'),
            $request->input('session-id'),
            $request->input('guest_id'),
            $request->input('guestId'),
            $request->query('session_id'),
            $request->cookie('guest_session_id'),
            $request->cookie('session_id'),
            $request->cookie('sessionId'),
            $user ? 'user_' . $user->id : null,
        ];

        $sessionId = null;
        foreach ($candidates as $cand) {
            if (!empty($cand) && is_string($cand)) {
                $sessionId = trim($cand);
                break;
            }
        }

        if (!$sessionId && !$user) {
            // Consistent fallback fingerprint for guests across requests from the same device/browser
            $ip = $request->ip() ?: '127.0.0.1';
            $ua = $request->header('User-Agent') ?: 'guest';
            $sessionId = 'guest_' . substr(md5($ip . '|' . $ua), 0, 16);
        }

        return $sessionId;
    }

    private function getUserReviewReactions($user, array $reviewIds): array
    {
        if (!$user || empty($reviewIds)) {
            return [];
        }

        return ReviewReaction::where('user_id', $user->id)
            ->whereIn('review_id', $reviewIds)
            ->pluck('type', 'review_id')
            ->toArray();
    }

    private function formatReviewForApi(Review $review, ?string $userReaction = null): array
    {
        if (!$review->relationLoaded('user')) {
            $review->load('user');
        }

        return [
            'id' => $review->id,
            'user_id' => $review->user_id,
            'user_name' => $review->user->name ?? 'User',
            'user' => $review->user ? [
                'id' => $review->user->id,
                'name' => $review->user->name,
            ] : null,
            'product_id' => $review->product_id,
            'rating' => $review->rating,
            'review' => $review->review,
            'attachments' => $review->attachment_urls,
            'like_count' => (int) ($review->like_count ?? 0),
            'dislike_count' => (int) ($review->dislike_count ?? 0),
            'user_reaction' => $userReaction,
            'status' => $review->status,
            'created_at' => $review->created_at?->format('d M Y'),
            'updated_at' => $review->updated_at?->format('d M Y'),
        ];
    }



    public function buyNow(Request $request)
    {
        return $this->addToBuyNow($request);
    }

    public function addToBuyNow(Request $request)
    {
        $productId = $request->input('product_id')
            ?: ($request->input('productId')
                ?: ($request->input('id')
                    ?: $request->input('item_id')));

        $variantId = $request->input('variant_id')
            ?: ($request->input('variantId')
                ?: null);

        $quantity = $request->input('quantity')
            ?: ($request->input('qty')
                ?: 1);

        $request->merge([
            'product_id' => $productId,
            'variant_id' => $variantId,
            'quantity'   => (int) $quantity,
        ]);

        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'variant_id' => 'nullable|integer|exists:product_variants,id',
            'quantity'   => 'required|integer|min:1',
            'address_id' => 'nullable|exists:addresses,id',
            'coupon_code' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        // Get product
        $product = Product::find($request->product_id);
        if (!$product) {
            return response()->json([
                'status' => false,
                'message' => 'Product not found',
            ], 404);
        }

        // Resolve variant by arch & size if variant_id wasn't directly supplied
        if (empty($request->variant_id)) {
            $archInput = $request->input('arch') ?: $request->input('selected_arch');
            $sizeInput = $request->input('size') ?: $request->input('selected_size');
            if ($archInput && $sizeInput) {
                $matchedVariant = ProductVariant::where('product_id', $product->id)
                    ->where('arch', $archInput)
                    ->where('size', (string) $sizeInput)
                    ->where('status', 1)
                    ->first();
                if ($matchedVariant) {
                    $request->merge(['variant_id' => $matchedVariant->id]);
                }
            }
        }

        // If product is variable and variant not selected, select first active variant
        if ($product->isVariable() && empty($request->variant_id)) {
            $defaultVariant = ProductVariant::where('product_id', $product->id)
                ->where('status', 1)
                ->where('quantity', '>', 0)
                ->first()
                ?: ProductVariant::where('product_id', $product->id)
                ->where('status', 1)
                ->first();

            if ($defaultVariant) {
                $request->merge(['variant_id' => $defaultVariant->id]);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Please select product Arch and Size variant before proceeding to Buy Now',
                ], 422);
            }
        }

        if (!empty($request->variant_id)) {
            $variant = ProductVariant::where('id', $request->variant_id)
                ->where('product_id', $request->product_id)
                ->where('status', 1)
                ->first();
            if (!$variant) {
                return response()->json([
                    'status' => false,
                    'message' => 'Selected variant does not belong to this product or is inactive',
                ], 422);
            }
        }

        // 🔥 Check stock for simple or variant product
        $stockCheck = $this->checkProductStock(
            $request->product_id,
            $request->quantity,
            $request->variant_id
        );

        if (!$stockCheck['available']) {
            return response()->json([
                'status' => false,
                'message' => $stockCheck['message'],
                'available_stock' => $stockCheck['available_stock'],
                'product_type' => $stockCheck['product_type']
            ], 400);
        }

        $user = $this->resolveOptionalApiUser($request);
        $sessionId = $this->resolveOptionalSessionId($request, $user);

        if (!$sessionId) {
            $sessionId = $user ? 'user_' . $user->id : uniqid('guest_');
        }

        // Claim any existing guest buy now records for this user
        if ($user && $sessionId) {
            BuyNow::whereNull('user_id')
                ->where('session_id', $sessionId)
                ->update(['user_id' => $user->id]);
        }

        // Check if this exact product & variant is already in BuyNow
        $existingItem = BuyNow::where(function ($q) use ($user, $sessionId) {
            if ($user && $sessionId) {
                $q->where('user_id', $user->id)->orWhere('session_id', $sessionId);
            } elseif ($user) {
                $q->where('user_id', $user->id);
            } elseif ($sessionId) {
                $q->where('session_id', $sessionId);
            }
        })
            ->where('product_id', $request->product_id)
            ->when($request->variant_id, fn($q) => $q->where('variant_id', $request->variant_id))
            ->first();

        // Get existing buy now to check for coupon and address
        $existingAnyBuyNow = $existingItem ?: BuyNow::where(function ($q) use ($user, $sessionId) {
            if ($user && $sessionId) {
                $q->where('user_id', $user->id)->orWhere('session_id', $sessionId);
            } elseif ($user) {
                $q->where('user_id', $user->id);
            } elseif ($sessionId) {
                $q->where('session_id', $sessionId);
            }
        })->latest()->first();

        $couponCode = $request->coupon_code ?? ($existingAnyBuyNow ? $existingAnyBuyNow->coupon_code : null);
        $addressId = $request->address_id ?? ($existingAnyBuyNow ? $existingAnyBuyNow->address_id : null);
        if (!$addressId && $user) {
            $addressId = Address::where('user_id', $user->id)->latest()->value('id');
        }

        if ($existingItem) {
            // Already added: do NOT insert a duplicate record! Re-use existing row
            // Remove any other old buy now products for this user/session so ONLY this selected product is active
            BuyNow::where(function ($q) use ($user, $sessionId) {
                if ($user && $sessionId) {
                    $q->where('user_id', $user->id)->orWhere('session_id', $sessionId);
                } elseif ($user) {
                    $q->where('user_id', $user->id);
                } elseif ($sessionId) {
                    $q->where('session_id', $sessionId);
                }
            })->where('id', '!=', $existingItem->id)->delete();

            $existingItem->quantity = (int) $request->quantity;
            if ($user && empty($existingItem->user_id)) {
                $existingItem->user_id = $user->id;
            }
            if ($addressId) {
                $existingItem->address_id = $addressId;
            }
            if ($couponCode) {
                $existingItem->coupon_code = $couponCode;
            }
            $existingItem->touch();
            $existingItem->save();
            $buyNow = $existingItem;
        } else {
            // Delete all old BuyNow records so ONLY this latest product exists
            BuyNow::where(function ($q) use ($user, $sessionId) {
                if ($user && $sessionId) {
                    $q->where('user_id', $user->id)->orWhere('session_id', $sessionId);
                } elseif ($user) {
                    $q->where('user_id', $user->id);
                } elseif ($sessionId) {
                    $q->where('session_id', $sessionId);
                }
            })->delete();

            $buyNow = BuyNow::create([
                'user_id' => $user->id ?? null,
                'session_id' => $sessionId,
                'product_id' => $request->product_id,
                'variant_id' => $request->variant_id,
                'quantity' => (int) $request->quantity,
                'address_id' => $addressId,
                'coupon_code' => $couponCode,
            ]);
            $buyNow->touch();
        }

        if ($user) {
            \Illuminate\Support\Facades\Cache::put('checkout_active_flow_u_' . $user->id, 'buy_now', now()->addDays(7));
            \Illuminate\Support\Facades\Cache::put('buy_now_clicked_at_u_' . $user->id, now()->timestamp, now()->addDays(7));
        }
        if ($sessionId) {
            \Illuminate\Support\Facades\Cache::put('checkout_active_flow_s_' . $sessionId, 'buy_now', now()->addDays(7));
            \Illuminate\Support\Facades\Cache::put('buy_now_clicked_at_s_' . $sessionId, now()->timestamp, now()->addDays(7));
        }

        // Load product details
        $buyNow->load(['product', 'variant', 'address']);

        $price = $this->getBuyNowPrice($buyNow);
        $subtotal = $price * $buyNow->quantity;

        $availableStock = $this->getProductAvailableStock(
            $request->product_id,
            $request->variant_id
        );

        // Calculate discount if coupon exists
        $discountAmount = 0;
        if ($buyNow->coupon_code) {
            $coupon = Coupon::where('code', $buyNow->coupon_code)->where('status', 1)->first();
            if ($coupon) {
                if ($coupon->type === 'percent') {
                    $discountAmount = ($subtotal * $coupon->value) / 100;
                    if (!empty($coupon->maximum_discount)) {
                        $discountAmount = min($discountAmount, $coupon->maximum_discount);
                    }
                } elseif ($coupon->type === 'fixed') {
                    $discountAmount = $coupon->value;
                }
            }
        }

        $bnArch = $buyNow->variant?->arch;
        $bnSize = $buyNow->variant?->size !== null && $buyNow->variant?->size !== '' ? (string) $buyNow->variant->size : null;
        $bnVariantTitle = $buyNow->variant ? ($buyNow->variant->variant_title ?? trim(($bnArch ?? '') . ' ' . ($bnSize ? 'Size ' . $bnSize : ''))) : null;
        $bnSku = $buyNow->variant?->sku ?: ($buyNow->product->sku ?? null);

        return response()->json([
            'status' => true,
            'message' => 'Product added to buy now',
            'session_id' => $sessionId,
            'data' => [
                'buy_now_id' => $buyNow->id,
                'session_id' => $sessionId,
                'address_id' => $buyNow->address_id,
                'coupon_code' => $buyNow->coupon_code,
                'discount_amount' => round($discountAmount, 2),
                'arch' => $bnArch,
                'size' => $bnSize,
                'selected_arch' => $bnArch,
                'selected_size' => $bnSize,
                'variant_title' => $bnVariantTitle,
                'sku' => $bnSku,
                'product' => [
                    'id' => $buyNow->product->id,
                    'name' => $buyNow->product->name,
                    'slug' => $buyNow->product->slug,
                    'arch' => $bnArch,
                    'size' => $bnSize,
                    'selected_arch' => $bnArch,
                    'selected_size' => $bnSize,
                    'variant_title' => $bnVariantTitle,
                    'sku' => $bnSku,
                    'thumbnail' => $buyNow->product->thumbnail
                        ? url('backend/assets/images/product/' . $buyNow->product->thumbnail)
                        : null,
                    'price' => $price,
                    'selling_price' => $buyNow->product->selling_price,
                    'product_type' => $buyNow->product->product_type,
                    'in_stock' => $availableStock > 0,
                    'available_stock' => $availableStock
                ],
                'variant' => $buyNow->variant ? [
                    'id' => $buyNow->variant->id,
                    'sku' => $buyNow->variant->sku,
                    'arch' => $bnArch,
                    'size' => $bnSize,
                    'selected_arch' => $bnArch,
                    'selected_size' => $bnSize,
                    'variant_title' => $bnVariantTitle,
                    'price' => $buyNow->variant->price,
                    'selling_price' => $buyNow->variant->selling_price,
                    'in_stock' => $buyNow->variant->quantity > 0,
                    'available_stock' => $buyNow->variant->quantity
                ] : null,
                'quantity' => $buyNow->quantity,
                'subtotal' => $subtotal,
                'available_stock' => $availableStock
            ]
        ])->cookie('guest_session_id', $sessionId, 60 * 24 * 30, '/', null, false, false);
    }

    /**
     * Get current buy now item (or cart checkout items)
     */
    public function getBuyNow(Request $request)
    {
        try {
            $user = $this->resolveOptionalApiUser($request);
            $sessionId = $this->resolveOptionalSessionId($request, $user);

            // Claim guest items for logged-in user if both present
            if ($user && $sessionId) {
                BuyNow::whereNull('user_id')
                    ->where('session_id', $sessionId)
                    ->update(['user_id' => $user->id]);

                Cart::whereNull('user_id')
                    ->where('session_id', $sessionId)
                    ->update(['user_id' => $user->id]);
            }

            // 1. Fetch BuyNow items
            $buyNowQuery = BuyNow::with(['product', 'variant.attributes.attribute', 'variant.attributes.attributeValue']);
            if ($user && $sessionId) {
                $buyNowQuery->where(function ($q) use ($user, $sessionId) {
                    $q->where('user_id', $user->id)
                        ->orWhere('session_id', $sessionId);
                });
            } elseif ($user) {
                $buyNowQuery->where('user_id', $user->id);
            } elseif ($sessionId) {
                $buyNowQuery->where('session_id', $sessionId);
            } else {
                $buyNowQuery->whereRaw('1 = 0');
            }

            $validBuyNowItems = $buyNowQuery->orderByDesc('id')->get()
                ->filter(fn($item) => $item->product !== null)->values();

            // Auto-populate Buy Now if direct product checkout was requested via query parameter
            $requestedProductId = $request->input('product_id')
                ?: ($request->input('productId')
                    ?: ($request->input('id')
                        ?: $request->input('item_id')));

            if ($requestedProductId && $validBuyNowItems->isEmpty()) {
                $prod = Product::find($requestedProductId);
                if ($prod) {
                    $reqVarId = $request->input('variant_id') ?: $request->input('variantId');
                    if ($prod->isVariable() && empty($reqVarId)) {
                        $defVar = ProductVariant::where('product_id', $prod->id)->where('status', 1)->first();
                        $reqVarId = $defVar?->id;
                    }

                    BuyNow::where(function ($q) use ($user, $sessionId) {
                        if ($user && $sessionId) {
                            $q->where('user_id', $user->id)->orWhere('session_id', $sessionId);
                        } elseif ($user) {
                            $q->where('user_id', $user->id);
                        } elseif ($sessionId) {
                            $q->where('session_id', $sessionId);
                        }
                    })->delete();

                    $newBN = BuyNow::create([
                        'user_id' => $user ? $user->id : null,
                        'session_id' => $sessionId ?: ($user ? 'user_' . $user->id : null),
                        'product_id' => $prod->id,
                        'variant_id' => $reqVarId,
                        'quantity' => max(1, (int) ($request->input('quantity') ?: ($request->input('qty') ?: 1))),
                    ]);
                    $newBN->load(['product', 'variant']);
                    $validBuyNowItems = collect([$newBN]);
                }
            }

            // 2. Fetch Cart items
            $cartQuery = Cart::with(['product', 'variant.attributes.attribute', 'variant.attributes.attributeValue']);
            if ($user && $sessionId) {
                $cartQuery->where(function ($q) use ($user, $sessionId) {
                    $q->where('user_id', $user->id)
                        ->orWhere('session_id', $sessionId);
                });
            } elseif ($user) {
                $cartQuery->where('user_id', $user->id);
            } elseif ($sessionId) {
                $cartQuery->where('session_id', $sessionId);
            } else {
                $cartQuery->whereRaw('1 = 0');
            }

            $validCartItems = $cartQuery->orderByDesc('id')->get()
                ->filter(fn($item) => $item->product !== null)->values();

            // 3. Resolve items for checkout
            $userKey = $user ? 'u_' . $user->id : 's_' . $sessionId;
            $activeFlow = \Illuminate\Support\Facades\Cache::get('checkout_active_flow_' . $userKey);
            if (!$activeFlow && $user && $sessionId) {
                $activeFlow = \Illuminate\Support\Facades\Cache::get('checkout_active_flow_s_' . $sessionId);
            }

            $cartViewedAt = (int) \Illuminate\Support\Facades\Cache::get('cart_viewed_at_' . $userKey, 0);
            if ($cartViewedAt === 0 && $user && $sessionId) {
                $cartViewedAt = (int) \Illuminate\Support\Facades\Cache::get('cart_viewed_at_s_' . $sessionId, 0);
            }

            $buyNowClickedAt = (int) \Illuminate\Support\Facades\Cache::get('buy_now_clicked_at_' . $userKey, 0);
            if ($buyNowClickedAt === 0 && $user && $sessionId) {
                $buyNowClickedAt = (int) \Illuminate\Support\Facades\Cache::get('buy_now_clicked_at_s_' . $sessionId, 0);
            }

            $source = $request->input('source') ?: $request->input('type');
            $referer = (string) ($request->header('referer') ?: $request->header('Referer') ?: '');

            $latestBuyNow = $validBuyNowItems->sortByDesc('updated_at')->first();
            $latestCart = $validCartItems->sortByDesc('updated_at')->first();
            $latestBuyNowTime = $latestBuyNow ? ($latestBuyNow->updated_at ? $latestBuyNow->updated_at->timestamp : 0) : 0;
            $latestCartTime = $latestCart ? ($latestCart->updated_at ? $latestCart->updated_at->timestamp : 0) : 0;

            // Determine if user is checking out Cart or Buy Now:
            $isCartMode = false;
            if ($source === 'cart' || str_contains(strtolower($referer), 'my-cart') || str_contains(strtolower($referer), '/cart')) {
                // Explicitly from cart
                $isCartMode = true;
            } elseif ($source === 'buy_now') {
                // Explicitly buy now
                $isCartMode = false;
            } elseif ($activeFlow === 'buy_now' && ($buyNowClickedAt > 0 && $buyNowClickedAt >= $cartViewedAt)) {
                // User clicked buy now more recently than viewing cart
                $isCartMode = false;
            } elseif ($activeFlow === 'cart' && ($cartViewedAt > 0 && $cartViewedAt >= $buyNowClickedAt)) {
                // User was on cart page more recently than buy now
                $isCartMode = true;
            } elseif ($latestCartTime > 0 && $latestCartTime > $latestBuyNowTime) {
                // Database timestamps: Cart was updated/viewed more recently than Buy Now
                $isCartMode = true;
            } elseif ($validBuyNowItems->isNotEmpty()) {
                // Buy Now item is present and was touched more recently
                $isCartMode = false;
            } elseif ($activeFlow === 'cart' || $validCartItems->isNotEmpty()) {
                $isCartMode = true;
            } else {
                $isCartMode = false;
            }

            if ($isCartMode) {
                $items = $validCartItems;
            } else {
                // In Buy Now mode: only show buy now items. If empty, data is empty!
                $items = $validBuyNowItems;
            }

            /*
            |--------------------------------------------------------------------------
            | ADDRESS
            |--------------------------------------------------------------------------
            */
            $selectedAddress = null;
            $addressId = $request->input('address_id')
                ?? $items->first()?->address_id
                ?? null;

            if ($addressId) {
                $selectedAddress = Address::where('id', $addressId)->first();
            }
            if (!$selectedAddress && $user) {
                $selectedAddress = Address::where('user_id', $user->id)->latest()->first();
            }

            /*
            |--------------------------------------------------------------------------
            | EMPTY BUY NOW STATE
            |--------------------------------------------------------------------------
            */
            if ($items->isEmpty()) {
                $trendingProducts = Product::where('status', 1)
                    ->latest()
                    ->take(10)
                    ->get()
                    ->map(function ($p) {
                        $galleryImages = [];
                        if (!empty($p->gallery)) {
                            $gallery = is_string($p->gallery) ? json_decode($p->gallery, true) : $p->gallery;
                            if (is_array($gallery)) {
                                $galleryImages = collect($gallery)->map(fn($img) => url('backend/assets/images/product/' . $img))->values()->toArray();
                            }
                        }
                        return [
                            'id' => $p->id,
                            'category_id' => $p->category_id,
                            'subcategory_id' => $p->subcategory_id,
                            'name' => $p->name,
                            'slug' => $p->slug,
                            'thumbnail' => $p->thumbnail ? url('backend/assets/images/product/' . $p->thumbnail) : null,
                            'gallery' => $galleryImages,
                            'price' => (float) $p->price,
                            'selling_price' => (float) $p->selling_price,
                            'is_trending' => (bool) $p->is_trending,
                            'is_featured' => (bool) $p->is_featured,
                            'is_best_selling' => (bool) $p->is_best_selling,
                            'is_top_rated' => (bool) $p->is_top_rated,
                            'is_new_arrival' => (bool) $p->is_new_arrival,
                            'quantity' => $p->quantity,
                            'in_stock' => $p->quantity > 0,
                            'stock_status' => $p->quantity > 0 ? 'In Stock' : 'Out Of Stock',
                        ];
                    });

                return response()->json([
                    'status' => true,
                    'message' => 'Buy Now fetched successfully',
                    'session_id' => $sessionId,
                    'selected_address' => $selectedAddress,
                    'coupon_code' => null,
                    'coupon' => null,
                    'data' => [],
                    'trending_products' => $trendingProducts->values(),
                    'cart_summary' => [
                        'subtotal' => 0.0,
                        'discount_amount' => 0.0,
                        'amount_after_discount' => 0.0,
                        'gst_percentage' => 0,
                        'gst_amount' => 0.0,
                        'shipping_charge' => 0.0,
                        'grand_total' => 0.0,
                    ],
                ])->cookie('guest_session_id', $sessionId, 60 * 24 * 30, '/', null, false, false);
            }

            /*
            |--------------------------------------------------------------------------
            | MAP ITEMS (EXACT SAME SHAPE AS GETCART)
            |--------------------------------------------------------------------------
            */
            $allSimilarProducts = collect();

            $mappedItems = $items->map(function ($item) use (&$allSimilarProducts) {
                $qty = max(1, (int) $item->quantity);
                $item->quantity = $qty;
                $item->qty = $qty;
                $item->cart_quantity = $qty;
                $item->buy_now_quantity = $qty;
                $item->item_quantity = $qty;
                $item->cart_id = $item->id;
                $item->buy_now_id = $item->id;

                $unitPrice = 0;
                $originalPrice = 0;

                if ($item->variant) {
                    $vSelling = (float) ($item->variant->selling_price ?? 0);
                    $vPrice = (float) ($item->variant->price ?? 0);
                    $pSelling = (float) ($item->product->selling_price ?? 0);
                    $pPrice = (float) ($item->product->price ?? 0);

                    if ($vSelling > 0) {
                        $unitPrice = $vSelling;
                    } elseif ($vPrice > 0) {
                        $unitPrice = $vPrice;
                    } elseif ($pSelling > 0) {
                        $unitPrice = $pSelling;
                    } elseif ($pPrice > 0) {
                        $unitPrice = $pPrice;
                    }

                    if ($vPrice > 0) {
                        $originalPrice = $vPrice;
                    } elseif ($pPrice > 0) {
                        $originalPrice = $pPrice;
                    } else {
                        $originalPrice = $unitPrice;
                    }

                    $arch = $item->variant->arch;
                    $size = $item->variant->size !== null && $item->variant->size !== '' ? (string) $item->variant->size : null;
                    $sku = $item->variant->sku;

                    if ((!$arch || !$size) && $item->variant->relationLoaded('attributes')) {
                        foreach ($item->variant->attributes as $attrRel) {
                            $attrName = strtolower($attrRel->attribute?->name ?? '');
                            $attrVal = $attrRel->attributeValue?->value ?? '';
                            if ($attrName === 'arch' && !$arch) $arch = $attrVal;
                            if ($attrName === 'size' && !$size) $size = (string) $attrVal;
                        }
                    }

                    if ((!$arch || !$size) && $sku) {
                        $skuUpper = strtoupper($sku);
                        if (!$arch) {
                            if (str_contains($skuUpper, '-LL-') || str_ends_with($skuUpper, '-LL')) $arch = 'Lower Left';
                            elseif (str_contains($skuUpper, '-LR-') || str_ends_with($skuUpper, '-LR')) $arch = 'Lower Right';
                            elseif (str_contains($skuUpper, '-UL-') || str_ends_with($skuUpper, '-UL')) $arch = 'Upper Left';
                            elseif (str_contains($skuUpper, '-UR-') || str_ends_with($skuUpper, '-UR')) $arch = 'Upper Right';
                        }
                        if (!$size && preg_match('/-(?:LL|LR|UL|UR)-([0-9]+)/', $skuUpper, $matches)) {
                            $size = $matches[1];
                        }
                    }

                    $variantTitle = null;
                    if ($arch || $size) {
                        $parts = array_filter([$arch, $size ? 'Size ' . $size : null]);
                        $variantTitle = implode(' - ', $parts);
                    } else {
                        $variantTitle = $item->variant->variant_title ?? trim(($item->variant->arch ?? '') . ' ' . ($item->variant->size ? 'Size ' . $item->variant->size : ''));
                    }

                    $item->variant_title = $variantTitle;
                    $item->arch = $arch;
                    $item->size = $size;
                    $item->selected_arch = $arch;
                    $item->selected_size = $size;
                    $item->sku = $sku ?: ($item->product->sku ?? null);

                    $item->setAttribute('arch', $arch);
                    $item->setAttribute('size', $size);
                    $item->setAttribute('selected_arch', $arch);
                    $item->setAttribute('selected_size', $size);
                    $item->setAttribute('variant_title', $variantTitle);
                    $item->setAttribute('sku', $sku ?: ($item->product->sku ?? null));

                    $item->variant->arch = $arch;
                    $item->variant->size = $size;
                    $item->variant->selected_arch = $arch;
                    $item->variant->selected_size = $size;
                    $item->variant->variant_title = $variantTitle;
                    $item->variant->setAttribute('arch', $arch);
                    $item->variant->setAttribute('size', $size);
                    $item->variant->setAttribute('selected_arch', $arch);
                    $item->variant->setAttribute('selected_size', $size);
                    $item->variant->setAttribute('variant_title', $variantTitle);

                    if (($item->variant->selling_price ?? 0) <= 0 && $unitPrice > 0) {
                        $item->variant->selling_price = (float) $unitPrice;
                    }
                    if (($item->variant->price ?? 0) <= 0 && $originalPrice > 0) {
                        $item->variant->price = (float) $originalPrice;
                    }
                } elseif ($item->product) {
                    $item->arch = null;
                    $item->size = null;
                    $item->selected_arch = null;
                    $item->selected_size = null;
                    $item->variant_title = null;
                    $item->sku = $item->product->sku ?? null;
                    $item->setAttribute('arch', null);
                    $item->setAttribute('size', null);
                    $item->setAttribute('selected_arch', null);
                    $item->setAttribute('selected_size', null);
                    $item->setAttribute('variant_title', null);
                    $item->setAttribute('sku', $item->product->sku ?? null);
                    $pSelling = (float) ($item->product->selling_price ?? 0);
                    $pPrice = (float) ($item->product->price ?? 0);

                    if ($pSelling > 0) {
                        $unitPrice = $pSelling;
                    } elseif ($pPrice > 0) {
                        $unitPrice = $pPrice;
                    }

                    if ($pPrice > 0) {
                        $originalPrice = $pPrice;
                    } else {
                        $originalPrice = $unitPrice;
                    }
                }

                if ($originalPrice < $unitPrice) {
                    $originalPrice = $unitPrice;
                }

                $discountPercent = ($originalPrice > 0 && $originalPrice > $unitPrice)
                    ? (int) round((($originalPrice - $unitPrice) / $originalPrice) * 100)
                    : 0;

                $lineTotal = round($unitPrice * $qty, 2);

                $item->price = (float) $unitPrice;
                $item->unit_price = (float) $unitPrice;
                $item->selling_price = (float) $unitPrice;
                $item->original_price = (float) $originalPrice;
                $item->discount = $discountPercent;
                $item->line_total = (float) $lineTotal;
                $item->total = (float) $lineTotal;
                $item->subtotal = (float) $lineTotal;

                if ($item->product) {
                    $item->product->arch = $item->arch;
                    $item->product->size = $item->size;
                    $item->product->selected_arch = $item->selected_arch;
                    $item->product->selected_size = $item->selected_size;
                    $item->product->variant_title = $item->variant_title;
                    $item->product->setAttribute('arch', $item->arch);
                    $item->product->setAttribute('size', $item->size);
                    $item->product->setAttribute('selected_arch', $item->selected_arch);
                    $item->product->setAttribute('selected_size', $item->selected_size);
                    $item->product->setAttribute('variant_title', $item->variant_title);

                    $item->product->selling_price = (float) $unitPrice;
                    $item->product->price = (float) $originalPrice;
                    $item->product->discount = $discountPercent;
                    $item->product->cart_quantity = $qty;
                    $item->product->qty = $qty;
                    $item->product->order_quantity = $qty;
                    $item->product->item_quantity = $qty;
                    $item->product->buy_now_quantity = $qty;
                    $item->product->available_stock = (int) ($item->product->quantity ?? 0);
                    $item->available_stock = (int) ($item->product->quantity ?? 0);

                    if ($item->product->thumbnail) {
                        $item->product->thumbnail = url('backend/assets/images/product/' . $item->product->thumbnail);
                    }

                    $galleryImages = [];
                    if (!empty($item->product->gallery)) {
                        $gallery = is_string($item->product->gallery)
                            ? json_decode($item->product->gallery, true)
                            : $item->product->gallery;

                        if (is_array($gallery)) {
                            $galleryImages = collect($gallery)
                                ->map(fn($img) => url('backend/assets/images/product/' . $img))
                                ->values()
                                ->toArray();
                        }
                    }
                    $item->product->gallery = $galleryImages;

                    $similarProducts = Product::where('category_id', $item->product->category_id)
                        ->where('id', '!=', $item->product->id)
                        ->where('status', 1)
                        ->latest()
                        ->take(5)
                        ->get()
                        ->map(function ($p) {
                            $galleryImages = [];
                            if (!empty($p->gallery)) {
                                $gallery = is_string($p->gallery) ? json_decode($p->gallery, true) : $p->gallery;
                                if (is_array($gallery)) {
                                    $galleryImages = collect($gallery)->map(fn($img) => url('backend/assets/images/product/' . $img))->values()->toArray();
                                }
                            }
                            return [
                                'id' => $p->id,
                                'category_id' => $p->category_id,
                                'subcategory_id' => $p->subcategory_id,
                                'name' => $p->name,
                                'slug' => $p->slug,
                                'thumbnail' => $p->thumbnail ? url('backend/assets/images/product/' . $p->thumbnail) : null,
                                'gallery' => $galleryImages,
                                'price' => (float) $p->price,
                                'selling_price' => (float) $p->selling_price,
                                'is_trending' => (bool) $p->is_trending,
                                'is_featured' => (bool) $p->is_featured,
                                'is_best_selling' => (bool) $p->is_best_selling,
                                'is_top_rated' => (bool) $p->is_top_rated,
                                'is_new_arrival' => (bool) $p->is_new_arrival,
                                'quantity' => $p->quantity,
                                'in_stock' => $p->quantity > 0,
                                'stock_status' => $p->quantity > 0 ? 'In Stock' : 'Out Of Stock',
                            ];
                        });

                    $allSimilarProducts = $allSimilarProducts->merge($similarProducts);
                }

                if ($item->variant) {
                    $item->variant->cart_quantity = $qty;
                    $item->variant->qty = $qty;
                    $item->variant->available_stock = (int) ($item->variant->quantity ?? 0);
                }

                return $item;
            });

            if ($mappedItems->isEmpty()) {
                $allSimilarProducts = Product::where('status', 1)
                    ->latest()
                    ->take(10)
                    ->get()
                    ->map(function ($p) {
                        $galleryImages = [];
                        if (!empty($p->gallery)) {
                            $gallery = is_string($p->gallery) ? json_decode($p->gallery, true) : $p->gallery;
                            if (is_array($gallery)) {
                                $galleryImages = collect($gallery)->map(fn($img) => url('backend/assets/images/product/' . $img))->values()->toArray();
                            }
                        }
                        return [
                            'id' => $p->id,
                            'category_id' => $p->category_id,
                            'subcategory_id' => $p->subcategory_id,
                            'name' => $p->name,
                            'slug' => $p->slug,
                            'thumbnail' => $p->thumbnail ? url('backend/assets/images/product/' . $p->thumbnail) : null,
                            'gallery' => $galleryImages,
                            'price' => (float) $p->price,
                            'selling_price' => (float) $p->selling_price,
                            'is_trending' => (bool) $p->is_trending,
                            'is_featured' => (bool) $p->is_featured,
                            'is_best_selling' => (bool) $p->is_best_selling,
                            'is_top_rated' => (bool) $p->is_top_rated,
                            'is_new_arrival' => (bool) $p->is_new_arrival,
                            'quantity' => $p->quantity,
                            'in_stock' => $p->quantity > 0,
                            'stock_status' => $p->quantity > 0 ? 'In Stock' : 'Out Of Stock',
                        ];
                    });
            }

            /*
            |--------------------------------------------------------------------------
            | SUBTOTAL
            |--------------------------------------------------------------------------
            */
            $subtotal = round($mappedItems->sum('line_total'), 2);

            /*
            |--------------------------------------------------------------------------
            | SHIPPING
            |--------------------------------------------------------------------------
            */
            $shippingCharge = (float) $this->calculateShippingCharge($subtotal);

            /*
            |--------------------------------------------------------------------------
            | COUPON
            |--------------------------------------------------------------------------
            */
            $couponCode = $request->input('coupon_code')
                ?: $request->header('coupon-code')
                ?: $request->header('coupon_code')
                ?: $request->query('coupon_code')
                ?: $items->first(fn($i) => !empty($i->coupon_code))?->coupon_code;

            $isCartCheckout = ($items->first() instanceof Cart);
            if (!$couponCode && $user) {
                $couponCode = $isCartCheckout
                    ? Cart::where('user_id', $user->id)->whereNotNull('coupon_code')->where('coupon_code', '!=', '')->latest()->value('coupon_code')
                    : BuyNow::where('user_id', $user->id)->whereNotNull('coupon_code')->where('coupon_code', '!=', '')->latest()->value('coupon_code');
            }
            if (!$couponCode && $sessionId) {
                $couponCode = $isCartCheckout
                    ? Cart::where('session_id', $sessionId)->whereNotNull('coupon_code')->where('coupon_code', '!=', '')->latest()->value('coupon_code')
                    : BuyNow::where('session_id', $sessionId)->whereNotNull('coupon_code')->where('coupon_code', '!=', '')->latest()->value('coupon_code');
            }

            $discountAmount = 0;
            $couponData = null;

            if ($couponCode && $subtotal > 0) {
                $coupon = Coupon::where('code', $couponCode)->where('status', 1)->first();
                if ($coupon) {
                    $couponData = $this->validateAndCalculateCoupon($coupon, (float) $subtotal);
                    if ($couponData) {
                        $discountAmount = (float) ($couponData['discount_amount'] ?? 0);

                        if ($isCartCheckout) {
                            if ($user) Cart::where('user_id', $user->id)->update(['coupon_code' => $coupon->code]);
                            elseif ($sessionId) Cart::where('session_id', $sessionId)->update(['coupon_code' => $coupon->code]);
                        } else {
                            if ($user) BuyNow::where('user_id', $user->id)->update(['coupon_code' => $coupon->code]);
                            elseif ($sessionId) BuyNow::where('session_id', $sessionId)->update(['coupon_code' => $coupon->code]);
                        }

                        foreach ($mappedItems as $mi) {
                            $mi->coupon_code = $coupon->code;
                        }
                    }
                }
            }

            /*
            |--------------------------------------------------------------------------
            | GRAND TOTAL
            |--------------------------------------------------------------------------
            */
            $taxable = max(0, round($subtotal - $discountAmount, 2));
            $gstPercentage = 0;
            $gstAmount = round(($taxable * $gstPercentage) / 100, 2);
            $grandTotal = round($taxable + $gstAmount + $shippingCharge, 2);

            return response()->json([
                'status' => true,
                'message' => 'Buy Now fetched successfully',
                'session_id' => $sessionId,
                'selected_address' => $selectedAddress,
                'coupon_code' => $couponData['code'] ?? null,
                'coupon' => $couponData,
                'data' => $mappedItems->values(),
                'trending_products' => $allSimilarProducts->unique('id')->values(),
                'cart_summary' => [
                    'subtotal' => round($subtotal, 2),
                    'discount_amount' => round($discountAmount, 2),
                    'amount_after_discount' => round($taxable, 2),
                    'gst_percentage' => $gstPercentage,
                    'gst_amount' => round($gstAmount, 2),
                    'shipping_charge' => round($shippingCharge, 2),
                    'grand_total' => round($grandTotal, 2),
                ],
            ])->cookie('guest_session_id', $sessionId, 60 * 24 * 30, '/', null, false, false);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }



    /**
     * Update buy now quantity
     */
    public function updateBuyNow(Request $request)
    {
        \Log::info('=== UPDATE BUY NOW REQUEST START ===');
        \Log::info('Request data:', $request->all());

        $buyNowId = $request->input('buy_now_id')
            ?? $request->input('cart_id')
            ?? $request->input('id')
            ?? $request->input('item_id');

        $validator = Validator::make($request->all(), [
            'buy_now_id' => 'nullable',
            'cart_id' => 'nullable',
            'id' => 'nullable',
            'quantity' => 'sometimes|required|integer|min:0',
            'address_id' => 'nullable|exists:addresses,id',
            'coupon_code' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $user = $this->resolveOptionalApiUser($request);
        $sessionId = $this->resolveOptionalSessionId($request, $user);

        $productId = $request->input('product_id');
        $variantId = $request->input('variant_id');

        $userKey = $user ? 'u_' . $user->id : 's_' . $sessionId;
        $activeFlow = \Illuminate\Support\Facades\Cache::get('checkout_active_flow_' . $userKey);
        if (!$activeFlow && $user && $sessionId) {
            $activeFlow = \Illuminate\Support\Facades\Cache::get('checkout_active_flow_s_' . $sessionId);
        }

        $cartViewedAt = (int) \Illuminate\Support\Facades\Cache::get('cart_viewed_at_' . $userKey, 0);
        if ($cartViewedAt === 0 && $user && $sessionId) {
            $cartViewedAt = (int) \Illuminate\Support\Facades\Cache::get('cart_viewed_at_s_' . $sessionId, 0);
        }

        $buyNowClickedAt = (int) \Illuminate\Support\Facades\Cache::get('buy_now_clicked_at_' . $userKey, 0);
        if ($buyNowClickedAt === 0 && $user && $sessionId) {
            $buyNowClickedAt = (int) \Illuminate\Support\Facades\Cache::get('buy_now_clicked_at_s_' . $sessionId, 0);
        }

        $isCartMode = ($activeFlow === 'cart') || ($cartViewedAt > 0 && $cartViewedAt >= $buyNowClickedAt);

        $buyNow = null;

        if ($isCartMode) {
            // Priority to Cart table when checking out from Cart
            if ($buyNowId) {
                $buyNow = Cart::find($buyNowId);
            }
            if (!$buyNow && $productId) {
                $buyNow = Cart::where('product_id', $productId)
                    ->when($variantId, fn($q) => $q->where('variant_id', $variantId))
                    ->when($user, fn($q) => $q->where('user_id', $user->id))
                    ->when(!$user && $sessionId, fn($q) => $q->where('session_id', $sessionId))
                    ->first();
            }
            if (!$buyNow && $buyNowId) {
                $buyNow = Cart::where('product_id', $buyNowId)
                    ->when($user, fn($q) => $q->where('user_id', $user->id))
                    ->when(!$user && $sessionId, fn($q) => $q->where('session_id', $sessionId))
                    ->first();
            }
            if (!$buyNow) {
                $buyNow = BuyNow::find($buyNowId);
            }
            if (!$buyNow && $productId) {
                $buyNow = BuyNow::where('product_id', $productId)
                    ->when($variantId, fn($q) => $q->where('variant_id', $variantId))
                    ->when($user, fn($q) => $q->where('user_id', $user->id))
                    ->when(!$user && $sessionId, fn($q) => $q->where('session_id', $sessionId))
                    ->first();
            }
        } else {
            // Priority to BuyNow table when checking out from Buy Now
            if ($buyNowId) {
                $buyNow = BuyNow::find($buyNowId);
            }
            if (!$buyNow && $productId) {
                $buyNow = BuyNow::where('product_id', $productId)
                    ->when($variantId, fn($q) => $q->where('variant_id', $variantId))
                    ->when($user, fn($q) => $q->where('user_id', $user->id))
                    ->when(!$user && $sessionId, fn($q) => $q->where('session_id', $sessionId))
                    ->first();
            }
            if (!$buyNow && $buyNowId) {
                $buyNow = BuyNow::where('product_id', $buyNowId)
                    ->when($user, fn($q) => $q->where('user_id', $user->id))
                    ->when(!$user && $sessionId, fn($q) => $q->where('session_id', $sessionId))
                    ->first();
            }
            if (!$buyNow && ($user || $sessionId)) {
                $buyNow = BuyNow::where(function ($q) use ($user, $sessionId) {
                    if ($user) $q->where('user_id', $user->id);
                    if ($sessionId) $user ? $q->orWhere('session_id', $sessionId) : $q->where('session_id', $sessionId);
                })->latest()->first();
            }
            // Fallback to Cart table
            if (!$buyNow && $buyNowId) {
                $buyNow = Cart::find($buyNowId);
            }
            if (!$buyNow && $productId) {
                $buyNow = Cart::where('product_id', $productId)
                    ->when($variantId, fn($q) => $q->where('variant_id', $variantId))
                    ->when($user, fn($q) => $q->where('user_id', $user->id))
                    ->when(!$user && $sessionId, fn($q) => $q->where('session_id', $sessionId))
                    ->first();
            }
        }

        if (!$buyNow) {
            return response()->json([
                'status' => false,
                'message' => 'Buy now item not found'
            ], 404);
        }

        // Auto-claim item to authenticated user
        if ($user && empty($buyNow->user_id)) {
            $buyNow->user_id = $user->id;
            $buyNow->save();
        }

        // If quantity is 0, delete item from its originating table only
        if ($request->has('quantity') && (int) $request->quantity === 0) {
            $removedId = $buyNow->id;
            $buyNow->delete();

            return response()->json([
                'status' => true,
                'message' => 'Item removed successfully.',
                'id' => (int) $removedId,
                'buy_now_id' => (int) $removedId,
                'cart_id' => (int) $removedId,
            ]);
        }

        // Check stock before updating quantity
        if ($request->has('quantity')) {
            $availableStock = $this->getProductAvailableStock(
                $buyNow->product_id,
                $buyNow->variant_id
            );

            if ((int) $request->quantity > $availableStock) {
                return response()->json([
                    'status' => false,
                    'message' => 'Not enough stock available. Available: ' . $availableStock,
                    'available_stock' => $availableStock,
                    'requested_quantity' => (int) $request->quantity
                ], 422);
            }
        }

        // Update fields on the item directly (without touching the other table)
        $changes = [];

        if ($request->has('quantity')) {
            $newQuantity = (int) $request->quantity;
            $oldQuantity = (int) $buyNow->quantity;
            $buyNow->quantity = $newQuantity;
            $changes['quantity'] = ['old' => $oldQuantity, 'new' => $newQuantity];
        }

        if ($request->filled('address_id')) {
            $oldAddressId = $buyNow->address_id;
            $buyNow->address_id = $request->address_id;
            $changes['address_id'] = ['old' => $oldAddressId, 'new' => $request->address_id];
        }

        if ($request->has('coupon_code')) {
            $couponCodeVal = !empty($request->coupon_code) ? $request->coupon_code : null;
            $oldCoupon = $buyNow->coupon_code;
            $buyNow->coupon_code = $couponCodeVal;
            $changes['coupon_code'] = ['old' => $oldCoupon, 'new' => $couponCodeVal];
        }

        if (empty($changes)) {
            return response()->json([
                'status' => true,
                'message' => 'No changes to update'
            ]);
        }

        $buyNow->save();
        $buyNow->refresh();
        $buyNow->load(['product', 'variant', 'address']);

        $price = $this->getBuyNowPrice($buyNow);
        $subtotal = $price * (int) $buyNow->quantity;
        $availableStock = $this->getProductAvailableStock(
            $buyNow->product_id,
            $buyNow->variant_id
        );

        $upArch = $buyNow->variant?->arch;
        $upSize = $buyNow->variant?->size !== null && $buyNow->variant?->size !== '' ? (string) $buyNow->variant->size : null;
        $upVariantTitle = $buyNow->variant ? ($buyNow->variant->variant_title ?? trim(($upArch ?? '') . ' ' . ($upSize ? 'Size ' . $upSize : ''))) : null;
        $upSku = $buyNow->variant?->sku ?: ($buyNow->product->sku ?? null);

        return response()->json([
            'status' => true,
            'message' => 'Buy now updated successfully',
            'changes' => $changes,
            'data' => [
                'id' => $buyNow->id,
                'buy_now_id' => $buyNow->id,
                'cart_id' => $buyNow->id,
                'quantity' => (int) $buyNow->quantity,
                'qty' => (int) $buyNow->quantity,
                'cart_quantity' => (int) $buyNow->quantity,
                'address_id' => $buyNow->address_id,
                'arch' => $upArch,
                'size' => $upSize,
                'selected_arch' => $upArch,
                'selected_size' => $upSize,
                'variant_title' => $upVariantTitle,
                'sku' => $upSku,
                'price' => (float) $price,
                'subtotal' => round($subtotal, 2),
                'available_stock' => $availableStock,
                'in_stock' => $availableStock > 0,
                'variant' => $buyNow->variant ? [
                    'id' => $buyNow->variant->id,
                    'sku' => $upSku,
                    'arch' => $upArch,
                    'size' => $upSize,
                    'selected_arch' => $upArch,
                    'selected_size' => $upSize,
                    'variant_title' => $upVariantTitle,
                    'price' => (float) $buyNow->variant->price,
                    'selling_price' => (float) ($buyNow->variant->selling_price ?? $buyNow->variant->price),
                ] : null,
                'product' => $buyNow->product ? [
                    'id' => $buyNow->product->id,
                    'name' => $buyNow->product->name,
                    'slug' => $buyNow->product->slug,
                    'arch' => $upArch,
                    'size' => $upSize,
                    'selected_arch' => $upArch,
                    'selected_size' => $upSize,
                    'variant_title' => $upVariantTitle,
                    'sku' => $upSku,
                    'cart_quantity' => (int) $buyNow->quantity,
                    'qty' => (int) $buyNow->quantity,
                    'thumbnail' => $buyNow->product->thumbnail
                        ? url('backend/assets/images/product/' . $buyNow->product->thumbnail)
                        : null,
                ] : null
            ]
        ]);
    }

    public function updateBuyNowAddress(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'address_id' => 'required|exists:addresses,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $user = auth('sanctum')->user() ?? auth()->user();
        if (!$user && $request->bearerToken()) {
            $accessToken = PersonalAccessToken::findToken($request->bearerToken());
            if ($accessToken) {
                $user = $accessToken->tokenable;
            }
        }
        if (!$user && $request->has('user_id') && !empty($request->user_id)) {
            $user = \App\Models\User::find($request->user_id);
        }

        $sessionId = $request->header('session-id')
            ?: $request->header('Session-Id')
            ?: $request->header('session_id')
            ?: $request->input('session_id')
            ?: $request->input('sessionId')
            ?: $request->input('session-id');

        $itemBuyNowId = $request->input('buy_now_id') ?: $request->input('cart_id') ?: $request->input('id');

        if ($user && $sessionId) {
            BuyNow::where(function ($q) use ($user, $sessionId) {
                $q->where('user_id', $user->id)->orWhere('session_id', $sessionId);
            })->update(['address_id' => $request->address_id]);

            Cart::where(function ($q) use ($user, $sessionId) {
                $q->where('user_id', $user->id)->orWhere('session_id', $sessionId);
            })->update(['address_id' => $request->address_id]);
        } elseif ($user) {
            BuyNow::where('user_id', $user->id)->update(['address_id' => $request->address_id]);
            Cart::where('user_id', $user->id)->update(['address_id' => $request->address_id]);
        } elseif ($sessionId) {
            BuyNow::where('session_id', $sessionId)->update(['address_id' => $request->address_id]);
            Cart::where('session_id', $sessionId)->update(['address_id' => $request->address_id]);
        }

        if ($itemBuyNowId) {
            BuyNow::where('id', $itemBuyNowId)->update(['address_id' => $request->address_id]);
            Cart::where('id', $itemBuyNowId)->update(['address_id' => $request->address_id]);
        }

        return response()->json([
            'status' => true,
            'message' => 'BuyNow address updated successfully'
        ]);
    }

    /**
     * Process buy now checkout - creates order and automatically clears buy now
     */
    public function buyNowCheckout(Request $request)
    {
        DB::beginTransaction();

        try {
            $user = $this->resolveOptionalApiUser($request);
            $sessionId = $this->resolveOptionalSessionId($request, $user);

            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'Please login to proceed with checkout'
                ], 401);
            }

            // 1. Fetch BuyNow items
            $buyNowQuery = BuyNow::with(['product', 'variant.attributes.attribute', 'variant.attributes.attributeValue']);
            if ($user && $sessionId) {
                $buyNowQuery->where(function ($q) use ($user, $sessionId) {
                    $q->where('user_id', $user->id)->orWhere('session_id', $sessionId);
                });
            } elseif ($user) {
                $buyNowQuery->where('user_id', $user->id);
            } elseif ($sessionId) {
                $buyNowQuery->where('session_id', $sessionId);
            } else {
                $buyNowQuery->whereRaw('1 = 0');
            }
            $buyNowItems = $buyNowQuery->orderBy('id', 'desc')->get()
                ->filter(fn($item) => $item->product !== null)->values();

            // 2. Fetch Cart items (in case checking out from Cart)
            $cartQuery = Cart::with(['product', 'variant.attributes.attribute', 'variant.attributes.attributeValue']);
            if ($user && $sessionId) {
                $cartQuery->where(function ($q) use ($user, $sessionId) {
                    $q->where('user_id', $user->id)->orWhere('session_id', $sessionId);
                });
            } elseif ($user) {
                $cartQuery->where('user_id', $user->id);
            } elseif ($sessionId) {
                $cartQuery->where('session_id', $sessionId);
            } else {
                $cartQuery->whereRaw('1 = 0');
            }
            $cartItems = $cartQuery->orderBy('id', 'desc')->get()
                ->filter(fn($item) => $item->product !== null)->values();

            // Resolve which collection to checkout
            $userKey = $user ? 'u_' . $user->id : 's_' . $sessionId;
            $activeFlow = \Illuminate\Support\Facades\Cache::get('checkout_active_flow_' . $userKey);
            if (!$activeFlow && $user && $sessionId) {
                $activeFlow = \Illuminate\Support\Facades\Cache::get('checkout_active_flow_s_' . $sessionId);
            }

            $cartViewedAt = (int) \Illuminate\Support\Facades\Cache::get('cart_viewed_at_' . $userKey, 0);
            if ($cartViewedAt === 0 && $user && $sessionId) {
                $cartViewedAt = (int) \Illuminate\Support\Facades\Cache::get('cart_viewed_at_s_' . $sessionId, 0);
            }

            $buyNowClickedAt = (int) \Illuminate\Support\Facades\Cache::get('buy_now_clicked_at_' . $userKey, 0);
            if ($buyNowClickedAt === 0 && $user && $sessionId) {
                $buyNowClickedAt = (int) \Illuminate\Support\Facades\Cache::get('buy_now_clicked_at_s_' . $sessionId, 0);
            }

            $source = $request->input('source')
                ?: $request->input('type')
                ?: $request->input('checkout_type')
                ?: $request->query('source')
                ?: $request->query('type');

            $referer = (string) ($request->header('referer') ?: $request->header('Referer') ?: '');

            $latestBuyNow = $buyNowItems->sortByDesc('updated_at')->first();
            $latestCart = $cartItems->sortByDesc('updated_at')->first();
            $latestBuyNowTime = $latestBuyNow ? ($latestBuyNow->updated_at ? $latestBuyNow->updated_at->timestamp : 0) : 0;
            $latestCartTime = $latestCart ? ($latestCart->updated_at ? $latestCart->updated_at->timestamp : 0) : 0;

            $isCartCheckout = false;
            if ($source === 'cart' || str_contains(strtolower($referer), 'my-cart') || str_contains(strtolower($referer), '/cart')) {
                $isCartCheckout = true;
            } elseif ($source === 'buy_now') {
                $isCartCheckout = false;
            } elseif ($activeFlow === 'buy_now' && ($buyNowClickedAt > 0 && $buyNowClickedAt >= $cartViewedAt)) {
                $isCartCheckout = false;
            } elseif ($activeFlow === 'cart' && ($cartViewedAt > 0 && $cartViewedAt >= $buyNowClickedAt)) {
                $isCartCheckout = true;
            } elseif ($latestCartTime > 0 && $latestCartTime > $latestBuyNowTime) {
                $isCartCheckout = true;
            } elseif ($buyNowItems->isNotEmpty()) {
                $isCartCheckout = false;
            } elseif ($activeFlow === 'cart' || $cartItems->isNotEmpty()) {
                $isCartCheckout = true;
            } else {
                $isCartCheckout = false;
            }

            if ($isCartCheckout) {
                $items = $cartItems;
            } else {
                $items = $buyNowItems;
            }

            if ($items->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => 'No items found for checkout'
                ], 404);
            }

            // Resolve delivery address
            $addressId = $request->input('address_id')
                ?? $items->first()?->address_id
                ?? Address::where('user_id', $user->id)->latest()->value('id');

            if (!$addressId) {
                return response()->json([
                    'status' => false,
                    'message' => 'Please select or add a delivery address to proceed with checkout'
                ], 422);
            }

            $address = Address::where('id', $addressId)
                ->where('user_id', $user->id)
                ->first();

            if (!$address) {
                $address = Address::where('id', $addressId)->first();
            }

            if (!$address) {
                return response()->json([
                    'status' => false,
                    'message' => 'Selected address not found or does not belong to user'
                ], 404);
            }

            // Verify stock and calculate total subtotal
            $subtotal = 0;
            foreach ($items as $checkoutItem) {
                $product = $checkoutItem->product;
                if (!$product) {
                    continue;
                }

                $availableStock = $this->getProductAvailableStock($checkoutItem->product_id, $checkoutItem->variant_id);
                if ($availableStock < $checkoutItem->quantity) {
                    $productName = $product->name . ($checkoutItem->variant ? ' (' . ($checkoutItem->variant->sku ?? 'Variant') . ')' : '');
                    return response()->json([
                        'status' => false,
                        'message' => 'Insufficient stock for ' . $productName,
                        'available_stock' => $availableStock
                    ], 400);
                }

                $itemPrice = (float) $this->getBuyNowPrice($checkoutItem);
                $subtotal += ($itemPrice * (int) $checkoutItem->quantity);
            }
            $subtotal = round($subtotal, 2);

            // Coupon discount calculation
            $discountAmount = 0;
            $couponCode = null;
            $couponCodeToUse = $request->input('coupon_code')
                ?: $items->first()?->coupon_code;

            if (!empty($couponCodeToUse)) {
                $coupon = Coupon::where('code', $couponCodeToUse)
                    ->where('status', 1)
                    ->first();

                if ($coupon) {
                    $couponData = $this->validateAndCalculateCoupon($coupon, (float) $subtotal);
                    if ($couponData) {
                        $discountAmount = (float) ($couponData['discount_amount'] ?? 0);
                        $couponCode = $coupon->code;
                        $coupon->increment('used_count');
                    }
                }
            }

            // GST calculation (Applied AFTER Discount)
            $gstPercentage = 0;
            $amountAfterDiscount = max(0, $subtotal - $discountAmount);
            $gstAmount = round(($amountAfterDiscount * $gstPercentage) / 100, 2);
            $totalWithGst = $amountAfterDiscount + $gstAmount;

            $shippingCharge = (float) $this->calculateShippingCharge($subtotal);
            $grandTotal = round($totalWithGst + $shippingCharge, 2);

            // Create order
            $order = Order::create([
                'user_id' => $user->id,
                'order_id' => Order::temporaryOrderNumber(),
                'subtotal' => $subtotal,
                'discount' => $discountAmount,
                'coupon_discount' => $discountAmount,
                'coupon_code' => $couponCode,
                'shipping_charge' => $shippingCharge,
                'gst_rate' => $gstPercentage,
                'cgst_amount' => round($gstAmount / 2, 2),
                'sgst_amount' => round($gstAmount / 2, 2),
                'igst_amount' => 0,
                'taxable_amount' => max(0, round($amountAfterDiscount - $gstAmount, 2)),
                'grand_total' => $grandTotal,
                'paid_amount' => 0,
                'due_amount' => $grandTotal,
                'payment_method' => $request->payment_method,
                'payment_type' => 'full',
                'payment_status' => 'pending',
                'order_status' => strtolower($request->payment_method) === 'cod' ? 'processing' : 'pending',
                'invoice_date' => now(),
                'order_source' => 'app',
            ]);

            // Save order addresses (shipping & billing)
            $formattedAddress = $address->formatted_address ?? (
                $address->address ?: trim(($address->flat ?? '') . ', ' . ($address->area ?? ''), ', ')
            );

            $addressPayload = [
                'order_id' => $order->id,
                'name' => $address->name ?? ($user->name ?? 'Customer'),
                'phone' => $address->phone ?? ($user->phone ?? ''),
                'alternate_phone' => $address->alternate_phone ?? null,
                'company_name' => $address->company_name ?? null,
                'gstin' => $address->gstin ?? null,
                'address' => $formattedAddress ?: 'Address',
                'landmark' => $address->landmark ?? null,
                'city' => $address->city ?? '',
                'state' => $address->state ?? '',
                'country' => $address->country ?? 'India',
                'pincode' => $address->pincode ?? '',
            ];

            OrderAddress::create(array_merge($addressPayload, ['type' => 'shipping']));
            OrderAddress::create(array_merge($addressPayload, ['type' => 'billing']));

            // Create order item for each checkout item
            foreach ($items as $checkoutItem) {
                $itemPrice = (float) $this->getBuyNowPrice($checkoutItem);
                $var = $checkoutItem->variant ?: ($checkoutItem->variant_id ? ProductVariant::find($checkoutItem->variant_id) : null);
                $prod = $checkoutItem->product ?: Product::find($checkoutItem->product_id);
                $arch = $checkoutItem->arch ?: ($var?->arch ?: null);
                $size = $checkoutItem->size !== null && $checkoutItem->size !== '' ? (string) $checkoutItem->size : ($var?->size !== null && $var?->size !== '' ? (string) $var->size : null);
                $sku = $var?->sku ?: ($prod?->sku ?? null);

                OrderItem::create([
                    'order_id'      => $order->id,
                    'product_id'    => $checkoutItem->product_id,
                    'variant_id'    => $checkoutItem->variant_id,
                    'product_name'  => $prod?->name,
                    'product_image' => $prod?->thumbnail,
                    'product_sku'   => $sku,
                    'arch'          => $arch,
                    'size'          => $size,
                    'quantity'      => $checkoutItem->quantity,
                    'price'         => $itemPrice,
                    'total'         => round($itemPrice * $checkoutItem->quantity, 2),
                ]);

                // Reduce stock (variant or product)
                if ($checkoutItem->variant_id) {
                    DB::table('product_variants')
                        ->where('id', $checkoutItem->variant_id)
                        ->decrement('quantity', $checkoutItem->quantity);
                } else {
                    $checkoutItem->product?->decrement('quantity', $checkoutItem->quantity);
                }

                // Delete checkout item from its originating table only (preserves the other table intact)
                $checkoutItem->delete();
            }

            $order->assignOrderNumber();

            DB::commit();

            try {
                $cacheKey = 'checkout_mode_' . ($user ? 'u_' . $user->id : 's_' . $sessionId);
                \Illuminate\Support\Facades\Cache::forget($cacheKey);
                if ($userKey) {
                    \Illuminate\Support\Facades\Cache::forget('checkout_active_flow_' . $userKey);
                    \Illuminate\Support\Facades\Cache::forget('cart_viewed_at_' . $userKey);
                    \Illuminate\Support\Facades\Cache::forget('buy_now_clicked_at_' . $userKey);
                }
            } catch (\Throwable $e) {
            }

            return response()->json([
                'status' => true,
                'message' => 'Order placed successfully',
                'data' => [
                    'order_id' => $order->id,
                    'order_number' => $order->display_order_id,
                    'subtotal' => round($subtotal, 2),
                    'discount_amount' => $discountAmount,
                    'gst_amount' => round($gstAmount, 2),
                    'shipping_charge' => $shippingCharge,
                    'grand_total' => round($grandTotal, 2),
                    'payment_method' => $request->payment_method,
                    'coupon_applied' => $couponCode
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    private function calculateShippingCharge(float $subtotal): float
    {
        $shippingSetting = ShippingSetting::where('status', 1)->first();

        if (!$shippingSetting) {
            return 0;
        }

        return $subtotal >= $shippingSetting->free_shipping_amount
            ? 0
            : (float) $shippingSetting->shipping_charge;
    }

    /**
     * Helper method to get price (product selling price or variant price)
     */
    private function getBuyNowPrice($buyNow)
    {
        if ($buyNow->variant) {
            $vSelling = (float) ($buyNow->variant->selling_price ?? 0);
            $vPrice = (float) ($buyNow->variant->price ?? 0);
            $pSelling = (float) ($buyNow->product->selling_price ?? 0);
            $pPrice = (float) ($buyNow->product->price ?? 0);

            if ($vSelling > 0) return $vSelling;
            if ($vPrice > 0) return $vPrice;
            if ($pSelling > 0) return $pSelling;
            if ($pPrice > 0) return $pPrice;
            return 0;
        }

        $pSelling = (float) ($buyNow->product->selling_price ?? 0);
        $pPrice = (float) ($buyNow->product->price ?? 0);

        if ($pSelling > 0) return $pSelling;
        if ($pPrice > 0) return $pPrice;
        return 0;
    }
    public function removeCouponFromBuyNow(Request $request)
    {
        $request->merge(['source' => 'buy_now']);
        return $this->removeCoupon($request);
    }

    /**
     * Apply coupon to buy now item (alias to applyCoupon)
     */
    public function applyCouponToBuyNow(Request $request)
    {
        $request->merge(['source' => 'buy_now']);
        return $this->applyCoupon($request);
    }

    public function emails(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:newsletters,email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $newsletter = Newsletter::create([
            'email' => $request->email,
            'status' => 1,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Newsletter subscribed successfully',
            'data' => $newsletter
        ]);
    }
    public function addProductQuestion(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'user_id' => 'required|exists:users,id',
            'question' => 'required|string|max:1000',
        ]);

        $faq = ProductFaq::create([
            'product_id' => $request->product_id,
            'user_id' => $request->user_id,
            'question' => $request->question,
            'answer' => null,
            'order' => 0,
            'status' => 0,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Question submitted successfully.',
            'data' => $faq
        ]);
    }

    /**
     * Validate and calculate coupon discount for cart or buy-now subtotal
     */
    private function validateAndCalculateCoupon(Coupon $coupon, float $subtotal): ?array
    {
        if ((int) $coupon->status !== 1) {
            return null;
        }

        $today = now()->toDateString();
        $startDate = $coupon->start_date ? \Illuminate\Support\Carbon::parse($coupon->start_date)->toDateString() : null;
        $endDate = $coupon->end_date ? \Illuminate\Support\Carbon::parse($coupon->end_date)->toDateString() : null;

        if ($startDate && $today < $startDate) {
            return null;
        }

        if ($endDate && $today > $endDate) {
            return null;
        }

        if (!empty($coupon->usage_limit) && $coupon->used_count >= $coupon->usage_limit) {
            return null;
        }

        if (!empty($coupon->minimum_amount) && $subtotal < (float) $coupon->minimum_amount) {
            return null;
        }

        $discount = 0;
        if ($coupon->type === 'percent') {
            $discount = ($subtotal * (float) $coupon->value) / 100;
            if (!empty($coupon->maximum_discount) && $discount > (float) $coupon->maximum_discount) {
                $discount = (float) $coupon->maximum_discount;
            }
        } else {
            $discount = min((float) $coupon->value, $subtotal);
        }

        $discountAmount = round((float) $discount, 2);

        return $this->formatCouponForApi($coupon, [
            'discount_amount' => $discountAmount,
        ]);
    }

    private function formatCouponForApi(Coupon $coupon, array $extra = []): array
    {
        $categoryIds = $this->resolveCouponCategoryIds($coupon);
        $productIds = $this->resolveCouponProductIds($coupon);

        $categories = $this->mapCouponItemsByIds(Category::class, $categoryIds);
        $products = $this->mapCouponItemsByIds(Product::class, $productIds);

        $categoryNames = array_column($categories, 'name');
        $productNames = array_column($products, 'name');

        $hasInclude = !empty($categoryIds) || !empty($productIds);

        if (!$hasInclude) {
            $type = 'all';
            $label = 'All products';
            $applyOnText = 'All products';
        } elseif (!empty($productIds) && empty($categoryIds)) {
            $type = 'products';
            $applyOnText = 'Products: ' . implode(', ', $productNames);
            $label = $applyOnText;
        } elseif (!empty($categoryIds) && empty($productIds)) {
            $type = 'categories';
            $applyOnText = 'Categories: ' . implode(', ', $categoryNames);
            $label = $applyOnText;
        } else {
            $type = 'specific';
            $parts = [];
            if (!empty($categoryNames)) {
                $parts[] = 'Categories: ' . implode(', ', $categoryNames);
            }
            if (!empty($productNames)) {
                $parts[] = 'Products: ' . implode(', ', $productNames);
            }
            $applyOnText = implode(' | ', $parts);
            $label = $applyOnText;
        }

        return array_merge([
            'id' => $coupon->id,
            'code' => $coupon->code,
            'type' => $coupon->type,
            'value' => $coupon->value,
            'minimum_amount' => $coupon->minimum_amount,
            'maximum_discount' => $coupon->maximum_discount,
            'start_date' => $coupon->start_date ? $coupon->start_date->format('Y-m-d') : null,
            'end_date' => $coupon->end_date ? $coupon->end_date->format('Y-m-d') : null,
            'applicability' => [
                'type' => $type,
                'label' => $label,
                'apply_on_text' => $applyOnText,
                'categories' => $categories,
                'products' => $products,
                'category_names' => $categoryNames,
                'product_names' => $productNames,
            ],
        ], $extra);
    }

    private function resolveCouponCategoryIds(Coupon $coupon): array
    {
        $ids = $this->normalizeCouponIds($coupon->category_ids);

        if (!empty($ids)) {
            return $ids;
        }

        if (Schema::hasTable('coupon_category')) {
            return DB::table('coupon_category')
                ->where('coupon_id', $coupon->id)
                ->pluck('category_id')
                ->map(fn($id) => (int) $id)
                ->unique()
                ->values()
                ->all();
        }

        return [];
    }

    private function resolveCouponProductIds(Coupon $coupon): array
    {
        return $this->normalizeCouponIds($coupon->product_ids);
    }

    private function mapCouponItemsByIds(string $modelClass, array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        return $modelClass::whereIn('id', $ids)
            ->get(['id', 'name'])
            ->map(fn($item) => ['id' => $item->id, 'name' => $item->name])
            ->values()
            ->all();
    }

    private function normalizeCouponIds($ids): array
    {
        if ($ids === null || $ids === '' || $ids === '[]') {
            return [];
        }

        if (is_string($ids)) {
            $decoded = json_decode($ids, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $ids = $decoded;
            } elseif (str_contains($ids, ',')) {
                $ids = explode(',', $ids);
            } elseif (is_numeric($ids)) {
                $ids = [$ids];
            } else {
                return [];
            }
        }

        if (is_object($ids)) {
            $ids = (array) $ids;
        }

        return array_values(array_unique(array_filter(array_map('intval', (array) $ids))));
    }

    public function whyChooseUs()
    {
        try {
            $items = [];
            if (Schema::hasTable('why_choose_us')) {
                $items = WhyChooseUs::where('status', 1)
                    ->orderBy('sort_order', 'asc')
                    ->orderBy('id', 'asc')
                    ->get()
                    ->map(function ($item) {
                        $item->image = $item->image ? url('backend/assets/images/why-choose-us/' . $item->image) : null;
                        return $item;
                    });
            }

            return response()->json([
                'status' => true,
                'message' => 'Why choose us fetched successfully',
                'data' => [
                    'badge' => 'Why Choose Us',
                    'title' => 'Why Partner With Kids-e-Dental?',
                    'items' => $items,
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
