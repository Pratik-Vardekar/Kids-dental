<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\HandlesCsv;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\User;

class ReportController extends Controller
{
    use HandlesCsv;

    public function index()
    {
        // Use paginate instead of get to have pagination methods available
        $orders = Order::with('user')
            ->latest()
            ->paginate(10);

        // Get sales by date for charts
        $salesByDate = Order::selectRaw('
                DATE(created_at) as date,
                COUNT(*) as orders_count,
                SUM(grand_total) as sales,
                SUM(paid_amount) as paid,
                SUM(due_amount) as due
            ')
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->limit(30)
            ->get();

        return view('admin.reports.index', [
            'totalOrders'      => Order::count(),
            'totalRevenue'     => Order::sum('grand_total'),
            'totalPaid'        => Order::sum('paid_amount'),
            'totalDue'         => Order::sum('due_amount'),
            'totalCustomers'   => User::byRole('user')->count(),
            'pendingOrders'    => Order::whereRaw('LOWER(TRIM(order_status)) = ?', ['pending'])->count(),
            'processingOrders' => Order::whereRaw('LOWER(TRIM(order_status)) = ?', ['processing'])->count(),
            'shippedOrders'    => Order::whereRaw('LOWER(TRIM(order_status)) = ?', ['shipped'])->count(),
            'deliveredOrders'  => Order::whereRaw('LOWER(TRIM(order_status)) = ?', ['delivered'])->count(),
            'cancelledOrders'  => Order::whereRaw('LOWER(TRIM(order_status)) = ?', ['cancelled'])->count(),
            'orders'           => $orders,
            'salesByDate'      => $salesByDate,
        ]);
    }

   public function sales()
{
    // Exclude pending orders from sales calculations
    $totalSales  = Order::where('order_status', '!=', 'pending')->sum('grand_total');
    $totalPaid   = Order::where('order_status', '!=', 'pending')->sum('paid_amount');
    $totalDue    = Order::where('order_status', '!=', 'pending')->sum('due_amount');
    $totalOrders = Order::where('order_status', '!=', 'pending')->count();

    // Use paginate instead of get
    $salesByDate = Order::where('order_status', '!=', 'pending')
        ->selectRaw('
            DATE(created_at) as date,
            COUNT(*) as orders_count,
            SUM(grand_total) as sales,
            SUM(paid_amount) as paid,
            SUM(due_amount) as due
        ')
        ->groupBy('date')
        ->orderBy('date', 'desc')
        ->paginate(15);

    return view('admin.reports.sales', compact(
        'totalSales',
        'totalPaid',
        'totalDue',
        'totalOrders',
        'salesByDate'
    ));
}

    public function orders()
{
    $pending    = Order::where('order_status', 'pending')->count();
    $processing = Order::where('order_status', 'processing')->count();
    $shipped    = Order::where('order_status', 'shipped')->count();
    $delivered  = Order::where('order_status', 'delivered')->count();
    $cancelled  = Order::where('order_status', 'cancelled')->count();

    // Exclude pending orders from the table
    $orders = Order::with('user')
        ->where('order_status', '!=', 'pending')
        ->latest()
        ->paginate(20);

    return view('admin.reports.orders', compact(
        'pending',
        'processing',
        'shipped',
        'delivered',
        'cancelled',
        'orders'
    ));
}

   public function customers()
{
    // Get only users who have placed at least one order
    $customers = User::byRole('user')
        ->whereHas('orders', function($query) {
            $query->where('order_status', '!=', 'cancelled'); // Optionally exclude cancelled orders
        })
        ->withCount(['orders' => function($query) {
            $query->where('order_status', '!=', 'cancelled'); // Count only non-cancelled orders
        }])
        ->withSum(['orders' => function($query) {
            $query->where('order_status', '!=', 'cancelled'); // Sum only non-cancelled orders
        }], 'grand_total')
        ->orderBy('orders_count', 'desc') // Order by most orders first
        ->paginate(20);

    return view('admin.reports.customers', compact('customers'));
}

    public function exportSales()
    {
        $rows = Order::where('order_status', '!=', 'pending')
            ->selectRaw('
                DATE(created_at) as date,
                COUNT(*) as orders_count,
                SUM(grand_total) as sales,
                SUM(paid_amount) as paid,
                SUM(due_amount) as due
            ')
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->get();

        $fileName = 'sales_report_' . date('Y-m-d_His') . '.csv';

        return $this->streamCsvDownload($fileName, [
            'Date',
            'Orders',
            'Sales',
            'Paid',
            'Due',
        ], function ($file) use ($rows) {
            foreach ($rows as $row) {
                fputcsv($file, [
                    $row->date,
                    $row->orders_count,
                    number_format((float) $row->sales, 2, '.', ''),
                    number_format((float) $row->paid, 2, '.', ''),
                    number_format((float) $row->due, 2, '.', ''),
                ]);
            }
        });
    }

    public function exportOrders()
    {
        $orders = Order::with('user')
            ->where('order_status', '!=', 'pending')
            ->latest()
            ->get();

        $fileName = 'orders_report_' . date('Y-m-d_His') . '.csv';

        return $this->streamCsvDownload($fileName, [
            'Order ID',
            'Customer',
            'Email',
            'Phone',
            'Payment Method',
            'Payment Status',
            'Order Status',
            'Grand Total',
            'Paid',
            'Due',
            'Date',
        ], function ($file) use ($orders) {
            foreach ($orders as $order) {
                fputcsv($file, [
                    $order->display_order_id,
                    $order->user->name ?? 'Guest',
                    $order->user->email ?? '',
                    $order->user->phone ?? '',
                    strtoupper($order->payment_method ?? ''),
                    ucfirst($order->payment_status ?? ''),
                    ucfirst($order->order_status ?? ''),
                    number_format((float) ($order->grand_total ?? 0), 2, '.', ''),
                    number_format((float) ($order->paid_amount ?? 0), 2, '.', ''),
                    number_format((float) ($order->due_amount ?? 0), 2, '.', ''),
                    optional($order->created_at)->format('d-m-Y H:i'),
                ]);
            }
        });
    }

    public function exportCustomers()
    {
        $customers = User::byRole('user')
            ->whereHas('orders', function ($query) {
                $query->where('order_status', '!=', 'cancelled');
            })
            ->withCount(['orders' => function ($query) {
                $query->where('order_status', '!=', 'cancelled');
            }])
            ->withSum(['orders' => function ($query) {
                $query->where('order_status', '!=', 'cancelled');
            }], 'grand_total')
            ->orderByDesc('orders_count')
            ->get();

        $fileName = 'customers_report_' . date('Y-m-d_His') . '.csv';

        return $this->streamCsvDownload($fileName, [
            'Customer',
            'Email',
            'Phone',
            'Total Orders',
            'Total Spent',
            'Registered On',
        ], function ($file) use ($customers) {
            foreach ($customers as $customer) {
                fputcsv($file, [
                    $customer->name,
                    $customer->email,
                    $customer->phone ?? '',
                    $customer->orders_count ?? 0,
                    number_format((float) ($customer->orders_sum_grand_total ?? 0), 2, '.', ''),
                    optional($customer->created_at)->format('d-m-Y'),
                ]);
            }
        });
    }
}