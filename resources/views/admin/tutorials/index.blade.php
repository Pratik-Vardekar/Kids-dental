@extends('layouts.admin')

@section('content')
<div class="container-fluid my-2">

    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 text-dark fw-bold">Tutorials</h4>
            <p class="text-muted mb-0">Manage subcategory-wise tutorial videos and descriptions</p>
        </div>

        <a href="{{ route('admin.tutorials.create', ['sub_category_id' => $subcategoryId]) }}" class="btn fw-semibold" style="background-color: #ffcc00; color: #111;">
            <i class="ti ti-plus me-1"></i> Add Tutorial
        </a>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Simple Filter Bar -->
    <div class="card mb-4">
        <div class="card-body p-3">
            <form action="{{ route('admin.tutorials.index') }}" method="GET" class="row g-2 align-items-center">
                <div class="col-md-5">
                    <input type="text" name="search" class="form-control" placeholder="Search title, description or URL..." value="{{ request('search') }}">
                </div>

                <div class="col-md-4">
                    <select name="sub_category_id" class="form-select">
                        <option value="">All SubCategories</option>
                        @foreach($subcategories as $sub)
                            <option value="{{ $sub->id }}" {{ (string) request('sub_category_id', $subcategoryId) === (string) $sub->id ? 'selected' : '' }}>
                                {{ $sub->category ? $sub->category->name . ' > ' : '' }}{{ $sub->name }} ({{ $subcategoryCounts[$sub->id] ?? 0 }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn fw-semibold w-100" style="background-color: #ffcc00; color: #111;">
                        <i class="ti ti-search me-1"></i> Filter
                    </button>
                    @if (request()->hasAny(['search', 'sub_category_id']))
                        <a href="{{ route('admin.tutorials.index') }}" class="btn btn-light border px-3" title="Reset Filter">
                            <i class="ti ti-refresh"></i>
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Tutorials Table -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="50" class="ps-3">#</th>
                            <th width="120">Video</th>
                            <th style="min-width: 170px;">SubCategory</th>
                            <th style="min-width: 220px;">Title</th>
                            <th style="min-width: 280px;">Video Description</th>
                            <th width="80" class="text-center">Order</th>
                            <th width="100" class="text-center">Status</th>
                            <th width="150" class="text-center pe-3">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tutorials as $index => $item)
                            <tr>
                                <td class="ps-3 text-muted">{{ $tutorials->firstItem() + $index }}</td>
                                <td>
                                    <div class="position-relative d-inline-block rounded overflow-hidden shadow-sm" style="width: 100px; height: 60px; background: #000;">
                                        <img src="{{ $item->thumbnail_url }}" alt="{{ $item->title }}" class="w-100 h-100 object-fit-cover" style="opacity: 0.88;" onerror="this.src='{{ asset('backend/assets/images/default-video.png') }}'">
                                        <a href="{{ $item->video_source_url }}" target="_blank" class="position-absolute top-50 start-50 translate-middle text-white" title="Play Video" style="font-size: 1.3rem; text-shadow: 0 1px 4px rgba(0,0,0,0.8);">
                                            <i class="ti ti-player-play-filled"></i>
                                        </a>
                                    </div>
                                </td>
                                <td>
                                    <strong>{{ $item->subCategory?->name ?? 'N/A' }}</strong>
                                    @if ($item->subCategory?->category)
                                        <small class="text-muted d-block fs-xs">{{ $item->subCategory->category->name }}</small>
                                    @endif
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark">{{ $item->title }}</div>
                                    <div class="d-flex align-items-center gap-1 mt-1">
                                        @if ($item->is_direct_video)
                                            <span class="badge bg-info text-white" style="font-size: 10px;">Video File</span>
                                            <a href="{{ $item->video_source_url }}" target="_blank" class="text-muted small text-truncate d-inline-block" style="max-width: 150px;">
                                                <i class="ti ti-file-play me-1"></i>Play file
                                            </a>
                                        @else
                                            <span class="badge bg-danger text-white" style="font-size: 10px;">URL</span>
                                            <a href="{{ $item->video_url }}" target="_blank" class="text-muted small text-truncate d-inline-block" style="max-width: 150px;">
                                                <i class="ti ti-link me-1"></i>{{ $item->video_url }}
                                            </a>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    @if ($item->description)
                                        <div class="text-muted small" style="white-space: pre-line; max-height: 80px; overflow-y: auto;">
                                            {{ $item->description }}
                                        </div>
                                    @else
                                        <span class="text-muted fst-italic small">No description added</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-light text-dark border">{{ $item->sort_order }}</span>
                                </td>
                                <td class="text-center">
                                    @if ($item->status)
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-danger">Inactive</span>
                                    @endif
                                </td>
                                <td class="text-center pe-3">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('admin.tutorials.edit', $item) }}" class="btn fw-semibold" style="background-color: #ffcc00; color: #111;" title="Edit">
                                            <i class="ti ti-edit"></i> Edit
                                        </a>
                                        <form action="{{ route('admin.tutorials.destroy', $item) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this tutorial video?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger" title="Delete">
                                                <i class="ti ti-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-5">
                                    <i class="ti ti-video-off fs-1 d-block mb-2 opacity-50"></i>
                                    <h6 class="text-muted fw-normal">No tutorial videos found.</h6>
                                    <a href="{{ route('admin.tutorials.create', ['sub_category_id' => $subcategoryId]) }}" class="btn btn-sm fw-semibold mt-2" style="background-color: #ffcc00; color: #111;">
                                        <i class="ti ti-plus me-1"></i> Add First Tutorial
                                    </a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($tutorials->hasPages())
                <div class="p-3 border-top">
                    {{ $tutorials->links() }}
                </div>
            @endif
        </div>
    </div>

</div>
@endsection
