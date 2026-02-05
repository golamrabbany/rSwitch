<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Did;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DidController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = Did::with('trunk:id,name', 'assignedUser:id,name');

        if ($user->isAdmin()) {
            if ($request->filled('user_id')) {
                $query->where('assigned_to_user_id', $request->user_id);
            }
        } elseif ($user->isReseller()) {
            $query->whereIn('assigned_to_user_id', $user->descendantIds());
        } else {
            $query->where('assigned_to_user_id', $user->id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $query->where('number', 'like', $request->search . '%');
        }

        $dids = $query->orderBy('number')->paginate(50);

        return response()->json($dids);
    }

    public function show(Request $request, Did $did): JsonResponse
    {
        $this->authorizeAccess($request->user(), $did);

        $did->load('trunk:id,name', 'assignedUser:id,name,email', 'destinationSipAccount:id,username');

        return response()->json($did);
    }

    private function authorizeAccess($user, Did $did): void
    {
        if ($user->isAdmin()) {
            return;
        }

        $allowedIds = $user->isReseller() ? $user->descendantIds() : [$user->id];

        if (!in_array($did->assigned_to_user_id, $allowedIds)) {
            abort(403);
        }
    }
}
