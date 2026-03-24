<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class RateController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $rateGroup = $user->rateGroup;

        if (!$rateGroup) {
            return view('client.rates.index', ['rateGroup' => null, 'rates' => collect()]);
        }

        $query = $rateGroup->rates()->where('status', 'active');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('prefix', 'like', "{$search}%")
                  ->orWhere('destination', 'like', "%{$search}%");
            });
        }

        $rates = $query->orderBy('prefix')->paginate(25);

        return view('client.rates.index', compact('rateGroup', 'rates'));
    }
}
