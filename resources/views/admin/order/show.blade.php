@extends('layouts.admin')

@section('content')

<div class="container-fluid px-4 py-4" style="max-width:1100px">

    {{-- Header --}}
    <div class="mb-4">
        <a href="{{ route('order.index') }}" class="btn btn-sm btn-light border d-inline-flex align-items-center gap-2 mb-3">
            <i class="ti ti-arrow-left"></i> Back to Orders
        </a>

        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
            <div>
                <p class="text-muted mb-1" style="font-size:13px">Order Details</p>
                <h3 class="fw-bold mb-0">Order #{{ $order->display_order_id }}</h3>
                <p class="text-muted mb-0 mt-1" style="font-size:13px">
                    Placed on {{ $order->created_at->format('d M Y') }} at {{ $order->created_at->format('h:i A') }}
                </p>
            </div>
            <div class="d-flex flex-wrap gap-2 mt-2 mt-md-0">
                <a href="{{ route('order.pdf', $order->id) }}" class="btn btn-outline-secondary d-flex align-items-center gap-2">
                    <i class="ti ti-download"></i> Download Invoice
                </a>
            </div>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @include('admin.order.partials.customer-requests', ['order' => $order])

    <div class="row g-3 mb-3">
        {{-- Column 1 --}}
        <div class="col-lg-4 d-flex flex-column gap-3">
            {{-- Delivery Address --}}
            @php
            $shippingAddress = $order->addresses
            ->where('type', 'shipping')
            ->first();
            @endphp
            @if($shippingAddress)
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom py-3 px-4">
                    <h6 class="fw-semibold mb-0 d-flex align-items-center gap-2">
                        <i class="ti ti-map-pin text-muted"></i> Delivery Address
                    </h6>
                </div>
                <div class="card-body px-4 py-3">
                    <p class="fw-semibold mb-1">{{ $shippingAddress->name }}</p>
                    <p class="text-muted mb-1" style="font-size:13px">{{ $shippingAddress->phone }}</p>
                    <p class="text-muted mb-1" style="font-size:13px">{{ $shippingAddress->address }}</p>
                    @if($shippingAddress->landmark)
                    <p class="text-muted mb-1" style="font-size:13px">{{ $shippingAddress->landmark }}</p>
                    @endif
                    <p class="text-muted mb-0" style="font-size:13px">
                        {{ $shippingAddress->city }}, {{ $shippingAddress->state }} — {{ $shippingAddress->pincode }}
                    </p>
                </div>
            </div>
            @endif
            {{-- Payment Method & Status --}}
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom py-3 px-4">
                    <h6 class="fw-semibold mb-0 d-flex align-items-center gap-2">
                        <i class="ti ti-credit-card text-muted"></i> Payment &amp; Status
                    </h6>
                </div>
                <div class="card-body px-4 py-3">
                    <div class="mb-2">
                        <span class="text-muted" style="font-size:12px;">Method:</span>
                        <span class="badge bg-secondary" style="font-size:12px;padding:5px 10px">
                            {{ strtoupper($order->payment_method ?? '-') }}
                        </span>
                    </div>
                    <div class="mb-2">
                        <span class="text-muted" style="font-size:12px;">Order Status:</span>
                        <form action="{{ route('order.status', $order->id) }}" method="POST" class="d-inline">
                            @csrf
                            @method('PUT')
                            <select name="order_status"
                                class="form-select form-select-sm d-inline w-auto"
                                style="min-width:120px"
                                onchange="this.form.submit()">
                                <option value="pending" {{ $order->order_status == 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="confirmed" {{ $order->order_status == 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                                <option value="processing" {{ $order->order_status == 'processing' ? 'selected' : '' }}>Processing</option>
                                <option value="shipped" {{ $order->order_status == 'shipped' ? 'selected' : '' }}>Shipped</option>
                                <option value="delivered" {{ $order->order_status == 'delivered' ? 'selected' : '' }}>Delivered</option>
                                <option value="returns" {{ $order->order_status == 'returns' ? 'selected' : '' }}>Returns</option>
                                <option value="cancelled" {{ $order->order_status == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                            </select>
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" name="send_email" value="1" id="sendEmailCheckbox" checked>
                                <label class="form-check-label text-muted" style="font-size:12px;" for="sendEmailCheckbox">
                                    Send email notification to user & admin
                                </label>
                            </div>
                        </form>
                    </div>
                    <!-- <div>
                        <span class="text-muted" style="font-size:12px;">Payment Status:</span>
                        <form action="{{ route('order.payment.status', $order->id) }}" method="POST" class="d-inline">
                            @csrf
                            @method('PUT')
                            <select name="payment_status"
                                    class="form-select form-select-sm d-inline w-auto"
                                    style="min-width:120px"
                                    onchange="this.form.submit()">
                                <option value="pending"
                                    {{ ! $orderSummary['is_fully_paid'] && $order->payment_status !== 'partial' && $order->display_payment_status !== 'Processing' ? 'selected' : '' }}>
                                    Pending
                                </option>
                                <option value="processing"
                                    {{ $order->display_payment_status === 'Processing' ? 'selected' : '' }}>
                                    Processing
                                </option>
                                <option value="partial"
                                    {{ $order->payment_status === 'partial' ? 'selected' : '' }}>
                                    Partial
                                </option>
                                <option value="paid"
                                    {{ $orderSummary['is_fully_paid'] || $order->payment_status === 'paid' ? 'selected' : '' }}>
                                    Paid
                                </option>
                            </select>
                        </form>
                    </div> -->
                </div>
            </div>

        </div>

        {{-- Column 2 --}}
        <div class="col-lg-8 d-flex flex-column gap-3">

            {{-- Ordered Products --}}
            <div class="card border-0 shadow-sm h-100 order-products-card">
                <div class="card-header bg-white border-bottom py-3 px-4 d-flex align-items-center justify-content-between">
                    <h6 class="fw-semibold mb-0 d-flex align-items-center gap-2">
                        <i class="ti ti-shopping-bag text-muted"></i> Ordered Products
                    </h6>
                    <span class="badge bg-light text-muted" style="font-size:12px">
                        {{ $order->items->count() }} item(s)
                    </span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0 order-products-table">
                        <thead class="table-light">
                            <tr>
                                <th class="px-4 py-3 order-col-product" colspan="2">Product</th>
                                <th class="text-center py-3 order-col-qty">Qty</th>
                                <th class="text-end py-3 order-col-price">Price</th>
                                <th class="text-end px-4 py-3 order-col-total">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($order->items as $item)
                            <tr>
                                <td class="ps-4 py-3 order-col-image">
                                    @php $img = $item->product->thumbnail ?? null; @endphp
                                    @if($img)
                                    <img src="{{ asset('backend/assets/images/product/' . $img) }}"
                                        alt="{{ $item->product->name }}"
                                        class="order-product-thumb">
                                    @else
                                    <div class="order-product-thumb order-product-thumb--empty">
                                        <i class="ti ti-photo"></i>
                                    </div>
                                    @endif
                                </td>
                                <td class="py-3 fw-semibold order-col-name">
                                    {{ $item->product->name ?? ($item->product_name ?? '-') }}
                                    @php
                                        $archVal = $item->arch ?? ($item->variant->arch ?? null);
                                        $sizeVal = $item->size ?? ($item->variant->size ?? null);
                                        $skuVal = $item->product_sku ?? ($item->variant->sku ?? ($item->product->sku ?? null));
                                    @endphp
                                    @if($archVal || $sizeVal)
                                    <br><span class="badge bg-primary-subtle text-primary border border-primary-subtle py-0 px-2 mt-1">
                                        {{ $archVal }} {{ $sizeVal ? 'Size ' . $sizeVal : '' }}
                                    </span>
                                    @endif
                                    @if($skuVal)
                                    <br><span class="text-muted fw-normal order-product-sku">SKU: {{ $skuVal }}</span>
                                    @endif
                                </td>
                                <td class="text-center py-3 order-col-qty">{{ $item->quantity }}</td>
                                <td class="text-end py-3 text-muted order-col-price">₹{{ number_format($item->price, 2) }}</td>
                                <td class="text-end px-4 py-3 fw-semibold order-col-total">₹{{ number_format($item->total, 2) }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">
                                    <i class="ti ti-inbox d-block mb-2" style="font-size:28px;opacity:.4"></i>
                                    No products found
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                        @if($order->items->count() > 0)
                        <tfoot class="order-summary-foot">
                            <tr>
                                <td colspan="4" class="text-end px-4 py-2 order-summary-label">Subtotal</td>
                                <td class="text-end px-4 py-2 fw-semibold order-summary-value">₹{{ number_format($orderSummary['items_subtotal'], 2) }}</td>
                            </tr>
                            @if($orderSummary['discount_amount'] > 0)
                            <tr>
                                <td colspan="4" class="text-end px-4 py-2 order-summary-label">Discount</td>
                                <td class="text-end px-4 py-2 order-summary-value text-danger">-₹{{ number_format($orderSummary['discount_amount'], 2) }}</td>
                            </tr>
                            @endif
                            @if($orderSummary['shipping_charge'] > 0)
                            <tr>
                                <td colspan="4" class="text-end px-4 py-2 order-summary-label">Shipping Charge</td>
                                <td class="text-end px-4 py-2 order-summary-value">₹{{ number_format($orderSummary['shipping_charge'], 2) }}</td>
                            </tr>
                            @endif
                            <tr>
                                <td colspan="4" class="text-end px-4 py-2 order-summary-label">{{ $orderSummary['gst_rate_label'] ?? 'GST (inclusive)' }}</td>
                                <td class="text-end px-4 py-2 order-summary-value">₹{{ number_format($orderSummary['gst_amount'], 2) }}</td>
                            </tr>
                            <tr class="order-summary-grand">
                                <td colspan="4" class="text-end px-4 py-3 order-summary-label fw-bold">Grand Total</td>
                                <td class="text-end px-4 py-3 order-summary-grand-value">₹{{ number_format($orderSummary['grand_total'], 2) }}</td>
                            </tr>
                            @if($orderSummary['total_paid'] > 0)
                            <tr class="{{ $orderSummary['is_fully_paid'] ? 'order-summary-paid' : '' }}">
                                <td colspan="4" class="text-end px-4 py-2 order-summary-label {{ $orderSummary['is_fully_paid'] ? 'text-success fw-semibold' : 'text-success' }}">
                                    Total Paid
                                </td>
                                <td class="text-end px-4 py-2 fw-semibold text-success">₹{{ number_format($orderSummary['total_paid'], 2) }}</td>
                            </tr>
                            @endif
                        </tfoot>
                        @endif
                    </table>
                </div>
            </div>
            {{-- Payment Summary --}}
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom py-3 px-4">
                    <h6 class="fw-semibold mb-0 d-flex align-items-center gap-2">
                        <i class="ti ti-currency-rupee text-muted"></i> Payment Details
                    </h6>
                </div>
                <div class="card-body px-4 py-3">
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <div class="bg-light rounded-3 p-3 text-center h-100">
                                <p class="text-muted mb-1" style="font-size:11px;text-transform:uppercase;letter-spacing:.5px">
                                    Grand Total
                                </p>
                                <p class="fw-semibold mb-0" style="font-size:17px">
                                    ₹{{ number_format($orderSummary['grand_total'], 2) }}
                                </p>
                            </div>
                        </div>
                        <!-- <div class="col-6">
                            <div class="rounded-3 p-3 text-center h-100 {{ $orderSummary['is_fully_paid'] ? 'bg-success-subtle border border-success' : 'bg-light' }}">
                                <p class="text-muted mb-1" style="font-size:11px;text-transform:uppercase;letter-spacing:.5px">
                                    Paid
                                </p>
                                <p class="fw-semibold mb-0 {{ $orderSummary['is_fully_paid'] ? 'text-success' : '' }}" style="font-size:17px">
                                    ₹{{ number_format($orderSummary['total_paid'], 2) }}
                                </p>
                            </div>
                        </div> -->
                    </div>

                    <!-- @if($orderSummary['is_fully_paid'])
                    <div class="alert alert-success d-flex align-items-center gap-2 mb-0 py-2 px-3">
                        <i class="ti ti-circle-check fs-5"></i>
                        <span>Payment collected. This COD order is fully paid.</span>
                    </div>
                    @elseif(($collectDue = max(0, (float) ($order->due_amount ?? ($orderSummary['grand_total'] - $orderSummary['total_paid'])))) > 0)
                    <div class="p-3 border rounded bg-light">
                        <h6 class="mb-2">Collect Payment</h6>
                        <form action="{{ route('order.remaining.payment', $order->id) }}" method="POST">
                            @csrf
                            <div class="row g-2 align-items-center">
                                <div class="col-md-9">
                                    <input type="number"
                                        step="0.01"
                                        min="1"
                                        max="{{ $collectDue }}"
                                        value="{{ $collectDue }}"
                                        name="amount"
                                        class="form-control"
                                        placeholder="Enter received amount"
                                        required>
                                </div>
                                <div class="col-md-3 d-grid">
                                    <button type="submit" class="btn btn-success">
                                        Mark Paid
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                    @endif -->
                </div>
            </div>
        </div>

    </div>

    {{-- Transaction History --}}
    @if($order->orderPayments->count())
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom py-3 px-4 d-flex align-items-center justify-content-between">
            <h6 class="fw-semibold mb-0 d-flex align-items-center gap-2">
                <i class="ti ti-history text-muted"></i> Transaction History
            </h6>
            <span class="badge bg-light text-muted" style="font-size:12px">
                {{ $order->orderPayments->count() }} transaction(s)
            </span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="px-4 py-3" style="font-size:12px;text-transform:uppercase;letter-spacing:.5px;font-weight:600;color:#6b7280">Amount</th>
                        <th class="py-3" style="font-size:12px;text-transform:uppercase;letter-spacing:.5px;font-weight:600;color:#6b7280">Method</th>
                        <th class="py-3" style="font-size:12px;text-transform:uppercase;letter-spacing:.5px;font-weight:600;color:#6b7280">Stage</th>
                        <th class="py-3" style="font-size:12px;text-transform:uppercase;letter-spacing:.5px;font-weight:600;color:#6b7280">Status</th>
                        <th class="text-end px-4 py-3" style="font-size:12px;text-transform:uppercase;letter-spacing:.5px;font-weight:600;color:#6b7280">Date</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($order->orderPayments as $payment)
                    <tr>
                        <td class="px-4 py-3 fw-semibold">₹{{ number_format($payment->amount, 2) }}</td>
                        <td class="py-3">
                            <span class="badge bg-light text-dark border" style="font-size:11px;font-weight:600;letter-spacing:.3px">
                                {{ strtoupper($payment->payment_method ?? '-') }}
                            </span>
                        </td>
                        <td class="py-3 text-muted">{{ ucfirst($payment->payment_stage ?? '-') }}</td>
                        <td class="py-3">
                            @if($payment->status == 'success')
                            <span class="badge bg-success" style="font-size:12px;padding:5px 12px">Success</span>
                            @elseif($payment->status == 'failed')
                            <span class="badge bg-danger" style="font-size:12px;padding:5px 12px">Failed</span>
                            @else
                            <span class="badge bg-warning text-dark" style="font-size:12px;padding:5px 12px">Pending</span>
                            @endif
                        </td>
                        <td class="text-end px-4 py-3 text-muted" style="font-size:13px">
                            {{ $payment->created_at->format('d M Y') }}<br>
                            <span style="font-size:12px">{{ $payment->created_at->format('h:i A') }}</span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- Invoice Preview --}}
    <div class="collapse" id="orderInvoiceSection">
        <div class="card border-0 shadow-sm mb-4" id="order-invoice-preview">
            <div class="card-header bg-white border-bottom py-3 px-4 d-flex align-items-center justify-content-between">
                <h6 class="fw-semibold mb-0 d-flex align-items-center gap-2">
                    <i class="ti ti-file-invoice text-muted"></i> Invoice
                </h6>
                <span class="text-muted" style="font-size:12px">
                    Invoice #{{ $order->display_invoice_no }}
                </span>
            </div>
            <div class="card-body px-4 py-4 invoice-preview-body">
                @include('admin.order.partials.invoice-content')
            </div>
        </div>
    </div>

</div>

@endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<style>
    .card {
        border-radius: 12px;
    }

    .card-header {
        border-radius: 12px 12px 0 0 !important;
    }

    .table-hover tbody tr:hover {
        background: #f8fafc;
    }

    .order-products-table thead th {
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: .5px;
        font-weight: 600;
        color: #6b7280;
        white-space: nowrap;
    }

    .order-products-table .order-col-image {
        width: 72px;
    }

    .order-products-table .order-col-qty {
        width: 72px;
    }

    .order-products-table .order-col-price {
        width: 120px;
        white-space: nowrap;
    }

    .order-products-table .order-col-total {
        width: 130px;
        white-space: nowrap;
    }

    .order-product-thumb {
        width: 48px;
        height: 48px;
        object-fit: cover;
        border-radius: 8px;
        border: 1px solid #e5e7eb;
        display: block;
    }

    .order-product-thumb--empty {
        background: #f3f4f6;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #9ca3af;
        font-size: 18px;
    }

    .order-product-sku {
        font-size: 11px;
    }

    .order-col-name {
        font-size: 14px;
        min-width: 180px;
    }

    .order-summary-foot {
        background: #f8fafc;
        border-top: 1px solid #e5e7eb;
    }

    .order-summary-foot tr:first-child td {
        padding-top: 1rem !important;
    }

    .order-summary-foot tr:last-child td {
        padding-bottom: 1rem !important;
    }

    .order-summary-label {
        font-size: 13px;
        color: #6b7280;
        vertical-align: middle;
    }

    .order-summary-value {
        font-size: 13px;
        color: #111827;
        vertical-align: middle;
        white-space: nowrap;
    }

    .order-summary-grand td {
        border-top: 1px solid #dbeafe;
        background: #eff6ff;
    }

    .order-summary-grand-value {
        font-size: 16px;
        font-weight: 700;
        color: #2563eb;
        white-space: nowrap;
    }

    .order-summary-paid td {
        background: #ecfdf5;
    }

    .bg-success-subtle {
        background-color: #d1fae5 !important;
    }

    .invoice-preview-body {
        font-size: 13px;
        color: #111;
        background: #fff;
    }

    .invoice-preview-body .invoice-document {
        width: 100%;
    }

    .invoice-preview-body .invoice-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 16px;
    }

    .invoice-preview-body .invoice-table th,
    .invoice-preview-body .invoice-table td {
        border: 1px solid #d1d5db;
        padding: 8px;
        vertical-align: top;
    }

    .invoice-preview-body .text-right {
        text-align: right;
    }

    .invoice-preview-body .text-center {
        text-align: center;
    }

    .invoice-preview-body .align-top {
        vertical-align: top;
    }

    .invoice-preview-body .heading {
        background: #f8fafc;
        font-weight: 600;
    }

    .invoice-preview-body .company-logo {
        width: 130px;
        max-width: 100%;
        height: auto;
        margin-bottom: 10px;
        display: block;
    }

    .invoice-preview-body .invoice-title {
        margin: 0 0 10px;
        font-size: 20px;
    }

    .invoice-preview-body .invoice-subtitle {
        margin: 0 0 8px;
        font-size: 14px;
        font-weight: 600;
    }

    .invoice-preview-body .grand-total {
        background: #f8fafc;
        font-weight: 700;
    }

    .invoice-preview-body .invoice-footer-note {
        margin-top: 12px;
        padding-top: 10px;
        border-top: 1px solid #d1d5db;
        text-align: center;
        color: #6b7280;
        font-size: 11px;
    }

    @media print {
        body * {
            visibility: hidden;
        }

        #order-invoice-preview,
        #order-invoice-preview * {
            visibility: visible;
        }

        #order-invoice-preview {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            box-shadow: none !important;
            border: none !important;
        }

        #order-invoice-preview .card-header {
            display: none;
        }
    }
</style>
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const printBtn = document.getElementById('printInvoiceBtn');
        const invoiceSection = document.getElementById('orderInvoiceSection');
        const invoiceCollapse = invoiceSection ? bootstrap.Collapse.getOrCreateInstance(invoiceSection, {
            toggle: false
        }) : null;

        printBtn?.addEventListener('click', function() {
            if (invoiceSection && !invoiceSection.classList.contains('show') && invoiceCollapse) {
                invoiceCollapse.show();
                invoiceSection.addEventListener('shown.bs.collapse', function handleShown() {
                    invoiceSection.removeEventListener('shown.bs.collapse', handleShown);
                    window.print();
                });
                return;
            }

            window.print();
        });
    });
</script>
@endpush