<x-admin-layout>
    <x-slot name="header">Bulk Import</x-slot>

    {{-- Page Header --}}
    <div class="page-header-row">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-cyan-500 to-teal-600 flex items-center justify-center">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                </svg>
            </div>
            <div>
                <h2 class="page-title">Bulk Import</h2>
                <p class="page-subtitle">Import users, SIP accounts, and DIDs from CSV files</p>
            </div>
        </div>
    </div>

    {{-- Import Errors/Warnings --}}
    @if (session('import_errors') && count(session('import_errors')) > 0)
        <div class="import-warning-card mb-6">
            <div class="import-warning-header">
                <div class="import-warning-icon">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <div>
                    <h4 class="import-warning-title">Import Warnings ({{ count(session('import_errors')) }})</h4>
                    <p class="import-warning-subtitle">Some rows could not be imported</p>
                </div>
            </div>
            <div class="import-warning-body">
                <ul class="import-warning-list">
                    @foreach (session('import_errors') as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <div class="max-w-4xl space-y-6">
        {{-- Users Import --}}
        <div class="import-card">
            <div class="import-card-header">
                <div class="import-card-icon import-card-icon-users">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                </div>
                <div class="import-card-header-text">
                    <h3 class="import-card-title">Import Users</h3>
                    <p class="import-card-subtitle">Create multiple user accounts at once</p>
                </div>
            </div>
            <div class="import-card-body">
                <div class="import-columns-hint">
                    <span class="import-columns-label">Required columns:</span>
                    <div class="import-columns-list">
                        <code>name</code>
                        <code>email</code>
                        <code>password</code>
                        <code>role</code>
                        <code>billing_type</code>
                        <code>balance</code>
                        <code>rate_group_id</code>
                        <code>parent_id</code>
                    </div>
                </div>
                <form method="POST" action="{{ route('admin.bulk-import.users') }}" enctype="multipart/form-data" class="import-form">
                    @csrf
                    <div class="import-file-input">
                        <input type="file" id="csv_users" name="csv_file" accept=".csv,.txt" required class="import-file">
                    </div>
                    <button type="submit" class="btn-primary">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                        </svg>
                        Import Users
                    </button>
                </form>
                <details class="import-example">
                    <summary class="import-example-toggle">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                        View CSV Example
                    </summary>
                    <pre class="import-example-code">name,email,password,role,billing_type,balance,rate_group_id,parent_id
John Doe,john@example.com,SecurePass123,client,prepaid,100.00,1,2
Jane Smith,jane@example.com,,reseller,postpaid,0,1,</pre>
                </details>
            </div>
        </div>

        {{-- SIP Accounts Import --}}
        <div class="import-card">
            <div class="import-card-header">
                <div class="import-card-icon import-card-icon-sip">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                    </svg>
                </div>
                <div class="import-card-header-text">
                    <h3 class="import-card-title">Import SIP Accounts</h3>
                    <p class="import-card-subtitle">Provision multiple SIP extensions at once</p>
                </div>
            </div>
            <div class="import-card-body">
                <div class="import-columns-hint">
                    <span class="import-columns-label">Required columns:</span>
                    <div class="import-columns-list">
                        <code>username</code>
                        <code>password</code>
                        <code>user_id</code>
                        <code>auth_type</code>
                        <code>allowed_ips</code>
                        <code>caller_id_name</code>
                        <code>caller_id_number</code>
                        <code>max_channels</code>
                    </div>
                </div>
                <form method="POST" action="{{ route('admin.bulk-import.sip-accounts') }}" enctype="multipart/form-data" class="import-form">
                    @csrf
                    <div class="import-file-input">
                        <input type="file" id="csv_sip" name="csv_file" accept=".csv,.txt" required class="import-file">
                    </div>
                    <button type="submit" class="btn-primary">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                        </svg>
                        Import SIP Accounts
                    </button>
                </form>
                <details class="import-example">
                    <summary class="import-example-toggle">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                        View CSV Example
                    </summary>
                    <pre class="import-example-code">username,password,user_id,auth_type,allowed_ips,caller_id_name,caller_id_number,max_channels
1001,,3,password,,John Doe,+18005551234,5
1002,MyPass!2024,3,ip,192.168.1.100,,,10</pre>
                </details>
            </div>
        </div>

        {{-- DIDs Import --}}
        <div class="import-card">
            <div class="import-card-header">
                <div class="import-card-icon import-card-icon-dids">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/>
                    </svg>
                </div>
                <div class="import-card-header-text">
                    <h3 class="import-card-title">Import DIDs</h3>
                    <p class="import-card-subtitle">Add multiple phone numbers at once</p>
                </div>
            </div>
            <div class="import-card-body">
                <div class="import-columns-hint">
                    <span class="import-columns-label">Required columns:</span>
                    <div class="import-columns-list">
                        <code>number</code>
                        <code>provider</code>
                        <code>trunk_id</code>
                        <code>assigned_to_user_id</code>
                        <code>destination_type</code>
                        <code>destination_id</code>
                        <code>destination_number</code>
                        <code>monthly_cost</code>
                        <code>monthly_price</code>
                    </div>
                </div>
                <form method="POST" action="{{ route('admin.bulk-import.dids') }}" enctype="multipart/form-data" class="import-form">
                    @csrf
                    <div class="import-file-input">
                        <input type="file" id="csv_dids" name="csv_file" accept=".csv,.txt" required class="import-file">
                    </div>
                    <button type="submit" class="btn-primary">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                        </svg>
                        Import DIDs
                    </button>
                </form>
                <details class="import-example">
                    <summary class="import-example-toggle">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                        View CSV Example
                    </summary>
                    <pre class="import-example-code">number,provider,trunk_id,assigned_to_user_id,destination_type,destination_id,destination_number,monthly_cost,monthly_price
+18005551234,Telnyx,1,3,sip_account,1,,1.50,3.00
+18005551235,Telnyx,1,,external,,+442071234567,1.50,3.00</pre>
                </details>
            </div>
        </div>

        {{-- Import Tips --}}
        <div class="detail-card">
            <div class="detail-card-header">
                <h3 class="detail-card-title">Import Tips</h3>
            </div>
            <div class="detail-card-body">
                <ul class="import-tips-list">
                    <li>
                        <svg class="w-4 h-4 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span>Save your file as CSV (Comma Separated Values) format</span>
                    </li>
                    <li>
                        <svg class="w-4 h-4 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span>First row must contain column headers</span>
                    </li>
                    <li>
                        <svg class="w-4 h-4 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span>Leave optional fields empty (not "null" or "N/A")</span>
                    </li>
                    <li>
                        <svg class="w-4 h-4 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span>Use UTF-8 encoding for special characters</span>
                    </li>
                    <li>
                        <svg class="w-4 h-4 text-amber-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        <span>Duplicate entries will be skipped</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</x-admin-layout>
