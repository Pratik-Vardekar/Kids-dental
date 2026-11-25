@extends('layouts.admin')

@section('content')
<div class="container-fluid my-2">

    <!-- Page Header -->
    <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between mb-4 gap-3">
        <div>
            <h4 class="mb-1 text-dark fw-bold">Edit FAQ Category</h4>
            <p class="text-muted mb-0">Update category details and sorting order.</p>
        </div>
        <div>
            <a href="{{ route('admin.help-categories.index') }}" class="btn btn-secondary d-inline-flex align-items-center gap-1 px-3 py-2 fw-semibold" style="border-radius: 6px;">
                <i class="ti ti-arrow-left"></i> Back to Categories
            </a>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4" role="alert" style="border-radius: 8px;">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card border-0 shadow-sm" style="border-radius: 10px;">
        <div class="card-header bg-white border-bottom py-3 px-4">
            <h5 class="card-title mb-0 fw-semibold text-dark">Edit Category: {{ $helpCategory->title }}</h5>
        </div>
        <div class="card-body p-4">
            <form action="{{ route('admin.help-categories.update', $helpCategory) }}" method="POST" class="row g-3">
                @csrf
                @method('PUT')

                <!-- Title -->
                <div class="col-12 col-md-6">
                    <label class="form-label text-dark fw-semibold">Category Title <span class="text-danger">*</span></label>
                    <input type="text" name="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title', $helpCategory->title) }}" required style="border-radius: 6px;">
                    @error('title')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Sort Order -->
                <div class="col-12 col-md-3">
                    <label class="form-label text-dark fw-semibold">Sort Order</label>
                    <input type="number" name="sort_order" min="0" class="form-control @error('sort_order') is-invalid @enderror" value="{{ old('sort_order', $helpCategory->sort_order) }}" style="border-radius: 6px;">
                    @error('sort_order')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Status -->
                <div class="col-12 col-md-3">
                    <label class="form-label text-dark fw-semibold">Status <span class="text-danger">*</span></label>
                    <select name="status" class="form-select" style="border-radius: 6px;">
                        <option value="1" {{ (string) old('status', $helpCategory->status ? '1' : '0') === '1' ? 'selected' : '' }}>Active</option>
                        <option value="0" {{ (string) old('status', $helpCategory->status ? '1' : '0') === '0' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>

                <!-- Action Buttons -->
                <div class="col-12 d-flex gap-2 pt-3 border-top">
                    <button type="submit" class="btn text-white px-4 py-2 fw-semibold" style="background-color: #0d9488; border-color: #0d9488; border-radius: 6px;">
                        <i class="ti ti-check me-1"></i> Update Category
                    </button>
                    <a href="{{ route('admin.help-categories.index') }}" class="btn btn-light border px-4 py-2 fw-semibold" style="border-radius: 6px;">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
