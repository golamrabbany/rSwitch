<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AuditService;
use App\Services\ListenTokenService;
use Illuminate\Http\Request;

class LiveListenController extends Controller
{
    public function token(Request $request)
    {
        // Defense-in-depth: route middleware already restricts to super_admin.
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $data = $request->validate([
            'linked_id' => ['required', 'string', 'max:128'],
            'unique_id' => ['nullable', 'string', 'max:128'],
            'caller' => ['nullable', 'string', 'max:128'],
            'callee' => ['nullable', 'string', 'max:128'],
        ]);

        AuditService::logAction('call.listen.start', null, [
            'linked_id' => $data['linked_id'],
            'unique_id' => $data['unique_id'] ?? null,
            'caller' => $data['caller'] ?? null,
            'callee' => $data['callee'] ?? null,
        ]);

        $token = ListenTokenService::fromConfig()->mint(
            linkedId: $data['linked_id'],
            uid: (int) auth()->id(),
            ttlSeconds: 30,
        );

        return response()->json(['token' => $token]);
    }
}
