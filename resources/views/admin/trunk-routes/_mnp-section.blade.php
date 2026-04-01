<div class="form-card">
    <div class="form-card-header">
        <h3 class="form-card-title">BD MNP</h3>
        <p class="form-card-subtitle">Mobile Number Portability — auto-convert to operator routing format</p>
    </div>
    <div class="form-card-body">
        <div class="form-group">
            <label class="flex items-center gap-2">
                <input type="checkbox" name="mnp_enabled" value="1" {{ $mnpEnabled ? 'checked' : '' }} class="form-checkbox">
                <span class="text-sm text-gray-700">Enable BD MNP</span>
            </label>
            <p class="form-hint">Auto-converts BD mobile numbers to MNP format after dial manipulation. Non-BD numbers pass through unchanged.</p>
        </div>

        <div class="mt-4 p-4 bg-gray-50 rounded-lg border border-gray-100">
            <p class="text-xs font-medium text-gray-600 mb-2">Operator Routing Map (automatic)</p>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-xs font-mono">
                <span class="text-gray-500">013,017 <span class="text-indigo-600">→ 71</span> <span class="text-gray-400">GP</span></span>
                <span class="text-gray-500">014,019 <span class="text-indigo-600">→ 91</span> <span class="text-gray-400">BL</span></span>
                <span class="text-gray-500">015 <span class="text-indigo-600">→ 51</span> <span class="text-gray-400">TT</span></span>
                <span class="text-gray-500">016,018 <span class="text-indigo-600">→ 81</span> <span class="text-gray-400">Robi</span></span>
            </div>
            <p class="text-xs text-gray-400 mt-2">Example: 01714101351 → <span class="font-mono text-gray-600">880<strong class="text-indigo-600">71</strong>1714101351</span></p>
        </div>
    </div>
</div>
