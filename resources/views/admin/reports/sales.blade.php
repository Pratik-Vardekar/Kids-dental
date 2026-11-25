@extends('layouts.admin')

@section('title', 'Sales Report')

@section('content')
    <div class="page-title-head d-flex align-items-center mb-3">
        <div class="flex-grow-1">
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('reports.index') }}" class="btn btn-sm btn-light border">
                    <i class="ti ti-arrow-left"></i>
                </a>
                <div>
                    <h4 class="page-main-title m-0">Sales Report</h4>
                    <p class="text-muted mb-0">Revenue, collections &amp; daily performance</p>
                </div>
            </div>
        </div>
        <div class="text-end">
            <a href="{{ route('reports.export.sales') }}" class="btn btn-warning btn-sm">
                <i class="ti ti-download me-1"></i> Export CSV
            </a>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-6 col-xxl-3">
            <div class="card card-h-100">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <h5 class="text-muted fs-sm text-uppercase text-truncate">Total Orders</h5>
                        <h3 class="mb-0 fw-bold">{{ number_format($totalOrders) }}</h3>
                        <small class="text-muted">all time</small>
                    </div>
                    <span class="avatar-md">
                        <span class="avatar-title bg-primary-subtle text-primary rounded-circle fs-22">
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
                        <h5 class="text-muted fs-sm text-uppercase text-truncate">Total Sales</h5>
                        <h3 class="mb-0 fw-bold text-warning">₹{{ number_format($totalSales, 0) }}</h3>
                        <small class="text-muted">total revenue</small>
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
                        <h5 class="text-muted fs-sm text-uppercase text-truncate">Total Paid</h5>
                        <h3 class="mb-0 fw-bold text-warning">₹{{ number_format($totalPaid, 0) }}</h3>
                        <small class="text-muted">collected amount</small>
                    </div>
                    <span class="avatar-md">
                        <span class="avatar-title bg-warning-subtle text-warning rounded-circle fs-22">
                            <i class="ti ti-wallet"></i>
                        </span>
                    </span>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xxl-3">
            <div class="card card-h-100">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <h5 class="text-muted fs-sm text-uppercase text-truncate">Total Due</h5>
                        <h3 class="mb-0 fw-bold text-warning">₹{{ number_format($totalDue, 0) }}</h3>
                        <small class="text-muted">pending collection</small>
                    </div>
                    <span class="avatar-md">
                        <span class="avatar-title bg-warning-subtle text-warning rounded-circle fs-22">
                            <i class="ti ti-clock"></i>
                        </span>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="d-flex align-items-center gap-3">
                        <span class="avatar-md">
                            <span class="avatar-title bg-warning-subtle text-warning rounded-circle fs-22">
                                <i class="ti ti-currency-rupee"></i>
                            </span>
                        </span>
                        <div class="flex-grow-1">
                            <h6 class="text-muted mb-1 fs-sm">Collection Rate</h6>
                            @php
                                $collectionRate = $totalSales > 0 ? round(($totalPaid / $totalSales) * 100, 1) : 0;
                            @endphp
                            <h4 class="mb-1 fw-bold text-warning">
                                {{ $collectionRate }}%
                                <small class="text-muted fs-sm">of total revenue</small>
                            </h4>
                            <div class="progress" style="height:6px;">
                                <div class="progress-bar bg-{{ $collectionRate >= 80 ? 'success' : ($collectionRate >= 50 ? 'warning' : 'danger') }}"
                                    role="progressbar" style="width: {{ $collectionRate }}%"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="d-flex align-items-center gap-3">
                        <span class="avatar-md">
                            <span class="avatar-title bg-warning-subtle text-warning rounded-circle fs-22">
                                <i class="ti ti-shopping-cart"></i>
                            </span>
                        </span>
                        <div>
                            <h6 class="text-muted mb-1 fs-sm">Average Order Value</h6>
                            @php
                                $avgOrderValue = $totalOrders > 0 ? round($totalSales / $totalOrders, 0) : 0;
                            @endphp
                            <h4 class="mb-0 fw-bold text-warning">₹{{ number_format($avgOrderValue, 0) }}</h4>
                            <small class="text-muted">per order</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="d-flex align-items-center gap-3">
                        <span class="avatar-md">
                            <span class="avatar-title bg-warning-subtle text-warning rounded-circle fs-22">
                                <i class="ti ti-calendar"></i>
                            </span>
                        </span>
                        <div>
                            <h6 class="text-muted mb-1 fs-sm">Daily Average</h6>
                            @php
                                $daysWithData = $salesByDate->count();
                                $dailyAvg = $daysWithData > 0 ? round($totalSales / $daysWithData, 0) : 0;
                            @endphp
                            <h4 class="mb-0 fw-bold text-warning">₹{{ number_format($dailyAvg, 0) }}</h4>
                            <small class="text-muted">per day</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h4 class="card-title mb-1">Daily Sales Trend</h4>
                <span class="text-muted fs-sm">Sales, paid &amp; due over time</span>
            </div>
            <div class="d-flex gap-3 flex-wrap">
                <span class="badge badge-soft-primary">Sales</span>
                <span class="badge badge-soft-success">Paid</span>
                <span class="badge badge-soft-danger">Due</span>
            </div>
        </div>
        <div class="card-body">
            <canvas id="salesTrend" height="100"></canvas>
        </div>
    </div>

    <div class="card">
        <div class="card-header border-light d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h4 class="card-title mb-1">Sales Summary by Date</h4>
                @if ($salesByDate instanceof \Illuminate\Pagination\LengthAwarePaginator)
                    <span class="text-muted fs-sm">Showing {{ $salesByDate->firstItem() ?? 0 }} to
                        {{ $salesByDate->lastItem() ?? 0 }} of {{ $salesByDate->total() }} records</span>
                @else
                    <span class="text-muted fs-sm">Showing {{ $salesByDate->count() }} records</span>
                @endif
            </div>
            <span class="badge badge-soft-primary">
                <i class="ti ti-calendar me-1"></i> Daily Summary
            </span>
        </div>
        <div class="table-responsive">
            <table class="table table-custom table-nowrap table-centered table-hover w-100 mb-0">
                <thead class="bg-light align-middle bg-opacity-25 thead-sm">
                    <tr class="text-uppercase fs-xxs">
                        <th>Date</th>
                        <th class="text-center">Orders</th>
                        <th class="text-end">Sales</th>
                        <th class="text-end">Paid</th>
                        <th class="text-end">Due</th>
                        <th class="text-center">Collection Rate</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($salesByDate as $sale)
                        @php
                            $collectionRate = $sale->sales > 0 ? round(($sale->paid / $sale->sales) * 100, 1) : 0;
                            $rateColor =
                                $collectionRate >= 80 ? 'success' : ($collectionRate >= 50 ? 'warning' : 'danger');
                        @endphp
                        <tr>
                            <td class="fw-medium">{{ \Carbon\Carbon::parse($sale->date)->format('d M Y') }}</td>
                            <td class="text-center">
                                <span class="badge badge-soft-primary fw-semibold px-3">{{ $sale->orders_count }}</span>
                            </td>
                            <td class="text-end fw-semibold">₹{{ number_format($sale->sales, 2) }}</td>
                            <td class="text-end text-success fw-medium">₹{{ number_format($sale->paid, 2) }}</td>
                            <td class="text-end">
                                @if ($sale->due > 0)
                                    <span class="text-danger fw-medium">₹{{ number_format($sale->due, 2) }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <div class="d-flex align-items-center justify-content-center gap-2">
                                    <div class="progress flex-grow-1" style="max-width:80px;height:6px;">
                                        <div class="progress-bar bg-{{ $rateColor }}" role="progressbar"
                                            style="width: {{ $collectionRate }}%"></div>
                                    </div>
                                    <span
                                        class="text-{{ $rateColor }} fw-semibold small">{{ $collectionRate }}%</span>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">No sales data available</td>
                        </tr>
                    @endforelse
                </tbody>
                @if ($salesByDate->count() > 0)
                    <tfoot class="bg-light bg-opacity-50">
                        <tr class="fw-semibold">
                            <td class="text-uppercase fs-xxs">Total</td>
                            <td class="text-center">{{ $salesByDate->sum('orders_count') }}</td>
                            <td class="text-end">₹{{ number_format($salesByDate->sum('sales'), 2) }}</td>
                            <td class="text-end text-success">₹{{ number_format($salesByDate->sum('paid'), 2) }}</td>
                            <td class="text-end text-danger">₹{{ number_format($salesByDate->sum('due'), 2) }}</td>
                            <td class="text-center">
                                @php
                                    $totalSalesSum = $salesByDate->sum('sales');
                                    $totalPaidSum = $salesByDate->sum('paid');
                                    $overallRate =
                                        $totalSalesSum > 0 ? round(($totalPaidSum / $totalSalesSum) * 100, 1) : 0;
                                @endphp
                                <span
                                    class="badge bg-{{ $overallRate >= 80 ? 'success' : ($overallRate >= 50 ? 'warning' : 'danger') }}">
                                    {{ $overallRate }}% collection
                                </span>
                            </td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
        @if ($salesByDate instanceof \Illuminate\Pagination\LengthAwarePaginator && $salesByDate->hasPages())
            <div class="card-footer border-0">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div class="text-muted small">
                        <i class="ti ti-info-circle me-1"></i>
                        Showing {{ $salesByDate->firstItem() }} to {{ $salesByDate->lastItem() }} of
                        {{ $salesByDate->total() }} records
                    </div>
                    <div>{{ $salesByDate->appends(request()->query())->links('pagination::bootstrap-5') }}</div>
                </div>
            </div>
        @endif
    </div>

    @push('scripts')
        <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const labels = @json($salesByDate->pluck('date')->reverse()->values());
                const salesData = @json($salesByDate->pluck('sales')->reverse()->values());
                const paidData = @json($salesByDate->pluck('paid')->reverse()->values());
                const dueData = @json($salesByDate->pluck('due')->reverse()->values());

                const formattedLabels = labels.map(date => {
                    const d = new Date(date);
                    return d.toLocaleDateString('en-IN', {
                        day: '2-digit',
                        month: 'short'
                    });
                });

                const ctx = document.getElementById('salesTrend');
                if (!ctx) return;

                if (salesData.length === 0 || salesData.every(v => v === 0)) {
                    ctx.parentElement.innerHTML =
                        '<div class="text-center py-5 text-muted"><i class="ti ti-chart-bar fs-1 d-block mb-2"></i>No sales data available to display</div>';
                    return;
                }

                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: formattedLabels,
                        datasets: [{
                                label: 'Sales',
                                data: salesData,
                                borderColor: '#39afd1',
                                backgroundColor: 'rgba(57,175,209,0.1)',
                                fill: true,
                                tension: 0.4,
                                borderWidth: 2
                            },
                            {
                                label: 'Paid',
                                data: paidData,
                                borderColor: '#02bc9c',
                                backgroundColor: 'rgba(2,188,156,0.05)',
                                fill: true,
                                borderDash: [6, 4],
                                tension: 0.4,
                                borderWidth: 2
                            },
                            {
                                label: 'Due',
                                data: dueData,
                                borderColor: '#fa5c7c',
                                backgroundColor: 'rgba(250,92,124,0.05)',
                                fill: true,
                                tension: 0.4,
                                borderWidth: 2
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        interaction: {
                            mode: 'index',
                            intersect: false
                        },
                        plugins: {
                            legend: {
                                display: true,
                                position: 'top'
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return context.dataset.label + ': ₹' + Number(context.raw)
                                            .toLocaleString('en-IN');
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                ticks: {
                                    callback: function(value) {
                                        return '₹' + Number(value).toLocaleString('en-IN');
                                    }
                                },
                                beginAtZero: true
                            }
                        }
                    }
                });
            });
        </script>
    @endpush
@endsection
