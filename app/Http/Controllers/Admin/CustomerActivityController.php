<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Wishlist;
use App\Models\BuyNow;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CustomerActivityController extends Controller
{
    /**
     * Display a listing of users with Cart, Wishlist, and Buy Now items.
     * Matches exact format of User Cart Details list.
     */
    public function index(Request $request)
    {
        $search = trim((string) $request->input('search', ''));
        $fromDate = $request->input('from_date', '');
        $toDate = $request->input('to_date', '');

        // Query users having at least one Cart, Wishlist, or Buy Now item
        $query = User::query()
            ->where(function ($q) {
                $q->whereHas('cartItems')
                  ->orWhereHas('wishlistItems')
                  ->orWhereHas('buyNowItems');
            })
            ->withCount([
                'cartItems as cart_count',
                'wishlistItems as wishlist_count',
                'buyNowItems as buy_now_count'
            ]);

        // Search Filter (name, email, phone, id)
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('id', $search);
            });
        }

        // Date Range Filters
        if (!empty($fromDate)) {
            $date = Carbon::parse($fromDate)->startOfDay();
            $query->where(function ($q) use ($date) {
                $q->whereHas('cartItems', fn($sq) => $sq->where('updated_at', '>=', $date))
                  ->orWhereHas('wishlistItems', fn($sq) => $sq->where('updated_at', '>=', $date))
                  ->orWhereHas('buyNowItems', fn($sq) => $sq->where('updated_at', '>=', $date));
            });
        }

        if (!empty($toDate)) {
            $date = Carbon::parse($toDate)->endOfDay();
            $query->where(function ($q) use ($date) {
                $q->whereHas('cartItems', fn($sq) => $sq->where('updated_at', '<=', $date))
                  ->orWhereHas('wishlistItems', fn($sq) => $sq->where('updated_at', '<=', $date))
                  ->orWhereHas('buyNowItems', fn($sq) => $sq->where('updated_at', '<=', $date));
            });
        }

        // Compute latest activity across carts, wishlists, and buy_nows
        $query->select('users.*')
            ->selectSub(
                DB::table(DB::raw("(
                    SELECT user_id, updated_at FROM carts WHERE user_id IS NOT NULL
                    UNION ALL
                    SELECT user_id, updated_at FROM wishlists WHERE user_id IS NOT NULL
                    UNION ALL
                    SELECT user_id, updated_at FROM buy_nows WHERE user_id IS NOT NULL
                ) as user_activities"))
                    ->select(DB::raw('MAX(updated_at)'))
                    ->whereColumn('user_activities.user_id', 'users.id'),
                'last_activity'
            )
            ->orderByRaw('COALESCE(last_activity, users.updated_at, users.created_at) DESC');

        $users = $query->paginate(20)->withQueryString();

        return view('admin.customer-activities.index', compact('users', 'search', 'fromDate', 'toDate'));
    }

    /**
     * Display the detailed cart, wishlist, and buy now items for a specific user.
     * Matches exact format of User Cart Details view.
     */
    public function show($id)
    {
        $user = User::findOrFail($id);

        $cartItems = Cart::where('user_id', $user->id)
            ->with(['product', 'variant'])
            ->latest('updated_at')
            ->get();

        $wishlistItems = Wishlist::where('user_id', $user->id)
            ->with(['product', 'variant'])
            ->latest('updated_at')
            ->get();

        $buyNowItems = BuyNow::where('user_id', $user->id)
            ->with(['product', 'variant'])
            ->latest('updated_at')
            ->get();

        $cartCount = $cartItems->count();
        $wishlistCount = $wishlistItems->count();
        $buyNowCount = $buyNowItems->count();

        return view('admin.customer-activities.show', compact(
            'user',
            'cartItems',
            'wishlistItems',
            'buyNowItems',
            'cartCount',
            'wishlistCount',
            'buyNowCount'
        ));
    }

    /**
     * Export users cart details as CSV.
     */
    public function export(Request $request)
    {
        $search = trim((string) $request->input('search', ''));
        $fromDate = $request->input('from_date', '');
        $toDate = $request->input('to_date', '');

        $query = User::query()
            ->where(function ($q) {
                $q->whereHas('cartItems')
                  ->orWhereHas('wishlistItems')
                  ->orWhereHas('buyNowItems');
            })
            ->withCount([
                'cartItems as cart_count',
                'wishlistItems as wishlist_count',
                'buyNowItems as buy_now_count'
            ]);

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('id', $search);
            });
        }

        if (!empty($fromDate)) {
            $date = Carbon::parse($fromDate)->startOfDay();
            $query->where(function ($q) use ($date) {
                $q->whereHas('cartItems', fn($sq) => $sq->where('updated_at', '>=', $date))
                  ->orWhereHas('wishlistItems', fn($sq) => $sq->where('updated_at', '>=', $date))
                  ->orWhereHas('buyNowItems', fn($sq) => $sq->where('updated_at', '>=', $date));
            });
        }

        if (!empty($toDate)) {
            $date = Carbon::parse($toDate)->endOfDay();
            $query->where(function ($q) use ($date) {
                $q->whereHas('cartItems', fn($sq) => $sq->where('updated_at', '<=', $date))
                  ->orWhereHas('wishlistItems', fn($sq) => $sq->where('updated_at', '<=', $date))
                  ->orWhereHas('buyNowItems', fn($sq) => $sq->where('updated_at', '<=', $date));
            });
        }

        $query->select('users.*')
            ->selectSub(
                DB::table(DB::raw("(
                    SELECT user_id, updated_at FROM carts WHERE user_id IS NOT NULL
                    UNION ALL
                    SELECT user_id, updated_at FROM wishlists WHERE user_id IS NOT NULL
                    UNION ALL
                    SELECT user_id, updated_at FROM buy_nows WHERE user_id IS NOT NULL
                ) as user_activities"))
                    ->select(DB::raw('MAX(updated_at)'))
                    ->whereColumn('user_activities.user_id', 'users.id'),
                'last_activity'
            )
            ->orderByRaw('COALESCE(last_activity, users.updated_at, users.created_at) DESC');

        $users = $query->get();

        $filename = 'user_cart_details_' . date('Y-m-d_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($users) {
            $file = fopen('php://output', 'w');
            fputcsv($file, [
                'ID',
                'User Name',
                'Email',
                'Phone',
                'Last Activity Date',
                'Cart Items',
                'Wishlist Items',
                'Buy Now Items',
            ]);

            foreach ($users as $user) {
                fputcsv($file, [
                    $user->id,
                    $user->name,
                    $user->email,
                    $user->phone ?? 'N/A',
                    $user->last_activity ? Carbon::parse($user->last_activity)->setTimezone('Asia/Kolkata')->format('d M Y, h:i A') . ' IST' : 'N/A',
                    $user->cart_count,
                    $user->wishlist_count,
                    $user->buy_now_count,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
