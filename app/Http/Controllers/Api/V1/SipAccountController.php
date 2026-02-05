<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\SipAccount;
use App\Services\AuditService;
use App\Services\SipProvisioningService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SipAccountController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = SipAccount::with('user:id,name,email');

        if ($user->isAdmin()) {
            // Admin sees all
            if ($request->filled('user_id')) {
                $query->where('user_id', $request->user_id);
            }
        } elseif ($user->isReseller()) {
            $query->whereIn('user_id', $user->descendantIds());
        } else {
            $query->where('user_id', $user->id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $query->where('username', 'like', $request->search . '%');
        }

        $accounts = $query->orderBy('username')->paginate(50);

        return response()->json(['data' => $accounts->items(), 'meta' => [
            'current_page' => $accounts->currentPage(),
            'last_page' => $accounts->lastPage(),
            'total' => $accounts->total(),
        ]]);
    }

    public function show(Request $request, SipAccount $sipAccount): JsonResponse
    {
        $this->authorizeAccess($request->user(), $sipAccount);

        $sipAccount->load('user:id,name,email');

        return response()->json(['data' => $sipAccount]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->isAdmin() && !$user->isReseller()) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'username' => ['required', 'string', 'max:50', 'unique:sip_accounts,username', 'regex:/^[a-zA-Z0-9._-]+$/'],
            'password' => ['required', 'string', 'min:8', 'max:64'],
            'auth_type' => ['required', 'in:password,ip,both'],
            'allowed_ips' => ['nullable', 'string', 'max:500'],
            'caller_id_name' => ['nullable', 'string', 'max:80'],
            'caller_id_number' => ['nullable', 'string', 'max:20'],
            'max_channels' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'codec_allow' => ['nullable', 'string', 'max:100'],
        ]);

        $sipAccount = SipAccount::create($validated);
        $sipAccount->refresh();

        app(SipProvisioningService::class)->provision($sipAccount);

        AuditService::logCreated($sipAccount, 'api.sip_account.created');

        return response()->json(['data' => $sipAccount], 201);
    }

    public function update(Request $request, SipAccount $sipAccount): JsonResponse
    {
        $user = $request->user();
        $this->authorizeAccess($user, $sipAccount);

        // Clients can only update limited fields
        if ($user->isClient()) {
            $validated = $request->validate([
                'password' => ['sometimes', 'string', 'min:8', 'max:64'],
                'caller_id_name' => ['nullable', 'string', 'max:80'],
                'caller_id_number' => ['nullable', 'string', 'max:20'],
            ]);
        } else {
            $validated = $request->validate([
                'password' => ['sometimes', 'string', 'min:8', 'max:64'],
                'auth_type' => ['sometimes', 'in:password,ip,both'],
                'allowed_ips' => ['nullable', 'string', 'max:500'],
                'caller_id_name' => ['nullable', 'string', 'max:80'],
                'caller_id_number' => ['nullable', 'string', 'max:20'],
                'max_channels' => ['nullable', 'integer', 'min:1', 'max:1000'],
                'codec_allow' => ['nullable', 'string', 'max:100'],
                'status' => ['sometimes', 'in:active,suspended,disabled'],
            ]);
        }

        $original = $sipAccount->getAttributes();
        $sipAccount->update($validated);

        app(SipProvisioningService::class)->provision($sipAccount);

        AuditService::logUpdated($sipAccount, $original, 'api.sip_account.updated');

        return response()->json(['data' => $sipAccount]);
    }

    public function destroy(Request $request, SipAccount $sipAccount): JsonResponse
    {
        $user = $request->user();

        if (!$user->isAdmin()) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        AuditService::logAction('api.sip_account.deleted', $sipAccount, $sipAccount->toArray());

        app(SipProvisioningService::class)->deprovision($sipAccount);
        $sipAccount->delete();

        return response()->json(['message' => 'SIP account deleted.']);
    }

    private function authorizeAccess($user, SipAccount $sipAccount): void
    {
        if ($user->isAdmin()) {
            return;
        }

        $allowedIds = $user->isReseller() ? $user->descendantIds() : [$user->id];

        if (!in_array($sipAccount->user_id, $allowedIds)) {
            abort(403);
        }
    }
}
