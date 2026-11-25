@extends('layouts.admin')

@section('content')
<div class="container-fluid">

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card">
        <div class="card-header">
            <h4 class="mb-0">Home Page Banners</h4>
        </div>

        <div class="card-body">

            <form action="{{ route('home-banner.update') }}"
                  method="POST"
                  enctype="multipart/form-data">

                @csrf

                @foreach($banners as $banner)

                    <div class="card shadow-sm border mb-4">

                        <div class="card-header bg-light d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                {{ $banner->name }}
                            </h5>

                            <span class="badge bg-primary fs-6">
                                {{ $banner->recommended_size ?? 'Size Not Set' }}
                            </span>
                        </div>

                        <div class="card-body">

                            <input type="hidden"
                                   name="banner_ids[]"
                                   value="{{ $banner->id }}">

                            <div class="row align-items-center">

                                <!-- Preview -->
                                <div class="col-md-6">

                                    <label class="form-label fw-bold mb-2">
                                        Current Banner
                                    </label>

                                    <div class="border rounded bg-light p-3 text-center">

                                        <img
                                            id="preview-{{ $banner->id }}"
                                            src="{{ $banner->image ? asset($banner->image) : asset('backend/assets/images/no-image.png') }}"
                                            class="img-fluid rounded shadow-sm"
                                            style="max-height:300px; object-fit:contain;">

                                    </div>

                                </div>

                                <!-- Upload -->
                                <div class="col-md-6">

                                    <label class="form-label fw-bold mb-2">
                                        Upload New Banner
                                    </label>

                                    <input
                                        type="file"
                                        class="form-control banner-input"
                                        name="image[{{ $banner->id }}]"
                                        data-preview="preview-{{ $banner->id }}"
                                        accept="image/*">

                                    <small class="text-muted mt-2 d-block">
                                        Recommended Size:
                                        <strong>
                                            {{ $banner->recommended_size ?? 'Not Set' }}
                                        </strong>
                                    </small>

                                    <div class="mt-3">
                                        <span class="badge bg-success">
                                            Live Preview Enabled
                                        </span>
                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>

                @endforeach

                <div class="text-end">
                    <button type="submit" class="btn btn-success px-4">
                        <i class="ti ti-device-floppy me-1"></i>
                        Save All Changes
                    </button>
                </div>

            </form>

        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {

    document.querySelectorAll('.banner-input').forEach(input => {

        input.addEventListener('change', function () {

            const file = this.files[0];

            if (!file) return;

            const preview = document.getElementById(
                this.dataset.preview
            );

            preview.src = URL.createObjectURL(file);

        });

    });

});
</script>
@endpush