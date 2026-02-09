<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\User;
use App\Notifications\InvoiceIssuedNotification;
use App\Services\AuditService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $query = Invoice::with('user');

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', $request->date_from . ' 00:00:00');
        }

        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', $request->date_to . ' 23:59:59');
        }

        $invoices = $query->orderBy('created_at', 'desc')->paginate(20);

        $users = User::whereIn('role', ['reseller', 'client'])->orderBy('name')->get(['id', 'name', 'email']);

        $stats = [
            'draft' => Invoice::where('status', 'draft')->count(),
            'issued' => Invoice::where('status', 'issued')->count(),
            'paid' => Invoice::where('status', 'paid')->count(),
            'overdue' => Invoice::where('status', 'overdue')->count(),
        ];

        return view('admin.invoices.index', compact('invoices', 'users', 'stats'));
    }

    public function create()
    {
        $users = User::whereIn('role', ['reseller', 'client'])
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'role']);

        return view('admin.invoices.create', compact('users'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
            'call_charges' => ['required', 'numeric', 'min:0'],
            'did_charges' => ['required', 'numeric', 'min:0'],
            'tax_amount' => ['required', 'numeric', 'min:0'],
            'due_date' => ['required', 'date'],
        ]);

        $totalAmount = bcadd(
            bcadd((string) $validated['call_charges'], (string) $validated['did_charges'], 4),
            (string) $validated['tax_amount'],
            4
        );

        $invoiceNumber = 'INV-' . now()->format('Ymd') . '-' . str_pad(
            Invoice::whereDate('created_at', today())->count() + 1,
            5, '0', STR_PAD_LEFT
        );

        $invoice = Invoice::create([
            'invoice_number' => $invoiceNumber,
            'user_id' => $validated['user_id'],
            'period_start' => $validated['period_start'],
            'period_end' => $validated['period_end'],
            'call_charges' => $validated['call_charges'],
            'did_charges' => $validated['did_charges'],
            'tax_amount' => $validated['tax_amount'],
            'total_amount' => $totalAmount,
            'status' => 'draft',
            'due_date' => $validated['due_date'],
        ]);

        AuditService::logCreated($invoice, 'invoice.created');

        return redirect()->route('admin.invoices.show', $invoice)
            ->with('success', "Invoice {$invoiceNumber} created.");
    }

    public function show(Invoice $invoice)
    {
        $invoice->load('user', 'payments');

        return view('admin.invoices.show', compact('invoice'));
    }

    public function update(Request $request, Invoice $invoice)
    {
        $validated = $request->validate([
            'action' => ['required', Rule::in(['issue', 'mark_paid', 'cancel'])],
        ]);

        $original = $invoice->getAttributes();

        switch ($validated['action']) {
            case 'issue':
                if ($invoice->status !== 'draft') {
                    return back()->with('warning', 'Only draft invoices can be issued.');
                }
                $invoice->update(['status' => 'issued']);
                $invoice->user->notify(new InvoiceIssuedNotification($invoice));
                break;

            case 'mark_paid':
                if (!in_array($invoice->status, ['issued', 'overdue'])) {
                    return back()->with('warning', 'Only issued or overdue invoices can be marked as paid.');
                }
                $invoice->update(['status' => 'paid', 'paid_at' => now()]);
                break;

            case 'cancel':
                if ($invoice->status === 'paid') {
                    return back()->with('warning', 'Paid invoices cannot be cancelled.');
                }
                $invoice->update(['status' => 'cancelled']);
                break;
        }

        AuditService::logUpdated($invoice, $original, 'invoice.' . $validated['action']);

        return back()->with('success', 'Invoice status updated.');
    }

    public function pdf(Invoice $invoice)
    {
        $invoice->load('user');

        $pdf = Pdf::loadView('pdf.invoice', compact('invoice'));

        return $pdf->download("{$invoice->invoice_number}.pdf");
    }
}
