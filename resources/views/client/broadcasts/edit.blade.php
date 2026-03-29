<x-client-layout>
    <x-slot name="header">Edit Broadcast</x-slot>

    <div class="page-header-row">
        <div>
            <h2 class="page-title">Edit Broadcast</h2>
            <p class="page-subtitle">{{ $broadcast->name }}</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('client.broadcasts.show', $broadcast) }}" class="btn-action-secondary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Back
            </a>
        </div>
    </div>

    <form method="POST" action="{{ route('client.broadcasts.update', $broadcast) }}" enctype="multipart/form-data"
          x-data="{
              scheduleType: '{{ $broadcast->scheduled_at ? 'scheduled' : 'now' }}',
              phoneListType: 'manual'
          }">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">

                @if($canEditFull)
                {{-- Name (draft/scheduled only) --}}
                <div class="form-card">
                    <div class="form-card-header">
                        <h3 class="form-card-title">Broadcast Details</h3>
                    </div>
                    <div class="form-card-body space-y-4">
                        <div class="form-group">
                            <label for="name" class="form-label">Broadcast Name</label>
                            <input type="text" id="name" name="name" value="{{ old('name', $broadcast->name) }}" required class="form-input">
                        </div>
                    </div>
                </div>

                {{-- Schedule (draft/scheduled only) --}}
                <div class="form-card">
                    <div class="form-card-header">
                        <h3 class="form-card-title">Schedule</h3>
                    </div>
                    <div class="form-card-body space-y-4">
                        <div class="flex gap-3">
                            <label class="flex-1 flex items-center justify-center gap-2 cursor-pointer px-4 py-2 rounded-lg border transition-colors" :class="scheduleType === 'now' ? 'border-indigo-500 bg-indigo-50 text-indigo-700' : 'border-gray-200 bg-white text-gray-600 hover:border-gray-300'">
                                <input type="radio" name="schedule_type" value="now" x-model="scheduleType" class="sr-only">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                <span class="text-sm font-medium">Start Manually</span>
                            </label>
                            <label class="flex-1 flex items-center justify-center gap-2 cursor-pointer px-4 py-2 rounded-lg border transition-colors" :class="scheduleType === 'scheduled' ? 'border-indigo-500 bg-indigo-50 text-indigo-700' : 'border-gray-200 bg-white text-gray-600 hover:border-gray-300'">
                                <input type="radio" name="schedule_type" value="scheduled" x-model="scheduleType" class="sr-only">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                <span class="text-sm font-medium">Schedule</span>
                            </label>
                        </div>
                        <div x-show="scheduleType === 'now'" x-transition class="text-sm text-gray-500">
                            <p>Start the broadcast manually from the detail page.</p>
                        </div>
                        <div x-show="scheduleType === 'scheduled'" x-transition>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="form-group">
                                    <label class="form-label">Date</label>
                                    <input type="date" name="scheduled_date" value="{{ old('scheduled_date', $broadcast->scheduled_at?->format('Y-m-d')) }}" class="form-input" min="{{ now()->format('Y-m-d') }}">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Time</label>
                                    <input type="time" name="scheduled_time" value="{{ old('scheduled_time', $broadcast->scheduled_at?->format('H:i')) }}" class="form-input">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                {{-- SIP Account (all statuses) --}}
                <div class="form-card">
                    <div class="form-card-header">
                        <h3 class="form-card-title">SIP Account</h3>
                    </div>
                    <div class="form-card-body">
                        <div class="form-group">
                            <label class="form-label">SIP Account</label>
                            <select name="sip_account_id" class="form-input">
                                @foreach($sipAccounts as $sip)
                                    <option value="{{ $sip->id }}" {{ $broadcast->sip_account_id == $sip->id ? 'selected' : '' }}>
                                        {{ $sip->username }}{{ $sip->max_channels ? ' ('.$sip->max_channels.' ch)' : '' }}
                                    </option>
                                @endforeach
                            </select>
                            <p class="form-hint">Max concurrent calls and ring timeout are set automatically from the SIP account</p>
                        </div>
                    </div>
                </div>

                @if($canEditFull)
                {{-- Add More Numbers (draft/scheduled only) --}}
                <div class="form-card">
                    <div class="form-card-header">
                        <h3 class="form-card-title">Add More Numbers</h3>
                        <p class="form-card-subtitle">Append additional numbers to this broadcast (existing numbers are kept)</p>
                    </div>
                    <div class="form-card-body space-y-4">
                        <div class="form-group">
                            <label class="form-label">Input Method</label>
                            <div class="flex gap-4 mt-1">
                                <label class="flex items-center gap-2 cursor-pointer px-4 py-2 rounded-lg border transition-colors"
                                       :class="phoneListType === 'manual' ? 'border-indigo-500 bg-indigo-50 text-indigo-700' : 'border-gray-200 bg-white text-gray-600 hover:border-gray-300'">
                                    <input type="radio" value="manual" x-model="phoneListType" class="sr-only">
                                    <span class="font-medium text-sm">Manual Entry</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer px-4 py-2 rounded-lg border transition-colors"
                                       :class="phoneListType === 'csv' ? 'border-indigo-500 bg-indigo-50 text-indigo-700' : 'border-gray-200 bg-white text-gray-600 hover:border-gray-300'">
                                    <input type="radio" value="csv" x-model="phoneListType" class="sr-only">
                                    <span class="font-medium text-sm">CSV Upload</span>
                                </label>
                            </div>
                        </div>
                        <div x-show="phoneListType === 'manual'" x-transition class="form-group">
                            <label class="form-label">Phone Numbers</label>
                            <textarea name="phone_numbers" rows="4" class="form-input font-mono text-sm" placeholder="Enter additional numbers, one per line&#10;&#10;Leave blank to skip"></textarea>
                            <p class="form-hint">New numbers will be appended. Duplicates and DNC numbers are skipped automatically.</p>
                        </div>
                        <div x-show="phoneListType === 'csv'" x-transition class="form-group">
                            <label class="form-label">CSV File</label>
                            <input type="file" name="csv_file" accept=".csv,.txt" class="form-input">
                            <p class="form-hint">One phone number per row. Duplicates skipped.</p>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Form Actions --}}
                <div class="flex items-center justify-end gap-3">
                    <a href="{{ route('client.broadcasts.show', $broadcast) }}" class="btn-secondary">Cancel</a>
                    <button type="submit" name="edit_action" value="save" class="inline-flex items-center px-4 py-2 text-sm font-medium rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                        Save Draft
                    </button>
                    @if(in_array($broadcast->status, ['draft', 'scheduled', 'paused']))
                        <button type="submit" name="edit_action" value="start" class="btn-primary">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/></svg>
                            Update & Start
                        </button>
                    @endif
                </div>
            </div>

            {{-- Sidebar --}}
            <div class="space-y-6">
                <div class="detail-card">
                    <div class="detail-card-header"><h3 class="detail-card-title">Current Status</h3></div>
                    <div class="detail-card-body">
                        <div class="space-y-3 text-sm">
                            <div class="flex justify-between items-center py-1 border-b border-gray-100">
                                <span class="text-gray-500">Status</span>
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium
                                    @if($broadcast->status === 'draft') bg-gray-100 text-gray-700
                                    @elseif($broadcast->status === 'scheduled') bg-blue-100 text-blue-700
                                    @elseif($broadcast->status === 'paused') bg-amber-100 text-amber-700
                                    @endif">{{ ucfirst($broadcast->status) }}</span>
                            </div>
                            <div class="flex justify-between items-center py-1 border-b border-gray-100">
                                <span class="text-gray-500">Type</span>
                                <span class="text-gray-900 font-medium">{{ ucfirst($broadcast->type) }}</span>
                            </div>
                            <div class="flex justify-between items-center py-1 border-b border-gray-100">
                                <span class="text-gray-500">Numbers</span>
                                <span class="text-gray-900 font-medium">{{ number_format($broadcast->total_numbers) }}</span>
                            </div>
                            @if($broadcast->voiceFile)
                            <div class="flex justify-between items-center py-1 border-b border-gray-100">
                                <span class="text-gray-500">Voice File</span>
                                <span class="text-gray-900 text-xs">{{ $broadcast->voiceFile->name }}</span>
                            </div>
                            @endif
                            <div class="flex justify-between items-center py-1">
                                <span class="text-gray-500">Created</span>
                                <span class="text-gray-900">{{ $broadcast->created_at->format('d M Y') }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="detail-card">
                    <div class="detail-card-header"><h3 class="detail-card-title">What You Can Edit</h3></div>
                    <div class="detail-card-body">
                        <ul class="text-xs text-gray-600 space-y-2">
                            @if($canEditFull)
                                <li class="flex items-start gap-2">
                                    <svg class="w-4 h-4 text-emerald-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    <span>Name, Schedule, Add Numbers</span>
                                </li>
                            @else
                                <li class="flex items-start gap-2">
                                    <svg class="w-4 h-4 text-amber-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                    <span>Broadcast was running — only call settings can be changed</span>
                                </li>
                            @endif
                            <li class="flex items-start gap-2">
                                <svg class="w-4 h-4 text-emerald-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                <span>SIP Account (concurrent calls auto-set)</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <svg class="w-4 h-4 text-red-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                <span>Voice/Survey template cannot be changed</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </form>
</x-client-layout>
