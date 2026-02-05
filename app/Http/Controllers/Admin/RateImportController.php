<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RateImport;
use App\Models\RateGroup;
use Illuminate\Http\Request;

class RateImportController extends Controller
{
    public function index(Request $request)
    {
        $query = RateImport::with('rateGroup:id,name', 'uploader:id,name');

        if ($request->filled('rate_group_id')) {
            $query->where('rate_group_id', $request->rate_group_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', $request->date_from . ' 00:00:00');
        }

        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', $request->date_to . ' 23:59:59');
        }

        $imports = $query->orderByDesc('created_at')->paginate(30)->withQueryString();

        $rateGroups = RateGroup::orderBy('name')->get(['id', 'name']);

        return view('admin.rate-imports.index', compact('imports', 'rateGroups'));
    }

    public function show(RateImport $rateImport)
    {
        $rateImport->load('rateGroup', 'uploader');

        return view('admin.rate-imports.show', compact('rateImport'));
    }
}
