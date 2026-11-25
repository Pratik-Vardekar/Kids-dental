@extends('layouts.admin')

@section('content')
    <div class="container-fluid my-2">

        <div class="mb-3">
            <h4 class="mb-1">Add Slider</h4>
            <p class="text-muted mb-0">Create new slider</p>
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

                <form action="{{ route('slider.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <div class="row g-3">
                        <!-- Title -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Title</label>
                            <input type="text" name="title" class="form-control" value="{{ old('title') }}" placeholder="Slider title">
                        </div>

                        <!-- Status -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Status</label>
                            <select name="status" class="form-select">
                                <option value="1" {{ old('status', '1') == '1' ? 'selected' : '' }}>Active</option>
                                <option value="0" {{ old('status') == '0' ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </div>

                        <!-- Sort Order -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Sort Order</label>
                            <input type="number" name="sort_order" class="form-control" value="{{ old('sort_order', 0) }}" min="0" placeholder="0">
                        </div>

                        <!-- Link -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Link</label>
                            <input type="text" name="link" class="form-control" value="{{ old('link') }}" placeholder="https://example.com">
                        </div>

                        <!-- Description -->
                        <div class="col-12">
                            <label class="form-label fw-semibold">Description</label>
                            <textarea name="description" class="form-control" rows="3" placeholder="Slider description (optional)">{{ old('description') }}</textarea>
                        </div>

                        <!-- Desktop Image Input -->
                        <div class="col-md-6">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label fw-semibold mb-0">Desktop Image</label>
                                <span class="text-muted small">1920 × 825 px</span>
                            </div>
                            <input type="file" name="image" id="desktop_image_input" class="form-control" accept="image/*" required>
                            <small class="text-muted d-block mt-1">Recommended size: 1920 × 825 px</small>
                            <div class="mt-2 d-none" id="preview-desktop-box">
                                <img id="preview-desktop" class="rounded border shadow-sm" style="max-width: 100%; height: 110px; object-fit: cover;">
                            </div>
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
                            <input type="file" name="mobile_image" id="mobile_image_input" class="form-control" accept="image/*">
                            <small class="text-muted d-block mt-1">Optional. Falls back to desktop image if not provided.</small>
                            <div class="mt-2 d-none" id="preview-mobile-box">
                                <img id="preview-mobile" class="rounded border shadow-sm" style="max-width: 100%; height: 110px; object-fit: cover;">
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="col-12 mt-4 pt-3 border-top">
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="ti ti-device-floppy me-1"></i>
                                Save Slider
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
                    let preview = document.getElementById('preview-desktop');
                    let box = document.getElementById('preview-desktop-box');
                    preview.src = event.target.result;
                    box.classList.remove('d-none');
                };
                reader.readAsDataURL(file);
            }
        });

        document.getElementById('mobile_image_input').addEventListener('change', function(e) {
            let file = e.target.files[0];
            if (file) {
                let reader = new FileReader();
                reader.onload = function(event) {
                    let preview = document.getElementById('preview-mobile');
                    let box = document.getElementById('preview-mobile-box');
                    preview.src = event.target.result;
                    box.classList.remove('d-none');
                };
                reader.readAsDataURL(file);
            }
        });
    </script>
@endsection
