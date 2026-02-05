<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorController extends Controller
{
    /**
     * Show 2FA setup page with QR code.
     */
    public function setup(Request $request)
    {
        $user = $request->user();

        if ($user->two_fa_enabled && $user->two_fa_confirmed_at) {
            return redirect()->route('two-factor.status');
        }

        $google2fa = new Google2FA();

        // Generate secret if not already set (or if previous setup was abandoned)
        if (!$user->two_fa_secret || !$user->two_fa_confirmed_at) {
            $secret = $google2fa->generateSecretKey();
            $user->update(['two_fa_secret' => encrypt($secret)]);
        } else {
            $secret = decrypt($user->two_fa_secret);
        }

        $qrUrl = $google2fa->getQRCodeUrl(
            config('app.name', 'rSwitch'),
            $user->email,
            $secret,
        );

        return view('two-factor.setup', [
            'secret' => $secret,
            'qrUrl' => $qrUrl,
        ]);
    }

    /**
     * Confirm 2FA setup by verifying first OTP code.
     */
    public function confirm(Request $request)
    {
        $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        $user = $request->user();
        $google2fa = new Google2FA();
        $secret = decrypt($user->two_fa_secret);

        if (!$google2fa->verifyKey($secret, $request->code)) {
            return back()->withErrors(['code' => 'Invalid verification code. Please try again.']);
        }

        // Generate recovery codes
        $recoveryCodes = collect(range(1, 8))->map(fn () => Str::random(10))->all();

        $user->update([
            'two_fa_enabled' => true,
            'two_fa_confirmed_at' => now(),
            'two_fa_recovery_codes' => array_map(fn ($code) => bcrypt($code), $recoveryCodes),
        ]);

        return view('two-factor.recovery-codes', [
            'recoveryCodes' => $recoveryCodes,
        ]);
    }

    /**
     * Show 2FA status page.
     */
    public function status(Request $request)
    {
        return view('two-factor.status', [
            'enabled' => $request->user()->two_fa_enabled && $request->user()->two_fa_confirmed_at,
        ]);
    }

    /**
     * Disable 2FA.
     */
    public function disable(Request $request)
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $request->user()->update([
            'two_fa_enabled' => false,
            'two_fa_secret' => null,
            'two_fa_recovery_codes' => null,
            'two_fa_confirmed_at' => null,
        ]);

        return redirect()->route('two-factor.status')
            ->with('success', 'Two-factor authentication has been disabled.');
    }

    /**
     * Show 2FA challenge page (during login).
     */
    public function challenge()
    {
        if (!session('2fa:user_id')) {
            return redirect()->route('login');
        }

        return view('two-factor.challenge');
    }

    /**
     * Verify 2FA code during login.
     */
    public function verify(Request $request)
    {
        $request->validate([
            'code' => ['required', 'string'],
        ]);

        $userId = session('2fa:user_id');

        if (!$userId) {
            return redirect()->route('login');
        }

        $user = \App\Models\User::findOrFail($userId);
        $code = $request->code;

        // Try TOTP code first
        if (strlen($code) === 6) {
            $google2fa = new Google2FA();
            $secret = decrypt($user->two_fa_secret);

            if ($google2fa->verifyKey($secret, $code)) {
                return $this->completeTwoFactorLogin($request, $user);
            }
        }

        // Try recovery code
        $recoveryCodes = $user->two_fa_recovery_codes ?? [];

        foreach ($recoveryCodes as $index => $hashedCode) {
            if (password_verify($code, $hashedCode)) {
                // Remove used recovery code
                unset($recoveryCodes[$index]);
                $user->update(['two_fa_recovery_codes' => array_values($recoveryCodes)]);

                return $this->completeTwoFactorLogin($request, $user);
            }
        }

        return back()->withErrors(['code' => 'Invalid authentication code.']);
    }

    private function completeTwoFactorLogin(Request $request, $user): \Illuminate\Http\RedirectResponse
    {
        session()->forget('2fa:user_id');
        session()->forget('2fa:remember');

        auth()->login($user, session('2fa:remember', false));
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }
}
