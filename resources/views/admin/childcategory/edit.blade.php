@extends('layouts.admin')

@section('content')
    <div class="container-fluid my-2">

        <div class="mb-4">
            <h4 class="mb-1">Edit Child Category</h4>
            <p class="text-muted mb-0">Update child category details</p>
        </div>

        <div class="card">
            <div class="card-body">

                <form action="{{ route('childcategory.update', $childcategory->id) }}" method="POST"
                    enctype="multipart/form-data" class="row g-3">

                    @csrf
                    @method('PUT')

                    {{-- Category --}}
                    <div class="col-md-6">
                        <label class="form-label">Category</label>

                        <select name="category_id" id="category_id" class="form-select" required>
                            <option value="">Select Category</option>

                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}"
                                    {{ old('category_id', $childcategory->subcategory->category_id ?? '') == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>

                        @error('category_id')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Sub Category --}}
                    <div class="col-md-6">
                        <label class="form-label">Sub Category</label>

                        <select name="sub_category_id" id="sub_category_id" class="form-select" required>

                            <option value="">Select Sub Category</option>

                            @foreach ($subcategories as $subcategory)
                                <option value="{{ $subcategory->id }}"
                                    {{ old('sub_category_id', $childcategory->sub_category_id) == $subcategory->id ? 'selected' : '' }}>
                                    {{ $subcategory->name }}
                                </option>
                            @endforeach

                        </select>

                        @error('sub_category_id')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Child Category Name --}}
                    <div class="col-md-6">
                        <label class="form-label">Child Category Name</label>

                        <input type="text" name="name" id="name" class="form-control"
                            value="{{ old('name', $childcategory->name) }}" placeholder="Enter child category name"
                            required>

                        @error('name')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Slug --}}
                    <div class="col-md-6">
                        <label class="form-label">Slug</label>

                        <input type="text" name="slug" id="slug" class="form-control"
                            value="{{ old('slug', $childcategory->slug) }}" placeholder="child-category-slug">

                        @error('slug')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Image --}}
                    <div class="col-md-6">
                        <label class="form-label">Image</label>

                        <input type="file" name="image" id="image" class="form-control" accept="image/*">

                        @error('image')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror

                        @if ($childcategory->image)
                            <div class="mt-2">
                                <img id="preview-image" src="{{ asset($childcategory->image) }}" alt="Child Category Image"
                                    class="rounded border" style="width:120px;height:120px;object-fit:cover;">
                            </div>
                        @else
                            <div class="mt-2 d-none" id="preview-wrapper">
                                <img id="preview-image" src="" class="rounded border"
                                    style="width:120px;height:120px;object-fit:cover;">
                            </div>
                        @endif
                    </div>

                    {{-- Status --}}
                    <div class="col-md-6">
                        <label class="form-label">Status</label>

                        <select name="status" class="form-select" required>
                            <option value="1" {{ old('status', $childcategory->status) == 1 ? 'selected' : '' }}>
                                Active
                            </option>

                            <option value="0" {{ old('status', $childcategory->status) == 0 ? 'selected' : '' }}>
                                Inactive
                            </option>
                        </select>

                        @error('status')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Buttons --}}
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-device-floppy me-1"></i>
                            Update Child Category
                        </button>

                        <a href="{{ route('childcategory.index') }}" class="btn btn-light">
                            Cancel
                        </a>
                    </div>

                </form>

            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {

            const categorySelect = document.getElementById('category_id');
            const subcategorySelect = document.getElementById('sub_category_id');

            function loadSubcategories(categoryId, selectedId = '') {

                subcategorySelect.innerHTML = '<option value="">Loading...</option>';

                if (!categoryId) {
                    subcategorySelect.innerHTML =
                        '<option value="">Select Sub Category</option>';
                    return;
                }

                fetch("{{ url('admin/get-subcategories') }}/" + categoryId)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Unable to load subcategories');
                        }
                        return response.json();
                    })
                    .then(data => {

                        subcategorySelect.innerHTML =
                            '<option value="">Select Sub Category</option>';

                        data.forEach(subcategory => {
                            const option = document.createElement('option');
                            option.value = subcategory.id;
                            option.textContent = subcategory.name;

                            if (String(selectedId) === String(subcategory.id)) {
                                option.selected = true;
                            }

                            subcategorySelect.appendChild(option);
                        });
                    })
                    .catch(error => {
                        console.error(error);
                        subcategorySelect.innerHTML =
                            '<option value="">Unable to load Sub Categories</option>';
                    });
            }

            categorySelect.addEventListener('change', function() {
                loadSubcategories(this.value);
            });

            // Generate slug only when name is changed
            document.getElementById('name').addEventListener('input', function() {
                const slug = this.value
                    .toLowerCase()
                    .trim()
                    .replace(/[^a-z0-9\s-]/g, '')
                    .replace(/\s+/g, '-')
                    .replace(/-+/g, '-');

                document.getElementById('slug').value = slug;
            });

            // Image preview
            document.getElementById('image').addEventListener('change', function(e) {
                const file = e.target.files[0];

                if (file) {
                    const reader = new FileReader();

                    reader.onload = function(event) {
                        document.getElementById('preview-image').src = event.target.result;

                        const wrapper = document.getElementById('preview-wrapper');
                        if (wrapper) {
                            wrapper.classList.remove('d-none');
                        }
                    };

                    reader.readAsDataURL(file);
                }
            });

        });
    </script>
@endsection
