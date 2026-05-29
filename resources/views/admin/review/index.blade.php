@extends('layouts.admin')

@section('content')

<div class="container-fluid my-2">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">Reviews</h4>
            <p class="text-muted mb-0">Manage product reviews & ratings</p>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card">
        <div class="card-body">

            <!-- FILTERS -->
            <form method="GET" action="{{ route('review.index') }}" class="mb-4">
                <div class="row g-3 align-items-end">
                    <!-- Search -->
                    <div class="col-md-3">
                        <label class="form-label small text-muted mb-1">
                            Search
                        </label>
                        <input type="text"
                            name="search"
                            class="form-control"
                            placeholder="Search by user or review..."
                            value="{{ request('search') }}">
                    </div>

                    <!-- Product Filter -->
                    <div class="col-md-3">
                        <label class="form-label small text-muted mb-1">
                            Product
                        </label>
                        <select name="product_id" class="form-select">
                            <option value="">All Products</option>
                            @foreach($products ?? [] as $product)
                                <option value="{{ $product->id }}"
                                    {{ request('product_id') == $product->id ? 'selected' : '' }}>
                                    {{ $product->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Rating Filter -->
                    <div class="col-md-2">
                        <label class="form-label small text-muted mb-1">
                            Rating
                        </label>
                        <select name="rating" class="form-select">
                            <option value="">All Ratings</option>
                            <option value="5" {{ request('rating') == '5' ? 'selected' : '' }}>⭐⭐⭐⭐⭐ (5)</option>
                            <option value="4" {{ request('rating') == '4' ? 'selected' : '' }}>⭐⭐⭐⭐ (4+)</option>
                            <option value="3" {{ request('rating') == '3' ? 'selected' : '' }}>⭐⭐⭐ (3+)</option>
                            <option value="2" {{ request('rating') == '2' ? 'selected' : '' }}>⭐⭐ (2+)</option>
                            <option value="1" {{ request('rating') == '1' ? 'selected' : '' }}>⭐ (1+)</option>
                        </select>
                    </div>

                    <!-- Status Filter -->
                    <div class="col-md-2">
                        <label class="form-label small text-muted mb-1">
                            Status
                        </label>
                        <select name="status" class="form-select">
                            <option value="">All Status</option>
                            <option value="1" {{ request('status') == '1' ? 'selected' : '' }}>
                                Approved
                            </option>
                            <option value="0" {{ request('status') == '0' ? 'selected' : '' }}>
                                Pending
                            </option>
                        </select>
                    </div>

                    <!-- Date Filter -->
                    <div class="col-md-2">
                        <label class="form-label small text-muted mb-1">
                            Created Date
                        </label>
                        <input type="date"
                            name="date"
                            class="form-control"
                            value="{{ request('date') }}">
                    </div>

                    <!-- Buttons -->
                    <div class="col-md-2">
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary flex-grow-1">
                                <i class="ti ti-filter me-1"></i>
                                Filter
                            </button>

                            <a href="{{ route('review.index') }}" class="btn btn-secondary">
                                <i class="ti ti-refresh"></i>
                            </a>

                            <!-- CSV Export Button -->
                            <a href="{{ route('review.export', request()->all()) }}" class="btn btn-success">
                                <i class="ti ti-file-export"></i>
                                CSV
                            </a>
                        </div>
                    </div>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-custom table-centered table-hover w-100 mb-0">
                    <thead class="bg-light align-middle bg-opacity-25 thead-sm">
                        <tr class="text-uppercase fs-xxs">
                            <th style="width: 50px">#</th>
                            <th style="width: 200px">User</th>
                            <th style="width: 250px">Product</th>
                            <th style="width: 100px">Rating</th>
                            <th>Review</th>
                            <th style="width: 100px">Image</th>
                            <th style="width: 100px">Status</th>
                            <th style="width: 110px">Date</th>
                            <th style="width: 80px" class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($reviews as $index => $review)
                        <tr>
                            <td class="align-middle">
                                @if(method_exists($reviews, 'firstItem'))
                                    {{ $reviews->firstItem() + $index }}
                                @else
                                    {{ $index + 1 }}
                                @endif
                            </td>
                            <td class="align-middle">
                                <div>
                                    <h6 class="mb-0 fw-semibold">
                                        {{ $review->user->name ?? '-' }}
                                    </h6>
                                    <small class="text-muted">
                                        {{ $review->user->email ?? '-' }}
                                    </small>
                                </div>
                            </td>
                            <td class="align-middle">
                                <div class="d-flex align-items-center">
                                    @php
                                        $gallery = $review->product->gallery ?? [];
                                    @endphp
                                    @if(!empty($gallery) && isset($gallery[0]))
                                        <img src="{{ asset('backend/assets/images/product/' . $gallery[0]) }}"
                                            class="rounded border me-2"
                                            style="width:40px; height:40px; object-fit:cover;">
                                    @endif
                                    <div>
                                        <h6 class="mb-0">
                                            {{ $review->product->name ?? '-' }}
                                        </h6>
                                    </div>
                                </div>
                            </td>
                            <td class="align-middle">
                                <div class="fs-14">
                                    @for($i = 1; $i <= 5; $i++)
                                        @if($i <= $review->rating)
                                            <i class="ti ti-star-filled text-warning"></i>
                                        @else
                                            <i class="ti ti-star text-muted"></i>
                                        @endif
                                    @endfor
                                </div>
                            </td>
                            <td class="align-middle" style="max-width:300px; white-space:normal; word-break:break-word;">
                                {{ Str::limit($review->review, 100) }}
                            </td>
                            <td class="align-middle">
                                @if($review->image)
                                    <img src="{{ asset('backend/assets/images/review/' . $review->image) }}"
                                        class="rounded border"
                                        style="width:50px; height:50px; object-fit:cover;">
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="align-middle">
                                @if($review->status == 1)
                                    <span class="badge bg-success-subtle text-success px-3 py-2 rounded-pill fs-xxs">
                                        <i class="ti ti-circle-check me-1"></i>
                                        Approved
                                    </span>
                                @else
                                    <span class="badge bg-warning-subtle text-warning px-3 py-2 rounded-pill fs-xxs">
                                        <i class="ti ti-clock me-1"></i>
                                        Pending
                                    </span>
                                @endif
                            </td>
                            <td class="align-middle text-muted">
                                {{ $review->created_at->format('d M, Y') }}
                            </td>
                            <td class="align-middle">
                                <div class="d-flex justify-content-center gap-1">
                                    <!-- STATUS TOGGLE with dropdown replaced by button -->
                                    <form action="{{ route('review.status', $review->id) }}" method="POST">
                                        @csrf
                                        <button type="submit"
                                            class="btn btn-light btn-icon btn-sm rounded-circle"
                                            title="{{ $review->status == 1 ? 'Set as Pending' : 'Approve Review' }}">
                                            @if($review->status == 1)
                                                <i class="ti ti-toggle-right fs-5 text-success"></i>
                                            @else
                                                <i class="ti ti-toggle-left fs-5 text-warning"></i>
                                            @endif
                                        </button>
                                    </form>

                                    <!-- DELETE -->
                                    <form action="{{ route('review.destroy', $review->id) }}"
                                        method="POST"
                                        onsubmit="return confirm('Delete this review? This action cannot be undone.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                            class="btn btn-light btn-icon btn-sm rounded-circle"
                                            title="Delete Review">
                                            <i class="ti ti-trash fs-5 text-danger"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center py-5">
                                <div class="d-flex flex-column align-items-center">
                                    <i class="ti ti-message-2 fs-1 text-muted mb-2"></i>
                                    <p class="text-muted mb-0">No reviews found</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Responsive Pagination - Only show if it's a paginator -->
            @if(method_exists($reviews, 'hasPages') && $reviews->hasPages())
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mt-4 pt-2 border-top">
                    <div class="text-muted small mb-3 mb-md-0">
                        Showing
                        <span class="fw-semibold">{{ $reviews->firstItem() }}</span>
                        to
                        <span class="fw-semibold">{{ $reviews->lastItem() }}</span>
                        of
                        <span class="fw-semibold">{{ $reviews->total() }}</span>
                        entries
                    </div>

                    <nav aria-label="Review pagination">
                        <ul class="pagination pagination-sm flex-wrap justify-content-center mb-0 gap-1">
                            {{-- Previous Page Link --}}
                            @if ($reviews->onFirstPage())
                                <li class="page-item disabled">
                                    <span class="page-link rounded-3"><i class="ti ti-chevron-left"></i></span>
                                </li>
                            @else
                                <li class="page-item">
                                    <a class="page-link rounded-3" href="{{ $reviews->previousPageUrl() }}" rel="prev">
                                        <i class="ti ti-chevron-left"></i>
                                    </a>
                                </li>
                            @endif

                            {{-- Pagination Elements --}}
                            @php
                                $currentPage = $reviews->currentPage();
                                $lastPage = $reviews->lastPage();
                                $start = max(1, $currentPage - 2);
                                $end = min($lastPage, $currentPage + 2);
                            @endphp

                            {{-- First page link --}}
                            @if($start > 1)
                                <li class="page-item d-none d-sm-block">
                                    <a class="page-link rounded-3" href="{{ $reviews->url(1) }}">1</a>
                                </li>
                                @if($start > 2)
                                    <li class="page-item disabled d-none d-sm-block">
                                        <span class="page-link rounded-3">...</span>
                                    </li>
                                @endif
                            @endif

                            {{-- Page numbers --}}
                            @for ($i = $start; $i <= $end; $i++)
                                @if ($i == $currentPage)
                                    <li class="page-item active" aria-current="page">
                                        <span class="page-link rounded-3">{{ $i }}</span>
                                    </li>
                                @else
                                    <li class="page-item d-none d-sm-block">
                                        <a class="page-link rounded-3" href="{{ $reviews->url($i) }}">{{ $i }}</a>
                                    </li>
                                @endif
                            @endfor

                            {{-- Last page link --}}
                            @if($end < $lastPage)
                                @if($end < $lastPage - 1)
                                    <li class="page-item disabled d-none d-sm-block">
                                        <span class="page-link rounded-3">...</span>
                                    </li>
                                @endif
                                <li class="page-item d-none d-sm-block">
                                    <a class="page-link rounded-3" href="{{ $reviews->url($lastPage) }}">{{ $lastPage }}</a>
                                </li>
                            @endif

                            {{-- Next Page Link --}}
                            @if ($reviews->hasMorePages())
                                <li class="page-item">
                                    <a class="page-link rounded-3" href="{{ $reviews->nextPageUrl() }}" rel="next">
                                        <i class="ti ti-chevron-right"></i>
                                    </a>
                                </li>
                            @else
                                <li class="page-item disabled">
                                    <span class="page-link rounded-3"><i class="ti ti-chevron-right"></i></span>
                                </li>
                            @endif
                        </ul>
                    </nav>
                </div>

                <!-- Mobile simplified pagination -->
                <div class="d-flex d-sm-none justify-content-center mt-3">
                    <div class="btn-group" role="group">
                        @if (!$reviews->onFirstPage())
                            <a href="{{ $reviews->previousPageUrl() }}" class="btn btn-outline-primary btn-sm">
                                <i class="ti ti-chevron-left"></i> Prev
                            </a>
                        @else
                            <button class="btn btn-outline-secondary btn-sm" disabled>
                                <i class="ti ti-chevron-left"></i> Prev
                            </button>
                        @endif

                        <span class="btn btn-light btn-sm border disabled">
                            Page {{ $reviews->currentPage() }} of {{ $reviews->lastPage() }}
                        </span>

                        @if ($reviews->hasMorePages())
                            <a href="{{ $reviews->nextPageUrl() }}" class="btn btn-outline-primary btn-sm">
                                Next <i class="ti ti-chevron-right"></i>
                            </a>
                        @else
                            <button class="btn btn-outline-secondary btn-sm" disabled>
                                Next <i class="ti ti-chevron-right"></i>
                            </button>
                        @endif
                    </div>
                </div>
            @elseif(method_exists($reviews, 'count') && $reviews->count() > 0)
                <!-- Simple info when only one page (no pagination needed) -->
                <div class="text-center text-muted small mt-3 pt-2 border-top">
                    Showing {{ $reviews->count() }} entries
                </div>
            @endif

        </div>
    </div>

</div>

@endsection

@push('styles')
<style>
    .bg-success-subtle {
        background-color: #d1fae5 !important;
    }
    .bg-warning-subtle {
        background-color: #fef3c7 !important;
    }
    .rounded-pill {
        border-radius: 50rem !important;
    }
    .fs-xxs {
        font-size: 0.7rem;
    }
    .fs-14 {
        font-size: 14px;
    }
    .btn-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        padding: 0;
    }
    .gap-1 {
        gap: 0.25rem;
    }
    .page-link {
        padding: 0.375rem 0.75rem;
        font-size: 0.875rem;
        color: #4f5d73;
        border: 1px solid #e2e8f0;
        transition: all 0.2s ease;
    }
    .page-link:hover {
        background-color: #f1f5f9;
        color: #1e293b;
        border-color: #cbd5e1;
    }
    .page-item.active .page-link {
        background-color: #0d6efd;
        border-color: #0d6efd;
        color: white;
    }
    .page-item.disabled .page-link {
        color: #94a3b8;
        background-color: #f8fafc;
        border-color: #e2e8f0;
    }
    .rounded-3 {
        border-radius: 0.5rem !important;
    }
    @media (max-width: 768px) {
        .table-responsive {
            font-size: 0.85rem;
        }
        .btn-icon {
            width: 28px;
            height: 28px;
        }
        .fs-5 {
            font-size: 1rem !important;
        }
    }
    .text-warning {
        color: #f59e0b !important;
    }
    .ti-star-filled, .ti-star {
        font-size: 14px;
    }
</style>
@endpush