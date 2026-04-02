@props(['sipAccount' => null])

<div x-data="{ cfEnabled: {{ old('call_forward_enabled', $sipAccount?->call_forward_enabled ?? false) ? 'true' : 'false' }} }">
    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg mb-4">
        <div>
            <p class="text-sm font-medium text-gray-900">Call Forwarding</p>
            <p class="text-xs text-gray-500 mt-0.5">Forward incoming calls to a SIP account or mobile number</p>
        </div>
        <input type="hidden" name="call_forward_enabled" :value="cfEnabled ? '1' : '0'">
        <button type="button" @click="cfEnabled = !cfEnabled"
            class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
            :class="cfEnabled ? 'bg-indigo-600' : 'bg-gray-200'"
            role="switch" :aria-checked="cfEnabled">
            <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                :class="cfEnabled ? 'translate-x-5' : 'translate-x-0'"></span>
        </button>
    </div>

    <div x-show="cfEnabled" x-cloak class="space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <label class="form-label">Forward Type</label>
                <select name="call_forward_type" class="form-input">
                    <option value="cfu" {{ old('call_forward_type', $sipAccount?->call_forward_type) === 'cfu' ? 'selected' : '' }}>Unconditional (CFU)</option>
                    <option value="cfnr" {{ old('call_forward_type', $sipAccount?->call_forward_type ?? 'cfnr') === 'cfnr' ? 'selected' : '' }}>No Reply (CFNR)</option>
                    <option value="cfb" {{ old('call_forward_type', $sipAccount?->call_forward_type) === 'cfb' ? 'selected' : '' }}>Busy (CFB)</option>
                    <option value="cfnr_cfb" {{ old('call_forward_type', $sipAccount?->call_forward_type) === 'cfnr_cfb' ? 'selected' : '' }}>No Reply + Busy</option>
                </select>
            </div>
            <div>
                <label class="form-label">Forward To</label>
                <input type="text" name="call_forward_destination"
                       value="{{ old('call_forward_destination', $sipAccount?->call_forward_destination) }}"
                       class="form-input font-mono" placeholder="SIP account or mobile">
            </div>
            <div>
                <label class="form-label">Ring Timeout (s)</label>
                <input type="number" name="call_forward_timeout"
                       value="{{ old('call_forward_timeout', $sipAccount?->call_forward_timeout ?? 20) }}"
                       class="form-input" min="5" max="120">
                <p class="text-xs text-gray-400 mt-1">For CFNR — ring before forwarding</p>
            </div>
        </div>

        <div class="p-3 bg-amber-50 rounded-lg border border-amber-200">
            <p class="text-xs text-amber-700">
                <strong>CFU:</strong> Always forward, never ring.
                <strong>CFNR:</strong> Ring first, forward if no answer.
                <strong>CFB:</strong> Forward when busy.
                Forward to mobile is billed to the account owner.
            </p>
        </div>
    </div>
</div>
