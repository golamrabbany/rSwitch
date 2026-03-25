<x-client-layout>
    <x-slot name="header">Voice Files</x-slot>

    {{-- Page Header --}}
    <div class="page-header-row">
        <div>
            <h2 class="page-title">My Voice Files</h2>
            <p class="page-subtitle">Upload and manage voice files for broadcasts</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('client.voice-files.create') }}" class="btn-action-primary-admin">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                </svg>
                Upload Voice File
            </a>
        </div>
    </div>

    {{-- Table --}}
    <div class="data-table-container">
        @if($voiceFiles->total() > 0)
            <div class="px-4 py-3 bg-gray-50 border-b border-gray-100">
                <span class="text-sm text-gray-600">
                    Showing <span class="font-semibold">{{ $voiceFiles->firstItem() }}–{{ $voiceFiles->lastItem() }}</span> of <span class="font-semibold">{{ number_format($voiceFiles->total()) }}</span> voice files
                </span>
            </div>
        @endif
        <table class="data-table data-table-compact">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Duration</th>
                    <th>Format</th>
                    <th>Status</th>
                    <th>Uploaded</th>
                    <th style="text-align: center">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($voiceFiles as $file)
                    <tr>
                        <td>
                            <div class="user-cell">
                                <div class="avatar avatar-indigo">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"/>
                                    </svg>
                                </div>
                                <div>
                                    <div class="user-name">{{ $file->name }}</div>
                                    <div class="user-email">{{ $file->original_filename ?? '' }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="text-sm text-gray-700">
                            @if($file->duration)
                                {{ floor($file->duration / 60) }}m {{ $file->duration % 60 }}s
                            @else
                                <span class="text-gray-400">--</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge badge-gray">{{ strtoupper($file->format) }}</span>
                        </td>
                        <td>
                            @switch($file->status)
                                @case('approved')
                                    <span class="badge badge-success">Approved</span>
                                    @break
                                @case('pending')
                                    <span class="badge badge-warning">Pending</span>
                                    @break
                                @case('rejected')
                                    <span class="badge badge-danger">Rejected</span>
                                    @break
                                @default
                                    <span class="badge badge-gray">{{ ucfirst($file->status) }}</span>
                            @endswitch
                        </td>
                        <td class="text-sm text-gray-500">
                            <div>{{ $file->created_at->format('M d, Y') }}</div>
                            <div class="text-xs text-gray-400">{{ $file->created_at->diffForHumans() }}</div>
                        </td>
                        <td>
                            <div class="flex items-center justify-center gap-1">
                                <a href="{{ route('client.voice-files.show', $file) }}" class="action-icon" title="View">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-12">
                            <div class="empty-state">
                                <svg class="empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"/>
                                </svg>
                                <p class="empty-text">No voice files yet</p>
                                <p class="text-sm text-gray-400 mt-1">
                                    <a href="{{ route('client.voice-files.create') }}" class="text-indigo-600 hover:text-indigo-700 font-medium">Upload your first voice file</a> to get started
                                </p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($voiceFiles->hasPages())
        <div class="mt-4 flex justify-end">
            {{ $voiceFiles->withQueryString()->onEachSide(1)->links('pagination::simple-tailwind') }}
        </div>
    @endif
</x-client-layout>
