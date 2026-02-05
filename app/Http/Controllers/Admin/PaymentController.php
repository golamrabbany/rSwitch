<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $query = Payment::with('user', 'rechargedBy', 'invoice');

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', $request->date_from . ' 00:00:00');
        }

        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', $request->date_to . ' 23:59:59');
        }

        $payments = $query->orderBy('created_at', 'desc')->paginate(20);

        $users = User::orderBy('name')->get(['id', 'name', 'email']);

        return view('admin.payments.index', compact('payments', 'users'));
    }

    public function show(Payment $payment)
    {
        $payment->load('user', 'rechargedBy', 'transaction', 'invoice');

        return view('admin.payments.show', compact('payment'));
    }
}
