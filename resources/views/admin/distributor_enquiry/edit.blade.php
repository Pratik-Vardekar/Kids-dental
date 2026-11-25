@extends('layouts.admin')
@section('content')

<div class="container-fluid my-2">

    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="mb-1">Edit Distributor Enquiry</h4>
            <p class="text-muted mb-0">Update enquiry details and status for {{ $distributorEnquiry->company_name }}</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.distributor-enquiries.show', $distributorEnquiry->id) }}" class="btn btn-info text-white">
                <i class="ti ti-eye me-1"></i> View Details
            </a>
            <a href="{{ route('admin.distributor-enquiries.index') }}" class="btn btn-secondary">
                <i class="ti ti-arrow-left me-1"></i> Back to List
            </a>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.distributor-enquiries.update', $distributorEnquiry->id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Company Name <span class="text-danger">*</span></label>
                        <input type="text" name="company_name" class="form-control"
                            value="{{ old('company_name', $distributorEnquiry->company_name) }}" required>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Contact Person <span class="text-danger">*</span></label>
                        <input type="text" name="contact_person" class="form-control"
                            value="{{ old('contact_person', $distributorEnquiry->contact_person) }}" required>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control"
                            value="{{ old('email', $distributorEnquiry->email) }}" required>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Mobile No. <span class="text-danger">*</span></label>
                        <input type="text" name="mobile_no" class="form-control"
                            value="{{ old('mobile_no', $distributorEnquiry->mobile_no) }}" required>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Country / City <span class="text-danger">*</span></label>
                        <input type="text" name="country_city" class="form-control"
                            value="{{ old('country_city', $distributorEnquiry->country_city) }}" required>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Status <span class="text-danger">*</span></label>
                        <select name="status" class="form-select" required>
                            <option value="0" {{ (string) old('status', $distributorEnquiry->status ? '1' : '0') === '0' ? 'selected' : '' }}>Unread / Pending</option>
                            <option value="1" {{ (string) old('status', $distributorEnquiry->status ? '1' : '0') === '1' ? 'selected' : '' }}>Read / Contacted</option>
                        </select>
                    </div>

                    <div class="col-12 mb-3">
                        <label class="form-label">Message</label>
                        <textarea name="message" class="form-control" rows="5">{{ old('message', $distributorEnquiry->message) }}</textarea>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mt-3">
                    <button type="submit" class="btn btn-success">
                        <i class="ti ti-check me-1"></i> Update Enquiry
                    </button>

                    <form action="{{ route('admin.distributor-enquiries.destroy', $distributorEnquiry->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this enquiry?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger">
                            <i class="ti ti-trash me-1"></i> Delete Enquiry
                        </button>
                    </form>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
