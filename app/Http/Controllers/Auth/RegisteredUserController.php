<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\CartService;
use App\Mail\UserWelcomeMail;
use App\Mail\UserRegistrationAdminMail;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;

class RegisteredUserController extends Controller
{
    public function create()
    {
        return view('auth.register');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                // Only block if an active (non-deleted) user already has this email
                Rule::unique('users', 'email')->whereNull('deleted_at'),
            ],
            'phone' => 'nullable|string|max:20',
            'password' => [
                'required',
                'confirmed',
                Rules\Password::defaults(),
            ],
        ], [
            'email.unique' => 'This email is already registered.',
        ]);

        // Soft-deleted rows still hold the unique email index — remove them so signup can proceed
        User::onlyTrashed()
            ->where('email', $request->email)
            ->forceDelete();

        $phone = $request->phone;
        if ($phone) {
            $digits = preg_replace('/\D+/', '', $phone);
            // Drop trunk prefix 0 (e.g. 09885... → 9885...)
            if (str_starts_with($digits, '91') && strlen($digits) > 10) {
                $digits = '91' . ltrim(substr($digits, 2), '0');
            } else {
                $digits = ltrim($digits, '0');
            }
            $phone = str_starts_with($digits, '91') ? '+' . $digits : '+91' . $digits;
        }

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'phone'    => $phone ?: null,
            'password' => Hash::make($request->password),
            'status'   => 1,
            'role_id'  => 5, // Default User Role
        ]);

        event(new Registered($user));

        try {
            Mail::to($user->email)->send(new UserWelcomeMail($user));
        } catch (\Throwable $e) {
            Log::error('User welcome email failed: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);
        }

        $adminEmail = config('mail.admin_order_email') ?: config('mail.from.address');
        if ($adminEmail) {
            try {
                Mail::to($adminEmail)->send(new UserRegistrationAdminMail($user));
            } catch (\Throwable $e) {
                Log::error('User registration admin email failed: ' . $e->getMessage(), [
                    'user_id' => $user->id,
                    'admin_email' => $adminEmail,
                ]);
            }
        }

        Auth::login($user);

        app(CartService::class)->mergeGuestCartToUser($user->id);

        // Merge Guest Wishlist
        $guestWishlist = session()->get('guest_wishlist', []);
        if (!empty($guestWishlist)) {
            foreach ($guestWishlist as $productId) {
                \App\Models\Wishlist::firstOrCreate([
                    'user_id' => $user->id,
                    'product_id' => $productId,
                ]);
            }
            session()->forget('guest_wishlist');
        }

        return redirect('/')
            ->with('success', 'Registration successful. Welcome to Kids-e-dental!');
    }
}
