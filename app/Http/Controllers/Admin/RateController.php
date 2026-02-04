<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Rate;
use App\Models\RateGroup;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RateController extends Controller
{
    public function create(RateGroup $rateGroup)
    {
        return view('admin.rates.create', compact('rateGroup'));
    }

    public function store(Request $request, RateGroup $rateGroup)
    {
        $validated = $this->validateRate($request);
        $validated['rate_group_id'] = $rateGroup->id;

        $rate = Rate::create($validated);

        AuditService::logCreated($rate);

        return redirect()->route('admin.rate-groups.show', $rateGroup)
            ->with('success', "Rate for prefix \"{$rate->prefix}\" created.");
    }

    public function edit(RateGroup $rateGroup, Rate $rate)
    {
        return view('admin.rates.edit', compact('rateGroup', 'rate'));
    }

    public function update(Request $request, RateGroup $rateGroup, Rate $rate)
    {
        $validated = $this->validateRate($request, $rate);

        $original = $rate->getAttributes();
        $rate->update($validated);

        AuditService::logUpdated($rate, $original);

        return redirect()->route('admin.rate-groups.show', $rateGroup)
            ->with('success', "Rate for prefix \"{$rate->prefix}\" updated.");
    }

    public function destroy(RateGroup $rateGroup, Rate $rate)
    {
        AuditService::logAction('deleted', $rate, $rate->toArray());
        $rate->delete();

        return redirect()->route('admin.rate-groups.show', $rateGroup)
            ->with('success', "Rate for prefix \"{$rate->prefix}\" deleted.");
    }

    private function validateRate(Request $request, ?Rate $rate = null): array
    {
        return $request->validate([
            'prefix' => ['required', 'string', 'regex:/^\d{1,20}$/'],
            'destination' => 'required|string|max:100',
            'rate_per_minute' => 'required|numeric|min:0',
            'connection_fee' => 'nullable|numeric|min:0',
            'min_duration' => 'nullable|integer|min:0',
            'billing_increment' => 'nullable|integer|min:1',
            'effective_date' => 'required|date',
            'end_date' => 'nullable|date|after:effective_date',
            'status' => ['required', Rule::in(['active', 'disabled'])],
        ]);
    }
}
