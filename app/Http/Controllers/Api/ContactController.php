<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\ContactEnquiryAdminMail;
use App\Mail\ContactEnquiryUserMail;
use App\Models\ContactEnquiry;
use App\Models\ContactSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class ContactController extends Controller
{
    /**
     * Get contact settings/details.
     */
    public function contactDetails()
    {
        $contact = ContactSetting::first();

        return response()->json([
            'status'  => true,
            'message' => 'Contact details fetched successfully.',
            'data'    => [
                'address'         => $contact->contact_address ?? '',
                'phone'           => $contact->contact_phone ?? '',
                'email'           => $contact->contact_email ?? '',
                'business_days'   => $contact->business_days ?? '',
                'business_hours'  => $contact->business_hours ?? '',
                'support_message' => $contact->support_message ?? '',
                'support_timings' => $contact->support_timings ?? '',
            ]
        ], 200);
    }

    /**
     * Alias for contactDetails.
     */
    public function index()
    {
        return $this->contactDetails();
    }

    /**
     * Submit a new contact enquiry.
     */
    public function store(Request $request)
    {
        // Support common alternative parameter names
        $fullName  = $request->input('full_name') ?? $request->input('name');
        $contactNo = $request->input('contact_no') ?? $request->input('phone') ?? $request->input('mobile');
        $comment   = $request->input('comment') ?? $request->input('message');

        $input = [
            'full_name'  => $fullName,
            'email'      => $request->input('email'),
            'contact_no' => $contactNo,
            'comment'    => $comment,
        ];

        $validator = Validator::make($input, [
            'full_name'  => 'required|string|max:255',
            'email'      => 'required|email|max:255',
            'contact_no' => 'required|string|max:20',
            'comment'    => 'nullable|string|max:2000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => $validator->errors()->first(),
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            $enquiry = ContactEnquiry::create([
                'full_name'  => $input['full_name'],
                'email'      => $input['email'],
                'contact_no' => $input['contact_no'],
                'comment'    => $input['comment'],
                'status'     => false,
            ]);

            // Send confirmation email to user
            try {
                Mail::to($enquiry->email)->send(new ContactEnquiryUserMail($enquiry));
            } catch (\Throwable $e) {
                Log::error('Contact enquiry user email failed: ' . $e->getMessage(), [
                    'enquiry_id' => $enquiry->id,
                    'email'      => $enquiry->email,
                ]);
            }

            // Send notification email to admin
            $adminEmail = config('mail.admin_order_email') ?: config('mail.from.address');
            if ($adminEmail) {
                try {
                    Mail::to($adminEmail)->send(new ContactEnquiryAdminMail($enquiry));
                } catch (\Throwable $e) {
                    Log::error('Contact enquiry admin email failed: ' . $e->getMessage(), [
                        'enquiry_id' => $enquiry->id,
                        'email'      => $adminEmail,
                    ]);
                }
            }

            return response()->json([
                'status'  => true,
                'message' => 'Thank you! Your message has been sent successfully.',
                'data'    => $enquiry,
            ], 201);

        } catch (\Throwable $e) {
            Log::error('Contact enquiry creation failed: ' . $e->getMessage());

            return response()->json([
                'status'  => false,
                'message' => 'Something went wrong while submitting your enquiry. Please try again later.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get list of enquiries (optional search/filter).
     */
    public function enquiries(Request $request)
    {
        $query = ContactEnquiry::query();

        if ($request->filled('email')) {
            $query->where('email', $request->email);
        }

        if ($request->filled('contact_no')) {
            $query->where('contact_no', $request->contact_no);
        }

        $enquiries = $query->latest()->paginate(10);

        return response()->json([
            'status' => true,
            'data'   => $enquiries,
        ], 200);
    }
}
