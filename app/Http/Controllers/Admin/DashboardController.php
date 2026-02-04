<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CallRecord;
use App\Models\SipAccount;
use App\Models\Trunk;
use App\Models\User;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $stats = [
            'total_resellers' => User::where('role', 'reseller')->count(),
            'total_clients' => User::where('role', 'client')->count(),
            'total_sip_accounts' => SipAccount::count(),
            'active_trunks' => Trunk::where('status', 'active')->count(),
        ];

        return view('admin.dashboard', compact('stats'));
    }
}
