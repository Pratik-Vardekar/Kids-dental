<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactEnquiry;
use Illuminate\Http\Request;

class ContactEnquiryController extends Controller
{
    public function index(Request $request)
    {
        $query = ContactEnquiry::query();

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('contact_no', 'like', "%{$search}%")
                    ->orWhere('comment', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status') && in_array($request->status, ['0', '1'], true)) {
            $query->where('status', (int) $request->status);
        }

        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        }

        $contactEnquiries = $query->orderBy('id', 'DESC')->paginate(10);

        return view('admin.contact_enquirey.index', compact('contactEnquiries'));
    }

    public function export(Request $request)
    {
        $query = ContactEnquiry::query();

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('contact_no', 'like', "%{$search}%")
                    ->orWhere('comment', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status') && in_array($request->status, ['0', '1'], true)) {
            $query->where('status', (int) $request->status);
        }

        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        }

        $enquiries = $query->orderBy('id', 'DESC')->get();

        $filename = 'contact_enquiries_' . date('Y-m-d_His') . '.csv';
        $handle = fopen('php://temp', 'w');

        // CSV Header
        fputcsv($handle, [
            'ID',
            'Full Name',
            'Email',
            'Contact No',
            'Comment',
            'Status',
            'Submitted Date',
            'Submitted Time',
        ]);

        foreach ($enquiries as $enquiry) {
            fputcsv($handle, [
                $enquiry->id,
                $enquiry->full_name,
                $enquiry->email,
                $enquiry->contact_no,
                $enquiry->comment,
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

    public function edit(ContactEnquiry $contactEnquiry)
    {
        return view('admin.contact_enquirey.edit', compact('contactEnquiry'));
    }

    public function update(Request $request, ContactEnquiry $contactEnquiry)
    {
        $request->validate([
            'full_name'  => 'required|string|max:255',
            'email'      => 'required|email',
            'contact_no' => 'required',
            'comment'    => 'nullable|string',
            'status'     => 'required',
        ]);

        $contactEnquiry->update([
            'full_name'  => $request->full_name,
            'email'      => $request->email,
            'contact_no' => $request->contact_no,
            'comment'    => $request->comment,
            'status'     => (bool) $request->status,
        ]);

        return redirect()
            ->route('admin.contact-enquiries.index')
            ->with('success', 'Contact enquiry updated successfully');
    }
}
