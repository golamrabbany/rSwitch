<x-reseller-layout>
    <x-slot name="header">Edit Survey Template</x-slot>

    <div class="page-header-row">
        <div>
            <h2 class="page-title">Edit: {{ $surveyTemplate->name }}</h2>
            <p class="page-subtitle">Update survey template details</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('reseller.survey-templates.show', $surveyTemplate) }}" class="btn-action-secondary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Cancel
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
            <form method="POST" action="{{ route('reseller.survey-templates.update', $surveyTemplate) }}">
                @csrf
                @method('PUT')

                <div class="form-card">
                    <div class="form-card-header">
                        <h3 class="form-card-title">Template Details</h3>
                    </div>
                    <div class="form-card-body space-y-4">
                        <div class="form-group">
                            <label class="form-label">Template Name</label>
                            <input type="text" name="name" value="{{ old('name', $surveyTemplate->name) }}" required class="form-input" placeholder="e.g. Customer Satisfaction Survey">
                            @error('name') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label">Description</label>
                            <textarea name="description" rows="3" class="form-input" placeholder="Brief description of the survey purpose...">{{ old('description', $surveyTemplate->description) }}</textarea>
                            @error('description') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="flex justify-end pt-2">
                            <button type="submit" class="btn-primary" style="background: #059669;">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                Update Template
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <div class="space-y-4" style="position:sticky; top:1rem;">
            {{-- Client Info --}}
            <div class="detail-card">
                <div class="detail-card-header"><h3 class="detail-card-title">Client</h3></div>
                <div class="detail-card-body">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-emerald-100 flex items-center justify-center">
                            <span class="text-sm font-bold text-emerald-600">{{ strtoupper(substr($surveyTemplate->client?->name ?? '?', 0, 2)) }}</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 truncate">{{ $surveyTemplate->client?->name }}</p>
                            <p class="text-xs text-gray-500 truncate">{{ $surveyTemplate->client?->email }}</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Status --}}
            <div class="detail-card">
                <div class="detail-card-header"><h3 class="detail-card-title">Current Status</h3></div>
                <div class="detail-card-body space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Status</span>
                        <span class="font-medium text-amber-600">Pending</span>
                    </div>
                    <div class="flex justify-between"><span class="text-gray-500">Created</span><span class="font-medium text-gray-700">{{ $surveyTemplate->created_at->format('M d, Y') }}</span></div>
                    <p class="text-xs text-gray-400 pt-2">Admin will configure questions and voice files after review.</p>
                </div>
            </div>
        </div>
    </div>
</x-reseller-layout>
