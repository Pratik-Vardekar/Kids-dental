@extends('layouts.admin')
@section('content')
    <div class="container-fluid my-2">
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <div>
                <h4 class="mb-1">Add Product</h4>
                <p class="text-muted mb-0">Create new product</p>
            </div>
            <div>
                <a href="{{ route('product.index') }}" class="btn btn-secondary">
                    <i class="ti ti-arrow-left me-1"></i> Back to Products
                </a>
            </div>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                <h6 class="alert-heading fw-bold mb-1">Please fix the following errors:</h6>
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <form action="{{ route('product.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <!-- 1. BASIC INFORMATION CARD -->
            <div class="card border rounded-3 mb-4 shadow-none">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="card-title mb-0 fw-bold">
                        <i class="ti ti-info-circle text-primary me-2"></i>Basic Information
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Product Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="name" class="form-control"
                                value="{{ old('name') }}" placeholder="Enter Product Name" required>
                            @error('name')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">SKU <span class="text-danger">*</span></label>
                            <input type="text" name="sku" id="sku" class="form-control"
                                value="{{ old('sku') }}" placeholder="Enter SKU" required>
                            @error('sku')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Slug</label>
                            <input type="text" name="slug" id="slug" value="{{ old('slug') }}"
                                class="form-control" placeholder="auto-generated-slug">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Category <span class="text-danger">*</span></label>
                            <select name="category_id" id="category_id" class="form-select" required>
                                <option value="">Select Category</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}"
                                        {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Sub Category <small class="text-muted">(Optional)</small></label>
                            <select name="subcategory_id" id="subcategory_id" class="form-select">
                                <option value="">Select Sub Category</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Child Category <small class="text-muted">(Optional)</small></label>
                            <select name="child_category_id" id="child_category_id" class="form-select">
                                <option value="">Select Child Category</option>
                            </select>
                            @error('child_category_id')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. PRICING & INVENTORY CARD -->
            <div class="card border rounded-3 mb-4 shadow-none">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="card-title mb-0 fw-bold">
                        <i class="ti ti-currency-rupee text-primary me-2"></i>Pricing & Inventory
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Price (₹) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0" name="price" value="{{ old('price') }}"
                                class="form-control" id="price" placeholder="0.00" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Discount Type</label>
                            <select name="discount_type" id="discount_type" class="form-select">
                                <option value="percent" {{ old('discount_type', 'percent') == 'percent' ? 'selected' : '' }}>
                                    Percentage (%)
                                </option>
                                <option value="flat" {{ old('discount_type') == 'flat' ? 'selected' : '' }}>
                                    Flat Amount (₹)
                                </option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Discount</label>
                            <div class="input-group">
                                <input type="number" step="0.01" min="0" name="discount" class="form-control"
                                    value="{{ old('discount', 0) }}" id="discount" placeholder="0">
                                <span class="input-group-text" id="discountTypeSymbol">%</span>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Selling Price (₹)</label>
                            <input type="number" step="0.01" min="0" name="selling_price"
                                value="{{ old('selling_price') }}" class="form-control" id="selling_price"
                                placeholder="Auto calculated if discount">
                        </div>

                        <!-- Dynamic Discount Info Badge -->
                        <div class="col-12 mt-1">
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-light text-dark border px-2 py-1" id="discountDynamicInfo">
                                    <i class="ti ti-discount me-1"></i> No Discount
                                </span>
                                <small class="text-muted" id="priceCalculationHint">
                                    Type <strong>Price</strong> and <strong>Discount</strong> to calculate <strong>Selling Price</strong> automatically.
                                </small>
                            </div>
                        </div>

                        <!-- GST Tax Fields -->
                        <div class="col-12">
                            <div class="border rounded p-3 bg-light-subtle">
                                @include('admin.product.partials.gst-tax-fields', [
                                    'selectedGstId' => old('gst_setting_id'),
                                ])
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Quantity</label>
                            <input type="number" name="quantity" value="{{ old('quantity') }}" class="form-control" placeholder="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Product Type</label>
                            <select name="product_type" class="form-select">
                                <option value="simple" {{ old('product_type', 'simple') == 'simple' ? 'selected' : '' }}>Simple Product</option>
                                <option value="variable" {{ old('product_type') == 'variable' ? 'selected' : '' }}>Variable Product</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="1" {{ old('status', '1') == '1' ? 'selected' : '' }}>Active</option>
                                <option value="0" {{ old('status') == '0' ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. VARIANTS CONFIGURATION (Variable Product Only) -->
            @include('admin.product.partials.variant-section')

            <!-- 4. MEDIA & VISUALS CARD -->
            <div class="card border rounded-3 mb-4 shadow-none">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="card-title mb-0 fw-bold">
                        <i class="ti ti-photo text-primary me-2"></i>Product Media
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Thumbnail Image <span class="text-danger">*</span></label>
                            <input type="file" name="thumbnail" id="thumbnail" class="form-control" accept="image/*">
                            <div id="thumbnail-preview-container" class="mt-2 d-none">
                                <img id="thumbnail-preview" src="" class="rounded border" style="width:110px;height:110px;object-fit:cover;">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Product Video</label>
                            <input type="file" name="video" class="form-control" accept="video/*">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Gallery Images</label>
                            <input type="file" name="gallery[]" id="images" class="form-control" multiple accept="image/*">
                            <label class="form-label d-none mt-2" id="preview-label">Gallery Preview:</label>
                            <div id="preview-container" class="d-flex flex-wrap gap-2 mt-2"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 5. DESCRIPTION & SPECIFICATIONS CARD -->
            <div class="card border rounded-3 mb-4 shadow-none">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="card-title mb-0 fw-bold">
                        <i class="ti ti-file-text text-primary me-2"></i>Description & Specifications
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" id="description" class="form-control" rows="6" placeholder="Enter product description...">{{ old('description') }}</textarea>
                            @error('description')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label">Specification</label>
                            <textarea name="specification" id="specification" class="form-control" rows="6" placeholder="Enter technical specifications...">{{ old('specification') }}</textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label d-block mb-2">Product Labels</label>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_featured" id="is_featured" value="1"
                                    {{ old('is_featured') ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_featured">
                                    Featured Product
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Badge</label>
                            <input type="text" name="badge" class="form-control" value="{{ old('badge') }}"
                                placeholder="e.g. BESTSELLER, HOT, NEW">
                        </div>
                    </div>
                </div>
            </div>

            <!-- 6. SUBMIT ACTION BUTTONS -->
            <div class="d-flex justify-content-between align-items-center mb-5 pb-3">
                <a href="{{ route('product.index') }}" class="btn btn-secondary px-4">
                    <i class="ti ti-arrow-left me-1"></i> Cancel
                </a>
                <button type="submit" class="btn btn-primary btn-lg px-5 fw-bold">
                    <i class="ti ti-device-floppy me-1"></i> Save Product
                </button>
            </div>
        </form>
    </div>
    @endsection
    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {

                // ======= PRICE, DISCOUNT TYPE, DISCOUNT, SELLING PRICE DYNAMIC CALCULATION =======
                const priceInput = document.getElementById('price');
                const discountTypeSelect = document.getElementById('discount_type');
                const discountInput = document.getElementById('discount');
                const discountTypeSymbol = document.getElementById('discountTypeSymbol');
                const sellingPriceInput = document.getElementById('selling_price');
                const discountInfo = document.getElementById('discountDynamicInfo');

                let isUpdatingPrice = false;

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

                    const price = parseFloat(priceInput.value) || 0;
                    const discount = parseFloat(discountInput.value) || 0;
                    const type = discountTypeSelect.value;
                    let sellingPrice = price;

                    if (price > 0 && discount > 0) {
                        if (type === 'percent') {
                            const discountAmount = (price * discount) / 100;
                            sellingPrice = Math.max(0, price - discountAmount);
                        } else {
                            sellingPrice = Math.max(0, price - discount);
                        }
                        sellingPriceInput.value = sellingPrice.toFixed(2);
                    } else if (price > 0 && discount === 0) {
                        sellingPriceInput.value = price.toFixed(2);
                    }

                    updateBadge(price, parseFloat(sellingPriceInput.value) || 0, discount, type);
                    sellingPriceInput.dispatchEvent(new Event('input'));
                    isUpdatingPrice = false;
                }

                function calculateDiscountFromSellingPrice() {
                    if (isUpdatingPrice) return;
                    isUpdatingPrice = true;

                    const price = parseFloat(priceInput.value) || 0;
                    const sellingPrice = parseFloat(sellingPriceInput.value) || 0;
                    const type = discountTypeSelect.value;

                    if (price > 0 && sellingPrice > 0 && sellingPrice < price) {
                        if (type === 'percent') {
                            const percent = Math.round(((price - sellingPrice) / price) * 100);
                            discountInput.value = percent;
                        } else {
                            const flatDiff = +(price - sellingPrice).toFixed(2);
                            discountInput.value = flatDiff;
                        }
                    } else if (sellingPrice >= price || sellingPrice <= 0) {
                        discountInput.value = 0;
                    }

                    updateBadge(price, sellingPrice, parseFloat(discountInput.value) || 0, type);
                    isUpdatingPrice = false;
                }

                if (discountTypeSelect) {
                    discountTypeSelect.addEventListener('change', function() {
                        const type = this.value;
                        if (discountTypeSymbol) discountTypeSymbol.textContent = type === 'percent' ? '%' : '₹';

                        const price = parseFloat(priceInput.value) || 0;
                        const sellingPrice = parseFloat(sellingPriceInput.value) || 0;

                        if (price > 0 && sellingPrice > 0 && sellingPrice < price) {
                            if (type === 'percent') {
                                discountInput.value = Math.round(((price - sellingPrice) / price) * 100);
                            } else {
                                discountInput.value = +(price - sellingPrice).toFixed(2);
                            }
                        }
                        updateBadge(price, sellingPrice, parseFloat(discountInput.value) || 0, type);
                    });
                }

                if (priceInput) priceInput.addEventListener('input', calculateSellingPriceFromDiscount);
                if (discountInput) discountInput.addEventListener('input', calculateSellingPriceFromDiscount);
                if (sellingPriceInput) sellingPriceInput.addEventListener('input', calculateDiscountFromSellingPrice);

                // Initial calculation on page load
                const initialPrice = parseFloat(priceInput?.value) || 0;
                const initialSellingPrice = parseFloat(sellingPriceInput?.value) || 0;
                const initialDiscount = parseFloat(discountInput?.value) || 0;

                if (initialPrice > 0 && initialSellingPrice > 0 && initialDiscount === 0 && initialSellingPrice <
                    initialPrice) {
                    calculateDiscountFromSellingPrice();
                } else if (priceInput) {
                    updateBadge(initialPrice, initialSellingPrice, initialDiscount, discountTypeSelect?.value ||
                        'percent');
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

                // =========================================
                // AUTO SLUG
                // =========================================

                document.getElementById('name').addEventListener('keyup', function() {

                    let slug = this.value
                        .toLowerCase()
                        .trim()
                        .replace(/[^a-z0-9\s-]/g, '')
                        .replace(/\s+/g, '-')
                        .replace(/-+/g, '-');

                    document.getElementById('slug').value = slug;

                });

                // =========================================
                // THUMBNAIL PREVIEW
                // =========================================

                document.getElementById('thumbnail')
                    .addEventListener('change', function(e) {

                        let file = e.target.files[0];

                        if (file) {

                            let reader = new FileReader();

                            reader.onload = function(event) {

                                document.getElementById('thumbnail-preview').src =
                                    event.target.result;

                                document.getElementById('thumbnail-preview-container')
                                    .classList.remove('d-none');

                            };

                            reader.readAsDataURL(file);

                        }

                    });

                // =========================================
                // GALLERY PREVIEW + REMOVE + SORT
                // =========================================

                // =========================================
                // GALLERY PREVIEW + REMOVE + SORT + PERSIST
                // =========================================

                let selectedFiles = [];

                const imagesInput = document.getElementById('images');

                const previewContainer = document.getElementById('preview-container');

                // LOAD SAVED PREVIEW AFTER VALIDATION ERROR
                // LOAD ONLY AFTER VALIDATION ERROR 
                @if ($errors->any())

                    loadSavedGallery();
                @else

                    localStorage.removeItem('product_gallery');
                @endif

                imagesInput.addEventListener('change', function(e) {

                    let newFiles = Array.from(e.target.files);

                    selectedFiles = [...selectedFiles, ...newFiles];

                    saveGalleryToStorage();

                    rebuildInputFiles();

                    renderGallery();

                });

                function renderGallery() {

                    previewContainer.innerHTML = '';

                    if (selectedFiles.length > 0) {

                        document.getElementById('preview-label')
                            .classList.remove('d-none');

                    } else {

                        document.getElementById('preview-label')
                            .classList.add('d-none');

                    }

                    selectedFiles.forEach((file, index) => {

                        const div = document.createElement('div');

                        div.classList.add('gallery-item');

                        div.dataset.index = index;

                        div.style.position = 'relative';
                        div.style.cursor = 'move';

                        div.innerHTML = `
   
               <button type="button"
                   class="btn btn-danger btn-sm remove-image"
                   style="
                       position:absolute;
                       top:-10px;
                       right:-10px;
                       border-radius:50%;
                       width:28px;
                       height:28px;
                       padding:0;
                       z-index:5;
                   ">
                   ×
               </button>
   
               <span class="image-order"
                   style="
                       position:absolute;
                       top:5px;
                       left:5px;
                       background:#000;
                       color:#fff;
                       padding:2px 8px;
                       border-radius:20px;
                       font-size:12px;
                       z-index:2;
                   ">
                   ${index + 1}
               </span>
   
               <img
                   class="rounded border"
                   style="
                       width:120px;
                       height:120px;
                       object-fit:cover;
                   ">
           `;

                        const img = div.querySelector('img');

                        // REMOVE IMAGE
                        div.querySelector('.remove-image')
                            .addEventListener('click', function() {

                                let removeIndex = parseInt(div.dataset.index);

                                selectedFiles.splice(removeIndex, 1);

                                saveGalleryToStorage();

                                rebuildInputFiles();

                                renderGallery();

                            });

                        previewContainer.appendChild(div);

                        let reader = new FileReader();

                        reader.onload = function(e) {

                            img.src = e.target.result;

                        };

                        reader.readAsDataURL(file);

                    });

                }

                // =========================================
                // REBUILD FILE INPUT
                // =========================================

                function rebuildInputFiles() {

                    let dataTransfer = new DataTransfer();

                    selectedFiles.forEach(file => {

                        dataTransfer.items.add(file);

                    });

                    imagesInput.files = dataTransfer.files;

                }

                // =========================================
                // SORTABLE
                // =========================================

                new Sortable(previewContainer, {

                    animation: 150,

                    onEnd: function() {

                        let newOrder = [];

                        document.querySelectorAll('.gallery-item')
                            .forEach((item) => {

                                let currentIndex = parseInt(item.dataset.index);

                                newOrder.push(selectedFiles[currentIndex]);

                            });

                        selectedFiles = newOrder;

                        saveGalleryToStorage();

                        rebuildInputFiles();

                        renderGallery();

                    }

                });

                // =========================================
                // SAVE GALLERY TO STORAGE
                // =========================================

                function saveGalleryToStorage() {

                    let promises = selectedFiles.map(file => {

                        return new Promise(resolve => {

                            let reader = new FileReader();

                            reader.onload = function(e) {

                                resolve({
                                    name: file.name,
                                    type: file.type,
                                    data: e.target.result
                                });

                            };

                            reader.readAsDataURL(file);

                        });

                    });

                    Promise.all(promises).then(images => {

                        localStorage.setItem(
                            'product_gallery',
                            JSON.stringify(images)
                        );

                    });

                }

                // =========================================
                // LOAD SAVED GALLERY
                // =========================================

                function loadSavedGallery() {

                    let saved = localStorage.getItem('product_gallery');

                    if (!saved) return;

                    let images = JSON.parse(saved);

                    images.forEach(image => {

                        let arr = image.data.split(',');
                        let mime = arr[0].match(/:(.*?);/)[1];
                        let bstr = atob(arr[1]);

                        let n = bstr.length;

                        let u8arr = new Uint8Array(n);

                        while (n--) {

                            u8arr[n] = bstr.charCodeAt(n);

                        }

                        let file = new File(
                            [u8arr],
                            image.name, {
                                type: mime
                            }
                        );

                        selectedFiles.push(file);

                    });

                    rebuildInputFiles();

                    renderGallery();

                }

                // =========================================
                // CLEAR STORAGE AFTER SUCCESS SUBMIT
                // =========================================

                document.querySelector('form')
                    .addEventListener('submit', function() {

                        localStorage.removeItem('product_gallery');

                    });

                // =========================================
                // CKEDITOR (details field hidden)

                ClassicEditor
                    .create(document.querySelector('#description'));

                ClassicEditor
                    .create(document.querySelector('#specification'));

                const categorySelect = document.getElementById('category_id');
                const subcategoryDropdown = document.getElementById('subcategory_id');
                const childcategoryDropdown = document.getElementById('child_category_id');

                function loadChildCategories(subcategoryId, selectedChildCategoryId = null) {
                    if (!childcategoryDropdown) return;
                    if (!subcategoryId) {
                        childcategoryDropdown.innerHTML = '<option value="">Select Child Category</option>';
                        return;
                    }

                    childcategoryDropdown.innerHTML = '<option value="">Loading...</option>';

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
                            childcategoryDropdown.innerHTML = '<option value="">Select Child Category</option>';

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
                                childcategoryDropdown.appendChild(option);
                            });
                        })
                        .catch(error => {
                            console.error('Fetch Child Categories Error:', error);
                            childcategoryDropdown.innerHTML = '<option value="">Select Child Category</option>';
                        });
                }

                function loadSubcategories(categoryId, selectedSubcategoryId = null, selectedChildCategoryId = null) {
                    if (!categoryId) {
                        subcategoryDropdown.innerHTML = '<option value="">Select Sub Category</option>';
                        if (childcategoryDropdown) {
                            childcategoryDropdown.innerHTML = '<option value="">Select Child Category</option>';
                        }
                        return;
                    }

                    subcategoryDropdown.innerHTML = '<option value="">Loading...</option>';

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
                            subcategoryDropdown.innerHTML = '<option value="">Select Sub Category</option>';

                            if (!data || data.length === 0) {
                                subcategoryDropdown.innerHTML = '<option value="">No subcategories found</option>';
                                if (childcategoryDropdown) {
                                    childcategoryDropdown.innerHTML =
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
                                subcategoryDropdown.appendChild(option);
                            });

                            if (selectedSubcategoryId) {
                                loadChildCategories(selectedSubcategoryId, selectedChildCategoryId);
                            }
                        })
                        .catch(error => {
                            console.error('Fetch Error:', error);
                            subcategoryDropdown.innerHTML = '<option value="">Error loading subcategories</option>';
                        });
                }

                if (categorySelect) {
                    categorySelect.addEventListener('change', function() {
                        loadSubcategories(this.value, null);
                        if (childcategoryDropdown) {
                            childcategoryDropdown.innerHTML = '<option value="">Select Child Category</option>';
                        }
                    });

                    @if (old('category_id'))
                        loadSubcategories("{{ old('category_id') }}", "{{ old('subcategory_id') }}",
                            "{{ old('child_category_id') }}");
                    @endif
                }

                if (subcategoryDropdown) {
                    subcategoryDropdown.addEventListener('change', function() {
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

                function updateNoFaqMessage() {
                    const faqItems = document.querySelectorAll('.faq-item');
                    if (faqItems.length === 0) {
                        if (!noFaqMessage) {
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

                function addFaq(faqData = null) {
                    const currentIndex = document.querySelectorAll('.faq-item').length;

                    const faqDiv = document.createElement('div');
                    faqDiv.className = 'faq-item border rounded p-3 mb-3';
                    faqDiv.setAttribute('data-faq-index', currentIndex);

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
                           value="${faqData ? faqData.question.replace(/"/g, '&quot;') : ''}">
                </div>
                <div class="col-md-12">
                    <label class="form-label">Answer</label>
                    <textarea name="faqs[${currentIndex}][answer]" 
                              class="form-control faq-answer" 
                              rows="3" 
                              placeholder="Provide a detailed answer to the question...">${faqData ? faqData.answer.replace(/"/g, '&quot;') : ''}</textarea>
                </div>
                <!-- Order field commented out
                <div class="col-md-6">
                    <label class="form-label">Order</label>
                    <input type="number" 
                           name="faqs[${currentIndex}][order]" 
                           class="form-control" 
                           value="${faqData ? faqData.order : currentIndex + 1}"
                           placeholder="Display order">
                </div>
                -->
                <div class="col-md-6">
                    <label class="form-label">Status</label>
                    <select name="faqs[${currentIndex}][status]" class="form-select">
                        <option value="1" ${faqData && faqData.status == 1 ? 'selected' : 'selected'}>Active</option>
                        <option value="0" ${faqData && faqData.status == 0 ? 'selected' : ''}>Inactive</option>
                    </select>
                </div>
            </div>
        `;

                    faqWrapper.appendChild(faqDiv);

                    // Initialize CKEditor for the answer textarea
                    if (typeof ClassicEditor !== 'undefined') {
                        const textarea = faqDiv.querySelector('.faq-answer');
                        ClassicEditor.create(textarea).catch(error => {
                            console.error(error);
                        });
                    }

                    updateNoFaqMessage();
                }

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
    @endpush
