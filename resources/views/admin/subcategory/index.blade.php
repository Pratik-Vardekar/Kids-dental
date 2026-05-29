@extends('layouts.admin')

@section('content')

<div class="container-fluid my-2">

    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="mb-1">Sub Categories</h4>
            <p class="text-muted mb-0">Manage all sub categories</p>
        </div>

        <a href="{{ route('subcategory.create') }}" class="btn btn-primary">
            <i class="ti ti-plus me-1"></i>
            Add Sub Category
        </a>
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
            <form method="GET" action="{{ route('subcategory.index') }}" class="mb-4">

                <div class="row g-3 align-items-end">

                    <!-- Search -->
                    <div class="col-md-3">
                        <label class="form-label small text-muted mb-1">
                            Search
                        </label>

                        <input type="text"
                            name="search"
                            class="form-control"
                            placeholder="Search sub category..."
                            value="{{ request('search') }}">
                    </div>

                    <!-- Category Filter - FIXED: changed name from 'category' to 'category_id' -->
                    <div class="col-md-3">
                        <label class="form-label small text-muted mb-1">
                            Category
                        </label>

                        <select name="category_id" class="form-select">

                            <option value="">All Categories</option>

                            @foreach($categories as $category)

                                <option value="{{ $category->id }}"
                                    {{ request('category_id') == $category->id ? 'selected' : '' }}>

                                    {{ $category->name }}

                                </option>

                            @endforeach

                        </select>
                    </div>

                    <!-- Status -->
                    <div class="col-md-2">
                        <label class="form-label small text-muted mb-1">
                            Status
                        </label>

                        <select name="status" class="form-select">

                            <option value="">All Status</option>

                            <option value="1"
                                {{ request('status') == '1' ? 'selected' : '' }}>

                                Active

                            </option>

                            <option value="0"
                                {{ request('status') == '0' ? 'selected' : '' }}>

                                Inactive

                            </option>

                        </select>
                    </div>

                    <!-- Date -->
                    <div class="col-md-2">
                        <label class="form-label small text-muted mb-1">
                            Created Date
                        </label>

                        <input type="date"
                            name="date"
                            class="form-control"
                            value="{{ request('date') }}">
                    </div>

                    <!-- Buttons - ADDED CSV EXPORT BUTTON -->
                    <div class="col-md-2">
                        <div class="d-flex gap-2">

                            <button type="submit"
                                class="btn btn-primary flex-grow-1">

                                <i class="ti ti-filter me-1"></i>
                                

                            </button>

                            <a href="{{ route('subcategory.index') }}"
                                class="btn btn-secondary">

                                <i class="ti ti-refresh"></i>

                            </a>

                            <!-- CSV Export Button -->
                            <a href="{{ route('subcategory.export', request()->all()) }}"
                                class="btn btn-success">

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
                            <th style="width: 80px">Image</th>
                            <th>Category</th>
                            <th>Name</th>
                            <th>Slug</th>
                            <th style="width: 100px">Status</th>
                            <th style="width: 110px">Created</th>
                            <th style="width: 120px" class="text-center">
                                Actions
                            </th>

                        </tr>

                    </thead>

                    <tbody>

                        @forelse($subcategories as $key => $subcategory)

                            <tr>

                                <td class="align-middle">
                                    {{ $subcategories->firstItem() + $key }}
                                </td>

                                <td class="align-middle">

                                    <div class="category-img-wrapper"
                                        style="width: 50px; height: 50px;">

                                        @if($subcategory->image)

                                            <img src="{{ asset('backend/assets/images/subcategory/' . $subcategory->image) }}"
                                                class="rounded border"
                                                alt="{{ $subcategory->name }}"
                                                style="width:100%;height:100%;object-fit:cover;">

                                        @else

                                            <img src="{{ asset('backend/assets/images/no-image.png') }}"
                                                class="rounded border"
                                                alt="No image"
                                                style="width:100%;height:100%;object-fit:cover;">

                                        @endif

                                    </div>

                                </td>

                                <td class="align-middle">
                                    {{ $subcategory->category->name ?? '-' }}
                                </td>

                                <td class="align-middle">
                                    <h5 class="mb-0 fs-6">
                                        {{ $subcategory->name }}
                                    </h5>
                                </td>

                                <td class="align-middle text-muted">
                                    {{ $subcategory->slug }}
                                </td>

                                <td class="align-middle">

                                    @if($subcategory->status == 1)

                                        <span class="badge bg-success-subtle text-success px-3 py-2 rounded-pill fs-xxs">
                                            <i class="ti ti-circle-check me-1"></i>
                                            Active
                                        </span>

                                    @else

                                        <span class="badge bg-danger-subtle text-danger px-3 py-2 rounded-pill fs-xxs">
                                            <i class="ti ti-circle-x me-1"></i>
                                            Inactive
                                        </span>

                                    @endif

                                </td>

                                <td class="align-middle text-muted">
                                    {{ $subcategory->created_at->format('d M, Y') }}
                                </td>

                                <td class="align-middle">

                                    <div class="d-flex justify-content-center gap-1">

                                        <!-- Status -->
                                        <form action="{{ route('subcategory.status', $subcategory->id) }}"
                                            method="POST">

                                            @csrf
                                            @method('PUT')

                                            <button type="submit"
                                                class="btn btn-light btn-icon btn-sm rounded-circle">

                                                @if($subcategory->status == 1)

                                                    <i class="ti ti-toggle-right fs-5 text-success"></i>

                                                @else

                                                    <i class="ti ti-toggle-left fs-5 text-danger"></i>

                                                @endif

                                            </button>

                                        </form>

                                        <!-- Edit -->
                                        <a href="{{ route('subcategory.edit', $subcategory->id) }}"
                                            class="btn btn-light btn-icon btn-sm rounded-circle">

                                            <i class="ti ti-edit fs-5"></i>

                                        </a>

                                        <!-- Delete -->
                                        <form action="{{ route('subcategory.destroy', $subcategory->id) }}"
                                            method="POST"
                                            onsubmit="return confirm('Delete this sub category?')">

                                            @csrf
                                            @method('DELETE')

                                            <button type="submit"
                                                class="btn btn-light btn-icon btn-sm rounded-circle">

                                                <i class="ti ti-trash fs-5 text-danger"></i>

                                            </button>

                                        </form>

                                    </div>

                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td colspan="8" class="text-center py-5">

                                    <div class="d-flex flex-column align-items-center">

                                        <i class="ti ti-category-empty fs-1 text-muted mb-2"></i>

                                        <p class="text-muted mb-0">
                                            No sub categories found
                                        </p>

                                        <a href="{{ route('subcategory.create') }}"
                                            class="btn btn-sm btn-primary mt-3">

                                            <i class="ti ti-plus me-1"></i>
                                            Add your first sub category

                                        </a>

                                    </div>

                                </td>

                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

            <!-- Responsive Pagination -->
            @if($subcategories->hasPages())
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mt-4 pt-2 border-top">
                    <!-- Showing Entries -->
                    <div class="text-muted small mb-3 mb-md-0">
                        Showing
                        <span class="fw-semibold">{{ $subcategories->firstItem() }}</span>
                        to
                        <span class="fw-semibold">{{ $subcategories->lastItem() }}</span>
                        of
                        <span class="fw-semibold">{{ $subcategories->total() }}</span>
                        entries
                    </div>

                    <!-- Custom Responsive Pagination Controls -->
                    <nav aria-label="Subcategory pagination">
                        <ul class="pagination pagination-sm flex-wrap justify-content-center mb-0 gap-1">
                            {{-- Previous Page Link --}}
                            @if ($subcategories->onFirstPage())
                                <li class="page-item disabled">
                                    <span class="page-link rounded-3"><i class="ti ti-chevron-left"></i></span>
                                </li>
                            @else
                                <li class="page-item">
                                    <a class="page-link rounded-3" href="{{ $subcategories->previousPageUrl() }}" rel="prev">
                                        <i class="ti ti-chevron-left"></i>
                                    </a>
                                </li>
                            @endif

                            {{-- Pagination Elements with responsive behavior --}}
                            @php
                                $currentPage = $subcategories->currentPage();
                                $lastPage = $subcategories->lastPage();
                                $start = max(1, $currentPage - 2);
                                $end = min($lastPage, $currentPage + 2);

                                if ($start <= 0) $start = 1;
                                if ($end > $lastPage) $end = $lastPage;
                            @endphp

                            {{-- First page link --}}
                            @if($start > 1)
                                <li class="page-item d-none d-sm-block">
                                    <a class="page-link rounded-3" href="{{ $subcategories->url(1) }}">1</a>
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
                                        <a class="page-link rounded-3" href="{{ $subcategories->url($i) }}">{{ $i }}</a>
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
                                    <a class="page-link rounded-3" href="{{ $subcategories->url($lastPage) }}">{{ $lastPage }}</a>
                                </li>
                            @endif

                            {{-- Next Page Link --}}
                            @if ($subcategories->hasMorePages())
                                <li class="page-item">
                                    <a class="page-link rounded-3" href="{{ $subcategories->nextPageUrl() }}" rel="next">
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

                <!-- Mobile simplified pagination (visible only on small screens) -->
                <div class="d-flex d-sm-none justify-content-center mt-3">
                    <div class="btn-group" role="group">
                        @if (!$subcategories->onFirstPage())
                            <a href="{{ $subcategories->previousPageUrl() }}" class="btn btn-outline-primary btn-sm">
                                <i class="ti ti-chevron-left"></i> Prev
                            </a>
                        @else
                            <button class="btn btn-outline-secondary btn-sm" disabled>
                                <i class="ti ti-chevron-left"></i> Prev
                            </button>
                        @endif

                        <span class="btn btn-light btn-sm border disabled">
                            Page {{ $subcategories->currentPage() }} of {{ $subcategories->lastPage() }}
                        </span>

                        @if ($subcategories->hasMorePages())
                            <a href="{{ $subcategories->nextPageUrl() }}" class="btn btn-outline-primary btn-sm">
                                Next <i class="ti ti-chevron-right"></i>
                            </a>
                        @else
                            <button class="btn btn-outline-secondary btn-sm" disabled>
                                Next <i class="ti ti-chevron-right"></i>
                            </button>
                        @endif
                    </div>
                </div>
            @elseif($subcategories->total() > 0)
                <!-- Simple info when only one page -->
                <div class="text-center text-muted small mt-3 pt-2 border-top">
                    Showing {{ $subcategories->total() }} entries
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

    .bg-danger-subtle {
        background-color: #fee2e2 !important;
    }

    .rounded-pill {
        border-radius: 50rem !important;
    }

    .fs-xxs {
        font-size: 0.7rem;
    }

    .category-img-wrapper {
        background-color: #f8f9fa;
        border-radius: 8px;
        overflow: hidden;
    }

    .table-centered th,
    .table-centered td {
        vertical-align: middle;
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

    /* Custom pagination styling */
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

</style>

@endpush