<?php

namespace App\Http\Controllers\Reseller;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $query = Payment::where('user_id', auth()->id());

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', $request->date_from . ' 00:00:00');
        }

        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', $request->date_to . ' 23:59:59');
        }

        $payments = $query->with('rechargedBy:id,name')
            ->orderByDesc('created_at')
            ->paginate(30);

        $stats = Payment::where('user_id', auth()->id())->selectRaw('
            COUNT(*) as total,
            COALESCE(SUM(CASE WHEN status = "completed" THEN amount ELSE 0 END), 0) as total_paid,
            COALESCE(SUM(CASE WHEN status = "pending" THEN amount ELSE 0 END), 0) as total_pending
        ')->first();

        return view('reseller.payments.index', compact('payments', 'stats'));
    }
}
