@extends('layouts.admin')

@section('content')
<div class="container-fluid my-2">

    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 text-dark fw-bold">Edit Category FAQ</h4>
            <p class="text-muted mb-0">Update FAQ question and answer details</p>
        </div>
        <a href="{{ route('admin.help-faqs.index', ['sub_category_id' => $helpFaq->sub_category_id]) }}" class="btn btn-secondary">
            <i class="ti ti-arrow-left me-1"></i> Back
        </a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card">
        <div class="card-body p-4">
            <form action="{{ route('admin.help-faqs.update', $helpFaq) }}" method="POST" class="row g-3">
                @csrf
                @method('PUT')

                <!-- SubCategory -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold">SubCategory <span class="text-danger">*</span></label>
                    <select name="sub_category_id" class="form-select @error('sub_category_id') is-invalid @enderror" required>
                        <option value="">Select SubCategory</option>
                        @foreach($subcategories as $sub)
                            <option value="{{ $sub->id }}" {{ (string) old('sub_category_id', $helpFaq->sub_category_id) === (string) $sub->id ? 'selected' : '' }}>
                                {{ $sub->category ? $sub->category->name . ' > ' : '' }}{{ $sub->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('sub_category_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Sort Order -->
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Sort Order</label>
                    <input type="number" name="sort_order" min="0" class="form-control @error('sort_order') is-invalid @enderror" value="{{ old('sort_order', $helpFaq->sort_order) }}">
                    @error('sort_order')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Status -->
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                    <select name="status" class="form-select">
                        <option value="1" {{ (string) old('status', $helpFaq->status ? '1' : '0') === '1' ? 'selected' : '' }}>Active</option>
                        <option value="0" {{ (string) old('status', $helpFaq->status ? '1' : '0') === '0' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>

                <!-- Question -->
                <div class="col-12">
                    <label class="form-label fw-semibold">Question <span class="text-danger">*</span></label>
                    <input type="text" name="question" class="form-control @error('question') is-invalid @enderror" value="{{ old('question', $helpFaq->question) }}" required>
                    @error('question')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Answer -->
                <div class="col-12">
                    <label class="form-label fw-semibold">Answer <span class="text-danger">*</span></label>
                    <textarea name="answer" rows="6" class="form-control @error('answer') is-invalid @enderror" required>{{ old('answer', $helpFaq->answer) }}</textarea>
                    @error('answer')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Buttons -->
                <div class="col-12 pt-3">
                    <button type="submit" class="btn btn-warning fw-semibold px-4">
                        <i class="ti ti-check me-1"></i> Update FAQ
                    </button>
                    <a href="{{ route('admin.help-faqs.index', ['sub_category_id' => $helpFaq->sub_category_id]) }}" class="btn btn-secondary ms-2">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
