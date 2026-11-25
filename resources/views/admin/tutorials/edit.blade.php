@extends('layouts.admin')

@section('content')
<div class="container-fluid my-2">

    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 text-dark fw-bold">Edit Tutorial Video</h4>
            <p class="text-muted mb-0">Update video details and description for this subcategory</p>
        </div>
        <a href="{{ route('admin.tutorials.index', ['sub_category_id' => $tutorial->sub_category_id]) }}" class="btn btn-secondary">
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
            <form action="{{ route('admin.tutorials.update', $tutorial) }}" method="POST" enctype="multipart/form-data" class="row g-3">
                @csrf
                @method('PUT')

                <!-- SubCategory -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold">SubCategory <span class="text-danger">*</span></label>
                    <select name="sub_category_id" class="form-select @error('sub_category_id') is-invalid @enderror" required>
                        <option value="">Select SubCategory</option>
                        @foreach($subcategories as $sub)
                            <option value="{{ $sub->id }}" {{ (string) old('sub_category_id', $tutorial->sub_category_id) === (string) $sub->id ? 'selected' : '' }}>
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
                    <input type="text" name="title" class="form-control @error('title') is-invalid @enderror" placeholder="Video Title" value="{{ old('title', $tutorial->title) }}" required>
                    @error('title')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Video Source: URL or File Upload -->
                <div class="col-12">
                    <div class="p-3 border rounded bg-light">
                        <label class="form-label fw-bold text-dark d-block mb-2">
                            <i class="ti ti-video me-1"></i> Video Source <span class="text-danger">*</span>
                            <span class="text-muted fw-normal small ms-1">(Update Video URL OR Choose a new Video file)</span>
                        </label>
                        <div class="row g-3 align-items-center">
                            <!-- Option A: Video URL -->
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small text-secondary">
                                    <i class="ti ti-link me-1"></i> Video URL
                                </label>
                                <input type="text" name="video_url" class="form-control @error('video_url') is-invalid @enderror" placeholder="https://www.youtube.com/watch?v=..." value="{{ old('video_url', $tutorial->video_file ? '' : $tutorial->video_url) }}">
                                <small class="text-muted d-block mt-1">YouTube, Vimeo, or web link.</small>
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
                                <small class="text-muted d-block mt-1">Replace with a new video file (Max: 150MB)</small>
                            </div>

                            <!-- Current Video Indicator -->
                            <div class="col-12 mt-2 pt-2 border-top">
                                <span class="fw-semibold small text-dark me-2">Current Video:</span>
                                @if ($tutorial->is_direct_video)
                                    <span class="badge bg-info text-white me-2">Uploaded File</span>
                                    <a href="{{ $tutorial->video_source_url }}" target="_blank" class="small text-primary">
                                        <i class="ti ti-download me-1"></i>View / Play File
                                    </a>
                                @elseif ($tutorial->video_url)
                                    <span class="badge bg-danger text-white me-2">URL</span>
                                    <a href="{{ $tutorial->video_url }}" target="_blank" class="small text-primary">
                                        <i class="ti ti-external-link me-1"></i>{{ $tutorial->video_url }}
                                    </a>
                                @else
                                    <span class="text-muted small">None</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Custom Thumbnail -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Custom Thumbnail <span class="text-muted small fw-normal">(Optional replacement)</span></label>
                    <input type="file" name="thumbnail" class="form-control @error('thumbnail') is-invalid @enderror" accept="image/*">
                    <div class="d-flex align-items-center mt-2 gap-2">
                        <img src="{{ $tutorial->thumbnail_url }}" alt="Current Preview" class="rounded border" style="width: 80px; height: 50px; object-fit: cover;">
                        <small class="text-muted">Current thumbnail preview</small>
                    </div>
                    @error('thumbnail')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Sort Order -->
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Sort Order</label>
                    <input type="number" name="sort_order" min="0" class="form-control @error('sort_order') is-invalid @enderror" value="{{ old('sort_order', $tutorial->sort_order) }}">
                    @error('sort_order')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Status -->
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                    <select name="status" class="form-select">
                        <option value="1" {{ (string) old('status', $tutorial->status) === '1' ? 'selected' : '' }}>Active</option>
                        <option value="0" {{ (string) old('status', $tutorial->status) === '0' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>

                <!-- Video-wise Description -->
                <div class="col-12">
                    <label class="form-label fw-semibold">Video Description <span class="text-muted small fw-normal">(Video-wise description)</span></label>
                    <textarea name="description" rows="5" class="form-control @error('description') is-invalid @enderror" placeholder="Write video-wise description...">{{ old('description', $tutorial->description) }}</textarea>
                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Buttons -->
                <div class="col-12 pt-3">
                    <button type="submit" class="btn fw-semibold px-4" style="background-color: #ffcc00; color: #111;">
                        <i class="ti ti-check me-1"></i> Update Tutorial
                    </button>
                    <a href="{{ route('admin.tutorials.index', ['sub_category_id' => $tutorial->sub_category_id]) }}" class="btn btn-secondary ms-2">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
