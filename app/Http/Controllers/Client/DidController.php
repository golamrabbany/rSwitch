<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Did;
use Illuminate\Http\Request;

class DidController extends Controller
{
    public function index(Request $request)
    {
        $query = Did::where('assigned_to_user_id', auth()->id())
            ->with('trunk:id,name', 'destinationSipAccount:id,username');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $query->where('number', 'like', "{$request->search}%");
        }

        $dids = $query->orderBy('number')->paginate(20);

        return view('client.dids.index', compact('dids'));
    }

    public function show(Did $did)
    {
        abort_unless($did->assigned_to_user_id === auth()->id(), 403);

        $did->load('trunk:id,name,provider', 'destinationSipAccount:id,username');

        return view('client.dids.show', compact('did'));
    }
}
