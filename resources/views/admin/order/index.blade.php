@extends('layouts.admin')

@section('content')

<div class="container-fluid my-2">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">Orders</h4>
            <p class="text-muted mb-0">Manage all orders</p>
        </div>

        <div class="d-flex gap-2">

            <a href="{{ route('order.export') }}" class="btn btn-success">
                <i class="ti ti-file-export me-1"></i> Export Orders
            </a>

        </div>
    </div>

    <!-- Stats -->
    <div class="row mb-4">

        <div class="col-md-3">
            <div class="card shadow-sm">
                <div class="card-body">
                    <p>Total Orders</p>
                    <h4>{{ $orders->total() }}</h4>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm">
                <div class="card-body">
                    <p>Pending</p>
                    <h4 class="text-warning">
                        {{ $orders->where('order_status','pending')->count() }}
                    </h4>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm">
                <div class="card-body">
                    <p>Delivered</p>
                    <h4 class="text-success">
                        {{ $orders->where('order_status','delivered')->count() }}
                    </h4>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm">
                <div class="card-body">
                    <p>Pending Payments</p>
                    <h4 class="text-danger">
                        ₹{{ number_format($orders->sum('due_amount'), 2) }}
                    </h4>
                </div>
            </div>
        </div>

    </div>

    <!-- Filter -->
    <div class="card mb-3 shadow-sm">
        <div class="card-body">

            <form method="GET" action="{{ route('order.index') }}" class="row g-3">

                <div class="col-md-4">
                    <input type="text" name="search" class="form-control"
                        placeholder="Search Order ID / Invoice"
                        value="{{ request('search') }}">
                </div>

                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        @foreach(['pending','processing','confirmed','packed','shipped','out_for_delivery','delivered','cancelled'] as $status)
                            <option value="{{ $status }}" {{ request('status') == $status ? 'selected' : '' }}>
                                {{ ucfirst(str_replace('_',' ', $status)) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <select name="payment_status" class="form-select">
                        <option value="">All Payments</option>
                        @foreach(['pending','partial','paid','failed'] as $p)
                            <option value="{{ $p }}" {{ request('payment_status') == $p ? 'selected' : '' }}>
                                {{ ucfirst($p) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2 d-flex gap-2">
                    <button class="btn btn-primary w-100">Filter</button>
                    <a href="{{ route('order.index') }}" class="btn btn-light">Reset</a>
                </div>

            </form>

        </div>
    </div>

    <!-- Table -->
    <div class="card shadow-sm">
        <div class="card-body p-0">

            <div class="table-responsive">

                <table class="table table-hover mb-0">

                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Invoice</th>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Payment</th>
                            <th>Paid</th>
                            <th>Due</th>
                            <th>Status</th>
                            <th>Total</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody>

                    @forelse($orders as $order)

                        <tr>
                            <td>{{ $loop->iteration }}</td>

                            <td class="text-primary fw-bold">
                                {{ $order->invoice_no ?? '-' }}
                            </td>

                            <td>{{ $order->order_id }}</td>

                            <td>
                                {{ $order->user->name ?? '-' }} <br>
                                <small>{{ $order->user->email ?? '-' }}</small>
                            </td>

                            <td>
                                <span class="badge bg-light text-dark">
                                    {{ ucfirst($order->payment_method) }}
                                </span>
                            </td>

                            <td class="text-success">
                                ₹{{ number_format($order->paid_amount ?? 0, 2) }}
                            </td>

                            <td class="text-danger">
                                ₹{{ number_format($order->due_amount ?? 0, 2) }}
                            </td>

                            <td>
                                <span class="badge bg-secondary">
                                    {{ ucfirst($order->order_status) }}
                                </span>
                            </td>

                            <td>
                                ₹{{ number_format($order->grand_total, 2) }}
                            </td>

                            <td>
                                {{ $order->created_at->format('d M Y') }}
                            </td>

                            <td>
                                <a href="{{ route('order.show', $order->id) }}" class="btn btn-sm btn-primary">
                                    View
                                </a>
                            </td>
                        </tr>

                    @empty

                        <tr>
                            <td colspan="11" class="text-center py-4">
                                No Orders Found
                            </td>
                        </tr>

                    @endforelse

                    </tbody>

                </table>

            </div>

        </div>

        <div class="p-3">
            {{ $orders->links() }}
        </div>

    </div>

</div>

@endsection