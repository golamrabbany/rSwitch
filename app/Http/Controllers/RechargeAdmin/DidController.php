<?php

namespace App\Http\Controllers\RechargeAdmin;

use App\Http\Controllers\Controller;
use App\Models\Did;
use App\Models\User;
use Illuminate\Http\Request;

class DidController extends Controller
{
    public function index(Request $request)
    {
        $currentUser = auth()->user();
        $userIds = $currentUser->descendantIds();

        $query = Did::whereIn('assigned_to_user_id', $userIds)
            ->with(['assignedUser:id,name,role', 'trunk:id,name']);

        // Search filter
        if ($search = $request->query('search')) {
            $query->where('number', 'like', "%{$search}%");
        }

        // Status filter
        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        // Owner filter
        if ($userId = $request->query('user_id')) {
            if (in_array($userId, $userIds)) {
                $query->where('assigned_to_user_id', $userId);
            }
        }

        $dids = $query->orderBy('number')->paginate(25)->withQueryString();

        return view('recharge-admin.dids.index', compact('dids'));
    }

    public function show(Did $did)
    {
        $currentUser = auth()->user();
        $userIds = $currentUser->descendantIds();

        // Authorization check
        abort_unless(in_array($did->assigned_to_user_id, $userIds), 403);

        $did->load(['assignedUser:id,name,role,email', 'trunk:id,name']);

        return view('recharge-admin.dids.show', compact('did'));
    }
}
