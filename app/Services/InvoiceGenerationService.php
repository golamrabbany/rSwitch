<?php

namespace App\Services;

use App\Models\CallRecord;
use App\Models\Did;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class InvoiceGenerationService
{
    /**
     * Generate an invoice for a reseller based on their usage.
     */
    public function generateForReseller(User $reseller, Carbon $periodStart, Carbon $periodEnd): Invoice
    {
        // Check for existing invoice
        $existing = Invoice::where('user_id', $reseller->id)
            ->where('period_start', $periodStart->toDateString())
            ->where('period_end', $periodEnd->toDateString())
            ->first();

        if ($existing) {
            throw new \RuntimeException("Invoice already exists for this period: {$existing->invoice_number}");
        }

        // Get call charges grouped by client
        $clientCharges = CallRecord::query()
            ->whereBetween('call_start', [$periodStart, $periodEnd])
            ->where('reseller_id', $reseller->id)
            ->where('disposition', 'ANSWERED')
            ->join('users', 'users.id', '=', 'call_records.user_id')
            ->groupBy('call_records.user_id', 'users.name')
            ->selectRaw('
                call_records.user_id,
                users.name as client_name,
                COUNT(*) as total_calls,
                COALESCE(SUM(call_records.billable_duration), 0) as total_seconds,
                COALESCE(SUM(call_records.reseller_cost), 0) as total_cost
            ')
            ->orderByDesc('total_cost')
            ->get();

        // DID charges for reseller's clients
        $clientIds = $reseller->children()->pluck('id');
        $didCharges = Did::where('status', 'active')
            ->whereIn('assigned_to_user_id', $clientIds)
            ->where('monthly_price', '>', 0)
            ->select('assigned_to_user_id', 'number', 'monthly_price')
            ->get();

        $totalCallCharges = $clientCharges->sum('total_cost');
        $totalDidCharges = $didCharges->sum('monthly_price');
        $totalAmount = bcadd((string) $totalCallCharges, (string) $totalDidCharges, 4);

        // Create invoice
        $invoiceNumber = 'INV-R-' . now()->format('Ymd') . '-' . str_pad(
            Invoice::whereDate('created_at', today())->count() + 1,
            5, '0', STR_PAD_LEFT
        );

        $invoice = Invoice::create([
            'invoice_number' => $invoiceNumber,
            'user_id' => $reseller->id,
            'period_start' => $periodStart->toDateString(),
            'period_end' => $periodEnd->toDateString(),
            'call_charges' => $totalCallCharges,
            'did_charges' => $totalDidCharges,
            'tax_amount' => '0.0000',
            'total_amount' => $totalAmount,
            'status' => 'draft',
            'due_date' => now()->addDays(15)->toDateString(),
        ]);

        // Create line items for client breakdown
        foreach ($clientCharges as $charge) {
            if ((float) $charge->total_cost <= 0) continue;

            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'description' => "Call charges — {$charge->client_name}",
                'type' => 'call_charges',
                'client_name' => $charge->client_name,
                'quantity' => $charge->total_calls,
                'minutes' => round($charge->total_seconds / 60, 2),
                'amount' => $charge->total_cost,
            ]);
        }

        // DID line items
        foreach ($didCharges as $did) {
            $clientName = User::find($did->assigned_to_user_id)?->name ?? 'Unknown';
            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'description' => "DID {$did->number} — {$clientName}",
                'type' => 'did_charges',
                'client_name' => $clientName,
                'quantity' => 1,
                'amount' => $did->monthly_price,
            ]);
        }

        AuditService::logCreated($invoice, 'invoice.generated_for_reseller');

        return $invoice;
    }

    /**
     * Preview charges for a reseller (no invoice created).
     */
    public function previewForReseller(User $reseller, Carbon $periodStart, Carbon $periodEnd): array
    {
        $clientCharges = CallRecord::query()
            ->whereBetween('call_start', [$periodStart, $periodEnd])
            ->where('reseller_id', $reseller->id)
            ->where('disposition', 'ANSWERED')
            ->join('users', 'users.id', '=', 'call_records.user_id')
            ->groupBy('call_records.user_id', 'users.name')
            ->selectRaw('
                users.name as client_name,
                COUNT(*) as total_calls,
                COALESCE(SUM(call_records.billable_duration), 0) as total_seconds,
                COALESCE(SUM(call_records.reseller_cost), 0) as total_cost
            ')
            ->orderByDesc('total_cost')
            ->get();

        $clientIds = $reseller->children()->pluck('id');
        $totalDidCharges = Did::where('status', 'active')
            ->whereIn('assigned_to_user_id', $clientIds)
            ->where('monthly_price', '>', 0)
            ->sum('monthly_price');

        return [
            'client_charges' => $clientCharges,
            'total_call_charges' => $clientCharges->sum('total_cost'),
            'total_did_charges' => $totalDidCharges,
            'total_amount' => $clientCharges->sum('total_cost') + $totalDidCharges,
        ];
    }
}
