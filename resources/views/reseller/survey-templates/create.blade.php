<x-reseller-layout>
    <x-slot name="header">Create Survey Template</x-slot>

    <div class="page-header-row">
        <div>
            <h2 class="page-title">Create Survey Template</h2>
            <p class="page-subtitle">Create a reusable survey configuration</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('reseller.survey-templates.index') }}" class="btn-action-secondary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Back
            </a>
        </div>
    </div>

    <div class="max-w-2xl">
        <form method="POST" action="{{ route('reseller.survey-templates.store') }}">
            @csrf

            <div class="form-card">
                <div class="form-card-header">
                    <h3 class="form-card-title">Template Details</h3>
                    <p class="form-card-subtitle">Basic information about the survey</p>
                </div>
                <div class="form-card-body space-y-4">
                    <div class="form-group">
                        <label for="name" class="form-label">Template Name</label>
                        <input type="text" id="name" name="name" value="{{ old('name') }}" required class="form-input" placeholder="e.g. Customer Satisfaction Survey">
                        <p class="form-hint">A descriptive name for this survey template</p>
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    <div class="form-group">
                        <label for="description" class="form-label">Description</label>
                        <textarea id="description" name="description" rows="3" class="form-input" placeholder="Brief description of the survey purpose...">{{ old('description') }}</textarea>
                        <p class="form-hint">Optional description for internal reference</p>
                        <x-input-error :messages="$errors->get('description')" class="mt-2" />
                    </div>

                    <div class="flex items-start gap-3 p-3 bg-amber-50 rounded-lg border border-amber-200">
                        <svg class="w-5 h-5 text-amber-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        <div class="text-sm text-amber-800">
                            <p class="font-medium">Requires Admin Approval</p>
                            <p class="text-amber-600 mt-0.5">Your survey template will be submitted for review. Admin will configure the survey questions and voice files.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 mt-6">
                <a href="{{ route('reseller.survey-templates.index') }}" class="btn-secondary">Cancel</a>
                <button type="submit" class="btn-primary" style="background: #059669;">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                    Create Template
                </button>
            </div>
        </form>
    </div>
</x-reseller-layout>
