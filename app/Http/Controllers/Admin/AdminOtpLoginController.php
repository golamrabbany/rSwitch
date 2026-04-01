<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AdminOtpLoginController extends Controller
{
    /**
     * Show the login form.
     */
    public function showLoginForm()
    {
        if (Auth::check() && Auth::user()->isAnyAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.auth.login');
    }

    /**
     * Validate credentials and generate OTP.
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        // Rate limiting: 5 attempts per minute per IP
        $key = 'admin-login:' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            return back()->withErrors([
                'email' => "Too many attempts. Please try again in {$seconds} seconds.",
            ])->withInput(['email' => $request->email]);
        }

        $user = User::where('email', $request->email)
            ->whereIn('role', ['super_admin', 'admin', 'recharge_admin'])
            ->where('status', 'active')
            ->first();

        if (!$user || !\App\Auth\Md5CompatibleUserProvider::checkPassword($request->password, $user)) {
            RateLimiter::hit($key, 60);
            return back()->withErrors([
                'email' => 'Invalid credentials.',
            ])->withInput(['email' => $request->email]);
        }

        // Generate 6-digit OTP
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $user->update([
            'otp_code' => $otp,
            'otp_expires_at' => now()->addMinutes(5),
        ]);

        // Store user ID in session for OTP verification
        session(['admin_otp_user_id' => $user->id]);

        return redirect()->route('admin.otp.verify.form')
            ->with('otp_display', $otp); // Display OTP for testing
    }

    /**
     * Show the OTP verification form.
     */
    public function showOtpForm()
    {
        if (!session('admin_otp_user_id')) {
            return redirect()->route('admin.login');
        }

        return view('admin.auth.verify-otp', [
            'otp_display' => session('otp_display'),
        ]);
    }

    /**
     * Verify OTP and complete login.
     */
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'otp' => 'required|string|size:6',
        ]);

        $userId = session('admin_otp_user_id');
        if (!$userId) {
            return redirect()->route('admin.login')
                ->withErrors(['otp' => 'Session expired. Please login again.']);
        }

        // Rate limiting
        $key = 'admin-otp-verify:' . $userId;
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            return back()->withErrors([
                'otp' => "Too many attempts. Please try again in {$seconds} seconds.",
            ]);
        }

        $user = User::find($userId);

        if (!$user || !$user->otp_code || $user->otp_code !== $request->otp) {
            RateLimiter::hit($key, 60);
            return back()->withErrors(['otp' => 'Invalid OTP code.']);
        }

        if ($user->otp_expires_at && $user->otp_expires_at->isPast()) {
            RateLimiter::hit($key, 60);
            return back()->withErrors(['otp' => 'OTP code has expired. Please login again.']);
        }

        // Clear OTP
        $user->update([
            'otp_code' => null,
            'otp_expires_at' => null,
        ]);

        // Clear session and rate limiter
        session()->forget('admin_otp_user_id');
        RateLimiter::clear($key);

        // Login the user
        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        // Redirect based on role
        $defaultRoute = match ($user->role) {
            'super_admin', 'admin' => route('admin.dashboard'),
            'recharge_admin' => route('recharge-admin.dashboard'),
            default => route('admin.dashboard'),
        };

        return redirect()->intended($defaultRoute);
    }

    /**
     * Regenerate OTP.
     */
    public function regenerateOtp()
    {
        $userId = session('admin_otp_user_id');
        if (!$userId) {
            return redirect()->route('admin.login');
        }

        $user = User::find($userId);
        if (!$user) {
            return redirect()->route('admin.login');
        }

        // Generate new OTP
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $user->update([
            'otp_code' => $otp,
            'otp_expires_at' => now()->addMinutes(5),
        ]);

        return redirect()->route('admin.otp.verify.form')
            ->with('otp_display', $otp)
            ->with('success', 'New OTP generated.');
    }

    /**
     * Logout admin.
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
