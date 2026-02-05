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

        $settings = SystemSetting::orderBy('group')->orderBy('key')->get()->groupBy('group');

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

            if ($setting && $setting->value !== $value) {
                $changes[$key] = ['old' => $setting->value, 'new' => $value];
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
