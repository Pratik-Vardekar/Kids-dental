@extends('layouts.admin')

@section('title', 'Reports')

@section('content')
    <div class="page-title-head d-flex align-items-center mb-3">
        <div class="flex-grow-1">
            <h4 class="page-main-title m-0">Reports</h4>
            <p class="text-muted mb-0">Analytics &amp; business insights</p>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-6 col-xxl-3">
            <div class="card card-h-100">
                <div class="card-body">
                    <h5 class="text-muted fs-sm text-uppercase text-truncate">Total Orders</h5>
                    <h3 class="mb-0 fw-bold text-primary">{{ number_format($totalOrders ?? 0) }}</h3>
                    <small class="text-muted">All time</small>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xxl-3">
            <div class="card card-h-100">
                <div class="card-body">
                    <h5 class="text-muted fs-sm text-uppercase text-truncate">Total Revenue</h5>
                    <h3 class="mb-0 fw-bold text-success">₹{{ number_format($totalRevenue ?? 0, 0) }}</h3>
                    <small class="text-muted">All time</small>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xxl-3">
            <div class="card card-h-100">
                <div class="card-body">
                    <h5 class="text-muted fs-sm text-uppercase text-truncate">Customers</h5>
                    <h3 class="mb-0 fw-bold text-warning">{{ number_format($totalCustomers ?? 0) }}</h3>
                    <small class="text-muted">Registered</small>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xxl-3">
            <div class="card card-h-100">
                <div class="card-body">
                    <h5 class="text-muted fs-sm text-uppercase text-truncate">Active Orders</h5>
                    <h3 class="mb-0 fw-bold text-info">{{ number_format(($processingOrders ?? 0) + ($shippedOrders ?? 0)) }}
                    </h3>
                    <small class="text-muted">Processing &amp; shipped</small>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="card card-h-100">
                <div class="card-body d-flex flex-column">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <span class="avatar-md flex-shrink-0">
                            <span class="avatar-title bg-primary-subtle text-primary rounded-circle fs-22">
                                <i class="ti ti-chart-line"></i>
                            </span>
                        </span>
                        <div>
                            <h5 class="mb-1">Sales Report</h5>
                            <p class="text-muted mb-0 fs-sm">Revenue &amp; collections</p>
                        </div>
                    </div>
                    <p class="text-muted flex-grow-1">View daily sales performance, revenue trends, paid &amp; due amounts
                        at a glance.</p>
                    <a href="{{ route('reports.sales') }}" class="btn btn-primary btn-sm">
                        View Report <i class="ti ti-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-h-100">
                <div class="card-body d-flex flex-column">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <span class="avatar-md flex-shrink-0">
                            <span class="avatar-title bg-success-subtle text-success rounded-circle fs-22">
                                <i class="ti ti-shopping-cart"></i>
                            </span>
                        </span>
                        <div>
                            <h5 class="mb-1">Order Report</h5>
                            <p class="text-muted mb-0 fs-sm">Status &amp; delivery</p>
                        </div>
                    </div>
                    <p class="text-muted flex-grow-1">Analyze orders by status, track delivery performance and identify
                        trends.</p>
                    <a href="{{ route('reports.orders') }}" class="btn btn-success btn-sm">
                        View Report <i class="ti ti-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-h-100">
                <div class="card-body d-flex flex-column">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <span class="avatar-md flex-shrink-0">
                            <span class="avatar-title bg-info-subtle text-info rounded-circle fs-22">
                                <i class="ti ti-users"></i>
                            </span>
                        </span>
                        <div>
                            <h5 class="mb-1">Customer Report</h5>
                            <p class="text-muted mb-0 fs-sm">Spending &amp; frequency</p>
                        </div>
                    </div>
                    <p class="text-muted flex-grow-1">Analyze customer purchase history, order frequency and total spending.
                    </p>
                    <a href="{{ route('reports.customers') }}" class="btn btn-info btn-sm">
                        View Report <i class="ti ti-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card card-h-100">
                <div class="card-header border-0">
                    <h4 class="card-title mb-1">Order status breakdown</h4>
                    <p class="text-muted mb-0 fs-sm">Current distribution</p>
                </div>
                <div class="card-body text-center">
                    <canvas id="statusDonut" height="200" role="img"
                        aria-label="Donut chart of order statuses"></canvas>
                    <div class="mt-3" id="statusLegend"></div>
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="card card-h-100">
                <div class="card-header border-0">
                    <h4 class="card-title mb-1">Revenue snapshot</h4>
                    <p class="text-muted mb-0 fs-sm">Total vs paid vs due</p>
                </div>
                <div class="card-body">
                    <canvas id="revenueBar" height="120" role="img"
                        aria-label="Bar chart comparing total revenue, paid, and due amounts"></canvas>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
        <script>
            (function() {
                const statusColors = ['#ffcc00', '#8790f6', '#02bc9c', '#fa5c7c'];
                const statusLabels = ['Processing', 'Shipped', 'Delivered', 'Cancelled'];
                const statusData = [{{ $processingOrders ?? 0 }}, {{ $shippedOrders ?? 0 }}, {{ $deliveredOrders ?? 0 }},
                    {{ $cancelledOrders ?? 0 }}
                ];
                const total = statusData.reduce((a, b) => a + b, 0);

                new Chart(document.getElementById('statusDonut'), {
                    type: 'doughnut',
                    data: {
                        labels: statusLabels,
                        datasets: [{
                            data: statusData,
                            backgroundColor: statusColors,
                            borderWidth: 2,
                            borderColor: '#fff',
                            hoverOffset: 5
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        cutout: '65%',
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(c) {
                                        const p = total > 0 ? Math.round((c.raw / total) * 100) : 0;
                                        return ' ' + c.label + ': ' + c.raw + ' (' + p + '%)';
                                    }
                                }
                            }
                        }
                    }
                });

                const leg = document.getElementById('statusLegend');
                statusLabels.forEach(function(lbl, i) {
                    const p = total > 0 ? Math.round((statusData[i] / total) * 100) : 0;
                    leg.innerHTML += '<div class="d-flex align-items-center justify-content-between py-1">' +
                        '<div class="d-flex align-items-center gap-2">' +
                        '<span class="badge rounded-circle p-1" style="background:' + statusColors[i] +
                        '">&nbsp;</span>' +
                        '<span class="text-muted">' + lbl + '</span>' +
                        '</div>' +
                        '<div class="d-flex align-items-center gap-2">' +
                        '<span class="fw-semibold">' + statusData[i].toLocaleString() + '</span>' +
                        '<span class="text-muted fs-sm">' + p + '%</span>' +
                        '</div></div>';
                });

                new Chart(document.getElementById('revenueBar'), {
                    type: 'bar',
                    data: {
                        labels: ['Total Sales', 'Paid', 'Due'],
                        datasets: [{
                            label: 'Amount (₹)',
                            data: [{{ $totalRevenue ?? 0 }}, {{ $totalPaid ?? 0 }},
                                {{ $totalDue ?? 0 }}
                            ],
                            backgroundColor: ['#39afd1', '#02bc9c', '#fa5c7c'],
                            borderRadius: 6,
                            borderSkipped: false
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(c) {
                                        return ' ₹' + Number(c.raw).toLocaleString('en-IN');
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                ticks: {
                                    callback: function(v) {
                                        return '₹' + Number(v / 1000).toFixed(0) + 'k';
                                    }
                                }
                            }
                        }
                    }
                });
            })();
        </script>
    @endpush
@endsection
