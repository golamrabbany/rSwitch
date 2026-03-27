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

    <script>var _clients = @json($clients->map(fn($c) => ['id' => $c->id, 'name' => $c->name, 'email' => $c->email, 'balance' => (float)$c->balance]));</script>

    <form method="POST" action="{{ route('reseller.survey-templates.store') }}"
          x-data="{
              selectedClient: null,
              onClientChange(id) {
                  this.selectedClient = _clients.find(c => c.id == id) || null;
              }
          }">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Main Form --}}
            <div class="lg:col-span-2 space-y-6">
                <div class="form-card">
                    <div class="form-card-header">
                        <h3 class="form-card-title">Template Details</h3>
                        <p class="form-card-subtitle">Basic information about the survey</p>
                    </div>
                    <div class="form-card-body space-y-4">
                        <div class="form-group">
                            <label for="client_id" class="form-label">Client</label>
                            <select id="client_id" name="client_id" required class="form-input" @change="onClientChange($event.target.value)">
                                <option value="">Select Client</option>
                                @foreach($clients as $client)
                                    <option value="{{ $client->id }}" {{ old('client_id') == $client->id ? 'selected' : '' }}>{{ $client->name }} ({{ $client->email }})</option>
                                @endforeach
                            </select>
                            <p class="form-hint">This template will belong to the selected client</p>
                            <x-input-error :messages="$errors->get('client_id')" class="mt-2" />

                            {{-- Client Info Banner --}}
                            <div x-show="selectedClient" x-transition class="mt-2 flex items-center justify-between p-3 bg-emerald-50 rounded-lg border border-emerald-200">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-full bg-emerald-100 flex items-center justify-center">
                                        <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-emerald-800" x-text="selectedClient?.name"></p>
                                        <p class="text-xs text-emerald-600" x-text="selectedClient?.email"></p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-mono font-semibold" :class="selectedClient?.balance > 0 ? 'text-emerald-600' : 'text-red-500'" x-text="'৳' + (selectedClient?.balance || 0).toFixed(2)"></p>
                                    <p class="text-xs text-gray-500">Balance</p>
                                </div>
                            </div>
                        </div>

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
                    </div>
                </div>

                {{-- Form Actions --}}
                <div class="flex items-center justify-end gap-3">
                    <a href="{{ route('reseller.survey-templates.index') }}" class="btn-secondary">Cancel</a>
                    <button type="submit" class="btn-primary" style="background: #059669;">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                        Create Template
                    </button>
                </div>
            </div>

            {{-- Sidebar --}}
            <div class="space-y-6">
                {{-- Approval Notice --}}
                <div class="detail-card">
                    <div class="detail-card-header">
                        <h3 class="detail-card-title">Approval Required</h3>
                    </div>
                    <div class="detail-card-body">
                        <div class="flex items-center gap-3 p-3 bg-amber-50 rounded-lg">
                            <div class="w-10 h-10 rounded-full bg-amber-100 flex items-center justify-center">
                                <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-amber-800">Pending Review</p>
                                <p class="text-xs text-amber-600">Admin will configure questions and voice files</p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- How It Works --}}
                <div class="detail-card">
                    <div class="detail-card-header">
                        <h3 class="detail-card-title">How It Works</h3>
                    </div>
                    <div class="detail-card-body">
                        <div class="space-y-3">
                            <div class="flex items-start gap-3">
                                <div class="w-6 h-6 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center text-xs font-bold flex-shrink-0">1</div>
                                <p class="text-sm text-gray-600">You create the template with name and description</p>
                            </div>
                            <div class="flex items-start gap-3">
                                <div class="w-6 h-6 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center text-xs font-bold flex-shrink-0">2</div>
                                <p class="text-sm text-gray-600">Admin reviews, adds survey questions and voice files</p>
                            </div>
                            <div class="flex items-start gap-3">
                                <div class="w-6 h-6 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center text-xs font-bold flex-shrink-0">3</div>
                                <p class="text-sm text-gray-600">Once approved, use it when creating survey broadcasts</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</x-reseller-layout>
