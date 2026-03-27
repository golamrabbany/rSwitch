<?php

namespace App\Http\Controllers\Reseller;

use App\Http\Controllers\Controller;
use App\Models\DncNumber;
use Illuminate\Http\Request;

class DncController extends Controller
{
    public function index(Request $request)
    {
        $authUser = auth()->user();

        $query = DncNumber::where('added_by', $authUser->id);

        if ($request->filled('search')) {
            $query->where('phone_number', 'like', "%{$request->search}%");
        }

        $numbers = $query->orderByDesc('created_at')->paginate(50);
        $totalCount = DncNumber::where('added_by', $authUser->id)->count();

        return view('reseller.dnc.index', compact('numbers', 'totalCount'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'phone_numbers' => ['required', 'string'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $raw = preg_split('/[\r\n,;]+/', $request->phone_numbers);
        $phones = collect($raw)
            ->map(fn ($n) => preg_replace('/[^0-9+]/', '', trim($n)))
            ->filter(fn ($n) => strlen($n) >= 7)
            ->unique()
            ->values();

        if ($phones->isEmpty()) {
            return back()->with('warning', 'No valid phone numbers found.');
        }

        $existing = DncNumber::whereIn('phone_number', $phones)
            ->where('added_by', auth()->id())
            ->pluck('phone_number')
            ->toArray();
        $newNumbers = $phones->diff($existing);

        $inserted = 0;
        foreach ($newNumbers as $phone) {
            DncNumber::create([
                'phone_number' => $phone,
                'reason' => $request->reason,
                'added_by' => auth()->id(),
            ]);
            $inserted++;
        }

        $skipped = count($existing);
        $msg = "{$inserted} number(s) added to DNC list.";
        if ($skipped > 0) {
            $msg .= " {$skipped} duplicate(s) skipped.";
        }

        return back()->with('success', $msg);
    }

    public function destroy(DncNumber $dncNumber)
    {
        abort_unless($dncNumber->added_by === auth()->id(), 403);

        $dncNumber->delete();

        return back()->with('success', "Number '{$dncNumber->phone_number}' removed from DNC list.");
    }
}
