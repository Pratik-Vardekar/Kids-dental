@extends('layouts.admin')

@push('styles')

<link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet"/>
<style>

body{
    background:#f5f7fb;
}

/* =========================
PAGE
========================= */

.page-header{
    margin-bottom:24px;
}

.page-title{
    font-size:28px;
    font-weight:700;
    margin-bottom:4px;
    color:#111827;
}

.page-subtitle{
    color:#6b7280;
    font-size:14px;
}

/* =========================
CARDS
========================= */

.coupon-card{
    background:#fff;
    border-radius:16px;
    border:1px solid #e5e7eb;
    box-shadow:0 6px 18px rgba(0,0,0,.04);
    height:100%;
    overflow:visible !important;
}

.coupon-card-header{
    padding:16px 18px;
    border-bottom:1px solid #eef2f7;
    background:#fafafa;
    border-radius:16px 16px 0 0;
}

.coupon-card-body{
    padding:18px;
    overflow:visible !important;
}

.section-title{
    font-size:15px;
    font-weight:700;
}

.include-title{
    color:#4f46e5;
}

.exclude-title{
    color:#ef4444;
}

/* =========================
FORM
========================= */

.form-group{
    display:flex;
    flex-direction:column;
}

.form-label{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:10px;
    margin-bottom:10px;
    font-size:14px;
    font-weight:600;
    color:#111827;
}

.form-label small{
    font-size:11px;
    font-weight:500;
    color:#9ca3af !important;
}

.form-control,
.form-select{
    border-radius:10px;
    border:1px solid #dbe2ea;
    min-height:48px;
    font-size:14px;
    padding:10px 14px;
    background:#fff;
}

.form-control::placeholder{
    color:#9ca3af;
}

.form-control:focus,
.form-select:focus{
    border-color:#4f46e5;
    box-shadow:0 0 0 3px rgba(79,70,229,.10);
}

/* =========================
TOM SELECT
========================= */

select[multiple]{
    visibility:hidden;
}

.ts-wrapper{
    width:100% !important;
    visibility:visible !important;
    position:relative;
}

.ts-control{
    border:1px solid #dbe2ea !important;
    border-radius:10px !important;
    min-height:48px !important;
    padding:8px 10px !important;
    background:#fff !important;
    box-shadow:none !important;
}

.ts-control.focus{
    border-color:#4f46e5 !important;
    box-shadow:0 0 0 3px rgba(79,70,229,.10) !important;
}

.ts-wrapper.multi .ts-control > div{
    background:#eef2ff !important;
    color:#4f46e5 !important;
    border-radius:6px !important;
    padding:4px 8px !important;
    font-size:13px !important;
    font-weight:600 !important;
}

.ts-dropdown{
    background:#fff !important;
    border:1px solid #e5e7eb !important;
    border-radius:10px !important;
    overflow:hidden !important;
    z-index:999999 !important;
    margin-top:6px !important;
    box-shadow:0 10px 30px rgba(0,0,0,.08) !important;
}

.ts-dropdown .option{
    padding:10px 14px !important;
}

.ts-dropdown .active{
    background:#eef2ff !important;
    color:#4f46e5 !important;
}

.ts-dropdown-content{
    background:#fff !important;
}

/* =========================
BUTTONS
========================= */

.btn-save{
    background:linear-gradient(135deg,#4f46e5,#6366f1);
    color:#fff;
    border:none;
    border-radius:10px;
    padding:12px 24px;
    font-weight:600;
}

.btn-save:hover{
    color:#fff;
}

.btn-cancel{
    border-radius:10px;
    padding:12px 24px;
    font-weight:600;
}

/* =========================
IMPORTANT FIX
========================= */

.content-page,
.container-fluid,
.card,
.card-body{
    overflow:visible !important;
}

/* =========================
MOBILE
========================= */

@media(max-width:768px){

    .page-title{
        font-size:22px;
    }

    .coupon-card-body{
        padding:14px;
    }

}

</style>

@endpush


@section('content')

<div class="container-fluid py-4">

    <div class="page-header">

        <h2 class="page-title">
            Edit Coupon
        </h2>

        <div class="page-subtitle">
            Update coupon details easily
        </div>

    </div>

    <form action="{{ route('coupon.update', $coupon->id) }}"
          method="POST">

        @csrf
        @method('PUT')

        <div class="row g-4">

            {{-- COUPON DETAILS --}}
            <div class="col-12">

                <div class="coupon-card">

                    <div class="coupon-card-header">

                        <div class="section-title">
                            Coupon Details
                        </div>

                    </div>

                    <div class="coupon-card-body">

                        <div class="row g-4">

                            <div class="col-lg-6">

                                <div class="form-group">

                                    <label class="form-label">
                                        <span>Coupon Code</span>
                                    </label>

                                    <input type="text"
                                           name="code"
                                           class="form-control"
                                           value="{{ $coupon->code }}"
                                           required>

                                </div>

                            </div>

                            <div class="col-lg-6">

                                <div class="form-group">

                                    <label class="form-label">
                                        <span>Coupon Type</span>
                                    </label>

                                    <select name="type"
                                            id="coupon_type"
                                            class="form-select">

                                        <option value="fixed"
                                            {{ old('type', $coupon->type) == 'fixed' ? 'selected' : '' }}>
                                            Fixed Amount
                                        </option>

                                        <option value="percent"
                                            {{ old('type', $coupon->type) == 'percent' ? 'selected' : '' }}>
                                            Percentage
                                        </option>

                                    </select>

                                </div>

                            </div>

                            <div class="col-lg-4" id="coupon_value_col">

                                <div class="form-group">

                                    <label class="form-label">
                                        <span>Coupon Value</span>
                                        <small id="coupon_value_hint" class="text-muted"></small>
                                    </label>

                                    <input type="number"
                                           name="value"
                                           id="coupon_value_input"
                                           class="form-control"
                                           step="any"
                                           value="{{ old('value', $coupon->value) }}">

                                </div>

                            </div>

                            <div class="col-lg-4" id="min_amount_col">

                                <div class="form-group">

                                    <label class="form-label">
                                        <span>Minimum Amount</span>
                                        <small class="text-muted">Optional</small>
                                    </label>

                                    <input type="number"
                                           name="minimum_amount"
                                           id="minimum_amount_input"
                                           class="form-control"
                                           step="any"
                                           value="{{ old('minimum_amount', $coupon->minimum_amount) }}">

                                </div>

                            </div>

                            <div class="col-lg-4" id="max_discount_col">

                                <div class="form-group">

                                    <label class="form-label">
                                        <span>Maximum Discount</span>
                                        <small class="text-muted">Optional</small>
                                    </label>

                                    <input type="number"
                                           name="maximum_discount"
                                           id="maximum_discount_input"
                                           class="form-control"
                                           step="any"
                                           value="{{ old('maximum_discount', $coupon->maximum_discount) }}">

                                </div>

                            </div>

                            <div class="col-lg-4">

                                <div class="form-group">

                                    <label class="form-label">
                                        <span>Start Date</span>
                                    </label>

                                    <input type="date"
                                           name="start_date"
                                           class="form-control"
                                           value="{{ \Carbon\Carbon::parse($coupon->start_date)->format('Y-m-d') }}">

                                </div>

                            </div>

                            <div class="col-lg-4">

                                <div class="form-group">

                                    <label class="form-label">
                                        <span>End Date</span>
                                    </label>

                                    <input type="date"
                                           name="end_date"
                                           class="form-control"
                                           value="{{ \Carbon\Carbon::parse($coupon->end_date)->format('Y-m-d') }}">

                                </div>

                            </div>

                            <div class="col-lg-4">

                                <div class="form-group">

                                    <label class="form-label">
                                        <span>Status</span>
                                    </label>

                                    <select name="status"
                                            class="form-select">

                                        <option value="1"
                                            {{ $coupon->status == 1 ? 'selected' : '' }}>
                                            Active
                                        </option>

                                        <option value="0"
                                            {{ $coupon->status == 0 ? 'selected' : '' }}>
                                            Inactive
                                        </option>

                                    </select>

                                </div>

                            </div>

                            <div class="col-lg-4">

                                <div class="form-group">

                                    <label class="form-label">
                                        <span>Usage Limit</span>
                                    </label>

                                    <input type="number"
                                           name="usage_limit"
                                           class="form-control"
                                           value="{{ $coupon->usage_limit }}">

                                </div>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

            {{-- INCLUDE CATEGORY --}}
            <div class="col-lg-6">

                <div class="coupon-card">

                    <div class="coupon-card-header">
                        <div class="section-title include-title">
                            Include Categories
                        </div>
                    </div>

                    <div class="coupon-card-body">

                        <select id="categories"
                                name="categories[]"
                                multiple>

                            @foreach($categories as $category)

                                <option value="{{ $category->id }}"
                                    {{ in_array($category->id, $coupon->category_ids ?? []) ? 'selected' : '' }}>

                                    {{ $category->name }}

                                </option>

                            @endforeach

                        </select>

                    </div>

                </div>

            </div>

            {{-- EXCLUDE CATEGORY --}}
            <div class="col-lg-6">

                <div class="coupon-card">

                    <div class="coupon-card-header">
                        <div class="section-title exclude-title">
                            Exclude Categories
                        </div>
                    </div>

                    <div class="coupon-card-body">

                        <select id="excluded_categories"
                                name="excluded_categories[]"
                                multiple>

                            @foreach($categories as $category)

                               <option value="{{ $category->id }}"
  {{ in_array($category->id, $coupon->exclude_category_ids ?? []) ? 'selected' : '' }}>
    {{ $category->name }}
</option>

                            @endforeach

                        </select>

                    </div>

                </div>

            </div>

            {{-- INCLUDE PRODUCTS --}}
            <div class="col-lg-6">

                <div class="coupon-card">

                    <div class="coupon-card-header">
                        <div class="section-title include-title">
                            Include Products
                        </div>
                    </div>

                    <div class="coupon-card-body">

                        <select id="products"
                                name="products[]"
                                multiple>

                            @foreach($products as $product)

                                <option value="{{ $product->id }}"
                                    {{ in_array($product->id, $coupon->product_ids ?? []) ? 'selected' : '' }}>

                                    {{ $product->name }}

                                </option>

                            @endforeach

                        </select>

                    </div>

                </div>

            </div>

            {{-- EXCLUDE PRODUCTS --}}
            <div class="col-lg-6">

                <div class="coupon-card">

                    <div class="coupon-card-header">
                        <div class="section-title exclude-title">
                            Exclude Products
                        </div>
                    </div>

                    <div class="coupon-card-body">

                        <select id="excluded_products"
                                name="excluded_products[]"
                                multiple>

                            @foreach($products as $product)

                                <option value="{{ $product->id }}"
                                    {{ in_array($product->id, $coupon->exclude_product_ids ?? []) ? 'selected' : '' }}>

                                    {{ $product->name }}

                                </option>

                            @endforeach

                        </select>

                    </div>

                </div>

            </div>

            {{-- BUTTONS --}}
            <div class="col-12 d-flex gap-3">

                <button type="submit"
                        class="btn btn-save">

                    Update Coupon

                </button>

                <a href="{{ route('coupon.index') }}"
                   class="btn btn-light btn-cancel">

                    Cancel

                </a>

            </div>

        </div>

    </form>

</div>

@endsection


@push('scripts')

<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const settings = {
        plugins: ['remove_button'],
        create: false,
        maxItems: null,
        hidePlaceholder: true,
        dropdownParent: 'body'
    };

    const includeCategories = new TomSelect('#categories', settings);
    const excludeCategories = new TomSelect('#excluded_categories', settings);

    const includeProducts = new TomSelect('#products', settings);
    const excludeProducts = new TomSelect('#excluded_products', settings);

    function syncCategoryDropdowns() {

        const included = includeCategories.getValue();
        const excluded = excludeCategories.getValue();

        includeCategories.clearOptions();
        excludeCategories.clearOptions();

        @foreach($categories as $category)

        if (!excluded.includes('{{ $category->id }}')) {
            includeCategories.addOption({
                value: '{{ $category->id }}',
                text: '{{ $category->name }}'
            });
        }

        if (!included.includes('{{ $category->id }}')) {
            excludeCategories.addOption({
                value: '{{ $category->id }}',
                text: '{{ $category->name }}'
            });
        }

        @endforeach

        includeCategories.refreshOptions(false);
        excludeCategories.refreshOptions(false);
    }

    function syncProductDropdowns() {

        const included = includeProducts.getValue();
        const excluded = excludeProducts.getValue();

        includeProducts.clearOptions();
        excludeProducts.clearOptions();

        @foreach($products as $product)

        if (!excluded.includes('{{ $product->id }}')) {
            includeProducts.addOption({
                value: '{{ $product->id }}',
                text: '{{ $product->name }}'
            });
        }

        if (!included.includes('{{ $product->id }}')) {
            excludeProducts.addOption({
                value: '{{ $product->id }}',
                text: '{{ $product->name }}'
            });
        }

        @endforeach

        includeProducts.refreshOptions(false);
        excludeProducts.refreshOptions(false);
    }

    includeCategories.on('change', syncCategoryDropdowns);
    excludeCategories.on('change', syncCategoryDropdowns);

    includeProducts.on('change', syncProductDropdowns);
    excludeProducts.on('change', syncProductDropdowns);

    syncCategoryDropdowns();
    syncProductDropdowns();

    // ==========================================
    // COUPON TYPE DYNAMIC TOGGLE (Fixed vs Percent)
    // ==========================================
    const couponTypeSelect = document.getElementById('coupon_type');
    const maxDiscountCol = document.getElementById('max_discount_col');
    const maxDiscountInput = document.getElementById('maximum_discount_input');
    const minAmountCol = document.getElementById('min_amount_col');
    const couponValueCol = document.getElementById('coupon_value_col');
    const couponValueHint = document.getElementById('coupon_value_hint');
    const couponValueInput = document.getElementById('coupon_value_input');

    function toggleDiscountFields() {
        if (!couponTypeSelect) return;

        const isFixed = couponTypeSelect.value === 'fixed';

        if (isFixed) {
            // FIXED AMOUNT: Hide Maximum Discount, Show Minimum Amount
            if (maxDiscountCol) {
                maxDiscountCol.style.display = 'none';
            }
            if (maxDiscountInput) {
                maxDiscountInput.disabled = true;
            }

            if (minAmountCol) {
                minAmountCol.style.display = 'block';
                minAmountCol.className = 'col-lg-6 col-md-6 col-12';
            }

            if (couponValueCol) {
                couponValueCol.className = 'col-lg-6 col-md-6 col-12';
            }

            if (couponValueHint) {
                couponValueHint.textContent = 'Discount in ₹';
            }
            if (couponValueInput && !couponValueInput.value) {
                couponValueInput.placeholder = 'e.g. 100';
            }
        } else {
            // PERCENTAGE: Show BOTH Minimum Amount and Maximum Discount
            if (maxDiscountCol) {
                maxDiscountCol.style.display = 'block';
                maxDiscountCol.className = 'col-lg-4 col-md-4 col-12';
            }
            if (maxDiscountInput) {
                maxDiscountInput.disabled = false;
            }

            if (minAmountCol) {
                minAmountCol.style.display = 'block';
                minAmountCol.className = 'col-lg-4 col-md-4 col-12';
            }

            if (couponValueCol) {
                couponValueCol.className = 'col-lg-4 col-md-4 col-12';
            }

            if (couponValueHint) {
                couponValueHint.textContent = 'Discount in % (e.g. 10)';
            }
            if (couponValueInput && !couponValueInput.value) {
                couponValueInput.placeholder = 'e.g. 10';
            }
        }
    }

    if (couponTypeSelect) {
        couponTypeSelect.addEventListener('change', toggleDiscountFields);
        toggleDiscountFields();
    }

});

</script>

@endpush