<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ImpersonationController extends Controller
{
    /**
     * Start impersonating a user.
     * Only Super Admin can impersonate.
     */
    public function start(User $user)
    {
        $admin = auth()->user();

        // Only super_admin can impersonate
        if (!$admin->isSuperAdmin()) {
            abort(403, 'Only Super Admin can impersonate users.');
        }

        // Cannot impersonate another super_admin
        if ($user->isSuperAdmin()) {
            return back()->with('error', 'Cannot impersonate another Super Admin.');
        }

        // Cannot impersonate yourself
        if ($admin->id === $user->id) {
            return back()->with('error', 'Cannot impersonate yourself.');
        }

        // Store the original admin ID in session
        session(['impersonator_id' => $admin->id]);
        session(['impersonator_name' => $admin->name]);

        // Log the impersonation start
        AuditService::logAction('user.impersonation.start', $user, [
            'impersonator_id' => $admin->id,
            'impersonator_name' => $admin->name,
            'target_user_id' => $user->id,
            'target_user_name' => $user->name,
            'target_user_role' => $user->role,
        ]);

        // Login as the target user
        Auth::login($user);

        // Each role has its own subdomain now, so cross-host redirect.
        $path = match ($user->role) {
            'admin' => route('admin.dashboard', [], false),
            'recharge_admin' => route('recharge-admin.dashboard', [], false),
            'reseller' => route('reseller.dashboard', [], false),
            'client' => route('client.dashboard', [], false),
            default => route('dashboard', [], false),
        };

        return redirect($this->urlForRole($user->role, $path))
            ->with('success', "Now viewing as {$user->name}");
    }

    /**
     * Stop impersonating and return to Super Admin.
     */
    public function stop()
    {
        $impersonatorId = session('impersonator_id');

        if (!$impersonatorId) {
            return redirect()->route('admin.dashboard');
        }

        $impersonatedUser = auth()->user();
        $admin = User::find($impersonatorId);

        if (!$admin || !$admin->isSuperAdmin()) {
            // Clear session and logout for security
            session()->forget(['impersonator_id', 'impersonator_name']);
            Auth::logout();
            return redirect()->route('admin.login')->with('error', 'Invalid impersonation session.');
        }

        // Log the impersonation end
        AuditService::logAction('user.impersonation.stop', $impersonatedUser, [
            'impersonator_id' => $admin->id,
            'impersonator_name' => $admin->name,
            'impersonated_user_id' => $impersonatedUser->id,
            'impersonated_user_name' => $impersonatedUser->name,
        ]);

        // Clear impersonation session
        session()->forget(['impersonator_id', 'impersonator_name']);

        // Login back as the admin
        Auth::login($admin);

        $path = route('admin.users.show', $impersonatedUser, false);

        return redirect($this->urlForRole('admin', $path))
            ->with('success', 'Returned to your admin account.');
    }

    /**
     * Build an absolute URL on the host that serves the given role's portal.
     * Falls back to the relative path when the three domains aren't all
     * configured (single-domain mode in local dev).
     */
    private function urlForRole(string $role, string $path): string
    {
        $domains = SystemSetting::domains();
        if (in_array(null, $domains, true)) {
            return $path;
        }

        $host = match ($role) {
            'reseller' => $domains['reseller'],
            'client' => $domains['client'],
            default => $domains['admin'],
        };

        return "https://{$host}{$path}";
    }

    /**
     * Check if currently impersonating.
     */
    public static function isImpersonating(): bool
    {
        return session()->has('impersonator_id');
    }

    /**
     * Get the impersonator's name.
     */
    public static function getImpersonatorName(): ?string
    {
        return session('impersonator_name');
    }
}
