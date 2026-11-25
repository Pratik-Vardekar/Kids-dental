<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\DistributorEnquiryAdminMail;
use App\Mail\DistributorEnquiryUserMail;
use App\Models\DistributorEnquiry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class DistributorEnquiryController extends Controller
{
    /**
     * Store a new distributor enquiry.
     */
    public function store(Request $request)
    {
        // Support common alternative parameter names from frontend
        $companyName   = $request->input('company_name') ?? $request->input('company');
        $contactPerson = $request->input('contact_person') ?? $request->input('name') ?? $request->input('full_name') ?? $request->input('person_name');
        $email         = $request->input('email');
        $mobileNo      = $request->input('mobile_no') ?? $request->input('phone') ?? $request->input('mobile') ?? $request->input('contact_no');
        $countryCity   = $request->input('country_city') ?? $request->input('city') ?? $request->input('country') ?? $request->input('location');
        $message       = $request->input('message') ?? $request->input('comment') ?? $request->input('notes');

        $input = [
            'company_name'   => $companyName,
            'contact_person' => $contactPerson,
            'email'          => $email,
            'mobile_no'      => $mobileNo,
            'country_city'   => $countryCity,
            'message'        => $message,
        ];

        $validator = Validator::make($input, [
            'company_name'   => 'required|string|max:255',
            'contact_person' => 'required|string|max:255',
            'email'          => 'required|email|max:255',
            'mobile_no'      => 'required|string|max:50',
            'country_city'   => 'required|string|max:255',
            'message'        => 'nullable|string|max:3000',
        ], [
            'company_name.required'   => 'Company name is required.',
            'contact_person.required' => 'Contact person name is required.',
            'email.required'          => 'Email address is required.',
            'email.email'             => 'Please provide a valid email address.',
            'mobile_no.required'      => 'Mobile number is required.',
            'country_city.required'   => 'Country/City is required.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => $validator->errors()->first(),
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            $enquiry = DistributorEnquiry::create([
                'company_name'   => trim($input['company_name']),
                'contact_person' => trim($input['contact_person']),
                'email'          => trim($input['email']),
                'mobile_no'      => trim($input['mobile_no']),
                'country_city'   => trim($input['country_city']),
                'message'        => $input['message'] ? trim($input['message']) : null,
                'status'         => false,
            ]);

            // Send confirmation email to applicant (non-blocking)
            try {
                if ($enquiry->email) {
                    Mail::to($enquiry->email)->send(new DistributorEnquiryUserMail($enquiry));
                }
            } catch (\Throwable $e) {
                Log::error('Distributor enquiry user email failed: ' . $e->getMessage(), [
                    'enquiry_id' => $enquiry->id,
                    'email'      => $enquiry->email,
                ]);
            }

            // Send notification email to admin (non-blocking)
            $adminEmail = config('mail.admin_order_email') ?: config('mail.from.address');
            if ($adminEmail) {
                try {
                    Mail::to($adminEmail)->send(new DistributorEnquiryAdminMail($enquiry));
                } catch (\Throwable $e) {
                    Log::error('Distributor enquiry admin email failed: ' . $e->getMessage(), [
                        'enquiry_id' => $enquiry->id,
                        'email'      => $adminEmail,
                    ]);
                }
            }

            return response()->json([
                'status'  => true,
                'message' => 'Thank you! Your distributor enquiry has been submitted successfully.',
                'data'    => $enquiry,
            ], 201);

        } catch (\Throwable $e) {
            Log::error('Distributor enquiry creation failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status'  => false,
                'message' => 'Something went wrong while submitting your enquiry. Please try again later.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get list of distributor enquiries (optional API endpoint).
     */
    public function index(Request $request)
    {
        $query = DistributorEnquiry::query();

        if ($request->filled('email')) {
            $query->where('email', $request->email);
        }

        if ($request->filled('mobile_no')) {
            $query->where('mobile_no', $request->mobile_no);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('company_name', 'like', "%{$search}%")
                  ->orWhere('contact_person', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('mobile_no', 'like', "%{$search}%")
                  ->orWhere('country_city', 'like', "%{$search}%");
            });
        }

        $enquiries = $query->latest('id')->paginate(10);

        return response()->json([
            'status' => true,
            'message' => 'Distributor enquiries fetched successfully.',
            'data'   => $enquiries,
        ], 200);
    }
}
