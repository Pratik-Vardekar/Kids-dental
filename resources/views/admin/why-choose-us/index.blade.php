@extends('layouts.admin')

@section('content')
    <div class="container-fluid my-2">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="mb-1">Why Partner With Kids-e-Dental?</h4>
                <p class="text-muted mb-0">Manage Why Choose Us / Partnership cards</p>
            </div>
            <a href="{{ route('admin.why-choose-us.create') }}" class="btn btn-primary">
                <i class="ti ti-plus me-1"></i> Add New Card
            </a>
        </div>

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="ti ti-check me-1"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped align-middle">
                        <thead class="table-light">
                            <tr>
                                <th width="60">#</th>
                                <th width="120">Image</th>
                                <th>Title</th>
                                <th>Description</th>
                                <th width="100">Sort Order</th>
                                <th width="110">Status</th>
                                <th width="140" class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($items as $index => $item)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>
                                        @if ($item->image)
                                            <img src="{{ asset('backend/assets/images/why-choose-us/' . $item->image) }}"
                                                alt="{{ $item->title }}"
                                                class="rounded border"
                                                style="width: 80px; height: 60px; object-fit: cover;">
                                        @else
                                            <div class="bg-light rounded border d-flex align-items-center justify-content-center text-muted"
                                                style="width: 80px; height: 60px; font-size: 11px;">
                                                No Image
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        <strong>{{ $item->title }}</strong>
                                    </td>
                                    <td>
                                        <p class="mb-0 text-muted" style="max-width: 400px; line-height: 1.4;">
                                            {{ \Illuminate\Support\Str::limit($item->description, 120) }}
                                        </p>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary">{{ $item->sort_order }}</span>
                                    </td>
                                    <td>
                                        <form action="{{ route('admin.why-choose-us.status', $item->id) }}" method="POST" style="display:inline;">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-sm {{ $item->status == 1 ? 'btn-success' : 'btn-danger' }}"
                                                title="Click to toggle status">
                                                {{ $item->status == 1 ? 'Active' : 'Inactive' }}
                                            </button>
                                        </form>
                                    </td>
                                    <td class="text-center">
                                        <a href="{{ route('admin.why-choose-us.edit', $item->id) }}"
                                            class="btn btn-sm btn-outline-primary me-1" title="Edit">
                                            <i class="ti ti-edit"></i>
                                        </a>

                                        <form action="{{ route('admin.why-choose-us.destroy', $item->id) }}"
                                            method="POST"
                                            class="d-inline"
                                            onsubmit="return confirm('Are you sure you want to delete this card?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                                <i class="ti ti-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">
                                        <i class="ti ti-info-circle fs-3 d-block mb-2"></i>
                                        No Why Choose Us cards found. Click "Add New Card" to create one.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
