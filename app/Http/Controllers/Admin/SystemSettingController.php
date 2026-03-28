<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use App\Services\AuditService;
use Illuminate\Http\Request;

class SystemSettingController extends Controller
{
    public function index()
    {
        // Ensure defaults exist
        SystemSetting::seedDefaults();

        $groupOrder = ['general', 'system', 'sip', 'billing', 'payment_gateways'];
        $settings = SystemSetting::orderBy('sort_order')->get()
            ->groupBy('group')
            ->sortBy(fn($items, $group) => array_search($group, $groupOrder));

        return view('admin.settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'settings'   => ['required', 'array'],
            'settings.*' => ['nullable', 'string', 'max:2000'],
        ]);

        $changes = [];

        foreach ($request->settings as $key => $value) {
            $setting = SystemSetting::find($key);
            if (!$setting) continue;

            // Skip empty password/secret fields (keep existing value)
            $isSecret = \Str::contains($key, ['password', 'secret']);
            if ($isSecret && ($value === null || $value === '')) continue;

            if ($setting->value !== $value) {
                $changes[$key] = ['old' => $isSecret ? '***' : $setting->value, 'new' => $isSecret ? '***' : $value];
                $setting->update(['value' => $value]);
                \Illuminate\Support\Facades\Cache::forget("setting:{$key}");
            }
        }

        if (!empty($changes)) {
            AuditService::logAction('system_settings.updated', null, $changes);
        }

        return redirect()->route('admin.settings.index')
            ->with('success', 'System settings updated.');
    }
}
