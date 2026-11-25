<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Cart;
use App\Models\BuyNow;
use App\Mail\UserWelcomeMail;
use App\Mail\UserRegistrationAdminMail;
use App\Mail\UserOtpMail;
use App\Mail\UserResetPasswordMail;
use App\Services\SmsAlertService;
use Google\Client as GoogleClient;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;

class AuthController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register API (Email, Name, Phone & Password)
    |--------------------------------------------------------------------------
    */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->whereNull('deleted_at'),
            ],
            'phone' => 'nullable|string|max:20',
            'password' => 'required|string|min:6',
            'password_confirmation' => 'nullable|string|same:password',
        ], [
            'email.unique' => 'This email is already registered.',
            'password.min' => 'Password must be at least 6 characters.',
            'password_confirmation.same' => 'Password confirmation does not match.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        // Clean up soft-deleted record with this email to avoid unique key conflicts
        User::onlyTrashed()->where('email', $request->email)->forceDelete();

        // Format phone number
        $phone = $request->phone;
        if ($phone) {
            $digits = preg_replace('/\D+/', '', $phone);
            if (str_starts_with($digits, '91') && strlen($digits) > 10) {
                $digits = '91' . ltrim(substr($digits, 2), '0');
            } else {
                $digits = ltrim($digits, '0');
            }
            $phone = str_starts_with($digits, '91') ? '+' . $digits : '+91' . $digits;
        }

        // Generate 6-digit verification OTP
        $otp = (string) rand(100000, 999999);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $phone ?: null,
            'password' => Hash::make($request->password),
            'status' => 1,
            'role' => 'user',
            'role_id' => 5, // Default User Role
            'auth_type' => 'email',
            'otp' => $otp,
            'otp_expires_at' => now()->addMinutes(10),
            'is_phone_verified' => 0,
        ]);

        // Send OTP ONLY to user's email address
        try {
            Mail::to($user->email)->send(new UserOtpMail($user, $otp));
        } catch (\Throwable $e) {
            Log::error('User verification OTP email failed: ' . $e->getMessage(), ['user_id' => $user->id]);
        }

        // Send admin notification email
        $adminEmail = config('mail.admin_order_email') ?: config('mail.from.address');
        if ($adminEmail) {
            try {
                Mail::to($adminEmail)->send(new UserRegistrationAdminMail($user));
            } catch (\Throwable $e) {
                Log::error('User registration admin notification failed: ' . $e->getMessage(), ['user_id' => $user->id]);
            }
        }

        if ($user && method_exists($user, 'recordLogin')) {
            $user->recordLogin();
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => true,
            'message' => 'Registration successful. A verification OTP has been sent to your email.',
            'token' => $token,
            'user' => $user->makeHidden(['otp', 'otp_expires_at', 'plain_password']),
        ], 201);
    }

    /*
    |--------------------------------------------------------------------------
    | Login API (Email or Phone + Password)
    |--------------------------------------------------------------------------
    */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string',
            'password' => 'required|string',
        ], [
            'email.required' => 'Email or phone number is required.',
            'password.required' => 'Password is required.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $loginInput = trim($request->email);

        // Determine if login input is email or phone
        if (filter_var($loginInput, FILTER_VALIDATE_EMAIL)) {
            $user = User::where('email', $loginInput)->first();
        } else {
            // Strip non-digits to match phone
            $digits = preg_replace('/\D+/', '', $loginInput);
            $raw10 = substr($digits, -10);

            $user = User::where(function ($query) use ($loginInput, $digits, $raw10) {
                $query->where('phone', $loginInput)
                    ->orWhere('phone', '+' . $digits)
                    ->orWhere('phone', '+91' . $raw10)
                    ->orWhere('phone', $raw10);
            })->first();
        }

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid email/phone or password',
            ], 401);
        }

        if ((int) $user->status !== 1) {
            return response()->json([
                'status' => false,
                'message' => 'Your account is inactive. Please contact customer support.',
            ], 403);
        }

        if ($user && method_exists($user, 'recordLogin')) {
            $user->recordLogin();
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => true,
            'message' => 'Login successful',
            'token' => $token,
            'user' => $user->makeHidden(['otp', 'otp_expires_at', 'plain_password']),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Get User Profile API
    |--------------------------------------------------------------------------
    */
    public function profile(Request $request)
    {
        $user = auth('sanctum')->user();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthenticated',
            ], 401);
        }

        // Attach helpful summary stats for frontend
        $userData = $user->toArray();
        $userData['orders_count'] = $user->orders()->count();
        $userData['wishlist_count'] = $user->wishlistItems()->count();
        $userData['cart_count'] = $user->cartItems()->sum('quantity');

        return response()->json([
            'status' => true,
            'user' => $userData,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Update User Profile API
    |--------------------------------------------------------------------------
    */
    public function updateProfile(Request $request)
    {
        $user = auth('sanctum')->user();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthenticated',
            ], 401);
        }

        // Check which fields the API actually receives
        \Log::info('Profile update received fields', [
            'fields' => array_keys($request->all()),
            'has_email' => $request->exists('email'),
            'has_phone' => $request->exists('phone'),
        ]);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')
                    ->ignore($user->id)
                    ->whereNull('deleted_at'),
            ],
            'phone' => 'sometimes|nullable|string|max:20',
            'avatar' => 'sometimes|nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $user->name = $request->input('name');
            $user->email = $request->input('email');

            // Update phone only when the request includes the phone field
            if ($request->exists('phone')) {
                $user->phone = $request->input('phone');
            }

            // Upload avatar if provided
            if ($request->hasFile('avatar')) {
                $uploadPath = public_path('uploads/avatars');

                if (!is_dir($uploadPath)) {
                    mkdir($uploadPath, 0755, true);
                }

                $avatar = $request->file('avatar');

                $filename = 'avatar_' . $user->id . '_' . time() . '.' .
                    $avatar->getClientOriginalExtension();

                $avatar->move($uploadPath, $filename);

                $user->avatar = 'uploads/avatars/' . $filename;
            }

            $user->saveOrFail();
            $user->refresh();

            return response()->json([
                'status' => true,
                'message' => 'Profile updated successfully',
                'user' => $user->makeHidden([
                    'password',
                    'otp',
                    'otp_expires_at',
                    'plain_password',
                ]),
            ]);
        } catch (\Throwable $e) {
            \Log::error('Profile update failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Failed to update profile.',
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Change Password API (Authenticated)
    |--------------------------------------------------------------------------
    */
    public function changePassword(Request $request)
    {
        $user = auth('sanctum')->user();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthenticated',
            ], 401);
        }

        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:6|confirmed',
        ], [
            'new_password.confirmed' => 'New password confirmation does not match.',
            'new_password.min' => 'New password must be at least 6 characters.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'status' => false,
                'message' => 'Current password is incorrect',
            ], 422);
        }

        $user->update([
            'password' => Hash::make($request->new_password),
            'plain_password' => null,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Password updated successfully',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Forgot Password API
    |--------------------------------------------------------------------------
    */
    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ], [
            'email.required' => 'Email address is required.',
            'email.email' => 'Please provide a valid email address.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'We could not find an account with this email address.',
            ], 404);
        }

        // Determine Frontend Base URL (Dynamic Origin > Input redirect/frontend_url > Referer > Env > Localhost)
        $frontendUrl = $request->input('frontend_url')
            ?: $request->input('redirect_url')
            ?: $request->header('origin');

        if (!$frontendUrl && $request->header('referer')) {
            $parsed = parse_url($request->header('referer'));
            if (!empty($parsed['scheme']) && !empty($parsed['host'])) {
                $frontendUrl = $parsed['scheme'] . '://' . $parsed['host'] . (!empty($parsed['port']) ? ':' . $parsed['port'] : '');
            }
        }

        if (!$frontendUrl) {
            $envUrl = env('FRONTEND_URL');
            if ($envUrl && !str_contains($envUrl, 'vercel.app')) {
                $frontendUrl = $envUrl;
            } else {
                $frontendUrl = 'http://localhost:3000';
            }
        }

        $frontendUrl = rtrim($frontendUrl, '/');

        // Generate token if possible
        $token = null;
        try {
            $token = Password::createToken($user);
        } catch (\Throwable $e) {
            Log::warning('Password reset token generation error: ' . $e->getMessage());
        }

        // Construct reset password link: e.g. http://localhost:3000/reset-password?email=...
        $resetUrl = $frontendUrl . '/reset-password?email=' . str_replace('%40', '@', urlencode($user->email));

        // Send branded reset password email
        try {
            Mail::to($user->email)->send(new UserResetPasswordMail($user, $resetUrl, $token));
        } catch (\Throwable $e) {
            Log::error('Failed to send reset password email: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Failed to send password reset email. Please try again later.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }

        return response()->json([
            'status' => true,
            'message' => 'Password reset link has been sent to your email address.',
            'reset_url' => $resetUrl,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Reset Password API
    |--------------------------------------------------------------------------
    */

    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:6|confirmed',
            'password_confirmation' => 'required|string|min:6',
        ], [
            'password.required' => 'Password is required.',
            'password.min' => 'Password must be at least 6 characters.',
            'password.confirmed' => 'Password confirmation does not match.',
            'password_confirmation.required' => 'Confirm password is required.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'User not found.',
            ], 404);
        }

        $user->forceFill([
            'password' => Hash::make($request->password),
            'plain_password' => null,
            'remember_token' => Str::random(60),
        ])->save();

        return response()->json([
            'status' => true,
            'message' => 'Password reset successfully.',
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Send OTP (Phone Auth Flow - Deprecated)
    |--------------------------------------------------------------------------
    */
    // public function auth(Request $request)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'phone' => 'required|numeric|digits:10',
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => $validator->errors()->first(),
    //         ], 422);
    //     }

    //     $otp = rand(100000, 999999);

    //     $user = User::where('phone', $request->phone)->first();

    //     if ($user) {
    //         $flow = 'login';

    //         $user->update([
    //             'otp' => $otp,
    //             'otp_expires_at' => now()->addMinutes(5),
    //             'auth_type' => $flow,
    //         ]);
    //     } else {
    //         $flow = 'register';

    //         $dummyEmail = 'user_' . uniqid() . '_' . time() . '@temp.com';

    //         $user = User::create([
    //             'name' => 'User_' . rand(1000, 9999),
    //             'email' => $dummyEmail,
    //             'phone' => $request->phone,
    //             'plain_password' => null,
    //             'password' => Hash::make(Str::random(16)),
    //             'status' => 1,
    //             'role' => 'user',
    //             'role_id' => 5,
    //             'otp' => $otp,
    //             'otp_expires_at' => now()->addMinutes(5),
    //             'is_phone_verified' => 0,
    //             'auth_type' => $flow,
    //         ]);
    //     }

    //     $message = "Dear User, Your verification OTP code is {$otp}.";
    //     SmsAlertService::send($request->phone, $message);

    //     return response()->json([
    //         'status' => true,
    //         'message' => 'OTP sent successfully',
    //         'flow' => $flow,
    //     ]);
    // }

    /*
    |--------------------------------------------------------------------------
    | Verify OTP
    |--------------------------------------------------------------------------
    */
    // public function verifyOtp(Request $request)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'phone' => 'required|numeric|digits_between:1,15',
    //         'otp' => 'required|digits:6',
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => $validator->errors()->first(),
    //         ], 422);
    //     }

    //     $user = User::where('phone', $request->phone)
    //         ->where('otp', $request->otp)
    //         ->first();

    //     if (!$user) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'Invalid OTP',
    //         ], 401);
    //     }

    //     if (!$user->otp_expires_at || now()->gt($user->otp_expires_at)) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'OTP expired',
    //         ], 401);
    //     }

    //     $flow = $user->auth_type;

    //     $user->update([
    //         'otp' => null,
    //         'otp_expires_at' => null,
    //         'is_phone_verified' => 1,
    //     ]);

    //     $userData = $user->toArray();
    //     $userData['auth_type'] = $flow;

    //     $token = $user->createToken('auth_token')->plainTextToken;

    //     return response()->json([
    //         'status' => true,
    //         'message' => $flow === 'login' ? 'Login successful' : 'Registration successful',
    //         'flow' => $flow,
    //         'user' => $userData,
    //         'token' => $token,
    //     ]);
    // }

    /*
    |--------------------------------------------------------------------------
    | Resend OTP
    |--------------------------------------------------------------------------
    */
    // public function resendOtp(Request $request)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'phone' => 'required|numeric|digits_between:1,15',
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => $validator->errors()->first(),
    //         ], 422);
    //     }

    //     $user = User::where('phone', $request->phone)->first();

    //     if (!$user) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'User not found',
    //         ], 404);
    //     }

    //     $otp = rand(100000, 999999);

    //     $user->update([
    //         'otp' => $otp,
    //         'otp_expires_at' => now()->addMinutes(5),
    //     ]);

    //     $message = "Dear User, Your verification OTP code is {$otp}.";
    //     SmsAlertService::send($request->phone, $message);

    //     return response()->json([
    //         'status' => true,
    //         'message' => 'OTP resent successfully',
    //     ]);
    // }

    /*
    |--------------------------------------------------------------------------
    | Logout API
    |--------------------------------------------------------------------------
    */
    public function logout(Request $request)
    {
        $user = auth('sanctum')->user() ?? auth()->user();
        if (!$user && $request->has('user_id') && !empty($request->user_id)) {
            $user = User::find($request->user_id);
        }

        $sessionId = $request->header('session-id')
            ?: $request->header('Session-Id')
            ?: $request->header('session_id')
            ?: $request->input('session_id')
            ?: $request->input('sessionId')
            ?: $request->input('session-id');

        // Empty user's Cart and BuyNow items upon logout
        if ($user) {
            Cart::where('user_id', $user->id)->delete();
            BuyNow::where('user_id', $user->id)->delete();
        }

        if ($sessionId) {
            Cart::where('session_id', $sessionId)->delete();
            BuyNow::where('session_id', $sessionId)->delete();
        }

        // Delete current device access token if authenticated via Sanctum
        if ($request->user() && $request->user()->currentAccessToken()) {
            $request->user()->currentAccessToken()->delete();
        }

        return response()->json([
            'status' => true,
            'message' => 'Logged out successfully. Cart cleared.',
            'cart_count' => 0,
            'data' => [],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Verify OTP (Email Verification)
    |--------------------------------------------------------------------------
    */
    public function verifyOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'otp' => 'required|digits:6',
        ], [
            'email.required' => 'Email address is required.',
            'otp.required' => 'Verification OTP is required.',
            'otp.digits' => 'OTP must be 6 digits.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::where('email', $request->email)
            ->where('otp', $request->otp)
            ->first();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid OTP code. Please check your email.',
            ], 401);
        }

        if (!$user->otp_expires_at || now()->gt($user->otp_expires_at)) {
            return response()->json([
                'status' => false,
                'message' => 'OTP has expired. Please request a new one.',
            ], 401);
        }

        $user->update([
            'otp' => null,
            'otp_expires_at' => null,
            'email_verified_at' => now(),
        ]);

        if ($user && method_exists($user, 'recordLogin')) {
            $user->recordLogin();
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => true,
            'message' => 'Email verified successfully.',
            'token' => $token,
            'user' => $user->fresh()->makeHidden(['otp', 'otp_expires_at', 'plain_password']),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Resend OTP (Sent ONLY to Email)
    |--------------------------------------------------------------------------
    */
    public function resendOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ], [
            'email.required' => 'Email address is required.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'No account found with this email address.',
            ], 404);
        }

        $otp = (string) rand(100000, 999999);

        $user->update([
            'otp' => $otp,
            'otp_expires_at' => now()->addMinutes(10),
        ]);

        // Send OTP ONLY to user's email
        try {
            Mail::to($user->email)->send(new UserOtpMail($user, $otp));
        } catch (\Throwable $e) {
            Log::error('Resend OTP email failed: ' . $e->getMessage(), ['user_id' => $user->id]);
            return response()->json([
                'status' => false,
                'message' => 'Failed to send OTP email. Please try again.',
            ], 500);
        }

        return response()->json([
            'status' => true,
            'message' => 'OTP resent successfully to your email.',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Google Login API
    |--------------------------------------------------------------------------
    */
    // public function googleLogin(Request $request)
    // {
    //     $request->validate([
    //         'id_token' => 'required|string',
    //     ]);

    //     $client = new GoogleClient([
    //         'client_id' => env('GOOGLE_CLIENT_ID'),
    //     ]);

    //     $payload = $client->verifyIdToken($request->id_token);

    //     if (!$payload) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'Invalid Google token',
    //         ], 401);
    //     }

    //     $user = User::updateOrCreate(
    //         [
    //             'email' => $payload['email'],
    //         ],
    //         [
    //             'name' => $payload['name'] ?? '',
    //             'google_id' => $payload['sub'],
    //             'avatar' => $payload['picture'] ?? null,
    //             'role' => 'user',
    //             'role_id' => 5,
    //             'status' => 1,
    //             'password' => Hash::make(Str::random(16)),
    //             'plain_password' => null,
    //         ]
    //     );

    //     $token = $user->createToken('auth_token')->plainTextToken;

    //     return response()->json([
    //         'status' => true,
    //         'message' => 'Login successful',
    //         'token' => $token,
    //         'user' => $user,
    //     ]);
    // }
}
