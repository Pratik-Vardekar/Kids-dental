<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Slider;
use App\Models\Product;
use App\Models\SubCategory;
use App\Models\Blog;

class ProductController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Home API
    |--------------------------------------------------------------------------
    */

    public function home()
{
    // Categories
    $categories = Category::where('status', 1)
        ->orderBy('id', 'DESC')
        ->get()
        ->map(function ($category) {

            $category->image = url('backend/assets/images/category/' . $category->image);

            return $category;
        });

    // Sliders
   $sliders = Slider::where('status', 1)
    ->orderBy('id', 'DESC')
    ->get()
    ->map(function ($slider) {

        $slider->image = url('backend/assets/images/slider/' . $slider->image);

        return $slider;
    });
    // Blogs
  $blogs = Blog::where('status', 1)
    ->orderBy('id', 'DESC')
    ->get()
    ->map(function ($blog) {

        $blog->image = url('backend/assets/images/blog/' . $blog->image);

        return $blog;
    });

    // Products grouped by category name
    $categoryProducts = [];

    $allCategories = Category::where('status', 1)
        ->with(['products' => function ($query) {
            $query->where('status', 1)
                ->with('subcategory')
                ->latest();
        }])
        ->orderBy('id', 'DESC')
        ->get();
foreach ($allCategories as $category) {

    $categoryProducts[$category->name] = $category->products->map(function ($product) {

        return [
            'id'              => $product->id,
            'category_id'     => $product->category_id,
            'subcategory_id'  => $product->subcategory_id,
            'name'            => $product->name,
            'slug'            => $product->slug,

            'thumbnail'       => url('backend/assets/images/product/' . $product->thumbnail),

            'price'           => $product->price,
            'selling_price'   => $product->selling_price,
            'details'         => $product->details,
            'description'     => $product->description,
            'specification'   => $product->specification,
            'quantity'        => $product->quantity,
            'gallery' => collect($product->gallery)->map(function ($image) {
    return url('backend/assets/images/product/' . $image);
}),

'gallery_order' => collect($product->gallery_order)->map(function ($image) {
    return url('backend/assets/images/product/' . $image);
}),
            'status'          => $product->status,
            'is_trending'     => $product->is_trending,
            'is_featured'     => $product->is_featured,
            'is_best_selling' => $product->is_best_selling,
            'is_top_rated'    => $product->is_top_rated,
            'is_new_arrival'  => $product->is_new_arrival,
            'badge'           => $product->badge,
            'discount'        => $product->discount,
            'subcategory'     => $product->subcategory,
            'average_rating'  => $product->averageRating(),
            'total_reviews'   => $product->totalReviews(),
        ];
    });
}
    return response()->json([
        'status'  => true,
        'message' => 'Home data fetched successfully',
        'data'    => [
            'categories' => $categories,
            'sliders'    => $sliders,
            'products'   => $categoryProducts,
            'blogs'      => $blogs,
        ]
    ]);
}
public function duplicate(Product $product)
{
    // =========================================
    // DUPLICATE THUMBNAIL
    // =========================================

    $thumbnail = null;

    if (
        $product->thumbnail &&
        file_exists(
            public_path('backend/assets/images/product/' . $product->thumbnail)
        )
    ) {

        $thumbPath = public_path(
            'backend/assets/images/product/' . $product->thumbnail
        );

        $thumbExt = pathinfo($thumbPath, PATHINFO_EXTENSION);

        $thumbnail = time() . '_copy_thumb.' . $thumbExt;

        copy(
            $thumbPath,
            public_path('backend/assets/images/product/' . $thumbnail)
        );
    }

    // =========================================
    // DUPLICATE GALLERY
    // =========================================

    $gallery = [];

    if ($product->gallery) {

        $images = is_array($product->gallery)
            ? $product->gallery
            : json_decode($product->gallery, true);

        foreach ($images as $image) {

            $oldPath = public_path(
                'backend/assets/images/product/' . $image
            );

            if (file_exists($oldPath)) {

                $ext = pathinfo($oldPath, PATHINFO_EXTENSION);

                $newImage = time() . rand(111,999) . '.' . $ext;

                copy(
                    $oldPath,
                    public_path('backend/assets/images/product/' . $newImage)
                );

                $gallery[] = $newImage;
            }
        }
    }

    // =========================================
    // CREATE DUPLICATE PRODUCT
    // =========================================

    Product::create([

        'category_id'     => $product->category_id,

        'subcategory_id'  => $product->subcategory_id,

        'name'            => $product->name . ' Copy',

        'slug'            => Str::slug($product->slug . '-copy-' . time()),

        'sku'             => $product->sku . '-COPY-' . rand(100,999),

        'thumbnail'       => $thumbnail,

        'price'           => $product->price,

        'selling_price'   => $product->selling_price,

        'details'         => $product->details,

        'description'     => $product->description,

        'specification'   => $product->specification,

        'quantity'        => $product->quantity,

        'gallery'         => $gallery,

        'gallery_order'   => $gallery,

        'status'          => 0,

        'is_trending'     => $product->is_trending,

        'is_featured'     => $product->is_featured,

        'is_best_selling' => $product->is_best_selling,

        'is_top_rated'    => $product->is_top_rated,

        'is_new_arrival'  => $product->is_new_arrival,

        'badge'           => $product->badge,

        'discount'        => $product->discount,
    ]);

    return redirect()
        ->route('product.index')
        ->with('success', 'Product duplicated successfully');
}
    /*
    |--------------------------------------------------------------------------
    | Categories
    |--------------------------------------------------------------------------
    */

   public function categories()
{
    $categories = Category::where('status', 1)
        ->latest()
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

    /*
    |--------------------------------------------------------------------------
    | Subcategories
    |--------------------------------------------------------------------------
    */

    public function subcategories()
    {
        $subcategories = SubCategory::with('category')
            ->where('status', 1)
            ->get();

        return response()->json([
            'status' => true,
            'message' => 'Subcategories with status 1 shown successfully',
            'data' => $subcategories
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Sliders
    |--------------------------------------------------------------------------
    */

    public function sliders()
    {
        $sliders = Slider::where('status', 1)->latest()->get();

        return response()->json([
            'status' => true,
            'message' => 'Sliders fetched successfully',
            'data' => $sliders
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Product List
    |--------------------------------------------------------------------------
    */

    public function index()
    {
        $products = Product::with(['category', 'subcategory'])
            ->where('status', 1)
            ->get();

        return response()->json([
            'status' => true,
            'message' => 'Products with status 1 fetched successfully',
            'data' => $products
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Product Details Without Subcategory
    |--------------------------------------------------------------------------
    */

    public function showWithoutSubcategory($category_slug, $slug)
    {
        $product = Product::with(['category', 'subcategory', 'reviews'])
            ->where('slug', $slug)
            ->whereHas('category', function ($q) use ($category_slug) {
                $q->where('slug', $category_slug);
            })
            ->first();

        if (!$product) {
            return response()->json([
                'status' => false,
                'message' => 'Product not found'
            ], 404);
        }

        return $this->productResponse($product);
    }

    /*
    |--------------------------------------------------------------------------
    | Product Details With Subcategory
    |--------------------------------------------------------------------------
    */

    public function showWithSubcategory($category_slug, $subcategory_slug, $slug)
    {
        $product = Product::with(['category', 'subcategory', 'reviews'])
            ->where('slug', $slug)
            ->whereHas('category', function ($q) use ($category_slug) {
                $q->where('slug', $category_slug);
            })
            ->whereHas('subcategory', function ($q) use ($subcategory_slug) {
                $q->where('slug', $subcategory_slug);
            })
            ->first();

        if (!$product) {
            return response()->json([
                'status' => false,
                'message' => 'Product not found'
            ], 404);
        }

        return $this->productResponse($product);
    }

    /*
    |--------------------------------------------------------------------------
    | Product Response
    |--------------------------------------------------------------------------
    */

    private function productResponse($product)
    {
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
                'discount' => $product->discount,
                'category' => $product->category,
                'subcategory' => $product->subcategory,
                'reviews' => $product->reviews,
                'created_at' => $product->created_at,
                'updated_at' => $product->updated_at
            ],
            'average_rating' => $product->averageRating(),
            'total_reviews' => $product->totalReviews()
        ]);
    }

    public function blogs()
    {
        $blogs = Blog::where('status', 1)->latest()->get();

        return response()->json([
            'status' => true,
            'message' => 'Blogs fetched successfully',
            'data' => $blogs
        ], 200);
    }
    
    public function blogDetails($id)
    {
        $blog = Blog::where('id', $id)
            ->where('status', 1)
            ->first();
    
        if (!$blog) {
            return response()->json([
                'status' => false,
                'message' => 'Blog not found'
            ], 404);
        }
    
        return response()->json([
            'status' => true,
            'message' => 'Blog fetched successfully',
            'data' => [
                'id' => $blog->id,
                'title' => $blog->title,
                'slug' => $blog->slug,
                'category' => $blog->category,
                'image' => $blog->image,
                'author' => $blog->author,
                'blog_date' => $blog->blog_date,
                'short_description' => $blog->short_description,
                'description' => $blog->description,
                'status' => $blog->status,
                'created_at' => $blog->created_at,
                'updated_at' => $blog->updated_at
            ]
        ], 200);
    }
}