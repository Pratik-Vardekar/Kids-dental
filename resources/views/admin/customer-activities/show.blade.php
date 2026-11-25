@extends('layouts.admin')

@section('content')
<div class="container-fluid my-2">

    <!-- Page Header (Matches Image 2) -->
    <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between mb-4 gap-3">
        <div>
            <h4 class="mb-1 text-dark fw-bold">User Cart Details</h4>
            <p class="text-muted mb-0">View cart, wishlist and buy now items.</p>
        </div>
        <div>
            <a href="{{ route('customer.activities.index') }}" class="btn text-white d-inline-flex align-items-center gap-1 px-3 py-2 fw-semibold" style="background-color: #6366f1; border-color: #6366f1; border-radius: 6px;">
                <i class="ti ti-arrow-left"></i> Back
            </a>
        </div>
    </div>

    <!-- User Profile & Summary Card (Matches Image 2) -->
    <div class="card border-0 shadow-sm mb-4" style="border-radius: 10px;">
        <div class="card-body p-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-4">
                <!-- User Left Box -->
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold fs-2"
                         style="width: 68px; height: 68px; min-width: 68px; background-color: #1d4ed8;">
                        {{ strtoupper(substr($user->name ?: 'U', 0, 1)) }}
                    </div>
                    <div>
                        <h4 class="mb-1 text-dark fw-bold">{{ $user->name }}</h4>
                        <div class="text-muted fs-sm mb-1">
                            <i class="ti ti-mail me-1"></i> {{ $user->email }}
                        </div>
                        <div class="text-muted fs-sm">
                            <i class="ti ti-phone me-1"></i> {{ $user->phone ?: 'N/A' }}
                        </div>
                    </div>
                </div>

                <!-- Right 3 Stats Boxes (Matches Image 2) -->
                <div class="d-flex gap-3">
                    <!-- Cart -->
                    <div class="card border rounded text-center px-4 py-3 shadow-none mb-0" style="min-width: 110px;">
                        <h3 class="mb-0 fw-bold" style="color: #0284c7;">{{ $cartCount }}</h3>
                        <span class="text-muted fs-xs mt-1 d-block">Cart</span>
                    </div>

                    <!-- Wishlist -->
                    <div class="card border rounded text-center px-4 py-3 shadow-none mb-0" style="min-width: 110px;">
                        <h3 class="mb-0 fw-bold" style="color: #0284c7;">{{ $wishlistCount }}</h3>
                        <span class="text-muted fs-xs mt-1 d-block">Wishlist</span>
                    </div>

                    <!-- Buy Now -->
                    <div class="card border rounded text-center px-4 py-3 shadow-none mb-0" style="min-width: 110px;">
                        <h3 class="mb-0 fw-bold" style="color: #059669;">{{ $buyNowCount }}</h3>
                        <span class="text-muted fs-xs mt-1 d-block">Buy Now</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Section 1: Cart Items (Matches Image 2) -->
    <div class="card border-0 shadow-sm mb-4" style="border-radius: 10px;">
        <div class="card-header bg-white border-bottom py-3 px-4 d-flex align-items-center gap-2">
            <i class="ti ti-shopping-cart fs-5 text-dark"></i>
            <h5 class="card-title mb-0 fw-semibold text-dark">Cart Items</h5>
            <span class="badge bg-light text-dark border ms-1">{{ $cartCount }}</span>
        </div>
        <div class="card-body p-0">
            @if($cartItems->isEmpty())
                <div class="text-center py-5">
                    <i class="ti ti-shopping-cart fs-1 text-muted d-block mb-2" style="opacity: 0.35;"></i>
                    <p class="text-muted mb-0">No cart items.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover table-centered mb-0 align-middle">
                        <thead class="bg-light bg-opacity-50">
                            <tr class="text-muted fs-xs">
                                <th class="ps-4" style="width: 50px;">#</th>
                                <th style="min-width: 260px;">Product</th>
                                <th style="min-width: 180px;">Variant (Arch & Size)</th>
                                <th class="text-center" style="width: 90px;">Quantity</th>
                                <th class="text-end" style="width: 120px;">Price</th>
                                <th class="text-end pe-4" style="min-width: 160px;">Date & Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($cartItems as $index => $item)
                                @php
                                    $imgSrc = null;
                                    if ($item->variant && !empty($item->variant->image)) {
                                        $imgSrc = asset('backend/assets/images/product/' . $item->variant->image);
                                    } elseif ($item->product && !empty($item->product->thumbnail)) {
                                        $imgSrc = asset('backend/assets/images/product/' . $item->product->thumbnail);
                                    }

                                    $price = 0;
                                    if ($item->variant) {
                                        $price = (float) ($item->variant->selling_price > 0 ? $item->variant->selling_price : ($item->variant->price > 0 ? $item->variant->price : ($item->product->selling_price > 0 ? $item->product->selling_price : ($item->product->price ?? 0))));
                                    } elseif ($item->product) {
                                        $price = (float) ($item->product->selling_price > 0 ? $item->product->selling_price : ($item->product->price ?? 0));
                                    }
                                @endphp
                                <tr>
                                    <td class="ps-4 text-muted fs-sm">{{ $index + 1 }}</td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            @if($imgSrc)
                                                <img src="{{ $imgSrc }}" alt="{{ $item->product->name ?? 'Product' }}"
                                                     class="rounded border" style="width: 45px; height: 45px; object-fit: cover;">
                                            @else
                                                <div class="rounded border bg-light d-flex align-items-center justify-content-center text-muted fs-xxs" style="width: 45px; height: 45px;">
                                                    No img
                                                </div>
                                            @endif
                                            <div>
                                                @if($item->product)
                                                    <a href="{{ route('product.edit', $item->product->id) }}" target="_blank" class="text-dark fw-semibold text-decoration-none fs-sm d-block">
                                                        {{ $item->product->name }}
                                                    </a>
                                                    @if($item->product->sku)
                                                        <span class="fs-xxs text-muted">SKU: <code>{{ $item->product->sku }}</code></span>
                                                    @endif
                                                @else
                                                    <span class="text-danger fs-sm">Product Deleted</span>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        @if($item->variant)
                                            <div class="d-flex flex-wrap gap-1 mb-1">
                                                @if(!empty($item->variant->arch))
                                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle fs-xxs">
                                                        {{ $item->variant->arch }}
                                                    </span>
                                                @endif
                                                @if(!empty($item->variant->size))
                                                    <span class="badge bg-purple-subtle text-purple border border-purple-subtle fs-xxs" style="background-color: #f3e8ff; color: #7e22ce;">
                                                        Size {{ $item->variant->size }}
                                                    </span>
                                                @endif
                                            </div>
                                            @if($item->variant->sku)
                                                <span class="fs-xxs text-muted d-block">Var SKU: <code>{{ $item->variant->sku }}</code></span>
                                            @endif
                                        @else
                                            <span class="text-muted fs-xs">Standard Product</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-light text-dark border px-2 py-1 fs-sm fw-bold">
                                            {{ $item->quantity }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <div class="fw-bold text-dark fs-sm">₹{{ number_format($price * $item->quantity, 2) }}</div>
                                        @if($item->quantity > 1)
                                            <small class="text-muted fs-xxs">₹{{ number_format($price, 2) }}/unit</small>
                                        @endif
                                    </td>
                                    <td class="text-end pe-4">
                                        @if($item->updated_at)
                                            @php
                                                $itemTime = \Illuminate\Support\Carbon::parse($item->updated_at)->setTimezone('Asia/Kolkata');
                                            @endphp
                                            <div class="text-dark fs-xs fw-medium">{{ $itemTime->format('d M Y') }}</div>
                                            <small class="text-muted fs-xxs d-block">{{ $itemTime->format('h:i A') }} IST</small>
                                        @else
                                            <span class="text-muted fs-xs">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <!-- Section 2: Wishlist Items (Matches Image 2) -->
    <div class="card border-0 shadow-sm mb-4" style="border-radius: 10px;">
        <div class="card-header bg-white border-bottom py-3 px-4 d-flex align-items-center gap-2">
            <i class="ti ti-heart fs-5 text-dark"></i>
            <h5 class="card-title mb-0 fw-semibold text-dark">Wishlist Items</h5>
            <span class="badge bg-light text-dark border ms-1">{{ $wishlistCount }}</span>
        </div>
        <div class="card-body p-0">
            @if($wishlistItems->isEmpty())
                <div class="text-center py-5">
                    <i class="ti ti-heart fs-1 text-muted d-block mb-2" style="opacity: 0.35;"></i>
                    <p class="text-muted mb-0">No wishlist items.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover table-centered mb-0 align-middle">
                        <thead class="bg-light bg-opacity-50">
                            <tr class="text-muted fs-xs">
                                <th class="ps-4" style="width: 50px;">#</th>
                                <th style="min-width: 260px;">Product</th>
                                <th style="min-width: 180px;">Variant (Arch & Size)</th>
                                <th class="text-end" style="width: 120px;">Price</th>
                                <th class="text-end pe-4" style="min-width: 160px;">Date & Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($wishlistItems as $index => $item)
                                @php
                                    $imgSrc = null;
                                    if ($item->variant && !empty($item->variant->image)) {
                                        $imgSrc = asset('backend/assets/images/product/' . $item->variant->image);
                                    } elseif ($item->product && !empty($item->product->thumbnail)) {
                                        $imgSrc = asset('backend/assets/images/product/' . $item->product->thumbnail);
                                    }

                                    $price = 0;
                                    if ($item->variant) {
                                        $price = (float) ($item->variant->selling_price > 0 ? $item->variant->selling_price : ($item->variant->price > 0 ? $item->variant->price : ($item->product->selling_price > 0 ? $item->product->selling_price : ($item->product->price ?? 0))));
                                    } elseif ($item->product) {
                                        $price = (float) ($item->product->selling_price > 0 ? $item->product->selling_price : ($item->product->price ?? 0));
                                    }
                                @endphp
                                <tr>
                                    <td class="ps-4 text-muted fs-sm">{{ $index + 1 }}</td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            @if($imgSrc)
                                                <img src="{{ $imgSrc }}" alt="{{ $item->product->name ?? 'Product' }}"
                                                     class="rounded border" style="width: 45px; height: 45px; object-fit: cover;">
                                            @else
                                                <div class="rounded border bg-light d-flex align-items-center justify-content-center text-muted fs-xxs" style="width: 45px; height: 45px;">
                                                    No img
                                                </div>
                                            @endif
                                            <div>
                                                @if($item->product)
                                                    <a href="{{ route('product.edit', $item->product->id) }}" target="_blank" class="text-dark fw-semibold text-decoration-none fs-sm d-block">
                                                        {{ $item->product->name }}
                                                    </a>
                                                    @if($item->product->sku)
                                                        <span class="fs-xxs text-muted">SKU: <code>{{ $item->product->sku }}</code></span>
                                                    @endif
                                                @else
                                                    <span class="text-danger fs-sm">Product Deleted</span>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        @if($item->variant)
                                            <div class="d-flex flex-wrap gap-1 mb-1">
                                                @if(!empty($item->variant->arch))
                                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle fs-xxs">
                                                        {{ $item->variant->arch }}
                                                    </span>
                                                @endif
                                                @if(!empty($item->variant->size))
                                                    <span class="badge bg-purple-subtle text-purple border border-purple-subtle fs-xxs" style="background-color: #f3e8ff; color: #7e22ce;">
                                                        Size {{ $item->variant->size }}
                                                    </span>
                                                @endif
                                            </div>
                                            @if($item->variant->sku)
                                                <span class="fs-xxs text-muted d-block">Var SKU: <code>{{ $item->variant->sku }}</code></span>
                                            @endif
                                        @else
                                            <span class="text-muted fs-xs">Standard Product</span>
                                        @endif
                                    </td>
                                    <td class="text-end fw-bold text-dark fs-sm">
                                        ₹{{ number_format($price, 2) }}
                                    </td>
                                    <td class="text-end pe-4">
                                        @if($item->updated_at)
                                            @php
                                                $itemTime = \Illuminate\Support\Carbon::parse($item->updated_at)->setTimezone('Asia/Kolkata');
                                            @endphp
                                            <div class="text-dark fs-xs fw-medium">{{ $itemTime->format('d M Y') }}</div>
                                            <small class="text-muted fs-xxs d-block">{{ $itemTime->format('h:i A') }} IST</small>
                                        @else
                                            <span class="text-muted fs-xs">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <!-- Section 3: Buy Now Items (Matches Image 2) -->
    <div class="card border-0 shadow-sm mb-4" style="border-radius: 10px;">
        <div class="card-header bg-white border-bottom py-3 px-4 d-flex align-items-center gap-2">
            <i class="ti ti-bolt fs-5 text-dark"></i>
            <h5 class="card-title mb-0 fw-semibold text-dark">Buy Now Items</h5>
            <span class="badge bg-light text-dark border ms-1">{{ $buyNowCount }}</span>
        </div>
        <div class="card-body p-0">
            @if($buyNowItems->isEmpty())
                <div class="text-center py-5">
                    <i class="ti ti-bolt fs-1 text-muted d-block mb-2" style="opacity: 0.35;"></i>
                    <p class="text-muted mb-0">No buy now items.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover table-centered mb-0 align-middle">
                        <thead class="bg-light bg-opacity-50">
                            <tr class="text-muted fs-xs">
                                <th class="ps-4" style="width: 50px;">#</th>
                                <th style="min-width: 260px;">Product</th>
                                <th style="min-width: 180px;">Variant (Arch & Size)</th>
                                <th class="text-center" style="width: 90px;">Quantity</th>
                                <th class="text-end" style="width: 120px;">Price</th>
                                <th class="text-end pe-4" style="min-width: 160px;">Date & Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($buyNowItems as $index => $item)
                                @php
                                    $imgSrc = null;
                                    if ($item->variant && !empty($item->variant->image)) {
                                        $imgSrc = asset('backend/assets/images/product/' . $item->variant->image);
                                    } elseif ($item->product && !empty($item->product->thumbnail)) {
                                        $imgSrc = asset('backend/assets/images/product/' . $item->product->thumbnail);
                                    }

                                    $price = 0;
                                    if ($item->variant) {
                                        $price = (float) ($item->variant->selling_price > 0 ? $item->variant->selling_price : ($item->variant->price > 0 ? $item->variant->price : ($item->product->selling_price > 0 ? $item->product->selling_price : ($item->product->price ?? 0))));
                                    } elseif ($item->product) {
                                        $price = (float) ($item->product->selling_price > 0 ? $item->product->selling_price : ($item->product->price ?? 0));
                                    }
                                @endphp
                                <tr>
                                    <td class="ps-4 text-muted fs-sm">{{ $index + 1 }}</td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            @if($imgSrc)
                                                <img src="{{ $imgSrc }}" alt="{{ $item->product->name ?? 'Product' }}"
                                                     class="rounded border" style="width: 45px; height: 45px; object-fit: cover;">
                                            @else
                                                <div class="rounded border bg-light d-flex align-items-center justify-content-center text-muted fs-xxs" style="width: 45px; height: 45px;">
                                                    No img
                                                </div>
                                            @endif
                                            <div>
                                                @if($item->product)
                                                    <a href="{{ route('product.edit', $item->product->id) }}" target="_blank" class="text-dark fw-semibold text-decoration-none fs-sm d-block">
                                                        {{ $item->product->name }}
                                                    </a>
                                                    @if($item->product->sku)
                                                        <span class="fs-xxs text-muted">SKU: <code>{{ $item->product->sku }}</code></span>
                                                    @endif
                                                @else
                                                    <span class="text-danger fs-sm">Product Deleted</span>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        @if($item->variant)
                                            <div class="d-flex flex-wrap gap-1 mb-1">
                                                @if(!empty($item->variant->arch))
                                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle fs-xxs">
                                                        {{ $item->variant->arch }}
                                                    </span>
                                                @endif
                                                @if(!empty($item->variant->size))
                                                    <span class="badge bg-purple-subtle text-purple border border-purple-subtle fs-xxs" style="background-color: #f3e8ff; color: #7e22ce;">
                                                        Size {{ $item->variant->size }}
                                                    </span>
                                                @endif
                                            </div>
                                            @if($item->variant->sku)
                                                <span class="fs-xxs text-muted d-block">Var SKU: <code>{{ $item->variant->sku }}</code></span>
                                            @endif
                                        @else
                                            <span class="text-muted fs-xs">Standard Product</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-light text-dark border px-2 py-1 fs-sm fw-bold">
                                            {{ $item->quantity }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <div class="fw-bold text-dark fs-sm">₹{{ number_format($price * $item->quantity, 2) }}</div>
                                        @if($item->quantity > 1)
                                            <small class="text-muted fs-xxs">₹{{ number_format($price, 2) }}/unit</small>
                                        @endif
                                    </td>
                                    <td class="text-end pe-4">
                                        @if($item->updated_at)
                                            @php
                                                $itemTime = \Illuminate\Support\Carbon::parse($item->updated_at)->setTimezone('Asia/Kolkata');
                                            @endphp
                                            <div class="text-dark fs-xs fw-medium">{{ $itemTime->format('d M Y') }}</div>
                                            <small class="text-muted fs-xxs d-block">{{ $itemTime->format('h:i A') }} IST</small>
                                        @else
                                            <span class="text-muted fs-xs">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

</div>
@endsection
