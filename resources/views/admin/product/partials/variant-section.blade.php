@php
    $archesList = ['Lower Left', 'Lower Right', 'Upper Left', 'Upper Right'];
    $sizesList = ['1', '2', '3', '4', '5', '6', '7'];
    $existingVariants = isset($product) && $product->variants ? $product->variants : collect();
    $isEditMode = isset($product) && !empty($product->id);
    $productId = $isEditMode ? $product->id : null;
@endphp

<div id="variant-section"
    class="col-12 {{ old('product_type', $product->product_type ?? 'simple') == 'variable' ? '' : 'd-none' }}">

    <div class="card border rounded-3 shadow-none mb-4 bg-white">
        <!-- Section Header -->
        <div class="card-header bg-white py-3 d-flex flex-wrap justify-content-between align-items-center gap-2 border-bottom">
            <div>
                <h5 class="mb-0 fw-bold text-dark">
                    <i class="ti ti-layers-intersect text-primary me-1"></i> Product Variants Configuration
                </h5>
                <small class="text-muted">Select Arch and Sizes to generate variant combinations</small>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 rounded-pill fw-semibold" id="variantCountBadge">
                    0 Variants Generated
                </span>
                <button type="button" class="btn btn-outline-danger btn-sm" id="clearAllVariantsBtn">
                    <i class="ti ti-trash me-1"></i> Clear Table
                </button>
            </div>
        </div>

        <div class="card-body p-3">

            <!-- GENERATOR ROW: Arch & Size Selection -->
            <div class="row g-3 mb-3">
                <!-- ARCH SELECTION CARD -->
                <div class="col-lg-6">
                    <div class="border rounded-3 p-3 bg-white h-100 shadow-none">
                        <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                            <label class="form-label fw-bold mb-0 text-dark small text-uppercase" style="letter-spacing: 0.5px;">
                                <i class="ti ti-dental text-primary me-1"></i>Select Arch
                            </label>
                            <div class="small">
                                <a href="javascript:void(0)" class="text-decoration-none me-1 fw-semibold text-primary" id="selectAllArchesBtn">Select All</a>
                                <span class="text-muted">|</span>
                                <a href="javascript:void(0)" class="text-muted text-decoration-none ms-1" id="clearAllArchesBtn">Clear</a>
                            </div>
                        </div>
                        <div class="d-flex flex-wrap gap-2 pt-1" id="archCheckboxesContainer">
                            @foreach ($archesList as $arch)
                                <label class="arch-pill-check" for="arch_{{ Str::slug($arch) }}">
                                    <input class="form-check-input arch-checkbox" type="checkbox"
                                        id="arch_{{ Str::slug($arch) }}" value="{{ $arch }}">
                                    <span class="pill-text">{{ $arch }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- SIZE SELECTION CARD -->
                <div class="col-lg-6">
                    <div class="border rounded-3 p-3 bg-white h-100 shadow-none">
                        <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                            <label class="form-label fw-bold mb-0 text-dark small text-uppercase" style="letter-spacing: 0.5px;">
                                <i class="ti ti-ruler-2 text-primary me-1"></i>Select Size
                            </label>
                            <div class="small">
                                <a href="javascript:void(0)" class="text-decoration-none me-1 fw-semibold text-primary" id="selectAllSizesBtn">Select All</a>
                                <span class="text-muted">|</span>
                                <a href="javascript:void(0)" class="text-muted text-decoration-none ms-1" id="clearAllSizesBtn">Clear</a>
                            </div>
                        </div>
                        <div class="d-flex flex-wrap gap-2 pt-1" id="sizeCheckboxesContainer">
                            @foreach ($sizesList as $size)
                                <label class="size-pill-check" for="size_{{ $size }}">
                                    <input class="form-check-input size-checkbox" type="checkbox"
                                        id="size_{{ $size }}" value="{{ $size }}">
                                    <span class="pill-text">Size {{ $size }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <!-- ADD BUTTON BAR -->
            <div class="d-flex flex-wrap justify-content-between align-items-center bg-light border rounded-3 p-3 mb-3 gap-2">
                <button type="button" class="btn btn-warning btn-sm px-4 fw-bold text-dark shadow-sm" id="addVariantsBtn">
                    <i class="ti ti-plus me-1"></i> Add Variants
                </button>
                <span class="text-muted small">
                    <i class="ti ti-info-circle me-1"></i> Select <strong>Arch</strong> & <strong>Size</strong> above, then click <strong>Add Variants</strong>. Remember to click <strong>{{ $isEditMode ? 'Update Product' : 'Save Product' }}</strong> below to save changes.
                </span>
            </div>

            <!-- LIVE FEEDBACK TOAST / ALERT -->
            <div id="variantLiveFeedback" class="alert d-none my-2 py-2 px-3 align-items-center justify-content-between" role="alert">
                <div class="d-flex align-items-center">
                    <span id="variantLiveFeedbackIcon" class="me-2"></span>
                    <span id="variantLiveFeedbackText" class="fw-semibold"></span>
                </div>
                <button type="button" class="btn-close ms-2" onclick="document.getElementById('variantLiveFeedback').classList.add('d-none')"></button>
            </div>

            <!-- UNIFIED VARIANTS TABLE CARD -->
            <div class="card border rounded-3 shadow-none overflow-hidden mb-2">
                <!-- TOOLBAR (Filters on Left, Search + Bulk Toggle on Right) -->
                <div class="card-header bg-light py-2 px-3 border-bottom">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <!-- Arch Filter Pills -->
                        <div class="d-flex align-items-center flex-wrap gap-1" id="archFilterButtonGroup">
                            <span class="text-muted small fw-bold me-1"><i class="ti ti-filter me-1"></i>Arch:</span>
                            <button type="button" class="btn btn-xs btn-primary arch-filter-btn" data-arch="all">
                                All <span class="badge bg-white text-dark ms-1" id="filterCountAll">0</span>
                            </button>
                            <button type="button" class="btn btn-xs btn-outline-secondary arch-filter-btn" data-arch="Lower Left">
                                Lower Left <span class="badge bg-secondary ms-1" id="filterCountLL">0</span>
                            </button>
                            <button type="button" class="btn btn-xs btn-outline-secondary arch-filter-btn" data-arch="Lower Right">
                                Lower Right <span class="badge bg-secondary ms-1" id="filterCountLR">0</span>
                            </button>
                            <button type="button" class="btn btn-xs btn-outline-secondary arch-filter-btn" data-arch="Upper Left">
                                Upper Left <span class="badge bg-secondary ms-1" id="filterCountUL">0</span>
                            </button>
                            <button type="button" class="btn btn-xs btn-outline-secondary arch-filter-btn" data-arch="Upper Right">
                                Upper Right <span class="badge bg-secondary ms-1" id="filterCountUR">0</span>
                            </button>
                        </div>

                        <!-- Right Side: Search & Bulk Action Toggle Button -->
                        <div class="d-flex align-items-center gap-2">
                            <div class="input-group input-group-sm" style="min-width: 220px; max-width: 280px;">
                                <span class="input-group-text bg-white border-end-0"><i class="ti ti-search text-muted"></i></span>
                                <input type="text" id="variantSearchInput" class="form-control border-start-0" placeholder="Search variants...">
                                <button class="btn btn-outline-secondary" type="button" id="clearVariantSearchBtn" title="Clear Search">
                                    <i class="ti ti-x"></i>
                                </button>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-dark" id="toggleBulkActionsBtn"
                                data-bs-toggle="collapse" data-bs-target="#bulkActionsCollapse" aria-expanded="true"
                                title="Toggle Bulk Actions Bar">
                                <i class="ti ti-bolt text-warning me-1"></i> Bulk Apply <i class="ti ti-chevron-down ms-1"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- COMPACT INLINE BULK ACTIONS BAR (Collapsible, open by default or toggled) -->
                <div class="collapse show" id="bulkActionsCollapse">
                    <div class="bg-white border-bottom p-2 px-3">
                        <div class="row g-2 align-items-center">
                            <div class="col-auto">
                                <span class="small fw-bold text-muted text-uppercase" style="font-size: 11px; letter-spacing: 0.5px;">
                                    <i class="ti ti-bolt text-warning me-1"></i>Bulk Fill:
                                </span>
                            </div>
                            <div class="col-md-2 col-sm-4">
                                <input type="text" id="bulkSkuPrefix" class="form-control form-control-sm" placeholder="SKU Prefix (e.g. KED)">
                            </div>
                            <div class="col-md-2 col-sm-4">
                                <input type="number" step="0.01" id="bulkPrice" class="form-control form-control-sm" placeholder="Price (₹)">
                            </div>
                            <div class="col-md-2 col-sm-4">
                                <input type="number" step="0.01" id="bulkSellingPrice" class="form-control form-control-sm" placeholder="Selling (₹)">
                            </div>
                            <div class="col-md-2 col-sm-4">
                                <input type="number" id="bulkStock" class="form-control form-control-sm" placeholder="Stock Qty">
                            </div>
                            <div class="col-auto">
                                <div class="form-check mb-0">
                                    <input class="form-check-input" type="checkbox" id="bulkApplyVisibleOnly" checked>
                                    <label class="form-check-label small text-muted" for="bulkApplyVisibleOnly">
                                        Visible only
                                    </label>
                                </div>
                            </div>
                            <div class="col-auto ms-auto">
                                <button type="button" class="btn btn-sm btn-dark px-3" id="applyBulkValuesBtn">
                                    <i class="ti ti-check me-1"></i> Apply
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TABLE -->
                <div class="table-responsive">
                    <table class="table table-bordered table-sm table-hover align-middle mb-0" id="variantsMatrixTable">
                        <thead class="table-light">
                            <tr class="text-center text-nowrap">
                                <th style="width: 45px;">#</th>
                                <th style="width: 140px;">Arch</th>
                                <th style="width: 95px;">Size</th>
                                <th>SKU <span class="text-danger">*</span></th>
                                <th style="width: 125px;">Price (₹)</th>
                                <th style="width: 125px;">Selling Price (₹)</th>
                                <th style="width: 105px;">Stock Qty</th>
                                <th style="width: 110px;">Status</th>
                                <th style="width: 55px;">Action</th>
                            </tr>
                        </thead>
                        <tbody id="variantsTableBody">
                            {{-- Populated dynamically via JS --}}
                        </tbody>
                    </table>
                </div>

                <!-- EMPTY STATE ALERT -->
                <div id="noVariantsNotice" class="alert alert-light border-0 text-center my-0 py-4 d-none">
                    <i class="ti ti-box-seam fs-2 text-muted d-block mb-1"></i>
                    <span class="text-muted small">
                        Please select an <strong>Arch</strong> and <strong>Size</strong> above, then click <strong>+ Add Variants</strong>.
                    </span>
                </div>
            </div>

        </div>
    </div>
</div>

<style>
    .arch-checkbox,
    .size-checkbox {
        cursor: pointer;
    }

    .arch-pill-check,
    .size-pill-check {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        padding: 0.4rem 0.8rem;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        background-color: #ffffff;
        color: #1e293b;
        font-size: 0.875rem;
        font-weight: 500;
        cursor: pointer;
        user-select: none;
        transition: all 0.15s ease-in-out;
        margin-bottom: 0;
    }

    .arch-pill-check:hover,
    .size-pill-check:hover {
        border-color: #94a3b8;
        background-color: #f8fafc;
    }

    .arch-pill-check .arch-checkbox,
    .size-pill-check .size-checkbox {
        width: 1.1rem;
        height: 1.1rem;
        margin: 0;
        cursor: pointer;
        flex-shrink: 0;
    }

    .arch-pill-check:has(.arch-checkbox:checked),
    .size-pill-check:has(.size-checkbox:checked) {
        background-color: #eff6ff !important;
        border-color: #2563eb !important;
        box-shadow: 0 0 0 1px #2563eb;
    }

    .arch-pill-check:has(.arch-checkbox:checked) .pill-text,
    .size-pill-check:has(.size-checkbox:checked) .pill-text {
        font-weight: 700;
        color: #1d4ed8 !important;
    }

    #variantsMatrixTable th {
        font-size: 0.8rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        color: #64748b;
        background-color: #f8fafc;
        padding: 0.55rem 0.5rem;
    }

    #variantsMatrixTable td {
        padding: 0.45rem 0.5rem;
        vertical-align: middle;
    }

    #variantsMatrixTable input.form-control-sm,
    #variantsMatrixTable select.form-select-sm {
        font-size: 0.825rem;
        border-color: #e2e8f0;
        border-radius: 4px;
        padding: 0.25rem 0.5rem;
    }

    #variantsMatrixTable input.form-control-sm:focus,
    #variantsMatrixTable select.form-select-sm:focus {
        border-color: #3b82f6;
        box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.15);
    }

    .btn-xs {
        padding: 0.22rem 0.55rem;
        font-size: 0.775rem;
        border-radius: 0.25rem;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const productTypeSelect = document.querySelector('select[name="product_type"]');
        const variantSection = document.getElementById('variant-section');
        const variantsTableBody = document.getElementById('variantsTableBody');
        const noVariantsNotice = document.getElementById('noVariantsNotice');
        const variantCountBadge = document.getElementById('variantCountBadge');

        const selectAllArchesBtn = document.getElementById('selectAllArchesBtn');
        const clearAllArchesBtn = document.getElementById('clearAllArchesBtn');
        const selectAllSizesBtn = document.getElementById('selectAllSizesBtn');
        const clearAllSizesBtn = document.getElementById('clearAllSizesBtn');

        const addVariantsBtn = document.getElementById('addVariantsBtn');
        const clearAllVariantsBtn = document.getElementById('clearAllVariantsBtn');

        const applyBulkValuesBtn = document.getElementById('applyBulkValuesBtn');
        const bulkApplyVisibleOnly = document.getElementById('bulkApplyVisibleOnly');
        const bulkSkuPrefixInput = document.getElementById('bulkSkuPrefix');
        const bulkPriceInput = document.getElementById('bulkPrice');
        const bulkSellingPriceInput = document.getElementById('bulkSellingPrice');
        const bulkStockInput = document.getElementById('bulkStock');

        const variantSearchInput = document.getElementById('variantSearchInput');
        const clearVariantSearchBtn = document.getElementById('clearVariantSearchBtn');

        const parentPriceInput = document.getElementById('price') || document.querySelector('input[name="price"]');
        const parentSellingPriceInput = document.getElementById('selling_price') || document.querySelector('input[name="selling_price"]');
        const parentSkuInput = document.getElementById('sku') || document.querySelector('input[name="sku"]');
        const parentQtyInput = document.querySelector('input[name="quantity"]');

        // Existing saved variants passed from backend (for edit page)
        const initialVariants = @json($existingVariants);
        const isEditMode = {{ $isEditMode ? 'true' : 'false' }};
        const currentProductId = {{ $productId ? $productId : 'null' }};
        const csrfToken = "{{ csrf_token() }}";
        const ajaxAddVariantsUrl = currentProductId ? "{{ url('admin/product') }}/" + currentProductId + "/ajax-add-variants" : null;
        const ajaxVariantBaseUrl = "{{ url('admin/product/variant') }}";

        function showFeedback(type, message) {
            const feedbackBox = document.getElementById('variantLiveFeedback');
            const feedbackText = document.getElementById('variantLiveFeedbackText');
            const feedbackIcon = document.getElementById('variantLiveFeedbackIcon');
            if (!feedbackBox || !feedbackText) return;
            feedbackBox.className = `alert alert-${type} my-2 py-2 px-3 d-flex align-items-center justify-content-between border`;
            feedbackText.textContent = message;
            feedbackIcon.innerHTML = type === 'success' ? '<i class="ti ti-circle-check fs-5 text-success"></i>' : (type === 'danger' ? '<i class="ti ti-alert-triangle fs-5 text-danger"></i>' : '<i class="ti ti-info-circle fs-5 text-info"></i>');
            feedbackBox.classList.remove('d-none');
            if (type === 'success') {
                setTimeout(() => { feedbackBox.classList.add('d-none'); }, 6000);
            }
        }

        // State storage for combinations: Map key "arch|size" => object
        const variantsMap = new Map();

        // Active filters state
        let currentArchFilter = 'all';
        let currentSearchQuery = '';

        // Helper: Abbreviation for Arch to build neat SKUs
        function getArchAbbreviation(arch) {
            const map = {
                'Lower Left': 'LL',
                'Lower Right': 'LR',
                'Upper Left': 'UL',
                'Upper Right': 'UR'
            };
            return map[arch] || arch.replace(/\s+/g, '-').toUpperCase();
        }

        // Generate SKU helper
        function generateSku(arch, size) {
            let baseSku = bulkSkuPrefixInput?.value?.trim() || parentSkuInput?.value?.trim() || 'KED';
            baseSku = baseSku.replace(/[^a-zA-Z0-9\-_]/g, '').toUpperCase();
            const archCode = getArchAbbreviation(arch);
            return `${baseSku}-${archCode}-${size}`;
        }

        // Toggle variant section visibility based on product type
        function handleProductTypeChange() {
            if (!productTypeSelect) return;
            if (productTypeSelect.value === 'variable') {
                variantSection.classList.remove('d-none');
            } else {
                variantSection.classList.add('d-none');
            }
        }

        if (productTypeSelect) {
            productTypeSelect.addEventListener('change', handleProductTypeChange);
            handleProductTypeChange();
        }

        // Snapshot current form input values into variantsMap before re-rendering or filtering
        function snapshotCurrentTableInputs() {
            const rows = variantsTableBody.querySelectorAll('tr[data-key]');
            rows.forEach(row => {
                const key = row.getAttribute('data-key');
                if (!key) return;

                const idInput = row.querySelector('.var-id-input');
                const skuInput = row.querySelector('.var-sku-input');
                const priceInput = row.querySelector('.var-price-input');
                const sellingPriceInput = row.querySelector('.var-selling-price-input');
                const qtyInput = row.querySelector('.var-qty-input');
                const statusInput = row.querySelector('.var-status-input');

                const existing = variantsMap.get(key) || {};
                variantsMap.set(key, {
                    ...existing,
                    id: idInput ? idInput.value : (existing.id || null),
                    sku: skuInput ? skuInput.value : (existing.sku || ''),
                    price: priceInput ? priceInput.value : (existing.price || ''),
                    selling_price: sellingPriceInput ? sellingPriceInput.value : (existing.selling_price || ''),
                    quantity: qtyInput ? qtyInput.value : (existing.quantity || ''),
                    status: statusInput ? statusInput.value : (existing.status !== undefined ? existing.status : 1)
                });
            });
        }

        // Arch order helper for clean grouped listing
        const archSortOrder = {
            'Lower Left': 1,
            'Lower Right': 2,
            'Upper Left': 3,
            'Upper Right': 4
        };

        // Update Arch badge counts in the filter toolbar
        function updateFilterCounts() {
            let total = 0;
            const counts = {
                'Lower Left': 0,
                'Lower Right': 0,
                'Upper Left': 0,
                'Upper Right': 0
            };

            variantsMap.forEach((val) => {
                total++;
                if (counts[val.arch] !== undefined) {
                    counts[val.arch]++;
                }
            });

            const countAll = document.getElementById('filterCountAll');
            const countLL = document.getElementById('filterCountLL');
            const countLR = document.getElementById('filterCountLR');
            const countUL = document.getElementById('filterCountUL');
            const countUR = document.getElementById('filterCountUR');

            if (countAll) countAll.textContent = total;
            if (countLL) countLL.textContent = counts['Lower Left'] || 0;
            if (countLR) countLR.textContent = counts['Lower Right'] || 0;
            if (countUL) countUL.textContent = counts['Upper Left'] || 0;
            if (countUR) countUR.textContent = counts['Upper Right'] || 0;

            // Simplified: Hide or dim filter buttons with 0 count when variants exist
            document.querySelectorAll('.arch-filter-btn').forEach(btn => {
                const arch = btn.getAttribute('data-arch');
                if (arch && arch !== 'all') {
                    const count = counts[arch] || 0;
                    if (total > 0 && count === 0 && currentArchFilter !== arch) {
                        btn.classList.add('d-none');
                    } else {
                        btn.classList.remove('d-none');
                    }
                }
            });
        }

        // Apply Arch and Text Filters to Table Rows
        function applyFilters() {
            const rows = variantsTableBody.querySelectorAll('tr.variant-row');
            let visibleCount = 0;
            const query = (currentSearchQuery || '').trim().toLowerCase();

            rows.forEach(row => {
                const arch = row.getAttribute('data-arch') || '';
                const size = row.getAttribute('data-size') || '';
                const skuInput = row.querySelector('.var-sku-input');
                const sku = (skuInput ? skuInput.value : (row.getAttribute('data-sku') || '')).toLowerCase();

                const matchArch = (currentArchFilter === 'all' || arch === currentArchFilter);
                const matchSearch = !query || 
                    arch.toLowerCase().includes(query) || 
                    size.toLowerCase().includes(query) || 
                    sku.includes(query) ||
                    `size ${size}`.includes(query);

                if (matchArch && matchSearch) {
                    row.classList.remove('d-none');
                    visibleCount++;
                } else {
                    row.classList.add('d-none');
                }
            });

            // Update badge text
            if (variantsMap.size === 0) {
                variantCountBadge.textContent = '0 Variants Generated';
            } else if (currentArchFilter !== 'all' || query) {
                variantCountBadge.textContent = `Showing ${visibleCount} of ${variantsMap.size} Variants`;
            } else {
                variantCountBadge.textContent = `${variantsMap.size} Variant${variantsMap.size === 1 ? '' : 's'} Generated`;
            }

            // Handle no matching rows alert
            const noResultsRow = document.getElementById('noSearchResultsRow');
            if (visibleCount === 0 && variantsMap.size > 0) {
                if (!noResultsRow) {
                    const tr = document.createElement('tr');
                    tr.id = 'noSearchResultsRow';
                    tr.innerHTML = `
                        <td colspan="9" class="text-center py-4 text-muted bg-white">
                            <i class="ti ti-search-off fs-2 d-block mb-1 text-secondary"></i>
                            <span class="fw-semibold">No variants match your current filter / search.</span>
                            <div class="mt-1">
                                <a href="javascript:void(0)" id="resetFiltersLink" class="text-primary small text-decoration-none">
                                    Reset Filters
                                </a>
                            </div>
                        </td>
                    `;
                    variantsTableBody.appendChild(tr);

                    const resetLink = document.getElementById('resetFiltersLink');
                    if (resetLink) {
                        resetLink.addEventListener('click', function() {
                            resetAllFilters();
                        });
                    }
                }
            } else if (noResultsRow) {
                noResultsRow.remove();
            }
        }

        // Reset both Search and Arch filters
        function resetAllFilters() {
            currentArchFilter = 'all';
            currentSearchQuery = '';

            if (variantSearchInput) variantSearchInput.value = '';

            document.querySelectorAll('.arch-filter-btn').forEach(btn => {
                if (btn.getAttribute('data-arch') === 'all') {
                    btn.classList.remove('btn-outline-secondary');
                    btn.classList.add('btn-primary');
                } else {
                    btn.classList.remove('btn-primary');
                    btn.classList.add('btn-outline-secondary');
                }
            });

            applyFilters();
        }

        // Re-render table rows from all items in variantsMap
        function renderVariantsTableFromMap() {
            variantsTableBody.innerHTML = '';

            if (variantsMap.size === 0) {
                noVariantsNotice.classList.remove('d-none');
                variantCountBadge.textContent = '0 Variants Generated';
                updateFilterCounts();
                return;
            }

            noVariantsNotice.classList.add('d-none');

            // Sort entries grouped by Arch and then by Size
            const sortedEntries = Array.from(variantsMap.entries()).sort((a, b) => {
                const [, valA] = a;
                const [, valB] = b;

                const orderA = archSortOrder[valA.arch] || 99;
                const orderB = archSortOrder[valB.arch] || 99;

                if (orderA !== orderB) {
                    return orderA - orderB;
                }

                const sizeNumA = parseInt(valA.size, 10);
                const sizeNumB = parseInt(valB.size, 10);
                if (!isNaN(sizeNumA) && !isNaN(sizeNumB)) {
                    return sizeNumA - sizeNumB;
                }
                return String(valA.size).localeCompare(String(valB.size));
            });

            let index = 0;
            sortedEntries.forEach(([key, data]) => {
                const arch = data.arch;
                const size = data.size;
                const idVal = data.id || '';
                const skuVal = (data.sku !== undefined && data.sku !== '') ? data.sku : generateSku(arch, size);
                const priceVal = (data.price !== undefined && data.price !== '') ? data.price : (parentPriceInput?.value || '');
                const sellingPriceVal = (data.selling_price !== undefined && data.selling_price !== '') ? data.selling_price : (parentSellingPriceInput?.value || '');
                const qtyVal = (data.quantity !== undefined && data.quantity !== '') ? data.quantity : (parentQtyInput?.value || '10');
                const statusVal = data.status !== undefined ? String(data.status) : '1';

                const tr = document.createElement('tr');
                tr.setAttribute('data-key', key);
                tr.setAttribute('data-arch', arch);
                tr.setAttribute('data-size', size);
                tr.setAttribute('data-sku', skuVal.toLowerCase());
                tr.className = 'text-center align-middle variant-row';
                tr.innerHTML = `
                    <td class="text-muted fw-semibold">${index + 1}</td>
                    <td class="text-start">
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 fw-semibold">${arch}</span>
                        <input type="hidden" name="variants[${index}][arch]" value="${arch}">
                        ${idVal ? `<input type="hidden" name="variants[${index}][id]" class="var-id-input" value="${idVal}">` : ''}
                    </td>
                    <td>
                        <span class="badge bg-light text-dark border px-2 py-1">Size ${size}</span>
                        <input type="hidden" name="variants[${index}][size]" value="${size}">
                    </td>
                    <td>
                        <input type="text" 
                               name="variants[${index}][sku]" 
                               class="form-control form-control-sm var-sku-input" 
                               value="${skuVal}" 
                               required 
                               placeholder="SKU">
                    </td>
                    <td>
                        <input type="number" 
                               step="0.01" 
                               name="variants[${index}][price]" 
                               class="form-control form-control-sm var-price-input" 
                               value="${priceVal}" 
                               placeholder="0.00">
                    </td>
                    <td>
                        <input type="number" 
                               step="0.01" 
                               name="variants[${index}][selling_price]" 
                               class="form-control form-control-sm var-selling-price-input" 
                               value="${sellingPriceVal}" 
                               placeholder="0.00">
                    </td>
                    <td>
                        <input type="number" 
                               name="variants[${index}][quantity]" 
                               class="form-control form-control-sm var-qty-input" 
                               value="${qtyVal}" 
                               min="0" 
                               placeholder="0">
                    </td>
                    <td>
                        <select name="variants[${index}][status]" class="form-select form-select-sm var-status-input">
                            <option value="1" ${statusVal === '1' ? 'selected' : ''}>Active</option>
                            <option value="0" ${statusVal === '0' ? 'selected' : ''}>Inactive</option>
                        </select>
                    </td>
                    <td>
                        <button type="button" class="btn btn-sm btn-outline-danger remove-single-variant-btn p-1" data-key="${key}" data-id="${idVal}" title="Remove this variant">
                            <i class="ti ti-trash"></i>
                        </button>
                    </td>
                `;

                variantsTableBody.appendChild(tr);
                index++;
            });

            updateFilterCounts();
            applyFilters();
        }

        // Add Variants button click - INSTANTLY ADDS AND SAVES
        if (addVariantsBtn) {
            addVariantsBtn.addEventListener('click', function(e) {
                if (e) e.preventDefault();
                const selectedArches = Array.from(document.querySelectorAll('.arch-checkbox:checked')).map(cb => cb.value);
                const selectedSizes = Array.from(document.querySelectorAll('.size-checkbox:checked')).map(cb => cb.value);

                if (selectedArches.length === 0) {
                    alert('Please select at least one Arch.');
                    return;
                }
                if (selectedSizes.length === 0) {
                    alert('Please select at least one Size.');
                    return;
                }

                snapshotCurrentTableInputs();

                const newlyAddedList = [];

                selectedArches.forEach(arch => {
                    selectedSizes.forEach(size => {
                        const key = `${arch}|${size}`;
                        if (!variantsMap.has(key)) {
                            const newVarObj = {
                                id: null,
                                arch: arch,
                                size: size,
                                sku: generateSku(arch, size),
                                price: parentPriceInput?.value || '',
                                selling_price: parentSellingPriceInput?.value || '',
                                quantity: parentQtyInput?.value || '10',
                                status: '1'
                            };
                            variantsMap.set(key, newVarObj);
                            newlyAddedList.push(newVarObj);
                        }
                    });
                });

                // Clear checkboxes after adding
                document.querySelectorAll('.arch-checkbox:checked').forEach(cb => cb.checked = false);
                document.querySelectorAll('.size-checkbox:checked').forEach(cb => cb.checked = false);

                renderVariantsTableFromMap();

                if (newlyAddedList.length > 0) {
                    showFeedback('success', `✓ ${newlyAddedList.length} variant(s) added to table. Configure prices & SKU, then click "${isEditMode ? 'Update Product' : 'Save Product'}" below to save.`);
                } else {
                    showFeedback('info', 'All selected combinations are already in the table.');
                }
            });
        }

        // Clear All Variants button click
        if (clearAllVariantsBtn) {
            clearAllVariantsBtn.addEventListener('click', function() {
                if (variantsMap.size > 0) {
                    if (confirm('Are you sure you want to remove all variants from the table?')) {
                        variantsMap.clear();
                        renderVariantsTableFromMap();
                    }
                }
            });
        }

        // Checkbox helpers
        if (selectAllArchesBtn) {
            selectAllArchesBtn.addEventListener('click', function() {
                document.querySelectorAll('.arch-checkbox').forEach(cb => cb.checked = true);
            });
        }
        if (clearAllArchesBtn) {
            clearAllArchesBtn.addEventListener('click', function() {
                document.querySelectorAll('.arch-checkbox').forEach(cb => cb.checked = false);
            });
        }
        if (selectAllSizesBtn) {
            selectAllSizesBtn.addEventListener('click', function() {
                document.querySelectorAll('.size-checkbox').forEach(cb => cb.checked = true);
            });
        }
        if (clearAllSizesBtn) {
            clearAllSizesBtn.addEventListener('click', function() {
                document.querySelectorAll('.size-checkbox').forEach(cb => cb.checked = false);
            });
        }

        // Arch Filter Buttons click handler
        document.querySelectorAll('.arch-filter-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.arch-filter-btn').forEach(b => {
                    b.classList.remove('btn-primary');
                    b.classList.add('btn-outline-secondary');
                });
                this.classList.remove('btn-outline-secondary');
                this.classList.add('btn-primary');

                currentArchFilter = this.getAttribute('data-arch') || 'all';
                applyFilters();
            });
        });

        // Live Search Input handler
        if (variantSearchInput) {
            variantSearchInput.addEventListener('input', function() {
                currentSearchQuery = this.value;
                applyFilters();
            });
        }

        // Clear Search Button
        if (clearVariantSearchBtn) {
            clearVariantSearchBtn.addEventListener('click', function() {
                if (variantSearchInput) {
                    variantSearchInput.value = '';
                    currentSearchQuery = '';
                    applyFilters();
                    variantSearchInput.focus();
                }
            });
        }

        // Apply Bulk Values
        if (applyBulkValuesBtn) {
            applyBulkValuesBtn.addEventListener('click', function() {
                const bulkPrefix = bulkSkuPrefixInput?.value?.trim();
                const bulkPrice = bulkPriceInput?.value;
                const bulkSelling = bulkSellingPriceInput?.value;
                const bulkStock = bulkStockInput?.value;
                const visibleOnly = bulkApplyVisibleOnly ? bulkApplyVisibleOnly.checked : false;

                const targetRows = visibleOnly
                    ? variantsTableBody.querySelectorAll('tr.variant-row:not(.d-none)')
                    : variantsTableBody.querySelectorAll('tr.variant-row');

                targetRows.forEach(row => {
                    const key = row.getAttribute('data-key');
                    if (!key) return;
                    const [arch, size] = key.split('|');

                    if (bulkPrefix) {
                        const skuInput = row.querySelector('.var-sku-input');
                        if (skuInput) skuInput.value = `${bulkPrefix}-${getArchAbbreviation(arch)}-${size}`.toUpperCase();
                    }
                    if (bulkPrice !== '' && bulkPrice !== undefined) {
                        const priceInput = row.querySelector('.var-price-input');
                        if (priceInput) priceInput.value = bulkPrice;
                    }
                    if (bulkSelling !== '' && bulkSelling !== undefined) {
                        const sellingInput = row.querySelector('.var-selling-price-input');
                        if (sellingInput) sellingInput.value = bulkSelling;
                    }
                    if (bulkStock !== '' && bulkStock !== undefined) {
                        const qtyInput = row.querySelector('.var-qty-input');
                        if (qtyInput) qtyInput.value = bulkStock;
                    }
                });

                snapshotCurrentTableInputs();
            });
        }

        // Remove single row button click delegation
        variantsTableBody.addEventListener('click', function(e) {
            const btn = e.target.closest('.remove-single-variant-btn');
            if (!btn) return;
            e.preventDefault();

            const tr = btn.closest('tr');
            const key = btn.getAttribute('data-key') || (tr ? tr.getAttribute('data-key') : null);
            const variantId = btn.getAttribute('data-id') || (key && variantsMap.get(key) ? variantsMap.get(key).id : null);

            if (!confirm('Are you sure you want to remove this variant?')) {
                return;
            }

            snapshotCurrentTableInputs();

            // Background delete if ID exists (non-blocking)
            if (isEditMode && variantId && ajaxVariantBaseUrl) {
                fetch(`${ajaxVariantBaseUrl}/${variantId}/delete`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    }
                }).catch(err => console.log('Background delete notice:', err));
            }

            if (key) {
                variantsMap.delete(key);
            }
            renderVariantsTableFromMap();
            showFeedback('info', `Variant removed from table. Remember to click "${isEditMode ? 'Update Product' : 'Save Product'}" below to save changes.`);
        });

        // Initialize from existing variants (for Edit Page)
        function initExistingVariants() {
            if (initialVariants && initialVariants.length > 0) {
                initialVariants.forEach(v => {
                    let arch = v.arch || '';
                    let size = String(v.size || '');

                    // Extract arch/size from variant_attributes if columns were empty
                    if ((!arch || !size) && v.variant_attributes && v.variant_attributes.length > 0) {
                        v.variant_attributes.forEach(attrItem => {
                            const attrName = (attrItem.attribute?.name || attrItem.attribute_name || '').toLowerCase();
                            const attrVal = attrItem.attribute_value?.value || attrItem.value || '';
                            if (attrName === 'arch' && !arch) {
                                arch = attrVal;
                            }
                            if (attrName === 'size' && !size) {
                                size = String(attrVal);
                            }
                        });
                    }

                    // Fallback parse arch/size from SKU if missing
                    if ((!arch || !size) && v.sku) {
                        const skuUpper = v.sku.toUpperCase();
                        if (!arch) {
                            if (skuUpper.includes('-LL-') || skuUpper.endsWith('-LL')) arch = 'Lower Left';
                            else if (skuUpper.includes('-LR-') || skuUpper.endsWith('-LR')) arch = 'Lower Right';
                            else if (skuUpper.includes('-UL-') || skuUpper.endsWith('-UL')) arch = 'Upper Left';
                            else if (skuUpper.includes('-UR-') || skuUpper.endsWith('-UR')) arch = 'Upper Right';
                        }
                        if (!size) {
                            const sizeMatch = skuUpper.match(/-(?:LL|LR|UL|UR)-([0-9]+)/);
                            if (sizeMatch) {
                                size = sizeMatch[1];
                            }
                        }
                    }

                    // Fallback default so no existing variant is ever lost
                    if (!arch) arch = 'Universal';
                    if (!size) size = '1';

                    const key = `${arch}|${size}`;
                    variantsMap.set(key, {
                        id: v.id,
                        arch: arch,
                        size: size,
                        sku: v.sku || '',
                        price: (v.price !== null && v.price !== undefined) ? v.price : '',
                        selling_price: (v.selling_price !== null && v.selling_price !== undefined) ? v.selling_price : '',
                        quantity: (v.quantity !== null && v.quantity !== undefined) ? v.quantity : 0,
                        status: v.status !== undefined ? (v.status ? 1 : 0) : 1
                    });
                });
            }

            renderVariantsTableFromMap();
        }

        // Initialize page
        initExistingVariants();
    });
</script>
