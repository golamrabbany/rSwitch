<x-admin-layout>
    <x-slot name="header">Invoice Details</x-slot>

    {{-- Page Header --}}
    <div class="page-header-row">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
            </div>
            <div>
                <h2 class="page-title">{{ $invoice->invoice_number }}</h2>
                <p class="page-subtitle">Invoice details and payment history</p>
            </div>
        </div>
        <div class="page-actions">
            <a href="{{ route('admin.invoices.pdf', $invoice) }}" class="btn-action-secondary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Download PDF
            </a>
            <a href="{{ route('admin.invoices.index') }}" class="btn-action-outline">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back to Invoices
            </a>
        </div>
    </div>

    {{-- Status Banner with Actions --}}
    @php
        $statusConfig = match($invoice->status) {
            'draft' => ['class' => 'invoice-status-draft', 'icon' => 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z', 'label' => 'Draft Invoice'],
            'issued' => ['class' => 'invoice-status-issued', 'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z', 'label' => 'Issued - Awaiting Payment'],
            'paid' => ['class' => 'invoice-status-paid', 'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z', 'label' => 'Paid'],
            'overdue' => ['class' => 'invoice-status-overdue', 'icon' => 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z', 'label' => 'Overdue'],
            'cancelled' => ['class' => 'invoice-status-cancelled', 'icon' => 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z', 'label' => 'Cancelled'],
            default => ['class' => 'invoice-status-draft', 'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z', 'label' => 'Unknown']
        };
    @endphp
    <div class="invoice-status-banner {{ $statusConfig['class'] }}">
        <div class="invoice-status-banner-content">
            <div class="invoice-status-banner-icon">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $statusConfig['icon'] }}"/>
                </svg>
            </div>
            <div class="invoice-status-banner-text">
                <span class="invoice-status-banner-title">{{ $statusConfig['label'] }}</span>
                @if($invoice->paid_at)
                    <span class="invoice-status-banner-meta">Paid on {{ $invoice->paid_at->format('M d, Y H:i') }}</span>
                @elseif($invoice->status === 'issued' || $invoice->status === 'overdue')
                    <span class="invoice-status-banner-meta">Due {{ $invoice->due_date?->format('M d, Y') }}</span>
                @endif
            </div>
        </div>
        <div class="invoice-status-banner-actions">
            @if($invoice->status === 'draft')
                <form method="POST" action="{{ route('admin.invoices.update', $invoice) }}" class="inline">
                    @csrf @method('PUT')
                    <input type="hidden" name="action" value="issue">
                    <button type="submit" class="btn-primary" onclick="return confirm('Issue this invoice?')">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                        </svg>
                        Issue Invoice
                    </button>
                </form>
            @endif

            @if(in_array($invoice->status, ['issued', 'overdue']))
                <form method="POST" action="{{ route('admin.invoices.update', $invoice) }}" class="inline">
                    @csrf @method('PUT')
                    <input type="hidden" name="action" value="mark_paid">
                    <button type="submit" class="btn-success" onclick="return confirm('Mark this invoice as paid?')">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Mark Paid
                    </button>
                </form>
            @endif

            @if($invoice->status !== 'paid' && $invoice->status !== 'cancelled')
                <form method="POST" action="{{ route('admin.invoices.update', $invoice) }}" class="inline">
                    @csrf @method('PUT')
                    <input type="hidden" name="action" value="cancel">
                    <button type="submit" class="btn-secondary text-red-600 hover:text-red-700" onclick="return confirm('Cancel this invoice?')">
                        Cancel
                    </button>
                </form>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Main Content --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Invoice Details --}}
            <div class="detail-card">
                <div class="detail-card-header">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <h3 class="detail-card-title">Invoice Details</h3>
                    </div>
                </div>
                <div class="detail-card-body">
                    <div class="detail-grid">
                        <div class="detail-item">
                            <span class="detail-label">Invoice Number</span>
                            <span class="detail-value font-mono">{{ $invoice->invoice_number }}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Status</span>
                            @php
                                $statusClass = match($invoice->status) {
                                    'draft' => 'badge-gray',
                                    'issued' => 'badge-blue',
                                    'paid' => 'badge-success',
                                    'overdue' => 'badge-danger',
                                    'cancelled' => 'badge-gray',
                                    default => 'badge-gray'
                                };
                            @endphp
                            <span class="badge {{ $statusClass }}">{{ ucfirst($invoice->status) }}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Billing Period</span>
                            <span class="detail-value">{{ $invoice->period_start?->format('M d, Y') }} - {{ $invoice->period_end?->format('M d, Y') }}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Due Date</span>
                            <span class="detail-value">{{ $invoice->due_date?->format('M d, Y') }}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Created</span>
                            <span class="detail-value">{{ $invoice->created_at->format('M d, Y H:i') }}</span>
                        </div>
                        @if($invoice->paid_at)
                        <div class="detail-item">
                            <span class="detail-label">Paid At</span>
                            <span class="detail-value">{{ $invoice->paid_at->format('M d, Y H:i') }}</span>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Charges Breakdown --}}
            <div class="detail-card">
                <div class="detail-card-header">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                        </svg>
                        <h3 class="detail-card-title">Charges Breakdown</h3>
                    </div>
                </div>
                <div class="detail-card-body p-0">
                    <div class="invoice-charges-table">
                        <div class="invoice-charge-row">
                            <span class="invoice-charge-label">Call Charges</span>
                            <span class="invoice-charge-value">{{ format_currency($invoice->call_charges) }}</span>
                        </div>
                        <div class="invoice-charge-row">
                            <span class="invoice-charge-label">DID Charges</span>
                            <span class="invoice-charge-value">{{ format_currency($invoice->did_charges) }}</span>
                        </div>
                        <div class="invoice-charge-row">
                            <span class="invoice-charge-label">Tax</span>
                            <span class="invoice-charge-value">{{ format_currency($invoice->tax_amount) }}</span>
                        </div>
                        <div class="invoice-charge-row invoice-charge-total">
                            <span class="invoice-charge-label">Total Amount</span>
                            <span class="invoice-charge-value">{{ format_currency($invoice->total_amount) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Line Items --}}
            @if($invoice->items->count() > 0)
            <div class="detail-card">
                <div class="detail-card-header">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                        </svg>
                        <h3 class="detail-card-title">Line Items ({{ $invoice->items->count() }})</h3>
                    </div>
                </div>
                <div class="detail-card-body p-0">
                    <table class="data-table data-table-compact">
                        <thead>
                            <tr>
                                <th>Description</th>
                                <th>Type</th>
                                <th style="text-align: right">Calls</th>
                                <th style="text-align: right">Minutes</th>
                                <th style="text-align: right">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($invoice->items as $item)
                                <tr>
                                    <td class="text-gray-900">{{ $item->description }}</td>
                                    <td>
                                        @if($item->type === 'call_charges')
                                            <span class="badge badge-blue">Calls</span>
                                        @elseif($item->type === 'did_charges')
                                            <span class="badge badge-purple">DID</span>
                                        @else
                                            <span class="badge badge-gray">Adj</span>
                                        @endif
                                    </td>
                                    <td style="text-align: right" class="tabular-nums">{{ number_format($item->quantity) }}</td>
                                    <td style="text-align: right" class="tabular-nums">{{ $item->minutes > 0 ? number_format($item->minutes, 1) : '—' }}</td>
                                    <td style="text-align: right" class="tabular-nums font-mono font-medium text-gray-900">{{ format_currency($item->amount) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            {{-- Payments --}}
            @if($invoice->payments->count() > 0)
            <div class="detail-card">
                <div class="detail-card-header">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                        <h3 class="detail-card-title">Payments ({{ $invoice->payments->count() }})</h3>
                    </div>
                </div>
                <div class="detail-card-body p-0">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th class="text-right">Amount</th>
                                <th>Method</th>
                                <th>Status</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($invoice->payments as $payment)
                                <tr>
                                    <td class="text-gray-900">{{ $payment->created_at->format('M d, Y H:i') }}</td>
                                    <td class="text-right font-semibold">{{ format_currency($payment->amount) }}</td>
                                    <td class="text-gray-600">{{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}</td>
                                    <td>
                                        @php
                                            $paymentClass = match($payment->status) {
                                                'completed' => 'badge-success',
                                                'pending' => 'badge-warning',
                                                default => 'badge-danger'
                                            };
                                        @endphp
                                        <span class="badge {{ $paymentClass }}">{{ ucfirst($payment->status) }}</span>
                                    </td>
                                    <td class="text-center">
                                        <a href="{{ route('admin.payments.show', $payment) }}" class="action-icon" title="View">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            {{-- Customer Info --}}
            <div class="detail-card">
                <div class="detail-card-header">
                    <h3 class="detail-card-title">Customer</h3>
                </div>
                <div class="detail-card-body">
                    <div class="flex items-center gap-4">
                        <div class="avatar {{ $invoice->user->role === 'reseller' ? 'avatar-emerald' : 'avatar-sky' }} w-14 h-14 text-lg">
                            {{ strtoupper(substr($invoice->user->name, 0, 1)) }}
                        </div>
                        <div>
                            <a href="{{ route('admin.users.show', $invoice->user) }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-700">
                                {{ $invoice->user->name }}
                            </a>
                            <p class="text-xs text-gray-500">{{ $invoice->user->email }}</p>
                            <span class="badge badge-gray mt-1">{{ ucfirst($invoice->user->role) }}</span>
                        </div>
                    </div>
                    <div class="mt-4 pt-4 border-t border-gray-100">
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-gray-500">Current Balance</span>
                            <span class="font-semibold text-gray-900">{{ format_currency($invoice->user->balance) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Amount Summary --}}
            <div class="detail-card">
                <div class="detail-card-header">
                    <h3 class="detail-card-title">Amount Summary</h3>
                </div>
                <div class="detail-card-body">
                    <div class="text-center">
                        <span class="text-3xl font-bold text-gray-900">{{ format_currency($invoice->total_amount) }}</span>
                        <p class="text-xs text-gray-500 mt-1">Total Amount Due</p>
                    </div>
                    <div class="mt-4 pt-4 border-t border-gray-100 space-y-2">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">Paid</span>
                            <span class="text-emerald-600 font-medium">{{ format_currency($invoice->payments->where('status', 'completed')->sum('amount')) }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">Outstanding</span>
                            <span class="text-red-600 font-medium">{{ format_currency($invoice->total_amount - $invoice->payments->where('status', 'completed')->sum('amount')) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Quick Actions --}}
            <div class="detail-card">
                <div class="detail-card-header">
                    <h3 class="detail-card-title">Quick Actions</h3>
                </div>
                <div class="detail-card-body space-y-2">
                    <a href="{{ route('admin.invoices.pdf', $invoice) }}" class="quick-action-btn">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Download PDF
                    </a>
                    <a href="{{ route('admin.users.show', $invoice->user) }}" class="quick-action-btn">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        View Customer
                    </a>
                    <a href="{{ route('admin.transactions.index', ['user_id' => $invoice->user_id]) }}" class="quick-action-btn">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                        View Transactions
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
