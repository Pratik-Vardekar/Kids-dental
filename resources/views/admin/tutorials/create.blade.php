@extends('layouts.admin')

@section('content')
<div class="container-fluid my-2">

    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 text-dark fw-bold">Add Tutorial Video</h4>
            <p class="text-muted mb-0">Add a tutorial video and description for a subcategory</p>
        </div>
        <a href="{{ route('admin.tutorials.index', ['sub_category_id' => $selectedSubcategoryId]) }}" class="btn btn-secondary">
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
            <form action="{{ route('admin.tutorials.store') }}" method="POST" enctype="multipart/form-data" class="row g-3">
                @csrf

                <!-- SubCategory -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold">SubCategory <span class="text-danger">*</span></label>
                    <select name="sub_category_id" class="form-select @error('sub_category_id') is-invalid @enderror" required>
                        <option value="">Select SubCategory</option>
                        @foreach($subcategories as $sub)
                            <option value="{{ $sub->id }}" {{ (string) old('sub_category_id', $selectedSubcategoryId) === (string) $sub->id ? 'selected' : '' }}>
                                {{ $sub->category ? $sub->category->name . ' > ' : '' }}{{ $sub->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('sub_category_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Title -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Video Title <span class="text-danger">*</span></label>
                    <input type="text" name="title" class="form-control @error('title') is-invalid @enderror" placeholder="e.g. Bioflx Crown Placement Technique & Preparation" value="{{ old('title') }}" required>
                    @error('title')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Video Source: URL or File Upload -->
                <div class="col-12">
                    <div class="p-3 border rounded bg-light">
                        <label class="form-label fw-bold text-dark d-block mb-2">
                            <i class="ti ti-video me-1"></i> Video Source <span class="text-danger">*</span>
                            <span class="text-muted fw-normal small ms-1">(Enter a Video URL OR Choose a Video file to upload)</span>
                        </label>
                        <div class="row g-3 align-items-center">
                            <!-- Option A: Video URL -->
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small text-secondary">
                                    <i class="ti ti-link me-1"></i> Video URL
                                </label>
                                <input type="text" name="video_url" class="form-control @error('video_url') is-invalid @enderror" placeholder="https://www.youtube.com/watch?v=... or Vimeo URL" value="{{ old('video_url') }}">
                                <small class="text-muted d-block mt-1">Paste YouTube, Vimeo, or external video URL.</small>
                            </div>

                            <!-- Divider / OR -->
                            <div class="col-md-1 text-center d-none d-md-block">
                                <span class="badge bg-secondary rounded-pill py-2 px-2 fw-semibold">OR</span>
                            </div>

                            <!-- Option B: Choose File -->
                            <div class="col-md-5">
                                <label class="form-label fw-semibold small text-secondary">
                                    <i class="ti ti-upload me-1"></i> Choose Video File
                                </label>
                                <input type="file" name="video_file" class="form-control @error('video_file') is-invalid @enderror" accept="video/mp4,video/webm,video/ogg,video/quicktime,video/*">
                                <small class="text-muted d-block mt-1">Upload video file (MP4, WEBM, MOV, etc. Max: 150MB)</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Custom Thumbnail (Optional) -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Custom Thumbnail <span class="text-muted small fw-normal">(Optional)</span></label>
                    <input type="file" name="thumbnail" class="form-control @error('thumbnail') is-invalid @enderror" accept="image/*">
                    <small class="text-muted">If omitted for YouTube videos, thumbnail is auto-fetched.</small>
                    @error('thumbnail')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Sort Order -->
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Sort Order</label>
                    <input type="number" name="sort_order" min="0" class="form-control @error('sort_order') is-invalid @enderror" value="{{ old('sort_order', 0) }}">
                    <small class="text-muted">Order within subcategory.</small>
                    @error('sort_order')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Status -->
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                    <select name="status" class="form-select">
                        <option value="1" {{ (string) old('status', '1') === '1' ? 'selected' : '' }}>Active</option>
                        <option value="0" {{ (string) old('status', '1') === '0' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>

                <!-- Video-wise Description -->
                <div class="col-12">
                    <label class="form-label fw-semibold">Video Description <span class="text-muted small fw-normal">(Video-wise description)</span></label>
                    <textarea name="description" rows="5" class="form-control @error('description') is-invalid @enderror" placeholder="Write video-wise description, clinical steps, key observations, or instructions...">{{ old('description') }}</textarea>
                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Buttons -->
                <div class="col-12 pt-3">
                    <button type="submit" class="btn fw-semibold px-4" style="background-color: #ffcc00; color: #111;">
                        <i class="ti ti-check me-1"></i> Save Tutorial
                    </button>
                    <a href="{{ route('admin.tutorials.index', ['sub_category_id' => $selectedSubcategoryId]) }}" class="btn btn-secondary ms-2">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
