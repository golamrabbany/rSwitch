<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KycDocument;
use App\Models\KycProfile;
use App\Models\User;
use App\Notifications\KycStatusNotification;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class KycController extends Controller
{
    public function index(Request $request)
    {
        $query = KycProfile::with('user', 'documents');

        if ($request->filled('status')) {
            $query->whereHas('user', fn ($q) => $q->where('kyc_status', $request->status));
        }

        if ($request->filled('account_type')) {
            $query->where('account_type', $request->account_type);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('id_number', 'like', "%{$search}%")
                  ->orWhereHas('user', fn ($u) => $u->where('email', 'like', "%{$search}%"));
            });
        }

        $profiles = $query->orderByDesc('submitted_at')->paginate(20);

        // Get stats
        $stats = [
            'pending' => User::where('kyc_status', 'pending')->count(),
            'approved' => User::where('kyc_status', 'approved')->count(),
            'rejected' => User::where('kyc_status', 'rejected')->count(),
        ];

        return view('admin.kyc.index', compact('profiles', 'stats'));
    }

    public function show(KycProfile $kycProfile)
    {
        $kycProfile->load('user', 'documents', 'reviewer');

        return view('admin.kyc.show', compact('kycProfile'));
    }

    public function approve(KycProfile $kycProfile)
    {
        $user = $kycProfile->user;
        $user->update([
            'kyc_status' => 'approved',
            'kyc_verified_at' => now(),
            'kyc_rejected_reason' => null,
        ]);

        $kycProfile->update([
            'reviewed_at' => now(),
            'reviewed_by' => auth()->id(),
        ]);

        AuditService::logAction('kyc.approved', $kycProfile, ['user_id' => $user->id, 'user_name' => $user->name]);

        $user->notify(new KycStatusNotification('approved'));

        return back()->with('success', 'KYC approved for ' . $user->name);
    }

    public function reject(Request $request, KycProfile $kycProfile)
    {
        $request->validate([
            'reason' => ['required', 'string', 'max:255'],
        ]);

        $user = $kycProfile->user;
        $user->update([
            'kyc_status' => 'rejected',
            'kyc_rejected_reason' => $request->reason,
        ]);

        $kycProfile->update([
            'reviewed_at' => now(),
            'reviewed_by' => auth()->id(),
        ]);

        AuditService::logAction('kyc.rejected', $kycProfile, ['user_id' => $user->id, 'reason' => $request->reason]);

        $user->notify(new KycStatusNotification('rejected', $request->reason));

        return back()->with('success', 'KYC rejected for ' . $user->name);
    }
}
