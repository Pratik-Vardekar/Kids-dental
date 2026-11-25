<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\CartService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthenticatedSessionController extends Controller
{
    /**
     * Show login page
     */
    public function create()
    {
        $this->ensureFaviconsExist();
        return view('auth.login');
    }

    /**
     * Ensure crisp square Kids-e-Dental crown favicon exists
     */
    private function ensureFaviconsExist()
    {
        $destPng = base_path('backend/assets/images/kids-dental-favicon.png');
        $srcPng = base_path('backend/assets/images/KidseDental_Logo.png');
        $rootIco = base_path('favicon.ico');

        if (file_exists($destPng) && (!file_exists($rootIco) || filesize($rootIco) === 0)) {
            @copy($destPng, $rootIco);
        }

        if (!file_exists($destPng) && file_exists($srcPng) && function_exists('imagecreatefrompng')) {
            $src = @imagecreatefrompng($srcPng);
            if ($src) {
                $w = imagesx($src);
                $h = imagesy($src);

                $minX = $w; $maxX = 0;
                $minY = $h; $maxY = 0;
                $foundYellow = false;

                for ($y = 0; $y < $h; $y++) {
                    for ($x = 0; $x < $w; $x++) {
                        $rgba = imagecolorat($src, $x, $y);
                        $alpha = ($rgba & 0x7F000000) >> 24;
                        if ($alpha > 115) continue;

                        $r = ($rgba >> 16) & 0xFF;
                        $g = ($rgba >> 8) & 0xFF;
                        $b = $rgba & 0xFF;

                        // Identify the Kids-e-Dental crown yellow color
                        if ($r > 190 && $g > 150 && $b < 80) {
                            $foundYellow = true;
                            if ($x < $minX) $minX = $x;
                            if ($x > $maxX) $maxX = $x;
                            if ($y < $minY) $minY = $y;
                            if ($y > $maxY) $maxY = $y;
                        }
                    }
                }

                if ($foundYellow && $maxX > $minX && $maxY > $minY) {
                    $pad = 6;
                    $crownX = max(0, $minX - $pad);
                    $crownY = max(0, $minY - $pad);
                    $crownW = min($w - $crownX, ($maxX - $minX) + ($pad * 2));
                    $crownH = min($h - $crownY, ($maxY - $minY) + ($pad * 2));

                    $size = max($crownW, $crownH);
                    $outSize = 128;
                    $thumb = imagecreatetruecolor($outSize, $outSize);
                    imagealphablending($thumb, false);
                    imagesavealpha($thumb, true);
                    $transparent = imagecolorallocatealpha($thumb, 0, 0, 0, 127);
                    imagefilledrectangle($thumb, 0, 0, $outSize, $outSize, $transparent);
                    imagealphablending($thumb, true);

                    $offsetX = ($size - $crownW) / 2;
                    $offsetY = ($size - $crownH) / 2;

                    $tempSquare = imagecreatetruecolor($size, $size);
                    imagealphablending($tempSquare, false);
                    imagesavealpha($tempSquare, true);
                    imagefilledrectangle($tempSquare, 0, 0, $size, $size, $transparent);
                    imagealphablending($tempSquare, true);

                    imagecopy($tempSquare, $src, (int)$offsetX, (int)$offsetY, $crownX, $crownY, $crownW, $crownH);
                    imagecopyresampled($thumb, $tempSquare, 0, 0, 0, 0, $outSize, $outSize, $size, $size);

                    @imagepng($thumb, $destPng, 9);
                    @imagedestroy($thumb);
                    @imagedestroy($tempSquare);
                }
                @imagedestroy($src);
            }
        }
    }

    /**
     * Handle login request
     */
    public function store(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt([
            'email' => $request->email,
            'password' => $request->password,
            'status' => 1,
        ])) {

            $request->session()->regenerate();

            app(CartService::class)->mergeGuestCartToUser(Auth::id());

            // Merge Guest Wishlist
            $guestWishlist = session()->get('guest_wishlist', []);
            if (!empty($guestWishlist)) {
                foreach ($guestWishlist as $productId) {
                    \App\Models\Wishlist::firstOrCreate([
                        'user_id' => Auth::id(),
                        'product_id' => $productId,
                    ]);
                }
                session()->forget('guest_wishlist');
            }

            $user = Auth::user();
            if ($user && method_exists($user, 'recordLogin')) {
                $user->recordLogin();
            }

            // Admin Login
            if ($user->role_id == 1) {

                return redirect()->route('admin.dashboard')
                    ->with('success', 'Welcome Admin!');
            }

            // Frontend User Login
            return redirect('/')
                ->with('success', 'Login successful. Welcome back!');
        }

        $inactiveUser = \App\Models\User::where('email', $request->email)->first();

        if ($inactiveUser && (int) $inactiveUser->status !== 1) {
            return back()
                ->withInput($request->only('email'))
                ->with('error', 'Your account is inactive. Please contact support.')
                ->withErrors([
                    'email' => 'Your account is inactive. Please contact support.',
                ]);
        }

        return back()
            ->withInput($request->only('email'))
            ->with('error', 'Invalid email or password.')
            ->withErrors([
                'email' => 'Invalid email or password.',
                'password' => 'Invalid email or password.',
            ]);
    }

    /**
     * Logout
     */
    public function destroy(Request $request)
    {
        $userId = Auth::id();
        if ($userId) {
            \App\Models\Cart::where('user_id', $userId)->delete();
            \App\Models\BuyNow::where('user_id', $userId)->delete();
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->with('success', 'You have been logged out successfully.');
    }
}
