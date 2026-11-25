@extends('layouts.admin')

@section('title', 'Order Report')

@section('content')
<div class="page-title-head d-flex align-items-center mb-3">
    <div class="flex-grow-1">
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('reports.index') }}" class="btn btn-sm btn-light border">
                <i class="ti ti-arrow-left"></i>
            </a>
            <div>
                <h4 class="page-main-title m-0">Order Report</h4>
                <p class="text-muted mb-0">Status breakdown &amp; delivery performance</p>
            </div>
        </div>
    </div>
    <div class="text-end">
        <a href="{{ route('reports.export.orders') }}" class="btn btn-dark btn-sm">
            <i class="ti ti-download me-1"></i> Export CSV
        </a>
    </div>
</div>

@php
$stats = [
    ['label' => 'Processing', 'count' => $processing, 'color' => 'primary', 'icon' => 'ti-refresh', 'foot' => 'Awaiting fulfillment'],
    ['label' => 'Shipped', 'count' => $shipped, 'color' => 'info', 'icon' => 'ti-truck', 'foot' => 'In transit'],
    ['label' => 'Delivered', 'count' => $delivered, 'color' => 'success', 'icon' => 'ti-circle-check', 'foot' => 'Successfully completed'],
    ['label' => 'Cancelled', 'count' => $cancelled, 'color' => 'danger', 'icon' => 'ti-circle-x', 'foot' => 'Orders voided'],
];
$total = $processing + $shipped + $delivered + $cancelled;
@endphp

<div class="row g-3 mb-3">
    @foreach($stats as $s)
    @php
        $percentage = $total > 0 ? round(($s['count'] / $total) * 100, 1) : 0;
    @endphp
    <div class="col-md-6 col-xxl-3">
        <div class="card card-h-100">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="avatar-sm">
                        <span class="avatar-title bg-{{ $s['color'] }}-subtle text-{{ $s['color'] }} rounded-circle">
                            <i class="ti {{ $s['icon'] }}"></i>
                        </span>
                    </span>
                    <span class="badge bg-light text-muted">{{ $percentage }}%</span>
                </div>
                <h5 class="text-muted fs-sm text-uppercase mb-1">{{ $s['label'] }}</h5>
                <h3 class="mb-1 fw-bold text-{{ $s['color'] }}">{{ number_format($s['count']) }}</h3>
                <small class="text-muted d-block mb-2">{{ $s['foot'] }}</small>
                <div class="progress" style="height:6px;">
                    <div class="progress-bar bg-{{ $s['color'] }}" role="progressbar" style="width: {{ max($percentage, 0) }}%"></div>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>

<div class="row g-3">
    <div class="col-xl-9">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h4 class="card-title mb-1">All Orders</h4>
                    <p class="text-muted mb-0 fs-sm">List of all orders excluding pending</p>
                </div>
                <span class="badge badge-soft-primary">
                    <i class="ti ti-shopping-cart me-1"></i> Total: {{ $orders->total() }}
                </span>
            </div>
            <div class="table-responsive">
                <table class="table table-custom table-nowrap table-centered table-hover w-100 mb-0">
                    <thead class="bg-light align-middle bg-opacity-25 thead-sm">
                        <tr class="text-uppercase fs-xxs">
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th class="text-end">Total</th>
                            <th class="text-end">Paid</th>
                            <th class="text-end">Due</th>
                            <th class="text-center">Payment</th>
                            <th class="text-center">Status</th>
                            <th class="text-end">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($orders as $order)
                        <tr>
                            <td class="fw-semibold text-primary">#{{ $order->display_order_id }}</td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="avatar-xs">
                                        <span class="avatar-title bg-primary text-white rounded-circle">
                                            {{ strtoupper(substr($order->user->name ?? 'U', 0, 1)) }}
                                        </span>
                                    </span>
                                    <span>{{ $order->user->name ?? '—' }}</span>
                                </div>
                            </td>
                            <td class="text-end fw-semibold">₹{{ number_format($order->grand_total, 2) }}</td>
                            <td class="text-end text-success">₹{{ number_format($order->paid_amount, 2) }}</td>
                            <td class="text-end">
                                @if($order->due_amount > 0)
                                    <span class="text-danger">₹{{ number_format($order->due_amount, 2) }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @php
                                $pc = match($order->payment_status) {
                                    'paid' => 'badge-soft-success',
                                    'partial' => 'badge-soft-warning',
                                    default => 'badge-soft-danger',
                                };
                                @endphp
                                <span class="badge {{ $pc }}">{{ ucfirst($order->payment_status) }}</span>
                            </td>
                            <td class="text-center">
                                @php
                                $sc = match($order->order_status) {
                                    'delivered' => 'badge-soft-success',
                                    'shipped' => 'badge-soft-info',
                                    'processing' => 'badge-soft-primary',
                                    'cancelled' => 'badge-soft-danger',
                                    default => 'badge-soft-warning',
                                };
                                @endphp
                                <span class="badge {{ $sc }}">{{ ucfirst($order->order_status) }}</span>
                            </td>
                            <td class="text-end text-muted">{{ $order->created_at->format('d M Y') }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="8" class="text-center py-5 text-muted">No orders found</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($orders->hasPages())
            <div class="card-footer border-0">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div class="text-muted small">
                        <i class="ti ti-info-circle me-1"></i>
                        Showing {{ $orders->firstItem() }} to {{ $orders->lastItem() }} of {{ $orders->total() }} orders
                    </div>
                    <div>{{ $orders->appends(request()->query())->links('pagination::bootstrap-5') }}</div>
                </div>
            </div>
            @endif
        </div>
    </div>

    <div class="col-xl-3">
        <div class="card card-h-100">
            <div class="card-header border-0">
                <h4 class="card-title mb-1">Status distribution</h4>
                <p class="text-muted mb-0 fs-sm">Order breakdown by status</p>
            </div>
            <div class="card-body text-center">
                <div class="position-relative d-inline-flex justify-content-center align-items-center mb-2" style="width: 200px; height: 200px; max-width: 100%;">
                    <canvas id="orderDonut" width="200" height="200" role="img" aria-label="Donut chart showing order status distribution"></canvas>
                    <div class="position-absolute top-50 start-50 translate-middle text-center" style="pointer-events: none; z-index: 2;">
                        <h4 class="mb-0 fw-bold text-dark">{{ number_format(($pending ?? 0) + ($processing ?? 0) + ($shipped ?? 0) + ($delivered ?? 0) + ($cancelled ?? 0)) }}</h4>
                        <span class="text-muted text-uppercase" style="font-size: 10px; letter-spacing: 0.5px;">Total Orders</span>
                    </div>
                </div>
                <div class="mt-2 text-start" id="donutLegend"></div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const labels = ['Pending', 'Processing', 'Shipped', 'Delivered', 'Cancelled'];
    const data = [
        {{ $pending ?? 0 }},
        {{ $processing ?? 0 }},
        {{ $shipped ?? 0 }},
        {{ $delivered ?? 0 }},
        {{ $cancelled ?? 0 }}
    ];
    const colors = ['#ffbc00', '#727cf5', '#39afd1', '#02bc9c', '#fa5c7c'];
    const total = data.reduce((a, b) => a + b, 0);
    const isZero = total === 0;

    const chartData = isZero ? [1] : data;
    const chartColors = isZero ? ['#e9ecef'] : colors;

    const ctx = document.getElementById('orderDonut');
    if (ctx) {
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: isZero ? ['No Orders'] : labels,
                datasets: [{
                    data: chartData,
                    backgroundColor: chartColors,
                    borderWidth: isZero ? 0 : 2,
                    borderColor: isZero ? '#e9ecef' : '#ffffff',
                    hoverOffset: isZero ? 0 : 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '72%',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        enabled: !isZero,
                        callbacks: {
                            label: c => {
                                const pct = total > 0 ? Math.round((c.raw / total) * 100) : 0;
                                return ` ${c.label}: ${c.raw} (${pct}%)`;
                            }
                        }
                    }
                }
            }
        });
    }

    const legend = document.getElementById('donutLegend');
    if (legend) {
        labels.forEach((lbl, i) => {
            const pct = total > 0 ? Math.round((data[i] / total) * 100) : 0;
            legend.innerHTML += `
                <div class="d-flex align-items-center justify-content-between py-1 border-bottom border-light">
                    <div class="d-flex align-items-center gap-2">
                        <span class="rounded-circle" style="background:${colors[i]};width:10px;height:10px;display:inline-block;"></span>
                        <span class="text-muted">${lbl}</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="fw-semibold">${data[i].toLocaleString()}</span>
                        <span class="text-muted fs-sm">${pct}%</span>
                    </div>
                </div>`;
        });
    }
});

</script>
@endpush
@endsection
