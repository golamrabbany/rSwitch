<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $query = Invoice::where('user_id', auth()->id());

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $invoices = $query->orderByDesc('created_at')->paginate(20);

        return view('client.invoices.index', compact('invoices'));
    }

    public function show(Invoice $invoice)
    {
        abort_unless($invoice->user_id === auth()->id(), 403);

        $invoice->load('payments');

        return view('client.invoices.show', compact('invoice'));
    }
}
