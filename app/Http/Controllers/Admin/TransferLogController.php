<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TransferLog;
use App\Models\User;
use Illuminate\Http\Request;

class TransferLogController extends Controller
{
    public function index(Request $request)
    {
        $query = TransferLog::with('performedBy:id,name', 'fromParent:id,name', 'toParent:id,name');

        if ($request->filled('transfer_type')) {
            $query->where('transfer_type', $request->transfer_type);
        }

        if ($request->filled('performed_by')) {
            $query->where('performed_by', $request->performed_by);
        }

        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', $request->date_from . ' 00:00:00');
        }

        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', $request->date_to . ' 23:59:59');
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('reason', 'like', "%{$search}%")
                  ->orWhere('transferred_item_type', 'like', "%{$search}%");
            });
        }

        $logs = $query->orderByDesc('created_at')->paginate(30)->withQueryString();

        $users = User::orderBy('name')->get(['id', 'name']);
        $types = TransferLog::select('transfer_type')->distinct()->pluck('transfer_type');

        return view('admin.transfer-logs.index', compact('logs', 'users', 'types'));
    }

    public function show(TransferLog $transferLog)
    {
        $transferLog->load('performedBy', 'fromParent', 'toParent');

        return view('admin.transfer-logs.show', compact('transferLog'));
    }
}
