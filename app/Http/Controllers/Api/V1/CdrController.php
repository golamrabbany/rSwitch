<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CallRecord;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CdrController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
        ]);

        $dateFrom = $request->date_from ?: now()->subDays(7)->toDateString();
        $dateTo = $request->date_to ?: now()->toDateString();

        $query = CallRecord::query()
            ->whereBetween('call_start', [
                $dateFrom . ' 00:00:00',
                $dateTo . ' 23:59:59',
            ]);

        if ($user->isAdmin()) {
            if ($request->filled('user_id')) {
                $query->where('user_id', $request->user_id);
            }
        } elseif ($user->isReseller()) {
            $query->whereIn('user_id', $user->descendantIds());
        } else {
            $query->where('user_id', $user->id);
        }

        if ($request->filled('disposition')) {
            $query->where('disposition', $request->disposition);
        }

        if ($request->filled('call_flow')) {
            $query->where('call_flow', $request->call_flow);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('caller', 'like', $search . '%')
                  ->orWhere('callee', 'like', $search . '%');
            });
        }

        $records = $query->orderByDesc('call_start')->paginate(50);

        return response()->json($records);
    }

    public function show(Request $request, string $uuid): JsonResponse
    {
        $user = $request->user();

        $cdr = CallRecord::where('uuid', $uuid)->firstOrFail();

        if (!$user->isAdmin()) {
            $allowedIds = $user->isReseller() ? $user->descendantIds() : [$user->id];

            if (!in_array($cdr->user_id, $allowedIds)) {
                abort(403);
            }
        }

        return response()->json($cdr);
    }
}
