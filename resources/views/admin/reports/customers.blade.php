@extends('layouts.admin')

@section('title', 'Customer Report')

@section('content')
@php
    $avgOrders = $customers->total() > 0 ? round($customers->sum('orders_count') / $customers->total(), 1) : 0;
    $avgSpend  = $customers->total() > 0 ? round($customers->sum('orders_sum_grand_total') / $customers->total(), 0) : 0;
@endphp

<div class="page-title-head d-flex align-items-center mb-3">
    <div class="flex-grow-1">
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('reports.index') }}" class="btn btn-sm btn-light border">
                <i class="ti ti-arrow-left"></i>
            </a>
            <div>
                <h4 class="page-main-title m-0">Customer Report</h4>
                <p class="text-muted mb-0">Purchase history, frequency &amp; spending analysis</p>
            </div>
        </div>
    </div>
    <div class="text-end">
        <a href="{{ route('reports.export.customers') }}" class="btn btn-dark btn-sm">
            <i class="ti ti-download me-1"></i> Export CSV
        </a>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-6 col-xxl-3">
        <div class="card card-h-100">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <h5 class="text-muted fs-sm text-uppercase">Total Customers</h5>
                    <h3 class="mb-0 fw-bold text-primary">{{ number_format($customers->total()) }}</h3>
                    <small class="text-muted">With purchases</small>
                </div>
                <span class="avatar-md">
                    <span class="avatar-title bg-primary-subtle text-primary rounded-circle fs-22">
                        <i class="ti ti-users"></i>
                    </span>
                </span>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xxl-3">
        <div class="card card-h-100">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <h5 class="text-muted fs-sm text-uppercase">Total Orders</h5>
                    <h3 class="mb-0 fw-bold text-success">{{ number_format($customers->sum('orders_count')) }}</h3>
                    <small class="text-muted">Completed orders</small>
                </div>
                <span class="avatar-md">
                    <span class="avatar-title bg-success-subtle text-success rounded-circle fs-22">
                        <i class="ti ti-shopping-cart"></i>
                    </span>
                </span>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xxl-3">
        <div class="card card-h-100">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <h5 class="text-muted fs-sm text-uppercase">Total Revenue</h5>
                    <h3 class="mb-0 fw-bold text-warning">₹{{ number_format($customers->sum('orders_sum_grand_total'), 0) }}</h3>
                    <small class="text-muted">From all orders</small>
                </div>
                <span class="avatar-md">
                    <span class="avatar-title bg-warning-subtle text-warning rounded-circle fs-22">
                        <i class="ti ti-currency-rupee"></i>
                    </span>
                </span>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xxl-3">
        <div class="card card-h-100">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <h5 class="text-muted fs-sm text-uppercase">Avg. Orders / Customer</h5>
                    <h3 class="mb-0 fw-bold text-info">{{ $avgOrders }}</h3>
                    <small class="text-muted">Per customer</small>
                </div>
                <span class="avatar-md">
                    <span class="avatar-title bg-info-subtle text-info rounded-circle fs-22">
                        <i class="ti ti-chart-bar"></i>
                    </span>
                </span>
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6 col-xl-3">
                <div class="d-flex align-items-center gap-3">
                    <i class="ti ti-user text-primary fs-22"></i>
                    <div>
                        <div class="text-muted fs-sm">Active customers</div>
                        <div class="fw-semibold">{{ number_format($customers->where('orders_count', '>', 0)->count()) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="d-flex align-items-center gap-3">
                    <i class="ti ti-trophy text-warning fs-22"></i>
                    <div>
                        <div class="text-muted fs-sm">Top spender</div>
                        <div class="fw-semibold">₹{{ number_format($customers->max('orders_sum_grand_total') ?? 0, 0) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="d-flex align-items-center gap-3">
                    <i class="ti ti-shopping-cart text-success fs-22"></i>
                    <div>
                        <div class="text-muted fs-sm">Most orders</div>
                        <div class="fw-semibold">{{ $customers->max('orders_count') ?? 0 }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="d-flex align-items-center gap-3">
                    <i class="ti ti-calendar text-info fs-22"></i>
                    <div>
                        <div class="text-muted fs-sm">Avg spend</div>
                        <div class="fw-semibold">₹{{ number_format($avgSpend, 0) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h4 class="card-title mb-1">Top customers by spend</h4>
            <p class="text-muted mb-0 fs-sm">Based on total purchase value</p>
        </div>
        <span class="badge badge-soft-primary"><i class="ti ti-trophy me-1"></i> Top 10</span>
    </div>
    <div class="card-body">
        @if($customers->count() > 0)
            <canvas id="topCustomersChart" height="120"></canvas>
        @else
            <div class="text-center py-5 text-muted">
                <i class="ti ti-users fs-1 d-block mb-2"></i>
                No customer data available
            </div>
        @endif
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h4 class="card-title mb-1">All Customers</h4>
            <p class="text-muted mb-0 fs-sm">Showing {{ $customers->firstItem() ?? 0 }} to {{ $customers->lastItem() ?? 0 }} of {{ $customers->total() }} customers</p>
        </div>
        <span class="badge badge-soft-primary"><i class="ti ti-users me-1"></i> {{ $customers->total() }} total</span>
    </div>
    <div class="table-responsive">
        <table class="table table-custom table-nowrap table-centered table-hover w-100 mb-0">
            <thead class="bg-light align-middle bg-opacity-25 thead-sm">
                <tr class="text-uppercase fs-xxs">
                    <th>Customer</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th class="text-center">Orders</th>
                    <th class="text-end">Total Spend</th>
                    <th class="text-center">Last Order</th>
                </tr>
            </thead>
            <tbody>
                @forelse($customers as $customer)
                @php
                    $avatarColors = ['bg-primary', 'bg-info', 'bg-success', 'bg-warning', 'bg-secondary'];
                    $avatarBg = $avatarColors[$loop->index % count($avatarColors)];
                    $ranks = ['🥇', '🥈', '🥉'];
                @endphp
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <span class="avatar-xs">
                                <span class="avatar-title {{ $avatarBg }} text-white rounded-circle">
                                    {{ strtoupper(substr($customer->name, 0, 1)) }}
                                </span>
                            </span>
                            <span class="fw-semibold">{{ $customer->name }}</span>
                            @if($loop->index < 3)
                                <span>{{ $ranks[$loop->index] }}</span>
                            @endif
                        </div>
                    </td>
                    <td class="text-muted">{{ $customer->email }}</td>
                    <td class="text-muted">{{ $customer->phone ?? '—' }}</td>
                    <td class="text-center">
                        <span class="badge badge-soft-primary">{{ $customer->orders_count }}</span>
                    </td>
                    <td class="text-end fw-semibold">₹{{ number_format($customer->orders_sum_grand_total ?? 0, 2) }}</td>
                    <td class="text-center text-muted">
                        @if($customer->last_order_date)
                            {{ \Carbon\Carbon::parse($customer->last_order_date)->format('d M Y') }}
                        @else
                            —
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center py-5 text-muted">No customers with orders found</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($customers->hasPages())
    <div class="card-footer border-0">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div class="text-muted small">
                <i class="ti ti-info-circle me-1"></i>
                Showing {{ $customers->firstItem() }} to {{ $customers->lastItem() }} of {{ $customers->total() }} results
            </div>
            <div>{{ $customers->appends(request()->query())->links('pagination::bootstrap-5') }}</div>
        </div>
    </div>
    @endif
</div>

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const topCustomers = @json(
        $customers->sortByDesc('orders_sum_grand_total')->take(10)->values()->map(fn($c) => [
            'name' => $c->name,
            'spend' => (float)($c->orders_sum_grand_total ?? 0),
        ])
    );

    const ctx = document.getElementById('topCustomersChart');
    if (ctx && topCustomers.length > 0) {
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: topCustomers.map(c => c.name.length > 22 ? c.name.slice(0, 22) + '…' : c.name),
                datasets: [{
                    label: 'Total Spend (₹)',
                    data: topCustomers.map(c => c.spend),
                    backgroundColor: '#39afd1',
                    borderRadius: 6,
                    borderSkipped: false
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: { label: c => ' ₹' + Number(c.raw).toLocaleString('en-IN') }
                    }
                },
                scales: {
                    x: {
                        ticks: { callback: v => '₹' + Number(v / 1000).toFixed(0) + 'k' }
                    }
                }
            }
        });
    }
});

</script>
@endpush
@endsection
