<x-recharge-admin-layout>
    <x-slot name="header">Balance Adjustment</x-slot>

    <div class="max-w-2xl">
        <div class="card">
            <div class="card-header">
                <h3 class="text-lg font-semibold text-gray-900">Recharge or Adjust Balance</h3>
                <p class="text-sm text-gray-500">All operations are logged for audit purposes</p>
            </div>
            <div class="card-body">
                <form action="{{ route('recharge-admin.balance.store') }}" method="POST">
                    @csrf

                    <!-- User Selection -->
                    <div class="mb-6">
                        <label for="user_id" class="form-label">Select User <span class="text-red-500">*</span></label>
                        <select name="user_id" id="user_id" class="form-select" required>
                            <option value="">Choose a user...</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" {{ (old('user_id', $selectedUserId) == $user->id) ? 'selected' : '' }}>
                                    {{ $user->name }} ({{ $user->email }}) - {{ ucfirst($user->role) }} - Balance: ${{ number_format($user->balance, 2) }}
                                </option>
                            @endforeach
                        </select>
                        @error('user_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Operation Type -->
                    <div class="mb-6">
                        <label class="form-label">Operation Type <span class="text-red-500">*</span></label>
                        <div class="flex space-x-4">
                            <label class="flex items-center">
                                <input type="radio" name="operation" value="credit" class="w-4 h-4 text-green-600 border-gray-300 focus:ring-green-500" {{ old('operation', 'credit') === 'credit' ? 'checked' : '' }}>
                                <span class="ml-2 text-sm text-gray-700">
                                    <span class="font-medium text-green-600">Credit (Add)</span>
                                </span>
                            </label>
                            <label class="flex items-center">
                                <input type="radio" name="operation" value="debit" class="w-4 h-4 text-red-600 border-gray-300 focus:ring-red-500" {{ old('operation') === 'debit' ? 'checked' : '' }}>
                                <span class="ml-2 text-sm text-gray-700">
                                    <span class="font-medium text-red-600">Debit (Deduct)</span>
                                </span>
                            </label>
                        </div>
                        @error('operation')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Amount -->
                    <div class="mb-6">
                        <label for="amount" class="form-label">Amount (USD) <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">$</span>
                            <input type="number" name="amount" id="amount" step="0.01" min="0.01" max="999999.99"
                                   class="form-input pl-7" placeholder="0.00" value="{{ old('amount') }}" required>
                        </div>
                        @error('amount')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Reason (MANDATORY) -->
                    <div class="mb-6">
                        <label for="reason" class="form-label">Reason / Note <span class="text-red-500">*</span></label>
                        <textarea name="reason" id="reason" rows="3" class="form-input"
                                  placeholder="Enter the reason for this balance adjustment (minimum 5 characters)..." required minlength="5" maxlength="500">{{ old('reason') }}</textarea>
                        <p class="mt-1 text-xs text-gray-500">This reason will be logged for audit purposes. Minimum 5 characters.</p>
                        @error('reason')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Warning -->
                    <div class="mb-6 p-4 bg-amber-50 border border-amber-200 rounded-lg">
                        <div class="flex">
                            <svg class="w-5 h-5 text-amber-600 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                            <div>
                                <p class="text-sm font-medium text-amber-800">Audit Notice</p>
                                <p class="text-sm text-amber-700 mt-1">This transaction will be recorded with your user ID, timestamp, and the reason provided. All balance operations are subject to audit review.</p>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-end space-x-3">
                        <a href="{{ route('recharge-admin.dashboard') }}" class="btn-secondary">Cancel</a>
                        <button type="submit" class="btn-primary">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Submit
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-recharge-admin-layout>
