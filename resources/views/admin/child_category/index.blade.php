@extends('layouts.admin')

@section('content')
    <div class="container-fluid my-2">

        {{-- Header --}}
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="mb-1">Child Categories</h4>
                <p class="text-muted mb-0">Manage your child categories</p>
            </div>

            <a href="{{ route('childcategory.create') }}" class="btn btn-primary">
                <i class="ti ti-plus me-1"></i>
                Add Child Category
            </a>
        </div>

        {{-- Success Message --}}
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        {{-- Error Message --}}
        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        {{-- Search and Filters --}}
        <div class="card mb-3">
            <div class="card-body">

                <form action="{{ route('childcategory.index') }}" method="GET">
                    <div class="row g-3">

                        <div class="col-md-4">
                            <label class="form-label">Search</label>
                            <input type="text" name="search" class="form-control" placeholder="Search child category..."
                                value="{{ request('search') }}">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Category</label>
                            <select name="category_id" class="form-select">
                                <option value="">All Categories</option>

                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}"
                                        {{ request('category_id') == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Sub Category</label>
                            <select name="sub_category_id" class="form-select">
                                <option value="">All Sub Categories</option>

                                @foreach ($subcategories as $subcategory)
                                    <option value="{{ $subcategory->id }}"
                                        {{ request('sub_category_id') == $subcategory->id ? 'selected' : '' }}>
                                        {{ $subcategory->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="">All</option>
                                <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>
                                    Active
                                </option>
                                <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>
                                    Inactive
                                </option>
                            </select>
                        </div>

                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">
                                <i class="ti ti-search me-1"></i> Filter
                            </button>

                            <a href="{{ route('childcategory.index') }}" class="btn btn-light">
                                Reset
                            </a>
                        </div>

                    </div>
                </form>

            </div>
        </div>

        {{-- Table --}}
        <div class="card">
            <div class="card-body">

                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle">

                        <thead class="table-light">
                            <tr>
                                <th width="60">#</th>
                                <th>Image</th>
                                <th>Category</th>
                                <th>Sub Category</th>
                                <th>Child Category</th>
                                <th>Slug</th>
                                <th>Status</th>
                                <th width="150">Action</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse ($childCategories as $child)
                                <tr>
                                    <td>
                                        {{ $childCategories->firstItem() + $loop->index }}
                                    </td>

                                    <td>
                                        @if ($child->image)
                                            <img src="{{ asset($child->image) }}" alt="{{ $child->name }}"
                                                style="width:55px;height:55px;object-fit:cover;" class="rounded border">
                                        @else
                                            <span class="text-muted">No Image</span>
                                        @endif
                                    </td>

                                    <td>
                                        {{ $child->subcategory->category->name ?? '-' }}
                                    </td>

                                    <td>
                                        {{ $child->subcategory->name ?? '-' }}
                                    </td>

                                    <td>
                                        <strong>{{ $child->name }}</strong>
                                    </td>

                                    <td>{{ $child->slug }}</td>

                                    <td>
                                        @if ($child->status == 1)
                                            <span class="badge bg-success">Active</span>
                                        @else
                                            <span class="badge bg-danger">Inactive</span>
                                        @endif
                                    </td>

                                    <td>
                                        <div class="d-flex gap-2">

                                            <a href="{{ route('childcategory.edit', $child->id) }}"
                                                class="btn btn-sm btn-warning">
                                                <i class="ti ti-edit"></i>
                                            </a>

                                            <form action="{{ route('childcategory.destroy', $child->id) }}" method="POST"
                                                onsubmit="return confirm('Are you sure you want to delete this child category?')">

                                                @csrf
                                                @method('DELETE')

                                                <button type="submit" class="btn btn-sm btn-danger">
                                                    <i class="ti ti-trash"></i>
                                                </button>
                                            </form>

                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-4">
                                        No child categories found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>

                    </table>
                </div>

                {{-- Pagination --}}
                <div class="mt-3">
                    {{ $childCategories->appends(request()->query())->links() }}
                </div>

            </div>
        </div>

    </div>
@endsection
