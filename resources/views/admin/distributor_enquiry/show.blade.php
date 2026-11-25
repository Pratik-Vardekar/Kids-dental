@extends('layouts.admin')
@section('content')

<div class="container-fluid my-2">

    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="mb-1">Distributor Enquiry Details</h4>
            <p class="text-muted mb-0">Submitted application details from {{ $distributorEnquiry->company_name }}</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.distributor-enquiries.edit', $distributorEnquiry->id) }}" class="btn btn-primary">
                <i class="ti ti-edit me-1"></i> Edit
            </a>
            <a href="{{ route('admin.distributor-enquiries.index') }}" class="btn btn-secondary">
                <i class="ti ti-arrow-left me-1"></i> Back to List
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Enquiry Information</h5>
                    <span>
                        @if($distributorEnquiry->status)
                            <span class="badge bg-success">Read / Contacted</span>
                        @else
                            <span class="badge bg-warning text-dark">Unread / Pending</span>
                        @endif
                    </span>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-borderless">
                            <tbody>
                                <tr>
                                    <th style="width: 200px;" class="text-muted">Company Name:</th>
                                    <td><strong class="fs-6">{{ $distributorEnquiry->company_name }}</strong></td>
                                </tr>
                                <tr>
                                    <th class="text-muted">Contact Person:</th>
                                    <td>{{ $distributorEnquiry->contact_person }}</td>
                                </tr>
                                <tr>
                                    <th class="text-muted">Email:</th>
                                    <td>
                                        <a href="mailto:{{ $distributorEnquiry->email }}" class="text-decoration-underline">
                                            {{ $distributorEnquiry->email }}
                                        </a>
                                    </td>
                                </tr>
                                <tr>
                                    <th class="text-muted">Mobile No.:</th>
                                    <td>
                                        <a href="tel:{{ $distributorEnquiry->mobile_no }}">
                                            {{ $distributorEnquiry->mobile_no }}
                                        </a>
                                    </td>
                                </tr>
                                <tr>
                                    <th class="text-muted">Country / City:</th>
                                    <td>{{ $distributorEnquiry->country_city }}</td>
                                </tr>
                                <tr>
                                    <th class="text-muted">Submitted Date:</th>
                                    <td>{{ $distributorEnquiry->created_at ? $distributorEnquiry->created_at->format('d M, Y \a\t h:i A') : '-' }}</td>
                                </tr>
                                <tr>
                                    <th class="text-muted">Last Updated:</th>
                                    <td>{{ $distributorEnquiry->updated_at ? $distributorEnquiry->updated_at->format('d M, Y \a\t h:i A') : '-' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        <h6 class="text-muted fw-bold mb-2">Message:</h6>
                        <div class="p-3 bg-light rounded border" style="white-space: pre-wrap; min-height: 100px;">
                            {{ $distributorEnquiry->message ?: 'No additional message provided.' }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">Quick Actions</h5>
                </div>
                <div class="card-body d-flex flex-column gap-2">
                    <a href="mailto:{{ $distributorEnquiry->email }}?subject=Regarding your Become a Distributor Enquiry" class="btn btn-outline-primary">
                        <i class="ti ti-mail me-1"></i> Send Email
                    </a>
                    <a href="tel:{{ $distributorEnquiry->mobile_no }}" class="btn btn-outline-success">
                        <i class="ti ti-phone me-1"></i> Call Contact
                    </a>
                    <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $distributorEnquiry->mobile_no) }}" target="_blank" class="btn btn-outline-info">
                        <i class="ti ti-brand-whatsapp me-1"></i> WhatsApp Message
                    </a>

                    <hr class="my-2">

                    <form action="{{ route('admin.distributor-enquiries.toggle-status', $distributorEnquiry->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-outline-secondary w-100">
                            <i class="ti ti-toggle-left me-1"></i> Mark as {{ $distributorEnquiry->status ? 'Unread' : 'Read' }}
                        </button>
                    </form>

                    <form action="{{ route('admin.distributor-enquiries.destroy', $distributorEnquiry->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this enquiry?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger w-100">
                            <i class="ti ti-trash me-1"></i> Delete Enquiry
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
