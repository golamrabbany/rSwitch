<?php

namespace App\Http\Controllers\Auth;

use App\Auth\Md5CompatibleUserProvider;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\OtpNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;

class UnifiedLoginController extends Controller
{
    /**
     * Show the unified login form.
     */
    public function showLoginForm()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    /**
     * Step 1: Validate credentials via Ajax and send OTP.
     */
    public function validateCredentials(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        // Rate limiting: 5 attempts per minute per IP
        $key = 'login-attempt:' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            return response()->json([
                'success' => false,
                'message' => "Too many attempts. Please try again in {$seconds} seconds.",
            ], 429);
        }

        $user = User::where('email', $request->email)
            ->where('status', 'active')
            ->first();

        if (!$user || !Md5CompatibleUserProvider::checkPassword($request->password, $user)) {
            RateLimiter::hit($key, 60);
            return response()->json([
                'success' => false,
                'message' => 'Invalid email or password.',
            ], 422);
        }

        // Generate 6-digit OTP
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $user->update([
            'otp_code' => $otp,
            'otp_expires_at' => now()->addMinutes(5),
        ]);

        // Store user ID in session for OTP verification
        session(['otp_user_id' => $user->id, 'otp_remember' => $request->boolean('remember')]);

        // Send OTP via email
        try {
            $user->notify(new OtpNotification($otp));
        } catch (\Exception $e) {
            // Log but don't fail — OTP is in DB for testing/fallback
            \Log::warning('OTP email failed: ' . $e->getMessage());
        }

        // Don't clear rate limiter here — clear after OTP verification

        // Mask email for display
        $parts = explode('@', $user->email);
        $name = $parts[0];
        $masked = substr($name, 0, 2) . str_repeat('*', max(strlen($name) - 4, 2)) . substr($name, -2) . '@' . $parts[1];

        return response()->json([
            'success' => true,
            'message' => 'OTP sent to your email.',
            'masked_email' => $masked,
            // Show OTP on screen when mail is not configured (log/array driver)
            'otp_display' => in_array(config('mail.default'), ['log', 'array']) ? $otp : null,
        ]);
    }

    /**
     * Step 2: Verify OTP and complete login.
     */
    public function verifyOtp(Request $request): JsonResponse
    {
        $request->validate([
            'otp' => 'required|string|size:6',
        ]);

        $userId = session('otp_user_id');
        if (!$userId) {
            return response()->json([
                'success' => false,
                'message' => 'Session expired. Please login again.',
                'expired' => true,
            ], 422);
        }

        // Rate limiting
        $key = 'otp-verify:' . $userId;
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            return response()->json([
                'success' => false,
                'message' => "Too many attempts. Please try again in {$seconds} seconds.",
            ], 429);
        }

        $user = User::find($userId);

        if (!$user || !$user->otp_code || $user->otp_code !== $request->otp) {
            RateLimiter::hit($key, 60);
            return response()->json([
                'success' => false,
                'message' => 'Invalid OTP code.',
            ], 422);
        }

        if ($user->otp_expires_at && $user->otp_expires_at->isPast()) {
            RateLimiter::hit($key, 60);
            return response()->json([
                'success' => false,
                'message' => 'OTP has expired. Please request a new one.',
            ], 422);
        }

        // Clear OTP
        $user->update([
            'otp_code' => null,
            'otp_expires_at' => null,
        ]);

        // Clear session data and rate limiters
        $remember = session('otp_remember', false);
        session()->forget(['otp_user_id', 'otp_remember']);
        RateLimiter::clear($key);
        RateLimiter::clear('login-attempt:' . $request->ip());

        // Login the user
        Auth::login($user, $remember);
        $request->session()->regenerate();

        // Determine redirect based on role
        $redirect = match ($user->role) {
            'super_admin', 'admin' => route('admin.dashboard'),
            'recharge_admin' => route('recharge-admin.dashboard'),
            'reseller' => route('reseller.dashboard'),
            'client' => route('client.dashboard'),
            default => route('dashboard'),
        };

        return response()->json([
            'success' => true,
            'message' => 'Login successful.',
            'redirect' => $redirect,
        ]);
    }

    /**
     * Resend OTP.
     */
    public function resendOtp(Request $request): JsonResponse
    {
        $userId = session('otp_user_id');
        if (!$userId) {
            return response()->json([
                'success' => false,
                'message' => 'Session expired. Please login again.',
                'expired' => true,
            ], 422);
        }

        // Rate limiting: 3 resends per 5 minutes
        $key = 'otp-resend:' . $userId;
        if (RateLimiter::tooManyAttempts($key, 3)) {
            $seconds = RateLimiter::availableIn($key);
            return response()->json([
                'success' => false,
                'message' => "Please wait {$seconds} seconds before requesting another OTP.",
            ], 429);
        }

        $user = User::find($userId);
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
                'expired' => true,
            ], 422);
        }

        // Generate new OTP
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $user->update([
            'otp_code' => $otp,
            'otp_expires_at' => now()->addMinutes(5),
        ]);

        RateLimiter::hit($key, 300);

        // Send OTP via email
        try {
            $user->notify(new OtpNotification($otp));
        } catch (\Exception $e) {
            \Log::warning('OTP email failed: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'New OTP sent to your email.',
            'otp_display' => in_array(config('mail.default'), ['log', 'array']) ? $otp : null,
        ]);
    }

    /**
     * Logout.
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
