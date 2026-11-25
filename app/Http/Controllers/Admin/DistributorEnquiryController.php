<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DistributorEnquiry;
use Illuminate\Http\Request;

class DistributorEnquiryController extends Controller
{
    /**
     * Display a listing of distributor enquiries.
     */
    public function index(Request $request)
    {
        $query = DistributorEnquiry::query();

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('company_name', 'like', "%{$search}%")
                    ->orWhere('contact_person', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('mobile_no', 'like', "%{$search}%")
                    ->orWhere('country_city', 'like', "%{$search}%")
                    ->orWhere('message', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status') && in_array($request->status, ['0', '1'], true)) {
            $query->where('status', (int) $request->status);
        }

        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        }

        $distributorEnquiries = $query->orderBy('id', 'DESC')->paginate(10);

        return view('admin.distributor_enquiry.index', compact('distributorEnquiries'));
    }

    /**
     * Export distributor enquiries to CSV.
     */
    public function export(Request $request)
    {
        $query = DistributorEnquiry::query();

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('company_name', 'like', "%{$search}%")
                    ->orWhere('contact_person', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('mobile_no', 'like', "%{$search}%")
                    ->orWhere('country_city', 'like', "%{$search}%")
                    ->orWhere('message', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status') && in_array($request->status, ['0', '1'], true)) {
            $query->where('status', (int) $request->status);
        }

        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        }

        $enquiries = $query->orderBy('id', 'DESC')->get();

        $filename = 'distributor_enquiries_' . date('Y-m-d_His') . '.csv';
        $handle = fopen('php://temp', 'w');

        // CSV Header
        fputcsv($handle, [
            'ID',
            'Company Name',
            'Contact Person',
            'Email',
            'Mobile No',
            'Country / City',
            'Message',
            'Status',
            'Submitted Date',
            'Submitted Time',
        ]);

        foreach ($enquiries as $enquiry) {
            fputcsv($handle, [
                $enquiry->id,
                $enquiry->company_name,
                $enquiry->contact_person,
                $enquiry->email,
                $enquiry->mobile_no,
                $enquiry->country_city,
                $enquiry->message,
                $enquiry->status ? 'Read' : 'Unread',
                $enquiry->created_at ? $enquiry->created_at->format('Y-m-d') : '-',
                $enquiry->created_at ? $enquiry->created_at->format('H:i:s') : '-',
            ]);
        }

        rewind($handle);
        $csvContent = stream_get_contents($handle);
        fclose($handle);

        return response($csvContent, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Display the specified distributor enquiry details.
     */
    public function show(DistributorEnquiry $distributorEnquiry)
    {
        // Mark as read when viewed if currently unread
        if (!$distributorEnquiry->status) {
            $distributorEnquiry->update(['status' => true]);
        }

        return view('admin.distributor_enquiry.show', compact('distributorEnquiry'));
    }

    /**
     * Show the form for editing the specified enquiry.
     */
    public function edit(DistributorEnquiry $distributorEnquiry)
    {
        return view('admin.distributor_enquiry.edit', compact('distributorEnquiry'));
    }

    /**
     * Update the specified distributor enquiry in storage.
     */
    public function update(Request $request, DistributorEnquiry $distributorEnquiry)
    {
        $request->validate([
            'company_name'   => 'required|string|max:255',
            'contact_person' => 'required|string|max:255',
            'email'          => 'required|email|max:255',
            'mobile_no'      => 'required|string|max:50',
            'country_city'   => 'required|string|max:255',
            'message'        => 'nullable|string',
            'status'         => 'required',
        ]);

        $distributorEnquiry->update([
            'company_name'   => $request->company_name,
            'contact_person' => $request->contact_person,
            'email'          => $request->email,
            'mobile_no'      => $request->mobile_no,
            'country_city'   => $request->country_city,
            'message'        => $request->message,
            'status'         => (bool) $request->status,
        ]);

        return redirect()
            ->route('admin.distributor-enquiries.index')
            ->with('success', 'Distributor enquiry updated successfully');
    }

    /**
     * Toggle status between Read and Unread.
     */
    public function toggleStatus(DistributorEnquiry $distributorEnquiry)
    {
        $distributorEnquiry->update([
            'status' => !$distributorEnquiry->status,
        ]);

        if (request()->ajax() || request()->wantsJson()) {
            return response()->json([
                'status'     => true,
                'new_status' => $distributorEnquiry->status,
                'message'    => 'Status updated to ' . ($distributorEnquiry->status ? 'Read' : 'Unread'),
            ]);
        }

        return redirect()->back()->with('success', 'Status updated successfully');
    }

    /**
     * Remove the specified distributor enquiry.
     */
    public function destroy(DistributorEnquiry $distributorEnquiry)
    {
        $distributorEnquiry->delete();

        return redirect()
            ->route('admin.distributor-enquiries.index')
            ->with('success', 'Distributor enquiry deleted successfully');
    }
}
