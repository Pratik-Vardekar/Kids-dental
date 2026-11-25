@extends('layouts.admin')

@section('content')
    <div class="container-fluid my-2">
        <div class="mb-4">
            <h4 class="mb-1">Edit Why Choose Us Card</h4>
            <p class="text-muted mb-0">Update card details</p>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="card">
            <div class="card-body">
                <form action="{{ route('admin.why-choose-us.update', $item->id) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Title <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control" value="{{ old('title', $item->title) }}" required>
                        </div>

                        <div class="col-md-12 mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="4">{{ old('description', $item->description) }}</textarea>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Change Image</label>
                            <input type="file" name="image" id="card_image" class="form-control" accept="image/*">
                            <small class="text-muted">Allowed types: jpeg, png, jpg, webp, svg. Leave empty to keep existing image.</small>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Image Preview</label>
                            <div>
                                @if ($item->image)
                                    <img id="preview-image"
                                        src="{{ asset('backend/assets/images/why-choose-us/' . $item->image) }}"
                                        class="rounded border"
                                        style="width: 140px; height: 100px; object-fit: cover;">
                                @else
                                    <img id="preview-image" class="rounded border d-none"
                                        style="width: 140px; height: 100px; object-fit: cover;">
                                    <span id="no-img-text" class="text-muted">No image uploaded</span>
                                @endif
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Sort Order</label>
                            <input type="number" name="sort_order" class="form-control" value="{{ old('sort_order', $item->sort_order) }}" min="0">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="1" {{ old('status', $item->status) == 1 ? 'selected' : '' }}>Active</option>
                                <option value="0" {{ old('status', $item->status) == 0 ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </div>
                    </div>

                    <div class="mt-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-device-floppy me-1"></i> Update Card
                        </button>
                        <a href="{{ route('admin.why-choose-us.index') }}" class="btn btn-light ms-2">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('card_image').addEventListener('change', function(e) {
            let file = e.target.files[0];
            if (file) {
                let reader = new FileReader();
                reader.onload = function(event) {
                    let preview = document.getElementById('preview-image');
                    let noImgText = document.getElementById('no-img-text');
                    preview.src = event.target.result;
                    preview.classList.remove('d-none');
                    if (noImgText) {
                        noImgText.classList.add('d-none');
                    }
                }
                reader.readAsDataURL(file);
            }
        });
    </script>
@endsection
