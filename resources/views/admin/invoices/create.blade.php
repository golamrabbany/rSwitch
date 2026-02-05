<x-admin-layout>
    <x-slot name="header">Create Invoice</x-slot>

    <div class="max-w-2xl">
        <div class="bg-white shadow sm:rounded-lg">
            <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                <h3 class="text-base font-semibold text-gray-900">New Manual Invoice</h3>
                <p class="mt-1 text-sm text-gray-500">Invoice number will be auto-generated.</p>
            </div>

            <form method="POST" action="{{ route('admin.invoices.store') }}" class="p-6 space-y-6">
                @csrf

                <div>
                    <label for="user_id" class="block text-sm font-medium text-gray-700">User</label>
                    <select id="user_id" name="user_id" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="">Select a user...</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}" {{ old('user_id') == $user->id ? 'selected' : '' }}>
                                {{ $user->name }} ({{ $user->email }}) — {{ ucfirst($user->role) }}
                            </option>
                        @endforeach
                    </select>
                    @error('user_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="period_start" class="block text-sm font-medium text-gray-700">Period Start</label>
                        <input type="date" id="period_start" name="period_start" value="{{ old('period_start') }}" required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        @error('period_start') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="period_end" class="block text-sm font-medium text-gray-700">Period End</label>
                        <input type="date" id="period_end" name="period_end" value="{{ old('period_end') }}" required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        @error('period_end') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label for="call_charges" class="block text-sm font-medium text-gray-700">Call Charges ($)</label>
                        <input type="number" id="call_charges" name="call_charges" value="{{ old('call_charges', '0.00') }}" step="0.01" min="0" required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        @error('call_charges') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="did_charges" class="block text-sm font-medium text-gray-700">DID Charges ($)</label>
                        <input type="number" id="did_charges" name="did_charges" value="{{ old('did_charges', '0.00') }}" step="0.01" min="0" required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        @error('did_charges') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="tax_amount" class="block text-sm font-medium text-gray-700">Tax ($)</label>
                        <input type="number" id="tax_amount" name="tax_amount" value="{{ old('tax_amount', '0.00') }}" step="0.01" min="0" required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        @error('tax_amount') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label for="due_date" class="block text-sm font-medium text-gray-700">Due Date</label>
                    <input type="date" id="due_date" name="due_date" value="{{ old('due_date') }}" required
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    @error('due_date') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="flex items-center gap-4 pt-4 border-t border-gray-200">
                    <button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                        Create Invoice
                    </button>
                    <a href="{{ route('admin.invoices.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</x-admin-layout>
