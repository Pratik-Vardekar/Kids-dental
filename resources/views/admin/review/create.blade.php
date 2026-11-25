@extends('layouts.admin')

@section('content')
<div class="container-fluid my-2">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
        <div>
            <h4 class="mb-1">Add Review</h4>
            <p class="text-muted mb-0">Create a product review from admin panel</p>
        </div>
        <a href="{{ route('admin.review.index') }}" class="btn btn-secondary">
            <i class="ti ti-arrow-left me-1"></i>
            Back to Reviews
        </a>
    </div>

    <div class="card">
        <div class="card-body">
            <form action="{{ url('admin/review') }}" method="POST">
                @csrf

                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="product_id" class="form-label">Product <span class="text-danger">*</span></label>
                        <select id="product_id" name="product_id" class="form-select @error('product_id') is-invalid @enderror" required>
                            <option value="">Select product</option>
                            @foreach($products as $product)
                                <option value="{{ $product->id }}" {{ old('product_id') == $product->id ? 'selected' : '' }}>
                                    {{ $product->name }} ({{ $product->sku ?? 'No SKU' }})
                                </option>
                            @endforeach
                        </select>
                        @error('product_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="user_id" class="form-label">Customer <span class="text-danger">*</span></label>
                        <select id="user_id" name="user_id" class="form-select @error('user_id') is-invalid @enderror" required>
                            <option value="">Select customer</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" {{ old('user_id') == $user->id ? 'selected' : '' }}>
                                    {{ $user->name }} ({{ $user->email }})
                                </option>
                            @endforeach
                        </select>
                        @error('user_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="rating" class="form-label">Rating <span class="text-danger">*</span></label>
                        <select id="rating" name="rating" class="form-select @error('rating') is-invalid @enderror" required>
                            <option value="">Select rating</option>
                            @for($i = 5; $i >= 1; $i--)
                                <option value="{{ $i }}" {{ old('rating') == $i ? 'selected' : '' }}>{{ $i }} Star</option>
                            @endfor
                        </select>
                        @error('rating')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                        <select id="status" name="status" class="form-select @error('status') is-invalid @enderror" required>
                            <option value="1" {{ old('status', '1') == '1' ? 'selected' : '' }}>Approved (Active)</option>
                            <option value="0" {{ old('status') == '0' ? 'selected' : '' }}>Pending (Inactive)</option>
                        </select>
                        @error('status')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12">
                        <label for="review" class="form-label">Review <span class="text-danger">*</span></label>
                        <textarea id="review" name="review" rows="5" class="form-control @error('review') is-invalid @enderror" placeholder="Enter review text..." required>{{ old('review') }}</textarea>
                        @error('review')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-plus me-1"></i>
                            Add Review
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
