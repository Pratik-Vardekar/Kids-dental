@extends('layouts.admin')

@section('content')
<div class="container-fluid my-2">

    <!-- Page Header -->
    <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between mb-4 gap-3">
        <div>
            <h4 class="mb-1 text-dark fw-bold">FAQ Categories</h4>
            <p class="text-muted mb-0">Manage product categories for Category FAQs (e.g. Bioflx Crowns, Kids-e-Crown®, etc.).</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.help-faqs.index') }}" class="btn btn-outline-secondary d-inline-flex align-items-center gap-1 px-3 py-2 fw-semibold" style="border-radius: 6px;">
                <i class="ti ti-message-question fs-5"></i> View All FAQs
            </a>
            <a href="{{ route('admin.help-categories.create') }}" class="btn text-white d-inline-flex align-items-center gap-1 px-3 py-2 fw-semibold" style="background-color: #0d9488; border-color: #0d9488; border-radius: 6px;">
                <i class="ti ti-plus fs-5"></i> Add Category
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert" style="border-radius: 8px;">
            <i class="ti ti-check me-2 fs-5"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card border-0 shadow-sm" style="border-radius: 10px;">
        <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-2">
                <i class="ti ti-category fs-5 text-dark"></i>
                <h5 class="card-title mb-0 fw-semibold text-dark">All Categories</h5>
                <span class="badge bg-light text-dark border ms-1">{{ $categories->count() }}</span>
            </div>
            <div>
                <a href="{{ route('admin.help-categories.create') }}" class="btn btn-sm text-white fw-semibold" style="background-color: #0d9488; border-radius: 6px;">
                    <i class="ti ti-plus"></i> Add Category
                </a>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-centered mb-0 align-middle">
                    <thead class="bg-light bg-opacity-50">
                        <tr class="text-muted fs-xs">
                            <th class="ps-4 py-3" style="width: 60px;">#</th>
                            <th class="py-3" style="min-width: 260px;">Category Title</th>
                            <th class="text-center py-3" style="width: 140px;">FAQs Count</th>
                            <th class="text-center py-3" style="width: 100px;">Sort Order</th>
                            <th class="text-center py-3" style="width: 110px;">Status</th>
                            <th class="text-center pe-4 py-3" style="width: 180px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($categories as $index => $category)
                            <tr>
                                <td class="ps-4 align-middle text-muted fs-sm">
                                    {{ $index + 1 }}
                                </td>
                                <td class="align-middle">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="rounded-circle" style="width: 10px; height: 10px; background-color: #0d9488; display: inline-block;"></span>
                                        <a href="{{ route('admin.help-faqs.index', ['category_id' => $category->id]) }}" class="text-dark fw-bold text-decoration-none fs-sm">
                                            {{ $category->title }}
                                        </a>
                                    </div>
                                </td>
                                <td class="align-middle text-center">
                                    <a href="{{ route('admin.help-faqs.index', ['category_id' => $category->id]) }}" class="text-decoration-none">
                                        <span class="badge px-3 py-1 fs-xs fw-semibold" style="background-color: #e0f2fe; color: #0284c7; border-radius: 12px;">
                                            <i class="ti ti-help me-1"></i> {{ $category->faqs_count }} FAQs
                                        </span>
                                    </a>
                                </td>
                                <td class="align-middle text-center">
                                    <span class="badge bg-light text-dark border">{{ $category->sort_order }}</span>
                                </td>
                                <td class="align-middle text-center">
                                    @if($category->status)
                                        <span class="badge text-white px-2 py-1 fs-xs fw-semibold" style="background-color: #059669; border-radius: 4px;">Active</span>
                                    @else
                                        <span class="badge bg-danger text-white px-2 py-1 fs-xs fw-semibold" style="border-radius: 4px;">Inactive</span>
                                    @endif
                                </td>
                                <td class="pe-4 align-middle text-center">
                                    <div class="d-inline-flex gap-1">
                                        <a href="{{ route('admin.help-faqs.create', ['category_id' => $category->id]) }}"
                                           class="btn btn-sm btn-outline-success d-inline-flex align-items-center justify-content-center p-2 rounded"
                                           style="width: 32px; height: 32px;" title="Add FAQ to this Category">
                                            <i class="ti ti-plus fs-5"></i>
                                        </a>
                                        <a href="{{ route('admin.help-categories.edit', $category) }}"
                                           class="btn btn-sm btn-outline-primary d-inline-flex align-items-center justify-content-center p-2 rounded"
                                           style="width: 32px; height: 32px;" title="Edit Category">
                                            <i class="ti ti-edit fs-5"></i>
                                        </a>
                                        <form action="{{ route('admin.help-categories.destroy', $category) }}" method="POST" class="d-inline"
                                              onsubmit="return confirm('Deleting this category will also delete all FAQs within it. Continue?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger d-inline-flex align-items-center justify-content-center p-2 rounded"
                                                    style="width: 32px; height: 32px;" title="Delete Category">
                                                <i class="ti ti-trash fs-5"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5">
                                    <div class="d-flex flex-column align-items-center py-4">
                                        <div class="avatar-lg rounded-circle bg-light d-flex align-items-center justify-content-center text-muted mb-3" style="width: 60px; height: 60px;">
                                            <i class="ti ti-category fs-2"></i>
                                        </div>
                                        <h5 class="text-dark fw-semibold mb-1">No FAQ categories found</h5>
                                        <p class="text-muted fs-xs mb-3">Get started by creating your first FAQ category.</p>
                                        <a href="{{ route('admin.help-categories.create') }}" class="btn btn-sm text-white fw-semibold" style="background-color: #0d9488;">
                                            <i class="ti ti-plus me-1"></i> Add Category
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
@endsection
