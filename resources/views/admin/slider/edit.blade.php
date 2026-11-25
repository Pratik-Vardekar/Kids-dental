@extends('layouts.admin')

@section('content')
    <div class="container-fluid my-2">

        <div class="mb-3">
            <h4 class="mb-1">Edit Slider</h4>
            <p class="text-muted mb-0">Update slider details</p>
        </div>

        <div class="alert alert-light border mb-4 py-2 text-muted small">
            <strong class="text-dark">Mobile Image:</strong> 750 × 800 px or 600 × 600 px <span class="text-secondary">(optional, falls back to desktop image)</span>
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
            <div class="card-body">

                <form action="{{ route('slider.update', $slider->id) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="row g-3">
                        <!-- Title -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Title</label>
                            <input type="text" name="title" class="form-control"
                                value="{{ old('title', $slider->title) }}" placeholder="Slider title">
                        </div>

                        <!-- Status -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Status</label>
                            <select name="status" class="form-select">
                                <option value="1" {{ old('status', $slider->status) == 1 ? 'selected' : '' }}>
                                    Active
                                </option>
                                <option value="0" {{ old('status', $slider->status) == 0 ? 'selected' : '' }}>
                                    Inactive
                                </option>
                            </select>
                        </div>

                        <!-- Sort Order -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Sort Order</label>
                            <input type="number" name="sort_order" class="form-control"
                                value="{{ old('sort_order', $slider->sort_order ?? 0) }}" min="0" placeholder="0">
                        </div>

                        <!-- Link -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Link</label>
                            <input type="text" name="link" class="form-control"
                                value="{{ old('link', $slider->link) }}" placeholder="https://example.com">
                        </div>

                        <!-- Description -->
                        <div class="col-12">
                            <label class="form-label fw-semibold">Description</label>
                            <textarea name="description" class="form-control" rows="3"
                                placeholder="Slider description (optional)">{{ old('description', $slider->description) }}</textarea>
                        </div>

                        <!-- Desktop Image Input -->
                        <div class="col-md-6">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label fw-semibold mb-0">Desktop Image</label>
                                <span class="text-muted small">1920 × 825 px</span>
                            </div>
                            <input type="file" name="image" id="desktop_image_input" class="form-control"
                                accept="image/*">
                            <small class="text-muted d-block mt-1">Leave blank to keep current desktop image.</small>
                        </div>

                        <!-- Mobile Image Input -->
                        <div class="col-md-6">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <div>
                                    <label class="form-label fw-semibold mb-0">Mobile Image</label>
                                    <span class="badge bg-primary-subtle text-primary ms-1" style="font-size: 0.72rem;">Optional</span>
                                </div>
                                <span class="text-muted small">750 × 800 px</span>
                            </div>
                            <input type="file" name="mobile_image" id="mobile_image_input" class="form-control"
                                accept="image/*">
                            <small class="text-muted d-block mt-1">Leave blank to keep current. Desktop image will never be shown on mobile screens.</small>
                        </div>

                        <!-- Current Desktop Image Preview -->
                        <div class="col-md-6">
                            <label class="form-label small text-muted mb-2">Current Desktop Image</label>
                            <div class="p-2 border rounded bg-light d-inline-block">
                                @if ($slider->image)
                                    <img id="preview-desktop"
                                        src="{{ asset('backend/assets/images/slider/' . $slider->image) }}"
                                        class="rounded border shadow-sm"
                                        style="max-width: 100%; height: 110px; object-fit: cover;">
                                @else
                                    <div id="preview-desktop"
                                        class="rounded border bg-white d-flex align-items-center justify-content-center text-muted small"
                                        style="width: 140px; height: 110px;">
                                        No Desktop Image
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Current Mobile Image Preview -->
                        <div class="col-md-6">
                            <label class="form-label small text-muted mb-2">Current Mobile Image</label>
                            <div class="p-2 border rounded bg-light d-inline-block">
                                @if ($slider->mobile_image)
                                    <img id="preview-mobile"
                                        src="{{ asset('backend/assets/images/slider/' . $slider->mobile_image) }}"
                                        class="rounded border shadow-sm"
                                        style="max-width: 100%; height: 110px; object-fit: cover;">
                                @else
                                    <div id="preview-mobile"
                                        class="rounded border bg-white d-flex align-items-center justify-content-center text-muted small"
                                        style="width: 140px; height: 110px;">
                                        No Mobile Image
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="col-12 mt-4 pt-3 border-top">
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="ti ti-device-floppy me-1"></i>
                                Update Slider
                            </button>
                            <a href="{{ route('slider.index') }}" class="btn btn-light ms-2">
                                Cancel
                            </a>
                        </div>
                    </div>

                </form>

            </div>
        </div>

    </div>

    <script>
        document.getElementById('desktop_image_input').addEventListener('change', function(e) {
            let file = e.target.files[0];
            if (file) {
                let reader = new FileReader();
                reader.onload = function(event) {
                    let container = document.getElementById('preview-desktop');
                    if (container && container.tagName.toLowerCase() === 'img') {
                        container.src = event.target.result;
                    } else if (container) {
                        let img = document.createElement('img');
                        img.id = 'preview-desktop';
                        img.src = event.target.result;
                        img.className = 'rounded border shadow-sm';
                        img.style.cssText = 'max-width: 100%; height: 110px; object-fit: cover;';
                        container.parentNode.replaceChild(img, container);
                    }
                };
                reader.readAsDataURL(file);
            }
        });

        document.getElementById('mobile_image_input').addEventListener('change', function(e) {
            let file = e.target.files[0];
            if (file) {
                let reader = new FileReader();
                reader.onload = function(event) {
                    let container = document.getElementById('preview-mobile');
                    if (container && container.tagName.toLowerCase() === 'img') {
                        container.src = event.target.result;
                    } else if (container) {
                        let img = document.createElement('img');
                        img.id = 'preview-mobile';
                        img.src = event.target.result;
                        img.className = 'rounded border shadow-sm';
                        img.style.cssText = 'max-width: 100%; height: 110px; object-fit: cover;';
                        container.parentNode.replaceChild(img, container);
                    }
                };
                reader.readAsDataURL(file);
            }
        });
    </script>
@endsection
