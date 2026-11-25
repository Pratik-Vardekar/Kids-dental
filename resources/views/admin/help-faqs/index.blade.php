@extends('layouts.admin')

@section('content')
    <div class="container-fluid my-2">

        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="mb-1 text-dark fw-bold">Category FAQs</h4>
                <p class="text-muted mb-0">Manage questions and answers by subcategory</p>
            </div>

            <a href="{{ route('admin.help-faqs.create', ['sub_category_id' => $subcategoryId]) }}" class="btn fw-semibold"
                style="background-color: #ffcc00;">
                <i class="ti ti-plus me-1"></i> Add FAQ
            </a>
        </div>

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if (session('info'))
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                {{ session('info') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <!-- Simple Filter Bar -->
        <div class="card mb-4">
            <div class="card-body p-3">
                <form action="{{ route('admin.help-faqs.index') }}" method="GET" class="row g-2 align-items-center">
                    <div class="col-md-5">
                        <input type="text" name="search" class="form-control" placeholder="Search question or answer..."
                            value="{{ request('search') }}">
                    </div>

                    <div class="col-md-4">
                        <select name="sub_category_id" class="form-select">
                            <option value="">All SubCategories</option>
                            @foreach ($subcategories as $sub)
                                <option value="{{ $sub->id }}"
                                    {{ (string) request('sub_category_id', $subcategoryId) === (string) $sub->id ? 'selected' : '' }}>
                                    {{ $sub->category ? $sub->category->name . ' > ' : '' }}{{ $sub->name }}
                                    ({{ $subcategoryCounts[$sub->id] ?? 0 }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3 d-flex gap-2">
                        <button type="submit" class="btn btn-warning fw-semibold w-100"style="background-color: #ffcc00;">
                            <i class="ti ti-search me-1"></i> Filter
                        </button>
                        @if (request()->hasAny(['search', 'sub_category_id', 'category_id']))
                            <a href="{{ route('admin.help-faqs.index') }}" class="btn btn-light border px-3"
                                title="Reset Filter">
                                <i class="ti ti-refresh"></i>
                            </a>
                        @endif
                    </div>
                </form>
            </div>
        </div>

        <!-- Simple Table Card -->
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="60" class="ps-3">#</th>
                                <th style="min-width: 180px;">SubCategory</th>
                                <th style="min-width: 260px;">Question</th>
                                <th style="min-width: 320px;">Answer</th>
                                <th width="80" class="text-center">Order</th>
                                <th width="100" class="text-center">Status</th>
                                <th width="140" class="text-center pe-3">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($faqs as $index => $faq)
                                @php
                                    $subName = $faq->subCategory?->name ?: $faq->category_title;
                                    $catName = $faq->subCategory?->category?->name;
                                @endphp
                                <tr>
                                    <td class="ps-3 text-muted">{{ $faqs->firstItem() + $index }}</td>
                                    <td>
                                        <strong>{{ $subName }}</strong>
                                        @if ($catName)
                                            <small class="text-muted d-block fs-xs">{{ $catName }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="fw-semibold text-dark">{{ $faq->question }}</span>
                                    </td>
                                    <td class="text-muted">
                                        {{ \Illuminate\Support\Str::limit(strip_tags($faq->answer), 120) }}
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-light text-dark border">{{ $faq->sort_order }}</span>
                                    </td>
                                    <td class="text-center">
                                        @if ($faq->status)
                                            <span class="badge bg-success">Active</span>
                                        @else
                                            <span class="badge bg-danger">Inactive</span>
                                        @endif
                                    </td>
                                    <td class="text-center pe-3">
                                        <a href="{{ route('admin.help-faqs.edit', $faq) }}"
                                            class="btn btn-warning btn-sm me-1"style="background-color: #ffcc00;"
                                            title="Edit">
                                            <i class="ti ti-edit"></i> Edit
                                        </a>

                                        <form action="{{ route('admin.help-faqs.destroy', $faq) }}" method="POST"
                                            class="d-inline" onsubmit="return confirm('Delete this FAQ?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm" title="Delete">
                                                <i class="ti ti-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">
                                        <p class="mb-2">No FAQs found.</p>
                                        <a href="{{ route('admin.help-faqs.create', ['sub_category_id' => $subcategoryId]) }}"
                                            class="btn btn-warning btn-sm fw-semibold">
                                            <i class="ti ti-plus me-1"></i> Add FAQ
                                        </a>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($faqs->hasPages())
                    <div class="d-flex justify-content-between align-items-center p-3 border-top">
                        <div class="text-muted small">
                            Showing {{ $faqs->firstItem() }} to {{ $faqs->lastItem() }} of {{ $faqs->total() }} FAQs
                        </div>
                        <div>
                            {{ $faqs->onEachSide(1)->links('pagination::bootstrap-5') }}
                        </div>
                    </div>
                @endif
            </div>
        </div>

    </div>
@endsection
