<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\HandlesCsv;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\GSTSetting;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductAttributeValue;
use App\Models\ProductFaq;
use App\Models\ProductVariant;
use App\Models\ProductVariantAttribute;
use App\Models\SubCategory;
use App\Models\ChildCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    use HandlesCsv;

    /**
     * Persist discount dynamically from MRP vs selling price (backend JS may also send it).
     */
    private function resolveProductDiscount(Request $request): int
    {
        $price = (float) ($request->price ?? 0);
        $sellingPrice = (float) ($request->selling_price ?? 0);

        if ($price > 0 && $sellingPrice > 0 && $sellingPrice < $price) {
            return (int) round((($price - $sellingPrice) / $price) * 100);
        }

        $fallbackDiscount = $request->discount;

        if ($fallbackDiscount !== null && $fallbackDiscount !== '') {
            return max(0, (int) round((float) $fallbackDiscount));
        }

        return 0;
    }

    private function productExportHeaders(): array
    {
        return [
            'SKU',
            'Name',
            'Slug',
            'Category',
            'Price',
            'Selling Price',
            'GST Percentage',
            'Quantity',
            'Product Type',
            'Status',
            'Details',
            'Description',
            'Specification',
        ];
    }

    private function buildProductQuery(Request $request)
    {
        $query = Product::with(['category', 'subcategory', 'childCategory', 'gstSetting']);

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

        return $query;
    }

    public function index(Request $request)
    {
        $products = $this->buildProductQuery($request)->latest()->paginate(10);

        $categories = Category::where('status', 1)->get();

        return view(
            'admin.product.index',
            compact('products', 'categories')
        );
    }

    public function create()
    {
        $categories = Category::where('status', 1)->get();
        $gstSettings = GSTSetting::where('status', 1)->orderBy('gst_percentage')->get();

        return view(
            'admin.product.add',
            compact('categories', 'gstSettings')
        );
    }

    public function getSubcategories($category_id)
    {
        $subcategories = SubCategory::where('category_id', $category_id)
            ->where('status', 1)
            ->get();

        return response()->json($subcategories);
    }

    public function getChildCategories($subcategory_id)
    {
        $childCategories = ChildCategory::where('sub_category_id', $subcategory_id)
            ->where('status', 1)
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json($childCategories);
    }

    public function store(Request $request)
    {
        $request->validate([

            'category_id'       => 'required',
            'subcategory_id'    => 'nullable|exists:sub_categories,id',
            'child_category_id' => 'nullable|exists:child_categories,id',

            'name'          => 'required',

            'slug'          => 'required|unique:products,slug',

            'sku'           => 'required|string|max:100|unique:products,sku',

            'price'         => 'nullable|numeric',

            'selling_price' => 'nullable|numeric',

            'discount'      => 'nullable|numeric',

            'discount_type' => 'nullable|in:percent,flat',

            'gst_setting_id' => 'nullable|exists:gst_settings,id',

            'quantity'      => 'nullable|numeric',

            'details'       => 'nullable',

            'description'   => 'required',

            'specification' => 'required',

            'thumbnail'     => 'nullable|image|mimes:jpg,jpeg,png,webp',

            'gallery'       => 'nullable|array|min:1',

            'gallery.*'     => 'nullable|image|mimes:jpg,jpeg,png,webp',

            'product_type'  => 'nullable|in:simple,variable',

            'variants' => 'nullable|array',
            'variants.*.arch' => 'nullable|string',
            'variants.*.size' => 'nullable|string',
            'variants.*.sku' => 'nullable|string',
            'variants.*.price' => 'nullable|numeric',
            'variants.*.selling_price' => 'nullable|numeric',
            'variants.*.quantity' => 'nullable|numeric',
            'variants.*.status' => 'nullable',
            'variants.*.image' => 'nullable|image|mimes:jpg,jpeg,png,webp',
            'variants.*.attributes' => 'nullable|array',
            'faqs' => 'nullable|array',
            'faqs.*.question' => 'required_with:faqs.*|string|max:500',
            'faqs.*.answer' => 'required_with:faqs.*|string',
            'video' => 'nullable|mimes:mp4,webm,ogg|max:51200',

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

                    $imageName = time() . rand(111, 999) . '.' . $image->extension();

                    $image->move(
                        public_path('backend/assets/images/product'),
                        $imageName
                    );

                    $gallery[] = $imageName;
                }
            }
            $video = null;

            if ($request->hasFile('video')) {
                $vid = $request->file('video');
                $video = time() . '_video.' . $vid->extension();

                $vid->move(
                    public_path('backend/assets/videos/product'),
                    $video
                );
            }
            // =========================================
            // PRODUCT SAVE
            // =========================================

            $product = Product::create([

                'category_id'       => $request->category_id,

                'subcategory_id'    => $request->filled('subcategory_id') ? $request->subcategory_id : null,

                'child_category_id' => $request->filled('child_category_id') ? $request->child_category_id : null,

                'name'            => $request->name,

                'slug'            => Str::slug($request->slug),

                'sku'             => $request->sku,

                'product_type'    => $request->product_type ?: 'simple',

                'thumbnail'       => $thumbnail,

                'price'           => $request->filled('price') ? $request->price : 0,

                'selling_price'   => $request->filled('selling_price') && (float) $request->selling_price > 0 ? (float) $request->selling_price : ($request->filled('price') ? (float) $request->price : 0),

                'gst_setting_id'  => $request->gst_setting_id ?: null,

                'details'         => $request->filled('details') ? $request->details : null,

                'description'     => $request->description,

                'specification'   => $request->specification,

                'quantity'        => $request->filled('quantity') ? $request->quantity : 0,

                'gallery'         => $gallery,

                'gallery_order'   => $gallery,

                'status'          => $request->status,

                'is_featured'     => $request->has('is_featured') ? 1 : 0,

                'is_best_selling' => $request->has('is_best_selling') ? 1 : 0,

                'is_new_arrival'  => $request->has('is_new_arrival') ? 1 : 0,

                'is_trending'     => $request->has('is_trending') ? 1 : 0,

                'is_top_rated'    => $request->has('is_top_rated') ? 1 : 0,

                'badge'           => $request->badge,

                'discount'        => $this->resolveProductDiscount($request),
                'video' => $video,

            ]);

            // =========================================
            // SAVE VARIANTS
            // =========================================

            if ($request->product_type == 'variable' && $request->has('variants')) {
                foreach ($request->variants as $index => $variant) {
                    // Skip if neither arch nor size is provided and no SKU
                    if (empty($variant['arch']) && empty($variant['size']) && empty($variant['sku'])) {
                        continue;
                    }

                    $variantImage = null;
                    if (isset($variant['image']) && $variant['image']) {
                        $image = $variant['image'];
                        $variantImage = time() . rand(111, 999) . '.' . $image->extension();
                        $image->move(
                            public_path('backend/assets/images/product'),
                            $variantImage
                        );
                    }

                    $sku = !empty($variant['sku'])
                        ? $variant['sku']
                        : ($product->sku . '-' . Str::slug($variant['arch'] ?? 'v') . '-' . ($variant['size'] ?? ($index + 1)));

                    $price = isset($variant['price']) && $variant['price'] !== ''
                        ? (float) $variant['price']
                        : (float) $product->price;

                    $sellingPrice = isset($variant['selling_price']) && $variant['selling_price'] !== ''
                        ? (float) $variant['selling_price']
                        : (float) $product->selling_price;

                    $quantity = isset($variant['quantity']) && $variant['quantity'] !== ''
                        ? (int) $variant['quantity']
                        : 0;

                    $status = isset($variant['status']) ? (int) $variant['status'] : 1;

                    $productVariant = ProductVariant::create([
                        'product_id'     => $product->id,
                        'arch'           => $variant['arch'] ?? null,
                        'size'           => $variant['size'] ?? null,
                        'sku'            => $sku,
                        'price'          => $price,
                        'selling_price'  => $sellingPrice,
                        'quantity'       => $quantity,
                        'image'          => $variantImage,
                        'status'         => $status,
                    ]);

                    // Sync ProductVariantAttribute for attributes table consistency
                    $attributesToSync = [];
                    if (!empty($productVariant->arch)) {
                        $attributesToSync[] = ['attribute' => 'Arch', 'value' => $productVariant->arch];
                    }
                    if (!empty($productVariant->size)) {
                        $attributesToSync[] = ['attribute' => 'Size', 'value' => (string) $productVariant->size];
                    }
                    if (isset($variant['attributes']) && is_array($variant['attributes'])) {
                        foreach ($variant['attributes'] as $attr) {
                            if (!empty($attr['attribute']) && !empty($attr['value'])) {
                                $attributesToSync[] = ['attribute' => $attr['attribute'], 'value' => $attr['value']];
                            }
                        }
                    }

                    foreach ($attributesToSync as $attr) {
                        $attribute = ProductAttribute::firstOrCreate(['name' => $attr['attribute']]);
                        $attributeValue = ProductAttributeValue::firstOrCreate([
                            'attribute_id' => $attribute->id,
                            'value'        => $attr['value']
                        ]);

                        ProductVariantAttribute::firstOrCreate([
                            'variant_id'         => $productVariant->id,
                            'attribute_id'       => $attribute->id,
                            'attribute_value_id' => $attributeValue->id,
                        ]);
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

        $subcategories = $product->category_id
            ? SubCategory::where('category_id', $product->category_id)
            ->where(function ($query) use ($product) {
                $query->where('status', 1);
                if ($product->subcategory_id) {
                    $query->orWhere('id', $product->subcategory_id);
                }
            })
            ->get()
            : collect();

        $childCategories = $product->subcategory_id
            ? ChildCategory::where('sub_category_id', $product->subcategory_id)
            ->where(function ($query) use ($product) {
                $query->where('status', 1);
                if ($product->child_category_id) {
                    $query->orWhere('id', $product->child_category_id);
                }
            })
            ->get()
            : collect();

        $product->load([
            'variants.variantAttributes.attribute',
            'variants.variantAttributes.attributeValue',
            'gstSetting',
        ]);

        $gstSettings = GSTSetting::where('status', 1)->orderBy('gst_percentage')->get();

        $page = $request->page;

        return view(
            'admin.product.edit',
            compact(
                'product',
                'categories',
                'subcategories',
                'childCategories',
                'gstSettings',
                'page'
            )
        );
    }

    public function update(Request $request, Product $product)
    {
        $request->validate([
            'category_id'       => 'required',
            'subcategory_id'    => 'nullable|exists:sub_categories,id',
            'child_category_id' => 'nullable|exists:child_categories,id',
            'name'          => 'required',
            'slug'          => 'required|unique:products,slug,' . $product->id,
            'sku'           => 'required|string|max:100|unique:products,sku,' . $product->id,
            'price'         => 'nullable|numeric',
            'selling_price' => 'nullable|numeric',
            'discount'      => 'nullable|numeric',
            'discount_type' => 'nullable|in:percent,flat',
            'gst_setting_id' => 'nullable|exists:gst_settings,id',
            'quantity'      => 'nullable|numeric',
            'details'       => 'nullable',
            'description'   => 'nullable',
            'specification' => 'nullable',
            'thumbnail'     => 'nullable|image|mimes:jpg,jpeg,png,webp',
            'images.*'      => 'nullable|image|mimes:jpg,jpeg,png,webp',
            'product_type'  => 'required|in:simple,variable',
            'variants' => 'nullable|array',
            'variants.*.id' => 'nullable|integer',
            'variants.*.arch' => 'nullable|string',
            'variants.*.size' => 'nullable|string',
            'variants.*.sku' => 'nullable|string',
            'variants.*.price' => 'nullable|numeric',
            'variants.*.selling_price' => 'nullable|numeric',
            'variants.*.quantity' => 'nullable|numeric',
            'variants.*.status' => 'nullable',
            'variants.*.image' => 'nullable|image|mimes:jpg,jpeg,png,webp',
            'variants.*.attributes' => 'nullable|array',
            'faqs' => 'nullable|array',
            'faqs.*.question' => 'required_with:faqs.*|string|max:500',
            'faqs.*.answer' => 'required_with:faqs.*|string',
            'video' => 'nullable|mimes:mp4,webm,ogg|max:51200',
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
            $video = $product->video;

            if ($request->hasFile('video')) {

                if ($product->video && file_exists(public_path('backend/assets/videos/product/' . $product->video))) {
                    unlink(public_path('backend/assets/videos/product/' . $product->video));
                }

                $vid = $request->file('video');

                $video = time() . '_video.' . $vid->extension();

                $vid->move(
                    public_path('backend/assets/videos/product'),
                    $video
                );
            }

            // dd($video);
            // =========================================
            // PRODUCT UPDATE
            // =========================================
            $product->update([
                'category_id'       => $request->category_id,
                'subcategory_id'    => $request->filled('subcategory_id') ? $request->subcategory_id : null,
                'child_category_id' => $request->filled('child_category_id') ? $request->child_category_id : null,
                'name'              => $request->name,
                'slug'            => Str::slug($request->slug),
                'sku'             => $request->sku,
                'product_type'    => $request->product_type ?: $product->product_type,
                'thumbnail'       => $thumbnail,
                'price'           => $request->filled('price') ? $request->price : 0,
                'selling_price'   => $request->filled('selling_price') && (float) $request->selling_price > 0 ? (float) $request->selling_price : ($request->filled('price') ? (float) $request->price : 0),
                'gst_setting_id'  => $request->gst_setting_id ?: null,
                'details'         => $request->input('details', $product->details),
                'description'     => $request->description,
                'specification'   => $request->specification,
                'quantity'        => $request->filled('quantity') ? $request->quantity : 0,
                'gallery'         => $gallery,
                'gallery_order'   => $gallery,
                'status'          => $request->status,
                'is_featured'     => $request->has('is_featured') ? 1 : 0,
                'is_best_selling' => $request->has('is_best_selling') ? 1 : 0,
                'is_new_arrival'  => $request->has('is_new_arrival') ? 1 : 0,
                'is_trending'     => $request->has('is_trending') ? 1 : 0,
                'is_top_rated'    => $request->has('is_top_rated') ? 1 : 0,
                'badge'           => $request->badge,
                'discount'        => $this->resolveProductDiscount($request),
                'video' => $video,
            ]);

            // =========================================
            // SAVE & UPDATE VARIANTS (NO DUPLICATE RECORDS)
            // =========================================
            if ($request->product_type == 'variable') {
                $processedVariantIds = [];

                if ($request->has('variants') && is_array($request->variants)) {
                    foreach ($request->variants as $index => $variant) {
                        if (empty($variant['arch']) && empty($variant['size']) && empty($variant['sku'])) {
                            continue;
                        }

                        $variantImage = null;
                        if (isset($variant['image']) && $variant['image']) {
                            $image = $variant['image'];
                            $variantImage = time() . rand(111, 999) . '.' . $image->extension();
                            $image->move(public_path('backend/assets/images/product'), $variantImage);
                        }

                        // Try to locate existing variant by ID
                        $existingVariant = null;
                        if (!empty($variant['id'])) {
                            $existingVariant = ProductVariant::where('product_id', $product->id)->find($variant['id']);
                        }

                        // Or try to match existing variant by (arch, size)
                        if (!$existingVariant && !empty($variant['arch']) && !empty($variant['size'])) {
                            $existingVariant = ProductVariant::where('product_id', $product->id)
                                ->where('arch', $variant['arch'])
                                ->where('size', $variant['size'])
                                ->first();
                        }

                        $sku = !empty($variant['sku'])
                            ? $variant['sku']
                            : ($product->sku . '-' . Str::slug($variant['arch'] ?? 'v') . '-' . ($variant['size'] ?? ($index + 1)));

                        $price = isset($variant['price']) && $variant['price'] !== ''
                            ? (float) $variant['price']
                            : (float) $product->price;

                        $sellingPrice = isset($variant['selling_price']) && $variant['selling_price'] !== ''
                            ? (float) $variant['selling_price']
                            : (float) $product->selling_price;

                        $quantity = isset($variant['quantity']) && $variant['quantity'] !== ''
                            ? (int) $variant['quantity']
                            : 0;

                        $status = isset($variant['status']) ? (int) $variant['status'] : 1;

                        if ($existingVariant) {
                            $updateData = [
                                'arch'          => $variant['arch'] ?? $existingVariant->arch,
                                'size'          => $variant['size'] ?? $existingVariant->size,
                                'sku'           => $sku,
                                'price'         => $price,
                                'selling_price' => $sellingPrice,
                                'quantity'      => $quantity,
                                'status'        => $status,
                            ];

                            if ($variantImage) {
                                if ($existingVariant->image && file_exists(public_path('backend/assets/images/product/' . $existingVariant->image))) {
                                    @unlink(public_path('backend/assets/images/product/' . $existingVariant->image));
                                }
                                $updateData['image'] = $variantImage;
                            }

                            $existingVariant->update($updateData);
                            $productVariant = $existingVariant;
                        } else {
                            $productVariant = ProductVariant::create([
                                'product_id'     => $product->id,
                                'arch'           => $variant['arch'] ?? null,
                                'size'           => $variant['size'] ?? null,
                                'sku'            => $sku,
                                'price'          => $price,
                                'selling_price'  => $sellingPrice,
                                'quantity'       => $quantity,
                                'image'          => $variantImage,
                                'status'         => $status,
                            ]);
                        }

                        $processedVariantIds[] = $productVariant->id;

                        // Sync attributes table for consistency
                        $attributesToSync = [];
                        if (!empty($productVariant->arch)) {
                            $attributesToSync[] = ['attribute' => 'Arch', 'value' => $productVariant->arch];
                        }
                        if (!empty($productVariant->size)) {
                            $attributesToSync[] = ['attribute' => 'Size', 'value' => (string) $productVariant->size];
                        }
                        if (isset($variant['attributes']) && is_array($variant['attributes'])) {
                            foreach ($variant['attributes'] as $attr) {
                                if (!empty($attr['attribute']) && !empty($attr['value'])) {
                                    $attributesToSync[] = ['attribute' => $attr['attribute'], 'value' => $attr['value']];
                                }
                            }
                        }

                        ProductVariantAttribute::where('variant_id', $productVariant->id)->delete();
                        foreach ($attributesToSync as $attr) {
                            $attribute = ProductAttribute::firstOrCreate(['name' => $attr['attribute']]);
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

                // Delete variants no longer selected
                $variantsToDelete = ProductVariant::where('product_id', $product->id)
                    ->whereNotIn('id', $processedVariantIds)
                    ->get();

                foreach ($variantsToDelete as $delVariant) {
                    if ($delVariant->image && file_exists(public_path('backend/assets/images/product/' . $delVariant->image))) {
                        @unlink(public_path('backend/assets/images/product/' . $delVariant->image));
                    }
                    ProductVariantAttribute::where('variant_id', $delVariant->id)->delete();
                    DB::table('carts')->where('variant_id', $delVariant->id)->delete();
                    DB::table('wishlists')->where('variant_id', $delVariant->id)->delete();
                    DB::table('order_items')->where('variant_id', $delVariant->id)->update(['variant_id' => null]);
                    $delVariant->delete();
                }

                // If all variants were removed, automatically switch product_type to simple
                if (empty($processedVariantIds)) {
                    $product->update(['product_type' => 'simple']);
                }
            } elseif ($request->product_type == 'simple') {
                // If switched to simple, remove variants
                $allVariants = ProductVariant::where('product_id', $product->id)->get();
                foreach ($allVariants as $delVariant) {
                    if ($delVariant->image && file_exists(public_path('backend/assets/images/product/' . $delVariant->image))) {
                        @unlink(public_path('backend/assets/images/product/' . $delVariant->image));
                    }
                    ProductVariantAttribute::where('variant_id', $delVariant->id)->delete();
                    DB::table('carts')->where('variant_id', $delVariant->id)->delete();
                    DB::table('wishlists')->where('variant_id', $delVariant->id)->delete();
                    DB::table('order_items')->where('variant_id', $delVariant->id)->update(['variant_id' => null]);
                    $delVariant->delete();
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

    public function export(Request $request)
    {
        $products = $this->buildProductQuery($request)->latest()->get();
        $fileName = 'products_' . date('Y-m-d_His') . '.csv';

        return $this->streamCsvDownload($fileName, $this->productExportHeaders(), function ($file) use ($products) {
            foreach ($products as $product) {
                fputcsv($file, [
                    $product->sku,
                    $product->name,
                    $product->slug,
                    $product->category->name ?? '',
                    $product->price,
                    $product->selling_price,
                    $product->gstSetting->gst_percentage ?? '',
                    $product->quantity,
                    $product->product_type ?? 'simple',
                    $product->status == 1 ? 'Active' : 'Inactive',
                    $product->details,
                    $product->description,
                    $product->specification,
                ]);
            }
        });
    }

    public function importSample()
    {
        $fileName = 'products_import_sample.csv';

        return $this->streamCsvDownload($fileName, $this->productExportHeaders(), function ($file) {
            fputcsv($file, [
                'NHC',
                'Number Hunt Challenge',
                'number-hunt-challenge',
                'Games',
                '799',
                '799',
                '18',
                '100',
                'simple',
                'Active',
                'Educational number hunt game',
                'Full product description here',
                'Age 3+ | 2-4 players',
            ]);
        });
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:10240',
        ]);

        $parsed = $this->parseCsvFile($request->file('file'));

        if (empty($parsed['rows'])) {
            return back()->with('error', 'CSV file is empty or invalid.');
        }

        $imported = 0;
        $updated = 0;
        $errors = [];

        foreach ($parsed['rows'] as $index => $row) {
            $rowNumber = $index + 2;

            try {
                DB::transaction(function () use ($row, $rowNumber, &$imported, &$updated) {
                    $sku = $this->csvValue($row, 'sku');
                    $name = $this->csvValue($row, 'name');

                    if (!$sku || !$name) {
                        throw new \RuntimeException("Row {$rowNumber}: SKU and Name are required.");
                    }

                    $categoryName = $this->csvValue($row, 'category');
                    $category = null;

                    if ($categoryName) {
                        $category = Category::where(function ($q) use ($categoryName) {
                            $q->whereRaw('LOWER(name) = ?', [strtolower($categoryName)])
                                ->orWhere('slug', Str::slug($categoryName));
                        })->first();
                    }

                    if (!$category) {
                        throw new \RuntimeException("Row {$rowNumber}: category '{$categoryName}' not found.");
                    }

                    $subcategory = null;
                    $subcategoryName = $this->csvValue($row, 'subcategory');
                    if ($subcategoryName) {
                        $subcategory = SubCategory::where('category_id', $category->id)
                            ->where(function ($q) use ($subcategoryName) {
                                $q->whereRaw('LOWER(name) = ?', [strtolower($subcategoryName)])
                                    ->orWhere('slug', Str::slug($subcategoryName));
                            })
                            ->first();

                        if (!$subcategory) {
                            throw new \RuntimeException("Row {$rowNumber}: subcategory '{$subcategoryName}' not found in category '{$categoryName}'.");
                        }
                    }

                    $gstSettingId = null;
                    $gstPercentage = $this->csvValue($row, 'gst_percentage');
                    if ($gstPercentage !== '') {
                        $gstSetting = GSTSetting::where('gst_percentage', (float) $gstPercentage)->first();
                        $gstSettingId = $gstSetting?->id;
                    }

                    $slug = $this->csvValue($row, 'slug') ?: Str::slug($name);
                    $productType = $this->csvValue($row, 'product_type', 'simple');
                    if (!in_array($productType, ['simple', 'variable'], true)) {
                        $productType = 'simple';
                    }

                    $data = [
                        'category_id' => $category->id,
                        'subcategory_id' => $subcategory?->id,
                        'name' => $name,
                        'slug' => $slug,
                        'price' => (float) $this->csvValue($row, 'price', 0),
                        'selling_price' => (float) $this->csvValue($row, 'selling_price', 0),
                        'gst_setting_id' => $gstSettingId,
                        'quantity' => (float) $this->csvValue($row, 'quantity', 0),
                        'product_type' => $productType,
                        'status' => $this->normalizeStatusValue($this->csvValue($row, 'status'), 1),
                        'details' => $this->csvValue($row, 'details', '-'),
                        'description' => $this->csvValue($row, 'description', '-'),
                        'specification' => $this->csvValue($row, 'specification', '-'),
                        'gallery' => [],
                    ];

                    $existing = Product::where('sku', $sku)->first();
                    if ($existing) {
                        $existing->update($data);
                        $updated++;
                        return;
                    }

                    if (Product::where('slug', $slug)->exists()) {
                        $data['slug'] = $slug . '-' . Str::lower(Str::random(4));
                    }

                    Product::create(array_merge($data, ['sku' => $sku]));
                    $imported++;
                });
            } catch (\Throwable $e) {
                $errors[] = $e->getMessage();
            }
        }

        $message = "Import complete: {$imported} created, {$updated} updated.";
        if (!empty($errors)) {
            $message .= ' ' . count($errors) . ' row(s) had errors.';
        }

        return redirect()
            ->route('product.index')
            ->with('success', $message)
            ->with('import_errors', array_slice($errors, 0, 25));
    }

    public function duplicate($id)
    {
        $product = Product::findOrFail($id);

        $newProduct = $product->replicate(); // copy all fields
        $newProduct->name = $product->name . ' (Copy)';
        $newProduct->slug = $product->slug . '-copy-' . time();
        $newProduct->save();

        return redirect()->back()->with('success', 'Product duplicated successfully!');
    }

    public function deleteVariant($id)
    {
        try {
            $variant = ProductVariant::findOrFail($id);

            if ($variant->image && file_exists(public_path('backend/assets/images/product/' . $variant->image))) {
                @unlink(public_path('backend/assets/images/product/' . $variant->image));
            }

            ProductVariantAttribute::where('variant_id', $variant->id)->delete();
            DB::table('carts')->where('variant_id', $variant->id)->delete();
            DB::table('wishlists')->where('variant_id', $variant->id)->delete();
            DB::table('order_items')->where('variant_id', $variant->id)->update(['variant_id' => null]);

            $productId = $variant->product_id;
            $variant->delete();

            $remaining = ProductVariant::where('product_id', $productId)->count();
            if ($remaining === 0) {
                Product::where('id', $productId)->update(['product_type' => 'simple']);
            }

            return response()->json([
                'success' => true,
                'message' => 'Variant deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Add variants directly to an existing product via AJAX without full page update.
     */
    public function ajaxAddVariants(Request $request, Product $product)
    {
        $request->validate([
            'variants' => 'required|array|min:1',
            'variants.*.arch' => 'required|string',
            'variants.*.size' => 'required|string',
            'variants.*.sku' => 'nullable|string',
            'variants.*.price' => 'nullable|numeric',
            'variants.*.selling_price' => 'nullable|numeric',
            'variants.*.quantity' => 'nullable|numeric',
            'variants.*.status' => 'nullable',
        ]);

        $createdVariants = [];

        DB::beginTransaction();
        try {
            if ($product->product_type !== 'variable') {
                $product->update(['product_type' => 'variable']);
            }

            foreach ($request->variants as $index => $varData) {
                $arch = $varData['arch'];
                $size = (string) $varData['size'];
                $sku = !empty($varData['sku'])
                    ? $varData['sku']
                    : ($product->sku . '-' . Str::slug($arch) . '-' . $size);

                $price = isset($varData['price']) && $varData['price'] !== ''
                    ? (float) $varData['price']
                    : (float) ($product->price ?: 0);

                $sellingPrice = isset($varData['selling_price']) && $varData['selling_price'] !== ''
                    ? (float) $varData['selling_price']
                    : (float) ($product->selling_price ?: $price);

                $quantity = isset($varData['quantity']) && $varData['quantity'] !== ''
                    ? (int) $varData['quantity']
                    : (int) ($product->quantity ?: 10);

                $status = isset($varData['status']) ? (int) $varData['status'] : 1;

                // Check if existing variant for same arch and size
                $variant = ProductVariant::where('product_id', $product->id)
                    ->where('arch', $arch)
                    ->where('size', $size)
                    ->first();

                if ($variant) {
                    $variant->update([
                        'sku' => $sku,
                        'price' => $price,
                        'selling_price' => $sellingPrice,
                        'quantity' => $quantity,
                        'status' => $status,
                    ]);
                } else {
                    $variant = ProductVariant::create([
                        'product_id' => $product->id,
                        'arch' => $arch,
                        'size' => $size,
                        'sku' => $sku,
                        'price' => $price,
                        'selling_price' => $sellingPrice,
                        'quantity' => $quantity,
                        'status' => $status,
                    ]);
                }

                // Sync variant attributes
                $attributesToSync = [
                    ['attribute' => 'Arch', 'value' => $arch],
                    ['attribute' => 'Size', 'value' => $size],
                ];

                ProductVariantAttribute::where('variant_id', $variant->id)->delete();
                foreach ($attributesToSync as $attr) {
                    $attribute = ProductAttribute::firstOrCreate(['name' => $attr['attribute']]);
                    $attributeValue = ProductAttributeValue::firstOrCreate([
                        'attribute_id' => $attribute->id,
                        'value' => $attr['value'],
                    ]);

                    ProductVariantAttribute::create([
                        'variant_id' => $variant->id,
                        'attribute_id' => $attribute->id,
                        'attribute_value_id' => $attributeValue->id,
                    ]);
                }

                $createdVariants[] = [
                    'id' => $variant->id,
                    'arch' => $variant->arch,
                    'size' => $variant->size,
                    'sku' => $variant->sku,
                    'price' => $variant->price,
                    'selling_price' => $variant->selling_price,
                    'quantity' => $variant->quantity,
                    'status' => $variant->status,
                    'key' => "{$arch}|{$size}",
                ];
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => count($createdVariants) . ' variant(s) saved to product successfully!',
                'variants' => $createdVariants,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to save variants: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update a single variant via AJAX without full product update.
     */
    public function ajaxUpdateVariant(Request $request, $id)
    {
        try {
            $variant = ProductVariant::findOrFail($id);

            $data = [];
            if ($request->has('sku') && !empty($request->sku)) $data['sku'] = $request->sku;
            if ($request->has('price')) $data['price'] = (float) $request->price;
            if ($request->has('selling_price')) $data['selling_price'] = (float) $request->selling_price;
            if ($request->has('quantity')) $data['quantity'] = (int) $request->quantity;
            if ($request->has('status')) $data['status'] = (int) $request->status;

            if (!empty($data)) {
                $variant->update($data);
            }

            return response()->json([
                'success' => true,
                'message' => 'Variant updated successfully',
                'variant' => $variant,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
