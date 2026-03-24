<x-reseller-layout>
    <x-slot name="header">Edit Rate Group</x-slot>

    {{-- Page Header --}}
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-emerald-400 to-emerald-500 flex items-center justify-center shadow-lg shadow-emerald-200">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
            </div>
            <div>
                <h2 class="text-xl font-bold text-gray-900">Edit Rate Group</h2>
                <p class="text-sm text-gray-500">Update details for "{{ $tariff->name }}"</p>
            </div>
        </div>
        <a href="{{ route('reseller.tariffs.show', $tariff) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 text-sm font-medium rounded-lg hover:bg-gray-50">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Back to Rate Group
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Main Form --}}
        <div class="lg:col-span-2 space-y-6">
            <form method="POST" action="{{ route('reseller.tariffs.update', $tariff) }}">
                @csrf
                @method('PUT')

                {{-- Rate Group Details --}}
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100">
                        <h3 class="font-semibold text-gray-900">Rate Group Details</h3>
                        <p class="text-sm text-gray-500">Basic information about this rate group</p>
                    </div>
                    <div class="p-6 space-y-5">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-1.5">Name</label>
                            <input type="text" name="name" id="name" required value="{{ old('name', $tariff->name) }}" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                            <p class="text-xs text-gray-400 mt-1.5">A descriptive name for this rate group</p>
                            @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="description" class="block text-sm font-medium text-gray-700 mb-1.5">Description</label>
                            <textarea name="description" id="description" rows="3" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">{{ old('description', $tariff->description) }}</textarea>
                            <p class="text-xs text-gray-400 mt-1.5">Optional notes about this rate group's purpose</p>
                        </div>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="flex items-center justify-end gap-3 mt-6">
                    <a href="{{ route('reseller.tariffs.show', $tariff) }}" class="px-5 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</a>
                    <button type="submit" class="px-6 py-2.5 bg-emerald-600 text-white text-sm font-medium rounded-lg hover:bg-emerald-700 shadow-sm">Save Changes</button>
                </div>
            </form>
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            {{-- Rate Group Info --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100">
                    <h3 class="font-semibold text-gray-900">Rate Group Info</h3>
                </div>
                <div class="p-5 space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-500">Type</span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-700">Reseller</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-500">Base Tariff</span>
                        <span class="text-sm font-medium text-gray-900">{{ $tariff->parentRateGroup?->name ?? '—' }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-500">Rates</span>
                        <span class="text-sm font-medium text-gray-900">{{ $tariff->rates()->count() }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-500">Clients Using</span>
                        <span class="text-sm font-medium text-gray-900">{{ $tariff->users()->count() }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-500">Created</span>
                        <span class="text-sm text-gray-900">{{ $tariff->created_at?->format('M d, Y') }}</span>
                    </div>
                </div>
            </div>

            {{-- Danger Zone --}}
            <div class="bg-white rounded-xl border border-red-200 shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-red-100">
                    <h3 class="font-semibold text-red-600">Danger Zone</h3>
                </div>
                <div class="p-5">
                    <p class="text-sm text-gray-500 mb-4">Permanently delete this rate group. This cannot be undone. Rate groups with assigned clients cannot be deleted.</p>
                    <form method="POST" action="{{ route('reseller.tariffs.destroy', $tariff) }}" onsubmit="return confirm('Are you sure you want to delete this rate group? This action cannot be undone.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="w-full px-4 py-2 text-sm font-medium text-red-600 bg-red-50 border border-red-200 rounded-lg hover:bg-red-100 transition-colors">
                            Delete This Rate Group
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-reseller-layout>
