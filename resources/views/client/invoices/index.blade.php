<x-client-layout>
    <x-slot name="header">Invoices</x-slot>

    <div class="page-header-row">
        <div>
            <h2 class="page-title">My Invoices</h2>
            <p class="page-subtitle">Billing history and invoice details</p>
        </div>
    </div>

    {{-- Filter --}}
    <div class="filter-card">
        <form method="GET" class="filter-row flex-wrap">
            <select name="status" class="filter-select">
                <option value="">All Statuses</option>
                @foreach (['draft', 'issued', 'paid', 'overdue', 'cancelled'] as $s)
                    <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn-search">Filter</button>
            @if(request('status'))
                <a href="{{ route('client.invoices.index') }}" class="btn-clear">Clear</a>
            @endif
        </form>
    </div>

    <div class="data-table-container">
        @if($invoices->total() > 0)
            <div class="px-4 py-3 bg-gray-50 border-b border-gray-100">
                <span class="text-sm text-gray-600">
                    Showing <span class="font-semibold">{{ $invoices->firstItem() }}–{{ $invoices->lastItem() }}</span> of <span class="font-semibold">{{ number_format($invoices->total()) }}</span> invoices
                </span>
            </div>
        @endif
        <table class="data-table data-table-compact">
            <thead>
                <tr>
                    <th>Invoice #</th>
                    <th>Period</th>
                    <th style="text-align: right">Amount</th>
                    <th>Status</th>
                    <th>Due Date</th>
                    <th style="text-align: center">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($invoices as $invoice)
                    <tr>
                        <td class="font-medium text-gray-900">{{ $invoice->invoice_number }}</td>
                        <td class="text-gray-500">
                            {{ $invoice->period_start?->format('M d') }} – {{ $invoice->period_end?->format('M d, Y') }}
                        </td>
                        <td style="text-align: right" class="tabular-nums font-mono font-medium text-gray-900">{{ format_currency($invoice->total_amount) }}</td>
                        <td>
                            @switch($invoice->status)
                                @case('paid') <span class="badge badge-success">Paid</span> @break
                                @case('issued') <span class="badge badge-blue">Issued</span> @break
                                @case('overdue') <span class="badge badge-danger">Overdue</span> @break
                                @case('cancelled') <span class="badge badge-gray">Cancelled</span> @break
                                @default <span class="badge badge-gray">Draft</span>
                            @endswitch
                        </td>
                        <td class="text-gray-500">{{ $invoice->due_date?->format('M d, Y') }}</td>
                        <td>
                            <div class="flex items-center justify-center gap-1">
                                <a href="{{ route('client.invoices.show', $invoice) }}" class="action-icon" title="View">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </a>
                                <a href="{{ route('client.invoices.pdf', $invoice) }}" class="action-icon" title="Download PDF">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                    </svg>
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center py-12"><div class="empty-state"><p class="empty-text">No invoices found</p></div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($invoices->hasPages())
        <div class="mt-4 flex justify-end">
            {{ $invoices->withQueryString()->onEachSide(1)->links('pagination::simple-tailwind') }}
        </div>
    @endif
</x-client-layout>
