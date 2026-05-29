<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Models\Product;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function index(Request $request)
    {
        $query = Review::with('user', 'product');

        // Search filter (user name, email, or review content)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('review', 'like', "%{$search}%")
                  ->orWhereHas('user', function($userQuery) use ($search) {
                      $userQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        // Product filter
        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        // Rating filter
        if ($request->filled('rating')) {
            $rating = $request->rating;
            if ($rating == '5') {
                $query->where('rating', 5);
            } else {
                $query->where('rating', '>=', $rating);
            }
        }

        // Status filter
        if ($request->filled('status') && in_array($request->status, ['0', '1'])) {
            $query->where('status', $request->status);
        }

        // Date filter
        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        }

        // Apply sorting
        $query->latest();

        // Paginate with preserving query parameters
        $reviews = $query->paginate(10)->withQueryString();

        // Get products for filter dropdown
        $products = Product::orderBy('name')->get();

        return view('admin.review.index', compact('reviews', 'products'));
    }

    public function status(Request $request, Review $review)
    {
        // Toggle status if no status provided in request
        $status = $request->has('status') 
            ? $request->status 
            : ($review->status == 1 ? 0 : 1);

        $review->update([
            'status' => $status
        ]);

        $message = $review->status == 1 
            ? 'Review approved successfully' 
            : 'Review marked as pending';

        return back()->with('success', $message);
    }

    public function destroy(Review $review)
    {
        // Delete Review Image
        if ($review->image && file_exists(public_path('backend/assets/images/review/' . $review->image))) {
            unlink(public_path('backend/assets/images/review/' . $review->image));
        }

        $review->delete();

        return back()->with('success', 'Review deleted successfully');
    }

    public function export(Request $request)
    {
        $query = Review::with('user', 'product');

        // Apply same filters as index method
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('review', 'like', "%{$search}%")
                  ->orWhereHas('user', function($userQuery) use ($search) {
                      $userQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        if ($request->filled('rating')) {
            $rating = $request->rating;
            if ($rating == '5') {
                $query->where('rating', 5);
            } else {
                $query->where('rating', '>=', $rating);
            }
        }

        if ($request->filled('status') && in_array($request->status, ['0', '1'])) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        }

        $reviews = $query->latest()->get();

        // Create CSV
        $filename = 'reviews_export_' . date('Y-m-d_His') . '.csv';
        $handle = fopen('php://temp', 'w');

        // Add CSV headers
        fputcsv($handle, [
            'ID',
            'User Name',
            'User Email',
            'Product Name',
            'Rating',
            'Review',
            'Has Image',
            'Status',
            'Created Date',
            'Created Time'
        ]);

        // Add data rows
        foreach ($reviews as $review) {
            fputcsv($handle, [
                $review->id,
                $review->user->name ?? 'N/A',
                $review->user->email ?? 'N/A',
                $review->product->name ?? 'N/A',
                $review->rating . ' ★',
                $review->review,
                $review->image ? 'Yes' : 'No',
                $review->status == 1 ? 'Approved' : 'Pending',
                $review->created_at->format('Y-m-d'),
                $review->created_at->format('H:i:s')
            ]);
        }

        // Reset pointer and get content
        rewind($handle);
        $csvContent = stream_get_contents($handle);
        fclose($handle);

        // Return as download
        return response($csvContent, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    // Optional: Bulk action method for approving/rejecting multiple reviews
    public function bulkAction(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:reviews,id',
            'action' => 'required|in:approve,pending,delete'
        ]);

        $count = 0;

        switch ($request->action) {
            case 'approve':
                $count = Review::whereIn('id', $request->ids)->update(['status' => 1]);
                $message = $count . ' review(s) approved successfully';
                break;
            case 'pending':
                $count = Review::whereIn('id', $request->ids)->update(['status' => 0]);
                $message = $count . ' review(s) marked as pending';
                break;
            case 'delete':
                // Delete images first
                $reviews = Review::whereIn('id', $request->ids)->get();
                foreach ($reviews as $review) {
                    if ($review->image && file_exists(public_path('backend/assets/images/review/' . $review->image))) {
                        unlink(public_path('backend/assets/images/review/' . $review->image));
                    }
                }
                $count = Review::whereIn('id', $request->ids)->delete();
                $message = $count . ' review(s) deleted successfully';
                break;
        }

        return back()->with('success', $message);
    }
}