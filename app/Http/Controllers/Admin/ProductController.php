<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductAttributeValue;
use App\Models\ProductFaq;
use App\Models\ProductVariant;
use App\Models\ProductVariantAttribute;
use App\Models\SubCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with('category');

        if ($request->search) {

            $query->where(function ($q) use ($request) {

                $q->where('name', 'LIKE', '%' . $request->search . '%')
                  ->orWhere('sku', 'LIKE', '%' . $request->search . '%');

            });
        }

        if ($request->category_id) {

            $query->where('category_id', $request->category_id);

        }

        if ($request->status !== null && $request->status !== '') {

            $query->where('status', $request->status);

        }

        if ($request->date) {

            $query->whereDate('created_at', $request->date);

        }

        $products = $query->latest()->paginate(10);

        $categories = Category::where('status', 1)->get();

        return view(
            'admin.product.index',
            compact('products', 'categories')
        );
    }

    public function create()
    {
        $categories = Category::where('status', 1)->get();

        return view(
            'admin.product.add',
            compact('categories')
        );
    }

    public function getSubcategories($category_id)
    {
        $subcategories = SubCategory::where('category_id', $category_id)
            ->where('status', 1)
            ->get();

        return response()->json($subcategories);
    }

    public function store(Request $request)
    {
        $request->validate([

            'category_id'   => 'required',

            'name'          => 'required',

            'slug'          => 'required|unique:products,slug',

            'sku'           => 'required|unique:products,sku',

            'price'         => 'required|numeric',

            'selling_price' => 'required|numeric',

            'quantity'      => 'required|numeric',

            'details'       => 'required',

            'description'   => 'required',

            'specification' => 'required',

            'thumbnail'     => 'required|image|mimes:jpg,jpeg,png,webp',

            'gallery'       => 'required|array|min:1',

            'gallery.*'     => 'required|image|mimes:jpg,jpeg,png,webp',

            'product_type'  => 'required|in:simple,variable',

            'variants' => 'nullable|array',

            'variants.*.sku' => 'nullable',

            'variants.*.price' => 'nullable|numeric',

            'variants.*.selling_price' => 'nullable|numeric',

            'variants.*.quantity' => 'nullable|numeric',

            'variants.*.image' => 'nullable|image|mimes:jpg,jpeg,png,webp',

            'variants.*.attributes' => 'nullable|array',

            'variants.*.attributes.*.attribute' =>
                'required_if:product_type,variable',

            'variants.*.attributes.*.value' =>
                'required_if:product_type,variable',
                  'faqs' => 'nullable|array',
        'faqs.*.question' => 'required_with:faqs.*|string|max:500',
        'faqs.*.answer' => 'required_with:faqs.*|string',

        ]);

        DB::beginTransaction();

        try {

            // =========================================
            // THUMBNAIL
            // =========================================

            $thumbnail = null;

            if ($request->hasFile('thumbnail')) {

                $thumb = $request->file('thumbnail');

                $thumbnail = time() . '_thumb.' . $thumb->extension();

                $thumb->move(
                    public_path('backend/assets/images/product'),
                    $thumbnail
                );
            }

            // =========================================
            // GALLERY
            // =========================================

            $gallery = [];

            if ($request->hasFile('gallery')) {

                foreach ($request->file('gallery') as $image) {

                    $imageName = time() . rand(111,999) . '.' . $image->extension();

                    $image->move(
                        public_path('backend/assets/images/product'),
                        $imageName
                    );

                    $gallery[] = $imageName;
                }
            }

            // =========================================
            // PRODUCT SAVE
            // =========================================

            $product = Product::create([

                'category_id'     => $request->category_id,

                'subcategory_id'  => $request->subcategory_id,

                'name'            => $request->name,

                'slug'            => Str::slug($request->slug),

                'sku'             => $request->sku,

                'product_type'    => $request->product_type,

                'thumbnail'       => $thumbnail,

                'price'           => $request->price,

                'selling_price'   => $request->selling_price,

                'details'         => $request->details,

                'description'     => $request->description,

                'specification'   => $request->specification,

                'quantity'        => $request->quantity,

                'gallery'         => $gallery,

                'gallery_order'   => $gallery,

                'status'          => $request->status,

                'is_trending'     => $request->is_trending ?? 0,

                'is_featured'     => $request->is_featured ?? 0,

                'is_best_selling' => $request->is_best_selling ?? 0,

                'is_top_rated'    => $request->is_top_rated ?? 0,

                'is_new_arrival'  => $request->is_new_arrival ?? 0,

                'badge'           => $request->badge,

                'discount'        => $request->discount ?? 0,

            ]);

            // =========================================
            // SAVE VARIANTS
            // =========================================

            if (
                $request->product_type == 'variable' &&
                $request->has('variants')
            ) {

                foreach ($request->variants as $index => $variant) {

                    // VARIANT IMAGE

                    $variantImage = null;

                    if (
                        isset($variant['image']) &&
                        $variant['image']
                    ) {

                        $image = $variant['image'];

                        $variantImage = time() . rand(111,999) . '.' . $image->extension();

                        $image->move(
                            public_path('backend/assets/images/product'),
                            $variantImage
                        );
                    }

                    // CREATE VARIANT

                    $productVariant = ProductVariant::create([

                        'product_id'     => $product->id,

                        'sku'            => $variant['sku'] ?? null,

                        'price'          => $variant['price'] ?? 0,

                        'selling_price'  => $variant['selling_price'] ?? 0,

                        'quantity'       => $variant['quantity'] ?? 0,

                        'image'          => $variantImage,

                        'status'         => 1,

                    ]);

                    // SAVE ATTRIBUTES

                    if (isset($variant['attributes'])) {

                        foreach ($variant['attributes'] as $attr) {

                            $attribute = ProductAttribute::firstOrCreate([
                                'name' => $attr['attribute']
                            ]);

                            $attributeValue = ProductAttributeValue::firstOrCreate([

                                'attribute_id' => $attribute->id,

                                'value'        => $attr['value']

                            ]);

                            ProductVariantAttribute::create([

                                'variant_id'         => $productVariant->id,

                                'attribute_id'       => $attribute->id,

                                'attribute_value_id' => $attributeValue->id,

                            ]);
                        }
                    }
                }
            }
            if ($request->has('faqs') && !empty($request->faqs)) {
            foreach ($request->faqs as $index => $faq) {
                if (!empty($faq['question']) && !empty($faq['answer'])) {
                    $product->faqs()->create([
                        'question' => $faq['question'],
                        'answer' => $faq['answer'],
                        'order' => $faq['order'] ?? $index,
                        'status' => $faq['status'] ?? 1,
                    ]);
                }
            }
        }

            DB::commit();

            return redirect()
                ->route('product.index')
                ->with('success', 'Product Added Successfully');

        } catch (\Exception $e) {

            DB::rollback();

            return back()->withErrors($e->getMessage());
        }
    }

    public function edit(Request $request, Product $product)
    {
        $categories = Category::where('status', 1)->get();

        $subcategories = SubCategory::where('status', 1)->get();

        $product->load([
            'variants.variantAttributes.attribute',
            'variants.variantAttributes.attributeValue'
        ]);

        $page = $request->page;

        return view(
            'admin.product.edit',
            compact(
                'product',
                'categories',
                'subcategories',
                'page'
            )
        );
    }

 public function update(Request $request, Product $product)
{
    $request->validate([
        'category_id'   => 'required',
        'name'          => 'required',
        'slug'          => 'required|unique:products,slug,' . $product->id,
        'sku'           => 'required|unique:products,sku,' . $product->id,
        'price'         => 'required|numeric',
        'selling_price' => 'required|numeric',
        'quantity'      => 'required|numeric',
        'thumbnail'     => 'nullable|image|mimes:jpg,jpeg,png,webp',
        'images.*'      => 'nullable|image|mimes:jpg,jpeg,png,webp',
        'product_type'  => 'required|in:simple,variable',
        'variants' => 'nullable|array',
        'variants.*.sku' => 'nullable',
        'variants.*.price' => 'nullable|numeric',
        'variants.*.selling_price' => 'nullable|numeric',
        'variants.*.quantity' => 'nullable|numeric',
        'variants.*.image' => 'nullable|image|mimes:jpg,jpeg,png,webp',
        'variants.*.attributes' => 'nullable|array',
        'variants.*.attributes.*.attribute' => 'required_if:product_type,variable',
        'variants.*.attributes.*.value' => 'required_if:product_type,variable',
         'faqs' => 'nullable|array',
        'faqs.*.question' => 'required_with:faqs.*|string|max:500',
        'faqs.*.answer' => 'required_with:faqs.*|string',
    ]);

    DB::beginTransaction();

    try {
        // =========================================
        // THUMBNAIL
        // =========================================
        $thumbnail = $product->thumbnail;
        if ($request->hasFile('thumbnail')) {
            if ($product->thumbnail && file_exists(public_path('backend/assets/images/product/' . $product->thumbnail))) {
                unlink(public_path('backend/assets/images/product/' . $product->thumbnail));
            }
            $thumb = $request->file('thumbnail');
            $thumbnail = time() . '_thumb.' . $thumb->extension();
            $thumb->move(public_path('backend/assets/images/product'), $thumbnail);
        }

        // =========================================
        // OLD GALLERY
        // =========================================
        $gallery = is_array($product->gallery) ? $product->gallery : json_decode($product->gallery, true);
        $gallery = $gallery ?? [];

        // =========================================
        // REMOVE IMAGES
        // =========================================
        if ($request->has('remove_images')) {
            foreach ($request->remove_images as $removeImage) {
                $path = public_path('backend/assets/images/product/' . $removeImage);
                if (file_exists($path)) {
                    unlink($path);
                }
                $gallery = array_filter($gallery, function ($img) use ($removeImage) {
                    return $img != $removeImage;
                });
            }
            $gallery = array_values($gallery);
        }

        // =========================================
        // ADD NEW IMAGES
        // =========================================
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $imageName = time() . rand(111, 999) . '.' . $image->extension();
                $image->move(public_path('backend/assets/images/product'), $imageName);
                $gallery[] = $imageName;
            }
        }

        // =========================================
        // GALLERY ORDER
        // =========================================
        if ($request->has('gallery_order')) {
            $orderedGallery = [];
            foreach ($request->gallery_order as $imageName) {
                if (in_array($imageName, $gallery)) {
                    $orderedGallery[] = $imageName;
                }
            }
            foreach ($gallery as $img) {
                if (!in_array($img, $orderedGallery)) {
                    $orderedGallery[] = $img;
                }
            }
            $gallery = $orderedGallery;
        }

        // =========================================
        // PRODUCT UPDATE
        // =========================================
        $product->update([
            'category_id'     => $request->category_id,
            'subcategory_id'  => $request->subcategory_id,
            'name'            => $request->name,
            'slug'            => Str::slug($request->slug),
            'sku'             => $request->sku,
            'product_type'    => $request->product_type,
            'thumbnail'       => $thumbnail,
            'price'           => $request->price,
            'selling_price'   => $request->selling_price,
            'details'         => $request->details,
            'description'     => $request->description,
            'specification'   => $request->specification,
            'quantity'        => $request->quantity,
            'gallery'         => $gallery,
            'gallery_order'   => $gallery,
            'status'          => $request->status,
            'is_trending'     => $request->is_trending ?? 0,
            'is_featured'     => $request->is_featured ?? 0,
            'is_best_selling' => $request->is_best_selling ?? 0,
            'is_top_rated'    => $request->is_top_rated ?? 0,
            'is_new_arrival'  => $request->is_new_arrival ?? 0,
            'badge'           => $request->badge,
            'discount'        => $request->discount ?? 0,
        ]);

        // =========================================
        // DELETE OLD VARIANTS AND THEIR ATTRIBUTES
        // =========================================
        $variantIds = ProductVariant::where('product_id', $product->id)->pluck('id');
        ProductVariantAttribute::whereIn('variant_id', $variantIds)->delete();
        ProductVariant::where('product_id', $product->id)->delete();

        // =========================================
        // SAVE NEW VARIANTS
        // =========================================
        if ($request->product_type == 'variable' && $request->has('variants')) {
            foreach ($request->variants as $variant) {
                $variantImage = null;
                if (isset($variant['image']) && $variant['image']) {
                    $image = $variant['image'];
                    $variantImage = time() . rand(111, 999) . '.' . $image->extension();
                    $image->move(public_path('backend/assets/images/product'), $variantImage);
                }

                $productVariant = ProductVariant::create([
                    'product_id'     => $product->id,
                    'sku'            => $variant['sku'] ?? null,
                    'price'          => $variant['price'] ?? 0,
                    'selling_price'  => $variant['selling_price'] ?? 0,
                    'quantity'       => $variant['quantity'] ?? 0,
                    'image'          => $variantImage,
                    'status'         => 1,
                ]);

                // SAVE ATTRIBUTES FOR THIS VARIANT
                if (isset($variant['attributes'])) {
                    foreach ($variant['attributes'] as $attr) {
                        $attribute = ProductAttribute::firstOrCreate([
                            'name' => $attr['attribute']
                        ]);
                        $attributeValue = ProductAttributeValue::firstOrCreate([
                            'attribute_id' => $attribute->id,
                            'value'        => $attr['value']
                        ]);
                        ProductVariantAttribute::create([
                            'variant_id'         => $productVariant->id,
                            'attribute_id'       => $attribute->id,
                            'attribute_value_id' => $attributeValue->id,
                        ]);
                    }
                }
            }
        }
  if ($request->has('remove_faqs')) {
            ProductFaq::whereIn('id', $request->remove_faqs)->delete();
        }
        
        // Update existing FAQs and add new ones
        if ($request->has('faqs')) {
            foreach ($request->faqs as $index => $faq) {
                if (isset($faq['id']) && !empty($faq['id'])) {
                    // Update existing FAQ
                    $productFaq = ProductFaq::find($faq['id']);
                    if ($productFaq && $productFaq->product_id == $product->id) {
                        $productFaq->update([
                            'question' => $faq['question'],
                            'answer' => $faq['answer'],
                            'order' => $faq['order'] ?? $index,
                            'status' => $faq['status'] ?? 1,
                        ]);
                    }
                } else {
                    // Create new FAQ
                    if (!empty($faq['question']) && !empty($faq['answer'])) {
                        $product->faqs()->create([
                            'question' => $faq['question'],
                            'answer' => $faq['answer'],
                            'order' => $faq['order'] ?? $index,
                            'status' => $faq['status'] ?? 1,
                        ]);
                    }
                }
            }
        }
        DB::commit();
        return redirect()->route('product.index', ['page' => $request->page])
            ->with('success', 'Product Updated Successfully');
    } catch (\Exception $e) {
        DB::rollback();
        return back()->withErrors($e->getMessage());
    }
}



    public function destroy(Product $product)
    {
        $gallery = is_array($product->gallery)
            ? $product->gallery
            : json_decode($product->gallery, true);

        if ($gallery) {

            foreach ($gallery as $image) {

                $path = public_path(
                    'backend/assets/images/product/' . $image
                );

                if (file_exists($path)) {

                    unlink($path);
                }
            }
        }

        $product->delete();

        return redirect()
            ->route('product.index')
            ->with('success', 'Product Deleted Successfully');
    }

    public function toggleStatus(Product $product)
    {
        $product->status = $product->status == 1 ? 0 : 1;

        $product->save();

        return redirect()->back()->with(
            'success',
            $product->status == 1
                ? 'Product activated successfully.'
                : 'Product deactivated successfully.'
        );
    }
}