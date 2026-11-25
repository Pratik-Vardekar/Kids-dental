@extends('layouts.admin')
@section('content')
    <div class="container-fluid my-2">
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <div>
                <h4 class="mb-1">Edit Product</h4>
                <p class="text-muted mb-0">Update product details</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ $product->getFrontendUrl() }}" target="_blank" class="btn btn-outline-primary">
                    <i class="ti ti-external-link me-1"></i> View on Website
                </a>
                <a href="{{ route('product.index') }}" class="btn btn-secondary">
                    <i class="ti ti-arrow-left me-1"></i> Back
                </a>
            </div>
        </div>
        <div class="card">
            <div class="card-body">
                <form action="{{ route('product.update', $product->id) }}" method="POST" enctype="multipart/form-data"
                    class="row g-3">
                    @csrf
                    @method('PUT')
                    <div class="col-md-4">
                        <label class="form-label">Product Name</label>
                        <input type="text" name="name" id="name" class="form-control"
                            value="{{ $product->name }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Slug</label>
                        <input type="text" name="slug" id="slug" class="form-control"
                            value="{{ $product->slug }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">SKU <span class="text-danger">*</span></label>
                        <input type="text" name="sku" id="sku" class="form-control"
                            value="{{ old('sku', $product->sku) }}" placeholder="Enter SKU" required>
                        @error('sku')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Category</label>
                        <select name="category_id" id="category_id" class="form-select" required>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}"
                                    {{ $product->category_id == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">
                            Sub Category
                            <small class="text-muted">(Optional)</small>
                        </label>
                        <select name="subcategory_id" id="subcategory_id" class="form-select">
                            <option value="">Select Sub Category</option>
                            @foreach ($subcategories as $subcategory)
                                <option value="{{ $subcategory->id }}"
                                    {{ $product->subcategory_id == $subcategory->id ? 'selected' : '' }}>
                                    {{ $subcategory->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">
                            Child Category
                            <small class="text-muted">(Optional)</small>
                        </label>
                        <select name="child_category_id" id="child_category_id" class="form-select">
                            <option value="">Select Child Category</option>
                            @foreach ($childCategories as $childCategory)
                                <option value="{{ $childCategory->id }}"
                                    {{ old('child_category_id', $product->child_category_id) == $childCategory->id ? 'selected' : '' }}>
                                    {{ $childCategory->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('child_category_id')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    @include('admin.product.partials.gst-tax-fields', [
                        'selectedGstId' => old('gst_setting_id', $product->gst_setting_id),
                    ])
                    {{-- <div class="col-md-3">
                        <label class="form-label">Discount Type</label>
                        <select name="discount_type" id="discount_type" class="form-select">
                            <option value="percent" {{ old('discount_type', 'percent') == 'percent' ? 'selected' : '' }}>
                                Percentage (%)</option>
                            <option value="flat" {{ old('discount_type') == 'flat' ? 'selected' : '' }}>Flat Amount (₹)
                            </option>
                        </select>
                    </div> --}}
                    <!-- Price, Selling Price, Discount Type, Discount Dynamic Calculation -->
                    <div class="col-md-4">
                        <label class="form-label">Price (₹) <span class="text-danger">*</span></label>
                        <input type="number" name="price" id="price" class="form-control"
                            value="{{ old('price', $product->price) }}" step="0.01" min="0" placeholder="0.00"
                            required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Selling Price (₹)</label>
                        <input type="number" name="selling_price" id="selling_price" class="form-control"
                            value="{{ old('selling_price', $product->selling_price) }}" step="0.01" min="0"
                            placeholder="Same as Price if no discount">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Discount (%)</label>
                        <div class="input-group">
                            <input type="number" name="discount" class="form-control" id="discount"
                                value="{{ old('discount', $product->discount) }}" step="0.01" min="0"
                                placeholder="0">
                            <span class="input-group-text" id="discountTypeSymbol">%</span>
                        </div>
                    </div>
                    <input type="hidden" name="discount_type" id="discount_type" value="percent">
                    <div class="col-12 mt-1 mb-2">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-light text-dark border px-2 py-1" id="discountDynamicInfo">
                                <i class="ti ti-discount me-1"></i> No Discount
                            </span>
                            <small class="text-muted" id="priceCalculationHint">
                                Type <strong>Price</strong> and <strong>Discount (%)</strong> to calculate <strong>Selling
                                    Price</strong> automatically (or edit Selling Price directly).
                            </small>
                        </div>
                    </div>

                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            const priceInput = document.getElementById('price');
                            const discountTypeSelect = document.getElementById('discount_type');
                            const discountInput = document.getElementById('discount');
                            const discountTypeSymbol = document.getElementById('discountTypeSymbol');
                            const sellingPriceInput = document.getElementById('selling_price');
                            const discountInfo = document.getElementById('discountDynamicInfo');

                            let isUpdatingPrice = false;

                            function getDiscountType() {
                                return (discountTypeSelect && discountTypeSelect.value) ? discountTypeSelect.value : 'percent';
                            }

                            function updateBadge(price, sellingPrice, discount, type) {
                                if (!discountInfo) return;
                                if (price > 0 && sellingPrice > 0 && sellingPrice < price) {
                                    const savedAmount = (price - sellingPrice).toFixed(2);
                                    const percent = Math.round(((price - sellingPrice) / price) * 100);
                                    discountInfo.className =
                                        'badge bg-success-subtle text-success border border-success-subtle px-2 py-1';
                                    discountInfo.innerHTML =
                                        `<i class="ti ti-discount-check me-1"></i> Save ₹${savedAmount} (${percent}% OFF)`;
                                } else if (price > 0 && sellingPrice > price) {
                                    discountInfo.className =
                                        'badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1';
                                    discountInfo.innerHTML =
                                        `<i class="ti ti-alert-circle me-1"></i> Selling price cannot exceed regular price`;
                                } else {
                                    discountInfo.className = 'badge bg-light text-dark border px-2 py-1';
                                    discountInfo.innerHTML = `<i class="ti ti-discount me-1"></i> No Discount`;
                                }
                            }

                            function calculateSellingPriceFromDiscount() {
                                if (isUpdatingPrice) return;
                                isUpdatingPrice = true;

                                try {
                                    const price = parseFloat(priceInput ? priceInput.value : 0) || 0;
                                    const discount = parseFloat(discountInput ? discountInput.value : 0) || 0;
                                    const type = getDiscountType();
                                    let sellingPrice = price;

                                    if (price > 0 && discount > 0) {
                                        if (type === 'percent') {
                                            const discountAmount = (price * discount) / 100;
                                            sellingPrice = Math.max(0, price - discountAmount);
                                        } else {
                                            sellingPrice = Math.max(0, price - discount);
                                        }
                                        if (sellingPriceInput) {
                                            sellingPriceInput.value = (Math.round(sellingPrice * 100) / 100).toFixed(2);
                                        }
                                    } else if (price > 0 && discount === 0) {
                                        if (sellingPriceInput) {
                                            sellingPriceInput.value = price.toFixed(2);
                                        }
                                    }

                                    const currentSelling = parseFloat(sellingPriceInput ? sellingPriceInput.value : 0) || 0;
                                    updateBadge(price, currentSelling, discount, type);

                                    if (sellingPriceInput) {
                                        sellingPriceInput.dispatchEvent(new Event('input'));
                                    }
                                } finally {
                                    isUpdatingPrice = false;
                                }
                            }

                            function calculateDiscountFromSellingPrice() {
                                if (isUpdatingPrice) return;
                                isUpdatingPrice = true;

                                try {
                                    const price = parseFloat(priceInput ? priceInput.value : 0) || 0;
                                    const sellingPrice = parseFloat(sellingPriceInput ? sellingPriceInput.value : 0) || 0;
                                    const type = getDiscountType();

                                    if (price > 0 && sellingPrice > 0 && sellingPrice < price) {
                                        if (type === 'percent') {
                                            const percent = +(((price - sellingPrice) / price) * 100).toFixed(2);
                                            if (discountInput) discountInput.value = percent;
                                        } else {
                                            const flatDiff = +(price - sellingPrice).toFixed(2);
                                            if (discountInput) discountInput.value = flatDiff;
                                        }
                                    } else if (sellingPrice >= price || sellingPrice <= 0) {
                                        if (discountInput) discountInput.value = 0;
                                    }

                                    const currentDiscount = parseFloat(discountInput ? discountInput.value : 0) || 0;
                                    updateBadge(price, sellingPrice, currentDiscount, type);
                                } finally {
                                    isUpdatingPrice = false;
                                }
                            }

                            function onPriceInput() {
                                const price = parseFloat(priceInput ? priceInput.value : 0) || 0;
                                const discount = parseFloat(discountInput ? discountInput.value : 0) || 0;
                                const sellingPrice = parseFloat(sellingPriceInput ? sellingPriceInput.value : 0) || 0;

                                if (discount > 0) {
                                    calculateSellingPriceFromDiscount();
                                } else if (sellingPrice > 0 && sellingPrice < price) {
                                    calculateDiscountFromSellingPrice();
                                } else {
                                    calculateSellingPriceFromDiscount();
                                }
                            }

                            if (discountTypeSelect && discountTypeSelect.tagName === 'SELECT') {
                                discountTypeSelect.addEventListener('change', function() {
                                    const type = this.value || 'percent';
                                    if (discountTypeSymbol) discountTypeSymbol.textContent = type === 'percent' ? '%' : '₹';

                                    const price = parseFloat(priceInput ? priceInput.value : 0) || 0;
                                    const sellingPrice = parseFloat(sellingPriceInput ? sellingPriceInput.value : 0) || 0;

                                    if (price > 0 && sellingPrice > 0 && sellingPrice < price) {
                                        if (type === 'percent') {
                                            if (discountInput) discountInput.value = +(((price - sellingPrice) / price) *
                                                100).toFixed(2);
                                        } else {
                                            if (discountInput) discountInput.value = +(price - sellingPrice).toFixed(2);
                                        }
                                    }
                                    updateBadge(price, sellingPrice, parseFloat(discountInput ? discountInput.value : 0) ||
                                        0, type);
                                });
                            }

                            if (priceInput) priceInput.addEventListener('input', onPriceInput);
                            if (discountInput) discountInput.addEventListener('input', calculateSellingPriceFromDiscount);
                            if (sellingPriceInput) sellingPriceInput.addEventListener('input', calculateDiscountFromSellingPrice);

                            // Initial calculation on page load
                            const initialPrice = parseFloat(priceInput?.value) || 0;
                            const initialSellingPrice = parseFloat(sellingPriceInput?.value) || 0;
                            const initialDiscount = parseFloat(discountInput?.value) || 0;

                            if (initialPrice > 0 && initialSellingPrice > 0 && initialDiscount === 0 && initialSellingPrice <
                                initialPrice) {
                                calculateDiscountFromSellingPrice();
                            } else if (initialPrice > 0 && initialDiscount > 0 && (!initialSellingPrice || initialSellingPrice ===
                                    0)) {
                                calculateSellingPriceFromDiscount();
                            } else if (priceInput) {
                                updateBadge(initialPrice, initialSellingPrice, initialDiscount, getDiscountType());
                            }

                            // Form submit fallback: If Selling Price is blank, default to Price automatically
                            const form = priceInput ? priceInput.closest('form') : null;
                            if (form) {
                                form.addEventListener('submit', function() {
                                    if (priceInput && (!sellingPriceInput.value || parseFloat(sellingPriceInput.value) <=
                                            0)) {
                                        sellingPriceInput.value = priceInput.value;
                                    }
                                });
                            }
                        });
                    </script>
                    <div class="col-md-4">
                        <label class="form-label">Quantity</label>
                        <input type="number" name="quantity" class="form-control" value="{{ $product->quantity }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Product Type</label>
                        <select name="product_type" class="form-select" required>
                            <option value="simple" {{ $product->product_type == 'simple' ? 'selected' : '' }}>
                                Simple Product
                            </option>
                            <option value="variable" {{ $product->product_type == 'variable' ? 'selected' : '' }}>
                                Variable Product
                            </option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="1" {{ $product->status == 1 ? 'selected' : '' }}>
                                Active
                            </option>
                            <option value="0" {{ $product->status == 0 ? 'selected' : '' }}>
                                Inactive
                            </option>
                        </select>
                    </div>

                    @include('admin.product.partials.variant-section', ['product' => $product])
                    <div class="col-md-6">
                        <label class="form-label d-block mb-2">
                            Product Labels
                        </label>
                        <div class="d-flex flex-wrap gap-4">
                            {{-- Trending, Featured, Top Rated commented out (not shown) --}}
                            {{-- <div class="form-check">
                     <input class="form-check-input"
                     type="checkbox"
                     name="is_trending"
                     value="1"
                     {{ $product->is_trending ? 'checked' : '' }}>
                     <label class="form-check-label">
                     Trending
                     </label>
                  </div> --}}
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_featured" value="1"
                                    {{ old('is_featured', $product->is_featured) ? 'checked' : '' }}>
                                <label class="form-check-label">
                                    Featured
                                </label>
                            </div>

                            {{-- <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_best_selling" value="1"
                                    {{ old('is_best_selling', $product->is_best_selling) ? 'checked' : '' }}>
                                <label class="form-check-label">
                                    Best Selling
                                </label>
                            </div> --}}
                            {{-- Top Rated commented out (not shown)
                  <div class="form-check">
                     <input class="form-check-input"
                     type="checkbox"
                     name="is_top_rated"
                     value="1"
                     {{ old('is_top_rated', $product->is_top_rated) ? 'checked' : '' }}>
                     <label class="form-check-label">
                     Top Rated
                     </label>
                  </div>
                  --}}
                            {{-- <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_new_arrival" value="1"
                                    {{ old('is_new_arrival', $product->is_new_arrival) ? 'checked' : '' }}>
                                <label class="form-check-label">
                                    New Arrival
                                </label>
                            </div> --}}
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Badge</label>
                        <input type="text" name="badge" class="form-control" placeholder="BESTSELLER"
                            value="{{ $product->badge }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Product Video</label>
                        <input type="file" name="video" id="videoInput" class="form-control" accept="video/*">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label d-block">Video Preview</label>

                        <div id="currentVideo">
                            @if ($product->video)
                                <video width="200" controls class="rounded border">
                                    <source src="{{ asset('backend/assets/videos/product/' . $product->video) }}">
                                </video>
                            @else
                                <p class="text-muted">No video uploaded</p>
                            @endif
                        </div>

                        <div id="videoPreview"></div>
                    </div>
                    <input type="hidden" name="page" value="{{ $page }}">

                    {{-- THUMBNAIL --}}
                    <div class="col-md-12">
                        <label class="form-label">Thumbnail</label>
                        <input type="file" name="thumbnail" id="thumbnailInput" class="form-control"
                            accept="image/*">
                    </div>
                    <div class="col-md-12">
                        <div id="thumbnailPreview">
                            @if ($product->thumbnail)
                                <img src="{{ asset('backend/assets/images/product/' . $product->thumbnail) }}"
                                    class="rounded border"
                                    style="
                     width:120px;
                     height:120px;
                     object-fit:cover;
                     ">
                            @endif
                        </div>
                    </div>
                    {{-- GALLERY --}}
                    <div class="col-md-12">
                        <label class="form-label">Add More Images</label>
                        <input type="file" name="images[]" id="galleryInput" class="form-control" multiple
                            accept="image/*">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Current Gallery</label>
                        <div id="sortable-gallery" class="d-flex flex-wrap gap-3">
                            @php
                                $galleryImages = $product->gallery;
                                if (is_string($galleryImages)) {
                                    $galleryImages = json_decode($galleryImages, true);
                                }
                                $galleryImages = is_array($galleryImages) ? $galleryImages : [];
                            @endphp

                            @foreach ($galleryImages as $image)
                                <div class="gallery-item position-relative" data-image="{{ $image }}">
                                    <span class="gallery-order badge bg-dark position-absolute"
                                        style="
                        top:5px;
                        left:5px;
                        z-index:2;
                        ">
                                        {{ $loop->iteration }}
                                    </span>
                                    <img src="{{ asset('backend/assets/images/product/' . $image) }}"
                                        class="rounded border"
                                        style="
                        width:120px;
                        height:120px;
                        object-fit:cover;
                        cursor:move;
                        ">
                                    <div class="form-check mt-2">
                                        <input class="form-check-input" type="checkbox" name="remove_images[]"
                                            value="{{ $image }}" id="remove{{ $loop->index }}">
                                        <label class="form-check-label text-danger" for="remove{{ $loop->index }}">
                                            Remove
                                        </label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    {{-- SCRIPTS --}}
                    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
                    <script>
                        document.addEventListener('DOMContentLoaded', function() {

                            // =========================================
                            // THUMBNAIL PREVIEW
                            // =========================================

                            const thumbnailInput = document.getElementById('thumbnailInput');

                            if (thumbnailInput) {

                                thumbnailInput.addEventListener('change', function(e) {

                                    const preview = document.getElementById('thumbnailPreview');

                                    preview.innerHTML = '';

                                    if (e.target.files[0]) {

                                        let reader = new FileReader();

                                        reader.onload = function(event) {

                                            preview.innerHTML = `
                                       <img src="${event.target.result}"
                                           class="rounded border"
                                           style="
                                               width:120px;
                                               height:120px;
                                               object-fit:cover;
                                           ">
                                   `;

                                        };

                                        reader.readAsDataURL(e.target.files[0]);

                                    }

                                });

                            }

                            // =========================================
                            // GALLERY PREVIEW
                            // =========================================

                            const galleryInput = document.getElementById('galleryInput');

                            if (galleryInput) {

                                galleryInput.addEventListener('change', function(e) {

                                    const gallery = document.getElementById('sortable-gallery');

                                    Array.from(e.target.files).forEach((file, index) => {

                                        let reader = new FileReader();

                                        reader.onload = function(event) {

                                            let uniqueId = 'new_' + Date.now() + index;

                                            gallery.insertAdjacentHTML('beforeend', `
               
                                       <div class="gallery-item position-relative"
                                           data-image="${uniqueId}">
               
                                           <span class="gallery-order badge bg-dark position-absolute"
                                               style="
                                                   top:5px;
                                                   left:5px;
                                                   z-index:2;
                                               ">
               
                                               ${document.querySelectorAll('.gallery-item').length + 1}
               
                                           </span>
               
                                           <img src="${event.target.result}"
                                               class="rounded border"
                                               style="
                                                   width:120px;
                                                   height:120px;
                                                   object-fit:cover;
                                                   cursor:move;
                                               ">
               
                                       </div>
               
                                   `);

                                            updateGalleryOrder();

                                        };

                                        reader.readAsDataURL(file);

                                    });

                                });

                            }

                            // =========================================
                            // SORTABLE
                            // =========================================

                            let sortableGallery = document.getElementById('sortable-gallery');

                            if (sortableGallery) {

                                new Sortable(sortableGallery, {

                                    animation: 150,

                                    onEnd: function() {

                                        updateGalleryOrder();

                                    }

                                });

                            }

                            // =========================================
                            // UPDATE ORDER
                            // =========================================

                            function updateGalleryOrder() {

                                // Remove old hidden inputs

                                document.querySelectorAll('input[name="gallery_order[]"]')
                                    .forEach(el => el.remove());

                                // Update Order Numbers

                                document.querySelectorAll('.gallery-item')
                                    .forEach((item, index) => {

                                        item.querySelector('.gallery-order')
                                            .innerText = index + 1;

                                        let image = item.dataset.image;

                                        // Only existing images need hidden input

                                        if (!image.startsWith('new_')) {

                                            let input = document.createElement('input');

                                            input.type = 'hidden';

                                            input.name = 'gallery_order[]';

                                            input.value = image;

                                            sortableGallery.appendChild(input);
                                        }

                                    });

                            }

                        });
                    </script>

                    {{-- <div class="col-12">
      <label class="form-label">Short Details</label>
      <textarea name="details"
         id="details"
         class="form-control"
         rows="3">{{ $product->details }}</textarea>
      </div> --}}
                    <div class="col-12">
                        <label class="form-label">Description</label>
                        <textarea name="description" id="description" class="form-control" rows="5">{{ $product->description }}</textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Specification</label>
                        <textarea name="specification" id="specification" class="form-control" rows="5">{{ $product->specification }}</textarea>
                    </div>
                    <!-- FAQ SECTION -->
                    {{-- <div class="col-12 mt-4">
                        <div class="card border">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Product FAQs (Frequently Asked Questions)</h5>
                                <button type="button" class="btn btn-sm btn-primary" id="addFaqBtn">
                                    + Add FAQ
                                </button>
                            </div>
                            <div class="card-body">
                                <div id="faq-wrapper">
                                    @if ($product->faqs && $product->faqs->count() > 0)
                                        @foreach ($product->faqs as $index => $faq)
                                            <div class="faq-item border rounded p-3 mb-3"
                                                data-faq-id="{{ $faq->id }}">
                                                <div class="row g-3">
                                                    <div class="col-md-12">
                                                        <div class="d-flex justify-content-between align-items-start">
                                                            <label class="form-label fw-bold">Question
                                                                {{ $loop->iteration }}</label>
                                                            <div>
                                                                <input type="hidden"
                                                                    name="faqs[{{ $index }}][id]"
                                                                    value="{{ $faq->id }}">
                                                                <button type="button"
                                                                    class="btn btn-sm btn-danger remove-faq-btn">
                                                                    <i class="ti ti-trash"></i> Remove
                                                                </button>
                                                            </div>
                                                        </div>
                                                        <input type="text" name="faqs[{{ $index }}][question]"
                                                            class="form-control"
                                                            placeholder="e.g., What is the warranty period?"
                                                            value="{{ $faq->question }}">
                                                    </div>
                                                    <div class="col-md-12">
                                                        <label class="form-label">Answer</label>
                                                        <textarea name="faqs[{{ $index }}][answer]" class="form-control faq-answer" rows="3"
                                                            placeholder="Provide a detailed answer to the question...">{{ $faq->answer }}</textarea>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label">Status</label>
                                                        <select name="faqs[{{ $index }}][status]"
                                                            class="form-select">
                                                            <option value="1"
                                                                {{ $faq->status == 1 ? 'selected' : '' }}>Active</option>
                                                            <option value="0"
                                                                {{ $faq->status == 0 ? 'selected' : '' }}>Inactive</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    @else
                                        <div class="alert alert-info" id="noFaqMessage">
                                            <i class="ti ti-info-circle"></i> No FAQs added yet. Click "Add FAQ" to add
                                            frequently asked questions about this product.
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div> --}}

                    <!-- Add the FAQ JavaScript (same as above) -->
                    <div class="col-12 mt-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-device-floppy me-1"></i>
                            Update Product
                        </button>
                        <a href="{{ route('product.index') }}" class="btn btn-light">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
        <script>
            document.addEventListener('DOMContentLoaded', function() {

                let sortableGallery = document.getElementById('sortable-gallery');

                if (sortableGallery) {

                    new Sortable(sortableGallery, {

                        animation: 150,

                        onEnd: function() {

                            // Update Order Numbers

                            document.querySelectorAll('.gallery-item')
                                .forEach((item, index) => {

                                    item.querySelector('.gallery-order')
                                        .innerText = index + 1;

                                });

                            // Update Hidden Inputs Order

                            let inputs = [];

                            document.querySelectorAll('.gallery-item')
                                .forEach((item) => {

                                    let image = item.dataset.image;

                                    inputs.push(
                                        `<input type="hidden" name="gallery_order[]" value="${image}">`
                                    );

                                });

                            document.querySelectorAll('input[name="gallery_order[]"]')
                                .forEach(el => el.remove());

                            sortableGallery.insertAdjacentHTML(
                                'beforeend',
                                inputs.join('')
                            );

                        }

                    });

                }

            });
        </script>
        <script>
            document.getElementById('name').addEventListener('keyup', function() {

                let slug = this.value
                    .toLowerCase()
                    .trim()
                    .replace(/[^a-z0-9\s-]/g, '')
                    .replace(/\s+/g, '-')
                    .replace(/-+/g, '-');

                document.getElementById('slug').value = slug;

            });
        </script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {

                // Auto Slug

                document.getElementById('name').addEventListener('keyup', function() {

                    let slug = this.value
                        .toLowerCase()
                        .trim()
                        .replace(/[^a-z0-9\s-]/g, '')
                        .replace(/\s+/g, '-')
                        .replace(/-+/g, '-');

                    document.getElementById('slug').value = slug;

                });

                // CKEditor (details field hidden)

                ClassicEditor
                    .create(document.querySelector('#description'));

                ClassicEditor
                    .create(document.querySelector('#specification'))
                    .catch(error => {
                        console.error(error);
                    });

            });
        </script>
        <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const categorySelect = document.getElementById('category_id');
                const subcategorySelect = document.getElementById('subcategory_id');
                const childcategorySelect = document.getElementById('child_category_id');

                function loadChildCategories(subcategoryId, selectedChildCategoryId = null) {
                    if (!childcategorySelect) return;
                    if (!subcategoryId) {
                        childcategorySelect.innerHTML = '<option value="">Select Child Category</option>';
                        return;
                    }

                    childcategorySelect.innerHTML = '<option value="">Loading...</option>';
                    let baseUrl = "{{ url('/') }}";
                    let fetchUrl = `${baseUrl}/admin/get-childcategories/${subcategoryId}`;

                    fetch(fetchUrl, {
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        })
                        .then(response => {
                            if (!response.ok) {
                                throw new Error(`HTTP error! status: ${response.status}`);
                            }
                            return response.json();
                        })
                        .then(data => {
                            childcategorySelect.innerHTML = '<option value="">Select Child Category</option>';

                            if (!data || data.length === 0) {
                                return;
                            }

                            data.forEach(child => {
                                const option = document.createElement('option');
                                option.value = child.id;
                                option.textContent = child.name;

                                if (selectedChildCategoryId && String(selectedChildCategoryId) === String(
                                        child.id)) {
                                    option.selected = true;
                                }

                                childcategorySelect.appendChild(option);
                            });
                        })
                        .catch(error => {
                            console.error('Error loading child categories:', error);
                            childcategorySelect.innerHTML = '<option value="">Select Child Category</option>';
                        });
                }

                function loadSubcategories(categoryId, selectedSubcategoryId = null, selectedChildCategoryId = null) {
                    if (!categoryId) {
                        subcategorySelect.innerHTML = '<option value="">Select Sub Category</option>';
                        if (childcategorySelect) {
                            childcategorySelect.innerHTML = '<option value="">Select Child Category</option>';
                        }
                        return;
                    }

                    subcategorySelect.innerHTML = '<option value="">Loading...</option>';
                    let baseUrl = "{{ url('/') }}";
                    let fetchUrl = `${baseUrl}/admin/get-subcategories/${categoryId}`;

                    fetch(fetchUrl, {
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        })
                        .then(response => {
                            if (!response.ok) {
                                throw new Error(`HTTP error! status: ${response.status}`);
                            }
                            return response.json();
                        })
                        .then(data => {
                            subcategorySelect.innerHTML = '<option value="">Select Sub Category</option>';

                            if (!data || data.length === 0) {
                                subcategorySelect.innerHTML =
                                    '<option value="">No subcategories available</option>';
                                if (childcategorySelect) {
                                    childcategorySelect.innerHTML =
                                        '<option value="">Select Child Category</option>';
                                }
                                return;
                            }

                            data.forEach(subcategory => {
                                const option = document.createElement('option');
                                option.value = subcategory.id;
                                option.textContent = subcategory.name;

                                if (selectedSubcategoryId && String(selectedSubcategoryId) === String(
                                        subcategory.id)) {
                                    option.selected = true;
                                }

                                subcategorySelect.appendChild(option);
                            });

                            if (selectedSubcategoryId) {
                                loadChildCategories(selectedSubcategoryId, selectedChildCategoryId);
                            }
                        })
                        .catch(error => {
                            console.error('Error loading subcategories:', error);
                            subcategorySelect.innerHTML = '<option value="">Error loading subcategories</option>';
                        });
                }

                if (categorySelect) {
                    categorySelect.addEventListener('change', function() {
                        loadSubcategories(this.value, null);
                        if (childcategorySelect) {
                            childcategorySelect.innerHTML = '<option value="">Select Child Category</option>';
                        }
                    });
                }

                if (subcategorySelect) {
                    subcategorySelect.addEventListener('change', function() {
                        loadChildCategories(this.value, null);
                    });
                }
            });
        </script>

        <script>
            // FAQ Management
            document.addEventListener('DOMContentLoaded', function() {
                let faqIndex = 0;
                const faqWrapper = document.getElementById('faq-wrapper');
                const addFaqBtn = document.getElementById('addFaqBtn');
                const noFaqMessage = document.getElementById('noFaqMessage');

                // Helper function to decode HTML entities
                function decodeHtml(html) {
                    if (!html) return '';
                    const txt = document.createElement('textarea');
                    txt.innerHTML = html;
                    return txt.value;
                }

                function updateNoFaqMessage() {
                    const faqItems = document.querySelectorAll('.faq-item');
                    if (faqItems.length === 0) {
                        if (!document.getElementById('noFaqMessage')) {
                            const message = document.createElement('div');
                            message.id = 'noFaqMessage';
                            message.className = 'alert alert-info';
                            message.innerHTML =
                                '<i class="ti ti-info-circle"></i> No FAQs added yet. Click "Add FAQ" to add frequently asked questions about this product.';
                            faqWrapper.appendChild(message);
                        }
                    } else {
                        const existingMessage = document.getElementById('noFaqMessage');
                        if (existingMessage) {
                            existingMessage.remove();
                        }
                    }
                }

                // Initialize existing FAQs with CKEditor
                function initializeExistingFaqs() {
                    document.querySelectorAll('.faq-item .faq-answer').forEach(textarea => {
                        // Decode HTML content if present
                        if (textarea.value) {
                            textarea.value = decodeHtml(textarea.value);
                        }

                        ClassicEditor
                            .create(textarea, {
                                toolbar: {
                                    items: [
                                        'bold', 'italic', 'underline', '|',
                                        'bulletedList', 'numberedList', '|',
                                        'link', '|',
                                        'undo', 'redo'
                                    ]
                                }
                            })
                            .catch(error => {
                                console.error('FAQ CKEditor error:', error);
                            });
                    });
                }

                function addFaq(faqData = null) {
                    const currentIndex = document.querySelectorAll('.faq-item').length;

                    const faqDiv = document.createElement('div');
                    faqDiv.className = 'faq-item border rounded p-3 mb-3';
                    faqDiv.setAttribute('data-faq-index', currentIndex);

                    const questionValue = faqData ? faqData.question.replace(/"/g, '&quot;') : '';
                    const answerValue = faqData ? decodeHtml(faqData.answer) : '';
                    // const orderValue = faqData ? faqData.order : currentIndex + 1;
                    const statusValue = faqData && faqData.status == 1 ? 1 : 1;

                    faqDiv.innerHTML = `
            <div class="row g-3">
                <div class="col-md-12">
                    <div class="d-flex justify-content-between align-items-start">
                        <label class="form-label fw-bold">Question ${currentIndex + 1}</label>
                        <button type="button" class="btn btn-sm btn-danger remove-faq-btn">
                            <i class="ti ti-trash"></i> Remove
                        </button>
                    </div>
                    <input type="text" 
                           name="faqs[${currentIndex}][question]" 
                           class="form-control" 
                           placeholder="e.g., What is the warranty period?"
                           value="${questionValue}">
                </div>
                <div class="col-md-12">
                    <label class="form-label">Answer</label>
                    <textarea name="faqs[${currentIndex}][answer]" 
                              class="form-control faq-answer" 
                              rows="3" 
                              placeholder="Provide a detailed answer to the question...">${answerValue}</textarea>
                </div>
                <!-- Order field commented out
                <div class="col-md-6">
                    <label class="form-label">Order</label>
                    <input type="number" 
                           name="faqs[${currentIndex}][order]" 
                           class="form-control" 
                           value=""
                           placeholder="Display order">
                </div>
                -->
                <div class="col-md-6">
                    <label class="form-label">Status</label>
                    <select name="faqs[${currentIndex}][status]" class="form-select">
                        <option value="1" ${statusValue == 1 ? 'selected' : ''}>Active</option>
                        <option value="0" ${statusValue == 0 ? 'selected' : ''}>Inactive</option>
                    </select>
                </div>
            </div>
        `;

                    faqWrapper.appendChild(faqDiv);

                    // Initialize CKEditor for the new FAQ answer
                    const textarea = faqDiv.querySelector('.faq-answer');
                    ClassicEditor
                        .create(textarea, {
                            toolbar: {
                                items: [
                                    'bold', 'italic', 'underline', '|',
                                    'bulletedList', 'numberedList', '|',
                                    'link', '|',
                                    'undo', 'redo'
                                ]
                            }
                        })
                        .catch(error => {
                            console.error('FAQ CKEditor error:', error);
                        });

                    updateNoFaqMessage();
                }

                // Initialize existing FAQs on page load
                initializeExistingFaqs();

                // Add FAQ button click
                if (addFaqBtn) {
                    addFaqBtn.addEventListener('click', function() {
                        addFaq();
                    });
                }

                // Remove FAQ
                document.addEventListener('click', function(e) {
                    if (e.target.classList.contains('remove-faq-btn') || e.target.closest('.remove-faq-btn')) {
                        const btn = e.target.classList.contains('remove-faq-btn') ? e.target : e.target.closest(
                            '.remove-faq-btn');
                        const faqItem = btn.closest('.faq-item');
                        if (faqItem && confirm('Are you sure you want to remove this FAQ?')) {
                            faqItem.remove();
                            updateNoFaqMessage();

                            // Update question numbers
                            document.querySelectorAll('.faq-item').forEach((item, idx) => {
                                const label = item.querySelector('.fw-bold');
                                if (label) {
                                    label.textContent = `Question ${idx + 1}`;
                                }
                            });
                        }
                    }
                });
            });
        </script>
        <script>
            document.getElementById('videoInput')?.addEventListener('change', function(e) {

                const file = e.target.files[0];
                const preview = document.getElementById('videoPreview');
                const currentVideo = document.getElementById('currentVideo');

                preview.innerHTML = '';

                if (file) {

                    // Completely remove old video preview
                    currentVideo.innerHTML = '';

                    const url = URL.createObjectURL(file);

                    preview.innerHTML = `
            <video width="200" controls class="rounded border">
                <source src="${url}" type="${file.type}">
                Your browser does not support video.
            </video>
        `;

                }
            });
        </script>
    @endsection
