@extends('layouts.admin')

@section('content')

<div class="container-fluid px-4 py-3">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-1">Order Details</h4>
            <span class="badge rounded-pill bg-light text-secondary border fw-normal px-3 py-2">
                <i class="ti ti-hash me-1"></i>{{ $order->order_id }}
            </span>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('order.print', $order->id) }}" target="_blank"
               class="btn btn-dark btn-sm px-3 d-flex align-items-center gap-1">
                <i class="ti ti-printer"></i> Print
            </a>
            <a href="{{ route('order.index') }}"
               class="btn btn-outline-secondary btn-sm px-3 d-flex align-items-center gap-1">
                <i class="ti ti-arrow-left"></i> Back
            </a>
        </div>
    </div>

    {{-- Row 1: Customer | Payment & Status | Order Summary --}}
    <div class="row g-3 mb-3">

        {{-- Customer Details --}}
        <div class="col-md-4">
            <div class="card border rounded-3 h-100">
                <div class="card-header bg-white border-bottom d-flex align-items-center gap-2 py-3">
                    <span class="bg-primary bg-opacity-10 text-primary rounded-2 p-1 lh-1">
                        <i class="ti ti-user fs-5"></i>
                    </span>
                    <h6 class="fw-bold mb-0">Customer Details</h6>
                </div>
                <div class="card-body py-3 px-3">
                    <div class="d-flex align-items-center py-2 border-bottom">
                        <span class="text-muted text-uppercase fw-semibold flex-shrink-0"
                              style="width:65px;font-size:.68rem;letter-spacing:.4px">Name</span>
                        <span class="fw-semibold text-dark small ms-2">{{ $order->user->name ?? '—' }}</span>
                    </div>
                    <div class="d-flex align-items-center py-2 border-bottom">
                        <span class="text-muted text-uppercase fw-semibold flex-shrink-0"
                              style="width:65px;font-size:.68rem;letter-spacing:.4px">Email</span>
                        <span class="fw-semibold text-dark small ms-2 text-truncate">{{ $order->user->email ?? '—' }}</span>
                    </div>
                    <div class="d-flex align-items-center py-2">
                        <span class="text-muted text-uppercase fw-semibold flex-shrink-0"
                              style="width:65px;font-size:.68rem;letter-spacing:.4px">Phone</span>
                        <span class="fw-semibold text-dark small ms-2">{{ $order->user->phone ?? '—' }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Payment & Order Status --}}
        <div class="col-md-4">
            <div class="card border rounded-3 h-100">
                <div class="card-header bg-white border-bottom d-flex align-items-center gap-2 py-3">
                    <span class="bg-success bg-opacity-10 text-success rounded-2 p-1 lh-1">
                        <i class="ti ti-credit-card fs-5"></i>
                    </span>
                    <h6 class="fw-bold mb-0">Payment & Status</h6>
                </div>
                <div class="card-body py-3 px-3">

                    <div class="mb-3">
                        <p class="text-muted fw-semibold mb-1"
                           style="font-size:.68rem;text-transform:uppercase;letter-spacing:.5px">Payment Method</p>
                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary
                                     border-opacity-25 rounded-pill px-3 py-2 fw-semibold">
                            <i class="ti ti-wallet me-1"></i>{{ ucfirst($order->payment_method ?? '—') }}
                        </span>
                    </div>

                    <div class="mb-3">
                        <p class="text-muted fw-semibold mb-1"
                           style="font-size:.68rem;text-transform:uppercase;letter-spacing:.5px">Payment Status</p>
                        <form action="{{ route('order.payment.status', $order->id) }}" method="POST">
                            @csrf @method('PUT')
                            <div class="input-group input-group-sm">
                                <select name="payment_status" class="form-select">
                                    @foreach(['pending','paid','partial','failed'] as $s)
                                        <option value="{{ $s }}" {{ $order->payment_status == $s ? 'selected' : '' }}>
                                            {{ ucfirst($s) }}
                                        </option>
                                    @endforeach
                                </select>
                                <button class="btn btn-success fw-semibold">Update</button>
                            </div>
                        </form>
                    </div>

                    <div>
                        <p class="text-muted fw-semibold mb-1"
                           style="font-size:.68rem;text-transform:uppercase;letter-spacing:.5px">Order Status</p>
                        <form action="{{ route('order.status', $order->id) }}" method="POST">
                            @csrf @method('PUT')
                            <div class="input-group input-group-sm">
                                <select name="order_status" class="form-select">
                                    @foreach(['pending','processing','confirmed','packed','shipped',
                                              'out_for_delivery','delivered','cancelled'] as $s)
                                        <option value="{{ $s }}" {{ $order->order_status == $s ? 'selected' : '' }}>
                                            {{ ucfirst(str_replace('_',' ',$s)) }}
                                        </option>
                                    @endforeach
                                </select>
                                <button class="btn btn-primary fw-semibold">Update</button>
                            </div>
                        </form>
                    </div>

                </div>
            </div>
        </div>

        {{-- Order Summary --}}
        <div class="col-md-4">
            <div class="card border rounded-3 h-100">
                <div class="card-header bg-white border-bottom d-flex align-items-center gap-2 py-3">
                    <span class="bg-warning bg-opacity-10 text-warning rounded-2 p-1 lh-1">
                        <i class="ti ti-receipt fs-5"></i>
                    </span>
                    <h6 class="fw-bold mb-0">Order Summary</h6>
                </div>
                <div class="card-body py-3 px-3">
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <span class="text-muted small">Subtotal</span>
                        <span class="small fw-semibold">₹{{ number_format($order->subtotal, 2) }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <span class="text-muted small">Discount</span>
                        <span class="small fw-semibold text-danger">− ₹{{ number_format($order->discount, 2) }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <span class="text-muted small">Shipping</span>
                        <span class="small fw-semibold">₹{{ number_format($order->shipping_charge, 2) }}</span>
                    </div>
                   @php
    $gstAmount = $order->cgst_amount + $order->sgst_amount + $order->igst_amount;
@endphp

@if($order->gst_rate > 0)
<div class="d-flex justify-content-between align-items-center py-2 border-bottom">
    <span class="text-muted small">
        GST ({{ $order->gst_rate }}%)
    </span>

    <span class="small fw-semibold">
        ₹{{ number_format($gstAmount, 2) }}
    </span>
</div>
@endif
                    <div class="d-flex justify-content-between align-items-center pt-3">
                        <span class="fw-bold">Grand Total</span>
                        <span class="fs-5 fw-bold text-success">₹{{ number_format($order->grand_total, 2) }}</span>
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- Row 2: Add Payment + Payment History --}}
    <div class="row g-3 mb-3">

        @if($order->payment_status !== 'paid' && $order->due_amount > 0)
        <div class="col-md-4">
            <div class="card border rounded-3 h-100">
                <div class="card-header bg-white border-bottom d-flex align-items-center gap-2 py-3">
                    <span class="bg-info bg-opacity-10 text-info rounded-2 p-1 lh-1">
                        <i class="ti ti-plus fs-5"></i>
                    </span>
                    <h6 class="fw-bold mb-0">Add Payment</h6>
                </div>
                <div class="card-body px-3">

                    <div class="alert alert-warning d-flex align-items-center gap-2 py-2 px-3 mb-3 rounded-3">
                        <i class="ti ti-alert-circle flex-shrink-0"></i>
                        <span class="small fw-semibold">
                            Due Amount: <span class="text-dark">₹{{ number_format($order->due_amount, 2) }}</span>
                        </span>
                    </div>

                    <form method="POST" action="{{ route('order.payment.store', $order->id) }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label text-muted fw-semibold"
                                   style="font-size:.68rem;text-transform:uppercase;letter-spacing:.5px">Amount</label>
                            <input type="number" name="amount" class="form-control form-control-sm"
                                   max="{{ $order->due_amount }}"
                                   placeholder="Max ₹{{ $order->due_amount }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted fw-semibold"
                                   style="font-size:.68rem;text-transform:uppercase;letter-spacing:.5px">Payment Method</label>
                            <select name="payment_method" class="form-select form-select-sm" required>
                                <option value="cash">Cash</option>
                                <option value="upi">UPI</option>
                                <option value="bank_transfer">Bank Transfer</option>
                                <option value="cod">COD</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted fw-semibold"
                                   style="font-size:.68rem;text-transform:uppercase;letter-spacing:.5px">Transaction ID</label>
                            <input type="text" name="transaction_id" class="form-control form-control-sm"
                                   placeholder="Optional">
                        </div>
                        <button class="btn btn-success btn-sm w-100 fw-semibold">
                            <i class="ti ti-check me-1"></i> Add Payment
                        </button>
                    </form>
                </div>
            </div>
        </div>
        @endif

        {{-- Payment History --}}
        <div class="{{ ($order->payment_status !== 'paid' && $order->due_amount > 0) ? 'col-md-8' : 'col-md-12' }}">
            <div class="card border rounded-3 h-100">
                <div class="card-header bg-white border-bottom d-flex align-items-center gap-2 py-3">
                    <span class="bg-secondary bg-opacity-10 text-secondary rounded-2 p-1 lh-1">
                        <i class="ti ti-history fs-5"></i>
                    </span>
                    <h6 class="fw-bold mb-0">Payment History</h6>
                    <span class="badge bg-secondary bg-opacity-10 text-secondary rounded-pill ms-auto fw-semibold">
                        {{ $order->orderPayments->count() }} records
                    </span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3 text-muted fw-semibold" style="font-size:.72rem">#</th>
                                    <th class="text-muted fw-semibold" style="font-size:.72rem">Amount</th>
                                    <th class="text-muted fw-semibold" style="font-size:.72rem">Method</th>
                                    <th class="text-muted fw-semibold" style="font-size:.72rem">Transaction ID</th>
                                    <th class="text-muted fw-semibold" style="font-size:.72rem">Status</th>
                                    <th class="text-muted fw-semibold" style="font-size:.72rem">Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($order->orderPayments as $payment)
                                    @php
                                        $badgeClass = match($payment->status) {
                                            'paid'    => 'bg-success-subtle text-success',
                                            'pending' => 'bg-warning-subtle text-warning',
                                            'failed'  => 'bg-danger-subtle text-danger',
                                            default   => 'bg-secondary-subtle text-secondary',
                                        };
                                    @endphp
                                    <tr>
                                        <td class="ps-3 text-muted small">{{ $loop->iteration }}</td>
                                        <td class="fw-bold text-dark small">₹{{ number_format($payment->amount, 2) }}</td>
                                        <td>
                                            <span class="badge bg-light text-dark border rounded-pill px-2 fw-semibold small">
                                                {{ ucfirst($payment->payment_method) }}
                                            </span>
                                        </td>
                                        <td class="text-muted small font-monospace">{{ $payment->transaction_id ?? '—' }}</td>
                                        <td>
                                            <span class="badge rounded-pill {{ $badgeClass }} fw-semibold px-3 small">
                                                {{ ucfirst($payment->status) }}
                                            </span>
                                        </td>
                                        <td class="text-muted small">{{ $payment->created_at->format('d M Y') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-5">
                                            <i class="ti ti-inbox d-block fs-2 mb-2 opacity-25"></i>
                                            <span class="small">No payments recorded yet</span>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- Row 3: Ordered Products + Refund Management --}}
    <div class="row g-3 mb-3">

        {{-- Ordered Products --}}
        <div class="col-md-8">
            <div class="card border rounded-3">
                <div class="card-header bg-white border-bottom d-flex align-items-center gap-2 py-3">
                    <span class="bg-primary bg-opacity-10 text-primary rounded-2 p-1 lh-1">
                        <i class="ti ti-shopping-bag fs-5"></i>
                    </span>
                    <h6 class="fw-bold mb-0">Ordered Products</h6>
                    <span class="badge bg-primary rounded-pill ms-auto">
                        {{ $order->items->count() }} items
                    </span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3 text-muted fw-semibold" style="width:50px;font-size:.72rem">#</th>
                                    <th class="text-muted fw-semibold" style="font-size:.72rem">Product</th>
                                    <th class="text-muted fw-semibold" style="font-size:.72rem">Price</th>
                                    <th class="text-muted fw-semibold" style="font-size:.72rem">Qty</th>
                                    <th class="text-muted fw-semibold" style="font-size:.72rem">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($order->items as $item)
                                    <tr>
                                        <td class="ps-3 text-muted small">{{ $loop->iteration }}</td>
                                        <td>
                                            <div class="d-flex align-items-center gap-3">
                                                @php
                                                    $product = $item->product;
                                                    $image = null;
                                                    if ($product) {
                                                        $image = $product->thumbnail ?? $product->image ?? null;
                                                        if (!$image && $product->gallery) {
                                                            $gallery = is_array($product->gallery)
                                                                ? $product->gallery
                                                                : json_decode($product->gallery, true);
                                                            $image = $gallery[0] ?? null;
                                                        }
                                                    }
                                                @endphp
                                                @if($image)
                                                    <img src="{{ asset('backend/assets/images/product/'.trim($image)) }}"
                                                         class="rounded-3 border flex-shrink-0"
                                                         style="width:44px;height:44px;object-fit:cover">
                                                @else
                                                    <div class="bg-light rounded-3 border d-flex align-items-center
                                                                justify-content-center text-muted flex-shrink-0"
                                                         style="width:44px;height:44px">
                                                        <i class="ti ti-photo"></i>
                                                    </div>
                                                @endif
                                                <span class="fw-semibold text-dark small">
                                                    {{ $item->product->name ?? '—' }}
                                                </span>
                                            </div>
                                        </td>
                                        <td class="text-muted small">₹{{ number_format($item->price, 2) }}</td>
                                        <td>
                                            <span class="badge bg-light text-dark border rounded-pill px-3 fw-bold small">
                                                × {{ $item->quantity }}
                                            </span>
                                        </td>
                                        <td class="fw-bold text-dark small">₹{{ number_format($item->total, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-5">
                                            <i class="ti ti-inbox d-block fs-2 mb-2 opacity-25"></i>
                                            <span class="small">No items found</span>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- Refund Management --}}
        <div class="col-md-4">
            <div class="card border rounded-3 h-100">
                <div class="card-header bg-white border-bottom d-flex align-items-center gap-2 py-3">
                    <span class="bg-danger bg-opacity-10 text-danger rounded-2 p-1 lh-1">
                        <i class="ti ti-arrow-back-up fs-5"></i>
                    </span>
                    <h6 class="fw-bold mb-0">Refund Management</h6>
                    @if($order->refunds->count())
                        <span class="badge bg-danger bg-opacity-10 text-danger rounded-pill ms-auto fw-semibold">
                            {{ $order->refunds->count() }}
                        </span>
                    @endif
                </div>
                <div class="card-body px-3 py-3">

                    {{-- Create Refund Form --}}
                    <form method="POST" action="{{ route('refund.store', $order->id) }}">
                        @csrf
                        <div class="mb-2">
                            <label class="form-label text-muted fw-semibold"
                                   style="font-size:.68rem;text-transform:uppercase;letter-spacing:.5px">Refund Amount</label>
                            <input type="number" name="amount" class="form-control form-control-sm"
                                   placeholder="Enter amount">
                        </div>
                        <div class="mb-2">
                            <label class="form-label text-muted fw-semibold"
                                   style="font-size:.68rem;text-transform:uppercase;letter-spacing:.5px">Reason</label>
                            <textarea name="reason" class="form-control form-control-sm" rows="2"
                                      placeholder="Optional reason"></textarea>
                        </div>
                        <button class="btn btn-danger btn-sm w-100 fw-semibold mb-3">
                            <i class="ti ti-plus me-1"></i> Create Refund Request
                        </button>
                    </form>

                    {{-- Refund List --}}
                    @if($order->refunds->count())
                        <p class="text-muted fw-semibold mb-2"
                           style="font-size:.68rem;text-transform:uppercase;letter-spacing:.5px">Existing Refunds</p>

                        <div class="d-flex flex-column gap-2">
                            @foreach($order->refunds as $refund)
                                @php
                                    $refundBadge = match($refund->status) {
                                        'approved'  => 'bg-success-subtle text-success',
                                        'rejected'  => 'bg-danger-subtle text-danger',
                                        'completed' => 'bg-primary-subtle text-primary',
                                        default     => 'bg-warning-subtle text-warning',
                                    };
                                @endphp
                                <div class="border rounded-3 p-2">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="fw-bold text-dark small">
                                            ₹{{ number_format($refund->amount, 2) }}
                                        </span>
                                        <span class="badge rounded-pill {{ $refundBadge }} fw-semibold small px-2">
                                            {{ ucfirst($refund->status) }}
                                        </span>
                                    </div>
                                    @if($refund->reason)
                                        <p class="text-muted mb-2" style="font-size:.78rem">{{ $refund->reason }}</p>
                                    @endif
                                    <div class="d-flex gap-1">
                                        <a href="{{ route('refund.approve', $refund->id) }}"
                                           class="btn btn-success btn-sm flex-fill fw-semibold"
                                           style="font-size:.75rem">
                                            <i class="ti ti-check me-1"></i>Approve
                                        </a>
                                        <a href="{{ route('refund.reject', $refund->id) }}"
                                           class="btn btn-warning btn-sm flex-fill fw-semibold"
                                           style="font-size:.75rem">
                                            <i class="ti ti-x me-1"></i>Reject
                                        </a>
                                        <a href="{{ route('refund.complete', $refund->id) }}"
                                           class="btn btn-primary btn-sm flex-fill fw-semibold"
                                           style="font-size:.75rem">
                                            <i class="ti ti-circle-check me-1"></i>Done
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center text-muted py-3">
                            <i class="ti ti-rosette-discount-off d-block fs-2 mb-2 opacity-25"></i>
                            <span class="small">No refunds yet</span>
                        </div>
                    @endif

                </div>
            </div>
        </div>

    </div>

</div>

@endsection