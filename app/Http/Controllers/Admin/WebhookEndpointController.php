<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WebhookEndpoint;
use App\Models\WebhookLog;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class WebhookEndpointController extends Controller
{
    public function index(Request $request)
    {
        $query = WebhookEndpoint::with('user');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('url', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('active', $request->input('status') === 'active');
        }

        $endpoints = $query->latest()->paginate(20)->withQueryString();

        return view('admin.webhooks.index', compact('endpoints'));
    }

    public function create()
    {
        $events = WebhookEndpoint::AVAILABLE_EVENTS;
        $users = \App\Models\User::orderBy('name')->get(['id', 'name', 'email', 'role']);

        return view('admin.webhooks.create', compact('events', 'users'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'url' => ['required', 'url', 'max:500'],
            'events' => ['required', 'array', 'min:1'],
            'events.*' => ['required', 'string', 'in:' . implode(',', array_keys(WebhookEndpoint::AVAILABLE_EVENTS))],
            'description' => ['nullable', 'string', 'max:255'],
            'active' => ['boolean'],
        ]);

        $validated['secret'] = Str::random(40);
        $validated['active'] = $request->boolean('active', true);

        $endpoint = WebhookEndpoint::create($validated);

        AuditService::logAction('webhook_endpoint.created', $endpoint, [
            'url' => $endpoint->url,
            'events' => $endpoint->events,
        ]);

        return redirect()->route('admin.webhooks.show', $endpoint)
            ->with('success', 'Webhook endpoint created. Secret: ' . $endpoint->secret);
    }

    public function show(WebhookEndpoint $webhook)
    {
        $webhook->load('user');

        $logs = WebhookLog::where('webhook_endpoint_id', $webhook->id)
            ->latest()
            ->paginate(20);

        return view('admin.webhooks.show', compact('webhook', 'logs'));
    }

    public function edit(WebhookEndpoint $webhook)
    {
        $events = WebhookEndpoint::AVAILABLE_EVENTS;
        $users = \App\Models\User::orderBy('name')->get(['id', 'name', 'email', 'role']);

        return view('admin.webhooks.edit', compact('webhook', 'events', 'users'));
    }

    public function update(Request $request, WebhookEndpoint $webhook)
    {
        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'url' => ['required', 'url', 'max:500'],
            'events' => ['required', 'array', 'min:1'],
            'events.*' => ['required', 'string', 'in:' . implode(',', array_keys(WebhookEndpoint::AVAILABLE_EVENTS))],
            'description' => ['nullable', 'string', 'max:255'],
            'active' => ['boolean'],
        ]);

        $validated['active'] = $request->boolean('active', true);

        $webhook->update($validated);

        AuditService::logAction('webhook_endpoint.updated', $webhook);

        return redirect()->route('admin.webhooks.show', $webhook)
            ->with('success', 'Webhook endpoint updated.');
    }

    public function destroy(WebhookEndpoint $webhook)
    {
        AuditService::logAction('webhook_endpoint.deleted', $webhook, [
            'url' => $webhook->url,
        ]);

        $webhook->delete();

        return redirect()->route('admin.webhooks.index')
            ->with('success', 'Webhook endpoint deleted.');
    }

    public function regenerateSecret(WebhookEndpoint $webhook)
    {
        $webhook->update(['secret' => Str::random(40)]);

        AuditService::logAction('webhook_endpoint.secret_regenerated', $webhook);

        return redirect()->route('admin.webhooks.show', $webhook)
            ->with('success', 'Webhook secret regenerated: ' . $webhook->secret);
    }
}
